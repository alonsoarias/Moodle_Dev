# Análisis Theme Remui – Lógica de Progreso

## 1. ¿Dónde se renderiza la barra de progreso del curso?
- **Cabecera del modo enfoque**: la plantilla `navbar_fm.mustache` muestra una barra de progreso horizontal con porcentaje cuando el usuario tiene progreso registrado.【F:theme/remui/templates/navbar_fm.mustache†L60-L78】
- **Bloque "My overview"**: tanto la vista resumen como el partial `progress-bar.mustache` reutilizan la misma estructura para imprimir el porcentaje y el texto accesible del progreso por curso.【F:theme/remui/templates/block_myoverview/view-summary.mustache†L118-L129】【F:theme/remui/templates/block_myoverview/progress-bar.mustache†L32-L34】

## 2. ¿Cómo obtiene Remui el porcentaje de progreso?
- **Controlador de cursos**: `theme_remui_coursehandler::get_focus_mode_data()` calcula el porcentaje invocando `\core_completion\progress::get_course_progress_percentage($COURSE, $USER->id)` y lo castea a entero para la plantilla del encabezado del curso.【F:theme/remui/classes/coursehandler.php†L387-L395】
- **Controlador de usuarios**: `theme_remui\usercontroller::get_users_courses_with_progress()` itera los cursos del usuario, consulta `progress::get_course_progress_percentage($course, $userobject->id)` y guarda el porcentaje junto con datos de actividades completadas para alimentar las tarjetas de cursos.【F:theme/remui/classes/usercontroller.php†L116-L168】

## 3. Estructura HTML/Mustache utilizada
- Las plantillas usan contenedores `.progress` de Bootstrap con `div.progress-bar` y textos auxiliares `.progress-text`/`.progress-data-wrapper`, manteniendo un `span.sr-only` para accesibilidad.【F:theme/remui/templates/navbar_fm.mustache†L60-L78】【F:theme/remui/templates/block_myoverview/view-summary.mustache†L118-L129】
- El partial `block_myoverview/progress-bar.mustache` encapsula la redacción del porcentaje para reutilizarlo en diferentes vistas del bloque.【F:theme/remui/templates/block_myoverview/progress-bar.mustache†L32-L34】

## 4. ¿Cómo se actualiza dinámicamente el progreso?
- El tema no define un módulo AMD específico para refrescar la barra de progreso del curso. Los datos llegan precalculados en PHP y se imprimen directamente en las plantillas; no hay llamadas AJAX asociadas a `navbar_fm.mustache` ni al course index en Remui. La única lógica relacionada con progreso en JavaScript es para otros flujos (ej. instalador), pero no para refrescar la barra principal.

## 5. Resumen rápido
- **API utilizada**: `core_completion\progress::get_course_progress_percentage()`.
- **Capas implicadas**: controladores PHP (`coursehandler`, `usercontroller`) → Mustache (`navbar_fm.mustache`, `block_myoverview`).
- **Actualización dinámica**: inexistente; Remui se apoya en renderizado del servidor para el progreso.
