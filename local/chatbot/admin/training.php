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
 * Training console for testing intents and responses.
 *
 * @package    local_chatbot
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_chatbot\form\training_form;

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_chatbot_training');
require_capability('local/chatbot:manage', context_system::instance());

$form = new training_form($PAGE->url);
$result = null;
$history = [];
$matchedkeywords = [];

if ($form->is_cancelled()) {
    redirect($PAGE->url);
} else if ($data = $form->get_data()) {
    $message = trim((string)$data->message);
    $sessionid = trim((string)$data->sessionid);

    if (!empty($data->logmessage)) {
        $response = local_chatbot_process_message($message, $sessionid ?: null);
        $result = [
            'intent' => $response['intent'],
            'response' => $response['response'],
            'logged' => true,
            'sessionid' => $response['sessionid'],
            'logid' => $response['logid'],
            'timestamp' => $response['timestamp'],
        ];
        $sessionid = $response['sessionid'];
    } else {
        $preview = local_chatbot_preview_message($message);
        $result = [
            'intent' => $preview['intent'],
            'response' => $preview['response'],
            'logged' => false,
            'sessionid' => $sessionid,
            'timestamp' => time(),
        ];
        if (!empty($preview['intentrecord']->keywords)) {
            $matchedkeywords = local_chatbot_parse_keywords($preview['intentrecord']->keywords);
        }
        if (!empty($preview['matchedkeyword'])) {
            $matchedkeywords[] = $preview['matchedkeyword'];
            $matchedkeywords = array_unique($matchedkeywords);
        }
    }

    if ($sessionid) {
        $history = local_chatbot_get_conversation_history($sessionid, 20);
    }
}

$PAGE->set_title(get_string('training', 'local_chatbot'));
$PAGE->set_heading(get_string('training', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('training', 'local_chatbot'));

echo $OUTPUT->notification(get_string('training_intro', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);

$form->display();

if ($result) {
    $status = $result['logged'] ? get_string('training_logged', 'local_chatbot') : get_string('training_preview', 'local_chatbot');
    $details = html_writer::start_div('local-chatbot-training-result alert alert-info');
    $details .= html_writer::tag('h3', get_string('training_result_heading', 'local_chatbot'));
    $details .= html_writer::tag('p', html_writer::span(get_string('training_result_status', 'local_chatbot', $status)));
    $details .= html_writer::tag('p', html_writer::span(get_string('training_result_intent', 'local_chatbot', $result['intent'])));
    $details .= html_writer::tag('p', html_writer::span(get_string('training_result_response', 'local_chatbot', $result['response'])));
    if (!empty($result['sessionid'])) {
        $details .= html_writer::tag('p', html_writer::span(get_string('training_result_session', 'local_chatbot', $result['sessionid'])));
    }
    if (!empty($matchedkeywords)) {
        $details .= html_writer::tag('p', get_string('training_result_keywords', 'local_chatbot', implode(', ', $matchedkeywords)));
    }
    $details .= html_writer::end_div();
    echo $details;

    echo $OUTPUT->heading(get_string('training_context_heading', 'local_chatbot'), 3);

    $suggestions = local_chatbot_get_suggestions_payload();
    if ($suggestions) {
        $items = [];
        foreach ($suggestions as $suggestion) {
            $label = trim(($suggestion['icon'] ?? '') . ' ' . $suggestion['text']);
            $items[] = html_writer::tag('li', format_string($label));
        }
        echo html_writer::tag('h4', get_string('training_context_suggestions', 'local_chatbot'));
        echo html_writer::tag('ul', implode('', $items), ['class' => 'list-unstyled']);
    }

    $quickactions = local_chatbot_get_quick_actions(true);
    if ($quickactions) {
        $rows = [];
        foreach ($quickactions as $action) {
            $rows[] = html_writer::tag('tr',
                html_writer::tag('td', format_string($action['label'])) .
                html_writer::tag('td', format_string($action['type'])) .
                html_writer::tag('td', format_string($action['description']))
            );
        }
        echo html_writer::tag('h4', get_string('training_context_actions', 'local_chatbot'));
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

if (!empty($history)) {
    echo $OUTPUT->heading(get_string('training_history_heading', 'local_chatbot'), 3);
    $rows = [];
    foreach ($history as $entry) {
        $rows[] = html_writer::tag('tr',
            html_writer::tag('td', userdate($entry->timecreated)) .
            html_writer::tag('td', format_text($entry->message, FORMAT_PLAIN)) .
            html_writer::tag('td', format_text($entry->response, FORMAT_PLAIN)) .
            html_writer::tag('td', format_string($entry->intent))
        );
    }
    echo html_writer::tag('table',
        html_writer::tag('thead', html_writer::tag('tr',
            html_writer::tag('th', get_string('time')) .
            html_writer::tag('th', get_string('message', 'local_chatbot')) .
            html_writer::tag('th', get_string('response', 'local_chatbot')) .
            html_writer::tag('th', get_string('intent', 'local_chatbot'))
        )) .
        html_writer::tag('tbody', implode('', $rows)),
        ['class' => 'generaltable local-chatbot-training-history']
    );
}

echo $OUTPUT->footer();
