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
 * Restore task for mod_intebchat.
 *
 * @package    mod_intebchat
 * @category   backup
 * © 2025 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/intebchat/backup/moodle2/restore_intebchat_stepslib.php');

/**
 * Provides all the settings and steps to perform a complete restore of the activity.
 */
class restore_intebchat_activity_task extends restore_activity_task {

    /**
     * No particular settings.
     */
    protected function define_my_settings() {
        // Nothing here.
    }

    /**
     * Define steps.
     */
    protected function define_my_steps() {
        $this->add_step(new restore_intebchat_activity_structure_step('intebchat_structure', 'intebchat.xml'));
    }

    /**
     * Define contents to be decoded.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        $contents = [];
        $contents[] = new restore_decode_content('intebchat', ['intro'], 'intebchat');
        return $contents;
    }

    /**
     * Define link decoding rules.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        $rules = [];
        $rules[] = new restore_decode_rule('INTEBCHATVIEWBYID', '/mod/intebchat/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('INTEBCHATINDEX',     '/mod/intebchat/index.php?id=$1', 'course');
        return $rules;
    }

    /**
     * Define restore log rules for this activity.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        $rules = [];
        $rules[] = new restore_log_rule('intebchat', 'add',    'view.php?id={course_module}', '{intebchat}');
        $rules[] = new restore_log_rule('intebchat', 'update', 'view.php?id={course_module}', '{intebchat}');
        $rules[] = new restore_log_rule('intebchat', 'view',   'view.php?id={course_module}', '{intebchat}');
        return $rules;
    }

    /**
     * Restore log rules for course logs (cmid = 0).
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];
        $rules[] = new restore_log_rule('intebchat', 'view all', 'index.php?id={course}', null);
        return $rules;
    }
}
