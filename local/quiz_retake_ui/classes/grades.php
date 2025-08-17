<?php
namespace local_quiz_retake_ui;

use mod_quiz\quiz_attempt;

class grades {
    /**
     * Calculate risk-free grade excluding risky slots.
     * This is a simplified placeholder.
     */
    public static function risk_free_grade(quiz_attempt $attempt, array $riskyslots): float {
        // Placeholder: simply return attempt's sumgrades ignoring risky slots.
        $grade = 0;
        foreach ($attempt->get_slots() as $slot) {
            if (in_array($slot, $riskyslots)) {
                continue;
            }
            $grade += $attempt->get_question_attempt($slot)->get_mark();
        }
        return $grade;
    }
}
