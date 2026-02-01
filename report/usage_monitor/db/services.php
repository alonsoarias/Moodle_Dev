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
 * External services definition for report_usage_monitor.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'report_usage_monitor_get_monitor_stats' => [
        'classname'    => 'report_usage_monitor\external\get_monitor_stats',
        'description'  => 'Get current usage statistics for external system integration.',
        'type'         => 'read',
        'capabilities' => 'report/usage_monitor:view',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'report_usage_monitor_get_notification_history' => [
        'classname'    => 'report_usage_monitor\external\get_notification_history',
        'description'  => 'Get notification history with pagination.',
        'type'         => 'read',
        'capabilities' => 'report/usage_monitor:view',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'report_usage_monitor_get_usage_data' => [
        'classname'    => 'report_usage_monitor\external\get_usage_data',
        'description'  => 'Get precalculated usage data (lightweight endpoint).',
        'type'         => 'read',
        'capabilities' => 'report/usage_monitor:view',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'report_usage_monitor_set_usage_thresholds' => [
        'classname'    => 'report_usage_monitor\external\set_usage_thresholds',
        'description'  => 'Set usage thresholds for users and disk.',
        'type'         => 'write',
        'capabilities' => 'report/usage_monitor:manage',
        'ajax'         => true,
        'services'     => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];

// Service definition.
$services = [
    'Usage Monitor API' => [
        'functions' => [
            'report_usage_monitor_get_monitor_stats',
            'report_usage_monitor_get_notification_history',
            'report_usage_monitor_get_usage_data',
            'report_usage_monitor_set_usage_thresholds',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'report_usage_monitor',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ],
];
