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
 * Service definitions for local_downloadcenter.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Academic Moodle Cooperation
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = [
    'local_downloadcenter_get_category_children' => [
        'classname' => 'local_downloadcenter\\external',
        'methodname' => 'get_category_children',
        'classpath' => '',
        'description' => 'Return rendered HTML for a category children block.',
        'type' => 'read',
        'capabilities' => 'local/downloadcenter:downloadmultiple',
        'ajax' => true,
    ],
    'local_downloadcenter_get_course_resources' => [
        'classname' => 'local_downloadcenter\\external',
        'methodname' => 'get_course_resources',
        'classpath' => '',
        'description' => 'Return rendered HTML for a course resources block.',
        'type' => 'read',
        'capabilities' => 'local/downloadcenter:downloadmultiple',
        'ajax' => true,
    ],
    'local_downloadcenter_set_course_selection' => [
        'classname' => 'local_downloadcenter\\external',
        'methodname' => 'set_course_selection',
        'classpath' => '',
        'description' => 'Persist course selection for the current user.',
        'type' => 'write',
        'capabilities' => 'local/downloadcenter:downloadmultiple',
        'ajax' => true,
    ],
    'local_downloadcenter_set_download_options' => [
        'classname' => 'local_downloadcenter\\external',
        'methodname' => 'set_download_options',
        'classpath' => '',
        'description' => 'Persist download options for the current user.',
        'type' => 'write',
        'capabilities' => 'local/downloadcenter:downloadmultiple',
        'ajax' => true,
    ],
];

$services = [];
