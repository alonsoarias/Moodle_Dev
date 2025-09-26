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
 * Manual testing console for chatbot responses.
 *
 * @package    local_chatbot
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_chatbot\form\test_form;

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_chatbot_test');
require_capability('local/chatbot:manage', context_system::instance());

$form = new test_form($PAGE->url);
$result = null;
$history = [];

if ($form->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $form->get_data()) {
    $message = trim((string)$data->message);
    $sessionid = trim((string)$data->sessionid) ?: null;

    $result = local_chatbot_process_message($message, $sessionid);
    $sessionid = $result['sessionid'];
    $history = local_chatbot_get_conversation_history($sessionid, 20);
}

$PAGE->set_title(get_string('test_chatbot', 'local_chatbot'));
$PAGE->set_heading(get_string('test_chatbot', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('test_chatbot', 'local_chatbot'));

echo $OUTPUT->notification(get_string('test_intro', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);

$form->display();

if ($result) {
    $card = html_writer::start_div('card my-4');
    $card .= html_writer::start_div('card-body');
    $card .= html_writer::tag('h3', get_string('test_result_heading', 'local_chatbot'), ['class' => 'card-title']);
    $card .= html_writer::tag('p', get_string('test_result_response', 'local_chatbot', $result['response']));
    $card .= html_writer::tag('p', get_string('test_result_intent', 'local_chatbot', $result['intent']));
    $card .= html_writer::tag('p', get_string('test_result_session', 'local_chatbot', $result['sessionid']));
    $card .= html_writer::tag('p', get_string('test_result_logid', 'local_chatbot', $result['logid']));
    $card .= html_writer::tag('p', get_string('test_result_time', 'local_chatbot', $result['response_time'] . ' ms'));
    $card .= html_writer::end_div();
    $card .= html_writer::end_div();
    echo $card;

    $suggestions = local_chatbot_get_suggestions_payload();
    if ($suggestions) {
        echo html_writer::tag('h4', get_string('test_suggestions_heading', 'local_chatbot'));
        $list = [];
        foreach ($suggestions as $suggestion) {
            $list[] = html_writer::tag('li', format_string(($suggestion['icon'] ?? '') . ' ' . $suggestion['text']));
        }
        echo html_writer::tag('ul', implode('', $list), ['class' => 'list-inline']);
    }

    $quickactions = local_chatbot_get_quick_actions(true);
    if ($quickactions) {
        echo html_writer::tag('h4', get_string('test_quickactions_heading', 'local_chatbot'));
        $rows = [];
        foreach ($quickactions as $action) {
            $rows[] = html_writer::tag('tr',
                html_writer::tag('td', format_string($action['label'])) .
                html_writer::tag('td', format_string($action['type'])) .
                html_writer::tag('td', format_string($action['description']))
            );
        }
        echo html_writer::tag('table',
            html_writer::tag('thead', html_writer::tag('tr',
                html_writer::tag('th', get_string('quickaction_name', 'local_chatbot')) .
                html_writer::tag('th', get_string('quickaction_type', 'local_chatbot')) .
                html_writer::tag('th', get_string('quickaction_description', 'local_chatbot'))
            )) .
            html_writer::tag('tbody', implode('', $rows)),
            ['class' => 'generaltable']
        );
    }
}

if ($history) {
    echo html_writer::tag('h4', get_string('test_history_heading', 'local_chatbot'));
    $rows = [];
    foreach ($history as $entry) {
        $rows[] = html_writer::tag('tr',
            html_writer::tag('td', userdate($entry->timecreated)) .
            html_writer::tag('td', format_text($entry->message, FORMAT_PLAIN)) .
            html_writer::tag('td', format_text($entry->response, FORMAT_PLAIN)) .
            html_writer::tag('td', format_string($entry->intent)) .
            html_writer::tag('td', format_string($entry->feedback ?? ''))
        );
    }
    echo html_writer::tag('table',
        html_writer::tag('thead', html_writer::tag('tr',
            html_writer::tag('th', get_string('time')) .
            html_writer::tag('th', get_string('message', 'local_chatbot')) .
            html_writer::tag('th', get_string('response', 'local_chatbot')) .
            html_writer::tag('th', get_string('intent', 'local_chatbot')) .
            html_writer::tag('th', get_string('feedback', 'local_chatbot'))
        )) .
        html_writer::tag('tbody', implode('', $rows)),
        ['class' => 'generaltable']
    );
}

echo $OUTPUT->footer();
