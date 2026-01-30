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
 * udesbot widget JavaScript (v3.8.0).
 *
 * Major rewrite to fix event handling inconsistencies.
 *
 * Features:
 * - Unified event delegation system (no duplicate handlers)
 * - Retry mechanism with exponential backoff
 * - Session ID for context synchronization
 * - Theme colors via CSS custom properties
 * - Conversation history persistence
 * - Inactivity timeout
 * - Role-based behavior
 * - Mascot animations
 * - Typing indicator
 * - Message timestamps
 * - Feedback system
 * - Export conversation
 * - Sound notifications
 * - Cross-tab conversation sync
 * - Scroll-to-bottom button (v3.8.0)
 * - Dark mode support (v3.8.0)
 * - Character counter (v3.8.0)
 * - Improved accessibility (v3.8.0)
 * - Keyboard shortcuts indicator (v3.8.0)
 * - Message time grouping (v3.8.0)
 *
 * @module     local_udesbot/widget
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @copyright  2025 OrionCloud<https://orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, Ajax) {

    var chat = {
        // ==============================================
        // Configuration Properties
        // ==============================================

        // Mascot state management.
        mascot: null,
        tooltip: null,
        tooltipTimer: null,
        suggestionTimer: null,
        lastUserQuestion: '',

        // Inactivity management.
        inactivityTimer: null,
        inactivityWarningTimer: null,
        inactivityTimeout: 600000, // 10 minutes default.
        inactivityWarningTime: 60000, // 1 minute warning before close.
        warningShown: false,

        // History management.
        historyLoaded: false,
        enableHistory: true,
        historyUrl: '',

        // User role.
        userRole: 'user',

        // Sound management.
        soundEnabled: true,
        notificationSound: null,
        audioContext: null,

        // Feedback URL.
        feedbackUrl: '',

        // References for export.
        messagesContainer: null,
        sesskey: '',
        botName: 'udesbot',

        // Message counter for unique IDs.
        messageCounter: 0,

        // Local storage persistence.
        userId: null,
        localStorageKey: 'udesbot-conversation',
        conversationData: [],
        greetingMessage: '',

        // Session ID for context tracking (v3.0.0).
        sessionId: null,

        // Retry configuration (v3.0.0).
        maxRetries: 3,
        retryDelays: [1000, 2000, 4000], // Exponential backoff delays in ms.

        // DOM references (v3.0.0, v3.8.0).
        elements: {
            chat: null,
            popup: null,
            btn: null,
            closeBtn: null,
            clearBtn: null,
            exportBtn: null,
            textarea: null,
            sendBtn: null,
            messages: null,
            loading: null,
            typingIndicator: null,
            scrollBtn: null,        // v3.8.0
            charCounter: null,      // v3.8.0
            keyboardHelper: null    // v3.8.0
        },

        // URLs (v3.0.0).
        urls: {
            service: '',
            shortcuts: ''
        },

        // State flags (v3.0.0).
        shortcutsLoaded: false,
        isProcessing: false,
        courseid: 1,

        // v3.8.0 - New features.
        scrollThreshold: 100, // Pixels from bottom to show scroll button.
        isUserScrolled: false,
        maxCharacters: 500, // Max characters in textarea.
        lastMessageTime: null,
        messageGroupInterval: 300000, // 5 minutes for grouping messages.

        // ==============================================
        // Initialization
        // ==============================================

        /**
         * Initialize the widget.
         */
        init: function() {
            var self = this;

            // Don't initialize in embedded layouts.
            if ($('body.pagelayout-embedded').length) {
                return;
            }

            var udesbotchat = $('#udesbot-chat');

            // Hide in maintenance or embedded layouts.
            if ($('.pagelayout-embedded, .pagelayout-maintenance').length) {
                udesbotchat.hide();
                udesbotchat.remove();
                return;
            }

            // Store main element reference.
            self.elements.chat = udesbotchat;

            // Get configuration from data attributes.
            self.inactivityTimeout = parseInt(udesbotchat.data('inactivity-timeout')) || 600000;
            self.enableHistory = parseInt(udesbotchat.data('enable-history')) === 1;
            self.historyUrl = udesbotchat.data('historyurl') || '';
            self.userRole = udesbotchat.data('userrolearchetype') || 'user';
            self.soundEnabled = parseInt(udesbotchat.data('sound-enabled')) !== 0;
            self.feedbackUrl = udesbotchat.data('feedbackurl') || '';
            self.botName = udesbotchat.data('botname') || 'udesbot';
            self.userId = udesbotchat.data('userid') || 0;
            self.localStorageKey = 'udesbot-conversation-' + self.userId;
            self.sesskey = udesbotchat.data('sesskey') || '';
            self.courseid = udesbotchat.data('courseid') || 1;
            self.urls.service = udesbotchat.data('serviceurl') || '';
            self.urls.shortcuts = udesbotchat.data('shortcutsurl') || '';

            // Generate unique session ID for this conversation (v3.0.0).
            self.sessionId = self.generateSessionId();

            // Save greeting message for reset.
            var greetingElement = udesbotchat.find('.udesbot-message').first();
            if (greetingElement.length) {
                self.greetingMessage = greetingElement.find('.udesbot-message-content').html();
            }

            // Cache DOM elements.
            self.elements.popup = udesbotchat.find('.udesbot-popup');
            self.elements.btn = $('#udesbot-btn');
            self.elements.closeBtn = $('#udesbot-close');
            self.elements.clearBtn = $('#udesbot-clear');
            self.elements.exportBtn = $('#udesbot-export');
            self.elements.textarea = $('#udesbot-textarea');
            self.elements.sendBtn = $('#udesbot-send');
            self.elements.messages = $('#udesbot-messages');
            self.elements.loading = $('#udesbot-loading');
            self.elements.typingIndicator = $('#udesbot-typing');

            // Store references for export.
            self.messagesContainer = self.elements.messages;

            // v3.8.0 - Create and initialize new UI elements.
            self.createScrollToBottomButton();
            self.createCharacterCounter();
            self.createKeyboardHelper();

            // Initialize notification sound.
            self.initNotificationSound();

            // Initialize mascot.
            self.mascot = $('#udesbot-mascot');
            self.tooltip = $('#udesbot-mascot-tooltip');

            // Load local conversation on page load.
            self.loadLocalConversation(self.elements.messages);

            // Listen for storage events to sync between tabs.
            self.initStorageListener(self.elements.messages);

            // Setup unified event delegation (v3.0.0).
            self.setupEventDelegation();

            // Setup direct event handlers.
            self.setupDirectHandlers();

            // Show widget with animation.
            udesbotchat.show(200);

            // Restore state from localStorage.
            if (localStorage.getItem('udesbot-isopen') === 'true') {
                self.openChat();
            }
        },

        /**
         * Generate a unique session ID for context tracking.
         *
         * @return {string} Unique session ID
         */
        generateSessionId: function() {
            return 'sess_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        // ==============================================
        // Unified Event Delegation System (v3.0.0)
        // ==============================================

        /**
         * Setup unified event delegation for all interactive elements.
         * This replaces all inline handlers to prevent duplicate execution.
         */
        setupEventDelegation: function() {
            var self = this;
            var udesbotchat = self.elements.chat;

            // Single delegation handler for ALL clickable elements.
            udesbotchat.on('click', '[data-udesbot-action]', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var $btn = $(this);
                var action = $btn.attr('data-udesbot-action');

                // Prevent double-clicks.
                if ($btn.hasClass('processing')) {
                    return;
                }

                switch (action) {
                    case 'shortcut':
                        self.handleShortcutClick($btn);
                        break;
                    case 'option':
                        self.handleOptionClick($btn);
                        break;
                    case 'popular-question':
                        self.handlePopularQuestionClick($btn);
                        break;
                    case 'similar-question':
                        self.handleSimilarQuestionClick($btn);
                        break;
                }
            });

            // Legacy support: Handle old button classes without data-udesbot-action.
            // This ensures backwards compatibility with existing HTML/templates.
            udesbotchat.on('click', '.udesbot-shortcut-btn:not([data-udesbot-action])', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleShortcutClick($(this));
            });

            udesbotchat.on('click', '.udesbot-option-btn:not([data-udesbot-action]):not(.disabled)', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.handleOptionClick($(this));
            });
        },

        /**
         * Handle shortcut button click.
         *
         * @param {jQuery} $btn - The clicked button
         */
        handleShortcutClick: function($btn) {
            var self = this;
            var action = $btn.attr('data-action');

            if (action && action.length >= 2) {
                self.elements.textarea.val(action);
                self.sendMessage();
            }
        },

        /**
         * Handle option button click.
         *
         * @param {jQuery} $btn - The clicked button
         */
        handleOptionClick: function($btn) {
            var self = this;

            // Disable all options in the same container.
            $btn.closest('.udesbot-options').find('.udesbot-option-btn')
                .prop('disabled', true).addClass('disabled');

            // Reset inactivity timer.
            self.resetInactivityTimer();

            // Get action or targetpattern.
            var action = $btn.attr('data-action');
            var targetpattern = $btn.attr('data-targetpattern');

            // Execute the action.
            if (action && action.length >= 2) {
                self.elements.textarea.val(action);
                self.sendMessage();
            } else if (targetpattern && targetpattern.length >= 2) {
                self.elements.textarea.val(targetpattern);
                self.sendMessage();
            }
        },

        /**
         * Handle popular question click.
         *
         * @param {jQuery} $btn - The clicked element
         */
        handlePopularQuestionClick: function($btn) {
            var self = this;
            var question = $btn.attr('data-question');

            if (question) {
                self.elements.textarea.val(question);
                self.hideTooltip();
                self.sendMessage();
            }
        },

        /**
         * Handle similar question click.
         *
         * @param {jQuery} $btn - The clicked element
         */
        handleSimilarQuestionClick: function($btn) {
            var self = this;
            var question = $btn.attr('data-question');

            if (question) {
                self.elements.textarea.val(question);
                self.hideTooltip();
                self.sendMessage();
            }
        },

        // ==============================================
        // Direct Event Handlers
        // ==============================================

        /**
         * Setup direct event handlers for non-delegated elements.
         */
        setupDirectHandlers: function() {
            var self = this;

            // Toggle chat open/close.
            self.elements.btn.on('click', function() {
                if (self.elements.chat.hasClass('udesbot-active')) {
                    self.closeChat();
                } else {
                    self.openChat();
                }
            });

            // Close button.
            self.elements.closeBtn.on('click', function(e) {
                e.preventDefault();
                self.closeChat();
            });

            // Clear history.
            self.elements.clearBtn.on('click', function(e) {
                e.preventDefault();
                self.clearChat();
            });

            // Export conversation.
            if (self.elements.exportBtn.length) {
                self.elements.exportBtn.on('click', function(e) {
                    e.preventDefault();
                    self.exportConversation();
                });
            }

            // Auto-resize textarea and reset inactivity on input.
            self.elements.textarea.on('input', function() {
                this.style.height = '34px';
                this.style.height = (this.scrollHeight) + 'px';
                self.resetInactivityTimer();
            });

            // Send on Enter (Shift+Enter for new line).
            self.elements.textarea.on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });

            // Send on button click.
            self.elements.sendBtn.on('click', function() {
                self.sendMessage();
            });
        },

        // ==============================================
        // Chat State Management
        // ==============================================

        /**
         * Open the chat window.
         */
        openChat: function() {
            var self = this;

            self.elements.chat.addClass('udesbot-active');
            localStorage.setItem('udesbot-isopen', 'true');
            self.elements.textarea.focus();

            // Load chat history on first open.
            if (!self.historyLoaded && self.enableHistory) {
                self.loadChatHistory();
            }

            // Load shortcuts.
            if (!self.shortcutsLoaded) {
                self.loadShortcuts();
            }

            // Initialize mascot on first open.
            if (self.mascot.length && !self.mascot.data('initialized')) {
                self.initMascot();
            }

            // Start inactivity timer.
            self.resetInactivityTimer();
        },

        /**
         * Close the chat window.
         */
        closeChat: function() {
            var self = this;

            self.elements.chat.removeClass('udesbot-active');
            localStorage.removeItem('udesbot-isopen');

            // Stop suggestion timer.
            if (self.suggestionTimer) {
                clearTimeout(self.suggestionTimer);
            }

            // Stop inactivity timer.
            self.stopInactivityTimer();
        },

        /**
         * Clear the chat and reset state.
         */
        clearChat: function() {
            var self = this;
            var messages = self.elements.messages;

            // Keep only greeting message.
            messages.find('.udesbot-message').not(':first').remove();
            messages.find('.udesbot-history-divider').remove();
            messages.find('.udesbot-inactivity-warning').remove();
            messages.find('.udesbot-shortcuts-container').remove();

            // Clear server-side history if enabled.
            if (self.enableHistory && self.historyUrl) {
                self.ajaxRequest(self.historyUrl, {
                    sesskey: self.sesskey,
                    action: 'clear'
                });
            }

            // Clear local storage.
            localStorage.removeItem('udesbot-history-' + self.userId);

            // Clear local conversation.
            self.conversationData = [];
            self.saveLocalConversation();

            // Reset flags.
            self.historyLoaded = false;
            self.shortcutsLoaded = false;

            // Generate new session ID.
            self.sessionId = self.generateSessionId();

            // Reload shortcuts.
            self.loadShortcuts();

            // Reset inactivity timer.
            self.resetInactivityTimer();

            // Reset mascot state.
            self.setMascotState('idle');
        },

        // ==============================================
        // Message Sending with Retry (v3.0.0)
        // ==============================================

        /**
         * Send a message to the bot with retry support.
         */
        sendMessage: function() {
            var self = this;
            var question = self.elements.textarea.val().trim();

            if (question.length < 2 || self.isProcessing) {
                return;
            }

            // Store last question for similar suggestions.
            self.lastUserQuestion = question;

            // Mark as processing.
            self.isProcessing = true;

            // Add user message to chat.
            self.addMessage(question, 'user');

            // Clear input.
            self.elements.textarea.val('');
            self.elements.textarea.css('height', '34px');

            // Show typing indicator.
            self.showTypingIndicator();

            // Set mascot to thinking state.
            self.setMascotState('thinking');
            self.hideTooltip();

            // Reset inactivity timer.
            self.resetInactivityTimer();

            // Send request with retry.
            self.sendMessageWithRetry(question, 0);
        },

        /**
         * Send message request with retry on failure.
         *
         * @param {string} question - The question to send
         * @param {number} attempt - Current attempt number (0-indexed)
         */
        sendMessageWithRetry: function(question, attempt) {
            var self = this;

            var requestData = {
                sesskey: self.sesskey,
                question: question,
                courseid: self.courseid,
                sessionid: self.sessionId // Context synchronization.
            };

            $.ajax({
                url: self.urls.service,
                type: 'POST',
                data: requestData,
                dataType: 'json',
                timeout: 30000, // 30 second timeout.
                success: function(data) {
                    self.handleMessageSuccess(data);
                },
                error: function(xhr, status, error) {
                    self.handleMessageError(question, attempt, xhr, status, error);
                }
            });
        },

        /**
         * Handle successful message response.
         *
         * @param {object} data - Response data
         */
        handleMessageSuccess: function(data) {
            var self = this;

            // Hide typing indicator.
            self.hideTypingIndicator();

            // Mark as not processing.
            self.isProcessing = false;

            if (data.success && data.response) {
                self.addMessage(data.response, 'bot', data.confidence, data.options, false, data.ruleid);

                // Play notification sound.
                self.playNotificationSound();

                // Set mascot state based on match.
                if (data.confidence && data.confidence > 0.5) {
                    self.setMascotState('success');
                } else {
                    self.setMascotState('confused');
                }
            } else if (data.error) {
                self.addMessage(data.error, 'bot error');
                self.setMascotState('confused');
            } else {
                self.addMessage(
                    M.util.get_string('error_noresponse', 'local_udesbot') || 'No response received',
                    'bot error'
                );
                self.setMascotState('confused');
            }

            // Reset inactivity timer after response.
            self.resetInactivityTimer();
        },

        /**
         * Handle message error with retry logic.
         *
         * @param {string} question - Original question
         * @param {number} attempt - Current attempt number
         * @param {object} xhr - XHR object
         * @param {string} status - Error status
         * @param {string} error - Error message
         */
        handleMessageError: function(question, attempt, xhr, status, error) {
            var self = this;

            // Check if we should retry.
            if (attempt < self.maxRetries && self.shouldRetry(xhr, status)) {
                var delay = self.retryDelays[attempt] || 4000;

                // Update typing indicator to show retry.
                self.updateTypingIndicator(
                    M.util.get_string('retrying', 'local_udesbot') || 'Retrying...'
                );

                // Retry after delay.
                setTimeout(function() {
                    self.sendMessageWithRetry(question, attempt + 1);
                }, delay);
            } else {
                // Max retries reached or non-retryable error.
                self.hideTypingIndicator();
                self.isProcessing = false;

                self.addMessage(
                    M.util.get_string('error_connection', 'local_udesbot') || 'Connection error. Please try again.',
                    'bot error'
                );
                self.setMascotState('confused');
            }
        },

        /**
         * Determine if request should be retried.
         *
         * @param {object} xhr - XHR object
         * @param {string} status - Error status
         * @return {boolean} Whether to retry
         */
        shouldRetry: function(xhr, status) {
            // Retry on timeout or network errors.
            if (status === 'timeout' || status === 'error') {
                return true;
            }

            // Retry on server errors (5xx).
            if (xhr && xhr.status >= 500 && xhr.status < 600) {
                return true;
            }

            // Don't retry on client errors (4xx) or other issues.
            return false;
        },

        /**
         * Show typing indicator.
         */
        showTypingIndicator: function() {
            var self = this;

            if (self.elements.typingIndicator.length) {
                self.elements.typingIndicator.show();
                self.elements.messages.scrollTop(self.elements.messages[0].scrollHeight);
            } else {
                self.elements.loading.show();
            }
        },

        /**
         * Hide typing indicator.
         */
        hideTypingIndicator: function() {
            var self = this;

            if (self.elements.typingIndicator.length) {
                self.elements.typingIndicator.hide();
            }
            self.elements.loading.hide();
        },

        /**
         * Update typing indicator text.
         *
         * @param {string} text - Text to show
         */
        updateTypingIndicator: function(text) {
            var self = this;

            if (self.elements.typingIndicator.length) {
                self.elements.typingIndicator.find('.udesbot-typing-text').text(text);
            }
        },

        // ==============================================
        // AJAX Helper with Retry
        // ==============================================

        /**
         * Make AJAX request with optional retry.
         *
         * @param {string} url - Request URL
         * @param {object} data - Request data
         * @param {function} onSuccess - Success callback
         * @param {function} onError - Error callback
         * @param {number} maxRetries - Max retry attempts
         * @return {jqXHR} jQuery XHR object
         */
        ajaxRequest: function(url, data, onSuccess, onError, maxRetries) {
            var self = this;
            maxRetries = maxRetries || 0;

            function doRequest(attempt) {
                return $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    dataType: 'json',
                    success: function(response) {
                        if (onSuccess) {
                            onSuccess(response);
                        }
                    },
                    error: function(xhr, status, error) {
                        if (attempt < maxRetries && self.shouldRetry(xhr, status)) {
                            var delay = self.retryDelays[attempt] || 4000;
                            setTimeout(function() {
                                doRequest(attempt + 1);
                            }, delay);
                        } else if (onError) {
                            onError(xhr, status, error);
                        }
                    }
                });
            }

            return doRequest(0);
        },

        // ==============================================
        // Message Display
        // ==============================================

        /**
         * Add a message to the chat.
         *
         * @param {string} text - Message text
         * @param {string} sender - 'user' or 'bot' or 'bot error'
         * @param {number} confidence - Confidence score (0-1)
         * @param {array} options - Quick reply options
         * @param {boolean} isHistory - Whether this is a history message
         * @param {number} ruleid - Rule ID for feedback
         * @return {string} Message ID
         */
        addMessage: function(text, sender, confidence, options, isHistory, ruleid) {
            var self = this;
            var messages = self.elements.messages;

            var isError = sender.indexOf('error') !== -1;
            var senderClass = sender.replace(' error', '');
            var timestamp = Date.now();

            // v3.8.0 - Check for time separator.
            if (!isHistory && self.shouldAddTimeSeparator(timestamp)) {
                var separator = self.createTimeSeparator(timestamp);
                messages.append(separator);
            }

            // Generate unique message ID.
            self.messageCounter++;
            var msgId = 'udesbot-msg-' + self.messageCounter;

            var messageDiv = $('<div>')
                .addClass('udesbot-message')
                .addClass('udesbot-' + senderClass)
                .attr('id', msgId)
                .attr('data-sender', senderClass)
                .attr('data-timestamp', Date.now());

            if (ruleid) {
                messageDiv.attr('data-ruleid', ruleid);
            }

            if (isError) {
                messageDiv.addClass('udesbot-error');
            }

            if (isHistory) {
                messageDiv.addClass('udesbot-history-message');
            }

            var contentDiv = $('<div>')
                .addClass('udesbot-message-content')
                .html(text);

            messageDiv.append(contentDiv);

            // Add message footer with timestamp and actions.
            var footerDiv = $('<div>').addClass('udesbot-message-footer');

            // Timestamp.
            var now = new Date();
            var timeStr = now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            var timestampSpan = $('<span>')
                .addClass('udesbot-timestamp')
                .text(timeStr);
            footerDiv.append(timestampSpan);

            // Add confidence indicator for bot messages.
            if (senderClass === 'bot' && !isError && typeof confidence !== 'undefined' && confidence > 0) {
                var confidencePercent = Math.round(confidence * 100);
                var confidenceSpan = $('<span>')
                    .addClass('udesbot-confidence')
                    .text(confidencePercent + '%');
                footerDiv.append(confidenceSpan);
            }

            // Add feedback buttons for bot messages.
            if (senderClass === 'bot' && !isError && !isHistory && self.feedbackUrl) {
                var feedbackDiv = self.createFeedbackButtons(msgId, ruleid);
                footerDiv.append(feedbackDiv);
            }

            messageDiv.append(footerDiv);

            // Add quick reply options if present (v3.0.0 - unified approach).
            if (senderClass === 'bot' && !isError && options && options.length > 0) {
                var optionsDiv = self.createOptionsButtons(options);
                messageDiv.append(optionsDiv);
            }

            messages.append(messageDiv);

            // Scroll to bottom if user hasn't scrolled up (v3.8.0).
            if (!self.isUserScrolled) {
                self.scrollToBottom(false);
            }

            // v3.8.0 - Announce to screen readers.
            if (!isHistory && senderClass === 'bot') {
                var plainText = $('<div>').html(text).text();
                self.announceToScreenReader(self.botName + ': ' + plainText.substring(0, 100));
            }

            // Save to local conversation - only for new messages, not history.
            if (!isHistory) {
                self.conversationData.push({
                    id: msgId,
                    sender: senderClass,
                    text: text,
                    timestamp: Date.now(),
                    confidence: confidence || null,
                    ruleid: ruleid || null,
                    options: options || null,
                    isError: isError
                });
                self.saveLocalConversation();
            }

            return msgId;
        },

        /**
         * Create options buttons container (v3.8.2 - same style as shortcuts).
         * Uses data attributes for unified event delegation.
         *
         * @param {array} options - Array of option objects
         * @return {jQuery} Options container element
         */
        createOptionsButtons: function(options) {
            var optionsDiv = $('<div>').addClass('udesbot-options');

            options.forEach(function(option) {
                var btn = $('<button>')
                    .addClass('udesbot-option-btn')
                    .attr('type', 'button')
                    .attr('data-udesbot-action', 'option');

                // Add data attributes for the delegation handler.
                if (option.action) {
                    btn.attr('data-action', option.action);
                }
                if (option.targetpattern) {
                    btn.attr('data-targetpattern', option.targetpattern);
                }

                // v3.8.2 - Icon (same style as shortcuts).
                var iconContainer = $('<span>').addClass('udesbot-option-icon');

                if (option.icon) {
                    if (option.icon.indexOf('bi-') === 0) {
                        // Bootstrap icon.
                        iconContainer.html('<i class="bi ' + option.icon + '"></i>');
                    } else {
                        // Emoji or text icon.
                        iconContainer.text(option.icon);
                    }
                } else {
                    // Default icon.
                    iconContainer.html('<i class="bi bi-arrow-right-circle"></i>');
                }

                btn.append(iconContainer);

                // Text container.
                var textContainer = $('<span>')
                    .addClass('udesbot-option-text')
                    .text(option.text);

                btn.append(textContainer);

                optionsDiv.append(btn);
            });

            return optionsDiv;
        },

        /**
         * Create feedback buttons.
         *
         * @param {string} msgId - Message ID
         * @param {number} ruleid - Rule ID
         * @return {jQuery} Feedback container element
         */
        createFeedbackButtons: function(msgId, ruleid) {
            var self = this;
            var feedbackDiv = $('<div>').addClass('udesbot-feedback');

            var thumbsUp = $('<button>')
                .addClass('udesbot-feedback-btn udesbot-thumbs-up')
                .attr('type', 'button')
                .attr('title', M.util.get_string('feedback_helpful', 'local_udesbot') || 'Helpful')
                .html('<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M1 21h4V9H1v12zm22-11c0-1.1-.9-2-2-2h-6.31l.95-4.57.03-.32c0-.41-.17-.79-.44-1.06L14.17 1 7.59 7.59C7.22 7.95 7 8.45 7 9v10c0 1.1.9 2 2 2h9c.83 0 1.54-.5 1.84-1.22l3.02-7.05c.09-.23.14-.47.14-.73v-2z"/></svg>')
                .on('click', function() {
                    self.sendFeedback(msgId, ruleid, 1, $(this));
                });

            var thumbsDown = $('<button>')
                .addClass('udesbot-feedback-btn udesbot-thumbs-down')
                .attr('type', 'button')
                .attr('title', M.util.get_string('feedback_nothelpful', 'local_udesbot') || 'Not helpful')
                .html('<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15 3H6c-.83 0-1.54.5-1.84 1.22l-3.02 7.05c-.09.23-.14.47-.14.73v2c0 1.1.9 2 2 2h6.31l-.95 4.57-.03.32c0 .41.17.79.44 1.06L9.83 23l6.59-6.59c.36-.36.58-.86.58-1.41V5c0-1.1-.9-2-2-2zm4 0v12h4V3h-4z"/></svg>')
                .on('click', function() {
                    self.sendFeedback(msgId, ruleid, 0, $(this));
                });

            feedbackDiv.append(thumbsUp).append(thumbsDown);
            return feedbackDiv;
        },

        // ==============================================
        // Shortcuts Loading
        // ==============================================

        /**
         * Load and display shortcuts with descriptions.
         */
        loadShortcuts: function() {
            var self = this;

            if (!self.urls.shortcuts) {
                self.shortcutsLoaded = true;
                return;
            }

            // Remove existing shortcuts container before loading new ones.
            self.elements.messages.find('.udesbot-shortcuts-container').remove();

            self.ajaxRequest(
                self.urls.shortcuts,
                {
                    sesskey: self.sesskey,
                    courseid: self.courseid,
                    userrole: self.userRole
                },
                function(data) {
                    if (data.success && data.shortcuts && data.shortcuts.length > 0) {
                        self.renderShortcuts(data.shortcuts);
                    }
                    self.shortcutsLoaded = true;
                },
                function() {
                    self.shortcutsLoaded = true;
                },
                2 // Retry up to 2 times.
            );
        },

        /**
         * Render shortcuts in the chat.
         *
         * @param {array} shortcuts - Array of shortcut objects
         */
        renderShortcuts: function(shortcuts) {
            var self = this;
            var messages = self.elements.messages;

            var greetingMessage = messages.find('.udesbot-message').first();
            if (!greetingMessage.length) {
                return;
            }

            var shortcutsContainer = $('<div>').addClass('udesbot-shortcuts-container');
            var shortcutsTitle = $('<div>')
                .addClass('udesbot-shortcuts-title')
                .text(M.util.get_string('shortcuts_title', 'local_udesbot') || 'Quick actions');
            shortcutsContainer.append(shortcutsTitle);

            var shortcutsGrid = $('<div>').addClass('udesbot-shortcuts-grid');

            shortcuts.forEach(function(shortcut) {
                var shortcutBtn = $('<button>')
                    .addClass('udesbot-shortcut-btn')
                    .attr('type', 'button')
                    .attr('data-udesbot-action', 'shortcut') // Unified action attribute.
                    .attr('data-action', shortcut.action);

                // Icon (Bootstrap Icons).
                if (shortcut.icon) {
                    var iconSpan = $('<span>')
                        .addClass('udesbot-shortcut-icon')
                        .html('<i class="bi ' + shortcut.icon + '"></i>');
                    shortcutBtn.append(iconSpan);
                }

                // Text container with name and description.
                var textContainer = $('<span>').addClass('udesbot-shortcut-text');
                var nameSpan = $('<span>')
                    .addClass('udesbot-shortcut-name')
                    .text(shortcut.name);
                textContainer.append(nameSpan);

                if (shortcut.description) {
                    var descSpan = $('<span>')
                        .addClass('udesbot-shortcut-desc')
                        .text(shortcut.description);
                    textContainer.append(descSpan);
                }

                shortcutBtn.append(textContainer);
                shortcutsGrid.append(shortcutBtn);
            });

            shortcutsContainer.append(shortcutsGrid);
            greetingMessage.after(shortcutsContainer);
        },

        // ==============================================
        // Chat History Methods
        // ==============================================

        /**
         * Load chat history from server.
         */
        loadChatHistory: function() {
            var self = this;

            if (!self.enableHistory || !self.historyUrl || self.historyLoaded) {
                return;
            }

            self.ajaxRequest(
                self.historyUrl,
                {
                    sesskey: self.sesskey,
                    action: 'recent',
                    limit: 10
                },
                function(data) {
                    if (data.success && data.history && data.history.length > 0) {
                        self.renderHistory(data.history);
                    }
                    self.historyLoaded = true;
                },
                function() {
                    self.historyLoaded = true;
                },
                2
            );
        },

        /**
         * Render history messages in the chat.
         *
         * @param {array} history - Array of history items
         */
        renderHistory: function(history) {
            var self = this;
            var messages = self.elements.messages;

            // Add history divider.
            var divider = $('<div>')
                .addClass('udesbot-history-divider')
                .text(M.util.get_string('previousconversation', 'local_udesbot') || 'Previous conversation');
            messages.append(divider);

            // Add history messages.
            history.forEach(function(item) {
                self.addMessage(item.question, 'user', null, null, true);
                self.addMessage(item.response, 'bot', item.confidence, null, true);
            });

            // Scroll to bottom.
            messages.scrollTop(messages[0].scrollHeight);
        },

        // ==============================================
        // Inactivity Timer Methods
        // ==============================================

        /**
         * Reset the inactivity timer.
         */
        resetInactivityTimer: function() {
            var self = this;

            // Clear existing timers.
            self.stopInactivityTimer();

            // Remove any existing warning.
            if (self.warningShown) {
                self.elements.messages.find('.udesbot-inactivity-warning').remove();
                self.warningShown = false;
            }

            // Don't start timer if chat is closed or timeout is 0.
            if (!self.elements.chat.hasClass('udesbot-active') || self.inactivityTimeout <= 0) {
                return;
            }

            // Set warning timer.
            self.inactivityWarningTimer = setTimeout(function() {
                self.showInactivityWarning();

                // After showing warning, set close timer.
                self.inactivityTimer = setTimeout(function() {
                    self.handleInactivityTimeout();
                }, self.inactivityWarningTime);
            }, self.inactivityTimeout);
        },

        /**
         * Stop all inactivity timers.
         */
        stopInactivityTimer: function() {
            var self = this;

            if (self.inactivityTimer) {
                clearTimeout(self.inactivityTimer);
                self.inactivityTimer = null;
            }

            if (self.inactivityWarningTimer) {
                clearTimeout(self.inactivityWarningTimer);
                self.inactivityWarningTimer = null;
            }
        },

        /**
         * Show inactivity warning message.
         */
        showInactivityWarning: function() {
            var self = this;

            if (self.warningShown) {
                return;
            }

            self.warningShown = true;

            var warningDiv = $('<div>')
                .addClass('udesbot-inactivity-warning')
                .html(
                    '<p>' + (M.util.get_string('inactivity_warning', 'local_udesbot') ||
                        'Chat will close soon due to inactivity') + '</p>' +
                    '<button type="button">' +
                        (M.util.get_string('keepchatopen', 'local_udesbot') || 'Keep chat open') +
                    '</button>'
                );

            warningDiv.find('button').on('click', function() {
                self.resetInactivityTimer();
            });

            self.elements.messages.append(warningDiv);
            self.elements.messages.scrollTop(self.elements.messages[0].scrollHeight);

            self.setMascotState('confused');
        },

        /**
         * Handle inactivity timeout.
         */
        handleInactivityTimeout: function() {
            var self = this;

            // Remove warning message.
            self.elements.messages.find('.udesbot-inactivity-warning').remove();

            // Minimize the chat.
            self.closeChat();

            // Reset state.
            self.warningShown = false;

            // Stop mascot suggestion timer.
            if (self.suggestionTimer) {
                clearTimeout(self.suggestionTimer);
            }

            // Clear local conversation data on inactivity timeout.
            self.conversationData = [];
            self.saveLocalConversation();

            // Generate new session ID.
            self.sessionId = self.generateSessionId();

            // Reset mascot state.
            self.setMascotState('idle');
        },

        // ==============================================
        // Mascot Methods
        // ==============================================

        /**
         * Initialize the mascot with greeting animation and event handlers.
         */
        initMascot: function() {
            var self = this;

            if (!self.mascot || !self.mascot.length) {
                return;
            }

            // Mark as initialized.
            self.mascot.data('initialized', true);

            // Start with greeting animation.
            self.setMascotState('greeting');

            // Show archetype-specific greeting.
            var greetingKey = 'mascot_greeting';
            var archetypeGreetings = {
                'student': 'mascot_greeting_student',
                'teacher': 'mascot_greeting_teacher',
                'editingteacher': 'mascot_greeting_editingteacher',
                'coursecreator': 'mascot_greeting_coursecreator',
                'manager': 'mascot_greeting_manager',
                'guest': 'mascot_greeting_guest',
                'user': 'mascot_greeting_user'
            };

            if (archetypeGreetings[self.userRole]) {
                greetingKey = archetypeGreetings[self.userRole];
            }

            setTimeout(function() {
                self.showTooltip(
                    M.util.get_string(greetingKey, 'local_udesbot') ||
                    M.util.get_string('mascot_greeting', 'local_udesbot') ||
                    'Hi! How can I help you?'
                );
            }, 800);

            // Transition to idle after greeting.
            setTimeout(function() {
                self.setMascotState('idle');
                self.hideTooltip();
                self.startSuggestionTimer();
            }, 3000);

            // Click handler - show popular questions.
            self.mascot.on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.showPopularQuestions();
            });

            // Hover handlers - pause suggestion timer.
            self.mascot.on('mouseenter', function() {
                if (self.suggestionTimer) {
                    clearTimeout(self.suggestionTimer);
                }
            });

            self.mascot.on('mouseleave', function() {
                self.startSuggestionTimer();
            });
        },

        /**
         * Set the mascot's animation state.
         *
         * @param {string} state - One of: idle, thinking, success, confused, greeting, suggesting
         */
        setMascotState: function(state) {
            var self = this;

            if (!self.mascot || !self.mascot.length) {
                return;
            }

            self.mascot.attr('data-state', state);

            // Auto-transition after certain states.
            if (state === 'success') {
                setTimeout(function() {
                    self.setMascotState('idle');
                    self.showTooltip(M.util.get_string('mascot_needmore', 'local_udesbot') || 'Need anything else?', 3000);
                }, 800);
            } else if (state === 'confused') {
                setTimeout(function() {
                    self.setMascotState('idle');
                    self.showSimilarQuestions();
                }, 1500);
            } else if (state === 'suggesting') {
                setTimeout(function() {
                    self.setMascotState('idle');
                }, 1800);
            }
        },

        /**
         * Show a tooltip message.
         *
         * @param {string} message - HTML content to display
         * @param {number} duration - Auto-hide duration in ms (0 for no auto-hide)
         */
        showTooltip: function(message, duration) {
            var self = this;

            if (!self.tooltip || !self.tooltip.length) {
                return;
            }

            self.tooltip.find('.tooltip-content').html(message);
            self.tooltip.addClass('visible');

            if (duration && duration > 0) {
                if (self.tooltipTimer) {
                    clearTimeout(self.tooltipTimer);
                }
                self.tooltipTimer = setTimeout(function() {
                    self.hideTooltip();
                }, duration);
            }
        },

        /**
         * Hide the tooltip.
         */
        hideTooltip: function() {
            var self = this;

            if (!self.tooltip || !self.tooltip.length) {
                return;
            }

            self.tooltip.removeClass('visible');

            if (self.tooltipTimer) {
                clearTimeout(self.tooltipTimer);
                self.tooltipTimer = null;
            }
        },

        /**
         * Start the suggestion timer.
         */
        startSuggestionTimer: function() {
            var self = this;

            if (self.suggestionTimer) {
                clearTimeout(self.suggestionTimer);
            }

            self.suggestionTimer = setTimeout(function() {
                self.showRandomSuggestion();
                self.startSuggestionTimer();
            }, 15000);
        },

        /**
         * Show a random suggestion in the tooltip.
         */
        showRandomSuggestion: function() {
            var self = this;

            var suggestions = [
                M.util.get_string('mascot_suggest_help', 'local_udesbot') || 'Click me for popular questions!'
            ];

            var archetypeSuggestions = {
                'student': [
                    M.util.get_string('mascot_suggest_tasks', 'local_udesbot') || 'Need help with your tasks?',
                    M.util.get_string('mascot_suggest_grades', 'local_udesbot') || 'I can show your grades',
                    M.util.get_string('mascot_suggest_calendar', 'local_udesbot') || 'Want to see the calendar?',
                    M.util.get_string('mascot_suggest_course', 'local_udesbot') || 'Ask me about your course',
                    M.util.get_string('mascot_suggest_deadlines', 'local_udesbot') || 'Check your upcoming deadlines'
                ],
                'teacher': [
                    M.util.get_string('mascot_suggest_grading', 'local_udesbot') || 'Need help with grading?',
                    M.util.get_string('mascot_suggest_students', 'local_udesbot') || 'Questions about your students?',
                    M.util.get_string('mascot_suggest_course', 'local_udesbot') || 'Ask me about your course',
                    M.util.get_string('mascot_suggest_attendance', 'local_udesbot') || 'View attendance reports'
                ],
                'editingteacher': [
                    M.util.get_string('mascot_suggest_grading', 'local_udesbot') || 'Need help with grading?',
                    M.util.get_string('mascot_suggest_students', 'local_udesbot') || 'Questions about your students?',
                    M.util.get_string('mascot_suggest_activities', 'local_udesbot') || 'Add activities to your course',
                    M.util.get_string('mascot_suggest_course', 'local_udesbot') || 'Ask me about your course'
                ],
                'coursecreator': [
                    M.util.get_string('mascot_suggest_newcourse', 'local_udesbot') || 'Create a new course?',
                    M.util.get_string('mascot_suggest_templates', 'local_udesbot') || 'Use course templates',
                    M.util.get_string('mascot_suggest_categories', 'local_udesbot') || 'Organize course categories'
                ],
                'manager': [
                    M.util.get_string('mascot_suggest_reports', 'local_udesbot') || 'View system reports?',
                    M.util.get_string('mascot_suggest_admin', 'local_udesbot') || 'Admin dashboard help?',
                    M.util.get_string('mascot_suggest_users', 'local_udesbot') || 'Manage users',
                    M.util.get_string('mascot_suggest_settings', 'local_udesbot') || 'Site configuration'
                ],
                'guest': [
                    M.util.get_string('mascot_suggest_browse', 'local_udesbot') || 'Browse available courses',
                    M.util.get_string('mascot_suggest_login', 'local_udesbot') || 'Log in for more features'
                ],
                'user': [
                    M.util.get_string('mascot_suggest_profile', 'local_udesbot') || 'Update your profile',
                    M.util.get_string('mascot_suggest_courses', 'local_udesbot') || 'Explore available courses',
                    M.util.get_string('mascot_suggest_calendar', 'local_udesbot') || 'Want to see the calendar?'
                ]
            };

            if (archetypeSuggestions[self.userRole]) {
                suggestions = suggestions.concat(archetypeSuggestions[self.userRole]);
            } else {
                suggestions = suggestions.concat(archetypeSuggestions['student']);
            }

            var random = suggestions[Math.floor(Math.random() * suggestions.length)];

            self.setMascotState('suggesting');
            self.showTooltip(random, 5000);
        },

        /**
         * Fetch and display popular questions.
         */
        showPopularQuestions: function() {
            var self = this;

            Ajax.call([{
                methodname: 'local_udesbot_get_popular_questions',
                args: {limit: 5}
            }])[0].done(function(questions) {
                if (!questions || questions.length === 0) {
                    self.showTooltip(
                        M.util.get_string('mascot_nopopular', 'local_udesbot') || 'No popular questions yet',
                        3000
                    );
                    return;
                }

                var html = '<div class="udesbot-popular-questions">';
                html += '<strong>' + (M.util.get_string('mascot_popularheader', 'local_udesbot') || 'Popular questions:') + '</strong>';

                questions.forEach(function(q) {
                    html += '<a href="#" class="udesbot-popular-q" data-udesbot-action="popular-question" data-question="' +
                            self.escapeHtml(q.pattern) + '">' + self.escapeHtml(q.pattern) + '</a>';
                });

                html += '</div>';

                self.showTooltip(html);
            }).fail(function() {
                self.showTooltip(
                    M.util.get_string('mascot_error', 'local_udesbot') || 'Could not load questions',
                    3000
                );
            });
        },

        /**
         * Fetch and display similar questions.
         */
        showSimilarQuestions: function() {
            var self = this;

            if (!self.lastUserQuestion) {
                self.showTooltip(
                    M.util.get_string('mascot_tryagain', 'local_udesbot') ||
                    'Try rephrasing your question or click me for suggestions',
                    5000
                );
                return;
            }

            Ajax.call([{
                methodname: 'local_udesbot_get_similar_questions',
                args: {question: self.lastUserQuestion, limit: 3}
            }])[0].done(function(questions) {
                if (!questions || questions.length === 0) {
                    self.showTooltip(
                        M.util.get_string('mascot_tryagain', 'local_udesbot') ||
                        'Try rephrasing your question or click me for suggestions',
                        5000
                    );
                    return;
                }

                var html = '<div class="udesbot-similar-questions">';
                html += '<strong>' + (M.util.get_string('mascot_similarheader', 'local_udesbot') || 'Did you mean:') + '</strong>';

                questions.forEach(function(q) {
                    html += '<a href="#" class="udesbot-similar-q" data-udesbot-action="similar-question" data-question="' +
                            self.escapeHtml(q.pattern) + '">' + self.escapeHtml(q.pattern) + '</a>';
                });

                html += '</div>';

                self.showTooltip(html);
            }).fail(function() {
                self.showTooltip(
                    M.util.get_string('mascot_tryagain', 'local_udesbot') ||
                    'Try rephrasing your question or click me for suggestions',
                    5000
                );
            });
        },

        /**
         * Escape HTML entities.
         *
         * @param {string} text - Text to escape
         * @return {string} Escaped text
         */
        escapeHtml: function(text) {
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        // ==============================================
        // Sound Notification Methods
        // ==============================================

        /**
         * Initialize the notification sound.
         */
        initNotificationSound: function() {
            var self = this;

            if (!self.soundEnabled) {
                return;
            }

            try {
                self.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            } catch (e) {
                self.soundEnabled = false;
            }
        },

        /**
         * Play a notification sound.
         */
        playNotificationSound: function() {
            var self = this;

            if (!self.soundEnabled || !self.audioContext) {
                return;
            }

            try {
                var oscillator = self.audioContext.createOscillator();
                var gainNode = self.audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(self.audioContext.destination);

                oscillator.frequency.value = 800;
                oscillator.type = 'sine';

                gainNode.gain.setValueAtTime(0.3, self.audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, self.audioContext.currentTime + 0.1);

                oscillator.start(self.audioContext.currentTime);
                oscillator.stop(self.audioContext.currentTime + 0.1);
            } catch (e) {
                // Silently fail.
            }
        },

        // ==============================================
        // Feedback Methods
        // ==============================================

        /**
         * Send feedback for a bot response.
         *
         * @param {string} msgId - Message element ID
         * @param {number} ruleid - Rule ID
         * @param {number} helpful - 1 for helpful, 0 for not helpful
         * @param {jQuery} btn - The clicked button
         */
        sendFeedback: function(msgId, ruleid, helpful, btn) {
            var self = this;

            if (!self.feedbackUrl) {
                return;
            }

            var msgElement = $('#' + msgId);
            msgElement.find('.udesbot-feedback-btn').prop('disabled', true);
            btn.addClass('selected');

            self.ajaxRequest(
                self.feedbackUrl,
                {
                    sesskey: self.sesskey,
                    ruleid: ruleid,
                    helpful: helpful
                },
                function(data) {
                    if (data.success) {
                        var thankYou = $('<span>')
                            .addClass('udesbot-feedback-thanks')
                            .text(M.util.get_string('feedback_thanks', 'local_udesbot') || 'Thanks!');
                        msgElement.find('.udesbot-feedback').append(thankYou);
                    }
                },
                function() {
                    msgElement.find('.udesbot-feedback-btn').prop('disabled', false);
                    btn.removeClass('selected');
                },
                2
            );
        },

        // ==============================================
        // Export Methods
        // ==============================================

        /**
         * Export the conversation as a text file.
         */
        exportConversation: function() {
            var self = this;

            if (!self.messagesContainer || !self.messagesContainer.length) {
                return;
            }

            var exportText = '';
            var botName = self.botName;
            var youLabel = M.util.get_string('export_you', 'local_udesbot') || 'You';

            exportText += '='.repeat(50) + '\n';
            exportText += (M.util.get_string('export_header', 'local_udesbot', botName) || 'Conversation with ' + botName) + '\n';
            exportText += (M.util.get_string('export_datetime', 'local_udesbot', new Date().toLocaleString()) || new Date().toLocaleString()) + '\n';
            exportText += '='.repeat(50) + '\n\n';

            self.messagesContainer.find('.udesbot-message').each(function() {
                var $msg = $(this);
                var sender = $msg.data('sender');
                var content = $msg.find('.udesbot-message-content').text().trim();
                var timestamp = $msg.find('.udesbot-timestamp').text() || '';

                if (content) {
                    var senderName = sender === 'user' ? youLabel : botName;
                    var prefix = '[' + timestamp + '] ' + senderName + ':\n';
                    exportText += prefix + content + '\n\n';
                }
            });

            exportText += '-'.repeat(50) + '\n';
            exportText += M.util.get_string('export_footer', 'local_udesbot') || 'End of conversation';

            var blob = new Blob([exportText], {type: 'text/plain;charset=utf-8'});
            var url = URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            var filename = M.util.get_string('export_filename', 'local_udesbot') || 'conversation';
            a.download = filename + '-' + new Date().toISOString().slice(0, 10) + '.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        },

        // ==============================================
        // Local Conversation Persistence Methods
        // ==============================================

        /**
         * Save conversation to localStorage.
         */
        saveLocalConversation: function() {
            var self = this;

            try {
                var dataToSave = self.conversationData.slice(-50);
                localStorage.setItem(self.localStorageKey, JSON.stringify({
                    version: 2,
                    timestamp: Date.now(),
                    sessionId: self.sessionId,
                    messages: dataToSave
                }));
            } catch (e) {
                // Storage might be full or disabled.
            }
        },

        /**
         * Load conversation from localStorage.
         *
         * @param {jQuery} messages - Messages container
         */
        loadLocalConversation: function(messages) {
            var self = this;

            try {
                var stored = localStorage.getItem(self.localStorageKey);
                if (!stored) {
                    return;
                }

                var data = JSON.parse(stored);
                if (!data || !data.messages || data.messages.length === 0) {
                    return;
                }

                // Check if data is older than 24 hours.
                var maxAge = 24 * 60 * 60 * 1000;
                if (Date.now() - data.timestamp > maxAge) {
                    localStorage.removeItem(self.localStorageKey);
                    return;
                }

                // Restore session ID if available.
                if (data.sessionId) {
                    self.sessionId = data.sessionId;
                }

                // Store in memory.
                self.conversationData = data.messages;

                // Clear existing messages except greeting.
                messages.find('.udesbot-message').not(':first').remove();

                // Restore messages.
                data.messages.forEach(function(msg) {
                    self.restoreMessage(messages, msg);
                });

                // Scroll to bottom.
                setTimeout(function() {
                    messages.scrollTop(messages[0].scrollHeight);
                }, 100);

            } catch (e) {
                // Parse error.
            }
        },

        /**
         * Restore a single message from stored data.
         *
         * @param {jQuery} messages - Messages container
         * @param {object} msg - Stored message object
         */
        restoreMessage: function(messages, msg) {
            var self = this;

            self.messageCounter++;
            var msgId = 'udesbot-msg-' + self.messageCounter;

            var messageDiv = $('<div>')
                .addClass('udesbot-message')
                .addClass('udesbot-' + msg.sender)
                .attr('id', msgId)
                .attr('data-sender', msg.sender)
                .attr('data-timestamp', msg.timestamp);

            if (msg.ruleid) {
                messageDiv.attr('data-ruleid', msg.ruleid);
            }

            if (msg.isError) {
                messageDiv.addClass('udesbot-error');
            }

            var contentDiv = $('<div>')
                .addClass('udesbot-message-content')
                .html(msg.text);

            messageDiv.append(contentDiv);

            var footerDiv = $('<div>').addClass('udesbot-message-footer');

            var msgDate = new Date(msg.timestamp);
            var timeStr = msgDate.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            var timestampSpan = $('<span>')
                .addClass('udesbot-timestamp')
                .text(timeStr);
            footerDiv.append(timestampSpan);

            if (msg.sender === 'bot' && !msg.isError && msg.confidence) {
                var confidencePercent = Math.round(msg.confidence * 100);
                var confidenceSpan = $('<span>')
                    .addClass('udesbot-confidence')
                    .text(confidencePercent + '%');
                footerDiv.append(confidenceSpan);
            }

            messageDiv.append(footerDiv);

            messages.append(messageDiv);
        },

        /**
         * Initialize storage event listener for cross-tab sync.
         *
         * @param {jQuery} messages - Messages container
         */
        initStorageListener: function(messages) {
            var self = this;

            $(window).on('storage', function(e) {
                var event = e.originalEvent;

                if (event.key !== self.localStorageKey) {
                    return;
                }

                if (event.newValue) {
                    try {
                        var data = JSON.parse(event.newValue);
                        if (data && data.messages) {
                            if (data.messages.length > self.conversationData.length) {
                                var newMessages = data.messages.slice(self.conversationData.length);

                                newMessages.forEach(function(msg) {
                                    self.restoreMessage(messages, msg);
                                });

                                self.conversationData = data.messages;
                                messages.scrollTop(messages[0].scrollHeight);

                                var lastMsg = newMessages[newMessages.length - 1];
                                if (lastMsg && lastMsg.sender === 'bot') {
                                    self.playNotificationSound();
                                }
                            }
                        }
                    } catch (e) {
                        // Ignore.
                    }
                } else {
                    self.conversationData = [];
                    messages.find('.udesbot-message').not(':first').remove();
                    messages.find('.udesbot-options').remove();
                }
            });
        },

        // ==============================================
        // v3.8.0 - Scroll to Bottom Button
        // ==============================================

        /**
         * Create the scroll-to-bottom button.
         */
        createScrollToBottomButton: function() {
            var self = this;

            var scrollBtn = $('<button>')
                .addClass('udesbot-scroll-btn')
                .attr('type', 'button')
                .attr('aria-label', M.util.get_string('scrolltobottom', 'local_udesbot') || 'Scroll to bottom')
                .html('<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/></svg>')
                .hide();

            self.elements.popup.find('.udesbot-messages').after(scrollBtn);
            self.elements.scrollBtn = scrollBtn;

            // Click handler.
            scrollBtn.on('click', function() {
                self.scrollToBottom(true);
            });

            // Scroll handler to show/hide button.
            self.elements.messages.on('scroll', function() {
                self.handleMessagesScroll();
            });
        },

        /**
         * Handle messages container scroll event.
         */
        handleMessagesScroll: function() {
            var self = this;
            var container = self.elements.messages[0];
            var distanceFromBottom = container.scrollHeight - container.scrollTop - container.clientHeight;

            if (distanceFromBottom > self.scrollThreshold) {
                self.isUserScrolled = true;
                self.elements.scrollBtn.fadeIn(200);
            } else {
                self.isUserScrolled = false;
                self.elements.scrollBtn.fadeOut(200);
            }
        },

        /**
         * Scroll to bottom of messages.
         *
         * @param {boolean} smooth - Whether to use smooth scrolling
         */
        scrollToBottom: function(smooth) {
            var self = this;
            var container = self.elements.messages[0];

            if (smooth) {
                self.elements.messages.animate({
                    scrollTop: container.scrollHeight
                }, 300);
            } else {
                container.scrollTop = container.scrollHeight;
            }

            self.isUserScrolled = false;
            self.elements.scrollBtn.fadeOut(200);
        },

        // ==============================================
        // v3.8.0 - Character Counter
        // ==============================================

        /**
         * Create character counter for textarea.
         */
        createCharacterCounter: function() {
            var self = this;

            var counterDiv = $('<div>')
                .addClass('udesbot-char-counter')
                .html('<span class="udesbot-char-count">0</span>/<span class="udesbot-char-max">' +
                      self.maxCharacters + '</span>');

            self.elements.textarea.after(counterDiv);
            self.elements.charCounter = counterDiv;

            // Update counter on input.
            self.elements.textarea.on('input', function() {
                self.updateCharacterCounter();
            });
        },

        /**
         * Update the character counter.
         */
        updateCharacterCounter: function() {
            var self = this;
            var length = self.elements.textarea.val().length;
            var countSpan = self.elements.charCounter.find('.udesbot-char-count');

            countSpan.text(length);

            if (length > self.maxCharacters) {
                self.elements.charCounter.addClass('over-limit');
                // Trim to max.
                self.elements.textarea.val(self.elements.textarea.val().substring(0, self.maxCharacters));
                countSpan.text(self.maxCharacters);
            } else if (length > self.maxCharacters * 0.9) {
                self.elements.charCounter.addClass('near-limit').removeClass('over-limit');
            } else {
                self.elements.charCounter.removeClass('near-limit over-limit');
            }
        },

        // ==============================================
        // v3.8.0 - Keyboard Shortcuts Helper
        // ==============================================

        /**
         * Create keyboard shortcuts helper.
         */
        createKeyboardHelper: function() {
            var self = this;

            var helperDiv = $('<div>')
                .addClass('udesbot-keyboard-helper')
                .html(
                    '<span class="udesbot-kbd-hint">' +
                    '<kbd>Enter</kbd> ' +
                    (M.util.get_string('tosend', 'local_udesbot') || 'to send') +
                    '</span>'
                );

            self.elements.textarea.parent().append(helperDiv);
            self.elements.keyboardHelper = helperDiv;
        },

        // ==============================================
        // v3.8.0 - Message Time Grouping
        // ==============================================

        /**
         * Check if a time separator should be added before a message.
         *
         * @param {number} timestamp - Message timestamp
         * @return {boolean} Whether to add separator
         */
        shouldAddTimeSeparator: function(timestamp) {
            var self = this;

            if (!self.lastMessageTime) {
                self.lastMessageTime = timestamp;
                return false;
            }

            var diff = timestamp - self.lastMessageTime;
            self.lastMessageTime = timestamp;

            return diff > self.messageGroupInterval;
        },

        /**
         * Create a time separator element.
         *
         * @param {number} timestamp - Timestamp for the separator
         * @return {jQuery} Time separator element
         */
        createTimeSeparator: function(timestamp) {
            var date = new Date(timestamp);
            var now = new Date();
            var label;

            // Format based on how recent.
            if (date.toDateString() === now.toDateString()) {
                label = M.util.get_string('today', 'local_udesbot') || 'Today';
            } else {
                var yesterday = new Date(now);
                yesterday.setDate(yesterday.getDate() - 1);
                if (date.toDateString() === yesterday.toDateString()) {
                    label = M.util.get_string('yesterday', 'local_udesbot') || 'Yesterday';
                } else {
                    label = date.toLocaleDateString(undefined, {
                        weekday: 'long',
                        month: 'short',
                        day: 'numeric'
                    });
                }
            }

            return $('<div>')
                .addClass('udesbot-time-separator')
                .html('<span>' + label + '</span>');
        },

        // ==============================================
        // v3.8.0 - Accessibility Improvements
        // ==============================================

        /**
         * Announce a message to screen readers.
         *
         * @param {string} message - Message to announce
         */
        announceToScreenReader: function(message) {
            var announcement = $('<div>')
                .addClass('udesbot-sr-announcement')
                .attr('role', 'status')
                .attr('aria-live', 'polite')
                .text(message);

            $('body').append(announcement);

            setTimeout(function() {
                announcement.remove();
            }, 1000);
        }
    };

    return chat;
});
