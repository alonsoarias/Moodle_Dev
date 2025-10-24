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
 * folder_custom download
 *
 * @package   mod_folder_custom
 * @copyright 2015 Andrew Hancox <andrewdchancox@googlemail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . "/../../config.php");

$id = required_param('id', PARAM_INT);  // Course module ID.
$cm = get_coursemodule_from_id('folder_custom', $id, 0, true, MUST_EXIST);

$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/folder_custom:view', $context);

$folder_custom = $DB->get_record('folder_custom', array('id' => $cm->instance), '*', MUST_EXIST);

$downloadable = folder_custom_archive_available($folder_custom, $cm);
if (!$downloadable) {
    throw new \moodle_exception('cannotdownloaddir', 'repository');
}

$fs = get_file_storage();
$files = $fs->get_area_files($context->id, 'mod_folder_custom', 'content');
if (empty($files)) {
    throw new \moodle_exception('cannotdownloaddir', 'repository');
}

// Log zip as downloaded.
folder_custom_downloaded($folder_custom, $course, $cm, $context);

// Close the session.
\core\session\manager::write_close();

$folder_customname = format_string($folder_custom->name, true, ["context" => $context]);
$filename = shorten_filename(clean_filename($folder_customname . "-" . date("Ymd")) . ".zip");
$zipwriter = \core_files\archive_writer::get_stream_writer($filename, \core_files\archive_writer::ZIP_WRITER);

foreach ($files as $file) {
    if ($file->is_directory()) {
        continue;
    }
    $pathinzip = $file->get_filepath() . $file->get_filename();
    $zipwriter->add_file_from_stored_file($pathinzip, $file);
}

// Finish the archive.
$zipwriter->finish();
exit();
