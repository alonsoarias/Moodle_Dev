# Moodle local_chatbot

`local_chatbot` adds a floating assistant to Moodle pages that mirrors the positioning and look & feel of the `local_geniai` widget while providing a lightweight, keyword-based responder. The widget is injected through Moodle's `before_footer_html_generation` hook, uses a Mustache template for markup, an AMD module for interactivity and logs every dialogue in a dedicated database table.

## Feature highlights

- Floating launcher with badge counter and animated popup styled after `local_geniai` (`templates/widget.mustache`, `styles.css`).
- Accessible chat surface with welcome prompt, quick actions, suggestions and typing indicator (Mustache + AMD module).
- Keyword-driven responder with configurable welcome/fallback messaging and intent tagging (`lib.php`).
- AJAX web services for messaging, history replay, quick actions, exports and feedback (`db/services.php`, `externallib.php`).
- Conversation log table with HTML, CSV or JSON export (`install.xml`, `lib.php`, `export.php`).
- Granular capabilities for usage, management and exporting plus Site administration pages for future expansion (`db/access.php`, `settings.php`, `admin/*`).

## Requirements and compatibility

| Requirement | Minimum |
|-------------|---------|
| Moodle core | 4.3 (build 2023100900) |
| PHP         | 8.0 |
| Database    | Any Moodle supported DB (table defined in `db/install.xml`) |

The widget is injected only for authenticated, non-guest users on layouts that allow popup notifications.

## Installation

1. Copy this directory to `<moodle_root>/local/chatbot` or clone the repository inside `local/`.
2. From the Moodle web UI or CLI run the upgrade step (`php admin/cli/upgrade.php`).
3. (Optional) Purge caches after installation (`php admin/cli/purge_caches.php`).
4. Run Moodle cron so caches are rebuilt (`php admin/cli/cron.php`).

The AMD controller is loaded directly from `amd/src/chatbot.js`; no additional build step is required for production. If you prefer minified assets, run `npx grunt amd`.

## Upgrading

- Version 1.0.1 introduces the badge counter, timestamp propagation, stronger hook integration and ensures the widget defaults to enabled. During upgrade a default `enabled` config value is created when missing (`db/upgrade.php`).
- After replacing the plugin directory, repeat the installation steps above. Moodle will apply outstanding database or configuration upgrades automatically.

## Configuration

Navigate to **Site administration → Plugins → Local plugins → Chatbot assistant** (`settings.php`) to manage:

- Enable chatbot (defaults to on).
- Assistant name shown in the header and aria labels.
- Launcher position (bottom-right or bottom-left).
- Theme palette (modern, minimal, dark).
- Welcome message template supporting `{name}` token replacement.
- Fallback response when no keyword is matched.
- Maximum message length enforced in the UI.
- Toggle for exposing the export button (requires `local/chatbot:export`).

## Capabilities

| Capability | Purpose | Default roles |
|------------|---------|----------------|
| `local/chatbot:use` | Launch the widget and call web services | Authenticated users |
| `local/chatbot:manage` | Access placeholder admin consoles | Manager |
| `local/chatbot:export` | Export chat history | Authenticated users |

## Widget behaviour and assets

- HTML is rendered from `templates/widget.mustache`, replicating `local_geniai` launcher/header layout and ARIA labelling.
- Styling lives in `styles.css` with responsive rules that mirror the reference plugin.
- `amd/src/chatbot.js` boots the widget, talks to web services, shows history, updates the launcher badge when new bot replies arrive and honours local/session storage for persistence.
- Server bootstrap (`lib.php`) injects CSS/JS, builds the template context, enforces capabilities and passes localisation strings plus runtime config to the AMD module.

## Web services

All services are defined in `db/services.php` and implemented in `externallib.php`:

- `local_chatbot_process_message`: keyword response, intent tagging, quick actions & suggestions.
- `local_chatbot_get_history`: returns the latest messages with timestamps for replay.
- `local_chatbot_get_suggestions`: contextual prompt suggestions.
- `local_chatbot_get_quick_actions`: shortcut buttons (profile, calendar, etc.).
- `local_chatbot_export_conversation`: server-side HTML/CSV/JSON export used by the widget and standalone export page.
- `local_chatbot_feedback`: stores thumbs-up/down feedback on each log entry.
- `local_chatbot_execute_action`: executes quick action shortcuts (placeholder).

## Data storage

The plugin creates `local_chatbot_logs` (`db/install.xml`) capturing:

- `userid`, `sessionid`, original `message` and bot `response`.
- `intent`, response time, optional feedback label and metadata JSON.
- `timecreated` timestamps used for history playback and exports.

## Troubleshooting

- **Widget invisible:** confirm the plugin is enabled, the user has `local/chatbot:use`, the page layout allows popups and you are not logged in as guest.
- **Launcher badge never resets:** opening the widget clears the badge and stores the state in `localStorage`. Purge browser storage if behaviour persists.
- **AJAX errors:** check browser console; the AMD module surfaces Moodle notifications and server responses. Ensure the web service user has the required capability.
- **Exports blank:** verify `local/chatbot_logs` contains data and the requester has `local/chatbot:export`.

## Post-installation checklist

- ✅ Launch the widget as a standard user and confirm welcome text, suggestions and quick actions render.
- ✅ Send a message and verify the bot response, badge counter increment (when closed) and history replay after page reload.
- ✅ Review the `local_chatbot_logs` table entries and test HTML/CSV/JSON exports via the widget or `/local/chatbot/export.php`.
- ✅ Visit the Site administration pages under **Local plugins → Chatbot assistant** to ensure capability restrictions and placeholder notices load.

## Development and testing tips

- Use `php -l` on modified PHP files or run Moodle's PHPUnit for deeper validation.
- Run `npx grunt amd` to generate minified JS if distributing the plugin.
- Purge caches (`php admin/cli/purge_caches.php`) after modifying Mustache, JS or CSS assets.
- Compare styles with `local/geniai/styles.css` if you need to align branding further.

## License

GPL v3 or later. See Moodle's standard license text for details.
