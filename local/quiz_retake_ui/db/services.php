<?php
$functions = [
    'local_quiz_retake_ui_get_attempt_stats' => [
        'classname'   => 'local_quiz_retake_ui\external\get_attempt_stats',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Return counts and grades for an attempt',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    'local_quiz_retake_ui_create_retake' => [
        'classname'   => 'local_quiz_retake_ui\external\create_retake',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Create a retake attempt with selected questions',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
    'local_quiz_retake_ui_toggle_risky' => [
        'classname'   => 'local_quiz_retake_ui\external\toggle_risky',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Toggle risky flag for a question slot',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => [MOODLE_OFFICIAL_MOBILE_SERVICE]
    ],
];
