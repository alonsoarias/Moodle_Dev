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
 * @copyright 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assignhideunsubmitted;

class assign extends \assign {
    /**
     * Load a list of users enrolled in the current course with the specified permission and group.
     * Participants without a submitted attempt are excluded for selected roles.
     *
     * @param int $currentgroup
     * @param bool $idsonly
     * @param bool $tablesort
     * @return array
     */
    public function list_participants($currentgroup, $idsonly, $tablesort = false) {
        global $USER;

        $roleid = (int)get_config('local_assignhideunsubmitted', 'hiderole');
        if ($roleid && $this->user_has_role($USER->id, $roleid)) {
            // Get full participant records from core implementation.
            $participants = parent::list_participants($currentgroup, false, $tablesort);
            $participants = $this->load_submission_info($participants);

            // Remove users that have not submitted anything.
            foreach ($participants as $userid => $participant) {
                if (empty($participant->submitted)) {
                    unset($participants[$userid]);
                }
            }

            if ($idsonly) {
                $ids = [];
                foreach ($participants as $userid => $unused) {
                    $ids[$userid] = (object)['id' => $userid];
                }
                return $ids;
            }

            return $participants;
        }

        // Fallback to core behaviour if filtering is not required.
        return parent::list_participants($currentgroup, $idsonly, $tablesort);
    }

    /**
     * Determine whether a user has the configured role in this context.
     *
     * @param int $userid
     * @param int $roleid
     * @return bool
     */
    protected function user_has_role(int $userid, int $roleid): bool {
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
     * Copy of core get_submission_info_for_participants() to attach submission data.
     *
     * @param array $participants
     * @return array
     */
    protected function load_submission_info(array $participants) {
        global $DB;

        if (empty($participants)) {
            return $participants;
        }

        list($insql, $params) = $DB->get_in_or_equal(array_keys($participants), SQL_PARAMS_NAMED);

        $assignid = $this->get_instance()->id;
        $params['assignmentid1'] = $assignid;
        $params['assignmentid2'] = $assignid;
        $params['assignmentid3'] = $assignid;

        $fields = 'SELECT u.id, s.status, s.timemodified AS stime, g.timemodified AS gtime, g.grade, uf.extensionduedate';
        $from = ' FROM {user} u
                         LEFT JOIN {assign_submission} s
                                ON u.id = s.userid
                               AND s.assignment = :assignmentid1
                               AND s.latest = 1
                         LEFT JOIN {assign_grades} g
                                ON u.id = g.userid
                               AND g.assignment = :assignmentid2
                               AND g.attemptnumber = s.attemptnumber
                         LEFT JOIN {assign_user_flags} uf
                                ON u.id = uf.userid
                               AND uf.assignment = :assignmentid3
            ';
        $where = ' WHERE u.id ' . $insql;

        if (!empty($this->get_instance()->blindmarking)) {
            $from .= 'LEFT JOIN {assign_user_mapping} um
                             ON u.id = um.userid
                            AND um.assignment = :assignmentid4 ';
            $params['assignmentid4'] = $assignid;
            $fields .= ', um.id as recordid ';
        }

        $sql = "$fields $from $where";

        $records = $DB->get_records_sql($sql, $params);

        if ($this->get_instance()->teamsubmission) {
            // Get all groups.
            $allgroups = groups_get_all_groups($this->get_course()->id,
                                               array_keys($participants),
                                               $this->get_instance()->teamsubmissiongroupingid,
                                               'DISTINCT g.id, g.name');
        }
        foreach ($participants as $userid => $participant) {
            $participants[$userid]->fullname = $this->fullname($participant);
            $participants[$userid]->submitted = false;
            $participants[$userid]->requiregrading = false;
            $participants[$userid]->grantedextension = false;
            $participants[$userid]->submissionstatus = '';
        }

        foreach ($records as $userid => $submissioninfo) {
            $submitted = false;
            $requiregrading = false;
            $grantedextension = false;
            $submissionstatus = !empty($submissioninfo->status) ? $submissioninfo->status : '';

            if (!empty($submissioninfo->stime) && $submissioninfo->status == ASSIGN_SUBMISSION_STATUS_SUBMITTED) {
                $submitted = true;
            }

            if ($submitted && ($submissioninfo->stime >= $submissioninfo->gtime ||
                    empty($submissioninfo->gtime) ||
                    $submissioninfo->grade === null)) {
                $requiregrading = true;
            }

            if (!empty($submissioninfo->extensionduedate)) {
                $grantedextension = true;
            }

            $participants[$userid]->submitted = $submitted;
            $participants[$userid]->requiregrading = $requiregrading;
            $participants[$userid]->grantedextension = $grantedextension;
            $participants[$userid]->submissionstatus = $submissionstatus;
            if ($this->get_instance()->teamsubmission) {
                $group = $this->get_submission_group($userid);
                if ($group) {
                    $participants[$userid]->groupid = $group->id;
                    $participants[$userid]->groupname = $group->name;
                }
            }
        }
        return $participants;
    }
}
