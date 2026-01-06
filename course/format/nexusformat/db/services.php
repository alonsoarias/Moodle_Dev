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
 * External functions and service definitions for Nexus Format.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'format_nexusformat_get_activity_content' => [
        'classname'     => 'format_nexusformat\external\get_activity_content',
        'description'   => 'Get the rendered content of a course activity',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    // Notes services.
    'format_nexusformat_get_notes' => [
        'classname'     => 'format_nexusformat\external\get_notes',
        'description'   => 'Get user notes for a course',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'format_nexusformat_save_note' => [
        'classname'     => 'format_nexusformat\external\save_note',
        'description'   => 'Save a user note',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],

    'format_nexusformat_delete_note' => [
        'classname'     => 'format_nexusformat\external\delete_note',
        'description'   => 'Delete a user note',
        'type'          => 'write',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
