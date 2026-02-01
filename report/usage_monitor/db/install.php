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
 * Páginas de administración del plugin se definen aquí.
 *
 * @package     report_usage_monitor
 * @category    admin
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 o posterior
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

/**
 * Configura el script de instalación.
 * Esta función se ejecuta durante la instalación del complemento y muestra notificaciones al usuario según las capacidades del servidor.
 * @return void
 */
function xmldb_report_usage_monitor_install()
{
    global $OUTPUT, $CFG;

    $pathtodu = '';

    // First, check if $CFG->pathtodu is already configured in Moodle's global settings.
    if (!empty($CFG->pathtodu) && file_exists($CFG->pathtodu) && is_executable($CFG->pathtodu)) {
        $pathtodu = $CFG->pathtodu;
        echo $OUTPUT->notification(
            get_string('pathtodu', 'report_usage_monitor') . ': ' . $pathtodu . ' (Moodle config)',
            'success'
        );
    } else {
        // If not configured globally, try to auto-detect.
        $shellexecavailable = false;
        if (function_exists('shell_exec')) {
            $disabledfunctions = ini_get('disable_functions');
            if (empty($disabledfunctions) || strpos($disabledfunctions, 'shell_exec') === false) {
                $shellexecavailable = true;
            }
        }

        if ($shellexecavailable) {
            echo $OUTPUT->notification(
                get_string('pathtodurecommendation', 'report_usage_monitor'),
                'info'
            );

            // Auto-detect du path on Linux systems.
            if (PHP_OS_FAMILY === 'Linux') {
                $detectedpath = @shell_exec('which du 2>/dev/null');
                $detectedpath = $detectedpath !== null ? trim($detectedpath) : '';

                if (!empty($detectedpath) && file_exists($detectedpath) && is_executable($detectedpath)) {
                    $pathtodu = $detectedpath;
                    // Save to global config so it persists.
                    set_config('pathtodu', $pathtodu);
                    echo $OUTPUT->notification(
                        get_string('pathtodu', 'report_usage_monitor') . ': ' . $pathtodu,
                        'success'
                    );
                }
            }
        } else {
            echo $OUTPUT->notification(
                get_string('activateshellexec', 'report_usage_monitor'),
                'warning'
            );
        }
    }

    // Check if hostname is valid for this plugin.
    // Note: Scheduled tasks are created AFTER install.php runs (by upgrade_component_updated).
    // The task state (enabled/disabled) is determined in db/tasks.php based on hostname.
    $hostnamevalid = report_usage_monitor_is_hostname_valid();

    if ($hostnamevalid) {
        // Server is authorized - tasks will be enabled when created.
        echo $OUTPUT->notification(
            get_string('pluginstatus_enabled', 'report_usage_monitor'),
            'success'
        );
        // Store du path info for when tasks are created.
        if (!empty($pathtodu)) {
            set_config('pathtodu_detected', $pathtodu, 'report_usage_monitor');
        }
    } else {
        // Server not authorized - tasks will be disabled when created.
        echo $OUTPUT->notification(
            get_string('plugin_disabled_hostname', 'report_usage_monitor'),
            'warning'
        );
    }

    return true;
}
