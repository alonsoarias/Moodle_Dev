# SÍNTESIS: LÓGICA DE PROGRESO PARA COURSEINDEX COMPECER

**Fecha de Creación:** 2025-10-27
**Autor:** Análisis basado en theme remui y plugin format_remuiformat
**Propósito:** Guía de implementación para rediseño de courseindex con barras de progreso

---

## TABLA DE CONTENIDOS

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [¿Cómo Obtener el Progreso del Curso Completo?](#2-cómo-obtener-el-progreso-del-curso-completo)
3. [¿Cómo Obtener el Progreso por Sección?](#3-cómo-obtener-el-progreso-por-sección)
4. [¿Cómo Renderizar las Barras de Progreso?](#4-cómo-renderizar-las-barras-de-progreso)
5. [Comparativa de Implementaciones](#5-comparativa-de-implementaciones)
6. [Recomendaciones Finales](#6-recomendaciones-finales)

---

## 1. RESUMEN EJECUTIVO

### Hallazgos Principales

**Estado Actual (Compecer):**
- ✅ Ya tiene barra de progreso del curso completo implementada
- ✅ Usa API de Moodle `core_completion\progress::get_course_progress_percentage()`
- ❌ NO tiene progreso por sección individual
- ❌ NO tiene indicadores de estado de actividades (○, ◐, ✓)
- ❌ Diseño no coincide con el mockup deseado

**Sistemas Analizados:**
- **Remui:** Barra de progreso global en block myoverview (fuera del courseindex)
- **Format_remuiformat:** Progreso por sección en formato de curso

**Conclusión:**
Combinar la lógica de `format_remuiformat` (progreso por sección) con la estructura actual de `compecer` (progreso global) para crear un courseindex completo según mockup.

---

## 2. ¿CÓMO OBTENER EL PROGRESO DEL CURSO COMPLETO?

### 2.1 API de Moodle Core (RECOMENDADA)

**Método:** `core_completion\progress::get_course_progress_percentage()`

**Ubicación:** `/lib/classes/completion/progress.php`

**Implementación en Compecer (ya existente):**

```php
// theme/compecer/classes/external/get_course_progress.php:99-102

use core_completion\progress;

$percentage = progress::get_course_progress_percentage($course, $USER->id);

if (!is_null($percentage)) {
    $percentage = floor($percentage);  // Redondear hacia abajo
} else {
    $percentage = 0;
}
```

**Parámetros:**
- `$course` - Objeto del curso (de `get_course()`)
- `$userid` - ID del usuario (opcional, por defecto el actual)

**Retorno:**
- `float|null` - Porcentaje 0-100 o null si no hay completion habilitado

### 2.2 Cálculo Manual (Alternativa)

```php
// Basado en format_remuiformat

global $USER;

// 1. Obtener información del curso
$course = get_course($courseid);

// 2. Verificar si completion está habilitado
$completion = new completion_info($course);
if (!$completion->is_enabled()) {
    return ['percentage' => 0, 'hascompletion' => false];
}

// 3. Obtener información rápida de módulos
$modinfo = get_fast_modinfo($course);
$sections = $modinfo->get_section_info_all();

// 4. Contadores
$total = 0;
$completed = 0;

// 5. Iterar por secciones y módulos
foreach ($sections as $section) {
    $modules = $modinfo->get_cms();

    foreach ($modules as $mod) {
        // Verificar si el módulo es visible y tiene completion
        if (!$mod->uservisible || !$mod->is_visible_on_course_page()) {
            continue;
        }

        // Verificar si tiene tracking
        if ($completion->is_enabled($mod) == COMPLETION_TRACKING_NONE) {
            continue;
        }

        $total++;

        // Obtener estado de completion
        $data = $completion->get_data($mod, false, $USER->id);

        if ($data->completionstate == COMPLETION_COMPLETE ||
            $data->completionstate == COMPLETION_COMPLETE_PASS) {
            $completed++;
        }
    }
}

// 6. Calcular porcentaje
$percentage = ($total > 0) ? floor(($completed / $total) * 100) : 0;

return [
    'percentage' => $percentage,
    'total' => $total,
    'completed' => $completed,
    'hascompletion' => true
];
```

### 2.3 Queries a Base de Datos

El método `get_course_progress_percentage()` utiliza internamente:

```sql
-- Obtener módulos del curso
SELECT cm.id, cm.module, cm.instance, cm.completion, cm.visible
FROM {course_modules} cm
INNER JOIN {modules} m ON m.id = cm.module
WHERE cm.course = ? AND cm.visible = 1 AND m.visible = 1

-- Obtener estado de completion del usuario
SELECT * FROM {course_modules_completion}
WHERE coursemoduleid = ? AND userid = ?
```

### 2.4 Datos Adicionales (Compecer actual)

Compecer también obtiene desglose por tipo de actividad:

```php
// theme/compecer/classes/external/get_course_progress.php:126-142

$activitytypes = [];
foreach ($modinfo->get_cms() as $cm) {
    if ($cm->uservisible && $cm->is_visible_on_course_page()) {
        $modname = get_string('modulename', $cm->modname);
        if (!isset($activitytypes[$modname])) {
            $activitytypes[$modname] = 0;
        }
        $activitytypes[$modname]++;
    }
}

// Convertir a lista: ["3 Quiz", "2 Assignments", "1 Forum"]
$activitylist = [];
foreach ($activitytypes as $type => $count) {
    $activitylist[] = "$count $type";
}
```

**Resultado:**
```json
{
    "courseid": 5,
    "percentage": 65.0,
    "hascompletion": true,
    "activitycount": 20,
    "completedcount": 13,
    "activitylist": ["5 Quiz", "3 Forum", "2 Assignment"]
}
```

---

## 3. ¿CÓMO OBTENER EL PROGRESO POR SECCIÓN?

### 3.1 Implementación de Format_remuiformat (RECOMENDADA)

**Ubicación:** `/course/format/remuiformat/classes/course_format_data_common_trait.php:300-360`

**Método:** `get_section_module_info()`

```php
/**
 * Obtener información de progreso de una sección específica
 *
 * @param stdClass $course Objeto del curso
 * @param int $sectionid ID de la sección
 * @return object Objeto con datos de progreso
 */
protected function get_section_module_info($course, $sectionid) {
    global $USER;

    // 1. Inicializar completion info
    $completion = new completion_info($course);
    $cancomplete = isloggedin() && !isguestuser();

    // 2. Obtener fast modinfo
    $modinfo = get_fast_modinfo($course);
    $section = $modinfo->get_section_info($sectionid);

    // 3. Inicializar contadores
    $total = 0;
    $complete = 0;

    // 4. Iterar por módulos de la sección
    if (!empty($modinfo->sections[$section->section])) {
        foreach ($modinfo->sections[$section->section] as $cmid) {
            $thismod = $modinfo->cms[$cmid];

            // Verificar visibilidad
            if (!$thismod->uservisible) {
                continue;
            }

            // Excluir labels
            if ($thismod->modname == 'label') {
                continue;
            }

            // Verificar que sea visible en página de curso
            if (!$thismod->is_visible_on_course_page()) {
                continue;
            }

            // Verificar que tenga completion tracking
            if ($cancomplete &&
                $completion->is_enabled($thismod) != COMPLETION_TRACKING_NONE) {

                $total++;

                // Obtener estado de completion
                $completiondata = $completion->get_data($thismod, true);

                if ($completiondata->completionstate == COMPLETION_COMPLETE ||
                    $completiondata->completionstate == COMPLETION_COMPLETE_PASS) {
                    $complete++;
                }
            }
        }
    }

    // 5. Calcular porcentaje
    $percentage = ($total > 0) ? round(($complete / $total) * 100, 0) : 0;

    // 6. Retornar objeto con información
    $pinfo = new stdClass();
    $pinfo->completed = $complete;
    $pinfo->total = $total;
    $pinfo->percentage = $percentage;
    $pinfo->progress = "$complete/$total";

    return $pinfo;
}
```

### 3.2 Integración en Export Template

Para pasarlo a Mustache:

```php
// En la clase que exporta datos para template

public function export_for_template(renderer_base $output) {
    global $COURSE;

    $data = new stdClass();

    // ... otros datos ...

    // Obtener todas las secciones
    $modinfo = get_fast_modinfo($COURSE);
    $sections = $modinfo->get_section_info_all();

    $data->sections = [];

    foreach ($sections as $section) {
        // Información básica de sección
        $sectiondata = [
            'id' => $section->id,
            'number' => $section->section,
            'title' => get_section_name($COURSE, $section),
            'visible' => $section->visible,
            // ... otros campos ...
        ];

        // AGREGAR PROGRESO
        $progressinfo = $this->get_section_module_info($COURSE, $section->id);
        $sectiondata['progressinfo'] = [
            'percentage' => $progressinfo->percentage,
            'completed' => $progressinfo->completed,
            'total' => $progressinfo->total,
            'progress' => $progressinfo->progress,  // "5/10"
        ];

        $data->sections[] = $sectiondata;
    }

    return $data;
}
```

### 3.3 Criterios de Completado

Una actividad se cuenta para el progreso SOLO si cumple:

| Criterio | Verificación |
|----------|-------------|
| Usuario logueado | `isloggedin()` |
| Usuario NO es guest | `!isguestuser()` |
| Actividad visible para usuario | `$mod->uservisible == true` |
| Visible en página de curso | `$mod->is_visible_on_course_page()` |
| NO es label (etiqueta) | `$mod->modname != 'label'` |
| Tiene completion tracking | `$completion->is_enabled($mod) != COMPLETION_TRACKING_NONE` |

**Estados contados como "completado":**
- `COMPLETION_COMPLETE` - Completada (manual o auto)
- `COMPLETION_COMPLETE_PASS` - Completada con aprobación

**Estados NO contados:**
- `COMPLETION_INCOMPLETE` - Sin completar
- `COMPLETION_COMPLETE_FAIL` - Completada con fallo (quiz reprobado)

---

## 4. ¿CÓMO RENDERIZAR LAS BARRAS DE PROGRESO?

### 4.1 Estructura HTML Óptima

#### Barra de Progreso Global (Header)

```html
<!-- Contenedor principal -->
<div class="courseindex-progress-container"
     data-courseid="{{courseid}}"
     {{^hascompletion}}style="display:none;"{{/hascompletion}}>

    <!-- Header -->
    <div class="progress-header">
        <h6 class="progress-title">
            {{#str}}courseprogress, theme_compecer{{/str}}
        </h6>

        <!-- Estadísticas -->
        <div class="progress-stats">
            <span class="progress-percentage">{{percentage}}%</span>
            <span class="progress-details">
                {{completedcount}} de {{activitycount}} actividades
            </span>
        </div>
    </div>

    <!-- Barra de progreso visual -->
    <div class="progress progress-rounded">
        <div class="progress-bar {{progresscolor}}"
             role="progressbar"
             style="width: {{percentage}}%;"
             aria-valuenow="{{percentage}}"
             aria-valuemin="0"
             aria-valuemax="100">
        </div>
    </div>

    <!-- Lista de actividades (opcional) -->
    {{#showdetails}}
    <ul class="progress-activity-list">
        {{#activitylist}}
        <li>{{.}}</li>
        {{/activitylist}}
    </ul>
    {{/showdetails}}
</div>
```

#### Barra de Progreso por Sección

```html
<!-- Encabezado de sección con progreso -->
<div class="course-index-header">
    <!-- Título de sección -->
    <a href="{{{sectionurl}}}" class="course-index-link">
        <span class="section-title">{{{title}}}</span>
    </a>

    <!-- Porcentaje de progreso -->
    {{#progressinfo}}
    <span class="section-progress-badge">
        {{percentage}}%
    </span>
    {{/progressinfo}}

    <!-- Toggle para expandir/colapsar -->
    <button class="course-index-toggle"
            data-toggle="collapse"
            data-target="#section-{{number}}">
        <i class="fa fa-chevron-down"></i>
    </button>
</div>

<!-- Contenido colapsable de la sección -->
<div id="section-{{number}}" class="course-index-content collapse">

    <!-- Barra de progreso pequeña (opcional) -->
    {{#progressinfo}}
    <div class="section-progress-bar">
        <div class="progress">
            <div class="progress-bar bg-success"
                 style="width: {{percentage}}%;"
                 role="progressbar">
            </div>
        </div>
        <small class="progress-text">{{progress}} completadas</small>
    </div>
    {{/progressinfo}}

    <!-- Lista de actividades -->
    <ul class="course-index-section-content">
        {{#cms}}
        {{> core_courseformat/local/courseindex/cm}}
        {{/cms}}
    </ul>
</div>
```

### 4.2 Estructura Mustache Recomendada

**Template Principal:** `drawer.mustache`

```mustache
{{!
    @template core_courseformat/local/courseindex/drawer

    Context:
    {
        "courseid": 5,
        "courseprogress": {
            "percentage": 65,
            "completedcount": 13,
            "activitycount": 20,
            "hascompletion": true,
            "progresscolor": "bg-warning",
            "activitylist": ["5 Quiz", "3 Forum"]
        },
        "sections": [
            {
                "id": 23,
                "number": 1,
                "title": "Introducción",
                "sectionurl": "#section-1",
                "progressinfo": {
                    "percentage": 60,
                    "completed": 3,
                    "total": 5,
                    "progress": "3/5"
                },
                "cms": [...]
            }
        ]
    }
}}

<nav id="courseindex" class="course-index-drawer">

    {{! Header del drawer }}
    <div class="drawer-header">
        <h5>{{#str}}courseindex, core{{/str}}</h5>
    </div>

    {{! Progreso global del curso }}
    {{#courseprogress}}
    <div class="courseindex-progress-container">
        <div class="progress-header">
            <span class="progress-title">PROGRESO DEL CURSO</span>
            <span class="progress-percentage">{{percentage}}%</span>
        </div>
        <div class="progress-details">
            {{completedcount}} de {{activitycount}} actividades ({{percentage}}%)
        </div>
        <div class="progress">
            <div class="progress-bar {{progresscolor}}"
                 style="width: {{percentage}}%;"
                 role="progressbar">
            </div>
        </div>
    </div>
    {{/courseprogress}}

    {{! Secciones del curso }}
    <div id="courseindex-content">
        {{#sections}}
        {{> core_courseformat/local/courseindex/section}}
        {{/sections}}
    </div>
</nav>
```

**Template Sección:** `section.mustache`

```mustache
{{!
    Context:
    {
        "id": 23,
        "title": "Unidad 1",
        "progressinfo": {
            "percentage": 33,
            "completed": 2,
            "total": 6
        }
    }
}}

<div class="course-index-section">

    {{! Header con progreso }}
    <div class="course-index-header">
        <button class="section-toggle" data-toggle="collapse">
            <i class="fa fa-chevron-right"></i>
        </button>

        <a href="{{{sectionurl}}}" class="section-link">
            {{{title}}}
        </a>

        {{#progressinfo}}
        <span class="section-progress">{{percentage}}%</span>
        {{/progressinfo}}
    </div>

    {{! Contenido }}
    <div class="section-content collapse">
        <ul class="activity-list">
            {{#cms}}
            {{> core_courseformat/local/courseindex/cm}}
            {{/cms}}
        </ul>
    </div>
</div>
```

### 4.3 Estilos CSS/SCSS Necesarios

```scss
// Variables
$progress-bg: rgba(0, 0, 0, 0.1);
$progress-height: 8px;
$progress-border-radius: 10px;

// Contenedor principal
.courseindex-progress-container {
    background: linear-gradient(135deg,
        rgba(255, 255, 255, 0.95),
        rgba(249, 250, 251, 0.95));
    border-radius: 12px;
    padding: 1rem;
    margin: 0 0.5rem 1rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    border-left: 4px solid #001f40;
    transition: all 0.3s ease;

    &:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        transform: translateY(-2px);
    }
}

// Header de progreso
.progress-header {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.progress-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: #001f40;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.progress-percentage {
    font-size: 1.5rem;
    color: #001f40;
    font-weight: 700;
}

.progress-details {
    font-size: 0.75rem;
    color: #666;
}

// Barra de progreso
.progress {
    height: $progress-height;
    background-color: $progress-bg;
    border-radius: $progress-border-radius;
    overflow: hidden;
}

.progress-bar {
    height: 100%;
    border-radius: $progress-border-radius;
    transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;

    // Animación shimmer
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
    0% { transform: translateX(0); }
    100% { transform: translateX(200%); }
}

// Colores según porcentaje
.progress-bar.bg-danger {
    background-color: #dc3545;  // < 30%
}

.progress-bar.bg-warning {
    background-color: #ffc107;  // 30-70%
}

.progress-bar.bg-success {
    background-color: #28a745;  // > 70%
}

// Sección con progreso
.course-index-section {
    margin-bottom: 0.5rem;
}

.course-index-header {
    display: flex;
    align-items: center;
    padding: 0.75rem;
    background: #365ba3;
    color: white;
    cursor: pointer;

    .section-link {
        flex: 1;
        color: white;
        font-weight: 500;

        &:hover {
            text-decoration: none;
        }
    }

    .section-progress {
        font-weight: 600;
        margin-left: auto;
        margin-right: 0.5rem;
        font-size: 0.9rem;
    }

    .section-toggle {
        background: none;
        border: none;
        color: white;
        padding: 0;
        margin-right: 0.5rem;

        i {
            transition: transform 0.3s ease;
        }

        &.collapsed i {
            transform: rotate(0deg);
        }

        &:not(.collapsed) i {
            transform: rotate(90deg);
        }
    }
}

// Responsive
@media (max-width: 768px) {
    .courseindex-progress-container {
        margin: 0 0.25rem 0.75rem;
        padding: 0.75rem;
    }

    .progress-percentage {
        font-size: 1.25rem;
    }
}
```

### 4.4 JavaScript para Interactividad

```javascript
// theme/compecer/amd/src/courseindex_progress.js

define(['jquery', 'core/ajax', 'core/log'], function($, Ajax, Log) {
    'use strict';

    /**
     * Inicializar módulo de progreso
     */
    function init(courseid) {
        // Cargar datos iniciales
        loadProgressData(courseid);

        // Escuchar eventos de completion
        $(document).on('coursemodulecompletion:updated', function() {
            loadProgressData(courseid);
        });
    }

    /**
     * Cargar datos de progreso vía AJAX
     */
    function loadProgressData(courseid) {
        var container = $('#courseindex-progress');

        Ajax.call([{
            methodname: 'theme_compecer_get_course_progress',
            args: { courseid: courseid }
        }])[0]
        .done(function(response) {
            updateProgressUI(response);
        })
        .fail(function(error) {
            Log.error('Failed to load progress: ' + error.message);
            container.hide();
        });
    }

    /**
     * Actualizar UI con datos de progreso
     */
    function updateProgressUI(data) {
        if (!data.hascompletion) {
            $('#courseindex-progress').hide();
            return;
        }

        var percentage = Math.floor(data.percentage);

        // Actualizar porcentaje
        $('.progress-percentage').text(percentage + '%');

        // Actualizar barra
        var progressBar = $('.progress-bar');
        progressBar.css('width', percentage + '%');
        progressBar.attr('aria-valuenow', percentage);

        // Cambiar color según porcentaje
        progressBar.removeClass('bg-danger bg-warning bg-info bg-success');
        if (percentage < 30) {
            progressBar.addClass('bg-danger');
        } else if (percentage < 70) {
            progressBar.addClass('bg-warning');
        } else {
            progressBar.addClass('bg-success');
        }

        // Actualizar detalles
        $('.progress-details').text(
            data.completedcount + ' de ' + data.activitycount + ' actividades'
        );

        // Mostrar con animación
        $('#courseindex-progress').fadeIn();
    }

    return {
        init: init
    };
});
```

---

## 5. COMPARATIVA DE IMPLEMENTACIONES

### 5.1 Tabla Comparativa

| Aspecto | Theme Compecer (Actual) | Theme Remui | Format Remuiformat | RECOMENDACIÓN |
|---------|------------------------|-------------|-------------------|---------------|
| **Progreso del curso** | ✅ Implementado | ✅ En myoverview block | ✅ Implementado | **Usar actual de Compecer** |
| **Progreso por sección** | ❌ No implementado | ❌ No implementado | ✅ Implementado | **Adoptar de Remuiformat** |
| **Ubicación** | Courseindex drawer | Block myoverview | Formato de curso | **Courseindex drawer** |
| **API usada** | `progress::get_course_progress_percentage()` | `course_summary_exporter` | Cálculo manual | **API de Moodle (más eficiente)** |
| **Actualización dinámica** | ✅ Con eventos | ⚠️ Sin verificar | ⚠️ Código comentado | **Eventos de Moodle** |
| **Desglose actividades** | ✅ Por tipo | ❌ No | ❌ No | **Mantener de Compecer** |
| **Estilos** | ✅ Modernos con animaciones | ✅ Bootstrap estándar | ✅ Tailwind-like | **Mejorar estilos Compecer** |
| **Caché** | ❌ No | ❌ No | ❌ No | **Considerar implementar** |

### 5.2 Análisis por Implementación

#### Compecer (Actual)

**Ventajas:**
- ✅ Integración nativa con courseindex drawer
- ✅ Usa API oficial de Moodle (más mantenible)
- ✅ Desglose por tipo de actividad
- ✅ Actualización dinámica funcional
- ✅ Estilos modernos con animaciones
- ✅ Accesibilidad ARIA implementada

**Desventajas:**
- ❌ No tiene progreso por sección individual
- ❌ No tiene indicadores de estado de actividades
- ❌ Diseño no coincide con mockup deseado

**Archivos clave:**
- `/theme/compecer/classes/external/get_course_progress.php`
- `/theme/compecer/amd/src/courseindex_progress.js`
- `/theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache`

#### Remui

**Ventajas:**
- ✅ Usa `course_summary_exporter` (datos pre-calculados)
- ✅ Integrado con core de Moodle

**Desventajas:**
- ❌ NO está en courseindex (está en block myoverview)
- ❌ No tiene progreso por sección
- ❌ Menos útil para nuestro caso

**Ubicación:**
- `/theme/remui/templates/block_myoverview/view-cards.mustache`
- NO es relevante para courseindex drawer

#### Format Remuiformat

**Ventajas:**
- ✅ Tiene progreso por sección individual
- ✅ Cálculo bien estructurado
- ✅ Separación clara de responsabilidades

**Desventajas:**
- ❌ Cálculo manual menos eficiente
- ❌ Actualización dinámica incompleta (código comentado)
- ❌ Diseñado para formato de curso, no drawer

**Lógica a extraer:**
- Método `get_section_module_info()` - Cálculo por sección
- Criterios de filtrado de actividades
- Estructura de datos de progreso

### 5.3 Diferencias Clave

#### Nivel de Granularidad

```
Compecer:
    Curso ───> 65% ✓
    Sección ───> N/A ✗
    Actividad ───> N/A ✗

Remui:
    Curso ───> 65% ✓
    Sección ───> N/A ✗
    Actividad ───> N/A ✗

Format Remuiformat:
    Curso ───> 65% ✓
    Sección ───> 60%, 33%, 0% ✓
    Actividad ───> N/A ✗

OBJETIVO (Mockup):
    Curso ───> 65% ✓ (NECESARIO)
    Sección ───> 60%, 33%, 0% ✓ (NECESARIO)
    Actividad ───> ○, ◐, ✓ ✓ (NECESARIO)
```

#### Método de Cálculo

**API de Moodle (Compecer/Remui):**
```php
$percentage = progress::get_course_progress_percentage($course);
// Ventaja: Mantenido por Moodle core, optimizado
```

**Manual (Format Remuiformat):**
```php
foreach ($modules as $mod) {
    if (/* criterios */) {
        $total++;
        if ($completion->get_data($mod)->completionstate == COMPLETION_COMPLETE) {
            $completed++;
        }
    }
}
$percentage = round(($completed / $total) * 100);
// Ventaja: Control total, personalizable
```

#### Ubicación de Renderizado

- **Compecer:** Courseindex drawer (✓ correcto)
- **Remui:** Block myoverview (✗ incorrecto para nuestro uso)
- **Remuiformat:** Página de curso (✗ incorrecto para nuestro uso)

---

## 6. RECOMENDACIONES FINALES

### 6.1 Estrategia de Implementación

**ENFOQUE HÍBRIDO RECOMENDADO:**

1. **Mantener de Compecer:**
   - Servicio web `theme_compecer_get_course_progress`
   - Módulo JavaScript `courseindex_progress.js`
   - Sistema de actualización dinámica con eventos
   - Estilos base con animaciones

2. **Adoptar de Format Remuiformat:**
   - Método `get_section_module_info()` para progreso por sección
   - Criterios de filtrado de actividades
   - Estructura de datos de progreso

3. **Crear Nuevo:**
   - Template `section.mustache` modificado con progreso
   - Template `cm.mustache` modificado con indicadores de estado
   - Estilos según mockup
   - Función para determinar estado de actividad (○, ◐, ✓)

### 6.2 Plan de Acción

#### Fase 1: Backend (PHP)

1. **Extender servicio web existente:**
   ```php
   // Añadir a theme/compecer/classes/external/get_course_progress.php

   public static function execute($courseid) {
       // ... código existente ...

       // NUEVO: Agregar progreso por sección
       $output['sections'] = [];
       foreach ($modinfo->get_section_info_all() as $section) {
           $sectionprogress = self::get_section_module_info($course, $section->id);
           $output['sections'][] = [
               'id' => $section->id,
               'number' => $section->section,
               'title' => get_section_name($course, $section),
               'progressinfo' => $sectionprogress
           ];
       }

       return $output;
   }

   private static function get_section_module_info($course, $sectionid) {
       // Implementar lógica de format_remuiformat
   }
   ```

2. **Crear función para estado de actividad:**
   ```php
   private static function get_activity_state($mod, $completion) {
       if (!isloggedin() || isguestuser()) {
           return 'notstarted';  // ○
       }

       if ($completion->is_enabled($mod) == COMPLETION_TRACKING_NONE) {
           return 'notracking';  // Sin indicador
       }

       $data = $completion->get_data($mod, true);

       if ($data->completionstate == COMPLETION_COMPLETE ||
           $data->completionstate == COMPLETION_COMPLETE_PASS) {
           return 'completed';  // ✓
       }

       // Verificar si ha iniciado
       if ($data->timemodified > 0) {
           return 'inprogress';  // ◐
       }

       return 'notstarted';  // ○
   }
   ```

#### Fase 2: Frontend (Templates)

1. **Modificar `drawer.mustache`:**
   - Mantener header de progreso global
   - Pasar datos de secciones con progreso

2. **Modificar `section.mustache`:**
   - Añadir badge de porcentaje en header
   - Opcional: mini-barra de progreso

3. **Modificar `cm.mustache`:**
   - Añadir indicador de estado (○, ◐, ✓)
   - Usar clases CSS según estado

#### Fase 3: JavaScript

1. **Extender `courseindex_progress.js`:**
   - Actualizar secciones además de progreso global
   - Actualizar indicadores de actividades
   - Mantener sistema de eventos

#### Fase 4: Estilos (SCSS/CSS)

1. **Crear estilos según mockup:**
   - Header de progreso global prominente
   - Badges de progreso por sección
   - Indicadores de estado de actividades
   - Animaciones y transiciones
   - Responsive design

### 6.3 Pseudocódigo del Flujo Completo

```
USUARIO ABRE COURSEINDEX DRAWER
  ↓
drawer.mustache renderiza con courseid
  ↓
JavaScript: courseindex_progress.init(courseid)
  ↓
AJAX: theme_compecer_get_course_progress(courseid)
  ↓
Backend:
  ├─ get_course_progress_percentage() → 65%
  ├─ foreach section:
  │   └─ get_section_module_info() → {60%, 33%, 0%}
  └─ foreach activity:
      └─ get_activity_state() → {completed, inprogress, notstarted}
  ↓
Retorna JSON:
{
  "courseprogress": {
    "percentage": 65,
    "completed": 13,
    "total": 20
  },
  "sections": [
    {
      "id": 1,
      "title": "Introducción",
      "progressinfo": {"percentage": 60, "completed": 3, "total": 5},
      "cms": [
        {"id": 1, "name": "Bienvenida", "state": "completed"},
        {"id": 2, "name": "Objetivos", "state": "inprogress"},
        {"id": 3, "name": "Conoce compañeros", "state": "notstarted"}
      ]
    }
  ]
}
  ↓
JavaScript: updateProgressUI(data)
  ├─ Actualiza barra global
  ├─ Actualiza badges de secciones
  └─ Actualiza iconos de actividades (○, ◐, ✓)
  ↓
RENDERIZADO COMPLETO SEGÚN MOCKUP
```

### 6.4 Ventajas del Enfoque Híbrido

| Ventaja | Descripción |
|---------|-------------|
| **Eficiencia** | Usa API de Moodle optimizada para progreso global |
| **Detalle** | Calcula progreso por sección individualmente |
| **Mantenibilidad** | Se basa en código estándar de Moodle |
| **Actualización** | Sistema de eventos ya implementado |
| **Escalabilidad** | Un solo servicio web para todos los datos |
| **UX** | Diseño coherente según mockup |

### 6.5 Consideraciones Técnicas

#### Performance

- **Caché:** Considerar cachear resultados (5 minutos)
  ```php
  $cache = cache::make('theme_compecer', 'courseprogress');
  $cachekey = "progress_{$courseid}_{$USER->id}";

  if ($data = $cache->get($cachekey)) {
      return $data;
  }

  // Calcular...
  $cache->set($cachekey, $output);
  ```

- **Lazy Loading:** Cargar progreso de secciones solo cuando se expanden

#### Accesibilidad

- Mantener atributos ARIA en barras de progreso
- Estados visuales claros (color + icono)
- Navegación por teclado
- Screen reader friendly

#### Responsive

- Diseño adaptable en móviles
- Colapsado por defecto en pantallas pequeñas
- Touch-friendly (áreas de clic grandes)

### 6.6 Riesgos y Mitigaciones

| Riesgo | Impacto | Mitigación |
|--------|---------|-----------|
| Performance con muchos módulos | Alto | Implementar caché |
| Inconsistencia de datos | Medio | Usar transacciones de BD |
| Cambios en API de Moodle | Bajo | Usar métodos documentados |
| Conflictos con otros plugins | Bajo | Namespacing correcto |

---

## CONCLUSIÓN

Para implementar el courseindex según el mockup proporcionado, se recomienda:

1. ✅ **Mantener** la implementación actual de progreso global de Compecer
2. ✅ **Adoptar** la lógica de progreso por sección de Format Remuiformat
3. ✅ **Crear** sistema de indicadores de estado de actividades
4. ✅ **Rediseñar** templates para coincidir con mockup
5. ✅ **Optimizar** con caché y lazy loading

Este enfoque híbrido combina lo mejor de ambos sistemas analizados, manteniendo compatibilidad con Moodle core y proporcionando la funcionalidad completa solicitada.

---

**Siguiente paso:** FASE 5 - Diseñar estructura detallada de templates Mustache según mockup
