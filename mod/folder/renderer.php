<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

class mod_folder_renderer extends plugin_renderer_base {

    /**
     * Returns html to display the content of mod_folder
     * OneDrive-style presentation with original data structure
     *
     * @param stdClass $folder record from 'folder' table
     * @return string
     */
    public function display_folder(stdClass $folder) {
        $folderinstances = get_fast_modinfo($folder->course)->get_instances_of('folder');
        if (!isset($folderinstances[$folder->id]) ||
                !($cm = $folderinstances[$folder->id]) ||
                !($context = context_module::instance($cm->id))) {
            return '';
        }

        $data = [];
        
        // Intro
        if (trim($folder->intro)) {
            if ($folder->display == FOLDER_DISPLAY_INLINE && $cm->showdescription) {
                $data['intro'] = format_module_intro('folder', $folder, $cm->id, false);
            }
        }

        // Buttons
        $buttons = [];
        $canmanagefolderfiles = has_capability('mod/folder:managefiles', $context);
        $canmanagecourseactivities = has_capability('moodle/course:manageactivities', $context);
        
        if ($canmanagefolderfiles && ($folder->display != FOLDER_DISPLAY_INLINE || !$canmanagecourseactivities)) {
            $editbutton = new single_button(
                new moodle_url('/mod/folder/edit.php', ['id' => $cm->id]),
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
                new moodle_url('/mod/folder/download_folder.php', ['id' => $cm->id]),
                get_string('downloadfolder', 'folder'), 
                'get'
            );
            $downloadbutton->class = 'navitem ms-auto';
            $data['download_button'] = $downloadbutton->export_for_template($this->output);
            $data['hasbuttons'] = true;
        }

        // Get folder tree (MANTENER LÓGICA ORIGINAL)
        $foldertree = new folder_tree($folder, $cm);
        if ($folder->display == FOLDER_DISPLAY_INLINE) {
            $foldertree->dir['dirname'] = $cm->get_formatted_name(array('escape' => false));
        }

        $data['id'] = 'folder_tree_' . $cm->id;
        $data['showexpanded'] = !empty($foldertree->folder->showexpanded);
        
        // Convertir estructura a elementos planos para grid (NUEVA FUNCIÓN)
        $data['items'] = $this->flatten_tree_for_grid($foldertree, $foldertree->dir);
        $data['has_items'] = !empty($data['items']);

        // Mantener también la estructura de árbol original para compatibilidad
        $data['dir'] = $this->renderable_tree_elements($foldertree, ['files' => [], 'subdirs' => [$foldertree->dir]]);

        return $this->render_from_template('mod_folder/folder', $data);
    }

    /**
     * NUEVA FUNCIÓN: Aplanar árbol de archivos para vista grid
     * 
     * @param folder_tree $tree
     * @param array $dir
     * @param string $path
     * @return array
     */
protected function flatten_tree_for_grid($tree, $dir, $path = '') {
    $items = [];
    
    // Procesar subdirectorios
    if (!empty($dir['subdirs'])) {
        foreach ($dir['subdirs'] as $subdir) {
            $subdirname = $subdir['dirname'];
            $newpath = $path ? $path . '/' . $subdirname : $subdirname;
            
            $items[] = [
                'name' => $subdirname,
                'type' => 'folder',
                'icon' => $this->output->pix_icon(file_folder_icon(), $subdirname, 'moodle', ['class' => 'icon-folder']),
                'icon_class' => 'folder-icon-folder',
                'size' => '',
                'size_bytes' => 0,
                'modified' => '',
                'modified_timestamp' => 0,
                'extension' => '',
                'file_category' => 'folder',
                'path' => $newpath,
                'is_folder' => true,
                'has_items' => !empty($subdir['subdirs']) || !empty($subdir['files'])
            ];
            
            $items = array_merge($items, $this->flatten_tree_for_grid($tree, $subdir, $newpath));
        }
    }
    
    // Procesar archivos
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
            
            if ($tree->folder->forcedownload) {
                $url->param('forcedownload', 1);
            }
            
            // Determinar si es imagen para preview
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
                'is_file' => true,
                'has_preview' => $is_image,
                'mimetype' => $file->get_mimetype()
            ];
        }
    }
    
    return $items;
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
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle');
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
            if ($tree->folder->forcedownload) {
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
     * Mantener función original para compatibilidad
     */
    protected function renderable_tree_elements(folder_tree $tree, array $dir): array {
        if (empty($dir['subdirs']) && empty($dir['files'])) {
            return [];
        }
        $elements = [];
        foreach ($dir['subdirs'] as $subdir) {
            $htmllize = $this->renderable_tree_elements($tree, $subdir);
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle');
            $elements[] = [
                'name' => $subdir['dirname'],
                'icon' => $image,
                'subdirs' => $htmllize,
                'hassubdirs' => !empty($htmllize),
            ];
        }
        foreach ($dir['files'] as $file) {
            $filename = $file->get_filename();
            $filenamedisplay = clean_filename($filename);

            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(),
                $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $filename, false);
            if (file_extension_in_typegroup($filename, 'web_image')) {
                $image = $url->out(false, ['preview' => 'tinyicon', 'oid' => $file->get_timemodified()]);
                $image = html_writer::empty_tag('img', ['src' => $image]);
            } else {
                $image = $this->output->pix_icon(file_file_icon($file), $filenamedisplay, 'moodle');
            }

            if ($tree->folder->forcedownload) {
                $url->param('forcedownload', 1);
            }

            $elements[] = [
                'name' => $filenamedisplay,
                'icon' => $image,
                'url' => $url,
                'subdirs' => null,
                'hassubdirs' => false,
            ];
        }

        return $elements;
    }
}

class folder_tree implements renderable {
    public $context;
    public $folder;
    public $cm;
    public $dir;

    public function __construct($folder, $cm) {
        $this->folder = $folder;
        $this->cm     = $cm;
        $this->context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'mod_folder', 'content', 0);
    }
}