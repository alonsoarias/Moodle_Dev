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

namespace format_nexusformat\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use moodle_exception;

/**
 * External function for getting activity content.
 *
 * @package    format_nexusformat
 * @copyright  2024 Nexus Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_activity_content extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
        ]);
    }

    /**
     * Get the content of an activity.
     *
     * @param int $cmid Course module ID
     * @return array Activity content data
     */
    public static function execute(int $cmid): array {
        global $DB, $PAGE, $OUTPUT, $CFG;

        // Validate parameters.
        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cmid = $params['cmid'];

        // Get the course module.
        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

        // Check context and capabilities.
        $context = context_module::instance($cmid);
        self::validate_context($context);

        // Check if user can access this activity.
        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($cmid);

        if (!$cminfo->uservisible) {
            throw new moodle_exception('nopermissions', 'error', '', 'view this activity');
        }

        // Build the content.
        $content = self::get_activity_html($cm, $cminfo, $course, $context);

        return [
            'content' => $content,
            'cmid' => $cmid,
            'modname' => $cm->modname,
            'name' => $cminfo->get_formatted_name(),
            'url' => $cminfo->url ? $cminfo->url->out(false) : '',
        ];
    }

    /**
     * Get the HTML content for an activity.
     *
     * @param object $cm Course module record
     * @param \cm_info $cminfo Course module info object
     * @param object $course Course record
     * @param \context_module $context Module context
     * @return string HTML content
     */
    protected static function get_activity_html($cm, $cminfo, $course, $context): string {
        global $CFG, $OUTPUT, $PAGE, $DB;

        $modname = $cm->modname;
        $html = '';

        // Activity header with title and description.
        $html .= '<div class="nexus-activity-header">';
        $html .= '<h3 class="nexus-activity-title">';
        $html .= '<img src="' . $cminfo->get_icon_url() . '" alt="" class="nexus-activity-icon" /> ';
        $html .= format_string($cminfo->name);
        $html .= '</h3>';

        // Show activity intro/description if available.
        $instance = $DB->get_record($modname, ['id' => $cm->instance], '*', MUST_EXIST);
        if (!empty($instance->intro)) {
            $html .= '<div class="nexus-activity-intro">';
            $html .= format_module_intro($modname, $instance, $cm->id);
            $html .= '</div>';
        }
        $html .= '</div>';

        // Main content area.
        $html .= '<div class="nexus-activity-main">';

        // Handle specific module types.
        switch ($modname) {
            case 'page':
                $html .= self::get_page_content($instance, $context);
                break;

            case 'label':
                // Labels just show their intro content.
                break;

            case 'resource':
                $html .= self::get_resource_content($cm, $instance, $context);
                break;

            case 'url':
                $html .= self::get_url_content($instance);
                break;

            case 'book':
                $html .= self::get_book_content($cm, $instance, $context);
                break;

            default:
                // For other activities (quiz, assign, forum, etc.), show a link.
                $html .= self::get_default_activity_content($cminfo);
                break;
        }

        $html .= '</div>';

        // Add completion button if needed.
        $html .= self::get_completion_section($cminfo, $course);

        return $html;
    }

    /**
     * Get page module content.
     */
    protected static function get_page_content($instance, $context): string {
        $content = file_rewrite_pluginfile_urls(
            $instance->content,
            'pluginfile.php',
            $context->id,
            'mod_page',
            'content',
            $instance->revision
        );
        return '<div class="nexus-page-content">' . format_text($content, $instance->contentformat) . '</div>';
    }

    /**
     * Get resource (file) content.
     */
    protected static function get_resource_content($cm, $instance, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/resource/lib.php');

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return '<p>' . get_string('filenotfound', 'resource') . '</p>';
        }

        $file = reset($files);
        $fileurl = \moodle_url::make_pluginfile_url(
            $context->id,
            'mod_resource',
            'content',
            0,
            $file->get_filepath(),
            $file->get_filename()
        )->out();

        $mimetype = $file->get_mimetype();
        $filename = $file->get_filename();

        // Handle different file types.
        if (strpos($mimetype, 'image/') === 0) {
            return '<div class="nexus-resource-image"><img src="' . $fileurl . '" alt="' . s($filename) . '" class="img-fluid" /></div>';
        } else if ($mimetype === 'application/pdf') {
            return '<div class="nexus-resource-pdf"><embed src="' . $fileurl . '" type="application/pdf" width="100%" height="600px" /></div>';
        } else if (strpos($mimetype, 'video/') === 0) {
            return '<div class="nexus-resource-video"><video controls class="w-100"><source src="' . $fileurl . '" type="' . $mimetype . '"></video></div>';
        } else if (strpos($mimetype, 'audio/') === 0) {
            return '<div class="nexus-resource-audio"><audio controls class="w-100"><source src="' . $fileurl . '" type="' . $mimetype . '"></audio></div>';
        } else {
            // For other files, show download link.
            return '<div class="nexus-resource-download"><a href="' . $fileurl . '" class="btn btn-primary" download><i class="fa fa-download"></i> ' . get_string('download') . ' ' . s($filename) . '</a></div>';
        }
    }

    /**
     * Get URL module content.
     */
    protected static function get_url_content($instance): string {
        $displayoptions = empty($instance->displayoptions) ? [] : (array)unserialize_array($instance->displayoptions);

        return '<div class="nexus-url-content">' .
            '<a href="' . s($instance->externalurl) . '" target="_blank" rel="noopener" class="btn btn-primary">' .
            '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'url') .
            '</a></div>';
    }

    /**
     * Get book module content (first chapter).
     */
    protected static function get_book_content($cm, $instance, $context): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/book/lib.php');

        // Get first chapter.
        $chapters = $DB->get_records('book_chapters', ['bookid' => $instance->id], 'pagenum', '*', 0, 1);
        if (empty($chapters)) {
            return '<p>' . get_string('nocontent', 'mod_book') . '</p>';
        }

        $chapter = reset($chapters);
        $content = file_rewrite_pluginfile_urls(
            $chapter->content,
            'pluginfile.php',
            $context->id,
            'mod_book',
            'chapter',
            $chapter->id
        );

        $html = '<div class="nexus-book-content">';
        $html .= '<h4>' . format_string($chapter->title) . '</h4>';
        $html .= format_text($content, $chapter->contentformat);
        $html .= '</div>';

        // Note about more chapters.
        $totalchapters = $DB->count_records('book_chapters', ['bookid' => $instance->id]);
        if ($totalchapters > 1) {
            $html .= '<div class="nexus-book-more alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ';
            $html .= get_string('numchapters', 'mod_book', $totalchapters);
            $html .= ' - <a href="' . (new \moodle_url('/mod/book/view.php', ['id' => $cm->id]))->out() . '">';
            $html .= get_string('viewbook', 'mod_book') . '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Get default content for interactive activities.
     */
    protected static function get_default_activity_content($cminfo): string {
        $html = '<div class="nexus-activity-link alert alert-info">';
        $html .= '<p><i class="fa fa-info-circle"></i> ';
        $html .= get_string('activity_requires_interaction', 'format_nexusformat') . '</p>';
        if ($cminfo->url) {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary" target="_blank">';
            $html .= '<i class="fa fa-external-link"></i> ';
            $html .= get_string('openactivity', 'format_nexusformat');
            $html .= '</a>';
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Get completion section HTML.
     */
    protected static function get_completion_section($cminfo, $course): string {
        global $USER, $CFG;
        require_once($CFG->libdir . '/completionlib.php');

        $completion = new \completion_info($course);
        if (!$completion->is_enabled() || $cminfo->completion == COMPLETION_TRACKING_NONE) {
            return '';
        }

        $completiondata = $completion->get_data($cminfo, true, $USER->id);
        $iscomplete = ($completiondata->completionstate == COMPLETION_COMPLETE ||
                       $completiondata->completionstate == COMPLETION_COMPLETE_PASS);

        $html = '<div class="nexus-completion-section">';
        if ($iscomplete) {
            $html .= '<span class="badge badge-success bg-success"><i class="fa fa-check"></i> ';
            $html .= get_string('completed', 'completion') . '</span>';
        } else {
            $html .= '<span class="badge badge-secondary bg-secondary"><i class="fa fa-circle-o"></i> ';
            $html .= get_string('notcompleted', 'completion') . '</span>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'content' => new external_value(PARAM_RAW, 'Activity HTML content'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'modname' => new external_value(PARAM_ALPHANUMEXT, 'Module name'),
            'name' => new external_value(PARAM_TEXT, 'Activity name'),
            'url' => new external_value(PARAM_URL, 'Activity URL', VALUE_OPTIONAL),
        ]);
    }
}
