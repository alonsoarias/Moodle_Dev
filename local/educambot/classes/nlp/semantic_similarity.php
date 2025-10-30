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
 * Semantic similarity engine using local algorithms (no external APIs).
 *
 * Implements multiple algorithms for measuring semantic similarity:
 * - Word overlap with synonym expansion
 * - Character n-gram similarity
 * - Soft cosine similarity with word relationships
 * - Contextual similarity based on surrounding words
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use local_educambot\local\text_helper;
use core_text;

/**
 * Calculates semantic similarity between texts using local algorithms.
 */
class semantic_similarity {
    /** @var array<string,array<string>> Synonym groups for semantic matching */
    protected array $synonymgroups = [];

    /** @var array<string,array<string>> Related terms for contextual similarity */
    protected array $relatedterms = [];

    /** @var array<string,float> Term importance weights */
    protected array $termweights = [];

    /** @var bool Whether the similarity engine has been initialized */
    protected bool $initialized = false;

    /**
     * Constructor.
     *
     * @param bool $autoinit Auto-initialize similarity data
     */
    public function __construct(bool $autoinit = true) {
        if ($autoinit) {
            $this->initialize();
        }
    }

    /**
     * Calculates comprehensive similarity between two texts.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @param array $options Options: weights, algorithms to use
     * @return array{
     *     overall:float,
     *     word_overlap:float,
     *     char_ngram:float,
     *     soft_cosine:float,
     *     contextual:float,
     *     breakdown:array
     * }
     */
    public function calculate_similarity(string $text1, string $text2, array $options = []): array {
        $this->ensure_initialized();

        $text1 = trim($text1);
        $text2 = trim($text2);

        if ($text1 === '' || $text2 === '') {
            return $this->empty_similarity_result();
        }

        // Default weights for different algorithms.
        $weights = $options['weights'] ?? [
            'word_overlap' => 0.35,
            'char_ngram' => 0.20,
            'soft_cosine' => 0.30,
            'contextual' => 0.15,
        ];

        // Calculate individual similarity scores.
        $wordoverlap = $this->word_overlap_similarity($text1, $text2);
        $charngram = $this->char_ngram_similarity($text1, $text2);
        $softcosine = $this->soft_cosine_similarity($text1, $text2);
        $contextual = $this->contextual_similarity($text1, $text2);

        // Calculate weighted overall score.
        $overall = ($wordoverlap * $weights['word_overlap']) +
                   ($charngram * $weights['char_ngram']) +
                   ($softcosine * $weights['soft_cosine']) +
                   ($contextual * $weights['contextual']);

        return [
            'overall' => round($overall, 4),
            'word_overlap' => round($wordoverlap, 4),
            'char_ngram' => round($charngram, 4),
            'soft_cosine' => round($softcosine, 4),
            'contextual' => round($contextual, 4),
            'breakdown' => [
                'text1_length' => core_text::strlen($text1),
                'text2_length' => core_text::strlen($text2),
                'text1_words' => count(text_helper::tokenize($text1)),
                'text2_words' => count(text_helper::tokenize($text2)),
            ],
        ];
    }

    /**
     * Calculates word overlap similarity with synonym expansion.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0.0 to 1.0)
     */
    protected function word_overlap_similarity(string $text1, string $text2): float {
        $tokens1 = text_helper::tokenize(text_helper::normalize($text1));
        $tokens2 = text_helper::tokenize(text_helper::normalize($text2));

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        // Expand tokens with synonyms.
        $expanded1 = $this->expand_with_synonyms($tokens1);
        $expanded2 = $this->expand_with_synonyms($tokens2);

        // Calculate Jaccard similarity with expansion.
        $intersection = count(array_intersect($expanded1, $expanded2));
        $union = count(array_unique(array_merge($expanded1, $expanded2)));

        if ($union === 0) {
            return 0.0;
        }

        // Base Jaccard score.
        $jaccard = $intersection / $union;

        // Bonus for direct token overlap (without synonyms).
        $directoverlap = count(array_intersect($tokens1, $tokens2));
        $maxdirect = max(count($tokens1), count($tokens2));
        $directbonus = $maxdirect > 0 ? ($directoverlap / $maxdirect) * 0.3 : 0.0;

        return min(1.0, $jaccard + $directbonus);
    }

    /**
     * Calculates character n-gram similarity (good for typos and variations).
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @param int $n N-gram size (default 3)
     * @return float Similarity score (0.0 to 1.0)
     */
    protected function char_ngram_similarity(string $text1, string $text2, int $n = 3): float {
        $text1 = core_text::strtolower(text_helper::normalize($text1));
        $text2 = core_text::strtolower(text_helper::normalize($text2));

        if ($text1 === '' || $text2 === '') {
            return 0.0;
        }

        $ngrams1 = $this->extract_char_ngrams($text1, $n);
        $ngrams2 = $this->extract_char_ngrams($text2, $n);

        if (empty($ngrams1) || empty($ngrams2)) {
            return 0.0;
        }

        $intersection = count(array_intersect($ngrams1, $ngrams2));
        $union = count(array_unique(array_merge($ngrams1, $ngrams2)));

        return $union > 0 ? $intersection / $union : 0.0;
    }

    /**
     * Calculates soft cosine similarity using word relationships.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0.0 to 1.0)
     */
    protected function soft_cosine_similarity(string $text1, string $text2): float {
        $tokens1 = text_helper::tokenize(text_helper::normalize($text1));
        $tokens2 = text_helper::tokenize(text_helper::normalize($text2));

        if (empty($tokens1) || empty($tokens2)) {
            return 0.0;
        }

        // Build frequency vectors.
        $freq1 = array_count_values($tokens1);
        $freq2 = array_count_values($tokens2);

        // Get all unique terms.
        $allterms = array_unique(array_merge($tokens1, $tokens2));

        // Calculate similarity matrix-weighted dot product.
        $dotproduct = 0.0;
        $norm1 = 0.0;
        $norm2 = 0.0;

        foreach ($allterms as $term1) {
            $count1 = $freq1[$term1] ?? 0;
            $weight1 = $this->get_term_weight($term1);

            foreach ($allterms as $term2) {
                $count2 = $freq2[$term2] ?? 0;
                $weight2 = $this->get_term_weight($term2);

                // Similarity between terms (1.0 if same, higher if synonyms, lower otherwise).
                $termsim = $this->get_term_similarity($term1, $term2);

                $dotproduct += ($count1 * $count2 * $termsim * $weight1 * $weight2);
            }

            $norm1 += ($count1 * $count1 * $weight1 * $weight1);
        }

        foreach ($allterms as $term2) {
            $count2 = $freq2[$term2] ?? 0;
            $weight2 = $this->get_term_weight($term2);
            $norm2 += ($count2 * $count2 * $weight2 * $weight2);
        }

        $denominator = sqrt($norm1) * sqrt($norm2);

        return $denominator > 0 ? min(1.0, $dotproduct / $denominator) : 0.0;
    }

    /**
     * Calculates contextual similarity based on surrounding word patterns.
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0.0 to 1.0)
     */
    protected function contextual_similarity(string $text1, string $text2): float {
        $tokens1 = text_helper::tokenize(text_helper::normalize($text1));
        $tokens2 = text_helper::tokenize(text_helper::normalize($text2));

        if (count($tokens1) < 2 || count($tokens2) < 2) {
            return 0.0;
        }

        // Extract bigrams (context pairs).
        $bigrams1 = $this->extract_bigrams($tokens1);
        $bigrams2 = $this->extract_bigrams($tokens2);

        if (empty($bigrams1) || empty($bigrams2)) {
            return 0.0;
        }

        // Match bigrams with related terms.
        $matchedbigrams = 0;
        foreach ($bigrams1 as $bigram1) {
            foreach ($bigrams2 as $bigram2) {
                if ($this->bigrams_match($bigram1, $bigram2)) {
                    $matchedbigrams++;
                    break;
                }
            }
        }

        $maxbigrams = max(count($bigrams1), count($bigrams2));

        return $maxbigrams > 0 ? $matchedbigrams / $maxbigrams : 0.0;
    }

    /**
     * Expands tokens with their synonyms.
     *
     * @param array $tokens Tokens to expand
     * @return array Expanded tokens
     */
    protected function expand_with_synonyms(array $tokens): array {
        $expanded = $tokens;

        foreach ($tokens as $token) {
            if (isset($this->synonymgroups[$token])) {
                $expanded = array_merge($expanded, $this->synonymgroups[$token]);
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * Extracts character n-grams from text.
     *
     * @param string $text Text
     * @param int $n N-gram size
     * @return array N-grams
     */
    protected function extract_char_ngrams(string $text, int $n): array {
        $ngrams = [];
        $length = core_text::strlen($text);

        for ($i = 0; $i <= $length - $n; $i++) {
            $ngrams[] = core_text::substr($text, $i, $n);
        }

        return $ngrams;
    }

    /**
     * Extracts word bigrams from tokens.
     *
     * @param array $tokens Tokens
     * @return array Bigrams
     */
    protected function extract_bigrams(array $tokens): array {
        $bigrams = [];
        $count = count($tokens);

        for ($i = 0; $i < $count - 1; $i++) {
            $bigrams[] = [$tokens[$i], $tokens[$i + 1]];
        }

        return $bigrams;
    }

    /**
     * Checks if two bigrams match semantically.
     *
     * @param array $bigram1 First bigram
     * @param array $bigram2 Second bigram
     * @return bool True if match
     */
    protected function bigrams_match(array $bigram1, array $bigram2): bool {
        [$w1a, $w1b] = $bigram1;
        [$w2a, $w2b] = $bigram2;

        // Exact match.
        if ($w1a === $w2a && $w1b === $w2b) {
            return true;
        }

        // Check if words are synonyms or related.
        $match1 = ($w1a === $w2a || $this->are_related($w1a, $w2a));
        $match2 = ($w1b === $w2b || $this->are_related($w1b, $w2b));

        return $match1 && $match2;
    }

    /**
     * Gets similarity between two terms (considering synonyms).
     *
     * @param string $term1 First term
     * @param string $term2 Second term
     * @return float Similarity (0.0 to 1.0)
     */
    protected function get_term_similarity(string $term1, string $term2): float {
        if ($term1 === $term2) {
            return 1.0;
        }

        // Check if they're in the same synonym group.
        if (isset($this->synonymgroups[$term1]) && in_array($term2, $this->synonymgroups[$term1], true)) {
            return 0.85;
        }

        if (isset($this->synonymgroups[$term2]) && in_array($term1, $this->synonymgroups[$term2], true)) {
            return 0.85;
        }

        // Check if they're related.
        if ($this->are_related($term1, $term2)) {
            return 0.65;
        }

        // Check string similarity as fallback.
        $strsim = text_helper::string_similarity($term1, $term2);
        if ($strsim > 0.8) {
            return 0.5;
        }

        return 0.0;
    }

    /**
     * Checks if two terms are semantically related.
     *
     * @param string $term1 First term
     * @param string $term2 Second term
     * @return bool True if related
     */
    protected function are_related(string $term1, string $term2): bool {
        if (isset($this->relatedterms[$term1]) && in_array($term2, $this->relatedterms[$term1], true)) {
            return true;
        }

        if (isset($this->relatedterms[$term2]) && in_array($term1, $this->relatedterms[$term2], true)) {
            return true;
        }

        return false;
    }

    /**
     * Gets importance weight for a term.
     *
     * @param string $term Term
     * @return float Weight (higher = more important)
     */
    protected function get_term_weight(string $term): float {
        return $this->termweights[$term] ?? 1.0;
    }

    /**
     * Returns empty similarity result.
     *
     * @return array Empty result
     */
    protected function empty_similarity_result(): array {
        return [
            'overall' => 0.0,
            'word_overlap' => 0.0,
            'char_ngram' => 0.0,
            'soft_cosine' => 0.0,
            'contextual' => 0.0,
            'breakdown' => [],
        ];
    }

    /**
     * Initializes synonym groups and related terms.
     */
    protected function initialize(): void {
        if ($this->initialized) {
            return;
        }

        // Moodle-specific synonym groups.
        $this->synonymgroups = [
            'curso' => ['asignatura', 'materia', 'clase'],
            'tarea' => ['assignment', 'trabajo', 'actividad'],
            'calificacion' => ['nota', 'puntuacion', 'evaluacion', 'grade'],
            'profesor' => ['docente', 'maestro', 'instructor', 'teacher'],
            'estudiante' => ['alumno', 'student', 'aprendiz'],
            'examen' => ['prueba', 'test', 'quiz', 'evaluacion'],
            'foro' => ['discusion', 'debate', 'forum'],
            'entregar' => ['subir', 'enviar', 'submit', 'upload'],
            'descargar' => ['bajar', 'download', 'obtener'],
            'archivo' => ['file', 'documento', 'fichero'],
            'enlace' => ['link', 'vinculo', 'url'],
            'recurso' => ['material', 'resource', 'contenido'],
            'modulo' => ['seccion', 'unidad', 'module'],
            'matricula' => ['inscripcion', 'registro', 'enrollment'],
            'acceso' => ['login', 'ingreso', 'entrada', 'access'],
            'contraseña' => ['password', 'clave', 'pass'],
            'configuracion' => ['ajustes', 'settings', 'config', 'opciones'],
            'tablero' => ['dashboard', 'panel', 'inicio'],
            'completar' => ['finalizar', 'terminar', 'complete', 'finish'],
            'ver' => ['visualizar', 'mirar', 'view', 'show'],
        ];

        // Related terms (broader associations).
        $this->relatedterms = [
            'curso' => ['inscripcion', 'matricula', 'contenido', 'programa'],
            'tarea' => ['entrega', 'fecha', 'calificacion', 'retroalimentacion'],
            'calificacion' => ['aprobado', 'reprobado', 'promedio', 'puntaje'],
            'profesor' => ['tutor', 'coordinador', 'facilitador'],
            'examen' => ['pregunta', 'respuesta', 'tiempo', 'intento'],
            'foro' => ['mensaje', 'respuesta', 'comentario', 'discusion'],
            'archivo' => ['pdf', 'word', 'excel', 'imagen', 'video'],
            'error' => ['problema', 'fallo', 'bug', 'issue'],
            'ayuda' => ['soporte', 'guia', 'tutorial', 'manual'],
        ];

        // Term importance weights (higher = more important for matching).
        $this->termweights = [
            'crear' => 1.5,
            'eliminar' => 1.5,
            'modificar' => 1.4,
            'error' => 1.6,
            'problema' => 1.5,
            'ayuda' => 1.3,
            'como' => 1.2,
            'donde' => 1.2,
            'cuando' => 1.2,
            'porque' => 1.3,
            'que' => 0.8,
            'hacer' => 1.0,
            'ver' => 0.9,
        ];

        $this->initialized = true;
    }

    /**
     * Ensures the similarity engine is initialized.
     */
    protected function ensure_initialized(): void {
        if (!$this->initialized) {
            $this->initialize();
        }
    }

    /**
     * Adds custom synonym group.
     *
     * @param string $main Main term
     * @param array $synonyms Synonyms
     */
    public function add_synonym_group(string $main, array $synonyms): void {
        $main = text_helper::normalize($main);
        $synonyms = array_map([text_helper::class, 'normalize'], $synonyms);
        $this->synonymgroups[$main] = $synonyms;
    }

    /**
     * Adds related terms.
     *
     * @param string $term Term
     * @param array $related Related terms
     */
    public function add_related_terms(string $term, array $related): void {
        $term = text_helper::normalize($term);
        $related = array_map([text_helper::class, 'normalize'], $related);
        $this->relatedterms[$term] = $related;
    }
}
