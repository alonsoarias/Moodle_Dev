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
 * Administration console for chatbot intents.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

use local_chatbot\form\intent_form;

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_chatbot_intents');

require_capability('local/chatbot:manage', context_system::instance());

$baseurl = new moodle_url('/local/chatbot/admin/intents.php');
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

if ($action === 'delete' && $id && confirm_sesskey()) {
    if ($DB->record_exists('local_chatbot_intents', ['id' => $id])) {
        $DB->delete_records('local_chatbot_intents', ['id' => $id]);
        local_chatbot_reset_runtime_cache();
        redirect($baseurl, get_string('intent_deleted', 'local_chatbot'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$current = null;
if ($action === 'edit' && $id) {
    $current = $DB->get_record('local_chatbot_intents', ['id' => $id], '*', MUST_EXIST);
}

$mform = new intent_form($baseurl);
if ($current) {
    $mform->set_data($current);
}

if ($mform->is_cancelled()) {
    redirect($baseurl);
} else if ($data = $mform->get_data()) {
    $record = (object) [
        'name' => $data->name,
        'keywords' => trim((string)$data->keywords),
        'response' => trim((string)$data->response),
        'isfallback' => empty($data->isfallback) ? 0 : 1,
        'enabled' => empty($data->enabled) ? 0 : 1,
        'sortorder' => (int)$data->sortorder,
        'timemodified' => time(),
    ];

    if (!empty($data->id)) {
        $record->id = $data->id;
        $DB->update_record('local_chatbot_intents', $record);
    } else {
        $record->timecreated = time();
        $record->id = $DB->insert_record('local_chatbot_intents', $record);
    }

    if ($record->isfallback) {
        $DB->set_field_select('local_chatbot_intents', 'isfallback', 0, 'id <> ?', [$record->id]);
        $DB->set_field('local_chatbot_intents', 'isfallback', 1, ['id' => $record->id]);
    }

    local_chatbot_reset_runtime_cache();

    redirect($baseurl, get_string('intent_saved', 'local_chatbot'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$PAGE->set_title(get_string('manage_intents', 'local_chatbot'));
$PAGE->set_heading(get_string('manage_intents', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_intents', 'local_chatbot'));

echo $OUTPUT->notification(get_string('intents_intro', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);

if ($action === 'edit') {
    echo $OUTPUT->heading(get_string('intent_edit_heading', 'local_chatbot'), 3);
} else {
    echo $OUTPUT->heading(get_string('intent_add_heading', 'local_chatbot'), 3);
}

$mform->display();

echo $OUTPUT->heading(get_string('intent_table_heading', 'local_chatbot'), 3);

$table = new flexible_table('local-chatbot-intents');
$table->define_columns(['name', 'keywords', 'response', 'status', 'sortorder', 'actions']);
$table->define_headers([
    get_string('intent_name', 'local_chatbot'),
    get_string('intent_keywords', 'local_chatbot'),
    get_string('intent_response', 'local_chatbot'),
    get_string('intent_status', 'local_chatbot'),
    get_string('intent_sortorder', 'local_chatbot'),
    get_string('actions'),
]);
$table->sortable(true, 'sortorder', SORT_ASC);
$table->set_attribute('class', 'generaltable generalbox local-chatbot-intents-table');
$table->setup();

$records = $DB->get_records('local_chatbot_intents', null, 'sortorder ASC, name ASC');
foreach ($records as $record) {
    $keywords = implode(', ', local_chatbot_parse_keywords($record->keywords));
    if ($keywords === '') {
        $keywords = html_writer::span(get_string('intent_no_keywords', 'local_chatbot'), 'text-muted');
    }

    $statusparts = [];
    $statusparts[] = $record->enabled ? get_string('enabled', 'core') : get_string('disabled', 'core');
    if ($record->isfallback) {
        $statusparts[] = get_string('intent_status_fallback', 'local_chatbot');
    }
    $status = implode(' · ', $statusparts);

    $actions = [];
    $actions[] = html_writer::link(new moodle_url($baseurl, ['action' => 'edit', 'id' => $record->id]), get_string('edit'));
    $actions[] = html_writer::link(
        new moodle_url($baseurl, ['action' => 'delete', 'id' => $record->id, 'sesskey' => sesskey()]),
        get_string('delete'),
        ['data-confirm' => get_string('intent_delete_confirm', 'local_chatbot', $record->name)]
    );

    $table->add_data([
        format_string($record->name),
        format_string($keywords),
        format_text($record->response, FORMAT_PLAIN),
        $status,
        (int)$record->sortorder,
        implode(' | ', $actions),
    ]);
}

$table->finish_output();

echo $OUTPUT->footer();
