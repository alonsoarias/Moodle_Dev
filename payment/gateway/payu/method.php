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
 * Payment method selector for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\api;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $USER, $DB, $PAGE, $OUTPUT;

require_login();
require_sesskey();

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

// Clean description.
$description = clean_param($description, PARAM_TEXT);

// Get gateway configuration.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

// Validate configuration.
if (empty($config->merchantid) || empty($config->payuaccountid) || 
    empty($config->apilogin) || empty($config->apikey)) {
    throw new moodle_exception('gatewaynotconfigured', 'paygw_payu');
}

// Get payment details.
$payable = helper::get_payable($component, $paymentarea, $itemid);
$currency = $payable->get_currency();

// Validate supported currency.
$supportedcurrencies = ['COP', 'USD'];
if (!in_array($currency, $supportedcurrencies)) {
    throw new moodle_exception('currencynotsupported', 'paygw_payu', '', $currency);
}

// Calculate amount with surcharge.
$surcharge = helper::get_gateway_surcharge('payu');
$amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

// Setup page.
$PAGE->set_url('/payment/gateway/payu/method.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('gatewayname', 'paygw_payu'));
$PAGE->set_heading(format_string($payable->get_description()));
$PAGE->set_secondary_navigation(false);

// Initialize PayU API.
$api = new api($config);

// Test connectivity with PayU.
try {
    if (!$api->ping()) {
        throw new moodle_exception('errorconnection', 'paygw_payu');
    }
} catch (Exception $e) {
    debugging('PayU connectivity test failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
}

// Get enabled payment methods from config.
$enabledmethods = !empty($config->enabledmethods) ? $config->enabledmethods : ['creditcard'];
if (is_string($enabledmethods)) {
    $enabledmethods = explode(',', $enabledmethods);
}

// Get PSE banks if PSE is enabled.
$banks = [];
if (in_array('pse', $enabledmethods)) {
    try {
        $banks = $api->get_pse_banks();
    } catch (Exception $e) {
        debugging('Error loading PSE banks: ' . $e->getMessage(), DEBUG_DEVELOPER);
        // Remove PSE from enabled methods if banks cannot be loaded.
        $enabledmethods = array_diff($enabledmethods, ['pse']);
    }
}

// Create payment record.
$paymentid = helper::save_payment(
    $component,
    $paymentarea,
    $itemid,
    $USER->id,
    $amount,
    $currency,
    'payu'
);

// Create PayU transaction record.
$transaction = new stdClass();
$transaction->paymentid = $paymentid;
$transaction->state = 'PENDING';
$transaction->timecreated = time();
$transaction->timemodified = time();
$transactionid = $DB->insert_record('paygw_payu', $transaction);

// Prepare template context.
$templatecontext = [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'paymentid' => $paymentid,
    'description' => $description,
    'amount' => $amount,
    'amountformatted' => \core_payment\helper::get_cost_as_string($amount, $currency),
    'currency' => $currency,
    'sesskey' => sesskey(),
    'banks' => [],
    'enabledmethods' => [],
    'formaction' => new moodle_url('/payment/gateway/payu/pay.php'),
    'returnurl' => $payable->get_success_url()->out(false),
    'cancelurl' => new moodle_url('/'),
];

// Add banks for PSE.
if (!empty($banks)) {
    foreach ($banks as $code => $name) {
        $templatecontext['banks'][] = [
            'code' => $code,
            'name' => $name,
        ];
    }
}

// Build enabled methods array for template.
$methodnames = [
    'creditcard' => get_string('creditcard', 'paygw_payu'),
    'pse' => get_string('pse', 'paygw_payu'),
    'nequi' => get_string('nequi', 'paygw_payu'),
    'bancolombia' => get_string('bancolombia', 'paygw_payu'),
    'googlepay' => get_string('googlepay', 'paygw_payu'),
    'cash' => get_string('cash', 'paygw_payu'),
];

foreach ($enabledmethods as $method) {
    if (isset($methodnames[$method])) {
        $templatecontext['enabledmethods'][] = [
            'value' => $method,
            'name' => $methodnames[$method],
            'selected' => (count($enabledmethods) == 1),
        ];
    }
}

// Add years for credit card expiry.
$currentyear = (int)date('Y');
$years = [];
for ($i = $currentyear; $i <= $currentyear + 20; $i++) {
    $years[] = ['value' => $i, 'label' => $i];
}
$templatecontext['years'] = $years;

// Add months for credit card expiry.
$months = [];
for ($i = 1; $i <= 12; $i++) {
    $months[] = ['value' => sprintf('%02d', $i), 'label' => sprintf('%02d', $i)];
}
$templatecontext['months'] = $months;

// Add installments options for Colombia.
$installments = [];
for ($i = 2; $i <= 36; $i++) {
    $installments[] = ['value' => $i];
}
$templatecontext['installments'] = $installments;

// Add user information.
$templatecontext['useremail'] = $USER->email;
$templatecontext['userfullname'] = fullname($USER);

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('paygw_payu/checkout_modal', $templatecontext);
echo $OUTPUT->footer();