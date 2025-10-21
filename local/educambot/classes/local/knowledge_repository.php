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
 * Repository exposing the structured knowledge base to the reasoning engine.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local;

use core_text;
use moodle_database;
use stdClass;

/**
 * Provides helpers to search knowledge entries.
 */
class knowledge_repository {
    /** @var moodle_database */
    protected moodle_database $db;

    /** @var array<int,stdClass>|null */
    protected ?array $entries = null;

    /** @var array<int,array>|null */
    protected ?array $topicsmap = null;

    /** @var array<int,array>|null */
    protected ?array $contextmap = null;

    /** @var array<int,array>|null */
    protected ?array $relationsmap = null;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * Searches the knowledge base using fuzzy heuristics.
     *
     * @param string $question
     * @param int|null $courseid
     * @param string|null $normalizedpage
     * @param array $roles Normalised role shortnames for the current user.
     * @param int $limit
     * @return array
     */
    public function search(
        string $question,
        ?int $courseid = null,
        ?string $normalizedpage = null,
        array $roles = [],
        int $limit = 5
    ): array {
        $question = trim($question);
        if ($question === '') {
            return [];
        }
        $normalizedquestion = text_helper::normalize($question);
        $questiontokens = text_helper::tokenize($question);
        $normalizedroles = array_filter(array_map(static function(string $role): string {
            return core_text::strtolower(trim($role));
        }, $roles));

        $scores = [];
        $contextmap = $this->get_context_map();
        foreach ($this->get_entries() as $entry) {
            $context = $contextmap[$entry->id] ?? ['courses' => [], 'courseids' => [], 'roles' => [], 'contexts' => []];
            if (!empty($context['roles'])) {
                if (empty($normalizedroles)) {
                    continue;
                }
                $entryroles = array_map(static function($role): string {
                    return core_text::strtolower(trim((string)$role));
                }, $context['roles']);
                if (empty(array_intersect($entryroles, $normalizedroles))) {
                    continue;
                }
            }

            $score = $this->score_entry($entry, $normalizedquestion, $questiontokens, $courseid, $normalizedpage);
            if ($score <= 0) {
                continue;
            }
            $scores[] = [
                'record' => $entry,
                'score' => min(1.2, $score),
                'topics' => $this->get_topics_map()[$entry->id] ?? [],
                'courses' => $context['courses'],
                'courseids' => $context['courseids'],
                'contexts' => $context['contexts'],
                'roles' => $context['roles'],
            ];
        }

        if (empty($scores)) {
            return [];
        }

        usort($scores, static function(array $a, array $b) {
            return $b['score'] <=> $a['score'];
        });

        if ($limit > 0) {
            $scores = array_slice($scores, 0, $limit);
        }

        return $scores;
    }

    /**
     * Returns cached knowledge entries.
     *
     * @return array<int,stdClass>
     */
    protected function get_entries(): array {
        if ($this->entries !== null) {
            return $this->entries;
        }
        $this->entries = $this->db->get_records('local_educambot_knowledge', ['enabled' => 1], 'timemodified DESC');
        return $this->entries;
    }

    /**
     * Returns a map of knowledge id to topics names.
     *
     * @return array<int,array>
     */
    protected function get_topics_map(): array {
        if ($this->topicsmap !== null) {
            return $this->topicsmap;
        }
        $this->topicsmap = [];
        $topics = $this->db->get_records('local_educambot_topic', null, '', 'id, name');
        if (empty($topics)) {
            return $this->topicsmap;
        }
        $links = $this->db->get_records('local_educambot_kn_topic', null, '', 'id, knowledgeid, topicid');
        foreach ($links as $link) {
            if (!isset($topics[$link->topicid])) {
                continue;
            }
            $this->topicsmap[$link->knowledgeid][] = format_string($topics[$link->topicid]->name);
        }
        return $this->topicsmap;
    }

    /**
     * Returns context mapping (courses, roles, page hints).
     *
     * @return array<int,array>
     */
    protected function get_context_map(): array {
        if ($this->contextmap !== null) {
            return $this->contextmap;
        }
        $this->contextmap = [];
        $records = $this->db->get_records('local_educambot_kn_context', null, '', 'id, knowledgeid, courseid, role, pagecontext');
        if (empty($records)) {
            return $this->contextmap;
        }
        $courseids = [];
        foreach ($records as $record) {
            $kid = (int)$record->knowledgeid;
            if (!isset($this->contextmap[$kid])) {
                $this->contextmap[$kid] = [
                    'courseids' => [],
                    'courses' => [],
                    'roles' => [],
                    'contexts' => [],
                ];
            }
            if (!empty($record->courseid)) {
                $cid = (int)$record->courseid;
                $this->contextmap[$kid]['courseids'][$cid] = $cid;
                $courseids[$cid] = $cid;
            }
            if (!empty($record->role)) {
                $this->contextmap[$kid]['roles'][$record->role] = $record->role;
            }
            if (!empty($record->pagecontext)) {
                $this->contextmap[$kid]['contexts'][] = $record->pagecontext;
            }
        }
        if (!empty($courseids)) {
            $courserecords = $this->db->get_records_list('course', 'id', array_values($courseids), '', 'id, fullname');
            foreach ($this->contextmap as $kid => $context) {
                $names = [];
                foreach ($context['courseids'] as $cid) {
                    if (isset($courserecords[$cid])) {
                        $names[] = format_string($courserecords[$cid]->fullname);
                    }
                }
                $this->contextmap[$kid]['courses'] = $names;
                $this->contextmap[$kid]['courseids'] = array_values($context['courseids']);
                $this->contextmap[$kid]['roles'] = array_values($context['roles']);
                $this->contextmap[$kid]['contexts'] = array_values(array_unique($context['contexts']));
            }
        } else {
            foreach ($this->contextmap as $kid => $context) {
                $this->contextmap[$kid]['courses'] = [];
                $this->contextmap[$kid]['courseids'] = [];
                $this->contextmap[$kid]['roles'] = array_values($context['roles']);
                $this->contextmap[$kid]['contexts'] = array_values(array_unique($context['contexts']));
            }
        }
        return $this->contextmap;
    }

    /**
     * Returns knowledge relations indexed by source id.
     *
     * @return array<int,array<int,array>>
     */
    protected function get_relations_map(): array {
        if ($this->relationsmap !== null) {
            return $this->relationsmap;
        }
        $this->relationsmap = [];
        $records = $this->db->get_records('local_educambot_relation', null, '', 'id, sourceid, targetid, relationtype');
        foreach ($records as $record) {
            $source = (int)$record->sourceid;
            $target = (int)$record->targetid;
            $this->relationsmap[$source][$target] = [
                'targetid' => $target,
                'relationtype' => $record->relationtype,
            ];
        }
        return $this->relationsmap;
    }

    /**
     * Expands knowledge matches with related entries.
     *
     * @param array $hits
     * @param int $depth
     * @param int $limit
     * @param array $roles Normalised role shortnames for the current user.
     * @return array
     */
    public function expand_with_relations(array $hits, int $depth = 1, int $limit = 6, array $roles = []): array {
        $expanded = [];
        $seen = [];
        $queue = [];
        foreach ($hits as $hit) {
            $hit['_depth'] = 0;
            $queue[] = $hit;
        }
        $topicsmap = $this->get_topics_map();
        $contextmap = $this->get_context_map();
        $normalizedroles = array_filter(array_map(static function(string $role): string {
            return core_text::strtolower(trim($role));
        }, $roles));

        while (!empty($queue)) {
            $current = array_shift($queue);
            $record = $current['record'];
            $id = (int)$record->id;
            $currentdepth = $current['_depth'] ?? 0;
            unset($current['_depth']);
            if (isset($seen[$id])) {
                continue;
            }
            $seen[$id] = true;
            $context = $contextmap[$id] ?? ['courses' => [], 'courseids' => [], 'roles' => [], 'contexts' => []];
            if (!empty($context['roles'])) {
                if (empty($normalizedroles)) {
                    continue;
                }
                $entryroles = array_map(static function($role): string {
                    return core_text::strtolower(trim((string)$role));
                }, $context['roles']);
                if (empty(array_intersect($entryroles, $normalizedroles))) {
                    continue;
                }
            }
            $current['roles'] = $context['roles'];
            $expanded[] = $current;

            if ($currentdepth >= $depth) {
                continue;
            }

            $relations = $this->get_relations_map()[$id] ?? [];
            foreach ($relations as $relation) {
                $targetid = $relation['targetid'];
                if (isset($seen[$targetid])) {
                    continue;
                }
                $entries = $this->get_entries();
                if (!isset($entries[$targetid])) {
                    continue;
                }
                $target = $entries[$targetid];
                $score = ($current['score'] ?? 0.6) * 0.75;
                $context = $contextmap[$targetid] ?? ['courses' => [], 'courseids' => [], 'roles' => [], 'contexts' => []];
                if (!empty($context['roles'])) {
                    if (empty($normalizedroles)) {
                        continue;
                    }
                    $entryroles = array_map(static function($role): string {
                        return core_text::strtolower(trim((string)$role));
                    }, $context['roles']);
                    if (empty(array_intersect($entryroles, $normalizedroles))) {
                        continue;
                    }
                }
                $queue[] = [
                    'record' => $target,
                    'score' => min(1.0, $score),
                    'topics' => $topicsmap[$targetid] ?? [],
                    'courses' => $context['courses'],
                    'courseids' => $context['courseids'],
                    'contexts' => $context['contexts'],
                    'roles' => $context['roles'],
                    'relationtype' => $relation['relationtype'],
                    'sourceid' => $id,
                    '_depth' => $currentdepth + 1,
                ];
            }
        }

        usort($expanded, static function(array $a, array $b) {
            return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
        });

        if ($limit > 0) {
            $expanded = array_slice($expanded, 0, $limit);
        }

        return $expanded;
    }

    /**
     * Calculates a score for a knowledge entry.
     *
     * @param stdClass $entry
     * @param string $normalizedquestion
     * @param array $questiontokens
     * @param int|null $courseid
     * @param string|null $normalizedpage
     * @return float
     */
    protected function score_entry(
        stdClass $entry,
        string $normalizedquestion,
        array $questiontokens,
        ?int $courseid,
        ?string $normalizedpage
    ): float {
        $score = 0.0;

        $titlenormalized = text_helper::normalize($entry->title ?? '');
        if ($titlenormalized !== '') {
            if (str_contains($normalizedquestion, $titlenormalized)) {
                $score += 0.7;
            }
            $score += text_helper::string_similarity($titlenormalized, $normalizedquestion) * 0.8;
        }

        $summarytext = text_helper::normalize(strip_tags((string)$entry->summary));
        if ($summarytext !== '') {
            if (str_contains($normalizedquestion, $summarytext)) {
                $score += 0.4;
            }
            $score += text_helper::string_similarity($summarytext, $normalizedquestion) * 0.5;
        }

        $contenttext = text_helper::normalize(strip_tags((string)$entry->content));
        if ($contenttext !== '') {
            $score += text_helper::string_similarity($contenttext, $normalizedquestion) * 0.3;
        }

        $entrytokens = text_helper::tokenize((string)$entry->title . ' ' . (string)$entry->summary . ' ' . (string)$entry->tags);
        if (!empty($entrytokens)) {
            $overlap = text_helper::token_overlap_score($entrytokens, $questiontokens);
            if ($overlap > 0) {
                $score += $overlap * 0.9;
            }
        }

        $topics = $this->get_topics_map()[$entry->id] ?? [];
        if (!empty($topics)) {
            $topictokens = [];
            foreach ($topics as $topicname) {
                $topictokens = array_merge($topictokens, text_helper::tokenize($topicname));
            }
            if (!empty($topictokens)) {
                $score += text_helper::token_overlap_score($topictokens, $questiontokens) * 0.5;
            }
        }

        $context = $this->get_context_map()[$entry->id] ?? null;
        if ($context) {
            if ($courseid && !empty($context['courseids']) && in_array($courseid, $context['courseids'], true)) {
                $score += 0.3;
            } else if ($courseid && !empty($context['courseids'])) {
                // Penalise mismatched course specific knowledge.
                $score -= 0.2;
            }
            if ($normalizedpage && !empty($context['contexts'])) {
                foreach ($context['contexts'] as $pagecontext) {
                    $normalizedcontext = text_helper::normalize($pagecontext);
                    if ($normalizedcontext !== '' && str_contains($normalizedpage, $normalizedcontext)) {
                        $score += 0.2;
                        break;
                    }
                }
            }
        }

        if (!empty($entry->type) && str_contains($normalizedquestion, text_helper::normalize($entry->type))) {
            $score += 0.1;
        }

        return $score;
    }
}
