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
 * Site-wide usage report for INTEB Chat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Parameters
$view = optional_param('view', 'overview', PARAM_ALPHA);
$courseid = optional_param('courseid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$period = optional_param('period', 'month', PARAM_ALPHA);
$download = optional_param('download', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 25, PARAM_INT);

// Setup admin page
admin_externalpage_setup('intebchatreport');

// Check capability
require_capability('mod/intebchat:viewsitereport', context_system::instance());

$PAGE->set_url(new moodle_url('/mod/intebchat/report_site.php', ['view' => $view, 'period' => $period]));
$PAGE->set_title(get_string('sitereport', 'mod_intebchat'));
$PAGE->set_heading(get_string('sitereport', 'mod_intebchat'));

// Initialize AMD module for AJAX
$PAGE->requires->js_call_amd('mod_intebchat/report', 'init', [[
    'type' => 'site',
    'view' => $view,
    'period' => $period,
    'courseid' => $courseid,
    'userid' => $userid,
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
    case 'year':
        $starttime = strtotime('-1 year');
        break;
    case 'all':
    default:
        $starttime = 0;
        break;
}

// Get statistics
$stats = get_site_statistics($starttime, $courseid, $userid);

// Handle CSV export
if ($download === 'csv') {
    export_report_csv($view, $stats, $starttime, $courseid, $userid);
    exit;
}

echo $OUTPUT->header();

// Navigation tabs
$tabs = [];
$baseurl = new moodle_url('/mod/intebchat/report_site.php', ['period' => $period]);
$tabs[] = new tabobject('overview', new moodle_url($baseurl, ['view' => 'overview']), get_string('overview', 'mod_intebchat'));
$tabs[] = new tabobject('courses', new moodle_url($baseurl, ['view' => 'courses']), get_string('usagebycourse', 'mod_intebchat'));
$tabs[] = new tabobject('users', new moodle_url($baseurl, ['view' => 'users']), get_string('usagebyuser', 'mod_intebchat'));

echo $OUTPUT->tabtree($tabs, $view);

// Period filter
echo html_writer::start_div('period-filter mb-3 d-flex align-items-center flex-wrap gap-2');
echo html_writer::tag('label', get_string('period', 'mod_intebchat') . ': ', ['class' => 'mr-2']);
$periods = [
    'day' => get_string('period_day', 'mod_intebchat'),
    'week' => get_string('period_week', 'mod_intebchat'),
    'month' => get_string('period_month', 'mod_intebchat'),
    'year' => get_string('period_year', 'mod_intebchat'),
    'all' => get_string('period_all', 'mod_intebchat'),
];
echo html_writer::select($periods, 'period', $period, null, [
    'id' => 'report-period-select',
    'class' => 'custom-select mr-2',
]);

// Export button
$exporturl = new moodle_url('/mod/intebchat/report_site.php', [
    'view' => $view,
    'period' => $period,
    'courseid' => $courseid,
    'userid' => $userid,
    'download' => 'csv'
]);
echo html_writer::link($exporturl, get_string('exportcsv', 'mod_intebchat'), [
    'class' => 'btn btn-secondary ml-3'
]);
// Loading indicator
echo html_writer::tag('div', html_writer::tag('i', '', ['class' => 'fa fa-spinner fa-spin fa-2x']) . ' ' . get_string('loading', 'core'),
    ['id' => 'report-loading', 'class' => 'text-center py-4', 'style' => 'display:none;']);

echo html_writer::end_div();

// Wrap content in a container for AJAX updates
echo html_writer::start_div('', ['id' => 'report-content']);

// Display content based on view
switch ($view) {
    case 'overview':
        display_overview($stats);
        break;
    case 'courses':
        display_courses_report($stats, $period, $page, $perpage);
        break;
    case 'users':
        display_users_report($stats, $period, $courseid, $page, $perpage);
        break;
}

// Pagination container for AJAX
echo html_writer::div('', 'report-pagination');

echo html_writer::end_div(); // #report-content

echo $OUTPUT->footer();

/**
 * Get site-wide statistics
 */
function get_site_statistics($starttime = 0, $courseid = 0, $userid = 0) {
    global $DB;

    $params = [];
    $where = [];

    if ($starttime > 0) {
        $where[] = 'l.timecreated >= :starttime';
        $params['starttime'] = $starttime;
    }

    if ($courseid > 0) {
        $where[] = 'i.course = :courseid';
        $params['courseid'] = $courseid;
    }

    if ($userid > 0) {
        $where[] = 'l.userid = :userid';
        $params['userid'] = $userid;
    }

    $whereclause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

    // Overall statistics
    $sql = "SELECT
                COUNT(DISTINCT l.id) as total_messages,
                COUNT(DISTINCT l.userid) as total_users,
                COUNT(DISTINCT i.course) as total_courses,
                COUNT(DISTINCT i.id) as total_instances,
                COALESCE(SUM(l.prompttokens), 0) as total_prompt_tokens,
                COALESCE(SUM(l.completiontokens), 0) as total_completion_tokens,
                COALESCE(SUM(l.totaltokens), 0) as total_tokens
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            $whereclause";

    $stats = $DB->get_record_sql($sql, $params);

    // Usage by course
    $sql = "SELECT
                i.course as courseid,
                c.fullname as coursename,
                c.shortname as shortname,
                COUNT(DISTINCT l.id) as messages,
                COUNT(DISTINCT l.userid) as users,
                COUNT(DISTINCT i.id) as instances,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            JOIN {course} c ON i.course = c.id
            $whereclause
            GROUP BY i.course, c.fullname, c.shortname
            ORDER BY tokens DESC";

    $stats->courses = $DB->get_records_sql($sql, $params);

    // Usage by user (top users)
    $userparams = $params;
    $userwhere = $whereclause;

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
                COUNT(DISTINCT i.course) as courses,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            JOIN {user} u ON l.userid = u.id
            $userwhere
            GROUP BY l.userid, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email
            ORDER BY tokens DESC";

    $stats->users = $DB->get_records_sql($sql, $userparams);

    // Daily usage for charts (last 30 days)
    $chartstart = max($starttime, strtotime('-30 days'));
    $sql = "SELECT
                DATE(FROM_UNIXTIME(l.timecreated)) as date,
                COUNT(DISTINCT l.id) as messages,
                COUNT(DISTINCT l.userid) as users,
                COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            JOIN {intebchat} i ON l.instanceid = i.id
            WHERE l.timecreated >= :chartstart
            GROUP BY DATE(FROM_UNIXTIME(l.timecreated))
            ORDER BY date ASC";

    $stats->daily = $DB->get_records_sql($sql, ['chartstart' => $chartstart]);

    return $stats;
}

/**
 * Display overview statistics
 */
function display_overview($stats) {
    global $OUTPUT;

    echo html_writer::start_div('row');

    // Summary cards
    $cards = [
        ['key' => 'total_messages', 'title' => get_string('totalmessages', 'mod_intebchat'), 'value' => number_format($stats->total_messages), 'icon' => 'comments', 'class' => 'primary'],
        ['key' => 'total_users', 'title' => get_string('totalusers', 'mod_intebchat'), 'value' => number_format($stats->total_users), 'icon' => 'users', 'class' => 'success'],
        ['key' => 'total_courses', 'title' => get_string('totalcourses', 'mod_intebchat'), 'value' => number_format($stats->total_courses), 'icon' => 'graduation-cap', 'class' => 'info'],
        ['key' => 'total_tokens', 'title' => get_string('totaltokens', 'mod_intebchat'), 'value' => number_format($stats->total_tokens), 'icon' => 'coins', 'class' => 'warning'],
    ];

    foreach ($cards as $card) {
        echo html_writer::start_div('col-md-3 mb-3');
        echo html_writer::start_div('card border-' . $card['class'] . ' summary-card', ['data-key' => $card['key']]);
        echo html_writer::start_div('card-body text-center');
        echo html_writer::tag('i', '', ['class' => 'fa fa-' . $card['icon'] . ' fa-2x text-' . $card['class'] . ' mb-2']);
        echo html_writer::tag('h3', $card['value'], ['class' => 'card-title mb-0 card-value']);
        echo html_writer::tag('p', $card['title'], ['class' => 'card-text text-muted']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }

    echo html_writer::end_div();

    // Token breakdown
    echo html_writer::start_div('row mt-4');
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card');
    echo html_writer::tag('div', get_string('tokenbreakdown', 'mod_intebchat'), ['class' => 'card-header']);
    echo html_writer::start_div('card-body');

    echo html_writer::start_tag('table', ['class' => 'table table-sm']);
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('prompttokens', 'mod_intebchat')) .
        html_writer::tag('td', number_format($stats->total_prompt_tokens), ['class' => 'text-right'])
    );
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('completiontokens', 'mod_intebchat')) .
        html_writer::tag('td', number_format($stats->total_completion_tokens), ['class' => 'text-right'])
    );
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('total')) .
        html_writer::tag('th', number_format($stats->total_tokens), ['class' => 'text-right'])
    );
    echo html_writer::end_tag('table');

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Averages
    echo html_writer::start_div('col-md-6');
    echo html_writer::start_div('card');
    echo html_writer::tag('div', get_string('averages', 'mod_intebchat'), ['class' => 'card-header']);
    echo html_writer::start_div('card-body');

    $avg_per_user = $stats->total_users > 0 ? round($stats->total_tokens / $stats->total_users) : 0;
    $avg_per_message = $stats->total_messages > 0 ? round($stats->total_tokens / $stats->total_messages) : 0;
    $avg_messages_per_user = $stats->total_users > 0 ? round($stats->total_messages / $stats->total_users, 1) : 0;

    echo html_writer::start_tag('table', ['class' => 'table table-sm']);
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('avgtokensperuser', 'mod_intebchat')) .
        html_writer::tag('td', number_format($avg_per_user), ['class' => 'text-right'])
    );
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('avgtokenspermessage', 'mod_intebchat')) .
        html_writer::tag('td', number_format($avg_per_message), ['class' => 'text-right'])
    );
    echo html_writer::tag('tr',
        html_writer::tag('td', get_string('avgmessagesperuser', 'mod_intebchat')) .
        html_writer::tag('td', number_format($avg_messages_per_user, 1), ['class' => 'text-right'])
    );
    echo html_writer::end_tag('table');

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    // Top 5 courses
    if (!empty($stats->courses)) {
        echo html_writer::start_div('card mt-4');
        echo html_writer::tag('div', get_string('topcourses', 'mod_intebchat'), ['class' => 'card-header']);
        echo html_writer::start_div('card-body');

        echo html_writer::start_tag('table', ['class' => 'table table-striped']);
        echo html_writer::start_tag('thead');
        echo html_writer::tag('tr',
            html_writer::tag('th', get_string('course')) .
            html_writer::tag('th', get_string('messages', 'mod_intebchat'), ['class' => 'text-right']) .
            html_writer::tag('th', get_string('users', 'mod_intebchat'), ['class' => 'text-right']) .
            html_writer::tag('th', get_string('tokens', 'mod_intebchat'), ['class' => 'text-right'])
        );
        echo html_writer::end_tag('thead');
        echo html_writer::start_tag('tbody');

        $count = 0;
        foreach ($stats->courses as $course) {
            if ($count >= 5) break;
            $courseurl = new moodle_url('/course/view.php', ['id' => $course->courseid]);
            echo html_writer::tag('tr',
                html_writer::tag('td', html_writer::link($courseurl, $course->coursename)) .
                html_writer::tag('td', number_format($course->messages), ['class' => 'text-right']) .
                html_writer::tag('td', number_format($course->users), ['class' => 'text-right']) .
                html_writer::tag('td', number_format($course->tokens), ['class' => 'text-right'])
            );
            $count++;
        }

        echo html_writer::end_tag('tbody');
        echo html_writer::end_tag('table');
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
}

/**
 * Display courses report
 */
function display_courses_report($stats, $period, $page, $perpage) {
    global $OUTPUT;

    if (empty($stats->courses)) {
        echo $OUTPUT->notification(get_string('nodata', 'mod_intebchat'), 'info');
        return;
    }

    $courses = array_values($stats->courses);
    $totalcount = count($courses);
    $courses = array_slice($courses, $page * $perpage, $perpage);

    echo html_writer::start_div('', ['id' => 'courses-section']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'id' => 'courses-table']);
    echo html_writer::start_tag('thead', ['class' => 'thead-dark']);
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('course')) .
        html_writer::tag('th', get_string('shortname')) .
        html_writer::tag('th', get_string('instances', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('messages', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('users', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('tokens', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('actions', 'mod_intebchat'))
    );
    echo html_writer::end_tag('thead');
    echo html_writer::start_tag('tbody');

    foreach ($courses as $course) {
        $courseurl = new moodle_url('/course/view.php', ['id' => $course->courseid]);
        $detailurl = new moodle_url('/mod/intebchat/report_site.php', [
            'view' => 'users',
            'period' => $period,
            'courseid' => $course->courseid
        ]);

        echo html_writer::tag('tr',
            html_writer::tag('td', html_writer::link($courseurl, $course->coursename)) .
            html_writer::tag('td', $course->shortname) .
            html_writer::tag('td', number_format($course->instances), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($course->messages), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($course->users), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($course->tokens), ['class' => 'text-right']) .
            html_writer::tag('td', html_writer::link($detailurl, get_string('viewusers', 'mod_intebchat'), ['class' => 'btn btn-sm btn-outline-primary']))
        );
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Pagination
    $baseurl = new moodle_url('/mod/intebchat/report_site.php', ['view' => 'courses', 'period' => $period]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
    echo html_writer::end_div(); // #courses-section
}

/**
 * Display users report
 */
function display_users_report($stats, $period, $courseid, $page, $perpage) {
    global $OUTPUT, $DB;

    // If filtering by course, get course-specific users
    if ($courseid > 0) {
        $course = $DB->get_record('course', ['id' => $courseid]);
        if ($course) {
            echo html_writer::tag('div',
                get_string('filteringbycourse', 'mod_intebchat', $course->fullname) . ' ' .
                html_writer::link(
                    new moodle_url('/mod/intebchat/report_site.php', ['view' => 'users', 'period' => $period]),
                    get_string('clearfilter', 'mod_intebchat'),
                    ['class' => 'btn btn-sm btn-outline-secondary']
                ),
                ['class' => 'alert alert-info']
            );
        }
    }

    if (empty($stats->users)) {
        echo $OUTPUT->notification(get_string('nodata', 'mod_intebchat'), 'info');
        return;
    }

    $users = array_values($stats->users);
    $totalcount = count($users);
    $users = array_slice($users, $page * $perpage, $perpage);

    echo html_writer::start_div('', ['id' => 'users-section']);
    echo html_writer::start_tag('table', ['class' => 'table table-striped table-hover', 'id' => 'users-table']);
    echo html_writer::start_tag('thead', ['class' => 'thead-dark']);
    echo html_writer::tag('tr',
        html_writer::tag('th', get_string('user', 'mod_intebchat')) .
        html_writer::tag('th', get_string('email', 'mod_intebchat')) .
        html_writer::tag('th', get_string('messages', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('courses', 'mod_intebchat'), ['class' => 'text-right']) .
        html_writer::tag('th', get_string('tokens', 'mod_intebchat'), ['class' => 'text-right'])
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
            html_writer::tag('td', number_format($user->courses), ['class' => 'text-right']) .
            html_writer::tag('td', number_format($user->tokens), ['class' => 'text-right'])
        );
    }

    echo html_writer::end_tag('tbody');
    echo html_writer::end_tag('table');

    // Pagination
    $baseurl = new moodle_url('/mod/intebchat/report_site.php', [
        'view' => 'users',
        'period' => $period,
        'courseid' => $courseid
    ]);
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);
    echo html_writer::end_div(); // #users-section
}

/**
 * Export report as CSV
 */
function export_report_csv($view, $stats, $starttime, $courseid, $userid) {
    $filename = 'intebchat_report_' . $view . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);

    $output = fopen('php://output', 'w');

    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    switch ($view) {
        case 'overview':
            fputcsv($output, ['Metric', 'Value']);
            fputcsv($output, ['Total Messages', $stats->total_messages]);
            fputcsv($output, ['Total Users', $stats->total_users]);
            fputcsv($output, ['Total Courses', $stats->total_courses]);
            fputcsv($output, ['Total Instances', $stats->total_instances]);
            fputcsv($output, ['Prompt Tokens', $stats->total_prompt_tokens]);
            fputcsv($output, ['Completion Tokens', $stats->total_completion_tokens]);
            fputcsv($output, ['Total Tokens', $stats->total_tokens]);
            break;

        case 'courses':
            fputcsv($output, ['Course', 'Short Name', 'Instances', 'Messages', 'Users', 'Tokens']);
            foreach ($stats->courses as $course) {
                fputcsv($output, [
                    $course->coursename,
                    $course->shortname,
                    $course->instances,
                    $course->messages,
                    $course->users,
                    $course->tokens
                ]);
            }
            break;

        case 'users':
            fputcsv($output, ['User', 'Email', 'Messages', 'Courses', 'Tokens']);
            foreach ($stats->users as $user) {
                fputcsv($output, [
                    $user->firstname . ' ' . $user->lastname,
                    $user->email,
                    $user->messages,
                    $user->courses,
                    $user->tokens
                ]);
            }
            break;
    }

    fclose($output);
}
