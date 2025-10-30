# Educam Bot - Intelligent Educational Chatbot for Moodle

**Version:** 2.1.2 (2025103005)
**License:** GNU GPL v3 or later
**Requires:** Moodle 4.0+
**Maintainer:** Educam

---

## 🤖 Overview

Educam Bot is a sophisticated, **100% LOCAL** educational chatbot for Moodle that provides intelligent assistance to students, teachers, and administrators. Unlike other chatbots, Educam Bot operates entirely on your server without requiring external API keys or cloud services.

### Key Features

- 🧠 **Advanced LOCAL NLP** - Sophisticated text processing with TF-IDF, intent detection, and spell correction
- 📚 **200+ Pre-loaded Answers** - Comprehensive knowledge base about Moodle ready to use
- 🎯 **Intent Recognition** - Understands 11 types of user intentions automatically
- ✍️ **Spell Tolerant** - Handles typos and misspellings gracefully
- 🌐 **Bilingual** - Full support for Spanish and English
- 👥 **Role-Aware** - Contextual responses based on user role (student/teacher/admin)
- 💬 **Conversational Memory** - Remembers previous exchanges for follow-up questions
- 📊 **Analytics Ready** - Logs conversations for improvement insights
- 🔒 **Privacy-Compliant** - GDPR-ready with configurable data retention
- ⚡ **Fast & Cached** - Optimized performance with multilevel caching

---

## 🆕 What's New in Version 2.0.0

### Major Enhancements

1. **TF-IDF Relevance Ranking** - Scientific approach to match questions with best answers (+50% precision)
2. **Intent Detection System** - Automatically classifies user questions into 11 intent types
3. **Spell Correction** - "Did you mean...?" suggestions for typos and misspellings
4. **Enhanced NLP Pipeline** - N-grams, improved Spanish stemming (10+ morphological rules), language detection
5. **Comprehensive Knowledge Base** - 200+ pre-configured entries about Moodle in 6 categories
6. **Structured Content** - Rich HTML responses with lists, tips, and clear formatting

See [CHANGELOG.md](CHANGELOG.md) for complete details.

---

## 📋 Requirements

- **Moodle:** 4.0 or higher
- **PHP:** 7.4 or higher
- **Database:** MySQL 5.7+ / MariaDB 10.2+ / PostgreSQL 9.6+
- **Browser:** Modern browsers with JavaScript enabled

---

## 📦 Installation

### Method 1: Via Moodle Plugin Installer (Recommended)

1. Download the latest release ZIP from the repository
2. Log in as administrator
3. Go to **Site administration → Plugins → Install plugins**
4. Upload the ZIP file
5. Click "Install plugin from the ZIP file"
6. Follow the installation wizard

### Method 2: Manual Installation

1. Extract the plugin files
2. Copy the `educambot` folder to `/path/to/moodle/local/`
3. Log in as administrator
4. Visit **Site administration → Notifications**
5. Click "Upgrade Moodle database"

### Post-Installation: Seed Knowledge Base

To populate the 200+ knowledge entries:

**Option A: Via Web Interface**
1. Go to **Site administration → Plugins → Local plugins → Educam Bot**
2. Click "Seed knowledge base"

**Option B: Via CLI**
```bash
cd /path/to/moodle
php local/educambot/cli/seed_knowledge.php
```

**Option C: Via Code**
```php
$stats = \local_educambot\local\setup\comprehensive_seed::seed_all();
echo "Created {$stats['knowledge']} knowledge entries in {$stats['topics']} categories";
```

---

## ⚙️ Configuration

### Basic Setup

1. Go to **Site administration → Plugins → Local plugins → Educam Bot**
2. Configure:
   - **Bot Name:** Customize the chatbot's name (default: "Educam Bot")
   - **Greeting Template:** Personalize the welcome message
   - **Primary Color:** Match your theme colors
   - **Logging:** Enable conversation logging for analytics
   - **History Limit:** Number of previous messages to remember (default: 8)

### Advanced Configuration

**Capabilities:**
- `local/educambot:use` - Use the chatbot
- `local/educambot:manage` - Manage knowledge base and rules

**Color Customization:**
- Primary color (header, buttons)
- Accent color (suggestions)
- Background color (conversation area)
- Text color (bot responses)

---

## 🎓 Usage

### For Students

The chatbot widget appears as a floating button in the bottom-right corner of all Moodle pages.

**Example Questions:**
- "¿Cómo entrego una tarea?"
- "Where can I see my grades?"
- "No puedo acceder al curso"
- "¿Qué es un cuestionario?"

### For Teachers

The bot provides role-specific answers for teaching tasks.

**Example Questions:**
- "¿Cómo creo un curso?"
- "How do I add a quiz?"
- "¿Cómo califico tareas?"
- "No puedo ver el libro de calificaciones"

### For Administrators

Admin-specific guidance for site management.

**Example Questions:**
- "¿Cómo instalo un plugin?"
- "How do I create user roles?"
- "¿Cómo hago un respaldo del sitio?"
- "Error de permisos en curso"

---

## 📚 Knowledge Base Management

### Managing Knowledge Entries

1. Go to **Site administration → Plugins → Local plugins → Educam Bot → Knowledge base**
2. Use the interface to:
   - Add new knowledge articles
   - Edit existing entries
   - Organize by topics
   - Set role restrictions
   - Create relations between entries

### Knowledge Entry Structure

Each entry includes:
- **Title:** Clear, descriptive heading
- **Summary:** One-line description for search results
- **Content:** Rich HTML with detailed information
- **Tags:** Keywords for search optimization
- **Topics:** Category organization
- **Roles:** Optional restriction (student/teacher/admin)
- **Relations:** Links to related entries

### Managing Rules

Rules are pattern-based responses for specific questions.

1. Go to **Site administration → Plugins → Local plugins → Educam Bot → Manage rules**
2. Add/edit rules with:
   - Pattern (main question)
   - Synonyms (alternative phrasings)
   - Keywords (matching terms)
   - Response (HTML answer)
   - Contexts (page-specific)

---

## 🔧 Advanced Features

### Intent Detection

The bot automatically detects 11 intent types:

1. **question** - Information requests
2. **help_request** - Assistance needed
3. **problem_report** - Error or issue reporting
4. **gratitude** - Expressing thanks
5. **greeting** - Hello, hi, buenos días
6. **farewell** - Goodbye, adiós
7. **confirmation** - Yes, si, ok
8. **denial** - No, nunca
9. **frustration** - Expressing frustration
10. **action_request** - Task requests (create, delete, modify)
11. **unknown** - Unclear intent

### Spell Correction

Handles common misspellings:
- "cursos" → "curso"
- "actividd" → "actividad"
- "quizz" → "quiz"
- And 50+ more

### TF-IDF Ranking

Scientific relevance calculation:
- Term frequency in document
- Inverse document frequency across corpus
- Cosine similarity for matching
- Confidence scores for all results

### Session Memory

Remembers conversation context:
- Last 8 exchanges (configurable)
- Follow-up question detection
- Contextual boosting
- Keywords aggregation

---

## 📊 Analytics & Monitoring

### Conversation Logs

View all conversations in:
**Site administration → Plugins → Local plugins → Educam Bot → Conversation logs**

Includes:
- User and session ID
- Question and response
- Confidence score
- Matched rule/knowledge
- Timestamp

### Unanswered Questions

Identify gaps in knowledge base:
**Site administration → Plugins → Local plugins → Educam Bot → Unanswered questions**

Use this data to:
- Add missing knowledge entries
- Create new rules
- Improve existing content

---

## 🛠️ Developer Guide

### Architecture

```
local_educambot/
├── classes/
│   ├── bot/              # Core engine and reasoners
│   ├── nlp/              # NLP components (TF-IDF, intent, spell, pipeline)
│   ├── local/            # Knowledge repository, context, utilities
│   ├── matching/         # Matching algorithms
│   ├── inference/        # Decision engine
│   ├── context/          # Session memory
│   └── output/           # Renderers and UI
├── db/                   # Database schema and upgrade
├── lang/                 # Language strings (ES/EN)
├── templates/            # Mustache templates
├── amd/src/              # JavaScript source
└── styles.css            # Widget styles
```

### Extending the Chatbot

**Add Custom NLP Component:**
```php
namespace local_educambot\nlp;

class my_analyzer {
    public function analyze(string $text): array {
        // Your analysis logic
        return $results;
    }
}
```

**Add Custom Intent:**
```php
// In classes/nlp/intent_detector.php
public const INTENT_CUSTOM = 'custom_intent';

// Add patterns in initialize_intent_patterns()
self::INTENT_CUSTOM => [
    ['keywords' => ['custom', 'keywords']],
],
```

**Add Custom Knowledge Seed:**
```php
// In classes/local/setup/my_seed.php
class my_seed {
    public static function seed_custom_category(): array {
        // Add your entries
    }
}
```

### API Usage

**Process a question programmatically:**
```php
$engine = new \local_educambot\bot\engine($userid, $page, $sessionid);
$result = $engine->respond($question);

echo $result['response'];
echo "Confidence: " . $result['confidence'];
```

**Search knowledge base:**
```php
$repo = new \local_educambot\local\knowledge_repository();
$hits = $repo->search($question, $courseid, $page, $roles, $limit);

foreach ($hits as $hit) {
    echo $hit['record']->title;
    echo "Score: " . $hit['score'];
}
```

**Detect intent:**
```php
$detector = new \local_educambot\nlp\intent_detector();
$result = $detector->detect($text);

echo "Intent: " . $result['intent'];
echo "Confidence: " . $result['confidence'];
print_r($result['slots']);
```

**Correct spelling:**
```php
$corrector = new \local_educambot\nlp\spell_corrector();
$result = $corrector->correct_text($text);

if ($result['has_errors']) {
    echo "Did you mean: " . $result['corrected'];
    print_r($result['corrections']);
}
```

---

## 🧪 Testing

### Manual Testing

1. Open your Moodle site as a student
2. Click the chatbot button (bottom-right)
3. Try various questions:
   - Basic questions about Moodle
   - Questions with typos
   - Follow-up questions
   - Different intent types

### Unit Testing (Coming Soon)

```bash
cd /path/to/moodle
vendor/bin/phpunit --testsuite local_educambot_testsuite
```

---

## 🐛 Troubleshooting

### Chatbot doesn't appear

- Check capability `local/educambot:use` for your role
- Verify JavaScript is enabled in browser
- Clear Moodle caches
- Check browser console for errors

### No answers returned

- Verify knowledge base is populated (run seed script)
- Check conversation logs for insights
- Review question formatting
- Ensure activities are enabled

### Poor match quality

- Add more knowledge entries for the topic
- Create specific rules for common questions
- Review and optimize tags/keywords
- Check unanswered questions log

### Performance issues

- Enable all caching (Application, Session, Request)
- Add database indexes (automatically created)
- Consider MySQL FULLTEXT or PostgreSQL GIN indexes
- Increase PHP memory limit if needed

---

## 📖 Additional Resources

- **Documentation:** [ANALYSIS_v2025103000.md](ANALYSIS_v2025103000.md)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)
- **Moodle Docs:** https://docs.moodle.org
- **License:** [GNU GPL v3](LICENSE)

---

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Write/update tests
5. Submit a pull request

Please follow Moodle coding standards and include PHPDoc comments.

---

## 📄 License

This program is free software: you can redistribute it and/or modify it under the terms of the
GNU General Public License as published by the Free Software Foundation, either version 3 of
the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program.
If not, see <http://www.gnu.org/licenses/>.

---

## 🙏 Credits

**Educam Bot** is developed and maintained by Educam.

Special thanks to the Moodle community for their continuous support and contributions.

---

## 📞 Support

For support, please:
1. Check the [Troubleshooting](#-troubleshooting) section
2. Review [ANALYSIS_v2025103000.md](ANALYSIS_v2025103000.md) for technical details
3. Check the conversation logs for insights
4. Contact your Moodle administrator

---

**Made with ❤️ for the Moodle education community**
