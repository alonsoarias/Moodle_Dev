<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Inject JS and CSS on quiz review pages.
 */
function local_quiz_retake_ui_before_http_headers() {
    global $PAGE, $CFG;
    if (!$PAGE->has_set_url()) {
        return;
    }
    $url = $PAGE->url->out_as_local_url();
    if (strpos($url, '/mod/quiz/review.php') === 0) {
        $PAGE->requires->js_call_amd('local_quiz_retake_ui/review', 'init', [optional_param('attempt', 0, PARAM_INT)]);
        $PAGE->requires->css('/local/quiz_retake_ui/styles/styles.css');
    } else if (strpos($url, '/mod/quiz/attempt.php') === 0) {
        $PAGE->requires->js_call_amd('local_quiz_retake_ui/attempt', 'init', [optional_param('attempt', 0, PARAM_INT)]);
        $PAGE->requires->css('/local/quiz_retake_ui/styles/styles.css');
    }
}
