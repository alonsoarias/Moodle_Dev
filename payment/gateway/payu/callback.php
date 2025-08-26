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
 * PayU payment gateway callback handler.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\api;
use paygw_payu\notifications;

// No login required for callback.
require_once(__DIR__ . '/../../../config.php');

global $DB;

// Log incoming data for debugging in test mode.
$debugmode = get_config('paygw_payu', 'debugmode');
if ($debugmode) {
    error_log('PayU Callback Data: ' . print_r($_REQUEST, true));
}

// Get callback parameters.
$merchantid = required_param('merchant_id', PARAM_INT);
$referencesale = required_param('reference_sale', PARAM_INT);
$value = required_param('value', PARAM_RAW);
$currency = required_param('currency', PARAM_TEXT);
$statepol = required_param('state_pol', PARAM_INT);
$signature = required_param('sign', PARAM_RAW);

// Optional parameters.
$responsecode = optional_param('response_code_pol', '', PARAM_TEXT);
$responsemessage = optional_param('response_message_pol', '', PARAM_TEXT);
$paymentmethod = optional_param('payment_method', '', PARAM_TEXT);
$paymentmethodtype = optional_param('payment_method_type', '', PARAM_TEXT);
$transactionid = optional_param('transaction_id', '', PARAM_TEXT);
$email = optional_param('email_buyer', '', PARAM_EMAIL);

// Get payment record.
if (!$payment = $DB->get_record('payments', ['id' => $referencesale])) {
    http_response_code(400);
    die('Invalid payment reference');
}

// Get gateway configuration.
$config = (object) helper::get_gateway_configuration(
    $payment->component,
    $payment->paymentarea,
    $payment->itemid,
    'payu'
);

// Validate merchant ID.
if ($merchantid != $config->merchantid) {
    http_response_code(400);
    die('Invalid merchant ID');
}

// Initialize API and validate signature.
$api = new api($config);
if (!$api->validate_callback_signature($_REQUEST)) {
    http_response_code(400);
    die('Invalid signature');
}

// Get or create PayU transaction record.
$transaction = $DB->get_record('paygw_payu', ['paymentid' => $referencesale]);
if (!$transaction) {
    $transaction = new stdClass();
    $transaction->paymentid = $referencesale;
    $transaction->payu_transaction_id = $transactionid;
    $transaction->payment_method = $paymentmethod;
    $transaction->amount = $value;
    $transaction->currency = $currency;
    $transaction->timecreated = time();
}

// Map PayU state to internal state.
$states = [
    4 => 'APPROVED',
    6 => 'DECLINED',
    7 => 'PENDING',
    5 => 'EXPIRED',
    104 => 'ERROR',
];

$newstate = $states[$statepol] ?? 'UNKNOWN';
$transaction->state = $newstate;
$transaction->response_code = $responsecode;
$transaction->timemodified = time();

// Update or insert transaction record.
if (!empty($transaction->id)) {
    $DB->update_record('paygw_payu', $transaction);
} else {
    $transaction->id = $DB->insert_record('paygw_payu', $transaction);
}

// Process based on state.
switch ($newstate) {
    case 'APPROVED':
        // Deliver order if not already delivered.
        if ($payment->status != 'complete') {
            helper::deliver_order(
                $payment->component,
                $payment->paymentarea,
                $payment->itemid,
                $payment->id,
                $payment->userid
            );
            
            // Send notification if enabled.
            if (!empty($config->enablenotifications)) {
                notifications::send_payment_receipt(
                    $payment->userid,
                    $payment->amount,
                    $payment->currency,
                    $payment->id,
                    'APPROVED',
                    [
                        'transactionid' => $transactionid,
                        'paymentmethod' => $paymentmethod,
                    ]
                );
            }
        }
        break;
        
    case 'DECLINED':
    case 'EXPIRED':
    case 'ERROR':
        // Send failure notification if enabled.
        if (!empty($config->enablenotifications)) {
            notifications::send_payment_receipt(
                $payment->userid,
                $payment->amount,
                $payment->currency,
                $payment->id,
                $newstate,
                [
                    'transactionid' => $transactionid,
                    'paymentmethod' => $paymentmethod,
                    'errormessage' => $responsemessage,
                ]
            );
        }
        break;
        
    case 'PENDING':
        // Payment still pending - no action needed.
        break;
}

// Return success response to PayU.
http_response_code(200);
echo 'OK';