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
 * PayU callback/confirmation handler
 *
 * @package     paygw_payu
 * @copyright   2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author      Alonso Arias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\notifications;

require("../../../config.php");

global $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();

// PayU parameters.
$merchantid    = required_param('merchant_id', PARAM_TEXT);
$state         = required_param('state_pol', PARAM_TEXT);
$referencecode = required_param('reference_sale', PARAM_TEXT);
$amount        = required_param('value', PARAM_FLOAT);
$currency      = required_param('currency', PARAM_TEXT);
$signature     = required_param('sign', PARAM_TEXT);
$transactionid = optional_param('transaction_id', '', PARAM_TEXT);
$referencepol  = optional_param('reference_pol', '', PARAM_TEXT);
$paymentmethod = optional_param('payment_method_type', '', PARAM_INT);

// Extract payment ID from reference code.
if (!preg_match('/MOODLE_(\d+)_/', $referencecode, $matches)) {
    die('Invalid reference code format');
}
$paymentid = $matches[1];

if (!$payutx = $DB->get_record('paygw_payu', ['paymentid' => $paymentid])) {
    die('FAIL. Not a valid transaction id');
}

if (!$payment = $DB->get_record('payments', ['id' => $payutx->paymentid])) {
    die('FAIL. Not a valid payment.');
}

$component   = $payment->component;
$paymentarea = $payment->paymentarea;
$itemid      = $payment->itemid;
$userid      = $payment->userid;

// Get config.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Round amount to 2 decimal places for signature validation.
$roundedamount = number_format($amount, 2, '.', '');

// Build signature for validation.
$validsignature = md5($config->apikey . '~' . $config->merchantid . '~' . $referencecode . '~' . 
                      $roundedamount . '~' . $currency . '~' . $state);

// Check signature.
if (strtoupper($validsignature) !== strtoupper($signature)) {
    die('FAIL. Signature does not match.');
}

// Process based on transaction state.
if ($state == '4') { // Approved.
    // Update payment.
    $payment->amount = $amount;
    $payment->currency = $currency;
    $DB->update_record('payments', $payment);

    // Deliver.
    helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

    // Notify user.
    notifications::notify(
        $userid,
        $payment->amount,
        $payment->currency,
        $paymentid,
        'Success completed'
    );

    // Update transaction status.
    if ($config->testmode) {
        $payutx->success = 3; // Test mode success.
    } else {
        $payutx->success = 1; // Production success.
    }

    // Write to DB.
    if (!$DB->update_record('paygw_payu', $payutx)) {
        die('FAIL. Update db error. Please contact us.');
    }

    die('SUCCESS');
} else if ($state == '6') { // Rejected.
    $payutx->success = 2;
    $DB->update_record('paygw_payu', $payutx);
    die('Transaction rejected');
} else if ($state == '5') { // Expired.
    $payutx->success = 4;
    $DB->update_record('paygw_payu', $payutx);
    die('Transaction expired');
} else if ($state == '7') { // Pending.
    die('Transaction pending');
} else {
    die('Unknown state: ' . $state);
}