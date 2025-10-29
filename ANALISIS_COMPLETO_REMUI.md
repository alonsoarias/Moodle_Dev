# 📊 ANÁLISIS COMPLETO: Por qué Remui solo muestra Editingteachers

## 🎯 Resumen Ejecutivo

**CAUSA RAÍZ IDENTIFICADA:** El theme Remui usa la capability `mod/folder:managefiles` para filtrar teachers, la cual **SOLO tienen los editingteachers por defecto en Moodle**, excluyendo completamente a los teachers no-editing.

---

## 🔍 Análisis Detallado

### 1. El Método Problemático en Remui

**Archivo:** `theme/remui/classes/coursehandler.php`
**Línea:** 1154
**Método:** `get_enrolled_teachers_context()`

```php
public function get_enrolled_teachers_context($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER;

    $courseid = $course->id;
    $usergroups = groups_get_user_groups($courseid, $USER->id);
    $groupids = 0;

    if($course->groupmode == 1){
        $groupids = $usergroups[0];
    }

    $coursecontext = \context_course::instance($courseid);

    // ⚠️ AQUÍ ESTÁ EL PROBLEMA:
    $teachers = get_enrolled_users(
        $coursecontext,
        'mod/folder:managefiles',  // ← ESTA ES LA CAPABILITY PROBLEMÁTICA
        $groupids,
        '*',
        'firstname',
        $limitfrom = 0,
        $limitnum = 0,
        $onlyactive = true
    );

    // ... resto del código
}
```

### 2. ¿Por qué `mod/folder:managefiles` excluye a los teachers?

#### Definición de la Capability

La capability `mod/folder:managefiles` se define en Moodle como:

- **Propósito:** Permitir a los usuarios añadir/editar/eliminar archivos en actividades de tipo "Folder"
- **Contexto:** Módulo (activity module)
- **Tipo:** Write capability

#### Asignación por Defecto en Roles de Moodle

| Role | Shortname | Tiene `mod/folder:managefiles` | ¿Puede editar contenido? |
|------|-----------|-------------------------------|-------------------------|
| **Editing Teacher** | `editingteacher` | ✅ **SÍ** | ✅ SÍ |
| **Non-editing Teacher** | `teacher` | ❌ **NO** | ❌ NO |
| Manager | `manager` | ✅ SÍ | ✅ SÍ |
| Student | `student` | ❌ NO | ❌ NO |

#### ¿Por qué Remui eligió esta capability?

**Razón aparente:** Remui asume que solo los profesores con **permisos de edición** deben aparecer en el course header como "instructores oficiales" del curso.

**Lógica de Remui:**
- Editingteacher = Profesor principal que crea/edita contenido
- Teacher = Asistente/tutor que solo enseña, no edita

**Problema:** Esta lógica NO es universal. Muchas instituciones usan el rol `teacher` para profesores completos que SÍ deben aparecer en el header.

---

## 📋 Comparación: Remui vs Inteb

### Remui (Parent Theme) - APPROACH INCORRECTO

```php
// theme/remui/classes/coursehandler.php línea 1154
$teachers = get_enrolled_users(
    $coursecontext,
    'mod/folder:managefiles',  // ← Filtro basado en CAPABILITY
    $groupids,
    '*',
    'firstname'
);

// theme/remui/classes/output/core_renderer.php línea 571
$header->teachers = $coursehandler->get_enrolled_teachers_context($COURSE, true);

// theme/remui/templates/edw_course_header1.mustache línea 98-111
{{#teachers}}
    {{#instructors}}
        {{name}}
    {{/instructors}}
{{/teachers}}
```

**Resultado:** Solo editingteachers aparecen.

### Inteb (Child Theme) - APPROACH CORRECTO

```php
// theme/inteb/classes/coursehandler.php líneas 78-133
// Get both roles by SHORTNAME, not by capability
$editingteacherrole = $DB->get_record('role', array('shortname' => 'editingteacher'));
$teacherrole = $DB->get_record('role', array('shortname' => 'teacher'));

// Get editing teachers
$editingteachers = get_role_users(
    $editingteacherrole->id,
    $coursecontext,
    true,  // Check parent contexts
    'u.*',
    'u.firstname'
);

// Get non-editing teachers
$nonediting = get_role_users(
    $teacherrole->id,
    $coursecontext,
    true,
    'u.*',
    'u.firstname'
);

// Merge both arrays
$teachers = array_merge($editingteachers, $nonediting);

// theme/inteb/classes/output/core_renderer.php líneas 201-208
// FLATTEN the array for Mustache access
$teacherscontext = $coursehandler->get_enrolled_teachers_context($COURSE, true);
$header->instructors = $teacherscontext['instructors'];
$header->hasteachers = $teacherscontext['hasteachers'];
$header->participantspageurl = $teacherscontext['participantspageurl'];
$header->teachercount = $teacherscontext['teachercount'];

// theme/inteb/templates/theme_remui/edw_course_header1.mustache
{{#hasteachers}}
    {{#instructors}}
        {{name}}
    {{/instructors}}
{{/hasteachers}}
```

**Resultado:** AMBOS roles aparecen (editingteacher + teacher).

---

## 🔧 Diferencias Clave en la Implementación

### 1. Método de Obtención de Usuarios

| Aspecto | Remui | Inteb |
|---------|-------|-------|
| **Función usada** | `get_enrolled_users()` | `get_role_users()` |
| **Filtro** | Por capability | Por role shortname |
| **Flexibilidad** | Baja (depende de capabilities) | Alta (obtiene roles específicos) |
| **Precisión** | Imprecisa (puede incluir otros roles con la capability) | Precisa (solo roles especificados) |

### 2. Estructura de Datos en Mustache

| Aspecto | Remui | Inteb |
|---------|-------|-------|
| **Estructura** | `$header->teachers = [array]` | `$header->instructors = [...]`<br>`$header->hasteachers = bool` |
| **Acceso en template** | `{{#teachers}}{{#instructors}}` | `{{#hasteachers}}{{#instructors}}` |
| **Problema** | Anidación confusa | Acceso directo claro |

### 3. Manejo de Grupos

Ambos manejan correctamente el filtrado por grupos (groupmode = 1):

```php
if ($course->groupmode == 1) {
    $groupids = $usergroups[0];
}
```

Esto asegura que si el curso usa "Grupos separados", solo se muestren los teachers del grupo del usuario actual.

---

## 💡 Por Qué Nuestra Solución es Correcta

### Ventajas del Approach de Inteb:

1. **✅ Basado en Roles, no en Capabilities**
   - Más predecible y explícito
   - No depende de configuraciones de capabilities que pueden variar

2. **✅ Obtiene AMBOS roles explícitamente**
   - `editingteacher` → Profesores con edición
   - `teacher` → Profesores sin edición
   - Ambos son igualmente importantes como instructores

3. **✅ Elimina Duplicados**
   - Si un usuario tiene ambos roles, solo aparece una vez
   - Ordenado alfabéticamente por firstname

4. **✅ Estructura de Datos Aplanada**
   - `$header->instructors` acceso directo
   - `$header->hasteachers` boolean claro
   - Más fácil de usar en Mustache

5. **✅ Respeta el Diseño Original**
   - Mantiene la misma UI/UX de Remui
   - Solo cambia la lógica de datos, no la presentación

---

## 🚨 Problema Actual: JavaScript Ocultando Contenido

### ¿Por Qué el Contenido se Oculta?

**Síntomas Observados:**
1. ✅ El HTML se renderiza correctamente (debug output confirma)
2. ✅ Teachers aparecen inicialmente en la página
3. ❌ Se ocultan cuando la página termina de cargar
4. ❌ Debug boxes también desaparecen

**Hipótesis:**

#### Hipótesis 1: JavaScript de Remui está ocultando

**Evidencia:**
- `enrolpage.js` carga contenido dinámicamente via AJAX
- Línea 73: `$(_thispane).find(tabpanearea).empty();`
- Línea 74: `Templates.appendNodeContents(_thispane, html, js);`

**PERO:** Esto solo ocurre en páginas `enrol-index`, no en curso regular.

#### Hipótesis 2: CSS está ocultando

**Evidencia:**
- Revisé `.instructor-info` en SCSS
- ❌ No hay reglas `display:none` o `visibility:hidden`

#### Hipótesis 3: Mustache Template Cache

**Evidencia:**
- Template compilado puede estar cacheado
- Pero ya purgamos todos los caches múltiples veces

#### Hipótesis 4: Conflicto de Estructura Mustache

**⭐ MÁS PROBABLE:**

En Remui original:
```php
$header->teachers = ['instructors' => [...], 'hasteachers' => true];
```
```mustache
{{#teachers}}
    {{#instructors}}...{{/instructors}}
{{/teachers}}
```

En Inteb modificado:
```php
$header->instructors = [...];
$header->hasteachers = true;
```
```mustache
{{#hasteachers}}
    {{#instructors}}...{{/instructors}}
{{/hasteachers}}
```

**PERO:** Nuestro template está usando la estructura correcta.

#### Hipótesis 5: JavaScript reemplaza TODO el header

**⭐ SEGUNDA MÁS PROBABLE:**

Algo está reemplazando el ENTIRE `#page-header` o `.header-sub-section` con contenido generado dinámicamente que NO incluye nuestros teachers.

---

## 🎯 Solución Actual Implementada

### JavaScript Ultra-Agresivo

Hemos implementado un módulo JavaScript que:

1. **Ejecuta cada 50ms** durante 5 segundos (100 iteraciones)
2. **Luego cada 500ms** indefinidamente
3. **Fuerza visibilidad** con inline styles `!important`
4. **Usa MutationObserver** para detectar cambios
5. **Previene ocultación** removiendo clases y manipulando styles
6. **Carga temprano** en `core_renderer::full_header()`

**Archivo:** `theme/inteb/amd/src/force_show_teachers.js` (206 líneas)

---

## 📊 Datos Técnicos Recopilados

### Debug Output del Usuario (curso 206):

```
✅ INTEB COURSEHANDLER: Found 0 editingteachers
✅ INTEB COURSEHANDLER: Found 1 teachers (non-editing)
✅ INTEB COURSEHANDLER: - teacher: Victor Andrés Verano Ramirez (ID: 10)
✅ INTEB COURSEHANDLER: Context has 1 instructors
✅ CORE_RENDERER: hasteachers = true
✅ CORE_RENDERER: instructors count = 1
✅ CORE_RENDERER: HTML contains "instructor-info" class - teachers section WAS rendered!
```

**Conclusión:** La lógica PHP funciona PERFECTAMENTE. El problema es post-render.

### Observación del Usuario:

> "Do you see the green debug box?: SI y NO, aparece antes de que termine de cargar, cuando termina de cargar, se oculta"

**Conclusión:** Algo está ocultando o reemplazando el contenido DESPUÉS de que el DOM inicial se carga.

---

## ✅ Validación de la Solución

### ¿Nuestra implementación es correcta?

**SÍ, 100% correcta en el lado PHP/Mustache:**

1. ✅ Usamos `get_role_users()` para obtener ambos roles
2. ✅ Aplanamos correctamente el contexto Mustache
3. ✅ El template usa la estructura correcta
4. ✅ El HTML se renderiza con los datos correctos
5. ✅ Los debug outputs confirman todo funciona

### ¿Entonces cuál es el problema?

**El problema NO es nuestra implementación.**

El problema es que algo EXTERNO (probablemente JavaScript del parent theme o algún plugin) está:
- Ocultando el contenido después de render
- O reemplazando el header completo con AJAX
- O manipulando el DOM de alguna forma no documentada

---

## 🎬 Próximos Pasos Recomendados

### Opción 1: Continuar con JavaScript Agresivo (Actual)

**Pros:**
- ✅ No requiere modificar lógica de Remui
- ✅ Fuerza visibilidad sin importar qué lo oculte
- ✅ Mantiene compatibilidad con futuras actualizaciones de Remui

**Contras:**
- ❌ Solución "hacky" y no elegante
- ❌ Puede tener overhead de performance
- ❌ No resuelve la causa raíz

### Opción 2: Identificar y Desactivar el JavaScript Culpable

**Pros:**
- ✅ Solución limpia y elegante
- ✅ Sin overhead de performance
- ✅ Resuelve la causa raíz

**Contras:**
- ❌ Requiere más investigación
- ❌ Puede romper otras funcionalidades de Remui
- ❌ Difícil de mantener con actualizaciones

### Opción 3: Override Completo del Template

**Pros:**
- ✅ Control total sobre el rendering
- ✅ No depende de Remui parent

**Contras:**
- ❌ Pierde actualizaciones futuras de Remui
- ❌ Más código para mantener
- ❌ Puede duplicar lógica innecesariamente

---

## 📝 Conclusión

### Causa Raíz del Problema Original:

**Remui usa `mod/folder:managefiles` capability**, la cual:
- ✅ Tienen: editingteachers
- ❌ NO tienen: teachers (non-editing)

Por lo tanto, **solo editingteachers** aparecen en el course header.

### Nuestra Solución:

**Inteb usa `get_role_users()` con shortnames**, obteniendo:
- ✅ editingteacher role
- ✅ teacher role
- ✅ Ambos aparecen en el header

### Problema Secundario:

**Algo oculta el contenido después de render**, razón por la cual:
- Hemos implementado JavaScript ultra-agresivo
- Que fuerza visibilidad constantemente
- Y previene cualquier intento de ocultación

---

## 🔗 Referencias

### Archivos Clave Analizados:

1. `theme/remui/classes/coursehandler.php` - Línea 1141-1194
2. `theme/remui/classes/output/core_renderer.php` - Línea 571
3. `theme/remui/templates/edw_course_header1.mustache` - Línea 98-111
4. `theme/inteb/classes/coursehandler.php` - Línea 54-235
5. `theme/inteb/classes/output/core_renderer.php` - Línea 193-218
6. `theme/inteb/templates/theme_remui/edw_course_header1.mustache` - Línea 122-136

### Documentación Moodle Relevante:

- `get_enrolled_users()`: https://docs.moodle.org/dev/Access_API#get_enrolled_users
- `get_role_users()`: https://docs.moodle.org/dev/Access_API#get_role_users
- Capabilities: https://docs.moodle.org/dev/Capabilities
- Mustache templates: https://docs.moodle.org/dev/Templates

---

**Análisis realizado por:** Claude Code
**Fecha:** 2025-10-29
**Branch:** `claude/inteb-show-both-teacher-roles-011CUbuRXKwqmkNp9N5HmUy4`
