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
 * Site-wide delete all forum replies (Admin only)
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

$confirm = optional_param('confirm', 0, PARAM_INT);

$PAGE->set_url(new moodle_url('/local/forum_delete_replies/admin.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('sitewidedelete', 'local_forum_delete_replies'));
$PAGE->set_heading(get_string('sitewidedelete', 'local_forum_delete_replies'));
$PAGE->set_pagelayout('admin');

$adminurl = new moodle_url('/admin/search.php');

// Get site-wide count.
$totalreplies = bulk_manager::get_site_reply_count();

// Execute deletion if confirmed.
if ($confirm && confirm_sesskey()) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('deleting', 'local_forum_delete_replies'));

    if ($totalreplies == 0) {
        echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
        echo $OUTPUT->single_button($adminurl, get_string('back'), 'get');
        echo $OUTPUT->footer();
        die;
    }

    // Progress bar.
    $progressbar = new progress_bar('sitedeleteprogress', 500, true);
    $progressbar->create();

    $progresscallback = function($current, $total, $message) use ($progressbar) {
        $progressbar->update($current, $total, $message);
    };

    // Execute site-wide deletion.
    $result = bulk_manager::delete_site_replies_fast($progresscallback);

    $progressbar->update_full(100, get_string('completed', 'local_forum_delete_replies'));

    echo html_writer::empty_tag('br');

    // Show results.
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

        if (!empty($result['error_messages'])) {
            echo html_writer::start_tag('pre');
            foreach ($result['error_messages'] as $error) {
                echo s($error) . "\n";
            }
            echo html_writer::end_tag('pre');
        }
    }

    echo html_writer::start_div('mt-4');
    echo $OUTPUT->single_button($adminurl, get_string('back'), 'get');
    echo html_writer::end_div();

    echo $OUTPUT->footer();
    die;
}

// Show confirmation page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sitewidedelete', 'local_forum_delete_replies'));

if ($totalreplies == 0) {
    echo $OUTPUT->notification(get_string('noreplies', 'local_forum_delete_replies'), 'info');
    echo $OUTPUT->single_button($adminurl, get_string('back'), 'get');
    echo $OUTPUT->footer();
    die;
}

// Big warning.
echo $OUTPUT->box_start('generalbox alert alert-danger text-center');
echo html_writer::tag('h2',
    html_writer::tag('i', '', ['class' => 'fa fa-exclamation-triangle']) . ' ' .
    get_string('warning', 'local_forum_delete_replies')
);
echo html_writer::tag('p', get_string('sitewidedeletewarning', 'local_forum_delete_replies'),
    ['style' => 'font-size: 1.3em;']);
echo html_writer::tag('p',
    html_writer::tag('strong', get_string('totalreplies', 'local_forum_delete_replies') . ': ') .
    html_writer::tag('span', number_format($totalreplies), ['class' => 'badge badge-danger bg-danger', 'style' => 'font-size: 1.5em;'])
);
echo $OUTPUT->box_end();

// Confirmation.
echo html_writer::tag('p', get_string('sitewideconfirm', 'local_forum_delete_replies', number_format($totalreplies)),
    ['class' => 'lead text-center']);

// Buttons.
$confirmurl = new moodle_url('/local/forum_delete_replies/admin.php', [
    'confirm' => 1,
    'sesskey' => sesskey(),
]);

echo html_writer::start_div('text-center my-4');

echo html_writer::link(
    $confirmurl,
    html_writer::tag('i', '', ['class' => 'fa fa-trash mr-2']) .
    get_string('deleteallnow', 'local_forum_delete_replies'),
    ['class' => 'btn btn-danger btn-lg mr-3', 'style' => 'font-size: 1.2em;']
);

echo html_writer::link(
    $adminurl,
    get_string('cancel'),
    ['class' => 'btn btn-secondary btn-lg']
);

echo html_writer::end_div();

echo $OUTPUT->footer();
