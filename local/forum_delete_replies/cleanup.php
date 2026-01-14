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
 * Cleanup execution page for delete replies from a single forum.
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once(__DIR__ . '/classes/cleanup_manager.php');

use local_forum_delete_replies\cleanup_manager;

$forumid = required_param('forumid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);
$fastmode = optional_param('fastmode', 0, PARAM_INT);

// Initialize cleanup manager.
$manager = new cleanup_manager($forumid);
$forum = $manager->get_forum();
$course = $manager->get_course();
$cm = $manager->get_cm();
$context = $manager->get_context();

require_login($course, false, $cm);
require_capability('local/forum_delete_replies:delete', $context);

$PAGE->set_url(new moodle_url('/local/forum_delete_replies/cleanup.php', [
    'forumid' => $forumid,
    'courseid' => $courseid,
]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('deletefromforum', 'local_forum_delete_replies'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('incourse');

// URLs for navigation.
$indexurl = new moodle_url('/local/forum_delete_replies/index.php', ['courseid' => $courseid]);
$forumurl = new moodle_url('/mod/forum/view.php', ['id' => $cm->id]);
$courseurl = new moodle_url('/course/view.php', ['id' => $courseid]);

// Get summary.
$summary = $manager->get_summary();

// If confirmed, execute deletion.
if ($confirm && confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleting', 'local_forum_delete_replies'));

    // Show progress.
    $progressbar = new progress_bar('deleteprogress', 500, true);
    $progressbar->create();

    $progresscallback = function($current, $total, $discussionname) use ($progressbar) {
        $message = get_string('processing', 'local_forum_delete_replies', [
            'current' => $current,
            'total' => $total,
        ]);
        $progressbar->update($current, $total, $message);
    };

    // Execute deletion.
    if ($fastmode) {
        $result = $manager->delete_replies_fast();
    } else {
        $result = $manager->delete_replies_safe($progresscallback);
    }

    $progressbar->update_full(100, get_string('completed', 'local_forum_delete_replies'));

    // Show results.
    echo html_writer::empty_tag('br');

    if ($result['errors'] == 0 && $result['deleted'] > 0) {
        echo $OUTPUT->notification(
            get_string('deletesuccess', 'local_forum_delete_replies', [
                'deleted' => $result['deleted'],
                'discussions' => $summary['discussions_with_replies'],
            ]),
            'success'
        );
    } else if ($result['errors'] > 0) {
        echo $OUTPUT->notification(
            get_string('deletepartial', 'local_forum_delete_replies', [
                'deleted' => $result['deleted'],
                'errors' => $result['errors'],
            ]),
            'warning'
        );

        // Show error details.
        if (!empty($result['error_messages'])) {
            echo html_writer::start_tag('details', ['class' => 'mt-2']);
            echo html_writer::tag('summary', get_string('errordetails', 'local_forum_delete_replies'));
            echo html_writer::start_tag('ul');
            foreach ($result['error_messages'] as $error) {
                echo html_writer::tag('li', s($error));
            }
            echo html_writer::end_tag('ul');
            echo html_writer::end_tag('details');
        }
    } else {
        echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
    }

    // Navigation buttons.
    echo html_writer::start_div('mt-4');
    echo $OUTPUT->single_button($forumurl, get_string('backtoforum', 'local_forum_delete_replies'), 'get');
    echo $OUTPUT->single_button($indexurl, get_string('deleteallreplies', 'local_forum_delete_replies'), 'get');
    echo $OUTPUT->single_button($courseurl, get_string('backtocourse', 'local_forum_delete_replies'), 'get');
    echo html_writer::end_div();

    echo $OUTPUT->footer();
    exit;
}

// Show confirmation page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletefromforum', 'local_forum_delete_replies') . ': ' . format_string($forum->name));

// Warning box.
echo $OUTPUT->box_start('generalbox alert alert-warning');
echo html_writer::tag('strong', get_string('warning', 'local_forum_delete_replies') . ': ');
echo get_string('warningmessage', 'local_forum_delete_replies');
echo $OUTPUT->box_end();

// Summary information.
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('h4', get_string('summary', 'local_forum_delete_replies'));

$summarylist = html_writer::start_tag('dl', ['class' => 'row']);
$summarylist .= html_writer::tag('dt', get_string('forum', 'local_forum_delete_replies'), ['class' => 'col-sm-4']);
$summarylist .= html_writer::tag('dd', format_string($forum->name), ['class' => 'col-sm-8']);
$summarylist .= html_writer::tag('dt', get_string('totaldiscussions', 'local_forum_delete_replies'), ['class' => 'col-sm-4']);
$summarylist .= html_writer::tag('dd', $summary['total_discussions'], ['class' => 'col-sm-8']);
$summarylist .= html_writer::tag('dt', get_string('totalreplies', 'local_forum_delete_replies'), ['class' => 'col-sm-4']);
$summarylist .= html_writer::tag('dd', html_writer::tag('strong', $summary['total_replies'], ['class' => 'text-danger']), ['class' => 'col-sm-8']);
$summarylist .= html_writer::end_tag('dl');
echo $summarylist;
echo $OUTPUT->box_end();

// Check if there are replies to delete.
if ($summary['total_replies'] == 0) {
    echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
    echo $OUTPUT->single_button($indexurl, get_string('backtocourse', 'local_forum_delete_replies'), 'get');
    echo $OUTPUT->footer();
    exit;
}

// Preview table.
echo html_writer::tag('h4', get_string('preview', 'local_forum_delete_replies'));
echo html_writer::tag('p', get_string('previewmessage', 'local_forum_delete_replies'));

$previewposts = $manager->get_replies_preview(50);

$table = new html_table();
$table->head = [
    get_string('discussionname', 'local_forum_delete_replies'),
    get_string('subject'),
    get_string('author', 'local_forum_delete_replies'),
    get_string('created', 'local_forum_delete_replies'),
];
$table->attributes['class'] = 'generaltable table-sm';

foreach ($previewposts as $post) {
    $table->data[] = [
        format_string($post->discussion_name),
        format_string($post->subject),
        fullname($post),
        userdate($post->created, get_string('strftimedatetimeshort', 'langconfig')),
    ];
}

if (count($previewposts) < $summary['total_replies']) {
    $remaining = $summary['total_replies'] - count($previewposts);
    $table->data[] = [
        html_writer::tag('em', get_string('andmore', 'local_forum_delete_replies', $remaining)),
        '',
        '',
        '',
    ];
}

echo html_writer::table($table);

// Confirmation message.
echo html_writer::tag('p', get_string('confirmdeletemessage', 'local_forum_delete_replies', [
    'forumname' => format_string($forum->name),
    'replycount' => $summary['total_replies'],
    'discussioncount' => $summary['discussions_with_replies'],
]));

// Confirmation buttons.
$confirmurl = new moodle_url('/local/forum_delete_replies/cleanup.php', [
    'forumid' => $forumid,
    'courseid' => $courseid,
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

$confirmurlfAST = new moodle_url('/local/forum_delete_replies/cleanup.php', [
    'forumid' => $forumid,
    'courseid' => $courseid,
    'confirm' => 1,
    'fastmode' => 1,
    'sesskey' => sesskey(),
]);

echo html_writer::start_div('mt-4');

// Standard delete button.
echo html_writer::link(
    $confirmurl,
    get_string('delete', 'local_forum_delete_replies'),
    ['class' => 'btn btn-danger mr-2']
);

// Fast mode button for large forums.
if ($summary['total_replies'] > 100) {
    echo html_writer::link(
        $confirmurlfAST,
        get_string('deletefast', 'local_forum_delete_replies'),
        ['class' => 'btn btn-warning mr-2', 'title' => get_string('deletefasttitle', 'local_forum_delete_replies')]
    );
}

// Cancel button.
echo html_writer::link(
    $indexurl,
    get_string('cancel', 'local_forum_delete_replies'),
    ['class' => 'btn btn-secondary']
);

echo html_writer::end_div();

echo $OUTPUT->footer();
