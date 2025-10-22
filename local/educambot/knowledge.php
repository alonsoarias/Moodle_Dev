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
 * Management interface for Educam Bot structured knowledge.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/editorlib.php');
require_once(__DIR__ . '/classes/form/knowledge_form.php');

$context = context_system::instance();
require_login();
require_capability('local/educambot:manage', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educambot/knowledge.php'));
$PAGE->set_title(get_string('manageknowledge', 'local_educambot'));
$PAGE->set_heading(get_string('manageknowledge', 'local_educambot'));

$renderer = $PAGE->get_renderer('local_educambot');

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_RAW_TRIMMED);
$status = optional_param('status', 'all', PARAM_ALPHA);
$topicfilter = optional_param('topic', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$page = max(0, $page);
$perpage = max(10, min(100, $perpage));

$baseparams = [
    'status' => $status,
    'topic' => $topicfilter,
    'perpage' => $perpage,
];
if ($search !== '') {
    $baseparams['search'] = $search;
}
$baseurl = new moodle_url('/local/educambot/knowledge.php', $baseparams);

$topics = $DB->get_records('local_educambot_topic', null, 'name ASC', 'id, name');
$topicoptions = [0 => get_string('filtertopicall', 'local_educambot')];
foreach ($topics as $topic) {
    $topicoptions[(int)$topic->id] = format_string($topic->name);
}

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
    if ($DB->record_exists('local_educambot_knowledge', ['id' => $id])) {
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_educambot_knowledge', ['id' => $id]);
        $DB->delete_records('local_educambot_kn_topic', ['knowledgeid' => $id]);
        $DB->delete_records('local_educambot_kn_context', ['knowledgeid' => $id]);
        $DB->delete_records('local_educambot_relation', ['sourceid' => $id]);
        $DB->delete_records('local_educambot_relation', ['targetid' => $id]);
        \local_educambot\local\knowledge_repository::reset_caches();
        $transaction->allow_commit();
        redirect($baseurl, get_string('deleted', 'local_educambot'));
    } else {
        print_error('invalidrecord', 'error');
    }
}

if (in_array($action, ['add', 'edit'], true)) {
    if ($action === 'edit') {
        $record = $DB->get_record('local_educambot_knowledge', ['id' => $id], '*', MUST_EXIST);
        $topicsassigned = $DB->get_records('local_educambot_kn_topic', ['knowledgeid' => $id], '', 'id, topicid');
        $record->topics = implode(',', array_map(static function($topic) {
            return (string)$topic->topicid;
        }, $topicsassigned));
        $record = file_prepare_standard_editor($record, 'content', $editoroptions, $context, 'local_educambot', 'knowledgecontent', $record->id);
    } else {
        $record = (object) [
            'id' => 0,
            'title' => '',
            'summary' => '',
            'content' => '',
            'topics' => '',
            'tags' => '',
            'enabled' => 1,
        ];
        $record = file_prepare_standard_editor($record, 'content', $editoroptions, $context, 'local_educambot', 'knowledgecontent', 0);
    }

    $form = new \local_educambot\form\knowledge_form($baseurl, [
        'topics' => $topicoptions,
        'editoroptions' => $editoroptions,
    ]);

    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($data = $form->get_data()) {
        $transaction = $DB->start_delegated_transaction();
        $now = time();
        $selectedtopics = array_filter(array_map('intval', $data->topics ?? []));

        $record = new stdClass();
        $record->title = trim(clean_param($data->title, PARAM_TEXT));
        $record->summary = trim(clean_param($data->summary ?? '', PARAM_RAW_TRIMMED));
        $record->tags = trim(clean_param($data->tags ?? '', PARAM_RAW_TRIMMED));
        $record->enabled = !empty($data->enabled) ? 1 : 0;
        $record->timemodified = $now;

        if ($action === 'edit') {
            $record->id = $id;
            $record->timecreated = $DB->get_field('local_educambot_knowledge', 'timecreated', ['id' => $id]);
            $data = file_postupdate_standard_editor($data, 'content', $editoroptions, $context, 'local_educambot', 'knowledgecontent', $id);
            $record->content = trim($data->content['text'] ?? '');
            $record->contentformat = FORMAT_HTML;
            $DB->update_record('local_educambot_knowledge', $record);
            $DB->delete_records('local_educambot_kn_topic', ['knowledgeid' => $id]);
            foreach ($selectedtopics as $topicid) {
                $DB->insert_record('local_educambot_kn_topic', (object) [
                    'knowledgeid' => $id,
                    'topicid' => $topicid,
                ]);
            }
        } else {
            $record->timecreated = $now;
            $record->contentformat = FORMAT_HTML;
            $record->content = '';
            $record->id = $DB->insert_record('local_educambot_knowledge', $record);
            $data = file_postupdate_standard_editor($data, 'content', $editoroptions, $context, 'local_educambot', 'knowledgecontent', $record->id);
            $record->content = trim($data->content['text'] ?? '');
            $DB->update_record('local_educambot_knowledge', $record);
            foreach ($selectedtopics as $topicid) {
                $DB->insert_record('local_educambot_kn_topic', (object) [
                    'knowledgeid' => $record->id,
                    'topicid' => $topicid,
                ]);
            }
        }

        \local_educambot\local\knowledge_repository::reset_caches();
        $transaction->allow_commit();
        redirect($baseurl, get_string('saved', 'local_educambot'));
    } else {
        $form->set_data($record);

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string($action === 'edit' ? 'editknowledge' : 'addknowledge', 'local_educambot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;
    }
}

$conditions = ['1=1'];
$params = [];
if ($status === 'enabled') {
    $conditions[] = 'k.enabled = :enabled';
    $params['enabled'] = 1;
} else if ($status === 'disabled') {
    $conditions[] = 'k.enabled = :enabled';
    $params['enabled'] = 0;
}

$joins = '';
if ($topicfilter > 0) {
    $joins .= ' JOIN {local_educambot_kn_topic} kt ON kt.knowledgeid = k.id';
    $conditions[] = 'kt.topicid = :topicid';
    $params['topicid'] = $topicfilter;
}

if ($search !== '') {
    $searchparam = '%' . core_text::strtolower($search) . '%';
    $conditions[] = '(' . $DB->sql_like('LOWER(k.title)', ':searchterm', false) .
        ' OR ' . $DB->sql_like('LOWER(k.summary)', ':searchterm2', false) .
        ' OR ' . $DB->sql_like('LOWER(k.tags)', ':searchterm3', false) . ')';
    $params['searchterm'] = $searchparam;
    $params['searchterm2'] = $searchparam;
    $params['searchterm3'] = $searchparam;
}

$where = implode(' AND ', $conditions);
$countsql = 'SELECT COUNT(DISTINCT k.id) FROM {local_educambot_knowledge} k' . $joins . ' WHERE ' . $where;
$total = $DB->count_records_sql($countsql, $params);

$records = [];
if ($total > 0) {
    $selectsql = 'SELECT DISTINCT k.* FROM {local_educambot_knowledge} k' . $joins . ' WHERE ' . $where . ' ORDER BY k.timemodified DESC';
    $records = $DB->get_records_sql($selectsql, $params, $page * $perpage, $perpage);
}

$knowledgeids = array_keys($records);
$topicsmap = [];
if (!empty($knowledgeids)) {
    $repository = new \local_educambot\local\knowledge_repository();
    $topicsmap = $repository->get_topics_for_ids($knowledgeids);
}

$recordsdata = [];
foreach ($records as $record) {
    $topicnames = $topicsmap[$record->id] ?? [];
    $recordsdata[] = [
        'title' => format_string($record->title),
        'topics' => !empty($topicnames) ? implode(', ', array_map(static function($name) {
            return format_string($name);
        }, $topicnames)) : get_string('notopics', 'local_educambot'),
        'status' => $record->enabled ? get_string('enabledyes', 'local_educambot') : get_string('enabledno', 'local_educambot'),
        'statusclass' => $record->enabled ? 'bg-success' : 'bg-secondary',
        'timemodified' => userdate($record->timemodified),
        'actions' => build_knowledge_actions((int)$record->id, $record->title, $search, $status, $topicfilter, $perpage, $page),
    ];
}

$paginghtml = '';
if ($total > $perpage) {
    $paginghtml = $OUTPUT->paging_bar($total, $page, $perpage, $baseurl);
}

$filtersdata = [
    'addurl' => (new moodle_url('/local/educambot/knowledge.php', ['action' => 'add'] + $baseparams))->out(false),
    'addlabel' => get_string('addknowledge', 'local_educambot'),
    'rulesurl' => (new moodle_url('/local/educambot/manage.php'))->out(false),
    'ruleslabel' => get_string('manageentries', 'local_educambot'),
    'status' => [
        'options' => [
            ['value' => 'all', 'label' => get_string('filterstatusall', 'local_educambot'), 'selected' => $status === 'all'],
            ['value' => 'enabled', 'label' => get_string('filterstatusenabled', 'local_educambot'), 'selected' => $status === 'enabled'],
            ['value' => 'disabled', 'label' => get_string('filterstatusdisabled', 'local_educambot'), 'selected' => $status === 'disabled'],
        ],
    ],
    'topic' => [
        'options' => array_map(static function($value, $label) use ($topicfilter) {
            return [
                'value' => $value,
                'label' => $label,
                'selected' => (int)$value === (int)$topicfilter,
            ];
        }, array_keys($topicoptions), array_values($topicoptions)),
    ],
    'perpage' => $perpage,
];

$searchinfo = [
    'term' => $search,
    'placeholder' => get_string('searchknowledgeplaceholder', 'local_educambot'),
    'issearch' => $search !== '',
    'clearurl' => (new moodle_url('/local/educambot/knowledge.php', ['status' => $status, 'topic' => $topicfilter, 'perpage' => $perpage]))->out(false),
    'resultsmessage' => get_string('searchresultsfound', 'local_educambot', $total),
    'noresults' => get_string('nosearchresults', 'local_educambot'),
    'noentries' => empty($recordsdata)
        ? ($search !== '' ? get_string('nosearchresults', 'local_educambot') : get_string('noknowledgeentries', 'local_educambot'))
        : '',
];

$renderable = new \local_educambot\output\knowledge_table($recordsdata, $filtersdata, $searchinfo, $paginghtml);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manageknowledge', 'local_educambot'));
echo $renderer->render($renderable);
echo $OUTPUT->footer();

/**
 * Builds the HTML for knowledge management actions.
 *
 * @param int $id
 * @param string $title
 * @param string $search
 * @param string $status
 * @param int $topic
 * @param int $perpage
 * @param int $page
 * @return string
 */
function build_knowledge_actions(int $id, string $title, string $search, string $status, int $topic, int $perpage, int $page): string {
    global $OUTPUT;
    $params = ['action' => 'edit', 'id' => $id, 'status' => $status, 'topic' => $topic, 'perpage' => $perpage, 'page' => $page];
    if ($search !== '') {
        $params['search'] = $search;
    }
    $editurl = new moodle_url('/local/educambot/knowledge.php', $params);

    $deleteparams = ['action' => 'delete', 'id' => $id, 'sesskey' => sesskey(), 'status' => $status, 'topic' => $topic, 'perpage' => $perpage, 'page' => $page];
    if ($search !== '') {
        $deleteparams['search'] = $search;
    }
    $deleteurl = new moodle_url('/local/educambot/knowledge.php', $deleteparams);
    $confirmmessage = get_string('confirmdelete', 'local_educambot', format_string($title));

    $deletebutton = new single_button($deleteurl, get_string('delete'), 'post');
    $deletebutton->class = 'btn btn-link p-0 m-0 align-baseline';
    $deletebutton->formid = 'delete-knowledge-' . $id;
    $deletebutton->add_confirm_action($confirmmessage);

    return html_writer::link($editurl, get_string('edit')) . ' | ' . $OUTPUT->render($deletebutton);
}
