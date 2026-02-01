<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Páginas de administración del plugin se definen aquí.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();
/**
 * Configura el script de instalación.
 * Esta función se ejecuta durante la instalación del complemento y muestra notificaciones al usuario según las capacidades del servidor.
 * @return void
 */
function xmldb_report_usage_monitor_install()
{
    global $OUTPUT;

    // Comprobamos si la función shell_exec está disponible en el servidor.
    // Si está disponible, mostramos recomendaciones y notas relacionadas con la ruta 'du'.
    if (function_exists('shell_exec')) {
        echo $OUTPUT->notification(
            get_string('pathtodurecommendation', 'report_usage_monitor'), // Mostramos un mensaje de recomendación. Este texto será traducido según el idioma del usuario.
            'info' // Se muestra una notificación informativa.
        );
        echo $OUTPUT->notification(
            get_string('pathtodunote', 'report_usage_monitor'), // Mostramos una nota adicional. Este texto será traducido según el idioma del usuario.
            'info' // Se muestra una notificación informativa.
        );
    } else {
        // Si la función shell_exec no está disponible, mostramos una advertencia al usuario.
        echo $OUTPUT->notification(
            get_string('activateshellexec', 'report_usage_monitor'), // Mostramos un mensaje de advertencia. Este texto será traducido según el idioma del usuario.
            'warning' // Se muestra una notificación de advertencia.
        );
    }
    return true;
}
