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
 * The main intebchat configuration form
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/intebchat/lib.php');

/**
 * Module instance settings form
 */
class mod_intebchat_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $PAGE;

        $mform = $this->_form;
        $config = get_config('mod_intebchat');
        $type = $config->type ?: 'chat';
        
        // For dynamic assistant list updates
        $PAGE->requires->js_call_amd('mod_intebchat/settings', 'init');

        // Adding the "general" fieldset
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('intebchatname', 'mod_intebchat'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'intebchatname', 'mod_intebchat');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        // Chat settings header
        $mform->addElement('header', 'chatsettings', get_string('chatsettings', 'mod_intebchat'));
        $mform->setExpanded('chatsettings');

        // Show labels setting
        $mform->addElement('advcheckbox', 'showlabels', get_string('showlabels', 'mod_intebchat'));
        $mform->setDefault('showlabels', 1);

        // Audio settings - Only if audio is enabled globally
        if (!empty($config->enableaudio)) {
            $mform->addElement('advcheckbox', 'enableaudio', get_string('enableaudio', 'mod_intebchat'));
            $mform->setDefault('enableaudio', 0);
            $mform->addHelpButton('enableaudio', 'enableaudio', 'mod_intebchat');

            $audiomodes = [
                'text' => get_string('audiomode_text', 'mod_intebchat'),
                'audio' => get_string('audiomode_audio', 'mod_intebchat'),
                'both' => get_string('audiomode_both', 'mod_intebchat')
            ];
            
            $mform->addElement('select', 'audiomode', get_string('audiomode', 'mod_intebchat'), $audiomodes);
            $mform->setDefault('audiomode', 'text');
            $mform->addHelpButton('audiomode', 'audiomode', 'mod_intebchat');
            $mform->disabledIf('audiomode', 'enableaudio', 'eq', 0);
        }

        // Hidden field for API type (always use global setting)
        $mform->addElement('hidden', 'apitype', $type);
        $mform->setType('apitype', PARAM_TEXT);

        // Assistant name (common for all API types)
        $mform->addElement('text', 'assistantname', get_string('assistantname', 'mod_intebchat'));
        $mform->setDefault('assistantname', '');
        $mform->setType('assistantname', PARAM_TEXT);
        $mform->addHelpButton('assistantname', 'config_assistantname', 'mod_intebchat');

        // Assistant API specific settings
        if ($type === 'assistant') {
            if ($config->allowinstancesettings) {
                // Get assistants using the appropriate API key
                $apikey = $config->apikey;
                if ($this->current && !empty($this->current->apikey)) {
                    $apikey = $this->current->apikey;
                }
                $assistants = intebchat_fetch_assistants_array($apikey);
                
                if (empty($assistants)) {
                    $mform->addElement('static', 'noassistants', get_string('assistant', 'mod_intebchat'), 
                        get_string('noassistants', 'mod_intebchat'));
                } else {
                    $mform->addElement('select', 'assistant', get_string('assistant', 'mod_intebchat'), $assistants);
                    $mform->setDefault('assistant', $config->assistant ?: reset($assistants));
                    $mform->addHelpButton('assistant', 'config_assistant', 'mod_intebchat');
                }
                $mform->setType('assistant', PARAM_TEXT);

                $mform->addElement('advcheckbox', 'persistconvo', get_string('persistconvo', 'mod_intebchat'));
                $mform->setDefault('persistconvo', 1);
                $mform->addHelpButton('persistconvo', 'config_persistconvo', 'mod_intebchat');

                $mform->addElement('textarea', 'instructions', get_string('config_instructions', 'mod_intebchat'), 
                    'rows="6" cols="80"');
                $mform->setType('instructions', PARAM_TEXT);
                $mform->addHelpButton('instructions', 'config_instructions', 'mod_intebchat');
            }
        } else {
            // Chat API specific settings
            $mform->addElement('textarea', 'sourceoftruth', get_string('sourceoftruth', 'mod_intebchat'), 
                'rows="10" cols="80"');
            $mform->setType('sourceoftruth', PARAM_TEXT);
            $mform->addHelpButton('sourceoftruth', 'config_sourceoftruth', 'mod_intebchat');

            if ($config->allowinstancesettings) {
                $mform->addElement('textarea', 'prompt', get_string('prompt', 'mod_intebchat'), 
                    'rows="6" cols="80"');
                $mform->setDefault('prompt', '');
                $mform->setType('prompt', PARAM_TEXT);
                $mform->addHelpButton('prompt', 'config_prompt', 'mod_intebchat');
            }
        }

        // Advanced settings (if allowed)
        if ($config->allowinstancesettings) {
            $mform->addElement('header', 'advancedsettings', get_string('advanced', 'mod_intebchat'));
            
            // API Key (instance level)
            $mform->addElement('text', 'apikey', get_string('apikey', 'mod_intebchat'), array('size' => '60'));
            $mform->setType('apikey', PARAM_TEXT);
            $mform->addHelpButton('apikey', 'config_apikey', 'mod_intebchat');
            
            if ($type !== 'assistant') {
                // Model selection (for chat)
                $models = intebchat_get_models()['models'];
                $mform->addElement('select', 'model', get_string('model', 'mod_intebchat'), $models);
                $mform->setDefault('model', get_config('mod_intebchat', 'model'));
                $mform->setType('model', PARAM_TEXT);
                $mform->addHelpButton('model', 'config_model', 'mod_intebchat');

                // Temperature
                $mform->addElement('text', 'temperature', get_string('temperature', 'mod_intebchat'));
                $mform->setDefault('temperature', 0.5);
                $mform->setType('temperature', PARAM_FLOAT);
                $mform->addHelpButton('temperature', 'config_temperature', 'mod_intebchat');

                // Max length
                $mform->addElement('text', 'maxlength', get_string('maxlength', 'mod_intebchat'));
                $mform->setDefault('maxlength', 500);
                $mform->setType('maxlength', PARAM_INT);
                $mform->addHelpButton('maxlength', 'config_maxlength', 'mod_intebchat');

                // Top P
                $mform->addElement('text', 'topp', get_string('topp', 'mod_intebchat'));
                $mform->setDefault('topp', 1);
                $mform->setType('topp', PARAM_FLOAT);
                $mform->addHelpButton('topp', 'config_topp', 'mod_intebchat');

                // Frequency penalty
                $mform->addElement('text', 'frequency', get_string('frequency', 'mod_intebchat'));
                $mform->setDefault('frequency', 1);
                $mform->setType('frequency', PARAM_FLOAT);
                $mform->addHelpButton('frequency', 'config_frequency', 'mod_intebchat');

                // Presence penalty
                $mform->addElement('text', 'presence', get_string('presence', 'mod_intebchat'));
                $mform->setDefault('presence', 1);
                $mform->setType('presence', PARAM_FLOAT);
                $mform->addHelpButton('presence', 'config_presence', 'mod_intebchat');
            }
        }

        // Add standard elements
        $this->standard_coursemodule_elements();

        // Add standard buttons
        $this->add_action_buttons();
    }

    /**
     * Form validation
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        
        $config = get_config('mod_intebchat');
        
        // Check if API key is configured (either globally or instance level)
        if (empty($config->apikey) && empty($data['apikey'])) {
            $errors['apikey'] = get_string('apikeymissing', 'mod_intebchat');
        }
        
        // Validate API-specific required fields
        if ($data['apitype'] === 'assistant' && empty($data['assistant']) && $config->allowinstancesettings) {
            // Only error if assistants are available
            $apikey = !empty($data['apikey']) ? $data['apikey'] : $config->apikey;
            $assistants = intebchat_fetch_assistants_array($apikey);
            if (!empty($assistants)) {
                $errors['assistant'] = get_string('required');
            }
        }
        
        // Validate numeric fields
        if (!empty($data['temperature'])) {
            if ($data['temperature'] < 0 || $data['temperature'] > 2) {
                $errors['temperature'] = get_string('temperaturerange', 'mod_intebchat');
            }
        }
        
        if (!empty($data['topp'])) {
            if ($data['topp'] < 0 || $data['topp'] > 1) {
                $errors['topp'] = get_string('topprange', 'mod_intebchat');
            }
        }
        
        if (!empty($data['maxlength'])) {
            if ($data['maxlength'] < 1 || $data['maxlength'] > 4000) {
                $errors['maxlength'] = get_string('maxlengthrange', 'mod_intebchat');
            }
        }
        
        return $errors;
    }

    /**
     * Process data before displaying form
     *
     * @param array $default_values
     */
    public function data_preprocessing(&$default_values) {
        parent::data_preprocessing($default_values);
        
        // Always use global API type
        $config = get_config('mod_intebchat');
        $default_values['apitype'] = $config->type ?: 'chat';
    }

    /**
     * Process form data after submission
     *
     * @param stdClass $data
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        
        // Ensure apitype is always set from global config
        $config = get_config('mod_intebchat');
        $data->apitype = $config->type ?: 'chat';
        
        // Set defaults for unchecked checkboxes
        if (!isset($data->enableaudio)) {
            $data->enableaudio = 0;
        }
    }
}