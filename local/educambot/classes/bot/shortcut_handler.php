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
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

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
     * @return array|null Response array or null if not a shortcut
     */
    public function process_shortcut($question) {
        global $DB;

        // Clean question for matching.
        $question = trim(strtolower($question));

        // Get all enabled shortcuts.
        $shortcuts = $DB->get_records('local_educambot_shortcut', ['enabled' => 1], 'sortorder ASC');

        foreach ($shortcuts as $shortcut) {
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
        ];
    }
}
