<?php

class block_carousel_educo_edit_form extends block_edit_form {

    protected function specific_definition($mform) {
        global $CFG;

        $itemsnumber = 1;
        if (isset($this->block->config->itemsnumber)) {
            $itemsnumber = $this->block->config->itemsnumber;
        }

        // Add block settings header
        $mform->addElement('header', 'config_header', get_string('blocksettings', 'block_carousel_educo'));

        // Dropdown to select the number of slides
        $itemsrange = range(1, 5); // Assuming a max of 5 slides as per the updated requirement
        $mform->addElement('select', 'config_itemsnumber', get_string('config_items', 'block_carousel_educo'), $itemsrange);
        $mform->setDefault('config_itemsnumber', 1);

        // Loop to create form fields for each slide
        for ($i = 1; $i <= $itemsnumber; $i++) {
            $mform->addElement('header', 'config_educo_item' . $i, get_string('config_item', 'block_carousel_educo') . ' ' . $i);

            // Title
            $mform->addElement('text', 'config_item_title' . $i, get_string('config_title', 'block_carousel_educo', $i));
            $mform->setDefault('config_item_title' . $i, "Diapositiva $i Título");
            $mform->setType('config_item_title' . $i, PARAM_TEXT);

            // Text
            $mform->addElement('textarea', 'config_item_text' . $i, get_string('config_text', 'block_carousel_educo', $i), 'wrap="virtual" rows="4" cols="50"');
            $mform->setDefault('config_item_text' . $i, "Texto de la Diapositiva $i");
            $mform->setType('config_item_text' . $i, PARAM_TEXT);

            // Image file manager
            $mform->addElement('filemanager', 'config_item_image' . $i, get_string('config_item_image', 'block_carousel_educo', $i), null, array(
                'subdirs' => 0,
                'maxbytes' => $CFG->maxbytes,
                'areamaxbytes' => 10485760,  // 10MB
                'maxfiles' => 1,
                'accepted_types' => array('image')
            ));

            // Button text
            $mform->addElement('text', 'config_item_button' . $i, get_string('config_button', 'block_carousel_educo', $i));
            $mform->setDefault('config_item_button' . $i, '');
            $mform->setType('config_item_button' . $i, PARAM_TEXT);

            // Button link
            $mform->addElement('text', 'config_item_link' . $i, get_string('config_link', 'block_carousel_educo', $i));
            $mform->setDefault('config_item_link' . $i, $CFG->wwwroot . '/');
            $mform->setType('config_item_link' . $i, PARAM_URL);
        }
    }

    public function set_data($defaults) {
        if (!empty($this->block->config) && is_object($this->block->config)) {
            for ($i = 1; $i <= $this->block->config->itemsnumber; $i++) {
                $draftitemid = file_get_submitted_draft_itemid('config_item_image' . $i);
                file_prepare_draft_area($draftitemid, $this->block->context->id, 'block_carousel_educo', 'content', $i,
                    array('subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1));
                $defaults->{'config_item_image'.$i} = $draftitemid;
            }
        }
        parent::set_data($defaults);
    }

    public function get_data() {
        $data = parent::get_data();
        if (!empty($data->config_itemsnumber)) {
            for ($i = 1; $i <= $data->config_itemsnumber; $i++) {
                $itemid = $i;
                file_save_draft_area_files($data->{'config_item_image'.$i}, $this->block->context->id, 'block_carousel_educo', 'content', $itemid,
                    array('subdirs' => 0, 'maxbytes' => $CFG->maxbytes, 'maxfiles' => 1));
            }
        }
        return $data;
    }
}