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
 * Provides user and course context to personalise chatbot answers.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local;

use context_course;
use html_writer;
use moodle_url;
use stdClass;

/**
 * Resolves user context and exposes helpers to personalise messages.
 */
class context_provider {
    /** @var int|null */
    protected ?int $userid;

    /** @var int|null */
    protected ?int $courseid;

    /** @var string|null */
    protected ?string $pageidentifier;

    /** @var stdClass|null */
    protected ?stdClass $user = null;

    /** @var array|null */
    protected ?array $courses = null;

    /**
     * Constructor.
     *
     * @param int|null $userid
     * @param int|null $courseid
     * @param string|null $pageidentifier
     */
    public function __construct(?int $userid, ?int $courseid, ?string $pageidentifier) {
        $this->userid = $userid;
        $this->courseid = $courseid;
        $this->pageidentifier = $pageidentifier;
    }

    /**
     * Returns the bot name configured by administrators.
     *
     * @param array $config
     * @return string
     */
    public function get_bot_name(array $config): string {
        $name = trim($config['botname'] ?? '');
        if ($name === '') {
            $name = get_string('defaultbotname', 'local_educambot');
        }
        return format_string($name);
    }

    /**
     * Builds a placeholder map that can be used to personalise HTML snippets.
     *
     * @param array $config
     * @return array
     */
    public function build_placeholder_map(array $config): array {
        $user = $this->get_user();
        $botname = $this->get_bot_name($config);
        $fullname = $user ? fullname($user) : get_string('guestuser', 'core');
        $firstname = $user->firstname ?? '';
        $lastname = $user->lastname ?? '';
        $courses = $this->get_courses();
        $coursenames = array_map(static fn($course) => format_string($course->fullname), $courses);
        $courselistplain = implode(', ', $coursenames);

        $courselisthtml = '';
        if (!empty($courses)) {
            $items = [];
            foreach ($courses as $course) {
                $url = (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false);
                $items[] = html_writer::tag('li', html_writer::link($url, format_string($course->fullname)));
            }
            $courselisthtml = html_writer::tag('ul', implode('', $items), ['class' => 'local-educambot__courses']);
        }

        $focuscourse = $this->get_focus_course();
        $focuscourseurl = '';
        $focuscoursename = '';
        if ($focuscourse) {
            $focuscoursename = format_string($focuscourse->fullname);
            $focuscourseurl = (new moodle_url('/course/view.php', ['id' => $focuscourse->id]))->out(false);
        }

        return [
            'botname' => $botname,
            'userfullname' => format_string($fullname),
            'userfirstname' => format_string($firstname),
            'userlastname' => format_string($lastname),
            'coursenames' => $courselistplain,
            'courselist' => $courselisthtml,
            'courselist_plain' => $courselistplain,
            'focuscourse' => $focuscoursename,
            'focuscourseurl' => $focuscourseurl,
        ];
    }

    /**
     * Personalises a HTML string replacing placeholders with contextual data.
     *
     * @param string $html
     * @param array $config
     * @return string
     */
    public function personalise_html(string $html, array $config): string {
        $placeholders = $this->build_placeholder_map($config);
        return interpolator::render($html, $placeholders);
    }

    /**
     * Generates an initial greeting for the chat widget using configured template.
     *
     * @param array $config
     * @return string
     */
    public function build_initial_greeting(array $config): string {
        $template = trim($config['greetingtemplate'] ?? '');
        if ($template === '') {
            $template = get_string('defaultgreeting', 'local_educambot');
        }
        $placeholders = $this->build_placeholder_map($config);
        return interpolator::render($template, $placeholders);
    }

    /**
     * Returns the main course related to the current page when available.
     *
     * @return stdClass|null
     */
    public function get_focus_course(): ?stdClass {
        if (!$this->courseid) {
            return null;
        }
        $courses = $this->get_courses();
        foreach ($courses as $course) {
            if ((int)$course->id === (int)$this->courseid) {
                return $course;
            }
        }
        // Load course even if user is not enrolled to provide context.
        try {
            $course = get_course($this->courseid);
            return $course ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Lazy loads the user record.
     *
     * @return stdClass|null
     */
    protected function get_user(): ?stdClass {
        global $DB;

        if ($this->userid === null) {
            return null;
        }
        if ($this->user !== null) {
            return $this->user;
        }
        $this->user = $DB->get_record('user', ['id' => $this->userid], '*', IGNORE_MISSING) ?: null;
        return $this->user;
    }

    /**
     * Returns the list of courses available to the user.
     *
     * @return stdClass[]
     */
    public function get_courses(): array {
        global $CFG;

        if ($this->courses !== null) {
            return $this->courses;
        }

        $this->courses = [];
        if (!$this->userid) {
            return $this->courses;
        }

        require_once($CFG->libdir . '/enrollib.php');
        $courses = enrol_get_users_courses($this->userid, true, 'id, fullname, shortname, idnumber, visible');
        foreach ($courses as $course) {
            if (!$course->visible) {
                $coursecontext = context_course::instance($course->id);
                if (!has_capability('moodle/course:viewhiddencourses', $coursecontext, $this->userid)) {
                    continue;
                }
            }
            $this->courses[] = $course;
        }

        return $this->courses;
    }
}
