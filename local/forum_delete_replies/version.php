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
 * Version information for local_forum_delete_replies
 *
 * This plugin allows teachers to delete all replies from forum discussions,
 * keeping only the initial post of each discussion.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_forum_delete_replies';
$plugin->version = 2025011300;
$plugin->requires = 2022112800; // Moodle 4.1+
$plugin->maturity = MATURITY_STABLE;
$plugin->release = 'v1.0.0';
$plugin->dependencies = [
    'mod_forum' => 2022112800
];
