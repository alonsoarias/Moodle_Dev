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
 * @copyright   2025 EducamBot Team
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

        // Define color fields with defaults.
        $colorfields = [
            'primarycolor' => '#0f6fc5',
            'secondarycolor' => '#084a8a',
            'textcolor' => '#1f2937',
            'backgroundcolor' => '#f9fafb',
            'usercolor' => '#0f6fc5',
            'botcolor' => '#ffffff',
        ];

        // Add each color field with an HTML5 color input type.
        // Using static element + text field approach for better UX.
        foreach ($colorfields as $field => $default) {
            // Add the text input field (this is the actual form field that gets submitted).
            $mform->addElement('text', $field, get_string($field, 'local_educambot'), [
                'size' => 10,
                'maxlength' => 7,
                'class' => 'educambot-color-text',
                'data-colorfield' => $field,
            ]);
            $mform->setType($field, PARAM_TEXT);
            $mform->addRule($field, null, 'required', null, 'client');
            $mform->setDefault($field, $default);
            $mform->addHelpButton($field, $field, 'local_educambot');
        }

        // Add JavaScript to enhance text fields with color pickers.
        $mform->addElement('html', '
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var colorFields = ["primarycolor", "secondarycolor", "textcolor", "backgroundcolor", "usercolor", "botcolor"];
                colorFields.forEach(function(field) {
                    var textInput = document.getElementById("id_" + field);
                    if (!textInput) return;

                    // Create color picker input.
                    var colorPicker = document.createElement("input");
                    colorPicker.type = "color";
                    colorPicker.id = "id_" + field + "_picker";
                    colorPicker.style.cssText = "width:40px;height:34px;padding:2px;cursor:pointer;vertical-align:middle;border:1px solid #ced4da;border-radius:4px;margin-right:8px;";
                    colorPicker.value = textInput.value || "#000000";

                    // Insert color picker before text input.
                    textInput.parentNode.insertBefore(colorPicker, textInput);

                    // Style the text input.
                    textInput.style.cssText = "font-family:monospace;width:80px;";

                    // Sync color picker to text input.
                    colorPicker.addEventListener("input", function() {
                        textInput.value = this.value;
                    });
                    colorPicker.addEventListener("change", function() {
                        textInput.value = this.value;
                    });

                    // Sync text input to color picker.
                    textInput.addEventListener("input", function() {
                        if (/^#[0-9A-Fa-f]{6}$/i.test(this.value)) {
                            colorPicker.value = this.value;
                        }
                    });
                    textInput.addEventListener("change", function() {
                        if (/^#[0-9A-Fa-f]{6}$/i.test(this.value)) {
                            colorPicker.value = this.value;
                        }
                    });

                    // Initialize color picker with current value.
                    if (textInput.value && /^#[0-9A-Fa-f]{6}$/i.test(textInput.value)) {
                        colorPicker.value = textInput.value;
                    }
                });
            });
            </script>
        ');

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
            'fontawesome' => get_string('icontype_fontawesome', 'local_educambot'),
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

        // Font Awesome input (shown when type is fontawesome).
        $mform->addElement('text', 'widgeticonfa', get_string('widgeticonfa', 'local_educambot'), ['size' => 30, 'placeholder' => 'fa-robot']);
        $mform->setType('widgeticonfa', PARAM_TEXT);
        $mform->hideIf('widgeticonfa', 'widgeticontype', 'neq', 'fontawesome');
        $mform->addHelpButton('widgeticonfa', 'widgeticonfa', 'local_educambot');

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
            'custom' => get_string('mascot_custom', 'local_educambot'),
        ];
        $mform->addElement('select', 'mascottype', get_string('mascottype', 'local_educambot'), $mascotoptions);
        $mform->setDefault('mascottype', 'clippy');
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
                case 'fontawesome':
                    $data['widgeticonfa'] = $data['widgeticonurl'];
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
