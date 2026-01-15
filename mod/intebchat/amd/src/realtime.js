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
 * OpenAI Realtime API integration with bidirectional transcription
 *
 * Features:
 * - Real-time user speech transcription displayed as they speak
 * - Real-time AI response transcription displayed as AI speaks
 * - Visual indicators for speaking states
 * - Automatic conversation persistence
 *
 * @module     mod_intebchat/realtime
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/str', 'core/notification'],
function($, Ajax, Str, Notification) {

    // WebRTC connections
    var pc = null;
    var dc = null;
    var localStream = null;
    var micTrack = null;
    var remoteAudio = null;
    var connected = false;

    // Instance configuration
    var instanceId = null;
    var currentConversationId = null;
    var strings = {};

    // Assistant mode configuration
    var useAssistant = false;
    var sessionSesskey = null;
    var audioMode = null;
    var processedCallIds = new Set(); // Track processed function call IDs to prevent duplicates
    var functionCallBuffer = '';

    // Microphone control
    var micEnabled = false;

    // Current message tracking for bidirectional transcription
    var currentUserMsgElement = null;
    var currentBotMsgElement = null;
    var currentResponseId = null;
    var isUserSpeaking = false;
    var isBotSpeaking = false;

    // Text accumulators for streaming transcription
    var userTranscriptBuffer = '';
    var botTranscriptBuffer = '';

    // Debounce timer for user speech end detection
    var userSpeechEndTimer = null;

    /**
     * Load localized strings
     */
    var loadStrings = function() {
        return Str.get_strings([
            {key: 'realtime_connecting', component: 'mod_intebchat'},
            {key: 'realtime_connected', component: 'mod_intebchat'},
            {key: 'realtime_disconnected', component: 'mod_intebchat'},
            {key: 'realtime_error', component: 'mod_intebchat'},
            {key: 'transcribing', component: 'mod_intebchat'},
            {key: 'realtime_you_speaking', component: 'mod_intebchat'},
            {key: 'realtime_ai_speaking', component: 'mod_intebchat'},
            {key: 'realtime_listening', component: 'mod_intebchat'},
            {key: 'realtime_processing', component: 'mod_intebchat'},
            {key: 'realtime_mic_start', component: 'mod_intebchat'},
            {key: 'realtime_mic_enabled', component: 'mod_intebchat'},
            {key: 'realtime_assistant_thinking', component: 'mod_intebchat'}
        ]).then(function(results) {
            strings.connecting = results[0] || 'Connecting...';
            strings.connected = results[1] || 'Connected - Speak naturally';
            strings.disconnected = results[2] || 'Disconnected';
            strings.error = results[3] || 'Connection error';
            strings.transcribing = results[4] || 'Transcribing...';
            strings.youSpeaking = results[5] || 'You are speaking...';
            strings.aiSpeaking = results[6] || 'AI is speaking...';
            strings.listening = results[7] || 'Listening...';
            strings.processing = results[8] || 'Processing...';
            strings.micStart = results[9] || 'Click to start speaking';
            strings.micEnabled = results[10] || 'Microphone active - speak now';
            strings.assistantThinking = results[11] || 'Consulting assistant...';
        });
    };

    /**
     * Initialize Realtime connection with bidirectional transcription
     */
    var init = async function(config) {
        instanceId = config.instanceId;
        currentConversationId = config.conversationId;
        audioMode = config.audioMode || 'conversacional';

        console.log('🚀 Initializing Realtime mode with bidirectional transcription');
        console.log('📌 Audio mode:', audioMode);

        await loadStrings();

        try {
            updateStatus('connecting');

            // Get ephemeral token with audiomode
            const tokenUrl = M.cfg.wwwroot + '/mod/intebchat/api/realtime_token.php?' +
                'instanceid=' + instanceId +
                '&voice=' + encodeURIComponent(config.voice || 'alloy') +
                '&audiomode=' + encodeURIComponent(audioMode) +
                '&sesskey=' + M.cfg.sesskey;

            const tokenResponse = await fetch(tokenUrl);

            if (!tokenResponse.ok) {
                throw new Error('Failed to get ephemeral token');
            }

            const tokenData = await tokenResponse.json();

            if (!tokenData.ephemeral) {
                throw new Error('No ephemeral token in response');
            }

            // Store assistant mode configuration
            useAssistant = tokenData.useAssistant || false;
            sessionSesskey = tokenData.sesskey || M.cfg.sesskey;

            if (useAssistant) {
                console.log('🤖 Assistant mode enabled - complex queries will be delegated');
            }

            // Initialize WebRTC with transcription enabled
            await initializeWebRTC(tokenData);

            // Setup visual indicators
            createRealtimeIndicators();

            // In conversacional modes, microphone starts disabled
            updateStatus('mic_disabled');

        } catch (error) {
            console.error('❌ Realtime initialization error:', error);
            updateStatus('error');
            Notification.addNotification({
                message: strings.error + ': ' + error.message,
                type: 'error'
            });
        }
    };

    /**
     * Initialize WebRTC connection with transcription configuration
     */
    var initializeWebRTC = async function(tokenData) {
        console.log('🔧 Setting up WebRTC connection with transcription...');

        // Create peer connection
        pc = new RTCPeerConnection();

        // Create and setup remote audio element
        remoteAudio = document.createElement('audio');
        remoteAudio.autoplay = true;
        remoteAudio.id = 'realtime-audio';
        remoteAudio.style.display = 'none';
        document.body.appendChild(remoteAudio);

        // Handle remote audio track - indicates bot is speaking
        pc.ontrack = (event) => {
            console.log('🔊 Remote audio track received');
            remoteAudio.srcObject = event.streams[0];

            // Monitor audio activity for speaking indicator
            monitorAudioActivity(event.streams[0], 'bot');
        };

        // Get user media (microphone)
        localStream = await navigator.mediaDevices.getUserMedia({
            audio: {
                echoCancellation: true,
                noiseSuppression: true,
                autoGainControl: true,
                sampleRate: 24000
            }
        });

        micTrack = localStream.getAudioTracks()[0];

        // Start with microphone muted - user must click to enable
        micTrack.enabled = false;
        micEnabled = false;
        console.log('🔇 Microphone starts muted - click to enable');

        pc.addTrack(micTrack, localStream);

        // Create data channel for events
        dc = pc.createDataChannel('oai-events');
        setupDataChannel();

        // Create and set local offer
        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // Send offer to OpenAI and get answer
        const model = tokenData.model || 'gpt-4o-realtime-preview-2024-12-17';
        const sdpResponse = await fetch('https://api.openai.com/v1/realtime?model=' + encodeURIComponent(model), {
            method: 'POST',
            body: offer.sdp,
            headers: {
                'Authorization': 'Bearer ' + tokenData.ephemeral,
                'Content-Type': 'application/sdp',
                'OpenAI-Beta': 'realtime=v1'
            }
        });

        const answerSdp = await sdpResponse.text();
        const answer = { type: 'answer', sdp: answerSdp };
        await pc.setRemoteDescription(answer);

        console.log('✅ WebRTC connection established');
    };

    /**
     * Monitor audio activity for visual feedback
     */
    var monitorAudioActivity = function(stream, source) {
        try {
            const audioContext = new AudioContext();
            const analyser = audioContext.createAnalyser();
            const microphone = audioContext.createMediaStreamSource(stream);
            microphone.connect(analyser);
            analyser.fftSize = 256;

            const bufferLength = analyser.frequencyBinCount;
            const dataArray = new Uint8Array(bufferLength);

            const checkAudio = function() {
                if (!connected) return;

                analyser.getByteFrequencyData(dataArray);
                let sum = dataArray.reduce((a, b) => a + b, 0);
                let average = sum / bufferLength;

                if (source === 'bot') {
                    if (average > 10) {
                        updateSpeakingIndicator('bot', true);
                    } else {
                        updateSpeakingIndicator('bot', false);
                    }
                }

                requestAnimationFrame(checkAudio);
            };

            checkAudio();
        } catch (e) {
            console.warn('Audio monitoring not supported:', e);
        }
    };

    /**
     * Setup data channel event handlers with comprehensive transcription handling
     */
    var setupDataChannel = function() {
        dc.addEventListener('open', () => {
            connected = true;
            updateStatus('connected');
            console.log('✅ Data channel opened - Ready for bidirectional conversation');

            // Configure session for transcription
            configureSession();
        });

        dc.addEventListener('close', () => {
            connected = false;
            updateStatus('disconnected');
            console.log('❌ Data channel closed');
        });

        dc.addEventListener('message', (event) => {
            try {
                const evt = JSON.parse(event.data);
                handleRealtimeEvent(evt);
            } catch (e) {
                // Ignore non-JSON messages
            }
        });
    };

    /**
     * Configure session for input/output audio transcription
     */
    var configureSession = function() {
        if (!dc || dc.readyState !== 'open') return;

        // Request both input and output transcription
        dc.send(JSON.stringify({
            type: 'session.update',
            session: {
                input_audio_transcription: {
                    model: 'whisper-1'
                },
                turn_detection: {
                    type: 'server_vad',
                    threshold: 0.5,
                    prefix_padding_ms: 300,
                    silence_duration_ms: 500
                }
            }
        }));

        console.log('📝 Session configured for bidirectional transcription');
    };

    /**
     * Handle all Realtime API events with bidirectional transcription
     */
    var handleRealtimeEvent = function(evt) {

        // ═══════════════════════════════════════════════════════════════
        // USER SPEECH EVENTS - Real-time transcription of what user says
        // ═══════════════════════════════════════════════════════════════

        // User started speaking
        if (evt.type === 'input_audio_buffer.speech_started') {
            console.log('🎤 User started speaking');
            isUserSpeaking = true;
            userTranscriptBuffer = '';
            createUserMessage();
            updateSpeakingIndicator('user', true);

            // Clear any pending end timer
            if (userSpeechEndTimer) {
                clearTimeout(userSpeechEndTimer);
                userSpeechEndTimer = null;
            }
        }

        // User speech transcription delta (streaming)
        if (evt.type === 'conversation.item.input_audio_transcription.delta') {
            const delta = evt.delta || '';
            if (delta) {
                userTranscriptBuffer += delta;
                updateUserTranscription(userTranscriptBuffer);
            }
        }

        // Alternative transcription events
        if (evt.type === 'input_audio_buffer.transcription.delta' ||
            evt.type === 'conversation.item.transcription.delta') {
            const delta = evt.delta || evt.transcript || '';
            if (delta) {
                userTranscriptBuffer += delta;
                updateUserTranscription(userTranscriptBuffer);
            }
        }

        // User stopped speaking
        if (evt.type === 'input_audio_buffer.speech_stopped') {
            console.log('🎤 User stopped speaking');
            isUserSpeaking = false;
            updateSpeakingIndicator('user', false);

            // Delay finalization to allow for final transcription
            userSpeechEndTimer = setTimeout(() => {
                if (userTranscriptBuffer) {
                    finalizeUserMessage(userTranscriptBuffer);
                }
            }, 800);
        }

        // User transcription completed
        if (evt.type === 'conversation.item.input_audio_transcription.completed') {
            const transcript = evt.transcript || userTranscriptBuffer;
            if (transcript) {
                userTranscriptBuffer = transcript;
                updateUserTranscription(transcript);
                finalizeUserMessage(transcript);
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // BOT RESPONSE EVENTS - Real-time transcription of AI response
        // ═══════════════════════════════════════════════════════════════

        // Response started
        if (evt.type === 'response.created' || evt.type === 'response.started') {
            console.log('🤖 AI response started');
            currentResponseId = evt.response?.id;
            botTranscriptBuffer = '';
            isBotSpeaking = true;
            createBotMessage();
            updateSpeakingIndicator('bot', true);
        }

        // Bot audio transcript delta (what AI is saying - transcription of audio output)
        if (evt.type === 'response.audio_transcript.delta') {
            const delta = evt.delta || '';
            if (delta) {
                botTranscriptBuffer += delta;
                updateBotTranscription(botTranscriptBuffer);
            }
        }

        // Bot text delta (text portion of response)
        if (evt.type === 'response.text.delta' ||
            evt.type === 'response.output_text.delta' ||
            evt.type === 'response.content_part.delta') {
            const delta = evt.delta || evt.text || '';
            if (delta) {
                botTranscriptBuffer += delta;
                updateBotTranscription(botTranscriptBuffer);
            }
        }

        // Response audio transcript done
        if (evt.type === 'response.audio_transcript.done') {
            const transcript = evt.transcript || botTranscriptBuffer;
            if (transcript) {
                botTranscriptBuffer = transcript;
                updateBotTranscription(transcript);
            }
        }

        // Response output item added (can contain text)
        if (evt.type === 'response.output_item.added') {
            if (evt.item?.content) {
                evt.item.content.forEach(part => {
                    if ((part.type === 'text' || part.type === 'audio') && part.transcript) {
                        botTranscriptBuffer = part.transcript;
                        updateBotTranscription(botTranscriptBuffer);
                    }
                });
            }
        }

        // Response completed
        if (evt.type === 'response.done' || evt.type === 'response.completed') {
            console.log('✅ AI response completed', evt);
            isBotSpeaking = false;
            updateSpeakingIndicator('bot', false);

            if (botTranscriptBuffer) {
                finalizeBotMessage(botTranscriptBuffer);
                saveMessageToConversation('assistant', botTranscriptBuffer);
            }

            // Handle token usage from response - check multiple possible locations
            var usage = null;

            // Try different usage locations in the event structure
            if (evt.response && evt.response.usage) {
                usage = evt.response.usage;
            } else if (evt.usage) {
                usage = evt.usage;
            } else if (evt.response && evt.response.output && evt.response.output[0] && evt.response.output[0].usage) {
                usage = evt.response.output[0].usage;
            }

            if (usage) {
                var totalTokens = (usage.input_tokens || usage.prompt_tokens || 0) +
                                  (usage.output_tokens || usage.completion_tokens || 0);

                // Add audio tokens if present
                if (usage.input_token_details && usage.input_token_details.audio_tokens) {
                    totalTokens += usage.input_token_details.audio_tokens;
                }
                if (usage.output_token_details && usage.output_token_details.audio_tokens) {
                    totalTokens += usage.output_token_details.audio_tokens;
                }

                console.log('📊 Token usage from response.done:', usage, 'Total:', totalTokens);

                // Update TokenTracker if available globally
                if (window.intebchat && window.intebchat.TokenTracker && totalTokens > 0) {
                    window.intebchat.TokenTracker.addTokens({
                        total: totalTokens,
                        prompt: usage.input_tokens || usage.prompt_tokens || 0,
                        completion: usage.output_tokens || usage.completion_tokens || 0,
                        audio_input: usage.input_token_details ? usage.input_token_details.audio_tokens || 0 : 0,
                        audio_output: usage.output_token_details ? usage.output_token_details.audio_tokens || 0 : 0
                    });
                }

                // Report token usage to backend
                if (totalTokens > 0) {
                    reportTokenUsage(totalTokens, usage);
                }
            } else {
                console.log('ℹ️ No usage data in response.done event');
            }

            currentResponseId = null;
            botTranscriptBuffer = '';
        }

        // Rate limits updated - may contain usage information
        if (evt.type === 'rate_limits.updated') {
            console.log('📊 Rate limits updated:', evt.rate_limits);
            // This event shows rate limits but not exact token counts
            // However, we can estimate usage from rate limit changes
        }

        // ═══════════════════════════════════════════════════════════════
        // FUNCTION CALL EVENTS - Handle assistant tool calls
        // ═══════════════════════════════════════════════════════════════

        // Function call started
        if (evt.type === 'response.function_call_arguments.delta') {
            const delta = evt.delta || '';
            if (delta) {
                functionCallBuffer += delta;
            }
        }

        // Function call completed
        if (evt.type === 'response.function_call_arguments.done') {
            if (evt.name === 'ask_assistant' && useAssistant) {
                console.log('🤖 Assistant function call:', evt);
                handleAssistantFunctionCall(evt.call_id, functionCallBuffer);
            }
            functionCallBuffer = '';
        }

        // Also handle output item done for function calls
        if (evt.type === 'response.output_item.done' && evt.item?.type === 'function_call') {
            if (evt.item.name === 'ask_assistant' && useAssistant) {
                console.log('🤖 Assistant function call from output_item:', evt.item);
                handleAssistantFunctionCall(evt.item.call_id, evt.item.arguments);
            }
        }

        // Response output item done
        if (evt.type === 'response.output_item.done') {
            if (evt.item?.content) {
                evt.item.content.forEach(part => {
                    if (part.transcript) {
                        botTranscriptBuffer = part.transcript;
                        updateBotTranscription(botTranscriptBuffer);
                    }
                });
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // CONVERSATION EVENTS
        // ═══════════════════════════════════════════════════════════════

        if (evt.type === 'conversation.item.created') {
            if (evt.item?.role === 'user' && evt.item?.content) {
                // User message with audio content
                evt.item.content.forEach(part => {
                    if (part.transcript) {
                        userTranscriptBuffer = part.transcript;
                        updateUserTranscription(userTranscriptBuffer);
                    }
                });
            }
        }

        // ═══════════════════════════════════════════════════════════════
        // ERROR EVENTS
        // ═══════════════════════════════════════════════════════════════

        if (evt.type === 'error') {
            console.error('❌ Realtime error:', evt.error);
            showError(evt.error?.message || 'Unknown error');
        }

        if (evt.type === 'response.error' || evt.type === 'response.failed') {
            console.error('❌ Response error:', evt);
            if (currentBotMsgElement) {
                currentBotMsgElement.addClass('error');
                updateBotTranscription('Error: ' + (evt.error?.message || 'Response failed'));
            }
            isBotSpeaking = false;
            updateSpeakingIndicator('bot', false);
        }
    };

    /**
     * Create visual indicators for speaking states
     */
    var createRealtimeIndicators = function() {
        // Create speaking indicator container
        if ($('#realtime-speaking-indicators').length === 0) {
            var userSpeakingText = strings.youSpeaking || 'You are speaking...';
            var aiSpeakingText = strings.aiSpeaking || 'AI is speaking...';

            var indicatorsHtml = `
                <div id="realtime-speaking-indicators" class="realtime-indicators">
                    <div id="user-speaking-indicator" class="speaking-indicator user-indicator" style="display:none;">
                        <i class="fa fa-microphone"></i>
                        <span class="speaking-label">${userSpeakingText}</span>
                        <div class="speaking-wave">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                    <div id="bot-speaking-indicator" class="speaking-indicator bot-indicator" style="display:none;">
                        <i class="fa fa-volume-up"></i>
                        <span class="speaking-label">${aiSpeakingText}</span>
                        <div class="speaking-wave">
                            <span></span><span></span><span></span>
                        </div>
                    </div>
                </div>
            `;
            $('#control_bar').before(indicatorsHtml);
        }
    };

    /**
     * Update speaking indicator visibility
     */
    var updateSpeakingIndicator = function(who, isSpeaking) {
        var indicatorId = who === 'user' ? '#user-speaking-indicator' : '#bot-speaking-indicator';

        if (isSpeaking) {
            $(indicatorId).fadeIn(200);
        } else {
            $(indicatorId).fadeOut(200);
        }
    };

    /**
     * Create user message element for streaming transcription
     */
    var createUserMessage = function() {
        // Remove any existing transcribing message
        $('.openai_message.user.transcribing-live').remove();

        const container = $('#intebchat_log');
        const msgId = 'user-msg-' + Date.now();

        currentUserMsgElement = $('<div>')
            .attr('id', msgId)
            .addClass('openai_message user transcribing-live')
            .html(`
                <div class="transcription-live-indicator">
                    <i class="fa fa-microphone pulse"></i>
                </div>
                <div class="transcription-text"></div>
            `);

        container.append(currentUserMsgElement);
        scrollToBottom();
    };

    /**
     * Update user transcription in real-time
     */
    var updateUserTranscription = function(text) {
        if (currentUserMsgElement && text) {
            currentUserMsgElement.find('.transcription-text').html(escapeHtml(text) + '<span class="transcription-cursor">|</span>');
            scrollToBottom();
        }
    };

    /**
     * Finalize user message
     */
    var finalizeUserMessage = function(finalText) {
        if (currentUserMsgElement) {
            currentUserMsgElement.removeClass('transcribing-live');
            currentUserMsgElement.find('.transcription-live-indicator').remove();
            currentUserMsgElement.find('.transcription-cursor').remove();

            if (finalText) {
                currentUserMsgElement.find('.transcription-text').text(finalText);

                // Add timestamp
                const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                currentUserMsgElement.append('<span class="message-timestamp">' + timestamp + '</span>');

                // Save to conversation
                saveMessageToConversation('user', finalText);
            }

            currentUserMsgElement = null;
        }

        userTranscriptBuffer = '';
    };

    /**
     * Create bot message element for streaming transcription
     */
    var createBotMessage = function() {
        const container = $('#intebchat_log');
        const msgId = 'bot-msg-' + Date.now();

        currentBotMsgElement = $('<div>')
            .attr('id', msgId)
            .addClass('openai_message bot streaming-live')
            .html(`
                <div class="transcription-live-indicator">
                    <i class="fa fa-volume-up pulse"></i>
                </div>
                <div class="transcription-text"></div>
            `);

        container.append(currentBotMsgElement);
        scrollToBottom();
    };

    /**
     * Update bot transcription in real-time
     */
    var updateBotTranscription = function(text) {
        if (currentBotMsgElement && text) {
            currentBotMsgElement.find('.transcription-text').html(escapeHtml(text) + '<span class="transcription-cursor">|</span>');
            scrollToBottom();
        }
    };

    /**
     * Finalize bot message
     */
    var finalizeBotMessage = function(finalText) {
        if (currentBotMsgElement) {
            currentBotMsgElement.removeClass('streaming-live');
            currentBotMsgElement.find('.transcription-live-indicator').remove();
            currentBotMsgElement.find('.transcription-cursor').remove();

            if (finalText) {
                currentBotMsgElement.find('.transcription-text').text(finalText);

                // Add timestamp
                const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                currentBotMsgElement.append('<span class="message-timestamp">' + timestamp + '</span>');
            }

            currentBotMsgElement = null;
        }
    };

    /**
     * Report token usage to backend
     */
    var reportTokenUsage = function(totalTokens, usageDetails) {
        if (!instanceId || totalTokens <= 0) return;

        console.log('📊 Reporting token usage to backend:', totalTokens);

        $.ajax({
            url: M.cfg.wwwroot + '/mod/intebchat/api/report_tokens.php',
            type: 'POST',
            dataType: 'json',
            contentType: 'application/json',
            data: JSON.stringify({
                instanceId: instanceId,
                tokens: totalTokens,
                inputTokens: usageDetails.input_tokens || 0,
                outputTokens: usageDetails.output_tokens || 0,
                audioInputTokens: usageDetails.input_token_details?.audio_tokens || 0,
                audioOutputTokens: usageDetails.output_token_details?.audio_tokens || 0,
                sesskey: M.cfg.sesskey
            }),
            headers: {
                'X-Sesskey': M.cfg.sesskey
            },
            success: function(response) {
                console.log('✅ Token usage reported:', response);
                // Update token info if limit exceeded
                if (response.limitExceeded && window.intebchat) {
                    window.intebchat.tokenInfo.exceeded = true;
                    // Optionally show warning
                    Notification.addNotification({
                        message: response.message || 'Token limit exceeded',
                        type: 'warning'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.warn('Could not report token usage:', error);
            }
        });
    };

    /**
     * Save message to conversation via AJAX
     */
    var saveMessageToConversation = function(role, text) {
        if (!text || !instanceId) return;

        console.log('💾 Saving message:', { role, text: text.substring(0, 50) + '...' });

        // Use Moodle AJAX to save the message
        Ajax.call([{
            methodname: 'mod_intebchat_save_realtime_message',
            args: {
                instanceid: instanceId,
                conversationid: currentConversationId || 0,
                role: role,
                message: text
            },
            done: function(response) {
                console.log('✅ Message saved:', response);
                // Update conversation ID if a new one was created
                if (response.conversationid && !currentConversationId) {
                    currentConversationId = response.conversationid;
                    console.log('📝 New conversation created:', currentConversationId);
                }
            },
            fail: function(error) {
                console.warn('Could not save message:', error);
            }
        }]);
    };

    /**
     * Show error message
     */
    var showError = function(message) {
        const container = $('#intebchat_log');
        const errorElement = $('<div>')
            .addClass('openai_message bot-error')
            .html('<i class="fa fa-exclamation-triangle"></i> ' + escapeHtml(message));

        container.append(errorElement);
        scrollToBottom();
    };

    /**
     * Send text message
     */
    var sendTextMessage = function(text) {
        if (!connected || !dc || !text) return;

        console.log('📤 Sending text message:', text);

        // Add user message to chat immediately
        const container = $('#intebchat_log');
        const timestamp = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        const userMsg = $('<div>')
            .addClass('openai_message user')
            .html(escapeHtml(text) + '<span class="message-timestamp">' + timestamp + '</span>');

        container.append(userMsg);
        scrollToBottom();

        // Send to API
        dc.send(JSON.stringify({
            type: 'conversation.item.create',
            item: {
                type: 'message',
                role: 'user',
                content: [{ type: 'input_text', text: text }]
            }
        }));

        // Request response
        dc.send(JSON.stringify({ type: 'response.create' }));

        saveMessageToConversation('user', text);
    };

    /**
     * Utility: Escape HTML
     */
    var escapeHtml = function(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    };

    /**
     * Utility: Scroll to bottom
     */
    var scrollToBottom = function() {
        const container = $('#intebchat_log');
        if (container.length) {
            container.scrollTop(container[0].scrollHeight);
        }
    };

    /**
     * Toggle microphone
     */
    var toggleMicrophone = function(enabled) {
        if (typeof enabled === 'undefined') {
            // Toggle current state
            enabled = !micEnabled;
        }

        if (micTrack) {
            micTrack.enabled = enabled;
            micEnabled = enabled;
            updateMicrophoneUI(enabled);
            updateStatus(enabled ? 'connected' : 'mic_disabled');
            console.log('🎤 Microphone:', enabled ? 'ON' : 'OFF');
        }
    };

    /**
     * Update microphone UI
     */
    var updateMicrophoneUI = function(enabled) {
        const micButton = $('#realtime-mic-toggle');
        if (enabled) {
            micButton.removeClass('btn-secondary btn-outline-secondary').addClass('btn-danger');
            micButton.html('<i class="fa fa-microphone"></i> ' + (strings.micEnabled || 'Mic: ON'));
            micButton.attr('title', strings.micEnabled || 'Microphone active');
        } else {
            micButton.removeClass('btn-danger').addClass('btn-outline-secondary');
            micButton.html('<i class="fa fa-microphone-slash"></i> ' + (strings.micStart || 'Click to speak'));
            micButton.attr('title', strings.micStart || 'Click to start speaking');
        }
    };

    /**
     * Handle assistant function call from Realtime API
     */
    var handleAssistantFunctionCall = async function(callId, argumentsJson) {
        if (!useAssistant) {
            console.warn('Assistant function called but useAssistant is false');
            return;
        }

        // Prevent duplicate processing of the same call
        if (processedCallIds.has(callId)) {
            console.log('🔄 Skipping duplicate function call:', callId);
            return;
        }
        processedCallIds.add(callId);

        console.log('🤖 Handling assistant function call:', callId);

        try {
            // Parse the arguments
            let args = {};
            try {
                args = JSON.parse(argumentsJson);
            } catch (e) {
                console.error('Failed to parse function arguments:', e);
                args = { question: argumentsJson };
            }

            const question = args.question || '';
            if (!question) {
                console.warn('No question provided to assistant function');
                sendFunctionResult(callId, 'No question provided');
                return;
            }

            // Show thinking indicator
            updateStatus('assistant_thinking');

            // Call the assistant endpoint
            const response = await $.ajax({
                url: M.cfg.wwwroot + '/mod/intebchat/api/realtime_assistant.php',
                type: 'POST',
                dataType: 'json',
                contentType: 'application/json',
                data: JSON.stringify({
                    instanceId: instanceId,
                    question: question,
                    conversationId: currentConversationId,
                    sesskey: sessionSesskey
                }),
                headers: {
                    'X-Sesskey': sessionSesskey
                }
            });

            console.log('🤖 Assistant response:', response);

            if (response.success && response.message) {
                // Update conversation ID from assistant response
                if (response.conversationId) {
                    currentConversationId = response.conversationId;
                    console.log('📝 Using assistant conversation ID:', currentConversationId);
                }

                // Send the result back to the Realtime API
                // Ask Realtime to communicate naturally but faithfully
                var naturalResponse = 'Respuesta del asistente especializado (comunícala de forma natural y conversacional, ' +
                    'manteniendo el contenido y significado pero usando tu propia voz):\n\n' +
                    response.message;
                sendFunctionResult(callId, naturalResponse);
            } else {
                sendFunctionResult(callId, response.error || 'Failed to get response from assistant');
            }

        } catch (error) {
            console.error('Error calling assistant:', error);
            sendFunctionResult(callId, 'Error consulting assistant: ' + (error.message || 'Unknown error'));
        }

        updateStatus(micEnabled ? 'connected' : 'mic_disabled');
    };

    /**
     * Send function result back to Realtime API
     */
    var sendFunctionResult = function(callId, result) {
        if (!dc || dc.readyState !== 'open') {
            console.warn('Data channel not open, cannot send function result');
            return;
        }

        console.log('📤 Sending function result for call:', callId);

        dc.send(JSON.stringify({
            type: 'conversation.item.create',
            item: {
                type: 'function_call_output',
                call_id: callId,
                output: result
            }
        }));

        // Trigger response generation
        dc.send(JSON.stringify({
            type: 'response.create'
        }));
    };

    /**
     * Update connection status
     */
    var updateStatus = function(status) {
        const statusElement = $('#realtime-status');
        const statusTexts = {
            'connecting': strings.connecting || '🔄 Connecting...',
            'connected': strings.connected || '✅ Connected - Speak naturally',
            'disconnected': strings.disconnected || '❌ Disconnected',
            'error': strings.error || '⚠️ Error',
            'mic_disabled': strings.micStart || '🎤 Click to start speaking',
            'assistant_thinking': strings.assistantThinking || '🤖 Consulting assistant...'
        };

        statusElement.text(statusTexts[status] || status);
        statusElement.removeClass('text-warning text-success text-danger text-info text-muted');

        if (status === 'connected') {
            statusElement.addClass('text-success');
        } else if (status === 'error' || status === 'disconnected') {
            statusElement.addClass('text-danger');
        } else if (status === 'mic_disabled') {
            statusElement.addClass('text-muted');
        } else if (status === 'assistant_thinking') {
            statusElement.addClass('text-info');
        } else {
            statusElement.addClass('text-warning');
        }
    };

    /**
     * Cleanup resources
     */
    var cleanup = function() {
        console.log('🧹 Cleaning up Realtime connection');

        if (userSpeechEndTimer) {
            clearTimeout(userSpeechEndTimer);
        }

        if (dc) dc.close();
        if (pc) pc.close();
        if (localStream) {
            localStream.getTracks().forEach(track => track.stop());
        }
        if (remoteAudio) {
            remoteAudio.remove();
        }

        $('#realtime-speaking-indicators').remove();

        pc = null;
        dc = null;
        localStream = null;
        micTrack = null;
        remoteAudio = null;
        connected = false;

        updateStatus('disconnected');
    };

    /**
     * Set conversation ID (for synchronization with lib.js)
     */
    var setConversationId = function(id) {
        if (id && id !== currentConversationId) {
            currentConversationId = id;
            console.log('📝 Realtime conversation ID updated:', id);
        }
    };

    /**
     * Get current conversation ID
     */
    var getConversationId = function() {
        return currentConversationId;
    };

    return {
        init: init,
        sendText: sendTextMessage,
        toggleMic: toggleMicrophone,
        cleanup: cleanup,
        isConnected: function() { return connected; },
        setConversationId: setConversationId,
        getConversationId: getConversationId
    };
});
