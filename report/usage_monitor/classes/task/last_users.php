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
 * Scheduled task to calculate recently connected users.
 *
 * This task counts the number of unique users who have logged in
 * during the current day and stores the count for dashboard display.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Recent users calculation scheduled task.
 *
 * Counts unique daily logins and stores the result for use
 * in the dashboard and threshold calculations.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class last_users extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('getlastusersconnected', 'report_usage_monitor');
    }

    /**
     * Execute the recent users calculation task.
     *
     * Counts unique users who have logged in today and stores
     * the result in plugin configuration.
     *
     * @return void
     */
    public function execute(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Plugin deshabilitado: hostname no autorizado o desactivado por configuración.");
            }
            return;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de cálculo de usuarios conectados recientemente...");
        }

        // Iniciar transacción para garantizar consistencia de datos
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Recuperar los usuarios conectados recientemente para hoy.
            $users = $DB->get_records_sql(users_today());
            $users_today = 0;
            
            foreach ($users as $log) {
                // Verificar que el timestamp es válido
                if (!isset($log->timestamp_fecha) || !is_numeric($log->timestamp_fecha) || $log->timestamp_fecha <= 0) {
                    debugging('last_users: Timestamp inválido en resultado de users_today(): ' . var_export($log, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                if (!isset($log->conteo_accesos_unicos) || !is_numeric($log->conteo_accesos_unicos)) {
                    debugging('last_users: Conteo de usuarios inválido: ' . var_export($log, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                $users_today = $log->conteo_accesos_unicos;
                set_config('totalusersdaily', $users_today, 'report_usage_monitor');
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Usuarios conectados hoy: $users_today para la fecha " . userdate($log->timestamp_fecha));
                }
            }

            // Precomputar porcentajes para el dashboard
            $reportconfig = get_config('report_usage_monitor');
            $max_users_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
            $users_percent = calculate_threshold_percentage($users_today, $max_users_threshold);
            $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');

            // Guardar valores precomputados
            set_config('users_percent', $users_percent, 'report_usage_monitor');
            set_config('users_warning_class', $users_warning_class, 'report_usage_monitor');

            // Guardar timestamp de última ejecución
            $execution_time = time();
            set_config('lastexecutioncalculateuserdaily', $execution_time, 'report_usage_monitor');
            
            // Permitir commit si todo ha ido bien
            $transaction->allow_commit();
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Usuarios conectados recientemente: $users_today.");
                mtrace("Porcentaje del umbral: $users_percent%");
                mtrace("Tarea completada con éxito.");
            }
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $transaction->rollback($e);
            
            debugging('last_users: Error en tarea de cálculo de usuarios: ' . $e->getMessage(), DEBUG_DEVELOPER);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Error durante el cálculo de usuarios: " . $e->getMessage());
                mtrace("Se ha revertido cualquier cambio parcial.");
            }
            
            // Permitir que la excepción se propague para registrar el fallo de la tarea
            throw $e;
        }

        return true;
    }
}