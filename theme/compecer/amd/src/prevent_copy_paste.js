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
 * Copy/paste prevention JavaScript module.
 *
 * @module     theme_compecer/prevent_copy_paste
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    'use strict';

    var SELECTORS = {
        ALLOWED_ELEMENTS: 'input, textarea'
    };

    var PreventCopyPaste = function() {
        this.registerEventListeners();
        this.disableSelection();
    };

    PreventCopyPaste.prototype.registerEventListeners = function() {
        // Prevent right click context menu
        $(document).on('contextmenu', function(e) {
            if (!$(e.target).is(SELECTORS.ALLOWED_ELEMENTS)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (!$(e.target).is(SELECTORS.ALLOWED_ELEMENTS)) {
                if (e.ctrlKey || e.metaKey) {
                    var key = e.key.toLowerCase();
                    // Lista extendida de atajos a prevenir
                    switch(key) {
                        case 'c': // Copy
                        case 'v': // Paste
                        case 'x': // Cut
                        case 'a': // Select all
                        case 's': // Save page
                        case 'u': // View source
                        case 'p': // Print
                        case 'j': // View downloads
                        case 'd': // Bookmark page
                        case 'f': // Find
                        case 'g': // Find next
                        case 'h': // View history
                        case 'i': // Dev tools
                        case 'k': // Insert link
                        case 'w': // Close window
                            e.preventDefault();
                            e.stopPropagation();
                            return false;
                    }
                }

                // Prevent F12 (Dev Tools)
                if (e.key === 'F12') {
                    e.preventDefault();
                    return false;
                }

                // Prevent Alt+PrintScreen
                if (e.altKey && e.key === 'PrintScreen') {
                    e.preventDefault();
                    return false;
                }

                // Prevent PrintScreen
                if (e.key === 'PrintScreen') {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Prevent copy/paste/cut
        $(document).on('copy paste cut', function(e) {
            if (!$(e.target).is(SELECTORS.ALLOWED_ELEMENTS)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent drag and drop
        $(document).on('dragstart drop', function(e) {
            if (!$(e.target).is(SELECTORS.ALLOWED_ELEMENTS)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent text selection
        $(document).on('selectstart', function(e) {
            if (!$(e.target).is(SELECTORS.ALLOWED_ELEMENTS)) {
                e.preventDefault();
                return false;
            }
        });
    };

    PreventCopyPaste.prototype.disableSelection = function() {
        $('body').css({
            '-webkit-user-select': 'none',
            '-moz-user-select': 'none',
            '-ms-user-select': 'none',
            'user-select': 'none'
        });
    };

    return {
        init: function() {
            return new PreventCopyPaste();
        }
    };
});