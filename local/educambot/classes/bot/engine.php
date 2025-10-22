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
use local_educambot\bot\composite_reasoner;
use local_educambot\bot\reasoner_interface;
use local_educambot\context\session_memory;
use local_educambot\inference\engine as inference_engine;
use local_educambot\local\context_provider;
use local_educambot\local\knowledge_repository;
use local_educambot\local\text_helper;
use local_educambot\matching\manager as matching_manager;
use local_educambot\nlp\pipeline;
use moodle_url;
use stdClass;

/**
 * Implements a flexible rule engine with fuzzy matching heuristics.
 */
class engine {
    /** Number of proactive suggestions shown to the user. */
    protected const SUGGESTION_LIMIT = 6;
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

    /** @var reasoner_interface */
    protected reasoner_interface $reasoner;

    /** @var inference_engine */
    protected inference_engine $inference;

    /** @var pipeline */
    protected pipeline $pipeline;

    /** @var matching_manager */
    protected matching_manager $matcher;

    /** @var session_memory */
    protected session_memory $sessionmemory;

    /** @var string|null */
    protected ?string $sessionid;

    /** @var int|null Resolved course id from current page */
    protected ?int $courseid = null;

    /**
     * Constructor.
     *
     * @param int|null $userid
     * @param string|null $pageidentifier
     */
    public function __construct(?int $userid, ?string $pageidentifier, ?string $sessionid = null) {
        global $DB;

        $this->db = $DB;
        $this->userid = $userid;
        $this->pageidentifier = $pageidentifier;
        $this->normalizedpage = $pageidentifier ? text_helper::normalize($pageidentifier) : null;
        $this->courseid = $this->resolve_courseid($pageidentifier);
        $this->contextprovider = new context_provider($userid, $this->courseid, $pageidentifier);
        $this->knowledge = new knowledge_repository();
        $this->config = (array)get_config('local_educambot');
        $this->sessionid = $sessionid;
        $historylimit = (int)($this->config['historylimit'] ?? 8);
        if ($historylimit <= 0) {
            $historylimit = 8;
        }
        $historylimit = max(3, min(50, $historylimit));
        $this->pipeline = new pipeline();
        $this->matcher = new matching_manager($this->pipeline, $this->config);
        $this->sessionmemory = new session_memory($this->sessionid, $historylimit);
        $this->reasoner = new composite_reasoner($this->contextprovider, $this->knowledge, $this->courseid, $this->normalizedpage);
        $this->inference = new inference_engine($this->reasoner, $this->sessionmemory);
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

        $analysis = $this->pipeline->process($question);
        if ($this->sessionmemory->looks_like_followup($analysis['tokens'])) {
            $historykeywords = $this->sessionmemory->aggregated_keywords();
            if (!empty($historykeywords)) {
                $analysis['keywords'] = array_values(array_unique(array_merge($analysis['keywords'], $historykeywords)));
            }
        }

        $scores = $this->rank_entries($question, $analysis);
        $roles = $this->contextprovider->get_effective_roles();
        $knowledgehits = $this->knowledge->search($question, $this->courseid, $this->normalizedpage, $roles);

        $decision = $this->inference->decide($question, $analysis, $scores, $knowledgehits);
        if ($decision) {
            if ($decision['type'] === 'rule') {
                $winner = $decision['rule'];
                $response = format_text($winner['entry']->response, FORMAT_HTML, [
                    'filter' => true,
                    'context' => context_system::instance(),
                ]);
                $response = $this->contextprovider->personalise_html($response, $this->config);
                $confidence = round(min(1, $winner['score']), 4);
                $this->sessionmemory->remember($question, $response, $analysis, [
                    'ruleid' => (int)$winner['entry']->id,
                    'knowledgeid' => null,
                    'confidence' => $confidence,
                ]);
                return [
                    'response' => $response,
                    'ruleid' => (int)$winner['entry']->id,
                    'confidence' => $confidence,
                    'suggestions' => $this->build_response_suggestions($scores, $question),
                ];
            }

            if ($decision['type'] === 'knowledge') {
                $knowledgebundle = $decision['knowledge'];
                $response = $this->build_knowledge_response($knowledgebundle);
                $topscore = $knowledgebundle[0]['score'] ?? 0.5;
                $confidence = round(min(1, max(0.35, $topscore)));
                $knowledgeid = (int)($knowledgebundle[0]['record']->id ?? 0);
                $this->sessionmemory->remember($question, $response, $analysis, [
                    'ruleid' => null,
                    'knowledgeid' => $knowledgeid ?: null,
                    'confidence' => $confidence,
                ]);
                return [
                    'response' => $response,
                    'ruleid' => null,
                    'confidence' => $confidence,
                    'suggestions' => $this->build_knowledge_suggestions($knowledgebundle),
                ];
            }
        }

        $suggestions = $this->get_suggestions();
        $fallback = get_string('noanswer', 'local_educambot');
        $this->sessionmemory->remember($question, $fallback, $analysis, [
            'ruleid' => null,
            'knowledgeid' => null,
            'confidence' => 0.0,
        ]);

        return ['response' => null, 'ruleid' => null, 'confidence' => 0.0, 'suggestions' => $suggestions];
    }

    /**
     * Retrieve proactive suggestions.
     *
     * @return array[]
     */
    public function get_suggestions(int $limit = self::SUGGESTION_LIMIT): array {
        $limit = max(1, $limit);
        $fetchlimit = min(120, max($limit * 6, 24));
        $conditions = 'enabled = :enabled AND suggested = :suggested';
        $params = ['enabled' => 1, 'suggested' => 1];
        $fields = 'id, pattern, contexts, roles';
        $records = $this->db->get_records_select('local_educambot_rule', $conditions, $params, 'timemodified DESC', $fields, 0, $fetchlimit);

        $suggestions = [];
        $normalizedpage = $this->normalizedpage ?? '';
        $addsuggestion = static function(array &$suggestions, string $id, string $text, int $limit): bool {
            foreach ($suggestions as $existing) {
                if ($existing['id'] === $id) {
                    return count($suggestions) < $limit;
                }
            }
            $suggestions[] = [
                'id' => $id,
                'text' => $text,
            ];
            return count($suggestions) < $limit;
        };

        if (!empty($records) && $normalizedpage !== '') {
            foreach ($records as $record) {
                if (empty($record->contexts) || !$this->entry_matches_roles($record)) {
                    continue;
                }
                $contexts = $this->explode_lines($record->contexts);
                foreach ($contexts as $context) {
                    $normalizedcontext = text_helper::normalize($context);
                    if ($normalizedcontext === '' || !str_contains($normalizedpage, $normalizedcontext)) {
                        continue;
                    }
                    $continue = $addsuggestion($suggestions, 'rule-' . (int)$record->id, format_string($record->pattern), $limit);
                    if (!$continue) {
                        return $suggestions;
                    }
                    break;
                }
            }
        }

        if (count($suggestions) < $limit && !empty($records)) {
            foreach ($records as $record) {
                if (!$this->entry_matches_roles($record)) {
                    continue;
                }
                if (!$addsuggestion($suggestions, 'rule-' . (int)$record->id, format_string($record->pattern), $limit)) {
                    return $suggestions;
                }
            }
        }

        if (count($suggestions) < $limit) {
            $needed = $limit - count($suggestions);
            foreach ($this->knowledge->get_top_suggestions($needed) as $knowledge) {
                if (!$addsuggestion($suggestions, 'knowledge-' . (int)$knowledge->id, format_string($knowledge->title), $limit)) {
                    return $suggestions;
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
            return $entries;
        }

        if (is_array($entries) && empty($entries)) {
            if ($this->db->record_exists('local_educambot_rule', ['enabled' => 1])) {
                $entries = $this->db->get_records('local_educambot_rule', ['enabled' => 1], 'timemodified DESC');
                $cache->set('all', $entries);
            }
        }

        return $entries ?: [];
    }

    /**
     * Rank all entries for the provided question.
     *
     * @param string $question
     * @param bool $ignoreroles When true, role checks are skipped.
     * @return array
     */
    protected function rank_entries(string $question, array $analysis, bool $ignoreroles = false): array {
        $entries = $this->get_entries();
        $normalizedpage = $this->normalizedpage ?? '';
        $scores = [];

        foreach ($entries as $entry) {
            if (!$ignoreroles && !$this->entry_matches_roles($entry)) {
                continue;
            }

            $scoredata = $this->matcher->score_entry($entry, $analysis, $question, $normalizedpage);
            $score = $scoredata['score'];
            if ($score <= 0) {
                continue;
            }

            $scores[] = [
                'entry' => $entry,
                'score' => min(1.5, $score),
                'breakdown' => $scoredata['breakdown'],
            ];
        }

        usort($scores, static function(array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scores;
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
        $usedids = [];
        $addsuggestion = static function($id, string $text) use (&$suggestions, &$usedids): void {
            $key = (string)$id;
            if (isset($usedids[$key])) {
                return;
            }
            $suggestions[] = [
                'id' => $id,
                'text' => $text,
            ];
            $usedids[$key] = true;
        };

        foreach (array_slice($scores, 0, 3) as $item) {
            $addsuggestion('rule-' . (int)$item['entry']->id, format_string($item['entry']->pattern));
        }

        if (count($suggestions) < 3) {
            foreach ($this->get_suggestions() as $suggestion) {
                $addsuggestion($suggestion['id'], $suggestion['text']);
                if (count($suggestions) >= 3) {
                    break;
                }
            }
        }

        if (count($suggestions) < 3) {
            $roles = $this->contextprovider->get_effective_roles();
            $knowledge = $this->knowledge->search($question, $this->courseid, $this->normalizedpage, $roles);
            foreach ($knowledge as $item) {
                if (count($suggestions) >= 3) {
                    break;
                }
                $addsuggestion('knowledge-' . (int)$item['record']->id, format_string($item['record']->title));
            }
        }

        return $suggestions;
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
        $requiredroles = array_filter(array_map(static function(string $role): string {
            return core_text::strtolower(trim($role));
        }, $requiredroles));
        if (empty($requiredroles)) {
            return true;
        }

        $userroles = $this->contextprovider->get_effective_roles();
        if (empty($userroles)) {
            return false;
        }

        return (bool)array_intersect($requiredroles, $userroles);
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
        $analysis = $this->pipeline->process($question);
        $scores = $this->rank_entries($question, $analysis, $ignoreroles);
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
            $summary = format_text($record->summary, FORMAT_HTML, [
                'filter' => true,
                'context' => context_system::instance(),
            ]);
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
            if (!empty($item['relationtype'])) {
                $relationlabel = $this->format_relation_label($item['relationtype']);
                if ($relationlabel !== '') {
                    $relationtext = get_string('knowledgefallbackrelation', 'local_educambot', $relationlabel);
                    $content .= \html_writer::tag('div', $relationtext, ['class' => 'local-educambot__knowledge-relation']);
                }
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

    /**
     * Formats relation labels for knowledge entries.
     *
     * @param string $relation
     * @return string
     */
    protected function format_relation_label(string $relation): string {
        $relation = trim($relation);
        if ($relation === '') {
            return '';
        }
        $relation = str_replace(['_', '-'], ' ', $relation);
        $relation = ucwords($relation);
        return format_string($relation);
    }
}
