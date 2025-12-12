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
 * Import/Export page for educambot knowledge base.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');

admin_externalpage_setup('local_educambot_importexport');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

$action = optional_param('action', '', PARAM_ALPHA);

$PAGE->set_url(new moodle_url('/local/educambot/import_export.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('importexport', 'local_educambot'));
$PAGE->set_heading(get_string('importexport', 'local_educambot'));

/**
 * Import form class.
 */
class local_educambot_import_form extends moodleform {
    /**
     * Form definition.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'importfile', get_string('selectfile', 'local_educambot'),
            null, ['accepted_types' => ['.json']]);
        $mform->addRule('importfile', null, 'required');

        $mform->addElement('advcheckbox', 'clearexisting', get_string('clearexisting', 'local_educambot'));
        $mform->addHelpButton('clearexisting', 'clearexisting', 'local_educambot');

        $this->add_action_buttons(true, get_string('import', 'local_educambot'));
    }
}

// Handle export action.
if ($action === 'export') {
    require_sesskey();

    // Get all categories.
    $categories = $DB->get_records('local_educambot_category', null, 'sortorder ASC');

    // Get all rules.
    $rules = $DB->get_records('local_educambot_rule', null, 'sortorder ASC');

    // Get all options.
    $options = $DB->get_records('local_educambot_option', null, 'sortorder ASC');

    // Build export data.
    $exportdata = [
        'version' => '2.0.0',
        'exported' => date('Y-m-d H:i:s'),
        'plugin' => 'local_educambot',
        'categories' => [],
        'rules' => [],
        'options' => [],
    ];

    // Export categories with original IDs for reference mapping.
    foreach ($categories as $cat) {
        $exportdata['categories'][] = [
            'id' => (int) $cat->id,
            'name' => $cat->name,
            'description' => $cat->description ?? '',
            'parent' => (int) $cat->parent,
            'sortorder' => (int) $cat->sortorder,
            'enabled' => (int) $cat->enabled,
        ];
    }

    // Export rules.
    foreach ($rules as $rule) {
        $exportdata['rules'][] = [
            'id' => (int) $rule->id,
            'categoryid' => (int) $rule->categoryid,
            'pattern' => $rule->pattern,
            'keywords' => $rule->keywords ?? '',
            'response' => $rule->response,
            'enabled' => (int) $rule->enabled,
            'sortorder' => (int) $rule->sortorder,
            'showoptions' => (int) $rule->showoptions,
            'language' => $rule->language ?? '',
            'contextaware' => (int) ($rule->contextaware ?? 0),
            'dynamicresponse' => (int) ($rule->dynamicresponse ?? 0),
            'requiredcontext' => $rule->requiredcontext ?? '',
            'roles' => $rule->roles ?? '',
            'courses' => $rule->courses ?? '',
            'tags' => $rule->tags ?? '',
            'archetypes' => $rule->archetypes ?? '',
        ];
    }

    // Export options.
    foreach ($options as $opt) {
        $exportdata['options'][] = [
            'id' => (int) $opt->id,
            'ruleid' => (int) $opt->ruleid,
            'text' => $opt->text,
            'targetruleid' => (int) $opt->targetruleid,
            'icon' => $opt->icon ?? '',
            'sortorder' => (int) $opt->sortorder,
            'enabled' => (int) $opt->enabled,
        ];
    }

    // Send JSON file.
    $filename = 'educambot_kb_' . date('Y-m-d_His') . '.json';
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo json_encode($exportdata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    die();
}

// Handle import.
$importform = new local_educambot_import_form();

if ($importform->is_cancelled()) {
    redirect(new moodle_url('/local/educambot/import_export.php'));
} else if ($data = $importform->get_data()) {
    // Process import.
    $content = $importform->get_file_content('importfile');

    if (empty($content)) {
        redirect(new moodle_url('/local/educambot/import_export.php'),
            get_string('importerror', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    $importdata = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        redirect(new moodle_url('/local/educambot/import_export.php'),
            get_string('importinvalidjson', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    if (empty($importdata['version'])) {
        redirect(new moodle_url('/local/educambot/import_export.php'),
            get_string('importinvalidversion', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    // Start transaction.
    $transaction = $DB->start_delegated_transaction();

    try {
        // Clear existing data if requested.
        if (!empty($data->clearexisting)) {
            $DB->delete_records('local_educambot_option');
            $DB->delete_records('local_educambot_rule');
            $DB->delete_records('local_educambot_category');
        }

        $now = time();
        $categorymap = []; // old_id => new_id
        $rulemap = []; // old_id => new_id
        $importedcats = 0;
        $importedrules = 0;
        $importedopts = 0;

        // Import categories (handle parent relationships).
        if (!empty($importdata['categories'])) {
            // First pass: import categories without parents.
            foreach ($importdata['categories'] as $catdata) {
                if (empty($catdata['parent'])) {
                    $record = new stdClass();
                    $record->name = $catdata['name'];
                    $record->description = $catdata['description'] ?? '';
                    $record->parent = 0;
                    $record->sortorder = $catdata['sortorder'] ?? 0;
                    $record->enabled = $catdata['enabled'] ?? 1;
                    $record->timecreated = $now;
                    $record->timemodified = $now;

                    $newid = $DB->insert_record('local_educambot_category', $record);
                    $categorymap[$catdata['id']] = $newid;
                    $importedcats++;
                }
            }

            // Second pass: import categories with parents.
            foreach ($importdata['categories'] as $catdata) {
                if (!empty($catdata['parent'])) {
                    $record = new stdClass();
                    $record->name = $catdata['name'];
                    $record->description = $catdata['description'] ?? '';
                    $record->parent = $categorymap[$catdata['parent']] ?? 0;
                    $record->sortorder = $catdata['sortorder'] ?? 0;
                    $record->enabled = $catdata['enabled'] ?? 1;
                    $record->timecreated = $now;
                    $record->timemodified = $now;

                    $newid = $DB->insert_record('local_educambot_category', $record);
                    $categorymap[$catdata['id']] = $newid;
                    $importedcats++;
                }
            }
        }

        // Import rules.
        if (!empty($importdata['rules'])) {
            foreach ($importdata['rules'] as $ruledata) {
                $record = new stdClass();
                $record->categoryid = $categorymap[$ruledata['categoryid']] ?? 0;
                $record->pattern = $ruledata['pattern'];
                $record->keywords = $ruledata['keywords'] ?? '';
                $record->response = $ruledata['response'];
                $record->enabled = $ruledata['enabled'] ?? 1;
                $record->sortorder = $ruledata['sortorder'] ?? 0;
                $record->showoptions = $ruledata['showoptions'] ?? 0;
                $record->language = $ruledata['language'] ?? '';
                $record->contextaware = $ruledata['contextaware'] ?? 0;
                $record->dynamicresponse = $ruledata['dynamicresponse'] ?? 0;
                $record->requiredcontext = $ruledata['requiredcontext'] ?? '';
                $record->roles = $ruledata['roles'] ?? '';
                $record->courses = $ruledata['courses'] ?? '';
                $record->tags = $ruledata['tags'] ?? '';
                $record->archetypes = $ruledata['archetypes'] ?? '';
                $record->timecreated = $now;
                $record->timemodified = $now;

                $newid = $DB->insert_record('local_educambot_rule', $record);
                $rulemap[$ruledata['id']] = $newid;
                $importedrules++;
            }
        }

        // Import options.
        if (!empty($importdata['options'])) {
            foreach ($importdata['options'] as $optdata) {
                $record = new stdClass();
                $record->ruleid = $rulemap[$optdata['ruleid']] ?? 0;
                $record->text = $optdata['text'];
                $record->targetruleid = $rulemap[$optdata['targetruleid']] ?? 0;
                $record->icon = $optdata['icon'] ?? '';
                $record->sortorder = $optdata['sortorder'] ?? 0;
                $record->enabled = $optdata['enabled'] ?? 1;

                // Only import if we have a valid ruleid.
                if ($record->ruleid > 0) {
                    $DB->insert_record('local_educambot_option', $record);
                    $importedopts++;
                }
            }
        }

        $transaction->allow_commit();

        $message = get_string('importsuccess', 'local_educambot', (object) [
            'categories' => $importedcats,
            'rules' => $importedrules,
            'options' => $importedopts,
        ]);

        redirect(new moodle_url('/local/educambot/import_export.php'),
            $message,
            null, \core\output\notification::NOTIFY_SUCCESS);

    } catch (Exception $e) {
        $transaction->rollback($e);
        redirect(new moodle_url('/local/educambot/import_export.php'),
            get_string('importerror', 'local_educambot') . ': ' . $e->getMessage(),
            null, \core\output\notification::NOTIFY_ERROR);
    }
}

// Display page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('importexport', 'local_educambot'));

// Current stats.
$catcount = $DB->count_records('local_educambot_category');
$rulecount = $DB->count_records('local_educambot_rule');
$optcount = $DB->count_records('local_educambot_option');

$statsobj = (object) [
    'categories' => $catcount,
    'rules' => $rulecount,
    'options' => $optcount,
];
echo html_writer::tag('div', get_string('currentstats', 'local_educambot', $statsobj),
    ['class' => 'alert alert-info']);

// Export section.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', get_string('exportkb', 'local_educambot'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::tag('p', get_string('exportkb_desc', 'local_educambot'));

$exporturl = new moodle_url('/local/educambot/import_export.php', [
    'action' => 'export',
    'sesskey' => sesskey(),
]);
echo html_writer::link($exporturl, get_string('exportfile', 'local_educambot'),
    ['class' => 'btn btn-primary']);

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Import section.
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', get_string('importkb', 'local_educambot'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

echo html_writer::tag('p', get_string('importkb_desc', 'local_educambot'));

$importform->display();

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
