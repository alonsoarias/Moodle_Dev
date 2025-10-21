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
 * Management interface for Educam Bot knowledge base.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once(__DIR__ . '/classes/form/entry_form.php');

$context = context_system::instance();
require_login();
require_capability('local/educambot:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educambot/manage.php'));
$PAGE->set_title(get_string('manageentries', 'local_educambot'));
$PAGE->set_heading(get_string('manageentries', 'local_educambot'));

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$output = $PAGE->get_renderer('core');
$baseurl = new moodle_url('/local/educambot/manage.php');

$cache = cache::make('local_educambot', 'rules');

if ($action === 'delete' && $id) {
    require_sesskey();
    if ($record = $DB->get_record('local_educambot_rule', ['id' => $id])) {
        $DB->delete_records('local_educambot_rule', ['id' => $id]);
        $cache->delete('all');
        redirect($baseurl, get_string('deleted', 'local_educambot'));
    } else {
        print_error('invalidrecord', 'error');
    }
}

if (in_array($action, ['add', 'edit'], true)) {
    if ($action === 'edit') {
        $record = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
    } else {
        $record = null;
    }

    $form = new \local_educambot\form\entry_form($baseurl, ['persistent' => $record]);

    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $form->get_data()) {
        $saved = new stdClass();
        $saved->pattern = trim($data->pattern);
        $saved->synonyms = trim($data->synonyms ?? '');
        $saved->keywords = trim($data->keywords ?? '');
        $saved->response = trim($data->response['text'] ?? '');
        $saved->roles = !empty($data->roles) ? implode(',', (array)$data->roles) : null;
        $saved->contexts = trim($data->contexts ?? '');
        $saved->suggested = !empty($data->suggested) ? 1 : 0;
        $saved->enabled = !empty($data->enabled) ? 1 : 0;
        $saved->timemodified = time();

        if ($action === 'edit') {
            $saved->id = $record->id;
            $saved->timecreated = $record->timecreated;
            $DB->update_record('local_educambot_rule', $saved);
        } else {
            $saved->timecreated = time();
            $id = $DB->insert_record('local_educambot_rule', $saved);
        }

        $cache->delete('all');
        redirect($baseurl, get_string('saved', 'local_educambot'));
    } else {
        if ($record) {
            $form->set_data($record);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string($action === 'edit' ? 'editentry' : 'addentry', 'local_educambot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;
    }
}

$records = $DB->get_records('local_educambot_rule', null, 'timemodified DESC');

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageentries', 'local_educambot'));

$addurl = new moodle_url('/local/educambot/manage.php', ['action' => 'add']);
echo $OUTPUT->single_button($addurl, get_string('addentry', 'local_educambot'));

if (!$records) {
    echo $OUTPUT->notification(get_string('noentries', 'local_educambot'), 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('pattern', 'local_educambot'),
    get_string('status', 'local_educambot'),
    get_string('suggested', 'local_educambot'),
    get_string('timemodified', 'core'),
    get_string('actions', 'local_educambot'),
];

foreach ($records as $record) {
    $status = $record->enabled ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot');
    $suggested = $record->suggested ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot');
    $editurl = new moodle_url('/local/educambot/manage.php', ['action' => 'edit', 'id' => $record->id]);
    $deleteurl = new moodle_url('/local/educambot/manage.php', ['action' => 'delete', 'id' => $record->id, 'sesskey' => sesskey()]);
    $deleteaction = new \core\output\confirm_action(get_string('confirmdelete', 'local_educambot', format_string($record->pattern)));
    $actions = $OUTPUT->action_link($editurl, get_string('edit')) . ' | ' .
        $OUTPUT->action_link($deleteurl, get_string('delete'), $deleteaction);

    $table->data[] = [
        format_string($record->pattern),
        $status,
        $suggested,
        userdate($record->timemodified),
        $actions,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
