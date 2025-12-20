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
 * Handles Addi webhook callbacks (server-to-server).
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_addi\gateway;
use paygw_addi\addi_helper;

// No login required - this is a server callback.
require_once(__DIR__ . '/../../../config.php');

// Get raw POST body.
$rawbody = file_get_contents('php://input');

if (empty($rawbody)) {
    debugging('Addi webhook: Empty request body', DEBUG_DEVELOPER);
    http_response_code(400);
    echo json_encode(['error' => 'Empty request body']);
    die();
}

// Parse JSON.
$data = json_decode($rawbody, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    debugging('Addi webhook: Invalid JSON: ' . json_last_error_msg(), DEBUG_DEVELOPER);
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    die();
}

// Log the webhook request.
debugging('Addi webhook received: ' . $rawbody, DEBUG_DEVELOPER);

// Extract application information.
$applicationid = $data['applicationId'] ?? $data['application_id'] ?? '';
$orderid = $data['orderId'] ?? $data['order_id'] ?? '';
$status = $data['status'] ?? '';
$eventtype = $data['eventType'] ?? $data['event_type'] ?? '';

if (empty($applicationid) && empty($orderid)) {
    debugging('Addi webhook: Missing applicationId or orderId', DEBUG_DEVELOPER);
    http_response_code(400);
    echo json_encode(['error' => 'Missing applicationId or orderId']);
    die();
}

global $DB;

// Find the transaction.
$transaction = null;
if (!empty($applicationid)) {
    $transaction = $DB->get_record('paygw_addi_transactions', ['applicationid' => $applicationid]);
}
if (!$transaction && !empty($orderid)) {
    $transaction = $DB->get_record('paygw_addi_transactions', ['reference' => $orderid]);
}

if (!$transaction) {
    debugging('Addi webhook: Transaction not found', DEBUG_DEVELOPER);
    http_response_code(404);
    echo json_encode(['error' => 'Transaction not found']);
    die();
}

try {
    // Get gateway configuration.
    $config = (object) helper::get_gateway_configuration(
        $transaction->component,
        $transaction->paymentarea,
        $transaction->itemid,
        'addi'
    );

    $addihelper = gateway::create_helper_from_config($config);

    // Map status.
    $newstatus = $addihelper->map_addi_status($status);

    // Update transaction.
    $addihelper->update_transaction($transaction->id, [
        'status' => $newstatus,
        'response' => $data,
    ]);

    // If approved and not yet delivered, deliver the order.
    if (strtoupper($status) === addi_helper::STATUS_APPROVED && empty($transaction->paymentid)) {
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

        // Send notification email.
        $user = \core_user::get_user($transaction->userid);
        if ($user) {
            $addihelper->send_notification_email($user, $config, 'completed', [
                'amount' => number_format($transaction->amount, 0, ',', '.') . ' ' . $transaction->currency,
                'currency' => $transaction->currency,
                'orderid' => $transaction->reference,
            ]);
        }

        debugging('Addi webhook: Payment delivered for ' . $transaction->reference, DEBUG_DEVELOPER);
    }

    // Return success.
    http_response_code(200);
    echo json_encode(['success' => true]);

} catch (\Exception $e) {
    debugging('Addi webhook error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    http_response_code(500);
    echo json_encode(['error' => 'Internal error']);
}
