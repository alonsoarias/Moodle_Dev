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
 * Contains class for payu payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_payu;

/**
 * The gateway class for payu payment gateway.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {
    /**
     * Configuration form for currency
     */
    public static function get_supported_currencies(): array {
        // 3-character ISO-4217: https://en.wikipedia.org/wiki/ISO_4217#Active_codes.
        return [
            'COP', 'USD', 'ARS', 'BRL', 'CLP', 'MXN', 'PEN'
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $options = ['api.payulatam.com'  => 'api.payulatam.com (Production)',
                    'sandbox.api.payulatam.com' => 'sandbox.api.payulatam.com (Sandbox)'];
        $mform->addElement('select', 'apiurl', get_string('apiurl', 'paygw_payu'), $options);
        $mform->setType('apiurl', PARAM_TEXT);

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

        $mform->addElement('advcheckbox', 'testmode', get_string('testmode', 'paygw_payu'), '0');
        $mform->setType('testmode', PARAM_INT);

        $paymentsystems = [
            '0' => get_string('all', 'paygw_payu'),
            'VISA' => 'VISA',
            'MASTERCARD' => 'MASTERCARD',
            'AMEX' => 'AMEX',
            'DINERS' => 'DINERS',
            'PSE' => 'PSE',
            'EFECTY' => 'EFECTY',
            'BALOTO' => 'BALOTO',
        ];
        $mform->addElement('select', 'paymentsystem', get_string('paymentsystem', 'paygw_payu'), $paymentsystems);
        $mform->setDefault('paymentsystem', '0');

        $mform->addElement('text', 'fixdesc', get_string('fixdesc', 'paygw_payu'), ['size' => 50]);
        $mform->setType('fixdesc', PARAM_TEXT);
        $mform->addHelpButton('fixdesc', 'fixdesc', 'paygw_payu');

        $mform->addElement('advcheckbox', 'skipmode', get_string('skipmode', 'paygw_payu'), '0');
        $mform->setType('skipmode', PARAM_TEXT);
        $mform->addHelpButton('skipmode', 'skipmode', 'paygw_payu');

        $mform->addElement('advcheckbox', 'passwordmode', get_string('passwordmode', 'paygw_payu'), '0');
        $mform->setType('passwordmode', PARAM_TEXT);
        $mform->disabledIf('passwordmode', 'skipmode', "neq", 0);

        $mform->addElement('passwordunmask', 'password', get_string('password', 'paygw_payu'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'password', 'paygw_payu');

        $mform->addElement(
            'advcheckbox',
            'usedetails',
            get_string('usedetails', 'paygw_payu')
        );
        $mform->setType('usedetails', PARAM_INT);
        $mform->addHelpButton('usedetails', 'usedetails', 'paygw_payu');

        $mform->addElement(
            'advcheckbox',
            'showduration',
            get_string('showduration', 'paygw_payu')
        );
        $mform->setType('showduration', PARAM_INT);

        $mform->addElement(
            'advcheckbox',
            'fixcost',
            get_string('fixcost', 'paygw_payu')
        );
        $mform->setType('fixcost', PARAM_INT);
        $mform->addHelpButton('fixcost', 'fixcost', 'paygw_payu');

        $mform->addElement('float', 'suggest', get_string('suggest', 'paygw_payu'), ['size' => 10]);
        $mform->setType('suggest', PARAM_FLOAT);
        $mform->disabledIf('suggest', 'fixcost', "neq", 0);

        $mform->addElement('float', 'maxcost', get_string('maxcost', 'paygw_payu'), ['size' => 10]);
        $mform->setType('maxcost', PARAM_FLOAT);
        $mform->disabledIf('maxcost', 'fixcost', "neq", 0);

        global $CFG;
        $mform->addElement('html', '<div class="label-callback" style="background: lightblue; padding: 15px;">' .
                                    get_string('callback', 'paygw_payu') . '<br>');
        $mform->addElement('html', $CFG->wwwroot . '/payment/gateway/payu/callback.php<br>');
        $mform->addElement('html', get_string('callback_help', 'paygw_payu') . '</div><br>');
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
        if (
            $data->enabled &&
                (empty($data->merchantid) || empty($data->accountid) || 
                 empty($data->apilogin) || empty($data->apikey))
        ) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
        if ($data->maxcost && $data->maxcost < $data->suggest) {
            $errors['maxcost'] = get_string('maxcosterror', 'paygw_payu');
        }
    }
}