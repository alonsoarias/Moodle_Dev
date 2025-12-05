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
 * External API for local_educambot (v1.9.0).
 *
 * Updated to use modern external API classes when available (Moodle 4.2+)
 * while maintaining backwards compatibility with older versions.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot;

defined('MOODLE_INTERNAL') || die();

// Use modern API if available (Moodle 4.2+), otherwise fall back to legacy.
if (class_exists('\core_external\external_api')) {
    // Moodle 4.2+ uses namespaced classes.
    class_alias('\core_external\external_api', '\local_educambot\base_external_api');
    class_alias('\core_external\external_function_parameters', '\local_educambot\base_external_function_parameters');
    class_alias('\core_external\external_value', '\local_educambot\base_external_value');
    class_alias('\core_external\external_single_structure', '\local_educambot\base_external_single_structure');
    class_alias('\core_external\external_multiple_structure', '\local_educambot\base_external_multiple_structure');
} else {
    // Legacy support for Moodle < 4.2.
    require_once($CFG->libdir . '/externallib.php');
    class_alias('\external_api', '\local_educambot\base_external_api');
    class_alias('\external_function_parameters', '\local_educambot\base_external_function_parameters');
    class_alias('\external_value', '\local_educambot\base_external_value');
    class_alias('\external_single_structure', '\local_educambot\base_external_single_structure');
    class_alias('\external_multiple_structure', '\local_educambot\base_external_multiple_structure');
}

use context_system;

/**
 * External functions for educambot mascot functionality.
 */
class external extends base_external_api {

    /**
     * Describes the parameters for get_popular_questions.
     *
     * @return base_external_function_parameters
     */
    public static function get_popular_questions_parameters() {
        return new base_external_function_parameters([
            'limit' => new base_external_value(PARAM_INT, 'Maximum number of questions to return', VALUE_DEFAULT, 5),
        ]);
    }

    /**
     * Get the most popular/frequently asked questions.
     *
     * @param int $limit Maximum number of questions to return
     * @return array Array of popular questions
     */
    public static function get_popular_questions($limit = 5) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::get_popular_questions_parameters(), [
            'limit' => $limit,
        ]);

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        // Check capability.
        require_capability('local/educambot:use', $context);

        $limit = min($params['limit'], 10); // Cap at 10.

        // Get popular questions from log table (most matched rules).
        $sql = "SELECT r.id, r.pattern, COUNT(l.id) as usage_count
                FROM {local_educambot_rule} r
                JOIN {local_educambot_log} l ON l.ruleid = r.id
                WHERE r.enabled = 1 AND l.matched = 1
                GROUP BY r.id, r.pattern
                ORDER BY usage_count DESC";

        $records = $DB->get_records_sql($sql, [], 0, $limit);

        $questions = [];
        foreach ($records as $record) {
            $questions[] = [
                'id' => (int)$record->id,
                'pattern' => format_string($record->pattern),
                'count' => (int)$record->usage_count,
            ];
        }

        // If not enough popular questions, fill with enabled rules.
        if (count($questions) < $limit) {
            $existingids = array_column($questions, 'id');
            $excludeids = !empty($existingids) ? $existingids : [0];
            list($insql, $inparams) = $DB->get_in_or_equal($excludeids, SQL_PARAMS_NAMED, 'id', false);

            $additional = $DB->get_records_select(
                'local_educambot_rule',
                "enabled = 1 AND id $insql",
                $inparams,
                'timecreated DESC',
                'id, pattern',
                0,
                $limit - count($questions)
            );

            foreach ($additional as $record) {
                $questions[] = [
                    'id' => (int)$record->id,
                    'pattern' => format_string($record->pattern),
                    'count' => 0,
                ];
            }
        }

        return $questions;
    }

    /**
     * Describes the return value for get_popular_questions.
     *
     * @return base_external_multiple_structure
     */
    public static function get_popular_questions_returns() {
        return new base_external_multiple_structure(
            new base_external_single_structure([
                'id' => new base_external_value(PARAM_INT, 'Rule ID'),
                'pattern' => new base_external_value(PARAM_TEXT, 'Question pattern'),
                'count' => new base_external_value(PARAM_INT, 'Usage count'),
            ])
        );
    }

    /**
     * Describes the parameters for get_similar_questions.
     *
     * @return base_external_function_parameters
     */
    public static function get_similar_questions_parameters() {
        return new base_external_function_parameters([
            'question' => new base_external_value(PARAM_TEXT, 'The question to find similar questions for'),
            'limit' => new base_external_value(PARAM_INT, 'Maximum number of questions to return', VALUE_DEFAULT, 3),
        ]);
    }

    /**
     * Get similar questions based on keywords from user's question.
     *
     * @param string $question The user's question
     * @param int $limit Maximum number of questions to return
     * @return array Array of similar questions
     */
    public static function get_similar_questions($question, $limit = 3) {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::get_similar_questions_parameters(), [
            'question' => $question,
            'limit' => $limit,
        ]);

        // Validate context.
        $context = context_system::instance();
        self::validate_context($context);

        // Check capability.
        require_capability('local/educambot:use', $context);

        $limit = min($params['limit'], 10);
        $question = clean_param($params['question'], PARAM_TEXT);

        // Tokenize question - get meaningful words (length > 3).
        $words = preg_split('/\s+/', mb_strtolower($question));
        $words = array_filter($words, function($w) {
            return mb_strlen($w) > 3;
        });

        if (empty($words)) {
            return [];
        }

        // Build query to find rules with matching keywords.
        $conditions = [];
        $sqlparams = [];
        $counter = 0;

        foreach ($words as $word) {
            $param1 = 'word' . $counter . 'a';
            $param2 = 'word' . $counter . 'b';
            $param3 = 'word' . $counter . 'c';

            // Use sql_compare_text for TEXT fields.
            $patterncompare = $DB->sql_compare_text('r.pattern');
            $keywordscompare = $DB->sql_compare_text('r.keywords');
            $tagscompare = $DB->sql_compare_text('r.tags');

            $conditions[] = "($patterncompare LIKE :$param1 OR $keywordscompare LIKE :$param2 OR $tagscompare LIKE :$param3)";
            $sqlparams[$param1] = '%' . $DB->sql_like_escape($word) . '%';
            $sqlparams[$param2] = '%' . $DB->sql_like_escape($word) . '%';
            $sqlparams[$param3] = '%' . $DB->sql_like_escape($word) . '%';
            $counter++;
        }

        $sql = "SELECT DISTINCT r.id, r.pattern
                FROM {local_educambot_rule} r
                WHERE r.enabled = 1 AND (" . implode(' OR ', $conditions) . ")
                ORDER BY r.pattern";

        $records = $DB->get_records_sql($sql, $sqlparams, 0, $limit);

        $questions = [];
        foreach ($records as $record) {
            $questions[] = [
                'id' => (int)$record->id,
                'pattern' => format_string($record->pattern),
            ];
        }

        return $questions;
    }

    /**
     * Describes the return value for get_similar_questions.
     *
     * @return base_external_multiple_structure
     */
    public static function get_similar_questions_returns() {
        return new base_external_multiple_structure(
            new base_external_single_structure([
                'id' => new base_external_value(PARAM_INT, 'Rule ID'),
                'pattern' => new base_external_value(PARAM_TEXT, 'Question pattern'),
            ])
        );
    }
}
