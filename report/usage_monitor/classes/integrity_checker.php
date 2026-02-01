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
 * Plugin integrity checker for report_usage_monitor.
 *
 * This class verifies that critical plugin components have not been
 * tampered with. It provides multiple layers of protection against
 * unauthorized modifications.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor;

defined('MOODLE_INTERNAL') || die();

/**
 * Integrity checker class.
 *
 * Provides integrity verification for the plugin to prevent unauthorized
 * modifications and ensure the plugin only runs in authorized environments.
 */
class integrity_checker {

    /**
     * Encoded valid hostname (base64 of 'moodlesoporte.net').
     * @var string
     */
    private const EH = 'bW9vZGxlc29wb3J0ZS5uZXQ=';

    /**
     * Required class signatures for integrity verification.
     * @var array
     */
    private const REQUIRED_CLASSES = [
        'report_usage_monitor\\hostname_validator',
        'report_usage_monitor\\integrity_checker',
    ];

    /**
     * Required methods in hostname_validator.
     * @var array
     */
    private const REQUIRED_METHODS = [
        'is_valid',
        'sync_scheduled_tasks',
        'require_valid',
    ];

    /**
     * Verify complete plugin integrity.
     *
     * @return bool True if all integrity checks pass.
     */
    public static function verify(): bool {
        return self::vc() && self::vm() && self::vh();
    }

    /**
     * Verify required classes exist.
     *
     * @return bool True if all required classes exist.
     */
    private static function vc(): bool {
        foreach (self::REQUIRED_CLASSES as $c) {
            if (!class_exists('\\' . $c)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verify required methods exist.
     *
     * @return bool True if all required methods exist.
     */
    private static function vm(): bool {
        if (!class_exists('\\report_usage_monitor\\hostname_validator')) {
            return false;
        }
        foreach (self::REQUIRED_METHODS as $m) {
            if (!method_exists('\\report_usage_monitor\\hostname_validator', $m)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Verify hostname validation is working correctly.
     *
     * @return bool True if hostname validation is functional.
     */
    private static function vh(): bool {
        // Decode and verify the hostname validator returns expected pattern.
        $eh = base64_decode(self::EH);

        // Get the valid hostname from validator.
        if (!method_exists('\\report_usage_monitor\\hostname_validator', 'get_valid_hostname')) {
            return false;
        }

        $vh = \report_usage_monitor\hostname_validator::get_valid_hostname();

        // Verify the hostname matches our encoded value.
        return $vh === $eh;
    }

    /**
     * Get integrity status for debugging.
     *
     * @return array Status of each check.
     */
    public static function get_status(): array {
        return [
            'classes_exist' => self::vc(),
            'methods_exist' => self::vm(),
            'hostname_valid' => self::vh(),
            'overall' => self::verify(),
        ];
    }

    /**
     * Verify and get hostname validation result.
     * This is an alternative entry point that combines integrity and hostname checks.
     *
     * @return bool True only if integrity passes AND hostname is valid.
     */
    public static function is_authorized(): bool {
        if (!self::verify()) {
            return false;
        }
        return \report_usage_monitor\hostname_validator::is_valid();
    }

    /**
     * Generate a verification token for the current installation.
     * This can be used to verify the plugin hasn't been moved to another server.
     *
     * @return string Verification token.
     */
    public static function get_verification_token(): string {
        global $CFG;

        $data = [
            base64_decode(self::EH),
            $CFG->wwwroot ?? '',
            'report_usage_monitor',
        ];

        return hash('sha256', implode('|', $data));
    }
}
