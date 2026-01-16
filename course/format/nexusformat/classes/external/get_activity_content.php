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
        global $DB, $PAGE, $OUTPUT, $CFG, $USER;

        $params = self::validate_parameters(self::execute_parameters(), ['cmid' => $cmid]);
        $cmid = $params['cmid'];

        $cm = get_coursemodule_from_id('', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $context = context_module::instance($cmid);
        self::validate_context($context);

        $modinfo = get_fast_modinfo($course);
        $cminfo = $modinfo->get_cm($cmid);

        if (!$cminfo->uservisible) {
            throw new moodle_exception('nopermissions', 'error', '', 'view this activity');
        }

        $modname = $cm->modname;
        $instance = $DB->get_record($modname, ['id' => $cm->instance], '*', MUST_EXIST);

        // Setup PAGE for renderers.
        $PAGE->set_context($context);
        $PAGE->set_course($course);
        $PAGE->set_cm($cminfo);
        $PAGE->set_url('/mod/' . $modname . '/view.php', ['id' => $cm->id]);

        // Render content.
        $content = self::render_content($modname, $cm, $cminfo, $instance, $course, $context);

        return [
            'content' => $content,
            'cmid' => $cmid,
            'modname' => $modname,
            'name' => $cminfo->get_formatted_name(),
            'url' => $cminfo->url ? $cminfo->url->out(false) : '',
        ];
    }

    /**
     * Render activity content.
     */
    protected static function render_content($modname, $cm, $cminfo, $instance, $course, $context): string {
        global $OUTPUT;

        $html = '<div class="nexus-activity-wrapper">';

        // Header.
        $html .= '<div class="nexus-activity-header mb-3">';
        $html .= '<h4><img src="' . $cminfo->get_icon_url() . '" class="activityicon me-2" /> ';
        $html .= format_string($cminfo->name) . '</h4>';

        if (!empty($instance->intro)) {
            $html .= '<div class="activity-intro">' . format_module_intro($modname, $instance, $cm->id) . '</div>';
        }
        $html .= '</div>';

        // Content.
        $html .= '<div class="nexus-activity-body">';
        $html .= self::get_module_content($modname, $cm, $cminfo, $instance, $course, $context);
        $html .= '</div></div>';

        return $html;
    }

    /**
     * Get module-specific content using native renderers.
     */
    protected static function get_module_content($modname, $cm, $cminfo, $instance, $course, $context): string {
        global $CFG;

        $method = 'render_mod_' . $modname;
        if (method_exists(self::class, $method)) {
            return self::$method($cm, $cminfo, $instance, $course, $context);
        }

        // Fallback for unsupported modules.
        return self::render_mod_default($cm, $cminfo, $modname);
    }

    // =========================================================================
    // PAGE MODULE - Using same logic as mod/page/view.php
    // =========================================================================
    protected static function render_mod_page($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/page/lib.php');

        $content = file_rewrite_pluginfile_urls(
            $instance->content, 'pluginfile.php', $context->id, 'mod_page', 'content', $instance->revision
        );

        $formatoptions = new \stdClass();
        $formatoptions->noclean = true;
        $formatoptions->overflowdiv = true;
        $formatoptions->context = $context;

        $html = '<div class="page-content">' . format_text($content, $instance->contentformat, $formatoptions) . '</div>';

        // Last modified.
        $options = empty($instance->displayoptions) ? [] : (array) @unserialize($instance->displayoptions);
        if (!isset($options['printlastmodified']) || !empty($options['printlastmodified'])) {
            $html .= '<div class="modified text-muted small mt-2">' . get_string('lastmodified') . ': ' . userdate($instance->timemodified) . '</div>';
        }

        return $html;
    }

    // =========================================================================
    // RESOURCE MODULE - Using mod/resource/view.php + resourcelib
    // =========================================================================
    protected static function render_mod_resource($cm, $cminfo, $instance, $course, $context): string {
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
        $displaytype = resource_get_final_display_type($instance);

        $path = '/' . $context->id . '/mod_resource/content/' . $instance->revision . $file->get_filepath() . $file->get_filename();
        $fullurl = \moodle_url::make_file_url('/pluginfile.php', $path, false);

        $html = '';
        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
                $mimetype = $file->get_mimetype();
                if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
                    $html = '<div class="text-center"><img src="' . $fullurl . '" class="img-fluid" alt="' . s($file->get_filename()) . '" /></div>';
                } else if ($mimetype === 'application/pdf') {
                    $html = '<embed src="' . $fullurl . '" type="application/pdf" width="100%" height="600px" />';
                } else if (file_mimetype_in_typegroup($mimetype, ['web_video', 'web_audio'])) {
                    $mediamanager = \core_media_manager::instance();
                    $html = $mediamanager->embed_url(new \moodle_url($fullurl), $instance->name, 0, 0, [
                        \core_media_manager::OPTION_TRUSTED => true,
                        \core_media_manager::OPTION_BLOCK => true
                    ]);
                } else {
                    $html = '<iframe src="' . $fullurl . '" width="100%" height="600px" style="border:none;"></iframe>';
                }
                break;

            case RESOURCELIB_DISPLAY_FRAME:
                $html = '<iframe src="' . $fullurl . '" width="100%" height="600px" style="border:none;"></iframe>';
                break;

            default:
                $html = '<div class="text-center py-4">';
                $html .= '<p><i class="fa fa-file fa-4x text-primary"></i></p>';
                $html .= '<p><strong>' . s($file->get_filename()) . '</strong></p>';
                $html .= '<p class="text-muted">' . display_size($file->get_filesize()) . '</p>';
                $html .= '<a href="' . $fullurl . '" class="btn btn-primary" target="_blank">';
                $html .= '<i class="fa fa-download"></i> ' . get_string('download') . '</a></div>';
        }

        return $html;
    }

    // =========================================================================
    // URL MODULE - Using mod/url/view.php + url locallib
    // =========================================================================
    protected static function render_mod_url($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/url/lib.php');
        require_once($CFG->dirroot . '/mod/url/locallib.php');
        require_once($CFG->libdir . '/resourcelib.php');

        $fullurl = url_get_full_url($instance, $cm, $course);
        $displaytype = url_get_final_display_type($instance);

        $html = '';
        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
            case RESOURCELIB_DISPLAY_FRAME:
                $html = '<iframe src="' . s($fullurl) . '" width="100%" height="600px" style="border:none;"></iframe>';
                break;

            default:
                $html = '<div class="text-center py-4">';
                $html .= '<p><i class="fa fa-external-link fa-4x text-primary"></i></p>';
                $html .= '<a href="' . s($fullurl) . '" class="btn btn-primary btn-lg" target="_blank" rel="noopener">';
                $html .= '<i class="fa fa-external-link"></i> ' . get_string('clicktoopen', 'url') . '</a></div>';
        }

        return $html;
    }

    // =========================================================================
    // FOLDER MODULE - Using native mod_folder renderer
    // =========================================================================
    protected static function render_mod_folder($cm, $cminfo, $instance, $course, $context): string {
        global $PAGE, $CFG;
        require_once($CFG->dirroot . '/mod/folder/lib.php');

        $renderer = $PAGE->get_renderer('mod_folder');
        return $renderer->display_folder($instance);
    }

    // =========================================================================
    // BOOK MODULE - Using mod/book/view.php + book locallib
    // =========================================================================
    protected static function render_mod_book($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/book/lib.php');
        require_once($CFG->dirroot . '/mod/book/locallib.php');

        $chapters = book_preload_chapters($instance);
        if (empty($chapters)) {
            return '<div class="alert alert-info">' . get_string('nocontent', 'mod_book') . '</div>';
        }

        $viewhidden = has_capability('mod/book:viewhiddenchapters', $context);
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

        $html = '<div class="row"><div class="col-md-3">';

        // TOC.
        $html .= '<div class="card"><div class="card-header">' . get_string('toc', 'mod_book') . '</div>';
        $html .= '<ul class="list-group list-group-flush">';
        foreach ($chapters as $ch) {
            if ($ch->hidden && !$viewhidden) continue;
            $active = ($ch->id == $firstchapter->id) ? 'active' : '';
            $indent = $ch->subchapter ? 'ps-4' : '';
            $url = new \moodle_url('/mod/book/view.php', ['id' => $cm->id, 'chapterid' => $ch->id]);
            $html .= '<a href="' . $url . '" class="list-group-item list-group-item-action ' . $active . ' ' . $indent . '">';
            $html .= format_string($ch->title) . '</a>';
        }
        $html .= '</ul></div></div>';

        // Chapter content.
        $html .= '<div class="col-md-9">';
        if (!$instance->customtitles) {
            $html .= '<h5>' . format_string($firstchapter->title) . '</h5>';
        }
        $content = file_rewrite_pluginfile_urls($firstchapter->content, 'pluginfile.php', $context->id, 'mod_book', 'chapter', $firstchapter->id);
        $html .= format_text($content, $firstchapter->contentformat, ['noclean' => true, 'overflowdiv' => true, 'context' => $context]);
        $html .= '</div></div>';

        return $html;
    }

    // =========================================================================
    // QUIZ MODULE - Using native mod_quiz renderer like view.php
    // =========================================================================
    protected static function render_mod_quiz($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->libdir . '/gradelib.php');

        // Get native quiz renderer.
        $output = $PAGE->get_renderer('mod_quiz');

        // Cache capabilities like view.php.
        $canattempt = has_capability('mod/quiz:attempt', $context);
        $canreviewmine = has_capability('mod/quiz:reviewmyattempts', $context);
        $canpreview = has_capability('mod/quiz:preview', $context);

        // Create quiz_settings and access_manager like view.php lines 43-64.
        $quizobj = \mod_quiz\quiz_settings::create_for_cmid($cm->id, $USER->id);
        $quiz = $quizobj->get_quiz();
        $timenow = time();
        $accessmanager = new \mod_quiz\access_manager($quizobj, $timenow,
            has_capability('mod/quiz:ignoretimelimits', $context, null, false));

        // Create view object like view.php line 75.
        $viewobj = new \mod_quiz\output\view_page();
        $viewobj->accessmanager = $accessmanager;
        $viewobj->canreviewmine = $canreviewmine || $canpreview;

        // Get user's attempts like view.php lines 80-99.
        $attempts = quiz_get_user_attempts($quiz->id, $USER->id, 'finished', true);
        $lastfinishedattempt = end($attempts);
        $unfinished = false;
        $unfinishedattemptid = null;

        if ($unfinishedattempt = quiz_get_user_attempt_unfinished($quiz->id, $USER->id)) {
            $attempts[] = $unfinishedattempt;
            $quizobj->create_attempt_object($unfinishedattempt)->handle_if_time_expired($timenow, false);
            $unfinished = $unfinishedattempt->state == \mod_quiz\quiz_attempt::IN_PROGRESS ||
                $unfinishedattempt->state == \mod_quiz\quiz_attempt::OVERDUE;
            if (!$unfinished) {
                $lastfinishedattempt = $unfinishedattempt;
            }
            $unfinishedattemptid = $unfinishedattempt->id;
            $unfinishedattempt = null;
        }
        $numattempts = count($attempts);

        // Compute grade item totals like view.php lines 101-103.
        $gradeitemmarks = $quizobj->get_grade_calculator()->compute_grade_item_totals_for_attempts(
            array_column($attempts, 'uniqueid'));

        // Build view object properties like view.php lines 104-115.
        $viewobj->attempts = $attempts;
        $viewobj->attemptobjs = [];
        foreach ($attempts as $attempt) {
            $attemptobj = new \mod_quiz\quiz_attempt($attempt, $quiz, $cm, $course, false);
            $attemptobj->set_grade_item_totals($gradeitemmarks[$attempt->uniqueid]);
            $viewobj->attemptobjs[] = $attemptobj;
        }
        $viewobj->attemptslist = new \mod_quiz\output\list_of_attempts($timenow);
        foreach (array_reverse($viewobj->attemptobjs) as $attemptobj) {
            $viewobj->attemptslist->add_attempt($attemptobj);
        }

        // Get grade information like view.php lines 117-159.
        if (!$canpreview) {
            $mygrade = quiz_get_best_grade($quiz, $USER->id);
        } else if ($lastfinishedattempt) {
            $mygrade = quiz_rescale_grade($lastfinishedattempt->sumgrades, $quiz, false);
        } else {
            $mygrade = null;
        }

        $mygradeoverridden = false;
        $gradebookfeedback = '';
        $gradeitem = \grade_item::fetch([
            'itemtype' => 'mod',
            'itemmodule' => 'quiz',
            'iteminstance' => $quiz->id,
            'itemnumber' => 0,
            'courseid' => $course->id,
        ]);

        if (!$canpreview && $gradeitem) {
            $grade = $gradeitem->get_grade($USER->id, false);
            $mygrade = $grade->finalgrade;
            if ($grade->overridden) {
                $mygradeoverridden = true;
            }
            if (!empty($grade->feedback)) {
                $gradebookfeedback = $grade->feedback;
            }
        }

        // Set remaining view object properties like view.php lines 172-219.
        if ($attempts) {
            list($someoptions, $alloptions) = quiz_get_combined_reviewoptions($quiz, $attempts);
            $viewobj->attemptcolumn = $quiz->attempts != 1;
            $viewobj->gradecolumn = $someoptions->marks >= \question_display_options::MARK_AND_MAX && quiz_has_grades($quiz);
            $viewobj->markcolumn = $viewobj->gradecolumn && ($quiz->grade != $quiz->sumgrades);
            $viewobj->overallstats = $lastfinishedattempt && $alloptions->marks >= \question_display_options::MARK_AND_MAX;
            $viewobj->feedbackcolumn = quiz_has_feedback($quiz) && $alloptions->overallfeedback;
        }

        $viewobj->timenow = $timenow;
        $viewobj->numattempts = $numattempts;
        $viewobj->mygrade = $mygrade;
        $viewobj->moreattempts = $unfinished || !$accessmanager->is_finished($numattempts, $lastfinishedattempt);
        $viewobj->mygradeoverridden = $mygradeoverridden;
        $viewobj->gradebookfeedback = $gradebookfeedback;
        $viewobj->lastfinishedattempt = $lastfinishedattempt;
        $viewobj->canedit = has_capability('mod/quiz:manage', $context);
        $viewobj->editurl = new \moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id]);
        $viewobj->backtocourseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
        $viewobj->startattempturl = $quizobj->start_attempt_url();

        if ($accessmanager->is_preflight_check_required($unfinishedattemptid)) {
            $viewobj->preflightcheckform = $accessmanager->get_preflight_check_form(
                $viewobj->startattempturl, $unfinishedattemptid);
        }
        $viewobj->popuprequired = $accessmanager->attempt_must_be_in_popup();
        $viewobj->popupoptions = $accessmanager->get_popup_options();

        // Info messages like view.php lines 207-219.
        $viewobj->infomessages = $viewobj->accessmanager->describe_rules();
        if ($quiz->attempts != 1) {
            $viewobj->infomessages[] = get_string('gradingmethod', 'quiz', quiz_get_grading_option_name($quiz->grademethod));
        }
        if ($gradeitem && grade_floats_different($gradeitem->gradepass, 0)) {
            $a = new \stdClass();
            $a->grade = quiz_format_grade($quiz, $gradeitem->gradepass);
            $a->maxgrade = quiz_format_grade($quiz, $quiz->grade);
            $viewobj->infomessages[] = get_string('gradetopassoutof', 'quiz', $a);
        }

        // Determine button text like view.php lines 222-264.
        $viewobj->quizhasquestions = $quizobj->has_questions();
        $viewobj->preventmessages = [];
        if (!$viewobj->quizhasquestions) {
            $viewobj->buttontext = '';
        } else {
            if ($unfinished) {
                $viewobj->buttontext = $canpreview ? get_string('continuepreview', 'quiz') : ($canattempt ? get_string('continueattemptquiz', 'quiz') : '');
            } else {
                if ($canpreview) {
                    $viewobj->buttontext = get_string('previewquizstart', 'quiz');
                } else if ($canattempt) {
                    $viewobj->preventmessages = $viewobj->accessmanager->prevent_new_attempt($viewobj->numattempts, $viewobj->lastfinishedattempt);
                    if ($viewobj->preventmessages) {
                        $viewobj->buttontext = '';
                    } else if ($viewobj->numattempts == 0) {
                        $viewobj->buttontext = get_string('attemptquiz', 'quiz');
                    } else {
                        $viewobj->buttontext = get_string('reattemptquiz', 'quiz');
                    }
                }
            }

            if ($canpreview) {
                $viewobj->preventmessages = $viewobj->accessmanager->prevent_access();
            } else if (!empty($viewobj->buttontext)) {
                if (!$viewobj->moreattempts) {
                    $viewobj->buttontext = '';
                } else if ($canattempt) {
                    $viewobj->preventmessages = $viewobj->accessmanager->prevent_access();
                    if ($viewobj->preventmessages) {
                        $viewobj->buttontext = '';
                    }
                }
            }
        }
        $viewobj->showbacktocourse = ($viewobj->buttontext === '' && course_get_format($course)->has_view_page());

        // Use native renderer methods to generate HTML (without header/footer).
        $html = '';
        $html .= $output->view_page_tertiary_nav($viewobj);
        $html .= $output->view_information($quiz, $cm, $context, $viewobj->infomessages);
        $html .= $output->view_result_info($quiz, $context, $cm, $viewobj);
        $html .= $output->render($viewobj->attemptslist);
        $html .= $output->box($output->view_page_buttons($viewobj), 'quizattempt');

        return $html;
    }

    // =========================================================================
    // ASSIGN MODULE - Using native assign renderables like view.php
    // Includes submission form directly to avoid extra navigation
    // =========================================================================
    protected static function render_mod_assign($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER, $PAGE, $DB, $OUTPUT;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/submission_form.php');

        // Create assign object exactly like view.php line 38.
        $assign = new \assign($context, $cm, $course);

        // Apply overrides like view.php line 51.
        $assign->update_effective_access($USER->id);

        // Get native assign renderer.
        $renderer = $assign->get_renderer();
        $effectiveinstance = $assign->get_instance($USER->id);

        $html = '';

        // Render header with attachments.
        $postfix = '';
        if ($assign->has_visible_attachments() && (!$effectiveinstance->submissionattachments)) {
            $postfix = $assign->render_area_files('mod_assign', ASSIGN_INTROATTACHMENT_FILEAREA, 0);
        }

        $header = new \assign_header(
            $effectiveinstance,
            $context,
            $assign->show_intro(),
            $cm->id,
            '', '', $postfix
        );
        $html .= $renderer->render($header);

        // Display plugin specific headers.
        $plugins = array_merge($assign->get_submission_plugins(), $assign->get_feedback_plugins());
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled() && $plugin->is_visible()) {
                $html .= $renderer->render(new \assign_plugin_header($plugin));
            }
        }

        // Teacher view: grading summary.
        if ($assign->can_view_grades()) {
            $actionbuttons = new \mod_assign\output\actionmenu($cm->id);
            $html .= $renderer->submission_actionmenu($actionbuttons);
            $summary = $assign->get_assign_grading_summary_renderable();
            $html .= $renderer->render($summary);
        }

        // Student view.
        if ($assign->can_view_submission($USER->id)) {
            $submission = $assign->get_user_submission($USER->id, false);
            $teamsubmission = null;
            if ($effectiveinstance->teamsubmission) {
                $teamsubmission = $assign->get_group_submission($USER->id, 0, false);
            }

            // Check if user can edit submission - show form directly instead of redirect.
            $canedit = $assign->is_any_submission_plugin_enabled() && $assign->can_edit_submission($USER->id);
            $submissionsopen = $assign->submissions_open($USER->id);
            $needssubmission = !$submission || $submission->status == ASSIGN_SUBMISSION_STATUS_NEW ||
                               $submission->status == ASSIGN_SUBMISSION_STATUS_REOPENED;

            if ($canedit && $submissionsopen && $needssubmission) {
                // Show submission form directly like view_edit_submission_page().
                $user = $DB->get_record('user', ['id' => $USER->id], '*', MUST_EXIST);

                // Check for time limit.
                $timelimitenabled = get_config('assign', 'enabletimelimit');
                $timelimit = $effectiveinstance->timelimit;
                $submission = $assign->get_user_submission($USER->id, true);

                if ($timelimitenabled && $timelimit && empty($submission->timestarted)) {
                    // Show begin assignment button with confirmation.
                    $html .= '<div class="alert alert-warning">';
                    $html .= '<p><i class="fa fa-clock-o"></i> ' . get_string('timelimit', 'assign') . ': ' . format_time($timelimit) . '</p>';
                    $html .= '<p>' . get_string('confirmstart', 'assign') . '</p>';
                    $html .= '</div>';
                    $urlparams = ['id' => $cm->id, 'action' => 'editsubmission', 'begin' => 1];
                    $beginurl = new \moodle_url('/mod/assign/view.php', $urlparams);
                    $html .= '<div class="text-center">';
                    $html .= '<a href="' . $beginurl . '" class="btn btn-primary btn-lg">';
                    $html .= '<i class="fa fa-play-circle"></i> ' . get_string('beginassignment', 'assign') . '</a>';
                    $html .= '</div>';
                } else {
                    // Render submission form directly.
                    $data = new \stdClass();
                    $data->userid = $USER->id;
                    $mform = new \mod_assign_submission_form(
                        new \moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'savesubmission']),
                        [$assign, $data]
                    );

                    // Show time limit panel if applicable.
                    if ($timelimitenabled && $timelimit && !empty($submission->timestarted)) {
                        $html .= $assign->get_timelimit_panel($submission);
                    }

                    // Render the form.
                    $html .= $renderer->render(new \assign_form('editsubmissionform', $mform));
                }
            } else {
                // Show submission status and feedback (normal view).
                $html .= $assign->view_submission_action_bar($effectiveinstance, $USER);
                $html .= $assign->view_student_summary($USER, true);
            }
        }

        return $html;
    }

    // =========================================================================
    // FORUM MODULE - Using native forum vaults and renderers like view.php
    // =========================================================================
    protected static function render_mod_forum($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        // Use native forum factories like view.php lines 29-35.
        $managerfactory = \mod_forum\local\container::get_manager_factory();
        $vaultfactory = \mod_forum\local\container::get_vault_factory();
        $rendererfactory = \mod_forum\local\container::get_renderer_factory();
        $forumvault = $vaultfactory->get_forum_vault();
        $discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();

        // Get forum entity.
        $forum = $forumvault->get_from_course_module_id($cm->id);
        $capabilitymanager = $managerfactory->get_capability_manager($forum);

        // Get display mode.
        $displaymode = get_user_preferences('forum_displaymode', $CFG->forum_displaymode);
        if (get_user_preferences('forum_useexperimentalui', false)) {
            if ($displaymode == FORUM_MODE_NESTED) {
                $displaymode = FORUM_MODE_NESTED_V2;
            }
        }

        // Get current group.
        $groupid = groups_get_activity_group($cm, true) ?: null;

        // Get sort order.
        $sortorder = get_user_preferences('forum_discussionlistsortorder', $discussionlistvault::SORTORDER_LASTPOST_DESC);

        $html = '';

        // Render action bar like view.php line 174.
        $html .= forum_activity_actionbar($forum, $groupid, $course, '');

        // Render discussions using native renderer like view.php lines 243-259.
        switch ($forum->get_type()) {
            case 'single':
                $discussionvault = $vaultfactory->get_discussion_vault();
                $postvault = $vaultfactory->get_post_vault();
                $discussion = $discussionvault->get_last_discussion_in_forum($forum);
                $discussioncount = $discussionvault->get_count_discussions_in_forum($forum);
                $hasmultiplediscussions = $discussioncount > 1;
                $discussionsrenderer = $rendererfactory->get_single_discussion_list_renderer($forum, $discussion,
                    $hasmultiplediscussions, $displaymode);
                $post = $postvault->get_from_id($discussion->get_first_post_id());
                $orderpostsby = $displaymode == FORUM_MODE_FLATNEWEST ? 'created DESC' : 'created ASC';
                $replies = $postvault->get_replies_to_post(
                    $USER,
                    $post,
                    $capabilitymanager->can_view_any_private_reply($USER),
                    $orderpostsby
                );
                $html .= $discussionsrenderer->render($USER, $post, $replies);
                break;

            case 'blog':
                $discussionsrenderer = $rendererfactory->get_blog_discussion_list_renderer($forum);
                $html .= $discussionsrenderer->render($USER, $cm, $groupid, $discussionlistvault::SORTORDER_CREATED_DESC,
                    0, 10, null, false);
                break;

            default:
                $discussionsrenderer = $rendererfactory->get_discussion_list_renderer($forum);
                $html .= $discussionsrenderer->render($USER, $cm, $groupid, $sortorder, 0, 10, $displaymode, false);
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // LESSON MODULE - Using lesson class
    // =========================================================================
    protected static function render_mod_lesson($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/lesson/lib.php');
        require_once($CFG->dirroot . '/mod/lesson/locallib.php');

        $lesson = new \lesson($instance, $cm, $course);
        $lesson->update_effective_access($USER->id);
        $canmanage = $lesson->can_manage();

        $html = '<div class="card mb-3"><div class="card-body">';
        $pagecount = $DB->count_records('lesson_pages', ['lessonid' => $lesson->id]);
        $html .= '<p><i class="fa fa-file-text-o"></i> ' . get_string('pages', 'lesson') . ': ' . $pagecount . '</p>';

        if ($lesson->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> ' . get_string('timelimit', 'lesson') . ': ' . format_time($lesson->timelimit) . '</p>';
        }

        if ($lesson->deadline > 0) {
            $class = $lesson->deadline < time() ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar-times-o"></i> ' . get_string('deadline', 'lesson') . ': <span class="' . $class . '">' . userdate($lesson->deadline) . '</span></p>';
        }

        if ($lesson->maxattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('maximumnumberofattempts', 'lesson') . ': ' . $lesson->maxattempts . '</p>';
        }
        $html .= '</div></div>';

        // Restrictions.
        $isopen = !$lesson->get_time_restriction_status();
        $hasdep = $lesson->get_dependencies_restriction_status();

        if (!$isopen && !$canmanage) {
            $html .= '<div class="alert alert-warning">' . get_string('lessonnotready2', 'lesson') . '</div>';
        }
        if ($hasdep && !$canmanage) {
            $html .= '<div class="alert alert-warning">' . get_string('completethefollowingconditions', 'lesson', '') . '</div>';
        }

        // User progress.
        if (!$canmanage) {
            $retries = $lesson->count_user_retries($USER->id);
            $attempts = $DB->get_records('lesson_grades', ['lessonid' => $lesson->id, 'userid' => $USER->id], 'completed DESC');

            if ($attempts) {
                $best = 0;
                $html .= '<div class="card mb-3"><div class="card-header">' . get_string('attempts', 'lesson') . '</div>';
                $html .= '<table class="table mb-0"><thead><tr><th>#</th><th>' . get_string('grade', 'grades') . '</th></tr></thead><tbody>';
                $n = count($attempts);
                foreach ($attempts as $a) {
                    $best = max($best, $a->grade);
                    $html .= '<tr><td>' . $n-- . '</td><td>' . format_float($a->grade, 1) . '%</td></tr>';
                }
                $html .= '</tbody></table>';
                $html .= '<div class="card-footer">' . get_string('bestgrade', 'lesson') . ': ' . format_float($best, 1) . '%</div></div>';
            }

            $lastpage = $lesson->get_last_page_seen($retries);
            $hasincomplete = $lastpage !== false && $lastpage != LESSON_EOL;
        }

        // Button.
        $html .= '<div class="text-center">';
        $canstart = ($isopen && !$hasdep) || $canmanage;

        if (!$canmanage && isset($retries) && !$lesson->retake && $retries > 0) {
            $canstart = false;
            $html .= '<div class="alert alert-info">' . get_string('noretake', 'lesson') . '</div>';
        }

        if ($canstart) {
            $btntext = $canmanage ? get_string('preview', 'lesson') :
                (isset($hasincomplete) && $hasincomplete ? get_string('continuelesson', 'lesson') :
                    (isset($attempts) && $attempts ? get_string('retakelesson', 'lesson') : get_string('startlesson', 'lesson')));
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . $btntext . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // CHOICE MODULE - Using choice lib functions
    // =========================================================================
    protected static function render_mod_choice($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/choice/lib.php');

        list($available, $warnings) = choice_get_availability_status($instance);

        $options = $DB->get_records('choice_options', ['choiceid' => $instance->id], 'id');
        $current = choice_get_my_response($instance);
        $hasanswered = !empty($current);

        $html = '';

        if (!$available && $warnings) {
            foreach ($warnings as $msg) {
                $html .= '<div class="alert alert-warning">' . $msg . '</div>';
            }
        }

        if ($hasanswered) {
            $html .= '<div class="alert alert-success"><strong>' . get_string('yourselection', 'choice') . ':</strong><br>';
            foreach ($current as $c) {
                $html .= '&bull; ' . format_string(choice_get_option_text($instance, $c->optionid)) . '<br>';
            }
            $html .= '</div>';
        }

        // Voting form.
        $canchoose = has_capability('mod/choice:choose', $context) && is_enrolled($context);
        $canupdate = $instance->allowupdate && $hasanswered && $available;
        $canvote = $canchoose && $available && (!$hasanswered || $canupdate);

        if ($canvote && $options) {
            $html .= '<form method="post" action="' . $cminfo->url . '">';
            $html .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            $html .= '<input type="hidden" name="action" value="makechoice">';
            $html .= '<div class="list-group mb-3">';

            $type = $instance->allowmultiple ? 'checkbox' : 'radio';
            $name = $instance->allowmultiple ? 'answer[]' : 'answer';

            foreach ($options as $opt) {
                $checked = '';
                foreach ($current as $c) {
                    if ($c->optionid == $opt->id) { $checked = 'checked'; break; }
                }
                $html .= '<label class="list-group-item"><input type="' . $type . '" name="' . $name . '" value="' . $opt->id . '" ' . $checked . ' class="me-2"> ' . format_string($opt->text) . '</label>';
            }
            $html .= '</div>';
            $html .= '<div class="text-center"><button type="submit" class="btn btn-primary btn-lg">' . ($hasanswered ? get_string('updatechoice', 'choice') : get_string('savemychoice', 'choice')) . '</button></div>';
            $html .= '</form>';
        }

        // Results.
        if (choice_can_view_results($instance, $current, $available)) {
            $counts = $DB->get_records_sql_menu("SELECT optionid, COUNT(*) FROM {choice_answers} WHERE choiceid = ? GROUP BY optionid", [$instance->id]);
            $total = array_sum($counts);

            $html .= '<h5 class="mt-4">' . get_string('responses', 'choice') . '</h5>';
            foreach ($options as $opt) {
                $cnt = isset($counts[$opt->id]) ? $counts[$opt->id] : 0;
                $pct = $total > 0 ? round($cnt / $total * 100) : 0;
                $html .= '<div class="mb-2"><div class="d-flex justify-content-between"><span>' . format_string($opt->text) . '</span><span>' . $cnt . ' (' . $pct . '%)</span></div>';
                $html .= '<div class="progress" style="height:20px;"><div class="progress-bar" style="width:' . $pct . '%"></div></div></div>';
            }
        }

        return $html;
    }

    // =========================================================================
    // FEEDBACK MODULE - Using mod_feedback_completion class
    // =========================================================================
    protected static function render_mod_feedback($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/feedback/lib.php');

        $completion = new \mod_feedback_completion($instance, $cm, $course->id);
        $canedititems = has_capability('mod/feedback:edititems', $context);
        $canviewreports = has_capability('mod/feedback:viewreports', $context);

        $isopen = $completion->is_open();
        $cancomplete = $completion->can_complete();
        $cansubmit = $completion->can_submit();

        $html = '';

        if ($canedititems || $canviewreports) {
            $items = $DB->count_records('feedback_item', ['feedback' => $instance->id, 'hasvalue' => 1]);
            $completed = $DB->count_records('feedback_completed', ['feedback' => $instance->id]);

            $html .= '<div class="card mb-3"><div class="card-header">' . get_string('overview', 'feedback') . '</div>';
            $html .= '<div class="card-body">';
            $html .= '<p><i class="fa fa-list"></i> ' . get_string('questions', 'feedback') . ': ' . $items . '</p>';
            $html .= '<p><i class="fa fa-check-circle"></i> ' . get_string('completed_feedbacks', 'feedback') . ': ' . $completed . '</p>';
            if ($instance->anonymous == FEEDBACK_ANONYMOUS_YES) {
                $html .= '<p><i class="fa fa-user-secret"></i> ' . get_string('anonymous', 'feedback') . '</p>';
            }
            $html .= '</div></div>';

            $html .= '<div class="text-center">';
            if ($completed > 0) {
                $html .= '<a href="' . (new \moodle_url('/mod/feedback/analysis.php', ['id' => $cm->id])) . '" class="btn btn-primary me-2"><i class="fa fa-bar-chart"></i> ' . get_string('analysis', 'feedback') . '</a>';
            }
            if ($canedititems) {
                $html .= '<a href="' . (new \moodle_url('/mod/feedback/edit.php', ['id' => $cm->id])) . '" class="btn btn-secondary"><i class="fa fa-pencil"></i> ' . get_string('edit_items', 'feedback') . '</a>';
            }
            $html .= '</div>';
        } else {
            if (!$isopen) {
                $html .= '<div class="alert alert-warning">' . get_string('feedback_is_not_open', 'feedback') . '</div>';
            } else if (!$cansubmit && $cancomplete) {
                $html .= '<div class="alert alert-success">' . get_string('this_feedback_is_already_submitted', 'feedback') . '</div>';
            }

            if ($isopen && $cancomplete && $cansubmit) {
                $html .= '<div class="text-center"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-pencil-square-o"></i> ' . get_string('complete_the_form', 'feedback') . '</a></div>';
            }
        }

        return $html;
    }

    // =========================================================================
    // GLOSSARY MODULE
    // =========================================================================
    protected static function render_mod_glossary($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/glossary/lib.php');

        $total = $DB->count_records('glossary_entries', ['glossaryid' => $instance->id, 'approved' => 1]);
        $html = '<p class="text-muted"><i class="fa fa-book"></i> ' . $total . ' ' . get_string('entries', 'glossary') . '</p>';

        // Recent entries.
        $entries = $DB->get_records_sql("SELECT * FROM {glossary_entries} WHERE glossaryid = ? AND approved = 1 ORDER BY timecreated DESC LIMIT 5", [$instance->id]);

        if ($entries) {
            $html .= '<div class="accordion" id="glossaryAcc">';
            foreach ($entries as $e) {
                $eid = 'ge' . $e->id;
                $html .= '<div class="accordion-item"><h2 class="accordion-header"><button class="accordion-button collapsed py-2" data-bs-toggle="collapse" data-bs-target="#' . $eid . '">' . format_string($e->concept) . '</button></h2>';
                $html .= '<div id="' . $eid . '" class="accordion-collapse collapse"><div class="accordion-body">' . format_text($e->definition, $e->definitionformat, ['context' => $context]) . '</div></div></div>';
            }
            $html .= '</div>';
        }

        $html .= '<div class="text-center mt-3"><a href="' . $cminfo->url . '" class="btn btn-primary"><i class="fa fa-book"></i> ' . get_string('viewglossary', 'glossary') . '</a></div>';

        return $html;
    }

    // =========================================================================
    // WIKI MODULE
    // =========================================================================
    protected static function render_mod_wiki($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/wiki/lib.php');

        $subwiki = $DB->get_record('wiki_subwikis', ['wikiid' => $instance->id], '*', IGNORE_MULTIPLE);
        $html = '';

        if ($subwiki) {
            $page = $DB->get_record('wiki_pages', ['subwikiid' => $subwiki->id, 'title' => $instance->firstpagetitle]);
            if ($page) {
                $html .= '<h5>' . format_string($page->title) . '</h5>';
                $html .= '<div class="border rounded p-3 mb-3">' . format_text($page->cachedcontent, FORMAT_HTML, ['context' => $context]) . '</div>';
            }
        }

        $html .= '<div class="text-center"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-book"></i> ' . get_string('modulename', 'wiki') . '</a></div>';

        return $html;
    }

    // =========================================================================
    // DATA MODULE
    // =========================================================================
    protected static function render_mod_data($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/data/lib.php');

        $records = $DB->count_records('data_records', ['dataid' => $instance->id]);
        $fields = $DB->count_records('data_fields', ['dataid' => $instance->id]);

        $html = '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-database"></i> ' . get_string('numrecords', 'data', $records) . '</p>';
        $html .= '<p><i class="fa fa-columns"></i> ' . get_string('fields', 'data') . ': ' . $fields . '</p>';
        if ($instance->requiredentries > 0) {
            $html .= '<p><i class="fa fa-exclamation-circle"></i> ' . get_string('requiredentries', 'data') . ': ' . $instance->requiredentries . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if (has_capability('mod/data:writeentry', $context)) {
            $html .= '<a href="' . (new \moodle_url('/mod/data/edit.php', ['d' => $instance->id])) . '" class="btn btn-primary me-2"><i class="fa fa-plus"></i> ' . get_string('add', 'data') . '</a>';
        }
        $html .= '<a href="' . $cminfo->url . '" class="btn btn-outline-primary"><i class="fa fa-list"></i> ' . get_string('view') . '</a>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // SCORM MODULE
    // =========================================================================
    protected static function render_mod_scorm($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/scorm/lib.php');
        require_once($CFG->dirroot . '/mod/scorm/locallib.php');

        $attempts = scorm_get_attempt_count($USER->id, $instance);

        $html = '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('attempts', 'scorm') . ': ' . $attempts . '</p>';
        if ($instance->maxattempt > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> ' . get_string('maximumattempts', 'scorm') . ': ' . $instance->maxattempt . '</p>';
        }
        if ($attempts > 0) {
            $score = scorm_grade_user($instance, $USER->id);
            if ($score !== false) {
                $html .= '<p><i class="fa fa-star"></i> ' . get_string('grade', 'grades') . ': ' . format_float($score, 2) . '%</p>';
            }
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if ($instance->maxattempt > 0 && $attempts >= $instance->maxattempt) {
            $html .= '<div class="alert alert-warning">' . get_string('exceeded', 'scorm') . '</div>';
        } else {
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('enter', 'scorm') . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // LTI MODULE
    // =========================================================================
    protected static function render_mod_lti($cm, $cminfo, $instance, $course, $context): string {
        return '<div class="text-center py-4"><p><i class="fa fa-external-link fa-4x text-primary"></i></p><p>' . get_string('launchexternaltool', 'lti') . '</p><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-external-link"></i> ' . get_string('launch', 'lti') . '</a></div>';
    }

    // =========================================================================
    // BIGBLUEBUTTONBN MODULE
    // =========================================================================
    protected static function render_mod_bigbluebuttonbn($cm, $cminfo, $instance, $course, $context): string {
        $html = '<div class="card mb-3"><div class="card-body">';
        if (!empty($instance->openingtime) && $instance->openingtime > 0) {
            $html .= '<p><i class="fa fa-calendar"></i> ' . get_string('mod_form_field_openingtime', 'bigbluebuttonbn') . ': ' . userdate($instance->openingtime) . '</p>';
        }
        if (!empty($instance->closingtime) && $instance->closingtime > 0) {
            $html .= '<p><i class="fa fa-calendar-times-o"></i> ' . get_string('mod_form_field_closingtime', 'bigbluebuttonbn') . ': ' . userdate($instance->closingtime) . '</p>';
        }
        $html .= '</div></div>';
        $html .= '<div class="text-center"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-video-camera"></i> ' . get_string('view_room', 'bigbluebuttonbn') . '</a></div>';

        return $html;
    }

    // =========================================================================
    // CHAT MODULE
    // =========================================================================
    protected static function render_mod_chat($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/chat/lib.php');

        $html = '<div class="card mb-3"><div class="card-body">';
        if (!empty($instance->chattime) && $instance->schedule > 0) {
            $next = $instance->chattime;
            $now = time();
            while ($next < $now) {
                $next += ($instance->schedule == 2) ? 86400 : 604800;
            }
            $html .= '<p><i class="fa fa-clock-o"></i> ' . get_string('nextchattime', 'chat') . ': ' . userdate($next) . '</p>';
        }
        $html .= '</div></div>';

        $users = $DB->count_records('chat_users', ['chatid' => $instance->id]);
        if ($users > 0) {
            $html .= '<div class="alert alert-success"><i class="fa fa-users"></i> ' . $users . ' ' . get_string('currentusers', 'chat') . '</div>';
        }

        $html .= '<div class="text-center"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-comments"></i> ' . get_string('enterchat', 'chat') . '</a></div>';

        return $html;
    }

    // =========================================================================
    // SURVEY MODULE
    // =========================================================================
    protected static function render_mod_survey($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/survey/lib.php');

        $completed = $DB->record_exists('survey_answers', ['survey' => $instance->id, 'userid' => $USER->id]);

        $html = '<div class="card mb-3"><div class="card-body">';
        if ($completed) {
            $html .= '<p class="text-success"><i class="fa fa-check-circle"></i> ' . get_string('surveycompleted', 'survey') . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if (!$completed) {
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-pencil-square-o"></i> ' . get_string('clicktocontinuecheck', 'survey') . '</a>';
        } else {
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-secondary btn-lg"><i class="fa fa-eye"></i> ' . get_string('view') . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // WORKSHOP MODULE
    // =========================================================================
    protected static function render_mod_workshop($cm, $cminfo, $instance, $course, $context): string {
        global $CFG;
        require_once($CFG->dirroot . '/mod/workshop/locallib.php');

        $workshop = new \workshop($instance, $cm, $course);

        $phases = [
            \workshop::PHASE_SETUP => get_string('phasesetup', 'workshop'),
            \workshop::PHASE_SUBMISSION => get_string('phasesubmission', 'workshop'),
            \workshop::PHASE_ASSESSMENT => get_string('phaseassessment', 'workshop'),
            \workshop::PHASE_EVALUATION => get_string('phaseevaluation', 'workshop'),
            \workshop::PHASE_CLOSED => get_string('phaseclosed', 'workshop'),
        ];

        $html = '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-tasks"></i> ' . get_string('currentphase', 'workshop') . ': ' . ($phases[$workshop->phase] ?? '') . '</p>';
        if ($workshop->submissionstart) {
            $html .= '<p><i class="fa fa-calendar"></i> ' . get_string('submissionstart', 'workshop') . ': ' . userdate($workshop->submissionstart) . '</p>';
        }
        if ($workshop->submissionend) {
            $html .= '<p><i class="fa fa-calendar-times-o"></i> ' . get_string('submissionend', 'workshop') . ': ' . userdate($workshop->submissionend) . '</p>';
        }
        $html .= '</div></div>';

        $html .= '<div class="text-center"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-users"></i> ' . get_string('viewworkshop', 'workshop') . '</a></div>';

        return $html;
    }

    // =========================================================================
    // IMSCP MODULE
    // =========================================================================
    protected static function render_mod_imscp($cm, $cminfo, $instance, $course, $context): string {
        return '<div class="text-center py-4"><p><i class="fa fa-archive fa-4x text-primary"></i></p><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('view') . '</a></div>';
    }

    // =========================================================================
    // LABEL MODULE
    // =========================================================================
    protected static function render_mod_label($cm, $cminfo, $instance, $course, $context): string {
        return ''; // Label content is in intro.
    }

    // =========================================================================
    // H5PACTIVITY MODULE - Using manager class like view.php
    // =========================================================================
    protected static function render_mod_h5pactivity($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER;
        require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');

        $html = '';

        try {
            $manager = \mod_h5pactivity\local\manager::create_from_coursemodule($cm);
            $moduleinstance = $manager->get_instance();

            // Check if user can submit.
            $cansubmit = $manager->can_submit();
            $trackingEnabled = $manager->is_tracking_enabled();

            $html .= '<div class="card mb-3"><div class="card-body">';

            if (!$cansubmit && !isguestuser()) {
                $html .= '<div class="alert alert-info">' . get_string('previewmode', 'mod_h5pactivity') . '</div>';
            }

            if (!$trackingEnabled) {
                $html .= '<div class="alert alert-warning">' . get_string('trackingdisabled', 'mod_h5pactivity') . '</div>';
            }

            // Attempts info.
            if ($cansubmit && $trackingEnabled) {
                $attemptcount = $manager->count_attempts($USER->id);
                $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('myattempts', 'mod_h5pactivity') . ': ' . $attemptcount . '</p>';

                if ($moduleinstance->maxattempts > 0) {
                    $html .= '<p><i class="fa fa-info-circle"></i> ' . get_string('maxattempts', 'mod_h5pactivity') . ': ' . $moduleinstance->maxattempts . '</p>';
                }
            }

            $html .= '</div></div>';

            // Button to launch H5P.
            $html .= '<div class="text-center">';
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('startactivity', 'mod_h5pactivity') . '</a>';

            // View attempts link for teachers.
            if ($manager->can_view_all_attempts() && $trackingEnabled) {
                $attemptcount = $manager->count_attempts();
                $html .= ' <a href="' . (new \moodle_url('/mod/h5pactivity/report.php', ['id' => $cm->id])) . '" class="btn btn-outline-secondary">';
                $html .= '<i class="fa fa-bar-chart"></i> ' . get_string('viewattempts', 'mod_h5pactivity', $attemptcount) . '</a>';
            }
            $html .= '</div>';

        } catch (\Exception $e) {
            $html = '<div class="text-center py-4"><p><i class="fa fa-play-circle fa-4x text-primary"></i></p>';
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('view') . '</a></div>';
        }

        return $html;
    }

    // =========================================================================
    // SUBSECTION MODULE - Redirects to section like view.php
    // =========================================================================
    protected static function render_mod_subsection($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;

        try {
            $manager = \mod_subsection\manager::create_from_coursemodule($cm);
            $delegatesection = $manager->get_delegated_section_info();

            if ($delegatesection) {
                $sectionurl = new \moodle_url('/course/section.php', ['id' => $delegatesection->id]);

                $html = '<div class="card mb-3"><div class="card-body">';
                $html .= '<p><i class="fa fa-folder-open"></i> ' . get_string('sectionname', 'format_topics') . ': ' . format_string($delegatesection->name ?: $instance->name) . '</p>';
                $html .= '</div></div>';

                $html .= '<div class="text-center">';
                $html .= '<a href="' . $sectionurl . '" class="btn btn-primary btn-lg"><i class="fa fa-arrow-right"></i> ' . get_string('gotosection', 'mod_subsection') . '</a>';
                $html .= '</div>';

                return $html;
            }
        } catch (\Exception $e) {
            // Fall through to default.
        }

        return '<div class="text-center py-4"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg">' . get_string('view') . '</a></div>';
    }

    // =========================================================================
    // HVP MODULE - H5P content (third-party plugin)
    // =========================================================================
    protected static function render_mod_hvp($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;

        $html = '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-puzzle-piece"></i> H5P Interactive Content</p>';

        // Get content info if available.
        if (!empty($instance->name)) {
            $html .= '<p><strong>' . format_string($instance->name) . '</strong></p>';
        }

        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('view') . '</a>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // CUSTOMCERT MODULE - Certificate (third-party plugin)
    // =========================================================================
    protected static function render_mod_customcert($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER, $OUTPUT;

        $html = '';
        $canreceive = has_capability('mod/customcert:receiveissue', $context);
        $canmanage = has_capability('mod/customcert:manage', $context);
        $canviewreport = has_capability('mod/customcert:viewreport', $context);

        // Check if user has a certificate.
        $issue = $DB->get_record('customcert_issues', ['userid' => $USER->id, 'customcertid' => $instance->id]);

        $html .= '<div class="card mb-3"><div class="card-body">';

        if ($issue && !$canmanage) {
            $html .= '<div class="alert alert-success"><i class="fa fa-certificate"></i> ' . get_string('receiveddate', 'customcert') . ': ' . userdate($issue->timecreated) . '</div>';
        }

        // Check required time.
        if (!empty($instance->requiredtime) && !$canmanage) {
            $coursetime = \mod_customcert\certificate::get_course_time($course->id);
            $requiredtime = $instance->requiredtime * 60;
            if ($coursetime < $requiredtime) {
                $html .= '<div class="alert alert-warning">' . get_string('requiredtimenotmet', 'customcert', $instance->requiredtime) . '</div>';
            }
        }

        $html .= '</div></div>';

        $html .= '<div class="text-center">';

        if ($canreceive) {
            $html .= '<a href="' . (new \moodle_url('/mod/customcert/view.php', ['id' => $cm->id, 'downloadown' => true])) . '" class="btn btn-primary btn-lg me-2">';
            $html .= '<i class="fa fa-download"></i> ' . get_string('getcustomcert', 'customcert') . '</a>';
        }

        if ($canviewreport) {
            $numissues = \mod_customcert\certificate::get_number_of_issues($instance->id, $cm, groups_get_activity_groupmode($cm));
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-outline-secondary">';
            $html .= '<i class="fa fa-list"></i> ' . get_string('listofissues', 'customcert', $numissues) . '</a>';
        }

        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // HOTPOT MODULE - HotPot quiz (third-party plugin)
    // =========================================================================
    protected static function render_mod_hotpot($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/hotpot/locallib.php');

        $html = '';

        try {
            $hotpot = \hotpot::create($instance, $cm, $course, $context);

            $html .= '<div class="card mb-3"><div class="card-body">';

            // Time info.
            $timenow = time();
            if ($timenow < $instance->timeopen) {
                $html .= '<div class="alert alert-info">' . get_string('gamenotavailable', 'hotpot', userdate($instance->timeopen)) . '</div>';
            } else if ($instance->timeclose && $timenow > $instance->timeclose) {
                $html .= '<div class="alert alert-warning">' . get_string('gameclosed', 'hotpot', userdate($instance->timeclose)) . '</div>';
            }

            // Attempts.
            if ($hotpot->can_attempt() || $hotpot->can_preview()) {
                $html .= '<p><i class="fa fa-info-circle"></i> ' . format_string($instance->name) . '</p>';
            }

            $html .= '</div></div>';

            $html .= '<div class="text-center">';
            if ($hotpot->can_attempt() || $hotpot->can_preview()) {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . get_string('start', 'hotpot') . '</a>';
            } else {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-secondary btn-lg">' . get_string('view') . '</a>';
            }
            $html .= '</div>';

        } catch (\Exception $e) {
            $html = '<div class="text-center py-4"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg">' . get_string('view') . '</a></div>';
        }

        return $html;
    }

    // =========================================================================
    // CHECKLIST MODULE - (third-party plugin)
    // =========================================================================
    protected static function render_mod_checklist($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/checklist/locallib.php');

        $html = '';

        // Count items.
        $totalitems = $DB->count_records_select('checklist_item', 'checklist = ? AND userid = 0', [$instance->id]);
        $checkeditems = $DB->count_records_sql(
            "SELECT COUNT(DISTINCT ci.id)
             FROM {checklist_item} ci
             JOIN {checklist_check} cc ON cc.item = ci.id
             WHERE ci.checklist = ? AND ci.userid = 0 AND cc.userid = ? AND cc.usertimestamp > 0",
            [$instance->id, $USER->id]
        );

        $html .= '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-check-square-o"></i> ' . get_string('progress', 'checklist') . ': ' . $checkeditems . ' / ' . $totalitems . '</p>';

        // Progress bar.
        $pct = $totalitems > 0 ? round($checkeditems / $totalitems * 100) : 0;
        $html .= '<div class="progress" style="height:25px;">';
        $html .= '<div class="progress-bar bg-success" style="width:' . $pct . '%">' . $pct . '%</div>';
        $html .= '</div>';

        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-list"></i> ' . get_string('viewchecklistteacher', 'checklist') . '</a>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // GAME MODULE - (third-party plugin)
    // =========================================================================
    protected static function render_mod_game($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/game/locallib.php');

        $html = '';

        // Time restrictions.
        $timenow = time();
        $canattempt = true;

        $html .= '<div class="card mb-3"><div class="card-body">';

        if ($timenow < $instance->timeopen) {
            $canattempt = false;
            $html .= '<div class="alert alert-info">' . get_string('gamenotavailable', 'game', userdate($instance->timeopen)) . '</div>';
        } else if ($instance->timeclose && $timenow > $instance->timeclose) {
            $canattempt = false;
            $html .= '<div class="alert alert-warning">' . get_string('gameclosed', 'game', userdate($instance->timeclose)) . '</div>';
        }

        // Attempts.
        $attempts = game_get_user_attempts($instance->id, $USER->id);
        $numattempts = count($attempts);

        if ($numattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('attempts', 'game') . ': ' . $numattempts . '</p>';

            // Best grade.
            $mygrade = game_get_best_grade($instance, $USER->id);
            if ($mygrade !== null) {
                $html .= '<p><i class="fa fa-star"></i> ' . get_string('bestgrade', 'game') . ': ' . game_format_grade($instance, $mygrade) . '</p>';
            }
        }

        if ($instance->maxattempts > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> ' . get_string('maximumattempts', 'game') . ': ' . $instance->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        // Check if can start new attempt.
        if ($instance->maxattempts > 0 && $numattempts >= $instance->maxattempts) {
            $canattempt = false;
        }

        $html .= '<div class="text-center">';
        if ($canattempt || has_capability('mod/game:manage', $context)) {
            $buttontext = $numattempts == 0 ? get_string('attemptgamenow', 'game') : get_string('reattemptgame', 'game');
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
        } else {
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-secondary btn-lg">' . get_string('view') . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // READALOUD MODULE - (third-party plugin)
    // =========================================================================
    protected static function render_mod_readaloud($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;

        $html = '';

        // Get attempts.
        $attempts = $DB->get_records('readaloud_attempt', ['readaloudid' => $instance->id, 'userid' => $USER->id], 'timecreated DESC');
        $numattempts = count($attempts);

        $html .= '<div class="card mb-3"><div class="card-body">';

        // Open/close dates.
        $timenow = time();
        $closed = false;

        if (!empty($instance->viewstart) && $timenow < $instance->viewstart) {
            $html .= '<div class="alert alert-info">' . get_string('activityisnotopenyet', 'readaloud') . '</div>';
            $closed = true;
        } else if (!empty($instance->viewend) && $timenow > $instance->viewend) {
            $html .= '<div class="alert alert-warning">' . get_string('activityisclosed', 'readaloud') . '</div>';
            $closed = true;
        }

        if ($numattempts > 0) {
            $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('attempts', 'readaloud') . ': ' . $numattempts . '</p>';

            // Latest attempt info.
            $latest = reset($attempts);
            if (!empty($latest->sessionscore)) {
                $html .= '<p><i class="fa fa-star"></i> ' . get_string('grade', 'grades') . ': ' . format_float($latest->sessionscore, 0) . '%</p>';
            }
        }

        if ($instance->maxattempts > 0) {
            $html .= '<p><i class="fa fa-info-circle"></i> ' . get_string('maxattempts', 'readaloud') . ': ' . $instance->maxattempts . '</p>';
        }

        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        if (!$closed) {
            $canattempt = $instance->maxattempts == 0 || $numattempts < $instance->maxattempts;
            if ($canattempt || has_capability('mod/readaloud:preview', $context)) {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-microphone"></i> ' . get_string('startactivity', 'readaloud') . '</a>';
            }
        }
        if ($numattempts > 0) {
            $html .= ' <a href="' . (new \moodle_url('/mod/readaloud/view.php', ['id' => $cm->id, 'reviewattempts' => 1])) . '" class="btn btn-outline-secondary">';
            $html .= '<i class="fa fa-history"></i> ' . get_string('reviewattempts', 'readaloud') . '</a>';
        }
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // SCHEDULER MODULE - (third-party plugin)
    // =========================================================================
    protected static function render_mod_scheduler($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/mod/scheduler/locallib.php');

        $html = '';

        try {
            $scheduler = \mod_scheduler\model\scheduler::load_by_coursemodule_id($cm->id);
            $permissions = new \mod_scheduler\permission\scheduler_permissions($context, $USER->id);

            $isteacher = has_any_capability(['mod/scheduler:manage', 'mod/scheduler:manageallappointments'], $context);
            $isstudent = has_capability('mod/scheduler:viewslots', $context);

            $html .= '<div class="card mb-3"><div class="card-body">';

            if ($isteacher) {
                // Count slots and appointments.
                $slots = $DB->count_records('scheduler_slots', ['schedulerid' => $instance->id]);
                $appointments = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {scheduler_appointment} a
                     JOIN {scheduler_slots} s ON s.id = a.slotid
                     WHERE s.schedulerid = ?",
                    [$instance->id]
                );

                $html .= '<p><i class="fa fa-calendar"></i> ' . get_string('slots', 'scheduler') . ': ' . $slots . '</p>';
                $html .= '<p><i class="fa fa-users"></i> ' . get_string('appointments', 'scheduler') . ': ' . $appointments . '</p>';
            } else if ($isstudent) {
                // Student's appointments.
                $myappointments = $DB->count_records_sql(
                    "SELECT COUNT(*) FROM {scheduler_appointment} a
                     JOIN {scheduler_slots} s ON s.id = a.slotid
                     WHERE s.schedulerid = ? AND a.studentid = ?",
                    [$instance->id, $USER->id]
                );

                $html .= '<p><i class="fa fa-calendar-check-o"></i> ' . get_string('myappointments', 'scheduler') . ': ' . $myappointments . '</p>';
            }

            $html .= '</div></div>';

            $html .= '<div class="text-center">';
            $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-calendar"></i> ' . get_string('viewscheduler', 'scheduler') . '</a>';
            $html .= '</div>';

        } catch (\Exception $e) {
            $html = '<div class="text-center py-4"><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg">' . get_string('view') . '</a></div>';
        }

        return $html;
    }

    // =========================================================================
    // PDFPROTECT MODULE - Protected PDF (third-party plugin)
    // =========================================================================
    protected static function render_mod_pdfprotect($cm, $cminfo, $instance, $course, $context): string {
        global $OUTPUT;

        $html = '<div class="card mb-3"><div class="card-body">';
        $html .= '<p><i class="fa fa-file-pdf-o"></i> ' . get_string('pluginname', 'pdfprotect') . '</p>';
        $html .= '<p>' . format_string($instance->name) . '</p>';
        $html .= '</div></div>';

        $html .= '<div class="text-center">';
        $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-eye"></i> ' . get_string('view') . '</a>';
        $html .= '</div>';

        return $html;
    }

    // =========================================================================
    // DEFAULT FALLBACK
    // =========================================================================
    protected static function render_mod_default($cm, $cminfo, $modname): string {
        return '<div class="text-center py-4"><p><i class="fa fa-puzzle-piece fa-4x text-muted"></i></p><a href="' . $cminfo->url . '" class="btn btn-primary btn-lg">' . get_string('view') . ' ' . get_string('modulename', $modname) . '</a></div>';
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'content' => new external_value(PARAM_RAW, 'HTML content'),
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'modname' => new external_value(PARAM_TEXT, 'Module name'),
            'name' => new external_value(PARAM_TEXT, 'Activity name'),
            'url' => new external_value(PARAM_URL, 'Activity URL'),
        ]);
    }
}
