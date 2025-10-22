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
 * Parser that transforms the deep analysis document into structured data.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local\setup;

use core_text;
use moodle_exception;

/**
 * Extracts FAQs, patterns and relationships from the deep analysis document.
 */
class deep_dataset_loader {
    /**
     * Parses the document and returns the dataset.
     *
     * @param string $filepath
     * @return array{
     *     categories:array<int,array{identifier:string,name:string,faqs:array<int,array>}>}
     */
    public function load(string $filepath): array {
        if (!is_readable($filepath)) {
            throw new moodle_exception('filenotfound', 'error', '', $filepath);
        }
        $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new moodle_exception('cannotreadfile', 'error', '', $filepath);
        }

        $categories = [];
        $currentcategory = null;
        $currentfaq = null;

        $flushfaq = function() use (&$currentfaq, &$currentcategory, &$categories): void {
            if ($currentcategory === null || $currentfaq === null) {
                return;
            }
            if (!isset($categories[$currentcategory])) {
                return;
            }
            $categories[$currentcategory]['faqs'][] = $currentfaq;
            $currentfaq = null;
        };

        foreach ($lines as $rawline) {
            $line = trim($rawline);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^###\s+Categoría\s+\d+\s*:\s*(.+)$/u', $line, $matches)) {
                $flushfaq();
                $name = trim($matches[1]);
                $identifier = core_text::strtolower(preg_replace('/[^a-z0-9]+/u', '_', core_text::specialtoascii($name)));
                $categories[$identifier] = [
                    'identifier' => $identifier,
                    'name' => $name,
                    'faqs' => [],
                ];
                $currentcategory = $identifier;
                continue;
            }
            if (!preg_match('/^####\s+FAQ\s+#(\d+)/', $line, $matches)) {
                if ($currentfaq === null) {
                    continue;
                }
                if (str_starts_with($line, 'Pregunta principal:')) {
                    $currentfaq['question'] = trim(substr($line, strlen('Pregunta principal:')));
                } else if (str_starts_with($line, 'Patrones de matching:')) {
                    $currentfaq['section'] = 'patterns';
                } else if (str_starts_with($line, 'Keywords:')) {
                    $keywords = trim($line);
                    $keywords = trim(substr($keywords, strlen('Keywords:')));
                    $keywords = trim($keywords, '[]');
                    $currentfaq['keywords'] = $this->split_list($keywords);
                    $currentfaq['section'] = null;
                } else if (str_starts_with($line, 'Sinónimos relevantes:')) {
                    $currentfaq['section'] = 'synonyms';
                } else if (str_starts_with($line, 'Respuesta:')) {
                    $currentfaq['section'] = 'answer';
                    $currentfaq['answer'] = '';
                } else if (str_starts_with($line, 'Prioridad:')) {
                    $currentfaq['priority'] = trim(substr($line, strlen('Prioridad:')));
                    $currentfaq['section'] = null;
                } else if (str_starts_with($line, 'Conocimientos relacionados:')) {
                    $currentfaq['section'] = 'related';
                    if (!isset($currentfaq['related'])) {
                        $currentfaq['related'] = [];
                    }
                } else if (str_starts_with($line, '- ')) {
                    $content = trim(substr($line, 2));
                    $section = $currentfaq['section'] ?? null;
                    if ($section === 'patterns') {
                        $currentfaq['patterns'][] = $content;
                    } else if ($section === 'synonyms') {
                        $currentfaq['synonyms'][] = $content;
                    } else if ($section === 'related') {
                        if (preg_match('/FAQ\s+#(\d+)/', $content, $relmatch)) {
                            $currentfaq['related'][] = (int)$relmatch[1];
                        }
                    } else if ($section === 'answer') {
                        $currentfaq['answer'] .= ($currentfaq['answer'] === '' ? '' : "\n") . $content;
                    }
                } else {
                    if (($currentfaq['section'] ?? '') === 'answer') {
                        $currentfaq['answer'] .= ($currentfaq['answer'] === '' ? '' : "\n") . $line;
                    }
                }
                continue;
            }

            $flushfaq();
            $faqid = (int)$matches[1];
            $currentfaq = [
                'id' => $faqid,
                'patterns' => [],
                'synonyms' => [],
                'keywords' => [],
                'answer' => '',
                'priority' => 'Media',
                'related' => [],
            ];
        }
        $flushfaq();

        return ['categories' => array_values($categories)];
    }

    /**
     * Splits list definitions separated by commas.
     *
     * @param string $text
     * @return array<int,string>
     */
    protected function split_list(string $text): array {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $items = preg_split('/[,;]+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        if (!$items) {
            return [];
        }
        return array_values(array_map('trim', $items));
    }
}
