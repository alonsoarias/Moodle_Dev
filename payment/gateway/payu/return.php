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
 * PayU response page handler.
 *
 * This page receives the user after completing payment on PayU.
 * It displays the payment result and redirects to the success page.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\payu_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB;

// Get payment context parameters.
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);

// Get PayU response parameters (GET).
$merchantid = optional_param('merchantId', '', PARAM_TEXT);
$referencecode = optional_param('referenceCode', '', PARAM_TEXT);
$txvalue = optional_param('TX_VALUE', 0, PARAM_FLOAT);
$currency = optional_param('currency', '', PARAM_TEXT);
$transactionstate = optional_param('transactionState', 0, PARAM_INT);
$signature = optional_param('signature', '', PARAM_TEXT);
$referencepol = optional_param('reference_pol', '', PARAM_TEXT);
$message = optional_param('message', '', PARAM_TEXT);
$lappaymentmethod = optional_param('lapPaymentMethod', '', PARAM_TEXT);
$laptransactionstate = optional_param('lapTransactionState', '', PARAM_TEXT);
$extra1 = optional_param('extra1', '', PARAM_TEXT);

// Get success URL for redirect.
$successurl = helper::get_success_url($component, $paymentarea, $itemid);

// If we don't have transaction info, just redirect.
if (empty($referencecode) && empty($extra1)) {
    redirect($successurl);
}

// Use extra1 as reference if available.
$ref = !empty($extra1) ? $extra1 : $referencecode;

// Get gateway configuration.
try {
    $config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
} catch (\Exception $e) {
    redirect($successurl, get_string('paymentunknownstatus', 'paygw_payu'), null, 'warning');
}

// Create PayU helper.
$payuhelper = payu_helper::from_config($config);

// Verify signature if present.
$validsignature = true;
if (!empty($signature) && !empty($referencecode)) {
    $validsignature = $payuhelper->verify_signature($signature, $referencecode, $txvalue, $currency, $transactionstate);
}

// Get local transaction.
$localrecord = $payuhelper->get_transaction_by_reference($ref);

// Update local record if found.
if ($localrecord && !empty($referencepol)) {
    $statename = $payuhelper->get_state_name($transactionstate);
    $payuhelper->update_transaction($ref, $statename, '', $referencepol);
}

// Determine message based on state.
$notificationtype = 'info';
$statusmessage = '';

switch ($transactionstate) {
    case payu_helper::STATE_APPROVED:
        $notificationtype = 'success';
        $statusmessage = get_string('paymentsuccessful', 'paygw_payu');
        break;

    case payu_helper::STATE_DECLINED:
        $notificationtype = 'error';
        $statusmessage = get_string('paymentdeclined', 'paygw_payu');
        break;

    case payu_helper::STATE_PENDING:
        $notificationtype = 'warning';
        $statusmessage = get_string('paymentpending', 'paygw_payu');
        break;

    case payu_helper::STATE_EXPIRED:
        $notificationtype = 'error';
        $statusmessage = get_string('paymentexpired', 'paygw_payu');
        break;

    case payu_helper::STATE_ERROR:
        $notificationtype = 'error';
        $statusmessage = get_string('paymenterror', 'paygw_payu');
        break;

    default:
        $notificationtype = 'warning';
        $statusmessage = get_string('paymentunknownstatus', 'paygw_payu');
}

// Add PayU message if available.
if (!empty($message)) {
    $statusmessage .= ' - ' . s($message);
}

// Add signature warning.
if (!$validsignature) {
    $statusmessage .= ' ' . get_string('signatureinvalid', 'paygw_payu');
    $notificationtype = 'warning';
}

// Redirect with message.
redirect($successurl, $statusmessage, null, $notificationtype);
