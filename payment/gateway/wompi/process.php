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
 * Process payment callback from Wompi.
 *
 * @package    paygw_wompi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_wompi\gateway;
use paygw_wompi\wompi_helper;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

require_login();

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$transactionid = optional_param('id', '', PARAM_TEXT);

// Get configuration.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'wompi');
$payable = helper::get_payable($component, $paymentarea, $itemid);

// Initialize Wompi helper (with automatic sandbox credentials support).
$wompihelper = gateway::create_helper_from_config($config);

// If no transaction ID provided, check if we have a reference.
if (empty($transactionid)) {
    // User might have returned without completing payment.
    redirect(
        new moodle_url('/'),
        get_string('paymentnotcompleted', 'paygw_wompi'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

// Verify the transaction with Wompi API.
$transaction = $wompihelper->get_transaction($transactionid);

if (!$transaction) {
    redirect(
        new moodle_url('/'),
        get_string('transactionnotfound', 'paygw_wompi'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// Get transaction status.
$status = strtoupper($transaction['status'] ?? '');

// Check if already delivered to prevent duplicate deliveries.
if ($wompihelper->is_already_delivered($transactionid)) {
    $url = helper::get_success_url($component, $paymentarea, $itemid);
    redirect(
        $url,
        get_string('alreadydelivered', 'paygw_wompi'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

// Update local transaction record.
$localrecord = $wompihelper->get_local_transaction_by_reference($transaction['reference'] ?? '');
if ($localrecord) {
    $wompihelper->update_transaction_status($localrecord->transactionid ?: $transactionid, $status);
}

// Process based on status.
if ($wompihelper->is_transaction_approved($status)) {
    // Payment successful - deliver the order.
    $surcharge = helper::get_gateway_surcharge('wompi');
    $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

    try {
        // Deliver the order to the user.
        $wompihelper->deliver_order(
            $component,
            $paymentarea,
            $itemid,
            $USER->id,
            $cost,
            $payable->get_currency()
        );

        // Mark transaction as delivered.
        if ($localrecord) {
            $wompihelper->mark_as_delivered($transactionid);
        } else {
            // Save the transaction if it wasn't saved before.
            $wompihelper->save_transaction([
                'userid' => $USER->id,
                'transactionid' => $transactionid,
                'reference' => $transaction['reference'] ?? '',
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid,
                'amount' => $transaction['amount_in_cents'] ?? 0,
                'currency' => $transaction['currency'] ?? 'COP',
                'status' => 'DELIVERED',
                'paymentmethod' => $transaction['payment_method_type'] ?? '',
            ]);
        }

        // Redirect to success page.
        $url = helper::get_success_url($component, $paymentarea, $itemid);
        redirect($url, get_string('paymentsuccessful', 'paygw_wompi'), 0, 'success');

    } catch (\Exception $e) {
        debugging('Wompi payment delivery error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        redirect(
            new moodle_url('/'),
            get_string('deliveryerror', 'paygw_wompi'),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

} else if ($wompihelper->is_transaction_pending($status)) {
    // Payment is pending (async payment methods like PSE, Bancolombia, etc.)
    redirect(
        new moodle_url('/'),
        get_string('paymentpending', 'paygw_wompi'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );

} else if ($wompihelper->is_transaction_failed($status)) {
    // Payment failed or was declined.
    $errordetail = $transaction['status_message'] ?? get_string('paymentdeclined', 'paygw_wompi');
    redirect(
        new moodle_url('/'),
        get_string('paymentfailed', 'paygw_wompi', $errordetail),
        null,
        \core\output\notification::NOTIFY_ERROR
    );

} else {
    // Unknown status.
    redirect(
        new moodle_url('/'),
        get_string('paymentunknownstatus', 'paygw_wompi', $status),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}
