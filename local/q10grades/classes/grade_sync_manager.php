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
 * Grade synchronization manager for Q10.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/gradelib.php');

use context_course;
use grade_item;
use grade_grade;
use stdClass;
use moodle_exception;

/**
 * Class to manage grade synchronization between Moodle and Q10.
 */
class grade_sync_manager {

    /** @var q10_api_client API client */
    private $apiclient;

    /** @var int Course ID */
    private $courseid;

    /** @var stdClass Course mapping data */
    private $mapping;

    /** @var string Grade scale to use */
    private $gradescale;

    /** @var string User mapping field */
    private $usermappingfield;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID.
     * @param q10_api_client|null $apiclient API client (optional).
     */
    public function __construct(int $courseid, ?q10_api_client $apiclient = null) {
        $this->courseid = $courseid;
        $this->apiclient = $apiclient ?? new q10_api_client();
        $this->gradescale = get_config('local_q10grades', 'gradescale') ?: 'percentage';
        $this->usermappingfield = get_config('local_q10grades', 'usermappingfield') ?: 'idnumber';
        $this->load_mapping();
    }

    /**
     * Load course mapping from database.
     */
    private function load_mapping(): void {
        global $DB;

        $this->mapping = $DB->get_record('local_q10grades_mapping', ['courseid' => $this->courseid]);
    }

    /**
     * Check if course has a valid mapping.
     *
     * @return bool True if mapping exists and is enabled.
     */
    public function has_mapping(): bool {
        return $this->mapping && $this->mapping->enabled;
    }

    /**
     * Get course mapping.
     *
     * @return stdClass|null Mapping record or null.
     */
    public function get_mapping(): ?stdClass {
        return $this->mapping;
    }

    /**
     * Save or update course mapping.
     *
     * @param string $subjectid Q10 subject ID.
     * @param string|null $periodid Q10 period ID.
     * @param string|null $groupid Q10 group ID.
     * @param bool $enabled Whether sync is enabled.
     * @return int Mapping record ID.
     */
    public function save_mapping(string $subjectid, ?string $periodid = null, ?string $groupid = null, bool $enabled = true): int {
        global $DB, $USER;

        $now = time();

        if ($this->mapping) {
            $this->mapping->q10_subject_id = $subjectid;
            $this->mapping->q10_period_id = $periodid;
            $this->mapping->q10_group_id = $groupid;
            $this->mapping->enabled = $enabled ? 1 : 0;
            $this->mapping->timemodified = $now;
            $this->mapping->usermodified = $USER->id;

            $DB->update_record('local_q10grades_mapping', $this->mapping);
            return $this->mapping->id;
        } else {
            $record = new stdClass();
            $record->courseid = $this->courseid;
            $record->q10_subject_id = $subjectid;
            $record->q10_period_id = $periodid;
            $record->q10_group_id = $groupid;
            $record->enabled = $enabled ? 1 : 0;
            $record->timecreated = $now;
            $record->timemodified = $now;
            $record->usermodified = $USER->id;

            $id = $DB->insert_record('local_q10grades_mapping', $record);
            $this->mapping = $DB->get_record('local_q10grades_mapping', ['id' => $id]);
            return $id;
        }
    }

    /**
     * Get user Q10 mapping.
     *
     * @param int $userid Moodle user ID.
     * @return stdClass|null User mapping or null.
     */
    public function get_user_mapping(int $userid): ?stdClass {
        global $DB;
        return $DB->get_record('local_q10grades_usermapping', ['userid' => $userid]) ?: null;
    }

    /**
     * Save user Q10 mapping.
     *
     * @param int $userid Moodle user ID.
     * @param string $q10studentid Q10 student ID.
     * @param string|null $q10studentcode Q10 student code.
     * @return int Mapping record ID.
     */
    public function save_user_mapping(int $userid, string $q10studentid, ?string $q10studentcode = null): int {
        global $DB;

        $now = time();
        $existing = $this->get_user_mapping($userid);

        if ($existing) {
            $existing->q10_student_id = $q10studentid;
            $existing->q10_student_code = $q10studentcode;
            $existing->timemodified = $now;
            $DB->update_record('local_q10grades_usermapping', $existing);
            return $existing->id;
        } else {
            $record = new stdClass();
            $record->userid = $userid;
            $record->q10_student_id = $q10studentid;
            $record->q10_student_code = $q10studentcode;
            $record->timecreated = $now;
            $record->timemodified = $now;
            return $DB->insert_record('local_q10grades_usermapping', $record);
        }
    }

    /**
     * Get Q10 student ID for a Moodle user.
     *
     * @param stdClass $user Moodle user object.
     * @return string|null Q10 student ID or null.
     */
    public function get_q10_student_id(stdClass $user): ?string {
        // First check explicit mapping.
        $mapping = $this->get_user_mapping($user->id);
        if ($mapping) {
            return $mapping->q10_student_id;
        }

        // Fall back to configured mapping field.
        $field = $this->usermappingfield;
        if (!empty($user->$field)) {
            return $user->$field;
        }

        return null;
    }

    /**
     * Get all grades for the course.
     *
     * @param bool $finalonly Only get final course grade.
     * @return array Array of grade data indexed by user ID.
     */
    public function get_course_grades(bool $finalonly = true): array {
        global $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $context = context_course::instance($this->courseid);
        $enrolledusers = get_enrolled_users($context, 'local/q10grades:sync', 0, 'u.*', null, 0, 0, true);

        $grades = [];

        if ($finalonly) {
            $gradeitem = grade_item::fetch(['itemtype' => 'course', 'courseid' => $this->courseid]);

            if (!$gradeitem) {
                return $grades;
            }

            foreach ($enrolledusers as $user) {
                $gradegrade = new grade_grade(['itemid' => $gradeitem->id, 'userid' => $user->id]);

                if ($gradegrade->finalgrade === null) {
                    continue;
                }

                $grades[$user->id] = [
                    'user' => $user,
                    'q10_student_id' => $this->get_q10_student_id($user),
                    'finalgrade' => $this->format_grade($gradegrade->finalgrade, $gradeitem),
                    'rawgrade' => $gradegrade->finalgrade,
                    'gradeitem' => $gradeitem,
                ];
            }
        } else {
            // Get all grade items.
            $gradeitems = grade_item::fetch_all(['courseid' => $this->courseid]);

            foreach ($enrolledusers as $user) {
                $usergrades = [];

                foreach ($gradeitems as $gradeitem) {
                    $gradegrade = new grade_grade(['itemid' => $gradeitem->id, 'userid' => $user->id]);

                    if ($gradegrade->finalgrade !== null) {
                        $usergrades[$gradeitem->id] = [
                            'gradeitemid' => $gradeitem->id,
                            'itemname' => $gradeitem->itemname,
                            'itemtype' => $gradeitem->itemtype,
                            'finalgrade' => $this->format_grade($gradegrade->finalgrade, $gradeitem),
                            'rawgrade' => $gradegrade->finalgrade,
                        ];
                    }
                }

                if (!empty($usergrades)) {
                    $grades[$user->id] = [
                        'user' => $user,
                        'q10_student_id' => $this->get_q10_student_id($user),
                        'grades' => $usergrades,
                    ];
                }
            }
        }

        return $grades;
    }

    /**
     * Format grade according to configured scale.
     *
     * @param float $grade Raw grade value.
     * @param grade_item $gradeitem Grade item.
     * @return mixed Formatted grade.
     */
    private function format_grade(float $grade, grade_item $gradeitem) {
        switch ($this->gradescale) {
            case 'percentage':
                if ($gradeitem->grademax > 0) {
                    return round(($grade / $gradeitem->grademax) * 100, 2);
                }
                return 0;

            case 'letter':
                return grade_format_gradevalue($grade, $gradeitem, true, GRADE_DISPLAY_TYPE_LETTER);

            case 'raw':
            default:
                return round($grade, 2);
        }
    }

    /**
     * Sync final grades to Q10.
     *
     * @param string $synctype Sync type (manual, scheduled, realtime).
     * @param int|null $syncedby User ID who initiated sync.
     * @return array Sync results.
     */
    public function sync_final_grades(string $synctype = 'manual', ?int $syncedby = null): array {
        global $USER;

        if (!$this->has_mapping()) {
            throw new moodle_exception('nomapping', 'local_q10grades');
        }

        if (!$this->apiclient->is_configured()) {
            throw new moodle_exception('apinotconfigured', 'local_q10grades');
        }

        $syncedby = $syncedby ?? $USER->id;
        $grades = $this->get_course_grades(true);

        $results = [
            'total' => count($grades),
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $gradestosend = [];
        $userstograde = [];

        foreach ($grades as $userid => $gradedata) {
            if (empty($gradedata['q10_student_id'])) {
                $results['skipped']++;
                $this->log_sync(
                    $userid,
                    null,
                    $gradedata['rawgrade'],
                    'error',
                    get_string('nostudentmapping', 'local_q10grades'),
                    $synctype,
                    $syncedby
                );
                continue;
            }

            $gradestosend[] = [
                'student_id' => $gradedata['q10_student_id'],
                'grade' => $gradedata['finalgrade'],
            ];
            $userstograde[$gradedata['q10_student_id']] = [
                'userid' => $userid,
                'rawgrade' => $gradedata['rawgrade'],
            ];
        }

        if (empty($gradestosend)) {
            return $results;
        }

        try {
            $response = $this->apiclient->upload_final_grades(
                $this->mapping->q10_subject_id,
                $gradestosend,
                $this->mapping->q10_period_id
            );

            // Process response.
            if (isset($response['results'])) {
                foreach ($response['results'] as $result) {
                    $studentid = $result['student_id'];
                    if (isset($userstograde[$studentid])) {
                        $userinfo = $userstograde[$studentid];

                        if ($result['success'] ?? false) {
                            $results['success']++;
                            $this->log_sync(
                                $userinfo['userid'],
                                null,
                                $userinfo['rawgrade'],
                                'success',
                                null,
                                $synctype,
                                $syncedby,
                                json_encode($result)
                            );
                        } else {
                            $results['failed']++;
                            $error = $result['error'] ?? 'Unknown error';
                            $results['errors'][] = $error;
                            $this->log_sync(
                                $userinfo['userid'],
                                null,
                                $userinfo['rawgrade'],
                                'error',
                                $error,
                                $synctype,
                                $syncedby,
                                json_encode($result)
                            );
                        }
                    }
                }
            } else {
                // Bulk success assumed.
                foreach ($userstograde as $studentid => $userinfo) {
                    $results['success']++;
                    $this->log_sync(
                        $userinfo['userid'],
                        null,
                        $userinfo['rawgrade'],
                        'success',
                        null,
                        $synctype,
                        $syncedby,
                        json_encode($response)
                    );
                }
            }
        } catch (\Exception $e) {
            foreach ($userstograde as $studentid => $userinfo) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
                $this->log_sync(
                    $userinfo['userid'],
                    null,
                    $userinfo['rawgrade'],
                    'error',
                    $e->getMessage(),
                    $synctype,
                    $syncedby
                );
            }
        }

        return $results;
    }

    /**
     * Log sync attempt.
     *
     * @param int|null $userid User ID.
     * @param int|null $gradeitemid Grade item ID.
     * @param float|null $gradesent Grade value sent.
     * @param string $status Status (pending, success, error).
     * @param string|null $errormessage Error message.
     * @param string $synctype Sync type.
     * @param int|null $syncedby User who initiated sync.
     * @param string|null $q10response Q10 API response.
     */
    private function log_sync(
        ?int $userid,
        ?int $gradeitemid,
        ?float $gradesent,
        string $status,
        ?string $errormessage,
        string $synctype,
        ?int $syncedby,
        ?string $q10response = null
    ): void {
        global $DB;

        $record = new stdClass();
        $record->courseid = $this->courseid;
        $record->userid = $userid;
        $record->gradeitemid = $gradeitemid;
        $record->grade_sent = $gradesent;
        $record->q10_response = $q10response;
        $record->status = $status;
        $record->error_message = $errormessage;
        $record->sync_type = $synctype;
        $record->synced_by = $syncedby;
        $record->timecreated = time();

        $DB->insert_record('local_q10grades_sync_log', $record);
    }

    /**
     * Public wrapper for logging sync attempts.
     *
     * @param int|null $userid User ID.
     * @param int|null $gradeitemid Grade item ID.
     * @param float|null $gradesent Grade value sent.
     * @param string $status Status (pending, success, error).
     * @param string|null $errormessage Error message.
     * @param string $synctype Sync type.
     * @param int|null $syncedby User who initiated sync.
     * @param string|null $q10response Q10 API response.
     */
    public function log_sync_public(
        ?int $userid,
        ?int $gradeitemid,
        ?float $gradesent,
        string $status,
        ?string $errormessage,
        string $synctype,
        ?int $syncedby,
        ?string $q10response = null
    ): void {
        $this->log_sync($userid, $gradeitemid, $gradesent, $status, $errormessage, $synctype, $syncedby, $q10response);
    }

    /**
     * Get sync history for the course.
     *
     * @param int $limit Maximum number of records.
     * @param int $offset Offset for pagination.
     * @return array Sync log records.
     */
    public function get_sync_history(int $limit = 50, int $offset = 0): array {
        global $DB;

        $sql = "SELECT sl.*, u.firstname, u.lastname, u.email,
                       sb.firstname as synced_by_firstname, sb.lastname as synced_by_lastname
                FROM {local_q10grades_sync_log} sl
                LEFT JOIN {user} u ON u.id = sl.userid
                LEFT JOIN {user} sb ON sb.id = sl.synced_by
                WHERE sl.courseid = :courseid
                ORDER BY sl.timecreated DESC";

        return $DB->get_records_sql($sql, ['courseid' => $this->courseid], $offset, $limit);
    }

    /**
     * Get sync statistics for the course.
     *
     * @return array Statistics array.
     */
    public function get_sync_stats(): array {
        global $DB;

        $stats = [
            'total_syncs' => 0,
            'successful' => 0,
            'failed' => 0,
            'last_sync' => null,
        ];

        $sql = "SELECT status, COUNT(*) as cnt
                FROM {local_q10grades_sync_log}
                WHERE courseid = :courseid
                GROUP BY status";

        $counts = $DB->get_records_sql($sql, ['courseid' => $this->courseid]);

        foreach ($counts as $count) {
            $stats['total_syncs'] += $count->cnt;
            if ($count->status === 'success') {
                $stats['successful'] = $count->cnt;
            } elseif ($count->status === 'error') {
                $stats['failed'] = $count->cnt;
            }
        }

        $lastsync = $DB->get_field_sql(
            "SELECT MAX(timecreated) FROM {local_q10grades_sync_log} WHERE courseid = :courseid AND status = 'success'",
            ['courseid' => $this->courseid]
        );

        if ($lastsync) {
            $stats['last_sync'] = userdate($lastsync);
        }

        return $stats;
    }
}
