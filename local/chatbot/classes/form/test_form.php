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
 * Form used in the manual testing console.
 *
 * @package     local_chatbot
 */
class test_form extends \moodleform {
    /**
     * Define the form.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('textarea', 'message', get_string('test_message', 'local_chatbot'), 'rows="4" cols="80"');
        $mform->setType('message', PARAM_RAW);
        $mform->addRule('message', get_string('required'), 'required', null, 'client');

        $mform->addElement('text', 'sessionid', get_string('test_sessionid', 'local_chatbot'));
        $mform->setType('sessionid', PARAM_ALPHANUMEXT);
        $mform->addHelpButton('sessionid', 'test_sessionid', 'local_chatbot');

        $this->add_action_buttons(false, get_string('test_send', 'local_chatbot'));
    }
}
