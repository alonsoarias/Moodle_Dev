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
 * Frontend controller for the Educam Bot widget.
 *
 * @module     local_educambot/widget
 */

define(['jquery'], function($) {
    var chat = {

        init: function() {

            if ($("body.pagelayout-embedded").length) {
                return;
            }

            var educambotchat = $("#educambot-chat");

            if ($(".pagelayout-embedded, .pagelayout-maintenance").length) {
                educambotchat.hide();
                educambotchat.remove();
                return;
            }

            var educambotscrollarea = document.getElementById("educambot-scrollarea");
            var educambotareamessages = $("#educambot-area-messages");
            var educambotsendarea = $("#educambot-sendarea");
            var educambottextarea = $("#educambot-textarea");

            educambotchat.show(200);

            // Auto-resize textarea
            educambottextarea
                .keydown(function(e) {
                    setTimeout(function() {
                        var messagesend = educambottextarea.val();
                        if (messagesend.length > 1) {
                            educambotsendarea.addClass("educambot-active");
                        } else {
                            educambotsendarea.removeClass("educambot-active");
                        }
                    }, 10);

                    var code = (e.keyCode ? e.keyCode : e.which);
                    if (code == 13) {
                        e.preventDefault();
                        sendMessage();
                    }
                })
                .on("input", function(event) {
                    event.currentTarget.style.height = "34px";
                    event.currentTarget.style.height = (event.currentTarget.scrollHeight) + "px";
                });

            // Prevent HTML pasting
            document.getElementById("educambot-textarea").addEventListener("paste", function(e) {
                e.preventDefault();
                var text = e.clipboardData.getData("text/plain");
                document.execCommand("insertHTML", false, text);
            });

            $("#educambot-icon-send").click(sendMessage);

            educambotsendarea.click(function() {
                document.getElementById("educambot-textarea").focus();
            });

            // Toggle chat panel
            $("#educambot-chat-btn").click(function() {
                educambotchat.toggleClass("educambot-active");
                if (educambotchat.hasClass("educambot-active")) {
                    educambottextarea.focus();
                    // Show initial message if exists
                    var initialMessageEl = $("#educambot_initial_message");
                    if (initialMessageEl.length && educambotareamessages.children().length === 0) {
                        try {
                            var configText = initialMessageEl.text().trim();
                            if (configText) {
                                var config = JSON.parse(configText);
                                if (config.initialMessage) {
                                    educambotareamessages.append(`
                                        <div class="educambot-message educambot-server format-text">${config.initialMessage}</div>
                                    `);
                                    educambotscrollarea.scrollTop = 10000000000000;
                                }
                            }
                        } catch (error) {
                            // eslint-disable-next-line no-console
                            console.warn("Error parsing initial message config:", error);
                        }
                    }
                }
            });

            // Close button
            $("#educambot-icon-close").click(function(e) {
                e.preventDefault();
                educambotchat.removeClass("educambot-active");
            });

            /**
             * Sends a message to the bot service.
             */
            function sendMessage() {
                var messagesend = educambottextarea.val().trim();
                if (messagesend.length > 1) {
                    setTimeout(function() {
                        educambottextarea.val("");
                        educambottextarea.css({height: 34});
                        educambotsendarea.removeClass("educambot-active");
                    }, 20);

                    var educambotServerId = "id-" + Math.random().toString(16).slice(2);
                    educambotareamessages.append(`
                        <div class="educambot-message" id="${educambotServerId}-send"></div>
                        <div id="${educambotServerId}" class="educambot-message educambot-server">
                            <svg height="40" class="educambot-loader">
                                <circle class="dot" cx="10" cy="20" r="3" style="fill:#777;" />
                                <circle class="dot" cx="20" cy="20" r="3" style="fill:#777;" />
                                <circle class="dot" cx="30" cy="20" r="3" style="fill:#777;" />
                            </svg>
                        </div>`);

                    $(`#${educambotServerId}-send`).html(messagesend);
                    educambotscrollarea.scrollTop = 10000000000000;

                    var serviceUrl = educambotchat.data('service-url');
                    var sessionKey = educambotchat.data('sessionkey');
                    var page = educambotchat.data('page') || "";
                    var noanswerText = educambotchat.data('noanswer') || "No encontr\u00e9 una respuesta.";
                    var confidenceLabel = educambotchat.data('confidence-label') || "";
                    var loadingText = educambotchat.data('loading') || "Procesando...";

                    // Show loading status
                    $("#educambot-status").text(loadingText).addClass("is-loading");

                    $.ajax({
                        url: serviceUrl,
                        type: 'POST',
                        data: {
                            sesskey: sessionKey,
                            question: messagesend,
                            sessionid: educambotchat.data('session') || '',
                            page: page
                        },
                        dataType: 'json',
                        success: function(payload) {
                            if (payload.sessionid) {
                                educambotchat.data('session', payload.sessionid);
                            }

                            var botReply = (typeof payload.response === "string" && payload.response.trim() !== "")
                                ? payload.response
                                : noanswerText;

                            $(`#${educambotServerId}`).html(botReply).addClass("format-text");

                            var rawConfidence = (typeof payload.confidence === "number" && !isNaN(payload.confidence))
                                ? payload.confidence
                                : 0;
                            var confidence = Math.min(1, Math.max(0, rawConfidence));

                            if (confidenceLabel && confidence > 0) {
                                $("#educambot-status").text(`${confidenceLabel}: ${(confidence * 100).toFixed(0)}%`);
                            } else {
                                $("#educambot-status").text("");
                            }

                            educambotscrollarea.scrollTop = 10000000000000;
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            // eslint-disable-next-line no-console
                            console.error("Error en el chatbot:", textStatus, errorThrown);
                            $(`#${educambotServerId}`)
                                .html("Lo siento, ocurri\u00f3 un error al procesar tu pregunta. Por favor, intenta nuevamente.")
                                .addClass("educambot-error");
                            $("#educambot-status").text("Error: " + textStatus);
                        },
                        complete: function() {
                            $("#educambot-status").removeClass("is-loading");
                            educambottextarea.focus();
                        }
                    });
                }
            }
        }
    };

    return chat;
});
