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
 * Standalone export page for chatbot conversations.
 *
 * @package    local_chatbot
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/chatbot/lib.php');

$sessionid = required_param('sessionid', PARAM_TEXT);
$format = optional_param('format', 'html', PARAM_ALPHA);
$download = optional_param('download', false, PARAM_BOOL);

require_login();
$systemcontext = context_system::instance();
require_capability('local/chatbot:export', $systemcontext);

$PAGE->set_context($systemcontext);
$PAGE->set_url('/local/chatbot/export.php', [
    'sessionid' => $sessionid,
    'format' => $format,
]);

// Validate format.
if (!in_array($format, ['html', 'csv', 'json'])) {
    $format = 'html';
}

// Get conversation data.
$content = local_chatbot_export_conversation($sessionid, $format);

// Set appropriate headers based on format.
if ($download) {
    $filename = 'chatbot-conversation-' . date('Y-m-d-His');
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
            break;
        case 'json':
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.json"');
            break;
        case 'html':
        default:
            header('Content-Type: text/html; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '.html"');
            break;
    }
    
    echo $content;
    exit;
}

// Display in browser.
$PAGE->set_title(get_string('chatbot_export_heading', 'local_chatbot'));
$PAGE->set_heading(get_string('chatbot_export_heading', 'local_chatbot'));

echo $OUTPUT->header();

if ($format === 'html') {
    echo $content;
} else {
    echo html_writer::tag('pre', htmlspecialchars($content));
}

// Download links.
$downloadurl = new moodle_url('/local/chatbot/export.php', [
    'sessionid' => $sessionid,
    'download' => 1,
]);

echo html_writer::start_div('mt-3');
echo html_writer::tag('h4', get_string('download'));

foreach (['html', 'csv', 'json'] as $exportformat) {
    $url = new moodle_url($downloadurl, ['format' => $exportformat]);
    echo html_writer::link($url, strtoupper($exportformat), [
        'class' => 'btn btn-secondary mr-2',
    ]);
}

echo html_writer::end_div();

echo $OUTPUT->footer();