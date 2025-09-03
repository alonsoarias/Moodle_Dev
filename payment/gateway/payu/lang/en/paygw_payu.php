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
 * @copyright   2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author      Alonso Arias
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['abouttopay'] = 'You are about to pay for';
$string['accountid'] = 'PayU Account ID';
$string['apikey'] = 'API Key';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'API Login for PayU integrations (optional for web checkout)';
$string['callback'] = 'Confirmation URL:';
$string['callback_help'] = 'Copy this URL and configure it in your PayU account as the confirmation URL.';
$string['cost'] = 'Enrollment cost';
$string['currency'] = 'Currency';
$string['donate'] = '<div>Plugin version: {$a->release} ({$a->versiondisk})<br>
For support and updates, contact <a href="mailto:soporte@nexuslabs.com.co">NexusLabs</a><br>
Documentation: <a href="https://developers.payulatam.com">PayU Developer Portal</a></div>';
$string['error_notvalidpayment'] = 'FAIL. Not a valid payment';
$string['error_notvalidtxid'] = 'FAIL. Not a valid transaction id';
$string['error_txdatabase'] = 'Error writing transaction data to database';
$string['fixcost'] = 'Fixed price mode';
$string['fixcost_help'] = 'Disables the ability for students to pay with an arbitrary amount.';
$string['fixdesc'] = 'Fixed payment description';
$string['fixdesc_help'] = 'This setting sets a fixed description for all payments.';
$string['gatewaydescription'] = 'PayU is an authorized payment gateway provider for processing credit card transactions in Latin America.';
$string['gatewayname'] = 'PayU';
$string['internalerror'] = 'An internal error has occurred. Please contact us.';
$string['maxcost'] = 'Maximum cost';
$string['maxcosterror'] = 'The maximum price must be higher than the recommended price';
$string['merchantid'] = 'Merchant ID';
$string['message'] = 'Message';
$string['message_invoice_created'] = 'Hello {$a->firstname}!
Your payment link {$a->orderid} to {$a->fee} {$a->currency} has been successfully created.
You can pay it within an hour.';
$string['message_success_completed'] = 'Hello {$a->firstname},
Your transaction of payment id {$a->orderid} with cost of {$a->fee} {$a->currency} is successfully completed.
If the item is not accessible please contact the administrator.';
$string['messageprovider:payment_receipt'] = 'Payment receipt';
$string['messagesubject'] = 'Payment notification';
$string['password'] = 'Password';
$string['password_error'] = 'Invalid payment password';
$string['password_help'] = 'Using this password you can bypass the payment process. It can be useful when payment is not possible.';
$string['password_success'] = 'Payment password accepted';
$string['password_text'] = 'If you are unable to make a payment, then ask your administrator for a password and enter it.';
$string['passwordmode'] = 'Password mode';
$string['payment'] = 'Payment';
$string['payment_error'] = 'Payment Error';
$string['payment_success'] = 'Payment Successful';
$string['paymentdeclined'] = 'Your payment was declined by the payment processor.';
$string['paymentexpired'] = 'Payment Expired';
$string['paymentexpireddesc'] = 'Your payment session has expired. Please try again.';
$string['paymentpending'] = 'Payment Pending';
$string['paymentpendingdesc'] = 'Your payment is being processed. You will receive a confirmation once it is complete.';
$string['paymentresponse'] = 'Payment Response';
$string['paymentserver'] = 'Payment server URL';
$string['paymentsuccessful'] = 'Your payment has been successfully processed.';
$string['paymore'] = 'If you want to pay more, simply enter your amount instead of the indicated amount.';
$string['pluginname'] = 'PayU payment';
$string['pluginname_desc'] = 'The PayU plugin allows you to receive payments via PayU for Latin America.';
$string['privacy:metadata'] = 'The PayU plugin stores some personal data.';
$string['privacy:metadata:paygw_payu:courseid'] = 'Course id';
$string['privacy:metadata:paygw_payu:email'] = 'Email';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Group names';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Store some data';
$string['privacy:metadata:paygw_payu:payu_latam'] = 'Send payment data to PayU';
$string['privacy:metadata:paygw_payu:success'] = 'Status';
$string['publickey'] = 'Public Key';
$string['publickey_help'] = 'Public key for tokenization (optional for web checkout)';
$string['referencecode'] = 'Reference Code';
$string['sendpaymentbutton'] = 'Pay with PayU';
$string['showduration'] = 'Show duration of training';
$string['skipmode'] = 'Can skip payment';
$string['skipmode_help'] = 'This setting allows a payment bypass button, which can be useful in public courses with optional payment.';
$string['skipmode_text'] = 'If you are not able to make a payment through the payment system, you can click on this button.';
$string['skippaymentbutton'] = 'Skip payment :(';
$string['suggest'] = 'Suggested cost';
$string['testcredential_auto'] = 'Test credential (auto-filled)';
$string['testmode'] = 'Test mode';
$string['testmode_active'] = 'Test Mode Active';
$string['testmode_description'] = 'Using PayU Colombia sandbox credentials. Transactions will not be real.';
$string['testmode_help'] = 'Enable test mode to use PayU sandbox credentials automatically';
$string['transactionid'] = 'Transaction ID';
$string['uninterrupted_desc'] = 'The price for the course is formed taking into account the missed time of the period you have not paid for.';
$string['unknownstate'] = 'Unknown transaction state';
$string['usedetails'] = 'Make it collapsible';
$string['usedetails_help'] = 'Display a button or password in a collapsed block.';
$string['usedetails_text'] = 'Click here if you are unable to pay.';
$string['validationerror'] = 'Validation error';