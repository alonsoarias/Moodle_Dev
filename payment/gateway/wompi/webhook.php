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
 * Webhook handler for Wompi events.
 *
 * This endpoint receives transaction status updates from Wompi.
 *
 * @package    paygw_wompi
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_wompi\wompi_helper;

// This is a webhook, no login required.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require_once(__DIR__ . '/../../../config.php');

// Get the raw POST data.
$payload = file_get_contents('php://input');

if (empty($payload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

// Decode the JSON payload.
$event = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

// Log the webhook event for debugging.
debugging('Wompi webhook received: ' . $payload, DEBUG_DEVELOPER);

// Validate event structure.
if (!isset($event['event']) || !isset($event['data']['transaction'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event structure']);
    exit;
}

$eventtype = $event['event'];
$transaction = $event['data']['transaction'];
$transactionid = $transaction['id'] ?? '';
$reference = $transaction['reference'] ?? '';
$status = strtoupper($transaction['status'] ?? '');

if (empty($transactionid) || empty($reference)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing transaction ID or reference']);
    exit;
}

// Find the local transaction record.
global $DB;
$localrecord = $DB->get_record('paygw_wompi_transactions', ['reference' => $reference]);

if (!$localrecord) {
    // Transaction not found in our database.
    http_response_code(200); // Acknowledge receipt but do nothing.
    echo json_encode(['status' => 'ignored', 'reason' => 'Transaction not found']);
    exit;
}

// Get the gateway configuration.
try {
    $config = (object) helper::get_gateway_configuration(
        $localrecord->component,
        $localrecord->paymentarea,
        $localrecord->itemid,
        'wompi'
    );
} catch (\Exception $e) {
    http_response_code(200);
    echo json_encode(['status' => 'ignored', 'reason' => 'Configuration not found']);
    exit;
}

// Initialize Wompi helper.
$wompihelper = new wompi_helper(
    $config->publickey,
    $config->privatekey,
    $config->integritykey,
    $config->environment ?? 'sandbox',
    $config->eventskey ?? ''
);

// Verify signature if events key is configured.
if (!empty($config->eventskey) && isset($event['signature'])) {
    $signaturefromheader = $event['signature']['checksum'] ?? '';
    if (!$wompihelper->verify_webhook_signature($event, $signaturefromheader)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
}

// Process based on event type.
switch ($eventtype) {
    case 'transaction.updated':
        // Update local transaction status.
        $localrecord->transactionid = $transactionid;
        $localrecord->status = $status;
        $localrecord->paymentmethod = $transaction['payment_method_type'] ?? $localrecord->paymentmethod;
        $localrecord->timemodified = time();
        $DB->update_record('paygw_wompi_transactions', $localrecord);

        // If approved and not yet delivered, deliver the order.
        if ($wompihelper->is_transaction_approved($status) && $localrecord->status !== 'DELIVERED') {
            try {
                $payable = helper::get_payable(
                    $localrecord->component,
                    $localrecord->paymentarea,
                    $localrecord->itemid
                );
                $surcharge = helper::get_gateway_surcharge('wompi');
                $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

                // Deliver the order.
                $wompihelper->deliver_order(
                    $localrecord->component,
                    $localrecord->paymentarea,
                    $localrecord->itemid,
                    $localrecord->userid,
                    $cost,
                    $payable->get_currency()
                );

                // Mark as delivered.
                $localrecord->status = 'DELIVERED';
                $localrecord->timemodified = time();
                $DB->update_record('paygw_wompi_transactions', $localrecord);

                // Notify user of successful payment.
                $url = helper::get_success_url(
                    $localrecord->component,
                    $localrecord->paymentarea,
                    $localrecord->itemid
                );
                $wompihelper->notify_user($localrecord->userid, 'successful', ['url' => $url->out()]);

            } catch (\Exception $e) {
                debugging('Wompi webhook delivery error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // If failed, notify the user.
        if ($wompihelper->is_transaction_failed($status)) {
            $wompihelper->notify_user($localrecord->userid, 'failed');
        }

        break;

    case 'nequi_token.updated':
        // Handle Nequi token updates if needed.
        debugging('Nequi token update received', DEBUG_DEVELOPER);
        break;

    default:
        // Unknown event type, acknowledge but do nothing.
        debugging('Unknown Wompi event type: ' . $eventtype, DEBUG_DEVELOPER);
        break;
}

// Return success response.
http_response_code(200);
echo json_encode(['status' => 'processed']);
