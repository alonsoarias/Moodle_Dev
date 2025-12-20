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
 * Scheduled task to clean up expired/old Addi transactions.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_addi\task;

/**
 * Scheduled task to clean up expired/old Addi transactions.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_expired_transactions extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:cleanupexpiredtransactions', 'paygw_addi');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('Addi: Cleaning up expired transactions...');

        // Delete initiated transactions older than 24 hours (abandoned).
        $initiatedtimelimit = time() - (24 * 60 * 60);

        $initiateddeleted = $DB->delete_records_select(
            'paygw_addi_transactions',
            "status = :status AND timecreated < :timelimit",
            ['status' => 'INITIATED', 'timelimit' => $initiatedtimelimit]
        );

        if ($initiateddeleted > 0) {
            mtrace('Addi: Deleted ' . $initiateddeleted . ' abandoned (initiated) transactions.');
        }

        // Delete failed/rejected transactions older than 30 days.
        $failedtimelimit = time() - (30 * 24 * 60 * 60);

        $faileddeleted = $DB->delete_records_select(
            'paygw_addi_transactions',
            "(status = :statusrejected OR status = :statusfailed OR status = :statuscancelled) AND timecreated < :timelimit",
            [
                'statusrejected' => 'REJECTED',
                'statusfailed' => 'FAILED',
                'statuscancelled' => 'CANCELLED',
                'timelimit' => $failedtimelimit,
            ]
        );

        if ($faileddeleted > 0) {
            mtrace('Addi: Deleted ' . $faileddeleted . ' old failed/rejected transactions.');
        }

        // Update stale pending transactions (older than 14 days) to expired.
        $pendingtimelimit = time() - (14 * 24 * 60 * 60);

        $expiredupdated = $DB->execute(
            "UPDATE {paygw_addi_transactions}
                SET status = :newstatus, timemodified = :now
              WHERE status = :status AND timecreated < :timelimit",
            [
                'newstatus' => 'EXPIRED',
                'now' => time(),
                'status' => 'PENDING',
                'timelimit' => $pendingtimelimit,
            ]
        );

        if ($expiredupdated) {
            mtrace('Addi: Marked stale pending transactions as expired.');
        }

        mtrace('Addi: Cleanup complete.');
    }
}
