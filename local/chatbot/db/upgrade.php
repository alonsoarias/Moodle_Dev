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
 * Upgrade script for local_chatbot
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function
 */
function xmldb_local_chatbot_upgrade($oldversion) {
    global $DB;
    
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2025012500) {
        
        // Define table local_chatbot_intents to be created if not exists
        $table = new xmldb_table('local_chatbot_intents');
        
        if (!$dbman->table_exists($table)) {
            // Adding fields
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
            $table->add_field('display_name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('parent_intent', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('training_phrases', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('required_entities', XMLDB_TYPE_TEXT, null, null, null, null, null);
            $table->add_field('action', XMLDB_TYPE_CHAR, '100', null, null, null, null);
            $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            
            // Adding keys
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            
            // Adding indexes
            $table->add_index('name', XMLDB_INDEX_UNIQUE, ['name']);
            $table->add_index('parent', XMLDB_INDEX_NOTUNIQUE, ['parent_intent']);
            
            // Create the table
            $dbman->create_table($table);
        }
        
        // Add new fields to existing tables if they don't exist
        $table = new xmldb_table('local_chatbot_responses');
        
        // Add intent field if it doesn't exist
        $field = new xmldb_field('intent', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'id');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add patterns field if it doesn't exist
        $field = new xmldb_field('patterns', XMLDB_TYPE_TEXT, null, null, null, null, null, 'keywords');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add response_variations field if it doesn't exist
        $field = new xmldb_field('response_variations', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add success_rate field if it doesn't exist
        $field = new xmldb_field('success_rate', XMLDB_TYPE_NUMBER, '5, 2', null, null, null, null, 'usage_count');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Add usage_count field if it doesn't exist
        $field = new xmldb_field('usage_count', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'enabled');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        // Initialize default intents
        require_once(__DIR__ . '/../lib.php');
        local_chatbot_init_intents();
        
        // Chatbot savepoint reached
        upgrade_plugin_savepoint(true, 2025012500, 'local', 'chatbot');
    }
    
    return true;
}
