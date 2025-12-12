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
 * Reports and statistics dashboard for educambot.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_educambot_reports');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

// Get filter parameters.
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto = optional_param('dateto', 0, PARAM_INT);
$matchfilter = optional_param('matchfilter', '', PARAM_ALPHA);
$searchquery = optional_param('search', '', PARAM_TEXT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 20;

$PAGE->set_url('/local/educambot/reports.php');
$PAGE->set_context($context);
$PAGE->set_title(get_string('reports', 'local_educambot'));
$PAGE->set_heading(get_string('reports', 'local_educambot'));

// Add CSS for dashboard cards.
$PAGE->requires->css(new moodle_url('/local/educambot/styles.css'));

echo $OUTPUT->header();

// Build WHERE conditions for filters.
$conditions = [];
$params = [];

if ($datefrom > 0) {
    $conditions[] = 'l.timecreated >= :datefrom';
    $params['datefrom'] = $datefrom;
}

if ($dateto > 0) {
    $conditions[] = 'l.timecreated <= :dateto';
    $params['dateto'] = $dateto + 86399; // End of day.
}

if ($matchfilter === 'matched') {
    $conditions[] = 'l.matched = 1';
} else if ($matchfilter === 'unmatched') {
    $conditions[] = 'l.matched = 0';
}

if (!empty($searchquery)) {
    $conditions[] = $DB->sql_like('l.question', ':search', false);
    $params['search'] = '%' . $DB->sql_like_escape($searchquery) . '%';
}

$where = empty($conditions) ? '1=1' : implode(' AND ', $conditions);

// ==================== SECTION 1: STATISTICS CARDS ====================
echo html_writer::start_tag('div', ['class' => 'educambot-stats-container mb-4']);

// Get statistics (use unfiltered counts for overview).
$totalconversations = $DB->count_records('local_educambot_log');
$matchedquestions = $DB->count_records('local_educambot_log', ['matched' => 1]);
$unmatchedquestions = $DB->count_records('local_educambot_log', ['matched' => 0]);
$successrate = $totalconversations > 0 ? round(($matchedquestions / $totalconversations) * 100, 1) : 0;
$avgconfidence = $DB->get_field_sql('SELECT AVG(confidence) FROM {local_educambot_log} WHERE matched = 1');
$avgconfidence = $avgconfidence ? round($avgconfidence * 100, 1) : 0;
$uniqueusers = $DB->count_records_sql('SELECT COUNT(DISTINCT userid) FROM {local_educambot_log}');

// Cards HTML with SVG icons (Bootstrap 4/5 compatible - no Font Awesome dependency).
$cards = [
    [
        'title' => get_string('totalconversations', 'local_educambot'),
        'value' => $totalconversations,
        'class' => 'bg-primary',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12zM2 0a2 2 0 0 0-2 2v12.793a.5.5 0 0 0 .854.353l2.853-2.853A1 1 0 0 1 4.414 12H14a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2H2z"/><path d="M3 3.5a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9a.5.5 0 0 1-.5-.5zM3 6a.5.5 0 0 1 .5-.5h9a.5.5 0 0 1 0 1h-9A.5.5 0 0 1 3 6zm0 2.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5z"/></svg>',
    ],
    [
        'title' => get_string('matchedquestions', 'local_educambot'),
        'value' => $matchedquestions,
        'class' => 'bg-success',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/></svg>',
    ],
    [
        'title' => get_string('unmatchedquestions', 'local_educambot'),
        'value' => $unmatchedquestions,
        'class' => 'bg-warning',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M5.255 5.786a.237.237 0 0 0 .241.247h.825c.138 0 .248-.113.266-.25.09-.656.54-1.134 1.342-1.134.686 0 1.314.343 1.314 1.168 0 .635-.374.927-.965 1.371-.673.489-1.206 1.06-1.168 1.987l.003.217a.25.25 0 0 0 .25.246h.811a.25.25 0 0 0 .25-.25v-.105c0-.718.273-.927 1.01-1.486.609-.463 1.244-.977 1.244-2.056 0-1.511-1.276-2.241-2.673-2.241-1.267 0-2.655.59-2.75 2.286zm1.557 5.763c0 .533.425.927 1.01.927.609 0 1.028-.394 1.028-.927 0-.552-.42-.94-1.029-.94-.584 0-1.009.388-1.009.94z"/></svg>',
    ],
    [
        'title' => get_string('successrate', 'local_educambot'),
        'value' => $successrate . '%',
        'class' => 'bg-info',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M0 0h1v15h15v1H0V0Zm14.817 3.113a.5.5 0 0 1 .07.704l-4.5 5.5a.5.5 0 0 1-.74.037L7.06 6.767l-3.656 5.027a.5.5 0 0 1-.808-.588l4-5.5a.5.5 0 0 1 .758-.06l2.609 2.61 4.15-5.073a.5.5 0 0 1 .704-.07Z"/></svg>',
    ],
    [
        'title' => get_string('averageconfidence', 'local_educambot'),
        'value' => $avgconfidence . '%',
        'class' => 'bg-secondary',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/><path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z"/></svg>',
    ],
    [
        'title' => get_string('uniqueusers', 'local_educambot'),
        'value' => $uniqueusers,
        'class' => 'bg-dark',
        'svg' => '<svg width="32" height="32" fill="currentColor" viewBox="0 0 16 16"><path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8Zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022ZM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816ZM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0Zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4Z"/></svg>',
    ],
];

echo html_writer::start_tag('div', ['class' => 'row']);
foreach ($cards as $card) {
    echo html_writer::start_tag('div', ['class' => 'col-md-4 col-lg-2 mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card text-white ' . $card['class']]);
    echo html_writer::start_tag('div', ['class' => 'card-body text-center']);
    echo html_writer::tag('div', $card['svg'], ['class' => 'mb-2']);
    echo html_writer::tag('h3', $card['value'], ['class' => 'card-title mb-0']);
    echo html_writer::tag('small', $card['title']);
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// ==================== SECTION 2: FILTERS ====================
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', get_string('filters', 'local_educambot'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

$filterurl = new moodle_url('/local/educambot/reports.php');
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out_omit_querystring(), 'class' => 'form-inline educambot-filter-form']);

echo html_writer::start_tag('div', ['class' => 'row w-100']);

// Date from.
echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-2']);
echo html_writer::tag('label', get_string('datefrom', 'local_educambot'), ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'datefrom_date',
    'class' => 'form-control',
    'value' => $datefrom > 0 ? date('Y-m-d', $datefrom) : '',
]);
echo html_writer::end_tag('div');

// Date to.
echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-2']);
echo html_writer::tag('label', get_string('dateto', 'local_educambot'), ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'date',
    'name' => 'dateto_date',
    'class' => 'form-control',
    'value' => $dateto > 0 ? date('Y-m-d', $dateto) : '',
]);
echo html_writer::end_tag('div');

// Match filter.
echo html_writer::start_tag('div', ['class' => 'col-md-2 mb-2']);
echo html_writer::tag('label', get_string('status_header', 'local_educambot'), ['class' => 'mr-2']);
$matchoptions = [
    '' => get_string('all', 'local_educambot'),
    'matched' => get_string('matchedonly', 'local_educambot'),
    'unmatched' => get_string('unmatchedonly', 'local_educambot'),
];
echo html_writer::select($matchoptions, 'matchfilter', $matchfilter, null, ['class' => 'form-control']);
echo html_writer::end_tag('div');

// Search.
echo html_writer::start_tag('div', ['class' => 'col-md-3 mb-2']);
echo html_writer::tag('label', get_string('search'), ['class' => 'mr-2']);
echo html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'class' => 'form-control',
    'value' => $searchquery,
    'placeholder' => get_string('searchquestion', 'local_educambot'),
]);
echo html_writer::end_tag('div');

// Submit button.
echo html_writer::start_tag('div', ['class' => 'col-md-1 mb-2']);
echo html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('applyfilters', 'local_educambot'),
    'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');

// Hidden fields for date conversion (JavaScript will populate these).
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'datefrom', 'id' => 'datefrom', 'value' => $datefrom]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'dateto', 'id' => 'dateto', 'value' => $dateto]);

echo html_writer::end_tag('form');

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// ==================== SECTION 3: CONVERSATIONS TABLE ====================
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', get_string('recentconversations', 'local_educambot'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

// Count total records with filters.
$countsql = "SELECT COUNT(*) FROM {local_educambot_log} l WHERE $where";
$totalrecords = $DB->count_records_sql($countsql, $params);

if ($totalrecords == 0) {
    echo $OUTPUT->notification(get_string('nologs', 'local_educambot'), 'info');
} else {
    // Get records with pagination.
    // Include all name fields required by fullname() function in Moodle 4.0+.
    $sql = "SELECT l.*, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic,
                   u.middlename, u.alternatename, u.email
            FROM {local_educambot_log} l
            LEFT JOIN {user} u ON u.id = l.userid
            WHERE $where
            ORDER BY l.timecreated DESC";

    $logs = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('question', 'local_educambot'),
        get_string('status_header', 'local_educambot'),
        get_string('confidence', 'local_educambot'),
        get_string('date'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable table-striped';

    foreach ($logs as $log) {
        // User name.
        $username = fullname($log);

        // Truncate question.
        $questiontext = strlen($log->question) > 50 ? substr($log->question, 0, 47) . '...' : $log->question;

        // Status badge.
        if ($log->matched) {
            $status = html_writer::tag('span', get_string('matched', 'local_educambot'),
                ['class' => 'badge badge-success']);
        } else {
            $status = html_writer::tag('span', get_string('unmatched', 'local_educambot'),
                ['class' => 'badge badge-warning']);
        }

        // Confidence percentage.
        $confidence = $log->confidence ? round($log->confidence * 100, 1) . '%' : '-';

        // Date and time.
        $datetime = userdate($log->timecreated, get_string('strftimedatetime', 'langconfig'));

        // Actions - View details (modal trigger).
        $viewdetails = html_writer::tag('button', get_string('viewdetails', 'local_educambot'), [
            'class' => 'btn btn-sm btn-info view-log-details',
            'data-logid' => $log->id,
            'data-question' => s($log->question),
            'data-response' => s($log->response),
            'data-user' => s($username),
            'data-date' => $datetime,
            'data-confidence' => $confidence,
            'data-matched' => $log->matched,
        ]);

        $table->data[] = [
            format_text($username, FORMAT_PLAIN),
            format_text($questiontext, FORMAT_PLAIN),
            $status,
            $confidence,
            $datetime,
            $viewdetails,
        ];
    }

    echo html_writer::table($table);

    // Pagination.
    $baseurl = new moodle_url('/local/educambot/reports.php', [
        'datefrom' => $datefrom,
        'dateto' => $dateto,
        'matchfilter' => $matchfilter,
        'search' => $searchquery,
    ]);
    echo $OUTPUT->paging_bar($totalrecords, $page, $perpage, $baseurl);
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// ==================== SECTION 4: UNMATCHED QUESTIONS ====================
echo html_writer::start_tag('div', ['class' => 'card mb-4']);
echo html_writer::start_tag('div', ['class' => 'card-header']);
echo html_writer::tag('h5', get_string('questionswithoutrule', 'local_educambot'), ['class' => 'mb-0']);
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', ['class' => 'card-body']);

// Get unique unmatched questions with frequency.
$unmatchedsql = "SELECT question, COUNT(*) as frequency
                 FROM {local_educambot_log}
                 WHERE matched = 0
                 GROUP BY question
                 ORDER BY frequency DESC
                 LIMIT 20";
$unmatchedquestions = $DB->get_records_sql($unmatchedsql);

if (empty($unmatchedquestions)) {
    echo $OUTPUT->notification(get_string('nounmatchedquestions', 'local_educambot'), 'success');
} else {
    $table = new html_table();
    $table->head = [
        get_string('question', 'local_educambot'),
        get_string('frequency', 'local_educambot'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable table-striped';

    foreach ($unmatchedquestions as $uq) {
        // Truncate question for display.
        $questiontext = strlen($uq->question) > 80 ? substr($uq->question, 0, 77) . '...' : $uq->question;

        // Create rule button - links to manage.php with prefilled pattern.
        $createruleurl = new moodle_url('/local/educambot/manage.php', [
            'action' => 'add',
            'pattern' => $uq->question,
        ]);
        $createrule = html_writer::link($createruleurl, get_string('createrule', 'local_educambot'),
            ['class' => 'btn btn-sm btn-primary']);

        $table->data[] = [
            format_text($questiontext, FORMAT_PLAIN),
            $uq->frequency,
            $createrule,
        ];
    }

    echo html_writer::table($table);
}

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Modal for viewing log details.
echo html_writer::start_tag('div', [
    'class' => 'modal fade',
    'id' => 'logDetailsModal',
    'tabindex' => '-1',
    'role' => 'dialog',
    'aria-labelledby' => 'logDetailsModalLabel',
    'aria-hidden' => 'true',
]);
echo html_writer::start_tag('div', ['class' => 'modal-dialog modal-lg', 'role' => 'document']);
echo html_writer::start_tag('div', ['class' => 'modal-content']);

// Modal header.
echo html_writer::start_tag('div', ['class' => 'modal-header']);
echo html_writer::tag('h5', get_string('conversationdetails', 'local_educambot'),
    ['class' => 'modal-title', 'id' => 'logDetailsModalLabel']);
echo html_writer::tag('button', html_writer::tag('span', '&times;', ['aria-hidden' => 'true']), [
    'type' => 'button',
    'class' => 'close',
    'data-dismiss' => 'modal',
    'aria-label' => get_string('close', 'local_educambot'),
]);
echo html_writer::end_tag('div');

// Modal body.
echo html_writer::start_tag('div', ['class' => 'modal-body']);
echo html_writer::tag('div', '', ['id' => 'modal-content-area']);
echo html_writer::end_tag('div');

// Modal footer - compatible with both BS4 and BS5.
echo html_writer::start_tag('div', ['class' => 'modal-footer']);
echo html_writer::tag('button', get_string('close', 'local_educambot'), [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'data-dismiss' => 'modal',
    'data-bs-dismiss' => 'modal',
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Initialize JavaScript module with localized strings.
$jsstrings = [
    'matched' => get_string('matched', 'local_educambot'),
    'unmatched' => get_string('unmatched', 'local_educambot'),
    'user' => get_string('user'),
    'date' => get_string('date'),
    'status' => get_string('status_header', 'local_educambot'),
    'confidence' => get_string('confidence', 'local_educambot'),
    'question' => get_string('question', 'local_educambot'),
    'response' => get_string('response', 'local_educambot'),
];
$PAGE->requires->js_call_amd('local_educambot/reports', 'init', [$jsstrings]);

echo $OUTPUT->footer();
