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
 * Settings for local_assignhideunsubmitted
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_assignhideunsubmitted',
        get_string('pluginname', 'local_assignhideunsubmitted'));
    
    // Check patch status
    $patchstatus = 'notapplied';
    $locallib = $CFG->dirroot . '/mod/assign/locallib.php';
    if (file_exists($locallib)) {
        $content = file_get_contents($locallib);
        if (strpos($content, 'local_assignhideunsubmitted_filter_participants') !== false) {
            $patchstatus = 'applied';
        }
    }
    
    // Show patch status
    if ($patchstatus == 'applied') {
        $statusmsg = '<div class="alert alert-success">' . get_string('patchapplied', 'local_assignhideunsubmitted') . '</div>';
    } else {
        $statusmsg = '<div class="alert alert-warning">' . get_string('patchnotapplied', 'local_assignhideunsubmitted') . 
                     '<br><br>To apply patch, run:<br><code>php local/assignhideunsubmitted/install_patch.php</code></div>';
    }
    
    $settings->add(new admin_setting_heading('local_assignhideunsubmitted/patchstatus',
        get_string('patchstatus', 'local_assignhideunsubmitted'),
        $statusmsg));
    
    // Enable/disable setting
    $settings->add(new admin_setting_configcheckbox(
        'local_assignhideunsubmitted/enabled',
        get_string('enabled', 'local_assignhideunsubmitted'),
        get_string('enabled_desc', 'local_assignhideunsubmitted'),
        1
    ));
    
    // Get all system roles
    $roles = role_get_names(\context_system::instance());
    $options = [0 => get_string('none')];
    foreach ($roles as $role) {
        $options[$role->id] = $role->localname;
    }
    
    // Role selector
    $settings->add(new admin_setting_configselect(
        'local_assignhideunsubmitted/hiderole',
        get_string('hiderole', 'local_assignhideunsubmitted'),
        get_string('hiderole_desc', 'local_assignhideunsubmitted'),
        0,
        $options
    ));
    
    $ADMIN->add('localplugins', $settings);
}