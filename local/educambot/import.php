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
 * Import/Export page for knowledge base (v3.5.0).
 *
 * Supports both JSON and CSV file formats for importing and exporting.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

require_login();
require_capability('local/educambot:manage', context_system::instance());

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educambot/import.php'));
$PAGE->set_title(get_string('importexport', 'local_educambot'));
$PAGE->set_heading(get_string('importexport', 'local_educambot'));

admin_externalpage_setup('local_educambot_importexport');

/**
 * Import form class supporting JSON and CSV.
 */
class import_form extends moodleform {
    /**
     * Define form elements.
     */
    protected function definition() {
        $mform = $this->_form;

        // File picker for JSON or CSV.
        $mform->addElement('filepicker', 'importfile', get_string('selectfile', 'local_educambot'),
            null, ['accepted_types' => ['.json', '.csv']]);
        $mform->addRule('importfile', get_string('required'), 'required', null, 'client');

        // Import type options.
        $importtypes = [
            'full' => get_string('import_type_full', 'local_educambot'),
            'rules_only' => get_string('import_type_rules', 'local_educambot'),
        ];
        $mform->addElement('select', 'importtype', get_string('import_type', 'local_educambot'), $importtypes);
        $mform->setDefault('importtype', 'full');

        // Clear existing option.
        $mform->addElement('advcheckbox', 'clearexisting', get_string('clearexisting', 'local_educambot'));
        $mform->setDefault('clearexisting', 0);
        $mform->addHelpButton('clearexisting', 'clearexisting', 'local_educambot');

        $this->add_action_buttons(true, get_string('import', 'local_educambot'));
    }
}

/**
 * Parse CSV content into rules array.
 *
 * @param string $content CSV content
 * @return array|false Parsed data or false on error
 */
function parse_csv_import($content) {
    $lines = explode("\n", trim($content));
    if (count($lines) < 2) {
        return false;
    }

    // Parse header.
    $header = str_getcsv(array_shift($lines), ',', '"', '\\');
    $header = array_map('trim', $header);

    // Validate required columns.
    $requiredColumns = ['pattern', 'response'];
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $header)) {
            return false;
        }
    }

    $rules = [];
    $rownum = 1;
    foreach ($lines as $line) {
        if (empty(trim($line))) {
            continue;
        }

        $values = str_getcsv($line, ',', '"', '\\');
        if (count($values) !== count($header)) {
            continue; // Skip malformed rows.
        }

        $row = array_combine($header, $values);
        if ($row === false) {
            continue;
        }

        // Add row number as ID if not present.
        if (!isset($row['id'])) {
            $row['id'] = $rownum;
        }
        $rownum++;

        $rules[] = $row;
    }

    return [
        'version' => '3.5.0',
        'format' => 'csv',
        'rules' => $rules,
    ];
}

/**
 * Import rules from parsed data.
 *
 * @param array $importdata Parsed import data
 * @param array $categorymap Category ID mapping
 * @param int $now Current timestamp
 * @return array Import statistics
 */
function import_rules($importdata, $categorymap, $now) {
    global $DB;

    $imported = 0;
    $updated = 0;
    $rulemap = [];

    foreach ($importdata['rules'] as $rule) {
        // Determine category ID.
        $categoryid = null;
        if (!empty($rule['categoryid']) && isset($categorymap[$rule['categoryid']])) {
            $categoryid = $categorymap[$rule['categoryid']];
        } else if (!empty($rule['category'])) {
            // Look up category by name.
            $cat = $DB->get_record('local_educambot_category', ['name' => $rule['category']]);
            if ($cat) {
                $categoryid = $cat->id;
            }
        }

        // Build record with all fields.
        $record = new stdClass();
        $record->categoryid = $categoryid;
        $record->pattern = $rule['pattern'] ?? '';
        $record->keywords = $rule['keywords'] ?? '';
        $record->response = $rule['response'] ?? '';
        $record->tags = $rule['tags'] ?? '';
        $record->enabled = isset($rule['enabled']) ? (int)$rule['enabled'] : 1;
        $record->showoptions = isset($rule['showoptions']) ? (int)$rule['showoptions'] : 1;
        $record->contextaware = isset($rule['contextaware']) ? (int)$rule['contextaware'] : 0;
        $record->dynamicresponse = isset($rule['dynamicresponse']) ? (int)$rule['dynamicresponse'] : 0;
        $record->requiredcontext = !empty($rule['requiredcontext']) ? $rule['requiredcontext'] : null;
        $record->roles = !empty($rule['roles']) ? $rule['roles'] : null;
        $record->courses = !empty($rule['courses']) ? $rule['courses'] : null;
        $record->lang = !empty($rule['lang']) ? $rule['lang'] : 'es';
        $record->langparent = null;
        $record->priority = isset($rule['priority']) ? (int)$rule['priority'] : 0;
        $record->needs_review = isset($rule['needs_review']) ? (int)$rule['needs_review'] : 0;
        $record->useroleoptions = isset($rule['useroleoptions']) ? (int)$rule['useroleoptions'] : 0;
        $record->timemodified = $now;

        // Check if rule already exists by pattern.
        $sql = "SELECT * FROM {local_educambot_rule} WHERE " .
               $DB->sql_compare_text('pattern') . " = " . $DB->sql_compare_text(':pattern') .
               " AND lang = :lang";
        $existing = $DB->get_records_sql($sql, ['pattern' => $record->pattern, 'lang' => $record->lang], 0, 1);
        $existingRule = !empty($existing) ? reset($existing) : null;

        if ($existingRule) {
            // Update existing rule.
            $record->id = $existingRule->id;
            $record->helpfulcount = $existingRule->helpfulcount;
            $record->nothelpfulcount = $existingRule->nothelpfulcount;
            $record->timecreated = $existingRule->timecreated;
            $DB->update_record('local_educambot_rule', $record);
            $rulemap[$rule['id'] ?? 0] = $record->id;
            $updated++;
        } else {
            // Insert new rule.
            $record->helpfulcount = 0;
            $record->nothelpfulcount = 0;
            $record->timecreated = $now;
            $newid = $DB->insert_record('local_educambot_rule', $record);
            $rulemap[$rule['id'] ?? 0] = $newid;
            $imported++;
        }
    }

    return [
        'imported' => $imported,
        'updated' => $updated,
        'rulemap' => $rulemap,
    ];
}

// Process import if form submitted.
$form = new import_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/educambot/import.php'));
} else if ($data = $form->get_data()) {
    $content = $form->get_file_content('importfile');
    $filename = $form->get_new_filename('importfile');

    if (empty($content)) {
        redirect(new moodle_url('/local/educambot/import.php'),
            get_string('importerror', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    // Detect file type.
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if ($extension === 'csv') {
        // Parse CSV.
        $importdata = parse_csv_import($content);
        if ($importdata === false) {
            redirect(new moodle_url('/local/educambot/import.php'),
                get_string('importinvalidcsv', 'local_educambot'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
    } else {
        // Parse JSON.
        $importdata = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            redirect(new moodle_url('/local/educambot/import.php'),
                get_string('importinvalidjson', 'local_educambot'),
                null, \core\output\notification::NOTIFY_ERROR);
        }
    }

    // Validate version.
    if (empty($importdata['version'])) {
        redirect(new moodle_url('/local/educambot/import.php'),
            get_string('importinvalidversion', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    // Clear existing data if requested.
    if (!empty($data->clearexisting)) {
        $DB->delete_records('local_educambot_option');
        $DB->delete_records('local_educambot_rule');
        if ($data->importtype === 'full') {
            $DB->delete_records('local_educambot_category');
        }
    }

    $now = time();
    $categorymap = [];
    $stats = ['categories' => 0, 'rules_imported' => 0, 'rules_updated' => 0, 'options' => 0];

    // Import categories (JSON full import only).
    if ($data->importtype === 'full' && !empty($importdata['categories'])) {
        // First pass: create all categories without parent.
        foreach ($importdata['categories'] as $cat) {
            $oldid = $cat['id'];

            // Check if category exists by name.
            $existing = $DB->get_record('local_educambot_category', ['name' => $cat['name']]);
            if ($existing) {
                $categorymap[$oldid] = $existing->id;
                continue;
            }

            $record = new stdClass();
            $record->name = $cat['name'];
            $record->description = $cat['description'] ?? '';
            $record->parent = null;
            $record->sortorder = $cat['sortorder'] ?? 0;
            $record->enabled = $cat['enabled'] ?? 1;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $newid = $DB->insert_record('local_educambot_category', $record);
            $categorymap[$oldid] = $newid;
            $stats['categories']++;
        }

        // Second pass: update parent references.
        foreach ($importdata['categories'] as $cat) {
            if (!empty($cat['parent']) && isset($categorymap[$cat['parent']])) {
                $DB->set_field('local_educambot_category', 'parent',
                    $categorymap[$cat['parent']], ['id' => $categorymap[$cat['id']]]);
            }
        }
    } else {
        // Build category map from existing categories.
        $existingCats = $DB->get_records('local_educambot_category');
        foreach ($existingCats as $cat) {
            $categorymap[$cat->id] = $cat->id;
        }
    }

    // Import rules.
    if (!empty($importdata['rules'])) {
        $result = import_rules($importdata, $categorymap, $now);
        $stats['rules_imported'] = $result['imported'];
        $stats['rules_updated'] = $result['updated'];
        $rulemap = $result['rulemap'];
    } else {
        $rulemap = [];
    }

    // Import options (JSON only).
    if (!empty($importdata['options'])) {
        foreach ($importdata['options'] as $opt) {
            // Skip if rule doesn't exist.
            if (empty($rulemap[$opt['ruleid']])) {
                continue;
            }

            $record = new stdClass();
            $record->ruleid = $rulemap[$opt['ruleid']];
            $record->text = $opt['text'];
            $record->action = $opt['action'] ?? null;
            $record->targetruleid = !empty($opt['targetruleid']) && isset($rulemap[$opt['targetruleid']])
                ? $rulemap[$opt['targetruleid']] : null;
            $record->icon = $opt['icon'] ?? '';
            $record->sortorder = $opt['sortorder'] ?? 0;
            $record->enabled = $opt['enabled'] ?? 1;

            $DB->insert_record('local_educambot_option', $record);
            $stats['options']++;
        }
    }

    // Success message with stats.
    redirect(new moodle_url('/local/educambot/import.php'),
        get_string('importsuccess_v2', 'local_educambot', $stats),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display page.
echo $OUTPUT->header();

// Export section.
echo $OUTPUT->heading(get_string('exportkb', 'local_educambot'), 3);
echo html_writer::tag('p', get_string('exportkb_desc', 'local_educambot'));

// Export buttons - JSON and CSV.
$exportjsonurl = new moodle_url('/local/educambot/export.php', ['format' => 'json', 'sesskey' => sesskey()]);
$exportcsvurl = new moodle_url('/local/educambot/export.php', ['format' => 'csv', 'sesskey' => sesskey()]);

echo html_writer::start_div('mb-4');
echo html_writer::link($exportjsonurl, get_string('export_json', 'local_educambot'),
    ['class' => 'btn btn-primary mr-2']);
echo html_writer::link($exportcsvurl, get_string('export_csv', 'local_educambot'),
    ['class' => 'btn btn-secondary ml-2']);
echo html_writer::end_div();

// Current statistics.
$catcount = $DB->count_records('local_educambot_category');
$rulecount = $DB->count_records('local_educambot_rule');
$optcount = $DB->count_records('local_educambot_option');

echo html_writer::tag('p', get_string('currentstats', 'local_educambot', [
    'categories' => $catcount,
    'rules' => $rulecount,
    'options' => $optcount,
]));

echo html_writer::tag('hr', '');

// Import section.
echo $OUTPUT->heading(get_string('importkb', 'local_educambot'), 3);
echo html_writer::tag('p', get_string('importkb_desc_v2', 'local_educambot'));

$form->display();

// Template downloads section.
echo html_writer::tag('hr', '');
echo $OUTPUT->heading(get_string('templates', 'local_educambot'), 3);
echo html_writer::tag('p', get_string('templates_desc', 'local_educambot'));

$templatejsonurl = new moodle_url('/local/educambot/db/data/templates/import_template.json');
$templatecsvurl = new moodle_url('/local/educambot/db/data/templates/import_template.csv');

echo html_writer::start_div('mb-4');
echo html_writer::link($templatejsonurl, get_string('download_json_template', 'local_educambot'),
    ['class' => 'btn btn-outline-primary mr-2', 'download' => 'import_template.json']);
echo html_writer::link($templatecsvurl, get_string('download_csv_template', 'local_educambot'),
    ['class' => 'btn btn-outline-secondary ml-2', 'download' => 'import_template.csv']);
echo html_writer::end_div();

echo $OUTPUT->footer();
