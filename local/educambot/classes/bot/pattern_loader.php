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
 * Pattern loader for educambot - loads NLP patterns from database.
 *
 * This class centralizes pattern loading from the local_educambot_pattern table.
 * All patterns are cached in memory for performance.
 *
 * Pattern types:
 * - intent: Intent detection patterns (greeting, farewell, query, etc.)
 * - topic: Topic classification patterns (assignments, grades, calendar, etc.)
 * - sentiment: Sentiment analysis patterns (positive, negative, frustrated, etc.)
 * - stopword: Stopwords for text normalization
 * - abbreviation: Abbreviation expansions
 * - synonym: Synonym groups
 * - entity: Entity extraction patterns (dates, times, references)
 * - conversation: Conversation flow patterns (follow-ups, pronouns)
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Pattern loader class - loads NLP patterns from database.
 */
class pattern_loader {

    /** @var bool Whether patterns have been loaded */
    private static $loaded = false;

    /** @var array Cached patterns by type */
    private static $cache = [];

    /** @var string Current language */
    private static $lang = 'es';

    /**
     * Load all patterns from database.
     *
     * @param string $lang Language code (default: es)
     */
    public static function load(string $lang = 'es'): void {
        global $DB;

        // Skip if already loaded for this language.
        if (self::$loaded && self::$lang === $lang) {
            return;
        }

        self::$lang = $lang;
        self::$cache = [];

        try {
            // Check if table exists.
            $dbman = $DB->get_manager();
            if (!$dbman->table_exists('local_educambot_pattern')) {
                self::$loaded = true;
                return;
            }

            // Load all patterns for the specified language.
            $patterns = $DB->get_records('local_educambot_pattern', [
                'lang' => $lang,
                'enabled' => 1
            ], 'type, sortorder');

            foreach ($patterns as $pattern) {
                if (!isset(self::$cache[$pattern->type])) {
                    self::$cache[$pattern->type] = [];
                }

                // Decode JSON data.
                $data = json_decode($pattern->patterndata, true);

                if ($pattern->patternkey === 'default') {
                    // Simple patterns - store full data.
                    self::$cache[$pattern->type] = $data;
                } else {
                    // Structured patterns - store by key.
                    self::$cache[$pattern->type][$pattern->patternkey] = $data;
                }
            }

            self::$loaded = true;

        } catch (\Exception $e) {
            debugging('Pattern loader error: ' . $e->getMessage(), DEBUG_DEVELOPER);
            self::$loaded = true;
        }
    }

    /**
     * Get patterns by type.
     *
     * @param string $type Pattern type
     * @return array Patterns or empty array
     */
    public static function get(string $type): array {
        self::ensure_loaded();
        return self::$cache[$type] ?? [];
    }

    /**
     * Get a specific pattern by type and key.
     *
     * @param string $type Pattern type
     * @param string $key Pattern key
     * @return array|null Pattern data or null
     */
    public static function get_pattern(string $type, string $key): ?array {
        self::ensure_loaded();
        return self::$cache[$type][$key] ?? null;
    }

    /**
     * Get intent patterns.
     *
     * @return array Intent patterns
     */
    public static function get_intents(): array {
        return self::get('intent');
    }

    /**
     * Get topic patterns.
     *
     * @return array Topic patterns
     */
    public static function get_topics(): array {
        return self::get('topic');
    }

    /**
     * Get sentiment patterns.
     *
     * @return array Sentiment patterns
     */
    public static function get_sentiments(): array {
        return self::get('sentiment');
    }

    /**
     * Get urgency keywords from sentiments.
     *
     * @return array Urgency keywords
     */
    public static function get_urgency_keywords(): array {
        $sentiments = self::get('sentiment');
        return $sentiments['urgency_keywords'] ?? [];
    }

    /**
     * Get stopwords.
     *
     * @return array Stopwords
     */
    public static function get_stopwords(): array {
        $data = self::get('stopword');
        return $data['stopwords'] ?? [];
    }

    /**
     * Get abbreviations.
     *
     * @return array Abbreviations (abbreviation => expansion)
     */
    public static function get_abbreviations(): array {
        $data = self::get('abbreviation');
        return $data['abbreviations'] ?? [];
    }

    /**
     * Get synonyms.
     *
     * @return array Synonyms (canonical => [synonyms])
     */
    public static function get_synonyms(): array {
        $data = self::get('synonym');
        return $data['synonyms'] ?? [];
    }

    /**
     * Get entity patterns.
     *
     * @return array Entity patterns
     */
    public static function get_entities(): array {
        return self::get('entity');
    }

    /**
     * Get conversation patterns.
     *
     * @return array Conversation patterns
     */
    public static function get_conversation(): array {
        return self::get('conversation');
    }

    /**
     * Get follow-up detection patterns.
     *
     * @return array Follow-up detection patterns
     */
    public static function get_follow_up_detection(): array {
        $conv = self::get_conversation();
        return $conv['follow_up_detection'] ?? [];
    }

    /**
     * Get follow-up prompts patterns.
     *
     * @return array Follow-up prompts patterns
     */
    public static function get_follow_up_prompts(): array {
        $conv = self::get_conversation();
        return $conv['follow_up_prompts'] ?? [];
    }

    /**
     * Get pronoun to entity mapping.
     *
     * @return array Pronoun entity mapping
     */
    public static function get_pronoun_entity_mapping(): array {
        $conv = self::get_conversation();
        return $conv['pronoun_entity_mapping'] ?? [];
    }

    /**
     * Ensure patterns are loaded.
     */
    private static function ensure_loaded(): void {
        if (!self::$loaded) {
            self::load();
        }
    }

    /**
     * Clear cached patterns (useful for testing or after updates).
     */
    public static function clear_cache(): void {
        self::$loaded = false;
        self::$cache = [];
    }

    /**
     * Check if patterns are loaded.
     *
     * @return bool True if loaded
     */
    public static function is_loaded(): bool {
        return self::$loaded;
    }

    /**
     * Get current language.
     *
     * @return string Current language code
     */
    public static function get_language(): string {
        return self::$lang;
    }

    /**
     * Reload patterns from database.
     *
     * @param string $lang Language code
     */
    public static function reload(string $lang = 'es'): void {
        self::$loaded = false;
        self::load($lang);
    }
}
