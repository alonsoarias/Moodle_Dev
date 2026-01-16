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
     * Try to use the native module renderer to generate content.
     * This provides the most accurate representation of what view.php shows.
     *
     * @param string $modname Module name
     * @param object $cm Course module record
     * @param object $instance Module instance record
     * @param object $course Course record
     * @param \context_module $context Module context
     * @return string|null HTML content or null if native renderer cannot be used
     */
    protected static function try_native_renderer(string $modname, $cm, $instance, $course, $context): ?string {
        global $PAGE, $OUTPUT, $CFG, $DB, $USER;

        // Modules that can use native renderers safely in AJAX context.
        $supported = ['folder', 'book'];

        if (!in_array($modname, $supported)) {
            return null;
        }

        try {
            // Set up a minimal page context for the renderer.
            $PAGE->set_context($context);
            $PAGE->set_course($course);

            switch ($modname) {
                case 'folder':
                    // Folder renderer is simple and returns HTML directly.
                    require_once($CFG->dirroot . '/mod/folder/lib.php');
                    $renderer = $PAGE->get_renderer('mod_folder');
                    // The display_folder method returns HTML.
                    return $renderer->display_folder($instance);

                case 'book':
                    // Book can render chapter content.
                    require_once($CFG->dirroot . '/mod/book/locallib.php');
                    $chapters = book_preload_chapters($instance);
                    if (empty($chapters)) {
                        return null; // No chapters, use custom handler.
                    }

                    // Get first visible chapter for preview.
                    $firstchapter = null;
                    foreach ($chapters as $ch) {
                        if (!$ch->hidden) {
                            $firstchapter = $ch;
                            break;
                        }
                    }

                    if ($firstchapter) {
                        $chapterobj = $DB->get_record('book_chapters', ['id' => $firstchapter->id]);
                        if ($chapterobj) {
                            $chaptercontent = file_rewrite_pluginfile_urls(
                                $chapterobj->content,
                                'pluginfile.php',
                                $context->id,
                                'mod_book',
                                'chapter',
                                $chapterobj->id
                            );
                            $html = '<div class="book-chapter-content">';
                            $html .= '<h4>' . format_string($chapterobj->title) . '</h4>';
                            $html .= format_text($chaptercontent, $chapterobj->contentformat, ['context' => $context]);
                            $html .= '</div>';

                            // Add TOC info.
                            $html .= '<div class="card mt-3"><div class="card-header">';
                            $html .= '<strong>' . get_string('toc', 'book') . '</strong>';
                            $html .= '</div><ul class="list-group list-group-flush">';
                            foreach ($chapters as $ch) {
                                if (!$ch->hidden) {
                                    $activeclass = ($ch->id == $firstchapter->id) ? 'active' : '';
                                    $html .= '<li class="list-group-item ' . $activeclass . '">';
                                    if ($ch->subchapter) {
                                        $html .= '<span class="ps-3">';
                                    }
                                    $html .= format_string($ch->title);
                                    if ($ch->subchapter) {
                                        $html .= '</span>';
                                    }
                                    $html .= '</li>';
                                }
                            }
                            $html .= '</ul></div>';

                            // Link to full book.
                            $html .= '<div class="text-center mt-3">';
                            $viewurl = new \moodle_url('/mod/book/view.php', ['id' => $cm->id]);
                            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary">';
                            $html .= '<i class="fa fa-book"></i> ' . get_string('modulename', 'book') . '</a>';
                            $html .= '</div>';

                            return $html;
                        }
                    }
                    return null;
            }
        } catch (\Exception $e) {
            // If native renderer fails, return null to use custom handler.
            debugging('Native renderer failed for ' . $modname . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
            return null;
        }

        return null;
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
            $introhtml = format_module_intro($modname, $instance, $cm->id);
            // Process any completion placeholders that might not be processed in AJAX context.
            $introhtml = self::process_completion_placeholders($introhtml, $cminfo, $course);
            $html .= $introhtml;
            $html .= '</div>';
        }
        $html .= '</div>';

        // Main content area.
        $html .= '<div class="nexus-activity-main">';

        // Try native renderer first for supported modules.
        $nativeContent = self::try_native_renderer($modname, $cm, $instance, $course, $context);
        if ($nativeContent !== null) {
            $html .= $nativeContent;
            $html .= '</div>'; // Close nexus-activity-main.
            return $html;
        }

        // Handle specific module types with custom handlers.
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
                $html .= self::get_url_content($cm, $instance, $course);
                break;

            case 'book':
                $html .= self::get_book_content($cm, $instance, $context);
                break;

            case 'quiz':
                $html .= self::get_quiz_content($cm, $instance, $course, $context);
                break;

            case 'assign':
                $html .= self::get_assign_content($cm, $instance, $course, $context);
                break;

            case 'forum':
                $html .= self::get_forum_content($cm, $instance, $course, $context);
                break;

            case 'lesson':
                $html .= self::get_lesson_content($cm, $instance, $course, $context);
                break;

            case 'choice':
                $html .= self::get_choice_content($cm, $instance, $course, $context);
                break;

            case 'feedback':
                $html .= self::get_feedback_content($cm, $instance, $course, $context);
                break;

            case 'glossary':
                $html .= self::get_glossary_content($cm, $instance, $course, $context);
                break;

            case 'wiki':
                $html .= self::get_wiki_content($cm, $instance, $course, $context);
                break;

            case 'data':
                $html .= self::get_data_content($cm, $instance, $course, $context);
                break;

            case 'h5pactivity':
                $html .= self::get_h5p_content($cm, $instance, $context);
                break;

            case 'scorm':
                $html .= self::get_scorm_content($cm, $instance, $context);
                break;

            case 'lti':
                $html .= self::get_lti_content($cm, $instance, $context);
                break;

            case 'bigbluebuttonbn':
                $html .= self::get_bigbluebuttonbn_content($cm, $instance, $course, $context);
                break;

            case 'chat':
                $html .= self::get_chat_content($cm, $instance, $course, $context);
                break;

            case 'survey':
                $html .= self::get_survey_content($cm, $instance, $course, $context);
                break;

            case 'workshop':
                $html .= self::get_workshop_content($cm, $instance, $course, $context);
                break;

            case 'folder':
            case 'folder_custom':
                $html .= self::get_folder_content($cm, $instance, $context);
                break;

            case 'imscp':
                $html .= self::get_imscp_content($cm, $instance, $context);
                break;

            case 'checklist':
                $html .= self::get_checklist_content($cm, $instance, $course, $context);
                break;

            case 'customcert':
                $html .= self::get_customcert_content($cm, $instance, $course, $context);
                break;

            case 'scheduler':
                $html .= self::get_scheduler_content($cm, $instance, $course, $context);
                break;

            case 'game':
                $html .= self::get_game_content($cm, $instance, $context);
                break;

            case 'hotpot':
                $html .= self::get_hotpot_content($cm, $instance, $course, $context);
                break;

            case 'hvp':
                $html .= self::get_hvp_content($cm, $instance, $context);
                break;

            case 'readaloud':
                $html .= self::get_readaloud_content($cm, $instance, $context);
                break;

            case 'pdfprotect':
                $html .= self::get_pdfprotect_content($cm, $instance, $context);
                break;

            default:
                // For other activities, try to get content dynamically.
                $html .= self::get_generic_activity_content($cm, $cminfo, $context);
                break;
        }

        $html .= '</div>';

        // Completion status is shown in the sidebar, not in activity content.
        // $html .= self::get_completion_section($cminfo, $course);

        return $html;
    }

    /**
     * Get page module content.
     * Replicates view.php lines 88-99: uses same format options and shows last modified.
     */
    protected static function get_page_content($instance, $context): string {
        // Exact same logic as view.php line 88-93.
        $content = file_rewrite_pluginfile_urls(
            $instance->content,
            'pluginfile.php',
            $context->id,
            'mod_page',
            'content',
            $instance->revision
        );

        // Same format options as view.php line 89-92.
        $formatoptions = new \stdClass;
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;

        $content = format_text($content, $instance->contentformat, $formatoptions);

        $html = '<div class="nexus-page-content generalbox center clearfix">' . $content . '</div>';

        // Show last modified same as view.php line 96-99.
        $options = empty($instance->displayoptions) ? [] : (array) unserialize_array($instance->displayoptions);
        if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
            $strlastmodified = get_string('lastmodified');
            $html .= '<div class="modified text-muted small mt-2">' . $strlastmodified . ': ' . userdate($instance->timemodified) . '</div>';
        }

        return $html;
    }

    /**
     * Get resource (file) content.
     * Replicates view.php logic: uses resource_get_final_display_type() and handles
     * EMBED, FRAME, and other display types like the native module.
     */
    protected static function get_resource_content($cm, $instance, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        require_once($CFG->dirroot . '/mod/resource/locallib.php');
        require_once($CFG->libdir . '/resourcelib.php');

        $fs = get_file_storage();
        // Same query as view.php line 68.
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return '<p>' . get_string('filenotfound', 'resource') . '</p>';
        }

        $file = reset($files);

        // Set mainfile like view.php line 77 does for display type detection.
        $instance->mainfile = $file->get_filename();

        // Use same display type logic as view.php line 78.
        $displaytype = resource_get_final_display_type($instance);

        $mimetype = $file->get_mimetype();
        $filename = $file->get_filename();

        // Build proper file URL with revision like view.php line 93.
        $path = '/' . $context->id . '/mod_resource/content/' . $instance->revision .
                $file->get_filepath() . $file->get_filename();
        $fullurl = \moodle_url::make_file_url('/pluginfile.php', $path, false);
        $downloadurl = \moodle_url::make_file_url('/pluginfile.php', $path, true);

        $html = '<div class="nexus-resource-content">';

        // Handle display options like printintro (view.php embed display).
        $options = empty($instance->displayoptions) ? [] : (array) unserialize_array($instance->displayoptions);
        if (!empty($options['printintro']) && trim(strip_tags($instance->intro))) {
            $html .= '<div class="nexus-resource-intro">' .
                     format_module_intro('resource', $instance, $cm->id, false) . '</div>';
        }

        // Replicate view.php switch statement (lines 98-107).
        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
                // Embed logic similar to resource_display_embed() in locallib.php.
                $title = format_string($instance->name);
                $clicktoopen = get_string('clicktoopen2', 'resource', "<a href=\"$fullurl\">$title</a>");

                // Use core_media_manager for video/audio like resourcelib does.
                $mediamanager = \core_media_manager::instance();
                $embedoptions = [
                    \core_media_manager::OPTION_TRUSTED => true,
                    \core_media_manager::OPTION_BLOCK => true,
                ];

                if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
                    $html .= '<div class="resourcecontent resourceimg">';
                    $html .= '<img class="resourceimage img-fluid" alt="' . s($filename) . '" src="' . $fullurl . '" />';
                    $html .= '</div>';
                } else if (file_mimetype_in_typegroup($mimetype, 'web_video')) {
                    $html .= '<div class="resourcecontent resourcevideo">';
                    $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $title, 0, 0, $embedoptions);
                    $html .= '</div>';
                } else if (file_mimetype_in_typegroup($mimetype, 'web_audio')) {
                    $html .= '<div class="resourcecontent resourceaudio">';
                    $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $title, 0, 0, $embedoptions);
                    $html .= '</div>';
                } else if ($mimetype === 'application/pdf') {
                    $html .= '<div class="resourcecontent resourcepdf">';
                    $html .= '<embed src="' . $fullurl . '" type="application/pdf" width="100%" height="600px" />';
                    $html .= '</div>';
                } else if (file_mimetype_in_typegroup($mimetype, '.htm')) {
                    $html .= '<div class="resourcecontent resourcehtml">';
                    $html .= '<iframe src="' . $fullurl . '" class="w-100" style="height:600px;border:none;"></iframe>';
                    $html .= '</div>';
                } else {
                    $html .= '<div class="resourcecontent">' . $clicktoopen . '</div>';
                }
                break;

            case RESOURCELIB_DISPLAY_FRAME:
                // Frame display - show in iframe like resource_display_frame().
                $html .= '<div class="resourcecontent resourceframe">';
                $html .= '<iframe src="' . $fullurl . '" class="w-100" style="height:600px;border:none;"></iframe>';
                $html .= '</div>';
                break;

            default:
                // For OPEN, NEW, POPUP, DOWNLOAD - show click to open/download links.
                if ($displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
                    $html .= '<div class="resourceworkaround">';
                    $html .= '<a href="' . $downloadurl . '" class="btn btn-primary">';
                    $html .= '<i class="fa fa-download"></i> ' . get_string('download') . ' ' . s($filename);
                    $html .= '</a></div>';
                } else {
                    // OPEN, NEW, POPUP modes - provide link to open.
                    $html .= '<div class="resourceworkaround">';
                    $html .= '<a href="' . $fullurl . '" class="btn btn-primary" target="_blank">';
                    $html .= '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'resource');
                    $html .= '</a></div>';
                }
                break;
        }

        // Show file details like size and type.
        $html .= '<div class="nexus-resource-details text-muted small mt-2">';
        $html .= '<span class="nexus-resource-filename"><i class="fa fa-file"></i> ' . s($filename) . '</span>';
        $html .= ' <span class="nexus-resource-size">(' . display_size($file->get_filesize()) . ')</span>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get URL module content.
     * Replicates view.php logic: uses url_get_full_url() and url_get_final_display_type()
     * to handle EMBED, FRAME, and other display types like the native module.
     */
    protected static function get_url_content($cm, $instance, $course): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/url/lib.php');
        require_once($CFG->dirroot . '/mod/url/locallib.php');
        require_once($CFG->libdir . '/resourcelib.php');

        // Check URL validity like view.php lines 58-64.
        $exturl = trim($instance->externalurl);
        if (empty($exturl) || $exturl === 'http://') {
            return '<div class="alert alert-warning">' . get_string('invalidstoredurl', 'url') . '</div>';
        }

        // Get full URL with parameters substituted like view.php line 75.
        $fullurl = str_replace('&amp;', '&', url_get_full_url($instance, $cm, $course));

        // Determine display type like view.php line 67.
        $displaytype = url_get_final_display_type($instance);

        $html = '<div class="nexus-url-content">';

        // Handle display options.
        $options = empty($instance->displayoptions) ? [] : (array) unserialize_array($instance->displayoptions);

        // Replicate view.php switch statement (lines 96-106).
        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
                // Embed logic similar to url_display_embed() in locallib.php.
                // Detect media type and embed accordingly.
                $mimetype = resourcelib_guess_url_mimetype($fullurl);
                $mediamanager = \core_media_manager::instance();
                $embedoptions = [
                    \core_media_manager::OPTION_TRUSTED => true,
                    \core_media_manager::OPTION_BLOCK => true,
                ];

                if (in_array($mimetype, ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml'])) {
                    // Image - embed directly.
                    $html .= '<div class="urlcontent resourceimg">';
                    $html .= '<img class="img-fluid" alt="' . s($instance->name) . '" src="' . s($fullurl) . '" />';
                    $html .= '</div>';
                } else if (in_array($mimetype, ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-flv', 'video/x-ms-wm'])) {
                    // Video - use media manager.
                    $html .= '<div class="urlcontent resourcevideo">';
                    $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $instance->name, 0, 0, $embedoptions);
                    $html .= '</div>';
                } else if (in_array($mimetype, ['audio/mp3', 'audio/x-realaudio-plugin', 'x-realaudio-plugin'])) {
                    // Audio - use media manager.
                    $html .= '<div class="urlcontent resourceaudio">';
                    $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $instance->name, 0, 0, $embedoptions);
                    $html .= '</div>';
                } else {
                    // Other embeddable content - try iframe.
                    $html .= '<div class="urlcontent resourcegeneral">';
                    $html .= '<iframe src="' . s($fullurl) . '" class="w-100" style="height:600px;border:none;"></iframe>';
                    $html .= '</div>';
                }
                break;

            case RESOURCELIB_DISPLAY_FRAME:
                // Frame display - show in iframe like url_display_frame().
                $html .= '<div class="urlcontent urlframe">';
                $html .= '<iframe src="' . s($fullurl) . '" class="w-100" style="height:600px;border:none;"></iframe>';
                $html .= '</div>';
                break;

            default:
                // For OPEN, NEW, POPUP, DOWNLOAD modes - show button to open.
                $html .= '<div class="urlworkaround">';
                $html .= '<a href="' . s($fullurl) . '" target="_blank" rel="noopener" class="btn btn-primary">';
                $html .= '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'url');
                $html .= '</a></div>';
                break;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get book module content (first visible chapter with TOC).
     * Replicates view.php logic: uses book_preload_chapters() to get proper chapter
     * structure with navigation, hidden status handling, and format_text options.
     */
    protected static function get_book_content($cm, $instance, $context): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        // Use book_preload_chapters() like view.php line 72.
        $chapters = book_preload_chapters($instance);

        if (empty($chapters)) {
            return '<p>' . get_string('nocontent', 'mod_book') . '</p>';
        }

        // Check if user can view hidden chapters.
        $viewhidden = has_capability('mod/book:viewhiddenchapters', $context);

        // Find first visible chapter like view.php lines 82-91.
        $firstchapter = null;
        foreach ($chapters as $ch) {
            if ($viewhidden || !$ch->hidden) {
                $firstchapter = $ch;
                break;
            }
        }

        if (!$firstchapter) {
            return '<p>' . get_string('nocontent', 'mod_book') . '</p>';
        }

        $html = '<div class="nexus-book-content">';

        // Build TOC sidebar like view.php does with book_add_fake_block().
        $html .= '<div class="nexus-book-layout d-flex">';

        // TOC on the left.
        $html .= '<div class="nexus-book-toc me-3" style="min-width:200px;">';
        $html .= '<h5>' . get_string('toc', 'mod_book') . '</h5>';
        $html .= '<ul class="list-unstyled">';

        $chnum = 0;
        foreach ($chapters as $ch) {
            if ($ch->hidden && !$viewhidden) {
                continue;
            }
            $chnum++;

            $isactive = ($ch->id == $firstchapter->id);
            $hidden = $ch->hidden ? ' class="dimmed_text"' : '';
            $activeclass = $isactive ? ' class="font-weight-bold"' : '';

            $chapterurl = new \moodle_url('/mod/book/view.php', ['id' => $cm->id, 'chapterid' => $ch->id]);

            if ($ch->subchapter) {
                $html .= '<li class="ms-3"' . $hidden . '>';
                $html .= '<a href="' . $chapterurl->out() . '"' . $activeclass . '>';
                $html .= format_string($ch->title) . '</a></li>';
            } else {
                $html .= '<li' . $hidden . '>';
                if ($instance->numbering == BOOK_NUM_NUMBERS && isset($ch->number)) {
                    $html .= '<strong>' . $ch->number . '.</strong> ';
                }
                $html .= '<a href="' . $chapterurl->out() . '"' . $activeclass . '>';
                $html .= format_string($ch->title) . '</a></li>';
            }
        }
        $html .= '</ul>';
        $html .= '</div>';

        // Chapter content on the right.
        $html .= '<div class="nexus-book-chapter flex-grow-1">';

        // Show chapter title like view.php lines 132-141.
        $hiddenclass = $firstchapter->hidden ? ' dimmed_text' : '';
        if (!$instance->customtitles) {
            $html .= '<h4 class="' . $hiddenclass . '">' . format_string($firstchapter->title) . '</h4>';
        }

        // Format chapter content like view.php lines 143-146.
        $chaptertext = file_rewrite_pluginfile_urls(
            $firstchapter->content,
            'pluginfile.php',
            $context->id,
            'mod_book',
            'chapter',
            $firstchapter->id
        );
        $html .= '<div class="book_content' . $hiddenclass . '">';
        $html .= format_text($chaptertext, $firstchapter->contentformat, ['noclean' => true, 'overflowdiv' => true, 'context' => $context]);
        $html .= '</div>';

        // Navigation hints.
        $visiblecount = 0;
        foreach ($chapters as $ch) {
            if ($viewhidden || !$ch->hidden) {
                $visiblecount++;
            }
        }
        if ($visiblecount > 1) {
            $html .= '<div class="nexus-book-nav mt-3 text-muted small">';
            $html .= '<i class="fa fa-book"></i> ' . $visiblecount . ' ' . get_string('chapters', 'mod_book');
            $html .= ' - ' . get_string('toc', 'mod_book');
            $html .= '</div>';
        }

        $html .= '</div>'; // End chapter content.
        $html .= '</div>'; // End layout.
        $html .= '</div>'; // End nexus-book-content.

        return $html;
    }

    /**
     * Get quiz module content.
     */
    protected static function get_quiz_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');

        $html = '<div class="nexus-quiz-content">';

        // Check user capabilities.
        $canpreview = has_capability('mod/quiz:preview', $context);
        $canmanage = has_capability('mod/quiz:manage', $context);
        $canviewreports = has_capability('mod/quiz:viewreports', $context);

        if ($canmanage || $canviewreports) {
            // Teacher view.
            $html .= self::get_quiz_teacher_view($cm, $instance, $context);
        } else {
            // Student view.
            $html .= self::get_quiz_student_view($cm, $instance, $context, $canpreview);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get quiz student view.
     */
    protected static function get_quiz_student_view($cm, $instance, $context, $canpreview = false): string {
        global $DB, $USER;

        $html = '';

        // Quiz info.
        $html .= '<div class="nexus-quiz-info card mb-3">';
        $html .= '<div class="card-body">';

        // Time limit.
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'quiz') . ':</strong> ';
            $html .= format_time($instance->timelimit) . '</p>';
        }

        // Attempts allowed.
        if ($instance->attempts) {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attemptsallowed', 'quiz') . ':</strong> ';
            $html .= $instance->attempts . '</p>';
        } else {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attemptsallowed', 'quiz') . ':</strong> ';
            $html .= get_string('unlimited') . '</p>';
        }

        // Grading method.
        $gradingmethods = [
            QUIZ_GRADEHIGHEST => get_string('gradehighest', 'quiz'),
            QUIZ_GRADEAVERAGE => get_string('gradeaverage', 'quiz'),
            QUIZ_ATTEMPTFIRST => get_string('attemptfirst', 'quiz'),
            QUIZ_ATTEMPTLAST => get_string('attemptlast', 'quiz'),
        ];
        if (isset($gradingmethods[$instance->grademethod])) {
            $html .= '<p><i class="fa fa-calculator"></i> <strong>' . get_string('grademethod', 'quiz') . ':</strong> ';
            $html .= $gradingmethods[$instance->grademethod] . '</p>';
        }

        // Grade to pass - same logic as view.php.
        $gradeitem = $DB->get_record('grade_items', [
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $instance->id
        ]);
        if ($gradeitem && !empty($gradeitem->gradepass) && $gradeitem->gradepass > 0) {
            $html .= '<p><i class="fa fa-graduation-cap"></i> <strong>' . get_string('gradetopass', 'grades') . ':</strong> ';
            $html .= format_float($gradeitem->gradepass, $instance->decimalpoints) . ' / ' .
                     format_float($instance->grade, $instance->decimalpoints) . '</p>';
        }

        $html .= '</div></div>';

        // User attempts.
        $attempts = quiz_get_user_attempts($instance->id, $USER->id, 'finished', true);

        // Show overall grade if user has attempts.
        if ($attempts && !$canpreview) {
            $mygrade = quiz_get_best_grade($instance, $USER->id);
            if ($mygrade !== null) {
                $html .= '<div class="nexus-quiz-grade alert alert-info mb-3">';
                $html .= '<strong>' . get_string('yourfinalgradeis', 'quiz',
                    format_float($mygrade, $instance->decimalpoints) . ' / ' .
                    format_float($instance->grade, $instance->decimalpoints)) . '</strong>';
                $html .= '</div>';
            }
        }

        if ($attempts) {
            $html .= '<div class="nexus-quiz-attempts mb-3">';
            $html .= '<h5>' . get_string('yourattempts', 'quiz') . '</h5>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>' . get_string('attempt', 'quiz') . '</th>';
            $html .= '<th>' . get_string('state', 'quiz') . '</th>';
            $html .= '<th>' . get_string('marks', 'quiz') . '</th>';
            $html .= '<th>' . get_string('grade', 'grades') . '</th></tr></thead>';
            $html .= '<tbody>';

            $attemptnum = 1;
            foreach ($attempts as $attempt) {
                $html .= '<tr>';
                $html .= '<td>' . $attemptnum++ . '</td>';
                $html .= '<td>' . quiz_attempt_state_name($attempt->state) . '</td>';
                // Raw marks from attempt.
                if ($attempt->sumgrades !== null) {
                    $html .= '<td>' . format_float($attempt->sumgrades, $instance->decimalpoints) . ' / ' .
                             format_float($instance->sumgrades, $instance->decimalpoints) . '</td>';
                    // Scaled grade.
                    $grade = quiz_rescale_grade($attempt->sumgrades, $instance, false);
                    $html .= '<td>' . format_float($grade, $instance->decimalpoints) . ' / ' .
                             format_float($instance->grade, $instance->decimalpoints) . '</td>';
                } else {
                    $html .= '<td>-</td><td>-</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
        }

        // Start quiz button.
        $html .= '<div class="nexus-quiz-action text-center">';
        $numattempts = count($attempts);
        $canstart = true;
        if ($instance->attempts && $numattempts >= $instance->attempts && !$canpreview) {
            $canstart = false;
            $html .= '<div class="alert alert-warning">' . get_string('nomoreattempts', 'quiz') . '</div>';
        }

        if ($canstart) {
            // Link to quiz view page (Moodle will handle the attempt start).
            $viewurl = new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
            $buttontext = $numattempts > 0 ? get_string('reattemptquiz', 'quiz') : get_string('attemptquiznow', 'quiz');
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Get quiz teacher view.
     */
    protected static function get_quiz_teacher_view($cm, $instance, $context): string {
        global $DB;

        $html = '';

        // Quiz info card.
        $html .= '<div class="nexus-quiz-info card mb-3">';
        $html .= '<div class="card-header"><strong>' . get_string('modulename', 'quiz') . '</strong></div>';
        $html .= '<div class="card-body">';

        // Time limit.
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'quiz') . ':</strong> ';
            $html .= format_time($instance->timelimit) . '</p>';
        }

        // Number of questions.
        $questioncount = $DB->count_records('quiz_slots', ['quizid' => $instance->id]);
        $html .= '<p><i class="fa fa-question-circle"></i> <strong>' . get_string('numquestionsx', 'quiz', $questioncount) . '</strong></p>';

        // Total marks (sumgrades = sum of question marks, grade = maximum grade the quiz is scaled to).
        $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('totalmarks', 'quiz') . ':</strong> ';
        $html .= format_float($instance->sumgrades, 2) . '</p>';

        // Maximum grade.
        if ($instance->grade > 0) {
            $html .= '<p><i class="fa fa-star-o"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ';
            $html .= format_float($instance->grade, 2) . '</p>';

            // Get grade to pass from grade_items table.
            $gradeitem = $DB->get_record('grade_items', [
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $instance->id
            ]);
            if ($gradeitem && !empty($gradeitem->gradepass) && $gradeitem->gradepass > 0) {
                $html .= '<p><i class="fa fa-graduation-cap"></i> <strong>' . get_string('gradetopass', 'grades') . ':</strong> ';
                $html .= format_float($gradeitem->gradepass, 2) . '</p>';
            }
        }

        $html .= '</div></div>';

        // Attempts summary.
        $html .= '<div class="nexus-quiz-summary card mb-3">';
        $html .= '<div class="card-header"><strong>' . get_string('attempts', 'quiz') . '</strong></div>';
        $html .= '<div class="card-body">';

        // Count total attempts.
        $totalattempts = $DB->count_records('quiz_attempts', ['quiz' => $instance->id, 'preview' => 0]);
        $html .= '<p><i class="fa fa-list"></i> <strong>' . get_string('attemptsnum', 'quiz', $totalattempts) . '</strong></p>';

        // Count unique users who attempted.
        $uniqueusers = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT userid) FROM {quiz_attempts} WHERE quiz = ? AND preview = 0",
            [$instance->id]
        );
        $html .= '<p><i class="fa fa-users"></i> <strong>' . get_string('attemptsnum', 'quiz', $uniqueusers) . '</strong></p>';

        // Average grade.
        $avggrade = $DB->get_field_sql(
            "SELECT AVG(sumgrades) FROM {quiz_attempts} WHERE quiz = ? AND preview = 0 AND state = 'finished'",
            [$instance->id]
        );
        if ($avggrade !== false && $avggrade !== null) {
            $avggrade = quiz_rescale_grade($avggrade, $instance, false);
            $html .= '<p><i class="fa fa-bar-chart"></i> <strong>' . get_string('averagegrade', 'quiz') . ':</strong> ';
            $html .= format_float($avggrade, $instance->decimalpoints) . ' / ' . format_float($instance->grade, $instance->decimalpoints) . '</p>';
        }

        $html .= '</div></div>';

        // Actions.
        $html .= '<div class="nexus-quiz-actions text-center">';

        // Preview quiz.
        $previewurl = new \moodle_url('/mod/quiz/startattempt.php', ['cmid' => $cm->id, 'sesskey' => sesskey()]);
        $html .= '<a href="' . (new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]))->out() . '" class="btn btn-info btn-lg me-2 mb-2">';
        $html .= '<i class="fa fa-eye"></i> ' . get_string('preview', 'quiz') . '</a>';

        // View results.
        $resultsurl = new \moodle_url('/mod/quiz/report.php', ['id' => $cm->id, 'mode' => 'overview']);
        $html .= '<a href="' . $resultsurl->out() . '" class="btn btn-primary btn-lg me-2 mb-2">';
        $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('results', 'quiz') . '</a>';

        // Edit quiz.
        if (has_capability('mod/quiz:manage', $context)) {
            $editurl = new \moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]);
            $html .= '<a href="' . $editurl->out() . '" class="btn btn-secondary btn-lg mb-2">';
            $html .= '<i class="fa fa-pencil"></i> ' . get_string('editquiz', 'quiz') . '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get assign module content.
     */
    protected static function get_assign_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $html = '<div class="nexus-assign-content">';
        $assign = new \assign($context, $cm, $course);

        // Check user capabilities.
        $cangrade = has_capability('mod/assign:grade', $context);
        $cansubmit = has_capability('mod/assign:submit', $context);

        if ($cangrade) {
            // Teacher/Grader view.
            $html .= self::get_assign_teacher_view($assign, $cm, $instance, $context);
        } else {
            // Student view.
            $html .= self::get_assign_student_view($assign, $cm, $instance, $context);
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get assign student view.
     */
    protected static function get_assign_student_view($assign, $cm, $instance, $context): string {
        global $USER;

        $html = '';

        // Assignment info.
        $html .= '<div class="nexus-assign-info card mb-3">';
        $html .= '<div class="card-body">';

        // Due date.
        if ($instance->duedate) {
            $dueclass = ($instance->duedate < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('duedate', 'assign') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($instance->duedate) . '</span></p>';
        }

        // Cut-off date.
        if ($instance->cutoffdate) {
            $html .= '<p><i class="fa fa-ban"></i> <strong>' . get_string('cutoffdate', 'assign') . ':</strong> ';
            $html .= userdate($instance->cutoffdate) . '</p>';
        }

        // Submission status.
        $submission = $assign->get_user_submission($USER->id, false);

        if ($submission) {
            $statusclass = ($submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) ? 'badge-success bg-success' : 'badge-warning bg-warning';
            $html .= '<p><i class="fa fa-check-square-o"></i> <strong>' . get_string('submissionstatus', 'assign') . ':</strong> ';
            $html .= '<span class="badge ' . $statusclass . '">' . get_string('submissionstatus_' . $submission->status, 'assign') . '</span></p>';

            // Last modified.
            if ($submission->timemodified) {
                $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timemodified', 'assign') . ':</strong> ';
                $html .= userdate($submission->timemodified) . '</p>';
            }
        } else {
            $html .= '<p><i class="fa fa-exclamation-circle"></i> <strong>' . get_string('submissionstatus', 'assign') . ':</strong> ';
            $html .= '<span class="badge badge-secondary bg-secondary">' . get_string('nosubmission', 'assign') . '</span></p>';
        }

        // Grade.
        $grade = $assign->get_user_grade($USER->id, false);
        if ($grade && $grade->grade !== null && $grade->grade >= 0) {
            $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ';
            $html .= format_float($grade->grade, 2) . ' / ' . format_float($instance->grade, 2) . '</p>';

            // Feedback.
            if (!empty($grade->feedbacktext)) {
                $html .= '<div class="alert alert-info mt-2">';
                $html .= '<strong>' . get_string('feedback', 'assign') . ':</strong><br>';
                $html .= format_text($grade->feedbacktext, FORMAT_HTML);
                $html .= '</div>';
            }
        }

        $html .= '</div></div>';

        // Action button - just link to the activity page (Moodle handles the rest).
        $html .= '<div class="nexus-assign-actions text-center">';
        $viewurl = new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]);

        if (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-upload"></i> ' . get_string('addsubmission', 'assign') . '</a>';
        } else {
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-secondary btn-lg">';
            $html .= '<i class="fa fa-eye"></i> ' . get_string('viewsubmission', 'assign') . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Get assign teacher view.
     */
    protected static function get_assign_teacher_view($assign, $cm, $instance, $context): string {
        global $DB;

        $html = '';

        // Grading summary.
        $html .= '<div class="nexus-assign-grading card mb-3">';
        $html .= '<div class="card-header"><strong>' . get_string('gradingsummary', 'assign') . '</strong></div>';
        $html .= '<div class="card-body">';

        // Get grading stats.
        $course = $assign->get_course();
        $coursecontext = \context_course::instance($course->id);

        // Count participants.
        $participants = count_enrolled_users($coursecontext, 'mod/assign:submit');
        $html .= '<p><i class="fa fa-users"></i> <strong>' . get_string('numberofparticipants', 'assign') . ':</strong> ' . $participants . '</p>';

        // Count submissions.
        $submissioncount = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT s.userid)
             FROM {assign_submission} s
             WHERE s.assignment = ? AND s.status = ? AND s.latest = 1",
            [$instance->id, ASSIGN_SUBMISSION_STATUS_SUBMITTED]
        );
        $html .= '<p><i class="fa fa-paper-plane"></i> <strong>' . get_string('numberofsubmittedassignments', 'assign') . ':</strong> ' . $submissioncount . '</p>';

        // Count needing grading.
        $needsgrading = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT s.userid)
             FROM {assign_submission} s
             LEFT JOIN {assign_grades} g ON s.assignment = g.assignment AND s.userid = g.userid AND g.attemptnumber = s.attemptnumber
             WHERE s.assignment = ? AND s.status = ? AND s.latest = 1 AND (g.id IS NULL OR s.timemodified > g.timemodified)",
            [$instance->id, ASSIGN_SUBMISSION_STATUS_SUBMITTED]
        );
        $html .= '<p><i class="fa fa-hourglass-half"></i> <strong>' . get_string('numberofsubmissionsneedgrading', 'assign') . ':</strong> ' . $needsgrading . '</p>';

        // Due date.
        if ($instance->duedate) {
            $dueclass = ($instance->duedate < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('duedate', 'assign') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($instance->duedate) . '</span></p>';
        }

        // Time remaining.
        if ($instance->duedate) {
            $timeremaining = $instance->duedate - time();
            if ($timeremaining > 0) {
                $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timeremaining', 'assign') . ':</strong> ';
                $html .= format_time($timeremaining) . '</p>';
            } else {
                $html .= '<p class="text-danger"><i class="fa fa-clock-o"></i> <strong>' . get_string('assignmentisdue', 'assign') . '</strong></p>';
            }
        }

        $html .= '</div></div>';

        // Grading actions.
        $html .= '<div class="nexus-assign-actions text-center">';

        // Grade button.
        $gradeurl = new \moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grading']);
        $html .= '<a href="' . $gradeurl->out() . '" class="btn btn-primary btn-lg me-2 mb-2">';
        $html .= '<i class="fa fa-pencil"></i> ' . get_string('viewgrading', 'assign') . '</a>';

        // View all submissions.
        $viewurl = new \moodle_url('/mod/assign/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-secondary btn-lg mb-2">';
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewgrading', 'assign') . '</a>';

        $html .= '</div>';

        return $html;
    }

    /**
     * Get forum module content - replicates view.php display.
     */
    protected static function get_forum_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $html = '<div class="nexus-forum-content">';

        // Forum type info.
        $forumtypes = forum_get_forum_types();
        $html .= '<div class="nexus-forum-info mb-3">';
        if (isset($forumtypes[$instance->type])) {
            $html .= '<span class="badge bg-secondary">' . $forumtypes[$instance->type] . '</span>';
        }
        $html .= '</div>';

        // Get discussions with full details like view.php.
        // Note: d.usermodified stores the USER ID who last modified, not a post ID.
        // We use a subquery to get the actual last post's user.
        $discussions = $DB->get_records_sql(
            "SELECT d.id, d.name, d.timemodified, d.pinned, d.forum, d.firstpost,
                    d.userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email,
                    (SELECT COUNT(*) FROM {forum_posts} p WHERE p.discussion = d.id) as replycount,
                    (SELECT MAX(p.modified) FROM {forum_posts} p WHERE p.discussion = d.id) as lastposttime,
                    d.usermodified as lastuserid
             FROM {forum_discussions} d
             JOIN {user} u ON u.id = d.userid
             WHERE d.forum = ?
             ORDER BY d.pinned DESC, d.timemodified DESC",
            [$instance->id]
        );

        // Get last poster names separately to avoid complex subqueries.
        if ($discussions) {
            $userids = array_unique(array_filter(array_column($discussions, 'lastuserid')));
            if ($userids) {
                list($insql, $params) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
                $lastusers = $DB->get_records_select('user', "id $insql", $params, '', 'id, firstname, lastname');
                foreach ($discussions as $discussion) {
                    if (!empty($discussion->lastuserid) && isset($lastusers[$discussion->lastuserid])) {
                        $discussion->lastfirstname = $lastusers[$discussion->lastuserid]->firstname;
                        $discussion->lastlastname = $lastusers[$discussion->lastuserid]->lastname;
                    } else {
                        $discussion->lastfirstname = '';
                        $discussion->lastlastname = '';
                    }
                }
            }
        }

        // Count total for display.
        $totaldiscussions = count($discussions);
        $html .= '<div class="nexus-forum-stats mb-3">';
        $html .= '<small class="text-muted">';
        $html .= '<i class="fa fa-comments"></i> ' . $totaldiscussions . ' ' . get_string('discussions', 'forum');
        $html .= '</small></div>';

        if ($discussions) {
            $html .= '<div class="nexus-forum-discussions list-group">';

            $showncount = 0;
            foreach ($discussions as $discussion) {
                if ($showncount >= 15) {
                    break; // Limit to 15 discussions in panel.
                }

                $discussionurl = new \moodle_url('/mod/forum/discuss.php', ['d' => $discussion->id]);
                $replies = max(0, $discussion->replycount - 1);

                $html .= '<div class="list-group-item list-group-item-action nexus-discussion';
                if ($discussion->pinned) {
                    $html .= ' border-warning bg-light';
                }
                $html .= '">';

                // Pinned indicator.
                if ($discussion->pinned) {
                    $html .= '<span class="badge bg-warning text-dark float-end"><i class="fa fa-thumb-tack"></i></span>';
                }

                // Discussion title.
                $html .= '<h6 class="mb-1"><a href="' . $discussionurl->out() . '" class="text-decoration-none">';
                $html .= format_string($discussion->name) . '</a></h6>';

                // Started by.
                $html .= '<p class="mb-1 small text-muted">';
                $html .= get_string('startedby', 'forum') . ' <strong>' . fullname($discussion) . '</strong>';
                $html .= '</p>';

                // Stats row.
                $html .= '<div class="d-flex justify-content-between align-items-center">';
                $html .= '<small class="text-muted">';
                $html .= '<i class="fa fa-reply"></i> ' . $replies . ' ' . get_string('replies', 'forum');
                $html .= '</small>';

                // Last post info.
                if ($discussion->lastposttime) {
                    $html .= '<small class="text-muted">';
                    $html .= get_string('lastpost', 'forum') . ': ';
                    $html .= userdate($discussion->lastposttime, get_string('strftimedatetimeshort', 'langconfig'));
                    if ($discussion->lastfirstname) {
                        $html .= ' ' . get_string('by') . ' ' . $discussion->lastfirstname . ' ' . $discussion->lastlastname;
                    }
                    $html .= '</small>';
                }
                $html .= '</div>';

                $html .= '</div>';
                $showncount++;
            }
            $html .= '</div>';

            // Show more link if there are more discussions.
            if ($totaldiscussions > 15) {
                $html .= '<div class="text-center mt-2">';
                $viewallurl = new \moodle_url('/mod/forum/view.php', ['id' => $cm->id]);
                $html .= '<a href="' . $viewallurl->out() . '" class="btn btn-sm btn-outline-secondary">';
                $html .= get_string('viewalldiscussions', 'forum', $totaldiscussions) . '</a>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('nodiscussions', 'forum');
            $html .= '</div>';
        }

        // Add discussion button.
        $html .= '<div class="nexus-forum-actions text-center mt-3">';
        if (forum_user_can_post_discussion($instance, null, -1, $cm, $context)) {
            $addurl = new \moodle_url('/mod/forum/post.php', ['forum' => $instance->id]);
            $html .= '<a href="' . $addurl->out() . '" class="btn btn-primary">';
            $html .= '<i class="fa fa-plus"></i> ' . get_string('addanewdiscussion', 'forum') . '</a>';
        }

        // View all button.
        $viewurl = new \moodle_url('/mod/forum/view.php', ['id' => $cm->id]);
        $html .= ' <a href="' . $viewurl->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewalldiscussions', 'forum') . '</a>';

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get lesson module content - replicates view.php display.
     */
    protected static function get_lesson_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG, $PAGE;
        require_once($CFG->dirroot . '/mod/lesson/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $html = '<div class="nexus-lesson-content">';

        // Create lesson object.
        $lesson = new \lesson($instance, $cm, $course);

        // Get user's lesson timer/attempt info.
        $canmanage = has_capability('mod/lesson:manage', $context);

        // Check if lesson is available.
        $available = true;
        $availablemessage = '';

        // Time restrictions.
        if ($instance->available > 0 && time() < $instance->available) {
            $available = false;
            $availablemessage = get_string('lessonopen', 'lesson', userdate($instance->available));
        } else if ($instance->deadline > 0 && time() > $instance->deadline) {
            $available = false;
            $availablemessage = get_string('lessonclosed', 'lesson', userdate($instance->deadline));
        }

        // Lesson info card.
        $html .= '<div class="nexus-lesson-info card mb-3">';
        $html .= '<div class="card-body">';

        // Number of pages.
        $pagecount = $DB->count_records('lesson_pages', ['lessonid' => $instance->id]);
        $html .= '<p><i class="fa fa-file-text-o"></i> <strong>' . get_string('pages', 'lesson') . ':</strong> ';
        $html .= $pagecount . ' ' . get_string('pages', 'lesson') . '</p>';

        // Time limit.
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'lesson') . ':</strong> ';
            $html .= format_time($instance->timelimit) . '</p>';
        }

        // Availability dates.
        if ($instance->available > 0) {
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('available', 'lesson') . ':</strong> ';
            $html .= userdate($instance->available) . '</p>';
        }
        if ($instance->deadline > 0) {
            $dueclass = ($instance->deadline < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('deadline', 'lesson') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($instance->deadline) . '</span></p>';
        }

        // Max attempts.
        if ($instance->maxattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('maximumnumberofattempts', 'lesson') . ':</strong> ';
            $html .= $instance->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        // User progress - only for non-managers.
        if (!$canmanage) {
            // Get user attempts.
            $attempts = $DB->get_records('lesson_grades', ['lessonid' => $instance->id, 'userid' => $USER->id], 'completed DESC');

            if ($attempts) {
                $html .= '<div class="nexus-lesson-attempts card mb-3">';
                $html .= '<div class="card-header"><strong>' . get_string('attempts', 'lesson') . '</strong></div>';
                $html .= '<div class="card-body">';

                $html .= '<table class="table table-sm">';
                $html .= '<thead><tr>';
                $html .= '<th>' . get_string('attempt', 'lesson') . '</th>';
                $html .= '<th>' . get_string('grade', 'grades') . '</th>';
                $html .= '<th>' . get_string('completed', 'lesson') . '</th>';
                $html .= '</tr></thead><tbody>';

                $attemptnum = count($attempts);
                $bestgrade = 0;
                foreach ($attempts as $attempt) {
                    if ($attempt->grade > $bestgrade) {
                        $bestgrade = $attempt->grade;
                    }
                    $html .= '<tr>';
                    $html .= '<td>' . $attemptnum-- . '</td>';
                    $html .= '<td>' . format_float($attempt->grade, 1) . '%</td>';
                    $html .= '<td>' . userdate($attempt->completed, get_string('strftimedatetimeshort', 'langconfig')) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';

                $html .= '<p class="mb-0"><strong>' . get_string('bestgrade', 'lesson') . ':</strong> ';
                $html .= format_float($bestgrade, 1) . '%</p>';
                $html .= '</div></div>';

                // Check if can retake.
                if ($instance->maxattempts > 0 && count($attempts) >= $instance->maxattempts) {
                    $available = false;
                    $availablemessage = get_string('maximumnumberofattemptsreached', 'lesson');
                }
            }

            // Check for incomplete attempt (in progress).
            $timer = $DB->get_record('lesson_timer', [
                'lessonid' => $instance->id,
                'userid' => $USER->id,
                'completed' => 0
            ]);

            if ($timer) {
                $html .= '<div class="alert alert-info">';
                $html .= '<i class="fa fa-info-circle"></i> ' . get_string('youhaveseen', 'lesson');
                $html .= '</div>';
            }
        }

        // Availability message.
        if (!$available && !$canmanage) {
            $html .= '<div class="alert alert-warning">';
            $html .= '<i class="fa fa-exclamation-triangle"></i> ' . $availablemessage;
            $html .= '</div>';
        }

        // Action buttons.
        $html .= '<div class="nexus-lesson-action text-center">';

        if ($available || $canmanage) {
            $url = new \moodle_url('/mod/lesson/view.php', ['id' => $cm->id]);

            // Determine button text.
            $buttontext = get_string('startlesson', 'lesson');
            $buttonicon = 'fa-play-circle';

            if (isset($timer) && $timer) {
                $buttontext = get_string('continuelesson', 'lesson');
                $buttonicon = 'fa-forward';
            } else if (isset($attempts) && $attempts) {
                $buttontext = get_string('retakelesson', 'lesson');
                $buttonicon = 'fa-refresh';
            }

            if ($canmanage) {
                $buttontext = get_string('preview', 'lesson');
                $buttonicon = 'fa-eye';
            }

            $html .= '<a href="' . $url->out() . '" class="btn btn-primary btn-lg me-2">';
            $html .= '<i class="fa ' . $buttonicon . '"></i> ' . $buttontext . '</a>';
        }

        // Edit button for managers.
        if ($canmanage) {
            $editurl = new \moodle_url('/mod/lesson/edit.php', ['id' => $cm->id]);
            $html .= '<a href="' . $editurl->out() . '" class="btn btn-secondary btn-lg">';
            $html .= '<i class="fa fa-pencil"></i> ' . get_string('edit', 'lesson') . '</a>';
        }

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get choice module content - replicates view.php display.
     */
    protected static function get_choice_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/choice/lib.php');

        $html = '<div class="nexus-choice-content">';

        // Check availability.
        $timenow = time();
        $isopen = true;
        $isclosed = false;

        if ($instance->timeopen > 0 && $timenow < $instance->timeopen) {
            $isopen = false;
            $html .= '<div class="alert alert-warning">';
            $html .= '<i class="fa fa-clock-o"></i> ' . get_string('notopenyet', 'choice', userdate($instance->timeopen));
            $html .= '</div>';
        }

        if ($instance->timeclose > 0 && $timenow > $instance->timeclose) {
            $isclosed = true;
            $html .= '<div class="alert alert-warning">';
            $html .= '<i class="fa fa-lock"></i> ' . get_string('expired', 'choice');
            $html .= '</div>';
        }

        // Show remaining time if applicable.
        if ($instance->timeclose > 0 && $timenow < $instance->timeclose && $isopen) {
            $html .= '<div class="alert alert-info mb-3">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('choiceclose', 'choice') . ': ';
            $html .= userdate($instance->timeclose);
            $html .= '</div>';
        }

        // Get options with limit info.
        $options = $DB->get_records('choice_options', ['choiceid' => $instance->id], 'id');

        // Get response counts for limits.
        $optionids = array_keys($options);
        if ($optionids) {
            $sql = "SELECT optionid, COUNT(*) as count
                    FROM {choice_answers}
                    WHERE choiceid = ?
                    GROUP BY optionid";
            $responsecounts = $DB->get_records_sql_menu($sql, [$instance->id]);
        } else {
            $responsecounts = [];
        }

        // Get user's answers.
        $answers = $DB->get_records('choice_answers', ['choiceid' => $instance->id, 'userid' => $USER->id]);
        $useranswers = [];
        foreach ($answers as $answer) {
            $useranswers[$answer->optionid] = $answer;
        }
        $hasanswered = !empty($useranswers);

        // Determine if user can vote.
        $canchoose = has_capability('mod/choice:choose', $context);
        $canupdate = $instance->allowupdate && $hasanswered && $isopen && !$isclosed;
        $canmakechoice = $canchoose && $isopen && !$isclosed && (!$hasanswered || $canupdate);

        if ($hasanswered) {
            // User has already answered - show their selection(s).
            $html .= '<div class="alert alert-success mb-3">';
            $html .= '<i class="fa fa-check-circle"></i> <strong>' . get_string('yourselection', 'choice') . ':</strong><br>';
            foreach ($useranswers as $optionid => $answer) {
                if (isset($options[$optionid])) {
                    $html .= '&bull; ' . format_string($options[$optionid]->text) . '<br>';
                }
            }
            $html .= '</div>';

            // Update allowed message.
            if ($canupdate) {
                $html .= '<div class="alert alert-info">';
                $html .= '<i class="fa fa-edit"></i> ' . get_string('allowupdate', 'choice');
                $html .= '</div>';
            }
        }

        // Show voting form if can make choice.
        if ($canmakechoice) {
            $html .= '<form method="post" action="' . (new \moodle_url('/mod/choice/view.php', ['id' => $cm->id]))->out() . '" class="nexus-choice-form">';
            $html .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            $html .= '<input type="hidden" name="action" value="makechoice">';
            $html .= '<input type="hidden" name="id" value="' . $cm->id . '">';

            $html .= '<div class="nexus-choice-options list-group mb-3">';

            $inputtype = $instance->allowmultiple ? 'checkbox' : 'radio';
            $inputname = $instance->allowmultiple ? 'answer[]' : 'answer';

            foreach ($options as $option) {
                $responsecount = isset($responsecounts[$option->id]) ? $responsecounts[$option->id] : 0;
                $isfull = ($instance->limitanswers && $option->maxanswers > 0 && $responsecount >= $option->maxanswers);
                $isselected = isset($useranswers[$option->id]);
                $disabled = $isfull && !$isselected ? 'disabled' : '';

                $html .= '<label class="list-group-item d-flex gap-2' . ($isfull && !$isselected ? ' list-group-item-secondary' : '') . '">';
                $html .= '<input class="form-check-input flex-shrink-0" type="' . $inputtype . '" ';
                $html .= 'name="' . $inputname . '" value="' . $option->id . '" ' . $disabled;
                if ($isselected) {
                    $html .= ' checked';
                }
                $html .= '>';
                $html .= '<span class="flex-grow-1">';
                $html .= format_string($option->text);

                // Show limit info.
                if ($instance->limitanswers && $option->maxanswers > 0) {
                    $remaining = max(0, $option->maxanswers - $responsecount);
                    if ($isfull) {
                        $html .= ' <span class="badge bg-danger">' . get_string('full', 'choice') . '</span>';
                    } else {
                        $html .= ' <span class="badge bg-secondary">' . $remaining . ' ' . get_string('spaceleft', 'choice') . '</span>';
                    }
                }
                $html .= '</span>';
                $html .= '</label>';
            }
            $html .= '</div>';

            $html .= '<div class="text-center">';
            $buttontext = $hasanswered ? get_string('updatechoice', 'choice') : get_string('savemychoice', 'choice');
            $html .= '<button type="submit" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-check"></i> ' . $buttontext . '</button>';
            $html .= '</div>';
            $html .= '</form>';
        }

        // Determine if results should be shown.
        $canviewresults = choice_can_view_results($instance, $hasanswered, $timenow);

        if ($canviewresults) {
            $html .= self::get_choice_results($instance, $options, $responsecounts);
        } else if (!$canmakechoice && !$hasanswered && $isopen && !$isclosed) {
            // Can't choose and hasn't answered.
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('havetologin', 'choice');
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get choice results - displays bar chart of responses.
     */
    protected static function get_choice_results($instance, $options, $responsecounts = null): string {
        global $DB;

        $html = '<div class="nexus-choice-results mt-3">';
        $html .= '<h5><i class="fa fa-bar-chart"></i> ' . get_string('responses', 'choice') . '</h5>';

        // Get counts if not provided.
        if ($responsecounts === null) {
            $sql = "SELECT optionid, COUNT(*) as count
                    FROM {choice_answers}
                    WHERE choiceid = ?
                    GROUP BY optionid";
            $responsecounts = $DB->get_records_sql_menu($sql, [$instance->id]);
        }

        $totalanswers = array_sum($responsecounts);

        $html .= '<div class="nexus-choice-chart">';
        foreach ($options as $option) {
            $count = isset($responsecounts[$option->id]) ? (int)$responsecounts[$option->id] : 0;
            $percent = $totalanswers > 0 ? round(($count / $totalanswers) * 100) : 0;

            $html .= '<div class="mb-3">';
            $html .= '<div class="d-flex justify-content-between mb-1">';
            $html .= '<span class="fw-medium">' . format_string($option->text) . '</span>';
            $html .= '<span class="text-muted">' . $count . ' (' . $percent . '%)</span>';
            $html .= '</div>';
            $html .= '<div class="progress" style="height: 24px;">';
            $html .= '<div class="progress-bar bg-primary" role="progressbar" style="width: ' . $percent . '%" ';
            $html .= 'aria-valuenow="' . $percent . '" aria-valuemin="0" aria-valuemax="100">';
            if ($percent >= 10) {
                $html .= $percent . '%';
            }
            $html .= '</div>';
            $html .= '</div></div>';
        }
        $html .= '</div>';

        // Total responses.
        $html .= '<p class="text-muted mt-2"><small>';
        $html .= '<i class="fa fa-users"></i> ' . get_string('numberofuser', 'choice') . ': ' . $totalanswers;
        $html .= '</small></p>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get feedback module content - replicates view.php display.
     */
    protected static function get_feedback_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/feedback/lib.php');

        $html = '<div class="nexus-feedback-content">';

        // Check capabilities.
        $canedititems = has_capability('mod/feedback:edititems', $context);
        $canviewreports = has_capability('mod/feedback:viewreports', $context);
        $cancomplete = has_capability('mod/feedback:complete', $context);

        // Check availability.
        $timenow = time();
        $isopen = true;

        if ($instance->timeopen > 0 && $timenow < $instance->timeopen) {
            $isopen = false;
            if (!$canedititems) {
                $html .= '<div class="alert alert-warning">';
                $html .= '<i class="fa fa-clock-o"></i> ' . get_string('feedback_is_not_open', 'feedback');
                $html .= ' (' . userdate($instance->timeopen) . ')';
                $html .= '</div>';
            }
        }

        if ($instance->timeclose > 0 && $timenow > $instance->timeclose) {
            $isopen = false;
            if (!$canedititems) {
                $html .= '<div class="alert alert-warning">';
                $html .= '<i class="fa fa-lock"></i> ' . get_string('feedback_is_not_open', 'feedback');
                $html .= '</div>';
            }
        }

        // Check if user has completed.
        $completed = $DB->get_record('feedback_completed', ['feedback' => $instance->id, 'userid' => $USER->id]);

        // Show teacher/instructor summary.
        if ($canedititems || $canviewreports) {
            $html .= '<div class="nexus-feedback-summary card mb-3">';
            $html .= '<div class="card-header"><strong>' . get_string('overview', 'feedback') . '</strong></div>';
            $html .= '<div class="card-body">';

            // Count items.
            $itemcount = $DB->count_records('feedback_item', ['feedback' => $instance->id, 'hasvalue' => 1]);
            $html .= '<p><i class="fa fa-list"></i> <strong>' . get_string('questions', 'feedback') . ':</strong> ' . $itemcount . '</p>';

            // Count completed responses.
            $completedcount = $DB->count_records('feedback_completed', ['feedback' => $instance->id]);
            $html .= '<p><i class="fa fa-check-circle"></i> <strong>' . get_string('completed_feedbacks', 'feedback') . ':</strong> ' . $completedcount . '</p>';

            // Count in progress.
            $inprogresscount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT userid)
                 FROM {feedback_completedtmp}
                 WHERE feedback = ?",
                [$instance->id]
            );
            if ($inprogresscount > 0) {
                $html .= '<p><i class="fa fa-spinner"></i> <strong>' . get_string('started', 'feedback') . ':</strong> ' . $inprogresscount . '</p>';
            }

            // Availability dates.
            if ($instance->timeopen > 0) {
                $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('feedbackopen', 'feedback') . ':</strong> ';
                $html .= userdate($instance->timeopen) . '</p>';
            }
            if ($instance->timeclose > 0) {
                $dueclass = ($instance->timeclose < time()) ? 'text-danger' : 'text-success';
                $html .= '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('feedbackclose', 'feedback') . ':</strong> ';
                $html .= '<span class="' . $dueclass . '">' . userdate($instance->timeclose) . '</span></p>';
            }

            // Anonymous indicator.
            if ($instance->anonymous == FEEDBACK_ANONYMOUS_YES) {
                $html .= '<p><i class="fa fa-user-secret"></i> ' . get_string('anonymous', 'feedback') . '</p>';
            }

            $html .= '</div></div>';

            // Action buttons for instructors.
            $html .= '<div class="nexus-feedback-actions text-center mb-3">';

            if ($canviewreports && $completedcount > 0) {
                $analysisurl = new \moodle_url('/mod/feedback/analysis.php', ['id' => $cm->id]);
                $html .= '<a href="' . $analysisurl->out() . '" class="btn btn-primary me-2 mb-2">';
                $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('analysis', 'feedback') . '</a>';

                $showentriesurl = new \moodle_url('/mod/feedback/show_entries.php', ['id' => $cm->id]);
                $html .= '<a href="' . $showentriesurl->out() . '" class="btn btn-secondary me-2 mb-2">';
                $html .= '<i class="fa fa-list-alt"></i> ' . get_string('show_entries', 'feedback') . '</a>';
            }

            if ($canedititems) {
                $editurl = new \moodle_url('/mod/feedback/edit.php', ['id' => $cm->id]);
                $html .= '<a href="' . $editurl->out() . '" class="btn btn-outline-secondary mb-2">';
                $html .= '<i class="fa fa-pencil"></i> ' . get_string('edit_items', 'feedback') . '</a>';
            }

            $html .= '</div>';
        }

        // Show student view.
        if ($cancomplete && !$canedititems) {
            $itemcount = $DB->count_records('feedback_item', ['feedback' => $instance->id, 'hasvalue' => 1]);

            $html .= '<div class="nexus-feedback-info card mb-3">';
            $html .= '<div class="card-body">';

            $html .= '<p><i class="fa fa-list"></i> <strong>' . get_string('questions', 'feedback') . ':</strong> ' . $itemcount . '</p>';

            if ($instance->anonymous == FEEDBACK_ANONYMOUS_YES) {
                $html .= '<p><i class="fa fa-user-secret"></i> ' . get_string('anonymous', 'feedback') . '</p>';
            }

            // Availability dates for student.
            if ($instance->timeclose > 0 && $isopen) {
                $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('feedbackclose', 'feedback') . ':</strong> ';
                $html .= userdate($instance->timeclose) . '</p>';
            }

            $html .= '</div></div>';

            if ($completed) {
                $html .= '<div class="alert alert-success">';
                $html .= '<i class="fa fa-check-circle"></i> ' . get_string('this_feedback_is_already_submitted', 'feedback');
                $html .= '</div>';

                // Show page after submit if configured.
                if (!empty($instance->page_after_submit)) {
                    $html .= '<div class="card mb-3"><div class="card-body">';
                    $html .= format_text($instance->page_after_submit, $instance->page_after_submitformat);
                    $html .= '</div></div>';
                }
            } else if ($isopen) {
                // Check for incomplete attempt.
                $incompletetmp = $DB->get_record('feedback_completedtmp', [
                    'feedback' => $instance->id,
                    'userid' => $USER->id
                ]);

                if ($incompletetmp) {
                    $html .= '<div class="alert alert-info">';
                    $html .= '<i class="fa fa-info-circle"></i> ' . get_string('not_started', 'feedback');
                    $html .= '</div>';
                }

                // Start/continue button.
                $html .= '<div class="text-center">';
                $url = new \moodle_url('/mod/feedback/complete.php', ['id' => $cm->id]);
                $buttontext = $incompletetmp ? get_string('continue', 'feedback') : get_string('complete_the_form', 'feedback');
                $html .= '<a href="' . $url->out() . '" class="btn btn-primary btn-lg">';
                $html .= '<i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get glossary module content - replicates view.php display.
     */
    protected static function get_glossary_content($cm, $instance, $course, $context): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/glossary/lib.php');

        $html = '<div class="nexus-glossary-content">';

        // Count entries.
        $totalentries = $DB->count_records('glossary_entries', ['glossaryid' => $instance->id, 'approved' => 1]);
        $pendingentries = $DB->count_records('glossary_entries', ['glossaryid' => $instance->id, 'approved' => 0]);

        // Stats.
        $html .= '<div class="nexus-glossary-stats mb-3">';
        $html .= '<small class="text-muted">';
        $html .= '<i class="fa fa-book"></i> ' . $totalentries . ' ' . get_string('entries', 'glossary');
        if ($pendingentries > 0 && has_capability('mod/glossary:approve', $context)) {
            $html .= ' | <span class="text-warning"><i class="fa fa-clock-o"></i> ' . $pendingentries . ' ' . get_string('pendingapproval', 'glossary') . '</span>';
        }
        $html .= '</small></div>';

        // Alphabet navigation.
        $alphabet = explode(',', get_string('alphabet', 'langconfig'));
        $html .= '<div class="nexus-glossary-alphabet mb-3">';
        $html .= '<div class="btn-group btn-group-sm flex-wrap" role="group">';

        // All button.
        $viewurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id, 'mode' => 'letter', 'hook' => 'ALL']);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-outline-primary">' . get_string('all', 'glossary') . '</a>';

        // Special button.
        $specialurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id, 'mode' => 'letter', 'hook' => 'SPECIAL']);
        $html .= '<a href="' . $specialurl->out() . '" class="btn btn-outline-secondary">' . get_string('special', 'glossary') . '</a>';

        // Letter buttons.
        foreach ($alphabet as $letter) {
            $letter = trim($letter);
            $letterurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id, 'mode' => 'letter', 'hook' => $letter]);
            $html .= '<a href="' . $letterurl->out() . '" class="btn btn-outline-secondary">' . $letter . '</a>';
        }
        $html .= '</div></div>';

        // Get entries (sorted alphabetically by concept).
        $entries = $DB->get_records_sql(
            "SELECT ge.*, u.id as userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email
             FROM {glossary_entries} ge
             JOIN {user} u ON u.id = ge.userid
             WHERE ge.glossaryid = ? AND ge.approved = 1
             ORDER BY ge.concept ASC",
            [$instance->id],
            0,
            20 // Limit to 20 entries in panel.
        );

        if ($entries) {
            $html .= '<div class="nexus-glossary-entries accordion" id="glossaryAccordion">';

            $currentletter = '';
            foreach ($entries as $entry) {
                $firstletter = \core_text::strtoupper(\core_text::substr($entry->concept, 0, 1));

                // Letter separator.
                if ($firstletter !== $currentletter) {
                    $currentletter = $firstletter;
                    $html .= '<div class="nexus-glossary-letter-header bg-light p-2 mb-2 rounded">';
                    $html .= '<strong class="text-primary">' . $currentletter . '</strong>';
                    $html .= '</div>';
                }

                // Entry card.
                $entryid = 'entry' . $entry->id;
                $html .= '<div class="accordion-item">';
                $html .= '<h2 class="accordion-header">';
                $html .= '<button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" ';
                $html .= 'data-bs-target="#' . $entryid . '" aria-expanded="false" aria-controls="' . $entryid . '">';
                $html .= '<strong>' . format_string($entry->concept) . '</strong>';
                $html .= '</button></h2>';

                $html .= '<div id="' . $entryid . '" class="accordion-collapse collapse">';
                $html .= '<div class="accordion-body">';

                // Definition.
                $definition = file_rewrite_pluginfile_urls(
                    $entry->definition,
                    'pluginfile.php',
                    $context->id,
                    'mod_glossary',
                    'entry',
                    $entry->id
                );
                $html .= '<div class="nexus-glossary-definition">';
                $html .= format_text($definition, $entry->definitionformat, ['context' => $context]);
                $html .= '</div>';

                // Author and date.
                $html .= '<div class="nexus-glossary-meta text-muted small mt-2">';
                $html .= '<i class="fa fa-user"></i> ' . fullname($entry);
                $html .= ' | <i class="fa fa-calendar"></i> ' . userdate($entry->timecreated, get_string('strftimedatetimeshort', 'langconfig'));
                $html .= '</div>';

                $html .= '</div></div></div>';
            }
            $html .= '</div>';

            // Show more link if there are more entries.
            if ($totalentries > 20) {
                $html .= '<div class="text-center mt-2">';
                $viewallurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id]);
                $html .= '<a href="' . $viewallurl->out() . '" class="btn btn-sm btn-outline-secondary">';
                $html .= get_string('showall', 'moodle') . ' (' . $totalentries . ' ' . get_string('entries', 'glossary') . ')</a>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('noentries', 'glossary');
            $html .= '</div>';
        }

        // Search form.
        $html .= '<div class="nexus-glossary-search mt-3">';
        $html .= '<form action="' . (new \moodle_url('/mod/glossary/view.php'))->out() . '" method="get" class="input-group">';
        $html .= '<input type="hidden" name="id" value="' . $cm->id . '">';
        $html .= '<input type="hidden" name="mode" value="search">';
        $html .= '<input type="text" name="hook" class="form-control" placeholder="' . get_string('search', 'glossary') . '...">';
        $html .= '<button type="submit" class="btn btn-outline-secondary">';
        $html .= '<i class="fa fa-search"></i></button>';
        $html .= '</form></div>';

        // Action buttons.
        $html .= '<div class="nexus-glossary-actions text-center mt-3">';

        if (has_capability('mod/glossary:write', $context)) {
            $addurl = new \moodle_url('/mod/glossary/edit.php', ['cmid' => $cm->id]);
            $html .= '<a href="' . $addurl->out() . '" class="btn btn-primary me-2">';
            $html .= '<i class="fa fa-plus"></i> ' . get_string('addentry', 'glossary') . '</a>';
        }

        // Pending approval button for teachers.
        if ($pendingentries > 0 && has_capability('mod/glossary:approve', $context)) {
            $approveurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id, 'mode' => 'approval']);
            $html .= '<a href="' . $approveurl->out() . '" class="btn btn-warning">';
            $html .= '<i class="fa fa-check"></i> ' . get_string('waitingapproval', 'glossary') . ' (' . $pendingentries . ')</a>';
        }

        // View full glossary.
        $viewurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-book"></i> ' . get_string('modulename', 'glossary') . '</a>';

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get wiki module content.
     */
    protected static function get_wiki_content($cm, $instance, $course, $context): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/wiki/lib.php');
        require_once($CFG->dirroot . '/mod/wiki/locallib.php');

        $html = '<div class="nexus-wiki-content">';

        // Get subwiki and first page.
        $subwiki = $DB->get_record('wiki_subwikis', ['wikiid' => $instance->id], '*', IGNORE_MULTIPLE);

        if ($subwiki) {
            $firstpage = $DB->get_record('wiki_pages', ['subwikiid' => $subwiki->id, 'title' => $instance->firstpagetitle]);

            if ($firstpage) {
                $html .= '<div class="nexus-wiki-page">';
                $html .= '<h5>' . format_string($firstpage->title) . '</h5>';
                $html .= '<div class="wiki-content">' . format_text($firstpage->cachedcontent, FORMAT_HTML) . '</div>';
                $html .= '</div>';

                // View full wiki button.
                $html .= '<div class="text-center mt-3">';
                $url = new \moodle_url('/mod/wiki/view.php', ['id' => $cm->id]);
                $html .= '<a href="' . $url->out() . '" class="btn btn-primary">';
                $html .= '<i class="fa fa-book"></i> ' . get_string('view', 'wiki') . ' ' . get_string('modulename', 'wiki') . '</a>';
                $html .= '</div>';
            } else {
                // Create first page - wiki has no first page yet.
                $html .= '<div class="alert alert-info">';
                $html .= get_string('wikiempty', 'format_nexusformat');
                $html .= '</div>';
                $url = new \moodle_url('/mod/wiki/create.php', ['id' => $cm->id]);
                $html .= '<div class="text-center">';
                $html .= '<a href="' . $url->out() . '" class="btn btn-primary">';
                $html .= '<i class="fa fa-plus"></i> ' . get_string('createpage', 'wiki') . '</a>';
                $html .= '</div>';
            }
        } else {
            $url = new \moodle_url('/mod/wiki/view.php', ['id' => $cm->id]);
            $html .= '<div class="text-center">';
            $html .= '<a href="' . $url->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play-circle"></i> ' . get_string('view', 'wiki') . ' ' . get_string('modulename', 'wiki') . '</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get database module content.
     */
    protected static function get_data_content($cm, $instance, $course, $context): string {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/mod/data/lib.php');

        $html = '<div class="nexus-data-content">';

        // Count entries.
        $totalentries = $DB->count_records('data_records', ['dataid' => $instance->id]);

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-database"></i> <strong>' . get_string('entries', 'data') . ':</strong> ' . $totalentries . '</p>';

        if ($instance->requiredentries > 0) {
            $html .= '<p><i class="fa fa-exclamation-circle"></i> <strong>' . get_string('requiredentries', 'data') . ':</strong> ' . $instance->requiredentries . '</p>';
        }
        $html .= '</div></div>';

        // Action buttons.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/data/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary me-2">';
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewentries', 'data') . '</a>';

        if (has_capability('mod/data:writeentry', $context)) {
            $addurl = new \moodle_url('/mod/data/edit.php', ['id' => $cm->id]);
            $html .= '<a href="' . $addurl->out() . '" class="btn btn-success">';
            $html .= '<i class="fa fa-plus"></i> ' . get_string('addentry', 'data') . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get H5P activity content.
     * H5P requires full page context for proper rendering, so we provide a link.
     */
    protected static function get_h5p_content($cm, $instance, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');

        $html = '<div class="nexus-h5p-content">';

        // H5P info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Check for user attempts.
        $attempts = $DB->count_records('h5pactivity_attempts', [
            'h5pactivityid' => $instance->id,
            'userid' => $USER->id
        ]);

        if ($attempts > 0) {
            // Get best score.
            $bestscore = $DB->get_field_sql(
                "SELECT MAX(scaled) FROM {h5pactivity_attempts} WHERE h5pactivityid = ? AND userid = ?",
                [$instance->id, $USER->id]
            );
            if ($bestscore !== false && $bestscore !== null) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ';
                $html .= format_float($bestscore * 100, 1) . '%</p>';
            }
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attempts', 'quiz') . ':</strong> ' . $attempts . '</p>';
        }

        // Max attempts info.
        if (!empty($instance->maxattempts) && $instance->maxattempts > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> <strong>' . get_string('maxattempts', 'h5pactivity') . ':</strong> ' . $instance->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        // Launch button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/h5pactivity/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-play-circle"></i> ' . get_string('startactivity', 'format_nexusformat') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get SCORM content.
     */
    protected static function get_scorm_content($cm, $instance, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/scorm/lib.php');
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        $html = '<div class="nexus-scorm-content">';

        // SCORM info.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Attempts info.
        $attempts = scorm_get_attempt_count($USER->id, $instance);
        $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attempts', 'scorm') . ':</strong> ' . $attempts . '</p>';

        if ($instance->maxattempt > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> <strong>' . get_string('maximumattempts', 'scorm') . ':</strong> ' . $instance->maxattempt . '</p>';
        }

        // Last score.
        if ($attempts > 0) {
            $score = scorm_grade_user($instance, $USER->id);
            if ($score !== false) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ' . format_float($score, 2) . '%</p>';
            }
        }

        $html .= '</div></div>';

        // Enter SCORM button.
        $html .= '<div class="text-center">';
        $canstart = true;
        if ($instance->maxattempt > 0 && $attempts >= $instance->maxattempt) {
            $canstart = false;
            $html .= '<div class="alert alert-warning">' . get_string('exceeded', 'scorm') . '</div>';
        }

        if ($canstart) {
            $url = new \moodle_url('/mod/scorm/player.php', ['cm' => $cm->id, 'newattempt' => 'on']);
            $buttontext = $attempts > 0 ? get_string('enter', 'scorm') : get_string('enter', 'scorm');
            $html .= '<a href="' . $url->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get LTI content.
     */
    protected static function get_lti_content($cm, $instance, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/lib.php');
        require_once($CFG->dirroot . '/mod/lti/locallib.php');

        $html = '<div class="nexus-lti-content">';

        // LTI launches in new window by nature, show launch button.
        $html .= '<div class="card mb-3"><div class="card-body text-center">';
        $html .= '<p><i class="fa fa-external-link fa-3x mb-3"></i></p>';
        $html .= '<p>' . get_string('launchexternaltool', 'lti') . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $url = new \moodle_url('/mod/lti/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-external-link"></i> ' . get_string('launch', 'lti') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get BigBlueButton content.
     */
    protected static function get_bigbluebuttonbn_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-bbb-content">';

        // Check capabilities.
        $canmoderate = has_capability('mod/bigbluebuttonbn:moderate', $context);
        $canjoin = has_capability('mod/bigbluebuttonbn:join', $context);

        // Meeting info.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Meeting type.
        if (!empty($instance->type)) {
            $types = [
                0 => get_string('room_mode_all', 'bigbluebuttonbn'),
                1 => get_string('room_mode_presentation', 'bigbluebuttonbn'),
                2 => get_string('room_mode_recording', 'bigbluebuttonbn'),
            ];
            if (isset($types[$instance->type])) {
                $html .= '<p><i class="fa fa-video-camera"></i> <strong>' . get_string('mod_form_field_room_type', 'bigbluebuttonbn') . ':</strong> ' . $types[$instance->type] . '</p>';
            }
        }

        // Opening time.
        if (!empty($instance->openingtime) && $instance->openingtime > 0) {
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('mod_form_field_openingtime', 'bigbluebuttonbn') . ':</strong> ' . userdate($instance->openingtime) . '</p>';
        }

        // Closing time.
        if (!empty($instance->closingtime) && $instance->closingtime > 0) {
            $html .= '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('mod_form_field_closingtime', 'bigbluebuttonbn') . ':</strong> ' . userdate($instance->closingtime) . '</p>';
        }

        $html .= '</div></div>';

        // Show recordings if available.
        if (file_exists($CFG->dirroot . '/mod/bigbluebuttonbn/classes/recording.php')) {
            $recordings = $DB->get_records('bigbluebuttonbn_recordings', ['bigbluebuttonbnid' => $instance->id], 'timecreated DESC', '*', 0, 5);
            if ($recordings) {
                $html .= '<div class="card mb-3">';
                $html .= '<div class="card-header"><strong>' . get_string('view_recording_list_actionbar', 'bigbluebuttonbn') . '</strong></div>';
                $html .= '<div class="card-body">';
                $html .= '<ul class="list-group list-group-flush">';
                foreach ($recordings as $recording) {
                    $html .= '<li class="list-group-item">';
                    $html .= '<i class="fa fa-play-circle"></i> ' . userdate($recording->timecreated);
                    $html .= '</li>';
                }
                $html .= '</ul></div></div>';
            }
        }

        // Join button.
        $html .= '<div class="text-center">';
        if ($canjoin) {
            $viewurl = new \moodle_url('/mod/bigbluebuttonbn/view.php', ['id' => $cm->id]);
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-video-camera"></i> ' . get_string('view_room', 'bigbluebuttonbn') . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get chat module content.
     * Matches view.php: shows next chat time, current users, past sessions link for teachers.
     */
    protected static function get_chat_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/chat/lib.php');

        $html = '<div class="nexus-chat-content">';
        $timenow = time();

        // Check capabilities.
        $canviewreport = has_capability('mod/chat:readlog', $context);

        // Chat info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Next chat time with countdown - same as view.php.
        if (!empty($instance->chattime) && $instance->chattime > 0 && $instance->schedule > 0) {
            $nextchattime = $instance->chattime;

            // Calculate next chat time based on schedule - same logic as view.php.
            if ($instance->schedule == 2) { // Daily.
                while ($nextchattime < $timenow) {
                    $nextchattime += 86400; // Add 24 hours.
                }
            } else if ($instance->schedule == 3) { // Weekly.
                while ($nextchattime < $timenow) {
                    $nextchattime += 604800; // Add 7 days.
                }
            }

            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('nextchattime', 'chat') . ':</strong> ' . userdate($nextchattime) . '</p>';

            // Countdown if chat is in the future.
            if ($nextchattime > $timenow) {
                $countdown = $nextchattime - $timenow;
                if ($countdown < 86400) { // Less than 24 hours.
                    $html .= '<p class="text-info"><i class="fa fa-hourglass-half"></i> ';
                    $html .= get_string('sessionstart', 'chat') . ': ' . format_time($countdown);
                    $html .= '</p>';
                }
            }
        }

        // Schedule type.
        if (!empty($instance->schedule)) {
            $schedules = [
                0 => get_string('donotusechattime', 'chat'),
                1 => get_string('repeatnone', 'chat'),
                2 => get_string('repeatdaily', 'chat'),
                3 => get_string('repeatweekly', 'chat'),
            ];
            if (isset($schedules[$instance->schedule])) {
                $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('schedule', 'chat') . ':</strong> ' . $schedules[$instance->schedule] . '</p>';
            }
        }

        $html .= '</div></div>';

        // Current users in chat - same as view.php.
        $currentusers = $DB->get_records_sql(
            "SELECT cu.*, u.firstname, u.lastname, u.picture, u.imagealt, u.email
             FROM {chat_users} cu
             JOIN {user} u ON u.id = cu.userid
             WHERE cu.chatid = ?
             ORDER BY cu.lastping DESC",
            [$instance->id]
        );

        if ($currentusers) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header bg-success text-white">';
            $html .= '<i class="fa fa-users"></i> <strong>' . get_string('currentusers', 'chat') . '</strong>';
            $html .= '</div>';
            $html .= '<div class="card-body">';
            $html .= '<ul class="list-unstyled mb-0">';
            foreach ($currentusers as $chatuser) {
                $idle = $timenow - $chatuser->lastping;
                $idlestr = $idle < 60 ? get_string('idle', 'chat', get_string('now')) : get_string('idle', 'chat', format_time($idle));
                $html .= '<li class="d-flex align-items-center mb-2">';
                $html .= '<i class="fa fa-circle text-success me-2" style="font-size: 8px;"></i>';
                $html .= '<span>' . fullname($chatuser) . '</span>';
                $html .= '<small class="text-muted ms-2">(' . $idlestr . ')</small>';
                $html .= '</li>';
            }
            $html .= '</ul></div></div>';
        }

        // Recent messages preview.
        $messages = $DB->get_records_sql(
            "SELECT cm.*, u.firstname, u.lastname
             FROM {chat_messages} cm
             JOIN {user} u ON u.id = cm.userid
             WHERE cm.chatid = ? AND cm.issystem = 0
             ORDER BY cm.timestamp DESC",
            [$instance->id],
            0, 5
        );

        if ($messages) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header"><strong>' . get_string('pastchats', 'chat') . '</strong></div>';
            $html .= '<div class="card-body">';
            foreach (array_reverse($messages) as $msg) {
                $html .= '<div class="chat-message mb-2">';
                $html .= '<small class="text-muted">' . userdate($msg->timestamp, get_string('strftimetime')) . ' - ' . fullname($msg) . ':</small> ';
                $html .= format_string($msg->message);
                $html .= '</div>';
            }
            $html .= '</div></div>';
        }

        // Action buttons.
        $html .= '<div class="text-center">';

        // Enter chat button.
        $viewurl = new \moodle_url('/mod/chat/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg me-2">';
        $html .= '<i class="fa fa-comments"></i> ' . get_string('enterchat', 'chat') . '</a>';

        // View past sessions - for users with readlog capability (same as view.php).
        if ($canviewreport) {
            $reporturl = new \moodle_url('/mod/chat/report.php', ['id' => $cm->id]);
            $html .= '<a href="' . $reporturl->out() . '" class="btn btn-outline-secondary">';
            $html .= '<i class="fa fa-history"></i> ' . get_string('viewreport', 'chat') . '</a>';
        }

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get survey module content.
     */
    /**
     * Get survey module content.
     * Matches view.php: shows survey type, completion status, response count for teachers.
     */
    protected static function get_survey_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/survey/lib.php');

        $html = '<div class="nexus-survey-content">';

        // Check capabilities.
        $canviewreports = has_capability('mod/survey:readresponses', $context);

        // Check if user has already answered.
        $surveydone = survey_already_done($instance->id, $USER->id);

        // Survey info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Survey type.
        $surveynames = [
            1 => 'COLLES (Actual)',
            2 => 'COLLES (Preferred)',
            3 => 'COLLES (Preferred and Actual)',
            4 => 'ATTLS',
            5 => 'Critical Incidents',
        ];
        if (isset($surveynames[$instance->template])) {
            $html .= '<p><i class="fa fa-list-alt"></i> <strong>' . get_string('surveytype', 'survey') . ':</strong> ' . $surveynames[$instance->template] . '</p>';
        }

        // Question count.
        $questioncount = $DB->count_records('survey_questions', ['template' => $instance->template, 'deleted' => 0]);
        $html .= '<p><i class="fa fa-question-circle"></i> <strong>' . get_string('questions', 'survey') . ':</strong> ' . $questioncount . '</p>';

        // Number of responses - for teachers (same info as report page).
        if ($canviewreports) {
            $responsecount = $DB->count_records_sql(
                "SELECT COUNT(DISTINCT userid) FROM {survey_answers} WHERE survey = ?",
                [$instance->id]
            );
            $html .= '<p><i class="fa fa-users"></i> <strong>' . get_string('responses', 'survey') . ':</strong> ' . $responsecount . '</p>';
        }

        $html .= '</div></div>';

        // Completion status.
        if ($surveydone) {
            $html .= '<div class="alert alert-success">';
            $html .= '<i class="fa fa-check-circle"></i> ' . get_string('surveycompleted', 'survey');
            $html .= '</div>';
        }

        // Action buttons.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/survey/view.php', ['id' => $cm->id]);

        if ($surveydone) {
            // User completed - show view results.
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-secondary btn-lg me-2">';
            $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('results', 'survey') . '</a>';
        } else if (!$canviewreports) {
            // User hasn't completed - show take survey.
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg me-2">';
            $html .= '<i class="fa fa-pencil-square-o"></i> ' . get_string('modulename', 'survey') . '</a>';
        }

        // Report link for teachers.
        if ($canviewreports) {
            $reporturl = new \moodle_url('/mod/survey/report.php', ['id' => $cm->id]);
            $html .= '<a href="' . $reporturl->out() . '" class="btn btn-outline-primary">';
            $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('report', 'survey') . '</a>';
        }

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get workshop module content.
     */
    protected static function get_workshop_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');

        $html = '<div class="nexus-workshop-content">';

        // Get workshop object.
        $workshop = new \workshop($instance, $cm, $course);

        // Workshop info.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Current phase.
        $phases = [
            \workshop::PHASE_SETUP => get_string('phasesetup', 'workshop'),
            \workshop::PHASE_SUBMISSION => get_string('phasesubmission', 'workshop'),
            \workshop::PHASE_ASSESSMENT => get_string('phaseassessment', 'workshop'),
            \workshop::PHASE_EVALUATION => get_string('phaseevaluation', 'workshop'),
            \workshop::PHASE_CLOSED => get_string('phaseclosed', 'workshop'),
        ];
        if (isset($phases[$instance->phase])) {
            $html .= '<p><i class="fa fa-flag"></i> <strong>' . get_string('currentphase', 'workshop') . ':</strong> ';
            $html .= '<span class="badge bg-primary">' . $phases[$instance->phase] . '</span></p>';
        }

        // Submission deadline.
        if (!empty($instance->submissionend) && $instance->submissionend > 0) {
            $dueclass = ($instance->submissionend < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('submissionend', 'workshop') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($instance->submissionend) . '</span></p>';
        }

        // Assessment deadline.
        if (!empty($instance->assessmentend) && $instance->assessmentend > 0) {
            $dueclass = ($instance->assessmentend < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar-check-o"></i> <strong>' . get_string('assessmentend', 'workshop') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($instance->assessmentend) . '</span></p>';
        }

        $html .= '</div></div>';

        // User submission status.
        $cansubmit = has_capability('mod/workshop:submit', $context);
        if ($cansubmit) {
            $submission = $DB->get_record('workshop_submissions', ['workshopid' => $instance->id, 'authorid' => $USER->id]);
            if ($submission) {
                $html .= '<div class="alert alert-info">';
                $html .= '<i class="fa fa-check"></i> ' . get_string('yoursubmission', 'workshop') . ': ';
                $html .= '<strong>' . format_string($submission->title) . '</strong>';
                $html .= '</div>';
            }
        }

        // View button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/workshop/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-users"></i> ' . get_string('modulename', 'workshop') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get folder module content.
     * Matches view.php: shows folder tree structure, download all button, edit button for managers.
     */
    protected static function get_folder_content($cm, $instance, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/folder/lib.php');

        $html = '<div class="nexus-folder-content">';

        // Check capabilities.
        $canmanagefiles = has_capability('mod/folder:managefiles', $context);

        // Get folder tree structure - same as view.php renderer.
        $fs = get_file_storage();
        $tree = $fs->get_area_tree($context->id, 'mod_folder', 'content', 0);

        // Recursive function to render folder tree.
        $renderTree = function($dir, $level = 0) use (&$renderTree, $context, $instance) {
            $html = '';
            $indent = $level > 0 ? 'ps-' . min($level * 3, 5) : '';

            // Render subdirectories first.
            if (!empty($dir['subdirs'])) {
                foreach ($dir['subdirs'] as $subdir) {
                    $html .= '<div class="nexus-folder-item folder-subdir ' . $indent . ' mb-2">';
                    $html .= '<div class="d-flex align-items-center py-1">';
                    $html .= '<i class="fa fa-folder text-warning me-2"></i>';
                    $html .= '<strong>' . s($subdir['dirname']) . '</strong>';
                    $html .= '</div>';
                    $html .= '<div class="nexus-folder-children ms-3">';
                    $html .= $renderTree($subdir, $level + 1);
                    $html .= '</div></div>';
                }
            }

            // Render files.
            if (!empty($dir['files'])) {
                foreach ($dir['files'] as $file) {
                    $filename = $file->get_filename();
                    $filepath = $file->get_filepath();
                    $fileurl = \moodle_url::make_pluginfile_url(
                        $context->id,
                        'mod_folder',
                        'content',
                        0,
                        $filepath,
                        $filename,
                        !empty($instance->forcedownload)
                    );

                    // Get file icon based on mimetype.
                    $mimetype = $file->get_mimetype();
                    $icon = 'fa-file-o';
                    if (strpos($mimetype, 'image') !== false) {
                        $icon = 'fa-file-image-o text-info';
                    } else if (strpos($mimetype, 'pdf') !== false) {
                        $icon = 'fa-file-pdf-o text-danger';
                    } else if (strpos($mimetype, 'word') !== false || strpos($mimetype, 'document') !== false) {
                        $icon = 'fa-file-word-o text-primary';
                    } else if (strpos($mimetype, 'excel') !== false || strpos($mimetype, 'spreadsheet') !== false) {
                        $icon = 'fa-file-excel-o text-success';
                    } else if (strpos($mimetype, 'powerpoint') !== false || strpos($mimetype, 'presentation') !== false) {
                        $icon = 'fa-file-powerpoint-o text-warning';
                    } else if (strpos($mimetype, 'zip') !== false || strpos($mimetype, 'archive') !== false) {
                        $icon = 'fa-file-archive-o text-secondary';
                    } else if (strpos($mimetype, 'video') !== false) {
                        $icon = 'fa-file-video-o text-purple';
                    } else if (strpos($mimetype, 'audio') !== false) {
                        $icon = 'fa-file-audio-o text-pink';
                    }

                    // File size.
                    $filesize = display_size($file->get_filesize());

                    $html .= '<div class="nexus-folder-item folder-file ' . $indent . ' d-flex justify-content-between align-items-center py-2 border-bottom">';
                    $html .= '<a href="' . $fileurl->out() . '" class="text-decoration-none flex-grow-1">';
                    $html .= '<i class="fa ' . $icon . ' me-2"></i>' . s($filename);
                    $html .= '<small class="text-muted ms-2">(' . $filesize . ')</small>';
                    $html .= '</a>';
                    $html .= '<a href="' . $fileurl->out() . '" class="btn btn-sm btn-outline-primary ms-2" download title="' . get_string('download') . '">';
                    $html .= '<i class="fa fa-download"></i></a>';
                    $html .= '</div>';
                }
            }

            return $html;
        };

        // Check if folder has content.
        $hasContent = !empty($tree['subdirs']) || !empty($tree['files']);

        if ($hasContent) {
            $html .= '<div class="nexus-folder-tree card mb-3"><div class="card-body">';
            $html .= $renderTree($tree);
            $html .= '</div></div>';

            // Download all button - same as view.php (if folder_archive_available).
            if (function_exists('folder_archive_available') && folder_archive_available($instance, $cm)) {
                $downloadurl = new \moodle_url('/mod/folder/download_folder.php', ['id' => $cm->id]);
                $html .= '<div class="text-center mb-3">';
                $html .= '<a href="' . $downloadurl->out() . '" class="btn btn-outline-secondary">';
                $html .= '<i class="fa fa-download me-1"></i> ' . get_string('downloadfolder', 'folder') . '</a>';
                $html .= '</div>';
            }
        } else {
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('nofiles', 'folder');
            $html .= '</div>';
        }

        // Action buttons.
        $html .= '<div class="text-center mt-3">';

        // Edit button for managers - same as view.php.
        if ($canmanagefiles) {
            $editurl = new \moodle_url('/mod/folder/edit.php', ['id' => $cm->id]);
            $html .= '<a href="' . $editurl->out() . '" class="btn btn-primary me-2">';
            $html .= '<i class="fa fa-edit"></i> ' . get_string('edit') . '</a>';
        }

        // View in full page.
        $viewurl = new \moodle_url('/mod/folder/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-folder-open"></i> ' . get_string('modulename', 'folder') . '</a>';

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get IMS content package content.
     */
    protected static function get_imscp_content($cm, $instance, $context): string {
        global $CFG;

        $html = '<div class="nexus-imscp-content">';

        $html .= '<div class="card mb-3"><div class="card-body text-center">';
        $html .= '<p><i class="fa fa-cube fa-3x mb-3"></i></p>';
        $html .= '<p>' . get_string('modulename_help', 'imscp') . '</p>';
        $html .= '</div></div>';

        // Launch button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/imscp/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-play-circle"></i> ' . get_string('modulename', 'imscp') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get checklist module content.
     */
    protected static function get_checklist_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-checklist-content">';

        // Get checklist items.
        $items = $DB->get_records('checklist_item', ['checklist' => $instance->id, 'hidden' => 0], 'position ASC');
        $checks = [];
        if ($items) {
            $itemids = array_keys($items);
            list($insql, $params) = $DB->get_in_or_equal($itemids, SQL_PARAMS_NAMED);
            $params['userid'] = $USER->id;
            $userchecks = $DB->get_records_select('checklist_check',
                "item $insql AND userid = :userid", $params, '', 'item, usertimestamp');
            foreach ($userchecks as $check) {
                $checks[$check->item] = $check;
            }
        }

        if ($items) {
            $totalitems = count($items);
            $checkeditems = count($checks);
            $progress = $totalitems > 0 ? round(($checkeditems / $totalitems) * 100) : 0;

            // Progress bar.
            $html .= '<div class="nexus-checklist-progress mb-3">';
            $html .= '<div class="d-flex justify-content-between mb-1">';
            $html .= '<span>' . get_string('progress', 'checklist') . '</span>';
            $html .= '<span>' . $checkeditems . ' / ' . $totalitems . ' (' . $progress . '%)</span>';
            $html .= '</div>';
            $html .= '<div class="progress">';
            $html .= '<div class="progress-bar bg-success" style="width: ' . $progress . '%"></div>';
            $html .= '</div></div>';

            // Items list.
            $html .= '<div class="nexus-checklist-items">';
            $html .= '<ul class="list-group">';
            foreach ($items as $item) {
                $checked = isset($checks[$item->id]);
                $checkclass = $checked ? 'list-group-item-success' : '';
                $checkicon = $checked ? 'fa-check-square-o text-success' : 'fa-square-o';

                $html .= '<li class="list-group-item ' . $checkclass . '">';
                $html .= '<i class="fa ' . $checkicon . ' me-2"></i>';
                $html .= format_string($item->displaytext);
                $html .= '</li>';
            }
            $html .= '</ul></div>';
        } else {
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('noitems', 'checklist');
            $html .= '</div>';
        }

        // View full checklist.
        $html .= '<div class="text-center mt-3">';
        $viewurl = new \moodle_url('/mod/checklist/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary">';
        $html .= '<i class="fa fa-check-square-o"></i> ' . get_string('modulename', 'checklist') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get custom certificate content.
     * Matches view.php: shows required time notice, issue status, manager reports.
     */
    protected static function get_customcert_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-customcert-content">';

        // Check capabilities.
        $canreceive = has_capability('mod/customcert:receiveissue', $context);
        $canmanage = has_capability('mod/customcert:manage', $context);
        $canviewreport = has_capability('mod/customcert:viewreport', $context);

        // Check required time - same as view.php.
        if (!empty($instance->requiredtime) && $instance->requiredtime > 0 && !$canmanage) {
            // Get time spent in course.
            $coursetime = 0;
            if (class_exists('\mod_customcert\certificate')) {
                $coursetime = \mod_customcert\certificate::get_course_time($course->id);
            }

            $requiredtimeseconds = $instance->requiredtime * 60;
            if ($coursetime < $requiredtimeseconds) {
                $a = new \stdClass();
                $a->requiredtime = $instance->requiredtime;
                $html .= '<div class="alert alert-warning">';
                $html .= '<i class="fa fa-clock-o"></i> ' . get_string('requiredtimenotmet', 'customcert', $a);
                $html .= '</div>';

                // Show progress toward required time.
                $progress = min(100, round(($coursetime / $requiredtimeseconds) * 100));
                $html .= '<div class="mb-3">';
                $html .= '<div class="d-flex justify-content-between mb-1">';
                $html .= '<span>' . get_string('coursetimereq', 'customcert') . '</span>';
                $html .= '<span>' . format_time($coursetime) . ' / ' . format_time($requiredtimeseconds) . '</span>';
                $html .= '</div>';
                $html .= '<div class="progress"><div class="progress-bar" style="width: ' . $progress . '%"></div></div>';
                $html .= '</div>';
            }
        }

        // Check if user has already received certificate.
        $issue = $DB->get_record('customcert_issues', ['customcertid' => $instance->id, 'userid' => $USER->id]);

        $html .= '<div class="card mb-3"><div class="card-body text-center">';

        if ($issue) {
            $html .= '<p><i class="fa fa-certificate fa-3x text-success mb-3"></i></p>';
            $html .= '<p class="text-success"><strong>' . get_string('receiveddate', 'customcert') . ':</strong> ' . userdate($issue->timecreated) . '</p>';
        } else {
            $html .= '<p><i class="fa fa-certificate fa-3x text-muted mb-3"></i></p>';
            $html .= '<p class="text-muted">' . get_string('notissued', 'customcert') . '</p>';
        }

        $html .= '</div></div>';

        // Show number of issues for managers/reporters - same as view.php.
        if ($canviewreport) {
            $numissues = $DB->count_records('customcert_issues', ['customcertid' => $instance->id]);
            $html .= '<div class="alert alert-info">';
            $html .= '<i class="fa fa-info-circle"></i> ' . get_string('listofissues', 'customcert', $numissues);
            $html .= '</div>';
        }

        // Action buttons.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/customcert/view.php', ['id' => $cm->id]);

        if ($issue && $canreceive) {
            // User has certificate - show download button.
            $downloadurl = new \moodle_url('/mod/customcert/view.php', ['id' => $cm->id, 'downloadown' => true]);
            $html .= '<a href="' . $downloadurl->out() . '" class="btn btn-success btn-lg me-2">';
            $html .= '<i class="fa fa-download"></i> ' . get_string('getcustomcert', 'customcert') . '</a>';
        } else if ($canreceive) {
            // User can receive but hasn't yet.
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg me-2">';
            $html .= '<i class="fa fa-certificate"></i> ' . get_string('getcustomcert', 'customcert') . '</a>';
        }

        // View full page (for report access).
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-external-link"></i> ' . get_string('modulename', 'customcert') . '</a>';

        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get scheduler module content.
     */
    protected static function get_scheduler_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-scheduler-content">';

        // Check capabilities.
        $canbook = has_capability('mod/scheduler:appoint', $context);
        $canmanage = has_capability('mod/scheduler:manage', $context);

        // Get user's appointments.
        $appointments = $DB->get_records_sql(
            "SELECT a.*, s.starttime, s.duration, s.teacherid,
                    t.firstname as teacherfirstname, t.lastname as teacherlastname
             FROM {scheduler_appointment} a
             JOIN {scheduler_slots} s ON s.id = a.slotid
             JOIN {user} t ON t.id = s.teacherid
             WHERE s.schedulerid = ? AND a.studentid = ?
             ORDER BY s.starttime DESC",
            [$instance->id, $USER->id],
            0, 5
        );

        if ($appointments) {
            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header"><strong>' . get_string('myappointments', 'scheduler') . '</strong></div>';
            $html .= '<div class="card-body">';
            $html .= '<ul class="list-group list-group-flush">';
            foreach ($appointments as $appt) {
                $statusclass = ($appt->starttime > time()) ? 'list-group-item-info' : 'list-group-item-secondary';
                $html .= '<li class="list-group-item ' . $statusclass . '">';
                $html .= '<strong>' . userdate($appt->starttime) . '</strong><br>';
                $html .= '<small>' . get_string('teacher', 'scheduler') . ': ' . fullname($appt) . '</small>';
                $html .= '</li>';
            }
            $html .= '</ul></div></div>';
        }

        // Available slots count.
        $availableslots = $DB->count_records_sql(
            "SELECT COUNT(*)
             FROM {scheduler_slots} s
             LEFT JOIN {scheduler_appointment} a ON a.slotid = s.id
             WHERE s.schedulerid = ? AND s.starttime > ? AND a.id IS NULL",
            [$instance->id, time()]
        );

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('availableslots', 'scheduler') . ':</strong> ' . $availableslots . '</p>';
        $html .= '</div></div>';

        // Book appointment button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/scheduler/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-calendar-plus-o"></i> ' . get_string('modulename', 'scheduler') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get game module content.
     * Matches view.php display: intro, grading method, time availability, attempts table, best grade, high scores.
     */
    protected static function get_game_content($cm, $instance, $context): string {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/mod/game/locallib.php');

        $html = '<div class="nexus-game-content">';
        $timenow = time();

        // Game type.
        $gametypes = [
            'hangman' => get_string('hangman', 'game'),
            'crossword' => get_string('cross', 'game'),
            'cryptex' => get_string('cryptex', 'game'),
            'millionaire' => get_string('millionaire', 'game'),
            'sudoku' => get_string('sudoku', 'game'),
            'bookquiz' => get_string('bookquiz', 'game'),
            'snakes' => get_string('snakes', 'game'),
            'hiddenpicture' => get_string('hiddenpicture', 'game'),
        ];

        // Game info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        if (isset($gametypes[$instance->gamekind])) {
            $html .= '<p><i class="fa fa-gamepad"></i> <strong>' . get_string('gametype', 'game') . ':</strong> ' . $gametypes[$instance->gamekind] . '</p>';
        }

        // Grading method - same as view.php.
        if ($instance->attempts != 1) {
            $gradingmethods = [
                1 => get_string('gradehighest', 'quiz'),
                2 => get_string('gradeaverage', 'quiz'),
                3 => get_string('attemptfirst', 'quiz'),
                4 => get_string('attemptlast', 'quiz'),
            ];
            if (isset($gradingmethods[$instance->grademethod])) {
                $html .= '<p><i class="fa fa-calculator"></i> <strong>' . get_string('gradingmethod', 'quiz') . ':</strong> ' . $gradingmethods[$instance->grademethod] . '</p>';
            }
        }

        // Max attempts.
        if (!empty($instance->maxattempts) && $instance->maxattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attemptsallowed', 'quiz') . ':</strong> ' . $instance->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        // Time availability - same as view.php.
        $canattempt = true;
        $strtimeopenclose = '';
        if ($timenow < $instance->timeopen && $instance->timeopen > 0) {
            $canattempt = false;
            $strtimeopenclose = get_string('gamenotavailable', 'game', userdate($instance->timeopen));
        } else if ($instance->timeclose && $timenow > $instance->timeclose) {
            $strtimeopenclose = get_string('gameclosed', 'game', userdate($instance->timeclose));
            $canattempt = false;
        } else {
            if ($instance->timeopen) {
                $strtimeopenclose = get_string('gameopenedon', 'game', userdate($instance->timeopen));
            }
            if ($instance->timeclose) {
                $strtimeopenclose = get_string('gamecloseson', 'game', userdate($instance->timeclose));
            }
        }

        // Teachers can always attempt.
        if (has_capability('mod/game:manage', $context)) {
            $canattempt = true;
        }

        if (!empty($strtimeopenclose)) {
            $alertclass = $canattempt ? 'alert-info' : 'alert-warning';
            $html .= '<div class="alert ' . $alertclass . '">' . $strtimeopenclose . '</div>';
        }

        // Get user attempts - same as view.php.
        $attempts = $DB->get_records('game_attempts', ['gameid' => $instance->id, 'userid' => $USER->id], 'attempt ASC');

        // Best grade calculation.
        $mygrade = null;
        if ($attempts && function_exists('game_get_best_grade')) {
            $mygrade = game_get_best_grade($instance, $USER->id);
        } else if ($attempts) {
            // Fallback calculation.
            $mygrade = $DB->get_field_sql(
                "SELECT MAX(score) FROM {game_attempts} WHERE gameid = ? AND userid = ? AND timefinish > 0",
                [$instance->id, $USER->id]
            );
            if ($mygrade !== false && $mygrade !== null && $instance->grade > 0) {
                $mygrade = $mygrade * $instance->grade;
            }
        }

        // Attempts table - same as view.php.
        if ($attempts) {
            $html .= '<h5>' . get_string('summaryofattempts', 'quiz') . '</h5>';
            $html .= '<div class="table-responsive"><table class="table table-striped table-sm">';
            $html .= '<thead><tr>';
            if ($instance->attempts != 1) {
                $html .= '<th>' . get_string('attempt', 'game') . '</th>';
            }
            $html .= '<th>' . get_string('timecompleted', 'game') . '</th>';
            if ($instance->grade > 0) {
                $html .= '<th>' . get_string('grade', 'game') . ' / ' . format_float($instance->grade, $instance->decimalpoints ?? 2) . '</th>';
            }
            $html .= '<th>' . get_string('timetaken', 'game') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($attempts as $attempt) {
                $rowclass = '';
                // Highlight best attempt.
                if ($mygrade !== null && $instance->grademethod == 1) { // QUIZ_GRADEHIGHEST
                    $attemptgrade = isset($attempt->score) ? ($attempt->score * $instance->grade) : 0;
                    if (abs($attemptgrade - $mygrade) < 0.0001) {
                        $rowclass = 'table-success';
                    }
                }

                $html .= '<tr class="' . $rowclass . '">';
                if ($instance->attempts != 1) {
                    $html .= '<td>' . ($attempt->preview ? get_string('preview', 'game') : $attempt->attempt) . '</td>';
                }

                // Date completed.
                $datecompleted = ($attempt->timefinish > 0) ? userdate($attempt->timefinish) : '-';
                $html .= '<td>' . $datecompleted . '</td>';

                // Grade.
                if ($instance->grade > 0) {
                    $attemptgrade = isset($attempt->score) ? format_float($attempt->score * $instance->grade, $instance->decimalpoints ?? 2) : '-';
                    $html .= '<td>' . $attemptgrade . '</td>';
                }

                // Time taken.
                if ($attempt->timefinish > 0) {
                    $timetaken = format_time($attempt->timefinish - $attempt->timestart);
                } else {
                    $timetaken = format_time($timenow - $attempt->timestart);
                }
                $html .= '<td>' . $timetaken . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';

            // Final grade display - same as view.php.
            if ($mygrade !== null && $instance->grade > 0) {
                $a = new \stdClass();
                $a->grade = format_float($mygrade, $instance->decimalpoints ?? 2);
                $a->maxgrade = format_float($instance->grade, $instance->decimalpoints ?? 2);
                $html .= '<div class="alert alert-success">';
                $html .= '<strong>' . get_string('yourfinalgradeis', 'game', get_string('outofshort', 'quiz', $a)) . '</strong>';
                $html .= '</div>';
            }
        }

        // Check if can start new attempt.
        $unfinishedattempt = $DB->get_record_sql(
            "SELECT * FROM {game_attempts} WHERE gameid = ? AND userid = ? AND timefinish = 0 ORDER BY attempt DESC LIMIT 1",
            [$instance->id, $USER->id]
        );

        // Determine button text.
        $buttontext = '';
        if ($unfinishedattempt) {
            if ($canattempt) {
                $buttontext = get_string('continueattemptgame', 'game');
            }
        } else {
            // Check max attempts.
            if ($instance->maxattempts > 0 && count($attempts) >= $instance->maxattempts) {
                $canattempt = false;
            }
            if ($canattempt) {
                $buttontext = count($attempts) == 0 ? get_string('attemptgamenow', 'game') : get_string('reattemptgame', 'game');
            }
        }

        // Play button.
        $html .= '<div class="text-center mt-3">';
        if ($buttontext) {
            $viewurl = new \moodle_url('/mod/game/view.php', ['id' => $cm->id]);
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play"></i> ' . $buttontext . '</a>';
        } else {
            // Max attempts reached or not available.
            $viewurl = new \moodle_url('/mod/game/view.php', ['id' => $cm->id]);
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-secondary btn-lg">';
            $html .= '<i class="fa fa-eye"></i> ' . get_string('modulename', 'game') . '</a>';
        }
        $html .= '</div>';

        // High scores - same as view.php.
        if (!empty($instance->highscore) && $instance->highscore > 0) {
            $highscores = $DB->get_records_sql(
                "SELECT u.id, u.firstname, u.lastname, MAX(ga.score) as maxscore
                 FROM {user} u
                 JOIN {game_attempts} ga ON ga.userid = u.id
                 WHERE ga.gameid = ? AND ga.score > 0
                 GROUP BY u.id, u.firstname, u.lastname
                 HAVING MAX(ga.score) > 0
                 ORDER BY MAX(ga.score) DESC",
                [$instance->id],
                0,
                $instance->highscore
            );

            if ($highscores && count($highscores) > 0) {
                $html .= '<div class="card mt-3"><div class="card-header">';
                $html .= '<strong>' . get_string('col_highscores', 'game') . '</strong>';
                $html .= '</div><div class="card-body p-0">';
                $html .= '<table class="table table-striped table-sm mb-0">';
                $html .= '<thead><tr><th>' . get_string('students') . '</th><th>' . get_string('percent', 'grades') . '</th></tr></thead>';
                $html .= '<tbody>';
                foreach ($highscores as $hs) {
                    $html .= '<tr>';
                    $html .= '<td>' . fullname($hs) . '</td>';
                    $html .= '<td>' . round($hs->maxscore * 100) . ' %</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div></div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get HotPot module content.
     */
    protected static function get_hotpot_content($cm, $instance, $course, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-hotpot-content">';

        // Attempts info.
        $attempts = $DB->get_records('hotpot_attempts', ['hotpotid' => $instance->id, 'userid' => $USER->id], 'timestart DESC');
        $numattempts = count($attempts);

        $html .= '<div class="card mb-3"><div class="card-body">';

        // Max attempts.
        if ($instance->attemptlimit > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attemptsallowed', 'hotpot') . ':</strong> ' . $instance->attemptlimit . '</p>';
        }

        // Time limit.
        if ($instance->timelimit > 0) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'hotpot') . ':</strong> ' . format_time($instance->timelimit) . '</p>';
        }

        // User attempts.
        $html .= '<p><i class="fa fa-list"></i> <strong>' . get_string('attempts', 'hotpot') . ':</strong> ' . $numattempts . '</p>';

        if ($numattempts > 0) {
            $bestattempt = reset($attempts);
            if ($bestattempt->score !== null) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('score', 'hotpot') . ':</strong> ' . format_float($bestattempt->score, 2) . '%</p>';
            }
        }

        $html .= '</div></div>';

        // Start button.
        $html .= '<div class="text-center">';
        $canstart = ($instance->attemptlimit == 0 || $numattempts < $instance->attemptlimit);
        if ($canstart) {
            $viewurl = new \moodle_url('/mod/hotpot/view.php', ['id' => $cm->id]);
            $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play-circle"></i> ' . get_string('modulename', 'hotpot') . '</a>';
        } else {
            $html .= '<div class="alert alert-warning">' . get_string('attemptsexceeded', 'hotpot') . '</div>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get HVP (H5P) module content.
     */
    protected static function get_hvp_content($cm, $instance, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-hvp-content">';

        $html .= '<div class="card mb-3"><div class="card-body">';

        // Get user attempts/results.
        $results = $DB->get_records('hvp_xapi_results', ['content_id' => $instance->id, 'user_id' => $USER->id], 'id DESC', '*', 0, 1);
        if ($results) {
            $result = reset($results);
            if (!empty($result->max_score) && $result->max_score > 0) {
                $percentage = round(($result->raw_score / $result->max_score) * 100);
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('score', 'hvp') . ':</strong> ';
                $html .= $result->raw_score . ' / ' . $result->max_score . ' (' . $percentage . '%)</p>';
            }
        }

        $html .= '</div></div>';

        // Launch button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/hvp/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-play-circle"></i> ' . get_string('modulename', 'hvp') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get ReadAloud module content.
     */
    protected static function get_readaloud_content($cm, $instance, $context): string {
        global $DB, $USER, $CFG;

        $html = '<div class="nexus-readaloud-content">';

        $html .= '<div class="card mb-3"><div class="card-body">';

        // Show passage preview.
        if (!empty($instance->passage)) {
            $html .= '<div class="nexus-passage-preview p-3 bg-light rounded mb-3">';
            $passage = strip_tags($instance->passage);
            $preview = \core_text::substr($passage, 0, 200);
            if (\core_text::strlen($passage) > 200) {
                $preview .= '...';
            }
            $html .= '<p class="mb-0">' . $preview . '</p>';
            $html .= '</div>';
        }

        // User attempts.
        $attempts = $DB->count_records('readaloud_attempt', ['readaloudid' => $instance->id, 'userid' => $USER->id]);
        $html .= '<p><i class="fa fa-microphone"></i> <strong>' . get_string('attempts', 'readaloud') . ':</strong> ' . $attempts . '</p>';

        if ($attempts > 0) {
            // Get best score.
            $bestscore = $DB->get_field_sql(
                "SELECT MAX(sessionscore) FROM {readaloud_attempt} WHERE readaloudid = ? AND userid = ?",
                [$instance->id, $USER->id]
            );
            if ($bestscore !== false && $bestscore !== null) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('bestscore', 'readaloud') . ':</strong> ' . format_float($bestscore, 0) . '</p>';
            }
        }

        $html .= '</div></div>';

        // Start button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/readaloud/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-microphone"></i> ' . get_string('modulename', 'readaloud') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get PDF Protect module content.
     */
    protected static function get_pdfprotect_content($cm, $instance, $context): string {
        global $CFG;

        $html = '<div class="nexus-pdfprotect-content">';

        $html .= '<div class="card mb-3"><div class="card-body text-center">';
        $html .= '<p><i class="fa fa-file-pdf-o fa-3x mb-3"></i></p>';
        $html .= '<p>' . get_string('clicktoview', 'pdfprotect') . '</p>';
        $html .= '</div></div>';

        // View button.
        $html .= '<div class="text-center">';
        $viewurl = new \moodle_url('/mod/pdfprotect/view.php', ['id' => $cm->id]);
        $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-eye"></i> ' . get_string('modulename', 'pdfprotect') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Get generic activity content for unsupported/unanalyzed modules.
     * This provides a rich fallback experience for any activity type.
     */
    protected static function get_generic_activity_content($cm, $cminfo, $context): string {
        global $CFG, $DB;

        $modname = $cm->modname;
        $html = '<div class="nexus-generic-content">';

        // Get the human-readable module name.
        $modulename = get_string('pluginname', 'mod_' . $modname);

        // Info card about this activity type.
        $html .= '<div class="card mb-4">';
        $html .= '<div class="card-body text-center">';

        // Activity type icon and name.
        $html .= '<div class="mb-3">';
        $html .= '<img src="' . $cminfo->get_icon_url() . '" alt="" class="mb-2" style="width: 48px; height: 48px;" />';
        $html .= '<h5 class="card-title">' . $modulename . '</h5>';
        $html .= '</div>';

        // Informative message.
        $html .= '<p class="text-muted mb-3">';
        $html .= '<i class="fa fa-info-circle"></i> ';
        $html .= get_string('activity_requires_fullview', 'format_nexusformat');
        $html .= '</p>';

        // Primary action button.
        if ($cminfo->url) {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg" target="_blank">';
            $html .= '<i class="fa fa-external-link"></i> ';
            $html .= get_string('openactivity', 'format_nexusformat');
            $html .= '</a>';
        }

        $html .= '</div></div>';

        // Try to show additional module-specific information.
        $instance = $DB->get_record($modname, ['id' => $cm->instance]);
        if ($instance) {
            $additionalinfo = self::get_generic_module_info($instance, $modname);
            if (!empty($additionalinfo)) {
                $html .= '<div class="card">';
                $html .= '<div class="card-header"><strong>' . get_string('activityinfo', 'format_nexusformat') . '</strong></div>';
                $html .= '<div class="card-body">' . $additionalinfo . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Get generic information from common module fields.
     */
    protected static function get_generic_module_info($instance, string $modname): string {
        $info = [];

        // Check for common date fields.
        if (!empty($instance->timeopen) && $instance->timeopen > 0) {
            $info[] = '<p><i class="fa fa-calendar"></i> <strong>' . get_string('open', 'form') . ':</strong> ' .
                      userdate($instance->timeopen) . '</p>';
        }
        if (!empty($instance->timeclose) && $instance->timeclose > 0) {
            $dueclass = ($instance->timeclose < time()) ? 'text-danger' : 'text-success';
            $info[] = '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('close', 'form') . ':</strong> ' .
                      '<span class="' . $dueclass . '">' . userdate($instance->timeclose) . '</span></p>';
        }
        if (!empty($instance->duedate) && $instance->duedate > 0) {
            $dueclass = ($instance->duedate < time()) ? 'text-danger' : 'text-success';
            $info[] = '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('duedate', 'assign') . ':</strong> ' .
                      '<span class="' . $dueclass . '">' . userdate($instance->duedate) . '</span></p>';
        }

        // Check for attempts/tries.
        if (isset($instance->attempts) && $instance->attempts > 0) {
            $info[] = '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attempts', 'quiz') . ':</strong> ' .
                      $instance->attempts . '</p>';
        }
        if (isset($instance->maxattempts) && $instance->maxattempts > 0) {
            $info[] = '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attempts', 'quiz') . ':</strong> ' .
                      $instance->maxattempts . '</p>';
        }

        // Check for time limit.
        if (!empty($instance->timelimit) && $instance->timelimit > 0) {
            $info[] = '<p><i class="fa fa-hourglass-half"></i> <strong>' . get_string('timelimit', 'quiz') . ':</strong> ' .
                      format_time($instance->timelimit) . '</p>';
        }

        // Check for grade.
        if (isset($instance->grade) && $instance->grade > 0) {
            $info[] = '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ' .
                      format_float($instance->grade, 2) . '</p>';
        }

        return implode('', $info);
    }

    /**
     * Get default content for interactive activities (fallback).
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
     * Process completion placeholders in content.
     * Moodle uses [[placeholder]] syntax for completion conditions.
     *
     * @param string $content The content to process
     * @param \cm_info $cminfo Course module info
     * @param object $course Course object
     * @return string Processed content
     */
    protected static function process_completion_placeholders(string $content, $cminfo, $course): string {
        global $DB, $CFG;

        // If no placeholders, return as-is.
        if (strpos($content, '[[') === false) {
            return $content;
        }

        // Get grade item for this activity.
        $gradeitem = $DB->get_record('grade_items', [
            'itemtype' => 'mod',
            'itemmodule' => $cminfo->modname,
            'iteminstance' => $cminfo->instance,
            'courseid' => $course->id
        ]);

        $replacements = [];

        if ($gradeitem) {
            // Grade to pass.
            if (!empty($gradeitem->gradepass)) {
                $replacements['[[gradetopass]]'] = format_float($gradeitem->gradepass, 2);
            } else {
                $replacements['[[gradetopass]]'] = '-';
            }

            // Grade max.
            if (!empty($gradeitem->grademax)) {
                $replacements['[[grademax]]'] = format_float($gradeitem->grademax, 2);
            }

            // Grade min.
            if (isset($gradeitem->grademin)) {
                $replacements['[[grademin]]'] = format_float($gradeitem->grademin, 2);
            }
        } else {
            // Default replacements if no grade item.
            $replacements['[[gradetopass]]'] = '-';
            $replacements['[[grademax]]'] = '-';
            $replacements['[[grademin]]'] = '0';
        }

        // Apply replacements.
        foreach ($replacements as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }

        // Remove any remaining unprocessed placeholders to avoid showing raw [[...]] text.
        $content = preg_replace('/\[\[[^\]]+\]\]/', '', $content);

        return $content;
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
