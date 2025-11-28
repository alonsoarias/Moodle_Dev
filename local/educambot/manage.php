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
 * Manage bot rules page.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_educambot_manage');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_url('/local/educambot/manage.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('managerules', 'local_educambot'));
$PAGE->set_heading(get_string('managerules', 'local_educambot'));

// Handle delete action.
if ($action === 'delete' && $id > 0) {
    require_sesskey();
    $DB->delete_records('local_educambot_rule', ['id' => $id]);
    redirect(new moodle_url('/local/educambot/manage.php'),
        get_string('ruledeleted', 'local_educambot'),
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Handle toggle enabled/disabled.
if ($action === 'toggle' && $id > 0) {
    require_sesskey();
    $rule = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
    $rule->enabled = $rule->enabled ? 0 : 1;
    $rule->timemodified = time();
    $DB->update_record('local_educambot_rule', $rule);
    redirect(new moodle_url('/local/educambot/manage.php'));
}

// Handle add/edit form.
if ($action === 'edit' || $action === 'add') {
    $mform = new \local_educambot\form\entry_form();

    // Load existing data if editing.
    if ($id > 0) {
        $rule = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
        $mform->set_data($rule);
    }

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/educambot/manage.php'));
    } else if ($data = $mform->get_data()) {
        $now = time();

        if ($data->id > 0) {
            // Update existing rule.
            $data->timemodified = $now;
            $DB->update_record('local_educambot_rule', $data);
            $message = get_string('ruleupdated', 'local_educambot');
        } else {
            // Create new rule.
            $data->timecreated = $now;
            $data->timemodified = $now;
            $DB->insert_record('local_educambot_rule', $data);
            $message = get_string('rulecreated', 'local_educambot');
        }

        redirect(new moodle_url('/local/educambot/manage.php'),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($id > 0 ? get_string('editrule', 'local_educambot') : get_string('addrule', 'local_educambot'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// List all rules (default action).
echo $OUTPUT->header();

// Add rule button.
$addurl = new moodle_url('/local/educambot/manage.php', ['action' => 'add']);
echo html_writer::link($addurl, get_string('addrule', 'local_educambot'),
    ['class' => 'btn btn-primary mb-3']);

// Get all rules.
$rules = $DB->get_records('local_educambot_rule', null, 'timecreated DESC');

if (empty($rules)) {
    echo $OUTPUT->notification(get_string('norules', 'local_educambot'), 'info');
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('pattern_header', 'local_educambot'),
        get_string('response_header', 'local_educambot'),
        get_string('options', 'local_educambot'),
        get_string('status_header', 'local_educambot'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($rules as $rule) {
        $editurl = new moodle_url('/local/educambot/manage.php', ['action' => 'edit', 'id' => $rule->id]);
        $deleteurl = new moodle_url('/local/educambot/manage.php',
            ['action' => 'delete', 'id' => $rule->id, 'sesskey' => sesskey()]);
        $toggleurl = new moodle_url('/local/educambot/manage.php',
            ['action' => 'toggle', 'id' => $rule->id, 'sesskey' => sesskey()]);
        $optionsurl = new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $rule->id]);

        // Truncate long text.
        $pattern = strlen($rule->pattern) > 60 ? substr($rule->pattern, 0, 57) . '...' : $rule->pattern;
        $response = strlen($rule->response) > 80 ? substr($rule->response, 0, 77) . '...' : $rule->response;

        // Count options for this rule.
        $optioncount = $DB->count_records('local_educambot_option', ['ruleid' => $rule->id]);
        $optionsbadge = html_writer::link($optionsurl,
            html_writer::tag('span', $optioncount, ['class' => 'badge badge-primary']) .
            ' ' . get_string('manageoptions', 'local_educambot'),
            ['class' => 'btn btn-sm btn-outline-primary']);

        // Status badge.
        if ($rule->enabled) {
            $status = html_writer::tag('span', get_string('status_enabled', 'local_educambot'),
                ['class' => 'badge badge-success']);
        } else {
            $status = html_writer::tag('span', get_string('status_disabled', 'local_educambot'),
                ['class' => 'badge badge-secondary']);
        }

        // Action links.
        $actions = html_writer::link($editurl, get_string('edit', 'local_educambot'),
            ['class' => 'btn btn-sm btn-secondary']);
        $actions .= ' ';
        $actions .= html_writer::link($toggleurl,
            $rule->enabled ? get_string('disable', 'local_educambot') : get_string('enable', 'local_educambot'),
            ['class' => 'btn btn-sm btn-info']);
        $actions .= ' ';
        $actions .= html_writer::link($deleteurl, get_string('delete', 'local_educambot'),
            [
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'return confirm("' . get_string('confirmdelete', 'local_educambot') . '");',
            ]);

        $table->data[] = [
            format_text($pattern, FORMAT_PLAIN),
            format_text($response, FORMAT_PLAIN),
            $optionsbadge,
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
