# Educam Bot plugin analysis

This document summarises the current state of the `local_educambot` plugin, focusing on the
architecture that powers the rule-based chatbot, the data it stores and the areas that were
recently improved to make reasoning more flexible.

## High-level architecture

* **Widget rendering** – `local/educambot/lib.php` attaches the widget renderable to the page
  footer. The widget is implemented with a mustache template (`templates/widget.mustache`), a
  companion stylesheet (`styles.css`) and an AMD module (`amd/src/widget.js`) that drives the
  chat interactions.
* **AJAX endpoint** – `service.php` receives questions from the browser. It instantiates the
  reasoning engine, logs the interaction, stores unanswered questions when needed and returns the
  response payload.
* **Rule management** – `manage.php` provides the administration UI backed by the
  `classes/form/entry_form.php` moodleform. It writes to the `local_educambot_rule` table and keeps
  the MUC cache (`db/caches.php`) in sync.
* **Background tasks & privacy** – A scheduled task (`classes/task/cleanup.php`) purges old logs
  according to the retention setting and `classes/privacy/provider.php` implements Moodle's
  privacy API.

### Data model

The plugin stores three entities defined in `db/install.xml`:

| Table | Purpose |
| ----- | ------- |
| `local_educambot_rule` | Knowledge base entries with patterns, synonyms, keywords, role/context restrictions, suggestion flag and timestamps. |
| `local_educambot_log` | Full conversation log including matched rule id, calculated confidence and originating page. |
| `local_educambot_unanswered` | Normalised list of unanswered questions that can be reviewed to expand the knowledge base. |

## Reasoning engine

The engine lives in `classes/bot/engine.php`. It now:

* Normalises text by stripping diacritics, removing punctuation and filtering stopwords in both
  English and Spanish before any comparison.
* Supports pattern wildcards (`*`, `?`), synonym phrases, keyword boosting and context hints in a
  single scoring pipeline.
* Calculates scores using a blend of direct matches, substring checks, similarity ratios
  (`similar_text` + Levenshtein) and token-overlap heuristics.
* Boosts rules when their configured contexts match the current page or appear in the question,
  and when configured keywords are detected.
* Provides dynamic suggestions by ranking the top matches for each answer and combining them with
  proactive suggestions marked in the knowledge base.
* Offers a `preview_rankings()` method so the administration UI can reuse the same heuristics to
  surface relevant entries while searching.

All rule data is cached at application level. The cache is cleared whenever the administrator
creates, edits or deletes a rule. Conversation logging can be toggled in the plugin settings and
unanswered questions are de-duplicated for each user within a one-day window to keep the review
queue actionable.

## Administration experience

* The knowledge base screen shows all entries (or the highest scoring matches when searching) in a
  pageless table with quick links to edit or delete rules. A new search box performs fuzzy matches
  against existing entries by leveraging the engine's ranking method.
* The moodleform supports role restrictions, context hints, proactive suggestions, wildcards,
  keywords and rich-text answers. Help buttons explain the behaviour of each field.
* Settings exposed through `settings.php` allow administrators to toggle logging and adjust the
  retention period for stored conversations.

## Frontend behaviour

* The widget is available on every non-AJAX page and toggles a floating panel. Messages are sent
  via `fetch` to the AJAX endpoint and responses are rendered as HTML inside the conversation log.
* Suggested questions are displayed under the transcript. The list is refreshed after each
  response using the suggestions returned by the engine, which now combine proactive and
  context-aware recommendations.

## Areas for future enhancement

* Build dedicated reports for conversation logs and unanswered questions so administrators can
  filter, export or bulk convert entries into new rules.
* Add support for importing/exporting the knowledge base (CSV/JSON) to speed up large deployments.
* Extend the reasoning engine with optional weight tuning per rule and analytics to highlight
  rules with low confidence or high deflection rates.
* Allow administrators to tag entries or group them by category to improve maintainability in very
  large knowledge bases.
