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
            'description' => 'Preguntas generales, saludos y navegacion',
            'parent' => null,
            'sortorder' => 1,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'cursos' => [
            'name' => 'Cursos',
            'description' => 'Inscripcion, acceso y gestion de cursos',
            'parent' => null,
            'sortorder' => 2,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'tareas' => [
            'name' => 'Tareas y Actividades',
            'description' => 'Entrega de tareas, foros, cuestionarios y actividades',
            'parent' => null,
            'sortorder' => 3,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'evaluaciones' => [
            'name' => 'Evaluaciones',
            'description' => 'Calificaciones, examenes y retroalimentacion',
            'parent' => null,
            'sortorder' => 4,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'perfil' => [
            'name' => 'Perfil y Cuenta',
            'description' => 'Configuracion de perfil, contrasena y preferencias',
            'parent' => null,
            'sortorder' => 5,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'recursos' => [
            'name' => 'Recursos y Materiales',
            'description' => 'Acceso a archivos, videos y materiales de estudio',
            'parent' => null,
            'sortorder' => 6,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'soporte' => [
            'name' => 'Soporte',
            'description' => 'Ayuda tecnica, calendario y comunicacion',
            'parent' => null,
            'sortorder' => 7,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        // v1.9.1 - Categorias por arquetipo de rol.
        'docentes' => [
            'name' => 'Docentes y Gestion',
            'description' => 'Gestion de cursos, calificaciones y estudiantes para profesores',
            'parent' => null,
            'sortorder' => 8,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'administracion' => [
            'name' => 'Administracion',
            'description' => 'Gestion del sitio, usuarios y configuracion para administradores',
            'parent' => null,
            'sortorder' => 9,
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

    // =============================================
    // EXTENDED KNOWLEDGE BASE - Version 1.6.1
    // =============================================
    $rules = [
        // =============================================
        // CATEGORY: GENERAL
        // =============================================

        // Startup options - Special rule for initial suggestions (marked with special keyword).
        'startup' => [
            'categoryid' => $catids['general'],
            'pattern' => '__startup__',
            'keywords' => "__startup__\n__init__",
            'response' => 'Selecciona una de las opciones o escribe tu pregunta:',
            'tags' => 'inicio, startup, opciones',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Main menu.
        'menu' => [
            'categoryid' => $catids['general'],
            'pattern' => 'Menu principal',
            'keywords' => "menu\ninicio\nayuda\nque puedes hacer\nopciones\nempezar\nvolver al inicio",
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
            'keywords' => "hola\nbuenas\nbuenos dias\nbuenas tardes\nbuenas noches\nque tal\nsaludos\nhey\nhi",
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
            'keywords' => "gracias\nmuchas gracias\nte lo agradezco\ngenial\nperfecto\nexcelente\nok gracias\ngracias por tu ayuda",
            'response' => '¡De nada! Me alegra poder ayudarte. Si tienes mas preguntas, no dudes en consultarme. ¡Que tengas un excelente dia de aprendizaje!',
            'tags' => 'agradecimiento, despedida',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Goodbye.
        'goodbye' => [
            'categoryid' => $catids['general'],
            'pattern' => 'Adios',
            'keywords' => "adios\nhasta luego\nnos vemos\nchao\nbyte\nhasta pronto\nme voy",
            'response' => '¡Hasta pronto! Fue un placer ayudarte. Recuerda que estoy aqui cuando me necesites. ¡Exito en tus estudios!',
            'tags' => 'despedida, adios',
            'enabled' => 1,
            'showoptions' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // About bot.
        'aboutbot' => [
            'categoryid' => $catids['general'],
            'pattern' => '¿Quien eres?',
            'keywords' => "quien eres\nque eres\neres un robot\neres humano\ncomo te llamas\ntu nombre",
            'response' => 'Soy Nexo Bot, un asistente virtual disenado para ayudarte a navegar y utilizar esta plataforma educativa Moodle. Puedo responder preguntas sobre cursos, tareas, calificaciones, tu perfil y mucho mas. ¡Estoy aqui para hacer tu experiencia de aprendizaje mas facil!',
            'tags' => 'bot, identidad, presentacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: CURSOS
        // =============================================

        // Enrollment.
        'enrollment' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Como me inscribo en un curso?',
            'keywords' => "inscribir\nmatricularme\nenrollarme\nregistrarme en curso\napuntarme\nentrar a un curso\nunirme a curso",
            'response' => 'Para inscribirte en un curso, sigue estos pasos:<br><br>1. Navega a la pagina del curso que te interesa<br>2. Haz clic en el boton "Inscribirme" o "Matricularme"<br>3. Si el curso requiere una clave de inscripcion, tu profesor te la proporcionara<br>4. Una vez inscrito, el curso aparecera en tu panel "Mis cursos"<br><br><strong>Tip:</strong> Tambien puedes buscar cursos desde el menu "Todos los cursos" en la navegacion.',
            'tags' => 'inscripcion, matricula, curso, registro',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // My courses.
        'mycourses' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Donde veo mis cursos?',
            'keywords' => "mis cursos\nver cursos\ncursos inscritos\npanel de cursos\nlista de cursos\nacceder a curso",
            'response' => 'Puedes ver tus cursos de varias formas:<br><br>1. <strong>Panel principal:</strong> Al iniciar sesion, veras el bloque "Mis cursos" en tu tablero<br>2. <strong>Menu de navegacion:</strong> Haz clic en "Mis cursos" en el menu superior o lateral<br>3. <strong>Perfil:</strong> En tu perfil puedes ver todos los cursos en los que estas inscrito<br><br>Los cursos se organizan por categorias y puedes filtrarlos por estado (en progreso, completados, etc.).',
            'tags' => 'cursos, panel, acceso',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Find courses.
        'findcourses' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Como busco cursos disponibles?',
            'keywords' => "buscar cursos\ncursos disponibles\ncatalogo de cursos\nencontrar curso\nver todos los cursos",
            'response' => 'Para buscar cursos disponibles:<br><br>1. Ve a "Todos los cursos" en el menu de navegacion<br>2. Usa el cuadro de busqueda para encontrar cursos por nombre<br>3. Navega por las categorias para explorar temas especificos<br>4. Haz clic en cualquier curso para ver su descripcion<br><br>Los cursos marcados como "abiertos" permiten inscripcion libre, mientras que otros pueden requerir una clave o aprobacion.',
            'tags' => 'buscar, catalogo, explorar',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Course key.
        'coursekey' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Que es la clave de inscripcion?',
            'keywords' => "clave de inscripcion\nclave de curso\npassword del curso\ncontrasena de inscripcion\nclave de matricula",
            'response' => 'La clave de inscripcion es una contrasena que protege el acceso a ciertos cursos:<br><br>- <strong>¿Quien la proporciona?</strong> Tu profesor o coordinador te dara la clave<br>- <strong>¿Cuando se usa?</strong> Solo al inscribirte por primera vez<br>- <strong>¿Es lo mismo que tu contrasena?</strong> No, es diferente a tu contrasena de usuario<br><br>Si no tienes la clave, contacta directamente a tu profesor o al administrador del curso.',
            'tags' => 'clave, inscripcion, password, acceso',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Course progress.
        'courseprogress' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Como veo mi progreso en el curso?',
            'keywords' => "progreso del curso\navance del curso\nporcentaje completado\nactividades completadas\nestado del curso",
            'response' => 'Para ver tu progreso en un curso:<br><br>1. Entra al curso deseado<br>2. Busca la barra de progreso en la parte superior o lateral<br>3. Tambien puedes ir a "Informe de actividad" en el menu del curso<br><br>El progreso se calcula segun las actividades que hayas completado. Las actividades marcadas con un check verde estan completadas.',
            'tags' => 'progreso, avance, completado',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Course certificate.
        'certificate' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Como obtengo mi certificado?',
            'keywords' => "certificado\ndiploma\nconstancia\ncertificacion\nobtener certificado\ndescargar certificado",
            'response' => 'Para obtener tu certificado:<br><br>1. Completa todas las actividades requeridas del curso<br>2. Asegurate de cumplir con los requisitos minimos de calificacion<br>3. Busca la actividad "Certificado" al final del curso<br>4. Haz clic para generar y descargar tu certificado en PDF<br><br><strong>Nota:</strong> No todos los cursos emiten certificado. Verifica los requisitos en la descripcion del curso.',
            'tags' => 'certificado, diploma, constancia, finalizacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: TAREAS Y ACTIVIDADES
        // =============================================

        // Assignment submission.
        'assignment' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como entrego una tarea?',
            'keywords' => "tarea\nsubir archivo\nentregar trabajo\nenviar tarea\nassignment\nactividad\nentregar archivo",
            'response' => 'Para entregar una tarea:<br><br>1. Accede al curso correspondiente<br>2. Haz clic en la actividad de tarea<br>3. Lee las instrucciones y requisitos cuidadosamente<br>4. Haz clic en "Agregar entrega"<br>5. Arrastra tu archivo o haz clic para seleccionarlo<br>6. Haz clic en "Guardar cambios"<br><br><strong>Importante:</strong> Verifica la fecha limite y los formatos de archivo permitidos antes de entregar.',
            'tags' => 'tarea, entrega, archivo, actividad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Edit assignment.
        'editassignment' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Puedo modificar una tarea enviada?',
            'keywords' => "modificar tarea\neditar entrega\ncambiar archivo\nreenviar tarea\ncorregir tarea",
            'response' => 'La posibilidad de modificar una tarea depende de la configuracion:<br><br>- <strong>Antes del cierre:</strong> Generalmente puedes editar tu entrega haciendo clic en "Editar entrega"<br>- <strong>Despues del cierre:</strong> Solo si el profesor habilita intentos adicionales<br><br>Para editar:<br>1. Ve a la tarea<br>2. Haz clic en "Editar entrega"<br>3. Elimina el archivo anterior y sube el nuevo<br>4. Guarda los cambios<br><br>Si la opcion no esta disponible, contacta a tu profesor.',
            'tags' => 'editar, modificar, reenviar, tarea',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Late submission.
        'latesubmission' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Puedo entregar una tarea tarde?',
            'keywords' => "entrega tardia\ntarea atrasada\nfuera de plazo\ndespues de la fecha\nextension\nprorroga",
            'response' => 'Las entregas tardias dependen de la configuracion del profesor:<br><br>- <strong>Entrega cerrada:</strong> No podras enviar despues de la fecha limite<br>- <strong>Con penalizacion:</strong> Puedes entregar pero con descuento en la calificacion<br>- <strong>Con extension:</strong> Algunos profesores otorgan extensiones individuales<br><br><strong>Recomendacion:</strong> Si tienes una situacion especial, contacta a tu profesor lo antes posible para solicitar una extension.',
            'tags' => 'tardia, extension, prorroga, plazo',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Forum participation.
        'forum' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como participo en un foro?',
            'keywords' => "foro\ndiscusion\nresponder\ncomentario\npublicar mensaje\ndebate\nparticipacion foro",
            'response' => 'Para participar en un foro:<br><br><strong>Crear un nuevo tema:</strong><br>1. Accede al foro<br>2. Haz clic en "Anadir un nuevo tema de discusion"<br>3. Escribe el asunto y tu mensaje<br>4. Adjunta archivos si es necesario<br>5. Haz clic en "Enviar al foro"<br><br><strong>Responder a un tema:</strong><br>1. Abre el tema que quieres responder<br>2. Haz clic en "Responder"<br>3. Escribe tu respuesta<br>4. Haz clic en enviar',
            'tags' => 'foro, discusion, participacion, responder',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Forum subscription.
        'forumsub' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Por que recibo tantos correos del foro?',
            'keywords' => "suscripcion foro\ncorreos foro\nnotificaciones foro\ndejar de recibir correos\ndesuscribirse",
            'response' => 'Los correos del foro son por la suscripcion automatica. Para gestionarlos:<br><br>1. Ve al foro en cuestion<br>2. Busca el enlace "Suscribirse/Darse de baja del foro"<br>3. Haz clic para cancelar la suscripcion<br><br>Tambien puedes configurar tus preferencias de correo en:<br>Perfil > Preferencias > Preferencias de foro<br><br>Opciones: Sin resumen, Resumen completo, o Temas.',
            'tags' => 'suscripcion, correos, notificaciones, foro',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Wiki.
        'wiki' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como uso un wiki?',
            'keywords' => "wiki\neditar wiki\ncolaborar wiki\npagina wiki\nwiki colaborativo",
            'response' => 'Un Wiki es una herramienta colaborativa:<br><br><strong>Para ver:</strong> Simplemente haz clic en la actividad Wiki<br><br><strong>Para editar:</strong><br>1. Abre la pagina del wiki<br>2. Haz clic en la pestana "Editar"<br>3. Modifica el contenido<br>4. Haz clic en "Guardar"<br><br><strong>Para crear nueva pagina:</strong><br>1. Escribe [[NombreDePagina]] en cualquier pagina<br>2. Guarda<br>3. Haz clic en el enlace rojo creado<br><br>El historial guarda todas las versiones.',
            'tags' => 'wiki, colaborativo, editar, paginas',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Glossary.
        'glossary' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como agrego terminos al glosario?',
            'keywords' => "glosario\nagregar termino\ndefinicion\ndiccionario\nconcepto\nentrada glosario",
            'response' => 'Para agregar un termino al glosario:<br><br>1. Accede a la actividad Glosario<br>2. Haz clic en "Agregar entrada"<br>3. Escribe el <strong>concepto</strong> (palabra o termino)<br>4. Escribe la <strong>definicion</strong><br>5. Opcionalmente agrega palabras clave y archivos adjuntos<br>6. Haz clic en "Guardar cambios"<br><br>Algunos glosarios permiten que los estudiantes comenten las entradas de otros.',
            'tags' => 'glosario, termino, definicion, diccionario',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: EVALUACIONES
        // =============================================

        // Grades.
        'grades' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Donde veo mis calificaciones?',
            'keywords' => "calificaciones\nnotas\npuntuacion\nevaluacion\nresultados\ngrading\nver notas",
            'response' => 'Para ver tus calificaciones:<br><br><strong>Opcion 1 - Desde el curso:</strong><br>1. Entra en el curso<br>2. Busca "Calificaciones" en el menu de navegacion<br><br><strong>Opcion 2 - Desde tu perfil:</strong><br>1. Haz clic en tu foto de perfil<br>2. Selecciona "Calificaciones"<br>3. Veras las notas de todos tus cursos<br><br>El libro de calificaciones muestra cada actividad, su peso y tu calificacion total.',
            'tags' => 'calificaciones, notas, evaluacion',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Quiz.
        'quiz' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Como hago un cuestionario o examen?',
            'keywords' => "cuestionario\nexamen\ntest\nquiz\nevaluacion\npreguntas\nhacer examen",
            'response' => 'Para realizar un cuestionario:<br><br>1. Accede al curso y haz clic en el cuestionario<br>2. Lee las instrucciones y el tiempo disponible<br>3. Haz clic en "Intente resolver el cuestionario ahora"<br>4. Responde las preguntas navegando con los botones de pagina<br>5. Al terminar, haz clic en "Terminar intento"<br>6. Revisa tus respuestas<br>7. Haz clic en "Enviar todo y terminar"<br><br><strong>¡Importante!</strong> Una vez enviado, no podras modificar tus respuestas.',
            'tags' => 'cuestionario, examen, quiz, test',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Quiz attempts.
        'quizattempts' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Cuantos intentos tengo en el cuestionario?',
            'keywords' => "intentos cuestionario\nvolver a intentar\nreintentar examen\nnumero de intentos\notro intento",
            'response' => 'El numero de intentos permitidos lo define el profesor:<br><br>1. Abre el cuestionario<br>2. Lee la informacion inicial que indica los intentos permitidos<br>3. En "Tus intentos previos" veras cuantos has usado<br><br><strong>Tipos de configuracion:</strong><br>- Intentos ilimitados<br>- Numero fijo (ej: 3 intentos)<br>- Un solo intento<br><br>La calificacion puede ser: el mejor intento, el promedio o el ultimo.',
            'tags' => 'intentos, reintentar, cuestionario',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Quiz review.
        'quizreview' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Puedo ver las respuestas correctas del examen?',
            'keywords' => "respuestas correctas\nrevision examen\nver errores\nretroalimentacion quiz\nrespuestas del cuestionario",
            'response' => 'La revision del cuestionario depende de la configuracion del profesor:<br><br><strong>Puede que veas:</strong><br>- Solo tu puntuacion<br>- Tus respuestas sin marcar las correctas<br>- Las respuestas correctas e incorrectas<br>- Retroalimentacion detallada<br><br><strong>Cuando puedes revisar:</strong><br>- Inmediatamente despues<br>- Despues de cerrar<br>- Despues de la fecha limite<br><br>Ve al cuestionario y haz clic en "Revisar" si esta disponible.',
            'tags' => 'revision, respuestas, retroalimentacion, examen',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Grade appeal.
        'gradeappeal' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Como reclamo una calificacion?',
            'keywords' => "reclamar nota\napelacion\nrevision de nota\nno estoy de acuerdo\ncalificacion incorrecta",
            'response' => 'Si tienes dudas sobre una calificacion:<br><br>1. <strong>Revisa la rubrica o criterios</strong> de evaluacion de la actividad<br>2. <strong>Lee la retroalimentacion</strong> que el profesor dejo<br>3. <strong>Contacta al profesor</strong> a traves de mensajeria o correo<br>   - Se respetuoso y especifico<br>   - Explica tu punto de vista con argumentos<br>4. Si no hay resolucion, consulta con el coordinador del curso<br><br><strong>Tip:</strong> Siempre adjunta evidencia si la tienes.',
            'tags' => 'reclamacion, apelacion, revision, nota',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Feedback.
        'feedback' => [
            'categoryid' => $catids['evaluaciones'],
            'pattern' => '¿Donde veo los comentarios de mi profesor?',
            'keywords' => "comentarios profesor\nretroalimentacion\nfeedback\nobservaciones\nanotaciones",
            'response' => 'Para ver la retroalimentacion de tu profesor:<br><br><strong>En tareas:</strong><br>1. Ve a la tarea<br>2. Haz clic en "Ver envio" o "Estado de la entrega"<br>3. Busca la seccion "Retroalimentacion"<br><br><strong>En cuestionarios:</strong><br>1. Abre el cuestionario<br>2. Haz clic en "Revisar" tu intento<br><br><strong>Notificaciones:</strong><br>Tambien recibiras notificacion cuando el profesor califique tu trabajo.',
            'tags' => 'retroalimentacion, feedback, comentarios',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: PERFIL Y CUENTA
        // =============================================

        // Profile.
        'profile' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como actualizo mi perfil?',
            'keywords' => "perfil\nfoto\nimagen\ndatos personales\neditar perfil\nmodificar perfil\nactualizar datos",
            'response' => 'Para actualizar tu perfil:<br><br>1. Haz clic en tu foto de perfil (esquina superior derecha)<br>2. Selecciona "Perfil"<br>3. Haz clic en "Editar perfil"<br>4. Modifica los campos:<br>   - Nombre y apellido<br>   - Correo electronico<br>   - Foto de perfil<br>   - Descripcion personal<br>   - Ciudad, pais<br>5. Haz clic en "Actualizar perfil"<br><br><strong>Nota:</strong> Algunos campos pueden estar bloqueados por el administrador.',
            'tags' => 'perfil, foto, datos, cuenta',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Profile picture.
        'profilepic' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como cambio mi foto de perfil?',
            'keywords' => "cambiar foto\nsubir imagen\navatar\nfoto de perfil\nimagen de usuario",
            'response' => 'Para cambiar tu foto de perfil:<br><br>1. Ve a tu Perfil > Editar perfil<br>2. Busca la seccion "Imagen de usuario"<br>3. Haz clic en el area de la imagen o arrastra una foto<br>4. Ajusta el recorte si es necesario<br>5. Haz clic en "Actualizar perfil"<br><br><strong>Requisitos recomendados:</strong><br>- Formato: JPG, PNG o GIF<br>- Tamano: Minimo 100x100 pixeles<br>- Imagen cuadrada para mejor visualizacion',
            'tags' => 'foto, imagen, avatar, perfil',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Password.
        'password' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como cambio mi contrasena?',
            'keywords' => "contrasena\npassword\nclave\nolvide contrasena\nrecuperar acceso\ncambiar clave\nmodificar password",
            'response' => 'Para cambiar tu contrasena:<br><br>1. Haz clic en tu foto de perfil<br>2. Selecciona "Preferencias"<br>3. En "Cuenta de usuario", haz clic en "Cambiar contrasena"<br>4. Introduce tu contrasena actual<br>5. Escribe tu nueva contrasena (2 veces)<br>6. Haz clic en "Guardar cambios"<br><br><strong>¿Olvidaste tu contrasena?</strong><br>En la pagina de login, usa "¿Olvido su nombre de usuario o contrasena?"',
            'tags' => 'contrasena, password, clave, seguridad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Notifications.
        'notifications' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como configuro mis notificaciones?',
            'keywords' => "notificaciones\nalertas\navisos\ncorreos\nconfigurar alertas\nrecibir notificaciones",
            'response' => 'Para configurar tus notificaciones:<br><br>1. Haz clic en tu foto de perfil<br>2. Ve a "Preferencias"<br>3. Selecciona "Preferencias de notificacion"<br>4. Para cada tipo de notificacion puedes elegir:<br>   - <strong>En linea:</strong> Notificacion dentro de Moodle<br>   - <strong>Correo:</strong> Recibir por email<br>5. Guarda los cambios<br><br>Puedes configurar alertas para: mensajes, foros, tareas, calificaciones y mas.',
            'tags' => 'notificaciones, alertas, correos, preferencias',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Language.
        'language' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Como cambio el idioma?',
            'keywords' => "idioma\nlenguaje\ncambiar idioma\ningles\nespanol\nidioma de la plataforma",
            'response' => 'Para cambiar el idioma de la plataforma:<br><br>1. Haz clic en tu foto de perfil<br>2. Ve a "Preferencias"<br>3. Busca "Idioma preferido"<br>4. Selecciona el idioma deseado<br>5. Guarda los cambios<br><br><strong>Nota:</strong> El idioma disponible depende de los paquetes instalados por el administrador. El contenido del curso permanecera en su idioma original.',
            'tags' => 'idioma, lenguaje, preferencias',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: RECURSOS Y MATERIALES
        // =============================================

        // Download files.
        'downloadfiles' => [
            'categoryid' => $catids['recursos'],
            'pattern' => '¿Como descargo los materiales del curso?',
            'keywords' => "descargar archivo\nbajar material\ndescargar pdf\nobtener documentos\nmateriales del curso\nrecursos",
            'response' => 'Para descargar materiales del curso:<br><br>1. Entra al curso<br>2. Localiza el recurso que deseas descargar<br>3. Haz clic en el nombre del archivo<br>4. El archivo se descargara o abrira en nueva pestana<br><br><strong>Para descargar carpetas completas:</strong><br>- Haz clic en la carpeta<br>- Busca el boton "Descargar carpeta"<br><br><strong>Tip:</strong> Los PDFs pueden abrirse directamente en el navegador; haz clic derecho > "Guardar enlace como" para descargar.',
            'tags' => 'descargar, materiales, archivos, recursos',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Videos.
        'videos' => [
            'categoryid' => $catids['recursos'],
            'pattern' => '¿Por que no puedo ver los videos?',
            'keywords' => "video no carga\nvideo no funciona\nno reproduce video\nproblemas con video\nver video",
            'response' => 'Si tienes problemas con los videos:<br><br><strong>Soluciones comunes:</strong><br>1. <strong>Actualiza la pagina</strong> (F5 o Ctrl+R)<br>2. <strong>Revisa tu conexion</strong> a internet<br>3. <strong>Prueba otro navegador</strong> (Chrome, Firefox, Edge)<br>4. <strong>Desactiva el bloqueador de anuncios</strong><br>5. <strong>Limpia la cache</strong> del navegador<br><br><strong>Para videos de YouTube:</strong><br>- Asegurate de no tener YouTube bloqueado<br>- Verifica que tu red permita acceso a YouTube',
            'tags' => 'video, reproduccion, problemas, multimedia',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // SCORM.
        'scorm' => [
            'categoryid' => $catids['recursos'],
            'pattern' => '¿Que es un paquete SCORM?',
            'keywords' => "scorm\npaquete scorm\ncontenido interactivo\nmodulo scorm\nscorm no abre",
            'response' => 'SCORM es un formato de contenido interactivo de aprendizaje:<br><br><strong>Caracteristicas:</strong><br>- Contenido multimedia interactivo<br>- Registra tu progreso automaticamente<br>- Puede incluir evaluaciones integradas<br><br><strong>Para usarlo:</strong><br>1. Haz clic en la actividad SCORM<br>2. Haz clic en "Entrar"<br>3. Navega usando los controles internos<br>4. Completa todas las secciones<br><br><strong>Si no abre:</strong> Desactiva el bloqueador de ventanas emergentes.',
            'tags' => 'scorm, interactivo, paquete, elearning',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // H5P.
        'h5p' => [
            'categoryid' => $catids['recursos'],
            'pattern' => '¿Que son las actividades H5P?',
            'keywords' => "h5p\ncontenido h5p\ninteractivo h5p\nactividad interactiva",
            'response' => 'H5P son actividades interactivas enriquecidas:<br><br><strong>Tipos comunes:</strong><br>- Videos interactivos con preguntas<br>- Presentaciones con navegacion<br>- Cuestionarios gamificados<br>- Tarjetas de memoria<br>- Lineas de tiempo<br>- Imagenes con puntos de acceso<br><br><strong>Para usar:</strong><br>1. Haz clic en la actividad H5P<br>2. Interactua con el contenido<br>3. Tu progreso se guarda automaticamente<br><br>Estas actividades funcionan en cualquier dispositivo.',
            'tags' => 'h5p, interactivo, multimedia, actividad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: SOPORTE
        // =============================================

        // Calendar.
        'calendar' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como veo el calendario?',
            'keywords' => "calendario\nfechas\neventos\nvencimientos\nplazos\nagenda\nfechas limite",
            'response' => 'El calendario de Moodle te muestra eventos importantes:<br><br>1. Busca el bloque "Calendario" en el panel lateral<br>2. O accede desde el menu "Calendario"<br><br><strong>Colores de eventos:</strong><br>- <span style="color:blue">Azul:</span> Eventos del sitio<br>- <span style="color:orange">Naranja:</span> Eventos del curso<br>- <span style="color:green">Verde:</span> Eventos de grupo<br>- <span style="color:gold">Amarillo:</span> Eventos personales<br>- <span style="color:red">Rojo:</span> Fechas limite<br><br>Haz clic en una fecha para ver los detalles.',
            'tags' => 'calendario, eventos, fechas, agenda',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Messages.
        'messages' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como envio un mensaje a mi profesor?',
            'keywords' => "mensaje\ncontactar profesor\nenviar mensaje\nchat\ncomunicar\nescribir mensaje\nmensajeria",
            'response' => 'Para enviar un mensaje a tu profesor:<br><br><strong>Opcion 1 - Mensajeria:</strong><br>1. Haz clic en el icono de mensajes (burbuja) en la barra superior<br>2. Haz clic en "Nuevo mensaje" o busca el nombre<br>3. Escribe tu mensaje y envia<br><br><strong>Opcion 2 - Desde el perfil:</strong><br>1. Ve a la lista de participantes del curso<br>2. Haz clic en el nombre del profesor<br>3. Haz clic en "Mensaje"<br><br><strong>Tip:</strong> Se claro y respetuoso en tu mensaje.',
            'tags' => 'mensaje, comunicacion, profesor, contacto',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Technical support.
        'support' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como contacto con soporte tecnico?',
            'keywords' => "soporte\nayuda tecnica\nproblema tecnico\nerror\ncontacto\nasistencia\nreportar problema",
            'response' => 'Si necesitas ayuda tecnica:<br><br><strong>Primero intenta:</strong><br>1. Cerrar sesion y volver a entrar<br>2. Limpiar la cache del navegador<br>3. Probar con otro navegador<br>4. Verificar tu conexion a internet<br><br><strong>Si el problema persiste:</strong><br>1. Anota el mensaje de error exacto<br>2. Haz una captura de pantalla<br>3. Contacta al administrador via:<br>   - Formulario de contacto del sitio<br>   - Correo del soporte<br><br>Incluye: tu usuario, curso, y descripcion del problema.',
            'tags' => 'soporte, ayuda, tecnico, error',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Browser issues.
        'browser' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Que navegador debo usar?',
            'keywords' => "navegador\nbrowser\nchrome\nfirefox\nedge\nsafari\nnavegador recomendado",
            'response' => 'Moodle funciona mejor con navegadores actualizados:<br><br><strong>Recomendados:</strong><br>- Google Chrome (preferido)<br>- Mozilla Firefox<br>- Microsoft Edge<br>- Safari (Mac)<br><br><strong>No recomendados:</strong><br>- Internet Explorer (descontinuado)<br>- Navegadores muy antiguos<br><br><strong>Tips:</strong><br>- Mantén tu navegador actualizado<br>- Habilita JavaScript<br>- Permite cookies para el sitio<br>- Desactiva bloqueadores de anuncios si hay problemas',
            'tags' => 'navegador, browser, compatibilidad',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Mobile app.
        'mobileapp' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Puedo usar Moodle en mi celular?',
            'keywords' => "celular\nmovil\napp\naplicacion\nsmartphone\ntableta\nmoodle mobile",
            'response' => 'Si, puedes usar Moodle en tu dispositivo movil:<br><br><strong>Opcion 1 - App oficial:</strong><br>1. Descarga "Moodle" desde App Store o Google Play<br>2. Abre la app y escribe la URL de tu sitio Moodle<br>3. Inicia sesion con tus credenciales<br><br><strong>Opcion 2 - Navegador:</strong><br>- Moodle tiene diseno responsive<br>- Funciona en cualquier navegador movil<br><br><strong>Funciones de la app:</strong><br>- Acceder a cursos y materiales<br>- Recibir notificaciones push<br>- Subir tareas<br>- Participar en foros',
            'tags' => 'movil, app, celular, mobile',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Login issues.
        'loginissues' => [
            'categoryid' => $catids['soporte'],
            'pattern' => 'No puedo iniciar sesion',
            'keywords' => "no puedo entrar\nlogin falla\nacceso denegado\nusuario bloqueado\nno me deja entrar\nsesion",
            'response' => 'Si tienes problemas para iniciar sesion:<br><br><strong>Verifica:</strong><br>1. <strong>Usuario correcto:</strong> Puede ser tu correo o un ID asignado<br>2. <strong>Contrasena:</strong> Respeta mayusculas/minusculas<br>3. <strong>Caps Lock:</strong> Asegurate de que este desactivado<br><br><strong>Soluciones:</strong><br>1. Usa "¿Olvido su contrasena?" para recuperarla<br>2. Limpia cookies del navegador<br>3. Prueba en modo incognito<br><br><strong>¿Cuenta bloqueada?</strong><br>Contacta al administrador para desbloquearla.',
            'tags' => 'login, acceso, sesion, problemas',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: DOCENTES Y GESTION (v1.9.1)
        // Reglas para arquetipos: teacher, editingteacher
        // =============================================

        // Grade assignment.
        'gradeassignment' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como califico una tarea?',
            'keywords' => "calificar tarea\ncalificar actividad\nponer nota\nevaluar estudiante\nrevisar entregas",
            'response' => 'Para calificar tareas de tus estudiantes:<br><br>1. Accede al curso y haz clic en la actividad de tarea<br>2. Haz clic en "Ver todas las entregas"<br>3. Para cada estudiante:<br>   - Haz clic en "Calificar" junto a su nombre<br>   - Revisa el archivo entregado<br>   - Asigna la calificacion en el campo correspondiente<br>   - Escribe retroalimentacion si lo deseas<br>   - Haz clic en "Guardar cambios"<br><br><strong>Tip:</strong> Usa "Calificacion rapida" para evaluar multiples entregas mas eficientemente.',
            'tags' => 'calificar, evaluar, tarea, profesor',
            'roles' => 'teacher,editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Create quiz.
        'createquiz' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como creo un cuestionario?',
            'keywords' => "crear cuestionario\ncrear examen\ncrear quiz\nanadir preguntas\ncrear evaluacion",
            'response' => 'Para crear un cuestionario en tu curso:<br><br>1. Activa el modo de edicion<br>2. En la seccion deseada, haz clic en "Anadir una actividad o recurso"<br>3. Selecciona "Cuestionario"<br>4. Configura:<br>   - Nombre y descripcion<br>   - Tiempo limite (si aplica)<br>   - Fecha de apertura y cierre<br>   - Numero de intentos permitidos<br>5. Haz clic en "Guardar y mostrar"<br>6. Haz clic en "Editar cuestionario" para anadir preguntas<br><br><strong>Tip:</strong> Crea las preguntas primero en el Banco de preguntas.',
            'tags' => 'crear, cuestionario, examen, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Student progress.
        'studentprogress' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como veo el progreso de mis estudiantes?',
            'keywords' => "progreso estudiantes\nver avance\ncompletion estudiantes\nreporte progreso\nseguimiento estudiantes",
            'response' => 'Para ver el progreso de tus estudiantes:<br><br><strong>Opcion 1 - Informe de actividad:</strong><br>1. Ve a tu curso<br>2. En Administracion del curso > Informes > Finalizacion de la actividad<br><br><strong>Opcion 2 - Por estudiante:</strong><br>1. Ve a Participantes<br>2. Haz clic en un estudiante<br>3. Revisa "Informes de actividad"<br><br><strong>Opcion 3 - Calificador:</strong><br>1. Administracion del curso > Calificaciones<br>2. Selecciona "Ver > Informe del calificador"',
            'tags' => 'progreso, estudiantes, seguimiento, profesor',
            'roles' => 'teacher,editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Set deadline.
        'setdeadline' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como configuro la fecha limite de una tarea?',
            'keywords' => "fecha limite\ndeadline\ncierre tarea\nfecha entrega\nplazo tarea",
            'response' => 'Para configurar fechas limite en tareas:<br><br>1. Ve a la tarea y haz clic en "Editar ajustes"<br>2. En la seccion "Disponibilidad":<br>   - <strong>Permitir entregas desde:</strong> Fecha de apertura<br>   - <strong>Fecha de entrega:</strong> Fecha limite sugerida<br>   - <strong>Fecha limite:</strong> Cierre definitivo de entregas<br>   - <strong>Fecha de corte:</strong> No acepta entregas despues<br>3. Guarda los cambios<br><br><strong>Tip:</strong> "Fecha de entrega" muestra aviso pero permite entregas tardias hasta "Fecha limite".',
            'tags' => 'fecha, limite, deadline, tarea, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Add resources.
        'addresources' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como agrego materiales al curso?',
            'keywords' => "agregar material\nsubir archivo\nagregar recurso\nagregar documento\nsubir pdf",
            'response' => 'Para agregar materiales a tu curso:<br><br>1. Activa el "Modo de edicion" (esquina superior derecha)<br>2. En la seccion deseada, haz clic en "Agregar una actividad o recurso"<br>3. Selecciona el tipo:<br>   - <strong>Archivo:</strong> Un documento (PDF, Word, etc.)<br>   - <strong>Carpeta:</strong> Multiples archivos organizados<br>   - <strong>URL:</strong> Enlace a sitio externo<br>   - <strong>Pagina:</strong> Contenido HTML<br>4. Sube el archivo o configura el recurso<br>5. Guarda los cambios',
            'tags' => 'agregar, material, recurso, archivo, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Enroll students.
        'enrollstudents' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como inscribo estudiantes en mi curso?',
            'keywords' => "inscribir estudiante\nmatricular alumno\nagregar estudiante\nenrol estudiante",
            'response' => 'Para inscribir estudiantes en tu curso:<br><br><strong>Inscripcion manual:</strong><br>1. Ve a tu curso > Participantes<br>2. Haz clic en "Inscribir usuarios"<br>3. Busca al estudiante por nombre o correo<br>4. Selecciona el rol (Estudiante)<br>5. Haz clic en "Inscribir"<br><br><strong>Autoinscripcion con clave:</strong><br>1. Administracion > Metodos de inscripcion<br>2. Habilita "Autoinscripcion"<br>3. Configura una clave de inscripcion<br>4. Comparte la clave con tus estudiantes',
            'tags' => 'inscribir, estudiante, matricular, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Create groups.
        'creategroups' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como creo grupos en mi curso?',
            'keywords' => "crear grupo\ngrupos de trabajo\norganizar grupos\nequipos estudiantes",
            'response' => 'Para crear grupos en tu curso:<br><br>1. Ve a Administracion del curso > Usuarios > Grupos<br>2. Haz clic en "Crear grupo"<br>3. Asigna un nombre al grupo<br>4. Guarda los cambios<br>5. Selecciona el grupo y haz clic en "Agregar/quitar usuarios"<br>6. Selecciona los estudiantes y agregalos<br><br><strong>Tip:</strong> Usa "Crear grupos automaticamente" para asignacion aleatoria.<br><br>Luego configura las actividades en "Modo de grupo" para trabajar con grupos.',
            'tags' => 'grupos, equipos, organizar, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Backup course.
        'backupcourse' => [
            'categoryid' => $catids['docentes'],
            'pattern' => '¿Como hago respaldo de mi curso?',
            'keywords' => "respaldo curso\nbackup\ncopia seguridad\nexportar curso\nguardar curso",
            'response' => 'Para crear un respaldo de tu curso:<br><br>1. Ve a Administracion del curso > Copia de seguridad<br>2. Selecciona los elementos a incluir:<br>   - Actividades y recursos<br>   - Bloques<br>   - Datos de usuarios (opcional)<br>   - Archivos del curso<br>3. Haz clic en "Siguiente" y revisa la configuracion<br>4. Haz clic en "Ejecutar copia de seguridad"<br>5. Descarga el archivo .mbz generado<br><br><strong>Tip:</strong> Guarda copias periodicas, especialmente antes de cambios importantes.',
            'tags' => 'backup, respaldo, copia, seguridad, profesor',
            'roles' => 'editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Teacher menu.
        'teachermenu' => [
            'categoryid' => $catids['docentes'],
            'pattern' => 'Menu de profesor',
            'keywords' => "menu profesor\nopciones profesor\nque puedo hacer como profesor\nherramientas docente",
            'response' => 'Como profesor tienes acceso a herramientas especiales:<br><br><strong>Gestion del curso:</strong><br>- Agregar y editar actividades<br>- Configurar fechas y restricciones<br>- Crear grupos de estudiantes<br><br><strong>Evaluacion:</strong><br>- Calificar tareas y cuestionarios<br>- Configurar rubrics<br>- Ver libro de calificaciones<br><br><strong>Seguimiento:</strong><br>- Ver progreso de estudiantes<br>- Informes de actividad<br>- Logs del curso<br><br>¿En que te puedo ayudar?',
            'tags' => 'menu, profesor, herramientas, docente',
            'roles' => 'teacher,editingteacher',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // =============================================
        // CATEGORY: ADMINISTRACION (v1.9.1)
        // Reglas para arquetipo: manager
        // =============================================

        // Create course.
        'createcourse' => [
            'categoryid' => $catids['administracion'],
            'pattern' => '¿Como creo un curso nuevo?',
            'keywords' => "crear curso\nnuevo curso\nanadir curso\ncurso nuevo",
            'response' => 'Para crear un nuevo curso:<br><br>1. Ve a Administracion del sitio > Cursos > Gestionar cursos y categorias<br>2. Selecciona la categoria donde quieres el curso<br>3. Haz clic en "Crear un nuevo curso"<br>4. Completa la informacion:<br>   - Nombre completo y corto<br>   - Categoria<br>   - Fechas de inicio y fin<br>   - Formato del curso<br>5. Haz clic en "Guardar y mostrar"<br>6. Asigna profesores en "Inscribir usuarios"<br><br><strong>Tip:</strong> Usa plantillas de curso para agilizar la creacion.',
            'tags' => 'crear, curso, nuevo, administrador',
            'roles' => 'manager,coursecreator',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Manage users.
        'manageusers' => [
            'categoryid' => $catids['administracion'],
            'pattern' => '¿Como gestiono usuarios?',
            'keywords' => "gestionar usuarios\nadministrar usuarios\ncrear usuario\neditar usuario\nusuarios del sitio",
            'response' => 'Para gestionar usuarios del sitio:<br><br><strong>Ver usuarios:</strong><br>Administracion del sitio > Usuarios > Cuentas > Examinar lista de usuarios<br><br><strong>Crear usuario:</strong><br>1. Administracion > Usuarios > Agregar un usuario<br>2. Completa los datos requeridos<br>3. Genera o asigna contrasena<br><br><strong>Editar usuario:</strong><br>1. Busca al usuario en la lista<br>2. Haz clic en el icono de editar<br>3. Modifica los campos necesarios<br><br><strong>Carga masiva:</strong><br>Administracion > Usuarios > Subir usuarios (CSV)',
            'tags' => 'usuarios, gestionar, administrador, cuentas',
            'roles' => 'manager',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Site reports.
        'sitereports' => [
            'categoryid' => $catids['administracion'],
            'pattern' => '¿Como veo los reportes del sitio?',
            'keywords' => "reportes sitio\ninformes sistema\nreportes moodle\nver estadisticas\nlogs sitio",
            'response' => 'Para ver reportes del sitio:<br><br><strong>Logs del sistema:</strong><br>Administracion > Informes > Logs<br><br><strong>Estadisticas:</strong><br>Administracion > Informes > Estadisticas del sitio<br><br><strong>Rendimiento:</strong><br>Administracion > Informes > Rendimiento<br><br><strong>Backups:</strong><br>Administracion > Informes > Logs de copia de seguridad<br><br><strong>Eventos:</strong><br>Administracion > Informes > Lista de eventos<br><br><strong>Tip:</strong> Configura alertas automaticas para eventos importantes.',
            'tags' => 'reportes, informes, estadisticas, administrador',
            'roles' => 'manager',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Site configuration.
        'siteconfig' => [
            'categoryid' => $catids['administracion'],
            'pattern' => '¿Como configuro el sitio?',
            'keywords' => "configurar sitio\najustes sitio\nconfiguracion moodle\npersonalizar sitio",
            'response' => 'Configuraciones principales del sitio:<br><br><strong>Apariencia:</strong><br>- Administracion > Apariencia > Temas<br>- Administracion > Apariencia > Logos<br><br><strong>Pagina principal:</strong><br>- Administracion > Pagina principal > Ajustes<br><br><strong>Seguridad:</strong><br>- Administracion > Seguridad > Politicas del sitio<br>- Administracion > Seguridad > Seguridad HTTP<br><br><strong>Plugins:</strong><br>- Administracion > Plugins > Vista general<br><br><strong>Idiomas:</strong><br>- Administracion > Idioma > Ajustes de idioma',
            'tags' => 'configuracion, sitio, ajustes, administrador',
            'roles' => 'manager',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Manage plugins.
        'manageplugins' => [
            'categoryid' => $catids['administracion'],
            'pattern' => '¿Como instalo un plugin?',
            'keywords' => "instalar plugin\nagregar plugin\nplugin moodle\nextension moodle",
            'response' => 'Para instalar un plugin en Moodle:<br><br><strong>Desde ZIP:</strong><br>1. Descarga el plugin (.zip) desde moodle.org/plugins<br>2. Ve a Administracion > Plugins > Instalar plugins<br>3. Sube el archivo ZIP<br>4. Haz clic en "Instalar plugin desde ZIP"<br>5. Confirma la instalacion<br><br><strong>Manual (FTP):</strong><br>1. Extrae el plugin en la carpeta correspondiente<br>2. Ve a Administracion > Notificaciones<br>3. Sigue el proceso de instalacion<br><br><strong>Tip:</strong> Siempre revisa la compatibilidad con tu version de Moodle.',
            'tags' => 'plugin, instalar, extension, administrador',
            'roles' => 'manager',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Admin menu.
        'adminmenu' => [
            'categoryid' => $catids['administracion'],
            'pattern' => 'Menu de administrador',
            'keywords' => "menu administrador\nopciones admin\nque puedo hacer como admin\nherramientas admin",
            'response' => 'Como administrador tienes acceso completo al sitio:<br><br><strong>Gestion de usuarios:</strong><br>- Crear y editar usuarios<br>- Asignar roles globales<br>- Carga masiva de usuarios<br><br><strong>Gestion de cursos:</strong><br>- Crear categorias y cursos<br>- Restaurar cursos<br>- Gestionar inscripciones<br><br><strong>Configuracion:</strong><br>- Temas y apariencia<br>- Plugins<br>- Seguridad<br><br><strong>Monitoreo:</strong><br>- Logs y reportes<br>- Rendimiento<br>- Tareas programadas<br><br>¿En que te puedo ayudar?',
            'tags' => 'menu, administrador, herramientas, admin',
            'roles' => 'manager',
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

    // =============================================
    // QUICK REPLY OPTIONS
    // =============================================
    $options = [
        // Startup options (shown when chat opens).
        ['ruleid' => $ruleids['startup'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Entregar Tarea', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Ver Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Examenes', 'targetruleid' => $ruleids['quiz'], 'icon' => '✏️', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Mi Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Ayuda', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 6, 'enabled' => 1],

        // Main Menu options.
        ['ruleid' => $ruleids['menu'], 'text' => 'Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Mi Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 5, 'enabled' => 1],

        // Greeting options.
        ['ruleid' => $ruleids['greeting'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Entregar Tarea', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Ayuda', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 4, 'enabled' => 1],

        // Thanks options.
        ['ruleid' => $ruleids['thanks'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['thanks'], 'text' => 'Otra Pregunta', 'targetruleid' => $ruleids['startup'], 'icon' => '❓', 'sortorder' => 2, 'enabled' => 1],

        // About bot options.
        ['ruleid' => $ruleids['aboutbot'], 'text' => 'Ver Opciones', 'targetruleid' => $ruleids['menu'], 'icon' => '📋', 'sortorder' => 1, 'enabled' => 1],

        // Enrollment options.
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Clave de Curso', 'targetruleid' => $ruleids['coursekey'], 'icon' => '🔑', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Buscar Cursos', 'targetruleid' => $ruleids['findcourses'], 'icon' => '🔍', 'sortorder' => 3, 'enabled' => 1],

        // My courses options.
        ['ruleid' => $ruleids['mycourses'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['mycourses'], 'text' => 'Inscribirme', 'targetruleid' => $ruleids['enrollment'], 'icon' => '➕', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['mycourses'], 'text' => 'Ver Progreso', 'targetruleid' => $ruleids['courseprogress'], 'icon' => '📈', 'sortorder' => 3, 'enabled' => 1],

        // Find courses options.
        ['ruleid' => $ruleids['findcourses'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['findcourses'], 'text' => 'Inscribirme', 'targetruleid' => $ruleids['enrollment'], 'icon' => '➕', 'sortorder' => 2, 'enabled' => 1],

        // Course key options.
        ['ruleid' => $ruleids['coursekey'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['coursekey'], 'text' => 'Inscribirme', 'targetruleid' => $ruleids['enrollment'], 'icon' => '➕', 'sortorder' => 2, 'enabled' => 1],

        // Course progress options.
        ['ruleid' => $ruleids['courseprogress'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['courseprogress'], 'text' => 'Certificado', 'targetruleid' => $ruleids['certificate'], 'icon' => '🎓', 'sortorder' => 2, 'enabled' => 1],

        // Certificate options.
        ['ruleid' => $ruleids['certificate'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['certificate'], 'text' => 'Ver Progreso', 'targetruleid' => $ruleids['courseprogress'], 'icon' => '📈', 'sortorder' => 2, 'enabled' => 1],

        // Assignment options.
        ['ruleid' => $ruleids['assignment'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Modificar Tarea', 'targetruleid' => $ruleids['editassignment'], 'icon' => '✏️', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Entrega Tardia', 'targetruleid' => $ruleids['latesubmission'], 'icon' => '⏰', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 4, 'enabled' => 1],

        // Edit assignment options.
        ['ruleid' => $ruleids['editassignment'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['editassignment'], 'text' => 'Entregar Tarea', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],

        // Late submission options.
        ['ruleid' => $ruleids['latesubmission'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['latesubmission'], 'text' => 'Contactar Profesor', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 2, 'enabled' => 1],

        // Forum options.
        ['ruleid' => $ruleids['forum'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['forum'], 'text' => 'Suscripciones', 'targetruleid' => $ruleids['forumsub'], 'icon' => '📧', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['forum'], 'text' => 'Mensajes', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 3, 'enabled' => 1],

        // Forum subscription options.
        ['ruleid' => $ruleids['forumsub'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['forumsub'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 2, 'enabled' => 1],

        // Wiki options.
        ['ruleid' => $ruleids['wiki'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],

        // Glossary options.
        ['ruleid' => $ruleids['glossary'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],

        // Grades options.
        ['ruleid' => $ruleids['grades'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Retroalimentacion', 'targetruleid' => $ruleids['feedback'], 'icon' => '💬', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Reclamar Nota', 'targetruleid' => $ruleids['gradeappeal'], 'icon' => '⚖️', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Cuestionarios', 'targetruleid' => $ruleids['quiz'], 'icon' => '❓', 'sortorder' => 4, 'enabled' => 1],

        // Quiz options.
        ['ruleid' => $ruleids['quiz'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['quiz'], 'text' => 'Intentos', 'targetruleid' => $ruleids['quizattempts'], 'icon' => '🔄', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['quiz'], 'text' => 'Ver Respuestas', 'targetruleid' => $ruleids['quizreview'], 'icon' => '👁️', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['quiz'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 4, 'enabled' => 1],

        // Quiz attempts options.
        ['ruleid' => $ruleids['quizattempts'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['quizattempts'], 'text' => 'Hacer Examen', 'targetruleid' => $ruleids['quiz'], 'icon' => '✏️', 'sortorder' => 2, 'enabled' => 1],

        // Quiz review options.
        ['ruleid' => $ruleids['quizreview'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['quizreview'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 2, 'enabled' => 1],

        // Grade appeal options.
        ['ruleid' => $ruleids['gradeappeal'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['gradeappeal'], 'text' => 'Contactar Profesor', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 2, 'enabled' => 1],

        // Feedback options.
        ['ruleid' => $ruleids['feedback'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['feedback'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 2, 'enabled' => 1],

        // Profile options.
        ['ruleid' => $ruleids['profile'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Cambiar Foto', 'targetruleid' => $ruleids['profilepic'], 'icon' => '📷', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Contrasena', 'targetruleid' => $ruleids['password'], 'icon' => '🔑', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 4, 'enabled' => 1],

        // Profile picture options.
        ['ruleid' => $ruleids['profilepic'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['profilepic'], 'text' => 'Editar Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 2, 'enabled' => 1],

        // Password options.
        ['ruleid' => $ruleids['password'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['password'], 'text' => 'Problemas de Acceso', 'targetruleid' => $ruleids['loginissues'], 'icon' => '🔒', 'sortorder' => 2, 'enabled' => 1],

        // Notifications options.
        ['ruleid' => $ruleids['notifications'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['notifications'], 'text' => 'Correos del Foro', 'targetruleid' => $ruleids['forumsub'], 'icon' => '📧', 'sortorder' => 2, 'enabled' => 1],

        // Language options.
        ['ruleid' => $ruleids['language'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],

        // Download files options.
        ['ruleid' => $ruleids['downloadfiles'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['downloadfiles'], 'text' => 'Problemas Video', 'targetruleid' => $ruleids['videos'], 'icon' => '🎬', 'sortorder' => 2, 'enabled' => 1],

        // Videos options.
        ['ruleid' => $ruleids['videos'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['videos'], 'text' => 'Soporte Tecnico', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['videos'], 'text' => 'Navegadores', 'targetruleid' => $ruleids['browser'], 'icon' => '🌐', 'sortorder' => 3, 'enabled' => 1],

        // SCORM options.
        ['ruleid' => $ruleids['scorm'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['scorm'], 'text' => 'Navegadores', 'targetruleid' => $ruleids['browser'], 'icon' => '🌐', 'sortorder' => 2, 'enabled' => 1],

        // H5P options.
        ['ruleid' => $ruleids['h5p'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],

        // Calendar options.
        ['ruleid' => $ruleids['calendar'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['calendar'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],

        // Messages options.
        ['ruleid' => $ruleids['messages'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['messages'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 2, 'enabled' => 1],

        // Support options.
        ['ruleid' => $ruleids['support'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['support'], 'text' => 'Navegadores', 'targetruleid' => $ruleids['browser'], 'icon' => '🌐', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['support'], 'text' => 'App Movil', 'targetruleid' => $ruleids['mobileapp'], 'icon' => '📱', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['support'], 'text' => 'Login', 'targetruleid' => $ruleids['loginissues'], 'icon' => '🔒', 'sortorder' => 4, 'enabled' => 1],

        // Browser options.
        ['ruleid' => $ruleids['browser'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['browser'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 2, 'enabled' => 1],

        // Mobile app options.
        ['ruleid' => $ruleids['mobileapp'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['mobileapp'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 2, 'enabled' => 1],

        // Login issues options.
        ['ruleid' => $ruleids['loginissues'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['loginissues'], 'text' => 'Cambiar Contrasena', 'targetruleid' => $ruleids['password'], 'icon' => '🔑', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['loginissues'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 3, 'enabled' => 1],

        // =============================================
        // OPCIONES PARA DOCENTES (v1.9.1)
        // =============================================

        // Grade assignment options.
        ['ruleid' => $ruleids['gradeassignment'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['gradeassignment'], 'text' => 'Ver Progreso', 'targetruleid' => $ruleids['studentprogress'], 'icon' => '📈', 'sortorder' => 2, 'enabled' => 1],

        // Create quiz options.
        ['ruleid' => $ruleids['createquiz'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['createquiz'], 'text' => 'Agregar Materiales', 'targetruleid' => $ruleids['addresources'], 'icon' => '📁', 'sortorder' => 2, 'enabled' => 1],

        // Student progress options.
        ['ruleid' => $ruleids['studentprogress'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['studentprogress'], 'text' => 'Calificar Tarea', 'targetruleid' => $ruleids['gradeassignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],

        // Set deadline options.
        ['ruleid' => $ruleids['setdeadline'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['setdeadline'], 'text' => 'Crear Cuestionario', 'targetruleid' => $ruleids['createquiz'], 'icon' => '❓', 'sortorder' => 2, 'enabled' => 1],

        // Add resources options.
        ['ruleid' => $ruleids['addresources'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['addresources'], 'text' => 'Crear Cuestionario', 'targetruleid' => $ruleids['createquiz'], 'icon' => '❓', 'sortorder' => 2, 'enabled' => 1],

        // Enroll students options.
        ['ruleid' => $ruleids['enrollstudents'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollstudents'], 'text' => 'Crear Grupos', 'targetruleid' => $ruleids['creategroups'], 'icon' => '👥', 'sortorder' => 2, 'enabled' => 1],

        // Create groups options.
        ['ruleid' => $ruleids['creategroups'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['creategroups'], 'text' => 'Inscribir Estudiantes', 'targetruleid' => $ruleids['enrollstudents'], 'icon' => '➕', 'sortorder' => 2, 'enabled' => 1],

        // Backup course options.
        ['ruleid' => $ruleids['backupcourse'], 'text' => 'Menu Profesor', 'targetruleid' => $ruleids['teachermenu'], 'icon' => '👨‍🏫', 'sortorder' => 1, 'enabled' => 1],

        // Teacher menu options.
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Calificar Tareas', 'targetruleid' => $ruleids['gradeassignment'], 'icon' => '📝', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Crear Cuestionario', 'targetruleid' => $ruleids['createquiz'], 'icon' => '❓', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Ver Progreso', 'targetruleid' => $ruleids['studentprogress'], 'icon' => '📈', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Agregar Material', 'targetruleid' => $ruleids['addresources'], 'icon' => '📁', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Inscribir', 'targetruleid' => $ruleids['enrollstudents'], 'icon' => '➕', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['teachermenu'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 6, 'enabled' => 1],

        // =============================================
        // OPCIONES PARA ADMINISTRADORES (v1.9.1)
        // =============================================

        // Create course options.
        ['ruleid' => $ruleids['createcourse'], 'text' => 'Menu Admin', 'targetruleid' => $ruleids['adminmenu'], 'icon' => '⚙️', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['createcourse'], 'text' => 'Gestionar Usuarios', 'targetruleid' => $ruleids['manageusers'], 'icon' => '👥', 'sortorder' => 2, 'enabled' => 1],

        // Manage users options.
        ['ruleid' => $ruleids['manageusers'], 'text' => 'Menu Admin', 'targetruleid' => $ruleids['adminmenu'], 'icon' => '⚙️', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['manageusers'], 'text' => 'Crear Curso', 'targetruleid' => $ruleids['createcourse'], 'icon' => '📚', 'sortorder' => 2, 'enabled' => 1],

        // Site reports options.
        ['ruleid' => $ruleids['sitereports'], 'text' => 'Menu Admin', 'targetruleid' => $ruleids['adminmenu'], 'icon' => '⚙️', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['sitereports'], 'text' => 'Configurar Sitio', 'targetruleid' => $ruleids['siteconfig'], 'icon' => '🔧', 'sortorder' => 2, 'enabled' => 1],

        // Site config options.
        ['ruleid' => $ruleids['siteconfig'], 'text' => 'Menu Admin', 'targetruleid' => $ruleids['adminmenu'], 'icon' => '⚙️', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['siteconfig'], 'text' => 'Instalar Plugin', 'targetruleid' => $ruleids['manageplugins'], 'icon' => '🔌', 'sortorder' => 2, 'enabled' => 1],

        // Manage plugins options.
        ['ruleid' => $ruleids['manageplugins'], 'text' => 'Menu Admin', 'targetruleid' => $ruleids['adminmenu'], 'icon' => '⚙️', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['manageplugins'], 'text' => 'Configurar Sitio', 'targetruleid' => $ruleids['siteconfig'], 'icon' => '🔧', 'sortorder' => 2, 'enabled' => 1],

        // Admin menu options.
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Crear Curso', 'targetruleid' => $ruleids['createcourse'], 'icon' => '📚', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Gestionar Usuarios', 'targetruleid' => $ruleids['manageusers'], 'icon' => '👥', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Ver Reportes', 'targetruleid' => $ruleids['sitereports'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Configuracion', 'targetruleid' => $ruleids['siteconfig'], 'icon' => '🔧', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Plugins', 'targetruleid' => $ruleids['manageplugins'], 'icon' => '🔌', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['adminmenu'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 6, 'enabled' => 1],
    ];

    // Insert all options.
    foreach ($options as $option) {
        $DB->insert_record('local_educambot_option', (object)$option);
    }

    // =============================================
    // SHORTCUTS - Quick access to Moodle features (v1.7.0)
    // =============================================
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

    // Insert all shortcuts.
    foreach ($shortcuts as $shortcut) {
        $DB->insert_record('local_educambot_shortcut', (object)$shortcut);
    }

    // =============================================
    // THEMES - Visual themes for widget (v1.8.0+)
    // =============================================
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
            'widgeticontype' => 'default',
            'widgeticonurl' => null,
            'mascottype' => 'clippy',
            'mascoturl' => null,
            'mascotenabled' => 1,
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
            'widgeticontype' => 'default',
            'widgeticonurl' => null,
            'mascottype' => 'robot',
            'mascoturl' => null,
            'mascotenabled' => 1,
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
            'widgeticontype' => 'default',
            'widgeticonurl' => null,
            'mascottype' => 'owl',
            'mascoturl' => null,
            'mascotenabled' => 1,
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
            'widgeticontype' => 'default',
            'widgeticonurl' => null,
            'mascottype' => 'clippy',
            'mascoturl' => null,
            'mascotenabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
    ];

    // Insert all themes.
    foreach ($themes as $theme) {
        $DB->insert_record('local_educambot_theme', (object)$theme);
    }

    // =============================================
    // SCHEDULE - Default availability (24/7) (v1.8.0)
    // =============================================
    for ($day = 0; $day <= 6; $day++) {
        $DB->insert_record('local_educambot_schedule', (object)[
            'dayofweek' => $day,
            'timefrom' => '00:00',
            'timeto' => '23:59',
            'enabled' => 1,
        ]);
    }

    return true;
}
