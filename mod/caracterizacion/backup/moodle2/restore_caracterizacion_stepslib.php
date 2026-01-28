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
 * Restore steps for mod_caracterizacion.
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Structure step to restore mod_caracterizacion.
 */
class restore_caracterizacion_activity_structure_step extends restore_activity_structure_step {

    /**
     * Defines the restore structure.
     *
     * @return array
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('caracterizacion', '/activity/caracterizacion');

        if ($userinfo) {
            $paths[] = new restore_path_element('caracterizacion_matriz',
                '/activity/caracterizacion/matrices/matriz');
            $paths[] = new restore_path_element('caracterizacion_role',
                '/activity/caracterizacion/matrices/matriz/roles/role');
            $paths[] = new restore_path_element('caracterizacion_recurso_general',
                '/activity/caracterizacion/matrices/matriz/recursos_generales/recurso_general');
            $paths[] = new restore_path_element('caracterizacion_unidad',
                '/activity/caracterizacion/matrices/matriz/unidades/unidad');
            $paths[] = new restore_path_element('caracterizacion_tema',
                '/activity/caracterizacion/matrices/matriz/unidades/unidad/temas/tema');
            $paths[] = new restore_path_element('caracterizacion_recurso',
                '/activity/caracterizacion/matrices/matriz/unidades/unidad/temas/tema/recursos/recurso');
            $paths[] = new restore_path_element('caracterizacion_fase',
                '/activity/caracterizacion/matrices/matriz/fases/fase');
            $paths[] = new restore_path_element('caracterizacion_comentario',
                '/activity/caracterizacion/matrices/matriz/fases/fase/comentarios/comentario');
        }

        return $paths;
    }

    /**
     * Process the main activity data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('caracterizacion', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process matrix data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_matriz($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->caracterizacionid = $this->get_new_parentid('caracterizacion');
        $data->creado_por = $this->get_mappingid('user', $data->creado_por);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('caracterizacion_matriz', $data);
        $this->set_mapping('caracterizacion_matriz', $oldid, $newitemid);
    }

    /**
     * Process role data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_role($data) {
        global $DB;

        $data = (object) $data;
        $data->matrizid = $this->get_new_parentid('caracterizacion_matriz');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('caracterizacion_roles', $data);
    }

    /**
     * Process general resource data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_recurso_general($data) {
        global $DB;

        $data = (object) $data;
        $data->matrizid = $this->get_new_parentid('caracterizacion_matriz');

        $DB->insert_record('caracterizacion_recursos_gen', $data);
    }

    /**
     * Process unit data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_unidad($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->matrizid = $this->get_new_parentid('caracterizacion_matriz');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('caracterizacion_unidades', $data);
        $this->set_mapping('caracterizacion_unidad', $oldid, $newitemid);
    }

    /**
     * Process topic data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_tema($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->unidadid = $this->get_new_parentid('caracterizacion_unidad');

        $newitemid = $DB->insert_record('caracterizacion_temas', $data);
        $this->set_mapping('caracterizacion_tema', $oldid, $newitemid);
    }

    /**
     * Process resource data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_recurso($data) {
        global $DB;

        $data = (object) $data;
        $data->temaid = $this->get_new_parentid('caracterizacion_tema');

        $DB->insert_record('caracterizacion_recursos', $data);
    }

    /**
     * Process phase data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_fase($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->matrizid = $this->get_new_parentid('caracterizacion_matriz');
        if (!empty($data->aprobado_por)) {
            $data->aprobado_por = $this->get_mappingid('user', $data->aprobado_por);
        }
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('caracterizacion_fases', $data);
        $this->set_mapping('caracterizacion_fase', $oldid, $newitemid);
    }

    /**
     * Process comment data.
     *
     * @param array $data The data.
     */
    protected function process_caracterizacion_comentario($data) {
        global $DB;

        $data = (object) $data;
        $data->faseid = $this->get_new_parentid('caracterizacion_fase');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

        $DB->insert_record('caracterizacion_comentarios', $data);
    }

    /**
     * After execute actions.
     */
    protected function after_execute() {
        $this->add_related_files('mod_caracterizacion', 'intro', null);
    }
}
