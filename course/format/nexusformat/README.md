# Nexus Format

A modern Moodle course format plugin with a two-column layout inspired by Edutin Academy. This format provides an immersive learning experience with content on the left and navigation on the right.

## Features

### Two-Column Layout
- **Content Area (70%)**: Displays activity content with AJAX loading for seamless navigation
- **Sidebar (30%)**: Collapsible navigation with course structure, progress tracking, and tools

### Course Navigation
- Hierarchical tree view with sections and activities
- Visual completion indicators (checkmarks, circles)
- Subsection support for nested content organization
- Click-to-load activities without page refresh

### Progress Tracking
- Visual progress bar showing course completion percentage
- Activity-level completion status in sidebar
- Gradable activities list with due dates and grades

### Notes System
- Personal note-taking for students
- Create, edit, and delete notes
- Notes stored per-course for each user

### Comments System
- Activity-level commenting
- Reply threads for discussions
- Like/heart reactions
- Sort by newest, oldest, or most liked
- Participation banner encouraging collaboration

### Mobile Responsive
- Drawer-style sidebar on mobile devices
- Floating action button to toggle sidebar
- Touch-friendly interface
- Automatic sidebar close on activity selection

### Customization
Configurable options in Site Administration:
- Primary and secondary accent colors
- Progress bar color
- Content area width (60-80%)
- Card border radius (none to extra large)
- Enable/disable card shadows
- Sidebar position (left or right)
- Toggle Activities tab
- Toggle Notes system
- Toggle Comments system
- Toggle Participation banner

## Requirements

- Moodle 4.0 or later (4.0+)
- PHP 7.4 or later
- Modern browser with JavaScript enabled

## Installation

1. Download or clone the plugin to `/course/format/nexusformat/`
2. Log in as administrator
3. Navigate to Site Administration > Notifications
4. Follow the installation prompts
5. Configure settings at Site Administration > Plugins > Course formats > Nexus Format

### Manual Installation

```bash
cd /path/to/moodle/course/format
git clone https://github.com/your-repo/nexusformat.git
```

Or download the ZIP and extract to `/course/format/nexusformat/`

## Configuration

### Global Settings
Navigate to **Site Administration > Plugins > Course formats > Nexus Format**

#### Layout Settings
- **Content area width**: Percentage of screen for main content (60-80%)

#### Color Settings
- **Primary accent color**: Main color for active elements and links
- **Secondary accent color**: Used in gradients (progress bar, banners)
- **Progress bar color**: End color of progress bar gradient

#### Feature Settings
- **Enable Activities tab**: Show/hide gradable activities list
- **Enable Notes system**: Allow students to create personal notes
- **Enable Comments system**: Enable activity commenting
- **Enable Participation banner**: Show collaboration encouragement banner

#### Visual Settings
- **Card border radius**: Roundness of card corners
- **Enable card shadows**: Add subtle shadows to cards
- **Sidebar position**: Left or right side of screen

### Course Settings
When editing a course:
1. Go to Course Settings
2. Under "Course format", select "Nexus Format"
3. Save changes

## Usage

### For Students
1. Click on any activity in the sidebar to load its content
2. Use tabs to switch between Content, Activities, and Notes
3. Track progress with the progress bar at the top
4. Add personal notes while studying
5. Participate in discussions through comments

### For Teachers
1. Edit mode works with standard Moodle editing interface
2. Hidden sections/activities are visible with indicators
3. Manage student comments (edit/delete capabilities)
4. View all student activity through standard Moodle reports

## Database Tables

The plugin creates the following tables:

### format_nexusformat_notes
Stores user notes:
- `id`: Primary key
- `courseid`: Course ID
- `cmid`: Course module ID (optional)
- `userid`: User ID
- `title`: Note title
- `content`: Note content
- `contentformat`: Content format
- `timecreated`: Creation timestamp
- `timemodified`: Last modification timestamp

### format_nexusformat_comments
Stores activity comments:
- `id`: Primary key
- `courseid`: Course ID
- `cmid`: Course module ID
- `userid`: User ID
- `content`: Comment content
- `contentformat`: Content format
- `parentid`: Parent comment ID (for replies)
- `timecreated`: Creation timestamp
- `timemodified`: Last modification timestamp

### format_nexusformat_likes
Stores comment likes:
- `id`: Primary key
- `commentid`: Comment ID
- `userid`: User ID
- `timecreated`: Like timestamp

## Web Services

The plugin provides these AJAX services:

- `format_nexusformat_get_activity_content`: Load activity content
- `format_nexusformat_get_notes`: Get user's notes
- `format_nexusformat_save_note`: Create/update note
- `format_nexusformat_delete_note`: Delete note
- `format_nexusformat_get_comments`: Get activity comments
- `format_nexusformat_save_comment`: Create/update comment
- `format_nexusformat_delete_comment`: Delete comment
- `format_nexusformat_toggle_like`: Like/unlike comment
- `format_nexusformat_get_replies`: Get comment replies

## Customization

### CSS Variables
Override default styles by setting CSS custom properties:

```css
:root {
    --nexus-accent-color: #0d6efd;
    --nexus-secondary-color: #764ba2;
    --nexus-progress-color: #0dcaf0;
    --nexus-content-width: 70%;
    --nexus-sidebar-width: 30%;
    --nexus-border-radius: 8px;
    --nexus-card-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
}
```

### Template Overrides
Templates can be overridden in your theme:
```
theme/yourtheme/templates/format_nexusformat/local/content.mustache
```

## Language Support

Currently supported languages:
- English (en)
- Spanish (es)

To add a new language:
1. Create `/lang/[code]/format_nexusformat.php`
2. Copy strings from English file
3. Translate all strings

## Troubleshooting

### Activities not loading
- Ensure JavaScript is enabled
- Check browser console for errors
- Verify AJAX services are enabled

### Completion not showing
- Enable completion tracking in course settings
- Set completion criteria for activities

### Comments not appearing
- Check if Comments system is enabled in settings
- Verify user has capability to view course

## Support

For issues and feature requests, please use the GitHub issue tracker.

## License

This plugin is licensed under the GNU GPL v3 or later.

Copyright (C) 2024 Nexus Learning

## Changelog

### Version 1.0.0
- Initial release
- Two-column layout with AJAX content loading
- Progress tracking with visual indicators
- Notes system for personal note-taking
- Comments system with replies and likes
- Mobile responsive design with drawer sidebar
- Configurable settings for colors and features
- Full Spanish and English language support
