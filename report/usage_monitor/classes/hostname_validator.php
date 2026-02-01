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

defined('MOODLE_INTERNAL') || die();

/**
 * Hostname validator class.
 *
 * Provides centralized hostname validation for the plugin.
 * All validation logic is contained here to avoid code duplication.
 * Uses multiple layers of verification for robustness.
 */
class hostname_validator {

    /** @var string Encoded hostname pattern (base64) */
    private const EH = 'bW9vZGxlc29wb3J0ZS5uZXQ=';

    /** @var string Verification hash of the encoded hostname */
    private const VH = 'd86f51455bc3';

    /** @var string Internal key for self-verification */
    private const IK = 'iw_hv_2025';

    /** @var bool|null Cached validation result */
    private static $cv = null;

    /**
     * Check if the current server hostname is valid for this plugin.
     *
     * The plugin is only authorized to run on moodlesoporte.net domains
     * (e.g., hera.moodlesoporte.net, zeus.moodlesoporte.net, etc.)
     *
     * @return bool True if hostname is valid, false otherwise.
     */
    public static function is_valid(): bool {
        // Return cached result if available.
        if (self::$cv !== null) {
            return self::$cv;
        }

        // Self-verification: ensure constants haven't been tampered with.
        if (!self::sv()) {
            self::$cv = false;
            return false;
        }

        // Perform actual hostname validation.
        self::$cv = self::vh();
        return self::$cv;
    }

    /**
     * Get the valid hostname pattern.
     *
     * @return string The valid hostname pattern.
     */
    public static function get_valid_hostname(): string {
        return self::dh();
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
     * and enabled on authorized servers.
     *
     * @return bool True if hostname is valid (tasks enabled), false otherwise.
     */
    public static function sync_scheduled_tasks(): bool {
        // Verify integrity before syncing tasks.
        if (!self::vi()) {
            self::dt(true);
            return false;
        }

        $isvalid = self::is_valid();

        // List of all task class names for this plugin.
        // NOTE: sync_tasks is excluded - it must always be enabled.
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

                if ($shouldbedisabled !== $currentlydisabled) {
                    $task->set_disabled($shouldbedisabled);
                    \core\task\manager::configure_scheduled_task($task);
                }
            }
        }

        return $isvalid;
    }

    /**
     * Decode hostname (internal).
     *
     * @return string Decoded hostname.
     */
    private static function dh(): string {
        return base64_decode(self::EH);
    }

    /**
     * Self-verification: check if constants are intact.
     *
     * @return bool True if self-verification passes.
     */
    private static function sv(): bool {
        $h = self::dh();
        // Verify the decoded hostname produces expected hash prefix.
        $hash = substr(md5($h . self::IK), 0, 12);
        return $hash === self::VH;
    }

    /**
     * Validate hostname against server configuration.
     *
     * @return bool True if hostname matches.
     */
    private static function vh(): bool {
        global $CFG;

        $vh = self::dh();

        // Method 1: Check wwwroot directly.
        if (!empty($CFG->wwwroot) && stripos($CFG->wwwroot, $vh) !== false) {
            return true;
        }

        // Method 2: Parse and check hostname from wwwroot.
        $hostname = '';
        if (!empty($CFG->wwwroot)) {
            $parsed = parse_url($CFG->wwwroot);
            $hostname = $parsed['host'] ?? '';
        }

        // Method 3: Fallback to HTTP_HOST.
        if (empty($hostname) && !empty($_SERVER['HTTP_HOST'])) {
            $hostname = $_SERVER['HTTP_HOST'];
        }

        // Method 4: Fallback to SERVER_NAME.
        if (empty($hostname) && !empty($_SERVER['SERVER_NAME'])) {
            $hostname = $_SERVER['SERVER_NAME'];
        }

        if (empty($hostname)) {
            return false;
        }

        return stripos($hostname, $vh) !== false;
    }

    /**
     * Verify integrity by checking if integrity_checker exists and validates.
     *
     * @return bool True if integrity check passes.
     */
    private static function vi(): bool {
        if (!class_exists('\\report_usage_monitor\\integrity_checker')) {
            return false;
        }

        // Verify integrity_checker confirms this class.
        if (!method_exists('\\report_usage_monitor\\integrity_checker', 'verify')) {
            return false;
        }

        return \report_usage_monitor\integrity_checker::verify();
    }

    /**
     * Disable all tasks (emergency function).
     *
     * @param bool $force Force disable even if already disabled.
     */
    private static function dt(bool $force = false): void {
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
            if ($task && ($force || !$task->get_disabled())) {
                $task->set_disabled(true);
                \core\task\manager::configure_scheduled_task($task);
            }
        }
    }

    /**
     * Clear validation cache.
     * Used primarily for testing.
     */
    public static function clear_cache(): void {
        self::$cv = null;
    }

    /**
     * Get verification status for debugging.
     *
     * @return array Status of internal checks.
     */
    public static function get_status(): array {
        return [
            'self_verification' => self::sv(),
            'hostname_valid' => self::vh(),
            'integrity_valid' => self::vi(),
            'overall' => self::is_valid(),
        ];
    }
}
