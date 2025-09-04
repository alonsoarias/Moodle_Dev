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
 * NexusPay enrolment plugin library functions.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check if the given password matches a group enrollment key in the specified course.
 *
 * @param  int $courseid Course ID
 * @param  string $password Group password to check
 * @return int|false Group ID if match found, false otherwise
 */
function enrol_nexuspay_check_group_enrolment_key($courseid, $password) {
    global $DB;

    if (empty($password)) {
        return false;
    }

    $groups = $DB->get_records('groups', ['courseid' => $courseid], 'id ASC', 'id, enrolmentkey');

    foreach ($groups as $group) {
        if (empty($group->enrolmentkey)) {
            continue;
        }
        
        // Check for exact match (case-sensitive).
        if ($group->enrolmentkey === $password) {
            return $group->id;
        }
    }
    
    return false;
}

/**
 * Format currency amount for display.
 * Handles Colombian Peso (COP) and US Dollar (USD) formatting.
 *
 * @param float $amount Amount to format
 * @param string $currency Currency code (COP or USD)
 * @return string Formatted currency string
 */
function enrol_nexuspay_format_currency($amount, $currency = 'COP') {
    $amount = (float) $amount;
    
    switch ($currency) {
        case 'COP':
            // Colombian Peso formatting: $1.234.567
            $formatted = '$' . number_format($amount, 0, ',', '.');
            break;
        case 'USD':
            // US Dollar formatting: $1,234.56
            $formatted = '$' . number_format($amount, 2, '.', ',');
            break;
        default:
            // Generic formatting for other currencies
            $formatted = \core_payment\helper::get_cost_as_string($amount, $currency);
            break;
    }
    
    return $formatted;
}

/**
 * Calculate the time remaining until enrollment expiry.
 *
 * @param int $timeend End timestamp of enrollment
 * @return string Human-readable time remaining
 */
function enrol_nexuspay_get_time_remaining($timeend) {
    if ($timeend == 0) {
        return get_string('unlimited');
    }
    
    $remaining = $timeend - time();
    
    if ($remaining <= 0) {
        return get_string('expired', 'enrol');
    }
    
    return format_time($remaining);
}

/**
 * Check if user is eligible for free trial.
 *
 * @param stdClass $instance Enrollment instance
 * @param int $userid User ID to check
 * @return bool True if eligible for free trial
 */
function enrol_nexuspay_is_trial_eligible($instance, $userid) {
    global $DB;
    
    // Check if trial period is configured.
    if (empty($instance->customint6)) {
        return false;
    }
    
    // Check if user has already used trial for this course.
    $hasusedtrial = $DB->record_exists('enrol_nexuspay', [
        'courseid' => $instance->courseid,
        'userid' => $userid
    ]);
    
    return !$hasusedtrial;
}

/**
 * Get enrollment period description.
 *
 * @param stdClass $instance Enrollment instance
 * @return string Human-readable period description
 */
function enrol_nexuspay_get_period_description($instance) {
    if (empty($instance->enrolperiod) && empty($instance->customint7)) {
        return get_string('unlimited');
    }
    
    // Handle special period types (month/year).
    if (!empty($instance->customchar1) && !empty($instance->customint7)) {
        $count = $instance->customint7;
        switch ($instance->customchar1) {
            case 'month':
                return get_string('nummonths', 'moodle', $count);
            case 'year':
                return get_string('numyears', 'moodle', $count);
            case 'week':
                return get_string('numweeks', 'moodle', $count);
            case 'day':
                return get_string('numdays', 'moodle', $count);
            case 'hour':
                return get_string('numhours', 'moodle', $count);
            case 'minute':
                return get_string('numminutes', 'moodle', $count);
        }
    }
    
    // Handle standard enrollment period.
    if (!empty($instance->enrolperiod)) {
        return format_time($instance->enrolperiod);
    }
    
    return get_string('unlimited');
}

/**
 * Send enrollment expiry notification to user.
 *
 * @param stdClass $user User object
 * @param stdClass $course Course object
 * @param stdClass $instance Enrollment instance
 * @param int $timeend Enrollment end time
 * @return bool True if message was sent successfully
 */
function enrol_nexuspay_send_expiry_notification($user, $course, $instance, $timeend) {
    global $CFG;
    
    $message = new \core\message\message();
    $message->component = 'enrol_nexuspay';
    $message->name = 'expiry_notification';
    $message->userfrom = \core_user::get_noreply_user();
    $message->userto = $user;
    $message->subject = get_string('expirymessageenrolledsubject', 'enrol_nexuspay');
    
    $a = new stdClass();
    $a->user = fullname($user);
    $a->course = format_string($course->fullname);
    $a->timeend = userdate($timeend);
    $a->enroller = \core_user::get_support_user();
    
    $message->fullmessage = get_string('expirymessageenrolledbody', 'enrol_nexuspay', $a);
    $message->fullmessageformat = FORMAT_PLAIN;
    $message->fullmessagehtml = '<p>' . nl2br($message->fullmessage) . '</p>';
    $message->notification = 1;
    
    return message_send($message);
}

/**
 * Get available payment gateways for the given context.
 *
 * @param context $context Course context
 * @return array Array of available payment gateway names
 */
function enrol_nexuspay_get_payment_gateways($context) {
    $accounts = \core_payment\helper::get_payment_accounts_menu($context);
    $gateways = [];
    
    foreach ($accounts as $accountid => $accountname) {
        $account = \core_payment\helper::get_payment_account($accountid);
        if ($account) {
            $enabledgateways = \core_payment\helper::get_enabled_payment_gateways($account);
            foreach ($enabledgateways as $gateway) {
                $gateways[$gateway->get_name()] = $gateway->get_display_name();
            }
        }
    }
    
    return $gateways;
}