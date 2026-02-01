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
 * Scheduled task to synchronize plugin tasks based on hostname validation.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task synchronization scheduled task.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_tasks extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('synctaskstask', 'report_usage_monitor');
    }

    /**
     * Execute the task synchronization.
     *
     * @return bool True on success.
     */
    public function execute(): bool {
        // All validation is centralized in hostname_validator.
        // Uses integrity cross-verification and obfuscated hostname.
        $isvalid = \report_usage_monitor\hostname_validator::sync_scheduled_tasks();

        if (debugging('', DEBUG_DEVELOPER)) {
            $status = $isvalid ? 'authorized (tasks enabled)' : 'unauthorized (tasks disabled)';
            mtrace("report_usage_monitor: Task sync completed. Server is {$status}.");
        }

        return true;
    }
}
