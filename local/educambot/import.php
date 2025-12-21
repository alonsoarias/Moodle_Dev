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
 * Import/Export page for knowledge base.
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
 * Import form class.
 */
class import_form extends moodleform {
    /**
     * Define form elements.
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('filepicker', 'importfile', get_string('selectfile', 'local_educambot'),
            null, ['accepted_types' => '.json']);
        $mform->addRule('importfile', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'clearexisting', get_string('clearexisting', 'local_educambot'));
        $mform->setDefault('clearexisting', 0);
        $mform->addHelpButton('clearexisting', 'clearexisting', 'local_educambot');

        $this->add_action_buttons(true, get_string('import', 'local_educambot'));
    }
}

// Process import if form submitted.
$form = new import_form();

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/educambot/import.php'));
} else if ($data = $form->get_data()) {
    $content = $form->get_file_content('importfile');

    if (empty($content)) {
        redirect(new moodle_url('/local/educambot/import.php'),
            get_string('importerror', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
    }

    $importdata = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        redirect(new moodle_url('/local/educambot/import.php'),
            get_string('importinvalidjson', 'local_educambot'),
            null, \core\output\notification::NOTIFY_ERROR);
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
        $DB->delete_records('local_educambot_category');
    }

    $now = time();
    $categorymap = [];
    $rulemap = [];

    // Import categories.
    if (!empty($importdata['categories'])) {
        // First pass: create all categories without parent.
        foreach ($importdata['categories'] as $cat) {
            $oldid = $cat['id'];

            $record = new stdClass();
            $record->name = $cat['name'];
            $record->description = $cat['description'] ?? '';
            $record->parent = null; // Set later.
            $record->sortorder = $cat['sortorder'] ?? 0;
            $record->enabled = $cat['enabled'] ?? 1;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $newid = $DB->insert_record('local_educambot_category', $record);
            $categorymap[$oldid] = $newid;
        }

        // Second pass: update parent references.
        foreach ($importdata['categories'] as $cat) {
            if (!empty($cat['parent']) && isset($categorymap[$cat['parent']])) {
                $DB->set_field('local_educambot_category', 'parent',
                    $categorymap[$cat['parent']], ['id' => $categorymap[$cat['id']]]);
            }
        }
    }

    // Import rules.
    if (!empty($importdata['rules'])) {
        foreach ($importdata['rules'] as $rule) {
            $oldid = $rule['id'];

            $record = new stdClass();
            $record->categoryid = !empty($rule['categoryid']) && isset($categorymap[$rule['categoryid']])
                ? $categorymap[$rule['categoryid']] : null;
            $record->pattern = $rule['pattern'];
            $record->keywords = $rule['keywords'] ?? '';
            $record->response = $rule['response'];
            $record->tags = $rule['tags'] ?? '';
            $record->enabled = $rule['enabled'] ?? 1;
            $record->showoptions = $rule['showoptions'] ?? 1;
            $record->timecreated = $now;
            $record->timemodified = $now;

            $newid = $DB->insert_record('local_educambot_rule', $record);
            $rulemap[$oldid] = $newid;
        }
    }

    // Import options.
    if (!empty($importdata['options'])) {
        foreach ($importdata['options'] as $opt) {
            // Skip if rule doesn't exist.
            if (empty($rulemap[$opt['ruleid']])) {
                continue;
            }

            $record = new stdClass();
            $record->ruleid = $rulemap[$opt['ruleid']];
            $record->text = $opt['text'];
            $record->targetruleid = !empty($opt['targetruleid']) && isset($rulemap[$opt['targetruleid']])
                ? $rulemap[$opt['targetruleid']] : null;
            $record->icon = $opt['icon'] ?? '';
            $record->sortorder = $opt['sortorder'] ?? 0;
            $record->enabled = $opt['enabled'] ?? 1;

            $DB->insert_record('local_educambot_option', $record);
        }
    }

    // Success message with stats.
    $stats = $importdata['statistics'] ?? [
        'categories' => count($importdata['categories'] ?? []),
        'rules' => count($importdata['rules'] ?? []),
        'options' => count($importdata['options'] ?? []),
    ];

    redirect(new moodle_url('/local/educambot/import.php'),
        get_string('importsuccess', 'local_educambot', $stats),
        null, \core\output\notification::NOTIFY_SUCCESS);
}

// Display page.
echo $OUTPUT->header();

// Export section.
echo $OUTPUT->heading(get_string('exportkb', 'local_educambot'), 3);
echo html_writer::tag('p', get_string('exportkb_desc', 'local_educambot'));

$exporturl = new moodle_url('/local/educambot/export.php', ['sesskey' => sesskey()]);
echo html_writer::div(
    html_writer::link($exporturl, get_string('exportfile', 'local_educambot'), ['class' => 'btn btn-primary']),
    'mb-4'
);

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
echo html_writer::tag('p', get_string('importkb_desc', 'local_educambot'));

$form->display();

echo $OUTPUT->footer();
