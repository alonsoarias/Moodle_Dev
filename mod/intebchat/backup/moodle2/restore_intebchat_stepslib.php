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
 * Restore steps for mod_intebchat.
 *
 * @package    mod_intebchat
 * @category   backup
 * @copyright  2025 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/backup/moodle2/restore_stepslib.php');

/**
 * Structure step to restore one intebchat activity.
 */
class restore_intebchat_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure to be restored.
     *
     * @return array of restore_path_element
     */
    protected function define_structure() {
        $paths = [];
        $userinfo = $this->get_setting_value('userinfo');

        // Root element.
        $paths[] = new restore_path_element('intebchat', '/activity/intebchat');

        // Child elements (only if user info is included).
        if ($userinfo) {
            $paths[] = new restore_path_element('intebchat_log', '/activity/intebchat/logs/log');
        }

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process main activity record.
     *
     * @param array $data
     */
    protected function process_intebchat($data) {
        global $DB;
        $data = (object)$data;

        // Mandatory field in every module table.
        $data->course = $this->get_courseid();

        // Make sure timestamps exist.
        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }
        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }

        // Insert and apply mapping.
        $newitemid = $DB->insert_record('intebchat', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process each log row.
     *
     * @param array $data
     */
    protected function process_intebchat_log($data) {
        global $DB;
        $data = (object)$data;

        $data->instanceid = $this->get_new_parentid('intebchat');
        $data->userid     = $this->get_mappingid('user', $data->userid);

        // Ajusta el nombre de la tabla si en tu install.xml usaste otro.
        $DB->insert_record('intebchat_log', $data);
    }

    /**
     * After execution: add related files.
     */
    protected function after_execute() {
        // Intro files.
        $this->add_related_files('mod_intebchat', 'intro', null);
    }
}
