# udesbot - Chatbot Inteligente para Moodle

**Version**: 2.2.1
**Requiere**: Moodle 4.0 - 4.5
**Licencia**: GPL v3+
**Autor**: Alonso Arias <soporte@orioncloud.com.co>
**Copyright**: 2025 OrionCloud<https://orioncloud.com.co>

## Descripcion

udesbot es un plugin de chatbot completo para Moodle que proporciona asistencia automatizada a estudiantes, profesores y administradores. Incluye un sistema de reglas avanzado, respuestas dinamicas basadas en contexto, feedback de usuarios, y personalizacion completa de la interfaz.

## Novedades en v2.2.1

- **Bordes rectos**: Apariencia limpia sin border-radius
- **Mascota externa**: Posicionada fuera del chat para no obstruir contenido
- **Animacion del boton**: Mantenida la animacion pulse en el boton flotante

## Novedades en v2.2.0

- **Nuevas mascotas**: Gato estudioso y Bombilla de ideas
- **Mascotas por defecto**: Todos los temas ahora incluyen mascota habilitada
- **Nuevo tema Professional**: Colores indigo con mascota Bombilla

## Novedades en v2.1.0

- **Base de conocimientos expandida**: 201 reglas cubriendo todos los arquetipos
- **Arquitectura JSON modular**: KB organizada en 9 archivos JSON tematicos
- **Placeholder {{site.name}}**: Respuestas personalizadas con el nombre del sitio
- **Terminologia Moodle 4.x**: Actualizada toda la KB (indice del curso, cajon de bloques, etc.)

## Novedades en v2.0.0

- **Indicador de escritura**: Animacion visual mientras el bot procesa
- **Timestamps**: Hora en cada mensaje
- **Sistema de feedback**: Thumbs up/down para mejorar respuestas
- **Exportar conversacion**: Descarga como archivo .txt
- **Sonidos de notificacion**: Audio sutil al recibir respuestas
- **Estado online**: Indicador visual de disponibilidad

## Caracteristicas Principales

### Sistema de Reglas Inteligente
- Matching basado en patrones y palabras clave
- Puntuacion de confianza para respuestas
- Soporte multi-idioma con auto-deteccion
- Filtrado por arquetipos de rol de Moodle
- Restriccion por cursos especificos
- Contadores de feedback por regla

### Widget de Chat Interactivo
- Interfaz flotante responsiva
- Indicador de escritura animado (v2.0.0)
- Timestamps en cada mensaje (v2.0.0)
- Botones de feedback thumbs up/down (v2.0.0)
- Exportar conversacion a texto (v2.0.0)
- Sonidos de notificacion (v2.0.0)
- Mascotas animadas (Clippy, Robot, Buho, personalizada)
- Temas visuales personalizables
- Opciones de respuesta rapida (Quick Replies)
- Historial de conversaciones persistente

### Accesos Rapidos (Shortcuts)
Comandos que muestran datos dinamicos de Moodle:
- Tareas pendientes
- Calificaciones del curso
- Eventos del calendario
- Mensajes recientes
- Profesores del curso
- Progreso academico

### Personalizacion Avanzada
- Temas de colores configurables
- Iconos personalizables (emoji, Font Awesome, imagen)
- Mascotas animadas con estados
- Horarios de disponibilidad
- Saludos por arquetipo de rol

### Administracion
- Panel de gestion de reglas con busqueda y filtros
- Categorias jerarquicas para organizar reglas
- Sistema de etiquetas (tags)
- Importar/Exportar base de conocimiento (JSON)
- Reportes y estadisticas de uso
- Cumplimiento GDPR con privacy provider

## Instalacion

1. Copiar la carpeta `udesbot` a `/local/`
2. Acceder a **Administracion del sitio > Notificaciones**
3. Seguir el proceso de instalacion/actualizacion
4. Configurar en **Administracion del sitio > Plugins > Plugins locales > udesbot**

## Configuracion

### Configuracion General
- **Habilitar widget**: Mostrar/ocultar el chat en todas las paginas
- **Nombre del bot**: Nombre que se muestra en el encabezado
- **Mensaje de saludo**: Plantilla con marcadores ({{userfirstname}}, {{fullname}}, {{botname}})

### Personalizacion Visual
- **Color primario**: Color principal del widget
- **Tipo de icono**: Default, emoji, Font Awesome o imagen personalizada
- **Mascota**: Ninguna, Clippy, Robot, Buho o SVG personalizado

### Horarios
- Configurar dias y horas de disponibilidad
- Fuera de horario el widget no se muestra

### Idioma
- **Auto-detectar idioma**: Preferir reglas en el idioma del usuario
- Soporte para traducciones vinculadas entre reglas

### Historial de Conversaciones
- **Habilitar historial**: Guardar y restaurar conversaciones de usuarios
- **Periodo de retencion**: Cuanto tiempo conservar los registros
  - Para siempre (sin limpieza automatica)
  - 1 semana, 1 mes, 3 meses, 6 meses, 1 año
- **Tarea programada**: Limpieza automatica diaria a las 3:00 AM

## Uso

### Para Administradores

#### Gestionar Reglas
1. Ir a **Administracion > Plugins locales > udesbot > Gestionar Reglas**
2. Crear reglas con:
   - **Patron**: Pregunta principal que activa la regla
   - **Palabras clave**: Terminos adicionales para matching
   - **Respuesta**: Texto que devuelve el bot
   - **Categoria**: Organizacion de la regla
   - **Tags**: Etiquetas para busqueda

#### Opciones Avanzadas de Reglas
- **Sensible al contexto**: Usa informacion del curso actual
- **Respuesta dinamica**: Contiene marcadores reemplazables
- **Contexto requerido**: Solo funciona en sitio/curso/actividad
- **Arquetipos permitidos**: student, teacher, editingteacher, coursecreator, manager, guest, user
- **Cursos**: IDs de cursos donde aplica (separados por coma)

#### Gestionar Categorias
Organizar reglas en categorias jerarquicas para mejor administracion.

#### Gestionar Accesos Rapidos
Crear comandos que muestran datos dinamicos:
- Tipo de accion: assignments, grades, calendar, messages, teachers, course, progress
- Palabras clave que activan el acceso

#### Importar/Exportar
- Exportar toda la base de conocimiento a JSON
- Importar desde archivo JSON
- Opcion para limpiar datos existentes

### Para Usuarios

El widget de chat aparece en la esquina inferior derecha de Moodle:

1. Hacer clic en el icono del bot para abrir el chat
2. Escribir una pregunta o seleccionar una sugerencia
3. El bot respondera basandose en las reglas configuradas
4. Las opciones de respuesta rapida permiten navegar temas relacionados

## Arquetipos de Rol de Moodle

El plugin filtra reglas basandose en arquetipos de rol (categorias fijas de Moodle), NO en nombres de rol personalizados:

| Arquetipo | Descripcion |
|-----------|-------------|
| `student` | Estudiantes matriculados |
| `teacher` | Profesores sin edicion |
| `editingteacher` | Profesores con edicion |
| `coursecreator` | Creadores de cursos |
| `manager` | Gestores y administradores |
| `guest` | Usuarios invitados |
| `user` | Usuarios autenticados sin rol especifico |

## Marcadores Dinamicos

En respuestas dinamicas puedes usar:

| Marcador | Descripcion |
|----------|-------------|
| `{{username}}` | Nombre completo del usuario |
| `{{userfirstname}}` | Nombre del usuario |
| `{{coursename}}` | Nombre del curso actual |
| `{{courseshortname}}` | Nombre corto del curso |
| `{{teacher}}` | Nombre del profesor |
| `{{grade}}` | Calificacion actual |
| `{{pendingassignments}}` | Lista de tareas pendientes |
| `{{nextevent}}` | Proximo evento del calendario |
| `{{botname}}` | Nombre configurado del bot |
| `{{site.name}}` | Nombre del sitio Moodle |

## Algoritmo de Matching

El motor calcula un puntaje para cada regla:

| Criterio | Puntos |
|----------|--------|
| Coincidencia exacta | +100 |
| Patron contenido en pregunta | +50 |
| Pregunta contenida en patron | +40 |
| Palabras en comun | hasta +30 |
| Palabra clave coincide | +20 por keyword |

La regla con mayor puntaje gana (si score > 0).

## Estructura de Base de Datos

### Tablas

| Tabla | Descripcion |
|-------|-------------|
| `local_udesbot_rule` | Reglas de respuesta |
| `local_udesbot_category` | Categorias jerarquicas |
| `local_udesbot_option` | Opciones de respuesta rapida |
| `local_udesbot_shortcut` | Accesos rapidos |
| `local_udesbot_log` | Registro de conversaciones |
| `local_udesbot_theme` | Temas visuales |
| `local_udesbot_schedule` | Horarios de disponibilidad |
| `local_udesbot_feedback` | Feedback de usuarios (v2.0.0) |

### Campos de Feedback en Reglas (v2.0.0)

| Campo | Tipo | Descripcion |
|-------|------|-------------|
| `helpfulcount` | INT | Contador de feedback positivo |
| `nothelpfulcount` | INT | Contador de feedback negativo |

## API AJAX

### Endpoint: `/local/udesbot/service.php`

```javascript
fetch('/local/udesbot/service.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'sesskey=' + M.cfg.sesskey + '&question=como me inscribo&courseid=2'
})
.then(r => r.json())
.then(data => console.log(data));
```

### Respuesta

```json
{
    "success": true,
    "response": "Para inscribirte en un curso...",
    "ruleid": 5,
    "confidence": 0.85,
    "options": [
        {"id": 1, "text": "Ver cursos disponibles", "icon": "book"}
    ],
    "type": "rule",
    "context": {
        "type": "course",
        "courseid": 2,
        "incourse": true
    }
}
```

### Endpoint: `/local/udesbot/feedback.php` (v2.0.0)

```javascript
fetch('/local/udesbot/feedback.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'sesskey=' + M.cfg.sesskey + '&ruleid=5&helpful=1'
})
.then(r => r.json())
.then(data => console.log(data));
```

### Respuesta Feedback

```json
{
    "success": true,
    "message": "Gracias por tu retroalimentacion!"
}
```

## Permisos

| Capacidad | Descripcion |
|-----------|-------------|
| `local/udesbot:use` | Usar el chatbot |
| `local/udesbot:manage` | Gestionar reglas y configuracion |

## Privacidad (GDPR)

El plugin incluye un privacy provider completo que:
- Describe los datos almacenados (preguntas, respuestas, timestamps)
- Exporta datos del usuario bajo solicitud
- Elimina datos del usuario bajo solicitud

## Requisitos del Sistema

- Moodle 4.0 o superior
- PHP 7.4 o superior
- JavaScript habilitado en el navegador

## Changelog

Ver [CHANGELOG.md](CHANGELOG.md) para el historial completo de versiones.

## Soporte

Para reportar problemas o sugerencias:
- Crear un issue en el repositorio
- Contactar a soporte@orioncloud.com.co

## Licencia

Este plugin es software libre bajo la licencia GNU GPL v3 o posterior.
Consulta el archivo LICENSE para mas detalles.

---

**Desarrollado por [Ingeweb](https://ingeweb.co)** - 2025
