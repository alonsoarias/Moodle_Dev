<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class mod_folder_custom_renderer extends plugin_renderer_base {

    /**
     * Returns html to display the content of mod_folder_custom
     * OneDrive-style presentation with original data structure
     *
     * @param stdClass $folder_custom record from 'folder_custom' table
     * @return string
     */
    public function display_folder_custom(stdClass $folder_custom) {
        $folder_custominstances = get_fast_modinfo($folder_custom->course)->get_instances_of('folder_custom');
        if (!isset($folder_custominstances[$folder_custom->id]) ||
                !($cm = $folder_custominstances[$folder_custom->id]) ||
                !($context = context_module::instance($cm->id))) {
            return '';
        }

        $data = [];
        
        // Intro
        if (trim($folder_custom->intro)) {
            if ($folder_custom->display == folder_custom_DISPLAY_INLINE && $cm->showdescription) {
                $data['intro'] = format_module_intro('folder_custom', $folder_custom, $cm->id, false);
            }
        }

        // Buttons
        $buttons = [];
        $canmanagefolder_customfiles = has_capability('mod/folder_custom:managefiles', $context);
        $canmanagecourseactivities = has_capability('moodle/course:manageactivities', $context);
        
        $modulebase = '/mod/' . $cm->modname;

        if ($canmanagefolder_customfiles && ($folder_custom->display != folder_custom_DISPLAY_INLINE || !$canmanagecourseactivities)) {
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

        $downloadable = folder_custom_archive_available($folder_custom, $cm);
        if ($downloadable) {
            $downloadbutton = new single_button(
                new moodle_url($modulebase . '/download_folder_custom.php', ['id' => $cm->id]),
                get_string('downloadfolder_custom', 'folder_custom'), 
                'get'
            );
            $downloadbutton->class = 'navitem ms-auto';
            $data['download_button'] = $downloadbutton->export_for_template($this->output);
            $data['hasbuttons'] = true;
        }

        // Get folder_custom tree (MANTENER LÓGICA ORIGINAL)
        $folder_customtree = new folder_custom_tree($folder_custom, $cm);
        if ($folder_custom->display == folder_custom_DISPLAY_INLINE) {
            $folder_customtree->dir['dirname'] = $cm->get_formatted_name(array('escape' => false));
        }

        $data['id'] = 'folder_custom_tree_' . $cm->id;
        $data['showexpanded'] = !empty($folder_customtree->folder_custom->showexpanded);

        $rootname = format_string($folder_custom->name, true, ['context' => $context]);
        $data['rootname'] = $rootname;

        // Convertir estructura a elementos planos para grid (NUEVA FUNCIÓN)
        $data['items'] = $this->flatten_tree_for_grid($folder_customtree, $folder_customtree->dir);
        $data['has_items'] = !empty($data['items']);

        // Construir estructura de navegación estilo explorador.
        $data['tree'] = $this->build_tree_structure($folder_customtree, $rootname);
        $data['has_tree'] = !empty($data['tree']);

        $strings = $this->get_template_strings();
        $data['strings'] = $strings;
        $data['stringsjson'] = json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return $this->render_from_template('mod_folder_custom/folder_custom', $data);
    }

    /**
     * NUEVA FUNCIÓN: Aplanar árbol de archivos para vista grid
     * 
     * @param folder_custom_tree $tree
     * @param array $dir
     * @param string $path
     * @return array
     */
    protected function flatten_tree_for_grid($tree, $dir, $path = '') {
        $items = [];

        $encodedparentpath = $this->encode_path($path);

        // Procesar subdirectorios.
        if (!empty($dir['subdirs'])) {
            foreach ($dir['subdirs'] as $subdir) {
                $subdirname = $subdir['dirname'];
                $newpath = $path ? $path . '/' . $subdirname : $subdirname;
                $encodedpath = $this->encode_path($newpath);
                $displayname = format_string($subdirname, true, ['context' => $tree->context]);

                $items[] = [
                    'name' => $displayname,
                    'type' => 'folder_custom',
                    'icon' => $this->output->pix_icon(file_folder_custom_icon(), $displayname, 'moodle', ['class' => 'icon-folder_custom']),
                    'icon_class' => 'folder_custom-icon-folder_custom',
                    'size' => '',
                    'size_bytes' => 0,
                    'modified' => '',
                    'modified_timestamp' => 0,
                    'extension' => '',
                    'file_category' => 'folder_custom',
                    'path' => $newpath,
                    'path_encoded' => $encodedpath,
                    'parent_path' => $path,
                    'parent_path_encoded' => $encodedparentpath,
                    'folder_custom_path' => $newpath,
                    'folder_custom_path_encoded' => $encodedpath,
                    'is_folder_custom' => true,
                    'has_items' => !empty($subdir['subdirs']) || !empty($subdir['files'])
                ];

                $items = array_merge($items, $this->flatten_tree_for_grid($tree, $subdir, $newpath));
            }
        }

        // Procesar archivos.
        if (!empty($dir['files'])) {
            foreach ($dir['files'] as $file) {
                $filename = $file->get_filename();
                $filesize = $file->get_filesize();
                $filesize_display = display_size($filesize);
                $modified_timestamp = $file->get_timemodified();
                $modified = userdate($modified_timestamp, get_string('strftimedatetime', 'langconfig'));

                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $file_category = $this->get_file_category($extension);

                $url = moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $filename,
                    false
                );

                if ($tree->folder_custom->forcedownload) {
                    $url->param('forcedownload', 1);
                }

                // Determinar si es imagen para preview.
                $is_image = file_extension_in_typegroup($filename, 'web_image');
                if ($is_image) {
                    $preview_url = $url->out(false, ['preview' => 'thumb', 'oid' => $modified_timestamp]);
                    $icon = html_writer::empty_tag('img', [
                        'src' => $preview_url,
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
                    'size' => $filesize_display,
                    'size_bytes' => $filesize,
                    'modified' => $modified,
                    'modified_timestamp' => $modified_timestamp,
                    'extension' => $extension,
                    'file_category' => $file_category,
                    'url' => $url->out(false),
                    'path' => $path,
                    'path_encoded' => $encodedparentpath,
                    'parent_path' => $path,
                    'parent_path_encoded' => $encodedparentpath,
                    'is_file' => true,
                    'has_preview' => $is_image,
                    'mimetype' => $file->get_mimetype()
                ];
            }
        }

        return $items;
    }

    /**
     * Build navigation structure for explorer view.
     *
     * @param folder_custom_tree $tree
     * @param string $rootname
     * @return array
     */
    protected function build_tree_structure(folder_custom_tree $tree, string $rootname): array {
        $rooticon = $this->output->pix_icon(
            file_folder_custom_icon(),
            $rootname,
            'moodle',
            ['class' => 'tree-folder_custom-icon']
        );

        $subdirs = $this->renderable_tree_elements(
            $tree,
            $tree->dir,
            '',
            !empty($tree->folder_custom->showexpanded)
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
     * Encode folder_custom path segments for safe HTML attributes.
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
     * Get file category for filtering
     *
     * @param string $extension
     * @return string
     */
    protected function get_file_category($extension) {
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
     * Get CSS class based on file extension
     * 
     * @param string $filename
     * @return string
     */
    protected function get_file_icon_class($filename) {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $icon_map = [
            'pdf' => 'file-pdf',
            'doc' => 'file-word', 'docx' => 'file-word',
            'xls' => 'file-excel', 'xlsx' => 'file-excel',
            'ppt' => 'file-powerpoint', 'pptx' => 'file-powerpoint',
            'jpg' => 'file-image', 'jpeg' => 'file-image', 'png' => 'file-image', 'gif' => 'file-image',
            'mp4' => 'file-video', 'avi' => 'file-video', 'mov' => 'file-video',
            'mp3' => 'file-audio', 'wav' => 'file-audio',
            'zip' => 'file-archive', 'rar' => 'file-archive',
        ];
        
        return isset($icon_map[$extension]) ? $icon_map[$extension] : 'file-generic';
    }

    /**
     * @deprecated since Moodle 4.3
     */
    protected function htmllize_tree($tree, $dir) {
        debugging('Method htmllize_tree() is deprecated.', DEBUG_DEVELOPER);
        
        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_custom_icon(), $subdir['dirname'], 'moodle');
            $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                html_writer::tag('span', s($subdir['dirname']), array('class' => 'fp-filename'));
            $filename = html_writer::tag('div', $filename, array('class' => 'fp-filename-icon'));
            $result .= html_writer::tag('li', $filename. $this->htmllize_tree($tree, $subdir));
        }
        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename, false);
            $filenamedisplay = clean_filename($filename);
            if (file_extension_in_typegroup($filename, 'web_image')) {
                $image = $url->out(false, array('preview' => 'tinyicon', 'oid' => $file->get_timemodified()));
                $image = html_writer::empty_tag('img', array('src' => $image));
            } else {
                $image = $this->output->pix_icon(file_file_icon($file), $filenamedisplay, 'moodle');
            }
            $filename = html_writer::tag('span', $image, array('class' => 'fp-icon')).
                html_writer::tag('span', $filenamedisplay, array('class' => 'fp-filename'));
            $urlparams = null;
            if ($tree->folder_custom->forcedownload) {
                $urlparams = ['forcedownload' => 1];
            }
            $filename = html_writer::tag('span',
                html_writer::link($url->out(false, $urlparams), $filename),
                ['class' => 'fp-filename-icon']
            );
            $result .= html_writer::tag('li', $filename);
        }
        $result .= '</ul>';
        return $result;
    }

    /**
     * Build directory nodes for navigation tree.
     *
     * @param folder_custom_tree $tree
     * @param array $dir
     * @param string $path
     * @param bool $expanded
     * @return array
     */
    protected function renderable_tree_elements(folder_custom_tree $tree, array $dir, string $path = '', bool $expanded = false): array {
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
                    file_folder_custom_icon(),
                    $displayname,
                    'moodle',
                    ['class' => 'tree-folder_custom-icon']
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
     * Returns the collection of strings required by the folder_custom template.
     *
     * @return array
     */
    protected function get_template_strings(): array {
        return [
            'navigationaria' => get_string('folder_customnavigation', 'mod_folder_custom'),
            'navigationheader' => get_string('navigationheader', 'mod_folder_custom'),
            'navigationcontrols' => get_string('navigationcontrols', 'mod_folder_custom'),
            'navbacktitle' => get_string('navigationback', 'mod_folder_custom'),
            'navbackaria' => get_string('navigationgoback', 'mod_folder_custom'),
            'navforwardtitle' => get_string('navigationforward', 'mod_folder_custom'),
            'navforwardaria' => get_string('navigationgoforward', 'mod_folder_custom'),
            'navuptitle' => get_string('navigationup', 'mod_folder_custom'),
            'navuparia' => get_string('navigationgoup', 'mod_folder_custom'),
            'breadcrumbsaria' => get_string('folder_custombreadcrumbs', 'mod_folder_custom'),
            'searchplaceholder' => get_string('searchplaceholder', 'mod_folder_custom'),
            'searcharia' => get_string('searcharia', 'mod_folder_custom'),
            'sortlabel' => get_string('sortlabel', 'mod_folder_custom'),
            'sortnameasc' => get_string('sortnameasc', 'mod_folder_custom'),
            'sortnamedesc' => get_string('sortnamedesc', 'mod_folder_custom'),
            'sortdatenewest' => get_string('sortdatenewest', 'mod_folder_custom'),
            'sortdateoldest' => get_string('sortdateoldest', 'mod_folder_custom'),
            'sorttypeasc' => get_string('sorttypeasc', 'mod_folder_custom'),
            'sorttypedesc' => get_string('sorttypedesc', 'mod_folder_custom'),
            'sortsizedesc' => get_string('sortsizedesc', 'mod_folder_custom'),
            'sortsizeasc' => get_string('sortsizeasc', 'mod_folder_custom'),
            'filterlabel' => get_string('filterlabel', 'mod_folder_custom'),
            'filterall' => get_string('filterall', 'mod_folder_custom'),
            'filterfolder_customs' => get_string('filterfolder_customs', 'mod_folder_custom'),
            'filterfiles' => get_string('filterfiles', 'mod_folder_custom'),
            'filterimages' => get_string('filterimages', 'mod_folder_custom'),
            'filterdocuments' => get_string('filterdocuments', 'mod_folder_custom'),
            'filtervideos' => get_string('filtervideos', 'mod_folder_custom'),
            'viewgrid' => get_string('viewgrid', 'mod_folder_custom'),
            'viewlist' => get_string('viewlist', 'mod_folder_custom'),
            'viewdetails' => get_string('viewdetails', 'mod_folder_custom'),
            'tableheadername' => get_string('tableheadername', 'mod_folder_custom'),
            'tableheadertype' => get_string('tableheadertype', 'mod_folder_custom'),
            'tableheadersize' => get_string('tableheadersize', 'mod_folder_custom'),
            'tableheaderdatemodified' => get_string('tableheaderdatemodified', 'mod_folder_custom'),
            'emptyfolder_custom' => get_string('emptyfolder_custom', 'mod_folder_custom'),
            'noresults' => get_string('noresults', 'mod_folder_custom'),
            'clearsearch' => get_string('clearsearch', 'mod_folder_custom'),
            'togglefolder_custom' => get_string('togglefolder_custom', 'mod_folder_custom'),
            'selectedcount' => get_string('selectedcount', 'mod_folder_custom'),
            'itemcounts' => get_string('itemcounts', 'mod_folder_custom'),
            'folder_customcountsingular' => get_string('folder_customcountsingular', 'mod_folder_custom'),
            'folder_customcountplural' => get_string('folder_customcountplural', 'mod_folder_custom'),
            'filecountsingular' => get_string('filecountsingular', 'mod_folder_custom'),
            'filecountplural' => get_string('filecountplural', 'mod_folder_custom'),
            'folder_customfilecounts' => get_string('folder_customfilecounts', 'mod_folder_custom'),
            'modulename' => get_string('modulename', 'mod_folder_custom'),
        ];
    }
}

class folder_custom_tree implements renderable {
    public $context;
    public $folder_custom;
    public $cm;
    public $dir;

    public function __construct($folder_custom, $cm) {
        $this->folder_custom = $folder_custom;
        $this->cm     = $cm;
        $this->context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_folder_custom', 'content', 0);
    }
}