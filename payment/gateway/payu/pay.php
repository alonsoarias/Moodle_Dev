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
 * Redirects user to the PayU payment portal
 *
 * @package     paygw_payu
 * @copyright   2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author      Alonso Arias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;

require_once(__DIR__ . '/../../../config.php');
global $CFG, $USER, $DB;
require_once($CFG->libdir . '/filelib.php');

require_login();
require_sesskey();

$userid = $USER->id;

$component   = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid      = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

$password    = optional_param('password', null, PARAM_TEXT);
$skipmode    = optional_param('skipmode', null, PARAM_INT);
$costself    = optional_param('costself', null, PARAM_TEXT);

$description = json_decode('"' . $description . '"');

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
$payable = helper::get_payable($component, $paymentarea, $itemid);

// Get currency and payment amount.
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu'); // In case user uses surcharge.

$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// Check self cost if not fixcost.
if (!empty($costself) && !$config->fixcost) {
    $cost = $costself;
}

// Check maxcost.
if ($config->maxcost && $cost > $config->maxcost) {
    $cost = $config->maxcost;
}

// Check uninterrupted mode for enrol_nexuspay.
$plugin = \core_plugin_manager::instance()->get_plugin_info('enrol_nexuspay');
$ver = 2025040100;
if ($component == "enrol_nexuspay" && $config->fixcost && $plugin && $plugin->versiondisk >= $ver) {
    $cs = $DB->get_record('enrol', ['id' => $itemid, 'enrol' => 'nexuspay']);
    if ($cs->customint5) {
        $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $cs->id]);
        // Prepare month and year.
        $ctime = time();
        $timeend = $ctime;
        if (isset($data->timeend)) {
            $timeend = $data->timeend;
        }
        $t1 = getdate($timeend);
        $t2 = getdate($ctime);
        
        // Check periods.
        $counter = 0;
        if ($t2['year'] > $t1['year']) {
            $ydiff = $t2['year'] - $t1['year'];
            if ($ydiff > 1) {
                $counter += 12 * ($ydiff - 1);
            }
            if ($t2['mon'] >= $t1['mon']) {
                $counter += 12 + $t2['mon'] - $t1['mon'];
            } else {
                $counter += 12 - ($t1['mon'] - $t2['mon']);
            }
        } else {
            $counter += $t2['mon'] - $t1['mon'];
        }
        
        // Check day.
        if ($t2['mday'] < $t1['mday'] && $counter > 0) {
            $counter -= 1;
        }
        
        if ($counter > 0) {
            $cost = $cost * $counter;
        }
    }
}

// Create payment record in database.
$paymentid = helper::save_payment(
    $payable->get_account_id(),
    $component,
    $paymentarea,
    $itemid,
    $userid,
    $cost,
    $currency,
    'payu'
);

// Save PayU transaction data.
$payutx = new stdClass();
$payutx->paymentid = $paymentid;
$payutx->courseid = $COURSE->id;
$payutx->success = 0;
$payutx->groupnames = '';
$DB->insert_record('paygw_payu', $payutx);

// Generate reference code.
$referencecode = 'MOODLE_' . $paymentid . '_' . time();

// Generate signature for PayU.
$signature = md5($config->apikey . '~' . $config->merchantid . '~' . $referencecode . '~' . $cost . '~' . $currency);

// Determine PayU URL based on test mode.
$payuurl = $config->testmode 
    ? 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/' 
    : 'https://checkout.payulatam.com/ppp-web-gateway-payu/';

// Build response and confirmation URLs.
$responseurl = new moodle_url('/payment/gateway/payu/response.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'paymentid' => $paymentid
]);

$confirmationurl = new moodle_url('/payment/gateway/payu/callback.php');

// Prepare form parameters for PayU.
$params = [
    'merchantId' => $config->merchantid,
    'accountId' => $config->accountid,
    'description' => $description,
    'referenceCode' => $referencecode,
    'amount' => $cost,
    'tax' => '0',
    'taxReturnBase' => '0',
    'currency' => $currency,
    'signature' => $signature,
    'test' => $config->testmode ? '1' : '0',
    'buyerEmail' => $USER->email,
    'buyerFullName' => fullname($USER),
    'responseUrl' => $responseurl->out(false),
    'confirmationUrl' => $confirmationurl->out(false)
];

// Build HTML form for redirection to PayU.
$html = '<html><body>';
$html .= '<form id="payuform" action="' . $payuurl . '" method="post">';
foreach ($params as $key => $value) {
    $html .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
}
$html .= '</form>';
$html .= '<script>document.getElementById("payuform").submit();</script>';
$html .= '</body></html>';

echo $html;