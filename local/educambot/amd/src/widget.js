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
 * Educam Bot widget JavaScript.
 *
 * @module     local_educambot/widget
 * @copyright  2025 EducamBot Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    return {
        /**
         * Initialize the widget.
         */
        init: function() {
            var widget = $('#educambot-widget');
            var panel = $('#educambot-panel');
            var toggle = $('#educambot-toggle');
            var close = $('#educambot-close');
            var input = $('#educambot-input');
            var send = $('#educambot-send');
            var messages = $('#educambot-messages');
            var loading = $('#educambot-loading');

            var serviceUrl = widget.data('serviceurl');
            var sesskey = widget.data('sesskey');
            var botname = widget.data('botname');

            var isOpen = false;

            // Toggle panel.
            toggle.on('click', function() {
                if (isOpen) {
                    panel.fadeOut(200);
                    isOpen = false;
                } else {
                    panel.fadeIn(200);
                    isOpen = true;
                    input.focus();
                }
            });

            // Close panel.
            close.on('click', function() {
                panel.fadeOut(200);
                isOpen = false;
            });

            // Auto-resize textarea.
            input.on('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });

            // Send on Enter (but allow Shift+Enter for new line).
            input.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Send on button click.
            send.on('click', function() {
                sendMessage();
            });

            /**
             * Send a message to the bot.
             */
            function sendMessage() {
                var question = input.val().trim();

                if (question.length === 0) {
                    return;
                }

                // Add user message to chat.
                addMessage(question, 'user');

                // Clear input.
                input.val('');
                input.css('height', 'auto');

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
                            addMessage(data.response, 'bot', data.confidence);
                        } else if (data.error) {
                            addMessage(data.error, 'bot', 0);
                        } else {
                            addMessage('Error: No response from server.', 'bot', 0);
                        }
                    },
                    error: function() {
                        loading.hide();
                        addMessage('Error: Could not connect to server. Please try again.', 'bot', 0);
                    }
                });
            }

            /**
             * Add a message to the chat.
             *
             * @param {string} text - Message text
             * @param {string} sender - 'user' or 'bot'
             * @param {number} confidence - Confidence score (0-1)
             */
            function addMessage(text, sender, confidence) {
                var messageDiv = $('<div>')
                    .addClass('educambot-message')
                    .addClass('educambot-' + sender);

                var contentDiv = $('<div>')
                    .addClass('educambot-message-content')
                    .html(text);

                messageDiv.append(contentDiv);

                // Add confidence indicator for bot messages.
                if (sender === 'bot' && typeof confidence !== 'undefined' && confidence > 0) {
                    var confidencePercent = Math.round(confidence * 100);
                    var confidenceDiv = $('<div>')
                        .addClass('educambot-confidence')
                        .text('Confidence: ' + confidencePercent + '%');
                    messageDiv.append(confidenceDiv);
                }

                messages.append(messageDiv);

                // Scroll to bottom.
                messages.scrollTop(messages[0].scrollHeight);
            }
        }
    };
});
