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
 * Web services for mod_intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_intebchat_create_conversation' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'create_conversation',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Create a new conversation',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:view',
    ],
    
    'mod_intebchat_load_conversation' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'load_conversation',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Load conversation messages',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:view',
    ],
    
    'mod_intebchat_clear_conversation' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'clear_conversation',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Clear conversation messages',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:view',
    ],
    
    'mod_intebchat_update_conversation_title' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'update_conversation_title',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Update conversation title',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:view',
    ],
    
    'mod_intebchat_get_assistants' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'get_assistants',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Get list of assistants for an API key',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:addinstance',
    ],

    'mod_intebchat_save_realtime_message' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'save_realtime_message',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Save a realtime message (user or assistant)',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:view',
    ],

    'mod_intebchat_get_site_report' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'get_site_report',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Get site-wide usage report data',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:viewsitereport',
    ],

    'mod_intebchat_get_course_report' => [
        'classname'   => 'mod_intebchat\external',
        'methodname'  => 'get_course_report',
        'classpath'   => 'mod/intebchat/classes/external.php',
        'description' => 'Get course-level usage report data',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/intebchat:viewanalytics',
    ],
];

$services = [
    'INTEB Chat service' => [
        'functions' => [
            'mod_intebchat_create_conversation',
            'mod_intebchat_load_conversation',
            'mod_intebchat_clear_conversation',
            'mod_intebchat_update_conversation_title',
            'mod_intebchat_get_assistants',
            'mod_intebchat_save_realtime_message',
            'mod_intebchat_get_site_report',
            'mod_intebchat_get_course_report',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];