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
 * Sync enrolments task.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_nexuspay\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Sync enrolments task class.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_enrolments extends \core\task\scheduled_task {

    /**
     * Get the name of this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('syncenrolmentstask', 'enrol_nexuspay');
    }

    /**
     * Execute the task.
     *
     * This task synchronizes NexusPay enrollments, processing expired
     * enrollments according to the configured action.
     */
    public function execute() {
        global $DB;
        
        $enrol = enrol_get_plugin('nexuspay');
        
        if (!$enrol) {
            mtrace('NexusPay enrollment plugin not found');
            return;
        }
        
        $trace = new \text_progress_trace();
        
        // Process expired enrollments.
        $this->process_expired_enrolments($trace);
        
        // Sync enrollments.
        $enrol->sync($trace);
        
        $trace->finished();
    }

    /**
     * Process expired enrollments according to configured action.
     *
     * @param \progress_trace $trace
     */
    protected function process_expired_enrolments(\progress_trace $trace) {
        global $DB;
        
        $action = get_config('enrol_nexuspay', 'expiredaction');
        $now = time();
        
        if ($action == ENROL_EXT_REMOVED_KEEP) {
            // Keep enrollments active - no action needed.
            $trace->output('Expired action is set to keep - no enrollments will be modified');
            return;
        }
        
        // Get expired enrollments.
        $sql = "SELECT ue.*, e.courseid, e.id as instanceid
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                WHERE e.enrol = :enrol
                  AND ue.timeend > 0
                  AND ue.timeend < :now
                  AND ue.status = :active";
        
        $params = [
            'enrol' => 'nexuspay',
            'now' => $now,
            'active' => ENROL_USER_ACTIVE
        ];
        
        $expiredenrolments = $DB->get_records_sql($sql, $params);
        
        if (empty($expiredenrolments)) {
            $trace->output('No expired enrollments found');
            return;
        }
        
        $trace->output('Processing ' . count($expiredenrolments) . ' expired enrollments');
        
        foreach ($expiredenrolments as $ue) {
            $course = $DB->get_record('course', ['id' => $ue->courseid]);
            $user = $DB->get_record('user', ['id' => $ue->userid]);
            
            if (!$course || !$user) {
                continue;
            }
            
            $trace->output("Processing expired enrollment for user {$user->id} in course {$course->id}");
            
            switch ($action) {
                case ENROL_EXT_REMOVED_SUSPENDNOROLES:
                    // Suspend enrollment and remove roles.
                    $instance = $DB->get_record('enrol', ['id' => $ue->instanceid]);
                    if ($instance) {
                        $enrol = enrol_get_plugin('nexuspay');
                        $enrol->update_user_enrol($instance, $ue->userid, ENROL_USER_SUSPENDED);
                        
                        // Remove roles.
                        $context = \context_course::instance($course->id);
                        role_unassign_all([
                            'userid' => $user->id,
                            'contextid' => $context->id,
                            'component' => 'enrol_nexuspay',
                            'itemid' => $instance->id
                        ]);
                        
                        $trace->output("Suspended user and removed roles");
                    }
                    break;
                    
                case ENROL_EXT_REMOVED_UNENROL:
                    // Completely unenroll user.
                    $instance = $DB->get_record('enrol', ['id' => $ue->instanceid]);
                    if ($instance) {
                        $enrol = enrol_get_plugin('nexuspay');
                        $enrol->unenrol_user($instance, $ue->userid);
                        $trace->output("Unenrolled user from course");
                    }
                    break;
            }
        }
    }
}