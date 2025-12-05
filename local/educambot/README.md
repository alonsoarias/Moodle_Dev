# Nexo Bot - Chatbot Inteligente para Moodle

**Version**: 1.9.3
**Requiere**: Moodle 4.0+
**Licencia**: GPL v3+

## Descripcion

Nexo Bot es un plugin de chatbot completo para Moodle que proporciona asistencia automatizada a estudiantes, profesores y administradores. Incluye un sistema de reglas avanzado, respuestas dinamicas basadas en contexto, y personalizacion completa de la interfaz.

## Caracteristicas Principales

### Sistema de Reglas Inteligente
- Matching basado en patrones y palabras clave
- Puntuacion de confianza para respuestas
- Soporte multi-idioma con auto-deteccion
- Filtrado por arquetipos de rol de Moodle
- Restriccion por cursos especificos

### Widget de Chat Interactivo
- Interfaz flotante responsiva
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

1. Copiar la carpeta `educambot` a `/local/`
2. Acceder a **Administracion del sitio > Notificaciones**
3. Seguir el proceso de instalacion/actualizacion
4. Configurar en **Administracion del sitio > Plugins > Plugins locales > Nexo Bot**

## Configuracion

### Configuracion General
- **Habilitar widget**: Mostrar/ocultar el chat en todas las paginas
- **Nombre del bot**: Nombre que se muestra en el encabezado
- **Mensaje de saludo**: Plantilla con marcadores ({{userfirstname}}, {{botname}})

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

## Uso

### Para Administradores

#### Gestionar Reglas
1. Ir a **Administracion > Plugins locales > Nexo Bot > Gestionar Reglas**
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
| `local_educambot_rule` | Reglas de respuesta |
| `local_educambot_category` | Categorias jerarquicas |
| `local_educambot_option` | Opciones de respuesta rapida |
| `local_educambot_shortcut` | Accesos rapidos |
| `local_educambot_log` | Registro de conversaciones |
| `local_educambot_theme` | Temas visuales |
| `local_educambot_schedule` | Horarios de disponibilidad |

## API AJAX

### Endpoint: `/local/educambot/service.php`

```javascript
fetch('/local/educambot/service.php', {
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

## Permisos

| Capacidad | Descripcion |
|-----------|-------------|
| `local/educambot:use` | Usar el chatbot |
| `local/educambot:manage` | Gestionar reglas y configuracion |

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
- Contactar al equipo de desarrollo EducamBot

## Licencia

Este plugin es software libre bajo la licencia GNU GPL v3 o posterior.
Consulta el archivo LICENSE para mas detalles.

---

**EducamBot Team** - 2025
