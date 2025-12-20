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
 * Main sync page for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:sync', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('syncgrades', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

// Navigation.
$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'));

// Check API configuration.
$apiclient = new \local_q10grades\q10_api_client();
$apiConfigured = $apiclient->is_configured();

// Get sync manager.
$syncmanager = new \local_q10grades\grade_sync_manager($courseid);
$mapping = $syncmanager->get_mapping();
$hasMapping = $syncmanager->has_mapping();

// Get Q10 items.
$q10items = $DB->get_records('local_q10grades_items', ['courseid' => $courseid, 'enabled' => 1], 'sortorder');

// Get sync stats.
$stats = $syncmanager->get_sync_stats();

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('syncgrades', 'local_q10grades'));

// Show warnings if not configured.
if (!$apiConfigured) {
    echo $OUTPUT->notification(
        get_string('apinotconfigured', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/admin/settings.php', ['section' => 'local_q10grades']),
            get_string('configureapi', 'local_q10grades')
        ),
        'warning'
    );
}

if (!$hasMapping) {
    echo $OUTPUT->notification(
        get_string('nomapping', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
            get_string('configuremapping', 'local_q10grades')
        ),
        'warning'
    );
}

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

echo $OUTPUT->tabtree($tabs, 'sync');

// Stats box.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('syncstatistics', 'local_q10grades'), ['class' => 'card-title']);

$statshtml = html_writer::start_div('row');
$statshtml .= html_writer::start_div('col-md-3');
$statshtml .= html_writer::tag('div', get_string('totalsyncs', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['total_syncs'], ['class' => 'h4']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::start_div('col-md-3');
$statshtml .= html_writer::tag('div', get_string('successful', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['successful'], ['class' => 'h4 text-success']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::start_div('col-md-3');
$statshtml .= html_writer::tag('div', get_string('failed', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['failed'], ['class' => 'h4 text-danger']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::start_div('col-md-3');
$statshtml .= html_writer::tag('div', get_string('lastsync', 'local_q10grades'), ['class' => 'text-muted']);
$statshtml .= html_writer::tag('div', $stats['last_sync'] ?? get_string('never', 'local_q10grades'), ['class' => 'h6']);
$statshtml .= html_writer::end_div();
$statshtml .= html_writer::end_div();

echo $statshtml;
echo html_writer::end_div();
echo html_writer::end_div();

// Mapping info.
if ($hasMapping) {
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('currentmapping', 'local_q10grades'), ['class' => 'card-title']);

    $table = new html_table();
    $table->attributes['class'] = 'table table-sm';
    $table->data = [];
    $table->data[] = [get_string('q10subjectid', 'local_q10grades'), $mapping->q10_subject_id];
    if (!empty($mapping->q10_period_id)) {
        $table->data[] = [get_string('q10periodid', 'local_q10grades'), $mapping->q10_period_id];
    }
    if (!empty($mapping->q10_group_id)) {
        $table->data[] = [get_string('q10groupid', 'local_q10grades'), $mapping->q10_group_id];
    }
    $table->data[] = [get_string('status'), $mapping->enabled ?
        html_writer::tag('span', get_string('enabled', 'local_q10grades'), ['class' => 'badge badge-success']) :
        html_writer::tag('span', get_string('disabled', 'local_q10grades'), ['class' => 'badge badge-secondary'])];

    echo html_writer::table($table);

    echo html_writer::link(
        new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
        get_string('editmapping', 'local_q10grades'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );

    echo html_writer::end_div();
    echo html_writer::end_div();
}

// Configured Q10 items.
echo html_writer::start_div('card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::tag('h5', get_string('configuredq10items', 'local_q10grades'), ['class' => 'card-title']);

if (empty($q10items)) {
    echo html_writer::tag('p', get_string('noitems', 'local_q10grades'), ['class' => 'text-muted']);
    echo html_writer::link(
        new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'action' => 'add']),
        get_string('additem', 'local_q10grades'),
        ['class' => 'btn btn-primary']
    );
} else {
    $calculationtypes = [
        'average' => get_string('formulaaverage', 'local_q10grades'),
        'weighted' => get_string('formulaweighted', 'local_q10grades'),
        'sum' => get_string('formulasum', 'local_q10grades'),
        'highest' => get_string('formulahighest', 'local_q10grades'),
        'lowest' => get_string('formulalowest', 'local_q10grades'),
    ];

    $table = new html_table();
    $table->head = [
        get_string('q10itemname', 'local_q10grades'),
        get_string('q10itemid', 'local_q10grades'),
        get_string('calculationtype', 'local_q10grades'),
        get_string('selectedactivities', 'local_q10grades'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($q10items as $item) {
        $selectedactivities = json_decode($item->selected_activities, true) ?? [];
        $activitycount = count($selectedactivities);

        $actions = html_writer::link(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'action' => 'edit', 'itemid' => $item->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit'))
        );

        $table->data[] = [
            html_writer::tag('strong', s($item->q10_item_name)),
            html_writer::tag('code', s($item->q10_item_id)),
            $calculationtypes[$item->calculation_type] ?? $item->calculation_type,
            $activitycount . ' ' . get_string('activities', 'local_q10grades'),
            $actions,
        ];
    }

    echo html_writer::table($table);

    echo html_writer::link(
        new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
        get_string('edititem', 'local_q10grades'),
        ['class' => 'btn btn-outline-primary']
    );
}

echo html_writer::end_div();
echo html_writer::end_div();

// Quick sync actions.
if ($apiConfigured && $hasMapping && !empty($q10items)) {
    echo html_writer::start_div('card mb-4');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('quickactions', 'local_q10grades'), ['class' => 'card-title']);

    echo html_writer::start_div('btn-group', ['role' => 'group']);

    echo html_writer::link(
        new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
        get_string('uploadgrades', 'local_q10grades'),
        ['class' => 'btn btn-primary']
    );

    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
