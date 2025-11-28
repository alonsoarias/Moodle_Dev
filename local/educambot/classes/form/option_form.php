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
 * Option entry form.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Option entry form class.
 */
class option_form extends \moodleform {

    /**
     * Define form elements.
     */
    protected function definition() {
        global $DB;

        $mform = $this->_form;

        // Hidden field for option ID (when editing).
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Hidden field for rule ID.
        $mform->addElement('hidden', 'ruleid');
        $mform->setType('ruleid', PARAM_INT);

        // Button text field.
        $mform->addElement('text', 'text', get_string('optiontext', 'local_educambot'), ['size' => 50, 'maxlength' => 100]);
        $mform->setType('text', PARAM_TEXT);
        $mform->addRule('text', get_string('required'), 'required', null, 'client');
        $mform->addRule('text', get_string('maximumchars', '', 100), 'maxlength', 100, 'client');
        $mform->addHelpButton('text', 'optiontext', 'local_educambot');

        // Icon field (emoji).
        $mform->addElement('text', 'icon', get_string('icon', 'local_educambot'), ['size' => 10, 'maxlength' => 50]);
        $mform->setType('icon', PARAM_TEXT);
        $mform->addHelpButton('icon', 'icon', 'local_educambot');

        // Target rule select.
        $rules = $DB->get_records_menu('local_educambot_rule', ['enabled' => 1], 'pattern ASC', 'id, pattern');
        $ruleoptions = ['' => get_string('selecttargetrule', 'local_educambot')] + $rules;
        $mform->addElement('select', 'targetruleid', get_string('targetrule', 'local_educambot'), $ruleoptions);
        $mform->setType('targetruleid', PARAM_INT);
        $mform->addRule('targetruleid', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('targetrule', 'targetrule', 'local_educambot');

        // Sort order field.
        $mform->addElement('text', 'sortorder', get_string('optionorder', 'local_educambot'), ['size' => 5]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);

        // Enabled checkbox.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_educambot'));
        $mform->setDefault('enabled', 1);

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data
     * @param array $files Uploaded files
     * @return array Errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate text is not empty after trimming.
        if (empty(trim($data['text']))) {
            $errors['text'] = get_string('required');
        }

        // Validate text length.
        if (strlen($data['text']) > 100) {
            $errors['text'] = get_string('maximumchars', '', 100);
        }

        // Validate target rule is selected.
        if (empty($data['targetruleid'])) {
            $errors['targetruleid'] = get_string('required');
        }

        return $errors;
    }
}
