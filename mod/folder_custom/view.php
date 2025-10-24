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
 * folder_custom module main user interface
 *
 * @package   mod_folder_custom
 * @copyright 2009 Petr Skoda  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(__DIR__ . '/locallib.php');
require_once("$CFG->dirroot/repository/lib.php");
require_once($CFG->libdir . '/completionlib.php');

$id = optional_param('id', 0, PARAM_INT);  // Course module ID
$f  = optional_param('f', 0, PARAM_INT);   // folder_custom instance id

if ($f) {  // Two ways to specify the module
    $folder_custom = $DB->get_record('folder_custom', array('id'=>$f), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('folder_custom', $folder_custom->id, $folder_custom->course, true, MUST_EXIST);
} else {
    $cm = get_coursemodule_from_id('folder_custom', $id, 0, true, MUST_EXIST);
    $folder_custom = $DB->get_record('folder_custom', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/folder_custom:view', $context);

// Redirect to course page if display is inline
if ($folder_custom->display == folder_custom_DISPLAY_INLINE) {
    redirect(course_get_url($folder_custom->course, $cm->sectionnum));
}

// Log event
$params = array(
    'context' => $context,
    'objectid' => $folder_custom->id
);
$event = \mod_folder_custom\event\course_module_viewed::create($params);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('folder_custom', $folder_custom);
$event->trigger();

// Update 'viewed' state if required by completion system
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Configure page
$modulepath = '/mod/' . $cm->modname;
$PAGE->set_url($modulepath . '/view.php', array('id' => $cm->id));
$PAGE->set_title($course->shortname.': '.$folder_custom->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($folder_custom);
$PAGE->add_body_class('limitedwidth');

// Add OneDrive-style class to body for additional styling if needed
$PAGE->add_body_class('mod-folder_custom-onedrive');

// Get renderer
$output = $PAGE->get_renderer('mod_folder_custom');

// Output page
echo $output->header();
echo $output->display_folder_custom($folder_custom);
echo $output->footer();