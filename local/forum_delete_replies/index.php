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
 * Forum selection page for delete replies.
 *
 * Lists all forums in a course with their reply counts and provides
 * options to delete replies from individual forums or all at once.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);

// Check basic capability at course level.
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/local/forum_delete_replies/index.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('deleteallreplies', 'local_forum_delete_replies'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// Get all forums in the course.
$forums = get_all_instances_in_course('forum', $course);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteallreplies', 'local_forum_delete_replies'));

if (empty($forums)) {
    echo $OUTPUT->notification(get_string('noforums', 'local_forum_delete_replies'), 'warning');
    echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $courseid]));
    echo $OUTPUT->footer();
    exit;
}

// Calculate total replies in all forums.
$totalreplies = $DB->count_records_select(
    'forum_posts',
    'discussion IN (SELECT id FROM {forum_discussions} WHERE forum IN
     (SELECT id FROM {forum} WHERE course = :courseid)) AND parent != 0',
    ['courseid' => $courseid]
);

// Show ONE-CLICK bulk delete button if there are replies.
if ($totalreplies > 0) {
    $bulkurl = new moodle_url('/local/forum_delete_replies/bulk.php', ['courseid' => $courseid]);

    echo $OUTPUT->box_start('generalbox text-center py-4', 'bulk-action-box');
    echo html_writer::tag('h4', get_string('oneclickdelete', 'local_forum_delete_replies'));
    echo html_writer::tag('p', get_string('bulkwarning', 'local_forum_delete_replies'), ['class' => 'text-muted']);
    echo html_writer::link(
        $bulkurl,
        html_writer::tag('i', '', ['class' => 'fa fa-trash-o mr-2']) .
        get_string('deleteallnow', 'local_forum_delete_replies') .
        ' (' . $totalreplies . ' ' . strtolower(get_string('replies', 'local_forum_delete_replies')) . ')',
        ['class' => 'btn btn-danger btn-lg', 'style' => 'font-size: 1.2em; padding: 12px 30px;']
    );
    echo $OUTPUT->box_end();

    echo html_writer::tag('hr', '');
    echo html_writer::tag('h5', get_string('selectforum', 'local_forum_delete_replies') . ' (' .
        get_string('optional', 'form') . ')', ['class' => 'mt-4']);
}

// Build table with forums and their reply counts.
$table = new html_table();
$table->head = [
    get_string('forum', 'local_forum_delete_replies'),
    get_string('discussions', 'local_forum_delete_replies'),
    get_string('replies', 'local_forum_delete_replies'),
    get_string('actions'),
];
$table->attributes['class'] = 'generaltable';

foreach ($forums as $forum) {
    $cm = get_coursemodule_from_instance('forum', $forum->id, $courseid);
    $modcontext = context_module::instance($cm->id);

    // Check if user can delete in this specific forum.
    if (!has_capability('local/forum_delete_replies:delete', $modcontext)) {
        continue;
    }

    // Count discussions and replies.
    $discussioncount = $DB->count_records('forum_discussions', ['forum' => $forum->id]);

    $replycount = $DB->count_records_select(
        'forum_posts',
        'discussion IN (SELECT id FROM {forum_discussions} WHERE forum = :forumid) AND parent != 0',
        ['forumid' => $forum->id]
    );

    $forumurl = new moodle_url('/mod/forum/view.php', ['id' => $cm->id]);
    $forumlink = html_writer::link($forumurl, format_string($forum->name));

    if ($replycount > 0) {
        $deleteurl = new moodle_url('/local/forum_delete_replies/cleanup.php', [
            'forumid' => $forum->id,
            'courseid' => $courseid,
        ]);
        $deletebutton = html_writer::link(
            $deleteurl,
            get_string('delete', 'local_forum_delete_replies'),
            ['class' => 'btn btn-danger btn-sm']
        );
    } else {
        $deletebutton = html_writer::span(
            get_string('noreplies', 'local_forum_delete_replies'),
            'text-muted'
        );
    }

    $table->data[] = [
        $forumlink,
        $discussioncount,
        $replycount,
        $deletebutton,
    ];
}

if (empty($table->data)) {
    echo $OUTPUT->notification(get_string('nopermission', 'local_forum_delete_replies'), 'warning');
} else {
    echo html_writer::tag('p', get_string('selectforumhelp', 'local_forum_delete_replies'));
    echo html_writer::table($table);
}

echo html_writer::empty_tag('br');
echo $OUTPUT->single_button(
    new moodle_url('/course/view.php', ['id' => $courseid]),
    get_string('backtocourse', 'local_forum_delete_replies'),
    'get'
);

echo $OUTPUT->footer();
