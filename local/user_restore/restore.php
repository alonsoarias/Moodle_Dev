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
 * Restore user page for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Parameters.
$userid = required_param('userid', PARAM_INT);

// Setup page.
require_login();
$context = context_system::instance();
require_capability('local/user_restore:restore', $context);

// Get the deleted user.
$user = \local_user_restore\restore_manager::get_deleted_user($userid);
if (!$user) {
    throw new moodle_exception('invaliduser', 'local_user_restore');
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_restore/restore.php', ['userid' => $userid]));
$PAGE->set_title(get_string('restoreuser', 'local_user_restore'));
$PAGE->set_heading(get_string('restoreuser', 'local_user_restore'));
$PAGE->set_pagelayout('admin');

// Add navigation.
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(
    get_string('deletedusers', 'local_user_restore'),
    new moodle_url('/local/user_restore/index.php')
);
$PAGE->navbar->add(get_string('restoreuser', 'local_user_restore'));

// Create form.
$returnurl = new moodle_url('/local/user_restore/index.php');
$form = new \local_user_restore\form\restore_form(null, ['user' => $user]);

// Handle form submission.
if ($form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $form->get_data()) {
    // Process the restoration.
    $result = \local_user_restore\restore_manager::restore_user(
        $data->userid,
        $data->newusername,
        $data->newemail,
        $data->newpassword,
        !empty($data->sendnotification),
        !empty($data->restoredata)
    );

    if ($result['success']) {
        // Build detailed message if data was restored.
        $message = $result['message'];
        if (!empty($result['details'])) {
            $details = $result['details'];
            $restoredinfo = [];

            if (!empty($details['enrolments']['restored'])) {
                $restoredinfo[] = get_string('enrolmentsrestored', 'local_user_restore', $details['enrolments']['restored']);
            }
            if (!empty($details['groups']['restored'])) {
                $restoredinfo[] = get_string('groupsrestored', 'local_user_restore', $details['groups']['restored']);
            }
            if (!empty($details['cohorts']['restored'])) {
                $restoredinfo[] = get_string('cohortsrestored', 'local_user_restore', $details['cohorts']['restored']);
            }
            if (!empty($details['roles']['restored'])) {
                $restoredinfo[] = get_string('rolesrestored', 'local_user_restore', $details['roles']['restored']);
            }
            if (!empty($details['grades']['restored'])) {
                $restoredinfo[] = get_string('gradesrestored', 'local_user_restore', $details['grades']['restored']);
            }

            if (!empty($restoredinfo)) {
                $message .= ' ' . implode(', ', $restoredinfo) . '.';
            }
        }
        redirect($returnurl, $message, null, \core\output\notification::NOTIFY_SUCCESS);
    } else {
        redirect(
            new moodle_url('/local/user_restore/restore.php', ['userid' => $userid]),
            $result['message'],
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('restoreuser', 'local_user_restore') . ': ' . fullname($user));

// Back link.
echo html_writer::start_div('mb-3');
echo html_writer::link(
    $returnurl,
    '&laquo; ' . get_string('back', 'local_user_restore'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

// Display the form.
$form->display();

echo $OUTPUT->footer();
