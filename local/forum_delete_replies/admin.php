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
 * Admin page for forum reply deletion - shows all available options
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/forum/lib.php');
require_once(__DIR__ . '/classes/bulk_manager.php');

use local_forum_delete_replies\bulk_manager;

require_login();
require_capability('moodle/site:config', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/forum_delete_replies/admin.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_forum_delete_replies'));
$PAGE->set_heading(get_string('pluginname', 'local_forum_delete_replies'));
$PAGE->set_pagelayout('admin');

$baseurl = new moodle_url('/local/forum_delete_replies/admin.php');

// Handle site-wide deletion.
if ($action === 'site' && $confirm && confirm_sesskey()) {
    $totalreplies = bulk_manager::get_site_reply_count();

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleting', 'local_forum_delete_replies'));

    if ($totalreplies == 0) {
        echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
        echo $OUTPUT->single_button($baseurl, get_string('back'), 'get');
        echo $OUTPUT->footer();
        die;
    }

    $progressbar = new progress_bar('sitedeleteprogress', 500, true);
    $progressbar->create();

    $progresscallback = function($current, $total, $message) use ($progressbar) {
        $progressbar->update($current, $total, $message);
    };

    $result = bulk_manager::delete_site_replies_fast($progresscallback);
    $progressbar->update_full(100, get_string('completed', 'local_forum_delete_replies'));

    echo html_writer::empty_tag('br');

    if ($result['errors'] == 0 && $result['deleted'] > 0) {
        echo $OUTPUT->notification(
            get_string('deletesuccess', 'local_forum_delete_replies', [
                'deleted' => $result['deleted'],
                'discussions' => 'all',
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
    }

    echo $OUTPUT->single_button($baseurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    die;
}

// Handle course-specific deletion.
if ($action === 'course' && $courseid && $confirm && confirm_sesskey()) {
    $course = get_course($courseid);

    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleting', 'local_forum_delete_replies') . ': ' . format_string($course->fullname));

    $progressbar = new progress_bar('coursedeleteprogress', 500, true);
    $progressbar->create();

    $progresscallback = function($current, $total, $message) use ($progressbar) {
        $progressbar->update($current, $total, $message);
    };

    $result = bulk_manager::delete_course_replies_fast($courseid, $progresscallback);
    $progressbar->update_full(100, get_string('completed', 'local_forum_delete_replies'));

    echo html_writer::empty_tag('br');

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
    }

    echo $OUTPUT->single_button($baseurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    die;
}

// Main page - show options.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'local_forum_delete_replies'));

// Get stats.
$totalreplies = bulk_manager::get_site_reply_count();

// Info box.
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('p', get_string('admindescription', 'local_forum_delete_replies'));
echo html_writer::tag('p',
    html_writer::tag('strong', get_string('totalreplies', 'local_forum_delete_replies') . ': ') .
    html_writer::tag('span', number_format($totalreplies),
        ['class' => 'badge ' . ($totalreplies > 0 ? 'badge-warning bg-warning' : 'badge-secondary bg-secondary')])
);
echo $OUTPUT->box_end();

// Option 1: Delete from specific course.
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('h4', get_string('deletefromcourse', 'local_forum_delete_replies'));
echo html_writer::tag('p', get_string('deletefromcoursedesc', 'local_forum_delete_replies'), ['class' => 'text-muted']);

// Course selector form.
$courses = get_courses('all', 'c.fullname ASC', 'c.id, c.fullname, c.shortname');
unset($courses[SITEID]); // Remove site course.

if (!empty($courses)) {
    $courseoptions = [];
    foreach ($courses as $course) {
        $replycount = bulk_manager::get_course_reply_count($course->id);
        $courseoptions[$course->id] = format_string($course->fullname) . ' (' . $replycount . ' ' .
            strtolower(get_string('replies', 'local_forum_delete_replies')) . ')';
    }

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $baseurl->out(false), 'class' => 'form-inline']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'course']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'confirm', 'value' => '1']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    echo html_writer::select($courseoptions, 'courseid', '', ['' => get_string('choosedots')],
        ['class' => 'form-control mr-2', 'id' => 'courseselector']);

    echo html_writer::tag('button', get_string('deleteallreplies', 'local_forum_delete_replies'),
        ['type' => 'submit', 'class' => 'btn btn-danger', 'id' => 'deletecoursebutton']);

    echo html_writer::end_tag('form');
} else {
    echo $OUTPUT->notification(get_string('nocourses', 'local_forum_delete_replies'), 'info');
}
echo $OUTPUT->box_end();

// Option 2: Delete from entire site.
echo $OUTPUT->box_start('generalbox');
echo html_writer::tag('h4', get_string('sitewidedelete', 'local_forum_delete_replies'));
echo html_writer::tag('p', get_string('sitewidedeletedesc', 'local_forum_delete_replies'), ['class' => 'text-muted']);

if ($totalreplies > 0) {
    $siteconfirmurl = new moodle_url('/local/forum_delete_replies/admin.php', [
        'action' => 'site',
        'confirm' => 1,
        'sesskey' => sesskey(),
    ]);

    echo html_writer::tag('p',
        html_writer::tag('strong', get_string('totalreplies', 'local_forum_delete_replies') . ': ') .
        number_format($totalreplies)
    );

    echo html_writer::link(
        $siteconfirmurl,
        html_writer::tag('i', '', ['class' => 'fa fa-trash mr-2']) .
        get_string('deleteallnow', 'local_forum_delete_replies'),
        ['class' => 'btn btn-danger', 'onclick' => "return confirm('" .
            addslashes_js(get_string('sitewideconfirm', 'local_forum_delete_replies', number_format($totalreplies))) . "');"]
    );
} else {
    echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
}
echo $OUTPUT->box_end();

// JavaScript to require course selection.
echo html_writer::script("
    document.getElementById('deletecoursebutton').addEventListener('click', function(e) {
        var selector = document.getElementById('courseselector');
        if (!selector.value) {
            e.preventDefault();
            alert('" . addslashes_js(get_string('selectcourse', 'local_forum_delete_replies')) . "');
            return false;
        }
        return confirm('" . addslashes_js(get_string('confirmdeletecoursemsg', 'local_forum_delete_replies')) . "');
    });
");

echo $OUTPUT->footer();
