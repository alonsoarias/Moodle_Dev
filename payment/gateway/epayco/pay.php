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
 * Initiates the ePayco payment process.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_epayco\gateway;
use paygw_epayco\epayco_helper;

require_once(__DIR__ . '/../../../config.php');

require_login();

$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'epayco');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('epayco');

$amount = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(), $surcharge);
$currency = $payable->get_currency();

// Create helper instance.
$epaycohelper = gateway::create_helper_from_config($config);

// Generate unique reference.
$reference = $epaycohelper->generate_reference($USER->id, $component, $paymentarea, $itemid);

// URLs for ePayco.
$confirmationurl = new moodle_url('/payment/gateway/epayco/confirmation.php');
$responseurl = new moodle_url('/payment/gateway/epayco/response.php');

// Customer data.
$customerdata = [];
if (!empty($config->collectcustomerdata)) {
    $customerdata = [
        'email' => $USER->email,
        'name' => $USER->firstname,
        'last_name' => $USER->lastname,
        'phone' => $USER->phone1 ?: '',
        'address' => $USER->address ?: '',
        'city' => $USER->city ?: '',
        'country' => 'CO',
        'doc_type' => 'CC',
        'doc_number' => '',
    ];
}

// Save initial transaction.
$transactionid = $epaycohelper->save_transaction([
    'userid' => $USER->id,
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'reference' => $reference,
    'amount' => $amount,
    'currency' => $currency,
    'status' => 'INITIATED',
]);

// Page setup.
$PAGE->set_url('/payment/gateway/epayco/pay.php', [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'description' => $description,
]);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('popup');
$PAGE->set_title(get_string('redirecting', 'paygw_epayco'));

echo $OUTPUT->header();
?>

<div style="text-align: center; padding: 40px;">
    <h3><?php echo get_string('redirecting', 'paygw_epayco'); ?></h3>
    <p><?php echo get_string('clicktopay', 'paygw_epayco'); ?></p>
    <p><strong><?php echo get_string('reference', 'paygw_epayco'); ?>:</strong> <?php echo $reference; ?></p>
    <div id="epayco-button" style="margin-top: 20px;"></div>
</div>

<script src="https://checkout.epayco.co/checkout.js"></script>
<script>
    var handler = ePayco.checkout.configure({
        key: '<?php echo $epaycohelper->get_public_key(); ?>',
        test: <?php echo $epaycohelper->is_test_mode() ? 'true' : 'false'; ?>
    });

    var data = {
        // Basic parameters.
        name: <?php echo json_encode($description); ?>,
        description: <?php echo json_encode($description); ?>,
        invoice: <?php echo json_encode($reference); ?>,
        currency: <?php echo json_encode($currency); ?>,
        amount: <?php echo json_encode((string)$amount); ?>,
        tax_base: "0",
        tax: "0",
        tax_ico: "0",
        country: "co",
        lang: <?php echo json_encode($config->language ?? 'es'); ?>,

        // URLs.
        external: "false",
        response: <?php echo json_encode($responseurl->out(false)); ?>,
        confirmation: <?php echo json_encode($confirmationurl->out(false)); ?>,

        // Extra data for identification.
        extra1: <?php echo json_encode($component); ?>,
        extra2: <?php echo json_encode($paymentarea); ?>,
        extra3: <?php echo json_encode((string)$itemid); ?>,
        extra4: <?php echo json_encode((string)$USER->id); ?>,

        <?php if (!empty($customerdata)): ?>
        // Customer data.
        name_billing: <?php echo json_encode($customerdata['name']); ?>,
        address_billing: <?php echo json_encode($customerdata['address']); ?>,
        type_doc_billing: <?php echo json_encode($customerdata['doc_type']); ?>,
        mobilephone_billing: <?php echo json_encode($customerdata['phone']); ?>,
        number_doc_billing: <?php echo json_encode($customerdata['doc_number']); ?>,
        email_billing: <?php echo json_encode($customerdata['email']); ?>,
        <?php endif; ?>
    };

    // Open checkout automatically.
    handler.open(data);
</script>

<?php
echo $OUTPUT->footer();
