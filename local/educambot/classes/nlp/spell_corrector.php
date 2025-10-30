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
 * Spell correction system using Levenshtein distance and Moodle-specific dictionary.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use core_text;

/**
 * Provides spell checking and correction with "Did you mean...?" suggestions.
 */
class spell_corrector {
    /** Maximum Levenshtein distance to consider a word as a correction candidate */
    protected const MAX_DISTANCE = 2;

    /** Minimum word length to apply spell checking */
    protected const MIN_WORD_LENGTH = 4;

    /** @var array<string,int> Dictionary of valid words with frequency */
    protected array $dictionary = [];

    /** @var array<string,string> Common misspellings map */
    protected array $commonerrors = [];

    /** @var bool Whether the dictionary has been initialized */
    protected bool $initialized = false;

    /**
     * Constructor. Optionally initializes the dictionary.
     *
     * @param bool $autoinit Auto-initialize dictionary
     */
    public function __construct(bool $autoinit = true) {
        if ($autoinit) {
            $this->initialize_dictionary();
        }
    }

    /**
     * Corrects spelling errors in a text, returning original and corrected versions.
     *
     * @param string $text Text to check
     * @param int $maxsuggestions Maximum number of suggestions per word
     * @return array{original:string,corrected:string,corrections:array,has_errors:bool}
     */
    public function correct_text(string $text, int $maxsuggestions = 3): array {
        $this->ensure_initialized();

        $text = trim($text);
        if ($text === '') {
            return [
                'original' => '',
                'corrected' => '',
                'corrections' => [],
                'has_errors' => false,
            ];
        }

        $words = $this->tokenize_for_spelling($text);
        $corrections = [];
        $correctedwords = [];
        $haserrors = false;

        foreach ($words as $word) {
            $normalized = core_text::strtolower($word);

            // Skip short words.
            if (core_text::strlen($normalized) < self::MIN_WORD_LENGTH) {
                $correctedwords[] = $word;
                continue;
            }

            // Skip if word is in dictionary.
            if (isset($this->dictionary[$normalized])) {
                $correctedwords[] = $word;
                continue;
            }

            // Check common errors first.
            if (isset($this->commonerrors[$normalized])) {
                $correction = $this->commonerrors[$normalized];
                $corrections[] = [
                    'word' => $word,
                    'suggestions' => [$correction],
                    'best' => $correction,
                    'confidence' => 0.95,
                ];
                $correctedwords[] = $correction;
                $haserrors = true;
                continue;
            }

            // Find suggestions using Levenshtein.
            $suggestions = $this->find_suggestions($normalized, $maxsuggestions);

            if (!empty($suggestions)) {
                $best = $suggestions[0]['word'];
                $corrections[] = [
                    'word' => $word,
                    'suggestions' => array_column($suggestions, 'word'),
                    'best' => $best,
                    'confidence' => $suggestions[0]['confidence'],
                ];
                $correctedwords[] = $best;
                $haserrors = true;
            } else {
                // No suggestions found, keep original.
                $correctedwords[] = $word;
            }
        }

        return [
            'original' => $text,
            'corrected' => implode(' ', $correctedwords),
            'corrections' => $corrections,
            'has_errors' => $haserrors,
        ];
    }

    /**
     * Finds correction suggestions for a single word.
     *
     * @param string $word Word to correct (normalized)
     * @param int $limit Maximum suggestions
     * @return array<int,array> Array of suggestions with confidence scores
     */
    public function find_suggestions(string $word, int $limit = 5): array {
        $this->ensure_initialized();

        if (isset($this->dictionary[$word])) {
            return []; // Word is correct.
        }

        $suggestions = [];
        $wordlength = core_text::strlen($word);

        foreach (array_keys($this->dictionary) as $dictword) {
            $dictlength = core_text::strlen($dictword);

            // Skip if length difference is too large.
            if (abs($wordlength - $dictlength) > self::MAX_DISTANCE) {
                continue;
            }

            $distance = levenshtein($word, $dictword);

            if ($distance <= self::MAX_DISTANCE && $distance > 0) {
                $frequency = $this->dictionary[$dictword];
                // Confidence based on distance and frequency.
                $confidence = (1 - ($distance / self::MAX_DISTANCE)) * (1 + log($frequency + 1) / 10);

                $suggestions[] = [
                    'word' => $dictword,
                    'distance' => $distance,
                    'confidence' => min(0.99, $confidence),
                ];
            }
        }

        // Sort by confidence (higher first).
        usort($suggestions, static function(array $a, array $b): int {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Checks if a word is spelled correctly.
     *
     * @param string $word Word to check
     * @return bool True if correct
     */
    public function is_correct(string $word): bool {
        $this->ensure_initialized();
        $normalized = core_text::strtolower(trim($word));
        return isset($this->dictionary[$normalized]);
    }

    /**
     * Adds a word to the dictionary.
     *
     * @param string $word Word to add
     * @param int $frequency Frequency/weight (higher = more common)
     */
    public function add_word(string $word, int $frequency = 1): void {
        $normalized = core_text::strtolower(trim($word));
        if ($normalized !== '') {
            if (!isset($this->dictionary[$normalized])) {
                $this->dictionary[$normalized] = 0;
            }
            $this->dictionary[$normalized] += $frequency;
        }
    }

    /**
     * Adds multiple words to the dictionary.
     *
     * @param array<int,string> $words Array of words
     * @param int $frequency Frequency for all words
     */
    public function add_words(array $words, int $frequency = 1): void {
        foreach ($words as $word) {
            $this->add_word($word, $frequency);
        }
    }

    /**
     * Tokenizes text for spelling correction (preserves original case).
     *
     * @param string $text Text to tokenize
     * @return array<int,string> Array of words
     */
    protected function tokenize_for_spelling(string $text): array {
        // Split by spaces and punctuation, preserving words.
        $words = preg_split('/[\s\.,;:!?\(\)\[\]{}]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return $words ? $words : [];
    }

    /**
     * Initializes the dictionary with Moodle-specific terms and common words.
     */
    protected function initialize_dictionary(): void {
        if ($this->initialized) {
            return;
        }

        // Moodle-specific terms (high frequency).
        $moodleterms = [
            // Core concepts.
            'moodle', 'curso', 'cursos', 'actividad', 'actividades', 'tarea', 'tareas', 'cuestionario',
            'cuestionarios', 'calificacion', 'calificaciones', 'nota', 'notas', 'estudiante', 'estudiantes',
            'alumno', 'alumnos', 'profesor', 'profesores', 'maestro', 'maestros', 'usuario', 'usuarios',
            'grupo', 'grupos', 'categoria', 'categorias', 'bloque', 'bloques', 'plugin', 'plugins',
            'tema', 'temas', 'recurso', 'recursos', 'foro', 'foros', 'wiki', 'wikis', 'glosario', 'glosarios',
            'leccion', 'lecciones', 'encuesta', 'encuestas', 'chat', 'chats', 'taller', 'talleres',
            'base', 'bases', 'datos', 'archivo', 'archivos', 'carpeta', 'carpetas', 'pagina', 'paginas',
            'url', 'urls', 'etiqueta', 'etiquetas', 'enlace', 'enlaces',

            // English terms.
            'course', 'courses', 'activity', 'activities', 'assignment', 'assignments', 'quiz', 'quizzes',
            'grade', 'grades', 'student', 'students', 'teacher', 'teachers', 'user', 'users', 'group',
            'groups', 'category', 'categories', 'block', 'blocks', 'plugin', 'plugins', 'theme', 'themes',
            'resource', 'resources', 'forum', 'forums', 'wiki', 'wikis', 'glossary', 'glossaries',
            'lesson', 'lessons', 'survey', 'surveys', 'chat', 'chats', 'workshop', 'workshops',
            'database', 'databases', 'file', 'files', 'folder', 'folders', 'page', 'pages', 'label',
            'labels', 'link', 'links',

            // Actions.
            'crear', 'eliminar', 'borrar', 'modificar', 'editar', 'cambiar', 'agregar', 'anadir', 'quitar',
            'descargar', 'subir', 'enviar', 'publicar', 'ocultar', 'mostrar', 'ver', 'visualizar',
            'calificar', 'evaluar', 'inscribir', 'matricular', 'acceder', 'entrar', 'salir', 'configurar',
            'create', 'delete', 'remove', 'modify', 'edit', 'change', 'add', 'download', 'upload', 'send',
            'publish', 'hide', 'show', 'view', 'grade', 'enroll', 'access', 'login', 'logout', 'configure',

            // Common words.
            'ayuda', 'como', 'cuando', 'donde', 'porque', 'que', 'cual', 'quien', 'puedo', 'quiero',
            'necesito', 'tengo', 'problema', 'error', 'funciona', 'sirve', 'permite', 'debe', 'puede',
            'help', 'what', 'when', 'where', 'which', 'want', 'need', 'have', 'problem', 'error',
            'works', 'allows', 'should', 'could', 'would',
        ];

        foreach ($moodleterms as $term) {
            $this->add_word($term, 10); // High frequency for Moodle terms.
        }

        // Common Spanish words.
        $spanishwords = [
            'esto', 'esta', 'estos', 'estas', 'hacer', 'hago', 'hace', 'hacen', 'hice', 'hicieron',
            'debo', 'debes', 'debe', 'deben', 'puedo', 'puedes', 'puede', 'pueden', 'quiero', 'quieres',
            'quiere', 'quieren', 'tengo', 'tienes', 'tiene', 'tienen', 'saber', 'sabes', 'sabe', 'saben',
            'encontrar', 'encuentro', 'encuentra', 'encuentran', 'buscar', 'busco', 'busca', 'buscan',
            'utilizar', 'utilizo', 'utiliza', 'utilizan', 'usar', 'uso', 'usa', 'usan',
        ];

        foreach ($spanishwords as $word) {
            $this->add_word($word, 5); // Medium frequency.
        }

        // Common English words.
        $englishwords = [
            'this', 'that', 'these', 'those', 'make', 'makes', 'made', 'find', 'finds', 'found',
            'search', 'searches', 'searched', 'using', 'used',
        ];

        foreach ($englishwords as $word) {
            $this->add_word($word, 5); // Medium frequency.
        }

        // Initialize common misspellings map.
        $this->commonerrors = [
            // Spanish.
            'cursos' => 'curso',
            'curs' => 'curso',
            'actividd' => 'actividad',
            'activida' => 'actividad',
            'trea' => 'tarea',
            'tareea' => 'tarea',
            'cuestionrio' => 'cuestionario',
            'cuestionaio' => 'cuestionario',
            'califcacion' => 'calificacion',
            'calificcion' => 'calificacion',
            'estudinte' => 'estudiante',
            'estudiante' => 'estudiante',
            'profsor' => 'profesor',
            'prfesor' => 'profesor',
            'usurio' => 'usuario',
            'usario' => 'usuario',

            // English.
            'cours' => 'course',
            'corse' => 'course',
            'activty' => 'activity',
            'assigment' => 'assignment',
            'asignment' => 'assignment',
            'quizz' => 'quiz',
            'gade' => 'grade',
            'stuent' => 'student',
            'teachr' => 'teacher',
            'usr' => 'user',
        ];

        $this->initialized = true;
    }

    /**
     * Ensures the dictionary is initialized.
     */
    protected function ensure_initialized(): void {
        if (!$this->initialized) {
            $this->initialize_dictionary();
        }
    }

    /**
     * Returns dictionary statistics.
     *
     * @return array<string,int> Statistics
     */
    public function get_statistics(): array {
        $this->ensure_initialized();

        return [
            'dictionary_size' => count($this->dictionary),
            'common_errors' => count($this->commonerrors),
            'total_frequency' => array_sum($this->dictionary),
        ];
    }

    /**
     * Exports the dictionary for caching or persistence.
     *
     * @return array{dictionary:array,commonerrors:array}
     */
    public function export_dictionary(): array {
        $this->ensure_initialized();

        return [
            'dictionary' => $this->dictionary,
            'commonerrors' => $this->commonerrors,
        ];
    }

    /**
     * Imports a previously exported dictionary.
     *
     * @param array $data Exported dictionary data
     */
    public function import_dictionary(array $data): void {
        if (isset($data['dictionary']) && is_array($data['dictionary'])) {
            $this->dictionary = $data['dictionary'];
        }
        if (isset($data['commonerrors']) && is_array($data['commonerrors'])) {
            $this->commonerrors = $data['commonerrors'];
        }
        $this->initialized = true;
    }
}
