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
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute local_educambot upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_educambot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Upgrade to version 2025103004 - Add common student questions seed.
    if ($oldversion < 2025103004) {
        // Execute the common questions seed to populate essential Q&A.
        require_once(__DIR__ . '/../classes/local/setup/common_questions_seed.php');

        try {
            $result = \local_educambot\local\setup\common_questions_seed::seed();
            mtrace('  → Common questions seed executed: ' . $result['created'] . ' created, ' .
                   $result['updated'] . ' updated (total: ' . $result['total'] . ')');
        } catch (\Throwable $e) {
            mtrace('  → WARNING: Common questions seed failed: ' . $e->getMessage());
            // Don't fail the upgrade if seed fails - admin can run it manually.
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025103004, 'local', 'educambot');
    }

    // Add local_educambot_feedback table if it doesn't exist (from v2.1.0).
    if ($oldversion < 2025103003) {
        $table = new xmldb_table('local_educambot_feedback');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('logid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('helpful', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('comment', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('logid_fk', XMLDB_KEY_FOREIGN, ['logid'], 'local_educambot_log', ['id']);

            $table->add_index('helpful_idx', XMLDB_INDEX_NOTUNIQUE, ['helpful']);
            $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

            $dbman->create_table($table);
            mtrace('  → Created local_educambot_feedback table');
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025103003, 'local', 'educambot');
    }

    // Upgrade to version 2025103005 - Ensure common questions seed is executed.
    // This fixes installations that upgraded from v2025103004 where seed wasn't run.
    if ($oldversion < 2025103005) {
        require_once(__DIR__ . '/../classes/local/setup/common_questions_seed.php');

        // Check if rules exist - if not, definitely run seed.
        $rulescount = $DB->count_records('local_educambot_rule', ['enabled' => 1]);

        if ($rulescount == 0) {
            mtrace('  → No enabled rules found. Executing common questions seed...');
            try {
                $result = \local_educambot\local\setup\common_questions_seed::seed();
                mtrace('  → Common questions seed executed: ' . $result['created'] . ' created, ' .
                       $result['updated'] . ' updated (total: ' . $result['total'] . ')');

                // Purge cache to ensure fresh data.
                \cache::make('local_educambot', 'rules')->purge();
                mtrace('  → Rules cache purged');
            } catch (\Throwable $e) {
                mtrace('  → WARNING: Common questions seed failed: ' . $e->getMessage());
            }
        } else {
            mtrace('  → Skipping seed: ' . $rulescount . ' enabled rules already exist');
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025103005, 'local', 'educambot');
    }

    return true;
}
