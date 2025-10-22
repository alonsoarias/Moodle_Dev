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
 * Lightweight natural language processing pipeline for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\nlp;

use core_text;

/**
 * Executes normalisation, tokenisation, stopword removal and stemming.
 */
class pipeline {
    /** @var array<string> */
    protected array $stopwords;

    /** @var array<string,string> */
    protected array $accentmap = [
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'Ü' => 'U', 'ü' => 'u', 'Ñ' => 'N', 'ñ' => 'n',
    ];

    /**
     * Constructor.
     *
     * @param array<string>|null $stopwords
     */
    public function __construct(?array $stopwords = null) {
        $this->stopwords = $stopwords ?? $this->load_default_stopwords();
    }

    /**
     * Process a text input and returns the extracted artefacts.
     *
     * @param string $text
     * @return array{
     *     original:string,
     *     normalised:string,
     *     tokens:array<int,string>,
     *     keywords:array<int,string>,
     *     entities:array<string,array>
     * }
     */
    public function process(string $text): array {
        $normalised = $this->normalise($text);
        $tokens = $this->tokenise($normalised);
        $keywords = $this->filter_stopwords($tokens);
        $stemmed = array_map([$this, 'stem'], $keywords);
        $stemmed = array_values(array_unique(array_filter($stemmed, static function(string $token): bool {
            return $token !== '';
        })));

        $entities = $this->extract_entities($text, $tokens);

        return [
            'original' => $text,
            'normalised' => $normalised,
            'tokens' => $tokens,
            'keywords' => $stemmed,
            'entities' => $entities,
        ];
    }

    /**
     * Normalises text (lowercase, accents, punctuation, trimming).
     *
     * @param string $text
     * @return string
     */
    public function normalise(string $text): string {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $text = strtr($text, $this->accentmap);
        $text = core_text::strtolower($text);
        // Replace punctuation with spaces but preserve slashes for dates.
        $text = preg_replace('/[^\p{L}\p{N}\/\-\s]/u', ' ', $text);
        $text = preg_replace('/\s{2,}/', ' ', (string)$text);
        return trim((string)$text);
    }

    /**
     * Tokenises a normalised text.
     *
     * @param string $text
     * @return array<int,string>
     */
    public function tokenise(string $text): array {
        if ($text === '') {
            return [];
        }
        $tokens = preg_split('/[\s,;:]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$tokens) {
            return [];
        }
        return array_values(array_map('trim', $tokens));
    }

    /**
     * Removes stopwords from a token collection.
     *
     * @param array<int,string> $tokens
     * @return array<int,string>
     */
    public function filter_stopwords(array $tokens): array {
        if (empty($tokens)) {
            return [];
        }
        $stopwords = $this->stopwords;
        return array_values(array_filter($tokens, static function(string $token) use ($stopwords): bool {
            return !in_array($token, $stopwords, true);
        }));
    }

    /**
     * Applies a lightweight Spanish stemmer.
     *
     * @param string $token
     * @return string
     */
    public function stem(string $token): string {
        $token = trim($token);
        if ($token === '') {
            return '';
        }
        // Remove plural endings.
        if (preg_match('/(iones|iones)$|((?<![aeiou])es)$/u', $token)) {
            $token = preg_replace('/(iones|iones)$/u', 'ion', $token);
            $token = preg_replace('/((?<![aeiou])es)$/u', '', $token);
        } elseif (preg_match('/(as|os)$/u', $token)) {
            $token = mb_substr($token, 0, -1);
        }
        // Remove gerund and infinitive suffixes.
        $token = preg_replace('/(ando|iendo|yendo|aremos|eremos|iremos|aria|eria|iria)$/u', '', $token);
        $token = preg_replace('/(ar|er|ir)$/u', '', $token);

        return trim((string)$token);
    }

    /**
     * Extracts simple entities like course codes, activities or dates.
     *
     * @param string $original
     * @param array<int,string> $tokens
     * @return array<string,array>
     */
    protected function extract_entities(string $original, array $tokens): array {
        $entities = [
            'courses' => [],
            'activities' => [],
            'dates' => [],
            'numbers' => [],
        ];

        foreach ($tokens as $token) {
            if (preg_match('/^[A-Z]{2,}[0-9]{2,}$/u', $token) || preg_match('/^[A-Z]{3,}-[0-9]{2,}$/u', $token)) {
                $entities['courses'][] = $token;
                continue;
            }
            if (preg_match('/^(tarea|quiz|cuestionario|foro|h5p|workshop|taller|wiki|glosario)$/u', $token)) {
                $entities['activities'][] = $token;
                continue;
            }
            if (preg_match('/^[0-9]{1,2}\/[0-9]{1,2}\/[0-9]{2,4}$/', $token) || preg_match('/^[0-9]{1,2}-[0-9]{1,2}-[0-9]{2,4}$/', $token)) {
                $entities['dates'][] = $token;
                continue;
            }
            if (preg_match('/^[0-9]+$/', $token)) {
                $entities['numbers'][] = $token;
            }
        }

        // Detect explicit dates within original text (e.g. "15 de marzo").
        if (preg_match_all('/([0-9]{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)/iu',
            $original, $matches)) {
            foreach ($matches[0] as $match) {
                $entities['dates'][] = core_text::strtolower(trim($match));
            }
        }

        foreach ($entities as $key => $list) {
            $entities[$key] = array_values(array_unique($list));
        }

        return $entities;
    }

    /**
     * Loads default Spanish stopwords enriched with key Moodle terminology.
     *
     * @return array<int,string>
     */
    protected function load_default_stopwords(): array {
        $stopwords = [
            'a', 'acá', 'ahí', 'ajena', 'ajeno', 'al', 'algo', 'alguna', 'algunas', 'alguno', 'algunos', 'allí', 'allá', 'ambas',
            'ambos', 'ante', 'antes', 'aquel', 'aquella', 'aquellas', 'aquello', 'aquellos', 'aquí', 'arriba', 'así', 'aun',
            'aún', 'aunque', 'bajo', 'bastante', 'bien', 'cada', 'casi', 'como', 'con', 'contra', 'cual', 'cuales', 'cualquier',
            'cuáles', 'cuando', 'cuanta', 'cuantas', 'cuanto', 'cuantos', 'cuánta', 'cuántas', 'cuánto', 'cuántos', 'de', 'dejar',
            'del', 'demás', 'dentro', 'desde', 'donde', 'dos', 'el', 'ella', 'ellas', 'ellos', 'en', 'encima', 'entre', 'era',
            'erais', 'eran', 'eras', 'eres', 'es', 'esa', 'esas', 'ese', 'eso', 'esos', 'esta', 'estaba', 'estaban', 'estado',
            'estar', 'estas', 'este', 'estos', 'estoy', 'fue', 'fueron', 'fui', 'ha', 'había', 'habrán', 'habrás', 'habré', 'habréis',
            'habremos', 'habría', 'habríais', 'habríamos', 'habrían', 'habrías', 'han', 'hasta', 'hay', 'incluso', 'la', 'las',
            'le', 'les', 'lo', 'los', 'más', 'me', 'mi', 'mis', 'mucho', 'muy', 'nada', 'ni', 'no', 'nos', 'nosotras', 'nosotros',
            'nuestra', 'nuestras', 'nuestro', 'nuestros', 'o', 'os', 'otra', 'otras', 'otro', 'otros', 'para', 'pero', 'poco',
            'por', 'porque', 'que', 'qué', 'quien', 'quienes', 'se', 'sea', 'sean', 'según', 'ser', 'si', 'sí', 'siempre', 'sin',
            'sois', 'solamente', 'solo', 'somos', 'son', 'su', 'sus', 'tal', 'tales', 'también', 'tampoco', 'tan', 'tanta', 'tantas',
            'tanto', 'tantos', 'te', 'tenéis', 'tenemos', 'tener', 'tengo', 'ti', 'tiene', 'tienen', 'toda', 'todas', 'todo',
            'todos', 'tu', 'tus', 'un', 'una', 'uno', 'unos', 'vos', 'vosotras', 'vosotros', 'y', 'ya',
            // Moodle specific filler words.
            'moodle', 'plataforma', 'curso', 'cursos', 'actividad', 'actividades', 'tarea', 'tareas', 'nota', 'notas',
        ];

        return array_values(array_unique(array_map(static function(string $token): string {
            return core_text::strtolower($token);
        }, $stopwords)));
    }
}
