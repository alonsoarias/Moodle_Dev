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
 * Management interface for Educam Bot rules.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/editorlib.php');
require_once($CFG->libdir . '/outputcomponents.php');
require_once(__DIR__ . '/classes/form/entry_form.php');

$context = context_system::instance();
require_login();
require_capability('local/educambot:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educambot/manage.php'));
$PAGE->set_title(get_string('manageentries', 'local_educambot'));
$PAGE->set_heading(get_string('manageentries', 'local_educambot'));

$renderer = $PAGE->get_renderer('local_educambot');

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$status = optional_param('status', 'all', PARAM_ALPHA);
$suggestedfilter = optional_param('suggested', 'all', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$page = max(0, $page);
$perpage = max(10, min(100, $perpage));

$baseparams = [
    'status' => $status,
    'suggested' => $suggestedfilter,
    'perpage' => $perpage,
];
if ($search !== '') {
    $baseparams['search'] = $search;
}
$baseurl = new moodle_url('/local/educambot/manage.php', $baseparams);

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
    require_post();
    if ($DB->record_exists('local_educambot_rule', ['id' => $id])) {
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
        redirect($baseurl);
    } else if ($data = $form->get_data()) {
        $now = time();
        $saved = new stdClass();
        $saved->pattern = trim(clean_param($data->pattern, PARAM_TEXT));
        $saved->synonyms = preg_replace('/\r\n?/', "\n", trim(clean_param($data->synonyms ?? '', PARAM_NOTAGS)));
        $saved->keywords = trim(clean_param($data->keywords ?? '', PARAM_NOTAGS));
        $saved->roles = !empty($data->roles) ? implode(',', array_map(static function(string $role): string {
            return trim(clean_param($role, PARAM_ALPHANUMEXT));
        }, (array)$data->roles)) : null;
        $saved->contexts = preg_replace('/\r\n?/', "\n", trim(clean_param($data->contexts ?? '', PARAM_NOTAGS)));
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
        redirect($baseurl, get_string('saved', 'local_educambot'));
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
$recordsdata = [];
$paginghtml = '';

if ($searching) {
    $engine = new \local_educambot\bot\engine(null, null);
    $limit = min(100, max(10, $perpage));
    $preview = $engine->preview_rankings($search, true, $limit);
    foreach ($preview as $ranked) {
        $entry = clone $ranked['entry'];
        $confidence = (float)($ranked['score'] ?? 0);
        $recordsdata[] = [
            'pattern' => format_string($entry->pattern),
            'hasconfidence' => true,
            'confidence' => sprintf('%d%%', (int)round(min(100, max(0, $confidence * 100)))),
            'status' => $entry->enabled ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot'),
            'statusclass' => $entry->enabled ? 'bg-success' : 'bg-secondary',
            'suggested' => $entry->suggested ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot'),
            'timemodified' => userdate($entry->timemodified),
            'actions' => build_rule_actions((int)$entry->id, $entry->pattern, $search, $status, $suggestedfilter, $perpage, $page),
        ];
    }
    $totalsearch = count($preview);
} else {
    $conditions = ['1=1'];
    $params = [];
    if ($status === 'enabled') {
        $conditions[] = 'enabled = :enabled';
        $params['enabled'] = 1;
    } else if ($status === 'disabled') {
        $conditions[] = 'enabled = :enabled';
        $params['enabled'] = 0;
    }
    if ($suggestedfilter === 'only') {
        $conditions[] = 'suggested = :suggested';
        $params['suggested'] = 1;
    } else if ($suggestedfilter === 'none') {
        $conditions[] = 'suggested = :suggested';
        $params['suggested'] = 0;
    }

    $select = implode(' AND ', $conditions);
    $total = $DB->count_records_select('local_educambot_rule', $select, $params);
    if ($total > 0) {
        $records = $DB->get_records_select('local_educambot_rule', $select, $params, 'timemodified DESC', '*', $page * $perpage, $perpage);
    } else {
        $records = [];
    }
    foreach ($records as $record) {
        $recordsdata[] = [
            'pattern' => format_string($record->pattern),
            'hasconfidence' => false,
            'status' => $record->enabled ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot'),
            'statusclass' => $record->enabled ? 'bg-success' : 'bg-secondary',
            'suggested' => $record->suggested ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot'),
            'timemodified' => userdate($record->timemodified),
            'actions' => build_rule_actions((int)$record->id, $record->pattern, $search, $status, $suggestedfilter, $perpage, $page),
        ];
    }
    if ($total > $perpage) {
        $paginghtml = $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
    }
    $totalsearch = $total;
}

$filtersdata = [
    'addurl' => (new moodle_url('/local/educambot/manage.php', ['action' => 'add'] + $baseparams))->out(false),
    'addlabel' => get_string('addentry', 'local_educambot'),
    'knowledgeurl' => (new moodle_url('/local/educambot/knowledge.php'))->out(false),
    'knowledgebuttonlabel' => get_string('manageknowledge', 'local_educambot'),
    'status' => [
        'options' => [
            ['value' => 'all', 'label' => get_string('filterstatusall', 'local_educambot'), 'selected' => $status === 'all'],
            ['value' => 'enabled', 'label' => get_string('filterstatusenabled', 'local_educambot'), 'selected' => $status === 'enabled'],
            ['value' => 'disabled', 'label' => get_string('filterstatusdisabled', 'local_educambot'), 'selected' => $status === 'disabled'],
        ],
    ],
    'suggested' => [
        'options' => [
            ['value' => 'all', 'label' => get_string('filtersuggestedall', 'local_educambot'), 'selected' => $suggestedfilter === 'all'],
            ['value' => 'only', 'label' => get_string('filtersuggestedonly', 'local_educambot'), 'selected' => $suggestedfilter === 'only'],
            ['value' => 'none', 'label' => get_string('filtersuggestednone', 'local_educambot'), 'selected' => $suggestedfilter === 'none'],
        ],
    ],
    'perpage' => $perpage,
];

$searchinfo = [
    'term' => $search,
    'placeholder' => get_string('searchplaceholder', 'local_educambot'),
    'issearch' => $searching,
    'clearurl' => (new moodle_url('/local/educambot/manage.php', ['status' => $status, 'suggested' => $suggestedfilter, 'perpage' => $perpage]))->out(false),
    'showconfidence' => $searching,
    'resultsmessage' => get_string('searchresultsfound', 'local_educambot', $totalsearch),
    'noresults' => get_string('nosearchresults', 'local_educambot'),
    'noentries' => empty($recordsdata)
        ? ($searching ? get_string('nosearchresults', 'local_educambot') : get_string('noentries', 'local_educambot'))
        : '',
];

$renderable = new \local_educambot\output\rule_table($recordsdata, $filtersdata, $searchinfo, $paginghtml);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageentries', 'local_educambot'));
echo $renderer->render($renderable);
echo $OUTPUT->footer();

/**
 * Builds the HTML for rule actions.
 *
 * @param int $id
 * @param string $pattern
 * @param string $search
 * @param string $status
 * @param string $suggested
 * @param int $perpage
 * @param int $page
 * @return string
 */
function build_rule_actions(int $id, string $pattern, string $search, string $status, string $suggested, int $perpage, int $page): string {
    global $OUTPUT;
    $params = ['action' => 'edit', 'id' => $id, 'status' => $status, 'suggested' => $suggested, 'perpage' => $perpage, 'page' => $page];
    if ($search !== '') {
        $params['search'] = $search;
    }
    $editurl = new moodle_url('/local/educambot/manage.php', $params);

    $deleteparams = ['action' => 'delete', 'id' => $id, 'sesskey' => sesskey(), 'status' => $status, 'suggested' => $suggested, 'perpage' => $perpage, 'page' => $page];
    if ($search !== '') {
        $deleteparams['search'] = $search;
    }
    $deleteurl = new moodle_url('/local/educambot/manage.php', $deleteparams);
    $confirmmessage = get_string('confirmdelete', 'local_educambot', format_string($pattern));

    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');
    $deletebutton->class = 'btn btn-link p-0 m-0 align-baseline';
    $deletebutton->formid = 'delete-rule-' . $id;
    $deletebutton->add_confirm_action($confirmmessage);

    return html_writer::link($editurl, get_string('edit')) . ' | ' . $OUTPUT->render($deletebutton);
}
