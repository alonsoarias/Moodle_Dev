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
 * Plugin installation script - loads knowledge base from JSON files.
 *
 * EducamBot v3.2.0 - Complete JSON-based knowledge base architecture.
 *
 * This file reads data from db/data/*.json files and inserts them
 * into the database. This modular approach allows:
 * - Easy maintenance of the knowledge base
 * - Separation of data and logic
 * - Simpler translations
 * - Independent versioning of KB content
 * - NLP patterns loaded from external JSON files
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the plugin and seed initial data from JSON files.
 *
 * @return bool Success status
 */
function xmldb_local_educambot_install() {
    global $DB;

    $datapath = __DIR__ . '/data/';
    $now = time();

    try {
        // Step 1: Install categories.
        $catids = install_categories_from_json($datapath . 'categories.json', $now);

        // Step 2: Install navigation rules.
        install_rules_from_json($datapath . 'navigation.json', $catids, $now);

        // Step 3: Install knowledge base rules from all files in knowledge/.
        $knowledgefiles = glob($datapath . 'knowledge/*.json');
        foreach ($knowledgefiles as $file) {
            install_rules_from_json($file, $catids, $now);
        }

        // Step 4: Install shortcuts.
        install_shortcuts_from_json($datapath . 'shortcuts.json', $now);

        // Step 5: Install themes.
        install_themes_from_json($datapath . 'themes.json', $now);

        // Step 6: Install NLP patterns (v3.2.0).
        install_nlp_patterns($datapath, $now);

        return true;

    } catch (Exception $e) {
        debugging('EducamBot install error: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Read and parse a JSON file.
 *
 * @param string $filepath Path to JSON file
 * @return array Decoded JSON data
 * @throws moodle_exception If file not found or invalid JSON
 */
function read_json_file($filepath) {
    if (!file_exists($filepath)) {
        throw new moodle_exception('filenotfound', 'local_educambot', '', $filepath);
    }

    $json = file_get_contents($filepath);
    $data = json_decode($json, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new moodle_exception('jsonparseerror', 'local_educambot', '', json_last_error_msg());
    }

    return $data;
}

/**
 * Install categories from JSON file.
 *
 * @param string $filepath Path to categories.json
 * @param int $now Current timestamp
 * @return array Map of category ID => database ID
 */
function install_categories_from_json($filepath, $now) {
    global $DB;

    $data = read_json_file($filepath);
    $catids = [];

    foreach ($data['categories'] as $category) {
        $record = new stdClass();
        $record->name = $category['name'];
        $record->description = $category['description'] ?? '';
        $record->parent = null;
        $record->sortorder = $category['sortorder'] ?? 0;
        $record->enabled = $category['enabled'] ? 1 : 0;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $catids[$category['id']] = $DB->insert_record('local_educambot_category', $record);
    }

    return $catids;
}

/**
 * Install rules from JSON file.
 *
 * @param string $filepath Path to rules JSON file
 * @param array $catids Category ID mapping
 * @param int $now Current timestamp
 */
function install_rules_from_json($filepath, $catids, $now) {
    global $DB;

    $data = read_json_file($filepath);

    if (!isset($data['rules'])) {
        return;
    }

    foreach ($data['rules'] as $rule) {
        // Get category ID.
        $categoryid = isset($catids[$rule['category']]) ? $catids[$rule['category']] : null;

        // Convert keywords array to newline-separated string.
        $keywords = is_array($rule['keywords']) ? implode("\n", $rule['keywords']) : $rule['keywords'];

        // Convert tags array to comma-separated string.
        $tags = is_array($rule['tags']) ? implode(', ', $rule['tags']) : ($rule['tags'] ?? '');

        $record = new stdClass();
        $record->categoryid = $categoryid;
        $record->pattern = $rule['pattern'];
        $record->keywords = $keywords;
        $record->response = $rule['response'];
        $record->tags = $tags;
        $record->enabled = isset($rule['enabled']) && $rule['enabled'] ? 1 : 0;
        $record->showoptions = isset($rule['showoptions']) && $rule['showoptions'] ? 1 : 0;
        $record->dynamicresponse = isset($rule['dynamicresponse']) && $rule['dynamicresponse'] ? 1 : 0;
        $record->requiredcontext = $rule['requiredcontext'] ?? null;
        $record->lang = $rule['lang'] ?? $rule['language'] ?? 'es';
        // Map JSON 'archetypes' to DB 'roles' field (fix v3.6.0).
        $record->roles = isset($rule['archetypes']) ? implode(',', $rule['archetypes']) : null;
        $record->priority = $rule['priority'] ?? 0;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $ruleid = $DB->insert_record('local_educambot_rule', $record);

        // Insert options if present.
        if (isset($rule['options']) && is_array($rule['options'])) {
            $sortorder = 1;
            foreach ($rule['options'] as $option) {
                $optrecord = new stdClass();
                $optrecord->ruleid = $ruleid;
                $optrecord->text = $option['text'];
                $optrecord->action = $option['action'] ?? '';
                $optrecord->icon = $option['icon'] ?? '';
                $optrecord->targetruleid = null;
                $optrecord->sortorder = $sortorder++;
                $optrecord->enabled = 1;

                $DB->insert_record('local_educambot_option', $optrecord);
            }
        }
    }
}

/**
 * Install shortcuts from JSON file.
 *
 * @param string $filepath Path to shortcuts.json
 * @param int $now Current timestamp
 */
function install_shortcuts_from_json($filepath, $now) {
    global $DB;

    $data = read_json_file($filepath);

    if (!isset($data['shortcuts'])) {
        return;
    }

    foreach ($data['shortcuts'] as $shortcut) {
        // Convert keywords array to newline-separated string.
        $keywords = is_array($shortcut['keywords']) ? implode("\n", $shortcut['keywords']) : $shortcut['keywords'];

        // Convert roles array to comma-separated string.
        $roles = null;
        if (isset($shortcut['roles']) && is_array($shortcut['roles'])) {
            $roles = implode(',', $shortcut['roles']);
        }

        $record = new stdClass();
        $record->name = $shortcut['name'];
        $record->keywords = $keywords;
        $record->actiontype = $shortcut['actiontype'];
        $record->description = $shortcut['description'] ?? null;
        $record->icon = $shortcut['icon'] ?? null;
        $record->roles = $roles;
        $record->context = $shortcut['context'] ?? 'any';
        $record->enabled = isset($shortcut['enabled']) && $shortcut['enabled'] ? 1 : 0;
        $record->sortorder = $shortcut['sortorder'] ?? 0;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $DB->insert_record('local_educambot_shortcut', $record);
    }
}

/**
 * Install themes from JSON file.
 *
 * @param string $filepath Path to themes.json
 * @param int $now Current timestamp
 */
function install_themes_from_json($filepath, $now) {
    global $DB;

    $data = read_json_file($filepath);

    if (!isset($data['themes'])) {
        return;
    }

    foreach ($data['themes'] as $theme) {
        $colors = $theme['colors'];

        $record = new stdClass();
        $record->name = $theme['name'];
        $record->primarycolor = $colors['primary'] ?? '#0f6fc5';
        $record->secondarycolor = $colors['secondary'] ?? '#084a8a';
        $record->textcolor = $colors['text'] ?? '#1f2937';
        $record->backgroundcolor = $colors['background'] ?? '#f9fafb';
        $record->usercolor = $colors['user'] ?? '#0f6fc5';
        $record->botcolor = $colors['bot'] ?? '#ffffff';
        $record->isdefault = isset($theme['isdefault']) && $theme['isdefault'] ? 1 : 0;

        // Widget icon configuration (v2.2.0).
        $widgeticon = $theme['widgeticon'] ?? [];
        $record->widgeticontype = $widgeticon['type'] ?? 'default';
        $record->widgeticonurl = $widgeticon['value'] ?? '';

        // Mascot configuration (v2.2.0).
        $mascot = $theme['mascot'] ?? [];
        $record->mascotenabled = isset($mascot['enabled']) && $mascot['enabled'] ? 1 : 0;
        $record->mascottype = $mascot['type'] ?? 'none';
        $record->mascoturl = null;

        $record->timecreated = $now;
        $record->timemodified = $now;

        $DB->insert_record('local_educambot_theme', $record);
    }
}

/**
 * Install NLP patterns from JSON files (v3.2.0).
 *
 * @param string $datapath Path to data directory
 * @param int $now Current timestamp
 */
function install_nlp_patterns($datapath, $now) {
    // Load structured patterns (intents, topics, sentiments).
    install_structured_patterns($datapath . 'intents.json', 'intent', $now);
    install_structured_patterns($datapath . 'topics.json', 'topic', $now);
    install_structured_patterns($datapath . 'sentiments.json', 'sentiment', $now);

    // Load simple patterns (full JSON as single record).
    install_simple_patterns($datapath . 'stopwords.json', 'stopword', $now);
    install_simple_patterns($datapath . 'abbreviations.json', 'abbreviation', $now);
    install_simple_patterns($datapath . 'synonyms.json', 'synonym', $now);
    install_simple_patterns($datapath . 'entities.json', 'entity', $now);
    install_simple_patterns($datapath . 'conversation.json', 'conversation', $now);
}

/**
 * Install structured patterns from JSON file.
 *
 * @param string $filepath Path to JSON file
 * @param string $type Pattern type (intent, topic, sentiment)
 * @param int $now Current timestamp
 */
function install_structured_patterns($filepath, $type, $now) {
    global $DB;

    if (!file_exists($filepath)) {
        return;
    }

    $json = file_get_contents($filepath);
    $data = json_decode($json, true);

    if (!$data) {
        return;
    }

    $lang = $data['lang'] ?? 'es';
    $patternsKey = $type . 's'; // intents, topics, sentiments.

    if (!isset($data[$patternsKey])) {
        return;
    }

    $sortorder = 0;
    foreach ($data[$patternsKey] as $key => $patternData) {
        $weight = $patternData['weight'] ?? $patternData['priority'] ?? 1.0;

        $record = new stdClass();
        $record->type = $type;
        $record->patternkey = $key;
        $record->patterndata = json_encode($patternData);
        $record->weight = $weight;
        $record->lang = $lang;
        $record->enabled = 1;
        $record->sortorder = $sortorder++;
        $record->timecreated = $now;
        $record->timemodified = $now;

        $DB->insert_record('local_educambot_pattern', $record);
    }
}

/**
 * Install simple patterns (full JSON as single record).
 *
 * @param string $filepath Path to JSON file
 * @param string $type Pattern type
 * @param int $now Current timestamp
 */
function install_simple_patterns($filepath, $type, $now) {
    global $DB;

    if (!file_exists($filepath)) {
        return;
    }

    $json = file_get_contents($filepath);
    $data = json_decode($json, true);

    if (!$data) {
        return;
    }

    $lang = $data['lang'] ?? 'es';

    $record = new stdClass();
    $record->type = $type;
    $record->patternkey = 'default';
    $record->patterndata = $json;
    $record->weight = 1.0;
    $record->lang = $lang;
    $record->enabled = 1;
    $record->sortorder = 0;
    $record->timecreated = $now;
    $record->timemodified = $now;

    $DB->insert_record('local_educambot_pattern', $record);
}
