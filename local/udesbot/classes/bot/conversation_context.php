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
 * Conversation context for udesbot - manages conversational state.
 *
 * Features:
 * - Tracks conversation topic across messages
 * - Remembers mentioned entities (assignments, courses, etc.)
 * - Resolves pronouns and references ("that task", "this one")
 * - Maintains short-term memory for follow-up questions
 * - Persists to database for cross-session continuity
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_udesbot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Conversation context class - manages conversational state.
 */
class conversation_context {

    /** @var int Maximum context age in seconds (30 minutes) */
    private const MAX_CONTEXT_AGE = 1800;

    /** @var int Maximum number of turns to remember */
    private const MAX_TURNS = 10;

    /** @var int User ID */
    private $userid;

    /** @var int Course ID */
    private $courseid;

    /** @var string Session ID for grouping conversations */
    private $sessionid;

    /** @var array Current conversation state */
    private $state;

    /** @var bool Whether state was loaded from DB */
    private $loaded = false;

    /** @var array Loaded conversation patterns from JSON */
    private $conversationPatterns = [];

    /** @var bool Whether patterns have been loaded */
    private static $patternsLoaded = false;

    /** @var array Cached conversation patterns (static) */
    private static $cachedPatterns = [];

    /**
     * Constructor.
     *
     * @param int $userid User ID
     * @param int $courseid Course ID
     * @param string|null $sessionid Optional session ID
     */
    public function __construct(int $userid, int $courseid = SITEID, ?string $sessionid = null) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->sessionid = $sessionid ?? session_id() ?? uniqid('conv_');

        $this->load_patterns();
        $this->init_state();
    }

    /**
     * Load conversation patterns from database via pattern_loader.
     */
    private function load_patterns(): void {
        // Use cached data if available.
        if (self::$patternsLoaded) {
            $this->conversationPatterns = self::$cachedPatterns;
            return;
        }

        // Load from database via pattern_loader.
        pattern_loader::load();
        $this->conversationPatterns = pattern_loader::get_conversation();

        // Cache the loaded data.
        self::$cachedPatterns = $this->conversationPatterns;
        self::$patternsLoaded = true;
    }

    /**
     * Initialize or load conversation state.
     */
    private function init_state(): void {
        // Try to load existing context.
        $loaded = $this->load_from_db();

        if (!$loaded || $this->is_context_expired()) {
            // Initialize fresh state.
            $this->state = [
                'topic' => null,
                'subtopic' => null,
                'entities' => [],
                'last_intent' => null,
                'last_ruleid' => null,
                'turns' => [],
                'mentioned_assignments' => [],
                'mentioned_courses' => [],
                'awaiting_response' => false,
                'follow_up_expected' => false,
                'last_question' => null,
                'started_at' => time(),
                'updated_at' => time(),
            ];
            $this->save_to_db();
        }
    }

    /**
     * Check if context has expired.
     *
     * @return bool True if expired
     */
    private function is_context_expired(): bool {
        if (!isset($this->state['updated_at'])) {
            return true;
        }
        return (time() - $this->state['updated_at']) > self::MAX_CONTEXT_AGE;
    }

    /**
     * Load context from database.
     *
     * @return bool True if loaded successfully
     */
    private function load_from_db(): bool {
        global $DB;

        try {
            $record = $DB->get_record('local_udesbot_context', [
                'userid' => $this->userid,
                'sessionid' => $this->sessionid,
            ]);

            if ($record && !empty($record->state)) {
                $this->state = json_decode($record->state, true);
                $this->loaded = true;
                return true;
            }
        } catch (\Exception $e) {
            // Table might not exist yet.
            debugging('Could not load conversation context: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        return false;
    }

    /**
     * Save context to database.
     */
    public function save_to_db(): void {
        global $DB;

        $this->state['updated_at'] = time();

        try {
            $record = $DB->get_record('local_udesbot_context', [
                'userid' => $this->userid,
                'sessionid' => $this->sessionid,
            ]);

            if ($record) {
                $record->state = json_encode($this->state);
                $record->last_topic = $this->state['topic'];
                $record->last_ruleid = $this->state['last_ruleid'];
                $record->timemodified = time();
                $DB->update_record('local_udesbot_context', $record);
            } else {
                $record = new \stdClass();
                $record->userid = $this->userid;
                $record->sessionid = $this->sessionid;
                $record->courseid = $this->courseid;
                $record->state = json_encode($this->state);
                $record->last_topic = $this->state['topic'];
                $record->last_ruleid = $this->state['last_ruleid'];
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('local_udesbot_context', $record);
            }
        } catch (\Exception $e) {
            // Table might not exist yet - silently fail.
            debugging('Could not save conversation context: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Update context with a new turn.
     *
     * @param string $question User's question
     * @param string|null $response Bot's response
     * @param array $detection Intent detection results
     * @param int|null $ruleid Matched rule ID
     */
    public function add_turn(string $question, ?string $response, array $detection, ?int $ruleid = null): void {
        // Update topic.
        if (isset($detection['topic']['primary']) && $detection['topic']['confidence'] > 0.3) {
            $this->state['topic'] = $detection['topic']['primary'];
        }

        // Update intent.
        if (isset($detection['intent']['primary'])) {
            $this->state['last_intent'] = $detection['intent']['primary'];
        }

        // Store ruleid.
        $this->state['last_ruleid'] = $ruleid;
        $this->state['last_question'] = $question;

        // Extract and store entities.
        if (!empty($detection['entities'])) {
            $this->merge_entities($detection['entities']);
        }

        // Add turn to history.
        $this->state['turns'][] = [
            'question' => $question,
            'response' => $response,
            'topic' => $this->state['topic'],
            'intent' => $this->state['last_intent'],
            'ruleid' => $ruleid,
            'timestamp' => time(),
        ];

        // Limit turns history.
        if (count($this->state['turns']) > self::MAX_TURNS) {
            $this->state['turns'] = array_slice($this->state['turns'], -self::MAX_TURNS);
        }

        // Detect if follow-up is expected.
        $this->state['follow_up_expected'] = $this->detect_follow_up_expected($response);

        $this->save_to_db();
    }

    /**
     * Merge new entities with existing ones.
     *
     * @param array $newEntities New entities to add
     */
    private function merge_entities(array $newEntities): void {
        foreach ($newEntities as $type => $values) {
            if (!isset($this->state['entities'][$type])) {
                $this->state['entities'][$type] = [];
            }
            $this->state['entities'][$type] = array_unique(
                array_merge($this->state['entities'][$type], (array)$values)
            );
        }
    }

    /**
     * Detect if a follow-up question is expected.
     *
     * @param string|null $response Bot's response
     * @return bool True if follow-up expected
     */
    private function detect_follow_up_expected(?string $response): bool {
        if (!$response) {
            return false;
        }

        // Get configuration from loaded patterns.
        $followUpConfig = $this->conversationPatterns['follow_up_prompts'] ?? [];

        // Check for question indicators.
        $questionIndicators = $followUpConfig['question_indicators'] ?? ['?', '¿'];
        foreach ($questionIndicators as $indicator) {
            if (strpos($response, $indicator) !== false) {
                return true;
            }
        }

        // Check for follow-up prompts from JSON.
        $prompts = $followUpConfig['detection_phrases'] ?? [];
        foreach ($prompts as $prompt) {
            if (mb_stripos($response, $prompt) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get current topic.
     *
     * @return string|null Current topic
     */
    public function get_topic(): ?string {
        return $this->state['topic'];
    }

    /**
     * Get last matched rule ID.
     *
     * @return int|null Last rule ID
     */
    public function get_last_ruleid(): ?int {
        return $this->state['last_ruleid'];
    }

    /**
     * Get last user question.
     *
     * @return string|null Last question
     */
    public function get_last_question(): ?string {
        return $this->state['last_question'];
    }

    /**
     * Get recent conversation turns.
     *
     * @param int $limit Number of turns to return
     * @return array Recent turns
     */
    public function get_recent_turns(int $limit = 5): array {
        return array_slice($this->state['turns'], -$limit);
    }

    /**
     * Check if this appears to be a follow-up question.
     *
     * @param string $question User's question
     * @return bool True if likely a follow-up
     */
    public function is_follow_up(string $question): bool {
        // Check if previous response expected a follow-up.
        if ($this->state['follow_up_expected']) {
            return true;
        }

        // Get follow-up detection config from JSON.
        $followUpConfig = $this->conversationPatterns['follow_up_detection'] ?? [];

        // Check for short affirmative/negative responses from JSON.
        $shortResponses = $followUpConfig['short_responses'] ?? [];
        $normalized = mb_strtolower(trim($question), 'UTF-8');
        if (in_array($normalized, $shortResponses)) {
            return true;
        }

        // Check for pronoun reference patterns from JSON.
        $pronounPatterns = $followUpConfig['pronoun_patterns'] ?? [];
        foreach ($pronounPatterns as $pattern) {
            $regex = '/' . $pattern . '/ui';
            if (@preg_match($regex, $question)) {
                return true;
            }
        }

        // Check for continuation patterns from JSON.
        $continuationPatterns = $followUpConfig['continuation_patterns'] ?? [];
        foreach ($continuationPatterns as $pattern) {
            $regex = '/' . $pattern . '/ui';
            if (@preg_match($regex, $question)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve a pronoun reference to an entity.
     *
     * @param string $pronoun The pronoun to resolve
     * @return array|null Resolved entity or null
     */
    public function resolve_reference(string $pronoun): ?array {
        $pronoun = mb_strtolower(trim($pronoun), 'UTF-8');

        // Map pronouns to entity types.
        $pronounMap = [
            'esa tarea' => 'assignment',
            'ese trabajo' => 'assignment',
            'esa actividad' => 'assignment',
            'ese curso' => 'course',
            'esa materia' => 'course',
            'ese profesor' => 'teacher',
            'esa nota' => 'grade',
            'esa calificación' => 'grade',
        ];

        // Check direct mappings.
        foreach ($pronounMap as $phrase => $type) {
            if (strpos($pronoun, $phrase) !== false) {
                return $this->get_last_entity_of_type($type);
            }
        }

        // Try to get most recently mentioned entity.
        if (!empty($this->state['entities'])) {
            foreach ($this->state['entities'] as $type => $entities) {
                if (!empty($entities)) {
                    return [
                        'type' => $type,
                        'value' => end($entities),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get last entity of a specific type.
     *
     * @param string $type Entity type
     * @return array|null Entity or null
     */
    private function get_last_entity_of_type(string $type): ?array {
        if (isset($this->state['entities'][$type]) && !empty($this->state['entities'][$type])) {
            return [
                'type' => $type,
                'value' => end($this->state['entities'][$type]),
            ];
        }
        return null;
    }

    /**
     * Store a mentioned assignment for context.
     *
     * @param int $assignmentid Assignment ID
     * @param string $name Assignment name
     */
    public function remember_assignment(int $assignmentid, string $name): void {
        $this->state['mentioned_assignments'][$assignmentid] = [
            'id' => $assignmentid,
            'name' => $name,
            'mentioned_at' => time(),
        ];
        $this->state['entities']['assignments'][] = $assignmentid;
        $this->save_to_db();
    }

    /**
     * Store a mentioned course for context.
     *
     * @param int $courseid Course ID
     * @param string $name Course name
     */
    public function remember_course(int $courseid, string $name): void {
        $this->state['mentioned_courses'][$courseid] = [
            'id' => $courseid,
            'name' => $name,
            'mentioned_at' => time(),
        ];
        $this->state['entities']['courses'][] = $courseid;
        $this->save_to_db();
    }

    /**
     * Get conversation summary for context.
     *
     * @return array Conversation summary
     */
    public function get_summary(): array {
        return [
            'topic' => $this->state['topic'],
            'turn_count' => count($this->state['turns']),
            'last_intent' => $this->state['last_intent'],
            'entities' => array_keys($this->state['entities']),
            'follow_up_expected' => $this->state['follow_up_expected'],
            'duration' => time() - ($this->state['started_at'] ?? time()),
        ];
    }

    /**
     * Reset conversation context.
     */
    public function reset(): void {
        global $DB;

        $this->state = [
            'topic' => null,
            'subtopic' => null,
            'entities' => [],
            'last_intent' => null,
            'last_ruleid' => null,
            'turns' => [],
            'mentioned_assignments' => [],
            'mentioned_courses' => [],
            'awaiting_response' => false,
            'follow_up_expected' => false,
            'last_question' => null,
            'started_at' => time(),
            'updated_at' => time(),
        ];

        try {
            $DB->delete_records('local_udesbot_context', [
                'userid' => $this->userid,
                'sessionid' => $this->sessionid,
            ]);
        } catch (\Exception $e) {
            // Ignore if table doesn't exist.
        }
    }

    /**
     * Get suggested follow-up questions based on context.
     *
     * @return array Suggested questions
     */
    public function get_follow_up_suggestions(): array {
        $suggestions = [];

        switch ($this->state['topic']) {
            case 'assignments':
                $suggestions = [
                    get_string('followup_assignment_1', 'local_udesbot'),
                    get_string('followup_assignment_2', 'local_udesbot'),
                    get_string('followup_assignment_3', 'local_udesbot'),
                ];
                break;
            case 'grades':
                $suggestions = [
                    get_string('followup_grades_1', 'local_udesbot'),
                    get_string('followup_grades_2', 'local_udesbot'),
                    get_string('followup_grades_3', 'local_udesbot'),
                ];
                break;
            case 'calendar':
                $suggestions = [
                    get_string('followup_calendar_1', 'local_udesbot'),
                    get_string('followup_calendar_2', 'local_udesbot'),
                    get_string('followup_calendar_3', 'local_udesbot'),
                ];
                break;
            case 'course':
                $suggestions = [
                    get_string('followup_course_1', 'local_udesbot'),
                    get_string('followup_course_2', 'local_udesbot'),
                    get_string('followup_course_3', 'local_udesbot'),
                ];
                break;
            default:
                $suggestions = [
                    get_string('followup_general_1', 'local_udesbot'),
                    get_string('followup_general_2', 'local_udesbot'),
                    get_string('followup_general_3', 'local_udesbot'),
                ];
        }

        return $suggestions;
    }

    /**
     * Clean up old context records (called by scheduled task).
     *
     * @param int $maxAge Maximum age in seconds
     * @return int Number of records deleted
     */
    public static function cleanup_old_contexts(int $maxAge = 86400): int {
        global $DB;

        $cutoff = time() - $maxAge;

        try {
            return $DB->delete_records_select(
                'local_udesbot_context',
                'timemodified < :cutoff',
                ['cutoff' => $cutoff]
            );
        } catch (\Exception $e) {
            return 0;
        }
    }
}
