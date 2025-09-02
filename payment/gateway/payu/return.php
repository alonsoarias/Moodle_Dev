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
 * Return page after PayU payment.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\api;
use paygw_payu\notifications;

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB, $USER, $PAGE, $OUTPUT;

// Get return parameters from PayU.
$merchantid = required_param('merchantId', PARAM_INT);
$referencecode = required_param('referenceCode', PARAM_TEXT);
$signature = required_param('signature', PARAM_RAW);
$txvalue = required_param('TX_VALUE', PARAM_RAW);
$currency = required_param('currency', PARAM_TEXT);
$transactionstate = required_param('transactionState', PARAM_INT);
$transactionid = required_param('transactionId', PARAM_TEXT);
$referencepol = optional_param('reference_pol', '', PARAM_TEXT);
$responsecode = optional_param('lapResponseCode', '', PARAM_TEXT);
$paymentmethodname = optional_param('lapPaymentMethodType', '', PARAM_TEXT);

// Extract payment ID from reference code.
if (preg_match('/MOODLE-(\d+)-/', $referencecode, $matches)) {
    $paymentid = $matches[1];
} else {
    throw new moodle_exception('invalidreference', 'paygw_payu');
}

// Get payment record.
$payment = $DB->get_record('payments', ['id' => $paymentid], '*', MUST_EXIST);

// Verify user owns this payment.
if ($payment->userid != $USER->id) {
    throw new moodle_exception('invaliduser', 'paygw_payu');
}

// Get PayU transaction record.
$transaction = $DB->get_record('paygw_payu', ['paymentid' => $payment->id]);
if (!$transaction) {
    $transaction = new stdClass();
    $transaction->paymentid = $payment->id;
    $transaction->timecreated = time();
}

// Map PayU states to internal states.
$statemap = [
    4 => 'APPROVED',
    6 => 'DECLINED', 
    7 => 'PENDING',
    5 => 'EXPIRED',
    104 => 'ERROR',
];

$newstate = $statemap[$transactionstate] ?? 'UNKNOWN';

// Update transaction record.
$transaction->payu_transaction_id = $transactionid;
$transaction->state = $newstate;
$transaction->response_code = $responsecode;
$transaction->payment_method = $paymentmethodname;
$transaction->timemodified = time();

if (!empty($transaction->id)) {
    $DB->update_record('paygw_payu', $transaction);
} else {
    $transaction->id = $DB->insert_record('paygw_payu', $transaction);
}

// Get config to verify signature if provided.
$config = (object) helper::get_gateway_configuration(
    $payment->component,
    $payment->paymentarea,
    $payment->itemid,
    'payu'
);

// Verify signature if provided.
if (!empty($signature) && !empty($config->apikey)) {
    $api = new api($config);
    $data = [
        'merchant_id' => $config->merchantid,
        'reference_sale' => $referencecode,
        'value' => $txvalue,
        'currency' => $currency,
        'state_pol' => $transactionstate,
        'sign' => $signature,
    ];
    
    if (!$api->validate_callback_signature($data)) {
        debugging('Invalid signature in return page', DEBUG_DEVELOPER);
    }
}

// Setup page.
$PAGE->set_url('/payment/gateway/payu/return.php', ['paymentid' => $payment->id]);
$PAGE->set_context(context_system::instance());

$payable = helper::get_payable($payment->component, $payment->paymentarea, $payment->itemid);

// Determine page title and message based on state.
$pagetitle = '';
$alerttype = 'info';
$alertmessage = '';
$showdetails = true;

switch ($newstate) {
    case 'APPROVED':
        $pagetitle = get_string('paymentsuccess', 'paygw_payu');
        $alerttype = 'success';
        $alertmessage = get_string('paymentsuccess', 'paygw_payu');
        
        // Deliver order if not already delivered.
        if ($payment->status != 'complete') {
            helper::deliver_order(
                $payment->component,
                $payment->paymentarea,
                $payment->itemid,
                $payment->id,
                $payment->userid
            );
        }
        break;
        
    case 'PENDING':
        $pagetitle = get_string('paymentpending', 'paygw_payu');
        $alerttype = 'warning';
        $alertmessage = get_string('paymentpending', 'paygw_payu');
        break;
        
    case 'DECLINED':
    case 'ERROR':
    case 'EXPIRED':
        $pagetitle = get_string('paymenterror', 'paygw_payu');
        $alerttype = 'danger';
        $alertmessage = paygw_payu_get_response_message($responsecode);
        break;
        
    default:
        $pagetitle = get_string('unknownstate', 'paygw_payu', $newstate);
        $alerttype = 'warning';
        $alertmessage = get_string('unknownstate', 'paygw_payu', $newstate);
}

$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

// Prepare template context.
$templatecontext = [
    'alerttype' => $alerttype,
    'alertmessage' => $alertmessage,
    'showdetails' => $showdetails,
    'transactionid' => $transactionid,
    'orderid' => $referencepol,
    'amount' => $payment->amount,
    'currency' => $payment->currency,
    'paymentmethod' => $paymentmethodname,
    'responsecode' => $responsecode,
];

// Add continue URL for successful payments.
if ($newstate === 'APPROVED') {
    $successurl = helper::get_success_url($payment->component, $payment->paymentarea, $payment->itemid);
    $templatecontext['continueurl'] = $successurl->out(false);
}

// Add retry URL for failed payments.
if (in_array($newstate, ['DECLINED', 'ERROR', 'EXPIRED'])) {
    $retryurl = new moodle_url('/payment/gateway/payu/method.php', [
        'component' => $payment->component,
        'paymentarea' => $payment->paymentarea,
        'itemid' => $payment->itemid,
        'description' => $payable->get_description(),
        'sesskey' => sesskey(),
    ]);
    $templatecontext['retryurl'] = $retryurl->out(false);
    $templatecontext['retrybutton'] = get_string('tryagain', 'core');
}

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('paygw_payu/return', $templatecontext);
echo $OUTPUT->footer();

// Send notification if enabled.
if (!empty($config->enablenotifications)) {
    notifications::process_transaction_notification($transaction, $payment);
}