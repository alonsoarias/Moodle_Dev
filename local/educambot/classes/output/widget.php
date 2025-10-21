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
 * Widget renderable for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\output;

use renderable;
use templatable;
use renderer_base;
use moodle_url;

/**
 * Provides data for the chatbot widget mustache template.
 */
class widget implements renderable, templatable {
    /** @var array Suggested questions */
    protected array $suggestions;

    /** @var string|null Current page identifier */
    protected ?string $pageidentifier;

    /**
     * Constructor.
     *
     * @param array $suggestions Suggested questions with text and id.
     * @param string|null $pageidentifier Current page path.
     */
    public function __construct(array $suggestions, ?string $pageidentifier) {
        $this->suggestions = $suggestions;
        $this->pageidentifier = $pageidentifier;
    }

    /**
     * Export data for mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        global $USER;

        $sessionkey = sesskey();

        return [
            'widgettitle' => get_string('widgettitle', 'local_educambot'),
            'widgetintro' => get_string('widgetintro', 'local_educambot'),
            'chatheader' => get_string('chatheader', 'local_educambot'),
            'placeholder' => get_string('startplaceholder', 'local_educambot'),
            'suggestions' => array_values($this->suggestions),
            'hasuggestions' => !empty($this->suggestions),
            'sessionkey' => $sessionkey,
            'serviceurl' => (new moodle_url('/local/educambot/service.php'))->out(false),
            'userid' => (int)$USER->id,
            'pageidentifier' => $this->pageidentifier ?? '',
            'strings' => [
                'loading' => get_string('loading', 'local_educambot'),
                'noanswer' => get_string('noanswer', 'local_educambot'),
                'suggestedquestions' => get_string('suggestedquestions', 'local_educambot'),
                'confidence' => get_string('confidence', 'local_educambot'),
            ],
        ];
    }
}
