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
 * Definición de las tareas programadas para el informe diario de usuarios.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

// Lista de tareas programadas para el complemento report_usage_monitor.
// Estas tareas se ejecutarán automáticamente en los intervalos de tiempo especificados.
$du_command_available = !empty($CFG->pathtodu) && is_executable(trim($CFG->pathtodu));
$tasks = array(
    // Tarea para calcular el uso del disco.
    array(
        'classname' => 'report_usage_monitor\task\disk_usage',
        'blocking' => 0,
        'minute' => '0',
        'hour' => $du_command_available ? '*/6' : '12', // Cada 6 horas si du está activo, de lo contrario, cada 12 horas.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Tarea para calcular los usuarios conectados más recientes.
    array(
        'classname' => 'report_usage_monitor\task\last_users',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/2',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Tarea para procesar notificaciones sobre el espacio en disco.
    array(
        'classname' => 'report_usage_monitor\task\notification_disk',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '*/12',  // Cada 12 horas
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Tarea para procesar notificaciones sobre los límites de usuarios diarios.
    array(
        'classname' => 'report_usage_monitor\task\notification_userlimit',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '8',  // Una vez al día a las 8 AM
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Tarea para calcular los usuarios principales en los últimos 90 días.
    array(
        'classname' => 'report_usage_monitor\task\users_daily_90_days',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ),
    // Tarea para calcular los usuarios diarios.
    array(
        'classname' => 'report_usage_monitor\task\users_daily',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '0',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    )
);
