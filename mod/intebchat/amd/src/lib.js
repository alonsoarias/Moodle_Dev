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

define(['jquery', 'core/ajax', 'core/str', 'core/notification', 'core/modal_save_cancel', 'core/modal_delete_cancel', 'core/templates', 'mod_intebchat/mascot'],
    function ($, Ajax, Str, Notification, ModalSaveCancel, ModalDeleteCancel, Templates, Mascot) {
        var questionString = 'Ask a question...';
        var errorString = 'An error occurred! Please try again later.';
        var currentConversationId = null;
        var currentInputMode = 'text';
        var lastInputMode = 'text';
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
        var RealtimeModule = null; // Reference to Realtime module for conversation sync

        /**
         * Token Tracker for real-time updates
         */
        var TokenTracker = {
            used: 0,
            limit: 0,
            audioUsed: 0,
            textUsed: 0,

            init: function (initialUsed, limit) {
                this.used = initialUsed;
                this.limit = limit;
                this.updateDisplay();
            },

            addTokens: function (tokenInfo) {
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

            updateDisplay: function () {
                var percentage = this.limit > 0 ? (this.used / this.limit * 100) : 0;

                // Update percentage display
                $('.token-count').text(percentage.toFixed(1) + '%');

                // Update token label with current values
                var usedFormatted = this.used.toLocaleString();
                var limitFormatted = this.limit.toLocaleString();
                var labelText = 'Tokens: ' + usedFormatted + ' / ' + limitFormatted;

                // Try to get localized format if available
                if (strings.tokensusedformat) {
                    labelText = strings.tokensusedformat
                        .replace('{used}', usedFormatted)
                        .replace('{limit}', limitFormatted);
                }
                $('.token-label').text(labelText);

                // Update progress bar
                $('.progress-bar').css('width', Math.min(percentage, 100) + '%');

                // Update aria attributes for accessibility
                $('.progress').attr('aria-valuenow', this.used);

                // Show breakdown if we have audio/text distinction
                if (this.audioUsed > 0 || this.textUsed > 0) {
                    if (!$('#token-breakdown').length) {
                        $('.token-display').after(
                            '<div id="token-breakdown" class="token-breakdown">' +
                            '<small>' + (strings.texttokens || 'Text') + ': <span id="text-tokens">0</span> | ' +
                            (strings.audiotokens || 'Audio') + ': <span id="audio-tokens">0</span></small>' +
                            '</div>'
                        );
                    }
                    $('#text-tokens').text(this.textUsed.toLocaleString());
                    $('#audio-tokens').text(this.audioUsed.toLocaleString());
                }

                // Update progress bar color based on usage
                $('.progress-bar').removeClass('warning danger');
                if (percentage >= 90) {
                    $('.progress-bar').addClass('danger');
                } else if (percentage > 75) {
                    $('.progress-bar').addClass('warning');
                }
            },

            checkLimits: function () {
                var percentage = this.limit > 0 ? (this.used / this.limit * 100) : 0;

                if (percentage >= 100) {
                    $('#openai_input').prop('disabled', true);
                    $('#go').prop('disabled', true);
                    $('#intebchat-icon-mic').prop('disabled', true);

                    if (!$('.token-limit-alert').length) {
                        $('#intebchat_log').before(
                            '<div class="alert alert-danger token-limit-alert">' +
                            '<i class="fa fa-exclamation-circle"></i> ' +
                            strings.tokenlimitexceeded +
                            '</div>'
                        );
                    }
                } else if (percentage > 90) {
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
         * Message Queue for offline support
         * Queues messages when offline and sends them when connection is restored
         */
        var MessageQueue = {
            STORAGE_KEY: 'intebchat_message_queue',
            isOnline: true,
            processing: false,

            /**
             * Initialize the message queue
             */
            init: function () {
                var self = this;
                this.isOnline = navigator.onLine;

                // Listen for online/offline events
                window.addEventListener('online', function () {
                    self.isOnline = true;
                    self.updateStatusIndicator();
                    self.processQueue();
                });

                window.addEventListener('offline', function () {
                    self.isOnline = false;
                    self.updateStatusIndicator();
                });

                // Add status indicator to UI
                this.createStatusIndicator();

                // Process any existing queue on init
                if (this.isOnline && this.getQueueLength() > 0) {
                    this.processQueue();
                }
            },

            /**
             * Create the online/offline status indicator
             */
            createStatusIndicator: function () {
                var indicator = '<div id="connection-status" class="connection-status" style="display:none;">' +
                    '<i class="fa fa-wifi-slash"></i> <span>' + (strings.offlinemode || 'Offline - messages will be queued') + '</span>' +
                    '</div>';
                $('#control_bar').prepend(indicator);
                this.updateStatusIndicator();
            },

            /**
             * Update the status indicator based on connection state
             */
            updateStatusIndicator: function () {
                var $indicator = $('#connection-status');
                if (this.isOnline) {
                    $indicator.fadeOut(200);
                } else {
                    $indicator.fadeIn(200);
                }
            },

            /**
             * Add a message to the queue
             * @param {object} messageData The message data to queue
             * @return {string} Queue ID
             */
            add: function (messageData) {
                var queue = this.getQueue();
                var queueId = 'q_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);

                queue.push({
                    id: queueId,
                    data: messageData,
                    timestamp: Date.now(),
                    retries: 0
                });

                this.saveQueue(queue);
                this.showQueueBadge();

                return queueId;
            },

            /**
             * Get the message queue from localStorage
             * @return {array} Queue array
             */
            getQueue: function () {
                try {
                    var stored = localStorage.getItem(this.STORAGE_KEY);
                    return stored ? JSON.parse(stored) : [];
                } catch (e) {
                    return [];
                }
            },

            /**
             * Save the queue to localStorage
             * @param {array} queue The queue to save
             */
            saveQueue: function (queue) {
                try {
                    localStorage.setItem(this.STORAGE_KEY, JSON.stringify(queue));
                } catch (e) {
                    console.error('Failed to save message queue:', e);
                }
            },

            /**
             * Get queue length
             * @return {number} Number of queued messages
             */
            getQueueLength: function () {
                return this.getQueue().length;
            },

            /**
             * Remove a message from the queue
             * @param {string} queueId The ID of the queued message
             */
            remove: function (queueId) {
                var queue = this.getQueue().filter(function (item) {
                    return item.id !== queueId;
                });
                this.saveQueue(queue);
                this.showQueueBadge();
            },

            /**
             * Process queued messages
             */
            processQueue: function () {
                var self = this;
                if (this.processing || !this.isOnline) {
                    return;
                }

                var queue = this.getQueue();
                if (queue.length === 0) {
                    return;
                }

                this.processing = true;

                // Process first message in queue
                var item = queue[0];

                // Update queued message UI to show it's being sent
                var $queuedMsg = $('#' + item.id);
                if ($queuedMsg.length) {
                    $queuedMsg.removeClass('queued').addClass('sending');
                    $queuedMsg.find('.queue-indicator').html('<i class="fa fa-spinner fa-spin"></i>');
                }

                // Send the message
                this.sendQueuedMessage(item).then(function () {
                    // Success - remove from queue
                    self.remove(item.id);
                    self.processing = false;

                    // Process next message
                    if (self.getQueueLength() > 0) {
                        setTimeout(function () {
                            self.processQueue();
                        }, 500);
                    }
                }).catch(function (error) {
                    console.error('Failed to send queued message:', error);
                    self.processing = false;

                    // Increment retry count
                    item.retries++;
                    if (item.retries >= 3) {
                        // Remove after 3 retries
                        self.remove(item.id);
                        self.showError(item.id, strings.messagesendfailed || 'Message could not be sent');
                    } else {
                        // Update queue and retry later
                        var queue = self.getQueue();
                        queue[0] = item;
                        self.saveQueue(queue);

                        setTimeout(function () {
                            self.processQueue();
                        }, 2000);
                    }
                });
            },

            /**
             * Send a queued message
             * @param {object} item Queue item
             * @return {Promise}
             */
            sendQueuedMessage: function (item) {
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: M.cfg.wwwroot + '/mod/intebchat/api/completion.php',
                        type: 'POST',
                        dataType: 'json',
                        contentType: 'application/json',
                        data: JSON.stringify(item.data),
                        success: function (response) {
                            if (response.error) {
                                reject(response.error);
                            } else {
                                // Update UI with response
                                var $queuedMsg = $('#' + item.id);
                                if ($queuedMsg.length) {
                                    $queuedMsg.removeClass('queued sending').find('.queue-indicator').remove();
                                }

                                // Add bot response
                                if (response.message) {
                                    addToChatLog('bot', response.message, item.data.instanceId);
                                }

                                resolve(response);
                            }
                        },
                        error: function (xhr, status, error) {
                            reject(error);
                        }
                    });
                });
            },

            /**
             * Show queue badge on send button
             */
            showQueueBadge: function () {
                var count = this.getQueueLength();
                var $badge = $('#queue-badge');

                if (count > 0) {
                    if (!$badge.length) {
                        $('#go').after('<span id="queue-badge" class="badge bg-warning queue-badge">' + count + '</span>');
                    } else {
                        $badge.text(count).show();
                    }
                } else {
                    $badge.hide();
                }
            },

            /**
             * Show error for a queued message
             * @param {string} queueId Queue ID
             * @param {string} message Error message
             */
            showError: function (queueId, message) {
                var $msg = $('#' + queueId);
                if ($msg.length) {
                    $msg.removeClass('queued sending').addClass('error');
                    $msg.find('.queue-indicator').html('<i class="fa fa-exclamation-circle text-danger"></i> ' + message);
                }
            },

            /**
             * Check if we're online
             * @return {boolean}
             */
            checkOnline: function () {
                return this.isOnline;
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

            tokenInfo.enabled = data.tokenLimitEnabled || false;
            tokenInfo.limit = data.tokenLimit || 0;
            tokenInfo.used = data.tokensUsed || 0;
            tokenInfo.exceeded = data.tokenLimitExceeded || false;
            tokenInfo.resetTime = data.resetTime || 0;

            audioConfig.enabled = data.audioEnabled || false;
            audioConfig.mode = data.audioMode || 'text';
            audioConfig.voice = data.audioVoice || undefined;

            if (audioConfig.mode === 'both') {
                currentInputMode = sessionStorage.getItem('intebchat_input_mode_' + instanceId) || 'text';
                lastInputMode = currentInputMode;
            }

            updateTokenUI();
            initDarkMode(instanceId);

            loadStrings().then(function () {
                initializeConversations(instanceId);

                if ($('#openai_input').length) {
                    $('#openai_input').attr('placeholder', strings.askaquestion);
                }

                // Initialize token reset countdown timer
                initTokenResetCountdown();

                // Initialize mascot AFTER strings are loaded (floating in bottom right)
                setTimeout(function () {
                    var $main = $('.intebchat-main');
                    Mascot.init({
                        url: $main.data('mascot-url') || '',
                        name: $main.data('mascot-name') || strings.assistant || 'Assistant',
                        container: '.intebchat-main'
                    });
                }, 500);

                // Initialize message queue for offline support
                MessageQueue.init();
            });

            // Always initialize TokenTracker if token display is shown
            if (tokenInfo.enabled || tokenInfo.limit > 0) {
                TokenTracker.init(tokenInfo.used, tokenInfo.limit);
            }

            // Expose TokenTracker globally for use by other modules (e.g., realtime.js)
            window.intebchat = window.intebchat || {};
            window.intebchat.TokenTracker = TokenTracker;
            window.intebchat.tokenInfo = tokenInfo;

            // Event listeners for chat input with debounce to prevent multiple submissions
            var isSending = false; // Flag to prevent double-send

            if (audioConfig.mode === 'text' || audioConfig.mode === 'both') {
                // Use keydown instead of keyup for better UX responsiveness
                $(document).on('keydown', '.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input', function (e) {
                    if (e.which === 13 && !e.shiftKey) {
                        e.preventDefault();
                        if (isSending) {
                            return; // Prevent multiple submissions
                        }
                        if (e.target.value !== "" && !tokenInfo.exceeded) {
                            isSending = true;
                            lastInputMode = 'text';
                            sendMessage(e.target.value, instanceId, api_type);
                            e.target.value = '';
                            // Reset flag after a short delay
                            setTimeout(function() {
                                isSending = false;
                            }, 500);
                        }
                    }
                });

                $(document).on('click', '.mod_intebchat[data-instance-id="' + instanceId + '"] #go', function (e) {
                    if (isSending) {
                        return; // Prevent multiple submissions
                    }
                    var input = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input');

                    if (!tokenInfo.exceeded && input.val() !== "") {
                        isSending = true;
                        lastInputMode = 'text';
                        sendMessage(input.val(), instanceId, api_type);
                        input.val('');
                        // Reset flag after a short delay
                        setTimeout(function() {
                            isSending = false;
                        }, 500);
                    }
                });
            }

            // Audio mode handlers - using document-level custom event for reliability
            if (audioConfig.enabled) {
                console.log('INTEBCHAT: Audio enabled, mode=' + audioConfig.mode);
                if (audioConfig.mode === 'audio' || audioConfig.mode === 'both') {
                    console.log('INTEBCHAT: Registering audio-ready event handler');

                    // Listen for recording started event to update mascot
                    $(document).on('intebchat-recording-started', function () {
                        Mascot.listening();
                    });

                    // Listen for intebchat-audio-ready event on document (reliable approach)
                    $(document).on('intebchat-audio-ready', function (e, data) {
                        console.log('INTEBCHAT: Audio ready event received');
                        var audioData = $('#intebchat-recorded-audio').val();
                        console.log('INTEBCHAT: Audio data present:', !!audioData, 'tokenExceeded:', tokenInfo.exceeded);
                        if (audioData && !tokenInfo.exceeded) {
                            lastInputMode = 'audio';
                            console.log('INTEBCHAT: Calling sendAudioMessage');
                            sendAudioMessage(instanceId, api_type);
                        }
                    });
                }
            }

            // ============================================================
            // Modo conversacional Realtime
            // Handles both 'conversacional' (Chat API) and 'conversacional_assistant' (Assistant API)
            // ============================================================
            var isConversacionalMode = audioConfig.enabled &&
                (audioConfig.mode === 'conversacional' || audioConfig.mode === 'conversacional_assistant');

            if (isConversacionalMode) {
                // Initialize Realtime mode - voice only (no text input)
                require(['mod_intebchat/realtime'], function (Realtime) {
                    RealtimeModule = Realtime; // Store for conversation ID sync
                    Realtime.init({
                        instanceId: instanceId,
                        conversationId: currentConversationId,
                        voice: audioConfig.voice || 'alloy',
                        audioMode: audioConfig.mode // Pass mode for assistant tool configuration
                    });

                    // Add realtime-specific controls - mic starts DISABLED
                    var realtimeControls = '<div class="realtime-controls">' +
                        '<button id="realtime-mic-toggle" class="btn btn-outline-secondary" title="' +
                        (strings.realtime_mic_start || 'Click to start speaking') + '">' +
                        '<i class="fa fa-microphone-slash"></i> ' +
                        (strings.realtime_mic_start || 'Click to speak') + '</button> ' +
                        '<span id="realtime-status" class="text-muted ml-2">' +
                        (strings.realtime_mic_start || 'Click microphone to start') + '</span>' +
                        '</div>';
                    $('#control_bar .input-container').html(realtimeControls);

                    // Mic toggle handler - toggles between on/off
                    $('#realtime-mic-toggle').on('click', function () {
                        Realtime.toggleMic(); // Toggle without parameter
                    });
                });
            }
            // ============================================================

            // New conversation button
            $(document).on('click', '#new-conversation-btn', function (e) {
                createNewConversation(instanceId);
            });

            // Clear conversation button - CORRECCIÓN PRINCIPAL
            $(document).on('click', '#clear-conversation-btn', function (e) {
                e.preventDefault();
                if (currentConversationId) {
                    showClearConversationModal(currentConversationId, instanceId);
                }
            });

            // Edit title button - CORRECCIÓN PRINCIPAL
            $(document).on('click', '#edit-title-btn', function (e) {
                e.preventDefault();
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

            if (tokenInfo.enabled) {
                setInterval(checkTokenReset, 60000);
            }

            if ($('.intebchat-conversation-item').length === 0) {
                createNewConversation(instanceId);
            }
        };

        /**
         * Dark mode detection and management - Enhanced with user/instance persistence
         */
        var initDarkMode = function (instanceId) {
            var $container = $('.mod_intebchat');

            // Get user ID from the page or session
            var userId = M.cfg.sesskey || 'default'; // Use session key as unique user identifier

            // Create unique key for this user and instance
            var themeKey = 'intebchat_theme_' + instanceId + '_' + userId;

            // Get saved theme for this specific user and instance
            var savedTheme = localStorage.getItem(themeKey);
            var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;

            // Apply saved theme or use system preference
            if (savedTheme === 'dark' || (savedTheme === null && prefersDark)) {
                $container.addClass('dark-mode');
                $('#theme-toggle-btn i').removeClass('fa-sun').addClass('fa-moon');
            } else {
                $container.removeClass('dark-mode');
                $('#theme-toggle-btn i').removeClass('fa-moon').addClass('fa-sun');
            }

            // Handle theme toggle
            $(document).on('click', '#theme-toggle-btn', function (e) {
                e.preventDefault();

                if ($container.hasClass('dark-mode')) {
                    $container.removeClass('dark-mode');
                    localStorage.setItem(themeKey, 'light');
                    $(this).find('i').removeClass('fa-moon').addClass('fa-sun');
                    $(this).attr('title', strings.darkmode || 'Switch to dark mode');
                } else {
                    $container.addClass('dark-mode');
                    localStorage.setItem(themeKey, 'dark');
                    $(this).find('i').removeClass('fa-sun').addClass('fa-moon');
                    $(this).attr('title', strings.lightmode || 'Switch to light mode');
                }
            });

            // Listen for system theme changes only if user hasn't set a preference
            if (window.matchMedia) {
                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                    if (localStorage.getItem(themeKey) === null) {
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
            console.log('INTEBCHAT: sendAudioMessage called');
            var audioData = $('#intebchat-recorded-audio').val();
            if (!audioData) {
                console.log('INTEBCHAT: No audio data, returning');
                return;
            }
            console.log('INTEBCHAT: Audio data length:', audioData.length);

            var doSend = function () {
                var responseMode = (audioConfig.mode === 'both') ? lastInputMode : audioConfig.mode;

                // Mostrar animación de transcripción mejorada
                addToChatLog('user transcribing',
                    '<div class="transcribing-animation">' +
                    '<i class="fa fa-microphone pulse"></i> ' +
                    '<span class="dots">' +
                    '<span>.</span><span>.</span><span>.</span>' +
                    '</span> ' +
                    (strings.transcribing || 'Transcribing...') +
                    '</div>', instanceId);

                Mascot.thinking();
                createCompletion('', instanceId, api_type, responseMode);
            };

            if (!currentConversationId) {
                Ajax.call([{
                    methodname: 'mod_intebchat_create_conversation',
                    args: { instanceid: instanceId },
                    done: function (response) {
                        currentConversationId = response.conversationid;
                        // Sync with Realtime module
                        if (RealtimeModule && RealtimeModule.setConversationId) {
                            RealtimeModule.setConversationId(response.conversationid);
                        }
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
                { key: 'conversationtitleupdated', component: 'mod_intebchat' },
                { key: 'tokensusedformat', component: 'mod_intebchat' },
                { key: 'tokensresetcountdown', component: 'mod_intebchat' },
                { key: 'thinking', component: 'mod_intebchat' },
                { key: 'assistant', component: 'mod_intebchat' },
                { key: 'realtime_mic_start', component: 'mod_intebchat' }
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
                strings.tokensusedformat = results[25];
                strings.tokensresetcountdown = results[26];
                strings.thinking = results[27];
                strings.assistant = results[28];
                strings.realtime_mic_start = results[29];

                questionString = strings.askaquestion;
                errorString = strings.erroroccurred;
            });
        };

        /**
         * Show modal for editing conversation title - CORREGIDO
         */
        var showEditTitleModal = function (conversationId) {
            var currentTitle = $('#conversation-title').text();

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
                show: true,
                removeOnClose: true
            }).then(function (modal) {
                var root = modal.getRoot();

                // Handle save button click
                root.on('modal-save-cancel:save', function (e) {
                    e.preventDefault();
                    var newTitle = $('#conversation-title-input').val().trim();
                    if (newTitle && newTitle !== currentTitle) {
                        updateConversationTitle(conversationId, newTitle);
                    }
                    modal.destroy();
                });

                // Focus input when modal is shown
                root.on('shown.bs.modal', function () {
                    $('#conversation-title-input').focus().select();
                });

                // Handle enter key
                root.on('keypress', '#conversation-title-input', function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        root.find('[data-action="save"]').trigger('click');
                    }
                });

                return modal;
            }).catch(Notification.exception);
        };

        /**
         * Show modal for clearing conversation - CORREGIDO
         */
        var showClearConversationModal = function (conversationId, instanceId) {
            ModalDeleteCancel.create({
                title: strings.clearconversation,
                body: '<p>' + strings.confirmclearmessage + '</p>',
                buttons: {
                    delete: strings.delete,
                    cancel: strings.cancel
                },
                show: true,
                removeOnClose: true
            }).then(function (modal) {
                var root = modal.getRoot();

                // Handle delete button click
                root.on('modal-delete-cancel:delete', function (e) {
                    e.preventDefault();
                    clearConversation(conversationId, instanceId);
                    modal.destroy();
                });

                // Handle cancel
                root.on('modal-delete-cancel:cancel', function (e) {
                    e.preventDefault();
                    modal.destroy();
                });

                return modal;
            }).catch(Notification.exception);
        };

        /**
         * Initialize conversation management
         */
        var initializeConversations = function (instanceId) {
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
                    // Sync with Realtime module
                    if (RealtimeModule && RealtimeModule.setConversationId) {
                        RealtimeModule.setConversationId(response.conversationid);
                    }

                    $('#intebchat_log').empty();
                    $('#conversation-title').text(response.title);

                    var conversationHtml = createConversationListItem(response);
                    if ($('.intebchat-no-conversations').length > 0) {
                        $('.intebchat-conversations-list').html(conversationHtml);
                    } else {
                        $('.intebchat-conversations-list').prepend(conversationHtml);
                    }

                    $('.intebchat-conversation-item').removeClass('active');
                    $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]').addClass('active');

                    if ($('#openai_input').length) {
                        $('#openai_input').focus();
                    }

                    Mascot.wave();
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
            $('#intebchat_log').html('<div class="loading-conversation text-center p-4">' +
                '<div class="spinner-border text-primary" role="status">' +
                '<span class="sr-only">Loading...</span></div> ' +
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
                    // Sync conversation ID with Realtime module if active
                    if (RealtimeModule && RealtimeModule.setConversationId) {
                        RealtimeModule.setConversationId(conversationId);
                    }
                    $('#conversation-title').text(response.title);
                    $('#intebchat_log').empty();

                    if (response.messages && response.messages.length > 0) {
                        response.messages.forEach(function (msg) {
                            var clean = stripAutoplay(msg.message);
                            addToChatLog(msg.role === 'user' ? 'user' : 'bot', clean, instanceId, false);
                        });
                    }

                    if (response.threadId) {
                        $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]')
                            .attr('data-thread-id', response.threadId);
                    }

                    $('.intebchat-conversation-item').removeClass('active');
                    $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]').addClass('active');

                    $('#conversations-sidebar').removeClass('mobile-open');

                    // Safe scroll with existence check
                    var messageContainer = $('#intebchat_log');
                    if (messageContainer.length && messageContainer[0]) {
                        setTimeout(function() {
                            messageContainer.stop(true).animate({
                                scrollTop: messageContainer[0].scrollHeight
                            }, 300);
                        }, 50);
                    }

                    $('#openai_input').focus();
                    Mascot.idle();
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
         * Clear a conversation - CORREGIDO
         */
        var clearConversation = function (conversationId, instanceId) {
            Ajax.call([{
                methodname: 'mod_intebchat_clear_conversation',
                args: { conversationid: conversationId },
                done: function (response) {
                    console.log('Clear conversation response:', response);

                    if (response.deleted) {
                        $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]')
                            .fadeOut(300, function () {
                                $(this).remove();

                                if ($('.intebchat-conversation-item').length === 0) {
                                    createNewConversation(instanceId);
                                } else {
                                    var firstConv = $('.intebchat-conversation-item').first();
                                    if (firstConv.length > 0) {
                                        firstConv.click();
                                    }
                                }
                            });

                        Notification.addNotification({
                            message: strings.conversationcleared,
                            type: 'success'
                        });
                    } else {
                        $('#intebchat_log').empty();

                        var $conversationItem = $('.intebchat-conversation-item[data-conversation-id="' + conversationId + '"]');
                        $conversationItem.find('.intebchat-conversation-preview').text('');
                        $conversationItem.removeAttr('data-thread-id');

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
         * Update conversation title - CORREGIDO
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
                        $('#conversation-title').text(newTitle);

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
                var now = new Date();
                $item.find('.intebchat-conversation-date').text(
                    now.toLocaleDateString([], { day: '2-digit', month: '2-digit' })
                );

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
            if (!currentConversationId) {
                Ajax.call([{
                    methodname: 'mod_intebchat_create_conversation',
                    args: { instanceid: instanceId },
                    done: function (response) {
                        currentConversationId = response.conversationid;
                        // Sync with Realtime module
                        if (RealtimeModule && RealtimeModule.setConversationId) {
                            RealtimeModule.setConversationId(response.conversationid);
                        }
                        $('#conversation-title').text(response.title);

                        var conversationHtml = createConversationListItem(response);
                        if ($('.intebchat-no-conversations').length > 0) {
                            $('.intebchat-conversations-list').html(conversationHtml);
                        } else {
                            $('.intebchat-conversations-list').prepend(conversationHtml);
                        }

                        $('.intebchat-conversation-item').removeClass('active');
                        $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]').addClass('active');

                        addToChatLog('user', message, instanceId);
                        Mascot.thinking();
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
            Mascot.thinking();
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

            if ($progressBar.length) {
                var percentage = (tokenInfo.used / tokenInfo.limit * 100);
                $progressBar.css('width', percentage + '%');

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
                window.location.reload();
            }
        };

        /**
         * Initialize and update the token reset countdown timer
         */
        var countdownInterval = null;
        var initTokenResetCountdown = function () {
            var $countdownElement = $('#token-reset-countdown');
            if (!$countdownElement.length) {
                return;
            }

            var resetTimestamp = parseInt($countdownElement.data('reset-timestamp'), 10);
            if (!resetTimestamp || isNaN(resetTimestamp)) {
                return;
            }

            var updateCountdown = function () {
                var now = Math.floor(Date.now() / 1000);
                var remaining = resetTimestamp - now;

                if (remaining <= 0) {
                    $countdownElement.text(strings.tokensresetcountdown ?
                        strings.tokensresetcountdown.replace('{$a}', '00:00:00') :
                        'Resets in: 00:00:00');
                    if (countdownInterval) {
                        clearInterval(countdownInterval);
                        countdownInterval = null;
                    }
                    // Reload page after countdown ends
                    setTimeout(function () {
                        window.location.reload();
                    }, 2000);
                    return;
                }

                var hours = Math.floor(remaining / 3600);
                var minutes = Math.floor((remaining % 3600) / 60);
                var seconds = remaining % 60;

                var timeStr = String(hours).padStart(2, '0') + ':' +
                    String(minutes).padStart(2, '0') + ':' +
                    String(seconds).padStart(2, '0');

                var displayText = strings.tokensresetcountdown ?
                    strings.tokensresetcountdown.replace('{$a}', timeStr) :
                    'Resets in: ' + timeStr;

                $countdownElement.text(displayText);
            };

            // Update immediately
            updateCountdown();

            // Update every second
            if (countdownInterval) {
                clearInterval(countdownInterval);
            }
            countdownInterval = setInterval(updateCountdown, 1000);
        };

        /**
         * Remove autoplay attribute from audio tags to avoid unwanted playback
         * @param {string} html
         * @return {string}
         */
        function stripAutoplay(html) {
            var container = $('<div></div>').html(html);
            container.find('audio').each(function () {
                $(this).removeAttr('autoplay');
                this.autoplay = false;
            });
            return container.html();
        }
        /**
         * Add a message to the chat UI with improved animations
         */
        var addToChatLog = function (type, message, instanceId, animate = true) {
            var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');

            if (type !== 'user transcribing') {
                messageContainer.find('.openai_message.transcribing').remove();
            }

            var messageElem = $('<div></div>').addClass('openai_message').addClass(type.replace(' ', '-'));

            // Add typing animation for bot messages
            if (type === 'bot' && animate) {
                messageElem.addClass('typing');
                var messageText = $('<span></span>').addClass('message-content');
                var cursor = $('<span class="typing-cursor">|</span>');
                messageText.append(cursor);
                messageElem.append(messageText);

                // Simulate typing effect
                var fullMessage = message;
                var currentIndex = 0;
                var typingSpeed = 30;

                messageContainer.append(messageElem);

                var typeWriter = function () {
                    if (currentIndex < fullMessage.length) {
                        var char = fullMessage.charAt(currentIndex);
                        if (char === '<') {
                            // Handle HTML tags
                            var tagEnd = fullMessage.indexOf('>', currentIndex);
                            if (tagEnd !== -1) {
                                var tag = fullMessage.substring(currentIndex, tagEnd + 1);
                                cursor.before(tag);
                                currentIndex = tagEnd + 1;
                            } else {
                                cursor.before(char);
                                currentIndex++;
                            }
                        } else {
                            cursor.before(char);
                            currentIndex++;
                        }
                        setTimeout(typeWriter, typingSpeed);
                    } else {
                        cursor.remove();
                        messageElem.removeClass('typing');
                        Mascot.idle();
                    }
                };

                typeWriter();
            } else {
                if (type !== 'bot' || !animate) {
                    message = stripAutoplay(message);
                }
                var messageText = $('<span></span>').html(message);
                messageElem.append(messageText);

                if (animate) {
                    messageElem.hide();
                    messageContainer.append(messageElem);
                    messageElem.fadeIn(300);
                } else {
                    messageContainer.append(messageElem);
                }
            }

            // Add timestamp
            var timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            var timestampElem = $('<span></span>').addClass('message-timestamp').text(timestamp);
            messageElem.append(timestampElem);

            // Smooth scroll to bottom with safety check
            if (messageContainer.length && messageContainer[0]) {
                // Use setTimeout to ensure DOM has updated
                setTimeout(function() {
                    messageContainer.stop(true).animate({
                        scrollTop: messageContainer[0].scrollHeight
                    }, 300);
                }, 50);
            }
        };

        /**
         * Whether to use streaming for completions
         * Can be disabled for compatibility or debugging
         */
        var useStreaming = true;

        /**
         * Check if streaming is supported by the browser
         * @return {boolean} True if streaming is supported
         */
        var isStreamingSupported = function () {
            return typeof fetch !== 'undefined' &&
                   typeof ReadableStream !== 'undefined' &&
                   typeof TextDecoder !== 'undefined';
        };

        /**
         * Create a streaming completion using Server-Sent Events
         * Falls back to regular AJAX if streaming fails
         */
        var createStreamingCompletion = function (message, instanceId, responseMode) {
            var history = buildTranscript(instanceId);

            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').addClass('disabled');
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').blur();

            // Add bot message placeholder for streaming
            var streamMsgId = 'stream-msg-' + Date.now();
            var $messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');

            // Remove any existing loading message
            $messageContainer.find('.openai_message.bot-loading').remove();

            // Add streaming message container
            var $streamMsg = $('<div>')
                .attr('id', streamMsgId)
                .addClass('openai_message bot streaming')
                .html('<span class="streaming-cursor"></span>');
            $messageContainer.append($streamMsg);
            // Safe scroll with existence check
            if ($messageContainer.length && $messageContainer[0]) {
                $messageContainer.stop(true).animate({ scrollTop: $messageContainer[0].scrollHeight }, 100);
            }

            Mascot.thinking();

            var requestData = {
                message: message,
                history: history,
                instanceId: instanceId,
                conversationId: currentConversationId || null,
                responseMode: responseMode || 'text',
                sesskey: M.cfg.sesskey
            };

            var fullContent = '';
            var streamConversationId = null;

            // Create EventSource-like connection using fetch
            fetch(M.cfg.wwwroot + '/mod/intebchat/api/completion_stream.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Sesskey': M.cfg.sesskey
                },
                body: JSON.stringify(requestData)
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP error ' + response.status);
                }

                var reader = response.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';

                function processStream() {
                    return reader.read().then(function (result) {
                        if (result.done) {
                            // Stream complete - finalize message
                            finishStreaming();
                            return;
                        }

                        buffer += decoder.decode(result.value, { stream: true });

                        // Parse SSE events
                        var lines = buffer.split('\n');
                        buffer = lines.pop(); // Keep incomplete line in buffer

                        lines.forEach(function (line) {
                            if (line.startsWith('data: ')) {
                                try {
                                    var data = JSON.parse(line.substring(6));

                                    if (data.content) {
                                        // Streaming chunk
                                        fullContent += data.content;
                                        var $msg = $('#' + streamMsgId);
                                        $msg.html(fullContent + '<span class="streaming-cursor"></span>');
                                        // Safe scroll with existence check
                                        if ($messageContainer.length && $messageContainer[0]) {
                                            $messageContainer.stop(true).animate({ scrollTop: $messageContainer[0].scrollHeight }, 50);
                                        }
                                    }

                                    if (data.id) {
                                        // New conversation created
                                        streamConversationId = data.id;
                                        if (!currentConversationId) {
                                            currentConversationId = streamConversationId;
                                            // Sync with Realtime module
                                            if (RealtimeModule && RealtimeModule.setConversationId) {
                                                RealtimeModule.setConversationId(streamConversationId);
                                            }
                                        }
                                    }

                                    if (data.conversationId) {
                                        streamConversationId = data.conversationId;
                                    }

                                    if (data.tokenInfo) {
                                        // Update token tracker
                                        TokenTracker.addTokens(data.tokenInfo);
                                    }
                                } catch (e) {
                                    // Ignore parse errors for non-JSON lines
                                }
                            } else if (line.startsWith('event: ')) {
                                var eventType = line.substring(7).trim();

                                if (eventType === 'complete') {
                                    // Use success animation on stream complete
                                    Mascot.success();
                                } else if (eventType === 'error') {
                                    // Use confused animation on stream error
                                    Mascot.confused();
                                }
                            }
                        });

                        return processStream();
                    });
                }

                function finishStreaming() {
                    // Remove streaming state
                    $('#' + streamMsgId).removeClass('streaming');
                    $('#' + streamMsgId + ' .streaming-cursor').remove();

                    // Add timestamp
                    var timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    $('#' + streamMsgId).append('<span class="message-timestamp">' + timestamp + '</span>');

                    // Update conversation preview
                    if (currentConversationId) {
                        updateConversationPreview(currentConversationId, message);
                    }

                    // Re-enable controls
                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');
                    Mascot.idle();

                    if ($('#openai_input').length) {
                        $('#openai_input').focus();
                    }
                }

                return processStream();
            }).catch(function (error) {
                console.error('Streaming error:', error);
                $('#' + streamMsgId).addClass('error bot-error').html(
                    '<i class="fa fa-exclamation-circle"></i> ' +
                    (strings.erroroccurred || 'An error occurred. Please try again.')
                );
                $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');
                // Use confused animation for streaming errors
                Mascot.confused();
            });
        };

        /**
         * Makes an API request to get a completion from GPT
         * Uses streaming by default for better UX, with fallback to regular AJAX
         */
        var createCompletion = function (message, instanceId, api_type, responseMode) {
            var audio = $('#intebchat-recorded-audio').val();

            // Use streaming for text-only requests (not audio) when supported
            // NOTE: Streaming is not supported with the Assistant API, only Chat Completions
            var canUseStreaming = useStreaming &&
                                  isStreamingSupported() &&
                                  !audio &&
                                  message &&
                                  api_type !== 'assistant';

            if (canUseStreaming) {
                createStreamingCompletion(message, instanceId, responseMode);
                return;
            }

            // Regular AJAX path for audio or when streaming is not supported
            var threadId = null;

            if (currentConversationId) {
                var $conversationItem = $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]');
                if ($conversationItem.length && $conversationItem.data('thread-id')) {
                    threadId = $conversationItem.data('thread-id');
                }
            }

            var history = buildTranscript(instanceId);

            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').addClass('disabled');
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').removeClass('error');
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').attr('placeholder', questionString);
            $('.mod_intebchat[data-instance-id="' + instanceId + '"] #openai_input').blur();

            if (!$('.mod_intebchat[data-instance-id="' + instanceId + '"] .openai_message.transcribing').length) {
                addToChatLog('bot loading',
                    '<div class="loading-dots">' +
                    '<span></span><span></span><span></span>' +
                    '</div>', instanceId);
            }

            var requestData = {
                message: message,
                history: history,
                instanceId: instanceId,
                conversationId: currentConversationId || null,
                threadId: threadId,
                audio: audio || null,
                responseMode: responseMode || 'text',
                sesskey: M.cfg.sesskey // CSRF protection
            };

            console.log('Sending completion request:', requestData);

            $.ajax({
                url: M.cfg.wwwroot + '/mod/intebchat/api/completion.php',
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify(requestData),
                headers: {
                    'X-Sesskey': M.cfg.sesskey // Alternative CSRF header
                },
                success: function (data) {
                    console.log('Completion response:', data);

                    $('#intebchat-recorded-audio').val('');
                    var messageContainer = $('.mod_intebchat[data-instance-id="' + instanceId + '"] #intebchat_log');

                    messageContainer.find('.openai_message.bot-loading, .openai_message.user-transcribing').remove();

                    $('.mod_intebchat[data-instance-id="' + instanceId + '"] #control_bar').removeClass('disabled');

                    if (data.message) {
                        if (audio && data.transcription) {
                            messageContainer.find('.openai_message.user-transcribing').remove();
                            var userContent = data.transcription;
                            if (data.useraudio) {
                                // IMPORTANTE: Removido autoplay del audio del usuario
                                userContent = '<audio controls src="' + data.useraudio + '"></audio>' +
                                    '<div class="transcription">' + data.transcription + '</div>';
                            }
                            addToChatLog('user', userContent, instanceId);
                        }

                        addToChatLog('bot', data.message, instanceId);
                        // Use success animation for successful response
                        Mascot.success();

                        if (data.conversationId && !currentConversationId) {
                            currentConversationId = data.conversationId;
                            // Sync with Realtime module
                            if (RealtimeModule && RealtimeModule.setConversationId) {
                                RealtimeModule.setConversationId(data.conversationId);
                            }
                        }

                        if (data.threadId && currentConversationId) {
                            $('.intebchat-conversation-item[data-conversation-id="' + currentConversationId + '"]')
                                .attr('data-thread-id', data.threadId);
                        }

                        if (currentConversationId) {
                            updateConversationPreview(currentConversationId, data.transcription || message);
                        }

                        // Always update token display if tokenInfo is present
                        if (data.tokenInfo) {
                            TokenTracker.addTokens(data.tokenInfo);
                            console.log('Token usage updated:', data.tokenInfo);
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
                        // Use confused animation for errors
                        Mascot.confused();
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
                    // Use confused animation for connection errors
                    Mascot.confused();
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

                if (!$item.is(':first-child')) {
                    $item.fadeOut(200, function () {
                        $(this).prependTo('.intebchat-conversations-list').fadeIn(200);
                    });
                }
            }
        };

        /**
         * Maximum number of messages to include in history.
         * This limits context size to improve performance and reduce token usage.
         * Each exchange = 2 messages (user + assistant), so 10 = last 5 exchanges.
         */
        var MAX_HISTORY_MESSAGES = 20;

        /**
         * Build transcript from chat history with compression.
         *
         * Only includes the most recent messages up to MAX_HISTORY_MESSAGES
         * to prevent sending entire conversation history on every request.
         *
         * @param {string} instanceId The instance ID
         * @param {number} maxMessages Optional override for max messages (default: MAX_HISTORY_MESSAGES)
         * @return {Array} Array of message objects [{user, message}, ...]
         */
        var buildTranscript = function (instanceId, maxMessages) {
            maxMessages = maxMessages || MAX_HISTORY_MESSAGES;
            var transcript = [];
            var $container = $('.mod_intebchat[data-instance-id="' + instanceId + '"]');
            var $messages = $container.find('.openai_message');
            var totalMessages = $messages.length;

            // Calculate starting index to only include last N messages
            // Subtract 1 because we skip the last message (it's the one being sent)
            var startIndex = Math.max(0, totalMessages - maxMessages - 1);

            $messages.each(function (index, element) {
                // Skip messages before our window
                if (index < startIndex) {
                    return;
                }

                // Skip the last message (current user message being sent)
                if (index === totalMessages - 1) {
                    return;
                }

                var user = window.intebchat.userName || 'User';
                if ($(element).hasClass('bot')) {
                    user = window.intebchat.assistantName || 'Assistant';
                }

                // Clone and clean the message
                var $messageText = $(element).clone();
                $messageText.find('.message-timestamp').remove();
                $messageText.find('audio').remove();
                $messageText.find('.transcription').remove();

                var text = $messageText.text().trim();

                // Skip empty messages
                if (text) {
                    transcript.push({ "user": user, "message": text });
                }
            });

            return transcript;
        };

        return {
            init: init
        };
    });
