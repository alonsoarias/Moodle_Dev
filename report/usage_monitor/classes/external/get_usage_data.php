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
 * External function to get precalculated usage data.
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
use core_external\external_value;

require_once(__DIR__ . '/../../locallib.php');

/**
 * Get usage data external function - lightweight API endpoint.
 */
class get_usage_data extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Get precalculated usage data for lightweight API consumption.
     *
     * @return array Usage data
     */
    public static function execute(): array {
        global $DB, $CFG;

        // Validate context and capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Check license validity - throws exception if not authorized.
        \report_usage_monitor\license::require_authorized();

        // Get plugin configuration.
        $reportconfig = get_config('report_usage_monitor');

        // Disk usage data.
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0)) +
                     ((int)($reportconfig->totalusagereadabledb ?? 0));
        $quotadisk = ((int)($reportconfig->disk_quota ?? 0)) * 1024 * 1024 * 1024;
        $diskpercent = $quotadisk > 0 ? calculate_threshold_percentage($diskusage, $quotadisk) : 0;

        // User usage data.
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
        $userthreshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        $userspercent = $userthreshold > 0 ? calculate_threshold_percentage($userstoday, $userthreshold) : 0;

        // Validate timestamps.
        $lastdiskcalc = self::validate_timestamp($reportconfig->lastexecutioncalculate ?? 0);
        $lastuserscalc = self::validate_timestamp($reportconfig->lastexecution ?? 0);
        $max90daysdate = self::validate_timestamp($reportconfig->max_userdaily_for_90_days_date ?? 0);

        // Calculate growth rates.
        $diskgrowthrate = calculate_growth_rate('disk');
        $usersgrowthrate = calculate_growth_rate('users');

        // Get server hostname.
        $hostname = parse_url($CFG->wwwroot, PHP_URL_HOST);

        return [
            'hostname' => $hostname,
            'disk_usage' => [
                'current' => $diskusage,
                'current_readable' => display_size($diskusage),
                'threshold' => $quotadisk,
                'threshold_readable' => display_size($quotadisk),
                'percentage' => round($diskpercent, 2),
                'last_calculated' => $lastdiskcalc,
            ],
            'user_usage' => [
                'current' => $userstoday,
                'threshold' => $userthreshold,
                'percentage' => round($userspercent, 2),
                'last_calculated' => $lastuserscalc,
                'max_90_days' => (int)($reportconfig->max_userdaily_for_90_days_users ?? 0),
                'max_90_days_date' => $max90daysdate,
            ],
            'projections' => [
                'disk_growth_rate' => $diskgrowthrate,
                'users_growth_rate' => $usersgrowthrate,
                'days_to_disk_threshold' => self::safe_project_limit(
                    $diskusage,
                    $quotadisk * 0.9,
                    $diskgrowthrate
                ),
                'days_to_users_threshold' => self::safe_project_limit(
                    $userstoday,
                    $userthreshold * 0.9,
                    $usersgrowthrate
                ),
            ],
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'hostname' => new external_value(PARAM_TEXT, 'Server hostname'),
            'disk_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current disk usage in bytes'),
                'current_readable' => new external_value(PARAM_TEXT, 'Current disk usage readable'),
                'threshold' => new external_value(PARAM_INT, 'Disk threshold in bytes'),
                'threshold_readable' => new external_value(PARAM_TEXT, 'Disk threshold readable'),
                'percentage' => new external_value(PARAM_FLOAT, 'Disk usage percentage'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
            ]),
            'user_usage' => new external_single_structure([
                'current' => new external_value(PARAM_INT, 'Current daily users'),
                'threshold' => new external_value(PARAM_INT, 'User threshold'),
                'percentage' => new external_value(PARAM_FLOAT, 'User usage percentage'),
                'last_calculated' => new external_value(PARAM_INT, 'Last calculation timestamp'),
                'max_90_days' => new external_value(PARAM_INT, 'Maximum users in 90 days'),
                'max_90_days_date' => new external_value(PARAM_INT, 'Date of max users'),
            ]),
            'projections' => new external_single_structure([
                'disk_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly disk growth rate'),
                'users_growth_rate' => new external_value(PARAM_FLOAT, 'Monthly users growth rate'),
                'days_to_disk_threshold' => new external_value(PARAM_INT, 'Days to disk threshold'),
                'days_to_users_threshold' => new external_value(PARAM_INT, 'Days to users threshold'),
            ]),
        ]);
    }

    /**
     * Validate timestamp with fallback.
     *
     * @param mixed $timestamp Timestamp to validate
     * @return int Valid timestamp
     */
    private static function validate_timestamp($timestamp): int {
        if (is_numeric($timestamp) && $timestamp > 0) {
            return (int)$timestamp;
        }
        return time();
    }

    /**
     * Safe wrapper for project_limit_date.
     *
     * @param int $current Current value
     * @param float $threshold Threshold
     * @param float $growthrate Growth rate
     * @return int Projected days
     */
    private static function safe_project_limit(int $current, float $threshold, float $growthrate): int {
        $result = project_limit_date($current, $threshold, $growthrate);

        if ($result === null || $result === PHP_INT_MAX || $result < 0) {
            return 999999;
        }

        return (int)$result;
    }
}
