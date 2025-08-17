<?php
namespace local_quiz_retake_ui\external;

use external_api;
use external_function_parameters;
use external_value;
use context_module;
use mod_quiz\quiz_attempt;
use moodle_url;
use question_engine;
use question_usage_by_activity;

class create_retake extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Original attempt id'),
            'mode' => new external_value(PARAM_ALPHA, 'subset option'),
        ]);
    }

    public static function execute(int $attemptid, string $mode): array {
        $attemptobj = quiz_attempt::create($attemptid);
        require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
        $context = context_module::instance($attemptobj->get_cmid());
        self::validate_context($context);
        require_sesskey();

        // TODO: Build new attempt based on $mode.
        $newattemptid = 0;

        return ['newattemptid' => $newattemptid];
    }

    public static function execute_returns() {
        return new external_function_parameters([
            'newattemptid' => new external_value(PARAM_INT, 'ID of the created attempt'),
        ]);
    }
}
