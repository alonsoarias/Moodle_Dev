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
 * Theme form for educambot.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing themes.
 */
class theme_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Hidden ID for editing.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Theme name.
        $mform->addElement('text', 'name', get_string('themename', 'local_educambot'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 50), 'maxlength', 50, 'client');

        // Color section header.
        $mform->addElement('header', 'colorsheader', get_string('colors', 'local_educambot'));

        // Primary color.
        $mform->addElement('text', 'primarycolor', get_string('primarycolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('primarycolor', PARAM_TEXT);
        $mform->addRule('primarycolor', null, 'required', null, 'client');
        $mform->setDefault('primarycolor', '#0f6fc5');
        $mform->addHelpButton('primarycolor', 'primarycolor', 'local_educambot');

        // Secondary color.
        $mform->addElement('text', 'secondarycolor', get_string('secondarycolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('secondarycolor', PARAM_TEXT);
        $mform->addRule('secondarycolor', null, 'required', null, 'client');
        $mform->setDefault('secondarycolor', '#084a8a');
        $mform->addHelpButton('secondarycolor', 'secondarycolor', 'local_educambot');

        // Text color.
        $mform->addElement('text', 'textcolor', get_string('textcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('textcolor', PARAM_TEXT);
        $mform->addRule('textcolor', null, 'required', null, 'client');
        $mform->setDefault('textcolor', '#1f2937');
        $mform->addHelpButton('textcolor', 'textcolor', 'local_educambot');

        // Background color.
        $mform->addElement('text', 'backgroundcolor', get_string('backgroundcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('backgroundcolor', PARAM_TEXT);
        $mform->addRule('backgroundcolor', null, 'required', null, 'client');
        $mform->setDefault('backgroundcolor', '#f9fafb');
        $mform->addHelpButton('backgroundcolor', 'backgroundcolor', 'local_educambot');

        // User message color.
        $mform->addElement('text', 'usercolor', get_string('usercolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('usercolor', PARAM_TEXT);
        $mform->addRule('usercolor', null, 'required', null, 'client');
        $mform->setDefault('usercolor', '#0f6fc5');
        $mform->addHelpButton('usercolor', 'usercolor', 'local_educambot');

        // Bot message color.
        $mform->addElement('text', 'botcolor', get_string('botcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('botcolor', PARAM_TEXT);
        $mform->addRule('botcolor', null, 'required', null, 'client');
        $mform->setDefault('botcolor', '#ffffff');
        $mform->addHelpButton('botcolor', 'botcolor', 'local_educambot');

        // Is default.
        $mform->addElement('advcheckbox', 'isdefault', get_string('setasdefault', 'local_educambot'));
        $mform->setDefault('isdefault', 0);

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate color format.
        $colorfields = ['primarycolor', 'secondarycolor', 'textcolor', 'backgroundcolor', 'usercolor', 'botcolor'];
        foreach ($colorfields as $field) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                $errors[$field] = get_string('invalidcolor', 'local_educambot');
            }
        }

        return $errors;
    }
}
