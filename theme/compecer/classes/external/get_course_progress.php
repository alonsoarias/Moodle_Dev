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
 * External service for getting course progress with section details.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use core_completion\progress;
use completion_info;
use context_course;
use stdClass;

/**
 * External service for getting course progress.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    /**
     * Get course progress with section details.
     *
     * @param int $courseid The course ID
     * @return array Course progress data
     */
    public static function execute($courseid) {
        global $USER, $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
        ]);

        $courseid = $params['courseid'];

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

        // Check capability.
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        // Initialize completion.
        $completion = new completion_info($course);

        // Check if completion is enabled.
        if (!$completion->is_enabled()) {
            return [
                'courseid' => $courseid,
                'percentage' => 0,
                'hascompletion' => false,
                'completedcount' => 0,
                'activitycount' => 0,
                'activitylist' => [],
                'progresscolor' => 'bg-secondary',
                'sections' => [],
            ];
        }

        // Get course progress percentage using Moodle API.
        $percentage = progress::get_course_progress_percentage($course, $USER->id);
        if (is_null($percentage)) {
            $percentage = 0;
        } else {
            $percentage = floor($percentage);
        }

        // Determine progress color.
        $progresscolor = self::get_progress_color($percentage);

        // Get fast modinfo.
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        // Count activities and get activity types.
        $activitycount = 0;
        $completedcount = 0;
        $activitytypes = [];

        foreach ($modinfo->get_cms() as $cm) {
            if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                continue;
            }

            // Count for activity types.
            $modname = get_string('modulename', $cm->modname);
            if (!isset($activitytypes[$modname])) {
                $activitytypes[$modname] = 0;
            }
            $activitytypes[$modname]++;

            // Count for completion.
            if ($completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                $activitycount++;
                $data = $completion->get_data($cm, false, $USER->id);
                if ($data->completionstate == COMPLETION_COMPLETE ||
                    $data->completionstate == COMPLETION_COMPLETE_PASS) {
                    $completedcount++;
                }
            }
        }

        // Build activity list.
        $activitylist = [];
        foreach ($activitytypes as $type => $count) {
            $activitylist[] = "$count $type";
        }

        // Get section progress information.
        $sectionsdata = [];
        foreach ($sections as $section) {
            // Skip section 0 if hidden.
            if ($section->section == 0 && !$section->visible) {
                continue;
            }

            $sectionprogress = self::get_section_module_info($course, $section, $completion, $modinfo);

            $sectiondata = [
                'id' => $section->id,
                'number' => $section->section,
                'name' => get_section_name($course, $section),
                'visible' => (bool)$section->visible,
                'sectionurl' => new \moodle_url('/course/view.php', [
                    'id' => $courseid,
                    'section' => $section->section,
                ]),
                'progressinfo' => [
                    'percentage' => $sectionprogress->percentage,
                    'completed' => $sectionprogress->completed,
                    'total' => $sectionprogress->total,
                    'progress' => $sectionprogress->progress,
                    'progresscolor' => self::get_progress_color($sectionprogress->percentage),
                ],
                'activities' => self::get_section_activities($section, $modinfo, $completion),
            ];

            $sectionsdata[] = $sectiondata;
        }

        return [
            'courseid' => $courseid,
            'percentage' => $percentage,
            'hascompletion' => true,
            'completedcount' => $completedcount,
            'activitycount' => $activitycount,
            'activitylist' => $activitylist,
            'progresscolor' => $progresscolor,
            'sections' => $sectionsdata,
        ];
    }

    /**
     * Get progress information for a section.
     *
     * @param stdClass $course Course object
     * @param stdClass $section Section info
     * @param completion_info $completion Completion info object
     * @param course_modinfo $modinfo Fast modinfo object
     * @return stdClass Section progress info
     */
    private static function get_section_module_info($course, $section, $completion, $modinfo) {
        global $USER;

        $total = 0;
        $complete = 0;
        $cancomplete = isloggedin() && !isguestuser();

        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $thismod = $modinfo->cms[$cmid];

                // Skip if not visible.
                if (!$thismod->uservisible) {
                    continue;
                }

                // Skip labels.
                if ($thismod->modname == 'label') {
                    continue;
                }

                // Skip if not visible on course page.
                if (!$thismod->is_visible_on_course_page()) {
                    continue;
                }

                // Check completion tracking.
                if ($cancomplete && $completion->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {
                    $total++;

                    $completiondata = $completion->get_data($thismod, true);
                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $complete++;
                    }
                }
            }
        }

        $percentage = ($total > 0) ? round(($complete / $total) * 100, 0) : 0;

        $pinfo = new stdClass();
        $pinfo->completed = $complete;
        $pinfo->total = $total;
        $pinfo->percentage = $percentage;
        $pinfo->progress = "$complete/$total";

        return $pinfo;
    }

    /**
     * Get activities for a section with their completion states.
     *
     * @param stdClass $section Section info
     * @param course_modinfo $modinfo Fast modinfo object
     * @param completion_info $completion Completion info object
     * @return array Activities data
     */
    private static function get_section_activities($section, $modinfo, $completion) {
        $activities = [];

        if (empty($modinfo->sections[$section->section])) {
            return $activities;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Skip if not visible.
            if (!$cm->uservisible) {
                continue;
            }

            // Skip if not visible on course page.
            if (!$cm->is_visible_on_course_page()) {
                continue;
            }

            $activitystate = self::get_activity_state($cm, $completion);

            $activities[] = [
                'id' => $cm->id,
                'name' => $cm->name,
                'modname' => $cm->modname,
                'url' => $cm->url ? $cm->url->out(false) : '',
                'state' => $activitystate,
            ];
        }

        return $activities;
    }

    /**
     * Get activity completion state.
     *
     * @param cm_info $mod Course module info
     * @param completion_info $completion Completion info object
     * @return string Activity state (notstarted|inprogress|completed|notracking)
     */
    private static function get_activity_state($mod, $completion) {
        global $USER;

        if (!isloggedin() || isguestuser()) {
            return 'notstarted';
        }

        if ($completion->is_enabled($mod) == COMPLETION_TRACKING_NONE) {
            return 'notracking';
        }

        $data = $completion->get_data($mod, true, $USER->id);

        if ($data->completionstate == COMPLETION_COMPLETE ||
            $data->completionstate == COMPLETION_COMPLETE_PASS) {
            return 'completed';
        }

        // Check if activity has been started (viewed or modified).
        if ($data->timemodified > 0) {
            return 'inprogress';
        }

        return 'notstarted';
    }

    /**
     * Get progress bar color based on percentage.
     *
     * @param float $percentage Progress percentage
     * @return string CSS class for color
     */
    private static function get_progress_color($percentage) {
        if ($percentage < 30) {
            return 'bg-danger';
        } else if ($percentage < 70) {
            return 'bg-warning';
        } else {
            return 'bg-success';
        }
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'percentage' => new external_value(PARAM_FLOAT, 'Overall completion percentage'),
            'hascompletion' => new external_value(PARAM_BOOL, 'Whether completion is enabled'),
            'completedcount' => new external_value(PARAM_INT, 'Number of completed activities'),
            'activitycount' => new external_value(PARAM_INT, 'Total number of activities'),
            'activitylist' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Activity type and count'),
                'List of activity types',
                VALUE_OPTIONAL
            ),
            'progresscolor' => new external_value(PARAM_TEXT, 'Progress bar color class'),
            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'name' => new external_value(PARAM_TEXT, 'Section name'),
                    'visible' => new external_value(PARAM_BOOL, 'Section visibility'),
                    'sectionurl' => new external_value(PARAM_URL, 'Section URL'),
                    'progressinfo' => new external_single_structure([
                        'percentage' => new external_value(PARAM_FLOAT, 'Section completion percentage'),
                        'completed' => new external_value(PARAM_INT, 'Completed activities in section'),
                        'total' => new external_value(PARAM_INT, 'Total activities in section'),
                        'progress' => new external_value(PARAM_TEXT, 'Progress string (e.g., "3/5")'),
                        'progresscolor' => new external_value(PARAM_TEXT, 'Progress color class'),
                    ], 'Section progress information'),
                    'activities' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'Activity ID'),
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'modname' => new external_value(PARAM_TEXT, 'Module name'),
                            'url' => new external_value(PARAM_URL, 'Activity URL'),
                            'state' => new external_value(PARAM_TEXT, 'Activity state'),
                        ]),
                        'Activities in section',
                        VALUE_OPTIONAL
                    ),
                ]),
                'Sections data',
                VALUE_OPTIONAL
            ),
        ]);
    }
}
