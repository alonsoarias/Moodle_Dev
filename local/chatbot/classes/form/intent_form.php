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
 * Form used to create or edit chatbot intents.
 *
 * @package     local_chatbot
 * @copyright   2024 Moodle Community
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class intent_form extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'name', get_string('intent_name', 'local_chatbot'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $mform->addElement('textarea', 'keywords', get_string('intent_keywords', 'local_chatbot'), 'rows="4" cols="60"');
        $mform->setType('keywords', PARAM_RAW);
        $mform->addHelpButton('keywords', 'intent_keywords', 'local_chatbot');

        $mform->addElement('textarea', 'response', get_string('intent_response', 'local_chatbot'), 'rows="5" cols="60"');
        $mform->setType('response', PARAM_RAW);
        $mform->addRule('response', get_string('required'), 'required', null, 'client');

        $mform->addElement('advcheckbox', 'isfallback', get_string('intent_fallback', 'local_chatbot'));
        $mform->addHelpButton('isfallback', 'intent_fallback', 'local_chatbot');

        $mform->addElement('advcheckbox', 'enabled', get_string('intent_enabled', 'local_chatbot'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('text', 'sortorder', get_string('intent_sortorder', 'local_chatbot'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 10);

        $this->add_action_buttons();
    }

    /**
     * Custom validation rules.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (empty($data['isfallback'])) {
            $keywords = local_chatbot_parse_keywords($data['keywords'] ?? '');
            if (empty($keywords)) {
                $errors['keywords'] = get_string('intent_keywords_required', 'local_chatbot');
            }
        }

        return $errors;
    }
}
