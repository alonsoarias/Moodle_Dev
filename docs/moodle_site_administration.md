# Administración del Sitio en Moodle

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Estructura de Directorios](#estructura-de-directorios)
3. [Arquitectura del Árbol de Administración](#arquitectura-del-árbol-de-administración)
4. [Sistema de Settings](#sistema-de-settings)
5. [Sistema de Permisos y Capacidades](#sistema-de-permisos-y-capacidades)
6. [Cómo los Plugins Registran Configuraciones](#cómo-los-plugins-registran-configuraciones)
7. [Flujo de Carga del Sistema](#flujo-de-carga-del-sistema)
8. [Plantillas de Renderizado](#plantillas-de-renderizado)
9. [Guía Práctica: Crear Settings en un Plugin](#guía-práctica-crear-settings-en-un-plugin)
10. [Referencia de Archivos Clave](#referencia-de-archivos-clave)

---

## Introducción

Moodle utiliza un sistema de administración del sitio robusto y extensible que permite:

- **Configuración centralizada**: Todos los ajustes del sistema se gestionan desde una interfaz unificada
- **Extensibilidad**: Los plugins pueden agregar sus propias configuraciones al árbol de administración
- **Control de acceso granular**: Basado en capacidades (capabilities) y roles
- **Persistencia**: Los valores se almacenan en la base de datos (`config` y `config_plugins`)

El sistema se basa en un **árbol jerárquico** de nodos que representan categorías, páginas de configuración y enlaces a páginas externas.

---

## Estructura de Directorios

### Directorio Principal `/admin`

```
/admin/
├── classes/                    # Clases PHP del componente admin
│   ├── admin/                  # Clases de admin settings
│   ├── external/               # Servicios web externos
│   ├── form/                   # Formularios de administración
│   ├── local/                  # Navegación local
│   ├── output/                 # Renderizadores de salida
│   ├── privacy/                # Proveedor de privacidad
│   ├── reportbuilder/          # Constructor de reportes
│   └── table/                  # Tablas de administración
│
├── settings/                   # Archivos de configuración por categoría
│   ├── top.php                 # Crea categorías principales (PRIMERO)
│   ├── plugins.php             # Carga configuración de plugins (ÚLTIMO)
│   ├── appearance.php          # Configuración de apariencia
│   ├── courses.php             # Configuración de cursos
│   ├── server.php              # Configuración del servidor
│   ├── security.php            # Configuración de seguridad
│   ├── users.php               # Configuración de usuarios
│   └── [otros archivos...]     # +25 archivos adicionales
│
├── roles/                      # Gestión de roles y permisos
│   ├── manage.php              # Administrar roles
│   ├── define.php              # Definir roles
│   ├── override.php            # Sobreescribir capacidades
│   ├── permissions.php         # Permisos de roles
│   └── lib.php                 # Funciones de utilidad
│
├── templates/                  # Plantillas Mustache para UI
│   ├── setting_*.mustache      # Plantillas por tipo de setting
│   ├── settings.mustache       # Página completa de settings
│   └── [50+ plantillas...]
│
├── cli/                        # Herramientas de línea de comandos
├── index.php                   # Página principal de administración
├── settings.php                # Procesa configuración guardada
├── lib.php                     # Funciones de utilidad
└── renderer.php                # Renderizado de UI de admin
```

### Archivo Central: `/lib/adminlib.php`

Este archivo (11,864+ líneas) es el corazón del sistema de administración. Define:

- Interfaces base del árbol de administración
- Todas las clases de nodos del árbol
- Todas las clases de tipos de settings
- Funciones globales para manejo del sistema

---

## Arquitectura del Árbol de Administración

El sistema de administración se organiza como un **árbol jerárquico** donde cada nodo puede ser una categoría, página de settings o enlace externo.

### Interfaces Base

```php
// Interfaz para cualquier elemento del árbol
interface part_of_admin_tree {
    public function locate($name);       // Busca un nodo por nombre
    public function prune($name);        // Elimina un nodo
    public function search($query);      // Busca texto en el árbol
    public function check_access();      // Verifica permisos de acceso
    public function is_hidden();         // Indica si está oculto
    public function show_save();         // Indica si mostrar botón guardar
}

// Interfaz para elementos que pueden tener hijos
interface parentable_part_of_admin_tree extends part_of_admin_tree {
    public function add($destinationname, $something, $beforesibling = null);
}
```

### Clases Principales del Árbol

#### 1. `admin_root` - Raíz del Árbol

**Ubicación**: `/lib/adminlib.php:1147`

```php
class admin_root extends admin_category {
    public $errors;              // Errores de validación
    public $search;              // Query de búsqueda actual
    public $fulltree;            // ¿Cargar árbol completo?
    public $loaded;              // ¿Árbol ya cargado?
    public $custom_defaults;     // Valores por defecto personalizados
}
```

Es el nodo raíz desde donde cuelgan todas las categorías principales.

#### 2. `admin_category` - Carpetas/Categorías

**Ubicación**: `/lib/adminlib.php:775`

```php
class admin_category implements parentable_part_of_admin_tree {
    public $name;                // Nombre interno (único)
    public $visiblename;         // Nombre visible al usuario
    public $hidden;              // ¿Está oculto?
    protected $children = [];    // Elementos hijos
    protected $category_cache;   // Cache de categorías
    public $sort;                // ¿Ordenar hijos alfabéticamente?
}
```

Representa carpetas en el menú de administración. Puede contener otras categorías, páginas de settings o páginas externas.

**Ejemplo de creación**:
```php
$ADMIN->add('root', new admin_category('users', new lang_string('users', 'admin')));
```

#### 3. `admin_settingpage` - Páginas de Configuración

**Ubicación**: `/lib/adminlib.php:1441`

```php
class admin_settingpage implements part_of_admin_tree {
    public $name;                // Nombre único
    public $visiblename;         // Nombre visible
    public $settings;            // stdClass con admin_setting objects
    public $req_capability;      // Capacidades requeridas para acceso
    public $context;             // Contexto de la página
    public $hidden;              // ¿Está oculto?
    protected $dependencies = [];// Dependencias entre settings
}
```

Agrupa múltiples `admin_setting` en una página de configuración.

**Ejemplo de creación**:
```php
$settingspage = new admin_settingpage('local_myplugin_settings',
    get_string('settings', 'local_myplugin'));

$settingspage->add(new admin_setting_configtext(...));
$settingspage->add(new admin_setting_configcheckbox(...));

$ADMIN->add('localplugins', $settingspage);
```

#### 4. `admin_externalpage` - Páginas Externas

**Ubicación**: `/lib/adminlib.php:1211`

```php
class admin_externalpage implements part_of_admin_tree {
    public $name;                // Nombre único
    public $visiblename;         // Nombre visible
    public $url;                 // URL a la página PHP
    public $req_capability;      // Capacidades requeridas
    public $context;             // Contexto
    public $hidden;              // ¿Está oculto?
}
```

Enlaza páginas PHP personalizadas en el árbol de administración.

**Ejemplo de creación**:
```php
$ADMIN->add('modules', new admin_externalpage(
    'managemods',
    get_string('managemodules'),
    "$CFG->wwwroot/$CFG->admin/modules.php",
    'moodle/site:config'  // Capacidad requerida
));
```

### Diagrama del Árbol

```
admin_root (raíz)
├── admin_category ('users')
│   ├── admin_settingpage ('userpolicies')
│   │   ├── admin_setting_configtext (...)
│   │   └── admin_setting_configcheckbox (...)
│   ├── admin_externalpage ('userbulk')
│   └── admin_category ('roles')
│       ├── admin_externalpage ('defineroles')
│       └── admin_externalpage ('assignroles')
│
├── admin_category ('courses')
│   ├── admin_settingpage ('coursesettings')
│   └── admin_externalpage ('coursecategories')
│
├── admin_category ('plugins')
│   ├── admin_category ('modsettings')
│   │   ├── admin_settingpage ('modsettingassign')
│   │   └── admin_settingpage ('modsettingquiz')
│   └── admin_category ('blocksettings')
│
└── [más categorías...]
```

---

## Sistema de Settings

### Clase Base Abstracta `admin_setting`

**Ubicación**: `/lib/adminlib.php:1707`

```php
abstract class admin_setting {
    public $name;               // Nombre único ('plugin/name' o 'name')
    public $visiblename;        // Nombre localizado
    public $description;        // Descripción (soporta Markdown)
    public $defaultsetting;     // Valor por defecto
    public $updatedcallback;    // Callback cuando se actualiza
    public $plugin;             // null = config core, else = nombre plugin
    public $nosave = false;     // Si true, no guarda (ej: headings)
    public $affectsmodinfo;     // ¿Requiere reconstruir cache?
    private $flags = [];        // admin_setting_flag objects

    // Métodos abstractos que cada tipo debe implementar
    abstract public function get_setting();           // Lee el valor
    abstract public function write_setting($data);    // Escribe el valor
    abstract public function output_html($data);      // Renderiza HTML
}
```

### Tipos de Settings Disponibles

| Clase | Descripción | Tipo de Dato |
|-------|-------------|--------------|
| `admin_setting_configtext` | Campo de texto simple | string |
| `admin_setting_configtextarea` | Área de texto multilínea | text |
| `admin_setting_confightmleditor` | Editor HTML completo | html |
| `admin_setting_configcheckbox` | Casilla de verificación | 0/1 |
| `admin_setting_configmulticheckbox` | Múltiples checkboxes | array |
| `admin_setting_configselect` | Menú desplegable | string |
| `admin_setting_configmultiselect` | Selección múltiple | array |
| `admin_setting_configfile` | Selector de archivo | string |
| `admin_setting_configdirectory` | Selector de directorio | string |
| `admin_setting_configpasswordunmask` | Contraseña con opción mostrar | string |
| `admin_setting_encryptedpassword` | Contraseña cifrada | encrypted |
| `admin_setting_configcolourpicker` | Selector de color | hex color |
| `admin_setting_configtime` | Selector de hora | time |
| `admin_setting_configduration` | Duración en tiempo | seconds |
| `admin_setting_heading` | Encabezado (no guarda) | N/A |
| `admin_setting_description` | Texto descriptivo (no guarda) | N/A |

### Almacenamiento de Valores

Los valores de configuración se almacenan en la base de datos:

| Tipo | Tabla | Identificador |
|------|-------|---------------|
| Settings de core | `mdl_config` | `name` |
| Settings de plugins | `mdl_config_plugins` | `plugin` + `name` |

**Lectura de valores**:
```php
// Settings de core
$value = get_config('core', 'sitename');
// o simplemente
$value = $CFG->sitename;

// Settings de plugins
$value = get_config('local_myplugin', 'mysetting');
```

**Escritura de valores**:
```php
// Settings de core
set_config('sitename', 'Mi Sitio Moodle');

// Settings de plugins
set_config('mysetting', 'valor', 'local_myplugin');
```

---

## Sistema de Permisos y Capacidades

### Capacidades de Administración Principales

Definidas en `/lib/db/access.php`:

| Capacidad | Descripción |
|-----------|-------------|
| `moodle/site:config` | Acceso completo a administración del sitio |
| `moodle/site:configview` | Ver configuración (solo lectura) |
| `moodle/site:manageblocks` | Gestionar bloques del sitio |
| `moodle/user:create` | Crear usuarios |
| `moodle/user:update` | Actualizar usuarios |
| `moodle/user:delete` | Eliminar usuarios |
| `moodle/role:manage` | Gestionar roles |
| `moodle/role:assign` | Asignar roles |

### Validación de Acceso

Cada nodo del árbol de administración puede requerir una o más capacidades:

```php
// En admin_settingpage::check_access()
public function check_access() {
    $context = empty($this->context)
        ? context_system::instance()
        : $this->context;

    foreach ($this->req_capability as $cap) {
        if (has_capability($cap, $context)) {
            return true;
        }
    }
    return false;
}
```

### Verificar Acceso de Administración

```php
// Verificar si el usuario puede acceder a admin
if (has_capability('moodle/site:config', context_system::instance())) {
    // Puede acceder
}

// Variable rápida para settings.php
if ($hassiteconfig) {
    // Agregar settings
}
```

### Gestión de Roles

Los archivos en `/admin/roles/` permiten:

- **manage.php**: Crear, editar y eliminar roles
- **define.php**: Definir capacidades de un rol
- **permissions.php**: Ver permisos de un contexto
- **override.php**: Sobreescribir capacidades en un contexto

---

## Cómo los Plugins Registran Configuraciones

### Método Principal: Archivo `settings.php`

Cada plugin puede crear un archivo `settings.php` en su directorio raíz:

```
/local/myplugin/
├── settings.php          ← Archivo de configuración
├── classes/
├── lib.php
├── version.php
└── lang/
```

Este archivo se carga automáticamente durante la construcción del árbol.

### Proceso de Carga

1. El sistema carga `/admin/settings/plugins.php`
2. Para cada tipo de plugin, obtiene la lista de plugins instalados
3. Llama `$plugin->load_settings($ADMIN, $parentnodename, $hassiteconfig)`
4. Cada plugin ejecuta su `settings.php`

**Código en `/admin/settings/plugins.php`**:
```php
// Ejemplo para plugins locales
$plugins = core_plugin_manager::instance()->get_plugins_of_type('local');
foreach ($plugins as $plugin) {
    $plugin->load_settings($ADMIN, 'localplugins', $hassiteconfig);
}
```

### Categorías Padre por Tipo de Plugin

| Tipo de Plugin | Categoría Padre |
|----------------|-----------------|
| `mod` | `modsettings` |
| `block` | `blocksettings` |
| `local` | `localplugins` |
| `auth` | `authsettings` |
| `enrol` | `enrolments` |
| `format` | `formatsettings` |
| `report` | `reports` |
| `tool` | `tools` |
| `theme` | `themes` |

---

## Flujo de Carga del Sistema

```
1. Usuario accede a /admin/index.php o /admin/settings.php
                        ↓
2. Se requiere /lib/adminlib.php
                        ↓
3. Se llama admin_get_root() para obtener el árbol
                        ↓
4. admin_get_root() crea nueva instancia admin_root()
                        ↓
5. Carga /admin/settings/top.php
   → Crea todas las categorías principales en orden
                        ↓
6. Carga todos los archivos en /admin/settings/*.php
   (appearance.php, courses.php, users.php, etc.)
   → Excepto top.php y plugins.php
                        ↓
7. Carga /admin/settings/plugins.php (ÚLTIMO)
   → Permite que plugins inyecten sus settings
                        ↓
8. Para cada tipo de plugin:
   → Obtiene lista de plugins instalados
   → Llama $plugin->load_settings()
   → Cada plugin carga su settings.php
                        ↓
9. Árbol completo construido ($ADMIN->loaded = true)
                        ↓
10. Renderiza la página solicitada
```

### Función Principal: `admin_get_root()`

**Ubicación**: `/lib/adminlib.php:8820`

```php
function admin_get_root($reload = false, $requirefulltree = true) {
    global $CFG, $DB, $OUTPUT, $ADMIN;

    if (is_null($ADMIN)) {
        $ADMIN = new admin_root($requirefulltree);
    }

    if (!$ADMIN->loaded) {
        // Cargar categorías principales primero
        require($CFG->dirroot.'/'.$CFG->admin.'/settings/top.php');

        // Cargar otros archivos de settings
        foreach (glob($CFG->dirroot.'/'.$CFG->admin.'/settings/*.php') as $file) {
            $filename = basename($file);
            if ($filename !== 'top.php' && $filename !== 'plugins.php') {
                require($file);
            }
        }

        // Cargar plugins al final
        require($CFG->dirroot.'/'.$CFG->admin.'/settings/plugins.php');

        $ADMIN->loaded = true;
    }

    return $ADMIN;
}
```

---

## Plantillas de Renderizado

### Ubicación de Plantillas

Las plantillas Mustache para la UI de administración están en `/admin/templates/`:

```
/admin/templates/
├── setting.mustache                  # Envoltorio genérico
├── settings.mustache                 # Página completa
├── setting_configtext.mustache       # Campo texto
├── setting_configcheckbox.mustache   # Checkbox
├── setting_configselect.mustache     # Dropdown
├── setting_configtextarea.mustache   # Área texto
├── setting_configpasswordunmask.mustache # Contraseña
├── setting_configcolourpicker.mustache   # Selector color
├── setting_heading.mustache          # Encabezado
├── setting_description.mustache      # Descripción
└── [más plantillas...]
```

### Ejemplo: Plantilla de Campo Texto

```mustache
{{!
    @template admin/setting_configtext
}}
<div class="form-group row {{#haserror}}has-danger{{/haserror}}"
     id="admin-{{name}}" data-setting="{{name}}">
    <label class="col-sm-3 col-form-label" for="{{id}}">
        {{{visiblename}}}
        {{#flags}}...{{/flags}}
    </label>
    <div class="col-sm-9">
        <input type="text"
               id="{{id}}"
               name="{{name}}"
               value="{{value}}"
               class="form-control {{#haserror}}is-invalid{{/haserror}}"
               {{#size}}size="{{size}}"{{/size}}>
        {{#haserror}}
        <div class="invalid-feedback">{{error}}</div>
        {{/haserror}}
        <div class="form-text text-muted">{{{description}}}</div>
    </div>
</div>
```

---

## Guía Práctica: Crear Settings en un Plugin

### Ejemplo Completo: Plugin Local

**Archivo**: `/local/myplugin/settings.php`

```php
<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    // 1. Crear categoría para el plugin
    $category = new admin_category(
        'local_myplugin',                              // Nombre único
        get_string('pluginname', 'local_myplugin')     // Nombre visible
    );
    $ADMIN->add('localplugins', $category);

    // 2. Crear página de settings general
    $settings = new admin_settingpage(
        'local_myplugin_settings',
        get_string('generalsettings', 'local_myplugin')
    );

    // 3. Agregar encabezado
    $settings->add(new admin_setting_heading(
        'local_myplugin/generalheading',
        get_string('general', 'local_myplugin'),
        get_string('general_desc', 'local_myplugin')
    ));

    // 4. Agregar campo de texto
    $settings->add(new admin_setting_configtext(
        'local_myplugin/apikey',                       // Nombre (plugin/setting)
        get_string('apikey', 'local_myplugin'),        // Etiqueta
        get_string('apikey_desc', 'local_myplugin'),   // Descripción
        '',                                             // Valor por defecto
        PARAM_ALPHANUMEXT                              // Tipo de parámetro
    ));

    // 5. Agregar checkbox
    $settings->add(new admin_setting_configcheckbox(
        'local_myplugin/enabled',
        get_string('enabled', 'local_myplugin'),
        get_string('enabled_desc', 'local_myplugin'),
        1                                              // Activado por defecto
    ));

    // 6. Agregar selector
    $options = [
        'option1' => get_string('option1', 'local_myplugin'),
        'option2' => get_string('option2', 'local_myplugin'),
        'option3' => get_string('option3', 'local_myplugin'),
    ];
    $settings->add(new admin_setting_configselect(
        'local_myplugin/mode',
        get_string('mode', 'local_myplugin'),
        get_string('mode_desc', 'local_myplugin'),
        'option1',                                     // Valor por defecto
        $options                                       // Opciones disponibles
    ));

    // 7. Agregar campo de duración
    $settings->add(new admin_setting_configduration(
        'local_myplugin/cachetime',
        get_string('cachetime', 'local_myplugin'),
        get_string('cachetime_desc', 'local_myplugin'),
        3600                                           // 1 hora por defecto
    ));

    // 8. Agregar contraseña
    $settings->add(new admin_setting_configpasswordunmask(
        'local_myplugin/secret',
        get_string('secret', 'local_myplugin'),
        get_string('secret_desc', 'local_myplugin'),
        ''
    ));

    // 9. Registrar la página en el árbol
    $ADMIN->add('local_myplugin', $settings);

    // 10. Opcional: Agregar página externa
    $ADMIN->add('local_myplugin', new admin_externalpage(
        'local_myplugin_manage',
        get_string('manage', 'local_myplugin'),
        new moodle_url('/local/myplugin/manage.php'),
        'moodle/site:config'                           // Capacidad requerida
    ));
}
```

### Usar los Valores de Configuración

```php
// En cualquier parte del código del plugin
$apikey = get_config('local_myplugin', 'apikey');
$enabled = get_config('local_myplugin', 'enabled');
$mode = get_config('local_myplugin', 'mode');
$cachetime = get_config('local_myplugin', 'cachetime');
```

### Crear Página Externa de Administración

**Archivo**: `/local/myplugin/manage.php`

```php
<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

// Configurar la página de administración
admin_externalpage_setup('local_myplugin_manage');

// Verificación de acceso ya se hace en admin_externalpage_setup()

$PAGE->set_title(get_string('manage', 'local_myplugin'));
$PAGE->set_heading(get_string('manage', 'local_myplugin'));

echo $OUTPUT->header();

// Contenido de la página
echo $OUTPUT->heading(get_string('manage', 'local_myplugin'));

// Tu código aquí...

echo $OUTPUT->footer();
```

---

## Referencia de Archivos Clave

| Archivo | Líneas | Descripción |
|---------|--------|-------------|
| `/lib/adminlib.php` | 11,864+ | Arquitectura completa del sistema |
| `/admin/index.php` | 300+ | Página principal de administración |
| `/admin/settings.php` | 150+ | Procesa guardar configuración |
| `/admin/settings/top.php` | 50+ | Crea categorías principales |
| `/admin/settings/plugins.php` | 500+ | Carga configuración de plugins |
| `/admin/renderer.php` | 2,500+ | Renderizado de UI |
| `/lib/classes/plugininfo/base.php` | 600+ | Base para información de plugins |
| `/admin/roles/manage.php` | 200+ | Gestión de roles |
| `/admin/roles/define.php` | 300+ | Definición de roles |
| `/lib/db/access.php` | 100+ | Capacidades del core |

---

## Resumen

El sistema de administración de Moodle es:

1. **Jerárquico**: Organizado como un árbol con categorías, páginas y settings
2. **Extensible**: Los plugins pueden agregar sus propias configuraciones
3. **Seguro**: Control de acceso basado en capacidades y roles
4. **Persistente**: Valores almacenados en base de datos
5. **Localizable**: Soporte completo para múltiples idiomas
6. **Templated**: UI renderizada con plantillas Mustache

El flujo típico para administrar el sitio es:

```
Usuario → /admin/index.php → Árbol de admin → Categoría →
Página de settings → Modifica valores → Guarda → Base de datos
```

Para desarrolladores, el proceso de agregar configuraciones es:

```
Crear settings.php → Agregar categoría/página → Agregar settings →
Usar get_config() para leer valores en el código
```
