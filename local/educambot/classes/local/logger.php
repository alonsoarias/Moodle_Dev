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
 * Conversation logger for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local;

use stdClass;
use function clean_param;
use function clean_text;
use function purify_html;

/**
 * Handles persistence of chatbot interactions.
 */
class logger {
    /** @var \moodle_database */
    protected $db;

    /**
     * Constructor.
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * Records a conversation entry.
     *
     * @param string $sessionid
     * @param string $question
     * @param string|null $response
     * @param int|null $ruleid
     * @param float $confidence
     * @param int|null $userid
     * @param string|null $page
     */
    public function log(string $sessionid, string $question, ?string $response, ?int $ruleid, float $confidence, ?int $userid, ?string $page): void {
        if (!get_config('local_educambot', 'loggingenabled')) {
            return;
        }
        $cleanquestion = clean_param($question, PARAM_RAW_TRIMMED);
        $cleanquestion = clean_text($cleanquestion, FORMAT_PLAIN, ['trusted' => false]);
        $cleanpage = $page !== null ? clean_param($page, PARAM_NOTAGS) : null;
        $cleanresponse = $response !== null ? purify_html($response) : null;

        $record = new stdClass();
        $record->sessionid = $sessionid;
        $record->question = $cleanquestion;
        $record->response = $cleanresponse;
        $record->ruleid = $ruleid;
        $record->confidence = $confidence;
        $record->userid = $userid;
        $record->page = $cleanpage;
        $record->timecreated = time();
        $this->db->insert_record('local_educambot_log', $record);
    }

    /**
     * Registers an unanswered question.
     *
     * @param string $question
     * @param int|null $userid
     * @param string|null $page
     */
    public function record_unanswered(string $question, ?int $userid, ?string $page): void {
        $question = trim($question);
        if ($question === '') {
            return;
        }

        $cleanquestion = clean_param($question, PARAM_RAW_TRIMMED);
        $cleanquestion = clean_text($cleanquestion, FORMAT_PLAIN, ['trusted' => false]);
        $params = [
            'question' => $cleanquestion,
            'recent' => time() - DAYSECS,
        ];
        $conditions = 'question = :question AND timecreated >= :recent';
        if ($userid !== null) {
            $conditions .= ' AND userid = :userid';
            $params['userid'] = $userid;
        }
        if ($this->db->record_exists_select('local_educambot_unanswered', $conditions, $params)) {
            return;
        }

        $cleanpage = $page !== null ? clean_param($page, PARAM_NOTAGS) : null;

        $record = new stdClass();
        $record->question = $cleanquestion;
        $record->userid = $userid;
        $record->page = $cleanpage;
        $record->timecreated = time();
        $this->db->insert_record('local_educambot_unanswered', $record);
    }
}
