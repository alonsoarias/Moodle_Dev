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
 * Database upgrade script for local_educambot.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade the local_educambot plugin.
 *
 * @param int $oldversion The old version of the plugin.
 * @return bool
 */
function xmldb_local_educambot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // v2.0.0 - Add feedback table and helpfulcount/nothelpfulcount to rules.
    if ($oldversion < 2025121200) {

        // Add helpfulcount field to rule table.
        $table = new xmldb_table('local_educambot_rule');
        $field = new xmldb_field('helpfulcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'langparent');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add nothelpfulcount field to rule table.
        $field = new xmldb_field('nothelpfulcount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'helpfulcount');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create feedback table.
        $table = new xmldb_table('local_educambot_feedback');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('helpful', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_educambot_rule', ['id']);

        $table->add_index('userid_ruleid_idx', XMLDB_INDEX_NOTUNIQUE, ['userid', 'ruleid']);
        $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121200, 'local', 'educambot');
    }

    // v2.2.0 - Enable mascot by default on existing themes.
    if ($oldversion < 2025121320) {

        // Get all themes that don't have mascot enabled.
        $themes = $DB->get_records('local_educambot_theme');

        foreach ($themes as $theme) {
            // Only update if mascot is not already configured.
            if (empty($theme->mascotenabled) && (empty($theme->mascottype) || $theme->mascottype === 'none')) {
                $update = new stdClass();
                $update->id = $theme->id;
                $update->mascotenabled = 1;
                $update->mascottype = 'robot'; // Default mascot.
                $update->timemodified = time();
                $DB->update_record('local_educambot_theme', $update);
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121320, 'local', 'educambot');
    }

    // v3.0.0 - Add conversation context table for improved bot behavior.
    if ($oldversion < 2025121940) {

        // Create conversation context table.
        $table = new xmldb_table('local_educambot_context');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', null, null, null, '1');
        $table->add_field('state', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('last_topic', XMLDB_TYPE_CHAR, '100', null, null, null, null);
        $table->add_field('last_ruleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('last_ruleid_fk', XMLDB_KEY_FOREIGN, ['last_ruleid'], 'local_educambot_rule', ['id']);

        $table->add_index('userid_sessionid_idx', XMLDB_INDEX_UNIQUE, ['userid', 'sessionid']);
        $table->add_index('timemodified_idx', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add priority field to rule table for manual rule prioritization.
        $ruletable = new xmldb_table('local_educambot_rule');
        $priorityfield = new xmldb_field('priority', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'nothelpfulcount');
        if (!$dbman->field_exists($ruletable, $priorityfield)) {
            $dbman->add_field($ruletable, $priorityfield);
        }

        // Add needs_review field for flagging problematic rules.
        $reviewfield = new xmldb_field('needs_review', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'priority');
        if (!$dbman->field_exists($ruletable, $reviewfield)) {
            $dbman->add_field($ruletable, $reviewfield);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121940, 'local', 'educambot');
    }

    // v3.0.1 - Add roles and context fields to shortcuts for role-based filtering.
    if ($oldversion < 2025121942) {

        $table = new xmldb_table('local_educambot_shortcut');

        // Add roles field.
        $rolesfield = new xmldb_field('roles', XMLDB_TYPE_TEXT, null, null, null, null, null, 'icon');
        if (!$dbman->field_exists($table, $rolesfield)) {
            $dbman->add_field($table, $rolesfield);
        }

        // Add context field.
        $contextfield = new xmldb_field('context', XMLDB_TYPE_CHAR, '20', null, null, null, 'any', 'roles');
        if (!$dbman->field_exists($table, $contextfield)) {
            $dbman->add_field($table, $contextfield);
        }

        // Update existing shortcuts with default roles and context based on actiontype.
        $shortcuts = $DB->get_records('local_educambot_shortcut');
        foreach ($shortcuts as $shortcut) {
            $update = new stdClass();
            $update->id = $shortcut->id;
            $update->timemodified = time();

            // Set default roles and context based on actiontype.
            switch ($shortcut->actiontype) {
                case 'assignments':
                case 'grades':
                case 'progress':
                case 'teachers':
                    $update->roles = 'student';
                    $update->context = 'course';
                    break;
                case 'participants':
                case 'teacher_grades':
                    $update->roles = 'teacher,editingteacher,manager,siteadmin';
                    $update->context = 'course';
                    break;
                case 'admin_users':
                case 'admin_reports':
                case 'admin_backup':
                    $update->roles = 'manager,siteadmin';
                    $update->context = 'any';
                    break;
                case 'admin_settings':
                case 'admin_plugins':
                case 'admin_security':
                    $update->roles = 'siteadmin';
                    $update->context = 'any';
                    break;
                case 'admin_courses':
                    $update->roles = 'manager,siteadmin,coursecreator';
                    $update->context = 'any';
                    break;
                default:
                    // General shortcuts available to all.
                    $update->roles = null;
                    $update->context = 'any';
                    break;
            }

            $DB->update_record('local_educambot_shortcut', $update);
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121942, 'local', 'educambot');
    }

    // Version 2025121945: Add action field to options table and reload options.
    if ($oldversion < 2025121945) {
        $table = new xmldb_table('local_educambot_option');

        // Add action field.
        $actionfield = new xmldb_field('action', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'text');
        if (!$dbman->field_exists($table, $actionfield)) {
            $dbman->add_field($table, $actionfield);
        }

        // Delete all existing options (they have wrong field values).
        $DB->delete_records('local_educambot_option');

        // Reload options from navigation.json.
        $datapath = __DIR__ . '/data/';
        $navfile = $datapath . 'navigation.json';

        if (file_exists($navfile)) {
            $json = file_get_contents($navfile);
            $data = json_decode($json, true);

            if ($data && isset($data['rules'])) {
                foreach ($data['rules'] as $rule) {
                    // Find the rule in database by pattern.
                    $dbrule = $DB->get_record('local_educambot_rule', ['pattern' => $rule['pattern']]);

                    if ($dbrule && isset($rule['options']) && is_array($rule['options'])) {
                        $sortorder = 1;
                        foreach ($rule['options'] as $option) {
                            $optrecord = new stdClass();
                            $optrecord->ruleid = $dbrule->id;
                            $optrecord->text = $option['text'];
                            $optrecord->action = $option['action'] ?? '';
                            $optrecord->icon = $option['icon'] ?? '';
                            $optrecord->targetruleid = null;
                            $optrecord->sortorder = $sortorder++;
                            $optrecord->enabled = 1;

                            $DB->insert_record('local_educambot_option', $optrecord);
                        }
                    }
                }
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121945, 'local', 'educambot');
    }

    // Version 2025121946: Add fallback navigation rules for menu options.
    if ($oldversion < 2025121946) {
        $datapath = __DIR__ . '/data/';
        $navfile = $datapath . 'navigation.json';

        if (file_exists($navfile)) {
            $json = file_get_contents($navfile);
            $data = json_decode($json, true);

            if ($data && isset($data['rules'])) {
                // Get the general category ID.
                $generalcat = $DB->get_record('local_educambot_category', ['name' => 'General']);
                $categoryid = $generalcat ? $generalcat->id : null;

                $now = time();

                // New fallback rules to add.
                $newruleids = [
                    'tasks_fallback', 'grades_fallback', 'messages_fallback',
                    'profile_fallback', 'courses_fallback', 'calendar_fallback'
                ];

                foreach ($data['rules'] as $rule) {
                    // Only process new fallback rules.
                    if (!in_array($rule['id'], $newruleids)) {
                        continue;
                    }

                    // Check if rule already exists.
                    if ($DB->record_exists('local_educambot_rule', ['pattern' => $rule['pattern']])) {
                        continue;
                    }

                    // Convert keywords array to newline-separated string.
                    $keywords = is_array($rule['keywords']) ? implode("\n", $rule['keywords']) : $rule['keywords'];
                    $tags = is_array($rule['tags']) ? implode(', ', $rule['tags']) : ($rule['tags'] ?? '');

                    $record = new stdClass();
                    $record->categoryid = $categoryid;
                    $record->pattern = $rule['pattern'];
                    $record->keywords = $keywords;
                    $record->response = $rule['response'];
                    $record->tags = $tags;
                    $record->enabled = 1;
                    $record->showoptions = isset($rule['showoptions']) && $rule['showoptions'] ? 1 : 0;
                    $record->timecreated = $now;
                    $record->timemodified = $now;

                    $ruleid = $DB->insert_record('local_educambot_rule', $record);

                    // Insert options for this rule.
                    if (isset($rule['options']) && is_array($rule['options'])) {
                        $sortorder = 1;
                        foreach ($rule['options'] as $option) {
                            $optrecord = new stdClass();
                            $optrecord->ruleid = $ruleid;
                            $optrecord->text = $option['text'];
                            $optrecord->action = $option['action'] ?? '';
                            $optrecord->icon = $option['icon'] ?? '';
                            $optrecord->targetruleid = null;
                            $optrecord->sortorder = $sortorder++;
                            $optrecord->enabled = 1;

                            $DB->insert_record('local_educambot_option', $optrecord);
                        }
                    }
                }
            }
        }

        // Savepoint reached.
        upgrade_plugin_savepoint(true, 2025121946, 'local', 'educambot');
    }

    return true;
}
