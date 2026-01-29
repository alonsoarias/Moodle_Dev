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
 * AMD module for phase management (approve/reject actions).
 *
 * @module      mod_caracterizacion/fase_manager
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax', 'core/notification', 'core/str'], function($, Ajax, Notification, Str) {

    /** @var {Object} Module configuration */
    var config = {};

    /**
     * Initialize the phase manager.
     *
     * @param {Object} params Configuration parameters.
     */
    var init = function(params) {
        config = params;
        bindEvents();
    };

    /**
     * Bind click events for phase action buttons.
     */
    var bindEvents = function() {
        $(document).on('click', '.btn-fase-action', function(e) {
            e.preventDefault();

            var btn = $(this);
            var action = btn.data('action');
            var faseid = btn.data('faseid');
            var matrizid = btn.data('matrizid');
            var cmid = btn.data('cmid');

            // Get the comment textarea for the corresponding phase.
            var comentarioField = btn.closest('.fase-actions').find('.fase-comentario');
            var comentario = comentarioField.val();

            if (!comentario || !comentario.trim()) {
                Notification.alert(
                    'Error',
                    'Debe ingresar un comentario para esta acción.',
                    'OK'
                );
                comentarioField.focus();
                return;
            }

            // Build confirm message based on action type.
            var confirmMsg = '';
            switch (action) {
                case 'aprobar':
                    confirmMsg = '¿Está seguro de que desea aprobar esta fase?';
                    break;
                case 'rechazar':
                    confirmMsg = '¿Está seguro de que desea rechazar esta fase?';
                    break;
                case 'comentar':
                    confirmMsg = '¿Está seguro de que desea enviar este comentario?';
                    break;
                case 'reenviar':
                    confirmMsg = '¿Está seguro de que desea reenviar esta fase a revisión?';
                    break;
                default:
                    confirmMsg = '¿Está seguro de que desea realizar esta acción?';
            }

            Notification.confirm(
                'Confirmar acción',
                confirmMsg,
                'Confirmar',
                'Cancelar',
                function() {
                    executeAction(faseid, matrizid, cmid, action, comentario);
                }
            );
        });
    };

    /**
     * Execute the phase action via AJAX.
     *
     * @param {number} faseid Phase record ID.
     * @param {number} matrizid Matrix ID.
     * @param {number} cmid Course module ID.
     * @param {string} action Action type (aprobar/rechazar/comentar/reenviar).
     * @param {string} comentario Comment text.
     */
    var executeAction = function(faseid, matrizid, cmid, action, comentario) {
        var request = Ajax.call([{
            methodname: 'mod_caracterizacion_fase_action',
            args: {
                faseid: faseid,
                matrizid: matrizid,
                cmid: cmid,
                accion: action,
                comentario: comentario
            }
        }]);

        request[0].done(function(response) {
            if (response.success) {
                Notification.alert(
                    'Acción realizada',
                    response.message,
                    'OK'
                ).then(function() {
                    // Reload the page to reflect changes.
                    window.location.reload();
                    return;
                }).catch(Notification.exception);
            } else {
                Notification.alert('Error', response.message, 'OK');
            }
        }).fail(function(error) {
            Notification.exception(error);
        });
    };

    return {
        init: init
    };
});
