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
 * Filter form for the dialogues console.
 *
 * @package     local_chatbot
 */
class dialogue_filter_form extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata ?? [];
        $intentoptions = $customdata['intents'] ?? [];

        $mform->addElement('text', 'sessionid', get_string('dialogue_filter_session', 'local_chatbot'));
        $mform->setType('sessionid', PARAM_ALPHANUMEXT);

        $mform->addElement('text', 'userid', get_string('dialogue_filter_userid', 'local_chatbot'));
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('select', 'intent', get_string('dialogue_filter_intent', 'local_chatbot'), $intentoptions);
        $mform->setDefault('intent', '');

        $mform->addElement('date_selector', 'from', get_string('dialogue_filter_from', 'local_chatbot'), ['optional' => true]);
        $mform->addElement('date_selector', 'to', get_string('dialogue_filter_to', 'local_chatbot'), ['optional' => true]);

        $mform->addElement('advcheckbox', 'hasfeedback', get_string('dialogue_filter_feedback', 'local_chatbot'));

        $mform->disable_form_change_checker();

        $buttonarray = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('dialogue_filter_apply', 'local_chatbot'));
        $buttonarray[] = $mform->createElement('submit', 'resetbutton', get_string('dialogue_filter_reset', 'local_chatbot'));
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }
}
