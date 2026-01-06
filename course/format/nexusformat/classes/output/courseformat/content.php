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
        $context = \context_course::instance($course->id);

        // Get base data from parent.
        $data = parent::export_for_template($output);

        // Add Nexus-specific data.
        $data->nexusformat = true;
        $data->courseid = $course->id;
        $data->coursefullname = format_string($course->fullname);
        $data->courseshortname = format_string($course->shortname);

        // Use integers for Mustache boolean compatibility.
        $data->editing = $PAGE->user_is_editing() ? 1 : 0;

        // Determine user role capabilities for view mode.
        $canviewhidden = has_capability('moodle/course:viewhiddenactivities', $context);
        $canmanage = has_capability('moodle/course:manageactivities', $context);
        $data->isteacher = ($canviewhidden || $canmanage) ? 1 : 0;
        $data->isstudent = !$data->isteacher ? 1 : 0;

        // Get progress information.
        $data->progress = $this->get_progress_data($course, $modinfo);

        // Get sections data for sidebar.
        $data->sidebarunits = $this->get_sidebar_units($modinfo, $output, $canviewhidden);

        // Get first activity URL for initial load.
        $data->initialactivityurl = $this->get_first_activity_url($modinfo);

        // Get first activity cmid for auto-load.
        $data->firstactivitycmid = $this->get_first_activity_cmid($modinfo);
        $data->hasfirstactivity = !empty($data->firstactivitycmid) ? 1 : 0;

        // Placeholder text.
        $data->selectactivitytext = get_string('select_activity', 'format_nexusformat');

        // Get gradable activities for the Activities tab.
        $data->gradableactivities = $this->get_gradable_activities($course, $modinfo);
        $data->hasgradableactivities = !empty($data->gradableactivities) ? 1 : 0;
        $data->gradablecount = count($data->gradableactivities);

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
     * Get gradable activities for the Activities tab.
     *
     * @param stdClass $course The course object
     * @param \course_modinfo $modinfo The course modinfo
     * @return array Array of gradable activity data
     */
    protected function get_gradable_activities(stdClass $course, \course_modinfo $modinfo): array {
        global $USER, $CFG;
        require_once($CFG->libdir . '/gradelib.php');

        $activities = [];
        $completion = new completion_info($course);

        // Get all grade items for activities in this course.
        $gradeitems = \grade_item::fetch_all([
            'courseid' => $course->id,
            'itemtype' => 'mod',
        ]);

        if (!$gradeitems) {
            return $activities;
        }

        foreach ($gradeitems as $gradeitem) {
            // Skip items without a valid module.
            if (empty($gradeitem->iteminstance) || empty($gradeitem->itemmodule)) {
                continue;
            }

            // Get the course module.
            try {
                $cm = $modinfo->get_cm($gradeitem->cmid ?? 0);
            } catch (\Exception $e) {
                // Try to find by module and instance.
                $cm = null;
                foreach ($modinfo->get_cms() as $coursemodule) {
                    if ($coursemodule->modname === $gradeitem->itemmodule &&
                        $coursemodule->instance == $gradeitem->iteminstance) {
                        $cm = $coursemodule;
                        break;
                    }
                }
            }

            if (!$cm || !$cm->uservisible) {
                continue;
            }

            $activity = new stdClass();
            $activity->id = $cm->id;
            $activity->name = format_string($cm->name);
            $activity->modname = $cm->modname;
            $activity->icon = $cm->get_icon_url()->out(false);
            $activity->url = $cm->url ? $cm->url->out(false) : '';

            // Get grade info.
            $activity->grademax = $gradeitem->grademax;
            $activity->gradepass = $gradeitem->gradepass;
            $activity->hasgrade = ($gradeitem->gradetype != GRADE_TYPE_NONE);

            // Get user's grade.
            $grades = grade_get_grades($course->id, 'mod', $cm->modname, $cm->instance, $USER->id);
            $activity->usergrade = null;
            $activity->gradeformatted = get_string('not_graded', 'format_nexusformat');
            $activity->isgraded = 0;
            $activity->ispassed = 0;
            $activity->isfailed = 0;

            if (!empty($grades->items[0]->grades[$USER->id])) {
                $usergrade = $grades->items[0]->grades[$USER->id];
                if ($usergrade->grade !== null) {
                    $activity->usergrade = $usergrade->grade;
                    $activity->gradeformatted = round($usergrade->grade, 2) . ' / ' . round($gradeitem->grademax, 2);
                    $activity->isgraded = 1;

                    // Check if passed.
                    if ($gradeitem->gradepass > 0) {
                        if ($usergrade->grade >= $gradeitem->gradepass) {
                            $activity->ispassed = 1;
                        } else {
                            $activity->isfailed = 1;
                        }
                    }
                }
            }

            // Get completion status.
            $activity->iscomplete = 0;
            $activity->isincomplete = 0;
            $activity->hascompletion = 0;

            if ($completion->is_enabled($cm) && $cm->completion != COMPLETION_TRACKING_NONE) {
                $activity->hascompletion = 1;
                $completiondata = $completion->get_data($cm, true, $USER->id);

                if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                    $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                    $activity->iscomplete = 1;
                } else {
                    $activity->isincomplete = 1;
                }
            }

            // Status label.
            if ($activity->iscomplete || $activity->isgraded) {
                $activity->statuslabel = get_string('status_completed', 'format_nexusformat');
                $activity->statusclass = 'success';
            } else {
                $activity->statuslabel = get_string('status_pending', 'format_nexusformat');
                $activity->statusclass = 'warning';
            }

            // Due date if available.
            $activity->duedate = null;
            $activity->duedateformatted = '';
            $activity->isoverdue = 0;

            // Check for due dates in common modules.
            $instance = $this->get_module_instance($cm);
            if ($instance) {
                $duefield = null;
                if (isset($instance->duedate) && $instance->duedate > 0) {
                    $duefield = $instance->duedate;
                } else if (isset($instance->timeclose) && $instance->timeclose > 0) {
                    $duefield = $instance->timeclose;
                } else if (isset($instance->cutoffdate) && $instance->cutoffdate > 0) {
                    $duefield = $instance->cutoffdate;
                }

                if ($duefield) {
                    $activity->duedate = $duefield;
                    $activity->duedateformatted = userdate($duefield, get_string('strftimedatetimeshort', 'langconfig'));
                    if ($duefield < time() && !$activity->iscomplete && !$activity->isgraded) {
                        $activity->isoverdue = 1;
                    }
                }
            }

            $activities[] = $activity;
        }

        // Sort by: incomplete first, then by name.
        usort($activities, function($a, $b) {
            // Incomplete activities first.
            if ($a->iscomplete != $b->iscomplete) {
                return $a->iscomplete - $b->iscomplete;
            }
            // Then by name.
            return strcmp($a->name, $b->name);
        });

        return $activities;
    }

    /**
     * Get module instance record.
     *
     * @param \cm_info $cm Course module info
     * @return object|null Module instance or null
     */
    protected function get_module_instance(\cm_info $cm): ?object {
        global $DB;
        try {
            return $DB->get_record($cm->modname, ['id' => $cm->instance]);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get sidebar units data - replicates Moodle courseindex structure.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @param renderer_base $output The renderer
     * @param bool $canviewhidden Whether user can view hidden activities
     * @return array Array of unit data for sidebar
     */
    protected function get_sidebar_units(\course_modinfo $modinfo, renderer_base $output, bool $canviewhidden = false): array {
        global $USER;

        $format = $this->format;
        $course = $format->get_course();
        $completion = new completion_info($course);
        $context = \context_course::instance($course->id);
        $units = [];

        // Get section preferences for collapsed state.
        $preferences = $format->get_sections_preferences();

        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            // Skip section 0 (general section) in the sidebar.
            if ($section->section == 0) {
                continue;
            }

            // Skip delegated sections (they are handled as part of their parent activity).
            if (!empty($section->component)) {
                continue;
            }

            // Check section visibility.
            $sectionvisible = $section->visible;
            $sectionuservisible = $section->uservisible;

            // For teachers: show all sections with visibility indicators.
            // For students: only show visible sections they can access.
            if (!$canviewhidden && !$sectionuservisible) {
                continue;
            }

            // Build section data similar to courseindex.
            $unit = $this->export_section_for_sidebar($section, $modinfo, $completion, $canviewhidden, $preferences);

            if ($unit !== null) {
                $units[] = $unit;
            }
        }

        return $units;
    }

    /**
     * Export a single section for the sidebar.
     *
     * @param \section_info $section The section info
     * @param \course_modinfo $modinfo The course modinfo
     * @param completion_info $completion The completion info
     * @param bool $canviewhidden Whether user can view hidden
     * @param array $preferences Section preferences
     * @param int $depth Nesting depth (for subsections)
     * @return stdClass|null Section data or null if should be skipped
     */
    protected function export_section_for_sidebar(
        \section_info $section,
        \course_modinfo $modinfo,
        completion_info $completion,
        bool $canviewhidden,
        array $preferences,
        int $depth = 0
    ): ?stdClass {
        global $USER;

        $format = $this->format;
        $course = $format->get_course();

        // Determine collapsed state from preferences.
        $indexcollapsed = false;
        if (isset($preferences[$section->id]) && !empty($preferences[$section->id]->indexcollapsed)) {
            $indexcollapsed = true;
        }

        $unit = new stdClass();
        $unit->id = $section->id;
        $unit->num = $section->section;
        $unit->name = $format->get_section_name($section);
        $unit->sectionurl = course_get_url($course, $section->section, ['navigation' => true])->out(false);

        // Collapsed state - first section expanded by default, rest collapsed.
        $unit->expanded = ($section->section == 1 && !$indexcollapsed) ? 1 : 0;
        $unit->indexcollapsed = $indexcollapsed ? 1 : 0;

        // Section visibility flags.
        $unit->visible = !empty($section->visible) ? 1 : 0;
        $unit->ishidden = !$section->visible ? 1 : 0;
        $unit->hiddentext = get_string('hiddenfromstudents');
        $unit->current = $format->is_section_current($section) ? 1 : 0;
        $unit->depth = $depth;
        $unit->issubsection = ($depth > 0) ? 1 : 0;

        // For delegated/subsections.
        $unit->isdelegated = !empty($section->component) ? 1 : 0;
        $unit->component = $section->component ?? '';

        // Get availability info for section.
        $unit->hasrestrictions = 0;
        if (!empty($section->availability)) {
            $ci = new \core_availability\info_section($section);
            $fullinfo = $ci->get_full_information();
            if (!empty($fullinfo)) {
                $unit->hasrestrictions = 1;
                $unit->availabilityinfo = $fullinfo;
            }
        }

        // Get course modules in this section.
        $unit->cms = [];
        if (!empty($modinfo->sections[$section->section])) {
            $cmindex = 1;
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->get_cm($cmid);

                // Check visibility.
                $activityvisible = $cm->visible;
                $activityuservisible = $cm->uservisible;
                $isstealth = $cm->is_stealth();

                // For students: only show visible activities.
                if (!$canviewhidden && (!$activityuservisible || $isstealth)) {
                    continue;
                }

                // Check for delegated section (subsection activity).
                $delegatedsectioninfo = $cm->get_delegated_section_info();

                $cmdata = new stdClass();
                $cmdata->id = $cm->id;
                $cmdata->num = $section->section . '.' . $cmindex;
                $cmdata->name = $cm->get_formatted_name();
                $cmdata->url = $cm->url ? $cm->url->out(false) : '';
                $cmdata->modname = $cm->modname;
                $cmdata->icon = $cm->get_icon_url()->out(false);
                $cmdata->anchor = "module-{$cm->id}";
                $cmdata->active = 0;
                $cmdata->uservisible = $activityuservisible ? 1 : 0;

                // Visibility flags.
                $cmdata->visible = $activityvisible ? 1 : 0;
                $cmdata->ishidden = !$activityvisible ? 1 : 0;
                $cmdata->isstealth = $isstealth ? 1 : 0;
                $cmdata->isrestricted = (!$activityuservisible && $activityvisible) ? 1 : 0;
                $cmdata->accessvisible = ($activityvisible && $activityuservisible) ? 1 : 0;

                // Visibility badge.
                if ($cmdata->ishidden) {
                    $cmdata->visibilitybadge = get_string('hiddenfromstudents');
                    $cmdata->badgeclass = 'badge-hidden';
                } else if ($cmdata->isstealth) {
                    $cmdata->visibilitybadge = get_string('hiddenoncoursepage');
                    $cmdata->badgeclass = 'badge-stealth';
                } else if ($cmdata->isrestricted) {
                    $cmdata->visibilitybadge = get_string('restricted');
                    $cmdata->badgeclass = 'badge-restricted';
                }

                // Availability info.
                if ($cm->availableinfo) {
                    $cmdata->availabilityinfo = $cm->availableinfo;
                    $cmdata->hascmrestrictions = 1;
                }

                // Completion status - using courseindex pattern.
                $cmdata->hascompletion = 0;
                $cmdata->iscomplete = 0;
                $cmdata->isincomplete = 0;
                $cmdata->isfail = 0;
                $cmdata->completionstate = 0;

                if ($completion->is_enabled($cm) && $cm->completion != COMPLETION_TRACKING_NONE) {
                    $completiondata = $completion->get_data($cm, true, $USER->id);
                    $cmdata->completionstate = $completiondata->completionstate;
                    $cmdata->hascompletion = 1;

                    if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                        $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $cmdata->iscomplete = 1;
                    } else if ($completiondata->completionstate == COMPLETION_COMPLETE_FAIL) {
                        $cmdata->isfail = 1;
                    } else {
                        $cmdata->isincomplete = 1;
                    }
                }

                // Handle delegated sections (subsections).
                $cmdata->hasdelegatedsection = 0;
                if (!empty($delegatedsectioninfo)) {
                    $cmdata->hasdelegatedsection = 1;
                    $cmdata->delegatedsectionid = $delegatedsectioninfo->id;

                    // Recursively export the subsection.
                    $subsectiondata = $this->export_section_for_sidebar(
                        $delegatedsectioninfo,
                        $modinfo,
                        $completion,
                        $canviewhidden,
                        $preferences,
                        $depth + 1
                    );

                    if ($subsectiondata !== null) {
                        $cmdata->sectioninfo = $subsectiondata;
                    }
                }

                $unit->cms[] = $cmdata;
                $cmindex++;
            }
        }

        $unit->hascms = !empty($unit->cms);
        $unit->cmcount = count($unit->cms);

        // For backward compatibility with existing template.
        $unit->lessons = $unit->cms;
        $unit->haslessons = $unit->hascms;
        $unit->lessoncount = $unit->cmcount;

        return $unit;
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
     * Get the cmid of the first loadable activity in the course.
     *
     * @param \course_modinfo $modinfo The course modinfo
     * @return int|null cmid of first activity or null
     */
    protected function get_first_activity_cmid(\course_modinfo $modinfo): ?int {
        $sections = $modinfo->get_section_info_all();

        foreach ($sections as $section) {
            // Skip delegated sections.
            if (!empty($section->component)) {
                continue;
            }

            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->get_cm($cmid);
                    // Skip hidden/stealth activities.
                    if (!$cm->uservisible || $cm->is_stealth()) {
                        continue;
                    }
                    // Check if it has a URL (is viewable).
                    if ($cm->url) {
                        return (int)$cm->id;
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
