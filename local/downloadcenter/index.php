<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Download center plugin entry point.
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/download_form.php');
require_once(__DIR__ . '/category_select_form.php');
require_once(__DIR__ . '/course_select_form.php');
require_once($CFG->libdir . '/adminlib.php');

core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$catid = optional_param('catid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$viewmode = optional_param('view', 'default', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

$selection = $SESSION->local_downloadcenter_selection ?? [];

// Manejo de acciones
if ($action === 'clear') {
    unset($SESSION->local_downloadcenter_selection);
    redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
}

if ($action === 'download') {
    if (empty($selection)) {
        redirect(new moodle_url('/local/downloadcenter/index.php'), 
                get_string('nocoursesselected', 'local_downloadcenter'), 
                null, 
                \core\output\notification::NOTIFY_WARNING);
    }

    // Validar acceso a cursos seleccionados
    $downloadcourses = [];
    $errors = [];
    
    foreach ($selection as $cid => $data) {
        try {
            $course = $DB->get_record('course', ['id' => $cid], '*', MUST_EXIST);
            $coursecontext = context_course::instance($course->id);
            
            // Verificar acceso al curso
            if (!can_access_course($course)) {
                $errors[] = get_string('noaccesstocourse', 'local_downloadcenter', $course->fullname);
                continue;
            }
            
            $downloadcourses[$cid] = [$course, $data];
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $SESSION->local_downloadcenter_errors = $errors;
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    }

    // Limpiar selección y cerrar sesión
    unset($SESSION->local_downloadcenter_selection);
    \core\session\manager::write_close();

    // Generar archivo ZIP
    $filename = sprintf('courses_%s.zip', userdate(time(), '%Y%m%d_%H%M'));
    
    try {
        $zipwriter = \core_files\archive_writer::get_stream_writer($filename, \core_files\archive_writer::ZIP_WRITER);

        foreach ($downloadcourses as $cid => [$course, $data]) {
            $downloadcenter = new local_downloadcenter_factory($course, $USER);
            
            if (!empty($data['downloadall'])) {
                $downloadcenter->select_all();
            } else {
                $downloadcenter->parse_form_data((object)$data);
            }
            
            $prefix = local_downloadcenter_factory::shorten_filename(clean_filename($course->shortname)) . '/';
            $filelist = $downloadcenter->build_filelist($prefix);
            
            foreach ($filelist as $pathinzip => $file) {
                if ($file instanceof \stored_file) {
                    $zipwriter->add_file_from_stored_file($pathinzip, $file);
                } else if (is_array($file)) {
                    $content = reset($file);
                    $zipwriter->add_file_from_string($pathinzip, $content);
                } else if (is_string($file)) {
                    $zipwriter->add_file_from_filepath($pathinzip, $file);
                }
            }
            
            // Registrar evento de descarga
            $event = \local_downloadcenter\event\zip_downloaded::create([
                'objectid' => $course->id,
                'context' => context_course::instance($course->id)
            ]);
            $event->trigger();
        }

        $zipwriter->finish();
        exit;
    } catch (Exception $e) {
        debugging('Error creating ZIP: ' . $e->getMessage(), DEBUG_DEVELOPER);
        redirect(new moodle_url('/local/downloadcenter/index.php'), 
                get_string('errorcreatinzip', 'local_downloadcenter'), 
                null, 
                \core\output\notification::NOTIFY_ERROR);
    }
}

// Configurar página principal
if (empty($catid) && empty($courseid)) {
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'));

    $catform = new local_downloadcenter_category_select_form();
    
    if ($data = $catform->get_data()) {
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $data->catid]));
    }

    echo $OUTPUT->header();
    
    // Mostrar barra de acciones similar a management.php
    echo html_writer::start_div('downloadcenter-header mb-3');
    echo $OUTPUT->heading(get_string('navigationlink', 'local_downloadcenter'), 2);
    
    // Información de ayuda
    echo $OUTPUT->notification(
        get_string('downloadcenter_help', 'local_downloadcenter'),
        \core\output\notification::NOTIFY_INFO
    );
    echo html_writer::end_div();
    
    // Mostrar errores si existen
    if (!empty($SESSION->local_downloadcenter_errors)) {
        foreach ($SESSION->local_downloadcenter_errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        unset($SESSION->local_downloadcenter_errors);
    }
    
    $catform->display();
    echo $OUTPUT->footer();
    exit;
}

// Manejo de vista por curso específico
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    
    // Verificar acceso al curso
    if (!can_access_course($course)) {
        print_error('noaccesstocourse', 'local_downloadcenter', 
                   new moodle_url('/local/downloadcenter/index.php'), 
                   $course->fullname);
    }

    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', 
                                  ['catid' => $catid, 'courseid' => $courseid]));
    $PAGE->set_context($coursecontext);
    $PAGE->set_pagelayout('incourse');

    $downloadcenter = new local_downloadcenter_factory($course, $USER);
    $userresources = $downloadcenter->get_resources_for_user();
    
    // Cargar JavaScript para filtros
    $PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init', 
                                 $downloadcenter->get_js_modnames());

    $downloadform = new local_downloadcenter_download_form(
        null, 
        ['res' => $userresources], 
        'post', 
        '', 
        ['data-double-submit-protection' => 'off']
    );

    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter') . ': ' . $course->fullname);
    $PAGE->set_heading($course->fullname);
    
    // Agregar navegación
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'), 
                      new moodle_url('/local/downloadcenter/index.php'));
    $PAGE->navbar->add($course->fullname);

    if ($data = $downloadform->get_data()) {
        $downloadcenter->parse_form_data($data);
        $selection[$courseid] = (array)$data;
        $SESSION->local_downloadcenter_selection = $selection;
        
        // Registrar evento de visualización
        $event = \local_downloadcenter\event\plugin_viewed::create([
            'objectid' => $course->id,
            'context' => $coursecontext
        ]);
        $event->trigger();
        
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]),
                get_string('filesadded', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } else if ($downloadform->is_cancelled()) {
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    } else {
        echo $OUTPUT->header();
        
        // Barra de herramientas
        echo html_writer::start_div('downloadcenter-toolbar mb-3');
        echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), 
                             ['class' => 'h3']);
        
        // Botón de volver
        echo html_writer::link(
            new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]),
            get_string('back'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_div();
        
        $downloadform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

// Vista de categoría
if ($catid) {
    $category = \core_course_category::get($catid, MUST_EXIST);
    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    
    // Filtrar cursos por búsqueda si existe
    if (!empty($search)) {
        $courses = array_filter($courses, function($course) use ($search) {
            return stripos($course->fullname, $search) !== false || 
                   stripos($course->shortname, $search) !== false;
        });
    }

    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);
    
    // Navegación
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'), 
                      new moodle_url('/local/downloadcenter/index.php'));
    $PAGE->navbar->add($category->get_formatted_name());

    $courseform = new local_downloadcenter_course_select_form(null, [
        'courses' => $courses,
        'selection' => $selection,
        'catid' => $catid
    ]);
    
    if ($data = $courseform->get_data()) {
        if (!empty($data->courses)) {
            foreach ($data->courses as $cid => $sel) {
                if ($sel) {
                    $selection[$cid] = ['downloadall' => 1];
                }
            }
            $SESSION->local_downloadcenter_selection = $selection;
        }
        redirect(new moodle_url('/local/downloadcenter/index.php', ['catid' => $catid]));
    }

    echo $OUTPUT->header();
    
    // Encabezado mejorado
    echo html_writer::start_div('downloadcenter-header card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), 
                         ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('downloadcenter_desc', 'local_downloadcenter'), 
                         ['class' => 'text-muted']);
    
    // Barra de búsqueda
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $PAGE->url,
        'class' => 'form-inline mb-3'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'catid',
        'value' => $catid
    ]);
    echo html_writer::start_div('input-group');
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'search',
        'class' => 'form-control',
        'placeholder' => get_string('searchcourses'),
        'value' => $search
    ]);
    echo html_writer::start_div('input-group-append');
    echo html_writer::tag('button', get_string('search'), [
        'type' => 'submit',
        'class' => 'btn btn-primary'
    ]);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
    
    echo html_writer::end_div();
    echo html_writer::end_div();
    
    // Mostrar formulario de cursos
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h3', $category->get_formatted_name(), ['class' => 'card-title h4']);
    
    if (!empty($courses)) {
        $courseform->display();
    } else {
        echo $OUTPUT->notification(get_string('nocoursesfound', 'local_downloadcenter'), 
                                  \core\output\notification::NOTIFY_WARNING);
    }
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Panel de selección actual
    if (!empty($selection)) {
        echo html_writer::start_div('card mt-4 border-success');
        echo html_writer::start_div('card-header bg-success text-white');
        echo html_writer::tag('h4', get_string('currentselection', 'local_downloadcenter'), 
                             ['class' => 'mb-0']);
        echo html_writer::end_div();
        echo html_writer::start_div('card-body');
        
        // Listar cursos seleccionados
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        foreach ($selection as $cid => $data) {
            $selectedcourse = $DB->get_record('course', ['id' => $cid]);
            if ($selectedcourse) {
                echo html_writer::tag('li', 
                    html_writer::tag('strong', $selectedcourse->fullname) . 
                    ' (' . $selectedcourse->shortname . ')',
                    ['class' => 'list-group-item']
                );
            }
        }
        echo html_writer::end_tag('ul');
        
        // Botones de acción
        echo html_writer::start_div('btn-group');
        
        $downloadurl = new moodle_url('/local/downloadcenter/index.php', ['action' => 'download']);
        echo html_writer::link($downloadurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-download mr-2']) . 
            get_string('downloadselection', 'local_downloadcenter'), 
            ['class' => 'btn btn-success']
        );
        
        $clearurl = new moodle_url('/local/downloadcenter/index.php', 
                                   ['action' => 'clear', 'catid' => $catid]);
        echo html_writer::link($clearurl, 
            html_writer::tag('i', '', ['class' => 'fa fa-times mr-2']) . 
            get_string('clearselection', 'local_downloadcenter'), 
            ['class' => 'btn btn-outline-danger']
        );
        
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo $OUTPUT->footer();
}