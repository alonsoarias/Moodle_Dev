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
 * Class providing completions for assistant API
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @copyright  Based on work by 2023 Bryce Yoder <me@bryceyoder.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/

namespace mod_intebchat\completion;

use mod_intebchat\completion;
defined('MOODLE_INTERNAL') || die;

class assistant extends \mod_intebchat\completion {

    private $thread_id;
    private $run_usage = null; // Store usage data from run

    public function __construct($model, $message, $history, $instance_settings, $thread_id = null) {
        parent::__construct($model, $message, $history, $instance_settings);

        // If thread_id is NULL or empty, create a new thread
        if (empty($thread_id)) {
            $thread_id = $this->create_thread();
        }
        $this->thread_id = $thread_id;
    }

    /**
     * Get the current thread ID
     * @return string The thread ID
     */
    public function get_thread_id() {
        return $this->thread_id;
    }

    /**
     * Given everything we know after constructing the parent, create a completion
     * @return array The API response including token usage and thread_id
     */
    public function create_completion($context) {
        $this->add_message_to_thread();
        $result = $this->run();
        
        // Always add thread_id to result
        $result['thread_id'] = $this->thread_id;
        
        // Add usage data if available
        if ($this->run_usage) {
            $result['usage'] = $this->run_usage;
        }
        
        return $result;
    }

    /**
     * Create a new thread in OpenAI
     * @return string The thread ID
     */
    private function create_thread() {
        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2'
            ),
        ));

        $response = $curl->post("https://api.openai.com/v1/threads");
        $response = json_decode($response);

        if (isset($response->error)) {
            throw new \Exception('Error creating thread: ' . $response->error->message);
        }

        if (!isset($response->id)) {
            throw new \Exception('Thread creation failed: No thread ID returned');
        }

        return $response->id;
    }

    /**
     * Add a message to the current thread
     * @return string The message ID
     */
    private function add_message_to_thread() {
        if (empty($this->message)) {
            return null;
        }

        $curlbody = [
            "role" => "user",
            "content" => $this->message
        ];

        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2'
            ),
        ));

        $response = $curl->post(
            "https://api.openai.com/v1/threads/" . $this->thread_id ."/messages", 
            json_encode($curlbody)
        );
        $response = json_decode($response);

        if (isset($response->error)) {
            throw new \Exception('Error adding message to thread: ' . $response->error->message);
        }

        return isset($response->id) ? $response->id : null;
    }

    /**
     * Make the actual API call to OpenAI and get run details including token usage
     * @return array The response from OpenAI
     */
    private function run() {
        if (empty($this->assistant)) {
            throw new \Exception('No assistant ID configured');
        }

        $curlbody = [
            "assistant_id" => $this->assistant,
        ];
        
        if ($this->instructions) {
            $curlbody["instructions"] = $this->instructions;
        }

        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2'
            ),
        ));

        $response = $curl->post(
            "https://api.openai.com/v1/threads/" . $this->thread_id . "/runs", 
            json_encode($curlbody)
        );
        $response = json_decode($response);

        if (isset($response->error)) {
            throw new \Exception($response->error->message);
        }

        if (!isset($response->id)) {
            throw new \Exception('Run creation failed: No run ID returned');
        }

        $run_id = $response->id;
        $run_completed = false;
        $iters = 0;
        
        while (!$run_completed) {
            $iters++;
            if ($iters >= 60) {
                return [
                    "id" => 0,
                    "message" => get_string('openaitimedout', 'mod_intebchat'),
                    "thread_id" => $this->thread_id
                ];
            }
            
            $run_status = $this->check_run_status($run_id);
            $run_completed = $run_status['completed'];
            
            if ($run_status['usage']) {
                $this->run_usage = $run_status['usage'];
            }
            
            if (!$run_completed) {
                sleep(1);
            }
        }

        // Get the messages after run completion
        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2'
            ),
        ));
        
        $response = $curl->get("https://api.openai.com/v1/threads/" . $this->thread_id . '/messages');
        $response = json_decode($response);

        if (isset($response->error)) {
            throw new \Exception('Error retrieving messages: ' . $response->error->message);
        }

        if (!isset($response->data) || empty($response->data)) {
            throw new \Exception('No messages returned from assistant');
        }

        $latest_message = $response->data[0];
        
        if (!isset($latest_message->content[0]->text->value)) {
            throw new \Exception('Invalid message format returned from assistant');
        }

        return [
            "id" => $latest_message->id,
            "message" => $latest_message->content[0]->text->value,
            "thread_id" => $this->thread_id
        ];
    }

    /**
     * Check run status and extract usage data
     * @param string $run_id The run ID to check
     * @return array Status and usage information
     */
    private function check_run_status($run_id) {
        $curl = new \curl();
        $curl->setopt(array(
            'CURLOPT_HTTPHEADER' => array(
                'Authorization: Bearer ' . $this->apikey,
                'Content-Type: application/json',
                'OpenAI-Beta: assistants=v2'
            ),
        ));

        $response = $curl->get("https://api.openai.com/v1/threads/" . $this->thread_id . "/runs/" . $run_id);
        $response = json_decode($response, true);
        
        $completed = false;
        $usage = null;
        
        if (isset($response['status'])) {
            // Run is completed when status is 'completed' or if there's an error
            $completed = ($response['status'] === 'completed' || 
                         $response['status'] === 'failed' || 
                         $response['status'] === 'cancelled' ||
                         $response['status'] === 'expired' ||
                         isset($response['error']));
            
            // Handle failed runs
            if ($response['status'] === 'failed' || isset($response['error'])) {
                $error_msg = isset($response['error']) ? $response['error']['message'] : 'Run failed';
                throw new \Exception('Assistant run failed: ' . $error_msg);
            }
            
            // Extract usage data if available
            if (isset($response['usage'])) {
                $usage = $response['usage'];
            }
        }
        
        return [
            'completed' => $completed,
            'usage' => $usage
        ];
    }
}