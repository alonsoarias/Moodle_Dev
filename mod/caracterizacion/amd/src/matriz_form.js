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
 * AMD module for the characterization matrix form.
 * Manages dynamic units, topics, and resources.
 *
 * @module      mod_caracterizacion/matriz_form
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/str', 'core/notification'], function($, Str, Notification) {

    /** @var {Object} Resource type definitions */
    var RESOURCE_TYPES = {
        educativo_digital: {
            label: 'Recursos Educativos Digitales',
            resources: {
                ebook: 'E-book',
                videoclase: 'Video Clase',
                podcast: 'Podcast',
                comicvirtual: 'Comic Virtual',
                pasoapaso: 'Paso a Paso',
                lineadetiempo: 'Línea de Tiempo',
                infografia: 'Infografía',
                mapaconceptual: 'Mapa Conceptual',
                mapamental: 'Mapa Mental',
                videointeractivo: 'Video Interactivo',
                videodiapositivas: 'Video con Diapositivas',
                videoexplicativo: 'Video Explicativo'
            }
        },
        interactivo_digital: {
            label: 'Recursos Interactivos Digitales',
            resources: {
                hotspots: 'Hotspots',
                emparejamiento: 'Emparejamiento',
                arrastrapalabras: 'Arrastra las Palabras',
                crucigrama: 'Crucigrama',
                ordenaparrafos: 'Ordena los Párrafos',
                sopadeletras: 'Sopa de Letras',
                glosariointeractivo: 'Glosario Interactivo'
            }
        },
        evaluativo: {
            label: 'Recursos Evaluativos',
            resources: {
                opcionunica: 'Opción Única',
                opcionmultiple: 'Opción Múltiple',
                verdaderofalso: 'Verdadero o Falso',
                marcapalabras: 'Marca las Palabras',
                espaciosenblanco: 'Espacios en Blanco',
                dictado: 'Dictado',
                tarjetadidactica: 'Tarjeta Didáctica',
                tarjetasdedialogo: 'Tarjetas de Diálogo'
            }
        },
        colaborativo: {
            label: 'Recursos Colaborativos',
            resources: {
                wiki: 'Wiki',
                tarea: 'Tarea',
                leccion: 'Lección',
                forotematico: 'Foro Temático',
                forosocial: 'Foro Social'
            }
        },
        externo: {
            label: 'Recursos Externos',
            resources: {
                videoconferencias: 'Video Conferencias',
                paquetes: 'Paquetes',
                plataformasexternas: 'Plataformas Externas'
            }
        }
    };

    /** @var {Array} Current units data */
    var unitsData = [];

    /** @var {Object} Module configuration */
    var config = {};

    /**
     * Initialize the module.
     *
     * @param {Object} params Configuration parameters.
     */
    var init = function(params) {
        config = params;
        var container = $('#mod-caracterizacion-unidades-container');
        if (!container.length) {
            return;
        }

        // Load existing data from hidden field.
        var existingData = $('input[name="unidades_data"]').val();
        if (existingData) {
            try {
                unitsData = JSON.parse(existingData);
            } catch (e) {
                unitsData = [];
            }
        }

        // If no data, initialize with default structure (5 units, 5 topics each).
        if (!unitsData.length) {
            for (var u = 1; u <= 5; u++) {
                var unit = {
                    numero: u,
                    nombre: '',
                    temas: []
                };
                for (var t = 1; t <= 5; t++) {
                    unit.temas.push({
                        numero: t,
                        nombre: '',
                        recursos: []
                    });
                }
                unitsData.push(unit);
            }
        }

        renderUnits(container);
        bindEvents(container);
        syncHiddenField();
    };

    /**
     * Render all units into the container.
     *
     * @param {jQuery} container The container element.
     */
    var renderUnits = function(container) {
        container.empty();

        // Add unit button.
        var addUnitBtn = '<div class="mb-3">' +
            '<button type="button" class="btn btn-primary btn-add-unit">' +
            '<i class="fa fa-plus"></i> Agregar Unidad</button></div>';
        container.append(addUnitBtn);

        unitsData.forEach(function(unit, uIndex) {
            var unitHtml = renderUnit(unit, uIndex);
            container.append(unitHtml);
        });

        // Summary section.
        container.append(renderSummary());
    };

    /**
     * Render a single unit.
     *
     * @param {Object} unit Unit data.
     * @param {number} uIndex Unit index.
     * @return {string} HTML string.
     */
    var renderUnit = function(unit, uIndex) {
        var html = '<div class="card mb-3 unit-card" data-unit-index="' + uIndex + '">';
        html += '<div class="card-header bg-success text-white d-flex justify-content-between align-items-center">';
        html += '<h5 class="mb-0">Unidad ' + unit.numero + '</h5>';
        html += '<button type="button" class="btn btn-sm btn-outline-light btn-remove-unit" data-unit-index="' +
            uIndex + '"><i class="fa fa-trash"></i> Eliminar Unidad</button>';
        html += '</div>';
        html += '<div class="card-body">';

        // Unit name.
        html += '<div class="form-group">';
        html += '<label>Nombre de la Unidad</label>';
        html += '<input type="text" class="form-control unit-name" data-unit-index="' + uIndex + '" ' +
            'value="' + escapeHtml(unit.nombre) + '" placeholder="Ingresar nombre de la unidad">';
        html += '</div>';

        // Add topic button.
        html += '<button type="button" class="btn btn-sm btn-info btn-add-topic mb-3" data-unit-index="' +
            uIndex + '"><i class="fa fa-plus"></i> Agregar Tema</button>';

        // Topics.
        unit.temas.forEach(function(tema, tIndex) {
            html += renderTopic(tema, uIndex, tIndex, unit.numero);
        });

        html += '</div></div>';
        return html;
    };

    /**
     * Render a single topic with its resources.
     *
     * @param {Object} tema Topic data.
     * @param {number} uIndex Unit index.
     * @param {number} tIndex Topic index.
     * @param {number} unitNum Unit number.
     * @return {string} HTML string.
     */
    var renderTopic = function(tema, uIndex, tIndex, unitNum) {
        var topicNum = unitNum + '.' + tema.numero;
        var html = '<div class="card mb-2 ml-3 topic-card" data-unit-index="' + uIndex +
            '" data-topic-index="' + tIndex + '">';
        html += '<div class="card-header bg-light d-flex justify-content-between align-items-center">';
        html += '<h6 class="mb-0">Tema ' + topicNum + '</h6>';
        html += '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-topic" ' +
            'data-unit-index="' + uIndex + '" data-topic-index="' + tIndex + '">' +
            '<i class="fa fa-times"></i></button>';
        html += '</div>';
        html += '<div class="card-body">';

        // Topic name.
        html += '<div class="form-group">';
        html += '<label>Nombre del Tema</label>';
        html += '<input type="text" class="form-control topic-name" data-unit-index="' + uIndex +
            '" data-topic-index="' + tIndex + '" value="' + escapeHtml(tema.nombre) +
            '" placeholder="Ingresar nombre del tema">';
        html += '</div>';

        // Resources table.
        html += '<div class="table-responsive">';
        html += '<table class="table table-sm table-bordered">';
        html += '<thead class="thead-light"><tr>';
        html += '<th>Tipo de Recurso</th><th>Recurso</th><th>Ítem/Título</th><th>Observaciones/Contenido</th><th>Acciones</th>';
        html += '</tr></thead><tbody>';

        tema.recursos.forEach(function(rec, rIndex) {
            html += renderResourceRow(rec, uIndex, tIndex, rIndex);
        });

        html += '</tbody></table></div>';

        // Add resource button.
        html += '<button type="button" class="btn btn-sm btn-outline-success btn-add-resource" ' +
            'data-unit-index="' + uIndex + '" data-topic-index="' + tIndex + '">' +
            '<i class="fa fa-plus"></i> Agregar Recurso</button>';

        html += '</div></div>';
        return html;
    };

    /**
     * Render a resource row in the table.
     *
     * @param {Object} rec Resource data.
     * @param {number} uIndex Unit index.
     * @param {number} tIndex Topic index.
     * @param {number} rIndex Resource index.
     * @return {string} HTML string.
     */
    var renderResourceRow = function(rec, uIndex, tIndex, rIndex) {
        var html = '<tr data-unit-index="' + uIndex + '" data-topic-index="' + tIndex +
            '" data-resource-index="' + rIndex + '">';

        // Resource type select.
        html += '<td><select class="form-control form-control-sm resource-type" data-unit-index="' +
            uIndex + '" data-topic-index="' + tIndex + '" data-resource-index="' + rIndex + '">';
        html += '<option value="">Seleccione tipo</option>';
        Object.keys(RESOURCE_TYPES).forEach(function(key) {
            var selected = (rec.tipo_recurso === key) ? ' selected' : '';
            html += '<option value="' + key + '"' + selected + '>' + RESOURCE_TYPES[key].label + '</option>';
        });
        html += '</select></td>';

        // Specific resource select.
        html += '<td><select class="form-control form-control-sm resource-name" data-unit-index="' +
            uIndex + '" data-topic-index="' + tIndex + '" data-resource-index="' + rIndex + '">';
        html += '<option value="">Seleccione recurso</option>';
        if (rec.tipo_recurso && RESOURCE_TYPES[rec.tipo_recurso]) {
            var resources = RESOURCE_TYPES[rec.tipo_recurso].resources;
            Object.keys(resources).forEach(function(rkey) {
                var selected = (rec.recurso === rkey) ? ' selected' : '';
                html += '<option value="' + rkey + '"' + selected + '>' + resources[rkey] + '</option>';
            });
        }
        html += '</select></td>';

        // Item input.
        html += '<td><input type="text" class="form-control form-control-sm resource-item" ' +
            'data-unit-index="' + uIndex + '" data-topic-index="' + tIndex +
            '" data-resource-index="' + rIndex + '" value="' + escapeHtml(rec.item || '') + '" ' +
            'placeholder="Título del recurso"></td>';

        // Observations/Content textarea.
        html += '<td><textarea class="form-control form-control-sm resource-observaciones" ' +
            'data-unit-index="' + uIndex + '" data-topic-index="' + tIndex +
            '" data-resource-index="' + rIndex + '" rows="2" ' +
            'placeholder="Descripción, guión o contenido del recurso">' +
            escapeHtml(rec.observaciones || '') + '</textarea></td>';

        // Delete button.
        html += '<td><button type="button" class="btn btn-sm btn-outline-danger btn-remove-resource" ' +
            'data-unit-index="' + uIndex + '" data-topic-index="' + tIndex +
            '" data-resource-index="' + rIndex + '"><i class="fa fa-trash"></i></button></td>';

        html += '</tr>';
        return html;
    };

    /**
     * Render resource summary.
     *
     * @return {string} HTML string.
     */
    var renderSummary = function() {
        var counts = {
            educativo_digital: 0,
            interactivo_digital: 0,
            evaluativo: 0,
            colaborativo: 0,
            externo: 0
        };
        var total = 0;

        unitsData.forEach(function(unit) {
            unit.temas.forEach(function(tema) {
                tema.recursos.forEach(function(rec) {
                    if (rec.tipo_recurso && counts[rec.tipo_recurso] !== undefined) {
                        counts[rec.tipo_recurso]++;
                        total++;
                    }
                });
            });
        });

        var html = '<div class="card mb-3 border-warning">';
        html += '<div class="card-header bg-warning"><h5 class="mb-0">Resumen de Recursos</h5></div>';
        html += '<div class="card-body"><table class="table table-sm">';
        html += '<tbody>';
        html += '<tr><td>Recursos Educativos Digitales</td><td class="text-right">' +
            counts.educativo_digital + '</td></tr>';
        html += '<tr><td>Recursos Interactivos Digitales</td><td class="text-right">' +
            counts.interactivo_digital + '</td></tr>';
        html += '<tr><td>Recursos Evaluativos</td><td class="text-right">' +
            counts.evaluativo + '</td></tr>';
        html += '<tr><td>Recursos Colaborativos</td><td class="text-right">' +
            counts.colaborativo + '</td></tr>';
        html += '<tr><td>Recursos Externos</td><td class="text-right">' +
            counts.externo + '</td></tr>';
        html += '<tr class="table-active font-weight-bold"><td>Total de Recursos</td>' +
            '<td class="text-right">' + total + '</td></tr>';
        html += '</tbody></table></div></div>';
        return html;
    };

    /**
     * Bind all events to the container.
     *
     * @param {jQuery} container The container element.
     */
    var bindEvents = function(container) {
        // Add unit.
        container.on('click', '.btn-add-unit', function() {
            var nextNum = unitsData.length > 0
                ? Math.max.apply(null, unitsData.map(function(u) { return u.numero; })) + 1
                : 1;
            var unit = {
                numero: nextNum,
                nombre: '',
                temas: [{numero: 1, nombre: '', recursos: []}]
            };
            unitsData.push(unit);
            renderUnits(container);
            syncHiddenField();
        });

        // Remove unit.
        container.on('click', '.btn-remove-unit', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            unitsData.splice(uIndex, 1);
            renderUnits(container);
            syncHiddenField();
        });

        // Add topic.
        container.on('click', '.btn-add-topic', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var unit = unitsData[uIndex];
            var nextNum = unit.temas.length > 0
                ? Math.max.apply(null, unit.temas.map(function(t) { return t.numero; })) + 1
                : 1;
            unit.temas.push({numero: nextNum, nombre: '', recursos: []});
            renderUnits(container);
            syncHiddenField();
        });

        // Remove topic.
        container.on('click', '.btn-remove-topic', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            unitsData[uIndex].temas.splice(tIndex, 1);
            renderUnits(container);
            syncHiddenField();
        });

        // Add resource.
        container.on('click', '.btn-add-resource', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            unitsData[uIndex].temas[tIndex].recursos.push({
                tipo_recurso: '',
                recurso: '',
                item: '',
                observaciones: ''
            });
            renderUnits(container);
            syncHiddenField();
        });

        // Remove resource.
        container.on('click', '.btn-remove-resource', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            var rIndex = parseInt($(this).data('resource-index'));
            unitsData[uIndex].temas[tIndex].recursos.splice(rIndex, 1);
            renderUnits(container);
            syncHiddenField();
        });

        // Unit name change.
        container.on('change', '.unit-name', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            unitsData[uIndex].nombre = $(this).val();
            syncHiddenField();
        });

        // Topic name change.
        container.on('change', '.topic-name', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            unitsData[uIndex].temas[tIndex].nombre = $(this).val();
            syncHiddenField();
        });

        // Resource type change - updates resource dropdown.
        container.on('change', '.resource-type', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            var rIndex = parseInt($(this).data('resource-index'));
            var typeKey = $(this).val();

            unitsData[uIndex].temas[tIndex].recursos[rIndex].tipo_recurso = typeKey;
            unitsData[uIndex].temas[tIndex].recursos[rIndex].recurso = '';

            // Update the resource dropdown.
            var resourceSelect = container.find('.resource-name[data-unit-index="' + uIndex +
                '"][data-topic-index="' + tIndex + '"][data-resource-index="' + rIndex + '"]');
            resourceSelect.empty().append('<option value="">Seleccione recurso</option>');

            if (typeKey && RESOURCE_TYPES[typeKey]) {
                var resources = RESOURCE_TYPES[typeKey].resources;
                Object.keys(resources).forEach(function(rkey) {
                    resourceSelect.append('<option value="' + rkey + '">' + resources[rkey] + '</option>');
                });
            }

            syncHiddenField();
            renderUnits(container);
        });

        // Resource name change.
        container.on('change', '.resource-name', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            var rIndex = parseInt($(this).data('resource-index'));
            unitsData[uIndex].temas[tIndex].recursos[rIndex].recurso = $(this).val();
            syncHiddenField();
            renderUnits(container);
        });

        // Resource item change.
        container.on('change', '.resource-item', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            var rIndex = parseInt($(this).data('resource-index'));
            unitsData[uIndex].temas[tIndex].recursos[rIndex].item = $(this).val();
            syncHiddenField();
        });

        // Resource observaciones change.
        container.on('change', '.resource-observaciones', function() {
            var uIndex = parseInt($(this).data('unit-index'));
            var tIndex = parseInt($(this).data('topic-index'));
            var rIndex = parseInt($(this).data('resource-index'));
            unitsData[uIndex].temas[tIndex].recursos[rIndex].observaciones = $(this).val();
            syncHiddenField();
        });
    };

    /**
     * Sync the units data to the hidden form field.
     */
    var syncHiddenField = function() {
        $('input[name="unidades_data"]').val(JSON.stringify(unitsData));
    };

    /**
     * Escape HTML special characters.
     *
     * @param {string} text The text to escape.
     * @return {string} Escaped text.
     */
    var escapeHtml = function(text) {
        if (!text) {
            return '';
        }
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(text));
        return div.innerHTML;
    };

    return {
        init: init
    };
});
