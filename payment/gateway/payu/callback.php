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
 * PayU confirmation page handler (webhook/callback).
 *
 * This endpoint receives transaction status updates from PayU via HTTP POST.
 * It processes the payment and delivers the order if approved.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\payu_helper;

// This is a webhook, no login required.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../../config.php');

global $DB;

// Get PayU confirmation parameters (POST).
$merchantid = required_param('merchant_id', PARAM_TEXT);
$referencecode = required_param('reference_sale', PARAM_TEXT);
$value = required_param('value', PARAM_FLOAT);
$currency = required_param('currency', PARAM_TEXT);
$statepol = required_param('state_pol', PARAM_INT);
$sign = required_param('sign', PARAM_TEXT);

// Optional parameters.
$transactionid = optional_param('transaction_id', '', PARAM_TEXT);
$referencepol = optional_param('reference_pol', '', PARAM_TEXT);
$paymentmethodtype = optional_param('payment_method_type', '', PARAM_INT);
$paymentmethodname = optional_param('payment_method_name', '', PARAM_TEXT);
$responsemessagepol = optional_param('response_message_pol', '', PARAM_TEXT);
$extra1 = optional_param('extra1', '', PARAM_TEXT); // Reference code.
$extra2 = optional_param('extra2', '', PARAM_TEXT); // Component.
$extra3 = optional_param('extra3', '', PARAM_TEXT); // Paymentarea:itemid.

// Get local transaction record.
$localrecord = $DB->get_record('paygw_payu_transactions', ['referencecode' => $referencecode]);

if (!$localrecord) {
    // Try with extra1 if reference_sale doesn't match.
    if (!empty($extra1)) {
        $localrecord = $DB->get_record('paygw_payu_transactions', ['referencecode' => $extra1]);
    }
    if (!$localrecord) {
        debugging('PayU callback: Transaction not found for reference: ' . $referencecode, DEBUG_DEVELOPER);
        http_response_code(200);
        echo 'OK';
        exit;
    }
}

$component = $localrecord->component;
$paymentarea = $localrecord->paymentarea;
$itemid = $localrecord->itemid;
$userid = $localrecord->userid;

// Get gateway configuration.
try {
    $config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
} catch (\Exception $e) {
    debugging('PayU callback: Configuration not found: ' . $e->getMessage(), DEBUG_DEVELOPER);
    http_response_code(200);
    echo 'OK';
    exit;
}

// Create PayU helper.
$payuhelper = payu_helper::from_config($config);

// Verify signature.
if (!$payuhelper->verify_signature($sign, $referencecode, $value, $currency, $statepol)) {
    debugging('PayU callback: Invalid signature for reference: ' . $referencecode, DEBUG_DEVELOPER);
    http_response_code(200);
    echo 'OK';
    exit;
}

// Get state name.
$statename = $payuhelper->get_state_name($statepol);

// Update local transaction record.
$payuhelper->update_transaction($referencecode, $statename, $transactionid, $referencepol);

// Check if already delivered to prevent duplicates.
if ($payuhelper->is_already_delivered($referencecode)) {
    debugging('PayU callback: Already delivered for reference: ' . $referencecode, DEBUG_DEVELOPER);
    http_response_code(200);
    echo 'OK';
    exit;
}

// Process based on transaction state.
if ($payuhelper->is_approved($statepol)) {
    // Payment approved - deliver the order.
    try {
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $surcharge = helper::get_gateway_surcharge('payu');
        $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

        // Deliver the order.
        $payuhelper->deliver_order(
            $component,
            $paymentarea,
            $itemid,
            $userid,
            $cost,
            $payable->get_currency()
        );

        // Mark as delivered.
        $payuhelper->mark_as_delivered($referencecode);

        // Notify user.
        $successurl = helper::get_success_url($component, $paymentarea, $itemid);
        $payuhelper->notify_user($userid, 'successful', ['url' => $successurl->out()]);

        debugging('PayU callback: Order delivered for reference: ' . $referencecode, DEBUG_DEVELOPER);

    } catch (\Exception $e) {
        debugging('PayU callback: Delivery error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

} else if ($payuhelper->is_failed($statepol)) {
    // Payment failed - notify user.
    $payuhelper->notify_user($userid, 'failed');
    debugging('PayU callback: Payment failed for reference: ' . $referencecode . ' State: ' . $statename, DEBUG_DEVELOPER);
}

// Return success response to PayU.
http_response_code(200);
echo 'OK';
