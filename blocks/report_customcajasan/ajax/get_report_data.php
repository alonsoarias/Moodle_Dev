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
 * AJAX endpoint to get report data - Optimizado para mejor rendimiento.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/blocks/report_customcajasan/lib.php');

if (function_exists('set_time_limit')) {
    set_time_limit(300);
}
raise_memory_limit(MEMORY_EXTRA);

require_login(null, false);

if (!confirm_sesskey()) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidsesskey', 'error')
    ]);
    die();
}

$instanceid = optional_param('instanceid', 0, PARAM_INT);
$coursecontextflag = optional_param('coursecontext', 0, PARAM_INT);
$requestedcourse = optional_param('courseid', 0, PARAM_INT);
$requestedcategory = optional_param('categoryid', 0, PARAM_INT);

$systemcontext = context_system::instance();
$blockinfo = $instanceid ? report_customcajasan_load_block_instance($instanceid) : null;

if ($instanceid && !$blockinfo) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidblockinstance', 'block_report_customcajasan')
    ]);
    die();
}

$incourse = false;
$coursecontext = null;
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
    }
}

if ($coursecontextflag && $requestedcourse) {
    try {
        $coursecontext = context_course::instance($requestedcourse);
        if (!in_array((int)$requestedcourse, $forcedcourseids, true)) {
            $forcedcourseids[] = (int)$requestedcourse;
        }
        $incourse = true;
    } catch (Exception $e) {
        $incourse = false;
    }
}

if (!$incourse && $requestedcourse) {
    try {
        $fallbackcontext = context_course::instance($requestedcourse);
        if (has_capability('moodle/course:update', $fallbackcontext)) {
            $coursecontext = $fallbackcontext;
            if (!in_array((int)$requestedcourse, $forcedcourseids, true)) {
                $forcedcourseids[] = (int)$requestedcourse;
            }
            $incourse = true;
        }
    } catch (Exception $e) {
        $incourse = false;
    }
}

try {
    if ($incourse) {
        require_capability('moodle/course:update', $coursecontext);
    } else {
        require_capability('block/report_customcajasan:manageblock', $systemcontext);
        require_capability('block/report_customcajasan:viewreport', $systemcontext);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => get_string('nopermissions', 'error', $incourse ? 'moodle/course:update' : 'block/report_customcajasan:viewreport')
    ]);
    die();
}

try {
    $filters = [
        'idnumber' => optional_param('idnumber', '', PARAM_TEXT),
        'firstname' => optional_param('firstname', '', PARAM_TEXT),
        'lastname' => optional_param('lastname', '', PARAM_TEXT),
        'estado' => optional_param('estado', '', PARAM_TEXT),
        'startdate' => optional_param('startdate', '', PARAM_TEXT),
        'enddate' => optional_param('enddate', '', PARAM_TEXT)
    ];

    if (!empty($filters['startdate'])) {
        $filters['startdate'] = strtotime($filters['startdate']);
    }
    if (!empty($filters['enddate'])) {
        $filters['enddate'] = strtotime($filters['enddate'] . ' 23:59:59');
    }

    $scope = report_customcajasan_effective_scope(
        $configcategories,
        $forcedcourseids,
        $requestedcategory ?: null,
        $incourse ? null : ($requestedcourse ?: null)
    );

    if ($incourse && empty($scope['courseids']) && !empty($forcedcourseids)) {
        $scope['courseids'] = report_customcajasan_normalize_id_list($forcedcourseids);
    }

    if ($scope['requiresselection'] && !$incourse) {
        echo json_encode([
            'success' => true,
            'html' => html_writer::div(
                get_string('report_no_scope_configured', 'block_report_customcajasan'),
                'alert alert-warning'
            ),
            'count' => 0
        ]);
        die();
    }

    $filters['courseids'] = $scope['courseids'];
    $filters['categoryids'] = $scope['categoryids'];
    $filters['category'] = $requestedcategory;
    $filters['course'] = $requestedcourse;

    $_SESSION['report_customcajasan_filters'] = $filters;

    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 100, PARAM_INT);
    if ($perpage == 0) {
        $perpage = 1000;
    }

    $totalcount = report_customcajasan_count_data($filters);
    $limitfrom = $page * $perpage;
    $enrollments = report_customcajasan_get_data($filters, $limitfrom, $perpage);

    if (empty($enrollments)) {
        echo json_encode([
            'success' => true,
            'html' => html_writer::div(
                get_string('no_data', 'block_report_customcajasan'),
                'alert alert-info'
            ),
            'count' => 0
        ]);
        die();
    }

    $html = html_writer::tag(
        'div',
        get_string('total_records', 'block_report_customcajasan') . ': ' . $totalcount,
        ['class' => 'font-weight-bold mb-2']
    );

    $table = new html_table();
    $table->head = [
        get_string('column_identificacion', 'block_report_customcajasan'),
        get_string('column_nombres', 'block_report_customcajasan'),
        get_string('column_apellidos', 'block_report_customcajasan'),
        get_string('column_correo', 'block_report_customcajasan'),
        get_string('column_curso', 'block_report_customcajasan'),
        get_string('column_categoria', 'block_report_customcajasan'),
        get_string('column_unidad', 'block_report_customcajasan'),
        get_string('column_fecha_matricula', 'block_report_customcajasan'),
        get_string('column_ultimo_acceso', 'block_report_customcajasan'),
        get_string('column_fecha_certificado', 'block_report_customcajasan'),
        get_string('column_estado', 'block_report_customcajasan')
    ];
    $table->data = [];

    foreach ($enrollments as $enrollment) {
        $courselink = html_writer::link(
            new moodle_url('/course/view.php', ['id' => $enrollment->courseid]),
            $enrollment->curso,
            ['target' => '_blank']
        );

        $ultimoacceso = $enrollment->ultimo_acceso;
        if ($enrollment->estado === 'NO INICIADO' || empty($ultimoacceso)) {
            $ultimoacceso = get_string('never', 'block_report_customcajasan');
        }

        $table->data[] = [
            $enrollment->identificacion,
            $enrollment->nombres,
            $enrollment->apellidos,
            $enrollment->correo,
            $courselink,
            $enrollment->categoria,
            $enrollment->unidad,
            $enrollment->fecha_matricula,
            $ultimoacceso,
            $enrollment->fecha_certificado,
            $enrollment->estado
        ];
    }

    $table->id = 'enrollment-report-table';
    $table->attributes['class'] = 'table table-striped table-bordered table-hover';

    $html .= html_writer::table($table);

    $baseurlparams = [
        'categoryid' => $requestedcategory,
        'courseid' => $requestedcourse,
        'idnumber' => $filters['idnumber'],
        'firstname' => $filters['firstname'],
        'lastname' => $filters['lastname'],
        'estado' => $filters['estado'],
        'startdate' => !empty($filters['startdate']) ? date('Y-m-d', $filters['startdate']) : '',
        'enddate' => !empty($filters['enddate']) ? date('Y-m-d', $filters['enddate']) : ''
    ];

    if ($incourse) {
        $baseurlparams['coursecontext'] = 1;
        if (!empty($forcedcourseids)) {
            $baseurlparams['courseid'] = reset($forcedcourseids);
        }
    }

    if ($instanceid) {
        $baseurlparams['instanceid'] = $instanceid;
    }

    $baseurl = new moodle_url('/blocks/report_customcajasan/report.php', $baseurlparams);
    $html .= custom_paging_bar($totalcount, $page, $perpage, $baseurl);

    echo json_encode([
        'success' => true,
        'html' => $html,
        'count' => $totalcount
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error processing data: ' . $e->getMessage()
    ]);
}
