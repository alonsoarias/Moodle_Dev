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
 * Form for editing report_customcajasan block instances.
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class block_report_customcajasan_edit_form
 *
 * Provides instance configuration options for the block.
 */
class block_report_customcajasan_edit_form extends block_edit_form {

    /**
     * Add block-specific configuration fields to the form.
     *
     * @param MoodleQuickForm $mform Form object to add elements to.
     */
    protected function specific_definition($mform) {
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $options = $this->block->get_available_info_options();
        $mform->addElement(
            'select',
            'config_displayoptions',
            get_string('config_displayoptions', 'block_report_customcajasan'),
            $options,
            ['multiple' => true, 'size' => count($options)]
        );
        $mform->addHelpButton('config_displayoptions', 'config_displayoptions', 'block_report_customcajasan');
        $mform->setDefault('config_displayoptions', [
            block_report_customcajasan::INFO_REPORT_LINK,
            block_report_customcajasan::INFO_INSTRUCTIONS,
        ]);

        $mform->addElement(
            'textarea',
            'config_custommessage',
            get_string('config_custommessage', 'block_report_customcajasan'),
            ['rows' => 4]
        );
        $mform->setType('config_custommessage', PARAM_TEXT);
        $mform->addHelpButton('config_custommessage', 'config_custommessage', 'block_report_customcajasan');
    }

    /**
     * Populate the form with default configuration data.
     *
     * @param stdClass $defaults Defaults passed from the block instance.
     */
    public function set_data($defaults) {
        if (!empty($this->block->config)) {
            if (!empty($this->block->config->displayoptions)) {
                $defaults->config_displayoptions = $this->block->config->displayoptions;
            }
            if (isset($this->block->config->custommessage)) {
                $defaults->config_custommessage = $this->block->config->custommessage;
            }
        }

        parent::set_data($defaults);
    }

    /**
     * Display the configuration form when the block is being added.
     *
     * @return bool
     */
    public static function display_form_when_adding(): bool {
        return true;
    }
}

