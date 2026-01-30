const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, BorderStyle, AlignmentType, HeadingLevel, PageBreak, Header, Footer, ImageRun } = require('docx');
const fs = require('fs');

// Fecha actual
const today = new Date();
const dateStr = today.toLocaleDateString('es-ES', { day: '2-digit', month: 'long', year: 'numeric' });

// Tipografía Arial para todo el documento
const FONT_FAMILY = 'Arial';

// Estilos comunes con Arial
const titleStyle = { bold: true, size: 48, color: '1a5276', font: FONT_FAMILY };
const heading1Style = { bold: true, size: 32, color: '2874a6', font: FONT_FAMILY };
const heading2Style = { bold: true, size: 28, color: '2e86ab', font: FONT_FAMILY };
const heading3Style = { bold: true, size: 24, color: '5dade2', font: FONT_FAMILY };
const normalStyle = { size: 22, font: FONT_FAMILY };
const smallStyle = { size: 20, font: FONT_FAMILY };
const italicStyle = { size: 22, italics: true, font: FONT_FAMILY };

// Función para crear celda de tabla
function createCell(text, options = {}) {
    return new TableCell({
        children: [new Paragraph({
            children: [new TextRun({ text, size: 20, bold: options.bold || false, font: FONT_FAMILY })],
            alignment: options.align || AlignmentType.LEFT
        })],
        shading: options.shading ? { fill: options.shading } : undefined,
        width: options.width ? { size: options.width, type: WidthType.PERCENTAGE } : undefined
    });
}

// Función para crear fila de tabla
function createRow(cells, isHeader = false) {
    return new TableRow({
        children: cells.map(cell =>
            typeof cell === 'string'
                ? createCell(cell, { bold: isHeader, shading: isHeader ? 'e8f6f3' : undefined })
                : cell
        )
    });
}

// Crear documento
const doc = new Document({
    sections: [{
        properties: {},
        headers: {
            default: new Header({
                children: [new Paragraph({
                    children: [new TextRun({ text: 'Manual de Usuario - Plugin Caracterización RED | UDES', size: 18, color: '7f8c8d', font: FONT_FAMILY })],
                    alignment: AlignmentType.RIGHT
                })]
            })
        },
        footers: {
            default: new Footer({
                children: [new Paragraph({
                    children: [new TextRun({ text: `© 2025 Universidad de Santander - UDES | Página `, size: 18, font: FONT_FAMILY })],
                    alignment: AlignmentType.CENTER
                })]
            })
        },
        children: [
            // ============ PORTADA ============
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: 'MANUAL DE USUARIO', ...titleStyle, size: 56 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: 'Plugin Caracterización RED', bold: true, size: 40, color: '2874a6' })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: 'Módulo de Caracterización de Recursos Educativos Digitales', size: 28 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: 'Universidad de Santander - UDES', bold: true, size: 32 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: `Versión: 1.1.2`, size: 24 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({
                children: [new TextRun({ text: `Fecha: ${dateStr}`, size: 24 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({
                children: [new TextRun({ text: 'Autor: Alonso Arias - OrionCloud', size: 24 })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ TABLA DE CONTENIDOS ============
            new Paragraph({
                children: [new TextRun({ text: 'TABLA DE CONTENIDOS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '1. Introducción', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   1.1 Descripción General', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   1.2 Objetivos del Plugin', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   1.3 Alcance', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Requisitos del Sistema', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. Instalación', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   3.1 Pasos de Instalación', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   3.2 Roles Creados Automáticamente', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   3.3 Verificación de la Instalación', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '4. Roles y Permisos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   4.1 Descripción de Cada Rol', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   4.2 Matriz de Permisos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '5. Flujo de Trabajo - Las 6 Fases', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.1 Diagrama del Flujo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.2 Fase 1: Diligencia la Caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.3 Fase 2: Revisión Curricular', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.4 Fase 3: Par / Corrector de Estilo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.5 Fase 4: Producción', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.6 Fase 5: Alistamiento en Moodle', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   5.7 Fase 6: Aprobación Final', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '6. Guía de Uso Detallada', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   6.1 Crear una Actividad', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   6.2 Crear una Matriz de Caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   6.3 Gestionar Unidades y Temas', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   6.4 Agregar Recursos Educativos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '   6.5 Gestionar Fases', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '7. Catálogo de Recursos Educativos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '8. Sistema de Notificaciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '9. Preguntas Frecuentes', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '10. Solución de Problemas', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '11. Glosario', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '12. Soporte Técnico', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 1. INTRODUCCIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '1. INTRODUCCIÓN', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '1.1 Descripción General', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'El Módulo de Caracterización RED (Recursos Educativos Digitales) es un plugin desarrollado específicamente para la plataforma Moodle de la Universidad de Santander (UDES). Este módulo permite gestionar de manera integral el proceso completo de producción de Recursos Educativos Digitales, siguiendo un flujo de trabajo estructurado y colaborativo que involucra a múltiples roles del equipo de producción académica.',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'El plugin implementa el proceso de producción de RED de la UDES, que consta de 6 fases secuenciales, cada una con roles específicos responsables de su ejecución y aprobación. Este enfoque garantiza la calidad de los recursos producidos y facilita la trazabilidad de todo el proceso.',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '1.2 Objetivos del Plugin', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Los objetivos principales del plugin son:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Centralizar la gestión de caracterización de cursos virtuales: Permitir que todos los actores del proceso trabajen en una única plataforma integrada con Moodle.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Automatizar el flujo de trabajo de producción de RED: Implementar las 6 fases del proceso UDES con transiciones automáticas y validaciones de permisos.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Facilitar la colaboración entre roles: Permitir que expertos disciplinares, asesores metodológicos, revisores y equipos de producción trabajen de manera coordinada.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Generar seguimiento detallado: Registrar todas las acciones, comentarios y aprobaciones para mantener un historial completo del proceso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Mantener inventario de recursos: Catalogar y contabilizar todos los recursos educativos definidos para cada curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '1.3 Alcance', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'Este plugin está diseñado para ser utilizado dentro del ecosistema Moodle de la UDES. Cubre las siguientes funcionalidades:',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Creación y gestión de matrices de caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Asignación de roles del proceso de producción', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Definición de recursos generales del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Estructuración de unidades y temas', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Especificación de recursos educativos por tema', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Gestión de las 6 fases del flujo de trabajo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Sistema de aprobaciones y rechazos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Comentarios y retroalimentación por fase', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Notificaciones automáticas a los roles correspondientes', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 2. REQUISITOS DEL SISTEMA ============
            new Paragraph({
                children: [new TextRun({ text: '2. REQUISITOS DEL SISTEMA', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'Para instalar y ejecutar correctamente el plugin Caracterización RED, su sistema debe cumplir con los siguientes requisitos mínimos:',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),

            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Componente', 'Requisito Mínimo', 'Recomendado'], true),
                    createRow(['Moodle', '4.0 o superior', '4.1 LTS o superior']),
                    createRow(['PHP', '7.4 o superior', '8.1 o superior']),
                    createRow(['MySQL', '5.7 o superior', '8.0 o superior']),
                    createRow(['MariaDB', '10.2 o superior', '10.6 o superior']),
                    createRow(['PostgreSQL', '10 o superior', '14 o superior']),
                    createRow(['Memoria PHP', '128 MB', '256 MB o más']),
                    createRow(['Navegador', 'Chrome 90+, Firefox 88+, Edge 90+', 'Última versión disponible']),
                ]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'Nota importante: El plugin utiliza funciones de JavaScript moderno (ES6+) y requiere que el navegador del usuario soporte estas características. Se recomienda mantener los navegadores actualizados.',
                    ...italicStyle
                })]
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 3. INSTALACIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '3. INSTALACIÓN', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '3.1 Pasos de Instalación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Siga estos pasos detallados para instalar el plugin:', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 1: Descarga del Plugin', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Descargue el archivo del plugin desde el repositorio oficial o la fuente proporcionada por el administrador del sistema.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 2: Extracción de Archivos', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Extraiga el contenido del archivo descargado en la carpeta /mod/ de su instalación de Moodle. La estructura debe quedar como: /mod/caracterizacion/', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 3: Acceso como Administrador', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Inicie sesión en su plataforma Moodle con una cuenta de administrador del sitio.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 4: Detección del Plugin', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Navegue a: Administración del sitio → Notificaciones. Moodle detectará automáticamente el nuevo plugin y mostrará la pantalla de actualización de la base de datos.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 5: Ejecución de la Instalación', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Haga clic en el botón "Actualizar base de datos de Moodle ahora". El sistema creará automáticamente todas las tablas necesarias y los 8 roles UDES.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Paso 6: Verificación', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Una vez completada la instalación, verifique que el plugin aparezca en la lista de actividades disponibles al editar un curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '3.2 Roles Creados Automáticamente', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Durante la instalación, el plugin crea automáticamente los siguientes 8 roles en el sistema Moodle:', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Rol', 'Nombre Interno', 'Arquetipo Base'], true),
                    createRow(['Experto Disciplinar', 'udes_experto_disciplinar', 'editingteacher']),
                    createRow(['Asesor Metodológico', 'udes_asesor_metodologico', 'editingteacher']),
                    createRow(['Revisor Curricular', 'udes_revisor_curricular', 'teacher']),
                    createRow(['Par Académico', 'udes_par_academico', 'teacher']),
                    createRow(['Corrector de Estilo', 'udes_corrector_estilo', 'teacher']),
                    createRow(['Coordinación de Producción', 'udes_coord_produccion', 'editingteacher']),
                    createRow(['Jefe de Medios', 'udes_jefe_medios', 'editingteacher']),
                    createRow(['Producción', 'udes_produccion', 'teacher']),
                    createRow(['Alistamiento', 'udes_alistamiento', 'teacher']),
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '3.3 Verificación de la Instalación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Para verificar que la instalación se completó correctamente:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '1. Vaya a Administración del sitio → Plugins → Vista general de plugins', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Busque "Caracterización RED" en la lista de módulos de actividad', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. Verifique que la versión mostrada sea la correcta (1.2.0)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '4. Vaya a Administración del sitio → Usuarios → Permisos → Definir roles', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '5. Verifique que los 9 roles UDES estén presentes en la lista', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 4. ROLES Y PERMISOS ============
            new Paragraph({
                children: [new TextRun({ text: '4. ROLES Y PERMISOS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'El plugin define 9 roles específicos que corresponden a los actores del proceso de producción de RED de la UDES. Cada rol tiene permisos específicos que determinan qué acciones puede realizar en cada fase del proceso.',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '4.1 Descripción Detallada de Cada Rol', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),

            // Experto Disciplinar
            new Paragraph({ children: [new TextRun({ text: '4.1.1 Experto Disciplinar', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Experto Disciplinar es el docente o profesional especializado en el área temática del curso. Es responsable de definir el contenido académico y los recursos necesarios para cada tema.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Diligenciar completamente el formulario de caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Definir la estructura de unidades y temas del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Especificar los recursos educativos necesarios para cada tema', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Proporcionar el contenido/guiones para los recursos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Colaborar con el Asesor Metodológico en la Fase 1', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 1 (Diligencia la Caracterización)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Crear matriz, Editar matriz, Actuar en fase 1', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Asesor Metodológico
            new Paragraph({ children: [new TextRun({ text: '4.1.2 Asesor Metodológico (Diseñador Instruccional)', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Asesor Metodológico es el profesional encargado de garantizar la calidad pedagógica del curso. Acompaña todo el proceso y es el principal responsable de las aprobaciones.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Acompañar al Experto Disciplinar en la elaboración de la caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar la coherencia pedagógica del diseño del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Coordinar los ajustes requeridos en cada fase', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Aprobar o rechazar las fases 1, 2, 3 y 6', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Dar la aprobación final del curso', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 1, 2, 3 y 6', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Crear matriz, Editar matriz, Aprobar fases 1/2/3/6, Ver todas las matrices', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Revisor Curricular
            new Paragraph({ children: [new TextRun({ text: '4.1.3 Revisor Curricular', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Revisor Curricular verifica que el contenido del curso esté alineado con el currículo del programa académico y las políticas institucionales.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar la caracterización desde la perspectiva curricular', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar coherencia con el plan de estudios', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Realizar observaciones y recomendaciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Documentar ajustes requeridos', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 2 (Revisión Curricular)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver matriz, Actuar en fase 2, Comentar', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Par Académico
            new Paragraph({ children: [new TextRun({ text: '4.1.4 Par Académico (Par Disciplinar)', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Par Académico es otro experto en el área disciplinar que revisa el contenido desde una perspectiva externa para garantizar su calidad y pertinencia.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar el contenido disciplinar propuesto', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar la actualidad y pertinencia del contenido', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Sugerir mejoras o complementos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Documentar sus observaciones', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 3 (Par / Corrector de Estilo)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver matriz, Actuar en fase 3, Comentar', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Corrector de Estilo
            new Paragraph({ children: [new TextRun({ text: '4.1.5 Corrector de Estilo', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Corrector de Estilo revisa y ajusta los textos de la caracterización para garantizar la calidad lingüística y comunicativa.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar ortografía y gramática', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar claridad y coherencia de los textos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Ajustar el estilo según las políticas institucionales', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Documentar los cambios realizados', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 3 (Par / Corrector de Estilo)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver matriz, Actuar en fase 3, Comentar', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Coordinación de Producción
            new Paragraph({ children: [new TextRun({ text: '4.1.6 Coordinación de Producción', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Coordinador de Producción gestiona el equipo de diseño y producción de los recursos educativos digitales.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recibir la caracterización aprobada', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Asignar recursos al equipo de diseño', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Supervisar la producción de los RED', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Aprobar los recursos producidos', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 4 (Producción)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver todas las matrices, Actuar en fase 4, Aprobar fase 4', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Jefe de Medios
            new Paragraph({ children: [new TextRun({ text: '4.1.7 Jefe de Medios', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El Jefe de Medios Educativos es responsable de supervisar el área de producción de recursos educativos digitales. Recibe notificaciones junto con Coordinación de Producción cuando la fase 3 es aprobada.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Supervisar el proceso de producción de RED', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recibir notificaciones cuando se aprueban las caracterizaciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Monitorear el estado de las producciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Coordinar con el equipo de producción', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 4 (Producción) - Rol de supervisión', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver todas las matrices', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Producción
            new Paragraph({ children: [new TextRun({ text: '4.1.8 Producción (Diseñador)', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El profesional de Producción es responsable de crear los recursos educativos digitales según las especificaciones de la caracterización.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Crear videos, infografías, interactivos y demás RED', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Seguir las especificaciones de la caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Entregar recursos con estándares de calidad', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Documentar el estado de producción', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 4 (Producción)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver matriz, Actuar en fase 4, Comentar', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Alistamiento
            new Paragraph({ children: [new TextRun({ text: '4.1.9 Alistamiento', ...heading3Style })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción: El profesional de Alistamiento es responsable de subir y configurar todos los recursos en la plataforma UDES Virtual (Moodle).', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Responsabilidades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recibir los recursos producidos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Subir los recursos a la plataforma Moodle', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Configurar las actividades según la caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar el correcto funcionamiento de todos los elementos', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fases de participación: Fase 5 (Alistamiento en Moodle)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Permisos: Ver matriz, Actuar en fase 5, Aprobar fase 5', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '4.2 Matriz de Permisos por Rol', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),

            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Permiso', 'Exp.', 'Ases.', 'Rev.', 'Par', 'Corr.', 'Coord.', 'Jefe', 'Prod.', 'Alist.'], true),
                    createRow(['Crear matriz', 'Sí', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Editar matriz', 'Sí', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Ver todas las matrices', 'No', 'Sí', 'No', 'No', 'No', 'Sí', 'Sí', 'No', 'No']),
                    createRow(['Actuar Fase 1', 'Sí', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Actuar Fase 2', 'No', 'Sí', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Actuar Fase 3', 'No', 'Sí', 'No', 'Sí', 'Sí', 'No', 'No', 'No', 'No']),
                    createRow(['Actuar Fase 4', 'No', 'No', 'No', 'No', 'No', 'Sí', 'No', 'Sí', 'No']),
                    createRow(['Actuar Fase 5', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Sí']),
                    createRow(['Actuar Fase 6', 'No', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Aprobar Fase 1,2,3,6', 'No', 'Sí', 'No', 'No', 'No', 'No', 'No', 'No', 'No']),
                    createRow(['Aprobar Fase 4', 'No', 'No', 'No', 'No', 'No', 'Sí', 'No', 'No', 'No']),
                    createRow(['Aprobar Fase 5', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'No', 'Sí']),
                    createRow(['Notificado Fase 3→4', 'No', 'No', 'No', 'No', 'No', 'Sí', 'Sí', 'No', 'No']),
                ]
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 5. FLUJO DE TRABAJO ============
            new Paragraph({
                children: [new TextRun({ text: '5. FLUJO DE TRABAJO - LAS 6 FASES', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'El proceso de producción de RED en la UDES sigue un flujo secuencial de 6 fases. Cada fase debe ser aprobada antes de que la siguiente pueda iniciarse. Este modelo garantiza la calidad y trazabilidad del proceso completo.',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.1 Diagrama del Flujo de Trabajo', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'El flujo de trabajo sigue la siguiente secuencia:', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            // Tabla del flujo de trabajo - Fila superior (Fases 1, 2, 3)
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    new TableRow({
                        children: [
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 1', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Diligencia la Caracterización', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '2874a6' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: '→', bold: true, size: 28, font: FONT_FAMILY })],
                                    alignment: AlignmentType.CENTER
                                })],
                                width: { size: 1, type: WidthType.PERCENTAGE },
                                verticalAlign: 'center'
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 2', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Revisión Curricular', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '2e86ab' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: '→', bold: true, size: 28, font: FONT_FAMILY })],
                                    alignment: AlignmentType.CENTER
                                })],
                                width: { size: 1, type: WidthType.PERCENTAGE },
                                verticalAlign: 'center'
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 3', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Par / Corrector de Estilo', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '5dade2' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                        ]
                    })
                ]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({ text: '↓ Continúa el flujo...', size: 20, font: FONT_FAMILY, italics: true })],
                alignment: AlignmentType.RIGHT
            }),
            new Paragraph({ children: [] }),

            // Tabla del flujo de trabajo - Fila inferior (Fases 4, 5, 6)
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    new TableRow({
                        children: [
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 4', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Producción', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '1abc9c' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: '→', bold: true, size: 28, font: FONT_FAMILY })],
                                    alignment: AlignmentType.CENTER
                                })],
                                width: { size: 1, type: WidthType.PERCENTAGE },
                                verticalAlign: 'center'
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 5', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Alistamiento en Moodle', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '27ae60' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: '→', bold: true, size: 28, font: FONT_FAMILY })],
                                    alignment: AlignmentType.CENTER
                                })],
                                width: { size: 1, type: WidthType.PERCENTAGE },
                                verticalAlign: 'center'
                            }),
                            new TableCell({
                                children: [new Paragraph({
                                    children: [new TextRun({ text: 'FASE 6', bold: true, size: 22, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                }), new Paragraph({
                                    children: [new TextRun({ text: 'Aprobación Final', size: 18, font: FONT_FAMILY, color: 'FFFFFF' })],
                                    alignment: AlignmentType.CENTER
                                })],
                                shading: { fill: '16a085' },
                                width: { size: 33, type: WidthType.PERCENTAGE }
                            }),
                        ]
                    })
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.2 Fase 1: Diligencia la Caracterización', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Experto Disciplinar, Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Esta es la fase inicial donde se define completamente la estructura y contenido del curso. El Experto Disciplinar, con el acompañamiento del Asesor Metodológico, completa el formulario de caracterización que incluye:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: '• Información del curso: Programa académico y nombre del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Asignación de roles: Identificación de todos los participantes del proceso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos generales: CVP, sala virtual, video de bienvenida, foro y mapa', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Estructura de unidades: Definición de las unidades del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Temas por unidad: Desglose de temas dentro de cada unidad', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos por tema: Especificación detallada de cada recurso educativo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Contenido de recursos: Guiones, descripciones y observaciones', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Criterios de aprobación:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Todos los campos obligatorios están completos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• La estructura de unidades y temas es coherente', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Los recursos seleccionados son apropiados para los objetivos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• El contenido/guiones están suficientemente detallados', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.3 Fase 2: Revisión Curricular', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Revisor Curricular, Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En esta fase, el Revisor Curricular analiza la caracterización para verificar su alineación con el currículo del programa académico. El Asesor Metodológico coordina los ajustes necesarios.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar coherencia con el plan de estudios', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar que los contenidos cumplan con los estándares curriculares', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Identificar posibles vacíos o redundancias', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Documentar observaciones y recomendaciones', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.4 Fase 3: Par / Corrector de Estilo', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Par Académico, Corrector de Estilo, Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Esta fase combina dos revisiones importantes: la revisión por pares desde la perspectiva disciplinar y la corrección de estilo de los textos.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades del Par Académico:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar la pertinencia y actualidad del contenido', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar la rigurosidad académica', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Sugerir mejoras o complementos', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades del Corrector de Estilo:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar ortografía y gramática', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar claridad y coherencia', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Ajustar el estilo según lineamientos institucionales', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.5 Fase 4: Producción', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Coordinación de Producción, Jefe de Medios (supervisión), Producción (Diseñador)', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Coordinación de Producción', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Notificados al iniciar: Coordinación de Producción y Jefe de Medios', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En esta fase se materializan los recursos educativos digitales. El equipo de producción crea videos, infografías, interactivos y demás recursos especificados en la caracterización.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Coordinación asigna recursos al equipo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Diseñadores crean los RED según especificaciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Control de calidad de los recursos producidos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Entrega de recursos para alistamiento', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.6 Fase 5: Alistamiento en Moodle', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Alistamiento', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Alistamiento', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'El profesional de Alistamiento sube todos los recursos a la plataforma UDES Virtual y configura las actividades del curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Subir recursos a la plataforma Moodle', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Configurar actividades y recursos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar funcionamiento correcto', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Organizar estructura del curso', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '5.7 Fase 6: Aprobación Final del Curso', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actores: Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Asesor Metodológico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Descripción detallada:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'El Asesor Metodológico realiza una revisión final del curso completo para verificar que todos los elementos estén correctamente implementados.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actividades principales:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisar el curso completo en la plataforma', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Verificar que coincida con la caracterización', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Aprobar para publicación', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Marcar la matriz como completada', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 6. GUÍA DE USO DETALLADA ============
            new Paragraph({
                children: [new TextRun({ text: '6. GUÍA DE USO DETALLADA', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '6.1 Crear una Actividad de Caracterización', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Para crear una nueva actividad de Caracterización RED en un curso, siga estos pasos:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 1: Acceder al curso', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Ingrese al curso de Moodle donde desea agregar la actividad de caracterización.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 2: Activar edición', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Haga clic en el botón "Activar edición" o "Modo de edición" en la parte superior derecha de la página del curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 3: Agregar actividad', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En la sección donde desea agregar la actividad, haga clic en "+ Añadir una actividad o recurso".', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 4: Seleccionar Caracterización RED', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En el selector de actividades, busque y seleccione "Caracterización RED".', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 5: Configurar la actividad', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Complete los campos del formulario:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Nombre: Ingrese un nombre descriptivo (ej: "Caracterización - Curso de Programación")', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Descripción: Opcionalmente, agregue una descripción de la actividad', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 6: Guardar', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Haga clic en "Guardar cambios y mostrar" para crear la actividad y acceder a ella inmediatamente.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '6.2 Crear una Nueva Matriz de Caracterización', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Una vez dentro de la actividad de Caracterización RED, puede crear una nueva matriz:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 1: Acceder a la actividad', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Haga clic en la actividad de Caracterización RED desde la página del curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 2: Nueva matriz', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Haga clic en el botón "Nueva Matriz de Caracterización". Este botón solo aparece si tiene el permiso para crear matrices.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 3: Sección "Información del Curso"', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Complete los siguientes campos obligatorios:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Programa Académico: Nombre del programa (ej: "Ingeniería de Sistemas")', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Nombre del Curso: Nombre completo del curso a caracterizar', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 4: Sección "Asignación de Roles"', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Seleccione los usuarios que participarán en cada rol del proceso. El sistema muestra todos los usuarios matriculados en el curso:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Asesor Metodológico: Seleccione el diseñador instruccional responsable', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Experto Disciplinar: Seleccione al docente experto en el área', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Revisor Curricular: Seleccione al revisor del currículo', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Par Académico: Seleccione al par evaluador', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Corrector de Estilo: Seleccione al corrector de textos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Coordinación de Producción: Seleccione al coordinador del equipo de diseño', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Jefe de Medios: Seleccione al jefe del área de medios educativos (recibirá notificaciones)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Producción: Seleccione al diseñador gráfico/multimedia', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Alistamiento: Seleccione al profesional de alistamiento', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 5: Sección "Recursos Generales del Curso"', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Marque los recursos generales que incluirá el curso:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Curso Virtual Portable (CVP): Paquete descargable del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Sala para Clases Virtuales: Espacio para sesiones sincrónicas', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Video de Bienvenida: Presentación inicial del curso', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Foro del Curso: Espacio de comunicación general', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Mapa del Curso: Navegación visual del contenido', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Para cada recurso marcado, puede agregar observaciones específicas.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '6.3 Gestionar Unidades y Temas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'La sección de Unidades permite definir la estructura completa del curso:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Agregar una Unidad:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '1. Haga clic en el botón "Agregar Unidad"', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Ingrese el nombre de la unidad en el campo correspondiente', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. La unidad se numerará automáticamente', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Agregar un Tema:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '1. Dentro de cada unidad, haga clic en "Agregar Tema"', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Ingrese el nombre del tema', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. El tema se numerará automáticamente (ej: 1.1, 1.2, etc.)', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Eliminar Unidades o Temas:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Use los botones de eliminar (icono de papelera) junto a cada unidad o tema. Esta acción eliminará también todos los recursos asociados.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '6.4 Agregar Recursos Educativos', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Para cada tema, puede especificar los recursos educativos necesarios:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 1: Agregar recurso', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Dentro de un tema, haga clic en "Agregar Recurso". Aparecerá una nueva fila en la tabla.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 2: Seleccionar tipo de recurso', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En la columna "Tipo de Recurso", seleccione la categoría:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos Educativos Digitales', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos Interactivos Digitales', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos Evaluativos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos Colaborativos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Recursos Externos', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 3: Seleccionar recurso específico', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'La columna "Recurso" mostrará las opciones disponibles según el tipo seleccionado.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 4: Ingresar título/ítem', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En la columna "Ítem/Título", ingrese el nombre o título del recurso específico.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Paso 5: Agregar contenido/observaciones', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En la columna "Observaciones/Contenido", ingrese:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Guiones para videos', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Descripciones detalladas', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Contenido textual', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Instrucciones para el equipo de producción', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '6.5 Gestionar Fases', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Una vez creada la matriz, puede gestionar las fases desde la vista de la matriz:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Ver estado de fases:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'En la sección "Progreso de Fases" verá una barra de progreso y acordeones para cada fase con su estado actual.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Estados posibles:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Pendiente (gris): La fase aún no ha iniciado', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• En Revisión (azul): La fase está activa y siendo trabajada', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Aprobada (verde): La fase fue aprobada exitosamente', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Rechazada (rojo): La fase fue rechazada y requiere correcciones', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Acciones disponibles:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Comentar: Agregar observaciones a la fase (disponible para actores de la fase)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Aprobar: Aprobar la fase y avanzar a la siguiente (solo aprobadores)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Rechazar: Rechazar la fase para correcciones (solo aprobadores)', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Reenviar: Reenviar una fase rechazada para nueva revisión', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 7. CATÁLOGO DE RECURSOS ============
            new Paragraph({
                children: [new TextRun({ text: '7. CATÁLOGO DE RECURSOS EDUCATIVOS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '7.1 Recursos Educativos Digitales', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Recurso', 'Descripción'], true),
                    createRow(['E-book', 'Libro digital interactivo con contenido del tema']),
                    createRow(['Video Clase', 'Grabación de clase magistral del docente']),
                    createRow(['Podcast', 'Audio educativo para consumo flexible']),
                    createRow(['Comic Virtual', 'Historieta digital con contenido educativo']),
                    createRow(['Paso a Paso', 'Tutorial secuencial de un procedimiento']),
                    createRow(['Línea de Tiempo', 'Representación cronológica de eventos']),
                    createRow(['Infografía', 'Gráfico informativo visual']),
                    createRow(['Mapa Conceptual', 'Diagrama de conceptos y relaciones']),
                    createRow(['Mapa Mental', 'Diagrama radial de ideas']),
                    createRow(['Video Interactivo', 'Video con elementos clickeables']),
                    createRow(['Video con Diapositivas', 'Presentación narrada en video']),
                    createRow(['Video Explicativo', 'Video animado explicativo']),
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '7.2 Recursos Interactivos Digitales', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Recurso', 'Descripción'], true),
                    createRow(['Hotspots', 'Imagen con puntos interactivos clickeables']),
                    createRow(['Emparejamiento', 'Actividad de relacionar elementos']),
                    createRow(['Arrastra las Palabras', 'Completar texto arrastrando palabras']),
                    createRow(['Crucigrama', 'Juego de palabras cruzadas']),
                    createRow(['Ordena los Párrafos', 'Organizar texto en orden correcto']),
                    createRow(['Sopa de Letras', 'Buscar palabras en matriz de letras']),
                    createRow(['Glosario Interactivo', 'Diccionario de términos del curso']),
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '7.3 Recursos Evaluativos', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Recurso', 'Descripción'], true),
                    createRow(['Opción Única', 'Pregunta con una sola respuesta correcta']),
                    createRow(['Opción Múltiple', 'Pregunta con varias respuestas correctas']),
                    createRow(['Verdadero o Falso', 'Evaluación de afirmaciones']),
                    createRow(['Marca las Palabras', 'Identificar palabras en un texto']),
                    createRow(['Espacios en Blanco', 'Completar texto con palabras faltantes']),
                    createRow(['Dictado', 'Transcripción de audio']),
                    createRow(['Tarjeta Didáctica', 'Flashcards para repaso']),
                    createRow(['Tarjetas de Diálogo', 'Simulación de conversaciones']),
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '7.4 Recursos Colaborativos', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Recurso', 'Descripción'], true),
                    createRow(['Wiki', 'Documento colaborativo editable']),
                    createRow(['Tarea', 'Actividad de entrega individual o grupal']),
                    createRow(['Lección', 'Contenido con navegación condicional']),
                    createRow(['Foro Temático', 'Discusión sobre tema específico']),
                    createRow(['Foro Social', 'Espacio de interacción informal']),
                ]
            }),
            new Paragraph({ children: [] }),

            new Paragraph({
                children: [new TextRun({ text: '7.5 Recursos Externos', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Recurso', 'Descripción'], true),
                    createRow(['Video Conferencias', 'Sesiones sincrónicas por video']),
                    createRow(['Paquetes', 'Contenido SCORM o xAPI externo']),
                    createRow(['Plataformas Externas', 'Enlaces a recursos de terceros']),
                ]
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 8. SISTEMA DE NOTIFICACIONES ============
            new Paragraph({
                children: [new TextRun({ text: '8. SISTEMA DE NOTIFICACIONES', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: 'El plugin incluye un sistema de notificaciones automáticas que informa a los usuarios relevantes cuando ocurren eventos importantes en el flujo de trabajo.',
                    ...normalStyle
                })]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Eventos que generan notificaciones:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Evento', 'Notificados', 'Contenido'], true),
                    createRow(['Fase 1 aprobada', 'Revisor Curricular', 'Invitación a iniciar revisión curricular']),
                    createRow(['Fase 2 aprobada', 'Par Académico, Corrector', 'Invitación a revisión de pares y estilo']),
                    createRow(['Fase 3 aprobada', 'Coord. Producción', 'Matriz lista para producción']),
                    createRow(['Fase 4 aprobada', 'Alistamiento', 'Recursos listos para alistamiento']),
                    createRow(['Fase 5 aprobada', 'Asesor Metodológico', 'Curso listo para aprobación final']),
                    createRow(['Fase rechazada', 'Actores de la fase', 'Solicitud de correcciones']),
                    createRow(['Fase reenviada', 'Aprobador', 'Fase lista para nueva revisión']),
                ]
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Canales de notificación:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Notificaciones en Moodle: Aparecen en el menú de notificaciones del usuario', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Correo electrónico: Se envían según la configuración del usuario', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Configurar preferencias de notificación:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Los usuarios pueden configurar sus preferencias en: Perfil → Preferencias → Preferencias de notificación → Caracterización RED', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 9. PREGUNTAS FRECUENTES ============
            new Paragraph({
                children: [new TextRun({ text: '9. PREGUNTAS FRECUENTES', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Por qué no puedo ver el botón "Nueva Matriz"?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Este botón solo aparece si tiene el permiso mod/caracterizacion:crearmatriz. Este permiso está asignado a los roles de Experto Disciplinar y Asesor Metodológico. Verifique que tenga asignado uno de estos roles en el curso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Por qué no puedo aprobar una fase?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Solo el rol designado como "aprobador" de cada fase puede aprobarla. Verifique que tenga el rol correcto y que la fase anterior esté aprobada (excepto la fase 1).', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Cómo puedo ver todas las matrices del sistema?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Necesita el permiso mod/caracterizacion:vertodasmatrices. Este permiso está asignado a los roles de Asesor Metodológico, Coordinación de Producción y Jefe de Medios.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Qué pasa si se rechaza una fase?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Cuando una fase es rechazada, los actores de esa fase reciben una notificación. Deben realizar los ajustes solicitados y usar el botón "Reenviar a Revisión" para enviar nuevamente la fase para aprobación.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Se pueden eliminar matrices?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Sí, pero solo usuarios con el permiso mod/caracterizacion:eliminarmatriz pueden hacerlo. Esta acción es irreversible y eliminará todos los datos asociados (roles, recursos, fases, comentarios).', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Puedo modificar una matriz después de que ha avanzado de fase?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: Sí, los usuarios con permiso de edición pueden modificar la matriz en cualquier momento. Sin embargo, se recomienda hacer modificaciones solo en las fases iniciales para mantener la integridad del proceso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'P: ¿Cómo sé cuántos recursos tiene una matriz?', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'R: En la vista de la matriz, la sección "Resumen de Recursos" muestra el conteo por tipo de recurso y el total general.', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 10. SOLUCIÓN DE PROBLEMAS ============
            new Paragraph({
                children: [new TextRun({ text: '10. SOLUCIÓN DE PROBLEMAS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Problema: El formulario de unidades/temas no carga correctamente', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Solución:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '1. Purgue la caché de Moodle: Administración del sitio → Desarrollo → Purgar todas las cachés', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Limpie la caché del navegador y recargue la página', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. Verifique que JavaScript esté habilitado en el navegador', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Problema: No recibo notificaciones del plugin', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Solución:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '1. Verifique sus preferencias de notificación en el perfil', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Confirme que el cron de Moodle está ejecutándose correctamente', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. Revise la configuración de correo del servidor', ...normalStyle })] }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Problema: Error "Capability was not found"', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: 'Solución:', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '1. Vaya a Administración del sitio → Notificaciones', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '2. Ejecute las actualizaciones pendientes', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '3. Purgue todas las cachés', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 11. GLOSARIO ============
            new Paragraph({
                children: [new TextRun({ text: '11. GLOSARIO', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),

            new Paragraph({ children: [new TextRun({ text: 'Caracterización: Proceso de definición detallada de los elementos y recursos que conformarán un curso virtual.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Matriz de Caracterización: Documento estructurado que contiene toda la información del curso a producir.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'RED (Recurso Educativo Digital): Material educativo en formato digital diseñado para facilitar el aprendizaje.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Fase: Etapa del proceso de producción con actores y responsabilidades específicas.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Actor: Usuario que participa en una fase del proceso.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Aprobador: Usuario con permiso para aprobar o rechazar una fase.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'CVP (Curso Virtual Portable): Paquete descargable del curso para uso offline.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'SCORM: Estándar para contenido de e-learning interoperable.', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Capability (Permiso): Autorización específica para realizar acciones en Moodle.', ...normalStyle })] }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 12. SOPORTE TÉCNICO ============
            new Paragraph({
                children: [new TextRun({ text: '12. SOPORTE TÉCNICO', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Para obtener soporte técnico relacionado con el plugin Caracterización RED, puede utilizar los siguientes canales:', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Información del Desarrollador:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Desarrollador: Alonso Arias', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Empresa: OrionCloud', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Email: soporte@orioncloud.com.co', ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [new TextRun({ text: 'Información del Plugin:', bold: true, ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Nombre: mod_caracterizacion', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: '• Versión: 1.1.2', ...normalStyle })] }),
            new Paragraph({ children: [new TextRun({ text: `• Fecha del manual: ${dateStr}`, ...normalStyle })] }),
            new Paragraph({ children: [] }),
            new Paragraph({ children: [] }),
            new Paragraph({
                children: [new TextRun({
                    text: '© 2025 Universidad de Santander - UDES. Todos los derechos reservados.',
                    bold: true, size: 20, font: FONT_FAMILY
                })],
                alignment: AlignmentType.CENTER
            }),
        ]
    }]
});

// Guardar documento
Packer.toBuffer(doc).then((buffer) => {
    fs.writeFileSync('/home/user/Moodle_Dev/mod/caracterizacion/docs/MANUAL_CARACTERIZACION_RED_UDES.docx', buffer);
    console.log('Manual generado exitosamente: MANUAL_CARACTERIZACION_RED_UDES.docx');
});
