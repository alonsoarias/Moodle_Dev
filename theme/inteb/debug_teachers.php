<?php
/**
 * Debug script to check teacher roles in courses
 *
 * Run this from command line: php theme/inteb/debug_teachers.php [courseid]
 * Or access via browser: http://yoursite/theme/inteb/debug_teachers.php?courseid=X
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Get course ID from command line or URL parameter
if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
    $courseid = isset($argv[1]) ? (int)$argv[1] : 0;
} else {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
    $courseid = optional_param('courseid', 0, PARAM_INT);
}

if (!$courseid) {
    echo "Usage: php debug_teachers.php [courseid]\n";
    echo "   or: http://yoursite/theme/inteb/debug_teachers.php?courseid=X\n";
    exit(1);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursecontext = context_course::instance($courseid);

echo "\n";
echo "=== DEBUGGING TEACHER ROLES IN COURSE ===\n";
echo "Course ID: {$course->id}\n";
echo "Course Name: {$course->fullname}\n";
echo "Group Mode: {$course->groupmode} (0=No groups, 1=Separate, 2=Visible)\n";
echo "\n";

// Check if roles exist
echo "--- CHECKING ROLES ---\n";
$editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
$teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

if ($editingteacherrole) {
    echo "✓ editingteacher role exists (ID: {$editingteacherrole->id})\n";
} else {
    echo "✗ editingteacher role NOT FOUND!\n";
}

if ($teacherrole) {
    echo "✓ teacher role exists (ID: {$teacherrole->id})\n";
} else {
    echo "✗ teacher role NOT FOUND!\n";
}
echo "\n";

// Get users with editingteacher role
echo "--- USERS WITH EDITINGTEACHER ROLE ---\n";
if ($editingteacherrole) {
    $editingteachers = get_role_users(
        $editingteacherrole->id,
        $coursecontext,
        true,
        'u.*',
        'u.firstname',
        true,
        0,  // All groups
        '',
        '',
        '',
        ''
    );
    echo "Found " . count($editingteachers) . " editingteachers:\n";
    foreach ($editingteachers as $user) {
        echo "  - {$user->firstname} {$user->lastname} (ID: {$user->id}, Username: {$user->username})\n";
    }
} else {
    echo "Cannot check - role not found\n";
}
echo "\n";

// Get users with teacher role
echo "--- USERS WITH TEACHER ROLE (non-editing) ---\n";
if ($teacherrole) {
    $teachers = get_role_users(
        $teacherrole->id,
        $coursecontext,
        true,
        'u.*',
        'u.firstname',
        true,
        0,  // All groups
        '',
        '',
        '',
        ''
    );
    echo "Found " . count($teachers) . " teachers (non-editing):\n";
    foreach ($teachers as $user) {
        echo "  - {$user->firstname} {$user->lastname} (ID: {$user->id}, Username: {$user->username})\n";
    }
} else {
    echo "Cannot check - role not found\n";
}
echo "\n";

// Check with coursehandler
echo "--- USING THEME_INTEB COURSEHANDLER ---\n";
try {
    require_once($CFG->dirroot . '/theme/inteb/classes/coursehandler.php');
    $coursehandler = new \theme_inteb\coursehandler();
    $context = $coursehandler->get_enrolled_teachers_context($course, true);

    echo "hasteachers: " . (isset($context['hasteachers']) && $context['hasteachers'] ? 'YES' : 'NO') . "\n";

    if (isset($context['instructors']) && is_array($context['instructors'])) {
        echo "Instructors in context: " . count($context['instructors']) . "\n";
        foreach ($context['instructors'] as $instructor) {
            echo "  - {$instructor['name']} (ID: {$instructor['id']})\n";
        }
    } else {
        echo "No instructors array in context!\n";
    }

    if (isset($context['teachercount'])) {
        echo "Additional teachers: {$context['teachercount']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// Check with parent theme coursehandler for comparison
echo "--- USING THEME_REMUI COURSEHANDLER (for comparison) ---\n";
try {
    require_once($CFG->dirroot . '/theme/remui/classes/coursehandler.php');
    $parenthandler = new theme_remui_coursehandler();
    $parentcontext = $parenthandler->get_enrolled_teachers_context($course, true);

    echo "hasteachers: " . (isset($parentcontext['hasteachers']) && $parentcontext['hasteachers'] ? 'YES' : 'NO') . "\n";

    if (isset($parentcontext['instructors']) && is_array($parentcontext['instructors'])) {
        echo "Instructors in context: " . count($parentcontext['instructors']) . "\n";
        foreach ($parentcontext['instructors'] as $instructor) {
            echo "  - {$instructor['name']} (ID: {$instructor['id']})\n";
        }
    } else {
        echo "No instructors array in context!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== DEBUG COMPLETE ===\n";
echo "\n";
