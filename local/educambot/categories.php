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
 * Manage categories for the chatbot knowledge base.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_educambot\form\category_form;

require_login();
require_capability('local/educambot:manage', context_system::instance());

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/educambot/categories.php'));
$PAGE->set_title(get_string('categories', 'local_educambot'));
$PAGE->set_heading(get_string('categories', 'local_educambot'));

admin_externalpage_setup('local_educambot_categories');

// Handle actions.
switch ($action) {
    case 'add':
    case 'edit':
        $categoryid = ($action === 'edit') ? $id : 0;

        $form = new category_form(null, ['categoryid' => $categoryid]);

        if ($action === 'edit' && $id > 0) {
            $category = $DB->get_record('local_educambot_category', ['id' => $id], '*', MUST_EXIST);
            $form->set_data($category);
        }

        if ($form->is_cancelled()) {
            redirect(new moodle_url('/local/educambot/categories.php'));
        } else if ($data = $form->get_data()) {
            $now = time();

            $record = new stdClass();
            $record->name = $data->name;
            $record->description = $data->description;
            $record->parent = $data->parent ?: null;
            $record->sortorder = $data->sortorder;
            $record->enabled = $data->enabled;
            $record->timemodified = $now;

            if (!empty($data->id)) {
                $record->id = $data->id;
                $DB->update_record('local_educambot_category', $record);
                $message = get_string('categoryupdated', 'local_educambot');
            } else {
                $record->timecreated = $now;
                $DB->insert_record('local_educambot_category', $record);
                $message = get_string('categorycreated', 'local_educambot');
            }

            redirect(new moodle_url('/local/educambot/categories.php'), $message, null,
                \core\output\notification::NOTIFY_SUCCESS);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string($action . 'category', 'local_educambot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;

    case 'delete':
        require_sesskey();

        $category = $DB->get_record('local_educambot_category', ['id' => $id], '*', MUST_EXIST);

        // Check if category has rules.
        $rulecount = $DB->count_records('local_educambot_rule', ['categoryid' => $id]);
        if ($rulecount > 0) {
            redirect(new moodle_url('/local/educambot/categories.php'),
                get_string('categoryhasrules', 'local_educambot', $rulecount),
                null, \core\output\notification::NOTIFY_ERROR);
        }

        // Check if category has child categories.
        $childcount = $DB->count_records('local_educambot_category', ['parent' => $id]);
        if ($childcount > 0) {
            redirect(new moodle_url('/local/educambot/categories.php'),
                get_string('categoryhaschildren', 'local_educambot', $childcount),
                null, \core\output\notification::NOTIFY_ERROR);
        }

        $DB->delete_records('local_educambot_category', ['id' => $id]);
        redirect(new moodle_url('/local/educambot/categories.php'),
            get_string('categorydeleted', 'local_educambot'),
            null, \core\output\notification::NOTIFY_SUCCESS);

    case 'toggle':
        require_sesskey();

        $category = $DB->get_record('local_educambot_category', ['id' => $id], '*', MUST_EXIST);
        $newstatus = $category->enabled ? 0 : 1;
        $DB->set_field('local_educambot_category', 'enabled', $newstatus, ['id' => $id]);

        // Also toggle all rules in this category.
        $DB->set_field('local_educambot_rule', 'enabled', $newstatus, ['categoryid' => $id]);

        redirect(new moodle_url('/local/educambot/categories.php'));
}

// Display list of categories.
echo $OUTPUT->header();

// Add category button.
$addurl = new moodle_url('/local/educambot/categories.php', ['action' => 'add']);
echo html_writer::div(
    html_writer::link($addurl, get_string('addcategory', 'local_educambot'), ['class' => 'btn btn-primary']),
    'mb-3'
);

// Get all categories.
$categories = $DB->get_records('local_educambot_category', null, 'sortorder ASC, name ASC');

if (empty($categories)) {
    echo $OUTPUT->notification(get_string('nocategories', 'local_educambot'), 'info');
} else {
    // Build hierarchical display.
    $categorylist = [];
    $toplevel = [];

    foreach ($categories as $cat) {
        $categorylist[$cat->id] = $cat;
        if (empty($cat->parent)) {
            $toplevel[] = $cat->id;
        }
    }

    // Function to get children.
    $getchildren = function($parentid) use ($categories) {
        $children = [];
        foreach ($categories as $cat) {
            if ($cat->parent == $parentid) {
                $children[] = $cat->id;
            }
        }
        return $children;
    };

    // Build table.
    $table = new html_table();
    $table->head = [
        get_string('categoryname', 'local_educambot'),
        get_string('categorydescription', 'local_educambot'),
        get_string('rules', 'local_educambot'),
        get_string('status_header', 'local_educambot'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable';

    // Recursive function to add rows.
    $addcategoryrow = function($catid, $level = 0) use (&$addcategoryrow, $categorylist, $getchildren, $DB, &$table) {
        $cat = $categorylist[$catid];
        $rulecount = $DB->count_records('local_educambot_rule', ['categoryid' => $catid]);

        // Indent based on level.
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
        $icon = $level > 0 ? '└── ' : '';

        // URLs.
        $editurl = new moodle_url('/local/educambot/categories.php', ['action' => 'edit', 'id' => $cat->id]);
        $deleteurl = new moodle_url('/local/educambot/categories.php',
            ['action' => 'delete', 'id' => $cat->id, 'sesskey' => sesskey()]);
        $toggleurl = new moodle_url('/local/educambot/categories.php',
            ['action' => 'toggle', 'id' => $cat->id, 'sesskey' => sesskey()]);
        $rulesurl = new moodle_url('/local/educambot/manage.php', ['categoryid' => $cat->id]);

        // Status badge.
        if ($cat->enabled) {
            $status = html_writer::tag('span', get_string('status_enabled', 'local_educambot'),
                ['class' => 'badge badge-success']);
            $toggletext = get_string('disable', 'local_educambot');
        } else {
            $status = html_writer::tag('span', get_string('status_disabled', 'local_educambot'),
                ['class' => 'badge badge-secondary']);
            $toggletext = get_string('enable', 'local_educambot');
        }

        // Actions.
        $actions = html_writer::link($editurl, get_string('edit', 'local_educambot'),
            ['class' => 'btn btn-sm btn-secondary mr-1']) . ' ';
        $actions .= html_writer::link($toggleurl, $toggletext,
            ['class' => 'btn btn-sm btn-outline-secondary mr-1']) . ' ';
        $actions .= html_writer::link($deleteurl, get_string('delete', 'local_educambot'),
            [
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'return confirm("' . get_string('confirmdelete', 'local_educambot') . '");',
            ]);

        // Rules link.
        $ruleslink = html_writer::link($rulesurl,
            html_writer::tag('span', $rulecount, ['class' => 'badge badge-info']) .
            ' ' . get_string('viewrules', 'local_educambot'),
            ['class' => 'btn btn-sm btn-outline-info']);

        $table->data[] = [
            $indent . $icon . format_text($cat->name, FORMAT_PLAIN),
            format_text($cat->description ?? '', FORMAT_PLAIN),
            $ruleslink,
            $status,
            $actions,
        ];

        // Add children.
        $children = $getchildren($catid);
        foreach ($children as $childid) {
            $addcategoryrow($childid, $level + 1);
        }
    };

    // Add top-level categories.
    foreach ($toplevel as $catid) {
        $addcategoryrow($catid);
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
