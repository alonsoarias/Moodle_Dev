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
 * View page - redirects to cleanup page for a specific forum
 *
 * This page can be accessed from the forum's action menu.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$cmid = required_param('id', PARAM_INT);

$cm = get_coursemodule_from_id('forum', $cmid, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$forum = $DB->get_record('forum', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, false, $cm);

$context = context_module::instance($cm->id);
require_capability('local/forum_delete_replies:delete', $context);

// Redirect to cleanup page.
$url = new moodle_url('/local/forum_delete_replies/cleanup.php', [
    'forumid' => $forum->id,
    'courseid' => $course->id,
]);

redirect($url);
