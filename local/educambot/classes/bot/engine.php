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
 * Bot engine - handles question processing and response generation (v3.4.0).
 *
 * Major improvements:
 * - Advanced text normalization with abbreviation expansion
 * - Intent detection for better context understanding
 * - Conversation context for follow-up questions
 * - Levenshtein distance for typo tolerance
 * - Synonym matching for flexible keyword matching
 * - Rule prioritization based on feedback and usage
 * - Better fallback with similar question suggestions
 * - Response variants for natural conversation
 * - N-gram matching for partial phrase matching (v3.3.0)
 * - Context-aware scoring boost (v3.3.0)
 * - Multi-word keyword matching (v3.3.0)
 * - Database-driven response templates (v3.3.0)
 * - Role-aware responses with archetype priority scoring (v3.4.0)
 * - Archetype-specific menu options and quick actions (v3.4.0)
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Bot engine class - core question processing.
 */
class engine {

    /** @var int Course ID for context filtering */
    protected $courseid;

    /** @var int User ID for role filtering */
    protected $userid;

    /** @var string Primary user archetype for role-aware responses (v3.4.0) */
    protected $userArchetype;

    /** @var array Priority topics for the current user archetype (v3.4.0) */
    protected $archetypePriorityTopics;

    /** @var text_normalizer Text normalizer instance */
    protected $normalizer;

    /** @var intent_detector Intent detector instance */
    protected $intentDetector;

    /** @var conversation_context Conversation context instance */
    protected $conversationContext;

    /** @var context_handler Moodle context handler */
    protected $moodleContext;

    /** @var response_builder Response builder instance */
    protected $responseBuilder;

    /** @var float Minimum confidence threshold */
    protected const MIN_CONFIDENCE = 0.25;

    /** @var float High confidence threshold */
    protected const HIGH_CONFIDENCE = 0.7;

    /** @var array Scoring weights for different match types */
    protected const SCORE_WEIGHTS = [
        'exact_match' => 100,
        'pattern_contains' => 45,
        'question_contains' => 35,
        'word_overlap' => 30,
        'keyword_match' => 25,
        'multi_word_keyword' => 28,
        'synonym_match' => 20,
        'ngram_match' => 18,
        'levenshtein' => 15,
        'context_boost' => 12,
        'archetype_priority' => 12,   // Boost for archetype priority topics (v3.4.0).
        'topic_match' => 10,
        'feedback_boost' => 10,
        'priority_boost' => 5,
        'intent_match' => 8,
    ];

    /**
     * Constructor.
     *
     * @param int $courseid Course ID for context
     * @param int $userid User ID for role filtering
     */
    public function __construct(int $courseid = SITEID, int $userid = 0) {
        global $USER;

        $this->courseid = $courseid;
        $this->userid = $userid ?: $USER->id;

        // Initialize components.
        $this->normalizer = new text_normalizer(false, true);
        $this->intentDetector = new intent_detector($this->normalizer);
        $this->conversationContext = new conversation_context($this->userid, $this->courseid);
        $this->moodleContext = new context_handler($this->courseid, $this->userid);
        $this->responseBuilder = new response_builder($this->moodleContext);

        // Initialize archetype-aware features (v3.4.0).
        $this->userArchetype = $this->moodleContext->get_user_archetype();
        $this->archetypePriorityTopics = pattern_loader::get_archetype_priority_topics($this->userArchetype);
    }

    /**
     * Process a question and return a response.
     *
     * @param string $question The user's question
     * @return array Response data
     */
    public function respond(string $question): array {
        global $DB;

        // Step 1: Analyze the question.
        $analysis = $this->analyze_question($question);

        // Step 2: Handle special intents first.
        $specialResponse = $this->handle_special_intents($analysis);
        if ($specialResponse !== null) {
            $this->log_and_update_context($question, $specialResponse, $analysis, null);
            return $specialResponse;
        }

        // Step 3: Check if this is a follow-up question.
        if ($this->conversationContext->is_follow_up($question)) {
            $followUpResponse = $this->handle_follow_up($question, $analysis);
            if ($followUpResponse !== null) {
                return $followUpResponse;
            }
        }

        // Step 4: Get filtered rules.
        $rules = $this->get_filtered_rules($analysis);

        // Step 5: Score and rank rules.
        $scoredRules = $this->score_rules($rules, $analysis);

        // Step 6: Get best match.
        $bestMatch = $this->get_best_match($scoredRules);

        // Step 7: Generate response.
        if ($bestMatch && $bestMatch['score'] >= self::MIN_CONFIDENCE * 100) {
            $response = $this->build_response($bestMatch, $analysis);
            $this->log_and_update_context($question, $response, $analysis, $bestMatch['rule']->id);
            return $response;
        }

        // Step 8: Handle no match - provide suggestions.
        return $this->handle_no_match($question, $analysis, $scoredRules);
    }

    /**
     * Analyze the user's question.
     *
     * @param string $question User's question
     * @return array Analysis results
     */
    protected function analyze_question(string $question): array {
        // Get text analysis.
        $textAnalysis = $this->normalizer->analyze($question);

        // Get intent detection.
        $intentAnalysis = $this->intentDetector->detect($question);

        // Get user archetypes.
        $userArchetypes = $this->get_user_archetypes();

        // Get user language.
        $userLang = substr(current_language(), 0, 2);

        return [
            'original' => $question,
            'normalized' => $textAnalysis['normalized'],
            'words' => $textAnalysis['words'],
            'keywords' => $textAnalysis['keywords'],
            'intent' => $intentAnalysis['intent'],
            'topic' => $intentAnalysis['topic'],
            'sentiment' => $intentAnalysis['sentiment'],
            'entities' => $intentAnalysis['entities'],
            'is_question' => $intentAnalysis['is_question'],
            'urgency' => $intentAnalysis['urgency'],
            'archetypes' => $userArchetypes,
            'language' => $userLang,
            'context_topic' => $this->conversationContext->get_topic(),
        ];
    }

    /**
     * Handle special intents (greetings, thanks, complaints).
     *
     * @param array $analysis Question analysis
     * @return array|null Response or null to continue normal processing
     */
    protected function handle_special_intents(array $analysis): ?array {
        $intent = $analysis['intent']['primary'];
        $sentiment = $analysis['sentiment']['primary'];

        switch ($intent) {
            case intent_detector::INTENT_GREETING:
                return $this->build_greeting_response($analysis);

            case intent_detector::INTENT_FAREWELL:
                return $this->build_farewell_response($analysis);

            case intent_detector::INTENT_THANKS:
                return $this->build_thanks_response($analysis);

            case intent_detector::INTENT_AFFIRMATION:
            case intent_detector::INTENT_NEGATION:
                // These are handled by follow-up logic.
                return null;
        }

        // Handle frustrated users specially.
        if ($sentiment === intent_detector::SENTIMENT_FRUSTRATED) {
            return $this->build_empathetic_response($analysis);
        }

        return null;
    }

    /**
     * Build greeting response.
     *
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function build_greeting_response(array $analysis): array {
        $userInfo = $this->moodleContext->get_user_info();
        $hour = (int)date('H');

        // Time-based greeting from language strings.
        if ($hour < 12) {
            $timeGreeting = get_string('greeting_morning', 'local_educambot');
        } else if ($hour < 19) {
            $timeGreeting = get_string('greeting_afternoon', 'local_educambot');
        } else {
            $timeGreeting = get_string('greeting_evening', 'local_educambot');
        }

        // Try to get archetype-specific greeting (v3.4.0).
        $archetypeGreeting = pattern_loader::get_archetype_greeting($this->userArchetype);

        if (!empty($archetypeGreeting)) {
            // Use archetype greeting with time prefix.
            $response = $timeGreeting . ', ' . $userInfo['firstname'] . '. ' . $archetypeGreeting;
        } else {
            // Fallback to generic greeting templates from language file.
            $params = (object)['greeting' => $timeGreeting, 'firstname' => $userInfo['firstname']];
            $greetings = [
                get_string('greeting_response_1', 'local_educambot', $params),
                get_string('greeting_response_2', 'local_educambot', $params),
                get_string('greeting_response_3', 'local_educambot', $params),
                get_string('greeting_response_4', 'local_educambot', $params),
            ];
            $response = $greetings[array_rand($greetings)];
        }

        return [
            'success' => true,
            'response' => $response,
            'ruleid' => null,
            'confidence' => 1.0,
            'type' => 'greeting',
            'options' => $this->get_quick_start_options(),
            'archetype' => $this->userArchetype,
        ];
    }

    /**
     * Build farewell response.
     *
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function build_farewell_response(array $analysis): array {
        // Farewell responses from language file.
        $farewells = [
            get_string('farewell_response_1', 'local_educambot'),
            get_string('farewell_response_2', 'local_educambot'),
            get_string('farewell_response_3', 'local_educambot'),
            get_string('farewell_response_4', 'local_educambot'),
        ];

        // Reset conversation context on farewell.
        $this->conversationContext->reset();

        return [
            'success' => true,
            'response' => $farewells[array_rand($farewells)],
            'ruleid' => null,
            'confidence' => 1.0,
            'type' => 'farewell',
        ];
    }

    /**
     * Build thanks response.
     *
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function build_thanks_response(array $analysis): array {
        // Thanks responses from language file.
        $responses = [
            get_string('thanks_response_1', 'local_educambot'),
            get_string('thanks_response_2', 'local_educambot'),
            get_string('thanks_response_3', 'local_educambot'),
            get_string('thanks_response_4', 'local_educambot'),
        ];

        return [
            'success' => true,
            'response' => $responses[array_rand($responses)],
            'ruleid' => null,
            'confidence' => 1.0,
            'type' => 'thanks',
            'options' => $this->get_follow_up_options(),
        ];
    }

    /**
     * Build empathetic response for frustrated users.
     *
     * @param array $analysis Analysis data
     * @return array|null Response or null to continue processing
     */
    protected function build_empathetic_response(array $analysis): ?array {
        // Only intercept if urgency is high.
        if ($analysis['urgency']['level'] === 'normal') {
            return null;
        }

        $userInfo = $this->moodleContext->get_user_info();
        // Empathetic responses from language file.
        $empathetic = [
            get_string('empathetic_response_1', 'local_educambot', $userInfo['firstname']),
            get_string('empathetic_response_2', 'local_educambot'),
            get_string('empathetic_response_3', 'local_educambot'),
        ];

        return [
            'success' => true,
            'response' => $empathetic[array_rand($empathetic)],
            'ruleid' => null,
            'confidence' => 0.8,
            'type' => 'empathetic',
            'options' => [
                ['text' => get_string('option_task_problem', 'local_educambot'), 'icon' => 'bi-file-earmark-text', 'action' => 'problema con mi tarea'],
                ['text' => get_string('option_access_problem', 'local_educambot'), 'icon' => 'bi-lock', 'action' => 'no puedo acceder'],
                ['text' => get_string('option_contact_teacher', 'local_educambot'), 'icon' => 'bi-person', 'action' => 'contactar profesor'],
            ],
        ];
    }

    /**
     * Handle follow-up questions.
     *
     * @param string $question User's question
     * @param array $analysis Analysis data
     * @return array|null Response or null
     */
    protected function handle_follow_up(string $question, array $analysis): ?array {
        // Handle affirmation/negation.
        if ($analysis['intent']['primary'] === intent_detector::INTENT_AFFIRMATION) {
            return $this->handle_affirmation($analysis);
        }

        if ($analysis['intent']['primary'] === intent_detector::INTENT_NEGATION) {
            return $this->handle_negation($analysis);
        }

        // Try to resolve pronoun references.
        $reference = $this->conversationContext->resolve_reference($question);
        if ($reference) {
            return $this->handle_reference($reference, $analysis);
        }

        return null;
    }

    /**
     * Handle affirmative follow-up.
     *
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function handle_affirmation(array $analysis): array {
        $suggestions = $this->conversationContext->get_follow_up_suggestions();

        return [
            'success' => true,
            'response' => get_string('affirmation_response', 'local_educambot'),
            'ruleid' => null,
            'confidence' => 1.0,
            'type' => 'follow_up',
            'options' => array_map(function($s) {
                return ['text' => $s, 'action' => $s];
            }, array_slice($suggestions, 0, 3)),
        ];
    }

    /**
     * Handle negative follow-up.
     *
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function handle_negation(array $analysis): array {
        return [
            'success' => true,
            'response' => get_string('negation_response', 'local_educambot'),
            'ruleid' => null,
            'confidence' => 1.0,
            'type' => 'follow_up',
        ];
    }

    /**
     * Handle pronoun reference resolution.
     *
     * @param array $reference Resolved reference
     * @param array $analysis Analysis data
     * @return array|null Response
     */
    protected function handle_reference(array $reference, array $analysis): ?array {
        // This can be extended to handle specific entity types.
        return null;
    }

    /**
     * Get filtered rules based on user context.
     *
     * @param array $analysis Analysis data
     * @return array Filtered rules
     */
    protected function get_filtered_rules(array $analysis): array {
        global $DB;

        $userLang = $analysis['language'];
        $userArchetypes = $analysis['archetypes'];

        // Get all enabled rules, ordered by feedback score.
        $sql = "SELECT r.*,
                       (r.helpfulcount - r.nothelpfulcount) as feedback_score,
                       c.enabled as category_enabled
                FROM {local_educambot_rule} r
                LEFT JOIN {local_educambot_category} c ON r.categoryid = c.id
                WHERE r.enabled = 1
                AND (c.enabled = 1 OR c.enabled IS NULL)
                ORDER BY feedback_score DESC, r.id ASC";

        $rules = $DB->get_records_sql($sql);

        // Filter by context.
        return $this->filter_rules_by_context($rules, $userArchetypes, $userLang);
    }

    /**
     * Filter rules by archetype, course, and language.
     *
     * @param array $rules All rules
     * @param array $userArchetypes User's archetypes
     * @param string $userLang User's language
     * @return array Filtered rules
     */
    protected function filter_rules_by_context(array $rules, array $userArchetypes, string $userLang): array {
        $autolang = get_config('local_educambot', 'autolang');
        $filtered = [];
        $seenPatterns = [];

        foreach ($rules as $rule) {
            // Check archetype filter.
            if (!empty($rule->roles)) {
                $ruleArchetypes = array_map('trim', explode(',', $rule->roles));
                if (!array_intersect($userArchetypes, $ruleArchetypes)) {
                    continue;
                }
            }

            // Check course filter.
            if (!empty($rule->courses)) {
                $ruleCourses = array_map('trim', explode(',', $rule->courses));
                if (!in_array($this->courseid, $ruleCourses) && !in_array('*', $ruleCourses)) {
                    continue;
                }
            }

            // Check required context.
            if (!empty($rule->requiredcontext)) {
                $contextType = $this->moodleContext->get_context_type();
                if ($rule->requiredcontext !== $contextType) {
                    continue;
                }
            }

            // Handle language preference.
            if ($autolang) {
                $patternKey = $rule->langparent ?: $rule->id;

                if (!isset($seenPatterns[$patternKey])) {
                    $seenPatterns[$patternKey] = $rule;
                } else {
                    $existingRule = $seenPatterns[$patternKey];
                    if ($rule->lang === $userLang && $existingRule->lang !== $userLang) {
                        $seenPatterns[$patternKey] = $rule;
                    }
                }
            } else {
                $filtered[$rule->id] = $rule;
            }
        }

        return $autolang ? array_values($seenPatterns) : $filtered;
    }

    /**
     * Score rules against the question.
     *
     * @param array $rules Filtered rules
     * @param array $analysis Question analysis
     * @return array Scored rules sorted by score
     */
    protected function score_rules(array $rules, array $analysis): array {
        $scored = [];

        foreach ($rules as $rule) {
            $score = $this->calculate_match_score($rule, $analysis);
            if ($score > 0) {
                $scored[] = [
                    'rule' => $rule,
                    'score' => $score,
                    'breakdown' => $this->get_score_breakdown($rule, $analysis),
                ];
            }
        }

        // Sort by score descending.
        usort($scored, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        return $scored;
    }

    /**
     * Calculate match score for a rule.
     *
     * @param object $rule Rule record
     * @param array $analysis Question analysis
     * @return int Match score
     */
    protected function calculate_match_score(object $rule, array $analysis): int {
        $score = 0;
        $question = $analysis['normalized'];
        $questionWords = $analysis['words'];
        $questionKeywords = $analysis['keywords'];

        // Normalize pattern.
        $pattern = $this->normalizer->normalize($rule->pattern);
        $patternWords = array_filter(explode(' ', $pattern));

        // 1. Exact match (highest score).
        if ($pattern === $question) {
            return self::SCORE_WEIGHTS['exact_match'];
        }

        // 2. Pattern contains question or vice versa.
        if (mb_strlen($pattern) > 3 && mb_strlen($question) > 3) {
            if (mb_strpos($question, $pattern) !== false) {
                $score += self::SCORE_WEIGHTS['pattern_contains'];
            } else if (mb_strpos($pattern, $question) !== false) {
                $score += self::SCORE_WEIGHTS['question_contains'];
            }
        }

        // 3. Word overlap (Jaccard-like).
        $commonWords = array_intersect($questionWords, $patternWords);
        if (count($patternWords) > 0) {
            $overlapRatio = count($commonWords) / count($patternWords);
            $score += (int)(self::SCORE_WEIGHTS['word_overlap'] * $overlapRatio);
        }

        // 4. Keyword matching from rule keywords (improved).
        if (!empty($rule->keywords)) {
            $keywords = array_filter(array_map('trim', explode("\n", $rule->keywords)));
            foreach ($keywords as $keyword) {
                $normalizedKeyword = $this->normalizer->normalize($keyword);
                if (!empty($normalizedKeyword)) {
                    // Check for multi-word keywords (higher score).
                    $keywordWordCount = count(explode(' ', $normalizedKeyword));
                    if ($keywordWordCount > 1 && mb_strpos($question, $normalizedKeyword) !== false) {
                        $score += self::SCORE_WEIGHTS['multi_word_keyword'];
                    } else if (mb_strpos($question, $normalizedKeyword) !== false) {
                        // Direct single word match.
                        $score += self::SCORE_WEIGHTS['keyword_match'];
                    } else {
                        // Synonym match.
                        $keywordResult = $this->normalizer->contains_keywords($question, [$normalizedKeyword], true);
                        if ($keywordResult['found']) {
                            $score += self::SCORE_WEIGHTS['synonym_match'];
                        }
                    }
                }
            }
        }

        // 5. N-gram matching for partial phrase matching.
        $ngramScore = $this->calculate_ngram_score($question, $pattern);
        if ($ngramScore > 0.3) {
            $score += (int)(self::SCORE_WEIGHTS['ngram_match'] * $ngramScore);
        }

        // 6. Levenshtein distance for typo tolerance.
        if (mb_strlen($pattern) <= 100 && mb_strlen($question) <= 100) {
            $similarity = $this->normalizer->calculate_similarity($question, $pattern);
            if ($similarity > 0.5) {
                $score += (int)(self::SCORE_WEIGHTS['levenshtein'] * $similarity);
            }
        }

        // 7. Context boost - if matches current conversation topic.
        if (!empty($analysis['context_topic']) && !empty($rule->tags)) {
            $ruleTags = array_map('trim', explode(',', mb_strtolower($rule->tags)));
            if (in_array($analysis['context_topic'], $ruleTags)) {
                $score += self::SCORE_WEIGHTS['context_boost'];
            }
        }

        // 8. Topic match bonus.
        if (!empty($rule->tags) && isset($analysis['topic']['primary'])) {
            $ruleTags = array_map('trim', explode(',', mb_strtolower($rule->tags)));
            if (in_array($analysis['topic']['primary'], $ruleTags)) {
                $score += self::SCORE_WEIGHTS['topic_match'];
            }
        }

        // 9. Intent match bonus.
        if (!empty($rule->tags) && isset($analysis['intent']['primary'])) {
            $ruleTags = array_map('trim', explode(',', mb_strtolower($rule->tags)));
            if (in_array($analysis['intent']['primary'], $ruleTags)) {
                $score += self::SCORE_WEIGHTS['intent_match'];
            }
        }

        // 10. Feedback boost (rules with positive feedback score higher).
        if (isset($rule->feedback_score) && $rule->feedback_score > 0) {
            $boost = min(self::SCORE_WEIGHTS['feedback_boost'], $rule->feedback_score);
            $score += $boost;
        }

        // 11. Archetype priority boost (v3.4.0) - boost rules matching user's priority topics.
        if (!empty($this->archetypePriorityTopics) && !empty($rule->tags)) {
            $ruleTags = array_map('trim', explode(',', mb_strtolower($rule->tags)));
            $priorityMatches = array_intersect($ruleTags, $this->archetypePriorityTopics);
            if (!empty($priorityMatches)) {
                // Higher boost for more priority matches.
                $boost = min(self::SCORE_WEIGHTS['archetype_priority'], count($priorityMatches) * 4);
                $score += $boost;
            }
        }

        return $score;
    }

    /**
     * Calculate n-gram similarity score between question and pattern.
     *
     * @param string $question Normalized question
     * @param string $pattern Normalized pattern
     * @param int $n N-gram size (default 2 for bigrams)
     * @return float Similarity score between 0 and 1
     */
    protected function calculate_ngram_score(string $question, string $pattern, int $n = 2): float {
        if (mb_strlen($question) < $n || mb_strlen($pattern) < $n) {
            return 0.0;
        }

        $questionNgrams = $this->get_ngrams($question, $n);
        $patternNgrams = $this->get_ngrams($pattern, $n);

        if (empty($questionNgrams) || empty($patternNgrams)) {
            return 0.0;
        }

        $intersection = array_intersect($questionNgrams, $patternNgrams);
        $union = array_unique(array_merge($questionNgrams, $patternNgrams));

        return count($intersection) / count($union);
    }

    /**
     * Extract n-grams from text.
     *
     * @param string $text Text to extract n-grams from
     * @param int $n N-gram size
     * @return array Array of n-grams
     */
    protected function get_ngrams(string $text, int $n): array {
        $ngrams = [];
        $length = mb_strlen($text);

        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = mb_substr($text, $i, $n);
        }

        return $ngrams;
    }

    /**
     * Get detailed score breakdown for debugging.
     *
     * @param object $rule Rule
     * @param array $analysis Analysis
     * @return array Score breakdown
     */
    protected function get_score_breakdown(object $rule, array $analysis): array {
        return [
            'pattern' => $rule->pattern,
            'normalized_pattern' => $this->normalizer->normalize($rule->pattern),
            'question' => $analysis['normalized'],
        ];
    }

    /**
     * Get best match from scored rules.
     *
     * @param array $scoredRules Scored rules
     * @return array|null Best match or null
     */
    protected function get_best_match(array $scoredRules): ?array {
        return !empty($scoredRules) ? $scoredRules[0] : null;
    }

    /**
     * Build response from matched rule.
     *
     * @param array $match Match data
     * @param array $analysis Analysis data
     * @return array Response
     */
    protected function build_response(array $match, array $analysis): array {
        global $DB;

        $rule = $match['rule'];
        $response = $rule->response;

        // Process dynamic placeholders.
        if ($rule->dynamicresponse || response_builder::has_placeholders($response)) {
            $response = $this->responseBuilder->build_response($response);
        }

        // Get options for this rule.
        $options = [];
        if ($rule->showoptions) {
            // Check if rule requests role-specific options (v3.4.0).
            $useRoleOptions = !empty($rule->useroleoptions);

            if ($useRoleOptions) {
                // Get archetype-specific menu options.
                $roleOptions = pattern_loader::get_archetype_menu_options($this->userArchetype);
                if (!empty($roleOptions)) {
                    foreach ($roleOptions as $opt) {
                        $options[] = [
                            'text' => $opt['text'] ?? '',
                            'icon' => $opt['icon'] ?? 'bi-chevron-right',
                            'action' => $opt['action'] ?? '',
                        ];
                    }
                }
            }

            // If no role options or not using role options, get from database.
            if (empty($options)) {
                $dbOptions = $DB->get_records('local_educambot_option', [
                    'ruleid' => $rule->id,
                    'enabled' => 1,
                ], 'sortorder ASC');

                foreach ($dbOptions as $opt) {
                    $option = [
                        'text' => $opt->text,
                        'icon' => $opt->icon,
                    ];

                    if ($opt->targetruleid) {
                        $targetRule = $DB->get_record('local_educambot_rule', ['id' => $opt->targetruleid]);
                        if ($targetRule) {
                            $option['targetpattern'] = $targetRule->pattern;
                        }
                    }

                    $options[] = $option;
                }
            }
        }

        $confidence = min(1.0, $match['score'] / 100);

        return [
            'success' => true,
            'response' => $response,
            'ruleid' => $rule->id,
            'confidence' => $confidence,
            'type' => 'matched',
            'options' => $options,
            'archetype' => $this->userArchetype,  // Include archetype in response (v3.4.0).
        ];
    }

    /**
     * Handle case when no rule matches.
     *
     * @param string $question Original question
     * @param array $analysis Analysis data
     * @param array $scoredRules Scored rules (may have low-confidence matches)
     * @return array Response
     */
    protected function handle_no_match(string $question, array $analysis, array $scoredRules): array {
        // Try to find similar questions from history.
        $suggestions = $this->find_similar_questions($question);

        // Prepare fallback response from language file.
        $fallbackResponses = [
            get_string('fallback_response_1', 'local_educambot'),
            get_string('fallback_response_2', 'local_educambot'),
            get_string('fallback_response_3', 'local_educambot'),
        ];

        $response = $fallbackResponses[array_rand($fallbackResponses)];

        // Add suggestions if available.
        $options = [];
        if (!empty($suggestions)) {
            $response .= "\n\n" . get_string('fallback_suggestions', 'local_educambot');
            foreach (array_slice($suggestions, 0, 3) as $suggestion) {
                $options[] = [
                    'text' => $suggestion['pattern'],
                    'action' => $suggestion['pattern'],
                ];
            }
        } else {
            // Provide default suggestions based on topic.
            $options = $this->get_topic_suggestions($analysis['topic']['primary'] ?? 'general');
        }

        // Log the unmatched question.
        $this->log_and_update_context($question, ['response' => $response, 'confidence' => 0], $analysis, null);

        return [
            'success' => true,
            'response' => $response,
            'ruleid' => null,
            'confidence' => 0,
            'type' => 'no_match',
            'options' => $options,
        ];
    }

    /**
     * Find similar questions that have been successfully answered.
     *
     * @param string $question User's question
     * @param int $limit Maximum results
     * @return array Similar questions with their rules
     */
    protected function find_similar_questions(string $question, int $limit = 5): array {
        global $DB;

        // Get rules with highest match success.
        $sql = "SELECT r.id, r.pattern,
                       (r.helpfulcount - r.nothelpfulcount) as score
                FROM {local_educambot_rule} r
                WHERE r.enabled = 1
                AND r.helpfulcount > 0
                ORDER BY score DESC
                LIMIT 20";

        $rules = $DB->get_records_sql($sql);

        if (empty($rules)) {
            return [];
        }

        // Use normalizer to find similar patterns.
        $patterns = [];
        foreach ($rules as $rule) {
            $patterns[$rule->id] = $rule->pattern;
        }

        $matches = $this->normalizer->find_best_matches($question, $patterns, 0.2, $limit);

        // Return matched rules.
        $results = [];
        foreach ($matches as $match) {
            if (isset($rules[$match['key']])) {
                $results[] = [
                    'id' => $rules[$match['key']]->id,
                    'pattern' => $rules[$match['key']]->pattern,
                    'similarity' => $match['similarity'],
                ];
            }
        }

        return $results;
    }

    /**
     * Get topic-based suggestions.
     *
     * @param string $topic Detected topic
     * @return array Suggestions
     */
    protected function get_topic_suggestions(string $topic): array {
        $suggestions = [
            intent_detector::TOPIC_ASSIGNMENTS => [
                ['text' => get_string('suggestion_pending_tasks', 'local_educambot'), 'icon' => 'bi-file-earmark-text', 'action' => 'tareas pendientes'],
                ['text' => get_string('suggestion_next_deadline', 'local_educambot'), 'icon' => 'bi-calendar', 'action' => 'fecha proxima tarea'],
            ],
            intent_detector::TOPIC_GRADES => [
                ['text' => get_string('suggestion_current_grade', 'local_educambot'), 'icon' => 'bi-trophy', 'action' => 'mi calificacion'],
                ['text' => get_string('suggestion_my_grades', 'local_educambot'), 'icon' => 'bi-graph-up', 'action' => 'mis notas'],
            ],
            intent_detector::TOPIC_CALENDAR => [
                ['text' => get_string('suggestion_week_events', 'local_educambot'), 'icon' => 'bi-calendar-event', 'action' => 'eventos semana'],
                ['text' => get_string('suggestion_next_exam', 'local_educambot'), 'icon' => 'bi-clock', 'action' => 'proximo examen'],
            ],
            intent_detector::TOPIC_COURSE => [
                ['text' => get_string('suggestion_who_teacher', 'local_educambot'), 'icon' => 'bi-person', 'action' => 'quien es el profesor'],
                ['text' => get_string('suggestion_course_info', 'local_educambot'), 'icon' => 'bi-info-circle', 'action' => 'informacion del curso'],
            ],
        ];

        return $suggestions[$topic] ?? [
            ['text' => get_string('suggestion_my_tasks', 'local_educambot'), 'icon' => 'bi-list-task', 'action' => 'mis tareas'],
            ['text' => get_string('suggestion_my_calendar', 'local_educambot'), 'icon' => 'bi-calendar3', 'action' => 'mi calendario'],
            ['text' => get_string('suggestion_my_grades_alt', 'local_educambot'), 'icon' => 'bi-award', 'action' => 'mis calificaciones'],
        ];
    }

    /**
     * Get quick start options for greetings.
     *
     * Uses archetype-specific quick actions if available (v3.4.0).
     *
     * @return array Options
     */
    protected function get_quick_start_options(): array {
        // Try to get archetype-specific quick actions (v3.4.0).
        $quickActions = pattern_loader::get_archetype_quick_actions($this->userArchetype);

        if (!empty($quickActions)) {
            return array_map(function($action) {
                return [
                    'text' => $action['text'] ?? '',
                    'icon' => $action['icon'] ?? 'bi-chevron-right',
                    'action' => $action['action'] ?? '',
                ];
            }, array_slice($quickActions, 0, 4));  // Limit to 4 quick actions.
        }

        // Fallback to default options.
        return [
            ['text' => get_string('option_view_tasks', 'local_educambot'), 'icon' => 'bi-list-check', 'action' => 'mis tareas pendientes'],
            ['text' => get_string('option_view_grades', 'local_educambot'), 'icon' => 'bi-trophy', 'action' => 'mis calificaciones'],
            ['text' => get_string('option_view_calendar', 'local_educambot'), 'icon' => 'bi-calendar-event', 'action' => 'mi calendario'],
        ];
    }

    /**
     * Get follow-up options.
     *
     * @return array Options
     */
    protected function get_follow_up_options(): array {
        $suggestions = $this->conversationContext->get_follow_up_suggestions();
        return array_map(function($s) {
            return ['text' => $s, 'action' => $s];
        }, array_slice($suggestions, 0, 3));
    }

    /**
     * Log conversation and update context.
     *
     * @param string $question User's question
     * @param array $response Response data
     * @param array $analysis Analysis data
     * @param int|null $ruleid Matched rule ID
     */
    protected function log_and_update_context(string $question, array $response, array $analysis, ?int $ruleid): void {
        global $DB;

        // Log to database.
        try {
            $log = new \stdClass();
            $log->userid = $this->userid;
            $log->question = $question;
            $log->response = $response['response'] ?? '';
            $log->ruleid = $ruleid;
            $log->confidence = $response['confidence'] ?? 0;
            $log->matched = $ruleid ? 1 : 0;
            $log->timecreated = time();

            $DB->insert_record('local_educambot_log', $log);
        } catch (\Exception $e) {
            debugging('Could not log conversation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Update conversation context.
        $this->conversationContext->add_turn($question, $response['response'] ?? null, $analysis, $ruleid);
    }

    /**
     * Get user's role archetypes.
     *
     * @return array Array of role archetypes
     */
    protected function get_user_archetypes(): array {
        global $DB;

        $archetypes = [];

        // Check for site administrator.
        if (is_siteadmin($this->userid)) {
            $archetypes[] = 'manager';
        }

        try {
            $syscontext = \context_system::instance();

            // Get course context if not site.
            if ($this->courseid > SITEID) {
                $coursecontext = \context_course::instance($this->courseid, IGNORE_MISSING);
                if ($coursecontext) {
                    $roles = get_user_roles($coursecontext, $this->userid, true);
                    foreach ($roles as $role) {
                        $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                        if ($rolerecord && !empty($rolerecord->archetype)) {
                            $archetypes[] = $rolerecord->archetype;
                        }
                    }
                }
            }

            // Check system-level roles.
            $sysroles = get_user_roles($syscontext, $this->userid, true);
            foreach ($sysroles as $role) {
                $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                if ($rolerecord && !empty($rolerecord->archetype)) {
                    $archetypes[] = $rolerecord->archetype;
                }
            }
        } catch (\Exception $e) {
            debugging('Could not get user roles: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Check if guest user.
        if (isguestuser($this->userid)) {
            $archetypes[] = 'guest';
        }

        // Add 'user' for authenticated users without specific archetypes.
        if (empty($archetypes) && isloggedin() && !isguestuser($this->userid)) {
            $archetypes[] = 'user';
        }

        return array_unique($archetypes);
    }

    /**
     * Get popular questions for mascot tooltip.
     *
     * @param int $limit Maximum number of questions
     * @return array Popular questions
     */
    public function get_popular_questions(int $limit = 5): array {
        global $DB;

        $sql = "SELECT r.id, r.pattern,
                       (r.helpfulcount + 1) as popularity
                FROM {local_educambot_rule} r
                WHERE r.enabled = 1
                AND r.helpfulcount > 0
                ORDER BY popularity DESC, r.id DESC
                LIMIT " . intval($limit);

        return array_values($DB->get_records_sql($sql));
    }

    /**
     * Get similar questions for when bot is confused.
     *
     * @param string $question User's question
     * @param int $limit Maximum results
     * @return array Similar questions
     */
    public function get_similar_questions(string $question, int $limit = 3): array {
        return $this->find_similar_questions($question, $limit);
    }
}
