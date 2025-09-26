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
 * Dialogue log viewer for the chatbot plugin.
 *
 * @package    local_chatbot
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/user/lib.php');

use local_chatbot\form\dialogue_filter_form;

defined('MOODLE_INTERNAL') || die();

class local_chatbot_dialogue_table extends table_sql {
    /**
     * Format user column.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_user($row): string {
        if (!empty($row->userid)) {
            $user = core_user::get_user($row->userid, 'id, firstname, lastname, email', IGNORE_MISSING);
            if ($user) {
                $name = fullname($user);
                return html_writer::link(new moodle_url('/user/profile.php', ['id' => $user->id]), format_string($name));
            }
        }
        return get_string('deleteduser', 'core');
    }

    /**
     * Format message column with preview.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_message($row): string {
        return format_text(shorten_text($row->message, 120), FORMAT_PLAIN);
    }

    /**
     * Format time column.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_timecreated($row): string {
        return userdate($row->timecreated);
    }

    /**
     * Format actions column.
     *
     * @param stdClass $row
     * @return string
     */
    public function col_actions($row): string {
        $detailurl = new moodle_url($this->baseurl);
        $detailurl->param('detail', $row->sessionid);
        return html_writer::link($detailurl, get_string('viewdetails', 'local_chatbot'));
    }
}

admin_externalpage_setup('local_chatbot_dialogues');
require_capability('local/chatbot:manage', context_system::instance());

$baseurl = new moodle_url('/local/chatbot/admin/dialogues.php');
$detail = optional_param('detail', '', PARAM_ALPHANUMEXT);

$filters = [
    'sessionid' => optional_param('sessionid', '', PARAM_ALPHANUMEXT),
    'userid' => optional_param('userid', 0, PARAM_INT),
    'intent' => optional_param('intent', '', PARAM_TEXT),
    'from' => optional_param('from', 0, PARAM_INT),
    'to' => optional_param('to', 0, PARAM_INT),
    'hasfeedback' => optional_param('hasfeedback', 0, PARAM_BOOL),
];

if (optional_param('resetbutton', '', PARAM_TEXT)) {
    redirect($baseurl);
}

$intentoptions = ['' => get_string('all')];
$intents = $DB->get_records('local_chatbot_intents', null, 'name ASC', 'name');
foreach ($intents as $intent) {
    $intentoptions[$intent->name] = format_string($intent->name);
}

$form = new dialogue_filter_form($baseurl, ['intents' => $intentoptions]);
$form->set_data((object)$filters);

if ($data = $form->get_data()) {
    $params = [];
    foreach ($filters as $key => $value) {
        if (!empty($data->$key)) {
            $params[$key] = $data->$key;
        }
    }
    redirect(new moodle_url($baseurl, $params));
}

$PAGE->set_url(new moodle_url($baseurl, array_filter($filters)));
$PAGE->set_title(get_string('dialogues', 'local_chatbot'));
$PAGE->set_heading(get_string('dialogues', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('dialogues', 'local_chatbot'));

$form->display();

if ($detail) {
    $entries = $DB->get_records('local_chatbot_logs', ['sessionid' => $detail], 'timecreated ASC');
    if ($entries) {
        echo $OUTPUT->heading(get_string('dialogue_detail_heading', 'local_chatbot', $detail), 3);
        $items = [];
        foreach ($entries as $entry) {
            $message = format_text($entry->message, FORMAT_PLAIN);
            $response = format_text($entry->response, FORMAT_PLAIN);
            $intent = format_string($entry->intent ?? '');
            $feedback = $entry->feedback ? format_string($entry->feedback) : '';
            $items[] = html_writer::tag('div',
                html_writer::tag('h5', userdate($entry->timecreated)) .
                html_writer::tag('p', html_writer::span(get_string('message', 'local_chatbot') . ': ' . $message)) .
                html_writer::tag('p', html_writer::span(get_string('response', 'local_chatbot') . ': ' . $response)) .
                html_writer::tag('p', html_writer::span(get_string('intent', 'local_chatbot') . ': ' . $intent)) .
                ($feedback ? html_writer::tag('p', get_string('feedback', 'local_chatbot') . ': ' . $feedback) : ''),
                ['class' => 'p-3 border rounded mb-3']
            );
        }
        echo implode('', $items);

        $exporturl = new moodle_url('/local/chatbot/export.php', ['sessionid' => $detail]);
        echo html_writer::div(html_writer::link($exporturl, get_string('dialogue_export', 'local_chatbot'),
            ['class' => 'btn btn-secondary']), 'mb-4');
    } else {
        echo $OUTPUT->notification(get_string('dialogue_no_records', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);
    }
}

$where = [];
$params = [];

if ($filters['sessionid']) {
    $where[] = 'l.sessionid = :fsessionid';
    $params['fsessionid'] = $filters['sessionid'];
}
if (!empty($filters['userid'])) {
    $where[] = 'l.userid = :fuserid';
    $params['fuserid'] = $filters['userid'];
}
if ($filters['intent']) {
    $where[] = 'l.intent = :fintent';
    $params['fintent'] = $filters['intent'];
}
if (!empty($filters['from'])) {
    $where[] = 'l.timecreated >= :ffrom';
    $params['ffrom'] = $filters['from'];
}
if (!empty($filters['to'])) {
    $where[] = 'l.timecreated <= :fto';
    $params['fto'] = $filters['to'] + DAYSECS - 1;
}
if (!empty($filters['hasfeedback'])) {
    $where[] = "(l.feedback IS NOT NULL AND l.feedback <> '')";
}

$wheresql = $where ? implode(' AND ', $where) : '1=1';

$table = new local_chatbot_dialogue_table('local-chatbot-dialogues');
$table->set_attribute('class', 'generaltable generalbox local-chatbot-dialogues-table');
$table->define_columns(['sessionid', 'user', 'intent', 'message', 'timecreated', 'feedback', 'actions']);
$table->define_headers([
    get_string('dialogue_session', 'local_chatbot'),
    get_string('user'),
    get_string('intent', 'local_chatbot'),
    get_string('message', 'local_chatbot'),
    get_string('time'),
    get_string('feedback', 'local_chatbot'),
    get_string('actions'),
]);
$table->sortable(true, 'timecreated', SORT_DESC);
$tablebaseurl = new moodle_url('/local/chatbot/admin/dialogues.php', array_filter($filters));
$table->define_baseurl($tablebaseurl);
$table->set_sql(
    'l.id, l.sessionid, l.userid, l.message, l.intent, l.timecreated, l.feedback',
    "{local_chatbot_logs} l",
    $wheresql,
    $params
);

$table->out(20, true);

echo $OUTPUT->footer();
