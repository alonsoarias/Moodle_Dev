<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Usage Monitor Report main dashboard.
 *
 * @package    report_usage_monitor
 * @copyright  2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

// Setup admin page.
admin_externalpage_setup('report_usage_monitor', '', null, '', ['pagelayout' => 'admin']);

// Sync scheduled tasks state based on hostname validity.
// This auto-corrects manually enabled tasks on unauthorized servers.
$hostvalid = \report_usage_monitor\hostname_validator::sync_scheduled_tasks();

// Check if plugin is enabled (hostname validation).
if (!$hostvalid) {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('dashboard_title', 'report_usage_monitor'));
    echo $OUTPUT->notification(get_string('plugin_disabled_hostname', 'report_usage_monitor'), 'error');
    // Show credits when plugin is disabled.
    echo html_writer::tag('div', get_string('reportinfotext', 'report_usage_monitor'), ['class' => 'mt-4 text-center text-muted small']);
    echo $OUTPUT->footer();
    die();
}

// Get the renderer and renderable.
$output = $PAGE->get_renderer('report_usage_monitor');
$dashboard = new \report_usage_monitor\output\dashboard();

// Output the page.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('dashboard_title', 'report_usage_monitor'));
echo $output->render_dashboard($dashboard);
echo $OUTPUT->footer();
