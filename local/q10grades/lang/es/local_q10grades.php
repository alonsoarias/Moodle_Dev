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

/**
 * Spanish language strings for Q10 Grades Sync plugin.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Sincronización de Notas Q10';
$string['syncgrades'] = 'Sincronizar Notas Q10';
$string['privacy:metadata'] = 'El plugin de Sincronización de Notas Q10 envía datos de calificaciones al sistema externo Q10.';

// Capabilities.
$string['q10grades:sync'] = 'Sincronizar notas a Q10';
$string['q10grades:viewlogs'] = 'Ver registros de sincronización Q10';
$string['q10grades:configure'] = 'Configurar integración Q10';
$string['q10grades:managemapping'] = 'Gestionar mapeo de cursos a Q10';

// Settings.
$string['apisettings'] = 'Configuración de API';
$string['apisettings_desc'] = 'Configure la conexión a la API de Q10. Necesita obtener estas credenciales de su administrador de Q10.';
$string['apiurl'] = 'URL de la API';
$string['apiurl_desc'] = 'La URL base de la API de Q10 (ej: https://api.q10.com/v1)';
$string['apikey'] = 'Clave API';
$string['apikey_desc'] = 'Su clave API de Q10 (Client ID)';
$string['apisecret'] = 'Secreto API';
$string['apisecret_desc'] = 'Su secreto API de Q10 (Client Secret)';
$string['institutionid'] = 'ID de Institución';
$string['institutionid_desc'] = 'Su identificador de institución en Q10';

$string['syncsettings'] = 'Configuración de Sincronización';
$string['syncsettings_desc'] = 'Configure cómo se sincronizan las notas con Q10.';
$string['enableautosync'] = 'Habilitar sincronización automática';
$string['enableautosync_desc'] = 'Sincronizar automáticamente las notas a Q10 según un horario';
$string['syncfrequency'] = 'Frecuencia de sincronización';
$string['syncfrequency_desc'] = 'Con qué frecuencia sincronizar las notas automáticamente';
$string['hourly'] = 'Cada hora';
$string['every6hours'] = 'Cada 6 horas';
$string['every12hours'] = 'Cada 12 horas';
$string['daily'] = 'Diariamente';
$string['weekly'] = 'Semanalmente';

$string['usermappingfield'] = 'Campo de mapeo de usuarios';
$string['usermappingfield_desc'] = 'Qué campo de usuario de Moodle usar para identificar estudiantes en Q10';
$string['coursemappingfield'] = 'Campo de mapeo de cursos';
$string['coursemappingfield_desc'] = 'Qué campo de curso de Moodle usar para identificar materias en Q10';

$string['gradescale'] = 'Formato de calificación';
$string['gradescale_desc'] = 'Cómo formatear las calificaciones al enviar a Q10';
$string['percentage'] = 'Porcentaje (0-100)';
$string['rawgrade'] = 'Valor de nota sin procesar';
$string['lettergrade'] = 'Calificación con letras';

$string['debugmode'] = 'Modo depuración';
$string['debugmode_desc'] = 'Habilitar registro detallado para solucionar problemas de la API';

// Course mapping.
$string['coursemapping'] = 'Mapeo de Curso';
$string['coursemapping_desc'] = 'Mapee este curso de Moodle a una materia en Q10. Ingrese el ID de materia de Q10 y opcionalmente el período y grupo.';
$string['q10subjectid'] = 'ID de Materia Q10';
$string['q10subjectid_help'] = 'El identificador único de la materia en Q10';
$string['q10periodid'] = 'ID de Período Q10';
$string['q10periodid_help'] = 'El ID del período académico en Q10 (opcional)';
$string['q10groupid'] = 'ID de Grupo Q10';
$string['q10groupid_help'] = 'El ID del grupo/sección en Q10 (opcional)';
$string['enablesync'] = 'Habilitar sincronización para este curso';
$string['savemapping'] = 'Guardar mapeo';
$string['mappingsaved'] = 'Mapeo de curso guardado exitosamente';
$string['currentmapping'] = 'Mapeo Actual';
$string['editmapping'] = 'Editar mapeo';
$string['configuremapping'] = 'Configurar mapeo';

// Formulas.
$string['formulas'] = 'Fórmulas de Calificación';
$string['formulas_desc'] = 'Configure fórmulas para combinar múltiples actividades de Moodle en una sola nota para Q10. Cada fórmula se mapea a un componente de evaluación de Q10.';
$string['addformula'] = 'Agregar fórmula';
$string['editformula'] = 'Editar fórmula';
$string['deleteformula'] = 'Eliminar fórmula';
$string['deleteformulaconfirm'] = '¿Está seguro de que desea eliminar la fórmula "{$a}"?';
$string['formuladeleted'] = 'Fórmula eliminada exitosamente';
$string['formulacreated'] = 'Fórmula creada exitosamente';
$string['formulaupdated'] = 'Fórmula actualizada exitosamente';
$string['noformulas'] = 'No hay fórmulas configuradas. Cree una fórmula para mapear actividades de Moodle a componentes de calificación de Q10.';
$string['configuredformulas'] = 'Fórmulas Configuradas';

$string['formulaname'] = 'Nombre de la fórmula';
$string['formulatype'] = 'Tipo de cálculo';
$string['formulaaverage'] = 'Promedio simple';
$string['formulaweighted'] = 'Promedio ponderado';
$string['formulasum'] = 'Suma';
$string['formulahighest'] = 'Nota más alta';
$string['formulalowest'] = 'Nota más baja';
$string['formulacustom'] = 'Fórmula personalizada';
$string['enableformula'] = 'Habilitar esta fórmula';
$string['saveformula'] = 'Guardar fórmula';

$string['selectactivities'] = 'Seleccionar Actividades';
$string['selectedactivities'] = 'Actividades seleccionadas';
$string['gradeitems'] = 'Ítems de calificación';
$string['noactivities'] = 'No se encontraron actividades en este curso';
$string['selectatleastone'] = 'Por favor seleccione al menos una actividad';

$string['itemweights'] = 'Pesos de Ítems';
$string['itemweights_desc'] = 'Asigne pesos a cada ítem seleccionado para el cálculo del promedio ponderado.';

$string['customformula'] = 'Fórmula personalizada';
$string['customformula_help'] = 'Ingrese una fórmula matemática personalizada. Use [[item_ID]] para referenciar ítems de calificación o [[grade1]], [[grade2]], etc. para referencia posicional. Operadores soportados: + - * / ()';

$string['q10componentid'] = 'ID de Componente Q10';
$string['q10componentid_help'] = 'El ID del componente de evaluación en Q10 donde se cargará esta calificación';

// Upload/Sync.
$string['uploadgrades'] = 'Cargar Notas';
$string['uploadtoq10'] = 'Cargar Notas a Q10';
$string['uploadconfirm'] = '¿Está seguro de que desea cargar las notas a Q10? Esta acción no se puede deshacer.';
$string['uploadcomplete'] = 'Carga completada: {$a->uploaded} notas cargadas, {$a->errors} errores.';
$string['gradepreview'] = 'Vista Previa de Notas';
$string['gradepreview_desc'] = 'Revise las notas calculadas antes de cargarlas a Q10.';
$string['previewgrades'] = 'Vista previa de notas';
$string['nostudentgrades'] = 'No hay notas de estudiantes disponibles para cargar.';
$string['formuladetails'] = 'Detalles de Fórmulas';
$string['student'] = 'Estudiante';
$string['notset'] = 'No configurado';
$string['nostudentid'] = 'El estudiante {$a} no tiene un ID de Q10 mapeado.';

// History.
$string['synchistory'] = 'Historial de Sincronización';
$string['nohistory'] = 'No hay historial de sincronización disponible.';
$string['syncstatistics'] = 'Estadísticas de Sincronización';
$string['totalsyncs'] = 'Total de sincronizaciones';
$string['successful'] = 'Exitosas';
$string['failed'] = 'Fallidas';
$string['lastsync'] = 'Última sincronización';
$string['never'] = 'Nunca';
$string['syncedby'] = 'Sincronizado por';
$string['pending'] = 'Pendiente';
$string['type'] = 'Tipo';
$string['synctype_manual'] = 'Manual';
$string['synctype_scheduled'] = 'Programada';
$string['synctype_realtime'] = 'Tiempo real';

// Grade item mapping.
$string['gradeitemsmapping'] = 'Mapeo de Ítems de Calificación';
$string['gradeitemsmapping_desc'] = 'Mapee ítems de calificación individuales de Moodle a componentes de evaluación de Q10.';
$string['nogradeitems'] = 'No se encontraron ítems de calificación en este curso.';
$string['savemappings'] = 'Guardar mapeos';
$string['selecteditems'] = 'Ítems seleccionados';
$string['nitems'] = '{$a} ítems';

// Quick actions.
$string['quickactions'] = 'Acciones Rápidas';

// Errors and warnings.
$string['apinotconfigured'] = 'La API de Q10 no está configurada.';
$string['configureapi'] = 'Configurar API';
$string['nomapping'] = 'Este curso no está mapeado a una materia de Q10.';
$string['nostudentmapping'] = 'El estudiante no tiene un mapeo de ID de Q10.';
$string['connectionsuccessful'] = '¡Conexión exitosa a la API de Q10!';
$string['authfailed'] = 'Fallo de autenticación: {$a}';
$string['apierror'] = 'Error de API: {$a}';
$string['invalidjsonresponse'] = 'Respuesta JSON inválida de la API de Q10';
$string['invalidhttpmethod'] = 'Método HTTP inválido: {$a}';

// Misc.
$string['enabled'] = 'Habilitado';
$string['disabled'] = 'Deshabilitado';
$string['unnamed'] = 'Sin nombre';

// Task.
$string['task_sync_grades'] = 'Sincronizar notas a Q10';
