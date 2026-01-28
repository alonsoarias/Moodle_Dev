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
 * Backup steps for mod_caracterizacion.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Define the complete backup structure for mod_caracterizacion.
 */
class backup_caracterizacion_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element.
        $activity = new backup_nested_element('caracterizacion', ['id'], [
            'name', 'intro', 'introformat', 'timecreated', 'timemodified',
        ]);

        $matrices = new backup_nested_element('matrices');
        $matriz = new backup_nested_element('matriz', ['id'], [
            'programa_academico', 'nombre_curso', 'fase_actual', 'estado',
            'creado_por', 'timecreated', 'timemodified',
        ]);

        $roles = new backup_nested_element('roles');
        $role = new backup_nested_element('role', ['id'], [
            'rol', 'userid', 'nombre_completo', 'timecreated',
        ]);

        $recursosgen = new backup_nested_element('recursos_generales');
        $recursogen = new backup_nested_element('recurso_general', ['id'], [
            'recurso', 'incluido', 'observaciones',
        ]);

        $unidades = new backup_nested_element('unidades');
        $unidad = new backup_nested_element('unidad', ['id'], [
            'numero', 'nombre', 'timecreated', 'timemodified',
        ]);

        $temas = new backup_nested_element('temas');
        $tema = new backup_nested_element('tema', ['id'], [
            'numero', 'nombre',
        ]);

        $recursos = new backup_nested_element('recursos');
        $recurso = new backup_nested_element('recurso', ['id'], [
            'tipo_recurso', 'recurso', 'item', 'observaciones',
        ]);

        $fases = new backup_nested_element('fases');
        $fase = new backup_nested_element('fase', ['id'], [
            'fase', 'estado', 'aprobado_por', 'fecha_inicio', 'fecha_fin',
            'timecreated', 'timemodified',
        ]);

        $comentarios = new backup_nested_element('comentarios');
        $comentario = new backup_nested_element('comentario', ['id'], [
            'userid', 'comentario', 'accion', 'timecreated',
        ]);

        // Build the tree.
        $activity->add_child($matrices);
        $matrices->add_child($matriz);

        $matriz->add_child($roles);
        $roles->add_child($role);

        $matriz->add_child($recursosgen);
        $recursosgen->add_child($recursogen);

        $matriz->add_child($unidades);
        $unidades->add_child($unidad);

        $unidad->add_child($temas);
        $temas->add_child($tema);

        $tema->add_child($recursos);
        $recursos->add_child($recurso);

        $matriz->add_child($fases);
        $fases->add_child($fase);

        $fase->add_child($comentarios);
        $comentarios->add_child($comentario);

        // Define sources.
        $activity->set_source_table('caracterizacion', ['id' => backup::VAR_ACTIVITYID]);

        if ($userinfo) {
            $matriz->set_source_table('caracterizacion_matriz', ['caracterizacionid' => backup::VAR_PARENTID]);
            $role->set_source_table('caracterizacion_roles', ['matrizid' => backup::VAR_PARENTID]);
            $recursogen->set_source_table('caracterizacion_recursos_gen', ['matrizid' => backup::VAR_PARENTID]);
            $unidad->set_source_table('caracterizacion_unidades', ['matrizid' => backup::VAR_PARENTID]);
            $tema->set_source_table('caracterizacion_temas', ['unidadid' => backup::VAR_PARENTID]);
            $recurso->set_source_table('caracterizacion_recursos', ['temaid' => backup::VAR_PARENTID]);
            $fase->set_source_table('caracterizacion_fases', ['matrizid' => backup::VAR_PARENTID]);
            $comentario->set_source_table('caracterizacion_comentarios', ['faseid' => backup::VAR_PARENTID]);
        }

        // Define ID annotations.
        if ($userinfo) {
            $matriz->annotate_ids('user', 'creado_por');
            $role->annotate_ids('user', 'userid');
            $fase->annotate_ids('user', 'aprobado_por');
            $comentario->annotate_ids('user', 'userid');
        }

        return $this->prepare_activity_structure($activity);
    }
}
