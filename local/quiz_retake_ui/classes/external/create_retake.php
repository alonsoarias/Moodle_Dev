<?php
namespace local_quiz_retake_ui\external;

use external_api;
use external_function_parameters;
use external_value;
use context_module;
use mod_quiz\quiz_attempt;
use question_engine;
use moodle_exception;
use local_quiz_retake_ui\grades;

class create_retake extends external_api {
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'attemptid' => new external_value(PARAM_INT, 'Original attempt id'),
            'mode' => new external_value(PARAM_ALPHA, 'subset option'),
        ]);
    }

    public static function execute(int $attemptid, string $mode): array {
        global $DB, $USER;
        $attemptobj = quiz_attempt::create($attemptid);
        require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
        $context = context_module::instance($attemptobj->get_cmid());
        self::validate_context($context);
        require_sesskey();

        $includeblank = in_array($mode, ['failed_blank', 'failed_blank_risky']);
        $includerisky = in_array($mode, ['failed_risky', 'failed_blank_risky']);

        $riskys = $DB->get_records('local_quiz_retake_risky', ['attemptid' => $attemptid, 'userid' => $USER->id], '', 'slot');
        $riskyslots = array_map(fn($r) => $r->slot, $riskys);

        $slots = [];
        $questionids = [];
        foreach ($attemptobj->get_slots() as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            $failed = !$qa->get_state()->is_unanswered() && $qa->get_fraction() < 1;
            $blank = $qa->get_state()->is_unanswered();
            $risky = in_array($slot, $riskyslots);
            if ($failed || ($includeblank && $blank) || ($includerisky && $risky)) {
                $slots[] = $slot;
                $questionids[] = $qa->get_question()->id;
            }
        }

        if (!$slots) {
            throw new moodle_exception('nothingtoretake', 'local_quiz_retake_ui');
        }

        $quizobj = $attemptobj->get_quizobj();
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        foreach ($slots as $slot) {
            $qa = $attemptobj->get_question_attempt($slot);
            $question = $qa->get_question();
            $maxmark = $attemptobj->get_question_max_mark($slot);
            $newslot = $quba->add_question($question, $maxmark);
            $quba->start_question($newslot);
        }
        question_engine::save_questions_usage_by_activity($quba);

        $timenow = time();
        $attemptnumber = $DB->count_records('quiz_attempts', ['quiz' => $quizobj->get_quizid(), 'userid' => $USER->id]) + 1;
        $attempt = quiz_create_attempt($quizobj, $attemptnumber, null, $timenow, false, $USER->id);
        $attempt->uniqueid = $quba->get_id();
        $attempt->timecheckstate = 0;
        $newattemptid = $DB->insert_record('quiz_attempts', $attempt);

        $normalgrade = quiz_rescale_grade($attemptobj->get_sum_marks(), $attemptobj->get_quiz(), false);
        $riskgrade = quiz_rescale_grade(grades::risk_free_grade($attemptobj, $riskyslots), $attemptobj->get_quiz(), false);
        $DB->insert_record('local_quiz_retake_log', (object)[
            'originalattemptid' => $attemptid,
            'newattemptid' => $newattemptid,
            'userid' => $USER->id,
            'questionids' => implode(',', $questionids),
            'options' => $mode,
            'normalgrade' => $normalgrade,
            'riskfreegrade' => $riskgrade,
            'timecreated' => $timenow,
        ]);

        return ['newattemptid' => $newattemptid];
    }

    public static function execute_returns() {
        return new external_function_parameters([
            'newattemptid' => new external_value(PARAM_INT, 'ID of the created attempt'),
        ]);
    }
}
