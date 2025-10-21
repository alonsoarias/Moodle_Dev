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
use local_educambot\local\context_provider;
use local_educambot\local\knowledge_repository;
use local_educambot\local\text_helper;
use moodle_url;
use stdClass;

/**
 * Implements a flexible rule engine with fuzzy matching heuristics.
 */
class engine {
    /** @var \moodle_database */
    protected $db;

    /** @var int|null */
    protected $userid;

    /** @var string|null */
    protected $pageidentifier;

    /** @var string|null Normalised page identifier */
    protected ?string $normalizedpage = null;

    /** @var context_provider */
    protected context_provider $contextprovider;

    /** @var knowledge_repository */
    protected knowledge_repository $knowledge;

    /** @var array Plugin configuration */
    protected array $config;

    /** @var int|null Resolved course id from current page */
    protected ?int $courseid = null;

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
        $this->normalizedpage = $pageidentifier ? text_helper::normalize($pageidentifier) : null;
        $this->courseid = $this->resolve_courseid($pageidentifier);
        $this->contextprovider = new context_provider($userid, $this->courseid, $pageidentifier);
        $this->knowledge = new knowledge_repository();
        $this->config = (array)get_config('local_educambot');
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
        if (!empty($scores)) {
            $winner = $scores[0];
            $response = format_text($winner['entry']->response, FORMAT_HTML, ['filter' => true]);
            $response = $this->contextprovider->personalise_html($response, $this->config);

            return [
                'response' => $response,
                'ruleid' => (int)$winner['entry']->id,
                'confidence' => round(min(1, $winner['score']), 4),
                'suggestions' => $this->build_response_suggestions($scores, $question),
            ];
        }

        $knowledge = $this->knowledge->search($question, $this->courseid, $this->normalizedpage);
        if (!empty($knowledge)) {
            $response = $this->build_knowledge_response($knowledge);
            $topscore = $knowledge[0]['score'] ?? 0.5;
            return [
                'response' => $response,
                'ruleid' => null,
                'confidence' => round(min(1, max(0.35, $topscore))),
                'suggestions' => $this->build_knowledge_suggestions($knowledge),
            ];
        }

        return ['response' => null, 'ruleid' => null, 'confidence' => 0.0, 'suggestions' => $this->get_suggestions()];
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
                    $normalizedcontext = text_helper::normalize($context);
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
        $normalizedquestion = text_helper::normalize($question);
        $questiontokens = text_helper::tokenize($question);
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
            $normalizedphrase = text_helper::normalize($phrase);
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

            $phrasescore = text_helper::string_similarity($normalizedphrase, $normalizedquestion);
            if ($phrasescore > 0.65) {
                $score += $phrasescore * 0.7;
            } else if ($phrasescore > 0.55) {
                $score += $phrasescore * 0.4;
            }

            $phraseTokens = text_helper::tokenize($phrase);
            $tokenoverlap = text_helper::token_overlap_score($phraseTokens, $questiontokens);
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
                $normalizedcontext = text_helper::normalize($context);
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

        if ($this->courseid && !empty($entry->contexts)) {
            $course = $this->contextprovider->get_focus_course();
            if ($course && str_contains(text_helper::normalize($course->fullname), $normalizedquestion)) {
                $score += 0.1;
            }
        }

        return $score;
    }

    /**
     * Builds suggestions for the response payload. Prioritises the best ranked entries and
     * fills remaining slots with general proactive suggestions.
     *
     * @param array $scores
     * @param string $question
     * @return array
     */
    protected function build_response_suggestions(array $scores, string $question): array {
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

        if (count($suggestions) < 3) {
            $knowledge = $this->knowledge->search($question, $this->courseid, $this->normalizedpage);
            foreach ($knowledge as $item) {
                if (count($suggestions) >= 3) {
                    break;
                }
                $suggestions[] = [
                    'id' => (int)$item['record']->id,
                    'text' => format_string($item['record']->title),
                ];
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
        $keywords = array_map(fn($keyword) => text_helper::normalize($keyword), $keywords);
        return array_filter($keywords);
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

    /**
     * Returns the resolved course id if any.
     *
     * @return int|null
     */
    public function get_courseid(): ?int {
        return $this->courseid;
    }

    /**
     * Extracts a course id from the current page identifier when possible.
     *
     * @param string|null $pageidentifier
     * @return int|null
     */
    protected function resolve_courseid(?string $pageidentifier): ?int {
        global $CFG;

        if (!$pageidentifier) {
            return null;
        }

        $url = $pageidentifier;
        if (!str_starts_with($url, 'http')) {
            $url = (new moodle_url($url))->out(false);
        }
        $urlparts = parse_url($url);
        $path = $urlparts['path'] ?? '';
        $params = [];
        if (!empty($urlparts['query'])) {
            parse_str($urlparts['query'], $params);
        }

        if (str_contains($path, '/course/view.php') && !empty($params['id'])) {
            return (int)$params['id'];
        }

        if (!empty($params['courseid'])) {
            return (int)$params['courseid'];
        }

        if (str_contains($path, '/mod/') && !empty($params['id'])) {
            require_once($CFG->dirroot . '/course/lib.php');
            $cmid = (int)$params['id'];
            try {
                $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
                return $cm->course ?? null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * Builds a HTML response when knowledge entries are used instead of direct rules.
     *
     * @param array $knowledge
     * @return string
     */
    protected function build_knowledge_response(array $knowledge): string {
        $botname = $this->contextprovider->get_bot_name($this->config);
        $intro = get_string('knowledgefallbackintro', 'local_educambot', $botname);
        $html = \html_writer::tag('p', $intro, ['class' => 'local-educambot__knowledge-intro']);
        $items = [];
        foreach (array_slice($knowledge, 0, 3) as $item) {
            $record = $item['record'];
            $title = format_string($record->title);
            $summary = format_text($record->summary, FORMAT_HTML, ['filter' => true]);
            $link = '';
            if (!empty($record->externalurl)) {
                $link = \html_writer::link($record->externalurl, get_string('knowledgefallbackopen', 'local_educambot'));
            }
            $meta = [];
            if (!empty($item['topics'])) {
                $meta[] = implode(', ', array_map('format_string', $item['topics']));
            }
            if (!empty($item['courses'])) {
                $meta[] = implode(', ', array_map('format_string', $item['courses']));
            }
            $metatext = '';
            if (!empty($meta)) {
                $metatext = \html_writer::tag('div', implode(' • ', $meta), ['class' => 'local-educambot__knowledge-meta']);
            }
            $content = \html_writer::tag('h4', $title, ['class' => 'local-educambot__knowledge-title']);
            $content .= \html_writer::tag('div', $summary, ['class' => 'local-educambot__knowledge-summary']);
            if ($link) {
                $content .= \html_writer::tag('div', $link, ['class' => 'local-educambot__knowledge-link']);
            }
            $content .= $metatext;
            $items[] = \html_writer::tag('li', $content, ['class' => 'local-educambot__knowledge-item']);
        }
        $html .= \html_writer::tag('ul', implode('', $items), ['class' => 'local-educambot__knowledge-list']);

        return $this->contextprovider->personalise_html($html, $this->config);
    }

    /**
     * Converts knowledge hits into suggestion entries.
     *
     * @param array $knowledge
     * @return array
     */
    protected function build_knowledge_suggestions(array $knowledge): array {
        $suggestions = [];
        foreach (array_slice($knowledge, 0, 3) as $item) {
            $suggestions[] = [
                'id' => (int)$item['record']->id,
                'text' => format_string($item['record']->title),
            ];
        }
        return $suggestions;
    }
}
