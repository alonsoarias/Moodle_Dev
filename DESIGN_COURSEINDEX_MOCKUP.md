# DISEÑO: COURSEINDEX CON PROGRESO SEGÚN MOCKUP

**Fecha:** 2025-10-27
**Fase:** 5 - Diseño de Implementación
**Base:** Análisis de compecer, remui y format_remuiformat

---

## TABLA DE CONTENIDOS

1. [Análisis del Mockup](#1-análisis-del-mockup)
2. [Arquitectura de Componentes](#2-arquitectura-de-componentes)
3. [Diseño de Templates Mustache](#3-diseño-de-templates-mustache)
4. [Diseño de Clases PHP](#4-diseño-de-clases-php)
5. [Diseño de Módulos JavaScript](#5-diseño-de-módulos-javascript)
6. [Diseño de Estilos SCSS/CSS](#6-diseño-de-estilos-scsscss)
7. [Estructura de Datos](#7-estructura-de-datos)
8. [Mapa de Archivos](#8-mapa-de-archivos)

---

## 1. ANÁLISIS DEL MOCKUP

### 1.1 Descomposición Visual

```
┌─────────────────────────────────────────────────┐
│  ┌───────────────────────────────────────────┐  │
│  │ [A] HEADER PROGRESO GLOBAL                │  │
│  │     PROGRESO DEL CURSO 65%                │  │
│  │     ▓▓▓▓▓▓▓▓▓▓▓▓▓░░░░░░░░░               │  │
│  │     13 de 20 actividades (65%)            │  │
│  └───────────────────────────────────────────┘  │
│                                                  │
│  [B] SECCIÓN EXPANDIDA                          │
│  ▼ Introducción                           60%   │
│     ○ Bienvenida               [C: ESTADO]      │
│     ✓ Objetivos                                 │
│     ◐ Conoce a tus compañeros                   │
│  ─────────────────────────────────────────────  │
│                                                  │
│  [D] SECCIÓN COLAPSADA                          │
│  ▶ Unidad 1: Fundamentos                   0%   │
│  ─────────────────────────────────────────────  │
│                                                  │
│  [B] SECCIÓN EXPANDIDA                          │
│  ▼ Unidad 2: Avanzado                      33%  │
│     ✓ Lectura: Conceptos avanzados             │
│     ✓ Video: Tutorial                          │
│     ○ Tarea: Ejercicio práctico                │
│  ─────────────────────────────────────────────  │
└─────────────────────────────────────────────────┘
```

### 1.2 Componentes Identificados

| ID | Componente | Descripción | Tipo |
|----|-----------|-------------|------|
| **A** | Header Progreso Global | Título + porcentaje + barra + detalles | Container |
| **B** | Sección Expandida | Toggle + título + % + lista actividades | Collapsible |
| **C** | Estado Actividad | Iconos: ○ (no iniciada), ◐ (en progreso), ✓ (completada) | Visual Indicator |
| **D** | Sección Colapsada | Toggle + título + % | Collapsed State |

### 1.3 Especificaciones Visuales

#### Header Progreso Global (A)

- **Título:** "PROGRESO DEL CURSO" (mayúsculas, bold, pequeño)
- **Porcentaje:** Grande (1.5-2rem), bold, color oscuro
- **Barra:** Horizontal, altura 8-12px, esquinas redondeadas
  - Relleno: Verde (>70%), Amarillo (30-70%), Rojo (<30%)
  - Animación: Shimmer effect
- **Detalles:** "X de Y actividades (Z%)" - Texto pequeño (0.75rem)
- **Fondo:** Degradado sutil blanco/gris claro
- **Borde:** Izquierda con color de acento (4px, azul oscuro)
- **Espaciado:** Padding generoso (1rem), margen inferior (1rem)

#### Sección Expandida (B)

- **Header:**
  - Fondo: Azul (#365ba3)
  - Color texto: Blanco
  - Padding: 0.75rem
  - Display: Flex (align items center)

- **Elementos del header:**
  1. Toggle icon (▼): Izquierda, 16px, rotación 0°
  2. Título: Flex-grow 1, bold, font-size 0.95rem
  3. Porcentaje: Derecha, bold, font-size 0.9rem

- **Contenido:**
  - Fondo: Blanco/gris muy claro
  - Lista de actividades sin bullets
  - Padding: 0.5rem 0

#### Estado de Actividad (C)

**Iconos y significado:**

| Estado | Icono | HTML Entity | Color | Descripción |
|--------|-------|-------------|-------|-------------|
| No iniciada | ○ | `&#9675;` | Gris (#999) | Actividad sin empezar |
| En progreso | ◐ | `&#9680;` | Naranja (#ff9800) | Actividad iniciada, no completada |
| Completada | ✓ | `&#10003;` | Verde (#28a745) | Actividad completada |
| Sin tracking | - | - | - | No mostrar icono |

**Estilo de actividad:**
- Padding: 0.5rem 1rem 0.5rem 2.5rem
- Position relative (para icono absoluto)
- Hover: Background rosa suave, borde izquierdo amarillo

#### Sección Colapsada (D)

- **Toggle icon (▶):** Rotación -90° (apunta derecha)
- **Resto:** Igual que header expandida

### 1.4 Comportamiento Interactivo

| Acción | Efecto | Duración |
|--------|--------|----------|
| Hover sobre sección | Sombra más pronunciada | 0.2s |
| Click en header sección | Expandir/colapsar contenido | 0.3s ease |
| Click en actividad | Navegar a actividad | Instantáneo |
| Completar actividad | Actualizar barra + icono sin reload | 0.6s |
| Cargar página | Fade-in de header progreso | 0.4s |

### 1.5 Estados por Defecto

- **Secciones:** Colapsadas por defecto
- **Sección actual:** Expandida automáticamente
- **Progreso:** Visible si hay completion habilitado
- **Actividades:** Mostrar todas las visibles

---

## 2. ARQUITECTURA DE COMPONENTES

### 2.1 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────┐
│                    COURSEINDEX DRAWER                    │
│                                                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │            PROGRESS HEADER COMPONENT               │ │
│  │  - Título                                          │ │
│  │  - Porcentaje                                      │ │
│  │  - Barra de progreso                               │ │
│  │  - Detalles (X/Y actividades)                      │ │
│  └────────────────────────────────────────────────────┘ │
│                                                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │              SECTIONS LIST COMPONENT               │ │
│  │                                                    │ │
│  │  ┌──────────────────────────────────────────────┐ │ │
│  │  │        SECTION COMPONENT (Repetido)          │ │ │
│  │  │                                              │ │ │
│  │  │  ┌────────────────────────────────────────┐ │ │ │
│  │  │  │      SECTION HEADER COMPONENT          │ │ │ │
│  │  │  │  - Toggle icon                         │ │ │ │
│  │  │  │  - Título                              │ │ │ │
│  │  │  │  - Badge de porcentaje                 │ │ │ │
│  │  │  └────────────────────────────────────────┘ │ │ │
│  │  │                                              │ │ │
│  │  │  ┌────────────────────────────────────────┐ │ │ │
│  │  │  │    SECTION CONTENT (Collapsible)       │ │ │ │
│  │  │  │                                        │ │ │ │
│  │  │  │  ┌──────────────────────────────────┐ │ │ │ │
│  │  │  │  │  ACTIVITIES LIST                 │ │ │ │ │
│  │  │  │  │                                  │ │ │ │ │
│  │  │  │  │  ┌────────────────────────────┐ │ │ │ │ │
│  │  │  │  │  │  ACTIVITY ITEM (Repetido)  │ │ │ │ │ │
│  │  │  │  │  │  - Icono de estado         │ │ │ │ │ │
│  │  │  │  │  │  - Nombre de actividad     │ │ │ │ │ │
│  │  │  │  │  └────────────────────────────┘ │ │ │ │ │
│  │  │  │  └──────────────────────────────────┘ │ │ │ │
│  │  │  └────────────────────────────────────────┘ │ │ │
│  │  └──────────────────────────────────────────────┘ │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Jerarquía de Templates

```
drawer.mustache (Principal)
├── progress_header.mustache (Nuevo - Componente reutilizable)
└── sections_list (Iteración)
    └── section.mustache (Modificado)
        ├── section_header (Parte del template)
        └── section_content (Parte del template)
            └── activities_list (Iteración)
                └── cm.mustache (Modificado)
                    └── activity_state_icon (Parte del template)
```

### 2.3 Flujo de Datos

```
PHP Backend
    ↓
[Servicio Web] get_course_progress_extended()
    ├── Obtener progreso global
    ├── Para cada sección:
    │   ├── Calcular progreso de sección
    │   └── Para cada actividad:
    │       └── Determinar estado (○, ◐, ✓)
    ↓
[JSON Response]
    ↓
JavaScript Frontend
    ↓
[Actualizar DOM]
    ├── Header progreso global
    ├── Badges de secciones
    └── Iconos de actividades
    ↓
[Mustache Templates]
    ↓
[HTML Renderizado]
```

---

## 3. DISEÑO DE TEMPLATES MUSTACHE

### 3.1 drawer.mustache (Template Principal)

**Ubicación:** `/theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache`

**Cambios respecto al actual:**
- ✅ Mantener estructura base
- ✅ Mantener header de progreso global (mejorar diseño)
- ➕ Pasar datos de progreso por sección
- ➕ Renderizar secciones con progreso

**Template completo:**

```mustache
{{!
    @template core_courseformat/local/courseindex/drawer

    Renderiza el drawer del índice de curso con barra de progreso.

    Context:
    {
        "courseid": 5,
        "coursename": "Introducción a Moodle",
        "courseprogress": {
            "percentage": 65,
            "completedcount": 13,
            "activitycount": 20,
            "hascompletion": true,
            "progresscolor": "bg-success",
            "progresstext": "13 de 20 actividades (65%)",
            "activitylist": ["5 Quiz", "3 Forum", "2 Assignment"]
        },
        "sections": [
            {
                "id": 23,
                "number": 1,
                "title": "Introducción",
                "sectionurl": "/course/view.php?id=5&section=1",
                "current": true,
                "visible": true,
                "indexcollapsed": false,
                "progressinfo": {
                    "percentage": 60,
                    "completed": 3,
                    "total": 5,
                    "progresstext": "3/5"
                },
                "cms": [...]
            }
        ]
    }
}}

<nav id="courseindex" class="course-index-drawer" data-courseid="{{courseid}}">

    {{! ========== HEADER DEL DRAWER ========== }}
    <div class="drawer-header mb-3">
        <h5 class="drawer-title">
            {{#str}}courseindex, core{{/str}}
        </h5>
    </div>

    {{! ========== PROGRESO GLOBAL DEL CURSO ========== }}
    {{#courseprogress}}
    {{#hascompletion}}
    <div id="courseindex-progress" class="courseindex-progress-container">

        {{! Header del progreso }}
        <div class="progress-header">
            <h6 class="progress-title">
                {{#str}}courseprogress, theme_compecer{{/str}}
            </h6>
            <span class="progress-percentage">{{percentage}}%</span>
        </div>

        {{! Detalles textuales }}
        <div class="progress-details mb-2">
            {{progresstext}}
        </div>

        {{! Barra de progreso visual }}
        <div class="progress progress-rounded">
            <div class="progress-bar {{progresscolor}}"
                 role="progressbar"
                 style="width: {{percentage}}%;"
                 aria-valuenow="{{percentage}}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-label="{{progresstext}}">
                <span class="sr-only">{{progresstext}}</span>
            </div>
        </div>

        {{! Lista de actividades (opcional - colapsable) }}
        {{#showactivitylist}}
        <details class="progress-activities-details">
            <summary class="progress-activities-summary">
                {{#str}}viewdetails, theme_compecer{{/str}}
            </summary>
            <ul class="progress-activity-list">
                {{#activitylist}}
                <li>{{.}}</li>
                {{/activitylist}}
            </ul>
        </details>
        {{/showactivitylist}}

    </div>
    {{/hascompletion}}
    {{/courseprogress}}

    {{! ========== LISTA DE SECCIONES ========== }}
    <div id="courseindex-content" class="courseindex-sections">
        {{#sections}}
        {{> core_courseformat/local/courseindex/section}}
        {{/sections}}
    </div>

</nav>

{{! ========== INICIALIZACIÓN JAVASCRIPT ========== }}
{{#js}}
require([
    'core_courseformat/local/courseindex/drawer',
    'theme_compecer/courseindex_progress'
], function(component, progressComponent) {
    component.init('courseindex');
    progressComponent.init({{courseid}});
});
{{/js}}
```

### 3.2 section.mustache (Template de Sección)

**Ubicación:** `/theme/compecer/templates/core_courseformat/local/courseindex/section.mustache`

**Cambios respecto al actual:**
- ➕ Badge de porcentaje en header
- ➕ Datos de progreso de sección
- ✅ Mantener funcionalidad de colapsar/expandir

**Template completo:**

```mustache
{{!
    @template core_courseformat/local/courseindex/section

    Renderiza una sección del curso con progreso.

    Context:
    {
        "id": 23,
        "number": 1,
        "title": "Introducción",
        "sectionurl": "/course/view.php?id=5&section=1",
        "current": true,
        "visible": true,
        "indexcollapsed": false,
        "hasrestrictions": false,
        "progressinfo": {
            "percentage": 60,
            "completed": 3,
            "total": 5,
            "progresstext": "3/5",
            "progresscolor": "bg-warning"
        },
        "cms": [...]
    }
}}

<div class="course-index-section w-100
     {{#current}}current{{/current}}
     {{^visible}}dimmed{{/visible}}"
     id="course-index-section-{{id}}"
     data-for="section"
     data-id="{{id}}"
     data-number="{{number}}"
     role="treeitem"
     aria-owns="course-index-collapse{{number}}">

    {{! ========== HEADER DE SECCIÓN ========== }}
    <div class="course-index-header
         {{^visible}}dimmed{{/visible}}
         {{#hasrestrictions}}restrictions{{/hasrestrictions}}"
         id="course-index-section-header-{{number}}"
         data-for="section_item">

        {{! Toggle icon }}
        <button class="course-index-toggle btn-unstyled"
                data-toggle="collapse"
                data-target="#course-index-collapse{{number}}"
                aria-expanded="{{^indexcollapsed}}true{{/indexcollapsed}}{{#indexcollapsed}}false{{/indexcollapsed}}"
                aria-controls="course-index-collapse{{number}}"
                {{#indexcollapsed}}collapsed{{/indexcollapsed}}>
            <i class="fa fa-chevron-right toggle-icon"
               aria-hidden="true"></i>
            <span class="sr-only">
                {{#str}}togglesection, core{{/str}}
            </span>
        </button>

        {{! Enlace a la sección }}
        <a href="{{{sectionurl}}}"
           class="course-index-link"
           data-action="navigatetosection"
           data-for="section_title"
           title="{{{title}}}">
            {{{title}}}
        </a>

        {{! Badge de porcentaje }}
        {{#progressinfo}}
        <span class="section-progress-badge"
              data-percentage="{{percentage}}"
              aria-label="{{percentage}}% completado">
            {{percentage}}%
        </span>
        {{/progressinfo}}

    </div>

    {{! ========== CONTENIDO COLAPSABLE DE SECCIÓN ========== }}
    <div id="course-index-collapse{{number}}"
         class="course-index-content collapse {{^indexcollapsed}}show{{/indexcollapsed}}"
         aria-labelledby="course-index-section-header-{{number}}"
         role="group">

        {{! Mini barra de progreso (opcional) }}
        {{#progressinfo}}
        {{#showminibar}}
        <div class="section-progress-minibar">
            <div class="progress progress-sm">
                <div class="progress-bar {{progresscolor}}"
                     style="width: {{percentage}}%;"
                     role="progressbar">
                </div>
            </div>
            <small class="progress-minitext">{{progresstext}} completadas</small>
        </div>
        {{/showminibar}}
        {{/progressinfo}}

        {{! Lista de actividades }}
        <ul class="course-index-section-content list-unstyled"
            data-for="cmlist"
            data-id="{{id}}"
            role="group">
            {{#cms}}
            {{> core_courseformat/local/courseindex/cm}}
            {{/cms}}
        </ul>

    </div>

</div>

{{! ========== INICIALIZACIÓN JAVASCRIPT ========== }}
{{#js}}
require(['core_courseformat/local/courseindex/section'], function(component) {
    component.init('course-index-section-{{id}}');
});
{{/js}}
```

### 3.3 cm.mustache (Template de Actividad)

**Ubicación:** `/theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache`

**Cambios respecto al actual:**
- ➕ Icono de estado de completado (○, ◐, ✓)
- ➕ Clases CSS según estado
- ✅ Mantener funcionalidad de navegación

**Template completo:**

```mustache
{{!
    @template core_courseformat/local/courseindex/cm

    Renderiza una actividad con indicador de estado.

    Context:
    {
        "id": 12,
        "name": "Bienvenida al curso",
        "url": "/mod/page/view.php?id=12",
        "modname": "page",
        "visible": true,
        "uservisible": true,
        "isactive": true,
        "hascmrestrictions": false,
        "completionstate": "completed",
        "completionicon": "✓",
        "completioncolor": "text-success",
        "completionlabel": "Completada"
    }
}}

<li class="course-index-item
    {{#isactive}}active{{/isactive}}
    {{^uservisible}}dimmed{{/uservisible}}
    {{#hascmrestrictions}}restrictions{{/hascmrestrictions}}
    completion-{{completionstate}}"
    id="course-index-cm-{{id}}"
    data-for="cm"
    data-id="{{id}}"
    data-completionstate="{{completionstate}}"
    role="treeitem">

    {{^hasdelegatedsection}}
    <div class="activity-item-content">

        {{! Icono de estado de completado }}
        {{#completionstate}}
        <span class="activity-completion-icon {{completioncolor}}"
              aria-label="{{completionlabel}}"
              title="{{completionlabel}}">
            {{completionicon}}
        </span>
        {{/completionstate}}

        {{! Enlace a la actividad }}
        {{#uservisible}}
        <a class="course-index-link activity-link"
           {{#url}}href="{{{url}}}"{{/url}}
           {{^url}}href="#cm{{id}}" data-anchor="true"{{/url}}
           data-for="cm_name"
           title="{{{name}}}">
            {{{name}}}
        </a>
        {{/uservisible}}

        {{! Si no es visible para el usuario }}
        {{^uservisible}}
        <span class="activity-link-disabled">
            {{{name}}}
        </span>
        {{/uservisible}}

    </div>
    {{/hasdelegatedsection}}

    {{! Sección delegada (caso especial) }}
    {{#hasdelegatedsection}}
    {{#sectioninfo}}
    {{> core_courseformat/local/courseindex/section}}
    {{/sectioninfo}}
    {{/hasdelegatedsection}}

</li>

{{! ========== INICIALIZACIÓN JAVASCRIPT ========== }}
{{#js}}
require(['core_courseformat/local/courseindex/cm'], function(component) {
    component.init('course-index-cm-{{id}}');
});
{{/js}}
```

### 3.4 Estructura de Contexto Completa

```json
{
  "courseid": 5,
  "coursename": "Introducción a Moodle",

  "courseprogress": {
    "percentage": 65,
    "completedcount": 13,
    "activitycount": 20,
    "hascompletion": true,
    "progresscolor": "bg-success",
    "progresstext": "13 de 20 actividades (65%)",
    "showactivitylist": true,
    "activitylist": [
      "5 Quiz",
      "3 Forum",
      "2 Assignment"
    ]
  },

  "sections": [
    {
      "id": 23,
      "number": 1,
      "title": "Introducción",
      "sectionurl": "/course/view.php?id=5&section=1",
      "current": true,
      "visible": true,
      "indexcollapsed": false,
      "hasrestrictions": false,

      "progressinfo": {
        "percentage": 60,
        "completed": 3,
        "total": 5,
        "progresstext": "3/5",
        "progresscolor": "bg-warning",
        "showminibar": false
      },

      "cms": [
        {
          "id": 12,
          "name": "Bienvenida al curso",
          "url": "/mod/page/view.php?id=12",
          "modname": "page",
          "visible": true,
          "uservisible": true,
          "isactive": false,
          "hascmrestrictions": false,

          "completionstate": "notstarted",
          "completionicon": "○",
          "completioncolor": "text-muted",
          "completionlabel": "No iniciada"
        },
        {
          "id": 13,
          "name": "Objetivos del curso",
          "url": "/mod/page/view.php?id=13",
          "modname": "page",
          "visible": true,
          "uservisible": true,
          "isactive": true,
          "hascmrestrictions": false,

          "completionstate": "completed",
          "completionicon": "✓",
          "completioncolor": "text-success",
          "completionlabel": "Completada"
        },
        {
          "id": 14,
          "name": "Conoce a tus compañeros",
          "url": "/mod/forum/view.php?id=14",
          "modname": "forum",
          "visible": true,
          "uservisible": true,
          "isactive": false,
          "hascmrestrictions": false,

          "completionstate": "inprogress",
          "completionicon": "◐",
          "completioncolor": "text-warning",
          "completionlabel": "En progreso"
        }
      ]
    }
  ]
}
```

---

## 4. DISEÑO DE CLASES PHP

### 4.1 Servicio Web Extendido

**Archivo:** `/theme/compecer/classes/external/get_course_progress.php`

**Modificaciones necesarias:**

```php
<?php
namespace theme_compecer\external;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use completion_info;
use core_completion\progress;
use context_course;

defined('MOODLE_INTERNAL') || die();

class get_course_progress extends external_api {

    /**
     * Parámetros de entrada
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID')
        ]);
    }

    /**
     * Ejecutar servicio
     *
     * @param int $courseid ID del curso
     * @return array Datos de progreso del curso y secciones
     */
    public static function execute($courseid) {
        global $USER;

        // 1. Validar parámetros
        $params = self::validate_parameters(
            self::execute_parameters(),
            ['courseid' => $courseid]
        );

        // 2. Validar contexto y permisos
        $context = context_course::instance($courseid);
        self::validate_context($context);
        require_capability('moodle/course:view', $context);

        // 3. Obtener curso
        $course = get_course($courseid);

        // 4. Obtener progreso global del curso
        $courseprogress = self::get_course_progress_data($course);

        // 5. Obtener progreso por sección
        $sections = self::get_sections_progress_data($course);

        // 6. Retornar datos completos
        return [
            'courseid' => $courseid,
            'coursename' => $course->fullname,
            'courseprogress' => $courseprogress,
            'sections' => $sections
        ];
    }

    /**
     * Obtener datos de progreso global del curso
     *
     * @param stdClass $course Objeto del curso
     * @return array Datos de progreso
     */
    private static function get_course_progress_data($course) {
        global $USER;

        // Inicializar completion info
        $completion = new completion_info($course);

        // Valores por defecto
        $data = [
            'percentage' => 0,
            'completedcount' => 0,
            'activitycount' => 0,
            'hascompletion' => false,
            'progresscolor' => 'bg-danger',
            'progresstext' => '',
            'showactivitylist' => true,
            'activitylist' => []
        ];

        // Verificar si completion está habilitado
        if (!$completion->is_enabled()) {
            return $data;
        }

        $data['hascompletion'] = true;

        // Obtener porcentaje usando API de Moodle
        $percentage = progress::get_course_progress_percentage($course, $USER->id);
        $data['percentage'] = !is_null($percentage) ? floor($percentage) : 0;

        // Obtener información detallada
        $modinfo = get_fast_modinfo($course);
        $activitycount = 0;
        $completedcount = 0;
        $activitytypes = [];

        foreach ($modinfo->get_cms() as $cm) {
            // Solo contar actividades visibles en página de curso
            if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                continue;
            }

            // Excluir labels
            if ($cm->modname == 'label') {
                continue;
            }

            // Verificar tracking de completion
            if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
                continue;
            }

            $activitycount++;

            // Contar por tipo
            $modname = get_string('modulename', $cm->modname);
            if (!isset($activitytypes[$modname])) {
                $activitytypes[$modname] = 0;
            }
            $activitytypes[$modname]++;

            // Verificar si está completada
            $cmdata = $completion->get_data($cm, false, $USER->id);
            if ($cmdata->completionstate == COMPLETION_COMPLETE ||
                $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
                $completedcount++;
            }
        }

        // Actualizar contadores
        $data['activitycount'] = $activitycount;
        $data['completedcount'] = $completedcount;

        // Texto de progreso
        $data['progresstext'] = sprintf(
            '%d de %d actividades (%d%%)',
            $completedcount,
            $activitycount,
            $data['percentage']
        );

        // Color según porcentaje
        $data['progresscolor'] = self::get_progress_color($data['percentage']);

        // Lista de actividades por tipo
        foreach ($activitytypes as $type => $count) {
            $data['activitylist'][] = "$count $type";
        }

        return $data;
    }

    /**
     * Obtener datos de progreso por sección
     *
     * @param stdClass $course Objeto del curso
     * @return array Array de secciones con progreso
     */
    private static function get_sections_progress_data($course) {
        global $USER;

        $completion = new completion_info($course);
        $modinfo = get_fast_modinfo($course);
        $sections = $modinfo->get_section_info_all();

        $result = [];

        foreach ($sections as $section) {
            // Información básica de sección
            $sectiondata = [
                'id' => $section->id,
                'number' => $section->section,
                'title' => get_section_name($course, $section),
                'sectionurl' => course_get_url($course, $section->section)->out(),
                'current' => $section->id == $modinfo->get_section_info(0)->id, // Simplificado
                'visible' => $section->visible,
                'indexcollapsed' => !$section->uservisible, // Por defecto
                'hasrestrictions' => !empty($section->availability)
            ];

            // Calcular progreso de sección
            $progressinfo = self::get_section_module_info($course, $section, $completion);
            $sectiondata['progressinfo'] = $progressinfo;

            // Obtener actividades de la sección
            $sectiondata['cms'] = self::get_section_activities($course, $section, $completion, $modinfo);

            $result[] = $sectiondata;
        }

        return $result;
    }

    /**
     * Calcular progreso de una sección
     *
     * Basado en format_remuiformat/classes/course_format_data_common_trait.php
     *
     * @param stdClass $course Objeto del curso
     * @param section_info $section Información de la sección
     * @param completion_info $completion Información de completion
     * @return array Datos de progreso de sección
     */
    private static function get_section_module_info($course, $section, $completion) {
        global $USER;

        $total = 0;
        $completed = 0;
        $cancomplete = isloggedin() && !isguestuser();

        $modinfo = get_fast_modinfo($course);

        // Iterar por módulos de la sección
        if (!empty($modinfo->sections[$section->section])) {
            foreach ($modinfo->sections[$section->section] as $cmid) {
                $cm = $modinfo->cms[$cmid];

                // Verificar visibilidad
                if (!$cm->uservisible || !$cm->is_visible_on_course_page()) {
                    continue;
                }

                // Excluir labels
                if ($cm->modname == 'label') {
                    continue;
                }

                // Verificar tracking
                if ($cancomplete &&
                    $completion->is_enabled($cm) != COMPLETION_TRACKING_NONE) {

                    $total++;

                    $cmdata = $completion->get_data($cm, false, $USER->id);
                    if ($cmdata->completionstate == COMPLETION_COMPLETE ||
                        $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
                        $completed++;
                    }
                }
            }
        }

        // Calcular porcentaje
        $percentage = ($total > 0) ? round(($completed / $total) * 100, 0) : 0;

        return [
            'percentage' => $percentage,
            'completed' => $completed,
            'total' => $total,
            'progresstext' => "$completed/$total",
            'progresscolor' => self::get_progress_color($percentage),
            'showminibar' => false // Opcional, configurable
        ];
    }

    /**
     * Obtener actividades de una sección con estado de completion
     *
     * @param stdClass $course Objeto del curso
     * @param section_info $section Información de la sección
     * @param completion_info $completion Información de completion
     * @param course_modinfo $modinfo Información de módulos
     * @return array Array de actividades
     */
    private static function get_section_activities($course, $section, $completion, $modinfo) {
        global $USER;

        $activities = [];

        if (empty($modinfo->sections[$section->section])) {
            return $activities;
        }

        foreach ($modinfo->sections[$section->section] as $cmid) {
            $cm = $modinfo->cms[$cmid];

            // Información básica
            $activitydata = [
                'id' => $cm->id,
                'name' => $cm->name,
                'url' => $cm->url ? $cm->url->out() : '',
                'modname' => $cm->modname,
                'visible' => $cm->visible,
                'uservisible' => $cm->uservisible,
                'isactive' => false, // TODO: Determinar actividad actual
                'hascmrestrictions' => !empty($cm->availability)
            ];

            // Determinar estado de completado
            $state = self::get_activity_completion_state($cm, $completion);
            $activitydata = array_merge($activitydata, $state);

            $activities[] = $activitydata;
        }

        return $activities;
    }

    /**
     * Determinar estado de completado de una actividad
     *
     * @param cm_info $cm Información del módulo
     * @param completion_info $completion Información de completion
     * @return array Estado con icono, color y label
     */
    private static function get_activity_completion_state($cm, $completion) {
        global $USER;

        // Si no hay tracking, no mostrar icono
        if ($completion->is_enabled($cm) == COMPLETION_TRACKING_NONE) {
            return [
                'completionstate' => '',
                'completionicon' => '',
                'completioncolor' => '',
                'completionlabel' => ''
            ];
        }

        // Usuario no puede completar
        if (!isloggedin() || isguestuser()) {
            return [
                'completionstate' => 'notstarted',
                'completionicon' => '○',
                'completioncolor' => 'text-muted',
                'completionlabel' => get_string('notstarted', 'theme_compecer')
            ];
        }

        // Obtener datos de completion
        $cmdata = $completion->get_data($cm, false, $USER->id);

        // Completada
        if ($cmdata->completionstate == COMPLETION_COMPLETE ||
            $cmdata->completionstate == COMPLETION_COMPLETE_PASS) {
            return [
                'completionstate' => 'completed',
                'completionicon' => '✓',
                'completioncolor' => 'text-success',
                'completionlabel' => get_string('completed', 'core_completion')
            ];
        }

        // En progreso (ha interactuado pero no completado)
        if ($cmdata->timemodified > 0) {
            return [
                'completionstate' => 'inprogress',
                'completionicon' => '◐',
                'completioncolor' => 'text-warning',
                'completionlabel' => get_string('inprogress', 'theme_compecer')
            ];
        }

        // No iniciada
        return [
            'completionstate' => 'notstarted',
            'completionicon' => '○',
            'completioncolor' => 'text-muted',
            'completionlabel' => get_string('notstarted', 'theme_compecer')
        ];
    }

    /**
     * Obtener color de barra de progreso según porcentaje
     *
     * @param float $percentage Porcentaje 0-100
     * @return string Clase CSS de Bootstrap
     */
    private static function get_progress_color($percentage) {
        if ($percentage < 30) {
            return 'bg-danger';
        } else if ($percentage < 70) {
            return 'bg-warning';
        } else {
            return 'bg-success';
        }
    }

    /**
     * Estructura de retorno
     */
    public static function execute_returns() {
        return new external_single_structure([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'coursename' => new external_value(PARAM_TEXT, 'Course name'),

            'courseprogress' => new external_single_structure([
                'percentage' => new external_value(PARAM_FLOAT, 'Progress percentage'),
                'completedcount' => new external_value(PARAM_INT, 'Completed activities count'),
                'activitycount' => new external_value(PARAM_INT, 'Total activities count'),
                'hascompletion' => new external_value(PARAM_BOOL, 'Has completion enabled'),
                'progresscolor' => new external_value(PARAM_TEXT, 'Progress bar color class'),
                'progresstext' => new external_value(PARAM_TEXT, 'Progress text description'),
                'showactivitylist' => new external_value(PARAM_BOOL, 'Show activity list'),
                'activitylist' => new external_multiple_structure(
                    new external_value(PARAM_TEXT, 'Activity type and count')
                )
            ]),

            'sections' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'number' => new external_value(PARAM_INT, 'Section number'),
                    'title' => new external_value(PARAM_TEXT, 'Section title'),
                    'sectionurl' => new external_value(PARAM_URL, 'Section URL'),
                    'current' => new external_value(PARAM_BOOL, 'Is current section'),
                    'visible' => new external_value(PARAM_BOOL, 'Is visible'),
                    'indexcollapsed' => new external_value(PARAM_BOOL, 'Is collapsed'),
                    'hasrestrictions' => new external_value(PARAM_BOOL, 'Has restrictions'),

                    'progressinfo' => new external_single_structure([
                        'percentage' => new external_value(PARAM_INT, 'Section progress percentage'),
                        'completed' => new external_value(PARAM_INT, 'Completed activities'),
                        'total' => new external_value(PARAM_INT, 'Total activities'),
                        'progresstext' => new external_value(PARAM_TEXT, 'Progress text (e.g., 3/5)'),
                        'progresscolor' => new external_value(PARAM_TEXT, 'Progress color class'),
                        'showminibar' => new external_value(PARAM_BOOL, 'Show mini progress bar')
                    ]),

                    'cms' => new external_multiple_structure(
                        new external_single_structure([
                            'id' => new external_value(PARAM_INT, 'CM ID'),
                            'name' => new external_value(PARAM_TEXT, 'Activity name'),
                            'url' => new external_value(PARAM_URL, 'Activity URL'),
                            'modname' => new external_value(PARAM_TEXT, 'Module name'),
                            'visible' => new external_value(PARAM_BOOL, 'Is visible'),
                            'uservisible' => new external_value(PARAM_BOOL, 'Is visible to user'),
                            'isactive' => new external_value(PARAM_BOOL, 'Is currently active'),
                            'hascmrestrictions' => new external_value(PARAM_BOOL, 'Has restrictions'),
                            'completionstate' => new external_value(PARAM_TEXT, 'Completion state'),
                            'completionicon' => new external_value(PARAM_TEXT, 'Completion icon'),
                            'completioncolor' => new external_value(PARAM_TEXT, 'Completion color'),
                            'completionlabel' => new external_value(PARAM_TEXT, 'Completion label')
                        ])
                    )
                ])
            )
        ]);
    }
}
```

### 4.2 Strings de Idioma Necesarios

**Archivo:** `/theme/compecer/lang/en/theme_compecer.php`

```php
// Existentes (mantener)
$string['courseprogress'] = 'Course Progress';
$string['sidebarcoursemenuheading'] = 'Course index';

// Nuevos (añadir)
$string['viewdetails'] = 'View details';
$string['notstarted'] = 'Not started';
$string['inprogress'] = 'In progress';
$string['completed'] = 'Completed';
$string['togglesection'] = 'Toggle section';
$string['activitiescompleted'] = '{$a->completed} of {$a->total} activities completed';
```

**Archivo:** `/theme/compecer/lang/es/theme_compecer.php`

```php
// Existentes (mantener)
$string['courseprogress'] = 'Progreso del curso';
$string['sidebarcoursemenuheading'] = 'Índice del curso';

// Nuevos (añadir)
$string['viewdetails'] = 'Ver detalles';
$string['notstarted'] = 'No iniciada';
$string['inprogress'] = 'En progreso';
$string['completed'] = 'Completada';
$string['togglesection'] = 'Alternar sección';
$string['activitiescompleted'] = '{$a->completed} de {$a->total} actividades completadas';
```

---

## 5. DISEÑO DE MÓDULOS JAVASCRIPT

### 5.1 Módulo Principal: courseindex_progress.js

**Ubicación:** `/theme/compecer/amd/src/courseindex_progress.js`

**Responsabilidades:**
1. Cargar datos de progreso vía AJAX
2. Actualizar DOM con datos recibidos
3. Escuchar eventos de completion
4. Gestionar animaciones de transición

**Pseudocódigo:**

```javascript
define(['jquery', 'core/ajax', 'core/log', 'core/notification'],
function($, Ajax, Log, Notification) {
    'use strict';

    // Variables privadas
    var courseid = null;
    var isUpdating = false;

    /**
     * Inicializar módulo
     */
    function init(cid) {
        courseid = cid;

        // Cargar datos iniciales
        loadProgressData();

        // Escuchar eventos de completion
        registerEventListeners();
    }

    /**
     * Cargar datos de progreso vía AJAX
     */
    function loadProgressData() {
        if (isUpdating) {
            return; // Evitar llamadas concurrentes
        }

        isUpdating = true;

        Ajax.call([{
            methodname: 'theme_compecer_get_course_progress',
            args: { courseid: courseid }
        }])[0]
        .done(function(response) {
            updateProgressUI(response);
        })
        .fail(function(error) {
            Log.error('Failed to load progress: ' + error.message);
            Notification.exception(error);
        })
        .always(function() {
            isUpdating = false;
        });
    }

    /**
     * Actualizar UI con datos de progreso
     */
    function updateProgressUI(data) {
        // 1. Actualizar progreso global
        updateCourseProgress(data.courseprogress);

        // 2. Actualizar secciones
        updateSectionsProgress(data.sections);

        // 3. Actualizar actividades
        updateActivitiesState(data.sections);

        // 4. Mostrar con fade-in
        $('#courseindex-progress').fadeIn(400);
    }

    /**
     * Actualizar progreso global del curso
     */
    function updateCourseProgress(progress) {
        if (!progress.hascompletion) {
            $('#courseindex-progress').hide();
            return;
        }

        var percentage = Math.floor(progress.percentage);

        // Actualizar porcentaje
        $('.progress-percentage').text(percentage + '%');

        // Actualizar detalles
        $('.progress-details').text(progress.progresstext);

        // Actualizar barra con animación
        var $bar = $('.progress-bar');
        $bar.css('width', percentage + '%');
        $bar.attr('aria-valuenow', percentage);

        // Cambiar color
        $bar.removeClass('bg-danger bg-warning bg-success bg-info');
        $bar.addClass(progress.progresscolor);

        // Actualizar lista de actividades
        if (progress.showactivitylist) {
            var $list = $('.progress-activity-list');
            $list.empty();
            progress.activitylist.forEach(function(activity) {
                $list.append($('<li>').text(activity));
            });
        }
    }

    /**
     * Actualizar badges de progreso por sección
     */
    function updateSectionsProgress(sections) {
        sections.forEach(function(section) {
            var selector = '[data-number="' + section.number + '"]';
            var $badge = $(selector).find('.section-progress-badge');

            if ($badge.length && section.progressinfo) {
                $badge.text(section.progressinfo.percentage + '%');
                $badge.attr('data-percentage', section.progressinfo.percentage);

                // Animar cambio
                $badge.addClass('progress-updated');
                setTimeout(function() {
                    $badge.removeClass('progress-updated');
                }, 600);
            }
        });
    }

    /**
     * Actualizar iconos de estado de actividades
     */
    function updateActivitiesState(sections) {
        sections.forEach(function(section) {
            section.cms.forEach(function(cm) {
                var selector = '[data-id="' + cm.id + '"]';
                var $item = $(selector);

                if ($item.length) {
                    // Actualizar clases de estado
                    $item.removeClass('completion-notstarted completion-inprogress completion-completed');
                    if (cm.completionstate) {
                        $item.addClass('completion-' + cm.completionstate);
                    }

                    // Actualizar icono
                    var $icon = $item.find('.activity-completion-icon');
                    if ($icon.length) {
                        $icon.text(cm.completionicon);
                        $icon.removeClass('text-muted text-warning text-success');
                        $icon.addClass(cm.completioncolor);
                        $icon.attr('aria-label', cm.completionlabel);
                    }
                }
            });
        });
    }

    /**
     * Registrar listeners de eventos
     */
    function registerEventListeners() {
        // Evento de Moodle cuando se completa una actividad
        $(document).on('coursemodulecompletion:updated', function() {
            loadProgressData();
        });

        // Evento personalizado (para compatibilidad)
        $(document).on('theme_compecer:activity_completed', function() {
            loadProgressData();
        });
    }

    // Exportar API pública
    return {
        init: init
    };
});
```

### 5.2 Módulo Complementario: courseindex_interactions.js (Nuevo)

**Responsabilidades:**
1. Gestionar expansión/colapso de secciones
2. Animaciones de transición
3. Persistencia de estado en localStorage

```javascript
define(['jquery'], function($) {
    'use strict';

    /**
     * Inicializar interacciones
     */
    function init() {
        // Toggle sections
        initSectionToggles();

        // Persistencia de estado
        restoreSectionStates();
    }

    /**
     * Inicializar toggles de secciones
     */
    function initSectionToggles() {
        $('.course-index-toggle').on('click', function() {
            var $toggle = $(this);
            var $icon = $toggle.find('.toggle-icon');

            // Rotar icono
            if ($toggle.hasClass('collapsed')) {
                $icon.css('transform', 'rotate(90deg)');
            } else {
                $icon.css('transform', 'rotate(0deg)');
            }

            // Guardar estado
            saveSectionState($toggle.closest('[data-number]').data('number'));
        });
    }

    /**
     * Guardar estado de sección en localStorage
     */
    function saveSectionState(sectionNumber) {
        var key = 'courseindex_section_' + sectionNumber;
        var $section = $('[data-number="' + sectionNumber + '"]');
        var isCollapsed = $section.find('.course-index-content').hasClass('show');

        localStorage.setItem(key, isCollapsed ? 'open' : 'closed');
    }

    /**
     * Restaurar estado de secciones desde localStorage
     */
    function restoreSectionStates() {
        $('.course-index-section').each(function() {
            var $section = $(this);
            var sectionNumber = $section.data('number');
            var key = 'courseindex_section_' + sectionNumber;
            var state = localStorage.getItem(key);

            if (state === 'open') {
                $section.find('.course-index-content').addClass('show');
                $section.find('.course-index-toggle').removeClass('collapsed');
                $section.find('.toggle-icon').css('transform', 'rotate(90deg)');
            }
        });
    }

    return {
        init: init
    };
});
```

---

## 6. DISEÑO DE ESTILOS SCSS/CSS

### 6.1 Estructura de Archivos

```
theme/compecer/scss/
├── compecer.scss (principal)
├── _variables.scss (variables globales)
├── _mixins.scss (mixins reutilizables)
└── components/
    └── _courseindex.scss (NUEVO - estilos de courseindex)
```

### 6.2 Variables SCSS

**Archivo:** `/theme/compecer/scss/_variables.scss`

```scss
// === COLORS ===

// Colores principales (existentes)
$primary-blue: #365ba3;
$primary-red: #e21144;
$yellow: #ffb000;
$white: #FFFFFF;
$gray: #5e5e5e;
$dark-blue: #001f40;

// Colores de progreso (nuevos)
$progress-bg: rgba(0, 0, 0, 0.1);
$progress-danger: #dc3545;
$progress-warning: #ffc107;
$progress-success: #28a745;
$progress-info: #17a2b8;

// Colores de estado de actividades (nuevos)
$activity-notstarted: #999;
$activity-inprogress: #ff9800;
$activity-completed: #28a745;

// === SPACING ===
$courseindex-padding: 1rem;
$section-padding: 0.75rem;
$activity-padding: 0.5rem 1rem;

// === BORDERS ===
$border-radius-base: 6px;
$border-radius-large: 12px;
$progress-border-radius: 10px;

// === TRANSITIONS ===
$transition-speed-fast: 0.2s;
$transition-speed-normal: 0.3s;
$transition-speed-slow: 0.6s;
$easing-standard: cubic-bezier(0.4, 0, 0.2, 1);

// === SHADOWS ===
$shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
$shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
$shadow-hover: 0 3px 8px rgba(0, 0, 0, 0.05);

// === TYPOGRAPHY ===
$font-size-small: 0.75rem;
$font-size-base: 0.875rem;
$font-size-large: 0.95rem;
$font-size-xlarge: 1.5rem;
```

### 6.3 Mixins Reutilizables

**Archivo:** `/theme/compecer/scss/_mixins.scss`

```scss
// Transición suave
@mixin smooth-transition($properties: all, $duration: $transition-speed-normal) {
    transition: $properties $duration $easing-standard;
}

// Sombra con hover
@mixin shadow-hover {
    box-shadow: $shadow-sm;
    @include smooth-transition(box-shadow);

    &:hover {
        box-shadow: $shadow-md;
    }
}

// Truncar texto
@mixin text-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

// Flexbox centrado
@mixin flex-center {
    display: flex;
    align-items: center;
    justify-content: center;
}

// Barra de progreso base
@mixin progress-bar-base($height: 8px) {
    height: $height;
    background-color: $progress-bg;
    border-radius: $progress-border-radius;
    overflow: hidden;
    position: relative;
}

// Animación shimmer
@mixin shimmer-effect {
    &::after {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg,
            transparent,
            rgba(255, 255, 255, 0.3),
            transparent);
        animation: shimmer 2s infinite;
    }
}

@keyframes shimmer {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(200%);
    }
}

// Responsive
@mixin mobile {
    @media (max-width: 768px) {
        @content;
    }
}

@mixin tablet {
    @media (min-width: 769px) and (max-width: 1024px) {
        @content;
    }
}

@mixin desktop {
    @media (min-width: 1025px) {
        @content;
    }
}
```

### 6.4 Estilos de CourseIndex

**Archivo:** `/theme/compecer/scss/components/_courseindex.scss`

```scss
// ============================================
// COURSEINDEX CON PROGRESO - MOCKUP DESIGN
// ============================================

// === DRAWER PRINCIPAL ===
.course-index-drawer {
    height: 100%;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 0.5rem;
    background-color: $white;

    // Scrollbar personalizado
    &::-webkit-scrollbar {
        width: 6px;
    }

    &::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
    }

    &::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 3px;

        &:hover {
            background: rgba(0, 0, 0, 0.3);
        }
    }
}

// === HEADER DEL DRAWER ===
.drawer-header {
    padding: 0.5rem 1rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);

    .drawer-title {
        font-size: $font-size-large;
        font-weight: 700;
        color: $dark-blue;
        margin: 0;
    }
}

// ============================================
// PROGRESO GLOBAL DEL CURSO
// ============================================

.courseindex-progress-container {
    background: linear-gradient(135deg,
        rgba(255, 255, 255, 0.95),
        rgba(249, 250, 251, 0.95));
    border-radius: $border-radius-large;
    padding: $courseindex-padding;
    margin: 0 0.5rem 1rem;
    border-left: 4px solid $dark-blue;
    @include shadow-hover;
    @include smooth-transition(all);

    &:hover {
        transform: translateY(-2px);
    }

    // Animación de entrada
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

// Header del progreso
.progress-header {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.progress-title {
    font-size: $font-size-base;
    font-weight: 700;
    color: $dark-blue;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin: 0;
}

.progress-percentage {
    font-size: $font-size-xlarge;
    color: $dark-blue;
    font-weight: 700;
    line-height: 1;
}

.progress-details {
    font-size: $font-size-small;
    color: $gray;
    margin-bottom: 0.75rem;
}

// Barra de progreso
.progress-rounded {
    @include progress-bar-base;
}

.progress-bar {
    height: 100%;
    border-radius: $progress-border-radius;
    @include smooth-transition(width, $transition-speed-slow);
    position: relative;
    overflow: hidden;
    @include shimmer-effect;

    // Colores
    &.bg-danger {
        background-color: $progress-danger !important;
    }

    &.bg-warning {
        background-color: $progress-warning !important;
    }

    &.bg-success {
        background-color: $progress-success !important;
    }

    &.bg-info {
        background-color: $progress-info !important;
    }
}

// Lista de actividades (colapsable)
.progress-activities-details {
    margin-top: 0.75rem;
    font-size: $font-size-small;

    summary {
        cursor: pointer;
        color: $primary-blue;
        font-weight: 500;
        list-style: none; // Quitar triángulo por defecto

        &::-webkit-details-marker {
            display: none;
        }

        &::before {
            content: '▶ ';
            display: inline-block;
            @include smooth-transition(transform, $transition-speed-fast);
        }

        &:hover {
            text-decoration: underline;
        }
    }

    &[open] summary::before {
        transform: rotate(90deg);
    }
}

.progress-activity-list {
    list-style: none;
    padding: 0;
    margin: 0.5rem 0 0 0;

    li {
        padding: 0.25rem 0 0.25rem 1.25rem;
        position: relative;
        color: $gray;

        &::before {
            content: '•';
            position: absolute;
            left: 0;
            color: $dark-blue;
            font-weight: bold;
        }
    }
}

// ============================================
// SECCIONES DEL CURSO
// ============================================

.courseindex-sections {
    margin-top: 0.5rem;
}

.course-index-section {
    position: relative;
    background-color: $white;
    border: 1px solid rgba(0, 0, 0, 0.1);
    border-radius: $border-radius-base;
    margin: 0 0.5rem 0.75rem;
    overflow: hidden;
    @include smooth-transition(all);

    &:hover {
        box-shadow: $shadow-hover;
        transform: translateY(-1px);
    }

    // Sección actual
    &.current {
        border-color: rgba($primary-blue, 0.3);
        box-shadow: 0 2px 12px rgba($primary-blue, 0.08);
    }

    // Sección no visible
    &.dimmed {
        opacity: 0.6;
    }
}

// Header de sección
.course-index-header {
    background-color: $primary-blue;
    display: flex;
    align-items: center;
    padding: 0;
    border-bottom: 1px solid rgba($white, 0.1);
    @include smooth-transition(background-color);

    &:hover {
        background-color: darken($primary-blue, 5%);
    }

    // Header no visible
    &.dimmed {
        opacity: 0.85;
    }

    // Header con restricciones
    &.restrictions {
        border-left: 3px solid $yellow;
    }
}

// Toggle icon de sección
.course-index-toggle {
    min-width: 40px;
    padding: $section-padding;
    background: transparent;
    border: none;
    color: rgba($white, 0.95);
    cursor: pointer;
    @include smooth-transition(transform);

    &:hover {
        background-color: rgba($white, 0.1);
    }

    &:focus {
        outline: 2px solid rgba($white, 0.5);
        outline-offset: -2px;
    }

    .toggle-icon {
        display: inline-block;
        @include smooth-transition(transform, $transition-speed-normal);
        transform: rotate(0deg);
    }

    &:not(.collapsed) .toggle-icon {
        transform: rotate(90deg);
    }
}

// Enlace a la sección
.course-index-link {
    flex: 1;
    color: $white;
    padding: $section-padding;
    font-size: $font-size-large;
    font-weight: 500;
    text-decoration: none;
    @include text-truncate;
    @include smooth-transition(all);

    &:hover,
    &:focus {
        background-color: rgba($white, 0.08);
        color: $white;
        text-decoration: none;
    }
}

// Badge de porcentaje de sección
.section-progress-badge {
    font-weight: 600;
    margin-right: 0.5rem;
    padding: 0.25rem 0.5rem;
    background-color: rgba($white, 0.2);
    border-radius: 4px;
    font-size: $font-size-base;
    color: $white;
    @include smooth-transition(all);

    // Animación al actualizar
    &.progress-updated {
        animation: pulse 0.6s ease;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        background-color: rgba($white, 0.2);
    }
    50% {
        transform: scale(1.1);
        background-color: rgba($white, 0.4);
    }
}

// ============================================
// CONTENIDO DE SECCIÓN (Colapsable)
// ============================================

.course-index-content {
    background-color: rgba(0, 0, 0, 0.02);

    // Mini barra de progreso (opcional)
    .section-progress-minibar {
        padding: 0.5rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);

        .progress {
            @include progress-bar-base(4px);
            margin-bottom: 0.25rem;
        }

        .progress-minitext {
            font-size: 0.7rem;
            color: $gray;
        }
    }
}

.course-index-section-content {
    list-style: none;
    padding: 0;
    margin: 0;
}

// ============================================
// ACTIVIDADES
// ============================================

.course-index-item {
    position: relative;
    margin: 2px 0;

    // Estados de completion
    &.completion-completed {
        background-color: rgba($activity-completed, 0.05);
    }

    &.completion-inprogress {
        background-color: rgba($activity-inprogress, 0.05);
    }

    &.completion-notstarted {
        // Sin fondo especial
    }

    // Actividad activa
    &.active {
        .activity-link {
            background-color: rgba($primary-red, 0.08);
            color: $primary-red;
            border-left-color: $yellow;
            font-weight: 500;
        }
    }

    // Actividad no visible
    &.dimmed {
        opacity: 0.6;
    }
}

.activity-item-content {
    display: flex;
    align-items: center;
    padding: $activity-padding;
    padding-left: 2.5rem; // Espacio para el icono
    position: relative;
    @include smooth-transition(all);

    &:hover {
        background-color: rgba($primary-red, 0.05);

        .activity-link {
            color: $primary-red;
            transform: translateX(2px);
        }

        .activity-completion-icon {
            transform: scale(1.2);
        }
    }
}

// Icono de estado de completion
.activity-completion-icon {
    position: absolute;
    left: 1rem;
    font-size: 1rem;
    @include smooth-transition(all);

    &.text-muted {
        color: $activity-notstarted;
    }

    &.text-warning {
        color: $activity-inprogress;
    }

    &.text-success {
        color: $activity-completed;
    }
}

// Enlace a la actividad
.activity-link {
    flex: 1;
    color: $gray;
    text-decoration: none;
    font-size: $font-size-base;
    border-left: 3px solid transparent;
    padding-left: 0.5rem;
    @include smooth-transition(all);

    &:hover {
        color: $primary-red;
        text-decoration: none;
        border-left-color: $yellow;
    }

    &:focus {
        outline: 2px solid rgba($primary-blue, 0.3);
        outline-offset: 2px;
    }
}

.activity-link-disabled {
    flex: 1;
    color: rgba($gray, 0.5);
    font-style: italic;
    cursor: not-allowed;
}

// ============================================
// RESPONSIVE DESIGN
// ============================================

@include mobile {
    .courseindex-progress-container {
        margin: 0 0.25rem 0.75rem;
        padding: 0.75rem;
    }

    .progress-percentage {
        font-size: 1.25rem;
    }

    .course-index-section {
        margin: 0 0.25rem 0.5rem;
    }

    .course-index-link {
        font-size: $font-size-base;
        padding: 0.5rem;
    }

    .section-progress-badge {
        font-size: $font-size-small;
        padding: 0.2rem 0.4rem;
    }

    .activity-item-content {
        padding: 0.4rem 0.75rem;
        padding-left: 2rem;
    }

    .activity-completion-icon {
        left: 0.5rem;
        font-size: 0.9rem;
    }
}

@include tablet {
    .courseindex-progress-container {
        margin: 0 0.4rem 0.9rem;
    }
}

// ============================================
// ACCESIBILIDAD
// ============================================

// Reducir movimiento para usuarios con preferencias
@media (prefers-reduced-motion: reduce) {
    * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}

// Mejoras de contraste
@media (prefers-contrast: high) {
    .course-index-header {
        border: 2px solid $white;
    }

    .activity-link {
        border-left-width: 4px;
    }
}

// Modo oscuro (preparación)
@media (prefers-color-scheme: dark) {
    .course-index-drawer {
        background-color: #1a1a1a;
        color: #e0e0e0;
    }

    .courseindex-progress-container {
        background: linear-gradient(135deg,
            rgba(30, 30, 30, 0.95),
            rgba(40, 40, 40, 0.95));
        border-left-color: lighten($dark-blue, 20%);
    }

    // ... más estilos de modo oscuro según necesidad
}
```

### 6.5 Importación en Archivo Principal

**Archivo:** `/theme/compecer/scss/compecer.scss`

```scss
// Importaciones base
@import 'variables';
@import 'mixins';

// Componentes
@import 'components/courseindex';

// ... resto de imports ...
```

---

## 7. ESTRUCTURA DE DATOS

### 7.1 Flujo Completo de Datos

```
┌─────────────────────────────────────────────────────────────┐
│                    MOODLE DATABASE                           │
│  {course} {course_modules} {course_modules_completion}      │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│              PHP BACKEND (Servicio Web)                      │
│                                                              │
│  get_course_progress::execute($courseid)                    │
│    ├─ get_course_progress_data()                            │
│    │    └─ progress::get_course_progress_percentage()       │
│    │                                                         │
│    └─ get_sections_progress_data()                          │
│         ├─ get_section_module_info()                        │
│         └─ get_section_activities()                         │
│              └─ get_activity_completion_state()             │
│                                                              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                    JSON RESPONSE                             │
│  {                                                           │
│    courseid: 5,                                              │
│    courseprogress: {...},                                    │
│    sections: [                                               │
│      {id, title, progressinfo, cms: [...]}                   │
│    ]                                                         │
│  }                                                           │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│           JAVASCRIPT (courseindex_progress.js)               │
│                                                              │
│  loadProgressData()                                          │
│    └─ Ajax.call('theme_compecer_get_course_progress')       │
│         └─ updateProgressUI(data)                            │
│              ├─ updateCourseProgress()                       │
│              ├─ updateSectionsProgress()                     │
│              └─ updateActivitiesState()                      │
│                                                              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│                 DOM (HTML Renderizado)                       │
│                                                              │
│  <nav class="course-index-drawer">                          │
│    <div class="courseindex-progress-container">             │
│      <!-- Barra de progreso global -->                      │
│    </div>                                                    │
│    <div class="courseindex-sections">                       │
│      <div class="course-index-section">                     │
│        <!-- Secciones con progreso -->                      │
│        <ul class="course-index-section-content">            │
│          <li class="course-index-item">                     │
│            <!-- Actividades con estado -->                  │
│          </li>                                               │
│        </ul>                                                 │
│      </div>                                                  │
│    </div>                                                    │
│  </nav>                                                      │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 8. MAPA DE ARCHIVOS

### 8.1 Archivos a Modificar

| Archivo | Tipo | Acción | Descripción |
|---------|------|--------|-------------|
| `/theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache` | Template | **MODIFICAR** | Actualizar estructura según mockup |
| `/theme/compecer/templates/core_courseformat/local/courseindex/section.mustache` | Template | **MODIFICAR** | Añadir badge de progreso |
| `/theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache` | Template | **MODIFICAR** | Añadir icono de estado |
| `/theme/compecer/classes/external/get_course_progress.php` | PHP | **EXTENDER** | Añadir progreso por sección y actividades |
| `/theme/compecer/amd/src/courseindex_progress.js` | JavaScript | **MODIFICAR** | Actualizar secciones y actividades |
| `/theme/compecer/scss/compecer.scss` | SCSS | **MODIFICAR** | Importar estilos de courseindex |
| `/theme/compecer/lang/en/theme_compecer.php` | Idioma | **AÑADIR** | Strings nuevos |
| `/theme/compecer/lang/es/theme_compecer.php` | Idioma | **AÑADIR** | Strings nuevos |

### 8.2 Archivos a Crear

| Archivo | Tipo | Descripción |
|---------|------|-------------|
| `/theme/compecer/scss/components/_courseindex.scss` | SCSS | Estilos completos según mockup |
| `/theme/compecer/scss/_mixins.scss` | SCSS | Mixins reutilizables (si no existe) |
| `/theme/compecer/amd/src/courseindex_interactions.js` | JavaScript | Módulo de interacciones (opcional) |

### 8.3 Archivos a Mantener

| Archivo | Descripción |
|---------|-------------|
| `/theme/compecer/db/services.php` | Mantener registro del servicio web |
| `/theme/compecer/version.php` | Incrementar versión después de cambios |
| `/theme/compecer/scss/_variables.scss` | Mantener variables existentes, añadir nuevas |

---

## CONCLUSIÓN

Este diseño proporciona una estructura completa y detallada para implementar el courseindex según el mockup proporcionado. La arquitectura combina:

✅ **Progreso global del curso** - Header prominente con barra visual
✅ **Progreso por sección** - Badges de porcentaje en cada sección
✅ **Estados de actividades** - Iconos visuales (○, ◐, ✓)
✅ **Diseño responsive** - Adaptable a móvil, tablet y escritorio
✅ **Accesibilidad** - ARIA, contraste, navegación por teclado
✅ **Performance** - Un solo servicio web, actualización dinámica
✅ **Mantenibilidad** - Código modular, bien documentado

**Próximo paso:** FASE 6 - Implementación del código según este diseño.
