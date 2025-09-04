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
 * Settings for the NexusPay enrollment plugin.
 *
 * @package    enrol_nexuspay
 * @copyright  2025 Alonso Arias <soporte@nexuslabs.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    
    // Get available currencies.
    $currencies = enrol_get_plugin('nexuspay')->get_possible_currencies();

    if (empty($currencies)) {
        $notify = new \core\output\notification(
            get_string('nocurrencysupported', 'core_payment'),
            \core\output\notification::NOTIFY_WARNING
        );
        $settings->add(new admin_setting_heading(
            'enrol_nexuspay_nocurrency',
            '',
            $OUTPUT->render($notify)
        ));
    }

    // Plugin description heading.
    $settings->add(new admin_setting_heading(
        'enrol_nexuspay_settings',
        '',
        get_string('pluginname_desc', 'enrol_nexuspay')
    ));

    // --- General Settings ---
    
    // Status.
    $options = [
        ENROL_INSTANCE_ENABLED  => get_string('yes'),
        ENROL_INSTANCE_DISABLED => get_string('no')
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/status',
        get_string('status', 'enrol_nexuspay'),
        get_string('status_desc', 'enrol_nexuspay'),
        ENROL_INSTANCE_DISABLED,
        $options
    ));

    // Allow new enrollments.
    $options = [
        1 => get_string('yes'),
        0 => get_string('no'),
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/newenrols',
        get_string('newenrols', 'enrol_nexuspay'),
        get_string('newenrols_desc', 'enrol_nexuspay'),
        1,
        $options
    ));

    // --- Payment Settings ---
    
    if (!empty($currencies)) {
        // Default cost.
        $settings->add(new admin_setting_configtext(
            'enrol_nexuspay/cost',
            get_string('cost', 'enrol_nexuspay'),
            '',
            0,
            PARAM_FLOAT,
            6
        ));
        
        // Default currency - prioritize COP for Colombia.
        $defaultcurrency = 'COP';
        if (!isset($currencies['COP'])) {
            $defaultcurrency = 'USD';
        }
        
        $settings->add(new admin_setting_configselect(
            'enrol_nexuspay/currency',
            get_string('currency', 'enrol_nexuspay'),
            '',
            $defaultcurrency,
            $currencies
        ));
    }

    // Default role.
    if (!during_initial_install()) {
        $options = get_default_enrol_roles(context_system::instance());
        $student = get_archetype_roles('student');
        $student = reset($student);
        $settings->add(new admin_setting_configselect(
            'enrol_nexuspay/roleid',
            get_string('defaultrole', 'enrol_nexuspay'),
            get_string('defaultrole_desc', 'enrol_nexuspay'),
            $student->id ?? null,
            $options
        ));
    }

    // --- Enrollment Period Settings ---
    
    // Enrollment period.
    $settings->add(new admin_setting_configduration(
        'enrol_nexuspay/enrolperiod',
        get_string('enrolperiod', 'enrol_nexuspay'),
        get_string('enrolperiod_desc', 'enrol_nexuspay'),
        0,
        86400 // Default unit: days
    ));

    // Free trial period.
    $settings->add(new admin_setting_configduration(
        'enrol_nexuspay/freetrial',
        get_string('freetrial', 'enrol_nexuspay'),
        get_string('freetrial_help', 'enrol_nexuspay'),
        0,
        86400 // Default unit: days
    ));

    // Uninterrupted payment.
    $settings->add(new admin_setting_configcheckbox(
        'enrol_nexuspay/uninterrupted',
        get_string('uninterrupted', 'enrol_nexuspay'),
        get_string('uninterrupted_help', 'enrol_nexuspay'),
        0
    ));

    // Show duration on enrollment page.
    $settings->add(new admin_setting_configcheckbox(
        'enrol_nexuspay/showduration',
        get_string('showduration', 'enrol_nexuspay'),
        '',
        1
    ));

    // --- Group Settings ---
    
    // Group key.
    $options = [
        0 => get_string('no'),
        1 => get_string('yes'),
        2 => get_string('force'),
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/groupkey',
        get_string('groupkey', 'enrol_self'),
        get_string('groupkey_desc', 'enrol_self'),
        0,
        $options
    ));

    // --- Expiry Settings ---
    
    // Expiry action.
    $options = [
        ENROL_EXT_REMOVED_KEEP           => get_string('extremovedkeep', 'enrol'),
        ENROL_EXT_REMOVED_SUSPENDNOROLES => get_string('extremovedsuspendnoroles', 'enrol_nexuspay'),
        ENROL_EXT_REMOVED_UNENROL        => get_string('extremovedunenrol', 'enrol'),
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/expiredaction',
        get_string('expiredaction', 'enrol_nexuspay'),
        get_string('expiredaction_help', 'enrol_nexuspay'),
        ENROL_EXT_REMOVED_KEEP,
        $options
    ));

    // Expiry notification.
    $options = [
        0 => get_string('no'),
        1 => get_string('expirynotifyenroller', 'core_enrol'),
        2 => get_string('expirynotifyall', 'core_enrol')
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/expirynotify',
        get_string('expirynotify', 'core_enrol'),
        get_string('expirynotify_help', 'core_enrol'),
        0,
        $options
    ));

    // Expiry threshold.
    $settings->add(new admin_setting_configduration(
        'enrol_nexuspay/expirythreshold',
        get_string('expirythreshold', 'core_enrol'),
        get_string('expirythreshold_help', 'core_enrol'),
        86400,
        86400
    ));

    // Hour to send expiry notifications.
    $options = [];
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf('%02d:00', $i);
    }
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/expirynotifyhour',
        get_string('expirynotifyhour', 'enrol_nexuspay'),
        '',
        6,
        $options
    ));

    // Expiry notification period.
    $settings->add(new admin_setting_configduration(
        'enrol_nexuspay/expirynotifyperiod',
        get_string('expirynotifyperiod', 'enrol_nexuspay'),
        get_string('expirynotifyperiod_desc', 'enrol_nexuspay'),
        900,
        60 // Default unit: minutes
    ));

    // Force payment settings.
    $options = [
        0 => get_string('no'),
        1 => get_string('enrolstartdate', 'enrol_nexuspay'),
        2 => get_string('enrolenddate', 'enrol_nexuspay'),
        3 => get_string('always'),
    ];
    $settings->add(new admin_setting_configselect(
        'enrol_nexuspay/forcepayment',
        get_string('forcepayment', 'enrol_nexuspay'),
        get_string('forcepayment_help', 'enrol_nexuspay'),
        0,
        $options
    ));
}