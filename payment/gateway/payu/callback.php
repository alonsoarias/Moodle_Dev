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
 * PayU callback handler (confirmation URL)
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\notifications;

require("../../../config.php");

global $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();

// Get PayU response parameters
$merchant_id = required_param('merchant_id', PARAM_TEXT);
$reference_sale = required_param('reference_sale', PARAM_TEXT);
$value = required_param('value', PARAM_FLOAT);
$currency = required_param('currency', PARAM_TEXT);
$state_pol = required_param('state_pol', PARAM_INT);
$sign = required_param('sign', PARAM_TEXT);
$payment_method_type = optional_param('payment_method_type', '', PARAM_INT);
$reference_pol = optional_param('reference_pol', '', PARAM_TEXT);
$transaction_id = optional_param('transaction_id', '', PARAM_TEXT);
$payment_method_id = optional_param('payment_method_id', 0, PARAM_INT);
$payment_method_name = optional_param('payment_method_name', '', PARAM_TEXT);
$response_message_pol = optional_param('response_message_pol', '', PARAM_TEXT);
$extra1 = optional_param('extra1', '', PARAM_TEXT); // payment ID
$extra2 = optional_param('extra2', '', PARAM_TEXT); // component
$extra3 = optional_param('extra3', '', PARAM_TEXT); // payment area

// Validate payment ID
if (empty($extra1)) {
    die('ERROR: Missing payment ID');
}

$paymentid = $extra1;

// Get PayU transaction record
$payu_record = $DB->get_record('paygw_payu', ['paymentid' => $paymentid]);
if (!$payu_record) {
    die('ERROR: Transaction not found');
}

// Get payment record
$payment = $DB->get_record('payments', ['id' => $paymentid]);
if (!$payment) {
    die('ERROR: Payment not found');
}

$component = $payment->component;
$paymentarea = $payment->paymentarea;
$itemid = $payment->itemid;
$userid = $payment->userid;

// Get gateway configuration
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Get API key based on environment
if ($config->environment === 'sandbox') {
    $credentials = \paygw_payu\gateway::get_test_credentials($config->country);
    $apiKey = $credentials['apikey'];
} else {
    $apiKey = $config->apikey;
}

// Format value to 1 decimal for signature
$formatted_value = number_format($value, 1, '.', '');

// Validate signature
$signature_string = $apiKey . "~" . $merchant_id . "~" . $reference_sale . "~" . $formatted_value . "~" . $currency . "~" . $state_pol;
$calculated_sign = md5($signature_string);

if (strtoupper($calculated_sign) !== strtoupper($sign)) {
    die('ERROR: Invalid signature');
}

// Map PayU state to internal status
$success = 0;
$state_message = '';

switch ($state_pol) {
    case 4: // Approved
        $success = 1;
        $state_message = 'APPROVED';
        break;
    case 6: // Declined
        $success = 4;
        $state_message = 'DECLINED';
        break;
    case 7: // Pending
        $success = 0;
        $state_message = 'PENDING';
        break;
    case 5: // Expired
        $success = 4;
        $state_message = 'EXPIRED';
        break;
    case 104: // Error
        $success = 4;
        $state_message = 'ERROR';
        break;
    default:
        $success = 0;
        $state_message = 'UNKNOWN';
}

// Update payment record
$payment->amount = $value;
$payment->currency = $currency;
$DB->update_record('payments', $payment);

// Update PayU transaction record
$payu_record->transactionid = $transaction_id;
$payu_record->orderId = $reference_pol;
$payu_record->amount = $value;
$payu_record->currency = $currency;
$payu_record->state = $state_message;
$payu_record->success = $success;
$payu_record->timemodified = time();

// Add test mode indicator if in sandbox
if ($config->environment === 'sandbox') {
    $payu_record->success = ($success == 1) ? 3 : $success;
}

if (!$DB->update_record('paygw_payu', $payu_record)) {
    die('ERROR: Failed to update transaction');
}

// If payment is approved, deliver the order
if ($success == 1) {
    helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);
    
    // Send notification to user
    notifications::notify(
        $userid,
        $payment->amount,
        $payment->currency,
        $paymentid,
        'payment_completed'
    );
}

// Return success response to PayU
echo 'OK';