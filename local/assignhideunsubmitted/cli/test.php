<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test script to verify the plugin is working
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');

$usage = "Test Assignment Hide Unsubmitted Plugin

Usage:
  php test.php --courseid=ID --assignid=ID [--userid=ID]

Options:
  --courseid    Course ID (required)
  --assignid    Assignment ID (required)
  --userid      User ID to test as (optional, defaults to admin)
  --help        Show this help message

Example:
  php test.php --courseid=2 --assignid=1
";

// Get command line options
$longoptions = ['courseid:', 'assignid:', 'userid:', 'help'];
$options = getopt('h', $longoptions);

if (isset($options['help']) || isset($options['h'])) {
    echo $usage;
    exit(0);
}

if (empty($options['courseid']) || empty($options['assignid'])) {
    echo "Error: --courseid and --assignid are required\n\n";
    echo $usage;
    exit(1);
}

$courseid = (int)$options['courseid'];
$assignid = (int)$options['assignid'];
$userid = isset($options['userid']) ? (int)$options['userid'] : 2; // Default to admin

echo "\n=== Testing Assignment Hide Unsubmitted Plugin ===\n\n";

// Load course and assignment
$course = $DB->get_record('course', ['id' => $courseid]);
if (!$course) {
    echo "✗ Course not found (ID: $courseid)\n";
    exit(1);
}
echo "✓ Course found: {$course->fullname}\n";

$cm = get_coursemodule_from_instance('assign', $assignid, $courseid);
if (!$cm) {
    echo "✗ Assignment not found (ID: $assignid)\n";
    exit(1);
}
echo "✓ Assignment found: {$cm->name}\n";

$context = context_module::instance($cm->id);

// Check plugin configuration
echo "\n--- Plugin Configuration ---\n";
$enabled = get_config('local_assignhideunsubmitted', 'enabled');
$roleid = get_config('local_assignhideunsubmitted', 'hiderole');
echo "Enabled: " . ($enabled ? 'Yes' : 'No') . "\n";
echo "Role ID: " . ($roleid ?: 'Not configured') . "\n";

if ($roleid) {
    $role = $DB->get_record('role', ['id' => $roleid]);
    echo "Role: {$role->shortname}\n";
}

// Check patch status
$locallib = $CFG->dirroot . '/mod/assign/locallib.php';
$content = file_get_contents($locallib);
$patchapplied = strpos($content, 'local_assignhideunsubmitted_filter_participants') !== false;
echo "Core patch: " . ($patchapplied ? 'Applied' : 'NOT applied') . "\n";

// Get submission statistics
echo "\n--- Submission Statistics ---\n";
$sql = "SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT CASE WHEN s.status = 'submitted' THEN s.userid END) as submitted_users,
            COUNT(DISTINCT CASE WHEN s.status = 'draft' THEN s.userid END) as draft_users,
            COUNT(DISTINCT CASE WHEN s.status IS NULL THEN u.id END) as no_submission
        FROM {user} u
        JOIN {user_enrolments} ue ON u.id = ue.userid
        JOIN {enrol} e ON ue.enrolid = e.id
        LEFT JOIN {assign_submission} s ON u.id = s.userid 
            AND s.assignment = :assignid 
            AND s.latest = 1
        WHERE e.courseid = :courseid
          AND u.deleted = 0
          AND u.suspended = 0";

$stats = $DB->get_record_sql($sql, [
    'assignid' => $assignid,
    'courseid' => $courseid
]);

echo "Total enrolled users: {$stats->total_users}\n";
echo "Submitted: {$stats->submitted_users}\n";
echo "Draft: {$stats->draft_users}\n";
echo "No submission: {$stats->no_submission}\n";

// Test the filtering
echo "\n--- Testing Filter ---\n";

// Create assignment instance
$assign = new assign($context, $cm, $course);

// Test as configured user
$testuser = $DB->get_record('user', ['id' => $userid]);
echo "Testing as user: {$testuser->username}\n";

// Check if user has the configured role
if ($roleid) {
    $roles = get_user_roles($context, $userid, true);
    $hasrole = false;
    foreach ($roles as $r) {
        if ((int)$r->roleid === $roleid) {
            $hasrole = true;
            break;
        }
    }
    echo "User has filter role: " . ($hasrole ? 'Yes' : 'No') . "\n";
}

// Get participants list
$participants = $assign->list_participants(0, false);
$participantcount = count($participants);

echo "\nParticipants shown: $participantcount\n";

// Determine if filtering is working
if ($enabled && $roleid && $patchapplied) {
    if ($participantcount == $stats->submitted_users) {
        echo "✓ Filtering is working correctly!\n";
        echo "  Only submitted users are shown.\n";
    } else if ($participantcount == $stats->total_users) {
        echo "✗ Filtering is NOT working\n";
        echo "  All users are shown instead of only submitted.\n";
    } else {
        echo "⚠ Unexpected result\n";
        echo "  Expected either {$stats->submitted_users} or {$stats->total_users} participants\n";
    }
} else {
    $reasons = [];
    if (!$enabled) $reasons[] = "Plugin is disabled";
    if (!$roleid) $reasons[] = "No role configured";
    if (!$patchapplied) $reasons[] = "Core patch not applied";
    
    echo "ℹ Filtering is not active\n";
    echo "  Reasons: " . implode(", ", $reasons) . "\n";
}

echo "\n=== Test Complete ===\n\n";