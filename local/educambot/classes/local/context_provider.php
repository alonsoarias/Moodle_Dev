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
use context_system;
use core_text;
use core_calendar\local\api as calendar_api;
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

    /** @var array<int,array> */
    protected array $courseoverviewcache = [];

    /** @var array<string,array> */
    protected array $upcomingcache = [];

    /** @var array<int,string>|null Cache of user role shortnames */
    protected ?array $userrolesshortnames = null;

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

        $placeholders = [
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

        $overview = $this->get_course_overview_data($focuscourse->id ?? null);
        $placeholders['focuscourse_summary'] = $overview['summaryhtml'] ?? '';
        $placeholders['focuscourse_summary_plain'] = $overview['summaryplain'] ?? '';
        $placeholders['focuscourse_sections'] = $overview['sectionshtml'] ?? '';
        $placeholders['focuscourse_sections_plain'] = $overview['sectionsplain'] ?? '';

        $events = [];
        if ($focuscourse) {
            $events = $this->get_upcoming_events($focuscourse->id, 5);
        }
        if (empty($events)) {
            $events = $this->get_upcoming_events(null, 5);
        }
        $placeholders['pendingactivities'] = $this->render_events_html($events);
        $placeholders['pendingactivities_plain'] = $this->render_events_plain($events);
        $placeholders['pendingactivities_count'] = count($events);
        $placeholders['nextdue'] = '';
        $placeholders['nextdue_date'] = '';
        $placeholders['nextdue_link'] = '';
        $placeholders['nextdue_course'] = '';
        if (!empty($events)) {
            $next = $events[0];
            $placeholders['nextdue'] = $next['name'] ?? '';
            $placeholders['nextdue_date'] = $next['duedate'] ?? '';
            $placeholders['nextdue_link'] = $next['url'] ?? '';
            $placeholders['nextdue_course'] = $next['course'] ?? '';
        }

        return $placeholders;
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
     * Returns a summary of the focus course including sections and summaries.
     *
     * @param int|null $courseid
     * @return array
     */
    protected function get_course_overview_data(?int $courseid = null): array {
        global $CFG;

        $course = null;
        if ($courseid) {
            try {
                $course = get_course($courseid);
            } catch (\Throwable $e) {
                return [];
            }
        } else {
            $course = $this->get_focus_course();
        }

        if (!$course) {
            return [];
        }

        $cachekey = (int)$course->id;
        if (isset($this->courseoverviewcache[$cachekey])) {
            return $this->courseoverviewcache[$cachekey];
        }

        $summaryhtml = '';
        $summaryplain = '';
        if (!empty($course->summary)) {
            $summaryhtml = format_text($course->summary, $course->summaryformat ?? FORMAT_HTML, ['filter' => true]);
            $summaryplain = trim(strip_tags($summaryhtml));
        }

        $sections = [];
        try {
            require_once($CFG->dirroot . '/course/lib.php');
            $modinfo = get_fast_modinfo($course->id, $this->userid ?? 0);
            foreach ($modinfo->get_section_info_all() as $section) {
                if (property_exists($section, 'uservisible') && !$section->uservisible) {
                    continue;
                }
                $sectionname = format_string(get_section_name($course, $section));
                $sectionsummary = '';
                if (!empty($section->summary)) {
                    $sectionsummary = format_text($section->summary, $section->summaryformat ?? FORMAT_HTML, ['filter' => true]);
                }
                if ($sectionname === '' && trim(strip_tags($sectionsummary)) === '') {
                    continue;
                }
                $sections[] = [
                    'name' => $sectionname,
                    'summary' => $sectionsummary,
                ];
            }
        } catch (\Throwable $e) {
            $sections = [];
        }

        $data = [
            'id' => $course->id,
            'summaryhtml' => $summaryhtml,
            'summaryplain' => $summaryplain,
            'sections' => $sections,
            'sectionshtml' => $this->render_sections_html($sections),
            'sectionsplain' => $this->render_sections_plain($sections),
        ];

        $this->courseoverviewcache[$cachekey] = $data;
        return $data;
    }

    /**
     * Converts section information into HTML snippets.
     *
     * @param array $sections
     * @return string
     */
    protected function render_sections_html(array $sections): string {
        if (empty($sections)) {
            return '';
        }
        $items = [];
        foreach ($sections as $section) {
            $content = html_writer::tag('div', $section['name'], ['class' => 'local-educambot__section-title']);
            if (!empty($section['summary'])) {
                $content .= html_writer::tag('div', $section['summary'], ['class' => 'local-educambot__section-summary']);
            }
            $items[] = html_writer::tag('li', $content, ['class' => 'local-educambot__section']);
        }
        return html_writer::tag('ul', implode('', $items), ['class' => 'local-educambot__sections']);
    }

    /**
     * Produces a plain-text representation of course sections.
     *
     * @param array $sections
     * @return string
     */
    protected function render_sections_plain(array $sections): string {
        if (empty($sections)) {
            return '';
        }
        $parts = [];
        foreach ($sections as $section) {
            $text = $section['name'];
            $summaryplain = trim(strip_tags($section['summary'] ?? ''));
            if ($summaryplain !== '') {
                $text .= ' - ' . $summaryplain;
            }
            $parts[] = $text;
        }
        return implode('; ', $parts);
    }

    /**
     * Returns upcoming events for the current user and course.
     *
     * @param int|null $courseid
     * @param int $limit
     * @return array
     */
    public function get_upcoming_events(?int $courseid = null, int $limit = 5): array {
        $user = $this->get_user();
        if (!$user) {
            return [];
        }

        $limit = max(1, min(10, $limit));
        $cachekey = ($courseid ?? 0) . ':' . $limit;
        if (isset($this->upcomingcache[$cachekey])) {
            return $this->upcomingcache[$cachekey];
        }

        $events = [];
        $start = time();
        $end = $start + (WEEKSECS * 8);

        try {
            if ($courseid) {
                $course = get_course($courseid);
                $raw = calendar_api::get_action_events_by_course($course, $start, $end, null, $limit);
            } else {
                $raw = calendar_api::get_action_events_by_timesort($start, $end, null, $limit, true, $user);
            }

            foreach ($raw as $event) {
                $times = $event->get_times();
                $starttime = $times->get_start_time()->getTimestamp();
                $sorttime = $times->get_sort_time()->getTimestamp();
                $action = null;
                try {
                    $action = $event->get_action();
                } catch (\Throwable $e) {
                    $action = null;
                }
                $url = '';
                if ($action && method_exists($action, 'is_actionable') && $action->is_actionable()) {
                    $url = $action->get_url()->out(false);
                }
                $courseproxy = $event->get_course();
                $coursename = '';
                $coursevalue = null;
                if ($courseproxy) {
                    try {
                        $coursevalue = $courseproxy->get('id');
                        $coursename = format_string($courseproxy->get('fullname') ?? '');
                    } catch (\Throwable $e) {
                        $coursevalue = null;
                    }
                }

                $events[] = [
                    'name' => format_string($event->get_name()),
                    'timestamp' => $sorttime ?: $starttime,
                    'duedate' => userdate($starttime),
                    'url' => $url,
                    'courseid' => $coursevalue ? (int)$coursevalue : null,
                    'course' => $coursename,
                ];
            }
        } catch (\Throwable $e) {
            $events = [];
        }

        usort($events, static function(array $a, array $b) {
            return ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0);
        });

        $events = array_slice($events, 0, $limit);
        $this->upcomingcache[$cachekey] = $events;
        return $events;
    }

    /**
     * Renders upcoming events as an HTML list.
     *
     * @param array $events
     * @return string
     */
    protected function render_events_html(array $events): string {
        if (empty($events)) {
            return '';
        }
        $items = [];
        foreach ($events as $event) {
            $name = $event['name'] ?? '';
            $date = $event['duedate'] ?? '';
            $course = $event['course'] ?? '';
            $content = html_writer::tag('div', $name, ['class' => 'local-educambot__event-name']);
            if ($course !== '') {
                $content .= html_writer::tag('div', $course, ['class' => 'local-educambot__event-course']);
            }
            if ($date !== '') {
                $content .= html_writer::tag('div', $date, ['class' => 'local-educambot__event-date']);
            }
            if (!empty($event['url'])) {
                $content .= html_writer::link($event['url'], get_string('eventopenlink', 'local_educambot'), ['class' => 'local-educambot__event-link']);
            }
            $items[] = html_writer::tag('li', $content, ['class' => 'local-educambot__event']);
        }
        return html_writer::tag('ul', implode('', $items), ['class' => 'local-educambot__event-list']);
    }

    /**
     * Renders upcoming events as plain text.
     *
     * @param array $events
     * @return string
     */
    protected function render_events_plain(array $events): string {
        if (empty($events)) {
            return '';
        }
        $parts = [];
        foreach ($events as $event) {
            $text = $event['name'] ?? '';
            if (!empty($event['duedate'])) {
                $text .= ' (' . $event['duedate'] . ')';
            }
            if (!empty($event['course'])) {
                $text .= ' - ' . $event['course'];
            }
            $parts[] = $text;
        }
        return implode('; ', $parts);
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

        $cache = \cache::make('local_educambot', 'user_courses');
        $cachekey = 'user_' . $this->userid;
        $cached = $cache->get($cachekey);
        if ($cached !== false) {
            $this->courses = $cached;
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

        $cache->set($cachekey, $this->courses);

        return $this->courses;
    }

    /**
     * Returns the list of effective role shortnames for the current user.
     *
     * @return array<int,string> Normalised role shortnames.
     */
    public function get_effective_roles(): array {
        if ($this->userrolesshortnames !== null) {
            return $this->userrolesshortnames;
        }

        $this->userrolesshortnames = [];
        if (!$this->userid) {
            return $this->userrolesshortnames;
        }

        $collected = [];

        try {
            $systemcontext = context_system::instance();
            $assignments = get_user_roles($systemcontext, $this->userid, false);
            foreach ($assignments as $assignment) {
                $shortname = $assignment->shortname ?? '';
                if ($shortname === '') {
                    continue;
                }
                $normalized = core_text::strtolower(trim($shortname));
                if ($normalized === '') {
                    continue;
                }
                $collected[$normalized] = $normalized;
            }
        } catch (\Throwable $e) {
            // Ignore failures retrieving system level roles.
        }

        $checkedcourseids = [];
        foreach ($this->get_courses() as $course) {
            $checkedcourseids[$course->id] = true;
            try {
                $coursecontext = context_course::instance($course->id);
                $assignments = get_user_roles($coursecontext, $this->userid, false);
                foreach ($assignments as $assignment) {
                    $shortname = $assignment->shortname ?? '';
                    if ($shortname === '') {
                        continue;
                    }
                    $normalized = core_text::strtolower(trim($shortname));
                    if ($normalized === '') {
                        continue;
                    }
                    $collected[$normalized] = $normalized;
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if ($this->courseid && !isset($checkedcourseids[$this->courseid])) {
            try {
                $coursecontext = context_course::instance($this->courseid);
                $assignments = get_user_roles($coursecontext, $this->userid, false);
                foreach ($assignments as $assignment) {
                    $shortname = $assignment->shortname ?? '';
                    if ($shortname === '') {
                        continue;
                    }
                    $normalized = core_text::strtolower(trim($shortname));
                    if ($normalized === '') {
                        continue;
                    }
                    $collected[$normalized] = $normalized;
                }
            } catch (\Throwable $e) {
                // Ignore lookup failures for the focus course.
            }
        }

        $this->userrolesshortnames = array_values($collected);
        return $this->userrolesshortnames;
    }
}
