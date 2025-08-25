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
 * Handles PayU payment notifications.
 *
 * @package     paygw_payu
 * @copyright   2024 Example
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require(__DIR__ . '/../../../config.php');

global $DB;

$reference = required_param('reference_sale', PARAM_INT);
$signature = required_param('sign', PARAM_RAW);
$value = required_param('value', PARAM_RAW);
$currency = required_param('currency', PARAM_TEXT);
$state = required_param('state_pol', PARAM_INT);
$merchantid = required_param('merchant_id', PARAM_INT);

if (!$payment = $DB->get_record('payments', ['id' => $reference])) {
    die('ERROR: invalid reference');
}

$component = $payment->component;
$paymentarea = $payment->paymentarea;
$itemid = $payment->itemid;
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');

if ((int)$merchantid !== (int)$config->merchantid) {
    die('ERROR: invalid merchant');
}

$formattedvalue = number_format((float)$value, 2, '.', '');
$localsign = md5($config->apikey . '~' . $merchantid . '~' . $reference . '~' . $formattedvalue . '~' . $currency . '~' . $state);
if (strtoupper($localsign) !== strtoupper($signature)) {
    die('ERROR: invalid signature');
}

if ((int)$state === 4) { // 4 means approved.
    helper::deliver_order($component, $paymentarea, $itemid, $payment->id, $payment->userid);
    echo 'OK';
} else {
    echo 'IGNORED';
}
