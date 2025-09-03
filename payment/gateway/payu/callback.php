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
 * PayU callback handler
 *
 * @package     paygw_payu
 * @copyright   2024 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\notifications;

require("../../../config.php");

global $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();

// PayU response parameters.
$merchant_id = required_param('merchant_id', PARAM_INT);
$state_pol = required_param('state_pol', PARAM_INT);
$reference_sale = required_param('reference_sale', PARAM_TEXT);
$reference_pol = optional_param('reference_pol', '', PARAM_TEXT);
$signature = required_param('sign', PARAM_TEXT);
$value = required_param('value', PARAM_FLOAT);
$currency = required_param('currency', PARAM_TEXT);
$transaction_state = required_param('response_code_pol', PARAM_INT);

// Get transaction from DB.
if (!$payutx = $DB->get_record('paygw_payu', ['referencecode' => $reference_sale])) {
    die('Invalid reference code');
}

if (!$payment = $DB->get_record('payments', ['id' => $payutx->paymentid])) {
    die('Invalid payment');
}

$component   = $payment->component;
$paymentarea = $payment->paymentarea;
$itemid      = $payment->itemid;
$paymentid   = $payment->id;
$userid      = $payment->userid;

// Get config.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Round the amount.
$cost = number_format($value, 2, '.', '');

// Generate signature for validation.
$signature_string = $config->apikey . '~' . $merchant_id . '~' . $reference_sale . '~' . $cost . '~' . $currency . '~' . $state_pol;
$generated_signature = md5($signature_string);

// Validate signature.
if (strtoupper($generated_signature) !== strtoupper($signature)) {
    die('Invalid signature');
}

// Update payment amount.
$payment->amount = $value;
$payment->currency = $currency;
$DB->update_record('payments', $payment);

// Process based on transaction state.
// State 4 = Approved, 6 = Declined, 5 = Expired, 7 = Pending.
if ($state_pol == 4) {
    // Transaction approved.
    helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);
    
    // Notify user.
    notifications::notify(
        $userid,
        $payment->amount,
        $payment->currency,
        $paymentid,
        'Success completed'
    );
    
    // Update success status.
    if ($config->testmode) {
        $payutx->success = 3; // Test mode success.
    } else {
        $payutx->success = 1; // Production success.
    }
    $payutx->state = 'APPROVED';
    $payutx->transactionid = $reference_pol;
} else if ($state_pol == 6) {
    // Transaction declined.
    $payutx->success = 0;
    $payutx->state = 'DECLINED';
    $payutx->transactionid = $reference_pol;
} else if ($state_pol == 7) {
    // Transaction pending.
    $payutx->success = 0;
    $payutx->state = 'PENDING';
    $payutx->transactionid = $reference_pol;
} else {
    // Other states.
    $payutx->success = 0;
    $payutx->state = 'ERROR';
    $payutx->transactionid = $reference_pol;
}

// Update database.
if (!$DB->update_record('paygw_payu', $payutx)) {
    die('Database update error');
} else {
    echo 'OK'; // PayU expects this response.
}