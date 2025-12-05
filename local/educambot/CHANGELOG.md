# Changelog

Todos los cambios notables de este proyecto seran documentados en este archivo.

El formato esta basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere a [Semantic Versioning](https://semver.org/lang/es/).

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
