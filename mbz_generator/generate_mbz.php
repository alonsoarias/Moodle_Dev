<?php
/**
 * Generador de archivo MBZ para el curso "Huellas Invisibles"
 *
 * Este script genera un archivo de backup de Moodle (.mbz) completo
 * para el curso de neurociencia infantil de RedNADI.
 *
 * Sitio: https://campus.rednadi.com/
 * Directora: Jaqui Esquitino
 *
 * @author Claude Code Generator
 * @version 1.0
 */

// Configuración
define('COURSE_FULLNAME', 'Huellas Invisibles: Neurociencia del Desarrollo Infantil');
define('COURSE_SHORTNAME', 'huellas-invisibles');
define('COURSE_SUMMARY', '<p>Bienvenidos al curso <strong>Huellas Invisibles</strong>, un programa integral de formación en neurociencia del desarrollo infantil.</p><p>Este curso, dirigido por <strong>Jaqui Esquitino</strong>, explora los fundamentos del desarrollo neurológico en la primera infancia a través de 10 capítulos especializados.</p><p>Cada capítulo incluye material de lectura, documentos complementarios, glosarios, bibliografía y evaluaciones para asegurar un aprendizaje completo.</p>');
define('MOODLE_VERSION', '2024042200');
define('MOODLE_RELEASE', '4.4 (Build: 20240422)');
define('BACKUP_VERSION', '2024042200');
define('BACKUP_RELEASE', '4.4');
define('ORIGINAL_WWWROOT', 'https://campus.rednadi.com');

// Directorios
define('CONTENT_DIR', __DIR__ . '/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output');
define('MBZ_FILENAME', 'backup-huellas-invisibles-' . date('Ymd') . '.mbz');

// IDs base (se incrementarán automáticamente)
$nextModuleId = 1;
$nextContextId = 100; // Empezamos en 100 para evitar conflictos
$nextFileId = 1;
$nextInstanceId = 1;

// Almacenamiento global de archivos para files.xml
$allFiles = [];

// Estructura del curso
$courseStructure = [
    'id' => 1,
    'contextid' => 50,
    'category_id' => 1,
    'category_name' => 'Cursos NADI',
    'sections' => []
];

/**
 * Definición de los capítulos del curso
 */
$chapters = [
    0 => [
        'name' => 'Presentación del Curso',
        'summary' => '<p>Bienvenidos a <strong>Huellas Invisibles</strong>: Un viaje por la neurociencia del desarrollo infantil.</p>',
        'files' => [
            ['type' => 'resource', 'name' => 'Título, Presentación y Programa del Curso', 'file' => 'Titulo presentacion y programa.docx'],
            ['type' => 'label', 'name' => 'Imagen del Curso', 'content' => '<p><img src="@@PLUGINFILE@@/huellas%20invisibles.png" alt="Huellas Invisibles" width="600" /></p>', 'file' => 'huellas invisibles.png'],
        ]
    ],
    1 => [
        'name' => 'Capítulo 1: Desarrollo Neurológico Infantil',
        'summary' => '<p>Exploración de los fundamentos del desarrollo neurológico en la primera infancia.</p>',
        'dir' => 'Capitulo 1 Desarrollo Neurológico Infantil',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Esculpiendo el Cerebro - Primera Infancia', 'file' => '2-Esculpiendo el Cerebro Primera Infancia (Cap 1).pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio Complementario', 'file' => '3-DOCUMENTO DE ESTUDIO COMPLEMENTARIO (Cap 1).pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario de Términos Clave', 'file' => '4- Glosario de Términos Clave (Cap 1).pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía de Referencia', 'file' => '5- Bibliografia de referencia y recomendada (Cap 1).pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-Autoevaluacion- Tests Capitulo 1.pdf'],
            ['type' => 'resource', 'name' => '🎬 Material Complementario - Video', 'file' => '7-Material complementario Video como funciona el cerebro.docx'],
        ]
    ],
    2 => [
        'name' => 'Capítulo 2: Funciones Emocionales',
        'summary' => '<p>Análisis de las funciones emocionales y su desarrollo en el cerebro infantil.</p>',
        'dir' => 'Capitulo 2- Funciones emocionales_',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Cerebro en Construcción', 'file' => '2- CEREBRO EN CONSTRUCCION.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento Complementario', 'file' => '3-DOCUMENTO COMPLEMENTARIO CAP 2.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '4-BIBLIOGRAFIA CAP 2.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '5-GLOSARIO CAP.2.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-TEST DE AUTOEVALUACION.docx'],
        ]
    ],
    3 => [
        'name' => 'Capítulo 3: Plasticidad Cerebral',
        'summary' => '<p>Estudio de la plasticidad cerebral y sus implicaciones en el aprendizaje temprano.</p>',
        'dir' => 'Capitulo 3 - Plasticidad Cerebral',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: El Cerebro Moldeable - Principios y Práctica', 'file' => '2-El Cerebro Moldeable Principios y Práctica.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio cap 3.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO CAP 3.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAP 3.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6- test Capitulo 3 Plasticidad cerebral.docx'],
        ]
    ],
    4 => [
        'name' => 'Capítulo 4: Sinaptogénesis',
        'summary' => '<p>Comprensión del proceso de sinaptogénesis y formación de conexiones neuronales.</p>',
        'dir' => 'Capitulo 4- Sinaptogénesis',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: El Cerebro Plástico - De la Construcción a la Maestría', 'file' => '2-El Cerebro Plástico De la Construcción a la Maestría.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio cap 4.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO CAP 4.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAP 4.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '5-Test Capitulo 4.docx'],
        ]
    ],
    5 => [
        'name' => 'Capítulo 5: Período Sensoriomotor',
        'summary' => '<p>Exploración del período sensoriomotor según Piaget y su base neurológica.</p>',
        'dir' => 'Capitulo 5 -  Período sensoriomotor',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Construyendo la Realidad - La Mente Sensoriomotora', 'file' => '2-Construyendo_la_Realidad_La_Mente_Sensoriomotora2.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento Complementario de Estudio', 'file' => '3-Documento Complementario de Estudio Cap 5.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO CAPITULO 5.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAPITULO 5.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6- test Capitulo 5.docx'],
        ]
    ],
    6 => [
        'name' => 'Capítulo 6: Estrés y Apego',
        'summary' => '<p>Análisis de la relación entre estrés, apego y desarrollo cerebral.</p>',
        'dir' => 'Capitulo 6 - Stress y Apego',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Arquitectos del Cerebro Infantil - Apego y Estrés', 'file' => '2-Arquitectos_del_Cerebro_Infantil_Apego_y_Estrés.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio capitulo 6.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO Capitulo 6.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5- BIBLIOGRAFIA CAPITULO 6.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6- Test Cap 6.docx'],
        ]
    ],
    7 => [
        'name' => 'Capítulo 7: Teoría Epigenética',
        'summary' => '<p>Introducción a la epigenética y su impacto en el desarrollo infantil.</p>',
        'dir' => 'Capitulo 7- Teoría Epigenética',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Huellas Invisibles - Entorno y Genes', 'file' => '2-Huellas Invisibles Entorno y Genes.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio Capitulo 7.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO Capitulo 7.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAPITULO 7.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-Test Capitulo 7.docx'],
        ]
    ],
    8 => [
        'name' => 'Capítulo 8: Reflejo de Apnea',
        'summary' => '<p>Estudio del reflejo de apnea y los reflejos primitivos en el desarrollo.</p>',
        'dir' => 'Capitulo 8 - Reflejo de Apnea',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Ancestral Water Echo', 'file' => '2-Ancestral Water Echo.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio Capitulo 8.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO Capitulo 8.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAPITULO 8.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-test Capitulo 8 8.docx'],
        ]
    ],
    9 => [
        'name' => 'Capítulo 9: Socialización en la Primera Infancia',
        'summary' => '<p>Análisis de los procesos de socialización y su base neurológica.</p>',
        'dir' => 'Capitulo 9 - Socialización en la 1er infancia',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: Arquitectos de Mentes - Construyendo el Cerebro', 'file' => '2-Arquitectos de Mentes Construyendo el Cerebro.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio Capitulo 9.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO Capitulo 9.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5- BIBLIOGRAFIA capitulo 9.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-TEST CAPITULO 9.docx'],
        ]
    ],
    10 => [
        'name' => 'Capítulo 10: La Atención',
        'summary' => '<p>Exploración de los mecanismos de atención y su desarrollo en la infancia.</p>',
        'dir' => 'Capitulo 10- La Atención',
        'files' => [
            ['type' => 'resource', 'name' => '📚 Libro: El Arquitecto Invisible - Un Viaje al Corazón de la Atención', 'file' => '2-El Arquitecto Invisible Un Viaje al Corazón de la Atención.pdf'],
            ['type' => 'resource', 'name' => '📄 Documento de Estudio', 'file' => '3-Documento de estudio Capitulo 10.pdf'],
            ['type' => 'resource', 'name' => '📖 Glosario', 'file' => '4-GLOSARIO Capitulo 10.pdf'],
            ['type' => 'resource', 'name' => '📚 Bibliografía', 'file' => '5-BIBLIOGRAFIA CAPITULO 10.pdf'],
            ['type' => 'resource', 'name' => '✅ Test de Autoevaluación', 'file' => '6-Test capitulo 10.docx'],
        ]
    ],
    11 => [
        'name' => 'Cierre del Curso',
        'summary' => '<p>Evaluación final y cierre del programa Huellas Invisibles.</p>',
        'files' => [
            ['type' => 'resource', 'name' => '📋 Encuesta de Opinión - Cierre del Curso', 'file' => 'Encuesta De Opinión – Cierre Curso Huellas Invisibles.docx'],
        ]
    ]
];

/**
 * Función principal
 */
function main() {
    global $chapters, $courseStructure;

    echo "=== Generador de MBZ para Huellas Invisibles ===\n\n";

    // Crear estructura de directorios
    echo "FASE 1: Creando estructura de directorios...\n";
    createDirectoryStructure();

    // Procesar capítulos y generar estructura
    echo "\nFASE 2: Procesando capítulos y actividades...\n";
    processChapters();

    // Generar XMLs
    echo "\nFASE 3: Generando archivos XML...\n";
    generateAllXMLFiles();

    // Procesar archivos binarios
    echo "\nFASE 4: Procesando archivos binarios...\n";
    processFiles();

    // Generar files.xml
    echo "\nFASE 4.3: Generando files.xml...\n";
    generateFilesXML();

    // Crear MBZ
    echo "\nFASE 5: Creando archivo MBZ...\n";
    createMBZ();

    echo "\n=== Proceso completado ===\n";
    echo "Archivo generado: " . OUTPUT_DIR . '/' . MBZ_FILENAME . "\n";
}

/**
 * Crea la estructura de directorios del backup
 */
function createDirectoryStructure() {
    $dirs = [
        OUTPUT_DIR,
        OUTPUT_DIR . '/course',
        OUTPUT_DIR . '/sections',
        OUTPUT_DIR . '/activities',
        OUTPUT_DIR . '/files',
    ];

    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "  Creado: $dir\n";
        }
    }
}

/**
 * Procesa todos los capítulos y construye la estructura
 */
function processChapters() {
    global $chapters, $courseStructure, $nextModuleId, $nextContextId, $nextInstanceId;

    foreach ($chapters as $sectionNum => $chapter) {
        echo "  Procesando: {$chapter['name']}\n";

        $sectionId = $sectionNum + 1;
        $sectionContextId = $nextContextId++;

        $section = [
            'id' => $sectionId,
            'number' => $sectionNum,
            'name' => $chapter['name'],
            'summary' => $chapter['summary'],
            'contextid' => $sectionContextId,
            'activities' => []
        ];

        $moduleIds = [];

        // Procesar archivos/actividades del capítulo
        foreach ($chapter['files'] as $fileInfo) {
            $moduleId = $nextModuleId++;
            $instanceId = $nextInstanceId++;
            $contextId = $nextContextId++;

            $filePath = '';
            if (isset($chapter['dir']) && !empty($chapter['dir'])) {
                $filePath = CONTENT_DIR . '/' . $chapter['dir'] . '/' . $fileInfo['file'];
            } else {
                $filePath = CONTENT_DIR . '/' . $fileInfo['file'];
            }

            $activity = [
                'moduleid' => $moduleId,
                'instanceid' => $instanceId,
                'contextid' => $contextId,
                'type' => $fileInfo['type'],
                'name' => $fileInfo['name'],
                'sectionid' => $sectionId,
                'sectionnumber' => $sectionNum,
                'filepath' => $filePath,
                'filename' => $fileInfo['file']
            ];

            if ($fileInfo['type'] === 'label' && isset($fileInfo['content'])) {
                $activity['content'] = $fileInfo['content'];
            }

            $section['activities'][] = $activity;
            $moduleIds[] = $moduleId;
        }

        $section['sequence'] = implode(',', $moduleIds);
        $courseStructure['sections'][] = $section;

        // Crear directorio de sección
        $sectionDir = OUTPUT_DIR . "/sections/section_{$sectionId}";
        if (!is_dir($sectionDir)) {
            mkdir($sectionDir, 0755, true);
        }

        // Crear directorios de actividades
        foreach ($section['activities'] as $activity) {
            $actDir = OUTPUT_DIR . "/activities/{$activity['type']}_{$activity['moduleid']}";
            if (!is_dir($actDir)) {
                mkdir($actDir, 0755, true);
            }
        }
    }
}

/**
 * Genera todos los archivos XML
 */
function generateAllXMLFiles() {
    global $courseStructure;

    // moodle_backup.xml
    echo "  Generando moodle_backup.xml...\n";
    generateMoodleBackupXML();

    // course.xml
    echo "  Generando course/course.xml...\n";
    generateCourseXML();

    // Secciones
    echo "  Generando XMLs de secciones...\n";
    foreach ($courseStructure['sections'] as $section) {
        generateSectionXML($section);
    }

    // Actividades
    echo "  Generando XMLs de actividades...\n";
    foreach ($courseStructure['sections'] as $section) {
        foreach ($section['activities'] as $activity) {
            generateActivityXML($activity);
        }
    }

    // Archivos auxiliares
    echo "  Generando archivos auxiliares...\n";
    generateAuxiliaryFiles();
}

/**
 * Genera moodle_backup.xml
 */
function generateMoodleBackupXML() {
    global $courseStructure;

    $backupId = generateUniqueId();
    $backupDate = time();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<moodle_backup>' . "\n";
    $xml .= '  <information>' . "\n";
    $xml .= '    <name>backup-' . COURSE_SHORTNAME . '-' . date('Ymd-Hi', $backupDate) . '</name>' . "\n";
    $xml .= '    <moodle_version>' . MOODLE_VERSION . '</moodle_version>' . "\n";
    $xml .= '    <moodle_release>' . htmlspecialchars(MOODLE_RELEASE) . '</moodle_release>' . "\n";
    $xml .= '    <backup_version>' . BACKUP_VERSION . '</backup_version>' . "\n";
    $xml .= '    <backup_release>' . BACKUP_RELEASE . '</backup_release>' . "\n";
    $xml .= '    <backup_date>' . $backupDate . '</backup_date>' . "\n";
    $xml .= '    <mnet_remoteusers>0</mnet_remoteusers>' . "\n";
    $xml .= '    <include_files>1</include_files>' . "\n";
    $xml .= '    <include_file_references_to_external_content>0</include_file_references_to_external_content>' . "\n";
    $xml .= '    <original_wwwroot>' . ORIGINAL_WWWROOT . '</original_wwwroot>' . "\n";
    $xml .= '    <original_site_identifier_hash>' . sha1(ORIGINAL_WWWROOT . $backupDate) . '</original_site_identifier_hash>' . "\n";
    $xml .= '    <original_course_id>' . $courseStructure['id'] . '</original_course_id>' . "\n";
    $xml .= '    <original_course_format>topics</original_course_format>' . "\n";
    $xml .= '    <original_course_fullname>' . htmlspecialchars(COURSE_FULLNAME) . '</original_course_fullname>' . "\n";
    $xml .= '    <original_course_shortname>' . htmlspecialchars(COURSE_SHORTNAME) . '</original_course_shortname>' . "\n";
    $xml .= '    <original_course_startdate>' . strtotime('2026-01-01') . '</original_course_startdate>' . "\n";
    $xml .= '    <original_course_enddate>0</original_course_enddate>' . "\n";
    $xml .= '    <original_course_contextid>' . $courseStructure['contextid'] . '</original_course_contextid>' . "\n";
    $xml .= '    <original_system_contextid>1</original_system_contextid>' . "\n";

    // Details
    $xml .= '    <details>' . "\n";
    $xml .= '      <detail backup_id="' . $backupId . '">' . "\n";
    $xml .= '        <type>course</type>' . "\n";
    $xml .= '        <format>moodle2</format>' . "\n";
    $xml .= '        <interactive>1</interactive>' . "\n";
    $xml .= '        <mode>10</mode>' . "\n";
    $xml .= '        <execution>1</execution>' . "\n";
    $xml .= '        <executiontime>0</executiontime>' . "\n";
    $xml .= '      </detail>' . "\n";
    $xml .= '    </details>' . "\n";

    // Contents
    $xml .= '    <contents>' . "\n";

    // Activities
    $xml .= '      <activities>' . "\n";
    foreach ($courseStructure['sections'] as $section) {
        foreach ($section['activities'] as $activity) {
            $xml .= '        <activity>' . "\n";
            $xml .= '          <moduleid>' . $activity['moduleid'] . '</moduleid>' . "\n";
            $xml .= '          <sectionid>' . $activity['sectionid'] . '</sectionid>' . "\n";
            $xml .= '          <modulename>' . $activity['type'] . '</modulename>' . "\n";
            $xml .= '          <title>' . htmlspecialchars($activity['name']) . '</title>' . "\n";
            $xml .= '          <directory>activities/' . $activity['type'] . '_' . $activity['moduleid'] . '</directory>' . "\n";
            $xml .= '        </activity>' . "\n";
        }
    }
    $xml .= '      </activities>' . "\n";

    // Sections
    $xml .= '      <sections>' . "\n";
    foreach ($courseStructure['sections'] as $section) {
        $xml .= '        <section>' . "\n";
        $xml .= '          <sectionid>' . $section['id'] . '</sectionid>' . "\n";
        $xml .= '          <title>' . htmlspecialchars($section['name']) . '</title>' . "\n";
        $xml .= '          <directory>sections/section_' . $section['id'] . '</directory>' . "\n";
        $xml .= '        </section>' . "\n";
    }
    $xml .= '      </sections>' . "\n";

    // Course
    $xml .= '      <course>' . "\n";
    $xml .= '        <courseid>' . $courseStructure['id'] . '</courseid>' . "\n";
    $xml .= '        <title>' . htmlspecialchars(COURSE_FULLNAME) . '</title>' . "\n";
    $xml .= '        <directory>course</directory>' . "\n";
    $xml .= '      </course>' . "\n";
    $xml .= '    </contents>' . "\n";

    // Settings
    $xml .= '    <settings>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>filename</name>' . "\n";
    $xml .= '        <value>' . MBZ_FILENAME . '</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>imscc11</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>users</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>anonymize</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>role_assignments</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>activities</name>' . "\n";
    $xml .= '        <value>1</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>blocks</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>files</name>' . "\n";
    $xml .= '        <value>1</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>filters</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>comments</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>badges</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>calendarevents</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>userscompletion</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>logs</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>grade_histories</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>questionbank</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>groups</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>competencies</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>customfield</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>contentbankcontent</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '      <setting>' . "\n";
    $xml .= '        <level>root</level>' . "\n";
    $xml .= '        <name>legacyfiles</name>' . "\n";
    $xml .= '        <value>0</value>' . "\n";
    $xml .= '      </setting>' . "\n";
    $xml .= '    </settings>' . "\n";

    $xml .= '  </information>' . "\n";
    $xml .= '</moodle_backup>' . "\n";

    file_put_contents(OUTPUT_DIR . '/moodle_backup.xml', $xml);
}

/**
 * Genera course/course.xml
 */
function generateCourseXML() {
    global $courseStructure;

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<course id="' . $courseStructure['id'] . '" contextid="' . $courseStructure['contextid'] . '">' . "\n";
    $xml .= '  <shortname>' . htmlspecialchars(COURSE_SHORTNAME) . '</shortname>' . "\n";
    $xml .= '  <fullname>' . htmlspecialchars(COURSE_FULLNAME) . '</fullname>' . "\n";
    $xml .= '  <idnumber></idnumber>' . "\n";
    $xml .= '  <summary>' . htmlspecialchars(COURSE_SUMMARY) . '</summary>' . "\n";
    $xml .= '  <summaryformat>1</summaryformat>' . "\n";
    $xml .= '  <format>topics</format>' . "\n";
    $xml .= '  <showgrades>1</showgrades>' . "\n";
    $xml .= '  <newsitems>5</newsitems>' . "\n";
    $xml .= '  <startdate>' . strtotime('2026-01-01') . '</startdate>' . "\n";
    $xml .= '  <enddate>0</enddate>' . "\n";
    $xml .= '  <marker>0</marker>' . "\n";
    $xml .= '  <maxbytes>0</maxbytes>' . "\n";
    $xml .= '  <legacyfiles>0</legacyfiles>' . "\n";
    $xml .= '  <showreports>0</showreports>' . "\n";
    $xml .= '  <visible>1</visible>' . "\n";
    $xml .= '  <groupmode>0</groupmode>' . "\n";
    $xml .= '  <groupmodeforce>0</groupmodeforce>' . "\n";
    $xml .= '  <defaultgroupingid>0</defaultgroupingid>' . "\n";
    $xml .= '  <lang></lang>' . "\n";
    $xml .= '  <theme></theme>' . "\n";
    $xml .= '  <timecreated>' . time() . '</timecreated>' . "\n";
    $xml .= '  <timemodified>' . time() . '</timemodified>' . "\n";
    $xml .= '  <requested>0</requested>' . "\n";
    $xml .= '  <showactivitydates>1</showactivitydates>' . "\n";
    $xml .= '  <showcompletionconditions>1</showcompletionconditions>' . "\n";
    $xml .= '  <pdfexportfont></pdfexportfont>' . "\n";
    $xml .= '  <enablecompletion>1</enablecompletion>' . "\n";
    $xml .= '  <completionnotify>0</completionnotify>' . "\n";
    $xml .= '  <category id="' . $courseStructure['category_id'] . '">' . "\n";
    $xml .= '    <name>' . htmlspecialchars($courseStructure['category_name']) . '</name>' . "\n";
    $xml .= '    <description></description>' . "\n";
    $xml .= '  </category>' . "\n";
    $xml .= '  <tags>' . "\n";
    $xml .= '    <tag id="1">' . "\n";
    $xml .= '      <name>neurociencia</name>' . "\n";
    $xml .= '      <rawname>Neurociencia</rawname>' . "\n";
    $xml .= '    </tag>' . "\n";
    $xml .= '    <tag id="2">' . "\n";
    $xml .= '      <name>desarrollo-infantil</name>' . "\n";
    $xml .= '      <rawname>Desarrollo Infantil</rawname>' . "\n";
    $xml .= '    </tag>' . "\n";
    $xml .= '    <tag id="3">' . "\n";
    $xml .= '      <name>primera-infancia</name>' . "\n";
    $xml .= '      <rawname>Primera Infancia</rawname>' . "\n";
    $xml .= '    </tag>' . "\n";
    $xml .= '  </tags>' . "\n";
    $xml .= '  <customfields>' . "\n";
    $xml .= '  </customfields>' . "\n";
    $xml .= '  <courseformatoptions>' . "\n";
    $xml .= '    <courseformatoption>' . "\n";
    $xml .= '      <courseid>' . $courseStructure['id'] . '</courseid>' . "\n";
    $xml .= '      <format>topics</format>' . "\n";
    $xml .= '      <sectionid>0</sectionid>' . "\n";
    $xml .= '      <name>hiddensections</name>' . "\n";
    $xml .= '      <value>0</value>' . "\n";
    $xml .= '    </courseformatoption>' . "\n";
    $xml .= '    <courseformatoption>' . "\n";
    $xml .= '      <courseid>' . $courseStructure['id'] . '</courseid>' . "\n";
    $xml .= '      <format>topics</format>' . "\n";
    $xml .= '      <sectionid>0</sectionid>' . "\n";
    $xml .= '      <name>coursedisplay</name>' . "\n";
    $xml .= '      <value>0</value>' . "\n";
    $xml .= '    </courseformatoption>' . "\n";
    $xml .= '  </courseformatoptions>' . "\n";
    $xml .= '</course>' . "\n";

    file_put_contents(OUTPUT_DIR . '/course/course.xml', $xml);

    // Generar inforef.xml para el curso
    $inforef = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $inforef .= '<inforef>' . "\n";
    $inforef .= '  <fileref>' . "\n";
    $inforef .= '  </fileref>' . "\n";
    $inforef .= '</inforef>' . "\n";
    file_put_contents(OUTPUT_DIR . '/course/inforef.xml', $inforef);

    // Generar roles.xml para el curso
    $roles = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $roles .= '<roles>' . "\n";
    $roles .= '  <role_overrides>' . "\n";
    $roles .= '  </role_overrides>' . "\n";
    $roles .= '  <role_assignments>' . "\n";
    $roles .= '  </role_assignments>' . "\n";
    $roles .= '</roles>' . "\n";
    file_put_contents(OUTPUT_DIR . '/course/roles.xml', $roles);

    // Generar enrolments.xml
    $enrolments = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $enrolments .= '<enrolments>' . "\n";
    $enrolments .= '  <enrols>' . "\n";
    $enrolments .= '    <enrol id="1">' . "\n";
    $enrolments .= '      <enrol>manual</enrol>' . "\n";
    $enrolments .= '      <status>0</status>' . "\n";
    $enrolments .= '      <name>$@NULL@$</name>' . "\n";
    $enrolments .= '      <enrolperiod>0</enrolperiod>' . "\n";
    $enrolments .= '      <enrolstartdate>0</enrolstartdate>' . "\n";
    $enrolments .= '      <enrolenddate>0</enrolenddate>' . "\n";
    $enrolments .= '      <expirynotify>0</expirynotify>' . "\n";
    $enrolments .= '      <expirythreshold>86400</expirythreshold>' . "\n";
    $enrolments .= '      <notifyall>0</notifyall>' . "\n";
    $enrolments .= '      <password>$@NULL@$</password>' . "\n";
    $enrolments .= '      <cost>$@NULL@$</cost>' . "\n";
    $enrolments .= '      <currency>$@NULL@$</currency>' . "\n";
    $enrolments .= '      <roleid>5</roleid>' . "\n";
    $enrolments .= '      <customint1>$@NULL@$</customint1>' . "\n";
    $enrolments .= '      <customint2>$@NULL@$</customint2>' . "\n";
    $enrolments .= '      <customint3>$@NULL@$</customint3>' . "\n";
    $enrolments .= '      <customint4>$@NULL@$</customint4>' . "\n";
    $enrolments .= '      <customint5>$@NULL@$</customint5>' . "\n";
    $enrolments .= '      <customint6>$@NULL@$</customint6>' . "\n";
    $enrolments .= '      <customint7>$@NULL@$</customint7>' . "\n";
    $enrolments .= '      <customint8>$@NULL@$</customint8>' . "\n";
    $enrolments .= '      <customchar1>$@NULL@$</customchar1>' . "\n";
    $enrolments .= '      <customchar2>$@NULL@$</customchar2>' . "\n";
    $enrolments .= '      <customchar3>$@NULL@$</customchar3>' . "\n";
    $enrolments .= '      <customdec1>$@NULL@$</customdec1>' . "\n";
    $enrolments .= '      <customdec2>$@NULL@$</customdec2>' . "\n";
    $enrolments .= '      <customtext1>$@NULL@$</customtext1>' . "\n";
    $enrolments .= '      <customtext2>$@NULL@$</customtext2>' . "\n";
    $enrolments .= '      <customtext3>$@NULL@$</customtext3>' . "\n";
    $enrolments .= '      <customtext4>$@NULL@$</customtext4>' . "\n";
    $enrolments .= '      <timecreated>' . time() . '</timecreated>' . "\n";
    $enrolments .= '      <timemodified>' . time() . '</timemodified>' . "\n";
    $enrolments .= '      <user_enrolments>' . "\n";
    $enrolments .= '      </user_enrolments>' . "\n";
    $enrolments .= '    </enrol>' . "\n";
    $enrolments .= '    <enrol id="2">' . "\n";
    $enrolments .= '      <enrol>self</enrol>' . "\n";
    $enrolments .= '      <status>1</status>' . "\n";
    $enrolments .= '      <name>$@NULL@$</name>' . "\n";
    $enrolments .= '      <enrolperiod>0</enrolperiod>' . "\n";
    $enrolments .= '      <enrolstartdate>0</enrolstartdate>' . "\n";
    $enrolments .= '      <enrolenddate>0</enrolenddate>' . "\n";
    $enrolments .= '      <expirynotify>0</expirynotify>' . "\n";
    $enrolments .= '      <expirythreshold>86400</expirythreshold>' . "\n";
    $enrolments .= '      <notifyall>0</notifyall>' . "\n";
    $enrolments .= '      <password>$@NULL@$</password>' . "\n";
    $enrolments .= '      <cost>$@NULL@$</cost>' . "\n";
    $enrolments .= '      <currency>$@NULL@$</currency>' . "\n";
    $enrolments .= '      <roleid>5</roleid>' . "\n";
    $enrolments .= '      <customint1>0</customint1>' . "\n";
    $enrolments .= '      <customint2>0</customint2>' . "\n";
    $enrolments .= '      <customint3>0</customint3>' . "\n";
    $enrolments .= '      <customint4>1</customint4>' . "\n";
    $enrolments .= '      <customint5>0</customint5>' . "\n";
    $enrolments .= '      <customint6>1</customint6>' . "\n";
    $enrolments .= '      <customint7>$@NULL@$</customint7>' . "\n";
    $enrolments .= '      <customint8>$@NULL@$</customint8>' . "\n";
    $enrolments .= '      <customchar1>$@NULL@$</customchar1>' . "\n";
    $enrolments .= '      <customchar2>$@NULL@$</customchar2>' . "\n";
    $enrolments .= '      <customchar3>$@NULL@$</customchar3>' . "\n";
    $enrolments .= '      <customdec1>$@NULL@$</customdec1>' . "\n";
    $enrolments .= '      <customdec2>$@NULL@$</customdec2>' . "\n";
    $enrolments .= '      <customtext1>$@NULL@$</customtext1>' . "\n";
    $enrolments .= '      <customtext2>$@NULL@$</customtext2>' . "\n";
    $enrolments .= '      <customtext3>$@NULL@$</customtext3>' . "\n";
    $enrolments .= '      <customtext4>$@NULL@$</customtext4>' . "\n";
    $enrolments .= '      <timecreated>' . time() . '</timecreated>' . "\n";
    $enrolments .= '      <timemodified>' . time() . '</timemodified>' . "\n";
    $enrolments .= '      <user_enrolments>' . "\n";
    $enrolments .= '      </user_enrolments>' . "\n";
    $enrolments .= '    </enrol>' . "\n";
    $enrolments .= '  </enrols>' . "\n";
    $enrolments .= '</enrolments>' . "\n";
    file_put_contents(OUTPUT_DIR . '/course/enrolments.xml', $enrolments);
}

/**
 * Genera section.xml para una sección
 */
function generateSectionXML($section) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<section id="' . $section['id'] . '">' . "\n";
    $xml .= '  <number>' . $section['number'] . '</number>' . "\n";
    $xml .= '  <name>' . htmlspecialchars($section['name']) . '</name>' . "\n";
    $xml .= '  <summary>' . htmlspecialchars($section['summary']) . '</summary>' . "\n";
    $xml .= '  <summaryformat>1</summaryformat>' . "\n";
    $xml .= '  <sequence>' . $section['sequence'] . '</sequence>' . "\n";
    $xml .= '  <visible>1</visible>' . "\n";
    $xml .= '  <availabilityjson>$@NULL@$</availabilityjson>' . "\n";
    $xml .= '  <timemodified>' . time() . '</timemodified>' . "\n";
    $xml .= '</section>' . "\n";

    $sectionDir = OUTPUT_DIR . '/sections/section_' . $section['id'];
    file_put_contents($sectionDir . '/section.xml', $xml);

    // Generar inforef.xml para la sección
    $inforef = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $inforef .= '<inforef>' . "\n";
    $inforef .= '  <fileref>' . "\n";
    $inforef .= '  </fileref>' . "\n";
    $inforef .= '</inforef>' . "\n";
    file_put_contents($sectionDir . '/inforef.xml', $inforef);
}

/**
 * Genera los XMLs para una actividad
 */
function generateActivityXML($activity) {
    $actDir = OUTPUT_DIR . '/activities/' . $activity['type'] . '_' . $activity['moduleid'];

    if ($activity['type'] === 'resource') {
        generateResourceXML($activity, $actDir);
    } else if ($activity['type'] === 'label') {
        generateLabelXML($activity, $actDir);
    }

    // Generar archivos comunes
    generateActivityCommonFiles($activity, $actDir);
}

/**
 * Genera XMLs para mod_resource
 */
function generateResourceXML($activity, $actDir) {
    // module.xml
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<module id="' . $activity['moduleid'] . '" version="' . MOODLE_VERSION . '">' . "\n";
    $xml .= '  <modulename>resource</modulename>' . "\n";
    $xml .= '  <sectionid>' . $activity['sectionid'] . '</sectionid>' . "\n";
    $xml .= '  <sectionnumber>' . $activity['sectionnumber'] . '</sectionnumber>' . "\n";
    $xml .= '  <idnumber></idnumber>' . "\n";
    $xml .= '  <added>' . time() . '</added>' . "\n";
    $xml .= '  <score>0</score>' . "\n";
    $xml .= '  <indent>0</indent>' . "\n";
    $xml .= '  <visible>1</visible>' . "\n";
    $xml .= '  <visibleoncoursepage>1</visibleoncoursepage>' . "\n";
    $xml .= '  <visibleold>1</visibleold>' . "\n";
    $xml .= '  <groupmode>0</groupmode>' . "\n";
    $xml .= '  <groupingid>0</groupingid>' . "\n";
    $xml .= '  <completion>1</completion>' . "\n";
    $xml .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>' . "\n";
    $xml .= '  <completionview>1</completionview>' . "\n";
    $xml .= '  <completionexpected>0</completionexpected>' . "\n";
    $xml .= '  <completionpassgrade>0</completionpassgrade>' . "\n";
    $xml .= '  <availability>$@NULL@$</availability>' . "\n";
    $xml .= '  <showdescription>0</showdescription>' . "\n";
    $xml .= '  <downloadcontent>1</downloadcontent>' . "\n";
    $xml .= '  <lang></lang>' . "\n";
    $xml .= '  <tags>' . "\n";
    $xml .= '  </tags>' . "\n";
    $xml .= '</module>' . "\n";
    file_put_contents($actDir . '/module.xml', $xml);

    // resource.xml
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<activity id="' . $activity['instanceid'] . '" moduleid="' . $activity['moduleid'] . '" modulename="resource" contextid="' . $activity['contextid'] . '">' . "\n";
    $xml .= '  <resource id="' . $activity['instanceid'] . '">' . "\n";
    $xml .= '    <name>' . htmlspecialchars($activity['name']) . '</name>' . "\n";
    $xml .= '    <intro></intro>' . "\n";
    $xml .= '    <introformat>1</introformat>' . "\n";
    $xml .= '    <tobemigrated>0</tobemigrated>' . "\n";
    $xml .= '    <legacyfiles>0</legacyfiles>' . "\n";
    $xml .= '    <legacyfileslast>$@NULL@$</legacyfileslast>' . "\n";
    $xml .= '    <display>0</display>' . "\n";
    $xml .= '    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>' . "\n";
    $xml .= '    <filterfiles>0</filterfiles>' . "\n";
    $xml .= '    <revision>1</revision>' . "\n";
    $xml .= '    <timemodified>' . time() . '</timemodified>' . "\n";
    $xml .= '  </resource>' . "\n";
    $xml .= '</activity>' . "\n";
    file_put_contents($actDir . '/resource.xml', $xml);
}

/**
 * Genera XMLs para mod_label
 */
function generateLabelXML($activity, $actDir) {
    // module.xml
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<module id="' . $activity['moduleid'] . '" version="' . MOODLE_VERSION . '">' . "\n";
    $xml .= '  <modulename>label</modulename>' . "\n";
    $xml .= '  <sectionid>' . $activity['sectionid'] . '</sectionid>' . "\n";
    $xml .= '  <sectionnumber>' . $activity['sectionnumber'] . '</sectionnumber>' . "\n";
    $xml .= '  <idnumber></idnumber>' . "\n";
    $xml .= '  <added>' . time() . '</added>' . "\n";
    $xml .= '  <score>0</score>' . "\n";
    $xml .= '  <indent>0</indent>' . "\n";
    $xml .= '  <visible>1</visible>' . "\n";
    $xml .= '  <visibleoncoursepage>1</visibleoncoursepage>' . "\n";
    $xml .= '  <visibleold>1</visibleold>' . "\n";
    $xml .= '  <groupmode>0</groupmode>' . "\n";
    $xml .= '  <groupingid>0</groupingid>' . "\n";
    $xml .= '  <completion>0</completion>' . "\n";
    $xml .= '  <completiongradeitemnumber>$@NULL@$</completiongradeitemnumber>' . "\n";
    $xml .= '  <completionview>0</completionview>' . "\n";
    $xml .= '  <completionexpected>0</completionexpected>' . "\n";
    $xml .= '  <completionpassgrade>0</completionpassgrade>' . "\n";
    $xml .= '  <availability>$@NULL@$</availability>' . "\n";
    $xml .= '  <showdescription>0</showdescription>' . "\n";
    $xml .= '  <downloadcontent>1</downloadcontent>' . "\n";
    $xml .= '  <lang></lang>' . "\n";
    $xml .= '  <tags>' . "\n";
    $xml .= '  </tags>' . "\n";
    $xml .= '</module>' . "\n";
    file_put_contents($actDir . '/module.xml', $xml);

    // label.xml
    $content = isset($activity['content']) ? $activity['content'] : '';

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<activity id="' . $activity['instanceid'] . '" moduleid="' . $activity['moduleid'] . '" modulename="label" contextid="' . $activity['contextid'] . '">' . "\n";
    $xml .= '  <label id="' . $activity['instanceid'] . '">' . "\n";
    $xml .= '    <name>' . htmlspecialchars($activity['name']) . '</name>' . "\n";
    $xml .= '    <intro>' . htmlspecialchars($content) . '</intro>' . "\n";
    $xml .= '    <introformat>1</introformat>' . "\n";
    $xml .= '    <timemodified>' . time() . '</timemodified>' . "\n";
    $xml .= '  </label>' . "\n";
    $xml .= '</activity>' . "\n";
    file_put_contents($actDir . '/label.xml', $xml);
}

/**
 * Genera archivos comunes para actividades
 */
function generateActivityCommonFiles($activity, $actDir) {
    // grades.xml
    $grades = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $grades .= '<activity_gradebook>' . "\n";
    $grades .= '  <grade_items>' . "\n";
    $grades .= '  </grade_items>' . "\n";
    $grades .= '  <grade_letters>' . "\n";
    $grades .= '  </grade_letters>' . "\n";
    $grades .= '</activity_gradebook>' . "\n";
    file_put_contents($actDir . '/grades.xml', $grades);

    // roles.xml
    $roles = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $roles .= '<roles>' . "\n";
    $roles .= '  <role_overrides>' . "\n";
    $roles .= '  </role_overrides>' . "\n";
    $roles .= '  <role_assignments>' . "\n";
    $roles .= '  </role_assignments>' . "\n";
    $roles .= '</roles>' . "\n";
    file_put_contents($actDir . '/roles.xml', $roles);

    // filters.xml
    $filters = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $filters .= '<filters>' . "\n";
    $filters .= '  <filter_actives>' . "\n";
    $filters .= '  </filter_actives>' . "\n";
    $filters .= '  <filter_configs>' . "\n";
    $filters .= '  </filter_configs>' . "\n";
    $filters .= '</filters>' . "\n";
    file_put_contents($actDir . '/filters.xml', $filters);

    // comments.xml
    $comments = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $comments .= '<comments>' . "\n";
    $comments .= '</comments>' . "\n";
    file_put_contents($actDir . '/comments.xml', $comments);

    // calendar.xml
    $calendar = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $calendar .= '<events>' . "\n";
    $calendar .= '</events>' . "\n";
    file_put_contents($actDir . '/calendar.xml', $calendar);

    // competencies.xml
    $competencies = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $competencies .= '<course_module_competencies>' . "\n";
    $competencies .= '</course_module_competencies>' . "\n";
    file_put_contents($actDir . '/competencies.xml', $competencies);

    // inforef.xml (se actualizará después con referencias a archivos)
    $inforef = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $inforef .= '<inforef>' . "\n";
    $inforef .= '  <fileref>' . "\n";
    $inforef .= '  </fileref>' . "\n";
    $inforef .= '</inforef>' . "\n";
    file_put_contents($actDir . '/inforef.xml', $inforef);
}

/**
 * Genera archivos auxiliares globales
 */
function generateAuxiliaryFiles() {
    // roles.xml (global)
    $roles = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $roles .= '<roles_definition>' . "\n";
    $roles .= '</roles_definition>' . "\n";
    file_put_contents(OUTPUT_DIR . '/roles.xml', $roles);

    // scales.xml
    $scales = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $scales .= '<scales_definition>' . "\n";
    $scales .= '</scales_definition>' . "\n";
    file_put_contents(OUTPUT_DIR . '/scales.xml', $scales);

    // outcomes.xml
    $outcomes = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $outcomes .= '<outcomes_definition>' . "\n";
    $outcomes .= '</outcomes_definition>' . "\n";
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', $outcomes);

    // questions.xml
    $questions = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $questions .= '<question_categories>' . "\n";
    $questions .= '</question_categories>' . "\n";
    file_put_contents(OUTPUT_DIR . '/questions.xml', $questions);

    // groups.xml
    $groups = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $groups .= '<groups>' . "\n";
    $groups .= '  <groupings>' . "\n";
    $groups .= '  </groupings>' . "\n";
    $groups .= '</groups>' . "\n";
    file_put_contents(OUTPUT_DIR . '/groups.xml', $groups);

    // gradebook.xml
    $gradebook = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $gradebook .= '<gradebook>' . "\n";
    $gradebook .= '  <attributes>' . "\n";
    $gradebook .= '  </attributes>' . "\n";
    $gradebook .= '  <grade_categories>' . "\n";
    $gradebook .= '    <grade_category id="1">' . "\n";
    $gradebook .= '      <parent>$@NULL@$</parent>' . "\n";
    $gradebook .= '      <depth>1</depth>' . "\n";
    $gradebook .= '      <path>/1/</path>' . "\n";
    $gradebook .= '      <fullname>?</fullname>' . "\n";
    $gradebook .= '      <aggregation>13</aggregation>' . "\n";
    $gradebook .= '      <keephigh>0</keephigh>' . "\n";
    $gradebook .= '      <droplow>0</droplow>' . "\n";
    $gradebook .= '      <aggregateonlygraded>1</aggregateonlygraded>' . "\n";
    $gradebook .= '      <aggregateoutcomes>0</aggregateoutcomes>' . "\n";
    $gradebook .= '      <timecreated>' . time() . '</timecreated>' . "\n";
    $gradebook .= '      <timemodified>' . time() . '</timemodified>' . "\n";
    $gradebook .= '      <hidden>0</hidden>' . "\n";
    $gradebook .= '    </grade_category>' . "\n";
    $gradebook .= '  </grade_categories>' . "\n";
    $gradebook .= '  <grade_items>' . "\n";
    $gradebook .= '    <grade_item id="1">' . "\n";
    $gradebook .= '      <categoryid>$@NULL@$</categoryid>' . "\n";
    $gradebook .= '      <itemname>$@NULL@$</itemname>' . "\n";
    $gradebook .= '      <itemtype>course</itemtype>' . "\n";
    $gradebook .= '      <itemmodule>$@NULL@$</itemmodule>' . "\n";
    $gradebook .= '      <iteminstance>1</iteminstance>' . "\n";
    $gradebook .= '      <itemnumber>$@NULL@$</itemnumber>' . "\n";
    $gradebook .= '      <iteminfo>$@NULL@$</iteminfo>' . "\n";
    $gradebook .= '      <idnumber>$@NULL@$</idnumber>' . "\n";
    $gradebook .= '      <calculation>$@NULL@$</calculation>' . "\n";
    $gradebook .= '      <gradetype>1</gradetype>' . "\n";
    $gradebook .= '      <grademax>100.00000</grademax>' . "\n";
    $gradebook .= '      <grademin>0.00000</grademin>' . "\n";
    $gradebook .= '      <scaleid>$@NULL@$</scaleid>' . "\n";
    $gradebook .= '      <outcomeid>$@NULL@$</outcomeid>' . "\n";
    $gradebook .= '      <gradepass>0.00000</gradepass>' . "\n";
    $gradebook .= '      <multfactor>1.00000</multfactor>' . "\n";
    $gradebook .= '      <plusfactor>0.00000</plusfactor>' . "\n";
    $gradebook .= '      <aggregationcoef>0.00000</aggregationcoef>' . "\n";
    $gradebook .= '      <aggregationcoef2>0.00000</aggregationcoef2>' . "\n";
    $gradebook .= '      <weightoverride>0</weightoverride>' . "\n";
    $gradebook .= '      <sortorder>1</sortorder>' . "\n";
    $gradebook .= '      <display>0</display>' . "\n";
    $gradebook .= '      <decimals>$@NULL@$</decimals>' . "\n";
    $gradebook .= '      <hidden>0</hidden>' . "\n";
    $gradebook .= '      <locked>0</locked>' . "\n";
    $gradebook .= '      <locktime>0</locktime>' . "\n";
    $gradebook .= '      <needsupdate>0</needsupdate>' . "\n";
    $gradebook .= '      <timecreated>' . time() . '</timecreated>' . "\n";
    $gradebook .= '      <timemodified>' . time() . '</timemodified>' . "\n";
    $gradebook .= '      <grade_grades>' . "\n";
    $gradebook .= '      </grade_grades>' . "\n";
    $gradebook .= '    </grade_item>' . "\n";
    $gradebook .= '  </grade_items>' . "\n";
    $gradebook .= '  <grade_letters>' . "\n";
    $gradebook .= '  </grade_letters>' . "\n";
    $gradebook .= '  <grade_settings>' . "\n";
    $gradebook .= '  </grade_settings>' . "\n";
    $gradebook .= '</gradebook>' . "\n";
    file_put_contents(OUTPUT_DIR . '/gradebook.xml', $gradebook);

    // completion.xml
    $completion = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $completion .= '<course_completion>' . "\n";
    $completion .= '</course_completion>' . "\n";
    file_put_contents(OUTPUT_DIR . '/completion.xml', $completion);
}

/**
 * Procesa los archivos binarios y los copia a la estructura de backup
 */
function processFiles() {
    global $courseStructure, $allFiles, $nextFileId;

    foreach ($courseStructure['sections'] as $section) {
        foreach ($section['activities'] as $activity) {
            if (!empty($activity['filepath']) && file_exists($activity['filepath'])) {
                $filePath = $activity['filepath'];
                $fileName = $activity['filename'];

                // Calcular hash SHA1
                $contentHash = sha1_file($filePath);
                $fileSize = filesize($filePath);

                // Determinar MIME type
                $mimeType = getMimeType($fileName);

                // Crear directorio por hash
                $hashDir = substr($contentHash, 0, 2);
                $filesDir = OUTPUT_DIR . '/files/' . $hashDir;
                if (!is_dir($filesDir)) {
                    mkdir($filesDir, 0755, true);
                }

                // Copiar archivo
                $destPath = $filesDir . '/' . $contentHash;
                if (!file_exists($destPath)) {
                    copy($filePath, $destPath);
                    echo "    Copiado: $fileName\n";
                }

                // Agregar a la lista de archivos
                $fileId = $nextFileId++;
                $allFiles[] = [
                    'id' => $fileId,
                    'contenthash' => $contentHash,
                    'contextid' => $activity['contextid'],
                    'component' => 'mod_' . $activity['type'],
                    'filearea' => ($activity['type'] === 'resource') ? 'content' : 'intro',
                    'itemid' => ($activity['type'] === 'resource') ? 0 : $activity['instanceid'],
                    'filepath' => '/',
                    'filename' => $fileName,
                    'filesize' => $fileSize,
                    'mimetype' => $mimeType,
                    'timecreated' => time(),
                    'timemodified' => time(),
                    'moduleid' => $activity['moduleid'],
                    'type' => $activity['type']
                ];

                // Actualizar inforef.xml de la actividad
                updateActivityInforef($activity, $fileId);
            }
        }
    }
}

/**
 * Actualiza el inforef.xml de una actividad con referencias a archivos
 */
function updateActivityInforef($activity, $fileId) {
    $actDir = OUTPUT_DIR . '/activities/' . $activity['type'] . '_' . $activity['moduleid'];

    $inforef = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $inforef .= '<inforef>' . "\n";
    $inforef .= '  <fileref>' . "\n";
    $inforef .= '    <file>' . "\n";
    $inforef .= '      <id>' . $fileId . '</id>' . "\n";
    $inforef .= '    </file>' . "\n";
    $inforef .= '  </fileref>' . "\n";
    $inforef .= '</inforef>' . "\n";

    file_put_contents($actDir . '/inforef.xml', $inforef);
}

/**
 * Genera files.xml
 */
function generateFilesXML() {
    global $allFiles;

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<files>' . "\n";

    foreach ($allFiles as $file) {
        $xml .= '  <file id="' . $file['id'] . '">' . "\n";
        $xml .= '    <contenthash>' . $file['contenthash'] . '</contenthash>' . "\n";
        $xml .= '    <contextid>' . $file['contextid'] . '</contextid>' . "\n";
        $xml .= '    <component>' . $file['component'] . '</component>' . "\n";
        $xml .= '    <filearea>' . $file['filearea'] . '</filearea>' . "\n";
        $xml .= '    <itemid>' . $file['itemid'] . '</itemid>' . "\n";
        $xml .= '    <filepath>' . $file['filepath'] . '</filepath>' . "\n";
        $xml .= '    <filename>' . htmlspecialchars($file['filename']) . '</filename>' . "\n";
        $xml .= '    <userid>$@NULL@$</userid>' . "\n";
        $xml .= '    <filesize>' . $file['filesize'] . '</filesize>' . "\n";
        $xml .= '    <mimetype>' . $file['mimetype'] . '</mimetype>' . "\n";
        $xml .= '    <status>0</status>' . "\n";
        $xml .= '    <timecreated>' . $file['timecreated'] . '</timecreated>' . "\n";
        $xml .= '    <timemodified>' . $file['timemodified'] . '</timemodified>' . "\n";
        $xml .= '    <source>' . htmlspecialchars($file['filename']) . '</source>' . "\n";
        $xml .= '    <author>Jaqui Esquitino</author>' . "\n";
        $xml .= '    <license>allrightsreserved</license>' . "\n";
        $xml .= '    <sortorder>0</sortorder>' . "\n";
        $xml .= '    <repositorytype>$@NULL@$</repositorytype>' . "\n";
        $xml .= '    <repositoryid>$@NULL@$</repositoryid>' . "\n";
        $xml .= '    <reference>$@NULL@$</reference>' . "\n";
        $xml .= '  </file>' . "\n";
    }

    $xml .= '</files>' . "\n";

    file_put_contents(OUTPUT_DIR . '/files.xml', $xml);
    echo "  Archivos procesados: " . count($allFiles) . "\n";
}

/**
 * Crea el archivo MBZ final
 */
function createMBZ() {
    $mbzPath = OUTPUT_DIR . '/' . MBZ_FILENAME;

    // Eliminar archivo existente
    if (file_exists($mbzPath)) {
        unlink($mbzPath);
    }

    // Crear ZIP
    $zip = new ZipArchive();
    if ($zip->open($mbzPath, ZipArchive::CREATE) !== TRUE) {
        die("Error: No se pudo crear el archivo ZIP\n");
    }

    // Agregar todos los archivos
    addDirectoryToZip($zip, OUTPUT_DIR, '');

    $numFiles = $zip->numFiles;
    $zip->close();

    echo "  Archivo MBZ creado con $numFiles archivos\n";
    echo "  Tamaño: " . formatBytes(filesize($mbzPath)) . "\n";
}

/**
 * Agrega un directorio recursivamente al ZIP
 */
function addDirectoryToZip($zip, $dir, $zipPath) {
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $dir . '/' . $file;
        $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;

        // No incluir el archivo .mbz en sí mismo
        if (pathinfo($file, PATHINFO_EXTENSION) === 'mbz') {
            continue;
        }

        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipFilePath);
            addDirectoryToZip($zip, $filePath, $zipFilePath);
        } else {
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}

/**
 * Obtiene el MIME type de un archivo
 */
function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'mp3' => 'audio/mpeg',
        'txt' => 'text/plain',
        'html' => 'text/html',
        'htm' => 'text/html',
        'zip' => 'application/zip',
    ];

    return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
}

/**
 * Genera un ID único
 */
function generateUniqueId() {
    return bin2hex(random_bytes(16));
}

/**
 * Formatea bytes a formato legible
 */
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Ejecutar
main();
