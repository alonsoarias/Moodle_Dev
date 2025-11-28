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
 * Theme management page for educambot.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

use local_educambot\form\theme_form;

// Parameters.
$action = optional_param('action', 'list', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Admin setup.
admin_externalpage_setup('local_educambot_themes');

$context = context_system::instance();
require_capability('local/educambot:manage', $context);

$PAGE->set_url(new moodle_url('/local/educambot/themes.php'));
$PAGE->set_title(get_string('managethemes', 'local_educambot'));
$PAGE->set_heading(get_string('managethemes', 'local_educambot'));

$baseurl = new moodle_url('/local/educambot/themes.php');

// Process actions.
switch ($action) {
    case 'add':
    case 'edit':
        $theme = null;
        if ($id) {
            $theme = $DB->get_record('local_educambot_theme', ['id' => $id], '*', MUST_EXIST);
        }

        $form = new theme_form($baseurl);

        if ($theme) {
            $form->set_data($theme);
        }

        if ($form->is_cancelled()) {
            redirect($baseurl);
        } else if ($data = $form->get_data()) {
            $now = time();

            $record = new stdClass();
            $record->name = $data->name;
            $record->primarycolor = $data->primarycolor;
            $record->secondarycolor = $data->secondarycolor;
            $record->textcolor = $data->textcolor;
            $record->backgroundcolor = $data->backgroundcolor;
            $record->usercolor = $data->usercolor;
            $record->botcolor = $data->botcolor;
            $record->isdefault = $data->isdefault ?? 0;
            $record->timemodified = $now;

            if (!empty($data->id)) {
                $record->id = $data->id;
                $DB->update_record('local_educambot_theme', $record);
                \core\notification::success(get_string('themeupdated', 'local_educambot'));
            } else {
                $record->timecreated = $now;
                $DB->insert_record('local_educambot_theme', $record);
                \core\notification::success(get_string('themecreated', 'local_educambot'));
            }

            // If this theme is set as default, unset others.
            if ($record->isdefault) {
                $DB->execute("UPDATE {local_educambot_theme} SET isdefault = 0 WHERE id != ?", [$record->id ?? $DB->get_field('local_educambot_theme', 'MAX(id)', [])]);
            }

            redirect($baseurl);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading($id ? get_string('edittheme', 'local_educambot') : get_string('addtheme', 'local_educambot'));
        $form->display();
        echo $OUTPUT->footer();
        exit;

    case 'delete':
        if (!$id) {
            redirect($baseurl);
        }

        $theme = $DB->get_record('local_educambot_theme', ['id' => $id], '*', MUST_EXIST);

        // Cannot delete default theme.
        if ($theme->isdefault) {
            \core\notification::error(get_string('cannotdeletedefault', 'local_educambot'));
            redirect($baseurl);
        }

        if ($confirm) {
            require_sesskey();
            $DB->delete_records('local_educambot_theme', ['id' => $id]);
            \core\notification::success(get_string('themedeleted', 'local_educambot'));
            redirect($baseurl);
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deletetheme', 'local_educambot'));
        echo $OUTPUT->confirm(
            get_string('confirmdelete', 'local_educambot', $theme->name),
            new moodle_url($baseurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
            $baseurl
        );
        echo $OUTPUT->footer();
        exit;

    case 'setdefault':
        if (!$id) {
            redirect($baseurl);
        }
        require_sesskey();

        // Unset all defaults.
        $DB->execute("UPDATE {local_educambot_theme} SET isdefault = 0");
        // Set this as default.
        $DB->set_field('local_educambot_theme', 'isdefault', 1, ['id' => $id]);

        \core\notification::success(get_string('themesetasdefault', 'local_educambot'));
        redirect($baseurl);

    default:
        // List themes.
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('managethemes', 'local_educambot'));

        // Add theme button.
        $addurl = new moodle_url($baseurl, ['action' => 'add']);
        echo html_writer::div(
            $OUTPUT->single_button($addurl, get_string('addtheme', 'local_educambot'), 'get'),
            'mb-3'
        );

        // Get all themes.
        $themes = $DB->get_records('local_educambot_theme', [], 'isdefault DESC, name ASC');

        if (empty($themes)) {
            echo $OUTPUT->notification(get_string('nothemes', 'local_educambot'), 'info');
        } else {
            echo html_writer::start_tag('div', ['class' => 'row']);

            foreach ($themes as $theme) {
                echo html_writer::start_tag('div', ['class' => 'col-md-4 mb-4']);
                echo html_writer::start_tag('div', ['class' => 'card', 'style' => 'border: 2px solid ' . $theme->primarycolor]);

                // Theme preview.
                echo html_writer::start_tag('div', [
                    'class' => 'card-body',
                    'style' => 'background-color: ' . $theme->backgroundcolor . '; color: ' . $theme->textcolor,
                ]);
                echo html_writer::tag('h5', $theme->name, ['class' => 'card-title']);

                if ($theme->isdefault) {
                    echo html_writer::tag('span', get_string('default'), ['class' => 'badge badge-success']);
                }

                // Color preview boxes.
                echo html_writer::start_tag('div', ['class' => 'mt-2']);
                echo html_writer::tag('span', '&nbsp;&nbsp;&nbsp;', [
                    'style' => 'background-color: ' . $theme->primarycolor . '; display: inline-block; width: 30px; height: 20px; border-radius: 3px;',
                    'title' => 'Primary',
                ]);
                echo html_writer::tag('span', '&nbsp;&nbsp;&nbsp;', [
                    'style' => 'background-color: ' . $theme->secondarycolor . '; display: inline-block; width: 30px; height: 20px; border-radius: 3px; margin-left: 5px;',
                    'title' => 'Secondary',
                ]);
                echo html_writer::tag('span', '&nbsp;&nbsp;&nbsp;', [
                    'style' => 'background-color: ' . $theme->usercolor . '; display: inline-block; width: 30px; height: 20px; border-radius: 3px; margin-left: 5px;',
                    'title' => 'User message',
                ]);
                echo html_writer::tag('span', '&nbsp;&nbsp;&nbsp;', [
                    'style' => 'background-color: ' . $theme->botcolor . '; display: inline-block; width: 30px; height: 20px; border-radius: 3px; margin-left: 5px; border: 1px solid #ccc;',
                    'title' => 'Bot message',
                ]);
                echo html_writer::end_tag('div');

                echo html_writer::end_tag('div');

                // Actions.
                echo html_writer::start_tag('div', ['class' => 'card-footer']);

                $editurl = new moodle_url($baseurl, ['action' => 'edit', 'id' => $theme->id]);
                echo html_writer::link($editurl, get_string('edit'), ['class' => 'btn btn-sm btn-primary']);

                if (!$theme->isdefault) {
                    $setdefaulturl = new moodle_url($baseurl, ['action' => 'setdefault', 'id' => $theme->id, 'sesskey' => sesskey()]);
                    echo html_writer::link($setdefaulturl, get_string('setasdefault', 'local_educambot'), ['class' => 'btn btn-sm btn-secondary ml-1']);

                    $deleteurl = new moodle_url($baseurl, ['action' => 'delete', 'id' => $theme->id]);
                    echo html_writer::link($deleteurl, get_string('delete'), ['class' => 'btn btn-sm btn-danger ml-1']);
                }

                echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
                echo html_writer::end_tag('div');
            }

            echo html_writer::end_tag('div');
        }

        echo $OUTPUT->footer();
}
