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

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to send user limit notification emails.
 *
 * This task monitors daily user counts against configured thresholds
 * and sends email notifications when usage exceeds warning levels.
 * Notification frequency is based on severity of usage percentage.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_userlimit extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('processuserlimitnotificationtask', 'report_usage_monitor');
    }

    /**
     * Execute the user limit notification task.
     *
     * Checks daily user count against configured thresholds and sends
     * email notifications when appropriate.
     *
     * @return bool True on success.
     */
    public function execute(): bool {
        global $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Plugin disabled - unauthorized hostname.");
            }
            return true; // Return true to not mark task as failed.
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Starting user limit notification task...");
        }

        // Procesar la notificación de límite de usuarios
        $result = $this->notify_user_limit();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: User limit notification task completed.");
        }
        
        return $result;
    }

    /**
     * Gestiona el proceso de notificación del límite de usuarios.
     *
     * @return bool
     */
    private function notify_user_limit()
    {
        global $DB, $CFG;
        
        // Obtener configuraciones del plugin
        $reportconfig = get_config('report_usage_monitor');
        
        // Obtener el umbral de usuarios configurado
        $user_threshold = (int)($reportconfig->max_daily_users_threshold ?? 100);
        
        // Nivel de advertencia configurado (default: 90%)
        $warning_level = !empty($reportconfig->users_warning_level) ? $reportconfig->users_warning_level : 90;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: User threshold: {$user_threshold}");
            mtrace("report_usage_monitor: Warning level: {$warning_level}%");
        }

        // REFACTORIZADO: Consulta optimizada para obtener usuarios activos del último día
        // Utilizamos la función refactorizada que trabaja directamente con timestamps
        $sql = "SELECT COUNT(DISTINCT userid) AS conteo_accesos_unicos,
                       UNIX_TIMESTAMP(DATE(FROM_UNIXTIME(timecreated))) AS timestamp_fecha
                FROM {logstore_standard_log}
                WHERE action = 'loggedin'
                  AND timecreated > :start_time
                GROUP BY timestamp_fecha
                ORDER BY timestamp_fecha DESC
                LIMIT 1";

        $params = [
            'start_time' => strtotime('-1 day')
        ];

        $lastday_users_record = $DB->get_record_sql($sql, $params);

        // Verificar si hay datos de usuarios
        if (!$lastday_users_record) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: No user data found for the last day.");
            }
            return true;
        }

        // Calcular porcentaje de uso
        $users_count = (int)$lastday_users_record->conteo_accesos_unicos;
        $users_percent = calculate_threshold_percentage($users_count, $user_threshold);
        $fecha_timestamp = $lastday_users_record->timestamp_fecha;

        // Verificar que el timestamp sea válido
        if (!is_numeric($fecha_timestamp) || $fecha_timestamp <= 0) {
            debugging('report_usage_monitor: Invalid timestamp obtained: ' . var_export($fecha_timestamp, true), DEBUG_DEVELOPER);
            $fecha_timestamp = time(); // Usar timestamp actual como fallback
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Unique users: {$users_count}, Percent: {$users_percent}%, Date: " . date('Y-m-d', $fecha_timestamp));
        }

        // Verificar si el porcentaje supera el nivel de advertencia configurado
        if ($users_percent < $warning_level) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: User usage ({$users_percent}%) is below warning level ({$warning_level}%). No notification sent.");
            }
            return true;
        }

        // Determinar si es necesario enviar notificación ahora
        $notification_interval = $this->calculate_notification_interval($users_percent);
        $last_notification_time = get_config('report_usage_monitor', 'last_notificationusers_time') ?: 0;
        $current_time = time();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Notification interval: {$notification_interval}s, Last notification: {$last_notification_time}");
            $time_since_last = $current_time - $last_notification_time;
            mtrace("report_usage_monitor: Time since last notification: {$time_since_last}s");
        }

        // Verificar si ha pasado suficiente tiempo desde la última notificación
        if ($current_time - $last_notification_time < $notification_interval) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Notification interval not reached.");
                $time_remaining = ($last_notification_time + $notification_interval) - $current_time;
                mtrace("report_usage_monitor: Next notification possible in: " . format_time($time_remaining));
            }
            return true;
        }

        // Llegados a este punto, debemos enviar la notificación
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("report_usage_monitor: Sending user limit notification...");
        }

        // REFACTORIZADO: Ahora pasamos el timestamp directamente,
        // la función email_notify_user_limit se encargará de formatearlo
        $result = email_notify_user_limit($users_count, $fecha_timestamp, $users_percent);

        // Actualizar tiempo de última notificación
        if ($result) {
            set_config('last_notificationusers_time', $current_time, 'report_usage_monitor');

            // Registrar la notificación en el historial
            $this->log_notification($users_percent, $users_count, $user_threshold);

            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Notification sent successfully.");
            }
        } else {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("report_usage_monitor: Error sending email notification.");
            }
        }
        
        return $result;
    }

    /**
     * Calcula el intervalo de notificación basado en el porcentaje de uso de usuarios.
     * 
     * @param float $users_percent Porcentaje de uso de usuarios.
     * @return int Intervalo en segundos entre notificaciones.
     */
    private function calculate_notification_interval($users_percent)
    {
        // Validación del parámetro
        if (!is_numeric($users_percent)) {
            debugging('calculate_notification_interval: Porcentaje no numérico: ' . var_export($users_percent, true), DEBUG_DEVELOPER);
            return PHP_INT_MAX; // No notificar en caso de error
        }

        $thresholds = [
            100 => 24 * 60 * 60,     // 1 día cuando se supera el 100%
            90 => 3 * 24 * 60 * 60,  // 3 días cuando se supera el 90%
            80 => 7 * 24 * 60 * 60   // 1 semana cuando se supera el 80%
        ];

        foreach ($thresholds as $threshold => $interval) {
            if ($users_percent >= $threshold) {
                return $interval;
            }
        }

        return PHP_INT_MAX; // No notification if under lowest threshold
    }
    
    /**
     * Registra información sobre la notificación enviada.
     * 
     * @param float $users_percent Porcentaje de uso de usuarios.
     * @param int $users_count Conteo de usuarios.
     * @param int $user_threshold Umbral de usuarios.
     * @return bool
     */
    private function log_notification($users_percent, $users_count, $user_threshold)
    {
        global $DB;
        
        // Verificar si existe la tabla para almacenar el historial
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'users';
            $record->percentage = $users_percent;
            $record->value = $users_count;
            $record->threshold = $user_threshold;
            $record->timecreated = time();
            
            // Usar transacción para garantizar consistencia
            $transaction = $DB->start_delegated_transaction();
            
            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                $transaction->allow_commit();
                
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Notificación registrada en el historial.");
                }
                return true;
            } catch (\Exception $e) {
                $transaction->rollback($e);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Error al registrar la notificación: " . $e->getMessage());
                }
                return false;
            }
        }
        
        return false;
    }
}