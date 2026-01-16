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
 * External function for getting activity content using native Moodle renderers.
 *
 * This class replicates the rendering logic from each module's view.php,
 * using the same renderers and output classes that Moodle uses natively.
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
     * Get the content of an activity using native renderers.
     *
     * @param int $cmid Course module ID
     * @return array Activity content data
     */
    public static function execute(int $cmid): array {
        global $DB, $PAGE, $OUTPUT, $CFG, $USER;

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

        // Get module instance.
        $modname = $cm->modname;
        $instance = $DB->get_record($modname, ['id' => $cm->instance], '*', MUST_EXIST);

        // Initialize PAGE for renderers.
        self::init_page_for_renderer($cm, $cminfo, $course, $context);

        // Build the content using native renderers.
        $content = self::render_activity_content($modname, $cm, $cminfo, $instance, $course, $context);

        return [
            'content' => $content,
            'cmid' => $cmid,
            'modname' => $modname,
            'name' => $cminfo->get_formatted_name(),
            'url' => $cminfo->url ? $cminfo->url->out(false) : '',
        ];
    }

    /**
     * Initialize PAGE object for renderer usage.
     */
    protected static function init_page_for_renderer($cm, $cminfo, $course, $context): void {
        global $PAGE;

        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_cm($cminfo);
        $PAGE->set_url('/mod/' . $cm->modname . '/view.php', ['id' => $cm->id]);
        $PAGE->set_title($cminfo->get_formatted_name());
        $PAGE->set_heading($course->fullname);
    }

    /**
     * Main render method that dispatches to module-specific renderers.
     */
    protected static function render_activity_content(string $modname, $cm, $cminfo, $instance, $course, $context): string {
        global $OUTPUT;

        $html = '';

        // Activity header.
        $html .= '<div class="nexus-activity-container">';
        $html .= '<div class="nexus-activity-header mb-3">';
        $html .= '<h3 class="nexus-activity-title d-flex align-items-center">';
        $html .= '<img src="' . $cminfo->get_icon_url() . '" alt="" class="me-2" style="width:24px;height:24px;" /> ';
        $html .= format_string($cminfo->name);
        $html .= '</h3>';

        // Module intro.
        if (!empty($instance->intro)) {
            $html .= '<div class="nexus-activity-intro">';
            $html .= format_module_intro($modname, $instance, $cm->id);
            $html .= '</div>';
        }
        $html .= '</div>';

        // Main content using native renderer.
        $html .= '<div class="nexus-activity-content">';

        try {
            $renderedContent = self::render_module($modname, $cm, $cminfo, $instance, $course, $context);
            $html .= $renderedContent;
        } catch (\Exception $e) {
            $html .= '<div class="alert alert-warning">';
            $html .= '<i class="fa fa-exclamation-triangle"></i> ';
            $html .= get_string('error') . ': ' . s($e->getMessage());
            $html .= '</div>';
            // Fallback link.
            $html .= '<div class="text-center mt-3">';
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary">';
            $html .= get_string('view') . ' ' . get_string('modulename', $modname);
            $html .= '</a></div>';
        }

        $html .= '</div>'; // Close nexus-activity-content.
        $html .= '</div>'; // Close nexus-activity-container.

        return $html;
    }

    /**
     * Dispatch to specific module renderer.
     */
    protected static function render_module(string $modname, $cm, $cminfo, $instance, $course, $context): string {
        $method = 'render_' . $modname;

        if (method_exists(self::class, $method)) {
            return self::$method($cm, $cminfo, $instance, $course, $context);
        }

        // Default: show link to view the activity.
        return self::render_default($cm, $cminfo, $instance, $modname);
    }

    // =========================================================================
    // MODULE-SPECIFIC RENDERERS - Each replicates view.php logic
    // =========================================================================

    /**
     * Render PAGE module - replicates mod/page/view.php
     */
    protected static function render_page($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/page/lib.php');

        // Replicate view.php lines 88-99.
        $content = file_rewrite_pluginfile_urls(
            $instance->content,
            'pluginfile.php',
            $context->id,
            'mod_page',
            'content',
            $instance->revision
        );

        // Same format options as view.php.
        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;

        $html = '<div class="page-content-container">';
        $html .= format_text($content, $instance->contentformat, $formatoptions);
        $html .= '</div>';

        // Show last modified like view.php.
        $options = empty($instance->displayoptions) ? [] : (array) @unserialize($instance->displayoptions);
        if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
            $html .= '<div class="modified text-muted small mt-3">';
            $html .= get_string('lastmodified') . ': ' . userdate($instance->timemodified);
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Render RESOURCE module - replicates mod/resource/view.php using resourcelib
     */
    protected static function render_resource($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/resource/lib.php');
        require_once($CFG->dirroot . '/mod/resource/locallib.php');
        require_once($CFG->libdir . '/resourcelib.php');

        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 'sortorder DESC, id ASC', false);

        if (count($files) < 1) {
            return '<div class="alert alert-warning">' . get_string('filenotfound', 'resource') . '</div>';
        }

        $file = reset($files);
        $instance->mainfile = $file->get_filename();

        // Use resource_get_final_display_type like view.php.
        $displaytype = resource_get_final_display_type($instance);

        $mimetype = $file->get_mimetype();
        $path = '/' . $context->id . '/mod_resource/content/' . $instance->revision .
                $file->get_filepath() . $file->get_filename();
        $fullurl = \moodle_url::make_file_url('/pluginfile.php', $path, false);
        $downloadurl = \moodle_url::make_file_url('/pluginfile.php', $path, true);

        $html = '<div class="resource-content-container">';

        // Handle printintro option.
        $options = empty($instance->displayoptions) ? [] : (array) @unserialize($instance->displayoptions);

        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
                $html .= self::render_resource_embed($file, $fullurl, $mimetype, $instance, $context);
                break;

            case RESOURCELIB_DISPLAY_FRAME:
                $html .= '<iframe src="' . s($fullurl) . '" class="w-100 border-0" style="height:600px;"></iframe>';
                break;

            default:
                // OPEN, NEW, POPUP, DOWNLOAD - show download/open button.
                $html .= '<div class="text-center p-4">';
                $html .= '<div class="mb-3"><i class="fa fa-file fa-4x text-primary"></i></div>';
                $html .= '<h5>' . s($file->get_filename()) . '</h5>';
                $html .= '<p class="text-muted">' . display_size($file->get_filesize()) . '</p>';

                if ($displaytype == RESOURCELIB_DISPLAY_DOWNLOAD) {
                    $html .= '<a href="' . s($downloadurl) . '" class="btn btn-primary btn-lg">';
                    $html .= '<i class="fa fa-download"></i> ' . get_string('download') . '</a>';
                } else {
                    $html .= '<a href="' . s($fullurl) . '" target="_blank" class="btn btn-primary btn-lg">';
                    $html .= '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'resource') . '</a>';
                }
                $html .= '</div>';
                break;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Helper to render embedded resource content.
     */
    protected static function render_resource_embed($file, $fullurl, $mimetype, $instance, $context): string {
        $html = '';
        $mediamanager = \core_media_manager::instance();
        $embedoptions = [
            \core_media_manager::OPTION_TRUSTED => true,
            \core_media_manager::OPTION_BLOCK => true,
        ];

        if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
            $html .= '<div class="text-center">';
            $html .= '<img class="img-fluid" alt="' . s($file->get_filename()) . '" src="' . s($fullurl) . '" />';
            $html .= '</div>';
        } else if (file_mimetype_in_typegroup($mimetype, 'web_video') || file_mimetype_in_typegroup($mimetype, 'web_audio')) {
            $html .= '<div class="media-container">';
            $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $instance->name, 0, 0, $embedoptions);
            $html .= '</div>';
        } else if ($mimetype === 'application/pdf') {
            $html .= '<embed src="' . s($fullurl) . '" type="application/pdf" class="w-100" style="height:600px;" />';
        } else {
            $html .= '<iframe src="' . s($fullurl) . '" class="w-100 border-0" style="height:600px;"></iframe>';
        }

        return $html;
    }

    /**
     * Render URL module - replicates mod/url/view.php using url locallib
     */
    protected static function render_url($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/url/lib.php');
        require_once($CFG->dirroot . '/mod/url/locallib.php');
        require_once($CFG->libdir . '/resourcelib.php');

        // Get full URL with parameters like view.php.
        $fullurl = url_get_full_url($instance, $cm, $course);
        $displaytype = url_get_final_display_type($instance);

        $html = '<div class="url-content-container">';

        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
            case RESOURCELIB_DISPLAY_FRAME:
                $mimetype = resourcelib_guess_url_mimetype($fullurl);
                $mediamanager = \core_media_manager::instance();

                if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
                    $html .= '<div class="text-center">';
                    $html .= '<img class="img-fluid" alt="' . s($instance->name) . '" src="' . s($fullurl) . '" />';
                    $html .= '</div>';
                } else if (file_mimetype_in_typegroup($mimetype, ['web_video', 'web_audio'])) {
                    $html .= $mediamanager->embed_url(new \moodle_url($fullurl), $instance->name, 0, 0, [
                        \core_media_manager::OPTION_TRUSTED => true,
                        \core_media_manager::OPTION_BLOCK => true,
                    ]);
                } else {
                    $html .= '<iframe src="' . s($fullurl) . '" class="w-100 border-0" style="height:600px;"></iframe>';
                }
                break;

            default:
                // Show link button.
                $html .= '<div class="text-center p-4">';
                $html .= '<div class="mb-3"><i class="fa fa-external-link fa-4x text-primary"></i></div>';
                $html .= '<a href="' . s($fullurl) . '" target="_blank" rel="noopener" class="btn btn-primary btn-lg">';
                $html .= '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'url') . '</a>';
                $html .= '</div>';
                break;
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render BOOK module - replicates mod/book/view.php using book locallib
     */
    protected static function render_book($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        // Use book_preload_chapters like view.php.
        $chapters = book_preload_chapters($instance);

        if (empty($chapters)) {
            return '<div class="alert alert-info">' . get_string('nocontent', 'mod_book') . '</div>';
        }

        $viewhidden = has_capability('mod/book:viewhiddenchapters', $context);

        // Find first visible chapter.
        $firstchapter = null;
        foreach ($chapters as $ch) {
            if ($viewhidden || !$ch->hidden) {
                $firstchapter = $ch;
                break;
            }
        }

        if (!$firstchapter) {
            return '<div class="alert alert-info">' . get_string('nocontent', 'mod_book') . '</div>';
        }

        $html = '<div class="book-content-container">';

        // Layout with TOC.
        $html .= '<div class="row">';

        // TOC sidebar.
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card">';
        $html .= '<div class="card-header"><strong>' . get_string('toc', 'mod_book') . '</strong></div>';
        $html .= '<ul class="list-group list-group-flush">';

        foreach ($chapters as $ch) {
            if ($ch->hidden && !$viewhidden) {
                continue;
            }
            $active = ($ch->id == $firstchapter->id) ? 'active' : '';
            $dimmed = $ch->hidden ? 'text-muted' : '';
            $indent = $ch->subchapter ? 'ps-4' : '';

            $churl = new \moodle_url('/mod/book/view.php', ['id' => $cm->id, 'chapterid' => $ch->id]);
            $html .= '<a href="' . $churl->out() . '" class="list-group-item list-group-item-action ' . $active . ' ' . $dimmed . ' ' . $indent . '">';
            $html .= format_string($ch->title);
            $html .= '</a>';
        }

        $html .= '</ul></div></div>';

        // Chapter content.
        $html .= '<div class="col-md-9">';

        if (!$instance->customtitles) {
            $html .= '<h4>' . format_string($firstchapter->title) . '</h4>';
        }

        $chaptercontent = file_rewrite_pluginfile_urls(
            $firstchapter->content,
            'pluginfile.php',
            $context->id,
            'mod_book',
            'chapter',
            $firstchapter->id
        );

        $html .= '<div class="book-chapter-content">';
        $html .= format_text($chaptercontent, $firstchapter->contentformat, ['noclean' => true, 'overflowdiv' => true, 'context' => $context]);
        $html .= '</div>';

        $html .= '</div>'; // col-md-9
        $html .= '</div>'; // row
        $html .= '</div>'; // book-content-container

        return $html;
    }

    /**
     * Render FOLDER module - uses native mod_folder renderer
     */
    protected static function render_folder($cm, $cminfo, $instance, $course, $context): string {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/mod/folder/lib.php');

        // Get folder renderer - this is the cleanest native renderer usage.
        $renderer = $PAGE->get_renderer('mod_folder');

        // The display_folder method returns clean HTML.
        return $renderer->display_folder($instance);
    }

    /**
     * Render QUIZ module - replicates mod/quiz/view.php using quiz classes
     */
    protected static function render_quiz($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $html = '<div class="quiz-content-container">';

        $canpreview = has_capability('mod/quiz:preview', $context);
        $canattempt = has_capability('mod/quiz:attempt', $context);
        $canmanage = has_capability('mod/quiz:manage', $context);
        $canviewreports = has_capability('mod/quiz:viewreports', $context);

        // Try to use quiz_settings and access_manager like view.php.
        $accessmanager = null;
        $infomessages = [];
        try {
            $quizobj = \mod_quiz\quiz_settings::create_for_cmid($cm->id, $USER->id);
            $accessmanager = new \mod_quiz\access_manager($quizobj, time(),
                has_capability('mod/quiz:ignoretimelimits', $context, null, false));
            $infomessages = $accessmanager->describe_rules();
        } catch (\Exception $e) {
            // Continue without access manager.
        }

        // Quiz info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Show access rules.
        if (!empty($infomessages)) {
            $html .= '<div class="quiz-rules mb-3">';
            foreach ($infomessages as $msg) {
                $html .= '<div class="text-info"><i class="fa fa-info-circle"></i> ' . $msg . '</div>';
            }
            $html .= '</div>';
        }

        // Time limit.
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'quiz') . ':</strong> ';
            $html .= format_time($instance->timelimit) . '</p>';
        }

        // Attempts.
        $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attemptsallowed', 'quiz') . ':</strong> ';
        $html .= $instance->attempts ? $instance->attempts : get_string('unlimited') . '</p>';

        // Grading method.
        if ($instance->attempts != 1) {
            $html .= '<p><i class="fa fa-calculator"></i> <strong>' . get_string('gradingmethod', 'quiz',
                quiz_get_grading_option_name($instance->grademethod)) . '</strong></p>';
        }

        // Grade to pass.
        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod', 'itemmodule' => 'quiz',
            'iteminstance' => $instance->id, 'courseid' => $course->id
        ]);
        if ($gradeitem && grade_floats_different($gradeitem->gradepass, 0)) {
            $a = new \stdClass();
            $a->grade = quiz_format_grade($instance, $gradeitem->gradepass);
            $a->maxgrade = quiz_format_grade($instance, $instance->grade);
            $html .= '<p><i class="fa fa-graduation-cap"></i> <strong>' . get_string('gradetopassoutof', 'quiz', $a) . '</strong></p>';
        }

        $html .= '</div></div>';

        // User attempts.
        if (!$canmanage && !$canviewreports) {
            $attempts = quiz_get_user_attempts($instance->id, $USER->id, 'finished', true);
            $unfinished = quiz_get_user_attempt_unfinished($instance->id, $USER->id);

            // Best grade.
            if ($attempts) {
                $mygrade = quiz_get_best_grade($instance, $USER->id);
                if ($mygrade !== null) {
                    $html .= '<div class="alert alert-info">';
                    $html .= '<strong>' . get_string('yourfinalgradeis', 'quiz',
                        quiz_format_grade($instance, $mygrade) . ' / ' . quiz_format_grade($instance, $instance->grade)) . '</strong>';
                    $html .= '</div>';
                }

                // Attempts table.
                $html .= '<div class="table-responsive mb-3">';
                $html .= '<table class="table table-striped">';
                $html .= '<thead><tr><th>' . get_string('attempt', 'quiz') . '</th>';
                $html .= '<th>' . get_string('state', 'quiz') . '</th>';
                $html .= '<th>' . get_string('grade', 'grades') . '</th></tr></thead><tbody>';

                $attemptnum = 1;
                foreach ($attempts as $attempt) {
                    $html .= '<tr><td>' . $attemptnum++ . '</td>';
                    $html .= '<td>' . quiz_attempt_state_name($attempt->state) . '</td>';
                    if ($attempt->sumgrades !== null) {
                        $grade = quiz_rescale_grade($attempt->sumgrades, $instance, false);
                        $html .= '<td>' . quiz_format_grade($instance, $grade) . '/' . quiz_format_grade($instance, $instance->grade) . '</td>';
                    } else {
                        $html .= '<td>-</td>';
                    }
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
            }

            // Action button.
            $html .= '<div class="text-center">';
            $numattempts = count($attempts);
            $nomoreattempts = $instance->attempts && $numattempts >= $instance->attempts;

            if ($unfinished) {
                $buttontext = $canpreview ? get_string('continuepreview', 'quiz') : get_string('continueattemptquiz', 'quiz');
            } else if ($nomoreattempts && !$canpreview) {
                $html .= '<div class="alert alert-warning">' . get_string('nomoreattempts', 'quiz') . '</div>';
                $buttontext = '';
            } else if ($canpreview) {
                $buttontext = get_string('previewquizstart', 'quiz');
            } else if ($numattempts == 0) {
                $buttontext = get_string('attemptquiz', 'quiz');
            } else {
                $buttontext = get_string('reattemptquiz', 'quiz');
            }

            if ($buttontext) {
                $viewurl = new \moodle_url('/mod/quiz/view.php', ['id' => $cm->id]);
                $html .= '<a href="' . $viewurl->out() . '" class="btn btn-primary btn-lg">';
                $html .= '<i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
            }
            $html .= '</div>';
        } else {
            // Teacher view - link to reports.
            $html .= '<div class="text-center">';
            $reportsurl = new \moodle_url('/mod/quiz/report.php', ['id' => $cm->id, 'mode' => 'overview']);
            $html .= '<a href="' . $reportsurl->out() . '" class="btn btn-primary btn-lg me-2">';
            $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('viewreports', 'quiz') . '</a>';

            if ($canmanage) {
                $editurl = new \moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]);
                $html .= '<a href="' . $editurl->out() . '" class="btn btn-secondary btn-lg">';
                $html .= '<i class="fa fa-pencil"></i> ' . get_string('editquiz', 'quiz') . '</a>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render ASSIGN module - replicates mod/assign/view.php using assign class
     */
    protected static function render_assign($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER, $OUTPUT;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $html = '<div class="assign-content-container">';

        // Create assign object like view.php.
        $assign = new \assign($context, $cm, $course);
        $assign->update_effective_access($USER->id);

        $cangrade = has_capability('mod/assign:grade', $context);
        $cansubmit = has_capability('mod/assign:submit', $context);

        // Get effective instance (with user overrides).
        $effectiveinstance = $assign->get_instance();

        // Assignment info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Due date.
        if ($effectiveinstance->duedate) {
            $dueclass = ($effectiveinstance->duedate < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('duedate', 'assign') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($effectiveinstance->duedate) . '</span></p>';

            if ($effectiveinstance->duedate > time()) {
                $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timeremaining', 'assign') . ':</strong> ';
                $html .= format_time($effectiveinstance->duedate - time()) . '</p>';
            }
        }

        // Cutoff date.
        if ($effectiveinstance->cutoffdate) {
            $html .= '<p><i class="fa fa-ban"></i> <strong>' . get_string('cutoffdate', 'assign') . ':</strong> ';
            $html .= userdate($effectiveinstance->cutoffdate) . '</p>';
        }

        $html .= '</div></div>';

        if ($cangrade) {
            // Teacher view - grading summary.
            $summary = $assign->get_assign_grading_summary_renderable();
            if ($summary) {
                $html .= '<div class="card mb-3">';
                $html .= '<div class="card-header"><strong>' . get_string('gradingsummary', 'assign') . '</strong></div>';
                $html .= '<div class="card-body">';

                $html .= '<p>' . get_string('numberofparticipants', 'assign') . ': ' . $summary->participantcount . '</p>';
                $html .= '<p>' . get_string('numberofsubmittedassignments', 'assign') . ': ' . $summary->submissionssubmittedcount . '</p>';
                $html .= '<p>' . get_string('numberofsubmissionsneedgrading', 'assign') . ': ' . $summary->submissionsneedgradingcount . '</p>';

                $html .= '</div></div>';
            }

            $html .= '<div class="text-center">';
            $gradeurl = new \moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grading']);
            $html .= '<a href="' . $gradeurl->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-check-square"></i> ' . get_string('viewgrading', 'assign') . '</a>';
            $html .= '</div>';
        } else {
            // Student view - submission status.
            $submission = $assign->get_user_submission($USER->id, false);
            $grade = $assign->get_user_grade($USER->id, false);

            $html .= '<div class="card mb-3"><div class="card-body">';

            // Submission status.
            if ($submission) {
                $statusclass = ($submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) ? 'bg-success' : 'bg-warning';
                $html .= '<p><i class="fa fa-check-square-o"></i> <strong>' . get_string('submissionstatus', 'assign') . ':</strong> ';
                $html .= '<span class="badge ' . $statusclass . '">' . get_string('submissionstatus_' . $submission->status, 'assign') . '</span></p>';

                if ($submission->timemodified) {
                    $html .= '<p><i class="fa fa-edit"></i> <strong>' . get_string('timemodified', 'assign') . ':</strong> ';
                    $html .= userdate($submission->timemodified) . '</p>';
                }
            } else {
                $html .= '<p><i class="fa fa-exclamation-circle"></i> <strong>' . get_string('submissionstatus', 'assign') . ':</strong> ';
                $html .= '<span class="badge bg-secondary">' . get_string('nosubmission', 'assign') . '</span></p>';
            }

            // Grade.
            if ($grade && $grade->grade !== null && $grade->grade >= 0) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ';
                $html .= format_float($grade->grade, 2) . ' / ' . format_float($effectiveinstance->grade, 2) . '</p>';
            }

            $html .= '</div></div>';

            // Action button.
            $html .= '<div class="text-center">';
            $submissionsopen = $assign->submissions_open($USER->id);

            if ($submissionsopen && (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED)) {
                $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
                $html .= '<i class="fa fa-upload"></i> ' . get_string('addsubmission', 'assign') . '</a>';
            } else {
                $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-secondary btn-lg">';
                $html .= '<i class="fa fa-eye"></i> ' . get_string('viewsubmission', 'assign') . '</a>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render FORUM module - replicates mod/forum/view.php using forum vaults
     */
    protected static function render_forum($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $html = '<div class="forum-content-container">';

        // Forum type badge.
        $forumtypes = forum_get_forum_types();
        if (isset($forumtypes[$instance->type])) {
            $html .= '<div class="mb-3"><span class="badge bg-secondary">' . $forumtypes[$instance->type] . '</span></div>';
        }

        // Get discussions.
        $discussions = $DB->get_records_sql(
            "SELECT d.*, u.firstname, u.lastname,
                    (SELECT COUNT(*) FROM {forum_posts} p WHERE p.discussion = d.id) - 1 as replycount
             FROM {forum_discussions} d
             JOIN {user} u ON u.id = d.userid
             WHERE d.forum = ?
             ORDER BY d.pinned DESC, d.timemodified DESC
             LIMIT 15",
            [$instance->id]
        );

        $totalcount = $DB->count_records('forum_discussions', ['forum' => $instance->id]);

        $html .= '<div class="mb-3 text-muted"><i class="fa fa-comments"></i> ' . $totalcount . ' ' . get_string('discussions', 'forum') . '</div>';

        if ($discussions) {
            $html .= '<div class="list-group mb-3">';
            foreach ($discussions as $d) {
                $durl = new \moodle_url('/mod/forum/discuss.php', ['d' => $d->id]);
                $html .= '<a href="' . $durl->out() . '" class="list-group-item list-group-item-action">';

                if ($d->pinned) {
                    $html .= '<span class="badge bg-warning float-end"><i class="fa fa-thumb-tack"></i></span>';
                }

                $html .= '<h6 class="mb-1">' . format_string($d->name) . '</h6>';
                $html .= '<small class="text-muted">' . get_string('startedby', 'forum') . ' ' . fullname($d);
                $html .= ' &middot; ' . $d->replycount . ' ' . get_string('replies', 'forum') . '</small>';
                $html .= '</a>';
            }
            $html .= '</div>';

            if ($totalcount > 15) {
                $html .= '<div class="text-center mb-3">';
                $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-sm btn-outline-secondary">';
                $html .= get_string('viewalldiscussions', 'forum', $totalcount) . '</a></div>';
            }
        } else {
            $html .= '<div class="alert alert-info">' . get_string('nodiscussions', 'forum') . '</div>';
        }

        // Add discussion button.
        $html .= '<div class="text-center">';
        if (forum_user_can_post_discussion($instance, null, -1, $cm, $context)) {
            $addurl = new \moodle_url('/mod/forum/post.php', ['forum' => $instance->id]);
            $html .= '<a href="' . $addurl->out() . '" class="btn btn-primary">';
            $html .= '<i class="fa fa-plus"></i> ' . get_string('addanewdiscussion', 'forum') . '</a> ';
        }
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewalldiscussions', 'forum') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render LESSON module - replicates mod/lesson/view.php using lesson class
     */
    protected static function render_lesson($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/lesson/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $html = '<div class="lesson-content-container">';

        // Create lesson object.
        $lesson = new \lesson($instance, $cm, $course);
        $lesson->update_effective_access($USER->id);

        $canmanage = $lesson->can_manage();

        // Check restrictions.
        $isopen = !$lesson->get_time_restriction_status();
        $hasdependencies = $lesson->get_dependencies_restriction_status();

        // Lesson info card.
        $html .= '<div class="card mb-3"><div class="card-body">';

        // Pages count.
        $pagecount = $DB->count_records('lesson_pages', ['lessonid' => $lesson->id]);
        $html .= '<p><i class="fa fa-file-text-o"></i> <strong>' . get_string('pages', 'lesson') . ':</strong> ' . $pagecount . '</p>';

        // Time limit.
        if ($lesson->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'lesson') . ':</strong> ';
            $html .= format_time($lesson->timelimit) . '</p>';
        }

        // Deadline.
        if ($lesson->deadline > 0) {
            $dueclass = ($lesson->deadline < time()) ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('deadline', 'lesson') . ':</strong> ';
            $html .= '<span class="' . $dueclass . '">' . userdate($lesson->deadline) . '</span></p>';
        }

        // Max attempts.
        if ($lesson->maxattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('maximumnumberofattempts', 'lesson') . ':</strong> ';
            $html .= $lesson->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        // User progress.
        if (!$canmanage) {
            $retries = $lesson->count_user_retries($USER->id);
            $attempts = $DB->get_records('lesson_grades', ['lessonid' => $lesson->id, 'userid' => $USER->id], 'completed DESC');

            if ($attempts) {
                $bestgrade = 0;
                $html .= '<div class="card mb-3">';
                $html .= '<div class="card-header"><strong>' . get_string('attempts', 'lesson') . '</strong></div>';
                $html .= '<div class="table-responsive"><table class="table table-sm mb-0">';
                $html .= '<thead><tr><th>' . get_string('attempt', 'lesson') . '</th>';
                $html .= '<th>' . get_string('grade', 'grades') . '</th></tr></thead><tbody>';

                $num = count($attempts);
                foreach ($attempts as $a) {
                    $bestgrade = max($bestgrade, $a->grade);
                    $html .= '<tr><td>' . $num-- . '</td><td>' . format_float($a->grade, 1) . '%</td></tr>';
                }
                $html .= '</tbody></table></div>';
                $html .= '<div class="card-footer">' . get_string('bestgrade', 'lesson') . ': <strong>' . format_float($bestgrade, 1) . '%</strong></div>';
                $html .= '</div>';
            }

            // Check incomplete attempt.
            $lastpageseen = $lesson->get_last_page_seen($retries);
            $hasincomplete = ($lastpageseen !== false && $lastpageseen != LESSON_EOL);
        }

        // Restrictions messages.
        if (!$isopen && !$canmanage) {
            $html .= '<div class="alert alert-warning"><i class="fa fa-clock-o"></i> ' . get_string('lessonnotready2', 'lesson') . '</div>';
        }
        if ($hasdependencies && !$canmanage) {
            $html .= '<div class="alert alert-warning"><i class="fa fa-link"></i> ' . get_string('completethefollowingconditions', 'lesson', '') . '</div>';
        }

        // Action button.
        $html .= '<div class="text-center">';
        $canstart = ($isopen && !$hasdependencies) || $canmanage;

        if (!$canmanage && isset($retries) && !$lesson->retake && $retries > 0) {
            $canstart = false;
            $html .= '<div class="alert alert-info">' . get_string('noretake', 'lesson') . '</div>';
        }

        if ($canstart) {
            $buttontext = get_string('startlesson', 'lesson');
            $buttonicon = 'fa-play-circle';

            if ($canmanage) {
                $buttontext = get_string('preview', 'lesson');
                $buttonicon = 'fa-eye';
            } else if (isset($hasincomplete) && $hasincomplete) {
                $buttontext = get_string('continuelesson', 'lesson');
                $buttonicon = 'fa-forward';
            } else if (isset($attempts) && $attempts) {
                $buttontext = get_string('retakelesson', 'lesson');
                $buttonicon = 'fa-refresh';
            }

            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa ' . $buttonicon . '"></i> ' . $buttontext . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render CHOICE module - replicates mod/choice/view.php
     */
    protected static function render_choice($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/choice/lib.php');

        $html = '<div class="choice-content-container">';

        // Get availability status like view.php.
        list($choiceavailable, $warnings) = choice_get_availability_status($instance);

        // Get options and responses.
        $options = $DB->get_records('choice_options', ['choiceid' => $instance->id], 'id');
        $current = choice_get_my_response($instance);
        $hasanswered = !empty($current);

        // Show warnings.
        if (!$choiceavailable && !empty($warnings)) {
            foreach ($warnings as $key => $msg) {
                $html .= '<div class="alert alert-warning"><i class="fa fa-exclamation-triangle"></i> ' . $msg . '</div>';
            }
        }

        // Show current selection.
        if ($hasanswered) {
            $html .= '<div class="alert alert-success"><i class="fa fa-check-circle"></i> <strong>' . get_string('yourselection', 'choice') . ':</strong><br>';
            foreach ($current as $c) {
                $optiontext = choice_get_option_text($instance, $c->optionid);
                $html .= '&bull; ' . format_string($optiontext) . '<br>';
            }
            $html .= '</div>';
        }

        // Show options if can vote.
        $canchoose = has_capability('mod/choice:choose', $context) && is_enrolled($context);
        $canupdate = $instance->allowupdate && $hasanswered && $choiceavailable;
        $canmakechoice = $canchoose && $choiceavailable && (!$hasanswered || $canupdate);

        if ($canmakechoice && $options) {
            $html .= '<form method="post" action="' . $cminfo->url->out() . '">';
            $html .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            $html .= '<input type="hidden" name="action" value="makechoice">';

            $html .= '<div class="list-group mb-3">';
            $inputtype = $instance->allowmultiple ? 'checkbox' : 'radio';
            $inputname = $instance->allowmultiple ? 'answer[]' : 'answer';

            foreach ($options as $opt) {
                $selected = false;
                foreach ($current as $c) {
                    if ($c->optionid == $opt->id) {
                        $selected = true;
                        break;
                    }
                }

                $html .= '<label class="list-group-item d-flex gap-2">';
                $html .= '<input class="form-check-input flex-shrink-0" type="' . $inputtype . '" ';
                $html .= 'name="' . $inputname . '" value="' . $opt->id . '"';
                if ($selected) {
                    $html .= ' checked';
                }
                $html .= '> <span>' . format_string($opt->text) . '</span></label>';
            }
            $html .= '</div>';

            $buttontext = $hasanswered ? get_string('updatechoice', 'choice') : get_string('savemychoice', 'choice');
            $html .= '<div class="text-center">';
            $html .= '<button type="submit" class="btn btn-primary btn-lg"><i class="fa fa-check"></i> ' . $buttontext . '</button>';
            $html .= '</div></form>';
        }

        // Show results if allowed.
        if (choice_can_view_results($instance, $current, $choiceavailable)) {
            $responses = $DB->get_records_sql_menu(
                "SELECT optionid, COUNT(*) FROM {choice_answers} WHERE choiceid = ? GROUP BY optionid",
                [$instance->id]
            );
            $total = array_sum($responses);

            $html .= '<div class="mt-4"><h5>' . get_string('responses', 'choice') . '</h5>';
            foreach ($options as $opt) {
                $count = isset($responses[$opt->id]) ? $responses[$opt->id] : 0;
                $pct = $total > 0 ? round(($count / $total) * 100) : 0;
                $html .= '<div class="mb-2">';
                $html .= '<div class="d-flex justify-content-between"><span>' . format_string($opt->text) . '</span>';
                $html .= '<span>' . $count . ' (' . $pct . '%)</span></div>';
                $html .= '<div class="progress" style="height:20px;">';
                $html .= '<div class="progress-bar" style="width:' . $pct . '%"></div></div></div>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render FEEDBACK module - replicates mod/feedback/view.php using mod_feedback_completion
     */
    protected static function render_feedback($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/feedback/lib.php');

        $html = '<div class="feedback-content-container">';

        // Use mod_feedback_completion like view.php.
        $feedbackcompletion = new \mod_feedback_completion($instance, $cm, $course->id);

        $canedititems = has_capability('mod/feedback:edititems', $context);
        $canviewreports = has_capability('mod/feedback:viewreports', $context);

        $isopen = $feedbackcompletion->is_open();
        $cancomplete = $feedbackcompletion->can_complete();
        $cansubmit = $feedbackcompletion->can_submit();

        // Teacher view.
        if ($canedititems || $canviewreports) {
            $itemcount = $DB->count_records('feedback_item', ['feedback' => $instance->id, 'hasvalue' => 1]);
            $completedcount = $DB->count_records('feedback_completed', ['feedback' => $instance->id]);

            $html .= '<div class="card mb-3">';
            $html .= '<div class="card-header"><strong>' . get_string('overview', 'feedback') . '</strong></div>';
            $html .= '<div class="card-body">';
            $html .= '<p><i class="fa fa-list"></i> ' . get_string('questions', 'feedback') . ': ' . $itemcount . '</p>';
            $html .= '<p><i class="fa fa-check-circle"></i> ' . get_string('completed_feedbacks', 'feedback') . ': ' . $completedcount . '</p>';

            if ($instance->anonymous == FEEDBACK_ANONYMOUS_YES) {
                $html .= '<p><i class="fa fa-user-secret"></i> ' . get_string('anonymous', 'feedback') . '</p>';
            }
            $html .= '</div></div>';

            $html .= '<div class="text-center">';
            if ($completedcount > 0) {
                $analysisurl = new \moodle_url('/mod/feedback/analysis.php', ['id' => $cm->id]);
                $html .= '<a href="' . $analysisurl->out() . '" class="btn btn-primary me-2">';
                $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('analysis', 'feedback') . '</a>';
            }
            if ($canedititems) {
                $editurl = new \moodle_url('/mod/feedback/edit.php', ['id' => $cm->id]);
                $html .= '<a href="' . $editurl->out() . '" class="btn btn-secondary">';
                $html .= '<i class="fa fa-pencil"></i> ' . get_string('edit_items', 'feedback') . '</a>';
            }
            $html .= '</div>';
        } else {
            // Student view.
            if (!$isopen) {
                $html .= '<div class="alert alert-warning"><i class="fa fa-clock-o"></i> ';
                $html .= get_string('feedback_is_not_open', 'feedback') . '</div>';
            } else if (!$cansubmit && $cancomplete) {
                $html .= '<div class="alert alert-success"><i class="fa fa-check-circle"></i> ';
                $html .= get_string('this_feedback_is_already_submitted', 'feedback') . '</div>';
            }

            if ($isopen && $cancomplete && $cansubmit) {
                $html .= '<div class="text-center">';
                $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
                $html .= '<i class="fa fa-pencil-square-o"></i> ' . get_string('complete_the_form', 'feedback') . '</a>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Render GLOSSARY module
     */
    protected static function render_glossary($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/glossary/lib.php');

        $html = '<div class="glossary-content-container">';

        $totalentries = $DB->count_records('glossary_entries', ['glossaryid' => $instance->id, 'approved' => 1]);

        $html .= '<div class="mb-3 text-muted"><i class="fa fa-book"></i> ' . $totalentries . ' ' . get_string('entries', 'glossary') . '</div>';

        // Alphabet navigation.
        $alphabet = explode(',', get_string('alphabet', 'langconfig'));
        $html .= '<div class="btn-group btn-group-sm flex-wrap mb-3">';
        $viewurl = new \moodle_url('/mod/glossary/view.php', ['id' => $cm->id, 'mode' => 'letter']);
        $html .= '<a href="' . $viewurl->out(['hook' => 'ALL']) . '" class="btn btn-outline-primary">' . get_string('all', 'glossary') . '</a>';
        foreach (array_slice($alphabet, 0, 10) as $letter) {
            $html .= '<a href="' . $viewurl->out(['hook' => trim($letter)]) . '" class="btn btn-outline-secondary">' . trim($letter) . '</a>';
        }
        $html .= '</div>';

        // Recent entries.
        $entries = $DB->get_records_sql(
            "SELECT e.*, u.firstname, u.lastname FROM {glossary_entries} e
             JOIN {user} u ON u.id = e.userid
             WHERE e.glossaryid = ? AND e.approved = 1
             ORDER BY e.timecreated DESC LIMIT 10",
            [$instance->id]
        );

        if ($entries) {
            $html .= '<div class="accordion" id="glossaryAccordion">';
            foreach ($entries as $entry) {
                $eid = 'entry' . $entry->id;
                $html .= '<div class="accordion-item">';
                $html .= '<h2 class="accordion-header"><button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#' . $eid . '">';
                $html .= '<strong>' . format_string($entry->concept) . '</strong></button></h2>';
                $html .= '<div id="' . $eid . '" class="accordion-collapse collapse"><div class="accordion-body">';
                $html .= format_text($entry->definition, $entry->definitionformat, ['context' => $context]);
                $html .= '</div></div></div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="text-center mt-3">';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary">';
        $html .= '<i class="fa fa-book"></i> ' . get_string('viewglossary', 'glossary') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render WIKI module
     */
    protected static function render_wiki($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/wiki/lib.php');
        require_once($CFG->dirroot . '/mod/wiki/locallib.php');

        $html = '<div class="wiki-content-container">';

        $subwiki = $DB->get_record('wiki_subwikis', ['wikiid' => $instance->id], '*', IGNORE_MULTIPLE);

        if ($subwiki) {
            $firstpage = $DB->get_record('wiki_pages', ['subwikiid' => $subwiki->id, 'title' => $instance->firstpagetitle]);

            if ($firstpage) {
                $html .= '<h5>' . format_string($firstpage->title) . '</h5>';
                $html .= '<div class="wiki-content border rounded p-3 mb-3">';
                $html .= format_text($firstpage->cachedcontent, FORMAT_HTML, ['context' => $context]);
                $html .= '</div>';
            } else {
                $html .= '<div class="alert alert-info">' . get_string('wikiempty', 'format_nexusformat') . '</div>';
            }
        }

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-book"></i> ' . get_string('view') . ' ' . get_string('modulename', 'wiki') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render DATA (database) module
     */
    protected static function render_data($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/data/lib.php');

        $html = '<div class="data-content-container">';

        $entrycount = $DB->count_records('data_records', ['dataid' => $instance->id]);
        $fieldcount = $DB->count_records('data_fields', ['dataid' => $instance->id]);

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-database"></i> ' . get_string('numrecords', 'data', $entrycount) . '</p>';
        $html .= '<p><i class="fa fa-columns"></i> ' . get_string('fields', 'data') . ': ' . $fieldcount . '</p>';

        if ($instance->requiredentries > 0) {
            $html .= '<p><i class="fa fa-exclamation-circle"></i> ' . get_string('requiredentries', 'data') . ': ' . $instance->requiredentries . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if (has_capability('mod/data:writeentry', $context)) {
            $addurl = new \moodle_url('/mod/data/edit.php', ['d' => $instance->id]);
            $html .= '<a href="' . $addurl->out() . '" class="btn btn-primary me-2">';
            $html .= '<i class="fa fa-plus"></i> ' . get_string('add', 'data') . '</a>';
        }
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-outline-primary">';
        $html .= '<i class="fa fa-list"></i> ' . get_string('view') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render SCORM module
     */
    protected static function render_scorm($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/scorm/lib.php');
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        $html = '<div class="scorm-content-container">';

        $attempts = scorm_get_attempt_count($USER->id, $instance);

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-repeat"></i> <strong>' . get_string('attempts', 'scorm') . ':</strong> ' . $attempts . '</p>';

        if ($instance->maxattempt > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> <strong>' . get_string('maximumattempts', 'scorm') . ':</strong> ' . $instance->maxattempt . '</p>';
        }

        if ($attempts > 0) {
            $score = scorm_grade_user($instance, $USER->id);
            if ($score !== false) {
                $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('grade', 'grades') . ':</strong> ' . format_float($score, 2) . '%</p>';
            }
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if ($instance->maxattempt > 0 && $attempts >= $instance->maxattempt) {
            $html .= '<div class="alert alert-warning">' . get_string('exceeded', 'scorm') . '</div>';
        } else {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-play-circle"></i> ' . get_string('enter', 'scorm') . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render LTI module
     */
    protected static function render_lti($cm, $cminfo, $instance, $course, $context): string {
        $html = '<div class="lti-content-container text-center p-4">';
        $html .= '<div class="mb-3"><i class="fa fa-external-link fa-4x text-primary"></i></div>';
        $html .= '<p class="lead">' . get_string('launchexternaltool', 'lti') . '</p>';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-external-link"></i> ' . get_string('launch', 'lti') . '</a>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render BigBlueButton module
     */
    protected static function render_bigbluebuttonbn($cm, $cminfo, $instance, $course, $context): string {
        global $DB;

        $html = '<div class="bbb-content-container">';

        $html .= '<div class="card mb-3"><div class="card-body">';

        if (!empty($instance->openingtime) && $instance->openingtime > 0) {
            $html .= '<p><i class="fa fa-calendar"></i> <strong>' . get_string('mod_form_field_openingtime', 'bigbluebuttonbn') . ':</strong> ';
            $html .= userdate($instance->openingtime) . '</p>';
        }

        if (!empty($instance->closingtime) && $instance->closingtime > 0) {
            $html .= '<p><i class="fa fa-calendar-times-o"></i> <strong>' . get_string('mod_form_field_closingtime', 'bigbluebuttonbn') . ':</strong> ';
            $html .= userdate($instance->closingtime) . '</p>';
        }

        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-video-camera"></i> ' . get_string('view_room', 'bigbluebuttonbn') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render CHAT module
     */
    protected static function render_chat($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/chat/lib.php');

        $html = '<div class="chat-content-container">';
        $timenow = time();

        $html .= '<div class="card mb-3"><div class="card-body">';

        // Next chat time.
        if (!empty($instance->chattime) && $instance->schedule > 0) {
            $nextchat = $instance->chattime;
            while ($nextchat < $timenow) {
                $nextchat += ($instance->schedule == 2) ? 86400 : 604800;
            }
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('nextchattime', 'chat') . ':</strong> ' . userdate($nextchat) . '</p>';
        }

        $html .= '</div></div>';

        // Current users.
        $currentusers = $DB->count_records('chat_users', ['chatid' => $instance->id]);
        if ($currentusers > 0) {
            $html .= '<div class="alert alert-success"><i class="fa fa-users"></i> ' . $currentusers . ' ' . get_string('currentusers', 'chat') . '</div>';
        }

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-comments"></i> ' . get_string('enterchat', 'chat') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render SURVEY module
     */
    protected static function render_survey($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/survey/lib.php');

        $html = '<div class="survey-content-container">';

        $surveytypes = [
            1 => 'ATTLS', 2 => 'COLLES (Preferred)', 3 => 'COLLES (Actual)',
            4 => 'COLLES (Preferred and Actual)', 5 => 'Critical Incidents'
        ];

        $html .= '<div class="card mb-3"><div class="card-body">';
        if (isset($surveytypes[$instance->template])) {
            $html .= '<p><i class="fa fa-list-alt"></i> <strong>' . get_string('surveytype', 'survey') . ':</strong> ' . $surveytypes[$instance->template] . '</p>';
        }

        // Check completion.
        $completed = $DB->record_exists('survey_answers', ['survey' => $instance->id, 'userid' => $USER->id]);
        if ($completed) {
            $html .= '<p class="text-success"><i class="fa fa-check-circle"></i> ' . get_string('surveycompleted', 'survey') . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if (!$completed) {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
            $html .= '<i class="fa fa-pencil-square-o"></i> ' . get_string('clicktocontinuecheck', 'survey') . '</a>';
        } else {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-secondary btn-lg">';
            $html .= '<i class="fa fa-eye"></i> ' . get_string('view') . '</a>';
        }
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render WORKSHOP module
     */
    protected static function render_workshop($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');

        $html = '<div class="workshop-content-container">';

        // Create workshop object.
        $workshop = new \workshop($instance, $cm, $course);

        $phases = [
            \workshop::PHASE_SETUP => get_string('phasesetup', 'workshop'),
            \workshop::PHASE_SUBMISSION => get_string('phasesubmission', 'workshop'),
            \workshop::PHASE_ASSESSMENT => get_string('phaseassessment', 'workshop'),
            \workshop::PHASE_EVALUATION => get_string('phaseevaluation', 'workshop'),
            \workshop::PHASE_CLOSED => get_string('phaseclosed', 'workshop'),
        ];

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-tasks"></i> <strong>' . get_string('currentphase', 'workshop') . ':</strong> ';
        $html .= isset($phases[$workshop->phase]) ? $phases[$workshop->phase] : get_string('unknown', 'workshop');
        $html .= '</p>';

        // Deadlines.
        if ($workshop->submissionstart) {
            $html .= '<p><i class="fa fa-calendar"></i> ' . get_string('submissionstart', 'workshop') . ': ' . userdate($workshop->submissionstart) . '</p>';
        }
        if ($workshop->submissionend) {
            $html .= '<p><i class="fa fa-calendar-times-o"></i> ' . get_string('submissionend', 'workshop') . ': ' . userdate($workshop->submissionend) . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-users"></i> ' . get_string('viewworkshop', 'workshop') . '</a>';
        $html .= '</div>';

        $html .= '</div>';
        return $html;
    }

    /**
     * Render IMSCP module
     */
    protected static function render_imscp($cm, $cminfo, $instance, $course, $context): string {
        $html = '<div class="imscp-content-container text-center p-4">';
        $html .= '<div class="mb-3"><i class="fa fa-archive fa-4x text-primary"></i></div>';
        $html .= '<p class="lead">' . get_string('modulename', 'imscp') . '</p>';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-play-circle"></i> ' . get_string('view') . '</a>';
        $html .= '</div>';
        return $html;
    }

    /**
     * Render LABEL module - labels have no view, just show intro
     */
    protected static function render_label($cm, $cminfo, $instance, $course, $context): string {
        // Labels are inline content, already shown in intro.
        return '';
    }

    /**
     * Default renderer for unsupported modules
     */
    protected static function render_default($cm, $cminfo, $instance, $modname): string {
        $html = '<div class="default-content-container text-center p-4">';
        $html .= '<div class="mb-3"><i class="fa fa-puzzle-piece fa-4x text-muted"></i></div>';
        $html .= '<p class="lead text-muted">' . get_string('modulename', $modname) . '</p>';
        $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary btn-lg">';
        $html .= '<i class="fa fa-external-link"></i> ' . get_string('view') . ' ' . get_string('modulename', $modname) . '</a>';
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
            'content' => new external_value(PARAM_RAW, 'HTML content for the activity'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'modname' => new external_value(PARAM_TEXT, 'Module name'),
            'name' => new external_value(PARAM_TEXT, 'Activity name'),
            'url' => new external_value(PARAM_URL, 'Activity URL'),
        ]);
    }
}
