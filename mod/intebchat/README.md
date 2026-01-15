# INTEB Chat - AI-Powered Chat Module for Moodle

[![Moodle](https://img.shields.io/badge/Moodle-4.1+-orange.svg)](https://moodle.org)
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Version](https://img.shields.io/badge/Version-3.6.1-green.svg)](https://github.com/alonsoarias/mod_intebchat)

**[Versión en Español](README_ES.md)**

INTEB Chat is a powerful Moodle activity module that integrates OpenAI's AI capabilities directly into your Learning Management System. It enables students and teachers to interact with AI assistants within courses, supporting text chat, voice input/output, and real-time conversations.

---

## Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Capabilities](#capabilities)
- [API Reference](#api-reference)
- [Database Schema](#database-schema)
- [Privacy & Security](#privacy--security)
- [Troubleshooting](#troubleshooting)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## Features

### 🤖 AI Integration

#### Chat Completions API
- Stateless conversation model using OpenAI's GPT models
- Configurable parameters: temperature, top-p, frequency/presence penalties
- Custom system prompts and knowledge base ("source of truth")
- Automatic conversation history management
- Default model: GPT-4.1

#### Assistants API
- Stateful AI assistants with persistent threads
- Assistant-specific instructions and configurations
- Thread ID tracking for conversation continuity
- Support for custom OpenAI assistants

### 🎙️ Audio Capabilities

#### Speech-to-Text
- Voice input using OpenAI Whisper API
- Supported formats: MP3, MP4, MPEG, MPGA, M4A, WAV, WebM, OGG
- Maximum file size: 25MB
- Automatic language detection

#### Text-to-Speech
- AI responses read aloud
- 11 voice options: alloy, ash, ballad, coral, echo, fable, nova, onyx, sage, shimmer, verse
- Configurable at global and instance level

#### Real-time Conversation Mode
- Bidirectional audio using OpenAI Realtime API (WebRTC)
- Natural voice conversation with automatic speech detection
- Server Voice Activity Detection (VAD) for automatic turn-taking
- Low-latency responses

### 💬 Chat Features

#### Conversation Management
- Create, save, and continue conversations
- Auto-generated conversation titles
- Full-text search across titles and messages
- Conversation history with pagination
- Clear and delete conversations

#### Streaming Responses
- Real-time response display using Server-Sent Events (SSE)
- Progressive token display
- Non-buffered output for improved UX

#### Offline Mode
- Messages queued when offline
- Automatic retry when connection restored
- Visual offline indicator

### 📊 Analytics & Reporting

#### Instance Analytics
- Total conversations, messages, tokens, users
- Daily activity visualization
- Top users ranking
- Period filtering (day, week, month, all)

#### Course Reports
- Per-instance usage breakdown
- Student activity tracking
- Token consumption by user
- Accessible from Course Reports navigation

#### Site Reports
- Site-wide usage statistics
- Course-level breakdowns
- User-level tracking
- Accessible from Site Administration

### 🎨 User Interface

#### Theme Support
- Dark/Light mode toggle
- CSS custom properties for theming
- Responsive design
- Mobile-optimized interface

#### Animated Mascots
Six interactive characters for visual engagement:
- INTEB Assistant (default)
- Robot
- Cat
- Owl
- Clippy
- Lightbulb

### 🔒 Security

#### API Key Protection
- AES-256-CBC encryption for stored API keys
- Automatic encryption on first access
- Instance-level API key support

#### Rate Limiting
- Sliding window algorithm
- User-based limits (default: 60 req/min)
- IP-based limits (default: 30 req/min)
- X-RateLimit-* response headers

#### Input Protection
- Prompt injection prevention
- Input sanitization with max length (10,000 chars)
- CSRF protection via session keys

### 🔧 Administration

#### Token Management
- Configurable limits per user per period
- Period options: hour, day, week, month
- Real-time usage tracking
- Live countdown to reset

#### Conversation Retention
- Automatic cleanup of old conversations
- Configurable retention period (days)
- Scheduled task for cleanup

---

## Requirements

| Requirement | Version |
|-------------|---------|
| Moodle | 4.1 or higher |
| PHP | 7.4 or higher |
| PHP Extensions | cURL, OpenSSL |
| OpenAI API Key | Required |

---

## Installation

### Method 1: Direct Download

1. Download the latest release from [GitHub](https://github.com/alonsoarias/mod_intebchat/releases)
2. Extract to `/mod/intebchat` in your Moodle installation
3. Navigate to **Site Administration → Notifications**
4. Follow the installation wizard

### Method 2: Git Clone

```bash
cd /path/to/moodle/mod
git clone https://github.com/alonsoarias/mod_intebchat.git intebchat
```

Then visit **Site Administration → Notifications** to complete installation.

---

## Configuration

### Global Settings

Navigate to **Site Administration → Plugins → Activity modules → INTEB Chat**

#### General Settings

| Setting | Description | Default |
|---------|-------------|---------|
| API Key | Your OpenAI API key (stored encrypted) | Required |
| API Type | Chat Completions or Assistants API | Chat |
| Restrict Usage | Require user login | Enabled |
| Enable Logging | Store conversation history | Disabled |
| Allow Instance Settings | Allow per-instance API keys | Disabled |

#### Audio Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Audio | Allow audio input/output globally | Disabled |
| Default Voice | Voice for text-to-speech | alloy |

#### Token Limits

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Token Limit | Restrict token usage per user | Disabled |
| Max Tokens Per User | Maximum tokens per period | 10,000 |
| Token Limit Period | Reset period (hour/day/week/month) | day |

#### Chat API Defaults

| Setting | Description | Default |
|---------|-------------|---------|
| System Prompt | Default system instructions | - |
| Source of Truth | Knowledge base for context | - |
| Model | Default model | gpt-4.1 |
| Temperature | Randomness (0-2) | 0.7 |
| Max Tokens | Maximum response tokens | 1024 |
| Top P | Nucleus sampling (0-1) | 1.0 |
| Frequency Penalty | Frequency penalty (-2 to 2) | 0 |
| Presence Penalty | Presence penalty (-2 to 2) | 0 |

#### Rate Limiting

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Rate Limiting | Activate request limits | Disabled |
| User Limit | Requests per minute per user | 60 |
| IP Limit | Requests per minute per IP | 30 |

#### Retention Settings

| Setting | Description | Default |
|---------|-------------|---------|
| Enable Retention | Auto-cleanup old conversations | Disabled |
| Retention Days | Days to keep conversations | 30 |

### Instance Settings

When adding INTEB Chat to a course, you can configure:

| Setting | Description |
|---------|-------------|
| Name | Activity name |
| Description | Activity description |
| Show Labels | Display name labels in chat |
| Mascot | Select animated character |
| Enable Audio | Enable audio for this instance |
| Audio Mode | text, audio, both, or conversational |
| Voice | Instance-specific voice |
| Instructions | Custom assistant instructions |
| Assistant Name | Display name for assistant |
| Persist Conversations | Save conversations between sessions |

---

## Usage

### For Students

1. Click on the INTEB Chat activity in your course
2. Type your message in the input field or use voice input
3. Press Enter or click Send
4. View the AI response in the chat area
5. Use the sidebar to manage conversations

### For Teachers

1. Add INTEB Chat activity to your course
2. Configure instance settings as needed
3. Access analytics via the course reports
4. Monitor student usage and token consumption

### For Administrators

1. Configure global settings
2. Set up token limits if needed
3. Enable rate limiting for protection
4. Access site-wide reports
5. Configure retention policies

---

## Capabilities

| Capability | Description | Default Roles |
|------------|-------------|---------------|
| `mod/intebchat:view` | View and use the chat | Student, Teacher, Manager |
| `mod/intebchat:addinstance` | Add new instances | Editing Teacher, Manager |
| `mod/intebchat:viewownconversations` | View own conversation history | Student, Teacher, Manager |
| `mod/intebchat:viewstudentconversations` | View student conversations | Teacher, Manager |
| `mod/intebchat:viewallconversations` | View all conversations | Manager |
| `mod/intebchat:viewanalytics` | Access analytics dashboard | Teacher, Manager |
| `mod/intebchat:viewsitereport` | Access site-wide reports | Manager |
| `mod/intebchat:managetokenlimits` | Manage user token limits | Manager |

---

## API Reference

### Web Services

| Function | Description | Type |
|----------|-------------|------|
| `mod_intebchat_create_conversation` | Create new conversation | write |
| `mod_intebchat_load_conversation` | Load conversation messages | read |
| `mod_intebchat_clear_conversation` | Clear conversation history | write |
| `mod_intebchat_update_conversation_title` | Update conversation title | write |
| `mod_intebchat_get_assistants` | List available assistants | read |
| `mod_intebchat_save_realtime_message` | Save realtime message | write |
| `mod_intebchat_get_site_report` | Get site-wide statistics | read |
| `mod_intebchat_get_course_report` | Get course-level statistics | read |

### Internal Endpoints

| Endpoint | Description |
|----------|-------------|
| `/mod/intebchat/api/completion.php` | Chat completions |
| `/mod/intebchat/api/completion_stream.php` | Streaming completions |
| `/mod/intebchat/api/realtime_token.php` | Realtime API sessions |

---

## Database Schema

### Tables

#### `intebchat`
Main activity module instances table.

#### `intebchat_log`
Message history and token tracking.

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| instanceid | int | Module instance FK |
| userid | int | User FK |
| conversationid | int | Conversation FK |
| usermessage | text | User's message |
| airesponse | text | AI's response |
| prompttokens | int | Input tokens |
| completiontokens | int | Output tokens |
| totaltokens | int | Combined tokens |
| timecreated | int | Timestamp |

#### `intebchat_conversations`
Conversation management.

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| instanceid | int | Module instance FK |
| userid | int | User FK |
| title | varchar(255) | Conversation title |
| preview | varchar(255) | Last message preview |
| threadid | varchar(255) | OpenAI thread ID |
| messagecount | int | Message count |
| timecreated | int | Created timestamp |
| timemodified | int | Last modified |

#### `intebchat_token_usage`
Token limit tracking per user.

| Field | Type | Description |
|-------|------|-------------|
| id | int | Primary key |
| userid | int | User FK |
| tokensused | int | Tokens in current period |
| periodstart | int | Period start timestamp |
| periodtype | varchar(10) | Period type |

---

## Privacy & Security

### GDPR Compliance

The plugin implements Moodle's privacy API:
- User data can be exported
- Conversation data marked as deletable
- Privacy metadata declared for all data storage

### Data Storage

| Data Type | Location | Retention |
|-----------|----------|-----------|
| Conversations | Moodle database | Configurable |
| Messages | Moodle database | Configurable |
| Token usage | Moodle database | Period-based |
| Audio files | Moodle temp directory | Session-based |
| Thread IDs | OpenAI servers | OpenAI policy |

### Security Measures

- API keys encrypted at rest (AES-256-CBC)
- CSRF protection on all endpoints
- Input sanitization and validation
- Rate limiting for DoS protection
- Session key validation

---

## Troubleshooting

### Common Issues

#### "Invalid API Key" Error
- Verify your OpenAI API key is correct
- Check if the key has sufficient credits
- Ensure the key has access to required models

#### Audio Not Working
- Enable audio in global settings first
- Enable audio for the specific instance
- Check browser microphone permissions
- Verify HTTPS is enabled (required for WebRTC)

#### Token Limit Reached
- Wait for the period to reset (shown in UI)
- Contact administrator to increase limit
- Consider upgrading to a higher limit

#### Rate Limit Errors
- Wait a few seconds and retry
- Contact administrator if persistent

### Debug Mode

Enable Moodle debugging to see detailed error messages:

```php
// In config.php
$CFG->debug = DEBUG_DEVELOPER;
$CFG->debugdisplay = 1;
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a detailed history of all changes.

---

## Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards

- Follow Moodle coding guidelines
- Include PHPDoc comments
- Add language strings for all text
- Write tests for new features

---

## License

This plugin is licensed under the [GNU General Public License v3](https://www.gnu.org/licenses/gpl-3.0.html).

---

## Support

### Author

**Alonso Arias**
- Email: soporte@ingeweb.co
- Website: [ingeweb.co](https://ingeweb.co)

### Resources

- [GitHub Repository](https://github.com/alonsoarias/mod_intebchat)
- [Issue Tracker](https://github.com/alonsoarias/mod_intebchat/issues)
- [Moodle Plugin Directory](https://moodle.org/plugins/mod_intebchat)

### Getting Help

1. Check the [Troubleshooting](#troubleshooting) section
2. Search existing [GitHub Issues](https://github.com/alonsoarias/mod_intebchat/issues)
3. Create a new issue with detailed information

---

## Acknowledgments

- OpenAI for providing the AI APIs
- Moodle community for the excellent LMS platform
- All contributors and testers

---

*Made with ❤️ for the Moodle community*
