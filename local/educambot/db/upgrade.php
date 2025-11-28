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
 * Plugin upgrade steps are defined here.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute local_educambot upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_local_educambot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Automatically generated Moodle v4.0.0 release upgrade line.
    // Put any upgrade step following this.

    if ($oldversion < 2025112004) {
        // Define table local_educambot_log to be created.
        $table = new xmldb_table('local_educambot_log');

        // Adding fields to table local_educambot_log.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('question', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('response', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('confidence', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null);
        $table->add_field('matched', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_educambot_log.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('userid_fk', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
        $table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_educambot_rule', ['id']);

        // Adding indexes to table local_educambot_log.
        // Note: userid and ruleid already have indexes from foreign keys.
        $table->add_index('matched_idx', XMLDB_INDEX_NOTUNIQUE, ['matched']);
        $table->add_index('timecreated_idx', XMLDB_INDEX_NOTUNIQUE, ['timecreated']);

        // Conditionally launch create table for local_educambot_log.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112004, 'local', 'educambot');
    }

    if ($oldversion < 2025112005) {
        // Add showoptions field to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');
        $field = new xmldb_field('showoptions', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'enabled');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define table local_educambot_option to be created.
        $table = new xmldb_table('local_educambot_option');

        // Adding fields to table local_educambot_option.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('ruleid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('text', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('targetruleid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        // Adding keys to table local_educambot_option.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('ruleid_fk', XMLDB_KEY_FOREIGN, ['ruleid'], 'local_educambot_rule', ['id']);
        $table->add_key('targetruleid_fk', XMLDB_KEY_FOREIGN, ['targetruleid'], 'local_educambot_rule', ['id']);

        // Adding indexes to table local_educambot_option.
        // Note: ruleid already has an index from foreign key.
        $table->add_index('sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table for local_educambot_option.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112005, 'local', 'educambot');
    }

    if ($oldversion < 2025112006) {
        // Define table local_educambot_category to be created.
        $table = new xmldb_table('local_educambot_category');

        // Adding fields to table local_educambot_category.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('parent', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_educambot_category.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('parent_fk', XMLDB_KEY_FOREIGN, ['parent'], 'local_educambot_category', ['id']);

        // Adding indexes to table local_educambot_category.
        // Note: parent already has an index from foreign key.
        $table->add_index('sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table for local_educambot_category.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add categoryid field to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');
        $field = new xmldb_field('categoryid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'id');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add categoryid index.
        $index = new xmldb_index('categoryid_idx', XMLDB_INDEX_NOTUNIQUE, ['categoryid']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Add tags field to local_educambot_rule table.
        $field = new xmldb_field('tags', XMLDB_TYPE_TEXT, null, null, null, null, null, 'response');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112006, 'local', 'educambot');
    }

    if ($oldversion < 2025112007) {
        // Version 1.6.1: Extended knowledge base + Startup suggestions.
        $now = time();

        // Add 'Recursos y Materiales' category if it doesn't exist.
        $recursoscat = $DB->get_record('local_educambot_category', ['name' => 'Recursos y Materiales']);
        if (!$recursoscat) {
            $recursoscat = new stdClass();
            $recursoscat->name = 'Recursos y Materiales';
            $recursoscat->description = 'Acceso a archivos, videos y materiales de estudio';
            $recursoscat->parent = null;
            $recursoscat->sortorder = 6;
            $recursoscat->enabled = 1;
            $recursoscat->timecreated = $now;
            $recursoscat->timemodified = $now;
            $recursoscat->id = $DB->insert_record('local_educambot_category', $recursoscat);
        }

        // Get existing category IDs.
        $catgeneral = $DB->get_record('local_educambot_category', ['name' => 'General']);
        $catcursos = $DB->get_record('local_educambot_category', ['name' => 'Cursos']);
        $cattareas = $DB->get_record('local_educambot_category', ['name' => 'Tareas y Actividades']);
        $catevaluaciones = $DB->get_record('local_educambot_category', ['name' => 'Evaluaciones']);
        $catperfil = $DB->get_record('local_educambot_category', ['name' => 'Perfil y Cuenta']);
        $catsoporte = $DB->get_record('local_educambot_category', ['name' => 'Soporte']);

        // Helper function to create rule if not exists.
        $createrule = function($pattern, $data) use ($DB, $now) {
            $existing = $DB->get_record('local_educambot_rule', ['pattern' => $pattern]);
            if (!$existing) {
                $data['timecreated'] = $now;
                $data['timemodified'] = $now;
                return $DB->insert_record('local_educambot_rule', (object)$data);
            }
            return $existing->id;
        };

        // Create startup rule (special rule for initial options).
        $startupid = $createrule('__startup__', [
            'categoryid' => $catgeneral ? $catgeneral->id : null,
            'pattern' => '__startup__',
            'keywords' => "__startup__\n__init__",
            'response' => 'Selecciona una de las opciones o escribe tu pregunta:',
            'tags' => 'inicio, startup, opciones',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        // Get existing rule IDs for linking options.
        $menuid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => 'Menu principal']);
        $assignmentid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => '¿Como entrego una tarea?']);
        $gradesid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => '¿Donde veo mis calificaciones?']);
        $quizid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => '¿Como hago un cuestionario o examen?']);
        $profileid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => '¿Como actualizo mi perfil?']);
        $supportid = $DB->get_field('local_educambot_rule', 'id', ['pattern' => '¿Como contacto con soporte tecnico?']);

        // Create new rules for v1.6.1.
        $mycoursesid = $createrule('¿Donde veo mis cursos?', [
            'categoryid' => $catcursos ? $catcursos->id : null,
            'pattern' => '¿Donde veo mis cursos?',
            'keywords' => "mis cursos\nver cursos\ncursos inscritos\npanel de cursos\nlista de cursos\nacceder a curso",
            'response' => 'Puedes ver tus cursos de varias formas:<br><br>1. <strong>Panel principal:</strong> Al iniciar sesion, veras el bloque "Mis cursos" en tu tablero<br>2. <strong>Menu de navegacion:</strong> Haz clic en "Mis cursos" en el menu superior o lateral<br>3. <strong>Perfil:</strong> En tu perfil puedes ver todos los cursos en los que estas inscrito',
            'tags' => 'cursos, panel, acceso',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $aboutbotid = $createrule('¿Quien eres?', [
            'categoryid' => $catgeneral ? $catgeneral->id : null,
            'pattern' => '¿Quien eres?',
            'keywords' => "quien eres\nque eres\neres un robot\neres humano\ncomo te llamas\ntu nombre",
            'response' => 'Soy Nexo Bot, un asistente virtual disenado para ayudarte a navegar y utilizar esta plataforma educativa Moodle. Puedo responder preguntas sobre cursos, tareas, calificaciones, tu perfil y mucho mas.',
            'tags' => 'bot, identidad, presentacion',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $goodbyeid = $createrule('Adios', [
            'categoryid' => $catgeneral ? $catgeneral->id : null,
            'pattern' => 'Adios',
            'keywords' => "adios\nhasta luego\nnos vemos\nchao\nbyte\nhasta pronto\nme voy",
            'response' => '¡Hasta pronto! Fue un placer ayudarte. Recuerda que estoy aqui cuando me necesites. ¡Exito en tus estudios!',
            'tags' => 'despedida, adios',
            'enabled' => 1,
            'showoptions' => 0,
        ]);

        $findcoursesid = $createrule('¿Como busco cursos disponibles?', [
            'categoryid' => $catcursos ? $catcursos->id : null,
            'pattern' => '¿Como busco cursos disponibles?',
            'keywords' => "buscar cursos\ncursos disponibles\ncatalogo de cursos\nencontrar curso\nver todos los cursos",
            'response' => 'Para buscar cursos disponibles:<br><br>1. Ve a "Todos los cursos" en el menu de navegacion<br>2. Usa el cuadro de busqueda para encontrar cursos por nombre<br>3. Navega por las categorias para explorar temas especificos<br>4. Haz clic en cualquier curso para ver su descripcion',
            'tags' => 'buscar, catalogo, explorar',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $coursekeyid = $createrule('¿Que es la clave de inscripcion?', [
            'categoryid' => $catcursos ? $catcursos->id : null,
            'pattern' => '¿Que es la clave de inscripcion?',
            'keywords' => "clave de inscripcion\nclave de curso\npassword del curso\ncontrasena de inscripcion",
            'response' => 'La clave de inscripcion es una contrasena que protege el acceso a ciertos cursos:<br><br>- <strong>¿Quien la proporciona?</strong> Tu profesor o coordinador te dara la clave<br>- <strong>¿Cuando se usa?</strong> Solo al inscribirte por primera vez<br>- <strong>¿Es lo mismo que tu contrasena?</strong> No, es diferente a tu contrasena de usuario',
            'tags' => 'clave, inscripcion, password, acceso',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $courseprogressid = $createrule('¿Como veo mi progreso en el curso?', [
            'categoryid' => $catcursos ? $catcursos->id : null,
            'pattern' => '¿Como veo mi progreso en el curso?',
            'keywords' => "progreso del curso\navance del curso\nporcentaje completado\nactividades completadas",
            'response' => 'Para ver tu progreso en un curso:<br><br>1. Entra al curso deseado<br>2. Busca la barra de progreso en la parte superior o lateral<br>3. Tambien puedes ir a "Informe de actividad" en el menu del curso',
            'tags' => 'progreso, avance, completado',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $certificateid = $createrule('¿Como obtengo mi certificado?', [
            'categoryid' => $catcursos ? $catcursos->id : null,
            'pattern' => '¿Como obtengo mi certificado?',
            'keywords' => "certificado\ndiploma\nconstancia\ncertificacion\nobtener certificado",
            'response' => 'Para obtener tu certificado:<br><br>1. Completa todas las actividades requeridas del curso<br>2. Asegurate de cumplir con los requisitos minimos de calificacion<br>3. Busca la actividad "Certificado" al final del curso<br>4. Haz clic para generar y descargar tu certificado en PDF',
            'tags' => 'certificado, diploma, constancia, finalizacion',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $editassignmentid = $createrule('¿Puedo modificar una tarea enviada?', [
            'categoryid' => $cattareas ? $cattareas->id : null,
            'pattern' => '¿Puedo modificar una tarea enviada?',
            'keywords' => "modificar tarea\neditar entrega\ncambiar archivo\nreenviar tarea",
            'response' => 'La posibilidad de modificar una tarea depende de la configuracion:<br><br>- <strong>Antes del cierre:</strong> Generalmente puedes editar tu entrega haciendo clic en "Editar entrega"<br>- <strong>Despues del cierre:</strong> Solo si el profesor habilita intentos adicionales',
            'tags' => 'editar, modificar, reenviar, tarea',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $latesubmissionid = $createrule('¿Puedo entregar una tarea tarde?', [
            'categoryid' => $cattareas ? $cattareas->id : null,
            'pattern' => '¿Puedo entregar una tarea tarde?',
            'keywords' => "entrega tardia\ntarea atrasada\nfuera de plazo\nextension\nprorroga",
            'response' => 'Las entregas tardias dependen de la configuracion del profesor:<br><br>- <strong>Entrega cerrada:</strong> No podras enviar despues de la fecha limite<br>- <strong>Con penalizacion:</strong> Puedes entregar pero con descuento<br>- <strong>Con extension:</strong> Algunos profesores otorgan extensiones individuales',
            'tags' => 'tardia, extension, prorroga, plazo',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $quizattemptsid = $createrule('¿Cuantos intentos tengo en el cuestionario?', [
            'categoryid' => $catevaluaciones ? $catevaluaciones->id : null,
            'pattern' => '¿Cuantos intentos tengo en el cuestionario?',
            'keywords' => "intentos cuestionario\nvolver a intentar\nreintentar examen\nnumero de intentos",
            'response' => 'El numero de intentos permitidos lo define el profesor:<br><br>1. Abre el cuestionario<br>2. Lee la informacion inicial que indica los intentos permitidos<br>3. En "Tus intentos previos" veras cuantos has usado',
            'tags' => 'intentos, reintentar, cuestionario',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $quizreviewid = $createrule('¿Puedo ver las respuestas correctas del examen?', [
            'categoryid' => $catevaluaciones ? $catevaluaciones->id : null,
            'pattern' => '¿Puedo ver las respuestas correctas del examen?',
            'keywords' => "respuestas correctas\nrevision examen\nver errores\nretroalimentacion quiz",
            'response' => 'La revision del cuestionario depende de la configuracion del profesor:<br><br>- Solo tu puntuacion<br>- Tus respuestas sin marcar las correctas<br>- Las respuestas correctas e incorrectas<br>- Retroalimentacion detallada',
            'tags' => 'revision, respuestas, retroalimentacion, examen',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $feedbackid = $createrule('¿Donde veo los comentarios de mi profesor?', [
            'categoryid' => $catevaluaciones ? $catevaluaciones->id : null,
            'pattern' => '¿Donde veo los comentarios de mi profesor?',
            'keywords' => "comentarios profesor\nretroalimentacion\nfeedback\nobservaciones",
            'response' => 'Para ver la retroalimentacion de tu profesor:<br><br><strong>En tareas:</strong><br>1. Ve a la tarea<br>2. Haz clic en "Ver envio" o "Estado de la entrega"<br>3. Busca la seccion "Retroalimentacion"',
            'tags' => 'retroalimentacion, feedback, comentarios',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $profilepicid = $createrule('¿Como cambio mi foto de perfil?', [
            'categoryid' => $catperfil ? $catperfil->id : null,
            'pattern' => '¿Como cambio mi foto de perfil?',
            'keywords' => "cambiar foto\nsubir imagen\navatar\nfoto de perfil",
            'response' => 'Para cambiar tu foto de perfil:<br><br>1. Ve a tu Perfil > Editar perfil<br>2. Busca la seccion "Imagen de usuario"<br>3. Haz clic en el area de la imagen o arrastra una foto<br>4. Ajusta el recorte si es necesario<br>5. Haz clic en "Actualizar perfil"',
            'tags' => 'foto, imagen, avatar, perfil',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $notificationsid = $createrule('¿Como configuro mis notificaciones?', [
            'categoryid' => $catperfil ? $catperfil->id : null,
            'pattern' => '¿Como configuro mis notificaciones?',
            'keywords' => "notificaciones\nalertas\navisos\ncorreos\nconfigurar alertas",
            'response' => 'Para configurar tus notificaciones:<br><br>1. Haz clic en tu foto de perfil<br>2. Ve a "Preferencias"<br>3. Selecciona "Preferencias de notificacion"<br>4. Para cada tipo elige: En linea o Correo',
            'tags' => 'notificaciones, alertas, correos, preferencias',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $downloadfilesid = $createrule('¿Como descargo los materiales del curso?', [
            'categoryid' => $recursoscat->id,
            'pattern' => '¿Como descargo los materiales del curso?',
            'keywords' => "descargar archivo\nbajar material\ndescargar pdf\nobtener documentos\nmateriales del curso",
            'response' => 'Para descargar materiales del curso:<br><br>1. Entra al curso<br>2. Localiza el recurso que deseas descargar<br>3. Haz clic en el nombre del archivo<br>4. El archivo se descargara o abrira en nueva pestana',
            'tags' => 'descargar, materiales, archivos, recursos',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $videosid = $createrule('¿Por que no puedo ver los videos?', [
            'categoryid' => $recursoscat->id,
            'pattern' => '¿Por que no puedo ver los videos?',
            'keywords' => "video no carga\nvideo no funciona\nno reproduce video\nproblemas con video",
            'response' => 'Si tienes problemas con los videos:<br><br>1. Actualiza la pagina (F5)<br>2. Revisa tu conexion a internet<br>3. Prueba otro navegador<br>4. Desactiva el bloqueador de anuncios<br>5. Limpia la cache del navegador',
            'tags' => 'video, reproduccion, problemas, multimedia',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $browserid = $createrule('¿Que navegador debo usar?', [
            'categoryid' => $catsoporte ? $catsoporte->id : null,
            'pattern' => '¿Que navegador debo usar?',
            'keywords' => "navegador\nbrowser\nchrome\nfirefox\nedge\nsafari",
            'response' => 'Moodle funciona mejor con navegadores actualizados:<br><br><strong>Recomendados:</strong><br>- Google Chrome (preferido)<br>- Mozilla Firefox<br>- Microsoft Edge<br>- Safari (Mac)',
            'tags' => 'navegador, browser, compatibilidad',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $mobileappid = $createrule('¿Puedo usar Moodle en mi celular?', [
            'categoryid' => $catsoporte ? $catsoporte->id : null,
            'pattern' => '¿Puedo usar Moodle en mi celular?',
            'keywords' => "celular\nmovil\napp\naplicacion\nsmartphone\nmoodle mobile",
            'response' => 'Si, puedes usar Moodle en tu dispositivo movil:<br><br>1. Descarga "Moodle" desde App Store o Google Play<br>2. Abre la app y escribe la URL de tu sitio Moodle<br>3. Inicia sesion con tus credenciales',
            'tags' => 'movil, app, celular, mobile',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $loginissuesid = $createrule('No puedo iniciar sesion', [
            'categoryid' => $catsoporte ? $catsoporte->id : null,
            'pattern' => 'No puedo iniciar sesion',
            'keywords' => "no puedo entrar\nlogin falla\nacceso denegado\nusuario bloqueado\nno me deja entrar",
            'response' => 'Si tienes problemas para iniciar sesion:<br><br>1. Verifica tu usuario (correo o ID asignado)<br>2. Revisa mayusculas/minusculas en contrasena<br>3. Asegurate de que Caps Lock este desactivado<br>4. Usa "¿Olvido su contrasena?" para recuperarla',
            'tags' => 'login, acceso, sesion, problemas',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        // Add startup options if the startup rule was created.
        if ($startupid && $mycoursesid) {
            // Check if startup options already exist.
            $existingoptions = $DB->count_records('local_educambot_option', ['ruleid' => $startupid]);
            if ($existingoptions == 0) {
                $startupoptions = [
                    ['ruleid' => $startupid, 'text' => 'Mis Cursos', 'targetruleid' => $mycoursesid, 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
                    ['ruleid' => $startupid, 'text' => 'Entregar Tarea', 'targetruleid' => $assignmentid, 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
                    ['ruleid' => $startupid, 'text' => 'Ver Calificaciones', 'targetruleid' => $gradesid, 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
                    ['ruleid' => $startupid, 'text' => 'Examenes', 'targetruleid' => $quizid, 'icon' => '✏️', 'sortorder' => 4, 'enabled' => 1],
                    ['ruleid' => $startupid, 'text' => 'Mi Perfil', 'targetruleid' => $profileid, 'icon' => '👤', 'sortorder' => 5, 'enabled' => 1],
                    ['ruleid' => $startupid, 'text' => 'Ayuda', 'targetruleid' => $supportid, 'icon' => '🆘', 'sortorder' => 6, 'enabled' => 1],
                ];

                foreach ($startupoptions as $opt) {
                    if ($opt['targetruleid']) {
                        $DB->insert_record('local_educambot_option', (object)$opt);
                    }
                }
            }
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112007, 'local', 'educambot');
    }

    return true;
}
