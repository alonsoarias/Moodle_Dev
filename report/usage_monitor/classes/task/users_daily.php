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
 * Scheduled task to calculate top daily unique user accesses.
 *
 * This task maintains a rolling list of the top 10 daily user access counts,
 * calculating usage percentages against configured thresholds and cleaning
 * up old historical data.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Daily users top calculation scheduled task.
 *
 * Maintains a list of the top 10 daily user counts, updates usage
 * percentage calculations, and performs history cleanup.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class users_daily extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('getlastusers', 'report_usage_monitor');
    }

    /**
     * Execute the daily users calculation task.
     *
     * This method performs the following operations:
     * 1. Retrieves current top 10 daily user records
     * 2. Gets today's unique user count
     * 3. Inserts or updates the top 10 list as needed
     * 4. Calculates usage percentage against threshold
     * 5. Cleans up records older than 6 months
     *
     * @return void
     */
    public function execute(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Plugin disabled - unauthorized hostname.");
            }
            return;
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Starting daily unique access top calculation task...");
        }

        $array_daily_top = [];

        // Iniciar transacción para asegurar consistencia de datos
        $transaction = $DB->start_delegated_transaction();

        try {
            // First, clean up records older than 6 months.
            $oldremoved = report_usage_monitor_cleanup_old_top_records();
            if ($oldremoved > 0 && debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Removed $oldremoved records older than 6 months.");
            }

            // Get the current top daily users AFTER cleanup.
            $userdaily_records = report_user_daily_top_task();

            foreach ($userdaily_records as $record) {
                // Validar que fecha sea un timestamp válido
                if (!is_numeric($record->fecha) || $record->fecha <= 0) {
                    debugging('users_daily: Timestamp inválido encontrado: ' . var_export($record->fecha, true), DEBUG_DEVELOPER);
                    continue;
                }

                $array_daily_top[] = [
                    "usuarios" => $record->cantidad_usuarios,
                    "fecha" => $record->fecha,
                ];
            }

            // Calculate minimum user count from current records.
            if (!empty($array_daily_top)) {
                $menor = min(array_column($array_daily_top, 'usuarios'));
            } else {
                $menor = null;
            }

            // Get today's/yesterday's user count.
            $users_daily_record = user_limit_daily_task();
            $users = [];

            foreach ($users_daily_record as $log) {
                // Validar que fecha sea un timestamp válido
                if (!is_numeric($log->fecha) || $log->fecha <= 0) {
                    debugging('users_daily: Timestamp inválido en user_limit_daily_task: ' . var_export($log->fecha, true), DEBUG_DEVELOPER);
                    continue;
                }

                $users = [
                    "usuarios" => $log->conteo_accesos_unicos,
                    "fecha" => $log->fecha,
                ];
                // Solo se espera un registro, así que se puede salir del bucle
                break;
            }

            // If no users for current day, do nothing
            if (empty($users)) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("report_usage_monitor: No user records found for current day.");
                }
            } else {
                // Always try to insert - the function handles duplicate checking and
                // will replace the lowest count record if table is full.
                insert_top_sql($users['fecha'], $users['usuarios']);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("report_usage_monitor: Processing record with {$users['usuarios']} users for timestamp {$users['fecha']}.");
                }
            }
            
            // Calcular el porcentaje de uso respecto al umbral
            $reportconfig = get_config('report_usage_monitor');
            $users_today = !empty($reportconfig->totalusersdaily) ? ($reportconfig->totalusersdaily) : 0;
            $max_users_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
            $users_percent = calculate_threshold_percentage($users_today, $max_users_threshold);
            $users_warning_class = ($users_percent < 70) ? 'bg-success' : (($users_percent < 90) ? 'bg-warning' : 'bg-danger');
            
            // Guardar valores precomputados
            set_config('users_percent', $users_percent, 'report_usage_monitor');
            set_config('users_warning_class', $users_warning_class, 'report_usage_monitor');
            
            // Limpiar datos antiguos - Ahora buscamos por el valor del timestamp para asegurar consistencia
            $sixMonthsAgo = time() - (180 * 24 * 60 * 60); // 180 days
            
            // Para report_usage_monitor_history tabla
            if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
                $oldHistoryCount = $DB->count_records_select('report_usage_monitor_history', 'timecreated < ?', [$sixMonthsAgo]);
                
                if ($oldHistoryCount > 0) {
                    $DB->delete_records_select('report_usage_monitor_history', 'timecreated < ?', [$sixMonthsAgo]);
                    
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Removed $oldHistoryCount records older than 6 months from report_usage_monitor_history.");
                    }
                }
            }
            
            $transaction->allow_commit();
            
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging('users_daily: Error en procesamiento: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Daily unique access top calculation task completed.");
        }
    }
}