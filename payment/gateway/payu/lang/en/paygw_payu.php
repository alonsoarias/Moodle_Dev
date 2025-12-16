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
 * Strings for component 'paygw_payu', language 'en'.
 *
 * @package    paygw_payu
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PayU Colombia';
$string['pluginname_desc'] = 'The PayU plugin allows you to receive payments via the PayU platform for Colombia.';
$string['gatewayname'] = 'PayU';
$string['gatewaydescription'] = 'PayU is a payment gateway for Colombia that supports credit/debit cards, PSE, cash and other local payment methods.';

// Configuration.
$string['environment'] = 'Environment';
$string['environment_help'] = 'Select the PayU environment. Use Sandbox for testing and Production for real transactions.';
$string['environment:sandbox'] = 'Sandbox (Testing)';
$string['environment:production'] = 'Production (Live)';

$string['merchantid'] = 'Merchant ID';
$string['merchantid_help'] = 'Your PayU merchant ID. Found in the PayU dashboard.';
$string['accountid'] = 'Account ID';
$string['accountid_help'] = 'Your PayU account ID for Colombia. Found in the PayU dashboard.';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'Your PayU API Key. Found in the technical configuration of the PayU dashboard.';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Your PayU API Login. Found in the technical configuration of the PayU dashboard.';

$string['invalidmerchantid'] = 'Merchant ID must be numeric';
$string['invalidaccountid'] = 'Account ID must be numeric';

$string['language'] = 'Payment page language';
$string['language:es'] = 'Spanish';
$string['language:en'] = 'English';

$string['collectcustomerdata'] = 'Collect customer data';
$string['collectcustomerdata_desc'] = 'Pre-fill customer information (email, name) in the payment form.';

$string['callback_urls'] = 'PayU Configuration URLs';
$string['confirmationurl'] = 'Confirmation URL';
$string['responseurl'] = 'Response URL';

$string['sandbox_notice'] = '<strong>Note:</strong> In Sandbox environment, PayU test credentials are automatically used. You do not need to enter credentials.';

// Payment process.
$string['redirecting'] = 'Redirecting to PayU...';
$string['redirecting_message'] = 'You are being redirected to PayU\'s secure payment page. Please wait...';
$string['reference'] = 'Reference';
$string['javascript_required'] = 'JavaScript is required for automatic payment.';
$string['continue_to_payu'] = 'Continue to PayU';

// Payment states.
$string['paymentsuccessful'] = 'Payment successful! Your enrollment has been processed.';
$string['paymentpending'] = 'Your payment is being processed. You will be notified once confirmed.';
$string['paymentdeclined'] = 'Your payment was declined. Please try again or use another payment method.';
$string['paymentexpired'] = 'The payment has expired. Please try again.';
$string['paymenterror'] = 'An error occurred processing your payment. Please try again.';
$string['paymentunknownstatus'] = 'Unknown payment status. Please contact support.';
$string['signatureinvalid'] = '(Warning: Could not verify signature)';

// Errors.
$string['error_transaction_create'] = 'Error creating transaction in database.';

// Notification messages.
$string['messageprovider:payment_successful'] = 'Payment successful confirmation';
$string['messageprovider:payment_failed'] = 'Payment failed notification';
$string['messageprovider:payment_pending'] = 'Payment pending notification';

$string['payment:successful:subject'] = 'Payment successful';
$string['payment:successful:message'] = 'Your payment was successful. You can now access your content at: {$a->url}';
$string['payment:failed:subject'] = 'Payment failed';
$string['payment:failed:message'] = 'Your payment could not be processed. Please try again or use another payment method.';
$string['payment:pending:subject'] = 'Payment pending';
$string['payment:pending:message'] = 'Your payment is being processed. You will be notified once confirmed.';

// Privacy.
$string['privacy:metadata:paygw_payu_transactions'] = 'Stores transaction data for PayU payments.';
$string['privacy:metadata:paygw_payu_transactions:userid'] = 'The ID of the user who made the payment.';
$string['privacy:metadata:paygw_payu_transactions:transactionid'] = 'The PayU transaction ID.';
$string['privacy:metadata:paygw_payu_transactions:referencecode'] = 'The unique payment reference.';
$string['privacy:metadata:paygw_payu_transactions:amount'] = 'The payment amount.';
$string['privacy:metadata:paygw_payu_transactions:state'] = 'The transaction state.';
$string['privacy:metadata:paygw_payu_transactions:timecreated'] = 'The time the transaction was created.';

$string['privacy:metadata:payu'] = 'To process payments, some user data is sent to PayU.';
$string['privacy:metadata:payu:email'] = 'Your email address.';
$string['privacy:metadata:payu:fullname'] = 'Your full name.';

// Scheduled tasks.
$string['task_check_pending'] = 'Check pending PayU transactions';
$string['task_cleanup_expired'] = 'Clean up expired PayU transactions';

// Status notifications.
$string['payment_notification'] = 'PayU payment notification';
$string['payment_approved'] = 'Your payment has been approved. You can now access your content.';
$string['payment_declined'] = 'Your payment has been declined. Please try a different payment method.';
$string['payment_expired_subject'] = 'Payment expired';
$string['payment_expired_message'] = 'Your payment of {$a->amount} {$a->currency} initiated on {$a->date} has expired due to lack of confirmation. If you wish to complete the purchase, please initiate a new payment.';
$string['messageprovider:payment_status'] = 'Payment status notifications';
