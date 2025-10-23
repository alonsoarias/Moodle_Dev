<?php
// This file is part of Moodle - http://moodle.org/.
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

namespace theme_compecer\courseindex;

use cm_info;
use completion_info;
use core_completion\progress;
use core_courseformat\base as course_format;

/**
 * Gather course progress data for the course index drawer.
 *
 * The approach mirrors the logic used in theme_remui and the
 * format_remuiformat course format, both of which rely on the
 * completion API to retrieve accurate progress information.
 *
 * @package   theme_compecer
 * @copyright 2024 IngeWeb
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class progress_data {
    /**
     * Build the dataset consumed by the drawer template and AMD module.
     *
     * @param course_format $format Course format instance.
     * @param int|null $userid Optional user id.
     * @return array
     */
    public static function build(course_format $format, ?int $userid = null): array {
        global $USER;

        $userid = $userid ?? $USER->id;
        $course = $format->get_course();
        $completioninfo = new completion_info($course);
        $enabled = $completioninfo->is_enabled();

        $istrackeduser = $completioninfo->is_tracked_user($userid);

        $dataset = [
            'enabled' => $enabled === COMPLETION_ENABLED,
            'strings' => self::get_strings(),
            'course' => [
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
                'percentageformatted' => '0%',
                'summary' => get_string('courseindex_progress_summary', 'theme_compecer'),
                'summarydisplay' => get_string('courseindex_progress_count', 'theme_compecer', (object)[
                    'completed' => 0,
                    'total' => 0,
                ]),
                'aria' => get_string('courseindex_progress_aria', 'theme_compecer'),
            ],
            'sections' => [],
            'modules' => [],
            'message' => [
                'notracking' => get_string('courseindex_notracking', 'theme_compecer'),
            ],
            'user' => [
                'istracked' => $istrackeduser,
            ],
        ];

        if (!$dataset['enabled']) {
            $dataset['course']['percentageformatted'] = '--';
            $dataset['course']['summary'] = get_string('courseindex_notracking', 'theme_compecer');
            $dataset['course']['summarydisplay'] = $dataset['course']['summary'];
            return $dataset;
        }

        $modinfo = get_fast_modinfo($course, $userid);
        $sections = $modinfo->get_section_info_all();
        $coursetotal = 0;
        $coursecompleted = 0;

        foreach ($sections as $sectioninfo) {
            if (!$sectioninfo->uservisible) {
                continue;
            }
            $sectiondata = self::build_section_data($sectioninfo, $modinfo, $completioninfo, $userid, $istrackeduser);
            if (!$sectiondata) {
                continue;
            }
            $sectionmodules = $sectiondata['modules'] ?? [];
            unset($sectiondata['modules']);
            $coursetotal += $sectiondata['total'];
            $coursecompleted += $sectiondata['completed'];
            $dataset['sections'][] = $sectiondata;
            if (!empty($sectionmodules)) {
                foreach ($sectionmodules as $moduledata) {
                    $dataset['modules'][] = $moduledata;
                }
            }
        }

        $percentage = self::normalise_percentage(
            progress::get_course_progress_percentage($course, $userid)
        );
        if ($percentage === null) {
            $percentage = $coursetotal > 0 ? round(($coursecompleted / $coursetotal) * 100) : 0;
        }

        $dataset['course'] = [
            'completed' => $coursecompleted,
            'total' => $coursetotal,
            'percentage' => $percentage,
            'percentageformatted' => self::format_percentage($percentage),
            'summary' => get_string('courseindex_progress_summary', 'theme_compecer'),
            'summarydisplay' => get_string('courseindex_progress_count', 'theme_compecer', (object)[
                'completed' => $coursecompleted,
                'total' => $coursetotal,
            ]),
            'aria' => get_string('courseindex_progress_aria', 'theme_compecer'),
        ];

        if (!$istrackeduser) {
            $dataset['course']['percentage'] = 0;
            $dataset['course']['percentageformatted'] = '--';
            $dataset['course']['summary'] = get_string('courseindex_notracking', 'theme_compecer');
            $dataset['course']['summarydisplay'] = $dataset['course']['summary'];
            $dataset['course']['aria'] = $dataset['course']['summary'];
        }

        return $dataset;
    }

    /**
     * Fetch language strings exposed to the front-end.
     *
     * @return array
     */
    protected static function get_strings(): array {
        return [
            'course' => [
                'summary' => self::normalise_tokens(get_string('courseindex_progress_summary', 'theme_compecer')),
                'aria' => self::normalise_tokens(get_string('courseindex_progress_aria', 'theme_compecer')),
            ],
            'section' => [
                'summary' => self::normalise_tokens(get_string('courseindex_section_summary', 'theme_compecer')),
                'aria' => self::normalise_tokens(get_string('courseindex_section_aria', 'theme_compecer')),
                'nottracked' => get_string('courseindex_section_nottracked', 'theme_compecer'),
            ],
            'status' => [
                'notstarted' => get_string('activitystatusnotstarted', 'theme_compecer'),
                'inprogress' => get_string('activitystatusinprogress', 'theme_compecer'),
                'completed' => get_string('activitystatuscompleted', 'theme_compecer'),
                'failed' => get_string('activitystatusfailed', 'theme_compecer'),
            ],
        ];
    }

    /**
     * Build the per-section completion dataset.
     *
     * @param \section_info $sectioninfo Section info.
     * @param \course_modinfo $modinfo Module info cache.
     * @param completion_info $completioninfo Completion helper.
     * @param int $userid User id.
     * @return array|null
     */
    protected static function build_section_data(
        \section_info $sectioninfo,
        \course_modinfo $modinfo,
        completion_info $completioninfo,
        int $userid,
        bool $istrackeduser
    ): ?array {
        $sectionmodules = $modinfo->sections[$sectioninfo->section] ?? [];
        $total = 0;
        $completed = 0;
        $modules = [];

        foreach ($sectionmodules as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if (!self::should_track_module($cm)) {
                continue;
            }

            $moduledata = self::build_module_data($sectioninfo, $cm, $completioninfo, $userid, $istrackeduser);
            if ($moduledata === null) {
                continue;
            }

            $modules[] = $moduledata;
            if ($moduledata['tracked']) {
                $total++;
                if (self::is_completed($moduledata['state'])) {
                    $completed++;
                }
            }
        }

        if ($total === 0) {
            return [
                'id' => $sectioninfo->id,
                'completed' => 0,
                'total' => 0,
                'percentage' => 0,
                'percentageformatted' => '0%',
                'summary' => get_string('courseindex_section_nottracked', 'theme_compecer'),
                'summarydisplay' => get_string('courseindex_section_nottracked', 'theme_compecer'),
                'aria' => get_string('courseindex_section_nottracked', 'theme_compecer'),
                'modules' => $modules,
            ];
        }

        $percentage = round(($completed / $total) * 100);

        return [
            'id' => $sectioninfo->id,
            'completed' => $completed,
            'total' => $total,
            'percentage' => $percentage,
            'percentageformatted' => self::format_percentage($percentage),
            'summary' => get_string('courseindex_section_summary', 'theme_compecer'),
            'summarydisplay' => get_string('courseindex_section_summary', 'theme_compecer', (object)[
                'completed' => $completed,
                'total' => $total,
                'percentage' => $percentage,
            ]),
            'aria' => get_string('courseindex_section_aria', 'theme_compecer'),
            'modules' => $modules,
        ];
    }

    /**
     * Build completion metadata for a single module.
     *
     * @param \section_info $sectioninfo Section info.
     * @param cm_info $cm Module info.
     * @param completion_info $completioninfo Completion helper.
     * @param int $userid User id.
     * @return array|null
     */
    protected static function build_module_data(
        \section_info $sectioninfo,
        cm_info $cm,
        completion_info $completioninfo,
        int $userid,
        bool $istrackeduser
    ): ?array {
        $tracking = $completioninfo->is_enabled($cm);
        if ($tracking == COMPLETION_TRACKING_NONE) {
            return null;
        }

        if (!$istrackeduser) {
            return [
                'id' => $cm->id,
                'sectionid' => $sectioninfo->id,
                'sectionnumber' => $sectioninfo->section,
                'tracked' => false,
                'state' => null,
                'status' => 'notstarted',
                'viewed' => false,
            ];
        }

        $completiondata = $completioninfo->get_data($cm, true, $userid);
        $state = isset($completiondata->completionstate) ? (int)$completiondata->completionstate : null;
        return [
            'id' => $cm->id,
            'sectionid' => $sectioninfo->id,
            'sectionnumber' => $sectioninfo->section,
            'tracked' => true,
            'state' => $state,
            'status' => self::map_completion_status($completiondata),
            'viewed' => !empty($completiondata->viewed),
        ];
    }

    /**
     * Determine if a module should be counted in the completion report.
     *
     * @param cm_info $cm Course module info.
     * @return bool
     */
    protected static function should_track_module(cm_info $cm): bool {
        return $cm->uservisible && $cm->is_visible_on_course_page();
    }

    /**
     * Detect whether a completion record means the activity is finished.
     *
     * @param object $completiondata Completion record.
     * @return bool
     */
    protected static function is_completed($completiondata): bool {
        if (is_object($completiondata)) {
            $state = (int)($completiondata->completionstate ?? 0);
        } else if (is_numeric($completiondata)) {
            $state = (int)$completiondata;
        } else {
            $state = 0;
        }

        return in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS, COMPLETION_COMPLETE_FAIL], true);
    }

    /**
     * Determine the visual status to display for a completion record.
     *
     * @param object $completiondata Completion information.
     * @return string
     */
    protected static function map_completion_status(object $completiondata): string {
        $state = isset($completiondata->completionstate) ? (int)$completiondata->completionstate : COMPLETION_INCOMPLETE;

        if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
            return 'completed';
        }

        if ($state === COMPLETION_COMPLETE_FAIL) {
            return 'failed';
        }

        if (!empty($completiondata->viewed) || !empty($completiondata->timemodified)) {
            return 'inprogress';
        }

        return 'notstarted';
    }

    /**
     * Normalise a percentage value.
     *
     * @param mixed $percentage Raw value.
     * @return int|null
     */
    protected static function normalise_percentage($percentage): ?int {
        if ($percentage === null || $percentage === '') {
            return null;
        }
        if (!is_numeric($percentage)) {
            return null;
        }
        return (int)round($percentage);
    }

    /**
     * Format a percentage for display.
     *
     * @param int $percentage Value to format.
     * @return string
     */
    protected static function format_percentage(int $percentage): string {
        $safe = max(0, min(100, $percentage));
        return $safe . '%';
    }

    /**
     * Replace Moodle style placeholders with the plain tokens expected by the JS helper.
     *
     * @param string $value Raw string.
     * @return string
     */
    protected static function normalise_tokens(string $value): string {
        $map = [
            '{$a->completed}' => '{completed}',
            '{$a->total}' => '{total}',
            '{$a->percentage}' => '{percentage}',
        ];
        return str_replace(array_keys($map), array_values($map), $value);
    }
}
