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
 * Nexo Bot widget JavaScript.
 *
 * @module     local_educambot/widget
 * @copyright  2025 EducamBot Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var chat = {
        /**
         * Initialize the widget.
         */
        init: function() {
            // Don't initialize in embedded layouts.
            if ($('body.pagelayout-embedded').length) {
                return;
            }

            var educambotchat = $('#educambot-chat');

            // Hide in maintenance or embedded layouts.
            if ($('.pagelayout-embedded, .pagelayout-maintenance').length) {
                educambotchat.hide();
                educambotchat.remove();
                return;
            }

            // Show widget with animation.
            educambotchat.show(200);

            var popup = educambotchat.find('.educambot-popup');
            var btn = $('#educambot-btn');
            var closeBtn = $('#educambot-close');
            var clearBtn = $('#educambot-clear');
            var textarea = $('#educambot-textarea');
            var sendBtn = $('#educambot-send');
            var messages = $('#educambot-messages');
            var loading = $('#educambot-loading');

            var serviceUrl = educambotchat.data('serviceurl');
            var sesskey = educambotchat.data('sesskey');

            // Toggle chat open/close.
            btn.on('click', function() {
                educambotchat.toggleClass('educambot-active');
                if (educambotchat.hasClass('educambot-active')) {
                    localStorage.setItem('educambot-isopen', 'true');
                    textarea.focus();
                } else {
                    localStorage.removeItem('educambot-isopen');
                }
            });

            // Close button.
            closeBtn.on('click', function(e) {
                e.preventDefault();
                educambotchat.removeClass('educambot-active');
                localStorage.removeItem('educambot-isopen');
            });

            // Clear history.
            clearBtn.on('click', function(e) {
                e.preventDefault();
                messages.find('.educambot-message').not(':first').remove();
            });

            // Auto-resize textarea.
            textarea.on('input', function() {
                this.style.height = '34px';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Send on Enter (Shift+Enter for new line).
            textarea.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Send on button click.
            sendBtn.on('click', function() {
                sendMessage();
            });

            // Restore state from localStorage.
            if (localStorage.getItem('educambot-isopen') === 'true') {
                educambotchat.addClass('educambot-active');
            }

            /**
             * Send a message to the bot.
             */
            function sendMessage() {
                var question = textarea.val().trim();

                if (question.length < 2) {
                    return;
                }

                // Add user message to chat.
                addMessage(question, 'user');

                // Clear input.
                textarea.val('');
                textarea.css('height', '34px');

                // Show loading.
                loading.show();

                // Send AJAX request.
                $.ajax({
                    url: serviceUrl,
                    type: 'POST',
                    data: {
                        sesskey: sesskey,
                        question: question
                    },
                    dataType: 'json',
                    success: function(data) {
                        loading.hide();

                        if (data.success && data.response) {
                            addMessage(data.response, 'bot', data.confidence, data.options);
                        } else if (data.error) {
                            addMessage(data.error, 'bot error');
                        } else {
                            addMessage('Error: No response received.', 'bot error');
                        }
                    },
                    error: function() {
                        loading.hide();
                        addMessage('Error: Could not connect to server.', 'bot error');
                    }
                });
            }

            /**
             * Handle option button click.
             *
             * @param {object} option - Option object with targetpattern
             */
            function handleOptionClick(option) {
                // Disable all option buttons in the chat.
                messages.find('.educambot-option-btn').prop('disabled', true).addClass('disabled');

                if (option.targetpattern) {
                    textarea.val(option.targetpattern);
                    sendMessage();
                }
            }

            /**
             * Add a message to the chat.
             *
             * @param {string} text - Message text
             * @param {string} sender - 'user' or 'bot' or 'bot error'
             * @param {number} confidence - Confidence score (0-1)
             * @param {array} options - Quick reply options
             */
            function addMessage(text, sender, confidence, options) {
                var isError = sender.indexOf('error') !== -1;
                var senderClass = sender.replace(' error', '');

                var messageDiv = $('<div>')
                    .addClass('educambot-message')
                    .addClass('educambot-' + senderClass);

                if (isError) {
                    messageDiv.addClass('educambot-error');
                }

                var contentDiv = $('<div>')
                    .addClass('educambot-message-content')
                    .html(text);

                messageDiv.append(contentDiv);

                // Add confidence indicator for bot messages.
                if (senderClass === 'bot' && !isError && typeof confidence !== 'undefined' && confidence > 0) {
                    var confidencePercent = Math.round(confidence * 100);
                    var confidenceDiv = $('<div>')
                        .addClass('educambot-confidence')
                        .text(confidencePercent + '%');
                    messageDiv.append(confidenceDiv);
                }

                // Add quick reply options if present.
                if (senderClass === 'bot' && !isError && options && options.length > 0) {
                    var optionsDiv = $('<div>').addClass('educambot-options');

                    options.forEach(function(option) {
                        var btnText = option.icon ? option.icon + ' ' + option.text : option.text;
                        var btn = $('<button>')
                            .addClass('educambot-option-btn')
                            .attr('type', 'button')
                            .text(btnText)
                            .on('click', function() {
                                handleOptionClick(option);
                            });
                        optionsDiv.append(btn);
                    });

                    messageDiv.append(optionsDiv);
                }

                messages.append(messageDiv);

                // Scroll to bottom.
                messages.scrollTop(messages[0].scrollHeight);
            }
        }
    };

    return chat;
});
