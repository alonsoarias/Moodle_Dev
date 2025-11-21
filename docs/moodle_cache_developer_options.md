# Sistema de Caché y Opciones de Desarrollador en Moodle

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Sistema de Caché (MUC)](#sistema-de-caché-muc)
3. [Tipos de Stores (Almacenes)](#tipos-de-stores-almacenes)
4. [Definiciones de Caché](#definiciones-de-caché)
5. [Proceso de Purga de Caché](#proceso-de-purga-de-caché)
6. [Configuración de Caché](#configuración-de-caché)
7. [Opciones de Desarrollador](#opciones-de-desarrollador)
8. [Configuración de Debug](#configuración-de-debug)
9. [Modo Diseñador de Tema](#modo-diseñador-de-tema)
10. [Perfilador de Rendimiento](#perfilador-de-rendimiento)
11. [Modo Mantenimiento](#modo-mantenimiento)
12. [Configuración Recomendada para Desarrollo](#configuración-recomendada-para-desarrollo)
13. [Referencia Rápida](#referencia-rápida)

---

## Introducción

Moodle implementa dos sistemas fundamentales para el rendimiento y desarrollo:

1. **MUC (Moodle Universal Cache)**: Sistema de caché flexible y extensible que permite almacenar datos en diferentes backends (archivos, memoria, Redis, etc.)

2. **Opciones de Desarrollador**: Conjunto de configuraciones que facilitan el desarrollo, debugging y optimización de Moodle

---

## Sistema de Caché (MUC)

### Arquitectura General

El sistema MUC está ubicado en `/cache/` y proporciona:

- **Abstracción de almacenamiento**: Interfaz única para diferentes backends
- **Tres modos de caché**: Application, Session y Request
- **Definiciones configurables**: Cada caché tiene comportamiento específico
- **Invalidación por eventos**: Purga automática cuando cambian datos

### Estructura de Directorios

```
/cache/
├── admin.php                    # Página de administración
├── classes/
│   ├── cache.php                # Clase principal
│   ├── application_cache.php    # Caché de aplicación
│   ├── session_cache.php        # Caché de sesión
│   ├── request_cache.php        # Caché de request
│   ├── definition.php           # Definiciones de caché
│   ├── helper.php               # Funciones auxiliares
│   ├── store.php                # Clase base de stores
│   ├── config.php               # Lector de configuración
│   ├── config_writer.php        # Escritor de configuración
│   └── factory.php              # Factory para instancias
├── stores/
│   ├── file/                    # Store de archivos
│   ├── session/                 # Store de sesión
│   ├── redis/                   # Store Redis
│   ├── apcu/                    # Store APCu
│   └── static/                  # Store estático
└── locks/
    └── file/                    # Locks basados en archivo
```

### Modos de Caché

| Modo | Constante | Descripción | Persistencia |
|------|-----------|-------------|--------------|
| **Application** | `MODE_APPLICATION` | Compartido entre todos los usuarios y requests | Persistente |
| **Session** | `MODE_SESSION` | Específico por sesión de usuario | Durante sesión |
| **Request** | `MODE_REQUEST` | Solo durante el request actual | Solo request |

### Clase Principal: `cache`

**Ubicación**: `/cache/classes/cache.php`

```php
/**
 * Crear instancia de caché
 *
 * @param string $component Componente (ej: 'core', 'mod_forum')
 * @param string $area Área de caché (ej: 'string', 'coursemodinfo')
 * @param array $identifiers Identificadores opcionales
 * @return cache Instancia de caché
 */
$cache = cache::make('core', 'string');

// Operaciones básicas
$cache->set('key', $value);           // Guardar valor
$value = $cache->get('key');          // Obtener valor
$cache->delete('key');                // Eliminar valor
$cache->purge();                      // Purgar todo el caché

// Operaciones múltiples
$cache->set_many(['key1' => $v1, 'key2' => $v2]);
$values = $cache->get_many(['key1', 'key2']);
$cache->delete_many(['key1', 'key2']);

// Verificar existencia
if ($cache->has('key')) {
    // La clave existe
}

// Obtener o crear (con callback)
$value = $cache->get('key');
if ($value === false) {
    $value = compute_expensive_value();
    $cache->set('key', $value);
}
```

### Clase Helper: `cache_helper`

**Ubicación**: `/cache/classes/helper.php`

```php
// Purgar por definición
cache_helper::purge_by_definition('core', 'string');

// Purgar por evento
cache_helper::purge_by_event('changesincoursecat');

// Purgar todo
cache_helper::purge_all();

// Purgar store específico
cache_helper::purge_store('redis');

// Purgar stores usados por una definición
cache_helper::purge_stores_used_by_definition('core', 'coursemodinfo');

// Actualizar definiciones (después de instalar plugins)
cache_helper::update_definitions();

// Obtener estadísticas
$stats = cache_helper::get_stats();
```

---

## Tipos de Stores (Almacenes)

### 1. File Store (Predeterminado)

**Ubicación**: `/cache/stores/file/lib.php`

Almacena caché en el sistema de archivos.

```php
// Configuración en moodledata/muc/config.php
'default_file' => [
    'name' => 'default_file',
    'plugin' => 'file',
    'configuration' => [
        'path' => '/var/moodledata/cache',
        'autocreate' => 1,
        'prescan' => false,
        'asyncpurge' => false,
    ],
],
```

**Características**:
- No requiere extensiones adicionales
- Funciona en cualquier instalación
- Más lento que stores en memoria
- Soporta: `key_aware`, `searchable`, `lockable`

### 2. Session Store

**Ubicación**: `/cache/stores/session/lib.php`

Almacena en la sesión PHP del usuario.

```php
// Usado automáticamente para MODE_SESSION
$cache = cache::make('core', 'usersessions');
```

**Características**:
- Específico por usuario
- Datos perdidos al cerrar sesión
- Sin configuración adicional necesaria

### 3. APCu Store

**Ubicación**: `/cache/stores/apcu/lib.php`

Usa APCu (Alternative PHP Cache user).

```php
// Requiere extensión APCu
// php.ini: extension=apcu.so

// Configuración
'apcu_store' => [
    'name' => 'apcu_store',
    'plugin' => 'apcu',
    'configuration' => [
        'prefix' => 'mdl_',
    ],
],
```

**Características**:
- Muy rápido (memoria compartida)
- No persiste entre reinicios de PHP
- No compartido entre servidores
- Ideal para: sitios de un solo servidor

### 4. Redis Store

**Ubicación**: `/cache/stores/redis/lib.php`

Usa servidor Redis externo.

```php
// Requiere extensión redis
// php.ini: extension=redis.so

// Configuración
'redis_store' => [
    'name' => 'redis_store',
    'plugin' => 'redis',
    'configuration' => [
        'server' => '127.0.0.1:6379',
        'prefix' => 'mdl_',
        'password' => '',
        'serializer' => Redis::SERIALIZER_PHP,
        'compressor' => Redis::COMPRESSION_NONE,
    ],
],
```

**Características**:
- Persiste entre reinicios
- Compartido entre servidores (clusters)
- Soporta compresión (gzip, zstd)
- Soporta Redis Cluster
- Ideal para: sitios con múltiples servidores

### 5. Static Store

**Ubicación**: `/cache/stores/static/lib.php`

Array estático en memoria durante el request.

```php
// Usado automáticamente para MODE_REQUEST
// No requiere configuración
```

**Características**:
- Más rápido posible
- Solo durante el request actual
- Sin persistencia

### Comparación de Stores

| Store | Velocidad | Persistencia | Compartido | Requisitos |
|-------|-----------|--------------|------------|------------|
| Static | Muy alta | No | No | Ninguno |
| APCu | Alta | No* | No | ext-apcu |
| Redis | Media-Alta | Sí | Sí | ext-redis + servidor |
| File | Media | Sí | Sí** | Ninguno |
| Session | Media | Sesión | No | Ninguno |

*No persiste entre reinicios de PHP-FPM/Apache
**Compartido si está en almacenamiento compartido (NFS, etc.)

---

## Definiciones de Caché

### Estructura de Definición

Las definiciones se declaran en archivos `db/caches.php`:

```php
<?php
// Ubicación: /lib/db/caches.php (core)
// o: /mod/myplugin/db/caches.php (plugin)

defined('MOODLE_INTERNAL') || die();

$definitions = [
    // Definición simple
    'string' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 30,
        'canuselocalstore' => true,
    ],

    // Definición con datasource
    'questiondata' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'datasource' => 'question_finder',
        'datasourcefile' => 'question/engine/bank.php',
    ],

    // Definición con eventos de invalidación
    'coursemodinfo' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'staticacceleration' => true,
        'staticaccelerationsize' => 1,
        'invalidationevents' => [
            'changesincoursecat',
            'changesincourse',
        ],
    ],

    // Definición con TTL
    'calendar_categories' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
        'ttl' => 900,  // 15 minutos
    ],
];
```

### Opciones de Definición

| Opción | Tipo | Descripción |
|--------|------|-------------|
| `mode` | int | **Requerido**. MODE_APPLICATION, MODE_SESSION, MODE_REQUEST |
| `simplekeys` | bool | Las claves son strings simples (sin hash) |
| `simpledata` | bool | Los datos son escalares (sin serialización) |
| `staticacceleration` | bool | Usar array estático como acelerador |
| `staticaccelerationsize` | int | Tamaño máximo del acelerador estático |
| `ttl` | int | Tiempo de vida en segundos |
| `datasource` | string | Clase que provee datos si no están en caché |
| `datasourcefile` | string | Archivo donde está la clase datasource |
| `invalidationevents` | array | Eventos que invalidan este caché |
| `requireidentifiers` | array | Identificadores requeridos al crear |
| `requiredataguarantee` | bool | Garantía de integridad de datos |
| `canuselocalstore` | bool | Puede usar store local adicional |
| `sharingoptions` | int | Opciones de compartición |

### Definiciones del Core

**Ubicación**: `/lib/db/caches.php`

```php
// Strings de idioma
'string' => [
    'mode' => cache_store::MODE_APPLICATION,
    'simplekeys' => true,
    'simpledata' => true,
    'staticacceleration' => true,
    'staticaccelerationsize' => 30,
    'canuselocalstore' => true,
],

// Configuración global
'config' => [
    'mode' => cache_store::MODE_APPLICATION,
    'staticacceleration' => true,
    'simpledata' => true,
],

// Información de módulos de curso
'coursemodinfo' => [
    'mode' => cache_store::MODE_APPLICATION,
    'simplekeys' => true,
    'simpledata' => true,
    'staticacceleration' => true,
    'invalidationevents' => [
        'changesincoursecat',
        'changesincourse',
    ],
],

// Eventos de invalidación
'eventinvalidation' => [
    'mode' => cache_store::MODE_APPLICATION,
    'staticacceleration' => true,
    'requiredataguarantee' => true,
    'simpledata' => true,
],
```

---

## Proceso de Purga de Caché

### Función Principal: `purge_all_caches()`

**Ubicación**: `/lib/moodlelib.php`

```php
/**
 * Purga todas las cachés del sistema
 */
function purge_all_caches() {
    purge_caches();
}

/**
 * Purga cachés selectivamente
 *
 * @param array $options Opciones de purga
 */
function purge_caches($options = []) {
    // Opciones disponibles:
    // 'muc'      => true  - MUC caches
    // 'courses'  => true  - Course caches
    // 'theme'    => true  - Theme cache
    // 'lang'     => true  - Language strings
    // 'js'       => true  - JavaScript
    // 'template' => true  - Templates
    // 'filter'   => true  - Text filters
    // 'other'    => true  - Otros caches

    // Si no se especifican opciones, purga todo
    if (empty($options)) {
        $options = [
            'muc' => true,
            'courses' => true,
            'theme' => true,
            'lang' => true,
            'js' => true,
            'template' => true,
            'filter' => true,
            'other' => true,
        ];
    }

    // Purgar MUC
    if (!empty($options['muc'])) {
        cache_helper::purge_all();
    }

    // Purgar cache de cursos
    if (!empty($options['courses'])) {
        rebuild_course_cache(0, true);
    }

    // Purgar cache de tema
    if (!empty($options['theme'])) {
        theme_reset_all_caches();
    }

    // Purgar strings de idioma
    if (!empty($options['lang'])) {
        get_string_manager()->reset_caches();
    }

    // Purgar JavaScript
    if (!empty($options['js'])) {
        js_reset_all_caches();
    }

    // Purgar templates
    if (!empty($options['template'])) {
        template_reset_all_caches();
    }

    // Purgar filtros
    if (!empty($options['filter'])) {
        reset_text_filters_cache();
    }

    // Purgar otros
    if (!empty($options['other'])) {
        purge_other_caches();
    }
}
```

### Purga por Helper

```php
// Purgar todo MUC
cache_helper::purge_all();

// Purgar definición específica
cache_helper::purge_by_definition('core', 'string');

// Purgar por evento (invalida cachés relacionados)
cache_helper::purge_by_event('changesincourse');

// Purgar store específico
cache_helper::purge_store('redis');

// Purgar stores usados por definición
cache_helper::purge_stores_used_by_definition('core', 'coursemodinfo');
```

### Funciones Específicas de Purga

```php
// Tema
theme_reset_all_caches();

// JavaScript
js_reset_all_caches();

// Templates
template_reset_all_caches();

// Strings de idioma
get_string_manager()->reset_caches();

// Filtros de texto
reset_text_filters_cache();

// Cache de cursos
rebuild_course_cache($courseid, $clearonly);

// Plugin manager
core_plugin_manager::reset_caches();

// Componentes
core_component::reset_cache();
```

### Página de Purga: `/admin/purgecaches.php`

```php
// Ubicación: /admin/purgecaches.php

// Define constante para ignorar caché de componentes
define('IGNORE_COMPONENT_CACHE', true);

require_once('../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Configurar página de admin
admin_externalpage_setup('purgecaches');

// Procesar formulario
if ($data = data_submitted() && confirm_sesskey()) {
    // Purgar según opciones seleccionadas
    purge_caches($data);

    // Mostrar mensaje de éxito
    redirect(new moodle_url('/admin/purgecaches.php'),
        get_string('purgecachesfinished', 'admin'));
}

// Mostrar formulario
// ...
```

**Acceso**: Site administration > Development > Purge caches

---

## Configuración de Caché

### Archivo de Configuración

**Ubicación**: `{moodledata}/muc/config.php`

```php
<?php
$configuration = [
    // Stores configurados
    'stores' => [
        'default_application' => [
            'name' => 'default_application',
            'plugin' => 'file',
            'configuration' => [
                'path' => '/var/moodledata/cache',
            ],
            'features' => 30,
            'modes' => 1,
        ],
        'default_session' => [
            'name' => 'default_session',
            'plugin' => 'session',
            'configuration' => [],
            'features' => 14,
            'modes' => 2,
        ],
        'default_request' => [
            'name' => 'default_request',
            'plugin' => 'static',
            'configuration' => [],
            'features' => 30,
            'modes' => 4,
        ],
    ],

    // Mapeo de modos a stores
    'modemappings' => [
        [
            'mode' => 1,  // MODE_APPLICATION
            'store' => 'default_application',
        ],
        [
            'mode' => 2,  // MODE_SESSION
            'store' => 'default_session',
        ],
        [
            'mode' => 4,  // MODE_REQUEST
            'store' => 'default_request',
        ],
    ],

    // Definiciones cargadas
    'definitions' => [
        // Se cargan automáticamente de db/caches.php
    ],

    // Mapeos personalizados de definiciones
    'definitionmappings' => [
        // Mapeos específicos de definición a store
    ],
];
```

### Administración de Caché

**URL**: `/cache/admin.php`

**Acceso**: Site administration > Plugins > Caching > Configuration

Permite:
- Ver stores configurados
- Agregar nuevos stores
- Mapear definiciones a stores
- Ver estadísticas de uso
- Purgar cachés específicos

### Directorios de Caché

```php
// Directorio principal de caché
$CFG->cachedir = $CFG->dataroot . '/cache';

// Caché local (por servidor en cluster)
$CFG->localcachedir = $CFG->dataroot . '/localcache';

// Directorio temporal para requests
$CFG->localrequestdir = sys_get_temp_dir() . '/requestdir';
```

---

## Opciones de Desarrollador

### Página de Debugging

**URL**: `/admin/settings.php?section=debugging`

**Acceso**: Site administration > Development > Debugging

### Niveles de Debug

**Ubicación**: `/lib/setuplib.php`

```php
// Constantes de nivel de debug
define('DEBUG_NONE', 0);
// Sin mensajes de error

define('DEBUG_MINIMAL', E_ERROR | E_PARSE);
// Solo errores fatales y de parsing

define('DEBUG_NORMAL', E_ERROR | E_PARSE | E_WARNING | E_NOTICE);
// Errores, warnings y notices

define('DEBUG_ALL', E_ALL & ~E_STRICT);
// Todos excepto strict standards

define('DEBUG_DEVELOPER', E_ALL | E_STRICT);
// Todos los mensajes, incluyendo strict
```

### Variables de Configuración

```php
// En config.php o via admin

// Nivel de debug
$CFG->debug = DEBUG_DEVELOPER;

// Mostrar errores en pantalla
$CFG->debugdisplay = 1;

// Mostrar IDs de strings de idioma
$CFG->debugstringids = 1;

// Traza de SQL (0 = deshabilitado, 1-100 = nivel)
$CFG->debugsqltrace = 0;

// Validadores (XHTML, CSS)
$CFG->debugvalidators = 0;

// Información de página
$CFG->debugpageinfo = 0;

// Información de templates
$CFG->debugtemplateinfo = 0;

// Debug de SMTP
$CFG->debugsmtp = 0;

// Performance debug (bitmask)
$CFG->perfdebug = 7;
// 1 = tiempo de página
// 2 = memoria
// 4 = queries SQL
```

### Clase `admin_setting_special_debug`

**Ubicación**: `/lib/adminlib.php`

```php
class admin_setting_special_debug extends admin_setting_configselect {

    public function load_choices() {
        $this->choices = [
            DEBUG_NONE      => get_string('debugnone', 'admin'),
            DEBUG_MINIMAL   => get_string('debugminimal', 'admin'),
            DEBUG_NORMAL    => get_string('debugnormal', 'admin'),
            DEBUG_ALL       => get_string('debugall', 'admin'),
            DEBUG_DEVELOPER => get_string('debugdeveloper', 'admin'),
        ];
        return true;
    }
}
```

---

## Configuración de Debug

### Debug en config.php

```php
<?php
// config.php - Configuración para desarrollo

// Nivel máximo de debug
$CFG->debug = DEBUG_DEVELOPER;

// Mostrar errores en pantalla
$CFG->debugdisplay = 1;

// Mostrar IDs de strings
$CFG->debugstringids = 1;

// Habilitar traza SQL (cuidado: impacto en rendimiento)
// $CFG->debugsqltrace = true;

// Desactivar caché de JS
$CFG->cachejs = false;

// Modo diseñador de tema
$CFG->themedesignermode = true;

// Desactivar caché de templates
$CFG->cachetemplates = false;

// Forzar recarga de strings
$CFG->langstringcache = false;
```

### Verificar Modo Debug

```php
// Verificar si está en modo desarrollador
if ($CFG->debugdeveloper) {
    // Modo desarrollador activo
}

// Verificar nivel de debug específico
if ($CFG->debug >= DEBUG_DEVELOPER) {
    // Debug developer o superior
}

// Verificar si se deben mostrar errores
if (!empty($CFG->debugdisplay)) {
    // Errores visibles en pantalla
}
```

### Funciones de Debug

```php
// Imprimir variable para debug
debugging('Mensaje de debug', DEBUG_DEVELOPER);

// Imprimir objeto/array
print_object($variable);

// Backtrace
debug_print_backtrace();

// Error de desarrollo
throw new coding_exception('Descripción del error');

// Marcar código deprecado
debugging('Esta función está deprecada', DEBUG_DEVELOPER);
```

---

## Modo Diseñador de Tema

### Configuración

**URL**: `/admin/settings.php?section=themesettings`

```php
// En config.php
$CFG->themedesignermode = true;
```

### Efectos

Cuando `themedesignermode` está activado:

1. **CSS no se cachea**: SCSS se recompila en cada request
2. **JavaScript no se cachea**: Archivos JS se cargan sin minificar
3. **Templates se recargan**: Mustache templates no se cachean
4. **Imágenes del tema**: Se regeneran dinámicamente

### Configuraciones Relacionadas

```php
// Caché de JavaScript
$CFG->cachejs = false;

// Caché de templates
$CFG->cachetemplates = false;

// Forzar recarga de CSS
$CFG->themerev = -1;  // Fuerza nueva revisión

// Slasharguments (requerido para algunos temas)
$CFG->slasharguments = true;
```

### Callback de Actualización

**Ubicación**: `/admin/settings/appearance.php`

```php
$setting = new admin_setting_configcheckbox('themedesignermode',
    new lang_string('themedesignermode', 'admin'),
    new lang_string('configthemedesignermode', 'admin'),
    0  // Deshabilitado por defecto
);
$setting->set_updatedcallback('theme_reset_all_caches');
```

---

## Perfilador de Rendimiento

### Requisitos

- Extensión PHP: `xhprof`, `tideways_xhprof` o `tideways`

### Configuración

**URL**: `/admin/settings.php?section=development`

```php
// Habilitar profiling
$CFG->profilingenabled = true;

// URLs a incluir en profiling (regex)
$CFG->profilingincluded = '/.*\.php$/';

// URLs a excluir
$CFG->profilingexcluded = '/admin\/.*\.php$/';

// Frecuencia automática (1 de cada N requests)
$CFG->profilingautofrec = 0;

// Permitir PROFILEME/DONTPROFILEME en URL
$CFG->profilingallowme = true;

// Permitir PROFILEALL/PROFILEALLSTOP
$CFG->profilingallowall = true;

// Tiempo límite para considerar "lento" (segundos)
$CFG->profilingslow = 3;

// Tiempo de retención de datos (segundos)
$CFG->profilinglifetime = 604800;  // 1 semana
```

### Uso Manual

```php
// En URL, agregar parámetros:
// ?PROFILEME - Perfilar este request
// ?DONTPROFILEME - No perfilar este request
// ?PROFILEALL - Perfilar todos los requests siguientes
// ?PROFILEALLSTOP - Dejar de perfilar todos
```

### Ver Resultados

**URL**: `/admin/tool/profiling/index.php`

---

## Modo Mantenimiento

### Activar Mantenimiento

**Método 1: Via Admin**

Site administration > Server > Maintenance mode

**Método 2: Via CLI**

```bash
# Activar
php admin/cli/maintenance.php --enable

# Desactivar
php admin/cli/maintenance.php --disable

# Activar con mensaje personalizado
php admin/cli/maintenance.php --enable --message="Actualizando sistema"
```

**Método 3: Via config.php**

```php
// Activar mantenimiento programado
$CFG->maintenance_later = time() + 300;  // En 5 minutos

// Activar inmediatamente
$CFG->maintenance_enabled = 1;
```

**Método 4: Archivo climaintenance.html**

```bash
# Crear archivo para mantenimiento CLI
echo "<h1>Sitio en mantenimiento</h1>" > $CFG->dataroot/climaintenance.html
```

### Detección de Mantenimiento

**Ubicación**: `/lib/setup.php`

```php
// Detección de modo mantenimiento CLI
if (file_exists("$CFG->dataroot/climaintenance.html")) {
    header($_SERVER['SERVER_PROTOCOL'] . ' 503 Moodle under maintenance');
    header('Status: 503 Moodle under maintenance');
    header('Retry-After: 300');
    echo file_get_contents("$CFG->dataroot/climaintenance.html");
    die;
}

// Detección de mantenimiento programado
if (isset($CFG->maintenance_later) && $CFG->maintenance_later <= time()) {
    // Activar modo mantenimiento
    set_config('maintenance_enabled', 1);
    unset_config('maintenance_later');
}
```

---

## Configuración Recomendada para Desarrollo

### config.php para Desarrollo

```php
<?php
// config.php - Configuración COMPLETA para desarrollo

// ==========================================
// DEBUGGING
// ==========================================
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
$CFG->debugstringids = 1;
$CFG->debugsqltrace = 0;  // Activar con cuidado
$CFG->debugpageinfo = 1;
$CFG->debugtemplateinfo = 1;

// ==========================================
// CACHÉ (Deshabilitar para desarrollo)
// ==========================================
$CFG->cachejs = false;
$CFG->cachetemplates = false;
$CFG->themedesignermode = true;
$CFG->langstringcache = false;

// ==========================================
// PERFORMANCE DEBUG
// ==========================================
$CFG->perfdebug = 15;  // Mostrar todo

// ==========================================
// DESARROLLO DE TEMAS
// ==========================================
$CFG->themedesignermode = true;

// ==========================================
// OPCIONALES (usar con cuidado)
// ==========================================
// Desactivar emails durante desarrollo
// $CFG->noemailever = true;

// Deshabilitar cron
// $CFG->cron_enabled = false;

// Forzar SSL (si aplica)
// $CFG->sslproxy = true;

// ==========================================
// NO USAR EN PRODUCCIÓN
// ==========================================
// $CFG->debugvalidators = true;  // Muy lento
// $CFG->debugsqltrace = true;    // Muy lento
```

### config.php para Producción

```php
<?php
// config.php - Configuración para PRODUCCIÓN

// ==========================================
// DEBUGGING - DESACTIVADO
// ==========================================
$CFG->debug = DEBUG_NONE;
$CFG->debugdisplay = 0;

// ==========================================
// CACHÉ - ACTIVADO
// ==========================================
$CFG->cachejs = true;
$CFG->cachetemplates = true;
$CFG->themedesignermode = false;

// ==========================================
// SEGURIDAD
// ==========================================
$CFG->cookiesecure = true;
$CFG->loginhttps = true;

// ==========================================
// PERFORMANCE
// ==========================================
$CFG->perfdebug = 0;
```

---

## Referencia Rápida

### Funciones de Caché

```php
// Crear instancia
$cache = cache::make('component', 'area');

// Operaciones CRUD
$cache->set('key', $value);
$value = $cache->get('key');
$cache->delete('key');
$cache->purge();

// Múltiples
$cache->set_many($array);
$cache->get_many($keys);
$cache->delete_many($keys);

// Verificar
$cache->has('key');
```

### Funciones de Purga

```php
// Purgar todo
purge_all_caches();

// Purgar selectivamente
purge_caches(['muc' => true, 'theme' => true]);

// Via helper
cache_helper::purge_all();
cache_helper::purge_by_definition('core', 'string');
cache_helper::purge_by_event('changesincourse');

// Específicos
theme_reset_all_caches();
js_reset_all_caches();
template_reset_all_caches();
rebuild_course_cache($courseid);
```

### Constantes de Debug

```php
DEBUG_NONE      = 0
DEBUG_MINIMAL   = E_ERROR | E_PARSE
DEBUG_NORMAL    = E_ERROR | E_PARSE | E_WARNING | E_NOTICE
DEBUG_ALL       = E_ALL & ~E_STRICT
DEBUG_DEVELOPER = E_ALL | E_STRICT
```

### Variables de config.php

| Variable | Desarrollo | Producción | Descripción |
|----------|------------|------------|-------------|
| `debug` | DEBUG_DEVELOPER | DEBUG_NONE | Nivel de errores |
| `debugdisplay` | 1 | 0 | Mostrar errores |
| `cachejs` | false | true | Caché JS |
| `cachetemplates` | false | true | Caché templates |
| `themedesignermode` | true | false | Modo diseñador |
| `perfdebug` | 15 | 0 | Info rendimiento |

### URLs de Administración

| URL | Descripción |
|-----|-------------|
| `/admin/purgecaches.php` | Purgar cachés |
| `/cache/admin.php` | Configurar caché |
| `/admin/settings.php?section=debugging` | Configurar debug |
| `/admin/settings.php?section=themesettings` | Configurar tema |
| `/admin/settings.php?section=development` | Desarrollo |
| `/admin/tool/profiling/index.php` | Ver profiling |

---

## Resumen

El sistema de caché y opciones de desarrollador de Moodle proporciona:

### Sistema de Caché (MUC)
- **3 modos**: Application, Session, Request
- **Múltiples stores**: File, Redis, APCu, Session, Static
- **Definiciones configurables**: TTL, eventos de invalidación, datasources
- **Purga flexible**: Por definición, evento, store o global

### Opciones de Desarrollador
- **5 niveles de debug**: NONE, MINIMAL, NORMAL, ALL, DEVELOPER
- **Caché deshabilitables**: JS, templates, tema
- **Herramientas**: Profiling, debug SQL, info de página
- **Modo mantenimiento**: CLI, admin, programado

### Mejores Prácticas
- **Desarrollo**: Debug máximo, caché deshabilitado
- **Producción**: Debug mínimo, caché habilitado, seguridad activa
