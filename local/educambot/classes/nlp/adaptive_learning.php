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
 * Adaptive learning system for continuous improvement without external AI.
 *
 * Implements local machine learning techniques:
 * - Frequency analysis of successful matches
 * - Pattern mining from conversation logs
 * - Automatic weight adjustment based on feedback
 * - Query clustering for similar questions
 * - Performance trend analysis
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use local_educambot\local\text_helper;
use moodle_database;
use stdClass;

/**
 * Implements adaptive learning from conversation data.
 */
class adaptive_learning {
    /** Minimum confidence threshold for successful match */
    protected const MIN_CONFIDENCE = 0.5;

    /** Minimum occurrences for pattern to be considered significant */
    protected const MIN_PATTERN_FREQUENCY = 3;

    /** @var moodle_database */
    protected moodle_database $db;

    /** @var \cache Application cache */
    protected $cache;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->cache = \cache::make('local_educambot', 'adaptive_learning');
    }

    /**
     * Analyzes conversation logs to identify improvement opportunities.
     *
     * @param int $daysback Number of days to analyze
     * @param int $limit Maximum number of insights to return
     * @return array{
     *     successful_patterns:array,
     *     failed_patterns:array,
     *     common_questions:array,
     *     suggested_improvements:array,
     *     performance_metrics:array
     * }
     */
    public function analyze_conversations(int $daysback = 30, int $limit = 50): array {
        $since = time() - ($daysback * 86400);

        return [
            'successful_patterns' => $this->find_successful_patterns($since, $limit),
            'failed_patterns' => $this->find_failed_patterns($since, $limit),
            'common_questions' => $this->find_common_questions($since, $limit),
            'suggested_improvements' => $this->suggest_improvements($since),
            'performance_metrics' => $this->calculate_performance_metrics($since),
        ];
    }

    /**
     * Finds patterns that consistently result in successful matches.
     *
     * @param int $since Timestamp to analyze from
     * @param int $limit Result limit
     * @return array Successful patterns
     */
    protected function find_successful_patterns(int $since, int $limit): array {
        $sql = "SELECT question, AVG(confidence) as avgconf, COUNT(*) as frequency, ruleid
                  FROM {local_educambot_log}
                 WHERE timecreated >= :since
                   AND confidence >= :minconf
                   AND ruleid IS NOT NULL
              GROUP BY question, ruleid
                HAVING COUNT(*) >= :minfreq
              ORDER BY frequency DESC, avgconf DESC";

        $params = [
            'since' => $since,
            'minconf' => self::MIN_CONFIDENCE,
            'minfreq' => self::MIN_PATTERN_FREQUENCY,
        ];

        $records = $this->db->get_records_sql($sql, $params, 0, $limit);

        $patterns = [];
        foreach ($records as $record) {
            $patterns[] = [
                'question' => $record->question,
                'avg_confidence' => round($record->avgconf, 3),
                'frequency' => (int)$record->frequency,
                'rule_id' => (int)$record->ruleid,
                'keywords' => text_helper::tokenize($record->question),
            ];
        }

        return $patterns;
    }

    /**
     * Finds patterns that consistently result in failed matches.
     *
     * @param int $since Timestamp to analyze from
     * @param int $limit Result limit
     * @return array Failed patterns
     */
    protected function find_failed_patterns(int $since, int $limit): array {
        $sql = "SELECT question, AVG(confidence) as avgconf, COUNT(*) as frequency
                  FROM {local_educambot_log}
                 WHERE timecreated >= :since
                   AND (confidence < :minconf OR confidence IS NULL)
              GROUP BY question
                HAVING COUNT(*) >= :minfreq
              ORDER BY frequency DESC, avgconf ASC";

        $params = [
            'since' => $since,
            'minconf' => self::MIN_CONFIDENCE,
            'minfreq' => self::MIN_PATTERN_FREQUENCY,
        ];

        $records = $this->db->get_records_sql($sql, $params, 0, $limit);

        $patterns = [];
        foreach ($records as $record) {
            $patterns[] = [
                'question' => $record->question,
                'avg_confidence' => round($record->avgconf ?? 0, 3),
                'frequency' => (int)$record->frequency,
                'keywords' => text_helper::tokenize($record->question),
                'suggested_keywords' => $this->extract_important_terms($record->question),
            ];
        }

        return $patterns;
    }

    /**
     * Finds most common questions asked.
     *
     * @param int $since Timestamp to analyze from
     * @param int $limit Result limit
     * @return array Common questions
     */
    protected function find_common_questions(int $since, int $limit): array {
        // Group similar questions using normalized text.
        $sql = "SELECT question, COUNT(*) as frequency, AVG(confidence) as avgconf
                  FROM {local_educambot_log}
                 WHERE timecreated >= :since
              GROUP BY question
              ORDER BY frequency DESC";

        $records = $this->db->get_records_sql($sql, ['since' => $since], 0, $limit * 2);

        // Cluster similar questions.
        $clusters = $this->cluster_similar_questions(array_values($records));

        $common = [];
        foreach (array_slice($clusters, 0, $limit) as $cluster) {
            $representative = $cluster['representative'];
            $common[] = [
                'question' => $representative->question,
                'frequency' => (int)$cluster['total_frequency'],
                'avg_confidence' => round($cluster['avg_confidence'], 3),
                'variations' => count($cluster['members']),
                'has_good_answer' => $cluster['avg_confidence'] >= self::MIN_CONFIDENCE,
            ];
        }

        return $common;
    }

    /**
     * Suggests improvements based on conversation analysis.
     *
     * @param int $since Timestamp to analyze from
     * @return array Suggestions
     */
    protected function suggest_improvements(int $since): array {
        $suggestions = [];

        // Find unanswered question patterns.
        $unanswered = $this->db->get_records_sql(
            "SELECT question, COUNT(*) as frequency
               FROM {local_educambot_unanswered}
              WHERE timecreated >= :since
           GROUP BY question
             HAVING COUNT(*) >= :minfreq
           ORDER BY frequency DESC",
            ['since' => $since, 'minfreq' => 2],
            0,
            20
        );

        foreach ($unanswered as $record) {
            $suggestions[] = [
                'type' => 'missing_knowledge',
                'priority' => 'high',
                'question' => $record->question,
                'frequency' => (int)$record->frequency,
                'suggestion' => 'Add new knowledge entry or rule for this common unanswered question',
                'keywords' => text_helper::tokenize($record->question),
            ];
        }

        // Find low-confidence matches that need improvement.
        $lowconfidence = $this->db->get_records_sql(
            "SELECT question, ruleid, AVG(confidence) as avgconf, COUNT(*) as frequency
               FROM {local_educambot_log}
              WHERE timecreated >= :since
                AND confidence > 0 AND confidence < :threshold
                AND ruleid IS NOT NULL
           GROUP BY question, ruleid
             HAVING COUNT(*) >= :minfreq
           ORDER BY frequency DESC",
            ['since' => $since, 'threshold' => 0.6, 'minfreq' => 2],
            0,
            15
        );

        foreach ($lowconfidence as $record) {
            $suggestions[] = [
                'type' => 'improve_existing',
                'priority' => 'medium',
                'question' => $record->question,
                'rule_id' => (int)$record->ruleid,
                'avg_confidence' => round($record->avgconf, 3),
                'frequency' => (int)$record->frequency,
                'suggestion' => 'Improve rule keywords or add synonyms to increase confidence',
                'keywords' => text_helper::tokenize($record->question),
            ];
        }

        return $suggestions;
    }

    /**
     * Calculates overall performance metrics.
     *
     * @param int $since Timestamp to analyze from
     * @return array Performance metrics
     */
    protected function calculate_performance_metrics(int $since): array {
        $totalquestions = $this->db->count_records_select(
            'local_educambot_log',
            'timecreated >= :since',
            ['since' => $since]
        );

        if ($totalquestions === 0) {
            return [
                'total_questions' => 0,
                'answer_rate' => 0.0,
                'avg_confidence' => 0.0,
                'high_confidence_rate' => 0.0,
            ];
        }

        $answered = $this->db->count_records_select(
            'local_educambot_log',
            'timecreated >= :since AND confidence >= :minconf',
            ['since' => $since, 'minconf' => self::MIN_CONFIDENCE]
        );

        $highconfidence = $this->db->count_records_select(
            'local_educambot_log',
            'timecreated >= :since AND confidence >= :highconf',
            ['since' => $since, 'highconf' => 0.75]
        );

        $avgconf = $this->db->get_field_sql(
            "SELECT AVG(confidence)
               FROM {local_educambot_log}
              WHERE timecreated >= :since AND confidence IS NOT NULL",
            ['since' => $since]
        );

        return [
            'total_questions' => $totalquestions,
            'answer_rate' => round(($answered / $totalquestions) * 100, 2),
            'avg_confidence' => round($avgconf ?? 0, 3),
            'high_confidence_rate' => round(($highconfidence / $totalquestions) * 100, 2),
        ];
    }

    /**
     * Clusters similar questions together.
     *
     * @param array $questions Questions to cluster
     * @return array Clusters
     */
    protected function cluster_similar_questions(array $questions): array {
        if (empty($questions)) {
            return [];
        }

        $clusters = [];

        foreach ($questions as $question) {
            $normalized = text_helper::normalize($question->question);
            $addedtocluster = false;

            // Try to add to existing cluster.
            foreach ($clusters as &$cluster) {
                $representative = text_helper::normalize($cluster['representative']->question);
                $similarity = text_helper::string_similarity($normalized, $representative);

                if ($similarity >= 0.75) {
                    $cluster['members'][] = $question;
                    $cluster['total_frequency'] += (int)$question->frequency;
                    $cluster['avg_confidence'] = (($cluster['avg_confidence'] * (count($cluster['members']) - 1)) +
                        ($question->avgconf ?? 0)) / count($cluster['members']);
                    $addedtocluster = true;
                    break;
                }
            }
            unset($cluster);

            // Create new cluster if not added.
            if (!$addedtocluster) {
                $clusters[] = [
                    'representative' => $question,
                    'members' => [$question],
                    'total_frequency' => (int)$question->frequency,
                    'avg_confidence' => $question->avgconf ?? 0,
                ];
            }
        }

        // Sort clusters by total frequency.
        usort($clusters, function($a, $b) {
            return $b['total_frequency'] <=> $a['total_frequency'];
        });

        return $clusters;
    }

    /**
     * Extracts important terms from a question for keyword suggestions.
     *
     * @param string $question Question text
     * @return array Important terms
     */
    protected function extract_important_terms(string $question): array {
        $tokens = text_helper::tokenize($question);
        $normalized = array_map([text_helper::class, 'normalize'], $tokens);

        // Filter out common words.
        $commonwords = ['como', 'que', 'donde', 'cuando', 'porque', 'para', 'puedo', 'hacer',
            'how', 'what', 'where', 'when', 'why', 'can', 'do', 'i', 'the', 'a', 'an'];

        $important = array_filter($normalized, function($token) use ($commonwords) {
            return !in_array($token, $commonwords, true) && strlen($token) > 3;
        });

        // Return unique important terms.
        return array_values(array_unique($important));
    }

    /**
     * Records feedback about a bot response for learning.
     *
     * @param int $logid Log entry ID
     * @param bool $helpful Was the response helpful?
     * @param string|null $comment Optional user comment
     * @return bool Success
     */
    public function record_feedback(int $logid, bool $helpful, ?string $comment = null): bool {
        $record = new stdClass();
        $record->logid = $logid;
        $record->helpful = $helpful ? 1 : 0;
        $record->comment = $comment;
        $record->timecreated = time();

        try {
            $this->db->insert_record('local_educambot_feedback', $record);
            $this->cache->purge();
            return true;
        } catch (\Exception $e) {
            debugging('Failed to record feedback: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return false;
        }
    }

    /**
     * Gets recommended knowledge entries to create based on unanswered questions.
     *
     * @param int $limit Number of recommendations
     * @return array Recommendations
     */
    public function get_knowledge_recommendations(int $limit = 10): array {
        $cachekey = 'knowledge_recommendations_' . $limit;
        $cached = $this->cache->get($cachekey);

        if ($cached !== false) {
            return $cached;
        }

        $since = time() - (30 * 86400); // Last 30 days.
        $unanswered = $this->db->get_records_sql(
            "SELECT question, COUNT(*) as frequency
               FROM {local_educambot_unanswered}
              WHERE timecreated >= :since
           GROUP BY question
           ORDER BY frequency DESC",
            ['since' => $since],
            0,
            $limit
        );

        $recommendations = [];
        foreach ($unanswered as $record) {
            $keywords = text_helper::tokenize($record->question);
            $important = $this->extract_important_terms($record->question);

            $recommendations[] = [
                'question' => $record->question,
                'frequency' => (int)$record->frequency,
                'priority' => $this->calculate_priority((int)$record->frequency),
                'suggested_title' => $this->generate_title($record->question),
                'suggested_keywords' => $important,
                'suggested_topics' => $this->suggest_topics($keywords),
            ];
        }

        $this->cache->set($cachekey, $recommendations, 3600); // Cache for 1 hour.

        return $recommendations;
    }

    /**
     * Calculates priority based on frequency.
     *
     * @param int $frequency Question frequency
     * @return string Priority level
     */
    protected function calculate_priority(int $frequency): string {
        if ($frequency >= 10) {
            return 'critical';
        } else if ($frequency >= 5) {
            return 'high';
        } else if ($frequency >= 3) {
            return 'medium';
        }
        return 'low';
    }

    /**
     * Generates a title from a question.
     *
     * @param string $question Question text
     * @return string Suggested title
     */
    protected function generate_title(string $question): string {
        // Remove question marks and extra spaces.
        $title = trim(str_replace('?', '', $question));

        // Capitalize first letter.
        $title = ucfirst($title);

        // Limit length.
        if (strlen($title) > 100) {
            $title = substr($title, 0, 97) . '...';
        }

        return $title;
    }

    /**
     * Suggests topics based on keywords.
     *
     * @param array $keywords Keywords from question
     * @return array Suggested topic names
     */
    protected function suggest_topics(array $keywords): array {
        $topicmap = [
            'curso' => 'Cursos',
            'course' => 'Courses',
            'tarea' => 'Tareas',
            'assignment' => 'Assignments',
            'calificacion' => 'Calificaciones',
            'grade' => 'Grades',
            'usuario' => 'Usuarios',
            'user' => 'Users',
            'configuracion' => 'Configuración',
            'config' => 'Configuration',
            'error' => 'Troubleshooting',
            'problema' => 'Troubleshooting',
        ];

        $topics = [];
        foreach ($keywords as $keyword) {
            $normalized = text_helper::normalize($keyword);
            if (isset($topicmap[$normalized])) {
                $topics[] = $topicmap[$normalized];
            }
        }

        return array_values(array_unique($topics));
    }
}
