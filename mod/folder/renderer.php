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
 * Folder module renderer.
 *
 * @package   mod_folder
 * @copyright 2009 Petr Skoda  {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class mod_folder_renderer extends plugin_renderer_base {

    /**
     * Returns html to display the content of mod_folder using an Explorer style layout.
     *
     * @param stdClass $folder Record from the folder table.
     * @return string
     */
    public function display_folder(stdClass $folder): string {
        $folderinstances = get_fast_modinfo($folder->course)->get_instances_of('folder');
        if (!isset($folderinstances[$folder->id]) ||
                !($cm = $folderinstances[$folder->id]) ||
                !($context = context_module::instance($cm->id))) {
            // Some error in parameters. Don't throw errors in renderer, just return empty string.
            return '';
        }

        $data = [];

        if (trim($folder->intro)) {
            if ($folder->display == FOLDER_DISPLAY_INLINE && $cm->showdescription) {
                // For "display inline" do not filter, filters run at display time.
                $data['intro'] = format_module_intro('folder', $folder, $cm->id, false);
            }
        }

        $canmanagefolderfiles = has_capability('mod/folder:managefiles', $context);
        $canmanagecourseactivities = has_capability('moodle/course:manageactivities', $context);

        $modulebase = '/mod/' . $cm->modname;

        if ($canmanagefolderfiles && ($folder->display != FOLDER_DISPLAY_INLINE || !$canmanagecourseactivities)) {
            $editbutton = new single_button(
                new moodle_url($modulebase . '/edit.php', ['id' => $cm->id]),
                get_string('edit'),
                'post',
                single_button::BUTTON_PRIMARY
            );
            $editbutton->class = 'navitem';
            $data['edit_button'] = $editbutton->export_for_template($this->output);
            $data['hasbuttons'] = true;
        }

        $downloadable = folder_archive_available($folder, $cm);
        if ($downloadable) {
            $downloadbutton = new single_button(
                new moodle_url($modulebase . '/download_folder.php', ['id' => $cm->id]),
                get_string('downloadfolder', 'folder'),
                'get'
            );
            $downloadbutton->class = 'navitem ms-auto';
            $data['download_button'] = $downloadbutton->export_for_template($this->output);
            $data['hasbuttons'] = true;
        }

        $foldertree = new folder_tree($folder, $cm);
        if ($folder->display == FOLDER_DISPLAY_INLINE) {
            // Display module name as the name of the root directory.
            $foldertree->dir['dirname'] = $cm->get_formatted_name(['escape' => false]);
        }

        $data['id'] = 'folder_tree_' . $cm->id;
        $data['showexpanded'] = !empty($foldertree->folder->showexpanded);

        $rootname = format_string($folder->name, true, ['context' => $context]);
        $data['rootname'] = $rootname;

        $data['items'] = $this->flatten_tree_for_grid($foldertree, $foldertree->dir);
        $data['has_items'] = !empty($data['items']);

        $data['tree'] = $this->build_tree_structure($foldertree, $rootname);
        $data['has_tree'] = !empty($data['tree']);

        $strings = $this->get_template_strings();
        $data['strings'] = $strings;

        $moduleconfig = [
            'containerid' => $data['id'],
            'showexpanded' => !empty($data['showexpanded']),
        ];

        $this->page->requires->js_call_amd('mod_folder/windows_explorer', 'init', [$moduleconfig]);

        return $this->render_from_template('mod_folder/folder', $data);
    }

    /**
     * Flatten the folder tree into a collection suitable for the explorer views.
     *
     * @param folder_tree $tree The folder tree to flatten.
     * @param array $dir Directory contents to process.
     * @param string $path Current path within the tree.
     * @return array
     */
    protected function flatten_tree_for_grid(folder_tree $tree, array $dir, string $path = ''): array {
        $items = [];
        $encodedparentpath = $this->encode_path($path);

        if (!empty($dir['subdirs'])) {
            foreach ($dir['subdirs'] as $subdir) {
                $subdirname = $subdir['dirname'];
                $newpath = $path ? $path . '/' . $subdirname : $subdirname;
                $encodedpath = $this->encode_path($newpath);
                $displayname = format_string($subdirname, true, ['context' => $tree->context]);

                $items[] = [
                    'name' => $displayname,
                    'type' => 'folder',
                    'icon' => $this->output->pix_icon(file_folder_icon(), $displayname, 'moodle', ['class' => 'icon-folder']),
                    'icon_class' => 'folder-icon-folder',
                    'size' => '',
                    'size_bytes' => 0,
                    'modified' => '',
                    'modified_timestamp' => 0,
                    'extension' => '',
                    'file_category' => 'folder',
                    'path' => $newpath,
                    'path_encoded' => $encodedpath,
                    'parent_path' => $path,
                    'parent_path_encoded' => $encodedparentpath,
                    'folder_path' => $newpath,
                    'folder_path_encoded' => $encodedpath,
                    'is_folder' => true,
                    'has_items' => !empty($subdir['subdirs']) || !empty($subdir['files'])
                ];

                $items = array_merge($items, $this->flatten_tree_for_grid($tree, $subdir, $newpath));
            }
        }

        if (!empty($dir['files'])) {
            foreach ($dir['files'] as $file) {
                $filename = $file->get_filename();
                $filesize = $file->get_filesize();
                $filesizedisplay = display_size($filesize);
                $modifiedtimestamp = $file->get_timemodified();
                $modified = userdate($modifiedtimestamp, get_string('strftimedatetime', 'langconfig'));

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $filecategory = $this->get_file_category($extension);

                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $filename,
                    false
                );

                if ($tree->folder->forcedownload) {
                    $url->param('forcedownload', 1);
                }

                $isimage = file_extension_in_typegroup($filename, 'web_image');
                if ($isimage) {
                    $previewurl = $url->out(false, ['preview' => 'thumb', 'oid' => $modifiedtimestamp]);
                    $icon = html_writer::empty_tag('img', [
                        'src' => $previewurl,
                        'alt' => clean_filename($filename),
                        'class' => 'file-preview-img'
                    ]);
                } else {
                    $icon = $this->output->pix_icon(
                        file_file_icon($file),
                        clean_filename($filename),
                        'moodle',
                        ['class' => 'icon-file']
                    );
                }

                $items[] = [
                    'name' => clean_filename($filename),
                    'type' => 'file',
                    'icon' => $icon,
                    'icon_class' => $this->get_file_icon_class($filename),
                    'size' => $filesizedisplay,
                    'size_bytes' => $filesize,
                    'modified' => $modified,
                    'modified_timestamp' => $modifiedtimestamp,
                    'extension' => $extension,
                    'file_category' => $filecategory,
                    'url' => $url->out(false),
                    'path' => $path,
                    'path_encoded' => $encodedparentpath,
                    'parent_path' => $path,
                    'parent_path_encoded' => $encodedparentpath,
                    'is_file' => true,
                    'has_preview' => $isimage,
                    'mimetype' => $file->get_mimetype()
                ];
            }
        }

        return $items;
    }

    /**
     * Build navigation structure for explorer view.
     *
     * @param folder_tree $tree The folder tree to render.
     * @param string $rootname Display name for the root node.
     * @return array
     */
    protected function build_tree_structure(folder_tree $tree, string $rootname): array {
        $rooticon = $this->output->pix_icon(
            file_folder_icon(),
            $rootname,
            'moodle',
            ['class' => 'tree-folder-icon']
        );

        $subdirs = $this->renderable_tree_elements(
            $tree,
            $tree->dir,
            '',
            !empty($tree->folder->showexpanded)
        );

        return [[
            'name' => $rootname,
            'icon' => $rooticon,
            'path' => '',
            'path_encoded' => '',
            'parent_path' => '',
            'parent_path_encoded' => '',
            'isroot' => true,
            'hassubdirs' => !empty($subdirs),
            'expanded' => true,
            'subdirs' => $subdirs,
        ]];
    }

    /**
     * Encode folder path segments for safe HTML attributes.
     *
     * @param string $path
     * @return string
     */
    protected function encode_path(string $path): string {
        $path = trim($path, '/');
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        $encoded = array_map('rawurlencode', $segments);

        return implode('/', $encoded);
    }

    /**
     * Determine the general file category for filtering.
     *
     * @param string $extension
     * @return string
     */
    protected function get_file_category(string $extension): string {
        $categories = [
            'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'],
            'document' => ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt'],
            'spreadsheet' => ['xls', 'xlsx', 'csv', 'ods'],
            'presentation' => ['ppt', 'pptx', 'odp'],
            'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'],
            'audio' => ['mp3', 'wav', 'ogg', 'flac', 'm4a'],
            'archive' => ['zip', 'rar', '7z', 'tar', 'gz'],
            'code' => ['html', 'css', 'js', 'php', 'py', 'java', 'cpp', 'c'],
        ];

        foreach ($categories as $category => $extensions) {
            if (in_array($extension, $extensions)) {
                return $category;
            }
        }

        return 'other';
    }

    /**
     * Get CSS class based on file extension.
     *
     * @param string $filename
     * @return string
     */
    protected function get_file_icon_class(string $filename): string {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $iconmap = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word', 'docx' => 'file-word',
            'xls' => 'file-excel', 'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint', 'pptx' => 'file-powerpoint',
            'jpg' => 'file-image', 'jpeg' => 'file-image', 'png' => 'file-image', 'gif' => 'file-image',
            'mp4' => 'file-video', 'avi' => 'file-video', 'mov' => 'file-video',
            'mp3' => 'file-audio', 'wav' => 'file-audio',
            'zip' => 'file-archive', 'rar' => 'file-archive',
        ];

        return $iconmap[$extension] ?? 'file-generic';
    }

    /**
     * Build directory nodes for navigation tree.
     *
     * @param folder_tree $tree
     * @param array $dir
     * @param string $path
     * @param bool $expanded
     * @return array
     */
    protected function renderable_tree_elements(folder_tree $tree, array $dir, string $path = '', bool $expanded = false): array {
        if (empty($dir['subdirs'])) {
            return [];
        }

        $elements = [];

        foreach ($dir['subdirs'] as $subdir) {
            $subdirname = $subdir['dirname'];
            $newpath = $path ? $path . '/' . $subdirname : $subdirname;
            $encodedpath = $this->encode_path($newpath);
            $encodedparent = $this->encode_path($path);
            $displayname = format_string($subdirname, true, ['context' => $tree->context]);

            $children = $this->renderable_tree_elements($tree, $subdir, $newpath, $expanded);

            $elements[] = [
                'name' => $displayname,
                'icon' => $this->output->pix_icon(
                    file_folder_icon(),
                    $displayname,
                    'moodle',
                    ['class' => 'tree-folder-icon']
                ),
                'path' => $newpath,
                'path_encoded' => $encodedpath,
                'parent_path' => $path,
                'parent_path_encoded' => $encodedparent,
                'hassubdirs' => !empty($children),
                'expanded' => $expanded,
                'subdirs' => $children,
            ];
        }

        return $elements;
    }

    /**
     * Returns the collection of strings required by the folder template.
     *
     * @return array
     */
    protected function get_template_strings(): array {
        return [
            'explorertitle' => get_string('explorertitle', 'mod_folder'),
            'exploreraddress' => get_string('exploreraddress', 'mod_folder'),
            'explorertree' => get_string('explorertree', 'mod_folder'),
            'explorercontents' => get_string('explorercontents', 'mod_folder'),
            'navigationaria' => get_string('foldernavigation', 'mod_folder'),
            'navigationheader' => get_string('navigationheader', 'mod_folder'),
            'navigationcontrols' => get_string('navigationcontrols', 'mod_folder'),
            'navbacktitle' => get_string('navigationback', 'mod_folder'),
            'navbackaria' => get_string('navigationgoback', 'mod_folder'),
            'navforwardtitle' => get_string('navigationforward', 'mod_folder'),
            'navforwardaria' => get_string('navigationgoforward', 'mod_folder'),
            'navuptitle' => get_string('navigationup', 'mod_folder'),
            'navuparia' => get_string('navigationgoup', 'mod_folder'),
            'breadcrumbsaria' => get_string('folderbreadcrumbs', 'mod_folder'),
            'searchplaceholder' => get_string('searchplaceholder', 'mod_folder'),
            'searcharia' => get_string('searcharia', 'mod_folder'),
            'sortbyname' => get_string('sortbyname', 'mod_folder'),
            'sortbydate' => get_string('sortbydate', 'mod_folder'),
            'sortbytype' => get_string('sortbytype', 'mod_folder'),
            'sortbysize' => get_string('sortbysize', 'mod_folder'),
            'sortlabel' => get_string('sortlabel', 'mod_folder'),
            'sortnameasc' => get_string('sortnameasc', 'mod_folder'),
            'sortnamedesc' => get_string('sortnamedesc', 'mod_folder'),
            'sortdatenewest' => get_string('sortdatenewest', 'mod_folder'),
            'sortdateoldest' => get_string('sortdateoldest', 'mod_folder'),
            'sorttypeasc' => get_string('sorttypeasc', 'mod_folder'),
            'sorttypedesc' => get_string('sorttypedesc', 'mod_folder'),
            'sortsizedesc' => get_string('sortsizedesc', 'mod_folder'),
            'sortsizeasc' => get_string('sortsizeasc', 'mod_folder'),
            'sortdirectionaria' => get_string('sortdirectionaria', 'mod_folder'),
            'sortascending' => get_string('sortascending', 'mod_folder'),
            'sortdescending' => get_string('sortdescending', 'mod_folder'),
            'filterlabel' => get_string('filterlabel', 'mod_folder'),
            'filterall' => get_string('filterall', 'mod_folder'),
            'filterfolders' => get_string('filterfolders', 'mod_folder'),
            'filterfiles' => get_string('filterfiles', 'mod_folder'),
            'filterimages' => get_string('filterimages', 'mod_folder'),
            'filterdocuments' => get_string('filterdocuments', 'mod_folder'),
            'filtervideos' => get_string('filtervideos', 'mod_folder'),
            'filterspreadsheets' => get_string('filterspreadsheets', 'mod_folder'),
            'filterpresentations' => get_string('filterpresentations', 'mod_folder'),
            'filteraudios' => get_string('filteraudios', 'mod_folder'),
            'filterarchives' => get_string('filterarchives', 'mod_folder'),
            'filtercode' => get_string('filtercode', 'mod_folder'),
            'filterother' => get_string('filterother', 'mod_folder'),
            'filterresults' => get_string('filterresults', 'mod_folder'),
            'viewgrid' => get_string('viewgrid', 'mod_folder'),
            'viewlist' => get_string('viewlist', 'mod_folder'),
            'viewdetails' => get_string('viewdetails', 'mod_folder'),
            'tableheadername' => get_string('tableheadername', 'mod_folder'),
            'tableheadertype' => get_string('tableheadertype', 'mod_folder'),
            'tableheadersize' => get_string('tableheadersize', 'mod_folder'),
            'tableheaderdatemodified' => get_string('tableheaderdatemodified', 'mod_folder'),
            'emptyfolder' => get_string('emptyfolder', 'mod_folder'),
            'noresults' => get_string('noresults', 'mod_folder'),
            'clearsearch' => get_string('clearsearch', 'mod_folder'),
            'togglefolder' => get_string('togglefolder', 'mod_folder'),
            'selectedcount' => get_string('selectedcount', 'mod_folder'),
            'itemcounts' => get_string('itemcounts', 'mod_folder'),
            'foldercountsingular' => get_string('foldercountsingular', 'mod_folder'),
            'foldercountplural' => get_string('foldercountplural', 'mod_folder'),
            'filecountsingular' => get_string('filecountsingular', 'mod_folder'),
            'filecountplural' => get_string('filecountplural', 'mod_folder'),
            'folderfilecounts' => get_string('folderfilecounts', 'mod_folder'),
            'modulename' => get_string('modulename', 'mod_folder'),
            'windowminimize' => get_string('windowminimize', 'mod_folder'),
            'windowmaximize' => get_string('windowmaximize', 'mod_folder'),
            'windowclose' => get_string('windowclose', 'mod_folder'),
            'statusready' => get_string('statusready', 'mod_folder'),
            'statusdetails' => get_string('statusdetails', 'mod_folder'),
            'ribbontabfile' => get_string('ribbontabfile', 'mod_folder'),
            'ribbontabhome' => get_string('ribbontabhome', 'mod_folder'),
            'ribbontabshare' => get_string('ribbontabshare', 'mod_folder'),
            'ribbontabview' => get_string('ribbontabview', 'mod_folder'),
            'ribbonfilemenuopen' => get_string('ribbonfilemenuopen', 'mod_folder'),
            'ribbonhomeclipboard' => get_string('ribbonhomeclipboard', 'mod_folder'),
            'ribbonhomeclipboardpaste' => get_string('ribbonhomeclipboardpaste', 'mod_folder'),
            'ribbonhomeclipboardcut' => get_string('ribbonhomeclipboardcut', 'mod_folder'),
            'ribbonhomeclipboardcopy' => get_string('ribbonhomeclipboardcopy', 'mod_folder'),
            'ribbonhomeclipboardcopyto' => get_string('ribbonhomeclipboardcopyto', 'mod_folder'),
            'ribbonhomeclipboardmoveto' => get_string('ribbonhomeclipboardmoveto', 'mod_folder'),
            'ribbonhomeorganize' => get_string('ribbonhomeorganize', 'mod_folder'),
            'ribbonhomeorganizedelete' => get_string('ribbonhomeorganizedelete', 'mod_folder'),
            'ribbonhomeorganizerename' => get_string('ribbonhomeorganizerename', 'mod_folder'),
            'ribbonhomeorganizeproperties' => get_string('ribbonhomeorganizeproperties', 'mod_folder'),
            'ribbonhomenew' => get_string('ribbonhomenew', 'mod_folder'),
            'ribbonhomenewfolder' => get_string('ribbonhomenewfolder', 'mod_folder'),
            'ribbonhomeopen' => get_string('ribbonhomeopen', 'mod_folder'),
            'ribbonhomeopenopen' => get_string('ribbonhomeopenopen', 'mod_folder'),
            'ribbonhomeopenhistory' => get_string('ribbonhomeopenhistory', 'mod_folder'),
            'ribbonhomeselect' => get_string('ribbonhomeselect', 'mod_folder'),
            'ribbonhomeselectall' => get_string('ribbonhomeselectall', 'mod_folder'),
            'ribbonhomeselectnone' => get_string('ribbonhomeselectnone', 'mod_folder'),
            'ribbonhomeselectinvert' => get_string('ribbonhomeselectinvert', 'mod_folder'),
            'ribbongroupshare' => get_string('ribbongroupshare', 'mod_folder'),
            'ribbonsharesendemail' => get_string('ribbonsharesendemail', 'mod_folder'),
            'ribbonsharesendzip' => get_string('ribbonsharesendzip', 'mod_folder'),
            'ribbonsharesendburn' => get_string('ribbonsharesendburn', 'mod_folder'),
            'ribbonsharespecialshare' => get_string('ribbonsharespecialshare', 'mod_folder'),
            'ribbonsharespecialadvanced' => get_string('ribbonsharespecialadvanced', 'mod_folder'),
            'ribbonviewlayout' => get_string('ribbonviewlayout', 'mod_folder'),
            'ribbonviewextraicons' => get_string('ribbonviewextraicons', 'mod_folder'),
            'ribbonviewdetails' => get_string('ribbonviewdetails', 'mod_folder'),
            'ribbonviewlist' => get_string('ribbonviewlist', 'mod_folder'),
            'ribbonviewtiles' => get_string('ribbonviewtiles', 'mod_folder'),
            'ribbonviewnavpane' => get_string('ribbonviewnavpane', 'mod_folder'),
            'ribbonviewpreviewpane' => get_string('ribbonviewpreviewpane', 'mod_folder'),
        ];
    }
}

class folder_tree implements renderable {
    public $context;
    public $folder;
    public $cm;
    public $dir;

    public function __construct($folder, $cm) {
        $this->folder = $folder;
        $this->cm = $cm;

        $this->context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_folder', 'content', 0);
    }
}
