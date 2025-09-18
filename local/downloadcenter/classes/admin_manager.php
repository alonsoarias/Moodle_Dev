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
     * Download multiple courses as single ZIP.
     *
     * @param array $courseids Array of course IDs
     * @param array $options Download options
     * @return void Outputs ZIP and exits
     */
    public function download_multiple_courses($courseids, $options = []) {
        global $USER, $DB, $CFG;
        
        require_once($CFG->libdir . '/filelib.php');
        
        // Set default options.
        $options = array_merge([
            'excludestudent' => false,
            'filesrealnames' => false,
            'addnumbering' => false
        ], $options);
        
        // Validate and get courses.
        $courses = [];
        foreach ($courseids as $courseid) {
            $course = $DB->get_record('course', ['id' => $courseid]);
            if ($course && can_access_course($course)) {
                $courses[] = $course;
            }
        }
        
        if (empty($courses)) {
            throw new \moodle_exception('nocourseaccess', 'local_downloadcenter');
        }
        
        // Set limits.
        $memorylimit = get_config('local_downloadcenter', 'memorylimit') ?: '512M';
        $timelimit = get_config('local_downloadcenter', 'timelimit') ?: 300;
        
        raise_memory_limit($memorylimit);
        core_php_time_limit::raise($timelimit);
        
        // Create filename.
        $filename = sprintf('courses_%d_%s.zip', 
            count($courses), 
            userdate(time(), '%Y%m%d_%H%M')
        );
        
        // Close session for performance.
        \core\session\manager::write_close();
        
        try {
            // Create ZIP writer.
            $zipwriter = \core_files\archive_writer::get_stream_writer(
                $filename, 
                \core_files\archive_writer::ZIP_WRITER
            );
            
            // Add each course to ZIP.
            foreach ($courses as $course) {
                $this->add_course_to_zip($course, $zipwriter, $options);
                
                // Log event.
                $event = \local_downloadcenter\event\zip_downloaded::create([
                    'objectid' => $course->id,
                    'context' => \context_course::instance($course->id),
                ]);
                $event->trigger();
            }
            
            // Finish and output ZIP.
            $zipwriter->finish();
            exit;
            
        } catch (\Exception $e) {
            debugging('Error creating multi-course zip: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('zipfailed', 'local_downloadcenter');
        }
    }
    
    /**
     * Add a single course to ZIP archive.
     *
     * @param \stdClass $course Course object
     * @param \core_files\archive_writer $zipwriter ZIP writer instance
     * @param array $options Download options
     * @return void
     */
    protected function add_course_to_zip($course, $zipwriter, $options) {
        global $USER;
        
        // Create factory for this course.
        $factory = new \local_downloadcenter\factory($course, $USER);
        
        // Set download options.
        $factory->set_download_options($options);
        
        // Get all resources.
        $resources = $factory->get_resources_for_user();
        
        // Create selection for all resources.
        $selection = [];
        foreach ($resources as $sectionid => $section) {
            $selection['item_topic_' . $sectionid] = 1;
            foreach ($section->res as $res) {
                // Skip student content if option is set.
                if ($options['excludestudent'] && $this->is_student_content($res)) {
                    continue;
                }
                $selection['item_' . $res->modname . '_' . $res->instanceid] = 1;
            }
        }
        
        // Parse selection.
        $factory->parse_form_data((object)$selection);
        
        // Build file list with course prefix.
        $courseprefix = $this->clean_filename($course->shortname) . '/';
        $filelist = $factory->build_filelist($courseprefix);
        
        // Add files to ZIP.
        foreach ($filelist as $path => $file) {
            if ($file === null) {
                // Directory placeholder.
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
     * Check if resource contains student content.
     *
     * @param \stdClass $res Resource object
     * @return bool True if student content
     */
    protected function is_student_content($res) {
        // These modules typically contain student submissions.
        $studentmodules = ['assign', 'forum', 'workshop', 'data', 'wiki', 'publication'];
        
        // For assignments, we still want to include the description.
        if ($res->modname === 'assign') {
            // We'll handle this specially in the factory to only exclude submissions.
            return false;
        }
        
        return in_array($res->modname, $studentmodules);
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