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
 * Handles ePayco confirmation callbacks (server-to-server).
 *
 * This file receives POST notifications from ePayco when a transaction
 * status changes. It validates the signature and processes the payment.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_epayco\gateway;
use paygw_epayco\epayco_helper;

// No login required - this is a server callback.
require_once(__DIR__ . '/../../../config.php');

// Get parameters from ePayco confirmation.
$refpayco = optional_param('x_ref_payco', '', PARAM_ALPHANUMEXT);
$transactionid = optional_param('x_id_invoice', '', PARAM_ALPHANUMEXT);
$amount = optional_param('x_amount', '', PARAM_TEXT);
$currency = optional_param('x_currency_code', '', PARAM_ALPHA);
$signature = optional_param('x_signature', '', PARAM_ALPHANUMEXT);
$codresponse = optional_param('x_cod_response', '', PARAM_INT);
$response = optional_param('x_response', '', PARAM_TEXT);
$responsemotivo = optional_param('x_response_reason_text', '', PARAM_TEXT);
$franchise = optional_param('x_franchise', '', PARAM_TEXT);
$approval = optional_param('x_approval_code', '', PARAM_ALPHANUMEXT);
$custidcliente = optional_param('x_cust_id_cliente', '', PARAM_ALPHANUMEXT);

// Extra parameters for identification.
$component = optional_param('x_extra1', '', PARAM_COMPONENT);
$paymentarea = optional_param('x_extra2', '', PARAM_AREA);
$itemid = optional_param('x_extra3', 0, PARAM_INT);
$userid = optional_param('x_extra4', 0, PARAM_INT);

// Log the confirmation request.
debugging('ePayco confirmation received: ref=' . $refpayco . ', cod_response=' . $codresponse, DEBUG_DEVELOPER);

// Basic validation.
if (empty($refpayco) || empty($transactionid) || empty($component) || empty($paymentarea) || !$itemid) {
    debugging('ePayco confirmation: Missing required parameters', DEBUG_DEVELOPER);
    http_response_code(400);
    echo 'Missing required parameters';
    die();
}

try {
    // Get gateway configuration.
    $config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'epayco');

    // Create helper instance.
    $epaycohelper = gateway::create_helper_from_config($config);

    // Verify signature.
    $expectedsignature = $epaycohelper->calculate_signature($refpayco, $transactionid, $amount, $currency);

    if (!hash_equals($expectedsignature, $signature)) {
        debugging('ePayco confirmation: Invalid signature. Expected: ' . $expectedsignature . ', Got: ' . $signature, DEBUG_DEVELOPER);
        http_response_code(400);
        echo 'Invalid signature';
        die();
    }

    // Get internal transaction.
    $transaction = $epaycohelper->get_transaction_by_reference($transactionid);

    if (!$transaction) {
        debugging('ePayco confirmation: Transaction not found for reference: ' . $transactionid, DEBUG_DEVELOPER);
        http_response_code(404);
        echo 'Transaction not found';
        die();
    }

    // Map ePayco status to internal status.
    $newstatus = $epaycohelper->map_epayco_status($codresponse);

    // Update transaction.
    $epaycohelper->update_transaction($transaction->id, [
        'refpayco' => $refpayco,
        'status' => $newstatus,
        'response' => [
            'cod_response' => $codresponse,
            'response_text' => $responsemotivo,
            'franchise' => $franchise,
            'approval_code' => $approval,
        ],
    ]);

    // If payment is approved, deliver the order.
    if ($codresponse == epayco_helper::STATUS_ACCEPTED) {
        // Get payable info.
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('epayco');
        $paymentamount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

        // Verify amount matches.
        if (abs((float)$amount - $paymentamount) > 0.01) {
            debugging('ePayco confirmation: Amount mismatch. Expected: ' . $paymentamount . ', Got: ' . $amount, DEBUG_DEVELOPER);
            http_response_code(400);
            echo 'Amount mismatch';
            die();
        }

        // Deliver the order.
        $paymentid = helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            (int)$userid,
            $paymentamount,
            $payable->get_currency(),
            'epayco'
        );

        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, (int)$userid);

        // Update transaction with payment ID.
        $epaycohelper->update_transaction($transaction->id, [
            'paymentid' => $paymentid,
        ]);

        // Send notification email.
        if ($userid) {
            $user = \core_user::get_user($userid);
            if ($user) {
                $epaycohelper->send_notification_email($user, $config, 'completed', [
                    'amount' => $amount,
                    'currency' => $currency,
                    'orderid' => $transactionid,
                ]);
            }
        }

        debugging('ePayco confirmation: Payment processed successfully for ref=' . $refpayco, DEBUG_DEVELOPER);
    } else if ($codresponse == epayco_helper::STATUS_PENDING) {
        // Send pending notification email.
        if ($userid) {
            $user = \core_user::get_user($userid);
            if ($user) {
                $epaycohelper->send_notification_email($user, $config, 'pending', [
                    'amount' => $amount,
                    'currency' => $currency,
                    'orderid' => $transactionid,
                ]);
            }
        }

        debugging('ePayco confirmation: Payment pending for ref=' . $refpayco, DEBUG_DEVELOPER);
    } else {
        debugging('ePayco confirmation: Payment not approved (code=' . $codresponse . ') for ref=' . $refpayco, DEBUG_DEVELOPER);
    }

    // Return success.
    http_response_code(200);
    echo 'OK';

} catch (\Exception $e) {
    debugging('ePayco confirmation error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    http_response_code(500);
    echo 'Internal error';
}
