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
 * Upgrade steps for the local_chatbot plugin.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_chatbot upgrade steps.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_chatbot_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024052400) {
        $table = new xmldb_table('local_chatbot_logs');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('sessionid', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
            $table->add_field('message', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('intent', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('responsetime', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('metadata', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('feedback', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('sessionid', XMLDB_INDEX_NOTUNIQUE, ['sessionid']);
            $table->add_index('timecreated', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2024052400, 'local', 'chatbot');
    }

    if ($oldversion < 2025012503) {
        if (get_config('local_chatbot', 'enabled') === null) {
            set_config('enabled', 1, 'local_chatbot');
        }

        upgrade_plugin_savepoint(true, 2025012503, 'local', 'chatbot');
    }

    if ($oldversion < 2025020100) {
        $table = new xmldb_table('local_chatbot_intents');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('keywords', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('isfallback', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
            $table->add_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
            $table->add_index('fallback', XMLDB_INDEX_NOTUNIQUE, ['isfallback']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_chatbot_suggestions');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('text', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('mode', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'message');
            $table->add_field('target', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('icon', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
            $table->add_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

            $dbman->create_table($table);
        }

        $table = new xmldb_table('local_chatbot_quickacts');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('actionkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'navigate');
            $table->add_field('payload', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('description', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('icon', XMLDB_TYPE_CHAR, '20', null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('enabled', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
            $table->add_index('sortorder', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);
            $table->add_index('actionkey', XMLDB_INDEX_UNIQUE, ['actionkey']);

            $dbman->create_table($table);
        }

        $time = time();

        if (!$DB->count_records('local_chatbot_intents')) {
            $intents = [
                [
                    'name' => 'Greeting',
                    'keywords' => "hola\nhello\nhi\nbuenos\nbuenas",
                    'response' => get_string('chatbot_response_greeting', 'local_chatbot'),
                    'isfallback' => 0,
                    'sortorder' => 10,
                ],
                [
                    'name' => 'Help',
                    'keywords' => "ayuda\nhelp\nassist\nsoporte",
                    'response' => get_string('chatbot_response_help', 'local_chatbot'),
                    'isfallback' => 0,
                    'sortorder' => 20,
                ],
                [
                    'name' => 'Courses',
                    'keywords' => "curso\ncourses\nclase\nsubject",
                    'response' => get_string('chatbot_response_courses', 'local_chatbot'),
                    'isfallback' => 0,
                    'sortorder' => 30,
                ],
                [
                    'name' => 'Grades',
                    'keywords' => "nota\ncalificación\ncalificacion\ngrade",
                    'response' => get_string('chatbot_response_grades', 'local_chatbot'),
                    'isfallback' => 0,
                    'sortorder' => 40,
                ],
                [
                    'name' => 'Fallback',
                    'keywords' => '',
                    'response' => (string)(get_config('local_chatbot', 'default_nomatch')
                        ?: get_string('default_nomatch', 'local_chatbot')),
                    'isfallback' => 1,
                    'sortorder' => 99,
                ],
            ];

            foreach ($intents as $intent) {
                $record = (object) $intent;
                $record->enabled = 1;
                $record->timecreated = $time;
                $record->timemodified = $time;
                $DB->insert_record('local_chatbot_intents', $record);
            }
        }

        if (!$DB->count_records('local_chatbot_quickacts')) {
            $quickactions = [
                [
                    'name' => get_string('chatbot_action_profile', 'local_chatbot'),
                    'actionkey' => 'navigate_profile',
                    'type' => 'navigate',
                    'payload' => '/user/profile.php?id={userid}',
                    'description' => get_string('chatbot_action_profile_desc', 'local_chatbot'),
                    'icon' => '👤',
                    'sortorder' => 10,
                ],
                [
                    'name' => get_string('chatbot_action_calendar', 'local_chatbot'),
                    'actionkey' => 'navigate_calendar',
                    'type' => 'navigate',
                    'payload' => '/calendar/view.php?view=month',
                    'description' => get_string('chatbot_action_calendar_desc', 'local_chatbot'),
                    'icon' => '🗓️',
                    'sortorder' => 20,
                ],
                [
                    'name' => get_string('chatbot_action_support', 'local_chatbot'),
                    'actionkey' => 'server_support',
                    'type' => 'server',
                    'payload' => get_string('chatbot_response_support', 'local_chatbot'),
                    'description' => get_string('chatbot_action_support_desc', 'local_chatbot'),
                    'icon' => '🆘',
                    'sortorder' => 30,
                ],
            ];

            foreach ($quickactions as $action) {
                $record = (object) $action;
                $record->enabled = 1;
                $record->timecreated = $time;
                $record->timemodified = $time;
                $DB->insert_record('local_chatbot_quickacts', $record);
            }
        }

        if (!$DB->count_records('local_chatbot_suggestions')) {
            $suggestions = [
                [
                    'text' => get_string('chatbot_suggestion_courses', 'local_chatbot'),
                    'mode' => 'message',
                    'target' => get_string('chatbot_suggestion_courses', 'local_chatbot'),
                    'icon' => '📚',
                    'sortorder' => 10,
                ],
                [
                    'text' => get_string('chatbot_suggestion_grades', 'local_chatbot'),
                    'mode' => 'message',
                    'target' => get_string('chatbot_suggestion_grades', 'local_chatbot'),
                    'icon' => '📊',
                    'sortorder' => 20,
                ],
                [
                    'text' => get_string('chatbot_suggestion_support', 'local_chatbot'),
                    'mode' => 'action',
                    'target' => 'server_support',
                    'icon' => '🆘',
                    'sortorder' => 30,
                ],
            ];

            foreach ($suggestions as $suggestion) {
                $record = (object) $suggestion;
                $record->enabled = 1;
                $record->timecreated = $time;
                $record->timemodified = $time;
                $DB->insert_record('local_chatbot_suggestions', $record);
            }
        }

        upgrade_plugin_savepoint(true, 2025020100, 'local', 'chatbot');
    }

    return true;
}

