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
 * Strings for component 'paygw_payu', language 'en'
 *
 * @package     paygw_payu
 * @copyright   2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Plugin name and description
$string['pluginname'] = 'PayU Latin America';
$string['pluginname_desc'] = 'The PayU plugin allows you to receive payments via PayU platform for Latin American countries.';
$string['gatewayname'] = 'PayU';
$string['gatewaydescription'] = 'PayU is an authorized payment gateway provider for processing credit card transactions in Latin America.';

// Countries
$string['country'] = 'Country of operation';
$string['country_ar'] = 'Argentina';
$string['country_br'] = 'Brazil';
$string['country_cl'] = 'Chile';
$string['country_co'] = 'Colombia';
$string['country_mx'] = 'Mexico';
$string['country_pa'] = 'Panama';
$string['country_pe'] = 'Peru';

// Environment settings
$string['environment'] = 'Environment';
$string['environment_sandbox'] = 'Sandbox (Testing)';
$string['environment_production'] = 'Production (Real payments)';

// Credentials
$string['merchantid'] = 'Merchant ID';
$string['merchantid_help'] = 'Your PayU Merchant ID. Required for production environment.';
$string['accountid'] = 'Account ID';
$string['accountid_help'] = 'Your PayU Account ID for the selected country. Required for production environment.';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'Your PayU API Key. Keep this secure! Required for production environment.';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Your PayU API Login. Required for production environment.';
$string['publickey'] = 'Public Key';
$string['publickey_help'] = 'Your PayU Public Key for tokenization (optional).';

// Language settings
$string['language'] = 'Payment page language';
$string['language_es'] = 'Spanish';
$string['language_en'] = 'English';
$string['language_pt'] = 'Portuguese';

// Payment settings
$string['abouttopay'] = 'You are about to pay for';
$string['payment'] = 'Payment';
$string['sendpaymentbutton'] = 'Pay with PayU';
$string['redirecting'] = 'Redirecting to PayU...';
$string['redirecting_message'] = 'You are being redirected to PayU secure payment page. Please wait...';

// Status messages
$string['payment_success'] = 'Payment successful!';
$string['payment_error'] = 'Payment error';
$string['payment_declined'] = 'Payment was declined';
$string['payment_pending'] = 'Payment is pending approval';
$string['payment_expired'] = 'Payment expired';
$string['payment_unknown'] = 'Unknown payment status';
$string['signature_invalid'] = '(Warning: Invalid signature)';

// Test mode
$string['autofilltest'] = 'Auto-fill test data';
$string['autofilltest_help'] = 'Automatically fills test card data in sandbox mode for easier testing.';
$string['sandbox_note'] = '<strong>Note:</strong> When using Sandbox environment, test credentials will be used automatically. You don\'t need to enter production credentials.';

// Optional payment modes
$string['skipmode'] = 'Allow payment skip';
$string['skipmode_help'] = 'Shows a button to skip payment. Useful for optional payments in public courses.';
$string['skipmode_text'] = 'If you cannot make a payment through the payment system, you can click this button.';
$string['skippaymentbutton'] = 'Skip payment';

$string['passwordmode'] = 'Enable password bypass';
$string['password'] = 'Bypass password';
$string['password_help'] = 'Users can bypass payment using this password. Useful when payment system is unavailable.';
$string['password_text'] = 'If you cannot make a payment, ask your administrator for the password and enter it here.';
$string['password_error'] = 'Invalid payment password';
$string['password_success'] = 'Payment password accepted';
$string['password_required'] = 'Password is required when password mode is enabled';

// Cost settings
$string['fixcost'] = 'Fixed price mode';
$string['fixcost_help'] = 'Disables the ability for students to pay with a custom amount.';
$string['suggest'] = 'Suggested price';
$string['maxcost'] = 'Maximum cost';
$string['maxcosterror'] = 'The maximum price must be higher than the suggested price';
$string['paymore'] = 'If you want to pay more, simply enter your amount instead of the suggested amount.';

// URLs
$string['callback_urls'] = 'Configuration URLs';
$string['confirmation_url'] = 'Confirmation URL';
$string['response_url'] = 'Response URL';

// Errors
$string['error_txdatabase'] = 'Error writing transaction to database';
$string['error_notvalidtxid'] = 'Invalid transaction ID';
$string['error_notvalidpayment'] = 'Invalid payment';
$string['error_notvalidpaymentid'] = 'Invalid payment ID';
$string['production_fields_required'] = 'All credentials are required for production environment';

// Privacy
$string['privacy:metadata'] = 'The PayU plugin stores personal data to process payments.';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Store payment transaction data';
$string['privacy:metadata:paygw_payu:userid'] = 'User ID';
$string['privacy:metadata:paygw_payu:courseid'] = 'Course ID';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Group names';
$string['privacy:metadata:paygw_payu:country'] = 'Country of transaction';
$string['privacy:metadata:paygw_payu:transactionid'] = 'PayU transaction ID';
$string['privacy:metadata:paygw_payu:referencecode'] = 'Reference code';
$string['privacy:metadata:paygw_payu:amount'] = 'Payment amount';
$string['privacy:metadata:paygw_payu:currency'] = 'Currency';
$string['privacy:metadata:paygw_payu:state'] = 'Transaction state';

// Notifications
$string['messagesubject'] = 'Payment notification';
$string['messageprovider:payment_receipt'] = 'Payment receipt';
$string['message_payment_completed'] = 'Hello {$a->firstname},
Your payment of {$a->fee} {$a->currency} (ID: {$a->orderid}) has been successfully completed.
If you cannot access the course, please contact the administrator.';
$string['message_payment_pending'] = 'Hello {$a->firstname},
Your payment of {$a->fee} {$a->currency} (ID: {$a->orderid}) is pending approval.
We will notify you once the payment is confirmed.';