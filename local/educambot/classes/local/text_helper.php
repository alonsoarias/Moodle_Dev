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
 * Helpers for text normalisation and similarity used by the chatbot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local;

use core_text;

/**
 * Utility helpers shared by the reasoning engine components.
 */
class text_helper {
    /**
     * Stopwords used to improve token based matching. They include common English and Spanish words so that
     * the matcher focuses on meaningful terms from the user question.
     */
    public const STOPWORDS = [
        'a', 'about', 'after', 'again', 'all', 'also', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'because',
        'been', 'before', 'but', 'by', 'can', 'could', 'de', 'del', 'do', 'does', 'each', 'el', 'ella', 'ellas',
        'ellos', 'en', 'era', 'eran', 'eres', 'es', 'esta', 'estaba', 'estamos', 'estan', 'este', 'for', 'from',
        'had', 'has', 'have', 'he', 'how', 'i', 'in', 'is', 'it', 'la', 'las', 'le', 'les', 'lo', 'los', 'me',
        'mas', 'más', 'mi', 'mis', 'much', 'muy', 'no', 'nos', 'not', 'now', 'of', 'on', 'or', 'our', 'para', 'por',
        'que', 'se', 'ser', 'she', 'should', 'si', 'so', 'some', 'su', 'sus', 'te', 'than', 'that', 'the', 'their',
        'them', 'then', 'there', 'these', 'they', 'this', 'those', 'through', 'to', 'una', 'unas', 'uno', 'unos',
        'very', 'was', 'we', 'what', 'when', 'where', 'which', 'who', 'why', 'will', 'with', 'would', 'you', 'your'
    ];

    /**
     * Normalises text to lower case, removing punctuation and duplicated whitespace.
     *
     * @param string $text
     * @return string
     */
    public static function normalize(string $text): string {
        $text = core_text::strtolower($text);
        $text = core_text::specialtoascii($text);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string)$text);
    }

    /**
     * Tokenises a string applying stopword filtering and returning unique terms.
     *
     * @param string $text
     * @return array
     */
    public static function tokenize(string $text): array {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }
        $tokens = preg_split('/\s+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($tokens)) {
            return [];
        }
        $tokens = array_map('trim', $tokens);
        $tokens = array_filter($tokens, static function(string $token) {
            return $token !== '' && !in_array($token, self::STOPWORDS, true);
        });
        return array_values(array_unique($tokens));
    }

    /**
     * Calculates the overlap ratio between the entry tokens and the user tokens.
     *
     * @param array $phasetokens
     * @param array $questiontokens
     * @return float
     */
    public static function token_overlap_score(array $phasetokens, array $questiontokens): float {
        if (empty($phasetokens) || empty($questiontokens)) {
            return 0.0;
        }
        $intersection = array_intersect($phasetokens, $questiontokens);
        if (empty($intersection)) {
            return 0.0;
        }
        $ratio = count($intersection) / max(1, count($phasetokens));
        $questionratio = count($intersection) / max(1, count($questiontokens));
        return max($ratio, $questionratio);
    }

    /**
     * Returns a similarity score between two normalised phrases using a hybrid of similar_text and levenshtein.
     *
     * @param string $phrase
     * @param string $question
     * @return float
     */
    public static function string_similarity(string $phrase, string $question): float {
        if ($phrase === '' || $question === '') {
            return 0.0;
        }
        $percent = 0.0;
        similar_text($phrase, $question, $percent);
        if ($percent >= 90) {
            return 0.95;
        }
        if ($percent >= 75) {
            return $percent / 100.0;
        }

        $distance = levenshtein($phrase, $question);
        $longest = max(strlen($phrase), strlen($question));
        if ($longest === 0) {
            return 0.0;
        }
        $levratio = 1 - ($distance / $longest);
        return max($levratio, $percent / 100.0);
    }
}
