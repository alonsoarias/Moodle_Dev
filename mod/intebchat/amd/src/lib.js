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
 * Main JavaScript for INTEB Chat module with Conversations Management
 *
 * @module     mod_intebchat/lib
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification', 'core/modal_save_cancel', 'core/modal_delete_cancel', 'core/templates'],
    function ($, Ajax, Str, Notification, ModalSaveCancel, ModalDeleteCancel, Templates) {
        var questionString = 'Ask a question...';
        var errorString = 'An error occurred! Please try again later.';
        var currentConversationId = null;
        var currentInputMode = 'text'; // 'text' or 'audio' - default to text
        var lastInputMode = 'text'; // Track the last input mode used
        var tokenInfo = {
            enabled: false,
            limit: 0,
            used: 0,
            exceeded: false,
            resetTime: 0
        };
        var strings = {};
        var audioConfig = {
            enabled: false,
            mode: 'text'
        };

        /**
         * Token Tracker for real-time updates
         */
        var TokenTracker = {
            used: 0,
            limit: 0,
            audioUsed: 0,
            textUsed: 0,
            
            init: function(initialUsed, limit) {
                this.used = initialUsed;
                this.limit = limit;
                this.updateDisplay();
            },
            
            addTokens: function(tokenInfo) {
                if (!tokenInfo) return;
                
                this.used += tokenInfo.total || 0;
                
                if (tokenInfo.audio_input || tokenInfo.audio_output) {
                    this.audioUsed += (tokenInfo.audio_input || 0) + (tokenInfo.audio_output || 0);
                }
                
                if (tokenInfo.prompt || tokenInfo.completion) {
                    this.textUsed += (tokenInfo.prompt || 0) + (tokenInfo.completion || 0) - 
                                    ((tokenInfo.audio_input || 0) + (tokenInfo.audio_output || 0));
                }
                
                this.updateDisplay();
                this.checkLimits();
            },
            
            updateDisplay: function() {
                var percentage = this.limit > 0 ? (this.used / this.limit * 100) : 0;
                
                // Update main display
                $('.token-count').text(percentage.toFixed(1) + '%');
                $('.progress-bar').css('width', Math.min(percentage, 100) + '%');
                
                // Update detailed breakdown if available
                if (this.audioUsed > 0 || this.textUsed > 0) {
                    if (!$('#token-breakdown').length) {
                        $('.token-display').after(
                            '<div id="token-breakdown" class="token-breakdown">' +
                            '<small>' + strings.texttokens + ': <span id="text-tokens">0</span> | ' +
                            strings.audiotokens + ': <span id="audio-tokens">0</span></small>' +
                            '</div>'
                        );
                    }
                    $('#text-tokens').text(this.textUsed.toLocaleString());
                    $('#audio-tokens').text(this.audioUsed.toLocaleString());
                }
                
                // Update progress bar color
                $('.progress-bar').removeClass('warning danger');
                if (percentage >= 90) {
                    $('.progress-bar').addClass('danger');
                } else if (percentage > 75) {
                    $('.progress-bar').addClass('warning');
                }
            },
            
            checkLimits: function() {
                var percentage = this.limit > 0 ? (this.used / this.limit * 100) : 0;
                
                if (percentage >= 100) {
                    // Disable input when limit exceeded
                    $('#openai_input').prop('disabled', true);
                    $('#go').prop('disabled', true);
                    $('#intebchat-icon-mic').prop('disabled', true);
                    
                    // Show alert
                    if (!$('.token-limit-alert').length) {
                        $('#intebchat_log').before(
                            '<div class="alert alert-danger token-limit-alert">' +
                            '<i class="fa fa-exclamation-circle"></i> ' +
                            strings.tokenlimitexceeded +
                            '</div>'
                        );
                    }
                } else if (percentage > 90) {
                    // Show warning
                    var remaining = this.limit - this.used;
                    if (!$('.token-warning-alert').length) {
                        $('#intebchat_log').before(
                            '<div class="alert alert-warning token-warning-alert">' +
                            '<i class="fa fa-exclamation-triangle"></i> ' +
                            strings.tokenlimitwarning.replace('{$a}', remaining) +
                            '</div>'
                        );
                    }
                }
            }
        };

        /**
         * Initialize the module with conversation management
         * @param {Object} data Configuration data
         */
        var init = function (data) {
            console.log('INTEBCHAT: Initializing with data:', data);
            
            var instanceId = data.instanceId;
            var api_type = data.api_type;
            var persistConvo = data.persistConvo;

            // Initialize token info
            tokenInfo.enabled = data.tokenLimitEnabled || false;
            tokenInfo.limit = data.tokenLimit || 0;
            tokenInfo.used = data.tokensUsed || 0;
            tokenInfo.exceeded = data.tokenLimitExceeded || false;
            tokenInfo.resetTime = data.resetTime || 0;

            // Initialize audio config
            audioConfig.enabled = data.audioEnabled || false;
            audioConfig.mode = data.audioMode || 'text';

            // Initialize current mode for mixed mode
            if (audioConfig.mode === 'both') {
                currentInputMode = sessionStorage.getItem('intebchat_input_mode_' + instanceId) || 'text';
                lastInputMode = currentInputMode;
            }

            // Update UI based on token limit status
            updateTokenUI();

            // Initialize dark mode
            initDarkMode();

            // Load strings first
            loadStrings().then(function () {
                // Initialize conversation management after strings are loaded
                initializeConversations(instanceId);

                // Set placeholder
                if ($('#openai_input').length) {
                    $('#openai_input').attr('placeholder', strings.askaquestion);
                }
            });

            // Initialize token tracker if enabled
            if (tokenInfo.enabled) {
                TokenTracker.init(tokenInfo.used, tokenInfo.limit);
                
                // Hook into AJAX responses to update token count
                $(document).ajaxComplete(function(event, xhr, settings) {
                    if (settings.url && settings.url.includes('/mod/intebchat/api/completion.php')) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.tokenInfo) {
                                TokenTracker.addTokens(response.tokenInfo);
                            }
                        } catch(e) {
                            console.error('Error processing token info:', e);
                        }
                    }
                });
            }

            // Event listeners for chat input - adjusted for audio modes
            if (audioConfig.mode === 'text' || audioConfig.mode === 'both') {
                $(document).on('keyup', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function (e) {
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        if (e.target.value !== "" && !tokenInfo.exceeded) {
                            lastInputMode = 'text'; // Track that text was used
                            sendMessage(e.target.value, instanceId, api_type);
                            e.target.value = '';
                        }
                    }
                });

                $(document).on('click', '.mod_intebchat[data-instance-id="' + instanceId + '"] #go', function (e) {
                    var input = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input');
                    
                    if (!tokenInfo.exceeded && input.val() !== "") {
                        lastInputMode = 'text'; // Track that text was used
                        sendMessage(input.val(), instanceId, api_type);
                        input.val('');
                    }
                });
            }

            // Audio mode specific handlers
            if (audioConfig.enabled) {
                // For both audio-only mode AND both mode, automatically send when recording stops
                if (audioConfig.mode === 'audio' || audioConfig.mode === 'both') {
                    $(document).on('audio-ready', '#intebchat-icon-stop', function () {
                        var audioData = $('#intebchat-recorded-audio').val();
                        if (audioData && !tokenInfo.exceeded) {
                            lastInputMode = 'audio'; // Track that audio was used
                            setTimeout(function() {
                                sendAudioMessage(instanceId, api_type);
                            }, 100);
                        }
                    });
                }
            }

            // New conversation button
            $(document).on('click', '#new-conversation-btn', function (e) {
                createNewConversation(instanceId);
            });

            // Clear conversation button with modal
            $(document).on('click', '#clear-conversation-btn', function (e) {
                if (currentConversationId) {
                    showClearConversationModal(currentConversationId, instanceId);
                }
            });

            // Edit title button with modal
            $(document).on('click', '#edit-title-btn', function (e) {
                if (currentConversationId) {
                    showEditTitleModal(currentConversationId);
                }
            });

            // Conversation item click
            $(document).on('click', '.intebchat-conversation-item', function (e) {
                var conversationId = $(this).data('conversation-id');
                loadConversation(conversationId, instanceId);
            });

            // Search conversations
            $(document).on('input', '#conversation-search', function (e) {
                filterConversations(e.target.value);
            });

            // Mobile menu toggle
            $(document).on('click', '#mobile-menu-toggle', function (e) {
                $('#conversations-sidebar').toggleClass('mobile-open');
            });

            // Auto-resize textarea
            if ($('#openai_input').length) {
                $(document).on('input', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function (e) {
                    this.style.height = 'auto';
                    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
                });
            }

            // Check token limit periodically if enabled
            if (tokenInfo.enabled) {
                setInterval(checkTokenReset, 60000); // Check every minute
            }

            // Start with a new conversation if none exists
            if ($('.intebchat-conversation-item').length === 0) {
                createNewConversation(instanceId);
            }
        };

        /**
         * Dark mode detection and management with visible toggle
         */
        var initDarkMode = function() {
            var $container = $('.mod_intebchat');
            
            // Check for saved preference first
            var savedTheme = localStorage.getItem('intebchat_theme');
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            // Apply initial theme
            if (savedTheme === 'dark' || (savedTheme === null && prefersDark)) {
                $container.addClass('dark-mode');
            }
            
            // Handle theme toggle click
            $(document).on('click', '#theme-toggle-btn', function(e) {
                e.preventDefault();
                
                if ($container.hasClass('dark-mode')) {
                    $container.removeClass('dark-mode');
                    localStorage.setItem('intebchat_theme', 'light');
                    $(this).find('i').removeClass('fa-moon').addClass('fa-sun');
                    $(this).attr('title', strings.darkmode || 'Switch to dark mode');
                } else {
                    $container.addClass('dark-mode');
                    localStorage.setItem('intebchat_theme', 'dark');
                    $(this).find('i').removeClass('fa-sun').addClass('fa-moon');
                    $(this).attr('title', strings.lightmode || 'Switch to light mode');
                }
            });
            
            // Listen for system theme changes
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                    if (localStorage.getItem('intebchat_theme') === null) {
                        if (e.matches) {
                            $container.addClass('dark-mode');
                            $('#theme-toggle-btn i').removeClass('fa-sun').addClass('fa-moon');
                        } else {
                            $container.removeClass('dark-mode');
                            $('#theme-toggle-btn i').removeClass('fa-moon').addClass('fa-sun');
                        }
                    }
                });
            }
        };

        /**
         * Send audio message
         */
        var sendAudioMessage = function (instanceId, api_type) {
            var audioData = $('#intebchat-recorded-audio').val();
            if (!audioData) {
                return;
            }

            var doSend = function () {
                // Use the last input mode to determine response mode
                var responseMode = (audioConfig.mode === 'both') ? lastInputMode : audioConfig.mode;
                
                addToChatLog('user transcribing', '<i class="fa fa-microphone"></i> ' +
                    (strings.transcribing || 'Transcribing...'), instanceId);
                    
                // Pass the response mode expected
                createCompletion('', instanceId, api_type, responseMode);
            };

            if (!currentConversationId) {
                Ajax.call([{
                    methodname: 'mod_intebchat_create_conversation',
                    args: { instanceid: instanceId },
                    done: function (response) {
                        currentConversationId = response.conversationid;
                        $('#conversation-title').text(response.title);
                        var conversationHtml = createConversationListItem(response);
                        if ($('.intebchat-no-conversations').length > 0) {
                            $('.intebchat-conversations-list').html(conversationHtml);
                        } else {
                            $('.intebchat-conversations-list').prepend(conversationHtml);
                        }
                        $('.intebchat-conversation-item').removeClass('active');
                        $('.intebchat-conversation-item[data-conversation-id="' +
                            currentConversationId + '"]').addClass('active');
                        doSend();
                    }
                }]);
            } else {
                doSend();
            }
        };

        /**
         * Load all required strings
         */
        var loadStrings = function () {
            var stringkeys = [
                { key: 'askaquestion', component: 'mod_intebchat' },
                { key: 'erroroccurred', component: 'mod_intebchat' },
                { key: 'newconversation', component: 'mod_intebchat' },
                { key: 'confirmclear', component: 'mod_intebchat' },
                { key: 'conversationcleared', component: 'mod_intebchat' },
                { key: 'loadingconversation', component: 'mod_intebchat' },
                { key: 'edittitle', component: 'mod_intebchat' },
                { key: 'clearconversation', component: 'mod_intebchat' },
                { key: 'cancel', component: 'core' },
                { key: 'save', component: 'core' },
                { key: 'delete', component: 'core' },
                { key: 'conversationtitle', component: 'mod_intebchat' },
                { key: 'confirmclearmessage', component: 'mod_intebchat' },
                { key: 'transcribing', component: 'mod_intebchat' },
                { key: 'switchtoaudiomode', component: 'mod_intebchat' },
                { key: 'switchtotextmode', component: 'mod_intebchat' },
                { key: 'tokenlimitexceeded', component: 'mod_intebchat' },
                { key: 'switchtheme', component: 'mod_intebchat' },
                { key: 'darkmode', component: 'mod_intebchat' },
                { key: 'lightmode', component: 'mod_intebchat' },
                { key: 'texttokens', component: 'mod_intebchat' },
                { key: 'audiotokens', component: 'mod_intebchat' },
                { key: 'tokenlimitwarning', component: 'mod_intebchat' },
                { key: 'reasoningmodelwarning', component: 'mod_intebchat' },
                { key: 'conversationtitleupdated', component: 'mod_intebchat' }
            ];

            return Str.get_strings(stringkeys).then(function (results) {
                strings.askaquestion = results[0];
                strings.erroroccurred = results[1];
                strings.newconversation = results[2];
                strings.confirmclear = results[3];
                strings.conversationcleared = results[4];
                strings.loadingconversation = results[5];
                strings.edittitle = results[6];
                strings.clearconversation = results[7];
                strings.cancel = results[8];
                strings.save = results[9];
                strings.delete = results[10];
                strings.conversationtitle = results[11];
                strings.confirmclearmessage = results[12];
                strings.transcribing = results[13];
                strings.switchtoaudiomode = results[14];
                strings.switchtotextmode = results[15];
                strings.tokenlimitexceeded = results[16];
                strings.switchtheme = results[17];
                strings.darkmode = results[18];
                strings.lightmode = results[19];
                strings.texttokens = results[20];
                strings.audiotokens = results[21];
                strings.tokenlimitwarning = results[22];
                strings.reasoningmodelwarning = results[23];
                strings.conversationtitleupdated = results[24];

                questionString = strings.askaquestion;
                errorString = strings.erroroccurred;
            });
        };

        /**
         * Show modal for editing conversation title - Updated for Moodle 4.3+
         */
        var showEditTitleModal = function (conversationId) {
            var currentTitle = $('#conversation-title').text();

            // Create modal using the new approach
            ModalSaveCancel.create({
                title: strings.edittitle,
                body: '<div class="form-group">' +
                    '<label for="conversation-title-input">' + strings.conversationtitle + '</label>' +
                    '<input type="text" class="form-control" id="conversation-title-input" value="' +
                    currentTitle.replace(/"/g, '&quot;') + '">' +
                    '</div>',
                buttons: {
                    save: strings.save,
                    cancel: strings.cancel
                },
                show: true
            }).then(function(modal) {
                // Handle save
                modal.getRoot().on('save', function(e) {
                    e.preventDefault();
                    var newTitle = $('#conversation-title-input').val().trim();
                    if (newTitle && newTitle !== currentTitle) {
                        updateConversationTitle(conversationId, newTitle);
                    }
                    modal.destroy();
                });

                // Focus input when modal is shown  
                modal.getRoot().on('shown', function() {
                    $('#conversation-title-input').focus().select();
                });

                // Handle enter key in input
                modal.getRoot().on('keypress', '#conversation-title-input', function(e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        modal.getRoot().find('[data-action="save"]').trigger('click');
                    }
                });

                return modal;
            }).catch(Notification.exception);
        };

        /**
         * Show modal for clearing conversation - Updated for Moodle 4.3+
         */
        var showClearConversationModal = function (conversationId, instanceId) {
            // Create modal using the new approach
            ModalDeleteCancel.create({
                title: strings.clearconversation,
                body: '<p>' + strings.confirmclearmessage + '</p>',
                buttons: {
                    delete: strings.delete,
                    cancel: strings.cancel
                },
                show: true
            }).then(function(modal) {
                // Handle delete
                modal.getRoot().on('delete', function(e) {
                    e.preventDefault();
                    clearConversation(conversationId, instanceId);
                    modal.destroy();
                });

                return modal;
            }).catch(Notification.exception);
        };

        /**
         * Initialize conversation management
         */
        var initializeConversations = function (instanceId) {
            // Load first conversation if exists
            var firstConversation = $('.intebchat-conversation-item').first();
            if (firstConversation.length > 0) {
                firstConversation.click();
            }
        };

        /**
         * Create a new conversation
         */
        var createNewConversation = function (instanceId) {
            Ajax.call([{
                methodname: 'mod_intebchat_create_conversation',
                args: { instanceid: instanceId },
                done: function (response) {
                    currentConversationId = response.conversationid;

                    // Clear chat log
                    $('#intebchat_log').empty();

                    // Update header
                    $('#conversation-title').text(response.title);

                    // Add to sidebar
                    var conversationHtml = createConversationListItem(response);
                    if ($('.intebchat-no-conversations').length > 0) {
                        $('.intebchat-conversations-list').html(conversationHtml);
                    } else {
                        $('.intebchat-conversations-list').prepend(conversationHtml);
                    }

                    // Set as active
                    $('.intebchat-conversation-item').removeClass('active');
                    $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]').addClass('active');

                    // Focus input
                    if ($('#openai_input').length) {
                        $('#openai_input').focus();
                    }
                },
                fail: function (error) {
                    Notification.addNotification({
                        message: error.message || strings.erroroccurred,
                        type: 'error'
                    });
                }
            }]);
        };

        /**
         * Load a conversation
         */
        var loadConversation = function (conversationId, instanceId) {
            // Show loading state
            $('#intebchat_log').html('<div class="loading-conversation text-center p-4">' +
                '<i class="fa fa-spinner fa-spin"></i> ' +
                strings.loadingconversation +
                '</div>');

            Ajax.call([{
                methodname: 'mod_intebchat_load_conversation',
                args: {
                    conversationid: conversationId,
                    instanceid: instanceId
                },
                done: function (response) {
                    console.log('Conversation loaded:', response);
                    
                    currentConversationId = conversationId;

                    // Update header
                    $('#conversation-title').text(response.title);

                    // Clear and load messages
                    $('#intebchat_log').empty();
                    
                    if (response.messages && response.messages.length > 0) {
                        response.messages.forEach(function (msg) {
                            addToChatLog(msg.role === 'user' ? 'user' : 'bot', msg.message, instanceId, false);
                        });
                    }

                    // Store threadId if exists (for Assistant API)
                    if (response.threadId) {
                        $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]')
                            .attr('data-thread-id', response.threadId);
                    }

                    // Update active state
                    $('.intebchat-conversation-item').removeClass('active');
                    $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]').addClass('active');

                    // Close mobile sidebar
                    $('#conversations-sidebar').removeClass('mobile-open');

                    // Scroll to bottom
                    var messageContainer = $('#intebchat_log');
                    messageContainer.animate({
                        scrollTop: messageContainer[0].scrollHeight
                    }, 300);
                    
                    // Focus input
                    $('#openai_input').focus();
                },
                fail: function (error) {
                    console.error('Error loading conversation:', error);
                    $('#intebchat_log').empty();
                    
                    var errorMessage = strings.erroroccurred;
                    if (error.message) {
                        errorMessage = error.message;
                    } else if (error.error) {
                        errorMessage = error.error;
                    }
                    
                    addToChatLog('bot error', errorMessage, instanceId);
                    
                    Notification.addNotification({
                        message: errorMessage,
                        type: 'error'
                    });
                }
            }]);
        };

        /**
         * Clear a conversation
         */
        var clearConversation = function (conversationId, instanceId) {
            Ajax.call([{
                methodname: 'mod_intebchat_clear_conversation',
                args: { conversationid: conversationId },
                done: function (response) {
                    console.log('Clear conversation response:', response);
                    
                    if (response.deleted) {
                        // Conversation was deleted completely
                        $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]')
                            .fadeOut(300, function () {
                                $(this).remove();

                                // Check if there are any conversations left
                                if ($('.intebchat-conversation-item').length === 0) {
                                    // No conversations left, create a new one
                                    createNewConversation(instanceId);
                                } else {
                                    // Select the first available conversation
                                    var firstConv = $('.intebchat-conversation-item').first();
                                    if (firstConv.length > 0) {
                                        firstConv.click();
                                    }
                                }
                            });

                        // Show notification
                        Notification.addNotification({
                            message: strings.conversationcleared,
                            type: 'success'
                        });
                    } else {
                        // Conversation was cleared but not deleted
                        $('#intebchat_log').empty();

                        // Update the preview in sidebar to empty
                        var $conversationItem = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
                        $conversationItem.find('.intebchat-conversation-preview').text('');
                        
                        // Remove stored threadId
                        $conversationItem.removeAttr('data-thread-id');

                        // Show notification
                        Notification.addNotification({
                            message: strings.conversationcleared,
                            type: 'success'
                        });
                    }
                },
                fail: function (error) {
                    console.error('Error clearing conversation:', error);
                    Notification.addNotification({
                        message: error.message || strings.erroroccurred,
                        type: 'error'
                    });
                }
            }]);
        };

        /**
         * Update conversation title
         */
        var updateConversationTitle = function (conversationId, newTitle) {
            if (!newTitle || newTitle.trim() === '') {
                return;
            }
            
            Ajax.call([{
                methodname: 'mod_intebchat_update_conversation_title',
                args: {
                    conversationid: conversationId,
                    title: newTitle
                },
                done: function (response) {
                    console.log('Title update response:', response);
                    
                    if (response && response.success) {
                        // Update header
                        $('#conversation-title').text(newTitle);
                        
                        // Update sidebar
                        var $item = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
                        $item.find('.title-text').text(newTitle);
                        $item.attr('data-title', newTitle);

                        Notification.addNotification({
                            message: strings.conversationtitleupdated || 'Title updated successfully',
                            type: 'success'
                        });
                    } else {
                        Notification.addNotification({
                            message: strings.erroroccurred,
                            type: 'error'
                        });
                    }
                },
                fail: function (error) {
                    console.error('Error updating title:', error);
                    Notification.addNotification({
                        message: error.message || strings.erroroccurred,
                        type: 'error'
                    });
                }
            }]);
        };

        /**
         * Refresh a conversation in the sidebar
         */
        var refreshConversationInSidebar = function (conversationId) {
            var $item = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
            if ($item.length) {
                // Update the modified time
                var now = new Date();
                $item.find('.intebchat-conversation-date').text(
                    now.toLocaleDateString([], { day: '2-digit', month: '2-digit' })
                );

                // Move to top if not already there
                if (!$item.is(':first-child')) {
                    $item.fadeOut(200, function () {
                        $(this).prependTo('.intebchat-conversations-list').fadeIn(200);
                    });
                }
            }
        };

        /**
         * Filter conversations
         */
        var filterConversations = function (searchTerm) {
            searchTerm = searchTerm.toLowerCase();

            $('.intebchat-conversation-item').each(function () {
                var title = $(this).find('.title-text').text().toLowerCase();
                var preview = $(this).find('.intebchat-conversation-preview').text().toLowerCase();

                if (title.includes(searchTerm) || preview.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        };

        /**
         * Create conversation list item HTML
         */
        var createConversationListItem = function (conversation) {
            var date = new Date(conversation.lastmessage * 1000);
            var dateStr = date.toLocaleDateString([], { day: '2-digit', month: '2-digit' });

            return '<div class="intebchat-conversation-item" ' +
                'data-conversation-id="' + conversation.conversationid + '" ' +
                'data-title="' + conversation.title + '">' +
                '<div class="intebchat-conversation-title">' +
                '<span class="title-text">' + conversation.title + '</span>' +
                '<span class="intebchat-conversation-date">' + dateStr + '</span>' +
                '</div>' +
                '<div class="intebchat-conversation-preview">' + conversation.preview + '</div>' +
                '</div>';
        };

        /**
         * Send message (enhanced with conversation management)
         */
        var sendMessage = function (message, instanceId, api_type) {
            // Create new conversation if none exists
            if (!currentConversationId) {
                // Create conversation first, then send message
                Ajax.call([{
                    methodname: 'mod_intebchat_create_conversation',
                    args: { instanceid: instanceId },
                    done: function (response) {
                        currentConversationId = response.conversationid;

                        // Update header
                        $('#conversation-title').text(response.title);

                        // Add to sidebar
                        var conversationHtml = createConversationListItem(response);
                        if ($('.intebchat-no-conversations').length > 0) {
                            $('.intebchat-conversations-list').html(conversationHtml);
                        } else {
                            $('.intebchat-conversations-list').prepend(conversationHtml);
                        }

                        // Set as active
                        $('.intebchat-conversation-item').removeClass('active');
                        $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]').addClass('active');

                        // Now send the message
                        addToChatLog('user', message, instanceId);
                        // Pass the response mode based on input type
                        var responseMode = (audioConfig.mode === 'both') ? lastInputMode : 'text';
                        createCompletion(message, instanceId, api_type, responseMode);
                    },
                    fail: function (error) {
                        Notification.addNotification({
                            message: error.message || errorString,
                            type: 'error'
                        });
                    }
                }]);
                return;
            }

            addToChatLog('user', message, instanceId);
            // Pass the response mode based on input type
            var responseMode = (audioConfig.mode === 'both') ? lastInputMode : 'text';
            createCompletion(message, instanceId, api_type, responseMode);
        };

        /**
         * Update UI based on token limit status
         */
        var updateTokenUI = function () {
            if (!tokenInfo.enabled) {
                return;
            }

            var $container = $('.mod_intebchat');
            var $input = $container.find('#openai_input');
            var $submitBtn = $container.find('#go');
            var $progressBar = $container.find('.progress-bar');

            if (tokenInfo.exceeded) {
                $input.prop('disabled', true);
                $submitBtn.prop('disabled', true);
            } else {
                $input.prop('disabled', false);
                $submitBtn.prop('disabled', false);
            }

            // Update progress bar
            if ($progressBar.length) {
                var percentage = (tokenInfo.used / tokenInfo.limit * 100);
                $progressBar.css('width', percentage + '%');

                // Update color based on usage
                $progressBar.removeClass('warning danger');
                if (percentage > 90) {
                    $progressBar.addClass('danger');
                } else if (percentage > 75) {
                    $progressBar.addClass('warning');
                }
            }
        };

        /**
         * Check if token limit has reset
         */
        var checkTokenReset = function () {
            var now = Date.now() / 1000;
            if (tokenInfo.exceeded && now > tokenInfo.resetTime) {
                // Reload page to refresh token status
                window.location.reload();
            }
        };

        /**
         * Add a message to the chat UI
         * @param {string} type Which side of the UI the message should be on. Can be "user" or "bot"
         * @param {string} message The text of the message to add
         * @param {int} instanceId The ID of the instance to manipulate
         * @param {boolean} animate Whether to animate the message
         */
        var addToChatLog = function (type, message, instanceId, animate = true) {
            var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');

            // Remove transcribing message if exists
            if (type !== 'user transcribing') {
                messageContainer.find('.openai_message.transcribing').remove();
            }

            var messageElem = $('<div></div>').addClass('openai_message').addClass(type.replace(' ', '-'));
            var messageText = $('<span></span>').html(message);
            messageElem.append(messageText);

            // Add timestamp
            var timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            var timestampElem = $('<span></span>').addClass('message-timestamp').text(timestamp);
            messageElem.append(timestampElem);

            if (animate) {
                messageElem.hide();
                messageContainer.append(messageElem);
                messageElem.fadeIn(300);
            } else {
                messageContainer.append(messageElem);
            }

            // Smooth scroll to bottom
            messageContainer.animate({
                scrollTop: messageContainer[0].scrollHeight
            }, 300);
        };

        /**
         * Makes an API request to get a completion from GPT
         * @param {string} message The text to get a completion for
         * @param {int} instanceId The ID of the instance
         * @param {string} api_type "assistant" | "chat" The type of API to use
         * @param {string} responseMode The desired response mode ('text' or 'audio')
         */
        var createCompletion = function (message, instanceId, api_type, responseMode) {
            var threadId = null;
            
            // Try to get threadId from current conversation
            if (currentConversationId) {
                var $conversationItem = $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]');
                if ($conversationItem.length && $conversationItem.data('thread-id')) {
                    threadId = $conversationItem.data('thread-id');
                }
            }

            // Build history from current conversation
            var history = buildTranscript(instanceId);

            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').addClass('disabled');
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').removeClass('error');
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', questionString);
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').blur();

            if (!$('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message.transcribing').length) {
                addToChatLog('bot loading', '...', instanceId);
            }

            var audio = $('#intebchat-recorded-audio').val();
            
            // Prepare request data with correct response mode
            var requestData = {
                message: message,
                history: history,
                instanceId: instanceId,
                conversationId: currentConversationId || null,
                threadId: threadId,
                audio: audio || null,
                responseMode: responseMode || 'text' // Use the passed response mode
            };
            
            // Debug log
            console.log('Sending completion request:', requestData);
            
            $.ajax({
                url: M.cfg.wwwroot + '/mod/intebchat/api/completion.php',
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(requestData),
                success: function (data) {
                    console.log('Completion response:', data);
                    
                    $('#intebchat-recorded-audio').val('');
                    var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');

                    // Remove loading or transcribing message
                    messageContainer.find('.openai_message.bot-loading, .openai_message.user-transcribing').remove();

                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                    if (data.message) {
                        // If we had audio input, replace the transcribing message with actual transcription
                        if (audio && data.transcription) {
                            messageContainer.find('.openai_message.user-transcribing').remove();
                            var userContent = data.transcription;
                            if (data.useraudio) {
                                userContent = '<audio controls autoplay src="' + data.useraudio + '"></audio>' +
                                    '<div class="transcription">' + data.transcription + '</div>';
                            }
                            addToChatLog('user', userContent, instanceId);
                        }

                        addToChatLog('bot', data.message, instanceId);

                        // Update conversation ID if returned
                        if (data.conversationId && !currentConversationId) {
                            currentConversationId = data.conversationId;
                        }
                        
                        // Store threadId if returned (for assistant API)
                        if (data.threadId && currentConversationId) {
                            $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]')
                                .attr('data-thread-id', data.threadId);
                        }

                        // Update conversation preview
                        if (currentConversationId) {
                            updateConversationPreview(currentConversationId, data.transcription || message);
                        }

                        // Update token usage if provided
                        if (data.tokenInfo && tokenInfo.enabled) {
                            TokenTracker.addTokens(data.tokenInfo);
                        }
                    } else if (data.error) {
                        console.error('Server error:', data.error);
                        if (data.error.type === 'token_limit_exceeded') {
                            tokenInfo.exceeded = true;
                            updateTokenUI();
                            Notification.addNotification({
                                message: data.error.message,
                                type: 'error'
                            });
                        } else {
                            addToChatLog('bot error', data.error.message, instanceId);
                        }
                    }
                    if ($('#openai_input').length) {
                        $('#openai_input').focus();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX error:', status, error, xhr.responseText);
                    var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
                    messageContainer.find('.openai_message.bot-loading, .openai_message.user-transcribing').remove();
                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                    var errorMsg = errorString;
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response.error) {
                            errorMsg = response.error.message || response.error;
                        }
                    } catch (e) {
                        errorMsg = errorString + ' (' + error + ')';
                    }

                    addToChatLog('bot error', errorMsg, instanceId);
                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').addClass('error');
                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', errorString);
                }
            });
        };

        /**
         * Update conversation preview in sidebar
         */
        var updateConversationPreview = function (conversationId, lastMessage) {
            if (!lastMessage) return;

            var $item = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
            if ($item.length) {
                $item.find('.intebchat-conversation-preview').text(lastMessage);
                var now = new Date();
                $item.find('.intebchat-conversation-date').text(
                    now.toLocaleDateString([], { day: '2-digit', month: '2-digit' })
                );

                // Move conversation to top if it's not already there
                if (!$item.is(':first-child')) {
                    $item.fadeOut(200, function () {
                        $(this).prependTo('.intebchat-conversations-list').fadeIn(200);
                    });
                }
            }
        };

        /**
         * Using the existing messages in the chat history, create a string that can be used to aid completion
         * @param {int} instanceId The instance from which to build the history
         * @return {Array} A transcript of the conversation up to this point
         */
        var buildTranscript = function (instanceId) {
            var transcript = [];
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message').each(function (index, element) {
                var messages = $('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message');
                if (index === messages.length - 1) {
                    return;
                }

                var user = userName;
                if ($(element).hasClass('bot')) {
                    user = assistantName;
                }

                // Remove timestamp from message text
                var messageText = $(element).clone();
                messageText.find('.message-timestamp').remove();
                messageText.find('audio').remove(); // Remove audio elements
                messageText.find('.transcription').remove(); // Remove transcription wrapper

                transcript.push({ "user": user, "message": messageText.text().trim() });
            });

            return transcript;
        };

        return {
            init: init
        };
    });