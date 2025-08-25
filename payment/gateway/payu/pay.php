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
 * Internal checkout flow for PayU payments.
 *
 * @package    paygw_payu
 * @copyright  2024 Example
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\api;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);
$description = json_decode('"' . $description . '"');

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu');
$amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);

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

if (optional_param('paymentmethod', null, PARAM_ALPHA)) {
    $method = required_param('paymentmethod', PARAM_ALPHA);
    $formdata = new stdClass();
    $formdata->paymentmethod = $method;
    $formdata->cardholder = required_param('cardholder', PARAM_TEXT);
    if ($method === 'creditcard') {
        $formdata->ccnumber = required_param('ccnumber', PARAM_TEXT);
        $formdata->ccexpmonth = required_param('ccexpmonth', PARAM_TEXT);
        $formdata->ccexpyear = required_param('ccexpyear', PARAM_TEXT);
        $formdata->cvv = required_param('cvv', PARAM_TEXT);
        $formdata->cardnetwork = required_param('cardnetwork', PARAM_ALPHANUMEXT);
        $formdata->phone = required_param('phone', PARAM_TEXT);
        $formdata->documentnumber = required_param('documentnumber', PARAM_TEXT);
    } else if ($method === 'pse') {
        $formdata->psebank = required_param('psebank', PARAM_ALPHANUMEXT);
        $formdata->usertype = required_param('usertype', PARAM_ALPHA);
        $formdata->documenttype = required_param('documenttype', PARAM_ALPHANUMEXT);
        $formdata->documentnumber = required_param('documentnumber', PARAM_TEXT);
        $formdata->phone = required_param('phone', PARAM_TEXT);
    } else if ($method === 'nequi' || $method === 'bancolombia') {
        $formdata->phone = required_param('phone', PARAM_TEXT);
        $formdata->documentnumber = required_param('documentnumber', PARAM_TEXT);
    } else if ($method === 'googlepay') {
        $formdata->gp_network = required_param('gp_network', PARAM_ALPHANUMEXT);
        $formdata->gp_token = required_param('gp_token', PARAM_RAW);
        $formdata->phone = required_param('phone', PARAM_TEXT);
        $formdata->documentnumber = required_param('documentnumber', PARAM_TEXT);
    } else if ($method === 'cash') {
        $formdata->cashmethod = required_param('cashmethod', PARAM_ALPHANUMEXT);
        $formdata->phone = required_param('phone', PARAM_TEXT);
        $formdata->documentnumber = required_param('documentnumber', PARAM_TEXT);
    }
    $formdata->description = $description;
    $formdata->email = $USER->email;
    $response = api::submit_transaction($config, $paymentid, $amount, $currency, $formdata);
    if ($response && strtoupper($response->state) === 'APPROVED') {
        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $USER->id);
        redirect(helper::get_success_url($component, $paymentarea, $itemid));
    } else if ($response && !empty($response->extraParameters->BANK_URL)) {
        redirect($response->extraParameters->BANK_URL);
    } else if ($response && !empty($response->extraParameters->URL_PAYMENT_RECEIPT_HTML)) {
        redirect($response->extraParameters->URL_PAYMENT_RECEIPT_HTML);
    } else if ($response) {
        throw new moodle_exception('paymentpending', 'paygw_payu');
    } else {
        throw new moodle_exception('paymenterror', 'paygw_payu');
    }
}

$PAGE->set_url(new moodle_url('/payment/gateway/payu/pay.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('gatewayname', 'paygw_payu'));
$PAGE->set_heading(get_string('gatewayname', 'paygw_payu'));

$banks = api::get_pse_banks($config);
$banklist = [];
foreach ($banks as $code => $name) {
    $banklist[] = ['code' => $code, 'name' => $name];
}

$templatecontext = [
    'url' => (new moodle_url('/payment/gateway/payu/pay.php'))->__toString(),
    'sesskey' => sesskey(),
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'description' => $description,
    'banks' => $banklist,
];

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('gatewayname', 'paygw_payu'), 3);
echo $OUTPUT->render_from_template('paygw_payu/checkout', $templatecontext);
echo $OUTPUT->footer();

