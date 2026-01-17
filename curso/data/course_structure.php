<?php
/**
 * Estructura del curso Huellas Invisibles
 * Mapeo de secciones y actividades
 */

$COURSE_STRUCTURE = [
    // Seccion 0: Presentacion
    0 => [
        'name' => 'Presentacion del Curso',
        'summary' => 'Bienvenido al curso Huellas Invisibles - Neurodesarrollo y neuroaprendizaje acuatico en la primera infancia',
        'dir' => null,
        'activities' => [
            ['type' => 'intro_label', 'name' => 'Presentacion del Curso'],
            ['file' => 'Titulo presentacion y programa.docx', 'type' => 'resource', 'name' => 'Programa del Curso'],
        ]
    ],

    // Seccion 1: Capitulo 1
    1 => [
        'name' => 'Capitulo 1: Desarrollo Neurologico Infantil',
        'summary' => 'Bases del neurodesarrollo y experiencias tempranas',
        'dir' => 'Capitulo 1 Desarrollo Neurológico Infantil',
        'activities' => [
            ['file' => '1-Capitulo 1 Desarrollo Neurologico Infantil_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Desarrollo Neurologico Infantil'],
            ['file' => '2-Esculpiendo el Cerebro Primera Infancia (Cap 1).pdf', 'type' => 'resource', 'name' => 'Libro: Esculpiendo el Cerebro - Primera Infancia'],
            ['file' => '3-DOCUMENTO DE ESTUDIO COMPLEMENTARIO (Cap 1).pdf', 'type' => 'resource', 'name' => 'Documento de Estudio Complementario'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 1', 'chapter' => 1],
            ['file' => '5- Bibliografia de referencia y recomendada (Cap 1).pdf', 'type' => 'resource', 'name' => 'Bibliografia de Referencia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 1', 'chapter' => 1],
        ]
    ],

    // Seccion 2: Capitulo 2
    2 => [
        'name' => 'Capitulo 2: Funciones Emocionales',
        'summary' => 'Emocion, cerebro y regulacion afectiva',
        'dir' => 'Capitulo 2- Funciones emocionales_',
        'activities' => [
            ['file' => '1-Capitulo 2 Funciones emocionales.txt', 'type' => 'video_placeholder', 'name' => 'Video: Funciones Emocionales'],
            ['file' => '2- CEREBRO EN CONSTRUCCION.pdf', 'type' => 'resource', 'name' => 'Libro: Cerebro en Construccion'],
            ['file' => '3-DOCUMENTO COMPLEMENTARIO CAP 2.pdf', 'type' => 'resource', 'name' => 'Documento Complementario'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 2', 'chapter' => 2],
            ['file' => '4-BIBLIOGRAFIA CAP 2.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 2', 'chapter' => 2],
        ]
    ],

    // Seccion 3: Capitulo 3
    3 => [
        'name' => 'Capitulo 3: Plasticidad Cerebral',
        'summary' => 'Aprendizaje, experiencia y cambio cerebral',
        'dir' => 'Capitulo 3 - Plasticidad Cerebral',
        'activities' => [
            ['file' => '1- Capitulo 3 Plasticidad Cerebral.txt', 'type' => 'video_placeholder', 'name' => 'Video: Plasticidad Cerebral'],
            ['file' => '2-El Cerebro Moldeable Principios y Práctica.pdf', 'type' => 'resource', 'name' => 'Libro: El Cerebro Moldeable'],
            ['file' => '3-Documento de estudio cap 3.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 3', 'chapter' => 3],
            ['file' => '5-BIBLIOGRAFIA CAP 3.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 3', 'chapter' => 3],
        ]
    ],

    // Seccion 4: Capitulo 4
    4 => [
        'name' => 'Capitulo 4: Sinaptogenesis',
        'summary' => 'Conexiones neuronales y ventanas de oportunidad',
        'dir' => 'Capitulo 4- Sinaptogénesis',
        'activities' => [
            ['file' => '1- Capitulo 4 Sinaptogenesis.txt', 'type' => 'video_placeholder', 'name' => 'Video: Sinaptogenesis'],
            ['file' => '2-El Cerebro Plástico De la Construcción a la Maestría.pdf', 'type' => 'resource', 'name' => 'Libro: El Cerebro Plastico'],
            ['file' => '3-Documento de estudio cap 4.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 4', 'chapter' => 4],
            ['file' => '5-BIBLIOGRAFIA CAP 4.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 4', 'chapter' => 4],
        ]
    ],

    // Seccion 5: Capitulo 5
    5 => [
        'name' => 'Capitulo 5: Periodo Sensoriomotor',
        'summary' => 'Cuerpo, movimiento y construccion de la mente',
        'dir' => 'Capitulo 5 -  Período sensoriomotor',
        'activities' => [
            ['file' => '1-Capitulo 5 Periodo sensoriomotor_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Periodo Sensoriomotor'],
            ['file' => '2-Construyendo_la_Realidad_La_Mente_Sensoriomotora2.pdf', 'type' => 'resource', 'name' => 'Libro: Construyendo la Realidad'],
            ['file' => '3-Documento Complementario de Estudio Cap 5.pdf', 'type' => 'resource', 'name' => 'Documento Complementario'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 5', 'chapter' => 5],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 5.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 5', 'chapter' => 5],
        ]
    ],

    // Seccion 6: Capitulo 6
    6 => [
        'name' => 'Capitulo 6: Estres y Apego',
        'summary' => 'Vinculo, regulacion y neurodesarrollo',
        'dir' => 'Capitulo 6 - Stress y Apego',
        'activities' => [
            ['file' => '1-Capitulo 6 Stress y apego.txt', 'type' => 'video_placeholder', 'name' => 'Video: Estres y Apego'],
            ['file' => '2-Arquitectos_del_Cerebro_Infantil_Apego_y_Estrés.pdf', 'type' => 'resource', 'name' => 'Libro: Arquitectos del Cerebro Infantil'],
            ['file' => '3-Documento de estudio capitulo 6.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 6', 'chapter' => 6],
            ['file' => '5- BIBLIOGRAFIA CAPITULO 6.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 6', 'chapter' => 6],
        ]
    ],

    // Seccion 7: Capitulo 7
    7 => [
        'name' => 'Capitulo 7: Teoria Epigenetica',
        'summary' => 'Herencia, ambiente y experiencia',
        'dir' => 'Capitulo 7- Teoría Epigenética',
        'activities' => [
            ['file' => '1-Capitulo 7 Epigenetica_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Teoria Epigenetica'],
            ['file' => '2-Huellas Invisibles Entorno y Genes.pdf', 'type' => 'resource', 'name' => 'Libro: Huellas Invisibles - Entorno y Genes'],
            ['file' => '3-Documento de estudio Capitulo 7.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 7', 'chapter' => 7],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 7.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 7', 'chapter' => 7],
        ]
    ],

    // Seccion 8: Capitulo 8
    8 => [
        'name' => 'Capitulo 8: Reflejo de Apnea',
        'summary' => 'Bases neurofisiologicas y medio acuatico',
        'dir' => 'Capitulo 8 - Reflejo de Apnea',
        'activities' => [
            ['file' => '1-Capitulo 8 Reflejo de apnea_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: Reflejo de Apnea'],
            ['file' => '2-Ancestral Water Echo.pdf', 'type' => 'resource', 'name' => 'Libro: Ancestral Water Echo'],
            ['file' => '3-Documento de estudio Capitulo 8.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 8', 'chapter' => 8],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 8.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 8', 'chapter' => 8],
        ]
    ],

    // Seccion 9: Capitulo 9
    9 => [
        'name' => 'Capitulo 9: Socializacion en la Primera Infancia',
        'summary' => 'Vinculo social y construccion emocional',
        'dir' => 'Capitulo 9 - Socialización en la 1er infancia',
        'activities' => [
            ['file' => '1-Capitulo 9 Socializacion.txt', 'type' => 'video_placeholder', 'name' => 'Video: Socializacion'],
            ['file' => '2-Arquitectos de Mentes Construyendo el Cerebro.pdf', 'type' => 'resource', 'name' => 'Libro: Arquitectos de Mentes'],
            ['file' => '3-Documento de estudio Capitulo 9.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 9', 'chapter' => 9],
            ['file' => '5- BIBLIOGRAFIA capitulo 9.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 9', 'chapter' => 9],
        ]
    ],

    // Seccion 10: Capitulo 10
    10 => [
        'name' => 'Capitulo 10: La Atencion',
        'summary' => 'Foco atencional, emocion y aprendizaje',
        'dir' => 'Capitulo 10- La Atención',
        'activities' => [
            ['file' => 'Capitulo 10 Atencion_0.txt', 'type' => 'video_placeholder', 'name' => 'Video: La Atencion'],
            ['file' => '2-El Arquitecto Invisible Un Viaje al Corazón de la Atención.pdf', 'type' => 'resource', 'name' => 'Libro: El Arquitecto Invisible'],
            ['file' => '3-Documento de estudio Capitulo 10.pdf', 'type' => 'resource', 'name' => 'Documento de Estudio'],
            ['type' => 'glossary', 'name' => 'Glosario de Terminos - Capitulo 10', 'chapter' => 10],
            ['file' => '5-BIBLIOGRAFIA CAPITULO 10.pdf', 'type' => 'resource', 'name' => 'Bibliografia'],
            ['type' => 'quiz', 'name' => 'Autoevaluacion - Capitulo 10', 'chapter' => 10],
        ]
    ],

    // Seccion 11: Cierre
    11 => [
        'name' => 'Cierre del Curso',
        'summary' => 'Evaluacion final y certificacion',
        'dir' => null,
        'activities' => [
            ['type' => 'feedback', 'name' => 'Encuesta de Opinion - Cierre del Curso'],
            ['type' => 'customcert', 'name' => 'Certificado del Curso'],
        ]
    ]
];

// Iconos SVG para cada seccion
$SECTION_ICONS = [
    0 => '#667eea', // Intro - Purpura
    1 => '#667eea', // Cap1 - Desarrollo neurologico
    2 => '#f5576c', // Cap2 - Emociones - Rosa
    3 => '#4facfe', // Cap3 - Plasticidad - Azul
    4 => '#fa709a', // Cap4 - Sinaptogenesis - Rosa/Amarillo
    5 => '#a8edea', // Cap5 - Sensoriomotor - Verde agua
    6 => '#fcb69f', // Cap6 - Estres/Apego - Naranja
    7 => '#a18cd1', // Cap7 - Epigenetica - Lavanda
    8 => '#66a6ff', // Cap8 - Apnea - Azul agua
    9 => '#fcb69f', // Cap9 - Socializacion - Naranja
    10 => '#f5af19', // Cap10 - Atencion - Amarillo
    11 => '#38ef7d', // Cierre - Verde
];
