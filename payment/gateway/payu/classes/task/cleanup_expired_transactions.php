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
 * Scheduled task to clean up expired/abandoned transactions.
 *
 * Transactions that remain in PENDING status for more than 7 days
 * are considered abandoned and will be marked as EXPIRED.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\task;

use core\task\scheduled_task;

/**
 * Cleanup expired transactions task.
 */
class cleanup_expired_transactions extends scheduled_task {

    /** @var int Days after which pending transactions are considered expired. */
    const EXPIRY_DAYS = 7;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_cleanup_expired', 'paygw_payu');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('Starting PayU expired transactions cleanup...');

        // Find transactions older than EXPIRY_DAYS that are still PENDING.
        $cutoff = time() - (self::EXPIRY_DAYS * DAYSECS);

        $sql = "SELECT * FROM {paygw_payu_transactions}
                WHERE state = :state
                AND timecreated < :cutoff";

        $transactions = $DB->get_records_sql($sql, [
            'state' => 'PENDING',
            'cutoff' => $cutoff,
        ]);

        if (empty($transactions)) {
            mtrace('No expired transactions found.');
            return;
        }

        mtrace('Found ' . count($transactions) . ' expired transactions to clean up.');

        $cleaned = 0;
        $errors = 0;

        foreach ($transactions as $transaction) {
            try {
                // Mark as expired.
                $transaction->state = 'EXPIRED';
                $transaction->timemodified = time();
                $DB->update_record('paygw_payu_transactions', $transaction);

                // Notify user about expired transaction.
                $this->send_expiry_notification($transaction);

                mtrace("  Transaction {$transaction->referencecode}: Marked as EXPIRED");
                $cleaned++;

            } catch (\Exception $e) {
                mtrace("  Transaction {$transaction->referencecode}: Error - " . $e->getMessage());
                $errors++;
            }
        }

        mtrace("Cleanup completed: Cleaned={$cleaned}, Errors={$errors}");
    }

    /**
     * Send notification to user about expired transaction.
     *
     * @param object $transaction The transaction record.
     */
    private function send_expiry_notification(object $transaction): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $transaction->userid]);
        if (!$user) {
            return;
        }

        $subject = get_string('payment_expired_subject', 'paygw_payu');
        $message = get_string('payment_expired_message', 'paygw_payu', [
            'amount' => number_format($transaction->amount, 0, ',', '.'),
            'currency' => $transaction->currency,
            'date' => userdate($transaction->timecreated),
        ]);

        $eventdata = new \core\message\message();
        $eventdata->component = 'paygw_payu';
        $eventdata->name = 'payment_status';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '<p>' . nl2br($message) . '</p>';
        $eventdata->smallmessage = $message;
        $eventdata->notification = 1;

        message_send($eventdata);
    }
}
