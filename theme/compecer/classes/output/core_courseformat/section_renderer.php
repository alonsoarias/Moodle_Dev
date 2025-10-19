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

namespace theme_compecer\output\core_courseformat;

use completion_info;
use core_completion\progress;
use core_courseformat\base as course_format;
use core_courseformat\output\section_renderer as core_section_renderer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/completionlib.php');

/**
 * Renderer extension that augments the course index with completion progress metadata.
 *
 * @package    theme_compecer
 */
class section_renderer extends core_section_renderer {
    /**
     * Export the course index drawer enriched with completion progress data.
     *
     * @param course_format $format the course format
     * @return string|null
     */
    public function course_index_drawer(course_format $format): ?string {
        global $USER;

        if (!$format->uses_course_index()) {
            return '';
        }

        include_course_editor($format);

        $course = $format->get_course();
        $modinfo = get_fast_modinfo($course, $USER->id);
        $completioninfo = new completion_info($course);

        $sectionsdataset = [];
        $modulesdataset = [];

        $globaltotal = 0;
        $globalcompleted = 0;

        $sections = $modinfo->get_section_info_all();
        foreach ($sections as $sectioninfo) {
            if (!$format->is_section_visible($sectioninfo)) {
                continue;
            }
            $sectiontotal = 0;
            $sectioncompleted = 0;

            $cmids = $modinfo->sections[$sectioninfo->section] ?? [];
            foreach ($cmids as $cmid) {
                $cm = $modinfo->get_cm($cmid);
                if (!$cm->uservisible) {
                    continue;
                }
                $modulesdataset[$cmid] = [
                    'sectionid' => $sectioninfo->id,
                    'tracked' => false,
                    'state' => null,
                    'started' => false,
                ];
                if (!$completioninfo->is_enabled($cm)) {
                    continue;
                }

                $completiondata = $completioninfo->get_data($cm, false, $USER->id);
                $state = (int)($completiondata->completionstate ?? COMPLETION_INCOMPLETE);
                $started = !empty($completiondata->timemodified) || !empty($completiondata->viewed);

                $modulesdataset[$cmid]['tracked'] = true;
                $modulesdataset[$cmid]['state'] = $state;
                $modulesdataset[$cmid]['started'] = $started;

                $sectiontotal++;
                $globaltotal++;

                if (in_array($state, [COMPLETION_COMPLETE, COMPLETION_COMPLETE_PASS], true)) {
                    $sectioncompleted++;
                    $globalcompleted++;
                }
            }

            $sectionsdataset[$sectioninfo->id] = [
                'completed' => $sectioncompleted,
                'total' => $sectiontotal,
            ];
        }

        $percentage = progress::get_course_progress_percentage($course, $USER->id);
        if ($percentage === null || $percentage === '') {
            $percentage = $globaltotal > 0 ? round(($globalcompleted / $globaltotal) * 100) : 0;
        }
        $percentage = (int)round($percentage);

        $hasprogress = $globaltotal > 0;
        $globalprogress = [
            'completed' => $globalcompleted,
            'total' => $globaltotal,
            'percentage' => $hasprogress ? $percentage : 0,
            'hasprogress' => $hasprogress,
        ];

        $statelabels = [
            'notstarted' => get_string('completion_notstarted', 'theme_compecer'),
            'inprogress' => get_string('completion_inprogress', 'theme_compecer'),
            'completed' => get_string('completion_completed', 'theme_compecer'),
            'failed' => get_string('completion_failed', 'theme_compecer'),
            'untracked' => get_string('completion_untracked', 'theme_compecer'),
        ];

        $context = [
            'hasglobalprogress' => $hasprogress,
            'globalprogress' => array_merge($globalprogress, [
                'percentageformatted' => $hasprogress ? format_float($percentage, 0) : '0',
            ]),
            'progressdataset' => json_encode([
                'global' => $globalprogress,
                'sections' => $sectionsdataset,
                'modules' => $modulesdataset,
            ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE),
            'statelabels' => json_encode($statelabels, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE),
        ];

        return $this->render_from_template('core_courseformat/local/courseindex/drawer', $context);
    }
}
