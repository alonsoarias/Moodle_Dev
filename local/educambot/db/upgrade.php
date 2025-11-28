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
        // Note: pattern is a TEXT field, must use sql_compare_text() for comparison.
        $createrule = function($pattern, $data) use ($DB, $now) {
            $sql = "SELECT * FROM {local_educambot_rule} WHERE " .
                   $DB->sql_compare_text('pattern') . " = " . $DB->sql_compare_text(':pattern');
            $existing = $DB->get_record_sql($sql, ['pattern' => $pattern]);
            if (!$existing) {
                $data['timecreated'] = $now;
                $data['timemodified'] = $now;
                return $DB->insert_record('local_educambot_rule', (object)$data);
            }
            return $existing->id;
        };

        // Helper function to get rule ID by pattern (using sql_compare_text for TEXT field).
        $getruleid = function($pattern) use ($DB) {
            $sql = "SELECT id FROM {local_educambot_rule} WHERE " .
                   $DB->sql_compare_text('pattern') . " = " . $DB->sql_compare_text(':pattern');
            return $DB->get_field_sql($sql, ['pattern' => $pattern]);
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

        // Get existing rule IDs for linking options (using helper function for TEXT comparison).
        $menuid = $getruleid('Menu principal');
        $assignmentid = $getruleid('¿Como entrego una tarea?');
        $gradesid = $getruleid('¿Donde veo mis calificaciones?');
        $quizid = $getruleid('¿Como hago un cuestionario o examen?');
        $profileid = $getruleid('¿Como actualizo mi perfil?');
        $supportid = $getruleid('¿Como contacto con soporte tecnico?');

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

        // Additional rules for complete knowledge base.
        $forumsubid = $createrule('¿Por que recibo tantos correos del foro?', [
            'categoryid' => $cattareas ? $cattareas->id : null,
            'pattern' => '¿Por que recibo tantos correos del foro?',
            'keywords' => "suscripcion foro\ncorreos foro\nnotificaciones foro\ndejar de recibir correos",
            'response' => 'Los correos del foro son por la suscripcion automatica. Para gestionarlos:<br><br>1. Ve al foro en cuestion<br>2. Busca el enlace "Suscribirse/Darse de baja del foro"<br>3. Haz clic para cancelar la suscripcion',
            'tags' => 'suscripcion, correos, notificaciones, foro',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $wikiid = $createrule('¿Como uso un wiki?', [
            'categoryid' => $cattareas ? $cattareas->id : null,
            'pattern' => '¿Como uso un wiki?',
            'keywords' => "wiki\neditar wiki\ncolaborar wiki\npagina wiki",
            'response' => 'Un Wiki es una herramienta colaborativa:<br><br><strong>Para editar:</strong><br>1. Abre la pagina del wiki<br>2. Haz clic en la pestana "Editar"<br>3. Modifica el contenido<br>4. Haz clic en "Guardar"',
            'tags' => 'wiki, colaborativo, editar, paginas',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $glossaryid = $createrule('¿Como agrego terminos al glosario?', [
            'categoryid' => $cattareas ? $cattareas->id : null,
            'pattern' => '¿Como agrego terminos al glosario?',
            'keywords' => "glosario\nagregar termino\ndefinicion\ndiccionario",
            'response' => 'Para agregar un termino al glosario:<br><br>1. Accede a la actividad Glosario<br>2. Haz clic en "Agregar entrada"<br>3. Escribe el concepto y la definicion<br>4. Haz clic en "Guardar cambios"',
            'tags' => 'glosario, termino, definicion, diccionario',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $gradeappealid = $createrule('¿Como reclamo una calificacion?', [
            'categoryid' => $catevaluaciones ? $catevaluaciones->id : null,
            'pattern' => '¿Como reclamo una calificacion?',
            'keywords' => "reclamar nota\napelacion\nrevision de nota\nno estoy de acuerdo",
            'response' => 'Si tienes dudas sobre una calificacion:<br><br>1. Revisa la rubrica o criterios de evaluacion<br>2. Lee la retroalimentacion del profesor<br>3. Contacta al profesor a traves de mensajeria<br>4. Se respetuoso y especifico en tu reclamo',
            'tags' => 'reclamacion, apelacion, revision, nota',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $languageid = $createrule('¿Como cambio el idioma?', [
            'categoryid' => $catperfil ? $catperfil->id : null,
            'pattern' => '¿Como cambio el idioma?',
            'keywords' => "idioma\nlenguaje\ncambiar idioma\ningles\nespanol",
            'response' => 'Para cambiar el idioma de la plataforma:<br><br>1. Haz clic en tu foto de perfil<br>2. Ve a "Preferencias"<br>3. Busca "Idioma preferido"<br>4. Selecciona el idioma deseado<br>5. Guarda los cambios',
            'tags' => 'idioma, lenguaje, preferencias',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $scormid = $createrule('¿Que es un paquete SCORM?', [
            'categoryid' => $recursoscat->id,
            'pattern' => '¿Que es un paquete SCORM?',
            'keywords' => "scorm\npaquete scorm\ncontenido interactivo\nmodulo scorm",
            'response' => 'SCORM es un formato de contenido interactivo de aprendizaje:<br><br>1. Haz clic en la actividad SCORM<br>2. Haz clic en "Entrar"<br>3. Navega usando los controles internos<br>4. Completa todas las secciones<br><br>Si no abre, desactiva el bloqueador de ventanas emergentes.',
            'tags' => 'scorm, interactivo, paquete, elearning',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        $h5pid = $createrule('¿Que son las actividades H5P?', [
            'categoryid' => $recursoscat->id,
            'pattern' => '¿Que son las actividades H5P?',
            'keywords' => "h5p\ncontenido h5p\ninteractivo h5p\nactividad interactiva",
            'response' => 'H5P son actividades interactivas enriquecidas:<br><br>- Videos interactivos con preguntas<br>- Presentaciones con navegacion<br>- Cuestionarios gamificados<br>- Tarjetas de memoria<br><br>Tu progreso se guarda automaticamente.',
            'tags' => 'h5p, interactivo, multimedia, actividad',
            'enabled' => 1,
            'showoptions' => 1,
        ]);

        // Get existing rule IDs for options (using helper function for TEXT comparison).
        $enrollmentid = $getruleid('¿Como me inscribo en un curso?');
        $forumid = $getruleid('¿Como participo en un foro?');
        $calendarid = $getruleid('¿Como veo el calendario?');
        $messagesid = $getruleid('¿Como envio un mensaje a mi profesor?');
        $passwordid = $getruleid('¿Como cambio mi contrasena?');

        // Helper function to add option if not exists.
        $addoption = function($ruleid, $text, $targetruleid, $icon, $sortorder) use ($DB) {
            if (!$ruleid || !$targetruleid) {
                return;
            }
            $existing = $DB->get_record('local_educambot_option', [
                'ruleid' => $ruleid,
                'text' => $text
            ]);
            if (!$existing) {
                $DB->insert_record('local_educambot_option', (object)[
                    'ruleid' => $ruleid,
                    'text' => $text,
                    'targetruleid' => $targetruleid,
                    'icon' => $icon,
                    'sortorder' => $sortorder,
                    'enabled' => 1
                ]);
            }
        };

        // Add startup options.
        $addoption($startupid, 'Mis Cursos', $mycoursesid, '📚', 1);
        $addoption($startupid, 'Entregar Tarea', $assignmentid, '📝', 2);
        $addoption($startupid, 'Ver Calificaciones', $gradesid, '📊', 3);
        $addoption($startupid, 'Examenes', $quizid, '✏️', 4);
        $addoption($startupid, 'Mi Perfil', $profileid, '👤', 5);
        $addoption($startupid, 'Ayuda', $supportid, '🆘', 6);

        // Add menu options.
        $addoption($menuid, 'Cursos', $mycoursesid, '📚', 1);
        $addoption($menuid, 'Tareas', $assignmentid, '📝', 2);
        $addoption($menuid, 'Calificaciones', $gradesid, '📊', 3);
        $addoption($menuid, 'Mi Perfil', $profileid, '👤', 4);
        $addoption($menuid, 'Soporte', $supportid, '🆘', 5);

        // Add navigation options for each rule.
        // Enrollment options.
        $addoption($enrollmentid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($enrollmentid, 'Clave de Curso', $coursekeyid, '🔑', 2);
        $addoption($enrollmentid, 'Buscar Cursos', $findcoursesid, '🔍', 3);

        // My courses options.
        $addoption($mycoursesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($mycoursesid, 'Inscribirme', $enrollmentid, '➕', 2);
        $addoption($mycoursesid, 'Ver Progreso', $courseprogressid, '📈', 3);

        // Find courses options.
        $addoption($findcoursesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($findcoursesid, 'Inscribirme', $enrollmentid, '➕', 2);

        // Course key options.
        $addoption($coursekeyid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($coursekeyid, 'Inscribirme', $enrollmentid, '➕', 2);

        // Course progress options.
        $addoption($courseprogressid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($courseprogressid, 'Certificado', $certificateid, '🎓', 2);

        // Certificate options.
        $addoption($certificateid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($certificateid, 'Ver Progreso', $courseprogressid, '📈', 2);

        // Assignment options.
        $addoption($assignmentid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($assignmentid, 'Modificar Tarea', $editassignmentid, '✏️', 2);
        $addoption($assignmentid, 'Entrega Tardia', $latesubmissionid, '⏰', 3);
        $addoption($assignmentid, 'Calificaciones', $gradesid, '📊', 4);

        // Edit assignment options.
        $addoption($editassignmentid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($editassignmentid, 'Entregar Tarea', $assignmentid, '📝', 2);

        // Late submission options.
        $addoption($latesubmissionid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($latesubmissionid, 'Contactar Profesor', $messagesid, '✉️', 2);

        // Forum options.
        $addoption($forumid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($forumid, 'Suscripciones', $forumsubid, '📧', 2);
        $addoption($forumid, 'Mensajes', $messagesid, '✉️', 3);

        // Forum subscription options.
        $addoption($forumsubid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($forumsubid, 'Notificaciones', $notificationsid, '🔔', 2);

        // Wiki options.
        $addoption($wikiid, 'Menu Principal', $menuid, '🏠', 1);

        // Glossary options.
        $addoption($glossaryid, 'Menu Principal', $menuid, '🏠', 1);

        // Grades options.
        $addoption($gradesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($gradesid, 'Retroalimentacion', $feedbackid, '💬', 2);
        $addoption($gradesid, 'Reclamar Nota', $gradeappealid, '⚖️', 3);
        $addoption($gradesid, 'Cuestionarios', $quizid, '❓', 4);

        // Quiz options.
        $addoption($quizid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($quizid, 'Intentos', $quizattemptsid, '🔄', 2);
        $addoption($quizid, 'Ver Respuestas', $quizreviewid, '👁️', 3);
        $addoption($quizid, 'Calificaciones', $gradesid, '📊', 4);

        // Quiz attempts options.
        $addoption($quizattemptsid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($quizattemptsid, 'Hacer Examen', $quizid, '✏️', 2);

        // Quiz review options.
        $addoption($quizreviewid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($quizreviewid, 'Calificaciones', $gradesid, '📊', 2);

        // Grade appeal options.
        $addoption($gradeappealid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($gradeappealid, 'Contactar Profesor', $messagesid, '✉️', 2);

        // Feedback options.
        $addoption($feedbackid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($feedbackid, 'Calificaciones', $gradesid, '📊', 2);

        // Profile options.
        $addoption($profileid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($profileid, 'Cambiar Foto', $profilepicid, '📷', 2);
        $addoption($profileid, 'Contrasena', $passwordid, '🔑', 3);
        $addoption($profileid, 'Notificaciones', $notificationsid, '🔔', 4);

        // Profile picture options.
        $addoption($profilepicid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($profilepicid, 'Editar Perfil', $profileid, '👤', 2);

        // Password options.
        $addoption($passwordid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($passwordid, 'Problemas de Acceso', $loginissuesid, '🔒', 2);

        // Notifications options.
        $addoption($notificationsid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($notificationsid, 'Correos del Foro', $forumsubid, '📧', 2);

        // Language options.
        $addoption($languageid, 'Menu Principal', $menuid, '🏠', 1);

        // Download files options.
        $addoption($downloadfilesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($downloadfilesid, 'Problemas Video', $videosid, '🎬', 2);

        // Videos options.
        $addoption($videosid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($videosid, 'Soporte Tecnico', $supportid, '🆘', 2);
        $addoption($videosid, 'Navegadores', $browserid, '🌐', 3);

        // SCORM options.
        $addoption($scormid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($scormid, 'Navegadores', $browserid, '🌐', 2);

        // H5P options.
        $addoption($h5pid, 'Menu Principal', $menuid, '🏠', 1);

        // Calendar options.
        $addoption($calendarid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($calendarid, 'Tareas', $assignmentid, '📝', 2);

        // Messages options.
        $addoption($messagesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($messagesid, 'Foros', $forumid, '💬', 2);

        // Support options.
        $addoption($supportid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($supportid, 'Navegadores', $browserid, '🌐', 2);
        $addoption($supportid, 'App Movil', $mobileappid, '📱', 3);
        $addoption($supportid, 'Login', $loginissuesid, '🔒', 4);

        // Browser options.
        $addoption($browserid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($browserid, 'Soporte', $supportid, '🆘', 2);

        // Mobile app options.
        $addoption($mobileappid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($mobileappid, 'Mis Cursos', $mycoursesid, '📚', 2);

        // Login issues options.
        $addoption($loginissuesid, 'Menu Principal', $menuid, '🏠', 1);
        $addoption($loginissuesid, 'Cambiar Contrasena', $passwordid, '🔑', 2);
        $addoption($loginissuesid, 'Soporte', $supportid, '🆘', 3);

        // About bot options.
        $addoption($aboutbotid, 'Ver Opciones', $menuid, '📋', 1);

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112007, 'local', 'educambot');
    }

    if ($oldversion < 2025112008) {
        // Version 1.7.0: Moodle Context Integration + Shortcuts.
        $now = time();

        // Add new fields to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');

        // Add contextaware field.
        $field = new xmldb_field('contextaware', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'showoptions');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add dynamicresponse field.
        $field = new xmldb_field('dynamicresponse', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'contextaware');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add requiredcontext field.
        $field = new xmldb_field('requiredcontext', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'dynamicresponse');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add contextaware index.
        $index = new xmldb_index('contextaware_idx', XMLDB_INDEX_NOTUNIQUE, ['contextaware']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define table local_educambot_shortcut to be created.
        $table = new xmldb_table('local_educambot_shortcut');

        // Adding fields to table local_educambot_shortcut.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('keywords', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('actiontype', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('icon', XMLDB_TYPE_CHAR, '50', null, null, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_educambot_shortcut.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Adding indexes to table local_educambot_shortcut.
        $table->add_index('actiontype_idx', XMLDB_INDEX_NOTUNIQUE, ['actiontype']);
        $table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);
        $table->add_index('sortorder_idx', XMLDB_INDEX_NOTUNIQUE, ['sortorder']);

        // Conditionally launch create table for local_educambot_shortcut.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Insert initial shortcuts.
        $shortcuts = [
            [
                'name' => 'Ver mis tareas',
                'keywords' => "ver mis tareas\nver tareas\ntareas pendientes\nque tareas tengo\nmostrar tareas",
                'actiontype' => 'assignments',
                'description' => 'Muestra lista de tareas pendientes del curso actual',
                'icon' => '📝',
                'sortorder' => 1,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Ver mis calificaciones',
                'keywords' => "ver calificaciones\nver notas\nmis notas\nmi calificacion\ncomo voy\nmi promedio",
                'actiontype' => 'grades',
                'description' => 'Muestra resumen de calificaciones del curso',
                'icon' => '📊',
                'sortorder' => 2,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Proximos eventos',
                'keywords' => "proximos eventos\neventos\ncalendario\nque hay esta semana\neventos pendientes\nfechas importantes",
                'actiontype' => 'calendar',
                'description' => 'Muestra eventos del calendario proximos 7 dias',
                'icon' => '📅',
                'sortorder' => 3,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Mis mensajes',
                'keywords' => "mis mensajes\nver mensajes\nmensajes nuevos\nmensajes no leidos",
                'actiontype' => 'messages',
                'description' => 'Muestra mensajes recientes y no leidos',
                'icon' => '✉️',
                'sortorder' => 4,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Mis profesores',
                'keywords' => "mis profesores\nquienes son mis profesores\nprofesores del curso\ncontactar profesor\ndocentes",
                'actiontype' => 'teachers',
                'description' => 'Muestra los profesores del curso actual',
                'icon' => '👨‍🏫',
                'sortorder' => 5,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Info del curso',
                'keywords' => "info del curso\ninformacion del curso\ndatos del curso\nsobre este curso",
                'actiontype' => 'course',
                'description' => 'Muestra informacion del curso actual',
                'icon' => '📚',
                'sortorder' => 6,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Mi progreso',
                'keywords' => "mi progreso\ncomo voy\navance del curso\nprogreso actual",
                'actiontype' => 'progress',
                'description' => 'Muestra el progreso en el curso actual',
                'icon' => '📈',
                'sortorder' => 7,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
        ];

        foreach ($shortcuts as $shortcut) {
            $DB->insert_record('local_educambot_shortcut', (object)$shortcut);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112008, 'local', 'educambot');
    }

    if ($oldversion < 2025112810) {
        // Version 1.8.0: Advanced Personalization + Multi-language.
        $now = time();

        // Add new fields to local_educambot_rule table.
        $table = new xmldb_table('local_educambot_rule');

        // Add roles field.
        $field = new xmldb_field('roles', XMLDB_TYPE_TEXT, null, null, null, null, null, 'requiredcontext');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add courses field.
        $field = new xmldb_field('courses', XMLDB_TYPE_TEXT, null, null, null, null, null, 'roles');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add lang field.
        $field = new xmldb_field('lang', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, 'es', 'courses');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add langparent field.
        $field = new xmldb_field('langparent', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'lang');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add lang index.
        $index = new xmldb_index('lang_idx', XMLDB_INDEX_NOTUNIQUE, ['lang']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Create schedule table.
        $table = new xmldb_table('local_educambot_schedule');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('dayofweek', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timefrom', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeto', XMLDB_TYPE_CHAR, '5', null, XMLDB_NOTNULL, null, null);
        $table->add_field('enabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('dayofweek_idx', XMLDB_INDEX_NOTUNIQUE, ['dayofweek']);
        $table->add_index('enabled_idx', XMLDB_INDEX_NOTUNIQUE, ['enabled']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create theme table.
        $table = new xmldb_table('local_educambot_theme');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('name', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('primarycolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('secondarycolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('textcolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('backgroundcolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('usercolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('botcolor', XMLDB_TYPE_CHAR, '7', null, XMLDB_NOTNULL, null, null);
        $table->add_field('isdefault', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_index('isdefault_idx', XMLDB_INDEX_NOTUNIQUE, ['isdefault']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Insert predefined themes.
        $themes = [
            [
                'name' => 'Default',
                'primarycolor' => '#0f6fc5',
                'secondarycolor' => '#084a8a',
                'textcolor' => '#1f2937',
                'backgroundcolor' => '#f9fafb',
                'usercolor' => '#0f6fc5',
                'botcolor' => '#ffffff',
                'isdefault' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Dark Mode',
                'primarycolor' => '#1f2937',
                'secondarycolor' => '#111827',
                'textcolor' => '#f9fafb',
                'backgroundcolor' => '#111827',
                'usercolor' => '#3b82f6',
                'botcolor' => '#374151',
                'isdefault' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Nature',
                'primarycolor' => '#059669',
                'secondarycolor' => '#047857',
                'textcolor' => '#1f2937',
                'backgroundcolor' => '#ecfdf5',
                'usercolor' => '#059669',
                'botcolor' => '#ffffff',
                'isdefault' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
            [
                'name' => 'Sunset',
                'primarycolor' => '#ea580c',
                'secondarycolor' => '#c2410c',
                'textcolor' => '#1f2937',
                'backgroundcolor' => '#fff7ed',
                'usercolor' => '#ea580c',
                'botcolor' => '#ffffff',
                'isdefault' => 0,
                'timecreated' => $now,
                'timemodified' => $now,
            ],
        ];

        foreach ($themes as $theme) {
            $DB->insert_record('local_educambot_theme', (object)$theme);
        }

        // Insert default schedule (24/7).
        $daynames = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
        for ($day = 0; $day <= 6; $day++) {
            $DB->insert_record('local_educambot_schedule', (object)[
                'dayofweek' => $day,
                'timefrom' => '00:00',
                'timeto' => '23:59',
                'enabled' => 1,
            ]);
        }

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112810, 'local', 'educambot');
    }

    if ($oldversion < 2025112811) {
        // Version 1.8.1: Widget icon and mascot customization.

        $table = new xmldb_table('local_educambot_theme');

        // Add widgeticontype field.
        $field = new xmldb_field('widgeticontype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'default', 'isdefault');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add widgeticonurl field.
        $field = new xmldb_field('widgeticonurl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'widgeticontype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add mascottype field.
        $field = new xmldb_field('mascottype', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'none', 'widgeticonurl');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add mascoturl field.
        $field = new xmldb_field('mascoturl', XMLDB_TYPE_TEXT, null, null, null, null, null, 'mascottype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add mascotenabled field.
        $field = new xmldb_field('mascotenabled', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'mascoturl');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Update default theme to enable mascot.
        $DB->execute("UPDATE {local_educambot_theme} SET mascottype = 'clippy', mascotenabled = 1 WHERE isdefault = 1");

        // Educambot savepoint reached.
        upgrade_plugin_savepoint(true, 2025112811, 'local', 'educambot');
    }

    return true;
}
