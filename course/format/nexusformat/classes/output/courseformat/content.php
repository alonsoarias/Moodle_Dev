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

        // Get base data from parent.
        $data = parent::export_for_template($output);

        // Add Nexus-specific data.
        $data->nexusformat = true;
        $data->courseid = $course->id;
        $data->editing = $PAGE->user_is_editing();

        // Get progress information.
        $data->progress = $this->get_progress_data($course, $modinfo);

        // Get sections data for sidebar.
        $data->sidebarunits = $this->get_sidebar_units($modinfo, $output);

        // Get first activity URL for initial load.
        $data->initialactivityurl = $this->get_first_activity_url($modinfo);

        // Placeholder text.
        $data->selectactivitytext = get_string('select_activity', 'format_nexusformat');

        // Strings for JavaScript.
        $data->strings = [
            'loading' => get_string('loading', 'format_nexusformat'),
            'error_loading' => get_string('error_loading', 'format_nexusformat'),
        ];

        return $data;
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

        $completion = new completion_info($course);
        if (!$completion->is_enabled()) {
            $progress->enabled = false;
            return $progress;
        }

        $progress->enabled = true;
        $cms = $modinfo->get_cms();

        foreach ($cms as $cm) {
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

        if ($progress->total > 0) {
            $progress->percentage = round(($progress->completed / $progress->total) * 100);
        }

        $progress->displaytext = get_string('progress_completed', 'format_nexusformat', $progress->percentage);

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
        $units = [];

        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            // Skip section 0 (general section) in the sidebar.
            if ($section->section == 0) {
                continue;
            }

            if (!$format->is_section_visible($section)) {
                continue;
            }

            $unit = new stdClass();
            $unit->id = $section->id;
            $unit->num = $section->section;
            $unit->name = $format->get_section_name($section);
            $unit->expanded = ($section->section == 1); // First unit expanded by default.
            $unit->lessons = [];

            // Get activities in this section.
            if (!empty($modinfo->sections[$section->section])) {
                $lessonnum = 1;
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);

                    if (!$cm->uservisible || $cm->is_stealth()) {
                        continue;
                    }

                    $lesson = new stdClass();
                    $lesson->id = $cm->id;
                    $lesson->num = $section->section . '.' . $lessonnum;
                    $lesson->name = $cm->get_formatted_name();
                    $lesson->url = $cm->url ? $cm->url->out(false) : '';
                    $lesson->modname = $cm->modname;
                    $lesson->icon = $cm->get_icon_url()->out(false);
                    $lesson->active = false;

                    // Get completion status.
                    $lesson->completed = false;
                    if ($completion->is_enabled() && $cm->completion != COMPLETION_TRACKING_NONE) {
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

            $units[] = $unit;
        }

        return $units;
    }

    /**
     * Get the URL of the first activity in the course.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @return string|null URL of first activity or null
     */
    protected function get_first_activity_url(\course_modinfo $modinfo): ?string {
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    if ($cm->uservisible && !$cm->is_stealth() && $cm->url) {
                        return $cm->url->out(false);
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
