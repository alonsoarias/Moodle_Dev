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
 * PayU configuration helper for auto-filling sandbox credentials.
 *
 * @module     paygw_payu/config_helper
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/notification', 'core/str'], function($, Notification, Str) {
    'use strict';
    
    /**
     * PayU Sandbox credentials for Colombia.
     * These are the official test credentials from PayU documentation.
     */
    const SANDBOX_CREDENTIALS = {
        merchantid: '508029',
        payuaccountid: '512321',
        apilogin: 'pRRXKOl8ikMmt9u',
        apikey: '4Vj8eK4rloUd272L48hsrarnUA'
    };

    /**
     * Test credit cards for sandbox environment.
     */
    const TEST_CARDS = {
        VISA_APPROVED: {
            number: '4111111111111111',
            cvv: '123',
            expMonth: '12',
            expYear: '2029',
            name: 'APPROVED',
            network: 'VISA'
        },
        MASTERCARD_APPROVED: {
            number: '5424000000000015',
            cvv: '123',
            expMonth: '12',
            expYear: '2029',
            name: 'APPROVED',
            network: 'MASTERCARD'
        },
        AMEX_APPROVED: {
            number: '370000000000002',
            cvv: '1234',
            expMonth: '12',
            expYear: '2029',
            name: 'APPROVED',
            network: 'AMEX'
        },
        DINERS_APPROVED: {
            number: '36018623456787',
            cvv: '123',
            expMonth: '12',
            expYear: '2029',
            name: 'APPROVED',
            network: 'DINERS'
        },
        VISA_DECLINED: {
            number: '4000000000000002',
            cvv: '123',
            expMonth: '12',
            expYear: '2029',
            name: 'DECLINED',
            network: 'VISA'
        },
        VISA_INSUFFICIENT: {
            number: '4000000000000063',
            cvv: '123',
            expMonth: '12',
            expYear: '2029',
            name: 'INSUFFICIENT_FUNDS',
            network: 'VISA'
        }
    };

    /**
     * Test PSE data.
     */
    const TEST_PSE = {
        bank: '1022',
        userType: 'N',
        document: '1234567890',
        phone: '3001234567',
        email: 'test@payulatam.com',
        name: 'TEST USER PSE'
    };

    /**
     * Test Nequi data.
     */
    const TEST_NEQUI = {
        phone: '3004567890',
        document: '1234567890'
    };

    /**
     * Test cash payment data.
     */
    const TEST_CASH = {
        document: '1234567890',
        phone: '3001234567',
        email: 'test@payulatam.com'
    };

    /**
     * Initialize the configuration helper.
     */
    const init = function() {
        // Check if we're on the gateway configuration page.
        const $configForm = $('form#mform1');
        const $testModeCheckbox = $configForm.find('input[name="testmode"]');
        
        if ($testModeCheckbox.length > 0) {
            setupConfigPage($testModeCheckbox);
        }
        
        // Check if we're on the payment page.
        const $paymentForm = $('#payu-checkout-form');
        if ($paymentForm.length > 0) {
            setupPaymentPage($paymentForm);
        }
    };

    /**
     * Setup configuration page helpers.
     */
    const setupConfigPage = function($testModeCheckbox) {
        // Add helper section.
        addSandboxHelperSection();
        
        // Handle test mode toggle.
        $testModeCheckbox.on('change', function() {
            if ($(this).is(':checked')) {
                $('#payu-sandbox-helper').slideDown();
                showSandboxPrompt();
            } else {
                $('#payu-sandbox-helper').slideUp();
                $('#payu-sandbox-indicator').remove();
            }
        });
        
        // Check initial state.
        if ($testModeCheckbox.is(':checked')) {
            $('#payu-sandbox-helper').show();
            highlightSandboxMode();
        }
    };

    /**
     * Add sandbox helper section.
     */
    const addSandboxHelperSection = function() {
        const $testModeRow = $('input[name="testmode"]').closest('.form-group, .fitem');
        
        const helperHtml = `
            <div id="payu-sandbox-helper" class="alert alert-info mt-3" style="display: none;">
                <h4><i class="fa fa-flask"></i> Sandbox Mode - Test Credentials</h4>
                <p>These are the official PayU test credentials for Colombia sandbox environment:</p>
                <div class="row">
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">Merchant ID:</dt>
                            <dd class="col-sm-8"><code>${SANDBOX_CREDENTIALS.merchantid}</code></dd>
                            <dt class="col-sm-4">Account ID:</dt>
                            <dd class="col-sm-8"><code>${SANDBOX_CREDENTIALS.payuaccountid}</code></dd>
                        </dl>
                    </div>
                    <div class="col-md-6">
                        <dl class="row">
                            <dt class="col-sm-4">API Login:</dt>
                            <dd class="col-sm-8"><code>${SANDBOX_CREDENTIALS.apilogin}</code></dd>
                            <dt class="col-sm-4">API Key:</dt>
                            <dd class="col-sm-8"><code>${SANDBOX_CREDENTIALS.apikey}</code></dd>
                        </dl>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" id="payu-autofill-sandbox">
                    <i class="fa fa-magic"></i> Auto-fill Sandbox Credentials
                </button>
                <button type="button" class="btn btn-secondary ml-2" id="payu-copy-credentials">
                    <i class="fa fa-clipboard"></i> Copy Credentials
                </button>
            </div>
        `;
        
        $testModeRow.after(helperHtml);
        
        // Setup button handlers.
        $('#payu-autofill-sandbox').on('click', autofillSandboxCredentials);
        $('#payu-copy-credentials').on('click', copySandboxCredentials);
    };

    /**
     * Show prompt to auto-fill credentials.
     */
    const showSandboxPrompt = function() {
        const $merchantId = $('input[name="merchantid"]');
        const $accountId = $('input[name="payuaccountid"]');
        const $apiLogin = $('input[name="apilogin"]');
        const $apiKey = $('input[name="apikey"]');
        
        // Check if fields are empty.
        if (!$merchantId.val() && !$accountId.val() && !$apiLogin.val() && !$apiKey.val()) {
            if (confirm('Would you like to auto-fill the sandbox test credentials?')) {
                autofillSandboxCredentials();
            }
        }
    };

    /**
     * Auto-fill sandbox credentials.
     */
    const autofillSandboxCredentials = function() {
        $('input[name="merchantid"]').val(SANDBOX_CREDENTIALS.merchantid);
        $('input[name="payuaccountid"]').val(SANDBOX_CREDENTIALS.payuaccountid);
        $('input[name="apilogin"]').val(SANDBOX_CREDENTIALS.apilogin);
        $('input[name="apikey"]').val(SANDBOX_CREDENTIALS.apikey);
        
        // Unmask password field if needed.
        const $apiKeyField = $('input[name="apikey"]');
        const $unmaskButton = $apiKeyField.siblings('.unmask');
        if ($unmaskButton.length && $apiKeyField.attr('type') === 'password') {
            $unmaskButton.trigger('click');
        }
        
        Notification.addNotification({
            message: 'Sandbox credentials have been filled successfully.',
            type: 'success'
        });
        
        highlightSandboxMode();
    };

    /**
     * Copy sandbox credentials to clipboard.
     */
    const copySandboxCredentials = function() {
        const credentials = `
Merchant ID: ${SANDBOX_CREDENTIALS.merchantid}
Account ID: ${SANDBOX_CREDENTIALS.payuaccountid}
API Login: ${SANDBOX_CREDENTIALS.apilogin}
API Key: ${SANDBOX_CREDENTIALS.apikey}
        `.trim();
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(credentials).then(function() {
                Notification.addNotification({
                    message: 'Credentials copied to clipboard!',
                    type: 'success'
                });
            });
        } else {
            // Fallback for older browsers.
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(credentials).select();
            document.execCommand('copy');
            $temp.remove();
            
            Notification.addNotification({
                message: 'Credentials copied to clipboard!',
                type: 'success'
            });
        }
    };

    /**
     * Highlight sandbox mode is active.
     */
    const highlightSandboxMode = function() {
        if (!$('#payu-sandbox-indicator').length) {
            const indicator = `
                <div id="payu-sandbox-indicator" class="alert alert-warning mb-3">
                    <i class="fa fa-exclamation-triangle"></i> 
                    <strong>SANDBOX MODE ACTIVE</strong> - Transactions will be processed in test environment
                </div>
            `;
            $('form#mform1').prepend(indicator);
        }
    };

    /**
     * Setup payment page helpers.
     */
    const setupPaymentPage = function($paymentForm) {
        const isTestMode = $paymentForm.data('testmode') === '1' || 
                          $paymentForm.data('testmode') === true;
        
        if (isTestMode) {
            addTestModeIndicator();
            addTestHelpers();
        }
    };

    /**
     * Add test mode indicator.
     */
    const addTestModeIndicator = function() {
        const indicator = `
            <div class="alert alert-warning mb-3" id="payu-test-indicator">
                <h4><i class="fa fa-flask"></i> Test Mode Active</h4>
                <ul class="mb-0">
                    <li>You are in PayU sandbox environment</li>
                    <li>No real money will be charged</li>
                    <li>Use the test data provided below</li>
                </ul>
            </div>
        `;
        $('#payu-checkout-form').prepend(indicator);
    };

    /**
     * Add test helpers to payment form.
     */
    const addTestHelpers = function() {
        // Credit card test helper.
        const $creditCardSection = $('.payment-method-fields[data-method="creditcard"]');
        if ($creditCardSection.length) {
            addCreditCardTestHelper($creditCardSection);
        }
        
        // PSE test helper.
        const $pseSection = $('.payment-method-fields[data-method="pse"]');
        if ($pseSection.length) {
            addPSETestHelper($pseSection);
        }
        
        // Nequi test helper.
        const $nequiSection = $('.payment-method-fields[data-method="nequi"]');
        if ($nequiSection.length) {
            addNequiTestHelper($nequiSection);
        }
        
        // Cash test helper.
        const $cashSection = $('.payment-method-fields[data-method="cash"]');
        if ($cashSection.length) {
            addCashTestHelper($cashSection);
        }
    };

    /**
     * Add credit card test helper.
     */
    const addCreditCardTestHelper = function($section) {
        const testHelper = `
            <div class="alert alert-info test-helper">
                <h5><i class="fa fa-credit-card"></i> Test Credit Cards</h5>
                <div class="form-group">
                    <select class="form-control test-card-selector">
                        <option value="">-- Select a test card --</option>
                        <option value="VISA_APPROVED">Visa (Approved)</option>
                        <option value="MASTERCARD_APPROVED">Mastercard (Approved)</option>
                        <option value="AMEX_APPROVED">American Express (Approved)</option>
                        <option value="DINERS_APPROVED">Diners (Approved)</option>
                        <option value="VISA_DECLINED">Visa (Declined)</option>
                        <option value="VISA_INSUFFICIENT">Visa (Insufficient Funds)</option>
                    </select>
                </div>
                <button type="button" class="btn btn-sm btn-primary fill-test-card">
                    <i class="fa fa-magic"></i> Fill Test Card
                </button>
            </div>
        `;
        
        $section.prepend(testHelper);
        
        // Handle test card filling.
        $section.find('.fill-test-card').on('click', function() {
            const selectedCard = $section.find('.test-card-selector').val();
            if (selectedCard && TEST_CARDS[selectedCard]) {
                fillCreditCardData(TEST_CARDS[selectedCard]);
            }
        });
        
        // Auto-fill on selection.
        $section.find('.test-card-selector').on('change', function() {
            const selectedCard = $(this).val();
            if (selectedCard && TEST_CARDS[selectedCard]) {
                fillCreditCardData(TEST_CARDS[selectedCard]);
            }
        });
    };

    /**
     * Fill credit card test data.
     */
    const fillCreditCardData = function(cardData) {
        $('#cardholder').val(cardData.name);
        $('#cardnumber').val(cardData.number);
        $('#expmonth').val(cardData.expMonth);
        $('#expyear').val(cardData.expYear);
        $('#cvv').val(cardData.cvv);
        $('#cardnetwork').val(cardData.network);
        $('#cc_phone').val('3001234567');
        $('#cc_documentnumber').val('1234567890');
        
        Notification.addNotification({
            message: 'Test card data filled',
            type: 'info'
        });
    };

    /**
     * Add PSE test helper.
     */
    const addPSETestHelper = function($section) {
        const testHelper = `
            <div class="alert alert-info test-helper">
                <h5><i class="fa fa-university"></i> Test PSE Bank Transfer</h5>
                <p>Click the button to fill test PSE data:</p>
                <button type="button" class="btn btn-sm btn-primary fill-test-pse">
                    <i class="fa fa-magic"></i> Fill Test PSE Data
                </button>
            </div>
        `;
        
        $section.prepend(testHelper);
        
        $section.find('.fill-test-pse').on('click', function() {
            fillPSEData();
        });
    };

    /**
     * Fill PSE test data.
     */
    const fillPSEData = function() {
        $('#psebank').val(TEST_PSE.bank);
        $('#pseusertype').val(TEST_PSE.userType);
        $('#pse_documentnumber').val(TEST_PSE.document);
        $('#pse_phone').val(TEST_PSE.phone);
        $('#pse_email').val(TEST_PSE.email);
        $('#cardholder').val(TEST_PSE.name);
        
        Notification.addNotification({
            message: 'Test PSE data filled',
            type: 'info'
        });
    };

    /**
     * Add Nequi test helper.
     */
    const addNequiTestHelper = function($section) {
        const testHelper = `
            <div class="alert alert-info test-helper">
                <h5><i class="fa fa-mobile"></i> Test Nequi Payment</h5>
                <p>Use test phone number starting with 300:</p>
                <button type="button" class="btn btn-sm btn-primary fill-test-nequi">
                    <i class="fa fa-magic"></i> Fill Test Nequi Data
                </button>
            </div>
        `;
        
        $section.prepend(testHelper);
        
        $section.find('.fill-test-nequi').on('click', function() {
            fillNequiData();
        });
    };

    /**
     * Fill Nequi test data.
     */
    const fillNequiData = function() {
        $('#nequi_phone').val(TEST_NEQUI.phone);
        $('#nequi_documentnumber').val(TEST_NEQUI.document);
        
        Notification.addNotification({
            message: 'Test Nequi data filled',
            type: 'info'
        });
    };

    /**
     * Add cash payment test helper.
     */
    const addCashTestHelper = function($section) {
        const testHelper = `
            <div class="alert alert-info test-helper">
                <h5><i class="fa fa-money"></i> Test Cash Payment</h5>
                <p>Fill test data for cash payment simulation:</p>
                <button type="button" class="btn btn-sm btn-primary fill-test-cash">
                    <i class="fa fa-magic"></i> Fill Test Cash Data
                </button>
            </div>
        `;
        
        $section.prepend(testHelper);
        
        $section.find('.fill-test-cash').on('click', function() {
            fillCashData();
        });
    };

    /**
     * Fill cash payment test data.
     */
    const fillCashData = function() {
        $('#cash_documentnumber').val(TEST_CASH.document);
        $('#cash_phone').val(TEST_CASH.phone);
        
        Notification.addNotification({
            message: 'Test cash payment data filled',
            type: 'info'
        });
    };

    return {
        init: init,
        getSandboxCredentials: function() {
            return SANDBOX_CREDENTIALS;
        },
        getTestCards: function() {
            return TEST_CARDS;
        }
    };
});