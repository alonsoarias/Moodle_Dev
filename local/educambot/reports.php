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

// Cards HTML.
$cards = [
    [
        'title' => get_string('totalconversations', 'local_educambot'),
        'value' => $totalconversations,
        'class' => 'bg-primary',
        'icon' => 'fa-comments',
    ],
    [
        'title' => get_string('matchedquestions', 'local_educambot'),
        'value' => $matchedquestions,
        'class' => 'bg-success',
        'icon' => 'fa-check-circle',
    ],
    [
        'title' => get_string('unmatchedquestions', 'local_educambot'),
        'value' => $unmatchedquestions,
        'class' => 'bg-warning',
        'icon' => 'fa-question-circle',
    ],
    [
        'title' => get_string('successrate', 'local_educambot'),
        'value' => $successrate . '%',
        'class' => 'bg-info',
        'icon' => 'fa-chart-line',
    ],
    [
        'title' => get_string('averageconfidence', 'local_educambot'),
        'value' => $avgconfidence . '%',
        'class' => 'bg-secondary',
        'icon' => 'fa-bullseye',
    ],
    [
        'title' => get_string('uniqueusers', 'local_educambot'),
        'value' => $uniqueusers,
        'class' => 'bg-dark',
        'icon' => 'fa-users',
    ],
];

echo html_writer::start_tag('div', ['class' => 'row']);
foreach ($cards as $card) {
    echo html_writer::start_tag('div', ['class' => 'col-md-4 col-lg-2 mb-3']);
    echo html_writer::start_tag('div', ['class' => 'card text-white ' . $card['class']]);
    echo html_writer::start_tag('div', ['class' => 'card-body text-center']);
    echo html_writer::tag('i', '', ['class' => 'fa ' . $card['icon'] . ' fa-2x mb-2']);
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
echo html_writer::start_tag('form', ['method' => 'get', 'action' => $filterurl->out_omit_querystring(), 'class' => 'form-inline']);

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

// JavaScript to convert date inputs to timestamps.
echo html_writer::script("
    document.querySelector('form').addEventListener('submit', function(e) {
        var datefromInput = document.querySelector('input[name=\"datefrom_date\"]');
        var datetoInput = document.querySelector('input[name=\"dateto_date\"]');
        var datefromHidden = document.getElementById('datefrom');
        var datetoHidden = document.getElementById('dateto');

        if (datefromInput.value) {
            datefromHidden.value = Math.floor(new Date(datefromInput.value).getTime() / 1000);
        } else {
            datefromHidden.value = 0;
        }

        if (datetoInput.value) {
            datetoHidden.value = Math.floor(new Date(datetoInput.value).getTime() / 1000);
        } else {
            datetoHidden.value = 0;
        }
    });
");

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
    $sql = "SELECT l.*, u.firstname, u.lastname, u.email
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

// Modal footer.
echo html_writer::start_tag('div', ['class' => 'modal-footer']);
echo html_writer::tag('button', get_string('close', 'local_educambot'), [
    'type' => 'button',
    'class' => 'btn btn-secondary',
    'data-dismiss' => 'modal',
]);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// JavaScript for modal.
echo html_writer::script("
    document.querySelectorAll('.view-log-details').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var question = this.dataset.question;
            var response = this.dataset.response;
            var user = this.dataset.user;
            var date = this.dataset.date;
            var confidence = this.dataset.confidence;
            var matched = this.dataset.matched;

            var statusBadge = matched == '1'
                ? '<span class=\"badge badge-success\">" . get_string('matched', 'local_educambot') . "</span>'
                : '<span class=\"badge badge-warning\">" . get_string('unmatched', 'local_educambot') . "</span>';

            var content = '<dl class=\"row\">' +
                '<dt class=\"col-sm-3\">" . get_string('user') . "</dt>' +
                '<dd class=\"col-sm-9\">' + user + '</dd>' +
                '<dt class=\"col-sm-3\">" . get_string('date') . "</dt>' +
                '<dd class=\"col-sm-9\">' + date + '</dd>' +
                '<dt class=\"col-sm-3\">" . get_string('status_header', 'local_educambot') . "</dt>' +
                '<dd class=\"col-sm-9\">' + statusBadge + '</dd>' +
                '<dt class=\"col-sm-3\">" . get_string('confidence', 'local_educambot') . "</dt>' +
                '<dd class=\"col-sm-9\">' + confidence + '</dd>' +
                '<dt class=\"col-sm-3\">" . get_string('question', 'local_educambot') . "</dt>' +
                '<dd class=\"col-sm-9\"><div class=\"alert alert-info\">' + question + '</div></dd>' +
                '<dt class=\"col-sm-3\">" . get_string('response', 'local_educambot') . "</dt>' +
                '<dd class=\"col-sm-9\"><div class=\"alert alert-secondary\">' + response + '</div></dd>' +
                '</dl>';

            document.getElementById('modal-content-area').innerHTML = content;

            // Show modal using Bootstrap.
            var modal = document.getElementById('logDetailsModal');
            if (typeof jQuery !== 'undefined') {
                jQuery(modal).modal('show');
            } else {
                modal.classList.add('show');
                modal.style.display = 'block';
            }
        });
    });
");

echo $OUTPUT->footer();
