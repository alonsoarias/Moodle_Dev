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
 * External function to get monitor statistics.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

require_once(__DIR__ . '/../../locallib.php');

/**
 * Get monitor statistics external function.
 */
class get_monitor_stats extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get current usage statistics for external system integration.
     *
     * @return array The statistics data
     */
    public static function execute(): array {
        global $DB, $CFG, $SITE;

        // Validate context and capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            throw new \moodle_exception('plugin_disabled_hostname', 'report_usage_monitor');
        }

        // Get plugin configuration.
        $reportconfig = get_config('report_usage_monitor');

        // Calculate disk usage.
        $diskusagebytes = (int)($reportconfig->totalusagereadable ?? 0);
        $diskusagedb = (int)($reportconfig->totalusagereadabledb ?? 0);
        $diskusage = $diskusagebytes + $diskusagedb;
        $quotadisk = ((int)($reportconfig->disk_quota ?? 0)) * 1024 * 1024 * 1024;

        // Prevent division by zero.
        $diskpercent = $quotadisk > 0 ? calculate_threshold_percentage($diskusage, $quotadisk) : 0;

        // Calculate user usage.
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
        $userthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $userspercent = $userthreshold > 0 ? calculate_threshold_percentage($userstoday, $userthreshold) : 0;

        // Get directory analysis from precalculated data.
        $diranalysisjson = $reportconfig->dir_analysis ?? '{}';
        $diranalysis = json_decode($diranalysisjson, true);
        if (empty($diranalysis) || !is_array($diranalysis)) {
            $diranalysis = [
                'database' => 0,
                'filedir' => 0,
                'cache' => 0,
                'others' => 0,
            ];
        }

        // Get largest courses from precalculated data.
        $largestcoursesjson = $reportconfig->largest_courses ?? '[]';
        $largestcourses = json_decode($largestcoursesjson);
        if (empty($largestcourses)) {
            $largestcourses = [];
        }

        // Validate timestamps.
        $lastdiskcalc = self::validate_timestamp($reportconfig->lastexecutioncalculate ?? 0);
        $lastuserscalc = self::validate_timestamp($reportconfig->lastexecution ?? 0);
        $max90daysdate = self::validate_timestamp($reportconfig->max_userdaily_for_90_days_date ?? 0);

        // Build response structure.
        $response = [
            'site_info' => [
                'name' => format_string($SITE->fullname),
                'shortname' => format_string($SITE->shortname),
                'moodle_version' => (int)$CFG->version,
                'moodle_release' => $CFG->release,
                'course_count' => $DB->count_records('course'),
                'user_count' => $DB->count_records('user', ['deleted' => 0]) - 1,
                'backup_auto_max_kept' => (int)get_config('backup', 'backup_auto_max_kept'),
            ],
            'disk_usage' => [
                'total_bytes' => $diskusage,
                'total_readable' => display_size($diskusage),
                'quota_bytes' => $quotadisk,
                'quota_readable' => display_size($quotadisk),
                'percentage' => round($diskpercent, 2),
                'details' => self::build_disk_details($diranalysis, $diskusage),
            ],
            'user_usage' => [
                'daily_users' => $userstoday,
                'threshold' => $userthreshold,
                'percentage' => round($userspercent, 2),
                'max_90_days' => (int)($reportconfig->max_userdaily_for_90_days_users ?? 0),
                'max_90_days_date' => $max90daysdate > 0 ? date('Y-m-d', $max90daysdate) : null,
            ],
            'largest_courses' => self::format_courses($largestcourses),
            'timestamps' => [
                'disk_calculation' => $lastdiskcalc,
                'users_calculation' => $lastuserscalc,
            ],
            'growth_rates' => [
                'disk' => [
                    'monthly_percent' => calculate_growth_rate('disk'),
                    'projected_days_to_threshold' => self::safe_project_limit(
                        $diskusage,
                        $quotadisk * 0.9,
                        calculate_growth_rate('disk')
                    ),
                ],
                'users' => [
                    'monthly_percent' => calculate_growth_rate('users'),
                    'projected_days_to_threshold' => self::safe_project_limit(
                        $userstoday,
                        $userthreshold * 0.9,
                        calculate_growth_rate('users')
                    ),
                ],
            ],
        ];

        return $response;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'site_info' => new external_single_structure([
                'name' => new external_value(PARAM_TEXT, 'Site name'),
                'shortname' => new external_value(PARAM_TEXT, 'Site short name'),
                'moodle_version' => new external_value(PARAM_INT, 'Moodle version number'),
                'moodle_release' => new external_value(PARAM_TEXT, 'Moodle release string'),
                'course_count' => new external_value(PARAM_INT, 'Total number of courses'),
                'user_count' => new external_value(PARAM_INT, 'Total number of active users'),
                'backup_auto_max_kept' => new external_value(PARAM_INT, 'Maximum automated backups kept'),
            ]),
            'disk_usage' => new external_single_structure([
                'total_bytes' => new external_value(PARAM_INT, 'Total disk usage in bytes'),
                'total_readable' => new external_value(PARAM_TEXT, 'Total disk usage in readable format'),
                'quota_bytes' => new external_value(PARAM_INT, 'Disk quota in bytes'),
                'quota_readable' => new external_value(PARAM_TEXT, 'Disk quota in readable format'),
                'percentage' => new external_value(PARAM_FLOAT, 'Disk usage percentage'),
                'details' => new external_single_structure([
                    'database' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Database size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Database size readable'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Database percentage of total'),
                    ]),
                    'filedir' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'File directory size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'File directory size readable'),
                        'percentage' => new external_value(PARAM_FLOAT, 'File directory percentage'),
                    ]),
                    'cache' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Cache size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Cache size readable'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Cache percentage'),
                    ]),
                    'backup' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Backup size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Backup size readable'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Backup percentage'),
                    ]),
                    'others' => new external_single_structure([
                        'bytes' => new external_value(PARAM_INT, 'Other files size in bytes'),
                        'readable' => new external_value(PARAM_TEXT, 'Other files size readable'),
                        'percentage' => new external_value(PARAM_FLOAT, 'Other files percentage'),
                    ]),
                ]),
            ]),
            'user_usage' => new external_single_structure([
                'daily_users' => new external_value(PARAM_INT, 'Daily active users count'),
                'threshold' => new external_value(PARAM_INT, 'User threshold limit'),
                'percentage' => new external_value(PARAM_FLOAT, 'User usage percentage'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in last 90 days'),
                'max_90_days_date' => new external_value(PARAM_TEXT, 'Date of maximum users', VALUE_OPTIONAL),
            ]),
            'largest_courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'size_bytes' => new external_value(PARAM_INT, 'Course size in bytes'),
                    'size_readable' => new external_value(PARAM_TEXT, 'Course size readable'),
                    'backup_size_bytes' => new external_value(PARAM_INT, 'Backup size in bytes'),
                    'backup_size_readable' => new external_value(PARAM_TEXT, 'Backup size readable'),
                    'percentage' => new external_value(PARAM_FLOAT, 'Percentage of total storage'),
                    'backup_count' => new external_value(PARAM_INT, 'Number of backups'),
                ])
            ),
            'timestamps' => new external_single_structure([
                'disk_calculation' => new external_value(PARAM_INT, 'Last disk calculation timestamp'),
                'users_calculation' => new external_value(PARAM_INT, 'Last users calculation timestamp'),
            ]),
            'growth_rates' => new external_single_structure([
                'disk' => new external_single_structure([
                    'monthly_percent' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate'),
                    'projected_days_to_threshold' => new external_value(PARAM_INT, 'Days to reach threshold'),
                ]),
                'users' => new external_single_structure([
                    'monthly_percent' => new external_value(PARAM_FLOAT, 'Monthly user growth rate'),
                    'projected_days_to_threshold' => new external_value(PARAM_INT, 'Days to reach threshold'),
                ]),
            ]),
        ]);
    }

    /**
     * Validate and return a timestamp, with fallback.
     *
     * @param mixed $timestamp The timestamp to validate
     * @return int Valid timestamp or current time
     */
    private static function validate_timestamp($timestamp): int {
        if (is_numeric($timestamp) && $timestamp > 0) {
            return (int)$timestamp;
        }
        return time();
    }

    /**
     * Build disk usage details with safe division.
     *
     * @param array $diranalysis Directory analysis data
     * @param int $total Total disk usage
     * @return array Formatted disk details
     */
    private static function build_disk_details(array $diranalysis, int $total): array {
        $details = [];
        $keys = ['database', 'filedir', 'cache', 'backup', 'others'];

        foreach ($keys as $key) {
            $bytes = (int)($diranalysis[$key] ?? 0);
            $percentage = ($total > 0) ? round(($bytes / $total) * 100, 2) : 0;

            $details[$key] = [
                'bytes' => $bytes,
                'readable' => display_size($bytes),
                'percentage' => $percentage,
            ];
        }

        return $details;
    }

    /**
     * Format courses array for API response.
     *
     * @param array $courses Raw courses data
     * @return array Formatted courses
     */
    private static function format_courses($courses): array {
        $formatted = [];

        foreach ($courses as $course) {
            if (!isset($course->id) || !isset($course->fullname)) {
                continue;
            }

            $formatted[] = [
                'id' => (int)$course->id,
                'fullname' => format_string($course->fullname),
                'shortname' => format_string($course->shortname ?? ''),
                'size_bytes' => (int)($course->filesize ?? 0),
                'size_readable' => display_size($course->filesize ?? 0),
                'backup_size_bytes' => (int)($course->backupsize ?? 0),
                'backup_size_readable' => display_size($course->backupsize ?? 0),
                'percentage' => (float)($course->percentage ?? 0),
                'backup_count' => (int)($course->backupcount ?? 0),
            ];
        }

        return $formatted;
    }

    /**
     * Safe wrapper for project_limit_date to handle edge cases.
     *
     * @param int $current Current value
     * @param float $threshold Threshold value
     * @param float $growthrate Growth rate
     * @return int Projected days or max int
     */
    private static function safe_project_limit(int $current, float $threshold, float $growthrate): int {
        $result = project_limit_date($current, $threshold, $growthrate);

        if ($result === null || $result === PHP_INT_MAX || $result < 0) {
            return 999999;
        }

        return (int)$result;
    }
}
