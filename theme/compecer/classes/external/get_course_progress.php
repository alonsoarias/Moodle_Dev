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
 * External function to get course progress data with section and activity details.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use core_completion\progress;
use context_course;
use completion_info;

/**
 * External function to get course progress data with enhanced section and activity details.
 */
class get_course_progress extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    /**
     * Get course progress data including sections and activities.
     *
     * @param int $courseid Course ID
     * @return array Complete course progress data with sections and activities
     */
    public static function execute($courseid) {
        global $USER;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid
        ]);

        // Validate context.
        $context = context_course::instance($params['courseid']);
        self::validate_context($context);

        // Check if user is enrolled in the course.
        require_capability('moodle/course:view', $context);

        // Get course object.
        $course = get_course($params['courseid']);

        // Get course progress data.
        $courseprogress = self::get_course_progress_data($course);

        // Get sections progress data.
        $sections = self::get_sections_progress_data($course);

        return [
            'courseid' => $course->id,
            'coursename' => $course->fullname,
            'courseprogress' => $courseprogress,
            'sections' => $sections
        ];
    }

    /**
     * Get overall course progress data.
     *
     * @param stdClass $course Course object
     * @return array Course progress data
     */
    private static function get_course_progress_data($course) {
        global $USER;

        $completion = new completion_info($course);

        // Initialize with defaults.
        $data = [
            'percentage' => 0,
            'completedcount' => 0,
            'activitycount' => 0,
            'hascompletion' => false,
            'progresscolor' => 'bg-danger',
            'progresstext' => '',
            'showactivitylist' => true,
            'activitylist' => []
        ];

        // Check if completion is enabled.
        if (!$completion->is_enabled()) {
            return $data;
        }

        $data['hascompletion'] = true;

        // Get percentage using Moodle core API.
        $percentage = progress::get_course_progress_percentage($course, $USER->id);
        $data['percentage'] = !is_null($percentage) ? floor($percentage) : 0;

        // Get detailed activity information.
        $modinfo = get_fast_modinfo($course);
        $activitycount = 0;
        $completedcount = 0;
        $activitytypes = [];

        foreach ($modinfo->get_cms() as $cm) {
            // Only count visible activities on course page.
            if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                continue;
            }

            // Exclude labels.
            if ($cm->modname == 'label') {
                continue;
            }

            // Check if completion tracking is enabled.
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $activitycount++;

            // Count by type.
            $modname = $cm->modfullname;
            if (!isset($activitytypes[$modname])) {
                $activitytypes[$modname] = 0;
            }
            $activitytypes[$modname]++;

            // Check completion status.
            $cmdata = $completion->get_data($cm, false, $USER->id);
            if ($cmdata->completionstate == COMPLETION_COMPLETE ||
                $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
                $completedcount++;
            }
        }

        $data['activitycount'] = $activitycount;
        $data['completedcount'] = $completedcount;

        // Progress text.
        $data['progresstext'] = sprintf(
            '%d de %d actividades (%d%%)',
            $completedcount,
            $activitycount,
            $data['percentage']
        );

        // Color based on percentage.
        $data['progresscolor'] = self::get_progress_color($data['percentage']);

        // Activity list by type.
        foreach ($activitytypes as $type => $count) {
            $data['activitylist'][] = "$count $type";
        }

        return $data;
    }

    /**
     * Get sections with progress data.
     *
     * @param stdClass $course Course object
     * @return array Array of sections with progress info
     */
    private static function get_sections_progress_data($course) {
        global $USER, $PAGE;

        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();
        $currentsection = $PAGE->context->get_course_context(false) ? 0 : 0;

        $result = [];

        foreach ($sections as $section) {
            // Basic section information.
            $sectiondata = [
                'id' => $section->id,
                'number' => $section->section,
                'title' => get_section_name($course, $section),
                'sectionurl' => course_get_url($course, $section->section)->out(),
                'current' => ($section->section == $currentsection),
                'visible' => (bool)$section->visible,
                'indexcollapsed' => !$section->uservisible,
                'hasrestrictions' => !empty($section->availability),
                'uniqid' => uniqid()
            ];

            // Calculate section progress.
            $progressinfo = self::get_section_module_info($course, $section, $completion);
            $sectiondata['progressinfo'] = $progressinfo;

            // Get activities with completion state.
            $sectiondata['cms'] = self::get_section_activities($course, $section, $completion, $modinfo);

            $result[] = $sectiondata;
        }

        return $result;
    }

    /**
     * Calculate progress for a specific section.
     *
     * Based on format_remuiformat logic.
     *
     * @param stdClass $course Course object
     * @param section_info $section Section info object
     * @param completion_info $completion Completion info object
     * @return array Section progress data
     */
    private static function get_section_module_info($course, $section, $completion) {
        global $USER;

        $total = 0;
        $completed = 0;
        $cancomplete = isloggedin() && !isguestuser();

        $modinfo = get_fast_modinfo($course);

        // Iterate through section modules.
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                // Check visibility.
                if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                    continue;
                }

                // Exclude labels.
                if ($cm->modname == 'label') {
                    continue;
                }

                // Check completion tracking.
                if ($cancomplete && $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {
                    $total++;

                    $cmdata = $completion->get_data($cm, false, $USER->id);
                    if ($cmdata->completionstate == COMPLETION_COMPLETE ||
                        $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed++;
                    }
                }
            }
        }

        // Calculate percentage.
        $percentage = ($total > 0) ? round(($completed / $total) * 100, 0) : 0;

        return [
            'percentage' => (int)$percentage,
            'completed' => $completed,
            'total' => $total,
            'progresstext' => "$completed/$total",
            'progresscolor' => self::get_progress_color($percentage),
            'showminibar' => false
        ];
    }

    /**
     * Get activities for a section with completion states.
     *
     * @param stdClass $course Course object
     * @param section_info $section Section info object
     * @param completion_info $completion Completion info object
     * @param course_modinfo $modinfo Course modinfo object
     * @return array Array of activities with completion data
     */
    private static function get_section_activities($course, $section, $completion, $modinfo) {
        global $USER;

        $activities = [];

        if (empty($modinfo->sections[$section->section])) {
            return $activities;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Basic activity data.
            $activitydata = [
                'id' => $cm->id,
                'name' => $cm->name,
                'url' => $cm->url ? $cm->url->out() : '',
                'modname' => $cm->modname,
                'visible' => (bool)$cm->visible,
                'uservisible' => (bool)$cm->uservisible,
                'isactive' => false, // TODO: Detect current activity.
                'hascmrestrictions' => !empty($cm->availability),
                'uniqid' => uniqid()
            ];

            // Get completion state.
            $state = self::get_activity_completion_state($cm, $completion);
            $activitydata = array_merge($activitydata, $state);

            $activities[] = $activitydata;
        }

        return $activities;
    }

    /**
     * Determine completion state of an activity.
     *
     * @param cm_info $cm Course module info
     * @param completion_info $completion Completion info object
     * @return array Completion state data with icon, color, and label
     */
    private static function get_activity_completion_state($cm, $completion) {
        global $USER;

        // No completion tracking.
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            return [
                'completionstate' => '',
                'completionicon' => '',
                'completioncolor' => '',
                'completionlabel' => ''
            ];
        }

        // User cannot complete.
        if (!isloggedin() || isguestuser()) {
            return [
                'completionstate' => 'notstarted',
                'completionicon' => '○',
                'completioncolor' => 'text-muted',
                'completionlabel' => get_string('notstarted', 'theme_compecer')
            ];
        }

        // Get completion data.
        $cmdata = $completion->get_data($cm, false, $USER->id);

        // Completed.
        if ($cmdata->completionstate == COMPLETION_COMPLETE ||
            $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
            return [
                'completionstate' => 'completed',
                'completionicon' => '✓',
                'completioncolor' => 'text-success',
                'completionlabel' => get_string('completed', 'core_completion')
            ];
        }

        // In progress (has interacted but not completed).
        if ($cmdata->timemodified > 0) {
            return [
                'completionstate' => 'inprogress',
                'completionicon' => '◐',
                'completioncolor' => 'text-warning',
                'completionlabel' => get_string('inprogress', 'theme_compecer')
            ];
        }

        // Not started.
        return [
            'completionstate' => 'notstarted',
            'completionicon' => '○',
            'completioncolor' => 'text-muted',
            'completionlabel' => get_string('notstarted', 'theme_compecer')
        ];
    }

    /**
     * Get progress bar color class based on percentage.
     *
     * @param float $percentage Progress percentage 0-100
     * @return string Bootstrap color class
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
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),

            'courseprogress' => new external_single_structure([
                'percentage' => new external_value(PARAM_FLOAT, 'Progress percentage'),
                'completedcount' => new external_value(PARAM_INT, 'Completed activities count'),
                'activitycount' => new external_value(PARAM_INT, 'Total activities count'),
                'hascompletion' => new external_value(PARAM_BOOL, 'Has completion enabled'),
                'progresscolor' => new external_value(PARAM_TEXT, 'Progress bar color class'),
                'progresstext' => new external_value(PARAM_TEXT, 'Progress text description'),
                'showactivitylist' => new external_value(PARAM_BOOL, 'Show activity list'),
                'activitylist' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Activity type and count')
                )
            ]),

            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'title' => new external_value(PARAM_TEXT, 'Section title'),
                    'sectionurl' => new external_value(PARAM_URL, 'Section URL'),
                    'current' => new external_value(PARAM_BOOL, 'Is current section'),
                    'visible' => new external_value(PARAM_BOOL, 'Is visible'),
                    'indexcollapsed' => new external_value(PARAM_BOOL, 'Is collapsed'),
                    'hasrestrictions' => new external_value(PARAM_BOOL, 'Has restrictions'),
                    'uniqid' => new external_value(PARAM_TEXT, 'Unique ID'),

                    'progressinfo' => new external_single_structure([
                        'percentage' => new external_value(PARAM_INT, 'Section progress percentage'),
                        'completed' => new external_value(PARAM_INT, 'Completed activities'),
                        'total' => new external_value(PARAM_INT, 'Total activities'),
                        'progresstext' => new external_value(PARAM_TEXT, 'Progress text'),
                        'progresscolor' => new external_value(PARAM_TEXT, 'Progress color class'),
                        'showminibar' => new external_value(PARAM_BOOL, 'Show mini progress bar')
                    ]),

                    'cms' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'CM ID'),
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'url' => new external_value(PARAM_URL, 'Activity URL'),
                            'modname' => new external_value(PARAM_TEXT, 'Module name'),
                            'visible' => new external_value(PARAM_BOOL, 'Is visible'),
                            'uservisible' => new external_value(PARAM_BOOL, 'Is visible to user'),
                            'isactive' => new external_value(PARAM_BOOL, 'Is currently active'),
                            'hascmrestrictions' => new external_value(PARAM_BOOL, 'Has restrictions'),
                            'uniqid' => new external_value(PARAM_TEXT, 'Unique ID'),
                            'completionstate' => new external_value(PARAM_TEXT, 'Completion state'),
                            'completionicon' => new external_value(PARAM_TEXT, 'Completion icon'),
                            'completioncolor' => new external_value(PARAM_TEXT, 'Completion color'),
                            'completionlabel' => new external_value(PARAM_TEXT, 'Completion label')
                        ])
                    )
                ])
            )
        ]);
    }
}
