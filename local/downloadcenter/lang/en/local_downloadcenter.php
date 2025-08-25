<?php
// This file is part of local_downloadcenter for Moodle - http://moodle.org/
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
 * Download center plugin language strings
 *
 * @package       local_downloadcenter
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['pluginname'] = 'Download center IEDigital';
$string['downloadcenter:view'] = 'View Download center';
$string['navigationlink'] = 'Download center';
$string['pagetitle'] = 'Download center for ';
$string['settings_title'] = 'Download center settings';

// Settings
$string['exclude_empty_topics'] = 'Exclude empty topics';
$string['exclude_empty_topics_help'] = 'Exclude empty topics from the downloaded zip.';
$string['maxzipsize'] = 'Maximum ZIP size (MB)';
$string['maxzipsize_desc'] = 'Maximum size for ZIP files in megabytes. Set to 0 for unlimited.';
$string['includeassignments'] = 'Include assignment descriptions';
$string['includeassignments_desc'] = 'Include assignment descriptions and intro attachments in downloads.';

// Interface strings
$string['warningmessage'] = 'Here you can download single or all available contents of this course in a ZIP archive.';
$string['createzip'] = 'Create ZIP archive';
$string['saveselection'] = 'Save selection';
$string['zipready'] = 'The ZIP archive has been successfully created.';
$string['download'] = 'Download';
$string['zipcreating'] = 'The ZIP archive is being created...';
$string['eventDOWNLOADEDZIP'] = 'ZIP was downloaded';
$string['eventVIEWED'] = 'Download center viewed';
$string['untitled'] = 'Untitled';
$string['privacy:null_reason'] = 'This plugin does not store or process any personal information. It presents an interface to download all course files which are manipulated from within the course.';
$string['no_downloadable_content'] = 'No downloadable content';

// Selection interface
$string['downloadall'] = 'Download all';
$string['selectfiles'] = 'Select files';
$string['selectonecourse'] = 'Please select exactly one course when choosing specific files.';
$string['selectcourses'] = 'Select courses';
$string['downloadselection'] = 'Download selected courses';
$string['clearselection'] = 'Clear selection';
$string['selected'] = 'selected';
$string['addcoursestoselection'] = 'Add selected courses';
$string['currentselection'] = 'Current selection';
$string['filesadded'] = 'Files added to selection';

// Error messages
$string['nocoursesselected'] = 'No courses selected for download';
$string['noaccesstocourse'] = 'You do not have access to course: {$a}';
$string['errorcreatinzip'] = 'Error creating ZIP file. Please try again.';
$string['nocoursesfound'] = 'No courses found';

// Help and descriptions
$string['downloadcenter_help'] = 'The Download Center allows you to download course content in bulk as ZIP archives. Select a category to view available courses, then select the courses you want to download.';
$string['downloadcenter_desc'] = 'Select courses from the list below to add them to your download selection. You can download content from multiple courses at once.';

// Navigation
$string['back'] = 'Back';
$string['administrationsite'] = 'Site administration';
$string['courses'] = 'Courses';
$string['searchcourses'] = 'Search courses...';
$string['search'] = 'Search';

// Capabilities
$string['downloadcenter:downloadcoursecontent'] = 'Download course content';
$string['downloadcenter:downloadallcourses'] = 'Download content from all courses';

// Notifications
$string['downloadsuccess'] = 'Download completed successfully';
$string['downloadfailed'] = 'Download failed: {$a}';
$string['courseadded'] = 'Course "{$a}" added to selection';
$string['courseremoved'] = 'Course "{$a}" removed from selection';
$string['selectioncleared'] = 'Selection cleared';

// Bulk operations
$string['bulkdownload'] = 'Bulk download';
$string['selectall'] = 'Select all';
$string['selectnone'] = 'Select none';
$string['selectedcount'] = '{$a} courses selected';

// File types
$string['resource'] = 'File';
$string['folder'] = 'Folder';
$string['page'] = 'Page';
$string['book'] = 'Book';
$string['assign'] = 'Assignment';
$string['glossary'] = 'Glossary';
$string['publication'] = 'Publication';
$string['lightboxgallery'] = 'Gallery';
$string['etherpadlite'] = 'Etherpad';

// Progress
$string['preparing'] = 'Preparing download...';
$string['processing'] = 'Processing {$a}...';
$string['compressing'] = 'Compressing files...';
$string['done'] = 'Done!';