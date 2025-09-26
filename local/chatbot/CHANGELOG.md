# Changelog

## 1.1.1 - 2025-02-20
- Se elimina la consola de analíticas obsoleta y se limpian todas las referencias asociadas para evitar enlaces rotos.
- El widget carga sugerencias y accesos rápidos nada más abrirse para ofrecer contexto inmediato a la persona usuaria.
- Ajustes menores en la administración para mostrar etiquetas traducidas de los tipos de acceso rápido.

## 1.1.0 - 2025-02-01
- Reemplazadas todas las pantallas administrativas provisionales por consolas completas de intenciones, entidades, entrenamiento, visor de diálogos y pruebas manuales (`admin/*`).
- Añadidas las tablas `local_chatbot_intents`, `local_chatbot_suggestions` y `local_chatbot_quickacts` junto con formularios de gestión y semillas iniciales (`db/install.xml`, `db/upgrade.php`).
- El clasificador de mensajes ahora consume las intenciones configuradas y almacena la palabra clave coincidente en los metadatos (`lib.php`).
- Sugerencias y accesos rápidos pasan a ser totalmente configurables y se exponen mediante los servicios web y el módulo AMD actualizados (`lib.php`, `externallib.php`, `amd/src/chatbot.js`).
- Nuevo visor de conversaciones con filtros avanzados y exportación directa (`admin/dialogues.php`).
- Documentación y traducciones renovadas para reflejar la administración ampliada del plugin (`README.md`, `lang/*`).

## 1.0.1 - 2025-02-15
- Convert the widget injector to the Moodle `before_footer_html_generation` hook API and stop echoing output directly (`lib.php`, `db/hooks.php`).
- Return the hook definition array so Moodle registers the widget callback reliably (`db/hooks.php`).
- Default the plugin to enabled on fresh installs/upgrades and persist the setting through an upgrade step (`lib.php`, `db/upgrade.php`).
- Propagate message timestamps to web service consumers and surface them in the UI/history replay (`lib.php`, `externallib.php`, `amd/src/chatbot.js`).
- Add launcher badge handling, local/session storage fallbacks and hardened UI interactions (`amd/src/chatbot.js`).
- Guard against missing users in exports and refresh documentation to include installation, upgrade and validation guidance (`lib.php`, `README.md`).

## 1.0.0 - 2024-05-24
- Rebuilt the chatbot widget to match the `local_geniai` floating UI using a new Mustache template and AMD module.
- Added a real conversation log table (`local_chatbot_logs`) with export and feedback capabilities.
- Simplified configuration options with clear Moodle language strings and capability checks.
- Replaced placeholder web service handlers with fully working implementations for messaging, suggestions, quick actions and exports.
- Updated documentation (README) and provided compiled AMD build output.
