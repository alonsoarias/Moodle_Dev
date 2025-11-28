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
 * Plugin strings are defined here.
 *
 * @package     local_educambot
 * @copyright   2025 EducamBot Team
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general.
$string['pluginname'] = 'Bot';
$string['educambot'] = 'Bot';

// Capabilities.
$string['educambot:use'] = 'Use Nexo Bot';
$string['educambot:manage'] = 'Manage Nexo Bot';

// Settings.
$string['settings_header'] = 'Nexo Bot Settings';
$string['general_heading'] = 'General';
$string['widgetenabled'] = 'Enable widget';
$string['widgetenabled_desc'] = 'Show the chat widget on all pages for users with the appropriate permissions';
$string['identity_heading'] = 'Bot Identity';
$string['appearance_heading'] = 'Appearance';

$string['botname'] = 'Bot name';
$string['botname_desc'] = 'The name displayed for the bot';
$string['botname_default'] = 'Nexo Bot';

$string['widgetlabel'] = 'Widget label';
$string['widgetlabel_desc'] = 'The label shown on the widget button';
$string['widgetlabel_default'] = 'Chat Bot';

$string['greetingtemplate'] = 'Greeting message';
$string['greetingtemplate_desc'] = 'The greeting message shown when opening the chat. You can use: {{userfirstname}}, {{userlastname}}, {{botname}}';
$string['greeting_default'] = 'Hello {{userfirstname}}! I\'m {{botname}}, your virtual assistant. How can I help you today?';

$string['primarycolor'] = 'Primary color';
$string['primarycolor_desc'] = 'The main color for the widget (buttons, header)';

// Management page.
$string['managerules'] = 'Manage Rules';
$string['managerules_desc'] = 'Create and manage bot response rules';
$string['addrule'] = 'Add Rule';
$string['editrule'] = 'Edit Rule';
$string['deleterule'] = 'Delete Rule';
$string['norules'] = 'No rules defined yet';
$string['confirmdelete'] = 'Are you sure you want to delete this rule?';

// Rule form.
$string['pattern'] = 'Question Pattern';
$string['pattern_help'] = 'The main question or phrase that will trigger this response. Example: "How do I enroll in a course?"';
$string['keywords'] = 'Additional Keywords';
$string['keywords_help'] = 'Additional words or phrases to match (one per line). Example: enroll, enrollment, register';
$string['response'] = 'Response';
$string['response_help'] = 'The answer the bot will provide when this rule matches';
$string['enabled'] = 'Enabled';
$string['enabled_help'] = 'Enable or disable this rule';

// Service responses.
$string['noresponse'] = 'I\'m sorry, I don\'t have an answer for that question. Please try rephrasing or contact support.';
$string['invalidquestion'] = 'Please enter a valid question.';

// Table headers.
$string['pattern_header'] = 'Pattern';
$string['response_header'] = 'Response';
$string['status_header'] = 'Status';
$string['actions_header'] = 'Actions';
$string['status_enabled'] = 'Enabled';
$string['status_disabled'] = 'Disabled';

// Actions.
$string['edit'] = 'Edit';
$string['delete'] = 'Delete';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';

// Messages.
$string['rulecreated'] = 'Rule created successfully';
$string['ruleupdated'] = 'Rule updated successfully';
$string['ruledeleted'] = 'Rule deleted successfully';
$string['error_savingrule'] = 'Error saving rule';

// Widget.
$string['typeaquestion'] = 'Type your question...';
$string['online'] = 'Online';
$string['clearhistory'] = 'Clear history';

// Reports.
$string['reports'] = 'Reports';
$string['viewreports'] = 'View Reports';
$string['totalconversations'] = 'Total Conversations';
$string['matchedquestions'] = 'Matched Questions';
$string['unmatchedquestions'] = 'Unmatched Questions';
$string['successrate'] = 'Success Rate';
$string['recentconversations'] = 'Recent Conversations';
$string['averageconfidence'] = 'Avg. Confidence';
$string['topquestions'] = 'Top Questions';
$string['questionswithoutrule'] = 'Questions Without Rule';
$string['nologs'] = 'No conversations recorded yet';
$string['nounmatchedquestions'] = 'All questions have been answered! No unmatched questions.';
$string['matched'] = 'Matched';
$string['unmatched'] = 'Unmatched';
$string['uniqueusers'] = 'Unique Users';
$string['confidence'] = 'Confidence';
$string['viewdetails'] = 'View Details';
$string['conversationdetails'] = 'Conversation Details';
$string['close'] = 'Close';
$string['createrule'] = 'Create Rule';
$string['frequency'] = 'Frequency';
$string['filters'] = 'Filters';
$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['all'] = 'All';
$string['matchedonly'] = 'Matched only';
$string['unmatchedonly'] = 'Unmatched only';
$string['searchquestion'] = 'Search question...';
$string['applyfilters'] = 'Apply';

// Options (Quick Replies).
$string['options'] = 'Options';
$string['manageoptions'] = 'Options';
$string['addoption'] = 'Add Option';
$string['editoption'] = 'Edit Option';
$string['deleteoption'] = 'Delete Option';
$string['optiontext'] = 'Button Text';
$string['optiontext_help'] = 'Text shown on the button (max 100 characters)';
$string['targetrule'] = 'Target Rule';
$string['targetrule_help'] = 'Rule to trigger when this option is clicked';
$string['selecttargetrule'] = 'Select a rule...';
$string['icon'] = 'Icon';
$string['icon_help'] = 'Emoji or icon to show before text (optional). Example: emoji icons';
$string['showoptions'] = 'Show options';
$string['showoptions_help'] = 'Show quick reply buttons after this response';
$string['nooptions'] = 'No options defined for this rule';
$string['optionorder'] = 'Order';
$string['optioncreated'] = 'Option created successfully';
$string['optionupdated'] = 'Option updated successfully';
$string['optiondeleted'] = 'Option deleted successfully';
$string['confirmdeleteoption'] = 'Are you sure you want to delete this option?';
$string['optionsfor'] = 'Options for rule';
$string['backtorules'] = 'Back to Rules';
$string['moveup'] = 'Move up';
$string['movedown'] = 'Move down';
