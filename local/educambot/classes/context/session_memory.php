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
 * Conversation memory utilities for Educam Bot sessions.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\context;

use core_text;

/**
 * Stores the last exchanges of the conversation in the Moodle session.
 */
class session_memory {
    /** Session storage key. */
    private const STORAGE_KEY = 'local_educambot_memory';

    /** Default history length when no configuration is supplied. */
    private const DEFAULT_HISTORY_LIMIT = 8;

    /** @var string */
    protected string $sessionid;

    /** @var int */
    protected int $limit;

    /**
     * Constructor.
     *
     * @param string|null $sessionid
     * @param int|null $limit
     */
    public function __construct(?string $sessionid, ?int $limit = null) {
        $this->sessionid = $sessionid !== null && $sessionid !== '' ? $sessionid : 'default';
        $this->limit = $limit ?? self::DEFAULT_HISTORY_LIMIT;
        $this->initialise_storage();
    }

    /**
     * Records a new exchange in the session memory.
     *
     * @param string $question
     * @param string $response
     * @param array $analysis
     * @param array $metadata
     */
    public function remember(string $question, string $response, array $analysis, array $metadata = []): void {
        $history = $this->get_storage();
        $history[] = [
            'timestamp' => time(),
            'question' => $question,
            'response' => $response,
            'analysis' => $analysis,
            'metadata' => $metadata,
        ];
        if (count($history) > $this->limit) {
            $history = array_slice($history, -1 * $this->limit);
        }
        $this->set_storage($history);
    }

    /**
     * Returns the last exchanges.
     *
     * @param int $limit
     * @return array<int,array>
     */
    public function history(int $limit = 5): array {
        $history = $this->get_storage();
        if ($limit <= 0) {
            return $history;
        }
        return array_slice($history, -1 * $limit);
    }

    /**
     * Returns the most recent exchange.
     *
     * @return array|null
     */
    public function last(): ?array {
        $history = $this->get_storage();
        if (empty($history)) {
            return null;
        }
        return end($history) ?: null;
    }

    /**
     * Determines if the current question likely references previous context.
     *
     * @param array<int,string> $tokens
     * @return bool
     */
    public function looks_like_followup(array $tokens): bool {
        $tokens = array_map(static fn($token) => core_text::strtolower(trim((string)$token)), $tokens);
        $markers = ['eso', 'esa', 'ese', 'anterior', 'anteriormente', 'lo', 'la', 'dicho', 'mencionado', 'otra', 'otro'];
        return (bool)array_intersect($markers, $tokens);
    }

    /**
     * Boosts knowledge hits reusing the last answer when the user is making a follow-up question.
     *
     * @param array $analysis
     * @param array<int,array> $knowledgehits
     * @return array<int,array>
     */
    public function boost_followup_hits(array $analysis, array $knowledgehits): array {
        if (empty($knowledgehits)) {
            return $knowledgehits;
        }
        $tokens = $analysis['tokens'] ?? [];
        if (!$this->looks_like_followup($tokens)) {
            return $knowledgehits;
        }
        $last = $this->last();
        if (!$last) {
            return $knowledgehits;
        }
        $lastknowledge = (int)($last['metadata']['knowledgeid'] ?? 0);
        if (!$lastknowledge) {
            return $knowledgehits;
        }
        foreach ($knowledgehits as &$hit) {
            $record = $hit['record'] ?? null;
            if ($record && (int)$record->id === $lastknowledge) {
                $hit['score'] = min(1.2, ($hit['score'] ?? 0) + 0.25);
            }
        }
        unset($hit);
        return $knowledgehits;
    }

    /**
     * Aggregates keywords from the current history.
     *
     * @param int $limit
     * @return array<int,string>
     */
    public function aggregated_keywords(int $limit = 3): array {
        $keywords = [];
        foreach ($this->history($limit) as $item) {
            $analysis = $item['analysis'] ?? [];
            foreach (($analysis['keywords'] ?? []) as $keyword) {
                $keywords[] = $keyword;
            }
        }
        return array_values(array_unique(array_filter($keywords)));
    }

    /**
     * Clears the stored history for the current session id.
     */
    public function reset(): void {
        $this->set_storage([]);
    }

    /**
     * Initialises storage within the Moodle session.
     */
    protected function initialise_storage(): void {
        global $SESSION;
        if (!isset($SESSION->{self::STORAGE_KEY}) || !is_array($SESSION->{self::STORAGE_KEY})) {
            $SESSION->{self::STORAGE_KEY} = [];
        }
        if (!isset($SESSION->{self::STORAGE_KEY}[$this->sessionid]) || !is_array($SESSION->{self::STORAGE_KEY}[$this->sessionid])) {
            $SESSION->{self::STORAGE_KEY}[$this->sessionid] = [];
        }
    }

    /**
     * Returns the stored history for the current session id.
     *
     * @return array<int,array>
     */
    protected function get_storage(): array {
        global $SESSION;
        $this->initialise_storage();
        return $SESSION->{self::STORAGE_KEY}[$this->sessionid];
    }

    /**
     * Persists the history for the current session id.
     *
     * @param array<int,array> $history
     */
    protected function set_storage(array $history): void {
        global $SESSION;
        $this->initialise_storage();
        $SESSION->{self::STORAGE_KEY}[$this->sessionid] = $history;
    }
}
