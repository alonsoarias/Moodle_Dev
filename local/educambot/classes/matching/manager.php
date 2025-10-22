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
 * Advanced matching manager that evaluates rules across multiple levels.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\matching;

use local_educambot\local\text_helper;
use local_educambot\nlp\pipeline;
use stdClass;

/**
 * Provides scoring utilities used by the rule engine.
 */
class manager {
    /** @var pipeline */
    protected pipeline $pipeline;

    /** @var array<string,mixed> */
    protected array $config;

    /**
     * Constructor.
     *
     * @param pipeline $pipeline
     * @param array<string,mixed> $config
     */
    public function __construct(pipeline $pipeline, array $config = []) {
        $this->pipeline = $pipeline;
        $this->config = $config;
    }

    /**
     * Scores a rule entry applying the different matching levels defined in the deep analysis plan.
     *
     * @param stdClass $entry
     * @param array $analysis Result of {@see pipeline::process()} for the question.
     * @param string $originalquestion
     * @param string $normalizedpage
     * @return array{score:float, breakdown:array<string,float>}
     */
    public function score_entry(stdClass $entry, array $analysis, string $originalquestion, string $normalizedpage): array {
        $score = 0.0;
        $breakdown = [
            'exact' => 0.0,
            'partial' => 0.0,
            'semantic' => 0.0,
            'contextual' => 0.0,
            'keywords' => 0.0,
        ];

        $phrases = $this->get_phrases($entry);
        $normalizedquestion = $analysis['normalised'] ?? '';
        $questiontokens = $analysis['tokens'] ?? [];
        $questionkeywords = $analysis['keywords'] ?? [];
        $entities = $analysis['entities'] ?? [];
        $synonymmap = $this->parse_synonym_map($entry);

        foreach ($phrases as $phrase) {
            $normalizedphrase = text_helper::normalize($phrase);
            if ($normalizedphrase === '') {
                continue;
            }

            if ($normalizedphrase === $normalizedquestion) {
                $breakdown['exact'] = max($breakdown['exact'], 1.0);
                $score += 1.0;
                break;
            }

            $wildcardscore = $this->match_wildcard($phrase, $originalquestion, $normalizedquestion);
            if ($wildcardscore !== null) {
                if ($wildcardscore > 0) {
                    $breakdown['exact'] = max($breakdown['exact'], $wildcardscore);
                    $score += $wildcardscore;
                    continue;
                }
            }

            if ($normalizedquestion !== '' && str_contains($normalizedquestion, $normalizedphrase)) {
                $breakdown['partial'] = max($breakdown['partial'], 0.85);
                $score += 0.85;
            }

            $similarity = text_helper::string_similarity($normalizedphrase, $normalizedquestion);
            if ($similarity > 0.65) {
                $contribution = $similarity * 0.7;
                $breakdown['partial'] = max($breakdown['partial'], $contribution);
                $score += $contribution;
            } else if ($similarity > 0.55) {
                $contribution = $similarity * 0.4;
                $breakdown['partial'] = max($breakdown['partial'], $contribution);
                $score += $contribution;
            }

            $phraseTokens = text_helper::tokenize($phrase);
            if (!empty($phraseTokens)) {
                $overlap = text_helper::token_overlap_score($phraseTokens, $questiontokens);
                if ($overlap > 0) {
                    $contribution = $overlap * 0.6;
                    $breakdown['partial'] = max($breakdown['partial'], $contribution);
                    $score += $contribution;
                }
            }
        }

        if (!empty($synonymmap) && !empty($questionkeywords)) {
            $matched = 0;
            $total = 0;
            foreach ($synonymmap as $lemma => $synonyms) {
                $total++;
                $tokens = array_merge([$lemma], $synonyms);
                $tokens = array_map(static fn($token) => text_helper::normalize($token), $tokens);
                if (array_intersect($tokens, $questionkeywords)) {
                    $matched++;
                }
            }
            if ($total > 0 && $matched > 0) {
                $ratio = $matched / $total;
                $contribution = min(0.4, $ratio * 0.6 + 0.1);
                $breakdown['semantic'] = $contribution;
                $score += $contribution;
            }
        }

        $keywords = $this->get_keywords($entry);
        if (!empty($keywords)) {
            $matchedkeywords = 0.0;
            foreach ($keywords as $keyword) {
                if (in_array($keyword, $questionkeywords, true)) {
                    $matchedkeywords += 1.0;
                } else if ($keyword !== '' && str_contains($normalizedquestion, $keyword)) {
                    $matchedkeywords += 0.5;
                }
            }
            if ($matchedkeywords > 0) {
                $contribution = min(0.35, ($matchedkeywords / max(1, count($keywords))) * 0.8);
                $breakdown['keywords'] = $contribution;
                $score += $contribution;
            }
        }

        $contextscore = $this->score_contextual_signals($entry, $normalizedquestion, $normalizedpage, $entities);
        if ($contextscore > 0) {
            $breakdown['contextual'] = $contextscore;
            $score += $contextscore;
        }

        return [
            'score' => $score,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Splits the configured phrases for the entry.
     *
     * @param stdClass $entry
     * @return array<int,string>
     */
    protected function get_phrases(stdClass $entry): array {
        $phrases = [];
        if (!empty($entry->pattern)) {
            $phrases[] = $entry->pattern;
        }
        if (!empty($entry->synonyms)) {
            foreach ($this->explode_lines($entry->synonyms) as $line) {
                if (str_contains($line, '->') || str_contains($line, '→')) {
                    continue;
                }
                $phrases[] = $line;
            }
        }
        return array_filter(array_map('trim', $phrases));
    }

    /**
     * Returns the keyword list for the entry.
     *
     * @param stdClass $entry
     * @return array<int,string>
     */
    protected function get_keywords(stdClass $entry): array {
        if (empty($entry->keywords)) {
            return [];
        }
        $keywords = preg_split('/[,;]+/', $entry->keywords, -1, PREG_SPLIT_NO_EMPTY);
        if (!$keywords) {
            return [];
        }
        return array_values(array_filter(array_map(static function(string $keyword): string {
            return text_helper::normalize($keyword);
        }, $keywords)));
    }

    /**
     * Parses synonym map definitions from the entry.
     *
     * @param stdClass $entry
     * @return array<string,array<int,string>>
     */
    protected function parse_synonym_map(stdClass $entry): array {
        if (empty($entry->synonyms)) {
            return [];
        }
        $map = [];
        foreach ($this->explode_lines($entry->synonyms) as $line) {
            $separator = null;
            if (str_contains($line, '->')) {
                $separator = '->';
            } else if (str_contains($line, '→')) {
                $separator = '→';
            }
            if (!$separator) {
                continue;
            }
            [$lemma, $synonyms] = array_map('trim', explode($separator, $line, 2));
            $lemma = text_helper::normalize($lemma);
            if ($lemma === '') {
                continue;
            }
            $synonyms = trim($synonyms);
            if ($synonyms === '') {
                continue;
            }
            $synonyms = trim($synonyms, '[]');
            $synonyms = preg_split('/[,;]+/', $synonyms, -1, PREG_SPLIT_NO_EMPTY);
            if (!$synonyms) {
                continue;
            }
            $map[$lemma] = array_values(array_filter(array_map(static function(string $token): string {
                return text_helper::normalize($token);
            }, $synonyms)));
        }
        return $map;
    }

    /**
     * Scores contextual hints including Moodle page identifiers and detected entities.
     *
     * @param stdClass $entry
     * @param string $normalizedquestion
     * @param string $normalizedpage
     * @param array<string,array> $entities
     * @return float
     */
    protected function score_contextual_signals(stdClass $entry, string $normalizedquestion, string $normalizedpage, array $entities): float {
        $score = 0.0;
        if (!empty($entry->contexts)) {
            foreach ($this->explode_lines($entry->contexts) as $context) {
                $normalizedcontext = text_helper::normalize($context);
                if ($normalizedcontext === '') {
                    continue;
                }
                if ($normalizedpage !== '' && str_contains($normalizedpage, $normalizedcontext)) {
                    $score += 0.2;
                    break;
                }
                if ($normalizedquestion !== '' && str_contains($normalizedquestion, $normalizedcontext)) {
                    $score += 0.15;
                    break;
                }
            }
        }

        if (!empty($entities['activities']) && !empty($entry->contexts)) {
            foreach ($entities['activities'] as $activity) {
                $activity = text_helper::normalize($activity);
                if ($activity !== '' && str_contains($entry->contexts, $activity)) {
                    $score += 0.1;
                    break;
                }
            }
        }

        return $score;
    }

    /**
     * Splits lines of text trimming blank entries.
     *
     * @param string $text
     * @return array<int,string>
     */
    protected function explode_lines(string $text): array {
        $lines = preg_split('/\r?\n/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$lines) {
            return [];
        }
        return array_map('trim', $lines);
    }

    /**
     * Evaluates wildcard patterns similar to the legacy implementation.
     *
     * @param string $phrase
     * @param string $originalquestion
     * @param string $normalizedquestion
     * @return float|null
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
}
