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

define(['jquery', 'core/ajax'], function($, Ajax) {

    var chat = {
        // Mascot state management (v1.8.1).
        mascot: null,
        tooltip: null,
        tooltipTimer: null,
        suggestionTimer: null,
        lastUserQuestion: '',

        // Conversation persistence (v1.8.3).
        storageKeyPrefix: 'educambot-conversation-',
        storageKey: null, // Will be set with course ID
        maxStoredMessages: 100,
        courseid: null,

        /**
         * Initialize the widget.
         */
        init: function() {
            var self = this;

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
            var startupUrl = educambotchat.data('startupurl');
            var sesskey = educambotchat.data('sesskey');
            var courseid = educambotchat.data('courseid') || 1;
            var startupOptionsLoaded = false;

            // Initialize conversation persistence with course-specific key (v1.8.3).
            self.courseid = courseid;
            self.storageKey = self.storageKeyPrefix + courseid;

            // Initialize mascot (v1.8.1).
            self.mascot = $('#educambot-mascot');
            self.tooltip = $('#educambot-mascot-tooltip');

            // Track if conversation was restored (v1.8.3).
            var conversationRestored = false;

            // Listen for storage events to sync across tabs (v1.8.3).
            $(window).on('storage', function(e) {
                if (e.originalEvent.key === self.storageKey) {
                    // Another tab updated the conversation, reload messages.
                    self.reloadMessages(messages);
                }
            });

            // Toggle chat open/close.
            btn.on('click', function() {
                educambotchat.toggleClass('educambot-active');
                if (educambotchat.hasClass('educambot-active')) {
                    localStorage.setItem('educambot-isopen', 'true');
                    textarea.focus();

                    // Restore saved conversation on first open (v1.8.2).
                    if (!conversationRestored) {
                        self.restoreMessages(messages);
                        conversationRestored = true;
                    }

                    // Load startup options on first open.
                    if (!startupOptionsLoaded) {
                        loadStartupOptions();
                    }
                    // Initialize mascot on first open (v1.8.1).
                    if (self.mascot.length && !self.mascot.data('initialized')) {
                        self.initMascot();
                    }
                } else {
                    localStorage.removeItem('educambot-isopen');
                    // Stop suggestion timer when closed.
                    if (self.suggestionTimer) {
                        clearTimeout(self.suggestionTimer);
                    }
                }
            });

            /**
             * Load and display startup options (suggested questions).
             */
            function loadStartupOptions() {
                if (!startupUrl) {
                    return;
                }

                $.ajax({
                    url: startupUrl,
                    type: 'POST',
                    data: {
                        sesskey: sesskey
                    },
                    dataType: 'json',
                    success: function(data) {
                        if (data.success && data.options && data.options.length > 0) {
                            // Add startup options after the greeting message.
                            var greetingMessage = messages.find('.educambot-message').first();
                            if (greetingMessage.length) {
                                var optionsDiv = $('<div>').addClass('educambot-options educambot-startup-options');

                                data.options.forEach(function(option) {
                                    var btnText = option.icon ? option.icon + ' ' + option.text : option.text;
                                    var optBtn = $('<button>')
                                        .addClass('educambot-option-btn')
                                        .attr('type', 'button')
                                        .text(btnText)
                                        .on('click', function() {
                                            handleOptionClick(option);
                                        });
                                    optionsDiv.append(optBtn);
                                });

                                greetingMessage.append(optionsDiv);
                            }
                        }
                        startupOptionsLoaded = true;
                    },
                    error: function() {
                        startupOptionsLoaded = true;
                    }
                });
            }

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
                // Clear saved messages from localStorage (v1.8.2).
                self.clearSavedMessages();
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

                // Restore saved conversation (v1.8.2).
                if (!conversationRestored) {
                    self.restoreMessages(messages);
                    conversationRestored = true;
                }

                // Load startup options if restored as open.
                if (!startupOptionsLoaded) {
                    loadStartupOptions();
                }
                // Initialize mascot if restored as open (v1.8.1).
                if (self.mascot.length && !self.mascot.data('initialized')) {
                    setTimeout(function() {
                        self.initMascot();
                    }, 500);
                }
            }

            /**
             * Send a message to the bot.
             */
            function sendMessage() {
                var question = textarea.val().trim();

                if (question.length < 2) {
                    return;
                }

                // Store last question for similar suggestions (v1.8.1).
                self.lastUserQuestion = question;

                // Add user message to chat.
                addMessage(question, 'user');

                // Clear input.
                textarea.val('');
                textarea.css('height', '34px');

                // Show loading.
                loading.show();

                // Set mascot to thinking state (v1.8.1).
                self.setMascotState('thinking');
                self.hideTooltip();

                // Send AJAX request.
                $.ajax({
                    url: serviceUrl,
                    type: 'POST',
                    data: {
                        sesskey: sesskey,
                        question: question,
                        courseid: courseid
                    },
                    dataType: 'json',
                    success: function(data) {
                        loading.hide();

                        if (data.success && data.response) {
                            addMessage(data.response, 'bot', data.confidence, data.options);
                            // Set mascot state based on match (v1.8.1).
                            if (data.confidence && data.confidence > 0.5) {
                                self.setMascotState('success');
                            } else {
                                self.setMascotState('confused');
                            }
                        } else if (data.error) {
                            addMessage(data.error, 'bot error');
                            self.setMascotState('confused');
                        } else {
                            addMessage('Error: No response received.', 'bot error');
                            self.setMascotState('confused');
                        }
                    },
                    error: function() {
                        loading.hide();
                        addMessage('Error: Could not connect to server.', 'bot error');
                        self.setMascotState('confused');
                    }
                });
            }

            /**
             * Handle option button click.
             *
             * @param {object} option - Option object with targetpattern or action
             */
            function handleOptionClick(option) {
                // Disable all option buttons in the chat.
                messages.find('.educambot-option-btn').prop('disabled', true).addClass('disabled');

                // Handle shortcut options (have 'action' property).
                if (option.action) {
                    textarea.val(option.action);
                    sendMessage();
                } else if (option.targetpattern) {
                    // Handle regular rule options.
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

                // Generate unique message ID (v1.8.3).
                var msgId = Date.now() + '-' + Math.random().toString(36).substr(2, 9);

                var messageDiv = $('<div>')
                    .addClass('educambot-message')
                    .addClass('educambot-' + senderClass)
                    .attr('data-msg-id', msgId);

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

                // Save message to localStorage for persistence (v1.8.3).
                self.saveMessageWithId(msgId, text, sender, confidence);
            }
        },

        // ==============================================
        // Mascot Methods (v1.8.1)
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

            // Show greeting tooltip.
            setTimeout(function() {
                self.showTooltip(M.util.get_string('mascot_greeting', 'local_educambot') || 'Hi! How can I help you?');
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
                    self.showTooltip(M.util.get_string('mascot_needmore', 'local_educambot') || 'Need anything else?', 3000);
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
         * Start the suggestion timer (shows random suggestions every 15s).
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
                M.util.get_string('mascot_suggest_tasks', 'local_educambot') || 'Need help with your tasks?',
                M.util.get_string('mascot_suggest_grades', 'local_educambot') || 'I can show your grades',
                M.util.get_string('mascot_suggest_calendar', 'local_educambot') || 'Want to see the calendar?',
                M.util.get_string('mascot_suggest_course', 'local_educambot') || 'Ask me about your course',
                M.util.get_string('mascot_suggest_help', 'local_educambot') || 'Click me for popular questions!'
            ];

            var random = suggestions[Math.floor(Math.random() * suggestions.length)];

            self.setMascotState('suggesting');
            self.showTooltip(random, 5000);
        },

        /**
         * Fetch and display popular questions from the server.
         */
        showPopularQuestions: function() {
            var self = this;

            Ajax.call([{
                methodname: 'local_educambot_get_popular_questions',
                args: {limit: 5}
            }])[0].done(function(questions) {
                if (!questions || questions.length === 0) {
                    self.showTooltip(
                        M.util.get_string('mascot_nopopular', 'local_educambot') || 'No popular questions yet',
                        3000
                    );
                    return;
                }

                var html = '<div class="educambot-popular-questions">';
                html += '<strong>' + (M.util.get_string('mascot_popularheader', 'local_educambot') || 'Popular questions:') + '</strong>';

                questions.forEach(function(q) {
                    html += '<a href="#" class="educambot-popular-q" data-question="' +
                            self.escapeHtml(q.pattern) + '">' + self.escapeHtml(q.pattern) + '</a>';
                });

                html += '</div>';

                self.showTooltip(html);

                // Add click handlers to the question links.
                self.tooltip.find('.educambot-popular-q').on('click', function(e) {
                    e.preventDefault();
                    var question = $(this).data('question');
                    $('#educambot-textarea').val(question);
                    self.hideTooltip();
                    $('#educambot-send').trigger('click');
                });
            }).fail(function() {
                self.showTooltip(
                    M.util.get_string('mascot_error', 'local_educambot') || 'Could not load questions',
                    3000
                );
            });
        },

        /**
         * Fetch and display similar questions based on the last user question.
         */
        showSimilarQuestions: function() {
            var self = this;

            if (!self.lastUserQuestion) {
                self.showTooltip(
                    M.util.get_string('mascot_tryagain', 'local_educambot') ||
                    'Try rephrasing your question or click me for suggestions',
                    5000
                );
                return;
            }

            Ajax.call([{
                methodname: 'local_educambot_get_similar_questions',
                args: {question: self.lastUserQuestion, limit: 3}
            }])[0].done(function(questions) {
                if (!questions || questions.length === 0) {
                    self.showTooltip(
                        M.util.get_string('mascot_tryagain', 'local_educambot') ||
                        'Try rephrasing your question or click me for suggestions',
                        5000
                    );
                    return;
                }

                var html = '<div class="educambot-similar-questions">';
                html += '<strong>' + (M.util.get_string('mascot_similarheader', 'local_educambot') || 'Did you mean:') + '</strong>';

                questions.forEach(function(q) {
                    html += '<a href="#" class="educambot-similar-q" data-question="' +
                            self.escapeHtml(q.pattern) + '">' + self.escapeHtml(q.pattern) + '</a>';
                });

                html += '</div>';

                self.showTooltip(html);

                // Add click handlers.
                self.tooltip.find('.educambot-similar-q').on('click', function(e) {
                    e.preventDefault();
                    var question = $(this).data('question');
                    $('#educambot-textarea').val(question);
                    self.hideTooltip();
                    $('#educambot-send').trigger('click');
                });
            }).fail(function() {
                self.showTooltip(
                    M.util.get_string('mascot_tryagain', 'local_educambot') ||
                    'Try rephrasing your question or click me for suggestions',
                    5000
                );
            });
        },

        /**
         * Escape HTML entities in a string.
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
        // Conversation Persistence Methods (v1.8.3)
        // ==============================================

        /**
         * Save a message to localStorage with pre-generated ID.
         *
         * @param {string} msgId - Unique message ID
         * @param {string} text - Message text
         * @param {string} sender - 'user' or 'bot'
         * @param {number} confidence - Confidence score (optional)
         */
        saveMessageWithId: function(msgId, text, sender, confidence) {
            var self = this;

            if (!self.storageKey) {
                return;
            }

            try {
                var messages = self.loadMessages();

                // Create message object with provided ID.
                var messageObj = {
                    id: msgId,
                    text: text,
                    sender: sender,
                    confidence: confidence || null,
                    timestamp: Date.now()
                };

                messages.push(messageObj);

                // Limit stored messages.
                if (messages.length > self.maxStoredMessages) {
                    messages = messages.slice(-self.maxStoredMessages);
                }

                localStorage.setItem(self.storageKey, JSON.stringify(messages));
            } catch (e) {
                // localStorage might be full or disabled.
                console.warn('Educambot: Could not save message to localStorage', e);
            }
        },

        /**
         * Load messages from localStorage.
         *
         * @return {array} Array of message objects
         */
        loadMessages: function() {
            var self = this;

            if (!self.storageKey) {
                return [];
            }

            try {
                var stored = localStorage.getItem(self.storageKey);
                if (stored) {
                    var messages = JSON.parse(stored);
                    // Validate array.
                    if (Array.isArray(messages)) {
                        return messages;
                    }
                }
            } catch (e) {
                console.warn('Educambot: Could not load messages from localStorage', e);
            }

            return [];
        },

        /**
         * Restore saved messages to the chat (initial load).
         *
         * @param {jQuery} messagesContainer - The messages container element
         */
        restoreMessages: function(messagesContainer) {
            var self = this;
            var messages = self.loadMessages();

            if (messages.length === 0) {
                return;
            }

            // Mark restored messages with data attribute to avoid duplicates.
            messages.forEach(function(msg) {
                // Check if message already exists in DOM.
                if (messagesContainer.find('[data-msg-id="' + msg.id + '"]').length > 0) {
                    return; // Skip duplicate.
                }

                self.renderMessage(messagesContainer, msg);
            });

            // Scroll to bottom.
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        },

        /**
         * Reload messages when another tab updates (sync).
         *
         * @param {jQuery} messagesContainer - The messages container element
         */
        reloadMessages: function(messagesContainer) {
            var self = this;
            var storedMessages = self.loadMessages();

            if (storedMessages.length === 0) {
                return;
            }

            // Only add new messages that aren't already in DOM.
            storedMessages.forEach(function(msg) {
                if (messagesContainer.find('[data-msg-id="' + msg.id + '"]').length === 0) {
                    self.renderMessage(messagesContainer, msg);
                }
            });

            // Scroll to bottom.
            messagesContainer.scrollTop(messagesContainer[0].scrollHeight);
        },

        /**
         * Render a single message to the container.
         *
         * @param {jQuery} container - The messages container
         * @param {object} msg - Message object with id, text, sender, confidence
         */
        renderMessage: function(container, msg) {
            var isError = msg.sender.indexOf('error') !== -1;
            var senderClass = msg.sender.replace(' error', '');

            var messageDiv = $('<div>')
                .addClass('educambot-message')
                .addClass('educambot-' + senderClass)
                .attr('data-msg-id', msg.id);

            if (isError) {
                messageDiv.addClass('educambot-error');
            }

            var contentDiv = $('<div>')
                .addClass('educambot-message-content')
                .html(msg.text);

            messageDiv.append(contentDiv);

            // Add confidence indicator for bot messages.
            if (senderClass === 'bot' && !isError && msg.confidence && msg.confidence > 0) {
                var confidencePercent = Math.round(msg.confidence * 100);
                var confidenceDiv = $('<div>')
                    .addClass('educambot-confidence')
                    .text(confidencePercent + '%');
                messageDiv.append(confidenceDiv);
            }

            container.append(messageDiv);
        },

        /**
         * Clear all saved messages for current course.
         */
        clearSavedMessages: function() {
            var self = this;

            if (!self.storageKey) {
                return;
            }

            try {
                localStorage.removeItem(self.storageKey);
            } catch (e) {
                console.warn('Educambot: Could not clear messages from localStorage', e);
            }
        }
    };

    return chat;
});
