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
\core_php_time_limit::raise();
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

    require_once(__DIR__ . '/classes/admin_manager.php');
    require_once(__DIR__ . '/classes/factory.php');

    $PAGE->set_context($systemcontext);
    $PAGE->set_url(new moodle_url('/local/downloadcenter/index.php', ['mode' => 'admin']));
    $PAGE->set_pagelayout('admin');
    $PAGE->add_body_class('path-local-downloadcenter-admin');

    $defaultoptions = [
        'excludestudent' => (int)(get_config('local_downloadcenter', 'excludestudentdefault') ?? 0),
        'filesrealnames' => 0,
        'addnumbering' => 0,
    ];

    $downloadoptions = [
        'excludestudent' => optional_param('excludestudent', $defaultoptions['excludestudent'], PARAM_BOOL),
        'filesrealnames' => optional_param('filesrealnames', $defaultoptions['filesrealnames'], PARAM_BOOL),
        'addnumbering' => optional_param('addnumbering', $defaultoptions['addnumbering'], PARAM_BOOL),
    ];
    try {
        $coursedata = optional_param_array('coursedata', null, PARAM_RAW);
    } catch (\coding_exception $exception) {
        if (strpos($exception->getMessage(), 'clean() can not process arrays') === false) {
            throw $exception;
        }
        $coursedata = null;
    }

    if ($coursedata === null) {
        if (isset($_POST['coursedata'])) {
            $coursedata = $_POST['coursedata'];
        } else if (isset($_GET['coursedata'])) {
            $coursedata = $_GET['coursedata'];
        }
    }

    if (is_array($coursedata)) {
        $coursedata = clean_param_array($coursedata, PARAM_RAW, true);
    } else {
        $coursedata = [];
    }

    if ($action === 'download') {
        require_sesskey();

        $courseselections = local_downloadcenter_prepare_admin_selections($coursedata, $downloadoptions);

        if (empty($courseselections)) {
            redirect($PAGE->url, get_string('noselectederror', 'local_downloadcenter'),
                null, \core\output\notification::NOTIFY_WARNING);
        }

        $adminmanager = new \local_downloadcenter\admin_manager();
        $adminmanager->download_multiple_courses($courseselections, $downloadoptions);
        exit;
    }

    $PAGE->set_title(get_string('admindownloadcenter', 'local_downloadcenter'));
    $PAGE->set_heading(get_string('admindownloadcenter', 'local_downloadcenter'));
    $PAGE->requires->css('/local/downloadcenter/styles.css');

    $categories = \core_course_category::get_all();
    $treehtml = '';
    foreach ($categories as $category) {
        if ($category->parent != 0) {
            continue;
        }
        $rendered = local_downloadcenter_render_admin_category($category, $coursedata);
        if (!empty($rendered['html'])) {
            $treehtml .= $rendered['html'];
        }
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('admindownloadcenter', 'local_downloadcenter'));

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $PAGE->url->out(false),
        'class' => 'downloadcenter-admin-form',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'download']);

    echo html_writer::start_div('row');

    echo html_writer::start_div('col-lg-8 downloadcenter-tree-column');
    echo html_writer::tag('h3', get_string('selectcourses', 'local_downloadcenter'));
    echo html_writer::tag('p', get_string('adminmultiselectinstructions', 'local_downloadcenter'), ['class' => 'text-muted']);
    if (!empty($treehtml)) {
        echo html_writer::div($treehtml, 'downloadcenter-category-tree');
    } else {
        echo html_writer::div(get_string('nocoursesfound', 'local_downloadcenter'), 'alert alert-info');
    }
    echo html_writer::end_div();

    echo html_writer::start_div('col-lg-4 downloadcenter-options-column');
    echo html_writer::tag('h3', get_string('downloadoptions', 'local_downloadcenter'));

    echo html_writer::start_div('form-check mb-2');
    echo html_writer::empty_tag('input', [
        'type' => 'checkbox',
        'class' => 'form-check-input',
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
        'class' => 'form-check-input',
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
        'class' => 'form-check-input',
        'id' => 'addnumbering',
        'name' => 'addnumbering',
        'value' => 1,
        'checked' => !empty($downloadoptions['addnumbering']) ? 'checked' : null,
    ]);
    echo html_writer::tag('label', get_string('downloadoptions:addnumbering', 'local_downloadcenter'),
        ['class' => 'form-check-label', 'for' => 'addnumbering']);
    echo html_writer::end_div();

    echo html_writer::tag('button', get_string('downloadselection', 'local_downloadcenter'),
        ['type' => 'submit', 'class' => 'btn btn-primary btn-block mt-3']);

    echo html_writer::end_div();

    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    $js = <<<'JS'
(function() {
    function updateCourseState(courseNode) {
        const courseCheckbox = courseNode.querySelector('summary .course-checkbox');
        const resourceCheckboxes = courseNode.querySelectorAll('.resource-checkbox');
        if (!courseCheckbox) {
            return;
        }
        if (!resourceCheckboxes.length) {
            courseCheckbox.indeterminate = false;
            return;
        }
        let checkedCount = 0;
        resourceCheckboxes.forEach(cb => {
            if (cb.checked) {
                checkedCount++;
            }
        });
        if (checkedCount === 0) {
            courseCheckbox.checked = false;
            courseCheckbox.indeterminate = false;
        } else if (checkedCount === resourceCheckboxes.length) {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = false;
        } else {
            courseCheckbox.checked = true;
            courseCheckbox.indeterminate = true;
        }
    }

    function updateCategoryState(categoryNode) {
        const categoryCheckbox = categoryNode.querySelector('summary .category-checkbox');
        if (!categoryCheckbox) {
            return;
        }
        const resourceCheckboxes = categoryNode.querySelectorAll('.resource-checkbox');
        if (!resourceCheckboxes.length) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
            return;
        }
        let checkedCount = 0;
        resourceCheckboxes.forEach(cb => {
            if (cb.checked) {
                checkedCount++;
            }
        });
        if (checkedCount === 0) {
            categoryCheckbox.checked = false;
            categoryCheckbox.indeterminate = false;
        } else if (checkedCount === resourceCheckboxes.length) {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = false;
        } else {
            categoryCheckbox.checked = true;
            categoryCheckbox.indeterminate = true;
        }
    }

    document.querySelectorAll('.downloadcenter-course').forEach(courseNode => {
        const courseCheckbox = courseNode.querySelector('summary .course-checkbox');
        const resourceCheckboxes = courseNode.querySelectorAll('.resource-checkbox');
        if (!courseCheckbox) {
            return;
        }

        courseCheckbox.addEventListener('change', () => {
            resourceCheckboxes.forEach(cb => {
                cb.checked = courseCheckbox.checked;
                cb.dispatchEvent(new Event('change'));
            });
        });

        resourceCheckboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                updateCourseState(courseNode);
                const categoryNode = courseNode.closest('.downloadcenter-category');
                if (categoryNode) {
                    updateCategoryState(categoryNode);
                }
            });
        });

        updateCourseState(courseNode);
    });

    document.querySelectorAll('.downloadcenter-category').forEach(categoryNode => {
        const categoryCheckbox = categoryNode.querySelector('summary .category-checkbox');
        if (!categoryCheckbox) {
            return;
        }

        categoryCheckbox.addEventListener('change', () => {
            const container = categoryNode.querySelector('.category-children');
            if (!container) {
                return;
            }
            container.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.checked = categoryCheckbox.checked;
                cb.dispatchEvent(new Event('change'));
            });
        });

        updateCategoryState(categoryNode);
    });
})();
JS;
    echo html_writer::script($js);

    echo $OUTPUT->footer();
    exit;
}

// If no valid mode, redirect to course listing.
require_login();
redirect(new moodle_url('/course/index.php'));

/**
 * Render a category node for the admin multi-course selector.
 *
 * @param \core_course_category $category Category object
 * @param array $selectedcoursedata Posted course/resource selections
 * @return array{html:string, selected:bool} Rendered HTML and selection state
 */
function local_downloadcenter_render_admin_category(\core_course_category $category, array $selectedcoursedata) {
    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    $children = $category->get_children();

    $content = '';
    $hasselected = false;

    foreach ($courses as $course) {
        if (!$course instanceof \core_course_list_element) {
            $course = new \core_course_list_element($course);
        }
        if (!$course->can_access()) {
            continue;
        }

        $rendered = local_downloadcenter_render_admin_course($course, $selectedcoursedata);
        if (!empty($rendered['html'])) {
            $content .= $rendered['html'];
            $hasselected = $hasselected || $rendered['selected'];
        }
    }

    foreach ($children as $child) {
        $childrendered = local_downloadcenter_render_admin_category($child, $selectedcoursedata);
        if (!empty($childrendered['html'])) {
            $content .= $childrendered['html'];
            $hasselected = $hasselected || $childrendered['selected'];
        }
    }

    if ($content === '') {
        return ['html' => '', 'selected' => false];
    }

    $categoryid = $category->id;
    $checkboxattrs = [
        'type' => 'checkbox',
        'class' => 'form-check-input category-checkbox',
        'data-categoryid' => $categoryid,
        'id' => 'category-' . $categoryid,
    ];
    if ($hasselected) {
        $checkboxattrs['checked'] = 'checked';
    }

    $labeltext = format_string($category->name);
    $coursecount = count($courses);
    if ($coursecount) {
        $labeltext .= ' (' . $coursecount . ' ' . get_string('courses') . ')';
    }

    $checkbox = html_writer::empty_tag('input', $checkboxattrs);
    $label = html_writer::tag('label', $labeltext, [
        'for' => 'category-' . $categoryid,
        'class' => 'mb-0 ml-2 font-weight-bold',
    ]);

    $summary = html_writer::tag('summary', $checkbox . $label, ['class' => 'd-flex align-items-center']);

    $detailsattrs = [
        'class' => 'downloadcenter-category mb-3',
        'data-categoryid' => $categoryid,
    ];
    if ($hasselected) {
        $detailsattrs['open'] = 'open';
    }

    $body = html_writer::div($content, 'category-children pl-3', ['id' => 'category-node-' . $categoryid]);

    return [
        'html' => html_writer::tag('details', $summary . $body, $detailsattrs),
        'selected' => $hasselected,
    ];
}

/**
 * Render a single course node with its resources for admin selection.
 *
 * @param \core_course_list_element $course Course element
 * @param array $selectedcoursedata Posted course/resource selections
 * @return array{html:string, selected:bool} Rendered HTML and selection state
 */
function local_downloadcenter_render_admin_course(\core_course_list_element $course, array $selectedcoursedata) {
    global $DB, $USER;

    $courseid = $course->id;
    $courserecord = $DB->get_record('course', ['id' => $courseid]);
    if (!$courserecord) {
        return ['html' => '', 'selected' => false];
    }

    $factory = new \local_downloadcenter\factory($courserecord, $USER);
    $resources = $factory->get_resources_for_user();
    $courseitems = $selectedcoursedata[$courseid] ?? [];
    if (!is_array($courseitems)) {
        $courseitems = [];
    }
    $fullcourseselected = !empty($courseitems['__fullcourse']);
    unset($courseitems['__fullcourse']);

    $hasselected = $fullcourseselected;
    $resourceoutput = '';
    $sectionindex = 0;

    if (empty($resources)) {
        $fallbackattrs = [
            'type' => 'checkbox',
            'class' => 'form-check-input resource-checkbox course-fullcourse-checkbox',
            'name' => 'coursedata[' . $courseid . '][__fullcourse]',
            'value' => 1,
            'id' => 'resource-' . $courseid . '-fullcourse',
            'data-fullcourse' => 1,
        ];
        if ($fullcourseselected) {
            $fallbackattrs['checked'] = 'checked';
        }

        $resourceoutput .= html_writer::div(
            html_writer::empty_tag('input', $fallbackattrs) .
            html_writer::tag('label', get_string('adminfullcourselabel', 'local_downloadcenter'), [
                'for' => 'resource-' . $courseid . '-fullcourse',
                'class' => 'form-check-label d-flex align-items-center',
            ]) .
            html_writer::tag('div', get_string('adminfullcoursehint', 'local_downloadcenter'), [
                'class' => 'text-muted small mt-1',
            ]),
            'form-check resource-item'
        );
    } else {
        foreach ($resources as $sectionid => $section) {
            $sectionclasses = 'downloadcenter-section-title font-weight-bold';
            if ($sectionindex++ > 0) {
                $sectionclasses .= ' mt-3';
            }
            $resourceoutput .= html_writer::div(format_string($section->title), $sectionclasses);

            foreach ($section->res as $res) {
                $reskey = 'item_' . $res->modname . '_' . $res->instanceid;
                $checked = $fullcourseselected || !empty($courseitems[$reskey]);
                if ($checked) {
                    $hasselected = true;
                }

                $inputattrs = [
                    'type' => 'checkbox',
                    'class' => 'form-check-input resource-checkbox',
                    'name' => 'coursedata[' . $courseid . '][' . $reskey . ']',
                    'value' => 1,
                    'id' => 'resource-' . $courseid . '-' . $res->cmid,
                ];
                if ($checked) {
                    $inputattrs['checked'] = 'checked';
                }

                $labeltext = $res->icon . format_string($res->name);
                if (!$res->visible || !empty($res->isstealth)) {
                    $labeltext .= html_writer::span(get_string('hidden'), 'badge badge-warning ml-2');
                }

                $resourceoutput .= html_writer::div(
                    html_writer::empty_tag('input', $inputattrs) .
                    html_writer::tag('label', $labeltext, [
                        'for' => 'resource-' . $courseid . '-' . $res->cmid,
                        'class' => 'form-check-label d-flex align-items-center',
                    ]),
                    'form-check resource-item'
                );
            }
        }
    }

    $coursecheckboxattrs = [
        'type' => 'checkbox',
        'class' => 'form-check-input course-checkbox',
        'data-courseid' => $courseid,
        'id' => 'course-' . $courseid,
    ];
    if ($hasselected) {
        $coursecheckboxattrs['checked'] = 'checked';
    }

    $coursebadge = '';
    if (!$course->visible) {
        $coursebadge = html_writer::span(get_string('hidden'), 'badge badge-warning ml-2');
    }

    $coursecontext = \context_course::instance($courseid);
    $coursename = method_exists($course, 'get_formatted_name') ? $course->get_formatted_name()
        : format_string($course->fullname, true, ['context' => $coursecontext]);
    $courseshortname = format_string($course->shortname, true, ['context' => $coursecontext]);

    $summarylabel = html_writer::tag('label',
        $coursename . ' (' . $courseshortname . ')' . $coursebadge,
        [
            'for' => 'course-' . $courseid,
            'class' => 'mb-0 ml-2 d-inline-flex align-items-center',
        ]
    );

    $summary = html_writer::tag(
        'summary',
        html_writer::empty_tag('input', $coursecheckboxattrs) . $summarylabel,
        ['class' => 'd-flex align-items-center']
    );

    $detailsattrs = [
        'class' => 'downloadcenter-course mb-2',
        'data-courseid' => $courseid,
    ];
    if ($hasselected) {
        $detailsattrs['open'] = 'open';
    }

    $body = html_writer::div($resourceoutput, 'course-resources pl-4', ['id' => 'course-node-' . $courseid]);

    return [
        'html' => html_writer::tag('details', $summary . $body, $detailsattrs),
        'selected' => $hasselected,
    ];
}

/**
 * Build the per-course selection array expected by the admin manager.
 *
 * @param array $coursedata Raw form data grouped by course
 * @param array $options Download options
 * @return array Prepared selections
 */
function local_downloadcenter_prepare_admin_selections(array $coursedata, array $options) {
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
        if (!$course || !can_access_course($course)) {
            continue;
        }

        $factory = new \local_downloadcenter\factory($course, $USER);
        $factory->set_download_options($options);
        $resources = $factory->get_resources_for_user();

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

        if (!empty($selection)) {
            $prepared[$courseid] = $selection;
        }
    }

    return $prepared;
}
