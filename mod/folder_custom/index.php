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
 * List of file folder_customs in course
 *
 * @package   mod_folder_custom
 * @copyright 2009 onwards Martin Dougiamas (http://dougiamas.com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');

$id = required_param('id', PARAM_INT); // course id

$course = $DB->get_record('course', array('id'=>$id), '*', MUST_EXIST);

require_course_login($course, true);
$PAGE->set_pagelayout('incourse');

$params = array(
    'context' => context_course::instance($course->id)
);
$event = \mod_folder_custom\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

$strfolder_custom       = get_string('modulename', 'folder_custom');
$strfolder_customs      = get_string('modulenameplural', 'folder_custom');
$strname         = get_string('name');
$strintro        = get_string('moduleintro');
$strlastmodified = get_string('lastmodified');

$PAGE->set_url('/mod/folder_custom/index.php', array('id' => $course->id));
$PAGE->set_title($course->shortname.': '.$strfolder_customs);
$PAGE->set_heading($course->fullname);
$PAGE->set_secondary_active_tab('coursehome');
$PAGE->navbar->add($strfolder_customs);
echo $OUTPUT->header();
if (!$PAGE->has_secondary_navigation()) {
    echo $OUTPUT->heading($strfolder_customs);
}

if (!$folder_customs = get_all_instances_in_course('folder_custom', $course)) {
    notice(get_string('thereareno', 'moodle', $strfolder_customs), "$CFG->wwwroot/course/view.php?id=$course->id");
    exit;
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_'.$course->format);
    $table->head  = array ($strsectionname, $strname, $strintro);
    $table->align = array ('center', 'left', 'left');
} else {
    $table->head  = array ($strlastmodified, $strname, $strintro);
    $table->align = array ('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($folder_customs as $folder_custom) {
    $cm = $modinfo->cms[$folder_custom->coursemodule];
    if ($usesections) {
        $printsection = '';
        if ($folder_custom->section !== $currentsection) {
            if ($folder_custom->section) {
                $printsection = get_section_name($course, $folder_custom->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $folder_custom->section;
        }
    } else {
        $printsection = '<span class="smallinfo">'.userdate($folder_custom->timemodified)."</span>";
    }

    $class = $folder_custom->visible ? '' : 'class="dimmed"'; // hidden modules are dimmed
    $table->data[] = array (
        $printsection,
        "<a $class href=\"view.php?id=$cm->id\">".format_string($folder_custom->name)."</a>",
        format_module_intro('folder_custom', $folder_custom, $cm->id));
}

echo html_writer::table($table);

echo $OUTPUT->footer();
