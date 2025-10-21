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

require_once(__DIR__ . '/lib.php');

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
        global $USER, $PAGE;

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

        $parentcontext = null;
        if (!empty($this->block->instance->parentcontextid)) {
            $parentcontext = context::instance_by_id($this->block->instance->parentcontextid, IGNORE_MISSING);
        } else if (!empty($this->block->page->context)) {
            $parentcontext = $this->block->page->context;
        }

        $iscoursecontext = $parentcontext && $parentcontext->contextlevel === CONTEXT_COURSE;

        $clearelements = [];

        if ($iscoursecontext) {
            $mform->addElement('header', 'configrestrictions', get_string('config_restriction_heading', 'block_report_customcajasan'));
            $mform->addHelpButton('configrestrictions', 'config_restriction_heading', 'block_report_customcajasan');

            $courseoptions = [];
            $courses = get_user_capability_course('block/report_customcajasan:viewreport', $USER->id, true, 'fullname, shortname', 'fullname');
            foreach ($courses as $course) {
                $coursecontext = context_course::instance($course->id, IGNORE_MISSING);
                $courseoptions[$course->id] = format_string($course->fullname, true, ['context' => $coursecontext ?: null]);
            }

            if (!empty($courseoptions)) {
                $coursename = 'config_' . block_report_customcajasan::CONFIG_COURSES;
                $mform->addElement(
                    'select',
                    $coursename,
                    get_string('config_coursefilters', 'block_report_customcajasan'),
                    $courseoptions,
                    ['multiple' => true, 'size' => min(10, max(3, count($courseoptions)))]
                );
                $mform->addHelpButton($coursename, 'config_coursefilters', 'block_report_customcajasan');

                $clearcoursename = $coursename . '_clear';
                $mform->addElement(
                    'button',
                    $clearcoursename,
                    get_string('config_clearselection', 'block_report_customcajasan'),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-secondary mt-1 block-report-customcajasan-clear',
                        'data-action' => 'clear-selection',
                        'data-target' => $coursename,
                    ]
                );
                $mform->registerNoSubmitButton($clearcoursename);
                $clearelements[] = $coursename;
            } else {
                $mform->addElement('static', 'config_' . block_report_customcajasan::CONFIG_COURSES . '_notice', '', get_string('config_coursefilters_empty', 'block_report_customcajasan'));
            }

            $categoryoptions = core_course_category::make_categories_list('block/report_customcajasan:viewreport');
            if (!empty($categoryoptions)) {
                $categoryname = 'config_' . block_report_customcajasan::CONFIG_CATEGORIES;
                $mform->addElement(
                    'select',
                    $categoryname,
                    get_string('config_categoryfilters', 'block_report_customcajasan'),
                    $categoryoptions,
                    ['multiple' => true, 'size' => min(10, max(3, count($categoryoptions)))]
                );
                $mform->addHelpButton($categoryname, 'config_categoryfilters', 'block_report_customcajasan');

                $clearcategoryname = $categoryname . '_clear';
                $mform->addElement(
                    'button',
                    $clearcategoryname,
                    get_string('config_clearselection', 'block_report_customcajasan'),
                    [
                        'type' => 'button',
                        'class' => 'btn btn-secondary mt-1 block-report-customcajasan-clear',
                        'data-action' => 'clear-selection',
                        'data-target' => $categoryname,
                    ]
                );
                $mform->registerNoSubmitButton($clearcategoryname);
                $clearelements[] = $categoryname;
            } else {
                $mform->addElement('static', 'config_' . block_report_customcajasan::CONFIG_CATEGORIES . '_notice', '', get_string('config_categoryfilters_empty', 'block_report_customcajasan'));
            }
        }

        if (!empty($clearelements)) {
            $PAGE->requires->js_init_code(
                "(function() {\n" .
                "    var targets = " . json_encode(array_values($clearelements)) . ";\n" .
                "    if (!targets.length) {\n" .
                "        return;\n" .
                "    }\n" .
                "    document.addEventListener('click', function(event) {\n" .
                "        var trigger = event.target;\n" .
                "        while (trigger && trigger !== document) {\n" .
                "            if (trigger.hasAttribute('data-action') && trigger.getAttribute('data-action') === 'clear-selection') {\n" .
                "                break;\n" .
                "            }\n" .
                "            trigger = trigger.parentElement;\n" .
                "        }\n" .
                "        if (!trigger || trigger === document) {\n" .
                "            return;\n" .
                "        }\n" .
                "        var name = trigger.getAttribute('data-target');\n" .
                "        if (!name || targets.indexOf(name) === -1) {\n" .
                "            return;\n" .
                "        }\n" .
                "        event.preventDefault();\n" .
                "        var element = document.getElementById('id_' + name);\n" .
                "        if (!element) {\n" .
                "            return;\n" .
                "        }\n" .
                "        if (element.tagName === 'SELECT') {\n" .
                "            for (var i = 0; i < element.options.length; i++) {\n" .
                "                element.options[i].selected = false;\n" .
                "            }\n" .
                "            element.value = '';\n" .
                "        } else {\n" .
                "            element.value = '';\n" .
                "        }\n" .
                "        var changeEvent;\n" .
                "        if (typeof window.Event === 'function') {\n" .
                "            changeEvent = new Event('change', {bubbles: true});\n" .
                "        } else {\n" .
                "            changeEvent = document.createEvent('Event');\n" .
                "            changeEvent.initEvent('change', true, true);\n" .
                "        }\n" .
                "        element.dispatchEvent(changeEvent);\n" .
                "    });\n" .
                "})();"
            );
        }
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

            $restrictions = block_report_customcajasan_compute_restrictions($this->block->config);
            if (!empty($restrictions['courses'])) {
                $defaults->{'config_' . block_report_customcajasan::CONFIG_COURSES} = $restrictions['courses'];
            }
            if (!empty($restrictions['categories'])) {
                $defaults->{'config_' . block_report_customcajasan::CONFIG_CATEGORIES} = $restrictions['categories'];
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

