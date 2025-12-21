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
 * Context handler for educambot - detects and manages Moodle context.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Context handler class - detects user's current context and provides Moodle data.
 */
class context_handler {

    /** @var int Current course ID */
    private $courseid;

    /** @var int Current user ID */
    private $userid;

    /** @var string Current page type */
    private $pagetype;

    /** @var object Course object */
    private $course;

    /** @var object User object */
    private $user;

    /** @var \context Current context */
    private $context;

    /**
     * Constructor - initializes context from Moodle globals.
     *
     * @param int|null $courseid Optional course ID override
     * @param int|null $userid Optional user ID override
     */
    public function __construct($courseid = null, $userid = null) {
        global $PAGE, $USER, $COURSE, $DB;

        $this->courseid = $courseid ?? ($COURSE->id ?? SITEID);
        $this->userid = $userid ?? $USER->id;
        $this->pagetype = $PAGE->pagetype ?? 'site-index';

        // Load course if not site.
        if ($this->courseid != SITEID) {
            $this->course = $DB->get_record('course', ['id' => $this->courseid]);
        } else {
            $this->course = null;
        }

        // Load user.
        $this->user = $DB->get_record('user', ['id' => $this->userid]);

        // Get context.
        if ($this->course) {
            $this->context = \context_course::instance($this->courseid, IGNORE_MISSING);
        } else {
            $this->context = \context_system::instance();
        }
    }

    /**
     * Get context type (site, course, activity).
     *
     * @return string Context type
     */
    public function get_context_type() {
        if ($this->courseid == SITEID) {
            return 'site';
        }

        if (strpos($this->pagetype, 'mod-') === 0) {
            return 'activity';
        }

        return 'course';
    }

    /**
     * Check if user is in a course context.
     *
     * @return bool True if in course context
     */
    public function is_in_course() {
        return $this->courseid != SITEID && $this->course !== null;
    }

    /**
     * Get course ID.
     *
     * @return int Course ID
     */
    public function get_course_id() {
        return $this->courseid;
    }

    /**
     * Get user ID.
     *
     * @return int User ID
     */
    public function get_user_id() {
        return $this->userid;
    }

    /**
     * Get course information.
     *
     * @return array|null Course info or null if not in course
     */
    public function get_course_info() {
        if (!$this->course) {
            return null;
        }

        return [
            'id' => $this->course->id,
            'fullname' => $this->course->fullname,
            'shortname' => $this->course->shortname,
            'startdate' => $this->course->startdate,
            'enddate' => $this->course->enddate,
            'summary' => $this->course->summary,
        ];
    }

    /**
     * Get user information.
     *
     * @return array User info
     */
    public function get_user_info() {
        if (!$this->user) {
            return [
                'id' => $this->userid,
                'firstname' => '',
                'lastname' => '',
                'email' => '',
            ];
        }

        return [
            'id' => $this->user->id,
            'firstname' => $this->user->firstname,
            'lastname' => $this->user->lastname,
            'fullname' => fullname($this->user),
            'email' => $this->user->email,
        ];
    }

    /**
     * Get user's pending assignments in current course.
     *
     * @return array List of pending assignments
     */
    public function get_user_assignments() {
        global $DB, $CFG;

        if (!$this->is_in_course()) {
            return [];
        }

        $assignments = [];
        $now = time();

        // Get all assignments in the course.
        $sql = "SELECT a.id, a.name, a.duedate, a.allowsubmissionsfromdate, cm.id as cmid
                FROM {assign} a
                JOIN {course_modules} cm ON cm.instance = a.id
                JOIN {modules} m ON m.id = cm.module AND m.name = 'assign'
                WHERE a.course = :courseid
                AND cm.visible = 1
                AND (a.duedate = 0 OR a.duedate > :now)
                ORDER BY a.duedate ASC";

        $records = $DB->get_records_sql($sql, [
            'courseid' => $this->courseid,
            'now' => $now,
        ]);

        foreach ($records as $record) {
            // Check if user has submitted.
            $submitted = $DB->record_exists('assign_submission', [
                'assignment' => $record->id,
                'userid' => $this->userid,
                'status' => 'submitted',
            ]);

            if (!$submitted) {
                $assignments[] = (object)[
                    'id' => $record->id,
                    'name' => $record->name,
                    'duedate' => $record->duedate,
                    'cmid' => $record->cmid,
                    'url' => new \moodle_url('/mod/assign/view.php', ['id' => $record->cmid]),
                ];
            }
        }

        return $assignments;
    }

    /**
     * Get upcoming calendar events.
     *
     * @param int $days Number of days to look ahead
     * @return array List of upcoming events
     */
    public function get_upcoming_events($days = 7) {
        global $DB;

        $now = time();
        $until = $now + ($days * 86400);

        $events = [];

        // Get course events if in course context.
        if ($this->is_in_course()) {
            $sql = "SELECT id, name, description, timestart, timeduration, eventtype
                    FROM {event}
                    WHERE (courseid = :courseid OR courseid = 0)
                    AND timestart BETWEEN :now AND :until
                    AND userid IN (0, :userid)
                    ORDER BY timestart ASC
                    LIMIT 10";

            $records = $DB->get_records_sql($sql, [
                'courseid' => $this->courseid,
                'now' => $now,
                'until' => $until,
                'userid' => $this->userid,
            ]);
        } else {
            // Get all user events.
            $sql = "SELECT id, name, description, timestart, timeduration, eventtype, courseid
                    FROM {event}
                    WHERE (userid = :userid OR userid = 0)
                    AND timestart BETWEEN :now AND :until
                    ORDER BY timestart ASC
                    LIMIT 10";

            $records = $DB->get_records_sql($sql, [
                'now' => $now,
                'until' => $until,
                'userid' => $this->userid,
            ]);
        }

        foreach ($records as $record) {
            $events[] = (object)[
                'id' => $record->id,
                'name' => $record->name,
                'description' => $record->description,
                'timestart' => $record->timestart,
                'timeduration' => $record->timeduration,
                'eventtype' => $record->eventtype,
            ];
        }

        return $events;
    }

    /**
     * Get user's grades in current course.
     *
     * @return array|null Grade information or null if not available
     */
    public function get_user_grades() {
        global $DB, $CFG;

        if (!$this->is_in_course()) {
            return null;
        }

        require_once($CFG->libdir . '/gradelib.php');

        // Get the course grade item.
        $gradeitem = \grade_item::fetch_course_item($this->courseid);
        if (!$gradeitem) {
            return null;
        }

        // Get user's grade.
        $grade = $gradeitem->get_grade($this->userid, false);

        if (!$grade || is_null($grade->finalgrade)) {
            return [
                'finalgrade' => null,
                'percentage' => null,
                'letter' => '-',
                'items' => $this->get_graded_items(),
            ];
        }

        // Calculate percentage.
        $percentage = 0;
        if ($gradeitem->grademax > 0) {
            $percentage = round(($grade->finalgrade / $gradeitem->grademax) * 100, 1);
        }

        // Get letter grade.
        $letters = \grade_get_letters($this->context);
        $letter = '-';
        foreach ($letters as $boundary => $value) {
            if ($percentage >= $boundary) {
                $letter = $value;
                break;
            }
        }

        return [
            'finalgrade' => round($grade->finalgrade, 2),
            'percentage' => $percentage,
            'grademax' => $gradeitem->grademax,
            'letter' => $letter,
            'items' => $this->get_graded_items(),
        ];
    }

    /**
     * Get list of graded items in course.
     *
     * @return array List of graded items
     */
    private function get_graded_items() {
        global $DB;

        $items = [];

        $sql = "SELECT gi.id, gi.itemname, gi.itemtype, gi.itemmodule, gi.grademax,
                       gg.finalgrade, gg.timemodified
                FROM {grade_items} gi
                LEFT JOIN {grade_grades} gg ON gg.itemid = gi.id AND gg.userid = :userid
                WHERE gi.courseid = :courseid
                AND gi.itemtype != 'course'
                AND gi.hidden = 0
                ORDER BY gi.sortorder ASC
                LIMIT 20";

        $records = $DB->get_records_sql($sql, [
            'courseid' => $this->courseid,
            'userid' => $this->userid,
        ]);

        foreach ($records as $record) {
            $items[] = (object)[
                'id' => $record->id,
                'name' => $record->itemname ?: $record->itemmodule,
                'type' => $record->itemtype,
                'module' => $record->itemmodule,
                'grademax' => $record->grademax,
                'finalgrade' => $record->finalgrade,
                'timemodified' => $record->timemodified,
            ];
        }

        return $items;
    }

    /**
     * Get course teachers/instructors.
     *
     * @return array List of teachers
     */
    public function get_course_teachers() {
        global $DB;

        if (!$this->is_in_course() || !$this->context) {
            return [];
        }

        $teachers = [];

        // Get users with editing teacher or teacher role.
        $roles = $DB->get_records('role', ['archetype' => 'editingteacher']);
        $roleids = array_keys($roles);

        // Also include non-editing teachers.
        $neteacherroles = $DB->get_records('role', ['archetype' => 'teacher']);
        $roleids = array_merge($roleids, array_keys($neteacherroles));

        if (empty($roleids)) {
            return [];
        }

        list($insql, $params) = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED);
        $params['contextid'] = $this->context->id;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                FROM {user} u
                JOIN {role_assignments} ra ON ra.userid = u.id
                WHERE ra.contextid = :contextid
                AND ra.roleid $insql
                AND u.deleted = 0
                ORDER BY u.lastname, u.firstname";

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $teachers[] = (object)[
                'id' => $record->id,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'fullname' => fullname($record),
                'email' => $record->email,
            ];
        }

        return $teachers;
    }

    /**
     * Get user's recent messages.
     *
     * @param int $limit Maximum number of messages
     * @return array List of recent messages
     */
    public function get_recent_messages($limit = 5) {
        global $DB;

        $sql = "SELECT m.id, m.useridfrom, m.subject, m.smallmessage, m.timecreated,
                       u.firstname, u.lastname
                FROM {messages} m
                JOIN {message_conversations} mc ON mc.id = m.conversationid
                JOIN {message_conversation_members} mcm ON mcm.conversationid = mc.id
                JOIN {user} u ON u.id = m.useridfrom
                WHERE mcm.userid = :userid
                AND m.useridfrom != :userid2
                ORDER BY m.timecreated DESC
                LIMIT " . intval($limit);

        $records = $DB->get_records_sql($sql, [
            'userid' => $this->userid,
            'userid2' => $this->userid,
        ]);

        $messages = [];
        foreach ($records as $record) {
            $messages[] = (object)[
                'id' => $record->id,
                'from' => fullname($record),
                'subject' => $record->subject,
                'preview' => shorten_text($record->smallmessage, 50),
                'timecreated' => $record->timecreated,
            ];
        }

        return $messages;
    }

    /**
     * Get unread message count.
     *
     * @return int Number of unread messages
     */
    public function get_unread_message_count() {
        global $DB;

        return \core_message\api::count_unread_conversations($this->user);
    }

    /**
     * Get next upcoming assignment.
     *
     * @return object|null Next assignment or null
     */
    public function get_next_assignment() {
        $assignments = $this->get_user_assignments();
        return !empty($assignments) ? reset($assignments) : null;
    }

    /**
     * Get next upcoming event.
     *
     * @return object|null Next event or null
     */
    public function get_next_event() {
        $events = $this->get_upcoming_events(30);
        return !empty($events) ? reset($events) : null;
    }

    /**
     * Get count of pending tasks (assignments).
     *
     * @return int Number of pending assignments
     */
    public function get_pending_tasks_count() {
        return count($this->get_user_assignments());
    }

    /**
     * Get user's role archetype (student, teacher, manager, siteadmin, etc.).
     *
     * @return string Role archetype or 'student' as default
     */
    public function get_user_archetype() {
        global $DB;

        // Site administrator always gets highest priority (v3.4.0).
        if (is_siteadmin($this->userid)) {
            return 'siteadmin';
        }

        // Priority order for archetypes (highest first).
        $priority = [
            'manager' => 5,
            'coursecreator' => 4,
            'editingteacher' => 3,
            'teacher' => 2,
            'student' => 1,
            'guest' => 0,
            'user' => 0,
            'frontpage' => 0,
        ];

        $archetype = 'student'; // Default.
        $highestpriority = -1;

        // Get user's role assignments in current context or any parent context.
        if ($this->context) {
            $roles = get_user_roles($this->context, $this->userid, true);

            foreach ($roles as $role) {
                $rolerec = $DB->get_record('role', ['id' => $role->roleid]);
                if ($rolerec && !empty($rolerec->archetype)) {
                    $p = $priority[$rolerec->archetype] ?? 0;
                    if ($p > $highestpriority) {
                        $highestpriority = $p;
                        $archetype = $rolerec->archetype;
                    }
                }
            }
        }

        // If no role found, check system level roles.
        if ($highestpriority < 0) {
            $systemcontext = \context_system::instance();
            $roles = get_user_roles($systemcontext, $this->userid, true);

            foreach ($roles as $role) {
                $rolerec = $DB->get_record('role', ['id' => $role->roleid]);
                if ($rolerec && !empty($rolerec->archetype)) {
                    $p = $priority[$rolerec->archetype] ?? 0;
                    if ($p > $highestpriority) {
                        $highestpriority = $p;
                        $archetype = $rolerec->archetype;
                    }
                }
            }
        }

        return $archetype;
    }

    /**
     * Get all context data as array for placeholders.
     *
     * @return array All context data
     */
    public function get_all_data() {
        return [
            'context_type' => $this->get_context_type(),
            'is_in_course' => $this->is_in_course(),
            'course' => $this->get_course_info(),
            'user' => $this->get_user_info(),
            'teachers' => $this->get_course_teachers(),
            'assignments' => $this->get_user_assignments(),
            'grades' => $this->get_user_grades(),
            'events' => $this->get_upcoming_events(),
            'pending_count' => $this->get_pending_tasks_count(),
        ];
    }
}
