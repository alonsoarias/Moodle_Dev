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
 * Web services for PayU payment gateway - COMPLETE implementation.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'paygw_payu_get_pse_banks' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'get_pse_banks',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Get list of PSE banks',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_get_payment_methods' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'get_payment_methods',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Get available payment methods',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_process_payment' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'process_payment',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Process payment transaction',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_get_airlines' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'get_airlines',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Get list of airlines for airline transactions',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_query_transaction' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'query_transaction',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Query transaction status',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_process_refund' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'process_refund',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Process refund for a transaction',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
    
    'paygw_payu_autofill_test_data' => [
        'classname' => 'paygw_payu_external',
        'methodname' => 'autofill_test_data',
        'classpath' => 'payment/gateway/payu/externallib.php',
        'description' => 'Auto-fill test data for sandbox mode',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];

$services = [
    'PayU payment service' => [
        'functions' => [
            'paygw_payu_get_pse_banks',
            'paygw_payu_get_payment_methods',
            'paygw_payu_process_payment',
            'paygw_payu_get_airlines',
            'paygw_payu_query_transaction',
            'paygw_payu_process_refund',
            'paygw_payu_autofill_test_data',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];