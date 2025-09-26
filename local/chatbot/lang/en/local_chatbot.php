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
 * English language strings for the local_chatbot plugin.
 *
 * @package    local_chatbot
 */

$string['pluginname'] = 'Chatbot assistant';

// Capabilities.
$string['chatbot:use'] = 'Use the chatbot widget';
$string['chatbot:manage'] = 'Configure the chatbot widget';
$string['chatbot:export'] = 'Export chatbot conversations';

// General strings.
$string['chatbot_title'] = 'Virtual assistant';
$string['chatbot_placeholder'] = 'Write your message…';
$string['chatbot_send'] = 'Send';
$string['chatbot_error'] = 'Sorry, something went wrong. Please try again.';
$string['chatbot_typing_indicator'] = 'The assistant is typing…';
$string['chatbot_status_online'] = 'Online and ready to help';
$string['chatbot_toggle_label'] = 'Talk with {$a}';
$string['chatbot_voice_input_label'] = 'Voice input';
$string['chatbot_emoji_picker_label'] = 'Emoji picker';
$string['chatbot_export_conversation_label'] = 'Export conversation';
$string['chatbot_minimize'] = 'Minimise';
$string['chatbot_close'] = 'Close';
$string['chatbot_quick_actions_region'] = 'Chatbot quick actions';
$string['chatbot_suggestions_region'] = 'Suggested prompts';
$string['chatbot_welcome_template'] = 'Hello {name}! I am here to guide you through Moodle.';
$string['default_nomatch'] = 'I am not sure about that yet. Could you rephrase your question?';

// Suggestions and quick actions.
$string['chatbot_suggestion_courses'] = 'Where are my courses?';
$string['chatbot_suggestion_grades'] = 'How do I see my grades?';
$string['chatbot_suggestion_support'] = 'Contact support';
$string['chatbot_action_profile'] = 'My profile';
$string['chatbot_action_profile_desc'] = 'Open your profile page';
$string['chatbot_action_calendar'] = 'Calendar';
$string['chatbot_action_calendar_desc'] = 'Open the calendar view';
$string['chatbot_action_support'] = 'Support centre';
$string['chatbot_action_support_desc'] = 'Display the institutional support contact options';
$string['chatbot_action_generic'] = 'Action executed: {$a}';

// Responses.
$string['chatbot_response_greeting'] = 'Hello! 👋 How can I help you today?';
$string['chatbot_response_help'] = 'I can help you locate courses, grades and useful links. Try asking “Where do I see my grades?”';
$string['chatbot_response_courses'] = 'Open the “My courses” menu at the top of the site to see your enrolled courses.';
$string['chatbot_response_grades'] = 'Visit the grades page from the user menu or open the Grades option inside each course.';
$string['chatbot_response_support'] = 'You can reach our support team via the help centre or by emailing the academic support mailbox.';

// Export.
$string['chatbot_export_heading'] = 'Conversation history';
$string['chatbot_export_empty'] = 'No messages have been recorded yet.';
$string['message'] = 'Message';
$string['response'] = 'Response';
$string['intent'] = 'Intent';
$string['feedback'] = 'Feedback';
$string['download'] = 'Download';
$string['time'] = 'Time';
$string['user'] = 'User';

// Settings.
$string['setting_enabled'] = 'Enable chatbot';
$string['setting_enabled_desc'] = 'If disabled the widget will not be injected on any page.';
$string['setting_assistantname'] = 'Assistant name';
$string['setting_assistantname_desc'] = 'Shown in the widget header and used in accessibility descriptions.';
$string['setting_position'] = 'Widget position';
$string['setting_position_desc'] = 'Select where the launcher should appear on the screen.';
$string['position_bottom_right'] = 'Bottom right';
$string['position_bottom_left'] = 'Bottom left';
$string['setting_theme'] = 'Theme';
$string['setting_theme_desc'] = 'Choose the colour scheme for the widget.';
$string['theme_modern'] = 'Modern';
$string['theme_minimal'] = 'Minimal';
$string['theme_dark'] = 'Dark';
$string['setting_welcome'] = 'Welcome message';
$string['setting_welcome_desc'] = 'Message displayed the first time a user opens the widget. Use {name} for the user name.';
$string['setting_nomatch'] = 'Fallback response';
$string['setting_nomatch_desc'] = 'Message sent when the chatbot cannot match a question.';
$string['setting_maxlength'] = 'Maximum message length';
$string['setting_maxlength_desc'] = 'Limit the amount of characters users can send.';
$string['setting_allow_export'] = 'Allow conversation export';
$string['setting_allow_export_desc'] = 'Show the export button when the user has the export capability.';

// Intents management.
$string['manage_intents'] = 'Manage intents';
$string['intents_intro'] = 'Maintain the intents that power the rule-based chatbot classifier. Keywords are matched in order and a single intent can act as the global fallback.';
$string['intent_name'] = 'Intent name';
$string['intent_keywords'] = 'Keywords';
$string['intent_keywords_help'] = 'Provide one keyword per line. Keywords are matched case-insensitively against the user message.';
$string['intent_response'] = 'Response template';
$string['intent_fallback'] = 'Use as fallback intent';
$string['intent_fallback_help'] = 'If enabled this response will be returned whenever no other intent matches.';
$string['intent_enabled'] = 'Enabled';
$string['intent_sortorder'] = 'Sort order';
$string['intent_status'] = 'Status';
$string['intent_status_fallback'] = 'Fallback';
$string['intent_no_keywords'] = 'No keywords';
$string['intent_keywords_required'] = 'Provide at least one keyword or mark the intent as fallback.';
$string['intent_unknown'] = 'Unclassified';
$string['intent_edit_heading'] = 'Edit intent';
$string['intent_add_heading'] = 'Add intent';
$string['intent_table_heading'] = 'Configured intents';
$string['intent_saved'] = 'Intent saved successfully.';
$string['intent_deleted'] = 'Intent deleted.';
$string['intent_delete_confirm'] = 'Do you really want to delete the intent "{$a}"?';

// Entities (quick actions & suggestions).
$string['manage_entities'] = 'Manage entities';
$string['entities_intro'] = 'Quick actions and suggestions define the shortcuts and hints that the assistant shows to end users.';
$string['entities_quickactions_tab'] = 'Quick actions';
$string['entities_suggestions_tab'] = 'Suggestions';
$string['quickaction_actionkey'] = 'Action key';
$string['quickaction_name'] = 'Label';
$string['quickaction_type'] = 'Action type';
$string['quickaction_type_help'] = 'Choose how the quick action behaves: navigating to a page, injecting a message in the composer or returning a server-side response.';
$string['quickaction_type_navigate'] = 'Navigate to a page';
$string['quickaction_type_inject'] = 'Prefill message';
$string['quickaction_type_server'] = 'Server reply';
$string['quickaction_payload'] = 'Payload';
$string['quickaction_payload_help'] = 'For navigation provide a relative path such as /user/profile.php?id={userid}. Inject/server actions use plain text as payload.';
$string['quickaction_description'] = 'Description';
$string['quickaction_icon'] = 'Icon / emoji';
$string['quickaction_icon_help'] = 'Optional emoji or short text used as the icon in the widget.';
$string['quickaction_enabled'] = 'Enabled';
$string['quickaction_sortorder'] = 'Sort order';
$string['quickaction_status'] = 'Status';
$string['quickaction_payload_required'] = 'Provide a URL or path for navigation actions.';
$string['quickaction_payload_text_required'] = 'Provide the message payload for this quick action.';
$string['quickaction_actionkey_unique'] = 'The action key must be unique.';
$string['quickaction_edit_heading'] = 'Edit quick action';
$string['quickaction_add_heading'] = 'Add quick action';
$string['quickaction_table_heading'] = 'Configured quick actions';
$string['quickaction_saved'] = 'Quick action saved.';
$string['quickaction_deleted'] = 'Quick action deleted.';
$string['quickaction_delete_confirm'] = 'Delete the quick action "{$a}"?';

$string['suggestion_text'] = 'Suggestion text';
$string['suggestion_mode'] = 'Suggestion behaviour';
$string['suggestion_mode_help'] = 'Message suggestions inject the text into the composer while action suggestions trigger a quick action.';
$string['suggestion_mode_message'] = 'Send message';
$string['suggestion_mode_action'] = 'Trigger quick action';
$string['suggestion_target'] = 'Target / action key';
$string['suggestion_target_help'] = 'For action suggestions specify the quick action key to execute. For message suggestions this can be left blank to reuse the text.';
$string['suggestion_icon'] = 'Icon / emoji';
$string['suggestion_enabled'] = 'Enabled';
$string['suggestion_sortorder'] = 'Sort order';
$string['suggestion_status'] = 'Status';
$string['suggestion_target_required'] = 'Choose an action to trigger when the suggestion mode is set to “Trigger quick action”.';
$string['suggestion_edit_heading'] = 'Edit suggestion';
$string['suggestion_add_heading'] = 'Add suggestion';
$string['suggestion_table_heading'] = 'Configured suggestions';
$string['suggestion_saved'] = 'Suggestion saved.';
$string['suggestion_deleted'] = 'Suggestion deleted.';
$string['suggestion_delete_confirm'] = 'Delete the suggestion "{$a}"?';

// Training console.
$string['training'] = 'Training & learning';
$string['training_intro'] = 'Experiment with intents, responses and quick actions without leaving the Moodle interface.';
$string['training_message'] = 'Message to analyse';
$string['training_logmessage'] = 'Log result to history';
$string['training_logmessage_help'] = 'If enabled the message will be stored in the conversation log as if it was submitted from the widget.';
$string['training_sessionid'] = 'Session identifier';
$string['training_sessionid_help'] = 'Provide a session id to reuse an existing conversation, otherwise a fresh session will be generated.';
$string['training_run'] = 'Run analysis';
$string['training_logged'] = 'Stored in conversation log';
$string['training_preview'] = 'Preview only';
$string['training_result_heading'] = 'Analysis result';
$string['training_result_status'] = 'Mode: {$a}';
$string['training_result_intent'] = 'Matched intent: {$a}';
$string['training_result_response'] = 'Response: {$a}';
$string['training_result_session'] = 'Session: {$a}';
$string['training_result_keywords'] = 'Keywords considered: {$a}';
$string['training_context_heading'] = 'Widget context overview';
$string['training_context_suggestions'] = 'Suggestion chips currently exposed:';
$string['training_context_actions'] = 'Quick actions currently available:';
$string['training_history_heading'] = 'Recent messages for this session';
$string['training_result_time'] = 'Response time: {$a}';

// Analytics dashboard.
$string['analytics'] = 'Analytics and reports';
$string['analytics_intro'] = 'Track engagement with the chatbot to understand how the assistant is used across the site.';
$string['analytics_total_messages'] = 'Total messages logged';
$string['analytics_total_sessions'] = 'Active sessions';
$string['analytics_total_users'] = 'Unique users';
$string['analytics_average_response'] = 'Average response time';
$string['analytics_intents_heading'] = 'Intents usage';
$string['analytics_messages'] = 'Messages';
$string['analytics_intents_chart_title'] = 'Messages per intent';
$string['analytics_activity_chart_title'] = 'Messages per day';
$string['analytics_activity_heading'] = 'Recent activity';
$string['analytics_feedback_heading'] = 'Feedback distribution';
$string['analytics_feedback_unknown'] = 'Unspecified';

// Dialogue viewer.
$string['dialogues'] = 'Dialogue flows';
$string['dialogue_filter_session'] = 'Session id';
$string['dialogue_filter_userid'] = 'User id';
$string['dialogue_filter_intent'] = 'Intent';
$string['dialogue_filter_from'] = 'From date';
$string['dialogue_filter_to'] = 'To date';
$string['dialogue_filter_feedback'] = 'Only messages with feedback';
$string['dialogue_filter_apply'] = 'Apply filters';
$string['dialogue_filter_reset'] = 'Reset';
$string['dialogue_detail_heading'] = 'Conversation for session {$a}';
$string['dialogue_export'] = 'Export conversation';
$string['dialogue_no_records'] = 'No conversation records matched the criteria.';
$string['dialogue_session'] = 'Session';
$string['viewdetails'] = 'View details';

// Manual test console.
$string['test_chatbot'] = 'Try the chatbot';
$string['test_intro'] = 'Send messages as the current user to verify keywords, intents and exports. Messages are stored in the main conversation log.';
$string['test_message'] = 'Message';
$string['test_sessionid'] = 'Session id (optional)';
$string['test_sessionid_help'] = 'Provide a custom session id to reuse an existing conversation. Leave empty to generate a new one.';
$string['test_send'] = 'Send message';
$string['test_result_heading'] = 'Server response';
$string['test_result_response'] = 'Response: {$a}';
$string['test_result_intent'] = 'Intent: {$a}';
$string['test_result_session'] = 'Session: {$a}';
$string['test_result_logid'] = 'Log id: {$a}';
$string['test_result_time'] = 'Response time: {$a}';
$string['test_suggestions_heading'] = 'Current suggestions';
$string['test_quickactions_heading'] = 'Quick actions overview';
$string['test_history_heading'] = 'Conversation history';

// Feedback buttons.
$string['chatbot_feedback_helpful'] = 'Helpful';
$string['chatbot_feedback_not_helpful'] = 'Not helpful';
$string['chatbot_feedback_thanks'] = 'Thanks for your feedback!';

// Legacy strings kept for backwards compatibility (no longer displayed).
$string['admin_placeholder'] = 'The management console is being prepared. The widget and web services are fully functional.';
$string['admin_placeholder_help'] = 'Continue using the Site administration settings page to configure the chatbot in the meantime.';
$string['analytics_and_reports'] = 'Analytics and reports';
