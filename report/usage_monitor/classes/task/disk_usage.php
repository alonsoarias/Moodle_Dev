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
 * Scheduled task to calculate disk usage statistics.
 *
 * This task calculates the total disk usage of the Moodle installation,
 * including the database, dataroot, and dirroot directories. It stores
 * the results in plugin configuration and history table for trending.
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace report_usage_monitor\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Disk usage calculation scheduled task.
 *
 * Calculates and stores disk usage statistics including:
 * - Database size
 * - Dataroot directory size
 * - Dirroot directory size
 * - Usage by subdirectory (filedir, cache, etc.)
 * - Largest courses by file storage
 * - Growth rate calculations
 *
 * @package     report_usage_monitor
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class disk_usage extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string The name of the task.
     */
    public function get_name(): string {
        return get_string('calculatediskusagetask', 'report_usage_monitor');
    }

    /**
     * Execute the disk usage calculation task.
     *
     * This method performs the following operations:
     * 1. Calculates database size using multi-database compatible SQL
     * 2. Calculates dataroot and dirroot directory sizes
     * 3. Computes usage percentage against configured quota
     * 4. Analyzes usage by subdirectory
     * 5. Identifies largest courses
     * 6. Records history for trend analysis
     * 7. Calculates growth rate
     *
     * @return bool True on success.
     */
    public function execute(): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');

        // Check if plugin is enabled (hostname validation).
        if (!report_usage_monitor_is_enabled()) {
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Plugin deshabilitado: hostname no autorizado o desactivado por configuración.");
            }
            return true; // Return true to not mark task as failed.
        }

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Iniciando tarea de cálculo de uso del disco...");
        }

        // Calcular el tamaño de la base de datos con la función refactorizada.
        // size_database() devuelve directamente el tamaño en bytes.
        $totalusagereadabledb = size_database();
        set_config('totalusagereadabledb', $totalusagereadabledb, 'report_usage_monitor');

        // Calcular el tamaño del directorio dataroot
        $totalusagedataroot = directory_size($CFG->dataroot);

        // Calcular el tamaño del directorio dirroot
        $totalusagedirroot = directory_size($CFG->dirroot);

        // Calcular el total del uso del disco legible
        $totalusagereadable = $totalusagedataroot + $totalusagedirroot;
        set_config('totalusagereadable', $totalusagereadable, 'report_usage_monitor');
        
        // Obtener umbrales y calcular porcentaje de uso
        $reportconfig = get_config('report_usage_monitor');
        $quotadisk_bytes = ((int) $reportconfig->disk_quota * 1024) * 1024 * 1024;
        $total_disk_usage = $totalusagereadable + $totalusagereadabledb;
        
        // Precomputar el porcentaje y la clase de advertencia
        $disk_percent = calculate_threshold_percentage($total_disk_usage, $quotadisk_bytes);
        $disk_warning_class = ($disk_percent < 70) ? 'bg-success' : (($disk_percent < 90) ? 'bg-warning' : 'bg-danger');
        
        // Guardar valores precomputados
        set_config('disk_percent', $disk_percent, 'report_usage_monitor');
        set_config('disk_warning_class', $disk_warning_class, 'report_usage_monitor');
        set_config('disk_usage_gb', display_size_in_gb($total_disk_usage, 2), 'report_usage_monitor');
        set_config('quotadisk_gb', display_size_in_gb($quotadisk_bytes, 2), 'report_usage_monitor');
        
        // Análisis por directorios con la función refactorizada
        $dir_analysis = analyze_disk_usage_by_directory($CFG->dataroot);
        
        // Asegurarse de que el valor de 'database' sea coherente con el calculado anteriormente
        $dir_analysis['database'] = $totalusagereadabledb;
        
        // Guardar los resultados detallados como JSON en la configuración
        set_config('dir_analysis', json_encode($dir_analysis), 'report_usage_monitor');
        
        // Obtener y guardar los cursos más grandes con la función refactorizada
        $largest_courses = get_largest_courses(5);
        set_config('largest_courses', json_encode($largest_courses), 'report_usage_monitor');
        
        // Guardar timestamp de última ejecución
        $execution_time = time();
        set_config('lastexecutioncalculatedisk', $execution_time, 'report_usage_monitor');
        
        // Registrar en el historial para poder mostrar gráficas de tendencia
        if ($DB->get_manager()->table_exists('report_usage_monitor_history')) {
            $record = new \stdClass();
            $record->type = 'disk';
            $record->percentage = $disk_percent;
            $record->value = $total_disk_usage;
            $record->threshold = $quotadisk_bytes;
            $record->timecreated = $execution_time;
            
            try {
                $DB->insert_record('report_usage_monitor_history', $record);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Uso de disco registrado en el historial.");
                }
            } catch (\Exception $e) {
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Error al registrar el uso de disco: " . $e->getMessage());
                }
            }
        }

        // Calcular tasa de crecimiento para disco y guardarla
        $growth_rate = calculate_growth_rate('disk');
        $growth_history = !empty($reportconfig->disk_growth_history) ? 
                          json_decode($reportconfig->disk_growth_history, true) : [];

        // Añadir entrada actual a la historia de crecimiento
        $growth_history[] = [
            'timestamp' => $execution_time,
            'rate' => $growth_rate,
            'usage' => $total_disk_usage
        ];
        
        // Mantener solo las últimas 10 entradas
        if (count($growth_history) > 10) {
            $growth_history = array_slice($growth_history, -10);
        }
        
        // Guardar historial de crecimiento actualizado
        set_config('disk_growth_history', json_encode($growth_history), 'report_usage_monitor');

        if (debugging('', DEBUG_DEVELOPER)) {
            mtrace("Uso del disco calculado. Total base de datos: $totalusagereadabledb bytes, Total dataroot: $totalusagedataroot bytes, Total dirroot: $totalusagedirroot bytes, Total uso legible: $totalusagereadable bytes.");
            mtrace("Análisis por directorios completado y guardado en configuración.");
            mtrace("Información de cursos más grandes guardada en configuración.");
            mtrace("Tasa de crecimiento mensual calculada: $growth_rate%");
            mtrace("Tarea de cálculo de uso del disco completada.");
        }

        return true;
    }
}