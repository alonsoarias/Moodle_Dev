// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Course index progress loader - Complete Redesign with Activity Status Icons
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log'], function($, Ajax, Log) {

    // Cache for activities completion data
    var activitiesCompletionCache = null;

    /**
     * Initialize the course index progress system
     *
     * @param {Number} courseId The course ID
     */
    var init = function(courseId) {
        Log.debug('courseindex_progress: Initializing for course ' + courseId);

        // Load overall course progress
        loadCourseProgress(courseId);

        // Wait for activities to be loaded in the DOM, then load completion states
        waitForActivitiesAndLoad(courseId);

        // Wait for sections to be loaded, then load section percentages
        var checkInterval = setInterval(function() {
            var sections = $('.course-index-section, .courseindex-section-redesign');
            if (sections.length > 0) {
                clearInterval(checkInterval);
                loadSectionsProgress(courseId);
            }
        }, 500);

        // Listen for state changes (when course index updates)
        $(document).on('state-changed', function() {
            Log.debug('courseindex_progress: State changed event detected');
            loadActivitiesCompletion(courseId);
            loadSectionsProgress(courseId);
        });

        // Also listen for completion updates
        $(document).on('core_completion:activity_completed', function() {
            Log.debug('courseindex_progress: Activity completion detected');
            setTimeout(function() {
                loadActivitiesCompletion(courseId);
                loadCourseProgress(courseId);
                loadSectionsProgress(courseId);
            }, 500);
        });
    };

    /**
     * Wait for activities to be present in DOM, then load completion states
     *
     * @param {Number} courseId The course ID
     */
    var waitForActivitiesAndLoad = function(courseId) {
        var attempts = 0;
        var maxAttempts = 20; // 10 seconds max

        var checkInterval = setInterval(function() {
            attempts++;
            var activities = $('.activity-status-icon[data-cm-id]');

            if (activities.length > 0) {
                Log.debug('courseindex_progress: Found ' + activities.length + ' activities in DOM');
                clearInterval(checkInterval);
                loadActivitiesCompletion(courseId);
            } else if (attempts >= maxAttempts) {
                Log.debug('courseindex_progress: Timeout waiting for activities, attempting load anyway');
                clearInterval(checkInterval);
                loadActivitiesCompletion(courseId);
            }
        }, 500);
    };

    /**
     * Load overall course progress
     *
     * @param {Number} courseId The course ID
     */
    var loadCourseProgress = function(courseId) {
        var promises = Ajax.call([{
            methodname: 'theme_compecer_get_course_progress',
            args: {courseid: courseId}
        }]);

        promises[0]
            .then(function(response) {
                Log.debug('courseindex_progress: Course progress loaded', response);
                if (response && response.hasprogress) {
                    updateCourseProgressDisplay(response);
                    showIconLegend();
                } else {
                    showNoTrackingMessage();
                }
                return;
            })
            .catch(function(error) {
                if (error && error.errorcode !== 'nopermissions') {
                    Log.error('courseindex_progress: Error loading course progress: ' + error.message);
                }
            });
    };

    /**
     * Update the course overall progress display (simplified - no activities count)
     *
     * @param {Object} data Progress data
     */
    var updateCourseProgressDisplay = function(data) {
        var $header = $('#courseindex-progress-header');
        if ($header.length === 0) return;

        var percentage = data.percentage || 0;

        // Update percentage display
        $header.find('[data-region="course-percentage"]').text(percentage + '%');

        // Update progress bar
        var $progressBar = $header.find('[data-region="course-progress-bar"]');
        if ($progressBar.length > 0) {
            $progressBar.css('width', percentage + '%').attr('aria-valuenow', percentage);

            // Add color class based on progress
            $progressBar.removeClass('progress-0 progress-low progress-medium progress-high progress-complete');
            if (percentage === 0) {
                $progressBar.addClass('progress-0');
            } else if (percentage < 40) {
                $progressBar.addClass('progress-low');
            } else if (percentage < 70) {
                $progressBar.addClass('progress-medium');
            } else if (percentage < 100) {
                $progressBar.addClass('progress-high');
            } else {
                $progressBar.addClass('progress-complete');
            }
        }

        // Show the header
        $header.fadeIn(300);
    };

    /**
     * Show icon legend
     */
    var showIconLegend = function() {
        $('#courseindex-icon-legend').fadeIn(300);
    };

    /**
     * Show message when completion tracking is not enabled
     */
    var showNoTrackingMessage = function() {
        $('#courseindex-no-tracking').fadeIn(300);
    };

    /**
     * Load activities completion states
     *
     * @param {Number} courseId The course ID
     */
    var loadActivitiesCompletion = function(courseId) {
        Log.debug('courseindex_progress: Loading activities completion for course ' + courseId);

        var promises = Ajax.call([{
            methodname: 'theme_compecer_get_activities_completion',
            args: {courseid: courseId}
        }]);

        promises[0]
            .then(function(response) {
                Log.debug('courseindex_progress: Activities completion loaded', response);
                if (response && response.activities) {
                    activitiesCompletionCache = response.activities;
                    updateActivitiesIcons(response.activities);
                }
                return;
            })
            .catch(function(error) {
                if (error && error.errorcode !== 'nopermissions') {
                    Log.error('courseindex_progress: Error loading activities completion: ' + error.message);
                }
            });
    };

    /**
     * Update activity status icons based on completion data
     *
     * @param {Array} activities Array of activity completion data
     */
    var updateActivitiesIcons = function(activities) {
        var updatedCount = 0;
        var notFoundCount = 0;

        activities.forEach(function(activity) {
            var $icon = $('.activity-status-icon[data-cm-id="' + activity.cmid + '"]');

            if ($icon.length === 0) {
                notFoundCount++;
                return;
            }

            // Remove all status classes
            $icon.removeClass('status-not-started status-in-progress status-completed');

            // Add appropriate class based on state
            // 0 = not started, 1 = in progress, 2 = completed
            var statusClass = '';
            var ariaLabel = '';

            if (activity.state === 2) {
                statusClass = 'status-completed';
                ariaLabel = 'Completed';
            } else if (activity.state === 1) {
                statusClass = 'status-in-progress';
                ariaLabel = 'In progress';
            } else {
                statusClass = 'status-not-started';
                ariaLabel = 'Not started';
            }

            $icon.addClass(statusClass);
            $icon.attr('aria-label', ariaLabel);
            updatedCount++;
        });

        Log.debug('courseindex_progress: Updated ' + updatedCount + ' activity icons, ' +
                 notFoundCount + ' not found in DOM');

        // If some icons were not found, try again after a delay
        if (notFoundCount > 0 && activitiesCompletionCache) {
            setTimeout(function() {
                updateActivitiesIcons(activitiesCompletionCache);
            }, 1000);
        }
    };

    /**
     * Load progress percentages for all sections
     *
     * @param {Number} courseId The course ID
     */
    var loadSectionsProgress = function(courseId) {
        $('.section-percentage').each(function() {
            var $badge = $(this);
            var sectionId = $badge.data('section-id');

            if (sectionId && !$badge.data('loaded')) {
                loadSectionPercentage(courseId, sectionId, $badge);
            }
        });
    };

    /**
     * Load progress percentage for a single section
     *
     * @param {Number} courseId The course ID
     * @param {Number} sectionId The section ID
     * @param {jQuery} $badge The percentage badge element
     */
    var loadSectionPercentage = function(courseId, sectionId, $badge) {
        var promises = Ajax.call([{
            methodname: 'theme_compecer_get_section_progress',
            args: {
                sectionid: sectionId,
                courseid: courseId
            }
        }]);

        promises[0]
            .then(function(response) {
                $badge.data('loaded', true);

                if (response && response.hasprogress && response.total > 0) {
                    var percentage = response.percentage || 0;
                    $badge.text(percentage + '%');
                    $badge.fadeIn(200);
                }
                return;
            })
            .catch(function(error) {
                $badge.data('loaded', true);
                if (error && error.errorcode !== 'nopermissions') {
                    Log.error('courseindex_progress: Error loading section progress: ' + error.message);
                }
            });
    };

    return {
        init: init
    };
});
