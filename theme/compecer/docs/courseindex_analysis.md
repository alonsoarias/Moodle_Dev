# Análisis integral de course index para theme Compecer

## 1. Arquitectura del theme Compecer

### 1.1 Jerarquía y herencia
- **Padres declarados**: `moove` y `boost`, según `config.php` (`$THEME->parents = ['moove', 'boost'];`). Esto implica que Compecer hereda primero todas las sobreescrituras de Moove y, en ausencia de ellas, recurre a Boost.
- **Callbacks SCSS**: reutiliza los *callbacks* de Moove (`theme_moove_get_pre_scss`, `theme_moove_get_precompiled_css`) y añade su propio `theme_compecer_get_main_scss_content()` que concatena variables y estilos propios con los del padre.
- **Renderers**: redefine `\theme_compecer\output\core_renderer`, extendiendo al renderer de Moove y cargando utilidades propias (`classes/output/core_renderer.php`). No hay overrides específicos para course index.
- **Estructura de carpetas relevante**: `amd/`, `classes/`, `layout/`, `scss/`, `templates/`, `style/`. No se detectaron módulos AMD personalizados para course index.

### 1.2 Course index en Compecer
- **Plantillas Mustache**: override en `templates/core_courseformat/local/courseindex/{drawer, section, cm}.mustache`.
  - `drawer.mustache` simplifica la cabecera del panel lateral y delega en `core_courseformat/local/courseindex/placeholders` para el contenido dinámico.
  - `section.mustache` reemplaza la estructura estándar con clases personalizadas (`course-index-section`, `course-index-header`). Mantiene el enlace original de Moodle sobre el título de sección (`href="{{{sectionurl}}}"`).
  - `cm.mustache` elimina el parcial `cmcompletion.mustache` y, con ello, los iconos de finalización. Solo imprime el nombre del módulo dentro de un `a`.
- **Estilos**: no hay SCSS específico para course index en `scss/`; el diseño depende de estilos heredados o de `style/custom.css`. No existe actualmente un esquema de colores propio para estados de finalización.
- **JavaScript**: se confía totalmente en los AMD core (`core_courseformat/local/courseindex/*`). El toggle de secciones mantiene la inicialización estándar (`component.init(...)`).

### 1.3 Estado del completion tracking
- La plantilla de actividades no renderiza los elementos `span.completioninfo` proporcionados por el core, por lo que el panel carece de iconografía de avance.
- No hay funciones PHP que calculen métricas de progreso ni que inyecten datos adicionales en el contexto de `courseindex`. La lógica actual se limita a lo que aporta Moodle core.

## 2. Análisis del theme Remui

### 2.1 Arquitectura técnica
- **Renderers y controladores**: múltiples clases dentro de `theme/remui/classes/` gestionan datos de progreso (`coursehandler.php`, `usercontroller.php`). Utilizan APIs de finalización (`\completion_info`, `\core_completion\progress`) y encapsulan cálculos para dashboard, encabezados y widgets.
- **Servicios externos**: traits en `theme/remui/classes/external/` exponen endpoints (ej. `enrol_get_course_content`) que estructuran secciones y actividades, pensados para consumo AJAX.
- **Plantillas Mustache**: se sobrescriben las plantillas de `core_courseformat/local/courseindex/*`, manteniendo compatibilidad con la API de completitud (incluyen `span.completioninfo` con `data-for="cm_completion"`).
- **Front-end**: SCSS dedicado (`scss/remui/_drawer.scss`, `scss/moodle/courseindex.scss`) aplica estilos a estados (`.completion_complete`, `.completion_incomplete`, `.completion_fail`).

### 2.2 Implementación del porcentaje de avance
- **Cálculo por curso**: `usercontroller::get_users_courses_with_progress()` recorre cursos matriculados, obtiene `completion_info`, calcula `progress::get_course_progress_percentage($course, $userid)` y compone metadatos adicionales (actividades completadas vs. totales).
- **Contextos adicionales**: `coursehandler::set_course_stats()` y `calculate_course_stats()` iteran sobre usuarios de un curso para producir totales por estado (completado, en progreso, no iniciado), apoyándose en `completion_info` y `core_completion\progress`. Los resultados se cachean en la configuración del tema (`edwcoursestats`, `edwdashboardstats`).
- **Datos por actividad**: los mismos métodos obtienen `completion_info->get_activities()` y `completion_info->get_data()` para contar completadas, pendientes y fallidas. Esta información alimenta componentes de interfaz como dashboards y menús.
- **Tablas implicadas**: aunque se abstrae mediante APIs, el flujo toca las tablas estándar de finalización (`course_modules_completion`, `course_completion_crit_compl`, `course_completion_criteria`) a través de `completion_info` y `core_completion\progress`. No se realizan consultas SQL directas; se usan helpers oficiales, garantizando compatibilidad con Moodle 4.5.

### 2.3 Presentación y estilo
- **Templates**: bloques como `block_myoverview` y tarjetas de curso (`templates/core_course/coursecard.mustache`) muestran barras de progreso con porcentajes inyectados desde los renderers.
- **Course index**: aunque Remui deja la lógica de cálculo en el core, mantiene los ganchos (`data-for="cm_completion"`) para que `core_courseformat/local/courseindex/cmcompletion` pueda pintar iconos sin modificaciones adicionales.
- **Accesibilidad**: los componentes incluyen etiquetas `sr-only` y `aria-*` en barras y botones. El SCSS define contrastes y estados hover.

### 2.4 Performance y caching
- Los cálculos intensivos (estadísticas de curso y dashboard) se almacenan como JSON en la configuración del tema (`set_config`/`get_config`). Se recalculan cuando se detectan cambios relevantes o por usuario/curso.
- Se evita recalcular progreso en tiempo real para todos los usuarios, mitigando cargas innecesarias en `completion_info`.

## 3. Consideraciones del theme Moove (padre)
- **Uso del course index**: Moove habilita explícitamente el drawer (`$THEME->usescourseindex = true`) y ofrece una opción de configuración (`enablecourseindex`) para ocultarlo. Las plantillas base (`templates/{drawers,course,incourse}.mustache`) renderizan el curso index de Boost sin modificaciones.
- **Estilos heredados**: incluye reglas en `style/moodle.css` para `.courseindex` y estados de `completioninfo`. Compecer puede apoyarse en estas reglas si reutiliza la estructura original.
- **Compatibilidad**: cualquier override en Compecer debe respetar los `data-*` attributes y hooks JS que Moove (y Boost) esperan para mantener drag & drop, accesibilidad y actualizaciones AJAX del índice.

## 4. Diseño detallado de la solución

### 4.1 Arquitectura general

La solución combinará **cálculo en servidor** (PHP) y **renderizado Mustache** sin romper los contratos de Moove/Boost. El flujo propuesto es:

1. *Entrada*: `core_courseformat\base::get_course_index()` invoca al renderer de Compecer.
2. *Renderer especializado*: un nuevo `\theme_compecer\output\courseindex_renderer` (subclase del renderer original) recopila los datos de progreso mediante un servicio dedicado.
3. *Servicio de progreso*: `\theme_compecer\local\courseprogress\provider` encapsula toda interacción con `completion_info`, devolviendo un DTO (`course_progress_dto`) con información global, por sección y por actividad.
4. *Contexto Mustache*: el renderer fusiona el contexto estándar del course index con los nuevos campos (`courseprogress`, `sectionprogress`, `cmcompletionstate`).
5. *Front-end*: plantillas Mustache y SCSS renderizan barras, iconos y etiquetas basadas en los datos calculados; un módulo AMD opcional escucha eventos para refrescar los porcentajes cuando Moodle emite actualizaciones en vivo.

### 4.2 Componentes PHP

| Componente | Responsabilidad | APIs utilizadas |
|------------|-----------------|-----------------|
| `classes/local/courseprogress/provider.php` | Calcular progreso global, por sección y mapear estados de actividades. Devuelve estructuras tipadas listas para Mustache. | `completion_info`, `core_completion\progress`, `get_fast_modinfo`, `course_modinfo->sections` |
| `classes/local/courseprogress/dto.php` | Objeto inmutable (o `stdClass`) con claves `course`, `sections`, `activities`, pensado para serializar en Mustache. | PHP nativo |
| `classes/output/courseindex_renderer.php` | Extiende `\core_courseformat\output\local\courseindex\renderer`. Inserta `courseprogress` y modifica el contexto de secciones/actividades. | Renderer de core, `parent::render_section`, `parent::render_cm`, helper `provider` |
| `classes/output/courseindex_helper.php` (opcional) | Traducir estados numéricos de completion a clases CSS e iconos. | `completion_info::is_enabled`, constantes de `completion_completion` |

**Consideraciones clave**:
- El provider debe respetar `completion_info::is_enabled()` y retornar valores nulos cuando la finalización esté deshabilitada para evitar renders inconsistentes.
- Se utilizarán cachés de `modinfo` y `completion_info` por petición para minimizar llamadas repetidas.
- Las funciones se diseñarán para ser cubiertas por pruebas unitarias (`phpunit`) mediante *data providers* que simulen cursos con distintos estados.

### 4.3 Contexto Mustache y plantillas

- `templates/core_courseformat/local/courseindex/drawer.mustache`
  - Añadir bloque `{{#courseprogress}}` con:
    - `progressbar` (0–100), `completedcount`, `totalcount`, `label` traducido (`{{#str}}progress, theme_compecer{{/str}}`).
    - Barra `<div role="progressbar" aria-valuenow="{{percent}}" aria-valuemin="0" aria-valuemax="100">`.
  - Mostrar texto `{{percent}}%` y `{{completedcount}}/{{totalcount}}`.

- `templates/core_courseformat/local/courseindex/section.mustache`
  - Sustituir `<a>` por `<button type="button" class="course-index-section-toggle" data-action="toggle">` para mantener accesibilidad y evitar navegación. Dentro, usar `<span class="section-title" role="presentation">`.
  - Inyectar `{{#progress}}` subcontexto con mini barra (`<progress>`, `<div class="section-progress">`).
  - Conservar atributos `data-target`, `data-for` que requiere el JS core.

- `templates/core_courseformat/local/courseindex/cm.mustache`
  - Restaurar parcial `cmcompletion` con `{{> core_courseformat/local/courseindex/cmcompletion }}` para enganchar eventos nativos.
  - Añadir contenedor `{{#completionicon}}<span class="cm-completion-state {{class}}" aria-label="{{label}}">{{{icon}}}</span>{{/completionicon}}`.
  - Garantizar que el enlace al módulo (`<a>`) persista para accesibilidad.

### 4.4 Diseño visual (SCSS)

1. Crear `scss/courseindex.scss` (importado desde `scss/compecer.scss`).
2. Definir mapa de colores basados en Edutin (`$ci-color-incomplete`, `$ci-color-progress`, `$ci-color-complete`, `$ci-color-failed`).
3. Estilos clave:
   - `.course-index-progressbar` con borde redondeado, gradientes suaves y estados contrastados.
   - `.section-progress` como barra compacta (alto 4px) que se integra en el encabezado.
   - `.cm-completion-state` con iconografía (usar `background-image: url('data-uri')` o pseudo-elementos si se opta por SVG inline).
4. Media queries (`max-width: 768px`) para adaptar tamaños en el drawer móvil.
5. Asegurar contraste mínimo de 4.5:1 y estados `focus` visibles.

### 4.5 Comportamiento dinámico (JavaScript)

- Crear `amd/src/courseindex-progress.js` (lazy-loaded):
  - Escuchar `core_courseformat/course-index:updated` y `core_courseformat/local/courseindex:itemupdated`.
  - Recalcular porcentajes tomando los atributos `data-completed`/`data-total` del DOM inyectados por Mustache.
  - Actualizar `aria-valuenow` y textos visibles sin requerir recarga.
- Registrar módulo en `amd/build.json` y cargarlo mediante `require(['theme_compecer/courseindex-progress'], function(module) { module.init(); });` en el footer del drawer si la finalización está habilitada.
- Fallback: si JS falla, los valores renderizados en servidor seguirán siendo válidos.

### 4.6 Compatibilidad, riesgos y mitigaciones

| Riesgo | Impacto | Mitigación |
|--------|---------|------------|
| Cambios en la estructura de `core_courseformat` en futuras versiones | Alto | Encapsular overrides en renderer propio y documentar dependencias; añadir pruebas que comparen estructura con la del core. |
| Cálculos costosos en cursos grandes | Medio | Cachear resultados en el request y, opcionalmente, almacenar progreso por usuario en `cache_store` configurable. |
| Incompatibilidad con otros plugins que personalicen el course index | Medio | Respetar `data-*` y no remover parciales core; proveer hooks mediante filtros de tema si es necesario. |
| Accesibilidad degradada al remover enlaces de sección | Alto | Sustituir por botones con `aria-controls` y `aria-expanded`, asegurando navegación por teclado y lectura por screen readers. |

### 4.7 Estrategia de pruebas

1. **Unitarias (PHPUnit)**
   - Simular cursos con finalización habilitada/deshabilitada, secciones vacías y actividades con estados `incomplete`, `complete`, `failed`.
   - Verificar que el provider calcula correctamente porcentajes y fracciones.

2. **Behat/E2E**
   - Escenario: usuario estudiante marca una actividad como completada y el porcentaje en el drawer se actualiza tras recargar.
   - Escenario: el título de sección no redirige pero permite expandir/colapsar.

3. **Pruebas manuales**
   - Navegadores desktop (Chrome, Firefox) y móvil (Chrome Android / Safari iOS).
   - Cursos grandes (+200 actividades) para medir performance visual.
   - Revisión de accesibilidad con `axe` o inspector de accesibilidad.

4. **Regresión visual**
   - Capturar capturas antes/después para validar consistencia con `app.edutin.com/classroom`.

### 4.8 Documentación y despliegue

- Actualizar `docs/courseindex_analysis.md` con los pasos de implementación completados y enlaces al código.
- Añadir sección en la documentación del tema (`theme/compecer/README.md` si aplica) con instrucciones para recompilar SCSS (`npm install && grunt css`).
- Registrar cualquier configuración nueva en `admin/settings.php` del tema si se exponen toggles para el porcentaje o iconos.

### 4.9 Cronograma propuesto (iterativo)

1. **Sprint 1**: Implementar provider PHP + renderer, añadir pruebas unitarias.
2. **Sprint 2**: Actualizar Mustache y SCSS; validar accesibilidad.
3. **Sprint 3**: Desarrollar módulo AMD (si es necesario) y pruebas Behat.
4. **Sprint 4**: QA completo, documentación final y preparación de release.

## 5. Respuestas a preguntas clave
- **Archivos a modificar**: `templates/core_courseformat/local/courseindex/{drawer, section, cm}.mustache`, nuevo helper PHP en `classes/`, posibles ajustes SCSS en `scss/compecer.scss` o archivo dedicado, y opcionalmente un AMD en `amd/src`.
- **Funciones/APIs a usar**: `completion_info`, `core_completion\progress::get_course_progress_percentage`, `course_get_format`, `get_fast_modinfo` para extraer secciones/actividades.
- **Tablas involucradas**: accesos indirectos a `course_modules`, `course_modules_completion`, `course_sections`, `course_completion_*` a través de las APIs de finalización.
- **Herencia con Moove**: mantener `data-*` y hooks JS originales para no interferir con drag & drop y accesibilidad del drawer.
- **Casos edge**: cursos sin finalización activada, secciones ocultas, actividades restringidas, subsecciones delegadas, usuarios sin permisos de edición.
- **Validación**: pruebas manuales en curso con completion on/off, confirmar recalculo tras marcar actividades, verificación en móvil (drawer colapsable) y chequeo de cachés si se introduce almacenamiento.

## 6. Implementación realizada (octubre 2024)
- **Cálculo servidor**: se añadió `theme\compecer\local\courseprogress\provider` para consolidar porcentajes globales, por sección y estados por actividad reutilizando `completion_info::get_data()` y detectando estados *sin iniciar*, *en progreso*, *completado*, *fallido* y *sin seguimiento*.
- **Servicio AJAX**: el nuevo endpoint `theme_compecer_courseindex_progress` (registro en `db/services.php`) expone la estructura agregada y controla acceso vía `require_login` y `context_course`.
- **Plantillas**: `drawer.mustache`, `section.mustache` y `cm.mustache` incorporan contenedores semánticos (`data-region`) para progreso, botón no navegable en secciones y placeholders de iconos personalizados; se mantiene compatibilidad con los `data-for` del core.
- **Interfaz**: el módulo AMD `theme_compecer/courseindex_progress` sincroniza los datos con el estado reactivo del course editor y refresca métricas ante eventos (`cm.completionstate`, cambios estructurales, etc.).
- **Estilos**: `_courseindex.scss` define barra de progreso global, micro barras por sección y nueva iconografía consistente con la paleta Edutin; se ocultan los iconos estándar de `completioninfo` para evitar duplicidades.
- **Internacionalización**: nuevas cadenas en `lang/en|es` describen los estados, etiquetas accesibles y mensajes cuando la finalización está deshabilitada.

