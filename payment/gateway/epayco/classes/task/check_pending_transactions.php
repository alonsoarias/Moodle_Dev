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
 * Scheduled task to check pending ePayco transactions.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_epayco\task;

use core_payment\helper;
use paygw_epayco\gateway;
use paygw_epayco\epayco_helper;

/**
 * Scheduled task to check pending ePayco transactions.
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
        return get_string('task:checkpendingtransactions', 'paygw_epayco');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('ePayco: Checking pending transactions...');

        // Get pending transactions from the last 24 hours.
        $timelimit = time() - (24 * 60 * 60);

        $transactions = $DB->get_records_select(
            'paygw_epayco_transactions',
            "status = :status AND refpayco IS NOT NULL AND refpayco != '' AND timecreated > :timelimit",
            ['status' => 'PENDING', 'timelimit' => $timelimit]
        );

        if (empty($transactions)) {
            mtrace('ePayco: No pending transactions to check.');
            return;
        }

        mtrace('ePayco: Found ' . count($transactions) . ' pending transactions to check.');

        foreach ($transactions as $transaction) {
            try {
                $this->check_transaction($transaction);
            } catch (\Exception $e) {
                mtrace('ePayco: Error checking transaction ' . $transaction->reference . ': ' . $e->getMessage());
            }
        }

        mtrace('ePayco: Finished checking pending transactions.');
    }

    /**
     * Check a single transaction with ePayco API.
     *
     * @param \stdClass $transaction The transaction record.
     */
    private function check_transaction(\stdClass $transaction): void {
        global $DB;

        mtrace('ePayco: Checking transaction ' . $transaction->reference . ' (refpayco: ' . $transaction->refpayco . ')');

        // Query ePayco API.
        $apiurl = 'https://secure.epayco.co/validation/v1/reference/' . urlencode($transaction->refpayco);

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $apiurl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        ]);

        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlerror = curl_error($curl);
        curl_close($curl);

        if ($httpcode !== 200 || empty($response)) {
            throw new \Exception('Failed to query ePayco API: HTTP ' . $httpcode . ' - ' . $curlerror);
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from ePayco API');
        }

        if (!isset($data['success']) || !$data['success']) {
            throw new \Exception('ePayco API returned error: ' . ($data['message'] ?? 'Unknown error'));
        }

        $txdata = $data['data'] ?? [];
        $codresponse = (int)($txdata['x_cod_response'] ?? 0);
        $responsemotivo = $txdata['x_response_reason_text'] ?? '';
        $amount = $txdata['x_amount'] ?? 0;
        $currency = $txdata['x_currency_code'] ?? '';

        // Map ePayco status to internal status.
        $newstatus = match($codresponse) {
            epayco_helper::STATUS_ACCEPTED => 'APPROVED',
            epayco_helper::STATUS_REJECTED => 'REJECTED',
            epayco_helper::STATUS_PENDING => 'PENDING',
            epayco_helper::STATUS_FAILED => 'FAILED',
            default => 'UNKNOWN',
        };

        mtrace('ePayco: Transaction ' . $transaction->reference . ' status: ' . $newstatus . ' (code: ' . $codresponse . ')');

        // Only update if status changed.
        if ($transaction->status === $newstatus) {
            mtrace('ePayco: Transaction ' . $transaction->reference . ' status unchanged.');
            return;
        }

        // Update transaction.
        $DB->update_record('paygw_epayco_transactions', (object) [
            'id' => $transaction->id,
            'status' => $newstatus,
            'response' => json_encode([
                'cod_response' => $codresponse,
                'response_text' => $responsemotivo,
            ]),
            'timemodified' => time(),
        ]);

        // If payment is now approved, deliver the order.
        if ($codresponse == epayco_helper::STATUS_ACCEPTED && empty($transaction->paymentid)) {
            try {
                // Get gateway configuration.
                $config = (object) helper::get_gateway_configuration(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    'epayco'
                );

                // Get payable info.
                $payable = helper::get_payable(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid
                );
                $surcharge = helper::get_gateway_surcharge('epayco');
                $paymentamount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

                // Verify amount matches.
                if (abs((float)$amount - $paymentamount) > 0.01) {
                    mtrace('ePayco: Amount mismatch for ' . $transaction->reference .
                           '. Expected: ' . $paymentamount . ', Got: ' . $amount);
                    return;
                }

                // Deliver the order.
                $paymentid = helper::save_payment(
                    $payable->get_account_id(),
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    (int)$transaction->userid,
                    $paymentamount,
                    $payable->get_currency(),
                    'epayco'
                );

                helper::deliver_order(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    $paymentid,
                    (int)$transaction->userid
                );

                // Update transaction with payment ID.
                $DB->set_field('paygw_epayco_transactions', 'paymentid', $paymentid, ['id' => $transaction->id]);

                mtrace('ePayco: Payment delivered for transaction ' . $transaction->reference);

                // Send notification email.
                $user = \core_user::get_user($transaction->userid);
                if ($user) {
                    $epaycohelper = gateway::create_helper_from_config($config);
                    $epaycohelper->send_notification_email($user, $config, 'completed', [
                        'amount' => $amount,
                        'currency' => $currency,
                        'orderid' => $transaction->reference,
                    ]);
                }

            } catch (\Exception $e) {
                mtrace('ePayco: Error delivering order for ' . $transaction->reference . ': ' . $e->getMessage());
            }
        }
    }
}
