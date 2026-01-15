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
 * Base completion object class
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2022 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace mod_intebchat;
defined('MOODLE_INTERNAL') || die;

/**
 * Base completion class for handling AI model completions.
 *
 * This abstract-like class provides the foundation for all completion types
 * (chat, assistant, etc.) and handles common settings and configuration.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class completion {

    /** @var string The API key for OpenAI */
    protected string $apikey;

    /** @var string The user's message to process */
    protected string $message;

    /** @var array The conversation history */
    protected array $history;

    /** @var string The name to use for the assistant in prompts */
    protected string $assistantname;

    /** @var string The name to use for the user in prompts */
    protected string $username;

    /** @var string The system prompt for the model */
    protected string $prompt;

    /** @var string|null Additional factual context for the model */
    protected ?string $sourceoftruth = null;

    /** @var string The model identifier (e.g., 'gpt-4.1') */
    protected string $model;

    /** @var float Temperature setting (0-2) for response randomness */
    protected float $temperature;

    /** @var int Maximum tokens for the response */
    protected int $maxlength;

    /** @var float Top-p (nucleus) sampling parameter */
    protected float $topp;

    /** @var float Frequency penalty (-2 to 2) */
    protected float $frequency;

    /** @var float Presence penalty (-2 to 2) */
    protected float $presence;

    /** @var string|null The OpenAI Assistant ID (for assistant API type) */
    protected ?string $assistant = null;

    /** @var string|null Additional instructions for the assistant */
    protected ?string $instructions = null;

    /** @var string The voice to use for TTS (alloy, ash, coral, etc.) */
    protected string $voice;

    /**
     * Initialize all the class properties that we'll need regardless of model.
     *
     * @param string $model The name of the model we're using
     * @param string $message The most recent message sent by the user
     * @param array $history An array of objects containing the history of the conversation
     * @param array $instance_settings An object containing the instance-level settings if applicable
     */
    public function __construct(string $model, string $message, array $history, array $instance_settings) {
        global $USER;
        
        // Set default values
        $this->model = $model;
        $this->apikey = get_config('mod_intebchat', 'apikey');

        // We fetch defaults for both chat and assistant APIs, even though only one can be active at a time
        // In the past, multiple different completion classes shared API types, so this might happen again
        // Any settings that don't apply to the current API type are just ignored

        $this->prompt = $this->get_setting('prompt', get_string('defaultprompt', 'mod_intebchat'));
        $this->assistantname = $this->get_setting('assistantname', get_string('defaultassistantname', 'mod_intebchat'));
        
        // Use the current user's firstname instead of a username setting
        $this->username = $USER->firstname ?: get_string('defaultusername', 'mod_intebchat');

        $this->temperature = $this->get_setting('temperature', 0.5);
        $this->maxlength = $this->get_setting('maxlength', 500);
        $this->topp = $this->get_setting('topp', 1);
        $this->frequency = $this->get_setting('frequency', 1);
        $this->presence = $this->get_setting('presence', 1);

        $this->assistant = $this->get_setting('assistant');
        $this->instructions = $this->get_setting('instructions');
        $this->voice = $this->get_setting('voice', 'alloy');

        // Then override with instance settings if applicable
        if (get_config('mod_intebchat', 'allowinstancesettings') === "1") {
            foreach ($instance_settings as $name => $value) {
                if ($value && $name !== 'username') { // Skip username as we always use firstname
                    $this->$name = $value;
                }
            }
        }

        $this->message = $message;
        $this->history = $history;

        $this->build_source_of_truth($instance_settings['sourceoftruth']);
    }

    /**
     * Get the voice to use for audio responses.
     *
     * @return string The voice identifier (e.g., 'alloy', 'nova', 'shimmer')
     */
    public function get_voice(): string {
        return $this->voice;
    }

    /**
     * Get the current model being used.
     *
     * @return string The model identifier
     */
    public function get_model(): string {
        return $this->model;
    }

    /**
     * Get the assistant name.
     *
     * @return string The assistant name
     */
    public function get_assistant_name(): string {
        return $this->assistantname;
    }

    /**
     * Attempt to get the saved value for a setting.
     *
     * If the setting isn't set, returns the provided default value.
     *
     * @param string $settingname The name of the setting to fetch
     * @param mixed $default_value The default value to return if the setting isn't set
     * @return mixed The saved or default value
     */
    protected function get_setting(string $settingname, mixed $default_value = null): mixed {
        $setting = get_config('mod_intebchat', $settingname);
        if (!$setting && $setting != 0) {
            $setting = $default_value;
        }
        return $setting;
    }

    /**
     * Build the source of truth by combining global and instance-level sources.
     *
     * Prepares the source of truth for inclusion in the prompt by adding
     * the appropriate preamble and combining multiple sources.
     *
     * @param string|null $localsourceoftruth The instance-level source of truth
     */
    private function build_source_of_truth(?string $localsourceoftruth): void {
        $sourceoftruth = get_config('mod_intebchat', 'sourceoftruth');
    
        if ($sourceoftruth || $localsourceoftruth) {
            $sourceoftruth = 
                get_string('sourceoftruthpreamble', 'mod_intebchat')
                . $sourceoftruth . "\n\n"
                . $localsourceoftruth . "\n\n";
            }
        $this->sourceoftruth = $sourceoftruth;
    }
}