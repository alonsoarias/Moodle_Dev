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
 * Chat completion class for standard chat models.
 *
 * Provides completions using the OpenAI Chat Completions API endpoint.
 * Supports GPT-3.5, GPT-4, GPT-4.1, and newer chat models.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat\completion;

use mod_intebchat\completion;
defined('MOODLE_INTERNAL') || die;

/**
 * Chat completion implementation for OpenAI Chat API.
 *
 * Handles the construction of chat prompts with system messages,
 * conversation history, and user messages for the Chat Completions endpoint.
 */
class chat extends \mod_intebchat\completion {

    /**
     * Constructor for chat completion.
     *
     * @param string $model The model identifier to use (e.g., 'gpt-4.1')
     * @param string $message The user's message to process
     * @param array $history The conversation history as array of messages
     * @param array $instance_settings Instance-specific settings array
     * @param string|null $thread_id Optional thread ID (unused, for API compatibility)
     */
    public function __construct(string $model, string $message, array $history, array $instance_settings, ?string $thread_id = null) {
        parent::__construct($model, $message, $history, $instance_settings);
    }

    /**
     * Create a completion using the Chat API.
     *
     * Constructs the full message array including system prompts, source of truth,
     * conversation history, and the current user message, then sends to OpenAI.
     *
     * @param \context $context The Moodle context for text formatting
     * @return array Response array with 'message', 'id', 'usage', and optionally 'error'
     */
    public function create_completion(\context $context): array {
        if ($this->sourceoftruth) {
            $this->sourceoftruth = format_string($this->sourceoftruth, true, ['context' => $context]);
            $this->prompt .= get_string('sourceoftruthreinforcement', 'mod_intebchat');
        }
        $this->prompt .= "\n\n";

        $history_json = $this->format_history();
        array_unshift($history_json, ["role" => "system", "content" => $this->prompt]);
        if ($this->sourceoftruth) {
            array_unshift($history_json, ["role" => "system", "content" => $this->sourceoftruth]);
        }

        array_push($history_json, ["role" => "user", "content" => $this->message]);

        $response_data = $this->make_api_call($history_json);
        return $response_data;
    }

    /**
     * Format the conversation history into OpenAI message format.
     *
     * Converts the internal history array into the role-based format
     * expected by the OpenAI Chat API.
     *
     * @return array Array of message objects with 'role' and 'content' keys
     */
    protected function format_history(): array {
        $history = [];
        foreach ($this->history as $index => $message) {
            $role = $index % 2 === 0 ? 'user' : 'assistant';
            $history[] = ['role' => $role, 'content' => $message['message']];
        }
        return $history;
    }

    /**
     * Make the API call to OpenAI Chat Completions endpoint.
     *
     * Sends the formatted messages to the API and processes the response.
     * Includes comprehensive error handling and validation.
     *
     * @param array $history The complete message array to send
     * @return array Response with 'message', 'id', 'usage', or 'error' keys
     */
    private function make_api_call(array $history): array {
        $curlbody = [
            "model" => $this->model,
            "messages" => $history,
            "temperature" => (float) $this->temperature,
            "max_tokens" => (int) $this->maxlength,
            "top_p" => (float) $this->topp,
            "frequency_penalty" => (float) $this->frequency,
            "presence_penalty" => (float) $this->presence,
            "stop" => $this->username . ":"
        ];

        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json'
            ),
            'CURLOPT_TIMEOUT' => 120,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ));

        // Use the standard chat completions endpoint
        $rawresponse = $curl->post("https://api.openai.com/v1/chat/completions", json_encode($curlbody));

        // Validate we got a response
        if ($rawresponse === false || $rawresponse === '') {
            $curlerror = $curl->get_errno() ? $curl->error : 'Empty response from API';
            debugging('OpenAI API curl error: ' . $curlerror, DEBUG_DEVELOPER);
            return [
                'error' => ['message' => 'API connection error: ' . $curlerror],
                'id' => 'error',
                'message' => null
            ];
        }

        // Validate JSON parsing
        $response = json_decode($rawresponse);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('OpenAI API invalid JSON: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return [
                'error' => ['message' => 'Invalid API response format'],
                'id' => 'error',
                'message' => null
            ];
        }

        // Validate response is an object
        if (!is_object($response)) {
            debugging('OpenAI API response is not an object', DEBUG_DEVELOPER);
            return [
                'error' => ['message' => 'Unexpected API response type'],
                'id' => 'error',
                'message' => null
            ];
        }

        $result = [];

        // Check for API errors
        if (property_exists($response, 'error')) {
            $errormsg = is_object($response->error) && property_exists($response->error, 'message')
                ? $response->error->message
                : 'Unknown API error';
            $result['error'] = ['message' => 'ERROR: ' . $errormsg];
            $result['id'] = 'error';
            $result['message'] = null;
            return $result;
        }

        // Validate required response structure
        if (!property_exists($response, 'choices') || !is_array($response->choices) || empty($response->choices)) {
            debugging('OpenAI API response missing choices array', DEBUG_DEVELOPER);
            return [
                'error' => ['message' => 'Invalid API response: missing choices'],
                'id' => 'error',
                'message' => null
            ];
        }

        // Validate first choice structure
        $firstChoice = $response->choices[0];
        if (!is_object($firstChoice) ||
            !property_exists($firstChoice, 'message') ||
            !is_object($firstChoice->message) ||
            !property_exists($firstChoice->message, 'content')) {
            debugging('OpenAI API response has invalid choice structure', DEBUG_DEVELOPER);
            return [
                'error' => ['message' => 'Invalid API response structure'],
                'id' => 'error',
                'message' => null
            ];
        }

        $result['id'] = property_exists($response, 'id') ? $response->id : 'unknown';
        $result['message'] = $firstChoice->message->content;

        // Include token usage information if available
        if (property_exists($response, 'usage') && is_object($response->usage)) {
            $result['usage'] = [
                'prompt_tokens' => (int)($response->usage->prompt_tokens ?? 0),
                'completion_tokens' => (int)($response->usage->completion_tokens ?? 0),
                'total_tokens' => (int)($response->usage->total_tokens ?? 0)
            ];
        }

        return $result;
    }
}