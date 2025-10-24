/**
 * JavaScript for the report_customcajasan report
 *
 * @module     block_report_customcajasan/report
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/notification', 'core/str'], function($, Notification, Str) {
    // State management for current page and filters
    var state = {
        currentPage: 0,
        perPage: 100,
        filter: {}
    };

    /**
     * Update hidden fields in the download form with current filter values
     */
    function updateDownloadForm() {
        $('#download_categoryid').val($('#categoryid').val());
        $('#download_courseid').val($('#courseid_filter').val());
        $('#download_idnumber').val($('#idnumber').val());
        $('#download_firstname').val($('#firstname').val());
        $('#download_lastname').val($('#lastname').val());
        $('#download_estado').val($('#estado').val());
        $('#download_startdate').val($('#startdate').val());
        $('#download_enddate').val($('#enddate').val());
    }

    /**
     * Load report data via AJAX
     */
    function loadReportData() {
        $('#report-results').addClass('loading');

        var formData = {
            'categoryid': $('#categoryid').val(),
            'courseid': $('#courseid_filter').val(),
            'idnumber': $('#idnumber').val(),
            'firstname': $('#firstname').val(),
            'lastname': $('#lastname').val(),
            'estado': $('#estado').val(),
            'startdate': $('#startdate').val(),
            'enddate': $('#enddate').val(),
            'page': state.currentPage,
            'perpage': state.perPage,
            'sesskey': M.cfg.sesskey,
            'blockinstanceid': $('input[name="blockinstanceid"]').val()
        };

        $.ajax({
            url: M.cfg.wwwroot + '/blocks/report_customcajasan/ajax/get_report_data.php',
            type: 'GET',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    $('#report-results').html(response.html);
                    state.totalCount = response.count;
                    initializeDynamicElements();
                    colorizeStatusCells();
                    updateDownloadForm();
                } else {
                    var errorMsg = (response && response.error) ? response.error : 
                        M.util.get_string('ajax_error', 'block_report_customcajasan');
                    
                    $('#report-results').html(
                        '<div class="alert alert-danger">' + errorMsg + '</div>'
                    );
                }
            },
            error: function(xhr, status) {
                Str.get_string('ajax_error_detail', 'block_report_customcajasan')
                    .then(function(errorString) {
                        $('#report-results').html(
                            '<div class="alert alert-danger">' + errorString + ': ' + status + '</div>'
                        );
                        return;
                    })
                    .catch(Notification.exception);
            },
            complete: function() {
                $('#report-results').removeClass('loading');
            }
        });
    }

    /**
     * Initialize dynamic elements like pagination
     */
    function initializeDynamicElements() {
        $(document).off('click', '.pagination .page-link').on('click', '.pagination .page-link', function(e) {
            e.preventDefault();
            var page = $(this).data('page');

            if (page !== undefined) {
                state.currentPage = page;
                loadReportData();
                
                $('html, body').animate({
                    scrollTop: $('#report-results').offset().top - 20
                }, 200);
            }
        });
    }

    /**
     * Apply colors to status cells based on status values
     */
    function colorizeStatusCells() {
        $('#enrollment-report-table tbody tr').each(function() {
            var statusCell = $(this).find('td:eq(10)');
            var statusText = statusCell.text().trim();

            statusCell.removeClass('bg-success bg-warning bg-info bg-secondary bg-danger text-white');

            if (statusText === 'APROBADO') {
                statusCell.addClass('bg-success text-white');
            } else if (statusText === 'EN CURSO') {
                statusCell.addClass('bg-warning');
            } else if (statusText === 'NO INICIADO') {
                statusCell.addClass('bg-danger text-white');
            } else if (statusText === 'SOLO CONSULTA') {
                statusCell.addClass('bg-secondary text-white');
            }
        });
    }

    /**
     * Initialize the module
     */
    function init() {
        // Handle category change
        $('#categoryid').on('change', function() {
            var categoryId = $(this).val();
            state.filter.category = categoryId;
            state.filter.course = '';
            state.currentPage = 0;

            $('#courseid_filter').empty();
            $('#courseid_filter').prop('disabled', true);

            Str.get_string('option_all', 'block_report_customcajasan')
                .then(function(allText) {
                    $('#courseid_filter').append($('<option>', {
                        value: '',
                        text: allText
                    }));
                })
                .catch(function() {
                    $('#courseid_filter').append($('<option>', {
                        value: '',
                        text: 'All'
                    }));
                });

            $('#courseid_filter').val('');

            if (categoryId) {
                $.ajax({
                    url: M.cfg.wwwroot + '/blocks/report_customcajasan/ajax/get_courses.php',
                    type: 'GET',
                    data: {
                        'categoryid': categoryId,
                        'sesskey': M.cfg.sesskey,
                        'blockinstanceid': $('input[name="blockinstanceid"]').val()
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.courses) {
                            $.each(data.courses, function(index, course) {
                                $('#courseid_filter').append($('<option>', {
                                    value: course.id,
                                    text: course.fullname
                                }));
                            });
                        }
                        $('#courseid_filter').prop('disabled', false);
                        updateDownloadForm();
                    },
                    error: function(xhr, status) {
                        Notification.exception({message: 'Error loading courses: ' + status});
                        $('#courseid_filter').prop('disabled', false);
                        updateDownloadForm();
                    }
                });
            } else {
                $('#courseid_filter').prop('disabled', false);
                updateDownloadForm();
            }
        });

        // Alphabet filter
        $('.alphabet-filter a').on('click', function(e) {
            e.preventDefault();
            var letter = $(this).data('letter');
            var target = $(this).data('target');

            state.filter[target] = letter;
            $('#' + target).val(letter);

            $(this).closest('.alphabet-filter').find('a').removeClass('active');
            $(this).addClass('active');

            state.currentPage = 0;
            loadReportData();
            updateDownloadForm();
        });

        // Form submission
        $('#report-form').on('submit', function(e) {
            e.preventDefault();
            state.currentPage = 0;
            loadReportData();
            updateDownloadForm();
        });

        // Selectbox changes
        $('#estado, #courseid_filter').on('change', function() {
            var id = $(this).attr('id');
            state.filter[id] = $(this).val();
            state.currentPage = 0;
            updateDownloadForm();
        });

        // Date changes
        $('#startdate, #enddate').on('change', function() {
            var id = $(this).attr('id');
            state.filter[id] = $(this).val();
            state.currentPage = 0;
            loadReportData();
            updateDownloadForm();
        });

        // ID number input with debounce
        var idnumberTimer;
        $('#idnumber').on('input', function() {
            clearTimeout(idnumberTimer);
            idnumberTimer = setTimeout(function() {
                state.filter.idnumber = $('#idnumber').val();
                state.currentPage = 0;
                loadReportData();
                updateDownloadForm();
            }, 500);
        });
        
        // Per page change
        $('#perpage').on('change', function() {
            var perpage = parseInt($(this).val());
            state.perPage = perpage;
            state.currentPage = 0;
            loadReportData();
        });

        // Download form validation
        $('#downloadForm').on('submit', function() {
            updateDownloadForm();
            
            var hasDownloadFilters = $('#download_categoryid').val() || 
                             $('#download_courseid').val() || 
                             $('#download_estado').val() ||
                             $('#download_idnumber').val() || 
                             $('#download_firstname').val() || 
                             $('#download_lastname').val() ||
                             $('#download_startdate').val() || 
                             $('#download_enddate').val();
            
            if (!hasDownloadFilters) {
                Notification.alert(
                    '',
                    M.util.get_string('filters_required', 'block_report_customcajasan')
                );
                return false;
            }
            
            return true;
        });

        // Initial load
        if ($('#report-results').length) {
            state.filter = {
                category: $('#categoryid').val(),
                course: $('#courseid_filter').val(),
                estado: $('#estado').val(),
                idnumber: $('#idnumber').val(),
                firstname: $('#firstname').val(),
                lastname: $('#lastname').val(),
                startdate: $('#startdate').val(),
                enddate: $('#enddate').val()
            };
            
            state.perPage = $('#perpage').val() ? parseInt($('#perpage').val()) : 100;

            var hasSelectboxFilter = $('#categoryid').val() || $('#courseid_filter').val() || $('#estado').val();
            var hasOtherFilter = $('#idnumber').val() || $('#firstname').val() || $('#lastname').val() ||
                                $('#startdate').val() || $('#enddate').val();

            if (hasOtherFilter) {
                loadReportData();
                updateDownloadForm();
            } else if (hasSelectboxFilter) {
                updateDownloadForm();
            }
        }

        initializeDynamicElements();
    }

    return {
        init: init
    };
});