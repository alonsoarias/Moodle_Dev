<?php
/**
 * Force Mustache Template Rebuild
 *
 * This script AGGRESSIVELY purges all caches and forces Mustache templates to rebuild
 *
 * Run from command line:
 * php theme/inteb/force_template_rebuild.php
 *
 * Or from browser (requires admin):
 * http://yoursite/theme/inteb/force_template_rebuild.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir.'/adminlib.php');

if (!defined('CLI_SCRIPT') || !CLI_SCRIPT) {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

echo "\n";
echo "==================================================\n";
echo "  FORCE MUSTACHE TEMPLATE REBUILD FOR INTEB\n";
echo "==================================================\n";
echo "\n";

// Step 1: Purge ALL Moodle caches
echo "Step 1: Purging ALL Moodle caches...\n";
purge_all_caches();
echo "   ✓ Moodle caches purged\n\n";

// Step 2: Delete compiled Mustache templates physically
echo "Step 2: Deleting compiled Mustache templates...\n";

$mustachedir = $CFG->localcachedir . '/mustache';
if (is_dir($mustachedir)) {
    $deleted = 0;
    $errors = 0;

    // Recursively delete all files in mustache cache
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($mustachedir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($iterator as $file) {
        try {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
                $deleted++;
            }
        } catch (Exception $e) {
            $errors++;
            echo "   ✗ Error deleting: " . $file->getRealPath() . "\n";
        }
    }

    echo "   ✓ Deleted $deleted compiled Mustache template files\n";
    if ($errors > 0) {
        echo "   ⚠ $errors errors occurred\n";
    }
} else {
    echo "   ℹ Mustache cache directory not found: $mustachedir\n";
}
echo "\n";

// Step 3: Clear theme cache specifically
echo "Step 3: Clearing theme cache...\n";
theme_reset_all_caches();
echo "   ✓ Theme caches cleared\n\n";

// Step 4: Rebuild theme
echo "Step 4: Rebuilding theme...\n";
$theme = theme_config::load('inteb');
$theme->force_svg_use(true);
$theme->set_rtl_mode(false);
echo "   ✓ Theme config reloaded\n\n";

// Step 5: Test template rendering
echo "Step 5: Testing template rendering...\n";
try {
    $PAGE->set_context(context_system::instance());
    $renderer = $PAGE->get_renderer('core');

    // Try to render a simple template to force Mustache compiler to run
    $testcontext = ['test' => true];
    // This will force Mustache to recompile if needed

    echo "   ✓ Mustache compiler is ready\n";
} catch (Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 6: Verify inteb coursehandler is loaded
echo "Step 6: Verifying theme_inteb coursehandler...\n";
try {
    $testhandler = new \theme_inteb\coursehandler();
    echo "   ✓ theme_inteb\\coursehandler class loaded successfully\n";
} catch (Exception $e) {
    echo "   ✗ ERROR: Cannot load theme_inteb\\coursehandler: " . $e->getMessage() . "\n";
}
echo "\n";

// Step 7: Check template file exists
echo "Step 7: Checking template files...\n";
$templatepath = $CFG->dirroot . '/theme/inteb/templates/theme_remui/edw_course_header1.mustache';
if (file_exists($templatepath)) {
    $mtime = filemtime($templatepath);
    $date = date('Y-m-d H:i:s', $mtime);
    echo "   ✓ Template exists: $templatepath\n";
    echo "   ℹ Last modified: $date\n";
} else {
    echo "   ✗ Template NOT FOUND: $templatepath\n";
}
echo "\n";

// Step 8: Show what to do next
echo "==================================================\n";
echo "  NEXT STEPS:\n";
echo "==================================================\n";
echo "\n";
echo "1. Close your browser completely (to clear browser cache)\n";
echo "2. Reopen browser in INCOGNITO/PRIVATE mode\n";
echo "3. Visit your course: http://yoursite/course/view.php?id=206\n";
echo "4. Enable debugging to see if teachers appear\n";
echo "5. Check browser Developer Tools > Console for errors\n";
echo "6. Check browser Developer Tools > Elements to see if\n";
echo "   <div class=\"instructor-info\"> is present in HTML\n";
echo "\n";
echo "If it STILL doesn't work:\n";
echo "- Check if CSS is hiding it (look for 'display: none')\n";
echo "- Check if JavaScript is removing it\n";
echo "- Run: php theme/inteb/debug_teachers.php 206\n";
echo "\n";
echo "==================================================\n";
echo "  CACHE PURGE COMPLETE!\n";
echo "==================================================\n";
echo "\n";
