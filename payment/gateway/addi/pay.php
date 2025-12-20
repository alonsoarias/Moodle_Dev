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
 * Initiates the Addi payment process.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_addi\gateway;
use paygw_addi\addi_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'addi');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('addi');

$amount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);
$currency = $payable->get_currency();

// Create helper instance.
$addihelper = gateway::create_helper_from_config($config);

// Validate amount is within limits.
if (!$addihelper->is_amount_valid($amount, $config)) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/payment/gateway/addi/pay.php');
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('error'));

    echo $OUTPUT->header();

    if ($amount < ($config->minamount ?? 50000)) {
        echo $OUTPUT->notification(get_string('amountbelowminimum', 'paygw_addi', (object)[
            'amount' => number_format($amount, 0, ',', '.'),
            'currency' => $currency,
            'min' => number_format($config->minamount ?? 50000, 0, ',', '.'),
        ]), 'error');
    } else {
        echo $OUTPUT->notification(get_string('amountabovemaximum', 'paygw_addi', (object)[
            'amount' => number_format($amount, 0, ',', '.'),
            'currency' => $currency,
            'max' => number_format($config->maxamount ?? 5000000, 0, ',', '.'),
        ]), 'error');
    }

    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

// Generate unique reference.
$reference = $addihelper->generate_reference($USER->id, $component, $paymentarea, $itemid);

// URLs for Addi.
$successurl = new moodle_url('/payment/gateway/addi/response.php', [
    'reference' => $reference,
    'status' => 'success',
]);
$failureurl = new moodle_url('/payment/gateway/addi/response.php', [
    'reference' => $reference,
    'status' => 'failure',
]);
$pendingurl = new moodle_url('/payment/gateway/addi/response.php', [
    'reference' => $reference,
    'status' => 'pending',
]);

// Save initial transaction.
$transactionid = $addihelper->save_transaction([
    'userid' => $USER->id,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'reference' => $reference,
    'amount' => $amount,
    'currency' => $currency,
    'status' => 'INITIATED',
]);

// Create Addi application.
$applicationdata = $addihelper->create_application([
    'order_id' => $reference,
    'amount' => $amount,
    'currency' => $currency,
    'description' => $description,
    'item_id' => $itemid,
    'email' => $USER->email,
    'first_name' => $USER->firstname,
    'last_name' => $USER->lastname,
    'phone' => $USER->phone1 ?: '',
    'address' => $USER->address ?: '',
    'city' => $USER->city ?: '',
    'success_url' => $successurl->out(false),
    'failure_url' => $failureurl->out(false),
    'pending_url' => $pendingurl->out(false),
]);

if (!$applicationdata || !isset($applicationdata['applicationId'])) {
    // Failed to create application.
    $addihelper->update_transaction($transactionid, [
        'status' => 'FAILED',
        'response' => ['error' => 'Failed to create Addi application'],
    ]);

    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/payment/gateway/addi/pay.php');
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('error'));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:applicationfailed', 'paygw_addi'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

// Update transaction with application ID.
$addihelper->update_transaction($transactionid, [
    'applicationid' => $applicationdata['applicationId'],
    'status' => 'PENDING',
    'response' => $applicationdata,
]);

// Get redirect URL from Addi response.
$redirecturl = $applicationdata['redirectUrl'] ?? $applicationdata['url'] ?? null;

if (!$redirecturl) {
    // No redirect URL provided.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/payment/gateway/addi/pay.php');
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('error'));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('error:applicationfailed', 'paygw_addi'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

// Redirect to Addi.
redirect($redirecturl);
