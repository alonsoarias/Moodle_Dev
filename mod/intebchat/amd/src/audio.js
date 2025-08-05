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

            function reset() {
                $('#intebchat-icon-mic').removeClass('recording').show();
                $('#intebchat-icon-stop').hide();
                $('#intebchat-recorded-audio').val('');
            }

            $('#intebchat-icon-mic').on('click', function () {
                reset();
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    alert('Your browser does not support recording!');
                    return;
                }
                navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.start();
                    $('#intebchat-icon-mic').addClass('recording').hide();
                    $('#intebchat-icon-stop').show();
                    mediaRecorder.ondataavailable = function (e) { chunks.push(e.data); };
                    mediaRecorder.onstop = function () {
                        var reader = new FileReader();
                        reader.readAsDataURL(new Blob(chunks, { type: 'audio/mp3' }));
                        reader.onloadend = function () {
                            $('#intebchat-recorded-audio').val(reader.result);
                            // Trigger send automatically in audio or both modes
                            if (audioMode === 'audio' || audioMode === 'both') {
                                $('#intebchat-icon-stop').trigger('audio-ready');
                            }
                        };
                        chunks = [];
                    };
                }).catch(function (err) {
                    alert(err);
                    reset();
                });
            });

            $('#intebchat-icon-stop').on('click', function () {
                if (mediaRecorder) {
                    mediaRecorder.stop();
                    mediaRecorder = null;
                }
                $('#intebchat-icon-mic').show().removeClass('recording');
                $('#intebchat-icon-stop').hide();
            });
        }
    };
});