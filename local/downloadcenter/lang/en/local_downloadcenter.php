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
 * Language strings for local_downloadcenter
 *
 * @package    local_downloadcenter
 * @copyright  2025 Original: Academic Moodle Cooperation, Extended: Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Download Center';

// Capabilities.
$string['downloadcenter:view'] = 'View download center';
$string['downloadcenter:downloadmultiple'] = 'Download multiple courses';
$string['downloadcenter:excludestudentcontent'] = 'Exclude student content from downloads';

// Navigation.
$string['navigationlink'] = 'Download center';
$string['admindownloadcenter'] = 'Admin Download Center';

// Forms and buttons.
$string['createzip'] = 'Create ZIP archive';
$string['download'] = 'Download';
$string['downloadoptions'] = 'Download options';
$string['selectcategory'] = 'Select category';
$string['selectcourses'] = 'Select courses';
$string['adminmultiselectinstructions'] = 'Select one or more categories to reveal their courses, then pick the courses and resources you wish to include in the administrator download.';
$string['loadcourses'] = 'Load courses';
$string['saveandcontinue'] = 'Save and continue';
$string['clearselection'] = 'Clear selection';
$string['downloadselection'] = 'Download selection';
$string['addtoselection'] = 'Add to selection';
$string['optionssaved'] = 'Options saved successfully';

// Options.
$string['downloadoptions:addnumbering'] = 'Add numbering to files and folders';
$string['downloadoptions:addnumbering_help'] = 'If enabled, course sections, files, and folders will be numbered in the order they appear in the course.';
$string['downloadoptions:filesrealnames'] = 'Download files with original file name';
$string['downloadoptions:filesrealnames_help'] = 'If enabled, file resources will be downloaded with their original file name instead of the visible name in the course.';
$string['excludestudentcontent'] = 'Exclude student-generated content';
$string['excludestudentcontent_help'] = 'If enabled, student submissions and content (assignments, forum posts, etc.) will be excluded from the download. Only course materials provided by teachers will be included.';
$string['includefiles'] = 'Include all file types';
$string['includefiles_help'] = 'If enabled, all file types will be included in the download.';

// Settings.
$string['generalsettings'] = 'General settings';
$string['generalsettings_desc'] = 'Configure general download center options.';
$string['performancesettings'] = 'Performance settings';
$string['performancesettings_desc'] = 'Configure performance-related settings for ZIP creation.';
$string['enableadmindownload'] = 'Enable admin multi-course download';
$string['enableadmindownload_desc'] = 'Allow administrators to download multiple courses at once.';
$string['maxcoursesperdownload'] = 'Maximum courses per download';
$string['maxcoursesperdownload_desc'] = 'Maximum number of courses that can be downloaded in a single ZIP file.';
$string['excludestudentdefault'] = 'Exclude student content by default';
$string['excludestudentdefault_desc'] = 'When enabled, student-generated content will be excluded by default in new downloads.';
$string['compressionlevel'] = 'ZIP compression level';
$string['compressionlevel_desc'] = 'Level of compression for ZIP files. Higher compression takes more time but produces smaller files.';
$string['compressionstore'] = 'Store only (no compression)';
$string['compressionfast'] = 'Fast compression';
$string['compressionbest'] = 'Best compression';
$string['memorylimit'] = 'Memory limit';
$string['memorylimit_desc'] = 'PHP memory limit for ZIP creation (e.g., 512M).';
$string['timelimit'] = 'Time limit';
$string['timelimit_desc'] = 'Maximum execution time in seconds for ZIP creation.';

// Messages.
$string['infomessage_students'] = 'Here you can download single or all available contents of this course in a ZIP archive.';
$string['infomessage_teachers'] = 'Here you can download single or all available contents of this course in a ZIP archive.<br>(Students will be able to download only visible/not hidden activities and resources.)';
$string['nocoursesselected'] = 'No courses selected for download.';
$string['nocourseaccess'] = 'You do not have access to download these courses.';
$string['nocoursesfound'] = 'No courses found in this category.';
$string['toomanycoursesselected'] = 'Too many courses selected. Maximum allowed: {$a}';
$string['zipfailed'] = 'Failed to create ZIP file.';
$string['noselectederror'] = 'Please select at least one resource to download.';
$string['zipcreating'] = 'The ZIP archive is being created...';
$string['zipready'] = 'The ZIP archive has been successfully created.';

// Search.
$string['search:hint'] = 'Type to filter activities and resources...';
$string['search:results'] = 'Search results';

// Events.
$string['eventviewed'] = 'Download center viewed';
$string['eventdownloadedzip'] = 'ZIP downloaded';

// Other.
$string['untitled'] = 'Untitled';
$string['privacy:metadata'] = 'The Download Center plugin does not store any personal data.';

// Add these strings to the existing language file:

$string['currentselection'] = 'Current selection';
$string['selectioncleared'] = 'Selection cleared';
$string['saveoptions'] = 'Save options';
$string['courses'] = 'courses';
$string['hidden'] = 'Hidden';