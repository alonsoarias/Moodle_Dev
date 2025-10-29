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
 * CLI script to add sample data to Educam Bot for testing purposes.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Get CLI options.
list($options, $unrecognized) = cli_get_params(
    ['help' => false, 'force' => false],
    ['h' => 'help', 'f' => 'force']
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo "Add sample data to Educam Bot for testing purposes.

Options:
-h, --help          Print this help
-f, --force         Force adding data even if entries already exist

Example:
\$ php local/educambot/cli/add_sample_data.php
\$ php local/educambot/cli/add_sample_data.php --force
";
    exit(0);
}

global $DB;

// Check if data already exists.
$existingcount = $DB->count_records('local_educambot_rule');
if ($existingcount > 0 && !$options['force']) {
    cli_writeln("Sample data already exists ($existingcount rules found). Use --force to add anyway.");
    exit(1);
}

cli_writeln("Adding sample data to Educam Bot...");

$now = time();

// Create a topic.
cli_writeln("Creating topic...");
$topic = new stdClass();
$topic->name = 'Primeros Pasos';
$topic->description = 'Información básica para comenzar a usar Moodle';
$topic->parentid = null;
$topic->sortorder = 1;
$topic->timecreated = $now;
$topic->timemodified = $now;
$topicid = $DB->insert_record('local_educambot_topic', $topic);
cli_writeln("  Topic created with ID: $topicid");

// Create sample rules.
cli_writeln("Creating sample rules...");

$rules = [
    [
        'pattern' => "¿Cómo accedo a la plataforma?\n¿Cómo ingreso a Moodle?\n¿Cómo entro al campus virtual?",
        'synonyms' => "ingresar\nacceder\nentrar\nloguearse",
        'keywords' => 'acceso, login, ingresar, plataforma',
        'response' => '<p>Para acceder a la plataforma, sigue estos pasos:</p>' .
            '<ol>' .
            '<li>Ve a la página principal de Moodle</li>' .
            '<li>Haz clic en "Iniciar sesión" en la esquina superior derecha</li>' .
            '<li>Ingresa tu nombre de usuario y contraseña</li>' .
            '<li>Haz clic en "Entrar"</li>' .
            '</ol>' .
            '<p>Si olvidaste tu contraseña, haz clic en "¿Olvidó su nombre de usuario o contraseña?"</p>',
        'suggested' => 1,
    ],
    [
        'pattern' => "¿Cómo subo una tarea?\n¿Cómo envío mi tarea?\n¿Dónde entrego las tareas?",
        'synonyms' => "enviar\nsubir\nentregar\ncargar",
        'keywords' => 'tarea, enviar, subir, entregar',
        'response' => '<p>Para subir una tarea en Moodle:</p>' .
            '<ol>' .
            '<li>Accede al curso correspondiente</li>' .
            '<li>Busca la actividad de "Tarea" en la sección correspondiente</li>' .
            '<li>Haz clic en el nombre de la tarea</li>' .
            '<li>Haz clic en "Agregar entrega"</li>' .
            '<li>Arrastra tu archivo o haz clic en el ícono para seleccionarlo</li>' .
            '<li>Haz clic en "Guardar cambios"</li>' .
            '</ol>' .
            '<p><strong>Importante:</strong> Verifica que tu archivo se haya cargado correctamente antes de cerrar la página.</p>',
        'suggested' => 1,
    ],
    [
        'pattern' => "¿Cómo cambio mi contraseña?\n¿Dónde modifico mi password?\n¿Cómo actualizo mi clave?",
        'synonyms' => "modificar\nactualizar\neditar\ncambiar",
        'keywords' => 'contraseña, password, clave, seguridad',
        'response' => '<p>Para cambiar tu contraseña:</p>' .
            '<ol>' .
            '<li>Inicia sesión en Moodle</li>' .
            '<li>Haz clic en tu nombre en la esquina superior derecha</li>' .
            '<li>Selecciona "Preferencias" en el menú</li>' .
            '<li>Haz clic en "Cambiar contraseña"</li>' .
            '<li>Ingresa tu contraseña actual</li>' .
            '<li>Ingresa tu nueva contraseña dos veces</li>' .
            '<li>Haz clic en "Guardar cambios"</li>' .
            '</ol>' .
            '<p><strong>Recomendación:</strong> Usa una contraseña segura con al menos 8 caracteres.</p>',
        'suggested' => 0,
    ],
    [
        'pattern' => "¿Cómo veo mis calificaciones?\n¿Dónde consulto mis notas?\n¿Dónde están las calificaciones?",
        'synonyms' => "consultar\nrevisar\nver\nmirar",
        'keywords' => 'calificaciones, notas, puntaje, evaluación',
        'response' => '<p>Para ver tus calificaciones en Moodle:</p>' .
            '<ol>' .
            '<li>Accede al curso correspondiente</li>' .
            '<li>En el menú lateral o superior, busca "Calificaciones"</li>' .
            '<li>Se abrirá el informe de calificaciones con todas tus notas</li>' .
            '</ol>' .
            '<p>También puedes recibir notificaciones cuando un profesor publique una nueva calificación.</p>',
        'suggested' => 1,
    ],
];

foreach ($rules as $index => $ruledata) {
    $rule = new stdClass();
    $rule->pattern = $ruledata['pattern'];
    $rule->synonyms = $ruledata['synonyms'];
    $rule->keywords = $ruledata['keywords'];
    $rule->response = $ruledata['response'];
    $rule->roles = null;
    $rule->contexts = null;
    $rule->suggested = $ruledata['suggested'];
    $rule->enabled = 1;
    $rule->timecreated = $now;
    $rule->timemodified = $now;
    $ruleid = $DB->insert_record('local_educambot_rule', $rule);
    cli_writeln("  Rule " . ($index + 1) . " created with ID: $ruleid");
}

// Create sample knowledge entries.
cli_writeln("Creating sample knowledge entries...");

$knowledgeentries = [
    [
        'title' => 'Guía de inicio rápido',
        'summary' => '<p>Aprende los conceptos básicos de Moodle en pocos minutos</p>',
        'content' => '<p>Moodle es una plataforma de aprendizaje en línea que te permite acceder a cursos, materiales de estudio, tareas y comunicarte con tus profesores y compañeros.</p>' .
            '<h3>Conceptos básicos:</h3>' .
            '<ul>' .
            '<li><strong>Curso:</strong> Espacio virtual donde se desarrolla el contenido académico</li>' .
            '<li><strong>Actividad:</strong> Tareas, foros, cuestionarios, etc.</li>' .
            '<li><strong>Recurso:</strong> Archivos, enlaces, páginas de contenido</li>' .
            '<li><strong>Calificaciones:</strong> Puedes ver tus notas en el libro de calificaciones</li>' .
            '</ul>',
        'tags' => 'moodle, inicio, tutorial, básico',
    ],
    [
        'title' => '¿Cómo navegar por un curso?',
        'summary' => '<p>Aprende a moverte eficientemente dentro de tus cursos de Moodle</p>',
        'content' => '<p>La navegación en Moodle es intuitiva una vez que conoces los elementos principales:</p>' .
            '<h3>Estructura de un curso:</h3>' .
            '<ul>' .
            '<li><strong>Panel lateral:</strong> Contiene bloques con información útil</li>' .
            '<li><strong>Área principal:</strong> Muestra el contenido del curso</li>' .
            '<li><strong>Menú superior:</strong> Permite acceder a diferentes secciones</li>' .
            '</ul>',
        'tags' => 'navegación, curso, interfaz',
    ],
];

$knowledgeids = [];
foreach ($knowledgeentries as $index => $kdata) {
    $knowledge = new stdClass();
    $knowledge->title = $kdata['title'];
    $knowledge->summary = $kdata['summary'];
    $knowledge->content = $kdata['content'];
    $knowledge->contentformat = FORMAT_HTML;
    $knowledge->type = 'faq';
    $knowledge->externalurl = null;
    $knowledge->tags = $kdata['tags'];
    $knowledge->enabled = 1;
    $knowledge->createdby = null;
    $knowledge->updatedby = null;
    $knowledge->timecreated = $now;
    $knowledge->timemodified = $now;
    $kid = $DB->insert_record('local_educambot_knowledge', $knowledge);
    $knowledgeids[] = $kid;
    cli_writeln("  Knowledge entry " . ($index + 1) . " created with ID: $kid");

    // Link to topic.
    $link = new stdClass();
    $link->knowledgeid = $kid;
    $link->topicid = $topicid;
    $DB->insert_record('local_educambot_kn_topic', $link);
}

// Create a relation between knowledge entries.
if (count($knowledgeids) >= 2) {
    cli_writeln("Creating knowledge relations...");
    $relation = new stdClass();
    $relation->sourceid = $knowledgeids[0];
    $relation->targetid = $knowledgeids[1];
    $relation->relationtype = 'related';
    $DB->insert_record('local_educambot_relation', $relation);
    cli_writeln("  Relation created between knowledge entries");
}

// Clear caches.
\local_educambot\local\knowledge_repository::reset_caches();
$cache = cache::make('local_educambot', 'rules');
$cache->purge();

cli_writeln("");
cli_writeln("✓ Sample data added successfully!");
cli_writeln("  - 1 topic created");
cli_writeln("  - " . count($rules) . " rules created");
cli_writeln("  - " . count($knowledgeentries) . " knowledge entries created");
cli_writeln("");
cli_writeln("You can now test the chatbot widget on any Moodle page.");

exit(0);
