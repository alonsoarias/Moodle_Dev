<?php
namespace local_quiz_retake_ui;

use mod_quiz\event\attempt_reviewed;

class observer {
    public static function attempt_reviewed(attempt_reviewed $event): void {
        // Placeholder for logging or other actions.
    }
}
