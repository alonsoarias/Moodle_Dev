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
 * AGGRESSIVELY force show teachers in course header
 *
 * This module uses multiple strategies to prevent ANY code from hiding
 * the instructor-info section in the course header.
 *
 * @module     theme_inteb/force_show_teachers
 * @copyright  2025 Claude Code
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var intervalId = null;
    var observerInstance = null;

    /**
     * Force instructor-info to be visible with extreme prejudice
     */
    var forceVisible = function() {
        var $instructorInfo = $('.instructor-info.stat-container');
        var $debugBox = $('[style*="background: #333"]');

        if ($instructorInfo.length > 0 || $debugBox.length > 0) {
            // Force ALL elements with instructor-info or debug styles to be visible
            var $allTargets = $('.instructor-info, [data-debug-instructors], [style*="background: #333"], [style*="background: #0f0"]');

            $allTargets.each(function() {
                var $el = $(this);

                // Force inline styles
                $el.attr('style', function(i, style) {
                    if (!style) return 'display: flex !important; visibility: visible !important; opacity: 1 !important;';
                    // Remove any display:none or visibility:hidden
                    style = style.replace(/display\s*:\s*none/gi, 'display: flex');
                    style = style.replace(/visibility\s*:\s*hidden/gi, 'visibility: visible');
                    style = style.replace(/opacity\s*:\s*0/gi, 'opacity: 1');
                    // Ensure our properties are there
                    if (!/display\s*:/i.test(style)) {
                        style += '; display: flex !important';
                    }
                    if (!/visibility\s*:/i.test(style)) {
                        style += '; visibility: visible !important';
                    }
                    if (!/opacity\s*:/i.test(style)) {
                        style += '; opacity: 1 !important';
                    }
                    return style;
                });

                // Also set via jQuery css() which adds inline styles
                $el.css({
                    'display': 'flex',
                    'visibility': 'visible',
                    'opacity': '1'
                });

                // Remove any classes that might hide it
                $el.removeClass('hidden d-none hide invisible');

                // Mark as protected
                $el.attr('data-inteb-protected', 'true');
            });

            console.log('[INTEB] Forced visibility on ' + $allTargets.length + ' elements');
            return true;
        }

        return false;
    };

    /**
     * Setup MutationObserver to watch for DOM changes
     */
    var setupObserver = function() {
        if (!window.MutationObserver) {
            console.log('[INTEB] MutationObserver not supported');
            return;
        }

        // Disconnect existing observer
        if (observerInstance) {
            observerInstance.disconnect();
        }

        observerInstance = new MutationObserver(function(mutations) {
            var needsForce = false;

            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' &&
                    (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                    var $target = $(mutation.target);

                    // Check if it's our protected element or a parent
                    if ($target.attr('data-inteb-protected') ||
                        $target.hasClass('instructor-info') ||
                        $target.closest('.instructor-info').length > 0 ||
                        $target.find('.instructor-info').length > 0) {
                        needsForce = true;
                    }
                }

                // Check for removed nodes
                if (mutation.type === 'childList' && mutation.removedNodes.length > 0) {
                    mutation.removedNodes.forEach(function(node) {
                        if (node.nodeType === 1) { // Element node
                            var $node = $(node);
                            if ($node.hasClass('instructor-info') ||
                                $node.find('.instructor-info').length > 0) {
                                console.log('[INTEB] WARNING: instructor-info was REMOVED from DOM!');
                                needsForce = true;
                            }
                        }
                    });
                }
            });

            if (needsForce) {
                console.log('[INTEB] DOM change detected, re-forcing visibility');
                forceVisible();
            }
        });

        // Observe the entire page-header and page-content
        var targets = $('#page-header, #page-content, #region-main');
        targets.each(function() {
            observerInstance.observe(this, {
                attributes: true,
                attributeFilter: ['style', 'class'],
                childList: true,
                subtree: true
            });
        });

        console.log('[INTEB] MutationObserver setup complete, watching ' + targets.length + ' elements');
    };

    /**
     * Start aggressive monitoring
     */
    var startMonitoring = function() {
        // Clear any existing interval
        if (intervalId) {
            clearInterval(intervalId);
        }

        // Force visible immediately
        forceVisible();

        // Setup observer
        setupObserver();

        // Run every 50ms for the first 5 seconds (very aggressive)
        var count = 0;
        intervalId = setInterval(function() {
            forceVisible();
            count++;

            // After 5 seconds (100 iterations), switch to slower interval
            if (count >= 100) {
                clearInterval(intervalId);

                // Continue with slower interval (every 500ms)
                intervalId = setInterval(forceVisible, 500);
                console.log('[INTEB] Switched to maintenance mode (500ms interval)');
            }
        }, 50);

        console.log('[INTEB] Aggressive monitoring started (50ms interval for 5 seconds)');
    };

    return {
        init: function() {
            // Start IMMEDIATELY - don't wait for document.ready
            startMonitoring();

            // Also run on document ready just in case
            $(document).ready(function() {
                console.log('[INTEB] Document ready, re-initializing');
                startMonitoring();
            });

            // And on window load for good measure
            $(window).on('load', function() {
                console.log('[INTEB] Window loaded, re-initializing');
                startMonitoring();
            });
        }
    };
});
