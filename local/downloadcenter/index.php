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
require_once($CFG->libdir . '/adminlib.php');

core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$parentid = optional_param('parent', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

$selection = $SESSION->local_downloadcenter_selection ?? [];

// Manejo de acciones
if ($action === 'clear') {
    unset($SESSION->local_downloadcenter_selection);
    redirect(local_downloadcenter_build_url($parentid));
}

if ($action === 'download') {
    if (empty($selection)) {
        redirect(local_downloadcenter_build_url(),
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
        redirect(local_downloadcenter_build_url($parentid));
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
            
            $categorypath = local_downloadcenter_category_path($course);
            $prefix = ($categorypath ? $categorypath . '/' : '') .
                local_downloadcenter_factory::shorten_filename(clean_filename($course->shortname)) . '/';
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
        redirect(local_downloadcenter_build_url(),
                get_string('errorcreatinzip', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_ERROR);
    }
}

// Configurar página principal
if ($courseid == 0 && $parentid == 0) {
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);
    $PAGE->navbar->add(get_string('administrationsite'), new moodle_url('/admin/search.php'));
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'));

    echo $OUTPUT->header();

    echo html_writer::start_div('downloadcenter-header mb-3');
    echo $OUTPUT->heading(get_string('navigationlink', 'local_downloadcenter'), 2);
    echo $OUTPUT->notification(
        get_string('downloadcenter_help', 'local_downloadcenter'),
        \core\output\notification::NOTIFY_INFO
    );
    echo html_writer::end_div();

    if (!empty($SESSION->local_downloadcenter_errors)) {
        foreach ($SESSION->local_downloadcenter_errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        unset($SESSION->local_downloadcenter_errors);
    }

    $top = \core_course_category::top();
    echo html_writer::start_tag('ul', ['class' => 'list-group']);
    foreach ($top->get_children(['sort' => ['name' => 1]]) as $cat) {
        $url = local_downloadcenter_build_url($cat->id);
        $name = $cat->get_formatted_name();
        echo html_writer::tag('li', html_writer::link($url, $name), ['class' => 'list-group-item']);
    }
    echo html_writer::end_tag('ul');

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
                   local_downloadcenter_build_url(),
                   $course->fullname);
    }

    // Ensure the course appears in the current selection by default.
    $selection = $SESSION->local_downloadcenter_selection ?? [];
    if (!isset($selection[$courseid])) {
        $selection[$courseid] = ['downloadall' => 1];
        $SESSION->local_downloadcenter_selection = $selection;
    }

    $PAGE->set_url(local_downloadcenter_build_url($parentid, ['courseid' => $courseid]));
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
                      local_downloadcenter_build_url($parentid));
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
        
        redirect(local_downloadcenter_build_url($parentid),
                get_string('filesadded', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } else if ($downloadform->is_cancelled()) {
        redirect(local_downloadcenter_build_url($parentid));
    } else {
        echo $OUTPUT->header();
        
        // Barra de herramientas
        echo html_writer::start_div('downloadcenter-toolbar mb-3');
        echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), 
                             ['class' => 'h3']);
        
        // Botón de volver
        echo html_writer::link(
            local_downloadcenter_build_url($parentid),
            get_string('back'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_div();
        
        $downloadform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

/**
 * Render a collapsible section for the given category including any child
 * categories and their courses.
 *
 * @param \core_course_category $category Category to render.
 * @param array $selection Current course selection.
 * @param string $search Current search filter.
 * @return string HTML output.
 */
function local_downloadcenter_render_category_tree(\core_course_category $category, array $selection,
        string $search): string {
    global $OUTPUT;

    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    if ($search !== '') {
        $courses = array_filter($courses, function($course) use ($search) {
            return stripos($course->fullname, $search) !== false ||
                   stripos($course->shortname, $search) !== false;
        });
    }

    $innerhtml = '';
    if (!empty($courses)) {
        foreach ($courses as $course) {
            if (!$course->can_access()) {
                continue;
            }
            $url = local_downloadcenter_build_url($category->id, ['courseid' => $course->id]);
            $label = html_writer::link($url, $course->get_formatted_name());
            $checked = isset($selection[$course->id]);
            if ($checked) {
                $label .= ' (' . get_string('selected', 'local_downloadcenter') . ')';
            }
            $checkboxid = 'course' . $course->id;
            $innerhtml .= html_writer::start_div('form-check');
            $innerhtml .= html_writer::checkbox('courses[' . $course->id . ']', 1, $checked, '', [
                'id' => $checkboxid,
                'class' => 'form-check-input'
            ]);
            $innerhtml .= html_writer::tag('label', $label, [
                'for' => $checkboxid,
                'class' => 'form-check-label'
            ]);
            $innerhtml .= html_writer::end_div();
        }
    } else {
        $innerhtml .= $OUTPUT->notification(get_string('nocoursesfound', 'local_downloadcenter'),
            \core\output\notification::NOTIFY_WARNING);
    }

    foreach ($category->get_children(['sort' => ['name' => 1]]) as $child) {
        $innerhtml .= local_downloadcenter_render_category_tree($child, $selection, $search);
    }

    $collapseid = 'cat' . $category->id;
    $html = html_writer::start_div('card mb-2');
    $html .= html_writer::tag('div',
        html_writer::tag('button', $category->get_formatted_name(), [
            'class' => 'btn btn-link text-left w-100',
            'data-toggle' => 'collapse',
            'data-target' => '#' . $collapseid,
            'aria-expanded' => 'false',
            'aria-controls' => $collapseid,
        ]),
        ['class' => 'card-header p-0']
    );
    $html .= html_writer::start_div('collapse', ['id' => $collapseid]);
    $html .= html_writer::div($innerhtml, 'card-body');
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    return $html;
}

// Vista de categorías
if ($parentid > 0) {
    $PAGE->set_url(local_downloadcenter_build_url($parentid));
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);

    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'),
                      local_downloadcenter_build_url());

    $category = \core_course_category::get($parentid, MUST_EXIST);

    if (optional_param('addcourses', null, PARAM_RAW) !== null) {
        require_sesskey();
        $posted = optional_param_array('courses', [], PARAM_BOOL);
        $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
        foreach ($posted as $courseid => $sel) {
            if ($sel) {
                $sessionselection[$courseid] = ['downloadall' => 1];
            }
        }
        $SESSION->local_downloadcenter_selection = $sessionselection;
        redirect(local_downloadcenter_build_url($parentid));
    }

    echo $OUTPUT->header();

    echo html_writer::start_div('downloadcenter-header card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h2', $category->get_formatted_name(), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('downloadcenter_desc', 'local_downloadcenter'), ['class' => 'text-muted']);
    echo html_writer::end_div();
    echo html_writer::end_div();

    $backurl = $category->parent ? local_downloadcenter_build_url($category->parent) : local_downloadcenter_build_url();
    echo html_writer::link($backurl, get_string('back'), ['class' => 'btn btn-secondary mb-3']);

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url,
        'class' => 'mb-4'
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'sesskey',
        'value' => sesskey()
    ]);
    echo html_writer::empty_tag('input', [
        'type' => 'hidden',
        'name' => 'parent',
        'value' => $parentid
    ]);

    echo local_downloadcenter_render_category_tree($category, $selection, $search);

    echo html_writer::tag('button',
        get_string('addcoursestoselection', 'local_downloadcenter'), [
            'type' => 'submit',
            'name' => 'addcourses',
            'class' => 'btn btn-primary mt-3'
        ]);
    echo html_writer::end_tag('form');

    if (!empty($selection)) {
        echo html_writer::start_div('card mt-4 border-success');
        echo html_writer::start_div('card-header bg-success text-white');
        echo html_writer::tag('h4', get_string('currentselection', 'local_downloadcenter'), ['class' => 'mb-0']);
        echo html_writer::end_div();
        echo html_writer::start_div('card-body');

        // Listar cursos seleccionados
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        foreach ($selection as $cid => $data) {
            $selectedcourse = $DB->get_record('course', ['id' => $cid]);
            if ($selectedcourse) {
                $cat = \core_course_category::get($selectedcourse->category, IGNORE_MISSING);
                $catname = $cat ? $cat->get_nested_name(false) : '';
                $label = '';
                if ($catname !== '') {
                    $label .= html_writer::span($catname, 'text-muted') . ' - ';
                }
                $label .= html_writer::tag('strong', $selectedcourse->fullname) .
                    ' (' . $selectedcourse->shortname . ')';
                echo html_writer::tag('li', $label, ['class' => 'list-group-item']);
            }
        }
        echo html_writer::end_tag('ul');

        // Botones de acción
        echo html_writer::start_div('btn-group');

        $downloadurl = local_downloadcenter_build_url(0, ['action' => 'download']);
        echo html_writer::link($downloadurl,
            html_writer::tag('i', '', ['class' => 'fa fa-download mr-2']) .
            get_string('downloadselection', 'local_downloadcenter'),
            ['class' => 'btn btn-success']
        );

        $clearurl = local_downloadcenter_build_url($parentid, ['action' => 'clear']);
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