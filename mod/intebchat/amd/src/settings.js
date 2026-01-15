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
 * Settings page JavaScript
 *
 * @module     mod_intebchat/settings
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /**
     * Cached strings for the module.
     * @type {Object}
     */
    var strings = {};

    /**
     * Load required language strings.
     * @return {Promise}
     */
    var loadStrings = function() {
        return Str.get_strings([
            {key: 'noassistantsfound', component: 'mod_intebchat'},
            {key: 'failedtoloadassistants', component: 'mod_intebchat'},
            {key: 'reasoningmodelinfo', component: 'mod_intebchat'},
        ]).then(function(results) {
            strings.noassistantsfound = results[0];
            strings.failedtoloadassistants = results[1];
            strings.reasoningmodelinfo = results[2];
            return strings;
        });
    };

    /**
     * Initialize the settings module.
     */
    var init = function() {
        // Load strings first, then initialize handlers.
        loadStrings().then(function() {
            initializeHandlers();
        }).catch(function() {
            // If strings fail to load, still initialize with fallbacks.
            strings.noassistantsfound = 'No assistants found';
            strings.failedtoloadassistants = 'Failed to fetch assistants';
            strings.reasoningmodelinfo = 'This is an advanced reasoning model with higher cost and response time.';
            initializeHandlers();
        });
    };

    /**
     * Initialize all event handlers.
     */
    var initializeHandlers = function() {
        // Handle global settings API type change.
        $('#id_s_mod_intebchat_type').on('change', function() {
            // If the API Type is changed, programmatically hit save so the page automatically reloads with the new options.
            $('.settingsform').addClass('mod_intebchat');
            $('.settingsform').addClass('disabled');
            $('.settingsform button[type="submit"]').click();
        });

        // Handle instance form API key change for assistant list update.
        var $apikeyField = $('#id_apikey');
        var $assistantSelect = $('#id_assistant');
        var $apitypeSelect = $('#id_apitype');

        if ($apikeyField.length && $assistantSelect.length) {
            var updateAssistantList = function() {
                var apikey = $apikeyField.val();
                var apitype = $apitypeSelect.val() || $apitypeSelect.find('input[type="hidden"]').val();

                if (apikey && apitype === 'assistant') {
                    // Show loading indicator.
                    $assistantSelect.prop('disabled', true);

                    Ajax.call([{
                        methodname: 'mod_intebchat_get_assistants',
                        args: {apikey: apikey},
                        done: function(response) {
                            // Clear current options.
                            $assistantSelect.empty();

                            if (response.assistants && response.assistants.length > 0) {
                                $.each(response.assistants, function(index, assistant) {
                                    $assistantSelect.append(
                                        $('<option></option>')
                                            .attr('value', assistant.id)
                                            .text(assistant.name)
                                    );
                                });
                            } else {
                                $assistantSelect.append(
                                    $('<option></option>')
                                        .attr('value', '')
                                        .text(strings.noassistantsfound)
                                );
                            }

                            $assistantSelect.prop('disabled', false);
                        },
                        fail: function(error) {
                            Notification.addNotification({
                                message: strings.failedtoloadassistants + ': ' + error.message,
                                type: 'error'
                            });
                            $assistantSelect.prop('disabled', false);
                        }
                    }]);
                }
            };

            // Update assistants when API key changes.
            $apikeyField.on('blur', updateAssistantList);

            // Update visibility when API type changes.
            $apitypeSelect.on('change', function() {
                if ($(this).val() === 'assistant') {
                    updateAssistantList();
                }
            });
        }

        // Handle form validation for API-specific fields.
        $('form.mform').on('submit', function(e) {
            var apitype = $('#id_apitype').val() || $('#id_apitype').find('input[type="hidden"]').val();
            var hasErrors = false;

            // Validate Azure fields if Azure is selected.
            if (apitype === 'azure') {
                if (!$('#id_resourcename').val()) {
                    $('#id_error_resourcename').text('Required').show();
                    hasErrors = true;
                }
                if (!$('#id_deploymentid').val()) {
                    $('#id_error_deploymentid').text('Required').show();
                    hasErrors = true;
                }
            }

            if (hasErrors) {
                e.preventDefault();
                return false;
            }
        });

        // Alert when a reasoning model is selected.
        var $modelSelect = $('#id_model');
        if ($modelSelect.length) {
            // Check initial value.
            var checkReasoningModel = function(val) {
                if (val && (val.includes('o1') || val.includes('o3'))) {
                    return true;
                }
                return false;
            };

            // Function to show reasoning model alert.
            var showReasoningModelAlert = function(modelName) {
                // Replace placeholder with model name.
                var message = strings.reasoningmodelinfo.replace('{$a}', modelName);
                Notification.addNotification({
                    message: message,
                    type: 'info'
                });
            };

            // Check on change.
            $modelSelect.on('change', function() {
                var val = $(this).val();
                if (checkReasoningModel(val)) {
                    showReasoningModelAlert(val);
                }
            });
        }

        // Also handle for global settings model select.
        var $globalModelSelect = $('#id_s_mod_intebchat_model');
        if ($globalModelSelect.length) {
            $globalModelSelect.on('change', function() {
                var val = $(this).val();
                if (val && (val.includes('o1') || val.includes('o3'))) {
                    var message = strings.reasoningmodelinfo.replace('{$a}', val);
                    Notification.addNotification({
                        message: message,
                        type: 'info'
                    });
                }
            });
        }
    };

    return {
        init: init
    };
});
