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
 * Intent detector for educambot - classifies user intent from questions.
 *
 * All patterns are loaded from database (local_educambot_pattern table):
 * - intents: Intent patterns (greeting, farewell, query, etc.)
 * - topics: Topic patterns (assignments, grades, calendar, etc.)
 * - sentiments: Sentiment patterns (positive, negative, frustrated, etc.)
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Intent detector class - classifies user intent from external JSON data.
 */
class intent_detector {

    // Intent type constants.
    public const INTENT_GREETING = 'greeting';
    public const INTENT_FAREWELL = 'farewell';
    public const INTENT_THANKS = 'thanks';
    public const INTENT_QUERY = 'query';
    public const INTENT_ACTION = 'action';
    public const INTENT_NAVIGATION = 'navigation';
    public const INTENT_COMPLAINT = 'complaint';
    public const INTENT_AFFIRMATION = 'affirmation';
    public const INTENT_NEGATION = 'negation';
    public const INTENT_CLARIFICATION = 'clarification';
    public const INTENT_REPEAT = 'repeat';
    public const INTENT_UNKNOWN = 'unknown';

    // Topic type constants.
    public const TOPIC_ASSIGNMENTS = 'assignments';
    public const TOPIC_GRADES = 'grades';
    public const TOPIC_CALENDAR = 'calendar';
    public const TOPIC_MESSAGES = 'messages';
    public const TOPIC_COURSE = 'course';
    public const TOPIC_TEACHER = 'teacher';
    public const TOPIC_PROFILE = 'profile';
    public const TOPIC_HELP = 'help';
    public const TOPIC_NAVIGATION = 'navigation';
    public const TOPIC_RESOURCES = 'resources';
    public const TOPIC_FORUM = 'forum';
    public const TOPIC_QUIZ = 'quiz';
    public const TOPIC_PROGRESS = 'progress';
    public const TOPIC_ENROLLMENT = 'enrollment';
    public const TOPIC_TECHNICAL = 'technical';
    public const TOPIC_GENERAL = 'general';

    // Sentiment type constants.
    public const SENTIMENT_POSITIVE = 'positive';
    public const SENTIMENT_NEGATIVE = 'negative';
    public const SENTIMENT_NEUTRAL = 'neutral';
    public const SENTIMENT_FRUSTRATED = 'frustrated';
    public const SENTIMENT_CONFUSED = 'confused';
    public const SENTIMENT_ANXIOUS = 'anxious';

    /** @var text_normalizer Text normalizer instance */
    private $normalizer;

    /** @var array Loaded intent patterns */
    private $intentPatterns = [];

    /** @var array Loaded topic patterns */
    private $topicPatterns = [];

    /** @var array Loaded sentiment patterns */
    private $sentimentPatterns = [];

    /** @var array Urgency keywords */
    private $urgencyKeywords = [];

    /** @var bool Whether data has been loaded */
    private static $dataLoaded = false;

    /** @var array Cached intent patterns */
    private static $cachedIntents = [];

    /** @var array Cached topic patterns */
    private static $cachedTopics = [];

    /** @var array Cached sentiment patterns */
    private static $cachedSentiments = [];

    /** @var array Cached urgency keywords */
    private static $cachedUrgency = [];

    /**
     * Constructor.
     *
     * @param text_normalizer|null $normalizer Optional normalizer instance
     */
    public function __construct(?text_normalizer $normalizer = null) {
        $this->normalizer = $normalizer ?? new text_normalizer();
        $this->load_data();
    }

    /**
     * Load patterns from database via pattern_loader.
     */
    private function load_data(): void {
        // Use cached data if available.
        if (self::$dataLoaded) {
            $this->intentPatterns = self::$cachedIntents;
            $this->topicPatterns = self::$cachedTopics;
            $this->sentimentPatterns = self::$cachedSentiments;
            $this->urgencyKeywords = self::$cachedUrgency;
            return;
        }

        // Load from database via pattern_loader.
        pattern_loader::load();

        // Get patterns from loader.
        $this->intentPatterns = pattern_loader::get_intents();
        $this->topicPatterns = pattern_loader::get_topics();
        $this->sentimentPatterns = pattern_loader::get_sentiments();
        $this->urgencyKeywords = pattern_loader::get_urgency_keywords();

        // Cache the loaded data.
        self::$cachedIntents = $this->intentPatterns;
        self::$cachedTopics = $this->topicPatterns;
        self::$cachedSentiments = $this->sentimentPatterns;
        self::$cachedUrgency = $this->urgencyKeywords;
        self::$dataLoaded = true;
    }

    /**
     * Detect intent from user input.
     *
     * @param string $text User input text
     * @return array Intent detection results
     */
    public function detect(string $text): array {
        $normalizedText = $this->normalizer->normalize($text);

        return [
            'intent' => $this->detect_intent($text, $normalizedText),
            'topic' => $this->detect_topic($normalizedText),
            'sentiment' => $this->detect_sentiment($normalizedText),
            'entities' => $this->normalizer->extract_entities($text),
            'is_question' => $this->is_question($text),
            'urgency' => $this->detect_urgency($normalizedText),
        ];
    }

    /**
     * Detect primary intent.
     *
     * @param string $originalText Original text
     * @param string $normalizedText Normalized text
     * @return array Intent with confidence
     */
    private function detect_intent(string $originalText, string $normalizedText): array {
        $bestIntent = self::INTENT_UNKNOWN;
        $bestScore = 0;
        $allMatches = [];

        foreach ($this->intentPatterns as $intent => $config) {
            $score = 0;
            $matched = false;
            $weight = $config['weight'] ?? 1.0;

            // Check regex patterns.
            if (!empty($config['patterns'])) {
                foreach ($config['patterns'] as $pattern) {
                    $regex = '/' . $pattern . '/ui';
                    if (@preg_match($regex, $originalText) || @preg_match($regex, $normalizedText)) {
                        $matched = true;
                        $score = max($score, $weight);
                        break;
                    }
                }
            }

            // Check keywords.
            if (!$matched && !empty($config['keywords'])) {
                foreach ($config['keywords'] as $keyword) {
                    if (mb_stripos($normalizedText, $keyword) !== false) {
                        $matched = true;
                        $score = max($score, $weight * 0.8);
                        break;
                    }
                }
            }

            if ($matched) {
                $allMatches[$intent] = $score;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestIntent = $intent;
                }
            }
        }

        return [
            'primary' => $bestIntent,
            'confidence' => $bestScore,
            'all_matches' => $allMatches,
        ];
    }

    /**
     * Detect topic from text.
     *
     * @param string $normalizedText Normalized text
     * @return array Topic with confidence
     */
    private function detect_topic(string $normalizedText): array {
        $scores = [];
        $words = explode(' ', $normalizedText);

        foreach ($this->topicPatterns as $topic => $config) {
            $matchCount = 0;
            $matchedKeywords = [];
            $keywords = $config['keywords'] ?? [];
            $priority = $config['priority'] ?? 1.0;

            foreach ($keywords as $keyword) {
                // Direct match.
                if (mb_stripos($normalizedText, $keyword) !== false) {
                    $matchCount++;
                    $matchedKeywords[] = $keyword;
                } else {
                    // Check synonyms.
                    foreach ($words as $word) {
                        if (mb_strlen($word) > 2 && $this->normalizer->are_synonyms($word, $keyword)) {
                            $matchCount++;
                            $matchedKeywords[] = $keyword;
                            break;
                        }
                    }
                }
            }

            if ($matchCount > 0) {
                $baseScore = $matchCount / max(count($keywords), 1);
                $scores[$topic] = [
                    'count' => $matchCount,
                    'score' => $baseScore * $priority,
                    'keywords' => array_unique($matchedKeywords),
                ];
            }
        }

        // Sort by score descending.
        uasort($scores, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $topics = array_keys($scores);
        $primaryTopic = !empty($topics) ? $topics[0] : self::TOPIC_GENERAL;
        $confidence = !empty($scores) ? reset($scores)['score'] : 0;

        return [
            'primary' => $primaryTopic,
            'confidence' => min(1.0, $confidence * 2),
            'all_topics' => $scores,
        ];
    }

    /**
     * Detect sentiment from text.
     *
     * @param string $normalizedText Normalized text
     * @return array Sentiment with confidence
     */
    private function detect_sentiment(string $normalizedText): array {
        $scores = [];

        foreach ($this->sentimentPatterns as $sentiment => $config) {
            $keywords = $config['keywords'] ?? [];
            $weight = $config['weight'] ?? 1.0;

            if (empty($keywords)) {
                continue;
            }

            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (mb_stripos($normalizedText, $keyword) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $scores[$sentiment] = ($matchCount / count($keywords)) * $weight;
            }
        }

        // Determine primary sentiment.
        if (empty($scores)) {
            return [
                'primary' => self::SENTIMENT_NEUTRAL,
                'confidence' => 0.5,
                'all_sentiments' => [],
            ];
        }

        arsort($scores);
        $primarySentiment = key($scores);
        $confidence = reset($scores);

        return [
            'primary' => $primarySentiment,
            'confidence' => min(1.0, $confidence * 3),
            'all_sentiments' => $scores,
        ];
    }

    /**
     * Check if text is a question.
     *
     * @param string $text Text to check
     * @return bool True if question
     */
    private function is_question(string $text): bool {
        // Check for question marks.
        if (preg_match('/[¿\?]/', $text)) {
            return true;
        }

        // Check for interrogative words.
        $interrogatives = '/(qué|que|cómo|como|dónde|donde|cuándo|cuando|por qué|porque|quién|quien|cuál|cual|cuánto|cuanto)/ui';
        return preg_match($interrogatives, $text) === 1;
    }

    /**
     * Detect urgency level.
     *
     * @param string $normalizedText Normalized text
     * @return array Urgency level
     */
    private function detect_urgency(string $normalizedText): array {
        $matchCount = 0;

        foreach ($this->urgencyKeywords as $keyword) {
            if (mb_stripos($normalizedText, $keyword) !== false) {
                $matchCount++;
            }
        }

        $level = 'normal';
        if ($matchCount >= 3) {
            $level = 'critical';
        } else if ($matchCount >= 2) {
            $level = 'high';
        } else if ($matchCount >= 1) {
            $level = 'medium';
        }

        return [
            'level' => $level,
            'score' => min(1.0, $matchCount / 3),
        ];
    }

    /**
     * Get a response strategy based on detected intent.
     *
     * @param array $detection Detection results from detect()
     * @return string Suggested response approach
     */
    public function get_response_strategy(array $detection): string {
        $intent = $detection['intent']['primary'];
        $sentiment = $detection['sentiment']['primary'];

        // Handle negative sentiments first.
        if (in_array($sentiment, [self::SENTIMENT_FRUSTRATED, self::SENTIMENT_NEGATIVE, self::SENTIMENT_ANXIOUS])) {
            return 'empathetic';
        }

        switch ($intent) {
            case self::INTENT_GREETING:
                return 'greeting';
            case self::INTENT_FAREWELL:
                return 'farewell';
            case self::INTENT_THANKS:
                return 'appreciation';
            case self::INTENT_COMPLAINT:
                return 'supportive';
            case self::INTENT_AFFIRMATION:
            case self::INTENT_NEGATION:
                return 'follow_up';
            case self::INTENT_CLARIFICATION:
            case self::INTENT_REPEAT:
                return 'clarify';
            default:
                return 'informative';
        }
    }

    /**
     * Get all loaded topic names.
     *
     * @return array Topic names
     */
    public function get_available_topics(): array {
        return array_keys($this->topicPatterns);
    }

    /**
     * Get all loaded intent names.
     *
     * @return array Intent names
     */
    public function get_available_intents(): array {
        return array_keys($this->intentPatterns);
    }

    /**
     * Get keywords for a specific topic.
     *
     * @param string $topic Topic name
     * @return array Keywords or empty array
     */
    public function get_topic_keywords(string $topic): array {
        return $this->topicPatterns[$topic]['keywords'] ?? [];
    }

    /**
     * Clear cached data (useful for testing or after JSON updates).
     */
    public static function clear_cache(): void {
        self::$dataLoaded = false;
        self::$cachedIntents = [];
        self::$cachedTopics = [];
        self::$cachedSentiments = [];
        self::$cachedUrgency = [];
    }
}
