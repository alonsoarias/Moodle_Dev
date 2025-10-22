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

use local_educambot\local\context_provider;
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

    /** @var int|null */
    protected ?int $userid;

    /** @var int|null */
    protected ?int $courseid;

    /**
     * Constructor.
     *
     * @param array $suggestions Suggested questions with text and id.
     * @param string|null $pageidentifier Current page path.
     * @param int|null $userid
     * @param int|null $courseid
     */
    public function __construct(array $suggestions, ?string $pageidentifier, ?int $userid, ?int $courseid) {
        $this->suggestions = $suggestions;
        $this->pageidentifier = $pageidentifier;
        $this->userid = $userid;
        $this->courseid = $courseid;
    }

    /**
     * Export data for mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {
        static $configcache = null;
        if ($configcache === null) {
            $configcache = (array)get_config('local_educambot');
        }
        $config = $configcache;
        $contextprovider = new context_provider($this->userid, $this->courseid, $this->pageidentifier);

        $botname = $contextprovider->get_bot_name($config);
        $introtemplate = trim($config['introtemplate'] ?? '');
        if ($introtemplate === '') {
            $introtemplate = get_string('widgetintro', 'local_educambot');
        }
        $intro = $contextprovider->personalise_html($introtemplate, $config);
        $tagline = trim($config['personalitytagline'] ?? '');
        if ($tagline !== '') {
            $tagline = $contextprovider->personalise_html($tagline, $config);
        }

        $widgetlabel = trim($config['widgetlabel'] ?? '');
        if ($widgetlabel === '') {
            $widgetlabel = get_string('widgettitle', 'local_educambot');
        }

        $primary = $config['primarycolor'] ?? '#0f6fc5';
        $accent = $config['accentcolor'] ?? '#e7f0fb';
        $background = $config['backgroundcolor'] ?? '#f7f9fc';
        $textcolor = $config['textcolor'] ?? '#1f2937';

        $initialmessage = $contextprovider->build_initial_greeting($config);
        $configpayload = json_encode([
            'initialMessage' => $initialmessage,
            'botName' => $botname,
            'tagline' => $tagline,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($configpayload === false) {
            $configpayload = '{}';
        }

        $sessionkey = sesskey();

        return [
            'widgettitle' => format_string($widgetlabel),
            'widgetintro' => $intro,
            'chatheader' => $botname,
            'placeholder' => get_string('startplaceholder', 'local_educambot'),
            'suggestions' => array_values($this->suggestions),
            'hasuggestions' => !empty($this->suggestions),
            'sessionkey' => $sessionkey,
            'serviceurl' => (new moodle_url('/local/educambot/service.php'))->out(false),
            'userid' => $this->userid,
            'pageidentifier' => $this->pageidentifier ?? '',
            'strings' => [
                'loading' => get_string('loading', 'local_educambot'),
                'noanswer' => get_string('noanswer', 'local_educambot'),
                'suggestedquestions' => get_string('suggestedquestions', 'local_educambot'),
                'confidence' => get_string('confidence', 'local_educambot'),
            ],
            'theme' => [
                'primary' => $primary,
                'accent' => $accent,
                'background' => $background,
                'text' => $textcolor,
            ],
            'tagline' => $tagline,
            'widgetconfig' => $configpayload,
        ];
    }
}
