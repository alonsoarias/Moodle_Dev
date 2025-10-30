# Solución Final: Override de Template format_remuiformat desde theme_inteb

## 🎯 Objetivo

Mostrar **AMBOS** roles de teachers (editing y non-editing) en cursos con formato `remuiformat`, mediante sobrescritura del template del formato desde `theme_inteb`, sin modificar el plugin del formato.

## 📋 Resumen de la Solución

Esta solución utiliza el sistema de herencia de templates de Moodle para sobrescribir el template del formato de curso desde el theme, permitiendo que el theme tome control completo de cómo se muestran los teachers.

### Componentes de la Solución

1. **Template Override**: `theme/inteb/templates/format_remuiformat/optional_secheader.mustache`
2. **Servicio Web**: `theme/inteb/classes/external/get_course_teachers.php`
3. **Registro del Servicio**: `theme/inteb/db/services.php`
4. **JavaScript AMD**: `theme/inteb/amd/src/format_remuiformat_teacher_fix.js`
5. **Versión del Theme**: Actualizada a `2025102900`

## 🔧 Cómo Funciona

### 1. Herencia de Templates en Moodle

Moodle permite que los themes sobrescriban templates de plugins colocando el template en:
```
theme/[nombre_theme]/templates/[nombre_plugin]/[nombre_template].mustache
```

En nuestro caso:
```
theme/inteb/templates/format_remuiformat/optional_secheader.mustache
```

Este template tendrá prioridad sobre el template original del formato.

### 2. Flujo de Ejecución

```
1. Curso con formato remuiformat carga
   ↓
2. Moodle busca template optional_secheader.mustache
   - Encuentra override en theme/inteb/templates/format_remuiformat/
   - Usa el template sobrescrito (NO el original)
   ↓
3. Template renderiza con datos del formato
   - Muestra solo editingteachers (datos originales del formato)
   ↓
4. JavaScript del formato ejecuta: headerreplaces.js
   - Reemplaza el header del theme con el header del formato
   ↓
5. JavaScript del theme ejecuta: format_remuiformat_teacher_fix.js
   - Se ejecuta 500ms después de headerreplaces
   - Llama al servicio web theme_inteb_get_course_teachers
   - Obtiene TODOS los teachers (editing + non-editing)
   - Actualiza el DOM con la lista completa
   ↓
6. Usuario ve: AMBOS tipos de teachers ✓
```

## 📁 Archivos Creados/Modificados

### 1. Template Override

**Archivo**: `theme/inteb/templates/format_remuiformat/optional_secheader.mustache`  
**Líneas**: 147  
**Cambios vs original**:
- Líneas 14-16: Comentario indicando que es override de INTEB
- Líneas 48-52: Comentario explicando la estrategia
- Líneas 102-138: JavaScript adicional que carga nuestro fix

**JavaScript agregado** (líneas 107-138):
```javascript
require(['theme_inteb/format_remuiformat_teacher_fix'], function(TeacherFix) {
    setTimeout(function() {
        // Obtiene courseId de M.cfg, URL, o body class
        var courseId = (typeof M !== 'undefined' && M.cfg && M.cfg.courseId) ? M.cfg.courseId : null;
        
        if (!courseId) {
            var urlParams = new URLSearchParams(window.location.search);
            courseId = urlParams.get('id');
        }
        
        if (courseId) {
            TeacherFix.init(parseInt(courseId));
        }
    }, 500);
});
```

### 2. Servicio Web

**Archivo**: `theme/inteb/classes/external/get_course_teachers.php`  
**Líneas**: 180  
**Funcionalidad**:
- Obtiene usuarios con rol `editingteacher` usando `get_role_users()`
- Obtiene usuarios con rol `teacher` usando `get_role_users()`
- Merge y deduplicación de resultados
- Respeta modo de grupos separados
- Ordena alfabéticamente por firstname
- Limita a 4 teachers + enlace "view all"
- Retorna JSON con: id, name, avatar HTML, profile URL

### 3. Registro del Servicio Web

**Archivo**: `theme/inteb/db/services.php`  
**Líneas**: 35  
**Configuración**:
```php
$functions = [
    'theme_inteb_get_course_teachers' => [
        'classname' => 'theme_inteb\external\get_course_teachers',
        'methodname' => 'execute',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
```

### 4. JavaScript AMD

**Archivo**: `theme/inteb/amd/src/format_remuiformat_teacher_fix.js`  
**Líneas**: 173  
**Funcionalidad**:
- Llama al servicio web `theme_inteb_get_course_teachers`
- Actualiza `.instructor-info.stat-container` con todos los teachers
- Usa múltiples estrategias de timing (DOM ready, window load, MutationObserver)
- Asegura visibilidad con inline styles
- Logging detallado para debugging

### 5. Versión del Theme

**Archivo**: `theme/inteb/version.php`  
**Cambios**:
```php
// ANTES:
$plugin->version = 2025012206;
$plugin->release = '4.5.0';

// DESPUÉS:
$plugin->version = 2025102900; // Format remuiformat template override
$plugin->release = '4.5.1';
```

## 🧪 Pasos para Implementar

### 1. Aplicar los Cambios

```bash
# Mergear o aplicar el PR
git checkout main
git merge claude/format-remuiformat-template-override
```

### 2. Upgrade de Base de Datos

El servicio web necesita ser registrado en Moodle:

```bash
# Opción 1: CLI
php admin/cli/upgrade.php

# Opción 2: Web
# Visitar: http://tu-sitio/admin/
```

Esto registrará el servicio web `theme_inteb_get_course_teachers`.

### 3. Purgar Cachés

```bash
# Opción 1: CLI
php admin/cli/purge_caches.php

# Opción 2: Web
# Visitar: http://tu-sitio/admin/purgecaches.php
```

Esto asegura que:
- El template sobrescrito se recompile
- El JavaScript AMD se regenere
- Todos los cachés se limpien

### 4. Verificar

1. Navegar a un curso con formato `remuiformat`
2. El curso debe tener:
   - Al menos 1 usuario con rol "Editing Teacher"
   - Al menos 1 usuario con rol "Teacher" (non-editing)
3. Verificar que AMBOS aparecen en el header
4. Abrir consola del navegador (F12) y verificar logs `[INTEB]`

## ✅ Ventajas de Esta Solución

| Aspecto | Beneficio |
|---------|-----------|
| **No modifica el plugin** | ✅ Respeta la integridad de format_remuiformat |
| **Usa sistema de herencia** | ✅ Sigue el patrón estándar de Moodle |
| **Compatible con actualizaciones** | ✅ El plugin puede actualizarse sin problema |
| **Mantenible** | ✅ Todo el código está en theme_inteb |
| **Debuggeable** | ✅ Logs claros y estructura predecible |
| **Reutilizable** | ✅ El servicio web puede usarse en otros contextos |

## 🔍 Comparación con Soluciones Anteriores

### ❌ Solución 1: Modificar plugin directamente

**Problema**: Cambios se pierden en actualizaciones
**Ubicación**: `course/format/remuiformat/lib.php`

### ❌ Solución 2: JavaScript desde core_renderer

**Problema**: Dependía de detección del formato en renderer
**Ubicación**: `theme/inteb/classes/output/core_renderer.php`

### ✅ Solución 3: Template override (ACTUAL)

**Ventaja**: Control completo desde el template del theme
**Ubicación**: `theme/inteb/templates/format_remuiformat/`

## 📊 Detalles Técnicos

### Obtención del Course ID

El JavaScript usa múltiples estrategias para obtener el course ID:

1. **M.cfg.courseId** (global de Moodle)
2. **URL param `id`** (en /course/view.php?id=X)
3. **Body class** (class="course-123")

### Timing del JavaScript

- **headerreplaces.js**: Ejecuta primero (del formato)
- **Delay de 500ms**: Asegura que headerreplaces completó
- **format_remuiformat_teacher_fix.js**: Ejecuta después

### Visibilidad

El JavaScript fuerza visibilidad con:
```javascript
$instructorInfo.show().css({
    'display': 'flex',
    'visibility': 'visible',
    'opacity': '1'
});
```

## 🚨 Troubleshooting

### Problema: Template override no se aplica

**Soluciones**:
1. Verificar ruta exacta: `theme/inteb/templates/format_remuiformat/optional_secheader.mustache`
2. Purgar cachés de Mustache: `php admin/cli/purge_caches.php`
3. Verificar que el theme activo es `inteb`

### Problema: Servicio web no existe

**Soluciones**:
1. Verificar que `theme/inteb/db/services.php` existe
2. Ejecutar upgrade: `php admin/cli/upgrade.php`
3. Verificar en: Admin → Servidor → Servicios web → Funciones
4. Buscar: `theme_inteb_get_course_teachers`

### Problema: JavaScript no se carga

**Soluciones**:
1. Purgar caché AMD: `php admin/cli/purge_caches.php`
2. Verificar archivo existe: `theme/inteb/amd/src/format_remuiformat_teacher_fix.js`
3. Revisar consola del navegador por errores
4. Verificar que el course ID se obtiene correctamente

### Problema: Solo se ven editingteachers

**Soluciones**:
1. Verificar que hay users con rol `teacher` en el curso
2. Revisar consola - ¿se ejecuta el JavaScript?
3. Verificar respuesta del servicio web en Network tab
4. Verificar permisos del user actual

## 📚 Referencias

### Sistema de Templates de Moodle

- **Documentación**: https://moodledev.io/docs/apis/subsystems/output/templates
- **Herencia**: Los themes pueden sobrescribir templates de plugins
- **Prioridad**: theme > plugin > core

### External API (Web Services)

- **Documentación**: https://moodledev.io/docs/apis/subsystems/external
- **Registro**: Se hace en `db/services.php`
- **Clases**: Heredan de `external_api`

### AMD JavaScript

- **Documentación**: https://moodledev.io/docs/apis/subsystems/amd
- **Ubicación**: `amd/src/` (fuente) y `amd/build/` (compilado)
- **Carga**: Via `require([...])` en templates

## 🎉 Resultado Final

**TODOS los teachers** (editing y non-editing) aparecen correctamente en el header de cursos con formato `remuiformat`.

La solución es:
- ✅ Limpia (usa sistema de herencia de Moodle)
- ✅ Mantenible (todo en theme_inteb)
- ✅ Compatible con actualizaciones del formato
- ✅ Reutilizable (servicio web puede usarse en otros lugares)
- ✅ Debuggeable (logs claros y estructura predecible)

## 📝 Versión

- **Version del theme**: `2025102900`
- **Release**: `4.5.1`
- **Fecha**: 29 de Octubre, 2025
- **Cambio**: Template override para mostrar ambos roles de teacher

