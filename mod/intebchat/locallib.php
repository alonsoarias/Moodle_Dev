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
 * Local library functions for conversation management
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get conversations for a user in an instance
 * 
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @param int $limit Number of conversations to return
 * @return array Array of conversation objects
 */
function intebchat_get_user_conversations($instanceid, $userid, $limit = 50)
{
    global $DB;

    $sql = "SELECT c.*, 
                   COALESCE((SELECT MAX(timecreated) FROM {intebchat_log} WHERE conversationid = c.id), c.timecreated) as lastmessage
            FROM {intebchat_conversations} c
            WHERE c.instanceid = :instanceid 
              AND c.userid = :userid
            ORDER BY lastmessage DESC";

    return $DB->get_records_sql($sql, ['instanceid' => $instanceid, 'userid' => $userid], 0, $limit);
}

/**
 * Get messages for a conversation
 * 
 * @param int $conversationid Conversation ID
 * @param int $limit Number of messages to return (0 = all)
 * @return array Array of message objects
 */
function intebchat_get_conversation_messages($conversationid, $limit = 0)
{
    global $DB;

    $params = ['conversationid' => $conversationid];

    if ($limit > 0) {
        // Use Moodle's get_records with limit parameter instead of raw SQL LIMIT
        return $DB->get_records(
            'intebchat_log',
            ['conversationid' => $conversationid],
            'timecreated ASC',
            'id, userid, usermessage, airesponse, totaltokens, timecreated',
            0,
            $limit
        );
    } else {
        // Get all records
        return $DB->get_records(
            'intebchat_log',
            ['conversationid' => $conversationid],
            'timecreated ASC'
        );
    }
}

/**
 * Check if user can view a conversation
 * 
 * @param int $conversationid Conversation ID
 * @param int $userid User ID to check
 * @param context $context Module context
 * @return bool True if user can view
 */
function intebchat_can_view_conversation($conversationid, $userid, $context)
{
    global $DB;

    $conversation = $DB->get_record('intebchat_conversations', ['id' => $conversationid]);
    if (!$conversation) {
        return false;
    }

    // User can view their own conversations
    if ($conversation->userid == $userid) {
        return true; // Simplified check since users should always see their own conversations
    }

    // Teachers can view student conversations in their courses
    if (has_capability('mod/intebchat:viewstudentconversations', $context)) {
        return true;
    }

    // Admins can view all conversations
    if (has_capability('mod/intebchat:viewallconversations', context_system::instance())) {
        return true;
    }

    return false;
}

/**
 * Generate automatic title for conversation based on first message
 * 
 * @param string $firstmessage First message content
 * @return string Generated title
 */
function intebchat_generate_conversation_title($firstmessage)
{
    // Clean and truncate the message
    $title = strip_tags($firstmessage);
    $title = trim($title);

    // Remove extra whitespace
    $title = preg_replace('/\s+/', ' ', $title);

    // If message is short enough, use it as title
    if (mb_strlen($title) <= 50) {
        return $title;
    }

    // Otherwise, truncate to 47 chars and add ellipsis
    $title = mb_substr($title, 0, 47) . '...';

    return $title;
}

/**
 * Clear all messages in a conversation
 * If conversation is empty, delete it completely
 * 
 * @param int $conversationid Conversation ID
 * @return array ['success' => bool, 'deleted' => bool]
 */
function intebchat_clear_conversation_messages($conversationid)
{
    global $DB;

    try {
        // Start transaction
        $transaction = $DB->start_delegated_transaction();

        // Get current message count
        $messagecount = $DB->count_records('intebchat_log', ['conversationid' => $conversationid]);

        if ($messagecount == 0) {
            // If conversation is already empty, delete it completely
            $DB->delete_records('intebchat_conversations', ['id' => $conversationid]);
            $transaction->allow_commit();
            return ['success' => true, 'deleted' => true];
        }

        // Delete all messages
        $DB->delete_records('intebchat_log', ['conversationid' => $conversationid]);

        $conversation = new stdClass();
        $conversation->id = $conversationid;
        $conversation->preview = '';
        $conversation->messagecount = 0;
        $conversation->threadid = null;    // Reiniciar el hilo asociado
        $conversation->timemodified = time();

        $DB->update_record('intebchat_conversations', $conversation);

        // Commit transaction
        $transaction->allow_commit();

        return ['success' => true, 'deleted' => false];
    } catch (Exception $e) {
        if (isset($transaction)) {
            $transaction->rollback($e);
        }
        debugging('Error clearing conversation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return ['success' => false, 'deleted' => false];
    }
}

/**
 * Delete a conversation completely
 * 
 * @param int $conversationid Conversation ID
 * @return bool Success
 */
function intebchat_delete_conversation_completely($conversationid)
{
    global $DB;

    // Verificar que la conversación existe
    if (!$DB->record_exists('intebchat_conversations', ['id' => $conversationid])) {
        debugging('Conversation does not exist: ' . $conversationid, DEBUG_DEVELOPER);
        return false;
    }

    $transaction = null;
    try {
        // Start transaction
        $transaction = $DB->start_delegated_transaction();

        // Delete all messages first (pueden no existir)
        $DB->delete_records('intebchat_log', ['conversationid' => $conversationid]);

        // Delete the conversation
        $deleted = $DB->delete_records('intebchat_conversations', ['id' => $conversationid]);

        // Commit transaction
        $transaction->allow_commit();

        return $deleted;
    } catch (Exception $e) {
        if ($transaction) {
            try {
                $transaction->rollback($e);
            } catch (Exception $rollback_exception) {
                // Log rollback failure
                debugging('Rollback failed: ' . $rollback_exception->getMessage(), DEBUG_DEVELOPER);
            }
        }
        debugging('Error deleting conversation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Get token usage statistics for a user
 * 
 * @param int $userid User ID
 * @param string $period Period type (hour, day, week, month)
 * @return object Statistics object
 */
function intebchat_get_user_token_stats($userid, $period = 'day')
{
    global $DB;

    require_once(__DIR__ . '/lib.php');
    $periodstart = intebchat_get_period_start($period);

    $stats = new stdClass();
    $stats->period = $period;
    $stats->periodstart = $periodstart;

    // Get current period usage
    $usage = $DB->get_record('intebchat_token_usage', [
        'userid' => $userid,
        'periodtype' => $period,
        'periodstart' => $periodstart
    ]);

    $stats->current_usage = $usage ? $usage->tokensused : 0;

    // Get total historical usage - handle null values
    $sql = "SELECT COALESCE(SUM(totaltokens), 0) as total
            FROM {intebchat_log} 
            WHERE userid = :userid";
    $result = $DB->get_record_sql($sql, ['userid' => $userid]);
    $stats->total_usage = $result ? $result->total : 0;

    // Get conversation count
    $stats->conversation_count = $DB->count_records('intebchat_conversations', ['userid' => $userid]);

    // Get message count
    $stats->message_count = $DB->count_records('intebchat_log', ['userid' => $userid]);

    return $stats;
}

/**
 * Search conversations by content
 * 
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @param string $search Search term
 * @return array Array of matching conversations
 */
function intebchat_search_conversations($instanceid, $userid, $search)
{
    global $DB;

    if (empty($search)) {
        return intebchat_get_user_conversations($instanceid, $userid);
    }

    $search = '%' . $DB->sql_like_escape($search) . '%';

    $sql = "SELECT DISTINCT c.*,
                   COALESCE((SELECT MAX(timecreated) FROM {intebchat_log} WHERE conversationid = c.id), c.timecreated) as lastmessage
            FROM {intebchat_conversations} c
            LEFT JOIN {intebchat_log} l ON l.conversationid = c.id
            WHERE c.instanceid = :instanceid 
              AND c.userid = :userid
              AND (
                  " . $DB->sql_like('c.title', ':searchtitle') . " OR
                  " . $DB->sql_like('l.usermessage', ':searchmessage') . " OR
                  " . $DB->sql_like('l.airesponse', ':searchresponse') . "
              )
            ORDER BY lastmessage DESC";

    return $DB->get_records_sql($sql, [
        'instanceid' => $instanceid,
        'userid' => $userid,
        'searchtitle' => $search,
        'searchmessage' => $search,
        'searchresponse' => $search
    ]);
}
