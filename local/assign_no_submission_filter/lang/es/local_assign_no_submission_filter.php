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
 * Spanish language strings for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Filtro de Tareas Sin Entregas';
$string['privacy:metadata'] = 'El plugin Filtro de Tareas Sin Entregas no almacena datos personales.';

// Settings
$string['enabled'] = 'Habilitar filtrado';
$string['enabled_desc'] = 'Cuando está habilitado, oculta automáticamente estudiantes sin entregas en la vista de calificación.';

$string['filtermode'] = 'Modo de filtrado';
$string['filtermode_desc'] = 'Selecciona cómo manejar estudiantes sin entregas.';
$string['mode_hide'] = 'Ocultar completamente';
$string['mode_highlight'] = 'Resaltar pero mostrar';

$string['applytoroles'] = 'Aplicar a roles';
$string['applytoroles_desc'] = 'Selecciona qué roles verán la vista filtrada. Por defecto: Profesor y Profesor sin permiso de edición.';

$string['autoapply'] = 'Aplicar automáticamente';
$string['autoapply_desc'] = 'Aplica automáticamente el filtro sin intervención del usuario.';

$string['hidenosubmission'] = 'Ocultar estudiantes sin entregas';

// Capabilities
$string['assign_no_submission_filter:bypassfilter'] = 'Omitir filtro de entregas';
$string['assign_no_submission_filter:configure'] = 'Configurar filtro de entregas';