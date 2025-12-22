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
     * Get question words for question detection (v3.6.0).
     *
     * @return array Question words
     */
    public static function get_question_words(): array {
        $intents = self::get('intent');
        return $intents['question_words']['words'] ?? [];
    }

    /**
     * Get question word patterns (v3.6.0).
     *
     * @return array Question word regex patterns
     */
    public static function get_question_patterns(): array {
        $intents = self::get('intent');
        return $intents['question_words']['patterns'] ?? [];
    }

    /**
     * Get verb analysis data for transitive/reflexive detection (v3.7.0).
     *
     * @return array Verb analysis patterns
     */
    public static function get_verb_analysis(): array {
        $intents = self::get('intent');
        return $intents['verb_analysis'] ?? [];
    }

    /**
     * Get reflexive markers (v3.7.0).
     *
     * @return array Reflexive marker words and patterns
     */
    public static function get_reflexive_markers(): array {
        $verbAnalysis = self::get_verb_analysis();
        return $verbAnalysis['reflexive_markers'] ?? [];
    }

    /**
     * Get transitive markers (v3.7.0).
     *
     * @return array Transitive marker words and patterns
     */
    public static function get_transitive_markers(): array {
        $verbAnalysis = self::get_verb_analysis();
        return $verbAnalysis['transitive_markers'] ?? [];
    }

    /**
     * Get action objects for Moodle context (v3.7.0).
     *
     * @return array Action objects by category
     */
    public static function get_action_objects(): array {
        $verbAnalysis = self::get_verb_analysis();
        return $verbAnalysis['action_objects'] ?? [];
    }

    /**
     * Get verb conjugations (v3.7.0).
     *
     * @return array Verb conjugation data
     */
    public static function get_verb_conjugations(): array {
        $intents = self::get('intent');
        return $intents['verb_conjugations'] ?? [];
    }

    /**
     * Get all conjugations for a verb stem (v3.7.0).
     *
     * @param string $stem Verb stem to search
     * @return array|null Verb data or null
     */
    public static function get_verb_by_stem(string $stem): ?array {
        $conjugations = self::get_verb_conjugations();
        foreach ($conjugations as $verb => $data) {
            $stems = $data['stems'] ?? [];
            foreach ($stems as $verbStem) {
                if (mb_strpos($stem, $verbStem) === 0 || mb_strpos($verbStem, $stem) === 0) {
                    return array_merge(['verb' => $verb], $data);
                }
            }
        }
        return null;
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
     * Get role knowledge patterns.
     *
     * @return array Role knowledge patterns
     */
    public static function get_role_knowledge(): array {
        return self::get('role_knowledge');
    }

    /**
     * Get role-specific data for an archetype.
     *
     * @param string $archetype User's archetype (student, teacher, etc.)
     * @return array Role-specific data or default user data
     */
    public static function get_role_data(string $archetype): array {
        $roleKnowledge = self::get_role_knowledge();
        $archetypes = $roleKnowledge['archetypes'] ?? [];

        // Return specific archetype data or fallback to 'user'.
        return $archetypes[$archetype] ?? $archetypes['user'] ?? [];
    }

    /**
     * Get priority topics for an archetype.
     *
     * @param string $archetype User's archetype
     * @return array Priority topics for scoring boost
     */
    public static function get_archetype_priority_topics(string $archetype): array {
        $roleData = self::get_role_data($archetype);
        return $roleData['priority_topics'] ?? [];
    }

    /**
     * Get quick actions for an archetype.
     *
     * @param string $archetype User's archetype
     * @return array Quick action buttons
     */
    public static function get_archetype_quick_actions(string $archetype): array {
        $roleData = self::get_role_data($archetype);
        return $roleData['quick_actions'] ?? [];
    }

    /**
     * Get menu options for an archetype.
     *
     * @param string $archetype User's archetype
     * @return array Menu options
     */
    public static function get_archetype_menu_options(string $archetype): array {
        $roleData = self::get_role_data($archetype);
        return $roleData['menu_options'] ?? [];
    }

    /**
     * Get greeting for an archetype.
     *
     * @param string $archetype User's archetype
     * @return string Greeting message
     */
    public static function get_archetype_greeting(string $archetype): string {
        $roleData = self::get_role_data($archetype);
        return $roleData['greeting'] ?? '';
    }

    /**
     * Get response templates (fallbacks, greetings, etc.).
     *
     * @return array Response templates
     */
    public static function get_responses(): array {
        return self::get('response');
    }

    /**
     * Get fallback responses.
     *
     * @return array Fallback response options
     */
    public static function get_fallback_responses(): array {
        $responses = self::get_responses();
        return $responses['fallback'] ?? [];
    }

    /**
     * Get topic suggestions for fallback.
     *
     * @param string $topic Topic name
     * @return array Suggested actions for the topic
     */
    public static function get_topic_suggestions(string $topic): array {
        $responses = self::get_responses();
        $suggestions = $responses['topic_suggestions'] ?? [];
        return $suggestions[$topic] ?? $suggestions['general'] ?? [];
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
