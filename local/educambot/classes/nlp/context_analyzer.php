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
 * Context analyzer for deep understanding of user questions and environment.
 *
 * Analyzes multiple contextual dimensions:
 * - Temporal context (time references, deadlines)
 * - User context (role, history, preferences)
 * - Topic context (subject matter, domain)
 * - Sentiment context (emotion, urgency)
 * - Technical context (platform, device, browser)
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use local_educambot\local\text_helper;
use core_text;

/**
 * Analyzes contextual information from user questions and environment.
 */
class context_analyzer {
    /** Context type: Temporal */
    public const CONTEXT_TEMPORAL = 'temporal';

    /** Context type: User */
    public const CONTEXT_USER = 'user';

    /** Context type: Topic */
    public const CONTEXT_TOPIC = 'topic';

    /** Context type: Sentiment */
    public const CONTEXT_SENTIMENT = 'sentiment';

    /** Context type: Technical */
    public const CONTEXT_TECHNICAL = 'technical';

    /** Urgency level: Low */
    public const URGENCY_LOW = 'low';

    /** Urgency level: Medium */
    public const URGENCY_MEDIUM = 'medium';

    /** Urgency level: High */
    public const URGENCY_HIGH = 'high';

    /** Urgency level: Critical */
    public const URGENCY_CRITICAL = 'critical';

    /** @var array<string,array> Topic patterns */
    protected array $topicpatterns = [];

    /** @var array<string,array> Sentiment patterns */
    protected array $sentimentpatterns = [];

    /** @var array<string,array> Temporal patterns */
    protected array $temporalpatterns = [];

    /** @var bool Whether analyzer has been initialized */
    protected bool $initialized = false;

    /**
     * Constructor.
     *
     * @param bool $autoinit Auto-initialize patterns
     */
    public function __construct(bool $autoinit = true) {
        if ($autoinit) {
            $this->initialize();
        }
    }

    /**
     * Analyzes all contextual dimensions of a question.
     *
     * @param string $question User question
     * @param array $nlpanalysis NLP analysis from pipeline
     * @param array $usercontext User context (role, history, etc.)
     * @param array $pagecontext Page context (URL, course, etc.)
     * @return array{
     *     temporal:array,
     *     user:array,
     *     topic:array,
     *     sentiment:array,
     *     technical:array,
     *     urgency:string,
     *     complexity:float
     * }
     */
    public function analyze(
        string $question,
        array $nlpanalysis = [],
        array $usercontext = [],
        array $pagecontext = []
    ): array {
        $this->ensure_initialized();

        $question = trim($question);
        if ($question === '') {
            return $this->empty_context_result();
        }

        $normalized = core_text::strtolower($question);
        $tokens = $nlpanalysis['tokens'] ?? text_helper::tokenize($question);
        $entities = $nlpanalysis['entities'] ?? [];

        return [
            'temporal' => $this->analyze_temporal($question, $normalized, $tokens, $entities),
            'user' => $this->analyze_user($question, $normalized, $usercontext),
            'topic' => $this->analyze_topic($question, $normalized, $tokens),
            'sentiment' => $this->analyze_sentiment($question, $normalized, $tokens),
            'technical' => $this->analyze_technical($question, $pagecontext),
            'urgency' => $this->detect_urgency($question, $normalized, $tokens),
            'complexity' => $this->calculate_complexity($question, $tokens),
        ];
    }

    /**
     * Analyzes temporal context (time references, deadlines).
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @param array $tokens Tokens
     * @param array $entities Entities
     * @return array Temporal context
     */
    protected function analyze_temporal(string $question, string $normalized, array $tokens, array $entities): array {
        $context = [
            'has_timeref' => false,
            'timeframe' => null,
            'deadline' => false,
            'dates' => [],
            'temporal_words' => [],
        ];

        // Extract dates from entities.
        if (!empty($entities['dates'])) {
            $context['has_timeref'] = true;
            $context['dates'] = $entities['dates'];
        }

        // Detect temporal words and phrases.
        foreach ($this->temporalpatterns as $timeframe => $patterns) {
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    $context['has_timeref'] = true;
                    $context['timeframe'] = $timeframe;
                    $context['temporal_words'][] = $pattern;
                }
            }
        }

        // Detect deadline urgency.
        $deadlinewords = ['urgente', 'deadline', 'fecha limite', 'vence', 'expires', 'hoy', 'today', 'ahora', 'now'];
        foreach ($deadlinewords as $word) {
            if (str_contains($normalized, $word)) {
                $context['deadline'] = true;
                break;
            }
        }

        $context['temporal_words'] = array_values(array_unique($context['temporal_words']));

        return $context;
    }

    /**
     * Analyzes user context (role, experience level, preferences).
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @param array $usercontext User context data
     * @return array User context
     */
    protected function analyze_user(string $question, string $normalized, array $usercontext): array {
        $context = [
            'role' => $usercontext['role'] ?? 'unknown',
            'experience_level' => $this->detect_experience_level($question, $normalized),
            'is_first_time' => false,
            'language_preference' => $this->detect_language($question),
        ];

        // Detect first-time user indicators.
        $firsttimephrases = [
            'no se como', 'no entiendo', 'primera vez', 'first time', 'nunca he',
            'how do i', 'donde esta', 'where is', 'soy nuevo', 'im new',
        ];

        foreach ($firsttimephrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                $context['is_first_time'] = true;
                break;
            }
        }

        return $context;
    }

    /**
     * Analyzes topic/subject matter context.
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @param array $tokens Tokens
     * @return array Topic context
     */
    protected function analyze_topic(string $question, string $normalized, array $tokens): array {
        $context = [
            'main_topics' => [],
            'subtopics' => [],
            'moodle_domain' => null,
            'is_technical' => false,
        ];

        // Detect main topics.
        foreach ($this->topicpatterns as $topic => $patterns) {
            $matches = 0;
            foreach ($patterns as $pattern) {
                if (str_contains($normalized, $pattern)) {
                    $matches++;
                }
            }
            if ($matches > 0) {
                $context['main_topics'][] = $topic;
            }
        }

        // Detect Moodle domain.
        $domains = [
            'course_management' => ['curso', 'course', 'crear curso', 'editar curso'],
            'grading' => ['calificacion', 'grade', 'nota', 'score', 'evaluar'],
            'assignments' => ['tarea', 'assignment', 'entrega', 'submission'],
            'quizzes' => ['cuestionario', 'quiz', 'examen', 'test'],
            'user_management' => ['usuario', 'user', 'student', 'teacher', 'matricula'],
            'configuration' => ['configurar', 'config', 'settings', 'ajustes'],
            'troubleshooting' => ['error', 'problema', 'issue', 'bug', 'no funciona'],
        ];

        foreach ($domains as $domain => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $context['moodle_domain'] = $domain;
                    break 2;
                }
            }
        }

        // Detect technical question.
        $technicalwords = ['api', 'database', 'sql', 'plugin', 'codigo', 'code', 'debug', 'log', 'error'];
        foreach ($technicalwords as $word) {
            if (str_contains($normalized, $word)) {
                $context['is_technical'] = true;
                break;
            }
        }

        return $context;
    }

    /**
     * Analyzes sentiment and emotional context.
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @param array $tokens Tokens
     * @return array Sentiment context
     */
    protected function analyze_sentiment(string $question, string $normalized, array $tokens): array {
        $context = [
            'sentiment' => 'neutral',
            'emotion' => [],
            'is_frustrated' => false,
            'is_confused' => false,
            'is_grateful' => false,
            'is_urgent' => false,
            'politeness' => 'neutral',
        ];

        // Detect frustration.
        $frustrationwords = [
            'no funciona', 'not working', 'nunca funciona', 'siempre falla', 'otra vez',
            'cansado', 'frustrado', 'frustrated', 'annoyed', 'molesto',
        ];
        foreach ($frustrationwords as $word) {
            if (str_contains($normalized, $word)) {
                $context['is_frustrated'] = true;
                $context['sentiment'] = 'negative';
                $context['emotion'][] = 'frustration';
                break;
            }
        }

        // Detect confusion.
        $confusionwords = [
            'no entiendo', 'dont understand', 'confused', 'confundido', 'que significa',
            'what does it mean', 'no se', 'dont know', 'perdido', 'lost',
        ];
        foreach ($confusionwords as $word) {
            if (str_contains($normalized, $word)) {
                $context['is_confused'] = true;
                $context['emotion'][] = 'confusion';
            }
        }

        // Detect gratitude.
        $gratitudewords = ['gracias', 'thanks', 'thank you', 'agradezco', 'excelente', 'perfecto'];
        foreach ($gratitudewords as $word) {
            if (str_contains($normalized, $word)) {
                $context['is_grateful'] = true;
                $context['sentiment'] = 'positive';
                $context['emotion'][] = 'gratitude';
                break;
            }
        }

        // Detect urgency.
        $urgencywords = ['urgente', 'urgent', 'rapido', 'quick', 'pronto', 'ahora', 'now', 'inmediato'];
        foreach ($urgencywords as $word) {
            if (str_contains($normalized, $word)) {
                $context['is_urgent'] = true;
                break;
            }
        }

        // Detect politeness.
        $politewords = ['por favor', 'please', 'podria', 'could you', 'disculpa', 'excuse me'];
        $rudewords = ['necesito ya', 'i need now', 'dame', 'give me'];

        foreach ($politewords as $word) {
            if (str_contains($normalized, $word)) {
                $context['politeness'] = 'polite';
                break;
            }
        }
        foreach ($rudewords as $word) {
            if (str_contains($normalized, $word)) {
                $context['politeness'] = 'demanding';
                break;
            }
        }

        $context['emotion'] = array_values(array_unique($context['emotion']));

        return $context;
    }

    /**
     * Analyzes technical context (platform, page, etc.).
     *
     * @param string $question Original question
     * @param array $pagecontext Page context data
     * @return array Technical context
     */
    protected function analyze_technical(string $question, array $pagecontext): array {
        return [
            'current_page' => $pagecontext['page'] ?? null,
            'course_id' => $pagecontext['courseid'] ?? null,
            'page_type' => $this->detect_page_type($pagecontext['page'] ?? ''),
            'moodle_version' => $pagecontext['moodle_version'] ?? null,
        ];
    }

    /**
     * Detects urgency level of the question.
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @param array $tokens Tokens
     * @return string Urgency level
     */
    protected function detect_urgency(string $question, string $normalized, array $tokens): string {
        // Critical urgency indicators.
        $criticalwords = ['error critico', 'critical error', 'no puedo acceder', 'cannot access', 'bloqueado', 'locked out'];
        foreach ($criticalwords as $word) {
            if (str_contains($normalized, $word)) {
                return self::URGENCY_CRITICAL;
            }
        }

        // High urgency indicators.
        $highwords = ['urgente', 'urgent', 'hoy', 'today', 'ahora', 'now', 'deadline', 'vence'];
        foreach ($highwords as $word) {
            if (str_contains($normalized, $word)) {
                return self::URGENCY_HIGH;
            }
        }

        // Medium urgency indicators.
        $mediumwords = ['pronto', 'soon', 'rapido', 'quick', 'esta semana', 'this week'];
        foreach ($mediumwords as $word) {
            if (str_contains($normalized, $word)) {
                return self::URGENCY_MEDIUM;
            }
        }

        return self::URGENCY_LOW;
    }

    /**
     * Calculates question complexity.
     *
     * @param string $question Original question
     * @param array $tokens Tokens
     * @return float Complexity score (0.0 to 1.0)
     */
    protected function calculate_complexity(string $question, array $tokens): float {
        $complexity = 0.0;

        // Length factor.
        $wordcount = count($tokens);
        if ($wordcount > 20) {
            $complexity += 0.3;
        } else if ($wordcount > 10) {
            $complexity += 0.2;
        } else if ($wordcount > 5) {
            $complexity += 0.1;
        }

        // Multi-part question.
        if (substr_count($question, '?') > 1) {
            $complexity += 0.2;
        }

        // Technical terms.
        $technicalcount = 0;
        $technicalterms = ['api', 'sql', 'database', 'plugin', 'repository', 'authentication'];
        foreach ($technicalterms as $term) {
            if (in_array($term, $tokens, true)) {
                $technicalcount++;
            }
        }
        $complexity += min(0.3, $technicalcount * 0.1);

        // Conditional or comparative language.
        $complexwords = ['si', 'if', 'cuando', 'when', 'mientras', 'while', 'como', 'how', 'porque', 'why'];
        $complexcount = 0;
        foreach ($complexwords as $word) {
            if (in_array($word, $tokens, true)) {
                $complexcount++;
            }
        }
        $complexity += min(0.2, $complexcount * 0.05);

        return min(1.0, $complexity);
    }

    /**
     * Detects user experience level from question.
     *
     * @param string $question Original question
     * @param string $normalized Normalized question
     * @return string Experience level
     */
    protected function detect_experience_level(string $question, string $normalized): string {
        $beginnerphrases = [
            'que es', 'what is', 'como funciona', 'how does it work', 'para que sirve',
            'no se', 'dont know', 'primera vez', 'first time', 'basico', 'basic',
        ];

        $expertphrases = [
            'api', 'webhook', 'custom plugin', 'override', 'database schema',
            'performance optimization', 'cache', 'repository pattern',
        ];

        foreach ($expertphrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'expert';
            }
        }

        foreach ($beginnerphrases as $phrase) {
            if (str_contains($normalized, $phrase)) {
                return 'beginner';
            }
        }

        return 'intermediate';
    }

    /**
     * Detects language from question.
     *
     * @param string $question Question text
     * @return string Language code
     */
    protected function detect_language(string $question): string {
        $spanishindicators = ['que', 'como', 'donde', 'cuando', 'porque', 'por', 'para', 'el', 'la', 'los', 'las'];
        $englishindicators = ['what', 'how', 'where', 'when', 'why', 'the', 'a', 'an', 'is', 'are'];

        $normalized = core_text::strtolower($question);
        $spanishcount = 0;
        $englishcount = 0;

        foreach ($spanishindicators as $indicator) {
            if (str_contains($normalized, ' ' . $indicator . ' ')) {
                $spanishcount++;
            }
        }

        foreach ($englishindicators as $indicator) {
            if (str_contains($normalized, ' ' . $indicator . ' ')) {
                $englishcount++;
            }
        }

        return $spanishcount > $englishcount ? 'es' : 'en';
    }

    /**
     * Detects page type from URL.
     *
     * @param string $page Page URL
     * @return string|null Page type
     */
    protected function detect_page_type(string $page): ?string {
        $types = [
            '/course/view.php' => 'course_page',
            '/mod/assign/' => 'assignment_page',
            '/mod/quiz/' => 'quiz_page',
            '/mod/forum/' => 'forum_page',
            '/grade/' => 'gradebook_page',
            '/user/profile.php' => 'profile_page',
            '/admin/' => 'admin_page',
        ];

        foreach ($types as $pattern => $type) {
            if (str_contains($page, $pattern)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * Returns empty context result.
     *
     * @return array Empty result
     */
    protected function empty_context_result(): array {
        return [
            'temporal' => ['has_timeref' => false, 'timeframe' => null, 'deadline' => false, 'dates' => [], 'temporal_words' => []],
            'user' => ['role' => 'unknown', 'experience_level' => 'intermediate', 'is_first_time' => false, 'language_preference' => 'en'],
            'topic' => ['main_topics' => [], 'subtopics' => [], 'moodle_domain' => null, 'is_technical' => false],
            'sentiment' => ['sentiment' => 'neutral', 'emotion' => [], 'is_frustrated' => false, 'is_confused' => false, 'is_grateful' => false, 'is_urgent' => false, 'politeness' => 'neutral'],
            'technical' => ['current_page' => null, 'course_id' => null, 'page_type' => null, 'moodle_version' => null],
            'urgency' => self::URGENCY_LOW,
            'complexity' => 0.0,
        ];
    }

    /**
     * Initializes patterns.
     */
    protected function initialize(): void {
        if ($this->initialized) {
            return;
        }

        // Temporal patterns.
        $this->temporalpatterns = [
            'past' => ['ayer', 'yesterday', 'antes', 'before', 'la semana pasada', 'last week'],
            'present' => ['hoy', 'today', 'ahora', 'now', 'actualmente', 'currently'],
            'future' => ['mañana', 'tomorrow', 'despues', 'after', 'proxima', 'next', 'en', 'in'],
            'deadline' => ['deadline', 'fecha limite', 'vence', 'expires', 'termina', 'ends'],
        ];

        // Topic patterns.
        $this->topicpatterns = [
            'courses' => ['curso', 'course', 'asignatura', 'materia'],
            'assignments' => ['tarea', 'assignment', 'trabajo', 'homework'],
            'grades' => ['calificacion', 'grade', 'nota', 'score'],
            'users' => ['usuario', 'user', 'estudiante', 'student', 'profesor', 'teacher'],
            'configuration' => ['configurar', 'config', 'ajustes', 'settings'],
            'troubleshooting' => ['error', 'problema', 'issue', 'bug'],
        ];

        $this->initialized = true;
    }

    /**
     * Ensures analyzer is initialized.
     */
    protected function ensure_initialized(): void {
        if (!$this->initialized) {
            $this->initialize();
        }
    }
}
