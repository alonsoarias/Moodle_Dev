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

// Raise limits for large downloads.
core_php_time_limit::raise();
raise_memory_limit(MEMORY_HUGE);

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
    
    $PAGE->set_context($systemcontext);
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']));
    $PAGE->set_pagelayout('admin');
    
    // Load necessary classes.
    require_once(__DIR__ . '/classes/admin_manager.php');
    require_once(__DIR__ . '/classes/factory.php');
    
    // Handle actions.
    if ($action === 'togglecourse') {
        $cid = required_param('cid', PARAM_INT);
        
        if (isset($SESSION->downloadcenter_selection[$cid])) {
            unset($SESSION->downloadcenter_selection[$cid]);
            $selected = false;
        } else {
            $SESSION->downloadcenter_selection[$cid] = true;
            $selected = true;
        }
        
        // Return JSON response for AJAX.
        if (optional_param('ajax', 0, PARAM_BOOL)) {
            echo json_encode(['success' => true, 'selected' => $selected]);
            exit;
        }
        
        redirect($PAGE->url);
    }
    
    if ($action === 'clear') {
        require_sesskey();
        $SESSION->downloadcenter_selection = [];
        redirect($PAGE->url, get_string('selectioncleared', 'local_downloadcenter'));
    }
    
    if ($action === 'updateoptions') {
        require_sesskey();
        $SESSION->downloadcenter_options = [
            'excludestudent' => optional_param('excludestudent', 0, PARAM_BOOL),
            'filesrealnames' => optional_param('filesrealnames', 0, PARAM_BOOL),
            'addnumbering' => optional_param('addnumbering', 0, PARAM_BOOL)
        ];
        redirect($PAGE->url, get_string('optionssaved', 'local_downloadcenter'));
    }
    
    if ($action === 'download') {
        require_sesskey();
        
        if (empty($SESSION->downloadcenter_selection)) {
            redirect($PAGE->url, get_string('nocoursesselected', 'local_downloadcenter'), 
                    null, \core\output\notification::NOTIFY_WARNING);
        }
        
        $adminmanager = new \local_downloadcenter\admin_manager();
        $options = $SESSION->downloadcenter_options;
        
        // Download selected courses.
        $adminmanager->download_multiple_courses(
            array_keys($SESSION->downloadcenter_selection),
            $options
        );
        exit;
    }
    
    // Setup page.
    $PAGE->set_title(get_string('admindownloadcenter', 'local_downloadcenter'));
    $PAGE->set_heading(get_string('admindownloadcenter', 'local_downloadcenter'));
    
    // Add CSS.
    $PAGE->requires->css('/local/downloadcenter/styles.css');
    
    // Display interface.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('admindownloadcenter', 'local_downloadcenter'));
    
    // Get all categories and courses.
    $categories = \core_course_category::get_all();
    $selectedcourses = $SESSION->downloadcenter_selection;
    $options = $SESSION->downloadcenter_options;
    
    // Display selection interface.
    echo html_writer::start_div('downloadcenter-admin-container');
    
    // Left panel - Category and course tree.
    echo html_writer::start_div('row');
    echo html_writer::start_div('col-md-8');
    
    echo html_writer::tag('h3', get_string('selectcourses', 'local_downloadcenter'));
    
    // Category tree.
    echo html_writer::start_div('category-tree card');
    echo html_writer::start_div('card-body');
    
    // Display root categories.
    foreach ($categories as $category) {
        if ($category->parent == 0) {
            echo render_category_tree($category, $selectedcourses, $PAGE->url);
        }
    }
    
    echo html_writer::end_div(); // card-body.
    echo html_writer::end_div(); // category-tree.
    
    echo html_writer::end_div(); // col-md-8.
    
    // Right panel - Selection summary and options.
    echo html_writer::start_div('col-md-4');
    
    echo html_writer::start_div('selection-panel card sticky-top');
    echo html_writer::start_div('card-header bg-primary text-white');
    echo html_writer::tag('h4', get_string('currentselection', 'local_downloadcenter') . 
                          ' (' . count($selectedcourses) . ')', ['class' => 'mb-0']);
    echo html_writer::end_div();
    
    echo html_writer::start_div('card-body');
    
    // List selected courses.
    if (!empty($selectedcourses)) {
        echo html_writer::start_tag('div', ['class' => 'selected-courses-list mb-3', 
                                            'style' => 'max-height: 300px; overflow-y: auto;']);
        
        foreach ($selectedcourses as $cid => $selected) {
            if ($course = $DB->get_record('course', ['id' => $cid])) {
                echo html_writer::start_div('selected-course mb-2 p-2 border rounded');
                echo html_writer::tag('strong', $course->fullname);
                echo html_writer::tag('span', ' (' . $course->shortname . ')', ['class' => 'text-muted']);
                echo html_writer::end_div();
            }
        }
        
        echo html_writer::end_tag('div');
        
        // Download options form.
        echo html_writer::start_tag('form', ['method' => 'post', 'action' => $PAGE->url->out(false)]);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'updateoptions']);
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        
        echo html_writer::tag('h5', get_string('downloadoptions', 'local_downloadcenter'), ['class' => 'mb-3']);
        
        // Exclude student content option.
        echo html_writer::start_div('form-check mb-2');
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'id' => 'excludestudent',
            'name' => 'excludestudent',
            'value' => '1',
            'checked' => !empty($options['excludestudent'])
        ]);
        echo html_writer::tag('label', get_string('excludestudentcontent', 'local_downloadcenter'), 
                             ['class' => 'form-check-label', 'for' => 'excludestudent']);
        echo html_writer::end_div();
        
        // Real filenames option.
        echo html_writer::start_div('form-check mb-2');
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'id' => 'filesrealnames',
            'name' => 'filesrealnames',
            'value' => '1',
            'checked' => !empty($options['filesrealnames'])
        ]);
        echo html_writer::tag('label', get_string('downloadoptions:filesrealnames', 'local_downloadcenter'), 
                             ['class' => 'form-check-label', 'for' => 'filesrealnames']);
        echo html_writer::end_div();
        
        // Add numbering option.
        echo html_writer::start_div('form-check mb-3');
        echo html_writer::empty_tag('input', [
            'type' => 'checkbox',
            'class' => 'form-check-input',
            'id' => 'addnumbering',
            'name' => 'addnumbering',
            'value' => '1',
            'checked' => !empty($options['addnumbering'])
        ]);
        echo html_writer::tag('label', get_string('downloadoptions:addnumbering', 'local_downloadcenter'), 
                             ['class' => 'form-check-label', 'for' => 'addnumbering']);
        echo html_writer::end_div();
        
        echo html_writer::tag('button', get_string('saveoptions', 'local_downloadcenter'), 
                             ['type' => 'submit', 'class' => 'btn btn-sm btn-secondary mb-3']);
        
        echo html_writer::end_tag('form');
        
        // Action buttons.
        echo html_writer::start_div('action-buttons');
        
        // Download button.
        $downloadurl = new moodle_url('/local/downloadcenter/index.php', [
            'mode' => 'admin',
            'action' => 'download',
            'sesskey' => sesskey()
        ]);
        echo html_writer::link($downloadurl, 
                               get_string('downloadselection', 'local_downloadcenter'), 
                               ['class' => 'btn btn-success btn-block mb-2']);
        
        // Clear button.
        $clearurl = new moodle_url('/local/downloadcenter/index.php', [
            'mode' => 'admin',
            'action' => 'clear',
            'sesskey' => sesskey()
        ]);
        echo html_writer::link($clearurl, 
                               get_string('clearselection', 'local_downloadcenter'), 
                               ['class' => 'btn btn-danger btn-block']);
        
        echo html_writer::end_div(); // action-buttons.
        
    } else {
        echo html_writer::tag('p', get_string('nocoursesselected', 'local_downloadcenter'), 
                             ['class' => 'text-muted']);
    }
    
    echo html_writer::end_div(); // card-body.
    echo html_writer::end_div(); // selection-panel.
    
    echo html_writer::end_div(); // col-md-4.
    echo html_writer::end_div(); // row.
    
    echo html_writer::end_div(); // downloadcenter-admin-container.
    
    // Add JavaScript for interactivity.
    echo html_writer::script('
        document.querySelectorAll(".category-toggle").forEach(function(toggle) {
            toggle.addEventListener("click", function(e) {
                e.preventDefault();
                var categoryId = this.getAttribute("data-category");
                var contentDiv = document.getElementById("category-content-" + categoryId);
                var icon = this.querySelector("i");
                
                if (contentDiv.style.display === "none") {
                    contentDiv.style.display = "block";
                    icon.classList.remove("fa-plus");
                    icon.classList.add("fa-minus");
                } else {
                    contentDiv.style.display = "none";
                    icon.classList.remove("fa-minus");
                    icon.classList.add("fa-plus");
                }
            });
        });
        
        // Handle course selection.
        document.querySelectorAll(".course-checkbox").forEach(function(checkbox) {
            checkbox.addEventListener("change", function() {
                var courseId = this.getAttribute("data-courseid");
                var isChecked = this.checked;
                
                // Update via AJAX.
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "' . $PAGE->url->out(false) . '&action=togglecourse&cid=" + courseId + "&ajax=1", true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        // Reload page to update selection display.
                        window.location.reload();
                    }
                };
                xhr.send();
            });
        });
    ');
    
    echo $OUTPUT->footer();
    exit;
}

// If no valid mode, redirect to course listing.
require_login();
redirect(new moodle_url('/course/index.php'));

/**
 * Render category tree recursively.
 *
 * @param \core_course_category $category Category object
 * @param array $selectedcourses Selected course IDs
 * @param \moodle_url $baseurl Base URL
 * @return string HTML output
 */
function render_category_tree($category, $selectedcourses, $baseurl) {
    global $OUTPUT;
    
    $output = '';
    
    // Get courses in this category.
    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    
    // Get child categories.
    $children = $category->get_children();
    
    // Only show if has courses or children.
    if (empty($courses) && empty($children)) {
        return '';
    }
    
    $output .= html_writer::start_div('category-item mb-2');
    
    // Category header.
    $output .= html_writer::start_div('category-header p-2 bg-light border rounded');
    
    $toggleicon = html_writer::tag('i', '', ['class' => 'fas fa-plus mr-2']);
    $output .= html_writer::link('#', 
                                 $toggleicon . format_string($category->name) . 
                                 ' (' . count($courses) . ' ' . get_string('courses') . ')',
                                 ['class' => 'category-toggle text-decoration-none text-dark', 
                                  'data-category' => $category->id]);
    
    $output .= html_writer::end_div();
    
    // Category content (initially hidden).
    $output .= html_writer::start_div('category-content ml-3', 
                                      ['id' => 'category-content-' . $category->id, 
                                       'style' => 'display: none;']);
    
    // Display courses.
    if (!empty($courses)) {
        $output .= html_writer::start_div('courses-list mb-2');
        
        foreach ($courses as $course) {
            if (!can_access_course($course)) {
                continue;
            }
            
            $checked = isset($selectedcourses[$course->id]);
            
            $output .= html_writer::start_div('form-check mb-1');
            
            $output .= html_writer::empty_tag('input', [
                'type' => 'checkbox',
                'class' => 'form-check-input course-checkbox',
                'id' => 'course-' . $course->id,
                'data-courseid' => $course->id,
                'checked' => $checked
            ]);
            
            $courselabel = format_string($course->fullname) . ' (' . format_string($course->shortname) . ')';
            if (!$course->visible) {
                $courselabel .= ' ' . html_writer::tag('span', get_string('hidden'), 
                                                       ['class' => 'badge badge-warning']);
            }
            
            $output .= html_writer::tag('label', $courselabel, 
                                       ['class' => 'form-check-label', 
                                        'for' => 'course-' . $course->id]);
            
            $output .= html_writer::end_div();
        }
        
        $output .= html_writer::end_div();
    }
    
    // Display child categories.
    if (!empty($children)) {
        foreach ($children as $child) {
            $output .= render_category_tree($child, $selectedcourses, $baseurl);
        }
    }
    
    $output .= html_writer::end_div(); // category-content.
    $output .= html_writer::end_div(); // category-item.
    
    return $output;
}