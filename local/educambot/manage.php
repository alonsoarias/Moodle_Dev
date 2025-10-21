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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/editorlib.php');
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
$search = optional_param('search', '', PARAM_TEXT);

$output = $PAGE->get_renderer('core');
$baseurl = new moodle_url('/local/educambot/manage.php');
if ($search !== '') {
    $baseurl->param('search', $search);
}

$cache = cache::make('local_educambot', 'rules');

$editoroptions = [
    'context' => $context,
    'maxfiles' => EDITOR_UNLIMITED_FILES,
    'maxbytes' => 0,
    'trusttext' => false,
    'subdirs' => false,
];

if ($action === 'delete' && $id) {
    require_sesskey();
    if ($record = $DB->get_record('local_educambot_rule', ['id' => $id])) {
        $DB->delete_records('local_educambot_rule', ['id' => $id]);
        $cache->purge();
        redirect($baseurl, get_string('deleted', 'local_educambot'));
    } else {
        print_error('invalidrecord', 'error');
    }
}

if (in_array($action, ['add', 'edit'], true)) {
    if ($action === 'edit') {
        $record = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
        $record = file_prepare_standard_editor($record, 'response', $editoroptions, $context, 'local_educambot', 'response', $record->id);
    } else {
        $record = (object) [
            'id' => 0,
            'pattern' => '',
            'synonyms' => '',
            'keywords' => '',
            'response' => '',
            'roles' => [],
            'contexts' => '',
            'suggested' => 0,
            'enabled' => 1,
        ];
        $record = file_prepare_standard_editor($record, 'response', $editoroptions, $context, 'local_educambot', 'response', 0);
    }

    $form = new \local_educambot\form\entry_form($baseurl, [
        'persistent' => $record,
        'editoroptions' => $editoroptions,
    ]);

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/educambot/manage.php', $search !== '' ? ['search' => $search] : []));
    } else if ($data = $form->get_data()) {
        $now = time();
        $saved = new stdClass();
        $saved->pattern = trim(clean_param($data->pattern, PARAM_TEXT));
        $saved->synonyms = trim(clean_param($data->synonyms ?? '', PARAM_TEXT));
        $saved->keywords = trim(clean_param($data->keywords ?? '', PARAM_TEXT));
        $saved->roles = !empty($data->roles) ? implode(',', array_map(static function(string $role): string {
            return trim(clean_param($role, PARAM_ALPHANUMEXT));
        }, (array)$data->roles)) : null;
        $saved->contexts = trim(clean_param($data->contexts ?? '', PARAM_TEXT));
        $saved->suggested = !empty($data->suggested) ? 1 : 0;
        $saved->enabled = !empty($data->enabled) ? 1 : 0;
        $saved->timemodified = $now;

        if ($action === 'edit') {
            $saved->id = $record->id;
            $saved->timecreated = $record->timecreated;
            $data = file_postupdate_standard_editor($data, 'response', $editoroptions, $context, 'local_educambot', 'response', $saved->id);
            $saved->response = trim($data->response);
            $DB->update_record('local_educambot_rule', $saved);
        } else {
            $saved->timecreated = $now;
            $saved->response = '';
            $saved->id = $DB->insert_record('local_educambot_rule', $saved);
            $data = file_postupdate_standard_editor($data, 'response', $editoroptions, $context, 'local_educambot', 'response', $saved->id);
            $saved->response = trim($data->response);
            $DB->update_record('local_educambot_rule', $saved);
        }

        $cache->purge();
        redirect(new moodle_url('/local/educambot/manage.php', $search !== '' ? ['search' => $search] : []),
            get_string('saved', 'local_educambot'));
    } else {
        $form->set_data($record);

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string($action === 'edit' ? 'editentry' : 'addentry', 'local_educambot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;
    }
}

$searching = $search !== '';

if ($searching) {
    $engine = new \local_educambot\bot\engine(null, null);
    $records = [];
    foreach ($engine->preview_rankings($search, true, 50) as $ranked) {
        $entry = clone $ranked['entry'];
        $entry->score = $ranked['score'];
        $records[] = $entry;
    }
} else {
    $records = array_values($DB->get_records('local_educambot_rule', null, 'timemodified DESC'));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageentries', 'local_educambot'));

$addurl = new moodle_url('/local/educambot/manage.php', ['action' => 'add']);
echo $OUTPUT->single_button($addurl, get_string('addentry', 'local_educambot'));

$searchform = html_writer::start_tag('form', ['method' => 'get', 'class' => 'local-educambot-search my-3']);
$searchform .= html_writer::start_div('d-flex flex-wrap gap-2 align-items-center');
$searchform .= html_writer::tag('label', get_string('searchknowledgebase', 'local_educambot'), [
    'for' => 'local-educambot-search-field',
    'class' => 'sr-only',
]);
$searchform .= html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'local-educambot-search-field',
    'value' => $search,
    'class' => 'form-control flex-grow-1',
    'placeholder' => get_string('searchplaceholder', 'local_educambot'),
]);
$searchform .= html_writer::tag('button', get_string('search'), [
    'type' => 'submit',
    'class' => 'btn btn-primary',
]);
if ($searching) {
    $searchform .= html_writer::link(new moodle_url('/local/educambot/manage.php'),
        get_string('clearsearch', 'local_educambot'), ['class' => 'btn btn-secondary']);
}
$searchform .= html_writer::end_div();
$searchform .= html_writer::end_tag('form');
echo $searchform;

if (!$records) {
    $message = $searching ? get_string('nosearchresults', 'local_educambot') : get_string('noentries', 'local_educambot');
    echo $OUTPUT->notification($message, 'info');
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('pattern', 'local_educambot'),
];
if ($searching) {
    $table->head[] = get_string('confidence', 'local_educambot');
}
$table->head = array_merge($table->head, [
    get_string('status', 'local_educambot'),
    get_string('suggested', 'local_educambot'),
    get_string('timemodified', 'local_educambot'),
    get_string('actions', 'local_educambot'),
]);

foreach ($records as $record) {
    $status = $record->enabled ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot');
    $suggested = $record->suggested ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot');
    $editparams = ['action' => 'edit', 'id' => $record->id];
    $deleteparams = ['action' => 'delete', 'id' => $record->id, 'sesskey' => sesskey()];
    if ($searching) {
        $editparams['search'] = $search;
        $deleteparams['search'] = $search;
    }
    $editurl = new moodle_url('/local/educambot/manage.php', $editparams);
    $deleteurl = new moodle_url('/local/educambot/manage.php', $deleteparams);
    $deleteaction = new \core\output\actions\confirm_action(
        get_string('confirmdelete', 'local_educambot', format_string($record->pattern))
    );
    $actions = $OUTPUT->action_link($editurl, get_string('edit')) . ' | ' .
        $OUTPUT->action_link($deleteurl, get_string('delete'), $deleteaction);

    $row = [format_string($record->pattern)];
    if ($searching) {
        $row[] = sprintf('%d%%', (int)round(($record->score ?? 0) * 100));
    }
    $row[] = $status;
    $row[] = $suggested;
    $row[] = userdate($record->timemodified);
    $row[] = $actions;

    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
