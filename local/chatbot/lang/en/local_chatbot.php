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
 * Language strings for local_chatbot (English)
 *
 * @package    local_chatbot
 * @copyright  2025 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Intelligent Chatbot Widget';
$string['chatbot:use'] = 'Use the chatbot';
$string['chatbot:manage'] = 'Manage chatbot settings';
$string['chatbot:viewanalytics'] = 'View chatbot analytics';
$string['chatbot:train'] = 'Train the chatbot';
$string['chatbot:export'] = 'Export conversations';
$string['chatbot:viewallconversations'] = 'View all conversations';
$string['chatbot:deletelogs'] = 'Delete conversation logs';
$string['chatbot:configureintents'] = 'Configure intents and entities';
$string['chatbot:impersonate'] = 'Test chatbot as different users';

// General settings
$string['chatbot_title'] = 'Virtual Assistant';
$string['chatbot_placeholder'] = 'Type your message...';
$string['chatbot_send'] = 'Send';
$string['chatbot_welcome'] = 'Hello! I\'m your intelligent assistant. How can I help you today?';
$string['chatbot_error'] = 'Sorry, an error occurred. Please try again.';
$string['chatbot_typing'] = 'Typing...';
$string['chatbot_status_online'] = 'Online and ready to help';
$string['chatbot_toggle_label'] = 'Talk with {$a}';
$string['chatbot_typing_indicator'] = 'The assistant is typing...';
$string['chatbot_voice_input_label'] = 'Voice input';
$string['chatbot_emoji_picker_label'] = 'Emoji picker';
$string['chatbot_export_conversation_label'] = 'Export conversation';
$string['chatbot_minimize'] = 'Minimise';
$string['chatbot_close'] = 'Close';
$string['chatbot_welcome_template'] = 'I am your intelligent assistant and I am here to help with anything you need. How can I assist you today?';
$string['chatbot_quick_actions_region'] = 'Chatbot quick actions';
$string['chatbot_suggestions_region'] = 'Suggested prompts';
$string['chatbot_suggestion_courses'] = 'Show my courses';
$string['chatbot_suggestion_grades'] = 'Show my grades';
$string['chatbot_action_profile'] = 'My profile';
$string['chatbot_action_profile_desc'] = 'Go to my user profile';
$string['chatbot_export_heading'] = 'Conversation export';
$string['chatbot_export_placeholder'] = 'Conversation history for session {$a} will appear here in a future release.';
$string['chatbot_enabled'] = 'Chatbot is enabled';
$string['chatbot_disabled'] = 'Chatbot is disabled';
$string['enable_chatbot'] = 'Enable Chatbot';
$string['settings'] = 'Chatbot Settings';

// Position settings
$string['position'] = 'Widget Position';
$string['position_desc'] = 'Select where the chatbot widget will appear';
$string['bottom_right'] = 'Bottom Right';
$string['bottom_left'] = 'Bottom Left';

// Response management
$string['manage_responses'] = 'Manage Chatbot Responses';
$string['keyword'] = 'Keyword';
$string['response'] = 'Response';
$string['add_response'] = 'Add Response';
$string['edit_responses'] = 'Edit Responses';
$string['delete_response'] = 'Delete Response';
$string['response_saved'] = 'Response saved successfully';
$string['response_deleted'] = 'Response deleted successfully';
$string['no_match'] = 'I\'m sorry, I don\'t understand your question. Could you please rephrase it?';

// Intelligence settings
$string['confidence_threshold'] = 'Confidence Threshold';
$string['confidence_threshold_desc'] = 'Minimum confidence score to accept an intent (0-10)';
$string['learning_enabled'] = 'Learning Mode';
$string['learning_enabled_desc'] = 'Allow the chatbot to learn from interactions';
$string['context_memory_size'] = 'Context Memory Size';
$string['context_memory_size_desc'] = 'Number of previous interactions to remember';
$string['fuzzy_matching'] = 'Fuzzy Matching';
$string['fuzzy_matching_desc'] = 'Enable approximate matching for spelling errors';
$string['multilingual'] = 'Multi-language Support';
$string['multilingual_desc'] = 'Detect and respond in user\'s language';

// Feature settings
$string['voice_input'] = 'Voice Input';
$string['voice_input_desc'] = 'Allow users to speak to the chatbot';
$string['quick_actions'] = 'Quick Actions';
$string['quick_actions_desc'] = 'Show contextual quick action buttons';
$string['suggestions'] = 'Smart Suggestions';
$string['suggestions_desc'] = 'Show context-based suggestions';
$string['emoji_picker'] = 'Emoji Picker';
$string['emoji_picker_desc'] = 'Include emoji picker in chat';
$string['typing_animation'] = 'Typing Animation';
$string['typing_animation_desc'] = 'Simulate progressive typing in responses';
$string['sound_notifications'] = 'Sound Notifications';
$string['sound_notifications_desc'] = 'Play sounds when receiving messages';
$string['allow_export'] = 'Allow Export';
$string['allow_export_desc'] = 'Users can export their conversations';

// Themes
$string['theme'] = 'Visual Theme';
$string['theme_desc'] = 'Select the visual theme for the chatbot';
$string['theme_modern'] = 'Modern (Gradient)';
$string['theme_minimal'] = 'Minimalist';
$string['theme_dark'] = 'Dark Theme';
$string['theme_colorful'] = 'Colorful';

// Personality
$string['personality'] = 'Chatbot Personality';
$string['personality_desc'] = 'Define the tone of chatbot responses';
$string['personality_professional'] = 'Professional';
$string['personality_friendly'] = 'Friendly';
$string['personality_casual'] = 'Casual';
$string['personality_formal'] = 'Formal';

// Performance settings
$string['cache_duration'] = 'Cache Duration';
$string['cache_duration_desc'] = 'Time in seconds to cache common responses';
$string['max_response_time'] = 'Maximum Response Time';
$string['max_response_time_desc'] = 'Maximum time in milliseconds before timeout';
$string['rate_limit'] = 'Message Rate Limit';
$string['rate_limit_desc'] = 'Maximum messages per minute per user';
$string['log_retention_days'] = 'Log Retention Days';
$string['log_retention_days_desc'] = 'Days to keep conversation logs';

// Privacy settings
$string['anonymous_mode'] = 'Anonymous Mode';
$string['anonymous_mode_desc'] = 'Do not log personally identifiable information';
$string['collect_analytics'] = 'Collect Analytics';
$string['collect_analytics_desc'] = 'Collect data to improve service';
$string['require_consent'] = 'Require Consent';
$string['require_consent_desc'] = 'Request consent before using chatbot';

// Admin pages
$string['manage_intents'] = 'Manage Intents';
$string['manage_entities'] = 'Manage Entities';
$string['training'] = 'Training & Learning';
$string['analytics'] = 'Analytics & Reports';
$string['dialogues'] = 'Dialogue Flows';
$string['test_chatbot'] = 'Test Chatbot';

// Messages
$string['default_nomatch'] = 'I\'m sorry, I don\'t fully understand your question. Could you rephrase or be more specific?';
$string['welcome_message'] = 'Hello {name}! I\'m your intelligent assistant. How can I help you today?';
$string['error_message'] = 'Sorry, an error occurred while processing your message. Please try again.';
$string['idle_message'] = 'Are you still there? If you need anything else, feel free to ask me.';

// Analytics
$string['total_interactions'] = 'Total Interactions';
$string['unique_users'] = 'Unique Users';
$string['avg_response_time'] = 'Average Response Time';
$string['sentiment_distribution'] = 'Sentiment Distribution';
$string['top_intents'] = 'Top Intents';

// Feedback
$string['helpful'] = 'Helpful';
$string['not_helpful'] = 'Not Helpful';
$string['feedback_thanks'] = 'Thank you for your feedback!';

// Export
$string['export_conversation'] = 'Export Conversation';
$string['export_format'] = 'Export Format';
$string['export_html'] = 'HTML';
$string['export_csv'] = 'CSV';
$string['export_json'] = 'JSON';
$string['export_text'] = 'Plain Text';

// Errors
$string['error_no_permission'] = 'You do not have permission to use the chatbot';
$string['error_disabled'] = 'The chatbot is currently disabled';
$string['error_processing'] = 'Error processing your message';
$string['error_timeout'] = 'Request timeout. Please try again';
$string['error_rate_limit'] = 'Too many messages. Please wait a moment';

// Success messages
$string['configuration_saved'] = 'Configuration saved successfully';
$string['intent_created'] = 'Intent created successfully';
$string['entity_created'] = 'Entity created successfully';
$string['training_complete'] = 'Training completed successfully';
$string['logs_exported'] = 'Logs exported successfully';
$string['intent_greeting_desc'] = 'System greeting intent used for onboarding new conversations.';
$string['intent_courses_desc'] = 'Provides guidance about accessing and managing courses.';
$string['intent_grades_desc'] = 'Helps users locate their grades and grade reports.';
$string['admin_placeholder'] = 'This management area is still being designed. The current release ships with the floating widget and core configuration options.';
$string['admin_placeholder_help'] = 'Continue configuring the chatbot via Site administration → Plugins → Local plugins → Intelligent Chatbot while we finish these tools.';
