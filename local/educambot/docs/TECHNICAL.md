# EducamBot - Documentación Técnica

**Versión:** 3.8.2 (Build 2025122211)
**Componente:** local_educambot
**Compatibilidad:** Moodle 4.0+
**PHP Mínimo:** 7.4
**Licencia:** GNU GPL v3+

---

## Tabla de Contenidos

1. [Arquitectura General](#arquitectura-general)
2. [Estructura de Archivos](#estructura-de-archivos)
3. [Base de Datos](#base-de-datos)
4. [Clases Principales](#clases-principales)
5. [Motor de Reglas](#motor-de-reglas)
6. [Servicios Web (AJAX)](#servicios-web-ajax)
7. [Sistema de Shortcuts](#sistema-de-shortcuts)
8. [Widget Frontend](#widget-frontend)
9. [Sistema de Temas](#sistema-de-temas)
10. [Mascotas SVG](#mascotas-svg)
11. [Internacionalización](#internacionalización)
12. [Privacidad y GDPR](#privacidad-y-gdpr)
13. [Tareas Programadas](#tareas-programadas)
14. [Extensibilidad](#extensibilidad)
15. [Pruebas](#pruebas)

---

## Arquitectura General

EducamBot sigue una arquitectura modular basada en los estándares de desarrollo de plugins de Moodle.

### Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                        FRONTEND (Browser)                        │
├─────────────────────────────────────────────────────────────────┤
│  widget.js (AMD)  │  styles.css  │  widget.mustache            │
└────────────┬──────┴───────┬──────┴─────────────────────────────┘
             │              │
             │   AJAX/REST  │
             ▼              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SERVICIOS WEB (PHP)                          │
├─────────────────────────────────────────────────────────────────┤
│  service.php  │  history.php  │  feedback.php  │  shortcuts.php │
└───────┬───────┴───────┬───────┴────────┬───────┴───────┬───────┘
        │               │                │               │
        ▼               ▼                ▼               ▼
┌─────────────────────────────────────────────────────────────────┐
│                     CAPA DE LÓGICA (Classes)                    │
├─────────────────────────────────────────────────────────────────┤
│  bot/engine.php        │  Sistema de matching de patrones       │
│  bot/shortcut_handler  │  Procesamiento de datos dinámicos      │
│  bot/response_builder  │  Construcción de respuestas            │
│  bot/context_handler   │  Gestión de contexto Moodle            │
└───────────────────────┬─────────────────────────────────────────┘
                        │
                        ▼
┌─────────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS (Moodle DML)                   │
├─────────────────────────────────────────────────────────────────┤
│  local_educambot_rules      │  Reglas de respuesta              │
│  local_educambot_categories │  Categorías organizativas         │
│  local_educambot_options    │  Opciones de respuesta rápida     │
│  local_educambot_shortcuts  │  Accesos rápidos                  │
│  local_educambot_themes     │  Temas de apariencia              │
│  local_educambot_log        │  Historial de conversaciones      │
│  local_educambot_feedback   │  Retroalimentación de usuarios    │
└─────────────────────────────────────────────────────────────────┘
```

---

## Estructura de Archivos

```
local/educambot/
├── amd/
│   ├── build/
│   │   └── widget.min.js          # JavaScript compilado
│   └── src/
│       └── widget.js              # Código fuente AMD (2147 líneas)
│
├── classes/
│   ├── bot/
│   │   ├── context_handler.php    # Gestión de contexto Moodle
│   │   ├── engine.php             # Motor principal de matching (800+ líneas)
│   │   ├── response_builder.php   # Construcción de respuestas
│   │   └── shortcut_handler.php   # Procesador de shortcuts dinámicos
│   │
│   ├── external/
│   │   ├── get_popular_questions.php
│   │   └── get_similar_questions.php
│   │
│   ├── form/
│   │   ├── category_form.php
│   │   ├── option_form.php
│   │   ├── rule_form.php
│   │   ├── shortcut_form.php
│   │   └── theme_form.php
│   │
│   ├── output/
│   │   └── widget.php             # Renderizador del widget
│   │
│   ├── privacy/
│   │   └── provider.php           # Cumplimiento GDPR
│   │
│   └── task/
│       ├── analyze_feedback.php   # Análisis de feedback
│       └── cleanup_history.php    # Limpieza de historial
│
├── db/
│   ├── access.php                 # Capacidades
│   ├── install.php                # Script de instalación
│   ├── install.xml                # Esquema de BD (XMLDB)
│   ├── services.php               # Definición de servicios externos
│   ├── upgrade.php                # Scripts de actualización
│   └── data/                      # Base de conocimiento inicial (excluida)
│
├── lang/
│   ├── en/
│   │   └── local_educambot.php
│   └── es/
│       └── local_educambot.php    # 750+ cadenas de texto
│
├── mascots/
│   ├── cat.svg
│   ├── clippy.svg
│   ├── lightbulb.svg
│   ├── owl.svg
│   └── robot.svg
│
├── pix/
│   └── icon.svg                   # Icono del plugin
│
├── templates/
│   └── widget.mustache            # Template del widget
│
├── feedback.php                   # Endpoint de feedback
├── history.php                    # Endpoint de historial
├── lib.php                        # Funciones principales
├── manage.php                     # Gestión de reglas
├── manage_*.php                   # Otros gestores
├── reports.php                    # Sistema de reportes
├── service.php                    # Endpoint principal del bot
├── settings.php                   # Configuración del plugin
├── shortcuts.php                  # Endpoint de shortcuts
├── styles.css                     # Estilos (1710 líneas)
└── version.php                    # Información de versión
```

---

## Base de Datos

### Esquema de Tablas

#### `local_educambot_rules`
Almacena las reglas de respuesta del bot.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `categoryid` | BIGINT | FK a categorías (nullable) |
| `pattern` | TEXT | Patrón de pregunta principal |
| `keywords` | TEXT | Palabras clave adicionales |
| `response` | TEXT | Respuesta del bot |
| `language` | VARCHAR(10) | Código de idioma |
| `parentruleid` | BIGINT | Regla padre (traducciones) |
| `roles` | TEXT | Roles permitidos (CSV) |
| `courses` | TEXT | IDs de cursos (CSV) |
| `archetypes` | TEXT | Arquetipos permitidos (JSON) |
| `contextaware` | TINYINT | Sensible al contexto |
| `dynamicresponse` | TINYINT | Usa marcadores dinámicos |
| `requiredcontext` | VARCHAR(20) | Contexto requerido |
| `showoptions` | TINYINT | Mostrar opciones rápidas |
| `tags` | TEXT | Etiquetas para búsqueda |
| `enabled` | TINYINT | Estado habilitado |
| `sortorder` | INT | Orden de prioridad |
| `timecreated` | BIGINT | Timestamp de creación |
| `timemodified` | BIGINT | Timestamp de modificación |

#### `local_educambot_categories`
Organización jerárquica de reglas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `name` | VARCHAR(255) | Nombre de categoría |
| `description` | TEXT | Descripción |
| `parentid` | BIGINT | Categoría padre (nullable) |
| `sortorder` | INT | Orden |
| `timecreated` | BIGINT | Timestamp de creación |
| `timemodified` | BIGINT | Timestamp de modificación |

#### `local_educambot_options`
Botones de respuesta rápida.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `ruleid` | BIGINT | FK a regla padre |
| `text` | VARCHAR(100) | Texto del botón |
| `targetruleid` | BIGINT | FK a regla destino |
| `targetpattern` | VARCHAR(255) | Patrón alternativo |
| `icon` | VARCHAR(50) | Emoji o clase de icono |
| `sortorder` | INT | Orden |
| `timecreated` | BIGINT | Timestamp de creación |

#### `local_educambot_shortcuts`
Accesos rápidos con datos dinámicos.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `name` | VARCHAR(100) | Nombre visible |
| `description` | VARCHAR(255) | Descripción |
| `icon` | VARCHAR(50) | Clase Bootstrap Icons |
| `keywords` | TEXT | Palabras clave de activación |
| `actiontype` | VARCHAR(50) | Tipo de acción |
| `archetypes` | TEXT | Arquetipos permitidos |
| `enabled` | TINYINT | Estado habilitado |
| `sortorder` | INT | Orden |
| `timecreated` | BIGINT | Timestamp de creación |

#### `local_educambot_themes`
Temas de colores personalizados.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `name` | VARCHAR(100) | Nombre del tema |
| `primarycolor` | VARCHAR(7) | Color primario HEX |
| `secondarycolor` | VARCHAR(7) | Color secundario HEX |
| `textcolor` | VARCHAR(7) | Color de texto HEX |
| `backgroundcolor` | VARCHAR(7) | Color de fondo HEX |
| `usercolor` | VARCHAR(7) | Color mensajes usuario |
| `botcolor` | VARCHAR(7) | Color mensajes bot |
| `isdefault` | TINYINT | Es tema por defecto |
| `timecreated` | BIGINT | Timestamp de creación |
| `timemodified` | BIGINT | Timestamp de modificación |

#### `local_educambot_log`
Historial de conversaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `userid` | BIGINT | FK a usuario |
| `courseid` | BIGINT | FK a curso (nullable) |
| `question` | TEXT | Pregunta del usuario |
| `response` | TEXT | Respuesta del bot |
| `ruleid` | BIGINT | FK a regla (nullable) |
| `confidence` | DECIMAL(5,4) | Nivel de confianza |
| `matched` | TINYINT | Si hubo coincidencia |
| `sessionid` | VARCHAR(50) | ID de sesión |
| `timecreated` | BIGINT | Timestamp |

#### `local_educambot_feedback`
Retroalimentación de usuarios.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `id` | BIGINT | Clave primaria |
| `userid` | BIGINT | FK a usuario |
| `ruleid` | BIGINT | FK a regla |
| `logid` | BIGINT | FK a log (nullable) |
| `helpful` | TINYINT | 1=útil, 0=no útil |
| `timecreated` | BIGINT | Timestamp |

---

## Clases Principales

### `\local_educambot\bot\engine`

Motor principal de procesamiento de mensajes.

```php
namespace local_educambot\bot;

class engine {
    /** @var int ID del usuario actual */
    private $userid;

    /** @var int ID del curso actual */
    private $courseid;

    /** @var string Arquetipo de rol del usuario */
    private $userarchetype;

    /** @var string ID de sesión para contexto */
    private $sessionid;

    /**
     * Constructor.
     *
     * @param int $userid ID del usuario
     * @param int $courseid ID del curso (0 para nivel sitio)
     * @param string $sessionid ID de sesión único
     */
    public function __construct(int $userid, int $courseid = 0, string $sessionid = '');

    /**
     * Procesa una pregunta y devuelve la respuesta.
     *
     * @param string $question Pregunta del usuario
     * @return array Respuesta con claves: success, response, confidence, options, ruleid
     */
    public function process_question(string $question): array;

    /**
     * Encuentra la mejor regla coincidente.
     *
     * @param string $question Pregunta normalizada
     * @return array|null Regla con score de confianza
     */
    private function find_best_match(string $question): ?array;

    /**
     * Calcula el score de coincidencia para una regla.
     *
     * @param object $rule Objeto de regla
     * @param string $question Pregunta normalizada
     * @return float Score entre 0 y 1
     */
    private function calculate_match_score(object $rule, string $question): float;

    /**
     * Verifica si el usuario tiene acceso a la regla.
     *
     * @param object $rule Objeto de regla
     * @return bool True si tiene acceso
     */
    private function user_can_access_rule(object $rule): bool;
}
```

### Algoritmo de Matching

El motor utiliza un algoritmo de coincidencia multi-fase:

1. **Normalización**: Convierte texto a minúsculas, elimina acentos y puntuación
2. **Coincidencia exacta**: Busca coincidencia exacta del patrón (score: 1.0)
3. **Coincidencia de palabras clave**: Evalúa keywords secundarios (score: 0.5-0.9)
4. **Coincidencia parcial**: Busca substrings y similitud (score: 0.3-0.5)
5. **Filtrado por contexto**: Verifica roles, arquetipos, cursos y contexto

```php
// Ejemplo de cálculo de score
private function calculate_match_score(object $rule, string $question): float {
    $score = 0.0;
    $pattern = $this->normalize_text($rule->pattern);

    // Coincidencia exacta del patrón
    if ($pattern === $question) {
        return 1.0;
    }

    // Patrón contenido en la pregunta
    if (strpos($question, $pattern) !== false) {
        $score = max($score, 0.9);
    }

    // Coincidencia de palabras clave
    $keywords = $this->parse_keywords($rule->keywords);
    $matchedKeywords = 0;
    foreach ($keywords as $keyword) {
        if (strpos($question, $keyword) !== false) {
            $matchedKeywords++;
        }
    }
    if (!empty($keywords)) {
        $keywordScore = ($matchedKeywords / count($keywords)) * 0.8;
        $score = max($score, $keywordScore);
    }

    // Similitud de palabras (Levenshtein para textos cortos)
    $words = explode(' ', $question);
    $patternWords = explode(' ', $pattern);
    // ... cálculo adicional

    return $score;
}
```

### `\local_educambot\bot\shortcut_handler`

Maneja los accesos rápidos con datos dinámicos de Moodle.

```php
class shortcut_handler {
    /**
     * Obtiene los shortcuts visibles para el usuario.
     *
     * @param int $userid ID del usuario
     * @param int $courseid ID del curso actual
     * @param string $userarchetype Arquetipo del usuario
     * @return array Lista de shortcuts con datos
     */
    public function get_visible_shortcuts(int $userid, int $courseid, string $userarchetype): array;

    /**
     * Ejecuta un shortcut y devuelve los datos.
     *
     * @param string $actiontype Tipo de acción
     * @param int $userid ID del usuario
     * @param int $courseid ID del curso
     * @return array Datos formateados para mostrar
     */
    public function execute_shortcut(string $actiontype, int $userid, int $courseid): array;
}
```

#### Tipos de Acciones Soportadas

| ActionType | Descripción | Requiere Curso |
|------------|-------------|----------------|
| `assignments` | Tareas pendientes | Sí |
| `grades` | Calificaciones del usuario | Sí |
| `calendar` | Eventos próximos (7 días) | No |
| `messages` | Mensajes recientes | No |
| `teachers` | Profesores del curso | Sí |
| `course` | Información del curso | Sí |
| `progress` | Progreso de completado | Sí |
| `courses` | Cursos matriculados | No |
| `participants` | Participantes del curso | Sí |
| `badges` | Insignias obtenidas | No |
| `teacher_grades` | Gestión de calificaciones (profesor) | Sí |
| `admin_users` | Gestión de usuarios (admin) | No |
| `admin_courses` | Administración de cursos (admin) | No |
| `admin_reports` | Reportes del sitio (admin) | No |
| `admin_settings` | Configuración del sitio (admin) | No |
| `admin_plugins` | Gestión de plugins (admin) | No |
| `admin_security` | Seguridad del sitio (admin) | No |
| `admin_backup` | Copias de seguridad (admin) | No |

### `\local_educambot\bot\response_builder`

Construye respuestas con marcadores dinámicos.

```php
class response_builder {
    /**
     * Construye la respuesta final reemplazando placeholders.
     *
     * @param string $template Template de respuesta
     * @param int $userid ID del usuario
     * @param int $courseid ID del curso
     * @return string Respuesta procesada
     */
    public function build(string $template, int $userid, int $courseid): string;

    /**
     * Obtiene todos los placeholders disponibles.
     *
     * @return array Lista de placeholders con descripciones
     */
    public static function get_available_placeholders(): array;
}
```

#### Placeholders Disponibles

| Placeholder | Descripción | Contexto |
|-------------|-------------|----------|
| `{{userfirstname}}` | Nombre del usuario | Global |
| `{{userlastname}}` | Apellido del usuario | Global |
| `{{fullname}}` | Nombre completo | Global |
| `{{username}}` | Nombre de usuario | Global |
| `{{botname}}` | Nombre del bot | Global |
| `{{coursename}}` | Nombre del curso | Curso |
| `{{courseshortname}}` | Nombre corto del curso | Curso |
| `{{courseenddate}}` | Fecha de fin del curso | Curso |
| `{{teachername}}` | Nombre del profesor | Curso |
| `{{currentgrade}}` | Calificación actual | Curso |
| `{{completion}}` | Porcentaje de progreso | Curso |
| `{{nextassignment}}` | Próxima tarea | Curso |
| `{{pendingassignments}}` | Tareas pendientes | Curso |
| `{{nextquiz}}` | Próximo cuestionario | Curso |
| `{{nextevent}}` | Próximo evento | Global |
| `{{weekevents}}` | Eventos de la semana | Global |

### `\local_educambot\bot\context_handler`

Gestiona el contexto de Moodle para las reglas.

```php
class context_handler {
    /**
     * Obtiene el arquetipo de rol del usuario.
     *
     * @param int $userid ID del usuario
     * @param int $courseid ID del curso (0 para nivel sitio)
     * @return string Arquetipo: student, teacher, editingteacher, etc.
     */
    public static function get_user_archetype(int $userid, int $courseid = 0): string;

    /**
     * Verifica si el contexto actual cumple con el requerido.
     *
     * @param string $required Contexto requerido: any, site, course, activity
     * @param int $courseid ID del curso actual
     * @return bool True si cumple
     */
    public static function context_matches(string $required, int $courseid): bool;
}
```

---

## Servicios Web (AJAX)

### Endpoints de Servicio

#### `service.php` - Endpoint Principal

Procesa las preguntas del usuario.

**Request (POST):**
```
sesskey: string (requerido)
question: string (requerido, min 2 caracteres)
courseid: int (opcional, default 0)
sessionid: string (opcional, para tracking de contexto)
```

**Response (JSON):**
```json
{
    "success": true,
    "response": "<p>Respuesta HTML del bot</p>",
    "confidence": 0.85,
    "options": [
        {
            "text": "Ver más información",
            "action": "más información sobre esto",
            "icon": "bi-info-circle"
        }
    ],
    "ruleid": 123
}
```

#### `shortcuts.php` - Accesos Rápidos

Obtiene los shortcuts disponibles y ejecuta acciones.

**Request (POST):**
```
sesskey: string (requerido)
courseid: int (opcional)
userrole: string (arquetipo del usuario)
```

**Response (JSON):**
```json
{
    "success": true,
    "shortcuts": [
        {
            "id": 1,
            "name": "Mis tareas",
            "description": "Ver tareas pendientes",
            "icon": "bi-journal-check",
            "action": "Ver mis tareas pendientes"
        }
    ]
}
```

#### `history.php` - Historial

Gestiona el historial de conversaciones.

**Actions:**
- `recent`: Obtiene conversaciones recientes
- `clear`: Limpia el historial del usuario

#### `feedback.php` - Retroalimentación

Registra la valoración de respuestas.

**Request (POST):**
```
sesskey: string (requerido)
ruleid: int (requerido)
helpful: int (1 o 0)
```

### Servicios Externos (Moodle Web Services)

Definidos en `db/services.php`:

```php
$functions = [
    'local_educambot_get_popular_questions' => [
        'classname'     => 'local_educambot\external\get_popular_questions',
        'methodname'    => 'execute',
        'description'   => 'Get popular questions from the chatbot',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
    'local_educambot_get_similar_questions' => [
        'classname'     => 'local_educambot\external\get_similar_questions',
        'methodname'    => 'execute',
        'description'   => 'Get similar questions based on user input',
        'type'          => 'read',
        'ajax'          => true,
        'loginrequired' => true,
    ],
];
```

---

## Sistema de Shortcuts

### Arquitectura de Shortcuts

```
┌─────────────────────────────────────────────────────┐
│                 Usuario hace clic                    │
│                 en acceso rápido                     │
└────────────────────────┬────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────┐
│              shortcut_handler.php                    │
│  - Valida permisos del usuario                      │
│  - Verifica contexto requerido                      │
│  - Ejecuta el tipo de acción                        │
└────────────────────────┬────────────────────────────┘
                         │
        ┌────────────────┼────────────────┐
        ▼                ▼                ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ assignments  │ │   grades     │ │  calendar    │
│ Moodle API   │ │ Gradebook    │ │ Calendar API │
└──────────────┘ └──────────────┘ └──────────────┘
        │                │                │
        └────────────────┼────────────────┘
                         ▼
┌─────────────────────────────────────────────────────┐
│              Formato de respuesta HTML               │
│  - Lista de items                                   │
│  - Links a páginas de Moodle                        │
│  - Iconos y fechas formateadas                      │
└─────────────────────────────────────────────────────┘
```

### Agregar un Nuevo Tipo de Shortcut

1. Agregar el tipo en `shortcut_handler.php`:

```php
// En el método execute_shortcut()
case 'my_new_type':
    return $this->get_my_new_data($userid, $courseid);
```

2. Implementar el método:

```php
private function get_my_new_data(int $userid, int $courseid): array {
    global $DB, $OUTPUT;

    // Obtener datos de Moodle
    $data = $DB->get_records('mi_tabla', ['userid' => $userid]);

    // Formatear respuesta
    $html = '<div class="educambot-shortcut-response">';
    $html .= '<strong>' . get_string('my_header', 'local_educambot') . '</strong>';
    // ... construir HTML
    $html .= '</div>';

    return [
        'success' => true,
        'response' => $html,
        'confidence' => 1.0,
        'options' => [],
        'ruleid' => 0,
    ];
}
```

3. Agregar strings en `lang/`:

```php
$string['actiontype_my_new_type'] = 'Mi nuevo tipo de datos';
$string['shortcut_mynewheader'] = 'Encabezado de mi nuevo shortcut:';
```

---

## Widget Frontend

### Módulo AMD: `widget.js`

El widget está implementado como un módulo AMD de Moodle usando jQuery.

```javascript
define(['jquery', 'core/ajax'], function($, Ajax) {
    var chat = {
        // Configuración
        inactivityTimeout: 600000,
        enableHistory: true,
        sessionId: null,
        maxRetries: 3,
        retryDelays: [1000, 2000, 4000],

        // Referencias DOM
        elements: {
            chat: null,
            popup: null,
            textarea: null,
            messages: null,
            // ...
        },

        // Inicialización
        init: function() {
            // Cachear elementos DOM
            // Configurar event delegation
            // Cargar historial local
            // Inicializar mascota
        },

        // Envío con retry
        sendMessageWithRetry: function(question, attempt) {
            // Implementa exponential backoff
        },

        // Persistencia local
        saveLocalConversation: function() {
            // Guarda en localStorage
        },

        // Sincronización entre tabs
        initStorageListener: function(messages) {
            // Escucha eventos de storage
        }
    };

    return chat;
});
```

### Sistema de Eventos

El widget usa **event delegation** unificado para evitar handlers duplicados:

```javascript
setupEventDelegation: function() {
    var self = this;

    // Único handler para todas las acciones
    self.elements.chat.on('click', '[data-educambot-action]', function(e) {
        e.preventDefault();
        e.stopPropagation();

        var action = $(this).attr('data-educambot-action');

        switch (action) {
            case 'shortcut':
                self.handleShortcutClick($(this));
                break;
            case 'option':
                self.handleOptionClick($(this));
                break;
            case 'popular-question':
                self.handlePopularQuestionClick($(this));
                break;
            // ...
        }
    });
}
```

### Características del Widget (v3.8.0+)

| Característica | Descripción |
|----------------|-------------|
| Scroll-to-bottom | Botón para ir al final de los mensajes |
| Character counter | Contador de caracteres con límite |
| Keyboard helper | Indicador de atajos de teclado |
| Time grouping | Separadores de tiempo entre mensajes |
| Screen reader | Anuncios para lectores de pantalla |
| Cross-tab sync | Sincronización de conversación entre pestañas |
| Retry mechanism | Reintentos con backoff exponencial |

### CSS Custom Properties

El widget usa CSS custom properties para tematización:

```css
#educambot-chat {
    --educambot-primary: #0f6fc5;
    --educambot-secondary: #084a8a;
    --educambot-text: #1f2937;
    --educambot-background: #f9fafb;
    --educambot-user: #0f6fc5;
    --educambot-bot: #ffffff;
}
```

Estas variables son inyectadas dinámicamente desde PHP basándose en el tema activo.

---

## Sistema de Temas

### Clase de Renderizado: `output/widget.php`

```php
class widget implements renderable, templatable {
    public function export_for_template(renderer_base $output) {
        // Obtener tema activo
        $theme = $this->get_active_theme();

        // Generar CSS inline con custom properties
        $customcss = $this->generate_theme_css($theme);

        return [
            'botname' => $this->config->botname,
            'greeting' => $this->process_greeting(),
            'customcss' => $customcss,
            'mascotsvg' => $this->get_mascot_svg(),
            // ...
        ];
    }

    private function generate_theme_css($theme) {
        return sprintf(
            '--educambot-primary: %s; --educambot-secondary: %s; ...',
            $theme->primarycolor,
            $theme->secondarycolor
            // ...
        );
    }
}
```

### Template Mustache: `widget.mustache`

```mustache
<div id="educambot-chat"
     class="educambot-role-{{userrolearchetype}}"
     style="{{customcss}}"
     data-inactivity-timeout="{{inactivitytimeout}}"
     data-enable-history="{{enablehistory}}"
     data-userid="{{userid}}"
     data-courseid="{{courseid}}"
     data-serviceurl="{{serviceurl}}"
     data-shortcutsurl="{{shortcutsurl}}"
     data-sesskey="{{sesskey}}">

    <!-- Botón flotante -->
    <button id="educambot-btn">
        <span class="educambot-open-icon">{{{widgeticon}}}</span>
        <span class="educambot-close-icon">×</span>
    </button>

    <!-- Popup del chat -->
    <div class="educambot-popup">
        <div class="educambot-header">
            <div class="educambot-info">
                <div class="educambot-avatar">{{{headericon}}}</div>
                <div class="educambot-namearea">
                    <span class="educambot-name">{{botname}}</span>
                    <span class="educambot-status">{{#str}}online, local_educambot{{/str}}</span>
                </div>
            </div>
            <!-- Acciones del header -->
        </div>

        <div id="educambot-messages" class="educambot-messages">
            <!-- Mensaje de saludo -->
            <div class="educambot-message educambot-bot">
                <div class="educambot-message-content">{{{greeting}}}</div>
            </div>
        </div>

        {{#mascotenabled}}
        <div id="educambot-mascot" class="educambot-mascot-container" data-state="idle">
            <div class="educambot-mascot-svg">{{{mascotsvg}}}</div>
            <!-- Tooltip de mascota -->
        </div>
        {{/mascotenabled}}

        <div class="educambot-sendarea">
            <textarea id="educambot-textarea" placeholder="{{#str}}typeaquestion, local_educambot{{/str}}"></textarea>
            <button id="educambot-send" class="educambot-send-btn">
                <!-- Icono de enviar -->
            </button>
        </div>

        <div class="educambot-credits">
            {{#str}}developedby, local_educambot{{/str}}
            <a href="https://ingeweb.co">Ingeweb</a>
        </div>
    </div>
</div>

{{#js}}
require(['local_educambot/widget'], function(chat) {
    chat.init();
});
{{/js}}
```

---

## Mascotas SVG

### Estructura de SVG Animable

Las mascotas usan SVG con elementos identificados para animación CSS:

```xml
<svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">
    <g id="body">
        <!-- Cuerpo principal -->
    </g>
    <g id="eyes">
        <!-- Ojos (para parpadeo) -->
    </g>
    <g id="arms">
        <!-- Brazos (para saludar) -->
    </g>
</svg>
```

### Estados de Animación CSS

```css
/* Estado: Thinking */
.educambot-mascot-container[data-state="thinking"] .educambot-mascot-svg {
    animation: educambot-mascot-think 1s ease-in-out infinite;
}

.educambot-mascot-container[data-state="thinking"] #eyes {
    animation: educambot-eyes-blink 0.5s ease infinite;
}

@keyframes educambot-mascot-think {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(-5deg); }
    75% { transform: rotate(5deg); }
}

/* Estado: Success */
.educambot-mascot-container[data-state="success"] .educambot-mascot-svg {
    animation: educambot-mascot-celebrate 0.8s ease-out;
}

@keyframes educambot-mascot-celebrate {
    0% { transform: scale(1) rotate(0deg); }
    25% { transform: scale(1.15) rotate(-8deg); }
    50% { transform: scale(1.1) rotate(8deg); }
    100% { transform: scale(1) rotate(0deg); }
}
```

### Mascotas Incluidas

| Archivo | Descripción |
|---------|-------------|
| `clippy.svg` | Clip de papel estilo Microsoft |
| `robot.svg` | Robot amigable con antenas |
| `owl.svg` | Búho académico |
| `cat.svg` | Gato estudioso con lentes |
| `lightbulb.svg` | Bombilla de ideas |

---

## Internacionalización

### Estructura de Archivos de Idioma

```
lang/
├── en/
│   └── local_educambot.php    # Inglés (base)
└── es/
    └── local_educambot.php    # Español (750+ strings)
```

### Categorías de Strings

| Prefijo | Descripción |
|---------|-------------|
| `pluginname`, `educambot` | Identificación |
| `settings_*` | Configuración |
| `manage*`, `add*`, `edit*`, `delete*` | Gestión |
| `shortcut_*` | Respuestas de shortcuts |
| `mascot_*` | Mensajes de mascota |
| `greeting_*`, `farewell_*`, `thanks_*` | Respuestas automáticas |
| `fallback_*` | Respuestas cuando no hay match |
| `privacy:*` | Metadatos de privacidad |
| `actiontype_*` | Tipos de acciones de shortcuts |
| `error_*` | Mensajes de error |
| `export_*` | Exportación de conversaciones |

### Multiidioma en Reglas

Las reglas soportan asociación con idioma y reglas padre para traducciones:

```php
// Al crear una traducción
$rule->language = 'es';
$rule->parentruleid = $originalruleid;

// Al buscar reglas, se prioriza el idioma del usuario
$userlang = current_language();
$rules = $DB->get_records_select('local_educambot_rules',
    "enabled = 1 AND (language = '' OR language = :lang)",
    ['lang' => $userlang]
);
```

---

## Privacidad y GDPR

### Provider de Privacidad

Implementado en `classes/privacy/provider.php`:

```php
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider {

    /**
     * Describe los datos almacenados.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_educambot_log', [
            'userid' => 'privacy:metadata:log:userid',
            'question' => 'privacy:metadata:log:question',
            'response' => 'privacy:metadata:log:response',
            // ...
        ], 'privacy:metadata:log');

        $collection->add_database_table('local_educambot_feedback', [
            'userid' => 'privacy:metadata:feedback:userid',
            // ...
        ], 'privacy:metadata:feedback');

        return $collection;
    }

    /**
     * Exporta datos del usuario.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        // Exportar logs y feedback del usuario
    }

    /**
     * Elimina datos del usuario.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        $DB->delete_records('local_educambot_log', ['userid' => $userid]);
        $DB->delete_records('local_educambot_feedback', ['userid' => $userid]);
    }
}
```

### Configuración de Retención

```php
// En settings.php
$settings->add(new admin_setting_configselect(
    'local_educambot/historyretention',
    get_string('historyretention', 'local_educambot'),
    get_string('historyretention_desc', 'local_educambot'),
    0, // 0 = forever
    [
        0 => get_string('retention_forever', 'local_educambot'),
        604800 => get_string('retention_1week', 'local_educambot'),
        2592000 => get_string('retention_1month', 'local_educambot'),
        // ...
    ]
));
```

---

## Tareas Programadas

### `cleanup_history`

Limpia registros de historial antiguos según la configuración de retención.

```php
class cleanup_history extends \core\task\scheduled_task {
    public function execute() {
        global $DB;

        $retention = get_config('local_educambot', 'historyretention');
        if (empty($retention)) {
            return; // Retención infinita
        }

        $cutoff = time() - $retention;
        $DB->delete_records_select(
            'local_educambot_log',
            'timecreated < :cutoff',
            ['cutoff' => $cutoff]
        );
    }
}
```

### `analyze_feedback`

Analiza el feedback negativo para identificar reglas problemáticas.

```php
class analyze_feedback extends \core\task\scheduled_task {
    public function execute() {
        global $DB;

        // Obtener reglas con alto feedback negativo
        $sql = "SELECT ruleid,
                       COUNT(*) as total,
                       SUM(CASE WHEN helpful = 0 THEN 1 ELSE 0 END) as negative
                FROM {local_educambot_feedback}
                WHERE timecreated > :since
                GROUP BY ruleid
                HAVING SUM(CASE WHEN helpful = 0 THEN 1 ELSE 0 END) > 5";

        $rules = $DB->get_records_sql($sql, ['since' => time() - 604800]);

        // Notificar a administradores o marcar reglas
        foreach ($rules as $rule) {
            $ratio = $rule->negative / $rule->total;
            if ($ratio > 0.5) {
                // Marcar regla para revisión
            }
        }
    }
}
```

### Configuración de Tareas

En `db/tasks.php`:

```php
$tasks = [
    [
        'classname' => 'local_educambot\task\cleanup_history',
        'blocking' => 0,
        'minute' => '0',
        'hour' => '3',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*'
    ],
    [
        'classname' => 'local_educambot\task\analyze_feedback',
        'blocking' => 0,
        'minute' => '30',
        'hour' => '4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1' // Solo lunes
    ]
];
```

---

## Extensibilidad

### Hooks y Eventos

El plugin emite eventos que pueden ser observados:

```php
// Ejemplo: Evento cuando se registra una conversación
$event = \local_educambot\event\conversation_logged::create([
    'context' => \context_system::instance(),
    'userid' => $USER->id,
    'other' => [
        'question' => $question,
        'matched' => $matched,
        'ruleid' => $ruleid
    ]
]);
$event->trigger();
```

### Agregar Nuevos Tipos de Iconos

En `output/widget.php`, método `get_widget_icon()`:

```php
case 'mytype':
    return '<span class="my-custom-icon">' . $iconvalue . '</span>';
```

### Personalizar el Motor de Matching

Extender `bot\engine`:

```php
class custom_engine extends \local_educambot\bot\engine {
    protected function calculate_match_score(object $rule, string $question): float {
        // Implementar algoritmo personalizado (NLP, ML, etc.)
        $baseScore = parent::calculate_match_score($rule, $question);

        // Agregar lógica adicional
        $customScore = $this->my_custom_matching($rule, $question);

        return max($baseScore, $customScore);
    }
}
```

---

## Pruebas

### Estructura de Tests

```
tests/
├── bot_engine_test.php
├── shortcut_handler_test.php
├── privacy_provider_test.php
└── behat/
    └── educambot_widget.feature
```

### Ejemplo de Test Unitario

```php
class bot_engine_test extends \advanced_testcase {
    public function test_exact_pattern_match() {
        $this->resetAfterTest();

        // Crear regla de prueba
        $rule = $this->create_test_rule([
            'pattern' => '¿Cómo me inscribo?',
            'response' => 'Para inscribirte...',
            'enabled' => 1
        ]);

        // Crear engine
        $engine = new \local_educambot\bot\engine($this->user->id);

        // Probar coincidencia exacta
        $result = $engine->process_question('¿Cómo me inscribo?');

        $this->assertTrue($result['success']);
        $this->assertEquals(1.0, $result['confidence']);
        $this->assertStringContains('Para inscribirte', $result['response']);
    }

    public function test_keyword_match() {
        // ...
    }

    public function test_no_match_fallback() {
        // ...
    }
}
```

### Behat Tests

```gherkin
@local_educambot
Feature: EducamBot widget
  As a student
  I need to use the chatbot
  So that I can get help with my courses

  Background:
    Given the following "users" exist:
      | username | firstname | lastname |
      | student1 | Student   | One      |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |

  Scenario: Open and close the chatbot widget
    Given I log in as "student1"
    When I click on "#educambot-btn" "css_element"
    Then I should see "Nexo Bot"
    And ".educambot-popup" "css_element" should be visible
    When I click on "#educambot-close" "css_element"
    Then ".educambot-popup" "css_element" should not be visible

  Scenario: Send a question and receive a response
    Given the following "local_educambot > rules" exist:
      | pattern              | response              | enabled |
      | ¿Cómo me inscribo?   | Para inscribirte...   | 1       |
    And I log in as "student1"
    And I click on "#educambot-btn" "css_element"
    When I set the field "educambot-textarea" to "¿Cómo me inscribo?"
    And I click on "#educambot-send" "css_element"
    Then I should see "Para inscribirte..."
```

---

## Changelog Técnico

### v3.8.2 (2025-12-22)
- Unificación de estilos entre opciones y shortcuts
- Fix hover text en botones de opciones
- Uso de Bootstrap Icons en opciones

### v3.8.0 (2025-12-20)
- Botón scroll-to-bottom
- Contador de caracteres
- Helper de atajos de teclado
- Separadores de tiempo en mensajes
- Mejoras de accesibilidad (ARIA)
- Animaciones mejoradas

### v3.0.0 (2025-11-15)
- Reescritura completa del widget JS
- Sistema de delegación de eventos unificado
- Mecanismo de retry con backoff exponencial
- Session ID para tracking de contexto
- Sincronización entre pestañas

### v2.2.0 (2025-10-01)
- Mascotas animadas SVG
- Temas de colores personalizables
- Shortcuts con descripciones
- Posicionamiento externo de mascota

### v2.0.0 (2025-08-15)
- Sistema de feedback
- Exportación de conversaciones
- Indicador de escritura
- Timestamps en mensajes
- Notificaciones de sonido

---

## Contacto y Soporte

- **Autor:** Alonso Arias
- **Email:** soporte@ingeweb.co
- **Web:** https://ingeweb.co
- **Repositorio:** GitHub (privado)

---

*Documentación generada para EducamBot v3.8.2*
*Última actualización: Diciembre 2025*
