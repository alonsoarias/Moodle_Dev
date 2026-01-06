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
 * Contains the main content output class for Nexus Format.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_nexusformat\output\courseformat;

use core_courseformat\output\local\content as content_base;
use core_courseformat\base as course_format;
use stdClass;
use renderer_base;
use completion_info;
use context_course;

/**
 * Main content output class for Nexus Format.
 *
 * This class handles the two-column layout with the activity content
 * on the left and the navigation sidebar on the right.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE, $USER;

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $format->get_modinfo();
        $context = context_course::instance($course->id);

        // Get base data from parent.
        $data = parent::export_for_template($output);

        // Add Nexus-specific data.
        $data->nexusformat = true;
        $data->courseid = $course->id;

        // Check editing mode - use integer for Mustache compatibility.
        $isediting = $PAGE->user_is_editing();
        $data->editing = $isediting ? 1 : 0;
        $data->notediting = $isediting ? 0 : 1;

        // Get progress information.
        $data->progress = $this->get_progress_data($course, $modinfo);

        // Get sections data for sidebar.
        $data->sidebarunits = $this->get_sidebar_units($modinfo, $output);

        // Count total activities.
        $data->totalactivities = $this->count_activities($modinfo);

        // Get first activity URL for initial display.
        $firstactivity = $this->get_first_activity($modinfo);
        $data->hasfirstactivity = !empty($firstactivity);
        if ($firstactivity) {
            $data->firstactivity = $firstactivity;
        }

        // Placeholder text.
        $data->selectactivitytext = get_string('select_activity', 'format_nexusformat');

        return $data;
    }

    /**
     * Count total visible activities in the course.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @return int Total count
     */
    protected function count_activities(\course_modinfo $modinfo): int {
        $count = 0;
        foreach ($modinfo->get_cms() as $cm) {
            if ($cm->uservisible && !$cm->is_stealth() && $cm->url) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get progress data for the course.
     *
     * @param stdClass $course The course object
     * @param \course_modinfo $modinfo The course modinfo
     * @return stdClass Progress data
     */
    protected function get_progress_data(stdClass $course, \course_modinfo $modinfo): stdClass {
        global $USER;

        $progress = new stdClass();
        $progress->percentage = 0;
        $progress->completed = 0;
        $progress->total = 0;
        $progress->enabled = false;
        $progress->hasactivities = false;

        $completion = new completion_info($course);

        // Check if completion is enabled for the course.
        if (!$completion->is_enabled()) {
            // Completion not enabled - count activities for display purposes.
            $cms = $modinfo->get_cms();
            foreach ($cms as $cm) {
                if ($cm->uservisible && !$cm->is_stealth() && $cm->url) {
                    $progress->total++;
                }
            }
            $progress->hasactivities = ($progress->total > 0);
            $progress->displaytext = get_string('activities_completed', 'format_nexusformat', [
                'completed' => 0,
                'total' => $progress->total
            ]);
            return $progress;
        }

        $progress->enabled = true;
        $cms = $modinfo->get_cms();

        foreach ($cms as $cm) {
            // Only count activities with completion tracking.
            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                continue;
            }
            if (!$cm->uservisible) {
                continue;
            }

            $progress->total++;
            $completiondata = $completion->get_data($cm, true, $USER->id);
            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $progress->completed++;
            }
        }

        $progress->hasactivities = ($progress->total > 0);

        if ($progress->total > 0) {
            $progress->percentage = round(($progress->completed / $progress->total) * 100);
        }

        $progress->displaytext = get_string('activities_completed', 'format_nexusformat', [
            'completed' => $progress->completed,
            'total' => $progress->total
        ]);

        return $progress;
    }

    /**
     * Get sidebar units data.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @param renderer_base $output The renderer
     * @return array Array of unit data for sidebar
     */
    protected function get_sidebar_units(\course_modinfo $modinfo, renderer_base $output): array {
        global $USER;

        $format = $this->format;
        $course = $format->get_course();
        $completion = new completion_info($course);
        $completionenabled = $completion->is_enabled();
        $units = [];

        $sections = $modinfo->get_section_info_all();
        $unitindex = 0;

        foreach ($sections as $section) {
            // Skip section 0 (general section) in the sidebar units list.
            if ($section->section == 0) {
                continue;
            }

            if (!$format->is_section_visible($section)) {
                continue;
            }

            $unitindex++;
            $unit = new stdClass();
            $unit->id = $section->id;
            $unit->num = $section->section;
            $unit->name = $format->get_section_name($section);
            $unit->expanded = ($unitindex == 1); // First unit expanded by default.
            $unit->lessons = [];

            // Get activities in this section.
            if (!empty($modinfo->sections[$section->section])) {
                $lessonnum = 1;
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);

                    if (!$cm->uservisible || $cm->is_stealth()) {
                        continue;
                    }

                    // Skip activities without URL (like labels).
                    if (!$cm->url) {
                        continue;
                    }

                    $lesson = new stdClass();
                    $lesson->id = $cm->id;
                    $lesson->num = $section->section . '.' . $lessonnum;
                    $lesson->name = $cm->get_formatted_name();
                    $lesson->url = $cm->url->out(false);
                    $lesson->modname = $cm->modname;
                    $lesson->icon = $cm->get_icon_url()->out(false);
                    $lesson->active = false;
                    $lesson->isfirst = ($unitindex == 1 && $lessonnum == 1);

                    // Get completion status.
                    $lesson->completed = false;
                    $lesson->hascompletion = false;
                    if ($completionenabled && $cm->completion != COMPLETION_TRACKING_NONE) {
                        $lesson->hascompletion = true;
                        $completiondata = $completion->get_data($cm, true, $USER->id);
                        $lesson->completed = ($completiondata->completionstate == COMPLETION_COMPLETE ||
                                             $completiondata->completionstate == COMPLETION_COMPLETE_PASS);
                    }

                    $unit->lessons[] = $lesson;
                    $lessonnum++;
                }
            }

            $unit->haslessons = !empty($unit->lessons);
            $unit->lessoncount = count($unit->lessons);

            if ($unit->haslessons) {
                $units[] = $unit;
            }
        }

        return $units;
    }

    /**
     * Get the first activity in the course.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @return stdClass|null First activity data or null
     */
    protected function get_first_activity(\course_modinfo $modinfo): ?stdClass {
        $format = $this->format;
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if (!$format->is_section_visible($section)) {
                continue;
            }

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if ($cm->uservisible && !$cm->is_stealth() && $cm->url) {
                        $activity = new stdClass();
                        $activity->id = $cm->id;
                        $activity->name = $cm->get_formatted_name();
                        $activity->url = $cm->url->out(false);
                        $activity->modname = $cm->modname;
                        $activity->icon = $cm->get_icon_url()->out(false);
                        return $activity;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get the template name for this content.
     *
     * @param renderer_base $renderer The renderer
     * @return string The template name
     */
    public function get_template_name(renderer_base $renderer): string {
        return 'format_nexusformat/local/content';
    }
}
