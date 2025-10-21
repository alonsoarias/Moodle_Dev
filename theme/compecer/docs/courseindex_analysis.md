# Rediseño formal del course index – Análisis y blueprint inspirado en RemUI

## 0. Resumen ejecutivo

Esta entrega documenta la fase de análisis exhaustiva previa al rediseño del course index del theme Compecer. Se estudió a profundidad el comportamiento del theme de referencia (RemUI), se auditó el estado actual del override de Compecer, se revisó la arquitectura de Moove (tema padre) y se definió un blueprint visual y técnico que mantenga compatibilidad con Moodle 4.5. El siguiente desarrollo se centrará en:

1. Traducir los principios formales, minimalistas y profesionales observados en RemUI.
2. Eliminar interacciones y estilos superfluos presentes en Compecer.
3. Reutilizar la lógica de progreso existente, alineándola con el lenguaje visual y textual objetivo.

---

## 1. Fase 1 – Análisis profundo del course index de RemUI

### 1.1 Diseño visual y jerarquía

- **Layout**. El drawer de RemUI mantiene un contenedor vertical simple: título de bloque seguido por el listado (`<nav id="courseindex" class="courseindex">`). No existen cajas adicionales ni separadores ornamentales.【F:theme/remui/templates/core_courseformat/local/courseindex/drawer.mustache†L33-L40】
- **Títulos de sección**. Cada sección es un `div` con un título alineado a la izquierda y un set compacto de iconos a la derecha (badge de sección actual, candado, drag handle, caret). Todo se resuelve dentro de una banda de color uniforme que ocupa el ancho completo.【F:theme/remui/templates/core_courseformat/local/courseindex/section.mustache†L72-L126】
- **Actividades**. Las filas de actividad funcionan como contenedores flex sin iconografía adicional, más allá de la marca de completitud estándar (`.completioninfo`).【F:theme/remui/templates/core_courseformat/local/courseindex/cm.mustache†L39-L84】
- **Colorimetría y espaciado**. El SCSS remarca un patrón austero: bordes suaves, fondos planos y tipografía neutra. El mixin de item establece padding uniforme de `0.5rem`, radios suaves y estados de foco sin sombras.【F:theme/remui/scss/moodle/courseindex.scss†L10-L139】 En el drawer, las secciones usan un fondo translúcido del color corporativo y márgenes mínimos de 3px.【F:theme/remui/scss/remui/_drawer.scss†L186-L236】

**Principio trasladable**: el drawer debe comportarse como un listado lineal con respiración suficiente, evitando tarjetas independientes o múltiples cajas.

### 1.2 Presentación de información

- **Indicadores de progreso**. RemUI no expone porcentajes dentro del drawer; concentra los indicadores en tarjetas y resúmenes del dashboard utilizando la API `progress::get_course_progress_percentage()` y conteos de actividades completadas para generar labels como “4 of 7 activities completed”.【F:theme/remui/classes/usercontroller.php†L123-L170】 Esta separación permite que el course index se mantenga visualmente ligero.
- **Estados de actividades**. Se apoya en `.completioninfo` nativo (clases `completion_complete`, `completion_fail`) y no introduce iconos personalizados en el markup. La diferenciación ocurre mediante color en CSS.【F:theme/remui/scss/moodle/courseindex.scss†L127-L138】
- **Organización textual**. Los títulos de sección siguen siendo enlaces (lo que activa el colapso en Boost). La jerarquía tipográfica se limita a pesos 600 para títulos y 400/500 para el resto.【F:theme/remui/templates/core_courseformat/local/courseindex/section.mustache†L79-L115】

### 1.3 Implementación técnica

- **Templates Mustache**. RemUI mantiene la estructura estándar heredada de Boost (`data-for`, `data-region`, `role="treeitem"`), lo que asegura compatibilidad con el editor y el arrastre de secciones.【F:theme/remui/templates/core_courseformat/local/courseindex/section.mustache†L63-L131】
- **SCSS modularizado**. El archivo `moodle/courseindex.scss` define mixins genéricos y tokens de color, mientras que `_drawer.scss` aplica el lenguaje visual específico del tema al contenedor lateral.【F:theme/remui/scss/moodle/courseindex.scss†L1-L175】【F:theme/remui/scss/remui/_drawer.scss†L174-L236】
- **Lógica de porcentaje**. Los servicios y controladores (p. ej. `coursehandler::get_enrolled_students`, `usercontroller::get_users_courses_with_progress`) calculan porcentajes con `completion_info` y `core_completion\progress::get_course_progress_percentage`, consolidando también fracciones `complete/total` para mensajes auxiliares.【F:theme/remui/classes/coursehandler.php†L360-L406】【F:theme/remui/classes/usercontroller.php†L123-L170】
- **JavaScript**. No existe módulo AMD específico para el course index; RemUI se apoya en la funcionalidad de core. Cualquier adaptación debe respetar este enfoque minimalista.

### 1.4 Experiencia de usuario

- **Percepción**. La navegación es directa: título, secciones plegables y actividades. No hay bloques de progreso que rompan el flujo.
- **Indicaciones**. Los estados se entienden por color (verde/rojo/gris) y por el texto de accesibilidad de `.completioninfo`. No hay tooltips invasivos.
- **Interacción**. El clic en la cabecera colapsa/expande la sección con un caret bien alineado; en edición, se muestran iconos de arrastre discretos.【F:theme/remui/templates/core_courseformat/local/courseindex/section.mustache†L88-L114】

**Conclusión**: La fortaleza de RemUI radica en la sobriedad del drawer y en delegar los porcentajes a superficies más adecuadas. Nuestro rediseño debe mantener la misma limpieza, aunque agreguemos métricas dentro del índice para cumplir los requisitos funcionales.

---

## 2. Fase 2 – Auditoría del course index actual de Compecer

### 2.1 Implementaciones existentes

- **Cabecera personalizada**. El drawer incorpora un bloque de progreso global con encabezado, porcentaje en grande, barra y fracción dinámica, además de un mensaje alterno cuando la finalización está deshabilitada.【F:theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache†L10-L52】
- **Secciones**. El título se presenta dentro de un botón que combina la etiqueta, la fracción de progreso y el caret. Bajo él se despliega un bloque adicional con barra de progreso y label `aria-live`.【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L24-L83】
- **Actividades**. Cada fila contiene un wrapper propio (`.ci-cm-row`) con icono de estado personalizado (`.ci-status-icon`), enlace truncado y candado opcional. Se mantiene el markup estándar para elementos delegados.【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L20-L66】
- **Lógica de datos**. El proveedor PHP agrega estados por actividad, sección y curso utilizando `completion_info`, con filtros para secciones invisibles o delegadas y etiquetas accesibles para cada estado.【F:theme/compecer/classes/local/courseprogress/provider.php†L53-L126】【F:theme/compecer/classes/local/courseprogress/provider.php†L165-L206】
- **Servicio AJAX**. La función externa valida contexto, exige login y delega en el proveedor para servir un payload normalizado (curso, secciones, cms).【F:theme/compecer/classes/external/courseindex_progress.php†L56-L105】
- **Módulo AMD**. `courseindex_progress.js` escucha los eventos del editor de curso, consulta el servicio y actualiza curso, secciones y estados de actividad; gestiona colas para evitar llamadas simultáneas.【F:theme/compecer/amd/src/courseindex_progress.js†L31-L198】
- **Estilos SCSS**. La hoja `compecer.scss` ya contiene un bloque específico (`// Course index refinements`) con tokens de color, escalas tipográficas y diseños minimalistas para barra global, secciones y estados de actividad (incluyendo iconografía generada con pseudo-elementos).【F:theme/compecer/scss/compecer.scss†L1133-L1522】

### 2.2 Problemas detectados

1. **Densidad visual en la cabecera**. El bloque de progreso tiene padding vertical amplio y varias líneas de texto, rompiendo la continuidad del listado a pesar del refinamiento actual. RemUI opta por presentar esta información en superficies externas; debemos compactarla y reducir a una sola línea + barra.
2. **Jerarquía tipográfica**. Se combinan pesos 600 y 500 en secciones y actividades; el resultado es ligeramente más ruidoso que el ideal remui. Es necesario reducir el número de pesos visibles y asegurar tamaños consistentes.
3. **Caret y fracción juntos**. El botón de sección mezcla fracción y caret, lo que dificulta la lectura rápida del porcentaje. Inspirándonos en RemUI, deberíamos separar visualmente la fracción (texto secundario alineado a la derecha) y posicionar el caret con menor protagonismo.
4. **Estados de actividad**. Aunque los iconos CSS actuales son minimalistas, necesitamos evaluar si el contorno circular añade ruido. RemUI muestra el estado dentro de la tipografía misma; podríamos explorar iconos lineales sin contenedor redondo para mayor sobriedad.
5. **Interacción en títulos de sección**. Actualmente el título no enlaza a la sección (el botón sólo colapsa). Es coherente con la consigna de eliminar enlaces, pero debemos reforzar que la interacción primaria sea clara y accesible (un único botón con `aria-expanded`).

### 2.3 Elementos satisfactorios

- **Lógica de datos robusta**. El proveedor ya filtra usuarios no trackeados, distingue estados `notstarted/inprogress/complete/failed` y genera etiquetas accesibles (mantendremos esta base).【F:theme/compecer/classes/local/courseprogress/provider.php†L85-L126】【F:theme/compecer/classes/local/courseprogress/provider.php†L165-L206】
- **Compatibilidad con el editor**. El AMD respeta los selectores `data-region` esperados por core y utiliza eventos reactivos de `core_courseformat`, lo que garantiza sincronización cuando se edita el curso.【F:theme/compecer/amd/src/courseindex_progress.js†L38-L108】
- **Organización SCSS**. Todo el estilo específico del índice está dentro de `compecer.scss`, cumpliendo la restricción del proyecto.

---

## 3. Fase 3 – Análisis del theme Compecer y su herencia

- **Config y herencia**. Compecer declara a Moove y Boost como padres, reutiliza los callbacks SCSS de Moove y mantiene `usescourseindex = true`, por lo que cualquier cambio en plantillas o JS debe respetar las estructuras de Boost.【F:theme/compecer/config.php†L45-L99】
- **Disponibilidad de overrides**. Compecer ya define plantillas en `templates/core_courseformat/local/courseindex/` y un módulo AMD propio; no existe renderer personalizado, por lo que la lógica depende totalmente de Mustache + JS.
- **Servicios registrados**. El servicio `theme_compecer_courseindex_progress` ya está disponible en `db/services.php` (no se modifica en esta fase, pero se mantiene en cuenta para la implementación).

---

## 4. Fase 4 – Consideraciones del tema padre (Moove)

- **Uso del drawer estándar**. Moove sólo controla si el drawer se muestra mediante layouts `drawers.php`, `course.php`, etc., y delega el contenido en `core_course_drawer()`. No sobrescribe los parciales del índice; por ello nuestros overrides seguirán funcionando siempre que mantengamos los IDs/roles esperados.【F:theme/moove/templates/drawers.mustache†L64-L124】【F:theme/moove/layout/course.php†L34-L123】
- **Compatibilidad futura**. Al no existir lógica adicional en Moove, basta con asegurarnos de que el markup siga siendo válido para Boost y que los scripts AMD se carguen mediante la plantilla.

---

## 5. Blueprint de diseño inspirado en RemUI

### 5.1 Principios visuales

1. **Minimalismo extremo**. Mantener el drawer como listado lineal sin tarjetas ni sombras. Los separadores serán líneas de 1px o espacios de `0.5rem`.
2. **Sobriedad cromática**. Limitar la paleta a gris oscuro (`#1F2933`) para texto, gris medio (`#616E7C`) para meta-información, gris claro (`#E1E7EF`) para divisores y un único acento azul (`#2E5CFF`) para progreso y estados positivos. Rojo (`#C62828`) se reserva a fallos.
3. **Tipografía**. Tres tamaños/pesos: 0.95rem/600 para encabezado, 0.875rem/500 para actividades, 0.8125rem/500 para metadatos. Ningún otro peso se utilizará.
4. **Iconografía funcional**. Estados representados mediante trazos lineales (check, cruz, diagonal) sin contenedores circulares gruesos. Uso exclusivo de pseudo-elementos CSS para evitar SVG externos.
5. **Accesibilidad**. Se mantienen `aria-live` para actualizaciones de progreso y `sr-only` para descripciones; se refuerza el contraste con el esquema cromático propuesto.

### 5.2 Estructura del drawer

- **Cabecera**.
  - Título: “Course index” (cadena localizable `courseindexheading`).
  - Línea secundaria: “Progreso del curso · 65%” (dinámico) con barra de 4px justo debajo.
  - Texto auxiliar: fracción `13/20 actividades completadas` alineada a la izquierda, sin caja propia.
  - Cuando la finalización esté deshabilitada, mostrar mensaje neutro en gris medio.
- **Secciones**.
  - Un único botón full-width con layout `grid` (columna para título, columna estrecha para fracción y caret). El caret debe rotar 180° al expandirse.
  - Debajo del botón, si hay datos de progreso, mostrar barra secundaria de 3px y resumen textual `4/7 · 57%`.
  - Cuando una sección esté destacada (`current`), una barra lateral izquierda de 3px en color acento indicará el estado.
- **Actividades**.
  - Layout `grid` con columnas: icono de estado (16px), nombre de actividad (truncado) y opcionalmente un contador micro (`(2 intentos)` si aplica en futuro).
  - Hover/focus: fondo `rgba(31,41,51,0.04)` y outline accesible.
  - Estados: `notstarted` (círculo delineado), `inprogress` (barra diagonal), `complete` (check lineal), `failed` (cruz), `nottracked` (línea horizontal). Los colores siguen la paleta.

### 5.3 Lógica de porcentajes

- **Curso**. Mantener el cálculo actual (`percent`, `fraction`, `a11y`) en el proveedor, pero asegurar que el texto formateado siga el patrón “X de Y actividades completadas”. Podemos reutilizar los strings y formato de RemUI como referencia para la traducción.【F:theme/compecer/classes/local/courseprogress/provider.php†L108-L124】【F:theme/remui/classes/usercontroller.php†L123-L170】
- **Secciones**. El proveedor ya retorna porcentajes y conteos por sección; se validará que las fracciones sigan un formato consistente (`complete/total` y `%`).
- **Actividades**. Los estados existentes se mapearán a data attributes (`data-status`) para que el CSS y el JS puedan actualizar iconos sin mutar el markup.

### 5.4 Interacción

- Botón de sección sin enlace a la vista de sección (cumpliendo la consigna). Se reforzará el `aria-expanded` y el texto `sr-only` existente.
- Se mantendrá la integración con el editor (arrastrar/soltar) respetando `data-for` y roles.
- No se introducirán animaciones complejas; sólo transiciones de color/width ≤ 200ms.

### 5.5 Comparativa (RemUI vs. Compecer actual vs. propuesta)

| Elemento | RemUI | Compecer (actual) | Propuesta |
| --- | --- | --- | --- |
| Cabecera | Título simple sin progreso | Bloque de progreso voluminoso | Banda compacta con frase + barra fina |
| Sección | Enlace + caret + iconos múltiples | Botón con fracción, barra aparte | Botón unificado, fracción alineada, barra secundaria minimal |
| Actividad | Texto + completioninfo | Icono circular + enlace | Icono lineal (pseudo-elemento) + enlace, sin contenedor circular |
| Porcentaje curso | Fuera del drawer | Dentro del drawer (actual) | Dentro del drawer pero compacto, siguiendo tono RemUI |

---

## 6. Plan de implementación técnica (Fase 2)

1. **Plantillas Mustache**.
   - `drawer.mustache`: Redefinir la cabecera con la estructura compacta, mantener `data-region` existentes y simplificar el HTML.
   - `section.mustache`: Convertir el header en un botón `grid`, mover la fracción a un span secundario y mostrar la barra de progreso sólo cuando existan datos.
   - `cm.mustache`: Ajustar el wrapper para el nuevo icono (añadir `data-status` en el contenedor y eliminar círculos si no son necesarios).
2. **JavaScript (`courseindex_progress.js`)**.
   - Actualizar selectores para la nueva estructura (nuevos data attributes para fracciones y barras).
   - Normalizar el formato de texto (`X/Y · Z%`) y garantizar que los estados se reflejen con `dataset.status`.
   - Mantener la lógica de cola/peticiones y los `aria-live`.
3. **PHP (`provider.php`)**.
   - Revisar `prepare_course_payload` y `prepare_section_payload` (si existen métodos auxiliares) para devolver cadenas formateadas según el nuevo copy. Garantizar que `percent` sea entero y `fraction` siga el formato definido.
   - Validar que el proveedor no compute secciones sin actividades visibles para evitar mostrar barras vacías.
4. **Estilos (`scss/compecer.scss`)**.
   - Sustituir el bloque `// Course index refinements` por la versión definitiva: tokens, layout `grid/flex`, iconografía lineal y estados hover accesibles.
   - Asegurar que no queden reglas muertas (ej. referencias a `.ci-status-indicator` antiguas si se cambia la estructura).
5. **Idiomas**.
   - Revisar cadenas en `lang/en` y `lang/es` para adaptar textos (“Course progress”, “Section progress disabled”, etc.), manteniendo traducciones formales.
6. **Pruebas**.
   - Cursos con finalización habilitada/inactiva, con secciones ocultas, con actividades delegadas.
   - Modos edición/no edición para comprobar arrastre y actualizaciones en vivo.
   - Verificación responsive (≥320px) para asegurar que las columnas del grid se adapten.

---

## 7. Riesgos y mitigaciones

| Riesgo | Mitigación |
| --- | --- |
| **Compatibilidad con el editor de curso**: cambiar markup puede romper la detección de dropzones. | Mantener `data-for`, `data-region`, IDs y clases principales heredadas de Boost. Validar en modo edición con arrastre/soltar. |
| **Sobrecarga visual involuntaria** al mostrar porcentajes en el drawer (algo que RemUI evita). | Diseñar la cabecera con altura mínima y texto conciso; probar con usuarios para asegurar que no distrae. |
| **Rendimiento del servicio** si se añaden cálculos extra. | Reutilizar el proveedor actual, evitando bucles adicionales; considerar caching en memoria si se detecta impacto durante pruebas. |
| **Accesibilidad**: iconografía lineal puede ser insuficiente sin texto. | Mantener `sr-only` con descripciones de estado y garantizar contraste mínimo 4.5:1. |

---

## 8. Próximos pasos

1. Validar el blueprint con stakeholders.
2. Ajustar cadenas de idioma necesarias.
3. Implementar cambios en plantillas, JS y SCSS siguiendo el plan de la sección 6.
4. Ejecutar pruebas funcionales y de accesibilidad.
5. Documentar el resultado final (capturas, comparativa antes/después) para la segunda entrega.

Este documento sienta las bases para que la fase de implementación replique la claridad y profesionalismo del course index de RemUI, respetando la arquitectura de Moove y las restricciones específicas del proyecto.
