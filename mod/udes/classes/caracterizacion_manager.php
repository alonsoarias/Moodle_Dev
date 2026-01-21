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
 * Caracterization manager class.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_udes;

defined('MOODLE_INTERNAL') || die();

/**
 * Manager class for caracterization operations.
 *
 * @package    mod_udes
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class caracterizacion_manager {

    /**
     * Create a new caracterization.
     *
     * @param int $udesid UDES activity ID
     * @param \stdClass $data Caracterization data
     * @return int Caracterization ID
     */
    public static function create_caracterizacion($udesid, $data) {
        global $DB;

        $caracterizacion = new \stdClass();
        $caracterizacion->udesid = $udesid;
        $caracterizacion->nombre = trim($data->nombre);

        // Course information - Excel H1-I2.
        $caracterizacion->programa_academico = isset($data->programa_academico) ? $data->programa_academico : '';
        $caracterizacion->nombre_curso = isset($data->nombre_curso) ? $data->nombre_curso : '';

        // Team members - Excel H3-I9.
        $caracterizacion->asesor_metodologico = isset($data->asesor_metodologico) ? $data->asesor_metodologico : '';
        $caracterizacion->experto_disciplinar = isset($data->experto_disciplinar) ? $data->experto_disciplinar : '';
        $caracterizacion->par_academico = isset($data->par_academico) ? $data->par_academico : '';
        $caracterizacion->corrector_estilo = isset($data->corrector_estilo) ? $data->corrector_estilo : '';
        $caracterizacion->coordinacion_produccion = isset($data->coordinacion_produccion) ? $data->coordinacion_produccion : '';
        $caracterizacion->produccion = isset($data->produccion) ? $data->produccion : '';
        $caracterizacion->alistamiento = isset($data->alistamiento) ? $data->alistamiento : '';

        // General resources - Excel J11-J15.
        $caracterizacion->cvp = isset($data->cvp) ? $data->cvp : 0;
        $caracterizacion->sala_clases = isset($data->sala_clases) ? $data->sala_clases : 0;
        $caracterizacion->video_bienvenida = isset($data->video_bienvenida) ? $data->video_bienvenida : 0;
        $caracterizacion->foro_curso = isset($data->foro_curso) ? $data->foro_curso : 0;
        $caracterizacion->mapa_curso = isset($data->mapa_curso) ? $data->mapa_curso : 0;

        // Workflow tracking.
        $caracterizacion->currentphase = 1; // Start at phase 1.
        $caracterizacion->estado = 'borrador';

        $caracterizacion->timecreated = time();
        $caracterizacion->timemodified = time();

        $caracterizacionid = $DB->insert_record('udes_caracterizacion', $caracterizacion);

        // Create initial workflow record for phase 1.
        self::init_workflow($caracterizacionid);

        return $caracterizacionid;
    }

    /**
     * Update an existing caracterization.
     *
     * @param int $caracterizacionid Caracterization ID
     * @param \stdClass $data Updated data
     * @return bool Success
     */
    public static function update_caracterizacion($caracterizacionid, $data) {
        global $DB;

        $caracterizacion = $DB->get_record('udes_caracterizacion', array('id' => $caracterizacionid), '*', MUST_EXIST);

        $caracterizacion->nombre = trim($data->nombre);

        // Course information.
        if (isset($data->programa_academico)) {
            $caracterizacion->programa_academico = $data->programa_academico;
        }
        if (isset($data->nombre_curso)) {
            $caracterizacion->nombre_curso = $data->nombre_curso;
        }

        // Team members.
        if (isset($data->asesor_metodologico)) {
            $caracterizacion->asesor_metodologico = $data->asesor_metodologico;
        }
        if (isset($data->experto_disciplinar)) {
            $caracterizacion->experto_disciplinar = $data->experto_disciplinar;
        }
        if (isset($data->par_academico)) {
            $caracterizacion->par_academico = $data->par_academico;
        }
        if (isset($data->corrector_estilo)) {
            $caracterizacion->corrector_estilo = $data->corrector_estilo;
        }
        if (isset($data->coordinacion_produccion)) {
            $caracterizacion->coordinacion_produccion = $data->coordinacion_produccion;
        }
        if (isset($data->produccion)) {
            $caracterizacion->produccion = $data->produccion;
        }
        if (isset($data->alistamiento)) {
            $caracterizacion->alistamiento = $data->alistamiento;
        }

        // General resources.
        if (isset($data->cvp)) {
            $caracterizacion->cvp = $data->cvp;
        }
        if (isset($data->sala_clases)) {
            $caracterizacion->sala_clases = $data->sala_clases;
        }
        if (isset($data->video_bienvenida)) {
            $caracterizacion->video_bienvenida = $data->video_bienvenida;
        }
        if (isset($data->foro_curso)) {
            $caracterizacion->foro_curso = $data->foro_curso;
        }
        if (isset($data->mapa_curso)) {
            $caracterizacion->mapa_curso = $data->mapa_curso;
        }

        $caracterizacion->timemodified = time();

        return $DB->update_record('udes_caracterizacion', $caracterizacion);
    }

    /**
     * Delete a caracterization and all associated data.
     *
     * @param int $caracterizacionid Caracterization ID
     * @return bool Success
     */
    public static function delete_caracterizacion($caracterizacionid) {
        global $DB;

        // Delete all associated records (cascading delete).
        $DB->delete_records('udes_recursos', array('caracterizacionid' => $caracterizacionid));
        $DB->delete_records('udes_workflow', array('caracterizacionid' => $caracterizacionid));
        $DB->delete_records('udes_aprobaciones', array('caracterizacionid' => $caracterizacionid));
        $DB->delete_records('udes_comentarios', array('caracterizacionid' => $caracterizacionid));
        $DB->delete_records('udes_role_assignments', array('caracterizacionid' => $caracterizacionid));

        // Delete the caracterization itself.
        return $DB->delete_records('udes_caracterizacion', array('id' => $caracterizacionid));
    }

    /**
     * Get a caracterization by ID.
     *
     * @param int $caracterizacionid Caracterization ID
     * @return \stdClass|false Caracterization object or false
     */
    public static function get_caracterizacion($caracterizacionid) {
        global $DB;

        return $DB->get_record('udes_caracterizacion', array('id' => $caracterizacionid));
    }

    /**
     * Get all caracterizations for a UDES activity.
     *
     * @param int $udesid UDES activity ID
     * @param string $sort Sort order (default: 'nombre ASC')
     * @return array Array of caracterization objects
     */
    public static function get_caracterizaciones_by_udes($udesid, $sort = 'nombre ASC') {
        global $DB;

        return $DB->get_records('udes_caracterizacion', array('udesid' => $udesid), $sort);
    }

    /**
     * Get caracterization with progress information.
     *
     * @param int $caracterizacionid Caracterization ID
     * @return \stdClass Caracterization with progress data
     */
    public static function get_caracterizacion_with_progress($caracterizacionid) {
        global $DB;

        $caracterizacion = self::get_caracterizacion($caracterizacionid);
        if (!$caracterizacion) {
            return false;
        }

        // Count resources.
        $caracterizacion->recursos_count = $DB->count_records('udes_recursos',
            array('caracterizacionid' => $caracterizacionid));

        // Get workflow progress.
        $caracterizacion->workflow_phases = $DB->get_records('udes_workflow',
            array('caracterizacionid' => $caracterizacionid), 'phase ASC');

        // Count approvals.
        $caracterizacion->aprobaciones_count = $DB->count_records('udes_aprobaciones',
            array('caracterizacionid' => $caracterizacionid, 'aprobado' => 1));

        // Count comments.
        $caracterizacion->comentarios_count = $DB->count_records('udes_comentarios',
            array('caracterizacionid' => $caracterizacionid));

        return $caracterizacion;
    }

    /**
     * Initialize workflow for a new caracterization.
     *
     * @param int $caracterizacionid Caracterization ID
     * @return void
     */
    private static function init_workflow($caracterizacionid) {
        global $DB, $USER;

        // Create workflow record for phase 1.
        $workflow = new \stdClass();
        $workflow->caracterizacionid = $caracterizacionid;
        $workflow->phase = 1;
        $workflow->estado = 'en_proceso';
        $workflow->userid = $USER->id;
        $workflow->timecreated = time();
        $workflow->timemodified = time();

        $DB->insert_record('udes_workflow', $workflow);
    }

    /**
     * Advance caracterization to next phase.
     *
     * @param int $caracterizacionid Caracterization ID
     * @param int $userid User advancing the phase
     * @return bool Success
     */
    public static function advance_to_next_phase($caracterizacionid, $userid) {
        global $DB;

        $caracterizacion = $DB->get_record('udes_caracterizacion', array('id' => $caracterizacionid), '*', MUST_EXIST);

        if ($caracterizacion->currentphase >= 6) {
            // Already at final phase.
            return false;
        }

        // Update current phase.
        $caracterizacion->currentphase++;
        $caracterizacion->timemodified = time();
        $DB->update_record('udes_caracterizacion', $caracterizacion);

        // Create workflow record for next phase.
        $workflow = new \stdClass();
        $workflow->caracterizacionid = $caracterizacionid;
        $workflow->phase = $caracterizacion->currentphase;
        $workflow->estado = 'en_proceso';
        $workflow->userid = $userid;
        $workflow->timecreated = time();
        $workflow->timemodified = time();

        $DB->insert_record('udes_workflow', $workflow);

        return true;
    }

    /**
     * Get caracterization statistics.
     *
     * @param int $udesid UDES activity ID
     * @return \stdClass Statistics object
     */
    public static function get_stats($udesid) {
        global $DB;

        $stats = new \stdClass();

        // Total caracterizations.
        $stats->total = $DB->count_records('udes_caracterizacion', array('udesid' => $udesid));

        // By phase.
        $sql = "SELECT currentphase, COUNT(*) as count
                  FROM {udes_caracterizacion}
                 WHERE udesid = :udesid
              GROUP BY currentphase";
        $stats->by_phase = $DB->get_records_sql($sql, array('udesid' => $udesid));

        // By status.
        $sql = "SELECT estado, COUNT(*) as count
                  FROM {udes_caracterizacion}
                 WHERE udesid = :udesid
              GROUP BY estado";
        $stats->by_estado = $DB->get_records_sql($sql, array('udesid' => $udesid));

        return $stats;
    }
}
