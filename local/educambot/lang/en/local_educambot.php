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
 * Strings for component 'local_educambot'.
 *
 * @package     local_educambot
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Educam Bot';
$string['privacy:metadata'] = 'The Educam Bot plugin stores conversation data to improve the knowledge base.';
$string['privacy:metadata:log'] = 'Conversation logs recorded by Educam Bot.';
$string['privacy:metadata:log:userid'] = 'The identifier of the user interacting with the bot.';
$string['privacy:metadata:log:sessionid'] = 'The internal chatbot session identifier used to group messages.';
$string['privacy:metadata:log:question'] = 'The message submitted by the user.';
$string['privacy:metadata:log:response'] = 'The response provided by the bot.';
$string['privacy:metadata:log:ruleid'] = 'The rule that generated the response, when available.';
$string['privacy:metadata:log:confidence'] = 'Calculated confidence for the matched rule.';
$string['privacy:metadata:log:page'] = 'Page where the conversation started.';
$string['privacy:metadata:log:timecreated'] = 'Time of the interaction.';
$string['privacy:metadata:unanswered'] = 'Questions that did not get an automatic answer.';
$string['privacy:metadata:unanswered:userid'] = 'The identifier of the user that asked the question.';
$string['privacy:metadata:unanswered:question'] = 'Content of the unanswered question.';
$string['privacy:metadata:unanswered:page'] = 'Page where the question was asked.';
$string['privacy:metadata:unanswered:timecreated'] = 'Time when the question was stored.';
$string['defaultbotname'] = 'Educam Bot';
$string['defaultgreeting'] = 'Hello {{userfirstname}}! I am {{botname}}. How can I help you today?';
$string['knowledgefallbackintro'] = '{{botname}} found related guidance that might help:';
$string['knowledgefallbackopen'] = 'Open resource';
$string['knowledgefallbackrelation'] = 'Related: {$a}';
$string['eventopenlink'] = 'View activity';
$string['manageentries'] = 'Knowledge base';
$string['manageentriesdesc'] = 'Configure chatbot answers, patterns and proactive suggestions.';
$string['addentry'] = 'Add entry';
$string['editentry'] = 'Edit entry';
$string['deleteentry'] = 'Delete entry';
$string['confirmdelete'] = 'Are you sure you want to delete "{$a}"?';
$string['pattern'] = 'Main question/pattern';
$string['synonyms'] = 'Synonyms or alternative phrasings';
$string['synonyms_help'] = 'List each synonym or alternative phrasing on a new line.';
$string['keywords'] = 'Keywords';
$string['keywords_help'] = 'Enter comma separated keywords to improve fuzzy matching.';
$string['response'] = 'Response';
$string['roles'] = 'Restrict to roles';
$string['roles_help'] = 'If set, the entry will only be used when the user has any of the selected roles.';
$string['contexts'] = 'Page contexts';
$string['contexts_help'] = 'Optional list of Moodle page paths or component names (one per line) to prioritise answers when the user is visiting a related area.';
$string['suggested'] = 'Mark as proactive suggestion';
$string['enabled'] = 'Enabled';
$string['saved'] = 'Entry saved';
$string['deleted'] = 'Entry deleted';
$string['logview'] = 'Conversation log';
$string['unansweredview'] = 'Unanswered questions';
$string['question'] = 'Question';
$string['matchedpattern'] = 'Matched pattern';
$string['confidence'] = 'Confidence';
$string['timecreated'] = 'Time';
$string['responsepreview'] = 'Response preview';
$string['searchplaceholder'] = 'Ask Educam Bot...';
$string['searchknowledgebase'] = 'Search the knowledge base';
$string['noanswer'] = 'I could not find an answer. I will let the administrators know.';
$string['suggestedquestions'] = 'Suggested questions';
$string['loading'] = 'Processing...';
$string['actions'] = 'Actions';
$string['timemodified'] = 'Last modified';
$string['send'] = 'Send message';
$string['settingsheading'] = 'Educam Bot settings';
$string['loggingenabled'] = 'Enable conversation logging';
$string['loggingenabled_desc'] = 'When enabled, the plugin will store every interaction for later review.';
$string['retentionperiod'] = 'Log retention (days)';
$string['retentionperiod_desc'] = 'Number of days to keep conversation logs. Older records are purged automatically.';
$string['brandingsettings'] = 'Branding and personality';
$string['botname'] = 'Bot name';
$string['botname_desc'] = 'Displayed name for the chatbot. You can use it in responses with the {{botname}} placeholder.';
$string['greetingtemplate'] = 'Greeting template';
$string['greetingtemplate_desc'] = 'Initial sentence shown in the conversation. Available placeholders: {{botname}}, {{userfirstname}}, {{userfullname}}, {{courselist}}.';
$string['widgetlabel'] = 'Widget label';
$string['widgetlabel_desc'] = 'Short label for the floating button that opens the chatbot.';
$string['introtemplate'] = 'Introductory text';
$string['introtemplate_desc'] = 'Description displayed at the top of the chat window. Placeholders such as {{botname}} or {{courselist}} are supported.';
$string['personalitytagline'] = 'Personality tagline';
$string['personalitytagline_desc'] = 'Optional short sentence to describe the bot under the header (for example “Your campus guide”).';
$string['primarycolor'] = 'Primary colour';
$string['primarycolor_desc'] = 'Main accent colour used for the header, user messages and buttons.';
$string['accentcolor'] = 'Accent colour';
$string['accentcolor_desc'] = 'Background colour for suggested questions and subtle accents.';
$string['backgroundcolor'] = 'Conversation background colour';
$string['backgroundcolor_desc'] = 'Background colour used for the conversation panel.';
$string['textcolor'] = 'Primary text colour';
$string['textcolor_desc'] = 'Colour used for bot answers and descriptive text.';
$string['widgettitle'] = 'Need help?';
$string['widgetintro'] = 'Ask me anything about the platform';
$string['knowledgebase'] = 'Knowledge base';
$string['savechanges'] = 'Save changes';
$string['cancel'] = 'Cancel';
$string['noentries'] = 'No entries configured yet. Add your first rule to start helping users!';
$string['unansweredquestion'] = 'Unanswered question';
$string['usercontext'] = 'User context';
$string['cleanuptask'] = 'Educam Bot log cleanup';
$string['sessionid'] = 'Session';
$string['roleany'] = 'Any role';
$string['contextany'] = 'Any page';
$string['status'] = 'Status';
$string['enabledyes'] = 'Enabled';
$string['enabledno'] = 'Disabled';
$string['formerrorpatternrequired'] = 'You must define the main question or pattern.';
$string['formerrorresponcerequired'] = 'You must provide a response.';
$string['generalsettings'] = 'General settings';
$string['startplaceholder'] = 'Type your question...';
$string['noconversationsfound'] = 'No records found yet.';
$string['export'] = 'Export';
$string['import'] = 'Import';
$string['faqtitle'] = 'Popular questions';
$string['clearsearch'] = 'Clear search';
$string['nosearchresults'] = 'No entries match your search yet.';
