# Solución: Override de format_remuiformat desde theme_inteb

## 🎯 Objetivo

Mostrar **AMBOS** roles de teachers (editing y non-editing) en el header de cursos que usan `format_remuiformat`, **SIN modificar el plugin** del formato.

## 🔍 El Problema Identificado

### 1. JavaScript de Reemplazo de Header

**Archivo:** `course/format/remuiformat/amd/src/headerreplaces.js:34`

```javascript
$('#page #page-header').replaceWith(headercontent);
```

Este JavaScript **REEMPLAZA COMPLETAMENTE** el header del theme con el header generado por el formato, por eso nuestros cambios en `theme_inteb` desaparecían.

### 2. Filtrado Incorrecto de Teachers

**Archivo:** `course/format/remuiformat/lib.php:893`

```php
$teachers = get_enrolled_users($coursecontext, 'mod/folder:managefiles', ...);
```

La capability `mod/folder:managefiles` solo la tienen los **editingteachers** por defecto, excluyendo a los non-editing teachers.

## ✅ Solución Implementada

La solución usa el patrón de **override desde theme** mediante:

### 1. Servicio Web en theme_inteb

**Archivo:** `theme/inteb/classes/external/get_course_teachers.php`

Servicio web que obtiene TODOS los teachers (editing y non-editing) de un curso:

```php
// Get editing teacher role
$editingteacherrole = $DB->get_record('role', ['shortname' => 'editingteacher']);
if ($editingteacherrole) {
    $editingteachers = get_role_users($editingteacherrole->id, $coursecontext, ...);
    $teachers = array_merge($teachers, $editingteachers);
}

// Get non-editing teacher role
$teacherrole = $DB->get_record('role', ['shortname' => 'teacher']);
if ($teacherrole) {
    $nonediting = get_role_users($teacherrole->id, $coursecontext, ...);
    $teachers = array_merge($teachers, $nonediting);
}
```

**Características:**
- ✅ Obtiene ambos roles explícitamente por shortname
- ✅ Elimina duplicados (si un usuario tiene ambos roles)
- ✅ Respeta modo de grupos separados
- ✅ Ordena alfabéticamente
- ✅ Limita a 4 teachers + enlace "view all"
- ✅ Requiere autenticación y capability `moodle/course:view`

### 2. Registro del Servicio Web

**Archivo:** `theme/inteb/db/services.php`

```php
$functions = [
    'theme_inteb_get_course_teachers' => [
        'classname' => 'theme_inteb\external\get_course_teachers',
        'methodname' => 'execute',
        'description' => 'Get all teachers (both editing and non-editing) for a course',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
```

### 3. Módulo JavaScript de Override

**Archivo:** `theme/inteb/amd/src/format_remuiformat_teacher_fix.js`

JavaScript que se ejecuta **DESPUÉS** de `headerreplaces.js`:

**Flujo:**
1. Espera a que `headerreplaces.js` reemplace el header
2. Llama al servicio web `theme_inteb_get_course_teachers`
3. Reemplaza el contenido de `.instructor-info` con TODOS los teachers
4. Asegura visibilidad del contenedor

**Estrategias de timing:**
- DOM ready + 500ms delay
- Window load + 500ms delay
- MutationObserver para detectar cuando se reemplaza el header

```javascript
var replaceTeachersList = function(courseId) {
    var request = {
        methodname: 'theme_inteb_get_course_teachers',
        args: { courseid: courseId }
    };

    Ajax.call([request])[0].done(function(response) {
        var $instructorInfo = $('.instructor-info.stat-container');
        $instructorInfo.empty();
        
        response.teachers.forEach(function(teacher) {
            // Render teacher HTML
        });
    });
};
```

### 4. Carga del JavaScript desde core_renderer

**Archivo:** `theme/inteb/classes/output/core_renderer.php:255-260`

```php
// INTEB: If course uses remuiformat, load our teacher fix to override the format's behavior
if ($COURSE->format == 'remuiformat') {
    $this->page->requires->js_call_amd('theme_inteb/format_remuiformat_teacher_fix', 'init', [$COURSE->id]);
    debugging('CORE_RENDERER: Loaded format_remuiformat_teacher_fix.js for course ' . $COURSE->id, DEBUG_DEVELOPER);
}
```

## 📊 Arquitectura de la Solución

```
┌─────────────────────────────────────────────────────────────┐
│  1. Page loads with format_remuiformat                     │
│     ↓                                                       │
│  2. format_remuiformat/headerreplaces.js executes          │
│     - Replaces theme header with format header             │
│     - Shows only editingteachers (capability filtered)     │
│     ↓                                                       │
│  3. theme_inteb/format_remuiformat_teacher_fix.js executes │
│     (AFTER headerreplaces completes)                       │
│     ↓                                                       │
│  4. AJAX call to theme_inteb_get_course_teachers           │
│     - Returns ALL teachers (editing + non-editing)         │
│     ↓                                                       │
│  5. Replace .instructor-info content                       │
│     - Show ALL teachers                                    │
│     - Make visible with inline styles                      │
│     ↓                                                       │
│  6. User sees: BOTH teacher types in header                │
└─────────────────────────────────────────────────────────────┘
```

## 📁 Archivos Modificados/Creados

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `theme/inteb/classes/external/get_course_teachers.php` | **NUEVO** | Servicio web para obtener todos los teachers |
| `theme/inteb/db/services.php` | **NUEVO** | Registro del servicio web |
| `theme/inteb/amd/src/format_remuiformat_teacher_fix.js` | **NUEVO** | JavaScript que intercepta y actualiza teachers |
| `theme/inteb/classes/output/core_renderer.php` | **MODIFICADO** | Carga JavaScript cuando formato es remuiformat |

## 🧪 Pasos para Probar

### 1. Purgar Cachés de Moodle

```bash
# Opción 1: Via web
http://tu-sitio/admin/purgecaches.php

# Opción 2: Via CLI
php admin/cli/purge_caches.php
```

### 2. Upgrade de Base de Datos

El nuevo servicio web necesita ser registrado:

```bash
# Via web
http://tu-sitio/admin/

# Via CLI
php admin/cli/upgrade.php
```

### 3. Verificar en un Curso

1. Navegar a un curso que use formato `remuiformat`
2. El curso debe tener:
   - Al menos 1 usuario con rol "Editing Teacher"
   - Al menos 1 usuario con rol "Teacher" (non-editing)
3. Verificar el header:
   - ¿Aparecen AMBOS tipos de teachers?
   - ¿Se mantienen visibles?
   - ¿No desaparecen al terminar de cargar?

### 4. Verificar Consola del Navegador

Abrir DevTools y verificar:
- No hay errores de JavaScript
- Aparecen logs `[INTEB]` indicando que el fix está funcionando
- La llamada AJAX a `theme_inteb_get_course_teachers` se ejecuta correctamente

## ✅ Ventajas de Esta Solución

| Ventaja | Descripción |
|---------|-------------|
| **No modifica el plugin** | Respeta la integridad de `format_remuiformat` |
| **Compatible con actualizaciones** | Futuras actualizaciones del plugin no afectarán nuestra solución |
| **Patrón estándar de Moodle** | Usa servicios web y AMD JavaScript siguiendo buenas prácticas |
| **Reutilizable** | El servicio web puede usarse en otros contextos si es necesario |
| **Debugging fácil** | Logs claros en consola para troubleshooting |
| **Respeta configuraciones** | Mantiene grupos separados, ordenamiento, límites, etc. |

## 🔧 Troubleshooting

### Problema: No aparecen los teachers

**Solución:**
1. Verificar en consola del navegador si hay errores
2. Verificar que el servicio web esté registrado: `Administración → Servidor → Servicios web → Funciones`
3. Purgar todas las cachés
4. Verificar que el usuario tenga `moodle/course:view` en el curso

### Problema: Solo aparecen editingteachers

**Solución:**
1. Verificar en consola si `format_remuiformat_teacher_fix.js` se está cargando
2. Verificar que la llamada AJAX se esté ejecutando
3. Verificar que el curso tenga teachers con rol "teacher" (non-editing)

### Problema: JavaScript no se carga

**Solución:**
1. Purgar cachés (especialmente AMD JavaScript cache)
2. Verificar en `theme/inteb/classes/output/core_renderer.php:256` que la condición se cumpla
3. Verificar en DevTools → Network que el archivo `.js` se esté descargando

## 📚 Comparación: Antes vs Después

### ANTES (Solución Incorrecta)

❌ Modificábamos directamente `course/format/remuiformat/lib.php`
❌ Los cambios se perderían en actualizaciones del plugin
❌ No respeta la arquitectura de Moodle

### DESPUÉS (Solución Correcta)

✅ Override limpio desde `theme_inteb`
✅ No se pierde en actualizaciones
✅ Sigue patrones estándar de Moodle
✅ Reutilizable y mantenible

## 🎉 Resultado Final

**TODOS los teachers** (editing y non-editing) aparecen correctamente en el header de cursos con formato `remuiformat`, sin modificar el plugin del formato.

La solución es:
- ✅ Limpia y mantenible
- ✅ Respeta la arquitectura de Moodle
- ✅ Compatible con actualizaciones futuras
- ✅ Reutilizable en otros contextos
- ✅ Fácil de debuggear y troubleshoot
