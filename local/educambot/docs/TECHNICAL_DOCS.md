# EducamBot - Documentación Técnica

**Versión:** 3.8.2
**Autor:** Alonso Arias <soporte@ingeweb.co>
**Copyright:** 2025 Ingeweb <https://ingeweb.co>
**Licencia:** GNU GPL v3 o posterior

---

## Tabla de Contenidos

1. [Arquitectura del Plugin](#1-arquitectura-del-plugin)
2. [Estructura de Directorios](#2-estructura-de-directorios)
3. [Base de Datos](#3-base-de-datos)
4. [APIs y Servicios](#4-apis-y-servicios)
5. [Clases Principales](#5-clases-principales)
6. [Motor de Matching (NLP)](#6-motor-de-matching-nlp)
7. [Hooks y Eventos](#7-hooks-y-eventos)
8. [Tareas Programadas](#8-tareas-programadas)
9. [Sistema de Plantillas](#9-sistema-de-plantillas)
10. [Extensibilidad](#10-extensibilidad)
11. [Notas para Desarrolladores](#11-notas-para-desarrolladores)

---

## 1. Arquitectura del Plugin

### 1.1 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────────┐
│                         CAPA DE PRESENTACIÓN                         │
├─────────────────────────────────────────────────────────────────────┤
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ widget.js    │  │ widget.      │  │ styles.css               │  │
│  │ (AMD Module) │  │ mustache     │  │ (Bootstrap Icons)        │  │
│  └──────┬───────┘  └──────────────┘  └──────────────────────────┘  │
│         │                                                            │
│         ▼                                                            │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │                    AJAX Endpoints                             │   │
│  │  service.php │ feedback.php │ history.php │ shortcuts_ajax   │   │
│  └──────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                          CAPA DE NEGOCIO                            │
├─────────────────────────────────────────────────────────────────────┤
│  ┌────────────────────────────────────────────────────────────┐     │
│  │                    Bot Engine (NLP Core)                    │     │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐   │     │
│  │  │text_         │ │intent_       │ │conversation_     │   │     │
│  │  │normalizer    │ │detector      │ │context           │   │     │
│  │  └──────────────┘ └──────────────┘ └──────────────────┘   │     │
│  │  ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐   │     │
│  │  │response_     │ │shortcut_     │ │context_          │   │     │
│  │  │builder       │ │handler       │ │handler           │   │     │
│  │  └──────────────┘ └──────────────┘ └──────────────────┘   │     │
│  │  ┌──────────────┐ ┌──────────────┐                        │     │
│  │  │pattern_      │ │quick_access_ │                        │     │
│  │  │loader        │ │handler       │                        │     │
│  │  └──────────────┘ └──────────────┘                        │     │
│  └────────────────────────────────────────────────────────────┘     │
│                                                                      │
│  ┌────────────────────────────────────────────────────────────┐     │
│  │              Servicios Externos (Web Services)              │     │
│  │  external.php: get_popular_questions, get_similar_questions │     │
│  └────────────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────┐
│                          CAPA DE DATOS                              │
├─────────────────────────────────────────────────────────────────────┤
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │                    Base de Datos Moodle                      │    │
│  │  local_educambot_rule     │ local_educambot_category         │    │
│  │  local_educambot_option   │ local_educambot_shortcut         │    │
│  │  local_educambot_log      │ local_educambot_feedback         │    │
│  │  local_educambot_theme    │ local_educambot_context          │    │
│  │  local_educambot_pattern  │                                  │    │
│  └─────────────────────────────────────────────────────────────┘    │
│                                                                      │
│  ┌─────────────────────────────────────────────────────────────┐    │
│  │               Archivos JSON (Base de Conocimiento)           │    │
│  │  db/data/knowledge/*.json  │  db/data/menus/*.json           │    │
│  │  db/data/intents.json      │  db/data/synonyms.json          │    │
│  └─────────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────────┘
```

### 1.2 Flujo de Datos

```
Usuario escribe pregunta
        │
        ▼
┌───────────────────┐
│   widget.js       │ ──→ AJAX POST a service.php
└───────────────────┘
        │
        ▼
┌───────────────────┐
│   service.php     │ ──→ Valida sesskey y permisos
└───────────────────┘
        │
        ▼
┌───────────────────┐
│   shortcut_       │ ──→ ¿Es un shortcut? → Ejecutar acción dinámica
│   handler         │
└───────────────────┘
        │ No
        ▼
┌───────────────────┐
│   engine.php      │ ──→ Motor principal de procesamiento
│   respond()       │
└───────────────────┘
        │
        ├──→ analyze_question()      → Normalización + detección de intención
        │
        ├──→ handle_special_intents() → Saludos, despedidas, agradecimientos
        │
        ├──→ get_filtered_rules()    → Filtrar por rol, idioma, contexto
        │
        ├──→ score_rules()           → Calcular puntaje de coincidencia
        │
        ├──→ build_response()        → Construir respuesta con placeholders
        │
        └──→ log_and_update_context() → Guardar en log y contexto
        │
        ▼
┌───────────────────┐
│   JSON Response   │ ──→ {success, response, ruleid, confidence, options}
└───────────────────┘
        │
        ▼
┌───────────────────┐
│   widget.js       │ ──→ Renderizar respuesta en el DOM
│   displayMessage()│
└───────────────────┘
```

---

## 2. Estructura de Directorios

```
local/educambot/
├── amd/
│   ├── build/
│   │   └── widget.min.js          # JS compilado (producción)
│   └── src/
│       └── widget.js              # Módulo AMD principal del widget
│
├── classes/
│   ├── bot/
│   │   ├── context_handler.php    # Manejo del contexto Moodle
│   │   ├── conversation_context.php # Contexto de conversación
│   │   ├── engine.php             # Motor principal de NLP
│   │   ├── intent_detector.php    # Detección de intenciones
│   │   ├── pattern_loader.php     # Carga de patrones desde DB/JSON
│   │   ├── quick_access_handler.php # Manejador de accesos rápidos
│   │   ├── response_builder.php   # Constructor de respuestas dinámicas
│   │   ├── shortcut_handler.php   # Manejador de shortcuts
│   │   └── text_normalizer.php    # Normalización de texto
│   │
│   ├── form/
│   │   ├── category_form.php      # Formulario de categorías
│   │   ├── entry_form.php         # Formulario de reglas
│   │   ├── option_form.php        # Formulario de opciones
│   │   ├── shortcut_form.php      # Formulario de shortcuts
│   │   └── theme_form.php         # Formulario de temas
│   │
│   ├── output/
│   │   └── widget.php             # Clase renderable del widget
│   │
│   ├── privacy/
│   │   └── provider.php           # Provider GDPR
│   │
│   ├── task/
│   │   ├── analyze_feedback.php   # Tarea de análisis de feedback
│   │   └── cleanup_history.php    # Tarea de limpieza de historial
│   │
│   ├── external.php               # Servicios web externos
│   └── hook_callbacks.php         # Callbacks de hooks Moodle
│
├── db/
│   ├── data/
│   │   ├── knowledge/             # Base de conocimiento por tema
│   │   │   ├── activities.json
│   │   │   ├── admin.json
│   │   │   ├── calendar.json
│   │   │   ├── courses.json
│   │   │   ├── general.json
│   │   │   ├── grades.json
│   │   │   ├── messages.json
│   │   │   └── teachers.json
│   │   │
│   │   ├── menus/                 # Menús por arquetipo de rol
│   │   │   ├── guest.json
│   │   │   ├── manager.json
│   │   │   ├── student.json
│   │   │   └── teacher.json
│   │   │
│   │   ├── templates/             # Plantillas de importación
│   │   │   ├── import_template.csv
│   │   │   └── import_template.json
│   │   │
│   │   ├── abbreviations.json     # Abreviaciones para normalización
│   │   ├── categories.json        # Categorías predefinidas
│   │   ├── conversation.json      # Patrones de conversación
│   │   ├── entities.json          # Entidades para NER
│   │   ├── intents.json           # Patrones de intención
│   │   ├── navigation.json        # Reglas de navegación
│   │   ├── responses.json         # Plantillas de respuesta
│   │   ├── role_knowledge.json    # Conocimiento por rol
│   │   ├── sentiments.json        # Patrones de sentimiento
│   │   ├── shortcuts.json         # Shortcuts predefinidos
│   │   ├── stopwords.json         # Palabras vacías
│   │   ├── synonyms.json          # Sinónimos
│   │   ├── themes.json            # Temas visuales
│   │   └── topics.json            # Tópicos para clasificación
│   │
│   ├── access.php                 # Definición de capacidades
│   ├── hooks.php                  # Definición de hooks
│   ├── install.php                # Script de instalación
│   ├── install.xml                # Esquema de base de datos
│   ├── services.php               # Definición de servicios web
│   ├── tasks.php                  # Definición de tareas programadas
│   └── upgrade.php                # Scripts de actualización
│
├── lang/
│   ├── en/
│   │   └── local_educambot.php    # Cadenas en inglés
│   └── es/
│       └── local_educambot.php    # Cadenas en español
│
├── pix/
│   └── mascots/                   # SVGs de mascotas
│       ├── cat.svg
│       ├── clippy.svg
│       ├── lightbulb.svg
│       ├── owl.svg
│       └── robot.svg
│
├── templates/
│   └── widget.mustache            # Template del widget
│
├── docs/                          # Documentación
│   ├── educambot_user_guide.html
│   └── TECHNICAL_DOCS.md
│
├── categories.php                 # Gestión de categorías
├── duplicate_rule.php             # Duplicar reglas
├── export.php                     # Exportar base de conocimiento
├── feedback.php                   # Endpoint de feedback AJAX
├── history.php                    # Endpoint de historial AJAX
├── import.php                     # Importar base de conocimiento
├── lib.php                        # Funciones de librería
├── manage.php                     # Gestión de reglas
├── manage_options.php             # Gestión de opciones
├── reports.php                    # Panel de reportes
├── service.php                    # Endpoint principal AJAX
├── settings.php                   # Configuración del plugin
├── shortcuts.php                  # Gestión de shortcuts
├── shortcuts_ajax.php             # Endpoint de shortcuts AJAX
├── startup.php                    # Script de inicio
├── styles.css                     # Estilos del widget
├── themes.php                     # Gestión de temas
├── version.php                    # Metadatos del plugin
├── CHANGELOG.md                   # Historial de cambios
└── README.md                      # Documentación básica
```

---

## 3. Base de Datos

### 3.1 Diagrama Entidad-Relación

```
┌─────────────────────────┐       ┌─────────────────────────┐
│ local_educambot_category│       │  local_educambot_rule   │
├─────────────────────────┤       ├─────────────────────────┤
│ PK id                   │◄──────│ FK categoryid           │
│ name                    │       │ PK id                   │
│ description             │       │ pattern                 │
│ FK parent               │───┐   │ keywords                │
│ sortorder               │   │   │ response                │
│ enabled                 │   │   │ tags                    │
│ timecreated            │   │   │ enabled                 │
│ timemodified           │   │   │ showoptions             │
└─────────────────────────┘   │   │ contextaware            │
         ▲                    │   │ dynamicresponse         │
         │                    │   │ requiredcontext         │
         └────────────────────┘   │ roles                   │
                                  │ courses                 │
                                  │ lang                    │
                                  │ FK langparent           │───┐
                                  │ helpfulcount            │   │
                                  │ nothelpfulcount         │   │
                                  │ priority                │   │
                                  │ needs_review            │   │
                                  │ timecreated            │   │
                                  │ timemodified           │   │
                                  └─────────────────────────┘   │
                                           ▲                    │
                                           │                    │
         ┌─────────────────────────────────┼────────────────────┘
         │                                 │
         │   ┌─────────────────────────┐   │
         │   │ local_educambot_option  │   │
         │   ├─────────────────────────┤   │
         │   │ PK id                   │   │
         │   │ FK ruleid              │───┘
         │   │ text                    │
         │   │ action                  │
         │   │ FK targetruleid        │────────┐
         │   │ icon                    │        │
         │   │ sortorder               │        │
         │   │ enabled                 │        │
         │   └─────────────────────────┘        │
         │                                      │
         └──────────────────────────────────────┘

┌─────────────────────────┐       ┌─────────────────────────┐
│  local_educambot_log    │       │local_educambot_feedback │
├─────────────────────────┤       ├─────────────────────────┤
│ PK id                   │       │ PK id                   │
│ FK userid               │       │ FK userid               │
│ question                │       │ FK ruleid               │
│ response                │       │ helpful                 │
│ FK ruleid               │       │ timecreated            │
│ confidence              │       │ timemodified           │
│ matched                 │       └─────────────────────────┘
│ timecreated            │
└─────────────────────────┘

┌─────────────────────────┐       ┌─────────────────────────┐
│local_educambot_shortcut │       │  local_educambot_theme  │
├─────────────────────────┤       ├─────────────────────────┤
│ PK id                   │       │ PK id                   │
│ name                    │       │ name                    │
│ keywords                │       │ primarycolor            │
│ actiontype              │       │ secondarycolor          │
│ description             │       │ textcolor               │
│ icon                    │       │ backgroundcolor         │
│ roles                   │       │ usercolor               │
│ context                 │       │ botcolor                │
│ sortorder               │       │ isdefault               │
│ enabled                 │       │ widgeticontype          │
│ timecreated            │       │ widgeticonurl           │
│ timemodified           │       │ mascottype              │
└─────────────────────────┘       │ mascoturl               │
                                  │ mascotenabled           │
                                  │ timecreated            │
                                  │ timemodified           │
                                  └─────────────────────────┘

┌─────────────────────────┐       ┌─────────────────────────┐
│ local_educambot_context │       │local_educambot_pattern  │
├─────────────────────────┤       ├─────────────────────────┤
│ PK id                   │       │ PK id                   │
│ FK userid               │       │ type                    │
│ sessionid               │       │ patternkey              │
│ FK courseid             │       │ patterndata             │
│ state (JSON)            │       │ weight                  │
│ last_topic              │       │ lang                    │
│ FK last_ruleid          │       │ enabled                 │
│ timecreated            │       │ sortorder               │
│ timemodified           │       │ timecreated            │
└─────────────────────────┘       │ timemodified           │
                                  └─────────────────────────┘
```

### 3.2 Descripción de Tablas

#### `local_educambot_rule`

Almacena las reglas de respuesta del chatbot.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| categoryid | INT(10) | FK a categoría |
| pattern | TEXT | Patrón de pregunta principal |
| keywords | TEXT | Palabras clave adicionales (una por línea) |
| response | TEXT | Respuesta del bot (puede incluir HTML) |
| tags | TEXT | Etiquetas separadas por comas |
| enabled | INT(1) | Estado activo (0/1) |
| showoptions | INT(1) | Mostrar opciones rápidas (0/1) |
| contextaware | INT(1) | Usar datos de contexto (0/1) |
| dynamicresponse | INT(1) | Contiene placeholders (0/1) |
| requiredcontext | CHAR(50) | Contexto requerido: site, course, activity |
| roles | TEXT | Arquetipos permitidos (separados por coma) |
| courses | TEXT | IDs de cursos (separados por coma) |
| lang | CHAR(10) | Código de idioma (es, en) |
| langparent | INT(10) | FK a regla padre para traducciones |
| helpfulcount | INT(10) | Contador de feedback positivo |
| nothelpfulcount | INT(10) | Contador de feedback negativo |
| priority | INT(10) | Prioridad manual |
| needs_review | INT(1) | Marcada para revisión (0/1) |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_category`

Organiza las reglas en categorías jerárquicas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| name | CHAR(100) | Nombre de la categoría |
| description | TEXT | Descripción |
| parent | INT(10) | FK a categoría padre |
| sortorder | INT(10) | Orden de visualización |
| enabled | INT(1) | Estado activo (0/1) |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_option`

Opciones de respuesta rápida asociadas a reglas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| ruleid | INT(10) | FK a regla |
| text | CHAR(100) | Texto del botón |
| action | CHAR(255) | Texto a enviar al hacer clic |
| targetruleid | INT(10) | FK a regla destino (opcional) |
| icon | CHAR(50) | Icono Bootstrap (ej: bi-check) |
| sortorder | INT(10) | Orden de visualización |
| enabled | INT(1) | Estado activo (0/1) |

#### `local_educambot_shortcut`

Comandos de acceso rápido a funciones de Moodle.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| name | CHAR(100) | Nombre del shortcut |
| keywords | TEXT | Palabras clave (una por línea) |
| actiontype | CHAR(50) | Tipo: assignments, grades, calendar, etc. |
| description | TEXT | Descripción de la acción |
| icon | CHAR(50) | Icono Bootstrap |
| roles | TEXT | Arquetipos permitidos |
| context | CHAR(20) | Contexto: any, course, activity |
| sortorder | INT(10) | Orden |
| enabled | INT(1) | Estado activo |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_log`

Registro de todas las conversaciones.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| userid | INT(10) | FK a usuario |
| question | TEXT | Pregunta del usuario |
| response | TEXT | Respuesta del bot |
| ruleid | INT(10) | FK a regla coincidente (null si no hubo) |
| confidence | DECIMAL(10,2) | Nivel de confianza (0-1) |
| matched | INT(1) | Si hubo coincidencia (0/1) |
| timecreated | INT(10) | Timestamp |

#### `local_educambot_feedback`

Feedback de usuarios sobre respuestas.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| userid | INT(10) | FK a usuario |
| ruleid | INT(10) | FK a regla |
| helpful | INT(1) | 1=útil, 0=no útil |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_theme`

Temas visuales del widget.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| name | CHAR(50) | Nombre del tema |
| primarycolor | CHAR(7) | Color primario (#RRGGBB) |
| secondarycolor | CHAR(7) | Color secundario |
| textcolor | CHAR(7) | Color del texto |
| backgroundcolor | CHAR(7) | Color de fondo |
| usercolor | CHAR(7) | Color burbuja usuario |
| botcolor | CHAR(7) | Color burbuja bot |
| isdefault | INT(1) | Es tema por defecto (0/1) |
| widgeticontype | CHAR(20) | Tipo: default, custom, emoji, fontawesome |
| widgeticonurl | TEXT | URL o código del icono |
| mascottype | CHAR(20) | Tipo: none, clippy, robot, owl, cat, lightbulb, custom |
| mascoturl | TEXT | URL del SVG personalizado |
| mascotenabled | INT(1) | Mascota habilitada (0/1) |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_context`

Contexto de conversación para seguimiento.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| userid | INT(10) | FK a usuario |
| sessionid | CHAR(255) | Identificador de sesión |
| courseid | INT(10) | FK a curso |
| state | TEXT | Estado JSON de la conversación |
| last_topic | CHAR(100) | Último tópico detectado |
| last_ruleid | INT(10) | FK a última regla |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

#### `local_educambot_pattern`

Patrones NLP cargados desde JSON.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT(10) | Clave primaria |
| type | CHAR(50) | Tipo: intent, topic, sentiment, stopword, etc. |
| patternkey | CHAR(100) | Identificador del patrón |
| patterndata | TEXT | Datos JSON del patrón |
| weight | DECIMAL(5,2) | Peso/prioridad |
| lang | CHAR(10) | Código de idioma |
| enabled | INT(1) | Estado activo |
| sortorder | INT(10) | Orden |
| timecreated | INT(10) | Timestamp de creación |
| timemodified | INT(10) | Timestamp de modificación |

---

## 4. APIs y Servicios

### 4.1 Endpoint Principal: `service.php`

**URL:** `/local/educambot/service.php`
**Método:** POST
**Autenticación:** Requiere sesskey válido

#### Parámetros de Entrada

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| sesskey | string | Sí | Token de sesión Moodle |
| question | string | Sí | Pregunta del usuario |
| courseid | int | No | ID del curso actual (default: SITEID) |

#### Respuesta Exitosa

```json
{
    "success": true,
    "response": "Texto de la respuesta del bot...",
    "ruleid": 42,
    "confidence": 0.85,
    "type": "matched",
    "options": [
        {
            "text": "Ver más información",
            "icon": "bi-info-circle",
            "action": "más información sobre esto"
        }
    ],
    "archetype": "student",
    "context": {
        "type": "course",
        "courseid": 5,
        "incourse": true
    }
}
```

#### Tipos de Respuesta

| Tipo | Descripción |
|------|-------------|
| `matched` | Coincidencia encontrada en reglas |
| `shortcut` | Comando de acceso rápido ejecutado |
| `greeting` | Saludo detectado |
| `farewell` | Despedida detectada |
| `thanks` | Agradecimiento detectado |
| `empathetic` | Respuesta empática (usuario frustrado) |
| `follow_up` | Seguimiento de conversación |
| `no_match` | Sin coincidencia, sugerencias ofrecidas |

### 4.2 Endpoint de Feedback: `feedback.php`

**URL:** `/local/educambot/feedback.php`
**Método:** POST

#### Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| sesskey | string | Sí | Token de sesión |
| ruleid | int | Sí | ID de la regla |
| helpful | int | Sí | 1=útil, 0=no útil |

#### Respuesta

```json
{
    "success": true,
    "message": "¡Gracias por tu retroalimentación!"
}
```

### 4.3 Endpoint de Historial: `history.php`

**URL:** `/local/educambot/history.php`
**Método:** POST

#### Parámetros

| Parámetro | Tipo | Requerido | Descripción |
|-----------|------|-----------|-------------|
| sesskey | string | Sí | Token de sesión |
| action | string | Sí | `get` o `clear` |

#### Respuesta (action=get)

```json
{
    "success": true,
    "messages": [
        {
            "type": "user",
            "content": "¿Cómo veo mis tareas?",
            "timestamp": 1703520000
        },
        {
            "type": "bot",
            "content": "Para ver tus tareas pendientes...",
            "timestamp": 1703520001,
            "ruleid": 15
        }
    ]
}
```

### 4.4 Servicios Web Externos

Definidos en `db/services.php` y `classes/external.php`.

#### `local_educambot_get_popular_questions`

Obtiene las preguntas más frecuentes.

```php
// Parámetros
$limit = 5; // Máximo 10

// Respuesta
[
    ['id' => 1, 'pattern' => '¿Cómo veo mis tareas?', 'count' => 150],
    ['id' => 2, 'pattern' => '¿Cuál es mi calificación?', 'count' => 120],
    // ...
]
```

#### `local_educambot_get_similar_questions`

Busca preguntas similares por palabras clave.

```php
// Parámetros
$question = 'tareas pendientes';
$limit = 3;

// Respuesta
[
    ['id' => 1, 'pattern' => '¿Cómo veo mis tareas pendientes?'],
    ['id' => 5, 'pattern' => '¿Cuántas tareas tengo?'],
]
```

---

## 5. Clases Principales

### 5.1 `\local_educambot\bot\engine`

**Ubicación:** `classes/bot/engine.php`

Motor principal de procesamiento de preguntas. Implementa el algoritmo de matching NLP.

#### Métodos Públicos

| Método | Descripción |
|--------|-------------|
| `__construct($courseid, $userid)` | Constructor con contexto |
| `respond($question): array` | Procesa pregunta y retorna respuesta |
| `get_popular_questions($limit): array` | Obtiene preguntas populares |
| `get_similar_questions($question, $limit): array` | Busca preguntas similares |

#### Constantes de Puntaje

```php
protected const SCORE_WEIGHTS = [
    'exact_match' => 100,
    'pattern_contains' => 45,
    'question_contains' => 35,
    'transitive_match' => 35,
    'reflexive_match' => 35,
    'phrase_order_match' => 32,
    'word_overlap' => 30,
    'action_object_match' => 28,
    'multi_word_keyword' => 28,
    'keyword_match' => 25,
    'verb_conjugation_match' => 24,
    'sub_phrase_match' => 23,
    'prefix_match' => 22,
    'synonym_match' => 20,
    'ngram_match' => 18,
    'question_word_bonus' => 16,
    'levenshtein' => 15,
    'context_boost' => 12,
    'archetype_priority' => 12,
    'archetype_match' => 15,
    'topic_match' => 10,
    'feedback_boost' => 10,
    'position_bonus' => 8,
    'intent_match' => 8,
    'priority_boost' => 5,
];
```

### 5.2 `\local_educambot\bot\text_normalizer`

**Ubicación:** `classes/bot/text_normalizer.php`

Normaliza texto para mejorar coincidencias.

#### Funcionalidades

- Conversión a minúsculas
- Eliminación de acentos
- Eliminación de puntuación
- Expansión de abreviaciones
- Eliminación de stopwords
- Sustitución de sinónimos
- Cálculo de similitud (Levenshtein)

#### Métodos Públicos

| Método | Descripción |
|--------|-------------|
| `normalize($text): string` | Normaliza texto |
| `analyze($text): array` | Análisis completo del texto |
| `calculate_similarity($a, $b): float` | Similitud entre textos (0-1) |
| `contains_keywords($text, $keywords, $useSynonyms): array` | Busca keywords |
| `find_best_matches($query, $patterns, $threshold, $limit): array` | Encuentra mejores coincidencias |

### 5.3 `\local_educambot\bot\intent_detector`

**Ubicación:** `classes/bot/intent_detector.php`

Detecta la intención del usuario.

#### Intenciones Soportadas

```php
const INTENT_QUESTION = 'question';
const INTENT_GREETING = 'greeting';
const INTENT_FAREWELL = 'farewell';
const INTENT_THANKS = 'thanks';
const INTENT_COMPLAINT = 'complaint';
const INTENT_AFFIRMATION = 'affirmation';
const INTENT_NEGATION = 'negation';
const INTENT_HELP = 'help';
const INTENT_UNKNOWN = 'unknown';
```

#### Tópicos

```php
const TOPIC_ASSIGNMENTS = 'assignments';
const TOPIC_GRADES = 'grades';
const TOPIC_CALENDAR = 'calendar';
const TOPIC_COURSE = 'course';
const TOPIC_MESSAGES = 'messages';
const TOPIC_PROFILE = 'profile';
const TOPIC_NAVIGATION = 'navigation';
const TOPIC_TECHNICAL = 'technical';
const TOPIC_GENERAL = 'general';
```

#### Sentimientos

```php
const SENTIMENT_POSITIVE = 'positive';
const SENTIMENT_NEGATIVE = 'negative';
const SENTIMENT_NEUTRAL = 'neutral';
const SENTIMENT_FRUSTRATED = 'frustrated';
const SENTIMENT_CONFUSED = 'confused';
```

### 5.4 `\local_educambot\bot\shortcut_handler`

**Ubicación:** `classes/bot/shortcut_handler.php`

Maneja comandos de acceso rápido a Moodle.

#### Tipos de Acción Soportados

| Tipo | Descripción |
|------|-------------|
| `assignments` | Tareas pendientes |
| `grades` | Calificaciones del usuario |
| `calendar` | Próximos eventos |
| `messages` | Mensajes recientes |
| `teachers` | Profesores del curso |
| `course` | Información del curso |
| `progress` | Progreso académico |
| `courses` | Cursos matriculados |
| `participants` | Participantes del curso |
| `badges` | Insignias obtenidas |
| `teacher_grades` | Gestión de calificaciones (docentes) |
| `admin_users` | Gestión de usuarios (admin) |
| `admin_courses` | Gestión de cursos (admin) |
| `admin_reports` | Reportes del sitio (admin) |
| `admin_settings` | Configuración (admin) |
| `admin_plugins` | Gestión de plugins (admin) |
| `admin_security` | Seguridad (admin) |
| `admin_backup` | Copias de seguridad (admin) |

### 5.5 `\local_educambot\bot\response_builder`

**Ubicación:** `classes/bot/response_builder.php`

Construye respuestas dinámicas reemplazando placeholders.

#### Placeholders Disponibles

```php
'{{course.name}}'        // Nombre completo del curso
'{{course.shortname}}'   // Nombre corto del curso
'{{course.teacher}}'     // Profesor principal
'{{course.teachers}}'    // Lista de profesores
'{{course.startdate}}'   // Fecha de inicio
'{{course.enddate}}'     // Fecha de fin
'{{user.firstname}}'     // Nombre del usuario
'{{user.lastname}}'      // Apellido del usuario
'{{user.fullname}}'      // Nombre completo
'{{user.email}}'         // Email
'{{user.grade}}'         // Calificación actual
'{{user.gradeletter}}'   // Calificación en letra
'{{next.assignment}}'    // Próxima tarea con fecha
'{{next.assignment.name}}' // Nombre de próxima tarea
'{{next.assignment.date}}' // Fecha de próxima tarea
'{{pending.tasks}}'      // Número de tareas pendientes
'{{pending.tasks.list}}' // Lista HTML de tareas
'{{next.event}}'         // Próximo evento
'{{events.week}}'        // Eventos de la semana
'{{unread.messages}}'    // Mensajes sin leer
'{{site.name}}'          // Nombre del sitio Moodle
```

### 5.6 `\local_educambot\bot\context_handler`

**Ubicación:** `classes/bot/context_handler.php`

Obtiene datos del contexto Moodle actual.

#### Métodos Principales

| Método | Descripción |
|--------|-------------|
| `get_course_info()` | Información del curso |
| `get_user_info()` | Información del usuario |
| `get_user_archetype()` | Arquetipo del rol principal |
| `get_course_teachers()` | Lista de profesores |
| `get_user_assignments()` | Tareas pendientes |
| `get_user_grades()` | Calificaciones |
| `get_upcoming_events($days)` | Eventos próximos |
| `get_unread_message_count()` | Mensajes sin leer |
| `get_pending_tasks_count()` | Contador de tareas |
| `is_in_course()` | ¿Está en un curso? |

---

## 6. Motor de Matching (NLP)

### 6.1 Algoritmo de Puntuación

El motor evalúa cada regla contra la pregunta del usuario calculando un puntaje:

```
PUNTAJE_TOTAL = Σ (peso_criterio × factor_coincidencia)
```

#### Orden de Evaluación

1. **Coincidencia exacta** (100 pts) - Pregunta = Patrón normalizado
2. **Contención de patrón** (45 pts) - Patrón está dentro de la pregunta
3. **Contención inversa** (35 pts) - Pregunta está dentro del patrón
4. **Acción transitiva/reflexiva** (35 pts) - Tipo de acción coincide
5. **Orden de frase** (32 pts) - Palabras en orden similar
6. **Solapamiento de palabras** (30 pts) - Jaccard similarity
7. **Keywords multi-palabra** (28 pts) - Frases clave completas
8. **Keywords simples** (25 pts) - Palabras clave individuales
9. **Conjugación verbal** (24 pts) - Coincidencia de verbos
10. **Sub-frases** (23 pts) - Partes de frases largas
11. **Prefijos/stems** (22 pts) - Raíces de palabras
12. **Sinónimos** (20 pts) - Palabras equivalentes
13. **N-gramas** (18 pts) - Secuencias de caracteres
14. **Palabras interrogativas** (16 pts) - cómo, qué, cuándo, etc.
15. **Levenshtein** (15 pts) - Tolerancia a errores
16. **Boost de arquetipo** (15 pts) - Regla para el rol del usuario
17. **Boost de contexto** (12 pts) - Tópico de conversación
18. **Boost de feedback** (10 pts) - Reglas bien valoradas
19. **Boost de tópico** (10 pts) - Tags coincidentes
20. **Boost de intención** (8 pts) - Intención coincidente
21. **Posición** (8 pts) - Coincidencia temprana

### 6.2 Umbral de Confianza

```php
protected const MIN_CONFIDENCE = 0.25;  // Mínimo para responder
protected const HIGH_CONFIDENCE = 0.7;  // Alta confianza
```

- Si `score >= MIN_CONFIDENCE * 100` → Respuesta normal
- Si `score < MIN_CONFIDENCE * 100` → Respuesta de "no entendí" con sugerencias

### 6.3 Detección de Acciones Transitivas/Reflexivas

El motor detecta si la pregunta es sobre una acción que el usuario hace sobre sí mismo (reflexiva) o sobre otros (transitiva):

**Reflexivas:** "inscribirme", "mi perfil", "mis tareas"
**Transitivas:** "inscribir estudiantes", "ver usuarios", "crear curso"

Esto evita confusiones entre "¿Cómo me inscribo?" y "¿Cómo inscribo estudiantes?".

---

## 7. Hooks y Eventos

### 7.1 Hooks de Moodle

Definidos en `db/hooks.php`:

```php
$callbacks = [
    [
        'hook' => \core\hook\output\before_footer_html_generation::class,
        'callback' => \local_educambot\hook_callbacks::class . '::before_footer_html_generation',
    ],
];
```

Este hook inyecta el widget del chat antes del footer en todas las páginas.

### 7.2 Callback Legacy

En `lib.php` se define el callback tradicional:

```php
function local_educambot_before_footer() {
    // Inyecta el widget si está habilitado
    // y el usuario tiene permisos
}
```

### 7.3 Archivos Servidos

```php
function local_educambot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options) {
    // Sirve archivos de widgeticon y mascot
}
```

Áreas de archivo soportadas:
- `widgeticon` - Iconos personalizados del widget
- `mascot` - SVGs de mascotas personalizadas

---

## 8. Tareas Programadas

Definidas en `db/tasks.php`:

### 8.1 Limpieza de Historial

```php
[
    'classname' => 'local_educambot\task\cleanup_history',
    'blocking' => 0,
    'minute' => '0',
    'hour' => '3',      // 3:00 AM
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
]
```

Elimina registros de `local_educambot_log` y `local_educambot_context` más antiguos que el período de retención configurado.

### 8.2 Análisis de Feedback

```php
[
    'classname' => 'local_educambot\task\analyze_feedback',
    'blocking' => 0,
    'minute' => '30',
    'hour' => '4',      // 4:30 AM
    'day' => '*',
    'month' => '*',
    'dayofweek' => '*',
]
```

Analiza el feedback de usuarios y marca reglas que necesitan revisión si tienen ratio negativo alto.

---

## 9. Sistema de Plantillas

### 9.1 Template Mustache: `widget.mustache`

Variables de contexto requeridas:

```php
$data = [
    'botname' => 'EducamBot',
    'widgetlabel' => '¿Necesitas ayuda?',
    'primarycolor' => '#0f6fc5',
    'secondarycolor' => '#6c757d',
    'textcolor' => '#333333',
    'backgroundcolor' => '#ffffff',
    'usercolor' => '#e3f2fd',
    'botcolor' => '#f5f5f5',
    'serviceurl' => '/local/educambot/service.php',
    'historyurl' => '/local/educambot/history.php',
    'shortcutsurl' => '/local/educambot/shortcuts_ajax.php',
    'feedbackurl' => '/local/educambot/feedback.php',
    'sesskey' => sesskey(),
    'courseid' => $courseid,
    'userid' => $USER->id,
    'userfirstname' => $USER->firstname,
    'userlastname' => $USER->lastname,
    'userfullname' => fullname($USER),
    'userrolearchetype' => 'student',
    'greetingmessage' => '¡Hola! ¿En qué puedo ayudarte?',
    'inactivitytimeout' => 600000,
    'enablehistory' => true,
    'soundenabled' => true,
    'widgeticon' => [...],  // Datos del icono
    'mascot' => [...],      // Datos de la mascota
];
```

### 9.2 Módulo AMD: `widget.js`

Funciones principales del módulo JavaScript:

| Función | Descripción |
|---------|-------------|
| `init()` | Inicializa el widget |
| `toggleChat()` | Abre/cierra el chat |
| `sendMessage()` | Envía mensaje al servidor |
| `displayMessage()` | Renderiza mensaje en el DOM |
| `loadHistory()` | Carga historial previo |
| `loadShortcuts()` | Carga accesos rápidos |
| `exportChat()` | Exporta conversación a .txt |
| `clearChat()` | Limpia la conversación |
| `submitFeedback()` | Envía feedback de respuesta |
| `playNotificationSound()` | Reproduce sonido de notificación |
| `startInactivityTimer()` | Inicia temporizador de inactividad |

---

## 10. Extensibilidad

### 10.1 Agregar Nuevos Tipos de Shortcut

1. Agregar tipo en `shortcut_handler.php`:

```php
case 'mi_nuevo_tipo':
    $response = $this->get_mi_nuevo_tipo_response();
    break;
```

2. Implementar el método:

```php
private function get_mi_nuevo_tipo_response() {
    // Lógica para obtener datos
    return $response;
}
```

3. Agregar al array de tipos:

```php
public static function get_action_types() {
    return [
        // ...existentes...
        'mi_nuevo_tipo' => get_string('actiontype_mi_nuevo_tipo', 'local_educambot'),
    ];
}
```

4. Agregar cadenas de idioma.

### 10.2 Agregar Nuevos Placeholders

En `response_builder.php`:

```php
public function get_placeholders() {
    return [
        // ...existentes...
        '{{mi.placeholder}}' => $this->get_mi_placeholder_value(),
    ];
}

private function get_mi_placeholder_value() {
    // Lógica para obtener el valor
    return $valor;
}
```

### 10.3 Agregar Nuevas Mascotas

1. Crear SVG en `pix/mascots/mi_mascota.svg`

2. Agregar opción en `theme_form.php`:

```php
$mascotoptions = [
    // ...existentes...
    'mi_mascota' => get_string('mascot_mi_mascota', 'local_educambot'),
];
```

3. Agregar cadenas de idioma.

### 10.4 Personalizar el Algoritmo de Matching

En `engine.php`, modificar `SCORE_WEIGHTS` o agregar nuevos criterios en `calculate_match_score()`:

```php
// Agregar nuevo criterio
$miCriterio = $this->evaluar_mi_criterio($question, $pattern);
if ($miCriterio > 0) {
    $score += self::SCORE_WEIGHTS['mi_criterio'] * $miCriterio;
}
```

---

## 11. Notas para Desarrolladores

### 11.1 Convenciones de Código

- Seguir estándares de codificación de Moodle
- Documentar métodos con PHPDoc
- Usar `get_string()` para todas las cadenas visibles
- Validar parámetros con `required_param()` y `optional_param()`
- Usar `$DB` para consultas a base de datos
- Verificar permisos con `require_capability()`

### 11.2 Depuración

Habilitar modo debug en Moodle:

```php
$CFG->debug = E_ALL;
$CFG->debugdisplay = 1;
```

Usar `debugging()` para mensajes de desarrollo:

```php
debugging('Mi mensaje de debug', DEBUG_DEVELOPER);
```

### 11.3 Compilar JavaScript

```bash
cd /path/to/moodle
grunt amd --force
# o específicamente:
grunt amd --src=local/educambot/amd/src
```

### 11.4 Testing

Ejecutar tests unitarios:

```bash
vendor/bin/phpunit --testsuite local_educambot_testsuite
```

### 11.5 Actualización del Plugin

Al actualizar la versión:

1. Incrementar `$plugin->version` en `version.php`
2. Agregar cambios de BD en `db/upgrade.php`
3. Documentar cambios en `CHANGELOG.md`
4. Probar en entorno de staging

### 11.6 Exportar/Importar Base de Conocimientos

**Exportar:**
```bash
# Desde la interfaz: EducamBot → Importar/Exportar → Exportar
# Genera archivo JSON con todas las reglas, categorías y opciones
```

**Importar:**
```bash
# Subir archivo JSON
# Opción para limpiar datos existentes antes de importar
```

### 11.7 Compatibilidad

- **Moodle 4.0+**: Uso de hooks modernos + callbacks legacy
- **PHP 7.4+**: Sin funciones deprecated
- **APIs externas**: Compatibilidad dual Moodle 4.2+ (namespaced) y <4.2 (legacy)
- **Base de datos**: XMLDB compatible con MySQL, PostgreSQL, MariaDB, Oracle

---

## Información de Contacto

**Desarrollador:** Alonso Arias
**Email:** soporte@ingeweb.co
**Empresa:** Ingeweb
**Web:** https://ingeweb.co
**Ventas:** ventas@ingeweb.co

---

*Documentación generada el 26 de diciembre de 2025*
