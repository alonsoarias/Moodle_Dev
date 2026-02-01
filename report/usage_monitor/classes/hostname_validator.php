<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Hostname validator for report_usage_monitor.
 *
 * This class centralizes all hostname validation logic for the plugin.
 * The plugin is exclusive to IngeWeb hosting services and only runs
 * on servers with authorized hostnames.
 *
 * @package     report_usage_monitor
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

/**
 * Hostname validator class.
 *
 * Provides centralized hostname validation for the plugin.
 * All validation logic is contained here to avoid code duplication.
 */
class hostname_validator {

    /**
     * The valid hostname pattern for IngeWeb hosting.
     */
    private const VALID_HOSTNAME = 'moodlesoporte.net';

    /**
     * Check if the current server hostname is valid for this plugin.
     *
     * The plugin is only authorized to run on moodlesoporte.net domains
     * (e.g., hera.moodlesoporte.net, zeus.moodlesoporte.net, etc.)
     *
     * Validation order:
     * 1. First checks if wwwroot contains the valid hostname pattern
     * 2. If not, checks the extracted hostname from wwwroot
     * 3. As fallback, checks HTTP_HOST server variable
     *
     * @return bool True if hostname is valid, false otherwise.
     */
    public static function is_valid(): bool {
        global $CFG;

        // First, check if wwwroot contains the valid hostname (case insensitive).
        // This covers cases where the full URL contains the valid domain.
        if (!empty($CFG->wwwroot) && stripos($CFG->wwwroot, self::VALID_HOSTNAME) !== false) {
            return true;
        }

        // Try to get hostname from wwwroot.
        $hostname = '';
        if (!empty($CFG->wwwroot)) {
            $parsed = parse_url($CFG->wwwroot);
            $hostname = $parsed['host'] ?? '';
        }

        // Fallback to server hostname.
        if (empty($hostname) && !empty($_SERVER['HTTP_HOST'])) {
            $hostname = $_SERVER['HTTP_HOST'];
        }

        // If no hostname could be determined, plugin is not authorized.
        if (empty($hostname)) {
            return false;
        }

        // Check if hostname contains the valid domain (case insensitive).
        // Valid examples: hera.moodlesoporte.net, zeus.moodlesoporte.net, moodlesoporte.net
        return stripos($hostname, self::VALID_HOSTNAME) !== false;
    }

    /**
     * Get the valid hostname pattern.
     *
     * @return string The valid hostname pattern.
     */
    public static function get_valid_hostname(): string {
        return self::VALID_HOSTNAME;
    }

    /**
     * Check if the plugin is enabled based on hostname validation.
     *
     * Alias for is_valid() for semantic clarity.
     *
     * @return bool True if plugin is enabled, false otherwise.
     */
    public static function is_plugin_enabled(): bool {
        return self::is_valid();
    }

    /**
     * Require valid hostname or throw exception.
     *
     * Use this method in API endpoints to block access on unauthorized servers.
     * Throws an exception that will be properly formatted in API responses.
     *
     * @throws \moodle_exception If hostname is not valid.
     * @return void
     */
    public static function require_valid(): void {
        if (!self::is_valid()) {
            throw new \moodle_exception('hostname_not_authorized', 'report_usage_monitor');
        }
    }

    /**
     * Synchronize scheduled tasks state based on hostname validation.
     *
     * This method ensures all plugin tasks are disabled on unauthorized servers
     * and enabled on authorized servers. Call this method when accessing
     * plugin pages to auto-correct manually changed task states.
     *
     * IMPORTANT: The sync_tasks task is excluded from this method because it
     * must always remain enabled to perform the synchronization on every server.
     *
     * @return bool True if hostname is valid (tasks enabled), false otherwise.
     */
    public static function sync_scheduled_tasks(): bool {
        $isvalid = self::is_valid();

        // List of all task class names for this plugin.
        // NOTE: sync_tasks is intentionally excluded - it must always be enabled
        // to perform hostname validation and task synchronization automatically.
        $taskclasses = [
            '\\report_usage_monitor\\task\\disk_usage',
            '\\report_usage_monitor\\task\\last_users',
            '\\report_usage_monitor\\task\\notification_disk',
            '\\report_usage_monitor\\task\\notification_userlimit',
            '\\report_usage_monitor\\task\\users_daily',
            '\\report_usage_monitor\\task\\users_daily_90_days',
        ];

        foreach ($taskclasses as $classname) {
            $task = \core\task\manager::get_scheduled_task($classname);
            if ($task) {
                $shouldbedisabled = !$isvalid;
                $currentlydisabled = (bool) $task->get_disabled();

                // Only update if state needs to change.
                if ($shouldbedisabled !== $currentlydisabled) {
                    $task->set_disabled($shouldbedisabled);
                    \core\task\manager::configure_scheduled_task($task);
                }
            }
        }

        return $isvalid;
    }
}
