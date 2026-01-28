<?php
/**
 * Generador de MBZ v2.0 - Curso "Huellas Invisibles"
 *
 * Implementa diseño instruccional basado en el modelo de Gagné
 * con actividades interactivas reales de Moodle.
 *
 * Sitio: https://campus.rednadi.com
 * Directora: Jaqui Esquitino
 *
 * ESTRUCTURA POR CAPÍTULO (Modelo de Gagné):
 * 1. Introducción y Objetivos (mod_page)
 * 2. Contenido Principal (mod_resource - PDF libro)
 * 3. Material Complementario (mod_resource - PDF documento)
 * 4. Glosario del Capítulo (mod_glossary)
 * 5. Bibliografía (mod_page)
 * 6. Autoevaluación (mod_resource - test)
 *
 * @version 2.0
 */

// ============================================================================
// CONFIGURACIÓN GLOBAL
// ============================================================================

define('COURSE_FULLNAME', 'Huellas Invisibles: Neurociencia del Desarrollo Infantil');
define('COURSE_SHORTNAME', 'huellas-invisibles');
define('MOODLE_VERSION', '2024042200');
define('MOODLE_RELEASE', '4.4 (Build: 20240422)');
define('BACKUP_VERSION', '2024042200');
define('BACKUP_RELEASE', '4.4');
define('ORIGINAL_WWWROOT', 'https://campus.rednadi.com');

define('CONTENT_DIR', __DIR__ . '/../mbz_generator/curso_content/curso/Huellas invisibles');
define('OUTPUT_DIR', __DIR__ . '/backup_output_v2');
define('MBZ_FILENAME', 'backup-huellas-invisibles-v2-' . date('Ymd') . '.mbz');

// Contadores globales
$GLOBALS['nextModuleId'] = 1;
$GLOBALS['nextContextId'] = 100;
$GLOBALS['nextFileId'] = 1;
$GLOBALS['nextInstanceId'] = 1;
$GLOBALS['nextGlossaryEntryId'] = 1;
$GLOBALS['nextFeedbackItemId'] = 1;
$GLOBALS['allFiles'] = [];
$GLOBALS['allActivities'] = [];
$GLOBALS['allSections'] = [];

// ============================================================================
// DEFINICIÓN DE CONTENIDO DEL CURSO
// ============================================================================

$COURSE_SUMMARY = <<<HTML
<div style="text-align: center; padding: 20px;">
<h2>🧠 Huellas Invisibles</h2>
<h3>Neurociencia del Desarrollo Infantil</h3>
<p><strong>Directora:</strong> Jaqui Esquitino</p>
<p><strong>Institución:</strong> Red NADI</p>
</div>
<p>Bienvenidos a <strong>Huellas Invisibles</strong>, un programa integral de formación en neurociencia del desarrollo infantil que explora los fundamentos del desarrollo neurológico en la primera infancia.</p>
<h4>📚 Contenido del Curso</h4>
<ul>
<li>10 capítulos especializados</li>
<li>Material de lectura en cada capítulo</li>
<li>Documentos de estudio complementarios</li>
<li>Glosarios de términos clave</li>
<li>Bibliografía de referencia</li>
<li>Autoevaluaciones por capítulo</li>
</ul>
<h4>🎯 Objetivos de Aprendizaje</h4>
<ul>
<li>Comprender los fundamentos del desarrollo neurológico infantil</li>
<li>Analizar los factores que influyen en el desarrollo cerebral</li>
<li>Aplicar conocimientos de neurociencia en contextos educativos</li>
<li>Evaluar el impacto del entorno en el desarrollo infantil</li>
</ul>
HTML;

// Definición de capítulos con objetivos, glosarios y bibliografías
$CHAPTERS = [
    1 => [
        'name' => 'Desarrollo Neurológico Infantil',
        'dir' => 'Capitulo 1 Desarrollo Neurológico Infantil',
        'objectives' => [
            'Comprender las etapas del desarrollo neurológico en la primera infancia',
            'Identificar los hitos del desarrollo cerebral desde el nacimiento',
            'Analizar la importancia de los primeros años de vida en la formación del cerebro',
            'Reconocer los factores que favorecen un desarrollo neurológico saludable'
        ],
        'glossary_terms' => [
            ['term' => 'Neurona', 'definition' => 'Célula especializada del sistema nervioso que transmite información a través de señales eléctricas y químicas. Es la unidad funcional básica del cerebro.'],
            ['term' => 'Sinapsis', 'definition' => 'Conexión funcional entre dos neuronas que permite la transmisión de impulsos nerviosos mediante neurotransmisores.'],
            ['term' => 'Mielinización', 'definition' => 'Proceso de formación de la vaina de mielina alrededor de los axones neuronales, que acelera la transmisión de impulsos nerviosos.'],
            ['term' => 'Neuroplasticidad', 'definition' => 'Capacidad del cerebro para reorganizarse formando nuevas conexiones neuronales a lo largo de la vida.'],
            ['term' => 'Período crítico', 'definition' => 'Ventana temporal durante el desarrollo en la cual el cerebro es especialmente receptivo a ciertos estímulos ambientales.']
        ],
        'files' => [
            'libro' => '2-Esculpiendo el Cerebro Primera Infancia (Cap 1).pdf',
            'documento' => '3-DOCUMENTO DE ESTUDIO COMPLEMENTARIO (Cap 1).pdf',
            'glosario_pdf' => '4- Glosario de Términos Clave (Cap 1).pdf',
            'bibliografia_pdf' => '5- Bibliografia de referencia y recomendada (Cap 1).pdf',
            'test' => '6-Autoevaluacion- Tests Capitulo 1.pdf',
            'complementario' => '7-Material complementario Video como funciona el cerebro.docx'
        ]
    ],
    2 => [
        'name' => 'Funciones Emocionales',
        'dir' => 'Capitulo 2- Funciones emocionales_',
        'objectives' => [
            'Comprender el desarrollo de las funciones emocionales en el cerebro infantil',
            'Identificar las estructuras cerebrales involucradas en las emociones',
            'Analizar la relación entre emociones y desarrollo cognitivo',
            'Reconocer la importancia de la regulación emocional en la infancia'
        ],
        'glossary_terms' => [
            ['term' => 'Sistema límbico', 'definition' => 'Conjunto de estructuras cerebrales responsables de las emociones, la memoria y la motivación. Incluye la amígdala, el hipocampo y el hipotálamo.'],
            ['term' => 'Amígdala', 'definition' => 'Estructura cerebral en forma de almendra que procesa las emociones, especialmente el miedo y la ansiedad.'],
            ['term' => 'Regulación emocional', 'definition' => 'Capacidad para modular y gestionar las respuestas emocionales de manera adaptativa.'],
            ['term' => 'Corteza prefrontal', 'definition' => 'Región del cerebro responsable de las funciones ejecutivas, incluyendo el control de impulsos y la toma de decisiones.']
        ],
        'files' => [
            'libro' => '2- CEREBRO EN CONSTRUCCION.pdf',
            'documento' => '3-DOCUMENTO COMPLEMENTARIO CAP 2.pdf',
            'glosario_pdf' => '5-GLOSARIO CAP.2.pdf',
            'bibliografia_pdf' => '4-BIBLIOGRAFIA CAP 2.pdf',
            'test' => '6-TEST DE AUTOEVALUACION.docx'
        ]
    ],
    3 => [
        'name' => 'Plasticidad Cerebral',
        'dir' => 'Capitulo 3 - Plasticidad Cerebral',
        'objectives' => [
            'Comprender el concepto de plasticidad cerebral y sus mecanismos',
            'Identificar los tipos de plasticidad neuronal',
            'Analizar cómo el ambiente moldea el desarrollo cerebral',
            'Aplicar principios de plasticidad en intervención temprana'
        ],
        'glossary_terms' => [
            ['term' => 'Plasticidad sináptica', 'definition' => 'Capacidad de las sinapsis para fortalecerse o debilitarse en respuesta a la actividad neuronal.'],
            ['term' => 'Potenciación a largo plazo (LTP)', 'definition' => 'Aumento persistente de la fuerza sináptica tras estimulación repetida, base del aprendizaje y la memoria.'],
            ['term' => 'Poda sináptica', 'definition' => 'Proceso de eliminación de conexiones sinápticas no utilizadas para optimizar la eficiencia cerebral.'],
            ['term' => 'Neurogénesis', 'definition' => 'Proceso de formación de nuevas neuronas en el cerebro, especialmente activo durante el desarrollo temprano.']
        ],
        'files' => [
            'libro' => '2-El Cerebro Moldeable Principios y Práctica.pdf',
            'documento' => '3-Documento de estudio cap 3.pdf',
            'glosario_pdf' => '4-GLOSARIO CAP 3.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAP 3.pdf',
            'test' => '6- test Capitulo 3 Plasticidad cerebral.docx'
        ]
    ],
    4 => [
        'name' => 'Sinaptogénesis',
        'dir' => 'Capitulo 4- Sinaptogénesis',
        'objectives' => [
            'Comprender el proceso de formación de sinapsis',
            'Identificar las etapas de la sinaptogénesis durante el desarrollo',
            'Analizar los factores que influyen en la formación sináptica',
            'Reconocer la importancia de la estimulación temprana'
        ],
        'glossary_terms' => [
            ['term' => 'Sinaptogénesis', 'definition' => 'Proceso de formación de nuevas sinapsis entre neuronas durante el desarrollo del sistema nervioso.'],
            ['term' => 'Densidad sináptica', 'definition' => 'Número de sinapsis por unidad de volumen de tejido cerebral.'],
            ['term' => 'Axón', 'definition' => 'Prolongación de la neurona que conduce el impulso nervioso hacia otras células.'],
            ['term' => 'Dendrita', 'definition' => 'Prolongaciones ramificadas de la neurona que reciben señales de otras neuronas.']
        ],
        'files' => [
            'libro' => '2-El Cerebro Plástico De la Construcción a la Maestría.pdf',
            'documento' => '3-Documento de estudio cap 4.pdf',
            'glosario_pdf' => '4-GLOSARIO CAP 4.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAP 4.pdf',
            'test' => '5-Test Capitulo 4.docx'
        ]
    ],
    5 => [
        'name' => 'Período Sensoriomotor',
        'dir' => 'Capitulo 5 -  Período sensoriomotor',
        'objectives' => [
            'Comprender las características del período sensoriomotor de Piaget',
            'Identificar los subestadios del desarrollo sensoriomotor',
            'Analizar la base neurológica del desarrollo sensoriomotor',
            'Reconocer la importancia de la exploración sensorial en el aprendizaje'
        ],
        'glossary_terms' => [
            ['term' => 'Período sensoriomotor', 'definition' => 'Primera etapa del desarrollo cognitivo según Piaget (0-2 años), donde el niño aprende a través de los sentidos y la acción motora.'],
            ['term' => 'Permanencia del objeto', 'definition' => 'Comprensión de que los objetos siguen existiendo aunque no se puedan ver o percibir directamente.'],
            ['term' => 'Esquema', 'definition' => 'Estructura cognitiva que organiza las experiencias y guía el comportamiento.'],
            ['term' => 'Coordinación visuomotora', 'definition' => 'Capacidad de coordinar la visión con los movimientos del cuerpo, especialmente de las manos.']
        ],
        'files' => [
            'libro' => '2-Construyendo_la_Realidad_La_Mente_Sensoriomotora2.pdf',
            'documento' => '3-Documento Complementario de Estudio Cap 5.pdf',
            'glosario_pdf' => '4-GLOSARIO CAPITULO 5.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAPITULO 5.pdf',
            'test' => '6- test Capitulo 5.docx'
        ]
    ],
    6 => [
        'name' => 'Estrés y Apego',
        'dir' => 'Capitulo 6 - Stress y Apego',
        'objectives' => [
            'Comprender la relación entre estrés, apego y desarrollo cerebral',
            'Identificar los tipos de apego y su impacto neurológico',
            'Analizar los efectos del estrés tóxico en el desarrollo infantil',
            'Reconocer estrategias para promover un apego seguro'
        ],
        'glossary_terms' => [
            ['term' => 'Apego seguro', 'definition' => 'Vínculo emocional saludable entre el niño y su cuidador que proporciona una base segura para la exploración.'],
            ['term' => 'Cortisol', 'definition' => 'Hormona del estrés producida por las glándulas suprarrenales que afecta múltiples funciones corporales.'],
            ['term' => 'Estrés tóxico', 'definition' => 'Activación prolongada del sistema de respuesta al estrés sin el apoyo de un adulto protector.'],
            ['term' => 'Eje HPA', 'definition' => 'Sistema hipotalámico-pituitario-adrenal que regula la respuesta al estrés en el organismo.']
        ],
        'files' => [
            'libro' => '2-Arquitectos_del_Cerebro_Infantil_Apego_y_Estrés.pdf',
            'documento' => '3-Documento de estudio capitulo 6.pdf',
            'glosario_pdf' => '4-GLOSARIO Capitulo 6.pdf',
            'bibliografia_pdf' => '5- BIBLIOGRAFIA CAPITULO 6.pdf',
            'test' => '6- Test Cap 6.docx'
        ]
    ],
    7 => [
        'name' => 'Teoría Epigenética',
        'dir' => 'Capitulo 7- Teoría Epigenética',
        'objectives' => [
            'Comprender los principios básicos de la epigenética',
            'Identificar cómo el ambiente modifica la expresión genética',
            'Analizar la influencia epigenética en el desarrollo cerebral',
            'Reconocer la importancia del ambiente temprano en la programación genética'
        ],
        'glossary_terms' => [
            ['term' => 'Epigenética', 'definition' => 'Estudio de los cambios heredables en la expresión genética que no implican alteraciones en la secuencia del ADN.'],
            ['term' => 'Metilación del ADN', 'definition' => 'Modificación química del ADN que puede silenciar genes sin cambiar su secuencia.'],
            ['term' => 'Expresión génica', 'definition' => 'Proceso por el cual la información de un gen se utiliza para sintetizar proteínas funcionales.'],
            ['term' => 'Marcadores epigenéticos', 'definition' => 'Modificaciones químicas del ADN o las histonas que regulan la actividad de los genes.']
        ],
        'files' => [
            'libro' => '2-Huellas Invisibles Entorno y Genes.pdf',
            'documento' => '3-Documento de estudio Capitulo 7.pdf',
            'glosario_pdf' => '4-GLOSARIO Capitulo 7.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAPITULO 7.pdf',
            'test' => '6-Test Capitulo 7.docx'
        ]
    ],
    8 => [
        'name' => 'Reflejo de Apnea',
        'dir' => 'Capitulo 8 - Reflejo de Apnea',
        'objectives' => [
            'Comprender el reflejo de apnea y su función evolutiva',
            'Identificar los reflejos primitivos y su desarrollo',
            'Analizar la importancia de los reflejos en la evaluación del desarrollo',
            'Reconocer la integración de reflejos primitivos'
        ],
        'glossary_terms' => [
            ['term' => 'Reflejo de apnea', 'definition' => 'Respuesta involuntaria que detiene temporalmente la respiración cuando la cara entra en contacto con el agua.'],
            ['term' => 'Reflejos primitivos', 'definition' => 'Respuestas motoras automáticas presentes en los recién nacidos que normalmente desaparecen con la maduración.'],
            ['term' => 'Reflejo de Moro', 'definition' => 'Respuesta de sobresalto del bebé ante estímulos súbitos, caracterizada por extensión y flexión de los brazos.'],
            ['term' => 'Integración de reflejos', 'definition' => 'Proceso por el cual los reflejos primitivos son inhibidos y reemplazados por movimientos voluntarios controlados.']
        ],
        'files' => [
            'libro' => '2-Ancestral Water Echo.pdf',
            'documento' => '3-Documento de estudio Capitulo 8.pdf',
            'glosario_pdf' => '4-GLOSARIO Capitulo 8.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAPITULO 8.pdf',
            'test' => '6-test Capitulo 8 8.docx'
        ]
    ],
    9 => [
        'name' => 'Socialización en la Primera Infancia',
        'dir' => 'Capitulo 9 - Socialización en la 1er infancia',
        'objectives' => [
            'Comprender los procesos de socialización temprana',
            'Identificar las bases neurológicas de la cognición social',
            'Analizar el desarrollo de las habilidades sociales en la infancia',
            'Reconocer la importancia del juego en la socialización'
        ],
        'glossary_terms' => [
            ['term' => 'Cognición social', 'definition' => 'Procesos cognitivos implicados en la comprensión e interacción con otras personas.'],
            ['term' => 'Neuronas espejo', 'definition' => 'Neuronas que se activan tanto al realizar una acción como al observar a otro realizarla.'],
            ['term' => 'Teoría de la mente', 'definition' => 'Capacidad de atribuir estados mentales (creencias, deseos, intenciones) a uno mismo y a los demás.'],
            ['term' => 'Atención conjunta', 'definition' => 'Capacidad de compartir el foco de atención con otra persona hacia un objeto o evento.']
        ],
        'files' => [
            'libro' => '2-Arquitectos de Mentes Construyendo el Cerebro.pdf',
            'documento' => '3-Documento de estudio Capitulo 9.pdf',
            'glosario_pdf' => '4-GLOSARIO Capitulo 9.pdf',
            'bibliografia_pdf' => '5- BIBLIOGRAFIA capitulo 9.pdf',
            'test' => '6-TEST CAPITULO 9.docx'
        ]
    ],
    10 => [
        'name' => 'La Atención',
        'dir' => 'Capitulo 10- La Atención',
        'objectives' => [
            'Comprender los mecanismos neurológicos de la atención',
            'Identificar los tipos de atención y su desarrollo',
            'Analizar los factores que influyen en la capacidad atencional',
            'Reconocer estrategias para favorecer el desarrollo de la atención'
        ],
        'glossary_terms' => [
            ['term' => 'Atención selectiva', 'definition' => 'Capacidad de focalizarse en un estímulo específico mientras se ignoran otros.'],
            ['term' => 'Atención sostenida', 'definition' => 'Capacidad de mantener el foco atencional durante un período prolongado de tiempo.'],
            ['term' => 'Funciones ejecutivas', 'definition' => 'Conjunto de procesos cognitivos que permiten planificar, organizar y regular el comportamiento.'],
            ['term' => 'Control inhibitorio', 'definition' => 'Capacidad de suprimir respuestas automáticas o impulsivas para lograr un objetivo.']
        ],
        'files' => [
            'libro' => '2-El Arquitecto Invisible Un Viaje al Corazón de la Atención.pdf',
            'documento' => '3-Documento de estudio Capitulo 10.pdf',
            'glosario_pdf' => '4-GLOSARIO Capitulo 10.pdf',
            'bibliografia_pdf' => '5-BIBLIOGRAFIA CAPITULO 10.pdf',
            'test' => '6-Test capitulo 10.docx'
        ]
    ]
];

// Preguntas de la encuesta de cierre
$FEEDBACK_ITEMS = [
    ['type' => 'multichoicerated', 'name' => '¿Cómo calificaría la calidad general del curso?', 'options' => '1####Muy deficiente|2####Deficiente|3####Regular|4####Bueno|5####Excelente'],
    ['type' => 'multichoicerated', 'name' => '¿Los contenidos cumplieron con sus expectativas?', 'options' => '1####No cumplieron|2####Parcialmente|3####Moderadamente|4####En gran medida|5####Totalmente'],
    ['type' => 'multichoicerated', 'name' => '¿Cómo evalúa la claridad de los materiales de estudio?', 'options' => '1####Muy confusos|2####Confusos|3####Aceptables|4####Claros|5####Muy claros'],
    ['type' => 'multichoicerated', 'name' => '¿Recomendaría este curso a otros profesionales?', 'options' => '1####Definitivamente no|2####Probablemente no|3####No estoy seguro|4####Probablemente sí|5####Definitivamente sí'],
    ['type' => 'multichoice', 'name' => '¿Cuál capítulo le resultó más interesante?', 'options' => 'Desarrollo Neurológico|Funciones Emocionales|Plasticidad Cerebral|Sinaptogénesis|Período Sensoriomotor|Estrés y Apego|Epigenética|Reflejo de Apnea|Socialización|La Atención'],
    ['type' => 'textarea', 'name' => '¿Qué aspectos del curso mejoraría?', 'options' => '60|5'],
    ['type' => 'textarea', 'name' => '¿Qué temas adicionales le gustaría que se incluyeran?', 'options' => '60|5'],
    ['type' => 'textfield', 'name' => 'Si desea recibir información sobre futuros cursos, indique su correo electrónico:', 'options' => '50|100']
];

// ============================================================================
// FUNCIONES PRINCIPALES
// ============================================================================

function main() {
    global $CHAPTERS, $COURSE_SUMMARY;

    echo "╔══════════════════════════════════════════════════════════════╗\n";
    echo "║  Generador MBZ v2.0 - Huellas Invisibles                    ║\n";
    echo "║  Diseño Instruccional basado en modelo de Gagné             ║\n";
    echo "╚══════════════════════════════════════════════════════════════╝\n\n";

    // Fase 1: Crear estructura de directorios
    echo "📁 FASE 1: Creando estructura de directorios...\n";
    createDirectoryStructure();

    // Fase 2: Procesar sección de presentación
    echo "\n📋 FASE 2: Procesando sección de presentación...\n";
    processIntroSection();

    // Fase 3: Procesar capítulos con diseño instruccional
    echo "\n📚 FASE 3: Procesando capítulos con diseño instruccional...\n";
    foreach ($CHAPTERS as $num => $chapter) {
        processChapter($num, $chapter);
    }

    // Fase 4: Procesar sección de cierre con encuesta
    echo "\n📝 FASE 4: Procesando sección de cierre...\n";
    processClosingSection();

    // Fase 5: Generar archivos XML globales
    echo "\n📄 FASE 5: Generando archivos XML globales...\n";
    generateGlobalXMLFiles();

    // Fase 6: Procesar archivos binarios
    echo "\n📦 FASE 6: Procesando archivos binarios...\n";
    processAllFiles();

    // Fase 7: Crear archivo MBZ
    echo "\n🗜️  FASE 7: Creando archivo MBZ...\n";
    createMBZ();

    echo "\n✅ ¡Proceso completado exitosamente!\n";
    echo "   Archivo: " . OUTPUT_DIR . '/' . MBZ_FILENAME . "\n";
}

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
            echo "   ✓ Creado: $dir\n";
        }
    }
}

// ============================================================================
// PROCESAMIENTO DE SECCIONES
// ============================================================================

function processIntroSection() {
    global $COURSE_SUMMARY;

    $sectionId = 1;
    $sectionNum = 0;

    // Crear directorio de sección
    createSectionDir($sectionId);

    $activities = [];

    // 1. Label con imagen del curso
    $activities[] = createLabelActivity(
        $sectionId, $sectionNum,
        'Bienvenida al Curso',
        '<p style="text-align: center;"><img src="@@PLUGINFILE@@/huellas%20invisibles.png" alt="Huellas Invisibles" width="600" /></p>',
        CONTENT_DIR . '/huellas invisibles.png'
    );

    // 2. Page con presentación del curso
    $activities[] = createPageActivity(
        $sectionId, $sectionNum,
        '📖 Presentación del Curso',
        $COURSE_SUMMARY,
        'Conoce los objetivos, contenidos y metodología del curso Huellas Invisibles.'
    );

    // 3. Recurso: Programa del curso
    $programFile = CONTENT_DIR . '/Titulo presentacion y programa.docx';
    if (file_exists($programFile)) {
        $activities[] = createResourceActivity(
            $sectionId, $sectionNum,
            '📋 Programa y Cronograma del Curso',
            $programFile
        );
    }

    // Guardar sección
    $GLOBALS['allSections'][] = [
        'id' => $sectionId,
        'number' => $sectionNum,
        'name' => 'Presentación del Curso',
        'summary' => '<p>Bienvenidos al curso <strong>Huellas Invisibles</strong>. En esta sección encontrarás la presentación general del curso, sus objetivos y el programa completo.</p>',
        'activities' => $activities
    ];

    echo "   ✓ Sección 0: Presentación del Curso (" . count($activities) . " actividades)\n";
}

function processChapter($num, $chapter) {
    $sectionId = $num + 1;
    $sectionNum = $num;

    // Crear directorio de sección
    createSectionDir($sectionId);

    $activities = [];
    $chapterDir = CONTENT_DIR . '/' . $chapter['dir'];

    // 1. PAGE: Introducción y Objetivos (Evento de Gagné 1 y 2)
    $objectives = implode('</li><li>', $chapter['objectives']);
    $introContent = <<<HTML
<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
<h2>🎯 Capítulo {$num}: {$chapter['name']}</h2>
</div>

<h3>📌 Objetivos de Aprendizaje</h3>
<p>Al finalizar este capítulo, serás capaz de:</p>
<ul>
<li>{$objectives}</li>
</ul>

<h3>📚 Contenido del Capítulo</h3>
<p>Este capítulo incluye:</p>
<ul>
<li><strong>Libro Principal:</strong> Material de lectura fundamental</li>
<li><strong>Documento de Estudio:</strong> Guía complementaria</li>
<li><strong>Glosario:</strong> Términos clave del capítulo</li>
<li><strong>Bibliografía:</strong> Referencias para profundizar</li>
<li><strong>Autoevaluación:</strong> Verifica tu aprendizaje</li>
</ul>

<p style="background-color: #e8f5e9; padding: 15px; border-left: 4px solid #4caf50; border-radius: 5px;">
<strong>💡 Recomendación:</strong> Lee primero los objetivos, luego el material principal, y finalmente realiza la autoevaluación para verificar tu comprensión.
</p>
HTML;

    $activities[] = createPageActivity(
        $sectionId, $sectionNum,
        "📌 Introducción y Objetivos",
        $introContent,
        "Objetivos de aprendizaje del capítulo {$num}"
    );

    // 2. RESOURCE: Libro principal (Evento de Gagné 4)
    if (isset($chapter['files']['libro'])) {
        $libroFile = $chapterDir . '/' . $chapter['files']['libro'];
        if (file_exists($libroFile)) {
            $activities[] = createResourceActivity(
                $sectionId, $sectionNum,
                "📚 Libro: " . pathinfo($chapter['files']['libro'], PATHINFO_FILENAME),
                $libroFile
            );
        }
    }

    // 3. RESOURCE: Documento de estudio (Evento de Gagné 5)
    if (isset($chapter['files']['documento'])) {
        $docFile = $chapterDir . '/' . $chapter['files']['documento'];
        if (file_exists($docFile)) {
            $activities[] = createResourceActivity(
                $sectionId, $sectionNum,
                "📄 Documento de Estudio Complementario",
                $docFile
            );
        }
    }

    // 4. GLOSSARY: Glosario de términos (Evento de Gagné 9)
    $activities[] = createGlossaryActivity(
        $sectionId, $sectionNum,
        "📖 Glosario del Capítulo {$num}",
        $chapter['glossary_terms'],
        "Términos clave del capítulo {$chapter['name']}"
    );

    // 5. PAGE: Bibliografía
    $biblioContent = createBibliographyContent($num, $chapter['name']);
    $activities[] = createPageActivity(
        $sectionId, $sectionNum,
        "📚 Bibliografía y Referencias",
        $biblioContent,
        "Referencias bibliográficas del capítulo {$num}"
    );

    // 6. RESOURCE: PDF de bibliografía original
    if (isset($chapter['files']['bibliografia_pdf'])) {
        $biblioFile = $chapterDir . '/' . $chapter['files']['bibliografia_pdf'];
        if (file_exists($biblioFile)) {
            $activities[] = createResourceActivity(
                $sectionId, $sectionNum,
                "📋 Bibliografía Completa (PDF)",
                $biblioFile
            );
        }
    }

    // 7. LABEL: Separador de autoevaluación
    $activities[] = createLabelActivity(
        $sectionId, $sectionNum,
        'Separador Autoevaluación',
        '<hr><h3 style="text-align: center; color: #1565c0;">✅ Autoevaluación</h3><p style="text-align: center;">Verifica tu comprensión de los conceptos del capítulo</p><hr>'
    );

    // 8. RESOURCE: Test de autoevaluación
    if (isset($chapter['files']['test'])) {
        $testFile = $chapterDir . '/' . $chapter['files']['test'];
        if (file_exists($testFile)) {
            $activities[] = createResourceActivity(
                $sectionId, $sectionNum,
                "✅ Test de Autoevaluación - Capítulo {$num}",
                $testFile
            );
        }
    }

    // 9. Material complementario si existe
    if (isset($chapter['files']['complementario'])) {
        $compFile = $chapterDir . '/' . $chapter['files']['complementario'];
        if (file_exists($compFile)) {
            $activities[] = createResourceActivity(
                $sectionId, $sectionNum,
                "🎬 Material Complementario",
                $compFile
            );
        }
    }

    // Guardar sección
    $GLOBALS['allSections'][] = [
        'id' => $sectionId,
        'number' => $sectionNum,
        'name' => "Capítulo {$num}: {$chapter['name']}",
        'summary' => "<p>{$chapter['objectives'][0]}</p>",
        'activities' => $activities
    ];

    echo "   ✓ Sección {$sectionNum}: Capítulo {$num} - {$chapter['name']} (" . count($activities) . " actividades)\n";
}

function processClosingSection() {
    global $FEEDBACK_ITEMS;

    $sectionId = 12;
    $sectionNum = 11;

    createSectionDir($sectionId);

    $activities = [];

    // 1. Page: Mensaje de cierre
    $closingContent = <<<HTML
<div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 15px;">
<h2>🎉 ¡Felicitaciones!</h2>
<h3>Has completado el curso Huellas Invisibles</h3>
</div>

<p style="margin-top: 20px; font-size: 1.1em;">
Querido/a participante, has llegado al final de este viaje por la neurociencia del desarrollo infantil.
A lo largo de estos 10 capítulos, has explorado los fundamentos del desarrollo cerebral, desde la formación
de sinapsis hasta los complejos procesos de socialización y atención.
</p>

<h3>📊 Lo que has aprendido:</h3>
<ul>
<li>Los principios del desarrollo neurológico infantil</li>
<li>La importancia de las funciones emocionales en el desarrollo</li>
<li>Cómo funciona la plasticidad cerebral</li>
<li>El proceso de sinaptogénesis y su importancia</li>
<li>Las características del período sensoriomotor</li>
<li>La relación entre estrés, apego y desarrollo</li>
<li>Los fundamentos de la epigenética</li>
<li>Los reflejos primitivos y su integración</li>
<li>Los procesos de socialización temprana</li>
<li>Los mecanismos de la atención</li>
</ul>

<h3>🙏 Tu opinión es importante</h3>
<p>Por favor, completa la encuesta de opinión para ayudarnos a mejorar este curso para futuros participantes.</p>

<p style="text-align: center; margin-top: 30px;">
<strong>Directora: Jaqui Esquitino</strong><br>
Red NADI - https://www.rednadi.com
</p>
HTML;

    $activities[] = createPageActivity(
        $sectionId, $sectionNum,
        "🎓 Cierre del Curso",
        $closingContent,
        "Mensaje de cierre y felicitaciones por completar el curso"
    );

    // 2. Feedback: Encuesta de opinión
    $activities[] = createFeedbackActivity(
        $sectionId, $sectionNum,
        "📝 Encuesta de Opinión - Cierre del Curso",
        $FEEDBACK_ITEMS,
        "Tu opinión es muy importante para mejorar nuestros cursos. Por favor, tómate unos minutos para completar esta encuesta."
    );

    // 3. Resource: Encuesta original en DOCX
    $encuestaFile = CONTENT_DIR . '/Encuesta De Opinión – Cierre Curso Huellas Invisibles.docx';
    if (file_exists($encuestaFile)) {
        $activities[] = createResourceActivity(
            $sectionId, $sectionNum,
            "📋 Encuesta de Opinión (Documento)",
            $encuestaFile
        );
    }

    $GLOBALS['allSections'][] = [
        'id' => $sectionId,
        'number' => $sectionNum,
        'name' => "Cierre del Curso",
        'summary' => "<p>Felicitaciones por completar el curso. Por favor, completa la encuesta de opinión.</p>",
        'activities' => $activities
    ];

    echo "   ✓ Sección 11: Cierre del Curso (" . count($activities) . " actividades)\n";
}

// ============================================================================
// CREACIÓN DE ACTIVIDADES
// ============================================================================

function createResourceActivity($sectionId, $sectionNum, $name, $filePath) {
    $moduleId = $GLOBALS['nextModuleId']++;
    $instanceId = $GLOBALS['nextInstanceId']++;
    $contextId = $GLOBALS['nextContextId']++;

    $activity = [
        'type' => 'resource',
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => $sectionNum,
        'name' => $name,
        'filepath' => $filePath,
        'filename' => basename($filePath)
    ];

    createActivityDir('resource', $moduleId);
    generateResourceXML($activity);

    $GLOBALS['allActivities'][] = $activity;
    return $activity;
}

function createLabelActivity($sectionId, $sectionNum, $name, $content, $imageFile = null) {
    $moduleId = $GLOBALS['nextModuleId']++;
    $instanceId = $GLOBALS['nextInstanceId']++;
    $contextId = $GLOBALS['nextContextId']++;

    $activity = [
        'type' => 'label',
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => $sectionNum,
        'name' => $name,
        'content' => $content,
        'filepath' => $imageFile,
        'filename' => $imageFile ? basename($imageFile) : null
    ];

    createActivityDir('label', $moduleId);
    generateLabelXML($activity);

    $GLOBALS['allActivities'][] = $activity;
    return $activity;
}

function createPageActivity($sectionId, $sectionNum, $name, $content, $intro = '') {
    $moduleId = $GLOBALS['nextModuleId']++;
    $instanceId = $GLOBALS['nextInstanceId']++;
    $contextId = $GLOBALS['nextContextId']++;

    $activity = [
        'type' => 'page',
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => $sectionNum,
        'name' => $name,
        'content' => $content,
        'intro' => $intro
    ];

    createActivityDir('page', $moduleId);
    generatePageXML($activity);

    $GLOBALS['allActivities'][] = $activity;
    return $activity;
}

function createGlossaryActivity($sectionId, $sectionNum, $name, $terms, $intro = '') {
    $moduleId = $GLOBALS['nextModuleId']++;
    $instanceId = $GLOBALS['nextInstanceId']++;
    $contextId = $GLOBALS['nextContextId']++;

    $activity = [
        'type' => 'glossary',
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => $sectionNum,
        'name' => $name,
        'intro' => $intro,
        'terms' => $terms
    ];

    createActivityDir('glossary', $moduleId);
    generateGlossaryXML($activity);

    $GLOBALS['allActivities'][] = $activity;
    return $activity;
}

function createFeedbackActivity($sectionId, $sectionNum, $name, $items, $intro = '') {
    $moduleId = $GLOBALS['nextModuleId']++;
    $instanceId = $GLOBALS['nextInstanceId']++;
    $contextId = $GLOBALS['nextContextId']++;

    $activity = [
        'type' => 'feedback',
        'moduleid' => $moduleId,
        'instanceid' => $instanceId,
        'contextid' => $contextId,
        'sectionid' => $sectionId,
        'sectionnumber' => $sectionNum,
        'name' => $name,
        'intro' => $intro,
        'items' => $items
    ];

    createActivityDir('feedback', $moduleId);
    generateFeedbackXML($activity);

    $GLOBALS['allActivities'][] = $activity;
    return $activity;
}

// ============================================================================
// GENERACIÓN DE XMLs
// ============================================================================

function generateResourceXML($activity) {
    $dir = OUTPUT_DIR . '/activities/resource_' . $activity['moduleid'];
    $time = time();

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
    $nameEsc = htmlspecialchars($activity['name']);
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

    generateActivityCommonFiles($activity, $dir);
}

function generateLabelXML($activity) {
    $dir = OUTPUT_DIR . '/activities/label_' . $activity['moduleid'];
    $time = time();

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
    $nameEsc = htmlspecialchars($activity['name']);
    $contentEsc = htmlspecialchars($activity['content']);
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

    generateActivityCommonFiles($activity, $dir);
}

function generatePageXML($activity) {
    $dir = OUTPUT_DIR . '/activities/page_' . $activity['moduleid'];
    $time = time();

    // module.xml
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>page</modulename>
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

    // page.xml
    $nameEsc = htmlspecialchars($activity['name']);
    $introEsc = htmlspecialchars($activity['intro']);
    $contentEsc = htmlspecialchars($activity['content']);
    $pageXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="page" contextid="{$activity['contextid']}">
  <page id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro>{$introEsc}</intro>
    <introformat>1</introformat>
    <content>{$contentEsc}</content>
    <contentformat>1</contentformat>
    <legacyfiles>0</legacyfiles>
    <legacyfileslast>\$@NULL@\$</legacyfileslast>
    <display>5</display>
    <displayoptions>a:2:{s:10:"printintro";i:0;s:17:"printlastmodified";i:1;}</displayoptions>
    <revision>1</revision>
    <timemodified>{$time}</timemodified>
  </page>
</activity>
XML;
    file_put_contents($dir . '/page.xml', $pageXml);

    generateActivityCommonFiles($activity, $dir);
}

function generateGlossaryXML($activity) {
    $dir = OUTPUT_DIR . '/activities/glossary_' . $activity['moduleid'];
    $time = time();

    // module.xml
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>glossary</modulename>
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

    // Generar entries XML
    $entriesXml = '';
    foreach ($activity['terms'] as $term) {
        $entryId = $GLOBALS['nextGlossaryEntryId']++;
        $conceptEsc = htmlspecialchars($term['term']);
        $defEsc = htmlspecialchars($term['definition']);
        $entriesXml .= <<<XML
      <entry id="{$entryId}">
        <userid>2</userid>
        <concept>{$conceptEsc}</concept>
        <definition>{$defEsc}</definition>
        <definitionformat>1</definitionformat>
        <definitiontrust>1</definitiontrust>
        <attachment></attachment>
        <timecreated>{$time}</timecreated>
        <timemodified>{$time}</timemodified>
        <teacherentry>1</teacherentry>
        <sourceglossaryid>0</sourceglossaryid>
        <usedynalink>1</usedynalink>
        <casesensitive>0</casesensitive>
        <fullmatch>1</fullmatch>
        <approved>1</approved>
        <aliases></aliases>
        <ratings></ratings>
      </entry>

XML;
    }

    // glossary.xml
    $nameEsc = htmlspecialchars($activity['name']);
    $introEsc = htmlspecialchars($activity['intro']);
    $glossaryXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="glossary" contextid="{$activity['contextid']}">
  <glossary id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro>{$introEsc}</intro>
    <introformat>1</introformat>
    <allowduplicatedentries>0</allowduplicatedentries>
    <displayformat>dictionary</displayformat>
    <mainglossary>0</mainglossary>
    <showspecial>1</showspecial>
    <showalphabet>1</showalphabet>
    <showall>1</showall>
    <allowcomments>0</allowcomments>
    <allowprintview>1</allowprintview>
    <usedynalink>1</usedynalink>
    <defaultapproval>1</defaultapproval>
    <globalglossary>0</globalglossary>
    <entbypage>10</entbypage>
    <editalways>0</editalways>
    <rsstype>0</rsstype>
    <rssarticles>0</rssarticles>
    <assessed>0</assessed>
    <assesstimestart>0</assesstimestart>
    <assesstimefinish>0</assesstimefinish>
    <scale>0</scale>
    <timecreated>{$time}</timecreated>
    <timemodified>{$time}</timemodified>
    <completionentries>0</completionentries>
    <entries>
{$entriesXml}    </entries>
    <categories></categories>
    <entriestags></entriestags>
  </glossary>
</activity>
XML;
    file_put_contents($dir . '/glossary.xml', $glossaryXml);

    generateActivityCommonFiles($activity, $dir);
}

function generateFeedbackXML($activity) {
    $dir = OUTPUT_DIR . '/activities/feedback_' . $activity['moduleid'];
    $time = time();

    // module.xml
    $moduleXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<module id="{$activity['moduleid']}" version="2024042200">
  <modulename>feedback</modulename>
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
  <completion>2</completion>
  <completiongradeitemnumber>\$@NULL@\$</completiongradeitemnumber>
  <completionview>0</completionview>
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

    // Generar items XML
    $itemsXml = '';
    $position = 1;
    foreach ($activity['items'] as $item) {
        $itemId = $GLOBALS['nextFeedbackItemId']++;
        $nameEsc = htmlspecialchars($item['name']);

        $presentation = '';
        switch ($item['type']) {
            case 'multichoicerated':
                $presentation = 'r>>>>>' . $item['options'] . '<<<<<0';
                break;
            case 'multichoice':
                $presentation = 'r>>>>>' . $item['options'] . '<<<<<0';
                break;
            case 'textarea':
                $presentation = $item['options'];
                break;
            case 'textfield':
                $presentation = $item['options'];
                break;
        }

        $itemsXml .= <<<XML
      <item id="{$itemId}">
        <template>0</template>
        <name>{$nameEsc}</name>
        <label>Q{$position}</label>
        <presentation>{$presentation}</presentation>
        <typ>{$item['type']}</typ>
        <hasvalue>1</hasvalue>
        <position>{$position}</position>
        <required>0</required>
        <dependitem>0</dependitem>
        <dependvalue></dependvalue>
        <options></options>
      </item>

XML;
        $position++;
    }

    // feedback.xml
    $nameEsc = htmlspecialchars($activity['name']);
    $introEsc = htmlspecialchars($activity['intro']);
    $feedbackXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<activity id="{$activity['instanceid']}" moduleid="{$activity['moduleid']}" modulename="feedback" contextid="{$activity['contextid']}">
  <feedback id="{$activity['instanceid']}">
    <name>{$nameEsc}</name>
    <intro>{$introEsc}</intro>
    <introformat>1</introformat>
    <anonymous>1</anonymous>
    <email_notification>0</email_notification>
    <multiple_submit>0</multiple_submit>
    <autonumbering>1</autonumbering>
    <site_after_submit></site_after_submit>
    <page_after_submit>&lt;p&gt;¡Gracias por completar la encuesta! Tu opinión es muy valiosa para nosotros.&lt;/p&gt;</page_after_submit>
    <page_after_submitformat>1</page_after_submitformat>
    <publish_stats>0</publish_stats>
    <timeopen>0</timeopen>
    <timeclose>0</timeclose>
    <timemodified>{$time}</timemodified>
    <completionsubmit>1</completionsubmit>
    <items>
{$itemsXml}    </items>
    <completeds></completeds>
  </feedback>
</activity>
XML;
    file_put_contents($dir . '/feedback.xml', $feedbackXml);

    generateActivityCommonFiles($activity, $dir);
}

function generateActivityCommonFiles($activity, $dir) {
    $time = time();

    // grades.xml
    file_put_contents($dir . '/grades.xml', '<?xml version="1.0" encoding="UTF-8"?>
<activity_gradebook>
  <grade_items></grade_items>
  <grade_letters></grade_letters>
</activity_gradebook>
');

    // roles.xml
    file_put_contents($dir . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>
<roles>
  <role_overrides></role_overrides>
  <role_assignments></role_assignments>
</roles>
');

    // filters.xml
    file_put_contents($dir . '/filters.xml', '<?xml version="1.0" encoding="UTF-8"?>
<filters>
  <filter_actives></filter_actives>
  <filter_configs></filter_configs>
</filters>
');

    // comments.xml
    file_put_contents($dir . '/comments.xml', '<?xml version="1.0" encoding="UTF-8"?>
<comments></comments>
');

    // calendar.xml
    file_put_contents($dir . '/calendar.xml', '<?xml version="1.0" encoding="UTF-8"?>
<events></events>
');

    // competencies.xml
    file_put_contents($dir . '/competencies.xml', '<?xml version="1.0" encoding="UTF-8"?>
<course_module_competencies></course_module_competencies>
');

    // inforef.xml (se actualizará después)
    file_put_contents($dir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref></fileref>
</inforef>
');
}

// ============================================================================
// FUNCIONES AUXILIARES
// ============================================================================

function createSectionDir($sectionId) {
    $dir = OUTPUT_DIR . "/sections/section_{$sectionId}";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function createActivityDir($type, $moduleId) {
    $dir = OUTPUT_DIR . "/activities/{$type}_{$moduleId}";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function createBibliographyContent($chapterNum, $chapterName) {
    return <<<HTML
<h3>📚 Referencias Bibliográficas - Capítulo {$chapterNum}</h3>
<p><strong>{$chapterName}</strong></p>

<h4>📖 Lecturas Recomendadas</h4>
<p>Para profundizar en los temas de este capítulo, te recomendamos consultar las siguientes fuentes:</p>

<ul>
<li>El documento PDF de bibliografía incluye todas las referencias académicas utilizadas en este capítulo.</li>
<li>Se recomienda revisar los artículos científicos originales para una comprensión más profunda.</li>
<li>Las fuentes están organizadas por tema y nivel de complejidad.</li>
</ul>

<h4>🔗 Recursos Adicionales</h4>
<p>Visita los siguientes recursos en línea para complementar tu aprendizaje:</p>
<ul>
<li><strong>Red NADI:</strong> <a href="https://www.rednadi.com" target="_blank">www.rednadi.com</a></li>
<li><strong>Campus Virtual:</strong> <a href="https://campus.rednadi.com" target="_blank">campus.rednadi.com</a></li>
</ul>

<p style="background-color: #fff3e0; padding: 15px; border-left: 4px solid #ff9800; border-radius: 5px;">
<strong>💡 Nota:</strong> Descarga el PDF de bibliografía completa para acceder a todas las referencias con formato APA.
</p>
HTML;
}

// ============================================================================
// GENERACIÓN DE ARCHIVOS GLOBALES
// ============================================================================

function generateGlobalXMLFiles() {
    generateMoodleBackupXML();
    generateCourseXML();
    generateSectionsXML();
    generateAuxiliaryXMLFiles();
    echo "   ✓ Archivos XML globales generados\n";
}

function generateMoodleBackupXML() {
    global $GLOBALS;

    $backupId = bin2hex(random_bytes(16));
    $time = time();

    // Construir lista de actividades
    $activitiesXml = '';
    foreach ($GLOBALS['allActivities'] as $act) {
        $titleEsc = htmlspecialchars($act['name']);
        $activitiesXml .= "        <activity>\n";
        $activitiesXml .= "          <moduleid>{$act['moduleid']}</moduleid>\n";
        $activitiesXml .= "          <sectionid>{$act['sectionid']}</sectionid>\n";
        $activitiesXml .= "          <modulename>{$act['type']}</modulename>\n";
        $activitiesXml .= "          <title>{$titleEsc}</title>\n";
        $activitiesXml .= "          <directory>activities/{$act['type']}_{$act['moduleid']}</directory>\n";
        $activitiesXml .= "        </activity>\n";
    }

    // Construir lista de secciones
    $sectionsXml = '';
    foreach ($GLOBALS['allSections'] as $sec) {
        $titleEsc = htmlspecialchars($sec['name']);
        $sectionsXml .= "        <section>\n";
        $sectionsXml .= "          <sectionid>{$sec['id']}</sectionid>\n";
        $sectionsXml .= "          <title>{$titleEsc}</title>\n";
        $sectionsXml .= "          <directory>sections/section_{$sec['id']}</directory>\n";
        $sectionsXml .= "        </section>\n";
    }

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<moodle_backup>
  <information>
    <name>backup-huellas-invisibles-v2-{$time}</name>
    <moodle_version>2024042200</moodle_version>
    <moodle_release>4.4 (Build: 20240422)</moodle_release>
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
    <details>
      <detail backup_id="{$backupId}">
        <type>course</type>
        <format>moodle2</format>
        <interactive>1</interactive>
        <mode>10</mode>
        <execution>1</execution>
        <executiontime>0</executiontime>
      </detail>
    </details>
    <contents>
      <activities>
{$activitiesXml}      </activities>
      <sections>
{$sectionsXml}      </sections>
      <course>
        <courseid>1</courseid>
        <title>Huellas Invisibles: Neurociencia del Desarrollo Infantil</title>
        <directory>course</directory>
      </course>
    </contents>
    <settings>
      <setting>
        <level>root</level>
        <name>filename</name>
        <value>backup-huellas-invisibles-v2.mbz</value>
      </setting>
      <setting>
        <level>root</level>
        <name>users</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>anonymize</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>role_assignments</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>activities</name>
        <value>1</value>
      </setting>
      <setting>
        <level>root</level>
        <name>blocks</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>files</name>
        <value>1</value>
      </setting>
      <setting>
        <level>root</level>
        <name>filters</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>comments</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>badges</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>calendarevents</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>userscompletion</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>logs</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>grade_histories</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>questionbank</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>groups</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>competencies</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>customfield</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>contentbankcontent</name>
        <value>0</value>
      </setting>
      <setting>
        <level>root</level>
        <name>legacyfiles</name>
        <value>0</value>
      </setting>
    </settings>
  </information>
</moodle_backup>
XML;

    file_put_contents(OUTPUT_DIR . '/moodle_backup.xml', $xml);
}

function generateCourseXML() {
    global $COURSE_SUMMARY;
    $time = time();
    $summaryEsc = htmlspecialchars($COURSE_SUMMARY);

    $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<course id="1" contextid="50">
  <shortname>huellas-invisibles</shortname>
  <fullname>Huellas Invisibles: Neurociencia del Desarrollo Infantil</fullname>
  <idnumber></idnumber>
  <summary>{$summaryEsc}</summary>
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
  <category id="1">
    <name>Cursos NADI</name>
    <description></description>
  </category>
  <tags>
    <tag id="1">
      <name>neurociencia</name>
      <rawname>Neurociencia</rawname>
    </tag>
    <tag id="2">
      <name>desarrollo-infantil</name>
      <rawname>Desarrollo Infantil</rawname>
    </tag>
    <tag id="3">
      <name>primera-infancia</name>
      <rawname>Primera Infancia</rawname>
    </tag>
    <tag id="4">
      <name>educacion</name>
      <rawname>Educación</rawname>
    </tag>
  </tags>
  <customfields></customfields>
  <courseformatoptions>
    <courseformatoption>
      <courseid>1</courseid>
      <format>topics</format>
      <sectionid>0</sectionid>
      <name>hiddensections</name>
      <value>0</value>
    </courseformatoption>
    <courseformatoption>
      <courseid>1</courseid>
      <format>topics</format>
      <sectionid>0</sectionid>
      <name>coursedisplay</name>
      <value>0</value>
    </courseformatoption>
  </courseformatoptions>
</course>
XML;

    file_put_contents(OUTPUT_DIR . '/course/course.xml', $xml);

    // inforef.xml para el curso
    file_put_contents(OUTPUT_DIR . '/course/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref></fileref>
</inforef>
');

    // roles.xml para el curso
    file_put_contents(OUTPUT_DIR . '/course/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>
<roles>
  <role_overrides></role_overrides>
  <role_assignments></role_assignments>
</roles>
');

    // enrolments.xml
    $time = time();
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
}

function generateSectionsXML() {
    global $GLOBALS;
    $time = time();

    foreach ($GLOBALS['allSections'] as $section) {
        $dir = OUTPUT_DIR . '/sections/section_' . $section['id'];

        // Construir sequence
        $moduleIds = array_column($section['activities'], 'moduleid');
        $sequence = implode(',', $moduleIds);

        $nameEsc = htmlspecialchars($section['name']);
        $summaryEsc = htmlspecialchars($section['summary']);

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<section id="{$section['id']}">
  <number>{$section['number']}</number>
  <name>{$nameEsc}</name>
  <summary>{$summaryEsc}</summary>
  <summaryformat>1</summaryformat>
  <sequence>{$sequence}</sequence>
  <visible>1</visible>
  <availabilityjson>\$@NULL@\$</availabilityjson>
  <timemodified>{$time}</timemodified>
</section>
XML;
        file_put_contents($dir . '/section.xml', $xml);

        // inforef.xml para la sección
        file_put_contents($dir . '/inforef.xml', '<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref></fileref>
</inforef>
');
    }
}

function generateAuxiliaryXMLFiles() {
    $time = time();

    // roles.xml (global)
    file_put_contents(OUTPUT_DIR . '/roles.xml', '<?xml version="1.0" encoding="UTF-8"?>
<roles_definition></roles_definition>
');

    // scales.xml
    file_put_contents(OUTPUT_DIR . '/scales.xml', '<?xml version="1.0" encoding="UTF-8"?>
<scales_definition></scales_definition>
');

    // outcomes.xml
    file_put_contents(OUTPUT_DIR . '/outcomes.xml', '<?xml version="1.0" encoding="UTF-8"?>
<outcomes_definition></outcomes_definition>
');

    // questions.xml
    file_put_contents(OUTPUT_DIR . '/questions.xml', '<?xml version="1.0" encoding="UTF-8"?>
<question_categories></question_categories>
');

    // groups.xml
    file_put_contents(OUTPUT_DIR . '/groups.xml', '<?xml version="1.0" encoding="UTF-8"?>
<groups>
  <groupings></groupings>
</groups>
');

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
  <grade_items>
    <grade_item id="1">
      <categoryid>\$@NULL@\$</categoryid>
      <itemname>\$@NULL@\$</itemname>
      <itemtype>course</itemtype>
      <itemmodule>\$@NULL@\$</itemmodule>
      <iteminstance>1</iteminstance>
      <itemnumber>\$@NULL@\$</itemnumber>
      <iteminfo>\$@NULL@\$</iteminfo>
      <idnumber>\$@NULL@\$</idnumber>
      <calculation>\$@NULL@\$</calculation>
      <gradetype>1</gradetype>
      <grademax>100.00000</grademax>
      <grademin>0.00000</grademin>
      <scaleid>\$@NULL@\$</scaleid>
      <outcomeid>\$@NULL@\$</outcomeid>
      <gradepass>0.00000</gradepass>
      <multfactor>1.00000</multfactor>
      <plusfactor>0.00000</plusfactor>
      <aggregationcoef>0.00000</aggregationcoef>
      <aggregationcoef2>0.00000</aggregationcoef2>
      <weightoverride>0</weightoverride>
      <sortorder>1</sortorder>
      <display>0</display>
      <decimals>\$@NULL@\$</decimals>
      <hidden>0</hidden>
      <locked>0</locked>
      <locktime>0</locktime>
      <needsupdate>0</needsupdate>
      <timecreated>{$time}</timecreated>
      <timemodified>{$time}</timemodified>
      <grade_grades></grade_grades>
    </grade_item>
  </grade_items>
  <grade_letters></grade_letters>
  <grade_settings></grade_settings>
</gradebook>
XML;
    file_put_contents(OUTPUT_DIR . '/gradebook.xml', $gradebookXml);

    // completion.xml
    file_put_contents(OUTPUT_DIR . '/completion.xml', '<?xml version="1.0" encoding="UTF-8"?>
<course_completion></course_completion>
');
}

// ============================================================================
// PROCESAMIENTO DE ARCHIVOS
// ============================================================================

function processAllFiles() {
    global $GLOBALS;
    $fileCount = 0;

    foreach ($GLOBALS['allActivities'] as &$activity) {
        if (!empty($activity['filepath']) && file_exists($activity['filepath'])) {
            $fileId = processFile($activity);
            if ($fileId) {
                updateActivityInforef($activity, $fileId);
                $fileCount++;
            }
        }
    }

    generateFilesXML();
    echo "   ✓ Archivos procesados: {$fileCount}\n";
}

function processFile($activity) {
    $filePath = $activity['filepath'];
    $fileName = $activity['filename'];

    if (!file_exists($filePath)) {
        return null;
    }

    $contentHash = sha1_file($filePath);
    $fileSize = filesize($filePath);
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
    }

    $fileId = $GLOBALS['nextFileId']++;

    // Determinar filearea según tipo de actividad
    $filearea = 'content';
    $itemid = 0;
    if ($activity['type'] === 'label') {
        $filearea = 'intro';
        $itemid = $activity['instanceid'];
    }

    $GLOBALS['allFiles'][] = [
        'id' => $fileId,
        'contenthash' => $contentHash,
        'contextid' => $activity['contextid'],
        'component' => 'mod_' . $activity['type'],
        'filearea' => $filearea,
        'itemid' => $itemid,
        'filepath' => '/',
        'filename' => $fileName,
        'filesize' => $fileSize,
        'mimetype' => $mimeType,
        'timecreated' => time(),
        'timemodified' => time()
    ];

    return $fileId;
}

function updateActivityInforef($activity, $fileId) {
    $dir = OUTPUT_DIR . '/activities/' . $activity['type'] . '_' . $activity['moduleid'];

    $inforef = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<inforef>
  <fileref>
    <file>
      <id>{$fileId}</id>
    </file>
  </fileref>
</inforef>
XML;

    file_put_contents($dir . '/inforef.xml', $inforef);
}

function generateFilesXML() {
    global $GLOBALS;

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<files>' . "\n";

    foreach ($GLOBALS['allFiles'] as $file) {
        $filenameEsc = htmlspecialchars($file['filename']);
        $xml .= "  <file id=\"{$file['id']}\">\n";
        $xml .= "    <contenthash>{$file['contenthash']}</contenthash>\n";
        $xml .= "    <contextid>{$file['contextid']}</contextid>\n";
        $xml .= "    <component>{$file['component']}</component>\n";
        $xml .= "    <filearea>{$file['filearea']}</filearea>\n";
        $xml .= "    <itemid>{$file['itemid']}</itemid>\n";
        $xml .= "    <filepath>{$file['filepath']}</filepath>\n";
        $xml .= "    <filename>{$filenameEsc}</filename>\n";
        $xml .= "    <userid>\$@NULL@\$</userid>\n";
        $xml .= "    <filesize>{$file['filesize']}</filesize>\n";
        $xml .= "    <mimetype>{$file['mimetype']}</mimetype>\n";
        $xml .= "    <status>0</status>\n";
        $xml .= "    <timecreated>{$file['timecreated']}</timecreated>\n";
        $xml .= "    <timemodified>{$file['timemodified']}</timemodified>\n";
        $xml .= "    <source>{$filenameEsc}</source>\n";
        $xml .= "    <author>Jaqui Esquitino</author>\n";
        $xml .= "    <license>allrightsreserved</license>\n";
        $xml .= "    <sortorder>0</sortorder>\n";
        $xml .= "    <repositorytype>\$@NULL@\$</repositorytype>\n";
        $xml .= "    <repositoryid>\$@NULL@\$</repositoryid>\n";
        $xml .= "    <reference>\$@NULL@\$</reference>\n";
        $xml .= "  </file>\n";
    }

    $xml .= '</files>' . "\n";

    file_put_contents(OUTPUT_DIR . '/files.xml', $xml);
}

// ============================================================================
// CREACIÓN DEL MBZ
// ============================================================================

function createMBZ() {
    $mbzPath = OUTPUT_DIR . '/' . MBZ_FILENAME;

    if (file_exists($mbzPath)) {
        unlink($mbzPath);
    }

    $zip = new ZipArchive();
    if ($zip->open($mbzPath, ZipArchive::CREATE) !== TRUE) {
        die("Error: No se pudo crear el archivo ZIP\n");
    }

    addDirectoryToZip($zip, OUTPUT_DIR, '');

    $numFiles = $zip->numFiles;
    $zip->close();

    $size = formatBytes(filesize($mbzPath));
    echo "   ✓ Archivo MBZ creado: {$numFiles} archivos, {$size}\n";
}

function addDirectoryToZip($zip, $dir, $zipPath) {
    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $filePath = $dir . '/' . $file;
        $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;

        if (pathinfo($file, PATHINFO_EXTENSION) === 'mbz') continue;

        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipFilePath);
            addDirectoryToZip($zip, $filePath, $zipFilePath);
        } else {
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}

function getMimeType($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'mp4' => 'video/mp4',
        'txt' => 'text/plain',
        'html' => 'text/html',
        'zip' => 'application/zip',
    ];
    return isset($mimeTypes[$ext]) ? $mimeTypes[$ext] : 'application/octet-stream';
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// ============================================================================
// EJECUTAR
// ============================================================================

main();
