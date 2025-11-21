# Sistema de Instalación y Actualización de Moodle

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura General](#arquitectura-general)
3. [Sistema de Instalación](#sistema-de-instalación)
4. [Sistema de Actualización (Upgrade)](#sistema-de-actualización-upgrade)
5. [Sistema XMLDB - Esquema de Base de Datos](#sistema-xmldb---esquema-de-base-de-datos)
6. [Plugin Manager](#plugin-manager)
7. [Instalación y Actualización de Plugins](#instalación-y-actualización-de-plugins)
8. [Herramientas CLI](#herramientas-cli)
9. [Sistema de Cachés](#sistema-de-cachés)
10. [Archivos de Configuración de Plugins](#archivos-de-configuración-de-plugins)
11. [Referencia Rápida](#referencia-rápida)

---

## Introducción

Moodle implementa un sistema robusto de instalación y actualización que permite:

- **Instalación automatizada**: Creación completa de la base de datos y configuración inicial
- **Actualizaciones incrementales**: Migraciones de esquema mediante savepoints
- **Gestión de plugins**: Detección automática de plugins nuevos/actualizados
- **Validación de requisitos**: Verificación de ambiente antes de proceder
- **CLI y Web**: Soporte para instalación vía línea de comandos y navegador

---

## Arquitectura General

### Archivos Principales

| Archivo | Descripción |
|---------|-------------|
| `/install.php` | Punto de entrada de instalación web |
| `/admin/index.php` | Panel de administración y actualización |
| `/version.php` | Define versión del core de Moodle |
| `/config.php` | Configuración del sitio (generado) |
| `/lib/upgradelib.php` | Funciones principales de upgrade |
| `/lib/installlib.php` | Funciones de instalación |
| `/lib/environmentlib.php` | Validación de requisitos del sistema |

### Tablas de Base de Datos Clave

| Tabla | Descripción |
|-------|-------------|
| `config` | Configuración global del sitio |
| `config_plugins` | Configuración y versiones de plugins |
| `upgrade_log` | Log de actualizaciones |

### Estructura de `config`

```sql
-- Entradas clave en tabla config
name = 'version'        -- Versión del core instalado (ej: 2024100707.04)
name = 'release'        -- Nombre de release (ej: 4.5.7+)
name = 'branch'         -- Rama (ej: 405)
name = 'upgraderunning' -- Timestamp de fin esperado del upgrade
name = 'allversionshash'-- Hash de todas las versiones
```

### Estructura de `config_plugins`

```sql
-- Para cada plugin
plugin = 'mod_forum', name = 'version', value = '2024010100'
plugin = 'mod_forum', name = 'enabled', value = '1'
```

---

## Sistema de Instalación

### Flujo de Instalación Web

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE INSTALACIÓN                          │
└─────────────────────────────────────────────────────────────────┘

1. Usuario accede a /install.php
   └── Detecta que no existe config.php
          │
          ▼
2. FASE: INSTALL_WELCOME (0)
   └── Mostrar bienvenida y selección de idioma
          │
          ▼
3. FASE: INSTALL_ENVIRONMENT (1)
   └── Validar requisitos del sistema
       ├── Versión de PHP
       ├── Extensiones requeridas
       └── Permisos de directorios
          │
          ▼
4. FASE: INSTALL_PATHS (2)
   └── Configurar rutas
       ├── wwwroot (URL del sitio)
       └── dataroot (directorio de datos)
          │
          ▼
5. FASE: INSTALL_DOWNLOADLANG (3)
   └── Descargar paquete de idioma (opcional)
          │
          ▼
6. FASE: INSTALL_DATABASETYPE (4)
   └── Seleccionar tipo de base de datos
       ├── mysqli
       ├── pgsql
       ├── mssql
       └── oracle
          │
          ▼
7. FASE: INSTALL_DATABASE (5)
   └── Configurar conexión a BD
       ├── Host
       ├── Nombre de BD
       ├── Usuario
       ├── Contraseña
       └── Prefijo de tablas
          │
          ▼
8. FASE: INSTALL_SAVE (6)
   └── Ejecutar instalación
       ├── Generar config.php
       ├── install_core()
       └── Redirigir a /admin/index.php
```

### Punto de Entrada: `/install.php`

```php
// Ubicación: /install.php

// Definición de fases
define('INSTALL_WELCOME',       0);
define('INSTALL_ENVIRONMENT',   1);
define('INSTALL_PATHS',         2);
define('INSTALL_DOWNLOADLANG',  3);
define('INSTALL_DATABASETYPE',  4);
define('INSTALL_DATABASE',      5);
define('INSTALL_SAVE',          6);

// Flujo principal
// 1. Cargar strings de idioma
require("install/lang/{$CFG->lang}/install.php");

// 2. Validar ambiente
list($envstatus, $environmentresults) = check_moodle_environment(
    normalize_version($release),
    ENV_SELECT_RELEASE
);

// 3. Recolectar configuración via formularios

// 4. Ejecutar instalación
install_core($version, true);

// 5. Generar config.php
// 6. Redirigir a admin/index.php
```

### Archivo de Versión: `/version.php`

```php
// Ubicación: /version.php

$version  = 2024100707.04;              // Formato: YYYYMMDD.RR
$release  = '4.5.7+ (Build: 20251030)'; // Nombre legible
$branch   = '405';                       // Rama de desarrollo
$maturity = MATURITY_STABLE;            // Nivel de madurez

// Formato de versión:
// YYYYMMDD = Fecha de branching
// RR = Release increments
// .XX = Cambios incrementales
```

### Función Principal: `install_core()`

**Ubicación**: `/lib/upgradelib.php`

```php
/**
 * Instala el core de Moodle completamente
 *
 * @param float $version Versión a instalar
 * @param bool $verbose Mostrar progreso
 */
function install_core($version, $verbose) {
    global $CFG, $DB;

    // 1. LIMPIAR DIRECTORIOS
    // ----------------------
    remove_dir($CFG->cachedir . '', true);
    make_cache_directory('', true);
    remove_dir($CFG->localcachedir . '', true);
    make_localcache_directory('', true);
    remove_dir($CFG->tempdir . '', true);
    make_temp_directory('', true);

    try {
        // 2. INSTALAR ESQUEMA DESDE XMLDB
        // --------------------------------
        $DB->get_manager()->install_from_xmldb_file(
            "$CFG->libdir/db/install.xml"
        );

        // 3. MARCAR INICIO DE UPGRADE
        // ---------------------------
        upgrade_started();

        // 4. EJECUTAR POST-INSTALL
        // ------------------------
        require_once("$CFG->libdir/db/install.php");
        xmldb_main_install();  // Crea sitio, roles, usuarios iniciales

        // 5. GUARDAR VERSIÓN
        // ------------------
        upgrade_main_savepoint(true, $version, false);

        // 6. ACTUALIZAR COMPONENTES
        // -------------------------
        upgrade_component_updated('moodle', '', true);

        // 7. APLICAR CONFIGURACIÓN POR DEFECTO
        // ------------------------------------
        admin_apply_default_settings(NULL, true);

        // 8. PURGAR CACHÉS
        // ----------------
        cache_helper::purge_all();

    } catch (Exception $ex) {
        upgrade_handle_exception($ex);
    }
}
```

### Función `xmldb_main_install()`

**Ubicación**: `/lib/db/install.php`

```php
/**
 * Inicializa datos después de crear el esquema
 */
function xmldb_main_install() {
    global $CFG, $DB, $SITE;

    // 1. Verificar contexto de sistema
    $syscontext = context_system::instance(0, MUST_EXIST, false);

    // 2. Crear curso frontpage (sitio)
    $newsite = new stdClass();
    $newsite->fullname     = '';
    $newsite->shortname    = '';
    $newsite->summary      = NULL;
    $newsite->newsitems    = 3;
    $newsite->numsections  = 1;
    $newsite->category     = 0;
    $newsite->format       = 'site';
    $newsite->timecreated  = time();
    $newsite->timemodified = $newsite->timecreated;

    if (defined('SITEID')) {
        $newsite->id = SITEID;
        $DB->import_record('course', $newsite);
        $DB->get_manager()->reset_sequence('course');
    } else {
        $newsite->id = $DB->insert_record('course', $newsite);
        define('SITEID', $newsite->id);
    }

    // 3. Crear opciones de formato del sitio
    $DB->insert_record('course_format_options', [
        'courseid' => SITEID,
        'format' => 'site',
        'sectionid' => 0,
        'name' => 'numsections',
        'value' => $newsite->numsections
    ]);

    // 4. Inicializar roles y capacidades
    // ... (código adicional para roles, capabilities, etc.)
}
```

### Requisitos del Sistema: `/admin/environment.xml`

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<COMPATIBILITY_MATRIX>
  <MOODLE version="4.5">
    <PHP_SETTING name="memory_limit" value="256M" level="required"/>
    <PHP version="8.1.0" level="required"/>
    <PHP_EXTENSION name="iconv" level="required"/>
    <PHP_EXTENSION name="mbstring" level="required"/>
    <PHP_EXTENSION name="curl" level="required"/>
    <PHP_EXTENSION name="openssl" level="required"/>
    <PHP_EXTENSION name="tokenizer" level="required"/>
    <PHP_EXTENSION name="xmlrpc" level="optional"/>
    <PHP_EXTENSION name="soap" level="optional"/>
    <DATABASE name="mysql" version="8.0" level="required"/>
    <DATABASE name="postgres" version="13" level="required"/>
  </MOODLE>
</COMPATIBILITY_MATRIX>
```

Procesado por `/lib/environmentlib.php`:

```php
// Validar ambiente
list($envstatus, $environmentresults) = check_moodle_environment(
    normalize_version($release),
    ENV_SELECT_RELEASE
);

if (!$envstatus) {
    // Mostrar errores de requisitos no cumplidos
}
```

---

## Sistema de Actualización (Upgrade)

### Detección de Actualizaciones

**Ubicación**: `/admin/index.php`

```php
// El script detecta si necesita upgrade comparando versiones

require('../config.php');

// Invalidar cache de version.php
if (function_exists('opcache_invalidate')) {
    opcache_invalidate($CFG->dirroot . '/version.php', true);
}

// Detectar si hay major upgrade
if (is_major_upgrade_required() && isloggedin()) {
    redirect_if_major_upgrade_required();
}

// Comparar versión en código vs versión en BD
$codeversion = $version;      // De /version.php
$dbversion = $CFG->version;   // De tabla config

if ($codeversion > $dbversion) {
    // Necesita upgrade
    upgrade_core($codeversion, true);
}
```

### Flujo de Actualización

```
┌─────────────────────────────────────────────────────────────────┐
│                  FLUJO DE ACTUALIZACIÓN                          │
└─────────────────────────────────────────────────────────────────┘

1. admin/index.php detecta $CFG->version < version.php
          │
          ▼
2. Validar ambiente y dependencias
   ├── check_moodle_environment()
   └── all_plugins_ok()
          │
          ▼
3. upgrade_core()
   ├── Purgar cachés
   ├── Ejecutar local/preupgrade.php (si existe)
   ├── xmldb_main_upgrade($oldversion)
   │   └── Ejecutar cada upgrade step
   ├── upgrade_main_savepoint()
   ├── upgrade_component_updated()
   └── Limpiar contextos
          │
          ▼
4. upgrade_noncore()
   ├── Para cada tipo de plugin:
   │   └── upgrade_plugins()
   │       ├── Detectar plugins a actualizar
   │       ├── xmldb_{plugin}_upgrade()
   │       └── upgrade_plugin_savepoint()
   ├── external_update_services()
   ├── cache_helper::update_definitions()
   └── Guardar hashes de versión
          │
          ▼
5. Purgar cachés finalmente
          │
          ▼
6. Redirigir a admin/index.php (completado)
```

### Función `upgrade_core()`

**Ubicación**: `/lib/upgradelib.php`

```php
/**
 * Actualiza el core de Moodle
 *
 * @param float $version Nueva versión
 * @param bool $verbose Mostrar progreso
 */
function upgrade_core($version, $verbose) {
    global $CFG, $SITE, $DB, $COURSE;

    raise_memory_limit(MEMORY_EXTRA);

    require_once($CFG->libdir . '/db/upgrade.php');

    try {
        // 1. PURGAR CACHÉS
        cache_helper::purge_all(true);
        purge_all_caches();

        // 2. SCRIPT DE PRE-UPGRADE LOCAL
        $preupgradefile = "$CFG->dirroot/local/preupgrade.php";
        if (file_exists($preupgradefile)) {
            require($preupgradefile);
        }

        // 3. EJECUTAR UPGRADE DEL CORE
        $result = xmldb_main_upgrade($CFG->version);

        // 4. GUARDAR NUEVA VERSIÓN
        if ($version > $CFG->version) {
            upgrade_main_savepoint($result, $version, false);
        }

        // 5. RE-CARGAR SITE Y COURSE
        $SITE = $DB->get_record('course', ['id' => $SITE->id]);
        $COURSE = clone($SITE);

        // 6. ACTUALIZAR COMPONENTES
        upgrade_component_updated('moodle');
        cache_helper::update_definitions(true);

        // 7. PURGAR CACHÉS NUEVAMENTE
        cache_helper::purge_all(true);
        purge_all_caches();

        // 8. LIMPIAR CONTEXTOS
        context_helper::cleanup_instances();
        context_helper::create_instances(null, false);
        context_helper::build_all_paths(false);

    } catch (Exception $ex) {
        upgrade_handle_exception($ex);
    }
}
```

### Archivo de Upgrade del Core: `/lib/db/upgrade.php`

```php
/**
 * Ejecuta los pasos de actualización del core
 *
 * @param float $oldversion Versión anterior
 * @return bool Éxito
 */
function xmldb_main_upgrade($oldversion) {
    global $CFG, $DB;

    require_once($CFG->libdir . '/db/upgradelib.php');

    $dbman = $DB->get_manager();

    // Versión mínima requerida
    if ($oldversion < 2022112802) {
        echo("Necesitas actualizar a 4.1.2 o superior primero!\n");
        exit(1);
    }

    // UPGRADE STEP 1: Limpiar registros huérfanos
    if ($oldversion < 2022120900.01) {
        $DB->delete_records_select('role_assignments',
            'NOT EXISTS (
                SELECT r.id FROM {role} r
                WHERE r.id = {role_assignments}.roleid
            )'
        );

        // Marcar savepoint
        upgrade_main_savepoint(true, 2022120900.01);
    }

    // UPGRADE STEP 2: Agregar índice
    if ($oldversion < 2022121600.01) {
        $table = new xmldb_table('block_instances');
        $index = new xmldb_index('blocknameindex',
            XMLDB_INDEX_NOTUNIQUE, ['blockname']);

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_main_savepoint(true, 2022121600.01);
    }

    // UPGRADE STEP 3: Remover setting obsoleto
    if ($oldversion < 2023010300.00) {
        unset_config('useexternalyui');

        upgrade_main_savepoint(true, 2023010300.00);
    }

    // UPGRADE STEP 4: Agregar campo
    if ($oldversion < 2023021700.01) {
        $table = new xmldb_table('course');
        $field = new xmldb_field('pdfexportfont',
            XMLDB_TYPE_CHAR, '50',
            null, false, false, null, 'showcompletionconditions');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_main_savepoint(true, 2023021700.01);
    }

    return true;
}
```

### Savepoints: Marcando Progreso

Los savepoints permiten reintentar upgrades fallidos desde el último punto exitoso.

```php
// Para el core
upgrade_main_savepoint($result, $version, $allowabort = true);

// Para plugins
upgrade_plugin_savepoint($result, $version, $type, $plugin, $allowabort = true);

// Para módulos (shorthand)
upgrade_mod_savepoint($result, $version, $modname, $allowabort = true);

// Para bloques (shorthand)
upgrade_block_savepoint($result, $version, $blockname, $allowabort = true);

// Internamente guardan:
set_config('version', $version);  // Nueva versión
set_config('upgraderunning', $expected_end);  // Timestamp de fin esperado
```

---

## Sistema XMLDB - Esquema de Base de Datos

### Estructura de `install.xml`

**Ubicación**: `/lib/db/install.xml` (para el core)

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<XMLDB PATH="lib/db" VERSION="20240000"
       COMMENT="XMLDB file for Moodle core">
  <TABLES>

    <!-- Definición de tabla -->
    <TABLE NAME="course" COMMENT="Central course table">
      <FIELDS>
        <!-- Campo autoincrement -->
        <FIELD NAME="id" TYPE="int" LENGTH="10"
               NOTNULL="true" SEQUENCE="true"/>

        <!-- Campo entero con default -->
        <FIELD NAME="category" TYPE="int" LENGTH="10"
               NOTNULL="true" DEFAULT="0"/>

        <!-- Campo texto -->
        <FIELD NAME="fullname" TYPE="char" LENGTH="255"
               NOTNULL="true"/>

        <!-- Campo texto largo -->
        <FIELD NAME="summary" TYPE="text"
               NOTNULL="false"/>

        <!-- Campo decimal -->
        <FIELD NAME="grade" TYPE="number" LENGTH="10"
               DECIMALS="5" NOTNULL="false"/>
      </FIELDS>

      <KEYS>
        <!-- Clave primaria -->
        <KEY NAME="primary" TYPE="primary" FIELDS="id"/>

        <!-- Clave foránea -->
        <KEY NAME="category" TYPE="foreign"
             FIELDS="category"
             REFTABLE="course_categories"
             REFFIELDS="id"/>
      </KEYS>

      <INDEXES>
        <!-- Índice único -->
        <INDEX NAME="shortname" UNIQUE="true"
               FIELDS="shortname"/>

        <!-- Índice no único -->
        <INDEX NAME="category_idx" UNIQUE="false"
               FIELDS="category"/>

        <!-- Índice compuesto -->
        <INDEX NAME="cat_sort" UNIQUE="false"
               FIELDS="category,sortorder"/>
      </INDEXES>
    </TABLE>

  </TABLES>
</XMLDB>
```

### Clases XMLDB

**Ubicación**: `/lib/xmldb/`

| Clase | Archivo | Descripción |
|-------|---------|-------------|
| `xmldb_table` | `xmldb_table.php` | Representa una tabla |
| `xmldb_field` | `xmldb_field.php` | Representa un campo |
| `xmldb_key` | `xmldb_key.php` | Clave primaria/foránea |
| `xmldb_index` | `xmldb_index.php` | Índice |
| `xmldb_file` | `xmldb_file.php` | Parsea archivos XML |

### Tipos de Campo XMLDB

```php
XMLDB_TYPE_INT      // Entero
XMLDB_TYPE_CHAR     // Cadena de longitud fija
XMLDB_TYPE_TEXT     // Texto largo
XMLDB_TYPE_FLOAT    // Punto flotante
XMLDB_TYPE_NUMBER   // Decimal con precisión
XMLDB_TYPE_BINARY   // Binario
XMLDB_TYPE_DATETIME // Fecha y hora
```

### Propiedades de Campo

```php
XMLDB_NOTNULL   // Campo no puede ser nulo
XMLDB_NULL      // Campo puede ser nulo
XMLDB_SEQUENCE  // Auto-increment
```

### Tipos de Clave

```php
XMLDB_KEY_PRIMARY  // Clave primaria
XMLDB_KEY_UNIQUE   // Clave única
XMLDB_KEY_FOREIGN  // Clave foránea
```

### Tipos de Índice

```php
XMLDB_INDEX_UNIQUE     // Índice único
XMLDB_INDEX_NOTUNIQUE  // Índice no único
```

### Database Manager: Operaciones XMLDB

```php
// Obtener el manager
$dbman = $DB->get_manager();

// ========== TABLAS ==========

// Crear tabla desde objeto
$table = new xmldb_table('mytable');
$table->add_field('id', XMLDB_TYPE_INT, '10', null,
                  XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
$table->add_field('name', XMLDB_TYPE_CHAR, '255', null,
                  XMLDB_NOTNULL, null, null);
$table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
$dbman->create_table($table);

// Verificar si tabla existe
if ($dbman->table_exists('mytable')) { }

// Renombrar tabla
$dbman->rename_table($table, 'newtablename');

// Eliminar tabla
$dbman->drop_table($table);

// ========== CAMPOS ==========

// Crear campo
$field = new xmldb_field('newfield', XMLDB_TYPE_CHAR, '100',
                         null, XMLDB_NOTNULL, null, 'default');

// Agregar campo a tabla existente
if (!$dbman->field_exists($table, $field)) {
    $dbman->add_field($table, $field);
}

// Modificar tipo de campo
$dbman->change_field_type($table, $field);

// Modificar precisión
$dbman->change_field_precision($table, $field);

// Modificar notnull
$dbman->change_field_notnull($table, $field);

// Modificar default
$dbman->change_field_default($table, $field);

// Renombrar campo
$dbman->rename_field($table, $field, 'newfieldname');

// Eliminar campo
$dbman->drop_field($table, $field);

// ========== CLAVES ==========

// Crear clave foránea
$key = new xmldb_key('fk_course', XMLDB_KEY_FOREIGN,
                     ['courseid'], 'course', ['id']);
$dbman->add_key($table, $key);

// Eliminar clave
$dbman->drop_key($table, $key);

// ========== ÍNDICES ==========

// Crear índice
$index = new xmldb_index('idx_name', XMLDB_INDEX_NOTUNIQUE,
                         ['field1', 'field2']);

if (!$dbman->index_exists($table, $index)) {
    $dbman->add_index($table, $index);
}

// Eliminar índice
$dbman->drop_index($table, $index);

// ========== DESDE ARCHIVO ==========

// Instalar desde archivo XML
$dbman->install_from_xmldb_file($CFG->dirroot . '/mod/myplugin/db/install.xml');
```

---

## Plugin Manager

### Clase Principal

**Ubicación**: `/lib/classes/plugin_manager.php`

```php
class core_plugin_manager {

    // Singleton
    public static function instance() {
        static $instance = null;
        if ($instance === null) {
            $instance = new self();
        }
        return $instance;
    }

    // ========== OBTENER INFORMACIÓN ==========

    // Todos los plugins
    public function get_plugins() { }

    // Plugins de un tipo específico
    public function get_plugins_of_type($type) { }

    // Información de un plugin específico
    public function get_plugin_info($component) { }

    // ========== PLUGINS INSTALADOS (BD) ==========

    // Plugins instalados por tipo
    public function get_installed_plugins($type) { }

    // Cargar desde BD
    protected function load_installed_plugins() { }

    // ========== PLUGINS PRESENTES (DISCO) ==========

    // Plugins en el sistema de archivos
    public function get_present_plugins() { }

    // Cargar desde disco (lee version.php)
    protected function load_present_plugins() { }

    // Lista de plugins por tipo
    public function get_plugin_list($type) { }

    // ========== PLUGINS HABILITADOS ==========

    public function get_enabled_plugins($type) { }

    // ========== ESTADOS ==========

    // Estado de un plugin específico
    public function get_plugin_status($component) { }

    // Verificar si todos los plugins están OK
    public function all_plugins_ok($version, &$failed, $branch) { }

    // ========== CACHÉS ==========

    public static function reset_caches($phpunitreset = false) { }
}
```

### Estados de Plugin

```php
// Constantes de estado
PLUGIN_STATUS_NODB      = 'nodb'       // Sin información en BD
PLUGIN_STATUS_UPTODATE  = 'uptodate'   // Actualizado
PLUGIN_STATUS_NEW       = 'new'        // Nuevo, listo para instalar
PLUGIN_STATUS_UPGRADE   = 'upgrade'    // Listo para actualizar
PLUGIN_STATUS_DELETE    = 'delete'     // Listo para eliminar
PLUGIN_STATUS_DOWNGRADE = 'downgrade'  // Versión en disco < BD
PLUGIN_STATUS_MISSING   = 'missing'    // En BD pero no en disco
```

### Detección de Cambios

```php
// En load_present_plugins()
protected function load_present_plugins() {
    $plugintypes = core_component::get_plugin_types();

    foreach ($plugintypes as $type => $typedir) {
        $plugins = core_component::get_plugin_list($type);

        foreach ($plugins as $pluginname => $plugindir) {
            // Lee version.php del plugin
            $plugin = new stdClass();
            include($plugindir . '/version.php');
            $this->presentplugins[$type][$pluginname] = $plugin;
        }
    }
}

// Comparación para determinar estado
$diskversion = $this->presentplugins[$type][$plugin]->version;
$dbversion = $this->installedplugins[$type][$plugin];

if ($dbversion === null) {
    $status = PLUGIN_STATUS_NEW;      // No instalado
} else if ($diskversion > $dbversion) {
    $status = PLUGIN_STATUS_UPGRADE;  // Necesita upgrade
} else if ($diskversion < $dbversion) {
    $status = PLUGIN_STATUS_DOWNGRADE;// Downgrade (problema)
} else {
    $status = PLUGIN_STATUS_UPTODATE; // OK
}
```

---

## Instalación y Actualización de Plugins

### Estructura de Archivos de Plugin

```
/mod/myplugin/
├── version.php           # Metadatos obligatorios
├── db/
│   ├── install.xml       # Esquema de tablas
│   ├── install.php       # Post-instalación
│   ├── upgrade.php       # Migraciones
│   ├── uninstall.php     # Pre-desinstalación
│   ├── access.php        # Capacidades
│   ├── events.php        # Observadores de eventos
│   ├── hooks.php         # Callbacks de hooks
│   ├── tasks.php         # Tareas programadas
│   ├── services.php      # Servicios web
│   ├── messages.php      # Proveedores de mensajes
│   └── tag.php           # Áreas de etiquetas
├── lang/
│   └── en/
│       └── myplugin.php  # Cadenas de idioma
├── lib.php               # Funciones auxiliares
├── settings.php          # Configuración admin
└── ...
```

### Archivo `version.php` de Plugin

```php
<?php
// Ubicación: /mod/myplugin/version.php

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_myplugin';     // Nombre Frankenstyle
$plugin->version   = 2024010100;         // YYYYMMDDXX
$plugin->requires  = 2023100900;         // Versión mínima de Moodle
$plugin->maturity  = MATURITY_STABLE;    // Madurez
$plugin->release   = '1.0.0';            // Versión legible

// Dependencias opcionales
$plugin->dependencies = [
    'mod_forum' => 2023100900,           // Requiere mod_forum
    'block_timeline' => ANY_VERSION,      // Cualquier versión
];
```

### Archivo `db/install.php` de Plugin

```php
<?php
// Ubicación: /mod/myplugin/db/install.php

defined('MOODLE_INTERNAL') || die();

/**
 * Ejecutado después de crear las tablas del plugin
 *
 * @return bool Éxito
 */
function xmldb_mod_myplugin_install() {
    global $DB;

    // Crear registros iniciales
    $record = new stdClass();
    $record->name = 'default';
    $record->value = 'initial';
    $record->timecreated = time();
    $DB->insert_record('myplugin_settings', $record);

    // Configurar valores por defecto
    set_config('enabled', 1, 'mod_myplugin');

    return true;
}
```

### Archivo `db/upgrade.php` de Plugin

```php
<?php
// Ubicación: /mod/myplugin/db/upgrade.php

defined('MOODLE_INTERNAL') || die();

/**
 * Ejecuta los pasos de actualización del plugin
 *
 * @param int $oldversion Versión anterior
 * @return bool Éxito
 */
function xmldb_mod_myplugin_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // UPGRADE STEP 1: Agregar campo
    if ($oldversion < 2024010101) {
        $table = new xmldb_table('myplugin');
        $field = new xmldb_field('newfield', XMLDB_TYPE_INT, '10',
                                 null, XMLDB_NOTNULL, null, '0', 'existingfield');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Marcar savepoint
        upgrade_mod_savepoint(true, 2024010101, 'myplugin');
    }

    // UPGRADE STEP 2: Crear nueva tabla
    if ($oldversion < 2024010102) {
        $table = new xmldb_table('myplugin_extra');

        $table->add_field('id', XMLDB_TYPE_INT, '10', null,
                          XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('mypluginid', XMLDB_TYPE_INT, '10', null,
                          XMLDB_NOTNULL, null, null);
        $table->add_field('data', XMLDB_TYPE_TEXT, null, null,
                          null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_myplugin', XMLDB_KEY_FOREIGN,
                        ['mypluginid'], 'myplugin', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_mod_savepoint(true, 2024010102, 'myplugin');
    }

    // UPGRADE STEP 3: Migrar datos
    if ($oldversion < 2024010103) {
        // Migración de datos
        $records = $DB->get_records('myplugin_old');
        foreach ($records as $record) {
            $newrecord = new stdClass();
            $newrecord->name = $record->oldname;
            $newrecord->value = $record->oldvalue;
            $DB->insert_record('myplugin_new', $newrecord);
        }

        upgrade_mod_savepoint(true, 2024010103, 'myplugin');
    }

    // UPGRADE STEP 4: Eliminar tabla obsoleta
    if ($oldversion < 2024010104) {
        $table = new xmldb_table('myplugin_old');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_mod_savepoint(true, 2024010104, 'myplugin');
    }

    return true;
}
```

### Función `upgrade_plugins()`

**Ubicación**: `/lib/upgradelib.php`

```php
/**
 * Actualiza todos los plugins de un tipo
 *
 * @param string $type Tipo de plugin (mod, block, etc.)
 * @param callable $startcallback Callback al iniciar
 * @param callable $endcallback Callback al terminar
 * @param bool $verbose Mostrar progreso
 */
function upgrade_plugins($type, $startcallback, $endcallback, $verbose) {
    global $CFG, $DB;

    // Obtener plugins del tipo
    $plugins = core_plugin_manager::instance()->get_plugins_of_type($type);

    foreach ($plugins as $plugin) {
        $component = $plugin->component;

        // Buscar archivo de upgrade
        $upgradefile = $plugin->full_path('db/upgrade.php');

        if (file_exists($upgradefile)) {
            // Obtener versión instalada
            $installedversion = $DB->get_field('config_plugins', 'value', [
                'plugin' => $component,
                'name' => 'version'
            ]);

            // Si versión en disco > versión instalada -> actualizar
            if ($installedversion !== null
                && $plugin->versiondisk > $installedversion) {

                // Callback de inicio
                $startcallback($component, false, $verbose);

                // Cargar y ejecutar función de upgrade
                require($upgradefile);
                $function = 'xmldb_' . $plugin->component . '_upgrade';

                if (function_exists($function)) {
                    $result = $function($installedversion);
                    if (!$result) {
                        throw new upgrade_exception($component,
                            $plugin->versiondisk);
                    }
                }

                // Guardar nueva versión
                set_config('version', $plugin->versiondisk, $component);

                // Callback de fin
                $endcallback($component, false, $verbose);
            }
        }
    }
}
```

### Nomenclatura Frankenstyle

Convención de nombres para componentes de Moodle:

```
[plugintype]_[pluginname]

Ejemplos:
- mod_forum            // Módulo Forum
- mod_quiz             // Módulo Quiz
- block_timeline       // Bloque Timeline
- auth_ldap            // Plugin Auth LDAP
- auth_manual          // Plugin Auth Manual
- enrol_manual         // Plugin Enrol Manual
- tool_installaddon    // Tool Install Add-on
- local_myplugin       // Plugin local personalizado
```

---

## Herramientas CLI

### CLI Install

**Ubicación**: `/admin/cli/install.php`

```bash
# Instalación completa no interactiva
php admin/cli/install.php \
    --lang=es \
    --wwwroot=http://example.com/moodle \
    --dataroot=/var/moodledata \
    --dbtype=mysqli \
    --dbhost=localhost \
    --dbname=moodle \
    --dbuser=moodleuser \
    --dbpass=password123 \
    --dbport=3306 \
    --prefix=mdl_ \
    --fullname="Mi Sitio Moodle" \
    --shortname="moodle" \
    --adminuser=admin \
    --adminpass=Admin123! \
    --adminemail=admin@example.com \
    --non-interactive \
    --agree-license
```

**Opciones principales**:

| Opción | Descripción |
|--------|-------------|
| `--wwwroot` | URL completa del sitio |
| `--dataroot` | Directorio de datos (no accesible web) |
| `--dbtype` | Tipo de BD: mysqli, pgsql, mssql, oracle |
| `--dbhost` | Host de la base de datos |
| `--dbname` | Nombre de la base de datos |
| `--dbuser` | Usuario de BD |
| `--dbpass` | Contraseña de BD |
| `--prefix` | Prefijo de tablas (ej: mdl_) |
| `--fullname` | Nombre completo del sitio |
| `--shortname` | Nombre corto del sitio |
| `--adminuser` | Username del administrador |
| `--adminpass` | Contraseña del administrador |
| `--adminemail` | Email del administrador |
| `--non-interactive` | No solicitar entrada |
| `--agree-license` | Aceptar licencia GPL |

### CLI Upgrade

**Ubicación**: `/admin/cli/upgrade.php`

```bash
# Actualización no interactiva
php admin/cli/upgrade.php --non-interactive

# Con opciones adicionales
php admin/cli/upgrade.php \
    --lang=es \
    --non-interactive \
    --allow-unstable \
    --verbose-settings
```

**Opciones**:

| Opción | Descripción |
|--------|-------------|
| `--lang` | Idioma de salida |
| `--non-interactive` | Modo no interactivo |
| `--allow-unstable` | Permitir versiones inestables |
| `--verbose-settings` | Mostrar configuración |

### CLI Install Database Only

**Ubicación**: `/admin/cli/install_database.php`

```bash
# Solo instalar BD (config.php ya existe)
php admin/cli/install_database.php \
    --adminuser=admin \
    --adminpass=Admin123! \
    --adminemail=admin@example.com \
    --agree-license
```

---

## Sistema de Cachés

### Deshabilitar Cachés Durante Upgrade

```php
// En admin/index.php durante upgrade
define('CACHE_DISABLE_ALL', true);

// También en scripts CLI
// admin/cli/install.php
// admin/cli/upgrade.php
```

### Purga de Cachés

```php
// Purgar todas las cachés (forzar aunque estén deshabilitadas)
cache_helper::purge_all(true);

// Función legacy
purge_all_caches();

// Actualizar definiciones de caché
cache_helper::update_definitions(true);

// Resetear caché de componentes
core_component::reset_cache();

// Resetear caché del plugin manager
core_plugin_manager::reset_caches();
```

### Cachés del Plugin Manager

```php
// Cachés utilizadas
$cache = cache::make('core', 'plugin_manager');

// Claves de caché:
'installed'  // Plugins instalados desde BD
'present'    // Plugins en disco
'enabled'    // Plugins habilitados
```

---

## Archivos de Configuración de Plugins

### Archivo `db/access.php` - Capacidades

```php
<?php
// Ubicación: /mod/myplugin/db/access.php

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'mod/myplugin:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    'mod/myplugin:addinstance' => [
        'riskbitmask' => RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
        'clonepermissionsfrom' => 'moodle/course:manageactivities',
    ],

    'mod/myplugin:manage' => [
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
];
```

### Archivo `db/events.php` - Observadores de Eventos

```php
<?php
// Ubicación: /mod/myplugin/db/events.php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname' => '\core\event\course_module_created',
        'callback' => '\mod_myplugin\observer::course_module_created',
        'priority' => 0,
    ],
    [
        'eventname' => '\core\event\user_enrolment_created',
        'callback' => '\mod_myplugin\observer::user_enrolled',
        'internal' => false,  // Puede ejecutarse en una tarea
    ],
];
```

### Archivo `db/tasks.php` - Tareas Programadas

```php
<?php
// Ubicación: /mod/myplugin/db/tasks.php

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => '\mod_myplugin\task\cleanup_task',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
    [
        'classname' => '\mod_myplugin\task\sync_task',
        'blocking' => 0,
        'minute' => '*/15',  // Cada 15 minutos
        'hour' => '*',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
    ],
];
```

### Archivo `db/services.php` - Servicios Web

```php
<?php
// Ubicación: /mod/myplugin/db/services.php

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_myplugin_get_items' => [
        'classname' => '\mod_myplugin\external\get_items',
        'description' => 'Get items from myplugin',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/myplugin:view',
    ],
    'mod_myplugin_create_item' => [
        'classname' => '\mod_myplugin\external\create_item',
        'description' => 'Create a new item',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'mod/myplugin:manage',
    ],
];

$services = [
    'myplugin_service' => [
        'functions' => ['mod_myplugin_get_items', 'mod_myplugin_create_item'],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'myplugin',
    ],
];
```

### Archivo `db/hooks.php` - Callbacks de Hooks

```php
<?php
// Ubicación: /mod/myplugin/db/hooks.php

defined('MOODLE_INTERNAL') || die();

$callbacks = [
    [
        'hook' => \core_course\hook\after_course_created::class,
        'callback' => \mod_myplugin\hook_listener::class . '::course_created',
        'priority' => 500,
    ],
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \mod_myplugin\hook_listener::class . '::before_footer',
    ],
];
```

---

## Referencia Rápida

### Funciones de Instalación

```php
// Instalación del core
install_core($version, $verbose)
install_init_dataroot($dataroot, $dirpermissions)

// Post-instalación
xmldb_main_install()
xmldb_{plugintype}_{pluginname}_install()
```

### Funciones de Upgrade

```php
// Upgrade del core
upgrade_core($version, $verbose)
xmldb_main_upgrade($oldversion)

// Upgrade de plugins
upgrade_noncore($verbose)
upgrade_plugins($type, $startcallback, $endcallback, $verbose)
xmldb_{plugintype}_{pluginname}_upgrade($oldversion)

// Savepoints
upgrade_main_savepoint($result, $version, $allowabort)
upgrade_plugin_savepoint($result, $version, $type, $plugin, $allowabort)
upgrade_mod_savepoint($result, $version, $modname, $allowabort)
upgrade_block_savepoint($result, $version, $blockname, $allowabort)
```

### Funciones de Validación

```php
// Verificar requisitos
check_moodle_environment($version, $env_select)
core_tables_exist()
is_major_upgrade_required()

// Verificar plugins
$manager->all_plugins_ok($version, $failed, $branch)
$manager->get_plugin_status($component)
```

### Funciones de Caché

```php
// Purgar
cache_helper::purge_all($force)
purge_all_caches()

// Actualizar definiciones
cache_helper::update_definitions($coreonly)

// Resetear
core_component::reset_cache()
core_plugin_manager::reset_caches($phpunitreset)
```

### Constantes de Madurez

```php
MATURITY_ALPHA    = 50    // Desarrollo inicial
MATURITY_BETA     = 100   // En pruebas
MATURITY_RC       = 150   // Release Candidate
MATURITY_STABLE   = 200   // Estable para producción
```

### Archivos Clave por Ruta

| Ruta | Descripción |
|------|-------------|
| `/install.php` | Instalación web |
| `/admin/index.php` | Panel de upgrade |
| `/version.php` | Versión del core |
| `/config.php` | Configuración (generado) |
| `/lib/upgradelib.php` | Funciones de upgrade |
| `/lib/db/install.xml` | Esquema inicial |
| `/lib/db/install.php` | Post-instalación |
| `/lib/db/upgrade.php` | Migraciones del core |
| `/admin/environment.xml` | Requisitos del sistema |
| `/admin/cli/install.php` | CLI install |
| `/admin/cli/upgrade.php` | CLI upgrade |

---

## Resumen

El sistema de instalación y actualización de Moodle proporciona:

1. **Instalación automatizada**:
   - Validación de requisitos del sistema
   - Creación de esquema mediante XMLDB
   - Inicialización de datos con `install.php`
   - Generación de `config.php`

2. **Actualizaciones incrementales**:
   - Comparación de versiones código vs BD
   - Ejecución de pasos de upgrade ordenados
   - Savepoints para recuperación ante fallos
   - Migraciones de esquema con XMLDB

3. **Gestión de plugins**:
   - Detección automática de nuevos plugins
   - Detección de actualizaciones pendientes
   - Soporte para dependencias entre plugins
   - Estados claros: NEW, UPGRADE, UPTODATE, etc.

4. **Herramientas CLI**:
   - Instalación desatendida
   - Actualizaciones automatizadas
   - Integración con CI/CD

5. **Sistema XMLDB**:
   - Definición de esquema en XML
   - Operaciones de BD agnósticas
   - Soporte para MySQL, PostgreSQL, MSSQL, Oracle
