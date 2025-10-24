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
 * Strings for component 'folder', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   mod_folder
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['bynameondate'] = 'by {$a->name} - {$a->date}';
$string['contentheader'] = 'Content';
$string['dnduploadmakefolder'] = 'Unzip files and create folder';
$string['downloadfolder'] = 'Download folder';
$string['eventallfilesdownloaded'] = 'Zip archive of folder downloaded';
$string['eventfolderupdated'] = 'Folder updated';
$string['folder:addinstance'] = 'Add a new folder';
$string['folder:managefiles'] = 'Manage files in folder module';
$string['folder:view'] = 'View folder content';
$string['foldercontent'] = 'Files and subfolders';
$string['forcedownload'] = 'Force download of files';
$string['forcedownload_help'] = 'Whether certain files, such as images or HTML files, should be displayed in the browser rather than being downloaded. Note that for security reasons, the setting should only be unticked if all users with the capability to manage files in the folder are trusted users.';
$string['indicator:cognitivedepth'] = 'Folder cognitive';
$string['indicator:cognitivedepth_help'] = 'This indicator is based on the cognitive depth reached by the student in a Folder resource.';
$string['indicator:cognitivedepthdef'] = 'Folder cognitive';
$string['indicator:cognitivedepthdef_help'] = 'The participant has reached this percentage of the cognitive engagement offered by the Folder resources during this analysis interval (Levels = No view, View)';
$string['indicator:cognitivedepthdef_link'] = 'Learning_analytics_indicators#Cognitive_depth';
$string['indicator:socialbreadth'] = 'Folder social';
$string['indicator:socialbreadth_help'] = 'This indicator is based on the social breadth reached by the student in a Folder resource.';
$string['indicator:socialbreadthdef'] = 'Folder social';
$string['indicator:socialbreadthdef_help'] = 'The participant has reached this percentage of the social engagement offered by the Folder resources during this analysis interval (Levels = No participation, Participant alone)';
$string['indicator:socialbreadthdef_link'] = 'Learning_analytics_indicators#Social_breadth';
$string['modulename'] = 'Folder';
$string['modulename_help'] = 'The folder module enables a teacher to display a number of related files inside a single folder, reducing scrolling on the course page. A zipped folder may be uploaded and unzipped for display, or an empty folder created and files uploaded into it.

A folder may be used

* For a series of files on one topic, for example a set of past examination papers in pdf format or a collection of image files for use in student projects
* To provide a shared uploading space for teachers on the course page (keeping the folder hidden so that only teachers can see it)';
$string['modulename_link'] = 'mod/folder/view';
$string['modulenameplural'] = 'Folders';
$string['newfoldercontent'] = 'New folder content';
$string['page-mod-folder-x'] = 'Any folder module page';
$string['page-mod-folder-view'] = 'Folder module main page';
$string['privacy:metadata'] = 'The Folder resource plugin does not store any personal data.';
$string['pluginadministration'] = 'Folder administration';
$string['pluginname'] = 'Folder';
$string['display'] = 'Display folder contents';
$string['display_help'] = 'If you choose to display the folder contents on a course page, there will be no link to a separate page. The description will be displayed only if \'Display description on course page\' is ticked. Note that participants view actions cannot be logged in this case.';
$string['displaypage'] = 'On a separate page';
$string['displayinline'] = 'Inline on a course page';
$string['noautocompletioninline'] = 'Automatic completion on viewing of activity can not be selected together with "Display inline" option';
$string['search:activity'] = 'Folder';
$string['showdownloadfolder'] = 'Show download folder button';
$string['showdownloadfolder_help'] = 'If set to \'yes\', a button will be displayed allowing the contents of the folder to be downloaded as a zip file.';
$string['showexpanded'] = 'Show subfolders expanded';
$string['showexpanded_help'] = 'If set to \'yes\', subfolders are shown expanded by default; otherwise they are shown collapsed.';
$string['maxsizetodownload'] = 'Maximum folder download size (MB)';
$string['maxsizetodownload_help'] = 'The maximum size of folder that can be downloaded as a zip file. If set to zero, the folder size is unlimited.';
$string['explorertitle'] = 'File Explorer';
$string['exploreraddress'] = 'Address';
$string['explorertree'] = 'Navigation pane';
$string['explorercontents'] = 'Folder contents';
$string['explorercolumnname'] = 'Name';
$string['explorercolumndate'] = 'Date modified';
$string['explorercolumntype'] = 'Type';
$string['explorercolumnsize'] = 'Size';
$string['explorerempty'] = 'This folder is empty.';
$string['foldernavigation'] = 'Folder navigation';
$string['navigationheader'] = 'Navigation';
$string['navigationcontrols'] = 'Navigation controls';
$string['navigationback'] = 'Back';
$string['navigationgoback'] = 'Go back';
$string['navigationforward'] = 'Forward';
$string['navigationgoforward'] = 'Go forward';
$string['navigationup'] = 'Up';
$string['navigationgoup'] = 'Go up';
$string['folderbreadcrumbs'] = 'Folder breadcrumbs';
$string['searchplaceholder'] = 'Search files and folders...';
$string['searcharia'] = 'Search';
$string['sortbyname'] = 'Name';
$string['sortbydate'] = 'Date modified';
$string['sortbytype'] = 'Type';
$string['sortbysize'] = 'Size';
$string['sortlabel'] = 'Sort by:';
$string['sortnameasc'] = 'Name (A to Z)';
$string['sortnamedesc'] = 'Name (Z to A)';
$string['sortdatenewest'] = 'Date modified (newest first)';
$string['sortdateoldest'] = 'Date modified (oldest first)';
$string['sorttypeasc'] = 'Type (A to Z)';
$string['sorttypedesc'] = 'Type (Z to A)';
$string['sortsizedesc'] = 'Size (largest first)';
$string['sortsizeasc'] = 'Size (smallest first)';
$string['sortdirectionaria'] = 'Sort direction';
$string['sortascending'] = 'Ascending';
$string['sortdescending'] = 'Descending';
$string['filterlabel'] = 'Show:';
$string['filterall'] = 'All items';
$string['filterfolders'] = 'Folders only';
$string['filterfiles'] = 'Files only';
$string['filterimages'] = 'Images';
$string['filterdocuments'] = 'Documents';
$string['filtervideos'] = 'Videos';
$string['filterspreadsheets'] = 'Spreadsheets';
$string['filterpresentations'] = 'Presentations';
$string['filteraudios'] = 'Audio';
$string['filterarchives'] = 'Archives';
$string['filtercode'] = 'Code';
$string['filterother'] = 'Other files';
$string['filterresults'] = 'Showing {$a->visible} of {$a->total} items';
$string['viewgrid'] = 'Grid view';
$string['viewlist'] = 'List view';
$string['viewdetails'] = 'Details view';
$string['tableheadername'] = 'Name';
$string['tableheadertype'] = 'Type';
$string['tableheadersize'] = 'Size';
$string['tableheaderdatemodified'] = 'Date modified';
$string['emptyfolder'] = 'This folder is empty';
$string['noresults'] = 'No items match your search';
$string['clearsearch'] = 'Clear search';
$string['togglefolder'] = 'Toggle folder';
$string['selectedcount'] = '{$a} selected';
$string['itemcounts'] = '{$a} items';
$string['foldercountsingular'] = '{$a} folder';
$string['foldercountplural'] = '{$a} folders';
$string['filecountsingular'] = '{$a} file';
$string['filecountplural'] = '{$a} files';
$string['folderfilecounts'] = '{$a->folders} folders, {$a->files} files';
$string['selectitem'] = 'Select {$a}';
$string['windowminimize'] = 'Minimise';
$string['windowmaximize'] = 'Maximise';
$string['windowclose'] = 'Close';
$string['statusready'] = 'Ready';
$string['statusdetails'] = 'View details';
$string['ribbontabfile'] = 'File';
$string['ribbontabhome'] = 'Home';
$string['ribbontabshare'] = 'Share';
$string['ribbontabview'] = 'View';
$string['ribbonfilemenuopen'] = 'Open in new window';
$string['ribbonhomeclipboard'] = 'Clipboard';
$string['ribbonhomeclipboardpaste'] = 'Paste';
$string['ribbonhomeclipboardcut'] = 'Cut';
$string['ribbonhomeclipboardcopy'] = 'Copy';
$string['ribbonhomeclipboardcopyto'] = 'Copy to';
$string['ribbonhomeclipboardmoveto'] = 'Move to';
$string['ribbonhomeorganize'] = 'Organise';
$string['ribbonhomeorganizedelete'] = 'Delete';
$string['ribbonhomeorganizerename'] = 'Rename';
$string['ribbonhomeorganizeproperties'] = 'Properties';
$string['ribbonhomenew'] = 'New';
$string['ribbonhomenewfolder'] = 'New folder';
$string['ribbonhomeopen'] = 'Open';
$string['ribbonhomeopenopen'] = 'Open';
$string['ribbonhomeopenhistory'] = 'History';
$string['ribbonhomeselect'] = 'Select';
$string['ribbonhomeselectall'] = 'Select all';
$string['ribbonhomeselectnone'] = 'Select none';
$string['ribbonhomeselectinvert'] = 'Invert selection';
$string['ribbongroupshare'] = 'Share';
$string['ribbonsharesendemail'] = 'Email';
$string['ribbonsharesendzip'] = 'Zip';
$string['ribbonsharesendburn'] = 'Burn to disc';
$string['ribbonsharespecialshare'] = 'Share with';
$string['ribbonsharespecialadvanced'] = 'Advanced security';
$string['ribbonviewlayout'] = 'Layout';
$string['ribbonviewextraicons'] = 'Extra large icons';
$string['ribbonviewdetails'] = 'Details';
$string['ribbonviewlist'] = 'List';
$string['ribbonviewtiles'] = 'Tiles';
$string['ribbonviewnavpane'] = 'Navigation pane';
$string['ribbonviewpreviewpane'] = 'Preview pane';
