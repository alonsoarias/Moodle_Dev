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
 * Class providing completions for chat models (3.5 and up)
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace mod_intebchat\completion;

use mod_intebchat\completion;
defined('MOODLE_INTERNAL') || die;

class chat extends \mod_intebchat\completion {

    public function __construct($model, $message, $history, $instance_settings, $thread_id = null) {
        parent::__construct($model, $message, $history, $instance_settings);
    }

    /**
     * Given everything we know after constructing the parent, create a completion by constructing the prompt and making the api call
     * @return array The API response including message and token usage
     */
    public function create_completion($context) {
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
     * Format the history JSON into a string that we can pass in the prompt
     * @return array The array representing the chat history
     */
    protected function format_history() {
        $history = [];
        foreach ($this->history as $index => $message) {
            $role = $index % 2 === 0 ? 'user' : 'assistant';
            array_push($history, ["role" => $role, "content" => $message["message"]]);
        }
        return $history;
    }

    /**
     * Make the actual API call to OpenAI
     * @return array The response from OpenAI including token usage
     */
    private function make_api_call($history) {
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
        ));

        // Use the standard chat completions endpoint
        $response = $curl->post("https://api.openai.com/v1/chat/completions", json_encode($curlbody));
        $response = json_decode($response);

        $result = [];
        
        if (property_exists($response, 'error')) {
            $result['error'] = ['message' => 'ERROR: ' . $response->error->message];
            $result['id'] = 'error';
            $result['message'] = null;
        } else {
            $result['id'] = property_exists($response, 'id') ? $response->id : 'unknown';
            $result['message'] = $response->choices[0]->message->content;
            
            // Include token usage information if available
            // This is the standard format from OpenAI API
            if (property_exists($response, 'usage')) {
                $result['usage'] = [
                    'prompt_tokens' => $response->usage->prompt_tokens ?? 0,
                    'completion_tokens' => $response->usage->completion_tokens ?? 0,
                    'total_tokens' => $response->usage->total_tokens ?? 0
                ];
            }
        }

        return $result;
    }
}