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
 * Helper to build progress payload for the course index drawer.
 *
 * The logic reuses the computation patterns from theme_remui and
 * course/format/remuiformat so that the course and section percentage
 * calculations remain consistent with those implementations.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\local\courseindex;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use core_completion\progress;
use course_modinfo;
use moodle_url;
use stdClass;

/**
 * Progress helper reused by the AJAX service.
 */
class progress_helper {
    /**
     * Build the data structure consumed by the JS module.
     *
     * @param stdClass $course Course record.
     * @param int $userid User id whose progress should be returned.
     * @return array
     */
    public static function build_course_payload(stdClass $course, int $userid): array {
        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            return [
                'courseid' => $course->id,
                'percentage' => 0,
                'hascompletion' => false,
                'completedcount' => 0,
                'activitycount' => 0,
                'activitylist' => [],
                'activitysummary' => '',
                'progresscolor' => 'bg-secondary',
                'sections' => [],
                'statelabels' => self::get_state_labels(),
            ];
        }

        $percentage = progress::get_course_progress_percentage($course, $userid);
        if ($percentage === null) {
            $percentage = 0;
        } else {
            $percentage = floor($percentage);
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $activitysummary = self::collect_activity_totals($course, $completion, $modinfo, $userid);

        $sections = [];
        foreach ($modinfo->get_section_info_all() as $section) {
            // Skip orphaned sections or ones the user cannot see.
            if (!$section) {
                continue;
            }
            if (!$section->uservisible) {
                continue;
            }
            // Section zero can be hidden; honour that flag.
            if ($section->section == 0 && !$section->visible) {
                continue;
            }

            $sectioninfo = self::get_section_progress($course, $section, $completion, $modinfo, $userid);
            $sectionurl = new moodle_url('/course/view.php', ['id' => $course->id, 'section' => $section->section]);

            $sections[] = [
                'id' => $section->id,
                'number' => $section->section,
                'name' => get_section_name($course, $section),
                'visible' => (bool)$section->visible,
                'sectionurl' => $sectionurl,
                'progressinfo' => $sectioninfo,
                'activities' => self::get_section_activities($section, $modinfo, $completion, $userid),
            ];
        }

        return [
            'courseid' => $course->id,
            'percentage' => $percentage,
            'hascompletion' => true,
            'completedcount' => $activitysummary['completed'],
            'activitycount' => $activitysummary['total'],
            'activitylist' => $activitysummary['activitylist'],
            'activitysummary' => $activitysummary['summary'],
            'progresscolor' => self::get_progress_colour($percentage),
            'sections' => $sections,
            'statelabels' => self::get_state_labels(),
        ];
    }

    /**
     * Collect activity counts based on completion info.
     *
     * Logic adapted from format_remuiformat\course_format_data_common_trait.
     *
     * @param stdClass $course Course record.
     * @param completion_info $completion Completion helper.
     * @param course_modinfo $modinfo Fast modinfo instance.
     * @param int $userid User id.
     * @return array
     */
    private static function collect_activity_totals(stdClass $course, completion_info $completion,
            course_modinfo $modinfo, int $userid): array {
        $activitycount = 0;
        $completedcount = 0;
        $activitytypes = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                continue;
            }

            $displayname = $cm->modplural ?? $cm->modfullname ?? $cm->name;
            if (!isset($activitytypes[$displayname])) {
                $activitytypes[$displayname] = 0;
            }
            $activitytypes[$displayname]++;

            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $activitycount++;
            $data = $completion->get_data($cm, true, $userid);
            if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                $completedcount++;
            }
        }

        $activitylist = [];
        foreach ($activitytypes as $name => $count) {
            $activitylist[] = $count . ' ' . $name;
        }

        $summary = '';
        if ($activitycount > 0) {
            $summary = get_string('courseprogressactivitysummary', 'theme_compecer', [
                'completed' => $completedcount,
                'total' => $activitycount,
            ]);
        }

        return [
            'total' => $activitycount,
            'completed' => $completedcount,
            'activitylist' => $activitylist,
            'summary' => $summary,
        ];
    }

    /**
     * Build section progress info replicating format_remuiformat behaviour.
     *
     * @param stdClass $course Course record.
     * @param \section_info $section Section info.
     * @param completion_info $completion Completion helper.
     * @param course_modinfo $modinfo Fast modinfo instance.
     * @param int $userid User id.
     * @return array
     */
    private static function get_section_progress(stdClass $course, \section_info $section,
            completion_info $completion, course_modinfo $modinfo, int $userid): array {
        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();

        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                    continue;
                }

                if ($cm->modname === 'label') {
                    continue;
                }

                if ($cancomplete && $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                    $total++;
                    $data = $completion->get_data($cm, true, $userid);
                    if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        $percentage = ($total > 0) ? round(($complete / $total) * 100, 0) : 0;
        $summary = '';
        if ($total > 0) {
            $summary = get_string('sectionprogresssummary', 'theme_compecer', [
                'completed' => $complete,
                'total' => $total,
            ]);
        }

        return [
            'percentage' => $percentage,
            'completed' => $complete,
            'total' => $total,
            'summary' => $summary,
            'progresscolor' => self::get_progress_colour($percentage),
        ];
    }

    /**
     * Return activities for the given section with completion state.
     * Logic adapted from theme_remui\coursehandler::get_section_module_info().
     *
     * @param \section_info $section Section info.
     * @param course_modinfo $modinfo Fast modinfo instance.
     * @param completion_info $completion Completion helper.
     * @param int $userid User id.
     * @return array
     */
    private static function get_section_activities(\section_info $section, course_modinfo $modinfo,
            completion_info $completion, int $userid): array {
        $activities = [];

        if (empty($modinfo->sections[$section->section])) {
            return $activities;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                continue;
            }

            $state = self::resolve_activity_state($cm, $completion, $userid);
            $activities[] = [
                'id' => $cm->id,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'url' => $cm->url ? $cm->url->out(false) : '',
                'state' => $state,
            ];
        }

        return $activities;
    }

    /**
     * Resolve the textual completion state for an activity.
     *
     * @param \cm_info $cm Course module info.
     * @param completion_info $completion Completion helper.
     * @param int $userid User id.
     * @return string
     */
    private static function resolve_activity_state(\cm_info $cm, completion_info $completion, int $userid): string {
        if (!isloggedin() || isguestuser()) {
            return 'notstarted';
        }

        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            return 'notracking';
        }

        $data = $completion->get_data($cm, true, $userid);
        if ($data->completionstate == COMPLETION_COMPLETE || $data->completionstate == COMPLETION_COMPLETE_PASS) {
            return 'completed';
        }

        if ($data->timemodified > 0) {
            return 'inprogress';
        }

        return 'notstarted';
    }

    /**
     * Map progress percentage to a CSS class. Matches the behaviour used in
     * theme_remui and format_remuiformat so the same colour palette is applied.
     *
     * @param float $percentage Progress percentage.
     * @return string
     */
    private static function get_progress_colour(float $percentage): string {
        if ($percentage < 30) {
            return 'bg-danger';
        } else if ($percentage < 70) {
            return 'bg-warning';
        }
        return 'bg-success';
    }

    /**
     * Return translated state labels for the JS module.
     *
     * @return array
     */
    private static function get_state_labels(): array {
        return [
            'notstarted' => get_string('activitystate_notstarted', 'theme_compecer'),
            'inprogress' => get_string('activitystate_inprogress', 'theme_compecer'),
            'completed' => get_string('activitystate_completed', 'theme_compecer'),
            'notracking' => get_string('activitystate_notracking', 'theme_compecer'),
        ];
    }
}
