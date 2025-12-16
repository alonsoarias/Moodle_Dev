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
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
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
$string['invalidintegritykey:sandbox'] = 'Invalid integrity key for sandbox environment. It should start with test_integrity_';
$string['invalidintegritykey:production'] = 'Invalid integrity key for production environment. It should start with prod_integrity_';
$string['sandboxcredentials'] = 'Automatic sandbox credentials';
$string['sandboxcredentials_desc'] = 'In sandbox mode, automatic test credentials will be used if no custom credentials are provided.';

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

// Scheduled tasks.
$string['task_check_pending'] = 'Check pending Wompi transactions';
$string['task_cleanup_expired'] = 'Clean up expired Wompi transactions';

// Status notifications.
$string['payment_notification'] = 'Wompi payment notification';
$string['payment_approved'] = 'Your payment has been approved. You can now access your content.';
$string['payment_declined'] = 'Your payment has been declined. Please try a different payment method.';
$string['payment_expired_subject'] = 'Payment expired';
$string['payment_expired_message'] = 'Your payment of {$a->amount} {$a->currency} initiated on {$a->date} has expired due to lack of confirmation. If you wish to complete the purchase, please initiate a new payment.';
$string['messageprovider:payment_status'] = 'Payment status notifications';

// Email template settings.
$string['emailtemplates'] = 'Email Notification Templates';
$string['availableplaceholders'] = 'Available Placeholders';
$string['placeholder:firstname'] = 'User\'s first name';
$string['placeholder:fullname'] = 'User\'s full name';
$string['placeholder:amount'] = 'Payment amount with currency';
$string['placeholder:currency'] = 'Currency code';
$string['placeholder:orderid'] = 'Order/Reference ID';

$string['email_completed_subject'] = 'Payment Completed - Subject';
$string['email_completed_subject_default'] = 'Payment Successful - Order #{orderid}';
$string['email_completed_body'] = 'Payment Completed - Body';
$string['email_completed_body_default'] = 'Hello {firstname},

Your payment of {amount} (Order ID: {orderid}) was completed successfully.

If you cannot access the course, please contact the administrator.';

$string['email_pending_subject'] = 'Payment Pending - Subject';
$string['email_pending_subject_default'] = 'Payment Pending - Order #{orderid}';
$string['email_pending_body'] = 'Payment Pending - Body';
$string['email_pending_body_default'] = 'Hello {firstname},

Your payment of {amount} (Order ID: {orderid}) is pending approval.

We will notify you once the payment is confirmed.';
