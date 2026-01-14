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
 * Admin settings for local_forum_delete_replies
 *
 * @package    local_forum_delete_replies
 * @copyright  2025 Your Organization
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a settings page for the plugin.
    $settings = new admin_settingpage(
        'local_forum_delete_replies',
        get_string('pluginname', 'local_forum_delete_replies')
    );

    // Add a link/description to the site-wide deletion page.
    $settings->add(new admin_setting_heading(
        'local_forum_delete_replies/heading',
        get_string('sitewidedelete', 'local_forum_delete_replies'),
        html_writer::link(
            new moodle_url('/local/forum_delete_replies/admin.php'),
            get_string('sitewidedelete', 'local_forum_delete_replies'),
            ['class' => 'btn btn-danger']
        ) . '<br><br>' .
        get_string('sitewidedeletewarning', 'local_forum_delete_replies')
    ));

    // Add to local plugins category.
    $ADMIN->add('localplugins', $settings);

    // Also add an external page for direct access.
    $ADMIN->add(
        'localplugins',
        new admin_externalpage(
            'local_forum_delete_replies_admin',
            get_string('sitewidedelete', 'local_forum_delete_replies'),
            new moodle_url('/local/forum_delete_replies/admin.php'),
            'moodle/site:config'
        )
    );
}
