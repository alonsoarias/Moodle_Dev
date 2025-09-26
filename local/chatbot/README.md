# Moodle local\_chatbot

A lightweight Moodle plugin that adds a floating, Moodle-native chatbot widget to every page for authenticated users. The widget mimics the position and behaviour of the `local_geniai` assistant while answering questions using simple keyword matching and quick action shortcuts to relevant Moodle pages.

## Features

- Floating launcher with modern UI inspired by `local_geniai`.
- Server-side keyword matcher with configurable fallback and welcome messages.
- Quick actions that link to common areas such as the user profile and calendar.
- Optional conversation export (HTML/CSV/JSON) when the user has the export capability.
- Conversation history persisted in the `local_chatbot_logs` table.
- Accessible Mustache template and AMD module written against Moodle 4.3 APIs.

## Requirements

- Moodle 4.3 (build 2023100900) or higher.
- PHP 8.0 or later.

## Installation

1. Copy this directory to `moodle/local/chatbot` or clone the repository inside the `local` folder.
2. Log in as an administrator and follow the upgrade notifications to install the plugin.
3. Run Moodle cron so the caches are rebuilt: `php admin/cli/cron.php`.

The plugin loads the AMD source module directly so the repository does not ship minified assets. When deploying to production you may optionally generate minified builds with Moodle's AMD tooling:

```bash
npx grunt amd
```

## Configuration

Navigate to **Site administration → Plugins → Local plugins → Chatbot assistant** to configure:

- **Enable chatbot:** Toggle the floating widget on/off.
- **Assistant name:** Header text and aria labels used by the widget.
- **Widget position:** Bottom-right (default) or bottom-left.
- **Theme:** Choose between modern, minimal or dark styles.
- **Welcome message:** Shown the first time a user opens the chatbot.
- **Fallback response:** Message returned when no keyword matches.
- **Maximum message length:** Client-side limit for user messages.
- **Allow conversation export:** Show the export button for users with `local/chatbot:export`.

## Capabilities

| Capability | Description | Default roles |
|------------|-------------|----------------|
| `local/chatbot:use` | Use the widget and send messages | All authenticated users |
| `local/chatbot:manage` | Access configuration pages | Manager |
| `local/chatbot:export` | Export conversation history | All authenticated users |

## Usage

After installation the widget automatically appears for authenticated users on pages where popup notifications are allowed. Open the launcher, type a message and the assistant will respond. Quick action buttons and suggested prompts offer shortcuts to frequently visited areas in Moodle.

## Troubleshooting

- **Widget not visible:** Confirm the plugin is enabled and the user has the `local/chatbot:use` capability. The widget is hidden for guests and maintenance layouts.
- **JavaScript cache:** If changes to the AMD module are not reflected, purge caches from **Site administration → Development → Purge caches**.
- **Export button hidden:** Ensure the user has `local/chatbot:export` and that the setting “Allow conversation export” remains enabled.

## Development notes

- Conversations are stored in `local_chatbot_logs`. Clean up records through standard Moodle DB maintenance or by truncating the table.
- Web services are registered for AJAX usage only (see `db/services.php`).
- Placeholder admin pages live under `local/chatbot/admin` for future expansion and currently display informative notices only.

## License

This plugin is released under the GNU General Public License v3 or later.
