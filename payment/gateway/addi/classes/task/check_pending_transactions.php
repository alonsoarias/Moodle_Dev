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
 * Scheduled task to check pending Addi transactions.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_addi\task;

use core_payment\helper;
use paygw_addi\gateway;
use paygw_addi\addi_helper;

/**
 * Scheduled task to check pending Addi transactions.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_pending_transactions extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task:checkpendingtransactions', 'paygw_addi');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('Addi: Checking pending transactions...');

        // Get pending transactions from the last 7 days.
        $timelimit = time() - (7 * 24 * 60 * 60);

        $transactions = $DB->get_records_select(
            'paygw_addi_transactions',
            "status = :status AND applicationid IS NOT NULL AND applicationid != '' AND timecreated > :timelimit",
            ['status' => 'PENDING', 'timelimit' => $timelimit]
        );

        if (empty($transactions)) {
            mtrace('Addi: No pending transactions to check.');
            return;
        }

        mtrace('Addi: Found ' . count($transactions) . ' pending transactions to check.');

        foreach ($transactions as $transaction) {
            try {
                $this->check_transaction($transaction);
            } catch (\Exception $e) {
                mtrace('Addi: Error checking transaction ' . $transaction->reference . ': ' . $e->getMessage());
            }
        }

        mtrace('Addi: Finished checking pending transactions.');
    }

    /**
     * Check a single transaction with Addi API.
     *
     * @param \stdClass $transaction The transaction record.
     */
    private function check_transaction(\stdClass $transaction): void {
        global $DB;

        mtrace('Addi: Checking transaction ' . $transaction->reference . ' (applicationId: ' . $transaction->applicationid . ')');

        // Get gateway configuration.
        $config = (object) helper::get_gateway_configuration(
            $transaction->component,
            $transaction->paymentarea,
            $transaction->itemid,
            'addi'
        );

        $addihelper = gateway::create_helper_from_config($config);

        // Query Addi API.
        $applicationdata = $addihelper->get_application($transaction->applicationid);

        if (!$applicationdata || !isset($applicationdata['status'])) {
            throw new \Exception('Failed to query Addi API');
        }

        $addistatus = strtoupper($applicationdata['status']);
        $newstatus = $addihelper->map_addi_status($addistatus);

        mtrace('Addi: Transaction ' . $transaction->reference . ' status: ' . $newstatus);

        // Only update if status changed.
        if ($transaction->status === $newstatus) {
            mtrace('Addi: Transaction ' . $transaction->reference . ' status unchanged.');
            return;
        }

        // Update transaction.
        $addihelper->update_transaction($transaction->id, [
            'status' => $newstatus,
            'response' => $applicationdata,
        ]);

        // If payment is now approved, deliver the order.
        if ($addistatus === addi_helper::STATUS_APPROVED && empty($transaction->paymentid)) {
            try {
                // Get payable info.
                $payable = helper::get_payable(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid
                );
                $surcharge = helper::get_gateway_surcharge('addi');
                $paymentamount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

                // Deliver the order.
                $paymentid = helper::save_payment(
                    $payable->get_account_id(),
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    (int)$transaction->userid,
                    $paymentamount,
                    $payable->get_currency(),
                    'addi'
                );

                helper::deliver_order(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    $paymentid,
                    (int)$transaction->userid
                );

                // Update transaction with payment ID.
                $DB->set_field('paygw_addi_transactions', 'paymentid', $paymentid, ['id' => $transaction->id]);

                mtrace('Addi: Payment delivered for transaction ' . $transaction->reference);

                // Send notification email.
                $user = \core_user::get_user($transaction->userid);
                if ($user) {
                    $addihelper->send_notification_email($user, $config, 'completed', [
                        'amount' => number_format($transaction->amount, 0, ',', '.') . ' ' . $transaction->currency,
                        'currency' => $transaction->currency,
                        'orderid' => $transaction->reference,
                    ]);
                }

            } catch (\Exception $e) {
                mtrace('Addi: Error delivering order for ' . $transaction->reference . ': ' . $e->getMessage());
            }
        }
    }
}
