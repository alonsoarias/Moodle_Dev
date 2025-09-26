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
 * Lightweight widget controller for the local_chatbot plugin.
 *
 * @module     local_chatbot/chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    'use strict';

    /**
     * Build an element from template string.
     *
     * @param {string} html
     * @return {JQuery}
     */
    const createElement = function(html) {
        return $(html.trim());
    };

    /**
     * Format a timestamp for display.
     *
     * @param {number} timestamp
     * @return {string}
     */
    const formatTime = function(timestamp) {
        const date = new Date(timestamp * 1000);
        return date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    };

    /**
     * Controller class for the floating widget.
     */
    class ChatbotWidget {
        /**
         * @param {Object} config
         */
        constructor(config) {
            this.config = config;
            this.isOpen = false;
            this.sessionid = config.sessionid;
            this.maxlength = parseInt(config.maxlength, 10) || 500;
            this.initialised = false;
            this.typingTimer = null;
        }

        /**
         * Entry point.
         */
        init() {
            this.cacheNodes();
            if (!this.$container.length) {
                return;
            }
            this.initialised = true;
            this.bindEvents();
            this.restoreLauncherState();
            this.updateCharCount();
            this.addWelcomeMessage();
            this.loadHistory();
        }

        /**
         * Cache frequently used DOM nodes.
         */
        cacheNodes() {
            this.$container = $('#local-chatbot-container');
            this.$widget = $('#chatbot-widget');
            this.$launcher = $('#chatbot-launcher');
            this.$messages = $('#chatbot-messages');
            this.$input = $('#chatbot-input');
            this.$send = $('#chatbot-send');
            this.$typing = $('#chatbot-typing-indicator');
            this.$badge = $('.chatbot-launcher-count');
            this.$charcount = $('#char-count');
            this.$quickActions = $('#chatbot-quick-actions');
            this.$suggestions = $('#chatbot-suggestions');
        }

        /**
         * Register DOM listeners.
         */
        bindEvents() {
            const self = this;

            this.$launcher.on('click', function() {
                self.toggle();
            });

            this.$send.on('click', function() {
                self.sendMessage();
            });

            this.$input.on('keydown', function(event) {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    self.sendMessage();
                }
            });

            this.$input.on('input', function() {
                self.updateCharCount();
                self.autoResizeTextarea();
            });

            this.$widget.on('click', '.chatbot-btn-close, .chatbot-btn-minimize', function() {
                self.close();
            });

            this.$widget.on('click', '.chatbot-btn-export', function() {
                self.exportConversation();
            });

            this.$widget.on('click', '.suggestion-chip', function() {
                const text = $(this).data('text');
                const action = $(this).data('action');
                if (action) {
                    self.executeAction(action);
                } else {
                    self.$input.val(text);
                    self.sendMessage();
                }
            });

            this.$widget.on('click', '.quick-action-btn', function() {
                const action = $(this).data('action');
                const url = $(this).data('url');
                if (action === 'navigate' && url) {
                    window.location.href = url;
                    return;
                }
                self.executeAction(action);
            });
        }

        /**
         * Restore launcher state from localStorage.
         */
        restoreLauncherState() {
            let state = null;
            try {
                state = window.localStorage.getItem(this.config.storagekey);
            } catch (e) {
                state = null;
            }

            if (state === 'open') {
                this.open();
            } else {
                this.close();
            }
        }

        /**
         * Persist launcher state.
         *
         * @param {string} state
         */
        persistState(state) {
            try {
                window.localStorage.setItem(this.config.storagekey, state);
            } catch (e) {
                // Ignore quota errors (e.g. private browsing).
            }
        }

        /**
         * Toggle widget visibility.
         */
        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        /**
         * Open the widget.
         */
        open() {
            this.$container.addClass('chatbot-active').show();
            this.$widget.attr('aria-hidden', 'false');
            this.$launcher.attr('aria-expanded', 'true');
            this.isOpen = true;
            this.$input.focus();
            this.scrollToBottom();
            this.clearBadge();
            this.persistState('open');
        }

        /**
         * Close the widget.
         */
        close() {
            this.$container.removeClass('chatbot-active').show();
            this.$widget.attr('aria-hidden', 'true');
            this.$launcher.attr('aria-expanded', 'false');
            this.isOpen = false;
            this.persistState('closed');
        }

        /**
         * Add welcome message when widget loads.
         */
        addWelcomeMessage() {
            const welcome = $('#local-chatbot-welcome').val();
            if (welcome) {
                this.addMessage('bot', welcome, {system: true});
            }
        }

        /**
         * Load conversation history from the server.
         */
        loadHistory() {
            const self = this;
            Ajax.call([{
                methodname: 'local_chatbot_get_history',
                args: {
                    sessionid: this.sessionid,
                    limit: 20,
                },
                done: function(history) {
                    if (!history || !history.length) {
                        return;
                    }
                    history.forEach(function(entry) {
                        self.addMessage('user', entry.message, {historical: true});
                        self.addMessage('bot', entry.response, {historical: true, intent: entry.intent});
                    });
                },
                fail: Notification.exception,
            }]);
        }

        /**
         * Send the message currently in the textbox.
         */
        sendMessage() {
            const message = this.$input.val().trim();
            if (!message) {
                return;
            }
            if (message.length > this.maxlength) {
                this.$input.val(message.slice(0, this.maxlength));
            }

            this.addMessage('user', message);
            this.$input.val('').trigger('input');
            this.showTyping();

            const self = this;
            Ajax.call([{
                methodname: 'local_chatbot_process_message',
                args: {
                    message: message,
                    sessionid: this.sessionid,
                    context: '{}',
                },
                done: function(response) {
                    self.sessionid = response.sessionid;
                    self.hideTyping();
                    self.addMessage('bot', response.response, {
                        intent: response.intent,
                        logid: response.logid,
                        responseTime: response.response_time,
                    });
                    self.renderSuggestions(response.suggestions || []);
                    self.renderQuickActions(response.actions || []);
                },
                fail: function(error) {
                    self.hideTyping();
                    self.addMessage('bot', self.config.strings.error || 'There was an error.');
                    Notification.exception(error);
                },
            }]);
        }

        /**
         * Execute a remote quick action.
         *
         * @param {string} action
         */
        executeAction(action) {
            const self = this;
            Ajax.call([{
                methodname: 'local_chatbot_execute_action',
                args: {
                    action: action,
                    params: '{}',
                },
                done: function(response) {
                    if (response.message) {
                        self.addMessage('bot', response.message);
                    }
                },
                fail: Notification.exception,
            }]);
        }

        /**
         * Export the current conversation.
         */
        exportConversation() {
            const self = this;
            Ajax.call([{
                methodname: 'local_chatbot_export_conversation',
                args: {
                    sessionid: this.sessionid,
                    format: 'html',
                },
                done: function(data) {
                    const blob = new Blob([data.content], {type: 'text/html'});
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'chatbot-conversation.html';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    URL.revokeObjectURL(url);
                },
                fail: Notification.exception,
            }]);
        }

        /**
         * Render suggestion chips.
         *
         * @param {Array} suggestions
         */
        renderSuggestions(suggestions) {
            const container = this.$suggestions.find('.suggestions-container');
            container.empty();

            if (!suggestions.length) {
                this.$suggestions.hide();
                return;
            }

            suggestions.forEach(function(suggestion) {
                const chip = createElement(
                    '<button type="button" class="suggestion-chip"></button>'
                );
                chip.text((suggestion.icon || '') + ' ' + suggestion.text);
                chip.attr('data-text', suggestion.text);
                chip.attr('data-action', suggestion.action || '');
                container.append(chip);
            });

            this.$suggestions.show();
        }

        /**
         * Render quick action buttons.
         *
         * @param {Array} actions
         */
        renderQuickActions(actions) {
            const container = this.$quickActions.find('.quick-actions-container');
            container.empty();

            if (!actions.length) {
                this.$quickActions.hide();
                return;
            }

            actions.forEach(function(action) {
                const button = createElement(
                    '<button type="button" class="quick-action-btn"></button>'
                );
                button.attr('data-action', action.action);
                button.attr('data-url', action.url || '');
                button.html('<span class="quick-action-icon">' + (action.icon || '') + '</span>' +
                    '<span class="quick-action-label">' + action.label + '</span>');
                container.append(button);
            });

            this.$quickActions.show();
        }

        /**
         * Append a message bubble to the conversation.
         *
         * @param {string} sender
         * @param {string} message
         * @param {Object} [meta]
         */
        addMessage(sender, message, meta) {
            meta = meta || {};
            const messageId = meta.logid ? 'log-' + meta.logid : 'msg-' + Date.now();
            const classes = ['chatbot-message'];
            classes.push(sender === 'user' ? 'chatbot-message-user' : 'chatbot-message-bot');
            if (meta.system) {
                classes.push('chatbot-message-system');
            }

            const avatar = sender === 'user' ? this.config.avatar : '🤖';
            const header = sender === 'user' ? this.config.username : this.config.strings.title;
            const timestamp = meta.timestamp ? formatTime(meta.timestamp) : formatTime(Math.floor(Date.now() / 1000));

            const template = `
                <div class="${classes.join(' ')}" data-id="${messageId}">
                    <div class="message-avatar" aria-hidden="true">${avatar}</div>
                    <div class="message-content-wrapper">
                        <div class="message-header">
                            <span class="message-author">${header}</span>
                            <span class="message-time">${timestamp}</span>
                        </div>
                        <div class="message-content">${this.escape(message)}</div>
                        ${meta.intent ? `<div class="message-intent">${meta.intent}</div>` : ''}
                    </div>
                </div>`;

            const $message = createElement(template);
            this.$messages.append($message);
            this.scrollToBottom();

            if (!this.isOpen && sender === 'bot') {
                this.incrementBadge();
            }
        }

        /**
         * Escape HTML entities.
         *
         * @param {string} text
         * @return {string}
         */
        escape(text) {
            return $('<div>').text(text).html().replace(/\n/g, '<br>');
        }

        /**
         * Scroll the conversation pane to the bottom.
         */
        scrollToBottom() {
            this.$messages.scrollTop(this.$messages.prop('scrollHeight'));
        }

        /**
         * Display typing indicator.
         */
        showTyping() {
            this.$typing.show();
            this.scrollToBottom();
        }

        /**
         * Hide typing indicator.
         */
        hideTyping() {
            this.$typing.hide();
        }

        /**
         * Automatically resize the textarea.
         */
        autoResizeTextarea() {
            this.$input.outerHeight(0);
            const newHeight = Math.min(this.$input.prop('scrollHeight'), 120);
            this.$input.outerHeight(newHeight);
        }

        /**
         * Update the character counter.
         */
        updateCharCount() {
            const length = this.$input.val().length;
            this.$charcount.text(length);
        }

        /**
         * Increase unread badge counter.
         */
        incrementBadge() {
            const current = parseInt(this.$badge.attr('data-count'), 10) || 0;
            const next = current + 1;
            this.$badge.attr('data-count', next);
            this.$badge.text(next).attr('aria-hidden', next ? 'false' : 'true');
        }

        /**
         * Clear unread badge.
         */
        clearBadge() {
            this.$badge.attr('data-count', 0);
            this.$badge.text('0').attr('aria-hidden', 'true');
        }
    }

    return {
        /**
         * Initialise the widget.
         *
         * @param {Object} config
         */
        init: function(config) {
            const widget = new ChatbotWidget(config || {});
            widget.init();
        }
    };
});
