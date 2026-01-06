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
            // Excluded modules - these are not displayed inline.
            case 'subsection':
            case 'intebchat':
            case 'folder_custom':
                // These modules are excluded from inline display.
                $html .= self::get_excluded_module_content($cm, $cminfo, $context);
                break;

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

            default:
                // For other activities, try to get content dynamically.
                $html .= self::get_generic_activity_content($cm, $cminfo, $context);
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

        $html .= '</div></div>';

        // User attempts.
        $attempts = quiz_get_user_attempts($instance->id, $USER->id, 'finished', true);
        if ($attempts) {
            $html .= '<div class="nexus-quiz-attempts mb-3">';
            $html .= '<h5>' . get_string('yourattempts', 'quiz') . '</h5>';
            $html .= '<table class="table table-striped">';
            $html .= '<thead><tr><th>' . get_string('attempt', 'quiz') . '</th>';
            $html .= '<th>' . get_string('state', 'quiz') . '</th>';
            $html .= '<th>' . get_string('grade', 'grades') . '</th></tr></thead>';
            $html .= '<tbody>';

            $attemptnum = 1;
            foreach ($attempts as $attempt) {
                $html .= '<tr>';
                $html .= '<td>' . $attemptnum++ . '</td>';
                $html .= '<td>' . quiz_attempt_state_name($attempt->state) . '</td>';
                if ($attempt->sumgrades !== null) {
                    $grade = quiz_rescale_grade($attempt->sumgrades, $instance, false);
                    $html .= '<td>' . format_float($grade, $instance->decimalpoints) . ' / ' .
                             format_float($instance->grade, $instance->decimalpoints) . '</td>';
                } else {
                    $html .= '<td>-</td>';
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
        $html .= '<div class="card-header"><strong>' . get_string('quizinformation', 'quiz') . '</strong></div>';
        $html .= '<div class="card-body">';

        // Time limit.
        if ($instance->timelimit) {
            $html .= '<p><i class="fa fa-clock-o"></i> <strong>' . get_string('timelimit', 'quiz') . ':</strong> ';
            $html .= format_time($instance->timelimit) . '</p>';
        }

        // Number of questions.
        $questioncount = $DB->count_records('quiz_slots', ['quizid' => $instance->id]);
        $html .= '<p><i class="fa fa-question-circle"></i> <strong>' . get_string('numquestions', 'quiz') . ':</strong> ';
        $html .= $questioncount . '</p>';

        // Total marks.
        $html .= '<p><i class="fa fa-star"></i> <strong>' . get_string('totalmarks', 'quiz') . ':</strong> ';
        $html .= format_float($instance->sumgrades, 2) . '</p>';

        // Grade to pass.
        if ($instance->grade > 0) {
            $html .= '<p><i class="fa fa-graduation-cap"></i> <strong>' . get_string('gradetopass', 'grades') . ':</strong> ';
            $html .= format_float($instance->grade, 2) . '</p>';
        }

        $html .= '</div></div>';

        // Attempts summary.
        $html .= '<div class="nexus-quiz-summary card mb-3">';
        $html .= '<div class="card-header"><strong>' . get_string('attemptsummary', 'quiz') . '</strong></div>';
        $html .= '<div class="card-body">';

        // Count total attempts.
        $totalattempts = $DB->count_records('quiz_attempts', ['quiz' => $instance->id, 'preview' => 0]);
        $html .= '<p><i class="fa fa-list"></i> <strong>' . get_string('totalattempts', 'quiz') . ':</strong> ' . $totalattempts . '</p>';

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
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewallsubmissions', 'assign') . '</a>';

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
        $discussions = $DB->get_records_sql(
            "SELECT d.*, d.pinned,
                    u.id as userid, u.firstname, u.lastname, u.picture, u.imagealt, u.email,
                    (SELECT COUNT(*) FROM {forum_posts} p WHERE p.discussion = d.id) as replycount,
                    (SELECT MAX(p.modified) FROM {forum_posts} p WHERE p.discussion = d.id) as lastposttime,
                    lastpost.userid as lastuserid,
                    lastuser.firstname as lastfirstname, lastuser.lastname as lastlastname
             FROM {forum_discussions} d
             JOIN {user} u ON u.id = d.userid
             LEFT JOIN {forum_posts} lastpost ON lastpost.id = d.usermodified
             LEFT JOIN {user} lastuser ON lastuser.id = lastpost.userid
             WHERE d.forum = ?
             ORDER BY d.pinned DESC, d.timemodified DESC",
            [$instance->id]
        );

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
        $html .= '<i class="fa fa-list"></i> ' . get_string('viewforum', 'forum') . '</a>';

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
            $availablemessage = get_string('lessonnotavailable', 'lesson', userdate($instance->available));
        } else if ($instance->deadline > 0 && time() > $instance->deadline) {
            $available = false;
            $availablemessage = get_string('lessonclosed', 'lesson', userdate($instance->deadline));
        }

        // Lesson info card.
        $html .= '<div class="nexus-lesson-info card mb-3">';
        $html .= '<div class="card-body">';

        // Number of pages.
        $pagecount = $DB->count_records('lesson_pages', ['lessonid' => $instance->id]);
        $html .= '<p><i class="fa fa-file-text-o"></i> <strong>' . get_string('numberoflessons', 'lesson') . ':</strong> ';
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
                $html .= '<div class="card-header"><strong>' . get_string('yourattempts', 'lesson') . '</strong></div>';
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
                $html .= '<i class="fa fa-info-circle"></i> ' . get_string('youhaveaninprogressattempt', 'lesson');
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
                    $html .= '<i class="fa fa-info-circle"></i> ' . get_string('feedbacknotstarted', 'feedback');
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
                $html .= get_string('viewall', 'glossary') . ' (' . $totalentries . ' ' . get_string('entries', 'glossary') . ')</a>';
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
        $html .= '<i class="fa fa-book"></i> ' . get_string('viewglossary', 'glossary') . '</a>';

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
                $html .= '<i class="fa fa-book"></i> ' . get_string('viewallpages', 'wiki') . '</a>';
                $html .= '</div>';
            } else {
                // Create first page.
                $html .= '<div class="alert alert-info">';
                $html .= get_string('nopages', 'wiki');
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
            $html .= '<i class="fa fa-play-circle"></i> ' . get_string('viewwiki', 'wiki') . '</a>';
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
     */
    protected static function get_h5p_content($cm, $instance, $context): string {
        global $CFG, $OUTPUT;
        require_once($CFG->dirroot . '/mod/h5pactivity/lib.php');

        $html = '<div class="nexus-h5p-content">';

        // Get H5P file.
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_h5pactivity', 'package', 0, 'id', false);

        if ($files) {
            $file = reset($files);
            $h5purl = \moodle_url::make_pluginfile_url(
                $context->id,
                'mod_h5pactivity',
                'package',
                0,
                $file->get_filepath(),
                $file->get_filename()
            );

            // Render H5P player.
            $html .= '<div class="h5p-placeholder" data-h5p-url="' . $h5purl->out() . '">';
            $html .= \core_h5p\player::display($h5purl->out(false), new \stdClass(), true, '', true);
            $html .= '</div>';
        } else {
            $html .= '<div class="alert alert-warning">' . get_string('contenttypenotinstalled', 'h5p') . '</div>';
        }

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
     * Get content for excluded modules (subsection, intebchat, folder_custom).
     * These modules are not displayed inline but we show a helpful message.
     *
     * @param object $cm Course module record
     * @param \cm_info $cminfo Course module info object
     * @param \context_module $context Module context
     * @return string HTML content
     */
    protected static function get_excluded_module_content($cm, $cminfo, $context): string {
        $modname = $cm->modname;
        $modulename = get_string('pluginname', 'mod_' . $modname);

        $html = '<div class="nexus-excluded-module text-center py-5">';
        $html .= '<div class="nexus-excluded-icon mb-4">';
        $html .= '<img src="' . $cminfo->get_icon_url() . '" alt="" style="width: 64px; height: 64px;" />';
        $html .= '</div>';
        $html .= '<h4>' . format_string($modulename) . '</h4>';
        $html .= '<p class="text-muted">';
        $html .= get_string('excluded_module_message', 'format_nexusformat');
        $html .= '</p>';

        // Open in full view button.
        if ($cminfo->url) {
            $html .= '<a href="' . $cminfo->url->out() . '" class="btn btn-primary">';
            $html .= '<i class="fa fa-external-link"></i> ' . get_string('openactivity', 'format_nexusformat');
            $html .= '</a>';
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
