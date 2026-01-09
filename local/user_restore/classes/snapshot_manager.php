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
 * Snapshot manager class for local_user_restore plugin.
 *
 * Captures and stores user data before deletion for later restoration.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_user_restore;

defined('MOODLE_INTERNAL') || die();

/**
 * Class to manage user data snapshots for restoration.
 */
class snapshot_manager {

    /** @var int The user ID. */
    protected $userid;

    /** @var string Table name for storing snapshots. */
    const TABLE = 'local_user_restore_data';

    /** Data types constants. */
    const TYPE_ENROLMENT = 'enrolment';
    const TYPE_GROUP = 'group';
    const TYPE_COHORT = 'cohort';
    const TYPE_ROLE = 'role';
    const TYPE_GRADE = 'grade';
    const TYPE_USERINFO = 'userinfo';
    const TYPE_PREFERENCE = 'preference';
    const TYPE_COMPLETION = 'completion';

    /**
     * Constructor.
     *
     * @param int $userid The user ID.
     */
    public function __construct(int $userid) {
        $this->userid = $userid;
    }

    /**
     * Capture all user data.
     */
    public function capture_all(): void {
        $this->capture_user_info();
        $this->capture_enrolments();
        $this->capture_groups();
        $this->capture_cohorts();
        $this->capture_roles();
        $this->capture_grades();
        $this->capture_preferences();
        $this->capture_completions();
    }

    /**
     * Store a data snapshot.
     *
     * @param string $datatype The type of data.
     * @param mixed $data The data to store.
     * @param string|null $datakey Optional key identifier.
     */
    protected function store_snapshot(string $datatype, $data, ?string $datakey = null): void {
        global $DB;

        $record = new \stdClass();
        $record->userid = $this->userid;
        $record->datatype = $datatype;
        $record->datakey = $datakey;
        $record->datajson = json_encode($data);
        $record->timecreated = time();

        $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Capture original user info (username, email, etc.).
     */
    protected function capture_user_info(): void {
        global $DB;

        $user = $DB->get_record('user', ['id' => $this->userid]);
        if (!$user) {
            return;
        }

        $userinfo = [
            'username' => $user->username,
            'email' => $user->email,
            'firstname' => $user->firstname,
            'lastname' => $user->lastname,
            'idnumber' => $user->idnumber,
            'auth' => $user->auth,
            'lang' => $user->lang,
            'timezone' => $user->timezone,
            'department' => $user->department,
            'institution' => $user->institution,
            'city' => $user->city,
            'country' => $user->country,
        ];

        $this->store_snapshot(self::TYPE_USERINFO, $userinfo);
    }

    /**
     * Capture user enrolments.
     */
    protected function capture_enrolments(): void {
        global $DB;

        $sql = "SELECT ue.id, ue.enrolid, ue.status, ue.timestart, ue.timeend, ue.timecreated,
                       e.enrol, e.courseid, e.roleid,
                       c.fullname as coursename, c.shortname as courseshortname
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                  JOIN {course} c ON c.id = e.courseid
                 WHERE ue.userid = :userid";

        $enrolments = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($enrolments as $enrolment) {
            $this->store_snapshot(
                self::TYPE_ENROLMENT,
                (array) $enrolment,
                'course_' . $enrolment->courseid
            );
        }
    }

    /**
     * Capture group memberships.
     */
    protected function capture_groups(): void {
        global $DB;

        $sql = "SELECT gm.id, gm.groupid, gm.timeadded,
                       g.name as groupname, g.courseid,
                       c.fullname as coursename
                  FROM {groups_members} gm
                  JOIN {groups} g ON g.id = gm.groupid
                  JOIN {course} c ON c.id = g.courseid
                 WHERE gm.userid = :userid";

        $groups = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($groups as $group) {
            $this->store_snapshot(
                self::TYPE_GROUP,
                (array) $group,
                'group_' . $group->groupid
            );
        }
    }

    /**
     * Capture cohort memberships.
     */
    protected function capture_cohorts(): void {
        global $DB;

        $sql = "SELECT cm.id, cm.cohortid, cm.timeadded,
                       c.name as cohortname, c.idnumber as cohortidnumber,
                       c.contextid
                  FROM {cohort_members} cm
                  JOIN {cohort} c ON c.id = cm.cohortid
                 WHERE cm.userid = :userid";

        $cohorts = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($cohorts as $cohort) {
            $this->store_snapshot(
                self::TYPE_COHORT,
                (array) $cohort,
                'cohort_' . $cohort->cohortid
            );
        }
    }

    /**
     * Capture role assignments.
     */
    protected function capture_roles(): void {
        global $DB;

        $sql = "SELECT ra.id, ra.roleid, ra.contextid, ra.component, ra.itemid, ra.timemodified,
                       r.shortname as rolename,
                       ctx.contextlevel, ctx.instanceid
                  FROM {role_assignments} ra
                  JOIN {role} r ON r.id = ra.roleid
                  JOIN {context} ctx ON ctx.id = ra.contextid
                 WHERE ra.userid = :userid";

        $roles = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($roles as $role) {
            $this->store_snapshot(
                self::TYPE_ROLE,
                (array) $role,
                'role_' . $role->roleid . '_ctx_' . $role->contextid
            );
        }
    }

    /**
     * Capture grades.
     */
    protected function capture_grades(): void {
        global $DB;

        $sql = "SELECT gg.id, gg.itemid, gg.rawgrade, gg.rawgrademax, gg.rawgrademin,
                       gg.finalgrade, gg.feedback, gg.feedbackformat,
                       gg.timecreated, gg.timemodified,
                       gi.itemname, gi.itemtype, gi.itemmodule, gi.iteminstance, gi.courseid,
                       c.fullname as coursename
                  FROM {grade_grades} gg
                  JOIN {grade_items} gi ON gi.id = gg.itemid
                  JOIN {course} c ON c.id = gi.courseid
                 WHERE gg.userid = :userid";

        $grades = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($grades as $grade) {
            $this->store_snapshot(
                self::TYPE_GRADE,
                (array) $grade,
                'item_' . $grade->itemid
            );
        }
    }

    /**
     * Capture user preferences.
     */
    protected function capture_preferences(): void {
        global $DB;

        $preferences = $DB->get_records('user_preferences', ['userid' => $this->userid]);

        if (!empty($preferences)) {
            $prefdata = [];
            foreach ($preferences as $pref) {
                $prefdata[$pref->name] = $pref->value;
            }
            $this->store_snapshot(self::TYPE_PREFERENCE, $prefdata);
        }
    }

    /**
     * Capture course completions.
     */
    protected function capture_completions(): void {
        global $DB;

        // Course completions.
        $sql = "SELECT cc.id, cc.course, cc.timeenrolled, cc.timestarted, cc.timecompleted,
                       c.fullname as coursename, c.shortname as courseshortname
                  FROM {course_completions} cc
                  JOIN {course} c ON c.id = cc.course
                 WHERE cc.userid = :userid";

        $completions = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($completions as $completion) {
            $this->store_snapshot(
                self::TYPE_COMPLETION,
                (array) $completion,
                'course_' . $completion->course
            );
        }

        // Activity completions.
        $sql = "SELECT cmc.id, cmc.coursemoduleid, cmc.completionstate, cmc.viewed,
                       cmc.timemodified,
                       cm.course, cm.module, cm.instance,
                       c.fullname as coursename
                  FROM {course_modules_completion} cmc
                  JOIN {course_modules} cm ON cm.id = cmc.coursemoduleid
                  JOIN {course} c ON c.id = cm.course
                 WHERE cmc.userid = :userid";

        $activitycompletions = $DB->get_records_sql($sql, ['userid' => $this->userid]);

        foreach ($activitycompletions as $actcompletion) {
            $this->store_snapshot(
                self::TYPE_COMPLETION,
                array_merge((array) $actcompletion, ['type' => 'activity']),
                'activity_' . $actcompletion->coursemoduleid
            );
        }
    }

    /**
     * Get all stored snapshots for a user.
     *
     * @param int $userid The user ID.
     * @param string|null $datatype Optional filter by data type.
     * @return array Array of snapshot records.
     */
    public static function get_snapshots(int $userid, ?string $datatype = null): array {
        global $DB;

        $params = ['userid' => $userid];
        $where = 'userid = :userid';

        if ($datatype !== null) {
            $where .= ' AND datatype = :datatype';
            $params['datatype'] = $datatype;
        }

        return $DB->get_records_select(self::TABLE, $where, $params, 'timecreated DESC');
    }

    /**
     * Check if snapshots exist for a user.
     *
     * @param int $userid The user ID.
     * @return bool True if snapshots exist.
     */
    public static function has_snapshots(int $userid): bool {
        global $DB;
        return $DB->record_exists(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Delete all snapshots for a user.
     *
     * @param int $userid The user ID.
     */
    public static function delete_snapshots(int $userid): void {
        global $DB;
        $DB->delete_records(self::TABLE, ['userid' => $userid]);
    }

    /**
     * Get summary of stored data for display.
     *
     * @param int $userid The user ID.
     * @return array Summary data.
     */
    public static function get_snapshot_summary(int $userid): array {
        global $DB;

        $sql = "SELECT datatype, COUNT(*) as count
                  FROM {" . self::TABLE . "}
                 WHERE userid = :userid
              GROUP BY datatype";

        $counts = $DB->get_records_sql_menu($sql, ['userid' => $userid]);

        return [
            'enrolments' => $counts[self::TYPE_ENROLMENT] ?? 0,
            'groups' => $counts[self::TYPE_GROUP] ?? 0,
            'cohorts' => $counts[self::TYPE_COHORT] ?? 0,
            'roles' => $counts[self::TYPE_ROLE] ?? 0,
            'grades' => $counts[self::TYPE_GRADE] ?? 0,
            'completions' => $counts[self::TYPE_COMPLETION] ?? 0,
            'has_userinfo' => isset($counts[self::TYPE_USERINFO]),
            'has_preferences' => isset($counts[self::TYPE_PREFERENCE]),
        ];
    }
}
