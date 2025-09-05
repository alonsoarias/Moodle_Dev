<?php
// This file is part of Ranking block for Moodle - http://moodle.org/
/**
 * PromWebSoft.
 *
 * @package    theme_inteb
 * @copyright  Creado por Ing Pablo A Pico - @pabloapico exclusivamente para plataformas Moodle creadas y soportadas por PromWebSoft - Sistemas y Publicidad
 */
namespace theme_inteb\output;

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
        return $this->render_from_template('theme_remui/certificates', $main->export_for_template($this));
    }
}
