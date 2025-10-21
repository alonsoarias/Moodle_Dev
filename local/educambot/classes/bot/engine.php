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
 * Implements a simple rule engine with fuzzy matching heuristics.
 */
class engine {
    /** @var \moodle_database */
    protected $db;

    /** @var int|null */
    protected $userid;

    /** @var string|null */
    protected $pageidentifier;

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

        $entries = $this->get_entries();
        $normalizedquestion = $this->normalize($question);
        $scores = [];

        foreach ($entries as $entry) {
            if (!$this->entry_matches_roles($entry)) {
                continue;
            }

            $score = $this->calculate_score($entry, $normalizedquestion, $question);
            if ($score <= 0) {
                continue;
            }

            $scores[] = [
                'entry' => $entry,
                'score' => $score,
            ];
        }

        if (empty($scores)) {
            return ['response' => null, 'ruleid' => null, 'confidence' => 0.0, 'suggestions' => $this->get_suggestions()];
        }

        usort($scores, static function(array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        $winner = $scores[0];
        $response = format_text($winner['entry']->response, FORMAT_HTML, ['filter' => true]);

        return [
            'response' => $response,
            'ruleid' => (int)$winner['entry']->id,
            'confidence' => round(min(1, $winner['score']), 4),
            'suggestions' => $this->get_suggestions(),
        ];
    }

    /**
     * Retrieve proactive suggestions.
     *
     * @return array[]
     */
    public function get_suggestions(): array {
        $records = $this->db->get_records('local_educambot_rule', ['enabled' => 1, 'suggested' => 1], 'timemodified DESC', 'id, pattern');
        $suggestions = [];
        foreach ($records as $record) {
            $suggestions[] = [
                'id' => (int)$record->id,
                'text' => $record->pattern,
            ];
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
     * Calculates the matching score for an entry.
     *
     * @param stdClass $entry
     * @param string $normalizedquestion
     * @param string $originalquestion
     * @return float
     */
    protected function calculate_score(stdClass $entry, string $normalizedquestion, string $originalquestion): float {
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
            if (str_contains($normalizedquestion, $normalizedphrase)) {
                $score += 0.75;
            } else {
                $distance = levenshtein($normalizedphrase, $normalizedquestion);
                $longest = max(strlen($normalizedphrase), strlen($normalizedquestion));
                if ($longest > 0) {
                    $similarity = 1 - ($distance / $longest);
                    if ($similarity > 0.6) {
                        $score += $similarity * 0.6;
                    }
                }
            }
        }

        $keywords = $this->get_keywords($entry);
        if (!empty($keywords)) {
            $matchedkeywords = 0;
            $questionwords = preg_split('/\s+/', $normalizedquestion, -1, PREG_SPLIT_NO_EMPTY);
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $questionwords, true)) {
                    $matchedkeywords++;
                }
            }
            if ($matchedkeywords > 0) {
                $score += min(0.25, $matchedkeywords / max(1, count($keywords)));
            }
        }

        if (!empty($entry->contexts) && $this->pageidentifier) {
            $contexts = $this->explode_lines($entry->contexts);
            foreach ($contexts as $context) {
                $normalizedcontext = $this->normalize($context);
                if ($normalizedcontext !== '' && str_contains($this->normalize($this->pageidentifier), $normalizedcontext)) {
                    $score += 0.15;
                    break;
                }
            }
        }

        return $score;
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
        $text = preg_replace('/[^a-z0-9áéíóúüñ\s]/u', ' ', $text);
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
}
