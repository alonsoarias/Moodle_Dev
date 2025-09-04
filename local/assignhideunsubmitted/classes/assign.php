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
 * Override of assign class to hide participants without submissions.
 *
 * @package   local_assignhideunsubmitted
 * @copyright 2024 Your Organization
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignhideunsubmitted;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/locallib.php');

/**
 * Extended assign class that filters out unsubmitted participants
 */
class assign extends \assign {
    
    /** @var array Cache for filtered participants */
    protected static $filtercache = [];
    
    /**
     * Override list_participants to filter unsubmitted students
     */
    public function list_participants($currentgroup, $idsonly, $tablesort = false) {
        global $USER, $DB, $CFG;
        
        // Check if filtering is enabled
        if (!get_config('local_assignhideunsubmitted', 'enabled')) {
            return parent::list_participants($currentgroup, $idsonly, $tablesort);
        }
        
        // Check role
        $roleid = (int)get_config('local_assignhideunsubmitted', 'hiderole');
        if (!$roleid || !$this->user_has_role($USER->id, $roleid)) {
            return parent::list_participants($currentgroup, $idsonly, $tablesort);
        }
        
        // Get all participants
        $allparticipants = parent::list_participants($currentgroup, false, $tablesort);
        
        if (empty($allparticipants)) {
            return $allparticipants;
        }
        
        // Filter by submission status
        $filtered = $this->filter_submitted_participants($allparticipants);
        
        // Convert to IDs only if requested
        if ($idsonly) {
            $ids = [];
            foreach ($filtered as $userid => $participant) {
                $ids[$userid] = (object)['id' => $userid];
            }
            return $ids;
        }
        
        return $filtered;
    }
    
    /**
     * Filter participants to only show those who have submitted
     */
    protected function filter_submitted_participants($participants) {
        global $DB;
        
        if (empty($participants)) {
            return [];
        }
        
        $participantids = array_keys($participants);
        list($insql, $params) = $DB->get_in_or_equal($participantids, SQL_PARAMS_NAMED);
        
        $params['assignid'] = $this->get_instance()->id;
        $params['submitted'] = ASSIGN_SUBMISSION_STATUS_SUBMITTED;
        
        // Query for submitted users
        $sql = "SELECT DISTINCT s.userid
                FROM {assign_submission} s
                WHERE s.assignment = :assignid
                  AND s.latest = 1
                  AND s.status = :submitted
                  AND s.userid $insql";
        
        $submittedusers = $DB->get_records_sql($sql, $params);
        
        $filtered = [];
        foreach ($submittedusers as $record) {
            if (isset($participants[$record->userid])) {
                $filtered[$record->userid] = $participants[$record->userid];
            }
        }
        
        return $filtered;
    }
    
    /**
     * Check if user has the configured role
     */
    protected function user_has_role($userid, $roleid) {
        $context = $this->get_context();
        $roles = get_user_roles($context, $userid, true);
        
        foreach ($roles as $role) {
            if ((int)$role->roleid === $roleid) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Override count_participants to use filtered list
     */
    public function count_participants($currentgroup) {
        $participants = $this->list_participants($currentgroup, true);
        return count($participants);
    }
}