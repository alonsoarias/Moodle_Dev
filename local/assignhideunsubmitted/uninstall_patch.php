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
 * Uninstall patch - restore original file
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');

echo "\n=== Uninstalling Assignment Hide Unsubmitted Patch ===\n\n";

$originalfile = $CFG->dirroot . '/mod/assign/locallib.php';
$backupfile = $CFG->dirroot . '/mod/assign/locallib.php.backup_hideunsubmitted';

if (!file_exists($backupfile)) {
    echo "✗ Backup file not found. Cannot restore.\n";
    exit(1);
}

// Restore from backup
if (copy($backupfile, $originalfile)) {
    echo "✓ Original file restored from backup\n";
    
    // Remove backup
    unlink($backupfile);
    echo "✓ Backup file removed\n";
    
    echo "\n";
    echo "IMPORTANT: Clear Moodle caches:\n";
    echo "  php admin/cli/purge_caches.php\n";
} else {
    echo "✗ Failed to restore from backup\n";
    exit(1);
}

echo "\n=== Patch Removal Complete ===\n";