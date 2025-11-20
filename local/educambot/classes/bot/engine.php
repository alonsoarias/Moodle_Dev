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

        // Get all enabled rules.
        $rules = $DB->get_records('local_educambot_rule', ['enabled' => 1]);

        $best_match = null;
        $best_score = 0;

        foreach ($rules as $rule) {
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
     * Normalize text for matching.
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
    private function normalize_text($text) {
        // Convert to lowercase.
        $text = core_text::strtolower($text);

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
