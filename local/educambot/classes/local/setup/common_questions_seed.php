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
 * Common student questions seed - Essential Q&A for immediate functionality.
 *
 * This seed contains the most frequently asked questions by students to ensure
 * the bot can answer basic questions immediately after installation.
 *
 * @package     local_educambot
 * @copyright   2025 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local\setup;

use stdClass;

/**
 * Seeds the most common student questions into rules table.
 */
class common_questions_seed {
    /**
     * Seeds common student questions as rules.
     *
     * @return array Statistics of seeded data
     */
    public static function seed(): array {
        global $DB;

        $rules = self::get_common_questions();
        $created = 0;
        $updated = 0;

        foreach ($rules as $ruledata) {
            // Use sql_compare_text for TEXT field comparison.
            $sql = "SELECT * FROM {local_educambot_rule} WHERE " .
                   $DB->sql_compare_text('pattern') . " = " . $DB->sql_compare_text(':pattern');
            $existing = $DB->get_record_sql($sql, ['pattern' => $ruledata['pattern']]);

            $record = new stdClass();
            $record->pattern = $ruledata['pattern'];
            $record->synonyms = $ruledata['synonyms'] ?? '';
            $record->keywords = $ruledata['keywords'] ?? '';
            $record->response = $ruledata['response'];
            $record->roles = $ruledata['roles'] ?? '';
            $record->contexts = $ruledata['contexts'] ?? '';
            $record->suggested = isset($ruledata['suggested']) ? $ruledata['suggested'] : 0;
            $record->enabled = 1;
            $record->timemodified = time();

            if ($existing) {
                $record->id = $existing->id;
                $DB->update_record('local_educambot_rule', $record);
                $updated++;
            } else {
                $record->timecreated = time();
                $DB->insert_record('local_educambot_rule', $record);
                $created++;
            }
        }

        // Purge cache.
        \cache::make('local_educambot', 'rules')->purge();

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($rules),
        ];
    }

    /**
     * Returns array of common student questions with comprehensive responses.
     *
     * @return array Array of rule data
     */
    protected static function get_common_questions(): array {
        return [
            // ASSIGNMENT/TAREA SUBMISSION QUESTIONS.
            [
                'pattern' => '¿Cómo enviar un trabajo?',
                'synonyms' => "¿Cómo entregar una tarea?\n¿Cómo subir una tarea?\n¿Cómo enviar una actividad?\n" .
                             "¿Cómo enviar assignment?\nHow to submit assignment?\nHow to upload assignment?\n" .
                             "¿Cómo entregar un trabajo?",
                'keywords' => 'enviar,entregar,subir,trabajo,tarea,assignment,submit,upload',
                'response' => '<h4>📤 Cómo Enviar/Entregar un Trabajo (Tarea)</h4>
<p>Para enviar o entregar una tarea en Moodle, sigue estos pasos:</p>
<ol>
<li><strong>Accede a tu curso</strong> desde el Dashboard o "Mis cursos"</li>
<li><strong>Localiza la tarea</strong> en la sección correspondiente del curso</li>
<li><strong>Haz clic en el nombre de la tarea</strong> para abrirla</li>
<li><strong>Haz clic en "Agregar entrega"</strong> o "Add submission"</li>
<li><strong>Sube tu archivo</strong>:
   <ul>
   <li>Arrastra el archivo a la zona indicada, o</li>
   <li>Haz clic en "Agregar" y selecciona el archivo desde tu computadora</li>
   </ul>
</li>
<li><strong>Revisa tu archivo</strong> antes de enviar</li>
<li><strong>Haz clic en "Guardar cambios"</strong></li>
<li><strong>Confirma el envío</strong> haciendo clic en "Enviar tarea" (este paso es IMPORTANTE)</li>
</ol>

<p><strong>💡 Consejos importantes:</strong></p>
<ul>
<li>✅ Verifica la <strong>fecha límite</strong> de entrega en la descripción de la tarea</li>
<li>✅ Asegúrate de hacer clic en <strong>"Enviar tarea"</strong> (no solo "Guardar cambios")</li>
<li>✅ Después de enviar, verás un mensaje de <strong>confirmación</strong></li>
<li>✅ Puedes <strong>reenviar</strong> si el profesor lo permite y la fecha límite no ha pasado</li>
<li>✅ Formatos aceptados comunes: PDF, Word, JPG, PNG, ZIP</li>
</ul>

<p><strong>⚠️ Si tienes problemas:</strong></p>
<ul>
<li>Verifica que el archivo no sea demasiado grande (usualmente máx 20-50 MB)</li>
<li>Asegúrate de estar usando un formato permitido</li>
<li>Intenta con otro navegador (Chrome, Firefox, Edge)</li>
<li>Contacta a tu profesor si persisten los problemas</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/mod/assign/\n/course/view.php',
                'suggested' => 1,
            ],

            // GRADES/CALIFICACIONES QUESTIONS.
            [
                'pattern' => '¿Cómo ver mis calificaciones?',
                'synonyms' => "¿Dónde veo mis notas?\n¿Cómo ver mis notas?\n¿Cómo ver mi calificación?\n" .
                             "Where can I see my grades?\nHow to check my grades?\n¿Dónde están mis calificaciones?",
                'keywords' => 'ver,calificaciones,notas,grades,puntuación,score',
                'response' => '<h4>📊 Cómo Ver tus Calificaciones/Notas</h4>
<p>Hay dos formas principales de ver tus calificaciones en Moodle:</p>

<h5>Opción 1: Desde el Dashboard (Recomendado)</h5>
<ol>
<li>Ve a tu <strong>Dashboard</strong> (Tablero) o página de inicio</li>
<li>Busca el bloque de <strong>"Calificaciones"</strong> en la columna derecha</li>
<li>Haz clic en <strong>"Ver todas las calificaciones"</strong></li>
<li>Verás un resumen de calificaciones de todos tus cursos</li>
</ol>

<h5>Opción 2: Desde dentro del curso</h5>
<ol>
<li>Entra al curso específico</li>
<li>En el menú lateral izquierdo, busca <strong>"Calificaciones"</strong> o "Grades"</li>
<li>Haz clic para ver el informe de calificaciones del curso</li>
</ol>

<p><strong>💡 ¿Qué puedes ver?</strong></p>
<ul>
<li>✅ Calificaciones de <strong>tareas entregadas</strong></li>
<li>✅ Calificaciones de <strong>cuestionarios/exámenes</strong></li>
<li>✅ Calificaciones de <strong>foros</strong> (si se califican)</li>
<li>✅ <strong>Promedio o total</strong> del curso (si está configurado)</li>
<li>✅ <strong>Retroalimentación</strong> del profesor (comentarios)</li>
</ul>

<p><strong>⚠️ Notas importantes:</strong></p>
<ul>
<li>Las calificaciones aparecen <strong>después de que el profesor las publica</strong></li>
<li>Algunas actividades pueden estar <strong>ocultas</strong> hasta cierta fecha</li>
<li>Si no ves calificaciones, puede que el profesor no las haya publicado aún</li>
<li>El <strong>promedio final</strong> depende de la configuración del profesor</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/grade/\n/course/view.php',
                'suggested' => 1,
            ],

            // COURSE ACCESS QUESTIONS.
            [
                'pattern' => '¿Cómo acceder a un curso?',
                'synonyms' => "¿Cómo entrar a un curso?\n¿Cómo abrir un curso?\nNo puedo entrar al curso\n" .
                             "How to access a course?\nHow to enter a course?\nCannot access course",
                'keywords' => 'acceder,entrar,abrir,curso,course,acceso,access',
                'response' => '<h4>🚪 Cómo Acceder a un Curso</h4>
<p>Para acceder a tus cursos en Moodle:</p>

<h5>Método 1: Desde "Mis cursos"</h5>
<ol>
<li>Inicia sesión en Moodle</li>
<li>En el menú superior, haz clic en <strong>"Mis cursos"</strong> o "My courses"</li>
<li>Verás la lista de todos tus cursos activos</li>
<li>Haz clic en el nombre del curso al que quieres acceder</li>
</ol>

<h5>Método 2: Desde el Dashboard</h5>
<ol>
<li>Ve a tu <strong>Dashboard</strong> (página de inicio)</li>
<li>Verás tarjetas con tus cursos</li>
<li>Haz clic en la tarjeta del curso que necesites</li>
</ol>

<p><strong>⚠️ Si no puedes acceder al curso:</strong></p>

<h5>Posibles problemas y soluciones:</h5>
<ul>
<li><strong>No estás inscrito:</strong>
   <ul>
   <li>Verifica con tu profesor o administrador</li>
   <li>Puede que necesites una clave de inscripción</li>
   </ul>
</li>
<li><strong>El curso está oculto:</strong>
   <ul>
   <li>Los profesores pueden ocultar cursos temporalmente</li>
   <li>Contacta al profesor para verificar la disponibilidad</li>
   </ul>
</li>
<li><strong>El curso tiene fecha de inicio futura:</strong>
   <ul>
   <li>Algunos cursos no se pueden acceder hasta su fecha de inicio</li>
   <li>Verifica las fechas del curso</li>
   </ul>
</li>
<li><strong>Problemas de permisos:</strong>
   <ul>
   <li>Contacta al soporte técnico o administrador del sitio</li>
   </ul>
</li>
</ul>

<p><strong>💡 Consejo:</strong> Si es tu primer acceso a un curso, puede tomar unos minutos aparecer en "Mis cursos" después de inscribirte.</p>',
                'roles' => 'student',
                'contexts' => '/course/view.php\n/my/',
                'suggested' => 1,
            ],

            // PASSWORD RESET.
            [
                'pattern' => '¿Cómo cambiar mi contraseña?',
                'synonyms' => "¿Cómo resetear mi contraseña?\n¿Olvidé mi contraseña?\n¿Cómo recuperar mi contraseña?\n" .
                             "How to change password?\nForgot password\nHow to reset password?\nPassword recovery",
                'keywords' => 'cambiar,resetear,recuperar,contraseña,password,olvidé,forgot,reset',
                'response' => '<h4>🔑 Cambiar o Recuperar Contraseña</h4>

<h5>Si olvidaste tu contraseña:</h5>
<ol>
<li>Ve a la página de <strong>inicio de sesión</strong> de Moodle</li>
<li>Haz clic en <strong>"¿Olvidó su nombre de usuario o contraseña?"</strong></li>
<li>Ingresa tu <strong>nombre de usuario o correo electrónico</strong></li>
<li>Haz clic en <strong>"Buscar"</strong></li>
<li>Revisa tu <strong>correo electrónico</strong></li>
<li>Sigue el enlace en el correo para <strong>restablecer tu contraseña</strong></li>
<li>Ingresa tu <strong>nueva contraseña</strong> (dos veces para confirmar)</li>
<li>Haz clic en <strong>"Guardar cambios"</strong></li>
</ol>

<h5>Si recuerdas tu contraseña actual y quieres cambiarla:</h5>
<ol>
<li>Inicia sesión en Moodle</li>
<li>Haz clic en tu <strong>nombre/foto de perfil</strong> (esquina superior derecha)</li>
<li>Selecciona <strong>"Preferencias"</strong> o "Preferences"</li>
<li>Haz clic en <strong>"Cambiar contraseña"</strong></li>
<li>Ingresa tu <strong>contraseña actual</strong></li>
<li>Ingresa tu <strong>nueva contraseña</strong> (dos veces)</li>
<li>Haz clic en <strong>"Guardar cambios"</strong></li>
</ol>

<p><strong>⚠️ Requisitos de contraseña segura:</strong></p>
<ul>
<li>Mínimo 8 caracteres (algunos sitios requieren más)</li>
<li>Incluir mayúsculas y minúsculas</li>
<li>Incluir números</li>
<li>Incluir caracteres especiales (!@#$%)</li>
<li>No usar contraseñas obvias (123456, password, etc.)</li>
</ul>

<p><strong>💡 Si no recibes el correo de recuperación:</strong></p>
<ul>
<li>Revisa la carpeta de <strong>Spam/Correo no deseado</strong></li>
<li>Verifica que usaste el <strong>correo correcto</strong></li>
<li>Espera unos minutos (puede demorar)</li>
<li>Contacta al <strong>soporte técnico</strong> si persiste el problema</li>
</ul>',
                'roles' => 'student,teacher',
                'contexts' => '/login/\n/user/profile.php',
                'suggested' => 1,
            ],

            // FORUM QUESTIONS.
            [
                'pattern' => '¿Cómo participar en un foro?',
                'synonyms' => "¿Cómo responder en un foro?\n¿Cómo publicar en un foro?\n¿Cómo escribir en foro?\n" .
                             "How to post in forum?\nHow to reply in forum?\nHow to participate in forum?",
                'keywords' => 'participar,responder,publicar,foro,forum,post,reply,mensaje',
                'response' => '<h4>💬 Cómo Participar en un Foro</h4>

<h5>Para crear un nuevo tema/discusión:</h5>
<ol>
<li>Entra al curso y localiza el <strong>foro</strong></li>
<li>Haz clic en el nombre del foro</li>
<li>Haz clic en <strong>"Añadir un nuevo tema de discusión"</strong></li>
<li>Escribe un <strong>asunto/título</strong> descriptivo</li>
<li>Escribe tu <strong>mensaje</strong> en el editor</li>
<li>Opcionalmente, adjunta <strong>archivos</strong> si es necesario</li>
<li>Haz clic en <strong>"Enviar al foro"</strong></li>
</ol>

<h5>Para responder a un tema existente:</h5>
<ol>
<li>Abre el foro</li>
<li>Haz clic en el <strong>tema/hilo</strong> que quieres leer</li>
<li>Lee los mensajes existentes</li>
<li>Haz clic en <strong>"Responder"</strong> al final del mensaje</li>
<li>Escribe tu respuesta</li>
<li>Haz clic en <strong>"Enviar al foro"</strong></li>
</ol>

<p><strong>💡 Buenas prácticas en foros:</strong></p>
<ul>
<li>✅ Usa <strong>títulos descriptivos</strong> para tus temas</li>
<li>✅ <strong>Lee las respuestas anteriores</strong> antes de publicar</li>
<li>✅ Sé <strong>respetuoso</strong> con tus compañeros y profesores</li>
<li>✅ Usa un <strong>lenguaje apropiado</strong> y profesional</li>
<li>✅ <strong>Cita o menciona</strong> a quien respondes si es necesario</li>
<li>✅ Revisa la <strong>ortografía</strong> antes de publicar</li>
<li>❌ No uses MAYÚSCULAS todo el tiempo (se considera gritar)</li>
<li>❌ No hagas spam o publiques contenido irrelevante</li>
</ul>

<p><strong>🔔 Suscripciones:</strong></p>
<ul>
<li>Puedes <strong>suscribirte</strong> a un foro para recibir notificaciones por correo</li>
<li>Haz clic en "Suscribirme a este foro" para activar notificaciones</li>
<li>Puedes cancelar la suscripción en cualquier momento</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/mod/forum/',
                'suggested' => 1,
            ],

            // QUIZ/EXAM QUESTIONS.
            [
                'pattern' => '¿Cómo hacer un cuestionario?',
                'synonyms' => "¿Cómo contestar un examen?\n¿Cómo hacer un quiz?\n¿Cómo responder cuestionario?\n" .
                             "How to take a quiz?\nHow to answer exam?\nHow to do questionnaire?",
                'keywords' => 'hacer,contestar,responder,cuestionario,quiz,examen,test',
                'response' => '<h4>📝 Cómo Realizar un Cuestionario/Examen</h4>

<h5>Pasos para realizar un cuestionario:</h5>
<ol>
<li>Entra al curso</li>
<li>Localiza el <strong>cuestionario/quiz</strong> en la sección correspondiente</li>
<li>Haz clic en el nombre del cuestionario</li>
<li>Lee las <strong>instrucciones</strong> cuidadosamente:
   <ul>
   <li>Tiempo límite disponible</li>
   <li>Número de intentos permitidos</li>
   <li>Fecha límite de entrega</li>
   <li>Calificación máxima</li>
   </ul>
</li>
<li>Haz clic en <strong>"Intentar resolver el cuestionario ahora"</strong></li>
<li>Responde las preguntas:
   <ul>
   <li>Algunas requieren selección múltiple</li>
   <li>Otras pueden pedir respuestas escritas</li>
   <li>Algunas pueden tener verdadero/falso</li>
   </ul>
</li>
<li><strong>Navega</strong> entre preguntas usando los botones de navegación</li>
<li>Cuando termines, haz clic en <strong>"Terminar intento"</strong></li>
<li><strong>Revisa</strong> tus respuestas antes de enviar (si es posible)</li>
<li>Haz clic en <strong>"Enviar todo y terminar"</strong></li>
<li><strong>Confirma</strong> el envío en el diálogo que aparece</li>
</ol>

<p><strong>⏱️ Sobre el tiempo límite:</strong></p>
<ul>
<li>Si hay tiempo límite, verás un <strong>cronómetro</strong> en pantalla</li>
<li>El cuestionario se <strong>enviará automáticamente</strong> al terminar el tiempo</li>
<li><strong>Guarda tus respuestas</strong> periódicamente (Moodle lo hace automáticamente)</li>
<li>No cierres la ventana ni navegues a otra página durante el intento</li>
</ul>

<p><strong>💡 Consejos importantes:</strong></p>
<ul>
<li>✅ Lee <strong>todas las instrucciones</strong> antes de comenzar</li>
<li>✅ Administra tu <strong>tiempo</strong> si hay límite</li>
<li>✅ Responde <strong>todas las preguntas</strong> (marca algo si no estás seguro)</li>
<li>✅ Usa la función de <strong>"Marcar pregunta"</strong> para revisar después</li>
<li>✅ Revisa tus respuestas antes de enviar</li>
<li>✅ Asegúrate de tener <strong>conexión estable a internet</strong></li>
<li>✅ Cierra <strong>otras aplicaciones</strong> para evitar problemas</li>
</ul>

<p><strong>⚠️ Si tienes problemas técnicos:</strong></p>
<ul>
<li>Toma una <strong>captura de pantalla</strong> del error</li>
<li>Anota la <strong>hora exacta</strong> del problema</li>
<li>Contacta <strong>inmediatamente</strong> a tu profesor</li>
<li>No cierres el navegador hasta tener instrucciones</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/mod/quiz/',
                'suggested' => 1,
            ],

            // PROFILE/SETTINGS.
            [
                'pattern' => '¿Cómo cambiar mi foto de perfil?',
                'synonyms' => "¿Cómo subir foto de perfil?\n¿Cómo cambiar mi avatar?\n¿Cómo actualizar mi imagen?\n" .
                             "How to change profile picture?\nHow to upload profile photo?\nChange avatar",
                'keywords' => 'cambiar,subir,foto,perfil,profile,picture,avatar,imagen',
                'response' => '<h4>👤 Cómo Cambiar tu Foto de Perfil</h4>
<ol>
<li>Haz clic en tu <strong>nombre o foto actual</strong> (esquina superior derecha)</li>
<li>Selecciona <strong>"Perfil"</strong> o "Profile"</li>
<li>Haz clic en <strong>"Editar perfil"</strong> o "Edit profile"</li>
<li>Busca la sección <strong>"Imagen de usuario"</strong> o "User picture"</li>
<li>Haz clic en <strong>"Elija un archivo"</strong> o arrastra tu imagen</li>
<li>Selecciona una imagen desde tu computadora:
   <ul>
   <li>Formatos aceptados: JPG, PNG, GIF</li>
   <li>Tamaño recomendado: máximo 2 MB</li>
   <li>Dimensiones ideales: 100x100 píxeles (cuadrada)</li>
   </ul>
</li>
<li>Si es necesario, <strong>recorta</strong> la imagen usando la herramienta</li>
<li>Baja hasta el final de la página</li>
<li>Haz clic en <strong>"Actualizar información personal"</strong></li>
</ol>

<p><strong>💡 Consejos para una buena foto de perfil:</strong></p>
<ul>
<li>✅ Usa una imagen <strong>clara y profesional</strong></li>
<li>✅ Preferiblemente una <strong>foto tuya</strong> (no logos o dibujos)</li>
<li>✅ Asegúrate de que tu <strong>rostro sea visible</strong></li>
<li>✅ Usa <strong>buena iluminación</strong></li>
<li>✅ Fondo <strong>neutro</strong> si es posible</li>
<li>❌ Evita imágenes ofensivas o inapropiadas</li>
<li>❌ No uses imágenes protegidas por derechos de autor</li>
</ul>

<p><strong>⚠️ Nota:</strong> Algunos sitios Moodle pueden requerir aprobación del administrador para cambios de foto de perfil.</p>',
                'roles' => 'student,teacher',
                'contexts' => '/user/profile.php\n/user/edit.php',
                'suggested' => 1,
            ],

            // MESSAGING.
            [
                'pattern' => '¿Cómo enviar un mensaje a mi profesor?',
                'synonyms' => "¿Cómo contactar a mi profesor?\n¿Cómo escribir al profesor?\n¿Cómo mandar mensaje al teacher?\n" .
                             "How to message teacher?\nHow to contact professor?\nSend message to instructor",
                'keywords' => 'enviar,mandar,mensaje,profesor,teacher,contactar,escribir,message',
                'response' => '<h4>✉️ Cómo Enviar Mensaje a tu Profesor</h4>

<h5>Método 1: Desde el perfil del profesor</h5>
<ol>
<li>Entra al curso</li>
<li>Busca la sección <strong>"Participantes"</strong> en el menú lateral</li>
<li>Busca a tu profesor en la lista</li>
<li>Haz clic en su <strong>nombre</strong></li>
<li>En su perfil, haz clic en <strong>"Enviar mensaje"</strong> o icono de sobre</li>
<li>Escribe tu mensaje</li>
<li>Haz clic en <strong>"Enviar mensaje"</strong></li>
</ol>

<h5>Método 2: Desde mensajería directa</h5>
<ol>
<li>Haz clic en el <strong>icono de mensajes</strong> (sobre o chat) en la barra superior</li>
<li>Haz clic en <strong>"Ver todos"</strong> o el ícono de más</li>
<li>Haz clic en <strong>"+ Nuevo mensaje"</strong></li>
<li>Busca el <strong>nombre de tu profesor</strong></li>
<li>Selecciónalo de la lista</li>
<li>Escribe tu mensaje</li>
<li>Presiona Enter o haz clic en <strong>enviar</strong></li>
</ol>

<p><strong>💡 Consejos para escribir a tu profesor:</strong></p>
<ul>
<li>✅ Usa un <strong>saludo formal</strong>: "Estimado/a Profesor/a [Nombre]"</li>
<li>✅ Sé <strong>claro y conciso</strong> en tu mensaje</li>
<li>✅ Incluye el <strong>nombre del curso</strong> si el profesor tiene varios</li>
<li>✅ Usa <strong>buena ortografía y gramática</strong></li>
<li>✅ Sé <strong>respetuoso</strong> y profesional</li>
<li>✅ Incluye tu <strong>nombre completo</strong> si no es obvio</li>
<li>✅ Despídete apropiadamente: "Saludos", "Gracias"</li>
</ul>

<p><strong>⏰ Tiempo de respuesta:</strong></p>
<ul>
<li>Los profesores usualmente responden en <strong>24-48 horas hábiles</strong></li>
<li>No esperes respuestas inmediatas, especialmente fuera de horario laboral</li>
<li>Si es urgente, menciona "Urgente:" en el asunto si es posible</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/message/\n/user/profile.php',
                'suggested' => 1,
            ],

            // DOWNLOAD MATERIALS.
            [
                'pattern' => '¿Cómo descargar materiales del curso?',
                'synonyms' => "¿Cómo bajar archivos del curso?\n¿Cómo descargar recursos?\n¿Cómo obtener materiales?\n" .
                             "How to download course materials?\nHow to get course files?\nDownload resources",
                'keywords' => 'descargar,bajar,obtener,materiales,archivos,recursos,download,files',
                'response' => '<h4>📥 Cómo Descargar Materiales del Curso</h4>

<h5>Descargar archivos individuales:</h5>
<ol>
<li>Entra al curso</li>
<li>Navega a la <strong>sección</strong> donde está el material</li>
<li>Busca el archivo que necesitas (PDF, Word, presentación, etc.)</li>
<li>Haz clic en el <strong>nombre del archivo</strong></li>
<li>El archivo se abrirá o descargará automáticamente</li>
<li>Si se abre en el navegador y quieres guardarlo:
   <ul>
   <li>Haz clic derecho → <strong>"Guardar como..."</strong></li>
   <li>O usa Ctrl+S (Windows) / Cmd+S (Mac)</li>
   </ul>
</li>
</ol>

<h5>Descargar carpetas completas:</h5>
<ol>
<li>Si el profesor ha compartido una <strong>carpeta</strong></li>
<li>Haz clic en el nombre de la carpeta</li>
<li>Busca la opción <strong>"Descargar carpeta"</strong> (si está disponible)</li>
<li>Se descargará un archivo <strong>ZIP</strong> con todo el contenido</li>
<li>Descomprime el ZIP en tu computadora</li>
</ol>

<p><strong>💡 Tipos de recursos descargables:</strong></p>
<ul>
<li>📄 Documentos: PDF, Word, Excel, PowerPoint</li>
<li>🖼️ Imágenes: JPG, PNG, GIF</li>
<li>🎥 Videos: MP4, AVI (o enlaces a YouTube/Vimeo)</li>
<li>🔊 Audio: MP3, WAV</li>
<li>📦 Comprimidos: ZIP, RAR</li>
<li>📝 Otros: TXT, CSV, etc.</li>
</ul>

<p><strong>⚠️ Si no puedes descargar:</strong></p>
<ul>
<li><strong>El recurso puede estar restringido:</strong>
   <ul>
   <li>Verifica fechas de disponibilidad</li>
   <li>Contacta al profesor si crees que deberías tener acceso</li>
   </ul>
</li>
<li><strong>Problemas de permisos:</strong>
   <ul>
   <li>Asegúrate de estar inscrito en el curso</li>
   <li>Verifica que hayas iniciado sesión</li>
   </ul>
</li>
<li><strong>Problemas técnicos:</strong>
   <ul>
   <li>Intenta con otro navegador</li>
   <li>Desactiva bloqueadores de pop-ups</li>
   <li>Verifica tu conexión a internet</li>
   </ul>
</li>
</ul>

<p><strong>📱 Desde dispositivos móviles:</strong></p>
<ul>
<li>Los archivos se descargan a tu carpeta de "Descargas"</li>
<li>Puede que necesites una app específica para abrir ciertos formatos</li>
<li>La app oficial de Moodle facilita la descarga de materiales</li>
</ul>',
                'roles' => 'student',
                'contexts' => '/course/view.php\n/mod/resource/',
                'suggested' => 1,
            ],
        ];
    }
}
