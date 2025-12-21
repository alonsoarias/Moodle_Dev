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
 * Theme form for educambot.
 *
 * @package     local_educambot
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Form for creating and editing themes.
 */
class theme_form extends \moodleform {

    /**
     * Form definition.
     */
    protected function definition() {
        $mform = $this->_form;

        // Get custom data passed from themes.php.
        $context = $this->_customdata['context'] ?? \context_system::instance();
        $themeid = $this->_customdata['themeid'] ?? 0;

        // Hidden ID for editing.
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Theme name.
        $mform->addElement('text', 'name', get_string('themename', 'local_educambot'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 50), 'maxlength', 50, 'client');

        // Color section header.
        $mform->addElement('header', 'colorsheader', get_string('colors', 'local_educambot'));

        // Primary color.
        $mform->addElement('text', 'primarycolor', get_string('primarycolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('primarycolor', PARAM_TEXT);
        $mform->addRule('primarycolor', null, 'required', null, 'client');
        $mform->setDefault('primarycolor', '#0f6fc5');
        $mform->addHelpButton('primarycolor', 'primarycolor', 'local_educambot');

        // Secondary color.
        $mform->addElement('text', 'secondarycolor', get_string('secondarycolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('secondarycolor', PARAM_TEXT);
        $mform->addRule('secondarycolor', null, 'required', null, 'client');
        $mform->setDefault('secondarycolor', '#084a8a');
        $mform->addHelpButton('secondarycolor', 'secondarycolor', 'local_educambot');

        // Text color.
        $mform->addElement('text', 'textcolor', get_string('textcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('textcolor', PARAM_TEXT);
        $mform->addRule('textcolor', null, 'required', null, 'client');
        $mform->setDefault('textcolor', '#1f2937');
        $mform->addHelpButton('textcolor', 'textcolor', 'local_educambot');

        // Background color.
        $mform->addElement('text', 'backgroundcolor', get_string('backgroundcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('backgroundcolor', PARAM_TEXT);
        $mform->addRule('backgroundcolor', null, 'required', null, 'client');
        $mform->setDefault('backgroundcolor', '#f9fafb');
        $mform->addHelpButton('backgroundcolor', 'backgroundcolor', 'local_educambot');

        // User message color.
        $mform->addElement('text', 'usercolor', get_string('usercolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('usercolor', PARAM_TEXT);
        $mform->addRule('usercolor', null, 'required', null, 'client');
        $mform->setDefault('usercolor', '#0f6fc5');
        $mform->addHelpButton('usercolor', 'usercolor', 'local_educambot');

        // Bot message color.
        $mform->addElement('text', 'botcolor', get_string('botcolor', 'local_educambot'), ['size' => 10, 'class' => 'color-picker']);
        $mform->setType('botcolor', PARAM_TEXT);
        $mform->addRule('botcolor', null, 'required', null, 'client');
        $mform->setDefault('botcolor', '#ffffff');
        $mform->addHelpButton('botcolor', 'botcolor', 'local_educambot');

        // Is default.
        $mform->addElement('advcheckbox', 'isdefault', get_string('setasdefault', 'local_educambot'));
        $mform->setDefault('isdefault', 0);

        // =============================================
        // Widget Icon Section (v1.8.1).
        // =============================================
        $mform->addElement('header', 'iconheader', get_string('widgeticonheading', 'local_educambot'));

        // Icon type.
        $iconoptions = [
            'default' => get_string('icontype_default', 'local_educambot'),
            'emoji' => get_string('icontype_emoji', 'local_educambot'),
            'bootstrap' => get_string('icontype_bootstrap', 'local_educambot'),
            'custom' => get_string('icontype_custom', 'local_educambot'),
        ];
        $mform->addElement('select', 'widgeticontype', get_string('widgeticontype', 'local_educambot'), $iconoptions);
        $mform->setDefault('widgeticontype', 'default');
        $mform->addHelpButton('widgeticontype', 'widgeticontype', 'local_educambot');

        // Emoji input (shown when type is emoji).
        $mform->addElement('text', 'widgeticonemoji', get_string('widgeticonemoji', 'local_educambot'), ['size' => 10]);
        $mform->setType('widgeticonemoji', PARAM_TEXT);
        $mform->hideIf('widgeticonemoji', 'widgeticontype', 'neq', 'emoji');
        $mform->addHelpButton('widgeticonemoji', 'widgeticonemoji', 'local_educambot');

        // Bootstrap icon input (shown when type is bootstrap).
        $mform->addElement('text', 'widgeticonbs', get_string('widgeticonbs', 'local_educambot'), ['size' => 30, 'placeholder' => 'bi-robot']);
        $mform->setType('widgeticonbs', PARAM_TEXT);
        $mform->hideIf('widgeticonbs', 'widgeticontype', 'neq', 'bootstrap');
        $mform->addHelpButton('widgeticonbs', 'widgeticonbs', 'local_educambot');

        // Custom icon file (shown when type is custom).
        $mform->addElement('filemanager', 'widgeticonfile', get_string('widgeticonfile', 'local_educambot'), null, [
            'subdirs' => 0,
            'maxbytes' => 102400, // 100KB max.
            'maxfiles' => 1,
            'accepted_types' => ['.png', '.svg', '.jpg', '.gif'],
        ]);
        $mform->hideIf('widgeticonfile', 'widgeticontype', 'neq', 'custom');
        $mform->addHelpButton('widgeticonfile', 'widgeticonfile', 'local_educambot');

        // =============================================
        // Mascot Section (v1.8.1).
        // =============================================
        $mform->addElement('header', 'mascotheader', get_string('mascotheading', 'local_educambot'));

        // Enable mascot.
        $mform->addElement('advcheckbox', 'mascotenabled', get_string('mascotenabled', 'local_educambot'));
        $mform->setDefault('mascotenabled', 1);
        $mform->addHelpButton('mascotenabled', 'mascotenabled', 'local_educambot');

        // Mascot type.
        $mascotoptions = [
            'none' => get_string('mascot_none', 'local_educambot'),
            'clippy' => get_string('mascot_clippy', 'local_educambot'),
            'robot' => get_string('mascot_robot', 'local_educambot'),
            'owl' => get_string('mascot_owl', 'local_educambot'),
            'cat' => get_string('mascot_cat', 'local_educambot'),
            'lightbulb' => get_string('mascot_lightbulb', 'local_educambot'),
            'custom' => get_string('mascot_custom', 'local_educambot'),
        ];
        $mform->addElement('select', 'mascottype', get_string('mascottype', 'local_educambot'), $mascotoptions);
        $mform->setDefault('mascottype', 'robot');
        $mform->hideIf('mascottype', 'mascotenabled', 'notchecked');
        $mform->addHelpButton('mascottype', 'mascottype', 'local_educambot');

        // Custom mascot file (SVG only, shown when type is custom).
        $mform->addElement('filemanager', 'mascotfile', get_string('mascotfile', 'local_educambot'), null, [
            'subdirs' => 0,
            'maxbytes' => 51200, // 50KB max.
            'maxfiles' => 1,
            'accepted_types' => ['.svg'],
        ]);
        $mform->hideIf('mascotfile', 'mascottype', 'neq', 'custom');
        $mform->hideIf('mascotfile', 'mascotenabled', 'notchecked');
        $mform->addHelpButton('mascotfile', 'mascotfile', 'local_educambot');

        // Action buttons.
        $this->add_action_buttons();
    }

    /**
     * Set form data - restore icon/mascot values to correct fields.
     *
     * @param stdClass|array $data Form data from database
     */
    public function set_data($data) {
        global $CFG;

        if (is_object($data)) {
            $data = (array)$data;
        }

        $context = \context_system::instance();
        $themeid = !empty($data['id']) ? $data['id'] : 0;

        // Restore widget icon value to the appropriate field based on type.
        if (!empty($data['widgeticonurl']) && !empty($data['widgeticontype'])) {
            switch ($data['widgeticontype']) {
                case 'emoji':
                    $data['widgeticonemoji'] = $data['widgeticonurl'];
                    break;
                case 'bootstrap':
                    $data['widgeticonbs'] = $data['widgeticonurl'];
                    break;
                case 'custom':
                    // For custom icons, prepare the file manager draft area.
                    // Do not use file_get_submitted_draft_itemid here - it only works for POST data.
                    break;
            }
        }

        // Prepare file manager draft areas for existing files.
        // This must be done for ALL cases, not just 'custom', to properly initialize the file managers.
        if ($themeid > 0) {
            // Prepare widget icon file draft area.
            $draftitemid = 0; // Let Moodle generate a new draft item id.
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'local_educambot',
                'widgeticon',
                $themeid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $data['widgeticonfile'] = $draftitemid;

            // Prepare mascot file draft area.
            $draftitemid = 0; // Let Moodle generate a new draft item id.
            file_prepare_draft_area(
                $draftitemid,
                $context->id,
                'local_educambot',
                'mascot',
                $themeid,
                ['subdirs' => 0, 'maxfiles' => 1]
            );
            $data['mascotfile'] = $draftitemid;
        }

        parent::set_data($data);
    }

    /**
     * Validate form data.
     *
     * @param array $data Form data.
     * @param array $files Uploaded files.
     * @return array Validation errors.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        // Validate color format.
        $colorfields = ['primarycolor', 'secondarycolor', 'textcolor', 'backgroundcolor', 'usercolor', 'botcolor'];
        foreach ($colorfields as $field) {
            if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $data[$field])) {
                $errors[$field] = get_string('invalidcolor', 'local_educambot');
            }
        }

        return $errors;
    }
}
