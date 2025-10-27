# ÍNDICE COMPLETO - ANÁLISIS DE ESTILOS COURSEINDEX

Generado: 2025-10-27
Versión: 1.0

---

## DOCUMENTOS DISPONIBLES

### 1. COURSEINDEX_ANALYSIS.md (21 KB)
**Análisis exhaustivo y detallado**

Contenido:
- Resumen ejecutivo
- Descripción completa de 44 selectores CSS
- Análisis de paleta de colores
- Espaciados y dimensiones
- Tipografía
- Transiciones y animaciones
- Efectos hover/focus
- Sombras y bordes
- Análisis responsivo
- Accesibilidad (prefers-reduced-motion)
- Variables SCSS utilizadas
- Anidamiento SCSS
- Mixins
- Selectores especiales y pseudoclases
- Conclusiones y recomendaciones
- Referencia rápida de archivos

**Mejor para:** Desarrolladores que necesitan entender la arquitectura completa

---

### 2. COURSEINDEX_SUMMARY.md (8.4 KB)
**Referencia visual rápida y práctica**

Contenido:
- Matriz de estructura de componentes (ASCII tree)
- Tarjeta de referencia de colores
- Puntos de quiebre responsivos
- Timing de animaciones y transiciones
- Patrones de sombras y bordes
- Estados visuales de iconos
- Escala tipográfica
- Características de accesibilidad
- Grilla de espaciados
- Código SCSS copy-paste
- Ubicaciones de archivos

**Mejor para:** Diseñadores y desarrolladores que necesitan referencias rápidas

---

## RESUMEN DE HALLAZGOS

### Selectores Identificados: 44
```
Componentes Principales (7):
- .course-index-section
- .course-index-header
- .course-index-link
- .course-index-toggle
- .course-index-content
- .course-index-section-content
- .course-index-item

Componentes Progress Bar (8):
- .courseindex-progress-container
- .courseindex-progress-container .progress-header
- .courseindex-progress-container .progress-title
- .courseindex-progress-container .progress-stats
- .courseindex-progress-container .progress-percentage
- .courseindex-progress-container .progress-details
- .courseindex-progress-container .progress
- .courseindex-progress-container .progress-bar

Selectores Relacionados (2):
- #nav-drawer
- .drawer

Estados y Variaciones (21+):
- :hover, :focus, .active, .current, .completed, .dimmed
- Pseudoelementos (::after, ::before)
```

---

## PALETA DE COLORES UTILIZADA

| Uso | Nombre | Hex | RGB |
|-----|--------|-----|-----|
| Headers, Nav | Primary Blue | #365ba3 | rgb(54, 91, 163) |
| Hover, Active, Botones | Primary Red | #e21144 | rgb(226, 17, 68) |
| Bordes Activos | Yellow | #ffb000 | rgb(255, 176, 0) |
| Fondos | White | #FFFFFF | rgb(255, 255, 255) |
| Textos Secundarios | Gray | #5e5e5e | rgb(94, 94, 94) |
| Progress Dark | Dark Blue | #001f40 | rgb(0, 31, 64) |

---

## TRANSICIONES Y ANIMACIONES

### Velocidades
- Quick: 0.2s (Links, Items)
- Normal: 0.25s (Sections)
- Smooth: 0.3s (Progress, Toggle)
- Progress Bar: 0.6s (cubic-bezier)

### Animaciones
- Shimmer (2s infinite): Progress bar gradient effect

### Transformaciones
- translateY(-1px), translateY(-2px)
- translateX(2px)
- rotate(0deg → 180deg)

---

## BREAKPOINTS RESPONSIVOS

```
Desktop (≥769px):
  - Width: 95%
  - Padding/Margin: Normal
  - Font-size: 0.95rem

Tablet/Mobile (≤768px):
  - Width: 98%
  - Padding/Margin: Reduced
  - Font-size: 0.875rem
  - Border-radius: Reduced
```

---

## ARCHIVOS FUENTE

| Archivo | Ubicación | Líneas | Descripción |
|---------|-----------|--------|-------------|
| compecer.scss | `/theme/compecer/scss/` | 1166 | Estilos principales |
| custom_variables.scss | `/theme/compecer/scss/` | 95 | Variables de color |
| _variables.scss | `/theme/compecer/scss/` | 104 | Variables heredadas (Moove) |
| custom.css | `/theme/compecer/style/` | 1791 | Estilos compilados |

---

## SECCIONES DE COURSEINDEX EN COMPECER.SCSS

| Sección | Líneas | Tema |
|---------|--------|------|
| Course Index Principal | 1441-1641 | Estructura base y contenedor |
| Progress Bar | 1652-1790 | Componente de progreso |
| Drawer Base | 26-66 | Configuración del drawer |
| Nav Drawer (compecer-co) | 429-488 | Variante específica de tema |

---

## CARACTERÍSTICAS DESTACADAS

### Diseño
✓ Jerarquía visual clara con colores consistentes
✓ Contraste WCAG AA para accesibilidad
✓ Estados bien diferenciados (hover, active, completed)
✓ Indicadores visuales múltiples (color, borde, icono)

### Responsividad
✓ Breakpoint principal: 768px
✓ Ajustes de padding, margin, font-size
✓ Ancho adaptativo (95% → 98%)
✓ Border-radius reducido en mobile

### Animaciones
✓ Transiciones suaves (cubic-bezier)
✓ Shimmer effect profesional en progress bar
✓ Rotación suave de iconos
✓ Soporte para prefers-reduced-motion

### Accesibilidad
✓ Indicadores de estado visuales claros
✓ Focus states implementados
✓ Respeto por prefers-reduced-motion
✓ Contraste de color adecuado

---

## PATRONES CLAVE IDENTIFICADOS

### 1. Patrón de Indicación de Estado
```
Item Default:
  - Color: Gray (#5e5e5e)
  - Border-left: Transparent
  
Item Hover:
  - Color: Red (#e21144)
  - Border-left: Yellow (#ffb000)
  - BG: Red 5%
  - Transform: X+2px
  
Item Active:
  - Color: Red (#e21144)
  - Border-left: Yellow (#ffb000)
  - Icon Color: Yellow (#ffb000)
  - Font-weight: 500
```

### 2. Patrón de Elevación (Shadow)
```
Default:      0 2px 8px rgba(0, 0, 0, 0.08)
Hover:        0 3px 8px rgba(0, 0, 0, 0.05)
Active:       0 2px 12px rgba(primary-blue, 0.08)
High:         0 4px 12px rgba(0, 0, 0, 0.12)
```

### 3. Patrón de Espaciado
```
Vertical:     0.75rem (sections), 1rem (containers)
Horizontal:   14-18px (headers), 10-16px (items)
Gap:          0.5rem (internal), 2px (items)
```

---

## RECOMENDACIONES PARA MEJORAS

### Corto Plazo
1. Extraer valores mágicos a variables SCSS
2. Documentar excepciones de timing (cubic-bezier)
3. Unificar transiciones (0.2s, 0.25s, 0.3s)

### Mediano Plazo
1. Crear mixins reutilizables para hover states
2. Mixin para responsive font sizes
3. Centralizar valores de shadow

### Largo Plazo
1. Considerar design tokens system
2. Documentación en Storybook
3. Testing automatizado de accesibilidad

---

## CÓMO UTILIZAR ESTOS DOCUMENTOS

### Para Diseñadores
1. Consultar COURSEINDEX_SUMMARY.md para referencias visuales
2. Revisar COLOR REFERENCE CARD para paleta
3. Revisar ICON STATES VISUAL para interacciones

### Para Desarrolladores
1. Leer COURSEINDEX_ANALYSIS.md completo para arquitectura
2. Usar COURSEINDEX_SUMMARY.md como referencia rápida
3. Consultar secciones de variables y mixins

### Para Mantenance
1. Usar tabla de archivos fuente para ubicar código
2. Revisar secciones de anidamiento SCSS
3. Consultar breakpoints responsivos

---

## CONTACTO Y ACTUALIZACIONES

Documento generado automáticamente por análisis de código
Última actualización: 2025-10-27
Versión: 1.0

Para actualizaciones o cambios en los estilos:
1. Modificar archivos en `/theme/compecer/scss/`
2. Compilar SCSS a CSS
3. Actualizar este análisis si hay cambios significativos

---

## APÉNDICE - KEYWORDS PARA BÚSQUEDA

courseindex, course-index, drawer, section, progress-bar, toggle, 
header, link, item, animation, transition, hover, active, responsive,
accessibility, color, spacing, typography, shadow, border, gradient,
animation, cubic-bezier, shimmer, transform, translateX, translateY,
rotate, primary-blue, primary-red, yellow, compecer-theme, moodle

---

**FIN DEL ÍNDICE**

