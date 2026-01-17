<?php
/**
 * Generador de MBZ v3.0 - Curso "Huellas Invisibles"
 *
 * VERSIÓN CONSERVADORA: Solo usa archivos reales del curso
 * NO inventa contenido (glosarios, objetivos, preguntas, etc.)
 *
 * Estructura basada EXACTAMENTE en los archivos existentes:
 * - mod_resource para PDFs y DOCX
 * - mod_label para imagen del curso
 *
 * Sitio: https://campus.rednadi.com
 * Directora: Jaqui Esquitino
 */

// ============================================================================
// CONFIGURACIÓN
// ============================================================================

define('CONTENT_DIR', __DIR__ . '/../mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_v3');
define('MBZ_FILENAME', 'backup-huellas-invisibles-v3-' . date('Ymd') . '.mbz');

define('COURSE_FULLNAME', 'Huellas Invisibles: Neurociencia del Desarrollo Infantil');
define('COURSE_SHORTNAME', 'huellas-invisibles');
define('MOODLE_VERSION', '2024042200');
define('ORIGINAL_WWWROOT', 'https://campus.rednadi.com');

// Contadores globales
$nextModuleId = 1;
$nextContextId = 100;
$nextFileId = 1;
$nextInstanceId = 1;
$allFiles = [];
$allActivities = [];
$allSections = [];

// ============================================================================
// DEFINICIÓN DE ESTRUCTURA BASADA EN ARCHIVOS REALES
// ============================================================================

// Estructura exacta de carpetas y archivos del curso
// Incluye video_placeholder para los marcadores de video (.txt) que indican donde se deben subir los .mp4
$COURSE_STRUCTURE = [
    0 => [
        'name' => 'Presentación del Curso',
        'summary' => 'Bienvenido al curso Huellas Invisibles',
        'dir' => null, // Archivos en raíz
        'files' => [
            ['file' => 'huellas invisibles.png', 'type' => 'label', 'name' => 'Imagen del Curso'],
            ['file' => 'Titulo presentacion y programa.docx', 'type' => 'resource', 'name' => 'Título, Presentación y Programa'],
        ]
    ],
    1 => [
        'name' => 'Capítulo 1: Desarrollo Neurológico Infantil',
        'summary' => 'Desarrollo Neurológico Infantil',
        'dir' => 'Capitulo 1 Desarrollo Neurológico Infantil',
        'files' => [
            ['file' => '1-Capitulo 1 Desarrollo Neurologico Infantil_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 1 - Desarrollo Neurológico Infantil', 'video' => '1-Capitulo 1 Desarrollo Neurologico Infantil_0.mp4'],
            ['file' => '2-Esculpiendo el Cerebro Primera Infancia (Cap 1).pdf', 'type' => 'resource', 'name' => 'Libro: Esculpiendo el Cerebro - Primera Infancia'],
            ['file' => '3-DOCUMENTO DE ESTUDIO COMPLEMENTARIO (Cap 1).pdf', 'type' => 'resource', 'name' => 'Documento de Estudio Complementario'],
            ['file' => '4- Glosario de Términos Clave (Cap 1).pdf', 'type' => 'resource', 'name' => 'Glosario de Términos Clave'],
            ['file' => '5- Bibliografia de referencia y recomendada (Cap 1).pdf', 'type' => 'resource', 'name' => 'Bibliografía de Referencia'],
            ['file' => '6-Autoevaluacion- Tests Capitulo 1.pdf', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
            ['file' => '7-Material complementario Video como funciona el cerebro.docx', 'type' => 'resource', 'name' => 'Material Complementario - Video'],
        ]
    ],
    2 => [
        'name' => 'Capítulo 2: Funciones Emocionales',
        'summary' => 'Funciones Emocionales',
        'dir' => 'Capitulo 2- Funciones emocionales_',
        'files' => [
            ['file' => '1-Capitulo 2 Funciones emocionales.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 2 - Funciones Emocionales', 'video' => '1-Capitulo 2 Funciones emocionales.mp4'],
            ['file' => '2- CEREBRO EN CONSTRUCCION.pdf', 'type' => 'resource', 'name' => 'Libro: Cerebro en Construcción'],
            ['file' => '3-DOCUMENTO COMPLEMENTARIO CAP 2.pdf', 'type' => 'resource', 'name' => 'Documento Complementario'],
            ['file' => '4-BIBLIOGRAFIA CAP 2.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '5-GLOSARIO CAP.2.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '6-TEST DE AUTOEVALUACION.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    3 => [
        'name' => 'Capítulo 3: Plasticidad Cerebral',
        'summary' => 'Plasticidad Cerebral',
        'dir' => 'Capitulo 3 - Plasticidad Cerebral',
        'files' => [
            ['file' => '1- Capitulo 3 Plasticidad Cerebral.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 3 - Plasticidad Cerebral', 'video' => '1- Capitulo 3 Plasticidad Cerebral.mp4'],
            ['file' => '2-El Cerebro Moldeable Principios y Práctica.pdf', 'type' => 'resource', 'name' => 'Libro: El Cerebro Moldeable - Principios y Práctica'],
            ['file' => '3-Documento de estudio cap 3.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO CAP 3.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAP 3.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6- test Capitulo 3 Plasticidad cerebral.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    4 => [
        'name' => 'Capítulo 4: Sinaptogénesis',
        'summary' => 'Sinaptogénesis',
        'dir' => 'Capitulo 4- Sinaptogénesis',
        'files' => [
            ['file' => '1- Capitulo 4 Sinaptogenesis.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 4 - Sinaptogénesis', 'video' => '1- Capitulo 4 Sinaptogenesis.mp4'],
            ['file' => '2-El Cerebro Plástico De la Construcción a la Maestría.pdf', 'type' => 'resource', 'name' => 'Libro: El Cerebro Plástico - De la Construcción a la Maestría'],
            ['file' => '3-Documento de estudio cap 4.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO CAP 4.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAP 4.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '5-Test Capitulo 4.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    5 => [
        'name' => 'Capítulo 5: Período Sensoriomotor',
        'summary' => 'Período Sensoriomotor',
        'dir' => 'Capitulo 5 -  Período sensoriomotor',
        'files' => [
            ['file' => '1-Capitulo 5 Periodo sensoriomotor_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 5 - Período Sensoriomotor', 'video' => '1-Capitulo 5 Periodo sensoriomotor_0.mp4'],
            ['file' => '2-Construyendo_la_Realidad_La_Mente_Sensoriomotora2.pdf', 'type' => 'resource', 'name' => 'Libro: Construyendo la Realidad - La Mente Sensoriomotora'],
            ['file' => '3-Documento Complementario de Estudio Cap 5.pdf', 'type' => 'resource', 'name' => 'Documento Complementario de Estudio'],
            ['file' => '4-GLOSARIO CAPITULO 5.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 5.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6- test Capitulo 5.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    6 => [
        'name' => 'Capítulo 6: Estrés y Apego',
        'summary' => 'Estrés y Apego',
        'dir' => 'Capitulo 6 - Stress y Apego',
        'files' => [
            ['file' => '1-Capitulo 6 Stress y apego.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 6 - Estrés y Apego', 'video' => '1-Capitulo 6 Stress y apego.mp4'],
            ['file' => '2-Arquitectos_del_Cerebro_Infantil_Apego_y_Estrés.pdf', 'type' => 'resource', 'name' => 'Libro: Arquitectos del Cerebro Infantil - Apego y Estrés'],
            ['file' => '3-Documento de estudio capitulo 6.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO Capitulo 6.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5- BIBLIOGRAFIA CAPITULO 6.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6- Test Cap 6.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    7 => [
        'name' => 'Capítulo 7: Teoría Epigenética',
        'summary' => 'Teoría Epigenética',
        'dir' => 'Capitulo 7- Teoría Epigenética',
        'files' => [
            ['file' => '1-Capitulo 7 Epigenetica_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 7 - Epigenética', 'video' => '1-Capitulo 7 Epigenetica_0.mp4'],
            ['file' => '2-Huellas Invisibles Entorno y Genes.pdf', 'type' => 'resource', 'name' => 'Libro: Huellas Invisibles - Entorno y Genes'],
            ['file' => '3-Documento de estudio Capitulo 7.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO Capitulo 7.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 7.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6-Test Capitulo 7.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    8 => [
        'name' => 'Capítulo 8: Reflejo de Apnea',
        'summary' => 'Reflejo de Apnea',
        'dir' => 'Capitulo 8 - Reflejo de Apnea',
        'files' => [
            ['file' => '1-Capitulo 8 Reflejo de apnea_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 8 - Reflejo de Apnea', 'video' => '1-Capitulo 8 Reflejo de apnea_0.mp4'],
            ['file' => '2-Ancestral Water Echo.pdf', 'type' => 'resource', 'name' => 'Libro: Ancestral Water Echo'],
            ['file' => '3-Documento de estudio Capitulo 8.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO Capitulo 8.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 8.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6-test Capitulo 8 8.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    9 => [
        'name' => 'Capítulo 9: Socialización en la Primera Infancia',
        'summary' => 'Socialización en la Primera Infancia',
        'dir' => 'Capitulo 9 - Socialización en la 1er infancia',
        'files' => [
            ['file' => '1-Capitulo 9 Socializacion.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 9 - Socialización', 'video' => '1-Capitulo 9 Socializacion.mp4'],
            ['file' => '2-Arquitectos de Mentes Construyendo el Cerebro.pdf', 'type' => 'resource', 'name' => 'Libro: Arquitectos de Mentes - Construyendo el Cerebro'],
            ['file' => '3-Documento de estudio Capitulo 9.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO Capitulo 9.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5- BIBLIOGRAFIA capitulo 9.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6-TEST CAPITULO 9.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    10 => [
        'name' => 'Capítulo 10: La Atención',
        'summary' => 'La Atención',
        'dir' => 'Capitulo 10- La Atención',
        'files' => [
            ['file' => 'Capitulo 10 Atencion_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Capítulo 10 - La Atención', 'video' => 'Capitulo 10 Atencion_0.mp4'],
            ['file' => '2-El Arquitecto Invisible Un Viaje al Corazón de la Atención.pdf', 'type' => 'resource', 'name' => 'Libro: El Arquitecto Invisible - Un Viaje al Corazón de la Atención'],
            ['file' => '3-Documento de estudio Capitulo 10.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['file' => '4-GLOSARIO Capitulo 10.pdf', 'type' => 'resource', 'name' => 'Glosario'],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 10.pdf', 'type' => 'resource', 'name' => 'Bibliografía'],
            ['file' => '6-Test capitulo 10.docx', 'type' => 'resource', 'name' => 'Test de Autoevaluación'],
        ]
    ],
    11 => [
        'name' => 'Cierre del Curso',
        'summary' => 'Cierre del Curso',
        'dir' => null,
        'files' => [
            ['file' => 'Encuesta De Opinión – Cierre Curso Huellas Invisibles.docx', 'type' => 'resource', 'name' => 'Encuesta de Opinión - Cierre del Curso'],
        ]
    ]
];

// SVG del icono de video para los placeholders
$VIDEO_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 120" width="200" height="120">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" style="stop-color:#1a237e;stop-opacity:1"/>
      <stop offset="100%" style="stop-color:#3949ab;stop-opacity:1"/>
    </linearGradient>
    <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="2" dy="2" stdDeviation="3" flood-color="#000" flood-opacity="0.3"/>
    </filter>
  </defs>
  <rect x="5" y="5" width="190" height="110" rx="10" fill="url(#bgGrad)" filter="url(#shadow)"/>
  <circle cx="100" cy="60" r="35" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="3"/>
  <polygon points="90,45 90,75 120,60" fill="white"/>
  <text x="100" y="105" font-family="Arial, sans-serif" font-size="10" fill="white" text-anchor="middle" opacity="0.8">Subir video .mp4</text>
</svg>';

// ============================================================================
// FUNCIÓN PRINCIPAL
// ============================================================================

function main() {
    global $COURSE_STRUCTURE, $allSections, $allActivities;

    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  Generador MBZ v3.0 - Huellas Invisibles (Conservador)      ║\n";
    echo "║  Solo archivos reales - Sin contenido inventado             ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    // Fase 1: Crear estructura
    echo "📁 FASE 1: Creando estructura de directorios...\n";
    createDirectories();

    // Fase 2: Verificar archivos existentes
    echo "\n🔍 FASE 2: Verificando archivos existentes...\n";
    $verifiedStructure = verifyFiles();

    // Fase 3: Procesar secciones y actividades
    echo "\n📚 FASE 3: Procesando secciones y actividades...\n";
    foreach ($verifiedStructure as $sectionNum => $section) {
        processSection($sectionNum, $section);
    }

    // Fase 4: Generar XMLs globales
    echo "\n📄 FASE 4: Generando archivos XML globales...\n";
    generateGlobalXMLs();

    // Fase 5: Procesar archivos binarios
    echo "\n📦 FASE 5: Procesando archivos binarios...\n";
    processFiles();

    // Fase 6: Crear MBZ
    echo "\n🗜️  FASE 6: Creando archivo MBZ...\n";
    createMBZ();

    // Resumen
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ RESUMEN:\n";
    echo "   - Secciones: " . count($allSections) . "\n";
    echo "   - Actividades: " . count($allActivities) . "\n";
    echo "   - Archivo: " . OUTPUT_DIR . '/' . MBZ_FILENAME . "\n";
    echo str_repeat("=", 60) . "\n";
}

function createDirectories() {
    $dirs = [OUTPUT_DIR, OUTPUT_DIR.'/course', OUTPUT_DIR.'/sections', OUTPUT_DIR.'/activities', OUTPUT_DIR.'/files'];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "   ✓ $dir\n";
        }
    }
}

function verifyFiles() {
    global $COURSE_STRUCTURE;
    $verified = [];
    $totalFiles = 0;
    $foundFiles = 0;

    foreach ($COURSE_STRUCTURE as $sectionNum => $section) {
        $verified[$sectionNum] = [
            'name' => $section['name'],
            'summary' => $section['summary'],
            'dir' => $section['dir'],
            'files' => []
        ];

        foreach ($section['files'] as $fileInfo) {
            $totalFiles++;
            $basePath = CONTENT_DIR;
            if ($section['dir']) {
                $basePath .= '/' . $section['dir'];
            }
            $fullPath = $basePath . '/' . $fileInfo['file'];

            if (file_exists($fullPath)) {
                $verifiedFile = [
                    'file' => $fileInfo['file'],
                    'type' => $fileInfo['type'],
                    'name' => $fileInfo['name'],
                    'path' => $fullPath,
                    'size' => filesize($fullPath)
                ];
                // Para video_placeholder, incluir el nombre del video esperado
                if ($fileInfo['type'] === 'video_placeholder' && isset($fileInfo['video'])) {
                    $verifiedFile['video'] = $fileInfo['video'];
                }
                $verified[$sectionNum]['files'][] = $verifiedFile;
                $foundFiles++;
                $typeLabel = ($fileInfo['type'] === 'video_placeholder') ? ' [VIDEO PLACEHOLDER]' : '';
                echo "   ✓ {$fileInfo['file']}{$typeLabel}\n";
            } else {
                echo "   ✗ NO ENCONTRADO: {$fileInfo['file']}\n";
            }
        }
    }

    echo "\n   Archivos encontrados: {$foundFiles}/{$totalFiles}\n";
    return $verified;
}

function processSection($sectionNum, $section) {
    global $allSections, $allActivities, $nextModuleId, $nextContextId, $nextInstanceId;

    $sectionId = $sectionNum + 1;

    // Crear directorio de sección
    $sectionDir = OUTPUT_DIR . "/sections/section_{$sectionId}";
    if (!is_dir($sectionDir)) mkdir($sectionDir, 0755, true);

    $activities = [];

    foreach ($section['files'] as $fileInfo) {
        $moduleId = $nextModuleId++;
        $instanceId = $nextInstanceId++;
        $contextId = $nextContextId++;

        // Para video_placeholder, usamos 'resource' como tipo real de actividad
        $activityType = ($fileInfo['type'] === 'video_placeholder') ? 'resource' : $fileInfo['type'];

        $activity = [
            'moduleid' => $moduleId,
            'instanceid' => $instanceId,
            'contextid' => $contextId,
            'sectionid' => $sectionId,
            'sectionnumber' => $sectionNum,
            'type' => $activityType,
            'original_type' => $fileInfo['type'], // Guardar tipo original para procesamiento
            'name' => $fileInfo['name'],
            'filepath' => $fileInfo['path'],
            'filename' => $fileInfo['file']
        ];

        // Para video_placeholder, guardar nombre del video esperado
        if ($fileInfo['type'] === 'video_placeholder' && isset($fileInfo['video'])) {
            $activity['video'] = $fileInfo['video'];
        }

        // Crear directorio de actividad
        $actDir = OUTPUT_DIR . "/activities/{$activityType}_{$moduleId}";
        if (!is_dir($actDir)) mkdir($actDir, 0755, true);

        // Generar XMLs de la actividad
        if ($fileInfo['type'] === 'resource') {
            generateResourceXML($activity, $actDir);
        } else if ($fileInfo['type'] === 'label') {
            generateLabelXML($activity, $actDir);
        } else if ($fileInfo['type'] === 'video_placeholder') {
            generateVideoPlaceholderXML($activity, $actDir);
        }

        generateActivityCommonXMLs($actDir);

        $activities[] = $activity;
        $allActivities[] = $activity;
    }

    // Guardar sección
    $allSections[] = [
        'id' => $sectionId,
        'number' => $sectionNum,
        'name' => $section['name'],
        'summary' => $section['summary'],
        'activities' => $activities,
        'sequence' => implode(',', array_column($activities, 'moduleid'))
    ];

    echo "   ✓ Sección {$sectionNum}: {$section['name']} (" . count($activities) . " actividades)\n";
}

function generateResourceXML($activity, $dir) {
    $time = time();
    $nameEsc = htmlspecialchars($activity['name']);

    // module.xml
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>resource</modulename>
  <sectionid>{$activity['sectionid']}</sectionid>
  <sectionnumber>{$activity['sectionnumber']}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>1</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($dir . '/module.xml', $moduleXml);

    // resource.xml
    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="resource" contextid="{$activity['contextid']}">
  <resource id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro></intro>
    <introformat>1</introformat>
    <tobemigrated>0</tobemigrated>
    <legacyfiles>0</legacyfiles>
    <legacyfileslast>\$@NULL@\$</legacyfileslast>
    <display>0</display>
    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>
    <filterfiles>0</filterfiles>
    <revision>1</revision>
    <timemodified>{$time}</timemodified>
  </resource>
</activity>
XML;
    file_put_contents($dir . '/resource.xml', $resourceXml);
}

function generateLabelXML($activity, $dir) {
    $time = time();
    $nameEsc = htmlspecialchars($activity['name']);
    $filenameUrl = rawurlencode($activity['filename']);

    // Contenido del label: mostrar la imagen
    $content = '<p style="text-align: center;"><img src="@@PLUGINFILE@@/' . $filenameUrl . '" alt="' . $nameEsc . '" style="max-width: 100%;" /></p>';
    $contentEsc = htmlspecialchars($content);

    // module.xml
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>label</modulename>
  <sectionid>{$activity['sectionid']}</sectionid>
  <sectionnumber>{$activity['sectionnumber']}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>0</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>0</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>0</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($dir . '/module.xml', $moduleXml);

    // label.xml
    $labelXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="label" contextid="{$activity['contextid']}">
  <label id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro>{$contentEsc}</intro>
    <introformat>1</introformat>
    <timemodified>{$time}</timemodified>
  </label>
</activity>
XML;
    file_put_contents($dir . '/label.xml', $labelXml);
}

function generateVideoPlaceholderXML($activity, $dir) {
    global $VIDEO_ICON_SVG;
    $time = time();
    $nameEsc = htmlspecialchars($activity['name']);
    $videoName = isset($activity['video']) ? $activity['video'] : 'video.mp4';
    $videoNameEsc = htmlspecialchars($videoName);

    // module.xml - como resource
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>resource</modulename>
  <sectionid>{$activity['sectionid']}</sectionid>
  <sectionnumber>{$activity['sectionnumber']}</sectionnumber>
  <idnumber></idnumber>
  <added>{$time}</added>
  <score>0</score>
  <indent>0</indent>
  <visible>1</visible>
  <visibleoncoursepage>1</visibleoncoursepage>
  <visibleold>1</visibleold>
  <groupmode>0</groupmode>
  <groupingid>0</groupingid>
  <completion>1</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>1</completionview>
  <completionexpected>0</completionexpected>
  <completionpassgrade>0</completionpassgrade>
  <availability>\$@NULL@\$</availability>
  <showdescription>1</showdescription>
  <downloadcontent>1</downloadcontent>
  <lang></lang>
  <tags></tags>
</module>
XML;
    file_put_contents($dir . '/module.xml', $moduleXml);

    // Descripción con SVG y mensaje
    $intro = '<div style="text-align: center; padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; margin: 10px 0;">';
    $intro .= '<div style="display: inline-block;">' . $VIDEO_ICON_SVG . '</div>';
    $intro .= '<p style="color: white; font-size: 14px; margin-top: 15px;"><strong>Video pendiente de subir:</strong></p>';
    $intro .= '<p style="color: #e0e0e0; font-size: 12px;">' . $videoNameEsc . '</p>';
    $intro .= '<p style="color: #b0b0b0; font-size: 11px; margin-top: 10px;"><em>Por favor, suba el archivo .mp4 correspondiente editando este recurso</em></p>';
    $intro .= '</div>';
    $introEsc = htmlspecialchars($intro);

    // resource.xml
    $resourceXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="resource" contextid="{$activity['contextid']}">
  <resource id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro>{$introEsc}</intro>
    <introformat>1</introformat>
    <tobemigrated>0</tobemigrated>
    <legacyfiles>0</legacyfiles>
    <legacyfileslast>\$@NULL@\$</legacyfileslast>
    <display>0</display>
    <displayoptions>a:1:{s:10:"printintro";i:1;}</displayoptions>
    <filterfiles>0</filterfiles>
    <revision>1</revision>
    <timemodified>{$time}</timemodified>
  </resource>
</activity>
XML;
    file_put_contents($dir . '/resource.xml', $resourceXml);
}

function generateActivityCommonXMLs($dir) {
    file_put_contents($dir . '/grades.xml', '<?xml version="1.0" encoding="UTF-8"?>
<activity_gradebook><grade_items></grade_items><grade_letters></grade_letters></activity_gradebook>');

    file_put_contents($dir . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>
<roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');

    file_put_contents($dir . '/filters.xml', '<?xml version="1.0" encoding="UTF-8"?>
<filters><filter_actives></filter_actives><filter_configs></filter_configs></filters>');

    file_put_contents($dir . '/comments.xml', '<?xml version="1.0" encoding="UTF-8"?><comments></comments>');
    file_put_contents($dir . '/calendar.xml', '<?xml version="1.0" encoding="UTF-8"?><events></events>');
    file_put_contents($dir . '/competencies.xml', '<?xml version="1.0" encoding="UTF-8"?><course_module_competencies></course_module_competencies>');
    file_put_contents($dir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref></inforef>');
}

function generateGlobalXMLs() {
    global $allSections, $allActivities;
    $time = time();
    $backupId = bin2hex(random_bytes(16));

    // moodle_backup.xml
    $activitiesXml = '';
    foreach ($allActivities as $act) {
        $titleEsc = htmlspecialchars($act['name']);
        $activitiesXml .= "        <activity><moduleid>{$act['moduleid']}</moduleid><sectionid>{$act['sectionid']}</sectionid><modulename>{$act['type']}</modulename><title>{$titleEsc}</title><directory>activities/{$act['type']}_{$act['moduleid']}</directory></activity>\n";
    }

    $sectionsXml = '';
    foreach ($allSections as $sec) {
        $titleEsc = htmlspecialchars($sec['name']);
        $sectionsXml .= "        <section><sectionid>{$sec['id']}</sectionid><title>{$titleEsc}</title><directory>sections/section_{$sec['id']}</directory></section>\n";
    }

    $backupXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-huellas-invisibles-v3-{$time}</name>
    <moodle_version>2024042200</moodle_version>
    <moodle_release>4.4</moodle_release>
    <backup_version>2024042200</backup_version>
    <backup_release>4.4</backup_release>
    <backup_date>{$time}</backup_date>
    <mnet_remoteusers>0</mnet_remoteusers>
    <include_files>1</include_files>
    <include_file_references_to_external_content>0</include_file_references_to_external_content>
    <original_wwwroot>https://campus.rednadi.com</original_wwwroot>
    <original_site_identifier_hash>{$backupId}</original_site_identifier_hash>
    <original_course_id>1</original_course_id>
    <original_course_format>topics</original_course_format>
    <original_course_fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</original_course_fullname>
    <original_course_shortname>huellas-invisibles</original_course_shortname>
    <original_course_startdate>{$time}</original_course_startdate>
    <original_course_enddate>0</original_course_enddate>
    <original_course_contextid>50</original_course_contextid>
    <original_system_contextid>1</original_system_contextid>
    <details><detail backup_id="{$backupId}"><type>course</type><format>moodle2</format><interactive>1</interactive><mode>10</mode><execution>1</execution><executiontime>0</executiontime></detail></details>
    <contents>
      <activities>
{$activitiesXml}      </activities>
      <sections>
{$sectionsXml}      </sections>
      <course><courseid>1</courseid><title>Huellas Invisibles: Neurociencia del Desarrollo Infantil</title><directory>course</directory></course>
    </contents>
    <settings>
      <setting><level>root</level><name>filename</name><value>backup-huellas-invisibles-v3.mbz</value></setting>
      <setting><level>root</level><name>users</name><value>0</value></setting>
      <setting><level>root</level><name>activities</name><value>1</value></setting>
      <setting><level>root</level><name>files</name><value>1</value></setting>
    </settings>
  </information>
</moodle_backup>
XML;
    file_put_contents(OUTPUT_DIR . '/moodle_backup.xml', $backupXml);

    // course.xml
    $courseXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<course id="1" contextid="50">
  <shortname>huellas-invisibles</shortname>
  <fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</fullname>
  <idnumber></idnumber>
  <summary>Curso de neurociencia del desarrollo infantil dirigido por Jaqui Esquitino - Red NADI</summary>
  <summaryformat>1</summaryformat>
  <format>topics</format>
  <showgrades>1</showgrades>
  <newsitems>5</newsitems>
  <startdate>{$time}</startdate>
  <enddate>0</enddate>
  <marker>0</marker>
  <maxbytes>0</maxbytes>
  <legacyfiles>0</legacyfiles>
  <showreports>0</showreports>
  <visible>1</visible>
  <groupmode>0</groupmode>
  <groupmodeforce>0</groupmodeforce>
  <defaultgroupingid>0</defaultgroupingid>
  <lang></lang>
  <theme></theme>
  <timecreated>{$time}</timecreated>
  <timemodified>{$time}</timemodified>
  <requested>0</requested>
  <showactivitydates>1</showactivitydates>
  <showcompletionconditions>1</showcompletionconditions>
  <pdfexportfont></pdfexportfont>
  <enablecompletion>1</enablecompletion>
  <completionnotify>0</completionnotify>
  <category id="1"><name>Cursos NADI</name><description></description></category>
  <tags></tags>
  <customfields></customfields>
  <courseformatoptions></courseformatoptions>
</course>
XML;
    file_put_contents(OUTPUT_DIR . '/course/course.xml', $courseXml);
    file_put_contents(OUTPUT_DIR . '/course/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref></inforef>');
    file_put_contents(OUTPUT_DIR . '/course/roles.xml', '<?xml version="1.0" encoding="UTF-8"?><roles><role_overrides></role_overrides><role_assignments></role_assignments></roles>');

    // enrolments.xml
    $enrolXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<enrolments>
  <enrols>
    <enrol id="1">
      <enrol>manual</enrol>
      <status>0</status>
      <name>\$@NULL@\$</name>
      <enrolperiod>0</enrolperiod>
      <enrolstartdate>0</enrolstartdate>
      <enrolenddate>0</enrolenddate>
      <expirynotify>0</expirynotify>
      <expirythreshold>86400</expirythreshold>
      <notifyall>0</notifyall>
      <password>\$@NULL@\$</password>
      <cost>\$@NULL@\$</cost>
      <currency>\$@NULL@\$</currency>
      <roleid>5</roleid>
      <customint1>\$@NULL@\$</customint1>
      <customint2>\$@NULL@\$</customint2>
      <customint3>\$@NULL@\$</customint3>
      <customint4>\$@NULL@\$</customint4>
      <customint5>\$@NULL@\$</customint5>
      <customint6>\$@NULL@\$</customint6>
      <customint7>\$@NULL@\$</customint7>
      <customint8>\$@NULL@\$</customint8>
      <customchar1>\$@NULL@\$</customchar1>
      <customchar2>\$@NULL@\$</customchar2>
      <customchar3>\$@NULL@\$</customchar3>
      <customdec1>\$@NULL@\$</customdec1>
      <customdec2>\$@NULL@\$</customdec2>
      <customtext1>\$@NULL@\$</customtext1>
      <customtext2>\$@NULL@\$</customtext2>
      <customtext3>\$@NULL@\$</customtext3>
      <customtext4>\$@NULL@\$</customtext4>
      <timecreated>{$time}</timecreated>
      <timemodified>{$time}</timemodified>
      <user_enrolments></user_enrolments>
    </enrol>
  </enrols>
</enrolments>
XML;
    file_put_contents(OUTPUT_DIR . '/course/enrolments.xml', $enrolXml);

    // Secciones
    foreach ($allSections as $sec) {
        $dir = OUTPUT_DIR . "/sections/section_{$sec['id']}";
        $nameEsc = htmlspecialchars($sec['name']);
        $summaryEsc = htmlspecialchars($sec['summary']);

        $sectionXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section id="{$sec['id']}">
  <number>{$sec['number']}</number>
  <name>{$nameEsc}</name>
  <summary>{$summaryEsc}</summary>
  <summaryformat>1</summaryformat>
  <sequence>{$sec['sequence']}</sequence>
  <visible>1</visible>
  <availabilityjson>\$@NULL@\$</availabilityjson>
  <timemodified>{$time}</timemodified>
</section>
XML;
        file_put_contents($dir . '/section.xml', $sectionXml);
        file_put_contents($dir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?><inforef><fileref></fileref></inforef>');
    }

    // Archivos auxiliares globales
    file_put_contents(OUTPUT_DIR . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?><roles_definition></roles_definition>');
    file_put_contents(OUTPUT_DIR . '/scales.xml', '<?xml version="1.0" encoding="UTF-8"?><scales_definition></scales_definition>');
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', '<?xml version="1.0" encoding="UTF-8"?><outcomes_definition></outcomes_definition>');
    file_put_contents(OUTPUT_DIR . '/questions.xml', '<?xml version="1.0" encoding="UTF-8"?><question_categories></question_categories>');
    file_put_contents(OUTPUT_DIR . '/groups.xml', '<?xml version="1.0" encoding="UTF-8"?><groups><groupings></groupings></groups>');
    file_put_contents(OUTPUT_DIR . '/completion.xml', '<?xml version="1.0" encoding="UTF-8"?><course_completion></course_completion>');

    // gradebook.xml
    $gradebookXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<gradebook>
  <attributes></attributes>
  <grade_categories>
    <grade_category id="1">
      <parent>\$@NULL@\$</parent>
      <depth>1</depth>
      <path>/1/</path>
      <fullname>?</fullname>
      <aggregation>13</aggregation>
      <keephigh>0</keephigh>
      <droplow>0</droplow>
      <aggregateonlygraded>1</aggregateonlygraded>
      <aggregateoutcomes>0</aggregateoutcomes>
      <timecreated>{$time}</timecreated>
      <timemodified>{$time}</timemodified>
      <hidden>0</hidden>
    </grade_category>
  </grade_categories>
  <grade_items></grade_items>
  <grade_letters></grade_letters>
  <grade_settings></grade_settings>
</gradebook>
XML;
    file_put_contents(OUTPUT_DIR . '/gradebook.xml', $gradebookXml);

    echo "   ✓ XMLs globales generados\n";
}

function processFiles() {
    global $allActivities, $allFiles, $nextFileId;
    $count = 0;
    $videoPlaceholders = 0;

    foreach ($allActivities as &$activity) {
        // Saltar video_placeholder - no hay archivo real que procesar
        if (isset($activity['original_type']) && $activity['original_type'] === 'video_placeholder') {
            $videoPlaceholders++;
            continue;
        }

        if (!empty($activity['filepath']) && file_exists($activity['filepath'])) {
            $contentHash = sha1_file($activity['filepath']);
            $fileSize = filesize($activity['filepath']);
            $mimeType = getMimeType($activity['filename']);

            // Crear directorio por hash
            $hashDir = substr($contentHash, 0, 2);
            $filesDir = OUTPUT_DIR . '/files/' . $hashDir;
            if (!is_dir($filesDir)) mkdir($filesDir, 0755, true);

            // Copiar archivo
            $destPath = $filesDir . '/' . $contentHash;
            if (!file_exists($destPath)) {
                copy($activity['filepath'], $destPath);
            }

            $fileId = $nextFileId++;

            // Filearea según tipo
            $filearea = ($activity['type'] === 'label') ? 'intro' : 'content';
            $itemid = ($activity['type'] === 'label') ? $activity['instanceid'] : 0;

            $allFiles[] = [
                'id' => $fileId,
                'contenthash' => $contentHash,
                'contextid' => $activity['contextid'],
                'component' => 'mod_' . $activity['type'],
                'filearea' => $filearea,
                'itemid' => $itemid,
                'filepath' => '/',
                'filename' => $activity['filename'],
                'filesize' => $fileSize,
                'mimetype' => $mimeType
            ];

            // Actualizar inforef
            $actDir = OUTPUT_DIR . "/activities/{$activity['type']}_{$activity['moduleid']}";
            $inforef = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<inforef>\n  <fileref>\n    <file><id>{$fileId}</id></file>\n  </fileref>\n</inforef>";
            file_put_contents($actDir . '/inforef.xml', $inforef);

            $count++;
        }
    }

    echo "   ✓ Archivos procesados: {$count}\n";
    if ($videoPlaceholders > 0) {
        echo "   ✓ Video placeholders (sin archivo): {$videoPlaceholders}\n";
    }

    // Generar files.xml
    $time = time();
    $filesXml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<files>\n";
    foreach ($allFiles as $file) {
        $fnEsc = htmlspecialchars($file['filename']);
        $filesXml .= "  <file id=\"{$file['id']}\">\n";
        $filesXml .= "    <contenthash>{$file['contenthash']}</contenthash>\n";
        $filesXml .= "    <contextid>{$file['contextid']}</contextid>\n";
        $filesXml .= "    <component>{$file['component']}</component>\n";
        $filesXml .= "    <filearea>{$file['filearea']}</filearea>\n";
        $filesXml .= "    <itemid>{$file['itemid']}</itemid>\n";
        $filesXml .= "    <filepath>{$file['filepath']}</filepath>\n";
        $filesXml .= "    <filename>{$fnEsc}</filename>\n";
        $filesXml .= "    <userid>\$@NULL@\$</userid>\n";
        $filesXml .= "    <filesize>{$file['filesize']}</filesize>\n";
        $filesXml .= "    <mimetype>{$file['mimetype']}</mimetype>\n";
        $filesXml .= "    <status>0</status>\n";
        $filesXml .= "    <timecreated>{$time}</timecreated>\n";
        $filesXml .= "    <timemodified>{$time}</timemodified>\n";
        $filesXml .= "    <source>{$fnEsc}</source>\n";
        $filesXml .= "    <author>Jaqui Esquitino</author>\n";
        $filesXml .= "    <license>allrightsreserved</license>\n";
        $filesXml .= "    <sortorder>0</sortorder>\n";
        $filesXml .= "    <repositorytype>\$@NULL@\$</repositorytype>\n";
        $filesXml .= "    <repositoryid>\$@NULL@\$</repositoryid>\n";
        $filesXml .= "    <reference>\$@NULL@\$</reference>\n";
        $filesXml .= "  </file>\n";
    }
    $filesXml .= "</files>\n";
    file_put_contents(OUTPUT_DIR . '/files.xml', $filesXml);
}

function createMBZ() {
    $mbzPath = OUTPUT_DIR . '/' . MBZ_FILENAME;
    if (file_exists($mbzPath)) unlink($mbzPath);

    $zip = new ZipArchive();
    if ($zip->open($mbzPath, ZipArchive::CREATE) !== TRUE) {
        die("Error: No se pudo crear el archivo ZIP\n");
    }

    addDirToZip($zip, OUTPUT_DIR, '');

    $numFiles = $zip->numFiles;
    $zip->close();

    $size = formatBytes(filesize($mbzPath));
    echo "   ✓ MBZ creado: {$numFiles} archivos, {$size}\n";
}

function addDirToZip($zip, $dir, $zipPath) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $dir . '/' . $file;
        $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;
        if (pathinfo($file, PATHINFO_EXTENSION) === 'mbz') continue;
        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipFilePath);
            addDirToZip($zip, $filePath, $zipFilePath);
        } else {
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}

function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $types = ['pdf'=>'application/pdf', 'docx'=>'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'png'=>'image/png', 'jpg'=>'image/jpeg'];
    return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
}

function formatBytes($bytes, $p = 2) {
    $u = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    return round($bytes / pow(1024, $pow), $p) . ' ' . $u[$pow];
}

main();
