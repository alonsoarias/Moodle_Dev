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

define(['jquery', 'core/ajax', 'core/str', 'core/notification', 'core/modal_factory', 'core/modal_events', 'core/templates'], 
function($, Ajax, Str, Notification, ModalFactory, ModalEvents, Templates) {
    var questionString = 'Ask a question...';
    var errorString = 'An error occurred! Please try again later.';
    var currentConversationId = null;
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
     * Initialize the module with conversation management
     * @param {Object} data Configuration data
     */
    var init = function(data) {
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

        // Update UI based on token limit status
        updateTokenUI();

        // Load strings first
        loadStrings().then(function() {
            // Initialize conversation management after strings are loaded
            initializeConversations(instanceId);
            
            // Set placeholder
            if ($('#openai_input').length) {
                $('#openai_input').attr('placeholder', strings.askaquestion);
            }
        });

        // Event listeners for chat input - adjusted for audio modes
        if (audioConfig.mode === 'text' || audioConfig.mode === 'both') {
            $(document).on('keyup', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function(e) {
                if (e.which === 13 && !e.shiftKey && e.target.value !== "") {
                    e.preventDefault();
                    if (!tokenInfo.exceeded) {
                        sendMessage(e.target.value, instanceId, api_type);
                        e.target.value = '';
                    }
                }
            });

            $(document).on('click', '.mod_intebchat[data-instance-id="' + instanceId + '"] #go', function(e) {
                var input = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input');
                if (input.val() !== "" && !tokenInfo.exceeded) {
                    sendMessage(input.val(), instanceId, api_type);
                    input.val('');
                }
            });
        }

        // Audio mode specific handlers
        if (audioConfig.enabled) {
            if (audioConfig.mode === 'audio') {
                // For audio-only mode, automatically send when recording stops
                $(document).on('audio-ready', '#intebchat-icon-stop', function() {
                    var audioData = $('#intebchat-recorded-audio').val();
                    if (audioData && !tokenInfo.exceeded) {
                        sendAudioMessage(instanceId, api_type);
                    }
                });
            } else if (audioConfig.mode === 'both') {
                // For both mode, send button is available for text, audio sends automatically
                $(document).on('audio-ready', '#intebchat-icon-stop', function() {
                    var audioData = $('#intebchat-recorded-audio').val();
                    if (audioData && !tokenInfo.exceeded) {
                        sendAudioMessage(instanceId, api_type);
                    }
                });
            }
        }

        // New conversation button
        $(document).on('click', '#new-conversation-btn', function(e) {
            createNewConversation(instanceId);
        });

        // Clear conversation button with modal
        $(document).on('click', '#clear-conversation-btn', function(e) {
            if (currentConversationId) {
                showClearConversationModal(currentConversationId, instanceId);
            }
        });

        // Edit title button with modal
        $(document).on('click', '#edit-title-btn', function(e) {
            if (currentConversationId) {
                showEditTitleModal(currentConversationId);
            }
        });

        // Conversation item click
        $(document).on('click', '.intebchat-conversation-item', function(e) {
            var conversationId = $(this).data('conversation-id');
            loadConversation(conversationId, instanceId);
        });

        // Search conversations
        $(document).on('input', '#conversation-search', function(e) {
            filterConversations(e.target.value);
        });

        // Mobile menu toggle
        $(document).on('click', '#mobile-menu-toggle', function(e) {
            $('#conversations-sidebar').toggleClass('mobile-open');
        });

        // Auto-resize textarea
        if ($('#openai_input').length) {
            $(document).on('input', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function(e) {
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
     * Send audio message
     */
var sendAudioMessage = function(instanceId, api_type) {
    var audioData = $('#intebchat-recorded-audio').val();
    if (!audioData) {
        return;
    }

    var doSend = function() {
        addToChatLog('user transcribing', '<i class="fa fa-microphone"></i> ' +
                     (strings.transcribing || 'Transcribing...'), instanceId);
        createCompletion('', instanceId, api_type);
    };

    if (!currentConversationId) {
        Ajax.call([{
            methodname: 'mod_intebchat_create_conversation',
            args: {instanceid: instanceId},
            done: function(response) {
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
    var loadStrings = function() {
        var stringkeys = [
            {key: 'askaquestion', component: 'mod_intebchat'},
            {key: 'erroroccurred', component: 'mod_intebchat'},
            {key: 'newconversation', component: 'mod_intebchat'},
            {key: 'confirmclear', component: 'mod_intebchat'},
            {key: 'conversationcleared', component: 'mod_intebchat'},
            {key: 'loadingconversation', component: 'mod_intebchat'},
            {key: 'edittitle', component: 'mod_intebchat'},
            {key: 'clearconversation', component: 'mod_intebchat'},
            {key: 'cancel', component: 'core'},
            {key: 'save', component: 'core'},
            {key: 'delete', component: 'core'},
            {key: 'conversationtitle', component: 'mod_intebchat'},
            {key: 'confirmclearmessage', component: 'mod_intebchat'}
        ];

        return Str.get_strings(stringkeys).then(function(results) {
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
            strings.conversationtitle = results[11] || 'Conversation Title';
            strings.confirmclearmessage = results[12] || 'Are you sure you want to clear this conversation? This action cannot be undone.';
            
            questionString = strings.askaquestion;
            errorString = strings.erroroccurred;
        });
    };

    /**
     * Show modal for editing conversation title
     */
    var showEditTitleModal = function(conversationId) {
        var currentTitle = $('#conversation-title').text();
        
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: strings.edittitle,
            body: '<div class="form-group">' +
                  '<label for="conversation-title-input">' + strings.conversationtitle + '</label>' +
                  '<input type="text" class="form-control" id="conversation-title-input" value="' + 
                  currentTitle.replace(/"/g, '&quot;') + '">' +
                  '</div>'
        }).then(function(modal) {
            modal.setSaveButtonText(strings.save);
            
            // Handle save
            modal.getRoot().on(ModalEvents.save, function(e) {
                var newTitle = $('#conversation-title-input').val().trim();
                if (newTitle && newTitle !== currentTitle) {
                    updateConversationTitle(conversationId, newTitle);
                }
            });
            
            // Focus input when modal is shown
            modal.getRoot().on(ModalEvents.shown, function() {
                $('#conversation-title-input').focus().select();
            });
            
            // Handle enter key in input
            modal.getRoot().on('keypress', '#conversation-title-input', function(e) {
                if (e.which === 13) {
                    modal.save();
                }
            });
            
            modal.show();
        });
    };

    /**
     * Show modal for clearing conversation
     */
    var showClearConversationModal = function(conversationId, instanceId) {
        ModalFactory.create({
            type: ModalFactory.types.SAVE_CANCEL,
            title: strings.clearconversation,
            body: '<p>' + strings.confirmclearmessage + '</p>'
        }).then(function(modal) {
            modal.setSaveButtonText(strings.delete);
            
            // Style the save button as danger
            modal.getRoot().find('.btn-primary').removeClass('btn-primary').addClass('btn-danger');
            
            // Handle delete
            modal.getRoot().on(ModalEvents.save, function(e) {
                clearConversation(conversationId, instanceId);
            });
            
            modal.show();
        });
    };

    /**
     * Initialize conversation management
     */
    var initializeConversations = function(instanceId) {
        // Load first conversation if exists
        var firstConversation = $('.intebchat-conversation-item').first();
        if (firstConversation.length > 0) {
            firstConversation.click();
        }
    };

    /**
     * Create a new conversation
     */
    var createNewConversation = function(instanceId) {
        Ajax.call([{
            methodname: 'mod_intebchat_create_conversation',
            args: {instanceid: instanceId},
            done: function(response) {
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
            fail: function(error) {
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
    var loadConversation = function(conversationId, instanceId) {
        // Show loading state
        $('#intebchat_log').html('<div class="loading-conversation">' + 
            '<i class="fa fa-spinner fa-spin"></i> ' + 
            strings.loadingconversation + 
            '</div>');
        
        Ajax.call([{
            methodname: 'mod_intebchat_load_conversation',
            args: {
                conversationid: conversationId,
                instanceid: instanceId
            },
            done: function(response) {
                currentConversationId = conversationId;
                
                // Update header
                $('#conversation-title').text(response.title);
                
                // Clear and load messages
                $('#intebchat_log').empty();
                response.messages.forEach(function(msg) {
                    addToChatLog(msg.role === 'user' ? 'user' : 'bot', msg.message, instanceId, false);
                });
                
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
            },
            fail: function(error) {
                $('#intebchat_log').empty();
                Notification.addNotification({
                    message: error.message || strings.erroroccurred,
                    type: 'error'
                });
            }
        }]);
    };

    /**
     * Clear a conversation
     */
    var clearConversation = function(conversationId, instanceId) {
        Ajax.call([{
            methodname: 'mod_intebchat_clear_conversation',
            args: {conversationid: conversationId},
            done: function(response) {
                if (response.deleted) {
                    // Conversation was deleted completely
                    // Remove from sidebar
                    $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]').fadeOut(300, function() {
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
                    // Clear chat log
                    $('#intebchat_log').empty();
                    
                    // Update the preview in sidebar to empty
                    var $conversationItem = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
                    $conversationItem.find('.intebchat-conversation-preview').text('');
                    
                    // Show notification
                    Notification.addNotification({
                        message: strings.conversationcleared,
                        type: 'success'
                    });
                    
                    // Update the conversation list to reflect the change
                    refreshConversationInSidebar(conversationId);
                }
            },
            fail: function(error) {
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
    var updateConversationTitle = function(conversationId, newTitle) {
        Ajax.call([{
            methodname: 'mod_intebchat_update_conversation_title',
            args: {
                conversationid: conversationId,
                title: newTitle
            },
            done: function(response) {
                // Update header
                $('#conversation-title').text(newTitle);
                
                // Update sidebar
                $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"] .title-text')
                    .text(newTitle);
                
                // Show success notification
                Notification.addNotification({
                    message: strings.save,
                    type: 'success'
                });
            },
            fail: function(error) {
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
    var refreshConversationInSidebar = function(conversationId) {
        var $item = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
        if ($item.length) {
            // Update the modified time
            var now = new Date();
            $item.find('.intebchat-conversation-date').text(
                now.toLocaleDateString([], {day: '2-digit', month: '2-digit'})
            );
            
            // Move to top if not already there
            if (!$item.is(':first-child')) {
                $item.fadeOut(200, function() {
                    $(this).prependTo('.intebchat-conversations-list').fadeIn(200);
                });
            }
        }
    };

    /**
     * Filter conversations
     */
    var filterConversations = function(searchTerm) {
        searchTerm = searchTerm.toLowerCase();
        
        $('.intebchat-conversation-item').each(function() {
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
    var createConversationListItem = function(conversation) {
        var date = new Date(conversation.lastmessage * 1000);
        var dateStr = date.toLocaleDateString([], {day: '2-digit', month: '2-digit'});
        
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
    var sendMessage = function(message, instanceId, api_type) {
        // Create new conversation if none exists
        if (!currentConversationId) {
            // Create conversation first, then send message
            Ajax.call([{
                methodname: 'mod_intebchat_create_conversation',
                args: {instanceid: instanceId},
                done: function(response) {
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
                    createCompletion(message, instanceId, api_type);
                },
                fail: function(error) {
                    Notification.addNotification({
                        message: error.message || errorString,
                        type: 'error'
                    });
                }
            }]);
            return;
        }
        
        addToChatLog('user', message, instanceId);
        createCompletion(message, instanceId, api_type);
    };

    /**
     * Update UI based on token limit status
     */
    var updateTokenUI = function() {
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
    var checkTokenReset = function() {
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
    var addToChatLog = function(type, message, instanceId, animate = true) {
        var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
        
        // Remove transcribing message if exists
        if (type !== 'user transcribing') {
            messageContainer.find('.openai_message.transcribing').remove();
        }
        
        var messageElem = $('<div></div>').addClass('openai_message').addClass(type.replace(' ', '-'));
        var messageText = $('<span></span>').html(message);
        messageElem.append(messageText);

        // Add timestamp
        var timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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
     */
    var createCompletion = function(message, instanceId, api_type) {
        var threadId = null;
        
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
        $.ajax({
            url: M.cfg.wwwroot + '/mod/intebchat/api/completion.php',
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                message: message,
                history: history,
                instanceId: instanceId,
                conversationId: currentConversationId || null,
                threadId: threadId,
                audio: audio || null
            }),
            success: function(data) {
                $('#intebchat-recorded-audio').val('');
                var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
                
                // Remove loading or transcribing message
                messageContainer.find('.openai_message.bot-loading, .openai_message.user-transcribing').remove();
                
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                if (data.message) {
                    // If we had audio input, replace the transcribing message with actual transcription
                    if (audio && data.transcription) {
                        messageContainer.find('.openai_message.user-transcribing').remove();
                        addToChatLog('user', data.transcription, instanceId);
                    }
                    
                    addToChatLog('bot', data.message, instanceId);
                    
                    // Update conversation ID if returned (for cases where conversation was created server-side)
                    if (data.conversationId && !currentConversationId) {
                        currentConversationId = data.conversationId;
                    }
                    
                    // Update conversation preview
                    if (currentConversationId) {
                        updateConversationPreview(currentConversationId, data.transcription || message);
                    }
                    
                    // Update token usage if provided
                    if (data.tokenInfo && tokenInfo.enabled) {
                        tokenInfo.used += data.tokenInfo.total || 0;
                        updateTokenUI();
                        
                        // Check if limit exceeded
                        if (tokenInfo.used >= tokenInfo.limit) {
                            tokenInfo.exceeded = true;
                            updateTokenUI();
                            Notification.addNotification({
                                message: strings.tokenlimitexceeded || 'Token limit exceeded',
                                type: 'error'
                            });
                        }
                    }
                } else if (data.error) {
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
            error: function(xhr, status, error) {
                var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');
                messageContainer.find('.openai_message.bot-loading, .openai_message.user-transcribing').remove();
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');
                
                var errorMsg = errorString;
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.error) {
                        errorMsg = response.error;
                    }
                } catch (e) {
                    // Use default error message
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
    var updateConversationPreview = function(conversationId, lastMessage) {
        if (!lastMessage) return;
        
        var $item = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
        if ($item.length) {
            $item.find('.intebchat-conversation-preview').text(lastMessage);
            var now = new Date();
            $item.find('.intebchat-conversation-date').text(
                now.toLocaleDateString([], {day: '2-digit', month: '2-digit'})
            );
            
            // Move conversation to top if it's not already there
            if (!$item.is(':first-child')) {
                $item.fadeOut(200, function() {
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
    var buildTranscript = function(instanceId) {
        var transcript = [];
        $('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message').each(function(index, element) {
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
            
            transcript.push({"user": user, "message": messageText.text().trim()});
        });

        return transcript;
    };

    return {
        init: init
    };
});