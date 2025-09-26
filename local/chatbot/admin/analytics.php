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
 * Analytics dashboard for the chatbot plugin.
 *
 * @package    local_chatbot
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/chartlib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_chatbot_analytics');
require_capability('local/chatbot:manage', context_system::instance());

$PAGE->set_title(get_string('analytics', 'local_chatbot'));
$PAGE->set_heading(get_string('analytics', 'local_chatbot'));

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('analytics', 'local_chatbot'));

echo $OUTPUT->notification(get_string('analytics_intro', 'local_chatbot'), \core\output\notification::NOTIFY_INFO);

$totalmessages = $DB->count_records('local_chatbot_logs');
$totalsessions = (int)$DB->get_field_sql('SELECT COUNT(DISTINCT sessionid) FROM {local_chatbot_logs}');
$totalusers = (int)$DB->get_field_sql('SELECT COUNT(DISTINCT userid) FROM {local_chatbot_logs}');
$averageresponse = (float)$DB->get_field_sql('SELECT AVG(responsetime) FROM {local_chatbot_logs}');

$stats = [
    get_string('analytics_total_messages', 'local_chatbot') => $totalmessages,
    get_string('analytics_total_sessions', 'local_chatbot') => $totalsessions,
    get_string('analytics_total_users', 'local_chatbot') => $totalusers,
    get_string('analytics_average_response', 'local_chatbot') => $averageresponse ? round($averageresponse, 2) . ' ms' : '-'
];

echo html_writer::start_div('local-chatbot-analytics-stats d-flex flex-wrap gap-3 mb-4');
foreach ($stats as $label => $value) {
    echo html_writer::div(
        html_writer::tag('strong', $value) . html_writer::empty_tag('br') .
        html_writer::span($label, 'text-muted'),
        'p-3 border rounded'
    );
}

echo html_writer::end_div();

$intentrecords = $DB->get_records_sql('SELECT intent, COUNT(*) AS total FROM {local_chatbot_logs} GROUP BY intent ORDER BY total DESC');
if ($intentrecords) {
    echo $OUTPUT->heading(get_string('analytics_intents_heading', 'local_chatbot'), 3);

    $table = new flexible_table('local-chatbot-analytics-intents');
    $table->define_columns(['intent', 'total']);
    $table->define_headers([get_string('intent', 'local_chatbot'), get_string('analytics_messages', 'local_chatbot')]);
    $table->set_attribute('class', 'generaltable generalbox');
    $table->setup();

    $chart = new core\chart_bar();
    $series = new core\chart_series(get_string('analytics_messages', 'local_chatbot'));
    $labels = [];
    $values = [];

    foreach ($intentrecords as $record) {
        $table->add_data([format_string($record->intent ?: get_string('intent_unknown', 'local_chatbot')), (int)$record->total]);
        $labels[] = $record->intent ?: get_string('intent_unknown', 'local_chatbot');
        $values[] = (int)$record->total;
    }

    $table->finish_output();

    $series->set_data($values);
    $chart->add_series($series);
    $chart->set_labels($labels);
    $chart->set_title(get_string('analytics_intents_chart_title', 'local_chatbot'));

    echo $OUTPUT->render($chart);
}

$days = 14;
$cutoff = time() - ($days - 1) * DAYSECS;
$labels = [];
$values = [];
$dailycounts = [];
$records = $DB->get_records_select('local_chatbot_logs', 'timecreated >= ?', [$cutoff], 'timecreated ASC', 'timecreated');
foreach ($records as $record) {
    $day = date('Y-m-d', (int)$record->timecreated);
    if (!isset($dailycounts[$day])) {
        $dailycounts[$day] = 0;
    }
    $dailycounts[$day]++;
}

for ($i = $days - 1; $i >= 0; $i--) {
    $date = date('Y-m-d', time() - $i * DAYSECS);
    $labels[] = $date;
    $values[] = $dailycounts[$date] ?? 0;
}

$activitychart = new core\chart_line();
$activityseries = new core\chart_series(get_string('analytics_messages', 'local_chatbot'), $values);
$activitychart->add_series($activityseries);
$activitychart->set_labels($labels);
$activitychart->set_title(get_string('analytics_activity_chart_title', 'local_chatbot'));

if (array_sum($values) > 0) {
    echo $OUTPUT->heading(get_string('analytics_activity_heading', 'local_chatbot'), 3);
    echo $OUTPUT->render($activitychart);
}

$feedbackrecords = $DB->get_records_sql("SELECT feedback, COUNT(*) AS total
    FROM {local_chatbot_logs}
    WHERE feedback IS NOT NULL AND feedback <> ''
    GROUP BY feedback");
if ($feedbackrecords) {
    echo $OUTPUT->heading(get_string('analytics_feedback_heading', 'local_chatbot'), 3);
    $table = new flexible_table('local-chatbot-analytics-feedback');
    $table->define_columns(['label', 'total']);
    $table->define_headers([get_string('feedback', 'local_chatbot'), get_string('analytics_messages', 'local_chatbot')]);
    $table->set_attribute('class', 'generaltable generalbox');
    $table->setup();

    $feedbackchart = new core\chart_pie();
    $pievalues = [];
    $pielabels = [];

    foreach ($feedbackrecords as $record) {
        $label = $record->feedback ?: get_string('analytics_feedback_unknown', 'local_chatbot');
        $table->add_data([format_string($label), (int)$record->total]);
        $pielabels[] = $label;
        $pievalues[] = (int)$record->total;
    }

    $table->finish_output();

    $feedbackchart->add_series(new core\chart_series(get_string('analytics_messages', 'local_chatbot'), $pievalues));
    $feedbackchart->set_labels($pielabels);

    echo $OUTPUT->render($feedbackchart);
}

echo $OUTPUT->footer();
