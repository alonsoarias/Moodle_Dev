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
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

use core_payment\form\account_gateway;

/**
 * The gateway class for the PayU Colombia payment gateway.
 *
 * @copyright  2024 Orion Cloud Consulting SAS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * Return currencies supported by PayU Colombia.
     *
     * @return string[] Array of currency codes
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

        // Merchant ID field.
        $mform->addElement('text', 'merchantid', get_string('merchantid', 'paygw_payu'));
        $mform->setType('merchantid', PARAM_TEXT);
        $mform->addRule('merchantid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('merchantid', 'merchantid', 'paygw_payu');

        // Account ID field.
        $mform->addElement('text', 'accountid', get_string('accountid', 'paygw_payu'));
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addRule('accountid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('accountid', 'accountid', 'paygw_payu');

        // API Login field.
        $mform->addElement('text', 'apilogin', get_string('apilogin', 'paygw_payu'));
        $mform->setType('apilogin', PARAM_TEXT);
        $mform->addRule('apilogin', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('apilogin', 'apilogin', 'paygw_payu');

        // API Key field (password).
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_payu'));
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addRule('apikey', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('apikey', 'apikey', 'paygw_payu');

        // Test mode checkbox.
        $mform->addElement('advcheckbox', 'testmode', get_string('testmode', 'paygw_payu'));
        $mform->setType('testmode', PARAM_BOOL);
        $mform->setDefault('testmode', 1);
        $mform->addHelpButton('testmode', 'testmode', 'paygw_payu');

        // Payment methods configuration.
        $mform->addElement('header', 'paymentmethods', get_string('paymentmethods', 'paygw_payu'));
        
        $paymentmethods = [
            'creditcard' => get_string('creditcard', 'paygw_payu'),
            'pse' => get_string('pse', 'paygw_payu'),
            'nequi' => get_string('nequi', 'paygw_payu'),
            'bancolombia' => get_string('bancolombia', 'paygw_payu'),
            'googlepay' => get_string('googlepay', 'paygw_payu'),
            'cash' => get_string('cash', 'paygw_payu'),
        ];

        $select = $mform->addElement('select', 'enabledmethods', get_string('enabledmethods', 'paygw_payu'), $paymentmethods);
        $select->setMultiple(true);
        $mform->setDefault('enabledmethods', array_keys($paymentmethods));
        $mform->addHelpButton('enabledmethods', 'enabledmethods', 'paygw_payu');

        // Cache settings.
        $mform->addElement('header', 'cachesettings', get_string('cachesettings', 'paygw_payu'));
        
        $mform->addElement('advcheckbox', 'enablecache', get_string('enablecache', 'paygw_payu'));
        $mform->setType('enablecache', PARAM_BOOL);
        $mform->setDefault('enablecache', 1);
        $mform->addHelpButton('enablecache', 'enablecache', 'paygw_payu');

        // Notification settings.
        $mform->addElement('header', 'notificationsettings', get_string('notificationsettings', 'paygw_payu'));
        
        $mform->addElement('advcheckbox', 'enablenotifications', get_string('enablenotifications', 'paygw_payu'));
        $mform->setType('enablenotifications', PARAM_BOOL);
        $mform->setDefault('enablenotifications', 1);
        $mform->addHelpButton('enablenotifications', 'enablenotifications', 'paygw_payu');

        // Show callback URL for reference.
        global $CFG;
        $callbackurl = $CFG->wwwroot . '/payment/gateway/payu/callback.php';
        $mform->addElement('static', 'callbackurl', get_string('callbackurl', 'paygw_payu'), 
            '<div class="alert alert-info">' . $callbackurl . '</div>');
        $mform->addHelpButton('callbackurl', 'callbackurl', 'paygw_payu');
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
        if ($data->enabled && 
            (empty($data->merchantid) || empty($data->accountid) || 
             empty($data->apilogin) || empty($data->apikey))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }

        // Validate merchant ID format (numeric).
        if (!empty($data->merchantid) && !is_numeric($data->merchantid)) {
            $errors['merchantid'] = get_string('merchantidinvalid', 'paygw_payu');
        }

        // Validate account ID format (numeric).
        if (!empty($data->accountid) && !is_numeric($data->accountid)) {
            $errors['accountid'] = get_string('accountidinvalid', 'paygw_payu');
        }

        // Check that at least one payment method is enabled.
        if ($data->enabled && empty($data->enabledmethods)) {
            $errors['enabledmethods'] = get_string('atleastonemethodrequired', 'paygw_payu');
        }
    }
}