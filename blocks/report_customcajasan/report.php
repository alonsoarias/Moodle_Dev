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
 * Report display page
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/blocks/report_customcajasan/lib.php');

// Verify login
require_login();

// Detectar si venimos de un contexto de curso
$coursecontext_param = optional_param('coursecontext', 0, PARAM_INT);
$courseid_param = optional_param('courseid', 0, PARAM_INT);

// Determinar el contexto apropiado
$systemcontext = context_system::instance();
$incourse = false;
$coursecontext = null;
$course = null;

if ($coursecontext_param && $courseid_param) {
    // Venimos de un curso específico
    $course = $DB->get_record('course', array('id' => $courseid_param), '*', MUST_EXIST);
    $coursecontext = context_course::instance($courseid_param);
    $incourse = true;
}

// Verificar permisos según el contexto
if ($incourse) {
    // Estamos viendo el reporte de un curso específico
    $can_view = has_capability('block/report_customcajasan:viewreport', $coursecontext);
    $is_manager = has_any_capability(['moodle/course:update', 'moodle/course:manageactivities'], $coursecontext);
    
    if (!$can_view && !$is_manager) {
        throw new required_capability_exception($coursecontext, 'block/report_customcajasan:viewreport', 'nopermissions', '');
    }
} else {
    // Estamos en el reporte global del sistema
    $can_view = has_capability('block/report_customcajasan:viewreport', $systemcontext);
    $is_manager = has_any_capability(['moodle/site:config', 'moodle/course:update'], $systemcontext);
    
    if (!$can_view && !$is_manager) {
        throw new required_capability_exception($systemcontext, 'block/report_customcajasan:viewreport', 'nopermissions', '');
    }
}

// Aumentar límites para permitir la generación de reportes grandes
if (function_exists('set_time_limit')) {
    set_time_limit(0); // Sin límite de tiempo para procesar reportes grandes
}
// Aumentar límite de memoria usando el valor definido por el administrador
raise_memory_limit(MEMORY_EXTRA);

// Page setup
$PAGE->set_context($incourse ? $coursecontext : $systemcontext);
$PAGE->set_url(new moodle_url('/blocks/report_customcajasan/report.php', 
    $incourse ? array('courseid' => $courseid_param, 'coursecontext' => 1) : array()));
$PAGE->set_pagelayout('report');

// Ajustar título según contexto
$page_title = $incourse 
    ? get_string('report_title_course', 'block_report_customcajasan', $course->fullname)
    : get_string('report_title', 'block_report_customcajasan');

$PAGE->set_title($page_title);
$PAGE->set_heading($page_title);
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('block_report_customcajasan/report', 'init');

// Get filter parameters for initial page load
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$courseid = optional_param('courseid', $incourse ? $courseid_param : 0, PARAM_INT);
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

// Si estamos en contexto de curso, forzar el filtro de curso
if ($incourse) {
    $courseid = $courseid_param;
}

// Check if at least one filter is applied for initial data load
$filter_selected = !empty($categoryid) || !empty($courseid) || !empty($idnumber) || 
                  !empty($firstname) || !empty($lastname) || !empty($estado) || 
                  !empty($startdate) || !empty($enddate);

// En contexto de curso, siempre hay un filtro seleccionado
if ($incourse) {
    $filter_selected = true;
}

// Handle download requests
if ($download) {
    // Primero, verificar si hay filtros en la sesión
    $session_filters = isset($_SESSION['report_customcajasan_filters']) ? 
                       $_SESSION['report_customcajasan_filters'] : array();
    
    // Prepare filter parameters - Priorizar valores de URL, después valores de sesión
    $filters = array();
    
    // Si estamos en contexto de curso, forzar el filtro
    if ($incourse) {
        $filters['course'] = $courseid_param;
    } else {
        // Categoría
        if (!empty($categoryid)) {
            $filters['category'] = $categoryid;
        } else if (!empty($session_filters['category'])) {
            $filters['category'] = $session_filters['category'];
        }
        
        // Curso
        if (!empty($courseid)) {
            $filters['course'] = $courseid;
        } else if (!empty($session_filters['course'])) {
            $filters['course'] = $session_filters['course'];
        }
    }
    
    // ID
    if (!empty($idnumber)) {
        $filters['idnumber'] = $idnumber;
    } else if (!empty($session_filters['idnumber'])) {
        $filters['idnumber'] = $session_filters['idnumber'];
    }
    
    // Nombres
    if (!empty($firstname)) {
        $filters['firstname'] = $firstname;
    } else if (!empty($session_filters['firstname'])) {
        $filters['firstname'] = $session_filters['firstname'];
    }
    
    // Apellidos
    if (!empty($lastname)) {
        $filters['lastname'] = $lastname;
    } else if (!empty($session_filters['lastname'])) {
        $filters['lastname'] = $session_filters['lastname'];
    }
    
    // Estado
    if (!empty($estado)) {
        $filters['estado'] = $estado;
    } else if (!empty($session_filters['estado'])) {
        $filters['estado'] = $session_filters['estado'];
    }
    
    // Fechas
    if (!empty($startdate)) {
        $filters['startdate'] = strtotime($startdate);
    } else if (!empty($session_filters['startdate'])) {
        $filters['startdate'] = $session_filters['startdate'];
    }
    
    if (!empty($enddate)) {
        $filters['enddate'] = strtotime($enddate . ' 23:59:59');
    } else if (!empty($session_filters['enddate'])) {
        $filters['enddate'] = $session_filters['enddate'];
    }
    
    // Verificar que al menos un filtro esté aplicado (en contexto de curso siempre hay filtro)
    $has_filter = $incourse;
    if (!$has_filter) {
        foreach ($filters as $filter_value) {
            if (!empty($filter_value)) {
                $has_filter = true;
                break;
            }
        }
    }
    
    if (!$has_filter) {
        // Redireccionar a la página del reporte con un mensaje de error
        redirect(
            new moodle_url('/blocks/report_customcajasan/report.php'),
            get_string('filters_required', 'block_report_customcajasan'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
        exit;
    }
    
    // Para descargas, ahora usamos las nuevas funciones optimizadas, sin cargar todos los datos en memoria primero
    if ($format === 'csv') {
        report_customcajasan_export_csv($filters, 'enrollment_report');
    } else {
        report_customcajasan_export_spreadsheet(
            $filters, 
            'enrollment_report', 
            $format, 
            get_string('report_title', 'block_report_customcajasan')
        );
    }
    // La función de exportación ya incluye exit(), por lo que no es necesario aquí
}

// Display form and report
echo $OUTPUT->header();
echo $OUTPUT->heading($page_title);

// Si estamos en contexto de curso, mostrar información del curso
if ($incourse) {
    echo html_writer::start_tag('div', array('class' => 'alert alert-info'));
    echo html_writer::tag('strong', get_string('viewing_course_report_full', 'block_report_customcajasan') . ': ');
    echo html_writer::tag('span', $course->fullname);
    echo html_writer::end_tag('div');
}

// Color codes for status - updated for new status values
echo html_writer::start_tag('div', array('class' => 'alert alert-info'));
echo html_writer::tag('strong', get_string('status_explanation', 'block_report_customcajasan') . ': ');
echo html_writer::tag('span', get_string('state_aprobado', 'block_report_customcajasan'), array('class' => 'badge badge-success p-2 mr-2'));
echo html_writer::tag('span', get_string('state_encurso', 'block_report_customcajasan'), array('class' => 'badge badge-warning p-2 mr-2'));
echo html_writer::tag('span', get_string('state_noiniciado', 'block_report_customcajasan'), array('class' => 'badge badge-danger p-2 mr-2'));
echo html_writer::tag('span', get_string('state_soloconsulta', 'block_report_customcajasan'), array('class' => 'badge badge-secondary p-2 mr-2'));
echo html_writer::empty_tag('br');
echo html_writer::tag('small', get_string('status_note', 'block_report_customcajasan'));
echo html_writer::end_tag('div');

// Info text explaining that filters are required (solo si no estamos en contexto de curso)
if (!$incourse) {
    echo html_writer::tag('div', 
        html_writer::tag('p', 
            html_writer::tag('strong', get_string('filters_required', 'block_report_customcajasan')),
            ['class' => 'small font-italic']
        ),
        ['class' => 'alert alert-warning']
    );
}

// Filter form - Remove form submission handler and set id for JavaScript
echo html_writer::start_tag('form', array('id' => 'report-form', 'method' => 'get', 'class' => 'mb-4'));

// Agregar campos ocultos para mantener el contexto de curso
if ($incourse) {
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'coursecontext', 'value' => '1'));
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid_param));
}

echo html_writer::start_div('container-fluid');

// First row of filters
echo html_writer::start_div('row');

// Category filter - Solo mostrar si NO estamos en contexto de curso
if (!$incourse) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_category', 'block_report_customcajasan'), array('for' => 'categoryid'));
    $categories = report_customcajasan_get_categories();
    $categoryoptions = array();
    $categoryoptions[''] = get_string('option_all', 'block_report_customcajasan');
    foreach ($categories as $category) {
        $indent = str_repeat('&nbsp;', $category->depth * 3);
        $categoryoptions[$category->id] = $indent . $category->name;
    }
    echo html_writer::select($categoryoptions, 'categoryid', $categoryid, false, array('class' => 'form-control', 'id' => 'categoryid'));
    echo html_writer::end_div();

    // Course filter - Solo mostrar si NO estamos en contexto de curso
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_course', 'block_report_customcajasan'), array('for' => 'courseid'));
    $courses = report_customcajasan_get_courses($categoryid);
    $courseoptions = array();
    $courseoptions[''] = get_string('option_all', 'block_report_customcajasan');
    foreach ($courses as $courseopt) {
        $courseoptions[$courseopt->id] = $courseopt->fullname;
    }
    echo html_writer::select($courseoptions, 'courseid', $courseid, false, array('class' => 'form-control', 'id' => 'courseid'));
    echo html_writer::end_div();
} else {
    // En contexto de curso, agregar campo oculto para el curso
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'id' => 'courseid', 'value' => $courseid));
    // Agregar también un campo oculto para categoryid vacío
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'id' => 'categoryid', 'value' => ''));
}

// Estado filter - with updated state options
$estadocolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($estadocolclass);
echo html_writer::tag('label', get_string('option_estado', 'block_report_customcajasan'), array('for' => 'estado'));
$estadoptions = report_customcajasan_get_states();
echo html_writer::select($estadoptions, 'estado', $estado, false, array('class' => 'form-control', 'id' => 'estado'));
echo html_writer::end_div();

echo html_writer::end_div(); // End first row

// Second row of filters
echo html_writer::start_div('row');

// ID number filter
$idnumbercolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($idnumbercolclass);
echo html_writer::tag('label', get_string('idnumber', 'block_report_customcajasan'), array('for' => 'idnumber'));
echo html_writer::empty_tag('input', array(
    'type' => 'text',
    'id' => 'idnumber',
    'name' => 'idnumber',
    'value' => $idnumber,
    'class' => 'form-control'
));
echo html_writer::end_div();

// First name filter with alphabet
$firstnamecolclass = $incourse ? 'col-md-6 mb-3' : 'col-md-4 mb-3';
echo html_writer::start_div($firstnamecolclass);
echo html_writer::tag('label', get_string('option_firstname', 'block_report_customcajasan'), array('for' => 'firstname'));

// Alphabet filter for first name
echo html_writer::start_div('alphabet-filter mt-1 mb-2');
echo html_writer::tag('span', get_string('option_filter_by_letter', 'block_report_customcajasan') . ': ', array('class' => 'mr-1 small'));
echo html_writer::link('#', get_string('option_all', 'block_report_customcajasan'), 
    array('class' => 'btn btn-sm btn-outline-secondary' . (empty($firstname) ? ' active' : ''), 'data-letter' => '', 'data-target' => 'firstname'));

foreach (range('A', 'Z') as $letter) {
    $active = $firstname === $letter ? ' active' : '';
    echo html_writer::link('#', $letter, 
        array('class' => 'btn btn-sm btn-outline-secondary' . $active, 'data-letter' => $letter, 'data-target' => 'firstname'));
}
echo html_writer::end_div();

echo html_writer::empty_tag('input', array(
    'type' => 'hidden',
    'id' => 'firstname',
    'name' => 'firstname',
    'value' => $firstname
));
echo html_writer::end_div();

// Last name filter with alphabet - Solo mostrar si NO estamos en contexto de curso
if (!$incourse) {
    echo html_writer::start_div('col-md-4 mb-3');
    echo html_writer::tag('label', get_string('option_lastname', 'block_report_customcajasan'), array('for' => 'lastname'));

    // Alphabet filter for last name
    echo html_writer::start_div('alphabet-filter mt-1 mb-2');
    echo html_writer::tag('span', get_string('option_filter_by_letter', 'block_report_customcajasan') . ': ', array('class' => 'mr-1 small'));
    echo html_writer::link('#', get_string('option_all', 'block_report_customcajasan'), 
        array('class' => 'btn btn-sm btn-outline-secondary' . (empty($lastname) ? ' active' : ''), 'data-letter' => '', 'data-target' => 'lastname'));

    foreach (range('A', 'Z') as $letter) {
        $active = $lastname === $letter ? ' active' : '';
        echo html_writer::link('#', $letter, 
            array('class' => 'btn btn-sm btn-outline-secondary' . $active, 'data-letter' => $letter, 'data-target' => 'lastname'));
    }
    echo html_writer::end_div();

    echo html_writer::empty_tag('input', array(
        'type' => 'hidden',
        'id' => 'lastname',
        'name' => 'lastname',
        'value' => $lastname
    ));
    echo html_writer::end_div();
} else {
    // En contexto de curso, agregar campo oculto para lastname vacío
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'lastname', 'id' => 'lastname', 'value' => ''));
}

echo html_writer::end_div(); // End second row

// Third row with date filters and submit button
echo html_writer::start_div('row');

// Start date filter
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('start_date', 'block_report_customcajasan'), array('for' => 'startdate'));
echo html_writer::empty_tag('input', array(
    'type' => 'date',
    'id' => 'startdate',
    'name' => 'startdate',
    'value' => $startdate,
    'class' => 'form-control'
));
echo html_writer::end_div();

// End date filter
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('end_date', 'block_report_customcajasan'), array('for' => 'enddate'));
echo html_writer::empty_tag('input', array(
    'type' => 'date',
    'id' => 'enddate',
    'name' => 'enddate',
    'value' => $enddate,
    'class' => 'form-control'
));
echo html_writer::end_div();

// Submit button
echo html_writer::start_div('col-md-4 mb-3 d-flex align-items-end');
echo html_writer::empty_tag('input', array(
    'type' => 'submit',
    'value' => get_string('search', 'block_report_customcajasan'),
    'class' => 'btn btn-primary'
));
echo html_writer::end_div();

echo html_writer::end_div(); // End third row
echo html_writer::end_div(); // End container-fluid
echo html_writer::end_tag('form');

// Results container - This will be updated via AJAX
echo html_writer::start_div('report-results mb-4', array('id' => 'report-results'));

// Show initial message if no filters selected (solo si NO estamos en contexto de curso)
if (!$filter_selected && !$incourse) {
    echo html_writer::tag('div', get_string('select_filter_first', 'block_report_customcajasan'), array('class' => 'alert alert-info'));
}

echo html_writer::end_div(); // End report-results

// Añadir el selector de registros por página
echo html_writer::start_div('per-page-selector mb-3');
echo html_writer::tag('label', get_string('records_per_page', 'block_report_customcajasan') . ':', array('for' => 'perpage', 'class' => 'mr-2'));
$perpageoptions = array(
    '20' => '20',
    '50' => '50',
    '100' => '100',
    '200' => '200',
    '500' => '500',
    '1000' => '1000',
    '0' => get_string('all_records', 'block_report_customcajasan')
);
echo html_writer::select($perpageoptions, 'perpage', $perpage, false, array('class' => 'form-control d-inline w-auto', 'id' => 'perpage'));
echo html_writer::end_div();

// Download options - This stays static since downloads need a page refresh
echo html_writer::start_div('download-options mt-3');
echo html_writer::start_tag('form', array('id' => 'downloadForm', 'method' => 'get'));

// Hidden fields to preserve filters y contexto
if ($incourse) {
    echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'coursecontext', 'value' => '1'));
}
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'value' => $categoryid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'idnumber', 'value' => $idnumber));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'firstname', 'value' => $firstname));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'lastname', 'value' => $lastname));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'estado', 'value' => $estado));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'startdate', 'value' => $startdate));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'enddate', 'value' => $enddate));

// Añadir mensaje de aviso para descargas grandes
if ($filter_selected) {
    $filters_for_count = array(
        'category' => $categoryid,
        'course' => $courseid,
        'idnumber' => $idnumber,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'estado' => $estado,
        'startdate' => !empty($startdate) ? strtotime($startdate) : '',
        'enddate' => !empty($enddate) ? strtotime($enddate . ' 23:59:59') : ''
    );
    
    $total_records = report_customcajasan_count_data($filters_for_count);
    
    if ($total_records > 1000) {
        echo html_writer::tag('div', 
            '<i class="fa fa-info-circle"></i> ' . 
            'La descarga de ' . $total_records . ' registros puede tardar varios minutos. Por favor, espere a que el proceso termine.',
            array('class' => 'alert alert-info download-warning mb-3 p-2')
        );
    }
}

// Format selection
echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('option_download_format', 'block_report_customcajasan') . ':', array('for' => 'format', 'class' => 'mr-2'));
$formatoptions = array(
    'excel' => get_string('option_download_excel', 'block_report_customcajasan'),
    'ods' => get_string('option_download_ods', 'block_report_customcajasan'),
    'csv' => get_string('option_download_csv', 'block_report_customcajasan')
);
echo html_writer::select($formatoptions, 'format', $format, false, array('class' => 'form-control d-inline w-auto'));

// Download button
echo '<button type="submit" name="download" value="1" class="btn btn-primary ml-2">';
echo get_string('btn_download', 'block_report_customcajasan');
echo '</button>';

echo html_writer::end_div(); // End form-group
echo html_writer::end_tag('form');
echo html_writer::end_div(); // End download-options

echo $OUTPUT->footer();