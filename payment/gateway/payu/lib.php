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
 * Library functions for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve the files from the PayU payment gateway.
 *
 * @param stdClass $course The course object
 * @param stdClass $cm The course module object
 * @param context $context The context
 * @param string $filearea The file area
 * @param array $args The file arguments
 * @param bool $forcedownload Force download
 * @param array $options Additional options
 * @return bool
 */
function paygw_payu_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    return false;
}

/**
 * Extend navigation for PayU payment gateway.
 *
 * @param navigation_node $navigation The navigation node
 * @param stdClass $course The course
 * @param context $context The context
 * @return void
 */
function paygw_payu_extend_navigation($navigation, $course, $context) {
    // Not used yet.
}

/**
 * Check if PayU gateway supports a given currency.
 *
 * @param string $currency Currency code
 * @return bool
 */
function paygw_payu_supports_currency($currency) {
    return in_array($currency, ['COP', 'USD']);
}

/**
 * Get PayU payment methods for a given country.
 *
 * @param string $country Country code (default CO for Colombia)
 * @return array
 */
function paygw_payu_get_payment_methods($country = 'CO') {
    $methods = [];
    
    switch ($country) {
        case 'CO':
            $methods = [
                'VISA' => get_string('visa', 'paygw_payu'),
                'MASTERCARD' => get_string('mastercard', 'paygw_payu'),
                'AMEX' => get_string('amex', 'paygw_payu'),
                'DINERS' => get_string('diners', 'paygw_payu'),
                'PSE' => get_string('pse', 'paygw_payu'),
                'NEQUI' => get_string('nequi', 'paygw_payu'),
                'BANCOLOMBIA_TRANSFER' => get_string('bancolombia', 'paygw_payu'),
                'GOOGLEPAY' => get_string('googlepay', 'paygw_payu'),
                'EFECTY' => get_string('efecty', 'paygw_payu'),
                'BALOTO' => get_string('baloto', 'paygw_payu'),
                'BANK_REFERENCED' => get_string('bankreferenced', 'paygw_payu'),
            ];
            break;
        default:
            // Other countries can be added here
            break;
    }
    
    return $methods;
}

/**
 * Format amount for PayU API.
 *
 * @param float $amount Amount to format
 * @param string $currency Currency code
 * @return string Formatted amount
 */
function paygw_payu_format_amount($amount, $currency = 'COP') {
    // PayU requires amounts with specific decimal places
    switch ($currency) {
        case 'COP':
            // Colombian peso - no decimals
            return number_format($amount, 0, '.', '');
        default:
            // Other currencies - 2 decimals
            return number_format($amount, 2, '.', '');
    }
}

/**
 * Validate Colombian phone number.
 *
 * @param string $phone Phone number
 * @return bool
 */
function paygw_payu_validate_phone($phone) {
    // Remove non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Colombian mobile numbers start with 3 and have 10 digits
    if (strlen($phone) === 10 && substr($phone, 0, 1) === '3') {
        return true;
    }
    
    // Allow landlines with area code (10 digits total)
    if (strlen($phone) === 10) {
        return true;
    }
    
    return false;
}

/**
 * Validate document number for Colombia.
 *
 * @param string $document Document number
 * @param string $type Document type (CC, CE, NIT, etc.)
 * @return bool
 */
function paygw_payu_validate_document($document, $type = 'CC') {
    // Remove special characters
    $document = preg_replace('/[^A-Za-z0-9]/', '', $document);
    
    switch ($type) {
        case 'CC': // Cédula de Ciudadanía
        case 'CE': // Cédula de Extranjería
            // Should be numeric, 6-10 digits
            if (preg_match('/^[0-9]{6,10}$/', $document)) {
                return true;
            }
            break;
        case 'NIT': // NIT
            // NIT can have 9-10 digits, sometimes with verification digit
            if (preg_match('/^[0-9]{9,10}$/', $document)) {
                return true;
            }
            break;
        case 'PP': // Passport
            // Passport can have letters and numbers
            if (strlen($document) >= 6 && strlen($document) <= 20) {
                return true;
            }
            break;
        case 'TI': // Tarjeta de Identidad
            // Should be numeric, 10-11 digits
            if (preg_match('/^[0-9]{10,11}$/', $document)) {
                return true;
            }
            break;
    }
    
    return false;
}

/**
 * Map PayU response codes to user-friendly messages.
 *
 * @param string $code Response code from PayU
 * @return string User-friendly message
 */
function paygw_payu_get_response_message($code) {
    $messages = [
        'APPROVED' => get_string('response_approved', 'paygw_payu'),
        'PAYMENT_NETWORK_REJECTED' => get_string('response_network_rejected', 'paygw_payu'),
        'ENTITY_DECLINED' => get_string('response_entity_declined', 'paygw_payu'),
        'INSUFFICIENT_FUNDS' => get_string('response_insufficient_funds', 'paygw_payu'),
        'INVALID_CARD' => get_string('response_invalid_card', 'paygw_payu'),
        'CONTACT_THE_ENTITY' => get_string('response_contact_entity', 'paygw_payu'),
        'EXPIRED_CARD' => get_string('response_expired_card', 'paygw_payu'),
        'RESTRICTED_CARD' => get_string('response_restricted_card', 'paygw_payu'),
        'INVALID_EXPIRY_DATE_OR_SECURITY_CODE' => get_string('response_invalid_expiry_cvv', 'paygw_payu'),
        'INVALID_RESPONSE_PARTIAL_APPROVAL' => get_string('response_partial_approval', 'paygw_payu'),
        'CREDIT_CARD_NOT_AUTHORIZED_FOR_INTERNET_TRANSACTIONS' => get_string('response_not_authorized_internet', 'paygw_payu'),
        'ANTIFRAUD_REJECTED' => get_string('response_antifraud_rejected', 'paygw_payu'),
        'DIGITAL_CERTIFICATE_NOT_FOUND' => get_string('response_certificate_not_found', 'paygw_payu'),
        'BANK_UNREACHABLE' => get_string('response_bank_unreachable', 'paygw_payu'),
        'PAYMENT_TIME_EXPIRED' => get_string('response_time_expired', 'paygw_payu'),
        'PENDING_TRANSACTION_REVIEW' => get_string('response_pending_review', 'paygw_payu'),
        'ERROR' => get_string('response_error', 'paygw_payu'),
    ];
    
    return $messages[$code] ?? get_string('response_unknown', 'paygw_payu');
}

/**
 * Get list of test credit cards for sandbox mode.
 *
 * @return array
 */
function paygw_payu_get_test_cards() {
    return [
        'VISA' => [
            'number' => '4111111111111111',
            'cvv' => '123',
            'expMonth' => '12',
            'expYear' => '2030',
            'name' => 'APPROVED',
        ],
        'MASTERCARD' => [
            'number' => '5424000000000015',
            'cvv' => '123',
            'expMonth' => '12',
            'expYear' => '2030',
            'name' => 'APPROVED',
        ],
        'AMEX' => [
            'number' => '370000000000002',
            'cvv' => '1234',
            'expMonth' => '12',
            'expYear' => '2030',
            'name' => 'APPROVED',
        ],
        'DINERS' => [
            'number' => '36018623456787',
            'cvv' => '123',
            'expMonth' => '12',
            'expYear' => '2030',
            'name' => 'APPROVED',
        ],
    ];
}