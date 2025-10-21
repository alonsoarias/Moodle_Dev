# Análisis y plan integral para el course index de Compecer (Moodle 4.5)

## 1. Estado actual del theme Compecer

### 1.1 Arquitectura general e herencia
- **Padres declarados**: `moove` y `boost`, según `theme/compecer/config.php`. Compecer hereda primero los overrides de Moove y, si faltan, recurre a Boost, por lo que cualquier cambio debe respetar los *hooks* y datos que estos temas esperan (`$THEME->usescourseindex = true`).
- **Callbacks SCSS**: se reutilizan los callbacks de Moove (`theme_moove_get_pre_scss`, `theme_moove_get_precompiled_css`) y se aporta `theme_compecer_get_main_scss_content()` para concatenar variables propias (`custom_variables.scss`) con el esqueleto de Moove.
- **Renderers**: existe `classes/output/core_renderer.php` que extiende al renderer de Moove; no hay renderer dedicado al course index, por lo que la lógica principal se delega a plantillas y JS.
- **Servicios / endpoints**: `db/services.php` registra `theme_compecer_courseindex_progress`, exponiendo datos de progreso vía AJAX.

### 1.2 Overrides del course index
- **`templates/core_courseformat/local/courseindex/drawer.mustache`**: incorpora cabecera propia, placeholders de progreso global (porcentaje, fracción y texto accesible) y carga el módulo AMD `theme_compecer/courseindex_progress` junto con el componente core (`core_courseformat/local/courseindex/drawer`).
- **`section.mustache`**: sustituye el `a` del título por un botón (no navega) y añade un bloque `data-region="section-progress"` con mini barra y textos auxiliares. Mantiene los atributos `data-*` requeridos por el editor de curso.
- **`cm.mustache`**: inyecta contenedor `data-region="cm-status"` para iconografía, oculta el parcial core de `completioninfo` y conserva el enlace a la actividad para accesibilidad.

### 1.3 Canal de datos de progreso
- **Proveedor**: `classes/local/courseprogress/provider.php` calcula porcentajes globales, por sección y clasifica actividades (`notstarted`, `inprogress`, `complete`, `failed`, `nottracked`). Usa `completion_info`, `get_fast_modinfo`, validaciones de contexto y cadenas localizadas (`lang/en|es/theme_compecer.php`).
- **Servicio externo**: `classes/external/courseindex_progress.php` valida acceso (`require_login`, `context_course::instance`, capacidad `moodle/course:view`), delega en el proveedor y expone la estructura para clientes JS.
- **Front-end reactivo**: `amd/src/courseindex_progress.js` extiende `core/reactive.BaseComponent`, escucha eventos del editor (`cm.completionstate:updated`, `section:created`, etc.), llama al servicio AJAX y actualiza DOM (barras, iconos, `aria-*`).
- **Internacionalización**: cadenas específicas para estados y resúmenes (`lang/*/theme_compecer.php`) aseguran textos descriptivos (requisito accesibilidad).

### 1.4 Estilos vigentes y puntos a simplificar
- **Ubicación**: `scss/_courseindex.scss` (importado desde `scss/compecer.scss`). El requerimiento actual indica eliminar este archivo y mover las reglas a `compecer.scss` para centralizar mantenimiento.
- **Diagnóstico visual**:
  - Uso intensivo de degradados (`background-image: linear-gradient(...)`) y sombras que contrastan con la meta de diseño minimalista.
  - Iconos de estado usan caracteres Unicode dentro de círculos llenos; generan saturación cromática (`$color-lime`, `$color-blue-terciary`, `$red`).
  - Barras de sección usan relleno sólido y cambios de color abruptos según estado.
  - Estilos adicionales en `compecer.scss` (fuera del índice) añaden bordes punteados y sombras globales, lo que puede interferir con la limpieza buscada.
- **Conclusión**: es necesario redefinir tipografía, espaciados y colores a partir de una paleta más neutra (grises suaves + acentos discretos) y consolidar todo en `compecer.scss` siguiendo la restricción de no crear `_courseindex.scss`.

### 1.5 Seguimiento de finalización
- El proveedor verifica `completion_info::is_enabled()` y si el usuario está en seguimiento (`is_tracked_user`). Cuando la finalización está deshabilitada se devuelve `completionenabled = false` y el drawer muestra un mensaje (`courseprogressdisabled`).
- Los estados disponibles coinciden con Moodle (`COMPLETION_COMPLETE_PASS/FAIL`, `COMPLETION_INCOMPLETE`) y añaden heurística `has_started` basada en vistas (`viewed`, `timemodified`, `customcompletion`).
- No se reutiliza el parcial core `cmcompletion`, por lo que actualmente dependemos del contenedor `ci-status-wrapper` y del módulo AMD para renderizar iconografía.

## 2. Referencia técnica: theme Remui

### 2.1 Obtención y cálculo de porcentajes
- **Controladores**: `classes/usercontroller.php` y `classes/coursehandler.php` usan `completion_info` y `\core_completion\progress::get_course_progress_percentage()` para obtener avance por curso, estudiantes y dashboards.
- **Servicios externos**: traits como `classes/external/enrol_get_course_content.php` y `classes/external/get_course_stats.php` estructuran datos de secciones/actividades para consumo AJAX.
- **Flujo de datos**: se formatean nombres con `\core_course_list_element`, se calculan totales de actividades completadas/pedientes con `completion_info->get_activities()` y `get_data()`, y se combinan con datos de contacto, imágenes y metadatos.

### 2.2 Presentación
- **Templates**: Remui mantiene los parciales core para `completioninfo` y añade barras de progreso en tarjetas de curso (`templates/core_course/coursecard.mustache`). El drawer reutiliza en gran parte la estructura de Boost, sin iconografía extra; el porcentaje se expone principalmente en vistas de dashboard.
- **Estilos**: SCSS dedicado (`scss/remui/_drawer.scss`, `scss/moodle/courseindex.scss`) define barras finas, colores planos y clases `completion_complete`, `completion_incomplete`, etc. Las reglas evitan degradados y utilizan espacios generosos.
- **Accesibilidad**: etiquetas `sr-only`, `aria-valuenow`, `aria-label` y contraste reforzado.

### 2.3 Performance y caching
- Estadísticas agregadas se cachean en configuración del tema (`set_config`/`get_config`) para evitar recálculo continuo.
- La lógica de progreso siempre pasa por APIs oficiales, lo que garantiza compatibilidad y evita SQL manual. Los servicios limitan la carga de datos a cursos/secciones visibles para el usuario.

## 3. Theme padre Moove
- **Compatibilidad**: Moove no sobrescribe las plantillas del course index; delega en Boost. Su SCSS (`theme/moove/style/moodle.css`) incluye estilos base para `.courseindex`, `completioninfo` y transiciones.
- **Configuración**: define `$THEME->usescourseindex = true` y provee callbacks SCSS utilizados por Compecer. Cualquier cambio debe respetar la estructura `data-region`, `data-action` y roles de árbol (`role="treeitem"`) esperados por Boost para edición drag&drop y accesibilidad.
- **Riesgos**: romper la compatibilidad con los scripts de Boost/Moove (por ejemplo, eliminando `component.init(...)` o atributos `data-for`) impediría actualizaciones en vivo del índice.

## 4. Brecha frente a los objetivos del rediseño
1. **Enlaces de sección**: aunque actualmente se usa un `<button>` no navegable, conviven dos controles (botón del título y botón de chevron). Debemos consolidar el patrón para evitar confusiones y asegurar foco accesible.
2. **Iconografía de finalización**: el módulo AMD ya produce iconos, pero el estilo es recargado y depende de caracteres Unicode. Se requiere iconografía simple (SVG o pseudo-elementos minimalistas) alineada con app.edutin.com/classroom.
3. **Porcentaje global y por sección**: la lógica existe, pero debemos revisar textos descriptivos para que sean claros ("Progreso del curso" / "Avance de la sección") y asegurar que se muestran sólo cuando hay seguimiento.
4. **Estética**: los degradados y colores saturados actuales chocan con el objetivo minimalista. También se debe evaluar la densidad vertical (espaciados en `.ci-cm-item`, márgenes de barras) para que el drawer respire.
5. **Organización SCSS**: el uso de `_courseindex.scss` contraviene la restricción actual. Es necesario consolidar los estilos en `scss/compecer.scss` y eliminar importaciones innecesarias.
6. **Dependencias heredadas**: debemos validar que cualquier simplificación respete los atributos requeridos por Boost/Moove para que los eventos `core_courseformat` sigan funcionando.

## 5. Plan de implementación propuesto

### 5.1 Fase PHP / datos
- Revisar `provider::for_course()` para optimizar iteraciones (evitar recorrer secciones ocultas cuando no se muestran en el índice) y exponer etiquetas preparadas para Mustache (ej. `coursetitle`, `sectiontitle`).
- Mantener uso de `completion_info` y `get_fast_modinfo`, garantizando compatibilidad con Moodle 4.5 y evitando consultas manuales.
- Validar estados adicionales (por ejemplo `COMPLETION_COMPLETE_FAIL_HIDDEN`) y asegurar que el servicio sigue devolviendo información neutra cuando la finalización está deshabilitada.

### 5.2 Mustache y accesibilidad
- Ajustar `drawer.mustache` para incorporar textos claros ("Progreso del curso") y preparar contenedores para barras minimalistas.
- Unificar el control de expansión en `section.mustache` (probablemente un solo botón con icono + título) garantizando que no exista navegación accidental.
- Revisar `cm.mustache` para incluir parciales core si es necesario y asegurar que los `data-region` se mantengan para actualizaciones reactivas.

### 5.3 Front-end (JS)
- Reutilizar `amd/src/courseindex_progress.js`, pero simplificar estados y garantizar que las clases generadas correspondan a la nueva guía visual.
- Documentar la interacción con `core/reactive` y eventos para facilitar mantenimiento.

### 5.4 Estilos (obligatorio en `compecer.scss`)
- Migrar el contenido de `_courseindex.scss` a una nueva sección dentro de `scss/compecer.scss` y eliminar la importación `@import 'courseindex';`.
- Redefinir paleta con grises (`$gray-100`, `$gray-500`) y un único color de acento (por ejemplo `$primary-blue` suavizado) para barras y estados completos.
- Reemplazar degradados por colores planos, reducir bordes redondeados excesivos y asegurar altura consistente para filas de actividad.
- Incluir variables para tamaños y espaciados, facilitando futuros ajustes.

### 5.5 Internacionalización y documentación
- Actualizar cadenas (`lang/en|es/theme_compecer.php`) para reflejar textos descriptivos exigidos ("Avance del curso", "Avance de la sección").
- Documentar en `docs/` el flujo de datos y dependencias para futuras actualizaciones.

### 5.6 Validación
- Probar escenarios con finalización activada/desactivada, secciones ocultas, actividades con estados fallidos y usuario sin seguimiento.
- Verificar en desktop/móvil que el drawer conserva comportamiento responsive (ancho, scroll) y accesibilidad (`tabindex`, `aria-expanded`).
- Confirmar que los eventos del editor siguen disparando actualizaciones en vivo.

## 6. Riesgos identificados y mitigaciones
| Riesgo | Impacto | Mitigación |
| --- | --- | --- |
| Remover atributos `data-*` requeridos por Boost | Alto | Revisar plantillas originales y realizar pruebas de edición con arrastrar/soltar tras los cambios. |
| Carga de servicio AJAX en cursos masivos | Medio | Considerar cachear resultados por petición y limitar el payload (por ejemplo, enviar sólo secciones visibles). |
| Reorganización SCSS rompe otras vistas | Medio | Encapsular reglas bajo `.course-index-*` y validar que no afecten otras partes del tema. |
| Cambios de iconografía afectan accesibilidad | Alto | Usar `aria-label` y `role="img"` o `sr-only` con textos claros; validar con lector de pantalla. |

## 7. Respuestas a preguntas clave
- **Archivos a modificar**: `templates/core_courseformat/local/courseindex/{drawer,section,cm}.mustache`, `scss/compecer.scss`, `amd/src/courseindex_progress.js`, `classes/local/courseprogress/provider.php`, `lang/en|es/theme_compecer.php` y posiblemente documentación complementaria.
- **Lógica de porcentaje en Remui**: se apoya en `completion_info` y `\core_completion\progress::get_course_progress_percentage()` dentro de `classes/usercontroller.php` y `classes/coursehandler.php`, complementado por servicios en `classes/external/`.
- **Tablas consultadas**: ninguna directa; se utilizan APIs que abstraen acceso a `course_modules`, `course_modules_completion`, `course_completion_*`.
- **APIs Moodle 4.5**: `completion_info`, `get_fast_modinfo`, `context_course`, `core/reactive` y servicios externos registrados en `db/services.php`.
- **Plantillas**: se reutilizarán overrides existentes ajustándolos para estilo minimalista y textos claros.
- **Herencia con Moove**: respetar `component.init(...)`, `data-for`, `role="treeitem"` y `aria-*` para no romper la integración con el editor.
- **Hooks / eventos**: `core_courseformat/local/courseindex/*` (JS) y eventos reactivos del course editor. El módulo AMD ya los escucha; debemos asegurarnos de que las clases resultantes sigan siendo válidas.
- **Conflictos potenciales**: coexistencia con plugins que modifiquen el course index. Se mitigará manteniendo nombres de `data-region` y ampliando, no reemplazando, estructuras.
- **Rendimiento**: optimizar el proveedor para evitar contar módulos delegados o ocultos innecesariamente; considerar cache per-request.
- **Casos edge**: cursos sin finalización, secciones sin actividades, actividades no visibles, usuarios sin permisos de visualización.
- **Validación**: pruebas manuales y, si es posible, PHPUnit focalizado en `provider::for_course()`.
- **Estilos a simplificar**: eliminación de degradados, círculos saturados y sombras fuertes en `_courseindex.scss`, reemplazándolos por colores planos y espacios amplios.
- **Textos descriptivos**: colocar "Progreso del curso" en la cabecera del drawer y "Avance de la sección" junto al título de cada sección, con porcentajes y fracciones legibles.

## 8. Próximos pasos
1. Migrar y simplificar los estilos del course index en `scss/compecer.scss` siguiendo la guía minimalista.
2. Ajustar plantillas Mustache para los textos descriptivos, control único de expansión y ganchos de progreso.
3. Revisar provider/servicio para garantizar coherencia con la lógica de Remui y eficiencia.
4. Ejecutar pruebas manuales en diferentes escenarios y documentar resultados.
5. Preparar la documentación final y guía de pruebas antes de la implementación definitiva.
