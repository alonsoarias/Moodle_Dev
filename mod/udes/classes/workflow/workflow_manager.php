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
 * Workflow manager for UDES production process.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_udes\workflow;

defined('MOODLE_INTERNAL') || die();

/**
 * Workflow manager class.
 *
 * @package    mod_udes
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class workflow_manager {

    /** @var int UDES instance ID */
    protected $udesid;

    /** @var \stdClass UDES instance */
    protected $udes;

    /** @var int Current phase */
    protected $currentphase;

    /**
     * Constructor.
     *
     * @param int $udesid UDES instance ID
     */
    public function __construct($udesid) {
        global $DB;

        $this->udesid = $udesid;
        $this->udes = $DB->get_record('udes', array('id' => $udesid), '*', MUST_EXIST);
        $this->currentphase = $this->udes->currentphase;
    }

    /**
     * Get current phase number.
     *
     * @return int
     */
    public function get_current_phase() {
        return $this->currentphase;
    }

    /**
     * Get phase status.
     *
     * @param int $phase Phase number
     * @return string Status: pendiente, en_proceso, aprobado, rechazado
     */
    public function get_phase_status($phase) {
        global $DB;

        $workflow = $DB->get_record('udes_workflow', array(
            'udesid' => $this->udesid,
            'phase' => $phase
        ));

        return $workflow ? $workflow->estado : 'pendiente';
    }

    /**
     * Check if phase can be accessed by user.
     *
     * @param int $phase Phase number
     * @param int $userid User ID
     * @return bool
     */
    public function can_access_phase($phase, $userid) {
        global $DB;

        // Phase 1 is always accessible.
        if ($phase == 1) {
            return true;
        }

        // Check if previous phase is approved.
        $previousphase = $phase - 1;
        $previousstatus = $this->get_phase_status($previousphase);

        return ($previousstatus === 'aprobado');
    }

    /**
     * Check if user has required role for phase.
     *
     * @param int $phase Phase number
     * @param int $userid User ID
     * @param \context $context Context
     * @return bool
     */
    public function user_has_phase_role($phase, $userid, $context) {
        $requiredcaps = $this->get_phase_required_capabilities($phase);

        foreach ($requiredcaps as $cap) {
            if (has_capability($cap, $context, $userid)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get required capabilities for a phase.
     *
     * @param int $phase Phase number
     * @return array Array of capability strings
     */
    protected function get_phase_required_capabilities($phase) {
        switch ($phase) {
            case 1: // Caracterización.
                return array('mod/udes:expertodisciplinar', 'mod/udes:asesormetodologico');
            case 2: // Revisión curricular.
                return array('mod/udes:revisorcurricular', 'mod/udes:asesormetodologico');
            case 3: // Par / Corrector.
                return array('mod/udes:pardisciplinar', 'mod/udes:correctorestilo', 'mod/udes:asesormetodologico');
            case 4: // Producción.
                return array('mod/udes:coordinacionproduccion', 'mod/udes:produccion');
            case 5: // Alistamiento.
                return array('mod/udes:alistamiento');
            case 6: // Aprobación final.
                return array('mod/udes:asesormetodologico');
            default:
                return array();
        }
    }

    /**
     * Approve current phase.
     *
     * @param int $userid User ID who approves
     * @param string $rol User role
     * @param string $comentario Optional comment
     * @return bool Success
     */
    public function approve_phase($userid, $rol, $comentario = '') {
        global $DB;

        // Check if phase is already approved.
        $existing = $DB->get_record('udes_aprobaciones', array(
            'udesid' => $this->udesid,
            'phase' => $this->currentphase,
            'rol' => $rol
        ));

        if ($existing && $existing->aprobado) {
            return false; // Already approved.
        }

        $approval = new \stdClass();
        $approval->udesid = $this->udesid;
        $approval->phase = $this->currentphase;
        $approval->rol = $rol;
        $approval->userid = $userid;
        $approval->aprobado = 1;
        $approval->comentario = $comentario;
        $approval->timecreated = time();

        if ($existing) {
            $approval->id = $existing->id;
            $DB->update_record('udes_aprobaciones', $approval);
        } else {
            $DB->insert_record('udes_aprobaciones', $approval);
        }

        // Update workflow status.
        $this->update_workflow_status($this->currentphase, 'aprobado', $userid);

        // Move to next phase if this was the final approval for current phase.
        if ($this->is_phase_fully_approved($this->currentphase)) {
            $this->advance_to_next_phase();
        }

        // Send notifications.
        $this->send_approval_notification($userid, $rol);

        return true;
    }

    /**
     * Reject current phase.
     *
     * @param int $userid User ID who rejects
     * @param string $rol User role
     * @param string $comentario Required comment explaining rejection
     * @return bool Success
     */
    public function reject_phase($userid, $rol, $comentario) {
        global $DB;

        if (empty($comentario)) {
            return false; // Comment is required for rejection.
        }

        $approval = new \stdClass();
        $approval->udesid = $this->udesid;
        $approval->phase = $this->currentphase;
        $approval->rol = $rol;
        $approval->userid = $userid;
        $approval->aprobado = 0;
        $approval->comentario = $comentario;
        $approval->timecreated = time();

        $DB->insert_record('udes_aprobaciones', $approval);

        // Update workflow status.
        $this->update_workflow_status($this->currentphase, 'rechazado', $userid);

        // Send notifications.
        $this->send_rejection_notification($userid, $rol, $comentario);

        return true;
    }

    /**
     * Check if phase is fully approved by all required roles.
     *
     * @param int $phase Phase number
     * @return bool
     */
    protected function is_phase_fully_approved($phase) {
        global $DB;

        // For now, require at least one approval per phase.
        // In a more complex implementation, you could require multiple approvals.
        $approvals = $DB->count_records('udes_aprobaciones', array(
            'udesid' => $this->udesid,
            'phase' => $phase,
            'aprobado' => 1
        ));

        return ($approvals >= 1);
    }

    /**
     * Advance to next phase.
     *
     * @return bool Success
     */
    protected function advance_to_next_phase() {
        global $DB;

        if ($this->currentphase >= 6) {
            return false; // Already at final phase.
        }

        $nextphase = $this->currentphase + 1;

        // Update UDES instance.
        $this->udes->currentphase = $nextphase;
        $this->udes->timemodified = time();
        $DB->update_record('udes', $this->udes);

        $this->currentphase = $nextphase;

        // Create workflow record for next phase.
        $workflow = new \stdClass();
        $workflow->udesid = $this->udesid;
        $workflow->phase = $nextphase;
        $workflow->estado = 'en_proceso';
        $workflow->userid = 0; // Will be set when someone starts working on it.
        $workflow->timecreated = time();
        $workflow->timemodified = time();

        $DB->insert_record('udes_workflow', $workflow);

        // Send notification to next phase users.
        $this->send_next_phase_notification($nextphase);

        return true;
    }

    /**
     * Update workflow status.
     *
     * @param int $phase Phase number
     * @param string $estado Status
     * @param int $userid User ID
     */
    protected function update_workflow_status($phase, $estado, $userid) {
        global $DB;

        $workflow = $DB->get_record('udes_workflow', array(
            'udesid' => $this->udesid,
            'phase' => $phase
        ));

        if ($workflow) {
            $workflow->estado = $estado;
            $workflow->userid = $userid;
            $workflow->timemodified = time();
            $DB->update_record('udes_workflow', $workflow);
        } else {
            $workflow = new \stdClass();
            $workflow->udesid = $this->udesid;
            $workflow->phase = $phase;
            $workflow->estado = $estado;
            $workflow->userid = $userid;
            $workflow->timecreated = time();
            $workflow->timemodified = time();
            $DB->insert_record('udes_workflow', $workflow);
        }
    }

    /**
     * Send approval notification.
     *
     * @param int $userid User who approved
     * @param string $rol Role
     */
    protected function send_approval_notification($userid, $rol) {
        // TODO: Implement notification sending.
        // This will send messages to relevant users about the approval.
    }

    /**
     * Send rejection notification.
     *
     * @param int $userid User who rejected
     * @param string $rol Role
     * @param string $comentario Comment
     */
    protected function send_rejection_notification($userid, $rol, $comentario) {
        // TODO: Implement notification sending.
        // This will send messages to relevant users about the rejection.
    }

    /**
     * Send notification for next phase.
     *
     * @param int $nextphase Next phase number
     */
    protected function send_next_phase_notification($nextphase) {
        // TODO: Implement notification sending.
        // This will notify users who can work on the next phase.
    }

    /**
     * Add comment to current phase.
     *
     * @param int $userid User ID
     * @param string $comentario Comment text
     * @param int $recursoid Optional resource ID
     * @return int Comment ID
     */
    public function add_comment($userid, $comentario, $recursoid = null) {
        global $DB;

        $comment = new \stdClass();
        $comment->udesid = $this->udesid;
        $comment->recursoid = $recursoid;
        $comment->phase = $this->currentphase;
        $comment->userid = $userid;
        $comment->comentario = $comentario;
        $comment->timecreated = time();

        return $DB->insert_record('udes_comentarios', $comment);
    }

    /**
     * Get comments for current phase.
     *
     * @param int $recursoid Optional resource ID to filter by
     * @return array Array of comment objects
     */
    public function get_comments($recursoid = null) {
        global $DB;

        $params = array(
            'udesid' => $this->udesid,
            'phase' => $this->currentphase
        );

        if ($recursoid !== null) {
            $params['recursoid'] = $recursoid;
        }

        return $DB->get_records('udes_comentarios', $params, 'timecreated DESC');
    }
}
