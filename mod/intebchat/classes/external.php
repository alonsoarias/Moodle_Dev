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

    /**
     * Save realtime message parameters
     * @return external_function_parameters
     */
    public static function save_realtime_message_parameters()
    {
        return new \external_function_parameters([
            'instanceid' => new \external_value(PARAM_INT, 'Instance ID'),
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID', VALUE_DEFAULT, 0),
            'role' => new \external_value(PARAM_TEXT, 'Role (user or assistant)'),
            'message' => new \external_value(PARAM_RAW, 'Message content'),
        ]);
    }

    /**
     * Save a realtime message (user or assistant)
     * For realtime mode, messages come separately unlike regular mode
     *
     * @param int $instanceid
     * @param int $conversationid
     * @param string $role
     * @param string $message
     * @return array
     */
    public static function save_realtime_message($instanceid, $conversationid, $role, $message)
    {
        global $USER, $DB;

        $params = self::validate_parameters(self::save_realtime_message_parameters(), [
            'instanceid' => $instanceid,
            'conversationid' => $conversationid,
            'role' => $role,
            'message' => $message
        ]);

        // Validate instance exists
        $instance = $DB->get_record('intebchat', ['id' => $instanceid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('intebchat', $instance->id, $instance->course, false, MUST_EXIST);
        $context = \context_module::instance($cm->id);

        self::validate_context($context);
        require_capability('mod/intebchat:view', $context);

        // Check if logging is enabled
        $config = get_config('mod_intebchat');
        if (empty($config->logging)) {
            return [
                'success' => true,
                'conversationid' => $conversationid,
                'messageid' => 0,
                'logging_disabled' => true
            ];
        }

        // Create conversation if needed
        if (empty($conversationid)) {
            $conversationid = intebchat_create_conversation($instanceid, $USER->id);
        }

        // Validate role
        $role = strtolower(trim($role));
        if (!in_array($role, ['user', 'assistant'])) {
            throw new \moodle_exception('invalidrole', 'mod_intebchat');
        }

        // Clean message
        $message = trim($message);
        if (empty($message)) {
            return [
                'success' => true,
                'conversationid' => $conversationid,
                'messageid' => 0,
                'empty_message' => true
            ];
        }

        $messageid = 0;

        if ($role === 'user') {
            // For user messages, create a new log record
            $record = new \stdClass();
            $record->instanceid = $instanceid;
            $record->userid = $USER->id;
            $record->conversationid = $conversationid;
            $record->usermessage = $message;
            $record->airesponse = ''; // Will be filled when assistant responds
            $record->prompttokens = 0;
            $record->completiontokens = 0;
            $record->totaltokens = 0;
            $record->contextid = $context->id;
            $record->timecreated = time();

            $messageid = $DB->insert_record('intebchat_log', $record);

            // Update conversation preview
            $preview = mb_substr(strip_tags($message), 0, 100);
            $DB->set_field('intebchat_conversations', 'preview', $preview, ['id' => $conversationid]);
            $DB->set_field('intebchat_conversations', 'timemodified', time(), ['id' => $conversationid]);

        } else {
            // For assistant messages, update the last record or create new
            // Find the last user message without AI response
            $sql = "SELECT * FROM {intebchat_log}
                    WHERE conversationid = :conversationid
                    AND userid = :userid
                    AND (airesponse = '' OR airesponse IS NULL)
                    ORDER BY timecreated DESC LIMIT 1";

            $lastmsg = $DB->get_record_sql($sql, [
                'conversationid' => $conversationid,
                'userid' => $USER->id
            ]);

            if ($lastmsg) {
                // Update the existing record with AI response
                $DB->set_field('intebchat_log', 'airesponse', $message, ['id' => $lastmsg->id]);
                $messageid = $lastmsg->id;
            } else {
                // Create a standalone assistant message (for cases like system responses)
                $record = new \stdClass();
                $record->instanceid = $instanceid;
                $record->userid = $USER->id;
                $record->conversationid = $conversationid;
                $record->usermessage = ''; // Empty for standalone assistant message
                $record->airesponse = $message;
                $record->prompttokens = 0;
                $record->completiontokens = 0;
                $record->totaltokens = 0;
                $record->contextid = $context->id;
                $record->timecreated = time();

                $messageid = $DB->insert_record('intebchat_log', $record);
            }

            // Update conversation timestamp
            $DB->set_field('intebchat_conversations', 'timemodified', time(), ['id' => $conversationid]);
        }

        // Update message count
        $messagecount = $DB->count_records('intebchat_log', ['conversationid' => $conversationid]);
        $DB->set_field('intebchat_conversations', 'messagecount', $messagecount, ['id' => $conversationid]);

        // Update title if this is the first message
        if ($messagecount <= 1 && $role === 'user') {
            $title = intebchat_generate_conversation_title($message);
            intebchat_update_conversation($conversationid, $title);
        }

        return [
            'success' => true,
            'conversationid' => (int)$conversationid,
            'messageid' => (int)$messageid
        ];
    }

    /**
     * Save realtime message return
     * @return external_single_structure
     */
    public static function save_realtime_message_returns()
    {
        return new \external_single_structure([
            'success' => new \external_value(PARAM_BOOL, 'Success status'),
            'conversationid' => new \external_value(PARAM_INT, 'Conversation ID'),
            'messageid' => new \external_value(PARAM_INT, 'Message ID'),
            'logging_disabled' => new \external_value(PARAM_BOOL, 'Whether logging is disabled', VALUE_OPTIONAL),
            'empty_message' => new \external_value(PARAM_BOOL, 'Whether message was empty', VALUE_OPTIONAL),
        ]);
    }

    // ========================================================================
    // REPORT METHODS
    // ========================================================================

    /**
     * Get site report parameters
     * @return external_function_parameters
     */
    public static function get_site_report_parameters()
    {
        return new \external_function_parameters([
            'view' => new \external_value(PARAM_ALPHA, 'View type: overview, courses, users'),
            'period' => new \external_value(PARAM_ALPHA, 'Period: day, week, month, year, all'),
            'courseid' => new \external_value(PARAM_INT, 'Filter by course ID', VALUE_DEFAULT, 0),
            'userid' => new \external_value(PARAM_INT, 'Filter by user ID', VALUE_DEFAULT, 0),
            'page' => new \external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new \external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 25),
        ]);
    }

    /**
     * Get site report data
     * @param string $view
     * @param string $period
     * @param int $courseid
     * @param int $userid
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public static function get_site_report($view, $period, $courseid, $userid, $page, $perpage)
    {
        global $DB;

        $params = self::validate_parameters(self::get_site_report_parameters(), [
            'view' => $view,
            'period' => $period,
            'courseid' => $courseid,
            'userid' => $userid,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('mod/intebchat:viewsitereport', $context);

        // Calculate date range
        $now = time();
        switch ($period) {
            case 'day':
                $starttime = strtotime('today');
                break;
            case 'week':
                $starttime = strtotime('-7 days');
                break;
            case 'month':
                $starttime = strtotime('-30 days');
                break;
            case 'year':
                $starttime = strtotime('-1 year');
                break;
            case 'all':
            default:
                $starttime = 0;
                break;
        }

        // Build query parameters
        $queryparams = [];
        $where = [];

        if ($starttime > 0) {
            $where[] = 'l.timecreated >= :starttime';
            $queryparams['starttime'] = $starttime;
        }

        if ($courseid > 0) {
            $where[] = 'i.course = :courseid';
            $queryparams['courseid'] = $courseid;
        }

        if ($userid > 0) {
            $where[] = 'l.userid = :userid';
            $queryparams['userid'] = $userid;
        }

        $whereclause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get summary statistics
        $sql = "SELECT
                    COUNT(DISTINCT l.id) as total_messages,
                    COUNT(DISTINCT l.userid) as total_users,
                    COUNT(DISTINCT i.course) as total_courses,
                    COUNT(DISTINCT i.id) as total_instances,
                    COALESCE(SUM(l.prompttokens), 0) as total_prompt_tokens,
                    COALESCE(SUM(l.completiontokens), 0) as total_completion_tokens,
                    COALESCE(SUM(l.totaltokens), 0) as total_tokens
                FROM {intebchat_log} l
                JOIN {intebchat} i ON l.instanceid = i.id
                $whereclause";

        $summary = $DB->get_record_sql($sql, $queryparams);

        $result = [
            'summary' => [
                'total_messages' => (int)$summary->total_messages,
                'total_users' => (int)$summary->total_users,
                'total_courses' => (int)$summary->total_courses,
                'total_instances' => (int)$summary->total_instances,
                'total_prompt_tokens' => (int)$summary->total_prompt_tokens,
                'total_completion_tokens' => (int)$summary->total_completion_tokens,
                'total_tokens' => (int)$summary->total_tokens,
            ],
            'courses' => [],
            'users' => [],
            'pagination' => [
                'page' => $page,
                'perpage' => $perpage,
                'total' => 0,
            ],
        ];

        // Get view-specific data
        if ($view === 'courses' || $view === 'overview') {
            $sql = "SELECT
                        i.course as courseid,
                        c.fullname as coursename,
                        c.shortname as shortname,
                        COUNT(DISTINCT l.id) as messages,
                        COUNT(DISTINCT l.userid) as users,
                        COUNT(DISTINCT i.id) as instances,
                        COALESCE(SUM(l.totaltokens), 0) as tokens
                    FROM {intebchat_log} l
                    JOIN {intebchat} i ON l.instanceid = i.id
                    JOIN {course} c ON i.course = c.id
                    $whereclause
                    GROUP BY i.course, c.fullname, c.shortname
                    ORDER BY tokens DESC";

            $courses = $DB->get_records_sql($sql, $queryparams);
            $result['pagination']['total'] = count($courses);

            // Apply pagination for courses view
            if ($view === 'courses') {
                $courses = array_slice(array_values($courses), $page * $perpage, $perpage);
            } else {
                // For overview, limit to top 5
                $courses = array_slice(array_values($courses), 0, 5);
            }

            foreach ($courses as $course) {
                $result['courses'][] = [
                    'courseid' => (int)$course->courseid,
                    'coursename' => $course->coursename,
                    'shortname' => $course->shortname,
                    'messages' => (int)$course->messages,
                    'users' => (int)$course->users,
                    'instances' => (int)$course->instances,
                    'tokens' => (int)$course->tokens,
                ];
            }
        }

        if ($view === 'users') {
            $sql = "SELECT
                        l.userid,
                        u.firstname,
                        u.lastname,
                        u.firstnamephonetic,
                        u.lastnamephonetic,
                        u.middlename,
                        u.alternatename,
                        u.email,
                        COUNT(DISTINCT l.id) as messages,
                        COUNT(DISTINCT i.course) as courses,
                        COALESCE(SUM(l.totaltokens), 0) as tokens
                    FROM {intebchat_log} l
                    JOIN {intebchat} i ON l.instanceid = i.id
                    JOIN {user} u ON l.userid = u.id
                    $whereclause
                    GROUP BY l.userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email
                    ORDER BY tokens DESC";

            $users = $DB->get_records_sql($sql, $queryparams);
            $result['pagination']['total'] = count($users);

            // Apply pagination
            $users = array_slice(array_values($users), $page * $perpage, $perpage);

            foreach ($users as $user) {
                $result['users'][] = [
                    'userid' => (int)$user->userid,
                    'fullname' => fullname($user),
                    'email' => $user->email,
                    'messages' => (int)$user->messages,
                    'courses' => (int)$user->courses,
                    'tokens' => (int)$user->tokens,
                ];
            }
        }

        return $result;
    }

    /**
     * Get site report return
     * @return external_single_structure
     */
    public static function get_site_report_returns()
    {
        return new \external_single_structure([
            'summary' => new \external_single_structure([
                'total_messages' => new \external_value(PARAM_INT, 'Total messages'),
                'total_users' => new \external_value(PARAM_INT, 'Total users'),
                'total_courses' => new \external_value(PARAM_INT, 'Total courses'),
                'total_instances' => new \external_value(PARAM_INT, 'Total instances'),
                'total_prompt_tokens' => new \external_value(PARAM_INT, 'Total prompt tokens'),
                'total_completion_tokens' => new \external_value(PARAM_INT, 'Total completion tokens'),
                'total_tokens' => new \external_value(PARAM_INT, 'Total tokens'),
            ]),
            'courses' => new \external_multiple_structure(
                new \external_single_structure([
                    'courseid' => new \external_value(PARAM_INT, 'Course ID'),
                    'coursename' => new \external_value(PARAM_TEXT, 'Course name'),
                    'shortname' => new \external_value(PARAM_TEXT, 'Course shortname'),
                    'messages' => new \external_value(PARAM_INT, 'Messages count'),
                    'users' => new \external_value(PARAM_INT, 'Users count'),
                    'instances' => new \external_value(PARAM_INT, 'Instances count'),
                    'tokens' => new \external_value(PARAM_INT, 'Tokens count'),
                ])
            ),
            'users' => new \external_multiple_structure(
                new \external_single_structure([
                    'userid' => new \external_value(PARAM_INT, 'User ID'),
                    'fullname' => new \external_value(PARAM_TEXT, 'User fullname'),
                    'email' => new \external_value(PARAM_TEXT, 'User email'),
                    'messages' => new \external_value(PARAM_INT, 'Messages count'),
                    'courses' => new \external_value(PARAM_INT, 'Courses count'),
                    'tokens' => new \external_value(PARAM_INT, 'Tokens count'),
                ])
            ),
            'pagination' => new \external_single_structure([
                'page' => new \external_value(PARAM_INT, 'Current page'),
                'perpage' => new \external_value(PARAM_INT, 'Items per page'),
                'total' => new \external_value(PARAM_INT, 'Total items'),
            ]),
        ]);
    }

    /**
     * Get course report parameters
     * @return external_function_parameters
     */
    public static function get_course_report_parameters()
    {
        return new \external_function_parameters([
            'courseid' => new \external_value(PARAM_INT, 'Course ID'),
            'period' => new \external_value(PARAM_ALPHA, 'Period: day, week, month, all'),
            'instanceid' => new \external_value(PARAM_INT, 'Filter by instance ID', VALUE_DEFAULT, 0),
            'page' => new \external_value(PARAM_INT, 'Page number', VALUE_DEFAULT, 0),
            'perpage' => new \external_value(PARAM_INT, 'Items per page', VALUE_DEFAULT, 25),
        ]);
    }

    /**
     * Get course report data
     * @param int $courseid
     * @param string $period
     * @param int $instanceid
     * @param int $page
     * @param int $perpage
     * @return array
     */
    public static function get_course_report($courseid, $period, $instanceid, $page, $perpage)
    {
        global $DB;

        $params = self::validate_parameters(self::get_course_report_parameters(), [
            'courseid' => $courseid,
            'period' => $period,
            'instanceid' => $instanceid,
            'page' => $page,
            'perpage' => $perpage,
        ]);

        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
        $context = \context_course::instance($course->id);
        self::validate_context($context);
        require_capability('mod/intebchat:viewanalytics', $context);

        // Calculate date range
        $now = time();
        switch ($period) {
            case 'day':
                $starttime = strtotime('today');
                break;
            case 'week':
                $starttime = strtotime('-7 days');
                break;
            case 'month':
                $starttime = strtotime('-30 days');
                break;
            case 'all':
            default:
                $starttime = 0;
                break;
        }

        // Build query parameters
        $queryparams = ['courseid' => $courseid];
        $where = ['i.course = :courseid'];

        if ($starttime > 0) {
            $where[] = 'l.timecreated >= :starttime';
            $queryparams['starttime'] = $starttime;
        }

        if ($instanceid > 0) {
            $where[] = 'i.id = :instanceid';
            $queryparams['instanceid'] = $instanceid;
        }

        $whereclause = 'WHERE ' . implode(' AND ', $where);

        // Get summary statistics
        $sql = "SELECT
                    COUNT(DISTINCT l.id) as total_messages,
                    COUNT(DISTINCT l.userid) as total_users,
                    COUNT(DISTINCT l.conversationid) as total_conversations,
                    COALESCE(SUM(l.totaltokens), 0) as total_tokens
                FROM {intebchat_log} l
                JOIN {intebchat} i ON l.instanceid = i.id
                $whereclause";

        $summary = $DB->get_record_sql($sql, $queryparams);

        $result = [
            'summary' => [
                'total_messages' => (int)($summary->total_messages ?? 0),
                'total_users' => (int)($summary->total_users ?? 0),
                'total_conversations' => (int)($summary->total_conversations ?? 0),
                'total_tokens' => (int)($summary->total_tokens ?? 0),
            ],
            'instances' => [],
            'users' => [],
            'pagination' => [
                'page' => $page,
                'perpage' => $perpage,
                'total' => 0,
            ],
        ];

        // Get instance statistics
        $instanceparams = ['courseid2' => $courseid];
        $instancewhere = "i.course = :courseid2";
        if ($starttime > 0) {
            $instancewhere .= " AND (l.timecreated >= :starttime2 OR l.timecreated IS NULL)";
            $instanceparams['starttime2'] = $starttime;
        }

        $sql = "SELECT
                    i.id as instanceid,
                    i.name,
                    COUNT(DISTINCT l.id) as messages,
                    COUNT(DISTINCT l.userid) as users,
                    COUNT(DISTINCT l.conversationid) as conversations,
                    COALESCE(SUM(l.totaltokens), 0) as tokens
                FROM {intebchat} i
                LEFT JOIN {intebchat_log} l ON l.instanceid = i.id
                WHERE $instancewhere
                GROUP BY i.id, i.name
                ORDER BY tokens DESC";

        $instances = $DB->get_records_sql($sql, $instanceparams);

        foreach ($instances as $instance) {
            $cm = get_coursemodule_from_instance('intebchat', $instance->instanceid, $courseid, false, IGNORE_MISSING);
            $result['instances'][] = [
                'instanceid' => (int)$instance->instanceid,
                'name' => format_string($instance->name),
                'messages' => (int)$instance->messages,
                'users' => (int)$instance->users,
                'conversations' => (int)$instance->conversations,
                'tokens' => (int)$instance->tokens,
                'cmid' => $cm ? (int)$cm->id : 0,
            ];
        }

        // Get user statistics
        $sql = "SELECT
                    l.userid,
                    u.firstname,
                    u.lastname,
                    u.firstnamephonetic,
                    u.lastnamephonetic,
                    u.middlename,
                    u.alternatename,
                    u.email,
                    COUNT(DISTINCT l.id) as messages,
                    COUNT(DISTINCT l.conversationid) as conversations,
                    COALESCE(SUM(l.totaltokens), 0) as tokens,
                    MAX(l.timecreated) as lastactivity
                FROM {intebchat_log} l
                JOIN {intebchat} i ON l.instanceid = i.id
                JOIN {user} u ON l.userid = u.id
                $whereclause
                GROUP BY l.userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email
                ORDER BY tokens DESC";

        $users = $DB->get_records_sql($sql, $queryparams);
        $result['pagination']['total'] = count($users);

        // Apply pagination
        $users = array_slice(array_values($users), $page * $perpage, $perpage);

        foreach ($users as $user) {
            $result['users'][] = [
                'userid' => (int)$user->userid,
                'fullname' => fullname($user),
                'email' => $user->email,
                'messages' => (int)$user->messages,
                'conversations' => (int)$user->conversations,
                'tokens' => (int)$user->tokens,
                'lastactivity' => (int)$user->lastactivity,
                'lastactivity_formatted' => userdate($user->lastactivity, get_string('strftimedatetime')),
            ];
        }

        return $result;
    }

    /**
     * Get course report return
     * @return external_single_structure
     */
    public static function get_course_report_returns()
    {
        return new \external_single_structure([
            'summary' => new \external_single_structure([
                'total_messages' => new \external_value(PARAM_INT, 'Total messages'),
                'total_users' => new \external_value(PARAM_INT, 'Total users'),
                'total_conversations' => new \external_value(PARAM_INT, 'Total conversations'),
                'total_tokens' => new \external_value(PARAM_INT, 'Total tokens'),
            ]),
            'instances' => new \external_multiple_structure(
                new \external_single_structure([
                    'instanceid' => new \external_value(PARAM_INT, 'Instance ID'),
                    'name' => new \external_value(PARAM_TEXT, 'Instance name'),
                    'messages' => new \external_value(PARAM_INT, 'Messages count'),
                    'users' => new \external_value(PARAM_INT, 'Users count'),
                    'conversations' => new \external_value(PARAM_INT, 'Conversations count'),
                    'tokens' => new \external_value(PARAM_INT, 'Tokens count'),
                    'cmid' => new \external_value(PARAM_INT, 'Course module ID'),
                ])
            ),
            'users' => new \external_multiple_structure(
                new \external_single_structure([
                    'userid' => new \external_value(PARAM_INT, 'User ID'),
                    'fullname' => new \external_value(PARAM_TEXT, 'User fullname'),
                    'email' => new \external_value(PARAM_TEXT, 'User email'),
                    'messages' => new \external_value(PARAM_INT, 'Messages count'),
                    'conversations' => new \external_value(PARAM_INT, 'Conversations count'),
                    'tokens' => new \external_value(PARAM_INT, 'Tokens count'),
                    'lastactivity' => new \external_value(PARAM_INT, 'Last activity timestamp'),
                    'lastactivity_formatted' => new \external_value(PARAM_TEXT, 'Last activity formatted'),
                ])
            ),
            'pagination' => new \external_single_structure([
                'page' => new \external_value(PARAM_INT, 'Current page'),
                'perpage' => new \external_value(PARAM_INT, 'Items per page'),
                'total' => new \external_value(PARAM_INT, 'Total items'),
            ]),
        ]);
    }
}
