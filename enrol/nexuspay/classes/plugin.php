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
 * NexusPay enrolment plugin implementation.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * NexusPay enrolment plugin implementation class.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_nexuspay_plugin extends enrol_plugin {

    /**
     * Returns the list of currencies that the payment subsystem supports.
     * For Colombia, we prioritize COP and USD.
     *
     * @return array[currencycode => currencyname]
     */
    public function get_possible_currencies(): array {
        $codes = \core_payment\helper::get_supported_currencies();
        
        $currencies = [];
        $prioritized = ['COP', 'USD']; // Prioritize Colombian Peso and US Dollar
        
        // Add prioritized currencies first if available
        foreach ($prioritized as $code) {
            if (in_array($code, $codes)) {
                $currencies[$code] = new lang_string($code, 'core_currencies');
            }
        }
        
        // Add remaining currencies
        foreach ($codes as $code) {
            if (!isset($currencies[$code])) {
                $currencies[$code] = new lang_string($code, 'core_currencies');
            }
        }

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
            return get_string('pluginname', 'enrol_nexuspay') . $role;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * Returns optional enrolment information icons.
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
     * Does this plugin allow manual enrolments?
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_enrol(stdClass $instance) {
        return true;
    }

    /**
     * Does this plugin allow manual unenrolments?
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Does this plugin allow manual changes in user enrolments?
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_manage(stdClass $instance) {
        return true;
    }

    /**
     * Does this plugin support groups?
     *
     * @param stdClass $instance course enrol instance
     * @return bool
     */
    public function allow_group_member_remove(stdClass $instance) {
        return true;
    }

    /**
     * Returns true if the plugin can be disabled.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/nexuspay:config', $context);
    }

    /**
     * Returns true if the plugin can be hidden/shown.
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/nexuspay:config', $context);
    }

    /**
     * Returns true if the current user can add a new instance in this course.
     *
     * @param int $courseid
     * @return bool
     */
    public function can_add_instance($courseid) {
        $context = context_course::instance($courseid, MUST_EXIST);

        if (empty(\core_payment\helper::get_supported_currencies())) {
            return false;
        }

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/nexuspay:config', $context)) {
            return false;
        }

        return true;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return bool
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Add new instance of enrol plugin.
     *
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        if ($fields && !empty($fields['cost'])) {
            $fields['cost'] = unformat_float($fields['cost']);
        }
        // Handle expiry notification fields.
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
     *
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     * @return bool
     */
    public function update_instance($instance, $data) {
        if ($data) {
            $data->cost = unformat_float($data->cost);
        }
        // Handle expiry notification fields.
        if ($data->expirynotify == 2) {
            $data->expirynotify = 1;
            $data->notifyall = 1;
        } else {
            $data->notifyall = 0;
        }
        // Keep previous/default value if expiry notification disabled.
        if (!$data->expirynotify) {
            $data->expirythreshold = $instance->expirythreshold;
        }
        // Handle trial settings.
        if (empty($data->customint6)) {
            $data->customint6 = 0;
        }
        // Calculate standard periods.
        if (!empty($data->customchar1) && !empty($data->customint7)) {
            switch ($data->customchar1) {
                case 'minute':
                    $data->enrolperiod = 60 * $data->customint7;
                    break;
                case 'hour':
                    $data->enrolperiod = 3600 * $data->customint7;
                    break;
                case 'day':
                    $data->enrolperiod = 86400 * $data->customint7;
                    break;
                case 'week':
                    $data->enrolperiod = 86400 * 7 * $data->customint7;
                    break;
                case 'month':
                case 'year':
                    // Keep special handling for month/year.
                    $data->enrolperiod = 0;
                    break;
                default:
                    $data->enrolperiod = 0;
            }
        }
        return parent::update_instance($instance, $data);
    }

    /**
     * Returns defaults for new instances.
     *
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

        return [
            'status'          => $this->get_config('status'),
            'roleid'          => $this->get_config('roleid'),
            'enrolperiod'     => $this->get_config('enrolperiod'),
            'expirynotify'    => $expirynotify,
            'notifyall'       => $notifyall,
            'expirythreshold' => $this->get_config('expirythreshold'),
            'customint1'      => 0, // Payment account.
            'customint2'      => $this->get_config('groupkey'),
            'customint3'      => $this->get_config('newenrols'),
            'customint4'      => $this->get_config('forcepayment'),
            'customint5'      => $this->get_config('uninterrupted'),
            'customint6'      => $this->get_config('freetrial'),
            'customint7'      => 0, // Number of periods.
            'customint8'      => $this->get_config('showduration'),
            'customchar1'     => 'day', // Default to days.
            'customtext1'     => '', // Default group.
            'currency'        => 'COP', // Default to Colombian Peso.
        ];
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $CFG, $USER, $OUTPUT, $PAGE, $DB;
        
        require_once("$CFG->dirroot/enrol/nexuspay/locallib.php");
        
        $enrolstatus = $this->can_self_enrol($instance);
        
        if (true === $enrolstatus) {
            // User can self-enrol, show payment options.
            return $this->show_payment_info($instance);
        } else if ($enrolstatus == get_string('paymentrequired', 'enrol_nexuspay')) {
            // Show payment form.
            return $this->show_payment_info($instance);
        } else {
            // Cannot enrol.
            return $OUTPUT->box($enrolstatus);
        }
    }

    /**
     * Checks if user can self enrol.
     *
     * @param stdClass $instance enrolment instance
     * @param bool $checkuserenrolment if true will check if user enrolment is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false
     */
    public function can_self_enrol(stdClass $instance, $checkuserenrolment = true) {
        global $DB, $OUTPUT, $USER;

        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            return get_string('canntenrol', 'enrol_self');
        }

        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            return get_string('canntenrolearly', 'enrol_self', userdate($instance->enrolstartdate));
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            return get_string('canntenrollate', 'enrol_self', userdate($instance->enrolenddate));
        }

        if (!$instance->customint3) {
            // New enrols not allowed.
            return false;
        }

        if ($checkuserenrolment) {
            if (isguestuser()) {
                return get_string('paymentrequired', 'enrol_nexuspay');
            }
            // Check if user is already enrolled.
            if ($DB->record_exists('user_enrolments', array('userid' => $USER->id, 'enrolid' => $instance->id))) {
                return false;
            }
        }

        return get_string('paymentrequired', 'enrol_nexuspay');
    }

    /**
     * Returns the user who is responsible for self enrolments in given instance.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($userid = $DB->get_field('enrol', 'userid', array('id' => $instanceid, 'enrol' => 'nexuspay'))) {
            return $DB->get_record('user', array('id' => $userid));
        } else {
            return get_admin();
        }
    }

    /**
     * Gets an array of the user enrolment actions.
     *
     * @param course_enrolment_manager $manager
     * @param stdClass $ue A user enrolment object
     * @return array An array of user_enrolment_actions
     */
    public function get_user_enrolment_actions(course_enrolment_manager $manager, $ue) {
        $actions = array();
        $context = $manager->get_context();
        $instance = $ue->enrolmentinstance;
        $params = $manager->get_moodlepage()->url->params();
        $params['ue'] = $ue->id;

        if ($this->allow_manage($instance) && has_capability('enrol/nexuspay:manage', $context)) {
            $url = new moodle_url('/enrol/editenrolment.php', $params);
            $action = new user_enrolment_action(
                new pix_icon('t/edit', ''),
                get_string('edit'),
                $url,
                array('class' => 'editenrollink', 'rel' => $ue->id)
            );
            $actions[] = $action;
        }

        if ($this->allow_unenrol($instance) && has_capability('enrol/nexuspay:unenrol', $context)) {
            $url = new moodle_url('/enrol/unenroluser.php', $params);
            $action = new user_enrolment_action(
                new pix_icon('t/delete', ''),
                get_string('unenrol', 'enrol'),
                $url,
                array('class' => 'unenrollink', 'rel' => $ue->id)
            );
            $actions[] = $action;
        }

        return $actions;
    }

    /**
     * Returns edit icons for the page with list of instances.
     *
     * @param stdClass $instance
     * @return array
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        $context = context_course::instance($instance->courseid);

        $icons = [];
        if (has_capability('enrol/nexuspay:enrol', $context) || has_capability('enrol/nexuspay:unenrol', $context)) {
            $managelink = new moodle_url("/enrol/nexuspay/manage.php", ['enrolid' => $instance->id]);
            $icons[] = $OUTPUT->action_icon(
                $managelink,
                new pix_icon('t/enrolusers', get_string('enrolusers', 'enrol_manual'), 'core', ['class' => 'iconsmall'])
            );
        }
        $parenticons = parent::get_action_icons($instance);
        $icons = array_merge($icons, $parenticons);

        return $icons;
    }

    /**
     * Execute synchronisation.
     *
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
     * Check if enrolment plugin is supported in csv course upload.
     *
     * @return bool
     */
    public function is_csv_upload_supported(): bool {
        return true;
    }

    /**
     * Generates payment information to display on enrol/info page.
     *
     * @param stdClass $instance
     * @param bool $force
     * @return string HTML
     */
    private function show_payment_info(stdClass $instance, $force = false) {
        global $USER, $OUTPUT, $DB, $PAGE;

        ob_start();

        if (isset($instance->customint3) && !$instance->customint3 || $instance->customint3 == 2 && !$force) {
            return ob_get_clean();
        }

        // Check user enrollment status.
        if ($data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
            if ($data->status == ENROL_USER_ACTIVE && $data->timeend == 0) {
                // Already enrolled with no end time.
                return ob_get_clean();
            }
        }

        // Check enrollment dates.
        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            if (($instance->customint4 == 0 || $instance->customint4 == 2) && !$force) {
                return ob_get_clean();
            }
        }

        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            if (($instance->customint4 == 0 || $instance->customint4 == 1) && !$force) {
                return ob_get_clean();
            }
        }

        // Calculate cost.
        if ((float) $instance->cost <= 0) {
            $cost = (float) $this->get_config('cost');
        } else {
            $cost = (float) $instance->cost;
        }

        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);
        $currency = $instance->currency ?: 'COP'; // Default to COP.

        if (abs($cost) < 0.01) {
            // No cost.
            echo '<p>' . get_string('nocost', 'enrol_nexuspay') . '</p>';
        } else {
            $template = [
                'isguestuser' => isguestuser() || !isloggedin(),
                'cost' => \core_payment\helper::get_cost_as_string($cost, $currency),
                'instanceid' => $instance->id,
                'instancename' => $instance->name,
                'courseid' => $instance->courseid,
                'description' => get_string(
                    'purchasedescription',
                    'enrol_nexuspay',
                    format_string($course->fullname, true, ['context' => $context])
                ),
                'successurl' => \enrol_nexuspay\payment\service_provider::get_success_url('fee', $instance->id)->out(false),
                'sesskey' => sesskey(),
            ];
            
            echo $OUTPUT->render_from_template('enrol_nexuspay/payment_region', $template);
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
                'roleid'     => $data->roleid,
                'cost'       => $data->cost,
                'currency'   => $data->currency,
            ];
        }
        if ($merge && $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            $instanceid = $this->add_instance($course, (array) $data);
        }
        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldinstancestatus
     */
    public function restore_user_enrolment(
        restore_enrolments_structure_step $step,
        $data,
        $instance,
        $userid,
        $oldinstancestatus
    ) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = [
            ENROL_INSTANCE_ENABLED  => get_string('yes'),
            ENROL_INSTANCE_DISABLED => get_string('no')
        ];
        return $options;
    }

    /**
     * Return an array of valid options for the expirynotify property.
     *
     * @return array
     */
    protected function get_expirynotify_options() {
        $options = [
            0 => get_string('no'),
            1 => get_string('expirynotifyenroller', 'core_enrol'),
            2 => get_string('expirynotifyall', 'core_enrol')
        ];
        return $options;
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
        // Merge notification settings for form.
        if ($instance->notifyall && $instance->expirynotify) {
            $instance->expirynotify = 2;
        }
        unset($instance->notifyall);

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_TEXT);

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_nexuspay'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        // New enrollments setting.
        $options = [
            1 => get_string('yes'),
            0 => get_string('no'),
        ];
        $mform->addElement('select', 'customint3', get_string('newenrols', 'enrol_nexuspay'), $options);
        $mform->setDefault('customint3', $this->get_config('newenrols'));
        $mform->addHelpButton('customint3', 'newenrols', 'enrol_nexuspay');

        // Payment account.
        $accounts = \core_payment\helper::get_payment_accounts_menu($context);
        if ($accounts) {
            $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
            $mform->addElement('select', 'customint1', get_string('paymentaccount', 'enrol_nexuspay'), $accounts);
        } else {
            $mform->addElement(
                'static',
                'customint1_text',
                get_string('paymentaccount', 'enrol_nexuspay'),
                html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-danger')
            );
            $mform->addElement('hidden', 'customint1');
            $mform->setType('customint1', PARAM_INT);
        }
        $mform->addHelpButton('customint1', 'paymentaccount', 'enrol_nexuspay');

        // Cost.
        $mform->addElement('text', 'cost', get_string('cost', 'enrol_nexuspay'), ['size' => 6]);
        $mform->setType('cost', PARAM_RAW);
        $mform->setDefault('cost', format_float($this->get_config('cost'), 2, true));

        // Currency.
        $supportedcurrencies = $this->get_possible_currencies();
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_nexuspay'), $supportedcurrencies);
        $mform->setDefault('currency', 'COP'); // Default to Colombian Peso.

        // Role.
        $roles = $this->get_roleid_options($instance, $context);
        $mform->addElement('select', 'roleid', get_string('assignrole', 'enrol_nexuspay'), $roles);
        $mform->setDefault('roleid', $this->get_config('roleid'));

        // Enrollment period.
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_nexuspay'), ['optional' => true, 'defaultunit' => 86400]);
        $mform->setDefault('enrolperiod', $this->get_config('enrolperiod'));
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_nexuspay');

        // Free trial period.
        $mform->addElement('duration', 'customint6', get_string('freetrial', 'enrol_nexuspay'), ['optional' => true, 'defaultunit' => 86400]);
        $mform->addHelpButton('customint6', 'freetrial', 'enrol_nexuspay');

        // Group settings.
        $options = [
            0 => get_string('no'),
            1 => get_string('yes'),
            2 => get_string('force'),
        ];
        $mform->addElement('select', 'customint2', get_string('groupkey', 'enrol_self'), $options);
        $mform->addHelpButton('customint2', 'groupkey', 'enrol_self');

        // Default group.
        $options = [0 => get_string('no')];
        $groups = groups_get_all_groups($instance->courseid);
        foreach ($groups as $group) {
            $options[$group->id] = $group->name;
        }
        $mform->addElement('select', 'customtext1', get_string('defaultgroup', 'enrol_nexuspay'), $options);

        // Expiry notifications.
        $options = $this->get_expirynotify_options();
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

        $options = ['optional' => false, 'defaultunit' => 86400];
        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);

        // Enrollment dates.
        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_nexuspay'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_nexuspay');

        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_nexuspay'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_nexuspay');

        // Advanced settings.
        $mform->addElement('advcheckbox', 'customint5', get_string('uninterrupted', 'enrol_nexuspay'));
        $mform->setType('customint5', PARAM_INT);
        $mform->addHelpButton('customint5', 'uninterrupted', 'enrol_nexuspay');

        $mform->addElement('advcheckbox', 'customint8', get_string('showduration', 'enrol_nexuspay'));
        $mform->setType('customint8', PARAM_INT);

        $options = [
            0 => get_string('no'),
            1 => get_string('enrolstartdate', 'enrol_nexuspay'),
            2 => get_string('enrolenddate', 'enrol_nexuspay'),
            3 => get_string('always'),
        ];
        $mform->addElement('select', 'customint4', get_string('forcepayment', 'enrol_nexuspay'), $options);
        $mform->setType('customint4', PARAM_INT);
        $mform->addHelpButton('customint4', 'forcepayment', 'enrol_nexuspay');

        if (enrol_accessing_via_instance($instance)) {
            $warningtext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warningtext);
        }

        return true;
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = [];

        if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
            $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_nexuspay');
        }

        $cost = str_replace(get_string('decsep', 'langconfig'), '.', $data['cost']);
        if (!is_numeric($cost) || $cost < 0 || round($cost, 2) != $cost) {
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
            'customint8' => PARAM_INT,
        ];

        if ($data['expirynotify'] != 0) {
            $tovalidate['expirythreshold'] = PARAM_INT;
        }

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        if ($data['status'] == ENROL_INSTANCE_ENABLED &&
            (!$data['customint1'] || !array_key_exists($data['customint1'], \core_payment\helper::get_payment_accounts_menu($context)))
        ) {
            $errors['status'] = get_string('validationerror', 'enrol_nexuspay');
        }

        return $errors;
    }

    /**
     * Return an array of valid options for the roleid.
     *
     * @param stdClass $instance
     * @param context $context
     * @return array
     */
    protected function get_roleid_options($instance, $context) {
        if ($instance->id) {
            $roles = get_default_enrol_roles($context, $instance->roleid);
        } else {
            $roles = get_default_enrol_roles($context, $this->get_config('roleid'));
        }
        return $roles;
    }

    /**
     * Sets up navigation entries.
     *
     * @param navigation_node $instancesnode navigation node
     * @param stdClass $instance enrol record instance
     * @return void
     */
    public function add_course_navigation($instancesnode, stdClass $instance) {
        global $USER, $DB;

        if ($instance->enrol !== 'nexuspay') {
            throw new coding_exception('Invalid enrol instance type!');
        }

        $context = context_course::instance($instance->courseid);
        
        // Check if user has payment pending.
        $data = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id]);
        if ($data && $instance->expirynotify && $data->timeend && 
            $data->timeend - time() < $instance->expirythreshold) {
            
            $navigation = $instancesnode;
            while ($navigation->parent !== null) {
                $navigation = $navigation->parent;
            }
            
            if ($courseadminnode = $navigation->get("courseadmin")) {
                if (!$nexuspaynode = $courseadminnode->get("nexuspay")) {
                    $nodeproperties = [
                        'text'      => get_string('menuname', 'enrol_nexuspay'),
                        'shorttext' => get_string('menunameshort', 'enrol_nexuspay'),
                        'type'      => navigation_node::TYPE_SETTING,
                        'key'       => 'nexuspay',
                    ];
                    $nexuspaynode = new navigation_node($nodeproperties);
                    $courseadminnode->add_node($nexuspaynode, 'users');
                }
                
                $nexuspaynode->add(
                    get_string('renewenrolment', 'enrol_nexuspay'),
                    new moodle_url('/enrol/nexuspay/pay.php', [
                        'courseid' => $instance->courseid,
                        'id' => $instance->id
                    ]),
                    navigation_node::TYPE_SETTING,
                    get_string('renewenrolment', 'enrol_nexuspay'),
                    'nexuspay',
                    new pix_icon('icon', '', 'enrol_nexuspay')
                );
            }
        }
    }
}