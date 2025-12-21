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
 * Schedule management page for educambot.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_educambot\bot\schedule_checker;

// Admin setup.
admin_externalpage_setup('local_educambot_schedule');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

$PAGE->set_url(new moodle_url('/local/educambot/schedule.php'));
$PAGE->set_title(get_string('manageschedule', 'local_educambot'));
$PAGE->set_heading(get_string('manageschedule', 'local_educambot'));

$baseurl = new moodle_url('/local/educambot/schedule.php');

// Process form submission.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $schedules = $DB->get_records('local_educambot_schedule', [], 'dayofweek ASC');

    foreach ($schedules as $schedule) {
        $enabled = optional_param('enabled_' . $schedule->id, 0, PARAM_INT);
        $timefrom = optional_param('timefrom_' . $schedule->id, '00:00', PARAM_TEXT);
        $timeto = optional_param('timeto_' . $schedule->id, '23:59', PARAM_TEXT);

        // Validate time format.
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timefrom)) {
            $timefrom = '00:00';
        }
        if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $timeto)) {
            $timeto = '23:59';
        }

        schedule_checker::update_schedule($schedule->id, $timefrom, $timeto, $enabled);
    }

    \core\notification::success(get_string('scheduleupdated', 'local_educambot'));
    redirect($baseurl);
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageschedule', 'local_educambot'));

// Help text.
echo html_writer::tag('p', get_string('schedule_help', 'local_educambot'), ['class' => 'alert alert-info']);

// Check if schedule is enabled.
$scheduleenabled = get_config('local_educambot', 'scheduleenabled');
if (!$scheduleenabled) {
    echo html_writer::tag('div', get_string('schedule_disabled_notice', 'local_educambot'), ['class' => 'alert alert-warning']);
}

// Get all schedules.
$schedules = schedule_checker::get_all_schedules();
$daynames = schedule_checker::get_day_names();

// Build form.
echo html_writer::start_tag('form', [
    'method' => 'post',
    'action' => $baseurl->out(false),
    'class' => 'schedule-form',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

// Table.
$table = new html_table();
$table->head = [
    get_string('dayofweek', 'local_educambot'),
    get_string('timefrom', 'local_educambot'),
    get_string('timeto', 'local_educambot'),
    get_string('enabled', 'local_educambot'),
];
$table->attributes['class'] = 'generaltable schedule-table';

foreach ($schedules as $schedule) {
    $dayname = $daynames[$schedule->dayofweek];

    // Time from input.
    $timefrom = html_writer::empty_tag('input', [
        'type' => 'time',
        'name' => 'timefrom_' . $schedule->id,
        'value' => $schedule->timefrom,
        'class' => 'form-control',
    ]);

    // Time to input.
    $timeto = html_writer::empty_tag('input', [
        'type' => 'time',
        'name' => 'timeto_' . $schedule->id,
        'value' => $schedule->timeto,
        'class' => 'form-control',
    ]);

    // Enabled checkbox.
    $enabledattrs = [
        'type' => 'checkbox',
        'name' => 'enabled_' . $schedule->id,
        'value' => '1',
        'class' => 'form-check-input',
    ];
    if ($schedule->enabled) {
        $enabledattrs['checked'] = 'checked';
    }
    $enabled = html_writer::empty_tag('input', $enabledattrs);

    $table->data[] = [
        html_writer::tag('strong', $dayname),
        $timefrom,
        $timeto,
        html_writer::tag('div', $enabled, ['class' => 'form-check']),
    ];
}

echo html_writer::table($table);

// Submit button.
echo html_writer::tag('button', get_string('savechanges'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);

echo html_writer::end_tag('form');

// Current status.
echo html_writer::tag('h4', get_string('currentstatus', 'local_educambot'), ['class' => 'mt-4']);
if (schedule_checker::is_available()) {
    echo html_writer::tag('div', get_string('botonline', 'local_educambot'), ['class' => 'alert alert-success']);
} else {
    echo html_writer::tag('div', schedule_checker::get_offline_message(), ['class' => 'alert alert-warning']);
}

echo $OUTPUT->footer();
