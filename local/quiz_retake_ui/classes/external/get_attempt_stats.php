<?php
// External function to return attempt statistics.

namespace local_quiz_retake_ui\external;

use external_api;
use external_function_parameters;
use external_single_structure;
use external_multiple_structure;
use external_value;
use context_module;
use mod_quiz\quiz_attempt;
use moodle_exception;

class get_attempt_stats extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Attempt ID'),
        ]);
    }

    public static function execute(int $attemptid): array {
        global $PAGE;
        $attemptobj = quiz_attempt::create($attemptid);
        require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
        $context = context_module::instance($attemptobj->get_cmid());
        self::validate_context($context);

        global $DB, $USER;
        $riskys = $DB->get_records('local_quiz_retake_risky', ['attemptid' => $attemptid, 'userid' => $USER->id], '', 'slot');
        $riskyslots = array_map(fn($r) => $r->slot, $riskys);
        $correct = $incorrect = $blank = 0;
        foreach ($attemptobj->get_slots() as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            if ($qa->get_state()->is_unanswered()) {
                $blank++;
                continue;
            }
            if ($qa->get_fraction() >= 1) {
                $correct++;
            } else {
                $incorrect++;
            }
        }
        $normalgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $attemptobj->get_quiz(), false);
        $riskfreegrade = quiz_rescale_grade(grades::risk_free_grade($attemptobj, $riskyslots), $attemptobj->get_quiz(), false);
        $resultpass = $normalgrade >= $attemptobj->get_quiz()->gradepass;
        return [
            'normalgrade' => $normalgrade,
            'riskfreegrade' => $riskfreegrade,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'blank' => $blank,
            'risky' => count($riskyslots),
            'riskyslots' => $riskyslots,
            'cutgrade' => $attemptobj->get_quiz()->gradepass,
            'result' => $resultpass ? get_string('pass', 'local_quiz_retake_ui') : get_string('fail', 'local_quiz_retake_ui'),
            'pass' => $resultpass,
        ];
    }

    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'normalgrade' => new external_value(PARAM_FLOAT, 'Normal grade'),
            'riskfreegrade' => new external_value(PARAM_FLOAT, 'Risk-free grade'),
            'correct' => new external_value(PARAM_INT, 'Correct questions'),
            'incorrect' => new external_value(PARAM_INT, 'Incorrect questions'),
            'blank' => new external_value(PARAM_INT, 'Unanswered questions'),
            'risky' => new external_value(PARAM_INT, 'Risked questions'),
            'cutgrade' => new external_value(PARAM_FLOAT, 'Grade to pass'),
            'result' => new external_value(PARAM_TEXT, 'Result string'),
            'pass' => new external_value(PARAM_BOOL, 'Whether passed'),
            'riskyslots' => new external_multiple_structure(new external_value(PARAM_INT, 'Risky slot')),
        ]);
    }
}
