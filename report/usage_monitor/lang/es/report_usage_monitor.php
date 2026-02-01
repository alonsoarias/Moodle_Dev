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
 * Las cadenas de complementos se definen aquí.
 *
 * @package     report_usage_monitor
 * @category    string
 * @copyright   2025 Soporte IngeWeb <soporte@ingeweb.co>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general strings
$string['pluginname'] = 'Usage Report';
$string['reportinfotext'] = 'Este plugin ha sido creado para otro caso de éxito de <strong>IngeWeb</strong>. Visítenos en <a target="_blank" href="http://ingeweb.co/">IngeWeb - Soluciones para triunfar en Internet</a>.';
$string['exclusivedisclaimer'] = 'Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a target="_blank" href="http://ingeweb.co/">IngeWeb</a>.';

// Plugin status strings
$string['pluginstatus'] = 'Estado del Plugin';
$string['pluginstatus_enabled'] = 'Plugin Habilitado';
$string['pluginstatus_enabled_desc'] = 'El plugin está activo y funcionando en un servidor autorizado.';
$string['pluginstatus_unauthorized'] = 'Servidor No Autorizado';
$string['pluginstatus_unauthorized_desc'] = 'El <strong>Usage Monitor</strong> es una herramienta exclusiva para plataformas Moodle administradas por <a href="https://ingeweb.co" target="_blank">IngeWeb</a>. Si estás interesado en este servicio, <a href="https://ingeweb.co/contacto" target="_blank">contáctanos</a>.';

// Dashboard strings
$string['dashboard'] = 'Panel de Control';
$string['dashboard_title'] = 'Dashboard de Usage Monitor';
$string['diskusage'] = 'Uso del disco';
$string['users_today_card'] = 'Usuarios Diarios Hoy';
$string['max_userdaily_for_90_days'] = 'Máximo de usuarios diarios en los últimos 90 días';
$string['notcalculatedyet'] = 'Aún no calculado';
$string['lastexecution'] = 'Última ejecución de cálculo de usuarios diarios: {$a}';
$string['lastexecutioncalculate'] = 'Último cálculo de espacio en disco: {$a}';
$string['users_today'] = 'Cantidad de usuarios diarios el día de hoy: {$a}';
$string['date'] = 'Fecha';
$string['last_calculation'] = 'Último cálculo';
$string['usersquantity'] = 'Cantidad de usuarios diarios';
$string['disk_usage_distribution'] = 'Distribución de Uso de Disco';
$string['disk_usage_history'] = 'Historial de Uso de Disco (Últimos 30 Días)';
$string['percentage_used'] = 'Porcentaje Utilizado';

// Dashboard sections
$string['disk_usage_by_directory'] = 'Uso de Disco por Directorio';
$string['largest_courses'] = 'Cursos más Grandes';
$string['database'] = 'Base de datos';
$string['files_dir'] = 'Archivos (filedir)';
$string['cache'] = 'Caché';
$string['others'] = 'Otros';
$string['directory'] = 'Directorio';
$string['size'] = 'Tamaño';
$string['percentage'] = 'Porcentaje';
$string['course'] = 'Curso';
$string['backup_count'] = 'Número de Copias';
$string['topuser'] = 'Top 10 usuarios diarios';
$string['lastusers'] = 'Usuarios diarios de los últimos 10 días';
$string['usertable'] = 'Tabla de top usuarios';
$string['userchart'] = 'Graficar top usuarios';
$string['system_info'] = 'Información del Sistema';
$string['moodle_version'] = 'Versión de Moodle';
$string['total_courses'] = 'Total de Cursos';
$string['backup_per_course'] = 'Copias de Seguridad por Curso';
$string['registered_users'] = 'Usuarios Registrados';
$string['active_users'] = 'usuarios activos';
$string['suspended_users'] = 'usuarios suspendidos';
$string['recommendations'] = 'Recomendaciones';

// Warning levels and indicator labels
$string['warning70'] = 'Advertencia (70%)';
$string['critical90'] = 'Crítico (90%)';
$string['limit100'] = 'Límite (100%)';
$string['percent_of_threshold'] = '% del umbral';

// Recommendation tips
$string['space_saving_tips'] = 'Consejos para ahorrar espacio en disco:';
$string['tip_backups'] = 'Reducir el número de copias de seguridad automáticas por curso (actualmente: {$a})';
$string['tip_files'] = 'Limpiar archivos antiguos sin uso mediante la herramienta de limpieza de archivos';
$string['tip_courses'] = 'Archivar o eliminar cursos antiguos que ya no se utilizan';
$string['tip_cache'] = 'Purgar la caché del sistema para liberar espacio temporal';
$string['disk_usage_ok'] = 'El uso del disco está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_count_ok'] = 'El recuento de usuarios está en un nivel aceptable. No se requiere acción inmediata.';
$string['user_limit_tips'] = 'Consejos para gestionar el límite de usuarios:';
$string['tip_user_inactive'] = 'Considere limpiar las cuentas de usuario inactivas que no han iniciado sesión durante mucho tiempo.';
$string['tip_user_limit'] = 'Si el número de usuarios se acerca constantemente al límite, considere aumentar su cuota.';

// Task strings
$string['calculatediskusagetask'] = 'Tarea para calcular el uso del disco';
$string['getlastusers'] = 'Tarea para calcular el top de accesos unicos';
$string['getlastusers90days'] = 'Tarea para obtener el top de usuarios en los últimos 90 días';
$string['getlastusersconnected'] = 'Tarea para calcular la cantidad de usuarios diarios de hoy';
$string['processdisknotificationtask'] = 'Tarea de notificación del uso del disco';
$string['processuserlimitnotificationtask'] = 'Tarea de notificación del límite de usuarios diarios';

// Settings strings
$string['mainsettings'] = 'Configuraciones principales';
$string['email'] = 'Email para notificaciones';
$string['configemail'] = 'Dirección de correo donde desea enviar las notificaciones.';
$string['max_daily_users_threshold'] = 'Límite de usuarios';
$string['configmax_daily_users_threshold'] = 'Establezca el límite de usuarios.';
$string['disk_quota'] = 'Cuota de disco';
$string['configdisk_quota'] = 'Cuota de disco en gigabytes';
$string['notificationsettings'] = 'Configuración de notificaciones';
$string['notificationsettingsinfo'] = 'Configure cuándo y cómo se envían las notificaciones.';
$string['disk_warning_level'] = 'Nivel de advertencia de disco';
$string['configdisk_warning_level'] = 'Porcentaje de uso de disco que activa las advertencias.';
$string['users_warning_level'] = 'Nivel de advertencia de usuarios';
$string['configusers_warning_level'] = 'Porcentaje del límite de usuarios que activa las advertencias.';
$string['pathtodu'] = 'Ruta al comando du';
$string['configpathtodu'] = 'Configura la ruta al comando du (uso de disco). Esto es necesario para calcular el uso de disco. <strong>Este ajuste se refleja en las rutas del sistema de Moodle</strong>)';
$string['pathtodurecommendation'] = 'Recomendamos que revise y configure la ruta a \'du\' en las Rutas del sistema de Moodle. Puede encontrar esta configuración en Administración del sitio > Servidor > Rutas del sistema. <a target="_blank" href="settings.php?section=systempaths#id_s__pathtodu">Haga clic aquí para ir a Rutas del sistema</a>.';
$string['pathtodunote'] = 'Nota: El path a \'du\' se detectará automáticamente solo si este plugin se encuentra en un sistema Linux y si se logra detectar la ubicación de \'du\'.';
$string['activateshellexec'] = 'La función shell_exec no está activa en este servidor. Para utilizar la detección automática del camino a du, debes habilitar shell_exec en la configuración de tu servidor.';

// Email notification strings
$string['subjectemail1'] = 'Límite de usuarios diarios superado plataforma:';
$string['subjectemail2'] = 'Alerta de espacio en disco plataforma:';

// API documentation strings
$string['api_documentation'] = 'Documentación de API';
$string['get_usage_data'] = 'Obtener datos de uso';
$string['get_usage_data_desc'] = 'Recupera datos precalculados de uso de disco y usuarios con mínima sobrecarga.';
$string['set_usage_thresholds'] = 'Configurar umbrales de uso';
$string['set_usage_thresholds_desc'] = 'Actualiza los umbrales configurados para usuarios y espacio en disco.';
$string['user_threshold_updated'] = 'Umbral de usuarios actualizado correctamente.';
$string['disk_threshold_updated'] = 'Umbral de disco actualizado correctamente.';
$string['error_user_threshold_negative'] = 'El umbral de usuarios debe ser mayor que 0.';
$string['error_disk_threshold_negative'] = 'El umbral de disco debe ser mayor que 0.';
$string['error_no_thresholds_provided'] = 'No se proporcionaron umbrales para actualizar.';

// Plugin status strings
$string['plugin_disabled_hostname'] = '<div class="text-center">
<h4 class="mb-3 text-danger"><i class="fa fa-exclamation-triangle"></i> Servidor No Autorizado</h4>
<p class="mb-0">Este plugin hace parte y es de uso exclusivo del servicio de hosting para Moodle proporcionado por <a href="https://ingeweb.co" target="_blank"><strong>IngeWeb</strong></a>.</p>
</div>';
$string['tasks_scheduled_install'] = 'Las tareas programadas se han configurado para ejecutarse inmediatamente. El dashboard mostrará datos actualizados después de la próxima ejecución del cron.';
$string['tasks_executing'] = 'Ejecutando tareas para obtener datos iniciales del dashboard...';
$string['tasks_executed_success'] = 'Todas las tareas se ejecutaron correctamente. El dashboard ahora muestra datos actualizados.';
$string['tasks_executed_partial'] = 'Se ejecutaron {$a} tareas. Algunas tareas pueden haber fallado, pero el dashboard debería mostrar datos parciales.';

// API response field descriptions
$string['server_hostname'] = 'Hostname del servidor';
$string['site_name'] = 'Nombre del sitio';
$string['site_shortname'] = 'Nombre corto del sitio';
$string['moodle_release'] = 'Versión de Moodle legible';
$string['course_count'] = 'Número de cursos';
$string['user_count'] = 'Número de usuarios';
$string['backup_auto_max_kept'] = 'Número de copias automáticas conservadas';
$string['total_bytes'] = 'Uso total de disco en bytes';
$string['total_readable'] = 'Uso de disco legible';
$string['quota_bytes'] = 'Cuota de disco en bytes';
$string['quota_readable'] = 'Cuota de disco legible';
$string['disk_percentage'] = 'Porcentaje de uso de disco';
$string['database_bytes'] = 'Tamaño de la base de datos en bytes';
$string['database_readable'] = 'Tamaño legible de la base de datos';
$string['database_percentage'] = 'Porcentaje de tamaño de la base de datos';
$string['filedir_bytes'] = 'Tamaño del directorio de archivos en bytes';
$string['filedir_readable'] = 'Tamaño legible del directorio de archivos';
$string['filedir_percentage'] = 'Porcentaje de tamaño del directorio de archivos';
$string['cache_bytes'] = 'Tamaño de caché en bytes';
$string['cache_readable'] = 'Tamaño legible de caché';
$string['cache_percentage'] = 'Porcentaje de caché';
$string['backup_bytes'] = 'Tamaño de copia de seguridad en bytes';
$string['backup_readable'] = 'Tamaño legible de copia de seguridad';
$string['backup_percentage'] = 'Porcentaje de copia de seguridad';
$string['others_bytes'] = 'Tamaño de otros directorios en bytes';
$string['others_readable'] = 'Tamaño legible de otros directorios';
$string['others_percentage'] = 'Porcentaje de otros directorios';
$string['user_threshold'] = 'Umbral de usuarios';
$string['user_percentage'] = 'Porcentaje de uso de usuarios';
$string['course_id'] = 'ID del curso';
$string['course_fullname'] = 'Nombre completo del curso';
$string['course_shortname'] = 'Nombre corto del curso';
$string['course_size_bytes'] = 'Tamaño del curso en bytes';
$string['course_size_readable'] = 'Tamaño legible del curso';
$string['course_backup_size_bytes'] = 'Tamaño de la copia de seguridad del curso en bytes';
$string['course_backup_size_readable'] = 'Tamaño legible de la copia de seguridad del curso';
$string['course_percentage'] = 'Porcentaje de tamaño del curso';
$string['course_backup_count'] = 'Número de copias de seguridad del curso';
$string['disk_calculation_timestamp'] = 'Timestamp del cálculo de disco';
$string['users_calculation_timestamp'] = 'Timestamp del cálculo de usuarios';

// Notification history API strings
$string['notification_type'] = 'Tipo de notificación (disk, users, o all)';
$string['notification_limit'] = 'Número máximo de registros a devolver';
$string['notification_offset'] = 'Desplazamiento para paginación';
$string['notification_total'] = 'Número total de registros disponibles';
$string['notification_limit_value'] = 'Número máximo de registros solicitados';
$string['notification_offset_value'] = 'Desplazamiento solicitado';
$string['notification_id'] = 'ID de la notificación';
$string['notification_type_value'] = 'Tipo de notificación (disk o users)';
$string['notification_percentage'] = 'Porcentaje de uso';
$string['notification_value'] = 'Valor legible';
$string['notification_value_raw'] = 'Valor en bytes o número de usuarios';
$string['notification_threshold'] = 'Umbral legible';
$string['notification_threshold_raw'] = 'Umbral en bytes o número de usuarios';
$string['notification_timecreated'] = 'Timestamp de creación';
$string['notification_timereadable'] = 'Fecha y hora legibles';

// Projections and growth rates
$string['api_projections_title'] = 'Proyecciones de crecimiento';
$string['api_projections_desc'] = 'Datos de proyección de crecimiento y días estimados para alcanzar umbrales';
$string['api_monthly_growth_rate'] = 'Tasa de crecimiento mensual';
$string['api_projection_days'] = 'Días para alcanzar umbral';
$string['growth_rate_disk'] = 'Tasa de crecimiento de disco';
$string['growth_rate_disk_desc'] = 'Tasa de crecimiento mensual del uso de disco en porcentaje';
$string['growth_rate_users'] = 'Tasa de crecimiento de usuarios';
$string['growth_rate_users_desc'] = 'Tasa de crecimiento mensual del número de usuarios en porcentaje';
$string['days_to_threshold_disk'] = 'Días hasta umbral de disco';
$string['days_to_threshold_disk_desc'] = 'Días proyectados hasta alcanzar el umbral de advertencia de disco';
$string['days_to_threshold_users'] = 'Días hasta umbral de usuarios';
$string['days_to_threshold_users_desc'] = 'Días proyectados hasta alcanzar el umbral de advertencia de usuarios';

// Email templates
$string['messagehtml_userlimit'] = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Límite de Usuarios - {$a->sitename}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #cccccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dddddd;
            font-size: 13px;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary-table td:first-child {
            width: 40%;
            font-weight: bold;
        }
        .alert-box {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .alert-box.critical {
            border-left: 4px solid #cc0000;
        }
        .alert-box.warning {
            border-left: 4px solid #ff9900;
        }
        .link-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #333333;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #cccccc;
            font-size: 11px;
            color: #666666;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Notificación de Límite de Usuarios Diarios</h1>
            <p>Plataforma: {$a->sitename} | Fecha: {$a->lastday}</p>
        </div>

        <div class="alert-box critical">
            <strong>Estado:</strong> Se ha superado el límite de usuarios diarios. El uso actual es del {$a->percentaje}% del umbral configurado.
        </div>

        <div class="section">
            <div class="section-title">Resumen</div>
            <table class="summary-table">
                <tr>
                    <td>Usuarios Activos Hoy</td>
                    <td>{$a->numberofusers}</td>
                </tr>
                <tr>
                    <td>Límite Configurado</td>
                    <td>{$a->threshold} usuarios</td>
                </tr>
                <tr>
                    <td>Porcentaje de Uso</td>
                    <td>{$a->percentaje}%</td>
                </tr>
                <tr>
                    <td>Usuarios Sobre el Límite</td>
                    <td>{$a->excess_users}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Información de la Plataforma</div>
            <table class="summary-table">
                <tr>
                    <td>Versión de Moodle</td>
                    <td>{$a->moodle_release}</td>
                </tr>
                <tr>
                    <td>Total de Cursos</td>
                    <td>{$a->courses_count}</td>
                </tr>
                <tr>
                    <td>Uso de Disco</td>
                    <td>{$a->diskusage} / {$a->quotadisk} ({$a->disk_percent}%)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Historial Reciente de Usuarios</div>
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Usuarios Activos</th>
                        <th>% del Límite</th>
                    </tr>
                </thead>
                <tbody>
                    {$a->historical_data_rows}
                </tbody>
            </table>
        </div>

        <div class="section">
            <p>Para ver estadísticas detalladas y gestionar su plataforma, acceda al panel de control:</p>
            <a href="{$a->referer}" class="link-button">Acceder al Panel</a>
        </div>

        <div class="footer">
            <p>Esta es una notificación automática generada por el plugin Usage Monitor.</p>
            <p>Nota: Solo se contabilizan usuarios únicos que se autenticaron en la fecha indicada.</p>
            <p>URL de la plataforma: <a href="{$a->siteurl}">{$a->siteurl}</a></p>
        </div>
    </div>
</body>
</html>';

$string['messagehtml_diskusage'] = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación de Espacio en Disco - {$a->sitename}</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 14px;
            line-height: 1.5;
            color: #333333;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #333333;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
            color: #333333;
        }
        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            color: #666666;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #333333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #cccccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th, table td {
            padding: 8px 10px;
            text-align: left;
            border: 1px solid #dddddd;
            font-size: 13px;
        }
        table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .summary-table td:first-child {
            width: 40%;
            font-weight: bold;
        }
        .alert-box {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .alert-box.critical {
            border-left: 4px solid #cc0000;
        }
        .alert-box.warning {
            border-left: 4px solid #ff9900;
        }
        .recommendations {
            padding: 10px 15px;
            margin-bottom: 15px;
            border: 1px solid #cccccc;
            background-color: #f9f9f9;
        }
        .recommendations ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .recommendations li {
            margin-bottom: 5px;
        }
        .link-button {
            display: inline-block;
            padding: 8px 16px;
            background-color: #333333;
            color: #ffffff;
            text-decoration: none;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #cccccc;
            font-size: 11px;
            color: #666666;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Notificación de Espacio en Disco</h1>
            <p>Plataforma: {$a->sitename} | Fecha: {$a->lastday}</p>
        </div>

        <div class="alert-box {$a->warning_level_class}">
            <strong>Estado:</strong> El uso de disco ha alcanzado el {$a->percentage}% de la cuota asignada.
        </div>

        <div class="section">
            <div class="section-title">Resumen de Uso de Disco</div>
            <table class="summary-table">
                <tr>
                    <td>Espacio Utilizado</td>
                    <td>{$a->diskusage}</td>
                </tr>
                <tr>
                    <td>Cuota Asignada</td>
                    <td>{$a->quotadisk}</td>
                </tr>
                <tr>
                    <td>Espacio Disponible</td>
                    <td>{$a->available_space} ({$a->available_percent}%)</td>
                </tr>
                <tr>
                    <td>Porcentaje de Uso</td>
                    <td>{$a->percentage}%</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Distribución del Almacenamiento</div>
            <table>
                <thead>
                    <tr>
                        <th>Componente</th>
                        <th>Tamaño</th>
                        <th>Porcentaje</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Base de datos</td>
                        <td>{$a->databasesize}</td>
                        <td>{$a->db_percent}%</td>
                    </tr>
                    <tr>
                        <td>Archivos (filedir)</td>
                        <td>{$a->filedir_size}</td>
                        <td>{$a->filedir_percent}%</td>
                    </tr>
                    <tr>
                        <td>Caché</td>
                        <td>{$a->cache_size}</td>
                        <td>{$a->cache_percent}%</td>
                    </tr>
                    <tr>
                        <td>Otros</td>
                        <td>{$a->other_size}</td>
                        <td>{$a->other_percent}%</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Información de la Plataforma</div>
            <table class="summary-table">
                <tr>
                    <td>Versión de Moodle</td>
                    <td>{$a->moodle_release}</td>
                </tr>
                <tr>
                    <td>Total de Cursos</td>
                    <td>{$a->coursescount}</td>
                </tr>
                <tr>
                    <td>Copias por Curso</td>
                    <td>{$a->backupcount}</td>
                </tr>
                <tr>
                    <td>Usuarios Activos</td>
                    <td>{$a->numberofusers} / {$a->threshold} ({$a->user_percent}%)</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="section-title">Cursos con Mayor Uso de Espacio</div>
            <table>
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Tamaño</th>
                        <th>% del Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$a->top_courses_rows}
                </tbody>
            </table>
        </div>

        <div class="recommendations">
            <strong>Recomendaciones:</strong>
            <ul>
                <li>Reducir copias automáticas por curso (actualmente: {$a->backupcount})</li>
                <li>Eliminar archivos sin uso mediante la herramienta de limpieza</li>
                <li>Revisar y limpiar los cursos más grandes listados arriba</li>
                <li>Purgar la caché del sistema para liberar espacio temporal</li>
            </ul>
        </div>

        <div class="section">
            <p>Para ver estadísticas detalladas y gestionar su plataforma, acceda al panel de control:</p>
            <a href="{$a->referer}" class="link-button">Acceder al Panel</a>
        </div>

        <div class="footer">
            <p>Esta es una notificación automática generada por el plugin Usage Monitor.</p>
            <p>Para asistencia técnica, contacte a su administrador de hosting.</p>
            <p>URL de la plataforma: <a href="{$a->siteurl}">{$a->siteurl}</a></p>
        </div>
    </div>
</body>
</html>';