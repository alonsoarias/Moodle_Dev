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
 * Post-installation script for mod_caracterizacion.
 *
 * Creates the 8 UDES roles required for the characterization workflow.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post-installation procedure.
 *
 * Creates the 8 UDES workflow roles with their specific capabilities.
 *
 * @return bool
 */
function xmldb_caracterizacion_install() {
    global $DB;

    // Define the 8 UDES roles with their capabilities.
    $udesroles = [
        [
            'shortname' => 'udes_experto_disciplinar',
            'name' => 'UDES - Experto Disciplinar',
            'description' => 'Rol para el experto disciplinar en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'editingteacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:crearmatriz' => CAP_ALLOW,
                'mod/caracterizacion:editarmatriz' => CAP_ALLOW,
                'mod/caracterizacion:expertodisciplinar' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_asesor_metodologico',
            'name' => 'UDES - Asesor Metodológico',
            'description' => 'Rol para el asesor metodológico en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'editingteacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:crearmatriz' => CAP_ALLOW,
                'mod/caracterizacion:editarmatriz' => CAP_ALLOW,
                'mod/caracterizacion:asesormetodologico' => CAP_ALLOW,
                'mod/caracterizacion:aprobarfase' => CAP_ALLOW,
                'mod/caracterizacion:vertodasmatrices' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_revisor_curricular',
            'name' => 'UDES - Revisor Curricular',
            'description' => 'Rol para el revisor curricular en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:revisorcurricular' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_par_academico',
            'name' => 'UDES - Par Académico',
            'description' => 'Rol para el par académico en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:paracademico' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_corrector_estilo',
            'name' => 'UDES - Corrector de Estilo',
            'description' => 'Rol para el corrector de estilo en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:correctorestilo' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_coord_produccion',
            'name' => 'UDES - Coordinación de Producción',
            'description' => 'Rol para la coordinación de producción en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'editingteacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:coordproduccion' => CAP_ALLOW,
                'mod/caracterizacion:aprobarfase' => CAP_ALLOW,
                'mod/caracterizacion:vertodasmatrices' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_produccion',
            'name' => 'UDES - Producción',
            'description' => 'Rol para el profesional de producción en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:produccion' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_alistamiento',
            'name' => 'UDES - Alistamiento',
            'description' => 'Rol para el profesional de alistamiento en el proceso de producción de recursos educativos UDES.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:alistamiento' => CAP_ALLOW,
                'mod/caracterizacion:aprobarfase' => CAP_ALLOW,
            ],
        ],
    ];

    // Get system context for role creation.
    $systemcontext = context_system::instance();

    foreach ($udesroles as $roledata) {
        // Check if role already exists.
        $existingrole = $DB->get_record('role', ['shortname' => $roledata['shortname']]);

        if ($existingrole) {
            $roleid = $existingrole->id;
        } else {
            // Create new role.
            $roleid = create_role(
                $roledata['name'],
                $roledata['shortname'],
                $roledata['description'],
                $roledata['archetype']
            );

            // Set role context levels (course and module).
            set_role_contextlevels($roleid, [CONTEXT_COURSE, CONTEXT_MODULE]);
        }

        // Assign capabilities to the role (only if capability exists).
        // Note: During fresh install, capabilities may not be registered yet.
        // They will be assigned later when upgrade runs or admin purges caches.
        foreach ($roledata['capabilities'] as $capability => $permission) {
            if ($DB->record_exists('capabilities', ['name' => $capability])) {
                assign_capability($capability, $permission, $roleid, $systemcontext->id, true);
            }
        }
    }

    // Mark context as dirty to refresh capabilities cache.
    $systemcontext->mark_dirty();

    return true;
}
