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
 * Redirects to the Wompi checkout for payment.
 *
 * @package    paygw_wompi
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_wompi\gateway;
use paygw_wompi\wompi_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
$description = urldecode(required_param('description', PARAM_TEXT));

// Get configuration and payable information.
$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'wompi');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('wompi');

// Calculate cost with surcharge.
$cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);

// Initialize Wompi helper.
$wompihelper = new wompi_helper(
    $config->publickey,
    $config->privatekey,
    $config->integritykey,
    $config->environment ?? 'sandbox',
    $config->eventskey ?? ''
);

// Generate unique reference for this transaction.
$reference = $wompihelper->generate_reference($component, $paymentarea, $itemid, $USER->id);

// Convert amount to cents (Wompi requires amounts in cents).
$amountincents = $wompihelper->amount_to_cents($cost);

// Generate integrity signature.
$currency = $payable->get_currency();
$signature = $wompihelper->generate_integrity_signature($reference, $amountincents, $currency);

// Build success and cancel URLs.
$successurl = new moodle_url('/payment/gateway/wompi/process.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
]);

$cancelurl = new moodle_url('/payment/gateway/wompi/cancelled.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
]);

// Get public key and checkout URL.
$publickey = $wompihelper->get_public_key();
$checkouturl = gateway::get_checkout_url();

// Prepare customer data if collection is enabled.
$customerdata = [];
if (!empty($config->collectcustomerdata)) {
    $customerdata = [
        'email' => $USER->email,
        'full_name' => fullname($USER),
        'phone_number' => $USER->phone1 ?? '',
    ];
}

// Save pending transaction to database.
$wompihelper->save_transaction([
    'userid' => $USER->id,
    'transactionid' => '', // Will be updated after payment.
    'reference' => $reference,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'amount' => $amountincents,
    'currency' => $currency,
    'status' => 'PENDING',
    'paymentmethod' => '',
]);

// Build the checkout page with Wompi widget.
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/payment/gateway/wompi/pay.php');
$PAGE->set_pagelayout('embedded');
$PAGE->set_title(get_string('redirecting', 'paygw_wompi'));

// Prepare payment methods.
$paymentmethods = $config->paymentmethods ?? ['CARD'];
if (is_string($paymentmethods)) {
    $paymentmethods = [$paymentmethods];
}

echo $OUTPUT->header();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo get_string('redirecting', 'paygw_wompi'); ?></title>
    <script src="<?php echo $checkouturl; ?>/widget.js"></script>
    <style>
        .wompi-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 400px;
            padding: 20px;
        }
        .wompi-loading {
            text-align: center;
            margin-bottom: 20px;
        }
        .wompi-info {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            max-width: 400px;
            width: 100%;
        }
        .wompi-info h3 {
            margin-top: 0;
            color: #333;
        }
        .wompi-info p {
            margin: 5px 0;
            color: #666;
        }
        .wompi-info .amount {
            font-size: 1.5em;
            font-weight: bold;
            color: #00a650;
        }
        .wompi-button {
            background: #00a650;
            color: white;
            border: none;
            padding: 15px 40px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .wompi-button:hover {
            background: #008a42;
        }
        .wompi-cancel {
            margin-top: 15px;
        }
        .wompi-cancel a {
            color: #666;
            text-decoration: none;
        }
        .wompi-cancel a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="wompi-container">
        <div class="wompi-info">
            <h3><?php echo format_string($description); ?></h3>
            <p class="amount">$<?php echo number_format($cost, 0, ',', '.'); ?> <?php echo $currency; ?></p>
            <p><?php echo get_string('reference', 'paygw_wompi'); ?>: <?php echo $reference; ?></p>
        </div>

        <div class="wompi-loading">
            <p><?php echo get_string('clicktopay', 'paygw_wompi'); ?></p>
        </div>

        <button id="wompi-pay-button" class="wompi-button">
            <?php echo get_string('paynow', 'paygw_wompi'); ?>
        </button>

        <div class="wompi-cancel">
            <a href="<?php echo $cancelurl->out(); ?>"><?php echo get_string('cancel'); ?></a>
        </div>
    </div>

    <script>
        document.getElementById('wompi-pay-button').addEventListener('click', function() {
            var checkout = new WidgetCheckout({
                currency: '<?php echo $currency; ?>',
                amountInCents: <?php echo $amountincents; ?>,
                reference: '<?php echo $reference; ?>',
                publicKey: '<?php echo $publickey; ?>',
                signature: {
                    integrity: '<?php echo $signature; ?>'
                },
                redirectUrl: '<?php echo $successurl->out(false); ?>',
                <?php if (!empty($customerdata['email'])): ?>
                customerData: {
                    email: '<?php echo $customerdata['email']; ?>',
                    fullName: '<?php echo addslashes($customerdata['full_name']); ?>',
                    <?php if (!empty($customerdata['phone_number'])): ?>
                    phoneNumber: '<?php echo $customerdata['phone_number']; ?>',
                    <?php endif; ?>
                },
                <?php endif; ?>
            });

            checkout.open(function(result) {
                var transaction = result.transaction;
                if (transaction) {
                    // Redirect to process page with transaction ID.
                    var processUrl = '<?php echo $successurl->out(false); ?>';
                    processUrl += (processUrl.indexOf('?') > -1 ? '&' : '?') + 'id=' + transaction.id;
                    window.location.href = processUrl;
                }
            });
        });
    </script>
</body>
</html>
<?php
echo $OUTPUT->footer();
