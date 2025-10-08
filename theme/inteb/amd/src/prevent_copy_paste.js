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
 * @module     theme_inteb/prevent_copy_paste
 * @copyright  2025 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery'], function($) {
    'use strict';

    var SELECTORS = {
        ALLOWED_ELEMENTS: 'input, textarea, [contenteditable="true"], .editor_atto_content, .atto_text, [role="textbox"]'
    };

    var PreventCopyPaste = function() {
        this.registerEventListeners();
        this.disableSelection();
    };

    PreventCopyPaste.prototype.registerEventListeners = function() {
        var self = this;

        // Función mejorada para verificar elementos permitidos
        function isElementAllowed(element) {
            if (!element) return false;
            
            var $element = $(element);
            
            // Verificar si el elemento en sí está permitido
            if ($element.is(SELECTORS.ALLOWED_ELEMENTS)) {
                return true;
            }
            
            // Verificar si está dentro de un contenedor permitido
            if ($element.closest(SELECTORS.ALLOWED_ELEMENTS).length > 0) {
                return true;
            }
            
            // Verificar atributos específicos de Moodle
            if ($element.closest('[contenteditable="true"]').length > 0) {
                return true;
            }
            
            // Verificar clases específicas de editores de Moodle
            if ($element.closest('.editor_atto_content, .atto_text').length > 0) {
                return true;
            }
            
            return false;
        }

        // Prevent right click context menu
        $(document).on('contextmenu', function(e) {
            if (!isElementAllowed(e.target)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent keyboard shortcuts
        $(document).on('keydown', function(e) {
            if (!isElementAllowed(e.target)) {
                if (e.ctrlKey || e.metaKey) {
                    // Usar e.key si está disponible, sino usar e.keyCode
                    var key = e.key ? e.key.toLowerCase() : '';
                    var keyCode = e.keyCode || e.which;
                    
                    // Lista extendida de atajos a prevenir
                    var blockedKeys = ['c', 'v', 'x', 'a', 's', 'u', 'p', 'j', 'd', 'f', 'g', 'h', 'i', 'k', 'w'];
                    var blockedKeyCodes = [67, 86, 88, 65, 83, 85, 80, 74, 68, 70, 71, 72, 73, 75, 87];
                    
                    if (blockedKeys.indexOf(key) !== -1 || blockedKeyCodes.indexOf(keyCode) !== -1) {
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }

                // Prevent F12 (Dev Tools) - usar tanto e.key como e.keyCode para compatibilidad
                if (e.key === 'F12' || e.keyCode === 123 || e.which === 123) {
                    e.preventDefault();
                    return false;
                }

                // Prevent Ctrl+Shift+I/J/C (Dev Tools)
                if (e.ctrlKey && e.shiftKey) {
                    var keyCode = e.keyCode || e.which;
                    if (keyCode === 73 || keyCode === 74 || keyCode === 67) { // I, J, C
                        e.preventDefault();
                        e.stopPropagation();
                        return false;
                    }
                }

                // Prevent Alt+PrintScreen
                if (e.altKey && (e.key === 'PrintScreen' || e.keyCode === 44)) {
                    e.preventDefault();
                    return false;
                }

                // Prevent PrintScreen
                if (e.key === 'PrintScreen' || e.keyCode === 44) {
                    e.preventDefault();
                    return false;
                }
            }
        });

        // Prevent copy/paste/cut
        $(document).on('copy paste cut', function(e) {
            if (!isElementAllowed(e.target)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent drag and drop
        $(document).on('dragstart drop', function(e) {
            if (!isElementAllowed(e.target)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent text selection
        $(document).on('selectstart', function(e) {
            if (!isElementAllowed(e.target)) {
                e.preventDefault();
                return false;
            }
        });

        // Prevent print dialog
        if (window.addEventListener) {
            window.addEventListener('beforeprint', function(e) {
                e.preventDefault();
                return false;
            });
        } else if (window.attachEvent) {
            // IE compatibility
            window.attachEvent('onbeforeprint', function(e) {
                e.returnValue = false;
                return false;
            });
        }
    };

    PreventCopyPaste.prototype.disableSelection = function() {
        // Aplicar CSS directamente al body
        $('body').css({
            '-webkit-user-select': 'none',
            '-moz-user-select': 'none',
            '-ms-user-select': 'none',
            'user-select': 'none',
            '-webkit-touch-callout': 'none',
            '-webkit-tap-highlight-color': 'transparent'
        });

        // También agregar los estilos al head para mayor robustez
        if ($('#prevent-copy-paste-styles').length === 0) {
            var css = '<style id="prevent-copy-paste-styles">' +
                'body { ' +
                '-webkit-user-select: none !important; ' +
                '-moz-user-select: none !important; ' +
                '-ms-user-select: none !important; ' +
                'user-select: none !important; ' +
                '-webkit-touch-callout: none !important; ' +
                '-webkit-tap-highlight-color: transparent !important; ' +
                '}' +
                'input, textarea, [contenteditable="true"], .editor_atto_content, .atto_text, [role="textbox"] { ' +
                '-webkit-user-select: text !important; ' +
                '-moz-user-select: text !important; ' +
                '-ms-user-select: text !important; ' +
                'user-select: text !important; ' +
                '}' +
                '/* Permitir selección dentro de editores de Moodle */' +
                '.editor_atto_content *, .atto_text *, [contenteditable="true"] * { ' +
                '-webkit-user-select: text !important; ' +
                '-moz-user-select: text !important; ' +
                '-ms-user-select: text !important; ' +
                'user-select: text !important; ' +
                '}' +
                '::selection { background: transparent; }' +
                '::-moz-selection { background: transparent; }' +
                '/* Pero permitir selección en editores */' +
                'input::selection, textarea::selection, [contenteditable="true"]::selection, ' +
                '.editor_atto_content ::selection, .atto_text ::selection { ' +
                'background: #b3d4fc !important; ' +
                '}' +
                'input::-moz-selection, textarea::-moz-selection, [contenteditable="true"]::-moz-selection, ' +
                '.editor_atto_content ::-moz-selection, .atto_text ::-moz-selection { ' +
                'background: #b3d4fc !important; ' +
                '}' +
                '</style>';
            $('head').append(css);
        }
    };

    return {
        init: function() {
            // Esperar a que el DOM esté listo antes de inicializar
            $(document).ready(function() {
                return new PreventCopyPaste();
            });
        }
    };
});