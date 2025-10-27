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
 * Course index progress bar functionality with section and activity state support.
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log', 'core/notification'], function($, Ajax, Log, Notification) {
    'use strict';

    var courseid = null;
    var isUpdating = false;

    /**
     * Initialize the course progress bar.
     *
     * @param {number} cid The course ID
     */
    var init = function(cid) {
        if (!cid || cid <= 0) {
            Log.debug('Invalid course ID, skipping progress bar initialization');
            return;
        }

        courseid = cid;

        // Wait for courseindex to be fully loaded
        $(document).ready(function() {
            loadProgressData();
        });

        // Listen for completion events to reload progress
        registerEventListeners();
    };

    /**
     * Register event listeners.
     */
    var registerEventListeners = function() {
        // Moodle core completion event
        $(document).on('coursemodulecompletion:updated', function() {
            loadProgressData();
        });

        // Custom theme event for compatibility
        $(document).on('theme_compecer:activity_completed', function() {
            loadProgressData();
        });
    };

    /**
     * Load progress data via AJAX.
     */
    var loadProgressData = function() {
        if (isUpdating) {
            return; // Prevent concurrent updates
        }

        var container = $('#courseindex-progress');
        if (container.length === 0) {
            return;
        }

        isUpdating = true;

        // Call web service
        Ajax.call([{
            methodname: 'theme_compecer_get_course_progress',
            args: {courseid: courseid}
        }])[0]
        .done(function(response) {
            updateProgressUI(response);
        })
        .fail(function(error) {
            Log.error('Failed to load course progress: ' + error.message);
            Notification.exception(error);
            container.hide();
        })
        .always(function() {
            isUpdating = false;
        });
    };

    /**
     * Update the complete progress UI.
     *
     * @param {object} data Progress data from the server
     */
    var updateProgressUI = function(data) {
        // Update global course progress
        updateCourseProgress(data.courseprogress);

        // Update section progress badges (if sections data is available)
        if (data.sections && data.sections.length > 0) {
            updateSectionsProgress(data.sections);
            updateActivitiesState(data.sections);
        }

        // Show container with animation
        $('#courseindex-progress').fadeIn(400);
    };

    /**
     * Update global course progress bar and stats.
     *
     * @param {object} progress Course progress data
     */
    var updateCourseProgress = function(progress) {
        var container = $('#courseindex-progress');

        // Only show if completion is enabled
        if (!progress || !progress.hascompletion) {
            container.hide();
            return;
        }

        var percentage = Math.floor(progress.percentage || 0);

        // Update percentage display
        container.find('.progress-percentage').text(percentage + '%');

        // Update details text
        if (progress.progresstext) {
            container.find('.progress-details').text(progress.progresstext);
        } else if (progress.activitycount > 0) {
            var detailsText = progress.completedcount + ' de ' + progress.activitycount + ' actividades';
            container.find('.progress-details').text(detailsText);
        }

        // Update progress bar with animation
        var progressBar = container.find('.progress-bar');
        progressBar.css('width', percentage + '%');
        progressBar.attr('aria-valuenow', percentage);
        progressBar.attr('aria-label', progress.progresstext || (percentage + '%'));

        // Update color based on server response or percentage
        progressBar.removeClass('bg-danger bg-warning bg-info bg-success');
        if (progress.progresscolor) {
            progressBar.addClass(progress.progresscolor);
        } else {
            // Fallback color logic
            if (percentage < 30) {
                progressBar.addClass('bg-danger');
            } else if (percentage < 70) {
                progressBar.addClass('bg-warning');
            } else {
                progressBar.addClass('bg-success');
            }
        }

        // Update activity list
        var activityList = container.find('.progress-activity-list');
        activityList.empty();
        if (progress.showactivitylist && progress.activitylist && progress.activitylist.length > 0) {
            progress.activitylist.forEach(function(activity) {
                activityList.append($('<li>').text(activity));
            });
        }
    };

    /**
     * Update section progress badges.
     *
     * @param {array} sections Array of section data
     */
    var updateSectionsProgress = function(sections) {
        sections.forEach(function(section) {
            if (!section.progressinfo) {
                return;
            }

            // Find section by data-number attribute
            var sectionElement = $('[data-for="section"][data-number="' + section.number + '"]');
            if (sectionElement.length === 0) {
                return;
            }

            // Find or create progress badge
            var badge = sectionElement.find('.section-progress-badge');
            if (badge.length === 0) {
                // Badge doesn't exist yet, it will be created by template
                return;
            }

            // Update badge content
            badge.text(section.progressinfo.percentage + '%');
            badge.attr('data-percentage', section.progressinfo.percentage);
            badge.attr('aria-label', section.progressinfo.percentage + '% completado');

            // Add animation class
            badge.addClass('progress-updated');
            setTimeout(function() {
                badge.removeClass('progress-updated');
            }, 600);
        });
    };

    /**
     * Update activity completion state icons.
     *
     * @param {array} sections Array of section data with activities
     */
    var updateActivitiesState = function(sections) {
        sections.forEach(function(section) {
            if (!section.cms || section.cms.length === 0) {
                return;
            }

            section.cms.forEach(function(cm) {
                // Find activity element by data-id
                var activityElement = $('[data-for="cm"][data-id="' + cm.id + '"]');
                if (activityElement.length === 0) {
                    return;
                }

                // Update completion state class
                activityElement.removeClass('completion-notstarted completion-inprogress completion-completed');
                if (cm.completionstate) {
                    activityElement.addClass('completion-' + cm.completionstate);
                }

                // Update completion icon
                var icon = activityElement.find('.activity-completion-icon');
                if (icon.length > 0 && cm.completionicon) {
                    icon.text(cm.completionicon);
                    icon.removeClass('text-muted text-warning text-success text-danger text-info');
                    if (cm.completioncolor) {
                        icon.addClass(cm.completioncolor);
                    }
                    if (cm.completionlabel) {
                        icon.attr('aria-label', cm.completionlabel);
                        icon.attr('title', cm.completionlabel);
                    }
                }
            });
        });
    };

    return {
        init: init,
        // Export for testing/debugging
        loadProgressData: loadProgressData
    };
});
