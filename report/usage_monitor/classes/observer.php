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
 * Event observer for report_usage_monitor.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

/**
 * Event observer class.
 *
 * Listens for configuration changes and recalculates cached values.
 */
class observer {

    /**
     * Observer for config_log_created event.
     *
     * Recalculates cached percentages when thresholds are changed.
     *
     * @param \core\event\config_log_created $event The event object.
     */
    public static function config_changed(\core\event\config_log_created $event): void {
        $data = $event->get_data();
        $other = $data['other'] ?? [];

        // Only process changes to our plugin settings.
        $plugin = $other['plugin'] ?? '';
        if ($plugin !== 'report_usage_monitor') {
            return;
        }

        $name = $other['name'] ?? '';
        $newvalue = $other['value'] ?? null;

        // Check which setting changed and recalculate accordingly.
        switch ($name) {
            case 'max_daily_users_threshold':
                self::recalculate_user_values((int)$newvalue);
                break;

            case 'disk_quota':
                self::recalculate_disk_values((int)$newvalue);
                break;

            case 'plugin_enabled':
                // Sync plugin_disabled when plugin_enabled changes from admin settings.
                self::sync_plugin_status((int)$newvalue);
                break;

            case 'plugin_disabled':
                // Sync plugin_enabled when plugin_disabled changes from API.
                self::sync_plugin_status_inverse((int)$newvalue);
                break;
        }
    }

    /**
     * Sync plugin_disabled based on plugin_enabled value.
     *
     * @param int $enabled The new plugin_enabled value (1 or 0).
     */
    private static function sync_plugin_status(int $enabled): void {
        // When plugin_enabled is set, update plugin_disabled to inverse.
        // This ensures both configs stay in sync.
        $disabled = $enabled ? 0 : 1;
        $currentdisabled = get_config('report_usage_monitor', 'plugin_disabled');

        // Only update if different to avoid infinite loop.
        if ((int)$currentdisabled !== $disabled) {
            set_config('plugin_disabled', $disabled, 'report_usage_monitor');
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Synced plugin status - enabled: {$enabled}, disabled: {$disabled}");
        }
    }

    /**
     * Sync plugin_enabled based on plugin_disabled value.
     *
     * @param int $disabled The new plugin_disabled value (1 or 0).
     */
    private static function sync_plugin_status_inverse(int $disabled): void {
        // When plugin_disabled is set, update plugin_enabled to inverse.
        $enabled = $disabled ? 0 : 1;
        $currentenabled = get_config('report_usage_monitor', 'plugin_enabled');

        // Only update if different to avoid infinite loop.
        if ((int)$currentenabled !== $enabled) {
            set_config('plugin_enabled', $enabled, 'report_usage_monitor');
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Synced plugin status inverse - disabled: {$disabled}, enabled: {$enabled}");
        }
    }

    /**
     * Recalculate user-related cached values.
     *
     * @param int $threshold New user threshold value.
     */
    private static function recalculate_user_values(int $threshold): void {
        if ($threshold <= 0) {
            return;
        }

        $config = get_config('report_usage_monitor');
        $userstoday = (int)($config->totalusersdaily ?? 0);

        // Calculate percentage.
        $percent = calculate_threshold_percentage($userstoday, $threshold);
        $warningclass = self::get_warning_class($percent);

        // Update cached values.
        set_config('users_percent', round($percent, 2), 'report_usage_monitor');
        set_config('users_warning_class', $warningclass, 'report_usage_monitor');

        // Log the recalculation for debugging.
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Recalculated user values - threshold: {$threshold}, " .
                   "users: {$userstoday}, percent: {$percent}%");
        }
    }

    /**
     * Recalculate disk-related cached values.
     *
     * @param int $thresholdgb New disk threshold value in GB.
     */
    private static function recalculate_disk_values(int $thresholdgb): void {
        if ($thresholdgb <= 0) {
            return;
        }

        $config = get_config('report_usage_monitor');
        $diskusage = ((int)($config->totalusagereadable ?? 0)) +
                     ((int)($config->totalusagereadabledb ?? 0));

        // Convert GB to bytes.
        $thresholdbytes = $thresholdgb * 1024 * 1024 * 1024;

        // Calculate percentage.
        $percent = calculate_threshold_percentage($diskusage, $thresholdbytes);
        $warningclass = self::get_warning_class($percent);

        // Update cached values.
        set_config('disk_percent', round($percent, 2), 'report_usage_monitor');
        set_config('disk_warning_class', $warningclass, 'report_usage_monitor');
        set_config('quotadisk_gb', display_size_in_gb($thresholdbytes, 2), 'report_usage_monitor');

        // Log the recalculation for debugging.
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Recalculated disk values - threshold: {$thresholdgb}GB, " .
                   "usage: {$diskusage} bytes, percent: {$percent}%");
        }
    }

    /**
     * Get CSS warning class based on percentage.
     *
     * @param float $percent Percentage value.
     * @return string CSS class name.
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
