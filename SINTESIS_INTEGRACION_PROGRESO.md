# Síntesis y Recomendaciones – Integración de progreso en CourseIndex Compecer

## 1. Similitudes entre Remui y format_remuiformat
- **API central**: Ambos reutilizan `core_completion\progress::get_course_progress_percentage()` para el porcentaje global de curso.【F:theme/remui/classes/coursehandler.php†L387-L395】【F:course/format/remuiformat/classes/external/course_progress_data.php†L32-L68】
- **Uso de `completion_info`**: La lógica de secciones cuenta actividades completables filtrando visibilidad y seguimiento antes de calcular porcentajes.【F:theme/remui/classes/usercontroller.php†L135-L168】【F:course/format/remuiformat/classes/course_format_data_common_trait.php†L300-L356】
- **Estructura visual**: Las barras se renderizan con contenedores `.progress`, barras `progress-bar` y clases de color (`bg-green-600`, `progress-xs`, etc.) que se pueden trasladar directamente.【F:theme/remui/templates/block_myoverview/view-summary.mustache†L118-L129】【F:course/format/remuiformat/templates/list_sections.mustache†L73-L109】

## 2. Diferencias clave
- **Ámbito de presentación**: Remui sólo muestra la barra global en cabeceras y widgets, sin progreso por sección ni AJAX asociado; format_remuiformat extiende el patrón a cada sección y expone servicio AJAX para refrescarlo.【F:theme/remui/templates/navbar_fm.mustache†L60-L78】【F:course/format/remuiformat/classes/external/course_progress_data.php†L55-L114】
- **Capa de datos**: format_remuiformat empaqueta los datos en traits reutilizables y en un servicio externo (`format_remuiformat_course_progress_data`), mientras que Remui calcula en controladores específicos del tema sin servicio público.【F:course/format/remuiformat/classes/course_format_data_common_trait.php†L300-L356】【F:theme/remui/classes/coursehandler.php†L387-L395】
- **Refresco dinámico**: El plugin define un módulo AMD (`card_editing_observer`) para re-renderizar la barra tras ediciones; Remui no incluye lógica dinámica para el curso index.【F:course/format/remuiformat/amd/src/card_editing_observer.js†L1-L65】

## 3. Recomendación de implementación
- **Modelo de datos**: Tomar como base la estructura de `course_format_data_common_trait` (secciones) y del trait externo de format_remuiformat para construir un helper en Compecer que prepare curso, secciones y actividades con los mismos campos (`percentage`, `summary`, `activities`). Esto se concretó en `progress_helper`, que replica los cálculos y devuelve `sections`, `activitylist` y etiquetas de estado listas para Mustache/JS.【F:theme/compecer/classes/local/courseindex/progress_helper.php†L50-L204】
- **Servicio AJAX**: Reemplazar la lógica previa de `get_course_progress` para delegar en el helper y devolver también etiquetas localizadas y resúmenes de actividades, facilitando la actualización en vivo.【F:theme/compecer/classes/external/get_course_progress.php†L37-L143】
- **Plantillas**: Adoptar la misma estructura visual de Remui/format_remuiformat (clases `.course-prgress-container`, `.progress progress-rounded`, `.progress-bar-warpper`) dentro del course index para mantener estilos compatibles.【F:theme/compecer/templates/core_courseformat/local/courseindex/drawer.mustache†L17-L45】【F:theme/compecer/templates/core_courseformat/local/courseindex/section.mustache†L43-L58】【F:theme/compecer/templates/core_courseformat/local/courseindex/cm.mustache†L20-L52】
- **JavaScript**: Actualizar el módulo AMD `courseindex_progress` para consumir los nuevos datos, replicar la lógica de coloreado y refrescar actividades, similar al patrón del `card_editing_observer` (llamada AJAX y reemplazo de nodos).【F:theme/compecer/amd/src/courseindex_progress.js†L26-L251】

## 4. Adaptaciones necesarias
- **Localización**: Añadir cadenas en `theme_compecer` para los textos de progreso y estados de actividades, evitando dependencias externas.【F:theme/compecer/lang/en/theme_compecer.php†L202-L209】【F:theme/compecer/lang/es/theme_compecer.php†L274-L281】
- **Datos adicionales**: Incluir en la respuesta AJAX resúmenes formateados (`activitysummary`, `sectionprogresssummary`) y etiquetas para estados, permitiendo a JS actualizar accesibilidad y tooltips sin construir textos manualmente.【F:theme/compecer/classes/local/courseindex/progress_helper.php†L163-L176】【F:theme/compecer/classes/external/get_course_progress.php†L93-L141】
- **Sin tocar estilos base**: Mantener las clases originales (bg-* y wrappers) evita modificar SCSS; las nuevas plantillas sólo reubican el markup dentro de Compecer.

## 5. Resultado esperado
- CourseIndex muestra barra global y por sección coherentes con Remui/format_remuiformat, actualizadas por AJAX y con estados de actividad accesibles, sin alterar estilos ni estructura base del tema.
