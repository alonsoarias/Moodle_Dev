# Block Report Educam1

Plugin de bloque para Moodle que genera reportes de completitud de actividades por tipo.

## Descripción

Block Report Educam1 es un plugin de bloque para Moodle que permite a los profesores y administradores visualizar el progreso de los estudiantes en actividades específicas de un curso. El plugin ofrece dos vistas diferentes para analizar el progreso de completitud.

## Características principales

### Ámbito de uso
- **Solo contextos de curso**: El bloque funciona únicamente dentro de cursos, no en el dashboard ni en otras áreas.
- **Múltiples instancias**: Permite agregar múltiples instancias del bloque en el mismo curso para reportar diferentes tipos de actividades.

### Configuración
- **Selector de tipo de actividad**: Cada instancia del bloque permite seleccionar un tipo de actividad específico (Tarea, Cuestionario, SCORM, Foro, etc.).
- **Configuración por instancia**: Cada bloque guarda su propia configuración independientemente.

### Dos vistas de reporte

#### Vista 1 - Detalle Individual
Muestra una tabla detallada con información individual de cada estudiante:
- ID Number (número de identificación)
- Nombres
- Apellidos
- Email
- Nombre de la actividad
- Fecha de finalización (si completó)
- Estado visual (✓ completado / ✗ no completado)

#### Vista 2 - Matriz Global
Muestra una tabla matricial con:
- Columnas de datos del estudiante (ID Number, Nombres, Apellidos, Email)
- Columnas adicionales para TODAS las actividades del tipo seleccionado
- Checks (✓) en las celdas donde el estudiante completó la actividad
- Permite ver de un vistazo el progreso de todos los estudiantes

### Exportación de datos
- **Formatos disponibles**: Excel (.xlsx), OpenDocument (.ods), CSV
- **Exportación por vista**: Cada vista exporta en su formato correspondiente
- **Preservación de estructura**: Los archivos exportados mantienen la misma estructura que la vista en pantalla

### Estadísticas
- Total de estudiantes
- Total de actividades (vista matricial)
- Tasa de completitud general
- Contadores de completados vs no completados

## Requisitos

- Moodle 4.0 o superior
- PHP 7.4 o superior

## Instalación

1. Descarga o clona este repositorio
2. Copia la carpeta `report_educam1` a `[moodle]/blocks/`
3. Accede a tu sitio Moodle como administrador
4. Navega a "Administración del sitio" → "Notificaciones"
5. Sigue las instrucciones para completar la instalación

## Uso

### Agregar el bloque a un curso

1. Activa el modo de edición en un curso
2. Selecciona "Agregar un bloque" → "Reporte de actividades Educam"
3. Configura el bloque seleccionando el tipo de actividad a reportar

### Configuración del bloque

1. Haz clic en el icono de configuración del bloque
2. Selecciona el tipo de actividad que deseas reportar del menú desplegable
3. Guarda los cambios

### Ver el reporte

1. Haz clic en "Ver reporte de actividades" en el bloque
2. Utiliza los botones para cambiar entre "Vista individual" y "Vista matricial"
3. Exporta los datos usando el formulario en la parte inferior

## Permisos

El plugin define las siguientes capacidades:

- `block/report_educam1:addinstance` - Añadir un nuevo bloque al curso
- `block/report_educam1:myaddinstance` - Añadir el bloque a Mi Moodle
- `block/report_educam1:viewreport` - Ver el reporte de actividades

Por defecto, estas capacidades están disponibles para:
- Administradores (manager)
- Profesores con permisos de edición (editingteacher)
- Profesores sin permisos de edición (teacher) - solo visualización

## Soporte multiidioma

El plugin incluye strings de idioma para:
- Español (es)
- Inglés (en)

## Desarrollo

### Estructura de archivos

```
blocks/report_educam1/
├── block_report_educam1.php    # Clase principal del bloque
├── edit_form.php                # Formulario de configuración
├── lib.php                      # Funciones auxiliares
├── report.php                   # Página de visualización de reportes
├── version.php                  # Información de versión
├── README.md                    # Este archivo
├── classes/
│   └── privacy/
│       └── provider.php         # Implementación de privacidad
├── db/
│   └── access.php              # Definición de capacidades
└── lang/
    ├── en/
    │   └── block_report_educam1.php  # Strings en inglés
    └── es/
        └── block_report_educam1.php  # Strings en español
```

## Créditos

- **Desarrollador**: Educam Development Team
- **Copyright**: 2025 Educam
- **Licencia**: GNU GPL v3 o posterior

## Changelog

### Versión 1.0.0 (2025-11-07)
- Versión inicial
- Implementación de vista individual
- Implementación de vista matricial
- Exportación a Excel, ODS y CSV
- Soporte multiidioma (español e inglés)
- Estadísticas de completitud

## Licencia

Este programa es software libre: puede redistribuirlo y/o modificarlo bajo los términos de la Licencia Pública General GNU publicada por la Free Software Foundation, ya sea la versión 3 de la Licencia, o (a su elección) cualquier versión posterior.

Este programa se distribuye con la esperanza de que sea útil, pero SIN NINGUNA GARANTÍA.
