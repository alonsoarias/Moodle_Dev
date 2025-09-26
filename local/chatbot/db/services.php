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
 * Web service definitions for the local_chatbot plugin.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_chatbot_process_message' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'process_message',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Process a message sent from the floating chatbot widget.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
    'local_chatbot_get_suggestions' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'get_suggestions',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Retrieve contextual suggestions for the chatbot widget.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
    'local_chatbot_get_quick_actions' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'get_quick_actions',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Retrieve quick action shortcuts for the chatbot widget.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
    'local_chatbot_feedback' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'feedback',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Store feedback for a chatbot response.',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
    'local_chatbot_get_history' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'get_history',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Fetch recent conversation messages.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
    'local_chatbot_export_conversation' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'export_conversation',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Export the conversation history for the current session.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'local/chatbot:export',
        'loginrequired' => true,
    ],
    'local_chatbot_execute_action' => [
        'classname' => 'local_chatbot_external',
        'methodname' => 'execute_action',
        'classpath' => 'local/chatbot/externallib.php',
        'description' => 'Execute a predefined chatbot action (for quick replies).',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'local/chatbot:use',
        'loginrequired' => true,
    ],
];

$services = [
    'Chatbot widget' => [
        'functions' => [
            'local_chatbot_process_message',
            'local_chatbot_get_suggestions',
            'local_chatbot_get_quick_actions',
            'local_chatbot_feedback',
            'local_chatbot_get_history',
            'local_chatbot_export_conversation',
            'local_chatbot_execute_action',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_chatbot_widget',
    ],
];
