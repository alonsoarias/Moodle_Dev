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

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/templates'], 
function($, Ajax, Notification, Str, Templates) {
    
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
         * Initialize the chatbot
         */
        init: function(config) {
            this.config = config;
            this.currentContext = {
                courseid: config.courseid,
                contextid: config.contextid,
                sessionid: config.sessionid
            };
            
            this.createWidget();
            this.attachEventHandlers();
            this.initializeFeatures();
            this.loadConversationHistory();
            this.showWelcomeMessage();
        },
        
        /**
         * Create the widget HTML structure
         */
        createWidget: function() {
            var self = this;
            
            var widgetHTML = `
                <div id="chatbot-widget" class="chatbot-${this.config.position} chatbot-theme-${this.config.theme}" style="display: none;">
                    <div id="chatbot-header">
                        <div class="chatbot-header-info">
                            <div class="chatbot-avatar">
                                <span class="chatbot-status-indicator"></span>
                            </div>
                            <div class="chatbot-header-text">
                                <span class="chatbot-title">Asistente Inteligente</span>
                                <span class="chatbot-status">En línea y listo para ayudar</span>
                            </div>
                        </div>
                        <div class="chatbot-header-actions">
                            <button class="chatbot-btn-minimize" title="Minimizar">−</button>
                            <button class="chatbot-btn-export" title="Exportar conversación">📥</button>
                            <button class="chatbot-btn-close" title="Cerrar">×</button>
                        </div>
                    </div>
                    
                    <div id="chatbot-quick-actions" style="display: none;">
                        <div class="quick-actions-container"></div>
                    </div>
                    
                    <div id="chatbot-body">
                        <div id="chatbot-messages"></div>
                        <div id="chatbot-typing-indicator" style="display: none;">
                            <div class="typing-dots">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            <span class="typing-text">El asistente está escribiendo...</span>
                        </div>
                    </div>
                    
                    <div id="chatbot-suggestions" style="display: none;">
                        <div class="suggestions-container"></div>
                    </div>
                    
                    <div id="chatbot-footer">
                        <div class="chatbot-input-container">
                            <textarea id="chatbot-input" 
                                     placeholder="Escribe tu mensaje aquí..." 
                                     rows="1"></textarea>
                            <div class="chatbot-input-actions">
                                ${this.config.features.voice_input ? 
                                  '<button class="chatbot-btn-voice" title="Entrada de voz">🎤</button>' : ''}
                                ${this.config.features.emoji_picker ? 
                                  '<button class="chatbot-btn-emoji" title="Emojis">😊</button>' : ''}
                                <button id="chatbot-send" class="chatbot-btn-send" title="Enviar">
                                    <svg viewBox="0 0 24 24" width="20" height="20">
                                        <path fill="currentColor" d="M2 21l21-9L2 3v7l15 2-15 2v7z"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="chatbot-char-count">
                            <span id="char-count">0</span>/500
                        </div>
                    </div>
                </div>
                
                <button id="chatbot-launcher" class="chatbot-launcher chatbot-${this.config.position}">
                    <div class="chatbot-launcher-icon">
                        <svg viewBox="0 0 24 24" width="28" height="28">
                            <path fill="white" d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/>
                        </svg>
                    </div>
                    <span class="chatbot-launcher-badge" style="display: none;">0</span>
                </button>
            `;
            
            $('#local-chatbot-container').html(widgetHTML);
            
            // Add custom theme styles if needed
            this.applyTheme();
        },
        
        /**
         * Attach event handlers
         */
        attachEventHandlers: function() {
            var self = this;
            
            // Launcher click
            $('#chatbot-launcher').on('click', function() {
                self.toggleChatbot();
            });
            
            // Header buttons
            $('.chatbot-btn-close').on('click', function() {
                self.closeChatbot();
            });
            
            $('.chatbot-btn-minimize').on('click', function() {
                self.minimizeChatbot();
            });
            
            $('.chatbot-btn-export').on('click', function() {
                self.exportConversation();
            });
            
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
            var self = this;
            
            $('#chatbot-widget').fadeIn(300, function() {
                $('#chatbot-input').focus();
                self.scrollToBottom();
            });
            
            $('#chatbot-launcher').fadeOut(200);
            this.isOpen = true;
            
            // Clear badge
            $('.chatbot-launcher-badge').hide().text('0');
            
            // Mark messages as read
            this.markMessagesAsRead();
            
            // Load context-specific content
            this.updateContextualContent();
        },
        
        /**
         * Close chatbot
         */
        closeChatbot: function() {
            $('#chatbot-widget').fadeOut(300);
            $('#chatbot-launcher').fadeIn(200);
            this.isOpen = false;
        },
        
        /**
         * Minimize chatbot
         */
        minimizeChatbot: function() {
            $('#chatbot-widget').addClass('minimized');
            setTimeout(() => {
                $('#chatbot-widget').removeClass('minimized').hide();
                $('#chatbot-launcher').fadeIn(200);
                this.isOpen = false;
            }, 300);
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
            $('#char-count').text(count);
            
            if (count > 450) {
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
            
            var welcomeMessage = greeting + ', ' + this.config.username.split(' ')[0] + '! 👋\n\n' +
                'Soy tu asistente inteligente y estoy aquí para ayudarte con todo lo que necesites. ' +
                'Puedo ayudarte con información sobre cursos, tareas, calificaciones y mucho más.\n\n' +
                '¿En qué puedo asistirte hoy?';
            
            this.addMessage('bot', welcomeMessage, {system: true});
        },
        
        /**
         * Export conversation
         */
        exportConversation: function() {
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
