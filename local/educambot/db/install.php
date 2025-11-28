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
 * Plugin installation script - seeds initial knowledge base.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Install the plugin and seed initial rules.
 *
 * @return bool
 */
function xmldb_local_educambot_install() {
    global $DB;

    $now = time();

    // First, create categories.
    $categories = [
        'general' => [
            'name' => 'General',
            'description' => 'Preguntas generales y saludos',
            'parent' => null,
            'sortorder' => 1,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'cursos' => [
            'name' => 'Cursos',
            'description' => 'Inscripcion y acceso a cursos',
            'parent' => null,
            'sortorder' => 2,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'tareas' => [
            'name' => 'Tareas y Actividades',
            'description' => 'Entrega de tareas, foros y cuestionarios',
            'parent' => null,
            'sortorder' => 3,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'evaluaciones' => [
            'name' => 'Evaluaciones',
            'description' => 'Calificaciones y examenes',
            'parent' => null,
            'sortorder' => 4,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'perfil' => [
            'name' => 'Perfil y Cuenta',
            'description' => 'Configuracion de perfil y contrasena',
            'parent' => null,
            'sortorder' => 5,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'soporte' => [
            'name' => 'Soporte',
            'description' => 'Ayuda tecnica y comunicacion',
            'parent' => null,
            'sortorder' => 6,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    // Insert categories and store IDs.
    $catids = [];
    foreach ($categories as $key => $cat) {
        $catids[$key] = $DB->insert_record('local_educambot_category', (object)$cat);
    }

    // Initial knowledge base rules with categories and tags.
    $rules = [
        // Main menu - entry point.
        'menu' => [
            'categoryid' => $catids['general'],
            'pattern' => 'Menu principal',
            'keywords' => "menu\ninicio\nayuda\nque puedes hacer\nopciones\nempezar",
            'response' => '¿En que puedo ayudarte hoy? Selecciona una opcion o escribe tu pregunta:',
            'tags' => 'menu, inicio, navegacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Greeting.
        'greeting' => [
            'categoryid' => $catids['general'],
            'pattern' => 'Hola',
            'keywords' => "hola\nbuenas\nbuenos dias\nbuenas tardes\nbuenas noches\nque tal\nsaludos",
            'response' => '¡Hola! Soy el asistente virtual de esta plataforma. Estoy aqui para ayudarte con tus dudas sobre el uso de Moodle. ¿En que puedo ayudarte hoy?',
            'tags' => 'saludo, bienvenida',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Thanks.
        'thanks' => [
            'categoryid' => $catids['general'],
            'pattern' => 'Gracias',
            'keywords' => "gracias\nmuchas gracias\nte lo agradezco\ngenial\nperfecto",
            'response' => '¡De nada! Me alegra poder ayudarte. Si tienes mas preguntas, no dudes en consultarme. ¡Que tengas un excelente dia de aprendizaje!',
            'tags' => 'agradecimiento, despedida',
            'enabled' => 1,
            'showoptions' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Enrollment questions.
        'enrollment' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Como me inscribo en un curso?',
            'keywords' => "inscribir\nmatricularme\nenrollarme\nregistrarme en curso\napuntarme",
            'response' => 'Para inscribirte en un curso, sigue estos pasos:<br><br>1. Navega a la pagina del curso que te interesa<br>2. Haz clic en el boton "Inscribirme" o "Matricularme"<br>3. Si el curso requiere una clave de inscripcion, tu profesor te la proporcionara<br>4. Una vez inscrito, el curso aparecera en tu panel "Mis cursos"',
            'tags' => 'inscripcion, matricula, curso, registro',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Assignment questions.
        'assignment' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como entrego una tarea?',
            'keywords' => "tarea\nsubir archivo\nentregar trabajo\nenviar tarea\nassignment\nactividad",
            'response' => 'Para entregar una tarea:<br><br>1. Accede al curso correspondiente<br>2. Haz clic en la actividad de tarea<br>3. Lee las instrucciones cuidadosamente<br>4. Haz clic en "Agregar entrega"<br>5. Arrastra tu archivo o haz clic para seleccionarlo<br>6. Haz clic en "Guardar cambios"<br><br>Recuerda verificar la fecha limite de entrega.',
            'tags' => 'tarea, entrega, archivo, actividad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Forum questions.
        'forum' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como participo en un foro?',
            'keywords' => "foro\ndiscusion\nresponder\ncomentario\npublicar mensaje\ndebate",
            'response' => 'Para participar en un foro:<br><br>1. Accede al curso y haz clic en el foro<br>2. Para crear un nuevo tema: haz clic en "Anadir un nuevo tema de discusion"<br>3. Escribe el asunto y tu mensaje<br>4. Haz clic en "Enviar al foro"<br><br>Para responder a un tema existente, haz clic en "Responder" debajo del mensaje.',
            'tags' => 'foro, discusion, participacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Grades questions.
        'grades' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Donde veo mis calificaciones?',
            'keywords' => "calificaciones\nnotas\npuntuacion\nevaluacion\nresultados\ngrading",
            'response' => 'Para ver tus calificaciones:<br><br>1. Entra en el curso correspondiente<br>2. En el menu lateral o de navegacion, busca "Calificaciones"<br>3. Veras un informe con todas tus notas del curso<br><br>Tambien puedes acceder desde tu perfil > "Calificaciones" para ver las notas de todos tus cursos.',
            'tags' => 'calificaciones, notas, evaluacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Quiz questions.
        'quiz' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Como hago un cuestionario o examen?',
            'keywords' => "cuestionario\nexamen\ntest\nquiz\nevaluacion\npreguntas",
            'response' => 'Para realizar un cuestionario:<br><br>1. Accede al curso y haz clic en el cuestionario<br>2. Lee las instrucciones y el tiempo disponible<br>3. Haz clic en "Intente resolver el cuestionario ahora"<br>4. Responde las preguntas y navega con los botones de pagina<br>5. Al terminar, haz clic en "Terminar intento"<br>6. Revisa tus respuestas y haz clic en "Enviar todo y terminar"<br><br>¡Importante! Una vez enviado, no podras modificar tus respuestas.',
            'tags' => 'cuestionario, examen, quiz, test',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Profile questions.
        'profile' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como actualizo mi perfil?',
            'keywords' => "perfil\nfoto\nimagen\ndatos personales\neditar perfil\nmodificar perfil",
            'response' => 'Para actualizar tu perfil:<br><br>1. Haz clic en tu foto de perfil (esquina superior derecha)<br>2. Selecciona "Perfil"<br>3. Haz clic en "Editar perfil"<br>4. Modifica los campos que desees (nombre, foto, descripcion, etc.)<br>5. Haz clic en "Actualizar perfil" para guardar los cambios',
            'tags' => 'perfil, foto, datos, cuenta',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Password questions.
        'password' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como cambio mi contrasena?',
            'keywords' => "contrasena\npassword\nclave\nolvide contrasena\nrecuperar acceso\ncambiar clave",
            'response' => 'Para cambiar tu contrasena:<br><br>1. Haz clic en tu foto de perfil (esquina superior derecha)<br>2. Selecciona "Preferencias"<br>3. En la seccion "Cuenta de usuario", haz clic en "Cambiar contrasena"<br>4. Introduce tu contrasena actual y la nueva contrasena<br>5. Haz clic en "Guardar cambios"<br><br>Si olvidaste tu contrasena, usa el enlace "¿Olvido su contrasena?" en la pagina de inicio de sesion.',
            'tags' => 'contrasena, password, clave, seguridad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Calendar questions.
        'calendar' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como veo el calendario?',
            'keywords' => "calendario\nfechas\neventos\nvencimientos\nplazos\nagenda",
            'response' => 'El calendario de Moodle te muestra eventos importantes:<br><br>1. En el panel lateral derecho encontraras el bloque "Calendario"<br>2. Los colores indican diferentes tipos de eventos:<br>   - Azul: eventos del sitio<br>   - Naranja: eventos del curso<br>   - Verde: eventos de grupo<br>   - Amarillo: eventos personales<br>3. Haz clic en una fecha para ver los detalles',
            'tags' => 'calendario, eventos, fechas, agenda',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Messages questions.
        'messages' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como envio un mensaje a mi profesor?',
            'keywords' => "mensaje\ncontactar profesor\nenviar mensaje\nchat\ncomunicar\nescribir",
            'response' => 'Para enviar un mensaje a tu profesor:<br><br>1. Haz clic en el icono de mensajes (burbuja) en la barra superior<br>2. Haz clic en "Nuevo mensaje"<br>3. Escribe el nombre del profesor en el buscador<br>4. Selecciona al profesor de la lista<br>5. Escribe tu mensaje y haz clic en enviar<br><br>Tambien puedes ir al perfil del profesor y hacer clic en "Mensaje".',
            'tags' => 'mensaje, comunicacion, profesor, contacto',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Help/Support.
        'support' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como contacto con soporte tecnico?',
            'keywords' => "soporte\nayuda\nproblema tecnico\nerror\ncontacto\nasistencia",
            'response' => 'Si necesitas ayuda tecnica:<br><br>1. Primero, intenta cerrar sesion y volver a entrar<br>2. Limpia la cache de tu navegador<br>3. Prueba con un navegador diferente<br><br>Si el problema persiste, contacta al administrador del sitio a traves del formulario de contacto o envia un correo describiendo tu problema con detalle.',
            'tags' => 'soporte, ayuda, tecnico, error',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    // Insert all rules and store IDs.
    $ruleids = [];
    foreach ($rules as $key => $rule) {
        $ruleids[$key] = $DB->insert_record('local_educambot_rule', (object)$rule);
    }

    // Define quick reply options for rules.
    $options = [
        // Options for Main Menu.
        ['ruleid' => $ruleids['menu'], 'text' => 'Cursos', 'targetruleid' => $ruleids['enrollment'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Mi Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 5, 'enabled' => 1],

        // Options for Greeting (same as menu).
        ['ruleid' => $ruleids['greeting'], 'text' => 'Cursos', 'targetruleid' => $ruleids['enrollment'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Mi Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 4, 'enabled' => 1],

        // Options after answering a specific question - go back to menu or ask another.
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],

        ['ruleid' => $ruleids['assignment'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 3, 'enabled' => 1],

        ['ruleid' => $ruleids['grades'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Cuestionarios', 'targetruleid' => $ruleids['quiz'], 'icon' => '❓', 'sortorder' => 3, 'enabled' => 1],

        ['ruleid' => $ruleids['profile'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Cambiar Contrasena', 'targetruleid' => $ruleids['password'], 'icon' => '🔑', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Mensajes', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 3, 'enabled' => 1],

        ['ruleid' => $ruleids['password'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['password'], 'text' => 'Mi Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 2, 'enabled' => 1],

        ['ruleid' => $ruleids['forum'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['forum'], 'text' => 'Mensajes', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 2, 'enabled' => 1],

        ['ruleid' => $ruleids['calendar'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['calendar'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],

        ['ruleid' => $ruleids['messages'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['messages'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 2, 'enabled' => 1],

        ['ruleid' => $ruleids['quiz'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['quiz'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 2, 'enabled' => 1],

        ['ruleid' => $ruleids['support'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
    ];

    // Insert all options.
    foreach ($options as $option) {
        $DB->insert_record('local_educambot_option', (object)$option);
    }

    return true;
}
