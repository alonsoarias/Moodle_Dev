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
 * Rule based engine for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

use cache;
use context_system;
use core_text;
use stdClass;

/**
 * Implements a flexible rule engine with fuzzy matching heuristics.
 */
class engine {
    /**
     * Stopwords used to improve token based matching. They include common English and Spanish words so that
     * the matcher focuses on meaningful terms from the user question.
     */
    protected const STOPWORDS = [
        'a', 'about', 'after', 'again', 'all', 'also', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'because',
        'been', 'before', 'but', 'by', 'can', 'could', 'de', 'del', 'do', 'does', 'each', 'el', 'ella', 'ellas',
        'ellos', 'en', 'era', 'eran', 'eres', 'es', 'esta', 'estaba', 'estamos', 'estan', 'este', 'for', 'from',
        'had', 'has', 'have', 'he', 'how', 'i', 'in', 'is', 'it', 'la', 'las', 'le', 'les', 'lo', 'los', 'me',
        'mas', 'más', 'mi', 'mis', 'much', 'muy', 'no', 'nos', 'not', 'now', 'of', 'on', 'or', 'our', 'para', 'por',
        'que', 'se', 'ser', 'she', 'should', 'si', 'so', 'some', 'su', 'sus', 'te', 'than', 'that', 'the', 'their',
        'them', 'then', 'there', 'these', 'they', 'this', 'those', 'through', 'to', 'una', 'unas', 'uno', 'unos',
        'very', 'was', 'we', 'what', 'when', 'where', 'which', 'who', 'why', 'will', 'with', 'would', 'you', 'your'
    ];

    /** @var \moodle_database */
    protected $db;

    /** @var int|null */
    protected $userid;

    /** @var string|null */
    protected $pageidentifier;

    /** @var string|null Normalised page identifier */
    protected ?string $normalizedpage = null;

    /**
     * Constructor.
     *
     * @param int|null $userid
     * @param string|null $pageidentifier
     */
    public function __construct(?int $userid, ?string $pageidentifier) {
        global $DB;

        $this->db = $DB;
        $this->userid = $userid;
        $this->pageidentifier = $pageidentifier;
        $this->normalizedpage = $pageidentifier ? $this->normalize($pageidentifier) : null;
    }

    /**
     * Finds the best response for the given question.
     *
     * @param string $question
     * @return array{response:string|null,ruleid:int|null,confidence:float,suggestions:array}
     */
    public function respond(string $question): array {
        $question = trim($question);
        if ($question === '') {
            return ['response' => null, 'ruleid' => null, 'confidence' => 0.0, 'suggestions' => []];
        }

        $scores = $this->rank_entries($question);
        if (empty($scores)) {
            return ['response' => null, 'ruleid' => null, 'confidence' => 0.0, 'suggestions' => $this->get_suggestions()];
        }

        $winner = $scores[0];
        $response = format_text($winner['entry']->response, FORMAT_HTML, ['filter' => true]);

        return [
            'response' => $response,
            'ruleid' => (int)$winner['entry']->id,
            'confidence' => round(min(1, $winner['score']), 4),
            'suggestions' => $this->build_response_suggestions($scores),
        ];
    }

    /**
     * Retrieve proactive suggestions.
     *
     * @return array[]
     */
    public function get_suggestions(): array {
        $records = $this->db->get_records('local_educambot_rule', ['enabled' => 1, 'suggested' => 1], 'timemodified DESC');
        $suggestions = [];
        $normalizedpage = $this->normalizedpage ?? '';
        foreach ($records as $record) {
            if ($normalizedpage !== '' && !empty($record->contexts)) {
                $contexts = $this->explode_lines($record->contexts);
                $match = false;
                foreach ($contexts as $context) {
                    $normalizedcontext = $this->normalize($context);
                    if ($normalizedcontext !== '' && str_contains($normalizedpage, $normalizedcontext)) {
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    continue;
                }
            }
            $suggestions[] = [
                'id' => (int)$record->id,
                'text' => format_string($record->pattern),
            ];
            if (count($suggestions) >= 6) {
                break;
            }
        }

        if (empty($suggestions)) {
            foreach ($records as $record) {
                $suggestions[] = [
                    'id' => (int)$record->id,
                    'text' => format_string($record->pattern),
                ];
                if (count($suggestions) >= 6) {
                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Retrieves entries from cache or database.
     *
     * @return stdClass[]
     */
    protected function get_entries(): array {
        $cache = cache::make('local_educambot', 'rules');
        $entries = $cache->get('all');
        if ($entries === false) {
            $entries = $this->db->get_records('local_educambot_rule', ['enabled' => 1], 'timemodified DESC');
            $cache->set('all', $entries);
        }
        return $entries;
    }

    /**
     * Rank all entries for the provided question.
     *
     * @param string $question
     * @param bool $ignoreroles When true, role checks are skipped.
     * @return array
     */
    protected function rank_entries(string $question, bool $ignoreroles = false): array {
        $entries = $this->get_entries();
        $normalizedquestion = $this->normalize($question);
        $questiontokens = $this->tokenize($question);
        $normalizedpage = $this->normalizedpage ?? '';
        $scores = [];

        foreach ($entries as $entry) {
            if (!$ignoreroles && !$this->entry_matches_roles($entry)) {
                continue;
            }

            $score = $this->calculate_score($entry, $normalizedquestion, $question, $questiontokens, $normalizedpage);
            if ($score <= 0) {
                continue;
            }

            $scores[] = [
                'entry' => $entry,
                'score' => min(1.5, $score),
            ];
        }

        usort($scores, static function(array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scores;
    }

    /**
     * Calculates the matching score for an entry.
     *
     * @param stdClass $entry
     * @param string $normalizedquestion
     * @param string $originalquestion
     * @param array $questiontokens
     * @param string $normalizedpage
     * @return float
     */
    protected function calculate_score(
        stdClass $entry,
        string $normalizedquestion,
        string $originalquestion,
        array $questiontokens,
        string $normalizedpage
    ): float {
        $score = 0.0;
        $phrases = $this->get_phrases($entry);

        foreach ($phrases as $phrase) {
            $normalizedphrase = $this->normalize($phrase);
            if ($normalizedphrase === '') {
                continue;
            }
            if ($normalizedphrase === $normalizedquestion) {
                $score += 1.0;
                break;
            }

            $wildcardscore = $this->match_wildcard($phrase, $originalquestion, $normalizedquestion);
            if ($wildcardscore !== null) {
                if ($wildcardscore > 0) {
                    $score += $wildcardscore;
                    continue;
                }
            }

            if (str_contains($normalizedquestion, $normalizedphrase)) {
                $score += 0.85;
            }

            $phrasescore = $this->string_similarity($normalizedphrase, $normalizedquestion);
            if ($phrasescore > 0.65) {
                $score += $phrasescore * 0.7;
            } else if ($phrasescore > 0.55) {
                $score += $phrasescore * 0.4;
            }

            $phraseTokens = $this->tokenize($phrase);
            $tokenoverlap = $this->token_overlap_score($phraseTokens, $questiontokens);
            if ($tokenoverlap > 0) {
                $score += $tokenoverlap * 0.6;
            }
        }

        $keywords = $this->get_keywords($entry);
        if (!empty($keywords)) {
            $matchedkeywords = 0.0;
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $questiontokens, true)) {
                    $matchedkeywords++;
                } else if ($keyword !== '' && str_contains($normalizedquestion, $keyword)) {
                    $matchedkeywords += 0.5;
                }
            }
            if ($matchedkeywords > 0) {
                $score += min(0.3, ($matchedkeywords / max(1, count($keywords))) * 0.8);
            }
        }

        if (!empty($entry->contexts)) {
            $contexts = $this->explode_lines($entry->contexts);
            foreach ($contexts as $context) {
                $normalizedcontext = $this->normalize($context);
                if ($normalizedcontext === '') {
                    continue;
                }
                if ($normalizedpage !== '' && str_contains($normalizedpage, $normalizedcontext)) {
                    $score += 0.15;
                    break;
                }
                if (str_contains($normalizedquestion, $normalizedcontext)) {
                    $score += 0.1;
                    break;
                }
            }
        }

        return $score;
    }

    /**
     * Builds suggestions for the response payload. Prioritises the best ranked entries and
     * fills remaining slots with general proactive suggestions.
     *
     * @param array $scores
     * @return array
     */
    protected function build_response_suggestions(array $scores): array {
        if (empty($scores)) {
            return $this->get_suggestions();
        }

        $suggestions = [];
        foreach (array_slice($scores, 0, 3) as $item) {
            $suggestions[] = [
                'id' => (int)$item['entry']->id,
                'text' => format_string($item['entry']->pattern),
            ];
        }

        if (count($suggestions) < 3) {
            foreach ($this->get_suggestions() as $suggestion) {
                $ids = array_column($suggestions, 'id');
                if (in_array($suggestion['id'], $ids, true)) {
                    continue;
                }
                $suggestions[] = $suggestion;
                if (count($suggestions) >= 3) {
                    break;
                }
            }
        }

        return $suggestions;
    }

    /**
     * Returns phrases including synonyms.
     *
     * @param stdClass $entry
     * @return array
     */
    protected function get_phrases(stdClass $entry): array {
        $phrases = [$entry->pattern];
        if (!empty($entry->synonyms)) {
            $phrases = array_merge($phrases, $this->explode_lines($entry->synonyms));
        }
        return array_filter(array_map('trim', $phrases));
    }

    /**
     * Returns keywords array.
     *
     * @param stdClass $entry
     * @return array
     */
    protected function get_keywords(stdClass $entry): array {
        if (empty($entry->keywords)) {
            return [];
        }
        $keywords = preg_split('/[,;]/', $entry->keywords, -1, PREG_SPLIT_NO_EMPTY);
        $keywords = array_map(fn($keyword) => $this->normalize($keyword), $keywords);
        return array_filter($keywords);
    }

    /**
     * Normalize text to lower case without punctuation.
     *
     * @param string $text
     * @return string
     */
    protected function normalize(string $text): string {
        $text = core_text::strtolower($text);
        $text = core_text::specialtoascii($text);
        $text = preg_replace('/[^a-z0-9\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim((string)$text);
    }

    /**
     * Splits text by newline.
     *
     * @param string $text
     * @return array
     */
    protected function explode_lines(string $text): array {
        return preg_split('/\r?\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Checks role restrictions.
     *
     * @param stdClass $entry
     * @return bool
     */
    protected function entry_matches_roles(stdClass $entry): bool {
        if (empty($entry->roles)) {
            return true;
        }
        if (!$this->userid) {
            return false;
        }
        $requiredroles = preg_split('/[,;\r\n]+/', $entry->roles, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($requiredroles)) {
            return true;
        }
        $systemcontext = context_system::instance();
        $userroles = get_user_roles($systemcontext, $this->userid, false);
        $usershortnames = array_map(static function($role) {
            return $role->shortname ?? '';
        }, $userroles);
        return (bool)array_intersect($requiredroles, $usershortnames);
    }

    /**
     * Tokenise a string applying stopword filtering and returning unique terms.
     *
     * @param string $text
     * @return array
     */
    protected function tokenize(string $text): array {
        $normalized = $this->normalize($text);
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
    protected function token_overlap_score(array $phasetokens, array $questiontokens): float {
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
     * Returns a similarity score between two normalised phrases.
     *
     * @param string $phrase
     * @param string $question
     * @return float
     */
    protected function string_similarity(string $phrase, string $question): float {
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

    /**
     * Attempts to match wildcard expressions from the knowledge base.
     *
     * @param string $phrase Original phrase as configured by the teacher.
     * @param string $originalquestion Raw user question.
     * @param string $normalizedquestion Normalised user question.
     * @return float|null Matching score or null when the phrase does not contain wildcards.
     */
    protected function match_wildcard(string $phrase, string $originalquestion, string $normalizedquestion): ?float {
        if (!str_contains($phrase, '*') && !str_contains($phrase, '?')) {
            return null;
        }

        $pattern = preg_quote($phrase, '/');
        $pattern = str_replace(['\\*', '\\?'], ['.*', '.'], $pattern);
        $pattern = '/^' . $pattern . '$/iu';

        if (preg_match($pattern, $originalquestion)) {
            return 0.9;
        }

        if (preg_match($pattern, $normalizedquestion)) {
            return 0.8;
        }

        return 0.0;
    }

    /**
     * Provides a preview of ranked entries for administrative tools.
     *
     * @param string $question
     * @param bool $ignoreroles When true, role restrictions are ignored.
     * @param int $limit
     * @return array
     */
    public function preview_rankings(string $question, bool $ignoreroles = false, int $limit = 10): array {
        if (trim($question) === '') {
            return [];
        }
        $scores = $this->rank_entries($question, $ignoreroles);
        if ($limit > 0) {
            $scores = array_slice($scores, 0, $limit);
        }
        return array_map(static function(array $item) {
            $item['score'] = round(min(1, $item['score']), 4);
            return $item;
        }, $scores);
    }
}
