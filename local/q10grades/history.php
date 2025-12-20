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
 * Sync history page for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:viewlogs', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('synchistory', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'),
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('synchistory', 'local_q10grades'));

$syncmanager = new \local_q10grades\grade_sync_manager($courseid);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('synchistory', 'local_q10grades'));

// Navigation tabs.
$tabs = [];
$tabs[] = new tabobject('sync',
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]),
    get_string('syncgrades', 'local_q10grades'));
$tabs[] = new tabobject('mapping',
    new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
    get_string('coursemapping', 'local_q10grades'));
$tabs[] = new tabobject('items',
    new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
    get_string('q10items', 'local_q10grades'));
$tabs[] = new tabobject('upload',
    new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
    get_string('uploadgrades', 'local_q10grades'));
$tabs[] = new tabobject('history',
    new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]),
    get_string('synchistory', 'local_q10grades'));

echo $OUTPUT->tabtree($tabs, 'history');

// Get total count for pagination.
$totalcount = $DB->count_records('local_q10grades_sync_log', ['courseid' => $courseid]);

// Get history records.
$history = $syncmanager->get_sync_history($perpage, $page * $perpage);

if (empty($history)) {
    echo $OUTPUT->notification(get_string('nohistory', 'local_q10grades'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('date'),
        get_string('student', 'local_q10grades'),
        get_string('grade', 'grades'),
        get_string('status'),
        get_string('type', 'local_q10grades'),
        get_string('syncedby', 'local_q10grades'),
        get_string('details'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($history as $record) {
        $studentname = '-';
        if ($record->firstname && $record->lastname) {
            $studentname = $record->firstname . ' ' . $record->lastname;
        }

        $syncedby = '-';
        if ($record->synced_by_firstname && $record->synced_by_lastname) {
            $syncedby = $record->synced_by_firstname . ' ' . $record->synced_by_lastname;
        }

        $statusbadge = '';
        switch ($record->status) {
            case 'success':
                $statusbadge = html_writer::tag('span', get_string('success'), ['class' => 'badge badge-success']);
                break;
            case 'error':
                $statusbadge = html_writer::tag('span', get_string('error'), ['class' => 'badge badge-danger']);
                break;
            case 'pending':
                $statusbadge = html_writer::tag('span', get_string('pending', 'local_q10grades'), ['class' => 'badge badge-warning']);
                break;
            default:
                $statusbadge = html_writer::tag('span', $record->status, ['class' => 'badge badge-secondary']);
        }

        $typelabel = get_string('synctype_' . $record->sync_type, 'local_q10grades');

        $details = '';
        if (!empty($record->error_message)) {
            $details = html_writer::tag('small', $record->error_message, ['class' => 'text-danger']);
        }

        $table->data[] = [
            userdate($record->timecreated, get_string('strftimedatetime', 'langconfig')),
            $studentname,
            $record->grade_sent !== null ? number_format($record->grade_sent, 2) : '-',
            $statusbadge,
            $typelabel,
            $syncedby,
            $details,
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    $baseurl = new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
}

// Stats summary.
$stats = $syncmanager->get_sync_stats();

echo html_writer::start_div('card mt-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('syncstatistics', 'local_q10grades'), ['class' => 'card-title']);

$statshtml = html_writer::start_div('row');
$statshtml .= html_writer::start_div('col-md-4');
$statshtml .= html_writer::tag('div', get_string('totalsyncs', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['total_syncs'], ['class' => 'h4']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::start_div('col-md-4');
$statshtml .= html_writer::tag('div', get_string('successful', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['successful'], ['class' => 'h4 text-success']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::start_div('col-md-4');
$statshtml .= html_writer::tag('div', get_string('failed', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['failed'], ['class' => 'h4 text-danger']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::end_div();

echo $statshtml;
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
