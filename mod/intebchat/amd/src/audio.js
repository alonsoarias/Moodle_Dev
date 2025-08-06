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
 * Audio recording utilities extracted from local_geniai plugin.
 *
 * @module     mod_intebchat/audio
 * @copyright  2024 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function ($) {
    return {
        init: function (mode) {
            var chunks = [];
            var mediaRecorder = null;
            var audioMode = mode || 'text';
            var stream = null;

            function reset() {
                $('#intebchat-icon-mic').removeClass('recording').show();
                $('#intebchat-icon-stop').hide();
                $('#intebchat-recorded-audio').val('');
                
                // Detener el stream si existe
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }

            $('#intebchat-icon-mic').on('click', function () {
                reset();
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Your browser does not support recording!');
                    return;
                }
                
                navigator.mediaDevices.getUserMedia({ audio: true })
                    .then(function (userStream) {
                        stream = userStream;
                        
                        // Usar el tipo MIME correcto según el navegador
                        var mimeType = 'audio/webm';
                        if (MediaRecorder.isTypeSupported('audio/webm')) {
                            mimeType = 'audio/webm';
                        } else if (MediaRecorder.isTypeSupported('audio/mp4')) {
                            mimeType = 'audio/mp4';
                        }
                        
                        mediaRecorder = new MediaRecorder(stream, { mimeType: mimeType });
                        chunks = []; // Limpiar chunks anteriores
                        
                        mediaRecorder.start();
                        $('#intebchat-icon-mic').addClass('recording').hide();
                        $('#intebchat-icon-stop').show();
                        
                        mediaRecorder.ondataavailable = function (e) { 
                            if (e.data && e.data.size > 0) {
                                chunks.push(e.data); 
                            }
                        };
                        
                        mediaRecorder.onstop = function () {
                            if (chunks.length > 0) {
                                var blob = new Blob(chunks, { type: 'audio/webm' });
                                var reader = new FileReader();
                                reader.readAsDataURL(blob);
                                reader.onloadend = function () {
                                    if (reader.result) {
                                        $('#intebchat-recorded-audio').val(reader.result);
                                        
                                        // Trigger auto send only for pure audio mode
                                        if (audioMode === 'audio') {
                                            // Dar tiempo para que el DOM se actualice
                                            setTimeout(function() {
                                                $('#intebchat-icon-stop').trigger('audio-ready');
                                            }, 100);
                                        }
                                    }
                                };
                            }
                            chunks = [];
                            
                            // Detener el stream
                            if (stream) {
                                stream.getTracks().forEach(track => track.stop());
                                stream = null;
                            }
                        };
                        
                        mediaRecorder.onerror = function(e) {
                            console.error('MediaRecorder error:', e);
                            alert('Error during recording: ' + e.error);
                            reset();
                        };
                    })
                    .catch(function (err) {
                        console.error('getUserMedia error:', err);
                        alert('Error accessing microphone: ' + err.message);
                        reset();
                    });
            });

            $('#intebchat-icon-stop').on('click', function () {
                if (mediaRecorder && mediaRecorder.state !== 'inactive') {
                    mediaRecorder.stop();
                    mediaRecorder = null;
                }
                $('#intebchat-icon-mic').show().removeClass('recording');
                $('#intebchat-icon-stop').hide();
            });
        }
    };
});