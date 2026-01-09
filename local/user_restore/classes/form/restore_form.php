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
 * Restore form for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for restoring a deleted user.
 */
class restore_form extends \moodleform {

    /**
     * Define the form elements.
     */
    protected function definition() {
        global $CFG;

        $mform = $this->_form;
        $user = $this->_customdata['user'] ?? null;

        // User information section.
        $mform->addElement('header', 'userinfo', get_string('restoreuser', 'local_user_restore'));

        // Display current user info.
        if ($user) {
            $userinfo = \local_user_restore\restore_manager::get_user_display_info($user);

            $mform->addElement('static', 'userid_display', get_string('userid', 'local_user_restore'), $user->id);
            $mform->addElement('static', 'currentusername_display', get_string('currentusername', 'local_user_restore'), $user->username);
            $mform->addElement('static', 'firstname_display', get_string('firstname', 'local_user_restore'), $user->firstname);
            $mform->addElement('static', 'lastname_display', get_string('lastname', 'local_user_restore'), $user->lastname);
            $mform->addElement('static', 'timedeleted_display', get_string('timedeleted', 'local_user_restore'), $userinfo->deletiontime_formatted);

            // Show probable original email if detected.
            if (!empty($userinfo->probable_original_email)) {
                $mform->addElement('static', 'originalemail_display', get_string('email', 'local_user_restore') . ' (probable)',
                    $userinfo->probable_original_email);
            }
        }

        // Info message.
        $mform->addElement('html', '<div class="alert alert-info">' .
            get_string('restoreinfo', 'local_user_restore') . '</div>');

        // New credentials section.
        $mform->addElement('header', 'newcredentials', get_string('newusername', 'local_user_restore'));

        // New username.
        $mform->addElement('text', 'newusername', get_string('newusername', 'local_user_restore'));
        $mform->setType('newusername', PARAM_USERNAME);
        $mform->addRule('newusername', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('newusername', 'newusername', 'local_user_restore');

        // New email.
        $mform->addElement('text', 'newemail', get_string('newemail', 'local_user_restore'));
        $mform->setType('newemail', PARAM_EMAIL);
        $mform->addRule('newemail', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('newemail', 'newemail', 'local_user_restore');

        // Pre-fill email if probable original was detected.
        if ($user) {
            $userinfo = \local_user_restore\restore_manager::get_user_display_info($user);
            if (!empty($userinfo->probable_original_email)) {
                $mform->setDefault('newemail', $userinfo->probable_original_email);
            }
        }

        // New password.
        $mform->addElement('passwordunmask', 'newpassword', get_string('newpassword', 'local_user_restore'));
        $mform->setType('newpassword', PARAM_RAW);
        $mform->addRule('newpassword', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('newpassword', 'newpassword', 'local_user_restore');

        // Send notification checkbox.
        $mform->addElement('advcheckbox', 'sendnotification', get_string('sendnotification', 'local_user_restore'));
        $mform->setDefault('sendnotification', 0);
        $mform->addHelpButton('sendnotification', 'sendnotification', 'local_user_restore');

        // Hidden user ID.
        $mform->addElement('hidden', 'userid');
        $mform->setType('userid', PARAM_INT);
        if ($user) {
            $mform->setDefault('userid', $user->id);
        }

        // Action buttons.
        $this->add_action_buttons(true, get_string('restore', 'local_user_restore'));
    }

    /**
     * Validate the form data.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $userid = $data['userid'] ?? 0;

        // Validate username.
        if (!empty($data['newusername'])) {
            if (!\local_user_restore\restore_manager::is_username_available($data['newusername'], $userid)) {
                $errors['newusername'] = get_string('usernameexists', 'local_user_restore');
            }
        }

        // Validate email.
        if (!empty($data['newemail'])) {
            if (!validate_email($data['newemail'])) {
                $errors['newemail'] = get_string('invalidemail');
            } else if (!\local_user_restore\restore_manager::is_email_available($data['newemail'], $userid)) {
                $errors['newemail'] = get_string('emailexists', 'local_user_restore');
            }
        }

        // Validate password strength.
        if (!empty($data['newpassword'])) {
            $errmsg = '';
            if (!check_password_policy($data['newpassword'], $errmsg)) {
                $errors['newpassword'] = $errmsg;
            }
        }

        return $errors;
    }
}
