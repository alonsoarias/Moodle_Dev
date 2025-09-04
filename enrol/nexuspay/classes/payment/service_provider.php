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
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_nexuspay\payment;

use core_payment\helper;

/**
 * Payment subsystem callback implementation for enrol_nexuspay.
 *
 * @copyright  2025 NexusPay Development Team
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class service_provider implements \core_payment\local\callback\service_provider {

    /**
     * Calculate enrollment cost considering uninterrupted periods and trials.
     *
     * @param stdClass $instance The enrollment instance
     * @return float
     */
    public static function get_uninterrupted_cost($instance): float {
        global $DB, $USER;

        // Get base cost.
        if ((float) $instance->cost <= 0) {
            $cost = (float) get_config('enrol_nexuspay', 'cost');
        } else {
            $cost = (float) $instance->cost;
        }

        // Check if user has existing enrollment.
        $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id]);
        
        // If user is suspended, return full cost.
        if ($data && $data->status != ENROL_USER_ACTIVE) {
            return $cost;
        }

        $freetrial = false;
        
        // Check for free trial eligibility.
        if ($instance->customint6 || $instance->customint7) {
            // Check if first time enrollment (no previous payment record).
            if ($instance->customint6 && 
                !$DB->record_exists('enrol_nexuspay', ['courseid' => $instance->courseid, 'userid' => $USER->id])) {
                $freetrial = true;
            }

            // Handle monthly/yearly subscriptions.
            $timeend = time();
            if (isset($data->timeend)) {
                $timeend = $data->timeend;
            }
            $currentdate = getdate(time());
            $enddate = getdate($timeend);

            // Calculate cost for monthly subscriptions.
            if ($instance->customchar1 == 'month' && $instance->customint7 > 0 && !$freetrial) {
                if ($instance->customint5 && $data) { // Uninterrupted payment enabled.
                    // Calculate months elapsed.
                    $monthsdiff = ($currentdate['year'] - $enddate['year']) * 12 + 
                                  $currentdate['mon'] - $enddate['mon'] + 1;
                    $cost = $monthsdiff * $cost;
                }
            } else if ($instance->customchar1 == 'year' && $instance->customint7 > 0 && !$freetrial) {
                if ($instance->customint5 && $data) { // Uninterrupted payment enabled.
                    // Calculate years elapsed.
                    $yearsdiff = $currentdate['year'] - $enddate['year'] + 1;
                    $cost = $yearsdiff * $cost;
                }
            }
        }

        // Handle standard period uninterrupted payments.
        if (isset($data) && $instance->customint5 && $instance->enrolperiod > 0) {
            if ($data->timeend < time() && $data->timestart) {
                // Calculate cost for missed periods.
                $priceperperiod = $cost / $instance->enrolperiod;
                $elapsedtime = time() - $data->timestart;
                $periodsneeded = ceil($elapsedtime / $instance->enrolperiod);
                $periodscovered = ($data->timeend - $data->timestart) / $instance->enrolperiod;
                $missedperiods = $periodsneeded - $periodscovered;
                
                if ($missedperiods > 0) {
                    $cost = $missedperiods * $instance->enrolperiod * $priceperperiod;
                }
            }
        }

        return $cost;
    }

    /**
     * Callback function that returns the enrollment cost and the account id
     * for the course that $instanceid enrollment instance belongs to.
     *
     * @param string $paymentarea Payment area (should be 'fee')
     * @param int $instanceid The enrollment instance id
     * @return \core_payment\local\entities\payable
     */
    public static function get_payable(string $paymentarea, int $instanceid): \core_payment\local\entities\payable {
        global $DB;

        $instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $instanceid], '*', MUST_EXIST);
        
        $cost = self::get_uninterrupted_cost($instance);
        $currency = $instance->currency ?: 'COP'; // Default to Colombian Peso.
        $account = $instance->customint1;

        return new \core_payment\local\entities\payable($cost, $currency, $account);
    }

    /**
     * Callback function that returns the URL of the page the user should be 
     * redirected to in the case of a successful payment.
     *
     * @param string $paymentarea Payment area
     * @param int $instanceid The enrollment instance id
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
     * @param int $instanceid The enrollment instance id
     * @param int $paymentid payment id as inserted into the 'payments' table
     * @param int $userid The userid the order is going to deliver to
     * @return bool Whether successful or not
     */
    public static function deliver_order(string $paymentarea, int $instanceid, int $paymentid, int $userid): bool {
        global $DB, $CFG;
        
        require_once($CFG->dirroot . '/group/lib.php');

        $instance = $DB->get_record('enrol', ['enrol' => 'nexuspay', 'id' => $instanceid], '*', MUST_EXIST);
        $plugin = enrol_get_plugin('nexuspay');

        $timestart = time();
        $timeend = $timestart;

        // Calculate surcharge if applicable.
        $surcharge = 0;
        if ($payment = $DB->get_record('payments', ['id' => $paymentid])) {
            $surcharge = helper::get_gateway_surcharge($payment->gateway);
        }

        // Get existing enrollment data if any.
        $existingenrollment = $DB->get_record('user_enrolments', ['userid' => $userid, 'enrolid' => $instance->id]);
        
        if ($existingenrollment) {
            // Keep original start time if exists.
            if ($existingenrollment->timestart) {
                $timestart = $existingenrollment->timestart;
            }
            // Extend from current end time if not expired.
            if ($existingenrollment->timeend > time()) {
                $timeend = $existingenrollment->timeend;
            }
        }

        // Check for free trial eligibility.
        $isfirstenrollment = !$DB->record_exists('enrol_nexuspay', ['courseid' => $instance->courseid, 'userid' => $userid]);
        
        if ($isfirstenrollment && $instance->customint6) {
            // Apply trial period.
            $timestart = 0;
            $timeend += $instance->customint6;
        } else if ($instance->enrolperiod && $instance->customint5) {
            // Uninterrupted period handling.
            if (isset($existingenrollment->timestart) && $existingenrollment->timestart) {
                if ($existingenrollment->timeend < time()) {
                    // Calculate periods needed to cover gap.
                    $elapsedperiods = ceil((time() - $existingenrollment->timestart) / $instance->enrolperiod);
                    
                    // Calculate paid periods based on payment amount.
                    $paidperiods = 0;
                    if ($payment->amount > 0 && $instance->cost > 0) {
                        $netamount = round($payment->amount / (1 + $surcharge / 100), 2);
                        $paidperiods = $netamount / $instance->cost;
                    }
                    
                    // Calculate unpaid periods.
                    $unpaidperiods = ceil((time() - $existingenrollment->timeend) / $instance->enrolperiod) - $paidperiods;
                    $unpaidperiods = max(0, $unpaidperiods);
                    
                    // Set new end time.
                    $timeend = $existingenrollment->timestart + ($elapsedperiods - $unpaidperiods) * $instance->enrolperiod;
                } else {
                    // Simply extend by enrollment period.
                    $timeend += $instance->enrolperiod;
                }
            } else {
                // New enrollment with standard period.
                $timeend += $instance->enrolperiod;
            }
        } else if ($instance->enrolperiod) {
            // Standard period without uninterrupted payment.
            $timeend += $instance->enrolperiod;
        } else if ($instance->customchar1 == 'month' && $instance->customint7 > 0) {
            // Monthly subscription.
            if (isset($existingenrollment->timeend)) {
                $timeend = $existingenrollment->timeend;
            }
            
            if ($instance->customint5 && $timeend < time()) {
                // Uninterrupted monthly payment.
                $currentdate = getdate(time());
                $enddate = getdate($timeend);
                $monthsdiff = ($currentdate['year'] - $enddate['year']) * 12 + 
                              $currentdate['mon'] - $enddate['mon'] + 1;
                $timeend = strtotime('+' . $monthsdiff . ' month', $timeend);
            } else {
                // Regular monthly extension.
                $timeend = strtotime('+' . $instance->customint7 . ' month', $timeend);
            }
        } else if ($instance->customchar1 == 'year' && $instance->customint7 > 0) {
            // Yearly subscription.
            if (isset($existingenrollment->timeend)) {
                $timeend = $existingenrollment->timeend;
            }
            
            if ($instance->customint5 && $timeend < time()) {
                // Uninterrupted yearly payment.
                $currentdate = getdate(time());
                $enddate = getdate($timeend);
                $yearsdiff = $currentdate['year'] - $enddate['year'] + 1;
                $timeend = strtotime('+' . $yearsdiff . ' year', $timeend);
            } else {
                // Regular yearly extension.
                $timeend = strtotime('+' . $instance->customint7 . ' year', $timeend);
            }
        } else {
            // Unlimited enrollment.
            $timestart = 0;
            $timeend = 0;
        }

        // Enroll or update enrollment.
        $plugin->enrol_user($instance, $userid, $instance->roleid, $timestart, $timeend);

        // Record payment transaction.
        $transaction = new \stdClass();
        $transaction->paymentid = $paymentid;
        $transaction->courseid = $instance->courseid;
        $transaction->userid = $userid;
        $transaction->timecreated = time();
        $DB->insert_record('enrol_nexuspay', $transaction);

        // Handle group assignment if configured.
        if (!empty($instance->customtext1) && (int)$instance->customtext1 > 0) {
            groups_add_member((int)$instance->customtext1, $userid);
        }
        
        // Check for pending group assignment.
        $pendinggroup = $DB->get_record('enrol_nexuspay_groups', [
            'userid' => $userid,
            'courseid' => $instance->courseid,
            'instanceid' => $instance->id
        ], 'groupid', IGNORE_MULTIPLE);
        
        if ($pendinggroup && $pendinggroup->groupid) {
            groups_add_member($pendinggroup->groupid, $userid);
            // Clean up pending group assignment.
            $DB->delete_records('enrol_nexuspay_groups', [
                'userid' => $userid,
                'courseid' => $instance->courseid,
                'instanceid' => $instance->id
            ]);
        }

        return true;
    }
}