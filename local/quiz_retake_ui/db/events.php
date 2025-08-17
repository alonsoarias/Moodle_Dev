<?php
$observers = [
    [
        'eventname'   => '\\mod_quiz\\event\\attempt_reviewed',
        'callback'    => 'local_quiz_retake_ui\\observer::attempt_reviewed',
        'priority'    => 9999,
    ],
];
