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
 * Event observers for local_assign_no_submission_filter
 *
 * @package    local_assign_no_submission_filter
 * @copyright  2024 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\mod_assign\event\grading_table_viewed',
        'callback' => '\local_assign_no_submission_filter\observer::grading_table_viewed',
        'priority' => 0,
        'internal' => true,
    ],
    [
        'eventname' => '\mod_assign\event\submission_graded',
        'callback' => '\local_assign_no_submission_filter\observer::submission_graded',
        'priority' => 0,
        'internal' => false,
    ],
    [
        'eventname' => '\mod_assign\event\submission_status_updated',
        'callback' => '\local_assign_no_submission_filter\observer::submission_status_updated',
        'priority' => 0,
        'internal' => false,
    ],
];