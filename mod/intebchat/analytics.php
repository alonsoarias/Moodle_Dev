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
 * Analytics dashboard for intebchat usage statistics.
 *
 * Displays token usage, conversation statistics, and user activity metrics
 * for a specific intebchat instance.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = required_param('id', PARAM_INT); // Course module ID.
$period = optional_param('period', 'week', PARAM_ALPHA); // Stats period.

$cm = get_coursemodule_from_id('intebchat', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
$intebchat = $DB->get_record('intebchat', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/intebchat:viewanalytics', $context);

// Setup page.
$PAGE->set_url('/mod/intebchat/analytics.php', ['id' => $id, 'period' => $period]);
$PAGE->set_title(format_string($intebchat->name) . ' - ' . get_string('analytics', 'mod_intebchat'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Add CSS.
$PAGE->requires->css('/mod/intebchat/styles/styles.css');

// Calculate period timestamps.
$now = time();
$periodstart = 0;
switch ($period) {
    case 'day':
        $periodstart = strtotime('today midnight');
        $periodlabel = get_string('period_day', 'mod_intebchat');
        break;
    case 'week':
        $periodstart = strtotime('-1 week');
        $periodlabel = get_string('period_week', 'mod_intebchat');
        break;
    case 'month':
        $periodstart = strtotime('-1 month');
        $periodlabel = get_string('period_month', 'mod_intebchat');
        break;
    case 'all':
        $periodstart = 0;
        $periodlabel = get_string('period_all', 'mod_intebchat');
        break;
    default:
        $periodstart = strtotime('-1 week');
        $periodlabel = get_string('period_week', 'mod_intebchat');
}

// Get statistics.
$stats = intebchat_get_instance_analytics($intebchat->id, $periodstart, $now);

// Build period selector.
$periods = [
    ['value' => 'day', 'label' => get_string('period_day', 'mod_intebchat'), 'selected' => $period === 'day'],
    ['value' => 'week', 'label' => get_string('period_week', 'mod_intebchat'), 'selected' => $period === 'week'],
    ['value' => 'month', 'label' => get_string('period_month', 'mod_intebchat'), 'selected' => $period === 'month'],
    ['value' => 'all', 'label' => get_string('period_all', 'mod_intebchat'), 'selected' => $period === 'all'],
];

// Format top users.
$topusers = [];
foreach ($stats['top_users'] as $user) {
    $userrecord = $DB->get_record('user', ['id' => $user->userid], 'id, firstname, lastname, email');
    if ($userrecord) {
        $topusers[] = [
            'fullname' => fullname($userrecord),
            'messages' => $user->messages,
            'tokens' => number_format($user->tokens),
            'profileurl' => (new moodle_url('/user/profile.php', ['id' => $user->userid]))->out()
        ];
    }
}

// Format daily stats for chart.
$dailystats = [];
foreach ($stats['daily_stats'] as $day) {
    $dailystats[] = [
        'date' => userdate($day->date, '%d/%m'),
        'messages' => $day->messages,
        'tokens' => $day->tokens
    ];
}

// Prepare template context.
$templatecontext = [
    'instancename' => format_string($intebchat->name),
    'viewurl' => (new moodle_url('/mod/intebchat/view.php', ['id' => $id]))->out(),
    'periodlabel' => $periodlabel,
    'periods' => $periods,
    'formaction' => (new moodle_url('/mod/intebchat/analytics.php', ['id' => $id]))->out(),

    // Summary stats.
    'total_conversations' => number_format($stats['total_conversations']),
    'total_messages' => number_format($stats['total_messages']),
    'total_tokens' => number_format($stats['total_tokens']),
    'unique_users' => number_format($stats['unique_users']),
    'avg_messages_per_user' => number_format($stats['avg_messages_per_user'], 1),
    'avg_tokens_per_message' => number_format($stats['avg_tokens_per_message'], 0),

    // Top users.
    'hastopusers' => !empty($topusers),
    'topusers' => $topusers,

    // Daily stats for chart.
    'hasdailystats' => !empty($dailystats),
    'dailystats' => $dailystats,
    'dailystats_json' => json_encode($dailystats),

    // Strings.
    'str_analytics' => get_string('analytics', 'mod_intebchat'),
    'str_backtochat' => get_string('backtochat', 'mod_intebchat'),
    'str_period' => get_string('period', 'mod_intebchat'),
    'str_overview' => get_string('analytics_overview', 'mod_intebchat'),
    'str_conversations' => get_string('conversations', 'mod_intebchat'),
    'str_messages' => get_string('messages', 'mod_intebchat'),
    'str_tokens' => get_string('tokens', 'mod_intebchat'),
    'str_users' => get_string('users', 'mod_intebchat'),
    'str_avgmessages' => get_string('avg_messages_per_user', 'mod_intebchat'),
    'str_avgtokens' => get_string('avg_tokens_per_message', 'mod_intebchat'),
    'str_topusers' => get_string('top_users', 'mod_intebchat'),
    'str_user' => get_string('user'),
    'str_usage' => get_string('usage', 'mod_intebchat'),
    'str_dailyactivity' => get_string('daily_activity', 'mod_intebchat'),
    'str_date' => get_string('date'),
    'str_nodata' => get_string('nodata', 'mod_intebchat'),
];

// Output page.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_intebchat/analytics', $templatecontext);
echo $OUTPUT->footer();
