<?php
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
 * Report display page for report_educam1.
 *
 * @package    block_report_educam1
 * @copyright  2025 IngeWeb - Soluciones para triunfar en Internet
 * @author     Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/report_educam1/lib.php');
require_once($CFG->libdir . '/blocklib.php');
require_once($CFG->libdir . '/tablelib.php');

$instanceid = required_param('instanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$view = optional_param('view', 'individual', PARAM_ALPHA); // 'individual' or 'matrix'
$download = optional_param('download', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_ALPHA);

// Filters
$filteridnumber = optional_param('filteridnumber', '', PARAM_TEXT);
$filterfirstname = optional_param('filterfirstname', '', PARAM_TEXT);
$filterlastname = optional_param('filterlastname', '', PARAM_TEXT);
$filterstatus = optional_param('filterstatus', '', PARAM_TEXT);
$filterstartdate = optional_param('filterstartdate', '', PARAM_TEXT);
$filterenddate = optional_param('filterenddate', '', PARAM_TEXT);

// Pagination
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

// Get course and context
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check permissions - restrict to users with editing permissions only
require_capability('moodle/course:update', $context);

// Load block instance
$blockinstance = $DB->get_record('block_instances', ['id' => $instanceid], '*', MUST_EXIST);
if ($blockinstance->blockname !== 'report_educam1') {
    throw new moodle_exception('invalidblockinstance', 'block_report_educam1');
}

$block = block_instance('report_educam1', $blockinstance);
if (!$block) {
    throw new moodle_exception('invalidblockinstance', 'block_report_educam1');
}

// Get block configuration
$config = !empty($block->config) ? $block->config : new stdClass();
$activitytype = !empty($config->activitytype) ? $config->activitytype : '';

if (empty($activitytype)) {
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]),
        get_string('block_no_activity_configured', 'block_report_educam1'),
        null,
        \core\output\notification::NOTIFY_ERROR);
}

// Build URL params
$urlparams = [
    'instanceid' => $instanceid,
    'courseid' => $courseid,
    'view' => $view
];

if (!empty($filteridnumber)) {
    $urlparams['filteridnumber'] = $filteridnumber;
}
if (!empty($filterfirstname)) {
    $urlparams['filterfirstname'] = $filterfirstname;
}
if (!empty($filterlastname)) {
    $urlparams['filterlastname'] = $filterlastname;
}
if (!empty($filterstatus)) {
    $urlparams['filterstatus'] = $filterstatus;
}
if (!empty($filterstartdate)) {
    $urlparams['filterstartdate'] = $filterstartdate;
}
if (!empty($filterenddate)) {
    $urlparams['filterenddate'] = $filterenddate;
}

// Set up page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/report_educam1/report.php', $urlparams));
$PAGE->set_pagelayout('report');

$activitytypename = report_educam1_get_activity_type_name($activitytype);
$page_title = get_string('report_title', 'block_report_educam1');

$PAGE->set_title($page_title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($page_title);
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('block_report_educam1/report', 'init');

// Handle download
if ($download) {
    $filters = [
        'idnumber' => $filteridnumber,
        'firstname' => $filterfirstname,
        'lastname' => $filterlastname,
        'status' => $filterstatus,
        'startdate' => !empty($filterstartdate) ? strtotime($filterstartdate) : '',
        'enddate' => !empty($filterenddate) ? strtotime($filterenddate . ' 23:59:59') : ''
    ];

    if ($view === 'individual') {
        $data = report_educam1_get_individual_view_data($courseid, $activitytype, null, $filters);
        $filename = get_string('export_filename_individual', 'block_report_educam1') . '_' . date('Y-m-d');

        if ($format === 'csv') {
            report_educam1_export_individual_csv($data, $filename);
        } else {
            report_educam1_export_individual_spreadsheet($data, $filename, $format, $page_title);
        }
    } else {
        $data = report_educam1_get_matrix_view_data($courseid, $activitytype, $filters);
        $filename = get_string('export_filename_matrix', 'block_report_educam1') . '_' . date('Y-m-d');

        if ($format === 'csv') {
            report_educam1_export_matrix_csv($data, $filename);
        } else {
            report_educam1_export_matrix_spreadsheet($data, $filename, $format, $page_title);
        }
    }
    die();
}

// Output starts here
echo $OUTPUT->header();
echo $OUTPUT->heading($page_title);

// Course and activity type info
echo html_writer::start_div('alert alert-primary mb-4 shadow-sm');
echo html_writer::start_div('d-flex align-items-center');
echo html_writer::tag('i', '', ['class' => 'fa fa-info-circle fa-2x mr-3']);
echo html_writer::start_div('');
echo html_writer::tag('h5', get_string('report_viewing_course', 'block_report_educam1'), ['class' => 'mb-1']);
echo html_writer::tag('p', format_string($course->fullname), ['class' => 'mb-1 font-weight-bold']);
echo html_writer::tag('small', get_string('report_activity_type', 'block_report_educam1') . ': ' . $activitytypename, ['class' => 'text-muted']);
echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// View switcher
echo html_writer::start_div('mb-4');
echo html_writer::start_div('btn-group btn-group-lg shadow-sm', ['role' => 'group', 'aria-label' => 'View switcher']);

$individualurlparams = ['instanceid' => $instanceid, 'courseid' => $courseid, 'view' => 'individual'];
$individualurl = new moodle_url('/blocks/report_educam1/report.php', $individualurlparams);
$individualclass = $view === 'individual' ? 'btn btn-primary' : 'btn btn-outline-primary';
echo html_writer::link($individualurl, '<i class="fa fa-list mr-1"></i>' . get_string('view_individual', 'block_report_educam1'), ['class' => $individualclass]);

$matrixurlparams = ['instanceid' => $instanceid, 'courseid' => $courseid, 'view' => 'matrix'];
$matrixurl = new moodle_url('/blocks/report_educam1/report.php', $matrixurlparams);
$matrixclass = $view === 'matrix' ? 'btn btn-primary' : 'btn btn-outline-primary';
echo html_writer::link($matrixurl, '<i class="fa fa-th mr-1"></i>' . get_string('view_matrix', 'block_report_educam1'), ['class' => $matrixclass]);

echo html_writer::end_div();
echo html_writer::end_div();

// Filters section
echo html_writer::start_div('card mb-4 shadow-sm');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', '<i class="fa fa-filter mr-2"></i>' . get_string('filter_header', 'block_report_educam1'), ['class' => 'card-title mb-4']);

echo html_writer::start_tag('form', ['method' => 'get', 'id' => 'filter-form', 'class' => 'filter-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);

// Filter section 1: Basic filters
echo html_writer::tag('h6', get_string('filter_section_basic', 'block_report_educam1'), ['class' => 'text-muted mb-3']);
echo html_writer::start_div('row');

// ID Number filter
echo html_writer::start_div('col-lg-3 col-md-6 mb-3');
echo html_writer::tag('label', get_string('filter_idnumber', 'block_report_educam1'), [
    'for' => 'filteridnumber',
    'class' => 'font-weight-bold'
]);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'id' => 'filteridnumber',
    'name' => 'filteridnumber',
    'value' => $filteridnumber,
    'class' => 'form-control form-control-lg',
    'placeholder' => get_string('filter_idnumber_placeholder', 'block_report_educam1')
]);
echo html_writer::end_div();

// Status filter
echo html_writer::start_div('col-lg-3 col-md-6 mb-3');
echo html_writer::tag('label', get_string('filter_status', 'block_report_educam1'), [
    'for' => 'filterstatus',
    'class' => 'font-weight-bold'
]);
$statusoptions = [
    '' => get_string('filter_all', 'block_report_educam1'),
    'completed' => get_string('status_completed', 'block_report_educam1'),
    'not_completed' => get_string('status_not_completed', 'block_report_educam1')
];
echo html_writer::select($statusoptions, 'filterstatus', $filterstatus, false, [
    'class' => 'form-control form-control-lg custom-select',
    'id' => 'filterstatus'
]);
echo html_writer::end_div();

// Start date filter
echo html_writer::start_div('col-lg-3 col-md-6 mb-3');
echo html_writer::tag('label', get_string('filter_startdate', 'block_report_educam1'), [
    'for' => 'filterstartdate',
    'class' => 'font-weight-bold'
]);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'id' => 'filterstartdate',
    'name' => 'filterstartdate',
    'value' => $filterstartdate,
    'class' => 'form-control form-control-lg'
]);
echo html_writer::tag('small', get_string('filter_startdate_help', 'block_report_educam1'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

// End date filter
echo html_writer::start_div('col-lg-3 col-md-6 mb-3');
echo html_writer::tag('label', get_string('filter_enddate', 'block_report_educam1'), [
    'for' => 'filterenddate',
    'class' => 'font-weight-bold'
]);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'id' => 'filterenddate',
    'name' => 'filterenddate',
    'value' => $filterenddate,
    'class' => 'form-control form-control-lg'
]);
echo html_writer::tag('small', get_string('filter_enddate_help', 'block_report_educam1'), ['class' => 'form-text text-muted']);
echo html_writer::end_div();

echo html_writer::end_div();

// Filter section 2: Name filters
echo html_writer::tag('h6', get_string('filter_section_names', 'block_report_educam1'), ['class' => 'text-muted mb-3 mt-4']);

// First name with alphabet filter
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-12 mb-3');
echo html_writer::tag('label', get_string('filter_firstname', 'block_report_educam1'), [
    'for' => 'filterfirstname',
    'class' => 'font-weight-bold'
]);
echo html_writer::start_div('alphabet-filter mt-2 mb-2 p-2 bg-light rounded');
echo html_writer::tag('span', get_string('filter_by_letter', 'block_report_educam1') . ': ', ['class' => 'mr-2 small font-weight-bold']);
echo html_writer::link('#', get_string('filter_all', 'block_report_educam1'),
    ['class' => 'btn btn-sm btn-outline-primary' . (empty($filterfirstname) ? ' active' : ''),
     'data-letter' => '',
     'data-target' => 'filterfirstname']);
foreach (range('A', 'Z') as $letter) {
    $active = $filterfirstname === $letter ? ' active' : '';
    echo html_writer::link('#', $letter,
        ['class' => 'btn btn-sm btn-outline-primary' . $active,
         'data-letter' => $letter,
         'data-target' => 'filterfirstname']);
}
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'filterfirstname', 'name' => 'filterfirstname', 'value' => $filterfirstname]);
echo html_writer::end_div();
echo html_writer::end_div();

// Last name with alphabet filter
echo html_writer::start_div('row');
echo html_writer::start_div('col-md-12 mb-3');
echo html_writer::tag('label', get_string('filter_lastname', 'block_report_educam1'), [
    'for' => 'filterlastname',
    'class' => 'font-weight-bold'
]);
echo html_writer::start_div('alphabet-filter mt-2 mb-2 p-2 bg-light rounded');
echo html_writer::tag('span', get_string('filter_by_letter', 'block_report_educam1') . ': ', ['class' => 'mr-2 small font-weight-bold']);
echo html_writer::link('#', get_string('filter_all', 'block_report_educam1'),
    ['class' => 'btn btn-sm btn-outline-primary' . (empty($filterlastname) ? ' active' : ''),
     'data-letter' => '',
     'data-target' => 'filterlastname']);
foreach (range('A', 'Z') as $letter) {
    $active = $filterlastname === $letter ? ' active' : '';
    echo html_writer::link('#', $letter,
        ['class' => 'btn btn-sm btn-outline-primary' . $active,
         'data-letter' => $letter,
         'data-target' => 'filterlastname']);
}
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'filterlastname', 'name' => 'filterlastname', 'value' => $filterlastname]);
echo html_writer::end_div();
echo html_writer::end_div();

// Action buttons
echo html_writer::start_div('row mt-4 pt-3 border-top');
echo html_writer::start_div('col-md-12 text-right');
$clearurl = new moodle_url('/blocks/report_educam1/report.php', [
    'instanceid' => $instanceid,
    'courseid' => $courseid,
    'view' => $view
]);
echo html_writer::link($clearurl, '<i class="fa fa-times mr-1"></i>' . get_string('filter_clear', 'block_report_educam1'), [
    'class' => 'btn btn-outline-secondary btn-lg mr-2'
]);
echo html_writer::tag('button', '<i class="fa fa-search mr-1"></i>' . get_string('filter_apply', 'block_report_educam1'), [
    'type' => 'submit',
    'class' => 'btn btn-primary btn-lg'
]);
echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

// Build filters array
$filters = [
    'idnumber' => $filteridnumber,
    'firstname' => $filterfirstname,
    'lastname' => $filterlastname,
    'status' => $filterstatus,
    'startdate' => !empty($filterstartdate) ? strtotime($filterstartdate) : '',
    'enddate' => !empty($filterenddate) ? strtotime($filterenddate . ' 23:59:59') : ''
];

// Display appropriate view
if ($view === 'individual') {
    report_educam1_display_individual_view($courseid, $activitytype, $page, $perpage, $filters, $urlparams);
} else {
    report_educam1_display_matrix_view($courseid, $activitytype, $page, $perpage, $filters, $urlparams);
}

// Export options
$exporturlparams = array_merge($urlparams, ['download' => '1']);

echo html_writer::start_div('card mt-4 shadow-sm');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', '<i class="fa fa-download mr-2"></i>' . get_string('export_header', 'block_report_educam1'), ['class' => 'card-title']);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
foreach ($exporturlparams as $key => $value) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
}

echo html_writer::tag('label', get_string('export_format', 'block_report_educam1') . ':', ['class' => 'mr-2']);

$formatoptions = [
    'excel' => get_string('export_excel', 'block_report_educam1'),
    'ods' => get_string('export_ods', 'block_report_educam1'),
    'csv' => get_string('export_csv', 'block_report_educam1')
];
echo html_writer::select($formatoptions, 'format', $format, false, ['class' => 'form-control mr-2']);

echo html_writer::tag('button', '<i class="fa fa-file-export mr-1"></i>' . get_string('export_button', 'block_report_educam1'), [
    'type' => 'submit',
    'class' => 'btn btn-success btn-lg'
]);

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Display individual view with pagination and filters.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type
 * @param int $page Current page
 * @param int $perpage Items per page
 * @param array $filters Filters
 * @param array $urlparams URL parameters
 */
function report_educam1_display_individual_view($courseid, $activitytype, $page, $perpage, $filters, $urlparams) {
    global $OUTPUT;

    $alldata = report_educam1_get_individual_view_data($courseid, $activitytype, null, $filters);

    if (empty($alldata)) {
        echo html_writer::div(
            get_string('no_data', 'block_report_educam1'),
            'alert alert-warning'
        );
        return;
    }

    // Calculate statistics
    $totalrows = count($alldata);
    $completedrows = array_filter($alldata, function($item) {
        return $item->completed;
    });
    $completedcount = count($completedrows);
    $completionrate = $totalrows > 0 ? round(($completedcount / $totalrows) * 100, 2) : 0;

    // Display statistics
    echo html_writer::start_div('card mb-4 shadow-sm');
    echo html_writer::start_div('card-body');
    echo html_writer::start_div('row text-center');
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('div', '<i class="fa fa-users fa-2x text-primary mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_total_students', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', count(array_unique(array_column($alldata, 'userid'))), ['class' => 'text-primary font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('div', '<i class="fa fa-check-circle fa-2x text-success mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_completed', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $completedcount . ' / ' . $totalrows, ['class' => 'text-success font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-4');
    echo html_writer::tag('div', '<i class="fa fa-chart-pie fa-2x ' . ($completionrate >= 70 ? 'text-success' : 'text-warning') . ' mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_completion_rate', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $completionrate . '%', ['class' => ($completionrate >= 70 ? 'text-success' : 'text-warning') . ' font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Pagination
    $start = $page * $perpage;
    $data = array_slice($alldata, $start, $perpage);

    // Per page selector
    echo html_writer::start_div('mb-3 d-flex justify-content-between align-items-center');
    echo html_writer::tag('span', get_string('showing_entries', 'block_report_educam1', [
        'start' => $start + 1,
        'end' => min($start + $perpage, $totalrows),
        'total' => $totalrows
    ]));

    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
    foreach ($urlparams as $key => $value) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
    }
    echo html_writer::tag('label', get_string('per_page', 'block_report_educam1') . ':', ['class' => 'mr-2']);
    $perpageoptions = [10 => '10', 25 => '25', 50 => '50', 100 => '100'];
    echo html_writer::select($perpageoptions, 'perpage', $perpage, false, [
        'class' => 'form-control',
        'onchange' => 'this.form.submit()'
    ]);
    echo html_writer::end_tag('form');
    echo html_writer::end_div();

    // Display table with better styling
    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-bordered table-hover', 'id' => 'report-table']);
    echo html_writer::start_tag('thead', ['class' => 'thead-dark']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('column_idnumber', 'block_report_educam1'), ['style' => 'width: 120px;']);
    echo html_writer::tag('th', get_string('column_firstname', 'block_report_educam1'), ['style' => 'width: 150px;']);
    echo html_writer::tag('th', get_string('column_lastname', 'block_report_educam1'), ['style' => 'width: 150px;']);
    echo html_writer::tag('th', get_string('column_email', 'block_report_educam1'), ['style' => 'width: 200px;']);
    echo html_writer::tag('th', get_string('column_activity', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_completion_date', 'block_report_educam1'), ['style' => 'width: 180px;']);
    echo html_writer::tag('th', get_string('column_status', 'block_report_educam1'), ['class' => 'text-center', 'style' => 'width: 140px;']);
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($data as $item) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($item->idnumber));
        echo html_writer::tag('td', s($item->firstname));
        echo html_writer::tag('td', s($item->lastname));
        echo html_writer::tag('td', s($item->email));
        echo html_writer::tag('td', s($item->activityname));

        $completiondate = $item->completiondate ? userdate($item->completiondate, '%Y-%m-%d %H:%M') : '-';
        echo html_writer::tag('td', $completiondate, ['class' => 'text-center']);

        $statusclass = $item->completed ? 'badge-success' : 'badge-danger';
        $statussymbol = $item->completed ?
            get_string('completed_symbol', 'block_report_educam1') :
            get_string('not_completed_symbol', 'block_report_educam1');
        $statustext = $item->completed ?
            get_string('status_completed', 'block_report_educam1') :
            get_string('status_not_completed', 'block_report_educam1');

        echo html_writer::tag('td',
            html_writer::span($statussymbol . ' ' . $statustext, 'badge ' . $statusclass . ' p-2'),
            ['class' => 'text-center']
        );

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    // Pagination controls
    if ($totalrows > $perpage) {
        $totalpages = ceil($totalrows / $perpage);
        report_educam1_display_pagination($page, $totalpages, $urlparams);
    }
}

/**
 * Display matrix view with filters and pagination.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type
 * @param int $page Current page
 * @param int $perpage Items per page
 * @param array $filters Filters
 * @param array $urlparams URL parameters
 */
function report_educam1_display_matrix_view($courseid, $activitytype, $page, $perpage, $filters, $urlparams) {
    global $OUTPUT;

    $matrixdata = report_educam1_get_matrix_view_data($courseid, $activitytype, $filters);
    $allstudents = $matrixdata['students'];
    $activities = $matrixdata['activities'];
    $completions = $matrixdata['completions'];

    if (empty($allstudents) || empty($activities)) {
        echo html_writer::div(
            get_string('no_data', 'block_report_educam1'),
            'alert alert-warning'
        );
        return;
    }

    // Calculate statistics (using all students)
    $totalstudents = count($allstudents);
    $totalactivities = count($activities);
    $totalcells = $totalstudents * $totalactivities;
    $completedcells = 0;

    foreach ($completions as $studentcompletions) {
        foreach ($studentcompletions as $completed) {
            if ($completed) {
                $completedcells++;
            }
        }
    }

    $completionrate = $totalcells > 0 ? round(($completedcells / $totalcells) * 100, 2) : 0;

    // Display statistics
    echo html_writer::start_div('card mb-4 shadow-sm');
    echo html_writer::start_div('card-body');
    echo html_writer::start_div('row text-center');
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('div', '<i class="fa fa-users fa-2x text-primary mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_total_students', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $totalstudents, ['class' => 'text-primary font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('div', '<i class="fa fa-tasks fa-2x text-info mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_total_activities', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $totalactivities, ['class' => 'text-info font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('div', '<i class="fa fa-check-circle fa-2x text-success mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_total_completions', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $completedcells . ' / ' . $totalcells, ['class' => 'text-success font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::start_div('col-md-3');
    echo html_writer::tag('div', '<i class="fa fa-chart-pie fa-2x ' . ($completionrate >= 70 ? 'text-success' : 'text-warning') . ' mb-2"></i>', ['class' => '']);
    echo html_writer::tag('h6', get_string('stats_completion_rate', 'block_report_educam1'), ['class' => 'text-muted small']);
    echo html_writer::tag('h2', $completionrate . '%', ['class' => ($completionrate >= 70 ? 'text-success' : 'text-warning') . ' font-weight-bold']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Pagination
    $start = $page * $perpage;
    $students = array_slice($allstudents, $start, $perpage);

    // Per page selector
    echo html_writer::start_div('mb-3 d-flex justify-content-between align-items-center');
    echo html_writer::tag('span', get_string('showing_entries', 'block_report_educam1', [
        'start' => $start + 1,
        'end' => min($start + $perpage, $totalstudents),
        'total' => $totalstudents
    ]));

    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
    foreach ($urlparams as $key => $value) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $key, 'value' => $value]);
    }
    echo html_writer::tag('label', get_string('per_page', 'block_report_educam1') . ':', ['class' => 'mr-2']);
    $perpageoptions = [10 => '10', 25 => '25', 50 => '50', 100 => '100'];
    echo html_writer::select($perpageoptions, 'perpage', $perpage, false, [
        'class' => 'form-control',
        'onchange' => 'this.form.submit()'
    ]);
    echo html_writer::end_tag('form');
    echo html_writer::end_div();

    // Display table with horizontal scroll
    echo html_writer::start_div('table-responsive shadow-sm');
    echo html_writer::start_tag('table', ['class' => 'table table-bordered table-hover', 'id' => 'matrix-table']);
    echo html_writer::start_tag('thead', ['class' => 'thead-dark']);
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('column_idnumber', 'block_report_educam1'), ['class' => 'sticky-col text-center', 'style' => 'min-width: 100px;']);
    echo html_writer::tag('th', get_string('column_firstname', 'block_report_educam1'), ['class' => 'text-center', 'style' => 'min-width: 120px;']);
    echo html_writer::tag('th', get_string('column_lastname', 'block_report_educam1'), ['class' => 'text-center', 'style' => 'min-width: 120px;']);
    echo html_writer::tag('th', get_string('column_email', 'block_report_educam1'), ['class' => 'text-center', 'style' => 'min-width: 180px;']);

    foreach ($activities as $activity) {
        $activityname = !empty($activity->activityname) ? $activity->activityname : 'ID ' . $activity->cmid;
        echo html_writer::tag('th', s($activityname), [
            'class' => 'text-center small',
            'style' => 'min-width: 100px; max-width: 150px; white-space: normal;'
        ]);
    }

    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($students as $student) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', s($student->idnumber), ['class' => 'sticky-col']);
        echo html_writer::tag('td', s($student->firstname));
        echo html_writer::tag('td', s($student->lastname));
        echo html_writer::tag('td', s($student->email));

        foreach ($activities as $activity) {
            $completed = isset($completions[$student->id][$activity->cmid]) ?
                $completions[$student->id][$activity->cmid] : false;

            $symbol = $completed ?
                get_string('completed_symbol', 'block_report_educam1') : '';
            $cellclass = $completed ? 'bg-success text-white' : 'bg-light';
            $title = $completed ? get_string('status_completed', 'block_report_educam1') : get_string('status_not_completed', 'block_report_educam1');

            echo html_writer::tag('td', $symbol, [
                'class' => 'text-center ' . $cellclass,
                'style' => 'font-size: 1.2em; font-weight: bold;',
                'title' => $title
            ]);
        }

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    // Pagination controls
    if ($totalstudents > $perpage) {
        $totalpages = ceil($totalstudents / $perpage);
        report_educam1_display_pagination($page, $totalpages, $urlparams);
    }

    // Add improved CSS for matrix view
    echo html_writer::tag('style', '
        .table-responsive {
            overflow-x: auto;
            border-radius: 0.375rem;
        }
        .sticky-col {
            position: sticky;
            left: 0;
            background-color: #343a40 !important;
            color: white !important;
            z-index: 10;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        tbody .sticky-col {
            background-color: #fff !important;
            color: #000 !important;
            font-weight: 600;
        }
        thead .sticky-col {
            z-index: 11;
        }
        #matrix-table tbody tr:hover {
            background-color: #e9ecef;
        }
        #report-table tbody tr:hover {
            background-color: #e9ecef;
        }
        .thead-dark th {
            background-color: #343a40;
            border-color: #454d55;
            font-weight: 600;
        }
        .card {
            border: none;
        }
        .shadow-sm {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
        }
        .alphabet-filter .btn {
            margin: 0 2px;
        }
    ');
}

/**
 * Display pagination controls.
 *
 * @param int $currentpage Current page number
 * @param int $totalpages Total number of pages
 * @param array $urlparams URL parameters
 */
function report_educam1_display_pagination($currentpage, $totalpages, $urlparams) {
    echo html_writer::start_tag('nav', ['aria-label' => 'Page navigation']);
    echo html_writer::start_tag('ul', ['class' => 'pagination justify-content-center']);

    // Previous button
    $prevclass = $currentpage <= 0 ? 'page-item disabled' : 'page-item';
    echo html_writer::start_tag('li', ['class' => $prevclass]);
    if ($currentpage > 0) {
        $prevparams = array_merge($urlparams, ['page' => $currentpage - 1]);
        $prevurl = new moodle_url('/blocks/report_educam1/report.php', $prevparams);
        echo html_writer::link($prevurl, '&laquo;', ['class' => 'page-link']);
    } else {
        echo html_writer::span('&laquo;', 'page-link');
    }
    echo html_writer::end_tag('li');

    // Page numbers
    $startpage = max(0, $currentpage - 2);
    $endpage = min($totalpages - 1, $currentpage + 2);

    if ($startpage > 0) {
        $firstparams = array_merge($urlparams, ['page' => 0]);
        $firsturl = new moodle_url('/blocks/report_educam1/report.php', $firstparams);
        echo html_writer::start_tag('li', ['class' => 'page-item']);
        echo html_writer::link($firsturl, '1', ['class' => 'page-link']);
        echo html_writer::end_tag('li');

        if ($startpage > 1) {
            echo html_writer::tag('li', html_writer::span('...', 'page-link'), ['class' => 'page-item disabled']);
        }
    }

    for ($i = $startpage; $i <= $endpage; $i++) {
        $pageclass = $i === $currentpage ? 'page-item active' : 'page-item';
        echo html_writer::start_tag('li', ['class' => $pageclass]);
        $pageparams = array_merge($urlparams, ['page' => $i]);
        $pageurl = new moodle_url('/blocks/report_educam1/report.php', $pageparams);
        echo html_writer::link($pageurl, $i + 1, ['class' => 'page-link']);
        echo html_writer::end_tag('li');
    }

    if ($endpage < $totalpages - 1) {
        if ($endpage < $totalpages - 2) {
            echo html_writer::tag('li', html_writer::span('...', 'page-link'), ['class' => 'page-item disabled']);
        }

        $lastparams = array_merge($urlparams, ['page' => $totalpages - 1]);
        $lasturl = new moodle_url('/blocks/report_educam1/report.php', $lastparams);
        echo html_writer::start_tag('li', ['class' => 'page-item']);
        echo html_writer::link($lasturl, $totalpages, ['class' => 'page-link']);
        echo html_writer::end_tag('li');
    }

    // Next button
    $nextclass = $currentpage >= $totalpages - 1 ? 'page-item disabled' : 'page-item';
    echo html_writer::start_tag('li', ['class' => $nextclass]);
    if ($currentpage < $totalpages - 1) {
        $nextparams = array_merge($urlparams, ['page' => $currentpage + 1]);
        $nexturl = new moodle_url('/blocks/report_educam1/report.php', $nextparams);
        echo html_writer::link($nexturl, '&raquo;', ['class' => 'page-link']);
    } else {
        echo html_writer::span('&raquo;', 'page-link');
    }
    echo html_writer::end_tag('li');

    echo html_writer::end_tag('ul');
    echo html_writer::end_tag('nav');
}
