<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/reports_custom:addinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,  // Correctamente configurado para el contexto de bloque
        'archetypes' => [
            'manager' => CAP_ALLOW,  // Solo los gestores pueden añadir instancias del bloque
        ],
        'clonepermissionsfrom' => 'moodle/site:manageblocks',  // Clonar permisos de manageblocks
    ],

    'block/reports_custom:myaddinstance' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,  // Aplicable también a CONTEXT_BLOCK
        'archetypes' => [
            'manager' => CAP_ALLOW,  // Solo los gestores pueden añadir instancias del bloque en su página 'Mi Moodle'
        ],
    ],

    'block/reports_custom:viewreports' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,  // Usando el contexto del sistema para la visualización de reportes
        'archetypes' => [
            'manager' => CAP_ALLOW,  // Solo los gestores tienen permiso para ver los reportes
        ],
    ],
    'block/reports_custom:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ]
    ],
];
