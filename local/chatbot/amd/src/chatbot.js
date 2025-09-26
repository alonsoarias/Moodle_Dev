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
     * Return the current timestamp in seconds.
     *
     * @return {number}
     */
    const nowInSeconds = function() {
        return Math.floor(Date.now() / 1000);
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
            this.quickActions = {};
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
                const mode = $(this).data('mode');
                const target = $(this).data('target');

                if (mode === 'action' && target) {
                    self.handleQuickAction(target);
                } else {
                    const message = target || text;
                    self.$input.val(message);
                    self.sendMessage();
                }
            });

            this.$widget.on('click', '.quick-action-btn', function() {
                const key = $(this).data('key');
                self.handleQuickAction(key, $(this));
            });

            this.$widget.on('click', '.feedback-btn', function() {
                const $btn = $(this);
                const $wrapper = $btn.closest('.message-feedback');
                const logid = $wrapper.data('logid');
                const feedback = $btn.data('feedback');
                
                self.submitFeedback(logid, feedback);
                $wrapper.find('.feedback-btn').removeClass('active');
                $btn.addClass('active');
            });
        }

        /**
         * Send message to server.
         */
        sendMessage() {
            const message = this.$input.val().trim();
            if (!message) {
                return;
            }
            if (message.length > this.maxlength) {
                this.$input.val(message.slice(0, this.maxlength));
            }

            this.addMessage('user', message, {timestamp: nowInSeconds()});
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
                        timestamp: response.timestamp,
                    });
                    self.renderSuggestions(response.suggestions || []);
                    self.renderQuickActions(response.actions || []);
                },
                fail: function(error) {
                    self.hideTyping();
                    self.addMessage('bot', self.config.strings.error || 'There was an error.', {
                        timestamp: nowInSeconds()
                    });
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
                        self.addMessage('bot', response.message, {timestamp: nowInSeconds()});
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
         * Submit feedback for a message.
         *
         * @param {number} logid
         * @param {string} feedback
         */
        submitFeedback(logid, feedback) {
            Ajax.call([{
                methodname: 'local_chatbot_feedback',
                args: {
                    logid: logid,
                    feedback: feedback,
                },
                done: function() {
                    // Silently succeed.
                },
                fail: function() {
                    // Silently fail.
                },
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
                chip.attr('data-mode', suggestion.mode || 'message');
                chip.attr('data-target', suggestion.target || '');
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
            this.quickActions = {};

            if (!actions.length) {
                this.$quickActions.hide();
                return;
            }

            const self = this;
            actions.forEach(function(action) {
                self.quickActions[action.actionkey] = action;
                const button = createElement(
                    '<button type="button" class="quick-action-btn"></button>'
                );
                button.attr('data-key', action.actionkey);
                button.attr('data-type', action.type || 'navigate');
                button.attr('data-url', action.url || '');
                button.attr('title', action.description || '');
                button.html('<span class="quick-action-icon">' + (action.icon || '') + '</span>' +
                    '<span class="quick-action-label">' + action.label + '</span>');
                container.append(button);
            });

            this.$quickActions.show();
        }

        /**
         * Execute quick action locally or via AJAX.
         *
         * @param {string} key
         * @param {JQuery} [$button]
         */
        handleQuickAction(key, $button) {
            if (!key) {
                return;
            }

            const action = this.quickActions[key];
            if (action) {
                if (action.type === 'navigate' && action.url) {
                    window.location.href = action.url;
                    return;
                }

                if (action.type === 'inject' && action.message) {
                    this.$input.val(action.message);
                    this.sendMessage();
                    return;
                }
            }

            this.executeAction(key);
        }

        /**
         * Append a message bubble to the conversation.
         * 
         * @param {string} sender 'user' or 'bot'
         * @param {string} text Message text
         * @param {Object} metadata Optional metadata
         */
        addMessage(sender, text, metadata) {
            metadata = metadata || {};

            const messageWrapper = createElement('<div class="chatbot-message chatbot-message-' + sender + '"></div>');

            if (sender === 'bot') {
                const avatar = createElement('<div class="message-avatar bot-avatar"><span>🤖</span></div>');
                messageWrapper.append(avatar);
            }

            const contentWrapper = createElement('<div class="message-content-wrapper"></div>');

            if (sender === 'user') {
                const header = createElement('<div class="message-header"></div>');
                header.append('<span class="message-sender">' + this.config.username + '</span>');
                header.append('<span class="message-time">' + this.getMessageTime(metadata) + '</span>');
                contentWrapper.append(header);
            }

            const content = createElement('<div class="message-content"></div>');
            content.text(text);
            contentWrapper.append(content);

            if (sender === 'bot' && metadata.intent) {
                const intent = createElement('<div class="message-intent">Intent: ' + metadata.intent + '</div>');
                contentWrapper.append(intent);
            }

            if (sender === 'bot' && metadata.logid) {
                const feedback = createElement('<div class="message-feedback" data-logid="' + metadata.logid + '"></div>');
                feedback.html(
                    '<span>Was this helpful?</span>' +
                    '<button class="feedback-btn" data-feedback="helpful" title="Helpful">👍</button>' +
                    '<button class="feedback-btn" data-feedback="not_helpful" title="Not helpful">👎</button>'
                );
                contentWrapper.append(feedback);
            }

            if (sender === 'user') {
                const avatar = createElement('<div class="message-avatar user-avatar"><span>' +
                    this.config.avatar + '</span></div>');
                messageWrapper.append(contentWrapper);
                messageWrapper.append(avatar);
            } else {
                messageWrapper.append(contentWrapper);
            }

            this.$messages.append(messageWrapper);
            this.scrollToBottom();

            if (sender === 'bot' && !this.isOpen && !metadata.history) {
                this.incrementBadge();
            }
        }
        
        /**
         * Show typing indicator.
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
         * Scroll messages to bottom.
         */
        scrollToBottom() {
            if (!this.$messages.length) {
                return;
            }
            const messages = this.$messages[0];
            if (messages) {
                messages.scrollTop = messages.scrollHeight;
            }
        }
        
        /**
         * Update character count display.
         */
        updateCharCount() {
            const current = this.$input.length ? this.$input.val().length : 0;
            const $current = $('#char-current');
            const $charcount = this.$charcount;

            $current.text(current);

            if (current > this.maxlength * 0.9) {
                $charcount.addClass('warning');
            } else {
                $charcount.removeClass('warning');
            }
        }

        /**
         * Auto-resize textarea based on content.
         */
        autoResizeTextarea() {
            if (!this.$input.length) {
                return;
            }
            const textarea = this.$input[0];
            if (!textarea) {
                return;
            }
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }
        
        /**
         * Restore launcher state from localStorage.
         */
        restoreLauncherState() {
            let state = null;
            if (window.localStorage) {
                state = localStorage.getItem(this.config.storagekey);
            }

            if (state === 'open') {
                this.open();
            }

            this.$container.show();
        }
        
        /**
         * Toggle widget open/closed.
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
            this.isOpen = true;
            this.$container.addClass('chatbot-active');
            this.$widget.attr('aria-hidden', 'false');
            this.$input.focus();
            this.resetBadge();

            if (window.localStorage) {
                localStorage.setItem(this.config.storagekey, 'open');
            }
        }
        
        /**
         * Close the widget.
         */
        close() {
            this.isOpen = false;
            this.$container.removeClass('chatbot-active');
            this.$widget.attr('aria-hidden', 'true');

            if (window.localStorage) {
                localStorage.setItem(this.config.storagekey, 'closed');
            }
        }

        /**
         * Add welcome message if first visit.
         */
        addWelcomeMessage() {
            let welcomeShown = false;
            if (window.sessionStorage) {
                welcomeShown = sessionStorage.getItem('chatbot_welcome_shown') === 'true';
            }

            if (welcomeShown) {
                return;
            }

            const welcomeText = $('#local-chatbot-welcome').val();
            if (welcomeText) {
                const parsedWelcome = welcomeText.replace('{name}', this.config.username);
                this.addMessage('bot', parsedWelcome, {history: true});
                if (window.sessionStorage) {
                    sessionStorage.setItem('chatbot_welcome_shown', 'true');
                }
            }
        }
        
        /**
         * Load conversation history.
         */
        loadHistory() {
            const self = this;
            Ajax.call([{
                methodname: 'local_chatbot_get_history',
                args: {
                    sessionid: this.sessionid,
                    limit: 10
                },
                done: function(history) {
                    if (history.length > 0) {
                        const separator = createElement('<div class="history-separator">Previous messages</div>');
                        self.$messages.prepend(separator);
                        
                        history.forEach(function(item) {
                            const metadata = {timestamp: item.timestamp, history: true};
                            self.addMessage('user', item.message, metadata);
                            self.addMessage('bot', item.response, {
                                intent: item.intent,
                                timestamp: item.timestamp,
                                history: true
                            });
                        });
                    }
                },
                fail: function() {
                    // Silently fail if history cannot be loaded.
                }
            }]);
        }

        /**
         * Return formatted message time.
         *
         * @param {Object} metadata
         * @return {string}
         */
        getMessageTime(metadata) {
            const timestamp = metadata.timestamp || nowInSeconds();
            return formatTime(timestamp);
        }

        /**
         * Increase launcher badge count.
         */
        incrementBadge() {
            if (!this.$badge.length) {
                return;
            }
            const current = parseInt(this.$badge.attr('data-count') || this.$badge.text() || '0', 10);
            const next = current + 1;
            this.$badge.attr('data-count', next);
            this.$badge.text(next);
            this.$badge.attr('aria-hidden', next ? 'false' : 'true');
        }

        /**
         * Reset launcher badge state.
         */
        resetBadge() {
            if (!this.$badge.length) {
                return;
            }
            this.$badge.attr('data-count', '0');
            this.$badge.text('0');
            this.$badge.attr('aria-hidden', 'true');
        }
    }
    
    /**
     * Module initialization.
     * 
     * @param {Object} config
     */
    const init = function(config) {
        const widget = new ChatbotWidget(config);
        widget.init();
    };
    
    return {
        init: init
    };
});
