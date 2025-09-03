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
 * Redirects user to the payment page
 *
 * @package     paygw_payu
 * @copyright   2024 Your Organization
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
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu');

$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// Check self cost if not fixcost.
if (!empty($costself) && !$config->fixcost) {
    $cost = $costself;
}

// Check maxcost.
if ($config->maxcost && $cost > $config->maxcost) {
    $cost = $config->maxcost;
}

// Check uninterrupted mode.
$plugin = \core_plugin_manager::instance()->get_plugin_info('enrol_yafee');
$ver = 2025040100;
if ($component == "enrol_yafee" && $config->fixcost) {
    $cs = $DB->get_record('enrol', ['id' => $itemid, 'enrol' => 'yafee']);
    if ($cs->customint5) {
        $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $cs->id]);
        $ctime = time();
        $timeend = $ctime;
        if (isset($data->timeend)) {
            $timeend = $data->timeend;
        }
        $t1 = getdate($timeend);
        $t2 = getdate($ctime);
        if (isset($data->timeend) && $data->timeend < $ctime) {
            if ($cs->enrolperiod) {
                $price = $cost / $cs->enrolperiod;
                $delta = ceil((($ctime - $data->timestart) / $cs->enrolperiod) + 0) * $cs->enrolperiod +
                     $data->timestart - $data->timeend;
                if ($plugin->versiondisk < $ver) {
                    $cost = $delta * $price;
                }
                $uninterrupted = true;
            } else if ($cs->customchar1 == 'month' && $cs->customint7 > 0) {
                $delta = ($t2['year'] - $t1['year']) * 12 + $t2['mon'] - $t1['mon'] + 1;
                if ($plugin->versiondisk < $ver) {
                    $cost = $delta * $cost;
                }
                $uninterrupted = true;
            } else if ($cs->customchar1 == 'year' && $cs->customint7 > 0) {
                $delta = ($t2['year'] - $t1['year']) + 1;
                if ($plugin->versiondisk < $ver) {
                    $cost = $delta * $cost;
                }
                $uninterrupted = true;
            }
        }
    }
}

$cost = number_format($cost, 2, '.', '');

// Get course and groups for user.
if ($component == "enrol_yafee" || $component == "enrol_fee") {
    $cs = $DB->get_record('enrol', ['id' => $itemid]);
    $cs->course = $cs->courseid;
} else if ($paymentarea == "cmfee") {
    $cs = $DB->get_record('course_modules', ['id' => $itemid]);
} else if ($paymentarea == "sectionfee") {
    $cs = $DB->get_record('course_sections', ['id' => $itemid]);
} else if ($component == "mod_gwpayments") {
    $cs = $DB->get_record('gwpayments', ['id' => $itemid]);
}
$groupnames = '';
if (!empty($cs->course)) {
    $courseid = $cs->course;
    if ($gs = groups_get_user_groups($courseid, $userid, true)) {
        foreach ($gs as $gr) {
            foreach ($gr as $g) {
                $groups[] = groups_get_group_name($g);
            }
        }
        if (isset($groups)) {
            $groupnames = implode(',', $groups);
        }
    }
} else {
    $courseid = '';
}

// Write tx to DB.
$paygwdata = new stdClass();
$paygwdata->courseid = $courseid;
$paygwdata->groupnames = $groupnames;

if (!$transactionid = $DB->insert_record('paygw_payu', $paygwdata)) {
    throw new Error(get_string('error_txdatabase', 'paygw_payu'));
}

$paygwdata->id = $transactionid;

// Build redirect.
$url = helper::get_success_url($component, $paymentarea, $itemid);

// Set the context of the page.
$PAGE->set_url($SCRIPT);
$PAGE->set_context(context_system::instance());

// Check passwordmode or skipmode.
if (!empty($password) || $skipmode) {
    $success = false;
    if ($config->skipmode) {
        $success = true;
    } else if (isset($cs->password) && !empty($cs->password)) {
        if ($password === $cs->password) {
            $success = true;
        }
    } else if ($config->passwordmode && !empty($config->password)) {
        if ($password === $config->password) {
            $success = true;
        }
    }

    if ($success) {
        // Make fake pay.
        $paymentid = helper::save_payment(
            $payable->get_account_id(),
            $component,
            $paymentarea,
            $itemid,
            $userid,
            0,
            $payable->get_currency(),
            'payu'
        );
        helper::deliver_order($component, $paymentarea, $itemid, $paymentid, $userid);

        // Write to DB.
        $paygwdata->success = 2;
        $paygwdata->paymentid = $paymentid;
        $DB->update_record('paygw_payu', $paygwdata);

        redirect($url, get_string('password_success', 'paygw_payu'), 0, 'success');
    } else {
        redirect($url, get_string('password_error', 'paygw_payu'), 0, 'error');
    }
    die;
}

// Save payment.
$paymentid = helper::save_payment(
    $payable->get_account_id(),
    $component,
    $paymentarea,
    $itemid,
    $userid,
    $cost,
    $payable->get_currency(),
    'payu'
);

// Generate PayU reference code.
$referencecode = 'MOODLE_' . $paymentid . '_' . time();

// Generate PayU signature.
$signature = md5($config->apikey . '~' . $config->merchantid . '~' . $referencecode . '~' . $cost . '~' . $currency);

// Write to DB.
$paygwdata->paymentid = $paymentid;
$paygwdata->referencecode = $referencecode;
$DB->update_record('paygw_payu', $paygwdata);

$successurl = $CFG->wwwroot . "/payment/gateway/payu/return.php";
$responseurl = $CFG->wwwroot . "/payment/gateway/payu/callback.php";

// Determine PayU checkout URL.
if ($config->testmode) {
    $paymenturl = "https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/";
} else {
    $paymenturl = "https://checkout.payulatam.com/ppp-web-gateway-payu/";
}

// Prepare form for PayU.
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redirecting to PayU...</title>
</head>
<body>
    <form id="payu_form" action="<?php echo $paymenturl; ?>" method="post">
        <input name="merchantId" type="hidden" value="<?php echo $config->merchantid; ?>">
        <input name="accountId" type="hidden" value="<?php echo $config->accountid; ?>">
        <input name="description" type="hidden" value="<?php echo htmlspecialchars($description); ?>">
        <input name="referenceCode" type="hidden" value="<?php echo $referencecode; ?>">
        <input name="amount" type="hidden" value="<?php echo $cost; ?>">
        <input name="currency" type="hidden" value="<?php echo $currency; ?>">
        <input name="signature" type="hidden" value="<?php echo $signature; ?>">
        <input name="test" type="hidden" value="<?php echo $config->testmode ? '1' : '0'; ?>">
        <input name="buyerEmail" type="hidden" value="<?php echo $USER->email; ?>">
        <input name="buyerFullName" type="hidden" value="<?php echo fullname($USER); ?>">
        <input name="responseUrl" type="hidden" value="<?php echo $responseurl; ?>">
        <input name="confirmationUrl" type="hidden" value="<?php echo $successurl; ?>">
        <?php if (!empty($config->paymentsystem) && $config->paymentsystem != '0'): ?>
        <input name="paymentMethods" type="hidden" value="<?php echo $config->paymentsystem; ?>">
        <?php endif; ?>
    </form>
    <script type="text/javascript">
        document.getElementById('payu_form').submit();
    </script>
    <p>Redirecting to PayU payment gateway...</p>
</body>
</html>
<?php