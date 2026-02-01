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
 * Plugin strings are defined here.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Usage Report';
$string['reportinfotext'] = 'This plugin has been created for another success story of <strong>IngeWeb</strong>. Visit us at <a target="_blank" href="http://ingeweb.co/">IngeWeb - Solutions to succeed on the Internet</a>.';
$string['exclusivedisclaimer'] = 'This plugin is part of, and is to be exclusively used with the Moodle hosting service provided by <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// Plugin status strings
$string['pluginstatus'] = 'Plugin Status';
$string['pluginstatus_enabled'] = 'Plugin Enabled';
$string['pluginstatus_enabled_desc'] = 'The plugin is active and running on an authorized server.';
$string['pluginstatus_unauthorized'] = 'Unauthorized Server';
$string['pluginstatus_unauthorized_desc'] = 'The <strong>Usage Monitor</strong> is an exclusive tool for Moodle platforms managed by <a href="https://ingeweb.co" target="_blank">IngeWeb</a>. If you are interested in this service, <a href="https://ingeweb.co/contacto" target="_blank">contact us</a>.';
$string['hostname_not_authorized'] = 'This server is not authorized to use the Usage Monitor plugin. This plugin is exclusive to IngeWeb hosting services.';

// Dashboard strings
$string['dashboard'] = 'Dashboard';
$string['dashboard_title'] = 'Usage Monitor Dashboard';
$string['diskusage'] = 'Disk usage';
$string['users_today_card'] = 'Daily Users Today';
$string['max_userdaily_for_90_days'] = 'Maximum daily users in the last 90 days';
$string['notcalculatedyet'] = 'Not calculated yet';
$string['lastexecution'] = 'Last daily users calculation run: {$a}';
$string['lastexecutioncalculate'] = 'Last disk space calculation: {$a}';
$string['users_today'] = 'Number of daily users today: {$a}';
$string['date'] = 'Date';
$string['last_calculation'] = 'Last calculation';
$string['usersquantity'] = 'Number of daily users';
$string['disk_usage_distribution'] = 'Disk Usage Distribution';
$string['disk_usage_history'] = 'Disk Usage History (Last 30 Days)';
$string['percentage_used'] = 'Percentage Used';

// Dashboard sections
$string['disk_usage_by_directory'] = 'Disk Usage by Directory';
$string['largest_courses'] = 'Largest Courses';
$string['database'] = 'Database';
$string['files_dir'] = 'Files (filedir)';
$string['cache'] = 'Cache';
$string['others'] = 'Others';
$string['directory'] = 'Directory';
$string['size'] = 'Size';
$string['percentage'] = 'Percentage';
$string['course'] = 'Course';
$string['backup_count'] = 'Backup Count';
$string['topuser'] = 'Top 10 Daily Users';
$string['lastusers'] = 'Daily users of the last 10 days';
$string['usertable'] = 'Top users table';
$string['userchart'] = 'Graph top users';
$string['system_info'] = 'System Information';
$string['moodle_version'] = 'Moodle Version';
$string['total_courses'] = 'Total Courses';
$string['backup_per_course'] = 'Backups per Course';
$string['registered_users'] = 'Registered Users';
$string['active_users'] = 'active users';
$string['suspended_users'] = 'suspended users';
$string['recommendations'] = 'Recommendations';

// Warning levels and indicator labels
$string['warning70'] = 'Warning (70%)';
$string['critical90'] = 'Critical (90%)';
$string['limit100'] = 'Limit (100%)';
$string['percent_of_threshold'] = '% of threshold';

// Recommendation tips
$string['space_saving_tips'] = 'Tips to save disk space:';
$string['tip_backups'] = 'Reduce the number of automatic backups per course (currently: {$a})';
$string['tip_files'] = 'Clean up old unused files using the file cleanup tool';
$string['tip_courses'] = 'Archive or delete old courses that are no longer used';
$string['tip_cache'] = 'Purge the system cache to free up temporary space';
$string['disk_usage_ok'] = 'Disk usage is at an acceptable level. No immediate action required.';
$string['user_count_ok'] = 'User count is at an acceptable level. No immediate action required.';
$string['user_limit_tips'] = 'Tips for managing user limit:';
$string['tip_user_inactive'] = 'Consider cleaning up inactive user accounts that haven\'t logged in for a long time.';
$string['tip_user_limit'] = 'If the number of users is consistently approaching the limit, consider increasing your quota.';

// Task strings
$string['calculatediskusagetask'] = 'Task to calculate the disk usage';
$string['getlastusers'] = 'Task to calculate the top of unique accesses';
$string['getlastusers90days'] = 'Task to get the top users in the last 90 days';
$string['getlastusersconnected'] = 'Task to calculate the number of daily users today';
$string['processdisknotificationtask'] = 'Process disk usage notification task';
$string['processuserlimitnotificationtask'] = 'Process daily user limit notification task';

// Settings strings
$string['mainsettings'] = 'Main settings';
$string['email'] = 'Email for notifications';
$string['configemail'] = 'Email address where you want to send the attendance notifications.';
$string['max_daily_users_threshold'] = 'Limit Users';
$string['configmax_daily_users_threshold'] = 'Number of Limit Users.';
$string['disk_quota'] = 'Disk Quota';
$string['configdisk_quota'] = 'Disk Quota in gigabytes';
$string['notificationsettings'] = 'Notification settings';
$string['notificationsettingsinfo'] = 'Configure when and how notifications are sent.';
$string['disk_warning_level'] = 'Disk warning level';
$string['configdisk_warning_level'] = 'Percentage of disk usage that triggers warnings.';
$string['users_warning_level'] = 'Users warning level';
$string['configusers_warning_level'] = 'Percentage of user limit that triggers warnings.';
$string['pathtodu'] = 'Path to du command';
$string['configpathtodu'] = 'Configure the path to the du command (disk usage). This is necessary for calculating disk usage. <strong>This setting is reflected in Moodle system paths</strong>)';
$string['pathtodurecommendation'] = 'We recommend that you review and configure the path to \'du\' in the Moodle System Paths. You can find this setting under Site administration > Server > System Paths. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Click here to go to System Paths</a>.';
$string['pathtodunote'] = 'Note: The path to \'du\' will be automatically detected only if this plugin is on a Linux system and if the location of \'du\' can be successfully detected.';
$string['activateshellexec'] = 'The shell_exec function is not active on this server. To use the auto-detection of the path to du, you need to enable shell_exec in your server configuration.';

// Email notification strings
$string['subjectemail1'] = 'Daily User Limit Exceeded on Platform:';
$string['subjectemail2'] = 'Disk Space Alert on Platform:';

// API documentation strings
$string['api_documentation'] = 'API Documentation';
$string['get_usage_data'] = 'Get usage data';
$string['get_usage_data_desc'] = 'Retrieves precalculated usage data for disk and users with minimal overhead.';
$string['set_usage_thresholds'] = 'Set usage thresholds';
$string['set_usage_thresholds_desc'] = 'Updates the configured thresholds for users and disk space.';
$string['user_threshold_updated'] = 'User threshold updated successfully.';
$string['disk_threshold_updated'] = 'Disk threshold updated successfully.';
$string['error_user_threshold_negative'] = 'User threshold must be greater than 0.';
$string['error_disk_threshold_negative'] = 'Disk threshold must be greater than 0.';
$string['error_no_thresholds_provided'] = 'No thresholds provided to update.';

// Plugin status strings
$string['plugin_disabled_hostname'] = '<div class="text-center">
<h4 class="mb-3 text-danger"><i class="fa fa-exclamation-triangle"></i> Unauthorized Server</h4>
<p class="mb-0">This plugin is part of and is for the exclusive use of the Moodle hosting service provided by <a href="https://ingeweb.co" target="_blank"><strong>IngeWeb</strong></a>.</p>
</div>';
$string['tasks_scheduled_install'] = 'Scheduled tasks have been configured to run immediately. The dashboard will display updated data after the next cron execution.';
$string['tasks_executing'] = 'Executing tasks to obtain initial dashboard data...';
$string['tasks_executed_success'] = 'All tasks executed successfully. The dashboard now displays updated data.';
$string['tasks_executed_partial'] = '{$a} tasks were executed. Some tasks may have failed, but the dashboard should display partial data.';

// API response field descriptions
$string['server_hostname'] = 'Server hostname';
$string['site_name'] = 'Site name';
$string['site_shortname'] = 'Site short name';
$string['moodle_release'] = 'Human-readable Moodle version';
$string['course_count'] = 'Number of courses';
$string['user_count'] = 'Number of users';
$string['backup_auto_max_kept'] = 'Number of automatic backups kept';
$string['total_bytes'] = 'Total disk usage in bytes';
$string['total_readable'] = 'Human-readable disk usage';
$string['quota_bytes'] = 'Disk quota in bytes';
$string['quota_readable'] = 'Human-readable disk quota';
$string['disk_percentage'] = 'Disk usage percentage';
$string['database_bytes'] = 'Database size in bytes';
$string['database_readable'] = 'Human-readable database size';
$string['database_percentage'] = 'Database size percentage';
$string['filedir_bytes'] = 'File directory size in bytes';
$string['filedir_readable'] = 'Human-readable file directory size';
$string['filedir_percentage'] = 'File directory size percentage';
$string['cache_bytes'] = 'Cache size in bytes';
$string['cache_readable'] = 'Human-readable cache size';
$string['cache_percentage'] = 'Cache size percentage';
$string['backup_bytes'] = 'Backup size in bytes';
$string['backup_readable'] = 'Human-readable backup size';
$string['backup_percentage'] = 'Backup size percentage';
$string['others_bytes'] = 'Other directories size in bytes';
$string['others_readable'] = 'Human-readable other directories size';
$string['others_percentage'] = 'Other directories size percentage';
$string['user_threshold'] = 'User threshold';
$string['user_percentage'] = 'User usage percentage';
$string['course_id'] = 'Course ID';
$string['course_fullname'] = 'Course full name';
$string['course_shortname'] = 'Course short name';
$string['course_size_bytes'] = 'Course size in bytes';
$string['course_size_readable'] = 'Human-readable course size';
$string['course_backup_size_bytes'] = 'Course backup size in bytes';
$string['course_backup_size_readable'] = 'Human-readable course backup size';
$string['course_percentage'] = 'Course size percentage';
$string['course_backup_count'] = 'Course backup count';
$string['disk_calculation_timestamp'] = 'Disk calculation timestamp';
$string['users_calculation_timestamp'] = 'Users calculation timestamp';

// Notification history API strings
$string['notification_type'] = 'Notification type (disk, users, or all)';
$string['notification_limit'] = 'Maximum number of records to return';
$string['notification_offset'] = 'Offset for pagination';
$string['notification_total'] = 'Total number of records available';
$string['notification_limit_value'] = 'Requested maximum number of records';
$string['notification_offset_value'] = 'Requested offset';
$string['notification_id'] = 'Notification ID';
$string['notification_type_value'] = 'Notification type (disk or users)';
$string['notification_percentage'] = 'Usage percentage';
$string['notification_value'] = 'Human-readable value';
$string['notification_value_raw'] = 'Value in bytes or user count';
$string['notification_threshold'] = 'Human-readable threshold';
$string['notification_threshold_raw'] = 'Threshold in bytes or user count';
$string['notification_timecreated'] = 'Creation timestamp';
$string['notification_timereadable'] = 'Human-readable date and time';

// Projections and growth rates
$string['api_projections_title'] = 'Growth projections';
$string['api_projections_desc'] = 'Growth projection data and estimated days to reach thresholds';
$string['api_monthly_growth_rate'] = 'Monthly growth rate';
$string['api_projection_days'] = 'Days to reach threshold';
$string['growth_rate_disk'] = 'Disk growth rate';
$string['growth_rate_disk_desc'] = 'Monthly growth rate of disk usage in percentage';
$string['growth_rate_users'] = 'User growth rate';
$string['growth_rate_users_desc'] = 'Monthly growth rate of the number of users in percentage';
$string['days_to_threshold_disk'] = 'Days until disk threshold';
$string['days_to_threshold_disk_desc'] = 'Projected days until reaching the disk warning threshold';
$string['days_to_threshold_users'] = 'Days until user threshold';
$string['days_to_threshold_users_desc'] = 'Projected days until reaching the user warning threshold';

// Email templates
$string['messagehtml_userlimit'] = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Limit Notification - {$a->sitename}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #cccccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dddddd;
            font-size: 13px;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary-table td:first-child {
            width: 40%;
            font-weight: bold;
        }
        .alert-box {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .alert-box.critical {
            border-left: 4px solid #cc0000;
        }
        .alert-box.warning {
            border-left: 4px solid #ff9900;
        }
        .link-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #333333;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #cccccc;
            font-size: 11px;
            color: #666666;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Daily User Limit Notification</h1>
            <p>Platform: {$a->sitename} | Date: {$a->lastday}</p>
        </div>

        <div class="alert-box critical">
            <strong>Status:</strong> The daily user limit has been exceeded. Current usage is at {$a->percentaje}% of the configured threshold.
        </div>

        <div class="section">
            <div class="section-title">Summary</div>
            <table class="summary-table">
                <tr>
                    <td>Active Users Today</td>
                    <td>{$a->numberofusers}</td>
                </tr>
                <tr>
                    <td>Configured Limit</td>
                    <td>{$a->threshold} users</td>
                </tr>
                <tr>
                    <td>Usage Percentage</td>
                    <td>{$a->percentaje}%</td>
                </tr>
                <tr>
                    <td>Users Over Limit</td>
                    <td>{$a->excess_users}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Platform Details</div>
            <table class="summary-table">
                <tr>
                    <td>Moodle Version</td>
                    <td>{$a->moodle_release}</td>
                </tr>
                <tr>
                    <td>Total Courses</td>
                    <td>{$a->courses_count}</td>
                </tr>
                <tr>
                    <td>Disk Usage</td>
                    <td>{$a->diskusage} / {$a->quotadisk} ({$a->disk_percent}%)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Recent User History</div>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Active Users</th>
                        <th>% of Limit</th>
                    </tr>
                </thead>
                <tbody>
                    {$a->historical_data_rows}
                </tbody>
            </table>
        </div>

        <div class="section">
            <p>To view detailed statistics and manage your platform, access the dashboard:</p>
            <a href="{$a->referer}" class="link-button">Access Dashboard</a>
        </div>

        <div class="footer">
            <p>This is an automated notification generated by the Usage Monitor plugin.</p>
            <p>Note: Only unique users who authenticated on the indicated date are counted.</p>
            <p>Platform URL: <a href="{$a->siteurl}">{$a->siteurl}</a></p>
        </div>
    </div>
</body>
</html>';

$string['messagehtml_diskusage'] = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disk Space Notification - {$a->sitename}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #cccccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dddddd;
            font-size: 13px;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary-table td:first-child {
            width: 40%;
            font-weight: bold;
        }
        .alert-box {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .alert-box.critical {
            border-left: 4px solid #cc0000;
        }
        .alert-box.warning {
            border-left: 4px solid #ff9900;
        }
        .recommendations {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .recommendations ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin-bottom: 5px;
        }
        .link-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #333333;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #cccccc;
            font-size: 11px;
            color: #666666;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Disk Space Notification</h1>
            <p>Platform: {$a->sitename} | Date: {$a->lastday}</p>
        </div>

        <div class="alert-box {$a->warning_level_class}">
            <strong>Status:</strong> Disk usage has reached {$a->percentage}% of the assigned quota.
        </div>

        <div class="section">
            <div class="section-title">Disk Usage Summary</div>
            <table class="summary-table">
                <tr>
                    <td>Used Space</td>
                    <td>{$a->diskusage}</td>
                </tr>
                <tr>
                    <td>Assigned Quota</td>
                    <td>{$a->quotadisk}</td>
                </tr>
                <tr>
                    <td>Available Space</td>
                    <td>{$a->available_space} ({$a->available_percent}%)</td>
                </tr>
                <tr>
                    <td>Usage Percentage</td>
                    <td>{$a->percentage}%</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Storage Distribution</div>
            <table>
                <thead>
                    <tr>
                        <th>Component</th>
                        <th>Size</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Database</td>
                        <td>{$a->databasesize}</td>
                        <td>{$a->db_percent}%</td>
                    </tr>
                    <tr>
                        <td>Files (filedir)</td>
                        <td>{$a->filedir_size}</td>
                        <td>{$a->filedir_percent}%</td>
                    </tr>
                    <tr>
                        <td>Cache</td>
                        <td>{$a->cache_size}</td>
                        <td>{$a->cache_percent}%</td>
                    </tr>
                    <tr>
                        <td>Other</td>
                        <td>{$a->other_size}</td>
                        <td>{$a->other_percent}%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Platform Information</div>
            <table class="summary-table">
                <tr>
                    <td>Moodle Version</td>
                    <td>{$a->moodle_release}</td>
                </tr>
                <tr>
                    <td>Total Courses</td>
                    <td>{$a->coursescount}</td>
                </tr>
                <tr>
                    <td>Backups per Course</td>
                    <td>{$a->backupcount}</td>
                </tr>
                <tr>
                    <td>Active Users</td>
                    <td>{$a->numberofusers} / {$a->threshold} ({$a->user_percent}%)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Largest Courses</div>
            <table>
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Size</th>
                        <th>% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$a->top_courses_rows}
                </tbody>
            </table>
        </div>

        <div class="recommendations">
            <strong>Recommendations:</strong>
            <ul>
                <li>Reduce automatic backups per course (currently: {$a->backupcount})</li>
                <li>Remove unused files using the file cleanup tool</li>
                <li>Review and clean the largest courses listed above</li>
                <li>Purge system cache to free temporary space</li>
            </ul>
        </div>

        <div class="section">
            <p>To view detailed statistics and manage your platform, access the dashboard:</p>
            <a href="{$a->referer}" class="link-button">Access Dashboard</a>
        </div>

        <div class="footer">
            <p>This is an automated notification generated by the Usage Monitor plugin.</p>
            <p>For technical assistance, please contact your hosting administrator.</p>
            <p>Platform URL: <a href="{$a->siteurl}">{$a->siteurl}</a></p>
        </div>
    </div>
</body>
</html>';