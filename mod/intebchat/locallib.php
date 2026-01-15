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
 * Get conversations for a user in an instance with pagination support.
 *
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @param int $limit Number of conversations to return (default 20, 0 = all)
 * @param int $offset Starting offset for pagination
 * @return array Array of conversation objects
 */
function intebchat_get_user_conversations($instanceid, $userid, $limit = 20, $offset = 0)
{
    global $DB;

    $sql = "SELECT c.*,
                   COALESCE((SELECT MAX(timecreated) FROM {intebchat_log} WHERE conversationid = c.id), c.timecreated) as lastmessage
            FROM {intebchat_conversations} c
            WHERE c.instanceid = :instanceid
              AND c.userid = :userid
            ORDER BY lastmessage DESC";

    $limitfrom = ($limit > 0) ? $offset : 0;
    $limitnum = ($limit > 0) ? $limit : 0;

    return $DB->get_records_sql($sql, ['instanceid' => $instanceid, 'userid' => $userid], $limitfrom, $limitnum);
}

/**
 * Get total count of conversations for pagination.
 *
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @return int Total number of conversations
 */
function intebchat_count_user_conversations($instanceid, $userid)
{
    global $DB;
    return $DB->count_records('intebchat_conversations', [
        'instanceid' => $instanceid,
        'userid' => $userid
    ]);
}

/**
 * Get conversations with pagination info.
 *
 * Returns conversations along with pagination metadata for lazy loading.
 *
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @param int $page Page number (0-based)
 * @param int $perpage Items per page
 * @return array ['conversations' => array, 'total' => int, 'page' => int, 'pages' => int, 'hasmore' => bool]
 */
function intebchat_get_paginated_conversations($instanceid, $userid, $page = 0, $perpage = 20)
{
    $total = intebchat_count_user_conversations($instanceid, $userid);
    $pages = ($perpage > 0) ? ceil($total / $perpage) : 1;
    $offset = $page * $perpage;

    $conversations = intebchat_get_user_conversations($instanceid, $userid, $perpage, $offset);

    return [
        'conversations' => array_values($conversations),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
        'hasmore' => ($page + 1) < $pages
    ];
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
 * Note: Log records are NOT deleted to preserve token usage history for reporting.
 * Instead, their conversationid is set to NULL to unlink them from the deleted conversation.
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

        // Unlink log records from conversation (preserve for token reporting)
        // Set conversationid to NULL instead of deleting to maintain token usage history
        $DB->set_field('intebchat_log', 'conversationid', null, ['conversationid' => $conversationid]);

        // Delete the conversation record
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

/**
 * Get analytics data for a specific intebchat instance.
 *
 * @param int $instanceid The intebchat instance ID
 * @param int $starttime Start timestamp for the period
 * @param int $endtime End timestamp for the period
 * @return array Analytics data including totals, top users, and daily stats
 */
function intebchat_get_instance_analytics($instanceid, $starttime, $endtime) {
    global $DB;

    $params = ['instanceid' => $instanceid];
    $timewhere = '';

    if ($starttime > 0) {
        $timewhere = ' AND l.timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }
    if ($endtime > 0) {
        $timewhere .= ' AND l.timecreated <= :endtime';
        $params['endtime'] = $endtime;
    }

    // Get summary statistics.
    $sql = "SELECT
                COUNT(DISTINCT c.id) as total_conversations,
                COUNT(DISTINCT l.id) as total_messages,
                COALESCE(SUM(l.totaltokens), 0) as total_tokens,
                COUNT(DISTINCT l.userid) as unique_users
            FROM {intebchat_conversations} c
            LEFT JOIN {intebchat_log} l ON l.conversationid = c.id
            WHERE c.instanceid = :instanceid
                  $timewhere";

    $summary = $DB->get_record_sql($sql, $params);

    // Calculate averages.
    $total_messages = (int)$summary->total_messages;
    $total_tokens = (int)$summary->total_tokens;
    $unique_users = (int)$summary->unique_users;

    $analytics = [
        'total_conversations' => (int)$summary->total_conversations,
        'total_messages' => $total_messages,
        'total_tokens' => $total_tokens,
        'unique_users' => $unique_users,
        'avg_messages_per_user' => $unique_users > 0 ? round($total_messages / $unique_users, 1) : 0,
        'avg_tokens_per_message' => $total_messages > 0 ? round($total_tokens / $total_messages) : 0,
    ];

    // Get top users.
    $sql = "SELECT
                l.userid,
                COUNT(l.id) as messages,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            JOIN {intebchat_conversations} c ON l.conversationid = c.id
            WHERE c.instanceid = :instanceid
                  $timewhere
            GROUP BY l.userid
            ORDER BY tokens DESC";

    $params_top = $params;
    $analytics['top_users'] = array_values($DB->get_records_sql($sql, $params_top, 0, 10));

    // Get daily statistics.
    $dailyparams = ['instanceid' => $instanceid];
    $dailytimewhere = '';

    if ($starttime > 0) {
        $dailytimewhere = ' AND l.timecreated >= :starttime';
        $dailyparams['starttime'] = $starttime;
    }
    if ($endtime > 0) {
        $dailytimewhere .= ' AND l.timecreated <= :endtime';
        $dailyparams['endtime'] = $endtime;
    }

    // Use database-agnostic date truncation.
    $sql = "SELECT
                DATE(FROM_UNIXTIME(l.timecreated)) as date,
                COUNT(l.id) as messages,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            JOIN {intebchat_conversations} c ON l.conversationid = c.id
            WHERE c.instanceid = :instanceid
                  $dailytimewhere
            GROUP BY DATE(FROM_UNIXTIME(l.timecreated))
            ORDER BY date DESC";

    $dailyrecords = $DB->get_records_sql($sql, $dailyparams, 0, 30);

    // Convert daily records to proper format with unix timestamps.
    $analytics['daily_stats'] = [];
    foreach ($dailyrecords as $record) {
        $analytics['daily_stats'][] = (object)[
            'date' => strtotime($record->date),
            'messages' => (int)$record->messages,
            'tokens' => (int)$record->tokens,
        ];
    }

    // Reverse to show oldest first for charts.
    $analytics['daily_stats'] = array_reverse($analytics['daily_stats']);

    return $analytics;
}
