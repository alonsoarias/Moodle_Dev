# Análisis Plugin format_remuiformat – Progreso de curso y secciones

## 1. ¿Cómo calcula el progreso del curso completo?
- El trait `format_remuiformat\external\course_progress_data` usa la API core `progress::get_course_progress_percentage($course)` para obtener el porcentaje y lo redondea hacia abajo antes de exponerlo vía servicio web.【F:course/format/remuiformat/classes/external/course_progress_data.php†L32-L96】
- Además construye un listado de actividades por tipo iterando `get_fast_modinfo($course)` para mostrar el resumen bajo la barra global.【F:course/format/remuiformat/classes/external/course_progress_data.php†L70-L96】

## 2. ¿Cómo calcula el progreso por sección?
- El método `get_section_module_info()` del trait `course_format_data_common_trait` recorre cada módulo visible, ignora etiquetas, verifica el seguimiento de finalización y calcula porcentaje completado vs. total usando `completion_info` para cada sección.【F:course/format/remuiformat/classes/course_format_data_common_trait.php†L300-L356】
- El objeto resultante incluye `percentage`, flags de completado, textos contextualizados (ej. actividades restantes) y HTML listo para Mustache.【F:course/format/remuiformat/classes/course_format_data_common_trait.php†L324-L349】

## 3. ¿Dónde se renderizan las barras de progreso?
- **Barra global**: la plantilla `course_progress.mustache` imprime el encabezado “Course completion progress”, la barra `progress progress-rounded` y la lista de actividades.【F:course/format/remuiformat/templates/course_progress.mustache†L1-L13】
- **Secciones**: `list_sections.mustache` añade un bloque `.progress-bar-warpper` con clases `progress-xs bg-grey-300` y texto de porcentaje para cada sección cuando hay información disponible.【F:course/format/remuiformat/templates/list_sections.mustache†L73-L109】

## 4. ¿Qué estructura HTML/Mustache se usa?
- Las plantillas combinan contenedores `.progress` con barras coloreadas `bg-green-600` y envoltorios `.progress-bar-warpper` reutilizables tanto en listas como en tarjetas (`card_section_summary.mustache`).【F:course/format/remuiformat/templates/list_sections.mustache†L73-L109】【F:course/format/remuiformat/templates/card_section_summary.mustache†L115-L132】
- El HTML deja huecos Mustache (`{{ percentage }}`, `{{ progress }}`) rellenados por la lógica PHP antes mencionada.【F:course/format/remuiformat/templates/list_sections.mustache†L73-L109】

## 5. ¿Cómo se actualiza el progreso dinámicamente?
- El módulo AMD `card_editing_observer.js` registra eventos DOM para detectar cuando se elimina o duplica una actividad. Entonces pide por AJAX el servicio `format_remuiformat_course_progress_data` y puede re-renderizar la plantilla `course_progress` usando `core/templates` (código comentado).【F:course/format/remuiformat/amd/src/card_editing_observer.js†L1-L65】
- El servicio devuelve porcentaje y lista de actividades, permitiendo refrescar el encabezado del curso sin recargar la página.【F:course/format/remuiformat/classes/external/course_progress_data.php†L55-L114】

## 6. Resumen rápido
- **APIs**: `core_completion\progress::get_course_progress_percentage`, `completion_info` para cada módulo.
- **Plantillas clave**: `course_progress.mustache`, `list_sections.mustache`, `card_section_summary.mustache`.
- **JavaScript**: módulo AMD `card_editing_observer` para pedir datos vía AJAX y reemplazar la barra global.
