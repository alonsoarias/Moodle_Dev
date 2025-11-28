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
 * Plugin upgrade steps are defined here.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_educambot upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_educambot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2025112004) {
        // Define table local_educambot_log to be created.
        $table = new xmldb_table('local_educambot_log');

        // Adding fields to table local_educambot_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('confidence', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null);
        $table->add_field('matched', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_educambot_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_educambot_rule', ['id']);

        // Adding indexes to table local_educambot_log.
        // Note: userid and ruleid already have indexes from foreign keys.
        $table->add_index('matched_idx', XMLDB_INDEX_NOTUNIQUE, ['matched']);
        $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_educambot_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112004, 'local', 'educambot');
    }

    if ($oldversion < 2025112005) {
        // Add showoptions field to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');
        $field = new xmldb_field('showoptions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'enabled');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table local_educambot_option to be created.
        $table = new xmldb_table('local_educambot_option');

        // Adding fields to table local_educambot_option.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('text', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetruleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table local_educambot_option.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_educambot_rule', ['id']);
        $table->add_key('targetruleid_fk', XMLDB_KEY_FOREIGN, ['targetruleid'], 'local_educambot_rule', ['id']);

        // Adding indexes to table local_educambot_option.
        // Note: ruleid already has an index from foreign key.
        $table->add_index('sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table for local_educambot_option.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112005, 'local', 'educambot');
    }

    if ($oldversion < 2025112006) {
        // Define table local_educambot_category to be created.
        $table = new xmldb_table('local_educambot_category');

        // Adding fields to table local_educambot_category.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_educambot_category.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('parent_fk', XMLDB_KEY_FOREIGN, ['parent'], 'local_educambot_category', ['id']);

        // Adding indexes to table local_educambot_category.
        // Note: parent already has an index from foreign key.
        $table->add_index('sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table for local_educambot_category.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add categoryid field to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add categoryid index.
        $index = new xmldb_index('categoryid_idx', XMLDB_INDEX_NOTUNIQUE, ['categoryid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add tags field to local_educambot_rule table.
        $field = new xmldb_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112006, 'local', 'educambot');
    }

    return true;
}
