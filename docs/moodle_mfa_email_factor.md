# Sistema MFA (Multi-Factor Authentication) de Moodle y Factor Email

## Introducción

Este documento explica en detalle el sistema de autenticación multifactor (MFA) de Moodle, con enfoque específico en el plugin `tool_mfa` y el factor de autenticación por email. Se incluyen los archivos del core de Moodle que interactúan con MFA y el flujo completo de autenticación.

---

## 1. Arquitectura General de tool_mfa

### 1.1 Ubicación y Estructura

El plugin MFA se encuentra en `admin/tool/mfa/` y tiene la siguiente estructura:

```
admin/tool/mfa/
├── amd/                          # Módulos JavaScript AMD
├── classes/
│   ├── event/                    # Eventos del sistema
│   │   ├── user_passed_mfa.php
│   │   ├── user_failed_mfa.php
│   │   ├── user_setup_factor.php
│   │   └── user_revoked_factor.php
│   ├── hook/                     # Hooks personalizados
│   │   └── after_user_passed_mfa.php
│   ├── local/
│   │   ├── factor/               # Clases base para factores
│   │   │   ├── object_factor.php
│   │   │   ├── object_factor_base.php
│   │   │   └── fallback.php
│   │   ├── form/                 # Formularios MFA
│   │   │   ├── login_form.php
│   │   │   └── verification_field.php
│   │   └── secret_manager.php
│   ├── output/                   # Renderizador
│   ├── plugininfo/
│   │   └── factor.php            # Información de factores
│   ├── privacy/
│   ├── manager.php               # Clase gestora principal
│   └── hook_callbacks.php        # Manejadores de hooks
├── db/
│   ├── access.php                # Capacidades
│   ├── hooks.php                 # Registro de hooks
│   ├── install.xml               # Esquema de BD
│   └── subplugins.php            # Definición de subplugins
├── factor/                       # Subplugins de factores
│   ├── admin/
│   ├── auth/
│   ├── capability/
│   ├── cohort/
│   ├── email/                    # Factor de email
│   ├── grace/
│   ├── iprange/
│   ├── nosetup/
│   ├── role/
│   ├── sms/
│   ├── token/
│   ├── totp/
│   └── webauthn/
├── templates/
├── auth.php                      # Página de autenticación MFA
├── action.php                    # Acciones de factores
├── lib.php                       # Funciones principales
├── settings.php                  # Configuración
└── version.php
```

### 1.2 Información de Versión

**Archivo:** `admin/tool/mfa/version.php`

```php
$plugin->version   = 2024100700;
$plugin->requires  = 2024100100;
$plugin->component = 'tool_mfa';
$plugin->maturity  = MATURITY_STABLE;
```

---

## 2. Tablas de Base de Datos

### 2.1 Tabla tool_mfa

**Archivo:** `admin/tool/mfa/db/install.xml:7-29`

Almacena los factores configurados por cada usuario:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(10) | Clave primaria |
| `userid` | int(10) | ID del usuario |
| `factor` | char(100) | Tipo de factor (email, totp, etc.) |
| `secret` | char(1333) | Datos secretos (códigos, claves encriptadas) |
| `label` | char(1333) | Etiqueta (email, nombre dispositivo, User-Agent) |
| `timecreated` | int(15) | Timestamp de creación |
| `createdfromip` | char(100) | IP de origen |
| `timemodified` | int(15) | Última modificación |
| `lastverified` | int(15) | Última verificación exitosa |
| `revoked` | int(1) | Flag de revocación |
| `lockcounter` | int(5) | Contador de intentos fallidos |

**Índices:**
- `userid` - Búsquedas por usuario
- `factor` - Búsquedas por tipo de factor
- `userid, factor, lockcounter` - Consultas de bloqueo

### 2.2 Tabla tool_mfa_secrets

**Archivo:** `admin/tool/mfa/db/install.xml:30-49`

Almacena tokens temporales de verificación:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(10) | Clave primaria |
| `userid` | int(10) | FK a usuario |
| `factor` | char(100) | Tipo de factor |
| `secret` | char(1333) | Código/token temporal |
| `timecreated` | int(15) | Timestamp creación |
| `expiry` | int(15) | Timestamp expiración |
| `revoked` | int(1) | Flag revocado |
| `sessionid` | char(100) | ID de sesión asociada |

### 2.3 Tabla tool_mfa_auth

**Archivo:** `admin/tool/mfa/db/install.xml:50-60`

Registra última autenticación MFA exitosa:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int(10) | Clave primaria |
| `userid` | int(10) | FK a usuario |
| `lastverified` | int(15) | Timestamp último MFA exitoso |

---

## 3. Clase Manager - Núcleo del Sistema

**Archivo:** `admin/tool/mfa/classes/manager.php` (~906 líneas)

### 3.1 Constantes de Redirección

```php
const REDIRECT = 1;             // Redirigir a auth.php
const NO_REDIRECT = 0;          // No redirigir
const REDIRECT_EXCEPTION = -1;  // Lanzar excepción (loop detectado)
const REDIR_LOOP_THRESHOLD = 5; // Umbral para detectar loops
```

### 3.2 Método require_auth()

**Líneas 648-705** - Punto de entrada principal del sistema MFA:

```php
public static function require_auth($courseorid = null, $autologinguest = null,
    $cm = null, $setwantsurltome = null, $preventredirect = null): void {
    global $SESSION;

    // No aplica a invitados
    if (isguestuser()) {
        return;
    }

    // Verificar si MFA está listo
    if (!self::is_ready()) {
        $SESSION->tool_mfa_authenticated = true;
        return;
    }

    // Ya autenticado
    if (empty($SESSION->tool_mfa_authenticated)) {
        $redir = self::should_require_mfa($cleanurl, $preventredirect);

        if ($redir == self::REDIRECT) {
            self::resolve_mfa_status(true);
        } else if ($redir == self::REDIRECT_EXCEPTION) {
            // Loop detectado - lanzar excepción
        }
    }
}
```

### 3.3 Método is_ready()

**Líneas 739-763** - Verifica si MFA debe activarse:

```php
public static function is_ready(): bool {
    global $CFG, $USER;

    // No durante upgrades
    if (!empty($CFG->upgraderunning)) {
        return false;
    }

    // Plugin habilitado
    $pluginenabled = get_config('tool_mfa', 'enabled');
    if (empty($pluginenabled)) {
        return false;
    }

    // Usuario tiene capacidad
    if (!has_capability('tool/mfa:mfaaccess',
        context_user::instance($USER->id))) {
        return false;
    }

    // Al menos un factor habilitado
    $enabledfactors = factor::get_enabled_factors();
    return count($enabledfactors) > 0;
}
```

### 3.4 Método get_status()

**Líneas 239-274** - Calcula el estado actual del usuario:

```php
public static function get_status(): string {
    $dominated = false;
    $dominated_weight = 0;
    $dominated_state = factor::STATE_NEUTRAL;

    // Verificar todos los factores activos
    $factors = factor::get_active_user_factor_types();

    foreach ($factors as $factor) {
        $state = $factor->get_state();

        // Si algún factor falló, retornar FAIL
        if ($state == factor::STATE_FAIL) {
            return factor::STATE_FAIL;
        }

        // Acumular peso de factores PASS
        if ($state == factor::STATE_PASS) {
            $dominated_weight += $factor->get_weight();
        }
    }

    // Si peso >= 100, usuario pasa MFA
    if ($dominated_weight >= 100) {
        return factor::STATE_PASS;
    }

    return factor::STATE_NEUTRAL;
}
```

### 3.5 Método set_pass_state()

**Líneas 340-402** - Establece que el usuario pasó MFA:

```php
public static function set_pass_state(): void {
    global $SESSION, $USER, $DB;

    if (!isset($SESSION->tool_mfa_authenticated)) {
        // Marcar como autenticado
        $SESSION->tool_mfa_authenticated = true;

        // Disparar evento
        $event = \tool_mfa\event\user_passed_mfa::user_passed_mfa_event($USER);
        $event->trigger();

        // Disparar hook para otros plugins
        $hook = new \tool_mfa\hook\after_user_passed_mfa();
        \core\di::get(\core\hook\manager::class)->dispatch($hook);

        // Actualizar tiempo de última verificación
        self::update_pass_time();

        // Limpiar contadores de bloqueo
        $DB->set_field('tool_mfa', 'lockcounter', 0, ['userid' => $USER->id]);
    }
}
```

### 3.6 Método should_require_mfa()

**Líneas 430-577** - Determina si debe redirigir a MFA:

**URLs Excluidas de Redirección:**
- URLs de upgrade del sistema
- Aceptación de políticas del sitio
- Requests AJAX/WS (lanza excepción)
- Página de login/logout
- Cambio de contraseña forzado
- URLs configuradas en `tool_mfa/redir_exclusions`
- La propia página `/admin/tool/mfa/auth.php`

**Detección de Loops:**
```php
// Líneas 551-567
if ($SESSION->mfa_redir_referer == get_local_referer(true)) {
    if (!isset($SESSION->mfa_redir_count)) {
        $SESSION->mfa_redir_count = 1;
    } else {
        $SESSION->mfa_redir_count++;
    }

    if ($SESSION->mfa_redir_count > self::REDIR_LOOP_THRESHOLD) {
        return self::REDIRECT_EXCEPTION;
    }
}
```

---

## 4. Estados de los Factores

**Archivo:** `admin/tool/mfa/classes/plugininfo/factor.php`

### 4.1 Constantes de Estado

```php
const STATE_UNKNOWN = 'unknown';  // Aún no verificado
const STATE_PASS = 'pass';        // Verificación exitosa
const STATE_FAIL = 'fail';        // Verificación fallida
const STATE_NEUTRAL = 'neutral';  // No aplica / no contribuye
const STATE_LOCKED = 'locked';    // Bloqueado por intentos fallidos
```

### 4.2 Métodos Principales de factor.php

**get_factors()** - Líneas 52-63:
```php
public static function get_factors(): array {
    $factors = \core_plugin_manager::instance()->get_plugins_of_type('factor');
    foreach ($factors as $factor) {
        $classname = '\\factor_'.$factor->name.'\\factor';
        if (class_exists($classname)) {
            $return[] = new $classname($factor->name);
        }
    }
    return self::sort_factors_by_order($return);
}
```

**get_next_user_login_factor()** - Líneas 161-175:
```php
public static function get_next_user_login_factor(): mixed {
    $factors = self::get_active_user_factor_types();

    foreach ($factors as $factor) {
        // Saltar factores sin entrada de usuario
        if (!$factor->has_input()) {
            continue;
        }

        // Retornar el primero sin verificar
        if ($factor->get_state() == self::STATE_UNKNOWN) {
            return $factor;
        }
    }

    return new \tool_mfa\local\factor\fallback();
}
```

---

## 5. Factores Disponibles

### 5.1 Tabla de Factores

| Factor | Tipo | Entrada | Descripción |
|--------|------|---------|-------------|
| **totp** | Input | Sí | Time-based OTP (Google Authenticator) |
| **email** | Input | Sí | Código por correo electrónico |
| **sms** | Input | Sí | Código por SMS |
| **token** | Input | Sí | Token de recuperación |
| **webauthn** | Input | Sí | FIDO2/WebAuthn (llaves de seguridad) |
| **admin** | Silent | No | Basado en ser administrador |
| **auth** | Silent | No | Basado en método de autenticación |
| **capability** | Silent | No | Basado en capacidad específica |
| **cohort** | Silent | No | Basado en pertenencia a cohorte |
| **role** | Silent | No | Basado en rol |
| **grace** | Silent | No | Período de gracia |
| **iprange** | Silent | No | Basado en rango IP |
| **nosetup** | Always | No | Nunca requiere MFA (fallback) |

### 5.2 Sistema de Pesos

Cada factor tiene un peso configurable. El usuario pasa MFA cuando la suma de pesos de factores con STATE_PASS >= 100.

**Ejemplo:**
- TOTP (peso: 80) + Email (peso: 30) = 110 >= 100 ✓ PASS
- Solo Email (peso: 100) = 100 >= 100 ✓ PASS
- Solo IPRange (peso: 50) = 50 < 100 ✗ Continúa

---

## 6. Factor Email - Análisis Detallado

### 6.1 Estructura del Factor

**Ubicación:** `admin/tool/mfa/factor/email/`

```
email/
├── classes/
│   ├── factor.php                 # Clase principal
│   ├── form/
│   │   └── email.php              # Formulario de revocación
│   ├── output/
│   │   └── renderer.php           # Renderizador de emails
│   ├── event/
│   │   └── unauth_email.php       # Evento de acceso no autorizado
│   └── privacy/
│       └── provider.php
├── email.php                       # Página de autenticación/revocación
├── settings.php                    # Configuración
├── templates/
│   └── email.mustache             # Template del email
├── lang/en/
│   └── factor_email.php
├── tests/
│   └── factor_test.php
└── version.php
```

### 6.2 Clase Principal - factor.php

**Archivo:** `admin/tool/mfa/factor/email/classes/factor.php`

#### Herencia:
```php
class factor extends object_factor_base {
    protected $icon = 'fa-envelope';
}
```

#### Método login_form_definition() - Líneas 42-46:
```php
public function login_form_definition(\MoodleQuickForm $mform): \MoodleQuickForm {
    $mform->addElement(new \tool_mfa\local\form\verification_field());
    $mform->setType('verificationcode', PARAM_ALPHANUM);
    return $mform;
}
```

#### Método login_form_definition_after_data() - Líneas 54-57:
```php
public function login_form_definition_after_data(\MoodleQuickForm $mform): \MoodleQuickForm {
    $this->generate_and_email_code();
    return $mform;
}
```

### 6.3 Generación de Códigos

**Método generate_and_email_code()** - Líneas 178-223:

```php
private function generate_and_email_code(): void {
    global $DB, $USER;

    // Buscar código existente (excluyendo el registro base)
    $sql = 'SELECT *
              FROM {tool_mfa}
             WHERE userid = ?
               AND factor = ?
           AND NOT label = ?';

    $record = $DB->get_record_sql($sql, [$USER->id, 'email', $USER->email]);
    $duration = get_config('factor_email', 'duration');

    // Generar código de 6 dígitos
    $newcode = random_int(100000, 999999);

    if (empty($record)) {
        // Crear nuevo registro de código
        $instanceid = $DB->insert_record('tool_mfa', [
            'userid' => $USER->id,
            'factor' => 'email',
            'secret' => $newcode,
            'label' => $_SERVER['HTTP_USER_AGENT'],  // Identifica dispositivo
            'timecreated' => time(),
            'createdfromip' => $USER->lastip,
            'timemodified' => time(),
            'lastverified' => time(),
            'revoked' => 0,
        ]);

        // Enviar email
        $this->email_verification_code($instanceid);

    } else if ($record->timecreated + $duration < time()) {
        // Código expirado, regenerar
        $DB->update_record('tool_mfa', [
            'id' => $record->id,
            'secret' => $newcode,
            'label' => $_SERVER['HTTP_USER_AGENT'],
            'timecreated' => time(),
            'createdfromip' => $USER->lastip,
            'timemodified' => time(),
            'lastverified' => time(),
            'revoked' => 0,
        ]);

        $this->email_verification_code($record->id);
    }
}
```

### 6.4 Envío del Email

**Método email_verification_code()** - Líneas 65-72:

```php
public static function email_verification_code(int $instanceid): void {
    global $PAGE, $USER;

    // Usuario de no-respuesta del sistema
    $noreplyuser = \core_user::get_noreply_user();

    // Asunto del email
    $subject = get_string('email:subject', 'factor_email');

    // Renderizar cuerpo del email
    $renderer = $PAGE->get_renderer('factor_email');
    $body = $renderer->generate_email($instanceid);

    // Enviar usando sistema de mensajes de Moodle
    email_to_user($USER, $noreplyuser, $subject, $body, $body);
}
```

### 6.5 Renderizado del Email

**Archivo:** `admin/tool/mfa/factor/email/classes/output/renderer.php`

**Método generate_email()** - Líneas 36-64:

```php
public function generate_email(int $instanceid): string|bool {
    global $DB, $USER, $CFG;

    $instance = $DB->get_record('tool_mfa', ['id' => $instanceid]);
    $site = get_site();
    $validity = get_config('factor_email', 'duration');

    // URL para autenticar directamente desde el email
    $authurl = new \moodle_url('/admin/tool/mfa/factor/email/email.php',
        ['instance' => $instance->id, 'pass' => 1, 'secret' => $instance->secret]);
    $authurlstring = \html_writer::link($authurl,
        get_string('email:link', 'factor_email'));

    // URL para bloquear acceso no autorizado
    $blockurl = new \moodle_url('/admin/tool/mfa/factor/email/email.php',
        ['instance' => $instance->id, 'secret' => $instance->secret]);
    $blockurlstring = \html_writer::link($blockurl,
        get_string('email:stoploginlink', 'factor_email'));

    // Información geográfica de la IP
    $geoinfo = iplookup_find_location($instance->createdfromip);

    // Datos para el template
    $templateinfo = [
        'logo' => $this->get_compact_logo_url(100, 100),
        'name' => $USER->firstname,
        'sitename' => $site->fullname,
        'siteurl' => $CFG->wwwroot,
        'code' => $instance->secret,           // Código de 6 dígitos
        'validity' => format_time($validity),  // "30 minutes"
        'authlink' => get_string('email:loginlink', 'factor_email', $authurlstring),
        'revokelink' => get_string('email:revokelink', 'factor_email', $blockurlstring),
        'ip' => $instance->createdfromip,
        'geocity' => $geoinfo['city'],
        'geocountry' => $geoinfo['country'],
        'ua' => $instance->label,              // User Agent
    ];

    return $this->render_from_template('factor_email/email', $templateinfo);
}
```

### 6.6 Template del Email

**Archivo:** `admin/tool/mfa/factor/email/templates/email.mustache`

```mustache
<div style="font-family: Arial, sans-serif; font-size: 18px">
    {{#logo}}
        <table style="width: 600px; margin-bottom: 10px;">
            <tr>
                <td></td>
                <td style="text-align: right;">
                    <img src="{{{logo}}}" alt="{{sitename}}" />
                </td>
            </tr>
        </table>
    {{/logo}}

    <!-- Saludo personalizado -->
    <p>{{#str}} email:greeting, factor_email, {{name}}{{/str}}</p>

    <!-- Mensaje principal -->
    <p>{{#str}} email:message, factor_email, {"sitename":{{#quote}}{{sitename}}{{/quote}},
        "siteurl":{{#quote}}{{siteurl}}{{/quote}} }{{/str}}</p>

    <!-- Código de verificación -->
    <h2 style="letter-spacing: 5px;">{{code}}</h2>

    <!-- Validez del código -->
    <p>{{#str}} email:validity, factor_email, {{validity}}{{/str}}</p>

    <!-- Enlaces de autenticación y revocación -->
    {{{authlink}}}
    {{{revokelink}}}

    <!-- Información de seguridad -->
    <div style="font-family: Arial, sans-serif; font-size: 12px">
        <p><strong>{{#str}} email:ipinfo, factor_email {{/str}}</strong></p>
        <p>{{#str}} email:originatingip, factor_email, {{ip}} {{/str}}</p>
        {{#geocountry}}
            <p>{{#str}} email:geoinfo, factor_email {{/str}}
               {{#geocity}}{{geocity}},{{/geocity}} {{geocountry}}</p>
        {{/geocountry}}
        <p><strong>{{#str}} email:uadescription, factor_email {{/str}}</strong></p>
        <p>{{ua}}</p>
    </div>
</div>
```

### 6.7 Validación de Códigos

**Método check_verification_code()** - Líneas 231-251:

```php
private function check_verification_code(string $enteredcode): bool {
    global $DB, $USER;

    $duration = get_config('factor_email', 'duration');

    // Obtener registro del código
    $sql = 'SELECT *
              FROM {tool_mfa}
             WHERE userid = ?
               AND factor = ?
           AND NOT label = ?';

    $record = $DB->get_record_sql($sql, [$USER->id, 'email', $USER->email]);

    // Validación doble: código correcto Y no expirado
    if ($enteredcode == $record->secret) {
        if ($record->timecreated + $duration > time()) {
            return true;
        }
    }

    return false;
}
```

**Uso en validación del formulario** - Líneas 80-89:

```php
public function login_form_validation(array $data): array {
    global $USER;
    $return = [];

    if (!$this->check_verification_code($data['verificationcode'])) {
        $return['verificationcode'] = get_string('error:wrongverification', 'factor_email');
    }

    return $return;
}
```

### 6.8 Configuración del Factor

**Archivo:** `admin/tool/mfa/factor/email/settings.php`

```php
// Habilitar/Deshabilitar
$settings->add(new admin_setting_configcheckbox('factor_email/enabled',
    new lang_string('settings:enablefactor', 'tool_mfa'),
    new lang_string('settings:enablefactor_help', 'tool_mfa'), 0));

// Peso del factor
$settings->add(new admin_setting_configtext('factor_email/weight',
    new lang_string('settings:weight', 'tool_mfa'),
    new lang_string('settings:weight_help', 'tool_mfa'), 100, PARAM_INT));

// Duración del código (default: 30 minutos)
$settings->add(new admin_setting_configduration('factor_email/duration',
    get_string('settings:duration', 'factor_email'),
    get_string('settings:duration_help', 'factor_email'),
    30 * MINSECS,    // 1800 segundos
    MINSECS));       // Mínimo: 1 minuto

// Suspender cuentas no autorizadas
$settings->add(new admin_setting_configcheckbox('factor_email/suspend',
    get_string('settings:suspend', 'factor_email'),
    get_string('settings:suspend_help', 'factor_email'), 0));
```

| Configuración | Clave | Valor Default | Descripción |
|---------------|-------|---------------|-------------|
| Habilitado | `factor_email/enabled` | 0 | Activa el factor |
| Peso | `factor_email/weight` | 100 | Contribución al total |
| Duración | `factor_email/duration` | 1800 seg | Validez del código |
| Suspender | `factor_email/suspend` | 0 | Suspender cuenta si no autorizado |

### 6.9 Verificación de Preparación

**Método is_ready()** - Líneas 153-171:

```php
private static function is_ready(): bool {
    global $DB, $USER;

    // Email no vacío
    if (empty($USER->email)) {
        return false;
    }

    // Email válido
    if (!validate_email($USER->email)) {
        return false;
    }

    // Usuario no ha superado umbral de bounces
    if (over_bounce_threshold($USER)) {
        return false;
    }

    // Factor no revocado
    if ($DB->record_exists('tool_mfa',
        ['userid' => $USER->id, 'factor' => 'email', 'revoked' => 1])) {
        return false;
    }

    return true;
}
```

### 6.10 Revocación de Acceso

**Archivo:** `admin/tool/mfa/factor/email/email.php` - Líneas 75-102

```php
if ($fromform = $form->get_data()) {
    // Validar instancia y código
    if (empty($instance) || empty($secret) || $instance->secret != $secret) {
        $message = get_string('error:badcode', 'factor_email');
    } else {
        $user = $DB->get_record('user', ['id' => $instance->userid]);

        // 1. Revocar TODOS los registros de email
        $DB->set_field('tool_mfa', 'revoked', 1,
            ['userid' => $user->id, 'factor' => 'email']);

        // 2. Destruir todas las sesiones del usuario
        \core\session\manager::destroy_user_sessions($instance->userid);

        // 3. Registrar evento de seguridad
        $ip = $instance->createdfromip;
        $useragent = $instance->label;
        $event = \factor_email\event\unauth_email::unauth_email_event(
            $user, $ip, $useragent);
        $event->trigger();

        // 4. Opcionalmente suspender la cuenta
        if (get_config('factor_email', 'suspend')) {
            $DB->set_field('user', 'suspended', 1, ['id' => $user->id]);
        }

        $message = get_string('email:revokesuccess', 'factor_email',
            fullname($user));
    }
}
```

### 6.11 Limpieza Post-MFA

**Método post_pass_state()** - Líneas 258-268:

```php
public function post_pass_state(): void {
    global $DB, $USER;

    // Eliminar todos los códigos temporales
    $selectsql = 'userid = ?
              AND factor = ?
          AND NOT label = ?';

    $DB->delete_records_select('tool_mfa', $selectsql,
        [$USER->id, 'email', $USER->email]);

    // Actualizar última verificación
    parent::post_pass_state();
}
```

---

## 7. Archivos del Core Afectados por MFA

### 7.1 lib/setup.php

**Líneas 1211-1213** - Primer punto de integración MFA:

```php
$afterconfighook = new \core\hook\after_config();
$afterconfighook->process_legacy_callbacks();
\core\di::get(\core\hook\manager::class)->dispatch($afterconfighook);
```

El hook `after_config` es escuchado por MFA en `hook_callbacks.php`.

### 7.2 lib/moodlelib.php

#### Función require_login() - Línea 2254

Define el punto de entrada principal para autenticación.

#### Hook after_require_login - Líneas 2423-2446 y 2663-2667

```php
// Para administradores (líneas 2440-2445)
$hook = new \core_user\hook\after_require_login($courseorid, $autologinguest,
    $cm, $setwantsurltome, $preventredirect);
$hook->process_legacy_callbacks();
\core\di::get(\core\hook\manager::class)->dispatch($hook);

// Para usuarios normales (líneas 2663-2667)
$hook = new \core_user\hook\after_require_login($courseorid, $autologinguest,
    $cm, $setwantsurltome, $preventredirect);
$hook->process_legacy_callbacks();
\core\di::get(\core\hook\manager::class)->dispatch($hook);
```

#### Función complete_user_login() - Línea 4104

```php
function complete_user_login($user) {
    // ... configuración de sesión ...

    // Evento user_loggedin (líneas 4120-4130)
    $event = \core\event\user_loggedin::create([
        'userid' => $user->id,
        'objectid' => $user->id,
        'other' => ['username' => $user->username],
    ]);
    $event->trigger();

    // Hook after_login_completed (línea 4133)
    $hook = new \core_user\hook\after_login_completed($user);
    \core\di::get(\core\hook\manager::class)->dispatch($hook);
}
```

### 7.3 login/index.php

**Línea 215** - Completa el login:

```php
complete_user_login($user);
```

### 7.4 admin/tool/mfa/lib.php

**Función tool_mfa_after_require_login()** - Líneas 41-53:

```php
function tool_mfa_after_require_login($courseorid = null, $autologinguest = null,
    $cm = null, $setwantsurltome = null, $preventredirect = null): void {
    global $SESSION;

    if (empty($SESSION->tool_mfa_authenticated)) {
        \tool_mfa\manager::require_auth($courseorid, $autologinguest,
            $cm, $setwantsurltome, $preventredirect);
    }
}
```

### 7.5 admin/tool/mfa/classes/hook_callbacks.php

**Método after_config()** - Líneas 34-56:

```php
public static function after_config(\core\hook\after_config $hook): void {
    global $CFG, $SESSION;

    if (during_initial_install() || isset($CFG->upgraderunning)) {
        return;
    }

    if (isloggedin() && !isguestuser()) {
        if (empty($SESSION->tool_mfa_authenticated)) {
            \tool_mfa\manager::require_auth();
        }
    }
}
```

### 7.6 admin/tool/mobile/

**Escucha del hook after_user_passed_mfa:**

**Archivo:** `admin/tool/mobile/db/hooks.php` - Líneas 43-46:

```php
[
    'hook' => tool_mfa\hook\after_user_passed_mfa::class,
    'callback' => 'tool_mobile\local\hooks\user\after_user_passed_mfa::callback',
    'priority' => 500,
],
```

**Callback:** `admin/tool/mobile/classes/local/hooks/user/after_user_passed_mfa.php`

```php
public static function callback(after_user_passed_mfa $hook): void {
    global $SESSION;

    // Verificar si viene de la app móvil
    if (!empty($_COOKIE['tool_mobile_launch'])) {
        $SESSION->tool_mfa_has_been_redirected = true;
    }
}
```

---

## 8. Variables de Sesión

### 8.1 Variables Principales

| Variable | Descripción | Ubicación |
|----------|-------------|-----------|
| `$SESSION->tool_mfa_authenticated` | Boolean: usuario pasó MFA | manager.php:343 |
| `$SESSION->tool_mfa_has_been_redirected` | Boolean: redirigido a auth.php | manager.php:306 |
| `$SESSION->wantsurl` | URL destino post-MFA | manager.php:683-686 |
| `$SESSION->tool_mfa_setwantsurl` | Boolean: wantsurl auto-establecido | manager.php:688 |
| `$SESSION->mfa_pending` | Boolean: pendiente de MFA | manager.php:679 |
| `$SESSION->mfa_redir_referer` | String: referer para detectar loops | manager.php:545 |
| `$SESSION->mfa_redir_count` | Int: contador de redirecciones | manager.php:549 |

### 8.2 Estados de Factores en Sesión

```php
$SESSION->mfa_pending_factors['factor_name'] = 'unknown'|'pass'|'fail'|'neutral'|'locked'
```

---

## 9. Eventos del Sistema

### 9.1 Eventos de MFA

**Archivo:** `admin/tool/mfa/classes/event/`

| Evento | Disparado en | Descripción |
|--------|--------------|-------------|
| `user_passed_mfa` | manager.php:344 | Usuario completó MFA |
| `user_failed_mfa` | manager.php (en fallo) | Usuario falló MFA |
| `user_setup_factor` | action.php | Usuario configuró factor |
| `user_revoked_factor` | action.php | Usuario revocó factor |
| `user_deleted_factor` | (admin) | Admin eliminó factor |

### 9.2 Evento del Factor Email

**Archivo:** `admin/tool/mfa/factor/email/classes/event/unauth_email.php`

```php
public static function unauth_email_event(stdClass $user, string $ip,
    string $useragent): \core\event\base {
    $data = [
        'relateduserid' => null,
        'context' => \context_user::instance($user->id),
        'other' => [
            'userid' => $user->id,
            'ip' => $ip,
            'useragent' => $useragent,
        ],
    ];

    return self::create($data);
}
```

---

## 10. Hooks del Sistema

### 10.1 Hooks Escuchados por MFA

**Archivo:** `admin/tool/mfa/db/hooks.php`

```php
$callbacks = [
    // Hook principal de MFA
    [
        'hook' => \core\hook\after_config::class,
        'callback' => [\tool_mfa\hook_callbacks::class, 'after_config'],
    ],
    // Acciones en masa de usuarios
    [
        'hook' => \core_user\hook\extend_bulk_user_actions::class,
        'callback' => [\tool_mfa\hook_callbacks::class, 'extend_bulk_user_actions'],
    ],
];
```

### 10.2 Hooks Disparados por MFA

**Hook personalizado:** `admin/tool/mfa/classes/hook/after_user_passed_mfa.php`

```php
class after_user_passed_mfa implements StoppableEventInterface {
    // Permite que otros plugins ejecuten lógica post-MFA
}
```

Despachado en manager.php línea 348-349:
```php
$hook = new \tool_mfa\hook\after_user_passed_mfa();
\core\di::get(\core\hook\manager::class)->dispatch($hook);
```

---

## 11. Flujo Completo de Autenticación

### 11.1 Diagrama de Flujo

```
┌─────────────────────────────────────────────────────────────────────┐
│                    FLUJO DE AUTENTICACIÓN MFA                       │
└─────────────────────────────────────────────────────────────────────┘

1. Usuario hace login
   └── login/index.php (línea 215)
       └── complete_user_login($user)

2. Sistema ejecuta hooks post-login
   └── lib/moodlelib.php (línea 4133)
       └── Hook: after_login_completed

3. Usuario intenta acceder a página protegida
   └── require_login() [lib/moodlelib.php:2254]

4. Hook after_require_login se dispara
   └── lib/moodlelib.php (líneas 2440-2446)
       └── tool_mfa_after_require_login() [admin/tool/mfa/lib.php:41]

5. MFA verifica si debe intervenir
   └── manager::require_auth() [manager.php:648]
       │
       ├── ¿Es invitado? → SÍ: Salir
       │
       ├── ¿MFA ready? → NO: Salir
       │   └── is_ready() verifica:
       │       - Plugin habilitado
       │       - Factores habilitados
       │       - Usuario tiene capacidad
       │
       └── ¿Ya autenticado? ($SESSION->tool_mfa_authenticated)
           └── SÍ: Salir
           └── NO: should_require_mfa()
               │
               ├── NO_REDIRECT: Salir
               │   (URLs excluidas, AJAX, etc.)
               │
               ├── REDIRECT_EXCEPTION: Lanzar excepción
               │   (Loop detectado)
               │
               └── REDIRECT: Continuar a paso 6

6. Redirigir a página de autenticación MFA
   └── manager::resolve_mfa_status(true)
       └── Guarda $SESSION->wantsurl
       └── Redirige a /admin/tool/mfa/auth.php

7. Página auth.php procesa MFA
   └── admin/tool/mfa/auth.php
       │
       ├── resolve_mfa_status() → get_status()
       │   └── Calcula peso total de factores PASS
       │
       ├── get_next_user_login_factor()
       │   └── Retorna primer factor sin verificar
       │
       └── Crea formulario de login del factor

8. Usuario completa verificación del factor
   │
   ├── Factor Email:
   │   └── generate_and_email_code()
   │       └── random_int(100000, 999999)
   │       └── Inserta en tool_mfa
   │       └── email_verification_code()
   │           └── email_to_user()
   │
   ├── Usuario ingresa código
   │   └── login_form_validation()
   │       └── check_verification_code()
   │           ├── Compara código
   │           └── Verifica expiración
   │
   └── Si válido: set_state(STATE_PASS)

9. Verificar si completó MFA
   └── resolve_mfa_status(true)
       └── get_status()
           │
           ├── Peso total < 100: Siguiente factor (volver a 7)
           │
           └── Peso total >= 100: set_pass_state()

10. Usuario pasó MFA
    └── manager::set_pass_state() [manager.php:340]
        │
        ├── $SESSION->tool_mfa_authenticated = true
        │
        ├── Evento: user_passed_mfa (línea 344)
        │   └── \tool_mfa\event\user_passed_mfa::trigger()
        │
        ├── Hook: after_user_passed_mfa (línea 348)
        │   └── tool_mobile escucha y procesa
        │
        ├── update_pass_time()
        │   └── Actualiza tool_mfa_auth.lastverified
        │
        └── Limpia contadores de bloqueo

11. Redirigir a destino original
    └── redirect($SESSION->wantsurl) o site_home

12. Acceso normal sin más interrupciones de MFA
```

### 11.2 Ejemplo Práctico: MFA con Email

```
1. Usuario: admin@ejemplo.com hace login
   └── Credenciales válidas → complete_user_login()

2. Usuario intenta acceder a /admin/index.php
   └── require_login() se ejecuta

3. MFA intercepta:
   └── tool_mfa_after_require_login()
       └── $SESSION->tool_mfa_authenticated está vacío
       └── manager::require_auth()
           └── is_ready() = true (MFA habilitado, email factor activo)
           └── should_require_mfa() = REDIRECT
           └── Guarda: $SESSION->wantsurl = '/admin/index.php'
           └── Redirige a: /admin/tool/mfa/auth.php

4. En auth.php:
   └── get_next_user_login_factor() retorna factor_email
   └── Factor email ejecuta login_form_definition_after_data()
       └── generate_and_email_code()
           └── Genera: 847293
           └── INSERT INTO tool_mfa (secret='847293', label='Mozilla/5.0...')
           └── email_verification_code()
               └── Renderiza template con código
               └── email_to_user() envía a admin@ejemplo.com

5. Email enviado contiene:
   ┌─────────────────────────────────────────────────┐
   │ Hello Admin 👋                                  │
   │                                                 │
   │ Here's your verification code for Mi Sitio     │
   │ (https://misitio.com).                         │
   │                                                 │
   │ 847293                                          │
   │                                                 │
   │ The code can only be used once and is valid    │
   │ for 30 minutes.                                │
   │                                                 │
   │ Or, if you're on the same device, use this     │
   │ link to login.                                 │
   │                                                 │
   │ If this wasn't you, you can block this login.  │
   │                                                 │
   │ IP Information:                                │
   │ Originating IP: 192.168.1.100                  │
   │ Location: Madrid, Spain                        │
   │                                                 │
   │ Device: Mozilla/5.0 (Windows NT 10.0...)       │
   └─────────────────────────────────────────────────┘

6. Usuario ingresa código: 847293
   └── login_form_validation()
       └── check_verification_code('847293')
           └── SELECT * FROM tool_mfa WHERE userid=1 AND factor='email'
           └── '847293' == record.secret ✓
           └── record.timecreated + 1800 > time() ✓
           └── return true

7. Factor email: set_state(STATE_PASS)
   └── Peso = 100 (configurado)

8. resolve_mfa_status():
   └── get_status() = STATE_PASS (peso 100 >= 100)
   └── set_pass_state()
       └── $SESSION->tool_mfa_authenticated = true
       └── Evento: user_passed_mfa
       └── Hook: after_user_passed_mfa
       └── DELETE FROM tool_mfa WHERE label != email (limpia códigos)

9. Redirige a $SESSION->wantsurl = '/admin/index.php'

10. Usuario accede normalmente sin más interrupciones
```

---

## 12. Seguridad

### 12.1 Bloqueo por Intentos Fallidos

**Archivo:** `admin/tool/mfa/classes/local/factor/object_factor_base.php`

```php
public function load_locked_state(): void {
    global $DB, $USER;

    $sql = "SELECT MAX(lockcounter) FROM {tool_mfa}
            WHERE userid = ? AND factor = ? AND revoked = ?";
    $this->lockcounter = $DB->get_field_sql($sql, [$USER->id, $this->name, 0]);

    $lockthreshold = get_config('tool_mfa', 'lockout'); // Default: 10

    if ($this->lockcounter >= $lockthreshold) {
        $this->set_state(\tool_mfa\plugininfo\factor::STATE_LOCKED);
    }
}
```

### 12.2 Protección contra Loops

**Archivo:** manager.php - Líneas 551-567

```php
if ($SESSION->mfa_redir_referer == get_local_referer(true)) {
    if (!isset($SESSION->mfa_redir_count)) {
        $SESSION->mfa_redir_count = 1;
    } else {
        $SESSION->mfa_redir_count++;
    }

    if ($SESSION->mfa_redir_count > self::REDIR_LOOP_THRESHOLD) { // 5
        return self::REDIRECT_EXCEPTION;
    }
}
```

### 12.3 Expiración de Códigos

- **Default:** 30 minutos (1800 segundos)
- **Configurable:** `factor_email/duration`
- **Verificación:** `timecreated + duration > time()`
- **Regeneración:** Automática si código expirado

### 12.4 Información de Seguridad en Emails

Cada email de verificación incluye:
- IP de origen del intento de login
- Geolocalización (ciudad, país)
- User Agent del navegador
- Enlace para bloquear acceso no autorizado
- Opción de suspender cuenta automáticamente

---

## 13. Capacidades

**Archivo:** `admin/tool/mfa/db/access.php`

```php
$capabilities = [
    'tool/mfa:mfaaccess' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_USER,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],
];
```

Esta capacidad se verifica en `is_ready()` y determina si un usuario está sujeto a MFA.

---

## 14. Configuración General de MFA

**Archivo:** `admin/tool/mfa/settings.php`

| Configuración | Clave | Default | Descripción |
|---------------|-------|---------|-------------|
| Habilitado | `tool_mfa/enabled` | 0 | Activa MFA globalmente |
| Bloqueo | `tool_mfa/lockout` | 10 | Intentos antes de bloqueo |
| Debug | `tool_mfa/debugmode` | 0 | Modo depuración |
| Exclusiones | `tool_mfa/redir_exclusions` | - | URLs excluidas |
| Guía | `tool_mfa/guidance` | 0 | Mostrar página de guía |
| Contenido guía | `tool_mfa/guidancecontent` | - | HTML de guía |

---

## 15. Integración con Sistema de Mensajes

### 15.1 Función email_to_user()

**Archivo:** `lib/moodlelib.php:5616`

El factor email usa esta función para enviar códigos:

```php
email_to_user($USER, $noreplyuser, $subject, $body, $body);
```

### 15.2 Validaciones Previas

**validate_email()** - `lib/weblib.php:387`:
```php
function validate_email($address) {
    require_once("{$CFG->libdir}/phpmailer/moodle_phpmailer.php");
    return moodle_phpmailer::validateAddress($address ?? '')
           && !preg_match('/[<>]/', $address);
}
```

**over_bounce_threshold()** - `lib/moodlelib.php:3061`:
```php
function over_bounce_threshold($user) {
    // Verifica si usuario ha superado umbral de rebotes
    // Default: 10 bounces con ratio >= 0.20
}
```

---

## 16. Resumen Técnico

### Componentes Principales

| Componente | Ubicación | Líneas |
|------------|-----------|--------|
| Manager | `admin/tool/mfa/classes/manager.php` | ~906 |
| Factor plugininfo | `admin/tool/mfa/classes/plugininfo/factor.php` | ~388 |
| Base factor | `admin/tool/mfa/classes/local/factor/object_factor_base.php` | ~500 |
| Email factor | `admin/tool/mfa/factor/email/classes/factor.php` | ~270 |
| Hook callbacks | `admin/tool/mfa/classes/hook_callbacks.php` | ~56 |

### Archivos del Core Afectados

| Archivo | Función | Relación con MFA |
|---------|---------|------------------|
| `lib/setup.php` | Bootstrap | Dispara hook after_config |
| `lib/moodlelib.php` | require_login() | Dispara hook after_require_login |
| `lib/moodlelib.php` | complete_user_login() | Evento user_loggedin |
| `login/index.php` | Login | Llama complete_user_login() |

### Puntos de Control

1. **Hook `after_config`** - Bootstrap temprano
2. **Hook `after_require_login`** - Post require_login()
3. **Página `/admin/tool/mfa/auth.php`** - Autenticación MFA
4. **Método `manager::require_auth()`** - Decisión de redirección
5. **Método `manager::set_pass_state()`** - Marca como autenticado

### Variable de Sesión Clave

```php
$SESSION->tool_mfa_authenticated = true|false
```

Esta variable determina si el usuario ha completado MFA en la sesión actual.

---

## 17. Referencias

### Archivos Principales

- `admin/tool/mfa/classes/manager.php` - Lógica central
- `admin/tool/mfa/classes/plugininfo/factor.php` - Gestión de factores
- `admin/tool/mfa/factor/email/classes/factor.php` - Factor email
- `admin/tool/mfa/auth.php` - Página de autenticación
- `admin/tool/mfa/lib.php` - Funciones hook
- `lib/moodlelib.php` - Integración con login
- `lib/setup.php` - Bootstrap

### Documentación Oficial

- [Moodle Multi-factor authentication](https://docs.moodle.org/en/Multi-factor_authentication)
- [Moodle Developer Documentation](https://moodledev.io/)
