<?php
/**
 * Comprehensive template verification script
 *
 * This script will:
 * 1. Show which template file should be used
 * 2. Purge ALL caches aggressively
 * 3. Show the compiled template path
 * 4. Verify template file timestamps
 *
 * Run from browser: http://yoursite/theme/inteb/verify_template.php?courseid=X
 * Or CLI: php theme/inteb/verify_template.php [courseid]
 */

define('CLI_SCRIPT', false);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

// Get course ID
if (defined('CLI_SCRIPT') && CLI_SCRIPT) {
    $courseid = isset($argv[1]) ? (int)$argv[1] : 0;
} else {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
    $courseid = optional_param('courseid', 0, PARAM_INT);
}

if (!$courseid) {
    echo "<h1>Template Verification Script</h1>";
    echo "<p>Usage: verify_template.php?courseid=X</p>";
    exit(1);
}

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

echo "<html><head><style>
body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
h1, h2 { color: #4ec9b0; border-bottom: 2px solid #4ec9b0; }
.success { color: #4ec9b0; }
.error { color: #f48771; }
.info { color: #ce9178; }
.step { background: #252526; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
pre { background: #1e1e1e; border: 1px solid #3e3e42; padding: 10px; overflow-x: auto; }
</style></head><body>";

echo "<h1>🔍 Template Verification Script</h1>";
echo "<p class='info'>Course ID: {$course->id} - {$course->fullname}</p>";

// Step 1: Check template file existence
echo "<div class='step'>";
echo "<h2>Step 1: Template File Check</h2>";

$inteb_template = $CFG->dirroot . '/theme/inteb/templates/theme_remui/edw_course_header1.mustache';
$remui_template = $CFG->dirroot . '/theme/remui/templates/edw_course_header1.mustache';

if (file_exists($inteb_template)) {
    $mtime = date('Y-m-d H:i:s', filemtime($inteb_template));
    echo "<p class='success'>✓ INTEB template exists: {$inteb_template}</p>";
    echo "<p class='info'>  Last modified: {$mtime}</p>";

    // Check for our debug marker
    $content = file_get_contents($inteb_template);
    if (strpos($content, 'INTEB TEMPLATE IS ACTIVE') !== false) {
        echo "<p class='success'>✓ Debug marker found in template!</p>";
    } else {
        echo "<p class='error'>✗ Debug marker NOT found in template!</p>";
    }
} else {
    echo "<p class='error'>✗ INTEB template NOT found!</p>";
}

if (file_exists($remui_template)) {
    echo "<p class='info'>  Parent template exists: {$remui_template}</p>";
}
echo "</div>";

// Step 2: Check configuration
echo "<div class='step'>";
echo "<h2>Step 2: Configuration Check</h2>";
$design = get_config('theme_remui', 'courseheaderdesign');
echo "<p>courseheaderdesign = <strong>{$design}</strong></p>";
echo "<p>Expected template: <strong>theme_remui/edw_course_header{$design}</strong></p>";
echo "</div>";

// Step 3: Purge caches
echo "<div class='step'>";
echo "<h2>Step 3: Purging ALL Caches</h2>";

echo "<p>Purging Moodle caches...</p>";
purge_all_caches();
echo "<p class='success'>✓ purge_all_caches() complete</p>";

echo "<p>Purging theme caches...</p>";
theme_reset_all_caches();
echo "<p class='success'>✓ theme_reset_all_caches() complete</p>";

// Physically delete compiled Mustache templates
echo "<p>Deleting compiled Mustache templates...</p>";
$mustachedir = $CFG->localcachedir . '/mustache';
if (is_dir($mustachedir)) {
    $deleted = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($mustachedir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $file) {
        if ($file->isDir()) {
            @rmdir($file->getRealPath());
        } else {
            @unlink($file->getRealPath());
            $deleted++;
        }
    }
    echo "<p class='success'>✓ Deleted {$deleted} compiled template files</p>";
    echo "<p class='info'>  Directory: {$mustachedir}</p>";
} else {
    echo "<p class='info'>  No Mustache cache directory found</p>";
}
echo "</div>";

// Step 4: Test coursehandler
echo "<div class='step'>";
echo "<h2>Step 4: Testing Coursehandler</h2>";

try {
    require_once($CFG->dirroot . '/theme/inteb/classes/coursehandler.php');
    $coursehandler = new \theme_inteb\coursehandler();
    $context = $coursehandler->get_enrolled_teachers_context($course, true);

    echo "<p class='success'>✓ Coursehandler loaded successfully</p>";

    if (isset($context['hasteachers']) && $context['hasteachers']) {
        echo "<p class='success'>✓ hasteachers = TRUE</p>";
    } else {
        echo "<p class='error'>✗ hasteachers = FALSE or not set</p>";
    }

    if (isset($context['instructors']) && is_array($context['instructors'])) {
        $count = count($context['instructors']);
        echo "<p class='success'>✓ Found {$count} instructor(s):</p>";
        echo "<ul>";
        foreach ($context['instructors'] as $instructor) {
            echo "<li>{$instructor['name']} (ID: {$instructor['id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='error'>✗ No instructors array in context!</p>";
    }

    echo "<p>Full context data:</p>";
    echo "<pre>" . htmlspecialchars(print_r($context, true)) . "</pre>";

} catch (Exception $e) {
    echo "<p class='error'>✗ ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// Step 5: Instructions
echo "<div class='step'>";
echo "<h2>Step 5: Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Clear your browser cache:</strong>";
echo "  <ul>";
echo "    <li>Chrome/Firefox: Press <kbd>Ctrl+Shift+Delete</kbd> (Windows) or <kbd>Cmd+Shift+Delete</kbd> (Mac)</li>";
echo "    <li>Or try a hard refresh: <kbd>Ctrl+Shift+R</kbd> (Windows) or <kbd>Cmd+Shift+R</kbd> (Mac)</li>";
echo "    <li>Or open the page in an incognito/private window</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Visit the course page:</strong> <a href='{$CFG->wwwroot}/course/view.php?id={$courseid}' target='_blank' style='color: #4ec9b0;'>Click here to open course {$courseid}</a></li>";
echo "<li><strong>Look for these debug markers:</strong>";
echo "  <ul>";
echo "    <li>🌈 A <strong>RAINBOW GRADIENT BANNER</strong> at the very top saying \"DEBUG: INTEB TEMPLATE IS ACTIVE\"</li>";
echo "    <li>🟢 A <strong>GREEN-ON-BLACK DEBUG BOX</strong> showing the teachers section status</li>";
echo "  </ul>";
echo "</li>";
echo "<li><strong>Report back:</strong>";
echo "  <ul>";
echo "    <li>Do you see the rainbow banner? (If NO: template not loading)</li>";
echo "    <li>Do you see the green debug box? (If YES: check what it says)</li>";
echo "    <li>Do you see the teacher displayed below the debug box?</li>";
echo "  </ul>";
echo "</li>";
echo "</ol>";
echo "</div>";

echo "<div class='step'>";
echo "<h2>✅ Verification Complete</h2>";
echo "<p>All caches have been purged. Template should be fresh on next page load.</p>";
echo "<p><strong>Don't forget to hard-refresh your browser!</strong></p>";
echo "</div>";

echo "</body></html>";
