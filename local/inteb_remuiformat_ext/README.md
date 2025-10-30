# Local INTEB Remuiformat Extension

This local plugin extends `format_remuiformat` functionality to show **ALL teachers** (editing teachers AND non-editing teachers) when using `theme_inteb`.

## Purpose

By default, `format_remuiformat` only shows editing teachers in course headers. This extension modifies the behavior to include:
- **Editing teachers** (with `mod/folder:managefiles` capability)
- **Non-editing teachers** (with `moodle/course:viewhiddenactivities` capability)

## Installation

### Step 1: Install the Local Plugin

1. Copy this directory to `/local/inteb_remuiformat_ext/`
2. Run: `php admin/cli/upgrade.php` or visit Site Administration → Notifications

### Step 2: Apply Patch to format_remuiformat

**IMPORTANT:** This plugin requires a small modification to `/course/format/remuiformat/lib.php`

Locate the `get_enrolled_teachers_context_formate()` function (around line 880) and modify it to check for theme_inteb:

```php
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $CFG, $PAGE;

    // PATCH: Check if theme_inteb is active and use extended functionality
    if ($PAGE->theme->name === 'inteb' && function_exists('local_inteb_get_enrolled_teachers_context_formate')) {
        return local_inteb_get_enrolled_teachers_context_formate($course, $frontlineteacher);
    }

    // Original function code continues below...
    global $OUTPUT, $USER, $DB;
    // ... rest of the original function ...
}
```

### Alternative: Automated Patch

You can apply the patch automatically:

```bash
cd /path/to/moodle
patch -p1 < local/inteb_remuiformat_ext/format_remuiformat.patch
```

## How It Works

1. When `format_remuiformat` is about to render teacher information, it checks if `theme_inteb` is active
2. If yes, it calls `local_inteb_get_enrolled_teachers_context_formate()` from this plugin
3. This function delegates to `\theme_inteb\format_remuiformat_helper::get_enrolled_teachers_context()`
4. The helper gathers ALL teachers (editing + non-editing) with appropriate indicators
5. The modified template in `theme_inteb/templates/format_remuiformat/optional_secheader.mustache` displays them

## Components

- **local/inteb_remuiformat_ext** - This plugin (wrapper functions)
- **theme/inteb/classes/format_remuiformat_helper.php** - Core logic for gathering all teachers
- **theme/inteb/templates/format_remuiformat/optional_secheader.mustache** - Modified template
- **theme/inteb/classes/coursehandler.php** - Extended course handler for course cards

## Uninstallation

1. Remove the patch from `/course/format/remuiformat/lib.php` (restore original code)
2. Uninstall via Site Administration → Plugins → Local plugins → INTEB Remuiformat Extension → Uninstall
3. Delete `/local/inteb_remuiformat_ext/` directory

## License

GPL v3 or later

## Author

INTEB - 2025
