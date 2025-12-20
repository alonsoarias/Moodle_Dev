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
 * Contains class for ePayco payment gateway.
 *
 * @package    paygw_epayco
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_epayco;

use core_payment\form\account_gateway;

/**
 * The gateway class for ePayco payment gateway.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * The list of currencies supported by ePayco Colombia.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        return ['COP', 'USD'];
    }

    /**
     * Configuration form for the gateway instance.
     *
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        // Environment selection.
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_epayco'), [
            'test' => get_string('environment:test', 'paygw_epayco'),
            'production' => get_string('environment:production', 'paygw_epayco'),
        ]);
        $mform->setType('environment', PARAM_TEXT);
        $mform->setDefault('environment', 'test');
        $mform->addHelpButton('environment', 'environment', 'paygw_epayco');

        // P_CUST_ID_CLIENTE.
        $mform->addElement('text', 'p_cust_id_cliente', get_string('p_cust_id_cliente', 'paygw_epayco'), ['size' => 30]);
        $mform->setType('p_cust_id_cliente', PARAM_TEXT);
        $mform->addHelpButton('p_cust_id_cliente', 'p_cust_id_cliente', 'paygw_epayco');
        $mform->addRule('p_cust_id_cliente', get_string('required'), 'required', null, 'client');

        // P_KEY.
        $mform->addElement('passwordunmask', 'p_key', get_string('p_key', 'paygw_epayco'), ['size' => 60]);
        $mform->setType('p_key', PARAM_TEXT);
        $mform->addHelpButton('p_key', 'p_key', 'paygw_epayco');
        $mform->addRule('p_key', get_string('required'), 'required', null, 'client');

        // PUBLIC_KEY.
        $mform->addElement('text', 'public_key', get_string('public_key', 'paygw_epayco'), ['size' => 60]);
        $mform->setType('public_key', PARAM_TEXT);
        $mform->addHelpButton('public_key', 'public_key', 'paygw_epayco');
        $mform->addRule('public_key', get_string('required'), 'required', null, 'client');

        // PRIVATE_KEY.
        $mform->addElement('passwordunmask', 'private_key', get_string('private_key', 'paygw_epayco'), ['size' => 60]);
        $mform->setType('private_key', PARAM_TEXT);
        $mform->addHelpButton('private_key', 'private_key', 'paygw_epayco');
        $mform->addRule('private_key', get_string('required'), 'required', null, 'client');

        // Language selection.
        $mform->addElement('select', 'language', get_string('language', 'paygw_epayco'), [
            'es' => get_string('language:es', 'paygw_epayco'),
            'en' => get_string('language:en', 'paygw_epayco'),
        ]);
        $mform->setType('language', PARAM_TEXT);
        $mform->setDefault('language', 'es');

        // Collect customer data.
        $mform->addElement(
            'advcheckbox',
            'collectcustomerdata',
            get_string('collectcustomerdata', 'paygw_epayco'),
            get_string('collectcustomerdata_desc', 'paygw_epayco')
        );
        $mform->setDefault('collectcustomerdata', true);

        // Display callback URLs.
        global $CFG;
        $mform->addElement('static', 'callback_info', get_string('callback_urls', 'paygw_epayco'),
            '<div class="alert alert-info">' .
            '<strong>' . get_string('confirmationurl', 'paygw_epayco') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/epayco/confirmation.php</code><br><br>' .
            '<strong>' . get_string('responseurl', 'paygw_epayco') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/epayco/response.php</code>' .
            '</div>'
        );

        // Email notification templates section.
        $mform->addElement('header', 'emailtemplates', get_string('emailtemplates', 'paygw_epayco'));

        // Placeholders help.
        $mform->addElement('static', 'placeholders_info', get_string('availableplaceholders', 'paygw_epayco'),
            '<div class="alert alert-info small">' .
            '<code>{firstname}</code> - ' . get_string('placeholder:firstname', 'paygw_epayco') . '<br>' .
            '<code>{fullname}</code> - ' . get_string('placeholder:fullname', 'paygw_epayco') . '<br>' .
            '<code>{amount}</code> - ' . get_string('placeholder:amount', 'paygw_epayco') . '<br>' .
            '<code>{currency}</code> - ' . get_string('placeholder:currency', 'paygw_epayco') . '<br>' .
            '<code>{orderid}</code> - ' . get_string('placeholder:orderid', 'paygw_epayco') .
            '</div>'
        );

        // Email subject for completed payments.
        $mform->addElement('text', 'email_completed_subject',
            get_string('email_completed_subject', 'paygw_epayco'), ['size' => 60]);
        $mform->setType('email_completed_subject', PARAM_TEXT);
        $mform->setDefault('email_completed_subject', get_string('email_completed_subject_default', 'paygw_epayco'));

        // Email body for completed payments.
        $mform->addElement('textarea', 'email_completed_body',
            get_string('email_completed_body', 'paygw_epayco'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_completed_body', PARAM_RAW);
        $mform->setDefault('email_completed_body', get_string('email_completed_body_default', 'paygw_epayco'));

        // Email subject for pending payments.
        $mform->addElement('text', 'email_pending_subject',
            get_string('email_pending_subject', 'paygw_epayco'), ['size' => 60]);
        $mform->setType('email_pending_subject', PARAM_TEXT);
        $mform->setDefault('email_pending_subject', get_string('email_pending_subject_default', 'paygw_epayco'));

        // Email body for pending payments.
        $mform->addElement('textarea', 'email_pending_body',
            get_string('email_pending_body', 'paygw_epayco'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_pending_body', PARAM_RAW);
        $mform->setDefault('email_pending_body', get_string('email_pending_body_default', 'paygw_epayco'));
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
        // All credentials are required.
        if ($data->enabled) {
            if (empty($data->p_cust_id_cliente)) {
                $errors['p_cust_id_cliente'] = get_string('required');
            }
            if (empty($data->p_key)) {
                $errors['p_key'] = get_string('required');
            }
            if (empty($data->public_key)) {
                $errors['public_key'] = get_string('required');
            }
            if (empty($data->private_key)) {
                $errors['private_key'] = get_string('required');
            }
        }
    }

    /**
     * Get the ePayco checkout URL.
     *
     * @return string
     */
    public static function get_checkout_url(): string {
        return 'https://checkout.epayco.co/checkout.js';
    }

    /**
     * Get ePayco test credentials (for reference only).
     *
     * @return array Test credentials.
     */
    public static function get_test_credentials(): array {
        return [
            'p_cust_id_cliente' => '9695',
            'p_key' => 'a1c7200f0e2029d11b62bfd863422d5db10a8397',
        ];
    }

    /**
     * Get test cards for sandbox environment.
     *
     * @return array Test cards information.
     */
    public static function get_test_cards(): array {
        return [
            'approved' => [
                'number' => '4575623182290326',
                'cvv' => '123',
                'exp_month' => '12',
                'exp_year' => '2025',
                'holder' => 'APPROVED',
            ],
            'rejected' => [
                'number' => '4151611527583283',
                'cvv' => '123',
                'exp_month' => '12',
                'exp_year' => '2025',
                'holder' => 'REJECTED',
            ],
            'pending' => [
                'number' => '373118856457642',
                'cvv' => '123',
                'exp_month' => '12',
                'exp_year' => '2025',
                'holder' => 'PENDING',
            ],
        ];
    }

    /**
     * Create an epayco_helper instance from configuration.
     *
     * @param object $config Plugin configuration.
     * @return epayco_helper Helper instance.
     */
    public static function create_helper_from_config(object $config): epayco_helper {
        $environment = $config->environment ?? 'test';

        return new epayco_helper(
            $config->p_cust_id_cliente,
            $config->p_key,
            $config->public_key,
            $config->private_key,
            $environment
        );
    }
}
