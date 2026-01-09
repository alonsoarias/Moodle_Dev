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
 * Hook callback definitions for the Assignment No Submission Filter plugin.
 *
 * This file defines the hook subscriptions for injecting CSS and JavaScript.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    // Hook for injecting content before head HTML.
    [
        'hook' => \core\hook\output\before_standard_head_html_generation::class,
        'callback' => 'local_assign_no_submission_filter\hook_callbacks::before_standard_head_html',
        'priority' => 1000,
    ],
];
