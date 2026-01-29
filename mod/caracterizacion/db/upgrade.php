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
 * Plugin upgrade steps.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_caracterizacion upgrade from the given old version.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool
 */
function xmldb_caracterizacion_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to version 2024012901: Create UDES roles.
    if ($oldversion < 2024012901) {
        // Create the 8 UDES workflow roles if they don't exist.
        caracterizacion_create_udes_roles();

        upgrade_mod_savepoint(true, 2024012901, 'caracterizacion');
    }

    return true;
}

/**
 * Create the 8 UDES workflow roles with their specific capabilities.
 *
 * This function can be called during installation or upgrade.
 */
function caracterizacion_create_udes_roles() {
    global $DB;

    // Define the 8 UDES roles with their capabilities.
    $udesroles = [
        [
            'shortname' => 'udes_experto_disciplinar',
            'name' => 'UDES - Experto Disciplinar',
            'description' => 'Rol para el experto disciplinar en el proceso de producción de recursos educativos UDES. Diligencia los formularios de caracterización y recursos educativos.',
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
            'description' => 'Rol para el asesor metodológico (diseñador instruccional) en el proceso de producción de recursos educativos UDES. Acompaña al experto y aprueba las fases 1, 2, 3 y 6.',
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
            'description' => 'Rol para el revisor curricular en el proceso de producción de recursos educativos UDES. Revisa y realiza recomendaciones sobre la caracterización en la fase 2.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:revisorcurricular' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_par_academico',
            'name' => 'UDES - Par Académico',
            'description' => 'Rol para el par académico (par disciplinar) en el proceso de producción de recursos educativos UDES. Revisa y realiza recomendaciones en la fase 3.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:paracademico' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_corrector_estilo',
            'name' => 'UDES - Corrector de Estilo',
            'description' => 'Rol para el corrector de estilo en el proceso de producción de recursos educativos UDES. Ajusta textos de caracterización y recursos en la fase 3.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:correctorestilo' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_coord_produccion',
            'name' => 'UDES - Coordinación de Producción',
            'description' => 'Rol para la coordinación de producción en el proceso de producción de recursos educativos UDES. Asigna recursos al equipo de producción y aprueba la fase 4.',
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
            'description' => 'Rol para el profesional de producción (diseño) en el proceso de producción de recursos educativos UDES. Desarrolla los recursos educativos en la fase 4.',
            'archetype' => 'teacher',
            'capabilities' => [
                'mod/caracterizacion:view' => CAP_ALLOW,
                'mod/caracterizacion:produccion' => CAP_ALLOW,
            ],
        ],
        [
            'shortname' => 'udes_alistamiento',
            'name' => 'UDES - Alistamiento',
            'description' => 'Rol para el profesional de alistamiento en el proceso de producción de recursos educativos UDES. Alista los recursos en la plataforma UDES Virtual y completa la fase 5.',
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
            // Role exists, update capabilities.
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

        // Assign capabilities to the role.
        foreach ($roledata['capabilities'] as $capability => $permission) {
            assign_capability($capability, $permission, $roleid, $systemcontext->id, true);
        }
    }

    // Mark context as dirty to refresh capabilities cache.
    $systemcontext->mark_dirty();
}
