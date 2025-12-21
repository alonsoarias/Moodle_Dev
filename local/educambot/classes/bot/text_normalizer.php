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
 * Text normalizer for educambot - advanced text processing for better matching.
 *
 * Features:
 * - Loads synonyms and abbreviations from external JSON files
 * - Preserves Spanish accents for semantic meaning
 * - Expands common abbreviations (q -> que, xq -> porque)
 * - Handles typos with Levenshtein distance
 * - Removes stopwords for better keyword matching
 * - Synonym expansion for common terms
 * - Word-by-word matching for better accuracy
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Text normalizer class - advanced text processing for bot matching.
 */
class text_normalizer {

    /** @var array Spanish stopwords to optionally remove */
    private const STOPWORDS = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
        'de', 'del', 'al', 'a', 'en', 'con', 'por', 'para',
        'y', 'o', 'u', 'e', 'ni', 'que', 'se', 'su', 'sus',
        'lo', 'le', 'les', 'me', 'te', 'nos', 'os',
        'mi', 'tu', 'mis', 'tus', 'este', 'esta', 'esto',
        'ese', 'esa', 'eso', 'aquel', 'aquella', 'aquello',
        'es', 'son', 'fue', 'era', 'ser', 'estar', 'esta',
        'muy', 'mas', 'menos', 'tan', 'tanto',
        'yo', 'tu', 'el', 'ella', 'nosotros', 'ellos', 'ellas',
    ];

    /** @var array Loaded abbreviations from JSON */
    private $abbreviations = [];

    /** @var array Loaded synonyms from JSON */
    private $synonyms = [];

    /** @var array Reverse synonym map for quick lookup */
    private $reverseSynonymMap = [];

    /** @var bool Whether to remove stopwords */
    private $removeStopwords = false;

    /** @var bool Whether to expand synonyms */
    private $expandSynonyms = true;

    /** @var bool Whether data has been loaded */
    private static $dataLoaded = false;

    /** @var array Cached abbreviations (static for performance) */
    private static $cachedAbbreviations = [];

    /** @var array Cached synonyms (static for performance) */
    private static $cachedSynonyms = [];

    /** @var array Cached reverse map (static for performance) */
    private static $cachedReverseMap = [];

    /**
     * Constructor.
     *
     * @param bool $removeStopwords Whether to remove stopwords
     * @param bool $expandSynonyms Whether to expand synonyms
     */
    public function __construct(bool $removeStopwords = false, bool $expandSynonyms = true) {
        $this->removeStopwords = $removeStopwords;
        $this->expandSynonyms = $expandSynonyms;

        $this->load_data();
    }

    /**
     * Load abbreviations and synonyms from JSON files.
     */
    private function load_data(): void {
        global $CFG;

        // Use cached data if available.
        if (self::$dataLoaded) {
            $this->abbreviations = self::$cachedAbbreviations;
            $this->synonyms = self::$cachedSynonyms;
            $this->reverseSynonymMap = self::$cachedReverseMap;
            return;
        }

        $datapath = $CFG->dirroot . '/local/educambot/db/data/';

        // Load abbreviations.
        $abbrevFile = $datapath . 'abbreviations.json';
        if (file_exists($abbrevFile)) {
            $content = file_get_contents($abbrevFile);
            $data = json_decode($content, true);
            if (isset($data['abbreviations'])) {
                $this->abbreviations = $data['abbreviations'];
            }
        }

        // Load synonyms.
        $synFile = $datapath . 'synonyms.json';
        if (file_exists($synFile)) {
            $content = file_get_contents($synFile);
            $data = json_decode($content, true);
            if (isset($data['synonyms'])) {
                $this->synonyms = $data['synonyms'];
                $this->build_reverse_synonym_map();
            }
        }

        // Cache the loaded data.
        self::$cachedAbbreviations = $this->abbreviations;
        self::$cachedSynonyms = $this->synonyms;
        self::$cachedReverseMap = $this->reverseSynonymMap;
        self::$dataLoaded = true;
    }

    /**
     * Build a reverse map for quick synonym lookup.
     * Maps each synonym to its canonical form (the key).
     */
    private function build_reverse_synonym_map(): void {
        $this->reverseSynonymMap = [];
        foreach ($this->synonyms as $canonical => $synonymList) {
            foreach ($synonymList as $synonym) {
                $this->reverseSynonymMap[mb_strtolower($synonym, 'UTF-8')] = $canonical;
            }
        }
    }

    /**
     * Normalize text for matching.
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
    public function normalize(string $text): string {
        // Convert to lowercase using UTF-8 aware function.
        $text = mb_strtolower($text, 'UTF-8');

        // Expand abbreviations.
        $text = $this->expand_abbreviations($text);

        // Remove excessive punctuation but keep question marks for intent detection.
        $text = preg_replace('/[^\p{L}\p{N}\s\?¿]/u', ' ', $text);

        // Normalize whitespace.
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Remove stopwords if configured.
        if ($this->removeStopwords) {
            $text = $this->remove_stopwords($text);
        }

        return $text;
    }

    /**
     * Normalize text and return additional metadata.
     *
     * @param string $text Text to normalize
     * @return array ['normalized' => string, 'words' => array, 'keywords' => array]
     */
    public function analyze(string $text): array {
        $normalized = $this->normalize($text);
        $words = array_filter(explode(' ', $normalized));

        // Extract meaningful keywords (non-stopwords).
        $keywords = array_filter($words, function($word) {
            return !in_array($word, self::STOPWORDS) && mb_strlen($word) > 2;
        });

        return [
            'original' => $text,
            'normalized' => $normalized,
            'words' => array_values($words),
            'keywords' => array_values($keywords),
            'word_count' => count($words),
            'keyword_count' => count($keywords),
        ];
    }

    /**
     * Expand common abbreviations in text.
     *
     * @param string $text Text with potential abbreviations
     * @return string Text with abbreviations expanded
     */
    public function expand_abbreviations(string $text): string {
        $words = explode(' ', $text);
        $expanded = [];

        foreach ($words as $word) {
            $clean = preg_replace('/[^\p{L}]/u', '', $word);
            $cleanLower = mb_strtolower($clean, 'UTF-8');
            if (isset($this->abbreviations[$cleanLower])) {
                $expanded[] = $this->abbreviations[$cleanLower];
            } else {
                $expanded[] = $word;
            }
        }

        return implode(' ', $expanded);
    }

    /**
     * Remove stopwords from text.
     *
     * @param string $text Text to process
     * @return string Text without stopwords
     */
    public function remove_stopwords(string $text): string {
        $words = explode(' ', $text);
        $filtered = array_filter($words, function($word) {
            return !in_array($word, self::STOPWORDS) && mb_strlen($word) > 1;
        });
        return implode(' ', $filtered);
    }

    /**
     * Get synonyms for a word.
     *
     * @param string $word Word to find synonyms for
     * @return array Array of synonyms (empty if none found)
     */
    public function get_synonyms(string $word): array {
        $word = mb_strtolower($word, 'UTF-8');

        // Direct lookup.
        if (isset($this->synonyms[$word])) {
            return $this->synonyms[$word];
        }

        // Reverse lookup (find canonical form, then return its synonyms).
        if (isset($this->reverseSynonymMap[$word])) {
            $canonical = $this->reverseSynonymMap[$word];
            return $this->synonyms[$canonical] ?? [];
        }

        return [];
    }

    /**
     * Get the canonical form of a word (the main synonym).
     *
     * @param string $word Word to canonicalize
     * @return string Canonical form or original word
     */
    public function get_canonical(string $word): string {
        $word = mb_strtolower($word, 'UTF-8');

        if (isset($this->synonyms[$word])) {
            return $word;
        }

        if (isset($this->reverseSynonymMap[$word])) {
            return $this->reverseSynonymMap[$word];
        }

        return $word;
    }

    /**
     * Check if two words are synonyms.
     *
     * @param string $word1 First word
     * @param string $word2 Second word
     * @return bool True if synonyms
     */
    public function are_synonyms(string $word1, string $word2): bool {
        $word1 = mb_strtolower($word1, 'UTF-8');
        $word2 = mb_strtolower($word2, 'UTF-8');

        if ($word1 === $word2) {
            return true;
        }

        // Get canonical forms and compare.
        $canonical1 = $this->get_canonical($word1);
        $canonical2 = $this->get_canonical($word2);

        return $canonical1 === $canonical2;
    }

    /**
     * Expand a word to include all its synonyms.
     *
     * @param string $word Word to expand
     * @return array Array containing the word and all synonyms
     */
    public function expand_word(string $word): array {
        $synonyms = $this->get_synonyms($word);
        if (empty($synonyms)) {
            return [$word];
        }
        return array_unique(array_merge([$word], $synonyms));
    }

    /**
     * Check if any word in text1 is a synonym of any word in text2.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return array Match info with count and matched words
     */
    public function find_synonym_matches(string $text1, string $text2): array {
        $words1 = array_filter(explode(' ', mb_strtolower($text1, 'UTF-8')));
        $words2 = array_filter(explode(' ', mb_strtolower($text2, 'UTF-8')));

        $matches = [];
        $matchedPairs = [];

        foreach ($words1 as $w1) {
            if (mb_strlen($w1) < 3) {
                continue;
            }
            foreach ($words2 as $w2) {
                if (mb_strlen($w2) < 3) {
                    continue;
                }
                if ($this->are_synonyms($w1, $w2)) {
                    $matches[] = $w1;
                    $matchedPairs[] = ['word' => $w1, 'synonym' => $w2, 'canonical' => $this->get_canonical($w1)];
                    break;
                }
            }
        }

        return [
            'count' => count($matches),
            'matches' => array_unique($matches),
            'pairs' => $matchedPairs,
        ];
    }

    /**
     * Calculate similarity between two texts.
     * Combines multiple metrics for better accuracy.
     *
     * @param string $text1 First text (normalized)
     * @param string $text2 Second text (normalized)
     * @return float Similarity score 0-1
     */
    public function calculate_similarity(string $text1, string $text2): float {
        // Exact match.
        if ($text1 === $text2) {
            return 1.0;
        }

        $scores = [];

        // Levenshtein distance (normalized).
        $maxLen = max(mb_strlen($text1), mb_strlen($text2));
        if ($maxLen > 0 && $maxLen <= 255) {
            $levenshtein = levenshtein($text1, $text2);
            $scores['levenshtein'] = 1 - ($levenshtein / $maxLen);
        }

        // Word overlap (Jaccard similarity).
        $words1 = array_filter(explode(' ', $text1));
        $words2 = array_filter(explode(' ', $text2));

        if (!empty($words1) && !empty($words2)) {
            $intersection = count(array_intersect($words1, $words2));
            $union = count(array_unique(array_merge($words1, $words2)));
            $scores['jaccard'] = $intersection / $union;

            // Synonym-aware word matching.
            $synonymMatches = $this->find_synonym_matches($text1, $text2);
            $scores['synonym'] = $synonymMatches['count'] / max(count($words1), count($words2));
        }

        // Containment check.
        if (mb_strpos($text1, $text2) !== false || mb_strpos($text2, $text1) !== false) {
            $scores['containment'] = 0.8;
        }

        // Calculate weighted average.
        $weights = [
            'levenshtein' => 0.25,
            'jaccard' => 0.25,
            'synonym' => 0.35,
            'containment' => 0.15,
        ];

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($scores as $metric => $score) {
            if (isset($weights[$metric])) {
                $weightedSum += $score * $weights[$metric];
                $totalWeight += $weights[$metric];
            }
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Find best matches for a query in a list of candidates.
     *
     * @param string $query Query text
     * @param array $candidates List of candidate texts
     * @param float $threshold Minimum similarity threshold
     * @param int $limit Maximum number of results
     * @return array Sorted matches with scores
     */
    public function find_best_matches(string $query, array $candidates, float $threshold = 0.3, int $limit = 5): array {
        $normalizedQuery = $this->normalize($query);
        $matches = [];

        foreach ($candidates as $key => $candidate) {
            $normalizedCandidate = $this->normalize($candidate);
            $similarity = $this->calculate_similarity($normalizedQuery, $normalizedCandidate);

            if ($similarity >= $threshold) {
                $matches[] = [
                    'key' => $key,
                    'text' => $candidate,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity descending.
        usort($matches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($matches, 0, $limit);
    }

    /**
     * Check if text contains any of the given keywords (with synonym support).
     *
     * @param string $text Text to search in
     * @param array $keywords Keywords to search for
     * @param bool $useSynonyms Whether to also check synonyms
     * @return array ['found' => bool, 'matches' => array]
     */
    public function contains_keywords(string $text, array $keywords, bool $useSynonyms = true): array {
        $normalizedText = $this->normalize($text);
        $textWords = explode(' ', $normalizedText);
        $matches = [];

        foreach ($keywords as $keyword) {
            $normalizedKeyword = $this->normalize($keyword);
            $keywordWords = explode(' ', $normalizedKeyword);

            // Direct phrase match.
            if (mb_strpos($normalizedText, $normalizedKeyword) !== false) {
                $matches[] = $keyword;
                continue;
            }

            // Word-by-word match with synonyms.
            if ($useSynonyms) {
                $allMatched = true;
                foreach ($keywordWords as $kw) {
                    if (mb_strlen($kw) < 2) {
                        continue;
                    }
                    $found = false;
                    foreach ($textWords as $tw) {
                        if ($this->are_synonyms($tw, $kw)) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $allMatched = false;
                        break;
                    }
                }
                if ($allMatched && count($keywordWords) > 0) {
                    $matches[] = $keyword;
                }
            }
        }

        return [
            'found' => !empty($matches),
            'matches' => array_unique($matches),
            'count' => count(array_unique($matches)),
        ];
    }

    /**
     * Check if a single word from keywords exists in text (word-by-word matching).
     *
     * @param string $text Text to search in
     * @param array $keywords Individual keywords to match
     * @param bool $useSynonyms Whether to use synonym matching
     * @return array Match results
     */
    public function match_individual_words(string $text, array $keywords, bool $useSynonyms = true): array {
        $normalizedText = $this->normalize($text);
        $textWords = array_filter(explode(' ', $normalizedText));
        $matches = [];
        $matchedKeywords = [];

        foreach ($textWords as $textWord) {
            if (mb_strlen($textWord) < 3) {
                continue;
            }

            foreach ($keywords as $keyword) {
                $normalizedKeyword = mb_strtolower(trim($keyword), 'UTF-8');
                $keywordWords = array_filter(explode(' ', $normalizedKeyword));

                foreach ($keywordWords as $kw) {
                    if (mb_strlen($kw) < 3) {
                        continue;
                    }

                    // Direct match.
                    if ($textWord === $kw) {
                        $matches[] = ['text_word' => $textWord, 'keyword' => $keyword, 'type' => 'exact'];
                        $matchedKeywords[] = $keyword;
                    } else if ($useSynonyms && $this->are_synonyms($textWord, $kw)) {
                        // Synonym match.
                        $matches[] = ['text_word' => $textWord, 'keyword' => $keyword, 'type' => 'synonym'];
                        $matchedKeywords[] = $keyword;
                    }
                }
            }
        }

        return [
            'found' => !empty($matches),
            'matches' => $matches,
            'matched_keywords' => array_unique($matchedKeywords),
            'count' => count(array_unique($matchedKeywords)),
        ];
    }

    /**
     * Extract entities from text (course names, dates, etc.).
     *
     * @param string $text Text to analyze
     * @return array Extracted entities
     */
    public function extract_entities(string $text): array {
        $entities = [
            'dates' => [],
            'numbers' => [],
            'course_references' => [],
        ];

        // Extract numbers.
        preg_match_all('/\d+/', $text, $numbers);
        $entities['numbers'] = $numbers[0] ?? [];

        // Extract date-like patterns.
        preg_match_all('/\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?/', $text, $dates);
        $entities['dates'] = $dates[0] ?? [];

        // Extract relative dates.
        $relativeDates = ['hoy', 'manana', 'ayer', 'esta semana', 'proxima semana',
                          'este mes', 'proximo mes', 'lunes', 'martes', 'miercoles',
                          'jueves', 'viernes', 'sabado', 'domingo'];
        foreach ($relativeDates as $relDate) {
            if (mb_stripos($text, $relDate) !== false) {
                $entities['dates'][] = $relDate;
            }
        }

        return $entities;
    }

    /**
     * Clear cached data (useful for testing or after JSON updates).
     */
    public static function clear_cache(): void {
        self::$dataLoaded = false;
        self::$cachedAbbreviations = [];
        self::$cachedSynonyms = [];
        self::$cachedReverseMap = [];
    }

    /**
     * Get all loaded synonyms (for debugging/admin).
     *
     * @return array All synonyms
     */
    public function get_all_synonyms(): array {
        return $this->synonyms;
    }

    /**
     * Get all loaded abbreviations (for debugging/admin).
     *
     * @return array All abbreviations
     */
    public function get_all_abbreviations(): array {
        return $this->abbreviations;
    }
}
