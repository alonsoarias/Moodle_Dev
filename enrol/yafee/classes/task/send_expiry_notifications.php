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
 * @package   enrol_yafee
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_yafee\task;

/**
 * Send expiry notifications task.
 *
 * @package   enrol_yafee
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_expiry_notifications extends \core\task\scheduled_task {
    /**
     * Name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('sendexpirynotificationstask', 'enrol_yafee');
    }

    /**
     * Run task for sending expiry notifications.
     */
    public function execute() {
        global $DB, $CFG;

        $enrol = enrol_get_plugin('yafee');
        $trace = new \text_progress_trace();
        $enrol->send_expiry_notifications($trace);

        // Extra expire notification.
        mtrace('start extra');
        $expirynotifyperiod = get_config('enrol_yafee', 'expirynotifyperiod');
        $ctime = strtotime(date('Y-m-d H:i', time()));

        $enroltx = $DB->get_records_sql('select ue.*,e.courseid from {user_enrolments} ue
            left join {enrol} e on ue.enrolid=e.id
            WHERE e.enrol=? AND ue.timeend<? AND ue.timeend>=?', [ 'yafee', $ctime, $ctime - $expirynotifyperiod ]);

        foreach ($enroltx as $data) {
            // Get user data.
            if (!$user = $DB->get_record('user', ['id' => $data->userid])) {
                mtrace("$data->userid not found");
                continue;
            }

            mtrace("$user->id $user->email $data->courseid expired");

            $oldforcelang = force_current_language($user->lang);

            // Make message.
            $message = new \core\message\message();
            $message->component = 'enrol_yafee';
            $message->name      = 'expiry_notification'; // The notification name from message.php.
            $message->userfrom  = \core_user::get_noreply_user(); // If the message is 'from' a specific user you can set them here.
            $message->userto    = \core_user::get_user($data->userid);
            $message->subject   = get_string('expiredmessagesubject', 'enrol_yafee');

            if (!$cs = $DB->get_record('course', ['id' => $data->courseid])) {
                mtrace("$data->courseid not found");
                continue;
            }

            $context = \context_course::instance($data->courseid);

            // Set the object with all informations to notify the user.
            $a = (object)[
                'firstname' => $user->firstname,
                'fullname'  => fullname($user),
                'payurl'       => $CFG->wwwroot . '/enrol/yafee/pay.php?id=' . $data->enrolid .
                               '&courseid=' . $data->courseid,
                'course'    => format_string($cs->fullname, true, ['context' => $context]),
            ];

            $messagebody = get_string('expiredmessagebody', 'enrol_yafee', $a);

            $message->fullmessage       = $messagebody;
            $message->fullmessageformat = FORMAT_MARKDOWN;
            $message->fullmessagehtml   = "<p>$messagebody</p>";
            $message->notification      = 1; // Because this is a notification generated from Moodle, not a user-to-user message.
            $message->contexturl        = ''; // A relevant URL for the notification.
            $message->contexturlname    = ''; // Link title explaining where users get to for the contexturl.
            $content = ['*' => ['header' => '', 'footer' => '']]; // Extra content for specific processor.
            $message->set_additional_content('email', $content);

            // Actually send the message.
            message_send($message);

            force_current_language($oldforcelang);
        }
    }
}
