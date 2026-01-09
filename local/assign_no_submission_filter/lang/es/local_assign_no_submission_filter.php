<?php
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
 * Spanish language strings
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Filtro Automático de Tareas Sin Envío';
$string['privacy:metadata'] = 'El plugin Filtro Automático de Tareas Sin Envío no almacena datos personales.';

// General settings
$string['general_section'] = 'Configuración General';
$string['general_section_desc'] = 'Configure la funcionalidad básica del filtro de envíos.';
$string['enabled'] = 'Habilitar filtrado automático';
$string['enabled_desc'] = 'Filtrar automáticamente estudiantes sin envíos en las tablas de calificación de tareas.';
$string['filter_downloads'] = 'Aplicar filtro a descargas';
$string['filter_downloads_desc'] = 'También filtrar estudiantes sin envíos al descargar hojas de calificación.';
$string['hide_participant_count'] = 'Ocultar cantidad de participantes';
$string['hide_participant_count_desc'] = 'Ocultar la fila de cantidad de participantes en las tablas de resumen de tareas.';

// Role settings
$string['roles_section'] = 'Configuración de Roles';
$string['roles_section_desc'] = 'Seleccione qué roles verán la vista filtrada. Los usuarios con estos roles tendrán ocultos a los estudiantes sin envíos.';
$string['selected_roles'] = 'Roles seleccionados';
$string['selected_roles_desc'] = 'Elija los roles que deberían ver las vistas de tareas filtradas. Solo los usuarios con estos roles tendrán el filtro aplicado.';