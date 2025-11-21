# Proceso de Login en Moodle - Autenticación Manual

## Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura General](#arquitectura-general)
3. [Flujo de Login](#flujo-de-login)
4. [Plugin de Autenticación Manual](#plugin-de-autenticación-manual)
5. [Funciones Clave de Autenticación](#funciones-clave-de-autenticación)
6. [Sistema de Contraseñas](#sistema-de-contraseñas)
7. [Gestión de Sesiones](#gestión-de-sesiones)
8. [Recuperación de Contraseña](#recuperación-de-contraseña)
9. [Cambio de Contraseña](#cambio-de-contraseña)
10. [Seguridad: Bloqueo de Cuentas](#seguridad-bloqueo-de-cuentas)
11. [Proceso de Logout](#proceso-de-logout)
12. [Configuración del Sistema](#configuración-del-sistema)
13. [Constantes y Códigos de Error](#constantes-y-códigos-de-error)
14. [Diagramas de Flujo](#diagramas-de-flujo)

---

## Introducción

Moodle implementa un sistema de autenticación modular y seguro que permite:

- **Múltiples métodos de autenticación**: Manual, LDAP, OAuth2, SAML, etc.
- **Seguridad robusta**: Hashing de contraseñas con SHA512 + peppers, protección contra fuerza bruta
- **Recuperación de contraseñas**: Sistema basado en tokens temporales
- **Gestión de sesiones**: Cookies seguras, timeout, límite de sesiones concurrentes

Este documento se enfoca en el **método de autenticación manual** (`auth_manual`), que es el método predeterminado donde las credenciales se almacenan y verifican directamente en la base de datos de Moodle.

---

## Arquitectura General

### Archivos Principales

| Archivo | Descripción |
|---------|-------------|
| `/login/index.php` | Página principal de login |
| `/login/logout.php` | Página de logout |
| `/login/lib.php` | Funciones de soporte para login |
| `/login/forgot_password.php` | Recuperación de contraseña |
| `/login/change_password.php` | Cambio de contraseña |
| `/login/unlock_account.php` | Desbloqueo de cuenta |
| `/auth/manual/auth.php` | Plugin de autenticación manual |
| `/lib/moodlelib.php` | Funciones core de autenticación |
| `/lib/authlib.php` | Funciones de bloqueo y seguridad |
| `/lib/sessionlib.php` | Gestión de sesiones y cookies |

### Tablas de Base de Datos

| Tabla | Descripción |
|-------|-------------|
| `user` | Usuarios con campo `password` (hash) |
| `user_password_resets` | Tokens de recuperación de contraseña |
| `user_password_history` | Historial de contraseñas |
| `user_preferences` | Preferencias (intentos fallidos, bloqueos) |
| `sessions` | Sesiones activas |

---

## Flujo de Login

### Diagrama General

```
┌─────────────────────────────────────────────────────────────────┐
│                    FLUJO DE LOGIN COMPLETO                       │
└─────────────────────────────────────────────────────────────────┘

Usuario accede a /login/index.php
          │
          ▼
┌─────────────────────┐
│  Mostrar formulario │ ◄────── GET request
│   de login          │
└─────────┬───────────┘
          │
          ▼ POST (username + password)
┌─────────────────────┐
│ authenticate_user_  │
│ login()             │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐     ┌─────────────────────┐
│ Validar token CSRF  │────►│ Validar reCAPTCHA   │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          ▼                           ▼
┌─────────────────────┐     ┌─────────────────────┐
│ Buscar usuario en   │     │ Verificar estado    │
│ base de datos       │     │ (suspendido, auth)  │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          ▼                           ▼
┌─────────────────────┐     ┌─────────────────────┐
│ login_is_lockedout()│     │ $authplugin->       │
│ Verificar bloqueo   │     │ user_login()        │
└─────────┬───────────┘     └─────────┬───────────┘
          │                           │
          │         ┌─────────────────┘
          │         ▼
          │  ┌─────────────────────┐
          │  │ validate_internal_  │
          │  │ user_password()     │
          │  └─────────┬───────────┘
          │            │
          ▼            ▼
┌─────────────────────────────────────────────────┐
│               ¿Autenticación exitosa?            │
└──────────┬───────────────────────┬──────────────┘
           │ SÍ                    │ NO
           ▼                       ▼
┌─────────────────────┐   ┌─────────────────────┐
│ login_attempt_      │   │ login_attempt_      │
│ valid()             │   │ failed()            │
│ (resetear contador) │   │ (incrementar count) │
└─────────┬───────────┘   └─────────┬───────────┘
          │                         │
          ▼                         ▼
┌─────────────────────┐   ┌─────────────────────┐
│ complete_user_      │   │ Mostrar error       │
│ login()             │   │ "Credenciales       │
└─────────┬───────────┘   │  inválidas"         │
          │               └─────────────────────┘
          ▼
┌─────────────────────┐
│ Crear sesión        │
│ Disparar evento     │
│ Set cookie          │
└─────────┬───────────┘
          │
          ▼
┌─────────────────────┐
│ Redirigir a página  │
│ solicitada/homepage │
└─────────────────────┘
```

### Punto de Entrada: `/login/index.php`

```php
// Ubicación: /login/index.php

// Línea 27-28: Carga de librerías
require('../config.php');
require_once('lib.php');

// Línea 88-93: Ejecutar hooks de plugins de autenticación
$authsequence = get_enabled_auth_plugins();
foreach ($authsequence as $authname) {
    $authplugin = get_auth_plugin($authname);
    $authplugin->loginpage_hook();
}

// Línea 137-158: Procesamiento del formulario
if ($frm && isset($frm->username)) {
    // Normalizar username a minúsculas
    $frm->username = trim(core_text::strtolower($frm->username));

    // Obtener token de login para validación CSRF
    $logintoken = isset($frm->logintoken) ? $frm->logintoken : '';
    $loginrecaptcha = login_captcha_enabled()
        ? ($frm->{'g-recaptcha-response'} ?? '')
        : false;

    // Intentar autenticación
    $user = authenticate_user_login(
        $frm->username,
        $frm->password,
        false,           // No ignorar lockout
        $errorcode,
        $logintoken,
        $loginrecaptcha
    );
}

// Línea 176-231: Completar login si fue exitoso
if ($user) {
    complete_user_login($user);

    // Establecer cookie "remember me" si está habilitado
    if (!empty($CFG->rememberusername)) {
        set_moodle_cookie($USER->username);
    }

    // Redirigir a página solicitada
    redirect(core_login_get_return_url());
}
```

---

## Plugin de Autenticación Manual

### Ubicación y Estructura

```
/auth/manual/
├── auth.php         # Clase auth_plugin_manual
├── settings.php     # Configuración del plugin
├── version.php      # Información de versión
└── lang/
    └── en/
        └── auth_manual.php  # Cadenas de idioma
```

### Clase `auth_plugin_manual`

**Ubicación**: `/auth/manual/auth.php`

```php
class auth_plugin_manual extends auth_plugin_base {

    /**
     * Constructor - inicializa el plugin
     */
    public function __construct() {
        $this->authtype = 'manual';
        $config = get_config(self::COMPONENT_NAME);
        $legacyconfig = get_config(self::LEGACY_COMPONENT_NAME);
        $this->config = (object) array_merge(
            (array) $legacyconfig,
            (array) $config
        );
    }

    /**
     * Verifica las credenciales del usuario
     *
     * @param string $username Nombre de usuario
     * @param string $password Contraseña en texto plano
     * @return bool True si las credenciales son válidas
     */
    public function user_login($username, $password) {
        global $CFG, $DB;

        // Buscar usuario en la base de datos
        $user = $DB->get_record('user', [
            'username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id
        ]);

        if (!$user) {
            return false;
        }

        // Validar contraseña usando función interna
        if (!validate_internal_user_password($user, $password)) {
            return false;
        }

        // Verificar contraseña "changeme" (legacy)
        if ($password === 'changeme') {
            set_user_preference('auth_forcepasswordchange', true, $user->id);
        }

        return true;
    }

    /**
     * Actualiza la contraseña del usuario
     */
    public function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);

        // Guardar timestamp de actualización
        set_user_preference('auth_manual_passwordupdatetime', time(), $user->id);

        return update_internal_user_password($user, $newpassword);
    }

    /**
     * Indica si las contraseñas se almacenan internamente
     */
    public function is_internal() {
        return true;
    }

    /**
     * Indica si el usuario puede cambiar su contraseña
     */
    public function can_change_password() {
        return true;
    }

    /**
     * Indica si se puede resetear la contraseña
     */
    public function can_reset_password() {
        return true;
    }

    /**
     * Verifica expiración de contraseña
     *
     * @return float Días restantes (negativo si expiró)
     */
    public function password_expire($username) {
        $result = 0;

        if (!empty($this->config->expirationtime)) {
            $user = core_user::get_user_by_username($username, 'id,timecreated');
            $lastpasswordupdatetime = get_user_preferences(
                'auth_manual_passwordupdatetime',
                $user->timecreated,
                $user->id
            );

            $expiretime = $lastpasswordupdatetime
                + ($this->config->expirationtime * DAYSECS);
            $now = time();
            $result = ($expiretime - $now) / DAYSECS;
        }

        return $result;
    }
}
```

---

## Funciones Clave de Autenticación

### `authenticate_user_login()`

**Ubicación**: `/lib/moodlelib.php` (línea ~3822)

Esta es la función principal que orquesta todo el proceso de autenticación.

```php
/**
 * Autentica un usuario con username y password
 *
 * @param string $username Nombre de usuario
 * @param string $password Contraseña
 * @param bool $ignorelockout Ignorar bloqueo de cuenta
 * @param int &$failurereason Código de error (por referencia)
 * @param string|false $logintoken Token CSRF
 * @param string|bool $loginrecaptcha Respuesta de reCAPTCHA
 * @return stdClass|false Usuario autenticado o false
 */
function authenticate_user_login(
    $username,
    $password,
    $ignorelockout = false,
    &$failurereason = null,
    $logintoken = false,
    string|bool $loginrecaptcha = false
) {
    global $CFG, $DB;

    // 1. BUSCAR USUARIO
    // -----------------
    // Buscar por username
    $user = get_complete_user_data('username', $username, $CFG->mnet_localhost_id);

    // Si está habilitado, buscar también por email
    if (!$user && !empty($CFG->authloginviaemail)) {
        $user = get_complete_user_data('email', $username);
    }

    // 2. VALIDAR TOKEN CSRF
    // ---------------------
    if (!\core\session\manager::validate_login_token($logintoken)) {
        $failurereason = AUTH_LOGIN_FAILED;
        // Disparar evento de login fallido
        \core\event\user_login_failed::create([
            'other' => ['username' => $username, 'reason' => AUTH_LOGIN_FAILED]
        ])->trigger();
        return false;
    }

    // 3. VALIDAR reCAPTCHA
    // --------------------
    if (login_captcha_enabled() && !validate_login_captcha($loginrecaptcha)) {
        $failurereason = AUTH_LOGIN_FAILED_RECAPTCHA;
        return false;
    }

    // 4. VERIFICAR ESTADO DEL USUARIO
    // -------------------------------
    if ($user) {
        // Usuario suspendido
        if ($user->suspended) {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            \core\event\user_login_failed::create([
                'userid' => $user->id,
                'other' => ['username' => $username, 'reason' => AUTH_LOGIN_SUSPENDED]
            ])->trigger();
            return false;
        }

        // Auth plugin no habilitado
        if (!is_enabled_auth($user->auth)) {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            return false;
        }

        // Usuario con nologin
        if ($user->auth === 'nologin') {
            $failurereason = AUTH_LOGIN_SUSPENDED;
            return false;
        }
    }

    // 5. VERIFICAR BLOQUEO DE CUENTA
    // ------------------------------
    if (!$ignorelockout && $user && $user->id) {
        if (login_is_lockedout($user)) {
            $failurereason = AUTH_LOGIN_LOCKOUT;
            \core\event\user_login_failed::create([
                'userid' => $user->id,
                'other' => ['username' => $username, 'reason' => AUTH_LOGIN_LOCKOUT]
            ])->trigger();
            return false;
        }
    }

    // 6. AUTENTICAR CON PLUGIN
    // ------------------------
    $auths = $user ? [$user->auth] : get_enabled_auth_plugins();

    foreach ($auths as $auth) {
        $authplugin = get_auth_plugin($auth);

        // Intentar login con este plugin
        if (!$authplugin->user_login($username, $password)) {
            continue;
        }

        // Autenticación exitosa
        // ...

        // 7. VALIDAR POLÍTICA DE CONTRASEÑA
        // ----------------------------------
        if (!empty($CFG->passwordpolicycheckonlogin)) {
            $errmsg = '';
            if (!check_password_policy($password, $errmsg, $user)) {
                set_user_preference('auth_forcepasswordchange', 1, $user);
            }
        }

        // 8. ACTUALIZAR HASH SI ES NECESARIO
        // -----------------------------------
        if ($user->id) {
            update_internal_user_password($user, $password);
        }

        // 9. REGISTRAR INTENTO EXITOSO
        // ----------------------------
        login_attempt_valid($user);
        $failurereason = AUTH_LOGIN_OK;

        return $user;
    }

    // 10. LOGIN FALLIDO
    // -----------------
    if ($user) {
        login_attempt_failed($user);
    }

    $failurereason = AUTH_LOGIN_FAILED;
    return false;
}
```

### `complete_user_login()`

**Ubicación**: `/lib/moodlelib.php` (línea ~4104)

Se ejecuta después de una autenticación exitosa para establecer la sesión.

```php
/**
 * Completa el proceso de login después de autenticación exitosa
 *
 * @param stdClass $user Usuario autenticado
 * @param array $extrauserinfo Información adicional para el evento
 * @return stdClass Usuario con sesión iniciada
 */
function complete_user_login($user, array $extrauserinfo = []) {
    global $CFG, $USER, $SESSION;

    // 1. CREAR SESIÓN
    // ---------------
    \core\session\manager::login_user($user);

    // 2. CARGAR PREFERENCIAS
    // ----------------------
    unset($USER->preference);
    check_user_preferences_loaded($USER);

    // 3. ACTUALIZAR TIEMPOS DE LOGIN
    // ------------------------------
    update_user_login_times();

    // 4. INICIALIZAR PREFERENCIAS DE SESIÓN
    // -------------------------------------
    set_login_session_preferences();

    // 5. DISPARAR EVENTO USER_LOGGEDIN
    // --------------------------------
    $event = \core\event\user_loggedin::create([
        'userid' => $USER->id,
        'objectid' => $USER->id,
        'other' => [
            'username' => $USER->username,
            'extrauserinfo' => $extrauserinfo
        ]
    ]);
    $event->trigger();

    // 6. DETECTAR NUEVO IP Y NOTIFICAR
    // --------------------------------
    if (!empty($SESSION->isnewsessioncookie)) {
        // Verificar si es un IP nuevo
        $isnewip = $USER->lastip !== getremoteaddr();

        if ($isnewip && /* condiciones válidas */) {
            // Encolar tarea para enviar notificación
            $task = new \core\task\send_login_notifications();
            $task->set_userid($USER->id);
            \core\task\manager::queue_adhoc_task($task);
        }
    }

    // 7. VERIFICAR FORZAR CAMBIO DE CONTRASEÑA
    // ----------------------------------------
    if (get_user_preferences('auth_forcepasswordchange', false)) {
        $SESSION->forcepasswordchange = true;
        redirect($CFG->wwwroot . '/login/change_password.php');
    }

    // 8. VERIFICAR PERFIL INCOMPLETO
    // ------------------------------
    if (user_not_fully_set_up($USER)) {
        redirect($CFG->wwwroot . '/user/edit.php');
    }

    return $USER;
}
```

### `require_login()`

**Ubicación**: `/lib/moodlelib.php` (línea ~2254)

Función que protege páginas requiriendo autenticación.

```php
/**
 * Requiere que el usuario esté autenticado
 *
 * @param mixed $courseorid Curso o ID de curso (opcional)
 * @param bool $autologinguest Auto-login como invitado
 * @param object $cm Módulo de curso (opcional)
 * @param bool $setwantsurltome Guardar URL actual para redirección
 * @param bool $preventredirect Prevenir redirección automática
 */
function require_login(
    $courseorid = null,
    $autologinguest = true,
    $cm = null,
    $setwantsurltome = true,
    $preventredirect = false
) {
    global $CFG, $SESSION, $USER, $PAGE;

    // 1. VERIFICAR TIMEOUT DE SESIÓN
    // ------------------------------
    if ((!isloggedin() || isguestuser())
        && !empty($SESSION->has_timed_out)) {
        redirect(get_login_url());
    }

    // 2. FORZAR LOGIN SI NO ESTÁ AUTENTICADO
    // --------------------------------------
    if (!isloggedin()) {
        if ($autologinguest && !empty($CFG->autologinguests)) {
            // Auto-login como invitado si está permitido
            $guest = get_complete_user_data('id', $CFG->siteguest);
            complete_user_login($guest);
        } else {
            // Guardar URL actual para después del login
            if ($setwantsurltome) {
                $SESSION->wantsurl = qualified_me();
            }

            if ($preventredirect) {
                throw new require_login_exception('You must be logged in');
            }

            redirect(get_login_url());
        }
    }

    // 3. VERIFICAR FORZAR CAMBIO DE CONTRASEÑA
    // ----------------------------------------
    if (get_user_preferences('auth_forcepasswordchange')) {
        redirect($CFG->wwwroot . '/login/change_password.php');
    }

    // 4. VERIFICAR PERFIL COMPLETO
    // ----------------------------
    if (user_not_fully_set_up($USER, true)) {
        redirect($CFG->wwwroot . '/user/edit.php');
    }

    // 5. VERIFICAR ACCESO AL CURSO (si aplica)
    // ----------------------------------------
    if ($courseorid) {
        // Verificar inscripción, capacidades, etc.
        // ...
    }
}
```

---

## Sistema de Contraseñas

### Verificación de Contraseñas

**Ubicación**: `/lib/moodlelib.php` (línea ~4291)

```php
/**
 * Valida la contraseña de un usuario
 *
 * @param stdClass $user Usuario con campo password (hash)
 * @param string $password Contraseña en texto plano
 * @return bool True si la contraseña es correcta
 */
function validate_internal_user_password(
    stdClass $user,
    #[\SensitiveParameter] string $password
): bool {
    global $CFG;

    // 1. VERIFICAR LONGITUD MÁXIMA
    // ----------------------------
    // Contraseñas muy largas pueden ser un ataque DoS
    if (exceeds_password_length($password)) {
        return false;
    }

    // 2. VERIFICAR PASSWORD NO CACHEADO
    // ---------------------------------
    if ($user->password === AUTH_PASSWORD_NOT_CACHED) {
        return false;
    }

    // 3. OBTENER PEPPERS
    // ------------------
    // Los peppers son secretos adicionales que se añaden a la contraseña
    $peppers = get_password_peppers();
    $islegacy = password_is_legacy_hash($user->password);

    // 4. VERIFICAR HASH LEGACY (bcrypt antiguo)
    // -----------------------------------------
    if ($islegacy && password_verify($password, $user->password)) {
        // Actualizar a hash moderno
        update_internal_user_password($user, $password);
        return true;
    }

    // 5. VERIFICAR CON PEPPERS
    // ------------------------
    $latestpepper = reset($peppers);
    $peppers = [-1 => ''] + $peppers; // Incluir sin pepper

    foreach ($peppers as $pepper) {
        $pepperedpassword = $password . $pepper;

        if (password_verify($pepperedpassword, $user->password)) {
            // Si no usa el pepper más reciente, actualizar
            if ($pepper !== $latestpepper) {
                update_internal_user_password($user, $password);
            }
            return true;
        }
    }

    return false;
}
```

### Generación de Hash

**Ubicación**: `/lib/moodlelib.php` (línea ~4345)

```php
/**
 * Genera hash de contraseña usando SHA512
 *
 * @param string $password Contraseña en texto plano
 * @param bool $fasthash Usar menos rounds (para pruebas)
 * @return string Hash generado
 */
function hash_internal_user_password(
    #[\SensitiveParameter] string $password,
    $fasthash = false
): string {

    // Rounds: 5000 (fast) o 10000 (default)
    $rounds = $fasthash ? 5000 : 10000;

    // Generar salt criptográfico de 16 bytes
    $randombytes = random_bytes(16);
    $salt = substr(strtr(base64_encode($randombytes), '+', '.'), 0, 16);

    // Generar hash usando crypt() con SHA512
    $generatedhash = crypt($password, implode('$', [
        '',              // Indica algoritmo
        '6',             // 6 = SHA512
        "rounds={$rounds}",
        $salt,
        '',
    ]));

    return $generatedhash;
}
```

**Formato del hash resultante**:
```
$6$rounds=10000$<16 caracteres salt>$<hash resultado>
```

Donde:
- `$6$` indica algoritmo SHA512
- `rounds=10000` indica iteraciones
- Salt de 16 caracteres aleatorios
- Hash resultante

### Actualización de Contraseñas

**Ubicación**: `/lib/moodlelib.php` (línea ~4399)

```php
/**
 * Actualiza la contraseña de un usuario en la base de datos
 *
 * @param stdClass $user Usuario
 * @param string|null $password Nueva contraseña (null = invalidar)
 * @param bool $fasthash Usar hash rápido
 * @return bool Éxito
 */
function update_internal_user_password(
    stdClass $user,
    #[\SensitiveParameter] ?string $password,
    bool $fasthash = false
): bool {
    global $CFG, $DB;

    // 1. AGREGAR PEPPER MÁS RECIENTE
    // ------------------------------
    $peppers = get_password_peppers();
    if (!empty($peppers) && $password !== null) {
        $password = $password . reset($peppers);
    }

    // 2. GENERAR HASH O INVALIDAR
    // ---------------------------
    $authplugin = get_auth_plugin($user->auth);

    if ($authplugin->prevent_local_passwords()) {
        $hashedpassword = AUTH_PASSWORD_NOT_CACHED;
    } else {
        $hashedpassword = hash_internal_user_password($password, $fasthash);
    }

    // 3. ACTUALIZAR EN BASE DE DATOS
    // ------------------------------
    $passwordchanged = ($user->password !== $hashedpassword);

    if ($passwordchanged) {
        $DB->set_field('user', 'password', $hashedpassword, ['id' => $user->id]);

        // Disparar evento
        \core\event\user_password_updated::create_from_user($user)->trigger();

        // Eliminar tokens de Web Service si está configurado
        if (!empty($CFG->passwordchangetokendeletion)) {
            webservice::delete_user_ws_tokens($user->id);
        }
    }

    return true;
}
```

---

## Gestión de Sesiones

### Cookies de Sesión

**Ubicación**: `/lib/sessionlib.php`

#### Establecer Cookie "Remember Me"

```php
/**
 * Guarda el username en una cookie para pre-llenar el formulario
 *
 * @param string $username Nombre de usuario
 */
function set_moodle_cookie($username) {
    global $CFG;

    if (NO_MOODLE_COOKIES) {
        return;
    }

    // No guardar cookie si rememberusername está deshabilitado
    if (empty($CFG->rememberusername)) {
        $username = '';
    }

    // No guardar cookie para invitado
    if ($username === 'guest') {
        return;
    }

    $cookiename = 'MOODLEID1_' . $CFG->sessioncookie;
    $cookiesecure = is_moodle_cookie_secure();

    // Eliminar cookie anterior
    setcookie(
        $cookiename,
        '',
        time() - HOURSECS,
        $CFG->sessioncookiepath,
        $CFG->sessioncookiedomain,
        $cookiesecure,
        $CFG->cookiehttponly
    );

    // Establecer nueva cookie (60 días de validez)
    setcookie(
        $cookiename,
        \core\encryption::encrypt($username),  // Username ENCRIPTADO
        time() + (DAYSECS * 60),
        $CFG->sessioncookiepath,
        $CFG->sessioncookiedomain,
        $cookiesecure,
        $CFG->cookiehttponly
    );
}
```

#### Obtener Cookie

```php
/**
 * Obtiene el username guardado en la cookie
 *
 * @return string Username o cadena vacía
 */
function get_moodle_cookie() {
    global $CFG;

    if (NO_MOODLE_COOKIES || empty($CFG->rememberusername)) {
        return '';
    }

    $cookiename = 'MOODLEID1_' . $CFG->sessioncookie;

    try {
        $username = \core\encryption::decrypt($_COOKIE[$cookiename] ?? '');

        if ($username === 'guest' || $username === 'nobody') {
            return '';
        }

        return $username;
    } catch (\moodle_exception $ex) {
        return '';
    }
}
```

**Características de seguridad de la cookie**:
- Username **encriptado** (no en texto plano)
- Flag `httpOnly` para prevenir acceso desde JavaScript
- Flag `secure` en conexiones HTTPS
- Validez de 60 días
- Se elimina la cookie anterior antes de establecer una nueva

---

## Recuperación de Contraseña

### Flujo Completo

```
┌─────────────────────────────────────────────────────────────────┐
│              FLUJO DE RECUPERACIÓN DE CONTRASEÑA                 │
└─────────────────────────────────────────────────────────────────┘

Usuario accede a /login/forgot_password.php
          │
          ▼
┌─────────────────────┐
│ ¿Tiene token en URL │
│ o sesión?           │
└─────────┬───────────┘
          │
     NO   │   SÍ
     ┌────┴────┐
     ▼         ▼
┌──────────┐  ┌──────────────────┐
│ Mostrar  │  │ Guardar token en │
│ formulario│ │ sesión y redirigir│
│(username │  └────────┬─────────┘
│ o email) │           │
└────┬─────┘           ▼
     │          ┌──────────────────┐
     ▼          │ core_login_      │
┌──────────┐    │ process_password_│
│ Enviar   │    │ set($token)      │
│ datos    │    └────────┬─────────┘
└────┬─────┘             │
     │                   ▼
     ▼          ┌──────────────────┐
┌──────────────────┐    │ Validar token    │
│ core_login_      │    │ (existe, no      │
│ process_password_│    │ expirado)        │
│ reset()          │    └────────┬─────────┘
└────────┬─────────┘             │
         │                       ▼
         ▼              ┌──────────────────┐
┌──────────────────┐    │ Mostrar form     │
│ Buscar usuario   │    │ nueva contraseña │
│ (username/email) │    └────────┬─────────┘
└────────┬─────────┘             │
         │                       ▼
         ▼              ┌──────────────────┐
┌──────────────────┐    │ Validar nueva    │
│ Generar token    │    │ contraseña       │
│ aleatorio (32    │    │ (política)       │
│ caracteres)      │    └────────┬─────────┘
└────────┬─────────┘             │
         │                       ▼
         ▼              ┌──────────────────┐
┌──────────────────┐    │ Actualizar       │
│ Guardar en       │    │ contraseña       │
│ user_password_   │    │ en BD            │
│ resets           │    └────────┬─────────┘
└────────┬─────────┘             │
         │                       ▼
         ▼              ┌──────────────────┐
┌──────────────────┐    │ Eliminar token   │
│ Enviar email     │    │ de reset         │
│ con link:        │    └────────┬─────────┘
│ ?token=XXX       │             │
└────────┬─────────┘             ▼
         │              ┌──────────────────┐
         ▼              │ complete_user_   │
┌──────────────────┐    │ login()          │
│ Mostrar mensaje  │    └────────┬─────────┘
│ "Email enviado"  │             │
└──────────────────┘             ▼
                        ┌──────────────────┐
                        │ Redirigir a      │
                        │ homepage         │
                        └──────────────────┘
```

### Punto de Entrada

**Ubicación**: `/login/forgot_password.php`

```php
<?php
require('../config.php');
require_once($CFG->libdir . '/authlib.php');
require_once('lib.php');

$PAGE->set_url('/login/forgot_password.php');
$PAGE->set_context(context_system::instance());

// Obtener token de URL o sesión
$token = optional_param('token', false, PARAM_ALPHANUM);

// Si hay token en sesión, usarlo
if (!empty($SESSION->password_reset_token)) {
    $token = $SESSION->password_reset_token;
    unset($SESSION->password_reset_token);
    $tokeninsession = true;
}

if (empty($token)) {
    // Nueva solicitud de recuperación
    core_login_process_password_reset_request();
} else {
    // Procesar token existente
    if (!$tokeninsession && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // Guardar en sesión y redirigir (evita exponer token en URL)
        $SESSION->password_reset_token = $token;
        redirect($CFG->wwwroot . '/login/forgot_password.php');
    } else {
        core_login_process_password_set($token);
    }
}
```

### Solicitud de Recuperación

**Ubicación**: `/login/lib.php` (línea ~84)

```php
/**
 * Procesa la solicitud de recuperación de contraseña
 *
 * @param string $username Username proporcionado
 * @param string $email Email proporcionado
 * @return array [status, notice, url]
 */
function core_login_process_password_reset($username, $email) {
    global $CFG, $DB;

    $user = false;
    $pwresetstatus = PWRESET_STATUS_NOEMAILSENT;

    // 1. BUSCAR USUARIO POR USERNAME
    // ------------------------------
    if (!empty($username)) {
        $username = core_text::strtolower($username);
        $user = $DB->get_record('user', [
            'username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id,
            'deleted' => 0,
            'suspended' => 0
        ]);
    }

    // 2. O BUSCAR POR EMAIL
    // ---------------------
    if (!$user && !empty($email)) {
        // Búsqueda case-insensitive
        $sql = "SELECT * FROM {user}
                WHERE " . $DB->sql_equal('email', ':email1', false, true) . "
                AND mnethostid = :mnethostid
                AND deleted = 0
                AND suspended = 0";

        $user = $DB->get_record_sql($sql, [
            'email1' => $email,
            'mnethostid' => $CFG->mnet_localhost_id
        ], IGNORE_MULTIPLE);
    }

    // 3. PROCESAR SI SE ENCONTRÓ USUARIO
    // ----------------------------------
    if ($user && !empty($user->confirmed)) {
        $userauth = get_auth_plugin($user->auth);
        $systemcontext = context_system::instance();

        // Verificar si puede resetear contraseña
        if (!$userauth->can_reset_password()
            || !is_enabled_auth($user->auth)
            || !has_capability('moodle/user:changeownpassword',
                               $systemcontext, $user->id)) {

            // Enviar email informativo (no puede resetear)
            if (send_password_change_info($user)) {
                $pwresetstatus = PWRESET_STATUS_OTHEREMAILSENT;
            }

        } else {
            // Verificar si ya hay un reset en progreso
            $resetinprogress = $DB->get_record('user_password_resets',
                ['userid' => $user->id]);

            if (empty($resetinprogress)) {
                // Nueva solicitud
                $resetrecord = core_login_generate_password_reset($user);
                $sendemail = true;

            } else if ($resetinprogress->timerequested < (time() - $CFG->pwresettime)) {
                // Solicitud expirada, crear nueva
                $DB->delete_records('user_password_resets',
                    ['id' => $resetinprogress->id]);
                $resetrecord = core_login_generate_password_reset($user);
                $sendemail = true;

            } else if (empty($resetinprogress->timererequested)) {
                // Primera re-solicitud del mismo reset
                $resetinprogress->timererequested = time();
                $DB->update_record('user_password_resets', $resetinprogress);
                $resetrecord = $resetinprogress;
                $sendemail = true;

            } else {
                // Ya se envió 2 veces, no enviar más
                $pwresetstatus = PWRESET_STATUS_ALREADYSENT;
                $sendemail = false;
            }

            // Enviar email si corresponde
            if ($sendemail) {
                $sendresult = send_password_change_confirmation_email(
                    $user,
                    $resetrecord
                );
                if ($sendresult) {
                    $pwresetstatus = PWRESET_STATUS_TOKENSENT;
                }
            }
        }
    }

    // 4. PREPARAR RESPUESTA
    // ---------------------
    // IMPORTANTE: No revelar si el usuario existe
    if (empty($CFG->protectusernames)) {
        // Mostrar mensaje específico según resultado
        $notice = get_string('emailpassword' . $pwresetstatus, 'moodle');
    } else {
        // Mensaje genérico (no revela información)
        $notice = get_string('emailpasswordconfirmsent', 'moodle');
    }

    return [$pwresetstatus, $notice, get_login_url()];
}
```

### Generación de Token

**Ubicación**: `/login/lib.php` (línea ~321)

```php
/**
 * Genera un registro de reset de contraseña con token único
 *
 * @param stdClass $user Usuario
 * @return stdClass Registro de reset
 */
function core_login_generate_password_reset($user) {
    global $DB;

    $resetrecord = new stdClass();
    $resetrecord->timerequested = time();
    $resetrecord->userid = $user->id;
    $resetrecord->token = random_string(32);  // Token aleatorio de 32 caracteres
    $resetrecord->id = $DB->insert_record('user_password_resets', $resetrecord);

    return $resetrecord;
}
```

**Estructura de la tabla `user_password_resets`**:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | int | Clave primaria |
| `userid` | int | FK a user.id |
| `token` | varchar(32) | Token único y aleatorio |
| `timerequested` | int | Timestamp de solicitud |
| `timererequested` | int | Timestamp de re-solicitud (nullable) |

### Envío de Email

**Ubicación**: `/lib/moodlelib.php` (línea ~6149)

```php
/**
 * Envía email de confirmación de reset de contraseña
 *
 * @param stdClass $user Usuario
 * @param stdClass $resetrecord Registro de reset con token
 * @return bool Éxito
 */
function send_password_change_confirmation_email($user, $resetrecord) {
    global $CFG;

    $site = get_site();
    $supportuser = core_user::get_support_user();

    // Tiempo de validez en minutos
    $pwresetmins = isset($CFG->pwresettime)
        ? floor($CFG->pwresettime / MINSECS)
        : 30;

    // Datos para el email
    $data = new stdClass();
    $data->username = $user->username;
    $data->sitename = format_string($site->fullname);
    $data->link = $CFG->wwwroot . '/login/forgot_password.php?token='
                  . $resetrecord->token;
    $data->admin = generate_email_signoff();
    $data->resetminutes = $pwresetmins;

    $message = get_string('emailresetconfirmation', '', $data);
    $subject = get_string('emailresetconfirmationsubject', '',
        format_string($site->fullname));

    return email_to_user($user, $supportuser, $subject, $message);
}
```

### Procesamiento de Nueva Contraseña

**Ubicación**: `/login/lib.php` (línea ~224)

```php
/**
 * Procesa el establecimiento de nueva contraseña
 *
 * @param string $token Token de reset
 */
function core_login_process_password_set($token) {
    global $DB, $CFG, $OUTPUT, $SESSION;

    // Tiempo de validez del token (default: 30 minutos)
    $pwresettime = isset($CFG->pwresettime) ? $CFG->pwresettime : 1800;

    // 1. OBTENER USUARIO CON TOKEN
    // ----------------------------
    $sql = "SELECT u.*, upr.token, upr.timerequested, upr.id as tokenid
            FROM {user} u
            JOIN {user_password_resets} upr ON upr.userid = u.id
            WHERE upr.token = ?";
    $user = $DB->get_record_sql($sql, [$token]);

    // 2. VALIDAR TOKEN EXISTE
    // -----------------------
    if (empty($user)) {
        echo $OUTPUT->header();
        notice(get_string('noresetrecord'), get_login_url());
        die;
    }

    // 3. VALIDAR TOKEN NO EXPIRADO
    // ----------------------------
    if ($user->timerequested < (time() - $pwresettime)) {
        $pwresetmins = floor($pwresettime / MINSECS);
        echo $OUTPUT->header();
        notice(
            get_string('resetrecordexpired', '', $pwresetmins),
            get_login_url()
        );
        die;
    }

    // 4. VALIDAR AUTH VÁLIDO
    // ----------------------
    if ($user->auth === 'nologin' || !is_enabled_auth($user->auth)) {
        throw new \moodle_exception('forgotteninvalidurl');
    }

    // 5. NO PERMITIR RESET A GUEST
    // ----------------------------
    if (isguestuser($user)) {
        throw new \moodle_exception('cannotresetguestpwd');
    }

    // 6. MOSTRAR FORMULARIO
    // ---------------------
    $mform = new login_set_password_form(null, $user);
    $data = $mform->get_data();

    if (empty($data)) {
        // Mostrar formulario
        $setdata = new stdClass();
        $setdata->username = $user->username;
        $setdata->token = $user->token;
        $mform->set_data($setdata);

        echo $OUTPUT->header();
        echo $OUTPUT->box(get_string('setpasswordinstructions'));
        $mform->display();
        echo $OUTPUT->footer();
        return;
    }

    // 7. PROCESAR NUEVA CONTRASEÑA
    // ----------------------------

    // Eliminar registro de reset
    $DB->delete_records('user_password_resets', ['id' => $user->tokenid]);

    // Actualizar contraseña
    $userauth = get_auth_plugin($user->auth);
    if (!$userauth->user_update_password($user, $data->password)) {
        throw new \moodle_exception('errorpasswordupdate', 'auth');
    }

    // Registrar en historial de contraseñas
    user_add_password_history($user->id, $data->password);

    // Destruir otras sesiones si se requiere
    if (!empty($CFG->passwordchangelogout)
        || !empty($data->logoutothersessions)) {
        \core\session\manager::destroy_user_sessions($user->id, session_id());
    }

    // Desbloquear cuenta si estaba bloqueada
    login_unlock_account($user);

    // Limpiar preferencias de forzar cambio
    unset_user_preference('auth_forcepasswordchange', $user);
    unset_user_preference('create_password', $user);

    // Completar login
    complete_user_login($user);

    // Aplicar límite de sesiones concurrentes
    \core\session\manager::apply_concurrent_login_limit($user->id, session_id());

    // Obtener URL de retorno
    $urltogo = core_login_get_return_url();
    unset($SESSION->wantsurl);

    // Redirigir con mensaje de éxito
    redirect($urltogo, get_string('passwordset'), 1);
}
```

---

## Cambio de Contraseña

### Para Usuario Autenticado

**Ubicación**: `/login/change_password.php`

```php
<?php
require('../config.php');
require_once($CFG->libdir . '/authlib.php');

$PAGE->set_url('/login/change_password.php');

// 1. VERIFICAR USUARIO AUTENTICADO
// --------------------------------
if (!isloggedin() || isguestuser()) {
    if (empty($SESSION->wantsurl)) {
        $SESSION->wantsurl = $CFG->wwwroot . '/login/change_password.php';
    }
    redirect(get_login_url());
}

// 2. VERIFICAR CAPACIDADES
// ------------------------
$systemcontext = context_system::instance();
require_capability('moodle/user:changeownpassword', $systemcontext);

// 3. VERIFICAR QUE EL AUTH PERMITE CAMBIO
// ---------------------------------------
$userauth = get_auth_plugin($USER->auth);

if (!$userauth->can_change_password()) {
    throw new \moodle_exception('nopasswordchange', 'auth');
}

// 4. CREAR Y PROCESAR FORMULARIO
// ------------------------------
$mform = new login_change_password_form();

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/user/profile.php');

} else if ($data = $mform->get_data()) {

    // 5. ACTUALIZAR CONTRASEÑA
    // ------------------------
    if (!$userauth->user_update_password($USER, $data->newpassword1)) {
        throw new \moodle_exception('errorpasswordupdate', 'auth');
    }

    // 6. REGISTRAR EN HISTORIAL
    // -------------------------
    user_add_password_history($USER->id, $data->newpassword1);

    // 7. DESTRUIR OTRAS SESIONES (opcional)
    // -------------------------------------
    if (!empty($CFG->passwordchangelogout)
        || !empty($data->logoutothersessions)) {
        \core\session\manager::destroy_user_sessions($USER->id, session_id());
    }

    // 8. DESBLOQUEAR CUENTA
    // ---------------------
    login_unlock_account($USER);

    // 9. LIMPIAR PREFERENCIAS
    // -----------------------
    unset_user_preference('auth_forcepasswordchange', $USER);
    unset_user_preference('create_password', $USER);

    // 10. REDIRIGIR CON ÉXITO
    // -----------------------
    redirect($CFG->wwwroot . '/user/profile.php',
        get_string('passwordchanged'), 1);
}

// Mostrar formulario
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('changepassword'));
$mform->display();
echo $OUTPUT->footer();
```

---

## Seguridad: Bloqueo de Cuentas

### Verificar si Cuenta está Bloqueada

**Ubicación**: `/lib/authlib.php` (línea ~903)

```php
/**
 * Verifica si una cuenta está bloqueada por intentos fallidos
 *
 * @param stdClass $user Usuario
 * @return bool True si está bloqueada
 */
function login_is_lockedout($user) {
    global $CFG;

    // No aplicar bloqueo a usuarios de otros hosts MNet
    if ($user->mnethostid != $CFG->mnet_localhost_id) {
        return false;
    }

    // No bloquear invitados
    if (isguestuser($user)) {
        return false;
    }

    // Si el threshold está en 0, el bloqueo está deshabilitado
    if (empty($CFG->lockoutthreshold)) {
        return false;
    }

    // Verificar si usuario puede ignorar bloqueo
    if (get_user_preferences('login_lockout_ignored', 0, $user)) {
        return false;
    }

    // Verificar preferencia de bloqueo
    $locked = get_user_preferences('login_lockout', 0, $user);

    if (!$locked) {
        return false;
    }

    // Si no hay duración definida, bloqueo permanente
    if (empty($CFG->lockoutduration)) {
        return true;
    }

    // Verificar si ha expirado el bloqueo
    if (time() - $locked < $CFG->lockoutduration) {
        return true;  // Aún bloqueado
    }

    // Bloqueo expirado, desbloquear automáticamente
    login_unlock_account($user);
    return false;
}
```

### Registrar Intento Fallido

**Ubicación**: `/lib/authlib.php` (línea ~967)

```php
/**
 * Registra un intento de login fallido
 *
 * @param stdClass $user Usuario
 */
function login_attempt_failed($user) {
    global $CFG;

    // Recargar preferencias
    unset($user->preference);

    // Obtener lock para evitar race conditions
    $resource = 'user:' . $user->id;
    $lockfactory = \core\lock\lock_config::get_lock_factory(
        'core_failed_login_count_lock'
    );

    if ($lock = $lockfactory->get_lock($resource, 10)) {
        try {
            // Obtener contadores actuales
            $count = get_user_preferences('login_failed_count', 0, $user);
            $last = get_user_preferences('login_failed_last', 0, $user);
            $sincesuccess = get_user_preferences(
                'login_failed_count_since_success', $count, $user
            );

            // Incrementar contador desde último éxito
            $sincesuccess++;
            set_user_preference('login_failed_count_since_success',
                $sincesuccess, $user);

            // Si el bloqueo está deshabilitado, solo registrar
            if (empty($CFG->lockoutthreshold)) {
                login_unlock_account($user);
                $lock->release();
                return;
            }

            // Resetear contador si pasó la ventana de tiempo
            if (!empty($CFG->lockoutwindow)
                && time() - $last > $CFG->lockoutwindow) {
                $count = 0;
            }

            // Incrementar contador
            $count++;

            // Guardar preferencias
            set_user_preference('login_failed_count', $count, $user);
            set_user_preference('login_failed_last', time(), $user);

            // Bloquear si alcanzó el threshold
            if ($count >= $CFG->lockoutthreshold) {
                login_lock_account($user);
            }

            $lock->release();

        } catch (Exception $e) {
            $lock->release();
            throw $e;
        }
    } else {
        throw new moodle_exception('locktimeout');
    }
}
```

### Bloquear Cuenta

**Ubicación**: `/lib/authlib.php` (línea ~1032)

```php
/**
 * Bloquea una cuenta de usuario
 *
 * @param stdClass $user Usuario
 */
function login_lock_account($user) {
    global $CFG;

    // Verificar si ya estaba bloqueada
    $alreadylockedout = get_user_preferences('login_lockout', 0, $user);

    // Establecer bloqueo
    set_user_preference('login_lockout', time(), $user);

    // Si es la primera vez que se bloquea
    if ($alreadylockedout == 0) {
        // Generar secreto para desbloqueo
        $secret = random_string(15);
        set_user_preference('login_lockout_secret', $secret, $user);

        // Preparar datos para email
        $site = get_site();
        $supportuser = core_user::get_support_user();

        $data = new stdClass();
        $data->firstname = $user->firstname;
        $data->username = $user->username;
        $data->sitename = format_string($site->fullname);
        $data->link = $CFG->wwwroot . '/login/unlock_account.php?u='
                      . $user->id . '&s=' . $secret;

        // Enviar email de desbloqueo
        $message = get_string('accountlockednotification', '', $data);
        $subject = get_string('accountlocked', 'admin');

        email_to_user($user, $supportuser, $subject, $message);
    }
}
```

### Desbloquear Cuenta

**Ubicación**: `/lib/authlib.php` (línea ~1070)

```php
/**
 * Desbloquea una cuenta de usuario
 *
 * @param stdClass $user Usuario
 * @param bool $notify Mostrar mensaje de notificación
 */
function login_unlock_account($user, bool $notify = false) {
    global $SESSION;

    // Limpiar todas las preferencias de bloqueo
    unset_user_preference('login_lockout', $user);
    unset_user_preference('login_failed_count', $user);
    unset_user_preference('login_failed_last', $user);
    unset_user_preference('login_lockout_secret', $user);

    // Mostrar mensaje si se requiere
    if ($notify) {
        $SESSION->logininfomsg = get_string('accountunlocked', 'admin');
    }
}
```

### Página de Desbloqueo

**Ubicación**: `/login/unlock_account.php`

```php
<?php
require('../config.php');

$userid = required_param('u', PARAM_INT);
$secret = required_param('s', PARAM_RAW);

// Obtener usuario
$user = $DB->get_record('user', [
    'id' => $userid,
    'deleted' => 0,
    'suspended' => 0
]);

if (!$user) {
    throw new \moodle_exception('lockouterrorunlock', 'admin', get_login_url());
}

// Validar secreto
$usersecret = get_user_preferences('login_lockout_secret', false, $user);

if ($secret === $usersecret) {
    // Desbloquear con notificación
    login_unlock_account($user, true);

    // Redirigir
    if ($USER->id == $user->id) {
        redirect($CFG->wwwroot . '/');
    } else {
        redirect(get_login_url());
    }
}

throw new \moodle_exception('lockouterrorunlock', 'admin', get_login_url());
```

---

## Proceso de Logout

**Ubicación**: `/lib/moodlelib.php` (línea ~2691)

```php
/**
 * Cierra la sesión del usuario actual
 */
function require_logout() {
    global $USER, $DB;

    // Si no hay sesión activa
    if (!isloggedin()) {
        \core\session\manager::terminate_current();
        return;
    }

    // 1. EJECUTAR PRE-LOGOUT HOOKS
    // ----------------------------
    $authsequence = get_enabled_auth_plugins();
    $authplugins = [];

    foreach ($authsequence as $authname) {
        $authplugins[$authname] = get_auth_plugin($authname);
        $authplugins[$authname]->prelogout_hook();
    }

    // 2. DISPARAR EVENTO USER_LOGGEDOUT
    // ---------------------------------
    $event = \core\event\user_loggedout::create([
        'userid' => $USER->id,
        'objectid' => $USER->id,
        'other' => ['sessionid' => session_id()]
    ]);
    $event->trigger();

    // Guardar copia del usuario para hooks
    $user = clone($USER);

    // 3. TERMINAR SESIÓN
    // ------------------
    \core\session\manager::terminate_current();

    // 4. EJECUTAR POST-LOGOUT HOOKS
    // -----------------------------
    foreach ($authplugins as $authplugin) {
        $authplugin->postlogout_hook($user);
    }
}
```

**Página de logout**: `/login/logout.php`

```php
<?php
require('../config.php');

$sesskey = optional_param('sesskey', '', PARAM_RAW);

// Validar sesskey para prevenir CSRF
if (!empty($sesskey) && confirm_sesskey($sesskey)) {
    require_logout();
}

redirect($CFG->wwwroot);
```

---

## Configuración del Sistema

### Variables de Configuración

| Variable | Descripción | Default |
|----------|-------------|---------|
| **Login General** |
| `$CFG->rememberusername` | Guardar username en cookie | true |
| `$CFG->authloginviaemail` | Permitir login con email | false |
| `$CFG->nolastloggedin` | No guardar último usuario | false |
| `$CFG->protectusernames` | No revelar existencia de usuarios | false |
| **Recuperación de Contraseña** |
| `$CFG->pwresettime` | Validez del token (segundos) | 1800 (30 min) |
| **Bloqueo de Cuenta** |
| `$CFG->lockoutthreshold` | Intentos para bloquear | 0 (deshabilitado) |
| `$CFG->lockoutduration` | Duración del bloqueo (segundos) | 0 (permanente) |
| `$CFG->lockoutwindow` | Ventana para contar intentos | 0 (sin límite) |
| **Cambio de Contraseña** |
| `$CFG->passwordchangelogout` | Cerrar otras sesiones al cambiar | false |
| `$CFG->passwordpolicycheckonlogin` | Validar política al login | false |
| **Cookies** |
| `$CFG->sessioncookie` | Nombre base de cookies | '' |
| `$CFG->sessioncookiepath` | Path de cookies | / |
| `$CFG->sessioncookiedomain` | Dominio de cookies | '' |
| `$CFG->cookiehttponly` | Flag httpOnly | true |
| **reCAPTCHA** |
| `$CFG->enableloginrecaptcha` | Habilitar en login | false |
| `$CFG->recaptchapublickey` | Clave pública | '' |
| `$CFG->recaptchaprivatekey` | Clave privada | '' |

### Configurar en `/config.php`

```php
<?php
// Ejemplo de configuración de seguridad

// Bloqueo después de 5 intentos fallidos
$CFG->lockoutthreshold = 5;

// Bloqueo de 30 minutos
$CFG->lockoutduration = 1800;

// Ventana de 15 minutos para contar intentos
$CFG->lockoutwindow = 900;

// Token de reset válido por 1 hora
$CFG->pwresettime = 3600;

// Cerrar otras sesiones al cambiar contraseña
$CFG->passwordchangelogout = true;

// Validar política de contraseña al login
$CFG->passwordpolicycheckonlogin = true;

// No revelar si un usuario existe
$CFG->protectusernames = true;
```

---

## Constantes y Códigos de Error

### Códigos de Autenticación

```php
// Resultados de authenticate_user_login()
define('AUTH_LOGIN_OK', 0);              // Login exitoso
define('AUTH_LOGIN_NOUSER', 1);          // Usuario no existe
define('AUTH_LOGIN_SUSPENDED', 2);       // Usuario suspendido
define('AUTH_LOGIN_FAILED', 3);          // Credenciales incorrectas
define('AUTH_LOGIN_LOCKOUT', 4);         // Cuenta bloqueada
define('AUTH_LOGIN_UNAUTHORISED', 5);    // No autorizado
define('AUTH_LOGIN_FAILED_RECAPTCHA', 6);// reCAPTCHA fallido
```

### Códigos de Reset de Contraseña

```php
// Resultados de core_login_process_password_reset()
define('PWRESET_STATUS_NOEMAILSENT', 1);   // No se envió email
define('PWRESET_STATUS_TOKENSENT', 2);     // Token enviado
define('PWRESET_STATUS_OTHEREMAILSENT', 3);// Email informativo enviado
define('PWRESET_STATUS_ALREADYSENT', 4);   // Ya se envió previamente
```

### Constante de Password

```php
// Password no almacenado localmente (auth externo)
define('AUTH_PASSWORD_NOT_CACHED', 'not cached');
```

---

## Diagramas de Flujo

### Login Exitoso

```
┌──────────────────────────────────────────────────────────────────┐
│                     LOGIN EXITOSO - RESUMEN                       │
└──────────────────────────────────────────────────────────────────┘

1. Usuario → /login/index.php (GET)
   └── Mostrar formulario

2. Usuario → Enviar credenciales (POST)
   └── authenticate_user_login()
       ├── Validar token CSRF ✓
       ├── Validar reCAPTCHA ✓
       ├── Buscar usuario ✓
       ├── Verificar no suspendido ✓
       ├── Verificar no bloqueado ✓
       └── $authplugin->user_login() ✓
           └── validate_internal_user_password() ✓

3. complete_user_login()
   ├── Crear sesión
   ├── Cargar preferencias
   ├── Disparar evento user_loggedin
   └── Verificar forzar cambio password

4. set_moodle_cookie() (si está habilitado)

5. Redirigir a página solicitada
```

### Login Fallido

```
┌──────────────────────────────────────────────────────────────────┐
│                     LOGIN FALLIDO - RESUMEN                       │
└──────────────────────────────────────────────────────────────────┘

1. Usuario → Enviar credenciales incorrectas

2. authenticate_user_login()
   └── $authplugin->user_login() ✗
       └── validate_internal_user_password() ✗

3. login_attempt_failed($user)
   ├── Incrementar login_failed_count
   ├── Registrar login_failed_last
   └── Si count >= threshold:
       └── login_lock_account()
           ├── Establecer login_lockout
           └── Enviar email de desbloqueo

4. Disparar evento user_login_failed

5. Mostrar mensaje "Credenciales inválidas"
```

### Recuperación de Contraseña

```
┌──────────────────────────────────────────────────────────────────┐
│              RECUPERACIÓN DE CONTRASEÑA - RESUMEN                 │
└──────────────────────────────────────────────────────────────────┘

FASE 1: Solicitud
1. Usuario → /login/forgot_password.php
2. Enviar username o email
3. core_login_process_password_reset()
   ├── Buscar usuario
   ├── Generar token (32 chars)
   ├── Guardar en user_password_resets
   └── Enviar email con link

FASE 2: Reset
4. Usuario → Click en link del email
5. Guardar token en sesión y redirigir
6. core_login_process_password_set()
   ├── Validar token (existe, no expirado)
   ├── Mostrar formulario nueva contraseña
   └── Al enviar:
       ├── Eliminar registro de reset
       ├── update_internal_user_password()
       ├── login_unlock_account()
       └── complete_user_login()
```

---

## Resumen

El sistema de login de Moodle con autenticación manual proporciona:

1. **Seguridad robusta**:
   - Hashing SHA512 con peppers
   - Protección contra fuerza bruta
   - Tokens CSRF
   - Soporte reCAPTCHA

2. **Recuperación de contraseña segura**:
   - Tokens de un solo uso
   - Expiración configurable
   - No revela existencia de usuarios

3. **Gestión de sesiones**:
   - Cookies seguras (httpOnly, secure)
   - Límite de sesiones concurrentes
   - Timeout configurable

4. **Extensibilidad**:
   - Hooks pre/post login/logout
   - Plugins de autenticación intercambiables
   - Eventos para integración

Los archivos clave son:
- `/login/index.php` - Punto de entrada
- `/auth/manual/auth.php` - Plugin de autenticación
- `/lib/moodlelib.php` - Funciones core
- `/lib/authlib.php` - Funciones de seguridad
- `/login/lib.php` - Funciones de soporte
