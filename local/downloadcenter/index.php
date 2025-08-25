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

$catids = [];
if (array_key_exists('catids', $_REQUEST) && is_array($_REQUEST['catids'])) {
    $catids = optional_param_array('catids', [], PARAM_INT);
} else {
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

// If no categories specified, use top-level categories
if (empty($catids)) {
    $catids = array_map(function($cat) {
        return $cat->id;
    }, \core_course_category::top()->get_children());
}

require_login();
$systemcontext = context_system::instance();
require_capability('local/downloadcenter:view', $systemcontext);

// Load current selection from session or persistent user preference
$selection = $SESSION->local_downloadcenter_selection ?? null;
if ($selection === null) {
    $pref = get_user_preferences('local_downloadcenter_selection', '');
    $selection = $pref === '' ? [] : json_decode($pref, true);
    if (!is_array($selection)) {
        $selection = [];
    }
    $SESSION->local_downloadcenter_selection = $selection;
}

// AJAX handler for toggling course selection
if ($action === 'togglecourse') {
    require_sesskey();
    $cid = required_param('courseid', PARAM_INT);
    $checked = optional_param('checked', 0, PARAM_BOOL);
    
    // Verify access to the course - get full course record
    $course = $DB->get_record('course', ['id' => $cid], '*', MUST_EXIST);
    if (!can_access_course($course)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
    
    $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
    if ($checked) {
        $sessionselection[$cid] = ['downloadall' => 1];
    } else {
        unset($sessionselection[$cid]);
    }
    $SESSION->local_downloadcenter_selection = $sessionselection;
    set_user_preference('local_downloadcenter_selection', json_encode($sessionselection));
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'selection' => $sessionselection]);
    exit;
}

// AJAX handler for saving course-specific selections
if ($action === 'savecourse' && $courseid) {
    require_sesskey();
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    if (!can_access_course($course)) {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }
    
    // Get current selection from session
    $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
    
    // Get submitted data
    $data = data_submitted() ?? new stdClass();
    unset($data->action, $data->sesskey, $data->courseid);
    
    // Convert data to array and check if any items are selected
    $dataArray = (array)$data;
    $hasSelection = false;
    
    foreach ($dataArray as $key => $value) {
        if ((strpos($key, 'item_') === 0 || strpos($key, 'item_topic_') === 0) && $value) {
            $hasSelection = true;
            break;
        }
    }
    
    // Update session based on selection
    if ($hasSelection) {
        // Store the selection data for this course
        $sessionselection[$courseid] = $dataArray;
    } else {
        // Remove course from selection if no items selected
        unset($sessionselection[$courseid]);
    }
    
    // Save to session and user preferences
    $SESSION->local_downloadcenter_selection = $sessionselection;
    set_user_preference('local_downloadcenter_selection', json_encode($sessionselection));
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'selection' => $sessionselection, 'hasSelection' => $hasSelection]);
    exit;
}

// AJAX handler for toggling multiple courses in a category
if ($action === 'togglecategory') {
    require_sesskey();
    $categoryid = required_param('categoryid', PARAM_INT);
    $courseids = required_param('courseids', PARAM_SEQUENCE);
    $checked = optional_param('checked', 0, PARAM_BOOL);
    
    $courseids = explode(',', $courseids);
    $sessionselection = $SESSION->local_downloadcenter_selection ?? [];
    
    foreach ($courseids as $cid) {
        $cid = intval($cid);
        if ($cid) {
            // Verify access to each course - get full course record for can_access_course
            $course = $DB->get_record('course', ['id' => $cid]);
            if ($course && can_access_course($course)) {
                if ($checked) {
                    $sessionselection[$cid] = ['downloadall' => 1];
                } else {
                    unset($sessionselection[$cid]);
                }
            }
        }
    }
    
    $SESSION->local_downloadcenter_selection = $sessionselection;
    set_user_preference('local_downloadcenter_selection', json_encode($sessionselection));
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'selection' => $sessionselection]);
    exit;
}

// Clear selection action
if ($action === 'clear') {
    require_sesskey();
    unset($SESSION->local_downloadcenter_selection);
    unset_user_preference('local_downloadcenter_selection');
    redirect(local_downloadcenter_build_url($catids));
}

// Download action
if ($action === 'download') {
    require_sesskey();
    if (empty($selection)) {
        redirect(local_downloadcenter_build_url([]),
                get_string('nocoursesselected', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_WARNING);
    }

    // Validate access to selected courses
    $downloadcourses = [];
    $errors = [];
    
    foreach ($selection as $cid => $data) {
        try {
            $course = $DB->get_record('course', ['id' => $cid], '*', MUST_EXIST);
            $coursecontext = context_course::instance($course->id);
            
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

    // Clear selection and close session for download
    unset($SESSION->local_downloadcenter_selection);
    unset_user_preference('local_downloadcenter_selection');
    \core\session\manager::write_close();

    // Generate ZIP file
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
            
            // Log download event
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

// Handle course-specific view
if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $coursecontext = context_course::instance($course->id);
    
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
    
    // Load JavaScript modules
    $PAGE->requires->js_call_amd('local_downloadcenter/modfilter', 'init',
                                 $downloadcenter->get_js_modnames());
    $PAGE->requires->js_call_amd('local_downloadcenter/section_tree', 'init');
    
    // Add language strings for JavaScript
    $PAGE->requires->strings_for_js(['saveselection'], 'local_downloadcenter');

    // Get current selection for this course
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
    
    // Add navigation
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'),
                      local_downloadcenter_build_url($catids));
    $PAGE->navbar->add($course->fullname);

    if ($data = $downloadform->get_data()) {
        // Process submitted data
        unset($data->submitbutton, $data->courseid);
        
        // Check if any items are selected
        $hasSelection = false;
        foreach ($data as $key => $value) {
            if ((strpos($key, 'item_') === 0) && $value) {
                $hasSelection = true;
                break;
            }
        }
        
        // Update selection
        if ($hasSelection) {
            $selection[$courseid] = (array)$data;
        } else {
            unset($selection[$courseid]);
        }
        
        $SESSION->local_downloadcenter_selection = $selection;
        set_user_preference('local_downloadcenter_selection', json_encode($selection));

        // Log view event
        $event = \local_downloadcenter\event\plugin_viewed::create([
            'objectid' => $course->id,
            'context' => $coursecontext
        ]);
        $event->trigger();
        
        // Redirect back to category view with success message
        redirect(local_downloadcenter_build_url($catids),
                get_string('filesadded', 'local_downloadcenter'),
                null,
                \core\output\notification::NOTIFY_SUCCESS);
    } else if ($downloadform->is_cancelled()) {
        redirect(local_downloadcenter_build_url($catids));
    } else {
        echo $OUTPUT->header();
        
        // Toolbar
        echo html_writer::start_div('downloadcenter-toolbar mb-3');
        echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), 
                             ['class' => 'h3']);
        
        // Back button
        echo html_writer::link(
            local_downloadcenter_build_url($catids),
            get_string('back'),
            ['class' => 'btn btn-secondary']
        );
        echo html_writer::end_div();
        
        // Show current selection status for this course
        if (!empty($courseselection)) {
            $selectedCount = 0;
            foreach ($courseselection as $key => $value) {
                if (strpos($key, 'item_') === 0 && $value) {
                    $selectedCount++;
                }
            }
            if ($selectedCount > 0) {
                $msg = $selectedCount . ' item' . ($selectedCount > 1 ? 's' : '') . ' currently selected';
                echo html_writer::div($msg, 'alert alert-info');
            }
        }
        
        $downloadform->display();
        echo $OUTPUT->footer();
        exit;
    }
}

/**
 * Render a collapsible category tree with courses and tri-state checkboxes.
 *
 * @param \core_course_category $category Category to render
 * @param array $selection Current course selection
 * @param array $catids Top-level category IDs
 * @param string $search Search filter
 * @return string HTML output
 */
function local_downloadcenter_render_category_tree(\core_course_category $category, array $selection,
        array $catids, string $search): string {
    global $SESSION, $OUTPUT, $DB;

    $courses = $category->get_courses(['recursive' => false, 'sort' => ['fullname' => 1]]);
    
    // Apply search filter
    if ($search !== '') {
        $courses = array_filter($courses, function($course) use ($search) {
            return stripos($course->fullname, $search) !== false ||
                   stripos($course->shortname, $search) !== false;
        });
    }

    // Calculate category checkbox state based on selected courses
    $allcourseids = array_keys($category->get_courses(['recursive' => true]));
    $selectedcourseids = array_intersect($allcourseids, array_keys($selection));
    
    // Determine if category is fully checked, partially checked, or unchecked
    $catchecked = !empty($allcourseids) && count($selectedcourseids) === count($allcourseids);
    $catindeterminate = !empty($selectedcourseids) && !$catchecked;

    // Calculate partial selection states for individual courses
    $courseStates = [];
    foreach ($courses as $course) {
        if (isset($selection[$course->id])) {
            $courseData = $selection[$course->id];
            if (isset($courseData['downloadall']) && $courseData['downloadall']) {
                $courseStates[$course->id] = 'full';
            } else {
                // Check if it's a partial selection
                $hasSelection = false;
                foreach ($courseData as $key => $value) {
                    if (strpos($key, 'item_') === 0 && $value) {
                        $hasSelection = true;
                        break;
                    }
                }
                $courseStates[$course->id] = $hasSelection ? 'partial' : 'none';
            }
        } else {
            $courseStates[$course->id] = 'none';
        }
    }

    // Build HTML output
    $collapseid = 'cat' . $category->id;
    $expanded = $catchecked || $catindeterminate;
    
    $html = html_writer::start_div('card mb-2 downloadcenter-category');
    
    // Category header with checkbox
    $checkboxattrs = [
        'type' => 'checkbox',
        'class' => 'downloadcenter-category-checkbox mr-2',
        'data-categoryid' => $category->id,
        'data-courseids' => implode(',', array_keys($courses))
    ];
    
    if ($catchecked) {
        $checkboxattrs['checked'] = 'checked';
    }
    if ($catindeterminate) {
        $checkboxattrs['data-indeterminate'] = '1';
    }

    $button = html_writer::tag('button', $category->get_formatted_name(), [
        'class' => 'btn btn-link text-left w-100',
        'data-toggle' => 'collapse',
        'data-target' => '#' . $collapseid,
        'aria-expanded' => $expanded ? 'true' : 'false',
        'aria-controls' => $collapseid,
    ]);
    
    $header = html_writer::empty_tag('input', $checkboxattrs) . $button;
    $html .= html_writer::tag('div', $header, ['class' => 'card-header p-0 d-flex align-items-center']);
    
    // Category content (courses and subcategories)
    $html .= html_writer::start_div('collapse' . ($expanded ? ' show' : ''), ['id' => $collapseid]);
    $html .= html_writer::start_div('card-body');
    
    // Render courses in this category
    if (!empty($courses)) {
        $html .= html_writer::start_tag('form', [
            'class' => 'downloadcenter-course-form',
            'data-categoryid' => $category->id
        ]);
        
        foreach ($courses as $course) {
            // Check access using the correct method for core_course_list_element
            if (!$course->can_access()) {
                continue;
            }
            
            $courseurl = local_downloadcenter_build_url($catids, ['courseid' => $course->id]);
            $courselabel = html_writer::link($courseurl, $course->get_formatted_name());
            
            $state = $courseStates[$course->id] ?? 'none';
            if ($state === 'partial') {
                $courselabel .= ' <span class="badge badge-info">' . 
                                get_string('selected', 'local_downloadcenter') . ' (partial)</span>';
            } else if ($state === 'full') {
                $courselabel .= ' <span class="badge badge-success">' . 
                                get_string('selected', 'local_downloadcenter') . '</span>';
            }
            
            $checkboxattrs = [
                'type' => 'checkbox',
                'class' => 'course-checkbox mr-2',
                'data-courseid' => $course->id,
                'data-categoryid' => $category->id
            ];
            
            if ($state === 'full') {
                $checkboxattrs['checked'] = 'checked';
            }
            if ($state === 'partial') {
                $checkboxattrs['data-indeterminate'] = '1';
            }
            
            $html .= html_writer::start_div('form-check mb-2');
            $html .= html_writer::empty_tag('input', $checkboxattrs);
            $html .= html_writer::tag('label', $courselabel, ['class' => 'form-check-label']);
            $html .= html_writer::end_div();
        }
        
        $html .= html_writer::end_tag('form');
    } else {
        $html .= $OUTPUT->notification(get_string('nocoursesfound', 'local_downloadcenter'),
            \core\output\notification::NOTIFY_WARNING);
    }
    
    // Render subcategories
    foreach ($category->get_children(['sort' => ['name' => 1]]) as $child) {
        $html .= local_downloadcenter_render_category_tree($child, $selection, $catids, $search);
    }
    
    $html .= html_writer::end_div(); // card-body
    $html .= html_writer::end_div(); // collapse
    $html .= html_writer::end_div(); // card
    
    return $html;
}

// Main category view
if (!empty($catids)) {
    $PAGE->set_context($systemcontext);
    $PAGE->set_url(local_downloadcenter_build_url($catids));
    $PAGE->set_title(get_string('navigationlink', 'local_downloadcenter'));
    $PAGE->set_heading($SITE->fullname);

    // Navigation
    $PAGE->navbar->add(get_string('courses'), new moodle_url('/course/management.php'));
    $PAGE->navbar->add(get_string('navigationlink', 'local_downloadcenter'),
                      local_downloadcenter_build_url($catids));

    // Load JavaScript for category tree
    $PAGE->requires->js_call_amd('local_downloadcenter/category_tree', 'init');
    
    // Add language strings for JavaScript
    $PAGE->requires->strings_for_js(['selected', 'saveselection'], 'local_downloadcenter');
    
    // Add inline JavaScript for AJAX auto-save
    $PAGE->requires->js_amd_inline("
        require(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
            // Auto-save function for course checkboxes
            $('.course-checkbox').on('change', function() {
                var checkbox = $(this);
                var courseid = checkbox.data('courseid');
                var checked = checkbox.is(':checked');
                
                // Clear indeterminate state when explicitly checked/unchecked
                checkbox.prop('indeterminate', false);
                checkbox.removeAttr('data-indeterminate');
                
                $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', {
                    action: 'togglecourse',
                    courseid: courseid,
                    checked: checked ? 1 : 0,
                    sesskey: M.cfg.sesskey
                }).done(function(response) {
                    if (response.status === 'ok') {
                        // Update visual feedback
                        var label = checkbox.next('label');
                        label.find('.badge').remove();
                        if (checked) {
                            label.append(' <span class=\"badge badge-success\">' + 
                                        M.str.local_downloadcenter.selected + '</span>');
                        }
                        
                        // Update parent category state
                        updateCategoryStates();
                    }
                }).fail(function() {
                    Notification.exception(new Error('Failed to save selection'));
                });
            });
            
            // Category checkbox handler
            $('.downloadcenter-category-checkbox').on('change', function() {
                var checkbox = $(this);
                var categoryid = checkbox.data('categoryid');
                var courseids = checkbox.data('courseids');
                var checked = checkbox.is(':checked');
                
                // Update all child course checkboxes
                var container = checkbox.closest('.downloadcenter-category');
                container.find('.course-checkbox').each(function() {
                    $(this).prop('checked', checked);
                    $(this).prop('indeterminate', false);
                    $(this).trigger('change');
                });
                
                // Save via AJAX
                $.post(M.cfg.wwwroot + '/local/downloadcenter/index.php', {
                    action: 'togglecategory',
                    categoryid: categoryid,
                    courseids: courseids,
                    checked: checked ? 1 : 0,
                    sesskey: M.cfg.sesskey
                });
            });
            
            // Update category states based on course selections
            function updateCategoryStates() {
                $('.downloadcenter-category').each(function() {
                    var container = $(this);
                    var checkbox = container.find('> .card-header .downloadcenter-category-checkbox');
                    var courseboxes = container.find('.course-checkbox');
                    var total = courseboxes.length;
                    var checked = courseboxes.filter(':checked').length;
                    var indeterminate = courseboxes.filter(function() {
                        return this.indeterminate || $(this).data('indeterminate');
                    }).length;
                    
                    if (total > 0) {
                        checkbox.prop('checked', checked === total);
                        checkbox.prop('indeterminate', (checked > 0 && checked < total) || indeterminate > 0);
                    }
                });
            }
            
            // Initialize indeterminate states on page load
            $('[data-indeterminate=\"1\"]').each(function() {
                this.indeterminate = true;
            });
            
            // Initial state calculation
            updateCategoryStates();
        });
    ");

    echo $OUTPUT->header();

    // Header section
    echo html_writer::start_div('downloadcenter-header card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h2', get_string('navigationlink', 'local_downloadcenter'), ['class' => 'card-title']);
    echo html_writer::tag('p', get_string('downloadcenter_desc', 'local_downloadcenter'), ['class' => 'text-muted']);

    // Search form
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

    // Show errors if any
    if (!empty($SESSION->local_downloadcenter_errors)) {
        foreach ($SESSION->local_downloadcenter_errors as $error) {
            echo $OUTPUT->notification($error, \core\output\notification::NOTIFY_ERROR);
        }
        unset($SESSION->local_downloadcenter_errors);
    }

    // Render category trees
    foreach ($catids as $cid) {
        $category = \core_course_category::get($cid, MUST_EXIST);
        echo local_downloadcenter_render_category_tree($category, $selection, $catids, $search);
    }

    // Current selection panel - Show always if there are selected courses OR items
    $hasAnySelection = !empty($selection);
    if ($hasAnySelection) {
        echo html_writer::start_div('card mt-4 border-success', ['id' => 'selection-panel']);
        echo html_writer::start_div('card-header bg-success text-white');
        echo html_writer::tag('h4', get_string('currentselection', 'local_downloadcenter'), ['class' => 'mb-0']);
        echo html_writer::end_div();
        echo html_writer::start_div('card-body');

        // List selected courses
        echo html_writer::start_tag('ul', ['class' => 'list-group mb-3']);
        $totalCourses = 0;
        foreach ($selection as $cid => $data) {
            $selectedcourse = $DB->get_record('course', ['id' => $cid]);
            if ($selectedcourse) {
                $totalCourses++;
                $badge = '';
                
                // Check if it's a full selection or partial
                if (isset($data['downloadall']) && $data['downloadall']) {
                    $badge = ' <span class="badge badge-success">Complete</span>';
                } else {
                    // Count selected items
                    $itemCount = 0;
                    foreach ($data as $key => $value) {
                        if (strpos($key, 'item_') === 0 && $value) {
                            $itemCount++;
                        }
                    }
                    if ($itemCount > 0) {
                        $badge = ' <span class="badge badge-info">' . $itemCount . ' items</span>';
                    }
                }
                
                echo html_writer::tag('li',
                    html_writer::tag('strong', $selectedcourse->fullname) .
                    ' (' . $selectedcourse->shortname . ')' . $badge,
                    ['class' => 'list-group-item']
                );
            }
        }
        echo html_writer::end_tag('ul');

        // Show summary - use a generic message if string doesn't exist
        $summaryMsg = $totalCourses . ' course' . ($totalCourses > 1 ? 's' : '') . ' selected';
        echo html_writer::tag('p', 
            $summaryMsg,
            ['class' => 'text-muted mb-3']
        );

        // Action buttons
        echo html_writer::start_div('btn-group');

        // Create download button
        $downloadbutton = new single_button(
            local_downloadcenter_build_url([], ['action' => 'download']),
            get_string('downloadselection', 'local_downloadcenter'),
            'post'
        );
        $downloadbutton->class = 'btn btn-success mr-2';
        echo $OUTPUT->render($downloadbutton);

        // Create clear button
        $clearbutton = new single_button(
            local_downloadcenter_build_url($catids, ['action' => 'clear']),
            get_string('clearselection', 'local_downloadcenter'),
            'post'
        );
        $clearbutton->class = 'btn btn-danger';
        echo $OUTPUT->render($clearbutton);

        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    } else {
        // Show message when no selection
        echo html_writer::div(
            html_writer::tag('p', get_string('nocoursesselected', 'local_downloadcenter'), 
                            ['class' => 'text-muted text-center py-4']),
            'card mt-4 border-secondary'
        );
    }

    echo $OUTPUT->footer();
}