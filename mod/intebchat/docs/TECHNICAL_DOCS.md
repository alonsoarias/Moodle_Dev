# INTEB Chat - Technical Documentation

> **Version:** 3.6.1
> **Moodle Compatibility:** 4.1+ (2022112800)
> **Package:** mod_intebchat
> **Author:** Alonso Arias <soporte@ingeweb.co>
> **License:** GNU GPL v3 or later

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Structure](#2-directory-structure)
3. [Database Schema](#3-database-schema)
4. [APIs and Services](#4-apis-and-services)
5. [Hooks and Events](#5-hooks-and-events)
6. [Core Classes](#6-core-classes)
7. [Backup and Restore](#7-backup-and-restore)
8. [Caching Strategy](#8-caching-strategy)
9. [Security Considerations](#9-security-considerations)
10. [Extensibility](#10-extensibility)
11. [Developer Notes](#11-developer-notes)

---

## 1. Architecture Overview

### 1.1 Plugin Type

INTEB Chat is a Moodle **Activity Module** (`mod_`) that integrates OpenAI's AI APIs into Moodle courses. As an activity module, it:

- Appears in the activity chooser
- Supports course module features (completion, groups, backup)
- Has per-instance configuration
- Integrates with Moodle's gradebook (view tracking)

### 1.2 Component Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                         MOODLE CORE                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │   Course    │  │  Gradebook  │  │   Backup    │              │
│  │   Module    │  │  API        │  │   System    │              │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘              │
└─────────┼────────────────┼────────────────┼─────────────────────┘
          │                │                │
          ▼                ▼                ▼
┌─────────────────────────────────────────────────────────────────┐
│                       MOD_INTEBCHAT                              │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │                      lib.php                             │    │
│  │  - Plugin callbacks (supports, add/update/delete)        │    │
│  │  - Core functions (token limits, logging, config)        │    │
│  └─────────────────────────────────────────────────────────┘    │
│                              │                                   │
│     ┌────────────────────────┼────────────────────────┐         │
│     ▼                        ▼                        ▼         │
│  ┌──────────┐         ┌──────────────┐         ┌──────────┐     │
│  │ view.php │         │ classes/     │         │ api/     │     │
│  │ Chat UI  │◄───────►│ external.php │◄───────►│ *.php    │     │
│  └──────────┘         │ completion   │         │ OpenAI   │     │
│       ▲               │ events       │         │ Calls    │     │
│       │               └──────────────┘         └────┬─────┘     │
│       │                                             │           │
│       ▼                                             ▼           │
│  ┌──────────────┐                          ┌────────────────┐   │
│  │ templates/   │                          │ OPENAI API     │   │
│  │ Mustache     │                          │ - Chat         │   │
│  │ chat.mustache│                          │ - Assistants   │   │
│  └──────────────┘                          │ - Audio        │   │
│                                            │ - Realtime     │   │
│                                            └────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### 1.3 Data Flow

```
┌──────────┐    HTTP/AJAX     ┌─────────────────┐    HTTPS     ┌──────────┐
│  Browser │ ◄──────────────► │  Moodle Server  │ ◄──────────► │ OpenAI   │
│          │                  │  mod_intebchat  │              │ API      │
└──────────┘                  └────────┬────────┘              └──────────┘
                                       │
                                       ▼
                              ┌─────────────────┐
                              │    Database     │
                              │ - intebchat     │
                              │ - intebchat_log │
                              │ - conversations │
                              │ - token_usage   │
                              └─────────────────┘
```

---

## 2. Directory Structure

```
mod_intebchat/
├── version.php              # Plugin metadata and version
├── lib.php                  # Core Moodle callbacks and functions
├── locallib.php             # Helper functions for conversation management
├── mod_form.php             # Activity instance configuration form
├── settings.php             # Global admin settings
├── view.php                 # Main activity view (chat interface)
├── index.php                # Course module listing
├── analytics.php            # Instance analytics page
├── report_course.php        # Course-level usage report
├── report_site.php          # Site-wide usage report
├── load-audio-temp.php      # Audio file serving endpoint
│
├── api/                     # API endpoints for OpenAI communication
│   ├── completion.php       # Chat completion (non-streaming)
│   ├── completion_stream.php # Streaming chat completion
│   ├── thread.php           # Assistant API thread management
│   ├── realtime_token.php   # WebRTC realtime token generation
│   └── report_tokens.php    # Token usage reporting
│
├── amd/                     # AMD JavaScript modules
│   ├── src/
│   │   ├── lib.js           # Main chat functionality
│   │   ├── audio.js         # Audio recording/playback
│   │   ├── realtime.js      # WebRTC realtime communication
│   │   ├── settings.js      # Admin settings dynamic updates
│   │   └── report.js        # Report page functionality
│   └── build/               # Minified JS files
│
├── backup/                  # Backup and restore handlers
│   └── moodle2/
│       ├── backup_intebchat_activity_task.class.php
│       ├── backup_intebchat_stepslib.php
│       ├── restore_intebchat_activity_task.class.php
│       └── restore_intebchat_stepslib.php
│
├── classes/                 # PHP classes (autoloaded)
│   ├── external.php         # Web services implementation
│   ├── completion.php       # OpenAI API wrapper
│   ├── audio.php            # Audio processing
│   ├── ratelimiter.php      # Rate limiting
│   ├── completion/          # Completion handlers
│   │   ├── assistant.php    # Assistant API handler
│   │   └── chat.php         # Chat API handler
│   ├── event/               # Event classes
│   │   ├── course_module_viewed.php
│   │   └── course_module_instance_list_viewed.php
│   ├── privacy/             # GDPR privacy provider
│   │   └── provider.php
│   └── task/                # Scheduled tasks
│       └── cleanup_conversations.php
│
├── db/                      # Database definitions
│   ├── install.xml          # Table schema
│   ├── upgrade.php          # Upgrade scripts
│   ├── access.php           # Capabilities
│   ├── services.php         # Web services definitions
│   ├── tasks.php            # Scheduled tasks
│   └── caches.php           # Cache definitions
│
├── lang/                    # Language files
│   ├── en/intebchat.php     # English strings
│   └── es/intebchat.php     # Spanish strings
│
├── pix/                     # Icons and images
│   ├── icon.svg             # Plugin icon
│   └── mascots/             # Animated mascot SVGs
│
├── styles/                  # CSS styles
│   └── styles.css           # Main stylesheet
│
├── templates/               # Mustache templates
│   ├── chat.mustache        # Chat interface
│   └── analytics.mustache   # Analytics dashboard
│
└── docs/                    # Documentation
    ├── intebchat_user_guide.html
    └── TECHNICAL_DOCS.md
```

---

## 3. Database Schema

### 3.1 Entity Relationship Diagram

```
┌──────────────────────────┐
│       intebchat          │
├──────────────────────────┤
│ id (PK)                  │
│ course (FK → course)     │
│ name                     │
│ intro                    │
│ introformat              │
│ showlabels               │
│ apitype                  │
│ sourceoftruth            │
│ prompt                   │
│ instructions             │
│ assistantname            │
│ apikey                   │
│ model                    │
│ temperature              │
│ maxlength                │
│ topp                     │
│ frequency                │
│ presence                 │
│ assistant                │
│ persistconvo             │
│ enableaudio              │
│ audiomode                │
│ voice                    │
│ mascot                   │
│ timecreated              │
│ timemodified             │
└───────────┬──────────────┘
            │ 1
            │
            │ *
┌───────────▼──────────────┐
│ intebchat_conversations  │
├──────────────────────────┤
│ id (PK)                  │
│ instanceid (FK)          │──────┐
│ userid (FK → user)       │      │
│ title                    │      │
│ preview                  │      │
│ threadid                 │      │
│ messagecount             │      │
│ timecreated              │      │
│ timemodified             │      │
└───────────┬──────────────┘      │
            │ 1                   │
            │                     │
            │ *                   │
┌───────────▼──────────────┐      │
│    intebchat_log         │      │
├──────────────────────────┤      │
│ id (PK)                  │      │
│ instanceid (FK)          │◄─────┘
│ userid (FK → user)       │
│ conversationid (FK)      │
│ usermessage              │
│ airesponse               │
│ prompttokens             │
│ completiontokens         │
│ totaltokens              │
│ contextid                │
│ timecreated              │
└──────────────────────────┘

┌──────────────────────────┐
│  intebchat_token_usage   │
├──────────────────────────┤
│ id (PK)                  │
│ userid (FK → user)       │
│ tokensused               │
│ periodstart              │
│ periodtype               │
│ timecreated              │
│ timemodified             │
└──────────────────────────┘
```

### 3.2 Table Descriptions

#### `intebchat` (Main Instance Table)

Stores activity module instances.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(10) | Primary key |
| `course` | INT(10) | FK to course table |
| `name` | VARCHAR(255) | Activity name |
| `intro` | TEXT | Activity description |
| `introformat` | INT(4) | Format of intro field |
| `showlabels` | INT(1) | Show name labels in chat |
| `apitype` | VARCHAR(20) | API type: 'chat' or 'assistant' |
| `sourceoftruth` | TEXT | Factual information for AI |
| `prompt` | TEXT | Custom system prompt |
| `instructions` | TEXT | Assistant instructions |
| `assistantname` | VARCHAR(255) | Display name for assistant |
| `apikey` | VARCHAR(255) | Instance-level API key (encrypted) |
| `model` | VARCHAR(255) | OpenAI model to use |
| `temperature` | DECIMAL(10,2) | Response randomness (0-2) |
| `maxlength` | INT(10) | Max response tokens |
| `topp` | DECIMAL(10,2) | Nucleus sampling |
| `frequency` | DECIMAL(10,2) | Frequency penalty |
| `presence` | DECIMAL(10,2) | Presence penalty |
| `assistant` | VARCHAR(255) | OpenAI Assistant ID |
| `persistconvo` | INT(1) | Persist conversations |
| `enableaudio` | INT(1) | Enable audio features |
| `audiomode` | VARCHAR(30) | Audio mode: text/audio/both/conversacional |
| `voice` | VARCHAR(20) | TTS voice |
| `mascot` | VARCHAR(50) | Mascot character |
| `timecreated` | INT(10) | Creation timestamp |
| `timemodified` | INT(10) | Last modified timestamp |

#### `intebchat_conversations` (Conversation Tracking)

Stores conversation metadata.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(10) | Primary key |
| `instanceid` | INT(10) | FK to intebchat |
| `userid` | INT(10) | FK to user |
| `title` | VARCHAR(255) | Conversation title |
| `preview` | VARCHAR(255) | Last message preview |
| `threadid` | VARCHAR(255) | OpenAI thread ID (Assistant API) |
| `messagecount` | INT(10) | Number of messages |
| `timecreated` | INT(10) | Creation timestamp |
| `timemodified` | INT(10) | Last activity timestamp |

#### `intebchat_log` (Message Log)

Stores all chat messages.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(10) | Primary key |
| `instanceid` | INT(10) | FK to intebchat |
| `userid` | INT(10) | FK to user |
| `conversationid` | INT(10) | FK to intebchat_conversations |
| `usermessage` | TEXT | User's message |
| `airesponse` | TEXT | AI's response |
| `prompttokens` | INT(10) | Prompt tokens used |
| `completiontokens` | INT(10) | Completion tokens used |
| `totaltokens` | INT(10) | Total tokens used |
| `contextid` | INT(10) | Moodle context ID |
| `timecreated` | INT(10) | Message timestamp |

#### `intebchat_token_usage` (Token Limits)

Tracks token usage per user for rate limiting.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT(10) | Primary key |
| `userid` | INT(10) | FK to user |
| `tokensused` | INT(10) | Tokens consumed |
| `periodstart` | INT(10) | Period start timestamp |
| `periodtype` | VARCHAR(10) | Period: hour/day/week/month |
| `timecreated` | INT(10) | Record creation |
| `timemodified` | INT(10) | Last update |

### 3.3 Indexes

```sql
-- intebchat
INDEX apitype (apitype)

-- intebchat_log
INDEX timecreated (timecreated)
INDEX user-time (userid, timecreated)
INDEX instance-user-time (instanceid, userid, timecreated)
INDEX conversation-time (conversationid, timecreated)

-- intebchat_conversations
INDEX instance-user (instanceid, userid)
INDEX timemodified (timemodified)

-- intebchat_token_usage
UNIQUE INDEX user-period (userid, periodstart, periodtype)
INDEX periodstart (periodstart)
```

---

## 4. APIs and Services

### 4.1 Web Services

Defined in `db/services.php`:

| Function | Description | Type | Capability |
|----------|-------------|------|------------|
| `mod_intebchat_create_conversation` | Create new conversation | write | view |
| `mod_intebchat_load_conversation` | Load conversation messages | read | view |
| `mod_intebchat_clear_conversation` | Delete conversation | write | view |
| `mod_intebchat_update_conversation_title` | Update title | write | view |
| `mod_intebchat_get_assistants` | List OpenAI assistants | read | addinstance |
| `mod_intebchat_save_realtime_message` | Save realtime message | write | view |
| `mod_intebchat_get_site_report` | Site-wide report | read | viewsitereport |
| `mod_intebchat_get_course_report` | Course report | read | viewanalytics |

### 4.2 External API Endpoints

Located in `/api/`:

#### `completion.php`
- **Purpose:** Non-streaming chat completion
- **Method:** POST
- **Parameters:**
  - `message` (string): User message
  - `history` (array): Previous messages
  - `instanceid` (int): Activity instance ID
  - `conversationid` (int): Conversation ID

#### `completion_stream.php`
- **Purpose:** Streaming chat completion (SSE)
- **Method:** POST
- **Headers:** `Content-Type: text/event-stream`
- **Parameters:** Same as completion.php

#### `thread.php`
- **Purpose:** Assistant API thread management
- **Method:** POST
- **Parameters:**
  - `message` (string): User message
  - `threadid` (string, optional): Existing thread ID
  - `instanceid` (int): Activity instance ID
  - `conversationid` (int): Conversation ID

#### `realtime_token.php`
- **Purpose:** Generate ephemeral token for WebRTC
- **Method:** POST
- **Returns:** Ephemeral token for OpenAI Realtime API

### 4.3 OpenAI Integration

The plugin communicates with these OpenAI endpoints:

| Endpoint | Purpose |
|----------|---------|
| `https://api.openai.com/v1/chat/completions` | Chat API |
| `https://api.openai.com/v1/assistants` | List assistants |
| `https://api.openai.com/v1/threads` | Create/manage threads |
| `https://api.openai.com/v1/threads/{id}/runs` | Run assistant |
| `https://api.openai.com/v1/audio/transcriptions` | Whisper transcription |
| `https://api.openai.com/v1/audio/speech` | Text-to-speech |
| `https://api.openai.com/v1/realtime/sessions` | Realtime WebRTC |

---

## 5. Hooks and Events

### 5.1 Moodle Callbacks (lib.php)

| Callback | Description |
|----------|-------------|
| `intebchat_supports($feature)` | Declares supported features |
| `intebchat_add_instance($data, $mform)` | Creates new instance |
| `intebchat_update_instance($data, $mform)` | Updates instance |
| `intebchat_delete_instance($id)` | Deletes instance and data |
| `intebchat_user_outline($course, $user, $mod, $instance)` | User activity outline |
| `intebchat_user_complete($course, $user, $mod, $instance)` | Detailed user activity |
| `intebchat_extend_navigation_course($nav, $course, $context)` | Add course nav items |
| `intebchat_pluginfile(...)` | Serve plugin files |

### 5.2 Supported Features

```php
FEATURE_GROUPS                    => true
FEATURE_GROUPINGS                 => true
FEATURE_MOD_INTRO                 => true
FEATURE_COMPLETION_TRACKS_VIEWS   => true
FEATURE_COMPLETION_HAS_RULES      => false
FEATURE_GRADE_HAS_GRADE           => false
FEATURE_BACKUP_MOODLE2            => true
FEATURE_SHOW_DESCRIPTION          => true
FEATURE_MODEDIT_DEFAULT_COMPLETION => true
```

### 5.3 Events

#### `course_module_viewed`

**Location:** `classes/event/course_module_viewed.php`

Triggered when a user views the chat activity.

```php
$event = \mod_intebchat\event\course_module_viewed::create([
    'context' => $context,
    'objectid' => $intebchat->id
]);
$event->trigger();
```

#### `course_module_instance_list_viewed`

**Location:** `classes/event/course_module_instance_list_viewed.php`

Triggered when viewing the list of all instances.

### 5.4 Scheduled Tasks

#### `cleanup_conversations`

**Location:** `classes/task/cleanup_conversations.php`

**Schedule:** Daily at 3:00 AM

**Purpose:**
- Delete inactive conversations older than retention period
- Clean up orphaned token usage records

---

## 6. Core Classes

### 6.1 `mod_intebchat\external`

**Location:** `classes/external.php`

Web service methods for AJAX communication.

```php
class external extends \external_api {
    // Conversation management
    public static function create_conversation($instanceid);
    public static function load_conversation($conversationid, $instanceid);
    public static function clear_conversation($conversationid);
    public static function update_conversation_title($conversationid, $title);

    // Assistant management
    public static function get_assistants($apikey);

    // Realtime
    public static function save_realtime_message($instanceid, $conversationid, $role, $message);

    // Reports
    public static function get_site_report($view, $period, $courseid, $userid, $page, $perpage);
    public static function get_course_report($courseid, $period, $instanceid, $page, $perpage);
}
```

### 6.2 `mod_intebchat\completion\chat`

**Location:** `classes/completion/chat.php`

Handles Chat API completions.

**Key Methods:**
- `get_completion($messages, $instance)` - Get AI response
- `stream_completion($messages, $instance)` - Stream response

### 6.3 `mod_intebchat\completion\assistant`

**Location:** `classes/completion/assistant.php`

Handles Assistant API interactions.

**Key Methods:**
- `create_thread()` - Create new thread
- `add_message($threadid, $message)` - Add message to thread
- `run_assistant($threadid, $assistantid)` - Execute assistant

### 6.4 `mod_intebchat\audio`

**Location:** `classes/audio.php`

Handles audio processing.

**Key Methods:**
- `transcribe($audiodata)` - Transcribe audio using Whisper
- `synthesize($text, $voice)` - Generate speech from text

### 6.5 `mod_intebchat\ratelimiter`

**Location:** `classes/ratelimiter.php`

Request rate limiting.

**Key Methods:**
- `check($userid, $ip)` - Check if request allowed
- `increment($userid, $ip)` - Record request

### 6.6 `mod_intebchat\privacy\provider`

**Location:** `classes/privacy/provider.php`

GDPR compliance provider.

**Implements:**
- `\core_privacy\local\metadata\provider`
- `\core_privacy\local\request\plugin\provider`

---

## 7. Backup and Restore

### 7.1 Backup Structure

**Location:** `backup/moodle2/backup_intebchat_stepslib.php`

Backed up data:
- Instance settings
- Chat logs (if user data included)
- Conversations (if user data included)

```php
protected function define_structure() {
    $intebchat = new backup_nested_element('intebchat', ['id'], [
        'name', 'intro', 'introformat', 'showlabels',
        'sourceoftruth', 'prompt', 'instructions',
        'assistantname', 'model', 'temperature', ...
    ]);

    if ($userinfo) {
        // Include logs
        $log->set_source_table('intebchat_log', ...);
    }
}
```

### 7.2 Restore Process

**Location:** `backup/moodle2/restore_intebchat_stepslib.php`

- Restores instance configuration
- Optionally restores user conversations and logs
- Maps user IDs to new course enrollment

---

## 8. Caching Strategy

### 8.1 Cache Definitions

**Location:** `db/caches.php`

```php
$definitions = [
    'config' => [
        'mode' => cache_store::MODE_APPLICATION,
        'simplekeys' => true,
        'simpledata' => true,
    ],
];
```

### 8.2 Configuration Cache

The plugin configuration is cached to reduce database queries:

```php
function intebchat_get_config($forcereload = false) {
    static $config = null;

    if ($config !== null && !$forcereload) {
        return $config;
    }

    $cache = \cache::make('mod_intebchat', 'config');
    $cached = $cache->get('plugin_config');

    if ($cached !== false) {
        return $cached;
    }

    // Load from database and cache
    $config = get_config('mod_intebchat');
    $cache->set('plugin_config', $config);

    return $config;
}
```

---

## 9. Security Considerations

### 9.1 API Key Encryption

API keys are encrypted before storage:

```php
function intebchat_encrypt_apikey($apikey) {
    // Use Moodle 4.0+ core encryption if available
    if (class_exists('\core\encryption')) {
        return \core\encryption::encrypt($apikey);
    }

    // Fallback to OpenSSL with Moodle's secret salt
    $salt = $CFG->secretsaltmain ?? $CFG->passwordsaltmain;
    $key = hash('sha256', $salt, true);
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($apikey, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    return 'enc:' . base64_encode($iv . $encrypted);
}
```

### 9.2 Input Sanitization

User messages are sanitized to prevent prompt injection:

```php
function intebchat_sanitize_input($input, $maxlength = 10000) {
    // Enforce length limit
    $input = substr($input, 0, $maxlength);

    // Remove control characters
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

    // Block injection patterns
    $patterns = [
        '/\bignore\s+(all\s+)?(previous|above|prior)\s+instructions?\b/i',
        '/\bshow\s+(your|the)\s+(system\s+)?(prompt|instructions?)\b/i',
        '/\bjailbreak\b/i',
        // ... more patterns
    ];

    foreach ($patterns as $pattern) {
        $input = preg_replace($pattern, '[input sanitized]', $input);
    }

    return trim($input);
}
```

### 9.3 Capability Checks

All actions verify user capabilities:

| Action | Required Capability |
|--------|---------------------|
| View chat | `mod/intebchat:view` |
| Add instance | `mod/intebchat:addinstance` |
| View own conversations | `mod/intebchat:viewownconversations` |
| View student conversations | `mod/intebchat:viewstudentconversations` |
| View all conversations | `mod/intebchat:viewallconversations` |
| View analytics | `mod/intebchat:viewanalytics` |
| View site report | `mod/intebchat:viewsitereport` |

### 9.4 Rate Limiting

Prevents API abuse:

```php
// Per-user limit (default: 60/minute)
// Per-IP limit (default: 30/minute)
```

---

## 10. Extensibility

### 10.1 Adding New Models

Edit `intebchat_get_models()` in `lib.php`:

```php
function intebchat_get_models() {
    return [
        "default" => "gpt-4.1",
        "models" => [
            'gpt-4.1' => 'GPT-4.1 (Recommended)',
            'your-new-model' => 'Your New Model Description',
            // ...
        ]
    ];
}
```

### 10.2 Adding New Voices

Edit the `$voices` array in `mod_form.php` and `settings.php`:

```php
$voices = [
    'alloy' => 'Alloy (Neutral)',
    'your-voice' => 'Your Voice Description',
    // ...
];
```

### 10.3 Adding New Mascots

1. Add SVG file to `pix/mascots/your-mascot.svg`
2. Add language strings:
   ```php
   $string['mascot_yourmascot'] = 'Your Mascot Name';
   ```
3. Update `mod_form.php`:
   ```php
   $mascots = [
       'yourmascot' => get_string('mascot_yourmascot', 'mod_intebchat'),
   ];
   ```

### 10.4 Custom Event Observers

Create an observer in your plugin:

```php
// db/events.php
$observers = [
    [
        'eventname' => '\mod_intebchat\event\course_module_viewed',
        'callback' => '\local_yourplugin\observer::handle_chat_view',
    ],
];
```

---

## 11. Developer Notes

### 11.1 Code Conventions

- Follow Moodle coding guidelines
- Use namespaces: `mod_intebchat\*`
- PHPDoc all public methods
- Use language strings for all UI text

### 11.2 JavaScript (AMD)

Modules are in `amd/src/`:

```javascript
// amd/src/lib.js
define(['jquery', 'core/ajax', 'core/notification'], function($, Ajax, Notification) {
    return {
        init: function(config) {
            // Initialize chat
        }
    };
});
```

Build minified versions:
```bash
npx grunt amd
```

### 11.3 Testing

Run unit tests:
```bash
vendor/bin/phpunit --testsuite mod_intebchat_testsuite
```

### 11.4 Debugging

Enable debugging in `config.php`:
```php
@error_reporting(E_ALL | E_STRICT);
@ini_set('display_errors', '1');
$CFG->debug = (E_ALL | E_STRICT);
$CFG->debugdisplay = 1;
```

### 11.5 Upgrading

When modifying database schema:

1. Update `db/install.xml`
2. Add upgrade steps in `db/upgrade.php`:
   ```php
   function xmldb_intebchat_upgrade($oldversion) {
       if ($oldversion < 2025010100) {
           // Add new field or table
           upgrade_mod_savepoint(true, 2025010100, 'intebchat');
       }
       return true;
   }
   ```
3. Increment version in `version.php`

### 11.6 Contributing

1. Fork the repository
2. Create feature branch
3. Follow coding standards
4. Write tests
5. Submit pull request

---

## Appendix A: Configuration Reference

### Global Settings (`settings.php`)

| Setting | Key | Type | Default |
|---------|-----|------|---------|
| API Key | `apikey` | text | - |
| API Type | `type` | select | chat |
| Restrict to authenticated | `restrictusage` | checkbox | 1 |
| Enable logging | `logging` | checkbox | 0 |
| Allow instance settings | `allowinstancesettings` | checkbox | 0 |
| Enable audio | `enableaudio` | checkbox | 0 |
| Default voice | `voice` | select | alloy |
| Enable token limit | `enabletokenlimit` | checkbox | 0 |
| Max tokens per user | `maxtokensperuser` | int | 10000 |
| Token limit period | `tokenlimitperiod` | select | day |
| Assistant name | `assistantname` | text | Assistant |
| Model | `model` | select | gpt-4.1 |
| Temperature | `temperature` | float | 0.5 |
| Max length | `maxlength` | int | 500 |
| Top P | `topp` | float | 1 |
| Frequency penalty | `frequency` | float | 1 |
| Presence penalty | `presence` | float | 1 |
| Enable retention | `enableretention` | checkbox | 0 |
| Retention days | `retentiondays` | int | 30 |

---

## Appendix B: Constants

Defined in `lib.php`:

```php
INTEBCHAT_MAX_INPUT_LENGTH      = 10000
INTEBCHAT_MAX_TITLE_LENGTH      = 255
INTEBCHAT_MAX_PREVIEW_LENGTH    = 255
INTEBCHAT_DEFAULT_TOKEN_LIMIT   = 10000
INTEBCHAT_CLEANUP_PROBABILITY   = 100
INTEBCHAT_DEFAULT_MODEL         = 'gpt-4.1'
INTEBCHAT_DEFAULT_TEMPERATURE   = 0.7
INTEBCHAT_DEFAULT_MAX_TOKENS    = 1024
INTEBCHAT_API_TIMEOUT           = 120
INTEBCHAT_API_CONNECT_TIMEOUT   = 10
INTEBCHAT_PERIOD_HOUR           = 3600
INTEBCHAT_PERIOD_DAY            = 86400
INTEBCHAT_PERIOD_WEEK           = 604800
INTEBCHAT_PERIOD_MONTH          = 2592000
```

---

**Document Version:** 1.0
**Last Updated:** December 2025
**Copyright:** 2025 IngeWeb - All Rights Reserved
