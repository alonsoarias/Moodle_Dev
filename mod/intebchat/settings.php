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
 * Plugin global settings
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // General settings header
    $settings->add(new admin_setting_heading(
        'mod_intebchat/generalheading',
        get_string('generalsettings', 'mod_intebchat'),
        get_string('generalsettingsdesc', 'mod_intebchat')
    ));

    // Global API Key
    $settings->add(new admin_setting_configtext(
        'mod_intebchat/apikey',
        get_string('apikey', 'mod_intebchat'),
        get_string('apikeydesc', 'mod_intebchat'),
        '',
        PARAM_TEXT
    ));

    // Default API Type (only chat and assistant now)
    $settings->add(new admin_setting_configselect(
        'mod_intebchat/type',
        get_string('type', 'mod_intebchat'),
        get_string('typedesc', 'mod_intebchat'),
        'chat',
        ['chat' => 'Chat API', 'assistant' => 'Assistant API']
    ));

    // Restrict usage to logged-in users
    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/restrictusage',
        get_string('restrictusage', 'mod_intebchat'),
        get_string('restrictusagedesc', 'mod_intebchat'),
        1
    ));

    // Enable logging
    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/logging',
        get_string('logging', 'mod_intebchat'),
        get_string('loggingdesc', 'mod_intebchat'),
        0
    ));

    // Allow instance-level settings
    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/allowinstancesettings',
        get_string('allowinstancesettings', 'mod_intebchat'),
        get_string('allowinstancesettingsdesc', 'mod_intebchat'),
        0
    ));

    // Token limit settings header
    $settings->add(new admin_setting_heading(
        'mod_intebchat/tokenlimitheading',
        get_string('tokenlimitsettings', 'mod_intebchat'),
        get_string('tokenlimitsettingsdesc', 'mod_intebchat')
    ));

    // Enable token limit
    $settings->add(new admin_setting_configcheckbox(
        'mod_intebchat/enabletokenlimit',
        get_string('enabletokenlimit', 'mod_intebchat'),
        get_string('enabletokenlimitdesc', 'mod_intebchat'),
        0
    ));

    // Maximum tokens per user
    $settings->add(new admin_setting_configtext(
        'mod_intebchat/maxtokensperuser',
        get_string('maxtokensperuser', 'mod_intebchat'),
        get_string('maxtokensperuserdesc', 'mod_intebchat'),
        10000,
        PARAM_INT
    ));

    // Token limit period
    $settings->add(new admin_setting_configselect(
        'mod_intebchat/tokenlimitperiod',
        get_string('tokenlimitperiod', 'mod_intebchat'),
        get_string('tokenlimitperioddesc', 'mod_intebchat'),
        'day',
        [
            'hour' => get_string('hour'),
            'day' => get_string('day'),
            'week' => get_string('week'),
            'month' => get_string('month')
        ]
    ));

    // Default values header - Only global defaults that aren't in mod_form
    $settings->add(new admin_setting_heading(
        'mod_intebchat/defaultsheading',
        get_string('defaultvalues', 'mod_intebchat'),
        get_string('defaultvaluesdesc', 'mod_intebchat')
    ));

    // Default assistant name
    $settings->add(new admin_setting_configtext(
        'mod_intebchat/assistantname',
        get_string('assistantname', 'mod_intebchat'),
        get_string('assistantnamedesc', 'mod_intebchat'),
        get_string('defaultassistantname', 'mod_intebchat'),
        PARAM_TEXT
    ));

    // API-specific default settings based on selected type
    require_once($CFG->dirroot . '/mod/intebchat/lib.php');
    $type = get_config('mod_intebchat', 'type') ?: 'chat';

    // Assistant API defaults
    if ($type === 'assistant') {
        $settings->add(new admin_setting_heading(
            'mod_intebchat/assistantdefaults',
            get_string('assistantheading', 'mod_intebchat'),
            get_string('assistantheadingdesc', 'mod_intebchat')
        ));

        $assistant_array = intebchat_fetch_assistants_array();
        if (count($assistant_array)) {
            $settings->add(new admin_setting_configselect(
                'mod_intebchat/assistant',
                get_string('assistant', 'mod_intebchat'),
                get_string('assistantdesc', 'mod_intebchat'),
                count($assistant_array) ? reset($assistant_array) : null,
                $assistant_array
            ));
        } else {
            $settings->add(new admin_setting_description(
                'mod_intebchat/noassistants',
                get_string('assistant', 'mod_intebchat'),
                get_string('noassistants', 'mod_intebchat')
            ));
        }

        $settings->add(new admin_setting_configcheckbox(
            'mod_intebchat/persistconvo',
            get_string('persistconvo', 'mod_intebchat'),
            get_string('persistconvodesc', 'mod_intebchat'),
            1
        ));
    }

    // Chat API defaults
    if ($type === 'chat') {
        $settings->add(new admin_setting_heading(
            'mod_intebchat/chatdefaults',
            get_string('chatheading', 'mod_intebchat'),
            get_string('chatheadingdesc', 'mod_intebchat')
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_intebchat/prompt',
            get_string('prompt', 'mod_intebchat'),
            get_string('promptdesc', 'mod_intebchat'),
            get_string('defaultprompt', 'mod_intebchat'),
            PARAM_TEXT
        ));

        $settings->add(new admin_setting_configtextarea(
            'mod_intebchat/sourceoftruth',
            get_string('sourceoftruth', 'mod_intebchat'),
            get_string('sourceoftruthdesc', 'mod_intebchat'),
            '',
            PARAM_TEXT
        ));

        $models = intebchat_get_models()['models'];
        $settings->add(new admin_setting_configselect(
            'mod_intebchat/model',
            get_string('model', 'mod_intebchat'),
            get_string('modeldesc', 'mod_intebchat'),
            'gpt-4o-mini',
            $models
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/temperature',
            get_string('temperature', 'mod_intebchat'),
            get_string('temperaturedesc', 'mod_intebchat'),
            0.5,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/maxlength',
            get_string('maxlength', 'mod_intebchat'),
            get_string('maxlengthdesc', 'mod_intebchat'),
            500,
            PARAM_INT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/topp',
            get_string('topp', 'mod_intebchat'),
            get_string('toppdesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/frequency',
            get_string('frequency', 'mod_intebchat'),
            get_string('frequencydesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));

        $settings->add(new admin_setting_configtext(
            'mod_intebchat/presence',
            get_string('presence', 'mod_intebchat'),
            get_string('presencedesc', 'mod_intebchat'),
            1,
            PARAM_FLOAT
        ));
    }
}