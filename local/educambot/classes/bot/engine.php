<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Bot engine - handles question processing and response generation.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_educambot\bot;

defined('MOODLE_INTERNAL') || die();

/**
 * Bot engine class.
 */
class engine {

    /** @var int Course ID for context filtering. */
    protected $courseid;

    /** @var int User ID for role filtering. */
    protected $userid;

    /**
     * Constructor.
     *
     * @param int $courseid Course ID for context.
     * @param int $userid User ID for role filtering.
     */
    public function __construct(int $courseid = SITEID, int $userid = 0) {
        global $USER;
        $this->courseid = $courseid;
        $this->userid = $userid ?: $USER->id;
    }

    /**
     * Process a question and return a response.
     *
     * @param string $question The user's question
     * @return array Response data with keys: response, ruleid, confidence
     */
    public function respond($question) {
        global $DB;

        // Normalize the question.
        $normalized = $this->normalize_text($question);

        // Get user's current language.
        $userlang = current_language();
        $userlang = substr($userlang, 0, 2); // Get base language (es, en, fr, etc.).

        // Get user's role ARCHETYPES in current context (not shortnames!).
        $userarchetypes = $this->get_user_archetypes();

        // Build SQL to filter rules by language preference.
        // Prefer rules in user's language, fallback to 'es' (default).
        $sql = "SELECT * FROM {local_educambot_rule}
                WHERE enabled = 1
                ORDER BY
                    CASE WHEN lang = :userlang THEN 0
                         WHEN lang = 'es' THEN 1
                         ELSE 2 END,
                    id ASC";
        $rules = $DB->get_records_sql($sql, ['userlang' => $userlang]);

        // Filter rules by archetype and course.
        $filteredrules = $this->filter_rules_by_context($rules, $userarchetypes);

        $best_match = null;
        $best_score = 0;

        foreach ($filteredrules as $rule) {
            $score = $this->calculate_match_score($normalized, $rule);

            if ($score > $best_score) {
                $best_score = $score;
                $best_match = $rule;
            }
        }

        // Return response if we have a good match (score > 0).
        if ($best_match && $best_score > 0) {
            return [
                'response' => $best_match->response,
                'ruleid' => $best_match->id,
                'confidence' => min(1.0, $best_score / 100), // Normalize to 0-1.
            ];
        }

        // No match found.
        return [
            'response' => null,
            'ruleid' => null,
            'confidence' => 0,
        ];
    }

    /**
     * Get user's role ARCHETYPES in current context.
     *
     * Important: This returns archetypes (student, teacher, editingteacher, manager, etc.)
     * NOT role shortnames which are customizable by admins.
     *
     * Moodle archetypes: manager, coursecreator, editingteacher, teacher, student, guest, user
     *
     * @return array Array of role archetypes.
     */
    protected function get_user_archetypes(): array {
        global $DB;

        $archetypes = [];

        // Check for site administrator first (special case).
        if (is_siteadmin($this->userid)) {
            $archetypes[] = 'manager';
        }

        try {
            $syscontext = \context_system::instance();

            // Get course context if not site.
            if ($this->courseid > SITEID) {
                $coursecontext = \context_course::instance($this->courseid, IGNORE_MISSING);
                if ($coursecontext) {
                    $roles = get_user_roles($coursecontext, $this->userid, true);
                    foreach ($roles as $role) {
                        $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                        if ($rolerecord && !empty($rolerecord->archetype)) {
                            $archetypes[] = $rolerecord->archetype;
                        }
                    }
                }
            }

            // Also check system-level roles.
            $sysroles = get_user_roles($syscontext, $this->userid, true);
            foreach ($sysroles as $role) {
                $rolerecord = $DB->get_record('role', ['id' => $role->roleid]);
                if ($rolerecord && !empty($rolerecord->archetype)) {
                    $archetypes[] = $rolerecord->archetype;
                }
            }
        } catch (\Exception $e) {
            // If context doesn't exist, continue with what we have.
        }

        // Check if guest user.
        if (isguestuser($this->userid)) {
            $archetypes[] = 'guest';
        }

        // Add 'user' for authenticated users without specific archetypes.
        if (empty($archetypes) && isloggedin() && !isguestuser($this->userid)) {
            $archetypes[] = 'user';
        }

        // Remove duplicates and return.
        return array_unique($archetypes);
    }

    /**
     * Filter rules by archetype and course restrictions.
     *
     * The 'roles' field in rules contains archetypes (student, teacher, editingteacher,
     * manager, coursecreator, guest, user) - NOT role shortnames.
     *
     * @param array $rules Array of rule records.
     * @param array $userarchetypes Array of user's role archetypes.
     * @return array Filtered rules.
     */
    protected function filter_rules_by_context(array $rules, array $userarchetypes): array {
        $autolang = get_config('local_educambot', 'autolang');
        $userlang = current_language();
        $userlang = substr($userlang, 0, 2);

        $filtered = [];
        $seenpatterns = [];

        foreach ($rules as $rule) {
            // Check archetype filter.
            // The 'roles' field contains archetypes like: teacher,editingteacher
            if (!empty($rule->roles)) {
                $rulearchetypes = array_map('trim', explode(',', $rule->roles));
                if (!array_intersect($userarchetypes, $rulearchetypes)) {
                    continue; // User doesn't have required archetype.
                }
            }

            // Check course filter.
            if (!empty($rule->courses)) {
                $rulecourses = array_map('trim', explode(',', $rule->courses));
                if (!in_array($this->courseid, $rulecourses)) {
                    continue; // Not in a valid course.
                }
            }

            // If auto-lang enabled, prefer rules in user's language.
            // Track patterns to avoid duplicates (prefer user's language).
            if ($autolang) {
                $patternkey = $rule->langparent ?: $rule->id;

                if (!isset($seenpatterns[$patternkey])) {
                    $seenpatterns[$patternkey] = $rule;
                } else {
                    // Replace if current rule is in user's language.
                    $existingrule = $seenpatterns[$patternkey];
                    if ($rule->lang === $userlang && $existingrule->lang !== $userlang) {
                        $seenpatterns[$patternkey] = $rule;
                    }
                }
            } else {
                $filtered[$rule->id] = $rule;
            }
        }

        return $autolang ? array_values($seenpatterns) : $filtered;
    }

    /**
     * Normalize text for matching.
     *
     * @param string $text Text to normalize
     * @return string Normalized text
     */
private function normalize_text($text) {
    // Convert to lowercase - use PHP's mb_strtolower for better UTF-8 support
    $text = mb_strtolower($text, 'UTF-8');

    // Remove extra whitespace.
    $text = trim(preg_replace('/\s+/', ' ', $text));

    // Remove punctuation.
    $text = preg_replace('/[^\w\s]/u', '', $text);

    return $text;
}

    /**
     * Calculate match score between question and rule.
     *
     * @param string $question Normalized question
     * @param object $rule Rule record from database
     * @return int Match score (higher is better)
     */
    private function calculate_match_score($question, $rule) {
        $score = 0;

        // Normalize pattern.
        $pattern = $this->normalize_text($rule->pattern);

        // Check for exact match (highest score).
        if ($pattern === $question) {
            return 100;
        }

        // Check if pattern is contained in question.
        if (strpos($question, $pattern) !== false) {
            $score += 50;
        }

        // Check if question is contained in pattern.
        if (strpos($pattern, $question) !== false) {
            $score += 40;
        }

        // Split into words for keyword matching.
        $question_words = explode(' ', $question);
        $pattern_words = explode(' ', $pattern);

        // Calculate word overlap.
        $common_words = array_intersect($question_words, $pattern_words);
        if (count($pattern_words) > 0) {
            $word_score = (count($common_words) / count($pattern_words)) * 30;
            $score += $word_score;
        }

        // Check keywords if defined.
        if (!empty($rule->keywords)) {
            $keywords = array_filter(array_map('trim', explode("\n", $rule->keywords)));
            foreach ($keywords as $keyword) {
                $keyword = $this->normalize_text($keyword);
                if (!empty($keyword) && strpos($question, $keyword) !== false) {
                    $score += 20;
                }
            }
        }

        return (int)$score;
    }
}
