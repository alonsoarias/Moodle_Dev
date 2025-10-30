# Patches para theme_inteb

Este directorio contiene patches necesarios para extender funcionalidad de plugins de terceros.

## format_remuiformat_show_all_teachers.patch

**Propósito:** Permite que theme_inteb muestre TODOS los profesores (editing + non-editing) en los headers de cursos con formato `format_remuiformat`.

**Ubicación del archivo a parchear:** `course/format/remuiformat/lib.php`

**Líneas modificadas:** ~880-882 (agrega 7 líneas)

### Aplicar el Patch

**Opción A: Aplicación Automática**

```bash
cd /path/to/moodle
patch -p1 < theme/inteb/patches/format_remuiformat_show_all_teachers.patch
```

**Opción B: Edición Manual** (Recomendado para mayor control)

Editar `/course/format/remuiformat/lib.php`, función `get_enrolled_teachers_context_formate()` (línea ~880):

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

### Verificar la Aplicación

```bash
grep -A 5 "theme_inteb" course/format/remuiformat/lib.php
```

Debería mostrar las líneas agregadas.

### Revertir el Patch

**Opción A:**
```bash
cd /path/to/moodle
patch -R -p1 < theme/inteb/patches/format_remuiformat_show_all_teachers.patch
```

**Opción B:**
Editar manualmente y eliminar las 7 líneas agregadas (el bloque if con el comentario PATCH).

### ¿Por qué es Necesario?

`format_remuiformat` usa una función global (no una clase) para obtener profesores. No podemos extenderla sin modificar el archivo original. El patch:
- Es mínimo (7 líneas)
- Solo se activa con theme_inteb
- No afecta otros temas
- Fácil de re-aplicar en actualizaciones

### Impacto

- **Con el patch:** Headers de format_remuiformat muestran TODOS los profesores
- **Sin el patch:** Headers de format_remuiformat muestran solo editing teachers (comportamiento original)

**Nota:** Las course cards (dashboard) funcionan sin este patch, ya que usan extensión de clase limpia.
