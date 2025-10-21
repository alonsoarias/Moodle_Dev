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
 * Privacy provider for local_educambot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Implements the privacy API for local_educambot.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe the types of data stored by the plugin.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_educambot_log', [
            'userid' => 'privacy:metadata:log:userid',
            'sessionid' => 'privacy:metadata:log:sessionid',
            'question' => 'privacy:metadata:log:question',
            'response' => 'privacy:metadata:log:response',
            'ruleid' => 'privacy:metadata:log:ruleid',
            'confidence' => 'privacy:metadata:log:confidence',
            'page' => 'privacy:metadata:log:page',
            'timecreated' => 'privacy:metadata:log:timecreated',
        ], 'privacy:metadata:log');

        $collection->add_database_table('local_educambot_unanswered', [
            'userid' => 'privacy:metadata:unanswered:userid',
            'question' => 'privacy:metadata:unanswered:question',
            'page' => 'privacy:metadata:unanswered:page',
            'timecreated' => 'privacy:metadata:unanswered:timecreated',
        ], 'privacy:metadata:unanswered');

        return $collection;
    }

    /**
     * Get the list of contexts which contain user information.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        global $DB;

        $logexists = $DB->record_exists('local_educambot_log', ['userid' => $userid]);
        $unansweredexists = $DB->record_exists('local_educambot_unanswered', ['userid' => $userid]);

        if ($logexists || $unansweredexists) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Export user data for approved contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $systemcontext = context_system::instance();

        if (!$contextlist->has_context($systemcontext)) {
            return;
        }

        $logs = $DB->get_records('local_educambot_log', ['userid' => $userid], 'timecreated ASC');
        if ($logs) {
            $export = [];
            foreach ($logs as $log) {
                $export[] = [
                    'question' => $log->question,
                    'response' => $log->response,
                    'confidence' => $log->confidence,
                    'page' => $log->page,
                    'timecreated' => transform::datetime($log->timecreated),
                ];
            }
            writer::with_context($systemcontext)->export_data(['Educam Bot', 'Log'], (object)['interactions' => $export]);
        }

        $unanswered = $DB->get_records('local_educambot_unanswered', ['userid' => $userid], 'timecreated ASC');
        if ($unanswered) {
            $export = [];
            foreach ($unanswered as $item) {
                $export[] = [
                    'question' => $item->question,
                    'page' => $item->page,
                    'timecreated' => transform::datetime($item->timecreated),
                ];
            }
            writer::with_context($systemcontext)->export_data(['Educam Bot', 'Unanswered'], (object)['questions' => $export]);
        }
    }

    /**
     * Delete data for all users in the supplied context.
     *
     * @param context $context
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_system) {
            return;
        }

        $DB->delete_records('local_educambot_log');
        $DB->delete_records('local_educambot_unanswered');
    }

    /**
     * Delete data for specific users.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (!$contextlist->count()) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        $contextids = $contextlist->get_contextids();

        if (!in_array(context_system::instance()->id, $contextids, true)) {
            return;
        }

        $DB->delete_records('local_educambot_log', ['userid' => $userid]);
        $DB->delete_records('local_educambot_unanswered', ['userid' => $userid]);
    }

    /**
     * Delete data for multiple users within a single context.
     *
     * @param context $context
     * @param array $userids
     */
    public static function delete_data_for_users(context $context, array $userids): void {
        global $DB;

        if (!$context instanceof context_system || empty($userids)) {
            return;
        }

        list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_educambot_log', "userid {$insql}", $params);
        $DB->delete_records_select('local_educambot_unanswered', "userid {$insql}", $params);
    }
}
