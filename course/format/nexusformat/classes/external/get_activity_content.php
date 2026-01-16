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
    // QUIZ MODULE - Using mod/quiz classes and renderer
    // =========================================================================
    protected static function render_mod_quiz($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB, $USER, $PAGE;
        require_once($CFG->dirroot . '/mod/quiz/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        require_once($CFG->libdir . '/gradelib.php');

        $html = '';
        $canpreview = has_capability('mod/quiz:preview', $context);
        $canmanage = has_capability('mod/quiz:manage', $context);

        // Use quiz_settings and access_manager like view.php.
        try {
            $quizobj = \mod_quiz\quiz_settings::create_for_cmid($cm->id, $USER->id);
            $accessmanager = new \mod_quiz\access_manager($quizobj, time(),
                has_capability('mod/quiz:ignoretimelimits', $context, null, false));
            $infomessages = $accessmanager->describe_rules();

            if (!empty($infomessages)) {
                $html .= '<div class="alert alert-info"><ul class="mb-0">';
                foreach ($infomessages as $msg) {
                    $html .= '<li>' . $msg . '</li>';
                }
                $html .= '</ul></div>';
            }
        } catch (\Exception $e) {
            // Continue without access manager.
        }

        // Quiz info.
        $html .= '<div class="card mb-3"><div class="card-body">';
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> ' . get_string('timelimit', 'quiz') . ': ' . format_time($instance->timelimit) . '</p>';
        }
        $html .= '<p><i class="fa fa-repeat"></i> ' . get_string('attemptsallowed', 'quiz') . ': ';
        $html .= $instance->attempts ? $instance->attempts : get_string('unlimited') . '</p>';

        if ($instance->attempts != 1) {
            $html .= '<p><i class="fa fa-calculator"></i> ' . get_string('gradingmethod', 'quiz', quiz_get_grading_option_name($instance->grademethod)) . '</p>';
        }
        $html .= '</div></div>';

        // User attempts.
        if (!$canmanage) {
            $attempts = quiz_get_user_attempts($instance->id, $USER->id, 'finished', true);
            $unfinished = quiz_get_user_attempt_unfinished($instance->id, $USER->id);
            $numattempts = count($attempts);

            if ($attempts) {
                $mygrade = quiz_get_best_grade($instance, $USER->id);
                if ($mygrade !== null) {
                    $html .= '<div class="alert alert-success">' . get_string('yourfinalgradeis', 'quiz',
                        quiz_format_grade($instance, $mygrade) . '/' . quiz_format_grade($instance, $instance->grade)) . '</div>';
                }

                $html .= '<table class="table table-striped"><thead><tr>';
                $html .= '<th>' . get_string('attempt', 'quiz') . '</th>';
                $html .= '<th>' . get_string('state', 'quiz') . '</th>';
                $html .= '<th>' . get_string('grade', 'grades') . '</th></tr></thead><tbody>';
                $num = 1;
                foreach ($attempts as $att) {
                    $grade = $att->sumgrades !== null ? quiz_format_grade($instance, quiz_rescale_grade($att->sumgrades, $instance, false)) : '-';
                    $html .= '<tr><td>' . $num++ . '</td><td>' . quiz_attempt_state_name($att->state) . '</td><td>' . $grade . '</td></tr>';
                }
                $html .= '</tbody></table>';
            }

            // Button.
            $html .= '<div class="text-center">';
            $nomoreattempts = $instance->attempts && $numattempts >= $instance->attempts;

            if ($unfinished) {
                $buttontext = $canpreview ? get_string('continuepreview', 'quiz') : get_string('continueattemptquiz', 'quiz');
            } else if ($nomoreattempts && !$canpreview) {
                $html .= '<div class="alert alert-warning">' . get_string('nomoreattempts', 'quiz') . '</div>';
                $buttontext = '';
            } else {
                $buttontext = $numattempts == 0 ? get_string('attemptquiz', 'quiz') : get_string('reattemptquiz', 'quiz');
            }

            if (!empty($buttontext)) {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-play-circle"></i> ' . $buttontext . '</a>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="text-center">';
            $html .= '<a href="' . (new \moodle_url('/mod/quiz/report.php', ['id' => $cm->id, 'mode' => 'overview'])) . '" class="btn btn-primary me-2"><i class="fa fa-bar-chart"></i> ' . get_string('viewreports', 'quiz') . '</a>';
            $html .= '<a href="' . (new \moodle_url('/mod/quiz/edit.php', ['cmid' => $cm->id])) . '" class="btn btn-secondary"><i class="fa fa-pencil"></i> ' . get_string('editquiz', 'quiz') . '</a>';
            $html .= '</div>';
        }

        return $html;
    }

    // =========================================================================
    // ASSIGN MODULE - Using assign class like view.php
    // =========================================================================
    protected static function render_mod_assign($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $USER, $OUTPUT;
        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        // Create assign object exactly like view.php line 38.
        $assign = new \assign($context, $cm, $course);

        // Apply overrides like view.php line 51.
        $assign->update_effective_access($USER->id);

        $effectiveinstance = $assign->get_instance();
        $cangrade = has_capability('mod/assign:grade', $context);

        $html = '<div class="card mb-3"><div class="card-body">';

        // Due date.
        if ($effectiveinstance->duedate) {
            $class = $effectiveinstance->duedate < time() ? 'text-danger' : 'text-success';
            $html .= '<p><i class="fa fa-calendar"></i> ' . get_string('duedate', 'assign') . ': ';
            $html .= '<span class="' . $class . '">' . userdate($effectiveinstance->duedate) . '</span></p>';

            if ($effectiveinstance->duedate > time()) {
                $html .= '<p><i class="fa fa-clock-o"></i> ' . get_string('timeremaining', 'assign') . ': ' . format_time($effectiveinstance->duedate - time()) . '</p>';
            }
        }

        if ($effectiveinstance->cutoffdate) {
            $html .= '<p><i class="fa fa-ban"></i> ' . get_string('cutoffdate', 'assign') . ': ' . userdate($effectiveinstance->cutoffdate) . '</p>';
        }
        $html .= '</div></div>';

        if ($cangrade) {
            // Teacher view - use assign's grading summary renderable.
            $summary = $assign->get_assign_grading_summary_renderable();
            if ($summary) {
                $html .= '<div class="card mb-3"><div class="card-header">' . get_string('gradingsummary', 'assign') . '</div>';
                $html .= '<div class="card-body">';
                $html .= '<p>' . get_string('numberofparticipants', 'assign') . ': ' . $summary->participantcount . '</p>';
                $html .= '<p>' . get_string('numberofsubmittedassignments', 'assign') . ': ' . $summary->submissionssubmittedcount . '</p>';
                $html .= '<p>' . get_string('numberofsubmissionsneedgrading', 'assign') . ': ' . $summary->submissionsneedgradingcount . '</p>';
                $html .= '</div></div>';
            }
            $html .= '<div class="text-center"><a href="' . (new \moodle_url('/mod/assign/view.php', ['id' => $cm->id, 'action' => 'grading'])) . '" class="btn btn-primary btn-lg"><i class="fa fa-check-square"></i> ' . get_string('viewgrading', 'assign') . '</a></div>';
        } else {
            // Student view - use assign methods.
            $submission = $assign->get_user_submission($USER->id, false);
            $grade = $assign->get_user_grade($USER->id, false);

            $html .= '<div class="card mb-3"><div class="card-body">';
            if ($submission) {
                $statusclass = $submission->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED ? 'bg-success' : 'bg-warning';
                $html .= '<p><i class="fa fa-check-square-o"></i> ' . get_string('submissionstatus', 'assign') . ': ';
                $html .= '<span class="badge ' . $statusclass . '">' . get_string('submissionstatus_' . $submission->status, 'assign') . '</span></p>';

                if ($submission->timemodified) {
                    $html .= '<p><i class="fa fa-edit"></i> ' . get_string('timemodified', 'assign') . ': ' . userdate($submission->timemodified) . '</p>';
                }
            } else {
                $html .= '<p><i class="fa fa-exclamation-circle"></i> ' . get_string('submissionstatus', 'assign') . ': ';
                $html .= '<span class="badge bg-secondary">' . get_string('nosubmission', 'assign') . '</span></p>';
            }

            if ($grade && $grade->grade !== null && $grade->grade >= 0) {
                $html .= '<p><i class="fa fa-star"></i> ' . get_string('grade', 'grades') . ': ' . format_float($grade->grade, 2) . '/' . format_float($effectiveinstance->grade, 2) . '</p>';
            }
            $html .= '</div></div>';

            // Button.
            $html .= '<div class="text-center">';
            $submissionsopen = $assign->submissions_open($USER->id);
            if ($submissionsopen && (!$submission || $submission->status != ASSIGN_SUBMISSION_STATUS_SUBMITTED)) {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-primary btn-lg"><i class="fa fa-upload"></i> ' . get_string('addsubmission', 'assign') . '</a>';
            } else {
                $html .= '<a href="' . $cminfo->url . '" class="btn btn-secondary btn-lg"><i class="fa fa-eye"></i> ' . get_string('viewsubmission', 'assign') . '</a>';
            }
            $html .= '</div>';
        }

        return $html;
    }

    // =========================================================================
    // FORUM MODULE - Using forum lib functions
    // =========================================================================
    protected static function render_mod_forum($cm, $cminfo, $instance, $course, $context): string {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/forum/lib.php');

        $html = '';

        // Forum type.
        $types = forum_get_forum_types();
        if (isset($types[$instance->type])) {
            $html .= '<span class="badge bg-secondary mb-2">' . $types[$instance->type] . '</span>';
        }

        // Discussions.
        $discussions = $DB->get_records_sql(
            "SELECT d.*, u.firstname, u.lastname, (SELECT COUNT(*) FROM {forum_posts} p WHERE p.discussion = d.id) - 1 as replies
             FROM {forum_discussions} d JOIN {user} u ON u.id = d.userid
             WHERE d.forum = ? ORDER BY d.pinned DESC, d.timemodified DESC LIMIT 10",
            [$instance->id]
        );
        $total = $DB->count_records('forum_discussions', ['forum' => $instance->id]);

        $html .= '<p class="text-muted"><i class="fa fa-comments"></i> ' . $total . ' ' . get_string('discussions', 'forum') . '</p>';

        if ($discussions) {
            $html .= '<div class="list-group mb-3">';
            foreach ($discussions as $d) {
                $url = new \moodle_url('/mod/forum/discuss.php', ['d' => $d->id]);
                $html .= '<a href="' . $url . '" class="list-group-item list-group-item-action">';
                if ($d->pinned) $html .= '<span class="badge bg-warning float-end"><i class="fa fa-thumb-tack"></i></span>';
                $html .= '<strong>' . format_string($d->name) . '</strong><br>';
                $html .= '<small class="text-muted">' . fullname($d) . ' &middot; ' . $d->replies . ' ' . get_string('replies', 'forum') . '</small>';
                $html .= '</a>';
            }
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-info">' . get_string('nodiscussions', 'forum') . '</div>';
        }

        // Buttons.
        $html .= '<div class="text-center">';
        if (forum_user_can_post_discussion($instance, null, -1, $cm, $context)) {
            $html .= '<a href="' . (new \moodle_url('/mod/forum/post.php', ['forum' => $instance->id])) . '" class="btn btn-primary me-2"><i class="fa fa-plus"></i> ' . get_string('addanewdiscussion', 'forum') . '</a>';
        }
        $html .= '<a href="' . $cminfo->url . '" class="btn btn-outline-primary">' . get_string('viewalldiscussions', 'forum') . '</a>';
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
