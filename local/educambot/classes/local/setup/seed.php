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
 * Seeds initial rules and knowledge entries for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local\setup;

use coding_exception;
use core_text;
use dml_exception;
use html_writer;
use local_educambot\local\knowledge_repository;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Populates the plugin with an initial catalogue of FAQs and knowledge.
 */
class seed {
    /**
     * Inserts default data when the knowledge base is empty.
     *
     * @throws dml_exception
     * @throws coding_exception
     * @throws moodle_exception
     */
    public static function seed_initial_data(): void {
        global $DB;

        if ($DB->record_exists('local_educambot_knowledge', [])) {
            return;
        }

        $dataset = self::get_dataset();
        if (empty($dataset['categories'])) {
            return;
        }

        $now = time();
        $transaction = $DB->start_delegated_transaction();

        $topicmap = self::ensure_topics_exist($dataset['categories'], $now);
        [$faqmap, $relations] = self::create_from_dataset($dataset['categories'], $topicmap, $now);

        self::create_relations($relations, $faqmap);

        knowledge_repository::reset_caches();
        $transaction->allow_commit();
    }

    /**
     * Loads the structured dataset from the analysis document.
     *
     * @return array
     * @throws moodle_exception
     */
    protected static function get_dataset(): array {
        $loader = new deep_dataset_loader();
        $filepath = dirname(__DIR__, 3) . '/docs/deep_analysis.md';
        return $loader->load($filepath);
    }

    /**
     * Ensures topics for every category exist.
     *
     * @param array<int,array> $categories
     * @param int $now
     * @return array<string,int>
     * @throws dml_exception
     */
    protected static function ensure_topics_exist(array $categories, int $now): array {
        global $DB;

        $topicmap = [];
        foreach ($categories as $category) {
            $identifier = $category['identifier'];
            $name = $category['name'];
            $existing = $DB->get_record('local_educambot_topic', ['name' => $name], 'id');
            if ($existing) {
                $topicmap[$identifier] = (int)$existing->id;
                continue;
            }
            $record = (object) [
                'name' => $name,
                'description' => 'Categoría inicial importada desde deep_analysis.md.',
                'parentid' => null,
                'sortorder' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $topicmap[$identifier] = $DB->insert_record('local_educambot_topic', $record);
        }
        return $topicmap;
    }

    /**
     * Creates knowledge, rules and collects relations.
     *
     * @param array<int,array> $categories
     * @param array<string,int> $topicmap
     * @param int $now
     * @return array{0:array<int,array>,1:array<int,array<int>>}
     * @throws dml_exception
     */
    protected static function create_from_dataset(array $categories, array $topicmap, int $now): array {
        global $DB;

        $faqmap = [];
        $relations = [];

        foreach ($categories as $category) {
            $topicid = $topicmap[$category['identifier']] ?? null;
            if (!$topicid) {
                continue;
            }
            foreach ($category['faqs'] as $faq) {
                $question = trim($faq['question'] ?? '');
                if ($question === '') {
                    continue;
                }
                $answer = trim($faq['answer'] ?? '');
                $htmlanswer = self::format_answer_html($answer);
                $summary = self::format_summary($answer);
                $priority = core_text::strtolower(trim($faq['priority'] ?? 'Media'));

                $knowledge = (object) [
                    'title' => $question,
                    'summary' => $summary,
                    'content' => $htmlanswer,
                    'contentformat' => FORMAT_HTML,
                    'type' => 'faq',
                    'externalurl' => null,
                    'tags' => implode(', ', $faq['keywords'] ?? []),
                    'enabled' => 1,
                    'createdby' => null,
                    'updatedby' => null,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $knowledgeid = $DB->insert_record('local_educambot_knowledge', $knowledge);

                $DB->insert_record('local_educambot_kn_topic', (object) [
                    'knowledgeid' => $knowledgeid,
                    'topicid' => $topicid,
                ]);

                $patternlines = array_merge([$question], $faq['patterns'] ?? []);
                $patternlines = array_values(array_unique(array_filter(array_map('trim', $patternlines))));
                $patterntext = implode("\n", $patternlines);

                $synonyms = array_values(array_unique(array_filter($faq['synonyms'] ?? [])));
                $synonymtext = implode("\n", $synonyms);

                $keywords = array_values(array_unique(array_filter($faq['keywords'] ?? [])));
                $keywordstext = implode(', ', $keywords);

                $rule = (object) [
                    'pattern' => $patterntext,
                    'synonyms' => $synonymtext,
                    'keywords' => $keywordstext,
                    'response' => $htmlanswer,
                    'roles' => null,
                    'contexts' => null,
                    'suggested' => $priority === 'alta' ? 1 : 0,
                    'enabled' => 1,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $ruleid = $DB->insert_record('local_educambot_rule', $rule);

                $faqmap[$faq['id']] = [
                    'knowledgeid' => $knowledgeid,
                    'ruleid' => $ruleid,
                ];

                if (!empty($faq['related'])) {
                    $relations[$faq['id']] = $faq['related'];
                }
            }
        }

        return [$faqmap, $relations];
    }

    /**
     * Creates relation records between knowledge entries.
     *
     * @param array<int,array<int>> $relations
     * @param array<int,array> $faqmap
     * @throws dml_exception
     */
    protected static function create_relations(array $relations, array $faqmap): void {
        global $DB;

        $created = [];
        foreach ($relations as $sourcefaq => $targets) {
            if (!isset($faqmap[$sourcefaq])) {
                continue;
            }
            $sourceid = $faqmap[$sourcefaq]['knowledgeid'];
            foreach ($targets as $targetfaq) {
                if (!isset($faqmap[$targetfaq])) {
                    continue;
                }
                $targetid = $faqmap[$targetfaq]['knowledgeid'];
                if ($sourceid === $targetid) {
                    continue;
                }
                $key = $sourceid . ':' . $targetid;
                if (isset($created[$key])) {
                    continue;
                }
                $DB->insert_record('local_educambot_relation', (object) [
                    'sourceid' => $sourceid,
                    'targetid' => $targetid,
                    'relationtype' => 'related',
                ]);
                $created[$key] = true;
            }
        }
    }

    /**
     * Builds HTML paragraphs for the stored answer.
     *
     * @param string $answer
     * @return string
     */
    protected static function format_answer_html(string $answer): string {
        $answer = trim($answer);
        if ($answer === '') {
            return html_writer::tag('p', '');
        }
        $paragraphs = preg_split('/\n{2,}/', $answer) ?: [$answer];
        $html = '';
        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }
            $lines = preg_split('/\n/', $paragraph) ?: [$paragraph];
            $text = trim(implode(' ', array_map('trim', $lines)));
            $html .= html_writer::tag('p', s($text));
        }
        return $html;
    }

    /**
     * Generates a concise summary for the knowledge entry.
     *
     * @param string $answer
     * @return string
     */
    protected static function format_summary(string $answer): string {
        $answer = trim($answer);
        if ($answer === '') {
            return html_writer::tag('p', '');
        }
        $plain = preg_replace('/\s+/', ' ', $answer);
        $plain = trim((string)$plain);
        $snippet = core_text::substr($plain, 0, 200);
        if (core_text::strlen($plain) > 200) {
            $snippet .= '…';
        }
        return html_writer::tag('p', s($snippet));
    }
}
