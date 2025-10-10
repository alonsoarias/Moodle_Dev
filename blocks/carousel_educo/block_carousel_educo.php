<?php

global $CFG;
require_once($CFG->dirroot . '/theme/edash/inc/block_handler/get-content.php');

class block_carousel_educo extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_carousel_educo');
    }

    public function specialization() {
        global $CFG;
        if (empty($this->config)) {
            $this->config = new stdClass();
            $this->config->itemsnumber = '1'; // Default to one slide
            $this->config->item_title1 = "Diapositiva 1 Título";
            $this->config->item_text1 = "Texto de la Diapositiva 1";
            $this->config->item_image1 = $CFG->wwwroot . '/theme/educo/pix/slide_country.jpg'; // Default image
            $this->config->item_button1 = ""; // Optional button
            $this->config->item_link1 = ""; // Optional link
        }
    }

    public function get_content() {
        global $CFG, $PAGE;

        require_once($CFG->libdir . '/filelib.php');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();

        if (!empty($this->config) && is_object($this->config)) {
            $data = $this->config;
            $data->itemsnumber = is_numeric($data->itemsnumber) ? (int)$data->itemsnumber : 1;
        } else {
            $data = new stdClass();
            $data->itemsnumber = 1;
        }

        $text = '<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">';

        // Generate carousel indicators
        for ($i = 0; $i < $data->itemsnumber; $i++) {
            $active = ($i == 0) ? 'class="active"' : '';
            $text .= '<li data-target="#carouselExampleIndicators" data-slide-to="' . $i . '" ' . $active . '></li>';
        }

        $text .= '</ol><div class="carousel-inner">';

        if ($data->itemsnumber > 0) {
            $fs = get_file_storage();
            $context = context_block::instance($this->instance->id);

            for ($i = 1; $i <= $data->itemsnumber; $i++) {
                $active = ($i == 1) ? 'active' : '';
                $imageurl = $this->get_image_url($context->id, $i);
                if (empty($imageurl)) {
                    $imageurl = $data->{"item_image$i"} ?? $CFG->wwwroot . '/theme/educo/pix/slide_country.jpg';
                }

                $item_title = $data->{'item_title' . $i} ?? '';
                $item_text = $data->{'item_text' . $i} ?? '';
                $button_text = $data->{'item_button' . $i} ?? '';
                $button_link = $data->{'item_link' . $i} ?? '';

                $button_html = '';
                if (!empty($button_text)) {
                    $button_html = '<a href="' . $button_link . '" class="btn btn-primary" style="color: white;">' . $button_text . '</a>';
                }

                $text .= '
                <div class="carousel-item ' . $active . '">
                    <img src="' . $imageurl . '" class="d-block w-100" style="height: 550px; object-fit: cover;" alt="' . $item_title . '">
                    <div class="carousel-caption d-none d-md-block" style="background-color: rgba(0, 0, 0, 0.4);">
                        <h2 style="color: white;">' . $item_title . '</h2>
                        <p style="color: white; font-size: 20px;">' . $item_text . '</p>
                        ' . $button_html . '
                    </div>
                </div>';
            }
        }

        $text .= '</div></div>';

        $this->content->footer = '';
        $this->content->text = $text;

        return $this->content;
    }

    private function get_image_url($contextid, $itemid) {
        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'block_carousel_educo', 'content', $itemid, 'sortorder', false);

        foreach ($files as $file) {
            if ($file->is_valid_image()) {
                return moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
            }
        }
        return '';
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return false;
    }

    public function applicable_formats() {
        return array(
            'all' => true,
            'my' => false,
            'admin' => false,
            'course-view' => true,
            'course' => true,
        );
    }
}