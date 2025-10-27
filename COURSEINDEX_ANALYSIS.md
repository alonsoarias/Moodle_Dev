# ANÁLISIS EXHAUSTIVO DE ESTILOS COURSEINDEX EN THEME COMPECER

## Resumen Ejecutivo

Se ha realizado un análisis completo de los estilos CSS/SCSS relacionados con courseindex en el tema compecer de Moodle. Se han identificado **42 selectores CSS** distribuidos en dos archivos principales, con énfasis en diseño responsivo, animaciones y accesibilidad.

---

## 1. ARCHIVOS ANALIZADOS

| Archivo | Tamaño | Líneas | Estado |
|---------|--------|--------|--------|
| `/theme/compecer/scss/compecer.scss` | Principal | 1166 | Contiene estilos básicos y compecer-co |
| `/theme/compecer/style/custom.css` | Procesado | 1791 | Contiene courseindex y progress bar |
| `/theme/compecer/scss/custom_variables.scss` | Variables | 95 | Paleta de colores y dimensiones |
| `/theme/compecer/scss/_variables.scss` | Heredadas | 104 | Variables legacy de moove |

---

## 2. SELECTORES CSS RELACIONADOS CON COURSEINDEX

### 2.1 COMPONENTES PRINCIPALES

#### A. `.course-index-section` (Línea 1441)
**Propiedades:**
- `position: relative`
- `background-color: $white` (FFFFFF)
- `border: 1px solid $border-color` (lighten($gray, 40%))
- `border-radius: 6px`
- `margin: 0 auto 0.75rem`
- `transition: all 0.25s ease-in-out`
- `width: 95%`
- `overflow: hidden`

**Estados:**
```scss
&:hover {
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05);
  transform: translateY(-1px);
}

&.current {
  border-color: rgba($primary-blue, 0.3);
  box-shadow: 0 2px 12px rgba($primary-blue, 0.08);
}
```

**Responsivo (max-width: 768px):**
- `width: 98%`
- `margin-bottom: 0.5rem`
- `border-radius: 4px`

---

#### B. `.course-index-header` (Línea 1460)
**Propiedades:**
- `background-color: $primary-blue` (#365ba3)
- `padding: 0`
- `display: flex`
- `align-items: stretch`
- `position: relative`
- `width: 100%`
- `border-bottom: 1px solid rgba($white, 0.1)`

**Estados:**
```scss
&.dimmed {
  opacity: 0.85;
  background-color: rgba($primary-blue, 0.95);
}

&::after {
  content: '';
  width: 12px;
  background-color: transparent;
}
```

**Anidamiento:**
- Contiene `.course-index-link` y `.course-index-toggle`
- Altura flexible según contenido

---

#### C. `.course-index-link` (Línea 1479)
**Propiedades:**
- `color: $white`
- `padding: 14px 18px`
- `flex-grow: 1`
- `font-size: 0.95rem`
- `font-weight: 500`
- `text-decoration: none`
- `transition: all 0.2s ease`
- `margin-right: auto`
- `letter-spacing: 0.2px`

**Estados:**
```scss
&:hover,
&:focus {
  background-color: rgba($white, 0.08);
  color: $white;
  text-decoration: none;
}
```

**Responsivo (max-width: 768px):**
- `padding: 12px 14px`
- `font-size: 0.875rem`

---

#### D. `.course-index-toggle` (Línea 1497)
**Propiedades:**
- `min-width: 46px`
- `padding: 0 10px`
- `margin-right: 6px`
- `background: transparent`
- `border: none`
- `color: rgba($white, 0.95)`
- `cursor: pointer`
- `display: flex`
- `align-items: center`
- `justify-content: center`
- `transition: all 0.2s ease`

**Ícono Anidado (i):**
```scss
i {
  font-size: 0.875rem;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

&.collapsed i {
  transform: rotate(0deg);
}

&:not(.collapsed) i {
  transform: rotate(180deg);
}
```

**Estados:**
```scss
&:hover,
&:focus {
  background-color: rgba($white, 0.08);
  color: $white;
}
```

**Responsivo (max-width: 768px):**
- `min-width: 40px`
- `padding: 0 8px`
- `margin-right: 4px`

---

#### E. `.course-index-content` (Línea 1530)
**Propiedades:**
- `background-color: $white`
- `width: 100%`

**Estados:**
```scss
&.collapse:not(.show) {
  display: none;
}
```

---

#### F. `.course-index-section-content` (Línea 1538)
**Propiedades:**
- `list-style: none`
- `margin: 0`
- `padding: 4px 0`
- `width: 100%`

---

#### G. `.course-index-item` (Línea 1545)
**Propiedades:**
- `width: 95%`
- `margin: 2px auto`

**Anidamiento (a):**
```scss
a {
  display: flex;
  align-items: center;
  padding: 10px 16px;
  color: $text-color (#5e5e5e)
  text-decoration: none;
  transition: all 0.2s ease;
  border-left: 3px solid transparent;
  border-radius: 0 4px 4px 0;
  width: 100%;
}
```

**Estados del Link:**
```scss
a:hover {
  background-color: rgb(226 17 68 / .05); // Rojo al 5%
  color: $primary-red (#e21144)
  border-left-color: $yellow (#ffb000)
  transform: translateX(2px);
  
  i {
    transform: translateX(2px);
    color: rgba($primary-red, 0.8);
  }
}

a.active {
  background-color: rgba($primary-red, 0.08);
  color: $primary-red (#e21144)
  border-left-color: $yellow (#ffb000)
  font-weight: 500;
  
  i {
    color: $yellow (#ffb000)
  }
}
```

**Ícono Anidado (i):**
```scss
i {
  margin-right: 12px;
  font-size: 1rem;
  width: 20px;
  text-align: center;
  color: rgba($text-color, 0.7);
  transition: all 0.2s ease;
}
```

**Estado Completado:**
```scss
&.completed a {
  color: rgba($text-color, 0.75);
  
  i {
    color: rgba($primary-blue, 0.6);
  }
  
  &:hover {
    background-color: rgb(226 17 68 / .05);
    color: $primary-red;
    border-left-color: $yellow;
    
    i {
      color: rgba($primary-red, 0.8);
    }
  }
}
```

**Responsivo (max-width: 768px):**
```scss
.course-index-item {
  width: 98%;
  
  a {
    padding: 10px 14px;
    font-size: 0.875rem;
    
    i {
      margin-right: 10px;
    }
  }
}
```

---

### 2.2 COMPONENTE PROGRESS BAR

#### H. `.courseindex-progress-container` (Línea 1652)
**Propiedades:**
- `background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(249, 250, 251, 0.95))`
- `border-radius: 12px`
- `padding: 1rem`
- `margin: 0 0.5rem 1rem`
- `box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08)`
- `transition: all 0.3s ease`
- `border-left: 4px solid #001f40` (Azul oscuro)

**Estado Hover:**
```scss
&:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  transform: translateY(-2px);
}
```

**Responsivo (max-width: 768px):**
- `padding: 0.75rem`
- `margin: 0 0.25rem 0.75rem`

---

#### I. `.courseindex-progress-container .progress-header` (Línea 1668)
**Propiedades:**
- `display: flex`
- `flex-direction: column`
- `gap: 0.5rem`

---

#### J. `.courseindex-progress-container .progress-title` (Línea 1674)
**Propiedades:**
- `font-size: 0.875rem`
- `font-weight: 700`
- `color: #001f40`
- `margin: 0`
- `text-transform: uppercase`
- `letter-spacing: 0.5px`

**Responsivo (max-width: 768px):**
- `font-size: 0.8125rem`

---

#### K. `.courseindex-progress-container .progress-stats` (Línea 1683)
**Propiedades:**
- `display: flex`
- `justify-content: space-between`
- `align-items: center`

---

#### L. `.courseindex-progress-container .progress-percentage` (Línea 1689)
**Propiedades:**
- `font-size: 1.5rem`
- `color: #001f40`
- `font-weight: 700`

**Responsivo (max-width: 768px):**
- `font-size: 1.25rem`

---

#### M. `.courseindex-progress-container .progress-details` (Línea 1695)
**Propiedades:**
- `font-size: 0.75rem`
- `color: #666`

---

#### N. `.courseindex-progress-container .progress` (Línea 1701)
**Propiedades:**
- `height: 8px`
- `border-radius: 10px`
- `background-color: rgba(102, 102, 102, 0.15)`
- `overflow: hidden`

---

#### O. `.courseindex-progress-container .progress-bar` (Línea 1708)
**Propiedades:**
- `transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1)`
- `height: 100%`
- `border-radius: 10px`
- `position: relative`
- `overflow: hidden`

**Animación (::after):**
```scss
&::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  bottom: 0;
  right: 0;
  background: linear-gradient(
    90deg,
    transparent,
    rgba(255, 255, 255, 0.3),
    transparent
  );
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}
```

---

#### P. `.courseindex-progress-container .progress-activity-list` (Línea 1743)
**Propiedades:**
- `list-style: none`
- `padding: 0`
- `margin: 0.75rem 0 0 0`
- `font-size: 0.75rem`
- `color: #333`

---

#### Q. `.courseindex-progress-container .progress-activity-list li` (Línea 1751)
**Propiedades:**
- `padding: 0.25rem 0`
- `padding-left: 1.25rem`
- `position: relative`

**Pseudo-elemento (::before):**
```scss
&::before {
  content: '•';
  position: absolute;
  left: 0;
  color: #001f40;
  font-weight: bold;
  font-size: 1rem;
}
```

---

### 2.3 SELECTORES RELACIONADOS - DRAWER

#### R. `#nav-drawer` (Línea 26)
**Propiedades Principales:**
- `background-color: $primary-blue` (#365ba3)
- `padding: 0`

**Anidamiento:**
```scss
#nav-drawer {
  &.closed:not(:hover) {
    opacity: 1;
  }
  
  .list-group {
    .list-group-item {
      a {
        font-size: 105%;
        line-height: auto;
      }
      &:hover {
        background-color: darken($primary-blue, 10%);
      }
    }
  }
  
  .metismenu {
    > li {
      &:hover {
        ul.collapse.in {
          background: darken($primary-blue, 15%);
        }
      }
    }
  }
}
```

---

#### S. `.drawer` (Línea 1430)
**Propiedades:**
```scss
.drawer {
  &.scrolled {
    .drawerheader {
      box-shadow: none;
    }
  }
}
```

---

### 2.4 SELECTORES COMPECER-CO (Tema Colombia)

#### T. `body.compecer-co #nav-drawer` (Línea 429)
**Propiedades específicas:**
```scss
.list-group {
  .list-group-item {
    a {
      color: $white;
    }
    &:hover > a {
      color: $white;
    }
  }
}
```

---

## 3. ANÁLISIS DE PATRONES DE DISEÑO

### 3.1 PALETA DE COLORES

**Colores Primarios:**
```
$primary-red:     #e21144    (Rojo destacado - enlaces hover/activos)
$secondary-red:   #be0a37    (Rojo oscuro - hover states)
$primary-blue:    #365ba3    (Azul principal - headers, nav)
$title-blue:      #345ba7    (Azul títulos)
$footer-blue:     #2a3462    (Azul oscuro - footer)
$yellow:          #ffb000    (Amarillo - bordes activos)
$white:           #FFFFFF    (Fondo)
$black:           #000000    (Texto)
$gray:            #5e5e5e    (Texto secundario)
```

**Colores Utilizados en CourseIndex:**
| Elemento | Color | Hex |
|----------|-------|-----|
| Header | primary-blue | #365ba3 |
| Texto Header | white | #FFFFFF |
| Fondo Contenido | white | #FFFFFF |
| Borde Activo | yellow | #ffb000 |
| Hover Item | primary-red | #e21144 |
| Borde Left Progress | Azul oscuro | #001f40 |
| Ícono Completado | primary-blue | #365ba3 |

---

### 3.2 ESPACIADOS Y DIMENSIONES

**Márgenes y Paddings:**
```
.course-index-section:
  - margin: 0 auto 0.75rem (vertical spacing)
  - width: 95% (content width)

.course-index-header:
  - padding: 0 (no internal padding)

.course-index-link:
  - padding: 14px 18px
  - letter-spacing: 0.2px

.course-index-item a:
  - padding: 10px 16px
  - border-left: 3px solid

.courseindex-progress-container:
  - padding: 1rem
  - margin: 0 0.5rem 1rem
```

**Alturas:**
```
Progress bar: 8px
Icon size: 1rem (16px default)
Section height: Auto (flexible)
```

---

### 3.3 TIPOGRAFÍA

**Fuentes utilizadas:**
- Familia: 'Roboto', sans-serif
- `headings-font-family: 'Roboto', sans-serif`

**Tamaños en CourseIndex:**
```
.course-index-link:
  - font-size: 0.95rem
  - font-weight: 500

.course-index-item a:
  - Hereda tamaño
  - font-weight: 500 (activo)

.progress-title:
  - font-size: 0.875rem (uppercase)
  - font-weight: 700

.progress-percentage:
  - font-size: 1.5rem
  - font-weight: 700
```

---

### 3.4 TRANSICIONES Y ANIMACIONES

**Transiciones:**
```scss
.course-index-section {
  transition: all 0.25s ease-in-out;
}

.course-index-link {
  transition: all 0.2s ease;
}

.course-index-toggle i {
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.courseindex-progress-container {
  transition: all 0.3s ease;
}

.courseindex-progress-container .progress-bar {
  transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
```

**Animaciones:**
```scss
@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

// Aplicada a: .progress-bar::after (2s infinite)
```

**Transformaciones:**
```
.course-index-section:hover:
  - transform: translateY(-1px)

.course-index-item a:hover:
  - transform: translateX(2px)

.courseindex-progress-container:hover:
  - transform: translateY(-2px)

.course-index-toggle:
  - rotate(0deg) a rotate(180deg)
```

---

### 3.5 EFECTOS HOVER/FOCUS

**CourseIndex Section Hover:**
```
box-shadow: 0 3px 8px rgba(0, 0, 0, 0.05)
transform: translateY(-1px)
```

**CourseIndex Link Hover:**
```
background-color: rgba(255, 255, 255, 0.08)
color: white
text-decoration: none
```

**CourseIndex Item Hover:**
```
background-color: rgb(226 17 68 / .05) [Rojo al 5%]
color: #e21144 [primary-red]
border-left-color: #ffb000 [yellow]
transform: translateX(2px)
```

**Progress Container Hover:**
```
box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12)
transform: translateY(-2px)
```

---

### 3.6 SOMBRAS Y BORDES

**Box Shadows:**
```
.course-index-section:
  - Default: ninguno
  - Hover: 0 3px 8px rgba(0, 0, 0, 0.05)
  - Current: 0 2px 12px rgba(#365ba3, 0.08)

.courseindex-progress-container:
  - Default: 0 2px 8px rgba(0, 0, 0, 0.08)
  - Hover: 0 4px 12px rgba(0, 0, 0, 0.12)
```

**Bordes:**
```
.course-index-section:
  - 1px solid lighten($gray, 40%)

.course-index-header:
  - 1px solid rgba(255, 255, 255, 0.1)

.course-index-item a:
  - border-left: 3px solid transparent
  - Cambios en hover/active

.courseindex-progress-container:
  - border-left: 4px solid #001f40
```

---

### 3.7 BORDES REDONDEADOS

```
.course-index-section:
  - 6px (desktop)
  - 4px (mobile)

.course-index-item a:
  - border-radius: 0 4px 4px 0 (solo lados derechos)

.courseindex-progress-container:
  - border-radius: 12px

.progress:
  - border-radius: 10px
```

---

## 4. ANÁLISIS RESPONSIVO

### 4.1 PUNTOS DE QUIEBRE IDENTIFICADOS

**max-width: 768px** - Tablet/Mobile
```scss
.course-index-section {
  width: 98%;
  margin-bottom: 0.5rem;
}

.course-index-link {
  padding: 12px 14px;
  font-size: 0.875rem;
}

.courseindex-progress-container {
  padding: 0.75rem;
  margin: 0 0.25rem 0.75rem;
}
```

---

## 5. ACCESIBILIDAD

### 5.1 PREFERS-REDUCED-MOTION

```scss
@media (prefers-reduced-motion: reduce) {
  .course-index-section,
  .course-index-link,
  .course-index-toggle,
  .course-index-item a,
  .course-index-item a i {
    transition: none;
    transform: none;
  }
  
  .courseindex-progress-container,
  .courseindex-progress-container .progress-bar,
  .courseindex-progress-container .progress-bar::after {
    transition: none;
    animation: none;
  }
}
```

---

## 6. VARIABLES SCSS UTILIZADAS

### 6.1 VARIABLES LOCALES DEL COMPONENTE

```scss
$course-index-border-width: 1px
$course-index-spacing: 1rem
$course-index-icon-size: 1.25rem
$primary-blue-hover: darken($primary-blue, 10%)
$primary-blue-dark: darken($primary-blue, 15%)
$footer-blue-dark: darken($footer-blue, 10%)
```

### 6.2 VARIABLES HEREDADAS

```scss
De custom_variables.scss:
$primary-red: #e21144
$secondary-red: #be0a37
$primary-blue: #365ba3
$title-blue: #345ba7
$footer-blue: #2a3462
$gray: #5e5e5e
$white: #FFFFFF
$black: #000000
$light-bg: #F9F9F9
$yellow: #ffb000
```

---

## 7. ANIDAMIENTO SCSS

### 7.1 ESTRUCTURA DEL COURSE-INDEX

```scss
.course-index-section
  ├── .course-index-header
  │   ├── .course-index-link (a)
  │   └── .course-index-toggle (button)
  │       └── i (icon)
  └── .course-index-content
      └── .course-index-section-content (ul)
          └── .course-index-item
              └── a
                  ├── i (icon)
                  └── Texto
```

### 7.2 ESTRUCTURA DEL PROGRESS BAR

```scss
.courseindex-progress-container
  ├── .progress-header
  │   ├── .progress-title (h6)
  │   └── .progress-stats
  │       ├── .progress-percentage
  │       └── .progress-details
  ├── .progress
  │   └── .progress-bar
  │       └── ::after (shimmer animation)
  └── .progress-activity-list
      └── li
          └── ::before (bullet point)
```

---

## 8. MIXINS SCSS UTILIZADOS

```scss
@mixin transition($property: all, $duration: 0.3s, $ease: ease) {
  transition: $property $duration $ease;
}

@mixin border-radius($radius) {
  border-radius: $radius;
}
```

Utilizados en courseindex:
- `@include transition(all, 0.25s, ease-in-out);`
- `@include border-radius(6px);`
- `@include border-radius(0 4px 4px 0);`

---

## 9. SELECTORES ESPECIALES Y PSEUDOCLASES

**Estados Complejos:**
```
.course-index-section.current
.course-index-header.dimmed
.course-index-toggle.collapsed
.course-index-content.collapse:not(.show)
.course-index-item a.active
.course-index-item.completed
.courseindex-progress-container::after
.progress-bar::after
.progress-activity-list li::before
```

**Selectores Combinados:**
```
.course-index-header::after (pseudoelemento)
.course-index-item a:hover (estado compuesto)
.course-index-toggle i (selector de descendencia)
body.compecer-co #nav-drawer (contexto específico)
```

---

## 10. CONCLUSIONES Y OBSERVACIONES

### 10.1 FORTALEZAS DEL DISEÑO

1. **Jerarquía Visual Clara:**
   - Uso consistente de colores primarios y secundarios
   - Contraste suficiente para accesibilidad
   - Estados (hover, active, completed) bien diferenciados

2. **Responsividad Completa:**
   - Diseño fluid con breakpoints en 768px
   - Adjustments de padding, font-size y width
   - Mantenimiento de funcionalidad en todos los tamaños

3. **Accesibilidad:**
   - Soporte para `prefers-reduced-motion`
   - Transiciones fluidas pero no excesivas
   - Indicadores visuales claros (bordes, colores, iconos)

4. **Animaciones Refinadas:**
   - Uso de cubic-bezier para interpolación natural
   - Shimmer animation en progress bar (efecto profesional)
   - Transformaciones sutiles (translateX, translateY)

### 10.2 PATRONES IDENTIFICADOS

1. **Patrón de Hover Gradient:**
   - Items responden con cambio de fondo + borde + movimiento
   - Color de rojo (#e21144) para acciones
   - Borde amarillo (#ffb000) para indicación activa

2. **Patrón de Espaciado:**
   - Márgenes verticales de 0.75rem/1rem
   - Márgenes horizontales auto-centered
   - Padding consistente en componentes relacionados

3. **Patrón de Bordes Direccionales:**
   - Uso de `border-left` para indicación de estado
   - Posición estratégica (izquierda) en items
   - Color dinámico según estado

### 10.3 RECOMENDACIONES

1. **Variables Locales:**
   - Considerar extraer valores mágicos (14px, 18px, etc.) a variables
   - Centralizar valores de shadow, border-radius

2. **Mixins Adicionales:**
   - Crear mixin para estados hover comunes
   - Mixin para responsive text sizes

3. **Consistencia:**
   - Unificar timing de transiciones (0.2s vs 0.25s vs 0.3s)
   - Documentar excepciones de diseño (cubic-bezier específicos)

---

## 11. REFERENCIA RÁPIDA DE ARCHIVOS

| Tarea | Ubicación | Líneas |
|-------|-----------|--------|
| Course Index Principal | `/theme/compecer/scss/compecer.scss` | 1441-1641 |
| Progress Bar | `/theme/compecer/scss/compecer.scss` | 1652-1790 |
| Drawer Base | `/theme/compecer/scss/compecer.scss` | 26-66 |
| Nav Drawer | `/theme/compecer/scss/compecer.scss` | 429-488 |
| Variables Color | `/theme/compecer/scss/custom_variables.scss` | 1-28 |
| Dimensiones | `/theme/compecer/scss/custom_variables.scss` | 36-39 |

---

## 12. APÉNDICE - SELECTORES COMPLETOS

### Lista Completa de Selectores CourseIndex

1. `.course-index-section`
2. `.course-index-section:hover`
3. `.course-index-section.current`
4. `.course-index-header`
5. `.course-index-header.dimmed`
6. `.course-index-header::after`
7. `.course-index-link`
8. `.course-index-link:hover`
9. `.course-index-link:focus`
10. `.course-index-toggle`
11. `.course-index-toggle:hover`
12. `.course-index-toggle:focus`
13. `.course-index-toggle.collapsed`
14. `.course-index-toggle.collapsed i`
15. `.course-index-toggle:not(.collapsed) i`
16. `.course-index-toggle i`
17. `.course-index-content`
18. `.course-index-content.collapse:not(.show)`
19. `.course-index-section-content`
20. `.course-index-item`
21. `.course-index-item a`
22. `.course-index-item a:hover`
23. `.course-index-item a:focus`
24. `.course-index-item a.active`
25. `.course-index-item a.active i`
26. `.course-index-item a i`
27. `.course-index-item a i:hover`
28. `.course-index-item.completed`
29. `.course-index-item.completed a`
30. `.course-index-item.completed a:hover`
31. `.course-index-item.completed a i`
32. `.courseindex-progress-container`
33. `.courseindex-progress-container:hover`
34. `.courseindex-progress-container .progress-header`
35. `.courseindex-progress-container .progress-title`
36. `.courseindex-progress-container .progress-stats`
37. `.courseindex-progress-container .progress-percentage`
38. `.courseindex-progress-container .progress-details`
39. `.courseindex-progress-container .progress`
40. `.courseindex-progress-container .progress-bar`
41. `.courseindex-progress-container .progress-bar::after`
42. `.courseindex-progress-container .progress-activity-list`
43. `.courseindex-progress-container .progress-activity-list li`
44. `.courseindex-progress-container .progress-activity-list li::before`

---

**Documento Generado:** Análisis Exhaustivo de CourseIndex Styles - Compecer Theme
**Fecha:** 2025-10-27
**Versión:** 1.0
