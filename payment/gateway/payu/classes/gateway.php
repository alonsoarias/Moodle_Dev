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
 * Contains class for PayU payment gateway for Latin America.
 *
 * @package    paygw_payu
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

/**
 * The gateway class for PayU payment gateway.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Countries supported by PayU Latin America
     */
    const SUPPORTED_COUNTRIES = [
        'AR' => 'Argentina',
        'BR' => 'Brasil', 
        'CL' => 'Chile',
        'CO' => 'Colombia',
        'MX' => 'México',
        'PA' => 'Panamá',
        'PE' => 'Perú'
    ];

    /**
     * Currencies supported by each country
     */
    const COUNTRY_CURRENCIES = [
        'AR' => ['ARS', 'USD'],
        'BR' => ['BRL', 'USD'],
        'CL' => ['CLP', 'USD'],
        'CO' => ['COP', 'USD'],
        'MX' => ['MXN', 'USD'],
        'PA' => ['USD', 'PAB'],
        'PE' => ['PEN', 'USD']
    ];

    /**
     * PayU account IDs for testing (sandbox)
     */
    const TEST_ACCOUNT_IDS = [
        'AR' => '512322',
        'BR' => '512327',
        'CL' => '512325',
        'CO' => '512321',
        'MX' => '512324',
        'PA' => '512326',
        'PE' => '512323'
    ];

    /**
     * Configuration form for supported currencies based on selected country
     */
    public static function get_supported_currencies(): array {
        // Return all possible currencies that PayU supports
        $currencies = [];
        foreach (self::COUNTRY_CURRENCIES as $country => $countryCurrencies) {
            foreach ($countryCurrencies as $currency) {
                $currencies[$currency] = $currency;
            }
        }
        return array_unique($currencies);
    }

    /**
     * Configuration form for the gateway instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        // Country selection
        $countries = [];
        foreach (self::SUPPORTED_COUNTRIES as $code => $name) {
            $countries[$code] = get_string('country_' . strtolower($code), 'paygw_payu', $name);
        }
        $mform->addElement('select', 'country', get_string('country', 'paygw_payu'), $countries);
        $mform->setType('country', PARAM_TEXT);
        $mform->setDefault('country', 'CO');
        $mform->addRule('country', get_string('required'), 'required', null, 'client');

        // Environment selection
        $environments = [
            'sandbox' => get_string('environment_sandbox', 'paygw_payu'),
            'production' => get_string('environment_production', 'paygw_payu')
        ];
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_payu'), $environments);
        $mform->setType('environment', PARAM_TEXT);
        $mform->setDefault('environment', 'sandbox');

        // Merchant ID
        $mform->addElement('text', 'merchantid', get_string('merchantid', 'paygw_payu'));
        $mform->setType('merchantid', PARAM_TEXT);
        $mform->addHelpButton('merchantid', 'merchantid', 'paygw_payu');
        $mform->disabledIf('merchantid', 'environment', 'eq', 'sandbox');

        // Account ID  
        $mform->addElement('text', 'accountid', get_string('accountid', 'paygw_payu'));
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addHelpButton('accountid', 'accountid', 'paygw_payu');
        $mform->disabledIf('accountid', 'environment', 'eq', 'sandbox');

        // API Key
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_payu'));
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addHelpButton('apikey', 'apikey', 'paygw_payu');
        $mform->disabledIf('apikey', 'environment', 'eq', 'sandbox');

        // API Login
        $mform->addElement('text', 'apilogin', get_string('apilogin', 'paygw_payu'));
        $mform->setType('apilogin', PARAM_TEXT);
        $mform->addHelpButton('apilogin', 'apilogin', 'paygw_payu');
        $mform->disabledIf('apilogin', 'environment', 'eq', 'sandbox');

        // Public Key (for tokenization)
        $mform->addElement('text', 'publickey', get_string('publickey', 'paygw_payu'));
        $mform->setType('publickey', PARAM_TEXT);
        $mform->addHelpButton('publickey', 'publickey', 'paygw_payu');
        $mform->disabledIf('publickey', 'environment', 'eq', 'sandbox');

        // Payment page language
        $languages = [
            'es' => get_string('language_es', 'paygw_payu'),
            'en' => get_string('language_en', 'paygw_payu'),
            'pt' => get_string('language_pt', 'paygw_payu')
        ];
        $mform->addElement('select', 'language', get_string('language', 'paygw_payu'), $languages);
        $mform->setType('language', PARAM_TEXT);
        $mform->setDefault('language', 'es');

        // Advanced settings
        $mform->addElement('advcheckbox', 'autofilltest', get_string('autofilltest', 'paygw_payu'));
        $mform->setType('autofilltest', PARAM_INT);
        $mform->addHelpButton('autofilltest', 'autofilltest', 'paygw_payu');

        $mform->addElement('advcheckbox', 'skipmode', get_string('skipmode', 'paygw_payu'));
        $mform->setType('skipmode', PARAM_INT);
        $mform->addHelpButton('skipmode', 'skipmode', 'paygw_payu');

        $mform->addElement('advcheckbox', 'passwordmode', get_string('passwordmode', 'paygw_payu'));
        $mform->setType('passwordmode', PARAM_INT);
        $mform->disabledIf('passwordmode', 'skipmode', 'neq', 0);

        $mform->addElement('passwordunmask', 'password', get_string('password', 'paygw_payu'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'password', 'paygw_payu');
        $mform->disabledIf('password', 'passwordmode', 'eq', 0);

        // Pricing options
        $mform->addElement('advcheckbox', 'fixcost', get_string('fixcost', 'paygw_payu'));
        $mform->setType('fixcost', PARAM_INT);
        $mform->addHelpButton('fixcost', 'fixcost', 'paygw_payu');

        $mform->addElement('float', 'suggest', get_string('suggest', 'paygw_payu'), ['size' => 10]);
        $mform->setType('suggest', PARAM_FLOAT);
        $mform->disabledIf('suggest', 'fixcost', 'neq', 0);

        $mform->addElement('float', 'maxcost', get_string('maxcost', 'paygw_payu'), ['size' => 10]);
        $mform->setType('maxcost', PARAM_FLOAT);
        $mform->disabledIf('maxcost', 'fixcost', 'neq', 0);

        // Callback URLs display
        global $CFG;
        $mform->addElement('html', '<div class="alert alert-info">');
        $mform->addElement('html', '<strong>' . get_string('callback_urls', 'paygw_payu') . '</strong><br>');
        $mform->addElement('html', get_string('confirmation_url', 'paygw_payu') . ':<br>');
        $mform->addElement('html', '<code>' . $CFG->wwwroot . '/payment/gateway/payu/callback.php</code><br><br>');
        $mform->addElement('html', get_string('response_url', 'paygw_payu') . ':<br>');
        $mform->addElement('html', '<code>' . $CFG->wwwroot . '/payment/gateway/payu/return.php</code><br>');
        $mform->addElement('html', '</div>');

        // Display note about test credentials
        $mform->addElement('html', '<div class="alert alert-warning">');
        $mform->addElement('html', get_string('sandbox_note', 'paygw_payu'));
        $mform->addElement('html', '</div>');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(
        \core_payment\form\account_gateway $form,
        \stdClass $data,
        array $files,
        array &$errors
    ): void {
        // For production environment, all fields are required
        if ($data->environment === 'production') {
            if (empty($data->merchantid) || empty($data->accountid) || 
                empty($data->apikey) || empty($data->apilogin)) {
                $errors['environment'] = get_string('production_fields_required', 'paygw_payu');
            }
        }

        // Validate max cost is greater than suggested cost
        if (!empty($data->maxcost) && !empty($data->suggest)) {
            if ($data->maxcost < $data->suggest) {
                $errors['maxcost'] = get_string('maxcosterror', 'paygw_payu');
            }
        }

        // If password mode is enabled, password is required
        if (!empty($data->passwordmode) && empty($data->password)) {
            $errors['password'] = get_string('password_required', 'paygw_payu');
        }
    }

    /**
     * Get PayU test credentials based on environment and country
     *
     * @param string $country
     * @return array
     */
    public static function get_test_credentials($country = 'CO'): array {
        return [
            'merchantid' => '508029',
            'accountid' => self::TEST_ACCOUNT_IDS[$country] ?? '512321',
            'apikey' => '4Vj8eK4rloUd272L48hsrarnUA',
            'apilogin' => 'pRRXKOl8ikMmt9u',
            'publickey' => 'PKaC6H4cEDJD919n705L544kSU'
        ];
    }
}