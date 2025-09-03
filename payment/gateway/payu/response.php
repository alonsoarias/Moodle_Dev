<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * PayU response page - shown to user after payment
 *
 * @package     paygw_payu
 * @copyright   2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author      Alonso Arias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/enrollib.php');

use core_payment\helper;

global $CFG, $USER, $DB;

require_login();

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$paymentid   = required_param('paymentid', PARAM_INT);

// PayU response parameters.
$merchantid        = optional_param('merchantId', '', PARAM_TEXT);
$transactionstate  = optional_param('transactionState', '', PARAM_INT);
$referencecode     = optional_param('referenceCode', '', PARAM_TEXT);
$amount            = optional_param('TX_VALUE', 0, PARAM_FLOAT);
$currency          = optional_param('currency', '', PARAM_TEXT);
$signature         = optional_param('signature', '', PARAM_TEXT);
$referencepol      = optional_param('reference_pol', '', PARAM_TEXT);
$transactionid     = optional_param('transactionId', '', PARAM_TEXT);
$message           = optional_param('message', '', PARAM_TEXT);

$payable = helper::get_payable($component, $paymentarea, $itemid);
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), helper::get_gateway_surcharge('payu'));

// Get success URL.
$successurl = helper::get_success_url($component, $paymentarea, $itemid)->out(false);

$PAGE->set_url('/payment/gateway/payu/response.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid
]);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('paymentresponse', 'paygw_payu'));
$PAGE->set_heading(get_string('paymentresponse', 'paygw_payu'));

echo $OUTPUT->header();

// Display message based on transaction state.
if ($transactionstate == 4) { // Approved.
    echo $OUTPUT->notification(get_string('payment_success', 'paygw_payu'), 'notifysuccess');
    echo html_writer::tag('p', get_string('paymentsuccessful', 'paygw_payu'));
    echo html_writer::tag('p', get_string('transactionid', 'paygw_payu') . ': ' . $transactionid);
    echo html_writer::tag('p', get_string('referencecode', 'paygw_payu') . ': ' . $referencecode);
    echo $OUTPUT->continue_button($successurl);
} else if ($transactionstate == 6) { // Declined.
    echo $OUTPUT->notification(get_string('payment_error', 'paygw_payu'), 'notifyerror');
    echo html_writer::tag('p', get_string('paymentdeclined', 'paygw_payu'));
    echo html_writer::tag('p', get_string('message', 'paygw_payu') . ': ' . $message);
    echo $OUTPUT->continue_button($CFG->wwwroot);
} else if ($transactionstate == 7) { // Pending.
    echo $OUTPUT->notification(get_string('paymentpending', 'paygw_payu'), 'notifywarning');
    echo html_writer::tag('p', get_string('paymentpendingdesc', 'paygw_payu'));
    echo html_writer::tag('p', get_string('transactionid', 'paygw_payu') . ': ' . $transactionid);
    echo $OUTPUT->continue_button($CFG->wwwroot);
} else if ($transactionstate == 5) { // Expired.
    echo $OUTPUT->notification(get_string('paymentexpired', 'paygw_payu'), 'notifyerror');
    echo html_writer::tag('p', get_string('paymentexpireddesc', 'paygw_payu'));
    echo $OUTPUT->continue_button($CFG->wwwroot);
} else {
    echo $OUTPUT->notification(get_string('unknownstate', 'paygw_payu'), 'notifyerror');
    echo $OUTPUT->continue_button($CFG->wwwroot);
}

echo $OUTPUT->footer();