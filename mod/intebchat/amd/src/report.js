/**
 * AJAX Report handler for INTEB Chat
 *
 * @module     mod_intebchat/report
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates'], function($, Ajax, Notification, Str, Templates) {

    var Report = {
        config: null,
        strings: {},

        /**
         * Initialize the report module
         * @param {Object} config Configuration object
         */
        init: function(config) {
            this.config = config;
            this.loadStrings();
            this.bindEvents();

            // Update URL without reload on filter change
            window.history.replaceState({}, '', this.buildUrl());
        },

        /**
         * Load localized strings
         */
        loadStrings: function() {
            var self = this;
            Str.get_strings([
                {key: 'loading', component: 'core'},
                {key: 'nodata', component: 'mod_intebchat'},
                {key: 'viewusers', component: 'mod_intebchat'},
                {key: 'viewdetails', component: 'mod_intebchat'},
            ]).then(function(strings) {
                self.strings.loading = strings[0];
                self.strings.nodata = strings[1];
                self.strings.viewusers = strings[2];
                self.strings.viewdetails = strings[3];
            }).catch(Notification.exception);
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Period filter change
            $(document).on('change', '#report-period-select', function() {
                self.config.period = $(this).val();
                self.config.page = 0;
                self.loadData();
            });

            // Instance filter change (course report)
            $(document).on('change', '#report-instance-select', function() {
                self.config.instanceid = parseInt($(this).val()) || 0;
                self.config.page = 0;
                self.loadData();
            });

            // View tabs change
            $(document).on('click', '.report-tab-link', function(e) {
                e.preventDefault();
                var view = $(this).data('view');
                if (view && view !== self.config.view) {
                    self.config.view = view;
                    self.config.page = 0;
                    self.loadData();

                    // Update active tab
                    $('.report-tab-link').removeClass('active');
                    $(this).addClass('active');
                }
            });

            // Pagination
            $(document).on('click', '.report-pagination .page-link', function(e) {
                e.preventDefault();
                var page = $(this).data('page');
                if (typeof page !== 'undefined' && page !== self.config.page) {
                    self.config.page = parseInt(page);
                    self.loadData();
                }
            });

            // Course filter (site report)
            $(document).on('click', '.filter-course-link', function(e) {
                e.preventDefault();
                self.config.courseid = parseInt($(this).data('courseid')) || 0;
                self.config.view = 'users';
                self.config.page = 0;
                self.loadData();
            });

            // Clear filter
            $(document).on('click', '.clear-filter-btn', function(e) {
                e.preventDefault();
                self.config.courseid = 0;
                self.config.page = 0;
                self.loadData();
            });
        },

        /**
         * Build URL with current parameters
         * @return {string}
         */
        buildUrl: function() {
            var params = new URLSearchParams(window.location.search);
            params.set('period', this.config.period);
            if (this.config.view) {
                params.set('view', this.config.view);
            }
            if (this.config.courseid) {
                params.set('courseid', this.config.courseid);
            }
            if (this.config.instanceid) {
                params.set('instanceid', this.config.instanceid);
            }
            params.set('page', this.config.page);
            return window.location.pathname + '?' + params.toString();
        },

        /**
         * Load data via AJAX
         */
        loadData: function() {
            var self = this;

            // Show loading state
            $('#report-content').addClass('loading');
            $('#report-loading').show();

            // Update URL
            window.history.pushState({}, '', this.buildUrl());

            var methodName, args;

            if (this.config.type === 'site') {
                methodName = 'mod_intebchat_get_site_report';
                args = {
                    view: this.config.view || 'overview',
                    period: this.config.period || 'month',
                    courseid: this.config.courseid || 0,
                    userid: this.config.userid || 0,
                    page: this.config.page || 0,
                    perpage: this.config.perpage || 25
                };
            } else {
                methodName = 'mod_intebchat_get_course_report';
                args = {
                    courseid: this.config.courseid,
                    period: this.config.period || 'month',
                    instanceid: this.config.instanceid || 0,
                    page: this.config.page || 0,
                    perpage: this.config.perpage || 25
                };
            }

            Ajax.call([{
                methodname: methodName,
                args: args
            }])[0].then(function(data) {
                self.renderData(data);
            }).catch(function(error) {
                Notification.exception(error);
            }).always(function() {
                $('#report-content').removeClass('loading');
                $('#report-loading').hide();
            });
        },

        /**
         * Render the data
         * @param {Object} data
         */
        renderData: function(data) {
            var self = this;

            // Update summary cards
            this.updateSummaryCards(data.summary);

            // Update tables based on type
            if (this.config.type === 'site') {
                this.renderSiteReport(data);
            } else {
                this.renderCourseReport(data);
            }

            // Update pagination
            this.renderPagination(data.pagination);
        },

        /**
         * Update summary cards with animation
         * @param {Object} summary
         */
        updateSummaryCards: function(summary) {
            var self = this;

            $('.summary-card').each(function() {
                var $card = $(this);
                var key = $card.data('key');
                if (summary[key] !== undefined) {
                    var value = self.formatNumber(summary[key]);
                    $card.find('.card-value').fadeOut(150, function() {
                        $(this).text(value).fadeIn(150);
                    });
                }
            });
        },

        /**
         * Render site report tables
         * @param {Object} data
         */
        renderSiteReport: function(data) {
            var self = this;
            var view = this.config.view || 'overview';

            if (view === 'overview' || view === 'courses') {
                var $tbody = $('#courses-table tbody');
                $tbody.empty();

                if (data.courses.length === 0) {
                    $tbody.append('<tr><td colspan="7" class="text-center">' + this.strings.nodata + '</td></tr>');
                } else {
                    data.courses.forEach(function(course) {
                        var row = '<tr>' +
                            '<td><a href="' + M.cfg.wwwroot + '/course/view.php?id=' + course.courseid + '">' + course.coursename + '</a></td>' +
                            '<td>' + course.shortname + '</td>' +
                            '<td class="text-right">' + self.formatNumber(course.instances) + '</td>' +
                            '<td class="text-right">' + self.formatNumber(course.messages) + '</td>' +
                            '<td class="text-right">' + self.formatNumber(course.users) + '</td>' +
                            '<td class="text-right">' + self.formatNumber(course.tokens) + '</td>' +
                            '<td><a href="#" class="btn btn-sm btn-outline-primary filter-course-link" data-courseid="' + course.courseid + '">' + self.strings.viewusers + '</a></td>' +
                            '</tr>';
                        $tbody.append(row);
                    });
                }
            }

            if (view === 'users') {
                var $tbody = $('#users-table tbody');
                $tbody.empty();

                if (data.users.length === 0) {
                    $tbody.append('<tr><td colspan="5" class="text-center">' + this.strings.nodata + '</td></tr>');
                } else {
                    data.users.forEach(function(user) {
                        var row = '<tr>' +
                            '<td><a href="' + M.cfg.wwwroot + '/user/profile.php?id=' + user.userid + '">' + user.fullname + '</a></td>' +
                            '<td>' + user.email + '</td>' +
                            '<td class="text-right">' + self.formatNumber(user.messages) + '</td>' +
                            '<td class="text-right">' + self.formatNumber(user.courses) + '</td>' +
                            '<td class="text-right">' + self.formatNumber(user.tokens) + '</td>' +
                            '</tr>';
                        $tbody.append(row);
                    });
                }
            }

            // Show/hide tables based on view
            if (view === 'users') {
                $('#courses-section').hide();
                $('#users-section').show();
            } else if (view === 'courses') {
                $('#courses-section').show();
                $('#users-section').hide();
            } else {
                $('#courses-section').show();
                $('#users-section').hide();
            }
        },

        /**
         * Render course report tables
         * @param {Object} data
         */
        renderCourseReport: function(data) {
            var self = this;

            // Render instances table
            var $instancesTbody = $('#instances-table tbody');
            $instancesTbody.empty();

            if (data.instances.length === 0) {
                $instancesTbody.append('<tr><td colspan="6" class="text-center">' + this.strings.nodata + '</td></tr>');
            } else {
                data.instances.forEach(function(instance) {
                    var actionLink = '';
                    if (instance.cmid > 0) {
                        actionLink = '<a href="' + M.cfg.wwwroot + '/mod/intebchat/analytics.php?id=' + instance.cmid + '" class="btn btn-sm btn-outline-primary">' + self.strings.viewdetails + '</a>';
                    }
                    var row = '<tr>' +
                        '<td>' + instance.name + '</td>' +
                        '<td class="text-right">' + self.formatNumber(instance.messages) + '</td>' +
                        '<td class="text-right">' + self.formatNumber(instance.users) + '</td>' +
                        '<td class="text-right">' + self.formatNumber(instance.conversations) + '</td>' +
                        '<td class="text-right">' + self.formatNumber(instance.tokens) + '</td>' +
                        '<td>' + actionLink + '</td>' +
                        '</tr>';
                    $instancesTbody.append(row);
                });
            }

            // Render users table
            var $usersTbody = $('#users-table tbody');
            $usersTbody.empty();

            if (data.users.length === 0) {
                $usersTbody.append('<tr><td colspan="6" class="text-center">' + this.strings.nodata + '</td></tr>');
            } else {
                data.users.forEach(function(user) {
                    var row = '<tr>' +
                        '<td><a href="' + M.cfg.wwwroot + '/user/profile.php?id=' + user.userid + '">' + user.fullname + '</a></td>' +
                        '<td>' + user.email + '</td>' +
                        '<td class="text-right">' + self.formatNumber(user.messages) + '</td>' +
                        '<td class="text-right">' + self.formatNumber(user.conversations) + '</td>' +
                        '<td class="text-right">' + self.formatNumber(user.tokens) + '</td>' +
                        '<td class="text-right">' + user.lastactivity_formatted + '</td>' +
                        '</tr>';
                    $usersTbody.append(row);
                });
            }
        },

        /**
         * Render pagination
         * @param {Object} pagination
         */
        renderPagination: function(pagination) {
            var $container = $('.report-pagination');
            $container.empty();

            if (pagination.total <= pagination.perpage) {
                return;
            }

            var totalPages = Math.ceil(pagination.total / pagination.perpage);
            var currentPage = pagination.page;

            var html = '<nav><ul class="pagination justify-content-center">';

            // Previous button
            if (currentPage > 0) {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage - 1) + '">&laquo;</a></li>';
            } else {
                html += '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
            }

            // Page numbers
            var startPage = Math.max(0, currentPage - 2);
            var endPage = Math.min(totalPages - 1, currentPage + 2);

            if (startPage > 0) {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="0">1</a></li>';
                if (startPage > 1) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }

            for (var i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    html += '<li class="page-item active"><span class="page-link">' + (i + 1) + '</span></li>';
                } else {
                    html += '<li class="page-item"><a class="page-link" href="#" data-page="' + i + '">' + (i + 1) + '</a></li>';
                }
            }

            if (endPage < totalPages - 1) {
                if (endPage < totalPages - 2) {
                    html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (totalPages - 1) + '">' + totalPages + '</a></li>';
            }

            // Next button
            if (currentPage < totalPages - 1) {
                html += '<li class="page-item"><a class="page-link" href="#" data-page="' + (currentPage + 1) + '">&raquo;</a></li>';
            } else {
                html += '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
            }

            html += '</ul></nav>';
            $container.html(html);
        },

        /**
         * Format number with thousands separator
         * @param {number} num
         * @return {string}
         */
        formatNumber: function(num) {
            if (typeof num === 'undefined' || num === null) {
                return '0';
            }
            return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
    };

    return {
        init: function(config) {
            Report.init(config);
        }
    };
});
