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
 * Nexo Bot widget JavaScript (v1.9.0).
 *
 * Features:
 * - Theme colors via CSS custom properties
 * - Conversation history persistence
 * - Inactivity timeout
 * - Role-based behavior
 * - Mascot animations
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

        // Inactivity management (v1.9.0).
        inactivityTimer: null,
        inactivityWarningTimer: null,
        inactivityTimeout: 600000, // 10 minutes default.
        inactivityWarningTime: 60000, // 1 minute warning before close.
        warningShown: false,

        // History management (v1.9.0).
        historyLoaded: false,
        enableHistory: true,
        historyUrl: '',

        // User role (v1.9.0).
        userRole: 'user',

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

            // Get configuration from data attributes (v1.9.0).
            self.inactivityTimeout = parseInt(educambotchat.data('inactivity-timeout')) || 600000;
            self.enableHistory = parseInt(educambotchat.data('enable-history')) === 1;
            self.historyUrl = educambotchat.data('historyurl') || '';
            self.userRole = educambotchat.data('userrolearchetype') || 'user';

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

            // Initialize mascot (v1.8.1).
            self.mascot = $('#educambot-mascot');
            self.tooltip = $('#educambot-mascot-tooltip');

            // Toggle chat open/close.
            btn.on('click', function() {
                educambotchat.toggleClass('educambot-active');
                if (educambotchat.hasClass('educambot-active')) {
                    localStorage.setItem('educambot-isopen', 'true');
                    textarea.focus();

                    // Load chat history on first open (v1.9.0).
                    if (!self.historyLoaded && self.enableHistory) {
                        self.loadChatHistory(messages, sesskey);
                    }

                    // Load startup options on first open.
                    if (!startupOptionsLoaded) {
                        loadStartupOptions();
                    }
                    // Initialize mascot on first open (v1.8.1).
                    if (self.mascot.length && !self.mascot.data('initialized')) {
                        self.initMascot();
                    }

                    // Start inactivity timer (v1.9.0).
                    self.resetInactivityTimer(educambotchat, messages);
                } else {
                    localStorage.removeItem('educambot-isopen');
                    // Stop suggestion timer when closed.
                    if (self.suggestionTimer) {
                        clearTimeout(self.suggestionTimer);
                    }
                    // Stop inactivity timer (v1.9.0).
                    self.stopInactivityTimer();
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
                self.stopInactivityTimer();
            });

            // Clear history.
            clearBtn.on('click', function(e) {
                e.preventDefault();
                // Keep only greeting message.
                messages.find('.educambot-message').not(':first').remove();
                messages.find('.educambot-history-divider').remove();

                // Clear server-side history if enabled (v1.9.0).
                if (self.enableHistory && self.historyUrl) {
                    $.ajax({
                        url: self.historyUrl,
                        type: 'POST',
                        data: {
                            sesskey: sesskey,
                            action: 'clear'
                        },
                        dataType: 'json'
                    });
                }

                // Clear local storage.
                localStorage.removeItem('educambot-history-' + educambotchat.data('userid'));

                // Reset inactivity timer.
                self.resetInactivityTimer(educambotchat, messages);
            });

            // Auto-resize textarea and reset inactivity on input.
            textarea.on('input', function() {
                this.style.height = '34px';
                this.style.height = (this.scrollHeight) + 'px';
                // Reset inactivity timer on typing (v1.9.0).
                self.resetInactivityTimer(educambotchat, messages);
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

                // Load chat history if restored as open (v1.9.0).
                if (!self.historyLoaded && self.enableHistory) {
                    self.loadChatHistory(messages, sesskey);
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

                // Start inactivity timer (v1.9.0).
                self.resetInactivityTimer(educambotchat, messages);
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

                // Reset inactivity timer (v1.9.0).
                self.resetInactivityTimer(educambotchat, messages);

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

                        // Reset inactivity timer after response (v1.9.0).
                        self.resetInactivityTimer(educambotchat, messages);
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

                // Reset inactivity timer (v1.9.0).
                self.resetInactivityTimer(educambotchat, messages);

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
             * @param {boolean} isHistory - Whether this is a history message (v1.9.0)
             */
            function addMessage(text, sender, confidence, options, isHistory) {
                var isError = sender.indexOf('error') !== -1;
                var senderClass = sender.replace(' error', '');

                var messageDiv = $('<div>')
                    .addClass('educambot-message')
                    .addClass('educambot-' + senderClass);

                if (isError) {
                    messageDiv.addClass('educambot-error');
                }

                // Mark as history message (v1.9.0).
                if (isHistory) {
                    messageDiv.addClass('educambot-history-message');
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

            // Make addMessage available to chat object for history loading.
            self.addMessageFn = addMessage;
        },

        // ==============================================
        // Chat History Methods (v1.9.0)
        // ==============================================

        /**
         * Load chat history from server.
         *
         * @param {jQuery} messages - Messages container
         * @param {string} sesskey - Session key
         */
        loadChatHistory: function(messages, sesskey) {
            var self = this;

            if (!self.enableHistory || !self.historyUrl || self.historyLoaded) {
                return;
            }

            $.ajax({
                url: self.historyUrl,
                type: 'POST',
                data: {
                    sesskey: sesskey,
                    action: 'recent',
                    limit: 10
                },
                dataType: 'json',
                success: function(data) {
                    if (data.success && data.history && data.history.length > 0) {
                        // Add history divider.
                        var divider = $('<div>')
                            .addClass('educambot-history-divider')
                            .text(M.util.get_string('previousconversation', 'local_educambot') || 'Previous conversation');
                        messages.append(divider);

                        // Add history messages.
                        data.history.forEach(function(item) {
                            self.addMessageFn(item.question, 'user', null, null, true);
                            self.addMessageFn(item.response, 'bot', item.confidence, null, true);
                        });

                        // Scroll to bottom.
                        messages.scrollTop(messages[0].scrollHeight);
                    }
                    self.historyLoaded = true;
                },
                error: function() {
                    self.historyLoaded = true;
                }
            });
        },

        // ==============================================
        // Inactivity Timer Methods (v1.9.0)
        // ==============================================

        /**
         * Reset the inactivity timer.
         *
         * @param {jQuery} educambotchat - Chat container
         * @param {jQuery} messages - Messages container
         */
        resetInactivityTimer: function(educambotchat, messages) {
            var self = this;

            // Clear existing timers.
            self.stopInactivityTimer();

            // Remove any existing warning.
            if (self.warningShown) {
                messages.find('.educambot-inactivity-warning').remove();
                self.warningShown = false;
            }

            // Don't start timer if chat is closed or timeout is 0.
            if (!educambotchat.hasClass('educambot-active') || self.inactivityTimeout <= 0) {
                return;
            }

            // Set warning timer (1 minute before close).
            var warningTime = Math.max(0, self.inactivityTimeout - self.inactivityWarningTime);
            if (warningTime > 0) {
                self.inactivityWarningTimer = setTimeout(function() {
                    self.showInactivityWarning(educambotchat, messages);
                }, warningTime);
            }

            // Set close timer.
            self.inactivityTimer = setTimeout(function() {
                self.handleInactivityTimeout(educambotchat);
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
         *
         * @param {jQuery} educambotchat - Chat container
         * @param {jQuery} messages - Messages container
         */
        showInactivityWarning: function(educambotchat, messages) {
            var self = this;

            if (self.warningShown) {
                return;
            }

            self.warningShown = true;

            var warningDiv = $('<div>')
                .addClass('educambot-inactivity-warning')
                .html(
                    '<p>' + (M.util.get_string('inactivity_warning', 'local_educambot') ||
                        'Chat will close soon due to inactivity') + '</p>' +
                    '<button type="button">' +
                        (M.util.get_string('keepchatopen', 'local_educambot') || 'Keep chat open') +
                    '</button>'
                );

            warningDiv.find('button').on('click', function() {
                self.resetInactivityTimer(educambotchat, messages);
            });

            messages.append(warningDiv);
            messages.scrollTop(messages[0].scrollHeight);

            // Set mascot to confused/concerned state.
            self.setMascotState('confused');
        },

        /**
         * Handle inactivity timeout - minimize the chat.
         *
         * @param {jQuery} educambotchat - Chat container
         */
        handleInactivityTimeout: function(educambotchat) {
            var self = this;

            // Minimize the chat.
            educambotchat.removeClass('educambot-active');
            localStorage.removeItem('educambot-isopen');

            // Stop timers.
            self.stopInactivityTimer();
            self.warningShown = false;

            // Stop mascot suggestion timer.
            if (self.suggestionTimer) {
                clearTimeout(self.suggestionTimer);
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

            // Show role-specific greeting (v1.9.0).
            var greetingKey = 'mascot_greeting';
            if (self.userRole === 'teacher' || self.userRole === 'editingteacher') {
                greetingKey = 'mascot_greeting_teacher';
            } else if (self.userRole === 'manager') {
                greetingKey = 'mascot_greeting_admin';
            }

            setTimeout(function() {
                self.showTooltip(
                    M.util.get_string(greetingKey, 'local_educambot') ||
                    M.util.get_string('mascot_greeting', 'local_educambot') ||
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
         * Role-specific suggestions (v1.9.0).
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

            // Add role-specific suggestions (v1.9.0).
            if (self.userRole === 'teacher' || self.userRole === 'editingteacher') {
                suggestions.push(
                    M.util.get_string('mascot_suggest_grading', 'local_educambot') || 'Need help with grading?',
                    M.util.get_string('mascot_suggest_students', 'local_educambot') || 'Questions about your students?'
                );
            } else if (self.userRole === 'manager') {
                suggestions.push(
                    M.util.get_string('mascot_suggest_reports', 'local_educambot') || 'View system reports?',
                    M.util.get_string('mascot_suggest_admin', 'local_educambot') || 'Admin dashboard help?'
                );
            }

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
        }
    };

    return chat;
});
