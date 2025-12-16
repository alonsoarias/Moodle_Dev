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
 * Scheduled task to check pending transactions status via PayU API.
 *
 * This task is essential for async payment methods like PSE, cash payments (Baloto, Efecty),
 * and bank transfers that may take hours to days to confirm.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu\task;

use core\task\scheduled_task;
use paygw_payu\payu_helper;
use paygw_payu\gateway;
use core_payment\helper as payment_helper;

/**
 * Check pending transactions task.
 */
class check_pending_transactions extends scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_check_pending', 'paygw_payu');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('Starting PayU pending transactions check...');

        // Get pending transactions from the last 72 hours (cash/bank payments can take time).
        $cutoff = time() - (72 * HOURSECS);
        $transactions = $DB->get_records_select(
            'paygw_payu_transactions',
            "state = :state AND timecreated > :cutoff",
            ['state' => 'PENDING', 'cutoff' => $cutoff],
            'timecreated ASC',
            '*',
            0,
            100 // Process max 100 transactions per run.
        );

        if (empty($transactions)) {
            mtrace('No pending transactions found.');
            return;
        }

        mtrace('Found ' . count($transactions) . ' pending transactions to check.');

        $processed = 0;
        $updated = 0;
        $errors = 0;

        foreach ($transactions as $transaction) {
            $processed++;

            try {
                // Get the payment account config for this transaction.
                $config = $this->get_gateway_config($transaction);
                if (!$config) {
                    mtrace("  Transaction {$transaction->referencecode}: No gateway config found, skipping.");
                    continue;
                }

                // Create helper instance and query PayU API.
                $helper = new payu_helper(
                    $config->merchantid ?? '',
                    $config->payuaccountid ?? '',
                    $config->apikey ?? '',
                    $config->apilogin ?? '',
                    $config->environment ?? 'sandbox'
                );

                // Query PayU for transaction status by order ID.
                $payudata = $helper->query_transaction_by_reference($transaction->referencecode);

                if (!$payudata) {
                    mtrace("  Transaction {$transaction->referencecode}: Could not fetch status from PayU.");
                    $errors++;
                    continue;
                }

                $newstate = $this->map_payu_state($payudata);
                mtrace("  Transaction {$transaction->referencecode}: PayU state = {$newstate}");

                // If status changed from PENDING, update the transaction.
                if ($newstate !== 'PENDING') {
                    $this->update_transaction_state($transaction, $newstate, $payudata);
                    $updated++;
                }

            } catch (\Exception $e) {
                mtrace("  Transaction {$transaction->referencecode}: Error - " . $e->getMessage());
                $errors++;
            }
        }

        mtrace("Completed: Processed={$processed}, Updated={$updated}, Errors={$errors}");
    }

    /**
     * Map PayU state_pol code to internal state.
     *
     * @param array $payudata The PayU response data.
     * @return string The internal state.
     */
    private function map_payu_state(array $payudata): string {
        $statepol = $payudata['state_pol'] ?? $payudata['transactionState'] ?? 7;

        switch ((int)$statepol) {
            case 4:
                return 'APPROVED';
            case 5:
                return 'EXPIRED';
            case 6:
                return 'DECLINED';
            case 7:
                return 'PENDING';
            case 104:
                return 'ERROR';
            default:
                return 'PENDING';
        }
    }

    /**
     * Get gateway configuration for a transaction.
     *
     * @param object $transaction The transaction record.
     * @return object|null The gateway config or null if not found.
     */
    private function get_gateway_config(object $transaction): ?object {
        try {
            $config = (object) payment_helper::get_gateway_configuration(
                $transaction->component,
                $transaction->paymentarea,
                $transaction->itemid,
                'payu'
            );

            return $config;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Update transaction state and deliver order if approved.
     *
     * @param object $transaction The transaction record.
     * @param string $newstate The new state.
     * @param array $payudata The full PayU transaction data.
     */
    private function update_transaction_state(object $transaction, string $newstate, array $payudata): void {
        global $DB;

        // Update the transaction state.
        $transaction->state = $newstate;
        $transaction->timemodified = time();
        $DB->update_record('paygw_payu_transactions', $transaction);

        mtrace("    Updated state to: {$newstate}");

        // If approved, deliver the order.
        if ($newstate === 'APPROVED') {
            $this->deliver_order($transaction);
        }

        // Send notification to user.
        $this->send_user_notification($transaction, $newstate);
    }

    /**
     * Deliver the order to the user.
     *
     * @param object $transaction The transaction record.
     */
    private function deliver_order(object $transaction): void {
        global $DB;

        // Check if already delivered.
        if ($transaction->state === 'DELIVERED') {
            mtrace("    Order already delivered, skipping.");
            return;
        }

        try {
            $payable = payment_helper::get_payable(
                $transaction->component,
                $transaction->paymentarea,
                $transaction->itemid
            );

            $cost = payment_helper::get_rounded_cost(
                $payable->get_amount(),
                $payable->get_currency(),
                payment_helper::get_gateway_surcharge('payu')
            );

            // Record the payment.
            $paymentid = payment_helper::save_payment(
                $payable->get_account_id(),
                $transaction->component,
                $transaction->paymentarea,
                $transaction->itemid,
                $transaction->userid,
                $cost,
                $payable->get_currency(),
                'payu'
            );

            // Deliver the order.
            payment_helper::deliver_order(
                $transaction->component,
                $transaction->paymentarea,
                $transaction->itemid,
                $paymentid,
                $transaction->userid
            );

            // Mark as delivered.
            $transaction->state = 'DELIVERED';
            $transaction->timemodified = time();
            $DB->update_record('paygw_payu_transactions', $transaction);

            mtrace("    Order delivered successfully (payment ID: {$paymentid})");

        } catch (\Exception $e) {
            mtrace("    Failed to deliver order: " . $e->getMessage());
        }
    }

    /**
     * Send notification to user about payment status.
     *
     * @param object $transaction The transaction record.
     * @param string $state The payment state.
     */
    private function send_user_notification(object $transaction, string $state): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $transaction->userid]);
        if (!$user) {
            return;
        }

        $stringkey = 'payment_' . strtolower($state);
        $subject = get_string('payment_notification', 'paygw_payu');
        $message = get_string_manager()->string_exists($stringkey, 'paygw_payu')
            ? get_string($stringkey, 'paygw_payu')
            : "Your payment status has been updated to: {$state}";

        $eventdata = new \core\message\message();
        $eventdata->component = 'paygw_payu';
        $eventdata->name = 'payment_status';
        $eventdata->userfrom = \core_user::get_noreply_user();
        $eventdata->userto = $user;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml = '<p>' . $message . '</p>';
        $eventdata->smallmessage = $message;
        $eventdata->notification = 1;

        message_send($eventdata);
    }
}
