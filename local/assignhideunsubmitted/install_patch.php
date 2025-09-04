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
 * Install patch for core modification
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');

echo "\n=== Installing Assignment Hide Unsubmitted Patch ===\n\n";

$originalfile = $CFG->dirroot . '/mod/assign/locallib.php';
$backupfile = $CFG->dirroot . '/mod/assign/locallib.php.backup_hideunsubmitted';

// Create backup
if (!file_exists($backupfile)) {
    if (copy($originalfile, $backupfile)) {
        echo "✓ Backup created: $backupfile\n";
    } else {
        echo "✗ Failed to create backup\n";
        exit(1);
    }
} else {
    echo "ℹ Backup already exists\n";
}

// Read the file
$content = file_get_contents($originalfile);

// Check if patch is already applied
if (strpos($content, 'local_assignhideunsubmitted_filter_participants') !== false) {
    echo "ℹ Patch is already applied\n";
    exit(0);
}

// Find the list_participants method and add our hook
$search = 'public function list_participants($currentgroup, $idsonly, $tablesort = false) {';
$replace = 'public function list_participants($currentgroup, $idsonly, $tablesort = false) {
        // BEGIN HOOK: local_assignhideunsubmitted
        global $CFG;
        if (file_exists($CFG->dirroot . \'/local/assignhideunsubmitted/hook.php\')) {
            require_once($CFG->dirroot . \'/local/assignhideunsubmitted/hook.php\');
            if (function_exists(\'local_assignhideunsubmitted_filter_participants\')) {
                $filtered = local_assignhideunsubmitted_filter_participants($this, $currentgroup, $idsonly, $tablesort);
                if ($filtered !== null) {
                    return $filtered;
                }
            }
        }
        // END HOOK: local_assignhideunsubmitted
        ';

$newcontent = str_replace($search, $replace, $content);

if ($newcontent === $content) {
    echo "✗ Could not find the list_participants method to patch\n";
    echo "  The file structure may have changed.\n";
    exit(1);
}

// Write the patched file
if (file_put_contents($originalfile, $newcontent)) {
    echo "✓ Patch applied successfully\n";
    echo "\n";
    echo "IMPORTANT: Clear Moodle caches for changes to take effect:\n";
    echo "  php admin/cli/purge_caches.php\n";
    echo "\nTo remove the patch later, run:\n";
    echo "  php local/assignhideunsubmitted/uninstall_patch.php\n";
} else {
    echo "✗ Failed to write patched file\n";
    exit(1);
}

echo "\n=== Patch Installation Complete ===\n";