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

    // First, create categories (8+ categories required).
    $categories = [
        'navegacion' => [
            'name' => 'Navegacion',
            'description' => 'Preguntas sobre como usar la plataforma',
            'parent' => null,
            'sortorder' => 1,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'general' => [
            'name' => 'General',
            'description' => 'Preguntas generales, saludos y bienvenida',
            'parent' => null,
            'sortorder' => 2,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'cursos' => [
            'name' => 'Cursos',
            'description' => 'Inscripcion, acceso y gestion de cursos',
            'parent' => null,
            'sortorder' => 3,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'tareas' => [
            'name' => 'Tareas y Actividades',
            'description' => 'Entrega de tareas, foros, cuestionarios y actividades',
            'parent' => null,
            'sortorder' => 4,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'evaluaciones' => [
            'name' => 'Evaluaciones',
            'description' => 'Calificaciones, examenes y retroalimentacion',
            'parent' => null,
            'sortorder' => 5,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'perfil' => [
            'name' => 'Perfil y Cuenta',
            'description' => 'Configuracion de perfil, contrasena y preferencias',
            'parent' => null,
            'sortorder' => 6,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'recursos' => [
            'name' => 'Recursos y Materiales',
            'description' => 'Acceso a archivos, videos y materiales de estudio',
            'parent' => null,
            'sortorder' => 7,
            'enabled' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],
        'soporte' => [
            'name' => 'Soporte Tecnico',
            'description' => 'Problemas tecnicos, calendario y comunicacion',
            'parent' => null,
            'sortorder' => 8,
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
        // CATEGORY: NAVEGACION
        // =============================================

        // Platform navigation.
        'platformnav' => [
            'categoryid' => $catids['navegacion'],
            'pattern' => '¿Como navego por la plataforma?',
            'keywords' => "navegar\nnavegacion\nmoverme\nexplorar\nrecorrer\nmenus\nbarra lateral",
            'response' => 'Para navegar por la plataforma Moodle:<br><br>1. <strong>Barra superior:</strong> Acceso rapido a inicio, notificaciones, mensajes y perfil<br>2. <strong>Menu lateral:</strong> Navegacion principal con cursos, calendario y archivos<br>3. <strong>Migas de pan:</strong> Ruta de navegacion para saber donde estas<br>4. <strong>Panel principal:</strong> Tu centro de control con cursos y actividades recientes<br><br><strong>Tip:</strong> El icono de hamburguesa (tres lineas) abre/cierra el menu lateral.',
            'tags' => 'navegacion, menus, explorar, plataforma',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Dashboard panel.
        'dashboard' => [
            'categoryid' => $catids['navegacion'],
            'pattern' => '¿Donde encuentro el panel principal?',
            'keywords' => "panel principal\ndashboard\ntablero\npagina inicio\narea personal\nmi pagina",
            'response' => 'El Panel Principal (Dashboard) es tu pagina de inicio personalizada:<br><br>1. Haz clic en <strong>"Inicio del sitio"</strong> o el logo de la plataforma<br>2. O selecciona <strong>"Area personal"</strong> en el menu de usuario<br><br><strong>En el panel encontraras:</strong><br>- Vista general de cursos<br>- Linea de tiempo con fechas limite<br>- Actividades pendientes<br>- Calendario de eventos<br>- Archivos recientes<br><br>Puedes personalizar los bloques segun tus preferencias.',
            'tags' => 'panel, dashboard, inicio, area personal',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Navigation block.
        'navblock' => [
            'categoryid' => $catids['navegacion'],
            'pattern' => '¿Como uso el bloque de navegacion?',
            'keywords' => "bloque navegacion\nmenu navegacion\nbarra navegacion\nmenú lateral\nnavegacion lateral",
            'response' => 'El bloque de navegacion te permite acceder rapidamente a:<br><br>- <strong>Inicio del sitio:</strong> Pagina principal de la plataforma<br>- <strong>Area personal:</strong> Tu panel de control<br>- <strong>Pagina del sitio:</strong> Informacion general<br>- <strong>Mi perfil:</strong> Tu configuracion personal<br>- <strong>Cursos actuales:</strong> Tus cursos activos<br><br><strong>Tip:</strong> Puedes expandir cada seccion haciendo clic en las flechas.',
            'tags' => 'bloque, navegacion, menu, lateral',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Find activities.
        'findactivities' => [
            'categoryid' => $catids['navegacion'],
            'pattern' => '¿Como encuentro actividades del curso?',
            'keywords' => "encontrar actividades\nver actividades\nlista actividades\nbuscar actividad\nactividades curso",
            'response' => 'Para encontrar actividades dentro de un curso:<br><br>1. <strong>Pagina del curso:</strong> Las actividades aparecen organizadas por temas o semanas<br>2. <strong>Indice del curso:</strong> Menu lateral con todas las secciones<br>3. <strong>Informe de actividad:</strong> Menu > Informes > Informe de actividad<br><br><strong>Iconos comunes:</strong><br>📝 Tareas | 📋 Cuestionarios | 💬 Foros | 📁 Recursos<br><br>Usa el filtro de completado para ver actividades pendientes.',
            'tags' => 'actividades, curso, buscar, encontrar',
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
        // ADDITIONAL RULES TO REACH 50+
        // =============================================

        // Messaging system.
        'messaging' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como funciona la mensajeria?',
            'keywords' => "mensajeria\nsistema mensajes\nmensajes privados\nchat privado\nbandeja entrada",
            'response' => 'El sistema de mensajeria de Moodle te permite comunicarte de forma privada:<br><br><strong>Para acceder:</strong><br>1. Haz clic en el icono de mensaje (burbuja) en la barra superior<br>2. Selecciona un contacto o busca un usuario<br><br><strong>Funciones:</strong><br>- Enviar mensajes privados<br>- Crear grupos de conversacion<br>- Ver historial de conversaciones<br>- Recibir notificaciones de nuevos mensajes<br><br>Puedes configurar las notificaciones en Preferencias > Mensajes.',
            'tags' => 'mensajeria, chat, comunicacion, privado',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Create calendar event.
        'createevent' => [
            'categoryid' => $catids['soporte'],
            'pattern' => '¿Como creo un evento en el calendario?',
            'keywords' => "crear evento\nagregar evento\nnuevo evento\ncalendario personal\nevento personal",
            'response' => 'Para crear un evento personal en el calendario:<br><br>1. Ve al <strong>Calendario</strong><br>2. Haz clic en un dia especifico o en "Nuevo evento"<br>3. Completa los detalles:<br>   - Titulo del evento<br>   - Descripcion<br>   - Fecha y hora<br>   - Tipo de evento (personal, curso, sitio)<br>4. Haz clic en "Guardar"<br><br><strong>Nota:</strong> Solo los profesores pueden crear eventos de curso. Los estudiantes pueden crear eventos personales.',
            'tags' => 'calendario, evento, crear, personal',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Badges.
        'badges' => [
            'categoryid' => $catids['cursos'],
            'pattern' => '¿Que son las insignias?',
            'keywords' => "insignias\nbadges\nlogros\nrecompensas\nmedallas\nreconocimientos",
            'response' => 'Las insignias son reconocimientos digitales por tus logros:<br><br><strong>Tipos de insignias:</strong><br>- Por completar cursos<br>- Por participacion en foros<br>- Por calificaciones destacadas<br>- Por hitos de aprendizaje<br><br><strong>Para ver tus insignias:</strong><br>1. Ve a tu Perfil<br>2. Selecciona "Insignias"<br><br>Las insignias pueden compartirse en redes sociales y algunos empleadores las reconocen como credenciales.',
            'tags' => 'insignias, badges, logros, reconocimiento',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Labels/tags.
        'tags' => [
            'categoryid' => $catids['recursos'],
            'pattern' => '¿Para que sirven las etiquetas?',
            'keywords' => "etiquetas\ntags\nclasificar\norganizar contenido\netiquetas recursos",
            'response' => 'Las etiquetas ayudan a organizar y encontrar contenido:<br><br><strong>Usos comunes:</strong><br>- Clasificar recursos por tema<br>- Encontrar contenido relacionado<br>- Navegar por interes especifico<br><br><strong>Como usar:</strong><br>1. Busca la nube de etiquetas en el sitio<br>2. Haz clic en una etiqueta para ver contenido relacionado<br><br>En tu perfil puedes agregar tus propios intereses como etiquetas personales.',
            'tags' => 'etiquetas, tags, clasificar, buscar',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Workshop activity.
        'workshop' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como funciona un taller?',
            'keywords' => "taller\nworkshop\nevaluacion pares\ncoevaluacion\nevaluar companeros",
            'response' => 'El Taller es una actividad de evaluacion entre pares:<br><br><strong>Fases del taller:</strong><br>1. <strong>Configuracion:</strong> El profesor prepara la actividad<br>2. <strong>Envio:</strong> Envias tu trabajo<br>3. <strong>Evaluacion:</strong> Evaluas trabajos de companeros segun una rubrica<br>4. <strong>Calificacion:</strong> Se calculan las notas<br>5. <strong>Cierre:</strong> Se publican resultados<br><br>Tu nota considera tanto tu trabajo como la calidad de tus evaluaciones.',
            'tags' => 'taller, workshop, evaluacion, pares',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // Chat activity.
        'chatactivity' => [
            'categoryid' => $catids['tareas'],
            'pattern' => '¿Como participo en un chat?',
            'keywords' => "chat\nconversacion\nchat en vivo\nsala de chat\nchat del curso",
            'response' => 'El chat es una herramienta de comunicacion en tiempo real:<br><br><strong>Para participar:</strong><br>1. Accede a la actividad Chat en el curso<br>2. Haz clic en "Entrar a la sala"<br>3. Escribe tu mensaje y presiona Enter<br><br><strong>Caracteristicas:</strong><br>- Comunicacion sincronica<br>- Se guardan las sesiones<br>- Puedes revisar sesiones anteriores<br><br>Los chats suelen tener horarios programados por el profesor.',
            'tags' => 'chat, comunicacion, tiempo real, sincrono',
            'enabled' => 1,
            'showoptions' => 1,
            'timecreated' => $now,
            'timemodified' => $now,
        ],

        // My badges.
        'mybadges' => [
            'categoryid' => $catids['perfil'],
            'pattern' => '¿Donde veo mis insignias?',
            'keywords' => "ver insignias\nmis insignias\ninsignias obtenidas\nmis logros\nmis medallas",
            'response' => 'Para ver tus insignias obtenidas:<br><br>1. Haz clic en tu foto de perfil<br>2. Selecciona "Perfil"<br>3. En el menu lateral, haz clic en "Insignias"<br><br><strong>En la pagina veras:</strong><br>- Insignias ganadas<br>- Fecha de obtencion<br>- Criterios cumplidos<br><br>Puedes compartir tus insignias en redes sociales o descargarlas como imagen.',
            'tags' => 'insignias, logros, perfil, medallas',
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
        ['ruleid' => $ruleids['aboutbot'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['aboutbot'], 'text' => 'Ayuda', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 3, 'enabled' => 1],

        // =============================================
        // NAVIGATION OPTIONS (new category)
        // =============================================

        // Platform navigation options.
        ['ruleid' => $ruleids['platformnav'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['platformnav'], 'text' => 'Panel Principal', 'targetruleid' => $ruleids['dashboard'], 'icon' => '📊', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['platformnav'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['platformnav'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 4, 'enabled' => 1],

        // Dashboard options.
        ['ruleid' => $ruleids['dashboard'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['dashboard'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['dashboard'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['dashboard'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 4, 'enabled' => 1],

        // Navigation block options.
        ['ruleid' => $ruleids['navblock'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['navblock'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['navblock'], 'text' => 'Panel Principal', 'targetruleid' => $ruleids['dashboard'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],

        // Find activities options.
        ['ruleid' => $ruleids['findactivities'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['findactivities'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['findactivities'], 'text' => 'Cuestionarios', 'targetruleid' => $ruleids['quiz'], 'icon' => '✏️', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['findactivities'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['findactivities'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 5, 'enabled' => 1],

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
        ['ruleid' => $ruleids['loginissues'], 'text' => 'Navegadores', 'targetruleid' => $ruleids['browser'], 'icon' => '🌐', 'sortorder' => 4, 'enabled' => 1],

        // =============================================
        // ADDITIONAL OPTIONS TO REACH 200+
        // =============================================

        // Additional startup options.
        ['ruleid' => $ruleids['startup'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 7, 'enabled' => 1],
        ['ruleid' => $ruleids['startup'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 8, 'enabled' => 1],

        // Additional menu options.
        ['ruleid' => $ruleids['menu'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 6, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 7, 'enabled' => 1],
        ['ruleid' => $ruleids['menu'], 'text' => 'Mensajes', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 8, 'enabled' => 1],

        // Additional greeting options.
        ['ruleid' => $ruleids['greeting'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['greeting'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 6, 'enabled' => 1],

        // Additional thanks options.
        ['ruleid' => $ruleids['thanks'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 3, 'enabled' => 1],

        // Additional enrollment options.
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['enrollment'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 5, 'enabled' => 1],

        // Additional my courses options.
        ['ruleid' => $ruleids['mycourses'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['mycourses'], 'text' => 'Certificado', 'targetruleid' => $ruleids['certificate'], 'icon' => '🎓', 'sortorder' => 5, 'enabled' => 1],

        // Additional find courses options.
        ['ruleid' => $ruleids['findcourses'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['findcourses'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 4, 'enabled' => 1],

        // Additional course key options.
        ['ruleid' => $ruleids['coursekey'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['coursekey'], 'text' => 'Buscar Cursos', 'targetruleid' => $ruleids['findcourses'], 'icon' => '🔍', 'sortorder' => 4, 'enabled' => 1],

        // Additional course progress options.
        ['ruleid' => $ruleids['courseprogress'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['courseprogress'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 4, 'enabled' => 1],

        // Additional certificate options.
        ['ruleid' => $ruleids['certificate'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['certificate'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 4, 'enabled' => 1],

        // Additional assignment options.
        ['ruleid' => $ruleids['assignment'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['assignment'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 6, 'enabled' => 1],

        // Additional edit assignment options.
        ['ruleid' => $ruleids['editassignment'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['editassignment'], 'text' => 'Contactar Profesor', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 4, 'enabled' => 1],

        // Additional late submission options.
        ['ruleid' => $ruleids['latesubmission'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['latesubmission'], 'text' => 'Mis Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 4, 'enabled' => 1],

        // Additional forum options.
        ['ruleid' => $ruleids['forum'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['forum'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 5, 'enabled' => 1],

        // Additional forum subscription options.
        ['ruleid' => $ruleids['forumsub'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['forumsub'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 4, 'enabled' => 1],

        // Additional wiki options.
        ['ruleid' => $ruleids['wiki'], 'text' => 'Glosario', 'targetruleid' => $ruleids['glossary'], 'icon' => '📖', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['wiki'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 3, 'enabled' => 1],

        // Additional glossary options.
        ['ruleid' => $ruleids['glossary'], 'text' => 'Wiki', 'targetruleid' => $ruleids['wiki'], 'icon' => '📄', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['glossary'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 3, 'enabled' => 1],

        // Additional grades options.
        ['ruleid' => $ruleids['grades'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['grades'], 'text' => 'Progreso', 'targetruleid' => $ruleids['courseprogress'], 'icon' => '📈', 'sortorder' => 6, 'enabled' => 1],

        // Additional quiz options.
        ['ruleid' => $ruleids['quiz'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['quiz'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 6, 'enabled' => 1],

        // Additional quiz attempts options.
        ['ruleid' => $ruleids['quizattempts'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['quizattempts'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 4, 'enabled' => 1],

        // Additional quiz review options.
        ['ruleid' => $ruleids['quizreview'], 'text' => 'Hacer Examen', 'targetruleid' => $ruleids['quiz'], 'icon' => '✏️', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['quizreview'], 'text' => 'Retroalimentacion', 'targetruleid' => $ruleids['feedback'], 'icon' => '💬', 'sortorder' => 4, 'enabled' => 1],

        // Additional grade appeal options.
        ['ruleid' => $ruleids['gradeappeal'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['gradeappeal'], 'text' => 'Retroalimentacion', 'targetruleid' => $ruleids['feedback'], 'icon' => '💬', 'sortorder' => 4, 'enabled' => 1],

        // Additional feedback options.
        ['ruleid' => $ruleids['feedback'], 'text' => 'Contactar Profesor', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['feedback'], 'text' => 'Reclamar Nota', 'targetruleid' => $ruleids['gradeappeal'], 'icon' => '⚖️', 'sortorder' => 4, 'enabled' => 1],

        // Additional profile options.
        ['ruleid' => $ruleids['profile'], 'text' => 'Idioma', 'targetruleid' => $ruleids['language'], 'icon' => '🌍', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['profile'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 6, 'enabled' => 1],

        // Additional profile picture options.
        ['ruleid' => $ruleids['profilepic'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['profilepic'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 4, 'enabled' => 1],

        // Additional password options.
        ['ruleid' => $ruleids['password'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['password'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 4, 'enabled' => 1],

        // Additional notifications options.
        ['ruleid' => $ruleids['notifications'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['notifications'], 'text' => 'Mensajes', 'targetruleid' => $ruleids['messages'], 'icon' => '✉️', 'sortorder' => 4, 'enabled' => 1],

        // Additional language options.
        ['ruleid' => $ruleids['language'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['language'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 3, 'enabled' => 1],

        // Additional download files options.
        ['ruleid' => $ruleids['downloadfiles'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['downloadfiles'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 4, 'enabled' => 1],

        // Additional videos options.
        ['ruleid' => $ruleids['videos'], 'text' => 'H5P', 'targetruleid' => $ruleids['h5p'], 'icon' => '🎮', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['videos'], 'text' => 'SCORM', 'targetruleid' => $ruleids['scorm'], 'icon' => '📦', 'sortorder' => 5, 'enabled' => 1],

        // Additional SCORM options.
        ['ruleid' => $ruleids['scorm'], 'text' => 'H5P', 'targetruleid' => $ruleids['h5p'], 'icon' => '🎮', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['scorm'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 4, 'enabled' => 1],

        // Additional H5P options.
        ['ruleid' => $ruleids['h5p'], 'text' => 'SCORM', 'targetruleid' => $ruleids['scorm'], 'icon' => '📦', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['h5p'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['h5p'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 4, 'enabled' => 1],

        // Additional calendar options.
        ['ruleid' => $ruleids['calendar'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['calendar'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['calendar'], 'text' => 'Panel Principal', 'targetruleid' => $ruleids['dashboard'], 'icon' => '📊', 'sortorder' => 5, 'enabled' => 1],

        // Additional messages options.
        ['ruleid' => $ruleids['messages'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['messages'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['messages'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 5, 'enabled' => 1],

        // Additional support options.
        ['ruleid' => $ruleids['support'], 'text' => 'Videos', 'targetruleid' => $ruleids['videos'], 'icon' => '🎬', 'sortorder' => 5, 'enabled' => 1],
        ['ruleid' => $ruleids['support'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 6, 'enabled' => 1],

        // Additional browser options.
        ['ruleid' => $ruleids['browser'], 'text' => 'Videos', 'targetruleid' => $ruleids['videos'], 'icon' => '🎬', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['browser'], 'text' => 'App Movil', 'targetruleid' => $ruleids['mobileapp'], 'icon' => '📱', 'sortorder' => 4, 'enabled' => 1],

        // Additional mobile app options.
        ['ruleid' => $ruleids['mobileapp'], 'text' => 'Navegadores', 'targetruleid' => $ruleids['browser'], 'icon' => '🌐', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['mobileapp'], 'text' => 'Soporte', 'targetruleid' => $ruleids['support'], 'icon' => '🆘', 'sortorder' => 4, 'enabled' => 1],
        ['ruleid' => $ruleids['mobileapp'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 5, 'enabled' => 1],

        // =============================================
        // OPTIONS FOR NEW RULES (to reach 50+ rules)
        // =============================================

        // Messaging options.
        ['ruleid' => $ruleids['messaging'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['messaging'], 'text' => 'Notificaciones', 'targetruleid' => $ruleids['notifications'], 'icon' => '🔔', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['messaging'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['messaging'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 4, 'enabled' => 1],

        // Create event options.
        ['ruleid' => $ruleids['createevent'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['createevent'], 'text' => 'Calendario', 'targetruleid' => $ruleids['calendar'], 'icon' => '📅', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['createevent'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['createevent'], 'text' => 'Panel Principal', 'targetruleid' => $ruleids['dashboard'], 'icon' => '📊', 'sortorder' => 4, 'enabled' => 1],

        // Badges options.
        ['ruleid' => $ruleids['badges'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['badges'], 'text' => 'Mis Insignias', 'targetruleid' => $ruleids['mybadges'], 'icon' => '🏅', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['badges'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['badges'], 'text' => 'Progreso', 'targetruleid' => $ruleids['courseprogress'], 'icon' => '📈', 'sortorder' => 4, 'enabled' => 1],

        // Tags options.
        ['ruleid' => $ruleids['tags'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['tags'], 'text' => 'Buscar Cursos', 'targetruleid' => $ruleids['findcourses'], 'icon' => '🔍', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['tags'], 'text' => 'Mis Cursos', 'targetruleid' => $ruleids['mycourses'], 'icon' => '📚', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['tags'], 'text' => 'Navegacion', 'targetruleid' => $ruleids['platformnav'], 'icon' => '🧭', 'sortorder' => 4, 'enabled' => 1],

        // Workshop options.
        ['ruleid' => $ruleids['workshop'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['workshop'], 'text' => 'Tareas', 'targetruleid' => $ruleids['assignment'], 'icon' => '📝', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['workshop'], 'text' => 'Calificaciones', 'targetruleid' => $ruleids['grades'], 'icon' => '📊', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['workshop'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 4, 'enabled' => 1],

        // Chat activity options.
        ['ruleid' => $ruleids['chatactivity'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['chatactivity'], 'text' => 'Mensajeria', 'targetruleid' => $ruleids['messaging'], 'icon' => '✉️', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['chatactivity'], 'text' => 'Foros', 'targetruleid' => $ruleids['forum'], 'icon' => '💬', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['chatactivity'], 'text' => 'Actividades', 'targetruleid' => $ruleids['findactivities'], 'icon' => '📝', 'sortorder' => 4, 'enabled' => 1],

        // My badges options.
        ['ruleid' => $ruleids['mybadges'], 'text' => 'Menu Principal', 'targetruleid' => $ruleids['menu'], 'icon' => '🏠', 'sortorder' => 1, 'enabled' => 1],
        ['ruleid' => $ruleids['mybadges'], 'text' => 'Insignias', 'targetruleid' => $ruleids['badges'], 'icon' => '🏆', 'sortorder' => 2, 'enabled' => 1],
        ['ruleid' => $ruleids['mybadges'], 'text' => 'Perfil', 'targetruleid' => $ruleids['profile'], 'icon' => '👤', 'sortorder' => 3, 'enabled' => 1],
        ['ruleid' => $ruleids['mybadges'], 'text' => 'Progreso', 'targetruleid' => $ruleids['courseprogress'], 'icon' => '📈', 'sortorder' => 4, 'enabled' => 1],
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
        [
            'name' => 'Soporte tecnico',
            'keywords' => "soporte\nayuda tecnica\nproblemas tecnicos\ncontactar soporte\nasistencia tecnica",
            'actiontype' => 'support',
            'description' => 'Contactar soporte tecnico',
            'icon' => '🆘',
            'sortorder' => 8,
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

    // =============================================
    // VALIDATION - Check completeness of knowledge base
    // =============================================
    $rulecount = $DB->count_records('local_educambot_rule');
    $categorycount = $DB->count_records('local_educambot_category');
    $optioncount = $DB->count_records('local_educambot_option');
    $shortcutcount = $DB->count_records('local_educambot_shortcut');

    if ($rulecount < 50 || $categorycount < 8 || $optioncount < 200 || $shortcutcount < 8) {
        debugging('Educambot: Warning - Knowledge base may be incomplete. ' .
                  'Rules: ' . $rulecount . '/50, ' .
                  'Categories: ' . $categorycount . '/8, ' .
                  'Options: ' . $optioncount . '/200, ' .
                  'Shortcuts: ' . $shortcutcount . '/8',
                  DEBUG_DEVELOPER);
    }

    return true;
}
