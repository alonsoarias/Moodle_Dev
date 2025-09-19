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
 * Admin manager for multi-course operations
 *
 * @package    local_downloadcenter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter;

defined('MOODLE_INTERNAL') || die();

/**
 * Admin manager class for handling multi-course downloads.
 */
class admin_manager {
    
    /**
     * Download multiple courses with individually selected resources.
     *
     * @param array $courseselections Array keyed by course ID containing selected resource keys
     * @param array $options Download options
     * @return void Outputs ZIP and exits
     */
    public function download_multiple_courses(array $courseselections, array $options = []) {
        global $USER, $DB, $CFG;

        require_once($CFG->libdir . '/filelib.php');

        $options = array_merge([
            'excludestudent' => false,
            'filesrealnames' => false,
            'addnumbering' => false,
        ], $options);

        $courses = [];
        foreach ($courseselections as $courseid => $selection) {
            $courseid = clean_param($courseid, PARAM_INT);
            if (empty($courseid) || empty($selection)) {
                continue;
            }

            $course = $DB->get_record('course', ['id' => $courseid]);
            if ($course && can_access_course($course)) {
                $courses[$courseid] = $course;
            }
        }

        if (empty($courses)) {
            throw new \moodle_exception('nocourseaccess', 'local_downloadcenter');
        }

        $memorylimit = get_config('local_downloadcenter', 'memorylimit') ?: '512M';
        $timelimit = get_config('local_downloadcenter', 'timelimit') ?: 300;

        raise_memory_limit($memorylimit);
        \core_php_time_limit::raise($timelimit);

        $filename = sprintf('courses_%d_%s.zip',
            count($courses),
            userdate(time(), '%Y%m%d_%H%M')
        );

        \core\session\manager::write_close();

        try {
            $zipwriter = \core_files\archive_writer::get_stream_writer(
                $filename,
                \core_files\archive_writer::ZIP_WRITER
            );

            foreach ($courses as $courseid => $course) {
                $selection = $courseselections[$courseid];
                $this->add_course_to_zip($course, $selection, $zipwriter, $options);

                $event = \local_downloadcenter\event\zip_downloaded::create([
                    'objectid' => $course->id,
                    'context' => \context_course::instance($course->id),
                ]);
                $event->trigger();
            }

            $zipwriter->finish();
            exit;

        } catch (\Exception $e) {
            debugging('Error creating multi-course zip: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('zipfailed', 'local_downloadcenter');
        }
    }
    
    /**
     * Add a single course selection to the archive.
     *
     * @param \stdClass $course Course object
     * @param array $selection Selected resource keys for this course
     * @param \core_files\archive_writer $zipwriter ZIP writer instance
     * @param array $options Download options
     * @return void
     */
    protected function add_course_to_zip($course, array $selection, $zipwriter, array $options) {
        global $USER;

        if (empty($selection)) {
            return;
        }

        $factory = new \local_downloadcenter\factory($course, $USER);
        $factory->set_download_options($options);
        $factory->parse_form_data((object)$selection);
        $factory->set_download_options($options);

        $courseprefix = $this->clean_filename($course->shortname) . '/';
        $filelist = $factory->build_filelist($courseprefix);

        foreach ($filelist as $path => $file) {
            if ($file === null) {
                continue;
            }

            if ($file instanceof \stored_file) {
                $zipwriter->add_file_from_stored_file($path, $file);
            } else if (is_array($file)) {
                $content = reset($file);
                $zipwriter->add_file_from_string($path, $content);
            } else if (is_string($file) && file_exists($file)) {
                $zipwriter->add_file_from_filepath($path, $file);
            }
        }
    }
    
    /**
     * Clean filename for use in ZIP.
     *
     * @param string $filename Filename to clean
     * @return string Cleaned filename
     */
    protected function clean_filename($filename) {
        $filename = str_replace('/', '_', $filename);
        $filename = clean_filename($filename);
        
        // Limit length.
        if (strlen($filename) > 64) {
            $filename = substr($filename, 0, 60) . '...';
        }
        
        return $filename;
    }
}