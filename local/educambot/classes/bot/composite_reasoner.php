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
 * Composite reasoner able to combine rules, structured knowledge and contextual hints.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

use local_educambot\local\context_provider;
use local_educambot\local\knowledge_repository;
use local_educambot\local\text_helper;
use moodle_exception;

/**
 * Aggregates the different retrievers to provide a single answer.
 */
class composite_reasoner implements reasoner_interface {
    /** @var context_provider */
    protected context_provider $contextprovider;

    /** @var knowledge_repository */
    protected knowledge_repository $knowledge;

    /** @var int|null */
    protected ?int $courseid;

    /** @var string|null */
    protected ?string $normalizedpage;

    /**
     * Constructor.
     *
     * @param context_provider $contextprovider
     * @param knowledge_repository $knowledge
     * @param int|null $courseid
     * @param string|null $normalizedpage
     */
    public function __construct(
        context_provider $contextprovider,
        knowledge_repository $knowledge,
        ?int $courseid,
        ?string $normalizedpage
    ) {
        $this->contextprovider = $contextprovider;
        $this->knowledge = $knowledge;
        $this->courseid = $courseid;
        $this->normalizedpage = $normalizedpage;
    }

    /**
     * {@inheritDoc}
     */
    public function decide(string $question, array $rulematches, array $knowledgehits): ?array {
        $decision = null;
        $bestknowledge = null;

        // DEBUG: Log decision inputs.
        debugging('composite_reasoner::decide - Rules: ' . count($rulematches) . ', Knowledge hits: ' . count($knowledgehits), DEBUG_DEVELOPER);

        if (!empty($knowledgehits)) {
            $knowledgehits = $this->decorate_knowledge_hits($knowledgehits);
            $bestknowledge = $knowledgehits[0] ?? null;
        }

        $bestrule = $rulematches[0] ?? null;
        $rulescore = $bestrule['score'] ?? 0;
        $knowledgescore = $bestknowledge['score'] ?? 0;

        // DEBUG: Log best scores.
        debugging('composite_reasoner::decide - Best rule score: ' . $rulescore . ', Best knowledge score: ' . $knowledgescore, DEBUG_DEVELOPER);

        if ($bestknowledge !== null) {
            $knowledgescore = $this->apply_contextual_boosts($bestknowledge, $question);
        }

        if ($bestrule !== null) {
            $rulescore = $this->stabilise_rule_score($rulescore, $bestrule, $question);
        }

        // BUGFIX: Reduced threshold from 0.15 to 0.05 to give knowledge more chance to be selected.
        // BUGFIX: Added minimum score threshold of 0.15 for knowledge to be considered valid.
        $minknowledgescore = 0.15;

        if ($bestrule !== null && $bestknowledge !== null) {
            // Knowledge wins if it has a better score OR if rule score is very low.
            if ($knowledgescore >= $minknowledgescore && ($knowledgescore > $rulescore + 0.05 || $rulescore < 0.3)) {
                $decision = [
                    'type' => 'knowledge',
                    'score' => $knowledgescore,
                    'knowledge' => $knowledgehits,
                ];
            } else {
                $decision = [
                    'type' => 'rule',
                    'score' => $rulescore,
                    'rule' => $bestrule,
                ];
            }
        } else if ($bestrule !== null) {
            $decision = [
                'type' => 'rule',
                'score' => $rulescore,
                'rule' => $bestrule,
            ];
        } else if ($bestknowledge !== null && $knowledgescore >= $minknowledgescore) {
            // BUGFIX: Only return knowledge if it meets minimum score threshold.
            $decision = [
                'type' => 'knowledge',
                'score' => $knowledgescore,
                'knowledge' => $knowledgehits,
            ];
        }

        if ($decision && $decision['type'] === 'knowledge' && empty($decision['knowledge'])) {
            // Safeguard to avoid returning empty knowledge bundles.
            debugging('composite_reasoner::decide - REJECTING knowledge decision due to empty bundle.', DEBUG_DEVELOPER);
            $decision = null;
        }

        // DEBUG: Log final decision.
        if ($decision) {
            debugging('composite_reasoner::decide - DECISION: ' . $decision['type'] . ' with score ' . $decision['score'], DEBUG_DEVELOPER);
        } else {
            debugging('composite_reasoner::decide - NO DECISION MADE (returning null)', DEBUG_DEVELOPER);
        }

        return $decision;
    }

    /**
     * {@inheritDoc}
     */
    public function get_context_provider(): context_provider {
        return $this->contextprovider;
    }

    /**
     * Boosts knowledge matches depending on the current course and page.
     *
     * @param array $hit
     * @param string $question
     * @return float
     */
    protected function apply_contextual_boosts(array &$hit, string $question): float {
        $score = $hit['score'] ?? 0;
        $focuscourse = $this->contextprovider->get_focus_course();
        $courseids = $hit['courseids'] ?? [];
        if ($focuscourse && !empty($courseids)) {
            if (in_array((int)$focuscourse->id, array_map('intval', $courseids), true)) {
                $score += 0.2;
            }
        }

        if (!empty($hit['contexts']) && $this->normalizedpage) {
            foreach ($hit['contexts'] as $context) {
                $normalizedcontext = text_helper::normalize($context);
                if ($normalizedcontext !== '' && str_contains($this->normalizedpage, $normalizedcontext)) {
                    $score += 0.15;
                    break;
                }
            }
        }

        // Lightweight keyword check to favour knowledge with matching tags.
        if (!empty($hit['record']->tags)) {
            $tagtext = text_helper::normalize($hit['record']->tags);
            $normalizedquestion = text_helper::normalize($question);
            if ($tagtext !== '' && $normalizedquestion !== '') {
                $overlap = text_helper::token_overlap($tagtext, $normalizedquestion);
                if ($overlap >= 0.3) {
                    $score += 0.05;
                }
            }
        }

        $hit['score'] = min(1.2, $score);
        return $hit['score'];
    }

    /**
     * Normalises the rule score to avoid overconfidence for short matches.
     *
     * @param float $score
     * @param array $match
     * @param string $question
     * @return float
     */
    protected function stabilise_rule_score(float $score, array $match, string $question): float {
        $pattern = text_helper::normalize($match['entry']->pattern ?? '');
        $normalizedquestion = text_helper::normalize($question);
        if ($pattern !== '' && $normalizedquestion !== '') {
            $overlap = text_helper::token_overlap($pattern, $normalizedquestion);
            if ($overlap < 0.45) {
                $score -= 0.1;
            }
        }
        return max(0, $score);
    }

    /**
     * Expands the main knowledge hits with connected entries.
     *
     * @param array $knowledgehits
     * @return array
     */
    protected function decorate_knowledge_hits(array $knowledgehits): array {
        try {
            $roles = $this->contextprovider->get_effective_roles();
            $expanded = $this->knowledge->expand_with_relations($knowledgehits, 1, 6, $roles);
        } catch (moodle_exception $e) {
            // Should not interrupt the reasoning flow when relations fail.
            $expanded = $knowledgehits;
        }
        return $expanded;
    }
}
