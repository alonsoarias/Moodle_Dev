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

define([], function() {
    const SELECTORS = {
        widget: '.local-educambot',
        toggle: '.local-educambot__toggle',
        panel: '.local-educambot__panel',
        close: '.local-educambot__close',
        messages: '.local-educambot__messages',
        input: '#educambot-input',
        send: '.local-educambot__send',
        suggestions: '.local-educambot__suggestions',
        status: '.local-educambot__status',
        config: '.local-educambot__config'
    };

    /**
     * Creates a message node.
     *
     * @param {HTMLElement} container
     * @param {String} text
     * @param {String} type
     */
    const addMessage = (container, text, type, allowHtml = false) => {
        const message = document.createElement('div');
        message.classList.add('local-educambot__message', `local-educambot__message--${type}`);
        if (allowHtml) {
            message.innerHTML = text;
        } else {
            message.textContent = text;
        }
        container.appendChild(message);
        container.scrollTop = container.scrollHeight;
    };

    /**
     * Updates suggestion buttons.
     *
     * @param {HTMLElement} container
     * @param {Array} suggestions
     */
    const refreshSuggestions = (container, suggestions) => {
        const list = container.querySelector('ul');
        if (!list) {
            return;
        }
        list.innerHTML = '';
        if (!suggestions || !suggestions.length) {
            container.setAttribute('hidden', 'hidden');
            return;
        }
        container.removeAttribute('hidden');
        suggestions.forEach(suggestion => {
            const li = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.question = suggestion.text;
            button.textContent = suggestion.text;
            li.appendChild(button);
            list.appendChild(li);
        });
    };

    /**
     * Sends a message to the backend service.
     *
     * @param {HTMLElement} widget
     * @param {HTMLElement} input
     * @param {HTMLElement} messages
     * @param {HTMLElement} status
     * @param {HTMLElement} suggestionContainer
     * @param {String} question
     */
    const sendMessage = async (widget, input, messages, status, suggestionContainer, question) => {
        const serviceUrl = widget.dataset.serviceUrl;
        const sessionKey = widget.dataset.sessionkey;
        const page = widget.dataset.page || '';
        const params = new URLSearchParams();
        params.append('sesskey', sessionKey);
        params.append('question', question);
        params.append('sessionid', widget.dataset.session || '');
        params.append('page', page);

        status.textContent = '';
        status.classList.add('is-loading');

        try {
            const response = await fetch(serviceUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString(),
                credentials: 'same-origin'
            });
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const payload = await response.json();
            widget.dataset.session = payload.sessionid;
            addMessage(messages, payload.response, 'bot', true);
            refreshSuggestions(suggestionContainer, payload.suggestions || []);
            const confidenceLabel = status.dataset.confidenceLabel || '';
            if (confidenceLabel) {
                status.textContent = `${confidenceLabel}: ${(payload.confidence * 100).toFixed(0)}%`;
            } else {
                status.textContent = `${(payload.confidence * 100).toFixed(0)}%`;
            }
        } catch (error) {
            status.textContent = error.message;
        } finally {
            status.classList.remove('is-loading');
            input.focus();
        }
    };

    /**
     * Initialises a single widget instance.
     *
     * @param {HTMLElement} widget
     */
    const initWidget = widget => {
        const toggle = widget.querySelector(SELECTORS.toggle);
        const panel = widget.querySelector(SELECTORS.panel);
        const close = widget.querySelector(SELECTORS.close);
        const messages = widget.querySelector(SELECTORS.messages);
        const input = widget.querySelector(SELECTORS.input);
        const send = widget.querySelector(SELECTORS.send);
        const suggestionContainer = widget.querySelector(SELECTORS.suggestions);
        const status = widget.querySelector(SELECTORS.status);
        const configScript = widget.querySelector(SELECTORS.config);

        let widgetConfig = {};
        if (configScript) {
            try {
                widgetConfig = JSON.parse(configScript.textContent || '{}');
            } catch (error) {
                // Ignore malformed configuration to avoid breaking the widget.
            }
            configScript.remove();
        }

        if (!toggle || !panel || !messages || !input || !send) {
            return;
        }

        const togglePanel = show => {
            const shouldShow = typeof show === 'boolean' ? show : panel.hasAttribute('hidden');
            if (shouldShow) {
                panel.removeAttribute('hidden');
                toggle.setAttribute('aria-expanded', 'true');
                input.focus();
            } else {
                panel.setAttribute('hidden', 'hidden');
                toggle.setAttribute('aria-expanded', 'false');
            }
        };

        toggle.addEventListener('click', () => togglePanel());
        if (close) {
            close.addEventListener('click', () => togglePanel(false));
        }

        if (widgetConfig.initialMessage) {
            addMessage(messages, widgetConfig.initialMessage, 'bot');
        }

        const handleSend = () => {
            const value = input.value.trim();
            if (!value) {
                return;
            }
            addMessage(messages, value, 'user');
            input.value = '';
            sendMessage(widget, input, messages, status, suggestionContainer, value);
        };

        send.addEventListener('click', handleSend);
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                handleSend();
            }
        });

        if (suggestionContainer) {
            suggestionContainer.addEventListener('click', event => {
                const target = event.target;
                if (target instanceof HTMLButtonElement && target.dataset.question) {
                    input.value = target.dataset.question;
                    handleSend();
                }
            });
        }
    };

    return {
        init() {
            const boot = () => {
                document.querySelectorAll(SELECTORS.widget).forEach(initWidget);
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot, {once: true});
            } else {
                boot();
            }
        }
    };
});
