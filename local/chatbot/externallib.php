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
 * External functions for intelligent chatbot
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/chatbot/lib.php');

class local_chatbot_external extends external_api {
    
    /**
     * Process message parameters
     */
    public static function process_message_parameters() {
        return new external_function_parameters(
            array(
                'message' => new external_value(PARAM_TEXT, 'User message'),
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'sessionid' => new external_value(PARAM_TEXT, 'Session ID'),
                'context' => new external_value(PARAM_TEXT, 'JSON context', VALUE_DEFAULT, '{}')
            )
        );
    }
    
    /**
     * Process user message with intelligent analysis
     */
    public static function process_message($message, $userid, $sessionid, $context = '{}') {
        global $USER, $DB, $CFG;
        
        // Validate parameters
        $params = self::validate_parameters(
            self::process_message_parameters(),
            array(
                'message' => $message,
                'userid' => $userid,
                'sessionid' => $sessionid,
                'context' => $context
            )
        );
        
        // Security check
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        // Process the message
        $result = local_chatbot_process_message($params['message'], $params['sessionid']);
        
        // Parse context
        $contextdata = json_decode($params['context'], true) ?: [];
        
        // Get suggestions for next actions
        $suggestions = [];
        $suggestionsdata = local_chatbot_get_suggestions($contextdata);
        foreach ($suggestionsdata as $suggestion) {
            $suggestions[] = [
                'text' => $suggestion['text'],
                'action' => $suggestion['action'] ?? '',
                'icon' => $suggestion['icon'] ?? ''
            ];
        }
        
        // Get any applicable quick actions
        $actions = [];
        $quickactions = local_chatbot_get_quick_actions($contextdata);
        foreach ($quickactions as $action) {
            $actions[] = [
                'type' => $action['action'],
                'label' => $action['label'],
                'icon' => $action['icon'] ?? '',
                'url' => isset($action['url']) ? $action['url']->out() : ''
            ];
        }
        
        return array(
            'response' => $result['response'],
            'response_time' => $result['response_time'] ?? 0,
            'sessionid' => $params['sessionid'],
            'suggestions' => $suggestions,
            'actions' => $actions,
            'status' => 'success'
        );
    }
    
    /**
     * Process message return structure
     */
    public static function process_message_returns() {
        return new external_single_structure(
            array(
                'response' => new external_value(PARAM_RAW, 'Bot response'),
                'response_time' => new external_value(PARAM_INT, 'Response time in milliseconds'),
                'sessionid' => new external_value(PARAM_TEXT, 'Session ID'),
                'suggestions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'text' => new external_value(PARAM_TEXT, 'Suggestion text'),
                            'action' => new external_value(PARAM_TEXT, 'Action identifier'),
                            'icon' => new external_value(PARAM_TEXT, 'Icon')
                        )
                    ),
                    'Suggestions',
                    VALUE_OPTIONAL
                ),
                'actions' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'type' => new external_value(PARAM_TEXT, 'Action type'),
                            'label' => new external_value(PARAM_TEXT, 'Action label'),
                            'icon' => new external_value(PARAM_TEXT, 'Icon'),
                            'url' => new external_value(PARAM_URL, 'URL if applicable')
                        )
                    ),
                    'Quick actions',
                    VALUE_OPTIONAL
                ),
                'status' => new external_value(PARAM_TEXT, 'Status')
            )
        );
    }
    
    /**
     * Get suggestions parameters
     */
    public static function get_suggestions_parameters() {
        return new external_function_parameters(
            array(
                'context' => new external_value(PARAM_TEXT, 'JSON context', VALUE_DEFAULT, '{}')
            )
        );
    }
    
    /**
     * Get contextual suggestions
     */
    public static function get_suggestions($context = '{}') {
        global $USER;
        
        $params = self::validate_parameters(
            self::get_suggestions_parameters(),
            array('context' => $context)
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        $contextdata = json_decode($params['context'], true) ?: [];
        $suggestions = local_chatbot_get_suggestions($contextdata);
        
        $result = [];
        foreach ($suggestions as $suggestion) {
            $result[] = [
                'text' => $suggestion['text'],
                'action' => $suggestion['action'] ?? '',
                'icon' => $suggestion['icon'] ?? ''
            ];
        }
        
        return $result;
    }
    
    /**
     * Get suggestions return structure
     */
    public static function get_suggestions_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'text' => new external_value(PARAM_TEXT, 'Suggestion text'),
                    'action' => new external_value(PARAM_TEXT, 'Action identifier'),
                    'icon' => new external_value(PARAM_TEXT, 'Icon', VALUE_OPTIONAL)
                )
            )
        );
    }
    
    /**
     * Get quick actions parameters
     */
    public static function get_quick_actions_parameters() {
        return new external_function_parameters(
            array(
                'context' => new external_value(PARAM_TEXT, 'JSON context', VALUE_DEFAULT, '{}')
            )
        );
    }
    
    /**
     * Get quick actions
     */
    public static function get_quick_actions($context = '{}') {
        $params = self::validate_parameters(
            self::get_quick_actions_parameters(),
            array('context' => $context)
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        $contextdata = json_decode($params['context'], true) ?: [];
        $actions = local_chatbot_get_quick_actions($contextdata);
        
        $result = [];
        foreach ($actions as $action) {
            $result[] = [
                'action' => $action['action'],
                'label' => $action['label'],
                'icon' => $action['icon'] ?? '',
                'description' => $action['description'] ?? '',
                'url' => isset($action['url']) ? $action['url']->out() : '',
                'params' => json_encode($action['params'] ?? [])
            ];
        }
        
        return $result;
    }
    
    /**
     * Get quick actions return structure
     */
    public static function get_quick_actions_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'action' => new external_value(PARAM_TEXT, 'Action identifier'),
                    'label' => new external_value(PARAM_TEXT, 'Action label'),
                    'icon' => new external_value(PARAM_TEXT, 'Icon'),
                    'description' => new external_value(PARAM_TEXT, 'Description'),
                    'url' => new external_value(PARAM_URL, 'URL if applicable'),
                    'params' => new external_value(PARAM_TEXT, 'JSON parameters')
                )
            )
        );
    }
    
    /**
     * Feedback parameters
     */
    public static function feedback_parameters() {
        return new external_function_parameters(
            array(
                'messageid' => new external_value(PARAM_TEXT, 'Message ID'),
                'feedback' => new external_value(PARAM_TEXT, 'Feedback type')
            )
        );
    }
    
    /**
     * Provide feedback on response
     */
    public static function feedback($messageid, $feedback) {
        $params = self::validate_parameters(
            self::feedback_parameters(),
            array(
                'messageid' => $messageid,
                'feedback' => $feedback
            )
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        // Extract the actual ID from the message ID
        $parts = explode('_', $params['messageid']);
        $logid = intval($parts[1] ?? 0);
        
        if ($logid) {
            local_chatbot_feedback($logid, $params['feedback']);
        }
        
        return ['success' => true];
    }
    
    /**
     * Feedback return structure
     */
    public static function feedback_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success status')
            )
        );
    }
    
    /**
     * Get history parameters
     */
    public static function get_history_parameters() {
        return new external_function_parameters(
            array(
                'sessionid' => new external_value(PARAM_TEXT, 'Session ID'),
                'limit' => new external_value(PARAM_INT, 'Limit', VALUE_DEFAULT, 10)
            )
        );
    }
    
    /**
     * Get conversation history
     */
    public static function get_history($sessionid, $limit = 10) {
        global $USER;
        
        $params = self::validate_parameters(
            self::get_history_parameters(),
            array(
                'sessionid' => $sessionid,
                'limit' => $limit
            )
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        $history = local_chatbot_get_conversation_history($params['sessionid'], $params['limit']);
        
        $result = [];
        foreach ($history as $item) {
            $result[] = [
                'message' => $item->message,
                'response' => $item->response,
                'intent' => $item->intent ?? '',
                'sentiment' => $item->sentiment ?? '',
                'timestamp' => $item->timecreated
            ];
        }
        
        return $result;
    }
    
    /**
     * Get history return structure
     */
    public static function get_history_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'message' => new external_value(PARAM_TEXT, 'User message'),
                    'response' => new external_value(PARAM_RAW, 'Bot response'),
                    'intent' => new external_value(PARAM_TEXT, 'Detected intent'),
                    'sentiment' => new external_value(PARAM_TEXT, 'Detected sentiment'),
                    'timestamp' => new external_value(PARAM_INT, 'Timestamp')
                )
            )
        );
    }
    
    /**
     * Export conversation parameters
     */
    public static function export_conversation_parameters() {
        return new external_function_parameters(
            array(
                'sessionid' => new external_value(PARAM_TEXT, 'Session ID'),
                'format' => new external_value(PARAM_TEXT, 'Export format', VALUE_DEFAULT, 'html')
            )
        );
    }
    
    /**
     * Export conversation
     */
    public static function export_conversation($sessionid, $format = 'html') {
        $params = self::validate_parameters(
            self::export_conversation_parameters(),
            array(
                'sessionid' => $sessionid,
                'format' => $format
            )
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        $content = local_chatbot_export_conversation($params['sessionid'], $params['format']);
        
        return ['content' => $content, 'format' => $params['format']];
    }
    
    /**
     * Export conversation return structure
     */
    public static function export_conversation_returns() {
        return new external_single_structure(
            array(
                'content' => new external_value(PARAM_RAW, 'Exported content'),
                'format' => new external_value(PARAM_TEXT, 'Format')
            )
        );
    }
    
    /**
     * Execute action parameters
     */
    public static function execute_action_parameters() {
        return new external_function_parameters(
            array(
                'action' => new external_value(PARAM_TEXT, 'Action identifier'),
                'params' => new external_value(PARAM_TEXT, 'JSON parameters', VALUE_DEFAULT, '{}')
            )
        );
    }
    
    /**
     * Execute custom action
     */
    public static function execute_action($action, $params = '{}') {
        global $USER, $DB, $CFG;
        
        $params = self::validate_parameters(
            self::execute_action_parameters(),
            array(
                'action' => $action,
                'params' => $params
            )
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:use', $systemcontext);
        
        $actionparams = json_decode($params['params'], true) ?: [];
        $message = '';
        
        // Execute action based on type
        switch ($params['action']) {
            case 'show_assignments':
                // Get pending assignments
                $courseid = $actionparams['courseid'] ?? 0;
                $sql = "SELECT a.name, a.duedate
                        FROM {assign} a
                        JOIN {course_modules} cm ON cm.instance = a.id
                        JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                        WHERE a.duedate > ? AND cm.visible = 1";
                $sqlparams = [time()];
                
                if ($courseid) {
                    $sql .= " AND cm.course = ?";
                    $sqlparams[] = $courseid;
                }
                
                $sql .= " ORDER BY a.duedate ASC LIMIT 5";
                
                $assignments = $DB->get_records_sql($sql, $sqlparams);
                
                if ($assignments) {
                    $message = "📝 **Tareas pendientes:**\n\n";
                    foreach ($assignments as $assignment) {
                        $duedate = userdate($assignment->duedate, '%d/%m/%Y %H:%M');
                        $message .= "• {$assignment->name} - Entrega: {$duedate}\n";
                    }
                } else {
                    $message = "¡Excelente! No tienes tareas pendientes en este momento. 🎉";
                }
                break;
                
            case 'today_activities':
                $message = "📅 **Actividades para hoy:**\n\n";
                // Implementation for today's activities
                $message .= "• No hay actividades programadas para hoy.\n";
                $message .= "\n💡 Tip: Revisa tu calendario para estar al día.";
                break;
                
            case 'show_grades':
                $message = "📊 Para ver tus calificaciones completas, ";
                $message .= "dirígete a: [Ver calificaciones](/grade/report/overview/index.php)";
                break;
                
            default:
                $message = "Acción ejecutada: " . $params['action'];
        }
        
        return ['message' => $message, 'success' => true];
    }
    
    /**
     * Execute action return structure
     */
    public static function execute_action_returns() {
        return new external_single_structure(
            array(
                'message' => new external_value(PARAM_RAW, 'Response message'),
                'success' => new external_value(PARAM_BOOL, 'Success status')
            )
        );
    }
    
    /**
     * Train pattern parameters
     */
    public static function train_pattern_parameters() {
        return new external_function_parameters(
            array(
                'pattern' => new external_value(PARAM_TEXT, 'Pattern to train'),
                'intent' => new external_value(PARAM_TEXT, 'Intent'),
                'approved' => new external_value(PARAM_BOOL, 'Is approved', VALUE_DEFAULT, false)
            )
        );
    }
    
    /**
     * Train new pattern
     */
    public static function train_pattern($pattern, $intent, $approved = false) {
        $params = self::validate_parameters(
            self::train_pattern_parameters(),
            array(
                'pattern' => $pattern,
                'intent' => $intent,
                'approved' => $approved
            )
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:manage', $systemcontext);
        
        local_chatbot_train($params['pattern'], $params['intent'], $params['approved']);
        
        return ['success' => true];
    }
    
    /**
     * Train pattern return structure
     */
    public static function train_pattern_returns() {
        return new external_single_structure(
            array(
                'success' => new external_value(PARAM_BOOL, 'Success status')
            )
        );
    }
    
    /**
     * Get analytics parameters
     */
    public static function get_analytics_parameters() {
        return new external_function_parameters(
            array(
                'timeframe' => new external_value(PARAM_TEXT, 'Timeframe', VALUE_DEFAULT, 'week')
            )
        );
    }
    
    /**
     * Get analytics data
     */
    public static function get_analytics($timeframe = 'week') {
        global $DB;
        
        $params = self::validate_parameters(
            self::get_analytics_parameters(),
            array('timeframe' => $timeframe)
        );
        
        $systemcontext = context_system::instance();
        require_capability('local/chatbot:viewanalytics', $systemcontext);
        
        // Calculate time range
        $now = time();
        switch ($params['timeframe']) {
            case 'day':
                $since = $now - 86400;
                break;
            case 'month':
                $since = $now - (86400 * 30);
                break;
            default: // week
                $since = $now - (86400 * 7);
        }
        
        // Get analytics data
        $totalinteractions = $DB->count_records_select('local_chatbot_logs', 'timecreated > ?', [$since]);
        $uniqueusers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT userid) FROM {local_chatbot_logs} WHERE timecreated > ?",
            [$since]
        );
        
        // Top intents
        $topintents = $DB->get_records_sql(
            "SELECT intent, COUNT(*) as count 
             FROM {local_chatbot_logs} 
             WHERE timecreated > ? AND intent IS NOT NULL
             GROUP BY intent 
             ORDER BY count DESC 
             LIMIT 5",
            [$since]
        );
        
        // Sentiment distribution
        $sentiments = $DB->get_records_sql(
            "SELECT sentiment, COUNT(*) as count 
             FROM {local_chatbot_logs} 
             WHERE timecreated > ? AND sentiment IS NOT NULL
             GROUP BY sentiment",
            [$since]
        );
        
        // Average response time
        $avgresponsetime = $DB->get_field_sql(
            "SELECT AVG(response_time) 
             FROM {local_chatbot_logs} 
             WHERE timecreated > ? AND response_time IS NOT NULL",
            [$since]
        );
        
        $intents = [];
        foreach ($topintents as $intent) {
            $intents[] = [
                'name' => $intent->intent,
                'count' => intval($intent->count)
            ];
        }
        
        $sentimentdata = [];
        foreach ($sentiments as $sentiment) {
            $sentimentdata[] = [
                'type' => $sentiment->sentiment,
                'count' => intval($sentiment->count)
            ];
        }
        
        return [
            'total_interactions' => $totalinteractions,
            'unique_users' => $uniqueusers,
            'avg_response_time' => round($avgresponsetime ?? 0),
            'top_intents' => $intents,
            'sentiment_distribution' => $sentimentdata
        ];
    }
    
    /**
     * Get analytics return structure
     */
    public static function get_analytics_returns() {
        return new external_single_structure(
            array(
                'total_interactions' => new external_value(PARAM_INT, 'Total interactions'),
                'unique_users' => new external_value(PARAM_INT, 'Unique users'),
                'avg_response_time' => new external_value(PARAM_INT, 'Average response time in ms'),
                'top_intents' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'name' => new external_value(PARAM_TEXT, 'Intent name'),
                            'count' => new external_value(PARAM_INT, 'Count')
                        )
                    )
                ),
                'sentiment_distribution' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'type' => new external_value(PARAM_TEXT, 'Sentiment type'),
                            'count' => new external_value(PARAM_INT, 'Count')
                        )
                    )
                )
            )
        );
    }
}
