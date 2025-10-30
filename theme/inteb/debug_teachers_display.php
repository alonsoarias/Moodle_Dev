<?php
/**
 * Script de debugging para verificar qué profesores se están obteniendo
 *
 * CÓMO USAR:
 * 1. Acceder vía navegador: https://tu-moodle.com/theme/inteb/debug_teachers_display.php?courseid=X
 * 2. O ejecutar desde CLI: php debug_teachers_display.php courseid=X
 *
 * @package   theme_inteb
 * @copyright 2025 INTEB
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Determinar si se ejecuta desde CLI o web
$is_cli = (php_sapi_name() === 'cli');

if ($is_cli) {
    // CLI mode
    define('CLI_SCRIPT', true);
    // Buscar courseid en argumentos
    foreach ($argv as $arg) {
        if (strpos($arg, 'courseid=') === 0) {
            $_GET['courseid'] = substr($arg, 9);
        }
    }
} else {
    // Web mode - necesita estar logueado como admin
}

require_once(__DIR__ . '/../../config.php');

// Requerir login y permisos de admin
require_login();
if (!is_siteadmin()) {
    die("ERROR: Necesitas ser administrador del sitio para ejecutar este script\n");
}

$courseid = optional_param('courseid', 0, PARAM_INT);

if (!$courseid) {
    die("ERROR: Debes proporcionar un courseid.\nUso: ?courseid=123\n");
}

// Obtener curso
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

if (!$is_cli) {
    echo "<html><head><meta charset='utf-8'><style>
        body { font-family: monospace; padding: 20px; }
        h1, h2 { color: #333; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        .teacher-list { background: #f5f5f5; padding: 10px; margin: 10px 0; }
        .teacher { padding: 5px; border-bottom: 1px solid #ddd; }
        .capability { background: #e8f4f8; padding: 3px 6px; border-radius: 3px; font-size: 0.9em; }
    </style></head><body>";
}

echo str_repeat("=", 80) . "\n";
echo "DEBUGGING DE PROFESORES EN FORMATO REMUIFORMAT\n";
echo str_repeat("=", 80) . "\n\n";

echo "📚 CURSO: " . $course->fullname . " (ID: $courseid)\n";
echo "🎨 Formato del curso: " . $course->format . "\n\n";

$coursecontext = context_course::instance($courseid);

echo str_repeat("-", 80) . "\n";
echo "1️⃣ PROFESORES CON CAPABILITY: mod/folder:managefiles (EDITINGTEACHER)\n";
echo str_repeat("-", 80) . "\n";

$editingteachers = get_enrolled_users(
    $coursecontext,
    'mod/folder:managefiles',
    0,
    'u.id, u.firstname, u.lastname, u.email',
    'u.firstname',
    0,
    0,
    true
);

if (empty($editingteachers)) {
    echo "⚠️  NO SE ENCONTRARON profesores con esta capability\n\n";
} else {
    echo "✅ Se encontraron " . count($editingteachers) . " profesor(es):\n\n";
    foreach ($editingteachers as $teacher) {
        echo "   • ID: {$teacher->id}\n";
        echo "     Nombre: {$teacher->firstname} {$teacher->lastname}\n";
        echo "     Email: {$teacher->email}\n";

        // Obtener roles del usuario
        $roles = get_user_roles($coursecontext, $teacher->id);
        $rolenames = array();
        foreach ($roles as $role) {
            $rolenames[] = $role->shortname;
        }
        echo "     Roles: " . implode(', ', $rolenames) . "\n\n";
    }
}

echo str_repeat("-", 80) . "\n";
echo "2️⃣ PROFESORES CON CAPABILITY: moodle/course:viewhiddenactivities (TEACHER + EDITINGTEACHER)\n";
echo str_repeat("-", 80) . "\n";

$allteachers = get_enrolled_users(
    $coursecontext,
    'moodle/course:viewhiddenactivities',
    0,
    'u.id, u.firstname, u.lastname, u.email',
    'u.firstname',
    0,
    0,
    true
);

if (empty($allteachers)) {
    echo "⚠️  NO SE ENCONTRARON profesores con esta capability\n\n";
} else {
    echo "✅ Se encontraron " . count($allteachers) . " profesor(es):\n\n";
    foreach ($allteachers as $teacher) {
        echo "   • ID: {$teacher->id}\n";
        echo "     Nombre: {$teacher->firstname} {$teacher->lastname}\n";
        echo "     Email: {$teacher->email}\n";

        // Obtener roles del usuario
        $roles = get_user_roles($coursecontext, $teacher->id);
        $rolenames = array();
        foreach ($roles as $role) {
            $rolenames[] = $role->shortname;
        }
        echo "     Roles: " . implode(', ', $rolenames) . "\n\n";
    }
}

echo str_repeat("-", 80) . "\n";
echo "3️⃣ RESULTADO DEL HELPER DE THEME_INTEB\n";
echo str_repeat("-", 80) . "\n";

// Verificar que existe la clase
if (!class_exists('\\theme_inteb\\format_remuiformat_helper')) {
    echo "❌ ERROR: No se pudo cargar la clase theme_inteb\\format_remuiformat_helper\n";
} else {
    echo "✅ Clase theme_inteb\\format_remuiformat_helper cargada correctamente\n\n";

    // Llamar al método
    try {
        $result = \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context($course, true);

        if (empty($result)) {
            echo "⚠️  El helper NO retornó profesores\n\n";
        } else {
            echo "✅ El helper retornó datos de profesores\n\n";

            if (isset($result['instructors'])) {
                echo "Número de profesores en resultado: " . count($result['instructors']) . "\n\n";

                foreach ($result['instructors'] as $instructor) {
                    echo "   • ID: " . (isset($instructor['id']) ? $instructor['id'] : 'N/A') . "\n";
                    echo "     Nombre: " . (isset($instructor['name']) ? $instructor['name'] : 'N/A') . "\n";
                    echo "\n";
                }
            } else {
                echo "⚠️  Estructura de datos inesperada:\n";
                echo "<pre>" . print_r($result, true) . "</pre>\n";
            }
        }
    } catch (Exception $e) {
        echo "❌ ERROR al ejecutar el helper:\n";
        echo "   " . $e->getMessage() . "\n";
        echo "   " . $e->getTraceAsString() . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "4️⃣ VERIFICACIÓN DE RENDERER OVERRIDE\n";
echo str_repeat("=", 80) . "\n";

// Verificar que existe el renderer override
$renderer_file = __DIR__ . '/renderers.php';
if (!file_exists($renderer_file)) {
    echo "❌ ERROR: No existe el archivo renderers.php en theme_inteb\n";
} else {
    echo "✅ Archivo renderers.php existe\n";

    // Verificar que la clase existe
    if (!class_exists('theme_inteb_format_remuiformat_renderer')) {
        echo "⚠️  ADVERTENCIA: Clase theme_inteb_format_remuiformat_renderer no está cargada\n";
        echo "   Esto es NORMAL si no estás viendo una página con formato remuiformat\n";
    } else {
        echo "✅ Clase theme_inteb_format_remuiformat_renderer está cargada\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📝 CONCLUSIONES\n";
echo str_repeat("=", 80) . "\n";

echo "\nPara que el renderer funcione correctamente, necesitas:\n\n";
echo "1. ✓ Profesores asignados con rol 'teacher' o 'editingteacher' en el curso\n";
echo "2. ✓ theme_inteb activo\n";
echo "3. ✓ Formato del curso configurado como 'remuiformat'\n";
echo "4. ✓ Caché de Moodle purgada (Admin > Desarrollo > Purgar todas las cachés)\n";
echo "5. ✓ Navegar a la página del curso (no esta página de debug)\n\n";

echo "Si los pasos 1-4 están cumplidos y ves profesores en las secciones 1️⃣ o 2️⃣ arriba,\n";
echo "entonces el renderer DEBERÍA mostrar a todos los profesores en la página del curso.\n\n";

if (!$is_cli) {
    echo "</body></html>";
}
