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
 * Shortcut handler for educambot - processes shortcut commands.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

// Required for completion_info class.
global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Shortcut handler class - processes shortcut commands for quick access to Moodle features.
 */
class shortcut_handler {

    /** @var context_handler Context handler */
    private $context;

    /**
     * Constructor.
     *
     * @param context_handler|null $context Context handler instance
     */
    public function __construct(context_handler $context = null) {
        $this->context = $context ?? new context_handler();
    }

    /**
     * Process question to check if it's a shortcut command.
     *
     * @param string $question User's question
     * @param string|null $userrole User's role archetype (optional)
     * @return array|null Response array or null if not a shortcut
     */
    public function process_shortcut($question, $userrole = null) {
        global $DB;

        // Clean question for matching.
        $question = trim(strtolower($question));

        // Get user's role archetype if not provided.
        if ($userrole === null) {
            $userrole = $this->context->get_user_archetype();
        }

        // Check if user is site admin.
        $issiteadmin = is_siteadmin();

        // Determine if we're in a course context.
        $courseid = $this->context->get_course_id();
        $incourse = ($courseid > 1);

        // Get all enabled shortcuts.
        $shortcuts = $DB->get_records('local_educambot_shortcut', ['enabled' => 1], 'sortorder ASC');

        foreach ($shortcuts as $shortcut) {
            // Filter by role (v3.0.1).
            if (!$issiteadmin && !empty($shortcut->roles)) {
                $allowedroles = array_map('trim', explode(',', $shortcut->roles));
                if (!in_array($userrole, $allowedroles)) {
                    continue;
                }
            }

            // Filter by context (v3.0.1).
            $shortcutcontext = $shortcut->context ?? 'any';
            if (!$incourse && $shortcutcontext === 'course') {
                continue;
            }

            $keywords = explode("\n", strtolower($shortcut->keywords));

            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (empty($keyword)) {
                    continue;
                }

                // Check for match (flexible matching).
                if ($this->matches_keyword($question, $keyword)) {
                    return $this->execute_shortcut($shortcut);
                }
            }
        }

        return null; // Not a shortcut.
    }

    /**
     * Check if question matches keyword.
     *
     * @param string $question User's question
     * @param string $keyword Keyword to match
     * @return bool True if matches
     */
    private function matches_keyword($question, $keyword) {
        // Exact match or contains keyword.
        if ($question === $keyword || strpos($question, $keyword) !== false) {
            return true;
        }

        // Check with simple normalization (remove accents).
        $questionNorm = $this->normalize_text($question);
        $keywordNorm = $this->normalize_text($keyword);

        return $questionNorm === $keywordNorm || strpos($questionNorm, $keywordNorm) !== false;
    }

    /**
     * Normalize text for matching (remove accents).
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
    private function normalize_text($text) {
        $unwanted = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'ñ' => 'n', 'Ñ' => 'N', 'ü' => 'u', 'Ü' => 'U',
        ];
        return strtr($text, $unwanted);
    }

    /**
     * Execute a shortcut action.
     *
     * @param object $shortcut Shortcut record
     * @return array Response array
     */
    private function execute_shortcut($shortcut) {
        switch ($shortcut->actiontype) {
            case 'assignments':
                $response = $this->get_assignments_response();
                break;
            case 'grades':
                $response = $this->get_grades_response();
                break;
            case 'calendar':
                $response = $this->get_calendar_response();
                break;
            case 'messages':
                $response = $this->get_messages_response();
                break;
            case 'teachers':
                $response = $this->get_teachers_response();
                break;
            case 'course':
                $response = $this->get_course_response();
                break;
            case 'progress':
                $response = $this->get_progress_response();
                break;
            case 'courses':
                $response = $this->get_courses_response();
                break;
            case 'participants':
                $response = $this->get_participants_response();
                break;
            case 'badges':
                $response = $this->get_badges_response();
                break;
            case 'teacher_grades':
                $response = $this->get_teacher_grades_response();
                break;
            case 'admin_users':
                $response = $this->get_admin_users_response();
                break;
            case 'admin_courses':
                $response = $this->get_admin_courses_response();
                break;
            case 'admin_reports':
                $response = $this->get_admin_reports_response();
                break;
            case 'admin_settings':
                $response = $this->get_admin_settings_response();
                break;
            case 'admin_plugins':
                $response = $this->get_admin_plugins_response();
                break;
            case 'admin_security':
                $response = $this->get_admin_security_response();
                break;
            case 'admin_backup':
                $response = $this->get_admin_backup_response();
                break;
            default:
                $response = get_string('unknownshortcut', 'local_educambot');
        }

        return [
            'success' => true,
            'response' => $response,
            'ruleid' => null,
            'confidence' => 1.0,
            'options' => $this->get_shortcut_options($shortcut->actiontype),
            'type' => 'shortcut',
            'shortcutid' => $shortcut->id,
        ];
    }

    /**
     * Get assignments response.
     *
     * @return string HTML response
     */
    private function get_assignments_response() {
        global $CFG;

        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $assignments = $this->context->get_user_assignments();

        if (empty($assignments)) {
            return get_string('shortcut_noassignments', 'local_educambot');
        }

        $response = get_string('shortcut_assignmentsheader', 'local_educambot') . '<br><br>';

        foreach ($assignments as $assignment) {
            $duetext = '';
            if ($assignment->duedate) {
                $duedate = userdate($assignment->duedate, '%d/%m/%Y %H:%M');
                $remaining = $assignment->duedate - time();

                if ($remaining < 0) {
                    $duetext = '<span style="color:#dc3545;">' . get_string('overdue', 'local_educambot') . '</span>';
                } else if ($remaining < 86400) {
                    $hours = floor($remaining / 3600);
                    $duetext = '<span style="color:#ffc107;">' . get_string('duein', 'local_educambot', $hours . 'h') . '</span>';
                } else {
                    $days = floor($remaining / 86400);
                    $duetext = get_string('duein', 'local_educambot', $days . 'd');
                }
            } else {
                $duetext = get_string('noduedate', 'local_educambot');
            }

            $response .= "- <a href='{$assignment->url}' target='_blank' rel='noopener'>{$assignment->name}</a> - {$duetext}<br>";
        }

        return $response;
    }

    /**
     * Get grades response.
     *
     * @return string HTML response
     */
    private function get_grades_response() {
        global $CFG;

        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $grades = $this->context->get_user_grades();
        $course = $this->context->get_course_info();

        if (!$grades) {
            return get_string('shortcut_nogrades', 'local_educambot');
        }

        $response = get_string('shortcut_gradesheader', 'local_educambot', $course['fullname']) . '<br><br>';

        // Overall grade.
        if ($grades['percentage'] !== null) {
            $response .= '<strong>' . get_string('overallgrade', 'local_educambot') . ':</strong> ';
            $response .= $grades['percentage'] . '% (' . $grades['letter'] . ')<br><br>';
        } else {
            $response .= get_string('notgradedyet', 'local_educambot') . '<br><br>';
        }

        // Recent graded items.
        if (!empty($grades['items'])) {
            $gradeditems = array_filter($grades['items'], function($item) {
                return $item->finalgrade !== null;
            });

            if (!empty($gradeditems)) {
                $response .= '<strong>' . get_string('recentgrades', 'local_educambot') . ':</strong><br>';
                foreach (array_slice($gradeditems, 0, 5) as $item) {
                    $percentage = $item->grademax > 0 ? round(($item->finalgrade / $item->grademax) * 100, 1) : 0;
                    $response .= "- {$item->name}: {$percentage}%<br>";
                }
            }
        }

        // Link to full grades (opens in new tab).
        $gradesurl = new \moodle_url('/grade/report/user/index.php', ['id' => $course['id']]);
        $response .= '<br>📊 <a href="' . $gradesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallgrades', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get calendar response.
     *
     * @return string HTML response
     */
    private function get_calendar_response() {
        global $CFG;

        $events = $this->context->get_upcoming_events(7);

        if (empty($events)) {
            return get_string('shortcut_noevents', 'local_educambot');
        }

        $response = get_string('shortcut_eventsheader', 'local_educambot') . '<br><br>';

        foreach ($events as $event) {
            $date = userdate($event->timestart, '%a %d/%m %H:%M');
            $icon = $this->get_event_icon($event->eventtype);
            $response .= "{$icon} <strong>{$event->name}</strong> - {$date}<br>";
        }

        // Link to calendar (opens in new tab).
        $calurl = new \moodle_url('/calendar/view.php');
        $response .= '<br>📅 <a href="' . $calurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewcalendar', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get event icon based on type.
     *
     * @param string $eventtype Event type
     * @return string Emoji icon
     */
    private function get_event_icon($eventtype) {
        $icons = [
            'due' => '📝',
            'course' => '📚',
            'user' => '👤',
            'site' => '🏫',
            'group' => '👥',
        ];
        return $icons[$eventtype] ?? '📅';
    }

    /**
     * Get messages response.
     *
     * @return string HTML response
     */
    private function get_messages_response() {
        global $CFG;

        $unreadcount = $this->context->get_unread_message_count();
        $messages = $this->context->get_recent_messages(5);

        $response = get_string('shortcut_messagesheader', 'local_educambot') . '<br><br>';

        if ($unreadcount > 0) {
            $response .= '<strong>' . get_string('unreadmessages', 'local_educambot', $unreadcount) . '</strong><br><br>';
        } else {
            $response .= get_string('nounreadmessages', 'local_educambot') . '<br><br>';
        }

        if (!empty($messages)) {
            $response .= '<strong>' . get_string('recentmessages', 'local_educambot') . ':</strong><br>';
            foreach ($messages as $message) {
                $time = userdate($message->timecreated, '%d/%m %H:%M');
                $response .= "- <strong>{$message->from}</strong>: {$message->preview} ({$time})<br>";
            }
        }

        // Link to messages (opens in new tab).
        $msgurl = new \moodle_url('/message/index.php');
        $response .= '<br>💬 <a href="' . $msgurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallmessages', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get teachers response.
     *
     * @return string HTML response
     */
    private function get_teachers_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $teachers = $this->context->get_course_teachers();
        $course = $this->context->get_course_info();

        if (empty($teachers)) {
            return get_string('shortcut_noteachers', 'local_educambot');
        }

        $response = get_string('shortcut_teachersheader', 'local_educambot', $course['fullname']) . '<br><br>';

        foreach ($teachers as $teacher) {
            $msgurl = new \moodle_url('/message/index.php', ['id' => $teacher->id]);
            $response .= "- <strong>{$teacher->fullname}</strong> ";
            $response .= "(<a href='{$msgurl}' target='_blank' rel='noopener'>" . get_string('sendmessage', 'local_educambot') . "</a>)<br>";
        }

        return $response;
    }

    /**
     * Get course info response.
     *
     * @return string HTML response
     */
    private function get_course_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $course = $this->context->get_course_info();
        $teachers = $this->context->get_course_teachers();
        $pending = $this->context->get_pending_tasks_count();

        $response = '<strong>' . $course['fullname'] . '</strong> (' . $course['shortname'] . ')<br><br>';

        // Dates.
        if ($course['startdate']) {
            $response .= get_string('startdate') . ': ' . userdate($course['startdate'], '%d/%m/%Y') . '<br>';
        }
        if ($course['enddate']) {
            $response .= get_string('enddate') . ': ' . userdate($course['enddate'], '%d/%m/%Y') . '<br>';
        }

        // Teacher.
        if (!empty($teachers)) {
            $response .= get_string('teacher', 'local_educambot') . ': ' . reset($teachers)->fullname . '<br>';
        }

        // Pending tasks.
        $response .= '<br>' . get_string('pendingtasks', 'local_educambot') . ': ' . $pending . '<br>';

        return $response;
    }

    /**
     * Get progress response.
     *
     * @return string HTML response
     */
    private function get_progress_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $course = $this->context->get_course_info();
        $grades = $this->context->get_user_grades();
        $pending = $this->context->get_pending_tasks_count();

        $response = get_string('shortcut_progressheader', 'local_educambot', $course['fullname']) . '<br><br>';

        // Grade.
        if ($grades && $grades['percentage'] !== null) {
            $response .= get_string('currentgrade', 'local_educambot') . ': ' . $grades['percentage'] . '%<br>';
        }

        // Pending.
        $response .= get_string('pendingtasks', 'local_educambot') . ': ' . $pending . '<br>';

        // Completion if available.
        $completion = $this->get_course_completion();
        if ($completion !== null) {
            $response .= get_string('completion', 'local_educambot') . ': ' . $completion . '%<br>';
        }

        return $response;
    }

    /**
     * Get course completion percentage.
     *
     * @return int|null Completion percentage or null
     */
    private function get_course_completion() {
        global $DB;

        $courseid = $this->context->get_course_id();
        $userid = $this->context->get_user_id();

        // Check if completion is enabled.
        $completion = new \completion_info($DB->get_record('course', ['id' => $courseid]));
        if (!$completion->is_enabled()) {
            return null;
        }

        // Get completion data.
        $activities = $completion->get_activities();
        if (empty($activities)) {
            return null;
        }

        $completed = 0;
        foreach ($activities as $activity) {
            $data = $completion->get_data($activity, false, $userid);
            if ($data->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        return round(($completed / count($activities)) * 100);
    }

    /**
     * Get user's enrolled courses response.
     *
     * @return string HTML response
     */
    private function get_courses_response() {
        global $CFG;

        $userid = $this->context->get_user_id();
        $courses = enrol_get_users_courses($userid, true, 'id, fullname, shortname, visible');

        if (empty($courses)) {
            return get_string('shortcut_nocourses', 'local_educambot');
        }

        $response = get_string('shortcut_coursesheader', 'local_educambot') . '<br><br>';

        $count = 0;
        foreach ($courses as $course) {
            if ($count >= 10) {
                $remaining = count($courses) - 10;
                $response .= '<br>' . get_string('andmore', 'local_educambot', $remaining);
                break;
            }

            $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $response .= "- <a href='{$courseurl}' target='_blank' rel='noopener'>{$course->fullname}</a><br>";
            $count++;
        }

        // Link to all courses.
        $mycoursesurl = new \moodle_url('/my/courses.php');
        $response .= '<br>📚 <a href="' . $mycoursesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallcourses', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get course participants response.
     *
     * @return string HTML response
     */
    private function get_participants_response() {
        global $DB;

        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $course = $this->context->get_course_info();
        $courseid = $this->context->get_course_id();
        $context = \context_course::instance($courseid);

        // Get enrolled users.
        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname', 'u.lastname, u.firstname', 0, 50);

        if (empty($users)) {
            return get_string('shortcut_noparticipants', 'local_educambot');
        }

        $response = get_string('shortcut_participantsheader', 'local_educambot', $course['fullname']) . '<br><br>';

        // Count total participants.
        $totalcount = count_enrolled_users($context);
        $response .= '<strong>' . get_string('totalparticipants', 'local_educambot', $totalcount) . '</strong><br><br>';

        // Show first 10 participants.
        $count = 0;
        foreach ($users as $user) {
            if ($count >= 10) {
                break;
            }
            $profileurl = new \moodle_url('/user/view.php', ['id' => $user->id, 'course' => $courseid]);
            $fullname = fullname($user);
            $response .= "- <a href='{$profileurl}' target='_blank' rel='noopener'>{$fullname}</a><br>";
            $count++;
        }

        if ($totalcount > 10) {
            $remaining = $totalcount - 10;
            $response .= '<br>' . get_string('andmore', 'local_educambot', $remaining);
        }

        // Link to participants page.
        $participantsurl = new \moodle_url('/user/index.php', ['id' => $courseid]);
        $response .= '<br>👥 <a href="' . $participantsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallparticipants', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get user's badges response.
     *
     * @return string HTML response
     */
    private function get_badges_response() {
        global $DB, $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $userid = $this->context->get_user_id();

        // Get user badges.
        $badges = badges_get_user_badges($userid);

        if (empty($badges)) {
            return get_string('shortcut_nobadges', 'local_educambot');
        }

        $response = get_string('shortcut_badgesheader', 'local_educambot') . '<br><br>';

        $count = 0;
        foreach ($badges as $badge) {
            if ($count >= 10) {
                $remaining = count($badges) - 10;
                $response .= '<br>' . get_string('andmore', 'local_educambot', $remaining);
                break;
            }

            $badgeurl = new \moodle_url('/badges/badge.php', ['hash' => $badge->uniquehash]);
            $dateissued = userdate($badge->dateissued, '%d/%m/%Y');
            $response .= "- 🏅 <a href='{$badgeurl}' target='_blank' rel='noopener'><strong>{$badge->name}</strong></a>";
            $response .= " - " . get_string('issuedon', 'local_educambot', $dateissued) . "<br>";
            $count++;
        }

        // Link to all badges.
        $badgesurl = new \moodle_url('/badges/mybadges.php');
        $response .= '<br>🏆 <a href="' . $badgesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallbadges', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get teacher grades management response.
     *
     * @return string HTML response
     */
    private function get_teacher_grades_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_educambot');
        }

        $course = $this->context->get_course_info();
        $courseid = $this->context->get_course_id();

        $response = get_string('shortcut_teachergradesheader', 'local_educambot', $course['fullname']) . '<br><br>';

        // Link to grader report.
        $graderurl = new \moodle_url('/grade/report/grader/index.php', ['id' => $courseid]);
        $response .= '📊 <a href="' . $graderurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewgraderreport', 'local_educambot') . ' ↗</a><br>';

        // Link to grade setup.
        $setupurl = new \moodle_url('/grade/edit/tree/index.php', ['id' => $courseid]);
        $response .= '⚙️ <a href="' . $setupurl . '" target="_blank" rel="noopener">';
        $response .= get_string('gradebooksetup', 'local_educambot') . ' ↗</a><br>';

        // Link to import grades.
        $importurl = new \moodle_url('/grade/import/index.php', ['id' => $courseid]);
        $response .= '📥 <a href="' . $importurl . '" target="_blank" rel="noopener">';
        $response .= get_string('importgrades', 'local_educambot') . ' ↗</a><br>';

        // Link to export grades.
        $exporturl = new \moodle_url('/grade/export/index.php', ['id' => $courseid]);
        $response .= '📤 <a href="' . $exporturl . '" target="_blank" rel="noopener">';
        $response .= get_string('exportgrades', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin users management response.
     *
     * @return string HTML response
     */
    private function get_admin_users_response() {
        global $DB;

        $response = get_string('shortcut_adminusersheader', 'local_educambot') . '<br><br>';

        // Get user count.
        $usercount = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
        $response .= '<strong>' . get_string('totalusers', 'local_educambot', $usercount) . '</strong><br><br>';

        // Link to browse users.
        $browseurl = new \moodle_url('/admin/user.php');
        $response .= '👥 <a href="' . $browseurl . '" target="_blank" rel="noopener">';
        $response .= get_string('browseusers', 'local_educambot') . ' ↗</a><br>';

        // Link to add user.
        $addurl = new \moodle_url('/user/editadvanced.php', ['id' => -1]);
        $response .= '➕ <a href="' . $addurl . '" target="_blank" rel="noopener">';
        $response .= get_string('addnewuser', 'local_educambot') . ' ↗</a><br>';

        // Link to upload users.
        $uploadurl = new \moodle_url('/admin/tool/uploaduser/index.php');
        $response .= '📤 <a href="' . $uploadurl . '" target="_blank" rel="noopener">';
        $response .= get_string('uploadusers', 'local_educambot') . ' ↗</a><br>';

        // Link to cohorts.
        $cohortsurl = new \moodle_url('/cohort/index.php');
        $response .= '👨‍👩‍👧‍👦 <a href="' . $cohortsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('managecohorts', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin courses management response.
     *
     * @return string HTML response
     */
    private function get_admin_courses_response() {
        global $DB;

        $response = get_string('shortcut_admincoursesheader', 'local_educambot') . '<br><br>';

        // Get course count.
        $coursecount = $DB->count_records('course') - 1; // Exclude site course.
        $response .= '<strong>' . get_string('totalcourses', 'local_educambot', $coursecount) . '</strong><br><br>';

        // Link to manage courses.
        $manageurl = new \moodle_url('/course/management.php');
        $response .= '📚 <a href="' . $manageurl . '" target="_blank" rel="noopener">';
        $response .= get_string('managecourses', 'local_educambot') . ' ↗</a><br>';

        // Link to add course.
        $addurl = new \moodle_url('/course/edit.php', ['category' => 1]);
        $response .= '➕ <a href="' . $addurl . '" target="_blank" rel="noopener">';
        $response .= get_string('addnewcourse', 'local_educambot') . ' ↗</a><br>';

        // Link to manage categories.
        $categoriesurl = new \moodle_url('/course/management.php');
        $response .= '📁 <a href="' . $categoriesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('managecategories', 'local_educambot') . ' ↗</a><br>';

        // Link to restore course.
        $restoreurl = new \moodle_url('/backup/restorefile.php', ['contextid' => 1]);
        $response .= '📥 <a href="' . $restoreurl . '" target="_blank" rel="noopener">';
        $response .= get_string('restorecourse', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin reports response.
     *
     * @return string HTML response
     */
    private function get_admin_reports_response() {
        $response = get_string('shortcut_adminreportsheader', 'local_educambot') . '<br><br>';

        // Link to logs.
        $logsurl = new \moodle_url('/report/log/index.php');
        $response .= '📋 <a href="' . $logsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewlogs', 'local_educambot') . ' ↗</a><br>';

        // Link to live logs.
        $livelogsurl = new \moodle_url('/report/loglive/index.php');
        $response .= '🔴 <a href="' . $livelogsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewlivelogs', 'local_educambot') . ' ↗</a><br>';

        // Link to activity report.
        $activityurl = new \moodle_url('/report/outline/index.php');
        $response .= '📊 <a href="' . $activityurl . '" target="_blank" rel="noopener">';
        $response .= get_string('activityreport', 'local_educambot') . ' ↗</a><br>';

        // Link to statistics.
        $statsurl = new \moodle_url('/report/stats/index.php');
        $response .= '📈 <a href="' . $statsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewstatistics', 'local_educambot') . ' ↗</a><br>';

        // Link to config changes.
        $configurl = new \moodle_url('/report/configlog/index.php');
        $response .= '⚙️ <a href="' . $configurl . '" target="_blank" rel="noopener">';
        $response .= get_string('configchanges', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin settings response.
     *
     * @return string HTML response
     */
    private function get_admin_settings_response() {
        $response = get_string('shortcut_adminsettingsheader', 'local_educambot') . '<br><br>';

        // Link to site administration.
        $adminurl = new \moodle_url('/admin/search.php');
        $response .= '🏠 <a href="' . $adminurl . '" target="_blank" rel="noopener">';
        $response .= get_string('siteadministration', 'local_educambot') . ' ↗</a><br>';

        // Link to front page settings.
        $frontpageurl = new \moodle_url('/admin/settings.php', ['section' => 'frontpagesettings']);
        $response .= '🌐 <a href="' . $frontpageurl . '" target="_blank" rel="noopener">';
        $response .= get_string('frontpagesettings', 'local_educambot') . ' ↗</a><br>';

        // Link to appearance settings.
        $appearanceurl = new \moodle_url('/admin/settings.php', ['section' => 'themesettings']);
        $response .= '🎨 <a href="' . $appearanceurl . '" target="_blank" rel="noopener">';
        $response .= get_string('appearancesettings', 'local_educambot') . ' ↗</a><br>';

        // Link to language settings.
        $languageurl = new \moodle_url('/admin/settings.php', ['section' => 'langsettings']);
        $response .= '🌍 <a href="' . $languageurl . '" target="_blank" rel="noopener">';
        $response .= get_string('languagesettings', 'local_educambot') . ' ↗</a><br>';

        // Link to notifications.
        $notificationsurl = new \moodle_url('/admin/settings.php', ['section' => 'messagesettings']);
        $response .= '🔔 <a href="' . $notificationsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('notificationsettings', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin plugins management response.
     *
     * @return string HTML response
     */
    private function get_admin_plugins_response() {
        $response = get_string('shortcut_adminpluginsheader', 'local_educambot') . '<br><br>';

        // Link to plugins overview.
        $pluginsurl = new \moodle_url('/admin/plugins.php');
        $response .= '🧩 <a href="' . $pluginsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('pluginsoverview', 'local_educambot') . ' ↗</a><br>';

        // Link to install plugins.
        $installurl = new \moodle_url('/admin/tool/installaddon/index.php');
        $response .= '➕ <a href="' . $installurl . '" target="_blank" rel="noopener">';
        $response .= get_string('installplugins', 'local_educambot') . ' ↗</a><br>';

        // Link to activity modules.
        $activitiesurl = new \moodle_url('/admin/modules.php');
        $response .= '📝 <a href="' . $activitiesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('manageactivities', 'local_educambot') . ' ↗</a><br>';

        // Link to authentication plugins.
        $authurl = new \moodle_url('/admin/settings.php', ['section' => 'manageauths']);
        $response .= '🔐 <a href="' . $authurl . '" target="_blank" rel="noopener">';
        $response .= get_string('manageauthentication', 'local_educambot') . ' ↗</a><br>';

        // Link to enrolment plugins.
        $enrolurl = new \moodle_url('/admin/settings.php', ['section' => 'manageenrols']);
        $response .= '📋 <a href="' . $enrolurl . '" target="_blank" rel="noopener">';
        $response .= get_string('manageenrolments', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin security response.
     *
     * @return string HTML response
     */
    private function get_admin_security_response() {
        $response = get_string('shortcut_adminsecurityheader', 'local_educambot') . '<br><br>';

        // Link to security overview.
        $overviewurl = new \moodle_url('/admin/settings.php', ['section' => 'sitepolicies']);
        $response .= '🛡️ <a href="' . $overviewurl . '" target="_blank" rel="noopener">';
        $response .= get_string('sitepolicies', 'local_educambot') . ' ↗</a><br>';

        // Link to HTTP security.
        $httpurl = new \moodle_url('/admin/settings.php', ['section' => 'httpsecurity']);
        $response .= '🔒 <a href="' . $httpurl . '" target="_blank" rel="noopener">';
        $response .= get_string('httpsecurity', 'local_educambot') . ' ↗</a><br>';

        // Link to IP blocker.
        $ipblockerurl = new \moodle_url('/admin/settings.php', ['section' => 'ipblocker']);
        $response .= '🚫 <a href="' . $ipblockerurl . '" target="_blank" rel="noopener">';
        $response .= get_string('ipblocker', 'local_educambot') . ' ↗</a><br>';

        // Link to notifications.
        $notifyurl = new \moodle_url('/admin/settings.php', ['section' => 'notifications']);
        $response .= '⚠️ <a href="' . $notifyurl . '" target="_blank" rel="noopener">';
        $response .= get_string('securitynotifications', 'local_educambot') . ' ↗</a><br>';

        // Link to security report.
        $reporturl = new \moodle_url('/report/security/index.php');
        $response .= '📋 <a href="' . $reporturl . '" target="_blank" rel="noopener">';
        $response .= get_string('securityreport', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin backup response.
     *
     * @return string HTML response
     */
    private function get_admin_backup_response() {
        $response = get_string('shortcut_adminbackupheader', 'local_educambot') . '<br><br>';

        // Link to backup settings.
        $settingsurl = new \moodle_url('/admin/settings.php', ['section' => 'backupgeneralsettings']);
        $response .= '⚙️ <a href="' . $settingsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('backupsettings', 'local_educambot') . ' ↗</a><br>';

        // Link to automated backups.
        $automatedurl = new \moodle_url('/admin/settings.php', ['section' => 'automated']);
        $response .= '🔄 <a href="' . $automatedurl . '" target="_blank" rel="noopener">';
        $response .= get_string('automatedbackups', 'local_educambot') . ' ↗</a><br>';

        // Link to restore.
        $restoreurl = new \moodle_url('/backup/restorefile.php', ['contextid' => 1]);
        $response .= '📥 <a href="' . $restoreurl . '" target="_blank" rel="noopener">';
        $response .= get_string('restoresite', 'local_educambot') . ' ↗</a><br>';

        // Link to import.
        $importurl = new \moodle_url('/backup/import.php');
        $response .= '📤 <a href="' . $importurl . '" target="_blank" rel="noopener">';
        $response .= get_string('importcourse', 'local_educambot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get quick options for shortcut type.
     *
     * @param string $actiontype Shortcut action type
     * @return array Quick options
     */
    private function get_shortcut_options($actiontype) {
        $options = [];

        switch ($actiontype) {
            case 'assignments':
                $options[] = ['text' => 'Ver Calificaciones', 'icon' => '📊', 'action' => 'ver mis calificaciones'];
                $options[] = ['text' => 'Calendario', 'icon' => '📅', 'action' => 'eventos'];
                break;
            case 'grades':
                $options[] = ['text' => 'Ver Tareas', 'icon' => '📝', 'action' => 'ver mis tareas'];
                $options[] = ['text' => 'Profesores', 'icon' => '👨‍🏫', 'action' => 'quienes son mis profesores'];
                break;
            case 'calendar':
                $options[] = ['text' => 'Ver Tareas', 'icon' => '📝', 'action' => 'ver mis tareas'];
                $options[] = ['text' => 'Mi Progreso', 'icon' => '📈', 'action' => 'mi progreso'];
                break;
            case 'messages':
                $options[] = ['text' => 'Profesores', 'icon' => '👨‍🏫', 'action' => 'quienes son mis profesores'];
                break;
            case 'courses':
                $options[] = ['text' => 'Mis Tareas', 'icon' => '📝', 'action' => 'mis tareas'];
                $options[] = ['text' => 'Mis Calificaciones', 'icon' => '📊', 'action' => 'mis calificaciones'];
                break;
            case 'participants':
                $options[] = ['text' => 'Profesores', 'icon' => '👨‍🏫', 'action' => 'quienes son mis profesores'];
                $options[] = ['text' => 'Info del Curso', 'icon' => '📖', 'action' => 'informacion del curso'];
                break;
            case 'badges':
                $options[] = ['text' => 'Mis Cursos', 'icon' => '📚', 'action' => 'mis cursos'];
                $options[] = ['text' => 'Mi Progreso', 'icon' => '📈', 'action' => 'mi progreso'];
                break;
            case 'teacher_grades':
                $options[] = ['text' => 'Participantes', 'icon' => '👥', 'action' => 'participantes'];
                $options[] = ['text' => 'Info del Curso', 'icon' => '📖', 'action' => 'informacion del curso'];
                break;
            case 'admin_users':
                $options[] = ['text' => 'Cursos', 'icon' => '📚', 'action' => 'gestionar cursos'];
                $options[] = ['text' => 'Reportes', 'icon' => '📊', 'action' => 'reportes sitio'];
                break;
            case 'admin_courses':
                $options[] = ['text' => 'Usuarios', 'icon' => '👥', 'action' => 'gestionar usuarios'];
                $options[] = ['text' => 'Backups', 'icon' => '💾', 'action' => 'backup'];
                break;
            case 'admin_reports':
                $options[] = ['text' => 'Usuarios', 'icon' => '👥', 'action' => 'gestionar usuarios'];
                $options[] = ['text' => 'Configuracion', 'icon' => '⚙️', 'action' => 'configuracion'];
                break;
            case 'admin_settings':
                $options[] = ['text' => 'Plugins', 'icon' => '🧩', 'action' => 'plugins'];
                $options[] = ['text' => 'Seguridad', 'icon' => '🛡️', 'action' => 'seguridad'];
                break;
            case 'admin_plugins':
                $options[] = ['text' => 'Configuracion', 'icon' => '⚙️', 'action' => 'configuracion'];
                $options[] = ['text' => 'Seguridad', 'icon' => '🛡️', 'action' => 'seguridad'];
                break;
            case 'admin_security':
                $options[] = ['text' => 'Configuracion', 'icon' => '⚙️', 'action' => 'configuracion'];
                $options[] = ['text' => 'Reportes', 'icon' => '📊', 'action' => 'reportes sitio'];
                break;
            case 'admin_backup':
                $options[] = ['text' => 'Cursos', 'icon' => '📚', 'action' => 'gestionar cursos'];
                $options[] = ['text' => 'Reportes', 'icon' => '📊', 'action' => 'reportes sitio'];
                break;
        }

        // Always add menu option.
        $options[] = ['text' => 'Menu Principal', 'icon' => '🏠', 'action' => 'menu'];

        return $options;
    }

    /**
     * Get all available shortcuts.
     *
     * @param bool $enabledonly Only enabled shortcuts
     * @return array List of shortcuts
     */
    public static function get_all_shortcuts($enabledonly = true) {
        global $DB;

        $params = $enabledonly ? ['enabled' => 1] : [];
        return $DB->get_records('local_educambot_shortcut', $params, 'sortorder ASC');
    }

    /**
     * Get available action types.
     *
     * @return array Action types with descriptions
     */
    public static function get_action_types() {
        return [
            'assignments' => get_string('actiontype_assignments', 'local_educambot'),
            'grades' => get_string('actiontype_grades', 'local_educambot'),
            'calendar' => get_string('actiontype_calendar', 'local_educambot'),
            'messages' => get_string('actiontype_messages', 'local_educambot'),
            'teachers' => get_string('actiontype_teachers', 'local_educambot'),
            'course' => get_string('actiontype_course', 'local_educambot'),
            'progress' => get_string('actiontype_progress', 'local_educambot'),
            'courses' => get_string('actiontype_courses', 'local_educambot'),
            'participants' => get_string('actiontype_participants', 'local_educambot'),
            'badges' => get_string('actiontype_badges', 'local_educambot'),
            'teacher_grades' => get_string('actiontype_teacher_grades', 'local_educambot'),
            'admin_users' => get_string('actiontype_admin_users', 'local_educambot'),
            'admin_courses' => get_string('actiontype_admin_courses', 'local_educambot'),
            'admin_reports' => get_string('actiontype_admin_reports', 'local_educambot'),
            'admin_settings' => get_string('actiontype_admin_settings', 'local_educambot'),
            'admin_plugins' => get_string('actiontype_admin_plugins', 'local_educambot'),
            'admin_security' => get_string('actiontype_admin_security', 'local_educambot'),
            'admin_backup' => get_string('actiontype_admin_backup', 'local_educambot'),
        ];
    }
}
