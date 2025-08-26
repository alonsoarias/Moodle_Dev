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
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PayU Colombia';
$string['pluginname_desc'] = 'The PayU Colombia payment gateway enables online payments through various methods including credit cards, PSE bank transfers, Nequi, and cash payments.';
$string['gatewayname'] = 'PayU Colombia';
$string['gatewaydescription'] = 'PayU is a leading payment service provider in Latin America, processing secure online payments in Colombia.';

// Configuration strings.
$string['merchantid'] = 'Merchant ID';
$string['merchantid_help'] = 'Your PayU merchant identifier number.';
$string['accountid'] = 'Account ID';
$string['accountid_help'] = 'Your PayU account identifier for Colombia.';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'Your secret API key provided by PayU. Keep this secure!';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Your API login credential for PayU services.';
$string['testmode'] = 'Test mode';
$string['testmode_help'] = 'Enable test mode to use the PayU sandbox environment for testing payments.';

// Payment methods configuration.
$string['paymentmethods'] = 'Payment Methods Configuration';
$string['enabledmethods'] = 'Enabled payment methods';
$string['enabledmethods_help'] = 'Select which payment methods should be available to users. At least one method must be enabled.';

// Cache settings.
$string['cachesettings'] = 'Cache Settings';
$string['enablecache'] = 'Enable caching';
$string['enablecache_help'] = 'Enable caching of PSE bank lists and other data to improve performance.';

// Notification settings.
$string['notificationsettings'] = 'Notification Settings';
$string['enablenotifications'] = 'Enable email notifications';
$string['enablenotifications_help'] = 'Send email notifications to users about their payment status.';

// Callback URL.
$string['callbackurl'] = 'Callback URL';
$string['callbackurl_help'] = 'Configure this URL in your PayU account for payment confirmations. Copy this URL to the confirmation URL field in your PayU merchant panel.';

// Payment methods.
$string['paymentmethod'] = 'Payment method';
$string['creditcard'] = 'Credit/Debit card';
$string['pse'] = 'PSE - Bank transfer';
$string['nequi'] = 'Nequi';
$string['bancolombia'] = 'Bancolombia button';
$string['googlepay'] = 'Google Pay';
$string['cash'] = 'Cash payment';

// Form fields.
$string['cardholder'] = 'Cardholder name';
$string['cardnumber'] = 'Card number';
$string['expmonth'] = 'Expiry month';
$string['expyear'] = 'Expiry year';
$string['cvv'] = 'Security code (CVV)';
$string['cardnetwork'] = 'Card type';
$string['installments'] = 'Installments';
$string['documenttype'] = 'Document type';
$string['documentnumber'] = 'Document number';
$string['phone'] = 'Phone number';
$string['email'] = 'Email address';
$string['psebank'] = 'Select your bank';
$string['usertype'] = 'Person type';
$string['personnatural'] = 'Natural person';
$string['personjuridica'] = 'Legal entity';
$string['cashmethod'] = 'Cash payment location';
$string['efecty'] = 'Efecty';
$string['otherscash'] = 'Su Red (Others)';
$string['bankreferenced'] = 'Bank reference';
$string['googlepaytoken'] = 'Google Pay token';

// Address fields.
$string['street1'] = 'Address line 1';
$string['street2'] = 'Address line 2';
$string['city'] = 'City';
$string['state'] = 'State/Department';
$string['postalcode'] = 'Postal code';

// Buttons and actions.
$string['submitpayment'] = 'Process payment';
$string['processingpayment'] = 'Processing your payment...';
$string['continuetopayment'] = 'Continue to payment';
$string['returntocourse'] = 'Return to course';
$string['viewreceipt'] = 'View receipt';
$string['viewpayment'] = 'View payment details';

// Messages and notifications.
$string['messagesubject_payment_receipt'] = 'Payment Receipt - PayU';
$string['messagesubject_payment_pending'] = 'Payment Pending - PayU';
$string['messagesubject_payment_error'] = 'Payment Error - PayU';
$string['messagesubject_cashreminder'] = 'Cash Payment Reminder - PayU';

$string['message_payment_success'] = 'Dear {$a->fullname},

Your payment of {$a->amount} has been successfully processed.

Payment ID: {$a->paymentid}
Transaction ID: {$a->transactionid}
Payment Method: {$a->paymentmethod}
Date: {$a->date}

Thank you for your payment.';

$string['message_payment_pending'] = 'Dear {$a->fullname},

Your payment of {$a->amount} is currently pending.

Payment ID: {$a->paymentid}
Status: {$a->state}
Date: {$a->date}

We will notify you once the payment is confirmed.';

$string['message_payment_error'] = 'Dear {$a->fullname},

There was an error processing your payment of {$a->amount}.

Payment ID: {$a->paymentid}
Status: {$a->state}
Date: {$a->date}

Please try again or contact support if the problem persists.';

$string['message_cash_reminder'] = 'Dear {$a->fullname},

This is a reminder about your pending cash payment.

Amount: {$a->amount}
Reference: {$a->reference}
Expiration date: {$a->expirationdate}

Please complete your payment at any authorized location. You can view and print your payment receipt here:
{$a->receipturl}';

// Errors.
$string['merchantidinvalid'] = 'Merchant ID must be numeric.';
$string['accountidinvalid'] = 'Account ID must be numeric.';
$string['atleastonemethodrequired'] = 'At least one payment method must be enabled.';
$string['errorgetbanks'] = 'Error retrieving bank list: {$a}';
$string['errorgetmethods'] = 'Error retrieving payment methods: {$a}';
$string['errortransaction'] = 'Transaction error: {$a}';
$string['errorquerytransaction'] = 'Error querying transaction: {$a}';
$string['errorcurlconnection'] = 'Connection error: {$a}';
$string['errorhttpcode'] = 'HTTP error code: {$a}';
$string['errorjsonparse'] = 'Error parsing response from PayU.';
$string['paymenterror'] = 'Unable to process payment. Please try again.';
$string['paymentpending'] = 'Your payment is being processed. You will receive a confirmation soon.';
$string['invalidphone'] = 'Invalid phone number format. Must be 10 digits.';
$string['invalidsignature'] = 'Invalid payment signature.';
$string['invalidmerchant'] = 'Invalid merchant configuration.';
$string['invalidreference'] = 'Invalid payment reference.';

// Privacy.
$string['privacy:metadata:paygw_payu:payu'] = 'Information sent to PayU for payment processing.';
$string['privacy:metadata:paygw_payu:payu:fullname'] = 'The full name of the user making the payment.';
$string['privacy:metadata:paygw_payu:payu:email'] = 'The email address of the user.';
$string['privacy:metadata:paygw_payu:payu:phone'] = 'The phone number provided for the payment.';
$string['privacy:metadata:paygw_payu:payu:documentnumber'] = 'The identification document number.';
$string['privacy:metadata:paygw_payu:payu:address'] = 'The billing or shipping address.';
$string['privacy:metadata:paygw_payu:payu:creditcard'] = 'Credit card information (transmitted securely to PayU).';
$string['privacy:metadata:paygw_payu:payu:amount'] = 'The payment amount.';
$string['privacy:metadata:paygw_payu:payu:currency'] = 'The payment currency.';

$string['privacy:metadata:paygw_payu:database'] = 'Transaction records stored locally.';
$string['privacy:metadata:paygw_payu:database:paymentid'] = 'The internal payment ID.';
$string['privacy:metadata:paygw_payu:database:payu_order_id'] = 'The PayU order identifier.';
$string['privacy:metadata:paygw_payu:database:payu_transaction_id'] = 'The PayU transaction identifier.';
$string['privacy:metadata:paygw_payu:database:state'] = 'The transaction state.';
$string['privacy:metadata:paygw_payu:database:payment_method'] = 'The payment method used.';
$string['privacy:metadata:paygw_payu:database:amount'] = 'The transaction amount.';
$string['privacy:metadata:paygw_payu:database:currency'] = 'The transaction currency.';
$string['privacy:metadata:paygw_payu:database:timecreated'] = 'When the transaction was created.';

// Payment instructions.
$string['instruction_pse'] = 'You will be redirected to your bank\'s secure website to complete the payment.';
$string['instruction_nequi'] = 'You will receive a push notification on your Nequi app to authorize the payment.';
$string['instruction_cash'] = 'Print or save the payment receipt and pay at any authorized location.';
$string['instruction_bancolombia'] = 'You will be redirected to Bancolombia to complete the payment.';