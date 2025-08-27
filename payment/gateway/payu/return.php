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
 * Return page after PayU payment processing.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');

global $DB, $USER;

require_login();

// Get return parameters from PayU.
$merchantid = required_param('merchantId', PARAM_INT);
$referencecode = required_param('referenceCode', PARAM_INT); 
$signature = required_param('signature', PARAM_RAW);
$currency = required_param('currency', PARAM_TEXT);
$transactionstate = required_param('transactionState', PARAM_INT);
$value = required_param('TX_VALUE', PARAM_RAW);
$message = optional_param('message', '', PARAM_TEXT);
$transactionid = optional_param('transactionId', PARAM_TEXT);
$referencepol = optional_param('reference_pol', PARAM_TEXT);
$responsecode = optional_param('lapResponseCode', PARAM_TEXT);

// Get payment record.
if (!$payment = $DB->get_record('payments', ['id' => $referencecode])) {
    throw new \moodle_exception('invalidreference', 'paygw_payu');
}

// Get PayU transaction record.
if (!$payutx = $DB->get_record('paygw_payu', ['paymentid' => $payment->id])) {
    throw new \moodle_exception('invalidtransaction', 'paygw_payu');
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
    throw new \moodle_exception('invalidmerchant', 'paygw_payu');
}

// Generate expected signature.
$formattedvalue = number_format((float)$value, 1, '.', '');
$expectedsignature = md5($config->apikey . '~' . $merchantid . '~' . 
                        $referencecode . '~' . $formattedvalue . '~' . 
                        $currency . '~' . $transactionstate);

// Validate signature.
if (strtoupper($expectedsignature) !== strtoupper($signature)) {
    throw new \moodle_exception('invalidsignature', 'paygw_payu');
}

// Update transaction record.
$payutx->payu_transaction_id = $transactionid;
$payutx->response_code = $responsecode;
$payutx->timemodified = time();

// Map PayU transaction states.
switch ($transactionstate) {
    case 4: // Approved
        $payutx->state = 'APPROVED';
        $success = true;
        break;
    case 6: // Declined
        $payutx->state = 'DECLINED';
        $success = false;
        break;
    case 5: // Expired
        $payutx->state = 'EXPIRED';
        $success = false;
        break;
    case 7: // Pending
        $payutx->state = 'PENDING';
        $success = false;
        break;
    default:
        $payutx->state = 'ERROR';
        $success = false;
}

$DB->update_record('paygw_payu', $payutx);

// Get success URL.
$url = helper::get_success_url($payment->component, $payment->paymentarea, $payment->itemid);

// Redirect with appropriate message.
if ($success) {
    // Deliver order if approved.
    if ($payutx->state === 'APPROVED') {
        helper::deliver_order($payment->component, $payment->paymentarea, 
                            $payment->itemid, $payment->id, $payment->userid);
    }
    redirect($url, get_string('paymentsuccess', 'paygw_payu'), 0, 'success');
} else if ($payutx->state === 'PENDING') {
    redirect($url, get_string('paymentpending', 'paygw_payu'), 0, 'info');
} else {
    $errormsg = get_string('paymenterror', 'paygw_payu');
    if (!empty($message)) {
        $errormsg .= ': ' . $message;
    }
    redirect($url, $errormsg, 0, 'error');
}