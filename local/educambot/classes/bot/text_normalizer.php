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
 * Text normalizer for educambot - advanced text processing for better matching.
 *
 * Features:
 * - Preserves Spanish accents for semantic meaning
 * - Expands common abbreviations (q -> que, xq -> porque)
 * - Handles typos with Levenshtein distance
 * - Removes stopwords for better keyword matching
 * - Synonym expansion for common terms
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Text normalizer class - advanced text processing for bot matching.
 */
class text_normalizer {

    /** @var array Common Spanish abbreviations to expand */
    private const ABBREVIATIONS = [
        'q' => 'que',
        'xq' => 'porque',
        'x' => 'por',
        'xa' => 'para',
        'xfa' => 'por favor',
        'pf' => 'por favor',
        'tb' => 'también',
        'tbn' => 'también',
        'tmb' => 'también',
        'pq' => 'porque',
        'k' => 'que',
        'd' => 'de',
        'dl' => 'del',
        'cn' => 'con',
        'ste' => 'este',
        'sta' => 'esta',
        'sts' => 'estos',
        'stas' => 'estas',
        'bn' => 'bien',
        'bs' => 'buenas',
        'gcias' => 'gracias',
        'grax' => 'gracias',
        'thx' => 'gracias',
        'hla' => 'hola',
        'ola' => 'hola',
        'kmo' => 'como',
        'kien' => 'quien',
        'dnd' => 'donde',
        'cdo' => 'cuando',
        'qndo' => 'cuando',
        'xo' => 'pero',
        'mxo' => 'mucho',
        'mcho' => 'mucho',
        'pco' => 'poco',
        'nda' => 'nada',
        'tdo' => 'todo',
        'msj' => 'mensaje',
        'msgs' => 'mensajes',
        'prof' => 'profesor',
        'profe' => 'profesor',
    ];

    /** @var array Spanish stopwords to optionally remove */
    private const STOPWORDS = [
        'el', 'la', 'los', 'las', 'un', 'una', 'unos', 'unas',
        'de', 'del', 'al', 'a', 'en', 'con', 'por', 'para',
        'y', 'o', 'u', 'e', 'ni', 'que', 'se', 'su', 'sus',
        'lo', 'le', 'les', 'me', 'te', 'nos', 'os',
        'mi', 'tu', 'mis', 'tus', 'este', 'esta', 'esto',
        'ese', 'esa', 'eso', 'aquel', 'aquella', 'aquello',
        'es', 'son', 'fue', 'era', 'ser', 'estar', 'está',
        'muy', 'más', 'menos', 'tan', 'tanto',
        'yo', 'tú', 'él', 'ella', 'nosotros', 'ellos', 'ellas',
    ];

    /** @var array Synonym mappings for common Moodle terms */
    private const SYNONYMS = [
        // Tareas
        'tarea' => ['tarea', 'trabajo', 'actividad', 'asignación', 'entrega', 'ejercicio'],
        'tareas' => ['tareas', 'trabajos', 'actividades', 'asignaciones', 'entregas', 'ejercicios'],

        // Calificaciones
        'calificación' => ['calificación', 'nota', 'puntuación', 'resultado', 'evaluación'],
        'calificaciones' => ['calificaciones', 'notas', 'puntuaciones', 'resultados', 'evaluaciones'],

        // Profesor
        'profesor' => ['profesor', 'profe', 'docente', 'maestro', 'tutor', 'instructor', 'teacher'],
        'profesores' => ['profesores', 'profes', 'docentes', 'maestros', 'tutores', 'instructores'],

        // Curso
        'curso' => ['curso', 'materia', 'asignatura', 'clase', 'módulo'],
        'cursos' => ['cursos', 'materias', 'asignaturas', 'clases', 'módulos'],

        // Examen
        'examen' => ['examen', 'prueba', 'test', 'quiz', 'evaluación', 'parcial', 'final'],
        'examenes' => ['examenes', 'exámenes', 'pruebas', 'tests', 'quizzes', 'evaluaciones'],

        // Fecha
        'fecha' => ['fecha', 'día', 'cuando', 'plazo', 'deadline', 'vencimiento'],

        // Ver/Mostrar
        'ver' => ['ver', 'mostrar', 'enseñar', 'visualizar', 'consultar', 'revisar'],

        // Ayuda
        'ayuda' => ['ayuda', 'ayudar', 'ayúdame', 'auxilio', 'soporte', 'asistencia'],

        // Mensaje
        'mensaje' => ['mensaje', 'correo', 'email', 'mail', 'notificación'],
        'mensajes' => ['mensajes', 'correos', 'emails', 'mails', 'notificaciones'],

        // Calendario
        'calendario' => ['calendario', 'agenda', 'horario', 'programación', 'fechas'],

        // Pendiente
        'pendiente' => ['pendiente', 'pendientes', 'sin hacer', 'por hacer', 'faltan', 'falta'],
    ];

    /** @var bool Whether to remove stopwords */
    private $removeStopwords = false;

    /** @var bool Whether to expand synonyms */
    private $expandSynonyms = true;

    /**
     * Constructor.
     *
     * @param bool $removeStopwords Whether to remove stopwords
     * @param bool $expandSynonyms Whether to expand synonyms
     */
    public function __construct(bool $removeStopwords = false, bool $expandSynonyms = true) {
        $this->removeStopwords = $removeStopwords;
        $this->expandSynonyms = $expandSynonyms;
    }

    /**
     * Normalize text for matching.
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
    public function normalize(string $text): string {
        // Convert to lowercase using UTF-8 aware function.
        $text = mb_strtolower($text, 'UTF-8');

        // Expand abbreviations.
        $text = $this->expand_abbreviations($text);

        // Remove excessive punctuation but keep question marks for intent detection.
        $text = preg_replace('/[^\p{L}\p{N}\s\?¿]/u', ' ', $text);

        // Normalize whitespace.
        $text = trim(preg_replace('/\s+/', ' ', $text));

        // Remove stopwords if configured.
        if ($this->removeStopwords) {
            $text = $this->remove_stopwords($text);
        }

        return $text;
    }

    /**
     * Normalize text and return additional metadata.
     *
     * @param string $text Text to normalize
     * @return array ['normalized' => string, 'words' => array, 'keywords' => array]
     */
    public function analyze(string $text): array {
        $normalized = $this->normalize($text);
        $words = array_filter(explode(' ', $normalized));

        // Extract meaningful keywords (non-stopwords).
        $keywords = array_filter($words, function($word) {
            return !in_array($word, self::STOPWORDS) && mb_strlen($word) > 2;
        });

        return [
            'original' => $text,
            'normalized' => $normalized,
            'words' => array_values($words),
            'keywords' => array_values($keywords),
            'word_count' => count($words),
            'keyword_count' => count($keywords),
        ];
    }

    /**
     * Expand common abbreviations in text.
     *
     * @param string $text Text with potential abbreviations
     * @return string Text with abbreviations expanded
     */
    public function expand_abbreviations(string $text): string {
        $words = explode(' ', $text);
        $expanded = [];

        foreach ($words as $word) {
            $clean = preg_replace('/[^\p{L}]/u', '', $word);
            if (isset(self::ABBREVIATIONS[$clean])) {
                $expanded[] = self::ABBREVIATIONS[$clean];
            } else {
                $expanded[] = $word;
            }
        }

        return implode(' ', $expanded);
    }

    /**
     * Remove stopwords from text.
     *
     * @param string $text Text to process
     * @return string Text without stopwords
     */
    public function remove_stopwords(string $text): string {
        $words = explode(' ', $text);
        $filtered = array_filter($words, function($word) {
            return !in_array($word, self::STOPWORDS) && mb_strlen($word) > 1;
        });
        return implode(' ', $filtered);
    }

    /**
     * Get synonyms for a word.
     *
     * @param string $word Word to find synonyms for
     * @return array Array of synonyms (empty if none found)
     */
    public function get_synonyms(string $word): array {
        $word = mb_strtolower($word, 'UTF-8');

        // Direct lookup.
        if (isset(self::SYNONYMS[$word])) {
            return self::SYNONYMS[$word];
        }

        // Reverse lookup (find which group this word belongs to).
        foreach (self::SYNONYMS as $key => $synonyms) {
            if (in_array($word, $synonyms)) {
                return $synonyms;
            }
        }

        return [];
    }

    /**
     * Check if two words are synonyms.
     *
     * @param string $word1 First word
     * @param string $word2 Second word
     * @return bool True if synonyms
     */
    public function are_synonyms(string $word1, string $word2): bool {
        $word1 = mb_strtolower($word1, 'UTF-8');
        $word2 = mb_strtolower($word2, 'UTF-8');

        if ($word1 === $word2) {
            return true;
        }

        $synonyms = $this->get_synonyms($word1);
        return in_array($word2, $synonyms);
    }

    /**
     * Calculate similarity between two texts.
     * Combines multiple metrics for better accuracy.
     *
     * @param string $text1 First text (normalized)
     * @param string $text2 Second text (normalized)
     * @return float Similarity score 0-1
     */
    public function calculate_similarity(string $text1, string $text2): float {
        // Exact match.
        if ($text1 === $text2) {
            return 1.0;
        }

        $scores = [];

        // Levenshtein distance (normalized).
        $maxLen = max(mb_strlen($text1), mb_strlen($text2));
        if ($maxLen > 0 && $maxLen <= 255) { // Levenshtein has length limits.
            $levenshtein = levenshtein($text1, $text2);
            $scores['levenshtein'] = 1 - ($levenshtein / $maxLen);
        }

        // Word overlap (Jaccard similarity).
        $words1 = array_filter(explode(' ', $text1));
        $words2 = array_filter(explode(' ', $text2));

        if (!empty($words1) && !empty($words2)) {
            $intersection = count(array_intersect($words1, $words2));
            $union = count(array_unique(array_merge($words1, $words2)));
            $scores['jaccard'] = $intersection / $union;

            // Keyword overlap with synonym support.
            $synonymMatches = 0;
            foreach ($words1 as $w1) {
                foreach ($words2 as $w2) {
                    if ($this->are_synonyms($w1, $w2)) {
                        $synonymMatches++;
                        break;
                    }
                }
            }
            $scores['synonym'] = $synonymMatches / max(count($words1), count($words2));
        }

        // Containment check.
        if (mb_strpos($text1, $text2) !== false || mb_strpos($text2, $text1) !== false) {
            $scores['containment'] = 0.8;
        }

        // Calculate weighted average.
        $weights = [
            'levenshtein' => 0.3,
            'jaccard' => 0.3,
            'synonym' => 0.25,
            'containment' => 0.15,
        ];

        $totalWeight = 0;
        $weightedSum = 0;

        foreach ($scores as $metric => $score) {
            if (isset($weights[$metric])) {
                $weightedSum += $score * $weights[$metric];
                $totalWeight += $weights[$metric];
            }
        }

        return $totalWeight > 0 ? $weightedSum / $totalWeight : 0;
    }

    /**
     * Find best matches for a query in a list of candidates.
     *
     * @param string $query Query text
     * @param array $candidates List of candidate texts
     * @param float $threshold Minimum similarity threshold
     * @param int $limit Maximum number of results
     * @return array Sorted matches with scores
     */
    public function find_best_matches(string $query, array $candidates, float $threshold = 0.3, int $limit = 5): array {
        $normalizedQuery = $this->normalize($query);
        $matches = [];

        foreach ($candidates as $key => $candidate) {
            $normalizedCandidate = $this->normalize($candidate);
            $similarity = $this->calculate_similarity($normalizedQuery, $normalizedCandidate);

            if ($similarity >= $threshold) {
                $matches[] = [
                    'key' => $key,
                    'text' => $candidate,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity descending.
        usort($matches, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($matches, 0, $limit);
    }

    /**
     * Check if text contains any of the given keywords.
     *
     * @param string $text Text to search in
     * @param array $keywords Keywords to search for
     * @param bool $useSynonyms Whether to also check synonyms
     * @return array ['found' => bool, 'matches' => array]
     */
    public function contains_keywords(string $text, array $keywords, bool $useSynonyms = true): array {
        $normalizedText = $this->normalize($text);
        $textWords = explode(' ', $normalizedText);
        $matches = [];

        foreach ($keywords as $keyword) {
            $normalizedKeyword = $this->normalize($keyword);

            // Direct match.
            if (mb_strpos($normalizedText, $normalizedKeyword) !== false) {
                $matches[] = $keyword;
                continue;
            }

            // Word-by-word match with synonyms.
            if ($useSynonyms) {
                foreach ($textWords as $textWord) {
                    if ($this->are_synonyms($textWord, $normalizedKeyword)) {
                        $matches[] = $keyword;
                        break;
                    }
                }
            }
        }

        return [
            'found' => !empty($matches),
            'matches' => array_unique($matches),
            'count' => count(array_unique($matches)),
        ];
    }

    /**
     * Extract entities from text (course names, dates, etc.).
     *
     * @param string $text Text to analyze
     * @return array Extracted entities
     */
    public function extract_entities(string $text): array {
        $entities = [
            'dates' => [],
            'numbers' => [],
            'course_references' => [],
        ];

        // Extract numbers.
        preg_match_all('/\d+/', $text, $numbers);
        $entities['numbers'] = $numbers[0] ?? [];

        // Extract date-like patterns.
        preg_match_all('/\d{1,2}[\/\-]\d{1,2}(?:[\/\-]\d{2,4})?/', $text, $dates);
        $entities['dates'] = $dates[0] ?? [];

        // Extract relative dates.
        $relativeDates = ['hoy', 'mañana', 'ayer', 'esta semana', 'próxima semana',
                          'este mes', 'próximo mes', 'lunes', 'martes', 'miércoles',
                          'jueves', 'viernes', 'sábado', 'domingo'];
        foreach ($relativeDates as $relDate) {
            if (mb_stripos($text, $relDate) !== false) {
                $entities['dates'][] = $relDate;
            }
        }

        return $entities;
    }
}
