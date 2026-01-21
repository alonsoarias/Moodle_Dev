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
 * AMD module for UDES recursos management.
 *
 * @module     mod_udes/recursos
 * @copyright  2026 Universidad de Santander - UDES (udes.edu.co)
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {

    /**
     * Resource types catalog from Excel analysis.
     */
    var resourceTypes = {
        'RECURSOS_EDUCATIVOS_DIGITALES': [
            'E-book',
            'Video Clase',
            'Podcast',
            'Comic Virtual',
            'Paso a Paso',
            'Línea de Tiempo',
            'Infografía',
            'Mapa Conceptual',
            'Mapa Mental',
            'Video Interactivo',
            'Video con diapositivas',
            'Video explicativo'
        ],
        'RECURSOS_INTERACTIVOS_DIGITALES': [
            'Opción única',
            'Opción múltiple',
            'Verdadero o falso',
            'Marca las palabras',
            'Espacios en blanco',
            'Dictado',
            'Tarjeta didáctica',
            'Tarjetas de diálogo',
            'Hotspots',
            'Emparejamiento',
            'Arrastra las palabras',
            'Crucigrama',
            'Ordena los párrafos',
            'Sopa de letras',
            'Glosario interactivo'
        ],
        'RECURSOS_EVALUATIVOS': [
            'Tarea',
            'Lección'
        ],
        'RECURSOS_COLABORATIVOS': [
            'Wiki',
            'Foro temático',
            'Foro social'
        ],
        'RECURSOS_EXTERNOS': [
            'Paquetes',
            'Plataformas externas',
            'Video conferencias'
        ]
    };

    return {
        /**
         * Initialize the recursos module.
         *
         * @method init
         */
        init: function() {
            // Set up event listeners.
            this.setupEventListeners();
        },

        /**
         * Set up event listeners for the form.
         *
         * @method setupEventListeners
         */
        setupEventListeners: function() {
            var tipoRecursoSelect = document.getElementById('id_tipo_recurso');
            var recursoSelect = document.getElementById('id_recurso');

            if (tipoRecursoSelect && recursoSelect) {
                tipoRecursoSelect.addEventListener('change', function() {
                    this.updateRecursoOptions(tipoRecursoSelect.value, recursoSelect);
                }.bind(this));
            }
        },

        /**
         * Update recurso dropdown options based on selected tipo.
         *
         * @method updateRecursoOptions
         * @param {String} tipoRecurso The selected resource type category
         * @param {HTMLElement} recursoSelect The recurso select element
         */
        updateRecursoOptions: function(tipoRecurso, recursoSelect) {
            // Clear current options.
            recursoSelect.innerHTML = '<option value="">Seleccione...</option>';

            if (tipoRecurso && resourceTypes[tipoRecurso]) {
                resourceTypes[tipoRecurso].forEach(function(recurso) {
                    var option = document.createElement('option');
                    option.value = recurso;
                    option.textContent = recurso;
                    recursoSelect.appendChild(option);
                });
            }
        },

        /**
         * Get resource types catalog.
         *
         * @method getResourceTypes
         * @return {Object} The resource types catalog
         */
        getResourceTypes: function() {
            return resourceTypes;
        }
    };
});
