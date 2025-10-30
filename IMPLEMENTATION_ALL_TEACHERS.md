# Implementación: Mostrar TODOS los Profesores en theme_inteb

## Resumen

Esta implementación extiende la funcionalidad de `theme_remui` y `format_remuiformat` para mostrar **TODOS** los profesores (editingteacher y teacher) cuando se utiliza `theme_inteb`.

## Componentes Implementados

### 1. Course Cards (Tarjetas de Curso)

**Archivos modificados:**
- `theme/inteb/classes/coursehandler.php` - Extiende `theme_remui_coursehandler`
- `theme/inteb/templates/theme_remui/course_card_grid.mustache`
- `theme/inteb/templates/theme_remui/course_card_list.mustache`
- `theme/inteb/templates/theme_remui/course_card_summary.mustache`

**Funcionamiento:**
- El método `get_courses()` en `coursehandler.php` obtiene todos los profesores usando dos capabilities:
  - `mod/folder:managefiles` → editing teachers
  - `moodle/course:viewhiddenactivities` → non-editing teachers
- Combina ambos arrays, elimina duplicados y marca cada profesor con `is_editing_teacher`
- Los templates muestran todos los profesores con clases CSS diferenciadas

### 2. Course Header (format_remuiformat)

**Archivos creados/modificados:**
- `local/inteb_remuiformat_ext/` - Plugin local que actúa como puente
- `theme/inteb/classes/format_remuiformat_helper.php` - Clase helper con la lógica extendida
- `theme/inteb/templates/format_remuiformat/optional_secheader.mustache` - Template sobrescrito
- `course/format/remuiformat/lib.php` - **PARCHEADO** (7 líneas agregadas)

**Funcionamiento:**
1. El plugin `format_remuiformat` llama a `get_enrolled_teachers_context_formate()`
2. Esta función (parcheada) detecta si el tema es `inteb`
3. Si es `inteb`, delega a `local_inteb_get_enrolled_teachers_context_formate()`
4. Esta función wrapper llama a `\theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context()`
5. El helper obtiene TODOS los profesores con los mismos criterios que coursehandler
6. El template sobrescrito en theme_inteb muestra todos los profesores

### 3. Estilos CSS

**Archivo:** `theme/inteb/scss/inteb.scss`

**Secciones:**
- **SECCIÓN 25** (líneas 1694-1708): Estilos para course cards
- **SECCIÓN 26** (líneas 1711-1727): Estilos para format_remuiformat

**Filosofía de diseño:**
- Respeta completamente los estilos originales de remui y remuiformat
- Solo agrega `opacity: 0.9` a non-editing teachers para distinguirlos sutilmente
- No agrega badges, iconos ni decoraciones visuales adicionales

## Arquitectura de la Solución

```
┌─────────────────────────────────────────────────────────────────┐
│                      USER REQUEST                               │
│          (Ver curso con formato remuiformat)                    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              format_remuiformat/lib.php                         │
│  get_enrolled_teachers_context_formate()                        │
│                                                                 │
│  if ($PAGE->theme->name === 'inteb') {                         │
│      return local_inteb_get_enrolled_teachers_context_formate() │
│  }                                                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│        local/inteb_remuiformat_ext/lib.php                      │
│  local_inteb_get_enrolled_teachers_context_formate()            │
│  → Wrapper function                                             │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│   theme/inteb/classes/format_remuiformat_helper.php             │
│   get_enrolled_teachers_context()                               │
│   → Lógica principal:                                           │
│     - Obtener editing teachers (mod/folder:managefiles)         │
│     - Obtener non-editing teachers (viewhiddenactivities)       │
│     - Combinar y marcar cada uno                                │
│     - Retornar contexto completo                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  theme/inteb/templates/format_remuiformat/                      │
│  optional_secheader.mustache                                    │
│  → Renderiza todos los profesores con clases diferenciadas     │
└─────────────────────────────────────────────────────────────────┘
```

## Capacidades Utilizadas

### Editing Teachers
- **Capability:** `mod/folder:managefiles`
- **Roles típicos:** `editingteacher`, `manager`
- **Permisos:** Pueden editar contenido del curso

### Non-Editing Teachers
- **Capability:** `moodle/course:viewhiddenactivities`
- **Roles típicos:** `teacher` (sin privilegios de edición)
- **Permisos:** Pueden ver el curso y actividades ocultas, pero no editarlas

## Patch Aplicado a format_remuiformat

**Archivo:** `course/format/remuiformat/lib.php` (línea 880)

```php
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER, $PAGE;

    // PATCH: Check if theme_inteb is active and use extended functionality
    // This allows theme_inteb to show ALL teachers (editing + non-editing)
    if ($PAGE->theme->name === 'inteb' && function_exists('local_inteb_get_enrolled_teachers_context_formate')) {
        return local_inteb_get_enrolled_teachers_context_formate($course, $frontlineteacher);
    }

    // Original function code continues...
    $courseid = $course->id;
    // ...
}
```

**Impacto del patch:**
- ✅ Solo 7 líneas agregadas
- ✅ No modifica comportamiento para otros temas
- ✅ Usa conditional check para máxima compatibilidad
- ✅ Fácil de revertir si es necesario

## Instalación y Configuración

### 1. Instalar el Plugin Local

```bash
# El plugin ya está en su lugar
cd /path/to/moodle
php admin/cli/upgrade.php --non-interactive
```

O visitar: **Administración del sitio → Notificaciones**

### 2. Verificar el Patch

El patch ya ha sido aplicado a `course/format/remuiformat/lib.php`.

Para verificar:
```bash
grep -n "theme_inteb" course/format/remuiformat/lib.php
```

Debería mostrar línea 885.

### 3. Limpiar Cachés

```bash
php admin/cli/purge_caches.php
```

O desde la interfaz: **Administración del sitio → Desarrollo → Limpiar todas las cachés**

### 4. Verificar Funcionamiento

1. Crear un curso de prueba
2. Asignar múltiples profesores:
   - Al menos 1 con rol `editingteacher`
   - Al menos 1 con rol `teacher` (non-editing)
3. Configurar el curso con formato `remuiformat`
4. Ver el curso con `theme_inteb` activo
5. Verificar que se muestran TODOS los profesores en:
   - Tarjetas de curso (Dashboard)
   - Header del curso (cuando usas format_remuiformat)

## Mantenimiento

### Actualización de format_remuiformat

Si se actualiza el plugin `format_remuiformat`, verificar:
1. Que la función `get_enrolled_teachers_context_formate()` sigue en el mismo lugar
2. Que el patch sigue aplicado (líneas 883-887 en lib.php)
3. Re-aplicar el patch si es necesario:
   ```bash
   cd /path/to/moodle
   patch -p1 < local/inteb_remuiformat_ext/format_remuiformat.patch
   ```

### Desinstalación

Si necesitas revertir los cambios:

1. **Revertir el patch:**
   ```bash
   cd /path/to/moodle/course/format/remuiformat
   git checkout lib.php
   # O editar manualmente eliminando líneas 883-887
   ```

2. **Desinstalar el plugin local:**
   - Administración del sitio → Plugins → Plugins locales
   - INTEB Remuiformat Extension → Desinstalar
   - Eliminar `/local/inteb_remuiformat_ext/`

3. **Los templates y coursehandler de theme_inteb seguirán funcionando** para las tarjetas de curso.

## Testing

### Casos de Prueba

1. **✓ Course cards con múltiples profesores**
   - Resultado: Muestra todos (editing + non-editing) con ligera transparencia en non-editing

2. **✓ Course header (remuiformat) con múltiples profesores**
   - Resultado: Muestra todos en el header

3. **✓ Respeta estilos originales**
   - Resultado: No hay cambios visuales excepto opacity en non-editing

4. **✓ Compatibilidad con otros temas**
   - Resultado: format_remuiformat funciona normalmente con otros temas

5. **✓ Modo de grupos**
   - Resultado: Respeta las restricciones de grupos del usuario

## Archivos Clave

```
theme/inteb/
├── classes/
│   ├── coursehandler.php                          ← Extiende theme_remui
│   └── format_remuiformat_helper.php              ← Lógica para format_remuiformat
├── templates/
│   ├── theme_remui/
│   │   ├── course_card_grid.mustache              ← Template sobrescrito
│   │   ├── course_card_list.mustache              ← Template sobrescrito
│   │   └── course_card_summary.mustache           ← Template sobrescrito
│   └── format_remuiformat/
│       └── optional_secheader.mustache            ← Template sobrescrito
└── scss/
    └── inteb.scss                                 ← Estilos mínimos

local/inteb_remuiformat_ext/
├── version.php                                    ← Metadata del plugin
├── lib.php                                        ← Wrapper function
├── README.md                                      ← Documentación
└── format_remuiformat.patch                       ← Patch para aplicar

course/format/remuiformat/
└── lib.php                                        ← **PARCHEADO** (línea 883-887)
```

## Créditos

- **Desarrollador:** Claude Code (Anthropic)
- **Cliente:** INTEB
- **Fecha:** Enero 2025
- **Versión:** 1.0.0

## Licencia

GPL v3 o posterior (igual que Moodle)
