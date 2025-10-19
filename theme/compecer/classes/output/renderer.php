<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
/**
 * IngeWeb.
 *
 * @package    theme_compecer
 * @copyright  Creado por Ing Pablo A Pico - @pabloapico exclusivamente para plataformas Moodle creadas y soportadas por IngeWeb - Soluciones para triunfar en Internet
 */
namespace theme_compecer\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Theme renderer
 *
 */
class renderer extends plugin_renderer_base {
    /**
     * Return the main content for the view page.
     *
     * @param \renderable $main The main renderable
     *
     * @return string HTML string
     *
     * @throws \moodle_exception
     */
    public function render_certificates(\renderable $main) {
        return $this->render_from_template('theme_moove/certificates', $main->export_for_template($this));
    }
}
