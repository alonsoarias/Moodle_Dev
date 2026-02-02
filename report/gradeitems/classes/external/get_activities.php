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
 * External function to get gradeable activities for a course.
 *
 * @package    report_gradeitems
 * @copyright  2026 Alonso Arias <soporte@orioncloud.com.co>
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @link       https://orioncloud.com.co
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_gradeitems\external;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_system;
use moodle_url;

/**
 * External function to get gradeable activities for a course.
 */
class get_activities extends external_api {

    /**
     * Define parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'activityvisibility' => new external_value(PARAM_RAW, 'Activity visibility filter', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Execute the function.
     *
     * @param int $courseid
     * @param string $activityvisibility
     * @return array
     */
    public static function execute(int $courseid, string $activityvisibility): array {
        global $DB;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), [
            'courseid' => $courseid,
            'activityvisibility' => $activityvisibility,
        ]);

        // Check capability.
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('report/gradeitems:view', $context);

        // Validate course exists.
        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);

        // Build visibility filter.
        $visibilityfilter = '';
        if ($params['activityvisibility'] === '1') {
            $visibilityfilter = ' AND cm.visible = 1';
        } else if ($params['activityvisibility'] === '0') {
            $visibilityfilter = ' AND cm.visible = 0';
        }

        // Main SQL query - Gradeable activities for this course.
        $sql = "SELECT
                    gi.id AS gradeitemid,
                    gi.itemname AS activityname,
                    gi.itemmodule AS moduletype,
                    gi.iteminstance,
                    cm.id AS cmid,
                    cm.visible AS activityvisible,
                    cm.section AS sectionid,
                    cs.name AS sectionname,
                    cs.section AS sectionnumber,
                    gi.gradetype,
                    gi.grademax,
                    gi.gradepass,
                    gi.aggregationcoef AS gradeweight,
                    gi.aggregationcoef2 AS gradeweight2,
                    (SELECT COUNT(gg.id)
                     FROM {grade_grades} gg
                     WHERE gg.itemid = gi.id
                       AND gg.finalgrade IS NOT NULL
                    ) AS gradecount,
                    (SELECT AVG(gg.finalgrade)
                     FROM {grade_grades} gg
                     WHERE gg.itemid = gi.id
                       AND gg.finalgrade IS NOT NULL
                    ) AS gradeaverage
                FROM {grade_items} gi
                JOIN {modules} m ON m.name = gi.itemmodule
                JOIN {course_modules} cm ON cm.course = gi.courseid
                                        AND cm.module = m.id
                                        AND cm.instance = gi.iteminstance
                LEFT JOIN {course_sections} cs ON cs.id = cm.section
               WHERE gi.courseid = :courseid
                 AND gi.itemtype = 'mod'
                 AND gi.gradetype > 0
                 AND cm.deletioninprogress = 0
                 $visibilityfilter
            ORDER BY cs.section, gi.sortorder";

        $records = $DB->get_records_sql($sql, ['courseid' => $params['courseid']]);

        // Format results.
        $activities = [];
        foreach ($records as $record) {
            // Get module display name.
            $modname = get_string('pluginname', 'mod_' . $record->moduletype);

            // Format grade type.
            $gradetypestr = self::format_grade_type($record->gradetype);

            // Format section.
            $section = $record->sectionname ?: get_string('section') . ' ' . $record->sectionnumber;

            // Calculate weight percentage.
            $weight = ($record->gradeweight2 > 0) ? round($record->gradeweight2 * 100, 2) : round($record->gradeweight, 2);

            // Activity URL.
            $activityurl = new moodle_url('/mod/' . $record->moduletype . '/view.php', ['id' => $record->cmid]);

            $activities[] = [
                'gradeitemid' => (int)$record->gradeitemid,
                'section' => $section,
                'activityname' => format_string($record->activityname),
                'activityurl' => $activityurl->out(false),
                'moduletype' => $modname,
                'activityvisible' => $record->activityvisible ? get_string('yes', 'report_gradeitems') : get_string('no', 'report_gradeitems'),
                'gradetype' => $gradetypestr,
                'grademax' => format_float($record->grademax, 2),
                'gradepass' => format_float($record->gradepass, 2),
                'gradeweight' => $weight . '%',
                'gradecount' => (int)$record->gradecount,
                'gradeaverage' => $record->gradeaverage !== null ? format_float($record->gradeaverage, 2) : '-',
            ];
        }

        return [
            'activities' => $activities,
            'totalcount' => count($activities),
        ];
    }

    /**
     * Define return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'activities' => new external_multiple_structure(
                new external_single_structure([
                    'gradeitemid' => new external_value(PARAM_INT, 'Grade item ID'),
                    'section' => new external_value(PARAM_TEXT, 'Section name'),
                    'activityname' => new external_value(PARAM_TEXT, 'Activity name'),
                    'activityurl' => new external_value(PARAM_URL, 'Activity URL'),
                    'moduletype' => new external_value(PARAM_TEXT, 'Module type'),
                    'activityvisible' => new external_value(PARAM_TEXT, 'Activity visibility'),
                    'gradetype' => new external_value(PARAM_TEXT, 'Grade type'),
                    'grademax' => new external_value(PARAM_TEXT, 'Maximum grade'),
                    'gradepass' => new external_value(PARAM_TEXT, 'Passing grade'),
                    'gradeweight' => new external_value(PARAM_TEXT, 'Grade weight'),
                    'gradecount' => new external_value(PARAM_INT, 'Number of grades'),
                    'gradeaverage' => new external_value(PARAM_TEXT, 'Average grade'),
                ])
            ),
            'totalcount' => new external_value(PARAM_INT, 'Total activities count'),
        ]);
    }

    /**
     * Format grade type to string.
     *
     * @param int $gradetype
     * @return string
     */
    private static function format_grade_type(int $gradetype): string {
        switch ($gradetype) {
            case 1:
                return get_string('gradetype_value', 'report_gradeitems');
            case 2:
                return get_string('gradetype_scale', 'report_gradeitems');
            case 3:
                return get_string('gradetype_text', 'report_gradeitems');
            default:
                return get_string('gradetype_none', 'report_gradeitems');
        }
    }
}
