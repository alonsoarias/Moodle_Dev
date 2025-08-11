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
 * External API for mod_intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');
require_once($CFG->dirroot . '/mod/intebchat/locallib.php');

class external extends \external_api
{

    /**
     * Create conversation parameters
     * @return external_function_parameters
     */
    public static function create_conversation_parameters()
    {
        return new \external_function_parameters([
            'instanceid' => new \external_value(PARAM_INT, 'Instance ID'),
        ]);
    }

    /**
     * Create a new conversation
     * @param int $instanceid
     * @return array
     */
    public static function create_conversation($instanceid)
    {
        global $USER, $DB;

        $params = self::validate_parameters(self::create_conversation_parameters(), [
            'instanceid' => $instanceid
        ]);

        // Validate instance exists
        $instance = $DB->get_record('intebchat', ['id' => $instanceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/intebchat:view', $context);

        // Create conversation
        $conversationid = intebchat_create_conversation($instanceid, $USER->id);

        return [
            'conversationid' => $conversationid,
            'title' => get_string('newconversation', 'mod_intebchat'),
            'preview' => '',
            'lastmessage' => time()
        ];
    }

    /**
     * Create conversation return
     * @return external_single_structure
     */
    public static function create_conversation_returns()
    {
        return new \external_single_structure([
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
            'title' => new \external_value(PARAM_TEXT, 'Conversation title'),
            'preview' => new \external_value(PARAM_TEXT, 'Preview text'),
            'lastmessage' => new \external_value(PARAM_INT, 'Last message timestamp'),
        ]);
    }

    /**
     * Load conversation parameters
     * @return external_function_parameters
     */
    public static function load_conversation_parameters()
    {
        return new \external_function_parameters([
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
            'instanceid' => new \external_value(PARAM_INT, 'Instance ID'),
        ]);
    }

    /**
     * Load conversation messages
     * @param int $conversationid
     * @param int $instanceid
     * @return array
     */
    public static function load_conversation($conversationid, $instanceid)
    {
        global $USER, $DB;

        try {
            $params = self::validate_parameters(self::load_conversation_parameters(), [
                'conversationid' => $conversationid,
                'instanceid' => $instanceid
            ]);

            // Validate instance and context
            $instance = $DB->get_record('intebchat', ['id' => $instanceid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);

            self::validate_context($context);
            require_capability('mod/intebchat:view', $context);

            // Check if user can view this conversation
            if (!intebchat_can_view_conversation($conversationid, $USER->id, $context)) {
                throw new \moodle_exception('nopermission', 'mod_intebchat');
            }

            // Get conversation
            $conversation = $DB->get_record('intebchat_conversations', ['id' => $conversationid], '*', MUST_EXIST);

            // Get messages using the fixed function
            $messages = intebchat_get_conversation_messages($conversationid);

            $formattedmessages = [];
            foreach ($messages as $msg) {
                // Add user message
                $formattedmessages[] = [
                    'id' => (string)$msg->id,
                    'role' => 'user',
                    'message' => $msg->usermessage,
                    'timestamp' => (int)$msg->timecreated
                ];

                // Add AI response if exists
                if (!empty($msg->airesponse)) {
                    $formattedmessages[] = [
                        'id' => $msg->id . '_response',
                        'role' => 'assistant',
                        'message' => format_text($msg->airesponse, FORMAT_MARKDOWN, ['context' => $context]),
                        'timestamp' => (int)$msg->timecreated
                    ];
                }
            }

            $result = [
                'conversationid' => (int)$conversation->id,
                'title' => $conversation->title,
                'messages' => $formattedmessages
            ];

            // Incluir threadId si existe
            if (!empty($conversation->threadid)) {
                $result['threadId'] = $conversation->threadid;
            }

            return $result;
        } catch (\Exception $e) {
            debugging('Error loading conversation: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('errorloadingconversation', 'mod_intebchat');
        }
    }

    /**
     * Load conversation return
     * @return external_single_structure
     */
    public static function load_conversation_returns()
    {
        return new \external_single_structure([
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
            'title' => new \external_value(PARAM_TEXT, 'Conversation title'),
            'messages' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_TEXT, 'Message ID'),
                    'role' => new \external_value(PARAM_TEXT, 'Role (user/assistant)'),
                    'message' => new \external_value(PARAM_RAW, 'Message content'),
                    'timestamp' => new \external_value(PARAM_INT, 'Timestamp'),
                ])
            )
        ]);
    }

    /**
     * Clear conversation parameters
     * @return external_function_parameters
     */
    public static function clear_conversation_parameters()
    {
        return new \external_function_parameters([
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
        ]);
    }

    /**
     * Clear conversation messages
     * @param int $conversationid
     * @return array
     */
    public static function clear_conversation($conversationid)
    {
        global $USER, $DB;

        try {
            $params = self::validate_parameters(self::clear_conversation_parameters(), [
                'conversationid' => $conversationid
            ]);

            // Get conversation to check permissions
            $conversation = $DB->get_record('intebchat_conversations', ['id' => $conversationid], '*', MUST_EXIST);

            // Validate instance and context
            $instance = $DB->get_record('intebchat', ['id' => $conversation->instanceid], '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false, MUST_EXIST);
            $context = \context_module::instance($cm->id);

            self::validate_context($context);
            require_capability('mod/intebchat:view', $context);

            // Check ownership
            if ($conversation->userid != $USER->id && !has_capability('mod/intebchat:viewstudentconversations', $context)) {
                throw new \moodle_exception('nopermission', 'mod_intebchat');
            }

            // Remove the conversation and all its messages
            $deleted = intebchat_delete_conversation_completely($conversationid);
            if (!$deleted) {
                throw new \moodle_exception('errorclearingconversation', 'mod_intebchat');
            }

            return [
                'success' => true,
                'deleted' => true
            ];
        } catch (\Exception $e) {
            debugging('Error clearing conversation: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('errorclearingconversation', 'mod_intebchat');
        }
    }

    /**
     * Clear conversation return
     * @return external_single_structure
     */
    public static function clear_conversation_returns()
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'deleted' => new \external_value(PARAM_BOOL, 'Whether conversation was deleted completely'),
        ]);
    }

    /**
     * Update conversation title parameters
     * @return external_function_parameters
     */
    public static function update_conversation_title_parameters()
    {
        return new \external_function_parameters([
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
            'title' => new \external_value(PARAM_TEXT, 'New title'),
        ]);
    }

    /**
     * Update conversation title
     * @param int $conversationid
     * @param string $title
     * @return array
     */
    public static function update_conversation_title($conversationid, $title)
    {
        global $USER, $DB;

        $params = self::validate_parameters(self::update_conversation_title_parameters(), [
            'conversationid' => $conversationid,
            'title' => $title
        ]);

        // Get conversation to check permissions
        $conversation = $DB->get_record('intebchat_conversations', ['id' => $conversationid], '*', MUST_EXIST);

        // Validate instance and context
        $instance = $DB->get_record('intebchat', ['id' => $conversation->instanceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/intebchat:view', $context);

        // Check ownership
        if ($conversation->userid != $USER->id) {
            throw new \moodle_exception('nopermission', 'mod_intebchat');
        }

        // Update title
        intebchat_update_conversation($conversationid, $title);

        return ['success' => true];
    }

    /**
     * Update conversation title return
     * @return external_single_structure
     */
    public static function update_conversation_title_returns()
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
        ]);
    }

    /**
     * Get assistants parameters
     * @return external_function_parameters
     */
    public static function get_assistants_parameters()
    {
        return new \external_function_parameters([
            'apikey' => new \external_value(PARAM_TEXT, 'API Key'),
        ]);
    }

    /**
     * Get list of assistants for an API key
     * @param string $apikey
     * @return array
     */
    public static function get_assistants($apikey)
    {
        // Validate user can add instances (settings)
        require_capability('mod/intebchat:addinstance', \context_system::instance());

        $params = self::validate_parameters(self::get_assistants_parameters(), [
            'apikey' => $apikey
        ]);

        // Get assistants
        $assistants_array = intebchat_fetch_assistants_array($apikey);

        $assistants = [];
        foreach ($assistants_array as $id => $name) {
            $assistants[] = [
                'id' => $id,
                'name' => $name
            ];
        }

        return ['assistants' => $assistants];
    }

    /**
     * Get assistants return
     * @return external_single_structure
     */
    public static function get_assistants_returns()
    {
        return new \external_single_structure([
            'assistants' => new \external_multiple_structure(
                new \external_single_structure([
                    'id' => new \external_value(PARAM_TEXT, 'Assistant ID'),
                    'name' => new \external_value(PARAM_TEXT, 'Assistant name'),
                ])
            )
        ]);
    }
}
