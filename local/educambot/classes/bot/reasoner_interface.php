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
 * Contract for reasoning strategies used by the Educam Bot engine.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

use local_educambot\local\context_provider;

/**
 * Defines the behaviour of a reasoning strategy.
 */
interface reasoner_interface {
    /**
     * Decides the most relevant outcome for a question.
     *
     * @param string $question Normalised user question.
     * @param array $rulematches Rule matches produced by the fuzzy retriever.
     * @param array $knowledgehits Knowledge entries ranked for the question.
     * @return array|null Returns an associative array describing the selected outcome or null when none applies.
     */
    public function decide(string $question, array $rulematches, array $knowledgehits): ?array;

    /**
     * Returns the context provider attached to the reasoner.
     *
     * @return context_provider
     */
    public function get_context_provider(): context_provider;
}
