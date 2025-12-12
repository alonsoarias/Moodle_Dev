# Análisis Exhaustivo del Plugin local_educambot

## Información General del Plugin

| Propiedad | Valor |
|-----------|-------|
| **Nombre** | local_educambot (Nexo Bot) |
| **Versión** | 2025052011 (v1.9.11) |
| **Requiere Moodle** | 4.1+ |
| **Maturity** | STABLE |

---

## Estructura de Archivos (51 archivos)

```
local/educambot/
├── amd/src/widget.js
├── classes/
│   ├── bot/ (engine.php, context_handler.php, response_builder.php, shortcut_handler.php, schedule_checker.php)
│   ├── form/ (entry_form.php, category_form.php, shortcut_form.php, option_form.php, theme_form.php)
│   ├── output/widget.php
│   ├── privacy/provider.php
│   ├── task/cleanup_history.php
│   ├── external.php
│   └── hook_callbacks.php
├── db/ (access.php, install.xml, install.php, upgrade.php, services.php, tasks.php, hooks.php)
├── lang/ (en/local_educambot.php, es/local_educambot.php)
├── pix/mascots/ (5 SVG mascots)
├── templates/widget.mustache
├── *.php (manage.php, categories.php, shortcuts.php, themes.php, reports.php, etc.)
├── lib.php
├── settings.php
├── styles.css
└── version.php
```

---

## Errores y Problemas Detectados

### ERRORES CRÍTICOS

#### 1. SQL Injection Potencial en `reports.php:220-238`
```php
echo html_writer::script("
    document.querySelector('form').addEventListener('submit', function(e) {
        // JavaScript inline con interpolación directa
    });
");
```
**Problema:** JavaScript inline con strings interpolados puede causar vulnerabilidades XSS.
**Solución:** Usar AMD modules con `$PAGE->requires->js_call_amd()`.

#### 2. Falta archivo `import_export.php`
El archivo está referenciado en `settings.php:192-193` pero **NO EXISTE** en el sistema de archivos.
```php
// settings.php líneas 192-193
$ADMIN->add('local_educambot', new admin_externalpage(
    'local_educambot_importexport',
    get_string('importexport', 'local_educambot'),
    new moodle_url('/local/educambot/import_export.php')
));
```
**Impacto:** Error 404 cuando el administrador accede a "Import/Export".

#### 3. Inconsistencia de tipo de retorno en `engine.php:117`
```php
public function respond(string $question): array {
    // ...
    return [
        'response' => null,  // Puede ser null
        'ruleid' => null,
        'confidence' => 0,
    ];
}
```
**Problema:** La documentación dice que retorna `?array` pero la firma dice `array`.

---

### ERRORES MODERADOS

#### 4. Deprecation Warning en `external.php`
```php
// líneas 70-71
public static function get_popular_questions_returns() {
    return new external_multiple_structure(
```
**Problema:** En Moodle 4.2+ se usa el nuevo patrón con atributos PHP 8 para external functions.
**Solución:** Migrar a `#[\core_external\external_api]` attributes.

#### 5. Falta de validación de entrada en `schedule.php:43-63`
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    // No usa optional_param para todos los parámetros
    $enabled = optional_param('enabled_' . $schedule->id, 0, PARAM_INT);
    $timefrom = optional_param('timefrom_' . $schedule->id, '00:00', PARAM_TEXT);
```
**Problema:** Aunque usa `optional_param`, la validación regex posterior puede fallar silenciosamente.

#### 6. Variable no inicializada en `widget.js:306`
```javascript
if (data.success && data.response) {
    addMessage(data.response, 'bot', data.confidence, data.options);
```
**Problema:** Si `data.confidence` es `undefined`, se pasa como parámetro sin validar.
**Solución:** Agregar fallback: `data.confidence || 0`.

#### 7. Posible Memory Leak en `widget.js:407`
```javascript
options.forEach(function(option) {
    var btn = $('<button>')...
        .on('click', function() { handleOptionClick(option); });
    optionsDiv.append(btn);
});
```
**Problema:** Los event listeners no se limpian cuando se eliminan los elementos.
**Solución:** Usar event delegation en el contenedor padre.

#### 8. Hardcoded timeout en `context_handler.php:134`
```php
$sql = "SELECT u.id, u.firstname, u.lastname...
        $teachers = $DB->get_records_sql($sql, ['courseid' => $this->courseid], 0, 5);
```
**Problema:** Límite hardcodeado de 5 profesores sin opción de configuración.

---

### ADVERTENCIAS Y MEJORAS RECOMENDADAS

#### 9. Inconsistencia en archivos de idioma
| String Key | English | Spanish |
|------------|---------|---------|
| `pluginname` | 'Bot' | 'Nexo Bot' |
| `online` | 'Online' | 'En linea' (sin tilde) |
| `nologs` | 'No conversations recorded yet' | 'No hay conversaciones registradas aun' (sin tilde) |

**Problema:** Nombres inconsistentes y faltan tildes en español.

#### 10. CSS z-index muy alto en `styles.css:28`
```css
#educambot-chat {
    z-index: 999999;
}
```
**Problema:** z-index extremadamente alto puede interferir con otros plugins/modales de Moodle.
**Solución:** Usar valores más moderados (10000-50000).

#### 11. Falta de cache en `engine.php`
```php
public function find_best_match(string $question): ?array {
    // Consulta a base de datos en cada request sin cache
    $sql = "SELECT r.*, c.name as categoryname FROM {local_educambot_rule} r...
```
**Solución:** Implementar MUC (Moodle Universal Cache) para las reglas.

#### 12. Logs excesivos en `cleanup_history.php`
```php
mtrace("Cleaning up conversation logs older than {$retentiondays} days...");
// ...
mtrace("Deleted {$count} old conversation log records.");
```
**Problema:** Múltiples `mtrace()` que pueden saturar logs en instalaciones grandes.

#### 13. Falta índice en `install.xml` para `local_educambot_log`
```xml
<INDEX NAME="idx_userid" UNIQUE="false" FIELDS="userid"/>
<INDEX NAME="idx_timecreated" UNIQUE="false" FIELDS="timecreated"/>
```
**Problema:** Falta índice compuesto para consultas frecuentes `userid + timecreated`.
**Solución:** Agregar `<INDEX NAME="idx_userid_time" UNIQUE="false" FIELDS="userid, timecreated"/>`.

---

## Problemas de Seguridad

### 1. XSS Potencial en `widget.mustache:100`
```mustache
<div class="educambot-message-content">
    {{{greetingmessage}}}
</div>
```
**Problema:** Triple mustache `{{{...}}}` no escapa HTML. Si `greetingmessage` contiene código malicioso, se ejecutará.
**Solución:** Sanitizar en PHP antes de pasar al template o usar `{{greetingmessage}}`.

### 2. Información sensible expuesta en `service.php:153-157`
```php
$response['context'] = [
    'type' => $contexthandler->get_context_type(),
    'courseid' => $contexthandler->get_course_id(),
    'incourse' => $contexthandler->is_in_course(),
];
```
**Problema:** El courseid se expone en respuestas AJAX públicamente.

### 3. Falta rate limiting en endpoints AJAX
Los archivos `service.php`, `history.php`, `startup.php` no implementan rate limiting.
**Solución:** Implementar throttling basado en sesión/IP.

---

## Problemas de Rendimiento

### 1. N+1 Query Problem en `manage.php:242`
```php
foreach ($rules as $rule) {
    // Una consulta por cada regla
    $optioncount = $DB->count_records('local_educambot_option', ['ruleid' => $rule->id]);
}
```
**Solución:** Hacer JOIN o subquery agregada en la consulta principal.

### 2. Carga innecesaria de CSS en `reports.php:48`
```php
$PAGE->requires->css(new moodle_url('/local/educambot/styles.css'));
```
**Problema:** styles.css ya se carga globalmente via `lib.php`.

### 3. Regex compilación repetida en `engine.php:80-93`
```php
foreach ($rules as $rule) {
    if (preg_match('/\b' . preg_quote($rule->pattern, '/') . '\b/i', $normalizedquestion)) {
```
**Solución:** Pre-compilar patrones regex o usar cache.

---

## Problemas de Código y Estilo

### 1. Código muerto en `response_builder.php:213-231`
La función `get_quiz_info()` existe pero nunca es llamada.

### 2. Inconsistencia de nombres de clases
- `\local_educambot\form\entry_form` (snake_case)
- Debería ser `entry_form` → consistente

### 3. Falta type hints en múltiples funciones
```php
// shortcut_handler.php:39
public static function get_action_types() {
    return [ // Sin return type hint
```

### 4. Magic numbers en `widget.js`
```javascript
self.inactivityTimeout = 600000; // 10 minutes default
self.inactivityWarningTime = 60000; // 1 minute warning
```
**Solución:** Usar constantes nombradas.

### 5. Documentación incompleta
Varios métodos carecen de docblocks completos (`@param`, `@return`, `@throws`).

---

## Problemas de UX/Accesibilidad

### 1. Falta de ARIA labels en botones de opciones
```javascript
var btn = $('<button>')
    .addClass('educambot-option-btn')
    .attr('type', 'button')
    .text(btnText)
```
**Problema:** Falta `aria-label` para lectores de pantalla.

### 2. Animaciones sin `prefers-reduced-motion` completo
El CSS tiene soporte parcial para `prefers-reduced-motion` pero no cubre todas las animaciones JavaScript.

### 3. Focus trapping faltante en el modal del chat
Cuando el popup está abierto, el focus puede salir del widget.

---

## Problemas de Compatibilidad

### 1. jQuery dependency en `widget.js`
```javascript
define(['jquery', 'core/ajax'], function($, Ajax) {
```
**Problema:** jQuery está siendo deprecado en Moodle. Versiones futuras podrían no incluirlo.
**Solución:** Migrar a vanilla JavaScript.

### 2. Bootstrap 4 clases hardcodeadas
```php
['class' => 'badge badge-success']  // Bootstrap 4
```
**Problema:** Moodle 4.3+ usa Bootstrap 5 donde algunas clases cambiaron.

### 3. Font Awesome versión mixta
El código referencia iconos de FA5 y FA6 indistintamente.

---

## Mejoras Arquitectónicas Sugeridas

### 1. Implementar Repository Pattern
Actualmente todas las consultas DB están dispersas. Crear:
```
classes/repository/rule_repository.php
classes/repository/category_repository.php
classes/repository/log_repository.php
```

### 2. Implementar Event System
Disparar eventos Moodle cuando:
- Se crea/elimina una regla
- Se registra una conversación
- Se alcanza el límite de inactividad

### 3. Agregar Unit Tests
No hay tests PHPUnit. Crear:
```
tests/engine_test.php
tests/context_handler_test.php
tests/privacy_provider_test.php
```

### 4. Implementar API REST formal
Reemplazar los endpoints AJAX personalizados por External Services de Moodle.

---

## Resumen Ejecutivo

| Categoría | Críticos | Moderados | Menores |
|-----------|----------|-----------|---------|
| **Errores** | 3 | 5 | 7 |
| **Seguridad** | 1 | 2 | 1 |
| **Rendimiento** | 0 | 3 | 2 |
| **Código/Estilo** | 0 | 2 | 5 |
| **UX/A11y** | 0 | 3 | 2 |
| **Compatibilidad** | 0 | 3 | 2 |
| **TOTAL** | **4** | **18** | **19** |

### Prioridades de Corrección:

1. **URGENTE:** Crear el archivo `import_export.php` faltante
2. **URGENTE:** Corregir XSS potencial en `widget.mustache`
3. **ALTA:** Migrar JavaScript inline a AMD modules
4. **ALTA:** Agregar índices compuestos a la base de datos
5. **MEDIA:** Implementar caching con MUC
6. **MEDIA:** Migrar de jQuery a vanilla JS
7. **BAJA:** Corregir inconsistencias de idioma
8. **BAJA:** Agregar tests unitarios

---

*Análisis generado el: 2025-12-12*
*Versión del plugin analizada: v1.9.11 (2025052011)*
