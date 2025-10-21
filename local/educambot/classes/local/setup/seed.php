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
 * Seeds initial rules and knowledge entries for Educam Bot.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\local\setup;

use coding_exception;
use core_text;
use dml_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Populates the plugin with an initial catalogue of FAQs and knowledge.
 */
class seed {
    /**
     * Inserts default data when the knowledge base is empty.
     *
     * @throws dml_exception
     * @throws coding_exception
     */
    public static function seed_initial_data(): void {
        global $DB;

        $now = time();
        $transaction = $DB->start_delegated_transaction();

        $topicmap = self::ensure_topics_exist($now);

        if (!$DB->record_exists('local_educambot_knowledge', [])) {
            $knowledgeids = self::create_knowledge($now, $topicmap);
        } else {
            $knowledgeids = $DB->get_records_menu('local_educambot_knowledge', null, '', 'title, id');
        }

        if (!$DB->record_exists('local_educambot_rule', [])) {
            self::create_rules($now);
        }

        // Relations are created as part of create_knowledge when the table is empty.
        $transaction->allow_commit();
    }

    /**
     * Returns the base definition of the expected topic hierarchy.
     *
     * @return array<string,array>
     */
    protected static function expected_topics(): array {
        return [
            'getting_started' => [
                'name' => 'Primeros pasos en Moodle',
                'description' => 'Acceso, perfil y navegación inicial para estudiantes y docentes.',
            ],
            'assignments' => [
                'name' => 'Tareas y entregas',
                'description' => 'Entregas, retroalimentación y seguimiento de actividades evaluables.',
            ],
            'grades' => [
                'name' => 'Calificaciones y seguimiento',
                'description' => 'Cómo revisar calificaciones, reportes y comentarios.',
            ],
            'communication' => [
                'name' => 'Comunicación y foros',
                'description' => 'Opciones de interacción con docentes y compañeros.',
            ],
            'courses' => [
                'name' => 'Gestión de cursos',
                'description' => 'Organización de cursos, secciones y contenidos.',
            ],
            'support' => [
                'name' => 'Soporte técnico',
                'description' => 'Resolución de incidencias frecuentes en la plataforma.',
            ],
        ];
    }

    /**
     * Ensures default topics exist and returns a map keyed by identifier.
     *
     * @param int $now
     * @return array<string,int>
     * @throws dml_exception
     */
    protected static function ensure_topics_exist(int $now): array {
        global $DB;

        $existing = $DB->get_records('local_educambot_topic', null, '', 'id, name, description');
        $expected = self::expected_topics();
        $topicmap = [];

        foreach ($existing as $record) {
            $normalized = core_text::strtolower(trim($record->name));
            foreach ($expected as $key => $topic) {
                if ($normalized === core_text::strtolower($topic['name'])) {
                    $topicmap[$key] = $record->id;
                    break;
                }
            }
        }

        foreach ($expected as $key => $topic) {
            if (!isset($topicmap[$key])) {
                $record = (object) [
                    'name' => $topic['name'],
                    'description' => $topic['description'],
                    'parentid' => null,
                    'sortorder' => 0,
                    'timecreated' => $now,
                    'timemodified' => $now,
                ];
                $topicmap[$key] = $DB->insert_record('local_educambot_topic', $record);
            }
        }

        return $topicmap;
    }

    /**
     * Creates knowledge entries with associated metadata.
     *
     * @param int $now
     * @param array<string,int> $topicmap
     * @return array<string,int>
     * @throws dml_exception
     */
    protected static function create_knowledge(int $now, array $topicmap): array {
        global $DB;

        $knowledgeentries = [
            'access_courses' => [
                'title' => 'Acceder a tus cursos',
                'summary' => '<p>Encuentra tus cursos activos desde el menú <strong>Mis cursos</strong> y fija los más importantes como favoritos.</p>',
                'content' => '<ol>' .
                    '<li>Haz clic en tu nombre o avatar en la barra superior y selecciona <strong>Mis cursos</strong>.</li>' .
                    '<li>Usa los filtros <em>En progreso</em>, <em>Futuros</em> o <em>Pasados</em> para localizar la asignatura.</li>' .
                    '<li>Marca el curso con la estrella para anclarlo en la página de inicio.</li>' .
                    '<li>Si accedes con un dispositivo móvil, instala la app oficial de Moodle e inicia sesión con tus credenciales institucionales.</li>' .
                    '</ol>',
                'tags' => 'acceso, cursos, inicio',
                'topics' => ['getting_started', 'courses'],
                'contexts' => [
                    ['courseid' => null, 'role' => 'student', 'pagecontext' => 'my/courses'],
                ],
            ],
            'recover_password' => [
                'title' => 'Recuperar la contraseña de Moodle',
                'summary' => '<p>Si olvidaste la contraseña, puedes restablecerla desde la página de acceso utilizando tu correo institucional.</p>',
                'content' => '<p>Visita la página de inicio de sesión y selecciona <strong>¿Olvidó su nombre de usuario o contraseña?</strong>. Ingresa tu correo institucional y revisa la bandeja de entrada para completar el proceso. Si no recibes el mensaje en unos minutos, revisa el correo no deseado o contacta a soporte indicando tu usuario y número de identificación.</p>',
                'tags' => 'contraseña, acceso, soporte',
                'topics' => ['getting_started', 'support'],
                'contexts' => [],
            ],
            'submit_assignment' => [
                'title' => 'Entregar una tarea en Moodle',
                'summary' => '<p>Sigue estos pasos para enviar archivos o texto en una actividad de tarea y asegúrate de confirmar la entrega.</p>',
                'content' => '<ol>' .
                    '<li>Ingresa al curso correspondiente y selecciona la tarea.</li>' .
                    '<li>Lee las instrucciones, la fecha límite y el tipo de entrega permitido.</li>' .
                    '<li>Pulsa <strong>Agregar entrega</strong>, sube los archivos requeridos o escribe en el editor.</li>' .
                    '<li>Haz clic en <strong>Guardar cambios</strong> y luego en <strong>Enviar tarea</strong> para finalizar.</li>' .
                    '<li>Verifica que el estado cambie a <em>Enviado para calificar</em>.</li>' .
                    '</ol>',
                'tags' => 'tareas, entregas, estudiantes',
                'topics' => ['assignments'],
                'contexts' => [
                    ['courseid' => null, 'role' => 'student', 'pagecontext' => 'mod/assign'],
                ],
            ],
            'check_grades' => [
                'title' => 'Consultar tus calificaciones',
                'summary' => '<p>Revisa tus calificaciones globales o por curso y descarga reportes detallados si están habilitados.</p>',
                'content' => '<p>Desde la página del curso, abre el menú de navegación y selecciona <strong>Calificaciones</strong>. Podrás ver la nota obtenida, la escala utilizada y los comentarios del docente. En la página <strong>Área personal &gt; Resumen de calificaciones</strong> tienes una vista consolidada de todas tus asignaturas.</p>',
                'tags' => 'calificaciones, seguimiento, reportes',
                'topics' => ['grades'],
                'contexts' => [
                    ['courseid' => null, 'role' => 'student', 'pagecontext' => 'grade/report'],
                ],
            ],
            'participate_forum' => [
                'title' => 'Participar en un foro de Moodle',
                'summary' => '<p>Aprende a crear discusiones, responder a tus compañeros y activar notificaciones en los foros.</p>',
                'content' => '<ol>' .
                    '<li>Entra al foro del curso y pulsa <strong>Agregar un nuevo tema de discusión</strong>.</li>' .
                    '<li>Escribe un asunto descriptivo y desarrolla tu aporte en el editor.</li>' .
                    '<li>Adjunta archivos si el docente lo permite y publica el mensaje.</li>' .
                    '<li>Para responder a otra persona, utiliza <strong>Responder</strong> bajo su intervención.</li>' .
                    '<li>Activa la suscripción al foro para recibir notificaciones por correo.</li>' .
                    '</ol>',
                'tags' => 'foros, comunicación, participación',
                'topics' => ['communication'],
                'contexts' => [
                    ['courseid' => null, 'role' => null, 'pagecontext' => 'mod/forum'],
                ],
            ],
            'missing_course' => [
                'title' => 'No veo mi curso en Moodle',
                'summary' => '<p>Verifica tu matrícula, la fecha de inicio del curso y el estado de tu usuario antes de contactar a soporte.</p>',
                'content' => '<ul>' .
                    '<li>Confirma con el docente que estés matriculado en el curso.</li>' .
                    '<li>Revisa si la fecha de inicio del curso aún no ha llegado.</li>' .
                    '<li>Comprueba que tu usuario esté activo y sin bloqueos.</li>' .
                    '<li>Si sigue sin aparecer, abre un ticket a soporte adjuntando capturas y el nombre del curso.</li>' .
                    '</ul>',
                'tags' => 'matrícula, soporte, acceso',
                'topics' => ['support', 'courses'],
                'contexts' => [],
            ],
            'upcoming_events' => [
                'title' => 'Consultar actividades próximas',
                'summary' => '<p>El bloque de línea de tiempo y el calendario muestran las entregas y eventos ordenados por fecha.</p>',
                'content' => '<p>Desde tu Área personal abre el bloque <strong>Línea de tiempo</strong> y filtra por <em>Próximos</em> para ver las entregas más cercanas. También puedes entrar al <strong>Calendario</strong>, habilitar los filtros por curso y suscribirte para recibir recordatorios en tu correo.</p>',
                'tags' => 'calendario, recordatorios, actividades',
                'topics' => ['assignments', 'grades'],
                'contexts' => [
                    ['courseid' => null, 'role' => 'student', 'pagecontext' => 'my'],
                ],
            ],
            'course_overview' => [
                'title' => 'Explorar la estructura de un curso',
                'summary' => '<p>Cada curso se organiza en secciones con recursos, actividades y un resumen inicial.</p>',
                'content' => '<p>Al ingresar a un curso, utiliza el índice lateral para desplazarte rápidamente entre secciones. Revisa los recursos de introducción, el plan de trabajo y las actividades obligatorias. El resumen de cada sección indica el propósito y los materiales clave.</p>',
                'tags' => 'estructura, secciones, navegación',
                'topics' => ['courses'],
                'contexts' => [
                    ['courseid' => null, 'role' => null, 'pagecontext' => 'course/view.php'],
                ],
            ],
            'contact_teacher' => [
                'title' => 'Contactar a tu docente',
                'summary' => '<p>Usa la mensajería de Moodle, los foros o el correo institucional para comunicarte con el profesorado.</p>',
                'content' => '<p>Desde la página del curso, ve a <strong>Participantes</strong>, ubica el nombre del docente y selecciona <strong>Mensaje</strong>. También puedes publicar en el foro de avisos o utilizar la información de contacto indicada en la sección de bienvenida.</p>',
                'tags' => 'docentes, comunicación, soporte',
                'topics' => ['communication'],
                'contexts' => [
                    ['courseid' => null, 'role' => null, 'pagecontext' => 'user/index.php'],
                ],
            ],
            'multimedia_support' => [
                'title' => 'Reproducir videos y paquetes SCORM',
                'summary' => '<p>Actualiza tu navegador, habilita ventanas emergentes y verifica la conexión para visualizar contenidos multimedia.</p>',
                'content' => '<ul>' .
                    '<li>Utiliza navegadores actualizados como Chrome o Firefox.</li>' .
                    '<li>Permite las ventanas emergentes para el sitio de Moodle.</li>' .
                    '<li>Borra la caché si un SCORM no carga y vuelve a intentarlo.</li>' .
                    '<li>Consulta el soporte técnico si el material requiere plugins específicos.</li>' .
                    '</ul>',
                'tags' => 'scorm, video, soporte técnico',
                'topics' => ['support'],
                'contexts' => [],
            ],
            'review_feedback' => [
                'title' => 'Revisar la retroalimentación de una tarea',
                'summary' => '<p>Después de calificar una tarea, revisa los comentarios, archivos adjuntos y rúbricas publicadas por el docente.</p>',
                'content' => '<ol>' .
                    '<li>Ingresa nuevamente a la tarea luego de recibir la notificación de calificación.</li>' .
                    '<li>En la sección <strong>Comentarios de la entrega</strong> lee el mensaje del docente.</li>' .
                    '<li>Descarga los archivos adjuntos con anotaciones o rúbricas evaluadas.</li>' .
                    '<li>Si tienes dudas, responde desde la misma tarea o envía un mensaje privado.</li>' .
                    '</ol>',
                'tags' => 'retroalimentación, tareas, seguimiento',
                'topics' => ['assignments', 'grades'],
                'contexts' => [
                    ['courseid' => null, 'role' => 'student', 'pagecontext' => 'mod/assign/view.php'],
                ],
            ],
            'organize_calendar' => [
                'title' => 'Organizar tu calendario académico',
                'summary' => '<p>Sincroniza el calendario de Moodle con tu agenda personal y configura recordatorios automáticos.</p>',
                'content' => '<p>En el calendario selecciona <strong>Exportar calendario</strong> para generar una URL iCal y agrégala en Google Calendar o Outlook. Activa las notificaciones de eventos desde <strong>Preferencias &gt; Preferencias de notificaciones</strong> para recibir alertas en tu correo electrónico.</p>',
                'tags' => 'calendario, notificaciones, productividad',
                'topics' => ['grades', 'assignments'],
                'contexts' => [],
            ],
            'mobile_app' => [
                'title' => 'Usar Moodle desde la aplicación móvil',
                'summary' => '<p>Descarga la app oficial, introduce la URL del campus y habilita las notificaciones push.</p>',
                'content' => '<ol>' .
                    '<li>Descarga la app <strong>Moodle</strong> desde Google Play o App Store.</li>' .
                    '<li>Introduce la URL del campus virtual institucional cuando se solicite.</li>' .
                    '<li>Accede con tu usuario y contraseña habituales.</li>' .
                    '<li>En <strong>Ajustes</strong> activa las notificaciones push para recibir avisos de nuevas calificaciones y mensajes.</li>' .
                    '</ol>',
                'tags' => 'móvil, acceso, notificaciones',
                'topics' => ['getting_started', 'support'],
                'contexts' => [],
            ],
        ];

        $knowledgeids = [];

        foreach ($knowledgeentries as $key => $entry) {
            $record = (object) [
                'title' => $entry['title'],
                'summary' => $entry['summary'],
                'content' => $entry['content'],
                'contentformat' => FORMAT_HTML,
                'type' => 'guide',
                'externalurl' => null,
                'tags' => $entry['tags'],
                'enabled' => 1,
                'createdby' => null,
                'updatedby' => null,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $knowledgeid = $DB->insert_record('local_educambot_knowledge', $record);
            $knowledgeids[$key] = $knowledgeid;

            if (!empty($entry['topics'])) {
                foreach ($entry['topics'] as $topickey) {
                    if (empty($topicmap[$topickey])) {
                        continue;
                    }
                    $link = (object) [
                        'knowledgeid' => $knowledgeid,
                        'topicid' => $topicmap[$topickey],
                    ];
                    $DB->insert_record('local_educambot_kn_topic', $link);
                }
            }

            if (!empty($entry['contexts'])) {
                foreach ($entry['contexts'] as $context) {
                    $contextrecord = (object) [
                        'knowledgeid' => $knowledgeid,
                        'courseid' => $context['courseid'] ?? null,
                        'role' => $context['role'] ?? null,
                        'pagecontext' => $context['pagecontext'] ?? null,
                    ];
                    $DB->insert_record('local_educambot_kn_context', $contextrecord);
                }
            }
        }

        $relations = [
            ['source' => 'access_courses', 'target' => 'missing_course', 'type' => 'soporte'],
            ['source' => 'submit_assignment', 'target' => 'review_feedback', 'type' => 'seguimiento'],
            ['source' => 'check_grades', 'target' => 'review_feedback', 'type' => 'retroalimentación'],
            ['source' => 'upcoming_events', 'target' => 'organize_calendar', 'type' => 'planificación'],
            ['source' => 'course_overview', 'target' => 'upcoming_events', 'type' => 'contexto'],
            ['source' => 'contact_teacher', 'target' => 'participate_forum', 'type' => 'comunicación'],
            ['source' => 'mobile_app', 'target' => 'access_courses', 'type' => 'alternativa'],
            ['source' => 'multimedia_support', 'target' => 'mobile_app', 'type' => 'soporte'],
        ];

        foreach ($relations as $relation) {
            if (empty($knowledgeids[$relation['source']]) || empty($knowledgeids[$relation['target']])) {
                continue;
            }
            $record = (object) [
                'sourceid' => $knowledgeids[$relation['source']],
                'targetid' => $knowledgeids[$relation['target']],
                'relationtype' => $relation['type'],
            ];
            $DB->insert_record('local_educambot_relation', $record);
        }

        return $knowledgeids;
    }

    /**
     * Creates the default set of rule-based FAQs.
     *
     * @param int $now
     * @throws dml_exception
     */
    protected static function create_rules(int $now): void {
        global $DB;

        $rules = [
            [
                'pattern' => '¿Cómo accedo a mis cursos?',
                'synonyms' => [
                    'No encuentro la página Mis cursos',
                    'How do I access my courses?',
                    'Ingresar a mis clases',
                ],
                'keywords' => 'acceso, cursos, mis cursos',
                'response' => '<p>Hola {{userfirstname}}, puedes abrir tus asignaturas desde el menú <strong>Mis cursos</strong> o con la lista que ves a continuación:</p>{{courselist}}<p>Si necesitas más detalles consulta el artículo <em>Acceder a tus cursos</em> en la base de conocimiento.</p>',
                'roles' => ['student'],
                'contexts' => ['my/'],
                'suggested' => true,
            ],
            [
                'pattern' => 'Olvidé mi contraseña',
                'synonyms' => [
                    'Restablecer contraseña de Moodle',
                    'I forgot my password',
                ],
                'keywords' => 'contraseña, recuperar, acceso',
                'response' => '<p>No te preocupes {{userfirstname}}. En la pantalla de acceso selecciona <strong>¿Olvidó su nombre de usuario o contraseña?</strong> e introduce tu correo institucional. Revisa tu bandeja de entrada y sigue el enlace para crear una nueva contraseña.</p>',
                'roles' => null,
                'contexts' => ['login/index.php'],
                'suggested' => true,
            ],
            [
                'pattern' => '¿Cómo entrego una tarea?',
                'synonyms' => [
                    'Enviar mi tarea',
                    'Subir una asignación',
                    'How do I submit an assignment?',
                ],
                'keywords' => 'tarea, entrega, assignment',
                'response' => '<p>Para enviar tu trabajo entra a la actividad, pulsa <strong>Agregar entrega</strong>, adjunta los archivos necesarios y finaliza con <strong>Enviar tarea</strong>. Recibirás un mensaje de confirmación cuando quede registrada.</p>',
                'roles' => ['student'],
                'contexts' => ['mod/assign'],
                'suggested' => true,
            ],
            [
                'pattern' => '¿Dónde veo mis calificaciones?',
                'synonyms' => [
                    'Ver notas del curso',
                    'Consultar calificaciones',
                    'Where can I see my grades?',
                ],
                'keywords' => 'calificaciones, notas, grades',
                'response' => '<p>Desde el menú del curso elige <strong>Calificaciones</strong> para ver los detalles por actividad. También puedes entrar a <strong>Área personal &gt; Resumen de calificaciones</strong> para un panorama general.</p>',
                'roles' => ['student'],
                'contexts' => ['grade/report'],
                'suggested' => true,
            ],
            [
                'pattern' => '¿Cómo participo en un foro?',
                'synonyms' => [
                    'Publicar en el foro',
                    'Responder en un foro',
                    'How do I post in a forum?',
                ],
                'keywords' => 'foro, discusión, mensaje',
                'response' => '<p>Abre el foro del curso, selecciona <strong>Añadir un nuevo tema</strong> o pulsa <strong>Responder</strong> para intervenir en un hilo existente. Activa la suscripción si quieres recibir avisos en tu correo.</p>',
                'roles' => null,
                'contexts' => ['mod/forum'],
                'suggested' => false,
            ],
            [
                'pattern' => 'No encuentro mi curso',
                'synonyms' => [
                    'Mi curso no aparece',
                    'Course missing',
                ],
                'keywords' => 'curso, desapareció, matrícula',
                'response' => '<p>Verifica con el docente que estés matriculado y revisa la fecha de inicio del curso. Si el problema continúa contacta a soporte con el nombre de la asignatura y una captura de tu listado de cursos.</p>',
                'roles' => null,
                'contexts' => ['my/'],
                'suggested' => true,
            ],
            [
                'pattern' => '¿Qué actividades tengo próximas?',
                'synonyms' => [
                    'Próximas entregas',
                    'Actividades pendientes',
                    'Upcoming deadlines',
                ],
                'keywords' => 'próximas, entregas, calendario',
                'response' => '<p>{{userfirstname}}, tu siguiente actividad es: <strong>{{nextdue}}</strong>. Revisa el bloque de línea de tiempo o el calendario para ver todo el detalle.</p>{{pendingactivities}}',
                'roles' => ['student'],
                'contexts' => ['my', 'course/view'],
                'suggested' => true,
            ],
            [
                'pattern' => 'Dame información del curso',
                'synonyms' => [
                    'Resumen del curso',
                    'Información de la asignatura',
                    'Course overview',
                ],
                'keywords' => 'curso, secciones, información',
                'response' => '<p>Este es el resumen de <strong>{{focuscourse}}</strong>:</p>{{focuscourse_summary}}<p>Secciones disponibles:</p>{{focuscourse_sections}}',
                'roles' => null,
                'contexts' => ['course/view'],
                'suggested' => false,
            ],
            [
                'pattern' => '¿Cómo contacto a mi profesor?',
                'synonyms' => [
                    'Enviar mensaje al docente',
                    'Contact teacher',
                ],
                'keywords' => 'docente, mensaje, contacto',
                'response' => '<p>Desde la pestaña <strong>Participantes</strong> busca al docente y usa la opción <strong>Mensaje</strong>. También puedes escribir en el foro de avisos o utilizar el correo institucional indicado en la bienvenida del curso.</p>',
                'roles' => null,
                'contexts' => ['user/index.php'],
                'suggested' => false,
            ],
            [
                'pattern' => 'No puedo ver un video o SCORM',
                'synonyms' => [
                    'Problemas con SCORM',
                    'The video does not load',
                ],
                'keywords' => 'video, scorm, soporte',
                'response' => '<p>Prueba con un navegador actualizado, permite ventanas emergentes para Moodle y borra la caché del navegador. Si el problema continúa envía un ticket a soporte indicando el curso y adjuntando una captura del error.</p>',
                'roles' => null,
                'contexts' => ['mod/scorm', 'mod/resource'],
                'suggested' => false,
            ],
            [
                'pattern' => '¿Puedo usar Moodle en el móvil?',
                'synonyms' => [
                    'Aplicación móvil Moodle',
                    'Moodle app',
                ],
                'keywords' => 'móvil, app, notificaciones',
                'response' => '<p>Sí. Descarga la app oficial <strong>Moodle</strong>, ingresa la URL del campus e inicia sesión con tus credenciales. Activa las notificaciones push para recibir avisos de mensajes y calificaciones.</p>',
                'roles' => null,
                'contexts' => [],
                'suggested' => false,
            ],
        ];

        foreach ($rules as $rule) {
            $record = (object) [
                'pattern' => $rule['pattern'],
                'synonyms' => !empty($rule['synonyms']) ? implode("\n", $rule['synonyms']) : null,
                'keywords' => $rule['keywords'],
                'response' => $rule['response'],
                'roles' => !empty($rule['roles']) ? implode(',', $rule['roles']) : null,
                'contexts' => !empty($rule['contexts']) ? implode("\n", $rule['contexts']) : null,
                'suggested' => !empty($rule['suggested']) ? 1 : 0,
                'enabled' => 1,
                'timecreated' => $now,
                'timemodified' => $now,
            ];
            $DB->insert_record('local_educambot_rule', $record);
        }
    }
}
