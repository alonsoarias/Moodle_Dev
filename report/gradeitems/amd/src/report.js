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
 * AJAX handling for Grade Items Report.
 *
 * @module     report_gradeitems/report
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /**
     * Module state.
     */
    var state = {
        categoryid: 0,
        courseid: 0,
        visibility: '',
        page: 0,
        perpage: 25,
        strings: {}
    };

    /**
     * Load language strings.
     *
     * @return {Promise}
     */
    var loadStrings = function() {
        return Str.get_strings([
            {key: 'showing', component: 'report_gradeitems'},
            {key: 'norecordsfound', component: 'report_gradeitems'},
            {key: 'viewactivities', component: 'report_gradeitems'},
            {key: 'gotocourse', component: 'report_gradeitems'},
            {key: 'yes', component: 'report_gradeitems'},
            {key: 'no', component: 'report_gradeitems'},
            {key: 'totalrecords', component: 'report_gradeitems'},
        ]).then(function(strings) {
            state.strings.showing = strings[0];
            state.strings.norecordsfound = strings[1];
            state.strings.viewactivities = strings[2];
            state.strings.gotocourse = strings[3];
            state.strings.yes = strings[4];
            state.strings.no = strings[5];
            state.strings.totalrecords = strings[6];
            return true;
        });
    };

    /**
     * Fetch courses via AJAX.
     *
     * @return {Promise}
     */
    var fetchCourses = function() {
        showLoading();

        return Ajax.call([{
            methodname: 'report_gradeitems_get_courses',
            args: {
                categoryid: state.categoryid,
                courseid: state.courseid,
                visibility: state.visibility,
                page: state.page,
                perpage: state.perpage
            }
        }])[0].then(function(response) {
            renderTable(response);
            renderPagination(response);
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
        var tableContainer = $('#gradeitems-table-container');
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
        $('#gradeitems-table-container').css('opacity', '1');
        $('#gradeitems-loading').hide();
    };

    /**
     * Update total count display.
     *
     * @param {Number} count
     */
    var updateTotalCount = function(count) {
        var text = state.strings.totalrecords.replace('{$a}', count);
        $('#gradeitems-total-count').text(text);
    };

    /**
     * Render the courses table.
     *
     * @param {Object} response
     */
    var renderTable = function(response) {
        var container = $('#gradeitems-table-container');
        var tableBody = container.find('tbody');

        // Clear existing rows.
        tableBody.empty();

        if (response.courses.length === 0) {
            // Show no results message.
            container.find('table').hide();
            if (!$('#gradeitems-no-results').length) {
                container.append('<div id="gradeitems-no-results" class="alert alert-info">' +
                    state.strings.norecordsfound + '</div>');
            }
            $('#gradeitems-no-results').show();
            $('#gradeitems-showing').hide();
            return;
        }

        // Hide no results message if visible.
        $('#gradeitems-no-results').hide();
        container.find('table').show();

        // Build table rows.
        response.courses.forEach(function(course) {
            var row = $('<tr></tr>');

            row.append($('<td></td>').text(course.categoryname));
            row.append($('<td></td>').text(course.courseshortname));
            row.append($('<td></td>').html(
                '<a href="' + course.courseurl + '" target="_blank" title="' +
                state.strings.gotocourse + '">' + course.coursefullname + '</a>'
            ));
            row.append($('<td></td>').text(course.coursevisible));
            row.append($('<td></td>').text(course.enrolledstudents));
            row.append($('<td></td>').html('<small>' + course.teachers + '</small>'));
            row.append($('<td></td>').text(course.totalactivities));
            row.append($('<td></td>').html(course.gradeableactivities));
            row.append($('<td></td>').html(
                '<a href="' + course.detailurl + '" class="btn btn-sm btn-outline-primary">' +
                state.strings.viewactivities + '</a>'
            ));

            tableBody.append(row);
        });

        // Update showing text.
        if (response.totalcount > 0) {
            var showingText = state.strings.showing
                .replace('{$a->from}', response.from)
                .replace('{$a->to}', response.to)
                .replace('{$a->total}', response.totalcount);
            $('#gradeitems-showing').text(showingText).show();
        } else {
            $('#gradeitems-showing').hide();
        }
    };

    /**
     * Render pagination controls.
     *
     * @param {Object} response
     */
    var renderPagination = function(response) {
        var container = $('#gradeitems-pagination');
        container.empty();

        if (!response.haspages) {
            return;
        }

        var nav = $('<nav aria-label="Page navigation"></nav>');
        var ul = $('<ul class="pagination justify-content-center"></ul>');

        // Previous button.
        var prevDisabled = response.page === 0 ? ' disabled' : '';
        ul.append('<li class="page-item' + prevDisabled + '">' +
            '<a class="page-link" href="#" data-page="' + (response.page - 1) + '">&laquo;</a></li>');

        // Page numbers.
        var startPage = Math.max(0, response.page - 2);
        var endPage = Math.min(response.totalpages - 1, response.page + 2);

        if (startPage > 0) {
            ul.append('<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li>');
            if (startPage > 1) {
                ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
        }

        for (var i = startPage; i <= endPage; i++) {
            var active = i === response.page ? ' active' : '';
            ul.append('<li class="page-item' + active + '">' +
                '<a class="page-link" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>');
        }

        if (endPage < response.totalpages - 1) {
            if (endPage < response.totalpages - 2) {
                ul.append('<li class="page-item disabled"><span class="page-link">...</span></li>');
            }
            ul.append('<li class="page-item"><a class="page-link" href="#" data-page="' +
                (response.totalpages - 1) + '">' + response.totalpages + '</a></li>');
        }

        // Next button.
        var nextDisabled = response.page >= response.totalpages - 1 ? ' disabled' : '';
        ul.append('<li class="page-item' + nextDisabled + '">' +
            '<a class="page-link" href="#" data-page="' + (response.page + 1) + '">&raquo;</a></li>');

        nav.append(ul);
        container.append(nav);
    };

    /**
     * Initialize event handlers.
     */
    var initEventHandlers = function() {
        // Category filter change - AJAX on change.
        $('#id_category').on('change', function() {
            state.categoryid = parseInt($(this).val()) || 0;
            state.page = 0;
            fetchCourses();
        });

        // Course filter change - AJAX on change.
        $('#id_course').on('change', function() {
            state.courseid = parseInt($(this).val()) || 0;
            state.page = 0;
            fetchCourses();
        });

        // Visibility filter change - AJAX on change.
        $('#id_visibility').on('change', function() {
            state.visibility = $(this).val() || '';
            state.page = 0;
            fetchCourses();
        });

        // Page size selector.
        $('#gradeitems-perpage').on('change', function() {
            state.perpage = parseInt($(this).val()) || 0;
            state.page = 0;
            fetchCourses();
        });

        // Pagination clicks.
        $(document).on('click', '#gradeitems-pagination .page-link', function(e) {
            e.preventDefault();

            var $this = $(this);
            if ($this.parent().hasClass('disabled') || $this.parent().hasClass('active')) {
                return;
            }

            var newPage = parseInt($this.data('page'));
            if (!isNaN(newPage) && newPage >= 0) {
                state.page = newPage;
                fetchCourses();

                // Scroll to top of table.
                $('html, body').animate({
                    scrollTop: $('#gradeitems-table-container').offset().top - 100
                }, 300);
            }
        });
    };

    /**
     * Initialize the module.
     *
     * @param {Object} config Initial configuration
     */
    var init = function(config) {
        state.categoryid = config.categoryid || 0;
        state.courseid = config.courseid || 0;
        state.visibility = config.visibility || '';
        state.page = config.page || 0;
        state.perpage = config.perpage || 25;

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
