# Changelog

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
