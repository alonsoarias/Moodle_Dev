<?php
namespace local_quiz_retake_ui\external;

use external_api;
use external_function_parameters;
use external_value;
use context_module;
use mod_quiz\quiz_attempt;
use dml_exception;

class toggle_risky extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
            'slot' => new external_value(PARAM_INT, 'Question slot'),
            'state' => new external_value(PARAM_BOOL, 'Whether question is risky'),
        ]);
    }

    /**
     * Mark or unmark a question as risky for an attempt.
     * @throws \required_capability_exception|\moodle_exception
     */
    public static function execute(int $attemptid, int $slot, bool $state): array {
        global $DB, $USER;

        $attemptobj = quiz_attempt::create($attemptid);
        require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
        $context = context_module::instance($attemptobj->get_cmid());
        self::validate_context($context);
        require_capability('mod/quiz:attempt', $context);
        require_sesskey();

        $params = ['attemptid' => $attemptid, 'slot' => $slot, 'userid' => $USER->id];
        if ($state) {
            if (!$DB->record_exists('local_quiz_retake_risky', $params)) {
                $params['timecreated'] = time();
                $DB->insert_record('local_quiz_retake_risky', $params);
            }
        } else {
            $DB->delete_records('local_quiz_retake_risky', $params);
        }
        return ['status' => 'ok'];
    }

    public static function execute_returns() {
        return new external_function_parameters([
            'status' => new external_value(PARAM_TEXT, 'Result status'),
        ]);
    }
}
