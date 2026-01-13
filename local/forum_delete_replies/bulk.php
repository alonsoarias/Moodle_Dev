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
 * Bulk delete all replies from all forums in a course
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once(__DIR__ . '/classes/bulk_manager.php');

use local_forum_delete_replies\bulk_manager;

$courseid = required_param('courseid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$course = get_course($courseid);
require_login($course);

$context = context_course::instance($courseid);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url(new moodle_url('/local/forum_delete_replies/bulk.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('deleteallreplies', 'local_forum_delete_replies'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

// Get summary.
$summary = bulk_manager::get_course_forums_summary($courseid);

// Execute deletion if confirmed.
if ($confirm && confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleting', 'local_forum_delete_replies'));

    // Progress bar.
    $progressbar = new progress_bar('bulkdeleteprogress', 500, true);
    $progressbar->create();

    $progresscallback = function($current, $total, $message) use ($progressbar) {
        $progressbar->update($current, $total, $message);
    };

    // Execute bulk deletion.
    $result = bulk_manager::delete_course_replies_fast($courseid, $progresscallback);

    $progressbar->update_full(100, get_string('completed', 'local_forum_delete_replies'));

    echo html_writer::empty_tag('br');

    // Show results.
    if ($result['errors'] == 0 && $result['deleted'] > 0) {
        echo $OUTPUT->notification(
            get_string('deletesuccess', 'local_forum_delete_replies', [
                'deleted' => $result['deleted'],
                'discussions' => $result['forums_cleaned'],
            ]),
            'success'
        );
    } else if ($result['deleted'] == 0 && $result['errors'] == 0) {
        echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
    } else {
        echo $OUTPUT->notification(
            get_string('deletepartial', 'local_forum_delete_replies', [
                'deleted' => $result['deleted'],
                'errors' => $result['errors'],
            ]),
            'warning'
        );

        if (!empty($result['error_messages'])) {
            echo html_writer::start_tag('details', ['class' => 'mt-2']);
            echo html_writer::tag('summary', 'Error details');
            echo html_writer::start_tag('ul');
            foreach ($result['error_messages'] as $error) {
                echo html_writer::tag('li', s($error));
            }
            echo html_writer::end_tag('ul');
            echo html_writer::end_tag('details');
        }
    }

    echo html_writer::start_div('mt-4');
    echo $OUTPUT->single_button($courseurl, get_string('backtocourse', 'local_forum_delete_replies'), 'get');
    echo html_writer::end_div();

    echo $OUTPUT->footer();
    die;
}

// Show confirmation page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deleteallreplies', 'local_forum_delete_replies') . ' - ' . $course->fullname);

// Check if there are replies.
if ($summary['total_replies'] == 0) {
    echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
    echo $OUTPUT->single_button($courseurl, get_string('backtocourse', 'local_forum_delete_replies'), 'get');
    echo $OUTPUT->footer();
    die;
}

// Warning box.
echo $OUTPUT->box_start('generalbox alert alert-danger');
echo html_writer::tag('h4', html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' .
    get_string('warning', 'local_forum_delete_replies'));
echo html_writer::tag('p', get_string('warningmessage', 'local_forum_delete_replies'));
echo html_writer::tag('p', html_writer::tag('strong',
    get_string('bulkwarning', 'local_forum_delete_replies')));
echo $OUTPUT->box_end();

// Summary box.
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('h4', get_string('summary', 'local_forum_delete_replies'));

$summaryhtml = html_writer::start_tag('div', ['class' => 'row']);

// Left column - stats.
$summaryhtml .= html_writer::start_tag('div', ['class' => 'col-md-6']);
$summaryhtml .= html_writer::start_tag('dl', ['class' => 'row']);
$summaryhtml .= html_writer::tag('dt', get_string('course'), ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dd', format_string($course->fullname), ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dt', get_string('totalforums', 'local_forum_delete_replies'), ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dd', $summary['total_forums'], ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dt', get_string('forumswithReplies', 'local_forum_delete_replies'), ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dd', $summary['forums_with_replies'], ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dt', get_string('totalreplies', 'local_forum_delete_replies'), ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::tag('dd',
    html_writer::tag('span', $summary['total_replies'], ['class' => 'badge badge-danger bg-danger', 'style' => 'font-size: 1.2em;']),
    ['class' => 'col-sm-6']);
$summaryhtml .= html_writer::end_tag('dl');
$summaryhtml .= html_writer::end_tag('div');

// Right column - forum list.
$summaryhtml .= html_writer::start_tag('div', ['class' => 'col-md-6']);
$summaryhtml .= html_writer::tag('h5', get_string('forumlist', 'local_forum_delete_replies'));

$forumlist = html_writer::start_tag('ul', ['class' => 'list-unstyled', 'style' => 'max-height: 200px; overflow-y: auto;']);
foreach ($summary['forums'] as $forum) {
    $badge = $forum->reply_count > 0
        ? html_writer::tag('span', $forum->reply_count, ['class' => 'badge badge-warning bg-warning'])
        : html_writer::tag('span', '0', ['class' => 'badge badge-secondary bg-secondary']);
    $forumlist .= html_writer::tag('li', format_string($forum->name) . ' ' . $badge);
}
$forumlist .= html_writer::end_tag('ul');
$summaryhtml .= $forumlist;
$summaryhtml .= html_writer::end_tag('div');

$summaryhtml .= html_writer::end_tag('div');
echo $summaryhtml;
echo $OUTPUT->box_end();

// Confirmation message.
echo html_writer::tag('p', get_string('bulkconfirmmessage', 'local_forum_delete_replies', [
    'replycount' => $summary['total_replies'],
    'forumcount' => $summary['forums_with_replies'],
    'coursename' => format_string($course->shortname),
]), ['class' => 'lead']);

// Big action button.
$confirmurl = new moodle_url('/local/forum_delete_replies/bulk.php', [
    'courseid' => $courseid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo html_writer::start_div('text-center my-4');

echo html_writer::link(
    $confirmurl,
    html_writer::tag('i', '', ['class' => 'fa fa-trash mr-2']) .
    get_string('deleteallnow', 'local_forum_delete_replies') .
    ' (' . $summary['total_replies'] . ' ' . get_string('replies', 'local_forum_delete_replies') . ')',
    ['class' => 'btn btn-danger btn-lg', 'style' => 'font-size: 1.3em; padding: 15px 40px;']
);

echo html_writer::end_div();

echo html_writer::start_div('text-center');
echo html_writer::link(
    $courseurl,
    get_string('cancel'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
