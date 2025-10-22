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

namespace theme_compecer\output;

defined('MOODLE_INTERNAL') || die();

use completion_info;
use core_courseformat\base as course_format;
use renderer_base;
use renderable;
use stdClass;
use templatable;

/**
 * Course index progress data provider for theme Compecer.
 *
 * Generates aggregate progress information for the course drawer, section
 * entries and individual course modules.
 */
class courseindex_progress implements renderable, templatable {
    /** @var course_format $format */
    protected $format;

    /** @var stdClass $course */
    protected $course;

    /** @var int $userid */
    protected $userid;

    /** @var completion_info $completioninfo */
    protected $completioninfo;

    /**
     * courseindex_progress constructor.
     *
     * @param course_format $format The active course format instance.
     * @param int $userid The user id to calculate progress for.
     */
    public function __construct(course_format $format, int $userid) {
        $this->format = $format;
        $this->course = $format->get_course();
        $this->userid = $userid;
        $this->completioninfo = new completion_info($this->course);
    }

    /**
     * Export data for Mustache templates and the front-end controller.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        $dataset = [
            'enabled' => false,
            'course' => [],
            'sections' => [],
            'activities' => [],
            'strings' => $this->get_status_strings(),
        ];

        if (!$this->completioninfo->is_enabled()) {
            $dataset['course'] = [
                'percentageformatted' => get_string('courseprogressdisabled', 'theme_compecer'),
                'summary' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
                'aria' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
            ];
            return [
                'enabled' => false,
                'course' => [
                    'title' => get_string('courseprogressheading', 'theme_compecer'),
                    'percentage' => null,
                    'percentageformatted' => get_string('courseprogressdisabled', 'theme_compecer'),
                    'summary' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
                    'aria' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
                ],
                'dataset' => json_encode($dataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            ];
        }

        $modinfo = get_fast_modinfo($this->course, $this->userid);
        $sectionsstructure = $modinfo->get_sections();

        $coursecounts = $this->init_counter();
        $sectiondata = [];
        $activitydata = [];

        foreach ($sectionsstructure as $sectionnum => $cmids) {
            $sectioninfo = $modinfo->get_section_info($sectionnum);
            if (!$sectioninfo || !$this->format->is_section_visible($sectioninfo)) {
                continue;
            }

            $sectioncounts = $this->init_counter();
            foreach ($cmids as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if (!$cm || !$cm->uservisible || $cm->deletioninprogress) {
                    continue;
                }
                if (!$this->completioninfo->is_enabled($cm)) {
                    continue;
                }

                $status = $this->resolve_cm_status($cm);
                if ($status === null) {
                    continue;
                }

                $sectioncounts['tracked']++;
                $coursecounts['tracked']++;

                $sectioncounts[$status['status']]++;
                $coursecounts[$status['status']]++;

                $activitydata[$cm->id] = [
                    'status' => $status['status'],
                    'state' => $status['state'],
                    'label' => get_string('completionstatus:' . $status['status'], 'theme_compecer'),
                ];
            }

            $sectiondata[$sectioninfo->id] = $this->build_section_payload($sectioninfo, $sectioncounts);
        }

        $dataset['activities'] = $activitydata;
        $dataset['sections'] = $sectiondata;

        $courseenabled = $coursecounts['tracked'] > 0;
        $dataset['enabled'] = $courseenabled;
        if ($courseenabled) {
            $coursepercentage = $this->percentage($coursecounts['completed'], $coursecounts['tracked']);
            $dataset['course'] = [
                'percentage' => $coursepercentage,
                'completed' => $coursecounts['completed'],
                'failed' => $coursecounts['failed'],
                'inprogress' => $coursecounts['inprogress'],
                'notstarted' => $coursecounts['notstarted'],
                'total' => $coursecounts['tracked'],
                'summary' => get_string('courseprogresssummary', 'theme_compecer', (object) [
                    'completed' => $coursecounts['completed'],
                    'total' => $coursecounts['tracked'],
                    'percentage' => $coursepercentage,
                ]),
                'aria' => get_string('courseprogressaria', 'theme_compecer', (object) [
                    'percentage' => $coursepercentage,
                ]),
            ];
        }

        $export = [
            'enabled' => $courseenabled,
            'course' => $this->build_course_payload($coursecounts),
            'dataset' => json_encode($dataset, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
        ];

        return $export;
    }

    /**
     * Initialise counters for progress tracking.
     *
     * @return array
     */
    protected function init_counter(): array {
        return [
            'tracked' => 0,
            'completed' => 0,
            'failed' => 0,
            'inprogress' => 0,
            'notstarted' => 0,
        ];
    }

    /**
     * Map a course module to a normalised completion state.
     *
     * @param \cm_info $cm
     * @return array|null
     */
    protected function resolve_cm_status(\cm_info $cm): ?array {
        $data = $this->completioninfo->get_data($cm, false, $this->userid);
        if (!isset($data->completionstate)) {
            return null;
        }

        $state = (int)$data->completionstate;
        if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
            return ['status' => 'completed', 'state' => $state];
        }

        if (in_array($state, [COMPLETION_COMPLETE_FAIL, COMPLETION_COMPLETE_FAIL_HIDDEN], true)) {
            return ['status' => 'failed', 'state' => $state];
        }

        $inprogress = $this->has_progress($data);
        return ['status' => $inprogress ? 'inprogress' : 'notstarted', 'state' => $state];
    }

    /**
     * Determine whether the completion data reflects ongoing progress.
     *
     * @param stdClass $data
     * @return bool
     */
    protected function has_progress(stdClass $data): bool {
        if (!empty($data->timemodified) || !empty($data->viewed)) {
            return true;
        }

        if (!empty($data->customcompletion) && is_array($data->customcompletion)) {
            foreach ($data->customcompletion as $rule) {
                if ((int)$rule !== COMPLETION_INCOMPLETE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Build the export payload for a section entry.
     *
     * @param stdClass $sectioninfo
     * @param array $counts
     * @return array
     */
    protected function build_section_payload(stdClass $sectioninfo, array $counts): array {
        $percentage = $counts['tracked'] > 0 ? $this->percentage($counts['completed'], $counts['tracked']) : 0;
        return [
            'id' => $sectioninfo->id,
            'number' => $sectioninfo->section,
            'tracked' => $counts['tracked'],
            'completed' => $counts['completed'],
            'failed' => $counts['failed'],
            'inprogress' => $counts['inprogress'],
            'notstarted' => $counts['notstarted'],
            'percentage' => $percentage,
            'summary' => get_string('sectionprogresssummary', 'theme_compecer', (object) [
                'completed' => $counts['completed'],
                'total' => $counts['tracked'],
            ]),
            'aria' => get_string('sectionprogressaria', 'theme_compecer', (object) [
                'completed' => $counts['completed'],
                'total' => $counts['tracked'],
                'percentage' => $percentage,
            ]),
        ];
    }

    /**
     * Build the course level payload used in the drawer header.
     *
     * @param array $counts
     * @return array
     */
    protected function build_course_payload(array $counts): array {
        if ($counts['tracked'] === 0) {
            return [
                'title' => get_string('courseprogressheading', 'theme_compecer'),
                'percentage' => null,
                'percentageformatted' => get_string('courseprogressdisabled', 'theme_compecer'),
                'summary' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
                'aria' => get_string('courseprogressdisabledsummary', 'theme_compecer'),
            ];
        }

        $percentage = $this->percentage($counts['completed'], $counts['tracked']);
        $formatted = format_float($percentage, 0) . '%';

        return [
            'title' => get_string('courseprogressheading', 'theme_compecer'),
            'percentage' => $percentage,
            'percentageformatted' => $formatted,
            'summary' => get_string('courseprogresssummary', 'theme_compecer', (object) [
                'completed' => $counts['completed'],
                'total' => $counts['tracked'],
                'percentage' => $percentage,
            ]),
            'aria' => get_string('courseprogressaria', 'theme_compecer', (object) [
                'percentage' => $percentage,
            ]),
        ];
    }

    /**
     * Calculate a rounded percentage based on two counters.
     *
     * @param int $completed
     * @param int $total
     * @return int
     */
    protected function percentage(int $completed, int $total): int {
        if ($total <= 0) {
            return 0;
        }
        return (int)round(($completed / $total) * 100);
    }

    /**
     * Provide localised status labels for the front-end controller.
     *
     * @return array
     */
    protected function get_status_strings(): array {
        return [
            'notstarted' => get_string('completionstatus:notstarted', 'theme_compecer'),
            'inprogress' => get_string('completionstatus:inprogress', 'theme_compecer'),
            'completed' => get_string('completionstatus:completed', 'theme_compecer'),
            'failed' => get_string('completionstatus:failed', 'theme_compecer'),
        ];
    }
}
