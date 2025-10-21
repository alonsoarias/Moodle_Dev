# Educam Bot (local_educambot)

Educam Bot is a local Moodle plugin that adds a rule-based assistant to the entire platform. The chatbot analyses the user question using pattern matching, synonyms, keywords and page context to provide contextual answers without relying on external AI providers.

## Main features

- Floating widget rendered across the site with a responsive chat panel.
- Rule builder that supports patterns, synonyms, keywords, wildcard expressions, context hints, proactive suggestions and role restrictions.
- Flexible matching engine with stopword-aware token scoring, Levenshtein/Similar-text comparison and wildcard evaluation.
- Conversation logging, unanswered question tracking and automatic cleanup task.
- Privacy API implementation for GDPR compliance.
- Customisable settings for log retention and logging toggle.
- Admin knowledge base search with fuzzy ranking of stored rules.

## Administration

After installing the plugin, administrators can configure global settings under **Site administration → Plugins → Local plugins → Educam Bot**. The knowledge base manager is also available from the same section.

## Development notes

- The widget JavaScript is written as an AMD module located in `amd/src/widget.js`. Run Moodle's `grunt amd` task after modifying JavaScript to generate the production build.
- Database tables are defined in `db/install.xml`. Use the XMLDB editor for structural changes.
- Scheduler cleanup task honours the `retentionperiod` configuration.

## Requirements

- Moodle 4.0 or later.
- PHP 7.4 or later.

