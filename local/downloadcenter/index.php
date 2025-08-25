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
require_once(__DIR__ . '/course_select_form.php');
require_once($CFG->libdir . '/adminlib.php');

core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

$catids = optional_param_array('catids', null, PARAM_INT);
if ($catids === null) {
    $catidsparam = optional_param('catids', '', PARAM_SEQUENCE);
    $catids = $catidsparam === '' ? [] : array_map('intval', explode(',', $catidsparam));
}
$catid = optional_param('catid', 0, PARAM_INT);
if ($catid && empty($catids)) {
    $catids = [$catid];
}
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$viewmode = optional_param('view', 'default', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);

if (empty($catids)) {
    $catids = array_map(function($cat) {
        return $cat->id;
    }, \core_course_category::top()->get_children());
}

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

// Load current selection either from session or persistent user preference.
$selection = $SESSION->local_downloadcenter_selection ?? null;
if ($selection === null) {
    $pref = get_user_preferences('local_downloadcenter_selection', '');
    $selection = $pref === '' ? [] : json_decode($pref, true);
    if (!is_array($selection)) {
        $selection = [];
    }
    $SESSION->local_downloadcenter_selection = $selection;
}

if ($action === 'togglecourse') {
    require_sesskey();
    $cid = required_param('courseid', PARAM_INT);
    $checked = optional_param('checked', 0, PARAM_BOOL);
    $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
    if ($checked) {
        $sessionselection[$cid] = ['downloadall' => 1];
    } else {
        unset($sessionselection[$cid]);
    }
    $SESSION->local_downloadcenter_selection = $sessionselection;
    set_user_preference('local_downloadcenter_selection', json_encode($sessionselection));
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

if ($action === 'savecourse' && $courseid) {
    require_sesskey();
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    if (!can_access_course($course)) {
        throw new moodle_exception('noaccesstocourse', 'local_downloadcenter', '', $course->fullname);
    }
    $downloadcenter = new local_downloadcenter_factory($course, $USER);
    $userresources = $downloadcenter->get_resources_for_user();
    $downloadform = new local_downloadcenter_download_form(null, ['res' => $userresources]);
    $data = data_submitted() ?? new stdClass();
    unset($data->action, $data->sesskey);
    $downloadcenter->parse_form_data($data);
    $selection[$courseid] = (array)$data;
    $SESSION->local_downloadcenter_selection = $selection;
    set_user_preference('local_downloadcenter_selection', json_encode($selection));
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok']);
    exit;
}

// Manejo de acciones
if ($action === 'clear') {
    require_sesskey();
    unset($SESSION->local_downloadcenter_selection);
    unset_user_preference('local_downloadcenter_selection');
    redirect(local_downloadcenter_build_url($catids));
}

if ($action === 'download') {
    require_sesskey();
    if (empty($selection)) {
        redirect(local_downloadcenter_build_url([]),
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
        redirect(local_downloadcenter_build_url($catids));
    }

    // Limpiar selección y cerrar sesión
    unset($SESSION->local_downloadcenter_selection);
    unset_user_preference('local_downloadcenter_selection');
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
        redirect(local_downloadcenter_build_url([]),
                get_string('errorcreatinzip', 'local_downloadcenter'), 
                null, 
                \core\output\notification::NOTIFY_ERROR);
    }
}

// Manejo de vista por curso específico
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    
    // Verificar acceso al curso
    if (!can_access_course($course)) {
        print_error('noaccesstocourse', 'local_downloadcenter',
                   local_downloadcenter_build_url([]),
                   $course->fullname);
    }

    $PAGE->set_url(local_downloadcenter_build_url($catids, ['courseid' => $courseid]));
    $PAGE->set_context($coursecontext);
    $PAGE->set_pagelayout('incourse');

    $downloadcenter = new local_downloadcenter_factory($course, $USER);
    $userresources = $downloadcenter->get_resources_for_user();
    $PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init',
                                 $downloadcenter->get_js_modnames());
    $PAGE->requires->js_call_amd('local_downloadcenter/section_tree', 'init');

    $courseselection = $selection[$courseid] ?? [];
    $downloadform = new local_downloadcenter_download_form(
        null,
        ['res' => $userresources, 'selection' => $courseselection],
        'post',
        '',
        ['data-double-submit-protection' => 'off']
    );

    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter') . ': ' . $course->fullname);
    $PAGE->set_heading($course->fullname);
    
    // Agregar navegación
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'),
                      local_downloadcenter_build_url($catids));
    $PAGE->navbar->add($course->fullname);

    if ($data = $downloadform->get_data()) {
        $downloadcenter->parse_form_data($data);
        $selection[$courseid] = (array)$data;
        $SESSION->local_downloadcenter_selection = $selection;
        set_user_preference('local_downloadcenter_selection', json_encode($selection));

        // Registrar evento de visualización
        $event = \local_downloadcenter\event\plugin_viewed::create([
            'objectid' => $course->id,
            'context' => $coursecontext
        ]);
        $event->trigger();
        
        redirect(local_downloadcenter_build_url($catids),
                get_string('filesadded', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } else if ($downloadform->is_cancelled()) {
        redirect(local_downloadcenter_build_url($catids));
    } else {
        echo $OUTPUT->header();
        
        // Barra de herramientas
        echo html_writer::start_div('downloadcenter-toolbar mb-3');
        echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), 
                             ['class' => 'h3']);
        
        // Botón de volver
        echo html_writer::link(
            local_downloadcenter_build_url($catids),
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
 * @param array $catids Top-level selected category ids.
 * @param string $search Current search filter.
 * @return string HTML output.
 */
function local_downloadcenter_render_category_tree(\core_course_category $category, array $selection,
        array $catids, string $search): string {
    global $SESSION, $OUTPUT;

    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    if ($search !== '') {
        $courses = array_filter($courses, function($course) use ($search) {
            return stripos($course->fullname, $search) !== false ||
                   stripos($course->shortname, $search) !== false;
        });
    }

    // Determine category checkbox state based on selected courses.
    $allcourseids = array_keys($category->get_courses(['recursive' => true]));
    $selectedcourseids = array_intersect($allcourseids, array_keys($selection));
    $catchecked = !empty($allcourseids) && count($selectedcourseids) === count($allcourseids);
    $catindeterminate = !empty($selectedcourseids) && !$catchecked;

    $courseform = new local_downloadcenter_course_select_form(null, [
        'courses' => $courses,
        'selection' => $selection,
        'catids' => $catids,
        'categoryid' => $category->id,
    ]);

    if ($data = $courseform->get_data()) {
        if ((int)$data->categoryid === (int)$category->id) {
            $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
            $selectedcourseids = [];
            if (!empty($data->courses)) {
                foreach ($data->courses as $courseid => $sel) {
                    if ($sel) {
                        $sessionselection[$courseid] = ['downloadall' => 1];
                        $selectedcourseids[] = $courseid;
                    }
                }
            }
            foreach ($courses as $course) {
                if (!in_array($course->id, $selectedcourseids)) {
                    unset($sessionselection[$course->id]);
                }
            }
            $SESSION->local_downloadcenter_selection = $sessionselection;
            set_user_preference('local_downloadcenter_selection', json_encode($sessionselection));
            redirect(local_downloadcenter_build_url($catids));
        }
    }
    if ($courseform->is_cancelled() && optional_param('categoryid', 0, PARAM_INT) == $category->id) {
        redirect(local_downloadcenter_build_url($catids));
    }

    ob_start();
    if (!empty($courses)) {
        $courseform->display();
    } else {
        echo $OUTPUT->notification(get_string('nocoursesfound', 'local_downloadcenter'),
            \core\output\notification::NOTIFY_WARNING);
    }
    $innerhtml = ob_get_clean();

    foreach ($category->get_children(['sort' => ['name' => 1]]) as $child) {
        $innerhtml .= local_downloadcenter_render_category_tree($child, $selection, $catids, $search);
    }

    $collapseid = 'cat' . $category->id;
    $checkboxattrs = [
        'type' => 'checkbox',
        'class' => 'downloadcenter-category-checkbox mr-2',
        'data-categoryid' => $category->id,
    ];
    if ($catchecked) {
        $checkboxattrs['checked'] = 'checked';
    }
    if ($catindeterminate) {
        $checkboxattrs['data-indeterminate'] = 1;
    }

    $expanded = $catchecked || $catindeterminate;
    $button = html_writer::tag('button', $category->get_formatted_name(), [
        'class' => 'btn btn-link text-left w-100',
        'data-toggle' => 'collapse',
        'data-target' => '#' . $collapseid,
        'aria-expanded' => $expanded ? 'true' : 'false',
        'aria-controls' => $collapseid,
    ]);
    $header = html_writer::empty_tag('input', $checkboxattrs) . $button;
    $html = html_writer::start_div('card mb-2 downloadcenter-category');
    $html .= html_writer::tag('div', $header, ['class' => 'card-header p-0 d-flex align-items-center']);
    $html .= html_writer::start_div('collapse' . ($expanded ? ' show' : ''), ['id' => $collapseid]);
    $html .= html_writer::div($innerhtml, 'card-body');
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();
    return $html;
}

// Vista de categorías seleccionadas
if (!empty($catids)) {
    $PAGE->set_url(local_downloadcenter_build_url($catids));
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);

    // Navegación
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'),
                      local_downloadcenter_build_url($catids));

    // JavaScript for category checkbox tree.
    $PAGE->requires->js_call_amd('local_downloadcenter/category_tree', 'init');

    echo $OUTPUT->header();

    // Encabezado
    echo html_writer::start_div('downloadcenter-header card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('downloadcenter_desc', 'local_downloadcenter'), ['class' => 'text-muted']);

    // Barra de búsqueda
    echo html_writer::start_tag('form', [
        'method' => 'get',
        'action' => $PAGE->url,
        'class' => 'form-inline mb-3'
    ]);
    foreach ($catids as $cid) {
        echo html_writer::empty_tag('input', [
            'type' => 'hidden',
            'name' => 'catids[]',
            'value' => $cid
        ]);
    }
    echo html_writer::start_div('input-group');
    echo html_writer::empty_tag('input', [
        'type' => 'text',
        'name' => 'search',
        'class' => 'form-control',
        'placeholder' => get_string('searchcourses', 'local_downloadcenter'),
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

    // Mostrar errores si existen.
    if (!empty($SESSION->local_downloadcenter_errors)) {
        foreach ($SESSION->local_downloadcenter_errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        unset($SESSION->local_downloadcenter_errors);
    }

    // Mostrar categorías seleccionadas en forma colapsable
    foreach ($catids as $cid) {
        $category = \core_course_category::get($cid, MUST_EXIST);
        echo local_downloadcenter_render_category_tree($category, $selection, $catids, $search);
    }

    // Panel de selección actual
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
                echo html_writer::tag('li',
                    html_writer::tag('strong', $selectedcourse->fullname) .
                    ' (' . $selectedcourse->shortname . ')',
                    ['class' => 'list-group-item']
                );
            }
        }
        echo html_writer::end_tag('ul');

        // Botones de acción.
        echo html_writer::start_div('btn-group');

        $downloadbutton = new single_button(
            local_downloadcenter_build_url([], ['action' => 'download']),
            get_string('downloadselection', 'local_downloadcenter'),
            'post',
            single_button::BUTTON_SUCCESS
        );
        $downloadbutton->class .= ' mr-2';
        echo $OUTPUT->render($downloadbutton);

        $clearbutton = new single_button(
            local_downloadcenter_build_url($catids, ['action' => 'clear']),
            get_string('clearselection', 'local_downloadcenter'),
            'post',
            single_button::BUTTON_DANGER
        );
        echo $OUTPUT->render($clearbutton);

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo $OUTPUT->footer();
}