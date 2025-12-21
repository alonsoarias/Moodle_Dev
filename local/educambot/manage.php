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
 * Manage bot rules page.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_educambot_manage');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);
$tag = optional_param('tag', '', PARAM_TEXT);

$PAGE->set_url('/local/educambot/manage.php', [
    'categoryid' => $categoryid,
    'search' => $search,
    'tag' => $tag,
]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('managerules', 'local_educambot'));
$PAGE->set_heading(get_string('managerules', 'local_educambot'));

// Handle delete action.
if ($action === 'delete' && $id > 0) {
    require_sesskey();
    // Delete associated options first.
    $DB->delete_records('local_educambot_option', ['ruleid' => $id]);
    $DB->delete_records('local_educambot_rule', ['id' => $id]);
    redirect(new moodle_url('/local/educambot/manage.php', ['categoryid' => $categoryid]),
        get_string('ruledeleted', 'local_educambot'),
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Handle toggle enabled/disabled.
if ($action === 'toggle' && $id > 0) {
    require_sesskey();
    $rule = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
    $rule->enabled = $rule->enabled ? 0 : 1;
    $rule->timemodified = time();
    $DB->update_record('local_educambot_rule', $rule);
    redirect(new moodle_url('/local/educambot/manage.php', ['categoryid' => $categoryid]));
}

// Handle add/edit form.
if ($action === 'edit' || $action === 'add') {
    $mform = new \local_educambot\form\entry_form();

    // Load existing data if editing.
    if ($id > 0) {
        $rule = $DB->get_record('local_educambot_rule', ['id' => $id], '*', MUST_EXIST);
        $mform->set_data($rule);
    } else if ($categoryid > 0) {
        // Pre-select category when adding from filtered view.
        $mform->set_data(['categoryid' => $categoryid]);
    }

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/educambot/manage.php', ['categoryid' => $categoryid]));
    } else if ($data = $mform->get_data()) {
        $now = time();

        if ($data->id > 0) {
            // Update existing rule.
            $data->timemodified = $now;
            $DB->update_record('local_educambot_rule', $data);
            $message = get_string('ruleupdated', 'local_educambot');
        } else {
            // Create new rule.
            $data->timecreated = $now;
            $data->timemodified = $now;
            $DB->insert_record('local_educambot_rule', $data);
            $message = get_string('rulecreated', 'local_educambot');
        }

        redirect(new moodle_url('/local/educambot/manage.php', ['categoryid' => $categoryid]),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($id > 0 ? get_string('editrule', 'local_educambot') : get_string('addrule', 'local_educambot'));
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// List all rules (default action).
echo $OUTPUT->header();

// Build filters section.
$categories = $DB->get_records_menu('local_educambot_category', null, 'sortorder ASC', 'id, name');
$categoryoptions = [0 => get_string('all', 'local_educambot')] + $categories;

$filterform = html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(), 'class' => 'mb-3']);
$filterform .= html_writer::start_div('form-inline');

// Category filter.
$filterform .= html_writer::label(get_string('category', 'local_educambot') . ': ', 'categoryid', true, ['class' => 'mr-2']);
$filterform .= html_writer::select($categoryoptions, 'categoryid', $categoryid, null, ['class' => 'form-control mr-3', 'id' => 'categoryid']);

// Search filter.
$filterform .= html_writer::label(get_string('searchrules', 'local_educambot') . ' ', 'search', true, ['class' => 'mr-2']);
$filterform .= html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'search',
    'id' => 'search',
    'value' => $search,
    'class' => 'form-control mr-3',
    'placeholder' => get_string('searchrules', 'local_educambot'),
]);

// Tag filter.
$filterform .= html_writer::label(get_string('tags', 'local_educambot') . ': ', 'tag', true, ['class' => 'mr-2']);
$filterform .= html_writer::empty_tag('input', [
    'type' => 'text',
    'name' => 'tag',
    'id' => 'tag',
    'value' => $tag,
    'class' => 'form-control mr-3',
    'placeholder' => get_string('tags', 'local_educambot'),
]);

// Submit button.
$filterform .= html_writer::empty_tag('input', [
    'type' => 'submit',
    'value' => get_string('applyfilters', 'local_educambot'),
    'class' => 'btn btn-secondary',
]);

$filterform .= html_writer::end_div();
$filterform .= html_writer::end_tag('form');

echo $filterform;

// Add rule button.
$addurl = new moodle_url('/local/educambot/manage.php', ['action' => 'add', 'categoryid' => $categoryid]);
echo html_writer::link($addurl, get_string('addrule', 'local_educambot'),
    ['class' => 'btn btn-primary mb-3']);

// Build WHERE clause for filtering.
$where = '1=1';
$params = [];

if ($categoryid > 0) {
    $where .= ' AND r.categoryid = :categoryid';
    $params['categoryid'] = $categoryid;
}

if (!empty($search)) {
    $where .= ' AND (r.pattern LIKE :search1 OR r.keywords LIKE :search2 OR r.response LIKE :search3)';
    $params['search1'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['search2'] = '%' . $DB->sql_like_escape($search) . '%';
    $params['search3'] = '%' . $DB->sql_like_escape($search) . '%';
}

if (!empty($tag)) {
    $where .= ' AND r.tags LIKE :tag';
    $params['tag'] = '%' . $DB->sql_like_escape($tag) . '%';
}

// Get rules with category name.
$sql = "SELECT r.*, c.name as categoryname
        FROM {local_educambot_rule} r
        LEFT JOIN {local_educambot_category} c ON r.categoryid = c.id
        WHERE $where
        ORDER BY r.timecreated DESC";

$rules = $DB->get_records_sql($sql, $params);

if (empty($rules)) {
    echo $OUTPUT->notification(get_string('norules', 'local_educambot'), 'info');
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('category', 'local_educambot'),
        get_string('pattern_header', 'local_educambot'),
        get_string('tags', 'local_educambot'),
        get_string('options', 'local_educambot'),
        get_string('status_header', 'local_educambot'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable';

    foreach ($rules as $rule) {
        $editurl = new moodle_url('/local/educambot/manage.php', ['action' => 'edit', 'id' => $rule->id]);
        $deleteurl = new moodle_url('/local/educambot/manage.php',
            ['action' => 'delete', 'id' => $rule->id, 'sesskey' => sesskey(), 'categoryid' => $categoryid]);
        $toggleurl = new moodle_url('/local/educambot/manage.php',
            ['action' => 'toggle', 'id' => $rule->id, 'sesskey' => sesskey(), 'categoryid' => $categoryid]);
        $optionsurl = new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $rule->id]);
        $duplicateurl = new moodle_url('/local/educambot/duplicate_rule.php', ['id' => $rule->id, 'sesskey' => sesskey()]);

        // Category name.
        $catname = $rule->categoryname ?? get_string('uncategorized', 'local_educambot');
        $catbadge = html_writer::tag('span', format_text($catname, FORMAT_PLAIN), ['class' => 'badge badge-info']);

        // Truncate long pattern.
        $pattern = strlen($rule->pattern) > 50 ? substr($rule->pattern, 0, 47) . '...' : $rule->pattern;

        // Tags display.
        $tagsdisplay = '';
        if (!empty($rule->tags)) {
            $tagsarray = array_map('trim', explode(',', $rule->tags));
            foreach ($tagsarray as $t) {
                if (!empty($t)) {
                    $tagurl = new moodle_url('/local/educambot/manage.php', ['tag' => $t]);
                    $tagsdisplay .= html_writer::link($tagurl,
                        html_writer::tag('span', $t, ['class' => 'badge badge-secondary mr-1']));
                }
            }
        }

        // Count options for this rule.
        $optioncount = $DB->count_records('local_educambot_option', ['ruleid' => $rule->id]);
        $optionsbadge = html_writer::link($optionsurl,
            html_writer::tag('span', $optioncount, ['class' => 'badge badge-primary']) .
            ' ' . get_string('manageoptions', 'local_educambot'),
            ['class' => 'btn btn-sm btn-outline-primary']);

        // Status badge.
        if ($rule->enabled) {
            $status = html_writer::tag('span', get_string('status_enabled', 'local_educambot'),
                ['class' => 'badge badge-success']);
        } else {
            $status = html_writer::tag('span', get_string('status_disabled', 'local_educambot'),
                ['class' => 'badge badge-secondary']);
        }

        // Action links.
        $actions = html_writer::link($editurl, get_string('edit', 'local_educambot'),
            ['class' => 'btn btn-sm btn-secondary mr-1']);
        $actions .= html_writer::link($duplicateurl, get_string('duplicate', 'local_educambot'),
            [
                'class' => 'btn btn-sm btn-outline-secondary mr-1',
                'onclick' => 'return confirm("' . get_string('confirmduplicaterule', 'local_educambot') . '");',
            ]);
        $actions .= html_writer::link($toggleurl,
            $rule->enabled ? get_string('disable', 'local_educambot') : get_string('enable', 'local_educambot'),
            ['class' => 'btn btn-sm btn-info mr-1']);
        $actions .= html_writer::link($deleteurl, get_string('delete', 'local_educambot'),
            [
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'return confirm("' . get_string('confirmdelete', 'local_educambot') . '");',
            ]);

        $table->data[] = [
            $catbadge,
            format_text($pattern, FORMAT_PLAIN),
            $tagsdisplay,
            $optionsbadge,
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
