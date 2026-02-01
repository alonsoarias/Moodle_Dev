<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin administration pages are defined here.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

if ($ADMIN->fulltree) {

    // =========================================================================
    // PLUGIN STATUS SECTION
    // =========================================================================
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/pluginstatus',
        get_string('pluginstatus', 'report_usage_monitor'),
        ''
    ));

    // Check hostname validity and show appropriate message.
    $hostvalid = report_usage_monitor_is_hostname_valid();
    $config = get_config('report_usage_monitor');
    $plugindisabled = !empty($config->plugin_disabled);

    if (!$hostvalid) {
        // Hostname not valid - plugin cannot be enabled.
        $statusmsg = html_writer::tag('div',
            html_writer::tag('strong', get_string('pluginstatus_unauthorized', 'report_usage_monitor')) .
            html_writer::empty_tag('br') .
            get_string('pluginstatus_unauthorized_desc', 'report_usage_monitor'),
            ['class' => 'alert alert-danger']
        );
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/pluginstatus_msg',
            '',
            $statusmsg
        ));
    } else if ($plugindisabled) {
        // Plugin manually disabled via API.
        $statusmsg = html_writer::tag('div',
            html_writer::tag('strong', get_string('pluginstatus_disabled', 'report_usage_monitor')) .
            html_writer::empty_tag('br') .
            get_string('pluginstatus_disabled_desc', 'report_usage_monitor'),
            ['class' => 'alert alert-warning']
        );
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/pluginstatus_msg',
            '',
            $statusmsg
        ));

        // Show enable checkbox (only on valid hostnames).
        $settings->add(new admin_setting_configcheckbox(
            'report_usage_monitor/plugin_enabled',
            get_string('plugin_enable', 'report_usage_monitor'),
            get_string('plugin_enable_desc', 'report_usage_monitor'),
            1
        ));
    } else {
        // Plugin enabled and hostname valid.
        $statusmsg = html_writer::tag('div',
            html_writer::tag('strong', get_string('pluginstatus_enabled', 'report_usage_monitor')) .
            html_writer::empty_tag('br') .
            get_string('pluginstatus_enabled_desc', 'report_usage_monitor'),
            ['class' => 'alert alert-success']
        );
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/pluginstatus_msg',
            '',
            $statusmsg
        ));

        // Show disable checkbox.
        $settings->add(new admin_setting_configcheckbox(
            'report_usage_monitor/plugin_enabled',
            get_string('plugin_enable', 'report_usage_monitor'),
            get_string('plugin_enable_desc', 'report_usage_monitor'),
            1
        ));
    }

    // =========================================================================
    // MAIN SETTINGS SECTION (only show if plugin can be enabled)
    // =========================================================================
    if ($hostvalid) {
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/mainsettings',
            get_string('mainsettings', 'report_usage_monitor'),
            ''
        ));

        $settings->add(new admin_setting_configtext(
            'report_usage_monitor/max_daily_users_threshold',
            get_string('max_daily_users_threshold', 'report_usage_monitor'),
            get_string('configmax_daily_users_threshold', 'report_usage_monitor'),
            100,
            PARAM_INT
        ));

    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/disk_quota',
        get_string('disk_quota', 'report_usage_monitor'),
        get_string('configdisk_quota', 'report_usage_monitor'),
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'report_usage_monitor/email',
        get_string('email', 'report_usage_monitor'),
        get_string('configemail', 'report_usage_monitor'),
        'hostingmoodle@ingeweb.co',
        PARAM_EMAIL,
        50
    ));
    
    // Sección de configuraciones de notificación
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/notificationsettings',
        get_string('notificationsettings', 'report_usage_monitor'),
        get_string('notificationsettingsinfo', 'report_usage_monitor')
    ));
    
    // Opciones para alertas de espacio en disco (en porcentaje)
    $diskoptions = [
        90 => '90%',
        95 => '95%',
        98 => '98%'
    ];
    
    $settings->add(new admin_setting_configselect(
        'report_usage_monitor/disk_warning_level',
        get_string('disk_warning_level', 'report_usage_monitor'),
        get_string('configdisk_warning_level', 'report_usage_monitor'),
        90,
        $diskoptions
    ));
    
    // Opciones para alertas de usuarios (en porcentaje)
    $useroptions = [
        80 => '80%',
        90 => '90%',
        95 => '95%'
    ];
    
    $settings->add(new admin_setting_configselect(
        'report_usage_monitor/users_warning_level',
        get_string('users_warning_level', 'report_usage_monitor'),
        get_string('configusers_warning_level', 'report_usage_monitor'),
        90,
        $useroptions
    ));
    
    // Configuración para el comando 'du'
    $defaultPathToDu = '';

    // Verificar si shell_exec está disponible (no solo existe, sino que no está deshabilitada).
    $shellexecavailable = false;
    if (function_exists('shell_exec')) {
        $disabledfunctions = ini_get('disable_functions');
        if (empty($disabledfunctions) || strpos($disabledfunctions, 'shell_exec') === false) {
            $shellexecavailable = true;
        }
    }

    if ($shellexecavailable) {
        // Intentar detectar automáticamente la ruta de 'du' en sistemas Linux.
        if (PHP_OS_FAMILY === 'Linux') {
            $pathToDu = @shell_exec('which du 2>/dev/null');
            $pathToDu = $pathToDu !== null ? trim($pathToDu) : '';

            if (!empty($pathToDu) && file_exists($pathToDu) && is_executable($pathToDu)) {
                $defaultPathToDu = $pathToDu;

                // Actualizar la configuración global si no está ya configurada.
                $currentpathtodu = get_config('pathtodu');
                if (empty($currentpathtodu)) {
                    set_config('pathtodu', $defaultPathToDu);
                    // Auto-detected du path: update scheduled tasks to run more frequently.
                    report_usage_monitor_update_scheduled_tasks($defaultPathToDu);
                } else if ($currentpathtodu !== $defaultPathToDu) {
                    // Path changed: update scheduled tasks configuration.
                    report_usage_monitor_update_scheduled_tasks($currentpathtodu);
                }
            } else {
                // Mostrar recomendación si no se puede detectar automáticamente.
                $infocontent = html_writer::tag('div',
                    get_string('pathtodurecommendation', 'report_usage_monitor'),
                    ['class' => 'alert alert-info']
                );
                $settings->add(new admin_setting_heading(
                    'report_usage_monitor/pathtodurecommendation',
                    '',
                    $infocontent
                ));
            }
        } else {
            // Mostrar recomendación para sistemas no Linux.
            $infocontent = html_writer::tag('div',
                get_string('pathtodurecommendation', 'report_usage_monitor'),
                ['class' => 'alert alert-info']
            );
            $settings->add(new admin_setting_heading(
                'report_usage_monitor/pathtodurecommendation',
                '',
                $infocontent
            ));
        }

        // Configuración para la ruta de du.
        $settings->add(new admin_setting_configexecutable(
            'pathtodu',
            get_string('pathtodu', 'report_usage_monitor'),
            get_string('configpathtodu', 'report_usage_monitor') .
            '<br>' .
            get_string('pathtodunote', 'report_usage_monitor'),
            $defaultPathToDu,
            PARAM_PATH,
            255
        ));
    } else {
        // Mostrar advertencia si shell_exec no está activo (solo informativo, no bloquea).
        $alertcontent = html_writer::tag('div',
            get_string('activateshellexec', 'report_usage_monitor'),
            ['class' => 'alert alert-warning']
        );
        $settings->add(new admin_setting_heading(
            'report_usage_monitor/activateshellexec',
            '',
            $alertcontent
        ));
    }

    // Habilitar API para integración con sistemas externos.
    $settings->add(new admin_setting_configcheckbox(
        'report_usage_monitor/enable_api',
        get_string('enable_api', 'report_usage_monitor'),
        get_string('configenable_api', 'report_usage_monitor'),
        1 // Habilitado por defecto.
    ));

    } // End if ($hostvalid)

    // Créditos y descargo de responsabilidad (always show).
    $settings->add(new admin_setting_heading(
        'report_usage_monitor/reportinfotext',
        '',
        get_string('reportinfotext', 'report_usage_monitor')
    ));
}

// Agregar página externa para el informe de uso
$ADMIN->add('reports', new admin_externalpage(
    'report_usage_monitor',
    get_string('pluginname', 'report_usage_monitor'),
    new moodle_url('/report/usage_monitor/index.php')
));

