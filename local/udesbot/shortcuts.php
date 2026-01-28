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
 * Shortcuts management page for udesbot.
 *
 * @package     local_udesbot
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @copyright   2025 OrionCloud<https://orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_udesbot\form\shortcut_form;
use local_udesbot\bot\shortcut_handler;

// Parameters.
$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Admin setup.
admin_externalpage_setup('local_udesbot_shortcuts');

$context = context_system::instance();
require_capability('local/udesbot:manage', $context);

$PAGE->set_url(new moodle_url('/local/udesbot/shortcuts.php'));
$PAGE->set_title(get_string('manageshortcuts', 'local_udesbot'));
$PAGE->set_heading(get_string('manageshortcuts', 'local_udesbot'));

$baseurl = new moodle_url('/local/udesbot/shortcuts.php');

// Process actions.
switch ($action) {
    case 'add':
    case 'edit':
        $shortcut = null;
        if ($id) {
            $shortcut = $DB->get_record('local_udesbot_shortcut', ['id' => $id], '*', MUST_EXIST);
        }

        // Build form URL with action and id to ensure correct processing on POST.
        $formurl = new moodle_url($baseurl, ['action' => $action]);
        if ($id) {
            $formurl->param('id', $id);
        }
        $form = new shortcut_form($formurl);

        if ($shortcut) {
            $form->set_data($shortcut);
        }

        if ($form->is_cancelled()) {
            redirect($baseurl);
        } else if ($data = $form->get_data()) {
            $now = time();

            $record = new stdClass();
            $record->name = $data->name;
            $record->keywords = $data->keywords;
            $record->actiontype = $data->actiontype;
            $record->description = $data->description ?? '';
            $record->icon = $data->icon ?? '';
            $record->sortorder = $data->sortorder ?? 0;
            $record->enabled = $data->enabled ?? 1;
            $record->timemodified = $now;

            if (!empty($data->id)) {
                $record->id = $data->id;
                $DB->update_record('local_udesbot_shortcut', $record);
                \core\notification::success(get_string('shortcutupdated', 'local_udesbot'));
            } else {
                $record->timecreated = $now;
                $DB->insert_record('local_udesbot_shortcut', $record);
                \core\notification::success(get_string('shortcutcreated', 'local_udesbot'));
            }

            redirect($baseurl);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading($id ? get_string('editshortcut', 'local_udesbot') : get_string('addshortcut', 'local_udesbot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;

    case 'delete':
        if (!$id) {
            redirect($baseurl);
        }

        $shortcut = $DB->get_record('local_udesbot_shortcut', ['id' => $id], '*', MUST_EXIST);

        if ($confirm) {
            require_sesskey();
            $DB->delete_records('local_udesbot_shortcut', ['id' => $id]);
            \core\notification::success(get_string('shortcutdeleted', 'local_udesbot'));
            redirect($baseurl);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteshortcut', 'local_udesbot'));
        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'local_udesbot', $shortcut->name),
            new moodle_url($baseurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $baseurl
        );
        echo $OUTPUT->footer();
        exit;

    case 'toggle':
        if (!$id) {
            redirect($baseurl);
        }
        require_sesskey();

        $shortcut = $DB->get_record('local_udesbot_shortcut', ['id' => $id], '*', MUST_EXIST);
        $shortcut->enabled = $shortcut->enabled ? 0 : 1;
        $shortcut->timemodified = time();
        $DB->update_record('local_udesbot_shortcut', $shortcut);

        redirect($baseurl);

    case 'moveup':
    case 'movedown':
        if (!$id) {
            redirect($baseurl);
        }
        require_sesskey();

        $shortcut = $DB->get_record('local_udesbot_shortcut', ['id' => $id], '*', MUST_EXIST);
        $shortcuts = $DB->get_records('local_udesbot_shortcut', [], 'sortorder ASC');
        $shortcutids = array_keys($shortcuts);
        $currentpos = array_search($id, $shortcutids);

        if ($action === 'moveup' && $currentpos > 0) {
            $swapid = $shortcutids[$currentpos - 1];
        } else if ($action === 'movedown' && $currentpos < count($shortcutids) - 1) {
            $swapid = $shortcutids[$currentpos + 1];
        } else {
            redirect($baseurl);
        }

        $swapshortcut = $shortcuts[$swapid];
        $tempsort = $shortcut->sortorder;
        $shortcut->sortorder = $swapshortcut->sortorder;
        $swapshortcut->sortorder = $tempsort;

        $DB->update_record('local_udesbot_shortcut', $shortcut);
        $DB->update_record('local_udesbot_shortcut', $swapshortcut);

        redirect($baseurl);

    default:
        // List shortcuts.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('manageshortcuts', 'local_udesbot'));

        // Add shortcut button.
        $addurl = new moodle_url($baseurl, ['action' => 'add']);
        echo html_writer::div(
            $OUTPUT->single_button($addurl, get_string('addshortcut', 'local_udesbot'), 'get'),
            'mb-3'
        );

        // Get all shortcuts.
        $shortcuts = $DB->get_records('local_udesbot_shortcut', [], 'sortorder ASC');
        $actiontypes = shortcut_handler::get_action_types();

        if (empty($shortcuts)) {
            echo $OUTPUT->notification(get_string('noshortcuts', 'local_udesbot'), 'info');
        } else {
            $table = new html_table();
            $table->head = [
                get_string('icon', 'local_udesbot'),
                get_string('shortcutname', 'local_udesbot'),
                get_string('actiontype', 'local_udesbot'),
                get_string('keywords', 'local_udesbot'),
                get_string('enabled', 'local_udesbot'),
                get_string('actions'),
            ];
            $table->attributes['class'] = 'generaltable shortcutstable';

            $shortcutcount = count($shortcuts);
            $i = 0;

            foreach ($shortcuts as $shortcut) {
                $i++;

                // Icon.
                $icon = $shortcut->icon ?: '⚡';

                // Action type.
                $typename = $actiontypes[$shortcut->actiontype] ?? $shortcut->actiontype;

                // Keywords preview.
                $keywordslines = explode("\n", $shortcut->keywords);
                $keywordspreview = implode(', ', array_slice($keywordslines, 0, 3));
                if (count($keywordslines) > 3) {
                    $keywordspreview .= '...';
                }

                // Enabled icon.
                $toggleurl = new moodle_url($baseurl, ['action' => 'toggle', 'id' => $shortcut->id, 'sesskey' => sesskey()]);
                if ($shortcut->enabled) {
                    $enabledicon = html_writer::link($toggleurl,
                        $OUTPUT->pix_icon('t/hide', get_string('disable')),
                        ['title' => get_string('disable')]
                    );
                } else {
                    $enabledicon = html_writer::link($toggleurl,
                        $OUTPUT->pix_icon('t/show', get_string('enable')),
                        ['title' => get_string('enable')]
                    );
                }

                // Actions.
                $actions = [];

                // Edit.
                $editurl = new moodle_url($baseurl, ['action' => 'edit', 'id' => $shortcut->id]);
                $actions[] = html_writer::link($editurl,
                    $OUTPUT->pix_icon('t/edit', get_string('edit')),
                    ['title' => get_string('edit')]
                );

                // Move up.
                if ($i > 1) {
                    $upurl = new moodle_url($baseurl, ['action' => 'moveup', 'id' => $shortcut->id, 'sesskey' => sesskey()]);
                    $actions[] = html_writer::link($upurl,
                        $OUTPUT->pix_icon('t/up', get_string('moveup')),
                        ['title' => get_string('moveup')]
                    );
                } else {
                    $actions[] = $OUTPUT->pix_icon('spacer', '');
                }

                // Move down.
                if ($i < $shortcutcount) {
                    $downurl = new moodle_url($baseurl, ['action' => 'movedown', 'id' => $shortcut->id, 'sesskey' => sesskey()]);
                    $actions[] = html_writer::link($downurl,
                        $OUTPUT->pix_icon('t/down', get_string('movedown')),
                        ['title' => get_string('movedown')]
                    );
                } else {
                    $actions[] = $OUTPUT->pix_icon('spacer', '');
                }

                // Delete.
                $deleteurl = new moodle_url($baseurl, ['action' => 'delete', 'id' => $shortcut->id]);
                $actions[] = html_writer::link($deleteurl,
                    $OUTPUT->pix_icon('t/delete', get_string('delete')),
                    ['title' => get_string('delete')]
                );

                $table->data[] = [
                    $icon,
                    html_writer::tag('strong', $shortcut->name) .
                        ($shortcut->description ? html_writer::empty_tag('br') . html_writer::tag('small', $shortcut->description, ['class' => 'text-muted']) : ''),
                    $typename,
                    html_writer::tag('small', $keywordspreview),
                    $enabledicon,
                    implode(' ', $actions),
                ];
            }

            echo html_writer::table($table);
        }

        // Help section.
        echo html_writer::tag('h4', get_string('shortcutshelp', 'local_udesbot'), ['class' => 'mt-4']);
        echo html_writer::tag('p', get_string('shortcutshelp_desc', 'local_udesbot'));

        // Action types help.
        echo html_writer::tag('h5', get_string('availableactiontypes', 'local_udesbot'));
        $actionlist = '<ul>';
        foreach ($actiontypes as $type => $name) {
            $actionlist .= "<li><strong>{$type}</strong>: {$name}</li>";
        }
        $actionlist .= '</ul>';
        echo $actionlist;

        echo $OUTPUT->footer();
}
