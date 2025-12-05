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
 * AJAX endpoint for retrieving chat history (v1.9.0).
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__ . '/../../config.php');

// Verify session - require_login must come first.
require_login();
require_sesskey();

// Get parameters.
$limit = optional_param('limit', 20, PARAM_INT);
$offset = optional_param('offset', 0, PARAM_INT);
$action = optional_param('action', 'get', PARAM_ALPHA);

// Limit max results for performance.
$limit = min($limit, 50);

header('Content-Type: application/json; charset=utf-8');

// Check if history is enabled.
$enablehistory = get_config('local_educambot', 'enablehistory') ?? 1;
if (!$enablehistory) {
    echo json_encode([
        'success' => true,
        'history' => [],
        'total' => 0,
        'message' => 'History is disabled'
    ]);
    die();
}

try {
    global $DB, $USER;

    switch ($action) {
        case 'get':
            // Get total count for pagination.
            $total = $DB->count_records('local_educambot_log', ['userid' => $USER->id]);

            // Get history records, most recent first.
            $records = $DB->get_records(
                'local_educambot_log',
                ['userid' => $USER->id],
                'timecreated DESC',
                '*',
                $offset,
                $limit
            );

            // Format records for JSON response.
            $history = [];
            foreach (array_reverse($records) as $record) {
                $history[] = [
                    'id' => (int) $record->id,
                    'question' => format_string($record->question),
                    'response' => format_text($record->response, FORMAT_HTML),
                    'confidence' => (float) $record->confidence,
                    'matched' => (bool) $record->matched,
                    'timecreated' => (int) $record->timecreated,
                    'timeformatted' => userdate($record->timecreated, get_string('strftimedatetimeshort', 'langconfig'))
                ];
            }

            echo json_encode([
                'success' => true,
                'history' => $history,
                'total' => (int) $total,
                'offset' => $offset,
                'limit' => $limit,
                'hasmore' => ($offset + $limit) < $total
            ]);
            break;

        case 'clear':
            // Clear user's history.
            $DB->delete_records('local_educambot_log', ['userid' => $USER->id]);

            echo json_encode([
                'success' => true,
                'message' => get_string('historydeleted', 'local_educambot')
            ]);
            break;

        case 'delete':
            // Delete a specific record.
            $recordid = required_param('id', PARAM_INT);
            $record = $DB->get_record('local_educambot_log', ['id' => $recordid, 'userid' => $USER->id]);

            if ($record) {
                $DB->delete_records('local_educambot_log', ['id' => $recordid]);
                echo json_encode([
                    'success' => true,
                    'message' => get_string('recorddeleted', 'local_educambot')
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'error' => get_string('recordnotfound', 'local_educambot')
                ]);
            }
            break;

        case 'recent':
            // Get only the most recent conversation from today.
            $todaystart = strtotime('today midnight');

            $records = $DB->get_records_select(
                'local_educambot_log',
                'userid = :userid AND timecreated >= :todaystart',
                ['userid' => $USER->id, 'todaystart' => $todaystart],
                'timecreated DESC',
                '*',
                0,
                $limit
            );

            $history = [];
            foreach (array_reverse($records) as $record) {
                $history[] = [
                    'id' => (int) $record->id,
                    'question' => format_string($record->question),
                    'response' => format_text($record->response, FORMAT_HTML),
                    'confidence' => (float) $record->confidence,
                    'matched' => (bool) $record->matched,
                    'timecreated' => (int) $record->timecreated,
                    'timeformatted' => userdate($record->timecreated, get_string('strftimetime', 'langconfig'))
                ];
            }

            echo json_encode([
                'success' => true,
                'history' => $history,
                'total' => count($history),
                'istoday' => true
            ]);
            break;

        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
