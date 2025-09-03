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
 * Fee enrolment plugin.
 *
 * This plugin allows you to set up paid courses.
 *
 * @package    enrol_nexuspay
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Fee enrolment plugin implementation.
 *
 * @copyright 2024 Alonso Arias <soporte@nexuslabs.com.co>
 * @author    Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_nexuspay_plugin extends enrol_plugin {
    /**
     * Returns the list of currencies that the payment subsystem supports and therefore we can work with.
     *
     * @return array[currencycode => currencyname]
     */
    public function get_possible_currencies(): array {
        $codes = \core_payment\helper::get_supported_currencies();

        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        uasort($currencies, function ($a, $b) {
            return strcmp($a, $b);
        });

        return $currencies;
    }

    /**
     * Returns localised name of enrol instance
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB;

        if (empty($instance->name)) {
            if (!empty($instance->roleid) && $role = $DB->get_record('role', ['id' => $instance->roleid])) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            } else {
                $role = '';
            }
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_' . $enrol) . $role;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        $found = false;
        foreach ($instances as $instance) {
            if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
                continue;
            }
            if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
                continue;
            }
            $found = true;
            break;
        }
        if ($found) {
            return [new pix_icon('icon', get_string('pluginname', 'enrol_nexuspay'), 'enrol_nexuspay')];
        }
        return [];
    }

    /**
     *
     * @return boolean
     */
    public function roles_protected() {
        // Users with role assign cap may tweak the roles later.
        return false;
    }

    /**
     *
     * @param stdClass $instance
     * @return boolean
     */
    public function allow_enrol(stdClass $instance) {
        // Users with enrol cap may enrol other users manually - requires enrol/nexuspay:enrol.
        return true;
    }

    /**
     *
     * @param stdClass $instance
     * @return boolean
     */
    public function allow_unenrol(stdClass $instance) {
        // Users with unenrol cap may unenrol other users manually - requires enrol/nexuspay:unenrol.
        return true;
    }

    /**
     *
     * @param stdClass $instance
     * @return boolean
     */
    public function allow_manage(stdClass $instance) {
        // Users with manage cap may tweak period and status - requires enrol/nexuspay:manage.
        return true;
    }

    /**
     *
     * @param stdClass $instance
     * @return boolean
     */
    public function show_enrolme_link(stdClass $instance) {
        return ($instance->status == ENROL_INSTANCE_ENABLED);
    }

    /**
     * Returns link to manual enrol UI if exists.
     * Does the access control tests automatically.
     *
     * @param stdClass $instance
     * @return moodle_url
     */
    public function get_manual_enrol_link($instance) {
        $name = $this->get_name();
        if ($instance->enrol !== $name) {
            throw new coding_exception('invalid enrol instance!');
        }

        if (!enrol_is_enabled($name)) {
            return null;
        }

        $context = context_course::instance($instance->courseid, MUST_EXIST);

        if (!has_capability('enrol/nexuspay:enrol', $context)) {
            // Note: manage capability not used here because it is used for editing
            // of existing enrolments which is not possible here.
            return null;
        }

        return new moodle_url('/enrol/nexuspay/manage.php', ['enrolid' => $instance->id, 'id' => $instance->courseid]);
    }

    /**
     * Returns edit icons for the page with list of instances.
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        $context = context_course::instance($instance->courseid);

        $icons = [];
        if (has_capability('enrol/nexuspay:enrol', $context) || has_capability('enrol/nexuspay:unenrol', $context)) {
            $managelink = new moodle_url("/enrol/nexuspay/manage.php", ['enrolid' => $instance->id]);
            $icons[] = $OUTPUT->action_icon($managelink, new pix_icon(
                't/enrolusers',
                get_string('enrolusers', 'enrol_manual'),
                'core',
                ['class' => 'iconsmall']
            ));
        }
        $parenticons = parent::get_action_icons($instance);
        $icons = array_merge($icons, $parenticons);

        return $icons;
    }

    /**
     * Returns a button to manually enrol users through the manual enrolment plugin.
     *
     * By default the first manual enrolment plugin instance available in the course is used.
     * If no manual enrolment instances exist within the course then false is returned.
     *
     * @param course_enrolment_manager $manager
     * @return enrol_user_button
     */
    public function get_manual_enrol_button(course_enrolment_manager $manager) {
        global $CFG, $PAGE;
        require_once($CFG->dirroot . '/cohort/lib.php');

        static $called = false;

        $instance = null;
        foreach ($manager->get_enrolment_instances() as $tempinstance) {
            if ($tempinstance->enrol == 'nexuspay') {
                if ($instance === null) {
                    $instance = $tempinstance;
                }
            }
        }
        if (empty($instance)) {
            return false;
        }

        $link = $this->get_manual_enrol_link($instance);
        if (!$link) {
            return false;
        }
        if (empty($instance->name)) {
            $instance->name = get_string('managemanualenrolements', 'enrol_nexuspay');
        }
        $button = new enrol_user_button($link, $instance->name, 'get');
        $button->class .= ' enrol_nexuspay_plugin';
        $button->type = single_button::BUTTON_PRIMARY;

        $context = context_course::instance($instance->courseid);
        $arguments = ['contextid' => $context->id];

        return $button;
    }

    /**
     * Returns true if the user can add a new instance in this course.
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (empty(\core_payment\helper::get_supported_currencies())) {
            return false;
        }

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/nexuspay:config', $context)) {
            return false;
        }

        // Multiple instances supported - different cost for different roles.
        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }

        $fields = [];
        $fields['status']          = $this->get_config('status');
        $fields['roleid']          = $this->get_config('roleid');
        $fields['enrolperiod']     = $this->get_config('enrolperiod');
        $fields['expirynotify']    = $expirynotify;
        $fields['notifyall']       = $notifyall;
        $fields['expirythreshold'] = $this->get_config('expirythreshold');
        $fields['customint1']      = 0; // Payment account.
        $fields['customint2']      = $this->get_config('groupkey');
        $fields['customint3']      = $this->get_config('newenrols');
        $fields['customint4']      = $this->get_config('forcepayment');
        $fields['customint5']      = $this->get_config('uninterrupted');
        $fields['customint6']      = $this->get_config('freetrial');
        $fields['customint7']      = 0; // Number periods of customchar1.
        $fields['customint8']      = $this->get_config('showduration');
        $fields['customchar1']     = 'minute'; // Types of periods.
        $fields['customtext1']     = ''; // Default group.

        return $fields;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param ?array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, ?array $fields = null) {
        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
        }
        // In the form we are representing 2 db columns with one field.
        if (!empty($fields) && !empty($fields['expirynotify'])) {
            if ($fields['expirynotify'] == 2) {
                $fields['expirynotify'] = 1;
                $fields['notifyall'] = 1;
            } else {
                $fields['notifyall'] = 0;
            }
        }
        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return boolean
     */
    public function update_instance($instance, $data) {
        if ($data) {
            $data->cost = unformat_float($data->cost);
        }
        // In the form we are representing 2 db columns with one field.
        if ($data->expirynotify == 2) {
            $data->expirynotify = 1;
            $data->notifyall = 1;
        } else {
            $data->notifyall = 0;
        }
        // Add previous value of newenrols if disabled.
        if (!isset($data->customint3)) {
            $data->customint3 = $instance->customint3;
        }

        return parent::update_instance($instance, $data);
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $OUTPUT, $SESSION, $USER, $DB;

        ob_start();

        if ($DB->record_exists('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            return ob_get_clean();
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return ob_get_clean();
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return ob_get_clean();
        }

        $course = $DB->get_record('course', ['id' => $instance->courseid]);
        $context = context_course::instance($course->id);

        $shortname = format_string($course->shortname, true, ['context' => $context]);
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them.
        if ($users = get_users_by_capability(
            $context,
            'moodle/course:update',
            'u.*',
            'u.id ASC',
            '',
            '',
            '',
            '',
            false,
            true
        )) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ((float) $instance->cost <= 0) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // No cost, other enrolment methods (instances) should be used.
            echo '<p>' . get_string('nocost', 'enrol_nexuspay') . '</p>';
        } else {
            // Sanitise some fields before building the PayU form.
            $coursefullname  = format_string($course->fullname, true, ['context' => $context]);
            $courseshortname = $shortname;
            $userfullname    = fullname($USER);
            $userfirstname   = $USER->firstname;
            $userlastname    = $USER->lastname;
            $useraddress     = $USER->address;
            $usercity        = $USER->city;
            $instancename    = $this->get_instance_name($instance);
            if (($instance->customint3 == 1) && !$DB->record_exists('enrol_nexuspay', ['courseid' => $instance->courseid, 'userid' => $USER->id])) {
                $cost = 0;
                echo '<div class="mdl-align"><p>' . get_string('newenrolswithoutpayment', 'enrol_nexuspay') . '</p></div>';
            } else {
                $cost = format_float($cost, 2, false);
                include($CFG->dirroot . '/enrol/nexuspay/enrol.html');
            }
        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Creates enrol form.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_force(stdClass $instance) {
        global $CFG, $OUTPUT, $SESSION, $USER, $DB;

        ob_start();

        $course = $DB->get_record('course', ['id' => $instance->courseid]);
        $context = context_course::instance($course->id);

        $shortname = format_string($course->shortname, true, ['context' => $context]);
        $strloginto = get_string("loginto", "", $shortname);
        $strcourses = get_string("courses");

        // Pass $view=true to filter hidden caps if the user cannot see them.
        if ($users = get_users_by_capability(
            $context,
            'moodle/course:update',
            'u.*',
            'u.id ASC',
            '',
            '',
            '',
            '',
            false,
            true
        )) {
            $users = sort_by_roleassignment_authority($users, $context);
            $teacher = array_shift($users);
        } else {
            $teacher = false;
        }

        if ((float) $instance->cost <= 0) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        if (abs($cost) < 0.01) { // No cost.
            return;
        } else {
            $coursefullname  = format_string($course->fullname, true, ['context' => $context]);
            $courseshortname = $shortname;
            $userfullname    = fullname($USER);
            $userfirstname   = $USER->firstname;
            $userlastname    = $USER->lastname;
            $useraddress     = $USER->address;
            $usercity        = $USER->city;
            $instancename    = $this->get_instance_name($instance);

            $cost = format_float($cost, 2, false);
            include($CFG->dirroot . '/enrol/nexuspay/enrol.html');
        }

        return $OUTPUT->box(ob_get_clean());
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;
        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid'   => $data->courseid,
                'enrol'      => $this->get_name(),
                'status'     => $data->status,
                'roleid'     => $data->roleid,
                'cost'       => $data->cost,
                'currency'   => $data->currency,
            ];
        }
        if ($merge and $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array) $data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restored role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = [];
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;
        if ($this->allow_unenrol($instance) && has_capability("enrol/nexuspay:unenrol", $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/delete', ''), get_string('unenrol', 'enrol'), $url, ['class' => 'unenrollink', 'rel' => $ue->id]);
        }
        if ($this->allow_manage($instance) && has_capability("enrol/nexuspay:manage", $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $actions[] = new user_enrolment_action(new pix_icon('t/edit', ''), get_string('edit'), $url, ['class' => 'editenrollink', 'rel' => $ue->id]);
        }
        return $actions;
    }

    /**
     * Adds an option enrol this user to a given course.
     *
     * @param \stdClass $course
     * @param \stdClass $user
     * @return bool
     */
    public function can_request_enrol(\stdClass $course, \stdClass $user): bool {
        return true;
    }

    /**
     * Process user request to enrol in a course.
     *
     * @param \stdClass $course Course to enrol the user in.
     * @param \stdClass $user The user to be enrolled.
     * @return bool Whether the enrolment was successful.
     */
    public function process_enrol_request(\stdClass $course, \stdClass $user): bool {
        global $CFG;
        $courseurl = new \moodle_url('/course/view.php', ['id' => $course->id]);
        redirect($courseurl);
    }

    /**
     * Set up cron for the plugin (if any).
     *
     * @return void
     */
    public function cron() {
        $trace = new text_progress_trace();
        $this->sync($trace);
        $this->send_expiry_notifications($trace);
    }

    /**
     * Returns the user who is responsible for nexuspay enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/nexuspay:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid && $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => $this->get_name()], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/nexuspay:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = false;
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Notify users about enrolments expiring soon.
     *
     * @param progress_trace $trace
     * @return void
     */
    protected function send_expiry_notifications($trace) {
        global $DB, $CFG;

        // Unfortunately this may take a long time, it should not be interrupted,
        // otherwise users get duplicate notification.
        core_php_time_limit::raise();

        $trace->output('sending enrolment expiration notifications...');

        $now = time();
        $alreadynotified = [];
        $sql = "SELECT ue.*, e.expirynotify, e.notifyall, e.expirythreshold, e.courseid, c.fullname
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'nexuspay' AND e.status = :enabled AND e.expirynotify > 0)
                  JOIN {course} c ON (c.id = e.courseid)
                  JOIN {user} u ON (u.id = ue.userid AND u.deleted = 0 AND u.suspended = 0)
                 WHERE ue.status = :active AND ue.timeend > 0 AND ue.timeend > :now1 AND ue.timeend < (e.expirythreshold + :now2)
              ORDER BY ue.enrolid ASC, u.lastname ASC, u.firstname ASC, u.id ASC";

        $params = ['enabled' => ENROL_INSTANCE_ENABLED, 'active' => ENROL_USER_ACTIVE, 'now1' => $now, 'now2' => $now];
        $rs = $DB->get_recordset_sql($sql, $params);

        $lastenrolid = 0;
        $users = [];

        foreach ($rs as $ue) {
            if ($lastenrolid && $lastenrolid != $ue->enrolid) {
                $this->notify_expiry_enroller($lastenrolid, $users, $trace);
                $users = [];
            }
            $lastenrolid = $ue->enrolid;

            $enroller = $this->get_enroller($ue->enrolid);
            $context = context_course::instance($ue->courseid);

            $user = $DB->get_record('user', ['id' => $ue->userid]);

            $users[] = ['fullname' => fullname($user, has_capability('moodle/site:viewfullnames', $context, $enroller)), 'timeend' => $ue->timeend];

            if (!$ue->notifyall) {
                continue;
            }

            if ($ue->timeend - $ue->expirythreshold + 86400 < $now) {
                // Notify enrolled users only once at the start of the threshold.
                $alreadynotified[$ue->id] = $ue->id;
                continue;
            }

            $this->notify_expiry_enrolled($user, $ue, $trace);
        }

        if ($lastenrolid && $users) {
            $this->notify_expiry_enroller($lastenrolid, $users, $trace);
        }

        $rs->close();
        $trace->output('...notification processing finished.');
        $trace->finished();
    }

    /**
     * Returns the user IDs of all users that are enrolled in the course with this enrolment method.
     *
     * @param int $courseid
     * @return array
     */
    public function get_enrolled_users($courseid) {
        global $DB;

        $sql = "SELECT ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'nexuspay')
                 WHERE e.courseid = :courseid";

        return $DB->get_fieldset_sql($sql, ['courseid' => $courseid]);
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param stdClass $course
     * @param stdClass $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        if ($inserted) {
            if (isset($data->enrol_nexuspay_status)) {
                $fields = ['status' => $data->enrol_nexuspay_status];
                if (isset($data->enrol_nexuspay_cost)) {
                    $fields['cost'] = unformat_float($data->enrol_nexuspay_cost);
                    $fields['currency'] = $data->enrol_nexuspay_currency;
                }
                if (isset($data->enrol_nexuspay_roleid)) {
                    $fields['roleid'] = $data->enrol_nexuspay_roleid;
                }
                if (isset($data->enrol_nexuspay_enrolperiod)) {
                    $fields['enrolperiod'] = $data->enrol_nexuspay_enrolperiod;
                }
                $this->add_instance($course, $fields);
            } else {
                if ($this->get_config('defaultenrol')) {
                    $this->add_default_instance($course);
                }
            }
        }
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {
        global $CFG;

        // Merge these two settings to one value for the single selection element.
        if ($instance->notifyall && $instance->expirynotify) {
            $instance->expirynotify = 2;
        }
        unset($instance->notifyall);

        $nameattribs = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_nexuspay'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        $accounts = \core_payment\helper::get_payment_accounts_menu($context);
        if ($accounts) {
            $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
            $mform->addElement('select', 'customint1', get_string('paymentaccount', 'payment'), $accounts);
        } else {
            $mform->addElement('static', 'customint1_text', get_string('paymentaccount', 'payment'), html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-danger'));
            $mform->addElement('hidden', 'customint1', '');
            $mform->setType('customint1', PARAM_INT);
        }
        $mform->addHelpButton('customint1', 'paymentaccount', 'enrol_nexuspay');

        $mform->addElement('text', 'cost', get_string('cost', 'enrol_nexuspay'), ['size' => 4]);
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));

        $currencies = $this->get_possible_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_nexuspay'), $currencies);
        $mform->setDefault('currency', $this->get_config('currency'));

        if ($roles = $this->get_roleid_options($instance, $context)) {
            $mform->addElement('select', 'roleid', get_string('defaultrole', 'role'), $roles);
            $mform->setDefault('roleid', $this->get_config('roleid'));
        }

        $options = ['optional' => true, 'defaultunit' => 86400];
        $mform->addElement('duration', 'enrolperiod', get_string('defaultenrolperiod', 'enrol_nexuspay'), $options);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'defaultenrolperiod', 'enrol_nexuspay');

        $groups = groups_get_all_groups($instance->courseid);
        $options = ['' => get_string('none')];
        foreach ($groups as $group) {
            $options[$group->id] = format_string($group->name, true, ['context' => $context]);
        }
        $mform->addElement('select', 'customtext1', get_string('defaultgroup', 'enrol_nexuspay'), $options);

        $options = $this->get_expirynotify_options();
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->setDefault('expirynotify', $this->get_config('expirynotify'));
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

        $options = ['optional' => false, 'defaultunit' => 86400];
        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
        $mform->setDefault('expirythreshold', $this->get_config('expirythreshold'));
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);

        $options = [
            0 => get_string('no'),
            1 => get_string('yes')
        ];
        $mform->addElement('select', 'customint2', get_string('groupkey', 'enrol_nexuspay'), $options);
        $mform->setDefault('customint2', $this->get_config('groupkey'));

        $options = [
            0 => get_string('no'),
            1 => get_string('yes')
        ];
        $mform->addElement('select', 'customint3', get_string('newenrols', 'enrol_nexuspay'), $options);
        $mform->setDefault('customint3', $this->get_config('newenrols'));
        $mform->addHelpButton('customint3', 'newenrols', 'enrol_nexuspay');

        $options = [
            0 => get_string('notrequired', 'enrol_nexuspay'),
            1 => get_string('enrolstartdate', 'enrol_nexuspay'),
            2 => get_string('enrolenddate', 'enrol_nexuspay'),
            3 => get_string('always')
        ];
        $mform->addElement(
            'select',
            'customint4',
            get_string('forcepayment', 'enrol_nexuspay'),
            $options
        );
        $mform->setType('customint4', PARAM_INT);
        $mform->addHelpButton('customint4', 'forcepayment', 'enrol_nexuspay');

        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_nexuspay'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_nexuspay');

        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_nexuspay'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_nexuspay');

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('enrol_nexuspay');
        $donate = get_string('donate', 'enrol_nexuspay', $plugininfo);
        $mform->addElement('html', $donate);

        if (enrol_accessing_via_instance($instance)) {
            $warningtext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningtext);
        }
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = [];

        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_nexuspay');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost) || $cost < 0.01 || round($cost, 2) != $cost) {
            $errors['cost'] = get_string('costerror', 'enrol_nexuspay');
        }

        if ($data['expirynotify'] > 0 && $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        $validstatus = array_keys($this->get_status_options());
        $validcurrency = array_keys($this->get_possible_currencies());
        $validexpirynotify = array_keys($this->get_expirynotify_options());
        $validroles = array_keys($this->get_roleid_options($instance, $context));
        $tovalidate = [
            'name' => PARAM_TEXT,
            'status' => $validstatus,
            'currency' => $validcurrency,
            'roleid' => $validroles,
            'expirynotify' => $validexpirynotify,
            'enrolstartdate' => PARAM_INT,
            'enrolenddate' => PARAM_INT,
            'customint1' => PARAM_INT,
            'customint2' => PARAM_INT,
            'customint3' => PARAM_INT,
            'customint4' => PARAM_INT,
            'customint5' => PARAM_INT,
            'customint6' => PARAM_INT,
            'customint7' => PARAM_INT,
            'customint8' => PARAM_INT,
            'customchar1' => PARAM_TEXT,
            'customtext1' => PARAM_TEXT
        ];
        if ($data['expirynotify'] != 0) {
            $tovalidate['expirythreshold'] = PARAM_INT;
        }

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        if (
            $data['status'] == ENROL_INSTANCE_ENABLED &&
            (!$data['customint1'] ||
                !array_key_exists($data['customint1'], \core_payment\helper::get_payment_accounts_menu($context)))
        ) {
            $errors['status'] = get_string('validationerror', 'enrol_nexuspay');
        }

        return $errors;
    }

    /**
     * Execute synchronisation.
     * @param progress_trace $trace
     * @return int exit code, 0 means ok
     */
    public function sync(progress_trace $trace) {
        $this->process_expirations($trace);
        return 0;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/nexuspay:config', $context);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/nexuspay:config', $context);
    }

    /**
     * Sets up navigation entries.
     *
     * @param navigation_node $instancesnode navigation node
     * @param stdClass $instance enrol record instance
     *
     * @throws coding_exception
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        global $USER, $DB;
        if ($instance->enrol !== 'nexuspay') {
            throw new coding_exception('Invalid enrol instance type!');
        }
        $context = context_course::instance($instance->courseid);

        if ($data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            if ($instance->expirynotify && $data->timeend && $data->timeend - time() < $instance->expirythreshold) {
                // Now manipulate upwards, bail as quickly as possible if not appropriate.
                $navigation = $instancesnode;
                while ($navigation->parent !== null) {
                    $navigation = $navigation->parent;
                }
                if (!$courseadminnode = $navigation->get("courseadmin")) {
                    return;
                }
                // Locate or add our own node if appropriate.
                if (!$canexuspaynode = $courseadminnode->get("canexuspay")) {
                    $nodeproperties = [
                        'text'          => get_string('menuname', 'enrol_nexuspay'),
                        'shorttext'     => get_string('menunameshort', 'enrol_nexuspay'),
                        'type'          => navigation_node::TYPE_SETTING,
                        'key'           => 'canexuspay'
                    ];
                    $canexuspaynode = new navigation_node($nodeproperties);
                    $courseadminnode->add_node($canexuspaynode, 'users');
                }
                // Add node.
                $canexuspaynode->add(
                    get_string('menuname', 'enrol_nexuspay'),
                    new moodle_url('/enrol/nexuspay/pay.php', ['courseid' => $instance->courseid, 'id' => $instance->id]),
                    navigation_node::TYPE_SETTING,
                    get_string('menuname', 'enrol_nexuspay'),
                    'nexuspay',
                    new pix_icon('icon', '', 'enrol_nexuspay')
                );
            }
        }
    }

    /**
     * Check if enrolment plugin is supported in csv course upload.
     *
     * @return bool
     */
    public function is_csv_upload_supported(): bool {
        return true;
    }
}