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
 * Centralized license validation for report_usage_monitor.
 *
 * This is the ONLY file that should perform validation.
 * All other files must request a token from this class and verify it.
 * Returns cryptographic tokens, not simple booleans.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * License validation class.
 *
 * Provides centralized, secure validation for the plugin.
 * Uses token-based responses to prevent bypass attacks.
 */
class license {

    /** @var string Encoded valid hostname (base64 of 'moodlesoporte.net') */
    private const EH = 'bW9vZGxlc29wb3J0ZS5uZXQ=';

    /** @var string Internal key for token generation */
    private const IK = 'iw_rum_2025_lic';

    /** @var int Token validity in seconds (5 minutes) */
    private const TV = 300;

    /** @var string|null Cached token for current request */
    private static $ct = null;

    /** @var int|null Cached token timestamp */
    private static $tt = null;

    /**
     * Get authorization token.
     *
     * This is the ONLY method that should be called to check if plugin is authorized.
     * Returns a token that must be validated using validate_token().
     *
     * @return string Authorization token (empty string if not authorized).
     */
    public static function get_token(): string {
        // Check if we have a valid cached token.
        if (self::$ct !== null && self::$tt !== null) {
            if ((time() - self::$tt) < self::TV) {
                return self::$ct;
            }
        }

        // Perform validation.
        if (!self::cv()) {
            self::$ct = '';
            self::$tt = time();
            return '';
        }

        // Generate new token.
        self::$ct = self::gt();
        self::$tt = time();

        return self::$ct;
    }

    /**
     * Validate a token.
     *
     * All code that needs to check authorization must call this method
     * with the token received from get_token().
     *
     * @param string $token Token to validate.
     * @return bool True if token is valid.
     */
    public static function validate_token(string $token): bool {
        if (empty($token)) {
            return false;
        }

        // Decode token.
        $decoded = base64_decode($token, true);
        if ($decoded === false) {
            return false;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 4) {
            return false;
        }

        list($ts, $eh, $sig, $rand) = $parts;

        // Check timestamp (token must not be older than TV seconds).
        $timestamp = (int)$ts;
        if ((time() - $timestamp) > self::TV) {
            return false;
        }

        // Verify hostname hash.
        $expectedeh = hash('sha256', base64_decode(self::EH));
        if ($eh !== $expectedeh) {
            return false;
        }

        // Verify signature.
        $expectedsig = hash('sha256', $ts . $eh . $rand . self::IK);
        if ($sig !== $expectedsig) {
            return false;
        }

        return true;
    }

    /**
     * Quick check if authorized.
     *
     * Convenience method that gets and validates token in one call.
     * Use this in code that needs a simple boolean result.
     *
     * @return bool True if authorized.
     */
    public static function is_authorized(): bool {
        $token = self::get_token();
        return self::validate_token($token);
    }

    /**
     * Get valid hostname.
     *
     * @return string The valid hostname pattern.
     */
    public static function get_valid_hostname(): string {
        return base64_decode(self::EH);
    }

    /**
     * Core validation - checks hostname.
     *
     * @return bool True if hostname is valid.
     */
    private static function cv(): bool {
        global $CFG;

        $vh = base64_decode(self::EH);

        // Check wwwroot.
        if (!empty($CFG->wwwroot) && stripos($CFG->wwwroot, $vh) !== false) {
            return true;
        }

        // Extract hostname from wwwroot.
        $hn = '';
        if (!empty($CFG->wwwroot)) {
            $p = parse_url($CFG->wwwroot);
            $hn = $p['host'] ?? '';
        }

        // Fallback to HTTP_HOST.
        if (empty($hn) && !empty($_SERVER['HTTP_HOST'])) {
            $hn = $_SERVER['HTTP_HOST'];
        }

        if (empty($hn)) {
            return false;
        }

        return stripos($hn, $vh) !== false;
    }

    /**
     * Generate token.
     *
     * @return string Generated token.
     */
    private static function gt(): string {
        $ts = time();
        $eh = hash('sha256', base64_decode(self::EH));
        $rand = bin2hex(random_bytes(8));
        $sig = hash('sha256', $ts . $eh . $rand . self::IK);

        $data = implode('|', [$ts, $eh, $sig, $rand]);
        return base64_encode($data);
    }

    /**
     * Require authorization or throw exception.
     *
     * Use this in API endpoints.
     *
     * @throws \moodle_exception If not authorized.
     */
    public static function require_authorized(): void {
        if (!self::is_authorized()) {
            throw new \moodle_exception('hostname_not_authorized', 'report_usage_monitor');
        }
    }

    /**
     * Synchronize scheduled tasks based on authorization.
     *
     * @return bool True if authorized (tasks enabled).
     */
    public static function sync_tasks(): bool {
        $authorized = self::is_authorized();

        $tasks = [
            '\\report_usage_monitor\\task\\disk_usage',
            '\\report_usage_monitor\\task\\last_users',
            '\\report_usage_monitor\\task\\notification_disk',
            '\\report_usage_monitor\\task\\notification_userlimit',
            '\\report_usage_monitor\\task\\users_daily',
            '\\report_usage_monitor\\task\\users_daily_90_days',
        ];

        foreach ($tasks as $classname) {
            $task = \core\task\manager::get_scheduled_task($classname);
            if ($task) {
                $shoulddisable = !$authorized;
                if ($shoulddisable !== (bool)$task->get_disabled()) {
                    $task->set_disabled($shoulddisable);
                    \core\task\manager::configure_scheduled_task($task);
                }
            }
        }

        return $authorized;
    }
}
