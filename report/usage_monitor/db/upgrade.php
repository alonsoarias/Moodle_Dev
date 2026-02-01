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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.

/**
 * Definición de pasos de actualización del complemento.
 *
 * @package     report_usage_monitor
 * @category    upgrade
 * @copyright   2023 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Actualiza el complemento report_usage_monitor.
 *
 * @param int $oldversion La versión antigua del complemento
 * @return bool
 */
function xmldb_report_usage_monitor_upgrade($oldversion)
{
    global $DB, $CFG;
    require_once($CFG->dirroot . '/report/usage_monitor/locallib.php');
    $dbman = $DB->get_manager();

    // Función de actualización.
    if ($oldversion < 2022090200) {
        // Define la tabla report_usage_monitor que se creará.
        $table = new xmldb_table('report_usage_monitor');

        // Agrega campos a la tabla report_usage_monitor.
        // Cambiado a XMLDB_TYPE_INTEGER para almacenar timestamps correctamente
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('fecha', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('cantidad_usuarios', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // Agrega claves a la tabla report_usage_monitor.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Crea la tabla report_usage_monitor de forma condicional.
        try {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        } catch (Exception $e) {
            echo "Error generado: $e";
        }

        // Punto de guardado de la versión 2022090200.
        upgrade_plugin_savepoint(true, 2022090200, 'report', 'usage_monitor');
    }

    if ($oldversion < 2022103100) {
        // Verificar primero el tipo de datos en la columna fecha
        // para evitar errores al intentar convertir timestamps existentes
        $records = $DB->get_records('report_usage_monitor', [], '', 'id, fecha');
        $needs_conversion = false;
        
        foreach ($records as $record) {
            // Si hay algún registro con fecha en formato de texto (no numérico),
            // necesitamos hacer la conversión
            if (!is_numeric($record->fecha) && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $record->fecha)) {
                $needs_conversion = true;
                break;
            }
        }
        
        if ($needs_conversion) {
            // Iniciamos una transacción para asegurar la consistencia de datos
            $transaction = $DB->start_delegated_transaction();
            try {
                // Se actualiza el campo fecha en el informe report_usage_monitor de tipo fecha a timestamp.
                $sql = "UPDATE {report_usage_monitor} set fecha=(UNIX_TIMESTAMP(STR_TO_DATE(fecha, '%d/%m/%Y')))";
                $DB->execute($sql);
                $transaction->allow_commit();
            } catch (Exception $e) {
                $transaction->rollback($e);
                throw $e;
            }
        }
        
        // Asegurarnos de que el campo fecha sea INTEGER
        $table = new xmldb_table('report_usage_monitor');
        $field = new xmldb_field('fecha', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_type($table, $field);
        }
        
        // Punto de guardado de la versión 2022103100.
        upgrade_plugin_savepoint(true, 2022103100, 'report', 'usage_monitor');
    }
    
    // Nueva actualización para corregir manejo de fechas
    if ($oldversion < 2025030403) {
        // Asegurarse de que la columna fecha en report_usage_monitor sea INTEGER
        $table = new xmldb_table('report_usage_monitor');
        
        // Identificar índices comunes por convención de nombres
        $indices = [
            'idx_fecha' => new xmldb_index('idx_fecha', XMLDB_INDEX_NOTUNIQUE, ['fecha']),
            'mdl_repousagmoni_fec_ix' => new xmldb_index('mdl_repousagmoni_fec_ix', XMLDB_INDEX_NOTUNIQUE, ['fecha'])
        ];
        
        // Eliminar todos los índices relacionados con fecha
        foreach ($indices as $indexname => $index) {
            if ($dbman->index_exists($table, $index)) {
                $dbman->drop_index($table, $index);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Eliminado índice $indexname en tabla report_usage_monitor");
                }
            }
        }
        
        // Ahora cambiar el tipo de campo
        $field = new xmldb_field('fecha', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        if ($dbman->field_exists($table, $field)) {
            try {
                $dbman->change_field_type($table, $field);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Cambiado tipo de campo fecha a INTEGER en tabla report_usage_monitor");
                }
            } catch (Exception $e) {
                debugging("Error al cambiar el tipo de campo: " . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        
        // Recrear el índice principal
        $index = new xmldb_index('idx_fecha', XMLDB_INDEX_NOTUNIQUE, ['fecha']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
            if (debugging('', DEBUG_DEVELOPER)) {
                mtrace("Recreado índice idx_fecha en tabla report_usage_monitor");
            }
        }
        
        // Verificar y corregir cualquier dato inválido en la tabla
        $transaction = $DB->start_delegated_transaction();
        try {
            // Encontrar registros con fechas no numéricas o inválidas
            $records = $DB->get_records_sql("SELECT id, fecha FROM {report_usage_monitor} WHERE fecha IS NULL OR fecha <= 0");
            
            foreach ($records as $record) {
                // Usar la fecha actual como fallback para cualquier fecha inválida
                $DB->set_field('report_usage_monitor', 'fecha', time(), ['id' => $record->id]);
                if (debugging('', DEBUG_DEVELOPER)) {
                    mtrace("Actualizado registro con ID {$record->id} con fecha inválida {$record->fecha} a timestamp actual");
                }
            }
            
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging("Error al corregir fechas inválidas: " . $e->getMessage(), DEBUG_DEVELOPER);
        }

        // Verificar y actualizar datos existentes para asegurar que sean timestamps UNIX válidos
        $transaction = $DB->start_delegated_transaction();
        try {
            // Obtener todos los registros para verificación
            $allrecords = $DB->get_records('report_usage_monitor');
            foreach ($allrecords as $record) {
                // Si la fecha parece ser un formato o no un timestamp válido
                if (!is_numeric($record->fecha) || $record->fecha < 946684800) { // 01/01/2000
                    // Intentar convertir formatos de fecha comunes a timestamp
                    $timestamp = strtotime(date('Y-m-d', time())); // Fallback a hoy
                    $DB->set_field('report_usage_monitor', 'fecha', $timestamp, ['id' => $record->id]);
                    
                    if (debugging('', DEBUG_DEVELOPER)) {
                        mtrace("Actualizado registro con ID {$record->id}, fecha {$record->fecha} a timestamp {$timestamp}");
                    }
                }
            }
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
            debugging("Error al actualizar fechas: " . $e->getMessage(), DEBUG_DEVELOPER);
        }
        
        // Punto de guardado para la versión de corrección de fechas
        upgrade_plugin_savepoint(true, 2025030403, 'report', 'usage_monitor');
    }

    return true;
}

// Funciones adicionales en upgrade.php (sin cambios)
function upgrade_show_recommended_notification()
{
    global $OUTPUT;
    echo $OUTPUT->notification(
        get_string('pathtodurecommendation', 'report_usage_monitor'), // Mostramos un mensaje de recomendación. Este texto será traducido según el idioma del usuario.
        'info' // Se muestra una notificación informativa.
    );
    echo $OUTPUT->notification(
        get_string('pathtodunote', 'report_usage_monitor'), // Mostramos una nota adicional. Este texto será traducido según el idioma del usuario.
        'info' // Se muestra una notificación informativa.
    );
}

function upgrade_show_warning_notification()
{
    global $OUTPUT;
    echo $OUTPUT->notification(
        get_string('activateshellexec', 'report_usage_monitor'), // Mostramos un mensaje de advertencia. Este texto será traducido según el idioma del usuario.
        'warning' // Se muestra una notificación de advertencia.
    );
}