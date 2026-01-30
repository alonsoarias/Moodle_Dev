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
 * Quick access handler for udesbot - processes quick access commands (v3.0.3).
 *
 * This replaces the database-dependent shortcut_handler with hardcoded quick access
 * commands for better reliability and performance.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_udesbot\bot;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Quick access handler class - processes quick access commands without database dependency.
 */
class quick_access_handler {

    /** @var context_handler Context handler */
    private $context;

    /** @var array Quick access commands definition */
    private $commands;

    /**
     * Constructor.
     *
     * @param context_handler|null $context Context handler instance
     */
    public function __construct(context_handler $context = null) {
        $this->context = $context ?? new context_handler();
        $this->init_commands();
    }

    /**
     * Initialize quick access commands.
     */
    private function init_commands() {
        $this->commands = [
            // Student commands.
            'assignments' => [
                'keywords' => ['tareas', 'mis tareas', 'tareas pendientes', 'ver tareas', 'asignaciones', 'trabajos'],
                'method' => 'get_assignments_response',
                'icon' => 'bi-file-earmark-text',
            ],
            'grades' => [
                'keywords' => ['calificaciones', 'mis calificaciones', 'notas', 'mis notas', 'ver notas'],
                'method' => 'get_grades_response',
                'icon' => 'bi-trophy',
            ],
            'calendar' => [
                'keywords' => ['calendario', 'eventos', 'proximos eventos', 'fechas', 'agenda'],
                'method' => 'get_calendar_response',
                'icon' => 'bi-calendar-event',
            ],
            'messages' => [
                'keywords' => ['mensajes', 'mis mensajes', 'bandeja', 'correo', 'inbox'],
                'method' => 'get_messages_response',
                'icon' => 'bi-envelope',
            ],
            'teachers' => [
                'keywords' => ['profesores', 'mis profesores', 'docentes', 'instructor', 'quien es mi profesor', 'quienes son mis profesores'],
                'method' => 'get_teachers_response',
                'icon' => 'bi-person-badge',
            ],
            'course' => [
                'keywords' => ['curso', 'informacion del curso', 'info curso', 'sobre el curso', 'datos del curso'],
                'method' => 'get_course_response',
                'icon' => 'bi-book',
            ],
            'progress' => [
                'keywords' => ['progreso', 'mi progreso', 'avance', 'como voy'],
                'method' => 'get_progress_response',
                'icon' => 'bi-graph-up',
            ],
            'courses' => [
                'keywords' => ['mis cursos', 'cursos inscritos', 'mis materias', 'en que cursos estoy'],
                'method' => 'get_courses_response',
                'icon' => 'bi-collection',
            ],
            'participants' => [
                'keywords' => ['participantes', 'companeros', 'estudiantes', 'quienes estan en el curso'],
                'method' => 'get_participants_response',
                'icon' => 'bi-people',
            ],
            'badges' => [
                'keywords' => ['insignias', 'mis insignias', 'medallas', 'logros'],
                'method' => 'get_badges_response',
                'icon' => 'bi-award',
            ],

            // Admin commands.
            'admin_users' => [
                'keywords' => ['gestionar usuarios', 'administrar usuarios', 'usuarios del sitio', 'gestion usuarios'],
                'method' => 'get_admin_users_response',
                'icon' => 'bi-people-fill',
                'admin' => true,
            ],
            'admin_courses' => [
                'keywords' => ['gestionar cursos', 'administrar cursos', 'gestion cursos', 'cursos del sitio'],
                'method' => 'get_admin_courses_response',
                'icon' => 'bi-journal-bookmark-fill',
                'admin' => true,
            ],
            'admin_reports' => [
                'keywords' => ['reportes', 'reportes sitio', 'informes', 'estadisticas', 'logs'],
                'method' => 'get_admin_reports_response',
                'icon' => 'bi-bar-chart-fill',
                'admin' => true,
            ],
            'admin_settings' => [
                'keywords' => ['configuracion', 'ajustes', 'settings', 'configurar sitio'],
                'method' => 'get_admin_settings_response',
                'icon' => 'bi-gear-fill',
                'admin' => true,
            ],
            'admin_plugins' => [
                'keywords' => ['plugins', 'complementos', 'extensiones', 'modulos'],
                'method' => 'get_admin_plugins_response',
                'icon' => 'bi-puzzle-fill',
                'admin' => true,
            ],
            'admin_security' => [
                'keywords' => ['seguridad', 'security', 'proteccion'],
                'method' => 'get_admin_security_response',
                'icon' => 'bi-shield-fill',
                'admin' => true,
            ],
            'admin_backup' => [
                'keywords' => ['backup', 'respaldo', 'copia seguridad', 'restaurar'],
                'method' => 'get_admin_backup_response',
                'icon' => 'bi-cloud-upload-fill',
                'admin' => true,
            ],

            // Teacher commands.
            'teacher_grades' => [
                'keywords' => ['calificar', 'gestionar notas', 'libro calificaciones', 'gradebook'],
                'method' => 'get_teacher_grades_response',
                'icon' => 'bi-pencil-square',
                'teacher' => true,
            ],

            // Menu command.
            'menu' => [
                'keywords' => ['menu', 'menu principal', 'opciones', 'ayuda', 'inicio', 'home'],
                'method' => 'get_menu_response',
                'icon' => 'bi-house',
            ],
        ];
    }

    /**
     * Process question to check if it's a quick access command.
     *
     * @param string $question User's question
     * @return array|null Response array or null if not a quick access command
     */
    public function process($question) {
        $question = $this->normalize_text(trim(strtolower($question)));
        $issiteadmin = is_siteadmin();

        foreach ($this->commands as $key => $command) {
            // Skip admin commands for non-admins.
            if (!empty($command['admin']) && !$issiteadmin) {
                continue;
            }

            // Check each keyword.
            foreach ($command['keywords'] as $keyword) {
                $keyword = $this->normalize_text($keyword);
                if ($question === $keyword || strpos($question, $keyword) !== false) {
                    return $this->execute_command($key, $command);
                }
            }
        }

        return null;
    }

    /**
     * Execute a quick access command.
     *
     * @param string $key Command key
     * @param array $command Command definition
     * @return array Response array
     */
    private function execute_command($key, $command) {
        $method = $command['method'];
        $response = $this->$method();

        return [
            'success' => true,
            'response' => $response,
            'ruleid' => null,
            'confidence' => 1.0,
            'options' => $this->get_command_options($key),
            'type' => 'quick_access',
            'command' => $key,
        ];
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
     * Get menu response with available options.
     *
     * @return string HTML response
     */
    private function get_menu_response() {
        return get_string('menu_help', 'local_udesbot');
    }

    /**
     * Get assignments response.
     *
     * @return string HTML response
     */
    private function get_assignments_response() {
        global $CFG;

        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $assignments = $this->context->get_user_assignments();

        if (empty($assignments)) {
            return get_string('shortcut_noassignments', 'local_udesbot');
        }

        $response = get_string('shortcut_assignmentsheader', 'local_udesbot') . '<br><br>';

        foreach ($assignments as $assignment) {
            $duetext = '';
            if ($assignment->duedate) {
                $remaining = $assignment->duedate - time();

                if ($remaining < 0) {
                    $duetext = '<span style="color:#dc3545;">' . get_string('overdue', 'local_udesbot') . '</span>';
                } else if ($remaining < 86400) {
                    $hours = floor($remaining / 3600);
                    $duetext = '<span style="color:#ffc107;">' . get_string('duein', 'local_udesbot', $hours . 'h') . '</span>';
                } else {
                    $days = floor($remaining / 86400);
                    $duetext = get_string('duein', 'local_udesbot', $days . 'd');
                }
            } else {
                $duetext = get_string('noduedate', 'local_udesbot');
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
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $grades = $this->context->get_user_grades();
        $course = $this->context->get_course_info();

        if (!$grades) {
            return get_string('shortcut_nogrades', 'local_udesbot');
        }

        $response = get_string('shortcut_gradesheader', 'local_udesbot', $course['fullname']) . '<br><br>';

        if ($grades['percentage'] !== null) {
            $response .= '<strong>' . get_string('overallgrade', 'local_udesbot') . ':</strong> ';
            $response .= $grades['percentage'] . '% (' . $grades['letter'] . ')<br><br>';
        } else {
            $response .= get_string('notgradedyet', 'local_udesbot') . '<br><br>';
        }

        if (!empty($grades['items'])) {
            $gradeditems = array_filter($grades['items'], function($item) {
                return $item->finalgrade !== null;
            });

            if (!empty($gradeditems)) {
                $response .= '<strong>' . get_string('recentgrades', 'local_udesbot') . ':</strong><br>';
                foreach (array_slice($gradeditems, 0, 5) as $item) {
                    $percentage = $item->grademax > 0 ? round(($item->finalgrade / $item->grademax) * 100, 1) : 0;
                    $response .= "- {$item->name}: {$percentage}%<br>";
                }
            }
        }

        $gradesurl = new \moodle_url('/grade/report/user/index.php', ['id' => $course['id']]);
        $response .= '<br><a href="' . $gradesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallgrades', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get calendar response.
     *
     * @return string HTML response
     */
    private function get_calendar_response() {
        $events = $this->context->get_upcoming_events(7);

        if (empty($events)) {
            return get_string('shortcut_noevents', 'local_udesbot');
        }

        $response = get_string('shortcut_eventsheader', 'local_udesbot') . '<br><br>';

        foreach ($events as $event) {
            $date = userdate($event->timestart, '%a %d/%m %H:%M');
            $response .= "- <strong>{$event->name}</strong> - {$date}<br>";
        }

        $calurl = new \moodle_url('/calendar/view.php');
        $response .= '<br><a href="' . $calurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewcalendar', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get messages response.
     *
     * @return string HTML response
     */
    private function get_messages_response() {
        $unreadcount = $this->context->get_unread_message_count();
        $messages = $this->context->get_recent_messages(5);

        $response = get_string('shortcut_messagesheader', 'local_udesbot') . '<br><br>';

        if ($unreadcount > 0) {
            $response .= '<strong>' . get_string('unreadmessages', 'local_udesbot', $unreadcount) . '</strong><br><br>';
        } else {
            $response .= get_string('nounreadmessages', 'local_udesbot') . '<br><br>';
        }

        if (!empty($messages)) {
            $response .= '<strong>' . get_string('recentmessages', 'local_udesbot') . ':</strong><br>';
            foreach ($messages as $message) {
                $time = userdate($message->timecreated, '%d/%m %H:%M');
                $response .= "- <strong>{$message->from}</strong>: {$message->preview} ({$time})<br>";
            }
        }

        $msgurl = new \moodle_url('/message/index.php');
        $response .= '<br><a href="' . $msgurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallmessages', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get teachers response.
     *
     * @return string HTML response
     */
    private function get_teachers_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $teachers = $this->context->get_course_teachers();
        $course = $this->context->get_course_info();

        if (empty($teachers)) {
            return get_string('shortcut_noteachers', 'local_udesbot');
        }

        $response = get_string('shortcut_teachersheader', 'local_udesbot', $course['fullname']) . '<br><br>';

        foreach ($teachers as $teacher) {
            $msgurl = new \moodle_url('/message/index.php', ['id' => $teacher->id]);
            $response .= "- <strong>{$teacher->fullname}</strong> ";
            $response .= "(<a href='{$msgurl}' target='_blank' rel='noopener'>" . get_string('sendmessage', 'local_udesbot') . "</a>)<br>";
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
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $course = $this->context->get_course_info();
        $teachers = $this->context->get_course_teachers();
        $pending = $this->context->get_pending_tasks_count();

        $response = '<strong>' . $course['fullname'] . '</strong> (' . $course['shortname'] . ')<br><br>';

        if ($course['startdate']) {
            $response .= get_string('startdate') . ': ' . userdate($course['startdate'], '%d/%m/%Y') . '<br>';
        }
        if ($course['enddate']) {
            $response .= get_string('enddate') . ': ' . userdate($course['enddate'], '%d/%m/%Y') . '<br>';
        }

        if (!empty($teachers)) {
            $response .= get_string('teacher', 'local_udesbot') . ': ' . reset($teachers)->fullname . '<br>';
        }

        $response .= '<br>' . get_string('pendingtasks', 'local_udesbot') . ': ' . $pending . '<br>';

        return $response;
    }

    /**
     * Get progress response.
     *
     * @return string HTML response
     */
    private function get_progress_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $course = $this->context->get_course_info();
        $grades = $this->context->get_user_grades();
        $pending = $this->context->get_pending_tasks_count();

        $response = get_string('shortcut_progressheader', 'local_udesbot', $course['fullname']) . '<br><br>';

        if ($grades && $grades['percentage'] !== null) {
            $response .= get_string('currentgrade', 'local_udesbot') . ': ' . $grades['percentage'] . '%<br>';
        }

        $response .= get_string('pendingtasks', 'local_udesbot') . ': ' . $pending . '<br>';

        $completion = $this->get_course_completion();
        if ($completion !== null) {
            $response .= get_string('completion', 'local_udesbot') . ': ' . $completion . '%<br>';
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

        $completion = new \completion_info($DB->get_record('course', ['id' => $courseid]));
        if (!$completion->is_enabled()) {
            return null;
        }

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
        $userid = $this->context->get_user_id();
        $courses = enrol_get_users_courses($userid, true, 'id, fullname, shortname, visible');

        if (empty($courses)) {
            return get_string('shortcut_nocourses', 'local_udesbot');
        }

        $response = get_string('shortcut_coursesheader', 'local_udesbot') . '<br><br>';

        $count = 0;
        foreach ($courses as $course) {
            if ($count >= 10) {
                $remaining = count($courses) - 10;
                $response .= '<br>' . get_string('andmore', 'local_udesbot', $remaining);
                break;
            }

            $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
            $response .= "- <a href='{$courseurl}' target='_blank' rel='noopener'>{$course->fullname}</a><br>";
            $count++;
        }

        $mycoursesurl = new \moodle_url('/my/courses.php');
        $response .= '<br><a href="' . $mycoursesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallcourses', 'local_udesbot') . ' ↗</a>';

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
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $course = $this->context->get_course_info();
        $courseid = $this->context->get_course_id();
        $context = \context_course::instance($courseid);

        $users = get_enrolled_users($context, '', 0, 'u.id, u.firstname, u.lastname', 'u.lastname, u.firstname', 0, 50);

        if (empty($users)) {
            return get_string('shortcut_noparticipants', 'local_udesbot');
        }

        $response = get_string('shortcut_participantsheader', 'local_udesbot', $course['fullname']) . '<br><br>';

        $totalcount = count_enrolled_users($context);
        $response .= '<strong>' . get_string('totalparticipants', 'local_udesbot', $totalcount) . '</strong><br><br>';

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
            $response .= '<br>' . get_string('andmore', 'local_udesbot', $remaining);
        }

        $participantsurl = new \moodle_url('/user/index.php', ['id' => $courseid]);
        $response .= '<br><a href="' . $participantsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallparticipants', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get user's badges response.
     *
     * @return string HTML response
     */
    private function get_badges_response() {
        global $CFG;

        require_once($CFG->libdir . '/badgeslib.php');

        $userid = $this->context->get_user_id();
        $badges = badges_get_user_badges($userid);

        if (empty($badges)) {
            return get_string('shortcut_nobadges', 'local_udesbot');
        }

        $response = get_string('shortcut_badgesheader', 'local_udesbot') . '<br><br>';

        $count = 0;
        foreach ($badges as $badge) {
            if ($count >= 10) {
                $remaining = count($badges) - 10;
                $response .= '<br>' . get_string('andmore', 'local_udesbot', $remaining);
                break;
            }

            $badgeurl = new \moodle_url('/badges/badge.php', ['hash' => $badge->uniquehash]);
            $dateissued = userdate($badge->dateissued, '%d/%m/%Y');
            $response .= "- <a href='{$badgeurl}' target='_blank' rel='noopener'><strong>{$badge->name}</strong></a>";
            $response .= " - " . get_string('issuedon', 'local_udesbot', $dateissued) . "<br>";
            $count++;
        }

        $badgesurl = new \moodle_url('/badges/mybadges.php');
        $response .= '<br><a href="' . $badgesurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewallbadges', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get teacher grades management response.
     *
     * @return string HTML response
     */
    private function get_teacher_grades_response() {
        if (!$this->context->is_in_course()) {
            return get_string('shortcut_nocourse', 'local_udesbot');
        }

        $course = $this->context->get_course_info();
        $courseid = $this->context->get_course_id();

        $response = get_string('shortcut_teachergradesheader', 'local_udesbot', $course['fullname']) . '<br><br>';

        $graderurl = new \moodle_url('/grade/report/grader/index.php', ['id' => $courseid]);
        $response .= '<a href="' . $graderurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewgraderreport', 'local_udesbot') . ' ↗</a><br>';

        $setupurl = new \moodle_url('/grade/edit/tree/index.php', ['id' => $courseid]);
        $response .= '<a href="' . $setupurl . '" target="_blank" rel="noopener">';
        $response .= get_string('gradebooksetup', 'local_udesbot') . ' ↗</a><br>';

        return $response;
    }

    /**
     * Get admin users management response.
     *
     * @return string HTML response
     */
    private function get_admin_users_response() {
        global $DB;

        $response = get_string('shortcut_adminusersheader', 'local_udesbot') . '<br><br>';

        $usercount = $DB->count_records('user', ['deleted' => 0, 'suspended' => 0]);
        $response .= '<strong>' . get_string('totalusers', 'local_udesbot', $usercount) . '</strong><br><br>';

        $browseurl = new \moodle_url('/admin/user.php');
        $response .= '<a href="' . $browseurl . '" target="_blank" rel="noopener">';
        $response .= get_string('browseusers', 'local_udesbot') . ' ↗</a><br>';

        $addurl = new \moodle_url('/user/editadvanced.php', ['id' => -1]);
        $response .= '<a href="' . $addurl . '" target="_blank" rel="noopener">';
        $response .= get_string('addnewuser', 'local_udesbot') . ' ↗</a><br>';

        $uploadurl = new \moodle_url('/admin/tool/uploaduser/index.php');
        $response .= '<a href="' . $uploadurl . '" target="_blank" rel="noopener">';
        $response .= get_string('uploadusers', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin courses management response.
     *
     * @return string HTML response
     */
    private function get_admin_courses_response() {
        global $DB;

        $response = get_string('shortcut_admincoursesheader', 'local_udesbot') . '<br><br>';

        $coursecount = $DB->count_records('course') - 1;
        $response .= '<strong>' . get_string('totalcourses', 'local_udesbot', $coursecount) . '</strong><br><br>';

        $manageurl = new \moodle_url('/course/management.php');
        $response .= '<a href="' . $manageurl . '" target="_blank" rel="noopener">';
        $response .= get_string('managecourses', 'local_udesbot') . ' ↗</a><br>';

        $addurl = new \moodle_url('/course/edit.php', ['category' => 1]);
        $response .= '<a href="' . $addurl . '" target="_blank" rel="noopener">';
        $response .= get_string('addnewcourse', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin reports response.
     *
     * @return string HTML response
     */
    private function get_admin_reports_response() {
        $response = get_string('shortcut_adminreportsheader', 'local_udesbot') . '<br><br>';

        $logsurl = new \moodle_url('/report/log/index.php');
        $response .= '<a href="' . $logsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewlogs', 'local_udesbot') . ' ↗</a><br>';

        $livelogsurl = new \moodle_url('/report/loglive/index.php');
        $response .= '<a href="' . $livelogsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewlivelogs', 'local_udesbot') . ' ↗</a><br>';

        $statsurl = new \moodle_url('/report/stats/index.php');
        $response .= '<a href="' . $statsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('viewstatistics', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin settings response.
     *
     * @return string HTML response
     */
    private function get_admin_settings_response() {
        $response = get_string('shortcut_adminsettingsheader', 'local_udesbot') . '<br><br>';

        $adminurl = new \moodle_url('/admin/search.php');
        $response .= '<a href="' . $adminurl . '" target="_blank" rel="noopener">';
        $response .= get_string('siteadministration', 'local_udesbot') . ' ↗</a><br>';

        $frontpageurl = new \moodle_url('/admin/settings.php', ['section' => 'frontpagesettings']);
        $response .= '<a href="' . $frontpageurl . '" target="_blank" rel="noopener">';
        $response .= get_string('frontpagesettings', 'local_udesbot') . ' ↗</a><br>';

        $appearanceurl = new \moodle_url('/admin/settings.php', ['section' => 'themesettings']);
        $response .= '<a href="' . $appearanceurl . '" target="_blank" rel="noopener">';
        $response .= get_string('appearancesettings', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin plugins management response.
     *
     * @return string HTML response
     */
    private function get_admin_plugins_response() {
        $response = get_string('shortcut_adminpluginsheader', 'local_udesbot') . '<br><br>';

        $pluginsurl = new \moodle_url('/admin/plugins.php');
        $response .= '<a href="' . $pluginsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('pluginsoverview', 'local_udesbot') . ' ↗</a><br>';

        $installurl = new \moodle_url('/admin/tool/installaddon/index.php');
        $response .= '<a href="' . $installurl . '" target="_blank" rel="noopener">';
        $response .= get_string('installplugins', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin security response.
     *
     * @return string HTML response
     */
    private function get_admin_security_response() {
        $response = get_string('shortcut_adminsecurityheader', 'local_udesbot') . '<br><br>';

        $overviewurl = new \moodle_url('/admin/settings.php', ['section' => 'sitepolicies']);
        $response .= '<a href="' . $overviewurl . '" target="_blank" rel="noopener">';
        $response .= get_string('sitepolicies', 'local_udesbot') . ' ↗</a><br>';

        $httpurl = new \moodle_url('/admin/settings.php', ['section' => 'httpsecurity']);
        $response .= '<a href="' . $httpurl . '" target="_blank" rel="noopener">';
        $response .= get_string('httpsecurity', 'local_udesbot') . ' ↗</a><br>';

        $reporturl = new \moodle_url('/report/security/index.php');
        $response .= '<a href="' . $reporturl . '" target="_blank" rel="noopener">';
        $response .= get_string('securityreport', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get admin backup response.
     *
     * @return string HTML response
     */
    private function get_admin_backup_response() {
        $response = get_string('shortcut_adminbackupheader', 'local_udesbot') . '<br><br>';

        $settingsurl = new \moodle_url('/admin/settings.php', ['section' => 'backupgeneralsettings']);
        $response .= '<a href="' . $settingsurl . '" target="_blank" rel="noopener">';
        $response .= get_string('backupsettings', 'local_udesbot') . ' ↗</a><br>';

        $automatedurl = new \moodle_url('/admin/settings.php', ['section' => 'automated']);
        $response .= '<a href="' . $automatedurl . '" target="_blank" rel="noopener">';
        $response .= get_string('automatedbackups', 'local_udesbot') . ' ↗</a><br>';

        $restoreurl = new \moodle_url('/backup/restorefile.php', ['contextid' => 1]);
        $response .= '<a href="' . $restoreurl . '" target="_blank" rel="noopener">';
        $response .= get_string('restoresite', 'local_udesbot') . ' ↗</a>';

        return $response;
    }

    /**
     * Get options for a command.
     *
     * @param string $key Command key
     * @return array Options
     */
    private function get_command_options($key) {
        $options = [];

        switch ($key) {
            case 'assignments':
                $options[] = ['text' => 'Ver Calificaciones', 'action' => 'ver mis calificaciones'];
                $options[] = ['text' => 'Ver Calendario', 'action' => 'calendario'];
                break;
            case 'grades':
                $options[] = ['text' => 'Ver Tareas', 'action' => 'mis tareas'];
                $options[] = ['text' => 'Ver Profesores', 'action' => 'mis profesores'];
                break;
            case 'calendar':
                $options[] = ['text' => 'Ver Tareas', 'action' => 'mis tareas'];
                $options[] = ['text' => 'Mi Progreso', 'action' => 'mi progreso'];
                break;
            case 'messages':
                $options[] = ['text' => 'Ver Profesores', 'action' => 'mis profesores'];
                break;
            case 'courses':
                $options[] = ['text' => 'Mis Tareas', 'action' => 'mis tareas'];
                $options[] = ['text' => 'Mis Calificaciones', 'action' => 'mis calificaciones'];
                break;
            case 'admin_users':
                $options[] = ['text' => 'Gestionar Cursos', 'action' => 'gestionar cursos'];
                $options[] = ['text' => 'Ver Reportes', 'action' => 'reportes'];
                break;
            case 'admin_courses':
                $options[] = ['text' => 'Gestionar Usuarios', 'action' => 'gestionar usuarios'];
                $options[] = ['text' => 'Ver Backups', 'action' => 'backup'];
                break;
            case 'menu':
                // No additional options for menu.
                return [];
        }

        // Always add menu option.
        $options[] = ['text' => 'Menu Principal', 'action' => 'menu'];

        return $options;
    }

    /**
     * Get menu options based on user role.
     *
     * @return array Menu options
     */
    public function get_menu_options() {
        $issiteadmin = is_siteadmin();
        $incourse = $this->context->is_in_course();

        $options = [];

        // Student options (always available).
        if ($incourse) {
            $options[] = ['text' => 'Mis Tareas', 'action' => 'mis tareas', 'icon' => 'bi-file-earmark-text'];
            $options[] = ['text' => 'Mis Calificaciones', 'action' => 'mis calificaciones', 'icon' => 'bi-trophy'];
            $options[] = ['text' => 'Mi Progreso', 'action' => 'mi progreso', 'icon' => 'bi-graph-up'];
            $options[] = ['text' => 'Mis Profesores', 'action' => 'mis profesores', 'icon' => 'bi-person-badge'];
        }

        $options[] = ['text' => 'Mis Cursos', 'action' => 'mis cursos', 'icon' => 'bi-collection'];
        $options[] = ['text' => 'Calendario', 'action' => 'calendario', 'icon' => 'bi-calendar-event'];
        $options[] = ['text' => 'Mensajes', 'action' => 'mensajes', 'icon' => 'bi-envelope'];
        $options[] = ['text' => 'Mis Insignias', 'action' => 'mis insignias', 'icon' => 'bi-award'];

        // Admin options.
        if ($issiteadmin) {
            $options[] = ['text' => 'Gestionar Usuarios', 'action' => 'gestionar usuarios', 'icon' => 'bi-people-fill'];
            $options[] = ['text' => 'Gestionar Cursos', 'action' => 'gestionar cursos', 'icon' => 'bi-journal-bookmark-fill'];
            $options[] = ['text' => 'Reportes', 'action' => 'reportes', 'icon' => 'bi-bar-chart-fill'];
            $options[] = ['text' => 'Configuracion', 'action' => 'configuracion', 'icon' => 'bi-gear-fill'];
        }

        return $options;
    }
}
