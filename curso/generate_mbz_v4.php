<?php
/**
 * Generador MBZ v4.0 - Curso "Huellas Invisibles"
 *
 * VERSION COMPLETA con actividades interactivas:
 * - mod_resource: PDFs, documentos
 * - mod_glossary: Glosarios con terminos extraidos
 * - mod_quiz: Tests con preguntas extraidas
 * - mod_feedback: Encuesta de cierre
 * - mod_customcert: Certificado del curso
 *
 * Completion automatico (completion=2, completionview=1)
 * Branding: #0170B9 (azul NADI), #3a3a3a (gris), Rubik font
 */

// ============================================================================
// CONFIGURACION GLOBAL
// ============================================================================

define('CONTENT_DIR', __DIR__ . '/../mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_v4');
define('MBZ_FILENAME', 'backup-huellas-invisibles-v4-' . date('Ymd-His') . '.mbz');

define('COURSE_FULLNAME', 'Huellas Invisibles: Neurociencia del Desarrollo Infantil');
define('COURSE_SHORTNAME', 'huellas-invisibles');
define('COURSE_SUMMARY', 'Formacion especializada en neurodesarrollo infantil, emocion y aprendizaje, con foco en la primera infancia y el medio acuatico. Docente: Lic. Emilio Masabeu - Campus NADI');
define('MOODLE_VERSION', '2024042200');
define('ORIGINAL_WWWROOT', 'https://campus.rednadi.com');

// Branding NADI
define('BRAND_COLOR_PRIMARY', '#0170B9');
define('BRAND_COLOR_SECONDARY', '#3a3a3a');
define('BRAND_FONT', 'Rubik, sans-serif');

// Contadores globales
$nextModuleId = 1;
$nextContextId = 100;
$nextFileId = 1;
$nextInstanceId = 1;
$nextQuestionId = 1;
$nextAnswerId = 1;
$nextGlossaryEntryId = 1;
$nextFeedbackItemId = 1;

// Colecciones globales
$allFiles = [];
$allActivities = [];
$allSections = [];
$allQuestions = [];

// Tiempo de generacion
$generationTime = time();

// ============================================================================
// INCLUDES DE DATOS Y FUNCIONES
// ============================================================================

// Los datos se cargaran desde archivos separados para mejor organizacion
// Por ahora definimos las funciones auxiliares basicas

/**
 * Crear directorios necesarios
 */
function createDirectoryStructure() {
    $dirs = [
        OUTPUT_DIR,
        OUTPUT_DIR . '/course',
        OUTPUT_DIR . '/sections',
        OUTPUT_DIR . '/activities',
        OUTPUT_DIR . '/files',
        OUTPUT_DIR . '/questions'
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/**
 * Obtener MIME type por extension
 */
function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = [
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'doc' => 'application/msword',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'txt' => 'text/plain'
    ];
    return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
}

/**
 * Formatear bytes a unidades legibles
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}

/**
 * Escapar HTML para XML
 */
function escapeXml($str) {
    return htmlspecialchars($str, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

/**
 * Generar ID unico
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

/**
 * Log con timestamp
 */
function logMessage($message, $icon = '  ') {
    echo "[" . date('H:i:s') . "] {$icon} {$message}\n";
}

// ============================================================================
// PLACEHOLDER - FUNCIONES A IMPLEMENTAR EN SIGUIENTES SUBFASES
// ============================================================================

// 4.1.2: $COURSE_STRUCTURE - Definicion de estructura
// 4.1.3-4: $GLOSSARY_DATA - Terminos de glosarios
// 4.1.5-6: $QUIZ_DATA - Preguntas de tests
// 4.2.1: generateResourceActivity()
// 4.2.2: generateLabelActivity()
// 4.3.1: generateGlossaryActivity()
// 4.4.1-2: generateQuizActivity(), generateQuestionBank()
// 4.5.1: generateFeedbackActivity()
// 4.6.1: generateCustomcertActivity()
// 4.7.1: generateGlobalXMLs(), createMBZ()

echo "=======================================================\n";
echo " MBZ Generator v4.0 - Huellas Invisibles\n";
echo " Configuracion cargada correctamente\n";
echo "=======================================================\n";
