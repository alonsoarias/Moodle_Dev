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
 * Manage options for a specific rule.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Get rule ID.
$ruleid = required_param('ruleid', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

admin_externalpage_setup('local_educambot_manage');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

// Verify rule exists.
$rule = $DB->get_record('local_educambot_rule', ['id' => $ruleid], '*', MUST_EXIST);

$PAGE->set_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('manageoptions', 'local_educambot'));
$PAGE->set_heading(get_string('manageoptions', 'local_educambot'));

// Breadcrumb.
$PAGE->navbar->add(get_string('managerules', 'local_educambot'),
    new moodle_url('/local/educambot/manage.php'));
$PAGE->navbar->add(get_string('manageoptions', 'local_educambot'));

// Handle delete action.
if ($action === 'delete' && $id > 0) {
    require_sesskey();
    $DB->delete_records('local_educambot_option', ['id' => $id]);
    redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]),
        get_string('optiondeleted', 'local_educambot'),
        null,
        \core\output\notification::NOTIFY_SUCCESS);
}

// Handle toggle enabled/disabled.
if ($action === 'toggle' && $id > 0) {
    require_sesskey();
    $option = $DB->get_record('local_educambot_option', ['id' => $id], '*', MUST_EXIST);
    $option->enabled = $option->enabled ? 0 : 1;
    $DB->update_record('local_educambot_option', $option);
    redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]));
}

// Handle move up/down.
if ($action === 'moveup' && $id > 0) {
    require_sesskey();
    $option = $DB->get_record('local_educambot_option', ['id' => $id], '*', MUST_EXIST);
    // Find the option above.
    $above = $DB->get_record_sql(
        'SELECT * FROM {local_educambot_option}
         WHERE ruleid = ? AND sortorder < ?
         ORDER BY sortorder DESC LIMIT 1',
        [$ruleid, $option->sortorder]);
    if ($above) {
        // Swap sort orders.
        $tempsort = $option->sortorder;
        $option->sortorder = $above->sortorder;
        $above->sortorder = $tempsort;
        $DB->update_record('local_educambot_option', $option);
        $DB->update_record('local_educambot_option', $above);
    }
    redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]));
}

if ($action === 'movedown' && $id > 0) {
    require_sesskey();
    $option = $DB->get_record('local_educambot_option', ['id' => $id], '*', MUST_EXIST);
    // Find the option below.
    $below = $DB->get_record_sql(
        'SELECT * FROM {local_educambot_option}
         WHERE ruleid = ? AND sortorder > ?
         ORDER BY sortorder ASC LIMIT 1',
        [$ruleid, $option->sortorder]);
    if ($below) {
        // Swap sort orders.
        $tempsort = $option->sortorder;
        $option->sortorder = $below->sortorder;
        $below->sortorder = $tempsort;
        $DB->update_record('local_educambot_option', $option);
        $DB->update_record('local_educambot_option', $below);
    }
    redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]));
}

// Handle add/edit form.
if ($action === 'edit' || $action === 'add') {
    $mform = new \local_educambot\form\option_form();

    // Set default rule ID.
    $mform->set_data(['ruleid' => $ruleid]);

    // Load existing data if editing.
    if ($id > 0) {
        $option = $DB->get_record('local_educambot_option', ['id' => $id], '*', MUST_EXIST);
        $mform->set_data($option);
    }

    if ($mform->is_cancelled()) {
        redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]));
    } else if ($data = $mform->get_data()) {
        if ($data->id > 0) {
            // Update existing option.
            $DB->update_record('local_educambot_option', $data);
            $message = get_string('optionupdated', 'local_educambot');
        } else {
            // Create new option - set sortorder to max + 1.
            $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {local_educambot_option} WHERE ruleid = ?', [$ruleid]);
            $data->sortorder = ($maxsort ?? 0) + 1;
            $DB->insert_record('local_educambot_option', $data);
            $message = get_string('optioncreated', 'local_educambot');
        }

        redirect(new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid]),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->heading($id > 0 ? get_string('editoption', 'local_educambot') : get_string('addoption', 'local_educambot'));

    // Show which rule we're editing options for.
    $rulepattern = strlen($rule->pattern) > 60 ? substr($rule->pattern, 0, 57) . '...' : $rule->pattern;
    echo html_writer::tag('div', get_string('optionsfor', 'local_educambot') . ': <strong>' . s($rulepattern) . '</strong>',
        ['class' => 'alert alert-info']);

    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// List all options (default action).
echo $OUTPUT->header();

// Back to rules link.
echo html_writer::link(new moodle_url('/local/educambot/manage.php'),
    '&laquo; ' . get_string('backtorules', 'local_educambot'),
    ['class' => 'btn btn-link mb-3']);

// Show which rule we're editing options for.
$rulepattern = strlen($rule->pattern) > 60 ? substr($rule->pattern, 0, 57) . '...' : $rule->pattern;
echo html_writer::tag('div', get_string('optionsfor', 'local_educambot') . ': <strong>' . s($rulepattern) . '</strong>',
    ['class' => 'alert alert-info']);

// Add option button.
$addurl = new moodle_url('/local/educambot/manage_options.php', ['ruleid' => $ruleid, 'action' => 'add']);
echo html_writer::link($addurl, get_string('addoption', 'local_educambot'),
    ['class' => 'btn btn-primary mb-3']);

// Get all options for this rule.
$options = $DB->get_records('local_educambot_option', ['ruleid' => $ruleid], 'sortorder ASC');

if (empty($options)) {
    echo $OUTPUT->notification(get_string('nooptions', 'local_educambot'), 'info');
} else {
    // Create table.
    $table = new html_table();
    $table->head = [
        get_string('optionorder', 'local_educambot'),
        get_string('icon', 'local_educambot'),
        get_string('optiontext', 'local_educambot'),
        get_string('targetrule', 'local_educambot'),
        get_string('status_header', 'local_educambot'),
        get_string('actions_header', 'local_educambot'),
    ];
    $table->attributes['class'] = 'generaltable';

    $optionscount = count($options);
    $i = 0;
    foreach ($options as $option) {
        $i++;
        $editurl = new moodle_url('/local/educambot/manage_options.php',
            ['ruleid' => $ruleid, 'action' => 'edit', 'id' => $option->id]);
        $deleteurl = new moodle_url('/local/educambot/manage_options.php',
            ['ruleid' => $ruleid, 'action' => 'delete', 'id' => $option->id, 'sesskey' => sesskey()]);
        $toggleurl = new moodle_url('/local/educambot/manage_options.php',
            ['ruleid' => $ruleid, 'action' => 'toggle', 'id' => $option->id, 'sesskey' => sesskey()]);
        $moveupurl = new moodle_url('/local/educambot/manage_options.php',
            ['ruleid' => $ruleid, 'action' => 'moveup', 'id' => $option->id, 'sesskey' => sesskey()]);
        $movedownurl = new moodle_url('/local/educambot/manage_options.php',
            ['ruleid' => $ruleid, 'action' => 'movedown', 'id' => $option->id, 'sesskey' => sesskey()]);

        // Get target rule pattern.
        $targetpattern = '';
        if ($option->targetruleid) {
            $targetrule = $DB->get_record('local_educambot_rule', ['id' => $option->targetruleid], 'pattern');
            if ($targetrule) {
                $targetpattern = strlen($targetrule->pattern) > 40 ?
                    substr($targetrule->pattern, 0, 37) . '...' : $targetrule->pattern;
            }
        }

        // Status badge.
        if ($option->enabled) {
            $status = html_writer::tag('span', get_string('status_enabled', 'local_educambot'),
                ['class' => 'badge badge-success']);
        } else {
            $status = html_writer::tag('span', get_string('status_disabled', 'local_educambot'),
                ['class' => 'badge badge-secondary']);
        }

        // Move up/down buttons.
        $movebtns = '';
        if ($i > 1) {
            $movebtns .= html_writer::link($moveupurl, '&#9650;', ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('moveup', 'local_educambot')]);
        }
        if ($i < $optionscount) {
            $movebtns .= ' ' . html_writer::link($movedownurl, '&#9660;', ['class' => 'btn btn-sm btn-outline-secondary', 'title' => get_string('movedown', 'local_educambot')]);
        }

        // Action links.
        $actions = html_writer::link($editurl, get_string('edit', 'local_educambot'),
            ['class' => 'btn btn-sm btn-secondary']);
        $actions .= ' ';
        $actions .= html_writer::link($toggleurl,
            $option->enabled ? get_string('disable', 'local_educambot') : get_string('enable', 'local_educambot'),
            ['class' => 'btn btn-sm btn-info']);
        $actions .= ' ';
        $actions .= html_writer::link($deleteurl, get_string('delete', 'local_educambot'),
            [
                'class' => 'btn btn-sm btn-danger',
                'onclick' => 'return confirm("' . get_string('confirmdeleteoption', 'local_educambot') . '");',
            ]);

        $table->data[] = [
            $movebtns,
            $option->icon ? $option->icon : '-',
            format_text($option->text, FORMAT_PLAIN),
            format_text($targetpattern, FORMAT_PLAIN),
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
