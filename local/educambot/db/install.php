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

    // Initial knowledge base rules.
    $rules = [
        // Enrollment questions.
        [
            'pattern' => '¿Cómo me inscribo en un curso?',
            'keywords' => "inscribir\nmatricularme\nenrollarme\nregistrarme en curso\napuntarme",
            'response' => 'Para inscribirte en un curso, sigue estos pasos:<br><br>1. Navega a la página del curso que te interesa<br>2. Haz clic en el botón "Inscribirme" o "Matricularme"<br>3. Si el curso requiere una clave de inscripción, tu profesor te la proporcionará<br>4. Una vez inscrito, el curso aparecerá en tu panel "Mis cursos"',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Password questions.
        [
            'pattern' => '¿Cómo cambio mi contraseña?',
            'keywords' => "contraseña\npassword\nclave\nolvidé contraseña\nrecuperar acceso\ncambiar clave",
            'response' => 'Para cambiar tu contraseña:<br><br>1. Haz clic en tu foto de perfil (esquina superior derecha)<br>2. Selecciona "Preferencias"<br>3. En la sección "Cuenta de usuario", haz clic en "Cambiar contraseña"<br>4. Introduce tu contraseña actual y la nueva contraseña<br>5. Haz clic en "Guardar cambios"<br><br>Si olvidaste tu contraseña, usa el enlace "¿Olvidó su contraseña?" en la página de inicio de sesión.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Profile questions.
        [
            'pattern' => '¿Cómo actualizo mi perfil?',
            'keywords' => "perfil\nfoto\nimagen\ndatos personales\neditar perfil\nmodificar perfil",
            'response' => 'Para actualizar tu perfil:<br><br>1. Haz clic en tu foto de perfil (esquina superior derecha)<br>2. Selecciona "Perfil"<br>3. Haz clic en "Editar perfil"<br>4. Modifica los campos que desees (nombre, foto, descripción, etc.)<br>5. Haz clic en "Actualizar perfil" para guardar los cambios',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Assignment questions.
        [
            'pattern' => '¿Cómo entrego una tarea?',
            'keywords' => "tarea\nsubir archivo\nentregar trabajo\nenviar tarea\nassignment\nactividad",
            'response' => 'Para entregar una tarea:<br><br>1. Accede al curso correspondiente<br>2. Haz clic en la actividad de tarea<br>3. Lee las instrucciones cuidadosamente<br>4. Haz clic en "Agregar entrega"<br>5. Arrastra tu archivo o haz clic para seleccionarlo<br>6. Haz clic en "Guardar cambios"<br><br>Recuerda verificar la fecha límite de entrega.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Grades questions.
        [
            'pattern' => '¿Dónde veo mis calificaciones?',
            'keywords' => "calificaciones\nnotas\npuntuación\nevaluación\nresultados\ngrading",
            'response' => 'Para ver tus calificaciones:<br><br>1. Entra en el curso correspondiente<br>2. En el menú lateral o de navegación, busca "Calificaciones"<br>3. Verás un informe con todas tus notas del curso<br><br>También puedes acceder desde tu perfil > "Calificaciones" para ver las notas de todos tus cursos.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Forum questions.
        [
            'pattern' => '¿Cómo participo en un foro?',
            'keywords' => "foro\ndiscusión\nresponder\ncomentario\npublicar mensaje\ndebate",
            'response' => 'Para participar en un foro:<br><br>1. Accede al curso y haz clic en el foro<br>2. Para crear un nuevo tema: haz clic en "Añadir un nuevo tema de discusión"<br>3. Escribe el asunto y tu mensaje<br>4. Haz clic en "Enviar al foro"<br><br>Para responder a un tema existente, haz clic en "Responder" debajo del mensaje.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Calendar questions.
        [
            'pattern' => '¿Cómo veo el calendario?',
            'keywords' => "calendario\nfechas\neventos\nvencimientos\nplazos\nagenda",
            'response' => 'El calendario de Moodle te muestra eventos importantes:<br><br>1. En el panel lateral derecho encontrarás el bloque "Calendario"<br>2. Los colores indican diferentes tipos de eventos:<br>   - Azul: eventos del sitio<br>   - Naranja: eventos del curso<br>   - Verde: eventos de grupo<br>   - Amarillo: eventos personales<br>3. Haz clic en una fecha para ver los detalles',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Messages questions.
        [
            'pattern' => '¿Cómo envío un mensaje a mi profesor?',
            'keywords' => "mensaje\ncontactar profesor\nenviar mensaje\nchat\ncomunicar\nescribir",
            'response' => 'Para enviar un mensaje a tu profesor:<br><br>1. Haz clic en el ícono de mensajes (burbuja) en la barra superior<br>2. Haz clic en "Nuevo mensaje"<br>3. Escribe el nombre del profesor en el buscador<br>4. Selecciona al profesor de la lista<br>5. Escribe tu mensaje y haz clic en enviar<br><br>También puedes ir al perfil del profesor y hacer clic en "Mensaje".',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Quiz questions.
        [
            'pattern' => '¿Cómo hago un cuestionario o examen?',
            'keywords' => "cuestionario\nexamen\ntest\nquiz\nevaluación\npreguntas",
            'response' => 'Para realizar un cuestionario:<br><br>1. Accede al curso y haz clic en el cuestionario<br>2. Lee las instrucciones y el tiempo disponible<br>3. Haz clic en "Intente resolver el cuestionario ahora"<br>4. Responde las preguntas y navega con los botones de página<br>5. Al terminar, haz clic en "Terminar intento"<br>6. Revisa tus respuestas y haz clic en "Enviar todo y terminar"<br><br>¡Importante! Una vez enviado, no podrás modificar tus respuestas.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Help/Support.
        [
            'pattern' => '¿Cómo contacto con soporte técnico?',
            'keywords' => "soporte\nayuda\nproblema técnico\nerror\ncontacto\nasistencia",
            'response' => 'Si necesitas ayuda técnica:<br><br>1. Primero, intenta cerrar sesión y volver a entrar<br>2. Limpia la caché de tu navegador<br>3. Prueba con un navegador diferente<br><br>Si el problema persiste, contacta al administrador del sitio a través del formulario de contacto o envía un correo describiendo tu problema con detalle.',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Greeting.
        [
            'pattern' => 'Hola',
            'keywords' => "hola\nbuenas\nbuenos días\nbuenas tardes\nbuenas noches\nqué tal\nsaludos",
            'response' => '¡Hola! Soy el asistente virtual de esta plataforma. Estoy aquí para ayudarte con tus dudas sobre el uso de Moodle. ¿En qué puedo ayudarte hoy?',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // Thanks.
        [
            'pattern' => 'Gracias',
            'keywords' => "gracias\nmuchas gracias\nte lo agradezco\ngenial\nperfecto",
            'response' => '¡De nada! Me alegra poder ayudarte. Si tienes más preguntas, no dudes en consultarme. ¡Que tengas un excelente día de aprendizaje!',
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    // Insert all rules.
    foreach ($rules as $rule) {
        $DB->insert_record('local_educambot_rule', (object)$rule);
    }

    return true;
}
