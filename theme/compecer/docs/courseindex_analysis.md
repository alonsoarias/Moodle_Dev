# Análisis integral del courseindex en el theme Compecer

## Resumen ejecutivo
- Compecer hereda de Moove y Boost; la configuración mantiene los _layouts_ originales del padre y declara el uso del courseindex, pero no aporta lógica adicional que respalde nuevas fuentes de datos para el índice.【F:theme/compecer/config.php†L34-L199】
- Los _templates_ Mustache del courseindex duplican la capa base de Moodle, pero introducen enlaces redundantes, estados de colapso forzados y eliminan los indicadores de finalización, lo que degrada la UX y la accesibilidad.【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L24-L67】【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L20-L56】
- El estilo vigente aplica coloraciones intensas, sombras y animaciones a través de `style/custom.css`, generando una estética saturada contraria a la referencia minimalista solicitada.【F:theme/compecer/style/custom.css†L1438-L1629】
- Remui ofrece una base técnica relevante: añade controles al drawer, mantiene los _placeholders_ de completion, y calcula porcentajes de avance mediante `completion_info` + `core_completion\progress`, almacenando estadísticas en caché de configuración; estas piezas serán la guía para integrar los indicadores en Compecer.【F:theme/remui/templates/common_start.mustache†L45-L140】【F:theme/remui/templates/courseindexdrawercontrols.mustache†L26-L58】【F:theme/remui/scss/moodle/courseindex.scss†L1-L205】【F:theme/remui/classes/coursehandler.php†L360-L634】【F:theme/remui/classes/usercontroller.php†L120-L191】
- Moove no personaliza el courseindex, por lo que cualquier sobrescritura en Compecer debe preservarlo como _fallback_ y evitar romper su actualización directa.【F:theme/moove/config.php†L29-L160】

## Fase 1 · Theme Compecer
### 1.1 Estructura e herencia
- Padres declarados: `['moove', 'boost']`, con callbacks de SCSS delegados a Moove (pre y precompilados) y a Compecer (extra SCSS). Esto implica que cualquier personalización debe convivir con los _mixins_ y variables globales de Moove.【F:theme/compecer/config.php†L71-L88】
- No existen clases personalizadas relacionadas con courseindex; la carpeta `classes/output` no introduce _renderers_ específicos, por lo que el contexto Mustache proviene íntegramente del cursoformato estándar.

### 1.2 Plantillas del courseindex
- `drawer.mustache` replica el contenedor estándar y agrega un encabezado estático, manteniendo la inicialización AMD nativa.【F:theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache†L10-L24】
- `section.mustache` conserva el rol `treeitem`, pero define dos `<a>` en el encabezado: uno con `data-action="togglecourseindexsection"` (mismo atributo del core) y otro con el icono chevron. El primero sigue apuntando a `sectionurl`, por lo que al hacer clic se navega y se colapsa simultáneamente, generando comportamiento impredecible.【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L34-L50】
- El bloque de contenido se marca con `collapse` sin clase `show`, forzando secciones cerradas por defecto (incluso la actual).【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L52-L60】
- `cm.mustache` elimina `span.completioninfo`, omite iconografía y encapsula el enlace en un contenedor flex, lo que impide que la capa AMD actualice estados de completion. También mantiene la llamada `require` al final, duplicando inicializaciones cuando el árbol cambia.【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L28-L55】

### 1.3 Estilos aplicados
- `style/custom.css` contiene SCSS sin compilar y reglas altamente decorativas: colores saturados en cabeceras (`$primary-blue`), sombras (`box-shadow`), transiciones y transformaciones en _hover_, y ancho fijo (`width: 95%`). Este bloque se inyecta globalmente, por lo que afecta escritorio y móvil y añade dependencia a variables de Moove.【F:theme/compecer/style/custom.css†L1438-L1629】
- Variables auxiliares como `$course-index-spacing` y `$course-index-icon-size` existen en `custom_variables.scss`, pero no se usan en `compecer.scss`, lo que indica deuda de consolidación de estilos.【F:theme/compecer/scss/custom_variables.scss†L85-L94】

### 1.4 JavaScript asociado
- Cada plantilla incluye `{{#js}} require(...) {{/js}}`, replicando la inicialización AMD estándar. Esto provoca múltiples llamadas redundantes cuando el DOM se re-renderiza (por ejemplo, tras actualizar completion o arrastrar elementos).【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L63-L67】【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L52-L55】
- No existe personalización en `amd/src`, por lo que cualquier lógica de progreso deberá añadirse desde cero.

### 1.5 Seguimiento de finalización actual
- Al retirar `completioninfo`, el índice perdió la capacidad de representar estados sin modificar la fuente de datos; el _exporter_ de core sigue enviando `completionstate`, pero no hay marca en DOM para `cm.js` (core) que permita renderizar iconos.【F:course/format/templates/local/courseindex/cm.mustache†L40-L63】【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L28-L44】
- No hay indicadores globales o por sección; la carga cognitiva recae en navegar hasta cada actividad para leer el bloque de progreso.

### 1.6 Riesgos de sobrecarga visual
- Contraste forzado: fondos azul intenso con texto blanco en cabeceras de sección, más bordes y sombras simultáneos.【F:theme/compecer/style/custom.css†L1460-L1527】
- Animaciones encadenadas (transiciones + `transform`) en enlaces de actividad, que sumadas al colapso por defecto dificultan escanear contenido rápidamente.【F:theme/compecer/style/custom.css†L1545-L1567】

## Fase 2 · Theme Remui
### 2.1 Arquitectura y _hooks_
- `common_start.mustache` envuelve el courseindex dentro de un drawer propio e inserta `courseindexdrawercontrols`, añadiendo controles para expandir/colapsar todas las secciones mediante un componente AMD dedicado.【F:theme/remui/templates/common_start.mustache†L50-L128】
- El componente `theme_remui/courseindexdrawercontrols` se registra como `BaseComponent` y despacha acciones al _course editor_ (`allSectionsIndexCollapsed`), apoyándose en la reactividad del core en vez de manipular DOM manualmente.【F:theme/remui/amd/src/courseindexdrawercontrols.js†L19-L63】

### 2.2 Presentación y estilos
- Las plantillas Remui conservan `completioninfo` y agregan contenedores para iconos, badges de “current” y controles drag & drop, mostrando que el flujo estándar funciona siempre que existan los `span` y clases esperadas.【F:theme/remui/templates/core_courseformat/local/courseindex/cm.mustache†L39-L86】【F:theme/remui/templates/core_courseformat/local/courseindex/section.mustache†L64-L128】
- El SCSS define mixins reutilizables (`courseindex-item-hover`, `courseindex-item-dragging`) y clases de completion (`completion_complete`, `completion_fail`), logrando una personalización ligera sin eliminar las dependencias del core.【F:theme/remui/scss/moodle/courseindex.scss†L1-L205】

### 2.3 Lógica de porcentajes
- `coursehandler::get_focus_context_data()` calcula el porcentaje global del curso para el usuario activo usando `\completion_info` y `\core_completion\progress::get_course_progress_percentage`, almacenándolo en la estructura `focusdata` consumida por varias vistas (incluida la UI de Focus Mode).【F:theme/remui/classes/coursehandler.php†L360-L399】
- `coursehandler::calculate_course_stats()` itera enrolados y clasifica su avance (completado/en progreso/no iniciado), reutilizando la misma API y demostrando cómo evitar consultas personalizadas a la BD.【F:theme/remui/classes/coursehandler.php†L508-L548】
- `set_dashboard_stats()` combina conteo de cursos y actividades completadas, persistiendo los resultados en `config_plugins` para reducir el coste de cálculo en paneles y servicios externos.【F:theme/remui/classes/coursehandler.php†L589-L634】
- `usercontroller::get_users_courses_with_progress()` agrega metadatos de progreso y recuentos de actividades por curso, generando cadenas descriptivas reutilizables; esta función es un ejemplo directo de cómo inyectar porcentaje + texto claro en plantillas.【F:theme/remui/classes/usercontroller.php†L120-L191】

### 2.4 Conclusiones aplicables
- Remui no personaliza el flujo de datos del courseindex, pero muestra la mínima infraestructura necesaria: conservar nodos `completioninfo`, añadir estilos ligeros y, cuando se requiere un porcentaje agregado, emplear `completion_info` + `core_completion\progress` desde un _renderer_ o controlador y almacenar en caché.
- Los controles de expand/collapse demuestran que la reactividad del courseindex permite añadir componentes secundarios sin reescribir el núcleo.

## Fase 3 · Theme Moove (padre)
- Moove sigue la estructura Boost y no overridea `core_courseformat/local/courseindex`. Esto significa que Compecer debe seguir respetando las mismas clases y `data-*` attributes para no romper integraciones futuras (p. ej., cuando Moove añada mejoras).【F:theme/moove/config.php†L29-L160】
- Callbacks SCSS (`theme_moove_get_pre_scss`, `theme_moove_get_precompiled_css`) siguen activos en Compecer; cualquier nuevo SCSS debe añadirse en `compecer.scss` para evitar conflictos con la cadena de compilación definida en el padre.【F:theme/compecer/config.php†L71-L88】

## Fase 4 · Diseño de la solución
### 4.1 Arquitectura propuesta
1. **Capa de datos (PHP)**
   - Crear una clase `theme_compecer\output\courseindex_progress` que, a partir de `\completion_info`, calcule:
     - Porcentaje global del curso (actividades completadas / totales).
     - Porcentaje por sección (filtrando módulos visibles del `cm_info`).
   - La clase expondrá métodos para obtener datos agregados y un _payload_ listo para Mustache (texto descriptivo, totales y porcentaje numérico).
2. **Inyección en plantillas**
   - Ampliar el contexto que llega al drawer usando un `renderable`/`templatable` invocado desde un _renderer_ del theme (p.ej. override en `theme_compecer\output\core\course_renderer`) o mediante `before_standard_top_of_body_html` si se requiere cargar al inicio.
   - Mantener el formato de datos que los componentes AMD esperan para `completioninfo`, reinsertando los nodos para que el core actualice iconos automáticamente.
3. **Interactividad**
   - Aprovechar el curso editor Reactivo: los porcentajes deben recalcularse vía AJAX cuando cambie la finalización. Se puede añadir un AMD en Compecer que escuche el evento `cm[ID].completionstate:updated` y solicite, vía _web service_ o _fragment_, los nuevos valores agregados.

### 4.2 Archivos a modificar/crear
- **Mustache**: `templates/core_courseformat/local/courseindex/section.mustache`, `cm.mustache`, potencialmente `drawer.mustache` para alojar indicadores globales.
- **SCSS**: añadir bloque exclusivo en `scss/compecer.scss` para estilos minimalistas del índice, eliminando dependencias de `style/custom.css`.
- **PHP**: nueva clase en `classes/output` y, si es necesario, override de renderer (`classes/output/core/course_renderer.php`) para suministrar contexto al drawer.
- **AMD**: módulo (ej. `amd/src/courseindex_progress.js`) que actualice indicadores al recibir cambios de completion.
- **Idioma**: strings nuevos para etiquetas descriptivas (“Progreso del curso”, “Actividades completadas 3 de 5”, etc.).

### 4.3 Estrategia de cálculo
- Reutilizar `\core_completion\progress::get_course_progress_percentage($course, $userid)` para el porcentaje global (idéntico a Remui).【F:theme/remui/classes/coursehandler.php†L360-L399】
- Para secciones: usar `completion_info::get_section_completion_data()` si disponible; de lo contrario, filtrar `get_fast_modinfo($course)->get_sections()` y contar actividades con `completion_info::get_data($cm)`.
- Caching:
  - Guardar resultados en `cache_store` del theme (por ejemplo, definir un nuevo _cache definition_) o reutilizar `cache::make('core', 'coursecompletionprogress')` si existe.
  - Invalidar en eventos `\core\event\course_module_completion_updated` y `\core\event\course_module_created/deleted`.

### 4.4 Estilo minimalista propuesto
- Paleta reducida: grises neutros para fondos, acentos suaves (ej. verde éxito, azul claro) únicamente en barras de progreso.
- Tipografía regular (sin negritas excesivas) con jerarquía basada en tamaño y espaciado.
- Espacio uniforme: padding vertical consistente (`0.75rem` secciones, `0.5rem` ítems) y separadores finos en lugar de bordes gruesos.
- Iconografía de estados: usar Font Awesome _outline_ simple o SVG lineal (no relleno), combinados con texto oculto accesible (`sr-only`).

### 4.5 Riesgos y mitigaciones
- **Rendimiento**: calcular porcentajes por sección puede ser costoso en cursos grandes. Se mitigará con caché por usuario + sección y cálculo incremental sólo cuando se reciba evento de completion.
- **Compatibilidad**: mantener atributos `data-for` y roles ARIA para que los componentes nativos sigan funcionando.
- **Accesibilidad**: asegurar contraste AA y proveer texto descriptivo (“65 % del curso completado”).
- **Sincronización**: los indicadores deben actualizarse tanto al marcar manualmente una actividad como al completar automáticamente; se validará escuchando los eventos de `core_courseformat` ya existentes.

### 4.6 Plan de pruebas
- Verificar curso con completion activado vs desactivado (los indicadores deben ocultarse cuando no aplique).
- Validar secciones sin actividades, actividades restringidas y módulos ocultos.
- Probar en dispositivos móviles (drawer colapsado) asegurando que la nueva UI se adapte sin saturar.
- Ejecutar `phpunit`/`behat` relevantes si se añaden componentes críticos (p.ej., pruebas unitarias para la nueva clase de progreso).

## Propuesta de diseño limpio (inspiración Edutin)
1. **Cabecera del drawer**
   - Barra horizontal con título "Progreso del curso" + porcentaje grande (tipografía semibold) y barra fina (altura 4 px) que ocupa el ancho del drawer.
2. **Secciones**
   - Título en gris oscuro con mini barra o `badge` indicando `X/Y actividades`.
   - Chevron reemplazado por botón icónico sin borde, alineado a la derecha, con rotación suave.
3. **Actividades**
   - Icono de estado (círculo vacío, semi lleno, lleno, cruz) alineado a la izquierda, seguido del nombre truncable.
   - Texto auxiliar opcional con la fecha límite o tipo de recurso en tono gris claro.
4. **Estados visuales**
   - `No iniciado`: icono contorno + texto gris.
   - `En progreso`: icono semicircular azul claro.
   - `Completado`: check verde sólido.
   - `Fallido`: cruz roja tenue.
5. **Interacción**
   - Mantener _hover_ discreto: cambio de fondo a gris muy claro, sin sombras ni desplazamientos.
   - Asegurar foco visible con borde o subrayado.

---

Este documento sirve como base para la siguiente etapa de diseño/implementación. No se han aplicado cambios funcionales todavía.
