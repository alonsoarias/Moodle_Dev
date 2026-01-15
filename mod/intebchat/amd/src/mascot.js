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
 * Mascot module for INTEB Chat
 *
 * Handles mascot display, animations, and state management.
 * Mascots are loaded as inline SVGs to enable CSS animations on internal elements.
 *
 * @module     mod_intebchat/mascot
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str'], function($, Str) {
    'use strict';

    /**
     * Mascot states
     * @type {Object}
     */
    var STATES = {
        IDLE: 'idle',
        THINKING: 'thinking',
        SUCCESS: 'success',
        CONFUSED: 'confused',
        GREETING: 'greeting',
        LISTENING: 'listening',
        SUGGESTING: 'suggesting'
    };

    /**
     * Mascot configuration
     * @type {Object}
     */
    var config = {
        containerId: 'intebchat-mascot',
        containerClass: 'intebchat-mascot',
        svgClass: 'mascot-svg',
        tooltipClass: 'mascot-tooltip',
        url: '',
        name: 'Assistant',
        initialized: false
    };

    /**
     * Current state
     * @type {string}
     */
    var currentState = STATES.IDLE;

    /**
     * Tooltip timer reference
     * @type {number|null}
     */
    var tooltipTimer = null;

    /**
     * Language strings
     * @type {Object}
     */
    var strings = {};

    /**
     * Load language strings
     * @returns {Promise}
     */
    function loadStrings() {
        return Str.get_strings([
            {key: 'mascothelp', component: 'mod_intebchat'},
            {key: 'mascotneedmore', component: 'mod_intebchat'},
            {key: 'mascotconfused', component: 'mod_intebchat'},
            {key: 'mascotgreeting', component: 'mod_intebchat'},
            {key: 'mascotlistening', component: 'mod_intebchat'},
            {key: 'thinking', component: 'mod_intebchat'}
        ]).then(function(results) {
            strings.help = results[0] || '¿En qué puedo ayudarte?';
            strings.needmore = results[1] || '¿Necesitas algo más?';
            strings.confused = results[2] || 'Hubo un problema...';
            strings.greeting = results[3] || '¡Hola! Estoy aquí para ayudarte.';
            strings.listening = results[4] || 'Te escucho...';
            strings.thinking = results[5] || 'Pensando...';
            return strings;
        }).catch(function() {
            // Use defaults if strings fail to load
            strings.help = '¿En qué puedo ayudarte?';
            strings.needmore = '¿Necesitas algo más?';
            strings.confused = 'Hubo un problema...';
            strings.greeting = '¡Hola! Estoy aquí para ayudarte.';
            strings.listening = 'Te escucho...';
            strings.thinking = 'Pensando...';
            return strings;
        });
    }

    /**
     * Create mascot container HTML
     * @returns {string}
     */
    function createContainerHtml() {
        return '<div id="' + config.containerId + '" class="' + config.containerClass + '" data-state="' + STATES.IDLE + '">' +
            '<div class="' + config.svgClass + '" role="img" aria-label="' + config.name + '"></div>' +
            '<div class="' + config.tooltipClass + '">' +
            '<div class="tooltip-content"></div>' +
            '<div class="tooltip-arrow"></div>' +
            '</div>' +
            '</div>';
    }

    /**
     * Fetch and inline the SVG
     * @param {string} url - URL of the SVG file
     * @returns {Promise}
     */
    function loadSvg(url) {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: url,
                dataType: 'text',
                cache: true,
                success: function(svgContent) {
                    try {
                        // Parse SVG content
                        var parser = new DOMParser();
                        var svgDoc = parser.parseFromString(svgContent, 'image/svg+xml');
                        var svgElement = svgDoc.querySelector('svg');

                        if (svgElement) {
                            // Clean up and prepare SVG
                            svgElement.setAttribute('width', '100%');
                            svgElement.setAttribute('height', '100%');
                            svgElement.classList.add('mascot-inline-svg');

                            // Remove XML declaration if present
                            var svgString = new XMLSerializer().serializeToString(svgElement);
                            resolve(svgString);
                        } else {
                            reject(new Error('Invalid SVG content'));
                        }
                    } catch (e) {
                        reject(e);
                    }
                },
                error: function(xhr, status, error) {
                    reject(new Error('Failed to load SVG: ' + error));
                }
            });
        });
    }

    /**
     * Set mascot state
     * @param {string} state - One of STATES values
     */
    function setState(state) {
        var $container = $('#' + config.containerId);
        if (!$container.length) {
            return;
        }

        currentState = state;
        $container.attr('data-state', state);

        // Trigger custom event for external listeners
        $(document).trigger('intebchat-mascot-state', {state: state});
    }

    /**
     * Show tooltip with message
     * @param {string} message - Message to display
     * @param {number} duration - Duration in ms (0 for permanent)
     */
    function showTooltip(message, duration) {
        var $tooltip = $('#' + config.containerId + ' .' + config.tooltipClass);
        if (!$tooltip.length) {
            return;
        }

        // Clear any existing timer
        if (tooltipTimer) {
            clearTimeout(tooltipTimer);
            tooltipTimer = null;
        }

        // Set content and show
        $tooltip.find('.tooltip-content').text(message);
        $tooltip.addClass('visible');

        // Auto-hide if duration specified
        if (duration > 0) {
            tooltipTimer = setTimeout(function() {
                hideTooltip();
            }, duration);
        }
    }

    /**
     * Hide tooltip
     */
    function hideTooltip() {
        var $tooltip = $('#' + config.containerId + ' .' + config.tooltipClass);
        $tooltip.removeClass('visible');

        if (tooltipTimer) {
            clearTimeout(tooltipTimer);
            tooltipTimer = null;
        }
    }

    /**
     * Bind mascot events
     */
    function bindEvents() {
        // Click event
        $(document).on('click', '#' + config.containerId, function(e) {
            e.preventDefault();
            wave();
            showTooltip(strings.help, 3000);
        });

        // Listen for audio recording events
        $(document).on('intebchat-recording-started', function() {
            listening();
        });
    }

    // ========================================================================
    // PUBLIC STATE METHODS
    // ========================================================================

    /**
     * Set idle state
     */
    function idle() {
        setState(STATES.IDLE);
        hideTooltip();
    }

    /**
     * Set thinking state
     */
    function thinking() {
        setState(STATES.THINKING);
        showTooltip(strings.thinking, 0);
    }

    /**
     * Set success state
     */
    function success() {
        setState(STATES.SUCCESS);
        showTooltip(strings.needmore, 3000);

        // Return to idle after animation
        setTimeout(function() {
            if (currentState === STATES.SUCCESS) {
                idle();
            }
        }, 2000);
    }

    /**
     * Set confused/error state
     */
    function confused() {
        setState(STATES.CONFUSED);
        showTooltip(strings.confused, 4000);

        // Return to idle after animation
        setTimeout(function() {
            if (currentState === STATES.CONFUSED) {
                idle();
            }
        }, 3000);
    }

    /**
     * Set greeting state (wave)
     */
    function greeting() {
        setState(STATES.GREETING);
        showTooltip(strings.greeting, 4000);

        // Return to idle after animation
        setTimeout(function() {
            if (currentState === STATES.GREETING) {
                idle();
            }
        }, 2000);
    }

    /**
     * Alias for greeting
     */
    function wave() {
        greeting();
    }

    /**
     * Set listening state
     */
    function listening() {
        setState(STATES.LISTENING);
        showTooltip(strings.listening, 0);
    }

    /**
     * Set suggesting state
     */
    function suggesting() {
        setState(STATES.SUGGESTING);
    }

    // ========================================================================
    // INITIALIZATION
    // ========================================================================

    /**
     * Initialize the mascot
     * @param {Object} options - Configuration options
     * @param {string} options.url - URL of the mascot SVG
     * @param {string} options.name - Name of the mascot for accessibility
     * @param {string} options.container - Selector for parent container
     * @returns {Promise}
     */
    function init(options) {
        if (config.initialized) {
            return Promise.resolve();
        }

        // Merge options
        config.url = options.url || '';
        config.name = options.name || 'Assistant';

        var $parent = $(options.container || '.intebchat-main');
        if (!$parent.length) {
            return Promise.reject(new Error('Parent container not found'));
        }

        // Load strings first
        return loadStrings().then(function() {
            // Create container
            $parent.append(createContainerHtml());

            // Load and inline SVG
            if (config.url) {
                return loadSvg(config.url).then(function(svgHtml) {
                    $('#' + config.containerId + ' .' + config.svgClass).html(svgHtml);
                }).catch(function(error) {
                    // Fallback to img tag
                    var imgHtml = '<img src="' + config.url + '" alt="' + config.name + '" />';
                    $('#' + config.containerId + ' .' + config.svgClass).html(imgHtml);
                    // eslint-disable-next-line no-console
                    console.warn('Mascot: SVG inline failed, using img fallback:', error);
                });
            }
            return Promise.resolve();
        }).then(function() {
            // Bind events
            bindEvents();

            // Mark as initialized
            config.initialized = true;

            // Play greeting animation
            greeting();

            return Promise.resolve();
        });
    }

    /**
     * Check if mascot is initialized
     * @returns {boolean}
     */
    function isInitialized() {
        return config.initialized;
    }

    /**
     * Get current state
     * @returns {string}
     */
    function getState() {
        return currentState;
    }

    /**
     * Destroy mascot instance
     */
    function destroy() {
        $('#' + config.containerId).remove();
        $(document).off('click', '#' + config.containerId);
        $(document).off('intebchat-recording-started');
        config.initialized = false;
        currentState = STATES.IDLE;
    }

    // Export public API
    return {
        // Constants
        STATES: STATES,

        // Initialization
        init: init,
        destroy: destroy,
        isInitialized: isInitialized,

        // State methods
        idle: idle,
        thinking: thinking,
        success: success,
        confused: confused,
        greeting: greeting,
        wave: wave,
        listening: listening,
        suggesting: suggesting,

        // Utility methods
        setState: setState,
        getState: getState,
        showTooltip: showTooltip,
        hideTooltip: hideTooltip
    };
});
