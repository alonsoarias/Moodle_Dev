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
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

use core_payment\form\account_gateway;

/**
 * The gateway class for PayU Colombia payment gateway.
 *
 * PayU is a leading payment gateway in Latin America, this plugin is
 * specifically designed for the Colombian market.
 *
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * The list of currencies supported by PayU Colombia.
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
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_payu'), [
            'sandbox' => get_string('environment:sandbox', 'paygw_payu'),
            'production' => get_string('environment:production', 'paygw_payu'),
        ]);
        $mform->setType('environment', PARAM_TEXT);
        $mform->setDefault('environment', 'sandbox');
        $mform->addHelpButton('environment', 'environment', 'paygw_payu');

        // Merchant ID.
        $mform->addElement('text', 'merchantid', get_string('merchantid', 'paygw_payu'), ['size' => 30]);
        $mform->setType('merchantid', PARAM_TEXT);
        $mform->addHelpButton('merchantid', 'merchantid', 'paygw_payu');
        $mform->hideIf('merchantid', 'environment', 'eq', 'sandbox');

        // Account ID.
        $mform->addElement('text', 'accountid', get_string('accountid', 'paygw_payu'), ['size' => 30]);
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addHelpButton('accountid', 'accountid', 'paygw_payu');
        $mform->hideIf('accountid', 'environment', 'eq', 'sandbox');

        // API Key.
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_payu'), ['size' => 60]);
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'paygw_payu');
        $mform->hideIf('apikey', 'environment', 'eq', 'sandbox');

        // API Login.
        $mform->addElement('text', 'apilogin', get_string('apilogin', 'paygw_payu'), ['size' => 30]);
        $mform->setType('apilogin', PARAM_TEXT);
        $mform->addHelpButton('apilogin', 'apilogin', 'paygw_payu');
        $mform->hideIf('apilogin', 'environment', 'eq', 'sandbox');

        // Language selection.
        $mform->addElement('select', 'language', get_string('language', 'paygw_payu'), [
            'es' => get_string('language:es', 'paygw_payu'),
            'en' => get_string('language:en', 'paygw_payu'),
        ]);
        $mform->setType('language', PARAM_TEXT);
        $mform->setDefault('language', 'es');

        // Collect customer data.
        $mform->addElement(
            'advcheckbox',
            'collectcustomerdata',
            get_string('collectcustomerdata', 'paygw_payu'),
            get_string('collectcustomerdata_desc', 'paygw_payu')
        );
        $mform->setDefault('collectcustomerdata', true);

        // Display callback URLs.
        global $CFG;
        $mform->addElement('static', 'callback_info', get_string('callback_urls', 'paygw_payu'),
            '<div class="alert alert-info">' .
            '<strong>' . get_string('confirmationurl', 'paygw_payu') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/payu/callback.php</code><br><br>' .
            '<strong>' . get_string('responseurl', 'paygw_payu') . ':</strong><br>' .
            '<code>' . $CFG->wwwroot . '/payment/gateway/payu/return.php</code>' .
            '</div>'
        );

        // Sandbox notice.
        $mform->addElement('static', 'sandbox_notice', '',
            '<div class="alert alert-warning">' .
            get_string('sandbox_notice', 'paygw_payu') .
            '</div>'
        );

        // Email notification templates section.
        $mform->addElement('header', 'emailtemplates', get_string('emailtemplates', 'paygw_payu'));

        // Placeholders help.
        $mform->addElement('static', 'placeholders_info', get_string('availableplaceholders', 'paygw_payu'),
            '<div class="alert alert-info small">' .
            '<code>{firstname}</code> - ' . get_string('placeholder:firstname', 'paygw_payu') . '<br>' .
            '<code>{fullname}</code> - ' . get_string('placeholder:fullname', 'paygw_payu') . '<br>' .
            '<code>{amount}</code> - ' . get_string('placeholder:amount', 'paygw_payu') . '<br>' .
            '<code>{currency}</code> - ' . get_string('placeholder:currency', 'paygw_payu') . '<br>' .
            '<code>{orderid}</code> - ' . get_string('placeholder:orderid', 'paygw_payu') .
            '</div>'
        );

        // Email subject for completed payments.
        $mform->addElement('text', 'email_completed_subject',
            get_string('email_completed_subject', 'paygw_payu'), ['size' => 60]);
        $mform->setType('email_completed_subject', PARAM_TEXT);
        $mform->setDefault('email_completed_subject', get_string('email_completed_subject_default', 'paygw_payu'));

        // Email body for completed payments.
        $mform->addElement('textarea', 'email_completed_body',
            get_string('email_completed_body', 'paygw_payu'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_completed_body', PARAM_RAW);
        $mform->setDefault('email_completed_body', get_string('email_completed_body_default', 'paygw_payu'));

        // Email subject for pending payments.
        $mform->addElement('text', 'email_pending_subject',
            get_string('email_pending_subject', 'paygw_payu'), ['size' => 60]);
        $mform->setType('email_pending_subject', PARAM_TEXT);
        $mform->setDefault('email_pending_subject', get_string('email_pending_subject_default', 'paygw_payu'));

        // Email body for pending payments.
        $mform->addElement('textarea', 'email_pending_body',
            get_string('email_pending_body', 'paygw_payu'), ['rows' => 6, 'cols' => 60]);
        $mform->setType('email_pending_body', PARAM_RAW);
        $mform->setDefault('email_pending_body', get_string('email_pending_body_default', 'paygw_payu'));
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
        // Production environment requires all credentials.
        if ($data->enabled && $data->environment === 'production') {
            if (empty($data->merchantid)) {
                $errors['merchantid'] = get_string('required');
            }
            if (empty($data->accountid)) {
                $errors['accountid'] = get_string('required');
            }
            if (empty($data->apikey)) {
                $errors['apikey'] = get_string('required');
            }
            if (empty($data->apilogin)) {
                $errors['apilogin'] = get_string('required');
            }
        }

        // Validate merchant ID format (should be numeric).
        if (!empty($data->merchantid) && !is_numeric($data->merchantid)) {
            $errors['merchantid'] = get_string('invalidmerchantid', 'paygw_payu');
        }

        // Validate account ID format (should be numeric).
        if (!empty($data->accountid) && !is_numeric($data->accountid)) {
            $errors['accountid'] = get_string('invalidaccountid', 'paygw_payu');
        }
    }

    /**
     * Get PayU test credentials for Colombia sandbox.
     *
     * @return array Test credentials.
     */
    public static function get_test_credentials(): array {
        return [
            'merchantId' => '508029',
            'accountId' => '512321',
            'apiKey' => '4Vj8eK4rloUd272L48hsrarnUA',
            'apiLogin' => 'pRRXKOl8ikMmt9u',
            'publicKey' => 'PKaC6H4cEDJD919n705L544kSU',
        ];
    }

    /**
     * Get the checkout URL based on environment.
     *
     * @param string $environment Environment (sandbox or production).
     * @return string Checkout URL.
     */
    public static function get_checkout_url(string $environment): string {
        if ($environment === 'production') {
            return 'https://checkout.payulatam.com/ppp-web-gateway-payu/';
        }
        return 'https://sandbox.checkout.payulatam.com/ppp-web-gateway-payu/';
    }

    /**
     * Get the API URL based on environment.
     *
     * @param string $environment Environment (sandbox or production).
     * @return string API URL.
     */
    public static function get_api_url(string $environment): string {
        if ($environment === 'production') {
            return 'https://api.payulatam.com/payments-api/4.0/service.cgi';
        }
        return 'https://sandbox.api.payulatam.com/payments-api/4.0/service.cgi';
    }
}
