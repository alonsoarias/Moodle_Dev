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
 * Payment subsystem callback implementation for enrol_nexuspay.
 *
 * @package    enrol_nexuspay
 * @category   payment
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_nexuspay\payment;

use core_payment\helper;

/**
 * Payment subsystem callback implementation for enrol_nexuspay.
 *
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {
    /**
     * Calculate enrolment cost
     *
     * @param stdClass $instance The enrolment instance
     * @return float
     */
    public static function get_uninterrupted_cost($instance): float {
        global $DB, $USER;

        if ((float) $instance->cost <= 0) {
            $cost = (float) get_config('enrol_nexuspay', 'cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if ($data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            if ($data->status) {
                return $cost;
            }
        }

        $freetrial = false;
        if ($instance->customint6 || $instance->customint7) {
            // Check first time trial.
            if (
                $instance->customint6 && !$DB->record_exists('enrol_nexuspay', ['courseid' => $instance->courseid,
                    'userid' => $USER->id])
            ) {
                    $freetrial = true;
            }

            // Prepare month and year.
            $timeend = time();
            if (isset($data->timeend)) {
                $timeend = $data->timeend;
            }
            $t1 = getdate($timeend);
            $t2 = getdate(time());

            // Check month and year.
            $counter = 0;
            if ($t2['year'] > $t1['year']) {
                $ydiff = $t2['year'] - $t1['year'];
                if ($ydiff > 1) {
                    $counter += 12 * ($ydiff - 1);
                }
                if ($t2['mon'] >= $t1['mon']) {
                    $counter += 12 + $t2['mon'] - $t1['mon'];
                } else {
                    $counter += 12 - ($t1['mon'] - $t2['mon']);
                }
            } else {
                $counter += $t2['mon'] - $t1['mon'];
            }
            if ($counter > 0 && $freetrial && ($counter >= $instance->customint6)) {
                $counter = $counter - $instance->customint6;
                $freetrial = false;
            } else if ($counter > 0 && $freetrial) {
                $freetrial = false;
                $counter = 0;
            }
            // Check day.
            if ($t2['mday'] < $t1['mday'] && $counter > 0) {
                $counter -= 1;
            }
            $cost = $cost * $counter;
        }
        if ($freetrial) {
            $cost = 0;
        }

        return $cost;
    }

    /**
     * Callback function that returns the enrolment cost and the accountid
     * for the course that $instanceid enrolment instance belongs to.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $instanceid): \core_payment\local\entities\payable {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $instanceid], '*', MUST_EXIST);

        $zero = new \core_payment\local\entities\payable(0, $instance->currency, $instance->customint1);
        if ($instance->cost == 0 || $instance->currency == '') {
            return $zero;
        }

        $cost = self::get_uninterrupted_cost($instance);
        if ($cost == 0) {
            return $zero;
        }

        return new \core_payment\local\entities\payable($cost, $instance->currency, $instance->customint1);
    }

    /**
     * Callback function that returns the URL of the page the user should be redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrolment instance id
     * @return \moodle_url
     */
    public static function get_success_url(string $paymentarea, int $instanceid): \moodle_url {
        global $DB;

        $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'nexuspay', 'id' => $instanceid], MUST_EXIST);

        return new \moodle_url('/course/view.php', ['id' => $courseid]);
    }

    /**
     * Callback function that delivers what the user paid for to them.
     *
     * @param string $paymentarea
     * @param int $instanceid The enrolment instance id
     * @param int $paymentid payment id as inserted into the 'payments' table, if needed for reference
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $instanceid, int $paymentid, int $userid): bool {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $instanceid], '*', MUST_EXIST);

        $plugin = enrol_get_plugin('nexuspay');

        if ($instance->enrolperiod) {
            $timestart = time();
            $timeend   = $timestart + $instance->enrolperiod;
        } else if ($instance->customint7 && $instance->customchar1) {
            $timestart = time();
            $timeend   = $timestart;
            $delta = 0;
            if ($instance->customchar1 == 'month') {
                $timeend = strtotime('+' . $instance->customint7 . 'month', $timeend);
            } else if ($instance->customchar1 == 'minute') {
                $timeend = strtotime('+' . $instance->customint7 . 'minute', $timeend);
            } else if ($instance->customchar1 == 'hour') {
                $timeend = strtotime('+' . $instance->customint7 . 'hour', $timeend);
            } else if ($instance->customchar1 == 'day') {
                $timeend = strtotime('+' . $instance->customint7 . 'day', $timeend);
            } else if ($instance->customchar1 == 'week') {
                $timeend = strtotime('+' . $instance->customint7 . 'week', $timeend);
            } else if ($instance->customchar1 == 'year') {
                $data = $DB->get_record('user_enrolments', ['userid' => $userid, 'enrolid' => $instance->id]);
                $ctime = time();
                $t1 = getdate($ctime);
                if (isset($data->timeend)) {
                    $t2 = getdate($data->timeend);
                    $delta = 12 * ($t1['year'] - $t2['year']) + ($t1['mon'] - $t2['mon']);
                    if ($t1['mday'] < $t2['mday']) {
                        $delta -= 1;
                    }
                }
                if ($delta > 0) {
                    $timeend = strtotime('+' . $delta . 'year', $timeend);
                } else {
                    $timeend = strtotime('+' . $instance->customint7 . 'year', $timeend);
                }
            } else {
                $timeend = strtotime('+' . $instance->customint7 . 'year', $timeend);
            }
        } else {
            $timestart = 0;
            $timeend   = 0;
        }

        $plugin->enrol_user($instance, $userid, $instance->roleid, $timestart, $timeend);

        $data = new \stdClass();
        $data->paymentid = $paymentid;
        $data->courseid = $instance->courseid;
        $data->timecreated = time();
        $data->userid = $userid;
        $DB->insert_record('enrol_nexuspay', $data);

        // Add user to group.
        $ext = $DB->get_records(
            'enrol_nexuspay_ext',
            ['userid' => $userid, 'courseid' => $instance->courseid],
            'id DESC',
            'ingroupid',
            0,
            1,
        );
        $ext = reset($ext);
        if (isset($ext->ingroupid) && $ext->ingroupid) {
            global $CFG;
            require_once($CFG->dirroot . '/group/lib.php');
            groups_add_member($ext->ingroupid, $userid);
        } else if (isset($instance->customtext1) && (int)$instance->customtext1 > 0) {
            global $CFG;
            require_once($CFG->dirroot . '/group/lib.php');
            groups_add_member((int)$instance->customtext1, $userid);
        }
        $DB->delete_records('enrol_nexuspay_ext', ['userid' => $userid, 'courseid' => $instance->courseid]);

        return true;
    }
}