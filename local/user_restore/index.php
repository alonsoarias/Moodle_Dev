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
 * Main page for local_user_restore plugin - displays list of deleted users.
 *
 * @package    local_user_restore
 * @copyright  2024 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/tablelib.php');

// Parameters.
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);
$sort = optional_param('sort', 'timemodified', PARAM_ALPHANUMEXT);
$dir = optional_param('dir', 'DESC', PARAM_ALPHA);
$search = optional_param('search', '', PARAM_RAW);

// Setup page.
require_login();
$context = context_system::instance();
require_capability('local/user_restore:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/user_restore/index.php', [
    'page' => $page,
    'perpage' => $perpage,
    'sort' => $sort,
    'dir' => $dir,
    'search' => $search,
]));
$PAGE->set_title(get_string('deletedusers', 'local_user_restore'));
$PAGE->set_heading(get_string('managedeletedusers', 'local_user_restore'));
$PAGE->set_pagelayout('admin');

// Add navigation.
$PAGE->navbar->add(get_string('administrationsite'));
$PAGE->navbar->add(get_string('users'));
$PAGE->navbar->add(get_string('deletedusers', 'local_user_restore'));

// Get deleted users.
$result = \local_user_restore\restore_manager::get_deleted_users($search, $sort, $dir, $page, $perpage);
$users = $result['users'];
$totalcount = $result['total'];

// Check for restore capability.
$canrestore = has_capability('local/user_restore:restore', $context);

// Start output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('deletedusers', 'local_user_restore'));

// Search form.
$searchurl = new moodle_url('/local/user_restore/index.php');
echo '<form class="user-restore-search-form mb-3" method="get" action="' . $searchurl . '">';
echo '<div class="input-group" style="max-width: 400px;">';
echo '<input type="text" name="search" class="form-control" placeholder="' .
     get_string('search') . '" value="' . s($search) . '">';
echo '<div class="input-group-append">';
echo '<button type="submit" class="btn btn-primary">' . get_string('search') . '</button>';
if (!empty($search)) {
    echo '<a href="' . $searchurl . '" class="btn btn-secondary">' . get_string('clear') . '</a>';
}
echo '</div></div></form>';

if (empty($users)) {
    echo $OUTPUT->notification(get_string('nodeletedusers', 'local_user_restore'), 'info');
} else {
    // Setup table.
    $table = new html_table();
    $table->attributes['class'] = 'generaltable user-restore-table';
    $table->id = 'deleted-users-table';

    // Build sortable headers.
    $baseurl = new moodle_url('/local/user_restore/index.php', [
        'perpage' => $perpage,
        'search' => $search,
    ]);

    $columns = [
        'id' => get_string('userid', 'local_user_restore'),
        'username' => get_string('currentusername', 'local_user_restore'),
        'firstname' => get_string('firstname', 'local_user_restore'),
        'lastname' => get_string('lastname', 'local_user_restore'),
        'timemodified' => get_string('timedeleted', 'local_user_restore'),
    ];

    $headers = [];
    foreach ($columns as $column => $label) {
        $sorticon = '';
        $sortdir = 'ASC';

        if ($sort === $column) {
            $sortdir = ($dir === 'ASC') ? 'DESC' : 'ASC';
            $sorticon = ($dir === 'ASC') ? ' &#9650;' : ' &#9660;';
        }

        $sorturl = new moodle_url($baseurl, ['sort' => $column, 'dir' => $sortdir]);
        $headers[] = html_writer::link($sorturl, $label . $sorticon);
    }
    $headers[] = get_string('actions', 'local_user_restore');

    $table->head = $headers;

    // Build table rows.
    foreach ($users as $user) {
        $userinfo = \local_user_restore\restore_manager::get_user_display_info($user);

        $row = [];
        $row[] = $user->id;
        $row[] = s($user->username);
        $row[] = s($user->firstname);
        $row[] = s($user->lastname);
        $row[] = $userinfo->deletiontime_formatted;

        // Actions column.
        $actions = '';
        if ($canrestore) {
            $restoreurl = new moodle_url('/local/user_restore/restore.php', ['userid' => $user->id]);
            $actions .= html_writer::link(
                $restoreurl,
                get_string('restore', 'local_user_restore'),
                ['class' => 'btn btn-sm btn-primary']
            );
        }
        $row[] = $actions;

        $table->data[] = $row;
    }

    echo html_writer::table($table);

    // Pagination.
    echo $OUTPUT->paging_bar($totalcount, $page, $perpage, new moodle_url($baseurl, ['sort' => $sort, 'dir' => $dir]));
}

echo $OUTPUT->footer();
