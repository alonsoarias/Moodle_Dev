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
 * External services for theme_inteb
 *
 * @package    theme_inteb
 * @copyright  2025 INTEB
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'theme_inteb_get_course_teachers' => [
        'classname' => 'theme_inteb\external\get_course_teachers',
        'methodname' => 'execute',
        'description' => 'Get all teachers (both editing and non-editing) for a course',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
