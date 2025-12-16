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
 * Contains class for Wompi payment gateway.
 *
 * @package    paygw_wompi
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_wompi;

use core_payment\form\account_gateway;

/**
 * The gateway class for Wompi payment gateway.
 *
 * @copyright  2024 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * The list of currencies supported by Wompi Colombia.
     * Currently only COP (Colombian Pesos) is supported.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['COP'];
    }

    /**
     * Configuration form for the gateway instance.
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance.
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        // Environment selection.
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_wompi'), [
            'sandbox' => get_string('environment:sandbox', 'paygw_wompi'),
            'production' => get_string('environment:production', 'paygw_wompi'),
        ]);
        $mform->setType('environment', PARAM_TEXT);
        $mform->setDefault('environment', 'sandbox');
        $mform->addHelpButton('environment', 'environment', 'paygw_wompi');

        // Public Key.
        $mform->addElement('text', 'publickey', get_string('publickey', 'paygw_wompi'), ['size' => 60]);
        $mform->setType('publickey', PARAM_TEXT);
        $mform->addHelpButton('publickey', 'publickey', 'paygw_wompi');

        // Private Key.
        $mform->addElement('text', 'privatekey', get_string('privatekey', 'paygw_wompi'), ['size' => 60]);
        $mform->setType('privatekey', PARAM_TEXT);
        $mform->addHelpButton('privatekey', 'privatekey', 'paygw_wompi');

        // Events Private Key (for webhook signature verification).
        $mform->addElement('text', 'eventskey', get_string('eventskey', 'paygw_wompi'), ['size' => 60]);
        $mform->setType('eventskey', PARAM_TEXT);
        $mform->addHelpButton('eventskey', 'eventskey', 'paygw_wompi');

        // Integrity Key (for signature generation).
        $mform->addElement('text', 'integritykey', get_string('integritykey', 'paygw_wompi'), ['size' => 60]);
        $mform->setType('integritykey', PARAM_TEXT);
        $mform->addHelpButton('integritykey', 'integritykey', 'paygw_wompi');

        // Payment methods selection.
        $paymentmethods = [
            'CARD' => get_string('paymentmethod:card', 'paygw_wompi'),
            'NEQUI' => get_string('paymentmethod:nequi', 'paygw_wompi'),
            'PSE' => get_string('paymentmethod:pse', 'paygw_wompi'),
            'BANCOLOMBIA_TRANSFER' => get_string('paymentmethod:bancolombia_transfer', 'paygw_wompi'),
            'BANCOLOMBIA_COLLECT' => get_string('paymentmethod:bancolombia_collect', 'paygw_wompi'),
            'DAVIPLATA' => get_string('paymentmethod:daviplata', 'paygw_wompi'),
            'PCOL' => get_string('paymentmethod:pcol', 'paygw_wompi'),
        ];
        $method = $mform->addElement(
            'select',
            'paymentmethods',
            get_string('paymentmethods', 'paygw_wompi'),
            $paymentmethods
        );
        $mform->setType('paymentmethods', PARAM_TEXT);
        $mform->setDefault('paymentmethods', ['CARD', 'NEQUI', 'PSE']);
        $method->setMultiple(true);
        $mform->addHelpButton('paymentmethods', 'paymentmethods', 'paygw_wompi');

        // Customer data collection.
        $mform->addElement(
            'advcheckbox',
            'collectcustomerdata',
            get_string('collectcustomerdata', 'paygw_wompi'),
            get_string('collectcustomerdata_desc', 'paygw_wompi')
        );
        $mform->setDefault('collectcustomerdata', true);
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors Form errors (passed by reference).
     */
    public static function validate_gateway_form(
        account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        if ($data->enabled &&
            (empty($data->publickey) || empty($data->privatekey) || empty($data->integritykey))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }

        // Validate public key format.
        if (!empty($data->publickey)) {
            if ($data->environment === 'sandbox' && strpos($data->publickey, 'pub_test_') !== 0) {
                $errors['publickey'] = get_string('invalidpublickey:sandbox', 'paygw_wompi');
            } else if ($data->environment === 'production' && strpos($data->publickey, 'pub_prod_') !== 0) {
                $errors['publickey'] = get_string('invalidpublickey:production', 'paygw_wompi');
            }
        }

        // Validate private key format.
        if (!empty($data->privatekey)) {
            if ($data->environment === 'sandbox' && strpos($data->privatekey, 'prv_test_') !== 0) {
                $errors['privatekey'] = get_string('invalidprivatekey:sandbox', 'paygw_wompi');
            } else if ($data->environment === 'production' && strpos($data->privatekey, 'prv_prod_') !== 0) {
                $errors['privatekey'] = get_string('invalidprivatekey:production', 'paygw_wompi');
            }
        }
    }

    /**
     * Get the Wompi API base URL based on environment.
     *
     * @param string $environment 'sandbox' or 'production'
     * @return string
     */
    public static function get_api_url(string $environment): string {
        if ($environment === 'production') {
            return 'https://production.wompi.co/v1';
        }
        return 'https://sandbox.wompi.co/v1';
    }

    /**
     * Get the Wompi checkout URL.
     *
     * @return string
     */
    public static function get_checkout_url(): string {
        return 'https://checkout.wompi.co';
    }
}
