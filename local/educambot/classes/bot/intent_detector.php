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
 * Detects:
 * - Query intents (questions about information)
 * - Action intents (requests to do something)
 * - Navigation intents (where to find something)
 * - Greeting intents (social interactions)
 * - Complaint intents (problems/frustrations)
 * - Sentiment (positive/negative/neutral)
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Intent detector class - classifies user intent.
 */
class intent_detector {

    // Intent types.
    public const INTENT_GREETING = 'greeting';
    public const INTENT_FAREWELL = 'farewell';
    public const INTENT_THANKS = 'thanks';
    public const INTENT_QUERY = 'query';
    public const INTENT_ACTION = 'action';
    public const INTENT_NAVIGATION = 'navigation';
    public const INTENT_COMPLAINT = 'complaint';
    public const INTENT_AFFIRMATION = 'affirmation';
    public const INTENT_NEGATION = 'negation';
    public const INTENT_UNKNOWN = 'unknown';

    // Topic types.
    public const TOPIC_ASSIGNMENTS = 'assignments';
    public const TOPIC_GRADES = 'grades';
    public const TOPIC_CALENDAR = 'calendar';
    public const TOPIC_MESSAGES = 'messages';
    public const TOPIC_COURSE = 'course';
    public const TOPIC_TEACHER = 'teacher';
    public const TOPIC_PROFILE = 'profile';
    public const TOPIC_HELP = 'help';
    public const TOPIC_NAVIGATION = 'navigation';
    public const TOPIC_GENERAL = 'general';

    // Sentiment types.
    public const SENTIMENT_POSITIVE = 'positive';
    public const SENTIMENT_NEGATIVE = 'negative';
    public const SENTIMENT_NEUTRAL = 'neutral';
    public const SENTIMENT_FRUSTRATED = 'frustrated';
    public const SENTIMENT_CONFUSED = 'confused';

    /** @var array Intent patterns */
    private const INTENT_PATTERNS = [
        self::INTENT_GREETING => [
            'patterns' => [
                '/^(hola|buenas?|buenos|hey|hi|hello|saludos|qué tal|que tal)/ui',
                '/^(buen(a|o)s?\s+(día|dias|tarde|tardes|noche|noches))/ui',
            ],
            'weight' => 1.0,
        ],
        self::INTENT_FAREWELL => [
            'patterns' => [
                '/^(adiós|adios|chao|chau|bye|hasta luego|nos vemos)/ui',
                '/(gracias.*adiós|gracias.*chao)/ui',
            ],
            'weight' => 1.0,
        ],
        self::INTENT_THANKS => [
            'patterns' => [
                '/^(gracias|muchas gracias|te agradezco|thanks|thx)/ui',
                '/(eres genial|me ayudaste|muy útil|excelente)/ui',
            ],
            'weight' => 1.0,
        ],
        self::INTENT_QUERY => [
            'patterns' => [
                '/(qué|que)\s+(es|son|significa|quiere decir)/ui',
                '/(cómo|como)\s+(es|son|funciona|se hace|puedo)/ui',
                '/(dónde|donde)\s+(está|estan|encuentro|queda|veo)/ui',
                '/(cuándo|cuando)\s+(es|son|empieza|termina|vence)/ui',
                '/(cuánto|cuanto|cuánta|cuanta)\s+(es|son|cuesta|falta|tengo)/ui',
                '/(por qué|porque|porqué)\s+/ui',
                '/(quién|quien)\s+(es|son|puede|debo)/ui',
                '/(cuál|cual|cuáles|cuales)\s+(es|son)/ui',
                '/^(hay|existe|tienen|tengo)\s+/ui',
                '/\?$/u',  // Ends with question mark.
            ],
            'weight' => 0.9,
        ],
        self::INTENT_ACTION => [
            'patterns' => [
                '/^(quiero|necesito|dame|muéstrame|muestrame|déjame|dejame)/ui',
                '/^(puedo|podría|podria|quisiera|me gustaría)/ui',
                '/^(ayúdame|ayudame|ayuda|help)/ui',
                '/(entregar|enviar|subir|descargar|ver|abrir|crear)/ui',
                '/(inscribir|matricular|registrar|cambiar|modificar)/ui',
            ],
            'weight' => 0.85,
        ],
        self::INTENT_NAVIGATION => [
            'patterns' => [
                '/(dónde|donde)\s+(está|queda|encuentro|veo|accedo)/ui',
                '/(cómo|como)\s+(llego|accedo|entro|voy)\s+a/ui',
                '/(ir|llevar|mostrar|abrir)\s+(a|la|el|mi)/ui',
                '/(link|enlace|url|dirección|página)\s+(de|del|a|para)/ui',
            ],
            'weight' => 0.8,
        ],
        self::INTENT_COMPLAINT => [
            'patterns' => [
                '/(no funciona|no sirve|no puedo|no me deja|error)/ui',
                '/(problema|falla|fallo|bug|roto|mal)/ui',
                '/(frustrado|frustrada|enojado|enojada|molesto|molesta)/ui',
                '/(terrible|horrible|pésimo|malísimo|inútil)/ui',
                '/(ayuda urgente|urgente|emergencia)/ui',
                '/(no entiendo|no sé|no se|confundido|confundida|perdido|perdida)/ui',
            ],
            'weight' => 0.95,
        ],
        self::INTENT_AFFIRMATION => [
            'patterns' => [
                '/^(sí|si|claro|ok|okay|vale|bien|correcto|exacto|afirmativo)/ui',
                '/^(eso|así|así es|eso mismo|exactamente)/ui',
            ],
            'weight' => 1.0,
        ],
        self::INTENT_NEGATION => [
            'patterns' => [
                '/^(no|nope|negativo|para nada|de ninguna manera)/ui',
                '/^(tampoco|ni|nunca|jamás)/ui',
            ],
            'weight' => 1.0,
        ],
    ];

    /** @var array Topic patterns */
    private const TOPIC_PATTERNS = [
        self::TOPIC_ASSIGNMENTS => [
            'keywords' => ['tarea', 'tareas', 'trabajo', 'trabajos', 'entrega', 'entregas',
                          'actividad', 'actividades', 'asignación', 'assignment', 'enviar',
                          'subir', 'entregar', 'pendiente', 'pendientes', 'ejercicio'],
        ],
        self::TOPIC_GRADES => [
            'keywords' => ['calificación', 'calificaciones', 'nota', 'notas', 'puntuación',
                          'resultado', 'resultados', 'evaluación', 'aprobado', 'reprobado',
                          'promedio', 'grade', 'grades', 'score', 'boletín', 'libreta'],
        ],
        self::TOPIC_CALENDAR => [
            'keywords' => ['calendario', 'fecha', 'fechas', 'evento', 'eventos', 'horario',
                          'agenda', 'programación', 'cuando', 'cuándo', 'vence', 'vencimiento',
                          'deadline', 'plazo', 'límite', 'mañana', 'hoy', 'semana'],
        ],
        self::TOPIC_MESSAGES => [
            'keywords' => ['mensaje', 'mensajes', 'correo', 'correos', 'email', 'notificación',
                          'notificaciones', 'bandeja', 'inbox', 'escribir', 'contactar'],
        ],
        self::TOPIC_COURSE => [
            'keywords' => ['curso', 'cursos', 'materia', 'materias', 'asignatura', 'clase',
                          'clases', 'módulo', 'contenido', 'tema', 'temas', 'unidad', 'lección'],
        ],
        self::TOPIC_TEACHER => [
            'keywords' => ['profesor', 'profesores', 'profe', 'docente', 'maestro', 'tutor',
                          'instructor', 'teacher', 'contactar', 'consulta', 'asesoría'],
        ],
        self::TOPIC_PROFILE => [
            'keywords' => ['perfil', 'cuenta', 'contraseña', 'password', 'usuario', 'foto',
                          'imagen', 'datos', 'configuración', 'preferencias', 'settings'],
        ],
        self::TOPIC_HELP => [
            'keywords' => ['ayuda', 'help', 'soporte', 'asistencia', 'manual', 'guía',
                          'tutorial', 'explicar', 'explicación', 'cómo', 'como'],
        ],
        self::TOPIC_NAVIGATION => [
            'keywords' => ['dónde', 'donde', 'encontrar', 'buscar', 'acceder', 'ir', 'llegar',
                          'ubicación', 'sección', 'menú', 'página', 'link', 'enlace'],
        ],
    ];

    /** @var array Sentiment patterns */
    private const SENTIMENT_PATTERNS = [
        self::SENTIMENT_POSITIVE => [
            'keywords' => ['gracias', 'excelente', 'genial', 'perfecto', 'increíble', 'bueno',
                          'bien', 'fantástico', 'maravilloso', 'útil', 'me encanta', 'feliz'],
        ],
        self::SENTIMENT_NEGATIVE => [
            'keywords' => ['malo', 'mal', 'terrible', 'horrible', 'pésimo', 'inútil',
                          'decepcionado', 'triste', 'molesto', 'enojado', 'peor'],
        ],
        self::SENTIMENT_FRUSTRATED => [
            'keywords' => ['frustrado', 'frustrada', 'harto', 'harta', 'cansado', 'cansada',
                          'no puedo más', 'imposible', 'desesperado', 'urgente', 'ya no sé'],
        ],
        self::SENTIMENT_CONFUSED => [
            'keywords' => ['confundido', 'confundida', 'perdido', 'perdida', 'no entiendo',
                          'no sé', 'no comprendo', 'complicado', 'difícil', 'lío', 'enredo'],
        ],
    ];

    /** @var text_normalizer Text normalizer instance */
    private $normalizer;

    /**
     * Constructor.
     *
     * @param text_normalizer|null $normalizer Optional normalizer instance
     */
    public function __construct(?text_normalizer $normalizer = null) {
        $this->normalizer = $normalizer ?? new text_normalizer();
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

        foreach (self::INTENT_PATTERNS as $intent => $config) {
            $score = 0;
            $matched = false;

            foreach ($config['patterns'] as $pattern) {
                // Check original text for patterns that need original casing/punctuation.
                if (preg_match($pattern, $originalText)) {
                    $matched = true;
                    $score = max($score, $config['weight']);
                }
                // Also check normalized text.
                if (preg_match($pattern, $normalizedText)) {
                    $matched = true;
                    $score = max($score, $config['weight']);
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

        foreach (self::TOPIC_PATTERNS as $topic => $config) {
            $matchCount = 0;
            $matchedKeywords = [];

            foreach ($config['keywords'] as $keyword) {
                // Check if keyword or its synonyms appear in text.
                if (mb_strpos($normalizedText, $keyword) !== false) {
                    $matchCount++;
                    $matchedKeywords[] = $keyword;
                } else {
                    // Check synonyms.
                    foreach ($words as $word) {
                        if ($this->normalizer->are_synonyms($word, $keyword)) {
                            $matchCount++;
                            $matchedKeywords[] = $keyword;
                            break;
                        }
                    }
                }
            }

            if ($matchCount > 0) {
                $scores[$topic] = [
                    'count' => $matchCount,
                    'score' => $matchCount / count($config['keywords']),
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
            'confidence' => min(1.0, $confidence * 2), // Scale up for better thresholds.
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

        foreach (self::SENTIMENT_PATTERNS as $sentiment => $config) {
            $matchCount = 0;
            foreach ($config['keywords'] as $keyword) {
                if (mb_strpos($normalizedText, $keyword) !== false) {
                    $matchCount++;
                }
            }

            if ($matchCount > 0) {
                $scores[$sentiment] = $matchCount / count($config['keywords']);
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
            'confidence' => min(1.0, $confidence * 3), // Scale up.
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
        $urgentKeywords = ['urgente', 'emergencia', 'ahora', 'inmediato', 'rápido', 'pronto',
                          'hoy', 'antes de', 'deadline', 'ya', 'ayuda', 'socorro'];
        $matchCount = 0;

        foreach ($urgentKeywords as $keyword) {
            if (mb_strpos($normalizedText, $keyword) !== false) {
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
     * Get a human-friendly response based on detected intent.
     *
     * @param array $detection Detection results from detect()
     * @return string Suggested response approach
     */
    public function get_response_strategy(array $detection): string {
        $intent = $detection['intent']['primary'];
        $sentiment = $detection['sentiment']['primary'];

        // Handle negative sentiments first.
        if (in_array($sentiment, [self::SENTIMENT_FRUSTRATED, self::SENTIMENT_NEGATIVE])) {
            return 'empathetic'; // Be extra understanding.
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
                return 'follow_up'; // User is responding to something.
            default:
                return 'informative';
        }
    }
}
