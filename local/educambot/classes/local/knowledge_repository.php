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

    /** @var array<int,stdClass> */
    protected array $entrybuffer = [];

    /** @var array<int,array>|null */
    protected ?array $topicsmap = null;

    /** @var array<int,array>|null */
    protected ?array $contextmap = null;

    /** @var array<int,array>|null */
    protected ?array $relationsmap = null;

    /** @var \cache */
    protected $entriescache;

    /** @var \cache */
    protected $topicscache;

    /** @var \cache */
    protected $contextcache;

    /** @var \cache */
    protected $relationscache;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->entriescache = \cache::make('local_educambot', 'knowledge');
        $this->topicscache = \cache::make('local_educambot', 'knowledge_topics');
        $this->contextcache = \cache::make('local_educambot', 'knowledge_context');
        $this->relationscache = \cache::make('local_educambot', 'knowledge_relations');
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
        $lowerquestion = core_text::strtolower(trim($question));
        if ($lowerquestion !== '') {
            $lowerquestion = preg_replace('/\s+/u', ' ', $lowerquestion);
        }
        $sqltokens = $this->build_search_tokens($lowerquestion, $questiontokens);
        $normalizedroles = array_filter(array_map(static function(string $role): string {
            return core_text::strtolower(trim($role));
        }, $roles));

        $scores = [];

        $candidateids = $this->select_candidate_ids($lowerquestion, $normalizedquestion, $sqltokens, $courseid, $normalizedpage, max(30, $limit * 6));
        if (empty($candidateids)) {
            return [];
        }

        $entries = $this->load_entries($candidateids);
        $contextmap = $this->get_context_map();
        foreach ($candidateids as $candidateid) {
            if (!isset($entries[$candidateid])) {
                continue;
            }
            $entry = $entries[$candidateid];
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
     * Provides generic knowledge suggestions when no rule matches are available.
     *
     * @param int $limit
     * @return array<int,stdClass>
     */
    public function get_top_suggestions(int $limit = 3): array {
        $limit = max(1, $limit);
        $sql = 'SELECT id FROM {local_educambot_knowledge} WHERE enabled = 1 ORDER BY timemodified DESC';
        $records = $this->db->get_records_sql($sql, [], 0, $limit);
        if (empty($records)) {
            return [];
        }
        $ids = array_map('intval', array_keys($records));
        $entries = $this->load_entries($ids);
        $ordered = [];
        foreach ($records as $record) {
            $knowledgeid = (int)$record->id;
            if (isset($entries[$knowledgeid])) {
                $ordered[] = $entries[$knowledgeid];
            }
        }

        return $ordered;
    }

    /**
     * Returns cached knowledge entries.
     *
     * @return array<int,stdClass>
     */
    protected function get_entries(): array {
        return $this->load_entries();
    }

    /**
     * Loads knowledge entries optionally restricted to a set of ids.
     *
     * @param array<int,int>|null $ids
     * @return array<int,stdClass>
     */
    protected function load_entries(?array $ids = null): array {
        if ($ids === null) {
            if ($this->entries !== null) {
                return $this->entries;
            }
            $cached = $this->entriescache->get('all');
            if ($cached !== false) {
                $this->entries = $cached;
                return $this->entries;
            }
            $this->entries = $this->db->get_records('local_educambot_knowledge', ['enabled' => 1], 'timemodified DESC');
            $this->entriescache->set('all', $this->entries);
            return $this->entries;
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        $results = [];
        $missing = [];

        if ($this->entries !== null) {
            foreach ($ids as $id) {
                if (isset($this->entries[$id])) {
                    $results[$id] = $this->entries[$id];
                } else if (isset($this->entrybuffer[$id])) {
                    $results[$id] = $this->entrybuffer[$id];
                } else {
                    $missing[] = $id;
                }
            }
        } else {
            foreach ($ids as $id) {
                if (isset($this->entrybuffer[$id])) {
                    $results[$id] = $this->entrybuffer[$id];
                } else {
                    $missing[] = $id;
                }
            }
        }

        if (!empty($missing)) {
            $cachekey = 'subset_' . sha1(implode('_', $missing));
            $cached = $this->entriescache->get($cachekey);
            if ($cached !== false) {
                foreach ($cached as $cachedid => $record) {
                    $this->entrybuffer[$cachedid] = $record;
                    $results[$cachedid] = $record;
                }
            } else {
                $fetched = $this->db->get_records_list('local_educambot_knowledge', 'id', $missing);
                $this->entriescache->set($cachekey, $fetched);
                foreach ($fetched as $fetchedid => $record) {
                    $this->entrybuffer[$fetchedid] = $record;
                    $results[$fetchedid] = $record;
                }
            }
        }

        return $results;
    }

    /**
     * Selects candidate knowledge ids using basic text filtering.
     *
     * @param string $normalizedquestion
     * @param array<int,string> $questiontokens
     * @param int|null $courseid
     * @param string|null $normalizedpage
     * @param int $limit
     * @return array<int,int>
     */
    protected function select_candidate_ids(string $lowerquestion, string $normalizedquestion, array $tokens, ?int $courseid, ?string $normalizedpage, int $limit): array {
        $joins = '';
        $conditions = ['k.enabled = 1'];
        $params = [];

        $needscontextjoin = $courseid || $normalizedpage;
        if ($needscontextjoin) {
            $joins .= ' LEFT JOIN {local_educambot_kn_context} kc ON kc.knowledgeid = k.id';
        }

        if ($courseid) {
            $conditions[] = '(kc.courseid IS NULL OR kc.courseid = :courseid)';
            $params['courseid'] = $courseid;
        }

        if ($normalizedpage) {
            $conditions[] = '(kc.pagecontext IS NULL OR kc.pagecontext = "" OR ' .
                $this->db->sql_like('LOWER(kc.pagecontext)', ':pagecontext', false) . ')';
            $params['pagecontext'] = '%' . core_text::strtolower($normalizedpage) . '%';
        }

        $searchconditions = [];
        if ($lowerquestion !== '') {
            $searchparam = '%' . $lowerquestion . '%';
            $searchconditions[] = $this->db->sql_like('LOWER(k.title)', ':searchtitle', false);
            $searchconditions[] = $this->db->sql_like('LOWER(k.summary)', ':searchsummary', false);
            $searchconditions[] = $this->db->sql_like('LOWER(k.tags)', ':searchtags', false);
            $params['searchtitle'] = $searchparam;
            $params['searchsummary'] = $searchparam;
            $params['searchtags'] = $searchparam;
        }
        if ($normalizedquestion !== '' && $normalizedquestion !== $lowerquestion) {
            $searchparamnormalized = '%' . $normalizedquestion . '%';
            $searchconditions[] = $this->db->sql_like('LOWER(k.title)', ':searchtitle_normalized', false);
            $searchconditions[] = $this->db->sql_like('LOWER(k.summary)', ':searchsummary_normalized', false);
            $searchconditions[] = $this->db->sql_like('LOWER(k.tags)', ':searchtags_normalized', false);
            $params['searchtitle_normalized'] = $searchparamnormalized;
            $params['searchsummary_normalized'] = $searchparamnormalized;
            $params['searchtags_normalized'] = $searchparamnormalized;
        }

        $tokens = array_slice($tokens, 0, 6);
        foreach ($tokens as $index => $token) {
            $paramname = 'token' . $index;
            $searchconditions[] = $this->db->sql_like('LOWER(k.content)', ':' . $paramname, false);
            $params[$paramname] = '%' . core_text::strtolower($token) . '%';
        }

        if (!empty($searchconditions)) {
            $conditions[] = '(' . implode(' OR ', $searchconditions) . ')';
        }

        $limit = max(10, $limit);
        $sql = 'SELECT DISTINCT k.id FROM {local_educambot_knowledge} k' . $joins . ' WHERE ' . implode(' AND ', $conditions) .
            ' ORDER BY k.timemodified DESC';
        $records = $this->db->get_records_sql($sql, $params, 0, $limit);

        return array_map('intval', array_keys($records));
    }

    /**
     * Creates the pool of tokens used to build SQL LIKE filters preserving accents.
     *
     * @param string $lowerquestion
     * @param array<int,string> $normalizedtokens
     * @return array<int,string>
     */
    protected function build_search_tokens(string $lowerquestion, array $normalizedtokens): array {
        $rawtokens = $this->extract_raw_tokens($lowerquestion);
        $normalizedtokens = array_map(static function(string $token): string {
            return core_text::strtolower(trim($token));
        }, $normalizedtokens);
        $pool = array_merge($rawtokens, $normalizedtokens);
        $pool = array_filter($pool, static function($token): bool {
            return is_string($token) && core_text::strlen(trim($token)) > 1;
        });
        $pool = array_map(static function(string $token): string {
            return core_text::strtolower(trim($token));
        }, $pool);

        return array_values(array_unique($pool));
    }

    /**
     * Splits the original question preserving accents for SQL matching.
     *
     * @param string $question
     * @return array<int,string>
     */
    protected function extract_raw_tokens(string $question): array {
        $question = trim($question);
        if ($question === '') {
            return [];
        }

        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $question, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) {
            return [];
        }

        return array_values(array_map(static function(string $token): string {
            return core_text::strtolower(trim($token));
        }, $tokens));
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
        $cached = $this->topicscache->get('all');
        if ($cached !== false) {
            $this->topicsmap = $cached;
            return $this->topicsmap;
        }
        $this->topicsmap = [];
        $topics = $this->db->get_records('local_educambot_topic', null, '', 'id, name');
        if (empty($topics)) {
            $this->topicscache->set('all', $this->topicsmap);
            return $this->topicsmap;
        }
        $links = $this->db->get_records('local_educambot_kn_topic', null, '', 'id, knowledgeid, topicid');
        foreach ($links as $link) {
            if (!isset($topics[$link->topicid])) {
                continue;
            }
            $this->topicsmap[$link->knowledgeid][] = format_string($topics[$link->topicid]->name);
        }
        $this->topicscache->set('all', $this->topicsmap);
        return $this->topicsmap;
    }

    /**
     * Returns topic names for a collection of knowledge ids.
     *
     * @param array<int,int> $ids
     * @return array<int,array>
     */
    public function get_topics_for_ids(array $ids): array {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }

        list($insql, $params) = $this->db->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $sql = 'SELECT kt.knowledgeid, t.name
                  FROM {local_educambot_kn_topic} kt
                  JOIN {local_educambot_topic} t ON t.id = kt.topicid
                 WHERE kt.knowledgeid ' . $insql . '
              ORDER BY t.name ASC';
        $records = $this->db->get_records_sql($sql, $params);
        $grouped = [];
        foreach ($records as $record) {
            $kid = (int)$record->knowledgeid;
            $grouped[$kid][$record->name] = $record->name;
        }
        foreach ($grouped as $kid => $names) {
            $grouped[$kid] = array_values($names);
        }
        return $grouped;
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
        $cached = $this->contextcache->get('all');
        if ($cached !== false) {
            $this->contextmap = $cached;
            return $this->contextmap;
        }
        $this->contextmap = [];
        $records = $this->db->get_records('local_educambot_kn_context', null, '', 'id, knowledgeid, courseid, role, pagecontext');
        if (empty($records)) {
            $this->contextcache->set('all', $this->contextmap);
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
        $this->contextcache->set('all', $this->contextmap);
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
        $cached = $this->relationscache->get('all');
        if ($cached !== false) {
            $this->relationsmap = $cached;
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
        $this->relationscache->set('all', $this->relationsmap);
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
                $entries = $this->load_entries([$targetid]);
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

    /**
     * Clears all cache definitions used by the repository.
     */
    public static function reset_caches(): void {
        foreach (['knowledge', 'knowledge_topics', 'knowledge_context', 'knowledge_relations'] as $definition) {
            \cache::make('local_educambot', $definition)->purge();
        }
    }
}
