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
 * Bot engine - handles question processing and response generation.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Bot engine class.
 */
class engine {

    /** @var int Course ID for context filtering. */
    protected $courseid;

    /** @var int User ID for role filtering. */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID for context.
     * @param int $userid User ID for role filtering.
     */
    public function __construct(int $courseid = SITEID, int $userid = 0) {
        global $USER;
        $this->courseid = $courseid;
        $this->userid = $userid ?: $USER->id;
    }

    /**
     * Process a question and return a response.
     *
     * @param string $question The user's question
     * @return array Response data with keys: response, ruleid, confidence
     */
    public function respond($question) {
        global $DB;

        // Normalize the question.
        $normalized = $this->normalize_text($question);

        // Get user's current language.
        $userlang = current_language();
        $userlang = substr($userlang, 0, 2); // Get base language (es, en, fr, etc.).

        // Get user's roles in current context.
        $userroles = $this->get_user_roles();

        // Build SQL to filter rules by language preference.
        // Prefer rules in user's language, fallback to 'es' (default).
        $sql = "SELECT * FROM {local_educambot_rule}
                WHERE enabled = 1
                ORDER BY
                    CASE WHEN lang = :userlang THEN 0
                         WHEN lang = 'es' THEN 1
                         ELSE 2 END,
                    id ASC";
        $rules = $DB->get_records_sql($sql, ['userlang' => $userlang]);

        // Filter rules by role and course.
        $filteredrules = $this->filter_rules_by_context($rules, $userroles);

        $best_match = null;
        $best_score = 0;

        foreach ($filteredrules as $rule) {
            $score = $this->calculate_match_score($normalized, $rule);

            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $rule;
            }
        }

        // Return response if we have a good match (score > 0).
        if ($best_match && $best_score > 0) {
            return [
                'response' => $best_match->response,
                'ruleid' => $best_match->id,
                'confidence' => min(1.0, $best_score / 100), // Normalize to 0-1.
            ];
        }

        // No match found.
        return [
            'response' => null,
            'ruleid' => null,
            'confidence' => 0,
        ];
    }

    /**
     * Get user's role shortnames in current context.
     *
     * @return array Array of role shortnames.
     */
    protected function get_user_roles(): array {
        $roles = [];

        try {
            if ($this->courseid > SITEID) {
                $context = \context_course::instance($this->courseid);
            } else {
                $context = \context_system::instance();
            }

            $userroles = get_user_roles($context, $this->userid, true);
            foreach ($userroles as $role) {
                $roles[] = $role->shortname;
            }
        } catch (\Exception $e) {
            // If context doesn't exist, return empty roles.
        }

        return $roles;
    }

    /**
     * Filter rules by role and course restrictions.
     *
     * @param array $rules Array of rule records.
     * @param array $userroles Array of user's role shortnames.
     * @return array Filtered rules.
     */
    protected function filter_rules_by_context(array $rules, array $userroles): array {
        $autolang = get_config('local_educambot', 'autolang');
        $userlang = current_language();
        $userlang = substr($userlang, 0, 2);

        $filtered = [];
        $seenpatterns = [];

        foreach ($rules as $rule) {
            // Check role filter.
            if (!empty($rule->roles)) {
                $ruleroles = array_map('trim', explode(',', $rule->roles));
                if (!array_intersect($userroles, $ruleroles)) {
                    continue; // User doesn't have required role.
                }
            }

            // Check course filter.
            if (!empty($rule->courses)) {
                $rulecourses = array_map('trim', explode(',', $rule->courses));
                if (!in_array($this->courseid, $rulecourses)) {
                    continue; // Not in a valid course.
                }
            }

            // If auto-lang enabled, prefer rules in user's language.
            // Track patterns to avoid duplicates (prefer user's language).
            if ($autolang) {
                $patternkey = $rule->langparent ?: $rule->id;

                if (!isset($seenpatterns[$patternkey])) {
                    $seenpatterns[$patternkey] = $rule;
                } else {
                    // Replace if current rule is in user's language.
                    $existingrule = $seenpatterns[$patternkey];
                    if ($rule->lang === $userlang && $existingrule->lang !== $userlang) {
                        $seenpatterns[$patternkey] = $rule;
                    }
                }
            } else {
                $filtered[$rule->id] = $rule;
            }
        }

        return $autolang ? array_values($seenpatterns) : $filtered;
    }

    /**
     * Normalize text for matching.
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
private function normalize_text($text) {
    // Convert to lowercase - use PHP's mb_strtolower for better UTF-8 support
    $text = mb_strtolower($text, 'UTF-8');

    // Remove extra whitespace.
    $text = trim(preg_replace('/\s+/', ' ', $text));

    // Remove punctuation.
    $text = preg_replace('/[^\w\s]/u', '', $text);

    return $text;
}

    /**
     * Calculate match score between question and rule.
     *
     * @param string $question Normalized question
     * @param object $rule Rule record from database
     * @return int Match score (higher is better)
     */
    private function calculate_match_score($question, $rule) {
        $score = 0;

        // Normalize pattern.
        $pattern = $this->normalize_text($rule->pattern);

        // Check for exact match (highest score).
        if ($pattern === $question) {
            return 100;
        }

        // Check if pattern is contained in question.
        if (strpos($question, $pattern) !== false) {
            $score += 50;
        }

        // Check if question is contained in pattern.
        if (strpos($pattern, $question) !== false) {
            $score += 40;
        }

        // Split into words for keyword matching.
        $question_words = explode(' ', $question);
        $pattern_words = explode(' ', $pattern);

        // Calculate word overlap.
        $common_words = array_intersect($question_words, $pattern_words);
        if (count($pattern_words) > 0) {
            $word_score = (count($common_words) / count($pattern_words)) * 30;
            $score += $word_score;
        }

        // Check keywords if defined.
        if (!empty($rule->keywords)) {
            $keywords = array_filter(array_map('trim', explode("\n", $rule->keywords)));
            foreach ($keywords as $keyword) {
                $keyword = $this->normalize_text($keyword);
                if (!empty($keyword) && strpos($question, $keyword) !== false) {
                    $score += 20;
                }
            }
        }

        return (int)$score;
    }
}
