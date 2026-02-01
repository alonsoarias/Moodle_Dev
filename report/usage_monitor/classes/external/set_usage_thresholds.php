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
 * External function to set usage thresholds.
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
 * Set usage thresholds external function.
 */
class set_usage_thresholds extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'user_threshold' => new external_value(
                PARAM_INT,
                'New daily users threshold',
                VALUE_DEFAULT,
                null
            ),
            'disk_threshold' => new external_value(
                PARAM_INT,
                'New disk threshold in GB',
                VALUE_DEFAULT,
                null
            ),
        ]);
    }

    /**
     * Set usage thresholds.
     *
     * @param int|null $userthreshold New user threshold
     * @param int|null $diskthreshold New disk threshold in GB
     * @return array Operation result
     */
    public static function execute(?int $userthreshold = null, ?int $diskthreshold = null): array {
        global $DB;

        // Validate context and capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:manage', $context);

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            throw new \moodle_exception('plugin_disabled_hostname', 'report_usage_monitor');
        }

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'user_threshold' => $userthreshold,
            'disk_threshold' => $diskthreshold,
        ]);

        $result = [
            'success' => true,
            'user_threshold_updated' => false,
            'disk_threshold_updated' => false,
            'messages' => [],
        ];

        // Check if at least one parameter is provided.
        if ($params['user_threshold'] === null && $params['disk_threshold'] === null) {
            $result['success'] = false;
            $result['messages'][] = get_string('error_no_thresholds_provided', 'report_usage_monitor');
            return $result;
        }

        // Start transaction.
        $transaction = $DB->start_delegated_transaction();

        try {
            // Update user threshold if provided.
            if ($params['user_threshold'] !== null) {
                if ($params['user_threshold'] > 0) {
                    set_config('max_daily_users_threshold', $params['user_threshold'], 'report_usage_monitor');
                    $result['user_threshold_updated'] = true;
                    $result['messages'][] = get_string('user_threshold_updated', 'report_usage_monitor');

                    // Recalculate precached values.
                    self::recalculate_user_values($params['user_threshold']);
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_user_threshold_negative', 'report_usage_monitor');
                }
            }

            // Update disk threshold if provided.
            if ($params['disk_threshold'] !== null) {
                if ($params['disk_threshold'] > 0) {
                    set_config('disk_quota', $params['disk_threshold'], 'report_usage_monitor');
                    $result['disk_threshold_updated'] = true;
                    $result['messages'][] = get_string('disk_threshold_updated', 'report_usage_monitor');

                    // Recalculate precached values.
                    self::recalculate_disk_values($params['disk_threshold']);
                } else {
                    $result['success'] = false;
                    $result['messages'][] = get_string('error_disk_threshold_negative', 'report_usage_monitor');
                }
            }

            if ($result['success']) {
                $transaction->allow_commit();
            } else {
                $transaction->rollback(new \moodle_exception('thresholds_update_failed', 'report_usage_monitor'));
            }
        } catch (\Exception $e) {
            $transaction->rollback($e);
            $result['success'] = false;
            $result['messages'][] = get_string('error_updating_thresholds', 'report_usage_monitor') . ': ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether operation succeeded'),
            'user_threshold_updated' => new external_value(PARAM_BOOL, 'Whether user threshold was updated'),
            'disk_threshold_updated' => new external_value(PARAM_BOOL, 'Whether disk threshold was updated'),
            'messages' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Status message')
            ),
        ]);
    }

    /**
     * Recalculate user-related precached values.
     *
     * @param int $threshold New threshold
     */
    private static function recalculate_user_values(int $threshold): void {
        $reportconfig = get_config('report_usage_monitor');
        $userstoday = (int)($reportconfig->totalusersdaily ?? 0);
        $userspercent = calculate_threshold_percentage($userstoday, $threshold);
        $warningclass = self::get_warning_class($userspercent);

        set_config('users_percent', $userspercent, 'report_usage_monitor');
        set_config('users_warning_class', $warningclass, 'report_usage_monitor');
    }

    /**
     * Recalculate disk-related precached values.
     *
     * @param int $thresholdgb New threshold in GB
     */
    private static function recalculate_disk_values(int $thresholdgb): void {
        $reportconfig = get_config('report_usage_monitor');
        $diskusage = ((int)($reportconfig->totalusagereadable ?? 0)) +
                     ((int)($reportconfig->totalusagereadabledb ?? 0));
        $quotadiskbytes = $thresholdgb * 1024 * 1024 * 1024;
        $diskpercent = calculate_threshold_percentage($diskusage, $quotadiskbytes);
        $warningclass = self::get_warning_class($diskpercent);

        set_config('disk_percent', $diskpercent, 'report_usage_monitor');
        set_config('disk_warning_class', $warningclass, 'report_usage_monitor');
        set_config('quotadisk_gb', display_size_in_gb($quotadiskbytes, 2), 'report_usage_monitor');
    }

    /**
     * Get warning class based on percentage.
     *
     * @param float $percent Percentage value
     * @return string CSS class
     */
    private static function get_warning_class(float $percent): string {
        if ($percent < 70) {
            return 'bg-success';
        } else if ($percent < 90) {
            return 'bg-warning';
        }
        return 'bg-danger';
    }
}
