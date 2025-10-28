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
 * Report display page.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/report_customcajasan/lib.php');

$instanceid = optional_param('instanceid', 0, PARAM_INT);
$coursecontextflag = optional_param('coursecontext', 0, PARAM_INT);
$requestedcourse = optional_param('courseid', 0, PARAM_INT);
$requestedcategory = optional_param('categoryid', 0, PARAM_INT);
$idnumber = optional_param('idnumber', '', PARAM_TEXT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$estado = optional_param('estado', '', PARAM_TEXT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate = optional_param('enddate', '', PARAM_TEXT);
$download = optional_param('download', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 100, PARAM_INT);

$systemcontext = context_system::instance();
$blockinfo = $instanceid ? report_customcajasan_load_block_instance($instanceid) : null;

if ($instanceid && !$blockinfo) {
    throw new moodle_exception('invalidblockinstance', 'block_report_customcajasan');
}

$incourse = false;
$coursecontext = null;
$course = null;
$forcedcourseids = [];
$configcategories = [];

if ($blockinfo) {
    $configcategories = $blockinfo['categoryids'];
    if (!empty($blockinfo['courseid'])) {
        $forcedcourseids[] = (int)$blockinfo['courseid'];
    }
    if ($blockinfo['incourse'] && !empty($blockinfo['courseid'])) {
        $incourse = true;
        $coursecontext = context_course::instance($blockinfo['courseid']);
        $course = $DB->get_record('course', ['id' => $blockinfo['courseid']], '*', MUST_EXIST);
    }
}

if ($coursecontextflag && $requestedcourse) {
    if (!$course || $course->id !== (int)$requestedcourse) {
        $course = $DB->get_record('course', ['id' => $requestedcourse], '*', MUST_EXIST);
    }
    $coursecontext = context_course::instance($requestedcourse);
    $forcedcourseids[] = (int)$requestedcourse;
    $incourse = true;
}

if ($incourse && $course) {
    require_login($course);
} else {
    require_login();
}

if ($incourse) {
    // Allow access if user can edit the course OR has the specific viewreport capability in the course context.
    $canedit = has_capability('moodle/course:update', $coursecontext);
    $canview = has_capability('block/report_customcajasan:viewreport', $coursecontext);

    if (!$canedit && !$canview) {
        // If neither capability is present, throw the standard error.
        require_capability('moodle/course:update', $coursecontext);
    }
} else {
    require_capability('block/report_customcajasan:manageblock', $systemcontext);
    require_capability('block/report_customcajasan:viewreport', $systemcontext);
}

if (function_exists('set_time_limit')) {
    set_time_limit(0);
}
raise_memory_limit(MEMORY_EXTRA);

$scope = report_customcajasan_effective_scope(
    $configcategories,
    $forcedcourseids,
    $requestedcategory ?: null,
    $incourse ? null : ($requestedcourse ?: null)
);

if ($incourse && empty($scope['courseids']) && !empty($forcedcourseids)) {
    $scope['courseids'] = report_customcajasan_normalize_id_list($forcedcourseids);
}

$filters = [
    'courseids' => $scope['courseids'],
    'categoryids' => $scope['categoryids'],
    'category' => $requestedcategory,
    'course' => $requestedcourse,
    'idnumber' => $idnumber,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'estado' => $estado,
    'startdate' => !empty($startdate) ? strtotime($startdate) : '',
    'enddate' => !empty($enddate) ? strtotime($enddate . ' 23:59:59') : ''
];

$_SESSION['report_customcajasan_filters'] = $filters;

$baseparams = [
    'categoryid' => $requestedcategory,
    'courseid' => $requestedcourse,
    'idnumber' => $idnumber,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'estado' => $estado,
    'startdate' => $startdate,
    'enddate' => $enddate,
    'page' => $page,
    'perpage' => $perpage
];
if ($incourse) {
    $baseparams['coursecontext'] = 1;
    if (!empty($forcedcourseids)) {
        $baseparams['courseid'] = reset($forcedcourseids);
    }
}
if ($instanceid) {
    $baseparams['instanceid'] = $instanceid;
}

$PAGE->set_context($incourse ? $coursecontext : $systemcontext);
$PAGE->set_url(new moodle_url('/blocks/report_customcajasan/report.php', $baseparams));
$PAGE->set_pagelayout('report');

$page_title = $incourse && $course
    ? get_string('report_title_course', 'block_report_customcajasan', $course->fullname)
    : get_string('report_title', 'block_report_customcajasan');

$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('block_report_customcajasan/report', 'init');

if ($download) {
    $scopefordownload = $scope;
    if ($scopefordownload['requiresselection'] && !$incourse) {
        redirect(
            new moodle_url('/blocks/report_customcajasan/report.php', $baseparams),
            get_string('download_scope_missing', 'block_report_customcajasan'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $downloadfilters = $filters;
    $downloadfilters['courseids'] = $scopefordownload['courseids'];
    $downloadfilters['categoryids'] = $scopefordownload['categoryids'];

    if ($format === 'csv') {
        report_customcajasan_export_csv($downloadfilters, 'enrollment_report');
    } else {
        report_customcajasan_export_spreadsheet(
            $downloadfilters,
            'enrollment_report',
            $format,
            $page_title
        );
    }
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading($page_title);

if ($incourse && $course) {
    echo html_writer::start_div('alert alert-info');
    echo html_writer::tag('strong', get_string('viewing_course_report_full', 'block_report_customcajasan') . ': ');
    echo html_writer::span(format_string($course->fullname));
    echo html_writer::end_div();
}

$categorynames = [];
if (!empty($scope['categoryids'])) {
    list($insql, $params) = $DB->get_in_or_equal($scope['categoryids'], SQL_PARAMS_NAMED);
    $records = $DB->get_records_sql("SELECT id, name FROM {course_categories} WHERE id {$insql}", $params);
    foreach ($scope['categoryids'] as $cid) {
        if (!empty($records[$cid])) {
            $categorynames[] = format_string($records[$cid]->name);
        }
    }
}

$scopeinfo = html_writer::start_div('alert alert-secondary');
$scopeinfo .= html_writer::tag('strong', get_string('report_scope_heading', 'block_report_customcajasan'));
$scopeinfo .= html_writer::empty_tag('br');

if ($incourse && $course) {
    $scopeinfo .= html_writer::tag('div',
        get_string('report_scope_current_course', 'block_report_customcajasan', format_string($course->fullname)));
}
if (!empty($categorynames)) {
    $scopeinfo .= html_writer::tag('div', get_string('report_scope_categories', 'block_report_customcajasan'));
    $scopeinfo .= html_writer::alist($categorynames, [], 'ul');
} else if (!$incourse) {
    $scopeinfo .= html_writer::tag('div', get_string('report_scope_notice_frontpage', 'block_report_customcajasan'));
}
$scopeinfo .= html_writer::tag('div',
    get_string('report_scope_effective_courses', 'block_report_customcajasan', count($scope['courseids'])));
$scopeinfo .= html_writer::end_div();

echo $scopeinfo;

echo html_writer::start_div('alert alert-info');
echo html_writer::tag('strong', get_string('status_explanation', 'block_report_customcajasan') . ': ');
echo html_writer::tag('span', get_string('state_aprobado', 'block_report_customcajasan'), ['class' => 'badge badge-success p-2 mr-2']);
echo html_writer::tag('span', get_string('state_encurso', 'block_report_customcajasan'), ['class' => 'badge badge-warning p-2 mr-2']);
echo html_writer::tag('span', get_string('state_noiniciado', 'block_report_customcajasan'), ['class' => 'badge badge-danger p-2 mr-2']);
echo html_writer::tag('span', get_string('state_soloconsulta', 'block_report_customcajasan'), ['class' => 'badge badge-secondary p-2 mr-2']);
echo html_writer::empty_tag('br');
echo html_writer::tag('small', get_string('status_note', 'block_report_customcajasan'));
echo html_writer::end_div();

if ($scope['requiresselection'] && !$incourse) {
    echo html_writer::tag('div',
        get_string('report_no_scope_configured', 'block_report_customcajasan'),
        ['class' => 'alert alert-warning']
    );
}

echo html_writer::start_tag('form', ['id' => 'report-form', 'method' => 'get', 'class' => 'mb-4']);

if ($instanceid) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid]);
}

if ($incourse && !empty($forcedcourseids)) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursecontext', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => reset($forcedcourseids)]);
} else {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursecontext', 'value' => '']);
}

echo html_writer::start_div('container-fluid');

echo html_writer::start_div('row');
if (!$incourse) {
    $categories = report_customcajasan_get_categories();
    $categoryoptions = ['' => get_string('option_all', 'block_report_customcajasan')];
    foreach ($categories as $category) {
        if (!empty($configcategories) && !in_array($category->id, $configcategories, true)) {
            continue;
        }
        $indent = str_repeat('&nbsp;', $category->depth * 3);
        $categoryoptions[$category->id] = $indent . format_string($category->name);
    }
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_category', 'block_report_customcajasan'), ['for' => 'categoryid']);
    echo html_writer::select($categoryoptions, 'categoryid', $requestedcategory, false, ['class' => 'form-control', 'id' => 'categoryid']);
    echo html_writer::end_div();

    $courses = report_customcajasan_get_courses($requestedcategory);
    $courseoptions = ['' => get_string('option_all', 'block_report_customcajasan')];
    foreach ($courses as $courseopt) {
        if (!empty($scope['courseids']) && !in_array($courseopt->id, $scope['courseids'], true)) {
            continue;
        }
        $courseoptions[$courseopt->id] = format_string($courseopt->fullname);
    }
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_course', 'block_report_customcajasan'), ['for' => 'courseid']);
    echo html_writer::select($courseoptions, 'courseid', $requestedcourse, false, ['class' => 'form-control', 'id' => 'courseid']);
    echo html_writer::end_div();
} else {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'id' => 'categoryid', 'value' => $requestedcategory]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'id' => 'courseid', 'value' => reset($forcedcourseids)]);
}

$estadocolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($estadocolclass);
echo html_writer::tag('label', get_string('option_estado', 'block_report_customcajasan'), ['for' => 'estado']);
$estadoptions = report_customcajasan_get_states();
echo html_writer::select($estadoptions, 'estado', $estado, false, ['class' => 'form-control', 'id' => 'estado']);
echo html_writer::end_div();

echo html_writer::end_div();

echo html_writer::start_div('row');
$idnumbercolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($idnumbercolclass);
echo html_writer::tag('label', get_string('idnumber', 'block_report_customcajasan'), ['for' => 'idnumber']);
echo html_writer::empty_tag('input', ['type' => 'text', 'id' => 'idnumber', 'name' => 'idnumber', 'value' => $idnumber, 'class' => 'form-control']);
echo html_writer::end_div();

$firstnamecolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($firstnamecolclass);
echo html_writer::tag('label', get_string('option_firstname', 'block_report_customcajasan'), ['for' => 'firstname']);
echo html_writer::start_div('alphabet-filter mt-1 mb-2');
echo html_writer::tag('span', get_string('option_filter_by_letter', 'block_report_customcajasan') . ': ', ['class' => 'mr-1 small']);
echo html_writer::link('#', get_string('option_all', 'block_report_customcajasan'), ['class' => 'btn btn-sm btn-outline-secondary' . (empty($firstname) ? ' active' : ''), 'data-letter' => '', 'data-target' => 'firstname']);
foreach (range('A', 'Z') as $letter) {
    $active = $firstname === $letter ? ' active' : '';
    echo html_writer::link('#', $letter, ['class' => 'btn btn-sm btn-outline-secondary' . $active, 'data-letter' => $letter, 'data-target' => 'firstname']);
}
echo html_writer::end_div();
echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'firstname', 'name' => 'firstname', 'value' => $firstname]);
echo html_writer::end_div();

if (!$incourse) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_lastname', 'block_report_customcajasan'), ['for' => 'lastname']);
    echo html_writer::start_div('alphabet-filter mt-1 mb-2');
    echo html_writer::tag('span', get_string('option_filter_by_letter', 'block_report_customcajasan') . ': ', ['class' => 'mr-1 small']);
    echo html_writer::link('#', get_string('option_all', 'block_report_customcajasan'), ['class' => 'btn btn-sm btn-outline-secondary' . (empty($lastname) ? ' active' : ''), 'data-letter' => '', 'data-target' => 'lastname']);
    foreach (range('A', 'Z') as $letter) {
        $active = $lastname === $letter ? ' active' : '';
        echo html_writer::link('#', $letter, ['class' => 'btn btn-sm btn-outline-secondary' . $active, 'data-letter' => $letter, 'data-target' => 'lastname']);
    }
    echo html_writer::end_div();
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'lastname', 'name' => 'lastname', 'value' => $lastname]);
    echo html_writer::end_div();
} else {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'lastname', 'id' => 'lastname', 'value' => $lastname]);
}

echo html_writer::end_div();

echo html_writer::start_div('row');
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('start_date', 'block_report_customcajasan'), ['for' => 'startdate']);
echo html_writer::empty_tag('input', ['type' => 'date', 'id' => 'startdate', 'name' => 'startdate', 'value' => $startdate, 'class' => 'form-control']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('end_date', 'block_report_customcajasan'), ['for' => 'enddate']);
echo html_writer::empty_tag('input', ['type' => 'date', 'id' => 'enddate', 'name' => 'enddate', 'value' => $enddate, 'class' => 'form-control']);
echo html_writer::end_div();

echo html_writer::start_div('col-md-4 mb-3 d-flex align-items-end');
echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => get_string('search', 'block_report_customcajasan'), 'class' => 'btn btn-primary']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

echo html_writer::start_div('report-results mb-4', ['id' => 'report-results']);
if ($scope['requiresselection'] && !$incourse) {
    echo html_writer::tag('div',
        get_string('report_no_scope_configured', 'block_report_customcajasan'),
        ['class' => 'alert alert-warning']
    );
}
echo html_writer::end_div();

echo html_writer::start_div('per-page-selector mb-3');
echo html_writer::tag('label', get_string('records_per_page', 'block_report_customcajasan') . ':', ['for' => 'perpage', 'class' => 'mr-2']);
$perpageoptions = ['20' => '20', '50' => '50', '100' => '100', '200' => '200', '500' => '500', '1000' => '1000', '0' => get_string('all_records', 'block_report_customcajasan')];
echo html_writer::select($perpageoptions, 'perpage', $perpage, false, ['class' => 'form-control d-inline w-auto', 'id' => 'perpage']);
echo html_writer::end_div();

echo html_writer::start_div('download-options mt-3');
echo html_writer::start_tag('form', ['id' => 'downloadForm', 'method' => 'get']);
if ($instanceid) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'instanceid', 'value' => $instanceid]);
}
if ($incourse) {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursecontext', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => reset($forcedcourseids)]);
} else {
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'coursecontext', 'value' => '0']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $requestedcourse]);
}
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'categoryid', 'value' => $requestedcategory]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'idnumber', 'value' => $idnumber]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'firstname', 'value' => $firstname]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'lastname', 'value' => $lastname]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'estado', 'value' => $estado]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'startdate', 'value' => $startdate]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'enddate', 'value' => $enddate]);

if (!$scope['requiresselection'] || $incourse) {
    $downloadcount = report_customcajasan_count_data($filters);
    if ($downloadcount > 1000) {
        $downloadsummary = html_writer::span('', 'fa fa-info-circle', ['aria-hidden' => 'true']);
        $downloadsummary .= ' ' . get_string('total_records', 'block_report_customcajasan') . ': ' . (int)$downloadcount;
        echo html_writer::div(
            $downloadsummary,
            'alert alert-info download-warning mb-3 p-2',
            ['role' => 'status']
        );
    }
}

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('option_download_format', 'block_report_customcajasan') . ':', ['for' => 'format', 'class' => 'mr-2']);
$formatoptions = [
    'excel' => get_string('option_download_excel', 'block_report_customcajasan'),
    'ods' => get_string('option_download_ods', 'block_report_customcajasan'),
    'csv' => get_string('option_download_csv', 'block_report_customcajasan')
];
echo html_writer::select($formatoptions, 'format', $format, false, ['class' => 'form-control d-inline w-auto']);

echo '<button type="submit" name="download" value="1" class="btn btn-primary ml-2">';
echo get_string('btn_download', 'block_report_customcajasan');
echo '</button>';

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();
