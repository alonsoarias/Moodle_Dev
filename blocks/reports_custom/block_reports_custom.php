<?php

class block_reports_custom extends block_base {
    public function init() {
        $this->title = get_string('reports_custom', 'block_reports_custom');
    }

    public function get_content() {
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;

        // Verifica si el usuario tiene la capacidad para ver el bloque
        if (!has_capability('block/reports_custom:view', context_block::instance($this->instance->id))) {
            return;
        }

        // Enlaces a los informes
        $urlcertificates = new moodle_url('/blocks/reports_custom/reports/certificates.php');
        $urlprogress = new moodle_url('/blocks/reports_custom/reports/progress.php');
        $this->content->text = html_writer::link($urlcertificates, get_string('certificates_report', 'block_reports_custom')) . '<br>';
        $this->content->text .= html_writer::link($urlprogress, get_string('progress_report', 'block_reports_custom'));

        return $this->content;
    }

    public function instance_allow_multiple() {
        return true;
    }

    public function has_config() {
        return true;
    }
}