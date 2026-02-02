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
 * AJAX handling for Grade Items Report - Course detail page.
 *
 * @module     report_gradeitems/course
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /**
     * Module state.
     */
    var state = {
        courseid: 0,
        activityvisibility: '',
        strings: {}
    };

    /**
     * Load language strings.
     *
     * @return {Promise}
     */
    var loadStrings = function() {
        return Str.get_strings([
            {key: 'noactivitiesfound', component: 'report_gradeitems'},
            {key: 'totalactivities_count', component: 'report_gradeitems'},
            {key: 'yes', component: 'report_gradeitems'},
            {key: 'no', component: 'report_gradeitems'},
        ]).then(function(strings) {
            state.strings.noactivitiesfound = strings[0];
            state.strings.totalactivities_count = strings[1];
            state.strings.yes = strings[2];
            state.strings.no = strings[3];
            return true;
        });
    };

    /**
     * Fetch activities via AJAX.
     *
     * @return {Promise}
     */
    var fetchActivities = function() {
        showLoading();

        return Ajax.call([{
            methodname: 'report_gradeitems_get_activities',
            args: {
                courseid: state.courseid,
                activityvisibility: state.activityvisibility
            }
        }])[0].then(function(response) {
            renderTable(response);
            updateTotalCount(response.totalcount);
            hideLoading();
            return response;
        }).catch(function(error) {
            hideLoading();
            Notification.exception(error);
        });
    };

    /**
     * Show loading indicator.
     */
    var showLoading = function() {
        var tableContainer = $('#gradeitems-activities-container');
        tableContainer.css('opacity', '0.5');
        if (!$('#gradeitems-loading').length) {
            tableContainer.before('<div id="gradeitems-loading" class="text-center py-3">' +
                '<div class="spinner-border text-primary" role="status">' +
                '<span class="sr-only">Loading...</span></div></div>');
        }
        $('#gradeitems-loading').show();
    };

    /**
     * Hide loading indicator.
     */
    var hideLoading = function() {
        $('#gradeitems-activities-container').css('opacity', '1');
        $('#gradeitems-loading').hide();
    };

    /**
     * Update total count display.
     *
     * @param {Number} count
     */
    var updateTotalCount = function(count) {
        var text = state.strings.totalactivities_count.replace('{$a}', count);
        $('#gradeitems-activities-count').text(text);
    };

    /**
     * Render the activities table.
     *
     * @param {Object} response
     */
    var renderTable = function(response) {
        var container = $('#gradeitems-activities-container');
        var tableBody = container.find('tbody');

        // Clear existing rows.
        tableBody.empty();

        if (response.activities.length === 0) {
            // Show no results message.
            container.find('table').hide();
            if (!$('#gradeitems-no-results').length) {
                container.append('<div id="gradeitems-no-results" class="alert alert-info">' +
                    state.strings.noactivitiesfound + '</div>');
            }
            $('#gradeitems-no-results').show();
            return;
        }

        // Hide no results message if visible.
        $('#gradeitems-no-results').hide();
        container.find('table').show();

        // Build table rows.
        response.activities.forEach(function(activity) {
            var row = $('<tr></tr>');

            row.append($('<td></td>').text(activity.section));
            row.append($('<td></td>').html(
                '<a href="' + activity.activityurl + '" target="_blank">' + activity.activityname + '</a>'
            ));
            row.append($('<td></td>').text(activity.moduletype));
            row.append($('<td></td>').text(activity.activityvisible));
            row.append($('<td></td>').text(activity.gradetype));
            row.append($('<td></td>').text(activity.grademax));
            row.append($('<td></td>').text(activity.gradepass));
            row.append($('<td></td>').text(activity.gradeweight));
            row.append($('<td></td>').text(activity.gradecount));
            row.append($('<td></td>').text(activity.gradeaverage));

            tableBody.append(row);
        });
    };

    /**
     * Initialize event handlers.
     */
    var initEventHandlers = function() {
        // Activity visibility filter change - AJAX on change.
        $('#gradeitems-activityvisibility').on('change', function() {
            state.activityvisibility = $(this).val() || '';
            fetchActivities();
        });
    };

    /**
     * Initialize the module.
     *
     * @param {Object} config Initial configuration
     */
    var init = function(config) {
        state.courseid = config.courseid || 0;
        state.activityvisibility = config.activityvisibility || '';

        loadStrings().then(function() {
            initEventHandlers();
            // Initial data is already loaded by PHP, no need to fetch again.
            return true;
        }).catch(Notification.exception);
    };

    return {
        init: init
    };
});
