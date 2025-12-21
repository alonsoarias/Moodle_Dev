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
 * Scheduled task to analyze feedback and flag problematic rules.
 *
 * This task runs daily and:
 * - Identifies rules with high negative feedback ratio (>30%)
 * - Flags them for admin review
 * - Cleans up old conversation contexts
 * - Generates analytics summary
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for feedback analysis.
 */
class analyze_feedback extends \core\task\scheduled_task {

    /** @var float Threshold for flagging rules as problematic (30% negative) */
    private const NEGATIVE_THRESHOLD = 0.30;

    /** @var int Minimum feedback count to consider for analysis */
    private const MIN_FEEDBACK_COUNT = 5;

    /** @var int Maximum age of conversation context in seconds (24 hours) */
    private const MAX_CONTEXT_AGE = 86400;

    /**
     * Get task name.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('task_analyze_feedback', 'local_educambot');
    }

    /**
     * Execute the task.
     */
    public function execute(): void {
        global $DB;

        mtrace('Educambot: Starting feedback analysis task...');

        // 1. Analyze feedback and flag problematic rules.
        $this->analyze_and_flag_rules();

        // 2. Clean up old conversation contexts.
        $this->cleanup_old_contexts();

        // 3. Generate daily statistics.
        $this->generate_statistics();

        mtrace('Educambot: Feedback analysis task completed.');
    }

    /**
     * Analyze feedback and flag rules that need review.
     */
    private function analyze_and_flag_rules(): void {
        global $DB;

        mtrace('  Analyzing rule feedback...');

        // Find rules with significant feedback and high negative ratio.
        $sql = "SELECT r.id, r.pattern, r.helpfulcount, r.nothelpfulcount,
                       (r.helpfulcount + r.nothelpfulcount) as total_feedback,
                       CASE WHEN (r.helpfulcount + r.nothelpfulcount) > 0
                            THEN r.nothelpfulcount * 1.0 / (r.helpfulcount + r.nothelpfulcount)
                            ELSE 0 END as negative_ratio
                FROM {local_educambot_rule} r
                WHERE r.enabled = 1
                AND (r.helpfulcount + r.nothelpfulcount) >= :min_feedback
                HAVING negative_ratio > :threshold";

        // Some databases don't support HAVING with aliases, so we use a subquery.
        $sql = "SELECT * FROM (
                    SELECT r.id, r.pattern, r.helpfulcount, r.nothelpfulcount,
                           (r.helpfulcount + r.nothelpfulcount) as total_feedback,
                           CASE WHEN (r.helpfulcount + r.nothelpfulcount) > 0
                                THEN r.nothelpfulcount * 1.0 / (r.helpfulcount + r.nothelpfulcount)
                                ELSE 0 END as negative_ratio
                    FROM {local_educambot_rule} r
                    WHERE r.enabled = 1
                    AND (r.helpfulcount + r.nothelpfulcount) >= :min_feedback
                ) AS subquery
                WHERE negative_ratio > :threshold";

        $problematic = $DB->get_records_sql($sql, [
            'min_feedback' => self::MIN_FEEDBACK_COUNT,
            'threshold' => self::NEGATIVE_THRESHOLD,
        ]);

        $flaggedCount = 0;
        foreach ($problematic as $rule) {
            // Flag the rule for review.
            $DB->set_field('local_educambot_rule', 'needs_review', 1, ['id' => $rule->id]);
            $flaggedCount++;

            mtrace(sprintf(
                '    Flagged rule #%d "%s" (%.1f%% negative, %d total feedback)',
                $rule->id,
                substr($rule->pattern, 0, 50),
                $rule->negative_ratio * 100,
                $rule->total_feedback
            ));
        }

        // Also unflag rules that have improved.
        $sql = "SELECT r.id
                FROM {local_educambot_rule} r
                WHERE r.needs_review = 1
                AND (r.helpfulcount + r.nothelpfulcount) >= :min_feedback
                AND (r.nothelpfulcount * 1.0 / (r.helpfulcount + r.nothelpfulcount)) <= :threshold";

        $improved = $DB->get_records_sql($sql, [
            'min_feedback' => self::MIN_FEEDBACK_COUNT,
            'threshold' => self::NEGATIVE_THRESHOLD - 0.05, // 5% margin for improvement.
        ]);

        $unflaggedCount = 0;
        foreach ($improved as $rule) {
            $DB->set_field('local_educambot_rule', 'needs_review', 0, ['id' => $rule->id]);
            $unflaggedCount++;
        }

        mtrace(sprintf('  Flagged %d rules, unflagged %d improved rules.', $flaggedCount, $unflaggedCount));
    }

    /**
     * Clean up old conversation contexts.
     */
    private function cleanup_old_contexts(): void {
        global $DB;

        mtrace('  Cleaning up old conversation contexts...');

        $cutoff = time() - self::MAX_CONTEXT_AGE;

        try {
            $deleted = $DB->delete_records_select(
                'local_educambot_context',
                'timemodified < :cutoff',
                ['cutoff' => $cutoff]
            );

            mtrace(sprintf('  Deleted %d old context records.', $deleted));
        } catch (\Exception $e) {
            mtrace('  Warning: Could not clean up contexts: ' . $e->getMessage());
        }
    }

    /**
     * Generate daily statistics.
     */
    private function generate_statistics(): void {
        global $DB;

        mtrace('  Generating daily statistics...');

        // Get yesterday's date range.
        $yesterday = strtotime('yesterday');
        $today = strtotime('today');

        // Count conversations.
        $conversations = $DB->count_records_select(
            'local_educambot_log',
            'timecreated >= :start AND timecreated < :end',
            ['start' => $yesterday, 'end' => $today]
        );

        // Count matched vs unmatched.
        $matched = $DB->count_records_select(
            'local_educambot_log',
            'timecreated >= :start AND timecreated < :end AND matched = 1',
            ['start' => $yesterday, 'end' => $today]
        );

        $unmatched = $conversations - $matched;

        // Count feedback.
        $positiveFeedback = $DB->count_records_select(
            'local_educambot_feedback',
            'timecreated >= :start AND timecreated < :end AND helpful = 1',
            ['start' => $yesterday, 'end' => $today]
        );

        $negativeFeedback = $DB->count_records_select(
            'local_educambot_feedback',
            'timecreated >= :start AND timecreated < :end AND helpful = 0',
            ['start' => $yesterday, 'end' => $today]
        );

        // Calculate match rate.
        $matchRate = $conversations > 0 ? round(($matched / $conversations) * 100, 1) : 0;

        // Calculate satisfaction rate.
        $totalFeedback = $positiveFeedback + $negativeFeedback;
        $satisfactionRate = $totalFeedback > 0 ? round(($positiveFeedback / $totalFeedback) * 100, 1) : 0;

        mtrace(sprintf('  Yesterday\'s stats:'));
        mtrace(sprintf('    - Total conversations: %d', $conversations));
        mtrace(sprintf('    - Matched: %d (%.1f%%)', $matched, $matchRate));
        mtrace(sprintf('    - Unmatched: %d', $unmatched));
        mtrace(sprintf('    - Positive feedback: %d', $positiveFeedback));
        mtrace(sprintf('    - Negative feedback: %d', $negativeFeedback));
        mtrace(sprintf('    - Satisfaction rate: %.1f%%', $satisfactionRate));

        // Store statistics in config for dashboard.
        set_config('stats_last_run', time(), 'local_educambot');
        set_config('stats_conversations', $conversations, 'local_educambot');
        set_config('stats_match_rate', $matchRate, 'local_educambot');
        set_config('stats_satisfaction', $satisfactionRate, 'local_educambot');
    }
}
