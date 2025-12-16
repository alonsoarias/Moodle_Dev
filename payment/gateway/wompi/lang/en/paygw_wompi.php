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
 * Strings for component 'paygw_wompi', language 'en'.
 *
 * @package    paygw_wompi
 * @copyright  2024 Alonso Arias <alonso.arias@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Wompi';
$string['pluginname_desc'] = 'The Wompi plugin allows you to receive payments via Wompi payment gateway (Colombia).';
$string['gatewaydescription'] = 'Wompi is a payment gateway for Colombia that allows credit cards, PSE, Nequi, and other local payment methods.';
$string['gatewayname'] = 'Wompi';

// Configuration.
$string['environment'] = 'Environment';
$string['environment_help'] = 'Select the Wompi environment. Use Sandbox for testing and Production for real transactions.';
$string['environment:sandbox'] = 'Sandbox (Testing)';
$string['environment:production'] = 'Production (Live)';

$string['publickey'] = 'Public Key';
$string['publickey_help'] = 'The public key from your Wompi dashboard. Starts with pub_test_ for sandbox or pub_prod_ for production.';
$string['privatekey'] = 'Private Key';
$string['privatekey_help'] = 'The private key from your Wompi dashboard. Starts with prv_test_ for sandbox or prv_prod_ for production.';
$string['eventskey'] = 'Events Private Key';
$string['eventskey_help'] = 'The events private key for webhook signature verification. Found in your Wompi dashboard under Events configuration.';
$string['integritykey'] = 'Integrity Key';
$string['integritykey_help'] = 'The integrity key used to generate transaction signatures. Found in your Wompi dashboard.';

$string['invalidpublickey:sandbox'] = 'Invalid public key for sandbox environment. It should start with pub_test_';
$string['invalidpublickey:production'] = 'Invalid public key for production environment. It should start with pub_prod_';
$string['invalidprivatekey:sandbox'] = 'Invalid private key for sandbox environment. It should start with prv_test_';
$string['invalidprivatekey:production'] = 'Invalid private key for production environment. It should start with prv_prod_';

$string['paymentmethods'] = 'Payment Methods';
$string['paymentmethods_help'] = 'Select the payment methods you want to offer to your customers.';

$string['paymentmethod:card'] = 'Credit/Debit Card';
$string['paymentmethod:nequi'] = 'Nequi';
$string['paymentmethod:pse'] = 'PSE (Bank Transfer)';
$string['paymentmethod:bancolombia_transfer'] = 'Bancolombia Transfer';
$string['paymentmethod:bancolombia_collect'] = 'Bancolombia Collect (Cash)';
$string['paymentmethod:daviplata'] = 'Daviplata';
$string['paymentmethod:pcol'] = 'Puntos Colombia';

$string['collectcustomerdata'] = 'Collect customer data';
$string['collectcustomerdata_desc'] = 'Pre-fill customer information (email, name) in the checkout form.';

// Payment process.
$string['redirecting'] = 'Redirecting to payment...';
$string['clicktopay'] = 'Click the button below to proceed with payment';
$string['paynow'] = 'Pay Now';
$string['reference'] = 'Reference';

$string['paymentsuccessful'] = 'Payment was successful';
$string['paymentpending'] = 'Your payment is being processed. You will be notified once confirmed.';
$string['paymentfailed'] = 'Payment failed: {$a}';
$string['paymentdeclined'] = 'Your payment was declined. Please try again or use a different payment method.';
$string['paymentcancelled'] = 'Payment was cancelled';
$string['paymentnotcompleted'] = 'Payment was not completed. Please try again.';
$string['paymentunknownstatus'] = 'Unknown payment status: {$a}. Please contact support.';

$string['transactionnotfound'] = 'Transaction not found. Please contact support if you were charged.';
$string['alreadydelivered'] = 'This payment has already been processed and delivered.';
$string['deliveryerror'] = 'An error occurred while processing your enrollment. Please contact support.';

// Messages.
$string['messageprovider:payment_failed'] = 'Failed payment notification';
$string['messageprovider:payment_successful'] = 'Successful payment confirmation';
$string['messageprovider:payment_pending'] = 'Pending payment notification';

$string['payment:successful:subject'] = 'Payment successful';
$string['payment:successful:message'] = 'Your payment was successful. You can now access your content at: {$a->url}';
$string['payment:failed:subject'] = 'Payment failed';
$string['payment:failed:message'] = 'Your payment failed to process. Please try again or use a different payment method.';
$string['payment:pending:subject'] = 'Payment pending';
$string['payment:pending:message'] = 'Your payment is being processed. You will be notified once it is confirmed.';

// Privacy.
$string['privacy:metadata:paygw_wompi_transactions'] = 'Stores transaction data for Wompi payments.';
$string['privacy:metadata:paygw_wompi_transactions:userid'] = 'The ID of the user who made the payment.';
$string['privacy:metadata:paygw_wompi_transactions:transactionid'] = 'The Wompi transaction ID.';
$string['privacy:metadata:paygw_wompi_transactions:reference'] = 'The unique payment reference.';
$string['privacy:metadata:paygw_wompi_transactions:amount'] = 'The payment amount in cents.';
$string['privacy:metadata:paygw_wompi_transactions:status'] = 'The transaction status.';
$string['privacy:metadata:paygw_wompi_transactions:timecreated'] = 'The time the transaction was created.';

$string['privacy:metadata:wompi'] = 'In order to process payments, some user data is sent to Wompi.';
$string['privacy:metadata:wompi:email'] = 'Your email address.';
$string['privacy:metadata:wompi:fullname'] = 'Your full name.';
