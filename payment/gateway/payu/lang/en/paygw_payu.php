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
 * @copyright   2024 Your Organization
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['abouttopay'] = 'You are about to pay for';
$string['apikey'] = 'API Key';
$string['apilogin'] = 'API Login';
$string['apiurl'] = 'API URL';
$string['accountid'] = 'Account ID';
$string['all'] = 'All payment methods';
$string['callback'] = 'Callback URL:';
$string['callback_help'] = 'Copy this and configure it in your PayU account settings.';
$string['fixdesc'] = 'Fixed payment comment';
$string['fixdesc_help'] = 'This setting sets a fixed comment for all payments.';
$string['gatewaydescription'] = 'PayU is an authorized payment gateway provider for processing credit card transactions.';
$string['gatewayname'] = 'PayU';
$string['maxcost'] = 'Maximum cost';
$string['maxcosterror'] = 'The maximum price must be higher than the recommended price';
$string['merchantid'] = 'Merchant ID';
$string['password'] = 'Password';
$string['password_error'] = 'Invalid payment password';
$string['password_help'] = 'Using this password you can bypass the payment process. It can be useful when it is not possible to make a payment.';
$string['password_success'] = 'Payment password accepted';
$string['password_text'] = 'If you are unable to make a payment, then ask your administrator for a password and enter it.';
$string['passwordmode'] = 'Password mode';
$string['payment'] = 'Payment';
$string['payment_error'] = 'Payment Error';
$string['payment_success'] = 'Payment Successful';
$string['paymore'] = 'If you want to pay more, simply enter your amount instead of the indicated amount.';
$string['paymentsystem'] = 'Payment method';
$string['pluginname'] = 'PayU payment';
$string['pluginname_desc'] = 'The PayU plugin allows you to receive payments via PayU Latam.';
$string['sendpaymentbutton'] = 'Send payment via PayU';
$string['skipmode'] = 'Can skip payment';
$string['skipmode_help'] = 'This setting allows a payment bypass button, which can be useful in public courses with optional payment.';
$string['skipmode_text'] = 'If you are not able to make a payment through the payment system, you can click on this button.';
$string['skippaymentbutton'] = 'Skip payment';
$string['suggest'] = 'Suggested cost';
$string['showduration'] = 'Show duration of training';
$string['testmode'] = 'Test mode';
$string['usedetails'] = 'Make it collapsible';
$string['usedetails_help'] = 'Display a button or password in a collapsed block.';
$string['usedetails_text'] = 'Click here if you are unable to pay.';
$string['fixcost'] = 'Fixed price mode';
$string['fixcost_help'] = 'Disables the ability for students to pay with an arbitrary amount.';

$string['internalerror'] = 'An internal error has occurred. Please contact us.';

$string['privacy:metadata'] = 'The PayU plugin stores some personal data.';
$string['privacy:metadata:paygw_payu:paygw_payu'] = 'Store payment data';
$string['privacy:metadata:paygw_payu:merchantid'] = 'Merchant ID';
$string['privacy:metadata:paygw_payu:apikey'] = 'API Key';
$string['privacy:metadata:paygw_payu:email'] = 'Email';
$string['privacy:metadata:paygw_payu:payu_data'] = 'PayU transaction data';
$string['privacy:metadata:paygw_payu:transactionid'] = 'Transaction ID';
$string['privacy:metadata:paygw_payu:courseid'] = 'Course ID';
$string['privacy:metadata:paygw_payu:groupnames'] = 'Group names';
$string['privacy:metadata:paygw_payu:success'] = 'Payment status';

$string['messagesubject'] = 'Payment notification';
$string['message_success_completed'] = 'Hello {$a->firstname},
Your transaction with payment ID {$a->orderid} for {$a->fee} {$a->currency} has been successfully completed.
If the item is not accessible please contact the administrator.';
$string['messageprovider:payment_receipt'] = 'Payment receipt';

$string['error_txdatabase'] = 'Error writing transaction data to database';
$string['error_notvalidtxid'] = 'Not a valid transaction ID';
$string['error_notvalidpayment'] = 'Not a valid payment';

$string['uninterrupted_desc'] = 'The price for the course is formed taking into account the missed time of the period you have not paid for.';