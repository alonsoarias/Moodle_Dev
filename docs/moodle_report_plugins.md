# Plugins de Reportes de Moodle: Security, Log, Loglive y Performance

## Introducción

Este documento explica en detalle los plugins de reportes de Moodle: `report_security`, `report_log`, `report_loglive` y `report_performance`. Se incluyen los archivos del core con los que interactúan y las tablas de base de datos que consultan.

---

## 1. Plugin report_security

### 1.1 Ubicación y Estructura

**Ruta:** `report/security/`

```
report/security/
├── classes/
│   ├── event/
│   │   └── report_viewed.php      # Evento de auditoría
│   └── privacy/
│       └── provider.php           # Null provider (no almacena datos)
├── db/
│   └── access.php                 # Capacidades
├── lang/en/
│   └── report_security.php        # Cadenas de idioma
├── index.php                      # Página principal
├── settings.php                   # Registro en admin
└── version.php                    # Información de versión
```

### 1.2 Página Principal - index.php

**Archivo:** `report/security/index.php`

```php
define('NO_OUTPUT_BUFFERING', true);  // Streaming para operaciones largas

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Control de acceso - requiere capacidad report/security:view
admin_externalpage_setup('reportsecurity', '', null, '', ['pagelayout' => 'report']);

// Parámetro para ver detalle de un check específico
$detail = optional_param('detail', '', PARAM_TEXT);

// Instancia la tabla de checks de seguridad
$table = new core\check\table('security', $url, $detail);

// Renderiza
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_security'));
echo $table->render($OUTPUT);
echo $OUTPUT->footer();

// Registra evento de auditoría
$event = \report_security\event\report_viewed::create(['context' => context_system::instance()]);
$event->trigger();
```

### 1.3 Capacidades

**Archivo:** `report/security/db/access.php`

```php
$capabilities = [
    'report/security:view' => [
        'riskbitmask' => RISK_CONFIG,      // Acceso a configuración del sistema
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,  // Solo contexto del sistema
        'archetypes' => [
            'manager' => CAP_ALLOW         // Solo managers por defecto
        ],
    ]
];
```

### 1.4 Sistema de Checks - Arquitectura Core

Los checks de seguridad están en `lib/classes/check/` y son gestionados por `core\check\manager`.

#### Clase Base: core\check\check

**Archivo:** `lib/classes/check/check.php`

```php
abstract class check {
    protected $component = '';

    public function get_component(): string;      // Componente del check
    public function get_id(): string;             // ID único
    public function get_ref(): string;            // Referencia global (component_id)
    public function get_name(): string;           // Nombre localizado
    public function get_action_link(): ?\action_link;  // Link a configuración
    abstract public function get_result(): result;     // Resultado del check
}
```

#### Clase de Resultado: core\check\result

**Archivo:** `lib/classes/check/result.php`

```php
class result {
    // Estados posibles
    const NA       = 'na';        // No aplica
    const OK       = 'ok';        // Correcto
    const INFO     = 'info';      // Información
    const UNKNOWN  = 'unknown';   // Desconocido
    const WARNING  = 'warning';   // Advertencia
    const ERROR    = 'error';     // Error
    const CRITICAL = 'critical';  // Crítico

    public function __construct($status, $summary, $details = '');
    public function get_status(): string;
    public function get_summary(): string;
    public function get_details(): string;
}
```

#### Gestor de Checks: core\check\manager

**Archivo:** `lib/classes/check/manager.php`

```php
class manager {
    const TYPES = ['status', 'security', 'performance'];

    public static function get_checks(string $type): array;
    public static function get_security_checks(): array;
}
```

### 1.5 Lista Completa de Security Checks

#### Categoría: ENVIRONMENT (Ambiente/Servidor)

| Check | Clase | Configuración Verificada | Estados |
|-------|-------|--------------------------|---------|
| displayerrors | `environment\displayerrors` | `WARN_DISPLAY_ERRORS_ENABLED` | OK/WARNING |
| unsecuredataroot | `environment\unsecuredataroot` | Ubicación de dataroot | OK/ERROR/CRITICAL |
| publicpaths | `environment\publicpaths` | Acceso HTTP a archivos sensibles | OK/WARNING |
| configrw | `environment\configrw` | Permisos de config.php | OK/WARNING |
| preventexecpath | `environment\preventexecpath` | `$CFG->preventexecpath` | OK/WARNING |

#### Categoría: SECURITY (Seguridad General)

| Check | Clase | Configuración Verificada | Estados |
|-------|-------|--------------------------|---------|
| embed | `security\embed` | `$CFG->allowobjectembed` | OK/ERROR |
| openprofiles | `security\openprofiles` | `$CFG->forcelogin`, `$CFG->forceloginforprofiles` | OK/WARNING |
| crawlers | `security\crawlers` | `$CFG->opentowebcrawlers` | OK/INFO/ERROR |
| passwordpolicy | `security\passwordpolicy` | `$CFG->passwordpolicy` | OK/WARNING |
| emailchangeconfirmation | `security\emailchangeconfirmation` | `$CFG->emailchangeconfirmation` | OK/WARNING/INFO |
| webcron | `security\webcron` | `$CFG->cronclionly`, `$CFG->cronremotepassword` | OK/WARNING |

#### Categoría: HTTP

| Check | Clase | Configuración Verificada | Estados |
|-------|-------|--------------------------|---------|
| cookiesecure | `http\cookiesecure` | HTTPS y cookies seguras | OK/WARNING/ERROR |

#### Categoría: ACCESS (Control de Acceso)

| Check | Clase | Configuración Verificada | Estados |
|-------|-------|--------------------------|---------|
| riskadmin | `access\riskadmin` | Lista de administradores | INFO |
| riskxss | `access\riskxss` | Usuarios con RISK_XSS | INFO/WARNING |
| riskbackup | `access\riskbackup` | Usuarios con permisos backup | INFO/WARNING |
| defaultuserrole | `access\defaultuserrole` | `$CFG->defaultuserroleid` | OK/CRITICAL |
| guestrole | `access\guestrole` | `$CFG->guestroleid` | OK/CRITICAL |
| frontpagerole | `access\frontpagerole` | `$CFG->defaultfrontpageroleid` | OK/CRITICAL |

### 1.6 Tablas de Base de Datos Consultadas

| Tabla | Check | Propósito |
|-------|-------|-----------|
| `mdl_user` | riskadmin | Obtener datos de administradores |
| `mdl_role` | defaultuserrole, guestrole, frontpagerole | Definición de roles |
| `mdl_role_capabilities` | riskxss, riskbackup, defaultuserrole | Capacidades de roles |
| `mdl_capabilities` | riskxss, defaultuserrole | Información de capacidades |
| `mdl_role_assignments` | riskxss, riskbackup | Asignaciones de roles |
| `mdl_config` | Todos los checks | Variables de configuración |

### 1.7 Archivos del Core Utilizados

| Archivo | Función | Uso |
|---------|---------|-----|
| `lib/adminlib.php` | `admin_externalpage_setup()` | Control de acceso |
| `lib/adminlib.php` | `is_dataroot_insecure()` | Validación de dataroot |
| `lib/weblib.php` | `is_https()` | Detectar HTTPS |
| `lib/weblib.php` | `is_moodle_cookie_secure()` | Validar cookies |
| `lib/classes/check/manager.php` | `get_security_checks()` | Obtener checks |
| `lib/classes/check/table.php` | `render()` | Renderizar tabla |

---

## 2. Plugin report_log

### 2.1 Ubicación y Estructura

**Ruta:** `report/log/`

```
report/log/
├── classes/
│   ├── event/
│   │   ├── report_viewed.php         # Evento de reporte visto
│   │   └── user_report_viewed.php    # Evento de reporte de usuario
│   ├── privacy/
│   │   └── provider.php
│   ├── helper.php                    # Funciones auxiliares
│   ├── renderable.php                # Clase report_log_renderable
│   ├── renderer.php                  # Renderizador
│   └── table_log.php                 # Tabla SQL de logs
├── db/
│   ├── access.php                    # Capacidades
│   └── install.php
├── graph.php                         # Gráficos de actividad
├── index.php                         # Página principal
├── lib.php                           # API pública
├── locallib.php                      # Funciones internas
├── settings.php
├── user.php                          # Reporte por usuario
└── version.php
```

### 2.2 Página Principal - index.php

**Archivo:** `report/log/index.php`

#### Parámetros Aceptados

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `id` | INT | 0 | ID del curso |
| `group` | INT | 0 | ID del grupo |
| `user` | INT | 0 | ID del usuario |
| `date` | INT | 0 | Timestamp del día |
| `modid` | ALPHANUMEXT | 0 | ID del módulo o 'site_errors' |
| `modaction` | ALPHAEXT | '' | Acción (view, add, update, delete) |
| `page` | INT | 0 | Número de página |
| `perpage` | INT | 100 | Registros por página |
| `download` | ALPHA | '' | Formato de descarga |
| `logreader` | COMPONENT | '' | Plugin reader de logs |
| `edulevel` | INT | -1 | Nivel educativo |
| `origin` | TEXT | '' | Origen (web, cli, restore, ws) |

#### Flujo de Ejecución

```php
// Validación de contexto
if ($course) {
    require_login($course);
    $context = context_course::instance($course->id);
}

// Verificar capacidad
require_capability('report/log:view', $context);

// Crear objeto renderable con todos los filtros
$reportlog = new report_log_renderable(
    $logreader, $course, $user, $modid, $modaction,
    $group, $edulevel, $showcourses, $showusers,
    $chooselog, true, $url, $date, $logformat,
    $page, $perpage, 'timecreated DESC', $origin
);

// Renderizar
$output = $PAGE->get_renderer('report_log');
echo $output->render($reportlog);
```

### 2.3 Clase report_log_table_log

**Archivo:** `report/log/classes/table_log.php`

Esta clase extiende `table_sql` y construye las queries a los log stores.

#### Método query_db() - Construcción de Filtros SQL

```php
public function query_db($pagesize, $useinitialsbar = true) {
    // 1. FILTRO POR CURSO
    if ($this->filterparams->courseid && courseid != SITEID) {
        $joins[] = "courseid = :courseid";
    }

    // 2. FILTRO POR ERRORES DEL SITIO
    if ($this->filterparams->siteerrors) {
        $joins[] = "(action='error' OR action='infected' OR action='failed')";
    }

    // 3. FILTRO POR MÓDULO
    if ($this->filterparams->modid) {
        $joins[] = "contextinstanceid = :contextinstanceid";
        $joins[] = "contextlevel = " . CONTEXT_MODULE;
    }

    // 4. FILTRO POR ACCIÓN (CRUD)
    if ($this->filterparams->action) {
        // c=create, r=read, u=update, d=delete
        $joins[] = "crud IN ('c', 'r', 'u', 'd')";
    }

    // 5. FILTRO POR GRUPO
    $groupfilter = report_helper::get_group_filter($this->filterparams);

    // 6. FILTRO POR FECHA (rango de 24 horas)
    if ($this->filterparams->date) {
        $joins[] = "timecreated > :date AND timecreated < :enddate";
    }

    // 7. FILTRO POR NIVEL EDUCATIVO
    if ($this->filterparams->edulevel >= 0) {
        $joins[] = "edulevel = :edulevel";
    }

    // 8. FILTRO POR ORIGEN
    if ($this->filterparams->origin) {
        $joins[] = "origin = :origin";
    }

    // 9. FILTRO POR ANONIMATO
    if (!has_capability('moodle/site:viewanonymousevents', $context)) {
        $joins[] = "anonymous = 0";
    }

    // Ejecutar query
    $selector = implode(' AND ', $joins);
    $total = $logreader->get_events_select_count($selector, $params);
    $this->rawdata = $logreader->get_events_select_iterator(...);
}
```

#### Columnas de la Tabla

| Columna | Método | Descripción |
|---------|--------|-------------|
| time | `col_time()` | Fecha/hora formateada |
| fullnameuser | `col_fullnameuser()` | Usuario que realizó la acción |
| relatedfullnameuser | `col_relatedfullnameuser()` | Usuario afectado |
| context | `col_context()` | Contexto del evento |
| component | `col_component()` | Componente (mod_forum, core, etc.) |
| eventname | `col_eventname()` | Nombre del evento |
| description | `col_description()` | Descripción del evento |
| origin | `col_origin()` | Origen (web, cli, ws, restore) |
| ip | `col_ip()` | Dirección IP |

### 2.4 Capacidades

**Archivo:** `report/log/db/access.php`

```php
$capabilities = [
    'report/log:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],
    ],
    'report/log:viewtoday' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],
    ],
];
```

### 2.5 Integración con Log Stores

El plugin usa la API de Log Store de Moodle y solo soporta SQL readers.

**Archivo:** `report/log/lib.php`

```php
function report_log_supports_logstore($instance) {
    if ($instance instanceof \core\log\sql_reader) {
        return true;
    }
    return false;
}
```

#### Métodos del SQL Reader Utilizados

```php
// Contar eventos
$total = $reader->get_events_select_count($selector, $params);

// Obtener eventos con paginación
$events = $reader->get_events_select_iterator(
    $selector,      // WHERE clause
    $params,        // Parámetros
    $orderby,       // ORDER BY
    $offset,        // Offset
    $limit          // Límite
);

// Obtener nombre de tabla interna
$logtable = $reader->get_internal_log_table_name();
// Típicamente: 'logstore_standard_log'
```

### 2.6 Tabla de Base de Datos: logstore_standard_log

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | PK Autoincrement |
| `eventname` | VARCHAR | Nombre del evento (ej: '\core\event\course_viewed') |
| `component` | VARCHAR | Componente (mod_forum, core) |
| `action` | VARCHAR | Acción legada (view, add, update, delete) |
| `target` | VARCHAR | Target del evento |
| `objecttable` | VARCHAR | Tabla del objeto |
| `objectid` | BIGINT | ID del objeto |
| `crud` | CHAR(1) | CRUD: c, r, u, d |
| `edulevel` | TINYINT | Nivel educativo: 0, 1, 2 |
| `contextid` | BIGINT | FK context.id |
| `contextlevel` | TINYINT | Nivel del contexto |
| `contextinstanceid` | BIGINT | Instancia del contexto |
| `userid` | BIGINT | FK user.id |
| `courseid` | BIGINT | FK course.id |
| `relateduserid` | BIGINT | Usuario relacionado |
| `anonymous` | TINYINT | Es anónimo (0/1) |
| `other` | LONGTEXT | Datos adicionales JSON |
| `timecreated` | BIGINT | Timestamp Unix |
| `origin` | VARCHAR(10) | web, cli, restore, ws, cron |
| `ip` | VARCHAR(45) | Dirección IP |
| `realuserid` | BIGINT | Usuario real (suplantación) |

### 2.7 Funciones de Gráficos

**Archivo:** `report/log/locallib.php`

```php
// Gráfico de actividad por día en todo el curso
function report_log_usercourse($userid, $courseid, $coursestart, $logreader = '') {
    $sql = "SELECT FLOOR((timecreated - $coursestart)/ 86400) AS day, COUNT(*) AS num
            FROM {$logtable}
            WHERE userid = :userid AND timecreated > $coursestart
            GROUP BY day";
}

// Gráfico de actividad por hora en un día
function report_log_userday($userid, $courseid, $daystart, $logreader = '') {
    $sql = "SELECT FLOOR((timecreated - $daystart)/ 3600) AS hour, COUNT(*) AS num
            FROM {$logtable}
            WHERE userid = :userid AND timecreated > $daystart
            GROUP BY hour";
}
```

### 2.8 Formatos de Descarga

```php
$logformats = [
    'showashtml' => 'Mostrar en página',
    'downloadascsv' => 'Descargar como CSV',
    'downloadasods' => 'Descargar como ODS',
    'downloadasexcel' => 'Descargar como Excel'
];
```

---

## 3. Plugin report_loglive

### 3.1 Ubicación y Estructura

**Ruta:** `report/loglive/`

```
report/loglive/
├── classes/
│   ├── event/
│   │   └── report_viewed.php         # Evento de auditoría
│   ├── privacy/
│   │   └── provider.php
│   ├── renderable.php                # Clase principal
│   ├── renderer.php                  # Renderizador HTML
│   ├── renderer_ajax.php             # Renderizador AJAX/JSON
│   ├── table_log.php                 # Tabla de logs
│   └── table_log_ajax.php            # Tabla para respuestas AJAX
├── db/
│   └── access.php
├── yui/
│   └── src/fetchlogs/
│       └── js/fetchlogs.js           # JavaScript para polling AJAX
├── index.php                         # Página principal
├── loglive_ajax.php                  # Endpoint AJAX
├── lib.php
├── settings.php
└── version.php
```

### 3.2 Diferencias con report_log

| Aspecto | report_loglive | report_log |
|---------|----------------|------------|
| **Enfoque** | Logs en VIVO, tiempo real | Análisis histórico |
| **Actualización** | AJAX polling cada 60s | Estática |
| **Rango de tiempo** | Última 1 hora (CUTOFF) | Cualquier período |
| **Filtros** | Básicos (curso, grupo) | Avanzados (usuario, acción, módulo) |
| **Descarga** | No | Sí (CSV, Excel, ODS) |
| **Gráficos** | No | Sí |
| **Vista de usuario** | No | Sí (user.php) |

### 3.3 Sistema de Actualización en Vivo (AJAX Polling)

#### Inicialización en index.php

```php
if ($page == 0 && !empty($logreader)) {
    $until = $renderable->get_table()->get_until();  // Timestamp más reciente

    $jsparams = [
        'since' => $until,           // Timestamp desde el cual buscar
        'courseid' => $id,
        'page' => $page,
        'logreader' => $logreader,
        'interval' => $refresh,      // Segundos entre actualizaciones (60)
        'perpage' => $renderable->perpage
    ];

    $PAGE->requires->yui_module('moodle-report_loglive-fetchlogs',
        'Y.M.report_loglive.FetchLogs.init', [$jsparams]);
}
```

#### Endpoint AJAX: loglive_ajax.php

```php
define('AJAX_SCRIPT', true);

$id = optional_param('id', 0, PARAM_INT);
$since = optional_param('since', 0, PARAM_INT);
$logreader = optional_param('logreader', '', PARAM_COMPONENT);

// Verificar permisos
require_capability('report/loglive:view', $context);

// Crear renderable con fecha desde 'since'
$renderable = new report_loglive_renderable($logreader, $id, '', $since, $page);

// Retorna JSON: {"logs": "<tr>...</tr>", "until": 1234567890}
$output = $PAGE->get_renderer('report_loglive');
echo $output->render($renderable);
```

#### JavaScript: fetchlogs.js

```javascript
// Inicialización - polling cada 60 segundos
initializer: function() {
    if (this.get('page') === 0) {
        this.callBack = Y.later(this.get('interval') * 1000, this,
                                this.fetchRecentLogs, null, true);
    }
}

// Obtención de logs nuevos
fetchRecentLogs: function() {
    var data = {
        logreader: this.get('logreader'),
        since: this.get('since'),     // Timestamp último log
        page: this.get('page'),
        id: this.get('courseid')
    };

    Y.io(M.cfg.wwwroot + '/report/loglive/loglive_ajax.php', {
        method: 'get',
        data: data,
        on: { complete: this.updateLogTable }
    });
}

// Actualización de tabla
updateLogTable: function(tid, response) {
    var responseobject = Y.JSON.parse(response.responseText);

    // Actualizar 'since' para próxima llamada
    this.set('since', responseobject.until);

    var tbody = Y.one(SELECTORS.TBODY);
    if (tbody && responseobject.logs) {
        // Insertar nuevas filas al inicio
        tbody.insertBefore(responseobject.logs, tbody.get('firstChild'));

        // Eliminar filas antiguas si exceden perpage
        var oldChildren = tbody.get('children').slice(this.get('perpage'));
        oldChildren.remove();
    }
}

// Toggle pause/resume
toggleUpdate: function() {
    if (this.callBack) {
        this.callBack.cancel();
        this.pauseButton.setContent('Resume');
    } else {
        this.callBack = Y.later(this.get('interval') * 1000, this,
                                this.fetchRecentLogs, null, true);
        this.pauseButton.setContent('Pause');
    }
}
```

### 3.4 Clase report_loglive_renderable

**Archivo:** `report/loglive/classes/renderable.php`

```php
class report_loglive_renderable implements renderable {
    const CUTOFF = 3600;  // 1 hora por defecto

    protected $logmanager;
    public $selectedlogreader = null;
    public $page;
    public $perpage = 100;
    public $course;
    public $date;              // Timestamp desde el cual mostrar
    protected $refresh = 60;   // Intervalo de refresco
    public $tablelog;

    public function get_readers($nameonly = false) {
        $this->logmanager = get_log_manager();
        return $this->logmanager->get_readers('core\log\sql_reader');
    }

    protected function setup_filters() {
        $filter = new \stdClass();
        $filter->courseid = $this->course ? $this->course->id : 0;
        $filter->logreader = $readers[$this->selectedlogreader];
        $filter->date = $this->date;
        $filter->orderby = 'timecreated DESC';

        // Filtrar eventos anónimos si no tiene capacidad
        if (!has_capability('moodle/site:viewanonymousevents', $context)) {
            $filter->anonymous = 0;
        }

        return $filter;
    }
}
```

### 3.5 Tabla AJAX: table_log_ajax.php

**Archivo:** `report/loglive/classes/table_log_ajax.php`

```php
class report_loglive_table_log_ajax extends report_loglive_table_log {

    public function out($pagesize, $useinitialsbar, $downloadhelpbutton = '') {
        $this->query_db($pagesize, false);
        $html = '';

        foreach ($this->rawdata as $row) {
            $formatedrow = $this->format_row($row);
            $html .= $this->get_row_html($formatedrow, 'newrow');  // Clase 'newrow'
        }

        // Retorna JSON
        return json_encode([
            'logs' => $html,                  // HTML de filas <tr>
            'until' => $this->get_until()     // Timestamp para próxima llamada
        ]);
    }

    public function get_until(): int {
        $until = $this->filterparams->date;
        foreach ($this->rawdata as $row) {
            $until = max($row->timecreated, $until);
        }
        return $until;
    }
}
```

### 3.6 Capacidad

```php
$capabilities = [
    'report/loglive:view' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],
    ],
];
```

### 3.7 Configuración del Intervalo de Refresco

```php
// En config.php se puede personalizar:
define('REPORT_LOGLIVE_REFRESH', 30);  // Cada 30 segundos

// En renderable.php:
protected function set_refresh_rate() {
    if (defined('REPORT_LOGLIVE_REFRESH')) {
        $this->refresh = REPORT_LOGLIVE_REFRESH;
    } else {
        $this->refresh = 60;  // Default 60 segundos
    }
}
```

---

## 4. Plugin report_performance

### 4.1 Ubicación y Estructura

**Ruta:** `report/performance/`

```
report/performance/
├── classes/
│   └── privacy/
│       └── provider.php              # Null provider
├── db/
│   └── access.php                    # Capacidades
├── lang/en/
│   └── report_performance.php        # Cadenas de idioma
├── index.php                         # Página principal
├── settings.php                      # Registro en admin
└── version.php
```

### 4.2 Página Principal - index.php

**Archivo:** `report/performance/index.php`

```php
define('NO_OUTPUT_BUFFERING', true);

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

// Control de acceso
admin_externalpage_setup('reportperformance', '', null, '', ['pagelayout' => 'report']);

// Parámetro para detalle
$detail = optional_param('detail', '', PARAM_TEXT);

// Instancia tabla de checks de performance
$table = new core\check\table('performance', $url, $detail);

// Renderiza
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('pluginname', 'report_performance'));
echo $table->render($OUTPUT);
echo $OUTPUT->footer();
```

### 4.3 Checks de Performance

Los checks están en `lib/classes/check/performance/`.

#### Lista de Checks

| Check | Clase | Configuración | Estados |
|-------|-------|---------------|---------|
| Designer Mode | `designermode` | `$CFG->themedesignermode` | OK/CRITICAL |
| CacheJS | `cachejs` | `$CFG->cachejs` | OK/CRITICAL |
| Debugging | `debugging` | `$CFG->debugdeveloper` | OK/WARNING |
| Backups | `backups` | `backup_auto_active` | OK/WARNING |
| Stats | `stats` | `$CFG->enablestats` | OK/WARNING |
| DB Schema | `dbschema` | Esquema BD vs install.xml | OK/ERROR |

#### Check: designermode

**Archivo:** `lib/classes/check/performance/designermode.php`

```php
class designermode extends check {
    public function get_result(): result {
        global $CFG;

        if (empty($CFG->themedesignermode)) {
            $status = result::OK;
            $summary = 'Theme designer mode is disabled';
        } else {
            $status = result::CRITICAL;  // Afecta severamente rendimiento
            $summary = 'Theme designer mode is enabled';
        }

        return new result($status, $summary, $details);
    }

    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/search.php', ['query' => 'themedesignermode']),
            get_string('themedesignermode', 'admin')
        );
    }
}
```

#### Check: cachejs

**Archivo:** `lib/classes/check/performance/cachejs.php`

```php
class cachejs extends check {
    public function get_result(): result {
        global $CFG;

        if (empty($CFG->cachejs)) {
            $status = result::CRITICAL;  // JavaScript no cacheado
            $summary = 'JavaScript caching is disabled';
        } else {
            $status = result::OK;
            $summary = 'JavaScript caching is enabled';
        }

        return new result($status, $summary, $details);
    }
}
```

#### Check: debugging

**Archivo:** `lib/classes/check/performance/debugging.php`

```php
class debugging extends check {
    public function get_result(): result {
        global $CFG;

        if (!$CFG->debugdeveloper) {
            $status = result::OK;
            $summary = 'Debug messages are not shown to developers';
        } else {
            $status = result::WARNING;  // Modo developer activo
            $summary = 'Debug messages are shown to developers';
        }

        return new result($status, $summary, $details);
    }

    public function get_action_link(): ?\action_link {
        return new \action_link(
            new \moodle_url('/admin/settings.php', ['section' => 'debugging']),
            get_string('debugging', 'admin')
        );
    }
}
```

#### Check: backups

**Archivo:** `lib/classes/check/performance/backups.php`

```php
class backups extends check {
    public function get_result(): result {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/helper/backup_cron_helper.class.php');

        $enabled = get_config('backup', 'backup_auto_active');

        if ($enabled == \backup_cron_automated_helper::AUTO_BACKUP_ENABLED) {
            $status = result::WARNING;  // Backups automáticos activos
            $summary = 'Automated course backups are enabled';
        } else {
            $status = result::OK;
            $summary = 'Automated course backups are disabled';
        }

        return new result($status, $summary, $details);
    }
}
```

#### Check: stats

**Archivo:** `lib/classes/check/performance/stats.php`

```php
class stats extends check {
    public function get_result(): result {
        global $CFG;

        if (!empty($CFG->enablestats)) {
            $status = result::WARNING;  // Procesamiento de estadísticas activo
            $summary = 'Statistics processing is enabled';
        } else {
            $status = result::OK;
            $summary = 'Statistics processing is disabled';
        }

        return new result($status, $summary, $details);
    }
}
```

#### Check: dbschema

**Archivo:** `lib/classes/check/performance/dbschema.php`

```php
class dbschema extends check {
    public function get_result(): result {
        global $DB;

        $dbmanager = $DB->get_manager();
        $schema = $dbmanager->get_install_xml_schema();

        if (!$errors = $dbmanager->check_database_schema($schema)) {
            return new result(result::OK, 'Database schema is correct', '');
        }

        // Compilar errores
        $details = '';
        foreach ($errors as $tablename => $items) {
            $details .= "<h4>$tablename</h4>";
            foreach ($items as $item) {
                $details .= "<pre>$item</pre>";
            }
        }

        return new result(result::ERROR, 'Database schema errors found', $details);
    }
}
```

### 4.4 Capacidad

```php
$capabilities = [
    'report/performance:view' => [
        'riskbitmask' => RISK_CONFIG,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW
        ],
    ]
];
```

### 4.5 Tablas de Base de Datos Consultadas

| Tabla | Check | Propósito |
|-------|-------|-----------|
| `mdl_config` | designermode, cachejs, debugging, stats | Variables globales |
| `mdl_config_plugins` | backups | Configuración del plugin backup |
| Todas las tablas | dbschema | Comparación de esquema |

---

## 5. Archivos del Core Comunes

### 5.1 Sistema de Checks

| Archivo | Descripción |
|---------|-------------|
| `lib/classes/check/check.php` | Clase base abstracta para checks |
| `lib/classes/check/result.php` | Clase de resultado con estados |
| `lib/classes/check/table.php` | Renderizador de tabla de checks |
| `lib/classes/check/manager.php` | Gestor que obtiene checks por tipo |

### 5.2 Sistema de Log Stores

| Archivo | Descripción |
|---------|-------------|
| `lib/classes/log/manager.php` | Gestor de log stores |
| `lib/classes/log/sql_reader.php` | Interfaz para readers SQL |
| `admin/tool/log/store/standard/classes/log/store.php` | Implementación standard |

### 5.3 Funciones de Administración

| Archivo | Función | Uso |
|---------|---------|-----|
| `lib/adminlib.php` | `admin_externalpage_setup()` | Control de acceso |
| `lib/adminlib.php` | `is_dataroot_insecure()` | Validación de seguridad |
| `lib/moodlelib.php` | `get_config()` | Obtener configuración |
| `lib/weblib.php` | `is_https()` | Detectar HTTPS |

---

## 6. Tablas de Base de Datos - Resumen

### 6.1 Tablas de Configuración

| Tabla | Plugins que la usan | Propósito |
|-------|---------------------|-----------|
| `mdl_config` | security, performance | Variables globales $CFG |
| `mdl_config_plugins` | performance (backups) | Configuración por plugin |

### 6.2 Tablas de Logs

| Tabla | Plugins que la usan | Propósito |
|-------|---------------------|-----------|
| `mdl_logstore_standard_log` | log, loglive | Almacén principal de logs |

**Campos principales de logstore_standard_log:**

```sql
CREATE TABLE mdl_logstore_standard_log (
    id BIGINT PRIMARY KEY,
    eventname VARCHAR(255),      -- '\core\event\course_viewed'
    component VARCHAR(100),      -- 'mod_forum', 'core'
    action VARCHAR(100),         -- 'viewed', 'created'
    target VARCHAR(100),
    objecttable VARCHAR(50),
    objectid BIGINT,
    crud CHAR(1),               -- c, r, u, d
    edulevel TINYINT,           -- 0=other, 1=teacher, 2=participant
    contextid BIGINT,
    contextlevel TINYINT,
    contextinstanceid BIGINT,
    userid BIGINT,
    courseid BIGINT,
    relateduserid BIGINT,
    anonymous TINYINT,
    other LONGTEXT,             -- JSON con datos extra
    timecreated BIGINT,
    origin VARCHAR(10),         -- web, cli, ws, restore
    ip VARCHAR(45),
    realuserid BIGINT
);
```

### 6.3 Tablas de Usuarios y Roles

| Tabla | Plugins que la usan | Propósito |
|-------|---------------------|-----------|
| `mdl_user` | security (riskadmin) | Datos de usuarios |
| `mdl_role` | security | Definición de roles |
| `mdl_role_capabilities` | security | Capacidades por rol |
| `mdl_role_assignments` | security | Asignaciones de roles |
| `mdl_capabilities` | security | Definición de capacidades |

---

## 7. Flujo de Ejecución Comparativo

### 7.1 report_security

```
Usuario → index.php
    → admin_externalpage_setup() [verifica report/security:view]
    → core\check\table('security')
        → manager::get_security_checks() [18 checks]
        → Cada check ejecuta get_result()
            → Accede a $CFG, $DB
            → Retorna result con status
    → render() [genera tabla HTML]
    → Evento report_viewed
```

### 7.2 report_log

```
Usuario → index.php
    → require_login(), require_capability()
    → report_log_renderable($filtros)
        → get_readers() [obtiene SQL readers]
        → setup_table()
            → report_log_table_log($filters)
            → query_db() [construye SQL dinámico]
                → logreader->get_events_select()
    → renderer->render()
        → tablelog->out() [genera tabla con paginación]
    → Evento report_viewed
```

### 7.3 report_loglive

```
Usuario → index.php
    → require_capability()
    → report_loglive_renderable()
        → setup_table()
    → render() [tabla inicial]
    → YUI module init [inicia polling]

[Cada 60 segundos]
JavaScript → loglive_ajax.php
    → require_capability()
    → report_loglive_renderable($since)
        → table_log_ajax->out()
    → JSON: {logs: "<tr>...</tr>", until: timestamp}
JavaScript → insertBefore() [nuevas filas al inicio]
```

### 7.4 report_performance

```
Usuario → index.php
    → admin_externalpage_setup() [verifica report/performance:view]
    → core\check\table('performance')
        → manager::get_performance_checks() [6 checks]
        → Cada check ejecuta get_result()
            → Accede a $CFG, $DB, dbmanager
            → Retorna result con status
    → render() [genera tabla HTML]
```

---

## 8. Resumen de Capacidades

| Plugin | Capacidad | Contexto | Roles Default |
|--------|-----------|----------|---------------|
| report_security | `report/security:view` | SYSTEM | manager |
| report_log | `report/log:view` | COURSE | teacher, editingteacher, manager |
| report_log | `report/log:viewtoday` | COURSE | teacher, editingteacher, manager |
| report_loglive | `report/loglive:view` | COURSE | teacher, editingteacher, manager |
| report_performance | `report/performance:view` | SYSTEM | manager |

---

## 9. Referencias de Archivos

### report_security
- `report/security/index.php` - Página principal
- `report/security/db/access.php` - Capacidades
- `lib/classes/check/security/*.php` - Checks de seguridad
- `lib/classes/check/access/*.php` - Checks de acceso
- `lib/classes/check/environment/*.php` - Checks de ambiente

### report_log
- `report/log/index.php` - Página principal
- `report/log/user.php` - Reporte por usuario
- `report/log/graph.php` - Gráficos
- `report/log/classes/table_log.php` - Tabla SQL
- `report/log/classes/renderable.php` - Clase renderable
- `report/log/locallib.php` - Funciones de gráficos

### report_loglive
- `report/loglive/index.php` - Página principal
- `report/loglive/loglive_ajax.php` - Endpoint AJAX
- `report/loglive/classes/table_log_ajax.php` - Tabla AJAX
- `report/loglive/yui/src/fetchlogs/js/fetchlogs.js` - JavaScript polling

### report_performance
- `report/performance/index.php` - Página principal
- `lib/classes/check/performance/*.php` - Checks de rendimiento
