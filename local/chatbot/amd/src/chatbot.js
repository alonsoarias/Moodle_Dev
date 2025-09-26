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
 * Intelligent Chatbot Widget JavaScript
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/templates'],
function($, Ajax, Notification, Templates) {
    
    var IntelligentChatbot = {
        
        config: null,
        isOpen: false,
        isTyping: false,
        conversationHistory: [],
        currentContext: {},
        lastMessageTime: null,
        typingTimer: null,
        messageQueue: [],
        isProcessing: false,

        /**
         * Normalize configuration options coming from PHP.
         *
         * @param {Object} config
         * @returns {Object}
         */
        prepareConfig: function(config) {
            config = config || {};

            config.position = config.position || 'bottom_right';
            config.theme = config.theme || 'modern';
            config.maxlength = config.maxlength || 500;
            config.avatar = config.avatar || (config.username ? config.username.charAt(0).toUpperCase() : 'A');

            config.features = $.extend({
                voice_input: false,
                emoji_picker: false,
                quick_actions: false,
                suggestions: false,
                typing_animation: false,
                sound_notifications: false
            }, config.features || {});

            config.permissions = $.extend({
                canexport: false
            }, config.permissions || {});

            config.strings = $.extend({
                title: 'Virtual Assistant',
                status: 'Online',
                toggle: 'Open assistant',
                placeholder: 'Type your message...',
                typing: 'The assistant is typing…',
                voice: 'Voice input',
                emoji: 'Emoji picker',
                send: 'Send message',
                export: 'Export conversation',
                minimize: 'Minimize',
                close: 'Close',
                welcome: 'Hello! I am your assistant. How can I help you today?'
            }, config.strings || {});

            return config;
        },

        /**
         * Initialize the chatbot
         */
        init: function(config) {
            var self = this;

            this.config = this.prepareConfig(config);
            this.currentContext = {
                courseid: this.config.courseid,
                contextid: this.config.contextid,
                sessionid: this.config.sessionid
            };

            this.createWidget().then(function() {
                self.cacheElements();
                self.attachEventHandlers();
                self.applyTheme();
                self.initializeFeatures();
                self.loadConversationHistory();
                self.showWelcomeMessage();
            }).catch(Notification.exception);
        },

        /**
         * Create the widget HTML structure
         */
        createWidget: function() {
            var self = this;
            var templateContext = {
                position: this.config.position,
                theme: this.config.theme,
                title: this.config.strings.title,
                status: this.config.strings.status,
                togglelabel: this.config.strings.toggle,
                placeholder: this.config.strings.placeholder,
                typing: this.config.strings.typing,
                voicelabel: this.config.strings.voice,
                emojilabel: this.config.strings.emoji,
                sendlabel: this.config.strings.send,
                exportlabel: this.config.strings.export,
                minimizelabel: this.config.strings.minimize,
                closelabel: this.config.strings.close,
                voiceenabled: this.config.features.voice_input,
                emojienabled: this.config.features.emoji_picker,
                canexport: this.config.permissions.canexport,
                maxlength: this.config.maxlength,
                initial: this.config.avatar,
                openicon: '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z"></path></svg>',
                closeicon: '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M19 6.41 17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path></svg>',
                minimizeicon: '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M19 13H5v-2h14v2z"></path></svg>',
                exporticon: '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M5 20h14v-2H5m14-9h-4V3H9v6H5l7 7 7-7z"></path></svg>',
                voiceicon: '<span aria-hidden="true">🎤</span>',
                emojiicon: '<span aria-hidden="true">😊</span>',
                sendicon: '<svg viewBox="0 0 24 24" role="presentation" focusable="false"><path fill="currentColor" d="M2 21l21-9L2 3v7l15 2-15 2v7z"></path></svg>'
            };

            $('#local-chatbot-container').remove();

            return Templates.render('local_chatbot/widget', templateContext).then(function(html, js) {
                $('body').append(html);
                Templates.runTemplateJS(js);
            });
        },

        /**
         * Cache main DOM elements for reuse
         */
        cacheElements: function() {
            this.$container = $('#local-chatbot-container');
            this.$container.show();
            this.$widget = $('#chatbot-widget');
            this.$launcher = $('#chatbot-launcher');
        },

        /**
         * Attach event handlers
         */
        attachEventHandlers: function() {
            var self = this;

            // Launcher click
            this.$launcher.on('click', function() {
                self.toggleChatbot();
            });

            // Header buttons
            $('.chatbot-btn-close').on('click', function() {
                self.closeChatbot();
            });

            $('.chatbot-btn-minimize').on('click', function() {
                self.minimizeChatbot();
            });

            if (this.config.permissions.canexport) {
                $('.chatbot-btn-export').on('click', function() {
                    self.exportConversation();
                });
            }
            
            // Send message
            $('#chatbot-send').on('click', function() {
                self.sendMessage();
            });
            
            // Input handling
            $('#chatbot-input').on('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    self.sendMessage();
                }
            });
            
            // Auto-resize textarea
            $('#chatbot-input').on('input', function() {
                self.autoResizeTextarea(this);
                self.updateCharCount();
                self.detectTyping();
            });
            
            // Voice input
            if (this.config.features.voice_input) {
                $('.chatbot-btn-voice').on('click', function() {
                    self.startVoiceInput();
                });
            }
            
            // Emoji picker
            if (this.config.features.emoji_picker) {
                $('.chatbot-btn-emoji').on('click', function() {
                    self.toggleEmojiPicker();
                });
            }
            
            // Suggestion clicks
            $(document).on('click', '.suggestion-chip', function() {
                var text = $(this).data('text');
                var action = $(this).data('action');
                
                if (action) {
                    self.handleQuickAction(action, $(this).data());
                } else {
                    $('#chatbot-input').val(text);
                    self.sendMessage();
                }
            });
            
            // Quick action clicks
            $(document).on('click', '.quick-action-btn', function() {
                var action = $(this).data('action');
                self.handleQuickAction(action, $(this).data());
            });
            
            // Feedback buttons
            $(document).on('click', '.message-feedback button', function() {
                var feedback = $(this).data('feedback');
                var messageId = $(this).closest('.chatbot-message').data('id');
                self.provideFeedback(messageId, feedback);
            });
        },
        
        /**
         * Initialize special features
         */
        initializeFeatures: function() {
            var self = this;
            
            // Quick actions
            if (this.config.features.quick_actions) {
                this.loadQuickActions();
            }
            
            // Suggestions
            if (this.config.features.suggestions) {
                this.loadSuggestions();
            }
            
            // Keyboard shortcuts
            this.initKeyboardShortcuts();
            
            // Idle detection
            this.initIdleDetection();
            
            // Connection monitoring
            this.monitorConnection();
        },
        
        /**
         * Toggle chatbot visibility
         */
        toggleChatbot: function() {
            if (this.isOpen) {
                this.closeChatbot();
            } else {
                this.openChatbot();
            }
        },

        /**
         * Open chatbot
         */
        openChatbot: function() {
            this.$container.show().addClass('chatbot-active');
            this.$widget.attr('aria-hidden', 'false');
            this.$launcher.attr('aria-expanded', 'true');
            $('#chatbot-input').focus();
            this.scrollToBottom();
            this.isOpen = true;

            // Clear badge
            $('.chatbot-launcher-badge').text('0').attr('aria-hidden', 'true');

            // Mark messages as read
            this.markMessagesAsRead();

            // Load context-specific content
            this.updateContextualContent();
        },

        /**
         * Close chatbot
         */
        closeChatbot: function() {
            this.$widget.attr('aria-hidden', 'true');
            this.$container.removeClass('chatbot-active');
            this.$launcher.attr('aria-expanded', 'false');
            this.isOpen = false;
        },

        /**
         * Minimize chatbot
         */
        minimizeChatbot: function() {
            this.closeChatbot();
        },
        
        /**
         * Send message
         */
        sendMessage: function() {
            var message = $('#chatbot-input').val().trim();
            
            if (message === '' || this.isProcessing) {
                return;
            }
            
            // Add to queue if already processing
            if (this.isProcessing) {
                this.messageQueue.push(message);
                return;
            }
            
            this.isProcessing = true;
            
            // Clear input
            $('#chatbot-input').val('').trigger('input');
            
            // Add user message to chat
            this.addMessage('user', message);
            
            // Show typing indicator
            this.showTypingIndicator();
            
            // Send to server
            var self = this;
            
            Ajax.call([{
                methodname: 'local_chatbot_process_message',
                args: {
                    message: message,
                    userid: this.config.userid,
                    sessionid: this.config.sessionid,
                    context: JSON.stringify(this.currentContext)
                },
                done: function(response) {
                    self.hideTypingIndicator();
                    
                    // Add bot response with animation
                    self.addMessage('bot', response.response, response);
                    
                    // Update context if provided
                    if (response.context) {
                        self.updateContext(response.context);
                    }
                    
                    // Show suggestions if available
                    if (response.suggestions) {
                        self.showSuggestions(response.suggestions);
                    }
                    
                    // Handle any actions
                    if (response.actions) {
                        self.executeActions(response.actions);
                    }
                    
                    self.isProcessing = false;
                    
                    // Process queued messages
                    if (self.messageQueue.length > 0) {
                        var nextMessage = self.messageQueue.shift();
                        $('#chatbot-input').val(nextMessage);
                        self.sendMessage();
                    }
                },
                fail: function(error) {
                    self.hideTypingIndicator();
                    self.addMessage('bot', 
                        'Lo siento, ha ocurrido un error. Por favor, intenta de nuevo.', 
                        {error: true}
                    );
                    self.isProcessing = false;
                    Notification.exception(error);
                }
            }]);
        },
        
        /**
         * Add message to chat
         */
        addMessage: function(sender, message, metadata = {}) {
            var self = this;
            var messageId = 'msg_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            var messageClass = sender === 'user' ? 'chatbot-message-user' : 'chatbot-message-bot';
            var avatar = sender === 'user' ? 
                '<div class="message-avatar user-avatar">' + this.config.username.charAt(0) + '</div>' :
                '<div class="message-avatar bot-avatar">🤖</div>';
            
            // Process message content (markdown, links, etc.)
            var processedMessage = this.processMessageContent(message);
            
            // Build message HTML
            var messageHTML = `
                <div class="chatbot-message ${messageClass} ${metadata.error ? 'error-message' : ''}" 
                     data-id="${messageId}"
                     data-sender="${sender}">
                    ${avatar}
                    <div class="message-content-wrapper">
                        <div class="message-content">
                            ${processedMessage}
                        </div>
                        <div class="message-metadata">
                            <span class="message-time">${this.getCurrentTime()}</span>
                            ${metadata.response_time ? 
                              '<span class="response-time">' + metadata.response_time + 'ms</span>' : ''}
                            ${metadata.intent ? 
                              '<span class="message-intent" title="Intención detectada">' + metadata.intent + '</span>' : ''}
                        </div>
                        ${sender === 'bot' && !metadata.error ? this.getFeedbackButtons(messageId) : ''}
                    </div>
                </div>
            `;
            
            // Add with animation
            var $message = $(messageHTML).hide();
            $('#chatbot-messages').append($message);
            $message.fadeIn(300);
            
            // Animate bot messages character by character
            if (sender === 'bot' && this.config.features.typing_animation) {
                this.animateText($message.find('.message-content'), processedMessage);
            }
            
            // Update conversation history
            this.conversationHistory.push({
                id: messageId,
                sender: sender,
                message: message,
                metadata: metadata,
                timestamp: Date.now()
            });
            
            // Scroll to bottom
            this.scrollToBottom();
            
            // Play sound if enabled
            if (this.config.features.sound_notifications && sender === 'bot') {
                this.playNotificationSound();
            }
        },
        
        /**
         * Process message content
         */
        processMessageContent: function(message) {
            // Convert line breaks
            message = message.replace(/\n/g, '<br>');
            
            // Process markdown-like syntax
            message = message.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            message = message.replace(/\*(.*?)\*/g, '<em>$1</em>');
            message = message.replace(/`(.*?)`/g, '<code>$1</code>');
            
            // Process lists
            message = message.replace(/^• (.*?)$/gm, '<li>$1</li>');
            message = message.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
            
            // Process links
            message = message.replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank">$1</a>');
            
            // Process emojis
            message = this.processEmojis(message);
            
            return message;
        },
        
        /**
         * Show typing indicator
         */
        showTypingIndicator: function() {
            $('#chatbot-typing-indicator').fadeIn(200);
            this.scrollToBottom();
        },
        
        /**
         * Hide typing indicator
         */
        hideTypingIndicator: function() {
            $('#chatbot-typing-indicator').fadeOut(200);
        },
        
        /**
         * Show suggestions
         */
        showSuggestions: function(suggestions) {
            var html = '';
            
            suggestions.forEach(function(suggestion) {
                html += `
                    <button class="suggestion-chip" 
                            data-text="${suggestion.text}"
                            data-action="${suggestion.action || ''}">
                        ${suggestion.icon || ''} ${suggestion.text}
                    </button>
                `;
            });
            
            $('.suggestions-container').html(html);
            $('#chatbot-suggestions').slideDown(200);
        },
        
        /**
         * Load quick actions
         */
        loadQuickActions: function() {
            var self = this;
            
            Ajax.call([{
                methodname: 'local_chatbot_get_quick_actions',
                args: {
                    context: JSON.stringify(this.currentContext)
                },
                done: function(actions) {
                    if (actions && actions.length > 0) {
                        self.displayQuickActions(actions);
                    }
                },
                fail: function(error) {
                    console.error('Failed to load quick actions:', error);
                }
            }]);
        },
        
        /**
         * Display quick actions
         */
        displayQuickActions: function(actions) {
            var html = '';
            
            actions.forEach(function(action) {
                html += `
                    <button class="quick-action-btn" 
                            data-action="${action.action}"
                            data-url="${action.url || ''}"
                            data-params="${JSON.stringify(action.params || {})}"
                            title="${action.description || action.label}">
                        <span class="quick-action-icon">${action.icon}</span>
                        <span class="quick-action-label">${action.label}</span>
                    </button>
                `;
            });
            
            $('.quick-actions-container').html(html);
            $('#chatbot-quick-actions').show();
        },
        
        /**
         * Handle quick action
         */
        handleQuickAction: function(action, data) {
            switch (action) {
                case 'navigate':
                    if (data.url) {
                        window.location.href = data.url;
                    }
                    break;
                    
                case 'show_assignments':
                    $('#chatbot-input').val('Muéstrame mis tareas pendientes');
                    this.sendMessage();
                    break;
                    
                case 'show_grades':
                    $('#chatbot-input').val('Quiero ver mis calificaciones');
                    this.sendMessage();
                    break;
                    
                case 'show_calendar':
                    $('#chatbot-input').val('Muéstrame el calendario');
                    this.sendMessage();
                    break;
                    
                default:
                    // Custom action - send to server
                    this.executeCustomAction(action, data);
            }
        },
        
        /**
         * Provide feedback on message
         */
        provideFeedback: function(messageId, feedback) {
            var self = this;
            
            // Visual feedback
            var $buttons = $('[data-id="' + messageId + '"] .message-feedback button');
            $buttons.prop('disabled', true);
            $buttons.filter('[data-feedback="' + feedback + '"]').addClass('selected');
            
            // Send to server
            Ajax.call([{
                methodname: 'local_chatbot_feedback',
                args: {
                    messageid: messageId,
                    feedback: feedback
                },
                done: function(response) {
                    // Show thank you message
                    self.showToast('¡Gracias por tu feedback!', 'success');
                },
                fail: function(error) {
                    console.error('Failed to send feedback:', error);
                }
            }]);
        },
        
        /**
         * Get feedback buttons HTML
         */
        getFeedbackButtons: function(messageId) {
            return `
                <div class="message-feedback">
                    <span class="feedback-label">¿Te fue útil?</span>
                    <button data-feedback="helpful" title="Útil">👍</button>
                    <button data-feedback="not_helpful" title="No útil">👎</button>
                </div>
            `;
        },
        
        /**
         * Auto-resize textarea
         */
        autoResizeTextarea: function(textarea) {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        },
        
        /**
         * Update character count
         */
        updateCharCount: function() {
            var count = $('#chatbot-input').val().length;
            var limit = this.config.maxlength || 500;

            $('#char-count').text(count);

            if (count > limit) {
                $('#chatbot-input').val(function(_, value) {
                    return value.substring(0, limit);
                });
                count = limit;
                $('#char-count').text(count);
            }

            if (count >= Math.round(limit * 0.9)) {
                $('.chatbot-char-count').addClass('warning');
            } else {
                $('.chatbot-char-count').removeClass('warning');
            }
        },
        
        /**
         * Detect typing
         */
        detectTyping: function() {
            var self = this;
            
            clearTimeout(this.typingTimer);
            
            if (!this.isTyping) {
                this.isTyping = true;
                this.sendTypingStatus(true);
            }
            
            this.typingTimer = setTimeout(function() {
                self.isTyping = false;
                self.sendTypingStatus(false);
            }, 1000);
        },
        
        /**
         * Send typing status
         */
        sendTypingStatus: function(isTyping) {
            // This could be used for real-time features
            // For now, just for internal state management
        },
        
        /**
         * Scroll to bottom of messages
         */
        scrollToBottom: function() {
            var container = $('#chatbot-messages')[0];
            container.scrollTop = container.scrollHeight;
        },
        
        /**
         * Get current time formatted
         */
        getCurrentTime: function() {
            var now = new Date();
            return now.getHours().toString().padStart(2, '0') + ':' + 
                   now.getMinutes().toString().padStart(2, '0');
        },
        
        /**
         * Load conversation history
         */
        loadConversationHistory: function() {
            var self = this;
            
            Ajax.call([{
                methodname: 'local_chatbot_get_history',
                args: {
                    sessionid: this.config.sessionid,
                    limit: 20
                },
                done: function(history) {
                    if (history && history.length > 0) {
                        self.displayHistory(history);
                    }
                },
                fail: function(error) {
                    console.error('Failed to load history:', error);
                }
            }]);
        },
        
        /**
         * Display conversation history
         */
        displayHistory: function(history) {
            var self = this;
            
            history.forEach(function(item) {
                self.addMessage('user', item.message, {historical: true});
                self.addMessage('bot', item.response, {historical: true});
            });
            
            // Add separator
            $('#chatbot-messages').append(
                '<div class="history-separator">Conversación anterior</div>'
            );
        },
        
        /**
         * Show welcome message
         */
        showWelcomeMessage: function() {
            var hour = new Date().getHours();
            var greeting;
            
            if (hour < 12) {
                greeting = '¡Buenos días';
            } else if (hour < 19) {
                greeting = '¡Buenas tardes';
            } else {
                greeting = '¡Buenas noches';
            }
            
            var firstname = this.config.username ? this.config.username.split(' ')[0] : '';
            var template = this.config.strings.welcome || '';
            var welcomeMessage = greeting + ', ' + firstname + '! 👋\n\n' + template.replace('{name}', firstname);

            this.addMessage('bot', welcomeMessage.trim(), {system: true});
        },
        
        /**
         * Export conversation
         */
        exportConversation: function() {
            if (!this.config.permissions.canexport) {
                return;
            }

            var self = this;

            Ajax.call([{
                methodname: 'local_chatbot_export_conversation',
                args: {
                    sessionid: this.config.sessionid,
                    format: 'html'
                },
                done: function(data) {
                    self.downloadFile('conversacion_' + Date.now() + '.html', data, 'text/html');
                    self.showToast('Conversación exportada exitosamente', 'success');
                },
                fail: function(error) {
                    self.showToast('Error al exportar la conversación', 'error');
                }
            }]);
        },
        
        /**
         * Download file
         */
        downloadFile: function(filename, content, mimeType) {
            var blob = new Blob([content], {type: mimeType});
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        
        /**
         * Initialize keyboard shortcuts
         */
        initKeyboardShortcuts: function() {
            var self = this;
            
            $(document).on('keydown', function(e) {
                // Ctrl/Cmd + Shift + C: Toggle chatbot
                if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.keyCode === 67) {
                    e.preventDefault();
                    self.toggleChatbot();
                }
                
                // ESC: Close chatbot
                if (e.keyCode === 27 && self.isOpen) {
                    self.closeChatbot();
                }
            });
        },
        
        /**
         * Initialize idle detection
         */
        initIdleDetection: function() {
            var self = this;
            var idleTime = 0;
            
            setInterval(function() {
                idleTime++;
                
                // After 5 minutes of inactivity
                if (idleTime > 5 && self.isOpen) {
                    self.addMessage('bot', 
                        '¿Sigues ahí? Si necesitas algo más, no dudes en preguntarme. 😊',
                        {system: true}
                    );
                    idleTime = 0;
                }
            }, 60000); // Check every minute
            
            // Reset idle time on activity
            $(document).on('mousemove keypress', function() {
                idleTime = 0;
            });
        },
        
        /**
         * Monitor connection
         */
        monitorConnection: function() {
            var self = this;
            
            window.addEventListener('online', function() {
                self.updateConnectionStatus(true);
            });
            
            window.addEventListener('offline', function() {
                self.updateConnectionStatus(false);
            });
        },
        
        /**
         * Update connection status
         */
        updateConnectionStatus: function(isOnline) {
            if (isOnline) {
                $('.chatbot-status').text('En línea y listo para ayudar');
                $('.chatbot-status-indicator').removeClass('offline');
            } else {
                $('.chatbot-status').text('Sin conexión');
                $('.chatbot-status-indicator').addClass('offline');
                this.showToast('Sin conexión a internet', 'warning');
            }
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type) {
            var toast = $('<div class="chatbot-toast chatbot-toast-' + type + '">' + message + '</div>');
            $('body').append(toast);
            
            toast.fadeIn(300);
            
            setTimeout(function() {
                toast.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Apply theme styles
         */
        applyTheme: function() {
            // Theme-specific customizations can be added here
            var theme = this.config.theme;
            
            switch (theme) {
                case 'dark':
                    $('#chatbot-widget').addClass('theme-dark');
                    break;
                case 'minimal':
                    $('#chatbot-widget').addClass('theme-minimal');
                    break;
                case 'colorful':
                    $('#chatbot-widget').addClass('theme-colorful');
                    break;
                default:
                    // Modern theme (default)
                    break;
            }
        },
        
        /**
         * Update context
         */
        updateContext: function(newContext) {
            Object.assign(this.currentContext, newContext);
        },
        
        /**
         * Update contextual content
         */
        updateContextualContent: function() {
            // Refresh quick actions and suggestions based on current context
            if (this.config.features.quick_actions) {
                this.loadQuickActions();
            }
            
            if (this.config.features.suggestions) {
                this.loadSuggestions();
            }
        },
        
        /**
         * Load suggestions
         */
        loadSuggestions: function() {
            var self = this;
            
            Ajax.call([{
                methodname: 'local_chatbot_get_suggestions',
                args: {
                    context: JSON.stringify(this.currentContext)
                },
                done: function(suggestions) {
                    if (suggestions && suggestions.length > 0) {
                        self.showSuggestions(suggestions);
                    }
                },
                fail: function(error) {
                    console.error('Failed to load suggestions:', error);
                }
            }]);
        },
        
        /**
         * Mark messages as read
         */
        markMessagesAsRead: function() {
            // Implementation for marking messages as read
            // Could be used for notification badges
        },
        
        /**
         * Process emojis in text
         */
        processEmojis: function(text) {
            var emojiMap = {
                ':)': '😊',
                ':-)': '😊',
                ':(': '😞',
                ':-(': '😞',
                ':D': '😃',
                ':-D': '😃',
                ':P': '😛',
                ':-P': '😛',
                ';)': '😉',
                ';-)': '😉',
                ':o': '😮',
                ':-o': '😮',
                ':|': '😐',
                ':-|': '😐',
                '<3': '❤️',
                '</3': '💔',
                ':*': '😘',
                ':-*': '😘'
            };
            
            for (var emoji in emojiMap) {
                text = text.replace(new RegExp(emoji.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'g'), emojiMap[emoji]);
            }
            
            return text;
        },
        
        /**
         * Animate text character by character
         */
        animateText: function($element, text) {
            // Simple implementation - can be enhanced
            $element.html(text);
        },
        
        /**
         * Play notification sound
         */
        playNotificationSound: function() {
            // Implementation for playing sound
            // Would need audio file
        },
        
        /**
         * Execute custom action
         */
        executeCustomAction: function(action, data) {
            // Send custom action to server for processing
            var self = this;
            
            Ajax.call([{
                methodname: 'local_chatbot_execute_action',
                args: {
                    action: action,
                    params: JSON.stringify(data.params || {})
                },
                done: function(response) {
                    if (response.message) {
                        self.addMessage('bot', response.message, {action_response: true});
                    }
                },
                fail: function(error) {
                    self.showToast('Error al ejecutar la acción', 'error');
                }
            }]);
        },
        
        /**
         * Execute actions from response
         */
        executeActions: function(actions) {
            var self = this;
            
            actions.forEach(function(action) {
                switch (action.type) {
                    case 'navigate':
                        setTimeout(function() {
                            window.location.href = action.url;
                        }, action.delay || 1000);
                        break;
                        
                    case 'open_modal':
                        // Implementation for opening modal
                        break;
                        
                    case 'show_notification':
                        self.showToast(action.message, action.level || 'info');
                        break;
                        
                    default:
                        console.log('Unknown action type:', action.type);
                }
            });
        },
        
        /**
         * Start voice input
         */
        startVoiceInput: function() {
            var self = this;
            
            if (!('webkitSpeechRecognition' in window)) {
                this.showToast('Tu navegador no soporta entrada de voz', 'warning');
                return;
            }
            
            var recognition = new webkitSpeechRecognition();
            recognition.lang = this.config.language || 'es-ES';
            recognition.interimResults = true;
            recognition.maxAlternatives = 1;
            
            recognition.onstart = function() {
                $('.chatbot-btn-voice').addClass('recording');
                self.showToast('Escuchando...', 'info');
            };
            
            recognition.onresult = function(event) {
                var transcript = event.results[0][0].transcript;
                $('#chatbot-input').val(transcript);
                self.updateCharCount();
            };
            
            recognition.onerror = function(event) {
                $('.chatbot-btn-voice').removeClass('recording');
                self.showToast('Error en el reconocimiento de voz', 'error');
            };
            
            recognition.onend = function() {
                $('.chatbot-btn-voice').removeClass('recording');
            };
            
            recognition.start();
        },
        
        /**
         * Toggle emoji picker
         */
        toggleEmojiPicker: function() {
            // Simple emoji picker implementation
            var emojis = ['😊', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😉', '😇',
                         '😍', '🥰', '😘', '😗', '😙', '😚', '🙂', '🤗', '🤔', '🤐',
                         '😐', '😑', '😶', '🙄', '😏', '😣', '😥', '😮', '🤯', '😪',
                         '😫', '😴', '😌', '😛', '😜', '😝', '🤤', '😒', '😓', '😔'];
            
            if ($('#emoji-picker').length === 0) {
                var picker = $('<div id="emoji-picker" class="emoji-picker"></div>');
                
                emojis.forEach(function(emoji) {
                    picker.append('<span class="emoji-option">' + emoji + '</span>');
                });
                
                $('#chatbot-footer').append(picker);
                
                $('.emoji-option').on('click', function() {
                    var emoji = $(this).text();
                    var currentText = $('#chatbot-input').val();
                    $('#chatbot-input').val(currentText + emoji).trigger('input');
                    $('#emoji-picker').hide();
                });
            } else {
                $('#emoji-picker').toggle();
            }
        }
    };
    
    return {
        init: function(config) {
            IntelligentChatbot.init(config);
        }
    };
});
