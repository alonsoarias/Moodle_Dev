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
 * Scheduled task to cleanup old conversation history.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to cleanup old conversation logs based on retention settings.
 */
class cleanup_history extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_cleanup_history', 'local_educambot');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Get retention period in days (0 = keep forever).
        $retentiondays = get_config('local_educambot', 'historyretention');

        // If retention is 0 (forever), do nothing.
        if (empty($retentiondays) || $retentiondays == 0) {
            mtrace('History retention set to forever, no cleanup needed.');
            return;
        }

        // Calculate cutoff timestamp.
        $cutofftime = time() - ($retentiondays * DAYSECS);

        mtrace("Cleaning up conversation logs older than {$retentiondays} days...");

        // Count records to delete.
        $count = $DB->count_records_select(
            'local_educambot_log',
            'timecreated < :cutoff',
            ['cutoff' => $cutofftime]
        );

        if ($count > 0) {
            // Delete old records.
            $DB->delete_records_select(
                'local_educambot_log',
                'timecreated < :cutoff',
                ['cutoff' => $cutofftime]
            );

            mtrace("Deleted {$count} old conversation log records.");
        } else {
            mtrace('No old records to delete.');
        }
    }
}
