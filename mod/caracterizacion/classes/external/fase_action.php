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
 * External function for phase actions (approve/reject).
 *
 * @package     mod_caracterizacion
 * @copyright   2024 Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_caracterizacion\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/caracterizacion/lib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;

/**
 * External function for handling phase actions.
 */
class fase_action extends external_api {

    /**
     * Parameter definition.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'faseid' => new external_value(PARAM_INT, 'Phase record ID'),
            'matrizid' => new external_value(PARAM_INT, 'Matrix ID'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'accion' => new external_value(PARAM_ALPHA, 'Action: aprobar or rechazar'),
            'comentario' => new external_value(PARAM_RAW, 'Comment text'),
        ]);
    }

    /**
     * Execute the phase action.
     *
     * @param int $faseid Phase record ID.
     * @param int $matrizid Matrix ID.
     * @param int $cmid Course module ID.
     * @param string $accion Action type.
     * @param string $comentario Comment text.
     * @return array Result.
     */
    public static function execute($faseid, $matrizid, $cmid, $accion, $comentario) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'faseid' => $faseid,
            'matrizid' => $matrizid,
            'cmid' => $cmid,
            'accion' => $accion,
            'comentario' => $comentario,
        ]);

        $cm = get_coursemodule_from_id('caracterizacion', $params['cmid'], 0, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);
        self::validate_context($context);

        $fase = $DB->get_record('caracterizacion_fases', ['id' => $params['faseid']], '*', MUST_EXIST);
        $matriz = $DB->get_record('caracterizacion_matriz', ['id' => $params['matrizid']], '*', MUST_EXIST);

        // Check permissions.
        if (!caracterizacion_user_can_act_on_phase($fase->fase, $context)) {
            return [
                'success' => false,
                'message' => get_string('sinpermisosfase', 'mod_caracterizacion'),
            ];
        }

        // Check that comment is provided.
        if (empty(trim($params['comentario']))) {
            return [
                'success' => false,
                'message' => get_string('comentarioobligatorio', 'mod_caracterizacion'),
            ];
        }

        $now = time();

        // Save comment.
        $comrecord = new \stdClass();
        $comrecord->faseid = $fase->id;
        $comrecord->userid = $USER->id;
        $comrecord->comentario = clean_param($params['comentario'], PARAM_TEXT);
        $comrecord->accion = $params['accion'];
        $comrecord->timecreated = $now;
        $DB->insert_record('caracterizacion_comentarios', $comrecord);

        $phases = caracterizacion_get_phases();

        if ($params['accion'] === 'aprobar') {
            // Approve current phase.
            $fase->estado = 'aprobada';
            $fase->aprobado_por = $USER->id;
            $fase->fecha_fin = $now;
            $fase->timemodified = $now;
            $DB->update_record('caracterizacion_fases', $fase);

            // Advance to next phase if not the last one.
            if ($fase->fase < 6) {
                $nextfase = $DB->get_record('caracterizacion_fases', [
                    'matrizid' => $matriz->id,
                    'fase' => $fase->fase + 1,
                ]);
                if ($nextfase) {
                    $nextfase->estado = 'en_revision';
                    $nextfase->fecha_inicio = $now;
                    $nextfase->timemodified = $now;
                    $DB->update_record('caracterizacion_fases', $nextfase);
                }

                $matriz->fase_actual = $fase->fase + 1;
                $matriz->estado = 'en_proceso';
                $matriz->timemodified = $now;
                $DB->update_record('caracterizacion_matriz', $matriz);
            } else {
                // Last phase approved - mark matrix as completed.
                $matriz->estado = 'completada';
                $matriz->timemodified = $now;
                $DB->update_record('caracterizacion_matriz', $matriz);
            }

            // Send notification.
            self::send_phase_notification($matriz, $fase->fase, 'aprobada', $context);

        } else if ($params['accion'] === 'rechazar') {
            // Reject current phase.
            $fase->estado = 'rechazada';
            $fase->timemodified = $now;
            $DB->update_record('caracterizacion_fases', $fase);

            // Send notification.
            self::send_phase_notification($matriz, $fase->fase, 'rechazada', $context);
        }

        return [
            'success' => true,
            'message' => get_string('accionrealizada', 'mod_caracterizacion'),
        ];
    }

    /**
     * Return definition.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the action was successful'),
            'message' => new external_value(PARAM_RAW, 'Result message'),
        ]);
    }

    /**
     * Send notification for phase transition.
     *
     * @param object $matriz Matrix record.
     * @param int $fasenumber Phase number.
     * @param string $tipo Notification type (aprobada/rechazada).
     * @param object $context Module context.
     */
    private static function send_phase_notification($matriz, $fasenumber, $tipo, $context) {
        global $DB, $USER;

        $phases = caracterizacion_get_phases();
        $fasename = get_string($phases[$fasenumber], 'mod_caracterizacion');

        // Get all users assigned to roles in this matrix.
        $roles = $DB->get_records('caracterizacion_roles', ['matrizid' => $matriz->id]);
        $notifiedusers = [];

        foreach ($roles as $role) {
            if ($role->userid == $USER->id) {
                continue; // Don't notify the actor.
            }
            if (in_array($role->userid, $notifiedusers)) {
                continue; // Don't double-notify.
            }
            $notifiedusers[] = $role->userid;

            // Log the notification.
            $notifrecord = new \stdClass();
            $notifrecord->matrizid = $matriz->id;
            $notifrecord->fase = $fasenumber;
            $notifrecord->userid_from = $USER->id;
            $notifrecord->userid_to = $role->userid;
            $notifrecord->tipo = 'fase_' . $tipo;
            $notifrecord->timecreated = time();

            $a = new \stdClass();
            $a->fase = $fasename;
            $a->matriz = $matriz->nombre_curso;
            $a->usuario = fullname($USER);
            $notifrecord->mensaje = get_string('notif_fase_' . $tipo, 'mod_caracterizacion', $a);

            $DB->insert_record('caracterizacion_notif', $notifrecord);

            // Send Moodle message.
            $eventdata = new \core\message\message();
            $eventdata->courseid = $DB->get_field('course_modules', 'course', ['id' => $context->instanceid]);
            $eventdata->component = 'mod_caracterizacion';
            $eventdata->name = 'fase' . $tipo;
            $eventdata->userfrom = $USER;
            $eventdata->userto = \core_user::get_user($role->userid);
            $eventdata->subject = get_string('notif_subject_' . $tipo, 'mod_caracterizacion', $matriz->nombre_curso);
            $eventdata->fullmessage = $notifrecord->mensaje;
            $eventdata->fullmessageformat = FORMAT_PLAIN;
            $eventdata->fullmessagehtml = '<p>' . $notifrecord->mensaje . '</p>';
            $eventdata->smallmessage = $notifrecord->mensaje;
            $eventdata->notification = 1;

            message_send($eventdata);
        }
    }
}
