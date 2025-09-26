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
 * Form to create or edit chatbot quick actions.
 *
 * @package     local_chatbot
 */
class quickaction_form extends \moodleform {
    /**
     * Define form structure.
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata ?? [];
        $currentid = $customdata['id'] ?? 0;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        if ($currentid) {
            $mform->setDefault('id', $currentid);
        }

        $mform->addElement('text', 'actionkey', get_string('quickaction_actionkey', 'local_chatbot'));
        $mform->setType('actionkey', PARAM_ALPHANUMEXT);
        $mform->addRule('actionkey', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'name', get_string('quickaction_name', 'local_chatbot'));
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        $options = [
            'navigate' => get_string('quickaction_type_navigate', 'local_chatbot'),
            'inject' => get_string('quickaction_type_inject', 'local_chatbot'),
            'server' => get_string('quickaction_type_server', 'local_chatbot'),
        ];
        $mform->addElement('select', 'type', get_string('quickaction_type', 'local_chatbot'), $options);
        $mform->setDefault('type', 'navigate');
        $mform->addHelpButton('type', 'quickaction_type', 'local_chatbot');

        $mform->addElement('textarea', 'payload', get_string('quickaction_payload', 'local_chatbot'), 'rows="4" cols="60"');
        $mform->setType('payload', PARAM_RAW);
        $mform->addHelpButton('payload', 'quickaction_payload', 'local_chatbot');

        $mform->addElement('text', 'description', get_string('quickaction_description', 'local_chatbot'));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('text', 'icon', get_string('quickaction_icon', 'local_chatbot'), 'maxlength="10"');
        $mform->setType('icon', PARAM_TEXT);
        $mform->addHelpButton('icon', 'quickaction_icon', 'local_chatbot');

        $mform->addElement('advcheckbox', 'enabled', get_string('quickaction_enabled', 'local_chatbot'));
        $mform->setDefault('enabled', 1);

        $mform->addElement('text', 'sortorder', get_string('quickaction_sortorder', 'local_chatbot'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 10);

        $this->add_action_buttons();
    }

    /**
     * Validate quick action data.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        global $DB;

        $payload = trim((string)($data['payload'] ?? ''));
        $type = $data['type'] ?? 'navigate';

        if ($type === 'navigate' && $payload === '') {
            $errors['payload'] = get_string('quickaction_payload_required', 'local_chatbot');
        }

        if ($type !== 'navigate' && $payload === '') {
            $errors['payload'] = get_string('quickaction_payload_text_required', 'local_chatbot');
        }

        $id = (int)($data['id'] ?? 0);
        $actionkey = trim((string)($data['actionkey'] ?? ''));
        if ($actionkey !== '') {
            $existing = $DB->get_record('local_chatbot_quickacts', ['actionkey' => $actionkey]);
            if ($existing && (int)$existing->id !== $id) {
                $errors['actionkey'] = get_string('quickaction_actionkey_unique', 'local_chatbot');
            }
        }

        return $errors;
    }
}
