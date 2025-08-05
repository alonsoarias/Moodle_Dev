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
 * Privacy API Provider
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat\privacy;

use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\contextlist;
use \core_privacy\local\request\approved_contextlist;
use \core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;

defined('MOODLE_INTERNAL') || die();

class provider implements 
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'mod_intebchat_log',
             [
                'userid' => 'privacy:metadata:intebchat_log:userid',
                'instanceid' => 'privacy:metadata:intebchat_log:instanceid',
                'usermessage' => 'privacy:metadata:intebchat_log:usermessage',
                'airesponse' => 'privacy:metadata:intebchat_log:airesponse',
                'timecreated' => 'privacy:metadata:intebchat_log:timecreated'
             ],
            'privacy:metadata:intebchat_log'
        );
    
        return $collection;
    }

    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        
        $sql = "SELECT DISTINCT ctx.id
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {intebchat} oc ON oc.id = cm.instance
                  JOIN {mod_intebchat_log} ocl ON ocl.instanceid = oc.id
                 WHERE ocl.userid = :userid";
        
        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'intebchat',
            'userid' => $userid
        ];
        
        $contextlist->add_from_sql($sql, $params);
        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        
        if (!$context instanceof \context_module) {
            return;
        }

        $sql = "SELECT ocl.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {intebchat} oc ON oc.id = cm.instance
                  JOIN {mod_intebchat_log} ocl ON ocl.instanceid = oc.id
                 WHERE cm.id = :cmid";

        $params = [
            'modname' => 'intebchat',
            'cmid' => $context->instanceid
        ];

        $userlist->add_from_sql('userid', $sql, $params);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $user = $contextlist->get_user();
            $userid = $user->id;

            // Get the course module.
            $cm = get_coursemodule_from_id('intebchat', $context->instanceid);
            if (!$cm) {
                continue;
            }

            // Get messages.
            $sql = "SELECT id, userid, usermessage, airesponse, timecreated 
                    FROM {mod_intebchat_log} 
                    WHERE instanceid = :instanceid AND userid = :userid
                    ORDER BY timecreated";
            
            $params = ['instanceid' => $cm->instance, 'userid' => $userid];
            $records = $DB->get_records_sql($sql, $params);

            if (!empty($records)) {
                $messages = new \stdClass();
                foreach ($records as $message) {
                    $messages->{$message->id} = [
                        "userid" => $message->userid,
                        "usermessage" => $message->usermessage,
                        "airesponse" => $message->airesponse,
                        "timecreated" => transform::datetime($message->timecreated)
                    ];
                }
        
                writer::with_context($context)->export_data(
                    [get_string('privacy:chatmessagespath', 'mod_intebchat')],
                    $messages
                );
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('intebchat', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('mod_intebchat_log', ['instanceid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        
        $userid = $contextlist->get_user()->id;
        
        foreach ($contextlist as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('intebchat', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('mod_intebchat_log', ['instanceid' => $cm->instance, 'userid' => $userid]);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        
        $context = $userlist->get_context();
        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('intebchat', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        
        $params = ['instanceid' => $cm->instance] + $userparams;
        $DB->delete_records_select('mod_intebchat_log', "instanceid = :instanceid AND userid $usersql", $params);
    }
}