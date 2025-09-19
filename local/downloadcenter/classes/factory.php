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
 * Factory class for creating download packages
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_downloadcenter;

defined('MOODLE_INTERNAL') || die();

/**
 * Factory class for managing course downloads.
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class factory {
    
    /** @var \stdClass Course object */
    protected $course;
    
    /** @var \stdClass User object */
    protected $user;
    
    /** @var array Sorted resources by section */
    protected $sortedresources = null;
    
    /** @var array Filtered resources after form submission */
    protected $filteredresources = [];
    
    /** @var array Download options */
    protected $downloadoptions = [
        'filesrealnames' => false,
        'addnumbering' => false,
        'excludestudent' => false,
    ];
    
    /** @var array Available resource types to include */
    protected $availableresources = [
        'resource',
        'folder',
        'publication',
        'page',
        'book',
        'lightboxgallery',
        'assign',
        'glossary',
        'etherpadlite',
        'subsection',
        'url',
        'label',
    ];
    
    /** @var array JavaScript module names */
    protected $jsnames = [];
    
    /** @var array Path count for unique names */
    protected $pathcount = [];
    
    /** @var int Section counter for numbering */
    protected $sectioncount = 0;
    
    /** @var int Resource counter for numbering */
    protected $resourcecount = 0;
    
    /**
     * Constructor.
     *
     * @param \stdClass $course Course object
     * @param \stdClass $user User object
     */
    public function __construct($course, $user) {
        $this->course = $course;
        $this->user = $user;
    }
    
    /**
     * Get resources available for download for the user.
     *
     * @return array Array of resources grouped by section
     */
    public function get_resources_for_user() {
        global $DB, $CFG;
        
        if (!empty($this->sortedresources)) {
            return $this->sortedresources;
        }
        
        $modinfo = get_fast_modinfo($this->course);
        $usesections = course_format_uses_sections($this->course->format);
        
        $context = \context_course::instance($this->course->id);
        $canviewhiddensections = has_capability('moodle/course:viewhiddensections', $context);
        $canviewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context);
        
        $sorted = [];
        
        if ($usesections) {
            $sections = $DB->get_records('course_sections', ['course' => $this->course->id], 'section');
            $courseformat = course_get_format($this->course);
            $max = $courseformat->get_last_section_number();
            
            foreach ($sections as $section) {
                if (intval($section->section) > $max) {
                    break;
                }
                
                if (!isset($sorted[$section->section]) && ($section->visible || $canviewhiddensections)) {
                    $sorted[$section->section] = new \stdClass();
                    $title = trim(get_section_name($this->course, $section->section));
                    $sorted[$section->section]->title = $title ?: get_string('untitled', 'local_downloadcenter');
                    $sorted[$section->section]->visible = $section->visible;
                    $sorted[$section->section]->itemid = $section->id;
                    $sorted[$section->section]->res = [];
                }
            }
        } else {
            $sorted['default'] = new \stdClass();
            $sorted['default']->title = get_string('general', 'core');
            $sorted['default']->visible = true;
            $sorted['default']->res = [];
            $sorted['default']->itemid = null;
        }
        
        // Collect course modules.
        $cms = [];
        $resources = [];
        
        foreach ($modinfo->cms as $cm) {
            if (!in_array($cm->modname, $this->availableresources)) {
                continue;
            }
            
            if (!$cm->uservisible && $cm->modname !== 'subsection') {
                continue;
            }
            
            if (!$cm->has_view() && !in_array($cm->modname, ['folder', 'subsection', 'label'])) {
                continue;
            }
            
            $cms[$cm->id] = $cm;
            $resources[$cm->modname][] = $cm->instance;
        }
        
        // Preload resource instances.
        foreach ($resources as $modname => $instances) {
            if (!empty($instances)) {
                $resources[$modname] = $DB->get_records_list($modname, 'id', $instances, 'id');
            }
        }
        
        // Process each course module.
        $availablesections = array_keys($sorted);
        $currentsection = $usesections ? null : 'default';
        
        foreach ($cms as $cm) {
            if (!isset($resources[$cm->modname][$cm->instance])) {
                continue;
            }
            
            $resource = $resources[$cm->modname][$cm->instance];
            
            if ($usesections) {
                $currentsection = $cm->sectionnum;
                if (!in_array($currentsection, $availablesections)) {
                    continue;
                }
            }
            
            if ($cm->is_stealth() && !$canviewhiddenactivities) {
                continue;
            }
            
            $cmcontext = \context_module::instance($cm->id);
            
            // Special handling for certain modules.
            if ($cm->modname === 'glossary') {
                if (!has_capability('mod/glossary:manageentries', $cmcontext) && !$resource->allowprintview) {
                    continue;
                }
            }
            
            // Add module name to JS names.
            if (!isset($this->jsnames[$cm->modname]) && $cm->modname !== 'subsection') {
                $this->jsnames[$cm->modname] = get_string('modulenameplural', 'mod_' . $cm->modname);
            }
            
            // Create resource object.
            $res = new \stdClass();
            $res->icon = '<img src="'.$cm->get_icon_url().'" class="activityicon" alt="'.$cm->get_module_type_name().'" /> ';
            $res->cmid = $cm->id;
            $res->name = $cm->get_formatted_name();
            $res->modname = $cm->modname;
            $res->instanceid = $cm->instance;
            $res->resource = $resource;
            $res->cm = $cm;
            $res->visible = $cm->visible;
            $res->isstealth = $cm->is_stealth();
            $res->context = $cmcontext;
            
            $sorted[$currentsection]->res[] = $res;
        }
        
        // Handle subsections if available.
        if (in_array('subsection', $this->availableresources)) {
            $this->replace_subsection_resources($sorted);
        }
        
        $this->sortedresources = $sorted;
        return $sorted;
    }
    
    /**
     * Parse form data and set filtered resources.
     *
     * @param \stdClass $data Form data
     * @return void
     */
    public function parse_form_data($data) {
        $data = (array)$data;
        $filtered = [];
        
        $sortedresources = $this->get_resources_for_user();
        
        foreach ($sortedresources as $sectionid => $info) {
            if (!isset($data['item_topic_' . $sectionid])) {
                continue;
            }
            
            $filtered[$sectionid] = new \stdClass();
            $filtered[$sectionid]->title = $info->title;
            $filtered[$sectionid]->visible = $info->visible;
            $filtered[$sectionid]->res = [];
            
            foreach ($info->res as $res) {
                $name = 'item_' . $res->modname . '_' . $res->instanceid;
                if (!isset($data[$name])) {
                    continue;
                }
                $filtered[$sectionid]->res[] = $res;
            }
        }
        
        $this->filteredresources = $filtered;
        $this->downloadoptions['filesrealnames'] = isset($data['filesrealnames']);
        $this->downloadoptions['addnumbering'] = isset($data['addnumbering']);
        $this->downloadoptions['excludestudent'] = isset($data['excludestudent']);
    }
    
    /**
     * Set download options.
     *
     * @param array $options Options array
     * @return void
     */
    public function set_download_options($options) {
        $this->downloadoptions = array_merge($this->downloadoptions, $options);
    }
    
    /**
     * Create ZIP file for download.
     *
     * @return void Outputs ZIP file and exits
     */
    public function create_zip() {
        global $CFG;
        
        require_once($CFG->libdir . '/filelib.php');
        
        // Apply configured limits.
        $memorylimit = get_config('local_downloadcenter', 'memorylimit');
        $timelimit = get_config('local_downloadcenter', 'timelimit');
        
        if ($memorylimit) {
            raise_memory_limit($memorylimit);
        }
        if ($timelimit) {
            \core_php_time_limit::raise($timelimit);
        }
        
        // Close session for performance.
        \core\session\manager::write_close();
        
        // Create filename.
        $filename = sprintf('%s_%s.zip', 
            format_string($this->course->shortname), 
            userdate(time(), '%Y%m%d_%H%M')
        );
        
        try {
            // Get compression level from settings.
            $compressionlevel = get_config('local_downloadcenter', 'compressionlevel');
            
            $zipwriter = \core_files\archive_writer::get_stream_writer(
                $filename, 
                \core_files\archive_writer::ZIP_WRITER,
                ['level' => $compressionlevel]
            );
            
            $filelist = $this->build_filelist();
            
            foreach ($filelist as $pathinzip => $file) {
                if ($file instanceof \stored_file) {
                    $zipwriter->add_file_from_stored_file($pathinzip, $file);
                } else if (is_array($file)) {
                    $content = reset($file);
                    $zipwriter->add_file_from_string($pathinzip, $content);
                } else if (is_string($file) && file_exists($file)) {
                    $zipwriter->add_file_from_filepath($pathinzip, $file);
                }
            }
            
            $zipwriter->finish();
            exit;
            
        } catch (\Exception $e) {
            debugging('Error creating zip: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('zipfailed', 'local_downloadcenter');
        }
    }
    
    /**
     * Build list of files to include in ZIP.
     *
     * @param string $prefix Optional prefix for all paths
     * @return array Array of files to include
     */
    public function build_filelist($prefix = '') {
        global $CFG;
        
        $fs = get_file_storage();
        $filelist = [];
        
        foreach ($this->filteredresources as $sectionid => $info) {
            $basedir = $this->prepare_directory_name($info->title, true);
            
            if ($prefix) {
                $basedir = $prefix . $basedir;
            }
            
            $filelist[$basedir] = null;
            
            foreach ($info->res as $res) {
                $this->add_resource_to_filelist($res, $basedir, $filelist, $fs);
            }
        }
        
        return $filelist;
    }
    
    /**
     * Add a resource to the file list.
     *
     * @param \stdClass $res Resource object
     * @param string $basedir Base directory path
     * @param array $filelist File list array (passed by reference)
     * @param \file_storage $fs File storage instance
     * @return void
     */
    protected function add_resource_to_filelist($res, $basedir, &$filelist, $fs) {
        $resdir = $basedir . '/' . $this->prepare_directory_name($res->name);
        $context = $res->context;
        
        // Skip student content if option is set.
        if ($this->downloadoptions['excludestudent'] && $this->is_student_content($res)) {
            return;
        }
        
        switch ($res->modname) {
            case 'resource':
                $this->add_resource_files($res, $basedir, $resdir, $filelist, $fs, $context);
                break;
                
            case 'folder':
                $this->add_folder_files($res, $resdir, $filelist, $fs, $context);
                break;
                
            case 'page':
                $this->add_page_files($res, $resdir, $filelist, $fs, $context);
                break;
                
            case 'book':
                $this->add_book_files($res, $resdir, $filelist, $fs, $context);
                break;
                
            case 'assign':
                $this->add_assign_files($res, $resdir, $filelist, $fs, $context);
                break;
                
            case 'glossary':
                $this->add_glossary_files($res, $resdir, $filelist, $fs, $context);
                break;
                
            default:
                // Handle other resource types if needed.
                break;
        }
    }
    
    /**
     * Check if resource contains student-generated content.
     *
     * @param \stdClass $res Resource object
     * @return bool True if student content should be excluded
     */
    protected function is_student_content($res) {
        // These modules typically contain student submissions.
        $studentmodules = ['assign', 'forum', 'workshop', 'data', 'wiki'];
        
        if (in_array($res->modname, $studentmodules)) {
            // For assignments, we can still include the description.
            if ($res->modname === 'assign') {
                return false; // We'll handle this specially in add_assign_files.
            }
            return true;
        }
        
        // Publication module is specifically for student uploads.
        if ($res->modname === 'publication') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add resource files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $basedir Base directory
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_resource_files($res, $basedir, $resdir, &$filelist, $fs, $context) {
        $files = $fs->get_area_files($context->id, 'mod_resource', 'content', 0, 
                                     'sortorder DESC, id ASC', false);
        
        if ($file = array_shift($files)) {
            $filename = $this->downloadoptions['filesrealnames'] ? 
                        $file->get_filename() : 
                        $res->name;
            
            $filename = $basedir . '/' . self::shorten_filename(clean_filename($filename));
            
            $extension = pathinfo($file->get_filename(), PATHINFO_EXTENSION);
            if ($extension && strpos($filename, '.' . $extension) === false) {
                $filename .= '.' . $extension;
            }
            
            $filelist[$filename] = $file;
        }
    }
    
    /**
     * Add folder files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_folder_files($res, $resdir, &$filelist, $fs, $context) {
        $filelist[$resdir] = null;
        $folder = $fs->get_area_tree($context->id, 'mod_folder', 'content', 0);
        $this->add_folder_contents($filelist, $folder, $resdir);
    }
    
    /**
     * Add folder contents recursively.
     *
     * @param array $filelist File list (by reference)
     * @param array $folder Folder structure
     * @param string $path Current path
     * @return void
     */
    protected function add_folder_contents(&$filelist, $folder, $path) {
        if (!empty($folder['subdirs'])) {
            foreach ($folder['subdirs'] as $foldername => $subfolder) {
                $foldername = self::shorten_filename($foldername);
                $this->add_folder_contents($filelist, $subfolder, $path . '/' . $foldername);
            }
        }
        
        if (!empty($folder['files'])) {
            foreach ($folder['files'] as $filename => $file) {
                $filelist[$path . '/' . self::shorten_filename($filename)] = $file;
            }
        }
    }
    
    /**
     * Add page files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_page_files($res, $resdir, &$filelist, $fs, $context) {
        $filelist[$resdir] = null;
        
        // Add any embedded files.
        $fsfiles = $fs->get_area_files($context->id, 'mod_page', 'content', 0, 'id', false);
        
        foreach ($fsfiles as $file) {
            if ($file->get_filesize() == 0) {
                continue;
            }
            $filename = $resdir . '/files/' . self::shorten_filename($file->get_filename());
            $filelist[$filename] = $file;
        }
        
        // Add HTML content.
        $filename = $resdir . '/' . self::shorten_filename($res->name . '.html');
        $content = str_replace('@@PLUGINFILE@@', 'files', $res->resource->content);
        $content = self::convert_content_to_html_doc($res->name, $content);
        $filelist[$filename] = [$content];
    }
    
    /**
     * Add book files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_book_files($res, $resdir, &$filelist, $fs, $context) {
        global $DB, $CFG, $OUTPUT;
        
        // Check if book module exists.
        if (!file_exists($CFG->dirroot . '/mod/book/locallib.php')) {
            return;
        }
        
        require_once($CFG->dirroot . '/mod/book/locallib.php');
        
        $filelist[$resdir] = null;
        
        $book = $res->resource;
        $cm = $res->cm;
        
        // Get book chapters.
        $chapters = book_preload_chapters($book);
        if (empty($chapters)) {
            return;
        }
        
        // Add embedded files.
        $fsfiles = $fs->get_area_files($context->id, 'mod_book', 'chapter', null, 'id', false);
        
        foreach ($fsfiles as $file) {
            if ($file->get_filesize() == 0) {
                continue;
            }
            $filename = $resdir . '/files/' . self::shorten_filename($file->get_filename());
            $filelist[$filename] = $file;
        }
        
        // Build HTML content.
        $content = '<h1>' . format_string($book->name) . '</h1>';
        $content .= '<div class="book-intro">' . format_text($book->intro, $book->introformat) . '</div>';
        
        foreach ($chapters as $chapter) {
            if ($chapter->hidden) {
                continue;
            }
            
            $chaptercontent = $DB->get_record('book_chapters', ['id' => $chapter->id]);
            if ($chaptercontent) {
                $content .= '<div class="book-chapter">';
                $content .= '<h2>' . format_string($chapter->title) . '</h2>';
                $content .= format_text($chaptercontent->content, $chaptercontent->contentformat);
                $content .= '</div>';
            }
        }
        
        $filename = $resdir . '/' . self::shorten_filename($res->name . '.html');
        $content = self::convert_content_to_html_doc($res->name, $content);
        $filelist[$filename] = [$content];
    }
    
    /**
     * Add assignment files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_assign_files($res, $resdir, &$filelist, $fs, $context) {
        global $CFG;
        
        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        
        $filelist[$resdir] = null;
        
        // Always include assignment description and intro attachments.
        if ($res->resource->intro) {
            // Add intro attachments.
            $introfiles = $fs->get_area_files($context->id, 'mod_assign', 'introattachment', 
                                              0, 'id', false);
            
            foreach ($introfiles as $file) {
                $filename = $resdir . '/intro/' . self::shorten_filename($file->get_filename());
                $filelist[$filename] = $file;
            }
            
            // Add intro as HTML.
            $introcontent = str_replace('@@PLUGINFILE@@', '', $res->resource->intro);
            $introcontent = self::convert_content_to_html_doc(
                get_string('description'),
                $introcontent
            );
            $filelist[$resdir . '/intro.html'] = [$introcontent];
        }
        
        // Only add student submissions if not excluded.
        if (!$this->downloadoptions['excludestudent']) {
            // Add student submissions logic here if needed.
            // For now, we skip student submissions when excludestudent is true.
        }
    }
    
    /**
     * Add glossary files to filelist.
     *
     * @param \stdClass $res Resource object
     * @param string $resdir Resource directory
     * @param array $filelist File list (by reference)
     * @param \file_storage $fs File storage
     * @param \context $context Context
     * @return void
     */
    protected function add_glossary_files($res, $resdir, &$filelist, $fs, $context) {
        global $DB, $CFG, $OUTPUT, $SITE;
        
        require_once($CFG->dirroot . '/mod/glossary/lib.php');
        
        $filelist[$resdir] = null;
        
        $glossary = $res->resource;
        $cm = $res->cm;
        $course = $this->course;
        
        // Build glossary content.
        $content = '';
        $entries = glossary_get_entries_by_letter($glossary, $context, 'ALL', 0, 0);
        
        if ($entries) {
            $content = '<h1>' . format_string($glossary->name) . '</h1>';
            $content .= '<div class="glossary-intro">' . 
                       format_text($glossary->intro, $glossary->introformat) . '</div>';
            
            foreach ($entries as $entry) {
                $content .= '<div class="glossary-entry">';
                $content .= '<h3>' . format_string($entry->concept) . '</h3>';
                $content .= '<div class="definition">' . 
                           format_text($entry->definition, $entry->definitionformat) . '</div>';
                $content .= '</div>';
            }
        }
        
        // Add attachments.
        $attachments = $fs->get_area_files($context->id, 'mod_glossary', 'attachment', 
                                           null, 'id', false);
        
        foreach ($attachments as $file) {
            $filename = $resdir . '/attachments/' . self::shorten_filename($file->get_filename());
            $filelist[$filename] = $file;
        }
        
        // Save content as HTML.
        if ($content) {
            $filename = $resdir . '/' . self::shorten_filename($res->name . '.html');
            $content = self::convert_content_to_html_doc($res->name, $content);
            $filelist[$filename] = [$content];
        }
    }
    
    /**
     * Replace subsection resources with their contents.
     *
     * @param array $sections Sections array (by reference)
     * @return void
     */
    protected function replace_subsection_resources(&$sections) {
        // Implementation for subsection module support.
        // This would expand subsections into their component resources.
    }
    
    /**
     * Prepare directory name with optional numbering.
     *
     * @param string $name Directory name
     * @param bool $issection Whether this is a section directory
     * @return string Prepared directory name
     */
    protected function prepare_directory_name($name, $issection = false) {
        $cleanname = clean_filename(html_entity_decode($name));
        
        if ($this->downloadoptions['addnumbering']) {
            if ($issection) {
                $this->sectioncount++;
                $cleanname = sprintf('%02d_%s', $this->sectioncount, $cleanname);
            } else {
                $this->resourcecount++;
                $cleanname = sprintf('%03d_%s', $this->resourcecount, $cleanname);
            }
        } else {
            $cleanname = $this->ensure_unique_path($cleanname);
        }
        
        return self::shorten_filename($cleanname);
    }
    
    /**
     * Ensure path is unique by adding counter if needed.
     *
     * @param string $path Path to check
     * @return string Unique path
     */
    protected function ensure_unique_path($path) {
        if (isset($this->pathcount[$path])) {
            $this->pathcount[$path]++;
            return $path . '_' . $this->pathcount[$path];
        }
        $this->pathcount[$path] = 1;
        return $path;
    }
    
    /**
     * Get JavaScript module names.
     *
     * @return array Array of module names
     */
    public function get_js_modnames() {
        return [$this->jsnames];
    }
    
    /**
     * Shorten filename to specified length.
     *
     * @param string $filename Filename to shorten
     * @param int $maxlength Maximum length
     * @return string Shortened filename
     */
    public static function shorten_filename($filename, $maxlength = 64) {
        $filename = (string)$filename;
        $filename = str_replace('/', '_', $filename);
        
        if (mb_strlen($filename) <= $maxlength) {
            return $filename;
        }
        
        $limit = round($maxlength / 2) - 1;
        return mb_substr($filename, 0, $limit) . '___' . mb_substr($filename, (1 - $limit));
    }
    
    /**
     * Convert content to complete HTML document.
     *
     * @param string $title Document title
     * @param string $content Document content
     * @param string $additionalhead Additional head content
     * @return string Complete HTML document
     */
    public static function convert_content_to_html_doc($title, $content, $additionalhead = '') {
        return <<<HTML
<!doctype html>
<html>
<head>
    <title>$title</title>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2, h3 { color: #333; }
        .book-chapter { margin: 20px 0; padding: 10px 0; border-bottom: 1px solid #ccc; }
        .glossary-entry { margin: 15px 0; padding: 10px; background: #f5f5f5; }
    </style>
    $additionalhead
</head>
<body>
$content
</body>
</html>
HTML;
    }
}