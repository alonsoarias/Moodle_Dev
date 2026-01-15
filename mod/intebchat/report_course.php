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
 * Course-level usage report for INTEB Chat
 *
 * Accessible from Course Reports navigation.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->dirroot . '/mod/intebchat/lib.php');

$courseid = required_param('courseid', PARAM_INT);
$period = optional_param('period', 'month', PARAM_ALPHA);
$instanceid = optional_param('instanceid', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);
require_capability('mod/intebchat:viewanalytics', $context);

$PAGE->set_url(new moodle_url('/mod/intebchat/report_course.php', ['courseid' => $courseid, 'period' => $period]));
$PAGE->set_title(get_string('intebchatreport', 'mod_intebchat'));
$PAGE->set_heading($course->fullname);
$PAGE->set_pagelayout('report');
$PAGE->set_context($context);

// Initialize AMD module for AJAX
$PAGE->requires->js_call_amd('mod_intebchat/report', 'init', [[
    'type' => 'course',
    'courseid' => $courseid,
    'period' => $period,
    'instanceid' => $instanceid,
    'page' => $page,
    'perpage' => $perpage,
]]);

// Calculate date range based on period
$now = time();
switch ($period) {
    case 'day':
        $starttime = strtotime('today');
        break;
    case 'week':
        $starttime = strtotime('-7 days');
        break;
    case 'month':
        $starttime = strtotime('-30 days');
        break;
    case 'all':
    default:
        $starttime = 0;
        break;
}

// Get all intebchat instances in the course
$instances = $DB->get_records('intebchat', ['course' => $courseid], 'name ASC');

if (empty($instances)) {
    echo $OUTPUT->header();
    echo $OUTPUT->notification(get_string('noinstancesincourse', 'mod_intebchat'), 'info');
    echo $OUTPUT->footer();
    exit;
}

// Get statistics for the course
$stats = get_course_statistics($courseid, $starttime, $instanceid);

// Handle CSV export
if ($download === 'csv') {
    export_course_report_csv($stats, $course, $starttime, $instanceid);
    exit;
}

echo $OUTPUT->header();

echo html_writer::tag('h2', get_string('intebchatreport', 'mod_intebchat'));

// Filters
echo html_writer::start_div('filters-row d-flex align-items-center mb-3 flex-wrap gap-3');

// Instance filter
echo html_writer::start_div('filter-group mr-3');
echo html_writer::tag('label', get_string('instance', 'mod_intebchat') . ': ', ['class' => 'mr-2', 'for' => 'report-instance-select']);
$instanceoptions = [0 => get_string('allinstances', 'mod_intebchat')];
foreach ($instances as $instance) {
    $instanceoptions[$instance->id] = format_string($instance->name);
}
echo html_writer::select($instanceoptions, 'instanceid', $instanceid, null, [
    'id' => 'report-instance-select',
    'class' => 'custom-select',
]);
echo html_writer::end_div();

// Period filter
echo html_writer::start_div('filter-group mr-3');
echo html_writer::tag('label', get_string('period', 'mod_intebchat') . ': ', ['class' => 'mr-2', 'for' => 'report-period-select']);
$periods = [
    'day' => get_string('period_day', 'mod_intebchat'),
    'week' => get_string('period_week', 'mod_intebchat'),
    'month' => get_string('period_month', 'mod_intebchat'),
    'all' => get_string('period_all', 'mod_intebchat'),
];
echo html_writer::select($periods, 'period', $period, null, [
    'id' => 'report-period-select',
    'class' => 'custom-select',
]);
echo html_writer::end_div();

// Export button
$exporturl = new moodle_url('/mod/intebchat/report_course.php', [
    'courseid' => $courseid,
    'period' => $period,
    'instanceid' => $instanceid,
    'download' => 'csv'
]);
echo html_writer::link($exporturl, get_string('exportcsv', 'mod_intebchat'), [
    'class' => 'btn btn-secondary'
]);

echo html_writer::end_div();

// Loading indicator
echo html_writer::div(
    html_writer::tag('i', '', ['class' => 'fa fa-spinner fa-spin fa-2x']) .
    html_writer::tag('span', get_string('loading', 'core'), ['class' => 'ml-2']),
    'text-center p-4',
    ['id' => 'report-loading', 'style' => 'display:none;']
);

// Content wrapper
echo html_writer::start_div('', ['id' => 'report-content']);

// Summary cards
echo html_writer::start_div('row mb-4');

$cards = [
    ['key' => 'total_messages', 'title' => get_string('totalmessages', 'mod_intebchat'), 'value' => number_format($stats->total_messages), 'icon' => 'comments', 'class' => 'primary'],
    ['key' => 'total_users', 'title' => get_string('totalusers', 'mod_intebchat'), 'value' => number_format($stats->total_users), 'icon' => 'users', 'class' => 'success'],
    ['key' => 'total_conversations', 'title' => get_string('totalconversations', 'mod_intebchat'), 'value' => number_format($stats->total_conversations), 'icon' => 'comment-dots', 'class' => 'info'],
    ['key' => 'total_tokens', 'title' => get_string('totaltokens', 'mod_intebchat'), 'value' => number_format($stats->total_tokens), 'icon' => 'coins', 'class' => 'warning'],
];

foreach ($cards as $card) {
    echo html_writer::start_div('col-md-3 col-sm-6 mb-3');
    echo html_writer::start_div('card border-' . $card['class'] . ' h-100 summary-card', ['data-key' => $card['key']]);
    echo html_writer::start_div('card-body text-center');
    echo html_writer::tag('i', '', ['class' => 'fa fa-' . $card['icon'] . ' fa-2x text-' . $card['class'] . ' mb-2']);
    echo html_writer::tag('h3', $card['value'], ['class' => 'card-title mb-0 card-value']);
    echo html_writer::tag('p', $card['title'], ['class' => 'card-text text-muted small']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::end_div();

// Instance breakdown (if not filtering by instance)
echo html_writer::start_div('', ['id' => 'instances-section']);
if ($instanceid == 0 && count($instances) > 1) {
    echo html_writer::start_div('card mb-4');
    echo html_writer::tag('div', get_string('usagebyinstance', 'mod_intebchat'), ['class' => 'card-header']);
    echo html_writer::start_div('card-body');

    echo html_writer::start_tag('table', ['id' => 'instances-table', 'class' => 'table table-striped table-hover']);
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('instance', 'mod_intebchat')) .
        html_writer::tag('th', get_string('messages', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('users', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('conversations', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('tokens', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('actions', 'mod_intebchat'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($stats->instances as $instance) {
        $cm = get_coursemodule_from_instance('intebchat', $instance->instanceid, $courseid, false, IGNORE_MISSING);
        $viewurl = $cm ? new moodle_url('/mod/intebchat/analytics.php', ['id' => $cm->id]) : '#';

        echo html_writer::tag('tr',
            html_writer::tag('td', format_string($instance->name)) .
            html_writer::tag('td', number_format($instance->messages), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($instance->users), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($instance->conversations), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($instance->tokens), ['class' => 'text-right']) .
            html_writer::tag('td', $cm ? html_writer::link($viewurl, get_string('viewdetails', 'mod_intebchat'), ['class' => 'btn btn-sm btn-outline-primary']) : '')
        );
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');
    echo html_writer::end_div();
    echo html_writer::end_div();
}
echo html_writer::end_div(); // End instances-section

// User activity table
echo html_writer::start_div('', ['id' => 'users-section']);
echo html_writer::start_div('card');
echo html_writer::tag('div', get_string('usagebyuser', 'mod_intebchat'), ['class' => 'card-header']);
echo html_writer::start_div('card-body');

if (empty($stats->users)) {
    echo $OUTPUT->notification(get_string('nodata', 'mod_intebchat'), 'info');
} else {
    $users = array_values($stats->users);
    $totalcount = count($users);
    $users = array_slice($users, $page * $perpage, $perpage);

    echo html_writer::start_tag('table', ['id' => 'users-table', 'class' => 'table table-striped table-hover']);
    echo html_writer::start_tag('thead', ['class' => 'thead-light']);
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('user', 'mod_intebchat')) .
        html_writer::tag('th', get_string('email', 'mod_intebchat')) .
        html_writer::tag('th', get_string('messages', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('conversations', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('tokens', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('lastactivity', 'mod_intebchat'), ['class' => 'text-right'])
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($users as $user) {
        $userurl = new moodle_url('/user/profile.php', ['id' => $user->userid]);
        $fullname = fullname($user);

        echo html_writer::tag('tr',
            html_writer::tag('td', html_writer::link($userurl, $fullname)) .
            html_writer::tag('td', $user->email) .
            html_writer::tag('td', number_format($user->messages), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($user->conversations), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($user->tokens), ['class' => 'text-right']) .
            html_writer::tag('td', userdate($user->lastactivity, get_string('strftimedatetime')), ['class' => 'text-right'])
        );
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Pagination
    echo html_writer::start_div('report-pagination');
    $baseurl = new moodle_url('/mod/intebchat/report_course.php', [
        'courseid' => $courseid,
        'period' => $period,
        'instanceid' => $instanceid
    ]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
    echo html_writer::end_div();
}

echo html_writer::end_div(); // End card-body
echo html_writer::end_div(); // End card
echo html_writer::end_div(); // End users-section
echo html_writer::end_div(); // End report-content

echo $OUTPUT->footer();

/**
 * Get course statistics
 */
function get_course_statistics($courseid, $starttime = 0, $instanceid = 0) {
    global $DB;

    $params = ['courseid' => $courseid];
    $where = ['i.course = :courseid'];

    if ($starttime > 0) {
        $where[] = 'l.timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }

    if ($instanceid > 0) {
        $where[] = 'i.id = :instanceid';
        $params['instanceid'] = $instanceid;
    }

    $whereclause = 'WHERE ' . implode(' AND ', $where);

    // Overall statistics
    $sql = "SELECT
                COUNT(DISTINCT l.id) as total_messages,
                COUNT(DISTINCT l.userid) as total_users,
                COUNT(DISTINCT l.conversationid) as total_conversations,
                COALESCE(SUM(l.totaltokens), 0) as total_tokens
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            $whereclause";

    $stats = $DB->get_record_sql($sql, $params);

    // Per-instance statistics
    $sql = "SELECT
                i.id as instanceid,
                i.name,
                COUNT(DISTINCT l.id) as messages,
                COUNT(DISTINCT l.userid) as users,
                COUNT(DISTINCT l.conversationid) as conversations,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat} i
            LEFT JOIN {intebchat_log} l ON l.instanceid = i.id
                " . ($starttime > 0 ? "AND l.timecreated >= :starttime2" : "") . "
            WHERE i.course = :courseid2
            GROUP BY i.id, i.name
            ORDER BY tokens DESC";

    $instanceparams = ['courseid2' => $courseid];
    if ($starttime > 0) {
        $instanceparams['starttime2'] = $starttime;
    }
    $stats->instances = $DB->get_records_sql($sql, $instanceparams);

    // Per-user statistics
    $sql = "SELECT
                l.userid,
                u.firstname,
                u.lastname,
                u.firstnamephonetic,
                u.lastnamephonetic,
                u.middlename,
                u.alternatename,
                u.email,
                COUNT(DISTINCT l.id) as messages,
                COUNT(DISTINCT l.conversationid) as conversations,
                COALESCE(SUM(l.totaltokens), 0) as tokens,
                MAX(l.timecreated) as lastactivity
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            JOIN {user} u ON l.userid = u.id
            $whereclause
            GROUP BY l.userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email
            ORDER BY tokens DESC";

    $stats->users = $DB->get_records_sql($sql, $params);

    return $stats;
}

/**
 * Export course report as CSV
 */
function export_course_report_csv($stats, $course, $starttime, $instanceid) {
    $filename = 'intebchat_course_' . $course->shortname . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Course info
    fputcsv($output, ['Course', $course->fullname]);
    fputcsv($output, ['Report Date', date('Y-m-d H:i:s')]);
    fputcsv($output, []);

    // Summary
    fputcsv($output, ['Summary']);
    fputcsv($output, ['Total Messages', $stats->total_messages]);
    fputcsv($output, ['Total Users', $stats->total_users]);
    fputcsv($output, ['Total Conversations', $stats->total_conversations]);
    fputcsv($output, ['Total Tokens', $stats->total_tokens]);
    fputcsv($output, []);

    // User details
    fputcsv($output, ['User Details']);
    fputcsv($output, ['Name', 'Email', 'Messages', 'Conversations', 'Tokens', 'Last Activity']);
    foreach ($stats->users as $user) {
        fputcsv($output, [
            $user->firstname . ' ' . $user->lastname,
            $user->email,
            $user->messages,
            $user->conversations,
            $user->tokens,
            userdate($user->lastactivity)
        ]);
    }

    fclose($output);
}
