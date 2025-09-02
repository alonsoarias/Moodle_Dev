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
 * Receipt page for cash payments.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB, $PAGE, $OUTPUT, $USER;

// Get parameters.
$orderid = optional_param('orderid', '', PARAM_TEXT);
$reference = optional_param('reference', '', PARAM_TEXT);
$paymentid = optional_param('paymentid', 0, PARAM_INT);

// Validate that we have at least one identifier.
if (empty($orderid) && empty($reference) && empty($paymentid)) {
    throw new moodle_exception('missingparameters', 'paygw_payu');
}

// Find the transaction.
$transaction = null;
if ($paymentid) {
    $transaction = $DB->get_record('paygw_payu', ['paymentid' => $paymentid]);
} else if ($orderid) {
    $transaction = $DB->get_record('paygw_payu', ['payu_order_id' => $orderid]);
}

if (!$transaction) {
    throw new moodle_exception('transactionnotfound', 'paygw_payu');
}

// Get payment record.
$payment = $DB->get_record('payments', ['id' => $transaction->paymentid], '*', MUST_EXIST);

// Verify user owns this payment.
if ($payment->userid != $USER->id && !is_siteadmin()) {
    throw new moodle_exception('invaliduser', 'paygw_payu');
}

// Get payable information.
$payable = helper::get_payable($payment->component, $payment->paymentarea, $payment->itemid);

// Get configuration.
$config = (object) helper::get_gateway_configuration(
    $payment->component,
    $payment->paymentarea,
    $payment->itemid,
    'payu'
);

// Setup page.
$PAGE->set_url('/payment/gateway/payu/receipt.php', ['paymentid' => $paymentid]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('paymentreceipt', 'paygw_payu'));
$PAGE->set_heading(get_string('paymentreceipt', 'paygw_payu'));

// Parse extra parameters if available.
$extraparams = [];
if (!empty($transaction->extra_parameters)) {
    $extraparams = json_decode($transaction->extra_parameters, true);
}

// Prepare template context.
$templatecontext = [
    'transactionid' => $transaction->payu_transaction_id,
    'orderid' => $transaction->payu_order_id,
    'reference' => $reference ?: $transaction->payu_order_id,
    'amount' => $transaction->amount,
    'currency' => $transaction->currency,
    'paymentmethod' => $transaction->payment_method,
    'state' => $transaction->state,
    'description' => $payable->get_description(),
    'userid' => $payment->userid,
    'username' => fullname($USER),
    'timecreated' => userdate($transaction->timecreated),
    'timemodified' => userdate($transaction->timemodified),
];

// Add cash-specific information.
if (in_array($transaction->payment_method, ['EFECTY', 'BALOTO', 'BANK_REFERENCED', 'OTHERS_CASH'])) {
    $templatecontext['iscash'] = true;
    
    // Calculate expiration date (usually 7 days from creation).
    $expirationtime = $transaction->timecreated + (7 * 24 * 60 * 60);
    $templatecontext['expirationdate'] = userdate($expirationtime);
    
    // Add receipt URLs if available.
    if (!empty($extraparams['URL_PAYMENT_RECEIPT_HTML'])) {
        $templatecontext['receipthtml'] = $extraparams['URL_PAYMENT_RECEIPT_HTML'];
    }
    if (!empty($extraparams['URL_PAYMENT_RECEIPT_PDF'])) {
        $templatecontext['receiptpdf'] = $extraparams['URL_PAYMENT_RECEIPT_PDF'];
    }
    
    // Add payment instructions.
    $templatecontext['instructions'] = get_string('cash_instructions_' . strtolower($transaction->payment_method), 'paygw_payu');
}

// Add PSE-specific information.
if ($transaction->payment_method === 'PSE') {
    $templatecontext['ispse'] = true;
    if (!empty($extraparams['BANK_URL'])) {
        $templatecontext['bankurl'] = $extraparams['BANK_URL'];
    }
}

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('paygw_payu/receipt', $templatecontext);
echo $OUTPUT->footer();