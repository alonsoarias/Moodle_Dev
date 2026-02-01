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

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task to send disk usage notification emails.
 *
 * This task monitors disk usage against configured thresholds and sends
 * email notifications when usage exceeds warning levels. Notification
 * frequency is based on severity of usage percentage.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_disk extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('processdisknotificationtask', 'report_usage_monitor');
    }

    /**
     * Execute the disk notification task.
     *
     * Checks disk usage against configured thresholds and sends
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
                mtrace("Plugin deshabilitado: hostname no autorizado o desactivado por configuración.");
            }
            return true; // Return true to not mark task as failed.
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de notificación de uso de disco...");
        }

        // Procesar la notificación de disco
        $result = $this->notify_disk_usage();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Tarea de notificación de uso de disco completada.");
        }
        
        return $result;
    }

    /**
     * Calcula el intervalo de notificación basado en el porcentaje de uso del disco.
     * 
     * @param float $disk_percent Porcentaje de uso del disco.
     * @return int Intervalo en segundos entre notificaciones.
     */
    private function calculate_notification_interval($disk_percent)
    {
        // Validación del parámetro
        if (!is_numeric($disk_percent)) {
            debugging('calculate_notification_interval: Porcentaje no numérico: ' . var_export($disk_percent, true), DEBUG_DEVELOPER);
            return PHP_INT_MAX; // No notificar en caso de error
        }

        // Thresholds definidos para el intervalo de notificación según la severidad
        $thresholds = [
            99.9 => 12 * 60 * 60,   // 12 horas para uso crítico (>99.9%)
            98.5 => 24 * 60 * 60,   // 1 día para uso muy alto (>98.5%)
            90 => 5 * 24 * 60 * 60, // 5 días para uso alto (>90%)
        ];

        // Determinar el intervalo apropiado
        foreach ($thresholds as $threshold => $interval) {
            if ($disk_percent >= $threshold) {
                return $interval;
            }
        }

        // Si está por debajo de todos los umbrales, no enviar notificación
        return PHP_INT_MAX;
    }

    /**
     * Gestiona el proceso de notificación del uso de disco.
     *
     * @return bool
     */
    private function notify_disk_usage()
    {
        global $DB, $CFG;
        
        // Obtener configuraciones del plugin
        $reportconfig = get_config('report_usage_monitor');
        
        // Calcular uso de disco y porcentaje
        $quotadisk = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $disk_usage = ((int) $reportconfig->totalusagereadable + (int) $reportconfig->totalusagereadabledb) ?: 0;
        $disk_percent = calculate_threshold_percentage($disk_usage, $quotadisk);

        // Nivel de advertencia configurado (default: 90%)
        $warning_level = !empty($reportconfig->disk_warning_level) ? $reportconfig->disk_warning_level : 90;

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Cuota de disco: $quotadisk bytes, Uso de disco: $disk_usage bytes, Porcentaje: $disk_percent%");
            mtrace("Nivel de advertencia: $warning_level%");
        }

        // Verificar si el porcentaje supera el nivel de advertencia configurado
        if ($disk_percent < $warning_level) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("El uso del disco ($disk_percent%) está por debajo del nivel de advertencia ($warning_level%). No se enviará notificación.");
            }
            return true;
        }

        // Determinar si es necesario enviar notificación ahora
        $notification_interval = $this->calculate_notification_interval($disk_percent);
        $last_notification_time = get_config('report_usage_monitor', 'last_notificationdisk_time') ?: 0;
        $current_time = time();

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Intervalo de notificación: $notification_interval segundos, Última notificación: $last_notification_time");
            $time_since_last = $current_time - $last_notification_time;
            mtrace("Tiempo transcurrido desde la última notificación: $time_since_last segundos");
        }

        // Verificar si ha pasado suficiente tiempo desde la última notificación
        if ($current_time - $last_notification_time < $notification_interval) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("No ha pasado el intervalo de notificación.");
                $time_remaining = ($last_notification_time + $notification_interval) - $current_time;
                mtrace("Próxima notificación posible en: " . format_time($time_remaining));
            }
            return true;
        }

        // Llegados a este punto, debemos enviar la notificación
        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Enviando notificación de uso de disco...");
        }
        
        // Recopilar información adicional para la notificación
        $userAccessCount = $this->get_total_user_access_count();
        
        // Enviar email de notificación
        $result = email_notify_disk_limit($quotadisk, $disk_usage, $disk_percent, $userAccessCount);
        
        // Actualizar tiempo de última notificación
        if ($result) {
            set_config('last_notificationdisk_time', $current_time, 'report_usage_monitor');
            
            // Registrar la notificación en el historial
            $this->log_notification($disk_percent, $disk_usage, $quotadisk);
            
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Notificación enviada con éxito.");
            }
        } else {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Error al enviar la notificación por email.");
            }
        }
        
        return $result;
    }

    /**
     * Obtiene el conteo total de accesos de usuarios.
     * REFACTORIZADO para usar SQL más eficiente y consistente.
     * 
     * @return int Conteo de usuarios únicos del último día.
     */
    private function get_total_user_access_count()
    {
        global $DB;
        
        $sql = "SELECT COUNT(DISTINCT userid) 
                FROM {logstore_standard_log} 
                WHERE action = 'loggedin' 
                  AND timecreated > :start";
                  
        $params = ['start' => strtotime('-1 day')];
        
        return (int) $DB->get_field_sql($sql, $params);
    }
    
    /**
     * Registra información sobre la notificación enviada.
     * 
     * @param float $disk_percent Porcentaje de uso del disco.
     * @param int $disk_usage Uso del disco en bytes.
     * @param int $quotadisk Cuota de disco en bytes.
     * @return bool
     */
    private function log_notification($disk_percent, $disk_usage, $quotadisk)
    {
        global $DB;
        
        // Verificar si existe la tabla para almacenar el historial
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'disk';
            $record->percentage = $disk_percent;
            $record->value = $disk_usage;
            $record->threshold = $quotadisk;
            $record->timecreated = time();
            
            // Usar transacción para asegurar consistencia
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