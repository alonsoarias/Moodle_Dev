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

namespace theme_compecer\local\courseprogress;

use cm_info;
use completion_info;
use context_course;
use section_info;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/completionlib.php');

/**
 * Aggregates completion progress information for the course index drawer.
 *
 * @package     theme_compecer
 * @copyright   2024 IngeWeb
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider {
    /**
     * Completion state identifiers.
     */
    private const STATUS_NOTSTARTED = 'notstarted';
    private const STATUS_INPROGRESS = 'inprogress';
    private const STATUS_COMPLETE = 'complete';
    private const STATUS_FAILED = 'failed';
    private const STATUS_NOTRACKED = 'nottracked';

    /**
     * Build a course progress data set for the given course and user.
     *
     * @param int $courseid Course identifier.
     * @param int|null $userid User identifier, defaults to current user.
     * @return array<string, mixed> Normalised progress data ready for export.
     */
    public static function for_course(int $courseid, ?int $userid = null): array {
        global $USER;

        $userid = $userid ?? $USER->id;
        $course = get_course($courseid);
        $context = context_course::instance($courseid);
        require_capability('moodle/course:view', $context, $userid, true, 'nopermissions');

        $completioninfo = new completion_info($course);
        $istracked = $completioninfo->is_tracked_user($userid);
        $coursecompletionenabled = $completioninfo->is_enabled();

        // If completion is disabled or the user is not tracked, return an empty payload.
        if (!$coursecompletionenabled || !$istracked) {
            return [
                'completionenabled' => false,
                'course' => self::empty_course_payload(),
                'sections' => [],
                'cms' => [],
            ];
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $sections = [];
        $cms = [];
        $coursecounts = self::initial_counters();

        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section->uservisible) {
                continue;
            }

            $sectioncounts = self::initial_counters();

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);

                    if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                        continue;
                    }

                    // Skip delegated sections to avoid recursive loops.
                    if ($cm->get_delegated_section_info()) {
                        continue;
                    }

                    $cmstatus = self::classify_cm($completioninfo, $cm, $userid);
                    $sectioncounts[$cmstatus['counter']]++;
                    if ($cmstatus['counter'] !== 'notracked') {
                        $sectioncounts['tracked']++;
                        $coursecounts[$cmstatus['counter']]++;
                        $coursecounts['tracked']++;
                    }

                    $cms[] = [
                        'id' => $cmid,
                        'status' => $cmstatus['status'],
                        'label' => $cmstatus['label'],
                    ];
                }
            }

            $sections[] = self::prepare_section_payload($course, $section, $sectioncounts);
        }

        $coursepayload = self::prepare_course_payload($course, $coursecounts, count($sections));

        return [
            'completionenabled' => true,
            'course' => $coursepayload,
            'sections' => $sections,
            'cms' => $cms,
        ];
    }

    /**
     * Return the initial counters for progress aggregation.
     *
     * @return array<string, int>
     */
    private static function initial_counters(): array {
        return [
            self::STATUS_NOTSTARTED => 0,
            self::STATUS_INPROGRESS => 0,
            self::STATUS_COMPLETE => 0,
            self::STATUS_FAILED => 0,
            self::STATUS_NOTRACKED => 0,
            'tracked' => 0,
        ];
    }

    /**
     * Build an empty course payload for disabled completion scenarios.
     *
     * @return array<string, mixed>
     */
    private static function empty_course_payload(): array {
        return [
            'percent' => 0,
            'completed' => 0,
            'failed' => 0,
            'inprogress' => 0,
            'notstarted' => 0,
            'total' => 0,
            'fraction' => '',
            'summary' => '',
            'a11y' => '',
        ];
    }

    /**
     * Determine the completion classification for a course module.
     *
     * @param completion_info $completioninfo Completion info handler.
     * @param cm_info $cm Course module information.
     * @param int $userid User identifier.
     * @return array<string, string> Classification result containing counter key, status and label.
     */
    private static function classify_cm(completion_info $completioninfo, cm_info $cm, int $userid): array {
        $status = self::STATUS_NOTRACKED;
        $counter = self::STATUS_NOTRACKED;
        $label = get_string('status_notracked', 'theme_compecer');

        if (!$completioninfo->is_enabled($cm)) {
            return [
                'status' => $status,
                'counter' => $counter,
                'label' => $label,
            ];
        }

        $data = $completioninfo->get_data($cm, true, $userid);
        $state = isset($data->completionstate) ? (int)$data->completionstate : COMPLETION_INCOMPLETE;

        if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
            $status = self::STATUS_COMPLETE;
            $counter = self::STATUS_COMPLETE;
            $label = get_string('status_complete', 'theme_compecer');
        } else if (in_array($state, [COMPLETION_COMPLETE_FAIL, COMPLETION_COMPLETE_FAIL_HIDDEN], true)) {
            $status = self::STATUS_FAILED;
            $counter = self::STATUS_FAILED;
            $label = get_string('status_failed', 'theme_compecer');
        } else {
            $started = self::has_started($data);
            $status = $started ? self::STATUS_INPROGRESS : self::STATUS_NOTSTARTED;
            $counter = $status;
            $label = get_string('status_' . $status, 'theme_compecer');
        }

        return [
            'status' => $status,
            'counter' => $counter,
            'label' => $label,
        ];
    }

    /**
     * Decide whether a user has started working on a course module.
     *
     * @param stdClass $data Raw completion data.
     * @return bool
     */
    private static function has_started(stdClass $data): bool {
        $viewed = !empty($data->viewed) && ((int)$data->viewed === COMPLETION_VIEWED);
        $timemodified = !empty($data->timemodified) && (int)$data->timemodified > 0;
        $overridden = !empty($data->overrideby);

        if ($viewed || $timemodified || $overridden) {
            return true;
        }

        if (!empty($data->customcompletion)) {
            foreach ($data->customcompletion as $value) {
                if ((int)$value !== COMPLETION_INCOMPLETE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Prepare the course level payload.
     *
     * @param stdClass $course Course record.
     * @param array<string, int> $counts Aggregated counters.
     * @param int $sectioncount Number of visible sections.
     * @return array<string, mixed>
     */
    private static function prepare_course_payload(stdClass $course, array $counts, int $sectioncount): array {
        $percent = self::percent($counts[self::STATUS_COMPLETE], $counts['tracked']);

        $summary = get_string('courseprogresssummary', 'theme_compecer', (object) [
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'total' => $counts['tracked'],
        ]);
        if ($counts['tracked'] <= 0) {
            $summary = get_string('progressnottrackedshort', 'theme_compecer');
        }

        $a11y = get_string('courseprogressaria', 'theme_compecer', (object) [
            'course' => format_string($course->fullname, true, ["context" => context_course::instance($course->id)]),
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'total' => $counts['tracked'],
            'sections' => $sectioncount,
        ]);

        return [
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'failed' => $counts[self::STATUS_FAILED],
            'inprogress' => $counts[self::STATUS_INPROGRESS],
            'notstarted' => $counts[self::STATUS_NOTSTARTED],
            'total' => $counts['tracked'],
            'fraction' => self::fraction_label($counts[self::STATUS_COMPLETE], $counts['tracked']),
            'summary' => $summary,
            'a11y' => $a11y,
        ];
    }

    /**
     * Prepare the section payload for consumption in JS.
     *
     * @param stdClass $course Course record.
     * @param section_info $section Section metadata.
     * @param array<string, int> $counts Section counters.
     * @return array<string, mixed>
     */
    private static function prepare_section_payload(stdClass $course, section_info $section, array $counts): array {
        $context = context_course::instance($course->id);
        $percent = self::percent($counts[self::STATUS_COMPLETE], $counts['tracked']);

        $summary = get_string('sectionprogresssummary', 'theme_compecer', (object) [
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'total' => $counts['tracked'],
        ]);
        if ($counts['tracked'] <= 0) {
            $summary = get_string('progressnottrackedshort', 'theme_compecer');
        }

        $a11y = get_string('sectionprogressaria', 'theme_compecer', (object) [
            'section' => format_string($section->name ?? $section->section, true, ['context' => $context]),
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'total' => $counts['tracked'],
        ]);

        return [
            'id' => $section->id,
            'percent' => $percent,
            'completed' => $counts[self::STATUS_COMPLETE],
            'inprogress' => $counts[self::STATUS_INPROGRESS],
            'notstarted' => $counts[self::STATUS_NOTSTARTED],
            'failed' => $counts[self::STATUS_FAILED],
            'total' => $counts['tracked'],
            'fraction' => self::fraction_label($counts[self::STATUS_COMPLETE], $counts['tracked']),
            'summary' => $summary,
            'a11y' => $a11y,
        ];
    }

    /**
     * Calculate a safe percentage value.
     *
     * @param int $completed Number of completed activities.
     * @param int $total Total trackable activities.
     * @return int
     */
    private static function percent(int $completed, int $total): int {
        if ($total <= 0) {
            return 0;
        }

        return (int)round(($completed / $total) * 100);
    }

    /**
     * Build a short fraction label for visual counters.
     *
     * @param int $completed Number of completed activities.
     * @param int $total Total trackable activities.
     * @return string
     */
    private static function fraction_label(int $completed, int $total): string {
        if ($total <= 0) {
            return get_string('progressnottrackedshort', 'theme_compecer');
        }

        return get_string('progressfraction', 'theme_compecer', (object) [
            'completed' => $completed,
            'total' => $total,
        ]);
    }
}
