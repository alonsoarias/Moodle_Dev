# Rediseño integral del course index – Auditoría y blueprint (Moodle 4.5, theme Compecer)

## 0. Resumen ejecutivo

El theme Compecer ya incorpora overrides y lógica personalizada para el course index, pero la estética actual no se ajusta al mandato de **formalidad y minimalismo**. Las plantillas Mustache añaden bloques de progreso y estados, el módulo AMD sincroniza datos con un servicio AJAX y el SCSS aplica un estilo aún cargado. Esta entrega documenta:

1. Una auditoría minuciosa del estado actual (estructura, estilos, JS y datos).
2. Un análisis de Moove (tema padre) y Remui (referencia técnica para porcentajes).
3. Un **documento de diseño** con la propuesta visual final (paleta, tipografía, jerarquía, iconografía, microcomponentes) alineada con la consigna “sin tanta cosa”.
4. Un plan de implementación detallado que preserva compatibilidad con Moodle 4.5 y con el ecosistema Boost/Moove.

La siguiente fase (no incluida en esta entrega) consistirá en ejecutar el rediseño siguiendo este blueprint.

---

## 1. Auditoría del estado actual del course index en Compecer

### 1.1 Arquitectura y herencia

| Elemento | Observaciones |
| --- | --- |
| **Configuración del tema** | `theme/compecer/config.php` declara a Moove y Boost como padres, reutilizando sus callbacks SCSS. Compecer mantiene `$THEME->usescourseindex = true`, por lo que cualquier override debe respetar los ganchos del course index estándar. |
| **Renderizado** | No existe renderer específico para el course index; se extiende el renderer de Moove (`classes/output/core_renderer.php`). La personalización ocurre en plantillas, JS y servicios. |
| **Servicios** | `db/services.php` expone `theme_compecer_courseindex_progress`, consumido por el módulo AMD para refrescar porcentajes. |

### 1.2 Plantillas Mustache vigentes

| Plantilla | Situación actual | Problemas detectados |
| --- | --- | --- |
| `templates/core_courseformat/local/courseindex/drawer.mustache` | Muestra encabezado con progreso global y ejecuta `core_courseformat/local/courseindex/drawer` + `theme_compecer/courseindex_progress`. | Bloque de progreso se siente voluminoso y con demasiados bordes; el texto «sidebarcoursemenuheading» no comunica formalidad (cadena genérica). |
| `section.mustache` | El título de sección es texto truncado, seguido de bloque `data-region="section-progress"` y un botón separado con chevron para el colapso. | Dos zonas interactuables (título con `tabindex` heredado y botón chevron). Falta unificación accesible del control. |
| `cm.mustache` | Inserta `data-region="cm-status"` para iconos, mantiene enlace a la actividad y oculta `completioninfo` nativo. | Iconografía basada en Unicode dentro de contenedores circulares; el contraste actual depende de colores saturados definidos en SCSS. |

### 1.3 Lógica de datos y JavaScript

| Componente | Rol | Hallazgos |
| --- | --- | --- |
| `classes/local/courseprogress/provider.php` | Agrega datos de progreso por curso, sección y actividad usando `completion_info` y `get_fast_modinfo`. | El flujo es sólido, respeta permisos y produce resúmenes accesibles. Puede optimizarse excluyendo secciones delegadas/ocultas (ya se hace) y quizá cache por petición. |
| `classes/external/courseindex_progress.php` | Servicio AJAX que valida contexto y entrega la estructura. | Utiliza `require_login` y `validate_context`, garantizando seguridad. |
| `amd/src/courseindex_progress.js` | Extiende `BaseComponent`, escucha eventos reactivos (`cm.completionstate:updated`, etc.) y aplica los datos al DOM. | Excelente desacople. Necesita ajustes para nuevos selectores/iconografía y para formateos más sobrios (ej. suprimir tooltips llamativos). |

### 1.4 Estilos actuales

| Ubicación | Observaciones |
| --- | --- |
| `scss/compecer.scss` (sección `#courseindex`) | Aunque ya se movió parte del estilo aquí, se preservan patrones decorativos: bordes redondeados pronunciados, gradientes remanentes y círculos coloreados para los estados (`content: '\2713'`, etc.). Los contenedores `.ci-course-progress-wrapper` y `.course-index-section` mantienen cajas blancas con bordes múltiples y sombreado leve. |
| Media queries vacías | Varias `@media` sin contenido útil, incluida una tipografía `@media (nax-width: 576px)` (typo). Ocupan espacio y añaden ruido. |

### 1.5 Problemas clave frente a los objetivos

1. **Secciones “clicables”** – aunque el título es texto, convive con un botón chevron visualmente dominante. Se percibe como dos controles. Debe integrarse en un único botón accesible y sobrio.
2. **Iconografía** – los estados se representan con glifos Unicode dentro de círculos; incluso con la paleta atenuada siguen siendo decorativos y no minimalistas.
3. **Bloque de progreso global** – visualmente pesado: caja blanca con borde y resumen textual extenso. Necesitamos una franja compacta con información directa (ej. «Progreso del curso · 65%» + barra fina).
4. **Espaciado y densidad** – la mezcla de `gap`, `padding` y bordes generosos provoca sensación de tarjetas flotantes. La meta es un listado vertical casi sin cajas, con separadores sutiles.
5. **Consistencia tipográfica** – se usan pesos 600/700 en múltiples elementos; debemos definir jerarquía clara (título, meta, cuerpo) y aplicarla consistentemente.
6. **Código SCSS** – la sección `#courseindex` es extensa y mezcla responsabilidades. Debe reorganizarse dentro de `compecer.scss` en bloques comentados con tokens de espaciado compartidos.

---

## 2. Análisis del theme padre (Moove) y dependencias Boost

- Moove no sobrescribe los parciales del course index; se apoya en los de Boost. Por tanto, seguir usando `component.init(...)` y los atributos `data-for`, `data-region`, `role="treeitem"` es obligatorio para mantener la edición con arrastrar/soltar y la accesibilidad de Boost.
- Su SCSS (`theme/moove/style/moodle.css`) añade ajustes ligeros al drawer. Nuestro rediseño debe encapsular reglas bajo `.course-index-*` o `#courseindex` para no interferir con estilos globales de Moove.
- Callbacks SCSS: `theme_compecer_get_main_scss_content()` concatena `custom_variables.scss` con el `moove.scss`. No debemos introducir nuevos archivos parciales; toda la nueva capa irá dentro de `compecer.scss` siguiendo la restricción explícita del proyecto.

---

## 3. Referencia técnica y de UX – Theme Remui

### 3.1 Cálculo de porcentajes

- `classes/usercontroller.php` agrega el progreso del usuario usando `\core_completion\progress::get_course_progress_percentage()` y `completion_info->get_activities()` para construir listas con datos agregados.
- `classes/coursehandler.php` repite la lógica para paneles del curso y estadísticas, utilizando la misma API de core completion.
- Los servicios externos (`classes/external/get_course_stats.php`, `classes/external/enrol_get_course_content.php`, etc.) estructuran respuestas que incluyen porcentajes, totales y estados por actividad.

**Conclusión técnica**: Remui no consulta tablas manualmente; la lógica actual de Compecer, basada en `completion_info` y en un proveedor dedicado, ya cumple con esta pauta. El foco será limpiar la presentación sin reescribir APIs clave.

### 3.2 Presentación

- Remui utiliza barras planas y tipografía sobria en sus tarjetas de curso (`templates/core_course/coursecard.mustache`).
- El drawer principal mantiene el layout estándar con líneas delgadas y sin iconografía adicional.
- La iconografía de estados se limita a checkmarks sencillos con colores neutros.

### 3.3 Buenas prácticas trasladables

1. **Uso de `progress::get_course_progress_percentage()`** para mantener coherencia con la métrica oficial.
2. **Barras estrechas y sin degradados**, con estados diferenciados por matices del mismo color base.
3. **Textos auxiliares minimalistas** – Remui evita párrafos largos y se enfoca en porcentajes y contadores.

---

## 4. Blueprint de diseño formal y minimalista

### 4.1 Principios generales

- **Sobriedad**: reducir cajas independientes; priorizar un listado lineal con separaciones mediante espacio y líneas sutiles.
- **Jerarquía clara**: máximo tres niveles de peso tipográfico (600 para encabezados, 500 para datos clave, 400 para texto auxiliar).
- **Consistencia tonal**: paleta neutra con un único color de acento.
- **Accesibilidad**: contraste mínimo 4.5:1 en texto y 3:1 en elementos gráficos relevantes; soporte de lectores de pantalla mediante `aria-live`, `aria-label` existentes.
- **Interacción discreta**: animaciones limitadas a transiciones de color/fondo suaves (≤150ms).

### 4.2 Paleta cromática propuesta

| Uso | Color | Motivo |
| --- | --- | --- |
| Fondo drawer | `#F7F8FA` (gris claro cálido) | Evita blanco absoluto y crea profundidad mínima. |
| Texto primario | `#1F2933` | Contraste alto, tono profesional. |
| Texto secundario | `#616E7C` | Lectura cómoda sin competir con títulos. |
| Líneas y divisores | `#E1E7EF` | Separadores sutiles. |
| Acento progreso | `#2E5CFF` (azul corporativo sobrio) | Un único color para barras y estados completados. |
| Estado fallido | `#C62828` aplicado sólo en icono/etiqueta | Reservado, evita saturación. |

### 4.3 Tipografía y espaciado

- **Tipografía**: continuar con la fuente base de Moodle (derivada de Bootstrap) pero fijar tamaños:
  - Título principal: 0.95rem, peso 600.
  - Texto secundario/etiquetas: 0.8125rem, peso 500.
  - Nombre de actividad: 0.875rem, peso 500, color primario.
- **Escala de espaciado** (en `rem`): `0` (ninguno), `0.25` (micro separación), `0.5`, `0.75`, `1`, `1.5`. Todos los márgenes/padding del drawer deberán tomar uno de estos valores.

### 4.4 Componentes y layout

1. **Cabecera del índice**
   - Línea superior con título «Course index» (cadena localizable), a la izquierda.
   - Debajo, bloque compacto de progreso: texto «Progreso del curso · 65%» en una sola línea, barra horizontal de 4px de grosor ocupando 100% del ancho, con extremos rectos.
   - Resumen accesible (ej. «13 de 20 actividades completadas») en texto pequeño debajo.

2. **Secciones**
   - Cada sección se representa como un único botón de ancho completo (`role="treeitem"` + `button` interno) que contiene:
     - Título truncado a la izquierda.
     - Pequeño contador a la derecha (ej. «4/7»), con color secundario.
     - Icono caret minimalista (SVG lineal) rotado según estado.
   - Separador inferior: línea de 1px (color divisor) en lugar de cajas con bordes completos.
   - `data-region="section-progress"` se convierte en una fila secundaria (texto + barra mini de 3px) visible solo si hay elementos trackeados.

3. **Actividades**
   - Estructura `display: grid` con dos columnas: icono de estado (16px) y enlace truncado.
   - Iconos: reemplazar glifos Unicode por pseudo-elementos con fondo y borde lineal minimalista. Estados:
     - **No iniciado**: círculo delineado.
     - **En progreso**: semicírculo o barra diagonal simple (SVG/pseudo con `linear-gradient` sutil).
     - **Completado**: check lineal (`stroke-width: 1.5px`).
     - **Fallido**: cruz lineal discreta en color rojo suave.
   - Eliminación de sombras y rellenos; se utiliza cambio de color de texto/fondo en `:hover`.

4. **Estados vacíos o finalización deshabilitada**
   - Mensaje en bloque neutro sin bordes (solo texto gris medio con icono informativo simple).

### 4.5 Microinteracciones

- `:hover` y `:focus` en filas aplicarán un fondo gris claro (`rgba(31,41,51,0.04)`) y contorno `outline: 2px` cuando reciba foco, en lugar de `box-shadow` interno.
- Transición lineal de 120ms en barras de progreso.
- Sin animaciones adicionales.

### 4.6 Iconografía

- Utilizar pseudo-elementos CSS (`::before`) con `mask-image` o `background-image` SVG inline para mantener compatibilidad.
- Alternativa fallback: iconos FontAwesome lineales (`fa-regular`) ya disponibles en Moodle, pero aplicados sin rellenos ni círculos.
- Etiquetas `aria-label` existentes en `courseindex_progress.js` se mantendrán para describir estados.

---

## 5. Elementos a conservar vs. eliminar

| Mantener | Ajustar/Eliminar |
| --- | --- |
| Servicio `theme_compecer_courseindex_progress` y proveedor PHP (lógica correcta y compatible). | Cajas con bordes y sombras en `.ci-course-progress-wrapper` y `.course-index-section`. |
| Integración con `core/reactive` y eventos del editor (reactividad imprescindible). | Iconografía basada en Unicode y clases `ci-status-wrapper` actuales (se rediseñarán). |
| Datos accesibles (`aria-live`, `sr-only`). | Botón chevron separado; se reemplaza por un único botón con caret integrado. |
| Estructura de listas y roles ARIA (`role="treeitem"`, `role="group"`). | Texto genérico «sidebarcoursemenuheading»; se introducirán cadenas nuevas más formales. |

---

## 6. Plan de implementación (fase 2)

### 6.1 Orden recomendado

1. **Preparación**
   - Respaldar overrides actuales.
   - Crear tokens SCSS para la paleta/espaciado dentro de `compecer.scss` (sección `// Course index (v2)` diferenciada).

2. **Plantillas Mustache**
   - `drawer.mustache`: Reestructurar cabecera y contenido, reemplazar texto por nuevas cadenas (`courseindexheading`, `courseprogresslabel`).
   - `section.mustache`: Convertir header en botón de bloque con caret inline y subfila para progreso (cuando exista).
   - `cm.mustache`: Sustituir el contenedor actual por estructura de grid; añadir wrappers para pseudo-iconos (`data-region="cm-status"` se conserva pero se ajusta markup interno).

3. **JavaScript**
   - Actualizar `courseindex_progress.js` para apuntar a los nuevos selectores (`.ci-status-icon`, `.ci-progress-meter`, etc.), manteniendo la lógica de datos.
   - Introducir formateo de fracciones (ej. `4/7`) en la capa JS, reutilizando `section.summary` si está disponible.

4. **PHP / Strings**
   - Ajustar `provider::prepare_course_payload()` y `prepare_section_payload()` para devolver campos compatibles con el nuevo copy (`label`, `fraction`).
   - Añadir cadenas en `lang/en/theme_compecer.php` y `lang/es/theme_compecer.php` («Course progress», «Section progress», etc.).

5. **Estilos**
   - Reescribir completamente el bloque `#courseindex` en `compecer.scss` eliminando clases obsoletas.
   - Adoptar layout con `display: grid` para actividades y `flex` simplificado para cabeceras.

6. **Pruebas**
   - Validar en cursos con >20 actividades, con finalización activada/desactivada.
   - Revisar edición (mover secciones, crear actividades) para asegurar actualizaciones en vivo.
   - Pruebas responsivas (≥320px de ancho) y accesibilidad básica (navegación con teclado, lector NVDA/VoiceOver).

### 6.2 Consideraciones de riesgo y mitigación

| Riesgo | Mitigación |
| --- | --- |
| Pérdida de atributos `data-*` necesarios para el editor | Revisar plantillas originales de Boost/Moove antes de eliminar atributos. Mantener `data-for`, `data-id`, `data-number`. |
| Estados sin seguimiento ocultos incorrectamente | En `courseindex_progress.js`, mantener la lógica que oculta contenedores si `total === 0` para evitar huecos. |
| Cambios SCSS afectando otras áreas | Encapsular todo dentro de `#courseindex` y `.course-index-` prefijos nuevos. |
| Rendimiento en cursos grandes | Mantener la agregación actual sin añadir consultas extra; considerar cache opcional (no prioritario para rediseño visual). |

---

## 7. Próximos entregables

1. **Rediseño del front-end** basado en este blueprint.
2. **Refactor mínimo de JS/PHP** para alinear selectores y textos.
3. **Documentación final** (comparativa antes/después, guía de mantenimiento y validación).

Esta documentación sirve como guía formal para la siguiente fase de implementación, garantizando que el resultado final sea coherente, profesional y alineado con las expectativas de diseño minimalista.
