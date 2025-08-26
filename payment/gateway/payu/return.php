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
 * PayU return page after payment.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

global $DB;

// Get return parameters.
$paymentid = optional_param('paymentid', 0, PARAM_INT);
$referencecode = optional_param('referenceCode', '', PARAM_TEXT);

// Try to find payment by ID or reference code.
if ($paymentid) {
    $payment = $DB->get_record('payments', ['id' => $paymentid]);
} else if ($referencecode) {
    $payment = $DB->get_record('payments', ['id' => $referencecode]);
} else {
    // No payment identifier provided.
    $PAGE->set_url('/payment/gateway/payu/return.php');
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('gatewayname', 'paygw_payu'));
    
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('invalidreference', 'paygw_payu'), 'error');
    echo $OUTPUT->continue_button(new moodle_url('/'));
    echo $OUTPUT->footer();
    exit;
}

if (!$payment) {
    throw new moodle_exception('invalidreference', 'paygw_payu');
}

// Check if user is authorized to view this payment.
if ($payment->userid != $USER->id) {
    throw new moodle_exception('nopermissions', 'error');
}

// Get transaction details.
$transaction = $DB->get_record('paygw_payu', ['paymentid' => $payment->id]);

// Redirect to success URL.
$successurl = helper::get_success_url(
    $payment->component,
    $payment->paymentarea,
    $payment->itemid
);

// Show appropriate message based on transaction state.
if ($transaction) {
    switch ($transaction->state) {
        case 'APPROVED':
            redirect($successurl, get_string('payment_success', 'paygw_payu'), 
                    null, \core\output\notification::NOTIFY_SUCCESS);
            break;
            
        case 'PENDING':
            redirect($successurl, get_string('payment_pending', 'paygw_payu'), 
                    null, \core\output\notification::NOTIFY_INFO);
            break;
            
        case 'DECLINED':
        case 'ERROR':
        case 'EXPIRED':
            redirect($successurl, get_string('payment_failed', 'paygw_payu'), 
                    null, \core\output\notification::NOTIFY_ERROR);
            break;
            
        default:
            redirect($successurl);
    }
} else {
    // No transaction record yet - payment is being processed.
    redirect($successurl, get_string('payment_processing', 'paygw_payu'), 
            null, \core\output\notification::NOTIFY_INFO);
}