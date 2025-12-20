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
 * Strings for component 'paygw_epayco', language 'en'.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'ePayco';
$string['pluginname_desc'] = 'The ePayco plugin allows you to receive payments via ePayco payment gateway (Colombia).';
$string['gatewayname'] = 'ePayco';
$string['gatewaydescription'] = 'ePayco is a Colombian payment gateway that supports credit/debit cards, PSE, cash payments and more.';

// Configuration.
$string['environment'] = 'Environment';
$string['environment_help'] = 'Select the ePayco environment. Use Test for development and Production for real transactions.';
$string['environment:test'] = 'Test (Sandbox)';
$string['environment:production'] = 'Production (Live)';

$string['p_cust_id_cliente'] = 'P_CUST_ID_CLIENTE';
$string['p_cust_id_cliente_help'] = 'Your ePayco customer ID. Found in Settings > Integrations > API Keys in your ePayco dashboard.';
$string['p_key'] = 'P_KEY';
$string['p_key_help'] = 'Your ePayco P_KEY. Found in Settings > Integrations > API Keys in your ePayco dashboard.';
$string['public_key'] = 'PUBLIC_KEY';
$string['public_key_help'] = 'Your ePayco public API key. Found in Settings > Integrations > API Keys in your ePayco dashboard.';
$string['private_key'] = 'PRIVATE_KEY';
$string['private_key_help'] = 'Your ePayco private API key. Found in Settings > Integrations > API Keys in your ePayco dashboard.';

$string['language'] = 'Payment page language';
$string['language:es'] = 'Spanish';
$string['language:en'] = 'English';

$string['collectcustomerdata'] = 'Collect customer data';
$string['collectcustomerdata_desc'] = 'Pre-fill customer information (email, name) in the checkout form.';

$string['callback_urls'] = 'ePayco Configuration URLs';
$string['confirmationurl'] = 'Confirmation URL';
$string['responseurl'] = 'Response URL';

// Payment process.
$string['redirecting'] = 'Redirecting to ePayco...';
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
$string['signatureinvalid'] = 'Invalid transaction signature. Please contact support.';

// Messages.
$string['messageprovider:payment_failed'] = 'Failed payment notification';
$string['messageprovider:payment_successful'] = 'Successful payment confirmation';
$string['messageprovider:payment_pending'] = 'Pending payment notification';
$string['messageprovider:payment_status'] = 'Payment status notifications';

$string['payment:successful:subject'] = 'Payment successful';
$string['payment:successful:message'] = 'Your payment was successful. You can now access your content at: {$a->url}';
$string['payment:failed:subject'] = 'Payment failed';
$string['payment:failed:message'] = 'Your payment failed to process. Please try again or use a different payment method.';
$string['payment:pending:subject'] = 'Payment pending';
$string['payment:pending:message'] = 'Your payment is being processed. You will be notified once it is confirmed.';

// Privacy.
$string['privacy:metadata:paygw_epayco_transactions'] = 'Stores transaction data for ePayco payments.';
$string['privacy:metadata:paygw_epayco_transactions:userid'] = 'The ID of the user who made the payment.';
$string['privacy:metadata:paygw_epayco_transactions:transactionid'] = 'The ePayco transaction ID.';
$string['privacy:metadata:paygw_epayco_transactions:reference'] = 'The unique payment reference.';
$string['privacy:metadata:paygw_epayco_transactions:amount'] = 'The payment amount.';
$string['privacy:metadata:paygw_epayco_transactions:status'] = 'The transaction status.';
$string['privacy:metadata:paygw_epayco_transactions:timecreated'] = 'The time the transaction was created.';

$string['privacy:metadata:epayco'] = 'In order to process payments, some user data is sent to ePayco.';
$string['privacy:metadata:epayco:email'] = 'Your email address.';
$string['privacy:metadata:epayco:fullname'] = 'Your full name.';

// Scheduled tasks.
$string['task_check_pending'] = 'Check pending ePayco transactions';
$string['task_cleanup_expired'] = 'Clean up expired ePayco transactions';

// Status notifications.
$string['payment_notification'] = 'ePayco payment notification';
$string['payment_approved'] = 'Your payment has been approved. You can now access your content.';
$string['payment_declined'] = 'Your payment has been declined. Please try a different payment method.';
$string['payment_expired_subject'] = 'Payment expired';
$string['payment_expired_message'] = 'Your payment of {$a->amount} {$a->currency} initiated on {$a->date} has expired. If you wish to complete the purchase, please initiate a new payment.';

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

// Payment button and modal.
$string['paywitepayco'] = 'Pay with ePayco';
$string['amounttopay'] = 'Amount to pay';
$string['redirectepayco'] = 'You will be redirected to ePayco to complete your payment.';

// Response page.
$string['paymentresult'] = 'Payment Result';
$string['paymentapproved'] = 'Payment Approved';
$string['paymentapproved_desc'] = 'Your payment has been processed successfully. You can now access your content.';
$string['paymentpending_desc'] = 'Your payment is being processed. You will be notified once it is confirmed.';
$string['paymentrejected'] = 'Payment Rejected';
$string['paymentrejected_desc'] = 'Your payment was rejected. Please try again with a different payment method.';
$string['paymentfailed_desc'] = 'An error occurred during payment processing. Please try again.';
$string['transactiondetails'] = 'Transaction Details';
$string['refpayco'] = 'ePayco Reference';
$string['amount'] = 'Amount';

// Errors.
$string['error:noreference'] = 'No transaction reference was provided.';
$string['error:queryingepayco'] = 'Could not retrieve transaction information from ePayco. Please contact support.';

// Scheduled tasks.
$string['task:checkpendingtransactions'] = 'Check pending ePayco transactions';
$string['task:cleanupexpiredtransactions'] = 'Clean up expired ePayco transactions';

// Privacy metadata for additional fields.
$string['privacy:metadata:paygw_epayco_transactions:component'] = 'The component name.';
$string['privacy:metadata:paygw_epayco_transactions:paymentarea'] = 'The payment area.';
$string['privacy:metadata:paygw_epayco_transactions:itemid'] = 'The item ID.';
$string['privacy:metadata:paygw_epayco_transactions:ref_payco'] = 'The ePayco payment reference.';
$string['privacy:metadata:paygw_epayco_transactions:currency'] = 'The payment currency.';
$string['privacy:metadata:epayco:firstname'] = 'Your first name.';
$string['privacy:metadata:epayco:lastname'] = 'Your last name.';
$string['privacy:metadata:epayco:phone'] = 'Your phone number.';
$string['privacy:metadata:epayco:address'] = 'Your address.';
$string['privacy:metadata:epayco:city'] = 'Your city.';
