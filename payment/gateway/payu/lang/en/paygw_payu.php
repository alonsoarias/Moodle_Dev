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
 * Strings for component 'paygw_payu', language 'en' - COMPLETE.
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
$string['payuaccountid'] = 'Account ID';
$string['accountid'] = 'Account ID';
$string['accountid_help'] = 'Your PayU account identifier for Colombia.';
$string['apikey'] = 'API Key';
$string['apikey_help'] = 'Your secret API key provided by PayU. Keep this secure!';
$string['apilogin'] = 'API Login';
$string['apilogin_help'] = 'Your API login credential for PayU services.';
$string['testmode'] = 'Test mode';
$string['testmode_help'] = 'Enable test mode to use the PayU sandbox environment for testing payments.';
$string['debugmode'] = 'Debug mode';
$string['debugmode_help'] = 'Enable debug mode to log detailed transaction information for troubleshooting.';

// Payment methods configuration.
$string['paymentmethods'] = 'Payment Methods Configuration';
$string['enabledmethods'] = 'Enabled payment methods';
$string['enabledmethods_help'] = 'Select which payment methods should be available to users. At least one method must be enabled.';

// Payment methods.
$string['creditcard'] = 'Credit/Debit Card';
$string['visa'] = 'Visa';
$string['mastercard'] = 'Mastercard';
$string['amex'] = 'American Express';
$string['diners'] = 'Diners Club';
$string['pse'] = 'PSE - Bank Transfer';
$string['nequi'] = 'Nequi';
$string['bancolombia'] = 'Bancolombia Button';
$string['googlepay'] = 'Google Pay';
$string['cash'] = 'Cash Payment';
$string['efecty'] = 'Efecty';
$string['baloto'] = 'Baloto';
$string['bankreferenced'] = 'Bank Referenced';
$string['otherscash'] = 'Other Cash Methods';

// Form fields.
$string['paymentmethod'] = 'Payment Method';
$string['choosepaymentmethod'] = 'Choose a payment method';
$string['cardholder'] = 'Cardholder Name';
$string['cardnumber'] = 'Card Number';
$string['expmonth'] = 'Expiration Month';
$string['expyear'] = 'Expiration Year';
$string['cvv'] = 'CVV';
$string['cardnetwork'] = 'Card Network';
$string['phone'] = 'Phone Number';
$string['documentnumber'] = 'Document Number';
$string['documenttype'] = 'Document Type';
$string['usertype'] = 'Person Type';
$string['personnatural'] = 'Natural Person';
$string['personjuridica'] = 'Legal Entity';
$string['psebank'] = 'Select Your Bank';
$string['selectbank'] = 'Select a bank...';
$string['cashmethod'] = 'Cash Payment Method';
$string['googlepaytoken'] = 'Google Pay Token';

// Transaction fields.
$string['reference'] = 'Reference';
$string['transactionid'] = 'Transaction ID';
$string['orderid'] = 'Order ID';
$string['amount'] = 'Amount';
$string['status'] = 'Status';
$string['date'] = 'Date';
$string['description'] = 'Description';
$string['for'] = 'For';

// Instructions and messages.
$string['instruction_creditcard'] = 'Enter your credit or debit card information to complete the payment.';
$string['instruction_pse'] = 'You will be redirected to your bank\'s website to complete the payment securely.';
$string['instruction_nequi'] = 'You will receive a push notification in your Nequi app to authorize the payment.';
$string['instruction_bancolombia'] = 'You will be redirected to Bancolombia to complete the payment.';
$string['instruction_googlepay'] = 'Complete the payment using your saved Google Pay cards.';
$string['instruction_cash'] = 'Print or save the payment receipt and pay at any authorized location.';

// Cash payment instructions.
$string['cash_instructions_efecty'] = 'Present this receipt at any Efecty location nationwide to complete your payment.';
$string['cash_instructions_baloto'] = 'Pay at any Baloto authorized location using the reference number provided.';
$string['cash_instructions_bank_referenced'] = 'Use the reference number to pay at any bank branch or through online banking.';
$string['cash_instructions_others_cash'] = 'Complete your payment at any authorized payment location using the reference number.';

// Status messages.
$string['paymentsuccess'] = 'Payment Successful';
$string['paymentpending'] = 'Payment Pending';
$string['paymenterror'] = 'Payment Error';
$string['paymentdeclined'] = 'Payment Declined';
$string['paymentexpired'] = 'Payment Expired';
$string['paymentcancelled'] = 'Payment Cancelled';

// Response messages.
$string['response_approved'] = 'Transaction approved successfully.';
$string['response_network_rejected'] = 'Transaction rejected by payment network.';
$string['response_entity_declined'] = 'Transaction declined by the bank.';
$string['response_insufficient_funds'] = 'Insufficient funds in the account.';
$string['response_invalid_card'] = 'Invalid card number.';
$string['response_contact_entity'] = 'Please contact your bank.';
$string['response_expired_card'] = 'The card has expired.';
$string['response_restricted_card'] = 'The card is restricted.';
$string['response_invalid_expiry_cvv'] = 'Invalid expiration date or security code.';
$string['response_partial_approval'] = 'Partial approval received.';
$string['response_not_authorized_internet'] = 'Card not authorized for internet transactions.';
$string['response_antifraud_rejected'] = 'Transaction rejected by antifraud system.';
$string['response_certificate_not_found'] = 'Digital certificate not found.';
$string['response_bank_unreachable'] = 'Unable to connect to the bank.';
$string['response_time_expired'] = 'Transaction time expired.';
$string['response_pending_review'] = 'Transaction pending review.';
$string['response_error'] = 'An error occurred processing the payment.';
$string['response_unknown'] = 'Unknown response from payment processor.';

// Actions.
$string['submitpayment'] = 'Submit Payment';
$string['processingpayment'] = 'Processing payment...';
$string['tryagain'] = 'Try Again';
$string['viewreceipt'] = 'View Receipt';
$string['viewhtmlreceipt'] = 'View Online Receipt';
$string['downloadpdfreceipt'] = 'Download PDF Receipt';
$string['gotobanksite'] = 'Go to Bank Website';
$string['backtohome'] = 'Back to Home';
$string['continue'] = 'Continue';

// Additional UI strings.
$string['paymentdetails'] = 'Payment Details';
$string['ordersummary'] = 'Order Summary';
$string['paymentreceipt'] = 'Payment Receipt';
$string['paymentinstructions'] = 'Payment Instructions';
$string['pseinstructions'] = 'PSE Bank Transfer';
$string['expirationdate'] = 'Expiration Date';
$string['loadingpaymentgateway'] = 'Loading payment gateway...';

// Status badges.
$string['statusbadge_APPROVED'] = 'success';
$string['statusbadge_PENDING'] = 'warning';
$string['statusbadge_DECLINED'] = 'danger';
$string['statusbadge_ERROR'] = 'danger';
$string['statusbadge_EXPIRED'] = 'secondary';

// Cache settings.
$string['cachesettings'] = 'Cache Settings';
$string['enablecache'] = 'Enable cache';
$string['enablecache_help'] = 'Cache PSE bank lists and payment methods to improve performance.';
$string['cachettl'] = 'Cache time to live';
$string['cachettl_help'] = 'How long to cache data in seconds (default: 86400 = 24 hours).';

// Notification settings.
$string['notificationsettings'] = 'Notification Settings';
$string['enablenotifications'] = 'Enable notifications';
$string['enablenotifications_help'] = 'Send email notifications to users about their payment status.';

// Callback settings.
$string['callbackurl'] = 'Callback URL';
$string['callbackurl_help'] = 'Configure this URL in your PayU merchant panel for payment notifications.';

// Privacy.
$string['privacy:metadata:paygw_payu:payu'] = 'Information sent to PayU for payment processing.';
$string['privacy:metadata:paygw_payu:payu:fullname'] = 'The full name of the user making the payment.';
$string['privacy:metadata:paygw_payu:payu:email'] = 'The email address of the user.';
$string['privacy:metadata:paygw_payu:payu:phone'] = 'The phone number provided for the transaction.';
$string['privacy:metadata:paygw_payu:payu:documentnumber'] = 'The document number provided for identification.';
$string['privacy:metadata:paygw_payu:payu:address'] = 'The billing address of the user.';
$string['privacy:metadata:paygw_payu:payu:creditcard'] = 'Credit card information for processing (tokenized).';
$string['privacy:metadata:paygw_payu:payu:amount'] = 'The amount of the payment.';
$string['privacy:metadata:paygw_payu:payu:currency'] = 'The currency of the payment.';

$string['privacy:metadata:paygw_payu:database'] = 'Information about PayU transactions stored locally.';
$string['privacy:metadata:paygw_payu:database:paymentid'] = 'The internal payment ID.';
$string['privacy:metadata:paygw_payu:database:payu_order_id'] = 'The PayU order identifier.';
$string['privacy:metadata:paygw_payu:database:payu_transaction_id'] = 'The PayU transaction identifier.';
$string['privacy:metadata:paygw_payu:database:state'] = 'The transaction state.';
$string['privacy:metadata:paygw_payu:database:payment_method'] = 'The payment method used.';
$string['privacy:metadata:paygw_payu:database:amount'] = 'The transaction amount.';
$string['privacy:metadata:paygw_payu:database:currency'] = 'The transaction currency.';
$string['privacy:metadata:paygw_payu:database:timecreated'] = 'When the transaction was created.';

// Messages for notifications.
$string['messagesubject_payment_receipt'] = 'Payment Receipt - PayU';
$string['messagesubject_payment_pending'] = 'Payment Pending - PayU';
$string['messagesubject_payment_error'] = 'Payment Failed - PayU';
$string['messagesubject_cashreminder'] = 'Cash Payment Reminder - PayU';
$string['messageprovider:payment_receipt'] = 'Payment receipts';

$string['message_payment_success'] = 'Hello {$a->fullname},

Your payment of {$a->amount} has been successfully processed.

Transaction ID: {$a->transactionid}
Payment Method: {$a->paymentmethod}
Payment ID: {$a->paymentid}
Date: {$a->date}

Thank you for your payment.';

$string['message_payment_pending'] = 'Hello {$a->fullname},

Your payment of {$a->amount} is pending processing.

Transaction ID: {$a->transactionid}
Payment Method: {$a->paymentmethod}
Payment ID: {$a->paymentid}
Date: {$a->date}

We will notify you once the payment is confirmed.';

$string['message_payment_error'] = 'Hello {$a->fullname},

Your payment of {$a->amount} could not be processed.

Transaction ID: {$a->transactionid}
Payment Method: {$a->paymentmethod}
Payment ID: {$a->paymentid}
Date: {$a->date}

Please try again or contact support.';

$string['message_cash_reminder'] = 'Hello {$a->fullname},

This is a reminder about your pending cash payment.

Reference: {$a->reference}
Amount: {$a->amount}
Expiration Date: {$a->expirationdate}

Please complete your payment before the expiration date.';

// Errors.
$string['gatewaynotconfigured'] = 'Payment gateway is not properly configured.';
$string['currencynotsupported'] = 'Currency {$a} is not supported by PayU Colombia.';
$string['invalidpayment'] = 'Invalid payment record.';
$string['invaliduser'] = 'Invalid user for this payment.';
$string['invalidpaymentmethod'] = 'Invalid payment method selected.';
$string['invalidphone'] = 'Invalid phone number format.';
$string['invaliddocument'] = 'Invalid document number.';
$string['invalidcard'] = 'Invalid credit card information.';
$string['merchantidinvalid'] = 'Merchant ID must be numeric.';
$string['accountidinvalid'] = 'Account ID must be numeric.';
$string['atleastonemethodrequired'] = 'At least one payment method must be enabled.';
$string['transactionnotfound'] = 'Transaction not found.';
$string['missingparameters'] = 'Missing required parameters.';
$string['unknownstate'] = 'Unknown transaction state: {$a}';

// Test mode.
$string['testmodeactive'] = 'Test Mode Active';
$string['testmodewarning'] = 'You are using PayU in test mode. No real transactions will be processed.';

// Capabilities.
$string['paygw/payu:receivepaymentnotifications'] = 'Receive payment notifications';

// Error codes for API.
$string['errorconnection'] = 'Could not connect to PayU API.';
$string['errorcurlconnection'] = 'Network error: {$a}';
$string['errorhttpcode'] = 'HTTP error code: {$a}';
$string['errorjsonparse'] = 'Invalid response from PayU API.';
$string['errortransaction'] = 'Transaction error: {$a}';
$string['errorgetbanks'] = 'Could not retrieve bank list: {$a}';
$string['errorgetmethods'] = 'Could not retrieve payment methods: {$a}';
$string['errorgetairlines'] = 'Could not retrieve airline list: {$a}';
$string['errorrefund'] = 'Refund error: {$a}';
$string['errorquery'] = 'Query error: {$a}';