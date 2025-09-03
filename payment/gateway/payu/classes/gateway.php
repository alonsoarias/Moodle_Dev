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
 * Contains class for PayU payment gateway.
 *
 * @package    paygw_payu
 * @copyright  2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author     Alonso Arias
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
     * Configuration form for currency
     */
    public static function get_supported_currencies(): array {
        // 3-character ISO-4217: https://en.wikipedia.org/wiki/ISO_4217#Active_codes.
        return [
            'COP', 'USD', 'EUR', 'MXN', 'ARS', 'BRL', 'CLP', 'PEN'
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

        // Test mode checkbox - MUST BE FIRST
        $mform->addElement('advcheckbox', 'testmode', get_string('testmode', 'paygw_payu'), get_string('testmode_help', 'paygw_payu'));
        $mform->setType('testmode', PARAM_INT);
        $mform->addHelpButton('testmode', 'testmode', 'paygw_payu');

        // Merchant ID field
        $mform->addElement('text', 'merchantid', get_string('merchantid', 'paygw_payu'));
        $mform->setType('merchantid', PARAM_TEXT);
        $mform->addRule('merchantid', get_string('required'), 'required', null, 'client');
        $mform->setDefault('merchantid', '');

        // Account ID field
        $mform->addElement('text', 'accountid', get_string('accountid', 'paygw_payu'));
        $mform->setType('accountid', PARAM_TEXT);
        $mform->addRule('accountid', get_string('required'), 'required', null, 'client');
        $mform->setDefault('accountid', '');

        // API Key field
        $mform->addElement('passwordunmask', 'apikey', get_string('apikey', 'paygw_payu'));
        $mform->setType('apikey', PARAM_TEXT);
        $mform->addRule('apikey', get_string('required'), 'required', null, 'client');
        $mform->setDefault('apikey', '');

        // API Login field (for API integrations)
        $mform->addElement('text', 'apilogin', get_string('apilogin', 'paygw_payu'));
        $mform->setType('apilogin', PARAM_TEXT);
        $mform->setDefault('apilogin', '');
        $mform->addHelpButton('apilogin', 'apilogin', 'paygw_payu');

        // Public Key field (for tokenization)
        $mform->addElement('text', 'publickey', get_string('publickey', 'paygw_payu'));
        $mform->setType('publickey', PARAM_TEXT);
        $mform->setDefault('publickey', '');
        $mform->addHelpButton('publickey', 'publickey', 'paygw_payu');

        // Fixed description
        $mform->addElement('text', 'fixdesc', get_string('fixdesc', 'paygw_payu'), ['size' => 50]);
        $mform->setType('fixdesc', PARAM_TEXT);
        $mform->addHelpButton('fixdesc', 'fixdesc', 'paygw_payu');

        // Skip mode
        $mform->addElement('advcheckbox', 'skipmode', get_string('skipmode', 'paygw_payu'), '0');
        $mform->setType('skipmode', PARAM_TEXT);
        $mform->addHelpButton('skipmode', 'skipmode', 'paygw_payu');

        // Password mode
        $mform->addElement('advcheckbox', 'passwordmode', get_string('passwordmode', 'paygw_payu'), '0');
        $mform->setType('passwordmode', PARAM_TEXT);
        $mform->disabledIf('passwordmode', 'skipmode', "neq", 0);

        // Password field
        $mform->addElement('passwordunmask', 'password', get_string('password', 'paygw_payu'));
        $mform->setType('password', PARAM_TEXT);
        $mform->addHelpButton('password', 'password', 'paygw_payu');

        // Use details
        $mform->addElement(
            'advcheckbox',
            'usedetails',
            get_string('usedetails', 'paygw_payu')
        );
        $mform->setType('usedetails', PARAM_INT);
        $mform->addHelpButton('usedetails', 'usedetails', 'paygw_payu');

        // Show duration
        $mform->addElement(
            'advcheckbox',
            'showduration',
            get_string('showduration', 'paygw_payu')
        );
        $mform->setType('showduration', PARAM_INT);

        // Fixed cost mode
        $mform->addElement(
            'advcheckbox',
            'fixcost',
            get_string('fixcost', 'paygw_payu')
        );
        $mform->setType('fixcost', PARAM_INT);
        $mform->addHelpButton('fixcost', 'fixcost', 'paygw_payu');

        // Suggested cost
        $mform->addElement('float', 'suggest', get_string('suggest', 'paygw_payu'), ['size' => 10]);
        $mform->setType('suggest', PARAM_FLOAT);
        $mform->disabledIf('suggest', 'fixcost', "neq", 0);

        // Maximum cost
        $mform->addElement('float', 'maxcost', get_string('maxcost', 'paygw_payu'), ['size' => 10]);
        $mform->setType('maxcost', PARAM_FLOAT);
        $mform->disabledIf('maxcost', 'fixcost', "neq", 0);

        // Callback URL information
        global $CFG;
        $mform->addElement('html', '<div class="label-callback" style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;">' .
                                    '<strong>' . get_string('callback', 'paygw_payu') . '</strong><br>');
        $mform->addElement('html', '<code style="background: #fff; padding: 5px; display: inline-block; margin: 5px 0;">' . 
                                    $CFG->wwwroot . '/payment/gateway/payu/callback.php</code><br>');
        $mform->addElement('html', '<small>' . get_string('callback_help', 'paygw_payu') . '</small></div>');

        // Plugin information
        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('paygw_payu');
        $donate = get_string('donate', 'paygw_payu', $plugininfo);
        $mform->addElement('html', $donate);

        // Add JavaScript for auto-fill functionality
        $mform->addElement('html', '
        <script type="text/javascript">
        (function() {
            // PayU Colombia Sandbox Credentials
            const SANDBOX_CREDENTIALS = {
                merchantid: "508029",
                accountid: "512321",
                apikey: "4Vj8eK4rloUd272L48hsrarnUA",
                apilogin: "pRRXKOl8ikMmt9u",
                publickey: "PKaC6H4cEDJD919n705L544kSU"
            };
            
            // Production fields placeholder
            const PRODUCTION_PLACEHOLDERS = {
                merchantid: "",
                accountid: "",
                apikey: "",
                apilogin: "",
                publickey: ""
            };
            
            // Store original values
            let originalValues = {};
            let isTestMode = false;
            
            function initializeTestMode() {
                const testModeCheckbox = document.querySelector(\'input[name="testmode"]\');
                if (!testModeCheckbox) return;
                
                // Get all credential fields
                const fields = {
                    merchantid: document.querySelector(\'input[name="merchantid"]\'),
                    accountid: document.querySelector(\'input[name="accountid"]\'),
                    apikey: document.querySelector(\'input[name="apikey"]\'),
                    apilogin: document.querySelector(\'input[name="apilogin"]\'),
                    publickey: document.querySelector(\'input[name="publickey"]\')
                };
                
                // Check initial state
                isTestMode = testModeCheckbox.checked;
                
                // Store original values on page load
                Object.keys(fields).forEach(key => {
                    if (fields[key]) {
                        originalValues[key] = fields[key].value;
                    }
                });
                
                // Function to update fields
                function updateFields(useTestCredentials) {
                    const credentials = useTestCredentials ? SANDBOX_CREDENTIALS : originalValues;
                    
                    Object.keys(fields).forEach(key => {
                        if (fields[key]) {
                            if (useTestCredentials) {
                                // Store current value before changing
                                if (!isTestMode) {
                                    originalValues[key] = fields[key].value || "";
                                }
                                // Set test credential
                                fields[key].value = credentials[key] || "";
                                // Add visual indicator
                                fields[key].style.backgroundColor = "#fffde7";
                                fields[key].setAttribute("readonly", "readonly");
                                
                                // Add helper text if not exists
                                let helperText = fields[key].parentElement.querySelector(".payu-test-helper");
                                if (!helperText) {
                                    helperText = document.createElement("div");
                                    helperText.className = "payu-test-helper";
                                    helperText.style.cssText = "color: #f57c00; font-size: 0.9em; margin-top: 5px;";
                                    helperText.textContent = "' . get_string('testcredential_auto', 'paygw_payu') . '";
                                    fields[key].parentElement.appendChild(helperText);
                                }
                            } else {
                                // Restore original value
                                fields[key].value = credentials[key] || "";
                                // Remove visual indicator
                                fields[key].style.backgroundColor = "";
                                fields[key].removeAttribute("readonly");
                                
                                // Remove helper text
                                let helperText = fields[key].parentElement.querySelector(".payu-test-helper");
                                if (helperText) {
                                    helperText.remove();
                                }
                            }
                        }
                    });
                    
                    isTestMode = useTestCredentials;
                }
                
                // Add event listener to checkbox
                testModeCheckbox.addEventListener("change", function() {
                    updateFields(this.checked);
                    
                    // Show/hide information message
                    let infoMessage = document.querySelector(".payu-test-info");
                    if (!infoMessage) {
                        infoMessage = document.createElement("div");
                        infoMessage.className = "payu-test-info alert alert-info";
                        infoMessage.style.cssText = "margin-top: 10px; padding: 10px;";
                        infoMessage.innerHTML = \'<strong>' . get_string('testmode_active', 'paygw_payu') . '</strong><br>\' + 
                                               \'' . get_string('testmode_description', 'paygw_payu') . '\';
                        testModeCheckbox.parentElement.parentElement.appendChild(infoMessage);
                    }
                    infoMessage.style.display = this.checked ? "block" : "none";
                });
                
                // Initialize on page load if test mode is already checked
                if (isTestMode) {
                    updateFields(true);
                }
            }
            
            // Wait for DOM to be ready
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", initializeTestMode);
            } else {
                initializeTestMode();
            }
        })();
        </script>
        ');
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
        // No validation needed in test mode as credentials are auto-filled
        if (!empty($data->testmode)) {
            return;
        }
        
        // Validate production credentials
        if (
            $data->enabled &&
                (empty($data->merchantid) || empty($data->accountid) || empty($data->apikey))
        ) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
        
        if ($data->maxcost && $data->maxcost < $data->suggest) {
            $errors['maxcost'] = get_string('maxcosterror', 'paygw_payu');
        }
    }
}