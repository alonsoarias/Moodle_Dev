# Documento de Analisis - Fase 1
## Plugin format_nexusformat (Nexus Format)

**Fecha:** 2026-01-06
**Compatibilidad:** Moodle 4.x
**Ubicacion:** `course/format/nexusformat/`

---

## 1. Hallazgos por Subfase

### 1.1 Subsistema de Formatos de Curso

#### Clase Base
- **Ubicacion:** `course/format/classes/base.php`
- **Namespace:** `core_courseformat`
- **Clase principal:** `core_courseformat\base` (abstracta)

#### Jerarquia de Clases
```
core_courseformat\base (abstracta)
    └── format_topics (extiende base)
    └── format_weeks (extiende base)
    └── format_remuiformat (extiende base)
    └── format_nexusformat (nuestra implementacion)
```

#### Metodos Clave a Implementar
| Metodo | Proposito | Valor para Nexus |
|--------|-----------|------------------|
| `uses_sections()` | Define si usa secciones | `true` |
| `uses_course_index()` | Habilita/deshabilita courseindex nativo | `false` (CRITICO) |
| `uses_indentation()` | Permite indentacion de actividades | `false` |
| `supports_ajax()` | Soporte AJAX | `true` con `capable = true` |
| `supports_components()` | Soporte para componentes reactivos | `true` |
| `get_view_url()` | URL para ver secciones | Personalizado |
| `course_format_options()` | Opciones de configuracion | Personalizado |
| `get_renderer()` | Obtiene el renderer | Usa renderer propio |
| `get_output_classname()` | Obtiene clases de output | Personalizado |

#### Ciclo de Vida del Renderizado
1. `course/view.php` carga el curso
2. `course_get_format($course)` instancia el formato
3. `format.php` del plugin se ejecuta
4. Se obtiene el renderer con `$PAGE->get_renderer('format_nexusformat')`
5. Se crea la clase de output (ej: `content`)
6. Se renderiza con templates Mustache

### 1.2 Actividades y Recursos

#### Estructura de Modulos
- **Ubicacion:** `mod/` (quiz, assign, forum, etc.)
- **Info de modulo:** Clase `cm_info` contiene toda la metadata
- **Acceso via:** `get_fast_modinfo($course)` retorna `course_modinfo`

#### Obtencion de Actividades
```php
$modinfo = get_fast_modinfo($course);
$cms = $modinfo->get_cms();           // Todos los course modules
$sections = $modinfo->get_sections(); // Modulos por seccion
$section_info = $modinfo->get_section_info($sectionnum); // Info de seccion
```

#### Propiedades de cm_info Relevantes
- `$cm->id` - ID del course module
- `$cm->modname` - Nombre del modulo (quiz, assign, etc.)
- `$cm->name` - Nombre de la actividad
- `$cm->url` - URL de la actividad
- `$cm->visible` - Visibilidad
- `$cm->uservisible` - Visible para el usuario actual
- `$cm->completion` - Tipo de completion tracking
- `$cm->completionstate` - Estado de completion

#### Renderizado de Actividades
- **Clase:** `core_courseformat\output\local\content\cm`
- **Template:** `course/format/templates/local/content/cm.mustache`
- Soporta: completion, visibility, availability, dates, groupmode

### 1.3 APIs Relevantes

#### Completion API
**Ubicacion:** `lib/completionlib.php`

**Constantes de Estado:**
```php
COMPLETION_INCOMPLETE = 0  // No completado
COMPLETION_COMPLETE = 1    // Completado
COMPLETION_COMPLETE_PASS = 2  // Completado con nota aprobatoria
COMPLETION_COMPLETE_FAIL = 3  // Completado con nota reprobatoria
```

**Constantes de Tracking:**
```php
COMPLETION_TRACKING_NONE = 0     // Sin tracking
COMPLETION_TRACKING_MANUAL = 1   // Manual (checkbox)
COMPLETION_TRACKING_AUTOMATIC = 2 // Automatico
```

**Obtencion de Progreso:**
```php
$completion = new completion_info($course);
$progress = $completion->get_progress_all(); // Progreso de todos
$data = $completion->get_data($cm, true, $userid); // Estado por actividad
```

#### Gradebook API
**Ubicacion:** `lib/gradelib.php`, `lib/grade/grade_item.php`

**Identificar Actividades Calificables:**
```php
// Obtener grade_items del curso
$grade_items = grade_item::fetch_all([
    'courseid' => $courseid,
    'itemtype' => 'mod'
]);

// Por cada grade_item:
// - $item->itemmodule = 'quiz', 'assign', etc.
// - $item->iteminstance = ID de la instancia
// - $item->gradetype = GRADE_TYPE_VALUE, GRADE_TYPE_SCALE, etc.
// - $item->grademax = nota maxima
// - $item->gradepass = nota para aprobar
```

**Obtener Calificaciones:**
```php
$grades = grade_get_grades($courseid, 'mod', $modname, $instanceid, $userid);
// Retorna: items[itemnumber]->grades[userid]->grade
```

#### File API
**Ubicacion:** `lib/filelib.php`, `lib/filestorage/file_storage.php`

**Manejo de Archivos:**
```php
$fs = get_file_storage();
$files = $fs->get_area_files($contextid, 'format_nexusformat', 'notes', $itemid);
$fs->create_file_from_storedfile($fileinfo, $sourcefile);
```

**Componentes para archivos:**
- `component`: 'format_nexusformat'
- `filearea`: 'notes' (para apuntes), 'attachments' (para comentarios)
- `itemid`: ID del registro relacionado

#### External API (Web Services)
**Ubicacion:** `db/services.php`

**Estructura:**
```php
$functions = [
    'format_nexusformat_save_note' => [
        'classname' => 'format_nexusformat\external\save_note',
        'methodname' => 'execute',
        'description' => 'Save a user note',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
    ],
];
```

**Clase External:**
```php
class save_note extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'content' => new external_value(PARAM_RAW, 'Note content'),
        ]);
    }

    public static function execute($courseid, $content) {
        // Implementacion
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'id' => new external_value(PARAM_INT, 'Note ID'),
        ]);
    }
}
```

### 1.4 Courseindex

#### Mecanismo de Control
**Archivo clave:** `course/format/classes/base.php:484-486`

```php
public function uses_course_index() {
    return false; // Por defecto NO usa courseindex
}
```

**Donde se evalua:** `lib/classes/output/core_renderer.php:512`

```php
if (
    $this->page->theme->usescourseindex &&
    $courseformat->uses_course_index() &&
    $this->page->pagelayout !== 'frametop'
) {
    return ''; // No muestra navigation links
}
```

#### Decision para Nexus Format
- **Retornar `false`** en `uses_course_index()` para ocultar el courseindex nativo
- El formato proporciona su propio indice en la columna derecha
- No se requiere CSS adicional para ocultar - el mecanismo nativo lo gestiona

#### Templates del Courseindex (referencia)
- `course/format/templates/local/courseindex/courseindex.mustache`
- `course/format/templates/local/courseindex/section.mustache`
- `course/format/templates/local/courseindex/cm.mustache`

### 1.5 Estandares de Codificacion

#### Estructura de Archivos Requerida
```
nexusformat/
├── version.php              # Obligatorio - Version del plugin
├── lib.php                  # Obligatorio - Clase format_nexusformat
├── format.php               # Obligatorio - Punto de entrada de renderizado
├── lang/
│   ├── en/
│   │   └── format_nexusformat.php  # Strings en ingles
│   └── es/
│       └── format_nexusformat.php  # Strings en espanol
├── db/
│   ├── install.xml          # Estructura de tablas
│   ├── access.php           # Capabilities (opcional)
│   ├── services.php         # Web services AJAX
│   └── upgrade.php          # Upgrades de BD
├── classes/
│   ├── output/
│   │   ├── renderer.php     # Renderer del formato
│   │   └── courseformat/
│   │       └── content.php  # Clase de contenido principal
│   ├── external/            # Clases de web services
│   └── privacy/
│       └── provider.php     # GDPR compliance
├── templates/               # Templates Mustache
├── styles.css               # Estilos CSS
├── amd/
│   └── src/                 # Modulos JavaScript AMD
└── settings.php             # Configuracion de admin (opcional)
```

#### Convenciones de Nombres
- **Clase principal:** `format_nexusformat` (extiende `core_courseformat\base`)
- **Namespace para clases:** `format_nexusformat\`
- **Renderer:** `format_nexusformat\output\renderer` (extiende `section_renderer`)
- **Output classes:** `format_nexusformat\output\courseformat\content`
- **Strings:** `get_string('key', 'format_nexusformat')`

#### version.php
```php
defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026010600;        // YYYYMMDDXX
$plugin->requires  = 2022041900;        // Moodle 4.0+
$plugin->component = 'format_nexusformat';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '1.0.0';
```

#### Seguridad
- Usar `require_login()` y `require_capability()`
- Usar `sesskey()` para acciones POST
- Usar `clean_param()` y `required_param()` / `optional_param()`
- Escapar output con `format_string()`, `format_text()`, `s()`
- Usar prepared statements con `$DB->get_record()`, etc.

---

## 2. Decisiones Tecnicas

### 2.1 Deshabilitar Courseindex
**Decision:** Retornar `false` en `uses_course_index()`
**Justificacion:** El formato proporciona su propio indice lateral, haciendo redundante el courseindex nativo.

### 2.2 Arquitectura de Renderizado
**Decision:** Usar el sistema de output classes + templates Mustache
**Justificacion:** Es el estandar moderno de Moodle 4.x, permite override por themes, y soporta componentes reactivos.

### 2.3 Layout de Dos Columnas
**Decision:** Implementar via CSS Grid/Flexbox en styles.css
**Justificacion:** Responsive, mantenible, no requiere modificar el layout del theme.

### 2.4 Navegacion de Actividades
**Decision:** Usar JavaScript AMD para carga dinamica
**Justificacion:** Mejor UX sin recarga de pagina completa, compatible con el sistema reactivo de Moodle.

### 2.5 Sistema de Apuntes y Comentarios
**Decision:** Tablas propias + Web Services AJAX + File API
**Justificacion:**
- Tablas propias para datos estructurados
- Web Services para operaciones CRUD sin recarga
- File API para adjuntos (imagenes, archivos)

### 2.6 Actividades Calificables
**Decision:** Consultar `grade_item` con `itemtype = 'mod'`
**Justificacion:** La tabla `grade_items` es la fuente oficial de actividades con calificacion configurada.

---

## 3. Arquitectura Propuesta

### 3.1 Estructura de Archivos

```
course/format/nexusformat/
├── version.php
├── lib.php                          # class format_nexusformat
├── format.php                       # Entry point
├── settings.php                     # Admin settings
├── styles.css                       # Estilos principales
│
├── lang/
│   ├── en/format_nexusformat.php
│   └── es/format_nexusformat.php
│
├── db/
│   ├── install.xml                  # Tablas: notes, comments, comment_likes
│   ├── access.php                   # Capabilities
│   ├── services.php                 # AJAX web services
│   └── upgrade.php
│
├── classes/
│   ├── output/
│   │   ├── renderer.php             # Renderer principal
│   │   └── courseformat/
│   │       ├── content.php          # Layout dos columnas
│   │       ├── sidebar.php          # Columna derecha (indice)
│   │       ├── activitycontent.php  # Columna izquierda
│   │       └── comments.php         # Sistema de comentarios
│   ├── external/
│   │   ├── save_note.php
│   │   ├── delete_note.php
│   │   ├── get_notes.php
│   │   ├── save_comment.php
│   │   ├── delete_comment.php
│   │   ├── toggle_like.php
│   │   └── get_comments.php
│   └── privacy/
│       └── provider.php
│
├── templates/
│   ├── layout.mustache              # Layout principal
│   ├── sidebar.mustache             # Indice lateral
│   ├── sidebar_tabs.mustache        # Pestanas
│   ├── progress_bar.mustache        # Barra de progreso
│   ├── unit_list.mustache           # Lista de unidades
│   ├── lesson_card.mustache         # Tarjeta de leccion
│   ├── activity_card.mustache       # Tarjeta de actividad calificable
│   ├── notes_tab.mustache           # Pestana apuntes
│   ├── note_item.mustache           # Item de apunte
│   ├── note_editor.mustache         # Editor de apuntes
│   ├── comments_section.mustache    # Seccion de comentarios
│   ├── comment_item.mustache        # Comentario individual
│   └── comment_editor.mustache      # Editor de comentarios
│
└── amd/
    └── src/
        ├── sidebar.js               # Logica del sidebar
        ├── tabs.js                   # Cambio de pestanas
        ├── units.js                  # Expandir/colapsar unidades
        ├── navigation.js            # Navegacion de lecciones
        ├── notes.js                  # CRUD de apuntes
        ├── comments.js              # CRUD de comentarios
        └── progress.js              # Actualizacion de progreso
```

### 3.2 Esquema de Base de Datos

#### Tabla: format_nexusformat_notes
```sql
CREATE TABLE mdl_format_nexusformat_notes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    courseid BIGINT NOT NULL,
    content LONGTEXT NOT NULL,
    contentformat TINYINT DEFAULT 1,
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,

    FOREIGN KEY (userid) REFERENCES mdl_user(id),
    FOREIGN KEY (courseid) REFERENCES mdl_course(id),
    INDEX idx_user_course (userid, courseid)
);
```

#### Tabla: format_nexusformat_comments
```sql
CREATE TABLE mdl_format_nexusformat_comments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT NOT NULL,
    courseid BIGINT NOT NULL,
    cmid BIGINT NOT NULL,
    parentid BIGINT DEFAULT NULL,
    content LONGTEXT NOT NULL,
    contentformat TINYINT DEFAULT 1,
    timecreated BIGINT NOT NULL,
    timemodified BIGINT NOT NULL,

    FOREIGN KEY (userid) REFERENCES mdl_user(id),
    FOREIGN KEY (courseid) REFERENCES mdl_course(id),
    FOREIGN KEY (cmid) REFERENCES mdl_course_modules(id),
    FOREIGN KEY (parentid) REFERENCES mdl_format_nexusformat_comments(id),
    INDEX idx_cmid (cmid),
    INDEX idx_parentid (parentid)
);
```

#### Tabla: format_nexusformat_likes
```sql
CREATE TABLE mdl_format_nexusformat_likes (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    commentid BIGINT NOT NULL,
    userid BIGINT NOT NULL,
    timecreated BIGINT NOT NULL,

    FOREIGN KEY (commentid) REFERENCES mdl_format_nexusformat_comments(id),
    FOREIGN KEY (userid) REFERENCES mdl_user(id),
    UNIQUE KEY unique_like (commentid, userid)
);
```

### 3.3 Flujo de Renderizado

```
1. Usuario accede a /course/view.php?id=X
   │
2. Moodle carga format_nexusformat via lib.php
   │
3. format.php se ejecuta
   │
4. Se instancia renderer y content class
   │
5. Content::export_for_template() prepara datos:
   │  ├── Secciones y actividades (modinfo)
   │  ├── Progreso de completion
   │  ├── Actividades calificables (grade_items)
   │  └── Datos de usuario
   │
6. Se renderiza layout.mustache:
   │  ├── Columna izquierda: contenido de actividad
   │  └── Columna derecha: sidebar con tabs
   │
7. JavaScript AMD se inicializa:
      ├── sidebar.js: tabs y navegacion
      ├── navigation.js: carga dinamica de contenido
      └── comments.js: interaccion de comentarios
```

---

## 4. Verificacion Pre-Fase 2

### Checklist de Conocimiento
- [x] Entendido el sistema de formatos y clase base
- [x] Identificado metodo para deshabilitar courseindex (`uses_course_index()`)
- [x] Conocida la estructura de cm_info y modinfo
- [x] Comprendida Completion API
- [x] Comprendida Gradebook API para actividades calificables
- [x] Conocida File API para adjuntos
- [x] Conocida External API para AJAX
- [x] Entendidos estandares de codificacion
- [x] Definida arquitectura de archivos
- [x] Disenado esquema de base de datos

### Riesgos Identificados
1. **Compatibilidad con themes:** El layout de dos columnas debe funcionar con Boost y otros themes. Mitigacion: usar CSS que no dependa de estructura especifica del theme.

2. **Rendimiento:** Cargar muchas actividades puede ser lento. Mitigacion: usar caching y carga perezosa.

3. **Conflictos con courseindex:** Aunque retornamos `false`, algunos themes podrian forzar su aparicion. Mitigacion: probar con multiples themes.

---

## 5. Siguiente Paso: Fase 2

Con este analisis completado, la Fase 2 procedera a crear:

1. **Archivos minimos:**
   - `version.php` - Definicion del plugin
   - `lib.php` - Clase `format_nexusformat` con `uses_course_index() = false`
   - `format.php` - Renderizado basico
   - `lang/en/format_nexusformat.php` - Strings

2. **Layout basico:**
   - `styles.css` - Grid de dos columnas
   - `templates/layout.mustache` - Estructura HTML

3. **Verificacion:**
   - Plugin instalable
   - Courseindex nativo oculto
   - Dos columnas visibles

---

**Documento generado para Fase 1 - Analisis**
**Listo para proceder a Fase 2 - Estructura Base**
