# 🚀 Educam Bot - Quick Start Guide

## Installation

1. **Install the plugin**:
   - Copy the plugin to `local/educambot` in your Moodle directory
   - Visit `Site administration > Notifications` to complete the installation
   - The database tables will be created automatically

2. **Add sample data** (recommended for testing):
   ```bash
   php local/educambot/cli/add_sample_data.php
   ```

3. **Configure the plugin**:
   - Go to `Site administration > Plugins > Local plugins > Educam Bot`
   - Customize bot name, colors, and behavior
   - Enable conversation logging if desired

## Verifying the Widget Works

After installation, you should see a floating chat button on the bottom-right corner of any Moodle page (when logged in).

### If the widget doesn't appear:

1. **Check JavaScript is compiled**:
   ```bash
   ls -la local/educambot/amd/build/widget.min.js
   ```
   - This file must exist. If it doesn't, the widget won't load.

2. **Clear Moodle caches**:
   - Go to `Site administration > Development > Purge all caches`
   - Or run: `php admin/cli/purge_caches.php`

3. **Check you have the capability**:
   - The capability `local/educambot:use` must be assigned to your role
   - By default, it's assigned to all authenticated users

4. **Check browser console**:
   - Open browser Developer Tools (F12)
   - Look for JavaScript errors in the Console tab
   - Look for failed network requests in the Network tab

## Testing the Bot

1. **Click the chat button** in the bottom-right corner
2. **Try these test questions** (if you added sample data):
   - "¿Cómo accedo a la plataforma?"
   - "¿Cómo subo una tarea?"
   - "¿Cómo veo mis calificaciones?"

3. **Check suggested questions** that appear in the chat

## Adding Your Own Content

### Via Web Interface:

1. **Add Rules** (simple Q&A):
   - Go to `Site administration > Plugins > Local plugins > Educam Bot`
   - Click "Knowledge base"
   - Click "Add entry"
   - Fill in the pattern (questions), synonyms, keywords, and response

2. **Add Knowledge** (structured content):
   - Click "Structured knowledge" button
   - Click "Add knowledge entry"
   - Fill in title, summary, content, and tags

### Via CLI (bulk import):

```bash
# Add sample data
php local/educambot/cli/add_sample_data.php

# Force add even if data exists
php local/educambot/cli/add_sample_data.php --force
```

## Customization

### Colors and Branding:

Go to plugin settings and customize:
- **Bot name**: Default is "Educam Bot"
- **Widget label**: Button text
- **Primary color**: Main accent color (#0f6fc5)
- **Accent color**: Secondary highlights (#e7f0fb)
- **Background color**: Conversation background (#f7f9fc)
- **Text color**: Primary text color (#1f2937)

### Greeting Message:

Customize the initial greeting with placeholders:
- `{{botname}}` - The configured bot name
- `{{userfirstname}}` - User's first name
- `{{userfullname}}` - User's full name
- `{{courselist}}` - List of user's courses

Example: "Hello {{userfirstname}}! I'm {{botname}}. How can I help you?"

## Troubleshooting

### Widget doesn't appear:
1. Check JavaScript file exists: `local/educambot/amd/build/widget.min.js`
2. Purge all caches
3. Check capability: `local/educambot:use`
4. Check browser console for errors

### Bot doesn't respond:
1. Check you have rules or knowledge entries
2. Run: `php local/educambot/cli/add_sample_data.php`
3. Check service.php is accessible
4. Check browser Network tab for AJAX errors

### Answers are not relevant:
1. Improve your patterns and keywords
2. Add synonyms for common terms
3. Use the search preview in the management interface
4. Check confidence scores in conversation logs

## Development

### Compile JavaScript after changes:

If you modify `amd/src/widget.js`, you need to recompile:

```bash
# Manual copy (for quick testing)
cp local/educambot/amd/src/widget.js local/educambot/amd/build/widget.min.js

# Or use Grunt (recommended)
grunt amd --root=local/educambot
```

Then purge caches:
```bash
php admin/cli/purge_caches.php
```

### Database Schema:

The plugin uses 8 tables:
- `local_educambot_rule` - Simple Q&A rules
- `local_educambot_knowledge` - Structured knowledge base
- `local_educambot_topic` - Categories for knowledge
- `local_educambot_kn_topic` - Links knowledge to topics
- `local_educambot_relation` - Relations between knowledge entries
- `local_educambot_kn_context` - Context restrictions (course, role, page)
- `local_educambot_log` - Conversation logs
- `local_educambot_unanswered` - Unanswered questions

## Support

For issues and feature requests, check:
- Plugin documentation
- ANALYSIS.md for technical details
- Moodle forums

## License

GNU GPL v3 or later
