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
 * Course index progress calculator.
 *
 * @package    theme_compecer
 * @copyright  2024 IngeWeb https://www.ingeweb.co
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_compecer\output;

use core_courseformat\base as course_format;
use completion_info;
use core_completion\progress;
use renderable;
use renderer_base;
use templatable;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Calculates and exports progress data for the course index.
 */
class courseindex_progress implements renderable, templatable {

    /** @var int Default number of characters used for textual progress bars. */
    protected const PROGRESS_BAR_LENGTH = 20;

    /** @var course_format Course format instance */
    protected $format;

    /** @var int User ID */
    protected $userid;

    /** @var completion_info Completion info instance */
    protected $completion;

    /**
     * Constructor.
     *
     * @param course_format $format Course format instance
     * @param int $userid User ID
     */
    public function __construct(course_format $format, int $userid) {
        $this->format = $format;
        $this->userid = $userid;
        $this->completion = new completion_info($format->get_course());
    }

    /**
     * Export data for template.
     *
     * @param renderer_base $output Renderer
     * @return stdClass Template data
     */
    public function export_for_template(renderer_base $output): stdClass {
        $data = new stdClass();
        $data->progress = $this->get_progress_data();
        return $data;
    }

    /**
     * Get comprehensive progress data.
     *
     * @return stdClass Progress data structure
     */
    protected function get_progress_data(): stdClass {
        $data = new stdClass();
        $data->enabled = $this->completion->is_enabled();

        // Course-level progress.
        $data->course = $this->get_course_progress();

        // Section-level progress.
        $data->sections = $this->get_sections_progress();

        $data->dataset = $this->encode_progress_dataset($data->course, $data->sections, $data->enabled);

        return $data;
    }

    /**
     * Get course-level progress information.
     *
     * @return stdClass Course progress data
     */
    protected function get_course_progress(): stdClass {
        $course = $this->format->get_course();
        $data = new stdClass();
        $data->title = get_string('courseprogressheading', 'theme_compecer');

        if (!$this->completion->is_enabled()) {
            $data->summary = get_string('courseprogressdisabledsummary', 'theme_compecer');
            $data->percentage = 0;
            $data->percentageformatted = get_string('courseprogressnotavailable', 'theme_compecer');
            $data->completed = 0;
            $data->total = 0;
            $data->aria = get_string('courseprogressdisabled', 'theme_compecer');
            $data->bar = $this->render_progress_bar(0);
            return $data;
        }

        // Get completion percentage using core API.
        $percentage = progress::get_course_progress_percentage($course, $this->userid);
        if ($percentage === null) {
            $percentage = 0;
        }

        // Get activity counts.
        $counts = $this->get_activity_counts();

        $data->percentage = round($percentage, 0);
        $data->percentageformatted = $data->percentage . '%';
        $data->completed = $counts->completed;
        $data->total = $counts->total;
        $data->summary = get_string(
            'courseprogresssummary',
            'theme_compecer',
            [
                'completed' => $counts->completed,
                'total' => $counts->total,
                'percentage' => $data->percentage,
            ]
        );
        $data->aria = get_string(
            'courseprogressaria',
            'theme_compecer',
            [
                'percentage' => $data->percentage,
                'completed' => $counts->completed,
                'total' => $counts->total,
            ]
        );

        $data->bar = $this->render_progress_bar((int)$data->percentage);

        return $data;
    }

    /**
     * Get activity completion counts.
     *
     * @return stdClass Object with completed and total counts
     */
    protected function get_activity_counts(): stdClass {
        $counts = new stdClass();
        $counts->completed = 0;
        $counts->total = 0;

        if (!$this->completion->is_enabled()) {
            return $counts;
        }

        $modinfo = get_fast_modinfo($this->format->get_course());
        foreach ($modinfo->get_cms() as $cm) {
            // Skip modules that don't have completion tracking.
            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                continue;
            }

            // Skip modules not visible to user.
            if (!$cm->uservisible) {
                continue;
            }

            $counts->total++;

            // Check if completed.
            $completiondata = $this->completion->get_data($cm, false, $this->userid);
            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $counts->completed++;
            }
        }

        return $counts;
    }

    /**
     * Get progress data for all sections.
     *
     * @return array Array of section progress data
     */
    protected function get_sections_progress(): array {
        $sections = [];
        $course = $this->format->get_course();
        $modinfo = get_fast_modinfo($course);

        foreach ($modinfo->get_section_info_all() as $section) {
            if (!$section->uservisible) {
                continue;
            }

            $sectiondata = new stdClass();
            $sectiondata->id = $section->id;
            $sectiondata->number = $section->section;

            // Calculate section completion.
            $counts = $this->get_section_activity_counts($section, $modinfo);
            $sectiondata->completed = $counts->completed;
            $sectiondata->total = $counts->total;

            if ($counts->total > 0) {
                $sectiondata->percentage = round(($counts->completed / $counts->total) * 100, 0);
                $sectiondata->summary = get_string(
                    'sectionprogresssummary',
                    'theme_compecer',
                    [
                        'completed' => $counts->completed,
                        'total' => $counts->total,
                        'percentage' => $sectiondata->percentage,
                    ]
                );
                $sectiondata->aria = get_string(
                    'sectionprogressaria',
                    'theme_compecer',
                    [
                        'completed' => $counts->completed,
                        'total' => $counts->total,
                        'percentage' => $sectiondata->percentage,
                    ]
                );
            } else {
                $sectiondata->percentage = 0;
                $sectiondata->summary = get_string('sectionprogressnotracked', 'theme_compecer');
                $sectiondata->aria = $sectiondata->summary;
            }

            $sectiondata->bar = $this->render_progress_bar((int)$sectiondata->percentage);

            $sections[] = $sectiondata;
        }

        return $sections;
    }

    /**
     * Encode progress data for the drawer dataset.
     *
     * @param stdClass $course Course progress data.
     * @param array $sections Section progress data.
     * @param bool $enabled Whether completion tracking is enabled.
     * @return string JSON encoded dataset.
     */
    protected function encode_progress_dataset(stdClass $course, array $sections, bool $enabled): string {
        $dataset = [
            'enabled' => $enabled,
            'course' => [
                'completed' => $course->completed ?? 0,
                'total' => $course->total ?? 0,
                'percentage' => $course->percentage ?? 0,
                'percentageformatted' => $course->percentageformatted ?? null,
                'bar' => $course->bar ?? $this->render_progress_bar(0),
                'summary' => $course->summary ?? '',
                'aria' => $course->aria ?? '',
            ],
            'sections' => array_map(function($section): array {
                return [
                    'id' => $section->id,
                    'number' => $section->number,
                    'completed' => $section->completed ?? 0,
                    'total' => $section->total ?? 0,
                    'percentage' => $section->percentage ?? 0,
                    'bar' => $section->bar ?? $this->render_progress_bar(0),
                    'summary' => $section->summary ?? '',
                    'aria' => $section->aria ?? '',
                ];
            }, $sections),
            'strings' => [
                'course' => [
                    'summary' => get_string(
                        'courseprogresssummary',
                        'theme_compecer',
                        [
                            'completed' => '{completed}',
                            'total' => '{total}',
                            'percentage' => '{percentage}',
                        ]
                    ),
                    'aria' => get_string(
                        'courseprogressaria',
                        'theme_compecer',
                        [
                            'percentage' => '{percentage}',
                            'completed' => '{completed}',
                            'total' => '{total}',
                        ]
                    ),
                ],
                'section' => [
                    'summary' => get_string(
                        'sectionprogresssummary',
                        'theme_compecer',
                        [
                            'completed' => '{completed}',
                            'total' => '{total}',
                            'percentage' => '{percentage}',
                        ]
                    ),
                    'aria' => get_string(
                        'sectionprogressaria',
                        'theme_compecer',
                        [
                            'completed' => '{completed}',
                            'total' => '{total}',
                            'percentage' => '{percentage}',
                        ]
                    ),
                    'nottracked' => get_string('sectionprogressnotracked', 'theme_compecer'),
                ],
                'status' => [
                    'notstarted' => get_string('completionstatus:notstarted', 'theme_compecer'),
                    'inprogress' => get_string('completionstatus:inprogress', 'theme_compecer'),
                    'completed' => get_string('completionstatus:completed', 'theme_compecer'),
                    'failed' => get_string('completionstatus:failed', 'theme_compecer'),
                ],
            ],
        ];

        return json_encode($dataset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Render a textual progress bar using unicode block characters.
     *
     * @param int $percentage Completion percentage from 0 to 100.
     * @param int $length Number of characters to represent the bar.
     * @return string
     */
    protected function render_progress_bar(int $percentage, int $length = self::PROGRESS_BAR_LENGTH): string {
        $percentage = max(0, min(100, $percentage));
        $length = max(1, $length);
        $filled = (int)round(($percentage / 100) * $length);
        $filled = min($length, max(0, $filled));

        $filledBlocks = str_repeat('█', $filled);
        $emptyBlocks = str_repeat('░', $length - $filled);

        return $filledBlocks . $emptyBlocks;
    }

    /**
     * Get activity completion counts for a section.
     *
     * @param \section_info $section Section info
     * @return stdClass Object with completed and total counts
     */
    protected function get_section_activity_counts(\section_info $section, \course_modinfo $modinfo): stdClass {
        $counts = new stdClass();
        $counts->completed = 0;
        $counts->total = 0;

        if (!$this->completion->is_enabled()) {
            return $counts;
        }

        if (empty($modinfo->sections[$section->section])) {
            return $counts;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Skip modules that don't have completion tracking.
            if ($cm->completion == COMPLETION_TRACKING_NONE) {
                continue;
            }

            // Skip modules not visible to user.
            if (!$cm->uservisible) {
                continue;
            }

            $counts->total++;

            // Check if completed.
            $completiondata = $this->completion->get_data($cm, false, $this->userid);
            if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                $counts->completed++;
            }
        }

        return $counts;
    }
}