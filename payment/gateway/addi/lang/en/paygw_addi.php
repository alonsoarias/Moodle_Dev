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
 * Strings for component 'paygw_addi', language 'en'.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Addi';
$string['pluginname_desc'] = 'The Addi plugin allows you to receive payments via Addi Buy Now Pay Later (BNPL) service (Colombia).';
$string['gatewayname'] = 'Addi';
$string['gatewaydescription'] = 'Addi is a Colombian Buy Now Pay Later service that allows customers to split their payments into installments.';

// Configuration.
$string['environment'] = 'Environment';
$string['environment_help'] = 'Select the Addi environment. Use Sandbox for testing and Production for real transactions.';
$string['environment:sandbox'] = 'Sandbox (Test)';
$string['environment:production'] = 'Production (Live)';

$string['clientid'] = 'Client ID';
$string['clientid_help'] = 'Your Addi Client ID. Provided by Addi when you register as a merchant.';
$string['clientsecret'] = 'Client Secret';
$string['clientsecret_help'] = 'Your Addi Client Secret. Provided by Addi when you register as a merchant.';
$string['allyslug'] = 'Ally Slug';
$string['allyslug_help'] = 'Your merchant identifier (Ally Slug). Provided by Addi during integration.';

$string['minamount'] = 'Minimum amount (COP)';
$string['minamount_help'] = 'Minimum order amount allowed for Addi payments. Default is 50,000 COP.';
$string['maxamount'] = 'Maximum amount (COP)';
$string['maxamount_help'] = 'Maximum order amount allowed for Addi payments. Default is 5,000,000 COP.';
$string['invalidminamount'] = 'Minimum amount must be a positive number.';
$string['invalidmaxamount'] = 'Maximum amount must be a positive number.';
$string['maxmustbegreaterthanmin'] = 'Maximum amount must be greater than minimum amount.';

$string['callback_urls'] = 'Addi Configuration URLs';
$string['redirecturl'] = 'Redirect URL';
$string['webhookurl'] = 'Webhook URL';

// Payment process.
$string['redirecting'] = 'Redirecting to Addi...';
$string['clicktopay'] = 'Click the button below to pay with Addi';
$string['paynow'] = 'Pay with Addi';
$string['paywithaddi'] = 'Pay with Addi';
$string['reference'] = 'Reference';
$string['amounttopay'] = 'Amount to pay';
$string['redirectaddi'] = 'You will be redirected to Addi to complete your payment in installments.';

$string['amountbelowminimum'] = 'The amount ({$a->amount} {$a->currency}) is below the minimum allowed for Addi ({$a->min} {$a->currency}).';
$string['amountabovemaximum'] = 'The amount ({$a->amount} {$a->currency}) is above the maximum allowed for Addi ({$a->max} {$a->currency}).';

// Response page.
$string['paymentresult'] = 'Payment Result';
$string['paymentapproved'] = 'Payment Approved';
$string['paymentapproved_desc'] = 'Your Addi financing has been approved. You can now access your content.';
$string['paymentpending'] = 'Payment Pending';
$string['paymentpending_desc'] = 'Your Addi application is being processed. You will be notified once it is confirmed.';
$string['paymentrejected'] = 'Payment Rejected';
$string['paymentrejected_desc'] = 'Your Addi financing request was rejected. Please try a different payment method.';
$string['paymentfailed'] = 'Payment Failed';
$string['paymentfailed_desc'] = 'An error occurred during payment processing. Please try again.';
$string['paymentcancelled'] = 'Payment Cancelled';
$string['paymentcancelled_desc'] = 'You cancelled the Addi financing request.';
$string['transactiondetails'] = 'Transaction Details';
$string['applicationid'] = 'Addi Application ID';
$string['amount'] = 'Amount';

// Errors.
$string['error:noreference'] = 'No transaction reference was provided.';
$string['error:applicationfailed'] = 'Could not create Addi application. Please try again.';
$string['error:queryingaddi'] = 'Could not retrieve transaction information from Addi. Please contact support.';
$string['error:invalidwebhook'] = 'Invalid webhook signature.';

// Scheduled tasks.
$string['task:checkpendingtransactions'] = 'Check pending Addi transactions';
$string['task:cleanupexpiredtransactions'] = 'Clean up expired Addi transactions';

// Privacy metadata.
$string['privacy:metadata:paygw_addi_transactions'] = 'Stores transaction data for Addi payments.';
$string['privacy:metadata:paygw_addi_transactions:userid'] = 'The ID of the user who made the payment.';
$string['privacy:metadata:paygw_addi_transactions:component'] = 'The component name.';
$string['privacy:metadata:paygw_addi_transactions:paymentarea'] = 'The payment area.';
$string['privacy:metadata:paygw_addi_transactions:itemid'] = 'The item ID.';
$string['privacy:metadata:paygw_addi_transactions:reference'] = 'The unique payment reference.';
$string['privacy:metadata:paygw_addi_transactions:applicationid'] = 'The Addi application ID.';
$string['privacy:metadata:paygw_addi_transactions:amount'] = 'The payment amount.';
$string['privacy:metadata:paygw_addi_transactions:currency'] = 'The payment currency.';
$string['privacy:metadata:paygw_addi_transactions:status'] = 'The transaction status.';
$string['privacy:metadata:paygw_addi_transactions:timecreated'] = 'The time the transaction was created.';

$string['privacy:metadata:addi'] = 'In order to process payments, some user data is sent to Addi.';
$string['privacy:metadata:addi:email'] = 'Your email address.';
$string['privacy:metadata:addi:firstname'] = 'Your first name.';
$string['privacy:metadata:addi:lastname'] = 'Your last name.';
$string['privacy:metadata:addi:phone'] = 'Your phone number.';
$string['privacy:metadata:addi:address'] = 'Your address.';

// Messages.
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
$string['email_completed_subject_default'] = 'Addi Financing Approved - Order #{orderid}';
$string['email_completed_body'] = 'Payment Completed - Body';
$string['email_completed_body_default'] = 'Hello {firstname},

Your Addi financing for {amount} (Order ID: {orderid}) has been approved.

You can now access your course. Your payment will be split into convenient installments as agreed with Addi.

If you have any questions, please contact support.';

$string['email_pending_subject'] = 'Payment Pending - Subject';
$string['email_pending_subject_default'] = 'Addi Application Pending - Order #{orderid}';
$string['email_pending_body'] = 'Payment Pending - Body';
$string['email_pending_body_default'] = 'Hello {firstname},

Your Addi financing application for {amount} (Order ID: {orderid}) is being processed.

We will notify you once the application is approved.';
