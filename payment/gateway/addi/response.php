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
 * Handles Addi response (user return from payment page).
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

$reference = required_param('reference', PARAM_ALPHANUMEXT);
$status = optional_param('status', '', PARAM_ALPHA);

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/payment/gateway/addi/response.php', ['reference' => $reference]);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('paymentresult', 'paygw_addi'));

echo $OUTPUT->header();

// Get transaction from database.
global $DB;
$transaction = $DB->get_record('paygw_addi_transactions', ['reference' => $reference]);

if (!$transaction) {
    echo $OUTPUT->notification(get_string('error:noreference', 'paygw_addi'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

// Get configuration.
try {
    $config = (object) helper::get_gateway_configuration(
        $transaction->component,
        $transaction->paymentarea,
        $transaction->itemid,
        'addi'
    );
    $addihelper = gateway::create_helper_from_config($config);
} catch (\Exception $e) {
    echo $OUTPUT->notification(get_string('error:queryingaddi', 'paygw_addi'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    die();
}

// If we have an application ID, query Addi for the current status.
$addistatus = null;
if (!empty($transaction->applicationid)) {
    $applicationdata = $addihelper->get_application($transaction->applicationid);
    if ($applicationdata && isset($applicationdata['status'])) {
        $addistatus = strtoupper($applicationdata['status']);

        // Update transaction status if changed.
        $newstatus = $addihelper->map_addi_status($addistatus);
        if ($transaction->status !== $newstatus) {
            $addihelper->update_transaction($transaction->id, [
                'status' => $newstatus,
                'response' => $applicationdata,
            ]);
            $transaction->status = $newstatus;
        }

        // If approved and not yet delivered, deliver the order.
        if ($addistatus === addi_helper::STATUS_APPROVED && empty($transaction->paymentid)) {
            try {
                $payable = helper::get_payable(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid
                );
                $surcharge = helper::get_gateway_surcharge('addi');
                $paymentamount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

                // Deliver the order.
                $paymentid = helper::save_payment(
                    $payable->get_account_id(),
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    (int)$transaction->userid,
                    $paymentamount,
                    $payable->get_currency(),
                    'addi'
                );

                helper::deliver_order(
                    $transaction->component,
                    $transaction->paymentarea,
                    $transaction->itemid,
                    $paymentid,
                    (int)$transaction->userid
                );

                // Update transaction with payment ID.
                $DB->set_field('paygw_addi_transactions', 'paymentid', $paymentid, ['id' => $transaction->id]);

                // Send notification email.
                $user = \core_user::get_user($transaction->userid);
                if ($user) {
                    $addihelper->send_notification_email($user, $config, 'completed', [
                        'amount' => number_format($transaction->amount, 0, ',', '.') . ' ' . $transaction->currency,
                        'currency' => $transaction->currency,
                        'orderid' => $transaction->reference,
                    ]);
                }
            } catch (\Exception $e) {
                debugging('Addi response: Error delivering order: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
    }
}

// Display result based on status.
echo html_writer::start_div('payment-result text-center py-5');

$displaystatus = $addistatus ?: strtoupper($status);

if ($displaystatus === 'APPROVED' || $transaction->status === 'APPROVED') {
    // Payment approved.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-check-circle fa-5x text-success']),
        ['class' => 'mb-4']
    );
    echo html_writer::tag('h2', get_string('paymentapproved', 'paygw_addi'), ['class' => 'text-success']);
    echo html_writer::tag('p', get_string('paymentapproved_desc', 'paygw_addi'), ['class' => 'lead']);

} else if ($displaystatus === 'PENDING' || $transaction->status === 'PENDING') {
    // Payment pending.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-clock-o fa-5x text-warning']),
        ['class' => 'mb-4']
    );
    echo html_writer::tag('h2', get_string('paymentpending', 'paygw_addi'), ['class' => 'text-warning']);
    echo html_writer::tag('p', get_string('paymentpending_desc', 'paygw_addi'), ['class' => 'lead']);

} else if ($displaystatus === 'CANCELLED' || $transaction->status === 'CANCELLED') {
    // Payment cancelled.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-ban fa-5x text-secondary']),
        ['class' => 'mb-4']
    );
    echo html_writer::tag('h2', get_string('paymentcancelled', 'paygw_addi'), ['class' => 'text-secondary']);
    echo html_writer::tag('p', get_string('paymentcancelled_desc', 'paygw_addi'), ['class' => 'lead']);

} else if (in_array($displaystatus, ['REJECTED', 'DECLINED']) ||
           in_array($transaction->status, ['REJECTED', 'DECLINED'])) {
    // Payment rejected.
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-times-circle fa-5x text-danger']),
        ['class' => 'mb-4']
    );
    echo html_writer::tag('h2', get_string('paymentrejected', 'paygw_addi'), ['class' => 'text-danger']);
    echo html_writer::tag('p', get_string('paymentrejected_desc', 'paygw_addi'), ['class' => 'lead']);

} else {
    // Other status (failed, etc.).
    echo html_writer::tag('div',
        html_writer::tag('i', '', ['class' => 'fa fa-exclamation-circle fa-5x text-secondary']),
        ['class' => 'mb-4']
    );
    echo html_writer::tag('h2', get_string('paymentfailed', 'paygw_addi'), ['class' => 'text-secondary']);
    echo html_writer::tag('p', get_string('paymentfailed_desc', 'paygw_addi'), ['class' => 'lead']);
}

// Transaction details.
echo html_writer::start_tag('div', ['class' => 'card mt-4 mx-auto', 'style' => 'max-width: 400px;']);
echo html_writer::start_tag('div', ['class' => 'card-body']);
echo html_writer::tag('h5', get_string('transactiondetails', 'paygw_addi'), ['class' => 'card-title']);

echo html_writer::tag('p',
    html_writer::tag('strong', get_string('reference', 'paygw_addi') . ': ') . s($transaction->reference),
    ['class' => 'card-text mb-1']
);
if (!empty($transaction->applicationid)) {
    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('applicationid', 'paygw_addi') . ': ') . s($transaction->applicationid),
        ['class' => 'card-text mb-1']
    );
}
echo html_writer::tag('p',
    html_writer::tag('strong', get_string('amount', 'paygw_addi') . ': ') .
    '$' . number_format($transaction->amount, 0, ',', '.') . ' ' . s($transaction->currency),
    ['class' => 'card-text mb-1']
);

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo html_writer::end_div();

// Determine redirect URL.
$redirecturl = new moodle_url('/');

if ($displaystatus === 'APPROVED' || $transaction->status === 'APPROVED') {
    try {
        $successurl = helper::get_success_url($transaction->component, $transaction->paymentarea, $transaction->itemid);
        $redirecturl = $successurl;
    } catch (\Exception $e) {
        // Use default redirect.
    }
}

echo html_writer::start_div('text-center mt-4');
echo $OUTPUT->single_button($redirecturl, get_string('continue'), 'get');
echo html_writer::end_div();

echo $OUTPUT->footer();
