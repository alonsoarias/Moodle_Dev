# LOCALIZACIONES EXACTAS - SELECTORES COURSEINDEX

Documento de referencia para ubicar rápidamente selectores y propiedades

---

## ARCHIVO: /theme/compecer/scss/compecer.scss

### SECCIÓN: COURSE INDEX - DISEÑO REFINADO Y MEJORADO

**Línea 1439:** Comentario de sección (inicio)

#### .course-index-section (Líneas 1441-1458)
Componente contenedor principal de sección
- Propiedades: position, background-color, border, border-radius, margin, transition, width, overflow
- Estados: :hover, .current

#### .course-index-header (Líneas 1460-1477)
Header de la sección
- Propiedades: background-color, padding, display, align-items, position, width, border-bottom
- Estados: .dimmed, ::after

#### .course-index-link (Líneas 1479-1495)
Enlace de sección
- Propiedades: color, padding, flex-grow, font-size, font-weight, text-decoration, transition, margin-right, letter-spacing
- Estados: :hover, :focus

#### .course-index-toggle (Líneas 1497-1528)
Botón de expandir/contraer
- Propiedades: min-width, padding, margin-right, background, border, color, cursor, display, transition
- Sub-selectores: i (icon)
- Estados: :hover, :focus, .collapsed, :not(.collapsed)

#### .course-index-content (Líneas 1530-1536)
Contenedor de contenido
- Propiedades: background-color, width
- Estados: .collapse:not(.show)

#### .course-index-section-content (Líneas 1538-1543)
Lista de items
- Propiedades: list-style, margin, padding, width

#### .course-index-item (Líneas 1545-1602)
Item individual
- Propiedades: width, margin
- Sub-selectores: a, i
- Estados: :hover, .active, .completed

**Media Query (Líneas 1604-1629):** max-width: 768px
- Ajustes para tablet/mobile

**Accessibility (Líneas 1631-1641):** prefers-reduced-motion
- Deshabilitación de transiciones y transformaciones

---

### SECCIÓN: COURSE INDEX PROGRESS BAR

**Línea 1648:** Comentario de sección (inicio)

#### .courseindex-progress-container (Líneas 1652-1660)
Contenedor del progress bar
- Propiedades: background (gradient), border-radius, padding, margin, box-shadow, transition, border-left
- Estados: :hover

#### .courseindex-progress-container .progress-header (Líneas 1668-1672)
Header del progress
- Propiedades: display, flex-direction, gap

#### .courseindex-progress-container .progress-title (Líneas 1674-1681)
Título del progress
- Propiedades: font-size, font-weight, color, margin, text-transform, letter-spacing

#### .courseindex-progress-container .progress-stats (Líneas 1683-1687)
Estadísticas
- Propiedades: display, justify-content, align-items

#### .courseindex-progress-container .progress-percentage (Líneas 1689-1693)
Porcentaje
- Propiedades: font-size, color, font-weight

#### .courseindex-progress-container .progress-details (Líneas 1695-1698)
Detalles
- Propiedades: font-size, color

#### .courseindex-progress-container .progress (Líneas 1701-1706)
Barra de progreso base
- Propiedades: height, border-radius, background-color, overflow

#### .courseindex-progress-container .progress-bar (Líneas 1708-1714)
Barra de progreso fill
- Propiedades: transition, height, border-radius, position, overflow
- Sub-elemento: ::after (shimmer animation)

#### @keyframes shimmer (Líneas 1733-1740)
Animación de shimmer
- Keyframes: 0%, 100%
- Efecto: translateX

#### .courseindex-progress-container .progress-activity-list (Líneas 1743-1749)
Lista de actividades
- Propiedades: list-style, padding, margin, font-size, color

#### .courseindex-progress-container .progress-activity-list li (Líneas 1751-1764)
Items de lista
- Propiedades: padding, position
- Sub-elemento: ::before (bullet)

**Media Query (Líneas 1767-1780):** max-width: 768px
- Ajustes para mobile

**Accessibility (Líneas 1782-1790):** prefers-reduced-motion
- Deshabilitación de transiciones y animaciones

---

### SECCIÓN: DRAWER BASE (COMPECER THEME)

#### #nav-drawer (Líneas 26-66)
Drawer de navegación principal
- Propiedades: background-color, padding
- Sub-selectores complejos para navegación anidada
- Estados: .closed:not(:hover)

#### .drawer (Línea 1430-1436)
Drawer genérico
- Sub-selector: .scrolled
- Sub-sub-selector: .drawerheader

---

### SECCIÓN: VARIANTE COMPECER-CO (TEMA COLOMBIA)

#### body.compecer-co #nav-drawer (Líneas 429-488)
Estilos específicos para variante compecer-co
- Múltiples sub-selectores anidados
- Estados y variaciones específicas para tema Colombia

---

## ARCHIVO: /theme/compecer/scss/custom_variables.scss

### Líneas 1-28: Paleta de Colores

```scss
$primary-red:    #e21144     (Línea 10)
$secondary-red:  #be0a37     (Línea 11)
$primary-blue:   #365ba3     (Línea 14)
$title-blue:     #345ba7     (Línea 15)
$footer-blue:    #2a3462     (Línea 16)
$gray:           #5e5e5e     (Línea 19)
$white:          #FFFFFF     (Línea 22)
$black:          #000000     (Línea 23)
$light-bg:       #F9F9F9     (Línea 24)
$yellow:         #ffb000     (Línea 26)
```

### Líneas 32-34: Tipografía

```scss
$font-family-sans-serif: 'Roboto', sans-serif  (Línea 32)
$headings-font-family:   $font-family-sans-serif (Línea 33)
```

### Líneas 38-39: Dimensiones

```scss
$navbar-height:         80px   (Línea 38)
$my-loginfooterheight:  80px   (Línea 39)
```

### Líneas 55-58: Color del Texto

```scss
$link-color:         $primary-blue      (Línea 55)
$text-color:         $gray              (Línea 57)
$heading-color:      $title-blue        (Línea 58)
```

### Líneas 69-70: Bordes y Sombras

```scss
$border-color:       lighten($gray, 40%)        (Línea 69)
$box-shadow:         0 2px 4px rgba(0,0,0,0.1)  (Línea 70)
```

### Líneas 86-91: Variables Locales de CourseIndex

```scss
$course-index-border-width: 1px              (Línea 86)
$course-index-spacing: 1rem                  (Línea 87)
$course-index-icon-size: 1.25rem             (Línea 88)
$primary-blue-hover: darken($primary-blue, 10%)    (Línea 89)
$primary-blue-dark: darken($primary-blue, 15%)     (Línea 90)
$footer-blue-dark: darken($footer-blue, 10%)       (Línea 91)
```

---

## ARCHIVO: /theme/compecer/scss/_variables.scss

### Variables Heredadas (Legacy de Moove)

Línea 7-48: Variables de colores legacy
- $color-blue, $color-orange, $color-yellow, etc.
- NO se usan directamente en courseindex pero afectan el tema

Línea 51: $nav-drawer-color
- Puede afectar comportamiento del drawer

---

## TABLA RESUMEN DE UBICACIONES

| Selector | Archivo | Líneas | Propiedades |
|----------|---------|--------|-------------|
| .course-index-section | compecer.scss | 1441-1458 | Base container |
| .course-index-header | compecer.scss | 1460-1477 | Header styling |
| .course-index-link | compecer.scss | 1479-1495 | Link styling |
| .course-index-toggle | compecer.scss | 1497-1528 | Toggle button |
| .course-index-content | compecer.scss | 1530-1536 | Content wrapper |
| .course-index-section-content | compecer.scss | 1538-1543 | Item list |
| .course-index-item | compecer.scss | 1545-1602 | Item container |
| .courseindex-progress-container | compecer.scss | 1652-1660 | Progress wrapper |
| .progress-* (all) | compecer.scss | 1668-1764 | Progress components |
| #nav-drawer | compecer.scss | 26-66, 429-488 | Drawer styling |
| .drawer | compecer.scss | 1430-1436 | Generic drawer |
| Variables Color | custom_variables.scss | 1-28 | Color palette |
| Variables Local | custom_variables.scss | 86-91 | Component vars |

---

## BÚSQUEDA RÁPIDA POR FUNCIONALIDAD

### Colores
- Archivo: `custom_variables.scss`
- Líneas: 1-28
- Variables: $primary-blue, $primary-red, $yellow, etc.

### Espaciados
- Archivo: `compecer.scss`
- Selectores: Inline en cada componente
- Valores: padding (px), margin (rem)

### Animaciones
- Archivo: `compecer.scss`
- Líneas: 1708-1714 (progress-bar con transition)
- Líneas: 1733-1740 (@keyframes shimmer)
- Líneas: 1516 (toggle icon transition)

### Estados (Hover/Active)
- Archivo: `compecer.scss`
- Líneas: 1450-1453 (.course-index-section:hover)
- Líneas: 1558-1576 (.course-index-item a:hover, a.active)
- Líneas: 1662-1665 (.courseindex-progress-container:hover)

### Responsivo (Mobile)
- Archivo: `compecer.scss`
- Líneas: 1604-1629 (course-index mobile)
- Líneas: 1767-1780 (progress-bar mobile)
- Línea: 1632 (@media prefers-reduced-motion)

### Drawer
- Archivo: `compecer.scss`
- Líneas: 26-66 (#nav-drawer base)
- Líneas: 429-488 (body.compecer-co #nav-drawer)
- Líneas: 1430-1436 (.drawer generic)

---

## NOTAS DE EDICIÓN

### Si necesitas modificar...

**Colores globales:**
- Editar: `/theme/compecer/scss/custom_variables.scss` (líneas 1-28)
- Afecta: Todos los componentes courseindex

**Tipografía de courseindex:**
- Editar: `compecer.scss` líneas 1483, 1612, 1674, etc.
- O variable global en custom_variables.scss

**Animaciones/Transiciones:**
- Editar: `compecer.scss` 
- Principales: líneas 1708-1714, 1733-1740, 1516

**Breakpoint responsivo:**
- Editar: `compecer.scss` línea 1604 (@media max-width: 768px)

**Progress bar específico:**
- Editar: `compecer.scss` líneas 1652-1790

**Estados hover/active:**
- Editar: `compecer.scss` líneas específicas según componente

---

## VARIABLES SCSS COMÚNMENTE USADAS

```scss
// Colores
$primary-blue: #365ba3          // Headers, nav
$primary-red: #e21144           // Hover, active
$yellow: #ffb000                // Active borders
$white: #FFFFFF                 // Fondos
$gray: #5e5e5e                  // Textos

// Dimensiones
$navbar-height: 80px
$course-index-spacing: 1rem

// Tipografía
$font-family-sans-serif: 'Roboto', sans-serif

// Funciones SCSS
darken($primary-blue, 10%)      // Colores más oscuros
lighten($gray, 40%)             // Colores más claros
rgba(255, 255, 255, 0.08)       // Con transparencia
```

---

**Documento de Localización Completo**
Generado: 2025-10-27
Versión: 1.0

