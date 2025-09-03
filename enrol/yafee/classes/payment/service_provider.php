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
 * Payment subsystem callback implementation for enrol_yafee.
 *
 * @package    enrol_yafee
 * @category   payment
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @copyright based on work by 2019 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_yafee\payment;

use core_payment\helper;

/**
 * Payment subsystem callback implementation for enrol_yafee.
 *
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @copyright based on work by 2019 Shamim Rezaie <shamim@moodle.com>
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
            $cost = (float) get_config('enrol_yafee', 'cost');
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
                $instance->customint6 && !$DB->record_exists('enrol_yafee', ['courseid' => $instance->courseid,
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
            if ($instance->customchar1 == 'month' && $instance->customint7 > 0 && !$freetrial) {
                if ($instance->customint5) {
                    $delta = ($t2['year'] - $t1['year']) * 12 + $t2['mon'] - $t1['mon'] + 1;
                    $cost  = $delta * $cost;
                }
            } else if ($instance->customchar1 == 'year' && $instance->customint7 > 0 && !$freetrial) {
                if ($instance->customint5) {
                    $delta = $t2['year'] - $t1['year'] + 1;
                    $cost  = $delta * $cost;
                }
            }
        }

        if (isset($data->timeend) || isset($data->timestart)) {
            if ($instance->customint5 && $instance->enrolperiod && $data->timeend < time() && $data->timestart) {
                $price = $cost / $instance->enrolperiod;
                $delta = ceil(((time() - $data->timestart) / $instance->enrolperiod) + 0) * $instance->enrolperiod +
                    $data->timestart - $data->timeend;
                $cost  = $delta * $price;
            }
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

        $instance = $DB->get_record('enrol', ['enrol' => 'yafee', 'id' => $instanceid], '*', MUST_EXIST);

        $cost = self::get_uninterrupted_cost($instance);

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

        $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'yafee', 'id' => $instanceid], MUST_EXIST);

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

        $instance = $DB->get_record('enrol', ['enrol' => 'yafee', 'id' => $instanceid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('yafee');

        $timestart = time();
        $timeend   = $timestart;

        $surcharge = 0;
        if ($payment = $DB->get_record('payments', ['id' => $paymentid])) {
            $surcharge = helper::get_gateway_surcharge($payment->gateway);
        }

        // Get time data.
        if ($userdata = $DB->get_record('user_enrolments', ['userid' => $userid, 'enrolid' => $instance->id])) {
            // Check trial.
            if ($userdata->timestart) {
                $timestart = $userdata->timestart;
            }
            // Always append if not expired.
            if ($userdata->timeend > time()) {
                $timeend = $userdata->timeend;
            }
        }

        // Check peroids.
        if (
            !$DB->record_exists('enrol_yafee', ['courseid' => $instance->courseid, 'userid' => $userid]) &&
            $instance->customint6
        ) {
            // Add trial period.
            $timestart = 0;
            $timeend  += $instance->customint6;
        } else if ($instance->enrolperiod && $instance->customint5) {
            // Uninterrupted period.
            if (isset($userdata->timestart)) {
                // Check trial.
                if ($userdata->timestart && $userdata->timeend < time()) {
                    $periods = ceil((time() - $userdata->timestart) / $instance->enrolperiod);
                    // Payless mode.
                    if ($payment->amount > 0) {
                        $fee = round($payment->amount / (1 + $surcharge / 100), 2);
                        $unpayed = ceil((time() - $userdata->timeend) / $instance->enrolperiod) -
                            $fee / $instance->cost;
                    } else {
                        $unpayed = 0;
                    }
                    $timeend = $userdata->timestart + ($periods - $unpayed) * $instance->enrolperiod;
                } else {
                    $timeend += $instance->enrolperiod;
                }
            } else {
                $timeend += $instance->enrolperiod;
            }
        } else if ($instance->enrolperiod) {
            // Standard period.
            $timeend += $instance->enrolperiod;
        } else if ($instance->customchar1 == 'month' && $instance->customint7 > 0) {
            if (isset($userdata->timeend)) {
                $timeend = $userdata->timeend;
            }
            $t1 = getdate($timeend);
            $t2 = getdate(time());
            if ($instance->customint5) {
                if ($timeend < time()) {
                    $delta = ($t2['year'] - $t1['year']) * 12 + $t2['mon'] - $t1['mon'] + 1;
                    $timeend = strtotime('+' . $delta . 'month', $timeend);
                } else {
                    $timeend = strtotime('+' . $instance->customint7 . 'month', $timeend);
                }
            } else {
                $timeend = strtotime('+' . $instance->customint7 . 'month', $timeend);
            }
        } else if ($instance->customchar1 == 'year' && $instance->customint7 > 0) {
            if (isset($userdata->timeend)) {
                $timeend = $userdata->timeend;
            }
            $t1 = getdate($timeend);
            $t2 = getdate(time());
            if ($instance->customint5) {
                if ($timeend < time()) {
                    $delta = ($t2['year'] - $t1['year']) + 1;
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
        $DB->insert_record('enrol_yafee', $data);

        // Add user to group.
        $ext = $DB->get_records(
            'enrol_yafee_ext',
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
        $DB->delete_records('enrol_yafee_ext', ['userid' => $userid, 'courseid' => $instance->courseid]);

        return true;
    }
}
