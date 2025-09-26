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
 * @copyright  2024 Moodle Community
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
$string['chatbot_action_generic'] = 'Action executed: {$a}';

// Responses.
$string['chatbot_response_greeting'] = 'Hello! 👋 How can I help you today?';
$string['chatbot_response_help'] = 'I can help you locate courses, grades and useful links. Try asking "Where do I see my grades?"';
$string['chatbot_response_courses'] = 'Open the "My courses" menu at the top of the site to see your enrolled courses.';
$string['chatbot_response_grades'] = 'Visit the grades page from the user menu or open the Grades option inside each course.';

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

// Admin placeholder screens.
$string['admin_placeholder'] = 'The management console is being prepared. The widget and web services are fully functional.';
$string['admin_placeholder_help'] = 'Continue using the Site administration settings page to configure the chatbot in the meantime.';
$string['manage_intents'] = 'Manage intents';
$string['manage_entities'] = 'Manage entities';
$string['training'] = 'Training & learning';
$string['analytics'] = 'Analytics and reports';
$string['dialogues'] = 'Dialogue flows';
$string['test_chatbot'] = 'Try the chatbot';

// Feedback buttons.
$string['chatbot_feedback_helpful'] = 'Helpful';
$string['chatbot_feedback_not_helpful'] = 'Not helpful';
$string['chatbot_feedback_thanks'] = 'Thanks for your feedback!';