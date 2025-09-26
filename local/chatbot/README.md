# Moodle local_chatbot

`local_chatbot` añade a Moodle un asistente flotante inspirado en el widget de `local_geniai`. El asistente se inyecta mediante el _hook_ `before_footer_html_generation`, renderiza su interfaz con una plantilla Mustache y un módulo AMD, y almacena cada diálogo en tablas dedicadas que puedes gestionar desde el área de administración del plugin.

## Novedades principales

- Lanzador flotante con contador de mensajes no leídos, animaciones y apariencia alineada con `local_geniai` (`templates/widget.mustache`, `styles.css`).
- Superficie de conversación accesible con mensaje de bienvenida, sugerencias, accesos rápidos, indicador de escritura y contador de caracteres (Mustache + AMD).
- Clasificador por palabras clave totalmente configurable desde la administración (tabla `local_chatbot_intents`), con intención _fallback_ y respuesta personalizada.
- Sugerencias y accesos rápidos dinámicos administrados en las tablas `local_chatbot_suggestions` y `local_chatbot_quickacts`, incluidos soportes para acciones de navegación, mensajes prefijados y respuestas del servidor.
- El widget recupera sugerencias y accesos rápidos en cuanto se inicia, mostrando contexto útil desde la primera interacción.
- Servicios web AJAX para mensajes, historial, sugerencias, accesos rápidos, exportaciones y retroalimentación (`db/services.php`, `externallib.php`).
- Consolas administrativas completas: gestión de intenciones, entidades, entrenamiento de mensajes, visor de diálogos y pruebas manuales (`admin/*`).
- Exportación HTML/CSV/JSON de conversaciones (`export.php`) y consola CLI/GUI con capacidades granuladas (`db/access.php`).

## Requisitos y compatibilidad

| Requisito    | Versión mínima |
|--------------|----------------|
| Moodle core  | 4.3 (build 2023100900) |
| PHP          | 8.0 |
| Base de datos | Cualquiera soportada por Moodle (tablas definidas en `db/install.xml`) |

El widget se muestra únicamente a usuarios autenticados (no invitados) y en páginas que permitan _popups_.

## Instalación

1. Copia este directorio en `<moodle_root>/local/chatbot` o clona el repositorio dentro de `local/`.
2. Desde la interfaz de Moodle o la CLI ejecuta el _upgrade_ (`php admin/cli/upgrade.php`).
3. (Opcional) Purga cachés (`php admin/cli/purge_caches.php`).
4. Ejecuta el cron de Moodle (`php admin/cli/cron.php`) para reconstruir cachés.

El módulo AMD se carga directamente desde `amd/src/chatbot.js`; no es obligatorio generar _builds_ minimizadas (puedes hacerlo con `npx grunt amd`).

## Actualizaciones

- **1.1.0 (2025-02-01)**: incorpora tablas para intenciones, sugerencias y accesos rápidos gestionables desde la administración; reemplaza las pantallas provisionales por consolas completas; añade visor de diálogos, consola de entrenamiento y pruebas; actualiza servicios web y el widget para consumir la configuración dinámica.
- **1.0.1 (2025-02-15)**: adapta la inyección del widget al _hook_ oficial, añade contador de mensajes y asegura que el plugin quede habilitado tras la actualización.

Después de sustituir el directorio del plugin, repite los pasos de instalación. Moodle aplicará automáticamente los cambios de base de datos y configuración pendientes.

## Configuración

### Ajustes generales

Visita **Administración del sitio → Plugins → Plugins locales → Asistente de chatbot** (`settings.php`) para controlar:

- Activar o desactivar el chatbot.
- Nombre del asistente (se muestra en el encabezado y textos accesibles).
- Posición del lanzador (esquina inferior derecha o izquierda).
- Paleta de colores (moderno, minimalista u oscuro).
- Mensaje de bienvenida con soporte para el token `{name}`.
- Mensaje por defecto cuando no hay coincidencias.
- Longitud máxima del mensaje en la interfaz.
- Visibilidad del botón de exportar (requiere el permiso `local/chatbot:export`).

### Consolas específicas

El plugin añade una categoría propia en **Administración del sitio → Plugins → Plugins locales → Asistente de chatbot** con las siguientes páginas:

| Página | Descripción |
|--------|-------------|
| **Gestionar intenciones** | Crea, edita y elimina intenciones, palabras clave y la intención _fallback_. |
| **Gestionar entidades** | Administra accesos rápidos (navegación, inyección o respuesta del servidor) y sugerencias mostradas en el widget. |
| **Entrenamiento y aprendizaje** | Analiza mensajes, comprueba coincidencias y (opcionalmente) registra el resultado en el historial. |
| **Flujos de diálogo** | Lista, filtra y detalla sesiones con posibilidad de exportar la conversación seleccionada. |
| **Probar el chatbot** | Envía mensajes manualmente para verificar respuestas y revisar el historial de una sesión concreta. |

## Capacidades

| Capacidad | Propósito | Roles por defecto |
|-----------|-----------|-------------------|
| `local/chatbot:use` | Mostrar el widget y consumir servicios web | Usuarios autenticados |
| `local/chatbot:manage` | Acceder a las consolas administrativas | Gestor |
| `local/chatbot:export` | Exportar historial de conversaciones | Usuarios autenticados |

## Funcionamiento del widget

- El HTML se genera con `templates/widget.mustache`, replicando la estructura y etiquetado ARIA del widget de `local_geniai`.
- Los estilos (`styles.css`) se encargan del aspecto flotante, animaciones y adaptabilidad.
- `amd/src/chatbot.js` inicializa el widget, consume los servicios AJAX, restaura el historial, gestiona el contador del lanzador y conserva la apertura/cierre usando `localStorage` y `sessionStorage`.
- `lib.php` controla la inyección del widget, construye el contexto Mustache, obtiene la configuración dinámica (intenciones, sugerencias y accesos rápidos), valida permisos y expone la configuración al módulo AMD.

## Servicios web

Los servicios definidos en `db/services.php` y desarrollados en `externallib.php` permiten al widget:

- Procesar mensajes (`local_chatbot_process_message`).
- Recuperar historial (`local_chatbot_get_history`).
- Obtener sugerencias dinámicas (`local_chatbot_get_suggestions`).
- Recuperar accesos rápidos gestionables (`local_chatbot_get_quick_actions`).
- Exportar conversaciones (`local_chatbot_export_conversation`).
- Guardar retroalimentación (`local_chatbot_feedback`).
- Ejecutar accesos rápidos del lado servidor (`local_chatbot_execute_action`).

## Almacenamiento de datos

El plugin crea las siguientes tablas (`db/install.xml`):

- `local_chatbot_logs`: historial de mensajes, respuestas, intención, tiempos de respuesta, metadatos y feedback.
- `local_chatbot_intents`: intenciones configurables con palabras clave, respuesta, orden y bandera de _fallback_.
- `local_chatbot_suggestions`: sugerencias mostradas en el widget con icono, modo (mensaje/acción) y orden.
- `local_chatbot_quickacts`: accesos rápidos con clave, tipo (navegación/inyección/servidor), carga útil, icono y orden.

## Resolución de problemas

- **El widget no aparece:** verifica que el plugin esté habilitado, que el usuario tenga `local/chatbot:use`, que la página permita popups y que no sea una sesión de invitado.
- **El contador del lanzador no se reinicia:** abrir el widget limpia el contador y guarda el estado en `localStorage`. Borra el almacenamiento del navegador si persiste.
- **Errores AJAX:** revisa la consola del navegador; el módulo AMD muestra las excepciones. Comprueba permisos y que los servicios web estén habilitados.
- **Exportaciones vacías:** asegúrate de que existan registros en `local_chatbot_logs` y de que quien exporta tenga `local/chatbot:export`.

## Lista de verificación tras la instalación

- ✅ Abre el widget con un usuario estándar y confirma que se muestran mensaje de bienvenida, sugerencias y accesos rápidos.
- ✅ Envía un mensaje, revisa la respuesta y valida que el contador del lanzador se incrementa cuando el widget está cerrado.
- ✅ Comprueba que la intención correcta queda registrada en `local_chatbot_logs` y prueba las exportaciones en `/local/chatbot/export.php`.
- ✅ Recorre las páginas administrativas (intenciones, entidades, entrenamiento, diálogos y pruebas) para confirmar que cargan y funcionan con tus datos reales.

## Desarrollo y pruebas

- Ejecuta `php -l` sobre los archivos PHP modificados o ejecuta PHPUnit si quieres pruebas más profundas.
- Para distribuir el plugin con assets minimizados puedes ejecutar `npx grunt amd`.
- Purga cachés (`php admin/cli/purge_caches.php`) tras modificar plantillas Mustache, JS o CSS.
- Compara estilos con `local/geniai/styles.css` si necesitas alinear aún más el diseño.

## Licencia

GPL v3 o posterior. Consulta la licencia estándar de Moodle para más detalles.
