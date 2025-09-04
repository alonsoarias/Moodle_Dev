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
 * Payment page for NexusPay enrollment plugin.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../config.php');
require_once('lib.php');

global $CFG, $USER, $DB, $OUTPUT, $PAGE;

defined('MOODLE_INTERNAL') || die();

require_login();

// Get parameters.
$id = required_param('id', PARAM_INT); // Enrollment instance ID.
$courseid = required_param('courseid', PARAM_INT);
$groupkey = optional_param('groupkey', 0, PARAM_INT);
$password = optional_param('password', '', PARAM_TEXT);
$force = optional_param('force', 0, PARAM_INT);

// Get course and enrollment instance.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id, MUST_EXIST);
$instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $id], '*', MUST_EXIST);

// Check group password if provided.
if ($groupkey && !empty($password)) {
    $redirecturl = $force 
        ? $CFG->wwwroot . '/enrol/nexuspay/pay.php?courseid=' . $course->id . '&id=' . $id . '&force=' . $force
        : $CFG->wwwroot . '/enrol/index.php?id=' . $course->id;
    
    if ($groupid = enrol_nexuspay_check_group_enrolment_key($course->id, $password)) {
        // Save group assignment for later.
        $groupassignment = new stdClass();
        $groupassignment->userid = $USER->id;
        $groupassignment->courseid = $courseid;
        $groupassignment->instanceid = $id;
        $groupassignment->groupid = $groupid;
        $groupassignment->timecreated = time();
        
        // Delete any existing group assignment.
        $DB->delete_records('enrol_nexuspay_groups', [
            'userid' => $USER->id,
            'courseid' => $courseid,
            'instanceid' => $id
        ]);
        
        // Insert new group assignment.
        $DB->insert_record('enrol_nexuspay_groups', $groupassignment);
        
        redirect($redirecturl, get_string('groupsuccess', 'enrol_nexuspay'), null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect($redirecturl, get_string('passwordinvalid', 'enrol_self'), null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Check if user is already enrolled.
$isenrolled = is_enrolled($context, $USER, '', false);

// If not enrolled and not forcing payment, redirect to enrollment page.
if (!$isenrolled && !$force) {
    redirect($CFG->wwwroot . '/enrol/index.php?id=' . $course->id);
}

// Set up the page.
$PAGE->set_course($course);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_url('/enrol/nexuspay/pay.php', ['courseid' => $course->id, 'id' => $id]);

// Page configuration.
$PAGE->add_body_class('limitedwidth');
$PAGE->set_cacheable(false);

// If uninterrupted payment is enabled, refresh page periodically.
if (!empty($instance->customint5)) {
    $PAGE->set_periodic_refresh_delay(120);
}

// Page title and heading.
$PAGE->set_title($course->shortname . ': ' . get_string('pluginname', 'enrol_nexuspay'));
$PAGE->set_heading($course->fullname);
$PAGE->set_secondary_navigation(false);

// Navigation breadcrumbs.
$PAGE->navbar->add(get_string('courses'), new moodle_url('/course/index.php'));
$PAGE->navbar->add($course->fullname, new moodle_url('/course/view.php', ['id' => $course->id]));
$PAGE->navbar->add(get_string('pluginname', 'enrol_nexuspay'));

// Output header.
echo $OUTPUT->header();

// Get plugin and display payment form.
$plugin = enrol_get_plugin('nexuspay');
if (!$plugin) {
    throw new coding_exception('NexusPay enrollment plugin not found');
}

// Generate payment form.
$paymentform = '';
if (method_exists($plugin, 'show_payment_info')) {
    // Use private method through reflection if needed.
    $reflection = new ReflectionMethod($plugin, 'show_payment_info');
    $reflection->setAccessible(true);
    $paymentform = $reflection->invoke($plugin, $instance, true);
} else {
    // Fallback to generating payment form directly.
    ob_start();
    
    // Calculate cost.
    $cost = \enrol_nexuspay\payment\service_provider::get_uninterrupted_cost($instance);
    $currency = $instance->currency ?: 'COP';
    
    if ($cost > 0) {
        $coststring = \core_payment\helper::get_cost_as_string($cost, $currency);
        
        $template = [
            'isguestuser' => isguestuser() || !isloggedin(),
            'cost' => $coststring,
            'instanceid' => $instance->id,
            'instancename' => $instance->name ?: get_string('pluginname', 'enrol_nexuspay'),
            'courseid' => $instance->courseid,
            'description' => get_string('purchasedescription', 'enrol_nexuspay', format_string($course->fullname, true, ['context' => $context])),
            'successurl' => \enrol_nexuspay\payment\service_provider::get_success_url('fee', $instance->id)->out(false),
            'sesskey' => sesskey(),
            'force' => true,
        ];
        
        echo $OUTPUT->render_from_template('enrol_nexuspay/payment_region', $template);
    } else {
        echo '<div class="alert alert-info">' . get_string('nocost', 'enrol_nexuspay') . '</div>';
    }
    
    $paymentform = ob_get_clean();
}

// Display payment form or notice.
if (!empty($paymentform)) {
    echo $OUTPUT->heading(get_string('renewenrolment', 'enrol_nexuspay'), 2);
    
    // Display course info box.
    $courserenderer = $PAGE->get_renderer('core', 'course');
    if (method_exists($courserenderer, 'course_info_box')) {
        echo $courserenderer->course_info_box($course);
    }
    
    echo $OUTPUT->box($paymentform, 'generalbox');
} else {
    // No payment form available.
    echo $OUTPUT->notification(get_string('notenrollable', 'enrol'), \core\output\notification::NOTIFY_WARNING);
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $course->id]));
}

// Output footer.
echo $OUTPUT->footer();