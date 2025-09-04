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
 * Redirects user back to the original page after PayU payment
 *
 * @package   paygw_payu
 * @copyright 2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require("../../../config.php");
global $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();

require_login();

// Get PayU response parameters
$merchant_id = required_param('merchantId', PARAM_TEXT);
$reference_sale = required_param('referenceCode', PARAM_TEXT);
$tx_value = required_param('TX_VALUE', PARAM_TEXT);
$currency = required_param('currency', PARAM_TEXT);
$transaction_state = required_param('transactionState', PARAM_INT);
$signature = required_param('signature', PARAM_TEXT);
$reference_pol = optional_param('reference_pol', '', PARAM_TEXT);
$cus = optional_param('cus', '', PARAM_TEXT);
$description = optional_param('description', '', PARAM_TEXT);
$lap_response_code = optional_param('lapResponseCode', '', PARAM_TEXT);
$lap_payment_method = optional_param('lapPaymentMethod', '', PARAM_TEXT);
$lap_payment_method_type = optional_param('lapPaymentMethodType', '', PARAM_TEXT);
$lap_transaction_state = optional_param('lapTransactionState', '', PARAM_TEXT);
$message = optional_param('message', '', PARAM_TEXT);
$extra1 = optional_param('extra1', '', PARAM_TEXT); // payment ID
$extra2 = optional_param('extra2', '', PARAM_TEXT); // component  
$extra3 = optional_param('extra3', '', PARAM_TEXT); // payment area

// Validate payment ID
if (empty($extra1)) {
    throw new \moodle_exception('error_notvalidpaymentid', 'paygw_payu');
}

$paymentid = $extra1;

// Get PayU transaction record
$payu_record = $DB->get_record('paygw_payu', ['paymentid' => $paymentid]);
if (!$payu_record) {
    throw new \moodle_exception('error_notvalidtxid', 'paygw_payu');
}

// Get payment record
$payment = $DB->get_record('payments', ['id' => $paymentid]);
if (!$payment) {
    throw new \moodle_exception('error_notvalidpayment', 'paygw_payu');
}

$component = $payment->component;
$paymentarea = $payment->paymentarea;
$itemid = $payment->itemid;

// Get gateway configuration
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Get API key based on environment
if ($config->environment === 'sandbox') {
    $credentials = \paygw_payu\gateway::get_test_credentials($config->country);
    $apiKey = $credentials['apikey'];
} else {
    $apiKey = $config->apikey;
}

// Validate signature
$signature_string = $apiKey . "~" . $merchant_id . "~" . $reference_sale . "~" . $tx_value . "~" . $currency . "~" . $transaction_state;
$calculated_signature = md5($signature_string);

$valid_signature = (strtoupper($calculated_signature) === strtoupper($signature));

// Determine transaction status
$success = false;
$status_message = '';

switch ($transaction_state) {
    case 4: // Approved
        $success = true;
        $status_message = get_string('payment_success', 'paygw_payu');
        $notification_type = 'success';
        break;
    case 6: // Declined
        $success = false;
        $status_message = get_string('payment_declined', 'paygw_payu');
        $notification_type = 'error';
        break;
    case 7: // Pending
        $success = false;
        $status_message = get_string('payment_pending', 'paygw_payu');
        $notification_type = 'info';
        break;
    case 5: // Expired
        $success = false;
        $status_message = get_string('payment_expired', 'paygw_payu');
        $notification_type = 'error';
        break;
    case 104: // Error
        $success = false;
        $status_message = get_string('payment_error', 'paygw_payu');
        $notification_type = 'error';
        break;
    default:
        $success = false;
        $status_message = get_string('payment_unknown', 'paygw_payu');
        $notification_type = 'warning';
}

// Add signature validation message
if (!$valid_signature) {
    $status_message .= ' ' . get_string('signature_invalid', 'paygw_payu');
    $notification_type = 'error';
}

// Add PayU message if available
if (!empty($message)) {
    $status_message .= ' - ' . $message;
}

// Update PayU transaction record with latest status
if (!empty($reference_pol)) {
    $payu_record->orderId = $reference_pol;
}
$payu_record->timemodified = time();
$DB->update_record('paygw_payu', $payu_record);

// Get success URL
$url = helper::get_success_url($component, $paymentarea, $itemid);

// Redirect with appropriate message
redirect($url, $status_message, null, $notification_type);