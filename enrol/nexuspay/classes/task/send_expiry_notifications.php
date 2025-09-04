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
 * Send expiry notifications task.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_nexuspay\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Send expiry notifications task class.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_expiry_notifications extends \core\task\scheduled_task {

    /**
     * Get the name of this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendexpirynotificationstask', 'enrol_nexuspay');
    }

    /**
     * Execute the task.
     *
     * This task sends notifications to users whose enrollment is about to expire.
     */
    public function execute() {
        global $DB, $CFG;
        
        $enrol = enrol_get_plugin('nexuspay');
        
        if (!$enrol) {
            mtrace('NexusPay enrollment plugin not found');
            return;
        }
        
        $trace = new \text_progress_trace();
        
        // Send standard expiry notifications.
        $enrol->send_expiry_notifications($trace);
        
        // Send extra expiry notifications for recently expired enrollments.
        $this->send_expired_notifications($trace);
        
        $trace->finished();
    }

    /**
     * Send notifications for recently expired enrollments.
     *
     * @param \progress_trace $trace
     */
    protected function send_expired_notifications(\progress_trace $trace) {
        global $DB, $CFG;
        
        $expirynotifyperiod = get_config('enrol_nexuspay', 'expirynotifyperiod');
        if (empty($expirynotifyperiod)) {
            $expirynotifyperiod = 900; // Default 15 minutes.
        }
        
        $currenttime = strtotime(date('Y-m-d H:i', time()));
        
        // Get enrollments that expired within the notification period.
        $sql = "SELECT ue.*, e.courseid, e.id as instanceid, u.*, c.fullname as coursename
                FROM {user_enrolments} ue
                JOIN {enrol} e ON ue.enrolid = e.id
                JOIN {user} u ON ue.userid = u.id
                JOIN {course} c ON e.courseid = c.id
                WHERE e.enrol = :enrol
                  AND ue.timeend < :now
                  AND ue.timeend >= :mintime
                  AND ue.status = :suspended";
        
        $params = [
            'enrol' => 'nexuspay',
            'now' => $currenttime,
            'mintime' => $currenttime - $expirynotifyperiod,
            'suspended' => ENROL_USER_SUSPENDED
        ];
        
        $expiredenrolments = $DB->get_records_sql($sql, $params);
        
        if (empty($expiredenrolments)) {
            $trace->output('No recently expired enrollments to notify');
            return;
        }
        
        $trace->output('Sending notifications for ' . count($expiredenrolments) . ' expired enrollments');
        
        foreach ($expiredenrolments as $enrollment) {
            // Skip if user is deleted or suspended.
            if ($enrollment->deleted || $enrollment->suspended) {
                continue;
            }
            
            $trace->output("Sending expired notification to user {$enrollment->userid} for course {$enrollment->courseid}");
            
            // Force user language.
            $oldforcelang = force_current_language($enrollment->lang);
            
            // Prepare message data.
            $course = $DB->get_record('course', ['id' => $enrollment->courseid]);
            $context = \context_course::instance($enrollment->courseid);
            
            $a = new \stdClass();
            $a->fullname = fullname($enrollment);
            $a->course = format_string($course->fullname, true, ['context' => $context]);
            $a->payurl = $CFG->wwwroot . '/enrol/nexuspay/pay.php?id=' . $enrollment->instanceid . 
                         '&courseid=' . $enrollment->courseid;
            
            // Create message.
            $message = new \core\message\message();
            $message->component = 'enrol_nexuspay';
            $message->name = 'expiry_notification';
            $message->userfrom = \core_user::get_noreply_user();
            $message->userto = $enrollment;
            $message->subject = get_string('expiredmessagesubject', 'enrol_nexuspay');
            $message->fullmessage = get_string('expiredmessagebody', 'enrol_nexuspay', $a);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = '<p>' . nl2br($message->fullmessage) . '</p>';
            $message->notification = 1;
            $message->contexturl = $a->payurl;
            $message->contexturlname = get_string('renewenrolment', 'enrol_nexuspay');
            
            // Send message.
            $messageid = message_send($message);
            
            if ($messageid) {
                $trace->output("Notification sent successfully (message id: $messageid)");
            } else {
                $trace->output("Failed to send notification");
            }
            
            // Restore original language.
            force_current_language($oldforcelang);
        }
    }
}