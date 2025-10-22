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
 * Inference coordinator for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\inference;

use local_educambot\bot\composite_reasoner;
use local_educambot\context\session_memory;

/**
 * Wraps the composite reasoner adding contextual memory adjustments.
 */
class engine {
    /** @var composite_reasoner */
    protected composite_reasoner $reasoner;

    /** @var session_memory */
    protected session_memory $memory;

    /**
     * Constructor.
     *
     * @param composite_reasoner $reasoner
     * @param session_memory $memory
     */
    public function __construct(composite_reasoner $reasoner, session_memory $memory) {
        $this->reasoner = $reasoner;
        $this->memory = $memory;
    }

    /**
     * Runs the inference process.
     *
     * @param string $question
     * @param array $analysis
     * @param array $rulematches
     * @param array $knowledgehits
     * @return array|null
     */
    public function decide(string $question, array $analysis, array $rulematches, array $knowledgehits): ?array {
        $knowledgehits = $this->memory->boost_followup_hits($analysis, $knowledgehits);
        if (!empty($knowledgehits)) {
            usort($knowledgehits, static function(array $a, array $b): int {
                return ($b['score'] ?? 0) <=> ($a['score'] ?? 0);
            });
        }

        $decision = $this->reasoner->decide($question, $rulematches, $knowledgehits);
        if ($decision && $decision['type'] === 'knowledge') {
            $decision['knowledge'] = $knowledgehits;
        }
        return $decision;
    }

    /**
     * Exposes the session memory for post processing.
     *
     * @return session_memory
     */
    public function get_memory(): session_memory {
        return $this->memory;
    }
}
