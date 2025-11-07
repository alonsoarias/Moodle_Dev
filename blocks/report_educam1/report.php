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

$instanceid = required_param('instanceid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$view = optional_param('view', 'individual', PARAM_ALPHA); // 'individual' or 'matrix'
$download = optional_param('download', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_ALPHA);

// Get course and context
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);

// Check permissions
$canedit = has_capability('moodle/course:update', $context);
$canview = has_capability('block/report_educam1:viewreport', $context);

if (!$canedit && !$canview) {
    require_capability('moodle/course:update', $context);
}

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

// Set up page
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/report_educam1/report.php', [
    'instanceid' => $instanceid,
    'courseid' => $courseid,
    'view' => $view
]));
$PAGE->set_pagelayout('report');

$activitytypename = report_educam1_get_activity_type_name($activitytype);
$page_title = get_string('report_title', 'block_report_educam1');

$PAGE->set_title($page_title);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add($page_title);

// Handle download
if ($download) {
    if ($view === 'individual') {
        $data = report_educam1_get_individual_view_data($courseid, $activitytype);
        $filename = get_string('export_filename_individual', 'block_report_educam1') . '_' . date('Y-m-d');

        if ($format === 'csv') {
            report_educam1_export_individual_csv($data, $filename);
        } else {
            report_educam1_export_individual_spreadsheet($data, $filename, $format, $page_title);
        }
    } else {
        $data = report_educam1_get_matrix_view_data($courseid, $activitytype);
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
echo html_writer::start_div('alert alert-info');
echo html_writer::tag('strong', get_string('report_viewing_course', 'block_report_educam1') . ': ');
echo html_writer::span(format_string($course->fullname));
echo html_writer::empty_tag('br');
echo html_writer::tag('strong', get_string('report_activity_type', 'block_report_educam1') . ': ');
echo html_writer::span($activitytypename);
echo html_writer::end_div();

// View switcher
echo html_writer::start_div('btn-group mb-3', ['role' => 'group']);

$individualurl = new moodle_url('/blocks/report_educam1/report.php', [
    'instanceid' => $instanceid,
    'courseid' => $courseid,
    'view' => 'individual'
]);
$individualclass = $view === 'individual' ? 'btn btn-primary active' : 'btn btn-secondary';
echo html_writer::link($individualurl, get_string('view_individual', 'block_report_educam1'), ['class' => $individualclass]);

$matrixurl = new moodle_url('/blocks/report_educam1/report.php', [
    'instanceid' => $instanceid,
    'courseid' => $courseid,
    'view' => 'matrix'
]);
$matrixclass = $view === 'matrix' ? 'btn btn-primary active' : 'btn btn-secondary';
echo html_writer::link($matrixurl, get_string('view_matrix', 'block_report_educam1'), ['class' => $matrixclass]);

echo html_writer::end_div();

// Display appropriate view
if ($view === 'individual') {
    report_educam1_display_individual_view($courseid, $activitytype);
} else {
    report_educam1_display_matrix_view($courseid, $activitytype);
}

// Export options
echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('export_header', 'block_report_educam1'), ['class' => 'card-title']);

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'view', 'value' => $view]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'download', 'value' => '1']);

echo html_writer::tag('label', get_string('export_format', 'block_report_educam1') . ':', ['class' => 'mr-2']);

$formatoptions = [
    'excel' => get_string('export_excel', 'block_report_educam1'),
    'ods' => get_string('export_ods', 'block_report_educam1'),
    'csv' => get_string('export_csv', 'block_report_educam1')
];
echo html_writer::select($formatoptions, 'format', $format, false, ['class' => 'form-control mr-2']);

echo html_writer::tag('button', get_string('export_button', 'block_report_educam1'), [
    'type' => 'submit',
    'class' => 'btn btn-primary'
]);

echo html_writer::end_tag('form');
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();

/**
 * Display individual view.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type
 */
function report_educam1_display_individual_view($courseid, $activitytype) {
    global $OUTPUT;

    $data = report_educam1_get_individual_view_data($courseid, $activitytype);

    if (empty($data)) {
        echo html_writer::div(
            get_string('no_data', 'block_report_educam1'),
            'alert alert-warning'
        );
        return;
    }

    // Calculate statistics
    $totalrows = count($data);
    $completedrows = array_filter($data, function($item) {
        return $item->completed;
    });
    $completedcount = count($completedrows);
    $completionrate = $totalrows > 0 ? round(($completedcount / $totalrows) * 100, 2) : 0;

    // Display statistics
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('stats_total_students', 'block_report_educam1') . ': ' .
        count(array_unique(array_column($data, 'userid'))), ['class' => 'card-text']);
    echo html_writer::tag('p', get_string('stats_completed', 'block_report_educam1') . ': ' . $completedcount .
        ' / ' . $totalrows . ' (' . $completionrate . '%)', ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Display table
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-bordered']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('column_idnumber', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_firstname', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_lastname', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_email', 'block_report_educam1'));
    echo html_writer::tag('th', 'Actividad');
    echo html_writer::tag('th', get_string('column_completion_date', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_status', 'block_report_educam1'));
    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($data as $item) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $item->idnumber);
        echo html_writer::tag('td', $item->firstname);
        echo html_writer::tag('td', $item->lastname);
        echo html_writer::tag('td', $item->email);
        echo html_writer::tag('td', $item->activityname);

        $completiondate = $item->completiondate ? userdate($item->completiondate, '%Y-%m-%d %H:%M') : '-';
        echo html_writer::tag('td', $completiondate);

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
}

/**
 * Display matrix view.
 *
 * @param int $courseid Course ID
 * @param string $activitytype Activity type
 */
function report_educam1_display_matrix_view($courseid, $activitytype) {
    global $OUTPUT;

    $matrixdata = report_educam1_get_matrix_view_data($courseid, $activitytype);
    $students = $matrixdata['students'];
    $activities = $matrixdata['activities'];
    $completions = $matrixdata['completions'];

    if (empty($students) || empty($activities)) {
        echo html_writer::div(
            get_string('no_data', 'block_report_educam1'),
            'alert alert-warning'
        );
        return;
    }

    // Calculate statistics
    $totalstudents = count($students);
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
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('p', get_string('stats_total_students', 'block_report_educam1') . ': ' . $totalstudents, ['class' => 'card-text']);
    echo html_writer::tag('p', get_string('stats_total_activities', 'block_report_educam1') . ': ' . $totalactivities, ['class' => 'card-text']);
    echo html_writer::tag('p', get_string('stats_completion_rate', 'block_report_educam1') . ': ' .
        $completedcells . ' / ' . $totalcells . ' (' . $completionrate . '%)', ['class' => 'card-text']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Display table with horizontal scroll
    echo html_writer::start_div('table-responsive');
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-bordered table-sm']);
    echo html_writer::start_tag('thead');
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('column_idnumber', 'block_report_educam1'), ['class' => 'sticky-col']);
    echo html_writer::tag('th', get_string('column_firstname', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_lastname', 'block_report_educam1'));
    echo html_writer::tag('th', get_string('column_email', 'block_report_educam1'));

    foreach ($activities as $activity) {
        $activityname = !empty($activity->activityname) ? $activity->activityname : 'ID ' . $activity->cmid;
        echo html_writer::tag('th', $activityname, [
            'class' => 'text-center small',
            'style' => 'min-width: 100px; max-width: 150px; white-space: normal;'
        ]);
    }

    echo html_writer::end_tag('tr');
    echo html_writer::end_tag('thead');

    echo html_writer::start_tag('tbody');
    foreach ($students as $student) {
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $student->idnumber, ['class' => 'sticky-col']);
        echo html_writer::tag('td', $student->firstname);
        echo html_writer::tag('td', $student->lastname);
        echo html_writer::tag('td', $student->email);

        foreach ($activities as $activity) {
            $completed = isset($completions[$student->id][$activity->cmid]) ?
                $completions[$student->id][$activity->cmid] : false;

            $symbol = $completed ?
                get_string('completed_symbol', 'block_report_educam1') : '';
            $cellclass = $completed ? 'bg-success text-white' : 'bg-light';

            echo html_writer::tag('td', $symbol, [
                'class' => 'text-center ' . $cellclass,
                'style' => 'font-size: 1.2em;'
            ]);
        }

        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();

    // Add some CSS for sticky column
    echo html_writer::tag('style', '
        .table-responsive {
            overflow-x: auto;
        }
        .sticky-col {
            position: sticky;
            left: 0;
            background-color: white;
            z-index: 1;
        }
        thead .sticky-col {
            z-index: 2;
        }
    ');
}
