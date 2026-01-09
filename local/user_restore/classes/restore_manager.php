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
 * Restore manager class for local_user_restore plugin.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage user restoration operations.
 */
class restore_manager {

    /**
     * Get all deleted users.
     *
     * @param string $search Optional search term.
     * @param string $sort Sort field.
     * @param string $dir Sort direction.
     * @param int $page Page number.
     * @param int $perpage Items per page.
     * @return array Array with 'users' and 'total' keys.
     */
    public static function get_deleted_users(
        string $search = '',
        string $sort = 'timemodified',
        string $dir = 'DESC',
        int $page = 0,
        int $perpage = 20
    ): array {
        global $DB;

        $params = ['deleted' => 1];
        $where = 'deleted = :deleted';

        // Add search conditions if provided.
        if (!empty($search)) {
            $searchlike = '%' . $DB->sql_like_escape($search) . '%';
            $where .= ' AND (' . $DB->sql_like('username', ':search1', false) .
                      ' OR ' . $DB->sql_like('firstname', ':search2', false) .
                      ' OR ' . $DB->sql_like('lastname', ':search3', false) .
                      ' OR ' . $DB->sql_like('idnumber', ':search4', false) . ')';
            $params['search1'] = $searchlike;
            $params['search2'] = $searchlike;
            $params['search3'] = $searchlike;
            $params['search4'] = $searchlike;
        }

        // Validate sort field.
        $allowedsorts = ['id', 'username', 'firstname', 'lastname', 'email', 'timemodified'];
        if (!in_array($sort, $allowedsorts)) {
            $sort = 'timemodified';
        }

        // Validate sort direction.
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';

        $total = $DB->count_records_select('user', $where, $params);
        $users = $DB->get_records_select(
            'user',
            $where,
            $params,
            "$sort $dir",
            'id, username, firstname, lastname, email, idnumber, timemodified, timecreated',
            $page * $perpage,
            $perpage
        );

        return [
            'users' => $users,
            'total' => $total,
        ];
    }

    /**
     * Get a single deleted user by ID.
     *
     * @param int $userid The user ID.
     * @return \stdClass|false The user record or false if not found/not deleted.
     */
    public static function get_deleted_user(int $userid) {
        global $DB;

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 1]);
        return $user;
    }

    /**
     * Check if a username is available.
     *
     * @param string $username The username to check.
     * @param int $excludeuserid User ID to exclude from check.
     * @return bool True if available, false otherwise.
     */
    public static function is_username_available(string $username, int $excludeuserid = 0): bool {
        global $DB;

        $params = ['username' => strtolower($username), 'deleted' => 0];
        $where = 'username = :username AND deleted = :deleted';

        if ($excludeuserid > 0) {
            $where .= ' AND id != :excludeid';
            $params['excludeid'] = $excludeuserid;
        }

        return !$DB->record_exists_select('user', $where, $params);
    }

    /**
     * Check if an email is available.
     *
     * @param string $email The email to check.
     * @param int $excludeuserid User ID to exclude from check.
     * @return bool True if available, false otherwise.
     */
    public static function is_email_available(string $email, int $excludeuserid = 0): bool {
        global $DB;

        $params = ['email' => strtolower($email), 'deleted' => 0];
        $where = 'email = :email AND deleted = :deleted';

        if ($excludeuserid > 0) {
            $where .= ' AND id != :excludeid';
            $params['excludeid'] = $excludeuserid;
        }

        return !$DB->record_exists_select('user', $where, $params);
    }

    /**
     * Restore a deleted user.
     *
     * @param int $userid The user ID to restore.
     * @param string $newusername The new username.
     * @param string $newemail The new email.
     * @param string $newpassword The new password (plain text).
     * @param bool $sendnotification Whether to send notification email.
     * @param bool $restoredata Whether to restore enrollments, groups, etc. from snapshots.
     * @return array Result with 'success', 'message', and 'details' keys.
     */
    public static function restore_user(
        int $userid,
        string $newusername,
        string $newemail,
        string $newpassword,
        bool $sendnotification = false,
        bool $restoredata = true
    ): array {
        global $DB, $CFG, $USER;

        require_once($CFG->dirroot . '/lib/moodlelib.php');
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/lib/enrollib.php');
        require_once($CFG->dirroot . '/group/lib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');

        // Validate the user exists and is deleted.
        $user = self::get_deleted_user($userid);
        if (!$user) {
            return [
                'success' => false,
                'message' => get_string('invaliduser', 'local_user_restore'),
                'details' => [],
            ];
        }

        // Validate username availability.
        if (!self::is_username_available($newusername, $userid)) {
            return [
                'success' => false,
                'message' => get_string('usernameexists', 'local_user_restore'),
                'details' => [],
            ];
        }

        // Validate email availability.
        if (!self::is_email_available($newemail, $userid)) {
            return [
                'success' => false,
                'message' => get_string('emailexists', 'local_user_restore'),
                'details' => [],
            ];
        }

        $restorationdetails = [];

        try {
            // Start transaction.
            $transaction = $DB->start_delegated_transaction();

            // Prepare user update.
            $updateuser = new \stdClass();
            $updateuser->id = $userid;
            $updateuser->deleted = 0;
            $updateuser->username = strtolower($newusername);
            $updateuser->email = strtolower($newemail);
            $updateuser->confirmed = 1;
            $updateuser->suspended = 0;
            $updateuser->timemodified = time();

            // Update the user record.
            $DB->update_record('user', $updateuser);

            // Set the new password.
            $userobj = $DB->get_record('user', ['id' => $userid]);
            $userobj->password = hash_internal_user_password($newpassword);
            $DB->set_field('user', 'password', $userobj->password, ['id' => $userid]);

            // Restore data from snapshots if enabled and available.
            if ($restoredata && snapshot_manager::has_snapshots($userid)) {
                $restorationdetails = self::restore_user_data($userid);
            }

            // Trigger event.
            $event = \local_user_restore\event\user_restored::create([
                'objectid' => $userid,
                'context' => \context_system::instance(),
                'relateduserid' => $userid,
                'other' => [
                    'username' => $newusername,
                    'restoredby' => $USER->id,
                    'restored_data' => $restorationdetails,
                ],
            ]);
            $event->trigger();

            // Commit transaction.
            $transaction->allow_commit();

            // Send notification if requested.
            if ($sendnotification) {
                self::send_restore_notification($userobj, $newpassword);
            }

            // Log the action if enabled.
            if (get_config('local_user_restore', 'enablelogging')) {
                self::log_restore($userid, $newusername, $USER->id);
            }

            // Clean up snapshots after successful restoration.
            if ($restoredata) {
                snapshot_manager::delete_snapshots($userid);
            }

            return [
                'success' => true,
                'message' => get_string('userrestored', 'local_user_restore', $newusername),
                'details' => $restorationdetails,
            ];

        } catch (\Exception $e) {
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            return [
                'success' => false,
                'message' => get_string('userrestoreerror', 'local_user_restore', $e->getMessage()),
                'details' => [],
            ];
        }
    }

    /**
     * Restore user data from snapshots (enrollments, groups, cohorts, roles, grades).
     *
     * @param int $userid The user ID.
     * @return array Details of what was restored.
     */
    protected static function restore_user_data(int $userid): array {
        $details = [
            'enrolments' => self::restore_enrolments($userid),
            'groups' => self::restore_groups($userid),
            'cohorts' => self::restore_cohorts($userid),
            'roles' => self::restore_roles($userid),
            'grades' => self::restore_grades($userid),
            'preferences' => self::restore_preferences($userid),
        ];

        return $details;
    }

    /**
     * Restore user enrollments from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_enrolments(int $userid): array {
        global $DB;

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0, 'courses' => []];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_ENROLMENT);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data) {
                $results['failed']++;
                continue;
            }

            $courseid = $data['courseid'] ?? 0;
            if (!$courseid || !$DB->record_exists('course', ['id' => $courseid])) {
                $results['skipped']++;
                continue;
            }

            // Find a valid enrol instance for this course.
            $enrolinstance = $DB->get_record('enrol', [
                'courseid' => $courseid,
                'enrol' => $data['enrol'] ?? 'manual',
                'status' => ENROL_INSTANCE_ENABLED,
            ]);

            if (!$enrolinstance) {
                // Try to find any manual enrol instance.
                $enrolinstance = $DB->get_record('enrol', [
                    'courseid' => $courseid,
                    'enrol' => 'manual',
                ]);
            }

            if (!$enrolinstance) {
                $results['skipped']++;
                continue;
            }

            // Check if user is already enrolled.
            if ($DB->record_exists('user_enrolments', ['enrolid' => $enrolinstance->id, 'userid' => $userid])) {
                $results['skipped']++;
                continue;
            }

            try {
                $enrolplugin = enrol_get_plugin($enrolinstance->enrol);
                if ($enrolplugin) {
                    $enrolplugin->enrol_user(
                        $enrolinstance,
                        $userid,
                        $data['roleid'] ?? null,
                        $data['timestart'] ?? 0,
                        $data['timeend'] ?? 0,
                        $data['status'] ?? ENROL_USER_ACTIVE
                    );
                    $results['restored']++;
                    $results['courses'][] = $data['courseshortname'] ?? $data['coursename'] ?? $courseid;
                }
            } catch (\Exception $e) {
                $results['failed']++;
                debugging('Failed to restore enrolment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $results;
    }

    /**
     * Restore user group memberships from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_groups(int $userid): array {
        global $DB;

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0, 'groups' => []];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_GROUP);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data) {
                $results['failed']++;
                continue;
            }

            $groupid = $data['groupid'] ?? 0;
            if (!$groupid || !$DB->record_exists('groups', ['id' => $groupid])) {
                $results['skipped']++;
                continue;
            }

            // Check if user is already a member.
            if ($DB->record_exists('groups_members', ['groupid' => $groupid, 'userid' => $userid])) {
                $results['skipped']++;
                continue;
            }

            try {
                groups_add_member($groupid, $userid);
                $results['restored']++;
                $results['groups'][] = $data['groupname'] ?? $groupid;
            } catch (\Exception $e) {
                $results['failed']++;
                debugging('Failed to restore group membership: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $results;
    }

    /**
     * Restore user cohort memberships from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_cohorts(int $userid): array {
        global $DB;

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0, 'cohorts' => []];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_COHORT);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data) {
                $results['failed']++;
                continue;
            }

            $cohortid = $data['cohortid'] ?? 0;
            if (!$cohortid || !$DB->record_exists('cohort', ['id' => $cohortid])) {
                $results['skipped']++;
                continue;
            }

            // Check if user is already a member.
            if ($DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid])) {
                $results['skipped']++;
                continue;
            }

            try {
                cohort_add_member($cohortid, $userid);
                $results['restored']++;
                $results['cohorts'][] = $data['cohortname'] ?? $cohortid;
            } catch (\Exception $e) {
                $results['failed']++;
                debugging('Failed to restore cohort membership: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $results;
    }

    /**
     * Restore user role assignments from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_roles(int $userid): array {
        global $DB;

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0, 'roles' => []];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_ROLE);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data) {
                $results['failed']++;
                continue;
            }

            $roleid = $data['roleid'] ?? 0;
            $contextid = $data['contextid'] ?? 0;

            if (!$roleid || !$contextid) {
                $results['skipped']++;
                continue;
            }

            // Verify role and context still exist.
            if (!$DB->record_exists('role', ['id' => $roleid])) {
                $results['skipped']++;
                continue;
            }

            if (!$DB->record_exists('context', ['id' => $contextid])) {
                $results['skipped']++;
                continue;
            }

            // Check if role assignment already exists.
            if ($DB->record_exists('role_assignments', [
                'roleid' => $roleid,
                'contextid' => $contextid,
                'userid' => $userid,
            ])) {
                $results['skipped']++;
                continue;
            }

            try {
                role_assign(
                    $roleid,
                    $userid,
                    $contextid,
                    $data['component'] ?? '',
                    $data['itemid'] ?? 0
                );
                $results['restored']++;
                $results['roles'][] = $data['rolename'] ?? $roleid;
            } catch (\Exception $e) {
                $results['failed']++;
                debugging('Failed to restore role assignment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $results;
    }

    /**
     * Restore user grades from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_grades(int $userid): array {
        global $DB, $CFG;

        require_once($CFG->libdir . '/gradelib.php');

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_GRADE);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data) {
                $results['failed']++;
                continue;
            }

            $itemid = $data['itemid'] ?? 0;
            if (!$itemid || !$DB->record_exists('grade_items', ['id' => $itemid])) {
                $results['skipped']++;
                continue;
            }

            // Check if grade already exists.
            if ($DB->record_exists('grade_grades', ['itemid' => $itemid, 'userid' => $userid])) {
                $results['skipped']++;
                continue;
            }

            try {
                $graderecord = new \stdClass();
                $graderecord->itemid = $itemid;
                $graderecord->userid = $userid;
                $graderecord->rawgrade = $data['rawgrade'] ?? null;
                $graderecord->rawgrademax = $data['rawgrademax'] ?? 100;
                $graderecord->rawgrademin = $data['rawgrademin'] ?? 0;
                $graderecord->finalgrade = $data['finalgrade'] ?? null;
                $graderecord->feedback = $data['feedback'] ?? null;
                $graderecord->feedbackformat = $data['feedbackformat'] ?? FORMAT_PLAIN;
                $graderecord->timecreated = time();
                $graderecord->timemodified = time();

                $DB->insert_record('grade_grades', $graderecord);
                $results['restored']++;
            } catch (\Exception $e) {
                $results['failed']++;
                debugging('Failed to restore grade: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        return $results;
    }

    /**
     * Restore user preferences from snapshots.
     *
     * @param int $userid The user ID.
     * @return array Restoration results.
     */
    protected static function restore_preferences(int $userid): array {
        global $DB;

        $results = ['restored' => 0, 'failed' => 0, 'skipped' => 0];
        $snapshots = snapshot_manager::get_snapshots($userid, snapshot_manager::TYPE_PREFERENCE);

        foreach ($snapshots as $snapshot) {
            $data = json_decode($snapshot->datajson, true);
            if (!$data || !is_array($data)) {
                $results['failed']++;
                continue;
            }

            foreach ($data as $name => $value) {
                // Check if preference already exists.
                if ($DB->record_exists('user_preferences', ['userid' => $userid, 'name' => $name])) {
                    $results['skipped']++;
                    continue;
                }

                try {
                    set_user_preference($name, $value, $userid);
                    $results['restored']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Send notification email to restored user.
     *
     * @param \stdClass $user The user object.
     * @param string $password The plain text password.
     * @return bool True on success.
     */
    protected static function send_restore_notification(\stdClass $user, string $password): bool {
        global $CFG, $SITE;

        $subject = get_string('pluginname', 'local_user_restore') . ': ' .
                   get_string('restoredsuccessfully', 'local_user_restore');

        $messagetext = "Your account has been restored.\n\n";
        $messagetext .= "Username: {$user->username}\n";
        $messagetext .= "Password: {$password}\n\n";
        $messagetext .= "Please login at: {$CFG->wwwroot}/login/\n";
        $messagetext .= "We recommend changing your password after logging in.\n\n";
        $messagetext .= "Regards,\n{$SITE->fullname}";

        $messagehtml = "<p>Your account has been restored.</p>";
        $messagehtml .= "<p><strong>Username:</strong> {$user->username}<br>";
        $messagehtml .= "<strong>Password:</strong> {$password}</p>";
        $messagehtml .= "<p>Please login at: <a href=\"{$CFG->wwwroot}/login/\">{$CFG->wwwroot}/login/</a></p>";
        $messagehtml .= "<p>We recommend changing your password after logging in.</p>";
        $messagehtml .= "<p>Regards,<br>{$SITE->fullname}</p>";

        return email_to_user($user, \core_user::get_support_user(), $subject, $messagetext, $messagehtml);
    }

    /**
     * Log a restoration action.
     *
     * @param int $userid The restored user ID.
     * @param string $username The new username.
     * @param int $restoredby The ID of the user who performed the restoration.
     */
    protected static function log_restore(int $userid, string $username, int $restoredby): void {
        global $DB;

        // We can use the standard Moodle logging via events.
        // The event is already triggered in restore_user().
        // Additional custom logging could be added here if needed.
    }

    /**
     * Get user info for display (tries to find original data in logs if available).
     *
     * @param \stdClass $user The deleted user record.
     * @return \stdClass Enhanced user object with additional info.
     */
    public static function get_user_display_info(\stdClass $user): \stdClass {
        global $DB;

        $info = clone $user;

        // Try to determine deletion time from timemodified.
        $info->deletiontime = $user->timemodified;

        // Format the deletion time.
        $info->deletiontime_formatted = userdate($user->timemodified, get_string('strftimedatetime', 'langconfig'));

        // Check if username looks like a deleted username (email.timestamp.randomstring).
        if (preg_match('/^(.+)\.(\d+)\.[a-f0-9]+$/', $user->username, $matches)) {
            $info->probable_original_email = $matches[1];
            $info->deletion_timestamp = (int) $matches[2];
        }

        return $info;
    }
}
