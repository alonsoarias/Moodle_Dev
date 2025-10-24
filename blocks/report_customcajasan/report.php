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
$systemcontext = context_system::instance();
global $DB, $SESSION;

// Get parameters
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$blockinstanceid = optional_param('blockinstanceid', 0, PARAM_INT);

// Get block restrictions
$blockrestrictions = block_report_customcajasan_get_block_restrictions($blockinstanceid);
$allowedcourses = $blockrestrictions['courses'];
$allowedcategories = $blockrestrictions['expandedcategories'];

// Determine context
if ($courseid && $courseid != SITEID) {
    $context = context_course::instance($courseid, MUST_EXIST);
} else if ($categoryid) {
    $context = context_coursecat::instance($categoryid, MUST_EXIST);
} else if (!empty($blockrestrictions['parentcontext'])) {
    $context = $blockrestrictions['parentcontext'];
} else {
    $context = $systemcontext;
}

// Verification logic based on context
$hasaccess = false;

if ($context->contextlevel == CONTEXT_SYSTEM) {
    // In system context: only site administrators
    $hasaccess = has_capability('moodle/site:config', $context);
    
} else if ($context->contextlevel == CONTEXT_COURSE) {
    // In course context: users with editing permissions
    $hasaccess = has_capability('moodle/course:update', $context) ||
                 has_capability('moodle/course:manageactivities', $context);
    
    // If block has restrictions, verify the course is allowed
    if ($hasaccess && !empty($allowedcourses)) {
        $courseallowed = in_array((int)$courseid, $allowedcourses, true);
        
        // If not in allowed courses, check if its category is allowed
        if (!$courseallowed && !empty($allowedcategories)) {
            $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
            if ($coursecategory !== false) {
                $courseallowed = in_array((int)$coursecategory, $allowedcategories, true);
            }
        }
        
        $hasaccess = $courseallowed;
    } else if ($hasaccess && !empty($allowedcategories)) {
        // If only categories are restricted, verify course belongs to allowed category
        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
        if ($coursecategory !== false) {
            $hasaccess = in_array((int)$coursecategory, $allowedcategories, true);
        } else {
            $hasaccess = false;
        }
    }
    
} else if ($context->contextlevel == CONTEXT_COURSECAT) {
    // In category context: only administrators
    $hasaccess = has_capability('moodle/site:config', $systemcontext);
}

// Throw exception if no access
if (!$hasaccess) {
    throw new required_capability_exception(
        $context,
        'block/report_customcajasan:viewreport',
        'nopermissions',
        ''
    );
}

// Apply additional filters based on restrictions
if (!empty($allowedcategories) && !empty($categoryid) && 
    !in_array((int)$categoryid, $allowedcategories, true)) {
    $categoryid = 0;
}

if (!empty($courseid) && (!empty($allowedcourses) || !empty($allowedcategories))) {
    $courseallowed = false;
    
    if (!empty($allowedcourses)) {
        $courseallowed = in_array((int)$courseid, $allowedcourses, true);
    }
    
    if (!$courseallowed && !empty($allowedcategories)) {
        $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
        if ($coursecategory !== false) {
            $courseallowed = in_array((int)$coursecategory, $allowedcategories, true);
        }
    }
    
    if (!$courseallowed) {
        $courseid = 0;
    }
}

if (!empty($categoryid) && !empty($courseid) &&
    !report_customcajasan_course_matches_category($courseid, $categoryid)) {
    $courseid = 0;
}

if (empty($courseid) && $context->contextlevel === CONTEXT_COURSE) {
    $context = !empty($blockrestrictions['parentcontext']) ? 
               $blockrestrictions['parentcontext'] : $systemcontext;
}

if (empty($categoryid) && $context->contextlevel === CONTEXT_COURSECAT) {
    $context = !empty($blockrestrictions['parentcontext']) ? 
               $blockrestrictions['parentcontext'] : $systemcontext;
}

// Increase limits for large reports
if (function_exists('set_time_limit')) {
    set_time_limit(0);
}
raise_memory_limit(MEMORY_EXTRA);

// Page setup
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/blocks/report_customcajasan/report.php', [
    'courseid' => $courseid,
    'categoryid' => $categoryid,
    'blockinstanceid' => $blockinstanceid,
]));
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('report_title', 'block_report_customcajasan'));
$PAGE->set_heading(get_string('report_title', 'block_report_customcajasan'));
$PAGE->requires->jquery();
$PAGE->requires->js_call_amd('block_report_customcajasan/report', 'init');

// Get filter parameters for initial page load
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

// Check if at least one filter is applied for initial data load
$filter_selected = !empty($categoryid) || !empty($courseid) || !empty($idnumber) || 
                  !empty($firstname) || !empty($lastname) || !empty($estado) || 
                  !empty($startdate) || !empty($enddate);

// Handle download requests
if ($download) {
    require_sesskey();

    $session_filters = isset($SESSION->report_customcajasan_filters) ?
        $SESSION->report_customcajasan_filters : array();
    
    $filters = array();
    
    if (!empty($categoryid)) {
        $filters['category'] = $categoryid;
    } else if (!empty($session_filters['category'])) {
        $filters['category'] = $session_filters['category'];
    }
    
    if (!empty($courseid)) {
        $filters['course'] = $courseid;
    } else if (!empty($session_filters['course'])) {
        $filters['course'] = $session_filters['course'];
    }
    
    if (!empty($idnumber)) {
        $filters['idnumber'] = $idnumber;
    } else if (!empty($session_filters['idnumber'])) {
        $filters['idnumber'] = $session_filters['idnumber'];
    }
    
    if (!empty($firstname)) {
        $filters['firstname'] = $firstname;
    } else if (!empty($session_filters['firstname'])) {
        $filters['firstname'] = $session_filters['firstname'];
    }
    
    if (!empty($lastname)) {
        $filters['lastname'] = $lastname;
    } else if (!empty($session_filters['lastname'])) {
        $filters['lastname'] = $session_filters['lastname'];
    }
    
    if (!empty($estado)) {
        $filters['estado'] = $estado;
    } else if (!empty($session_filters['estado'])) {
        $filters['estado'] = $session_filters['estado'];
    }
    
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

    if (!empty($filters['category']) && !empty($filters['course']) &&
            !report_customcajasan_course_matches_category($filters['course'], $filters['category'])) {
        $filters['course'] = 0;
    }

    $filters['allowedcourses'] = $allowedcourses;
    $filters['allowedcategories'] = $allowedcategories;
    $filters['blockinstanceid'] = $blockinstanceid;
    
    $has_filter = false;
    foreach ($filters as $key => $filter_value) {
        if (in_array($key, ['allowedcourses', 'allowedcategories', 'blockinstanceid'], true)) {
            continue;
        }
        if (!empty($filter_value)) {
            $has_filter = true;
            break;
        }
    }
    
    if (!$has_filter) {
        redirect(
            new moodle_url('/blocks/report_customcajasan/report.php', ['blockinstanceid' => $blockinstanceid]),
            get_string('filters_required', 'block_report_customcajasan'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
        exit;
    }
    
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
}

// Display form and report
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('report_title', 'block_report_customcajasan'));

// Color codes for status
echo html_writer::start_tag('div', array('class' => 'alert alert-info'));
echo html_writer::tag('strong', get_string('status_explanation', 'block_report_customcajasan') . ': ');
echo html_writer::tag('span', get_string('state_aprobado', 'block_report_customcajasan'), array('class' => 'badge badge-success p-2 mr-2'));
echo html_writer::tag('span', get_string('state_encurso', 'block_report_customcajasan'), array('class' => 'badge badge-warning p-2 mr-2'));
echo html_writer::tag('span', get_string('state_noiniciado', 'block_report_customcajasan'), array('class' => 'badge badge-danger p-2 mr-2'));
echo html_writer::tag('span', get_string('state_soloconsulta', 'block_report_customcajasan'), array('class' => 'badge badge-secondary p-2 mr-2'));
echo html_writer::empty_tag('br');
echo html_writer::tag('small', get_string('status_note', 'block_report_customcajasan'));
echo html_writer::end_tag('div');

echo html_writer::tag('div', 
    html_writer::tag('p', 
        html_writer::tag('strong', get_string('filters_required', 'block_report_customcajasan')),
        ['class' => 'small font-italic']
    ),
    ['class' => 'alert alert-warning']
);

// Filter form
echo html_writer::start_tag('form', array('id' => 'report-form', 'method' => 'get', 'class' => 'mb-4'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'blockinstanceid', 'value' => $blockinstanceid));
echo html_writer::start_div('container-fluid');

// First row of filters
echo html_writer::start_div('row');

// Category filter
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('option_category', 'block_report_customcajasan'), array('for' => 'categoryid'));
$categories = report_customcajasan_get_categories($blockrestrictions['categories'], $allowedcourses);
$categoryoptions = array();
$categoryoptions[''] = get_string('option_all', 'block_report_customcajasan');
foreach ($categories as $category) {
    $indent = str_repeat('&nbsp;', $category->depth * 3);
    $categoryoptions[$category->id] = $indent . $category->name;
}
echo html_writer::select($categoryoptions, 'categoryid', $categoryid, false, array('class' => 'form-control', 'id' => 'categoryid'));
echo html_writer::end_div();

// Course filter
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('option_course', 'block_report_customcajasan'), array('for' => 'courseid'));
$courses = report_customcajasan_get_courses($categoryid, $allowedcourses, $allowedcategories);
$courseoptions = array();
$courseoptions[''] = get_string('option_all', 'block_report_customcajasan');
foreach ($courses as $course) {
    $courseoptions[$course->id] = $course->fullname;
}
echo html_writer::select($courseoptions, 'courseid', $courseid, false, array('class' => 'form-control', 'id' => 'courseid'));
echo html_writer::end_div();

// Estado filter
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('option_estado', 'block_report_customcajasan'), array('for' => 'estado'));
$estadoptions = report_customcajasan_get_states();
echo html_writer::select($estadoptions, 'estado', $estado, false, array('class' => 'form-control', 'id' => 'estado'));
echo html_writer::end_div();

echo html_writer::end_div();

// Second row of filters
echo html_writer::start_div('row');

// ID number filter
echo html_writer::start_div('col-md-4 mb-3');
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
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('option_firstname', 'block_report_customcajasan'), array('for' => 'firstname'));

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

// Last name filter with alphabet
echo html_writer::start_div('col-md-4 mb-3');
echo html_writer::tag('label', get_string('option_lastname', 'block_report_customcajasan'), array('for' => 'lastname'));

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

echo html_writer::end_div();

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

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_tag('form');

// Results container
echo html_writer::start_div('report-results mb-4', array('id' => 'report-results'));

if (!$filter_selected) {
    echo html_writer::tag('div', get_string('select_filter_first', 'block_report_customcajasan'), array('class' => 'alert alert-info'));
}

echo html_writer::end_div();

// Per page selector
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

// Download options
echo html_writer::start_div('download-options mt-3');
echo html_writer::start_tag('form', array('id' => 'downloadForm', 'method' => 'get'));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'blockinstanceid', 'value' => $blockinstanceid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));

// Hidden fields to preserve filters
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'categoryid', 'value' => $categoryid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'courseid', 'value' => $courseid));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'idnumber', 'value' => $idnumber));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'firstname', 'value' => $firstname));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'lastname', 'value' => $lastname));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'estado', 'value' => $estado));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'startdate', 'value' => $startdate));
echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'enddate', 'value' => $enddate));

if ($filter_selected) {
    $total_records = report_customcajasan_count_data(array(
        'category' => $categoryid,
        'course' => $courseid,
        'idnumber' => $idnumber,
        'firstname' => $firstname,
        'lastname' => $lastname,
        'estado' => $estado,
        'startdate' => !empty($startdate) ? strtotime($startdate) : '',
        'enddate' => !empty($enddate) ? strtotime($enddate . ' 23:59:59') : '',
        'allowedcourses' => $allowedcourses,
        'allowedcategories' => $allowedcategories,
    ));
    
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

echo html_writer::end_div();
echo html_writer::end_tag('form');
echo html_writer::end_div();

echo $OUTPUT->footer();