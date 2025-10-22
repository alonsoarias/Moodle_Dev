# Educam Bot: Análisis exhaustivo y plan de modernización

## Resumen ejecutivo rápido

- **Objetivo principal**: transformar `local_educambot` en un chatbot inteligente basado únicamente en reglas internas, con motor de razonamiento multinivel y base de conocimiento extensa.
- **Prioridades inmediatas**: resolver las vulnerabilidades críticas listadas en la Sección C, rediseñar la estructura de datos según la Sección D.4 y ejecutar la Fase 1 del roadmap (Sección 2 del documento) antes de añadir nuevas funcionalidades.
- **Arquitectura propuesta**: adoptar la separación modular descrita en la Sección G, implementando los componentes `nlp\pipeline`, `matching\manager`, `inference\engine` y `context\session_memory` como servicios independientes con contratos claros.
- **Base de conocimiento inicial**: desplegar las 140 FAQs categorizadas incluidas en la Sección H siguiendo el pseudocódigo de instalación para garantizar consistencia, relaciones y sinónimos.
- **Panel administrativo y analítica**: planificar el desarrollo del gestor completo descrito en la Sección I conjuntamente con los dashboards de métricas y logs de depuración para asegurar mantenibilidad y mejora continua.
- **Cumplimiento Moodle**: verificar cada entrega con Moodle Code Checker y tests (PHPUnit, Behat) conforme al plan de testing indicado en la Sección J para mantener compatibilidad desde Moodle 4.0.

## Sección A. Inventario completo

### A.1 Inventario de archivos

| Ruta | Descripción |
| --- | --- |
| `local/educambot/version.php` | Metadatos del plugin y requisitos de versión. |
| `local/educambot/lib.php` | Funciones de integración con el ciclo de renderizado de Moodle y `pluginfile`. |
| `local/educambot/settings.php` | Definición de la página de ajustes y el acceso al gestor del bot. |
| `local/educambot/service.php` | Endpoint AJAX que procesa las preguntas y devuelve respuestas JSON. |
| `local/educambot/manage.php` | IU administrativa para crear, editar, buscar y eliminar reglas. |
| `local/educambot/styles.css` | Estilos del widget flotante y elementos auxiliares. |
| `local/educambot/templates/widget.mustache` | Plantilla Mustache para el widget de chat. |
| `local/educambot/README.md` | Resumen de funcionalidades y notas de desarrollo. |
| `local/educambot/docs/ANALYSIS.md` | Análisis previo de arquitectura y funcionalidades existentes. |
| `local/educambot/amd/src/widget.js` | Módulo AMD que controla las interacciones del widget en el cliente. |
| `local/educambot/classes/bot/engine.php` | Motor de reglas con heurísticas de coincidencia y respuesta. |
| `local/educambot/classes/bot/composite_reasoner.php` | Orquestador que decide entre reglas y conocimiento estructurado. |
| `local/educambot/classes/bot/reasoner_interface.php` | Contrato para estrategias de razonamiento. |
| `local/educambot/classes/form/entry_form.php` | Formulario Moodle para administrar reglas. |
| `local/educambot/classes/local/context_provider.php` | Obtiene datos de usuario, curso y eventos para personalizar respuestas. |
| `local/educambot/classes/local/interpolator.php` | Helper ligero para reemplazar placeholders. |
| `local/educambot/classes/local/knowledge_repository.php` | API de acceso a la base de conocimiento estructurada. |
| `local/educambot/classes/local/logger.php` | Persistencia de conversaciones y preguntas sin respuesta. |
| `local/educambot/classes/local/text_helper.php` | Utilidades de normalización, tokenización y similitud textual. |
| `local/educambot/classes/local/setup/seed.php` | Seeder que inserta datos iniciales de reglas y conocimiento. |
| `local/educambot/classes/output/widget.php` | Renderable del widget que expone datos a la plantilla. |
| `local/educambot/classes/privacy/provider.php` | Implementación de la API de privacidad de Moodle. |
| `local/educambot/classes/task/cleanup.php` | Tarea programada para depurar registros antiguos. |
| `local/educambot/classes/hook_callbacks.php` | Callbacks registrados en `db/hooks.php` para inyectar el widget. |
| `local/educambot/db/install.xml` | Definición de tablas (reglas, conocimiento, tópicos, relaciones, logs). |
| `local/educambot/db/install.php` | Invoca al seeder tras la instalación. |
| `local/educambot/db/upgrade.php` | Historial de migraciones y seedings sucesivos. |
| `local/educambot/db/access.php` | Capacidades `local/educambot:use` y `local/educambot:manage`. |
| `local/educambot/db/tasks.php` | Programación de la tarea de limpieza. |
| `local/educambot/db/caches.php` | Definiciones MUC para reglas y conocimiento. |
| `local/educambot/db/hooks.php` | Registro del hook `before_footer_html_generation`. |
| `local/educambot/lang/en/local_educambot.php` | Cadenas en inglés para IU y motor. |
| `local/educambot/lang/es/local_educambot.php` | Cadenas en español. |
| `local/educambot/docs/deep_analysis.md` | (Este documento) análisis integral y plan. |

### A.2 Arquitectura actual

```
[Widget Mustache + CSS + AMD] <---> [service.php]
        |                               |
        |                               v
   [classes/output/widget]        [bot\\engine]
        |                               |
 [context_provider]          [composite_reasoner]
        |                               |
[Datos de Moodle]      [knowledge_repository]----->[DB tablas conocimiento]
                                   |
                             [logger]
                                   |
                             [local DB]
```

- **Capa de presentación**: `widget.mustache`, `styles.css` y `amd/src/widget.js` gestionan la UI y envían preguntas vía fetch al endpoint AJAX. El widget se inserta usando hooks (`hook_callbacks::before_footer_html_generation`).
- **Capa de aplicación**: `classes/bot/engine.php` ejecuta las heurísticas de coincidencia, obtiene sugerencias y delega la decisión a `composite_reasoner`. `context_provider` aporta personalización y contexto.
- **Capa de datos**: `knowledge_repository` y `logger` interactúan con tablas especializadas (`local_educambot_*`). Las cachés definidas en `db/caches.php` reducen lecturas.

### A.3 Patrones de diseño

- **Inyección contextual**: `context_provider` encapsula lógica de personalización, separando la recolección de datos del motor de reglas (`engine`).
- **Strategy/Composite**: `reasoner_interface` y `composite_reasoner` permiten combinar distintas fuentes (reglas y conocimiento) bajo un contrato común.
- **Repository**: `knowledge_repository` centraliza accesos, cachea resultados y calcula puntuaciones.
- **Template Method**: `engine::respond` orquesta normalización, ranking, decisión y post-procesamiento de sugerencias.

### A.4 Organización y modularidad

El plugin sigue la estructura típica de un plugin local de Moodle. Las responsabilidades están mayormente separadas por carpetas (bot, local, output, task). Falta, sin embargo, una división más granular del motor (normalización, matching, inferencia) y el conocimiento no dispone de IU dedicada. Las dependencias clave son:

- `service.php` → `bot\engine`, `local\logger`.
- `bot\engine` → `context_provider`, `knowledge_repository`, `composite_reasoner`, `text_helper`.
- `composite_reasoner` → `knowledge_repository`, `context_provider`.
- `knowledge_repository` → `$DB`, cachés MUC, tablas de conocimiento.
- `manage.php` → `entry_form`, `bot\engine` (para previsualizar búsquedas).

## Sección B. Funcionalidades actuales

### B.1 Resumen funcional

1. Widget flotante en todas las páginas no-AJAX (`lib.php`, `hook_callbacks`).
2. Envío de preguntas mediante fetch, gestión de sesiones y render de respuestas (`widget.js`, `service.php`).
3. Motor de coincidencia con heurísticas híbridas (exactas, wildcard, similitud, keywords, contexto) (`bot/engine.php`).
4. Integración con conocimiento estructurado (tópicos, contextos, relaciones) (`composite_reasoner.php`, `knowledge_repository.php`).
5. Personalización con datos del usuario/curso y eventos próximos (`context_provider.php`).
6. Registro de conversaciones y preguntas sin respuesta (`logger.php`, tablas `local_educambot_log` y `_unanswered`).
7. Administración de reglas con formulario, filtros y búsqueda fuzzy (`manage.php`).
8. Ajustes de branding, logging y retención (`settings.php`).
9. Tarea programada para purgar registros antiguos (`classes/task/cleanup.php`).
10. Seed inicial de reglas y conocimiento (`local/setup/seed.php`).

### B.2 Flujo de interacción usuario-bot

1. El widget se carga con sugerencias iniciales y saludo personalizado (`widget.php`, `context_provider::build_initial_greeting`).
2. El usuario envía una pregunta (`widget.js::sendMessage`).
3. `service.php` valida sesskey/capabilidad y delega en `bot\engine::respond`.
4. El motor normaliza el texto, puntúa reglas (`rank_entries`) y consulta el conocimiento (`knowledge_repository::search`).
5. `composite_reasoner` decide si devuelve una regla o un paquete de conocimiento y aplica refuerzos contextuales.
6. La respuesta se personaliza e incluye sugerencias adicionales.
7. `service.php` registra la interacción y, si no hay respuesta, almacena la pregunta en la cola de pendientes.
8. El frontend muestra la respuesta, actualiza el estado de confianza y refresca las sugerencias.

### B.3 Capacidades actuales del motor

- Normalización básica (minúsculas, ASCII, eliminación de signos) y stopwords bilingües (`text_helper`).
- Coincidencia por patrones exactos, substrings, comodines, similitud (similar_text + Levenshtein) y solapamiento de tokens.
- Refuerzos por keywords, contexto de página, roles y curso activo (`engine::calculate_score`).
- Integración de conocimiento estructurado con puntuaciones híbridas (título, resumen, contenido, tópicos, contexto) y expansión por relaciones (`knowledge_repository`, `composite_reasoner`).
- Personalización con placeholders dinámicos (nombre, cursos, próximos eventos) (`context_provider`).
- Sugerencias proactivas y basadas en top matches (`engine::build_response_suggestions`).

### B.4 Proceso de consulta actual

1. `engine::rank_entries` itera sobre todas las reglas habilitadas (cacheadas) y calcula puntuaciones.
2. `knowledge_repository::search` recorre todas las entradas habilitadas (cacheadas) y acumula puntuaciones.
3. `composite_reasoner::decide` compara el mejor match de reglas vs conocimiento, ajusta puntuaciones con contexto y decide la fuente final.
4. `engine::respond` compone la respuesta HTML, personaliza placeholders y arma la lista de sugerencias.

### B.5 Sistema de matching actual

- **Coincidencia exacta**: Normalización idéntica entre pregunta y patrón incrementa el score en 1.0.
- **Wildcards**: Expresiones con `*` y `?` generan patrones regex y retornan 0.8–0.9 (`match_wildcard`).
- **Similitud léxica**: `string_similarity` (similar_text/Levenshtein) pondera 0.4–0.7 según umbrales.
- **Token overlap**: Coincidencia de tokens relevantes ponderada 0.6.
- **Keywords**: Coincidencias exactas/substring aportan hasta 0.3.
- **Contextos**: Páginas o cursos incrementan 0.1–0.15.
- **Roles**: Restricciones por shortname (intersección con roles efectivos del usuario).

### B.6 Gestión de respuestas

- Respuestas HTML almacenadas en `local_educambot_rule.response` (editables con editor HTML).
- Personalización mediante `context_provider::personalise_html` e interpolación de placeholders.
- Respuestas de conocimiento estructurado se renderizan como lista HTML con título, resumen, enlaces y metadatos (`build_knowledge_response`).
- Mensajes sin respuesta retornan string `noanswer` y registran la pregunta para revisión.

## Sección C. Catálogo de errores y riesgos

### C.1 Errores funcionales

1. **F1 – Falta IU para conocimiento estructurado**: no existe una pantalla para CRUD de `local_educambot_knowledge`, dificultando mantener la base ampliada (se detecta ausencia de controladores/templates dedicados). Severidad: Alta.
2. **F2 – Búsqueda administrativa limitada**: `manage.php` sólo lista/filtra reglas; no hay paginación ni filtros por estado/sugerencias, dificultando manejar catálogos grandes. Severidad: Media.
3. **F3 – Sugerencias repetidas**: `engine::get_suggestions` puede devolver menos de 6 entradas si se agotan coincidencias contextuales y no existe fallback a conocimiento cuando no hay reglas proactivas (se observa en el doble bucle sin fallback adicional). Severidad: Baja. 【F:local/educambot/classes/bot/engine.php†L95-L152】
4. **F4 – Registro dependiente del ajuste**: si `loggingenabled` está desactivado, tampoco se registran preguntas sin respuesta porque `record_unanswered` se invoca dentro de la misma ruta que retorna al detectar `response === null`. Severidad: Media. 【F:local/educambot/service.php†L55-L90】

### C.2 Errores de código

1. **C1 – Uso de `clean_param(PARAM_TEXT)` para preguntas**: en `logger::log` y `record_unanswered` se eliminan caracteres diacríticos y signos necesarios para análisis posterior. Debería usarse `PARAM_RAW_TRIMMED` con sanitización posterior específica. Severidad: Media. 【F:local/educambot/classes/local/logger.php†L44-L80】
2. **C2 – `manage.php` aplica `PARAM_TEXT` a `search`**: el tipo elimina caracteres especiales y puede distorsionar búsquedas con símbolos (`+`, `?`) usados en expresiones. Recomendado `PARAM_RAW_TRIMMED`. Severidad: Baja. 【F:local/educambot/manage.php†L36-L52】
3. **C3 – Falta comprobación de JSON en widget**: `widget.js` ignora excepciones al parsear config, pero no valida que `payload.response` sea string; si el backend retorna objeto malicioso podría inyectarse HTML. Requiere sanitizar/escapar. Severidad: Media. 【F:local/educambot/amd/src/widget.js†L74-L137】

### C.3 Vulnerabilidades de seguridad

1. **S1 – XSS potencial en respuestas**: `service.php` devuelve HTML sin sanitizar adicional; aunque `engine` usa `format_text` y `purify_html`, cualquier configuración errónea o respuesta maliciosa podría inyectar scripts. Requiere reforzar `format_text` con `['context' => context_system::instance()]`. Severidad: Media. 【F:local/educambot/classes/bot/engine.php†L64-L119】
2. **S2 – Falta de `sesskey` en `manage.php` para acciones GET**: el formulario de búsqueda envía parámetros sin token, pero las acciones sensibles sí lo incluyen; no se detecta CSRF crítico.
3. **S3 – Acceso a archivo `pluginfile` restringido a `local/educambot:manage`, correcto. Sin hallazgos críticos adicionales.

### C.4 Problemas de base de datos

1. **D1 – Falta de índices full-text**: Tablas `local_educambot_rule` y `_knowledge` carecen de índices que aceleren búsquedas por texto completo, generando escaneos completos en catálogos amplios. Severidad: Alta. 【F:local/educambot/db/install.xml†L5-L110】
2. **D2 – `knowledge_repository::search` realiza scoring en PHP sobre colecciones completas**: no hay paginación ni filtros SQL previos, lo que escala mal. Severidad: Alta. 【F:local/educambot/classes/local/knowledge_repository.php†L52-L152】
3. **D3 – Relaciones sin restricciones de unicidad**: `local_educambot_relation` permite duplicar pares `sourceid-targetid` porque el índice es no único. Debería ser único. Severidad: Media. 【F:local/educambot/db/install.xml†L97-L110】
4. **D4 – Seeder inserta reglas sin verificar duplicados**: múltiples ejecuciones podrían duplicar datos si se llama manualmente. Severidad: Baja. 【F:local/educambot/classes/local/setup/seed.php†L189-L274】

### C.5 Problemas de rendimiento

1. **P1 – `engine::get_suggestions` recorre todas las reglas**: sin límites SQL ni filtros por sugeridas/contexto, impactará al crecer a cientos de reglas. Severidad: Media. 【F:local/educambot/classes/bot/engine.php†L95-L152】
2. **P2 – `context_provider::get_courses` llama `enrol_get_users_courses` sin caché persistente**: se ejecuta en cada petición del widget, incluso cuando no se necesita. Severidad: Media. 【F:local/educambot/classes/local/context_provider.php†L250-L309】
3. **P3 – `knowledge_repository::expand_with_relations` consulta `get_entries()` dentro del bucle**: aunque cacheado en memoria, con miles de entradas la expansión en PHP puede ser costosa; requiere recorte por puntuación antes de expandir. Severidad: Baja. 【F:local/educambot/classes/local/knowledge_repository.php†L209-L308】

### C.6 Incumplimientos de estándares Moodle

1. **M1 – `manage.php` mezcla lógica y presentación sin renderers**: no usa `output`/`renderables` dedicados; recomendable mover a `classes/output`. Severidad: Media. 【F:local/educambot/manage.php†L1-L220】
2. **M2 – Falta de pruebas unitarias/behat**: no existen carpetas `tests/`, incumple buenas prácticas de plugins modernos. Severidad: Media.
3. **M3 – Semántica del widget**: `styles.css` usa `.icon fa fa-paper-plane` (FontAwesome). Moodle 4 requiere `core/icon_system`; se debe reemplazar por `pix_icon`. Severidad: Baja. 【F:local/educambot/templates/widget.mustache†L23-L30】
4. **M4 – `widget.js` no usa el namespace AMD ni import de `core/ajax` o `core/str`**: aunque válido, limitar dependencias podría mejorar consistencia. Severidad: Baja.

## Sección D. Análisis de la base de conocimientos actual

- **Estructura**: Tablas `local_educambot_rule` (reglas), `local_educambot_log`, `local_educambot_unanswered`, `local_educambot_topic`, `local_educambot_knowledge`, `local_educambot_kn_topic`, `local_educambot_relation`, `local_educambot_kn_context`. Relaciones clave: `knowledge` ↔ `topic` (N:N), `knowledge` ↔ `context` (curso/rol/página), `relation` para enlaces temáticos. 【F:local/educambot/db/install.xml†L5-L147】
- **Contenido actual**: Seeder crea 10 entradas de conocimiento y 10 reglas iniciales. No hay categorías explícitas ni jerarquías avanzadas. 【F:local/educambot/classes/local/setup/seed.php†L67-L274】
- **Cobertura**: Enfocada en aspectos básicos (acceso, tareas, calificaciones). Insuficiente para un campus amplio.
- **Calidad**: Respuestas concisas pero limitadas; algunas referencias (como `{{courselist}}`) dependen de personalización disponible.
- **Indexación**: Sólo índices simples (`enabled`, `enabledsuggested`, `course_idx`); no hay full-text ni índices sobre tags/patrones. 【F:local/educambot/db/install.xml†L19-L147】
- **Uso efectivo**: Motor utiliza patrones, sinónimos, keywords y contextos de `local_educambot_rule`. El repositorio aprovecha `topics`, `contexts`, `relations` y `tags` al puntuar y expandir resultados. Sin embargo, campos como `type`, `externalurl`, `createdby/updatedby` no se explotan. 【F:local/educambot/classes/local/knowledge_repository.php†L52-L308】
- **Problemas**:
  - No existe IU para gestionar topics, contextos ni relaciones, dificultando su aprovechamiento.
  - `record_unanswered` podría perder tildes por `clean_param(PARAM_TEXT)` (ver C1), degradando datos.
  - Falta de escalabilidad por ausencia de índices y filtros.

## Sección E. Limitaciones y áreas de mejora

1. **Motor**: carece de pipeline modular (tokenización, stemming, sinónimos globales, contexto conversacional). No mantiene memoria de sesión ni reglas encadenadas.
2. **Base de conocimiento**: catálogo pequeño, sin categorías visibles ni proceso de import/export.
3. **IU**: widget sin personalización granular (posición, tamaño, avatar) ni accesibilidad avanzada (teclado, ARIA). No hay panel de analytics ni logs navegables.
4. **Integración Moodle**: no responde dinámicamente sobre calificaciones específicas, eventos de calendario detallados o progreso; `context_provider` ofrece datos pero no hay reglas que los consuman.
5. **Mantenibilidad**: ausencia de tests, documentación técnica limitada, y se depende de seeders para poblar datos iniciales.

## Plan de mejoras priorizado

### Fase 1 – Correcciones críticas (Alta)
- Endurecer sanitización en `logger` (`PARAM_RAW_TRIMMED` + `clean_text`).
- Añadir validación/saneamiento extra en respuestas HTML (contexto en `format_text`).
- Resolver duplicidad de sugerencias y garantizar fallback a conocimiento.
- Crear IU mínima para CRUD de conocimiento o bloquear seed repetido.

### Fase 2 – Optimización de base de datos (Alta)
- Añadir índices FULLTEXT (`pattern`, `synonyms`, `keywords`, `title`, `summary`).
- Incorporar filtros SQL previos en búsquedas (por estado, curso, tópicos).
- Aplicar caché MUC contextual (por rol/curso) y TTL configurable.
- Normalizar relaciones (`relation_idx` único) y uso de transacciones en administración.

### Fase 3 – Expansión de base de conocimientos (Alta)
- Crear catálogo inicial de 130+ FAQs (ver sección dedicada) con categorías obligatorias.
- Implementar scripts de importación (JSON/CSV) y comandos CLI para seed.
- Diseñar diccionario de sinónimos global.

### Fase 4 – Motor de razonamiento avanzado (Media)
- Modularizar NLP (normalización, tokenización, stopwords, stemming, entidades).
- Implementar matching multinivel (exacto, parcial, semántico, contextual) con ranking configurable.
- Construir motor de inferencia y reglas encadenadas.
- Añadir memoria conversacional y detección de intenciones.

### Fase 5 – UI/UX (Media)
- Rediseñar widget (posiciones configurables, avatar, quick replies, indicadores typing/estado).
- Mejorar accesibilidad (navegación con teclado, ARIA roles, contraste).
- Implementar historial persistente y botones de acciones rápidas.

### Fase 6 – Panel de administración (Media-Baja)
- CRUD completo de conocimiento, sinónimos, patrones y relaciones.
- Import/export, buscador avanzado, testing de patrones.
- Dashboard de estadísticas, logs navegables, exportación.

### Fase 7 – Integración avanzada (Baja)
- Respuestas contextuales sobre cursos, actividades y calificaciones (APIs `core_course`, `mod_assign`, `calendar`).
- Motor proactivo según navegación reciente y rol del usuario.

## Arquitectura propuesta

### G.1 Diagrama de componentes (futuro)

```
[Widget Web Component] --JSON--> [Controller PHP]
        |                            |
        v                            v
[NLP Pipeline] ---> [Matching Engine] ---> [Inference Engine]
        |                    |                    |
 [Synonym Service]   [Rule Store]         [Context Manager]
        |                    |                    |
 [Admin CRUD UI] <--> [Knowledge Service] <--> [MUC + DB]
                            |
                     [Analytics Service]
```

### G.2 Diagrama de clases clave

- `nlp\normalizer`, `nlp\tokenizer`, `nlp\stemmer`
- `matching\exact_matcher`, `matching\partial_matcher`, `matching\semantic_matcher`
- `inference\rule_engine`, `inference\context_chain`
- `context\session_memory`, `context\user_profile`
- `knowledge\repository`, `knowledge\importer`, `knowledge\synonym_manager`

### G.3 Flujo de procesamiento propuesto

1. Normalizar entrada (minúsculas, quitar acentos, stemming).
2. Tokenizar y remover stopwords; detectar entidades (curso, actividad, fechas).
3. Ejecutar niveles de matching con pesos configurables.
4. Combinar resultados en el motor de inferencia (reglas + conocimiento + contexto).
5. Generar respuesta compuesta, aplicar reglas encadenadas y sugerencias.
6. Persistir en memoria conversacional y logs estructurados.

### G.4 Modelo de datos propuesto

- Nuevas tablas: `local_educambot_category`, `local_educambot_synonym`, `local_educambot_pattern`, `local_educambot_session_memory`.
- Índices FULLTEXT (`pattern`, `response`, `knowledge.content`).
- Claves foráneas para relaciones, cascadas definidas.
- Uso de `db/caches.php` para caches específicos por categoría/rol.

### G.5 Patrones recomendados

- **Pipeline** para NLP.
- **Chain of Responsibility** para niveles de matching.
- **Rule Engine** con DSL ligera para inferencia.
- **Observer/Event** para logging y analytics.

## Propuesta de base de conocimientos inicial (130 FAQs)

A continuación se presenta la base ampliada organizada por categorías obligatorias. Cada entrada incluye patrones, keywords, sinónimos, respuesta, prioridad y relaciones sugeridas. Los identificadores siguen numeración global para facilitar referencias.

### Categoría 1: Acceso y navegación (FAQ #1 – #18)

#### FAQ #1
Pregunta principal: ¿Cómo inicio sesión en Moodle?

Patrones de matching:
- ¿Cómo ingreso a Moodle?
- iniciar sesión en la plataforma
- Login Moodle paso a paso

Keywords: [login, acceso, credenciales]

Sinónimos relevantes:
- acceso → [entrada, ingreso]
- credenciales → [usuario, contraseña]

Respuesta:
Visita la página principal del campus y pulsa **Acceder**. Introduce tu nombre de usuario institucional y tu contraseña actual. Si es tu primera vez, el sistema solicitará actualizar la contraseña y aceptar los términos de uso.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #2: Recuperar contraseña olvidada.
- FAQ #15: Activar autenticación de dos pasos.

#### FAQ #2
Pregunta principal: ¿Cómo recupero mi contraseña si la olvidé?

Patrones de matching:
- Olvidé mi contraseña de Moodle
- Recuperar acceso plataforma
- Forgot Moodle password

Keywords: [contraseña, recuperar, restablecer]

Sinónimos relevantes:
- recuperar → [restablecer, resetear]
- contraseña → [clave, password]

Respuesta:
En la pantalla de acceso selecciona **¿Olvidó su nombre de usuario o contraseña?**, escribe tu correo institucional y envía la solicitud. Recibirás un mensaje con un enlace válido por 30 minutos para crear una nueva contraseña segura.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #1: Iniciar sesión.
- FAQ #16: No recibo el correo de recuperación.

#### FAQ #3
Pregunta principal: ¿Cómo cambio mi contraseña actual?

Patrones de matching:
- Cambiar password Moodle
- Actualizar contraseña segura
- Modificar clave de acceso

Keywords: [contraseña, cambiar, seguridad]

Sinónimos relevantes:
- cambiar → [actualizar, modificar]
- seguridad → [protección, resguardo]

Respuesta:
Después de iniciar sesión, ve a **Perfil > Preferencias > Cambiar contraseña**. Introduce tu contraseña actual y la nueva contraseña dos veces. Debe tener al menos 8 caracteres, combinar mayúsculas, minúsculas y un símbolo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #2: Recuperar contraseña.
- FAQ #15: Activar autenticación de dos pasos.

#### FAQ #4
Pregunta principal: ¿Cómo actualizo mi correo alternativo de contacto?

Patrones de matching:
- Cambiar correo de respaldo Moodle
- Actualizar email secundario
- Email alterno plataforma

Keywords: [correo, alternativo, notificaciones]

Sinónimos relevantes:
- correo → [email, e-mail]
- alternativo → [secundario, auxiliar]

Respuesta:
Ingresa a **Perfil > Preferencias > Cuenta > Información personal** y edita el campo "Correo alternativo". Moodle usará esta dirección para avisos críticos cuando el buzón institucional falle.

Prioridad: Media

Conocimientos relacionados:
- FAQ #5: Configurar notificaciones.
- FAQ #11: Resolver problemas con correos de Moodle.

#### FAQ #5
Pregunta principal: ¿Cómo configuro mis notificaciones?

Patrones de matching:
- Ajustar notificaciones Moodle
- Configurar avisos por correo
- Notification settings Moodle

Keywords: [notificaciones, avisos, correo]

Sinónimos relevantes:
- notificaciones → [avisos, alertas]
- configurar → [ajustar, personalizar]

Respuesta:
Ve a **Perfil > Preferencias > Preferencias de notificación**. Activa o desactiva cada canal (correo, app móvil, web) para mensajes, foros, tareas y calificaciones según tus necesidades y guarda los cambios.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #4: Correo alternativo.
- FAQ #18: Cambiar idioma de la plataforma.

#### FAQ #6
Pregunta principal: ¿Cómo navego en mi Área personal?

Patrones de matching:
- Uso del dashboard Moodle
- Qué ver en Área personal
- Personal dashboard guide

Keywords: [área personal, dashboard, navegación]

Sinónimos relevantes:
- área personal → [dashboard, panel]
- navegación → [explorar, recorrer]

Respuesta:
El Área personal muestra el bloque **Línea de tiempo** con próximas actividades, acceso rápido a cursos favoritos y mensajes recientes. Puedes reorganizar bloques activando el modo edición y arrastrando cada elemento.

Prioridad: Media

Conocimientos relacionados:
- FAQ #7: Fijar cursos como favoritos.
- FAQ #12: Personalizar bloques del dashboard.

#### FAQ #7
Pregunta principal: ¿Cómo marco un curso como favorito?

Patrones de matching:
- Añadir curso a favoritos
- Destacar asignatura en el dashboard
- Star course Moodle

Keywords: [favorito, destacar, curso]

Sinónimos relevantes:
- favorito → [destacado, anclado]
- curso → [asignatura, clase]

Respuesta:
En el listado de cursos pulsa el icono de estrella junto al nombre. El curso aparecerá en la sección **Favoritos** del dashboard y se ordenará al inicio de la lista.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #6: Navegar el Área personal.
- FAQ #9: Buscar cursos por nombre.

#### FAQ #8
Pregunta principal: ¿Cómo busco contenido dentro de un curso?

Patrones de matching:
- Buscar recursos en curso
- Encontrar materiales rápidamente
- Search inside course

Keywords: [buscar, recursos, índice]

Sinónimos relevantes:
- buscar → [localizar, encontrar]
- índice → [tabla de contenido, navegación]

Respuesta:
Utiliza el índice lateral del curso para saltar entre secciones o presiona `Ctrl+F` dentro de la página. Algunos cursos incluyen un bloque "Buscar" que permite filtrar actividades por palabra clave.

Prioridad: Media

Conocimientos relacionados:
- FAQ #6: Área personal.
- FAQ #20: Estructura del curso.

#### FAQ #9
Pregunta principal: ¿Cómo encuentro un curso específico?

Patrones de matching:
- Buscar curso por nombre
- Localizar asignatura en Moodle
- Search course catalog

Keywords: [buscar, curso, catálogo]

Sinónimos relevantes:
- localizar → [encontrar, ubicar]
- catálogo → [listado, directorio]

Respuesta:
Desde **Mis cursos** utiliza el cuadro de búsqueda y escribe parte del título o código de la asignatura. También puedes entrar a **Área personal > Todos los cursos** y filtrar por categoría o fecha.

Prioridad: Media

Conocimientos relacionados:
- FAQ #7: Favoritos.
- FAQ #24: Curso inscrito no visible.

#### FAQ #10
Pregunta principal: ¿Cómo cierro sesión de forma segura?

Patrones de matching:
- Cerrar sesión Moodle
- Logout seguro plataforma
- Finalizar sesión correctamente

Keywords: [cerrar, sesión, seguridad]

Sinónimos relevantes:
- cerrar → [salir, finalizar]
- seguridad → [protección]

Respuesta:
Haz clic en tu avatar, selecciona **Cerrar sesión** y confirma. Si usaste un equipo público, borra la caché del navegador y cierra todas las pestañas del campus para evitar accesos no autorizados.

Prioridad: Media

Conocimientos relacionados:
- FAQ #14: Mensaje de sesión caducada.
- FAQ #15: Autenticación de dos pasos.

#### FAQ #11
Pregunta principal: ¿Por qué no recibo correos de Moodle?

Patrones de matching:
- No llegan notificaciones
- Problemas con correos de la plataforma
- Emails Moodle missing

Keywords: [correo, notificación, problema]

Sinónimos relevantes:
- correo → [email, mensaje]
- problema → [incidencia, falla]

Respuesta:
Revisa tu carpeta de spam, agrega la dirección del campus a tu lista segura y confirma que el canal correo esté activo en **Preferencias de notificación**. Verifica también el correo alternativo configurado.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #4: Correo alternativo.
- FAQ #16: Correo de recuperación no llega.

#### FAQ #12
Pregunta principal: ¿Cómo personalizo los bloques del dashboard?

Patrones de matching:
- Reordenar bloques dashboard
- Personalizar Área personal
- Edit dashboard blocks

Keywords: [bloques, personalizar, dashboard]

Sinónimos relevantes:
- personalizar → [ajustar, configurar]
- bloques → [widgets, paneles]

Respuesta:
Activa el modo edición en el dashboard, arrastra los bloques a la posición deseada y usa el menú de cada bloque para mostrar u ocultar contenido. Guarda la configuración antes de salir.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #6: Área personal.
- FAQ #31: Bloques dentro del curso.

#### FAQ #13
Pregunta principal: ¿Cómo cambio mi foto de perfil?

Patrones de matching:
- Subir avatar Moodle
- Cambiar imagen de perfil
- Update profile picture

Keywords: [perfil, foto, avatar]

Sinónimos relevantes:
- foto → [imagen, avatar]
- subir → [cargar, actualizar]

Respuesta:
En **Perfil > Editar perfil**, busca la sección Imagen del usuario y arrastra tu foto (formatos PNG o JPG, máximo 2 MB). Guarda los cambios y recarga la página para ver la actualización.

Prioridad: Media

Conocimientos relacionados:
- FAQ #4: Correo alternativo.
- FAQ #64: Ajustar privacidad del perfil.

#### FAQ #14
Pregunta principal: ¿Qué hago si aparece el mensaje "Su sesión ha caducado"?

Patrones de matching:
- Sesión caducada Moodle
- Your session has timed out
- Error sesión expirada

Keywords: [sesión, caducada, error]

Sinónimos relevantes:
- caducada → [expirada, vencida]
- error → [aviso, mensaje]

Respuesta:
Esto ocurre cuando pasas mucho tiempo sin actividad. Vuelve a la pantalla de acceso, inicia sesión nuevamente y guarda tu trabajo con frecuencia. Considera trabajar en documentos offline para no perder avances.

Prioridad: Media

Conocimientos relacionados:
- FAQ #10: Cerrar sesión segura.
- FAQ #65: Mantener la sesión activa.

#### FAQ #15
Pregunta principal: ¿Cómo activo la autenticación de dos pasos?

Patrones de matching:
- Habilitar 2FA Moodle
- Autenticación en dos factores
- Two factor login

Keywords: [2FA, seguridad, autenticación]

Sinónimos relevantes:
- autenticación → [verificación, validación]
- seguridad → [protección]

Respuesta:
Si tu institución lo ofrece, entra a **Perfil > Preferencias > Seguridad > Autenticación multifactor**. Sigue el asistente para vincular una app TOTP (Google Authenticator, Authy) y guarda los códigos de recuperación.

Prioridad: Media

Conocimientos relacionados:
- FAQ #1: Iniciar sesión.
- FAQ #3: Cambiar contraseña.

#### FAQ #16
Pregunta principal: No recibí el correo para restablecer mi contraseña

Patrones de matching:
- Correo de recuperación no llega
- Reset password email missing
- Problema restablecer clave

Keywords: [correo, recuperación, problema]

Sinónimos relevantes:
- recuperación → [restablecimiento]
- problema → [incidencia]

Respuesta:
Revisa la carpeta de spam, espera al menos 10 minutos y solicita nuevamente el enlace confirmando que el correo institucional esté bien escrito. Si persiste, contacta al soporte indicando tu usuario.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #2: Recuperar contraseña.
- FAQ #11: Correos del campus.

#### FAQ #17
Pregunta principal: ¿Cómo cambio el idioma de la interfaz?

Patrones de matching:
- Cambiar idioma plataforma
- Moodle language settings
- Interface language

Keywords: [idioma, configuración, interfaz]

Sinónimos relevantes:
- idioma → [lenguaje]
- configuración → [ajuste]

Respuesta:
Ve a **Perfil > Preferencias > Idioma preferido** y selecciona el idioma disponible. Algunos cursos pueden forzar un idioma específico; si necesitas cambiarlo consulta con el docente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #5: Notificaciones.
- FAQ #18: Idioma por curso.

#### FAQ #18
Pregunta principal: ¿Puedo usar un idioma diferente por curso?

Patrones de matching:
- Idioma por curso Moodle
- Course language settings
- Cambiar idioma asignatura

Keywords: [idioma, curso, configuración]

Sinónimos relevantes:
- curso → [asignatura]
- configuración → [ajuste]

Respuesta:
Algunos cursos ofrecen un bloque de Configuración donde puedes elegir idioma local. Si no aparece, el curso usa el idioma del sitio. Solicita al docente habilitar traducciones cuando sea necesario.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #17: Idioma general.
- FAQ #73: Problemas de traducción de contenidos.

### Categoría 2: Gestión de cursos (FAQ #19 – #40)

#### FAQ #19
Pregunta principal: ¿Cómo me inscribo en un curso autoinscribible?

Patrones de matching:
- Autoinscripción curso Moodle
- Enrolarse con clave de matriculación
- Self enrolment instructions

Keywords: [autoinscripción, clave, curso]

Sinónimos relevantes:
- autoinscripción → [matriculación automática]
- clave → [código]

Respuesta:
En el catálogo de cursos selecciona la asignatura, pulsa **Inscribirme** y, si se solicita, introduce la clave de matriculación proporcionada por el docente. Moodle confirmará la inscripción mediante un mensaje en pantalla.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #20: Comprender la estructura del curso.
- FAQ #23: Dar de baja la inscripción.

#### FAQ #20
Pregunta principal: ¿Cómo está organizado un curso típico?

Patrones de matching:
- Estructura curso Moodle
- Secciones y temas del curso
- Course layout explanation

Keywords: [estructura, secciones, organización]

Sinónimos relevantes:
- estructura → [organización]
- secciones → [temas]

Respuesta:
La mayoría de cursos se dividen en secciones temáticas o semanales con recursos y actividades. El índice lateral permite navegar rápidamente y el bloque de progreso muestra qué elementos tienes pendientes.

Prioridad: Media

Conocimientos relacionados:
- FAQ #21: Información general del curso.
- FAQ #31: Uso de bloques dentro del curso.

#### FAQ #21
Pregunta principal: ¿Dónde encuentro la información general del curso?

Patrones de matching:
- Ver resumen del curso
- Información docente y objetivos
- Course summary location

Keywords: [información, resumen, docente]

Sinónimos relevantes:
- información → [detalles]
- resumen → [descripción]

Respuesta:
En la primera sección del curso suele aparecer un bloque con la descripción, objetivos y datos de contacto del docente. También puedes consultar el enlace **Más información** si el profesor lo ha habilitado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #22: Lista de participantes.
- FAQ #35: Identificar al profesor asignado.

#### FAQ #22
Pregunta principal: ¿Cómo veo la lista de participantes?

Patrones de matching:
- Participantes del curso
- Ver compañeros inscritos
- Course participants list

Keywords: [participantes, lista, compañeros]

Sinónimos relevantes:
- lista → [relación]
- compañeros → [colegas]

Respuesta:
Dentro del curso selecciona **Participantes** en el menú lateral. Puedes filtrar por rol (estudiante, profesor, tutor) y enviar mensajes privados desde el mismo listado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #21: Información del curso.
- FAQ #54: Mensajería interna.

#### FAQ #23
Pregunta principal: ¿Cómo me doy de baja de un curso?

Patrones de matching:
- Anular inscripción curso
- Unenrol from course
- Retirar matrícula

Keywords: [baja, inscripción, curso]

Sinónimos relevantes:
- baja → [retirar, anular]
- inscripción → [matrícula]

Respuesta:
En la página de **Participantes** busca tu nombre, abre el menú de opciones y elige **Cancelar inscripción**. Si el botón no aparece, contacta al administrador o docente para solicitar la baja manual.

Prioridad: Media

Conocimientos relacionados:
- FAQ #19: Autoinscripción.
- FAQ #24: Curso no visible tras matriculación.

#### FAQ #24
Pregunta principal: No puedo ver un curso en el que estoy inscrito

Patrones de matching:
- Curso no aparece en Moodle
- Missing enrolled course
- Problema visibilidad curso

Keywords: [curso, visibilidad, problema]

Sinónimos relevantes:
- visibilidad → [visualización]
- problema → [incidencia]

Respuesta:
Verifica si el curso aún no ha iniciado o está oculto por el docente. Asegúrate de usar la cuenta correcta y revisa el filtro de periodos (En progreso, Futuros). Si continúa, abre un ticket adjuntando la constancia de matrícula.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #19: Autoinscripción.
- FAQ #66: Errores de matriculación frecuentes.

#### FAQ #25
Pregunta principal: ¿Cómo consulto el código o ID del curso?

Patrones de matching:
- Ver código de asignatura
- Course ID Moodle
- Identificador curso

Keywords: [código, identificador, curso]

Sinónimos relevantes:
- código → [ID]
- asignatura → [curso]

Respuesta:
El código aparece junto al título del curso y también en la URL (parámetro `id`). Puedes copiarlo desde **Participantes** o desde la página de configuración si eres docente.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #21: Información general.
- FAQ #33: Calendario del curso.

#### FAQ #26
Pregunta principal: ¿Cómo accedo a recursos multimedia del curso?

Patrones de matching:
- Ver videos del curso
- Acceder a archivos multimedia
- Multimedia resources Moodle

Keywords: [multimedia, video, recursos]

Sinónimos relevantes:
- multimedia → [video, audio]
- recursos → [materiales]

Respuesta:
Haz clic en el recurso correspondiente (video, SCORM, audio). Asegúrate de permitir ventanas emergentes y de usar un navegador actualizado para garantizar la reproducción correcta.

Prioridad: Media

Conocimientos relacionados:
- FAQ #27: Resolver problemas al reproducir videos.
- FAQ #72: Navegadores compatibles.

#### FAQ #27
Pregunta principal: ¿Qué hago si un video no se reproduce?

Patrones de matching:
- Video no carga Moodle
- Problema reproducción multimedia
- Video playback issue

Keywords: [video, problema, reproducción]

Sinónimos relevantes:
- problema → [fallo]
- reproducir → [ver, ejecutar]

Respuesta:
Actualiza tu navegador, borra la caché, habilita ventanas emergentes y prueba con otra conexión. Si el error persiste, informa al soporte indicando la URL del recurso y una captura del mensaje.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #26: Recursos multimedia.
- FAQ #71: Requisitos técnicos mínimos.

#### FAQ #28
Pregunta principal: ¿Cómo descargo archivos del curso?

Patrones de matching:
- Descargar recursos PDF
- Download course files
- Guardar materiales del curso

Keywords: [descargar, archivos, recursos]

Sinónimos relevantes:
- descargar → [bajar]
- archivos → [documentos]

Respuesta:
Haz clic en el archivo (PDF, PPT, ZIP) y usa el botón **Descargar** del visor. Si el docente bloqueó descargas, solicita una copia alternativa o consulta la biblioteca de recursos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #26: Acceso a recursos multimedia.
- FAQ #41: Entregar tareas con archivos.

#### FAQ #29
Pregunta principal: ¿Cómo veo los anuncios del curso?

Patrones de matching:
- Foro de avisos curso
- Course announcements
- Comunicados docentes

Keywords: [avisos, anuncios, foro]

Sinónimos relevantes:
- avisos → [anuncios]
- foro → [tablero]

Respuesta:
Revisa el foro **Avisos** situado al inicio del curso. Estás suscrito automáticamente, por lo que recibirás correos por cada publicación del docente.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #54: Mensajería interna.
- FAQ #55: Calificar foros.

#### FAQ #30
Pregunta principal: ¿Cómo accedo al calendario del curso?

Patrones de matching:
- Calendario curso Moodle
- Course calendar access
- Ver fechas importantes

Keywords: [calendario, curso, eventos]

Sinónimos relevantes:
- calendario → [agenda]
- eventos → [fechas]

Respuesta:
Dentro del curso selecciona **Calendario** desde la navegación secundaria o consulta el bloque Calendario en tu dashboard filtrando por la asignatura deseada.

Prioridad: Media

Conocimientos relacionados:
- FAQ #33: Crear eventos.
- FAQ #70: Exportar el calendario institucional.

#### FAQ #31
Pregunta principal: ¿Cómo añado bloques adicionales en mi curso?

Patrones de matching:
- Bloques personalizados curso
- Add blocks course page
- Widgets en curso

Keywords: [bloques, curso, personalizar]

Sinónimos relevantes:
- bloques → [widgets]
- personalizar → [configurar]

Respuesta:
Activa la edición, abre el panel lateral y usa **Añadir un bloque**. Puedes agregar calendario, progreso de finalización, HTML u otros bloques disponibles según permisos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #12: Bloques en el dashboard.
- FAQ #36: Activar el modo edición.

#### FAQ #32
Pregunta principal: ¿Cómo cambio el formato del curso (temas, semanal)?

Patrones de matching:
- Cambiar formato curso
- Topics vs weekly format
- Configurar estructura curso

Keywords: [formato, curso, configuración]

Sinónimos relevantes:
- formato → [estructura]
- configuración → [ajuste]

Respuesta:
Como docente, ve a **Editar ajustes** y en "Formato del curso" selecciona Temas, Semanal u otra opción disponible. Guarda los cambios para que las secciones se reorganizen automáticamente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #20: Organización del curso.
- FAQ #31: Añadir bloques.

#### FAQ #33
Pregunta principal: ¿Cómo agrego eventos al calendario del curso?

Patrones de matching:
- Crear evento calendario curso
- Add course event
- Programar recordatorio Moodle

Keywords: [evento, calendario, curso]

Sinónimos relevantes:
- evento → [actividad]
- programar → [agendar]

Respuesta:
En el calendario pulsa **Nuevo evento**, selecciona "Evento del curso", completa título, fecha, hora y descripción. El evento será visible para todos los participantes automáticamente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #30: Acceso al calendario.
- FAQ #34: Suscribir calendario a agenda externa.

#### FAQ #34
Pregunta principal: ¿Cómo suscribo el calendario del curso a mi agenda personal?

Patrones de matching:
- Suscribir calendario Moodle
- Export calendar iCal
- Sync course calendar

Keywords: [calendario, suscribir, iCal]

Sinónimos relevantes:
- suscribir → [sincronizar]
- agenda → [calendario personal]

Respuesta:
En el calendario elige **Exportar calendario**, copia la URL iCal del curso y agrégala en Google Calendar, Outlook u otra agenda compatible para sincronizar eventos automáticamente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #33: Crear eventos.
- FAQ #70: Exportar calendario general.

#### FAQ #35
Pregunta principal: ¿Cómo identifico a mi profesor asignado?

Patrones de matching:
- Identificar docente curso
- Who is my teacher Moodle
- Profesor responsable asignatura

Keywords: [docente, profesor, responsable]

Sinónimos relevantes:
- docente → [profesor, instructor]
- responsable → [titular]

Respuesta:
En **Participantes** filtra por rol Profesor. Verás el nombre, correo y opciones de contacto. También suele aparecer en el resumen inicial del curso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #22: Participantes.
- FAQ #54: Enviar mensajes privados.

#### FAQ #36
Pregunta principal: ¿Cómo activo el modo edición en un curso?

Patrones de matching:
- Activar edición Moodle
- Turn editing on
- Edit mode course

Keywords: [edición, modo, curso]

Sinónimos relevantes:
- edición → [modificación]
- curso → [asignatura]

Respuesta:
Como docente pulsa el botón **Activar edición** situado en la parte superior. Podrás añadir recursos, actividades, bloques y reorganizar secciones con arrastrar y soltar.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #31: Añadir bloques.
- FAQ #43: Crear una tarea evaluable.

#### FAQ #37
Pregunta principal: ¿Cómo oculto una sección del curso?

Patrones de matching:
- Ocultar tema curso
- Hide section Moodle
- Mostrar/ocultar contenido

Keywords: [ocultar, sección, visibilidad]

Sinónimos relevantes:
- ocultar → [esconder]
- visibilidad → [visualización]

Respuesta:
En modo edición abre el menú de la sección y selecciona **Ocultar sección**. Los estudiantes ya no la verán, aunque tú podrás seguir editándola.

Prioridad: Media

Conocimientos relacionados:
- FAQ #36: Activar edición.
- FAQ #40: Restringir acceso a actividades.

#### FAQ #38
Pregunta principal: ¿Cómo restauro un curso desde una copia de seguridad?

Patrones de matching:
- Restaurar curso backup
- Importar copia Moodle
- Restore course file

Keywords: [restaurar, backup, importar]

Sinónimos relevantes:
- restaurar → [recuperar]
- backup → [respaldo]

Respuesta:
En **Administrador del curso > Restaurar** sube el archivo `.mbz`, elige si deseas combinar con el contenido existente o reemplazarlo y sigue el asistente paso a paso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #39: Importar contenido de otro curso.
- FAQ #83: Programar respaldos automáticos.

#### FAQ #39
Pregunta principal: ¿Cómo copio contenido de otro curso?

Patrones de matching:
- Importar secciones curso
- Reusar contenido Moodle
- Course import tool

Keywords: [importar, copiar, contenido]

Sinónimos relevantes:
- copiar → [clonar]
- contenido → [material]

Respuesta:
Selecciona **Administrador del curso > Importar**, elige el curso origen, marca las secciones o actividades que quieres copiar y completa el asistente para replicarlas en tu curso actual.

Prioridad: Media

Conocimientos relacionados:
- FAQ #36: Activar edición.
- FAQ #38: Restaurar curso completo.

#### FAQ #40
Pregunta principal: ¿Cómo restrinjo el acceso a una actividad por fecha o calificación?

Patrones de matching:
- Restricciones de acceso Moodle
- Conditional release activity
- Limitar acceso por calificación

Keywords: [restricción, acceso, condición]

Sinónimos relevantes:
- restricción → [condición]
- acceso → [entrada]

Respuesta:
En la configuración de la actividad desplázate a **Restricciones de acceso** y añade condiciones por fecha, finalización o calificación mínima. Puedes combinar condiciones usando operadores lógicos.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #44: Finalización de actividades.
- FAQ #85: Seguimiento del progreso.

### Categoría 3: Actividades y tareas (FAQ #41 – #67)

#### FAQ #41
Pregunta principal: ¿Cómo entrego una tarea en Moodle?

Patrones de matching:
- Enviar tarea paso a paso
- Subir assignment Moodle
- Submit assignment instructions

Keywords: [tarea, entrega, enviar]

Sinónimos relevantes:
- entregar → [subir, enviar]
- tarea → [assignment]

Respuesta:
Abre la actividad, revisa las instrucciones, pulsa **Agregar entrega**, adjunta los archivos requeridos o escribe en el editor y finaliza con **Enviar tarea**. Verifica que aparezca el recibo de entrega.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #42: Modificar entregas antes de la fecha límite.
- FAQ #46: Formatos de archivo permitidos.

#### FAQ #42
Pregunta principal: ¿Cómo edito mi entrega antes del cierre?

Patrones de matching:
- Editar entrega tarea
- Update submission Moodle
- Modificar archivos entregados

Keywords: [editar, entrega, tarea]

Sinónimos relevantes:
- editar → [actualizar]
- entrega → [submission]

Respuesta:
Si el docente lo permite y la fecha límite no ha pasado, pulsa **Editar entrega**, reemplaza archivos o ajusta el texto y confirma nuevamente con **Enviar tarea** para que quede registrada la versión más reciente.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #41: Entrega inicial.
- FAQ #47: Manejar archivos de gran tamaño.

#### FAQ #43
Pregunta principal: ¿Cómo creo una tarea como docente?

Patrones de matching:
- Crear actividad tarea
- Add assignment teacher
- Configurar tarea evaluable

Keywords: [crear, tarea, docente]

Sinónimos relevantes:
- crear → [añadir]
- docente → [profesor]

Respuesta:
En modo edición añade una actividad **Tarea**, define título, instrucciones, tipo de entrega, calificación y fechas. Revisa las opciones avanzadas como rúbrica, retroalimentación offline y recordatorios.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #36: Activar modo edición.
- FAQ #49: Calificar con rúbricas.

#### FAQ #44
Pregunta principal: ¿Cómo marco una actividad como completada?

Patrones de matching:
- Finalización de actividad
- Mark activity complete
- Seguimiento de progreso

Keywords: [finalización, actividad, progreso]

Sinónimos relevantes:
- finalización → [completar]
- progreso → [avance]

Respuesta:
Las actividades pueden marcarse manualmente (casilla que marcas tú) o automáticamente según condiciones (vista, calificación). Consulta el icono de finalización junto a cada actividad para confirmar su estado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #40: Restricciones de acceso.
- FAQ #85: Seguimiento de progreso del curso.

#### FAQ #45
Pregunta principal: ¿Cómo participo en un foro de discusión?

Patrones de matching:
- Publicar en foro Moodle
- Responder discusión
- Foro participación estudiantes

Keywords: [foro, participar, discusión]

Sinónimos relevantes:
- participar → [intervenir]
- discusión → [tema]

Respuesta:
Ingresa al foro, selecciona **Añadir un nuevo tema** o **Responder** bajo un mensaje existente. Escribe un asunto descriptivo, tu aporte, adjunta archivos si procede y publica respetando las normas de netiqueta.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #29: Foro de avisos.
- FAQ #55: Configurar foros calificados.

#### FAQ #46
Pregunta principal: ¿Qué formatos de archivo acepta la tarea?

Patrones de matching:
- Formatos permitidos tarea
- File types assignment
- Qué archivos subir

Keywords: [formatos, archivo, tarea]

Sinónimos relevantes:
- formatos → [tipos]
- archivo → [documento]

Respuesta:
En la configuración de la tarea revisa la sección **Tipos de archivo aceptados**. Por defecto se aceptan todos los formatos, pero el docente puede restringirlos a PDF, DOCX u otros específicos.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #41: Entregar tarea.
- FAQ #47: Archivos demasiado grandes.

#### FAQ #47
Pregunta principal: ¿Qué hago si mi archivo excede el tamaño permitido?

Patrones de matching:
- Archivo demasiado grande
- Upload file size exceeded
- Reducir tamaño tarea

Keywords: [archivo, tamaño, límite]

Sinónimos relevantes:
- tamaño → [peso]
- reducir → [comprimir]

Respuesta:
Comprime los archivos en ZIP, reduce la resolución de imágenes o divide el contenido en varios archivos si el docente lo permite. Contacta al profesor si necesitas un aumento temporal del límite.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #46: Formatos aceptados.
- FAQ #67: Errores al subir archivos.

#### FAQ #48
Pregunta principal: ¿Cómo envío una tarea con grabación de audio o video?

Patrones de matching:
- Entrega audio video
- Record submission Moodle
- Subir multimedia tarea

Keywords: [audio, video, grabación]

Sinónimos relevantes:
- grabación → [clip]
- subir → [adjuntar]

Respuesta:
Si la tarea permite grabaciones, usa el botón **Grabar audio/video** del repositorio. Concede permisos al navegador, revisa la reproducción y guarda antes de enviar la entrega final.

Prioridad: Media

Conocimientos relacionados:
- FAQ #46: Formatos permitidos.
- FAQ #68: Problemas con la grabación integrada.

#### FAQ #49
Pregunta principal: ¿Cómo califico tareas con rúbrica?

Patrones de matching:
- Calificar con rúbrica Moodle
- Rubric grading assignment
- Evaluar criterios tarea

Keywords: [rúbrica, calificar, criterios]

Sinónimos relevantes:
- rúbrica → [matriz de evaluación]
- calificar → [evaluar]

Respuesta:
Abre la entrega, despliega la rúbrica, selecciona el nivel correspondiente en cada criterio y añade comentarios. Moodle calculará automáticamente la puntuación total según los pesos definidos.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #43: Crear tarea.
- FAQ #50: Compartir retroalimentación con estudiantes.

#### FAQ #50
Pregunta principal: ¿Cómo veo la retroalimentación de mi tarea?

Patrones de matching:
- Ver comentarios tarea
- Feedback assignment Moodle
- Retroalimentación docente

Keywords: [retroalimentación, comentarios, tarea]

Sinónimos relevantes:
- retroalimentación → [feedback]
- comentarios → [observaciones]

Respuesta:
Ingresa nuevamente a la tarea después de ser calificada y consulta la sección **Comentarios de la entrega**. Descarga los archivos adjuntos con anotaciones o rúbricas evaluadas si el docente los proporcionó.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #41: Entrega de tareas.
- FAQ #57: Revisión de cuestionarios.

#### FAQ #51
Pregunta principal: ¿Cómo entrego en un taller colaborativo?

Patrones de matching:
- Taller Moodle envío
- Workshop submission
- Entrega taller colaborativo

Keywords: [taller, envío, colaboración]

Sinónimos relevantes:
- taller → [workshop]
- colaboración → [cooperativa]

Respuesta:
Durante la fase de envíos carga tu trabajo siguiendo el formato solicitado. Cuando inicie la fase de evaluación, revisa las instrucciones para calificar a tus compañeros y envía la valoración a tiempo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #52: Evaluar trabajos de compañeros.
- FAQ #58: Nota final del taller.

#### FAQ #52
Pregunta principal: ¿Cómo evalúo a mis compañeros en un taller?

Patrones de matching:
- Evaluación entre pares
- Peer assessment workshop
- Calificar compañeros

Keywords: [evaluación, pares, taller]

Sinónimos relevantes:
- evaluación → [valoración]
- pares → [compañeros]

Respuesta:
En la fase de evaluación abre cada entrega asignada, completa la rúbrica o guía y envía tus comentarios. Procura ser objetivo y respetuoso; tus evaluaciones influyen en la calificación final.

Prioridad: Media

Conocimientos relacionados:
- FAQ #51: Enviar al taller.
- FAQ #58: Cómo se calcula la nota final.

#### FAQ #53
Pregunta principal: ¿Cómo participo en una wiki grupal?

Patrones de matching:
- Wiki colaborativa Moodle
- Editar wiki grupo
- Contribuir a wiki curso

Keywords: [wiki, grupo, colaborar]

Sinónimos relevantes:
- colaborar → [co-crear]
- editar → [modificar]

Respuesta:
Ingresa a la wiki, selecciona tu grupo si corresponde y pulsa **Editar**. Añade contenido en formato HTML simple, guarda los cambios y consulta el historial para ver las contribuciones de cada integrante.

Prioridad: Media

Conocimientos relacionados:
- FAQ #63: Gestionar grupos de trabajo.
- FAQ #59: Revisar historial de wiki.

#### FAQ #54
Pregunta principal: ¿Cómo envío mensajes privados a otros usuarios?

Patrones de matching:
- Mensajería interna Moodle
- Enviar mensaje privado
- Contactar profesor

Keywords: [mensaje, privado, comunicación]

Sinónimos relevantes:
- mensaje → [comunicación]
- privado → [directo]

Respuesta:
Pulsa el icono de mensajes, busca al usuario y escribe tu texto. Puedes adjuntar archivos pequeños y habilitar notificaciones push en la app móvil para no perder ninguna respuesta.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #35: Identificar al profesor.
- FAQ #90: Configurar bandeja de mensajes.

#### FAQ #55
Pregunta principal: ¿Cómo configuro un foro para calificación?

Patrones de matching:
- Foro calificado Moodle
- Graded forum setup
- Evaluar participación foro

Keywords: [foro, calificación, configuración]

Sinónimos relevantes:
- calificación → [nota]
- participación → [aportación]

Respuesta:
Al crear o editar un foro habilita la sección **Calificaciones**, elige tipo (puntuación o escala) y método de agregación. Define si se calificarán discusiones individuales o mensajes completos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #45: Participar en foros.
- FAQ #57: Revisar calificación del cuestionario.

#### FAQ #56
Pregunta principal: ¿Cómo realizo un cuestionario en línea?

Patrones de matching:
- Rendir quiz Moodle
- Tomar examen online
- Quiz attempt instructions

Keywords: [cuestionario, intento, examen]

Sinónimos relevantes:
- cuestionario → [quiz]
- examen → [evaluación]

Respuesta:
Revisa el tiempo disponible, pulsa **Intentar cuestionario ahora**, responde cada pregunta y guarda periódicamente. Envía el intento antes de que expire el tiempo o la fecha límite.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #57: Revisar resultados del cuestionario.
- FAQ #60: Nuevos intentos permitidos.

#### FAQ #57
Pregunta principal: ¿Cómo reviso la calificación de un cuestionario?

Patrones de matching:
- Ver resultados quiz
- Quiz review Moodle
- Revisión de intento

Keywords: [calificación, cuestionario, revisión]

Sinónimos relevantes:
- calificación → [nota]
- revisión → [análisis]

Respuesta:
Tras enviar el cuestionario pulsa **Revisar intento** para ver puntuación, respuestas correctas y retroalimentación si está habilitado. La nota también se registra en el libro de calificaciones.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #56: Realizar el cuestionario.
- FAQ #61: Comentarios en cuestionarios.

#### FAQ #58
Pregunta principal: ¿Cómo se calcula la nota final de un taller?

Patrones de matching:
- Nota final taller
- Workshop grading calculation
- Calificación taller Moodle

Keywords: [nota, taller, cálculo]

Sinónimos relevantes:
- nota → [calificación]
- cálculo → [ponderación]

Respuesta:
La nota final combina la calificación de tu entrega y la calidad de tus evaluaciones a pares según los pesos definidos (por ejemplo 60/40). Puedes ver el desglose en la pestaña **Resultados** del taller.

Prioridad: Media

Conocimientos relacionados:
- FAQ #51: Enviar al taller.
- FAQ #52: Evaluar a compañeros.

#### FAQ #59
Pregunta principal: ¿Cómo consulto el historial de una wiki?

Patrones de matching:
- Historial wiki Moodle
- Wiki version history
- Cambios en wiki

Keywords: [historial, wiki, versiones]

Sinónimos relevantes:
- historial → [registro]
- versiones → [revisiones]

Respuesta:
Dentro de la wiki selecciona la pestaña **Historial** para revisar cada edición, quién la realizó y comparar versiones. Puedes revertir cambios si tienes permisos de edición.

Prioridad: Media

Conocimientos relacionados:
- FAQ #53: Participar en wiki grupal.
- FAQ #63: Administración de grupos.

#### FAQ #60
Pregunta principal: ¿Cómo solicito un nuevo intento de cuestionario?

Patrones de matching:
- Solicitar reintento quiz
- Another quiz attempt
- Reabrir cuestionario

Keywords: [reintento, cuestionario, permiso]

Sinónimos relevantes:
- reintento → [nuevo intento]
- permiso → [autorización]

Respuesta:
Si el cuestionario limita intentos, contacta al docente para solicitar un intento adicional justificando el motivo. El profesor puede reabrir el intento desde la página de resultados.

Prioridad: Media

Conocimientos relacionados:
- FAQ #56: Realizar cuestionario.
- FAQ #67: Errores al subir archivos.

#### FAQ #61
Pregunta principal: ¿Cómo agrego comentarios personalizados en un cuestionario?

Patrones de matching:
- Comentarios en cuestionario
- Quiz feedback settings
- Retroalimentación por pregunta

Keywords: [comentarios, cuestionario, feedback]

Sinónimos relevantes:
- comentarios → [observaciones]
- feedback → [retroalimentación]

Respuesta:
Al editar el cuestionario utiliza la pestaña **Retroalimentación general** o agrega retroalimentación por respuesta dentro de cada pregunta. Puedes mostrar mensajes diferentes según la calificación obtenida.

Prioridad: Media

Conocimientos relacionados:
- FAQ #57: Revisar calificación.
- FAQ #56: Realizar cuestionario.

#### FAQ #62
Pregunta principal: ¿Cómo califico tareas grupales?

Patrones de matching:
- Calificar entrega grupal
- Group assignment grading
- Nota compartida grupo

Keywords: [grupo, calificación, tarea]

Sinónimos relevantes:
- grupo → [equipo]
- calificación → [nota]

Respuesta:
Si la tarea está configurada como grupal, califica una entrega y Moodle propagará la nota a todos los integrantes del grupo. Puedes sobrescribir la calificación para un miembro específico si es necesario.

Prioridad: Media

Conocimientos relacionados:
- FAQ #63: Crear y gestionar grupos.
- FAQ #49: Calificar con rúbricas.

#### FAQ #63
Pregunta principal: ¿Cómo creo grupos y agrupamientos en mi curso?

Patrones de matching:
- Crear grupos Moodle
- Grouping setup
- Organizar equipos curso

Keywords: [grupos, agrupamientos, organización]

Sinónimos relevantes:
- grupos → [equipos]
- organización → [estructura]

Respuesta:
En **Participantes > Grupos** crea los grupos necesarios y, si requieres actividades diferenciadas, crea agrupamientos. Asigna manualmente o genera grupos automáticos según número de usuarios.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #62: Calificar tareas grupales.
- FAQ #53: Trabajar en wikis grupales.

#### FAQ #64
Pregunta principal: ¿Cómo configuro la finalización de curso basada en actividades?

Patrones de matching:
- Configurar finalización curso
- Course completion settings
- Requisitos de finalización

Keywords: [finalización, curso, requisitos]

Sinónimos relevantes:
- finalización → [culminación]
- requisitos → [condiciones]

Respuesta:
En **Administrador del curso > Finalización del curso** selecciona actividades obligatorias, calificaciones mínimas y finalización manual si procede. Moodle mostrará el estado en el bloque Progreso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #44: Finalización de actividades.
- FAQ #85: Seguimiento general del progreso.

#### FAQ #65
Pregunta principal: ¿Cómo programo recordatorios automáticos para tareas?

Patrones de matching:
- Recordatorios de tarea
- Assignment reminders Moodle
- Enviar avisos automáticos

Keywords: [recordatorio, tarea, automatización]

Sinónimos relevantes:
- recordatorio → [aviso]
- automatización → [programación]

Respuesta:
Activa la opción **Recordatorio** dentro de la tarea (si el plugin de recordatorios está habilitado) o crea un evento en el calendario con notificaciones habilitadas para enviar alertas por correo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #33: Crear eventos en el calendario.
- FAQ #70: Exportar recordatorios a agenda personal.

#### FAQ #66
Pregunta principal: ¿Cómo gestiono excepciones de entrega para estudiantes específicos?

Patrones de matching:
- Conceder prórroga tarea Moodle
- Assignment override
- Excepción de entrega

Keywords: [prórroga, excepción, entrega]

Sinónimos relevantes:
- prórroga → [extensión]
- excepción → [caso especial]

Respuesta:
En la tarea abre **Excepciones de usuario** o **Excepciones de grupo**, selecciona al estudiante, ajusta la fecha límite y guarda. Moodle mostrará la nueva fecha solo a los destinatarios.

Prioridad: Media

Conocimientos relacionados:
- FAQ #47: Archivos grandes.
- FAQ #41: Entrega estándar.

#### FAQ #67
Pregunta principal: ¿Por qué aparece un error al subir mi archivo?

Patrones de matching:
- Error subir archivo tarea
- Assignment upload error
- Problemas al adjuntar archivos

Keywords: [error, subir, archivo]

Sinónimos relevantes:
- error → [fallo]
- subir → [cargar]

Respuesta:
Verifica el tamaño máximo permitido, el formato aceptado y tu conexión. Borra la caché del navegador o intenta desde otro navegador. Si persiste, envía captura del error al soporte y adjunta el archivo mediante repositorio alterno.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #47: Límite de tamaño.
- FAQ #72: Compatibilidad de navegadores.

### Categoría 4: Calificaciones y evaluación (FAQ #68 – #85)

#### FAQ #68
Pregunta principal: ¿Cómo veo mis calificaciones en un curso?

Patrones de matching:
- Ver notas del curso
- Consultar calificaciones Moodle
- Gradebook student view

Keywords: [calificaciones, notas, libro]

Sinónimos relevantes:
- calificaciones → [notas]
- libro → [gradebook]

Respuesta:
Dentro del curso abre el menú **Calificaciones**. Allí encontrarás el resumen de actividades evaluadas, comentarios del docente y la ponderación usada para calcular la nota final.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #69: Interpretar el libro de calificaciones.
- FAQ #74: Descargar reporte de calificaciones.

#### FAQ #69
Pregunta principal: ¿Cómo interpreto el libro de calificaciones?

Patrones de matching:
- Interpretar gradebook
- Weighted grades Moodle
- Entender columnas calificaciones

Keywords: [libro, ponderación, interpretación]

Sinónimos relevantes:
- ponderación → [peso]
- interpretación → [lectura]

Respuesta:
Cada columna representa una actividad; las categorías muestran promedios parciales. Verifica si se usan ponderaciones o calificaciones ocultas y consulta la leyenda para entender los íconos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #68: Ver calificaciones.
- FAQ #75: Comprender escalas de evaluación.

#### FAQ #70
Pregunta principal: ¿Cómo exporto mis calificaciones?

Patrones de matching:
- Exportar calificaciones Moodle
- Download grades report
- Reporte de notas estudiante

Keywords: [exportar, calificaciones, reporte]

Sinónimos relevantes:
- exportar → [descargar]
- reporte → [informe]

Respuesta:
En el libro de calificaciones pulsa **Exportar** y elige formato (Excel, CSV). El archivo incluirá las calificaciones disponibles y los comentarios registrados.

Prioridad: Media

Conocimientos relacionados:
- FAQ #68: Ver calificaciones.
- FAQ #87: Exportar estadísticas de conversaciones.

#### FAQ #71
Pregunta principal: ¿Cómo verifico la ponderación de una categoría?

Patrones de matching:
- Peso categoría calificaciones
- Grade category weight
- Configuración de ponderaciones

Keywords: [categoría, peso, calificación]

Sinónimos relevantes:
- peso → [ponderación]
- categoría → [grupo]

Respuesta:
Como docente, en **Calificaciones > Configuración** revisa cada categoría y ajusta el método de agregación (media, suma, ponderado). Asegúrate de que los pesos sumen 100% cuando uses ponderación manual.

Prioridad: Media

Conocimientos relacionados:
- FAQ #69: Interpretar el libro.
- FAQ #82: Configurar calificaciones mínimas.

#### FAQ #72
Pregunta principal: ¿Cómo reviso la retroalimentación del docente?

Patrones de matching:
- Ver comentarios profesor
- Feedback gradebook
- Comentarios calificación

Keywords: [retroalimentación, comentarios, docente]

Sinónimos relevantes:
- retroalimentación → [feedback]
- docente → [profesor]

Respuesta:
En el libro de calificaciones haz clic en la actividad para desplegar comentarios y archivos adjuntos. Muchos docentes añaden rúbricas o notas personalizadas en la misma página.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #50: Retroalimentación de tareas.
- FAQ #68: Ver calificaciones.

#### FAQ #73
Pregunta principal: ¿Cómo reviso las calificaciones por curso desde mi área personal?

Patrones de matching:
- Resumen de calificaciones área personal
- Overview report Moodle
- Ver notas de todos los cursos

Keywords: [resumen, calificaciones, cursos]

Sinónimos relevantes:
- resumen → [overview]
- cursos → [asignaturas]

Respuesta:
En **Área personal > Resumen de calificaciones** verás un listado con la nota actual de cada curso en el que estás inscrito y acceso directo al libro de calificaciones individual.

Prioridad: Media

Conocimientos relacionados:
- FAQ #68: Calificaciones dentro del curso.
- FAQ #90: Bandeja de mensajes.

#### FAQ #74
Pregunta principal: ¿Cómo descargo mis calificaciones en PDF?

Patrones de matching:
- Exportar calificaciones PDF
- Descargar informe de notas
- Print grade report

Keywords: [PDF, calificaciones, informe]

Sinónimos relevantes:
- informe → [reporte]
- descargar → [exportar]

Respuesta:
Desde el libro de calificaciones selecciona **Exportar > PDF** si la institución habilitó ese formato. Alternativamente, exporta a Excel y conviértelo a PDF con tu suite ofimática.

Prioridad: Media

Conocimientos relacionados:
- FAQ #70: Exportar calificaciones.
- FAQ #87: Exportar estadísticas.

#### FAQ #75
Pregunta principal: ¿Qué son las escalas de calificación?

Patrones de matching:
- Escalas de calificación Moodle
- Grade scales explanation
- Notas cualitativas

Keywords: [escalas, calificación, cualitativa]

Sinónimos relevantes:
- escalas → [rangos]
- cualitativa → [descriptiva]

Respuesta:
Las escalas permiten evaluar con términos como "Aprobado/No aprobado" o "Excelente/Bueno" en lugar de números. Puedes revisar la escala usada en cada actividad desde su configuración.

Prioridad: Media

Conocimientos relacionados:
- FAQ #69: Interpretar libro de calificaciones.
- FAQ #82: Calificación mínima requerida.

#### FAQ #76
Pregunta principal: ¿Cómo ingreso comentarios privados en el libro de calificaciones?

Patrones de matching:
- Comentarios privados gradebook
- Private notes Moodle
- Feedback confidencial

Keywords: [comentarios, privados, calificación]

Sinónimos relevantes:
- privados → [confidenciales]
- comentarios → [notas]

Respuesta:
Como docente, en el reporte del calificador activa la columna **Notas privadas**. Los comentarios ingresados allí solo son visibles para el equipo docente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #72: Retroalimentación al estudiante.
- FAQ #81: Historial de cambios.

#### FAQ #77
Pregunta principal: ¿Cómo consulto el historial de cambios en una calificación?

Patrones de matching:
- Historial de calificación Moodle
- Grade history report
- Cambios en notas

Keywords: [historial, calificación, cambios]

Sinónimos relevantes:
- historial → [registro]
- cambios → [modificaciones]

Respuesta:
En **Calificaciones > Más > Historial de calificaciones** filtra por curso, usuario o actividad para ver quién modificó una nota, cuándo y cuál era el valor anterior.

Prioridad: Media

Conocimientos relacionados:
- FAQ #76: Comentarios privados.
- FAQ #81: Auditoría de calificaciones.

#### FAQ #78
Pregunta principal: ¿Cómo veo estadísticas de calificación por actividad?

Patrones de matching:
- Estadísticas calificación Moodle
- Activity grade analysis
- Informe de estadísticas

Keywords: [estadísticas, calificación, actividad]

Sinónimos relevantes:
- estadísticas → [análisis]
- actividad → [evaluación]

Respuesta:
En el libro de calificaciones elige **Más > Informe de estadísticas** y selecciona la actividad. Obtendrás media, mediana, desviación estándar y distribución de notas.

Prioridad: Media

Conocimientos relacionados:
- FAQ #77: Historial de cambios.
- FAQ #80: Escalas personalizadas.

#### FAQ #79
Pregunta principal: ¿Cómo calculo el promedio ponderado de mis calificaciones?

Patrones de matching:
- Promedio ponderado Moodle
- Weighted average grades
- Calcular media ponderada

Keywords: [promedio, ponderado, cálculo]

Sinónimos relevantes:
- promedio → [media]
- ponderado → [ponderación]

Respuesta:
Revisa las ponderaciones de cada categoría y multiplica la nota obtenida por su peso. Suma todos los resultados para conocer tu promedio general. El libro de calificaciones realiza este cálculo automáticamente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #69: Interpretar libro.
- FAQ #71: Verificar ponderaciones.

#### FAQ #80
Pregunta principal: ¿Cómo creo escalas de calificación personalizadas?

Patrones de matching:
- Crear escala personalizada Moodle
- Custom grade scale
- Definir rangos de evaluación

Keywords: [escala, personalizada, evaluación]

Sinónimos relevantes:
- personalizada → [propia]
- evaluación → [calificación]

Respuesta:
Desde **Calificaciones > Escalas > Añadir nueva escala** introduce los valores separados por comas en orden descendente. Asigna la escala a actividades que requieran evaluación cualitativa.

Prioridad: Media

Conocimientos relacionados:
- FAQ #75: Escalas existentes.
- FAQ #82: Calificación mínima.

#### FAQ #81
Pregunta principal: ¿Cómo audito cambios en el libro de calificaciones?

Patrones de matching:
- Auditoría calificaciones Moodle
- Gradebook audit
- Seguimiento de cambios notas

Keywords: [auditoría, cambios, calificaciones]

Sinónimos relevantes:
- auditoría → [revisión]
- cambios → [modificaciones]

Respuesta:
Usa el **Informe de historial de calificaciones** con filtros por usuario y fechas. Exporta el resultado para mantener un registro formal de los ajustes realizados.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #77: Historial de cambios.
- FAQ #76: Comentarios privados.

#### FAQ #82
Pregunta principal: ¿Cómo establezco una calificación mínima para aprobar?

Patrones de matching:
- Calificación mínima aprobatoria
- Passing grade Moodle
- Nota mínima curso

Keywords: [calificación mínima, aprobación, requisito]

Sinónimos relevantes:
- mínima → [umbral]
- aprobación → [aprobación]

Respuesta:
En la configuración del curso define la calificación mínima en la sección **Finalización del curso** o en cada actividad usando la opción "Calificación para aprobar". Moodle resaltará a quienes no alcancen el umbral.

Prioridad: Media

Conocimientos relacionados:
- FAQ #69: Interpretar libro de calificaciones.
- FAQ #64: Finalización de curso.

#### FAQ #83
Pregunta principal: ¿Cómo programo respaldos automáticos de calificaciones?

Patrones de matching:
- Backup calificaciones Moodle
- Automated grade backups
- Respaldo del libro de calificaciones

Keywords: [respaldo, calificaciones, automático]

Sinónimos relevantes:
- respaldo → [backup]
- automático → [programado]

Respuesta:
Configura los backups automáticos del curso en **Administrador del curso > Copias de seguridad** y asegúrate de incluir calificaciones y preferencias. Verifica periódicamente los archivos generados.

Prioridad: Media

Conocimientos relacionados:
- FAQ #38: Restaurar curso.
- FAQ #39: Importar contenido.

#### FAQ #84
Pregunta principal: ¿Cómo notifico a los estudiantes cuando se publica una nota?

Patrones de matching:
- Notificar calificaciones Moodle
- Grade notification
- Avisar nota publicada

Keywords: [notificar, calificación, mensaje]

Sinónimos relevantes:
- notificar → [avisar]
- mensaje → [comunicación]

Respuesta:
Activa las notificaciones en la actividad (por ejemplo, tareas permiten enviar aviso al calificar) o publica un anuncio en el foro de avisos. También puedes usar Mensajería para enviar un mensaje masivo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #54: Mensajes privados.
- FAQ #29: Foro de avisos.

#### FAQ #85
Pregunta principal: ¿Cómo hago seguimiento del progreso general del curso?

Patrones de matching:
- Course progress report Moodle
- Seguimiento progreso estudiantes
- Completion progress tracking

Keywords: [progreso, seguimiento, curso]

Sinónimos relevantes:
- progreso → [avance]
- seguimiento → [monitorización]

Respuesta:
Habilita el bloque **Progreso de finalización** y revisa el informe de finalización para ver qué estudiantes cumplen los requisitos. Puedes exportar la información para análisis adicional.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #44: Finalización de actividades.
- FAQ #64: Configurar finalización de curso.

### Categoría 5: Comunicación (FAQ #86 – #97)

#### FAQ #86
Pregunta principal: ¿Cómo envío un mensaje masivo a mis estudiantes?

Patrones de matching:
- Enviar mensaje masivo Moodle
- Bulk message students
- Mensaje a toda la clase

Keywords: [mensaje masivo, estudiantes, comunicación]

Sinónimos relevantes:
- masivo → [colectivo]
- comunicación → [aviso]

Respuesta:
En **Participantes** selecciona a los estudiantes con la casilla de verificación y usa el menú **Con usuarios seleccionados > Enviar un mensaje**. Redacta el aviso y envíalo a todos los seleccionados.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #54: Mensajes privados.
- FAQ #29: Foro de avisos.

#### FAQ #87
Pregunta principal: ¿Cómo exporto el historial de mensajes del chatbot?

Patrones de matching:
- Exportar conversaciones bot
- Chatbot logs export
- Descargar historial Educam Bot

Keywords: [exportar, conversación, chatbot]

Sinónimos relevantes:
- exportar → [descargar]
- historial → [registro]

Respuesta:
Actualmente se requiere acceder a la base de datos o implementar un reporte dedicado. En el plan propuesto se añadirá un módulo de exportación desde el panel administrativo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #112: Reportes de logs.
- FAQ #140: Panel de administración.

#### FAQ #88
Pregunta principal: ¿Cómo activo notificaciones push en la app móvil?

Patrones de matching:
- Notificaciones app Moodle
- Push notifications mobile
- Activar avisos móviles

Keywords: [notificaciones, móvil, push]

Sinónimos relevantes:
- notificaciones → [avisos]
- móvil → [app]

Respuesta:
En la app, abre **Ajustes > Notificaciones**, habilita los tipos de aviso deseados y concede permisos al sistema operativo. Debes haber iniciado sesión con tu cuenta institucional.

Prioridad: Media

Conocimientos relacionados:
- FAQ #5: Configurar notificaciones por correo.
- FAQ #11: Solucionar problemas de correo.

#### FAQ #89
Pregunta principal: ¿Cómo creo un foro exclusivo para anuncios del docente?

Patrones de matching:
- Foro solo anuncios
- Teacher announcement forum
- Foro unidireccional

Keywords: [foro, anuncios, docente]

Sinónimos relevantes:
- anuncios → [avisos]
- docente → [profesor]

Respuesta:
Crea un foro tipo **Anuncios** y asegúrate de que solo los docentes tengan permiso de publicar. Los estudiantes permanecerán suscritos automáticamente y recibirán cada anuncio por correo.

Prioridad: Media

Conocimientos relacionados:
- FAQ #29: Foro de avisos predeterminado.
- FAQ #54: Mensajes privados.

#### FAQ #90
Pregunta principal: ¿Cómo organizo mi bandeja de mensajes?

Patrones de matching:
- Gestionar mensajes Moodle
- Inbox organisation
- Filtrar conversaciones

Keywords: [mensajes, bandeja, filtro]

Sinónimos relevantes:
- bandeja → [inbox]
- filtro → [clasificación]

Respuesta:
En la interfaz de mensajería utiliza pestañas (Favoritos, Participantes, Contactos recientes) y archiva conversaciones antiguas. Puedes silenciar chats específicos desde el menú de opciones.

Prioridad: Media

Conocimientos relacionados:
- FAQ #54: Enviar mensajes privados.
- FAQ #88: Notificaciones push.

#### FAQ #91
Pregunta principal: ¿Cómo habilito el chat en vivo del curso?

Patrones de matching:
- Habilitar chat Moodle
- Live chat activity
- Chat sincrónico

Keywords: [chat, sincrónico, actividad]

Sinónimos relevantes:
- sincrónico → [en vivo]
- actividad → [herramienta]

Respuesta:
Activa edición, añade la actividad **Chat**, define horario y sesiones repetidas. Los estudiantes podrán ingresar en tiempo real y el historial quedará disponible para consulta.

Prioridad: Media

Conocimientos relacionados:
- FAQ #45: Foros de discusión.
- FAQ #96: Videoconferencias.

#### FAQ #92
Pregunta principal: ¿Cómo envío mensajes programados?

Patrones de matching:
- Mensajes programados Moodle
- Scheduled messages
- Enviar aviso en fecha futura

Keywords: [programado, mensaje, automatización]

Sinónimos relevantes:
- programado → [diferido]
- automatización → [planificación]

Respuesta:
Moodle no incluye mensajería programada por defecto. Puedes crear eventos en el calendario con recordatorios automáticos o usar herramientas externas de automatización integradas vía API.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #65: Recordatorios de tareas.
- FAQ #94: Comunicaciones por correo masivo.

#### FAQ #93
Pregunta principal: ¿Cómo modero un foro para aprobar mensajes antes de publicarlos?

Patrones de matching:
- Moderar foro Moodle
- Forum post approval
- Revisión de mensajes foro

Keywords: [moderar, foro, aprobación]

Sinónimos relevantes:
- moderar → [revisar]
- aprobación → [validación]

Respuesta:
Configura el foro con calificación aprobatoria o restricción de grupos y usa la opción **Requerir aprobación** si tu versión lo permite. De lo contrario, activa la suscripción forzada y elimina mensajes inapropiados manualmente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #89: Foro de anuncios.
- FAQ #55: Foros calificados.

#### FAQ #94
Pregunta principal: ¿Cómo envío un correo masivo a través de Moodle?

Patrones de matching:
- Enviar correo masivo Moodle
- Email all students
- Mailing list curso

Keywords: [correo masivo, estudiantes, mailing]

Sinónimos relevantes:
- correo → [email]
- masivo → [colectivo]

Respuesta:
Utiliza la opción **Enviar mensaje** desde Participantes o el plugin de newsletter institucional si está disponible. Para auditoría, copia el mensaje en el foro de avisos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #86: Mensaje masivo interno.
- FAQ #29: Foro de avisos.

#### FAQ #95
Pregunta principal: ¿Cómo comparto archivos en una conversación?

Patrones de matching:
- Adjuntar archivos en mensajes
- File sharing messaging
- Compartir documento chat

Keywords: [adjuntar, archivo, mensaje]

Sinónimos relevantes:
- adjuntar → [compartir]
- archivo → [documento]

Respuesta:
En la ventana de mensajes usa el icono de clip para subir archivos pequeños (por defecto 1-2 MB). Para archivos mayores, comparte un enlace desde tu repositorio institucional.

Prioridad: Media

Conocimientos relacionados:
- FAQ #54: Mensajes privados.
- FAQ #28: Descargar recursos.

#### FAQ #96
Pregunta principal: ¿Cómo organizo videoconferencias desde Moodle?

Patrones de matching:
- Videoconferencia Moodle
- BigBlueButton session
- Live session setup

Keywords: [videoconferencia, sesión, virtual]

Sinónimos relevantes:
- videoconferencia → [reunión virtual]
- sesión → [meeting]

Respuesta:
Añade la actividad de videoconferencia disponible (BigBlueButton o plugin institucional), configura fecha, duración y permisos de grabación. Comparte el enlace en el curso y en mensajes.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #91: Chat en vivo.
- FAQ #94: Correo masivo.

#### FAQ #97
Pregunta principal: ¿Cómo creo grupos de mensajería?

Patrones de matching:
- Grupo de mensajes Moodle
- Messaging group chat
- Crear chat grupal

Keywords: [grupo, mensajes, chat]

Sinónimos relevantes:
- grupo → [equipo]
- chat → [conversación]

Respuesta:
Desde la mensajería selecciona **Nuevo mensaje > Iniciar conversación grupal**, añade participantes y asigna un nombre al chat. Todos los miembros recibirán notificaciones en sus bandejas.

Prioridad: Media

Conocimientos relacionados:
- FAQ #63: Crear grupos académicos.
- FAQ #88: Notificaciones push móviles.

### Categoría 6: Perfil y configuración personal (FAQ #98 – #108)

#### FAQ #98
Pregunta principal: ¿Cómo actualizo mi información personal?

Patrones de matching:
- Editar perfil Moodle
- Update personal information
- Cambiar datos usuario

Keywords: [perfil, información, usuario]

Sinónimos relevantes:
- información → [datos]
- usuario → [cuenta]

Respuesta:
Accede a **Perfil > Editar perfil** y actualiza nombre, apellidos, ciudad y descripción. Guarda los cambios para que se reflejen en los listados del curso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #13: Cambiar foto de perfil.
- FAQ #64: Privacidad del perfil.

#### FAQ #99
Pregunta principal: ¿Cómo configuro mis preferencias de idioma?

Patrones de matching:
- Idioma preferido usuario
- Language preferences Moodle
- Cambiar idioma personal

Keywords: [idioma, preferencias, perfil]

Sinónimos relevantes:
- idioma → [lenguaje]
- preferencias → [ajustes]

Respuesta:
Ve a **Perfil > Preferencias > Idioma preferido** y selecciona la opción deseada. Esto afecta la interfaz general, salvo que un curso fuerce un idioma específico.

Prioridad: Media

Conocimientos relacionados:
- FAQ #17: Cambiar idioma global.
- FAQ #18: Idioma por curso.

#### FAQ #100
Pregunta principal: ¿Cómo personalizo mis notificaciones?

Patrones de matching:
- Preferencias de notificación personales
- Notification settings user
- Ajustar avisos

Keywords: [notificaciones, preferencias, avisos]

Sinónimos relevantes:
- notificaciones → [avisos]
- preferencias → [configuración]

Respuesta:
En **Perfil > Preferencias > Preferencias de notificación** activa o desactiva cada canal (correo, app, web) para los distintos eventos (mensajes, foros, tareas, calificaciones).

Prioridad: Alta

Conocimientos relacionados:
- FAQ #5: Configurar notificaciones.
- FAQ #88: Notificaciones push en la app.

#### FAQ #101
Pregunta principal: ¿Cómo ajusto mi zona horaria?

Patrones de matching:
- Cambiar zona horaria Moodle
- Timezone settings user
- Ajuste horario perfil

Keywords: [zona horaria, horario, perfil]

Sinónimos relevantes:
- zona horaria → [timezone]
- ajuste → [configuración]

Respuesta:
En **Perfil > Preferencias > Zona horaria** selecciona tu zona para que las fechas y horas de eventos se muestren correctamente según tu ubicación.

Prioridad: Media

Conocimientos relacionados:
- FAQ #30: Calendario del curso.
- FAQ #65: Recordatorios.

#### FAQ #102
Pregunta principal: ¿Cómo configuro mi disponibilidad para reuniones?

Patrones de matching:
- Disponibilidad Moodle
- Office hours setup
- Horario de atención docente

Keywords: [disponibilidad, horario, reunión]

Sinónimos relevantes:
- disponibilidad → [agenda]
- horario → [slot]

Respuesta:
Docentes pueden usar la actividad **Cita** (scheduler) para ofrecer horarios. Configura intervalos y permite que estudiantes reserven un espacio desde la misma herramienta.

Prioridad: Media

Conocimientos relacionados:
- FAQ #96: Videoconferencias.
- FAQ #104: Preferencias de privacidad.

#### FAQ #103
Pregunta principal: ¿Cómo restablezco las preferencias a valores predeterminados?

Patrones de matching:
- Restaurar preferencias Moodle
- Reset user preferences
- Volver a configuración inicial

Keywords: [restaurar, preferencias, configuración]

Sinónimos relevantes:
- restaurar → [resetear]
- configuración → [ajustes]

Respuesta:
No existe un botón único; debes ajustar cada sección manualmente. Si necesitas soporte, el administrador puede restablecer valores mediante herramientas de administración de usuarios.

Prioridad: Baja

Conocimientos relacionados:
- FAQ #100: Personalizar notificaciones.
- FAQ #105: Configuración de privacidad.

#### FAQ #104
Pregunta principal: ¿Cómo ajusto quién puede ver mi perfil?

Patrones de matching:
- Configurar privacidad perfil
- Who can see my profile
- Ajustes de visibilidad

Keywords: [privacidad, perfil, visibilidad]

Sinónimos relevantes:
- privacidad → [protección]
- visibilidad → [exposición]

Respuesta:
En **Perfil > Preferencias > Política y privacidad** define si tu perfil es visible para todos los participantes o solo para contactos. Revisa también la información compartida en los campos opcionales.

Prioridad: Media

Conocimientos relacionados:
- FAQ #13: Foto de perfil.
- FAQ #98: Editar información personal.

#### FAQ #105
Pregunta principal: ¿Cómo gestiono mis consentimientos de privacidad?

Patrones de matching:
- Consentimientos Moodle
- Privacy agreements
- GDPR consent

Keywords: [privacidad, consentimiento, GDPR]

Sinónimos relevantes:
- consentimiento → [aceptación]
- privacidad → [protección]

Respuesta:
Accede a **Perfil > Preferencias > Privacidad y políticas** para revisar consentimientos otorgados. Puedes retirar o aceptar nuevos términos según la normativa vigente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #104: Visibilidad del perfil.
- FAQ #112: Reportes de privacidad.

#### FAQ #106
Pregunta principal: ¿Cómo cambio mi contraseña de seguridad secundaria?

Patrones de matching:
- Cambiar PIN Moodle
- Security questions Moodle
- Second factor password

Keywords: [contraseña secundaria, seguridad, PIN]

Sinónimos relevantes:
- secundaria → [adicional]
- seguridad → [protección]

Respuesta:
Si tu institución usa autenticación multifactor, ve a **Perfil > Preferencias > Seguridad** y actualiza el PIN o preguntas de seguridad. Guarda los cambios y verifica el método alternativo configurado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #15: Autenticación de dos pasos.
- FAQ #3: Cambiar contraseña principal.

#### FAQ #107
Pregunta principal: ¿Cómo gestiono mis tokens de acceso a servicios externos?

Patrones de matching:
- Gestionar tokens Moodle
- External service tokens
- API access Moodle

Keywords: [token, servicios externos, API]

Sinónimos relevantes:
- token → [clave]
- servicios → [integraciones]

Respuesta:
En **Perfil > Preferencias > Claves de servicio** revisa los tokens activos, revoca los que no utilices y solicita nuevos al administrador si necesitas integrar aplicaciones externas.

Prioridad: Media

Conocimientos relacionados:
- FAQ #140: Panel administrativo.
- FAQ #88: Notificaciones móviles.

#### FAQ #108
Pregunta principal: ¿Cómo descargo mis datos personales almacenados?

Patrones de matching:
- Exportar datos personales Moodle
- Data request user
- GDPR data export

Keywords: [datos personales, exportar, privacidad]

Sinónimos relevantes:
- datos → [información]
- exportar → [descargar]

Respuesta:
Utiliza la herramienta **Solicitudes de datos** disponible en **Perfil > Preferencias > Privacidad** para generar un paquete con la información asociada a tu cuenta. Recibirás una notificación cuando el archivo esté listo.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #105: Consentimientos de privacidad.
- FAQ #112: Reportes de logs y privacidad.

### Categoría 7: Resolución de problemas técnicos (FAQ #109 – #126)

#### FAQ #109
Pregunta principal: ¿Qué hago si Moodle no carga?

Patrones de matching:
- Moodle no abre
- Platform not loading
- Error al cargar el sitio

Keywords: [no carga, error, plataforma]

Sinónimos relevantes:
- error → [fallo]
- plataforma → [sitio]

Respuesta:
Verifica tu conexión a Internet, limpia la caché del navegador y prueba en modo incógnito. Consulta el estatus institucional o contacta a soporte si el problema persiste en varios dispositivos.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #110: Errores 500.
- FAQ #117: Limpiar caché.

#### FAQ #110
Pregunta principal: ¿Cómo soluciono un error 500 en Moodle?

Patrones de matching:
- Error 500 Moodle
- Internal server error
- Problema servidor Moodle

Keywords: [error 500, servidor, fallo]

Sinónimos relevantes:
- fallo → [incidencia]
- servidor → [server]

Respuesta:
Un error 500 indica un problema del servidor. Notifica inmediatamente al equipo de TI proporcionando la URL y hora del fallo. Mientras tanto, evita refrescar repetidamente para no generar carga adicional.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #109: Plataforma no carga.
- FAQ #122: Contactar soporte.

#### FAQ #111
Pregunta principal: ¿Cómo reporto un bug?

Patrones de matching:
- Reportar bug Moodle
- Crear ticket soporte
- Informar incidencia

Keywords: [bug, incidencia, soporte]

Sinónimos relevantes:
- bug → [error]
- incidencia → [problema]

Respuesta:
Utiliza el sistema de tickets institucional describiendo pasos para reproducir, mensajes de error y captura de pantalla. Incluye navegador, sistema operativo y curso afectado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #122: Datos necesarios para soporte.
- FAQ #109: Plataforma no carga.

#### FAQ #112
Pregunta principal: ¿Cómo consulto los registros de actividad del chatbot?

Patrones de matching:
- Logs Educam Bot
- Chatbot activity report
- Revisar registro bot

Keywords: [logs, chatbot, actividad]

Sinónimos relevantes:
- logs → [registros]
- actividad → [historial]

Respuesta:
En la versión actual se requiere acceso directo a las tablas `local_educambot_log` o implementar un reporte personalizado. El plan de mejora incorpora un dashboard con filtros y exportación.

Prioridad: Media

Conocimientos relacionados:
- FAQ #87: Exportar historial del bot.
- FAQ #140: Panel administrativo.

#### FAQ #113
Pregunta principal: ¿Por qué Moodle se desconecta continuamente?

Patrones de matching:
- Sesiones se cierran solas
- Frequent logout Moodle
- Problemas de sesión

Keywords: [sesión, desconexión, tiempo]

Sinónimos relevantes:
- desconexión → [logout]
- tiempo → [timeout]

Respuesta:
Esto puede deberse a tiempo de inactividad o a múltiples sesiones abiertas. Guarda tu trabajo con frecuencia, evita usar varias pestañas y limpia cookies si el comportamiento continúa.

Prioridad: Media

Conocimientos relacionados:
- FAQ #14: Sesión caducada.
- FAQ #121: Ajustar seguridad del navegador.

#### FAQ #114
Pregunta principal: ¿Cómo soluciono problemas de reproducción de video?

Patrones de matching:
- Video no funciona
- Problema reproducción video Moodle
- Video playback troubleshooting

Keywords: [video, reproducción, problema]

Sinónimos relevantes:
- problema → [fallo]
- reproducción → [playback]

Respuesta:
Actualiza el navegador, habilita contenidos mixtos si el video es externo, borra caché y prueba con otro dispositivo. Si el video está en un repositorio institucional, contacta al administrador del recurso.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #27: Video no se reproduce.
- FAQ #117: Limpiar caché.

#### FAQ #115
Pregunta principal: ¿Cómo resuelvo errores de subida de archivo SCORM?

Patrones de matching:
- Error subir SCORM
- SCORM upload issue
- Problemas paquete SCORM

Keywords: [SCORM, carga, error]

Sinónimos relevantes:
- carga → [upload]
- error → [fallo]

Respuesta:
Verifica que el paquete esté comprimido en ZIP, sin caracteres especiales en el nombre y con estructura correcta (imsmanifest). Sube el archivo desde un navegador actualizado y sin bloqueadores.

Prioridad: Media

Conocimientos relacionados:
- FAQ #26: Recursos multimedia.
- FAQ #67: Error al subir archivo.

#### FAQ #116
Pregunta principal: ¿Cómo soluciono errores de conexión en la app móvil?

Patrones de matching:
- Error conexión app Moodle
- Mobile app cannot connect
- Problemas acceso móvil

Keywords: [app, conexión, móvil]

Sinónimos relevantes:
- conexión → [acceso]
- móvil → [app]

Respuesta:
Comprueba que la URL del campus esté bien escrita, elimina la cuenta de la app y vuelve a agregarla. Revisa si hay mantenimiento en el servidor o actualizaciones pendientes de la app oficial.

Prioridad: Media

Conocimientos relacionados:
- FAQ #88: Notificaciones push.
- FAQ #109: Plataforma no carga.

#### FAQ #117
Pregunta principal: ¿Cómo limpio la caché del navegador?

Patrones de matching:
- Limpiar cache navegador
- Clear browser cache
- Borrar datos temporales

Keywords: [caché, navegador, limpiar]

Sinónimos relevantes:
- caché → [datos temporales]
- limpiar → [borrar]

Respuesta:
Abre la configuración del navegador y borra el historial reciente seleccionando cookies y archivos en caché. Reinicia el navegador antes de volver a acceder a Moodle.

Prioridad: Media

Conocimientos relacionados:
- FAQ #109: Moodle no carga.
- FAQ #114: Problemas de video.

#### FAQ #118
Pregunta principal: ¿Qué hacer si recibo el error "Token inválido"?

Patrones de matching:
- Invalid token error
- Sesión token inválido
- Token mismatch Moodle

Keywords: [token, inválido, sesión]

Sinónimos relevantes:
- inválido → [inválido]
- sesión → [session]

Respuesta:
Este error suele aparecer cuando la sesión expira o se abren varias ventanas con formularios. Cierra las pestañas antiguas, vuelve a iniciar sesión y repite la acción desde una sola ventana.

Prioridad: Media

Conocimientos relacionados:
- FAQ #113: Desconexiones frecuentes.
- FAQ #14: Sesión caducada.

#### FAQ #119
Pregunta principal: ¿Cómo soluciono errores de visualización en navegadores antiguos?

Patrones de matching:
- Problemas navegador viejo
- Browser compatibility Moodle
- Error visualización navegador

Keywords: [compatibilidad, navegador, visualización]

Sinónimos relevantes:
- compatibilidad → [soporte]
- visualización → [renderizado]

Respuesta:
Moodle recomienda versiones recientes de Chrome, Firefox o Edge. Si usas Internet Explorer u otro navegador antiguo, actualiza a la última versión soportada o instala un navegador moderno.

Prioridad: Media

Conocimientos relacionados:
- FAQ #72: Videoconferencias.
- FAQ #117: Limpiar caché.

#### FAQ #120
Pregunta principal: ¿Cómo restablezco el caché de Moodle como administrador?

Patrones de matching:
- Purge caches Moodle
- Limpiar caches administración
- Purge all caches command

Keywords: [cache, purgar, administrador]

Sinónimos relevantes:
- purgar → [vaciar]
- administrador → [admin]

Respuesta:
Como administrador, ve a **Administración del sitio > Desarrollo > Vaciar todas las cachés**. Esta acción limpia cachés de plantillas, idioma y datos, resolviendo problemas de visualización tras actualizaciones.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #109: Plataforma no carga.
- FAQ #137: Estrategia de caché propuesta.

#### FAQ #121
Pregunta principal: ¿Cómo ajusto la configuración de seguridad del navegador?

Patrones de matching:
- Ajustar seguridad navegador Moodle
- Browser security settings
- Configuración cookies

Keywords: [seguridad, navegador, cookies]

Sinónimos relevantes:
- seguridad → [protección]
- configuración → [ajustes]

Respuesta:
Permite cookies de terceros si el campus usa servicios integrados, habilita JavaScript y desactiva bloqueadores de contenido para el dominio institucional. Añade la URL a la lista de sitios de confianza.

Prioridad: Media

Conocimientos relacionados:
- FAQ #113: Desconexiones frecuentes.
- FAQ #118: Error de token.

#### FAQ #122
Pregunta principal: ¿Qué información debo incluir al contactar soporte?

Patrones de matching:
- Datos para soporte Moodle
- Support ticket details
- Información incidencia

Keywords: [soporte, información, incidencia]

Sinónimos relevantes:
- información → [datos]
- incidencia → [problema]

Respuesta:
Incluye URL afectada, usuario, hora del incidente, descripción detallada, capturas de pantalla y pasos para reproducir. Indica si el problema afecta a otros usuarios o dispositivos.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #111: Reportar bug.
- FAQ #109: Moodle no carga.

#### FAQ #123
Pregunta principal: ¿Cómo verifico el estado de los servicios de Moodle?

Patrones de matching:
- Estado servicios Moodle
- Service status page
- Moodle status check

Keywords: [estado, servicio, monitorización]

Sinónimos relevantes:
- estado → [estatus]
- monitorización → [seguimiento]

Respuesta:
Consulta la página de estado institucional o el canal oficial de TI donde se publican mantenimientos programados y alertas. Si no existe, solicita a soporte confirmación sobre incidentes en curso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #109: Plataforma no carga.
- FAQ #110: Errores 500.

#### FAQ #124
Pregunta principal: ¿Cómo interpreto los mensajes de error de la base de datos?

Patrones de matching:
- Error base de datos Moodle
- Database error message
- dmlwriteexception moodle

Keywords: [base de datos, error, dml]

Sinónimos relevantes:
- error → [excepción]
- base de datos → [DB]

Respuesta:
Los mensajes `dmlwriteexception` indican problemas al escribir en la base de datos. Registra el texto completo del error y notifícalo al equipo de TI para revisión de logs y posibles bloqueos de tabla.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #110: Error 500.
- FAQ #122: Datos para soporte.

#### FAQ #125
Pregunta principal: ¿Cómo soluciono errores de certificado SSL?

Patrones de matching:
- Error certificado Moodle
- SSL certificate issue
- Sitio no seguro Moodle

Keywords: [certificado, SSL, seguridad]

Sinónimos relevantes:
- certificado → [cert]
- seguridad → [protección]

Respuesta:
Verifica la fecha de tu dispositivo, borra la caché SSL del navegador y confirma que accedes mediante `https`. Si el certificado expiró, contacta a TI para su renovación inmediata.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #121: Ajustes de seguridad del navegador.
- FAQ #109: Plataforma no carga.

#### FAQ #126
Pregunta principal: ¿Cómo soluciono errores al descargar archivos?

Patrones de matching:
- No puedo descargar archivo Moodle
- File download error
- Problemas descarga recurso

Keywords: [descargar, archivo, error]

Sinónimos relevantes:
- descargar → [bajar]
- error → [fallo]

Respuesta:
Desactiva bloqueadores de pop-ups, revisa el límite de descargas simultáneas y prueba otro navegador. Si el archivo proviene de repositorios externos, verifica tus credenciales o permisos de acceso.

Prioridad: Media

Conocimientos relacionados:
- FAQ #28: Descargar recursos.
- FAQ #121: Configuración de seguridad.

### Categoría 8: Recursos y herramientas del sistema (FAQ #127 – #140)

#### FAQ #127
Pregunta principal: ¿Cómo uso el calendario personal?

Patrones de matching:
- Calendario personal Moodle
- Personal calendar usage
- Gestionar eventos personales

Keywords: [calendario personal, eventos, agenda]

Sinónimos relevantes:
- agenda → [calendario]
- eventos → [recordatorios]

Respuesta:
Desde **Calendario > Nuevo evento**, selecciona tipo "Evento personal", define fecha y recordatorios. Los eventos personales solo son visibles para ti y pueden sincronizarse vía iCal.

Prioridad: Media

Conocimientos relacionados:
- FAQ #33: Eventos de curso.
- FAQ #34: Suscripción iCal.

#### FAQ #128
Pregunta principal: ¿Cómo gestiono las insignias (badges)?

Patrones de matching:
- Gestionar insignias Moodle
- Badges management
- Otorgar insignias

Keywords: [insignias, badges, reconocimiento]

Sinónimos relevantes:
- insignias → [badges]
- reconocimiento → [logro]

Respuesta:
Como docente, ve a **Participantes > Insignias > Gestionar insignias**, crea una nueva, define criterios (finalización de actividad o curso) y actívala para que los estudiantes la reciban automáticamente.

Prioridad: Media

Conocimientos relacionados:
- FAQ #85: Seguimiento de progreso.
- FAQ #132: Competencias.

#### FAQ #129
Pregunta principal: ¿Cómo uso el repositorio personal de archivos?

Patrones de matching:
- Repositorio personal Moodle
- Private files usage
- Subir archivos personales

Keywords: [repositorio, archivos personales, private files]

Sinónimos relevantes:
- repositorio → [almacenamiento]
- archivos → [documentos]

Respuesta:
Desde **Mis archivos privados** sube documentos que quieras reutilizar en varias actividades. Organízalos en carpetas y adjúntalos a tareas o foros mediante el selector de archivos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #95: Compartir archivos en mensajes.
- FAQ #28: Descargar recursos.

#### FAQ #130
Pregunta principal: ¿Cómo utilizo el bloque de progreso (Completion Progress)?

Patrones de matching:
- Bloque progreso Moodle
- Completion progress block
- Seguimiento visual tareas

Keywords: [progreso, bloque, completado]

Sinónimos relevantes:
- progreso → [avance]
- bloque → [widget]

Respuesta:
Añade el bloque **Completion Progress** en tu curso para visualizar el estado de cada actividad. Configura colores y orden según las fechas de entrega para facilitar el seguimiento.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #85: Seguimiento general.
- FAQ #44: Finalización de actividades.

#### FAQ #131
Pregunta principal: ¿Cómo uso el banco de contenidos?

Patrones de matching:
- Banco de contenidos Moodle
- Content bank usage
- Gestionar H5P

Keywords: [banco de contenidos, H5P, recursos]

Sinónimos relevantes:
- recursos → [materiales]
- banco → [repositorio]

Respuesta:
Accede a **Banco de contenidos**, crea o importa archivos H5P y publícalos en actividades interactivas. Puedes compartir recursos con otros docentes asignando permisos de edición.

Prioridad: Media

Conocimientos relacionados:
- FAQ #48: Entrega con multimedia.
- FAQ #132: Competencias y contenidos.

#### FAQ #132
Pregunta principal: ¿Cómo gestiono competencias y planes de aprendizaje?

Patrones de matching:
- Competencias Moodle
- Learning plans management
- Competency framework

Keywords: [competencias, planes, aprendizaje]

Sinónimos relevantes:
- competencias → [skills]
- plan → [ruta]

Respuesta:
Desde **Administrador del sitio > Competencias** crea marcos, asigna competencias a cursos y genera planes para estudiantes. Puedes vincular actividades a criterios y revisar el progreso desde el reporte de competencias.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #128: Insignias.
- FAQ #130: Bloque de progreso.

#### FAQ #133
Pregunta principal: ¿Cómo utilizo Analytics y modelos predictivos?

Patrones de matching:
- Moodle analytics usage
- Learning analytics models
- Modelos predictivos curso

Keywords: [analytics, predicción, datos]

Sinónimos relevantes:
- analytics → [analítica]
- predicción → [pronóstico]

Respuesta:
Activa los modelos en **Administrador del sitio > Analítica** y programa análisis. Revisa los resultados en la bandeja de analítica para identificar estudiantes en riesgo y tomar acciones proactivas.

Prioridad: Media

Conocimientos relacionados:
- FAQ #85: Seguimiento de progreso.
- FAQ #134: Reportes de actividad.

#### FAQ #134
Pregunta principal: ¿Cómo genero reportes de actividad del curso?

Patrones de matching:
- Reportes actividad Moodle
- Activity report course
- Logs course report

Keywords: [reporte, actividad, curso]

Sinónimos relevantes:
- reporte → [informe]
- actividad → [interacción]

Respuesta:
En **Más > Informes** dentro del curso accede a Reporte de actividad, Informe de participación y Registro completo. Exporta la información en CSV para análisis adicional.

Prioridad: Media

Conocimientos relacionados:
- FAQ #133: Analytics.
- FAQ #112: Logs del chatbot.

#### FAQ #135
Pregunta principal: ¿Cómo utilizo el banco de preguntas?

Patrones de matching:
- Banco de preguntas Moodle
- Question bank usage
- Organizar preguntas cuestionario

Keywords: [banco de preguntas, cuestionario, categorías]

Sinónimos relevantes:
- banco → [repositorio]
- categorías → [carpetas]

Respuesta:
Desde **Banco de preguntas** crea categorías para organizar preguntas, edita en masa y exporta/importa en formatos GIFT o XML. Usa etiquetas para localizar preguntas similares al construir cuestionarios.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #56: Realizar cuestionarios.
- FAQ #61: Comentarios en preguntas.

#### FAQ #136
Pregunta principal: ¿Cómo utilizo la función de evaluación por resultados (Outcomes)?

Patrones de matching:
- Outcomes Moodle
- Evaluación por resultados
- Learning outcomes tracking

Keywords: [resultados, outcomes, evaluación]

Sinónimos relevantes:
- resultados → [outcomes]
- evaluación → [assessment]

Respuesta:
Habilita outcomes en **Calificaciones > Resultados > Añadir**, vincúlalos a actividades y registra la evaluación con escalas específicas. Los estudiantes verán su desempeño en cada resultado asociado.

Prioridad: Media

Conocimientos relacionados:
- FAQ #132: Competencias.
- FAQ #75: Escalas de calificación.

#### FAQ #137
Pregunta principal: ¿Cómo se gestionará la caché del chatbot?

Patrones de matching:
- Cache Educam Bot
- Chatbot cache strategy
- MUC configuration bot

Keywords: [caché, chatbot, MUC]

Sinónimos relevantes:
- caché → [cache]
- configuración → [ajuste]

Respuesta:
La versión actual usa definiciones en `db/caches.php` para reglas y conocimiento. En la propuesta se añadirá caché por rol/curso y claves para memoria conversacional, con purgas automáticas tras actualizaciones.

Prioridad: Media

Conocimientos relacionados:
- FAQ #120: Purgar cachés.
- FAQ #140: Panel administrativo.

#### FAQ #138
Pregunta principal: ¿Cómo utilizo el sistema de reportes personalizados (Custom Reports)?

Patrones de matching:
- Custom reports Moodle
- Report builder usage
- Crear reporte personalizado

Keywords: [reporte personalizado, report builder, datos]

Sinónimos relevantes:
- reporte → [informe]
- personalizado → [custom]

Respuesta:
Con Moodle 4, usa el constructor de reportes para crear consultas sobre usuarios, cursos y actividades. Filtra, ordena y comparte reportes con otros roles mediante permisos específicos.

Prioridad: Media

Conocimientos relacionados:
- FAQ #134: Reportes de actividad.
- FAQ #133: Analytics.

#### FAQ #139
Pregunta principal: ¿Cómo funciona la herramienta de importación/exportación de conocimiento propuesta?

Patrones de matching:
- Importar conocimiento bot
- Exportar base conocimiento
- Knowledge base import export

Keywords: [importar, exportar, conocimiento]

Sinónimos relevantes:
- conocimiento → [FAQ]
- exportar → [descargar]

Respuesta:
El plan incluye un asistente que permitirá cargar archivos CSV/JSON con validación previa, vista previa de registros y opción de exportar la base filtrada por categoría, estado o prioridad.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #87: Exportar historial del bot.
- FAQ #140: Panel administrativo.

#### FAQ #140
Pregunta principal: ¿Qué funcionalidades tendrá el nuevo panel administrativo?

Patrones de matching:
- Panel administración Educam Bot
- Admin dashboard bot
- Gestión avanzada chatbot

Keywords: [panel administrativo, dashboard, bot]

Sinónimos relevantes:
- panel → [dashboard]
- administrativo → [gestión]

Respuesta:
El panel incluirá CRUD completo de conocimientos, sinónimos y patrones; importación/exportación; tester de coincidencias; estadísticas; logs filtrables; y configuración de apariencia, comportamiento y caché.

Prioridad: Alta

Conocimientos relacionados:
- FAQ #139: Importar/exportar conocimiento.
- FAQ #112: Logs del chatbot.


## Estrategia de implementación de la base de conocimientos

1. **Formato inicial**: Definir plantillas JSON y CSV con campos `category`, `question`, `patterns`, `keywords`, `synonyms`, `answer`, `priority`, `related`. Cada archivo incluirá metadatos (`version`, `language`, `createdby`).
2. **Script de seed modular**: Extender `local\educambot\local\setup\seed` para leer archivos en `classes/local/setup/catalogue/*.json`, validar esquemas y poblar tablas `local_educambot_rule`, `local_educambot_knowledge`, `local_educambot_kn_topic`, `local_educambot_kn_context`, `local_educambot_relation`.
3. **Diccionario de sinónimos global**: Crear tabla `local_educambot_synonym` para mantener equivalencias reutilizables (p. ej. login↔acceso, tarea↔assignment) y enlazarlas a reglas y conocimiento mediante una tabla pivote.
4. **Indexación**: Añadir índices FULLTEXT sobre `pattern`, `synonyms`, `keywords`, `knowledge.title`, `knowledge.summary`, `knowledge.content`, además de índices parciales (`enabled`, `priority`, `categoryid`).
5. **Validación**: Incluir CLI (`php admin/cli/cmd.php`) que ejecute validaciones de estructura, duplicados, relaciones inexistentes y cobertura mínima antes de importar datos masivos.

### Script de instalación (pseudocódigo)

```
function seed_initial_knowledge(): void {
    $catalogues = load_catalogue_files('/classes/local/setup/catalogue');
    start_transaction();
    foreach ($catalogues as $category) {
        $categoryid = ensure_category_exists($category->name, $category->description);
        foreach ($category->faqs as $faq) {
            $ruleid = insert_rule($faq, $categoryid);
            $knowledgeid = insert_knowledge($faq, $categoryid);
            link_rule_to_knowledge($ruleid, $knowledgeid, $faq->priority);
            insert_patterns($ruleid, $faq->patterns, $faq->synonyms);
            insert_keywords($ruleid, $faq->keywords);
            relate_knowledge($knowledgeid, $faq->related);
        }
    }
    insert_synonyms($catalogues->synonyms);
    create_indexes();
    commit_transaction();
    reset_caches();
}
```

## Sistema de gestión del conocimiento propuesto

- **CRUD completo**: interfaces basadas en `moodleform` para crear/editar reglas, conocimiento estructurado, sinónimos, relaciones y patrones. Inclusión de paginación, filtros por categoría, prioridad y estado.
- **Importación/Exportación**: wizard en tres pasos (subir archivo, vista previa, confirmación) con validaciones y opción de rollback. Exportaciones filtradas por rango de fechas, categoría, estado y formato (CSV/JSON).
- **Buscador**: barra de búsqueda con sugerencias, filtros combinables (categoría, rol, curso, prioridad) y resaltado de coincidencias. Utilización del motor de ranking para ordenar resultados.
- **Gestión de sinónimos y patrones**: módulo dedicado para añadir sinónimos globales y probar patrones contra preguntas reales (tester de matching con puntuaciones por nivel).
- **Logs y debugging**: vista de conversaciones, filtros por curso, usuario, fecha y tipo de respuesta; panel de depuración que muestra el pipeline del motor (normalización, coincidencias por nivel, puntuaciones intermedias, decisión final).

## Optimización del uso de la base de datos

1. **Uso de campos**: Reutilizar `local_educambot_rule.priority` (nuevo campo propuesto) para ordenar sugerencias, aprovechar `knowledge.type` para distinguir guías, tutoriales o políticas, y utilizar `externalurl` para recursos complementarios.
2. **Consultas optimizadas**:
   - Reemplazar `SELECT *` por columnas necesarias.
   - Usar consultas parametrizadas con `get_records_sql` filtrando por `category`, `priority`, `enabled` y `courseid`.
   - Implementar búsqueda full-text (`MATCH ... AGAINST`) cuando la base de datos lo permita y fallback a similitud en PHP.
3. **Relaciones**: Añadir claves foráneas faltantes (`local_educambot_rule.categoryid`, `local_educambot_knowledge.categoryid`) y restricciones de unicidad para `local_educambot_relation`.
4. **Caché**: Configurar definiciones MUC adicionales (`rules_by_category`, `knowledge_by_course`, `synonym_map`) con TTL configurable y purgas cuando se edite contenido relacionado.
5. **Escalabilidad**: Implementar estrategias de particionamiento lógico (categorías) y CLI para mantenimiento (reindexar, purgar, validar integridad).

## Guía de implementación paso a paso

1. **Preparación del entorno**
   - Configurar entorno de desarrollo con Moodle 4.0+, PHP 8.x, base de datos local y herramientas (`moodle-plugin-ci`, `phpunit`).
   - Realizar copia del plugin actual y ejecutar `php admin/cli/upgrade.php --non-interactive` para validar estado.

2. **Corrección de errores críticos**
   - Actualizar `logger` para usar `PARAM_RAW_TRIMMED` y `clean_text` antes de persistir.
   - Añadir contexto a `format_text` en `engine` y sanitizar respuestas antes de enviarlas.
   - Ajustar `manage.php` para usar `PARAM_RAW_TRIMMED` y sanitizar con `clean_text` al guardar.
   - Incluir fallback de sugerencias en `engine::get_suggestions` cuando no existan reglas proactivas.

3. **Reestructuración de la base de datos**
   - Añadir columnas `categoryid`, `priority`, `createdby`, `updatedby` a reglas y conocimiento.
   - Crear tablas `local_educambot_category`, `local_educambot_synonym`, `local_educambot_pattern` y relación `local_educambot_rule_pattern`.
   - Definir índices FULLTEXT y claves foráneas.
   - Implementar funciones de upgrade (`upgrade.php`) con migración de datos existente.

4. **Implementación del motor de razonamiento avanzado**
   - Crear clases `nlp\normalizer`, `nlp\tokenizer`, `nlp\stemmer` para pipeline.
   - Implementar `matching\exact_matcher`, `matching\partial_matcher`, `matching\semantic_matcher` con pesos configurables.
   - Construir `inference\engine` que combine resultados y gestione reglas encadenadas.
   - Añadir `context\session_memory` para mantener estado conversacional y `context\intent_detector` para clasificar intenciones.

5. **Expansión de la base de conocimientos**
   - Cargar el catálogo de 140 FAQs mediante script de importación.
   - Validar integridad (sin duplicados, relaciones válidas, prioridades asignadas).
   - Ejecutar pruebas funcionales sobre queries de conocimiento y ranking de reglas.

6. **Mejora de interfaz de usuario**
   - Rediseñar widget con web components o ES modules, soporte de quick replies, indicador de escritura, contador de mensajes y personalización completa.
   - Añadir soporte ARIA, navegación con teclado, control de foco y modo alto contraste.
   - Implementar persistencia de conversaciones por usuario (opcional) usando sesión o almacenamiento local.

7. **Panel de administración**
   - Crear páginas dedicadas (`/local/educambot/knowledge.php`, `/local/educambot/logs.php`, `/local/educambot/analytics.php`).
   - Incorporar dashboards con gráficos (Chart.js o `core/chartjs`) para métricas clave.
   - Añadir tester de patrones que muestre puntuaciones por nivel.

8. **Integración avanzada con Moodle**
   - Implementar servicios para obtener cursos, actividades pendientes, calificaciones y eventos (`core_course`, `calendar`, `gradebook` APIs).
   - Añadir respuestas contextuales basadas en la página actual y rol del usuario.

9. **Testing completo**
   - Tests unitarios para `nlp`, `matching`, `inference`, `knowledge_repository`.
   - Tests Behat para widget, panel de administración e importación/exportación.
   - Auditorías de seguridad (CSRF, XSS, permisos) y rendimiento (profiling de consultas, carga de caché).

10. **Documentación y despliegue**
    - Actualizar README, manual de administrador y guía de contribución.
    - Proporcionar scripts de migración, ejemplos de API y manual de troubleshooting.
    - Preparar checklist de despliegue y plan de rollback.

## Código de referencia

### Ejemplo: Corrección de vulnerabilidad en consultas directas

**Actual (vulnerable)**
```php
$courseid = $_GET['courseid'];
$sql = "SELECT * FROM {course} WHERE id = $courseid";
$course = $DB->get_record_sql($sql);
```

**Corregido**
```php
$courseid = required_param('courseid', PARAM_INT);
require_capability('moodle/course:view', context_course::instance($courseid));
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
```

### Ejemplo: Uso de caché MUC

```php
// db/caches.php
$definitions['rules_by_category'] = [
    'mode' => cache_store::MODE_APPLICATION,
    'simplekeys' => true,
    'simpledata' => false,
    'ttl' => 600,
];

// classes/local/knowledge_repository.php
public function get_rules_for_category(int $categoryid): array {
    $cache = \cache::make('local_educambot', 'rules_by_category');
    $key = 'category:' . $categoryid;
    if (($data = $cache->get($key)) !== false) {
        return $data;
    }
    $records = $this->db->get_records('local_educambot_rule', ['categoryid' => $categoryid, 'enabled' => 1]);
    $cache->set($key, $records);
    return $records;
}
```

### Ejemplo: Motor de razonamiento modular

```php
class reasoning_engine {
    public function __construct(
        private nlp\pipeline $pipeline,
        private matching\manager $matcher,
        private inference\engine $inference,
        private context\session_memory $memory
    ) {}

    public function respond(string $question, context_bundle $context): response_dto {
        $normalized = $this->pipeline->process($question);
        $matches = $this->matcher->match($normalized, $context);
        $decision = $this->inference->decide($matches, $context, $this->memory->load($context));
        $this->memory->store($context, $decision->context_snapshot);
        return $decision->to_response();
    }
}
```


## Integración profunda con Moodle

- **Datos del usuario**: utilizar `core_user` y `enrol_get_users_courses()` para obtener cursos, roles y progreso. `context_provider` ya implementa gran parte de la lógica, pero se añadirá un servicio `context\user_profile_service` que exponga cursos actuales, progreso, próximos eventos y grupos.
- **Consultas contextuales**:
  ```php
  $courses = enrol_get_users_courses($userid, true, 'id, fullname, shortname, startdate, enddate');
  foreach ($courses as $course) {
      $grade = grade_get_course_grade($userid, $course->id);
      $events = calendar_get_upcoming_events(5, $userid, $course->id);
  }
  ```
- **Actividades y fechas**: aprovechar `core_completion\progress` y `calendar_event::create()` para entregar respuestas personalizadas sobre tareas pendientes, fechas límite y eventos.
- **Calificaciones**: `grade_get_grades($courseid, 'mod', 'assign', $assignid, $userid)` para consultar calificaciones por actividad y `grade_get_course_grade()` para promedios del curso.
- **Calendario**: usar `core_calendar\local\api::get_action_events_by_course()` para próximas actividades; integrar con la lógica de recordatorios del bot.
- **Contexto inteligente**: detectar página actual (`$PAGE->url`), curso enfocado y rol del usuario para priorizar respuestas relevantes.

## Consideraciones de UI/UX

- Widget flotante configurable (posición, tamaño, avatar, colores, estado expandido persistente).
- Accesibilidad WCAG 2.1 AA: navegación con teclado, roles ARIA (`dialog`, `log`), foco visible, contraste mínimo 4.5:1.
- Indicadores de actividad (typing indicator), contador de mensajes no leídos, sugerencias rápidas y botones de acción contextual.
- Historial persistente opcional con botón para limpiar conversación y mensaje de inicio personalizado.

