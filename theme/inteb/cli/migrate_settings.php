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
 * CLI script to migrate settings from old format to new prefixed format.
 *
 * @package    theme_inteb
 * @copyright  2025 IngeWeb <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

// Corregimos la ruta para que sean 3 niveles arriba (theme/inteb/cli -> MOODLE_ROOT)
require(dirname(dirname(dirname(__DIR__))) . '/config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/theme/inteb/lib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'mode' => 'check', // 'check' or 'migrate'
    ),
    array(
        'h' => 'help',
        'm' => 'mode',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Command line script to migrate theme settings to new prefixed format.

Options:
-h, --help            Print this help.
-m, --mode            Mode to run (check or migrate).

Examples:
\$ php theme/inteb/cli/migrate_settings.php --mode=check
\$ php theme/inteb/cli/migrate_settings.php --mode=migrate
";
    echo $help;
    exit(0);
}

// Define the mapping of old settings to new prefixed settings
$oldtonew = [
    'generalnoticemode' => 'ib_generalnoticemode',
    'generalnotice' => 'ib_generalnotice',
    'enable_chat' => 'ib_enable_chat',
    'tawkto_embed_url' => 'ib_tawkto_embed_url',
    'copypaste_prevention' => 'ib_copypaste_prevention',
    'copypaste_roles' => 'ib_copypaste_roles',
    'login_numberofslides' => 'ib_login_numberofslides',
    'login_carouselinterval' => 'ib_login_carouselinterval',
    'showpersonalareaheader' => 'ib_showpersonalareaheader',
    'personalareaheader' => 'ib_personalareaheader',
    'showmycoursesheader' => 'ib_showmycoursesheader',
    'mycoursesheader' => 'ib_mycoursesheader',
    'hidefrontpagesections' => 'ib_hidefrontpagesections',
    'hidefootersections' => 'ib_hidefootersections',
    'abouttitle' => 'ib_abouttitle',
    'abouttext' => 'ib_abouttext'
];

// Add slide-specific settings
for ($i = 1; $i <= 10; $i++) {
    $oldtonew["login_slidetitle$i"] = "ib_login_slidetitle$i";
    $oldtonew["login_slideurl$i"] = "ib_login_slideurl$i";
}

// First check which old settings exist and what would be migrated
$existingSettings = [];
$toMigrateCount = 0;

echo "Checking theme_inteb settings...\n";
foreach ($oldtonew as $old => $new) {
    $value = get_config('theme_inteb', $old);
    $newvalue = get_config('theme_inteb', $new);
    
    if ($value !== false) {
        $existingSettings[$old] = $value;
        echo "Found old setting: $old";
        
        if ($newvalue !== false) {
            echo " (new setting already exists)\n";
        } else {
            echo " (will be migrated to $new)\n";
            $toMigrateCount++;
        }
    }
}

echo "\nFound " . count($existingSettings) . " old settings, of which $toMigrateCount need migration.\n";

// If in migrate mode, perform the migration
if ($options['mode'] === 'migrate' && $toMigrateCount > 0) {
    echo "\nPerforming migration...\n";
    
    foreach ($oldtonew as $old => $new) {
        $value = get_config('theme_inteb', $old);
        $newvalue = get_config('theme_inteb', $new);
        
        if ($value !== false && $newvalue === false) {
            echo "Migrating $old to $new\n";
            set_config($new, $value, 'theme_inteb');
        }
    }
    
    echo "\nMigration complete. $toMigrateCount settings have been migrated.\n";
    
    // Special handling for file areas (like slideimages)
    echo "\nNote: File settings like slideimages need to be manually migrated by re-uploading them in the theme settings.\n";
} else if ($options['mode'] === 'migrate' && $toMigrateCount === 0) {
    echo "\nNo settings need migration.\n";
} else {
    echo "\nRun with --mode=migrate to perform the actual migration.\n";
}

exit(0);