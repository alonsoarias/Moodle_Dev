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

namespace local_chatbot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form to manage suggestion chips.
 *
 * @package     local_chatbot
 */
class suggestion_form extends \moodleform {
    /**
     * Define form fields.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'text', get_string('suggestion_text', 'local_chatbot'));
        $mform->setType('text', PARAM_TEXT);
        $mform->addRule('text', get_string('required'), 'required', null, 'client');

        $options = [
            'message' => get_string('suggestion_mode_message', 'local_chatbot'),
            'action' => get_string('suggestion_mode_action', 'local_chatbot'),
        ];
        $mform->addElement('select', 'mode', get_string('suggestion_mode', 'local_chatbot'), $options);
        $mform->setDefault('mode', 'message');
        $mform->addHelpButton('mode', 'suggestion_mode', 'local_chatbot');

        $mform->addElement('text', 'target', get_string('suggestion_target', 'local_chatbot'));
        $mform->setType('target', PARAM_TEXT);
        $mform->addHelpButton('target', 'suggestion_target', 'local_chatbot');

        $mform->addElement('text', 'icon', get_string('suggestion_icon', 'local_chatbot'), 'maxlength="10"');
        $mform->setType('icon', PARAM_TEXT);

        $mform->addElement('advcheckbox', 'enabled', get_string('suggestion_enabled', 'local_chatbot'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('text', 'sortorder', get_string('suggestion_sortorder', 'local_chatbot'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 10);

        $this->add_action_buttons();
    }

    /**
     * Validate suggestion.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (($data['mode'] ?? 'message') === 'action' && empty($data['target'])) {
            $errors['target'] = get_string('suggestion_target_required', 'local_chatbot');
        }

        return $errors;
    }
}
