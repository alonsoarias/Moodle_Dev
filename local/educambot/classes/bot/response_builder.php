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
 * Response builder for educambot - builds dynamic responses with placeholders.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Response builder class - handles placeholder replacement in dynamic responses.
 */
class response_builder {

    /** @var context_handler Context handler instance */
    private $context;

    /** @var array Cached data */
    private $cache = [];

    /**
     * Constructor.
     *
     * @param context_handler $context Context handler instance
     */
    public function __construct(context_handler $context) {
        $this->context = $context;
    }

    /**
     * Build dynamic response by replacing placeholders with real data.
     *
     * @param string $template Response template with placeholders
     * @return string Processed response
     */
    public function build_response($template) {
        // Define all available placeholders.
        $placeholders = $this->get_placeholders();

        // Replace placeholders in template.
        $response = $template;
        foreach ($placeholders as $placeholder => $value) {
            $response = str_replace($placeholder, $value, $response);
        }

        return $response;
    }

    /**
     * Get all available placeholders with their values.
     *
     * @return array Placeholder => value pairs
     */
    public function get_placeholders() {
        return [
            // Course placeholders.
            '{{course.name}}' => $this->get_course_name(),
            '{{course.shortname}}' => $this->get_course_shortname(),
            '{{course.teacher}}' => $this->get_teacher_name(),
            '{{course.teachers}}' => $this->get_all_teachers(),
            '{{course.startdate}}' => $this->get_course_startdate(),
            '{{course.enddate}}' => $this->get_course_enddate(),

            // User placeholders.
            '{{user.firstname}}' => $this->get_user_firstname(),
            '{{user.lastname}}' => $this->get_user_lastname(),
            '{{user.fullname}}' => $this->get_user_fullname(),
            '{{user.email}}' => $this->get_user_email(),
            '{{user.grade}}' => $this->get_current_grade(),
            '{{user.gradeletter}}' => $this->get_grade_letter(),

            // Activity placeholders.
            '{{next.assignment}}' => $this->get_next_assignment(),
            '{{next.assignment.name}}' => $this->get_next_assignment_name(),
            '{{next.assignment.date}}' => $this->get_next_assignment_date(),
            '{{next.quiz}}' => $this->get_next_quiz(),
            '{{pending.tasks}}' => $this->get_pending_tasks_count(),
            '{{pending.tasks.list}}' => $this->get_pending_tasks_list(),

            // Calendar placeholders.
            '{{next.event}}' => $this->get_next_event(),
            '{{events.week}}' => $this->get_week_events(),

            // Messages placeholders.
            '{{unread.messages}}' => $this->get_unread_count(),

            // Site placeholders.
            '{{site.name}}' => $this->get_site_name(),
        ];
    }

    /**
     * Get list of available placeholders for help/documentation.
     *
     * @return array List of placeholder descriptions
     */
    public static function get_available_placeholders() {
        return [
            '{{course.name}}' => 'Nombre completo del curso',
            '{{course.shortname}}' => 'Nombre corto del curso',
            '{{course.teacher}}' => 'Nombre del profesor principal',
            '{{course.teachers}}' => 'Lista de todos los profesores',
            '{{course.startdate}}' => 'Fecha de inicio del curso',
            '{{course.enddate}}' => 'Fecha de fin del curso',
            '{{user.firstname}}' => 'Nombre del usuario',
            '{{user.lastname}}' => 'Apellido del usuario',
            '{{user.fullname}}' => 'Nombre completo del usuario',
            '{{user.email}}' => 'Email del usuario',
            '{{user.grade}}' => 'Calificacion actual (porcentaje)',
            '{{user.gradeletter}}' => 'Calificacion en letra',
            '{{next.assignment}}' => 'Proxima tarea con fecha',
            '{{next.assignment.name}}' => 'Nombre de la proxima tarea',
            '{{next.assignment.date}}' => 'Fecha de la proxima tarea',
            '{{pending.tasks}}' => 'Numero de tareas pendientes',
            '{{pending.tasks.list}}' => 'Lista de tareas pendientes',
            '{{next.event}}' => 'Proximo evento del calendario',
            '{{events.week}}' => 'Eventos de esta semana',
            '{{unread.messages}}' => 'Numero de mensajes no leidos',
            '{{site.name}}' => 'Nombre del sitio Moodle',
        ];
    }

    // Course methods.

    /**
     * Get course name.
     *
     * @return string Course name or N/A
     */
    private function get_course_name() {
        $info = $this->context->get_course_info();
        return $info ? $info['fullname'] : get_string('notavailable', 'local_educambot');
    }

    /**
     * Get course short name.
     *
     * @return string Course short name or N/A
     */
    private function get_course_shortname() {
        $info = $this->context->get_course_info();
        return $info ? $info['shortname'] : get_string('notavailable', 'local_educambot');
    }

    /**
     * Get main teacher name.
     *
     * @return string Teacher name or generic text
     */
    private function get_teacher_name() {
        $teachers = $this->context->get_course_teachers();
        if (!empty($teachers)) {
            return reset($teachers)->fullname;
        }
        return get_string('theteacher', 'local_educambot');
    }

    /**
     * Get all teachers as formatted list.
     *
     * @return string Formatted list of teachers
     */
    private function get_all_teachers() {
        $teachers = $this->context->get_course_teachers();
        if (empty($teachers)) {
            return get_string('noteachersassigned', 'local_educambot');
        }

        $names = array_map(function($t) {
            return $t->fullname;
        }, $teachers);

        return implode(', ', $names);
    }

    /**
     * Get course start date.
     *
     * @return string Formatted date
     */
    private function get_course_startdate() {
        $info = $this->context->get_course_info();
        if ($info && $info['startdate']) {
            return userdate($info['startdate'], get_string('strftimedatefullshort', 'langconfig'));
        }
        return get_string('notavailable', 'local_educambot');
    }

    /**
     * Get course end date.
     *
     * @return string Formatted date
     */
    private function get_course_enddate() {
        $info = $this->context->get_course_info();
        if ($info && $info['enddate']) {
            return userdate($info['enddate'], get_string('strftimedatefullshort', 'langconfig'));
        }
        return get_string('noenddate', 'local_educambot');
    }

    // User methods.

    /**
     * Get user first name.
     *
     * @return string First name
     */
    private function get_user_firstname() {
        $info = $this->context->get_user_info();
        return $info['firstname'];
    }

    /**
     * Get user last name.
     *
     * @return string Last name
     */
    private function get_user_lastname() {
        $info = $this->context->get_user_info();
        return $info['lastname'];
    }

    /**
     * Get user full name.
     *
     * @return string Full name
     */
    private function get_user_fullname() {
        $info = $this->context->get_user_info();
        return $info['fullname'] ?? ($info['firstname'] . ' ' . $info['lastname']);
    }

    /**
     * Get user email.
     *
     * @return string Email
     */
    private function get_user_email() {
        $info = $this->context->get_user_info();
        return $info['email'];
    }

    /**
     * Get current grade percentage.
     *
     * @return string Grade percentage
     */
    private function get_current_grade() {
        $grades = $this->context->get_user_grades();
        if ($grades && $grades['percentage'] !== null) {
            return $grades['percentage'] . '%';
        }
        return get_string('notgraded', 'local_educambot');
    }

    /**
     * Get grade letter.
     *
     * @return string Grade letter
     */
    private function get_grade_letter() {
        $grades = $this->context->get_user_grades();
        return $grades ? $grades['letter'] : '-';
    }

    // Assignment methods.

    /**
     * Get next assignment with date.
     *
     * @return string Next assignment info
     */
    private function get_next_assignment() {
        $assignment = $this->context->get_next_assignment();
        if ($assignment) {
            $date = $assignment->duedate ? userdate($assignment->duedate, '%d/%m/%Y') : get_string('noduedate', 'local_educambot');
            return $assignment->name . ' - ' . get_string('duedate', 'local_educambot') . ': ' . $date;
        }
        return get_string('nopendingassignments', 'local_educambot');
    }

    /**
     * Get next assignment name only.
     *
     * @return string Assignment name
     */
    private function get_next_assignment_name() {
        $assignment = $this->context->get_next_assignment();
        return $assignment ? $assignment->name : get_string('nopendingassignments', 'local_educambot');
    }

    /**
     * Get next assignment date only.
     *
     * @return string Assignment date
     */
    private function get_next_assignment_date() {
        $assignment = $this->context->get_next_assignment();
        if ($assignment && $assignment->duedate) {
            return userdate($assignment->duedate, '%d/%m/%Y %H:%M');
        }
        return get_string('noduedate', 'local_educambot');
    }

    /**
     * Get next quiz.
     *
     * @return string Next quiz info
     */
    private function get_next_quiz() {
        // TODO: Implement quiz tracking.
        return get_string('nopendingquizzes', 'local_educambot');
    }

    /**
     * Get pending tasks count.
     *
     * @return string Number of pending tasks
     */
    private function get_pending_tasks_count() {
        return (string) $this->context->get_pending_tasks_count();
    }

    /**
     * Get pending tasks as HTML list.
     *
     * @return string HTML list of tasks
     */
    private function get_pending_tasks_list() {
        $assignments = $this->context->get_user_assignments();
        if (empty($assignments)) {
            return get_string('nopendingassignments', 'local_educambot');
        }

        $list = '<ul>';
        foreach (array_slice($assignments, 0, 5) as $assignment) {
            $date = $assignment->duedate ? userdate($assignment->duedate, '%d/%m/%Y') : '';
            $list .= "<li><a href='{$assignment->url}'>{$assignment->name}</a>";
            if ($date) {
                $list .= " - {$date}";
            }
            $list .= '</li>';
        }
        $list .= '</ul>';

        if (count($assignments) > 5) {
            $list .= get_string('andmore', 'local_educambot', count($assignments) - 5);
        }

        return $list;
    }

    // Calendar methods.

    /**
     * Get next event.
     *
     * @return string Next event info
     */
    private function get_next_event() {
        $event = $this->context->get_next_event();
        if ($event) {
            $date = userdate($event->timestart, '%d/%m/%Y %H:%M');
            return $event->name . ' - ' . $date;
        }
        return get_string('noupcomingevents', 'local_educambot');
    }

    /**
     * Get week events as list.
     *
     * @return string HTML list of events
     */
    private function get_week_events() {
        $events = $this->context->get_upcoming_events(7);
        if (empty($events)) {
            return get_string('noeventthisweek', 'local_educambot');
        }

        $list = '<ul>';
        foreach ($events as $event) {
            $date = userdate($event->timestart, '%d/%m %H:%M');
            $list .= "<li>{$event->name} - {$date}</li>";
        }
        $list .= '</ul>';

        return $list;
    }

    // Messages methods.

    /**
     * Get unread message count.
     *
     * @return string Number of unread messages
     */
    private function get_unread_count() {
        return (string) $this->context->get_unread_message_count();
    }

    /**
     * Check if response has placeholders.
     *
     * @param string $response Response text to check
     * @return bool True if has placeholders
     */
    public static function has_placeholders($response) {
        return preg_match('/\{\{[a-z.]+\}\}/', $response) === 1;
    }

    // Site methods.

    /**
     * Get site name.
     *
     * @return string Site full name
     */
    private function get_site_name() {
        global $SITE;
        return format_string($SITE->fullname);
    }
}
