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
 * Scheduled task to clean up old conversations.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to clean up old conversations based on retention settings.
 */
class cleanup_conversations extends \core\task\scheduled_task {

    /**
     * Get the name of the task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanupconversations', 'mod_intebchat');
    }

    /**
     * Execute the scheduled task.
     *
     * This task deletes conversations that have been inactive for longer than
     * the configured retention period.
     */
    public function execute() {
        global $DB;

        $config = get_config('mod_intebchat');

        // Check if retention is enabled
        if (empty($config->enableretention)) {
            mtrace('Conversation retention is disabled. Skipping cleanup.');
            return;
        }

        $retentiondays = !empty($config->retentiondays) ? (int)$config->retentiondays : 30;
        $cutofftime = time() - ($retentiondays * DAYSECS);

        mtrace("Starting conversation cleanup. Retention period: {$retentiondays} days.");
        mtrace("Deleting conversations inactive since: " . userdate($cutofftime));

        // Find conversations to delete
        // A conversation is inactive if its last message (timemodified) is older than the cutoff
        $sql = "SELECT c.id, c.instanceid
                FROM {intebchat_conversations} c
                WHERE c.timemodified < :cutofftime";

        $params = ['cutofftime' => $cutofftime];
        $conversations = $DB->get_records_sql($sql, $params);

        if (empty($conversations)) {
            mtrace(get_string('noconversationstoclean', 'mod_intebchat'));
            return;
        }

        $count = 0;
        $fs = get_file_storage();

        foreach ($conversations as $conversation) {
            // Get the context for this conversation's instance
            $cm = get_coursemodule_from_instance('intebchat', $conversation->instanceid, 0, false, IGNORE_MISSING);

            // Delete associated log entries
            $DB->delete_records('intebchat_log', ['conversationid' => $conversation->id]);

            // Delete associated audio files if context exists
            if ($cm) {
                $context = \context_module::instance($cm->id);
                $fs->delete_area_files($context->id, 'mod_intebchat', 'useraudio', $conversation->id);
            }

            // Delete the conversation
            $DB->delete_records('intebchat_conversations', ['id' => $conversation->id]);
            $count++;
        }

        mtrace(get_string('conversationsdeleted', 'mod_intebchat', $count));
    }
}
