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
 * Web services for intelligent chatbot
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_chatbot_process_message' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'process_message',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Process user message with intelligent analysis',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_get_suggestions' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'get_suggestions',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Get contextual suggestions',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_get_quick_actions' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'get_quick_actions',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Get quick actions for current context',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_feedback' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'feedback',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Provide feedback on chatbot response',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_get_history' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'get_history',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Get conversation history',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_export_conversation' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'export_conversation',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Export conversation in various formats',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_execute_action' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'execute_action',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Execute a custom action',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ),
    
    'local_chatbot_train_pattern' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'train_pattern',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Train chatbot with new pattern',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:manage',
        'loginrequired' => true,
    ),
    
    'local_chatbot_get_analytics' => array(
        'classname'   => 'local_chatbot_external',
        'methodname'  => 'get_analytics',
        'classpath'   => 'local/chatbot/externallib.php',
        'description' => 'Get chatbot analytics data',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'local/chatbot:viewanalytics',
        'loginrequired' => true,
    ),
);

$services = array(
    'Intelligent Chatbot Service' => array(
        'functions' => array(
            'local_chatbot_process_message',
            'local_chatbot_get_suggestions',
            'local_chatbot_get_quick_actions',
            'local_chatbot_feedback',
            'local_chatbot_get_history',
            'local_chatbot_export_conversation',
            'local_chatbot_execute_action'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'intelligent_chatbot'
    ),
    
    'Chatbot Management Service' => array(
        'functions' => array(
            'local_chatbot_train_pattern',
            'local_chatbot_get_analytics'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'chatbot_management'
    )
);
