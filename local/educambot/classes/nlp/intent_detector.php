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
 * Intent detection system for classifying user questions and extracting slots.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use core_text;

/**
 * Detects user intent and extracts relevant entities (slots) from questions.
 */
class intent_detector {
    /** Intent: User is asking a question seeking information */
    public const INTENT_QUESTION = 'question';

    /** Intent: User is requesting help or assistance */
    public const INTENT_HELP_REQUEST = 'help_request';

    /** Intent: User is reporting a problem or error */
    public const INTENT_PROBLEM_REPORT = 'problem_report';

    /** Intent: User is expressing gratitude */
    public const INTENT_GRATITUDE = 'gratitude';

    /** Intent: User is greeting */
    public const INTENT_GREETING = 'greeting';

    /** Intent: User is saying goodbye */
    public const INTENT_FAREWELL = 'farewell';

    /** Intent: User is confirming something */
    public const INTENT_CONFIRMATION = 'confirmation';

    /** Intent: User is denying or rejecting */
    public const INTENT_DENIAL = 'denial';

    /** Intent: User is expressing frustration or complaint */
    public const INTENT_FRUSTRATION = 'frustration';

    /** Intent: User wants to perform an action (create, delete, modify) */
    public const INTENT_ACTION_REQUEST = 'action_request';

    /** Intent: Unknown or unclear */
    public const INTENT_UNKNOWN = 'unknown';

    /** @var array<string,array> Intent patterns mapped by language */
    protected array $intentpatterns = [];

    /** @var array<string,array> Slot extraction patterns */
    protected array $slotpatterns = [];

    /**
     * Constructor. Initializes intent and slot patterns.
     */
    public function __construct() {
        $this->initialize_intent_patterns();
        $this->initialize_slot_patterns();
    }

    /**
     * Detects the intent of a user question.
     *
     * @param string $text User input text
     * @param array $analysis Optional NLP analysis from pipeline
     * @return array{intent:string,confidence:float,slots:array,evidence:array}
     */
    public function detect(string $text, array $analysis = []): array {
        $text = trim($text);
        if ($text === '') {
            return [
                'intent' => self::INTENT_UNKNOWN,
                'confidence' => 0.0,
                'slots' => [],
                'evidence' => [],
            ];
        }

        $normalized = core_text::strtolower($text);
        $tokens = $analysis['tokens'] ?? $this->simple_tokenize($text);
        $entities = $analysis['entities'] ?? [];

        // Try to match against each intent pattern.
        $scores = [];
        $evidences = [];

        foreach ($this->intentpatterns as $intent => $patterns) {
            $score = 0.0;
            $evidence = [];

            foreach ($patterns as $pattern) {
                $match = $this->match_pattern($pattern, $normalized, $tokens);
                if ($match['score'] > 0) {
                    $score = max($score, $match['score']);
                    if (!empty($match['evidence'])) {
                        $evidence[] = $match['evidence'];
                    }
                }
            }

            if ($score > 0) {
                $scores[$intent] = $score;
                $evidences[$intent] = $evidence;
            }
        }

        // Determine the best intent.
        if (empty($scores)) {
            $intent = self::INTENT_UNKNOWN;
            $confidence = 0.0;
            $evidence = [];
        } else {
            arsort($scores);
            $intent = array_key_first($scores);
            $confidence = $scores[$intent];
            $evidence = $evidences[$intent] ?? [];
        }

        // Extract slots.
        $slots = $this->extract_slots($text, $normalized, $tokens, $entities);

        return [
            'intent' => $intent,
            'confidence' => min(1.0, $confidence),
            'slots' => $slots,
            'evidence' => $evidence,
        ];
    }

    /**
     * Matches a pattern against the input text.
     *
     * @param array $pattern Pattern definition
     * @param string $normalized Normalized text
     * @param array $tokens Tokens
     * @return array{score:float,evidence:string}
     */
    protected function match_pattern(array $pattern, string $normalized, array $tokens): array {
        $score = 0.0;
        $evidence = '';

        // Check keywords.
        if (!empty($pattern['keywords'])) {
            $keywordmatches = 0;
            foreach ($pattern['keywords'] as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    $keywordmatches++;
                    $evidence = "matched keyword: {$keyword}";
                }
            }
            if ($keywordmatches > 0) {
                $score += ($keywordmatches / count($pattern['keywords'])) * 0.7;
            }
        }

        // Check regex patterns.
        if (!empty($pattern['regex'])) {
            foreach ($pattern['regex'] as $regex) {
                if (preg_match($regex, $normalized)) {
                    $score += 0.8;
                    $evidence = "matched regex: {$regex}";
                    break;
                }
            }
        }

        // Check token patterns (specific token sequences).
        if (!empty($pattern['token_sequences'])) {
            $tokensstr = implode(' ', $tokens);
            foreach ($pattern['token_sequences'] as $sequence) {
                if (str_contains($tokensstr, $sequence)) {
                    $score += 0.6;
                    $evidence = "matched token sequence: {$sequence}";
                    break;
                }
            }
        }

        // Check for question markers.
        if (!empty($pattern['question_markers']) && $this->has_question_marker($normalized)) {
            $score += 0.3;
        }

        // Check sentence structure.
        if (!empty($pattern['structure'])) {
            if ($pattern['structure'] === 'question' && $this->is_question_structure($normalized)) {
                $score += 0.4;
            } else if ($pattern['structure'] === 'imperative' && $this->is_imperative_structure($normalized, $tokens)) {
                $score += 0.4;
            }
        }

        return [
            'score' => min(1.5, $score),
            'evidence' => $evidence,
        ];
    }

    /**
     * Extracts slots (entities) from the text based on patterns.
     *
     * @param string $original Original text
     * @param string $normalized Normalized text
     * @param array $tokens Tokens
     * @param array $entities Entities from NLP pipeline
     * @return array<string,mixed> Extracted slots
     */
    protected function extract_slots(string $original, string $normalized, array $tokens, array $entities): array {
        $slots = [];

        // Extract from NLP entities.
        if (!empty($entities['courses'])) {
            $slots['course_codes'] = $entities['courses'];
        }
        if (!empty($entities['activities'])) {
            $slots['activity_types'] = $entities['activities'];
        }
        if (!empty($entities['dates'])) {
            $slots['dates'] = $entities['dates'];
        }
        if (!empty($entities['numbers'])) {
            $slots['numbers'] = $entities['numbers'];
        }

        // Extract using slot patterns.
        foreach ($this->slotpatterns as $slotname => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $original, $matches)) {
                    if (!isset($slots[$slotname])) {
                        $slots[$slotname] = [];
                    }
                    $slots[$slotname][] = $matches[1] ?? $matches[0];
                }
            }
        }

        // Extract action verbs.
        $actionverbs = $this->extract_action_verbs($tokens);
        if (!empty($actionverbs)) {
            $slots['actions'] = $actionverbs;
        }

        // Extract topics/subjects.
        $topics = $this->extract_topics($tokens);
        if (!empty($topics)) {
            $slots['topics'] = $topics;
        }

        return $slots;
    }

    /**
     * Checks if the text has question markers (?, qué, cómo, cuándo, etc.).
     *
     * @param string $text Normalized text
     * @return bool
     */
    protected function has_question_marker(string $text): bool {
        if (str_contains($text, '?')) {
            return true;
        }

        $questionwords = ['que', 'como', 'cuando', 'donde', 'por que', 'porque', 'quien', 'cual', 'cuales',
            'what', 'how', 'when', 'where', 'why', 'who', 'which'];

        foreach ($questionwords as $word) {
            if (str_starts_with($text, $word . ' ') || str_contains($text, ' ' . $word . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the text has a question structure.
     *
     * @param string $text Normalized text
     * @return bool
     */
    protected function is_question_structure(string $text): bool {
        // Ends with question mark.
        if (str_ends_with($text, '?')) {
            return true;
        }

        // Starts with question word.
        $questionstarts = ['que', 'como', 'cuando', 'donde', 'por que', 'quien', 'cual',
            'what', 'how', 'when', 'where', 'why', 'who', 'which', 'can', 'could', 'would', 'should',
            'puedo', 'podria', 'deberia'];

        foreach ($questionstarts as $start) {
            if (str_starts_with($text, $start . ' ')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determines if the text has an imperative structure (command/request).
     *
     * @param string $text Normalized text
     * @param array $tokens Tokens
     * @return bool
     */
    protected function is_imperative_structure(string $text, array $tokens): bool {
        if (empty($tokens)) {
            return false;
        }

        $imperativeverbs = ['ayuda', 'ayudame', 'muestra', 'muestrame', 'explica', 'explicame', 'dime',
            'crea', 'elimina', 'modifica', 'cambia', 'abre', 'cierra', 'help', 'show', 'explain', 'tell',
            'create', 'delete', 'modify', 'change', 'open', 'close'];

        $firsttoken = $tokens[0] ?? '';
        return in_array($firsttoken, $imperativeverbs, true);
    }

    /**
     * Extracts action verbs from tokens.
     *
     * @param array $tokens Tokens
     * @return array<int,string> Action verbs found
     */
    protected function extract_action_verbs(array $tokens): array {
        $actionverbs = ['crear', 'eliminar', 'borrar', 'modificar', 'cambiar', 'editar', 'agregar', 'anadir',
            'quitar', 'ver', 'visualizar', 'descargar', 'subir', 'enviar', 'publicar', 'ocultar', 'mostrar',
            'create', 'delete', 'remove', 'modify', 'change', 'edit', 'add', 'view', 'download', 'upload',
            'send', 'publish', 'hide', 'show'];

        $found = [];
        foreach ($tokens as $token) {
            if (in_array($token, $actionverbs, true)) {
                $found[] = $token;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Extracts topics/subjects from tokens.
     *
     * @param array $tokens Tokens
     * @return array<int,string> Topics found
     */
    protected function extract_topics(array $tokens): array {
        $topics = ['curso', 'actividad', 'tarea', 'cuestionario', 'calificacion', 'nota', 'usuario',
            'estudiante', 'alumno', 'profesor', 'maestro', 'grupo', 'categoria', 'bloque', 'plugin',
            'course', 'activity', 'assignment', 'quiz', 'grade', 'user', 'student', 'teacher',
            'group', 'category', 'block', 'plugin'];

        $found = [];
        foreach ($tokens as $token) {
            if (in_array($token, $topics, true)) {
                $found[] = $token;
            }
        }

        return array_values(array_unique($found));
    }

    /**
     * Simple tokenization for cases where NLP pipeline is not used.
     *
     * @param string $text Text to tokenize
     * @return array<int,string> Tokens
     */
    protected function simple_tokenize(string $text): array {
        $normalized = core_text::strtolower($text);
        $tokens = preg_split('/[\s,;:?!]+/', $normalized, -1, PREG_SPLIT_NO_EMPTY);
        return $tokens ? array_map('trim', $tokens) : [];
    }

    /**
     * Initializes intent patterns for Spanish and English.
     */
    protected function initialize_intent_patterns(): void {
        $this->intentpatterns = [
            self::INTENT_QUESTION => [
                [
                    'keywords' => ['que', 'como', 'cuando', 'donde', 'por que', 'quien', 'cual'],
                    'question_markers' => true,
                    'structure' => 'question',
                ],
                [
                    'keywords' => ['what', 'how', 'when', 'where', 'why', 'who', 'which'],
                    'question_markers' => true,
                    'structure' => 'question',
                ],
                [
                    'regex' => ['/^(que|como|cuando|donde|por que|quien|cual)\s/i'],
                ],
            ],

            self::INTENT_HELP_REQUEST => [
                [
                    'keywords' => ['ayuda', 'ayudame', 'necesito ayuda', 'puedes ayudarme', 'auxilio'],
                ],
                [
                    'keywords' => ['help', 'help me', 'need help', 'can you help', 'assist'],
                ],
                [
                    'regex' => ['/\b(ayuda|help)\b/i', '/necesito\s+(ayuda|asistencia)/i'],
                ],
            ],

            self::INTENT_PROBLEM_REPORT => [
                [
                    'keywords' => ['error', 'problema', 'fallo', 'no funciona', 'no puedo', 'roto'],
                ],
                [
                    'keywords' => ['error', 'problem', 'issue', 'not working', 'cannot', 'broken', 'bug'],
                ],
                [
                    'regex' => ['/\b(error|problema|fallo|bug)\b/i', '/no\s+(funciona|puedo|trabaja)/i'],
                ],
            ],

            self::INTENT_GRATITUDE => [
                [
                    'keywords' => ['gracias', 'muchas gracias', 'agradezco', 'agradecido', 'perfecto'],
                ],
                [
                    'keywords' => ['thanks', 'thank you', 'appreciate', 'grateful', 'perfect'],
                ],
                [
                    'regex' => ['/^(gracias|thanks|thank\s+you)/i'],
                ],
            ],

            self::INTENT_GREETING => [
                [
                    'keywords' => ['hola', 'buenos dias', 'buenas tardes', 'buenas noches', 'saludos'],
                ],
                [
                    'keywords' => ['hello', 'hi', 'good morning', 'good afternoon', 'good evening', 'hey'],
                ],
                [
                    'regex' => ['/^(hola|hello|hi|hey|buenos|buenas)\b/i'],
                ],
            ],

            self::INTENT_FAREWELL => [
                [
                    'keywords' => ['adios', 'hasta luego', 'nos vemos', 'chao', 'hasta pronto'],
                ],
                [
                    'keywords' => ['goodbye', 'bye', 'see you', 'farewell', 'later'],
                ],
                [
                    'regex' => ['/^(adios|bye|goodbye|chao|hasta)\b/i'],
                ],
            ],

            self::INTENT_CONFIRMATION => [
                [
                    'keywords' => ['si', 'claro', 'por supuesto', 'correcto', 'exacto', 'afirmativo', 'ok'],
                ],
                [
                    'keywords' => ['yes', 'sure', 'of course', 'correct', 'exactly', 'affirmative', 'ok', 'okay'],
                ],
                [
                    'regex' => ['/^(si|yes|ok|okay|claro|correcto)\b/i'],
                ],
            ],

            self::INTENT_DENIAL => [
                [
                    'keywords' => ['no', 'negativo', 'nunca', 'jamas', 'tampoco'],
                ],
                [
                    'keywords' => ['no', 'nope', 'negative', 'never', 'neither'],
                ],
                [
                    'regex' => ['/^(no|nope|nunca|never)\b/i'],
                ],
            ],

            self::INTENT_FRUSTRATION => [
                [
                    'keywords' => ['frustrante', 'molesto', 'enojado', 'harto', 'cansado', 'desesperado'],
                ],
                [
                    'keywords' => ['frustrating', 'annoying', 'angry', 'tired', 'fed up', 'desperate'],
                ],
                [
                    'regex' => ['/\b(frustra|molesta|enoja|harto|desesper)/i'],
                ],
            ],

            self::INTENT_ACTION_REQUEST => [
                [
                    'keywords' => ['crear', 'eliminar', 'modificar', 'cambiar', 'agregar', 'quitar'],
                    'structure' => 'imperative',
                ],
                [
                    'keywords' => ['create', 'delete', 'modify', 'change', 'add', 'remove'],
                    'structure' => 'imperative',
                ],
                [
                    'token_sequences' => ['quiero crear', 'quiero eliminar', 'quiero modificar', 'necesito crear',
                        'want to create', 'want to delete', 'want to modify', 'need to create'],
                ],
            ],
        ];
    }

    /**
     * Initializes slot extraction patterns.
     */
    protected function initialize_slot_patterns(): void {
        $this->slotpatterns = [
            'user_name' => [
                '/usuario\s+([a-z0-9_\-\.]+)/i',
                '/user\s+([a-z0-9_\-\.]+)/i',
                '/alumno\s+([a-z0-9_\-\s]+)/i',
                '/student\s+([a-z0-9_\-\s]+)/i',
            ],
            'course_name' => [
                '/curso\s+(["\']?)([^"\']+)\1/i',
                '/course\s+(["\']?)([^"\']+)\1/i',
            ],
            'activity_name' => [
                '/actividad\s+(["\']?)([^"\']+)\1/i',
                '/activity\s+(["\']?)([^"\']+)\1/i',
                '/tarea\s+(["\']?)([^"\']+)\1/i',
                '/assignment\s+(["\']?)([^"\']+)\1/i',
            ],
            'error_message' => [
                '/error:\s+([^\.]+)/i',
                '/mensaje\s+de\s+error:\s+([^\.]+)/i',
                '/error\s+message:\s+([^\.]+)/i',
            ],
        ];
    }

    /**
     * Gets a human-readable description of an intent.
     *
     * @param string $intent Intent constant
     * @return string Description
     */
    public static function get_intent_description(string $intent): string {
        $descriptions = [
            self::INTENT_QUESTION => 'Information request',
            self::INTENT_HELP_REQUEST => 'Assistance needed',
            self::INTENT_PROBLEM_REPORT => 'Problem or error report',
            self::INTENT_GRATITUDE => 'Expressing thanks',
            self::INTENT_GREETING => 'Greeting',
            self::INTENT_FAREWELL => 'Saying goodbye',
            self::INTENT_CONFIRMATION => 'Confirming',
            self::INTENT_DENIAL => 'Denying or rejecting',
            self::INTENT_FRUSTRATION => 'Expressing frustration',
            self::INTENT_ACTION_REQUEST => 'Action or task request',
            self::INTENT_UNKNOWN => 'Unknown intent',
        ];

        return $descriptions[$intent] ?? 'Unknown';
    }
}
