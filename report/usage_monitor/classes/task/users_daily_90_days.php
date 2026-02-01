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
 * Scheduled task to calculate maximum users in the last 90 days.
 *
 * This task finds the day with the highest number of unique logins
 * within the last 90 days and stores this information for display
 * on the dashboard.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * 90-day maximum users calculation scheduled task.
 *
 * Analyzes login data from the last 90 days to find the day
 * with the maximum number of unique user logins.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_daily_90_days extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('getlastusers90days', 'report_usage_monitor');
    }

    /**
     * Execute the 90-day users calculation task.
     *
     * Finds the maximum daily user count in the last 90 days
     * and stores the result in plugin configuration.
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
            mtrace("Iniciando tarea de cálculo de usuarios diarios en los últimos 90 días...");
        }

        // REFACTORIZADO: Usamos la consulta SQL sin necesidad de pasar un formato de fecha
        $sql = max_userdaily_for_90_days();
        
        $max_users = 0;
        $max_date = 0;
        
        // Iniciar transacción para asegurar consistencia de datos
        $transaction = $DB->start_delegated_transaction();
        
        try {
            $users_90_days_records = $DB->get_records_sql($sql);

            foreach ($users_90_days_records as $record) {
                // Validar que la fecha sea un timestamp válido
                if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                    debugging('users_daily_90_days: Timestamp inválido encontrado: ' . var_export($record->fecha, true), DEBUG_DEVELOPER);
                    continue;
                }
                
                // La consulta ahora devuelve campos estandarizados
                if (isset($record->usuarios)) {
                    $max_users = $record->usuarios;
                    $max_date = $record->fecha;

                    set_config('max_userdaily_for_90_days_date', $record->fecha, 'report_usage_monitor');
                    set_config('max_userdaily_for_90_days_users', $record->usuarios, 'report_usage_monitor');
                }
            }

            // Registrar el valor máximo en el historial para trazabilidad
            if ($max_users > 0 && $max_date > 0 && $DB->get_manager()->table_exists('report_usage_monitor_history')) {
                // Verificar si ya existe un registro para esta fecha
                $existing = $DB->get_record_sql(
                    "SELECT id FROM {report_usage_monitor_history}
                    WHERE type = 'users90d' AND timecreated = ?",
                    [$max_date]
                );

                if (!$existing) {
                    // Crear el registro histórico
                    $reportconfig = get_config('report_usage_monitor');
                    $threshold = $reportconfig->max_daily_users_threshold ?? 100;

                    $record = new \stdClass();
                    // Cambiado 'users_90_days' a 'users90d' para cumplir con la limitación de 10 caracteres
                    $record->type = 'users90d';
                    $record->percentage = calculate_threshold_percentage($max_users, $threshold);
                    $record->value = $max_users;
                    $record->threshold = $threshold;
                    $record->timecreated = $max_date;

                    $DB->insert_record('report_usage_monitor_history', $record);
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Máximo de usuarios 90 días registrado en el historial.");
                    }
                }
            }
            
            // Guardar timestamp de última ejecución
            $execution_time = time();
            set_config('lastexecutioncalculateusers90days', $execution_time, 'report_usage_monitor');
            
            $transaction->allow_commit();
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('users_daily_90_days: Error en procesamiento: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            $formatted_date = ($max_date && is_numeric($max_date) && $max_date > 0) ? date('d/m/Y', (int)$max_date) : 'N/A';
            mtrace("Usuarios principales en los últimos 90 días calculados: " . $max_users . " en fecha " . $formatted_date);
            mtrace("Tarea de cálculo de usuarios principales en los últimos 90 días completada.");
        }
    }
}