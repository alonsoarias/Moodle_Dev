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
 * AJAX endpoint to get report data
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
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

global $SESSION;

if (!confirm_sesskey()) {
    echo json_encode([
        'success' => false,
        'error' => get_string('invalidsesskey', 'error')
    ]);
    die();
}

$blockinstanceid = optional_param('blockinstanceid', 0, PARAM_INT);
$blockrestrictions = block_report_customcajasan_get_block_restrictions($blockinstanceid);

$courseid = optional_param('courseid', 0, PARAM_INT);
if ($courseid && $courseid != SITEID) {
    $context = context_course::instance($courseid, IGNORE_MISSING);
} else if (!empty($blockrestrictions['parentcontext'])) {
    $context = $blockrestrictions['parentcontext'];
} else {
    $context = context_system::instance();
}

if (!$context) {
    $context = context_system::instance();
}

$hasaccess = false;

try {
    if ($context->contextlevel == CONTEXT_SYSTEM) {
        $hasaccess = has_capability('moodle/site:config', $context);
    } else if ($context->contextlevel == CONTEXT_COURSE) {
        $hasaccess = has_capability('moodle/course:update', $context) ||
                     has_capability('moodle/course:manageactivities', $context);
        
        // Verify block restrictions
        if ($hasaccess && !empty($courseid)) {
            $allowedcourses = $blockrestrictions['courses'];
            $allowedcategories = $blockrestrictions['expandedcategories'];
            
            if (!empty($allowedcourses)) {
                $courseallowed = in_array((int)$courseid, $allowedcourses, true);
                
                if (!$courseallowed && !empty($allowedcategories)) {
                    $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
                    if ($coursecategory !== false) {
                        $courseallowed = in_array((int)$coursecategory, $allowedcategories, true);
                    }
                }
                
                $hasaccess = $courseallowed;
            } else if (!empty($allowedcategories)) {
                $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
                if ($coursecategory !== false) {
                    $hasaccess = in_array((int)$coursecategory, $allowedcategories, true);
                } else {
                    $hasaccess = false;
                }
            }
        }
    }
    
    if (!$hasaccess) {
        throw new required_capability_exception(
            $context,
            'block/report_customcajasan:viewreport',
            'nopermissions',
            ''
        );
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => get_string('nopermissions', 'error')
    ]);
    die();
}

try {
    global $DB;

    $filters = array(
        'category' => optional_param('categoryid', 0, PARAM_INT),
        'course' => optional_param('courseid', 0, PARAM_INT),
        'idnumber' => optional_param('idnumber', '', PARAM_TEXT),
        'firstname' => optional_param('firstname', '', PARAM_TEXT),
        'lastname' => optional_param('lastname', '', PARAM_TEXT),
        'estado' => optional_param('estado', '', PARAM_TEXT),
        'startdate' => optional_param('startdate', '', PARAM_TEXT),
        'enddate' => optional_param('enddate', '', PARAM_TEXT)
    );

    $allowedcourses = $blockrestrictions['courses'];
    $allowedcategories = $blockrestrictions['expandedcategories'];

    if (!empty($allowedcategories) && !empty($filters['category']) &&
        !in_array((int)$filters['category'], $allowedcategories, true)) {
        $filters['category'] = 0;
    }

    if (!empty($filters['course']) && (!empty($allowedcourses) || !empty($allowedcategories))) {
        $courseallowed = in_array((int)$filters['course'], $allowedcourses, true);
        if (!$courseallowed && !empty($allowedcategories)) {
            $coursecategory = $DB->get_field('course', 'category', ['id' => $filters['course']]);
            if ($coursecategory !== false) {
                $courseallowed = in_array((int)$coursecategory, $allowedcategories, true);
            }
        }
        if (!$courseallowed) {
            $filters['course'] = 0;
        }
    }

    if (!empty($filters['category']) && !empty($filters['course']) &&
            !report_customcajasan_course_matches_category($filters['course'], $filters['category'])) {
        $filters['course'] = 0;
    }

    if (!empty($filters['startdate'])) {
        $filters['startdate'] = strtotime($filters['startdate']);
    }
    
    if (!empty($filters['enddate'])) {
        $filters['enddate'] = strtotime($filters['enddate'] . ' 23:59:59');
    }

    $filters['allowedcourses'] = $allowedcourses;
    $filters['allowedcategories'] = $allowedcategories;
    $filters['blockinstanceid'] = $blockinstanceid;

    $SESSION->report_customcajasan_filters = $filters;
    
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 100, PARAM_INT);
    
    if ($perpage == 0) {
        $perpage = 1000;
    }
    
    $totalcount = report_customcajasan_count_data($filters);
    
    $limitfrom = $page * $perpage;
    $enrollments = report_customcajasan_get_data($filters, $limitfrom, $perpage);

    $datetimeformat = get_string('strftimedatetime', 'langconfig');
    foreach ($enrollments as $enrollment) {
        $enrollment->fecha_matricula = !empty($enrollment->fecha_matricula)
            ? userdate((int)$enrollment->fecha_matricula, $datetimeformat)
            : get_string('never', 'block_report_customcajasan');

        if (!empty($enrollment->ultimo_acceso)) {
            $enrollment->ultimo_acceso = userdate((int)$enrollment->ultimo_acceso, $datetimeformat);
        } else {
            $enrollment->ultimo_acceso = '';
        }

        if (!empty($enrollment->fecha_certificado)) {
            $enrollment->fecha_certificado = userdate((int)$enrollment->fecha_certificado, $datetimeformat);
        } else {
            $enrollment->fecha_certificado = '';
        }
    }
    
    $html = '';
    if (empty($enrollments)) {
        $html = html_writer::tag('div', 
            get_string('no_data', 'block_report_customcajasan'), 
            array('class' => 'alert alert-info'));
    } else {
        $html .= html_writer::tag('div', 
            get_string('total_records', 'block_report_customcajasan') . ': ' . $totalcount, 
            array('class' => 'font-weight-bold mb-2'));
        
        $table = new html_table();
        $table->head = array(
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
        );
        $table->data = array();
        
        foreach ($enrollments as $enrollment) {
            $curso_link = html_writer::link(
                new moodle_url('/course/view.php', array('id' => $enrollment->courseid)),
                $enrollment->curso,
                array('target' => '_blank')
            );
            
            $ultimo_acceso = $enrollment->ultimo_acceso;
            if ($enrollment->estado === 'NO INICIADO' || empty($ultimo_acceso)) {
                $ultimo_acceso = get_string('never', 'block_report_customcajasan');
            }
            
            $table->data[] = array(
                $enrollment->identificacion,
                $enrollment->nombres,
                $enrollment->apellidos,
                $enrollment->correo,
                $curso_link,
                $enrollment->categoria,
                $enrollment->unidad,
                $enrollment->fecha_matricula,
                $ultimo_acceso,
                $enrollment->fecha_certificado, 
                $enrollment->estado
            );
        }
        
        $table->id = 'enrollment-report-table';
        $table->attributes['class'] = 'table table-striped table-bordered table-hover';
        
        $html .= html_writer::table($table);
        
        $baseurl = new moodle_url('/blocks/report_customcajasan/report.php', [
            'categoryid' => $filters['category'],
            'courseid' => $filters['course'],
            'idnumber' => $filters['idnumber'],
            'firstname' => $filters['firstname'],
            'lastname' => $filters['lastname'],
            'estado' => $filters['estado'],
            'startdate' => $filters['startdate'] ? date('Y-m-d', $filters['startdate']) : '',
            'enddate' => $filters['enddate'] ? date('Y-m-d', $filters['enddate']) : '',
            'blockinstanceid' => $blockinstanceid,
        ]);
        
        $html .= custom_paging_bar($totalcount, $page, $perpage, $baseurl);
    }
    
    $response = array(
        'success' => true,
        'html' => $html,
        'count' => $totalcount
    );
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error processing data: ' . $e->getMessage()
    ]);
}