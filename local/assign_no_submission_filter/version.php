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
 * Version information for local_assign_no_submission_filter.
 *
 * This plugin filters assignment grading views to show only students
 * who have made submissions, improving the grading workflow for teachers.
 *
 * @package    local_assign_no_submission_filter
 * @author     IngeWeb
 * @copyright  2026 IngeWeb para TecnosZubia
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_assign_no_submission_filter';
$plugin->version = 2026010900;
$plugin->requires = 2022112800; // Moodle 4.3+
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v2.0.0';
$plugin->dependencies = [
    'mod_assign' => 2022112800,
];
