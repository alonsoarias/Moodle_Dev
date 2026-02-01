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
 * External function to get notification history.
 *
 * @package    report_usage_monitor
 * @copyright  2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\external;

defined('MOODLE_INTERNAL') || die();

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;

require_once(__DIR__ . '/../../locallib.php');

/**
 * Get notification history external function.
 */
class get_notification_history extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'type' => new external_value(
                PARAM_ALPHA,
                'Notification type: disk, users, or all',
                VALUE_DEFAULT,
                'all'
            ),
            'limit' => new external_value(
                PARAM_INT,
                'Maximum number of records to return',
                VALUE_DEFAULT,
                30
            ),
            'offset' => new external_value(
                PARAM_INT,
                'Offset for pagination',
                VALUE_DEFAULT,
                0
            ),
        ]);
    }

    /**
     * Get notification history.
     *
     * @param string $type Notification type filter
     * @param int $limit Maximum records
     * @param int $offset Pagination offset
     * @return array Notification history data
     */
    public static function execute(string $type = 'all', int $limit = 30, int $offset = 0): array {
        global $DB, $CFG;

        // Validate context and capabilities.
        $context = \context_system::instance();
        self::validate_context($context);
        require_capability('report/usage_monitor:view', $context);

        // Check license validity - throws exception if not authorized.
        \report_usage_monitor\license::require_authorized();

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'type' => $type,
            'limit' => $limit,
            'offset' => $offset,
        ]);

        // Sanitize limit.
        $params['limit'] = min(max(1, $params['limit']), 100);
        $params['offset'] = max(0, $params['offset']);

        // Build query.
        $where = '';
        $sqlparams = [];

        if ($params['type'] !== 'all' && in_array($params['type'], ['disk', 'users'])) {
            $where = ' WHERE type = :type';
            $sqlparams['type'] = $params['type'];
        }

        $sql = "SELECT * FROM {report_usage_monitor_history}" . $where .
               " ORDER BY timecreated DESC";

        $records = $DB->get_records_sql($sql, $sqlparams, $params['offset'], $params['limit']);

        // Count total records.
        $countsql = "SELECT COUNT(*) FROM {report_usage_monitor_history}" . $where;
        $total = $DB->count_records_sql($countsql, $sqlparams);

        // Format results.
        $items = [];
        foreach ($records as $record) {
            $timecreated = self::validate_timestamp($record->timecreated);

            $items[] = [
                'id' => (int)$record->id,
                'type' => $record->type,
                'percentage' => (float)$record->percentage,
                'value' => $record->type === 'disk' ? display_size($record->value) : (string)$record->value,
                'value_raw' => (int)$record->value,
                'threshold' => $record->type === 'disk' ? display_size($record->threshold) : (string)$record->threshold,
                'threshold_raw' => (int)$record->threshold,
                'timecreated' => $timecreated,
                'timereadable' => userdate($timecreated, get_string('strftimedatetime', 'langconfig')),
            ];
        }

        // Get server hostname.
        $hostname = parse_url($CFG->wwwroot, PHP_URL_HOST);

        return [
            'hostname' => $hostname,
            'total' => $total,
            'limit' => $params['limit'],
            'offset' => $params['offset'],
            'items' => $items,
        ];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'hostname' => new external_value(PARAM_TEXT, 'Server hostname'),
            'total' => new external_value(PARAM_INT, 'Total number of records'),
            'limit' => new external_value(PARAM_INT, 'Records per page'),
            'offset' => new external_value(PARAM_INT, 'Current offset'),
            'items' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Record ID'),
                    'type' => new external_value(PARAM_ALPHA, 'Notification type'),
                    'percentage' => new external_value(PARAM_FLOAT, 'Usage percentage'),
                    'value' => new external_value(PARAM_TEXT, 'Formatted value'),
                    'value_raw' => new external_value(PARAM_INT, 'Raw value'),
                    'threshold' => new external_value(PARAM_TEXT, 'Formatted threshold'),
                    'threshold_raw' => new external_value(PARAM_INT, 'Raw threshold'),
                    'timecreated' => new external_value(PARAM_INT, 'Creation timestamp'),
                    'timereadable' => new external_value(PARAM_TEXT, 'Readable date'),
                ])
            ),
        ]);
    }

    /**
     * Validate timestamp with fallback.
     *
     * @param mixed $timestamp Timestamp to validate
     * @return int Valid timestamp
     */
    private static function validate_timestamp($timestamp): int {
        if (is_numeric($timestamp) && $timestamp > 0) {
            return (int)$timestamp;
        }
        return time();
    }
}
