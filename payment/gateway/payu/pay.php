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
 * Process payment for PayU gateway.
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
require_sesskey();

global $DB, $USER, $PAGE, $OUTPUT;

// Get payment parameters.
$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$paymentid   = required_param('paymentid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$method      = required_param('paymentmethod', PARAM_ALPHA);

// Clean description.
$description = clean_param($description, PARAM_TEXT);

// Verify payment exists.
if (!$payment = $DB->get_record('payments', ['id' => $paymentid])) {
    throw new moodle_exception('invalidpayment', 'paygw_payu');
}

// Verify payment is for correct user.
if ($payment->userid != $USER->id) {
    throw new moodle_exception('invaliduser', 'paygw_payu');
}

// Get gateway configuration.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Check if gateway is configured.
if (empty($config->merchantid) || empty($config->payuaccountid) ||
    empty($config->apilogin) || empty($config->apikey)) {
    throw new moodle_exception('gatewaynotconfigured', 'paygw_payu');
}

// Get payment details.
$payable = helper::get_payable($component, $paymentarea, $itemid);
$currency = $payment->currency;
$amount = $payment->amount;

// Check supported currency.
if (!in_array($currency, ['COP', 'USD'])) {
    throw new moodle_exception('currencynotsupported', 'paygw_payu');
}

// Initialize API client.
$api = new api($config);

// Prepare payment data.
$paymentdata = new stdClass();
$paymentdata->paymentmethod = $method;
$paymentdata->description = $description;

// Collect form data based on payment method.
switch ($method) {
    case 'creditcard':
        $paymentdata->cardholder = required_param('cardholder', PARAM_TEXT);
        $paymentdata->cardnumber = required_param('cardnumber', PARAM_RAW);
        $paymentdata->expmonth = required_param('expmonth', PARAM_INT);
        $paymentdata->expyear = required_param('expyear', PARAM_INT);
        $paymentdata->cvv = required_param('cvv', PARAM_INT);
        $paymentdata->cardnetwork = required_param('cardnetwork', PARAM_ALPHA);
        $paymentdata->installments = optional_param('installments', 1, PARAM_INT);
        $paymentdata->phone = optional_param('phone', '', PARAM_RAW);
        $paymentdata->documentnumber = optional_param('documentnumber', '', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        break;
        
    case 'pse':
        $paymentdata->psebank = required_param('psebank', PARAM_ALPHANUMEXT);
        $paymentdata->pseusertype = required_param('pseusertype', PARAM_ALPHA);
        $paymentdata->documentnumber = required_param('documentnumber', PARAM_RAW);
        $paymentdata->phone = required_param('phone', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        $paymentdata->cardholder = optional_param('cardholder', fullname($USER), PARAM_TEXT);
        break;
        
    case 'nequi':
        $paymentdata->phone = required_param('phone', PARAM_RAW);
        $paymentdata->documentnumber = required_param('documentnumber', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        $paymentdata->cardholder = optional_param('cardholder', fullname($USER), PARAM_TEXT);
        
        // Validate Nequi phone.
        if (!preg_match('/^3[0-9]{9}$/', $paymentdata->phone)) {
            throw new moodle_exception('invalidphone', 'paygw_payu');
        }
        break;
        
    case 'bancolombia':
        $paymentdata->phone = required_param('phone', PARAM_RAW);
        $paymentdata->documentnumber = required_param('documentnumber', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        $paymentdata->cardholder = optional_param('cardholder', fullname($USER), PARAM_TEXT);
        break;
        
    case 'googlepay':
        $paymentdata->gp_token = required_param('gp_token', PARAM_RAW);
        $paymentdata->gp_network = required_param('gp_network', PARAM_ALPHA);
        $paymentdata->phone = optional_param('phone', '', PARAM_RAW);
        $paymentdata->documentnumber = optional_param('documentnumber', '', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        $paymentdata->cardholder = optional_param('cardholder', fullname($USER), PARAM_TEXT);
        break;
        
    case 'cash':
        $paymentdata->cashmethod = required_param('cashmethod', PARAM_ALPHA);
        $paymentdata->documentnumber = required_param('documentnumber', PARAM_RAW);
        $paymentdata->phone = optional_param('phone', '', PARAM_RAW);
        $paymentdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
        $paymentdata->cardholder = optional_param('cardholder', fullname($USER), PARAM_TEXT);
        break;
        
    default:
        throw new moodle_exception('invalidpaymentmethod', 'paygw_payu');
}

// Add address information if provided.
$paymentdata->address1 = optional_param('address1', '', PARAM_TEXT);
$paymentdata->address2 = optional_param('address2', '', PARAM_TEXT);
$paymentdata->city = optional_param('city', 'Bogotá', PARAM_TEXT);
$paymentdata->state = optional_param('state', 'Bogotá D.C.', PARAM_TEXT);
$paymentdata->postalcode = optional_param('postalcode', '000000', PARAM_TEXT);

// Add IVA if applicable.
$paymentdata->includeiva = optional_param('includeiva', 0, PARAM_BOOL);

// Update transaction record.
$transaction = $DB->get_record('paygw_payu', ['paymentid' => $paymentid]);
if (!$transaction) {
    $transaction = new stdClass();
    $transaction->paymentid = $paymentid;
    $transaction->timecreated = time();
}
$transaction->payment_method = $method;
$transaction->amount = $amount;
$transaction->currency = $currency;
$transaction->state = 'PROCESSING';
$transaction->timemodified = time();

if (empty($transaction->id)) {
    $transaction->id = $DB->insert_record('paygw_payu', $transaction);
} else {
    $DB->update_record('paygw_payu', $transaction);
}

try {
    // Process payment with PayU.
    $response = $api->process_payment($paymentid, $amount, $currency, $paymentdata);
    
    // Update transaction with response.
    $transaction->payu_order_id = $response->orderId ?? null;
    $transaction->payu_transaction_id = $response->transactionId ?? null;
    $transaction->state = $response->state ?? 'UNKNOWN';
    $transaction->response_code = $response->responseCode ?? null;
    $transaction->extra_parameters = json_encode($response->extraParameters ?? []);
    $transaction->timemodified = time();
    $DB->update_record('paygw_payu', $transaction);
    
    // Handle response based on state.
    switch ($response->state) {
        case 'APPROVED':
            // Payment approved immediately.
            helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $USER->id);
            
            // Send success notification.
            if (!empty($config->enablenotifications)) {
                notifications::send_payment_receipt(
                    $USER->id,
                    $amount,
                    $currency,
                    $paymentid,
                    'APPROVED',
                    [
                        'transactionid' => $response->transactionId ?? '',
                        'paymentmethod' => $method,
                    ]
                );
            }
            
            // Redirect to success page.
            $successurl = $payable->get_success_url();
            redirect($successurl, get_string('paymentsuccess', 'paygw_payu'), 0);
            break;
            
        case 'PENDING':
            // Payment pending (PSE, cash, etc.).
            if (!empty($config->enablenotifications)) {
                notifications::send_payment_receipt(
                    $USER->id,
                    $amount,
                    $currency,
                    $paymentid,
                    'PENDING',
                    [
                        'transactionid' => $response->transactionId ?? '',
                        'paymentmethod' => $method,
                    ]
                );
            }
            
            // Show pending message with instructions.
            $PAGE->set_url('/payment/gateway/payu/pay.php');
            $PAGE->set_context(context_system::instance());
            $PAGE->set_title(get_string('paymentpending', 'paygw_payu'));
            
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('paymentpending', 'paygw_payu'), 'info');
            
            // Show payment receipt for cash payments.
            if ($method === 'cash' && !empty($response->extraParameters)) {
                $receipturl = $response->extraParameters->URL_PAYMENT_RECEIPT_HTML ?? '';
                if ($receipturl) {
                    echo html_writer::tag('p', get_string('instruction_cash', 'paygw_payu'));
                    echo html_writer::link($receipturl, get_string('viewreceipt', 'paygw_payu'), 
                        ['class' => 'btn btn-primary', 'target' => '_blank']);
                }
            }
            
            // Show PSE redirect URL.
            if ($method === 'pse' && !empty($response->extraParameters)) {
                $pseurl = $response->extraParameters->BANK_URL ?? '';
                if ($pseurl) {
                    echo html_writer::tag('p', get_string('instruction_pse', 'paygw_payu'));
                    redirect($pseurl);
                }
            }
            
            echo $OUTPUT->footer();
            break;
            
        case 'DECLINED':
        case 'ERROR':
        case 'EXPIRED':
            // Payment failed.
            if (!empty($config->enablenotifications)) {
                notifications::send_payment_receipt(
                    $USER->id,
                    $amount,
                    $currency,
                    $paymentid,
                    $response->state,
                    [
                        'transactionid' => $response->transactionId ?? '',
                        'paymentmethod' => $method,
                        'errorcode' => $response->responseCode ?? '',
                    ]
                );
            }
            
            // Show error and retry option.
            $retryurl = new moodle_url('/payment/gateway/payu/method.php', [
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid,
                'description' => $description,
                'sesskey' => sesskey(),
            ]);
            
            throw new moodle_exception('paymenterror', 'paygw_payu', $retryurl, 
                $response->responseMessage ?? 'Payment failed');
            break;
            
        default:
            // Unknown state.
            throw new moodle_exception('unknownstate', 'paygw_payu', '', $response->state);
    }
    
} catch (Exception $e) {
    // Update transaction with error.
    $transaction->state = 'ERROR';
    $transaction->response_code = 'EXCEPTION';
    $transaction->timemodified = time();
    $DB->update_record('paygw_payu', $transaction);
    
    // Log error.
    debugging('PayU payment error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    
    // Show error to user.
    $retryurl = new moodle_url('/payment/gateway/payu/method.php', [
        'component' => $component,
        'paymentarea' => $paymentarea,
        'itemid' => $itemid,
        'description' => $description,
        'sesskey' => sesskey(),
    ]);
    
    throw new moodle_exception('paymenterror', 'paygw_payu', $retryurl, $e->getMessage());
}