# Changelog

All notable changes to the INTEB Chat Moodle plugin are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added
- New language strings for JavaScript UI: `recording`, `browsernotsupported`, `recordingerror`, `microphoneerror`, `tokensresetin`, `required`, `unknownerror`
- New language string `chatcompletions` for API type display
- Complete Spanish translations for all new strings

### Changed
- Replaced hardcoded text strings in `audio.js` with Moodle language string system
- Updated audio.js to load localized strings for error messages with proper fallbacks
- Cleaned up duplicate `js_` prefixed strings from language files (using existing strings instead)

### Fixed
- ESLint errors in AMD modules: removed unused parameters and imports in `settings.js` and `report.js`
- Backup/restore functionality with proper implementation of `intebchat_get_instance_analytics()`
- Duplicate function declaration `intebchat_get_instance_analytics()` causing fatal error
- Duplicate activity description when using single activity course format
- mod_form.php: Added missing 'text' option to audiomode selector
- mod_form.php: Fixed audiomode selector to include 'text' option
- mod_form.php: Fixed missing 'chatcompletions' language string
- mod_form.php: Fixed validation showing error on non-existent apikey field
- AMD modules recompiled with latest changes

---

## [3.6.1] - 2025-12-24

### Added
- Comprehensive README documentation in English and Spanish
- Complete CHANGELOG with full version history
- Site-wide usage reports accessible from Site Administration → Reports
- Course-level usage reports accessible from Course Reports navigation
- AJAX support for reports (no full page reload on filter changes)
- Conversation retention with automatic cleanup via scheduled task
- External API methods: `get_site_report`, `get_course_report`
- New language strings for reports interface (English and Spanish)
- New capabilities: `mod/intebchat:viewsitereport`

### Changed
- Analytics button removed from chat header (now accessible via Course Reports)
- Improved course reports navigation compatibility for Moodle 4.x
- Version bump to force language cache refresh

### Fixed
- Missing language component in `get_string()` calls causing errors
- Missing user name fields for `fullname()` function (`firstnamephonetic`, `lastnamephonetic`, `middlename`, `alternatename`)

---

## [3.6.0] - 2025-12-24

### Added
- User audio persistence - recordings saved permanently with conversations
- Conversation retention settings with configurable cleanup period
- Scheduled task `cleanup_conversations` for automatic old conversation cleanup
- Database fields for audio storage

### Fixed
- Audio data URI handling with codec parameters (regex pattern fix)
- Message validation bypass when audio is present
- MimeType storage before mediaRecorder disposal (null reference fix)
- Audio event handling mechanism simplified

---

## [3.5.0] - 2025-12-24

### Added
- **Streaming responses** using Server-Sent Events (SSE)
  - Real-time AI response display
  - Non-buffered output for improved UX
  - Proper nginx compatibility headers
- **Analytics dashboard** per activity instance
  - Total conversations, messages, tokens, users
  - Daily activity visualization
  - Top users ranking
  - Period filtering (day, week, month, all)
- **Offline/queue mode**
  - Messages queued when offline
  - Automatic retry when connection restored
  - Visual offline indicator
- AMD modules compiled with terser for production

### Fixed
- Scroll issues in chat interface
- Audio recording and playback issues

---

## [3.4.0] - 2025-12-24

### Added
- **API key encryption** using AES-256-CBC with Moodle's secret salt
  - Automatic detection of encrypted values
  - Fallback to Moodle 4.0+ core encryption
  - Instance-level API key support
- **Prompt injection protection**
  - Input sanitization with max length enforcement (10,000 chars)
  - Role validation for messages
  - Clean parameter parsing
- **Rate limiting**
  - Sliding window algorithm
  - User-based limits (default: 60 req/min)
  - IP-based limits (default: 30 req/min)
  - X-RateLimit-* response headers
  - HTTP 429 handling

### Changed
- Improved error handling and validation
- Enhanced security for all API endpoints
- Security headers added to API responses

---

## [3.3.1] - 2025-12-24

### Added
- Live countdown timer for token reset display
- Hover animations for all mascot characters
- Realtime message saving for conversation persistence (OpenAI Realtime API)

### Changed
- Redesigned mascot animations for more natural feel
- Updated INTEB Assistant SVG with official brand colors (#00A651, #003DA5)
- Moved mascot from header to corner assistant position

### Fixed
- Mascot visibility in assistant position
- Token display labels showing incorrect values
- Dynamic token updates in UI

---

## [3.3.0] - 2025-12-24

### Added
- **Mascot selection feature** with 6 animated characters:
  - INTEB Assistant (default, brand colors)
  - Robot
  - Cat
  - Owl
  - Clippy
  - Lightbulb
- SVG mascot illustrations with CSS animations

### Fixed
- Token limit tracking to prevent double-counting
- Dynamic token display updates in UI

---

## [3.2.0] - 2025-08-11

### Added
- **Dark mode** with theme toggle button
- Conversation action modals:
  - Clear conversation
  - Delete conversation
  - Rename conversation
- **WhatsApp-style audio recording overlay**
- Configurable voice selection (11 OpenAI voices)
- Audio autoplay sanitization for security

### Changed
- Refactored form fields and validation for API types
- Enhanced chat module with improved modals and i18n
- Moved and restyled theme toggle button in chat UI

### Fixed
- Instance voice persistence across sessions
- Audio format handling improvements
- Table name prefix issues in DB queries

---

## [3.1.0] - 2025-08-05

### Added
- **Audio recording and transcription** using OpenAI Whisper API
  - Supported formats: MP3, MP4, MPEG, MPGA, M4A, WAV, WebM, OGG
  - Max file size: 25MB
  - Automatic language detection
- **Text-to-speech** audio responses
  - 11 voice options: alloy, ash, ballad, coral, echo, fable, nova, onyx, sage, shimmer, verse
- Global audio settings in plugin configuration
- Animated assistant UI enhancements
- Voice selection support at instance level
- Temporary audio file serving

### Changed
- Refactored audio and chat JS modules for improved support
- Enhanced conversation and audio handling

### Fixed
- Conversation deletion handling in clearConversation
- Modal dialogs for conversation actions

---

## [3.0.0] - 2025-07-31

### Added
- **Conversation management system**
  - Create, load, save conversations
  - Conversation titles with auto-generation
  - Message history with pagination
  - Preview text for conversation list
- Conversation creation and message handling improvements
- Restore chat history functionality
- `intebchat_conversations` database table

### Changed
- Major refactor of chat module to support conversations
- Removed legacy admin reports (replaced with per-instance analytics)
- Updated model selection options

### Fixed
- Token usage tracking with consistent period start
- Username configuration issues

---

## [2.2.0] - 2025-07-24

### Added
- Token limit tracking per user
- Usage report page
- `intebchat_token_usage` database table
- Configurable token limits per period (hour, day, week, month)
- `locallib.php` with helper functions

### Changed
- Updated database schema with new fields
- Improved backup/restore support

### Fixed
- Token tracking consistency
- Period start calculations

---

## [2.1.0] - 2025-07-23

### Added
- Username configuration feature
- Token usage tracking foundation

### Fixed
- Token usage tracking by using consistent period start
- Various bug fixes from validation review

---

## [2.0.0] - 2025-07-22

### Added
- **OpenAI Assistants API** support
  - Stateful AI assistants with persistent threads
  - Thread ID tracking for conversation continuity
  - Custom assistant configuration per instance
- **OpenAI Chat Completions API** support
  - Configurable model parameters (temperature, top-p, penalties)
  - Custom system prompts
  - Source of truth / knowledge base
- Activity module structure for Moodle
- Backup and restore support
- Privacy provider for GDPR compliance

### Changed
- Complete rewrite from block plugin to activity module
- New database schema

---

## [1.x.x] - Earlier Development

### Added
- Initial plugin structure
- Basic OpenAI integration
- Chat interface foundation

---

## Migration Notes

### Upgrading to 3.6.x
1. Run Moodle upgrade to apply database changes
2. Purge caches to refresh language strings
3. Configure retention settings in plugin configuration if automatic cleanup is desired
4. Reports now accessible from:
   - Course Reports → INTEB Chat Report
   - Site Administration → Reports → INTEB Chat Report

### Upgrading to 3.5.x
- Streaming responses enabled by default
- Analytics dashboard available per instance (requires `mod/intebchat:viewanalytics` capability)
- Offline mode automatically activates when connection lost

### Upgrading to 3.4.x
- API keys are automatically encrypted on first access after upgrade
- Rate limiting disabled by default - enable in plugin settings if needed
- Existing API keys continue to work

### Upgrading to 3.3.x
- Default mascot is INTEB Assistant
- Token display now shows live countdown to reset

### Upgrading to 3.0.x
- Conversations feature requires database upgrade (automatic via Moodle upgrade)
- Previous chat history is preserved in new conversation format
- Legacy admin reports removed - use per-instance analytics instead

---

## Links

- [GitHub Repository](https://github.com/alonsoarias/mod_intebchat)
- [Issue Tracker](https://github.com/alonsoarias/mod_intebchat/issues)
- [Moodle Plugin Directory](https://moodle.org/plugins/mod_intebchat)
