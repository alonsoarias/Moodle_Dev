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
 * Contains class for Addi payment gateway.
 *
 * @package    paygw_addi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_addi;

use core_payment\form\account_gateway;

/**
 * The gateway class for Addi payment gateway.
 *
 * Addi is a Colombian "Buy Now, Pay Later" (BNPL) service that allows
 * customers to split their payments into installments.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * The list of currencies supported by Addi.
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
     * @param account_gateway $form
     */
    public static function add_configuration_to_gateway_form(account_gateway $form): void {
        $mform = $form->get_mform();

        // Environment selection.
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_addi'), [
            'sandbox' => get_string('environment:sandbox', 'paygw_addi'),
            'production' => get_string('environment:production', 'paygw_addi'),
        ]);
        $mform->setType('environment', PARAM_TEXT);
        $mform->setDefault('environment', 'sandbox');
        $mform->addHelpButton('environment', 'environment', 'paygw_addi');

        // Client ID.
        $mform->addElement('text', 'clientid', get_string('clientid', 'paygw_addi'), ['size' => 60]);
        $mform->setType('clientid', PARAM_TEXT);
        $mform->addHelpButton('clientid', 'clientid', 'paygw_addi');
        $mform->addRule('clientid', get_string('required'), 'required', null, 'client');

        // Client Secret.
        $mform->addElement('passwordunmask', 'clientsecret', get_string('clientsecret', 'paygw_addi'), ['size' => 60]);
        $mform->setType('clientsecret', PARAM_TEXT);
        $mform->addHelpButton('clientsecret', 'clientsecret', 'paygw_addi');
        $mform->addRule('clientsecret', get_string('required'), 'required', null, 'client');

        // Ally Slug (merchant identifier).
        $mform->addElement('text', 'allyslug', get_string('allyslug', 'paygw_addi'), ['size' => 60]);
        $mform->setType('allyslug', PARAM_TEXT);
        $mform->addHelpButton('allyslug', 'allyslug', 'paygw_addi');
        $mform->addRule('allyslug', get_string('required'), 'required', null, 'client');

        // Minimum order amount for Addi.
        $mform->addElement('text', 'minamount', get_string('minamount', 'paygw_addi'), ['size' => 20]);
        $mform->setType('minamount', PARAM_INT);
        $mform->setDefault('minamount', 50000);
        $mform->addHelpButton('minamount', 'minamount', 'paygw_addi');

        // Maximum order amount for Addi.
        $mform->addElement('text', 'maxamount', get_string('maxamount', 'paygw_addi'), ['size' => 20]);
        $mform->setType('maxamount', PARAM_INT);
        $mform->setDefault('maxamount', 5000000);
        $mform->addHelpButton('maxamount', 'maxamount', 'paygw_addi');

        // Display callback URLs.
        global $CFG;
        $mform->addElement('static', 'callback_info', get_string('callback_urls', 'paygw_addi'),
            '<div class="alert alert-info">' .
            '<strong>' . get_string('redirecturl', 'paygw_addi') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/addi/response.php</code><br><br>' .
            '<strong>' . get_string('webhookurl', 'paygw_addi') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/addi/webhook.php</code>' .
            '</div>'
        );

        // Email notification templates section.
        $mform->addElement('header', 'emailtemplates', get_string('emailtemplates', 'paygw_addi'));

        // Placeholders help.
        $mform->addElement('static', 'placeholders_info', get_string('availableplaceholders', 'paygw_addi'),
            '<div class="alert alert-info small">' .
            '<code>{firstname}</code> - ' . get_string('placeholder:firstname', 'paygw_addi') . '<br>' .
            '<code>{fullname}</code> - ' . get_string('placeholder:fullname', 'paygw_addi') . '<br>' .
            '<code>{amount}</code> - ' . get_string('placeholder:amount', 'paygw_addi') . '<br>' .
            '<code>{currency}</code> - ' . get_string('placeholder:currency', 'paygw_addi') . '<br>' .
            '<code>{orderid}</code> - ' . get_string('placeholder:orderid', 'paygw_addi') .
            '</div>'
        );

        // Email subject for completed payments.
        $mform->addElement('text', 'email_completed_subject',
            get_string('email_completed_subject', 'paygw_addi'), ['size' => 60]);
        $mform->setType('email_completed_subject', PARAM_TEXT);
        $mform->setDefault('email_completed_subject', get_string('email_completed_subject_default', 'paygw_addi'));

        // Email body for completed payments.
        $mform->addElement('textarea', 'email_completed_body',
            get_string('email_completed_body', 'paygw_addi'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_completed_body', PARAM_RAW);
        $mform->setDefault('email_completed_body', get_string('email_completed_body_default', 'paygw_addi'));

        // Email subject for pending payments.
        $mform->addElement('text', 'email_pending_subject',
            get_string('email_pending_subject', 'paygw_addi'), ['size' => 60]);
        $mform->setType('email_pending_subject', PARAM_TEXT);
        $mform->setDefault('email_pending_subject', get_string('email_pending_subject_default', 'paygw_addi'));

        // Email body for pending payments.
        $mform->addElement('textarea', 'email_pending_body',
            get_string('email_pending_body', 'paygw_addi'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_pending_body', PARAM_RAW);
        $mform->setDefault('email_pending_body', get_string('email_pending_body_default', 'paygw_addi'));
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
            if (empty($data->clientid)) {
                $errors['clientid'] = get_string('required');
            }
            if (empty($data->clientsecret)) {
                $errors['clientsecret'] = get_string('required');
            }
            if (empty($data->allyslug)) {
                $errors['allyslug'] = get_string('required');
            }
        }

        // Validate minimum amount.
        if (isset($data->minamount) && $data->minamount < 0) {
            $errors['minamount'] = get_string('invalidminamount', 'paygw_addi');
        }

        // Validate maximum amount.
        if (isset($data->maxamount) && $data->maxamount < 0) {
            $errors['maxamount'] = get_string('invalidmaxamount', 'paygw_addi');
        }

        // Ensure min is less than max.
        if (isset($data->minamount) && isset($data->maxamount) && $data->minamount >= $data->maxamount) {
            $errors['maxamount'] = get_string('maxmustbegreaterthanmin', 'paygw_addi');
        }
    }

    /**
     * Get the Addi Auth URL based on environment.
     *
     * @param string $environment 'sandbox' or 'production'
     * @return string
     */
    public static function get_auth_url(string $environment): string {
        if ($environment === 'production') {
            return 'https://auth.addi.com/oauth/token';
        }
        return 'https://auth.addi-staging.com/oauth/token';
    }

    /**
     * Get the Addi API base URL based on environment.
     *
     * @param string $environment 'sandbox' or 'production'
     * @return string
     */
    public static function get_api_url(string $environment): string {
        if ($environment === 'production') {
            return 'https://api.addi.com';
        }
        return 'https://api.staging.addi.com';
    }

    /**
     * Create an addi_helper instance from configuration.
     *
     * @param object $config Plugin configuration.
     * @return addi_helper Helper instance.
     */
    public static function create_helper_from_config(object $config): addi_helper {
        $environment = $config->environment ?? 'sandbox';

        return new addi_helper(
            $config->clientid,
            $config->clientsecret,
            $config->allyslug,
            $environment
        );
    }
}
