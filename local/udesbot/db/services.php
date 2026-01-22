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
 * External functions and service definitions for local_udesbot.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_udesbot_get_popular_questions' => [
        'classname' => 'local_udesbot\external',
        'methodname' => 'get_popular_questions',
        'description' => 'Get most frequently asked questions for mascot suggestions',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
    'local_udesbot_get_similar_questions' => [
        'classname' => 'local_udesbot\external',
        'methodname' => 'get_similar_questions',
        'description' => 'Get similar questions based on keywords',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];

$services = [
    'udesbot services' => [
        'functions' => [
            'local_udesbot_get_popular_questions',
            'local_udesbot_get_similar_questions',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'local_udesbot',
    ],
];
