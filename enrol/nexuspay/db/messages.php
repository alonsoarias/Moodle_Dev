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
 * Defines message providers for NexusPay enrollments.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Message providers for NexusPay enrollment plugin.
 */
$messageproviders = [
    
    // Enrollment expiry notification.
    'expiry_notification' => [
        'capability' => 'enrol/nexuspay:manage',
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    
    // Payment successful notification.
    'payment_successful' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    
    // Payment failed notification.
    'payment_failed' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    
    // Trial started notification.
    'trial_started' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    
    // Trial expiring notification.
    'trial_expiring' => [
        'defaults' => [
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN,
            'email' => MESSAGE_PERMITTED,
        ],
    ],
    
];