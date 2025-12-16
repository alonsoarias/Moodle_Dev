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
 * Redirects user to the PayU Colombia payment page.
 *
 * @package    paygw_payu
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_payu\payu_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();
require_sesskey();

global $CFG, $USER;

// Get payment parameters.
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

// Decode description.
$description = json_decode('"' . $description . '"');

// Get gateway configuration.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'payu');
$payable = helper::get_payable($component, $paymentarea, $itemid);

// Get currency and payment amount.
$currency = $payable->get_currency();
$surcharge = helper::get_gateway_surcharge('payu');
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// Create PayU helper instance.
$payuhelper = payu_helper::from_config($config);

// Generate unique reference code.
$referencecode = $payuhelper->generate_reference($component, $paymentarea, $itemid, $USER->id);

// Save transaction to database.
$transactionid = $payuhelper->save_transaction([
    'userid' => $USER->id,
    'referencecode' => $referencecode,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'currency' => $currency,
    'amount' => $cost,
    'state' => 'PENDING',
]);

if (!$transactionid) {
    throw new moodle_exception('error_transaction_create', 'paygw_payu');
}

// Prepare buyer information.
$buyername = fullname($USER);
$buyeremail = $USER->email;
$buyerphone = $USER->phone1 ?? $USER->phone2 ?? '';

// Build form parameters.
$formparams = $payuhelper->build_form_params([
    'referenceCode' => $referencecode,
    'description' => $description,
    'amount' => $cost,
    'currency' => $currency,
    'buyerEmail' => $buyeremail,
    'buyerFullName' => $buyername,
    'telephone' => $buyerphone,
    'confirmationUrl' => $CFG->wwwroot . '/payment/gateway/payu/callback.php',
    'responseUrl' => $CFG->wwwroot . '/payment/gateway/payu/return.php?component=' .
        urlencode($component) . '&paymentarea=' . urlencode($paymentarea) . '&itemid=' . $itemid,
    'language' => $config->language ?? 'es',
    'extra1' => $referencecode,
    'extra2' => $component,
    'extra3' => $paymentarea . ':' . $itemid,
]);

// Get checkout URL.
$gatewayurl = $payuhelper->get_checkout_url();

// Output HTML form for redirect.
$PAGE->set_context(context_system::instance());

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('redirecting', 'paygw_payu'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #a8e063 0%, #56ab2f 100%);
        }
        .container {
            text-align: center;
            background: white;
            padding: 48px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            max-width: 400px;
            width: 90%;
        }
        .logo {
            width: 180px;
            margin-bottom: 24px;
        }
        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #e0e0e0;
            border-top-color: #56ab2f;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 24px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        .amount {
            font-size: 28px;
            font-weight: 700;
            color: #56ab2f;
            margin: 16px 0;
        }
        .reference {
            font-size: 12px;
            color: #999;
            margin-top: 16px;
            font-family: monospace;
        }
        noscript .btn {
            display: inline-block;
            background: #56ab2f;
            color: white;
            padding: 12px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="https://colombia.payu.com/wp-content/uploads/sites/2/2022/03/PayU_Logo_Full-color.svg"
             alt="PayU" class="logo" onerror="this.style.display='none'">
        <div class="spinner"></div>
        <h2><?php echo get_string('redirecting', 'paygw_payu'); ?></h2>
        <p><?php echo get_string('redirecting_message', 'paygw_payu'); ?></p>
        <div class="amount">
            <?php echo $currency . ' ' . number_format($cost, 0, ',', '.'); ?>
        </div>
        <div class="reference">
            <?php echo get_string('reference', 'paygw_payu') . ': ' . $referencecode; ?>
        </div>
        <noscript>
            <p><?php echo get_string('javascript_required', 'paygw_payu'); ?></p>
            <button type="submit" form="payuform" class="btn">
                <?php echo get_string('continue_to_payu', 'paygw_payu'); ?>
            </button>
        </noscript>
    </div>

    <form id="payuform" action="<?php echo $gatewayurl; ?>" method="POST" style="display:none;">
        <?php foreach ($formparams as $key => $value): ?>
            <input type="hidden" name="<?php echo htmlspecialchars($key); ?>"
                   value="<?php echo htmlspecialchars($value); ?>">
        <?php endforeach; ?>
    </form>

    <script>
        document.getElementById('payuform').submit();
    </script>
</body>
</html>
