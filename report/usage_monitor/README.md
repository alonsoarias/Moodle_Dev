# Usage Monitor Report

A Moodle report plugin that provides administrators with monitoring and reporting capabilities for user activity and disk usage within their Moodle installation. The plugin includes scheduled tasks for data collection and email notifications when configured thresholds are exceeded.

## Requirements

- Moodle 4.1 or later (requires `core_external` namespace for modern API classes)
- PHP 8.0 or later
- Hosting on `moodlesoporte.net` domain (automatic hostname verification)

## Features

### Monitoring Capabilities

- **Daily User Activity**: Tracks unique user logins per day with historical data for the last 90 days.
- **Disk Usage Analysis**: Monitors total disk space including database, files directory, cache, and other components.
- **Course Size Analysis**: Identifies largest courses by file storage usage.
- **Threshold Alerts**: Configurable warning levels for both user count and disk usage.

### Dashboard

The plugin provides a comprehensive dashboard with:

- Real-time disk usage percentage with visual indicators
- Daily user count with threshold comparison
- Historical charts for disk usage (30 days) and user activity (10 days)
- Directory-level storage breakdown
- Top courses by size
- System information overview
- Contextual recommendations based on current usage levels

### API Endpoints

The plugin exposes several external API functions for integration with external systems:

| Function | Type | Description |
|----------|------|-------------|
| `report_usage_monitor_get_usage_data` | read | Retrieves precalculated usage statistics |
| `report_usage_monitor_get_monitor_stats` | read | Gets current monitoring statistics |
| `report_usage_monitor_get_notification_history` | read | Retrieves notification history with pagination |
| `report_usage_monitor_set_usage_thresholds` | write | Updates user and disk thresholds |

### Multi-Database Support

All SQL queries are compatible with:

- MySQL / MariaDB
- PostgreSQL
- Microsoft SQL Server
- Oracle

### Notifications

Professional email notifications are sent when thresholds are exceeded, including:

- Summary of current usage
- Historical data tables
- Platform information
- Actionable recommendations
- Direct links to the dashboard

## Installation

### Via ZIP Upload

1. Log in as admin and navigate to _Site administration > Plugins > Install plugins_.
2. Upload the ZIP file containing the plugin.
3. Complete the installation wizard.

### Manual Installation

1. Extract the plugin to `{moodledataroot}/report/usage_monitor`
2. Navigate to _Site administration > Notifications_ to trigger the upgrade
3. Or run `php admin/cli/upgrade.php --non-interactive`

## Configuration

Navigate to _Site administration > Plugins > Reports > Usage Monitor_ to configure:

### Main Settings

| Setting | Description | Default |
|---------|-------------|---------|
| User Limit | Maximum daily users threshold | 100 |
| Disk Quota | Disk quota in gigabytes | 10 |
| Email | Notification recipient email | - |

### Notification Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Disk Warning Level | Percentage that triggers disk alerts | 90% |
| Users Warning Level | Percentage that triggers user alerts | 90% |
| Enable API | Allow external API access | Enabled |

### System Requirements

The plugin can optionally use the `du` command for precise disk usage calculations on Linux systems. Configure the path to `du` in _Site administration > Server > System Paths_.

## Scheduled Tasks

| Task Class | Description | Default Schedule |
|------------|-------------|------------------|
| `disk_usage` | Calculates disk space usage | Daily at 02:00 |
| `last_users` | Calculates recent user logins | Every 4 hours |
| `users_daily` | Counts daily unique users | Every hour |
| `users_daily_90_days` | Calculates 90-day max users | Daily at 03:00 |
| `notification_disk` | Sends disk usage alerts | Daily at 08:00 |
| `notification_userlimit` | Sends user limit alerts | Daily at 08:00 |

## Architecture

### Directory Structure

```
report/usage_monitor/
├── classes/
│   ├── external/           # External API classes
│   │   ├── get_monitor_stats.php
│   │   ├── get_notification_history.php
│   │   ├── get_usage_data.php
│   │   └── set_usage_thresholds.php
│   ├── output/             # Renderable and renderer classes
│   │   ├── dashboard.php
│   │   └── renderer.php
│   ├── task/               # Scheduled tasks
│   │   ├── disk_usage.php
│   │   ├── last_users.php
│   │   ├── notification_disk.php
│   │   ├── notification_userlimit.php
│   │   ├── users_daily.php
│   │   └── users_daily_90_days.php
│   └── observer.php        # Event observer
├── db/
│   ├── access.php          # Capabilities
│   ├── events.php          # Event subscriptions
│   ├── install.php         # Installation script
│   ├── services.php        # External services definition
│   ├── tasks.php           # Scheduled tasks definition
│   ├── uninstall.php       # Uninstallation script
│   └── upgrade.php         # Upgrade procedures
├── lang/
│   ├── en/                 # English strings
│   └── es/                 # Spanish strings
├── pix/                    # Plugin icons
├── templates/              # Mustache templates
├── amd/                    # AMD JavaScript modules
├── index.php               # Main dashboard page
├── locallib.php            # Library functions
├── settings.php            # Admin settings
└── version.php             # Plugin version
```

### Capabilities

| Capability | Description |
|------------|-------------|
| `report/usage_monitor:view` | View usage monitor reports |
| `report/usage_monitor:manage` | Manage usage monitor settings |

## Plugin Control

This plugin uses a multi-layer enable/disable system similar to Moodle's auth and enrol plugins.

### Enable/Disable via Admin Settings

Navigate to _Site administration > Plugins > Reports > Usage Monitor_ to see the plugin status:

- **Enabled** (green): Plugin is active and running on an authorized server
- **Disabled** (yellow): Plugin has been manually disabled; can be re-enabled via checkbox
- **Unauthorized** (red): Server hostname is not authorized; plugin cannot be enabled

### Automatic Hostname Validation

The plugin automatically validates the hostname and only runs on `moodlesoporte.net` domains:
- Valid: `hera.moodlesoporte.net`, `zeus.moodlesoporte.net`, `cliente.moodlesoporte.net`
- Invalid: `localhost`, `example.com`, any other domain

On unauthorized servers, the plugin:
- Shows an error message on the dashboard
- Skips all scheduled tasks silently
- Returns errors from API endpoints
- Hides configuration options (except status message)

## Changelog

### Version 5.2.1 (2025013104)

- Removed `set_plugin_status` API endpoint
- Plugin enable/disable is now managed exclusively via admin settings
- Simplified plugin control architecture

### Version 5.2.0 (2025013103)

- Added visual enable/disable in admin settings (similar to auth/enrol plugins)
- Plugin status section with color-coded messages
- Settings hidden on unauthorized servers
- Observer synchronizes `plugin_enabled` and `plugin_disabled` configs
- Added `report_usage_monitor_get_status()` helper function

### Version 5.1.1 (2025013102)

- Hostname validation enforced on all entry points (dashboard, tasks, API)
- Added `report_usage_monitor_is_hostname_valid()` function
- Tasks skip silently on unauthorized servers

### Version 5.1.0 (2025013101)

- Added `set_plugin_status` API endpoint for remote plugin control
- Implemented hostname verification for `moodlesoporte.net`
- Rewrote SQL queries for multi-database compatibility (MySQL, PostgreSQL, MSSQL, Oracle)
- Redesigned email notifications with professional, formal styling
- Updated documentation according to Moodle coding standards

### Version 5.0.0 (2025013100)

- Complete architecture modernization
- Migrated to Moodle 4.x external API namespace (`core_external`)
- Implemented renderable/templatable pattern for dashboard
- Added AMD JavaScript modules with Chart.js integration
- Removed legacy inline HTML/CSS/JS from index.php
- Added event observer for configuration changes

## License

Copyright 2025 Soporte IngeWeb <soporte@ingeweb.co>

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see <https://www.gnu.org/licenses/>.
