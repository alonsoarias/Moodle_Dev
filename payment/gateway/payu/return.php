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
 * Redirects user to the original page
 *
 * @package   paygw_payu
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require("../../../config.php");
global $CFG, $USER, $DB;

defined('MOODLE_INTERNAL') || die();

require_login();

$referencesale = required_param('referenceCode', PARAM_TEXT);
$transactionstate = optional_param('transactionState', 4, PARAM_INT);

if (!$payutx = $DB->get_record('paygw_payu', ['referencecode' => $referencesale])) {
    throw new \moodle_exception(get_string('error_notvalidtxid', 'paygw_payu'), 'paygw_payu');
}

if (!$payment = $DB->get_record('payments', ['id' => $payutx->paymentid])) {
    throw new \moodle_exception(get_string('error_notvalidpayment', 'paygw_payu'), 'paygw_payu');
}

$paymentarea = $payment->paymentarea;
$component   = $payment->component;
$itemid      = $payment->itemid;

$url = helper::get_success_url($component, $paymentarea, $itemid);

// Check transaction state.
// 4 = Approved, 6 = Declined, 5 = Expired, 7 = Pending.
if ($transactionstate == 4 && $payutx->success > 0) {
    redirect($url, get_string('payment_success', 'paygw_payu'), 0, 'success');
} else if ($transactionstate == 7) {
    redirect($url, get_string('payment_pending', 'paygw_payu'), 0, 'info');
} else {
    redirect($url, get_string('payment_error', 'paygw_payu'), 0, 'error');
}