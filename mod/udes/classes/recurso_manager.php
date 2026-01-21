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
 * Resource manager for UDES educational resources.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_udes;

defined('MOODLE_INTERNAL') || die();

/**
 * Resource manager class.
 *
 * @package    mod_udes
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recurso_manager {

    /**
     * Create a new resource.
     *
     * @param int $udesid UDES instance ID
     * @param \stdClass $data Resource data
     * @param int $userid User creating the resource
     * @return int Resource ID
     */
    public static function create_recurso($udesid, $data, $userid) {
        global $DB;

        $recurso = new \stdClass();
        $recurso->udesid = $udesid;
        $recurso->unidad = $data->unidad;
        $recurso->tema = $data->tema;
        $recurso->nombre_unidad = $data->nombre_unidad;
        $recurso->nombre_tema = $data->nombre_tema;
        $recurso->tipo_recurso = $data->tipo_recurso;
        $recurso->recurso = $data->recurso;
        $recurso->contenido = isset($data->contenido) ? json_encode($data->contenido) : '';
        $recurso->estado = 'borrador';
        $recurso->userid = $userid;
        $recurso->timecreated = time();
        $recurso->timemodified = time();

        return $DB->insert_record('udes_recursos', $recurso);
    }

    /**
     * Update a resource.
     *
     * @param int $recursoid Resource ID
     * @param \stdClass $data Updated data
     * @return bool Success
     */
    public static function update_recurso($recursoid, $data) {
        global $DB;

        $recurso = $DB->get_record('udes_recursos', array('id' => $recursoid), '*', MUST_EXIST);

        $recurso->unidad = $data->unidad;
        $recurso->tema = $data->tema;
        $recurso->nombre_unidad = $data->nombre_unidad;
        $recurso->nombre_tema = $data->nombre_tema;
        $recurso->tipo_recurso = $data->tipo_recurso;
        $recurso->recurso = $data->recurso;

        if (isset($data->contenido)) {
            $recurso->contenido = json_encode($data->contenido);
        }

        $recurso->timemodified = time();

        return $DB->update_record('udes_recursos', $recurso);
    }

    /**
     * Delete a resource.
     *
     * @param int $recursoid Resource ID
     * @return bool Success
     */
    public static function delete_recurso($recursoid) {
        global $DB;

        // Delete related comments.
        $DB->delete_records('udes_comentarios', array('recursoid' => $recursoid));

        // Delete the resource.
        return $DB->delete_records('udes_recursos', array('id' => $recursoid));
    }

    /**
     * Get all resources for a UDES instance.
     *
     * @param int $udesid UDES instance ID
     * @param array $filters Optional filters (unidad, tipo_recurso, etc.)
     * @return array Array of resource objects
     */
    public static function get_recursos($udesid, $filters = array()) {
        global $DB;

        $params = array('udesid' => $udesid);
        $where = array('udesid = :udesid');

        if (isset($filters['unidad'])) {
            $params['unidad'] = $filters['unidad'];
            $where[] = 'unidad = :unidad';
        }

        if (isset($filters['tipo_recurso'])) {
            $params['tipo_recurso'] = $filters['tipo_recurso'];
            $where[] = 'tipo_recurso = :tipo_recurso';
        }

        $sql = "SELECT * FROM {udes_recursos} WHERE " . implode(' AND ', $where) . " ORDER BY unidad, tema";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get resource by ID.
     *
     * @param int $recursoid Resource ID
     * @return \stdClass|false Resource object or false
     */
    public static function get_recurso($recursoid) {
        global $DB;

        $recurso = $DB->get_record('udes_recursos', array('id' => $recursoid));

        if ($recurso && !empty($recurso->contenido)) {
            $recurso->contenido = json_decode($recurso->contenido);
        }

        return $recurso;
    }

    /**
     * Get resource types grouped by category.
     *
     * @return array Associative array of categories and their resource types
     */
    public static function get_resource_types() {
        return udes_get_resource_types();
    }

    /**
     * Get resource count by category.
     *
     * @param int $udesid UDES instance ID
     * @return array Associative array of category => count
     */
    public static function get_resource_count_by_category($udesid) {
        global $DB;

        $sql = "SELECT tipo_recurso, COUNT(*) as count
                FROM {udes_recursos}
                WHERE udesid = :udesid
                GROUP BY tipo_recurso";

        $results = $DB->get_records_sql($sql, array('udesid' => $udesid));

        $counts = array();
        foreach ($results as $result) {
            $counts[$result->tipo_recurso] = $result->count;
        }

        return $counts;
    }

    /**
     * Get total resource count.
     *
     * @param int $udesid UDES instance ID
     * @return int Total count
     */
    public static function get_total_resource_count($udesid) {
        global $DB;

        return $DB->count_records('udes_recursos', array('udesid' => $udesid));
    }

    /**
     * Get resources grouped by unidad.
     *
     * @param int $udesid UDES instance ID
     * @return array Array grouped by unidad
     */
    public static function get_recursos_by_unidad($udesid) {
        global $DB;

        $recursos = $DB->get_records('udes_recursos',
            array('udesid' => $udesid),
            'unidad, tema'
        );

        $grouped = array();
        foreach ($recursos as $recurso) {
            if (!isset($grouped[$recurso->unidad])) {
                $grouped[$recurso->unidad] = array();
            }
            $grouped[$recurso->unidad][] = $recurso;
        }

        return $grouped;
    }

    /**
     * Get form fields for a specific resource type.
     *
     * @param string $tipo_recurso Resource type
     * @param string $recurso Specific resource
     * @return array Array of form field definitions
     */
    public static function get_resource_form_fields($tipo_recurso, $recurso) {
        // Base fields common to all resources.
        $fields = array(
            'titulo' => array(
                'type' => 'text',
                'label' => 'Título del recurso',
                'required' => true
            ),
            'descripcion' => array(
                'type' => 'textarea',
                'label' => 'Descripción',
                'required' => true
            ),
            'objetivo' => array(
                'type' => 'textarea',
                'label' => 'Objetivo de aprendizaje',
                'required' => true
            ),
        );

        // Add specific fields based on resource type.
        switch ($tipo_recurso) {
            case 'RECURSOS_EDUCATIVOS_DIGITALES':
                $fields['duracion'] = array(
                    'type' => 'text',
                    'label' => 'Duración estimada (minutos)',
                    'required' => false
                );
                $fields['guion'] = array(
                    'type' => 'textarea',
                    'label' => 'Guion / Contenido',
                    'required' => true
                );
                break;

            case 'RECURSOS_INTERACTIVOS_DIGITALES':
                $fields['instrucciones'] = array(
                    'type' => 'textarea',
                    'label' => 'Instrucciones para el estudiante',
                    'required' => true
                );
                $fields['num_preguntas'] = array(
                    'type' => 'number',
                    'label' => 'Número de preguntas',
                    'required' => false
                );
                break;

            case 'RECURSOS_EVALUATIVOS':
                $fields['criterios'] = array(
                    'type' => 'textarea',
                    'label' => 'Criterios de evaluación',
                    'required' => true
                );
                $fields['puntaje'] = array(
                    'type' => 'number',
                    'label' => 'Puntaje máximo',
                    'required' => true
                );
                break;

            case 'RECURSOS_COLABORATIVOS':
                $fields['dinamica'] = array(
                    'type' => 'textarea',
                    'label' => 'Dinámica de colaboración',
                    'required' => true
                );
                break;

            case 'RECURSOS_EXTERNOS':
                $fields['url'] = array(
                    'type' => 'url',
                    'label' => 'URL del recurso externo',
                    'required' => true
                );
                $fields['proveedor'] = array(
                    'type' => 'text',
                    'label' => 'Proveedor/Plataforma',
                    'required' => false
                );
                break;
        }

        return $fields;
    }
}
