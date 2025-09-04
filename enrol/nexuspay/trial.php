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
 * Free trial enrollment handler for NexusPay.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../config.php');

global $USER, $DB, $CFG;

defined('MOODLE_INTERNAL') || die();

// Must be logged in.
require_login();
require_sesskey();

// Set page context.
$PAGE->set_url('/enrol/nexuspay/trial.php');
$PAGE->set_context(context_system::instance());

// Get enrollment instance ID.
$instanceid = required_param('instanceid', PARAM_INT);

// Get enrollment instance.
$instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $instanceid], '*', MUST_EXIST);

// Get course and context.
$course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

// Prepare success redirect URL.
$successurl = \enrol_nexuspay\payment\service_provider::get_success_url('fee', $instanceid);

// Check if free trial is configured.
if (empty($instance->customint6)) {
    print_error('nofreetrial', 'enrol_nexuspay', $successurl);
}

// Check if user has already used trial for this course.
$hasusedtrial = $DB->record_exists('enrol_nexuspay', [
    'courseid' => $instance->courseid,
    'userid' => $USER->id
]);

if ($hasusedtrial) {
    // User has already used their trial.
    redirect(
        $successurl,
        get_string('trialused', 'enrol_nexuspay'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Check if user is already enrolled.
if (is_enrolled($context, $USER->id, '', true)) {
    // Already enrolled, redirect to course.
    redirect($successurl);
}

// Get payment account details.
$payable = helper::get_payable('enrol_nexuspay', 'fee', $instanceid);

// Create a zero-cost payment record for the trial.
$currency = $payable->get_currency() ?: 'COP';

// Save payment record (with 0 amount for trial).
$paymentid = helper::save_payment(
    $payable->get_account_id(),
    'enrol_nexuspay',
    'fee',
    $instanceid,
    $USER->id,
    0, // Zero cost for trial.
    $currency,
    'nexuspay_trial' // Gateway identifier for trials.
);

// Process the trial enrollment.
try {
    // Deliver the order (enroll the user).
    $success = helper::deliver_order(
        'enrol_nexuspay',
        'fee',
        $instanceid,
        $paymentid,
        $USER->id
    );
    
    if ($success) {
        // Log the trial activation.
        $event = \core\event\course_viewed::create([
            'objectid' => $course->id,
            'context' => $context,
            'other' => [
                'enrolmethod' => 'nexuspay',
                'trial' => true
            ]
        ]);
        $event->trigger();
        
        // Redirect to course with success message.
        redirect(
            $successurl,
            get_string('trialactivated', 'enrol_nexuspay'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        // Enrollment failed.
        print_error('enrollmentfailed', 'enrol_nexuspay', $successurl);
    }
} catch (Exception $e) {
    // Handle any exceptions.
    debugging('Trial enrollment failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
    print_error('enrollmentfailed', 'enrol_nexuspay', $successurl);
}