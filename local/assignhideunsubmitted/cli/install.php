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
 * Installation script
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');

echo "\n";
echo "=====================================\n";
echo " Assignment Hide Unsubmitted Plugin\n";
echo " Installation Script\n";
echo "=====================================\n\n";

// Step 1: Check requirements
echo "1. Checking requirements...\n";

// Check Moodle version
if ($CFG->version < 2022041900) {
    echo "   ✗ ERROR: Moodle 4.0 or higher is required\n";
    exit(1);
}
echo "   ✓ Moodle version: OK\n";

// Check PHP version
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
    echo "   ✗ ERROR: PHP 7.4 or higher is required\n";
    exit(1);
}
echo "   ✓ PHP version: OK\n";

// Step 2: Check if plugin is installed
echo "\n2. Checking plugin status...\n";
$dbman = $DB->get_manager();
$table = new xmldb_table('config_plugins');
if ($dbman->table_exists($table)) {
    $installed = $DB->get_record('config_plugins', [
        'plugin' => 'local_assignhideunsubmitted',
        'name' => 'version'
    ]);
    if ($installed) {
        echo "   ✓ Plugin is installed (version: {$installed->value})\n";
    } else {
        echo "   ℹ Plugin is not yet installed\n";
    }
}

// Step 3: Configure plugin
echo "\n3. Configuring plugin...\n";

// Enable plugin
set_config('enabled', 1, 'local_assignhideunsubmitted');
echo "   ✓ Plugin enabled\n";

// Set default role (teacher)
$teacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($teacherrole) {
    set_config('hiderole', $teacherrole->id, 'local_assignhideunsubmitted');
    echo "   ✓ Filter role set to: Editing Teacher (ID: {$teacherrole->id})\n";
} else {
    echo "   ⚠ Warning: Could not find editing teacher role\n";
}

// Step 4: Check patch status
echo "\n4. Checking core patch status...\n";
$locallib = $CFG->dirroot . '/mod/assign/locallib.php';
$content = file_get_contents($locallib);

if (strpos($content, 'local_assignhideunsubmitted_filter_participants') !== false) {
    echo "   ✓ Core patch is already applied\n";
} else {
    echo "   ℹ Core patch is NOT applied\n";
    echo "\n";
    echo "   The plugin requires a small modification to Moodle core.\n";
    echo "   Do you want to apply the patch now? (y/n): ";
    
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim($line) === 'y' || trim($line) === 'Y') {
        echo "\n   Applying patch...\n";
        require(__DIR__ . '/../install_patch.php');
    } else {
        echo "\n   ⚠ Patch not applied. The plugin may not work correctly.\n";
        echo "   You can apply it later by running:\n";
        echo "   php local/assignhideunsubmitted/install_patch.php\n";
    }
}

// Step 5: Clear caches
echo "\n5. Clearing caches...\n";
purge_all_caches();
echo "   ✓ All caches cleared\n";

// Done
echo "\n";
echo "=====================================\n";
echo " Installation Complete!\n";
echo "=====================================\n\n";
echo "Next steps:\n";
echo "1. Visit your Moodle site as admin\n";
echo "2. Go to Site administration > Notifications\n";
echo "3. Complete any pending upgrades\n";
echo "4. Test the plugin with an assignment\n";
echo "\n";
echo "To verify the installation, run:\n";
echo "  php local/assignhideunsubmitted/cli/test.php\n";
echo "\n";