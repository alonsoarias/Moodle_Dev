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
 * Administration console for chatbot entities: quick actions and suggestions.
 *
 * @package    local_chatbot
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/local/chatbot/lib.php');

use local_chatbot\form\quickaction_form;
use local_chatbot\form\suggestion_form;

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_chatbot_entities');
require_capability('local/chatbot:manage', context_system::instance());

$baseurl = new moodle_url('/local/chatbot/admin/entities.php');
$tab = optional_param('tab', 'quickactions', PARAM_ALPHA);
$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$tabs = [
    new tabobject('quickactions', new moodle_url($baseurl, ['tab' => 'quickactions']),
        get_string('entities_quickactions_tab', 'local_chatbot')),
    new tabobject('suggestions', new moodle_url($baseurl, ['tab' => 'suggestions']),
        get_string('entities_suggestions_tab', 'local_chatbot')),
];

$PAGE->set_title(get_string('manage_entities', 'local_chatbot'));
$PAGE->set_heading(get_string('manage_entities', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('manage_entities', 'local_chatbot'));

echo $OUTPUT->tabtree($tabs, $tab);

echo $OUTPUT->notification(get_string('entities_intro', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);

if ($tab === 'quickactions') {
    $formurl = new moodle_url($baseurl, ['tab' => 'quickactions']);
    $current = null;
    if ($action === 'edit' && $id) {
        $current = $DB->get_record('local_chatbot_quickacts', ['id' => $id], '*', MUST_EXIST);
    }

    $form = new quickaction_form($formurl, ['id' => $current->id ?? 0]);
    if ($current) {
        $form->set_data($current);
    }

    if ($action === 'delete' && $id && confirm_sesskey()) {
        if ($DB->record_exists('local_chatbot_quickacts', ['id' => $id])) {
            $DB->delete_records('local_chatbot_quickacts', ['id' => $id]);
            local_chatbot_reset_runtime_cache();
            redirect($formurl, get_string('quickaction_deleted', 'local_chatbot'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    if ($form->is_cancelled()) {
        redirect($formurl);
    } else if ($data = $form->get_data()) {
        $record = (object) [
            'actionkey' => trim((string)$data->actionkey),
            'name' => trim((string)$data->name),
            'type' => $data->type,
            'payload' => trim((string)$data->payload),
            'description' => trim((string)$data->description),
            'icon' => trim((string)$data->icon),
            'enabled' => empty($data->enabled) ? 0 : 1,
            'sortorder' => (int)$data->sortorder,
            'timemodified' => time(),
        ];

        if ($record->type === 'navigate' && $record->payload !== '') {
            if (!preg_match('/^(https?:\/\/|\/)/', $record->payload)) {
                $record->payload = '/' . ltrim($record->payload, '/');
            }
        }

        if (!empty($data->id)) {
            $record->id = $data->id;
            $DB->update_record('local_chatbot_quickacts', $record);
        } else {
            $record->timecreated = time();
            $record->id = $DB->insert_record('local_chatbot_quickacts', $record);
        }

        local_chatbot_reset_runtime_cache();

        redirect($formurl, get_string('quickaction_saved', 'local_chatbot'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('quickaction_edit_heading', 'local_chatbot'), 3);
    } else {
        echo $OUTPUT->heading(get_string('quickaction_add_heading', 'local_chatbot'), 3);
    }
    $form->display();

    echo $OUTPUT->heading(get_string('quickaction_table_heading', 'local_chatbot'), 3);

    $table = new flexible_table('local-chatbot-quickactions');
    $table->define_columns(['name', 'actionkey', 'type', 'payload', 'status', 'sortorder', 'actions']);
    $table->define_headers([
        get_string('quickaction_name', 'local_chatbot'),
        get_string('quickaction_actionkey', 'local_chatbot'),
        get_string('quickaction_type', 'local_chatbot'),
        get_string('quickaction_payload', 'local_chatbot'),
        get_string('quickaction_status', 'local_chatbot'),
        get_string('quickaction_sortorder', 'local_chatbot'),
        get_string('actions'),
    ]);
    $table->sortable(true, 'sortorder', SORT_ASC);
    $table->set_attribute('class', 'generaltable generalbox local-chatbot-quickactions-table');
    $table->setup();

    $quickactions = $DB->get_records('local_chatbot_quickacts', null, 'sortorder ASC, name ASC');
    foreach ($quickactions as $qa) {
        $statusparts = [];
        $statusparts[] = $qa->enabled ? get_string('enabled', 'core') : get_string('disabled', 'core');
        $statusparts[] = local_chatbot_get_quickaction_type_label($qa->type);
        $status = implode(' · ', $statusparts);

        $actions = [];
        $actions[] = html_writer::link(new moodle_url($formurl, ['action' => 'edit', 'id' => $qa->id]), get_string('edit'));
        $actions[] = html_writer::link(new moodle_url($formurl, [
            'action' => 'delete',
            'id' => $qa->id,
            'sesskey' => sesskey(),
        ]), get_string('delete'), [
            'data-confirm' => get_string('quickaction_delete_confirm', 'local_chatbot', $qa->name),
        ]);

        $table->add_data([
            format_string($qa->name),
            format_string($qa->actionkey),
            local_chatbot_get_quickaction_type_label($qa->type),
            format_text($qa->payload, FORMAT_PLAIN),
            $status,
            (int)$qa->sortorder,
            implode(' | ', $actions),
        ]);
    }

    $table->finish_output();
} else {
    $formurl = new moodle_url($baseurl, ['tab' => 'suggestions']);
    $current = null;
    if ($action === 'edit' && $id) {
        $current = $DB->get_record('local_chatbot_suggestions', ['id' => $id], '*', MUST_EXIST);
    }

    if ($action === 'delete' && $id && confirm_sesskey()) {
        if ($DB->record_exists('local_chatbot_suggestions', ['id' => $id])) {
            $DB->delete_records('local_chatbot_suggestions', ['id' => $id]);
            local_chatbot_reset_runtime_cache();
            redirect($formurl, get_string('suggestion_deleted', 'local_chatbot'), null,
                \core\output\notification::NOTIFY_SUCCESS);
        }
    }

    $form = new suggestion_form($formurl);
    if ($current) {
        $form->set_data($current);
    }

    if ($form->is_cancelled()) {
        redirect($formurl);
    } else if ($data = $form->get_data()) {
        $record = (object) [
            'text' => trim((string)$data->text),
            'mode' => $data->mode,
            'target' => trim((string)$data->target),
            'icon' => trim((string)$data->icon),
            'enabled' => empty($data->enabled) ? 0 : 1,
            'sortorder' => (int)$data->sortorder,
            'timemodified' => time(),
        ];

        if (!empty($data->id)) {
            $record->id = $data->id;
            $DB->update_record('local_chatbot_suggestions', $record);
        } else {
            $record->timecreated = time();
            $record->id = $DB->insert_record('local_chatbot_suggestions', $record);
        }

        local_chatbot_reset_runtime_cache();

        redirect($formurl, get_string('suggestion_saved', 'local_chatbot'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    if ($action === 'edit') {
        echo $OUTPUT->heading(get_string('suggestion_edit_heading', 'local_chatbot'), 3);
    } else {
        echo $OUTPUT->heading(get_string('suggestion_add_heading', 'local_chatbot'), 3);
    }
    $form->display();

    echo $OUTPUT->heading(get_string('suggestion_table_heading', 'local_chatbot'), 3);

    $table = new flexible_table('local-chatbot-suggestions');
    $table->define_columns(['text', 'mode', 'target', 'icon', 'status', 'sortorder', 'actions']);
    $table->define_headers([
        get_string('suggestion_text', 'local_chatbot'),
        get_string('suggestion_mode', 'local_chatbot'),
        get_string('suggestion_target', 'local_chatbot'),
        get_string('suggestion_icon', 'local_chatbot'),
        get_string('suggestion_status', 'local_chatbot'),
        get_string('suggestion_sortorder', 'local_chatbot'),
        get_string('actions'),
    ]);
    $table->sortable(true, 'sortorder', SORT_ASC);
    $table->set_attribute('class', 'generaltable generalbox local-chatbot-suggestions-table');
    $table->setup();

    $suggestions = $DB->get_records('local_chatbot_suggestions', null, 'sortorder ASC, text ASC');
    foreach ($suggestions as $suggestion) {
        $statusparts = [];
        $statusparts[] = $suggestion->enabled ? get_string('enabled', 'core') : get_string('disabled', 'core');
        $statusparts[] = get_string('suggestion_mode_' . $suggestion->mode, 'local_chatbot');

        $actions = [];
        $actions[] = html_writer::link(new moodle_url($formurl, ['action' => 'edit', 'id' => $suggestion->id]), get_string('edit'));
        $actions[] = html_writer::link(new moodle_url($formurl, [
            'action' => 'delete',
            'id' => $suggestion->id,
            'sesskey' => sesskey(),
        ]), get_string('delete'), [
            'data-confirm' => get_string('suggestion_delete_confirm', 'local_chatbot', $suggestion->text),
        ]);

        $table->add_data([
            format_string($suggestion->text),
            get_string('suggestion_mode_' . $suggestion->mode, 'local_chatbot'),
            format_string($suggestion->target),
            format_string($suggestion->icon),
            implode(' · ', $statusparts),
            (int)$suggestion->sortorder,
            implode(' | ', $actions),
        ]);
    }

    $table->finish_output();
}

echo $OUTPUT->footer();
