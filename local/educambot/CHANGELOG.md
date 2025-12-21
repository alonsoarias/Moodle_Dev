# Changelog

Todos los cambios notables de este proyecto seran documentados en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

## [2.2.2] - 2025-12-13

### Agregado
- **Shortcuts con descripciones**: Nuevo sistema de acciones rapidas con nombre y descripcion
  - Cada shortcut muestra icono, nombre y descripcion de lo que hace
  - Grid responsive con 2 columnas en desktop, 1 en mobile
  - Iconos de Bootstrap Icons para cada accion
  - 10 shortcuts predefinidos: tareas, calificaciones, calendario, mensajes, profesores, curso, progreso, cursos, participantes, insignias
- **Endpoint shortcuts_ajax.php**: Nuevo endpoint AJAX para cargar shortcuts dinamicamente

### Corregido
- **{{site.name}} placeholder**: Ahora funciona en TODAS las respuestas
  - Anteriormente solo funcionaba cuando `dynamicresponse = 1` en la regla
  - Ahora se procesan placeholders para todas las respuestas del bot

### Cambiado
- **shortcuts.json actualizado**: Agregados campos `description` e `icon` a cada shortcut
- **widget.js**: Nueva funcion `loadShortcuts()` para cargar y mostrar shortcuts con descripciones
- **styles.css**: Nuevos estilos para `.educambot-shortcuts-container` y componentes relacionados

## [2.2.1] - 2025-12-13

### Cambiado
- **Bordes rectos**: Eliminados border-radius del popup, header y credits
- **Mascota externa**: Reposicionada fuera del contenedor del chat (a la izquierda)
  - Ya no obstruye el contenido del chat
  - Visible solo cuando el chat esta abierto
  - Posicion adaptativa en movil (esquina inferior izquierda)
- **Estilos simplificados**: Removidos estilos comerciales del chat abierto
  - Mantenida solo la animacion del boton flotante (pulse y gradientes)
  - Restaurada la apariencia original del area de mensajes

## [2.2.0] - 2025-12-13

### Agregado
- **Apariencia comercial premium**: Rediseno completo del widget con estetica profesional
  - Botones con gradientes y animaciones modernas
  - Efecto glassmorphism en el popup
  - Sombras sofisticadas y transiciones suaves
  - Bordes redondeados premium
  - Animacion de pulso en el boton flotante
  - Mejor tipografia y espaciado
- **Nuevas mascotas**: Agregadas 2 nuevas opciones de mascota
  - `cat`: Gato estudioso
  - `lightbulb`: Bombilla de ideas
- **Nuevo tema Professional**: Tema adicional con colores indigo

### Cambiado
- **Mascotas habilitadas por defecto**: Todos los temas ahora incluyen mascota habilitada
  - Tema Default: Robot
  - Tema Nature: Buho
  - Tema Sunset: Gato
  - Tema Professional: Bombilla
- **Migracion automatica**: Temas existentes se actualizan para mostrar mascota Robot

### Mejorado
- **CSS optimizado**: Mas de 350 lineas de estilos premium agregadas
- **Formulario de temas**: Opciones de mascota cat y lightbulb disponibles
- **Experiencia de usuario**: Animaciones mas fluidas y profesionales

## [2.1.1] - 2025-12-13

### Agregado
- **Placeholder {{site.name}}**: Nuevo marcador dinamico para personalizar respuestas con el nombre del sitio Moodle
  - Implementado en `response_builder.php` usando `$SITE->fullname`
  - Documentado en README.md y en `get_available_placeholders()`
  - Reemplaza todas las referencias hardcodeadas a "Moodle 4" en la KB

### Cambiado
- **KB sin referencias a version de Moodle**: Todas las menciones a "Moodle 4" reemplazadas por `{{site.name}}`
  - Respuestas ahora muestran el nombre real del sitio en lugar de "Moodle 4"
  - Hace el chatbot mas personalizado y profesional

## [2.1.0] - 2025-12-13

### Agregado
- **Base de conocimientos expandida a 201 reglas**: Cobertura completa para todos los arquetipos
  - `courses.json`: 16 reglas sobre cursos
  - `activities.json`: 30 reglas sobre actividades (tareas, foros, cuestionarios, etc.)
  - `grades.json`: 10 reglas sobre calificaciones
  - `users.json`: 19 reglas sobre perfil y cuenta
  - `teachers.json`: 29 reglas para docentes
  - `admin.json`: 50 reglas para administradores
  - `communication.json`: 15 reglas sobre mensajeria y comunicacion
  - `technical.json`: 17 reglas sobre aspectos tecnicos
  - `resources.json`: 17 reglas sobre recursos y materiales

### Cambiado
- **Terminologia actualizada a Moodle 4.x**:
  - "Menu de navegacion izquierdo" → "Indice del curso"
  - "Panel lateral" → "Cajon de bloques"
  - "Barra de navegacion" → "Barra de navegacion superior"
  - "Menu desplegable del usuario" → "Menu de usuario"
  - Toda la KB refleja la interfaz moderna de Moodle 4.x

### Mejorado
- **Consistencia en respuestas**: Todas las reglas siguen el mismo patron de formato
- **Navegacion clara**: Instrucciones paso a paso con rutas de navegacion actualizadas

## [2.0.9] - 2025-12-13

### Corregido
- **Error completion_info**: Agregado `require_once` para `completionlib.php` en `shortcut_handler.php`
  - El shortcut de progreso del curso ahora funciona correctamente
  - Solucionado: `Class "completion_info" not found`

### Mejorado
- **Compatibilidad Bootstrap 4/5**:
  - Modal en reports.php ahora funciona con ambas versiones
  - Agregados atributos duales: `data-dismiss` y `data-bs-dismiss`
  - JavaScript detecta automaticamente version de Bootstrap
  - Clase de boton de cierre compatible: `close btn-close`

### Agregado
- **CSS Fallbacks para Bootstrap 5**: Nuevas reglas CSS para compatibilidad
  - `.badge.badge-success`, `.badge.badge-warning`, `.badge.badge-info`
  - `.badge.badge-primary`, `.badge.badge-secondary`, `.badge.badge-danger`
  - Estilos de boton de cierre compatibles

### Refactorizado
- **Arquitectura JSON para Base de Conocimientos**: Migracion completa de install.php
  - `install.php` reducido de 1,978 lineas (140KB) a 282 lineas (9KB)
  - Nueva estructura modular en `db/data/`:
    - `categories.json`: 10 categorias del sistema
    - `navigation.json`: Reglas de navegacion y menu (startup, menu, greeting, thanks, goodbye, about)
    - `shortcuts.json`: 10 atajos dinamicos (tareas, calificaciones, calendario, etc.)
    - `themes.json`: 3 temas visuales (Default, Nature, Sunset)
    - `knowledge/courses.json`: Reglas sobre cursos
    - `knowledge/activities.json`: Reglas sobre actividades (tareas, cuestionarios, foros, etc.)
    - `knowledge/grades.json`: Reglas sobre calificaciones
    - `knowledge/users.json`: Reglas sobre perfil y cuenta
    - `menus/student.json`: Menu contextual para estudiantes
    - `menus/teacher.json`: Menu contextual para docentes
    - `menus/manager.json`: Menu contextual para administradores
    - `menus/guest.json`: Menu contextual para invitados
  - Parser JSON con funciones modulares para cada tipo de dato
  - Manejo de errores con excepciones y debugging
  - Facilita mantenimiento, traducciones y versionado independiente del KB

## [2.0.8] - 2025-12-12

### Agregado
- **Expansion 30% de la base de conocimientos para todos los arquetipos**:
  - **Estudiantes (+16 reglas)**: App movil, Acceso offline, Grupos, Blog personal
    Portfolio, Evaluacion entre pares, Base de datos, Leccion, SCORM
    Marcadores, Busqueda, Accesibilidad, Consulta, Retroalimentacion, Chat, Insignias
  - **Docentes (+5 reglas)**: Gestion de grupos, Rubricas, Extensiones de plazos
    Copia de seguridad de curso, Importar contenido
  - **Administradores (+8 reglas)**: Categorias de cursos, Herramientas externas (LTI)
    Banco de contenido, Banco de preguntas, Config movil, Anuncios del sitio
    Papelera de reciclaje, Eventos del sitio
- **Opciones de respuesta rapida** para todas las nuevas reglas

### Cambiado
- Total de reglas en KB: 80+ → 115+

## [2.0.7] - 2025-12-12

### Agregado
- **Base de conocimientos ampliada para administradores**: 19 nuevas reglas para gestores del sitio
  - Backup y restauracion, Seguridad, Cache, Tareas programadas
  - Servicios web/API, Insignias, Competencias, Paquetes de idioma
  - Apariencia del sitio, Autenticacion, Privacidad/GDPR
  - Notificaciones, Limpieza, Rendimiento, Inscripciones
  - Cohortes, Roles y permisos, H5P, Learning Analytics
- **Opciones de respuesta rapida** para todas las nuevas reglas de administracion

### Eliminado
- **Tema Dark Mode**: Retirado de los temas predeterminados de install.php

### Cambiado
- Total de reglas en KB: 60+ → 80+
- Total de temas: 4 → 3 (Default, Nature, Sunset)

## [2.0.6] - 2025-12-12

### Corregido
- **Strings de mascota faltantes**: Agregadas cadenas de saludo por rol a `strings_for_js()`
  - `mascot_greeting_student`, `mascot_greeting_teacher`, `mascot_greeting_editingteacher`
  - `mascot_greeting_coursecreator`, `mascot_greeting_manager`, `mascot_greeting_guest`, `mascot_greeting_user`
  - Estas cadenas se usaban en widget.js pero no se cargaban via JavaScript

## [2.0.5] - 2025-12-12

### Cambiado
- **Headers de copyright unificados**: Todos los archivos PHP y JS ahora tienen el header correcto
  - @author: Alonso Arias <soporte@ingeweb.co>
  - @copyright: 2025 Ingeweb <https://ingeweb.co>
- **Verificacion de compatibilidad**: Confirmada compatibilidad con Moodle 4.0-4.5 y PHP 8.x

### Verificado
- **APIs externas**: Compatibilidad dual Moodle 4.2+ (namespaced) y <4.2 (legacy)
- **Base de datos**: Esquema XMLDB compatible con MySQL, PostgreSQL, Oracle
- **PHP 8.x**: Sin funciones deprecated, sin errores de tipo

## [2.0.4] - 2025-12-12

### Agregado
- **Creditos de Ingeweb**: Elemento visible en el widget que indica "Desarrollado por Ingeweb"
  - Link a https://ingeweb.co
  - Estilos discretos pero visibles

### Cambiado
- **Documentacion de archivos**: Actualizados headers segun estandar Moodle
  - Autor: Alonso Arias <soporte@ingeweb.co>
  - Copyright: 2025 Ingeweb <https://ingeweb.co>
- **Nombre del plugin**: Cambiado de "Nexo Bot" a "EducamBot"

## [2.0.3] - 2025-12-12

### Agregado
- **Persistencia de conversacion entre paginas**: La conversacion ahora persiste al navegar entre diferentes paginas del sitio
  - Guarda mensajes en localStorage con clave unica por usuario
  - Restaura automaticamente la conversacion al cargar cualquier pagina
  - Limita a ultimos 50 mensajes para evitar problemas de almacenamiento
  - Expira automaticamente despues de 24 horas de inactividad
- **Sincronizacion entre pestanas**: Los mensajes nuevos se sincronizan automaticamente entre pestanas abiertas
  - Usa el evento `storage` para detectar cambios en tiempo real
  - Reproduce sonido de notificacion cuando llega un mensaje en otra pestana
  - Limpia la conversacion en todas las pestanas cuando se borra en una

## [2.0.2] - 2025-12-12

### Corregido
- **Cadenas de idioma en JavaScript**: Agregado `strings_for_js()` en lib.php para cargar todas las cadenas necesarias por el widget
  - Corrige error `[[mascot_needmore,local_educambot]]` y similares que aparecian en la interfaz
  - Incluye 47 cadenas: mensajes de error, exportacion, feedback, mascota y sugerencias

## [2.0.1] - 2025-12-12

### Agregado
- **Handlers de shortcuts**: Implementados handlers faltantes para actiontypes `courses`, `participants`, `badges`
  - `get_courses_response()`: Lista cursos matriculados del usuario
  - `get_participants_response()`: Muestra participantes del curso actual
  - `get_badges_response()`: Muestra insignias obtenidas del usuario
- **Strings de idioma**: Agregadas 13 nuevas cadenas para shortcuts en ES y EN

### Corregido
- **Mascota desaparece al hacer scroll**: Movida fuera del area de mensajes al contenedor popup
- **Posicionamiento de mascota**: Actualizado CSS con z-index correcto y posicion absoluta relativa al popup
- **Overflow del popup**: Cambiado a `visible` para permitir que el tooltip de mascota se muestre correctamente
- **Padding de mensajes**: Agregado espacio inferior para evitar que mensajes queden detras de la mascota
- **Error "Acceso rapido desconocido"**: Resuelto implementando los handlers faltantes en shortcut_handler.php

## [2.0.0] - 2025-12-12

### Agregado
- **Indicador de escritura**: Animacion de tres puntos que rebota mientras el bot procesa la respuesta
- **Timestamps en mensajes**: Cada mensaje muestra la hora (HH:MM) en formato local
- **Sistema de feedback**: Botones thumbs up/down en respuestas del bot
  - Persistencia en servidor con tabla `local_educambot_feedback`
  - Contadores `helpfulcount` y `nothelpfulcount` en tabla de reglas
  - Mensaje de agradecimiento al enviar feedback
- **Exportar conversacion**: Boton para descargar la conversacion como archivo .txt
  - Incluye timestamps, nombres de usuario/bot
  - Formato legible con encabezado y pie de pagina
- **Notificaciones de sonido**: Sonido sutil al recibir respuesta del bot
  - Usa Web Audio API (sin archivos externos)
  - Configurable desde la administracion
- **Indicador de estado online**: Punto verde animado con efecto pulse en el header
- **Estilos de contenido enriquecido**: Soporte para listas, enlaces, codigo, negritas, cursivas
- **Configuraciones v2.0.0**:
  - `enablefeedback`: Habilitar/deshabilitar sistema de feedback
  - `enablesound`: Habilitar/deshabilitar sonidos de notificacion
- **Endpoint feedback.php**: AJAX para enviar feedback de usuarios
- **Tabla local_educambot_feedback**: Almacena feedback de usuarios por regla
- **Migracion db/upgrade.php**: Para actualizaciones desde versiones anteriores

### Cambiado
- Widget.js actualizado a v2.0.0 con nuevas funcionalidades (~280 lineas nuevas)
- Template mustache con indicador de escritura y boton de exportar
- Estilos CSS extendidos (+270 lineas) para nuevos componentes
- Footer de mensajes unificado con timestamp, confianza y acciones

### Corregido
- Opciones de respuesta rapida: Ya no se deshabilitan permanentemente
- Boton limpiar chat: Ahora recarga correctamente las opciones de inicio
- Base de conocimientos extendida con ~50 reglas y 10 categorias

## [1.9.6] - 2025-12-05

### Corregido
- **history.php**: Orden corregido de `require_login()` y `require_sesskey()` para evitar errores de sesion
- **startup.php**: Uso de `$DB->sql_compare_text()` para compatibilidad con todos los motores de base de datos (Oracle, PostgreSQL)
- **widget.mustache**: Corregida variable `data-userrole` que referenciaba `{{userrole}}` inexistente (ahora usa `{{userrolearchetype}}`)
- **option_form.php**: Corregido `addHelpButton()` que referenciaba elemento incorrecto (`targetrule` -> `targetruleid`)

## [1.9.5] - 2025-12-05

### Agregado
- **Retencion de historial**: Nueva configuracion para definir cuanto tiempo conservar los logs de conversaciones
  - Opciones: Para siempre, 1 semana, 1 mes, 3 meses, 6 meses, 1 año
- **Tarea programada de limpieza**: `cleanup_history` se ejecuta diariamente a las 3:00 AM
  - Elimina automaticamente registros antiguos segun la configuracion de retencion

### Cambiado
- Saludo por defecto vuelve a usar `{{userfirstname}}` en lugar de `{{fullname}}`

## [1.9.4] - 2025-12-05

### Agregado
- **Marcadores de nombre**: `{{fullname}}` y `{{username}}` disponibles para mensajes de saludo
- Los enlaces de shortcuts ahora se abren en nueva pestana (`target="_blank"`)
- Indicadores visuales en enlaces directos (iconos y flecha ↗)

### Corregido
- Formulario de temas: Los valores de icono (emoji, Font Awesome) y mascota ahora se restauran correctamente al editar
- Metodo `set_data()` en theme_form para cargar archivos existentes en draft area

## [1.9.3] - 2025-12-05

### Corregido
- **CRITICO**: El filtrado por arquetipos de rol ahora funciona correctamente
  - `engine.php`: Nuevo metodo `get_user_archetypes()` que obtiene arquetipos reales de Moodle
  - Antes usaba `$role->shortname` (personalizable) en lugar de `$role->archetype` (fijo)
  - El filtrado por arquetipos NUNCA funcionaba en versiones anteriores
- `service.php`: El engine ahora recibe `$courseid` y `$userid` correctamente
- `entry_form.php`: Selector multi-select para arquetipos con conversion automatica

### Agregado
- Metodos `set_data()` y `get_data()` en entry_form para conversion array<->string
- Cadenas de idioma para arquetipos en espanol e ingles

## [1.9.2] - 2025-12-05

### Agregado
- Base de conocimientos ampliada para docentes y gestores
- 2 nuevas categorias: "Docentes y Gestion", "Administracion"
- 15 nuevas reglas especificas para arquetipos teacher, editingteacher y manager
- Migracion en `upgrade.php` para agregar contenido automaticamente

## [1.9.1] - 2025-12-05

### Cambiado
- Comportamiento del widget basado en arquetipos de rol (no shortnames)
- Saludos personalizados por arquetipo: student, teacher, editingteacher, coursecreator, manager, guest, user
- Sugerencias contextuales segun el arquetipo del usuario

### Corregido
- Correccion de temas visuales del widget

## [1.9.0] - 2025-12-05

### Agregado
- **Persistencia de conversaciones**: Historial guardado en localStorage
- **Timeout por inactividad**: Cierre automatico configurable (default: 10 min)
- **Privacidad GDPR**: Provider completo para exportacion/eliminacion de datos
- Cadenas de idioma para nuevas funcionalidades

### Cambiado
- Comportamiento del bot adaptado segun rol del usuario

## [1.8.1] - 2025-12-04

### Agregado
- **Personalizacion de icono del widget**: default, emoji, Font Awesome, imagen personalizada
- **Sistema de mascotas animadas**: Clippy, Robot, Buho academico, SVG personalizado
- Mensajes contextuales de la mascota con sugerencias
- Animaciones CSS para estados de la mascota (idle, thinking, talking, error)

## [1.8.0] - 2025-12-04

### Agregado
- **Gestion de temas visuales**: Colores personalizables (primario, secundario, texto, fondo, burbujas)
- **Horarios de disponibilidad**: Configurar dias/horas en que el bot esta activo
- **Soporte multi-idioma**: Campo `lang` en reglas, auto-deteccion de idioma del usuario
- **Traducciones vinculadas**: Campo `langparent` para relacionar reglas traducidas
- **Restricciones avanzadas**: Filtrar reglas por roles y cursos especificos
- Seccion de configuracion de idioma en settings

## [1.7.0] - 2025-12-03

### Agregado
- **Accesos rapidos (Shortcuts)**: Comandos que muestran datos dinamicos de Moodle
  - Tareas pendientes del curso
  - Calificaciones del usuario
  - Eventos del calendario (proximos 7 dias)
  - Mensajes recientes
  - Profesores del curso
  - Informacion del curso
  - Progreso en el curso
- **Respuestas dinamicas**: Marcadores reemplazados con datos reales del contexto
- **Reglas sensibles al contexto**: Requieren estar en curso/actividad especifica
- Tabla `local_educambot_shortcut` para gestion de accesos rapidos
- Clases: `context_handler`, `shortcut_handler`, `response_builder`

## [1.6.1] - 2025-12-02

### Agregado
- Preguntas sugeridas al abrir el chat
- Opcion "Otra pregunta" para escribir libremente
- Categoria especial "Recursos y Materiales"

## [1.6.0] - 2025-12-02

### Agregado
- **Sistema de duplicacion de reglas**: Copiar reglas existentes con sus opciones
- **Busqueda avanzada de reglas**: Filtrar por patron, tags, categoria
- Archivo `duplicate_rule.php` para gestion de duplicados

## [1.5.0] - 2025-12-01

### Agregado
- **Importar/Exportar base de conocimiento**: Formato JSON
- Opcion para limpiar datos existentes antes de importar
- Validacion de version del archivo de importacion
- Estadisticas de importacion (categorias, reglas, opciones)

## [1.4.0] - 2025-11-30

### Agregado
- **Sistema de etiquetas (tags)**: Organizar y buscar reglas por tags
- Filtrado por tags en la lista de reglas
- Campo `tags` en tabla `local_educambot_rule`

## [1.3.0] - 2025-11-29

### Agregado
- **Sistema de categorias jerarquicas**: Organizar reglas en categorias/subcategorias
- Tabla `local_educambot_category` con soporte para anidamiento
- Filtrado de reglas por categoria
- Campo `categoryid` en reglas

## [1.2.0] - 2025-11-28

### Agregado
- **Opciones de respuesta rapida (Quick Replies)**: Botones clickeables despues de respuestas
- Tabla `local_educambot_option` para gestion de opciones
- Vinculacion de opciones a reglas destino
- Iconos personalizables para opciones

## [1.1.0] - 2025-11-27

### Agregado
- **Sistema de reportes**: Dashboard con estadisticas de uso
- Total de conversaciones, preguntas respondidas/sin respuesta
- Tasa de exito y confianza promedio
- Preguntas frecuentes y preguntas sin regla
- Tabla `local_educambot_log` para registro de conversaciones

## [1.0.0] - 2025-11-26

### Agregado
- **Widget de chat visual**: Interfaz flotante en todas las paginas
- Diseno responsivo con animaciones
- Indicador de escritura del bot
- Boton para limpiar historial
- Integracion con estilos de Moodle

## [0.9.0] - 2025-11-25

### Agregado
- Release inicial - Fase 1
- Sistema de reglas basico con patron + palabras clave
- Endpoint AJAX `service.php` para procesar preguntas
- Panel de administracion para gestionar reglas
- Algoritmo de matching con puntuacion
- Permisos: `local/educambot:use` y `local/educambot:manage`
- Tabla `local_educambot_rule` con campos basicos

---

## Tipos de cambios

- `Agregado` para funcionalidades nuevas.
- `Cambiado` para cambios en funcionalidades existentes.
- `Obsoleto` para funcionalidades que seran eliminadas proximamente.
- `Eliminado` para funcionalidades eliminadas.
- `Corregido` para correcciones de bugs.
- `Seguridad` en caso de vulnerabilidades.
