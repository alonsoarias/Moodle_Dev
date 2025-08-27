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
 * Cache definitions for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Cache for PSE banks list.
    'psebanks' => [
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 86400, // 24 hours
        'simplekeys' => true,
        'simplevalues' => false,
    ],
    // Cache for payment methods.
    'paymentmethods' => [
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 86400, // 24 hours
        'simplekeys' => true,
        'simplevalues' => false,
    ],
    // Cache for airlines list.
    'airlines' => [
        'mode' => cache_store::MODE_APPLICATION,
        'ttl' => 604800, // 7 days
        'simplekeys' => true,
        'simplevalues' => false,
    ],
];