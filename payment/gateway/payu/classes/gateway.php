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
 * Contains class for PayU Colombia payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Example
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

use core_payment\form\account_gateway;

/**
 * The gateway class for the PayU Colombia payment gateway.
 */
class gateway extends \core_payment\gateway {
    /**
     * Return currencies supported by PayU.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['COP', 'USD'];
    }

    /**
     * Adds configuration fields to the gateway form.
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'merchantid', get_string('merchantid', 'paygw_payu'));
        $mform->setType('merchantid', PARAM_TEXT);
        $mform->addRule('merchantid', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'accountid', get_string('accountid', 'paygw_payu'));
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addRule('accountid', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'apilogin', get_string('apilogin', 'paygw_payu'));
        $mform->setType('apilogin', PARAM_TEXT);
        $mform->addRule('apilogin', get_string('required'), 'required', null, 'client');

        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_payu'));
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addRule('apikey', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'testmode', get_string('testmode', 'paygw_payu'));
        $mform->setType('testmode', PARAM_BOOL);
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors
     */
    public static function validate_gateway_form(account_gateway $form, \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled && (empty($data->merchantid) || empty($data->accountid) || empty($data->apilogin) || empty($data->apikey))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
    }
}
