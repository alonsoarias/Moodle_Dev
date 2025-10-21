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
 * Upgrade steps for local_educambot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Executes upgrade steps for the plugin.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_educambot_upgrade(int $oldversion): bool {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024060100) {
        // Define table local_educambot_topic to be created.
        $table = new xmldb_table('local_educambot_topic');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('parentfk', XMLDB_KEY_FOREIGN, ['parentid'], 'local_educambot_topic', ['id']);

            $dbman->create_table($table);
        }

        // Define table local_educambot_knowledge to be created.
        $table = new xmldb_table('local_educambot_knowledge');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('summary', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('content', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('contentformat', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('type', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('externalurl', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('updatedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

            $dbman->create_table($table);
        }

        // Define table local_educambot_kn_topic to be created.
        $table = new xmldb_table('local_educambot_kn_topic');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('knowledgeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('topicid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('knowledgefk', XMLDB_KEY_FOREIGN, ['knowledgeid'], 'local_educambot_knowledge', ['id']);
            $table->add_key('topicfk', XMLDB_KEY_FOREIGN, ['topicid'], 'local_educambot_topic', ['id']);
            $table->add_index('knowledgetopic_idx', XMLDB_INDEX_UNIQUE, ['knowledgeid', 'topicid']);

            $dbman->create_table($table);
        }

        // Define table local_educambot_relation to be created.
        $table = new xmldb_table('local_educambot_relation');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('sourceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('targetid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('relationtype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('sourcefk', XMLDB_KEY_FOREIGN, ['sourceid'], 'local_educambot_knowledge', ['id']);
            $table->add_key('targetfk', XMLDB_KEY_FOREIGN, ['targetid'], 'local_educambot_knowledge', ['id']);
            $table->add_index('relation_idx', XMLDB_INDEX_NOTUNIQUE, ['sourceid', 'targetid']);

            $dbman->create_table($table);
        }

        // Define table local_educambot_kn_context to be created.
        $table = new xmldb_table('local_educambot_kn_context');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('knowledgeid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
            $table->add_field('role', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('pagecontext', XMLDB_TYPE_CHAR, '255', null, null, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('knowledgefk', XMLDB_KEY_FOREIGN, ['knowledgeid'], 'local_educambot_knowledge', ['id']);
            $table->add_index('course_idx', XMLDB_INDEX_NOTUNIQUE, ['courseid']);

            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2024060100, 'local', 'educambot');
    }

    return true;
}
