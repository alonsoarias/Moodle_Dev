const { Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell, WidthType, BorderStyle, AlignmentType, HeadingLevel, PageBreak, Header, Footer } = require('docx');
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
const boldStyle = { size: 22, bold: true, font: FONT_FAMILY };

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

// Función para crear párrafo normal
function p(text, style = normalStyle) {
    return new Paragraph({ children: [new TextRun({ text, ...style })] });
}

// Función para crear párrafo con viñeta
function bullet(text) {
    return new Paragraph({ children: [new TextRun({ text: '• ' + text, ...normalStyle })] });
}

// Función para crear párrafo vacío
function empty() {
    return new Paragraph({ children: [] });
}

// Crear documento
const doc = new Document({
    sections: [{
        properties: {},
        headers: {
            default: new Header({
                children: [new Paragraph({
                    children: [new TextRun({ text: 'Manual de Usuario - Plugin UDESBOT | Universidad de Santander', size: 18, color: '7f8c8d', font: FONT_FAMILY })],
                    alignment: AlignmentType.RIGHT
                })]
            })
        },
        footers: {
            default: new Footer({
                children: [new Paragraph({
                    children: [new TextRun({ text: `© 2026 Universidad de Santander - UDES | OrionCloud`, size: 18, font: FONT_FAMILY })],
                    alignment: AlignmentType.CENTER
                })]
            })
        },
        children: [
            // ============ PORTADA ============
            empty(), empty(), empty(),
            new Paragraph({
                children: [new TextRun({ text: 'MANUAL DE USUARIO', ...titleStyle, size: 56 })],
                alignment: AlignmentType.CENTER
            }),
            empty(),
            new Paragraph({
                children: [new TextRun({ text: 'UDESBOT', bold: true, size: 52, color: '2874a6', font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            empty(),
            new Paragraph({
                children: [new TextRun({ text: 'Chatbot Inteligente para Moodle', size: 32, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            empty(),
            new Paragraph({
                children: [new TextRun({ text: 'Asistente Virtual Automatizado para la Plataforma UDES Virtual', size: 24, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            empty(), empty(),
            new Paragraph({
                children: [new TextRun({ text: 'Universidad de Santander - UDES', bold: true, size: 32, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            empty(), empty(), empty(),
            new Paragraph({
                children: [new TextRun({ text: `Versión: 3.8.2`, size: 24, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({
                children: [new TextRun({ text: `Fecha: ${dateStr}`, size: 24, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({
                children: [new TextRun({ text: 'Desarrollado por: Alonso Arias - OrionCloud', size: 24, font: FONT_FAMILY })],
                alignment: AlignmentType.CENTER
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ TABLA DE CONTENIDOS ============
            new Paragraph({
                children: [new TextRun({ text: 'TABLA DE CONTENIDOS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('1. Introducción'),
            p('   1.1 ¿Qué es UDESBOT?'),
            p('   1.2 Beneficios del Chatbot'),
            p('   1.3 Características Principales'),
            p('   1.4 Compatibilidad'),
            p('2. Requisitos del Sistema'),
            p('3. Instalación del Plugin'),
            p('   3.1 Pasos de Instalación'),
            p('   3.2 Verificación de la Instalación'),
            p('   3.3 Activación del Widget'),
            p('4. Configuración General'),
            p('   4.1 Configuración del Bot'),
            p('   4.2 Personalización Visual'),
            p('   4.3 Configuración de Mascotas'),
            p('   4.4 Configuración de Idioma'),
            p('   4.5 Historial de Conversaciones'),
            p('   4.6 Tiempo de Inactividad'),
            p('5. Gestión de Reglas'),
            p('   5.1 ¿Qué es una Regla?'),
            p('   5.2 Crear una Nueva Regla'),
            p('   5.3 Patrones y Palabras Clave'),
            p('   5.4 Respuestas Dinámicas'),
            p('   5.5 Restricciones por Rol'),
            p('   5.6 Restricciones por Curso'),
            p('   5.7 Contexto de la Regla'),
            p('6. Gestión de Categorías'),
            p('   6.1 Crear Categorías'),
            p('   6.2 Organizar Reglas'),
            p('7. Opciones de Respuesta Rápida'),
            p('   7.1 ¿Qué son las Opciones?'),
            p('   7.2 Configurar Opciones'),
            p('8. Atajos Dinámicos (Shortcuts)'),
            p('   8.1 Tipos de Atajos Disponibles'),
            p('   8.2 Atajos para Estudiantes'),
            p('   8.3 Atajos para Profesores'),
            p('   8.4 Atajos para Administradores'),
            p('   8.5 Configurar Atajos Personalizados'),
            p('9. Temas y Personalización Visual'),
            p('   9.1 Crear un Tema'),
            p('   9.2 Configurar Colores'),
            p('   9.3 Iconos del Widget'),
            p('   9.4 Mascotas Animadas'),
            p('10. Sistema de Placeholders'),
            p('   10.1 Lista de Placeholders'),
            p('   10.2 Uso en Respuestas'),
            p('11. Algoritmo de Coincidencia'),
            p('   11.1 Sistema de Puntuación'),
            p('   11.2 Umbral de Confianza'),
            p('12. Importar y Exportar'),
            p('   12.1 Exportar Base de Conocimiento'),
            p('   12.2 Importar Reglas'),
            p('   12.3 Formato JSON'),
            p('   12.4 Formato CSV'),
            p('13. Reportes y Analíticas'),
            p('   13.1 Panel de Reportes'),
            p('   13.2 Métricas Disponibles'),
            p('   13.3 Filtros de Búsqueda'),
            p('14. Sistema de Retroalimentación'),
            p('   14.1 Feedback del Usuario'),
            p('   14.2 Mejorar Reglas con Feedback'),
            p('15. Privacidad y GDPR'),
            p('   15.1 Datos Almacenados'),
            p('   15.2 Exportar Datos de Usuario'),
            p('   15.3 Eliminar Datos de Usuario'),
            p('16. Guía del Usuario Final'),
            p('   16.1 Usar el Chatbot'),
            p('   16.2 Funciones Disponibles'),
            p('   16.3 Exportar Conversación'),
            p('17. Solución de Problemas'),
            p('18. Preguntas Frecuentes'),
            p('19. Glosario'),
            p('20. Soporte Técnico'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 1. INTRODUCCIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '1. INTRODUCCIÓN', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '1.1 ¿Qué es UDESBOT?', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('UDESBOT es un plugin de chatbot inteligente y completo diseñado específicamente para la plataforma Moodle de la Universidad de Santander (UDES). Este asistente virtual proporciona soporte automatizado a estudiantes, profesores y administradores, respondiendo preguntas frecuentes, proporcionando información del curso y facilitando la navegación dentro de la plataforma UDES Virtual.'),
            empty(),
            p('El chatbot utiliza un sofisticado sistema de coincidencia de patrones y palabras clave con puntuación de confianza para proporcionar respuestas precisas y contextuales. Además, integra funcionalidades dinámicas que permiten acceder a información en tiempo real de Moodle, como calificaciones, tareas pendientes, eventos del calendario y más.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '1.2 Beneficios del Chatbot', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('La implementación de UDESBOT ofrece múltiples beneficios para la comunidad universitaria:', boldStyle),
            empty(),
            bullet('Disponibilidad 24/7: El chatbot está disponible en todo momento para responder consultas, sin importar la hora o el día.'),
            empty(),
            bullet('Reducción de Carga de Soporte: Automatiza respuestas a preguntas frecuentes, reduciendo la carga del equipo de soporte técnico y académico.'),
            empty(),
            bullet('Respuestas Instantáneas: Los usuarios obtienen respuestas inmediatas sin necesidad de esperar atención humana.'),
            empty(),
            bullet('Información Personalizada: Proporciona información específica del usuario como sus calificaciones, tareas pendientes y eventos próximos.'),
            empty(),
            bullet('Mejora la Experiencia del Usuario: Facilita la navegación y el acceso a recursos dentro de la plataforma Moodle.'),
            empty(),
            bullet('Multiidioma: Soporta español e inglés con detección automática del idioma del usuario.'),
            empty(),
            bullet('Adaptable por Roles: Ofrece respuestas y funcionalidades específicas según el rol del usuario (estudiante, profesor, administrador).'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '1.3 Características Principales', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('UDESBOT incluye las siguientes características avanzadas:', boldStyle),
            empty(),
            bullet('Sistema de Reglas Inteligente: Motor de coincidencia avanzado con patrones, palabras clave y puntuación de confianza.'),
            empty(),
            bullet('Widget de Chat Interactivo: Interfaz flotante y responsiva con indicadores de escritura, marcas de tiempo y botones de retroalimentación.'),
            empty(),
            bullet('Atajos Dinámicos: Acceso rápido a información de Moodle como tareas, calificaciones, calendario, mensajes y más.'),
            empty(),
            bullet('Mascotas Animadas: Personajes visuales animados (Clippy, Robot, Búho, Gato, Bombilla) que hacen la interacción más amigable.'),
            empty(),
            bullet('Temas Personalizables: Sistema completo de personalización visual con colores, iconos y estilos.'),
            empty(),
            bullet('Sistema de Retroalimentación: Los usuarios pueden calificar las respuestas para mejorar continuamente la base de conocimiento.'),
            empty(),
            bullet('Historial de Conversaciones: Almacenamiento persistente con opciones de retención configurables.'),
            empty(),
            bullet('Exportación de Conversaciones: Los usuarios pueden descargar sus conversaciones en formato de texto.'),
            empty(),
            bullet('Cumplimiento GDPR: Funcionalidad completa de exportación y eliminación de datos de usuario.'),
            empty(),
            bullet('Importación/Exportación: Soporte para JSON y CSV para gestionar la base de conocimiento.'),
            empty(),
            bullet('Reportes y Analíticas: Panel completo con métricas de uso, preguntas sin respuesta y tasa de éxito.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '1.4 Compatibilidad', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Componente', 'Versión Soportada'], true),
                    createRow(['Moodle', '4.0, 4.1, 4.2, 4.3, 4.4, 4.5']),
                    createRow(['PHP', '7.4 o superior (recomendado 8.1+)']),
                    createRow(['Navegadores', 'Chrome, Firefox, Edge, Safari (versiones actuales)']),
                    createRow(['Dispositivos', 'Desktop, Tablet, Móvil (diseño responsivo)']),
                ]
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 2. REQUISITOS DEL SISTEMA ============
            new Paragraph({
                children: [new TextRun({ text: '2. REQUISITOS DEL SISTEMA', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Para instalar y ejecutar correctamente el plugin UDESBOT, su sistema debe cumplir con los siguientes requisitos:'),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Componente', 'Requisito Mínimo', 'Recomendado'], true),
                    createRow(['Moodle', '4.0 (build 2022041900)', '4.1 LTS o superior']),
                    createRow(['PHP', '7.4', '8.1 o superior']),
                    createRow(['MySQL/MariaDB', '5.7 / 10.2', '8.0 / 10.6']),
                    createRow(['PostgreSQL', '10', '14 o superior']),
                    createRow(['Memoria PHP', '128 MB', '256 MB o más']),
                    createRow(['JavaScript', 'ES6+', 'Navegador actualizado']),
                    createRow(['AJAX', 'Habilitado', 'Requerido']),
                ]
            }),
            empty(),
            p('Requisitos adicionales:', boldStyle),
            empty(),
            bullet('El servidor web debe permitir solicitudes AJAX.'),
            bullet('Los usuarios deben tener JavaScript habilitado en sus navegadores.'),
            bullet('Se recomienda HTTPS para una comunicación segura.'),
            bullet('El cron de Moodle debe estar configurado para tareas programadas (limpieza de historial).'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 3. INSTALACIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '3. INSTALACIÓN DEL PLUGIN', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '3.1 Pasos de Instalación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Siga estos pasos detallados para instalar el plugin UDESBOT en su plataforma Moodle:'),
            empty(),
            p('Paso 1: Obtener el Plugin', boldStyle),
            p('Descargue el archivo del plugin desde el repositorio oficial o la fuente proporcionada. El archivo debe contener la carpeta "udesbot" con todos los archivos del plugin.'),
            empty(),
            p('Paso 2: Subir al Servidor', boldStyle),
            p('Copie la carpeta "udesbot" completa al directorio /local/ de su instalación de Moodle. La estructura final debe ser: /local/udesbot/'),
            empty(),
            p('Paso 3: Verificar Permisos', boldStyle),
            p('Asegúrese de que los archivos tengan los permisos correctos de lectura para el servidor web. Típicamente: archivos 644 y directorios 755.'),
            empty(),
            p('Paso 4: Iniciar Sesión como Administrador', boldStyle),
            p('Acceda a su plataforma Moodle con una cuenta de administrador del sitio.'),
            empty(),
            p('Paso 5: Ejecutar la Instalación', boldStyle),
            p('Navegue a: Administración del sitio → Notificaciones. Moodle detectará automáticamente el nuevo plugin y mostrará la pantalla de actualización.'),
            empty(),
            p('Paso 6: Actualizar Base de Datos', boldStyle),
            p('Haga clic en "Actualizar base de datos de Moodle ahora". El sistema creará automáticamente las 8 tablas necesarias para el funcionamiento del chatbot.'),
            empty(),
            p('Paso 7: Configuración Inicial', boldStyle),
            p('Después de la instalación, será redirigido a la página de configuración del plugin donde podrá personalizar el comportamiento del chatbot.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '3.2 Verificación de la Instalación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Para verificar que la instalación se completó correctamente:'),
            empty(),
            bullet('Vaya a Administración del sitio → Plugins → Vista general de plugins'),
            bullet('Busque "udesbot" en la sección de plugins locales'),
            bullet('Verifique que la versión mostrada sea 3.8.2'),
            bullet('Confirme que el estado sea "Habilitado"'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '3.3 Activación del Widget', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El widget del chatbot se activa automáticamente después de la instalación. Para verificar:'),
            empty(),
            bullet('Cierre sesión y vuelva a iniciar sesión'),
            bullet('Debería ver el icono del chatbot en la esquina inferior derecha de la pantalla'),
            bullet('Haga clic en el icono para abrir el chat'),
            bullet('Si no aparece, verifique la configuración en Administración del sitio → Plugins → Plugins locales → UDESBOT'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 4. CONFIGURACIÓN GENERAL ============
            new Paragraph({
                children: [new TextRun({ text: '4. CONFIGURACIÓN GENERAL', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('La configuración del plugin se encuentra en: Administración del sitio → Plugins → Plugins locales → UDESBOT → Configuración'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.1 Configuración del Bot', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Opción', 'Descripción', 'Valor Predeterminado'], true),
                    createRow(['Widget Habilitado', 'Activa o desactiva el widget del chatbot en todas las páginas', 'Sí']),
                    createRow(['Nombre del Bot', 'Nombre que se muestra en el encabezado del chat', 'UDES Bot']),
                    createRow(['Etiqueta del Widget', 'Texto que aparece junto al icono del widget', 'Chat Bot']),
                    createRow(['Saludo Inicial', 'Mensaje de bienvenida cuando se abre el chat', 'Hola {{username}}, ¿en qué puedo ayudarte?']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.2 Personalización Visual', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Opción', 'Descripción', 'Valor Predeterminado'], true),
                    createRow(['Color Primario', 'Color principal del widget y encabezado', '#0f6fc5 (azul Moodle)']),
                    createRow(['Tipo de Icono', 'Estilo del icono del widget (predeterminado, emoji, Bootstrap, personalizado)', 'Predeterminado']),
                    createRow(['Emoji del Icono', 'Si selecciona emoji, especifique cuál usar', '💬']),
                    createRow(['Icono Bootstrap', 'Si selecciona Bootstrap, elija el icono de la biblioteca', 'chat-dots']),
                    createRow(['Imagen Personalizada', 'Suba una imagen para usar como icono del widget', '-']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.3 Configuración de Mascotas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Las mascotas son personajes animados que aparecen junto al chat para hacer la interacción más amigable:'),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Mascota', 'Descripción'], true),
                    createRow(['Ninguna', 'Sin mascota animada']),
                    createRow(['Clippy', 'El clásico asistente de Office con estilo retro']),
                    createRow(['Robot', 'Un robot amigable y moderno']),
                    createRow(['Búho', 'Un búho sabio que representa conocimiento']),
                    createRow(['Gato', 'Un gato estudioso y curioso']),
                    createRow(['Bombilla', 'Una bombilla que representa ideas y ayuda']),
                    createRow(['Personalizada', 'Suba su propia imagen de mascota']),
                ]
            }),
            empty(),
            p('Estados de la mascota:', boldStyle),
            bullet('Idle: Estado de espera normal'),
            bullet('Thinking: Cuando el bot está procesando la pregunta'),
            bullet('Talking: Cuando el bot muestra la respuesta'),
            bullet('Error: Cuando ocurre un error en la comunicación'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.4 Configuración de Idioma', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            bullet('Detección Automática de Idioma: Cuando está habilitada, el bot detecta automáticamente el idioma del usuario y responde en ese idioma (si hay reglas disponibles).'),
            bullet('Idioma de la Regla: Cada regla puede tener un idioma específico asignado.'),
            bullet('Reglas Padre: Las reglas pueden tener traducciones vinculadas mediante el sistema de reglas padre.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.5 Historial de Conversaciones', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Opción', 'Descripción'], true),
                    createRow(['Guardar Historial', 'Activa el almacenamiento del historial de conversaciones']),
                    createRow(['Período de Retención', 'Tiempo que se mantienen las conversaciones antes de eliminarlas automáticamente']),
                ]
            }),
            empty(),
            p('Opciones de retención disponibles:', boldStyle),
            bullet('Para siempre: Las conversaciones nunca se eliminan'),
            bullet('1 semana: Se eliminan después de 7 días'),
            bullet('1 mes: Se eliminan después de 30 días'),
            bullet('3 meses: Se eliminan después de 90 días (predeterminado)'),
            bullet('6 meses: Se eliminan después de 180 días'),
            bullet('1 año: Se eliminan después de 365 días'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '4.6 Tiempo de Inactividad', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El sistema puede cerrar automáticamente el chat después de un período de inactividad:'),
            empty(),
            bullet('Tiempo de Inactividad: Tiempo en milisegundos antes de mostrar advertencia (predeterminado: 600000 = 10 minutos)'),
            bullet('Antes de cerrar, se muestra un mensaje de advertencia al usuario'),
            bullet('El usuario puede hacer clic en "Mantener abierto" para continuar la conversación'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 5. GESTIÓN DE REGLAS ============
            new Paragraph({
                children: [new TextRun({ text: '5. GESTIÓN DE REGLAS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Las reglas son el corazón del chatbot. Definen qué preguntas puede responder y cómo lo hace.'),
            empty(),
            p('Acceda a la gestión de reglas desde: Administración del sitio → Plugins → Plugins locales → UDESBOT → Gestionar Reglas'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.1 ¿Qué es una Regla?', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Una regla es una unidad de conocimiento que contiene:'),
            empty(),
            bullet('Patrón: La pregunta o frase principal que activa la regla'),
            bullet('Palabras Clave: Términos adicionales que pueden activar la regla'),
            bullet('Respuesta: El texto que el bot enviará al usuario'),
            bullet('Categoría: Clasificación para organizar las reglas'),
            bullet('Etiquetas: Marcadores adicionales para búsqueda y filtrado'),
            bullet('Restricciones: Limitaciones por rol, curso o contexto'),
            bullet('Opciones: Botones de respuesta rápida relacionados'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.2 Crear una Nueva Regla', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Paso 1: Acceder al Formulario', boldStyle),
            p('Haga clic en el botón "Agregar Regla" en la página de gestión de reglas.'),
            empty(),
            p('Paso 2: Información Básica', boldStyle),
            bullet('Patrón: Ingrese la pregunta principal (ej: "¿Cómo accedo a mis calificaciones?")'),
            bullet('Palabras Clave: Agregue términos separados por coma (ej: "notas, calificaciones, ver notas")'),
            bullet('Respuesta: Escriba la respuesta completa que el bot debe dar'),
            empty(),
            p('Paso 3: Clasificación', boldStyle),
            bullet('Seleccione una categoría para organizar la regla'),
            bullet('Agregue etiquetas relevantes para facilitar la búsqueda'),
            empty(),
            p('Paso 4: Configuración Avanzada', boldStyle),
            bullet('Active "Respuesta Dinámica" si usa placeholders'),
            bullet('Configure restricciones de rol si la regla es específica'),
            bullet('Seleccione el contexto requerido (sitio, curso, actividad)'),
            empty(),
            p('Paso 5: Guardar', boldStyle),
            p('Haga clic en "Guardar cambios" para crear la regla.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.3 Patrones y Palabras Clave', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El Patrón:', boldStyle),
            bullet('Es la forma principal de la pregunta'),
            bullet('Debe ser representativo de cómo los usuarios hacen la pregunta'),
            bullet('El sistema busca coincidencias exactas y parciales'),
            empty(),
            p('Las Palabras Clave:', boldStyle),
            bullet('Son términos alternativos que pueden activar la regla'),
            bullet('Se separan por coma'),
            bullet('Cada palabra clave coincidente suma puntos a la confianza'),
            bullet('Incluya sinónimos, abreviaciones y variantes comunes'),
            empty(),
            p('Ejemplo:', boldStyle),
            p('Patrón: "¿Cómo veo mis calificaciones?"'),
            p('Palabras Clave: notas, calificaciones, ver notas, consultar notas, mis notas, grade, grades'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.4 Respuestas Dinámicas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Las respuestas dinámicas permiten incluir información personalizada del usuario usando placeholders (marcadores de posición).'),
            empty(),
            p('Para activar respuestas dinámicas:', boldStyle),
            bullet('Marque la casilla "Respuesta Dinámica" al crear/editar la regla'),
            bullet('Use los placeholders disponibles en el texto de la respuesta'),
            bullet('El sistema reemplazará automáticamente los placeholders con datos reales'),
            empty(),
            p('Ejemplo de respuesta dinámica:', italicStyle),
            p('"Hola {{userfirstname}}, tu calificación actual en {{coursename}} es {{grade}}. Tienes {{pendingassignments}} tareas pendientes."'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.5 Restricciones por Rol', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Puede limitar qué usuarios ven ciertas reglas basándose en su rol:'),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Arquetipo de Rol', 'Descripción'], true),
                    createRow(['student', 'Estudiantes matriculados en cursos']),
                    createRow(['teacher', 'Profesores sin edición']),
                    createRow(['editingteacher', 'Profesores con edición']),
                    createRow(['coursecreator', 'Creadores de cursos']),
                    createRow(['manager', 'Administradores/Gestores']),
                    createRow(['guest', 'Usuarios invitados']),
                    createRow(['user', 'Usuarios autenticados (cualquier rol)']),
                ]
            }),
            empty(),
            p('Si no selecciona ningún rol, la regla estará disponible para todos.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.6 Restricciones por Curso', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Puede limitar reglas a cursos específicos:'),
            empty(),
            bullet('Seleccione los cursos donde la regla debe estar disponible'),
            bullet('Si no selecciona ninguno, la regla está disponible en todos los cursos'),
            bullet('Útil para reglas con información específica de un curso'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '5.7 Contexto de la Regla', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El contexto determina dónde debe estar el usuario para ver la regla:'),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Contexto', 'Descripción'], true),
                    createRow(['Cualquier Contexto', 'La regla está disponible en cualquier página']),
                    createRow(['Contexto del Sitio', 'Solo disponible en páginas del sitio (no dentro de cursos)']),
                    createRow(['Contexto del Curso', 'Solo disponible cuando el usuario está dentro de un curso']),
                    createRow(['Contexto de Actividad', 'Solo disponible cuando el usuario está en una actividad específica']),
                ]
            }),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 6. GESTIÓN DE CATEGORÍAS ============
            new Paragraph({
                children: [new TextRun({ text: '6. GESTIÓN DE CATEGORÍAS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Las categorías permiten organizar las reglas de manera jerárquica para facilitar la administración.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '6.1 Crear Categorías', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Administración del sitio → Plugins → Plugins locales → UDESBOT → Gestionar Categorías'),
            empty(),
            p('Campos de la categoría:', boldStyle),
            bullet('Nombre: Nombre descriptivo de la categoría'),
            bullet('Descripción: Explicación del propósito de la categoría'),
            bullet('Categoría Padre: Permite crear jerarquías (subcategorías)'),
            bullet('Orden: Posición en la lista de categorías'),
            bullet('Habilitada: Si la categoría y sus reglas están activas'),
            empty(),
            p('Ejemplos de categorías:', boldStyle),
            bullet('General: Navegación, inicio de sesión, menús'),
            bullet('Cursos: Inscripción, navegación de cursos, información'),
            bullet('Actividades: Tareas, foros, cuestionarios, lecciones'),
            bullet('Calificaciones: Notas, progreso, retroalimentación'),
            bullet('Mensajes: Mensajería, notificaciones'),
            bullet('Profesores: Temas específicos para docentes'),
            bullet('Administración: Herramientas administrativas'),
            bullet('Calendario: Eventos, fechas límite'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '6.2 Organizar Reglas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Para asignar una regla a una categoría:'),
            empty(),
            bullet('Al crear/editar una regla, seleccione la categoría en el campo correspondiente'),
            bullet('Use el filtro de categorías en la página de gestión para ver reglas por categoría'),
            bullet('Las reglas sin categoría aparecen en "Sin categorizar"'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 7. OPCIONES DE RESPUESTA RÁPIDA ============
            new Paragraph({
                children: [new TextRun({ text: '7. OPCIONES DE RESPUESTA RÁPIDA', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '7.1 ¿Qué son las Opciones?', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Las opciones de respuesta rápida son botones que aparecen después de una respuesta del bot, permitiendo al usuario continuar la conversación con un solo clic.'),
            empty(),
            p('Beneficios:', boldStyle),
            bullet('Guían al usuario hacia temas relacionados'),
            bullet('Reducen la necesidad de escribir'),
            bullet('Mejoran la experiencia de usuario'),
            bullet('Facilitan la navegación por la base de conocimiento'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '7.2 Configurar Opciones', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Gestionar Reglas → Seleccione una regla → Gestionar Opciones'),
            empty(),
            p('Campos de la opción:', boldStyle),
            bullet('Texto: El texto que aparece en el botón'),
            bullet('Regla Destino: La regla que se activará al hacer clic'),
            bullet('Icono: Icono opcional que acompaña al texto'),
            bullet('Orden: Posición del botón entre las opciones'),
            bullet('Habilitada: Si la opción está activa'),
            empty(),
            p('Ejemplo:', boldStyle),
            p('Para una regla sobre calificaciones, podría agregar opciones como:'),
            bullet('"Ver mis tareas pendientes" → Vinculada a regla de tareas'),
            bullet('"Contactar al profesor" → Vinculada a regla de mensajes'),
            bullet('"Ver calendario" → Vinculada a regla de eventos'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 8. ATAJOS DINÁMICOS ============
            new Paragraph({
                children: [new TextRun({ text: '8. ATAJOS DINÁMICOS (SHORTCUTS)', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Los atajos dinámicos son comandos especiales que ejecutan consultas en tiempo real a Moodle para obtener información actualizada del usuario.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '8.1 Tipos de Atajos Disponibles', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El plugin incluye atajos predefinidos para diferentes tipos de usuarios:'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '8.2 Atajos para Estudiantes', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Atajo', 'Descripción', 'Ejemplo de Pregunta'], true),
                    createRow(['Tareas', 'Lista de tareas pendientes', '¿Cuáles son mis tareas pendientes?']),
                    createRow(['Calificaciones', 'Notas del curso actual', '¿Cuáles son mis calificaciones?']),
                    createRow(['Calendario', 'Próximos eventos y fechas', '¿Qué eventos tengo esta semana?']),
                    createRow(['Mensajes', 'Mensajes recientes', '¿Tengo mensajes nuevos?']),
                    createRow(['Profesores', 'Lista de profesores del curso', '¿Quién es mi profesor?']),
                    createRow(['Curso', 'Información del curso actual', '¿Cuál es la información del curso?']),
                    createRow(['Progreso', 'Progreso de completitud', '¿Cuál es mi progreso?']),
                    createRow(['Cursos', 'Cursos matriculados', '¿En qué cursos estoy inscrito?']),
                    createRow(['Participantes', 'Compañeros del curso', '¿Quiénes son mis compañeros?']),
                    createRow(['Insignias', 'Insignias obtenidas', '¿Qué insignias he ganado?']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '8.3 Atajos para Profesores', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Atajo', 'Descripción'], true),
                    createRow(['Calificaciones del Curso', 'Acceso rápido al libro de calificaciones']),
                    createRow(['Configuración del Libro', 'Configuración del gradebook']),
                    createRow(['Importar Calificaciones', 'Herramienta de importación de notas']),
                    createRow(['Exportar Calificaciones', 'Herramienta de exportación de notas']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '8.4 Atajos para Administradores', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Atajo', 'Descripción'], true),
                    createRow(['Gestión de Usuarios', 'Administración de usuarios del sitio']),
                    createRow(['Gestión de Cursos', 'Administración de cursos']),
                    createRow(['Reportes', 'Acceso a reportes del sitio']),
                    createRow(['Configuración', 'Configuración general del sitio']),
                    createRow(['Plugins', 'Gestión de plugins instalados']),
                    createRow(['Seguridad', 'Configuración de seguridad']),
                    createRow(['Respaldos', 'Herramientas de backup']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '8.5 Configurar Atajos Personalizados', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Administración del sitio → Plugins → Plugins locales → UDESBOT → Gestionar Atajos'),
            empty(),
            p('Campos del atajo:', boldStyle),
            bullet('Nombre: Nombre descriptivo del atajo'),
            bullet('Palabras Clave: Términos que activan el atajo'),
            bullet('Tipo de Acción: El tipo de datos que recupera'),
            bullet('Descripción: Explicación del propósito'),
            bullet('Icono: Icono visual del atajo'),
            bullet('Roles: Roles que pueden usar el atajo'),
            bullet('Orden: Posición en la lista'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 9. TEMAS Y PERSONALIZACIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '9. TEMAS Y PERSONALIZACIÓN VISUAL', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '9.1 Crear un Tema', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Administración del sitio → Plugins → Plugins locales → UDESBOT → Gestionar Temas'),
            empty(),
            p('Haga clic en "Agregar Tema" y configure:'),
            empty(),
            bullet('Nombre del Tema: Identificador descriptivo'),
            bullet('Colores: Personalice cada elemento del widget'),
            bullet('Icono: Tipo y configuración del icono'),
            bullet('Mascota: Selección y configuración de mascota'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '9.2 Configurar Colores', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Color', 'Descripción', 'Formato'], true),
                    createRow(['Color Primario', 'Encabezado y elementos principales', '#0f6fc5']),
                    createRow(['Color Secundario', 'Elementos de acento', '#5a6268']),
                    createRow(['Color de Texto', 'Texto general del widget', '#333333']),
                    createRow(['Color de Fondo', 'Fondo del área de chat', '#ffffff']),
                    createRow(['Color del Usuario', 'Burbujas de mensajes del usuario', '#e3f2fd']),
                    createRow(['Color del Bot', 'Burbujas de mensajes del bot', '#f5f5f5']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '9.3 Iconos del Widget', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Tipo', 'Descripción'], true),
                    createRow(['Predeterminado', 'Icono SVG incluido con el plugin']),
                    createRow(['Emoji', 'Use cualquier emoji como icono (ej: 💬, 🤖, 📚)']),
                    createRow(['Bootstrap Icons', 'Seleccione de 100+ iconos de Bootstrap']),
                    createRow(['Personalizado', 'Suba su propia imagen (PNG, SVG, JPG)']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '9.4 Mascotas Animadas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Las mascotas pueden mostrar sugerencias contextuales y mensajes de ayuda:'),
            empty(),
            bullet('Mensaje de Saludo: Aparece cuando se abre el chat'),
            bullet('Sugerencias de Temas: Basadas en el rol del usuario'),
            bullet('Mensajes de Error: Cuando no se encuentra respuesta'),
            bullet('Preguntas Populares: Sugiere las preguntas más frecuentes'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 10. SISTEMA DE PLACEHOLDERS ============
            new Paragraph({
                children: [new TextRun({ text: '10. SISTEMA DE PLACEHOLDERS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Los placeholders son marcadores que se reemplazan automáticamente con datos reales del usuario o del contexto.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '10.1 Lista de Placeholders', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Placeholder', 'Descripción', 'Ejemplo de Resultado'], true),
                    createRow(['{{username}}', 'Nombre de usuario completo', 'Juan Pérez']),
                    createRow(['{{userfirstname}}', 'Primer nombre del usuario', 'Juan']),
                    createRow(['{{coursename}}', 'Nombre del curso actual', 'Programación Web']),
                    createRow(['{{courseshortname}}', 'Nombre corto del curso', 'PROGWEB']),
                    createRow(['{{teacher}}', 'Nombre del profesor del curso', 'María García']),
                    createRow(['{{grade}}', 'Calificación actual del usuario', '85.5%']),
                    createRow(['{{pendingassignments}}', 'Número de tareas pendientes', '3']),
                    createRow(['{{nextevent}}', 'Próximo evento del calendario', 'Examen - 15 Feb']),
                    createRow(['{{botname}}', 'Nombre del chatbot', 'UDES Bot']),
                    createRow(['{{site.name}}', 'Nombre del sitio Moodle', 'UDES Virtual']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '10.2 Uso en Respuestas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Ejemplo de respuesta con placeholders:', boldStyle),
            empty(),
            p('"¡Hola {{userfirstname}}! 👋'),
            p(''),
            p('Aquí tienes un resumen de tu situación en {{coursename}}:'),
            p(''),
            p('📊 Tu calificación actual: {{grade}}'),
            p('📝 Tareas pendientes: {{pendingassignments}}'),
            p('📅 Próximo evento: {{nextevent}}'),
            p(''),
            p('Tu profesor es {{teacher}}. Si tienes dudas, no dudes en contactarlo."'),
            empty(),
            p('Nota: Si un placeholder no tiene valor disponible (ej: no hay profesor asignado), el sistema mostrará un mensaje alternativo como "No asignado" o "No disponible".', italicStyle),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 11. ALGORITMO DE COINCIDENCIA ============
            new Paragraph({
                children: [new TextRun({ text: '11. ALGORITMO DE COINCIDENCIA', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('UDESBOT utiliza un sofisticado algoritmo de coincidencia para encontrar la mejor respuesta a cada pregunta.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '11.1 Sistema de Puntuación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('El algoritmo calcula una puntuación de confianza para cada regla:'),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Tipo de Coincidencia', 'Puntos', 'Descripción'], true),
                    createRow(['Coincidencia Exacta', '+100', 'La pregunta coincide exactamente con el patrón']),
                    createRow(['Patrón Contenido', '+50', 'El patrón está contenido en la pregunta']),
                    createRow(['Pregunta Contenida', '+40', 'La pregunta está contenida en el patrón']),
                    createRow(['Palabras Comunes', '+30', 'Palabras compartidas entre pregunta y patrón']),
                    createRow(['Palabra Clave', '+20', 'Por cada palabra clave que coincide']),
                    createRow(['Acción Transitiva', '+35', 'Verbos de acción relacionados']),
                    createRow(['Orden de Frase', '+32', 'Las palabras están en el orden correcto']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '11.2 Umbral de Confianza', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            bullet('Umbral Mínimo: 0.25 (25%) - Por debajo de este valor, se considera "sin respuesta"'),
            bullet('Umbral Alto: 0.70 (70%) - Por encima de este valor, la coincidencia es muy confiable'),
            empty(),
            p('Cuando ninguna regla supera el umbral mínimo, el bot:'),
            bullet('Muestra un mensaje de "no entendí"'),
            bullet('Sugiere preguntas similares o populares'),
            bullet('Registra la pregunta como "sin coincidencia" para análisis'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 12. IMPORTAR Y EXPORTAR ============
            new Paragraph({
                children: [new TextRun({ text: '12. IMPORTAR Y EXPORTAR', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '12.1 Exportar Base de Conocimiento', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Administración del sitio → Plugins → Plugins locales → UDESBOT → Importar/Exportar'),
            empty(),
            p('Opciones de exportación:', boldStyle),
            bullet('Formato JSON: Incluye todas las reglas con estructura completa'),
            bullet('Formato CSV: Formato tabular para edición en hojas de cálculo'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '12.2 Importar Reglas', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Opciones de importación:', boldStyle),
            bullet('Importación Completa: Importa reglas, categorías y configuración'),
            bullet('Solo Reglas: Mantiene categorías existentes, solo agrega reglas'),
            bullet('Limpiar Existentes: Elimina datos actuales antes de importar'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '12.3 Formato JSON', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Estructura del archivo JSON:', boldStyle),
            p('{'),
            p('  "version": "3.8.2",'),
            p('  "categories": [...],'),
            p('  "rules": ['),
            p('    {'),
            p('      "pattern": "¿Cómo veo mis calificaciones?",'),
            p('      "keywords": "notas,calificaciones,grades",'),
            p('      "response": "Para ver tus calificaciones...",'),
            p('      "category": "Calificaciones",'),
            p('      "enabled": true'),
            p('    }'),
            p('  ]'),
            p('}'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '12.4 Formato CSV', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Columnas del archivo CSV:', boldStyle),
            p('pattern,keywords,response,category,tags,enabled,roles,courses'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 13. REPORTES Y ANALÍTICAS ============
            new Paragraph({
                children: [new TextRun({ text: '13. REPORTES Y ANALÍTICAS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '13.1 Panel de Reportes', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Acceda a: Administración del sitio → Plugins → Plugins locales → UDESBOT → Reportes'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '13.2 Métricas Disponibles', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Métrica', 'Descripción'], true),
                    createRow(['Total de Conversaciones', 'Número total de interacciones registradas']),
                    createRow(['Preguntas Coincidentes', 'Preguntas que encontraron una regla']),
                    createRow(['Preguntas Sin Coincidencia', 'Preguntas que no encontraron respuesta']),
                    createRow(['Tasa de Éxito', 'Porcentaje de coincidencias vs total']),
                    createRow(['Usuarios Únicos', 'Número de usuarios diferentes que usaron el bot']),
                    createRow(['Confianza Promedio', 'Promedio de puntuación de confianza']),
                    createRow(['Preguntas Más Frecuentes', 'Top de preguntas más realizadas']),
                    createRow(['Preguntas Sin Regla', 'Preguntas frecuentes sin respuesta configurada']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '13.3 Filtros de Búsqueda', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            bullet('Rango de Fechas: Filtre por período específico'),
            bullet('Estado de Coincidencia: Solo coincidentes, solo sin coincidencia, o todos'),
            bullet('Búsqueda de Texto: Busque preguntas específicas'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 14. SISTEMA DE RETROALIMENTACIÓN ============
            new Paragraph({
                children: [new TextRun({ text: '14. SISTEMA DE RETROALIMENTACIÓN', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '14.1 Feedback del Usuario', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Cada respuesta del bot incluye botones de retroalimentación:'),
            empty(),
            bullet('👍 Útil: El usuario encontró la respuesta útil'),
            bullet('👎 No Útil: La respuesta no resolvió la consulta'),
            empty(),
            p('El feedback se almacena por regla y se usa para:'),
            bullet('Identificar reglas que necesitan mejora'),
            bullet('Priorizar reglas con alta calificación'),
            bullet('Detectar respuestas problemáticas'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '14.2 Mejorar Reglas con Feedback', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('En la gestión de reglas puede ver:'),
            bullet('Contador de "Útil" por regla'),
            bullet('Contador de "No útil" por regla'),
            bullet('Tasa de satisfacción calculada'),
            empty(),
            p('Reglas con bajo índice de satisfacción deben ser revisadas y mejoradas.'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 15. PRIVACIDAD Y GDPR ============
            new Paragraph({
                children: [new TextRun({ text: '15. PRIVACIDAD Y GDPR', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('UDESBOT cumple completamente con el Reglamento General de Protección de Datos (GDPR).'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '15.1 Datos Almacenados', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            new Table({
                width: { size: 100, type: WidthType.PERCENTAGE },
                rows: [
                    createRow(['Dato', 'Propósito', 'Retención'], true),
                    createRow(['ID de Usuario', 'Identificar conversaciones', 'Configurable']),
                    createRow(['Pregunta', 'Historial de consultas', 'Configurable']),
                    createRow(['Respuesta', 'Historial de respuestas', 'Configurable']),
                    createRow(['ID de Regla', 'Vincular con regla coincidente', 'Configurable']),
                    createRow(['Confianza', 'Calidad de la coincidencia', 'Configurable']),
                    createRow(['Timestamp', 'Fecha y hora de la consulta', 'Configurable']),
                    createRow(['Feedback', 'Calificación de utilidad', '24 horas único']),
                ]
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '15.2 Exportar Datos de Usuario', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Los usuarios pueden solicitar una exportación de sus datos a través de:'),
            bullet('Perfil → Privacidad y políticas → Exportar mis datos'),
            bullet('El sistema incluirá todas las conversaciones del chatbot'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '15.3 Eliminar Datos de Usuario', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Los usuarios pueden solicitar eliminación de sus datos:'),
            bullet('Perfil → Privacidad y políticas → Eliminar mis datos'),
            bullet('Se eliminarán todas las conversaciones y feedback del usuario'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 16. GUÍA DEL USUARIO FINAL ============
            new Paragraph({
                children: [new TextRun({ text: '16. GUÍA DEL USUARIO FINAL', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '16.1 Usar el Chatbot', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Paso 1: Abrir el Chat', boldStyle),
            p('Haga clic en el icono del chatbot en la esquina inferior derecha de cualquier página.'),
            empty(),
            p('Paso 2: Escribir su Pregunta', boldStyle),
            p('Escriba su pregunta en el campo de texto y presione Enter o haga clic en el botón de enviar.'),
            empty(),
            p('Paso 3: Recibir la Respuesta', boldStyle),
            p('El bot procesará su pregunta y mostrará la respuesta. Puede ver un indicador de "escribiendo..." mientras procesa.'),
            empty(),
            p('Paso 4: Usar Opciones Rápidas', boldStyle),
            p('Si aparecen botones de opciones, haga clic en ellos para continuar la conversación.'),
            empty(),
            p('Paso 5: Dar Retroalimentación', boldStyle),
            p('Use los botones 👍 o 👎 para indicar si la respuesta fue útil.'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '16.2 Funciones Disponibles', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            bullet('Borrar Historial: Botón en el encabezado para limpiar la conversación'),
            bullet('Exportar: Descargue la conversación como archivo de texto'),
            bullet('Cerrar: Haga clic fuera del widget o en el botón X'),
            bullet('Atajos: Escriba comandos como "mis tareas" o "mis notas" para información rápida'),
            empty(),

            new Paragraph({
                children: [new TextRun({ text: '16.3 Exportar Conversación', ...heading2Style })],
                heading: HeadingLevel.HEADING_2
            }),
            empty(),
            p('Para exportar su conversación:'),
            bullet('Haga clic en el icono de exportar en el encabezado del chat'),
            bullet('Se descargará un archivo .txt con el historial completo'),
            bullet('El archivo incluye fecha, hora y todos los mensajes'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 17. SOLUCIÓN DE PROBLEMAS ============
            new Paragraph({
                children: [new TextRun({ text: '17. SOLUCIÓN DE PROBLEMAS', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            p('Problema: El widget no aparece', boldStyle),
            p('Solución:'),
            bullet('Verifique que el widget esté habilitado en la configuración'),
            bullet('Limpie la caché del navegador'),
            bullet('Verifique que JavaScript esté habilitado'),
            bullet('Purgue las cachés de Moodle'),
            empty(),

            p('Problema: El bot no responde', boldStyle),
            p('Solución:'),
            bullet('Verifique la conectividad a Internet'),
            bullet('Revise la consola del navegador para errores JavaScript'),
            bullet('Confirme que el servicio AJAX esté funcionando'),
            bullet('Verifique los permisos del usuario'),
            empty(),

            p('Problema: El bot siempre dice que no entiende', boldStyle),
            p('Solución:'),
            bullet('Revise que existan reglas habilitadas'),
            bullet('Verifique que las reglas no tengan restricciones de rol/curso'),
            bullet('Agregue más palabras clave a las reglas'),
            bullet('Revise los reportes para ver preguntas sin coincidencia'),
            empty(),

            p('Problema: Los placeholders no se reemplazan', boldStyle),
            p('Solución:'),
            bullet('Verifique que "Respuesta Dinámica" esté activada en la regla'),
            bullet('Confirme que el placeholder esté escrito correctamente'),
            bullet('Verifique que haya datos disponibles (ej: estar en un curso)'),
            empty(),

            p('Problema: La mascota no aparece', boldStyle),
            p('Solución:'),
            bullet('Verifique que la mascota esté habilitada en configuración'),
            bullet('Confirme que el tipo de mascota esté seleccionado'),
            bullet('Si usa mascota personalizada, verifique que la imagen se haya subido'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 18. PREGUNTAS FRECUENTES ============
            new Paragraph({
                children: [new TextRun({ text: '18. PREGUNTAS FRECUENTES', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            p('P: ¿Cuántas reglas puede manejar el chatbot?', boldStyle),
            p('R: No hay límite técnico. El plugin incluye más de 200 reglas predefinidas y puede agregar tantas como necesite.'),
            empty(),

            p('P: ¿El chatbot funciona en dispositivos móviles?', boldStyle),
            p('R: Sí, el widget es completamente responsivo y funciona en todos los dispositivos.'),
            empty(),

            p('P: ¿Puedo personalizar los mensajes de error?', boldStyle),
            p('R: Sí, los mensajes se pueden modificar en los archivos de idioma del plugin.'),
            empty(),

            p('P: ¿El chatbot puede responder en múltiples idiomas?', boldStyle),
            p('R: Sí, soporta español e inglés con detección automática. Puede agregar reglas en ambos idiomas.'),
            empty(),

            p('P: ¿Cómo sé qué preguntas hacen los usuarios que no tienen respuesta?', boldStyle),
            p('R: Use la sección de Reportes → Preguntas Sin Regla para ver las consultas más frecuentes sin coincidencia.'),
            empty(),

            p('P: ¿Puedo desactivar el chatbot temporalmente?', boldStyle),
            p('R: Sí, desactive la opción "Widget Habilitado" en la configuración.'),
            empty(),

            p('P: ¿Las conversaciones son privadas?', boldStyle),
            p('R: Las conversaciones se almacenan en la base de datos de Moodle y son accesibles solo para administradores. Los usuarios pueden exportar y eliminar sus propios datos.'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 19. GLOSARIO ============
            new Paragraph({
                children: [new TextRun({ text: '19. GLOSARIO', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),

            p('AJAX: Técnica de desarrollo web para comunicación asíncrona con el servidor sin recargar la página.'),
            empty(),
            p('Arquetipo de Rol: Clasificación base de roles en Moodle (student, teacher, manager, etc.).'),
            empty(),
            p('Atajo (Shortcut): Comando especial que ejecuta consultas dinámicas a Moodle.'),
            empty(),
            p('Chatbot: Programa de inteligencia artificial diseñado para simular conversaciones.'),
            empty(),
            p('Confianza: Puntuación que indica qué tan bien coincide una pregunta con una regla.'),
            empty(),
            p('GDPR: Reglamento General de Protección de Datos de la Unión Europea.'),
            empty(),
            p('Mascota: Personaje animado que acompaña al chatbot.'),
            empty(),
            p('Patrón: Frase principal que define cómo se activa una regla.'),
            empty(),
            p('Placeholder: Marcador de posición que se reemplaza con datos dinámicos.'),
            empty(),
            p('Regla: Unidad de conocimiento que define una pregunta y su respuesta.'),
            empty(),
            p('Widget: Componente de interfaz de usuario flotante del chatbot.'),
            new Paragraph({ children: [new PageBreak()] }),

            // ============ 20. SOPORTE TÉCNICO ============
            new Paragraph({
                children: [new TextRun({ text: '20. SOPORTE TÉCNICO', ...heading1Style })],
                heading: HeadingLevel.HEADING_1
            }),
            empty(),
            p('Para obtener soporte técnico relacionado con el plugin UDESBOT:'),
            empty(),
            p('Información del Desarrollador:', boldStyle),
            bullet('Desarrollador: Alonso Arias'),
            bullet('Empresa: OrionCloud'),
            bullet('Email: soporte@orioncloud.com.co'),
            empty(),
            p('Información del Plugin:', boldStyle),
            bullet('Nombre: local_udesbot'),
            bullet('Versión: 3.8.2 (Build 2025122211)'),
            bullet('Compatibilidad: Moodle 4.0 - 4.5'),
            bullet('Licencia: GNU GPL v3+'),
            p(`Fecha del manual: ${dateStr}`),
            empty(), empty(),
            new Paragraph({
                children: [new TextRun({
                    text: '© 2026 Universidad de Santander - UDES. Todos los derechos reservados.',
                    bold: true, size: 20, font: FONT_FAMILY
                })],
                alignment: AlignmentType.CENTER
            }),
            empty(),
            new Paragraph({
                children: [new TextRun({
                    text: 'Desarrollado por OrionCloud para la comunidad UDES desde diciembre 2025.',
                    size: 18, font: FONT_FAMILY, italics: true
                })],
                alignment: AlignmentType.CENTER
            }),
        ]
    }]
});

// Guardar documento
Packer.toBuffer(doc).then((buffer) => {
    fs.writeFileSync('/home/user/Moodle_Dev/local/udesbot/docs/MANUAL_UDESBOT_UDES.docx', buffer);
    console.log('Manual generado exitosamente: MANUAL_UDESBOT_UDES.docx');
});
