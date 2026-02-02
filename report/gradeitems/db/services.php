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
 * External services definitions for the Grade Items Report plugin.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'report_gradeitems_get_courses' => [
        'classname'     => 'report_gradeitems\external\get_courses',
        'methodname'    => 'execute',
        'description'   => 'Get courses with gradeable activities count',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'report/gradeitems:view',
    ],
    'report_gradeitems_get_activities' => [
        'classname'     => 'report_gradeitems\external\get_activities',
        'methodname'    => 'execute',
        'description'   => 'Get gradeable activities for a course',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'report/gradeitems:view',
    ],
];
