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
 * Redirects user to the PayU payment page
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\gateway;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $USER, $DB;
require_once($CFG->libdir . '/filelib.php');

require_login();
require_sesskey();

$userid = $USER->id;

// Get payment parameters
$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

// Optional parameters
$password    = optional_param('password', null, PARAM_TEXT);
$skipmode    = optional_param('skipmode', null, PARAM_INT);
$costself    = optional_param('costself', null, PARAM_TEXT);

$description = json_decode('"' . $description . '"');

// Get gateway configuration
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
$payable = helper::get_payable($component, $paymentarea, $itemid);

// Get currency and payment amount
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu');
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// Check self cost if not fixcost
if (!empty($costself) && empty($config->fixcost)) {
    $cost = $costself;
}

// Check maxcost
if (!empty($config->maxcost) && $cost > $config->maxcost) {
    $cost = $config->maxcost;
}

// Check uninterrupted mode for enrol_nexuspay
$uninterrupted_cost = $cost;
if ($component == "enrol_nexuspay" && !empty($config->fixcost)) {
    $cs = $DB->get_record('enrol', ['id' => $itemid, 'enrol' => 'nexuspay']);
    if (!empty($cs->customint5)) {
        $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $cs->id]);
        if ($data) {
            // Calculate uninterrupted cost based on time
            $ctime = time();
            $timeend = $data->timeend ?? $ctime;
            if ($timeend < $ctime && $cs->enrolperiod > 0) {
                $periods_missed = ceil(($ctime - $timeend) / $cs->enrolperiod);
                $uninterrupted_cost = $cost * (1 + $periods_missed);
            }
        }
    }
    $cost = $uninterrupted_cost;
}

// Get course and group information
$courseid = 0;
$groupnames = '';
if ($paymentarea == 'enrolment') {
    $cs = $DB->get_record('enrol', ['id' => $itemid]);
    $courseid = $cs->courseid;
    
    // Check for group enrollment
    if ($cs->customint1) {
        $groupid = $DB->get_record('enrol_nexuspay_groups', [
            'userid' => $USER->id,
            'courseid' => $courseid,
            'instanceid' => $itemid
        ]);
        if ($groupid && $groupid->groupid) {
            $group = $DB->get_record('groups', ['id' => $groupid->groupid]);
            if ($group) {
                $groupnames = $group->name;
            }
        }
    }
}

// Create PayU transaction record
$payu_record = new stdClass();
$payu_record->courseid = $courseid;
$payu_record->userid = $userid;
$payu_record->groupnames = $groupnames;
$payu_record->country = $config->country ?? 'CO';
$payu_record->currency = $currency;
$payu_record->amount = $cost;
$payu_record->timecreated = time();

$transactionid = $DB->insert_record('paygw_payu', $payu_record);

if (!$transactionid) {
    throw new moodle_exception('error_txdatabase', 'paygw_payu');
}

$payu_record->id = $transactionid;

// Build redirect URL
$url = helper::get_success_url($component, $paymentarea, $itemid);

// Set page context
$PAGE->set_url($SCRIPT);
$PAGE->set_context(context_system::instance());

// Check for password or skip mode
if (!empty($password) || $skipmode) {
    $success = false;
    
    if ($config->skipmode && $skipmode) {
        $success = true;
    } else if (!empty($config->passwordmode) && !empty($config->password)) {
        // Check payment password
        if ($password === $config->password) {
            $success = true;
        }
    }

    if ($success) {
        // Create fake payment
        $paymentid = helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            $userid,
            0,
            $payable->get_currency(),
            'payu'
        );
        
        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

        // Update transaction record
        $payu_record->success = 2;
        $payu_record->paymentid = $paymentid;
        $DB->update_record('paygw_payu', $payu_record);

        redirect($url, get_string('password_success', 'paygw_payu'), 0, 'success');
    } else {
        redirect($url, get_string('password_error', 'paygw_payu'), 0, 'error');
    }
    die; // Never reached
}

// Save payment record
$paymentid = helper::save_payment(
    $payable->get_account_id(),
    $component,
    $paymentarea,
    $itemid,
    $userid,
    $cost,
    $payable->get_currency(),
    'payu'
);

// Update PayU record with payment ID
$payu_record->paymentid = $paymentid;
$DB->update_record('paygw_payu', $payu_record);

// Get credentials based on environment
if ($config->environment === 'sandbox') {
    $credentials = gateway::get_test_credentials($config->country);
    $merchantId = $credentials['merchantid'];
    $accountId = $credentials['accountid'];
    $apiKey = $credentials['apikey'];
    $test = 1;
    $gateway_url = "https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/";
} else {
    $merchantId = $config->merchantid;
    $accountId = $config->accountid;
    $apiKey = $config->apikey;
    $test = 0;
    $gateway_url = "https://checkout.payulatam.com/ppp-web-gateway-payu/";
}

// Generate reference code
$referenceCode = "MOODLE-" . $paymentid . "-" . time();

// Generate signature
$signature_string = $apiKey . "~" . $merchantId . "~" . $referenceCode . "~" . $cost . "~" . $currency;
$signature = md5($signature_string);

// Prepare buyer information
$buyer_name = $USER->firstname . ' ' . $USER->lastname;
$buyer_email = $USER->email;
$buyer_phone = $USER->phone1 ?? '';

// Build PayU form parameters
$payu_params = [
    'merchantId' => $merchantId,
    'accountId' => $accountId,
    'description' => substr($description, 0, 255),
    'referenceCode' => $referenceCode,
    'amount' => $cost,
    'currency' => $currency,
    'signature' => $signature,
    'test' => $test,
    'buyerEmail' => $buyer_email,
    'buyerFullName' => $buyer_name,
    'telephone' => $buyer_phone,
    'confirmationUrl' => $CFG->wwwroot . '/payment/gateway/payu/callback.php',
    'responseUrl' => $CFG->wwwroot . '/payment/gateway/payu/return.php',
    'lng' => $config->language ?? 'es',
    'extra1' => $paymentid,
    'extra2' => $component,
    'extra3' => $paymentarea
];

// Update transaction with reference code
$payu_record->referencecode = $referenceCode;
$DB->update_record('paygw_payu', $payu_record);

// Auto-fill test data if enabled
if ($config->environment === 'sandbox' && !empty($config->autofilltest)) {
    // Add test data parameters
    $payu_params['payerDocument'] = '1234567890';
    $payu_params['mobilePhone'] = '3001234567';
    $payu_params['billingAddress'] = 'Calle 100 # 1-1';
    $payu_params['billingCity'] = 'Bogota';
    $payu_params['billingCountry'] = $config->country;
}

// Create HTML form for redirect
$html = '<!DOCTYPE html>
<html>
<head>
    <title>' . get_string('redirecting', 'paygw_payu') . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .loader {
            text-align: center;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="loader">
        <div class="spinner"></div>
        <p>' . get_string('redirecting_message', 'paygw_payu') . '</p>
    </div>
    <form id="payuform" action="' . $gateway_url . '" method="POST" style="display:none;">';

foreach ($payu_params as $key => $value) {
    $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
}

$html .= '</form>
    <script>
        document.getElementById("payuform").submit();
    </script>
</body>
</html>';

echo $html;