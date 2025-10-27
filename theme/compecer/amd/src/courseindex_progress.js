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
 * Course index progress bar functionality.
 *
 * @module     theme_compecer/courseindex_progress
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/log'], function($, Ajax, Log) {
    'use strict';

    /**
     * Initialize the course progress bar.
     *
     * @param {number} courseid The course ID
     */
    var init = function(courseid) {
        if (!courseid || courseid <= 0) {
            Log.debug('Invalid course ID, skipping progress bar initialization');
            return;
        }

        // Wait for courseindex to be fully loaded
        $(document).ready(function() {
            loadProgressData(courseid);
        });

        // Listen for completion events to reload progress
        $(document).on('coursemodulecompletion:updated', function() {
            loadProgressData(courseid);
        });
    };

    /**
     * Load progress data via AJAX.
     *
     * @param {number} courseid The course ID
     */
    var loadProgressData = function(courseid) {
        var container = $('#courseindex-progress');
        if (container.length === 0) {
            return;
        }

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
            // Hide the progress container if there's an error
            container.hide();
        });
    };

    /**
     * Update the progress bar UI.
     *
     * @param {object} data Progress data from the server
     */
    var updateProgressUI = function(data) {
        var container = $('#courseindex-progress');

        // Only show if completion is enabled
        if (!data.hascompletion) {
            container.hide();
            return;
        }

        var percentage = Math.floor(data.percentage || 0);

        // Update percentage display
        container.find('.progress-percentage').text(percentage + '%');

        // Update progress bar
        var progressBar = container.find('.progress-bar');
        progressBar.css('width', percentage + '%');
        progressBar.attr('aria-valuenow', percentage);

        // Update color based on percentage
        progressBar.removeClass('bg-danger bg-warning bg-info bg-success');
        if (percentage < 25) {
            progressBar.addClass('bg-danger');
        } else if (percentage < 50) {
            progressBar.addClass('bg-warning');
        } else if (percentage < 75) {
            progressBar.addClass('bg-info');
        } else {
            progressBar.addClass('bg-success');
        }

        // Update details text
        if (data.activitycount > 0) {
            var detailsText = data.completedcount + ' / ' + data.activitycount + ' activities';
            container.find('.progress-details').text(detailsText);
        }

        // Update activity list
        var activityList = container.find('.progress-activity-list');
        activityList.empty();
        if (data.activitylist && data.activitylist.length > 0) {
            data.activitylist.forEach(function(activity) {
                activityList.append($('<li>').text(activity));
            });
        }

        // Show the container with animation
        container.fadeIn('slow');
    };

    return {
        init: init
    };
});
