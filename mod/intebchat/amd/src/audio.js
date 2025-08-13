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
 * Audio recording utilities with WhatsApp-style interface and confirmation
 *
 * @module     mod_intebchat/audio
 * @copyright  2024 Eduardo Kraus
 * @copyright  2025 Enhanced by Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events'], 
    function ($, Str, ModalFactory, ModalEvents) {
    return {
        init: function (mode) {
            let chunks = [];
            let mediaRecorder = null;
            let audioMode = mode || 'text';
            let stream = null;
            let recordingStartTime = null;
            let timerInterval = null;
            let audioBlob = null;
            let audioUrl = null;
            let wasCancelled = false;

            // Load strings
            var strings = {};
            Str.get_strings([
                {key: 'recordaudio', component: 'mod_intebchat'},
                {key: 'stoprecording', component: 'mod_intebchat'},
                {key: 'cancel', component: 'mod_intebchat'},
                {key: 'send', component: 'mod_intebchat'},
                {key: 'audiorecorded', component: 'mod_intebchat'},
                {key: 'confirmaudiosend', component: 'mod_intebchat'},
                {key: 'playaudio', component: 'mod_intebchat'},
                {key: 'rerecord', component: 'mod_intebchat'}
            ]).then(function(results) {
                strings.recordaudio = results[0];
                strings.stoprecording = results[1];
                strings.cancel = results[2];
                strings.send = results[3] || 'Send';
                strings.audiorecorded = results[4] || 'Audio Recorded';
                strings.confirmaudiosend = results[5] || 'Do you want to send this audio?';
                strings.playaudio = results[6] || 'Play Audio';
                strings.rerecord = results[7] || 'Re-record';
            });

            /**
             * Create recording overlay if not exists
             */
            function createRecordingOverlay() {
                if ($('#recording-overlay').length === 0) {
                    var overlayHtml = `
                        <div id="recording-overlay" class="recording-overlay">
                            <div class="recording-container">
                                <div class="recording-timer">00:00</div>
                                <div class="recording-label">${strings.recordaudio || 'Recording...'}</div>
                                <div class="recording-wave">
                                    <div class="wave-bar"></div>
                                    <div class="wave-bar"></div>
                                    <div class="wave-bar"></div>
                                    <div class="wave-bar"></div>
                                    <div class="wave-bar"></div>
                                </div>
                                <div class="recording-controls">
                                    <button class="btn btn-cancel" id="cancel-recording">
                                        <i class="fa fa-times"></i>
                                    </button>
                                    <button class="btn btn-stop" id="stop-recording">
                                        <i class="fa fa-stop"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    $('body').append(overlayHtml);
                }
            }

            /**
             * Show audio confirmation modal
             */
            function showAudioConfirmation(audioDataUrl) {
                // Create audio preview element
                var audioPreviewHtml = `
                    <div class="audio-confirmation-content">
                        <p>${strings.confirmaudiosend}</p>
                        <audio controls src="${audioUrl}" style="width: 100%; margin: 20px 0;"></audio>
                        <div class="audio-duration" style="text-align: center; color: #666;">
                            Duration: ${formatDuration(recordingStartTime ? Math.floor((Date.now() - recordingStartTime) / 1000) : 0)}
                        </div>
                    </div>
                `;

                ModalFactory.create({
                    type: ModalFactory.types.SAVE_CANCEL,
                    title: strings.audiorecorded,
                    body: audioPreviewHtml,
                    buttons: {
                        save: strings.send,
                        cancel: strings.rerecord
                    }
                }).then(function(modal) {
                    modal.show();

                    // Handle send button
                    modal.getRoot().on(ModalEvents.save, function(e) {
                        e.preventDefault();
                        // Set the audio data and trigger the send
                        $('#intebchat-recorded-audio').val(audioDataUrl);
                        if (audioMode === 'audio' || audioMode === 'both') {
                            setTimeout(function () {
                                $('#intebchat-icon-stop').trigger('audio-ready');
                            }, 100);
                        }
                        modal.destroy();
                    });

                    // Handle re-record button
                    modal.getRoot().on(ModalEvents.cancel, function(e) {
                        e.preventDefault();
                        // Clear the audio and allow re-recording
                        reset();
                        modal.destroy();
                        // Optionally, start recording again immediately
                        // startRecording();
                    });

                    // Clean up audio URL when modal is closed
                    modal.getRoot().on(ModalEvents.hidden, function() {
                        if (audioUrl) {
                            URL.revokeObjectURL(audioUrl);
                            audioUrl = null;
                        }
                    });

                    return modal;
                }).catch(function(error) {
                    console.error('Error creating modal:', error);
                    // Fallback to confirm dialog
                    if (confirm(strings.confirmaudiosend)) {
                        $('#intebchat-recorded-audio').val(audioDataUrl);
                        if (audioMode === 'audio' || audioMode === 'both') {
                            setTimeout(function () {
                                $('#intebchat-icon-stop').trigger('audio-ready');
                            }, 100);
                        }
                    } else {
                        reset();
                    }
                });
            }

            /**
             * Format duration in mm:ss
             */
            function formatDuration(seconds) {
                var minutes = Math.floor(seconds / 60);
                var secs = seconds % 60;
                return (minutes < 10 ? '0' : '') + minutes + ':' + (secs < 10 ? '0' : '') + secs;
            }

            /**
             * Update timer display
             */
            function updateTimer() {
                if (!recordingStartTime) return;
                
                var elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
                var display = formatDuration(elapsed);
                
                $('.recording-timer').text(display);
            }

            /**
             * Start recording
             */
            function startRecording() {
                reset();
                wasCancelled = false; // Ensure flag is reset
                
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Your browser does not support recording!');
                    return;
                }

                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (userStream) {
                        stream = userStream;
                        mediaRecorder = new MediaRecorder(stream);
                        chunks = [];

                        mediaRecorder.start();
                        recordingStartTime = Date.now();
                        
                        // Show WhatsApp-style overlay
                        createRecordingOverlay();
                        $('#recording-overlay').addClass('active');
                        
                        // Start timer
                        timerInterval = setInterval(updateTimer, 100);
                        
                        // Update button states
                        $('#intebchat-icon-mic').addClass('recording').hide();
                        $('#intebchat-icon-stop').show();

                        mediaRecorder.ondataavailable = function (e) {
                            if (e.data && e.data.size > 0) {
                                chunks.push(e.data);
                            }
                        };

                        mediaRecorder.onstop = function () {
                            if (chunks.length > 0 && !wasCancelled) {
                                audioBlob = new Blob(chunks, { type: 'audio/webm' });
                                audioUrl = URL.createObjectURL(audioBlob);
                                
                                var reader = new FileReader();
                                reader.readAsDataURL(audioBlob);
                                reader.onloadend = function () {
                                    if (reader.result) {
                                        // Don't automatically send - show confirmation instead
                                        // Unless it was cancelled
                                        if (!wasCancelled) {
                                            showAudioConfirmation(reader.result);
                                        }
                                    }
                                };
                            }
                            chunks = [];
                            if (stream) {
                                stream.getTracks().forEach(track => track.stop());
                                stream = null;
                            }
                            wasCancelled = false; // Reset the flag
                        };

                        mediaRecorder.onerror = function (e) {
                            alert('Error during recording: ' + e.error);
                            reset();
                        };
                    })
                    .catch(function (err) {
                        alert('Error accessing microphone: ' + err.message);
                        reset();
                    });
            }

            /**
             * Stop recording (for confirmation)
             */
            function stopRecording() {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    wasCancelled = false; // Not cancelled, will show confirmation
                    mediaRecorder.stop();
                    mediaRecorder = null;
                }
                
                // Hide overlay
                $('#recording-overlay').removeClass('active');
                
                // Clear timer
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                // Don't reset recordingStartTime yet - we need it for duration display
                
                // Reset button states
                $('#intebchat-icon-mic').show().removeClass('recording');
                $('#intebchat-icon-stop').hide();
            }

            /**
             * Cancel recording (without confirmation)
             */
            function cancelRecording() {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    wasCancelled = true; // Mark as cancelled before stopping
                    mediaRecorder.stop();
                    mediaRecorder = null;
                }
                
                // Clear recorded data
                $('#intebchat-recorded-audio').val('');
                chunks = [];
                
                // Hide overlay
                $('#recording-overlay').removeClass('active');
                
                // Clear timer
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                recordingStartTime = null;
                
                // Stop stream
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                
                // Clean up audio URL
                if (audioUrl) {
                    URL.revokeObjectURL(audioUrl);
                    audioUrl = null;
                }
                
                // Reset button states
                $('#intebchat-icon-mic').show().removeClass('recording');
                $('#intebchat-icon-stop').hide();
            }

            /**
             * Reset recording state
             */
            function reset() {
                $('#intebchat-icon-mic').removeClass('recording').show();
                $('#intebchat-icon-stop').hide();
                $('#intebchat-recorded-audio').val('');
                $('#recording-overlay').removeClass('active');
                
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerInterval = null;
                }
                recordingStartTime = null;
                
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                
                // Clean up audio URL
                if (audioUrl) {
                    URL.revokeObjectURL(audioUrl);
                    audioUrl = null;
                }
                
                audioBlob = null;
                chunks = [];
                wasCancelled = false; // Reset the flag
            }

            // Event handlers
            $('#intebchat-icon-mic').on('click', function () {
                startRecording();
            });

            $('#intebchat-icon-stop').on('click', function () {
                stopRecording();
            });

            // Overlay controls
            $(document).on('click', '#stop-recording', function () {
                stopRecording();
            });

            $(document).on('click', '#cancel-recording', function () {
                cancelRecording();
            });

            // Handle ESC key to cancel recording
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && $('#recording-overlay').hasClass('active')) {
                    cancelRecording();
                }
            });
        }
    };
});