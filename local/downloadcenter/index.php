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
 * Download center main page
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/locallib.php');

// Get parameters.
$courseid = optional_param('courseid', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$mode = optional_param('mode', '', PARAM_ALPHA);

// Determine mode.
$systemcontext = context_system::instance();
$isadmin = has_capability('moodle/site:config', $systemcontext);

// If no mode specified, determine based on parameters.
if (empty($mode)) {
    if ($courseid > 0) {
        $mode = 'course';
    } else if ($isadmin) {
        $mode = 'admin';
    } else {
        $mode = 'course';
    }
}

// Initialize session for selection storage.
if (!isset($SESSION->downloadcenter_selection)) {
    $SESSION->downloadcenter_selection = [];
}
if (!isset($SESSION->downloadcenter_options)) {
    $SESSION->downloadcenter_options = [
        'excludestudent' => get_config('local_downloadcenter', 'excludestudentdefault') ?? 0,
        'filesrealnames' => 0,
        'addnumbering' => 0
    ];
}

// COURSE MODE - Original functionality.
if ($mode === 'course' && $courseid > 0) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    require_course_login($course);
    
    $context = context_course::instance($course->id);
    require_capability('local/downloadcenter:view', $context);
    
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['courseid' => $course->id]));
    $PAGE->set_pagelayout('incourse');
    $PAGE->add_body_class('limitedwidth');
    
    // Load necessary files.
    require_once(__DIR__ . '/classes/factory.php');
    require_once(__DIR__ . '/classes/forms/download_form.php');
    
    // Create factory instance.
    $downloadcenter = new \local_downloadcenter\factory($course, $USER);
    
    // Get resources for user.
    $userresources = $downloadcenter->get_resources_for_user();
    
    // Setup JavaScript.
    $PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init', $downloadcenter->get_js_modnames());
    $PAGE->requires->js_call_amd('local_downloadcenter/search', 'init');
    
    // Create form.
    $downloadform = new \local_downloadcenter\forms\download_form(null, [
        'res' => $userresources,
        'mode' => 'course'
    ], 'post', '', ['data-double-submit-protection' => 'off']);
    
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter') . ': ' . $course->fullname);
    $PAGE->set_heading($course->fullname);
    
    // Handle form submission.
    if ($data = $downloadform->get_data()) {
        // Trigger download event.
        $event = \local_downloadcenter\event\zip_downloaded::create([
            'objectid' => $course->id,
            'context' => $context,
        ]);
        $event->add_record_snapshot('course', $course);
        $event->trigger();
        
        // Process download.
        $downloadcenter->parse_form_data($data);
        $downloadcenter->create_zip();
        exit;
        
    } else if ($downloadform->is_cancelled()) {
        redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
        
    } else {
        // Trigger view event.
        $event = \local_downloadcenter\event\plugin_viewed::create([
            'objectid' => $course->id,
            'context' => $context,
        ]);
        $event->add_record_snapshot('course', $course);
        $event->trigger();
        
        // Display form.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('navigationlink', 'local_downloadcenter'));
        $downloadform->display();
        echo $OUTPUT->footer();
    }
    exit;
}

// ADMIN MODE - Multi-course functionality.
if ($mode === 'admin' && $isadmin) {
    require_login();
    require_capability('moodle/site:config', $systemcontext);
    require_capability('local/downloadcenter:downloadmultiple', $systemcontext);

    require_once(__DIR__ . '/classes/admin_manager.php');
    require_once(__DIR__ . '/classes/factory.php');
    require_once(__DIR__ . '/classes/selection_manager.php');
    require_once(__DIR__ . '/classes/output/admin_tree_renderer.php');

    $PAGE->set_context($systemcontext);
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']));
    $PAGE->set_pagelayout('admin');
    $PAGE->add_body_class('path-local-downloadcenter-admin');

    $allowrestrictedcourses = has_capability('local/downloadcenter:downloadmultiple', $systemcontext);

    $selectionmanager = new \local_downloadcenter\selection_manager($USER->id);
    $storedoptions = $selectionmanager->get_download_options();
    $defaultoptions = [
        'excludestudent' => (int)(get_config('local_downloadcenter', 'excludestudentdefault') ?? 0),
        'filesrealnames' => 0,
        'addnumbering' => 0,
    ];
    $downloadoptions = array_merge($defaultoptions, $storedoptions);
    $downloadoptions['excludestudent'] = optional_param('excludestudent', $downloadoptions['excludestudent'], PARAM_BOOL);
    $downloadoptions['filesrealnames'] = optional_param('filesrealnames', $downloadoptions['filesrealnames'], PARAM_BOOL);
    $downloadoptions['addnumbering'] = optional_param('addnumbering', $downloadoptions['addnumbering'], PARAM_BOOL);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $selectionmanager->set_download_options($downloadoptions);
    }

    $rawcoursedata = [];
    try {
        $rawcoursedata = optional_param_array('coursedata', null, PARAM_RAW);
    } catch (\coding_exception $exception) {
        if (strpos($exception->getMessage(), 'clean() can not process arrays') === false) {
            throw $exception;
        }
        $rawcoursedata = null;
    }

    if ($rawcoursedata === null) {
        if (isset($_POST['coursedata'])) {
            $rawcoursedata = $_POST['coursedata'];
        } else if (isset($_GET['coursedata'])) {
            $rawcoursedata = $_GET['coursedata'];
        }
    }

    if (is_array($rawcoursedata)) {
        $coursedata = clean_param_array($rawcoursedata, PARAM_RAW, true);
    } else {
        $coursedata = [];
    }

    if ($action === 'clearselection') {
        require_sesskey();
        $selectionmanager->clear_selection();
        redirect($PAGE->url, get_string('selectioncleared', 'local_downloadcenter'),
            null, \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'download') {
        require_sesskey();

        if (empty($coursedata)) {
            $coursedata = $selectionmanager->get_course_selections();
        }

        $courseselections = local_downloadcenter_prepare_admin_selections(
            $coursedata,
            $downloadoptions,
            $allowrestrictedcourses
        );

        if (empty($courseselections)) {
            redirect($PAGE->url, get_string('noselectederror', 'local_downloadcenter'),
                null, \core\output\notification::NOTIFY_WARNING);
        }

        $adminmanager = new \local_downloadcenter\admin_manager();
        $adminmanager->download_multiple_courses($courseselections, $downloadoptions, $allowrestrictedcourses);
        exit;
    }

    $treerenderer = new \local_downloadcenter\output\admin_tree_renderer($selectionmanager, $allowrestrictedcourses);
    $treehtml = $treerenderer->render_root_categories();

    $initialconfig = [
        'sesskey' => sesskey(),
        'options' => $downloadoptions,
        'selection' => $selectionmanager->get_course_selections(),
        'allowRestricted' => $allowrestrictedcourses,
        'services' => [
            'categoryChildren' => 'local_downloadcenter_get_category_children',
            'courseResources' => 'local_downloadcenter_get_course_resources',
            'setSelection' => 'local_downloadcenter_set_course_selection',
            'setOptions' => 'local_downloadcenter_set_download_options',
        ],
        'strings' => [
            'loading' => get_string('loading', 'local_downloadcenter'),
            'nocontent' => get_string('nocontentavailable', 'local_downloadcenter'),
            'selectionlabel' => get_string('currentselection', 'local_downloadcenter'),
        ],
    ];

    $PAGE->set_title(get_string('admindownloadcenter', 'local_downloadcenter'));
    $PAGE->set_heading(get_string('admindownloadcenter', 'local_downloadcenter'));
    $PAGE->requires->css('/local/downloadcenter/styles.css');
    $PAGE->requires->js_call_amd('local_downloadcenter/admin_tree', 'init', $initialconfig);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('admindownloadcenter', 'local_downloadcenter'));

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url->out(false),
        'class' => 'downloadcenter-admin-form',
        'id' => 'downloadcenter-admin-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'download']);

    echo html_writer::start_div('row');

    echo html_writer::start_div('col-lg-8 downloadcenter-tree-column');
    echo html_writer::tag('h3', get_string('selectcourses', 'local_downloadcenter'));
    echo html_writer::tag('p', get_string('adminmultiselectinstructions', 'local_downloadcenter'), ['class' => 'text-muted']);

    if (!empty($treehtml)) {
        echo html_writer::div($treehtml, 'downloadcenter-category-tree', [
            'data-region' => 'downloadcenter-tree',
        ]);
    } else {
        echo html_writer::div(get_string('nocoursesfound', 'local_downloadcenter'), 'alert alert-info');
    }
    echo html_writer::end_div();

    echo html_writer::start_div('col-lg-4 downloadcenter-options-column');
    echo html_writer::tag('h3', get_string('downloadoptions', 'local_downloadcenter'));

    $selectioncount = count($selectionmanager->get_course_selections());
    echo html_writer::div(
        html_writer::span(
            get_string('currentselection', 'local_downloadcenter') . ': ' .
            html_writer::span($selectioncount, 'selection-count badge badge-primary'),
            'font-weight-bold'
        ),
        'downloadcenter-selection-summary mb-3'
    );

    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input downloadcenter-option',
        'data-option' => 'excludestudent',
        'id' => 'excludestudent',
        'name' => 'excludestudent',
        'value' => 1,
        'checked' => !empty($downloadoptions['excludestudent']) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('excludestudentcontent', 'local_downloadcenter'),
        ['class' => 'form-check-label', 'for' => 'excludestudent']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input downloadcenter-option',
        'data-option' => 'filesrealnames',
        'id' => 'filesrealnames',
        'name' => 'filesrealnames',
        'value' => 1,
        'checked' => !empty($downloadoptions['filesrealnames']) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('downloadoptions:filesrealnames', 'local_downloadcenter'),
        ['class' => 'form-check-label', 'for' => 'filesrealnames']);
    echo html_writer::end_div();

    echo html_writer::start_div('form-check mb-3');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input downloadcenter-option',
        'data-option' => 'addnumbering',
        'id' => 'addnumbering',
        'name' => 'addnumbering',
        'value' => 1,
        'checked' => !empty($downloadoptions['addnumbering']) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('downloadoptions:addnumbering', 'local_downloadcenter'),
        ['class' => 'form-check-label', 'for' => 'addnumbering']);
    echo html_writer::end_div();

    echo html_writer::start_div('d-flex flex-column gap-2');
    echo html_writer::tag('button', get_string('downloadselection', 'local_downloadcenter'), [
        'type' => 'submit',
        'class' => 'btn btn-primary btn-block mt-3',
        'id' => 'download-selection',
        'disabled' => $selectioncount ? null : 'disabled',
    ]);
    echo html_writer::link(new moodle_url($PAGE->url, ['action' => 'clearselection', 'sesskey' => sesskey()]),
        get_string('clearselection', 'local_downloadcenter'),
        ['class' => 'btn btn-secondary mt-2', 'id' => 'clear-selection-link']);
    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    echo $OUTPUT->footer();
    exit;
}

// If no valid mode, redirect to course listing.
require_login();
redirect(new moodle_url('/course/index.php'));

/**
 * Build the per-course selection array expected by the admin manager.
 *
 * @param array $coursedata Raw form data grouped by course
 * @param array $options Download options
 * @param bool $allowrestricted True when course access checks should be bypassed
 * @return array Prepared selections
 */
function local_downloadcenter_prepare_admin_selections(array $coursedata, array $options,
        bool $allowrestricted = false) {
    global $DB, $USER;

    $prepared = [];

    foreach ($coursedata as $courseid => $items) {
        $courseid = clean_param($courseid, PARAM_INT);
        if (empty($courseid) || !is_array($items)) {
            continue;
        }

        $requested = [];
        $fullcourseselected = false;
        foreach ($items as $itemkey => $value) {
            if (empty($value) || !is_string($itemkey)) {
                continue;
            }
            if ($itemkey === '__fullcourse') {
                $fullcourseselected = true;
                continue;
            }
            if (!preg_match('/^item_[a-z][a-z0-9]*_\d+$/i', $itemkey)) {
                continue;
            }
            $requested[$itemkey] = true;
        }

        if (empty($requested) && !$fullcourseselected) {
            continue;
        }

        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course || (!$allowrestricted && !can_access_course($course))) {
            continue;
        }

        $factory = new \local_downloadcenter\factory($course, $USER);
        $factory->set_download_options($options);
        try {
            $resources = $factory->get_resources_for_user();
        } catch (\moodle_exception $exception) {
            debugging('Unable to load resources for course ' . $courseid . ': ' . $exception->getMessage(),
                DEBUG_DEVELOPER);
            $resources = [];
        }

        $selection = [];
        foreach ($resources as $sectionid => $section) {
            foreach ($section->res as $res) {
                $reskey = 'item_' . $res->modname . '_' . $res->instanceid;
                if ($fullcourseselected || isset($requested[$reskey])) {
                    $selection[$reskey] = 1;
                    $selection['item_topic_' . $sectionid] = 1;
                }
            }
        }

        if ($fullcourseselected) {
            $selection['__fullcourse'] = 1;
        }

        if (!empty($selection)) {
            $prepared[$courseid] = $selection;
        }
    }

    return $prepared;
}
