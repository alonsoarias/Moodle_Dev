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
 * Event observers for the Compecer theme.
 *
 * Observes completion events to invalidate course progress cache.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    // Invalidate cache when course module completion is updated.
    [
        'eventname' => '\core\event\course_module_completion_updated',
        'callback' => '\theme_compecer\observers\course_progress_observer::invalidate_user_progress',
    ],
    // Invalidate cache when course completion is updated.
    [
        'eventname' => '\core\event\course_completion_updated',
        'callback' => '\theme_compecer\observers\course_progress_observer::invalidate_user_progress',
    ],
    // Purge course cache when completion defaults are updated.
    [
        'eventname' => '\core\event\completion_defaults_updated',
        'callback' => '\theme_compecer\observers\course_progress_observer::purge_course_progress',
    ],
    // Purge course cache when course content is deleted.
    [
        'eventname' => '\core\event\course_module_deleted',
        'callback' => '\theme_compecer\observers\course_progress_observer::purge_course_progress',
    ],
    // Purge course cache when course content is created.
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\theme_compecer\observers\course_progress_observer::purge_course_progress',
    ],
];
