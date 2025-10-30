# Mostrar TODOS los Profesores en theme_inteb

## 📋 Resumen

Esta implementación extiende `theme_remui` y `format_remuiformat` para mostrar **TODOS** los profesores (editingteacher + teacher) cuando se usa `theme_inteb`.

## ✅ Componentes Implementados

### 1. Course Cards (Tarjetas de Curso) - ✅ FUNCIONA SIN MODIFICACIONES EXTERNAS

**Archivos en theme_inteb:**
- `classes/coursehandler.php` - Extiende `theme_remui_coursehandler`
- `templates/theme_remui/course_card_grid.mustache`
- `templates/theme_remui/course_card_list.mustache`
- `templates/theme_remui/course_card_summary.mustache`
- `scss/inteb.scss` (SECCIÓN 25, líneas 1694-1708)

**Cómo funciona:**
```php
// theme/inteb/classes/coursehandler.php extiende theme_remui_coursehandler
class theme_inteb_coursehandler extends theme_remui_coursehandler {
    public function get_courses($filters = array()) {
        $courses = parent::get_courses($filters);
        // Agrega TODOS los profesores usando capabilities:
        // - mod/folder:managefiles → editing teachers
        // - moodle/course:viewhiddenactivities → non-editing teachers
    }
}
```

✅ **Extensión limpia de clase padre, sin modificar theme_remui**

---

### 2. Course Headers (format_remuiformat) - ⚠️ REQUIERE PATCH MÍNIMO

**Archivos en theme_inteb:**
- `classes/format_remuiformat_helper.php` - Lógica para obtener TODOS los profesores
- `templates/format_remuiformat/optional_secheader.mustache` - Template sobrescrito
- `scss/inteb.scss` (SECCIÓN 26, líneas 1711-1727)

**El problema:**
`format_remuiformat` usa una función global `get_enrolled_teachers_context_formate()` en `lib.php` que NO es una clase que podamos extender. Esta función prepara el contexto de profesores que se pasa al template.

**Opciones consideradas:**

| Opción | ¿Funciona? | Razón |
|--------|-----------|-------|
| Extender clase de format_remuiformat | ❌ | No es una clase, es función global |
| Override de renderer | ❌ | Los renderables tienen propiedades privadas |
| Template override sin modificar contexto | ❌ | El template recibe datos ya procesados |
| Crear plugin local como intermediario | ❌ | Innecesariamente complejo |
| Patch mínimo a format_remuiformat | ✅ | Único método viable |

**Solución implementada:**

Se requiere agregar **7 líneas** en `course/format/remuiformat/lib.php` (línea 881):

```php
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER, $PAGE;

    // PATCH: Si theme_inteb está activo, usar helper extendido
    if ($PAGE->theme->name === 'inteb' && class_exists('\\theme_inteb\\format_remuiformat_helper')) {
        return \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context($course, $frontlineteacher);
    }

    // Código original continúa sin cambios...
    $courseid = $course->id;
    // ...
}
```

✅ **Solo 7 líneas, no afecta otros temas, fácil de mantener**

---

## 📦 Instalación

### Paso 1: Aplicar Patch a format_remuiformat

**Opción A: Edición Manual** (Recomendado)

Editar `/course/format/remuiformat/lib.php`, línea ~880-882:

**ANTES:**
```php
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER;

    $courseid = $course->id;
```

**DESPUÉS:**
```php
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER, $PAGE;

    // PATCH: Si theme_inteb está activo, usar helper extendido
    if ($PAGE->theme->name === 'inteb' && class_exists('\\theme_inteb\\format_remuiformat_helper')) {
        return \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context($course, $frontlineteacher);
    }

    // Código original continúa sin cambios...
    $courseid = $course->id;
```

**Opción B: Aplicar Patch Automático**

```bash
cd /path/to/moodle
cat > /tmp/format_remuiformat_inteb.patch <<'EOF'
--- a/course/format/remuiformat/lib.php
+++ b/course/format/remuiformat/lib.php
@@ -878,8 +878,15 @@
  * @return context array
  */
 function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
-    global $OUTPUT, $CFG, $USER;
+    global $OUTPUT, $CFG, $USER, $PAGE;

+    // PATCH: Si theme_inteb está activo, usar helper extendido
+    if ($PAGE->theme->name === 'inteb' && class_exists('\\theme_inteb\\format_remuiformat_helper')) {
+        return \theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context($course, $frontlineteacher);
+    }
+
+    // Código original continúa sin cambios...
     $courseid = $course->id;

     $usergroups = groups_get_user_groups($courseid, $USER->id);
EOF

patch -p1 < /tmp/format_remuiformat_inteb.patch
```

### Paso 2: Limpiar Cachés

```bash
php admin/cli/purge_caches.php
```

O desde la interfaz: **Administración del sitio → Desarrollo → Limpiar todas las cachés**

---

## 🧪 Verificación

### Test 1: Course Cards
1. Ir al Dashboard con theme_inteb activo
2. Verificar tarjetas de curso
3. Debe mostrar TODOS los profesores (editing + non-editing)
4. Los non-editing deben tener ligera transparencia (opacity: 0.9)

### Test 2: Course Headers (format_remuiformat)
1. Crear curso con formato `remuiformat`
2. Asignar múltiples profesores:
   - Al menos 1 con rol `editingteacher`
   - Al menos 1 con rol `teacher` (non-editing)
3. Entrar al curso
4. Verificar el header
5. Debe mostrar TODOS los profesores

---

## 🎨 Estilos Aplicados

**Filosofía:** Respetar estilos originales, solo agregar diferenciación mínima.

```scss
// Solo opacity para diferenciar non-editing teachers
.non-editing-teacher {
    opacity: 0.9;
}
```

**Sin badges, sin iconos, sin decoraciones adicionales** - Solo funcionalidad extendida.

---

## 🔄 Mantenimiento

### Al Actualizar format_remuiformat

1. Verificar si la función `get_enrolled_teachers_context_formate()` cambió de ubicación
2. Re-aplicar el patch si es necesario (son solo 7 líneas)
3. Limpiar cachés

### Desinstalar/Revertir

**Revertir patch:**
```bash
cd /path/to/moodle/course/format/remuiformat
# Editar lib.php manualmente, eliminar líneas agregadas (las 7 líneas del if)
```

**Los archivos de theme_inteb:**
- NO necesitan eliminarse
- Course cards seguirán funcionando (extensión limpia)
- Solo el header de format_remuiformat volverá a comportamiento original

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────┐
│              COURSE CARDS (Dashboard)                   │
│                                                         │
│  theme_remui_coursehandler (original)                  │
│             ↓ extends                                   │
│  theme_inteb_coursehandler                             │
│   → get_courses() sobrescribe método                   │
│   → Obtiene TODOS los profesores                       │
│   → Templates sobrescritos renderizan                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│        COURSE HEADERS (format_remuiformat)              │
│                                                         │
│  format_remuiformat/lib.php                            │
│   get_enrolled_teachers_context_formate()              │
│             ↓ (con patch)                              │
│   Detecta: ¿theme_inteb activo?                        │
│             ↓ sí                                       │
│   theme_inteb\format_remuiformat_helper                │
│     ::get_enrolled_teachers_context()                  │
│   → Obtiene TODOS los profesores                       │
│   → Template sobrescrito renderiza                     │
└─────────────────────────────────────────────────────────┘
```

---

## ❓ FAQ

**P: ¿Por qué no se puede extender como con coursehandler.php?**
R: Porque `coursehandler.php` es una clase. La función `get_enrolled_teachers_context_formate()` de format_remuiformat es una función global, no una clase que podamos extender.

**P: ¿Por qué no crear un plugin local como intermediario?**
R: Sería innecesariamente complejo. El patch directo es más simple y mantenible.

**P: ¿El patch afecta otros temas?**
R: No. Solo se activa cuando `$PAGE->theme->name === 'inteb'`.

**P: ¿Qué pasa si no aplico el patch?**
R: Las course cards funcionarán perfectamente. Solo los headers de format_remuiformat seguirán mostrando solo editing teachers.

**P: ¿Es seguro modificar format_remuiformat?**
R: Es un patch mínimo de 7 líneas con validación condicional. No rompe funcionalidad existente.

---

## 📊 Capabilities Utilizadas

| Capability | Detecta | Roles típicos |
|-----------|---------|---------------|
| `mod/folder:managefiles` | Editing teachers | editingteacher, manager |
| `moodle/course:viewhiddenactivities` | Non-editing teachers | teacher |

---

## 📄 Archivos Clave

```
theme/inteb/
├── classes/
│   ├── coursehandler.php                     ← Extiende theme_remui ✅
│   └── format_remuiformat_helper.php         ← Helper para format_remuiformat ✅
├── templates/
│   ├── theme_remui/
│   │   ├── course_card_grid.mustache         ← Override ✅
│   │   ├── course_card_list.mustache         ← Override ✅
│   │   └── course_card_summary.mustache      ← Override ✅
│   └── format_remuiformat/
│       └── optional_secheader.mustache       ← Override ✅
└── scss/
    └── inteb.scss                            ← Estilos mínimos ✅

course/format/remuiformat/
└── lib.php                                   ← Requiere patch (+7 líneas) ⚠️
```

---

## 📝 Licencia

GPL v3 o posterior (compatible con Moodle)

---

## 👤 Desarrollo

- **Cliente:** INTEB
- **Fecha:** Enero 2025
- **Versión:** 1.0.1
