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

// Get payment parameters.
$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$method      = optional_param('paymentmethod', '', PARAM_ALPHA);

// Clean description.
$description = clean_param($description, PARAM_TEXT);

// Get gateway configuration.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Check if gateway is configured.
if (empty($config->merchantid) || empty($config->payuaccountid) ||
    empty($config->apilogin) || empty($config->apikey)) {
    throw new moodle_exception('gatewaynotconfigured', 'paygw_payu');
}

// Get payment details.
$payable = helper::get_payable($component, $paymentarea, $itemid);
$currency = $payable->get_currency();

// Check supported currency.
if (!in_array($currency, ['COP', 'USD'])) {
    throw new moodle_exception('currencynotsupported', 'paygw_payu');
}

// Calculate amount with surcharge.
$surcharge = helper::get_gateway_surcharge('payu');
$amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

// If no payment method selected, show the form.
if (empty($method)) {
    $PAGE->set_url('/payment/gateway/payu/pay.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('gatewayname', 'paygw_payu'));
    
    // Initialize API client.
    $api = new api($config);
    
    // Get PSE banks if PSE is enabled.
    $banks = [];
    if (!empty($config->enabledmethods) && in_array('pse', $config->enabledmethods)) {
        try {
            $banks = $api->get_pse_banks();
        } catch (Exception $e) {
            debugging('Error loading PSE banks: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }
    
    // Prepare template context.
    $context = [
        'component' => $component,
        'paymentarea' => $paymentarea,
        'itemid' => $itemid,
        'description' => $description,
        'amount' => $amount,
        'currency' => $currency,
        'localizedcost' => helper::get_cost_as_string($amount, $currency),
        'banks' => array_map(function($code, $name) {
            return ['code' => $code, 'name' => $name];
        }, array_keys($banks), $banks),
        'months' => [],
        'years' => [],
        'installments' => [],
    ];
    
    // Generate months.
    for ($i = 1; $i <= 12; $i++) {
        $context['months'][] = [
            'value' => str_pad($i, 2, '0', STR_PAD_LEFT),
            'label' => str_pad($i, 2, '0', STR_PAD_LEFT),
        ];
    }
    
    // Generate years.
    $currentyear = date('Y');
    for ($i = 0; $i < 20; $i++) {
        $year = $currentyear + $i;
        $context['years'][] = [
            'value' => $year,
            'label' => $year,
        ];
    }
    
    // Generate installments.
    for ($i = 2; $i <= 36; $i++) {
        if ($i <= 12 || $i == 18 || $i == 24 || $i == 36) {
            $context['installments'][] = ['value' => $i];
        }
    }
    
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('gatewayname', 'paygw_payu'));
    echo $OUTPUT->render_from_template('paygw_payu/checkout', $context);
    echo $OUTPUT->footer();
    exit;
}

// Process payment submission.
$formdata = new stdClass();
$formdata->paymentmethod = $method;
$formdata->cardholder = required_param('cardholder', PARAM_TEXT);
$formdata->email = optional_param('email', $USER->email, PARAM_EMAIL);
$formdata->phone = optional_param('phone', '', PARAM_TEXT);
$formdata->documentnumber = optional_param('documentnumber', '', PARAM_TEXT);
$formdata->description = $description;
$formdata->paymentid = $itemid; // Will be replaced with actual payment ID.

// Get method-specific data.
switch ($method) {
    case 'creditcard':
        $formdata->ccnumber = required_param('cardnumber', PARAM_TEXT);
        $formdata->ccexpmonth = required_param('expmonth', PARAM_TEXT);
        $formdata->ccexpyear = required_param('expyear', PARAM_TEXT);
        $formdata->cvv = required_param('cvv', PARAM_TEXT);
        $formdata->cardnetwork = required_param('cardnetwork', PARAM_ALPHANUMEXT);
        $formdata->installments = optional_param('installments', 1, PARAM_INT);
        break;
        
    case 'pse':
        $formdata->psebank = required_param('psebank', PARAM_ALPHANUMEXT);
        $formdata->usertype = required_param('usertype', PARAM_ALPHA);
        $formdata->documenttype = required_param('documenttype', PARAM_ALPHANUMEXT);
        break;
        
    case 'nequi':
    case 'bancolombia':
        // Phone is required for these methods.
        $formdata->phone = required_param('phone', PARAM_TEXT);
        break;
        
    case 'googlepay':
        $formdata->gp_network = required_param('gp_network', PARAM_ALPHANUMEXT);
        $formdata->gp_token = required_param('gp_token', PARAM_RAW);
        break;
        
    case 'cash':
        $formdata->cashmethod = required_param('cashmethod', PARAM_ALPHANUMEXT);
        break;
        
    default:
        throw new moodle_exception('invalidpaymentmethod', 'paygw_payu');
}

// Save payment record.
$paymentid = helper::save_payment(
    $payable->get_account_id(),
    $component,
    $paymentarea,
    $itemid,
    $USER->id,
    $amount,
    $currency,
    'payu'
);

// Update form data with payment ID.
$formdata->paymentid = $paymentid;

// Initialize API client and submit transaction.
$api = new api($config);

try {
    $response = $api->submit_transaction($paymentid, $amount, $currency, $formdata);
    
    // Handle response based on state.
    if ($response->state === 'APPROVED') {
        // Payment approved - deliver order.
        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $USER->id);
        
        // Send notification if enabled.
        if (!empty($config->enablenotifications)) {
            notifications::send_payment_receipt(
                $USER->id,
                $amount,
                $currency,
                $paymentid,
                'APPROVED',
                [
                    'transactionid' => $response->transactionId,
                    'paymentmethod' => $method,
                    'contexturl' => helper::get_success_url($component, $paymentarea, $itemid)->out(false),
                ]
            );
        }
        
        // Redirect to success page.
        redirect(helper::get_success_url($component, $paymentarea, $itemid));
        
    } else if ($response->state === 'PENDING') {
        // Payment pending - handle based on method.
        if (!empty($response->extraParameters->BANK_URL)) {
            // PSE or Bancolombia - redirect to bank.
            redirect($response->extraParameters->BANK_URL);
            
        } else if (!empty($response->extraParameters->URL_PAYMENT_RECEIPT_HTML)) {
            // Cash payment - show receipt.
            $receipturl = $response->extraParameters->URL_PAYMENT_RECEIPT_HTML;
            
            // Send notification if enabled.
            if (!empty($config->enablenotifications)) {
                $expirationdate = $response->extraParameters->EXPIRATION_DATE ?? '';
                $reference = $response->extraParameters->REFERENCE ?? '';
                
                notifications::send_cash_payment_reminder(
                    $USER->id,
                    $amount,
                    $currency,
                    $reference,
                    $expirationdate,
                    $receipturl
                );
            }
            
            // Redirect to receipt.
            redirect($receipturl);
            
        } else {
            // Nequi or other pending payment.
            if (!empty($config->enablenotifications)) {
                notifications::send_payment_receipt(
                    $USER->id,
                    $amount,
                    $currency,
                    $paymentid,
                    'PENDING',
                    [
                        'transactionid' => $response->transactionId,
                        'paymentmethod' => $method,
                    ]
                );
            }
            
            // Show pending message.
            $PAGE->set_url('/payment/gateway/payu/pay.php');
            $PAGE->set_context(context_system::instance());
            $PAGE->set_title(get_string('paymentpending', 'paygw_payu'));
            
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('paymentpending', 'paygw_payu'), 'info');
            echo $OUTPUT->continue_button(helper::get_success_url($component, $paymentarea, $itemid));
            echo $OUTPUT->footer();
        }
        
    } else {
        // Payment failed or rejected.
        $errormessage = $response->responseMessage ?? get_string('paymenterror', 'paygw_payu');
        
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
                ]
            );
        }
        
        throw new moodle_exception('paymenterror', 'paygw_payu', '', $errormessage);
    }
    
} catch (Exception $e) {
    // Log error.
    debugging('PayU payment error: ' . $e->getMessage(), DEBUG_DEVELOPER);
    
    // Show error page.
    $PAGE->set_url('/payment/gateway/payu/pay.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('paymenterror', 'paygw_payu'));
    
    echo $OUTPUT->header();
    echo $OUTPUT->notification($e->getMessage(), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/payment/gateway/payu/pay.php', [
        'component' => $component,
        'paymentarea' => $paymentarea,
        'itemid' => $itemid,
        'description' => $description,
    ]));
    echo $OUTPUT->footer();
}