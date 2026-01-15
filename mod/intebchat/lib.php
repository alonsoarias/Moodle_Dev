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
 * Library of interface functions and constants.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// ============================================================================
// PLUGIN CONSTANTS
// ============================================================================

/**
 * Maximum length for user input messages.
 */
define('INTEBCHAT_MAX_INPUT_LENGTH', 10000);

/**
 * Maximum length for conversation titles.
 */
define('INTEBCHAT_MAX_TITLE_LENGTH', 255);

/**
 * Maximum length for conversation previews.
 */
define('INTEBCHAT_MAX_PREVIEW_LENGTH', 255);

/**
 * Default token limit per user per period.
 */
define('INTEBCHAT_DEFAULT_TOKEN_LIMIT', 10000);

/**
 * Probability of running cleanup tasks (1 in N requests).
 */
define('INTEBCHAT_CLEANUP_PROBABILITY', 100);

/**
 * Default model for API calls.
 */
define('INTEBCHAT_DEFAULT_MODEL', 'gpt-4.1');

/**
 * Default temperature for API calls.
 */
define('INTEBCHAT_DEFAULT_TEMPERATURE', 0.7);

/**
 * Default max tokens for API responses.
 */
define('INTEBCHAT_DEFAULT_MAX_TOKENS', 1024);

/**
 * API timeout in seconds.
 */
define('INTEBCHAT_API_TIMEOUT', 120);

/**
 * API connection timeout in seconds.
 */
define('INTEBCHAT_API_CONNECT_TIMEOUT', 10);

/**
 * Period durations in seconds.
 */
define('INTEBCHAT_PERIOD_HOUR', 3600);
define('INTEBCHAT_PERIOD_DAY', 86400);
define('INTEBCHAT_PERIOD_WEEK', 604800);
define('INTEBCHAT_PERIOD_MONTH', 2592000);

// ============================================================================
// CORE FUNCTIONS
// ============================================================================

/**
 * Get cached plugin configuration.
 *
 * This function caches the configuration to reduce database queries.
 * The cache is stored statically and optionally in Moodle's cache API.
 *
 * @param bool $forcereload Force reload from database
 * @return stdClass The plugin configuration object
 */
function intebchat_get_config($forcereload = false) {
    static $config = null;

    if ($config !== null && !$forcereload) {
        return $config;
    }

    // Try to get from cache first
    try {
        $cache = \cache::make('mod_intebchat', 'config');
        if (!$forcereload) {
            $cached = $cache->get('plugin_config');
            if ($cached !== false) {
                $config = $cached;
                return $config;
            }
        }
    } catch (\Exception $e) {
        // Cache not available, continue without it
        debugging('intebchat config cache not available: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }

    // Load from database
    $config = get_config('mod_intebchat');

    // Store in cache
    if (isset($cache)) {
        try {
            $cache->set('plugin_config', $config);
        } catch (\Exception $e) {
            // Ignore cache write failures
        }
    }

    return $config;
}

/**
 * Clear the plugin configuration cache.
 *
 * Call this when configuration changes are saved.
 */
function intebchat_clear_config_cache() {
    try {
        $cache = \cache::make('mod_intebchat', 'config');
        $cache->delete('plugin_config');
    } catch (\Exception $e) {
        // Ignore if cache not available
    }
}

/**
 * Encrypt an API key for secure storage.
 *
 * Uses Moodle's core encryption if available (4.0+), otherwise falls back to OpenSSL.
 *
 * @param string $apikey The plain text API key
 * @return string The encrypted API key (base64 encoded)
 */
function intebchat_encrypt_apikey($apikey) {
    if (empty($apikey)) {
        return '';
    }

    // Use Moodle's core encryption class if available (Moodle 4.0+).
    if (class_exists('\core\encryption')) {
        try {
            return \core\encryption::encrypt($apikey);
        } catch (\Exception $e) {
            debugging('Encryption failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    // Fallback to OpenSSL encryption using Moodle's secret.
    global $CFG;
    $salt = $CFG->secretsaltmain ?? $CFG->passwordsaltmain ?? null;
    if (empty($salt)) {
        debugging('No secret salt configured in Moodle. API key encryption requires $CFG->secretsaltmain or $CFG->passwordsaltmain.', DEBUG_DEVELOPER);
        throw new \moodle_exception('nosaltconfigured', 'mod_intebchat');
    }
    $key = hash('sha256', $salt, true);
    $iv = openssl_random_pseudo_bytes(16);
    if ($iv === false) {
        throw new \moodle_exception('cryptoerror', 'mod_intebchat');
    }
    $encrypted = openssl_encrypt($apikey, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        debugging('OpenSSL encryption failed: ' . openssl_error_string(), DEBUG_DEVELOPER);
        throw new \moodle_exception('cryptoerror', 'mod_intebchat');
    }

    // Prefix with 'enc:' to identify encrypted values.
    return 'enc:' . base64_encode($iv . $encrypted);
}

/**
 * Decrypt an API key from storage.
 *
 * @param string $encrypted The encrypted API key
 * @return string The decrypted API key
 */
function intebchat_decrypt_apikey($encrypted) {
    if (empty($encrypted)) {
        return '';
    }

    // If not encrypted (legacy or plain text), return as-is.
    if (strpos($encrypted, 'enc:') !== 0 && !preg_match('/^[A-Za-z0-9+\/=]+$/', $encrypted)) {
        // Looks like a plain API key (starts with sk-), return as-is.
        return $encrypted;
    }

    // Try Moodle's core encryption first.
    if (class_exists('\core\encryption') && strpos($encrypted, 'enc:') !== 0) {
        try {
            return \core\encryption::decrypt($encrypted);
        } catch (\Exception $e) {
            // May not be encrypted with core encryption, try fallback.
            debugging('Core decryption failed, trying fallback: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    // Fallback OpenSSL decryption.
    if (strpos($encrypted, 'enc:') === 0) {
        global $CFG;
        $salt = $CFG->secretsaltmain ?? $CFG->passwordsaltmain ?? null;
        if (empty($salt)) {
            debugging('No secret salt configured. Cannot decrypt API key.', DEBUG_DEVELOPER);
            return $encrypted;
        }
        $key = hash('sha256', $salt, true);
        $data = base64_decode(substr($encrypted, 4));

        if ($data === false || strlen($data) < 17) {
            debugging('Invalid encrypted data format', DEBUG_DEVELOPER);
            return $encrypted;
        }

        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

        if ($decrypted !== false) {
            return $decrypted;
        }

        debugging('OpenSSL decryption failed: ' . openssl_error_string(), DEBUG_DEVELOPER);
    }

    // Return original if all decryption attempts fail.
    return $encrypted;
}

/**
 * Check if a string appears to be an encrypted API key.
 *
 * @param string $value The value to check
 * @return bool True if the value appears to be encrypted
 */
function intebchat_is_encrypted($value) {
    if (empty($value)) {
        return false;
    }

    // Check for our custom encryption prefix.
    if (strpos($value, 'enc:') === 0) {
        return true;
    }

    // Check for Moodle core encryption (typically base64 with specific format).
    if (class_exists('\core\encryption') && preg_match('/^[A-Za-z0-9+\/=]{50,}$/', $value)) {
        return true;
    }

    return false;
}

/**
 * Sanitize user input to prevent prompt injection attacks.
 *
 * This function helps protect against common prompt injection patterns
 * while preserving legitimate user input.
 *
 * @param string $input The user input to sanitize
 * @param int $maxlength Maximum allowed length (default 10000)
 * @return string The sanitized input
 */
function intebchat_sanitize_input($input, $maxlength = INTEBCHAT_MAX_INPUT_LENGTH) {
    if (empty($input)) {
        return '';
    }

    // Enforce maximum length.
    if (strlen($input) > $maxlength) {
        $input = substr($input, 0, $maxlength);
    }

    // Remove null bytes and other control characters (except newlines and tabs).
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);

    // Patterns that attempt to override system instructions.
    $injection_patterns = [
        // Direct instruction overrides.
        '/\bignore\s+(all\s+)?(previous|above|prior)\s+instructions?\b/i',
        '/\bdisregard\s+(all\s+)?(previous|above|prior)\s+instructions?\b/i',
        '/\bforget\s+(all\s+)?(previous|above|prior)\s+instructions?\b/i',
        // System prompt extraction attempts.
        '/\b(show|reveal|display|output|print|tell\s+me)\s+(your|the)\s+(system\s+)?(prompt|instructions?)\b/i',
        '/\bwhat\s+(are|is)\s+your\s+(system\s+)?(prompt|instructions?)\b/i',
        // Role playing to bypass restrictions.
        '/\b(you\s+are\s+now|pretend\s+(to\s+be|you\'?re)|act\s+as\s+if\s+you\'?re)\s+(a\s+)?(?!helpful|assistant)(jailbroken|unrestricted|evil|harmful)\b/i',
        // Developer mode / DAN attempts.
        '/\b(developer|dev)\s*mode\b/i',
        '/\bDAN\s*(mode)?\b/i',
        '/\bjailbreak\b/i',
        // Markdown/code injection for prompt leakage.
        '/```system\b/i',
        '/```prompt\b/i',
    ];

    foreach ($injection_patterns as $pattern) {
        if (preg_match($pattern, $input)) {
            // Log the attempt for monitoring.
            debugging('Potential prompt injection attempt detected', DEBUG_DEVELOPER);
            // Replace the matched pattern with a safe placeholder.
            $input = preg_replace($pattern, '[input sanitized]', $input);
        }
    }

    // Normalize excessive whitespace.
    $input = preg_replace('/\s{10,}/', '    ', $input);

    // Trim the result.
    return trim($input);
}

/**
 * Validate that input meets security requirements.
 *
 * @param string $input The input to validate
 * @param int $maxlength Maximum allowed length
 * @return array ['valid' => bool, 'message' => string, 'sanitized' => string]
 */
function intebchat_validate_input($input, $maxlength = INTEBCHAT_MAX_INPUT_LENGTH) {
    $result = [
        'valid' => true,
        'message' => '',
        'sanitized' => ''
    ];

    // Check for empty input.
    if (empty($input)) {
        $result['valid'] = false;
        $result['message'] = get_string('invalidinput', 'mod_intebchat');
        return $result;
    }

    // Check length before sanitization.
    if (strlen($input) > $maxlength) {
        $result['valid'] = false;
        $result['message'] = get_string('inputtoolong', 'mod_intebchat');
        return $result;
    }

    // Sanitize the input.
    $result['sanitized'] = intebchat_sanitize_input($input, $maxlength);

    // Validate the sanitized input is not empty.
    if (empty($result['sanitized'])) {
        $result['valid'] = false;
        $result['message'] = get_string('invalidinput', 'mod_intebchat');
        return $result;
    }

    return $result;
}

/**
 * Returns the information on whether the module supports a feature
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function intebchat_supports($feature)
{
    switch ($feature) {
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_MODEDIT_DEFAULT_COMPLETION:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the intebchat into the database
 *
 * @param stdClass $intebchat Submitted data from the form in mod_form.php
 * @param mod_intebchat_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted intebchat record
 */
function intebchat_add_instance(stdClass $intebchat, mod_intebchat_mod_form $mform = null)
{
    global $DB;

    $intebchat->timecreated = time();
    $intebchat->timemodified = time();

    // Process standard intro fields
    if (!isset($intebchat->intro)) {
        $intebchat->intro = '';
    }
    if (!isset($intebchat->introformat)) {
        $intebchat->introformat = FORMAT_HTML;
    }

    // Always use global API type
    $config = get_config('mod_intebchat');
    $intebchat->apitype = $config->type ?: 'chat';

    // Set defaults for unchecked checkboxes
    if (!isset($intebchat->showlabels)) {
        $intebchat->showlabels = 0;
    }
    if (!isset($intebchat->persistconvo)) {
        $intebchat->persistconvo = 0;
    }

    if (!isset($intebchat->enableaudio)) {
        $intebchat->enableaudio = 0;
        $intebchat->audiomode = 'text';
    }
    if (!isset($intebchat->voice)) {
        $intebchat->voice = get_config('mod_intebchat', 'voice') ?: 'alloy';
    }

    // Clean up fields based on API type
if ($intebchat->apitype === 'assistant') {
    // Clear chat-specific fields
    $intebchat->sourceoftruth = null;
    $intebchat->prompt = null;
    $intebchat->model = null;
    $intebchat->temperature = null;
    $intebchat->maxlength = null;
    $intebchat->topp = null;
    $intebchat->frequency = null;
    $intebchat->presence = null;
    // Keep instructions and assistantname for assistant type
} else {
    // For chat type: clear assistant-specific fields except instructions
    $intebchat->assistant = null;
    $intebchat->persistconvo = 0;
    // Keep instructions and assistantname for all types
    // Clear model-specific fields as they use global settings
    $intebchat->sourceoftruth = null;
    $intebchat->prompt = null;
    $intebchat->model = null;
    $intebchat->temperature = null;
    $intebchat->maxlength = null;
    $intebchat->topp = null;
    $intebchat->frequency = null;
    $intebchat->presence = null;
}

    // Clear any Azure fields that might exist
    $intebchat->resourcename = null;
    $intebchat->deploymentid = null;
    $intebchat->apiversion = null;

    // Insert the record
    $intebchat->id = $DB->insert_record('intebchat', $intebchat);

    return $intebchat->id;
}

/**
 * Updates an instance of the intebchat in the database
 *
 * @param stdClass $intebchat An object from the form in mod_form.php
 * @param mod_intebchat_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function intebchat_update_instance(stdClass $intebchat, mod_intebchat_mod_form $mform = null)
{
    global $DB;

    $intebchat->timemodified = time();
    $intebchat->id = $intebchat->instance;

    // Always use global API type
    $config = get_config('mod_intebchat');
    $intebchat->apitype = $config->type ?: 'chat';

    // Set defaults for unchecked checkboxes
    if (!isset($intebchat->showlabels)) {
        $intebchat->showlabels = 0;
    }
    if (!isset($intebchat->persistconvo)) {
        $intebchat->persistconvo = 0;
    }

    if (!isset($intebchat->enableaudio)) {
        $intebchat->enableaudio = 0;
        $intebchat->audiomode = 'text';
    }
    if (!isset($intebchat->voice)) {
        $intebchat->voice = get_config('mod_intebchat', 'voice') ?: 'alloy';
    }

    // Clean up fields based on API type
if ($intebchat->apitype === 'assistant') {
    // Clear chat-specific fields
    $intebchat->sourceoftruth = null;
    $intebchat->prompt = null;
    $intebchat->model = null;
    $intebchat->temperature = null;
    $intebchat->maxlength = null;
    $intebchat->topp = null;
    $intebchat->frequency = null;
    $intebchat->presence = null;
    // Keep instructions and assistantname for assistant type
} else {
    // For chat type: clear assistant-specific fields except instructions
    $intebchat->assistant = null;
    $intebchat->persistconvo = 0;
    // Keep instructions and assistantname for all types
    // Clear model-specific fields as they use global settings
    $intebchat->sourceoftruth = null;
    $intebchat->prompt = null;
    $intebchat->model = null;
    $intebchat->temperature = null;
    $intebchat->maxlength = null;
    $intebchat->topp = null;
    $intebchat->frequency = null;
    $intebchat->presence = null;
}

    // Clear any Azure fields
    $intebchat->resourcename = null;
    $intebchat->deploymentid = null;
    $intebchat->apiversion = null;

    return $DB->update_record('intebchat', $intebchat);
}

/**
 * Removes an instance of the intebchat from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Fail
 */
function intebchat_delete_instance($id)
{
    global $DB;

    if (!$intebchat = $DB->get_record('intebchat', array('id' => $id))) {
        return false;
    }

    // Delete all conversations
    $conversations = $DB->get_records('intebchat_conversations', array('instanceid' => $intebchat->id));
    foreach ($conversations as $conversation) {
        intebchat_delete_conversation($conversation->id);
    }

    // Delete the instance itself
    $DB->delete_records('intebchat', array('id' => $intebchat->id));

    return true;
}

/**
 * Delete a conversation and all its messages
 *
 * @param int $conversationid Conversation ID
 * @return boolean Success/Fail
 */
function intebchat_delete_conversation($conversationid)
{
    global $DB;

    // Delete all messages in the conversation
    $DB->delete_records('intebchat_log', array('conversationid' => $conversationid));

    // Delete the conversation
    $DB->delete_records('intebchat_conversations', array('id' => $conversationid));

    return true;
}

/**
 * Create a new conversation
 *
 * @param int $instanceid Instance ID
 * @param int $userid User ID
 * @param string $title Conversation title
 * @return int New conversation ID
 */
function intebchat_create_conversation($instanceid, $userid, $title = null)
{
    global $DB;

    if (!$title) {
        $title = get_string('newconversation', 'mod_intebchat');
    }

    $conversation = new stdClass();
    $conversation->instanceid = $instanceid;
    $conversation->userid = $userid;
    $conversation->title = $title;
    $conversation->preview = '';
    $conversation->messagecount = 0;
    $conversation->timecreated = time();
    $conversation->timemodified = time();

    return $DB->insert_record('intebchat_conversations', $conversation);
}

/**
 * Update conversation details
 *
 * @param int $conversationid Conversation ID
 * @param string $title New title
 * @param string $preview Preview text
 * @return boolean Success/Fail
 */
function intebchat_update_conversation($conversationid, $title = null, $preview = null)
{
    global $DB;

    $conversation = $DB->get_record('intebchat_conversations', array('id' => $conversationid));
    if (!$conversation) {
        return false;
    }

    $updated = false;
    
    if ($title !== null && trim($title) !== '') {
        // Sanitizar el título y limitar longitud
        $conversation->title = substr(trim($title), 0, 255);
        $updated = true;
    }
    
    if ($preview !== null) {
        $conversation->preview = substr($preview, 0, 255);
        $updated = true;
    }
    
    if ($updated) {
        $conversation->timemodified = time();
        return $DB->update_record('intebchat_conversations', $conversation);
    }
    
    return true; // No changes needed
}

/**
 * Check if user has exceeded token limit
 *
 * @param int $userid User ID to check
 * @return array ['allowed' => bool, 'used' => int, 'limit' => int, 'reset_time' => int]
 */
function intebchat_check_token_limit($userid)
{
    global $DB;

    $config = get_config('mod_intebchat');

    // If token limit is not enabled, allow unlimited
    if (empty($config->enabletokenlimit)) {
        return ['allowed' => true, 'used' => 0, 'limit' => 0, 'reset_time' => 0];
    }

    $limit = (int)($config->maxtokensperuser ?? INTEBCHAT_DEFAULT_TOKEN_LIMIT);
    $period = $config->tokenlimitperiod ?: 'day';

    // Calculate period start time
    $now = time();
    $periodstart = intebchat_get_period_start($period, $now);

    // Get token usage record for current period
    $usage = $DB->get_record('intebchat_token_usage', [
        'userid' => $userid,
        'periodtype' => $period,
        'periodstart' => $periodstart
    ]);

    // If no record exists, user has 0 tokens used for this period
    $tokensused = $usage ? (int)$usage->tokensused : 0;

    // Calculate when the limit resets
    $reset_time = $periodstart + intebchat_get_period_duration($period);

    return [
        'allowed' => $tokensused < $limit,
        'used' => $tokensused,
        'limit' => $limit,
        'reset_time' => $reset_time
    ];
}

/**
 * Get period duration in seconds
 *
 * @param string $period Period type
 * @return int Duration in seconds
 */
function intebchat_get_period_duration($period)
{
    switch ($period) {
        case 'hour':
            return INTEBCHAT_PERIOD_HOUR;
        case 'week':
            return INTEBCHAT_PERIOD_WEEK;
        case 'month':
            return INTEBCHAT_PERIOD_MONTH;
        case 'day':
        default:
            return INTEBCHAT_PERIOD_DAY;
    }
}

/**
 * Update token usage for a user
 *
 * @param int $userid User ID
 * @param int $tokens Number of tokens to add
 * @return bool Success
 */
function intebchat_update_token_usage($userid, $tokens)
{
    global $DB;

    // Validate input
    if (empty($userid) || $tokens <= 0) {
        return true;
    }

    $config = get_config('mod_intebchat');

    // If token limit is not enabled, don't track
    if (empty($config->enabletokenlimit)) {
        return true;
    }

    $period = $config->tokenlimitperiod ?: 'day';
    $now = time();

    // Calculate current period start
    $periodstart = intebchat_get_period_start($period, $now);

    // Try to update existing record first (more common case)
    $sql = "UPDATE {intebchat_token_usage}
            SET tokensused = tokensused + :tokens, timemodified = :now
            WHERE userid = :userid AND periodtype = :periodtype AND periodstart = :periodstart";

    $params = [
        'tokens' => (int)$tokens,
        'now' => $now,
        'userid' => $userid,
        'periodtype' => $period,
        'periodstart' => $periodstart
    ];

    $updated = $DB->execute($sql, $params);

    // Check if any row was updated
    $usage = $DB->get_record('intebchat_token_usage', [
        'userid' => $userid,
        'periodtype' => $period,
        'periodstart' => $periodstart
    ]);

    if (!$usage) {
        // No record exists, create new one
        try {
            $usage = new stdClass();
            $usage->userid = $userid;
            $usage->tokensused = (int)$tokens;
            $usage->periodstart = $periodstart;
            $usage->periodtype = $period;
            $usage->timecreated = $now;
            $usage->timemodified = $now;
            $DB->insert_record('intebchat_token_usage', $usage);
        } catch (\dml_exception $e) {
            // Race condition - record was created by another request
            // Try to update again
            $DB->execute($sql, $params);
        }
    }

    // Periodically clean up old records (1 in N chance per request)
    if (rand(1, INTEBCHAT_CLEANUP_PROBABILITY) === 1) {
        intebchat_cleanup_old_token_records($period, $periodstart);
    }

    return true;
}

/**
 * Clean up old token usage records
 *
 * @param string $period Period type
 * @param int $currentperiodstart Start of current period
 */
function intebchat_cleanup_old_token_records($period, $currentperiodstart)
{
    global $DB;

    try {
        // Delete records from previous periods
        $DB->delete_records_select(
            'intebchat_token_usage',
            'periodstart < :periodstart AND periodtype = :periodtype',
            ['periodstart' => $currentperiodstart, 'periodtype' => $period]
        );
    } catch (\Exception $e) {
        // Log error but don't fail the request
        debugging('Failed to clean up old token records: ' . $e->getMessage(), DEBUG_DEVELOPER);
    }
}

/**
 * Normalize token usage from different OpenAI API response formats
 *
 * @param array $usage Raw usage data from API response
 * @return array|null Normalized token info or null if empty
 */
function intebchat_normalize_usage($usage)
{
    if (empty($usage) || !is_array($usage)) {
        return null;
    }

    // Handle both old format (prompt_tokens/completion_tokens) and new format (input_tokens/output_tokens)
    $prompt = $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
    $completion = $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0;
    $total = $usage['total_tokens'] ?? ($prompt + $completion);

    // Only return if we have actual token data
    if ($total > 0) {
        return [
            'prompt' => (int)$prompt,
            'completion' => (int)$completion,
            'total' => (int)$total
        ];
    }

    return null;
}

/**
 * Log message with token tracking and conversation management
 *
 * @param int $instanceid The module instance ID
 * @param int $conversationid The conversation ID
 * @param string $usermessage The text sent from the user
 * @param string $airesponse The text returned by the AI
 * @param object $context The context object
 * @param array $tokeninfo Token usage information ['prompt' => int, 'completion' => int, 'total' => int]
 */
function intebchat_log_message($instanceid, $conversationid, $usermessage, $airesponse, $context, $tokeninfo = null)
{
    global $USER, $DB;

    $config = get_config('mod_intebchat');
    if (empty($conversationid) && empty($config->logging)) {
        return;
    }

    $record = new stdClass();
    $record->instanceid = $instanceid;
    $record->userid = $USER->id;
    $record->conversationid = $conversationid;
    $record->usermessage = $usermessage;
    $record->airesponse = $airesponse;
    $record->contextid = $context->id;
    $record->timecreated = time();

    // Add token information if provided
    if ($tokeninfo && is_array($tokeninfo)) {
        $record->prompttokens = $tokeninfo['prompt'] ?? 0;
        $record->completiontokens = $tokeninfo['completion'] ?? 0;
        $record->totaltokens = $tokeninfo['total'] ?? 0;
        // Note: Token usage is updated in completion.php, not here, to avoid double counting
    } else {
        // Log with zero tokens if no token info provided
        $record->prompttokens = 0;
        $record->completiontokens = 0;
        $record->totaltokens = 0;
    }

    $DB->insert_record('intebchat_log', $record);

    // Update conversation message count and preview
    if ($conversationid) {
        $messagecount = $DB->count_records('intebchat_log', ['conversationid' => $conversationid]);
        intebchat_update_conversation($conversationid, null, $usermessage);

        $DB->set_field('intebchat_conversations', 'messagecount', $messagecount, ['id' => $conversationid]);
    }
}

/**
 * Get API configuration for an instance
 *
 * @param object $instance The intebchat instance
 * @return array Configuration array
 */
function intebchat_get_api_config($instance)
{
    $config = get_config('mod_intebchat');

    // Start with global config
    $apiconfig = [
        'apikey' => $config->apikey,
        'type' => $config->type ?: 'chat',
        'model' => $config->model,
        'temperature' => $config->temperature,
        'maxlength' => $config->maxlength,
        'topp' => $config->topp,
        'frequency' => $config->frequency,
        'presence' => $config->presence,
        'assistant' => $config->assistant,
    ];

    // Override with instance settings if allowed and present
    if ($config->allowinstancesettings) {
        if (!empty($instance->apikey)) {
            $apiconfig['apikey'] = $instance->apikey;
        }
        if (!empty($instance->model)) {
            $apiconfig['model'] = $instance->model;
        }
        if (isset($instance->temperature)) {
            $apiconfig['temperature'] = $instance->temperature;
        }
        if (isset($instance->maxlength)) {
            $apiconfig['maxlength'] = $instance->maxlength;
        }
        if (isset($instance->topp)) {
            $apiconfig['topp'] = $instance->topp;
        }
        if (isset($instance->frequency)) {
            $apiconfig['frequency'] = $instance->frequency;
        }
        if (isset($instance->presence)) {
            $apiconfig['presence'] = $instance->presence;
        }
        if (!empty($instance->assistant)) {
            $apiconfig['assistant'] = $instance->assistant;
        }
    }

    return $apiconfig;
}

// Keep all existing functions from the original file below...

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 */
function intebchat_user_outline($course, $user, $mod, $intebchat)
{
    global $DB;

    $conversations = $DB->get_records(
        'intebchat_conversations',
        array('instanceid' => $intebchat->id, 'userid' => $user->id),
        'timemodified DESC',
        '*',
        0,
        1
    );

    if ($conversations) {
        $conversation = reset($conversations);
        $result = new stdClass();
        $result->info = get_string('conversations', 'mod_intebchat') . ': ' . count($conversations);
        $result->time = $conversation->timemodified;
        return $result;
    }
    return null;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 */
function intebchat_user_complete($course, $user, $mod, $intebchat)
{
    global $DB, $OUTPUT;

    $conversations = $DB->get_records(
        'intebchat_conversations',
        array('instanceid' => $intebchat->id, 'userid' => $user->id),
        'timecreated ASC'
    );

    if ($conversations) {
        echo $OUTPUT->heading(get_string('conversations', 'mod_intebchat') . ': ' . count($conversations));

        // Calculate total tokens if token limit is enabled
        $config = get_config('mod_intebchat');
        if (!empty($config->enabletokenlimit)) {
            $totaltokens = 0;
            foreach ($conversations as $conv) {
                $tokens = $DB->get_field_sql(
                    "SELECT SUM(totaltokens) FROM {intebchat_log} WHERE conversationid = :convid",
                    ['convid' => $conv->id]
                );
                $totaltokens += $tokens;
            }
            echo '<p>' . get_string('totaltokensused', 'mod_intebchat', $totaltokens) . '</p>';
        }

        foreach ($conversations as $conv) {
            echo '<h4>' . s($conv->title) . '</h4>';
            echo '<p>' . get_string('messages', 'mod_intebchat') . ': ' . $conv->messagecount . '</p>';
            echo '<p>' . get_string('created', 'mod_intebchat') . ': ' . userdate($conv->timecreated) . '</p>';
        }
    } else {
        echo '<p>' . get_string('noconversations', 'mod_intebchat') . '</p>';
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in intebchat activities and print it out.
 */
function intebchat_print_recent_activity($course, $viewfullnames, $timestart)
{
    return false;
}

/**
 * Prepares the recent activity data
 */
function intebchat_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {}

/**
 * Prints single activity item prepared by {@link intebchat_get_recent_mod_activity()}
 */
function intebchat_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {}

/**
 * Function to be run periodically according to the moodle cron
 */
function intebchat_cron()
{
    return true;
}

/**
 * Returns all other caps used in the module
 */
function intebchat_get_extra_capabilities()
{
    return array();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 */
function intebchat_get_file_areas($course, $cm, $context)
{
    return array();
}

/**
 * File browsing support for intebchat file areas
 */
function intebchat_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename)
{
    return null;
}

/**
 * Serves the files from the intebchat file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param context $context the context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found - just send the file
 */
function intebchat_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array())
{
    global $USER, $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    // Handle user audio files
    if ($filearea === 'useraudio') {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        // Verify user has access to this conversation
        $conversation = $DB->get_record('intebchat_conversations', ['id' => $itemid]);
        if (!$conversation) {
            send_file_not_found();
        }

        // User can access their own audio or if they have view capability
        if ($conversation->userid !== $USER->id && !has_capability('mod/intebchat:view', $context)) {
            send_file_not_found();
        }

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'mod_intebchat', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            send_file_not_found();
        }

        // Send the file with appropriate caching
        send_stored_file($file, 86400, 0, $forcedownload, $options);
    }

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding intebchat nodes if there is a relevant content
 */
function intebchat_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {}

/**
 * Extends the settings navigation with the intebchat settings
 */
function intebchat_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $intebchatnode = null) {}

/**
 * Adds INTEB Chat report link to course reports navigation.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course object
 * @param context $context The course context
 */
function intebchat_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    global $DB;

    // Check if there are any intebchat instances in this course
    $instances = $DB->get_records('intebchat', ['course' => $course->id], '', 'id');
    if (empty($instances)) {
        return;
    }

    // Check capability to view analytics
    if (!has_capability('mod/intebchat:viewanalytics', $context)) {
        return;
    }

    $url = new moodle_url('/mod/intebchat/report_course.php', ['courseid' => $course->id]);

    // Try different node keys that Moodle versions use for course reports
    $reportsnode = $navigation->find('coursereports', navigation_node::TYPE_CONTAINER);
    if (!$reportsnode) {
        // Try TYPE_CUSTOM for Moodle 4.x
        $reportsnode = $navigation->find('coursereports', navigation_node::TYPE_CUSTOM);
    }
    if (!$reportsnode) {
        // Try finding by key without type
        $reportsnode = $navigation->find('coursereports', null);
    }

    if ($reportsnode) {
        $reportsnode->add(
            get_string('intebchatreport', 'mod_intebchat'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            'intebchatreport',
            new pix_icon('icon', '', 'mod_intebchat')
        );
    }
}

/**
 * Helper functions from original block plugin
 */

/**
 * Fetch the current API type from the database, defaulting to "chat"
 * @return String: the API type (chat|assistant)
 */
function intebchat_get_type_to_display()
{
    $stored_type = get_config('mod_intebchat', 'type');
    if ($stored_type && in_array($stored_type, ['chat', 'assistant'])) {
        return $stored_type;
    }

    return 'chat';
}

/**
 * Use an API key to fetch a list of assistants from a user's OpenAI account
 * @param String (optional): The API key to use. If not provided, will use site-wide key.
 * @return Array: The list of assistants
 */
function intebchat_fetch_assistants_array($apikey = null)
{
    if (!$apikey) {
        $apikey = get_config('mod_intebchat', 'apikey');
    }

    if (!$apikey) {
        return [];
    }

    $curl = new \curl();
    $curl->setopt(array(
        'CURLOPT_HTTPHEADER' => array(
            'Authorization: Bearer ' . $apikey,
            'Content-Type: application/json',
            'OpenAI-Beta: assistants=v2'
        ),
    ));

    $response = $curl->get("https://api.openai.com/v1/assistants?order=desc&limit=100");
    $response = json_decode($response);
    $assistant_array = [];
    if (property_exists($response, 'data')) {
        foreach ($response->data as $assistant) {
            $assistant_array[$assistant->id] = $assistant->name;
        }
    }

    return $assistant_array;
}

/**
 * Return a list of available OpenAI models - Actualizado Diciembre 2025
 *
 * @return array Array con 'default' y 'models'
 * @see https://platform.openai.com/docs/models
 */
function intebchat_get_models()
{
    return [
        // Default recomendado para balance calidad/costo
        "default" => "gpt-4.1",
        "models" => [
            // ═══════════════════════════════════════════════════════════════
            // GPT-5 SERIES (Noviembre-Diciembre 2025 - Más recientes)
            // ═══════════════════════════════════════════════════════════════
            'gpt-5.2'             => 'GPT-5.2 (Flagship - Dic 2025)',
            'gpt-5.1'             => 'GPT-5.1 (Nov 2025)',
            'gpt-5'               => 'GPT-5 (Razonamiento avanzado)',
            'gpt-5-mini'          => 'GPT-5 Mini (Rápido y económico)',

            // ═══════════════════════════════════════════════════════════════
            // O-SERIES (Modelos de razonamiento - Abril 2025+)
            // ═══════════════════════════════════════════════════════════════
            'o4-mini'             => 'o4-mini (Razonamiento rápido - SOTA)',
            'o4-mini-2025-04-16'  => 'o4-mini (2025-04-16)',
            'o3'                  => 'o3 (Razonamiento avanzado)',
            'o3-pro'              => 'o3-pro (Máximo razonamiento)',
            'o3-mini'             => 'o3-mini (Económico)',
            'o1'                  => 'o1 (Razonamiento)',
            'o1-pro'              => 'o1-pro (Razonamiento extendido)',
            'o1-mini'             => 'o1-mini (Razonamiento económico)',

            // ═══════════════════════════════════════════════════════════════
            // GPT-4.1 SERIES (Abril 2025 - Contexto 1M tokens)
            // ═══════════════════════════════════════════════════════════════
            'gpt-4.1'             => 'GPT-4.1 (Recomendado - 1M contexto)',
            'gpt-4.1-2025-04-14'  => 'GPT-4.1 (2025-04-14)',
            'gpt-4.1-mini'        => 'GPT-4.1 Mini (Económico)',
            'gpt-4.1-mini-2025-04-14' => 'GPT-4.1 Mini (2025-04-14)',
            'gpt-4.1-nano'        => 'GPT-4.1 Nano (Ultra rápido)',
            'gpt-4.1-nano-2025-04-14' => 'GPT-4.1 Nano (2025-04-14)',

            // ═══════════════════════════════════════════════════════════════
            // GPT-4o SERIES (Omni - Audio/Visión nativa)
            // ═══════════════════════════════════════════════════════════════
            'chatgpt-4o-latest'   => 'ChatGPT-4o Latest',
            'gpt-4o'              => 'GPT-4o (Multimodal)',
            'gpt-4o-2024-11-20'   => 'GPT-4o (2024-11-20)',
            'gpt-4o-audio-preview' => 'GPT-4o Audio (Para audio nativo)',
            'gpt-4o-mini'         => 'GPT-4o Mini (Económico)',
            'gpt-4o-mini-2024-07-18' => 'GPT-4o Mini (2024-07-18)',

            // ═══════════════════════════════════════════════════════════════
            // MODELOS ESPECIALIZADOS (Realtime, Código)
            // ═══════════════════════════════════════════════════════════════
            'gpt-4o-realtime-preview' => 'GPT-4o Realtime (WebRTC voz)',
            'gpt-4o-realtime-preview-2024-12-17' => 'GPT-4o Realtime (2024-12-17)',
            'codex-mini'          => 'Codex Mini (Especializado código)',

            // ═══════════════════════════════════════════════════════════════
            // LEGADO (Aún disponibles pero no recomendados)
            // ═══════════════════════════════════════════════════════════════
            'gpt-4-turbo'         => 'GPT-4 Turbo (Legado)',
            'gpt-4'               => 'GPT-4 (Legado)',
        ]
    ];
}

/**
 * Mark the activity completed (if required) and trigger the course_module_viewed event.
 *
 * @param  stdClass $intebchat   intebchat object
 * @param  stdClass $course     course object
 * @param  stdClass $cm         course module object
 * @param  stdClass $context    context object
 * @since Moodle 3.0
 */
function intebchat_view($intebchat, $course, $cm, $context)
{
    // Trigger course_module_viewed event.
    $params = array(
        'context' => $context,
        'objectid' => $intebchat->id
    );

    $event = \mod_intebchat\event\course_module_viewed::create($params);
    $event->add_record_snapshot('course_modules', $cm);
    $event->add_record_snapshot('course', $course);
    $event->add_record_snapshot('intebchat', $intebchat);
    $event->trigger();

    // Completion.
    $completion = new completion_info($course);
    $completion->set_module_viewed($cm);
}

/**
 * Calculate the start timestamp for the current period.
 *
 * @param string $period Period type
 * @param int $timestamp Reference timestamp
 * @return int Period start timestamp
 */
function intebchat_get_period_start($period, $timestamp = null)
{
    $timestamp = $timestamp ?? time();

    switch ($period) {
        case 'hour':
            return $timestamp - ($timestamp % 3600);
        case 'week':
            $day = date('w', $timestamp);
            $monday = $timestamp - (($day == 0 ? 6 : $day - 1) * 86400);
            return strtotime(date('Y-m-d 00:00:00', $monday));
        case 'month':
            return strtotime(date('Y-m-01 00:00:00', $timestamp));
        case 'day':
        default:
            return strtotime(date('Y-m-d 00:00:00', $timestamp));
    }
}

// ============================================================================
// ANALYTICS FUNCTIONS
// ============================================================================

/**
 * Get analytics data for an intebchat instance.
 *
 * Collects various statistics about usage including conversations,
 * messages, tokens, and user activity.
 *
 * @param int $instanceid The intebchat instance ID
 * @param int $periodstart Start timestamp for the period
 * @param int $periodend End timestamp for the period
 * @return array Analytics data array
 */
function intebchat_get_instance_analytics($instanceid, $periodstart, $periodend)
{
    global $DB;

    $stats = [
        'total_conversations' => 0,
        'total_messages' => 0,
        'total_tokens' => 0,
        'unique_users' => 0,
        'avg_messages_per_user' => 0,
        'avg_tokens_per_message' => 0,
        'top_users' => [],
        'daily_stats' => []
    ];

    // Build time condition
    $timecondition = '';
    $params = ['instanceid' => $instanceid];

    if ($periodstart > 0) {
        $timecondition = ' AND l.timecreated >= :periodstart AND l.timecreated <= :periodend';
        $params['periodstart'] = $periodstart;
        $params['periodend'] = $periodend;
    }

    // Total conversations in period
    $sql = "SELECT COUNT(DISTINCT c.id)
            FROM {intebchat_conversations} c
            LEFT JOIN {intebchat_log} l ON l.conversationid = c.id
            WHERE c.instanceid = :instanceid" . str_replace('l.timecreated', 'c.timecreated', $timecondition);
    $stats['total_conversations'] = (int)$DB->count_records_sql($sql, $params);

    // Total messages and tokens
    $sql = "SELECT COUNT(*) as messages, COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            WHERE l.instanceid = :instanceid" . $timecondition;
    $result = $DB->get_record_sql($sql, $params);
    $stats['total_messages'] = (int)($result->messages ?? 0);
    $stats['total_tokens'] = (int)($result->tokens ?? 0);

    // Unique users
    $sql = "SELECT COUNT(DISTINCT l.userid)
            FROM {intebchat_log} l
            WHERE l.instanceid = :instanceid" . $timecondition;
    $stats['unique_users'] = (int)$DB->count_records_sql($sql, $params);

    // Calculate averages
    if ($stats['unique_users'] > 0) {
        $stats['avg_messages_per_user'] = $stats['total_messages'] / $stats['unique_users'];
    }
    if ($stats['total_messages'] > 0) {
        $stats['avg_tokens_per_message'] = $stats['total_tokens'] / $stats['total_messages'];
    }

    // Top users by activity
    $sql = "SELECT l.userid, COUNT(*) as messages, COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            WHERE l.instanceid = :instanceid" . $timecondition . "
            GROUP BY l.userid
            ORDER BY messages DESC
            LIMIT 10";
    $stats['top_users'] = array_values($DB->get_records_sql($sql, $params));

    // Daily stats for chart (last 30 days max)
    $chartstart = max($periodstart, strtotime('-30 days'));
    $sql = "SELECT DATE(FROM_UNIXTIME(l.timecreated)) as date,
                   COUNT(*) as messages,
                   COALESCE(SUM(l.totaltokens), 0) as tokens
            FROM {intebchat_log} l
            WHERE l.instanceid = :instanceid
              AND l.timecreated >= :chartstart
              AND l.timecreated <= :periodend
            GROUP BY DATE(FROM_UNIXTIME(l.timecreated))
            ORDER BY date ASC";

    $chartparams = [
        'instanceid' => $instanceid,
        'chartstart' => $chartstart,
        'periodend' => $periodend
    ];

    $dailyresults = $DB->get_records_sql($sql, $chartparams);

    // Convert dates to timestamps for template
    foreach ($dailyresults as $day) {
        $stats['daily_stats'][] = (object)[
            'date' => strtotime($day->date),
            'messages' => (int)$day->messages,
            'tokens' => (int)$day->tokens
        ];
    }

    return $stats;
}