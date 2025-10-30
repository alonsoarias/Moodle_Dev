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
 * Enhanced NLP pipeline with n-grams, improved stemming, and integration with new NLP components.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use local_educambot\nlp\pipeline;
use local_educambot\nlp\intent_detector;
use local_educambot\nlp\spell_corrector;

/**
 * Extended pipeline with advanced NLP features.
 */
class enhanced_pipeline extends pipeline {
    /** @var intent_detector Intent detection system */
    protected ?intent_detector $intentdetector = null;

    /** @var spell_corrector Spell correction system */
    protected ?spell_corrector $spellcorrector = null;

    /** @var bool Whether to enable spell correction */
    protected bool $enablespellcheck = true;

    /** @var bool Whether to enable intent detection */
    protected bool $enableintentdetection = true;

    /** @var array<string,string> Abbreviation expansion map */
    protected array $abbreviations = [];

    /**
     * Constructor.
     *
     * @param array<string>|null $stopwords Custom stopwords
     * @param bool $enablespellcheck Enable spell checking
     * @param bool $enableintentdetection Enable intent detection
     */
    public function __construct(
        ?array $stopwords = null,
        bool $enablespellcheck = true,
        bool $enableintentdetection = true
    ) {
        parent::__construct($stopwords);

        $this->enablespellcheck = $enablespellcheck;
        $this->enableintentdetection = $enableintentdetection;

        if ($this->enablespellcheck) {
            $this->spellcorrector = new spell_corrector();
        }

        if ($this->enableintentdetection) {
            $this->intentdetector = new intent_detector();
        }

        $this->initialize_abbreviations();
    }

    /**
     * Enhanced process method with n-grams, intent, and spell check.
     *
     * @param string $text Input text
     * @return array{
     *     original:string,
     *     normalised:string,
     *     tokens:array<int,string>,
     *     keywords:array<int,string>,
     *     entities:array<string,array>,
     *     ngrams:array<string,array>,
     *     intent:array|null,
     *     spelling:array|null,
     *     language:string
     * }
     */
    public function process(string $text): array {
        // Expand abbreviations first.
        $expanded = $this->expand_abbreviations($text);

        // Spell check if enabled.
        $spelling = null;
        $correctedtext = $expanded;
        if ($this->enablespellcheck && $this->spellcorrector) {
            $spelling = $this->spellcorrector->correct_text($expanded, 3);
            if ($spelling['has_errors'] && !empty($spelling['corrected'])) {
                $correctedtext = $spelling['corrected'];
            }
        }

        // Run base pipeline on corrected text.
        $base = parent::process($correctedtext);

        // Extract n-grams.
        $ngrams = $this->extract_ngrams($base['tokens']);

        // Detect language.
        $language = $this->detect_language($text, $base['tokens']);

        // Detect intent if enabled.
        $intent = null;
        if ($this->enableintentdetection && $this->intentdetector) {
            $intent = $this->intentdetector->detect($correctedtext, $base);
        }

        return [
            'original' => $text,
            'normalised' => $base['normalised'],
            'tokens' => $base['tokens'],
            'keywords' => $base['keywords'],
            'entities' => $base['entities'],
            'ngrams' => $ngrams,
            'intent' => $intent,
            'spelling' => $spelling,
            'language' => $language,
        ];
    }

    /**
     * Improved Spanish stemmer with more morphological rules.
     *
     * @param string $token Token to stem
     * @return string Stemmed token
     */
    public function stem(string $token): string {
        $token = trim($token);
        if ($token === '' || strlen($token) <= 3) {
            return $token;
        }

        $original = $token;

        // Step 1: Remove plural suffixes.
        $token = $this->remove_plural_suffix($token);

        // Step 2: Remove diminutive/augmentative suffixes.
        $token = $this->remove_diminutive_suffix($token);

        // Step 3: Remove adverbial suffix -mente.
        if (preg_match('/mente$/u', $token) && strlen($token) > 7) {
            $token = preg_replace('/mente$/u', '', $token);
        }

        // Step 4: Remove verb suffixes (gerunds, infinitives, participles).
        $token = $this->remove_verb_suffix($token);

        // Step 5: Remove derivational suffixes.
        $token = $this->remove_derivational_suffix($token);

        // Ensure we didn't over-stem (minimum length 3).
        if (strlen($token) < 3) {
            return $original;
        }

        return trim($token);
    }

    /**
     * Removes plural suffixes from Spanish words.
     *
     * @param string $word Word
     * @return string Word without plural suffix
     */
    protected function remove_plural_suffix(string $word): string {
        // Remove -ces (e.g., luces → luz).
        if (preg_match('/ces$/u', $word) && strlen($word) > 4) {
            return preg_replace('/ces$/u', 'z', $word);
        }

        // Remove -es after consonant (e.g., profesores → profesor).
        if (preg_match('/([^aeiou])es$/u', $word) && strlen($word) > 4) {
            return preg_replace('/es$/u', '', $word);
        }

        // Remove -s after vowel (e.g., cursos → curso).
        if (preg_match('/([aeiou])s$/u', $word) && strlen($word) > 3) {
            return preg_replace('/s$/u', '', $word);
        }

        return $word;
    }

    /**
     * Removes diminutive/augmentative suffixes.
     *
     * @param string $word Word
     * @return string Word without diminutive suffix
     */
    protected function remove_diminutive_suffix(string $word): string {
        $diminutives = ['ito', 'ita', 'itos', 'itas', 'ico', 'ica', 'icos', 'icas',
            'illo', 'illa', 'illos', 'illas', 'ote', 'ota', 'oton', 'otona'];

        foreach ($diminutives as $suffix) {
            if (preg_match("/{$suffix}$/u", $word) && strlen($word) > strlen($suffix) + 2) {
                return preg_replace("/{$suffix}$/u", '', $word);
            }
        }

        return $word;
    }

    /**
     * Removes verb suffixes (gerunds, infinitives, participles).
     *
     * @param string $word Word
     * @return string Word without verb suffix
     */
    protected function remove_verb_suffix(string $word): string {
        // Gerunds: -ando, -iendo, -yendo.
        if (preg_match('/(ando|iendo|yendo)$/u', $word) && strlen($word) > 6) {
            return preg_replace('/(ando|iendo|yendo)$/u', '', $word);
        }

        // Past participles: -ado, -ido.
        if (preg_match('/(ado|ido)$/u', $word) && strlen($word) > 5) {
            return preg_replace('/(ado|ido)$/u', '', $word);
        }

        // Infinitives: -ar, -er, -ir.
        if (preg_match('/(ar|er|ir)$/u', $word) && strlen($word) > 4) {
            return preg_replace('/(ar|er|ir)$/u', '', $word);
        }

        // Future/conditional: -aré, -eré, -iré, -aría, -ería, -iría.
        if (preg_match('/(are|ere|ire|aria|eria|iria)$/u', $word) && strlen($word) > 6) {
            return preg_replace('/(are|ere|ire|aria|eria|iria)$/u', '', $word);
        }

        return $word;
    }

    /**
     * Removes derivational suffixes.
     *
     * @param string $word Word
     * @return string Word without derivational suffix
     */
    protected function remove_derivational_suffix(string $word): string {
        $suffixes = ['cion', 'sion', 'miento', 'idad', 'mente', 'ancia', 'encia',
            'dor', 'dora', 'able', 'ible', 'ante', 'ente'];

        foreach ($suffixes as $suffix) {
            if (preg_match("/{$suffix}$/u", $word) && strlen($word) > strlen($suffix) + 2) {
                return preg_replace("/{$suffix}$/u", '', $word);
            }
        }

        return $word;
    }

    /**
     * Extracts n-grams (bi-grams and tri-grams) from tokens.
     *
     * @param array<int,string> $tokens Tokens
     * @return array{bigrams:array,trigrams:array}
     */
    protected function extract_ngrams(array $tokens): array {
        $bigrams = [];
        $trigrams = [];

        $count = count($tokens);

        // Extract bi-grams.
        for ($i = 0; $i < $count - 1; $i++) {
            $bigram = $tokens[$i] . ' ' . $tokens[$i + 1];
            $bigrams[] = $bigram;
        }

        // Extract tri-grams.
        for ($i = 0; $i < $count - 2; $i++) {
            $trigram = $tokens[$i] . ' ' . $tokens[$i + 1] . ' ' . $tokens[$i + 2];
            $trigrams[] = $trigram;
        }

        return [
            'bigrams' => array_values(array_unique($bigrams)),
            'trigrams' => array_values(array_unique($trigrams)),
        ];
    }

    /**
     * Detects the likely language of the text (ES or EN).
     *
     * @param string $text Original text
     * @param array<int,string> $tokens Tokens
     * @return string Language code ('es' or 'en')
     */
    protected function detect_language(string $text, array $tokens): string {
        $spanishindicators = ['que', 'como', 'cuando', 'donde', 'porque', 'por', 'para', 'con', 'sin',
            'pero', 'tambien', 'muy', 'mas', 'menos', 'este', 'esta', 'estos', 'estas'];

        $englishindicators = ['what', 'how', 'when', 'where', 'why', 'with', 'without', 'but', 'also',
            'very', 'more', 'less', 'this', 'that', 'these', 'those'];

        $spanishcount = 0;
        $englishcount = 0;

        foreach ($tokens as $token) {
            if (in_array($token, $spanishindicators, true)) {
                $spanishcount++;
            }
            if (in_array($token, $englishindicators, true)) {
                $englishcount++;
            }
        }

        // Check for Spanish-specific characters.
        if (preg_match('/[áéíóúñü]/iu', $text)) {
            $spanishcount += 2;
        }

        return $spanishcount >= $englishcount ? 'es' : 'en';
    }

    /**
     * Expands common abbreviations in text.
     *
     * @param string $text Text with abbreviations
     * @return string Text with expanded abbreviations
     */
    protected function expand_abbreviations(string $text): array {
        $expanded = $text;

        foreach ($this->abbreviations as $abbr => $full) {
            // Use word boundaries to avoid partial matches.
            $pattern = '/\b' . preg_quote($abbr, '/') . '\b/i';
            $expanded = preg_replace($pattern, $full, $expanded);
        }

        return $expanded;
    }

    /**
     * Initializes common abbreviations map.
     */
    protected function initialize_abbreviations(): void {
        $this->abbreviations = [
            // Spanish.
            'prof' => 'profesor',
            'profe' => 'profesor',
            'est' => 'estudiante',
            'act' => 'actividad',
            'cal' => 'calificacion',
            'eval' => 'evaluacion',
            'matric' => 'matricula',
            'config' => 'configuracion',
            'admin' => 'administrador',
            'info' => 'informacion',

            // English.
            'prof' => 'professor',
            'student' => 'student',
            'assign' => 'assignment',
            'config' => 'configuration',
            'admin' => 'administrator',
            'info' => 'information',
        ];
    }

    /**
     * Gets the intent detector instance.
     *
     * @return intent_detector|null
     */
    public function get_intent_detector(): ?intent_detector {
        return $this->intentdetector;
    }

    /**
     * Gets the spell corrector instance.
     *
     * @return spell_corrector|null
     */
    public function get_spell_corrector(): ?spell_corrector {
        return $this->spellcorrector;
    }
}
