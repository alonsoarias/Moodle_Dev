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
 * Course index progress module.
 * Handles loading and updating course progress with section and activity details.
 *
 * @module     theme_compecer/courseindex_progress
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log', 'core/notification'], function($, Ajax, Log, Notification) {
    'use strict';

    /**
     * Initialize the course index progress module.
     *
     * @param {Number} courseid The course ID
     */
    var init = function(courseid) {
        if (!courseid) {
            Log.debug('Course ID not provided to courseindex_progress module');
            return;
        }

        Log.debug('Initializing courseindex_progress for course ' + courseid);

        // Load initial progress data.
        loadProgressData(courseid);

        // Listen for completion events to update progress.
        $(document).on('coursemodulecompletion:updated', function() {
            Log.debug('Completion updated, reloading progress data');
            loadProgressData(courseid);
        });

        // Listen for section collapse/expand to lazy load progress if needed.
        $(document).on('shown.bs.collapse', '.course-index-content', function() {
            var sectionElement = $(this).closest('.course-index-section');
            var sectionId = sectionElement.data('id');
            if (sectionId) {
                updateSectionProgress(courseid, sectionId);
            }
        });
    };

    /**
     * Load progress data from the server.
     *
     * @param {Number} courseid The course ID
     */
    var loadProgressData = function(courseid) {
        var container = $('#courseindex-progress-container');

        Ajax.call([{
            methodname: 'theme_compecer_get_course_progress',
            args: {courseid: courseid}
        }])[0]
        .done(function(response) {
            Log.debug('Progress data loaded:', response);
            updateProgressUI(response);
        })
        .fail(function(error) {
            Log.error('Failed to load course progress:', error);
            container.hide();
            // Show error notification only in development mode.
            if (M.cfg.developmentmode) {
                Notification.addNotification({
                    message: 'Failed to load course progress: ' + error.message,
                    type: 'error'
                });
            }
        });
    };

    /**
     * Update the UI with progress data.
     *
     * @param {Object} data Progress data from server
     */
    var updateProgressUI = function(data) {
        var container = $('#courseindex-progress-container');

        if (!data.hascompletion) {
            container.hide();
            return;
        }

        var percentage = Math.floor(data.percentage);

        // Update percentage display.
        container.find('[data-region="progress-percentage"]').text(percentage + '%');

        // Update completed/total counts.
        container.find('[data-region="completed-count"]').text(data.completedcount);
        container.find('[data-region="activity-count"]').text(data.activitycount);

        // Update progress bar.
        var progressBar = container.find('[data-region="progress-bar"]');
        progressBar.css('width', percentage + '%');
        progressBar.attr('aria-valuenow', percentage);

        // Update progress bar color.
        progressBar.removeClass('bg-danger bg-warning bg-info bg-success');
        progressBar.addClass(data.progresscolor);

        // Add pulse animation to percentage when updated.
        var percentageEl = container.find('[data-region="progress-percentage"]');
        percentageEl.addClass('updated');
        setTimeout(function() {
            percentageEl.removeClass('updated');
        }, 500);

        // Show container with fade in.
        container.fadeIn(300);

        // Update section progress.
        if (data.sections && data.sections.length > 0) {
            updateSectionsProgress(data.sections);
        }

        // Update activity states.
        updateActivitiesState(data.sections);
    };

    /**
     * Update progress for all sections.
     *
     * @param {Array} sections Array of section data
     */
    var updateSectionsProgress = function(sections) {
        sections.forEach(function(section) {
            var badge = $('[data-region="section-progress-' + section.id + '"]');
            if (badge.length > 0) {
                var progressInfo = section.progressinfo;
                if (progressInfo && progressInfo.total > 0) {
                    badge.find('[data-region="section-percentage"]').text(progressInfo.percentage + '%');
                    badge.fadeIn(200);
                } else {
                    badge.hide();
                }
            }
        });
    };

    /**
     * Update activity completion states.
     *
     * @param {Array} sections Array of section data with activities
     */
    var updateActivitiesState = function(sections) {
        if (!sections || sections.length === 0) {
            return;
        }

        sections.forEach(function(section) {
            if (!section.activities || section.activities.length === 0) {
                return;
            }

            section.activities.forEach(function(activity) {
                var indicator = $('[data-region="activity-status-' + activity.id + '"]');
                if (indicator.length > 0) {
                    var currentState = indicator.attr('data-state');
                    if (currentState !== activity.state) {
                        // Update state.
                        indicator.attr('data-state', activity.state);

                        // Update parent classes.
                        var activityItem = indicator.closest('.activity-item');
                        activityItem.removeClass('activity-status-notstarted activity-status-inprogress ' +
                                                'activity-status-completed activity-status-notracking');
                        activityItem.addClass('activity-status-' + activity.state);

                        // Update screen reader text.
                        var srText = getStateText(activity.state);
                        indicator.find('.sr-only').text(srText);

                        // Add transition effect.
                        indicator.addClass('state-changed');
                        setTimeout(function() {
                            indicator.removeClass('state-changed');
                        }, 300);
                    }
                }
            });
        });
    };

    /**
     * Update progress for a specific section.
     *
     * @param {Number} courseid The course ID
     * @param {Number} sectionid The section ID
     */
    var updateSectionProgress = function(courseid, sectionid) {
        // This could be expanded to load section-specific progress if needed.
        Log.debug('Section ' + sectionid + ' expanded, progress already loaded');
    };

    /**
     * Get human-readable state text for screen readers.
     *
     * @param {String} state The activity state
     * @return {String} Human-readable state text
     */
    var getStateText = function(state) {
        var stateTexts = {
            'notstarted': M.util.get_string('notstarted', 'core'),
            'inprogress': M.util.get_string('inprogress', 'core'),
            'completed': M.util.get_string('completed', 'core'),
            'notracking': M.util.get_string('nottracked', 'core')
        };

        return stateTexts[state] || state;
    };

    return {
        init: init
    };
});
