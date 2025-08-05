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
 * @package    mod_intebchat
 * @category   upgrade
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_intebchat upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_intebchat_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2025021800) {
        // Add apitype field to intebchat table if it doesn't exist
        $table = new xmldb_table('intebchat');
        $field = new xmldb_field('apitype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'chat', 'showlabels');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add token tracking fields to log table
        $table = new xmldb_table('mod_intebchat_log');
        
        $field = new xmldb_field('prompttokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'airesponse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('completiontokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'prompttokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('totaltokens', XMLDB_TYPE_INTEGER, '10', null, null, null, '0', 'completiontokens');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Create token usage table
        $table = new xmldb_table('mod_intebchat_token_usage');

        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('tokensused', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('periodstart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('periodtype', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'day');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('user-period', XMLDB_INDEX_UNIQUE, ['userid', 'periodstart', 'periodtype']);
            $table->add_index('periodstart', XMLDB_INDEX_NOTUNIQUE, ['periodstart']);

            $dbman->create_table($table);
        }

        // Update existing records to have default apitype
        $DB->execute("UPDATE {intebchat} SET apitype = 'chat' WHERE apitype IS NULL OR apitype = ''");

        upgrade_mod_savepoint(true, 2025021800, 'intebchat');
    }

    if ($oldversion < 2025021900) {
        // Remove username field if it exists
        $table = new xmldb_table('intebchat');
        $field = new xmldb_field('username');
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025021900, 'intebchat');
    }

    if ($oldversion < 2025022000) {
        // Remove Azure-related fields
        $table = new xmldb_table('intebchat');
        
        // Remove resourcename field
        $field = new xmldb_field('resourcename');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Remove deploymentid field
        $field = new xmldb_field('deploymentid');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Remove apiversion field
        $field = new xmldb_field('apiversion');
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }
        
        // Update any existing records that have 'azure' as apitype to 'chat'
        $DB->execute("UPDATE {intebchat} SET apitype = 'chat' WHERE apitype = 'azure'");
        
        // Clean up any Azure settings from config
        unset_config('resourcename', 'mod_intebchat');
        unset_config('deploymentid', 'mod_intebchat');
        unset_config('apiversion', 'mod_intebchat');
        
        // Update the type config if it was set to 'azure'
        $currenttype = get_config('mod_intebchat', 'type');
        if ($currenttype === 'azure') {
            set_config('type', 'chat', 'mod_intebchat');
        }

        upgrade_mod_savepoint(true, 2025022000, 'intebchat');
    }

    if ($oldversion < 2025022100) {
        // Drop the mod_intebchat_usage table if it exists (removing cost tracking)
        $table = new xmldb_table('mod_intebchat_usage');
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2025022100, 'intebchat');
    }

    if ($oldversion < 2025030100) {
        // Create conversations table
        $table = new xmldb_table('mod_intebchat_conversations');
        
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('preview', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('threadid', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('messagecount', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('instanceid', XMLDB_KEY_FOREIGN, ['instanceid'], 'intebchat', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

            $table->add_index('instance-user', XMLDB_INDEX_NOTUNIQUE, ['instanceid', 'userid']);
            $table->add_index('timemodified', XMLDB_INDEX_NOTUNIQUE, ['timemodified']);

            $dbman->create_table($table);
        }

        // Add conversationid field to log table
        $table = new xmldb_table('mod_intebchat_log');
        $field = new xmldb_field('conversationid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'userid');
        
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            
            // Add foreign key
            $key = new xmldb_key('conversationid', XMLDB_KEY_FOREIGN, ['conversationid'], 'mod_intebchat_conversations', ['id']);
            $dbman->add_key($table, $key);
            
            // Add index
            $index = new xmldb_index('conversation-time', XMLDB_INDEX_NOTUNIQUE, ['conversationid', 'timecreated']);
            $dbman->add_index($table, $index);
        }

        // Migrate existing logs to conversations
        $instances = $DB->get_records('intebchat');
        foreach ($instances as $instance) {
            // Get unique users who have messages in this instance
            $users = $DB->get_records_sql("
                SELECT DISTINCT userid 
                FROM {mod_intebchat_log} 
                WHERE instanceid = :instanceid",
                ['instanceid' => $instance->id]
            );
            
            foreach ($users as $user) {
                // Create a default conversation for existing messages
                $conversation = new stdClass();
                $conversation->instanceid = $instance->id;
                $conversation->userid = $user->userid;
                $conversation->title = get_string('defaultconversation', 'mod_intebchat');
                $conversation->preview = '';
                $conversation->messagecount = 0;
                $conversation->timecreated = time();
                $conversation->timemodified = time();
                
                $conversationid = $DB->insert_record('mod_intebchat_conversations', $conversation);
                
                // Update existing logs to reference this conversation
                $DB->execute("
                    UPDATE {mod_intebchat_log} 
                    SET conversationid = :conversationid 
                    WHERE instanceid = :instanceid AND userid = :userid",
                    [
                        'conversationid' => $conversationid,
                        'instanceid' => $instance->id,
                        'userid' => $user->userid
                    ]
                );
                
                // Update conversation preview and message count
                $lastmessage = $DB->get_record_sql("
                    SELECT usermessage 
                    FROM {mod_intebchat_log} 
                    WHERE conversationid = :conversationid 
                    ORDER BY timecreated DESC 
                    LIMIT 1",
                    ['conversationid' => $conversationid]
                );
                
                if ($lastmessage) {
                    $conversation->preview = substr($lastmessage->usermessage, 0, 100);
                }
                
                $messagecount = $DB->count_records('mod_intebchat_log', ['conversationid' => $conversationid]);
                
                $DB->execute("
                    UPDATE {mod_intebchat_conversations} 
                    SET preview = :preview, messagecount = :messagecount 
                    WHERE id = :id",
                    [
                        'preview' => $conversation->preview,
                        'messagecount' => $messagecount,
                        'id' => $conversationid
                    ]
                );
            }
        }

        // Clean up old report permissions from roles
        $DB->delete_records('role_capabilities', ['capability' => 'mod/intebchat:viewallreports']);
        
        upgrade_mod_savepoint(true, 2025030100, 'intebchat');
    }

    if ($oldversion < 2025030101) {
        $table = new xmldb_table('intebchat');
        
        // Add enableaudio field
        $field = new xmldb_field('enableaudio', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'persistconvo');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add audiomode field
        $field = new xmldb_field('audiomode', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'text', 'enableaudio');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_mod_savepoint(true, 2025030101, 'intebchat');
    }

    return true;
}