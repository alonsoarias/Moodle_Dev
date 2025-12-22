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
 * @author      Alonso Arias <soporte@ingeweb.co>
 * @copyright   2025 Ingeweb <https://ingeweb.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin general.
$string['pluginname'] = 'EducamBot';
$string['educambot'] = 'EducamBot';
$string['developedby'] = 'Developed by';

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
$string['greetingtemplate_desc'] = 'The greeting message shown when opening the chat. Available placeholders: {{userfirstname}}, {{userlastname}}, {{fullname}} (full name), {{username}} (alias for fullname), {{botname}}';
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
$string['question'] = 'Question';
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

// Categories.
$string['categories'] = 'Categories';
$string['managecategories'] = 'Manage Categories';
$string['category'] = 'Category';
$string['categoryname'] = 'Category Name';
$string['categorydescription'] = 'Description';
$string['parentcategory'] = 'Parent Category';
$string['addcategory'] = 'Add Category';
$string['editcategory'] = 'Edit Category';
$string['deletecategory'] = 'Delete Category';
$string['nocategories'] = 'No categories defined yet';
$string['categorycreated'] = 'Category created successfully';
$string['categoryupdated'] = 'Category updated successfully';
$string['categorydeleted'] = 'Category deleted successfully';
$string['categoryhasrules'] = 'Cannot delete category: it contains {$a} rule(s). Move or delete them first.';
$string['categoryhaschildren'] = 'Cannot delete category: it has {$a} subcategory(ies). Delete them first.';
$string['uncategorized'] = 'Uncategorized';
$string['rules'] = 'Rules';
$string['viewrules'] = 'View Rules';

// Tags.
$string['tags'] = 'Tags';
$string['tags_help'] = 'Comma-separated tags for searching (e.g.: enrollment, register, course)';

// Import/Export.
$string['importexport'] = 'Import/Export';
$string['exportkb'] = 'Export Knowledge Base';
$string['exportkb_desc'] = 'Download the complete knowledge base (categories, rules, and options) as a JSON file.';
$string['importkb'] = 'Import Knowledge Base';
$string['importkb_desc'] = 'Upload a JSON file to import categories, rules, and options.';
$string['exportfile'] = 'Export to JSON';
$string['import'] = 'Import';
$string['selectfile'] = 'Select file';
$string['clearexisting'] = 'Clear existing data';
$string['clearexisting_help'] = 'Delete all existing categories, rules, and options before importing. Use with caution!';
$string['importsuccess'] = 'Import successful! Imported {$a->categories} categories, {$a->rules} rules, and {$a->options} options.';
$string['importerror'] = 'Error reading import file';
$string['importinvalidjson'] = 'Invalid JSON format in import file';
$string['importinvalidversion'] = 'Invalid or missing version in import file';
$string['importinvalidcsv'] = 'Invalid CSV format. Make sure to include required columns: pattern, response';
$string['currentstats'] = 'Current knowledge base: {$a->categories} categories, {$a->rules} rules, {$a->options} options.';

// v3.5.0 - Import/Export improvements.
$string['export_json'] = 'Export JSON';
$string['export_csv'] = 'Export CSV';
$string['import_type'] = 'Import type';
$string['import_type_full'] = 'Full (categories, rules and options)';
$string['import_type_rules'] = 'Rules only';
$string['importkb_desc_v2'] = 'Upload a JSON or CSV file to import rules. JSON format supports categories, rules and options. CSV format only supports rules.';
$string['importsuccess_v2'] = 'Import completed: {$a->categories} categories, {$a->rules_imported} new rules, {$a->rules_updated} updated rules, {$a->options} options.';
$string['templates'] = 'Example Templates';
$string['templates_desc'] = 'Download example templates to understand the import structure.';
$string['download_json_template'] = 'Download JSON template';
$string['download_csv_template'] = 'Download CSV template';

// Duplicate.
$string['duplicate'] = 'Duplicate';
$string['duplicaterule'] = 'Duplicate Rule';
$string['confirmduplicaterule'] = 'Create a copy of this rule with its options?';
$string['ruleduplicated'] = 'Rule duplicated successfully';
$string['copy'] = 'Copy';

// Search.
$string['searchrules'] = 'Search rules...';

// Version 1.6.1 - Suggested questions.
$string['suggestedquestions'] = 'Frequently asked questions';
$string['selectanoption'] = 'Select an option or type your question';
$string['anotherquestion'] = 'Another question';
$string['resourcescategory'] = 'Resources and Materials';

// Version 1.7.0 - Moodle Context and Shortcuts.
// Shortcuts.
$string['shortcuts'] = 'Shortcuts';
$string['manageshortcuts'] = 'Manage Shortcuts';
$string['addshortcut'] = 'Add Shortcut';
$string['editshortcut'] = 'Edit Shortcut';
$string['deleteshortcut'] = 'Delete Shortcut';
$string['noshortcuts'] = 'No shortcuts defined yet';
$string['shortcutname'] = 'Shortcut Name';
$string['shortcutname_help'] = 'Descriptive name for the shortcut';
$string['shortcutkeywords'] = 'Keywords';
$string['shortcutkeywords_help'] = 'Words or phrases that trigger this shortcut (one per line)';
$string['actiontype'] = 'Action Type';
$string['actiontype_help'] = 'The type of Moodle data this shortcut will display';
$string['shortcutcreated'] = 'Shortcut created successfully';
$string['shortcutupdated'] = 'Shortcut updated successfully';
$string['shortcutdeleted'] = 'Shortcut deleted successfully';
$string['shortcutshelp'] = 'Shortcuts Help';
$string['shortcutshelp_desc'] = 'Shortcuts allow users to get Moodle information directly. When a user types a phrase matching the keywords, the bot will show dynamic system data.';
$string['availableactiontypes'] = 'Available action types';
$string['nametoolong'] = 'Name is too long (max 100 characters)';
$string['keywordsrequired'] = 'Keywords are required';
$string['sortorder'] = 'Sort Order';
$string['unknownshortcut'] = 'Unknown shortcut';

// Action types.
$string['actiontype_assignments'] = 'Pending course assignments';
$string['actiontype_grades'] = 'Course grades';
$string['actiontype_calendar'] = 'Upcoming calendar events';
$string['actiontype_messages'] = 'Recent messages';
$string['actiontype_teachers'] = 'Course teachers';
$string['actiontype_course'] = 'Course information';
$string['actiontype_progress'] = 'Course progress';
$string['actiontype_courses'] = 'My enrolled courses';
$string['actiontype_participants'] = 'Course participants';
$string['actiontype_badges'] = 'My badges';

// Shortcut responses.
$string['shortcut_nocourse'] = 'This feature is only available within a course. Please navigate to a course first.';
$string['shortcut_noassignments'] = 'You have no pending assignments in this course! Great work!';
$string['shortcut_nogrades'] = 'No grades available yet in this course.';
$string['shortcut_noevents'] = 'No events scheduled for the next 7 days.';
$string['shortcut_noteachers'] = 'No teachers assigned to this course.';
$string['shortcut_assignmentsheader'] = 'Your pending assignments:';
$string['shortcut_gradesheader'] = 'Your grades in {$a}:';
$string['shortcut_eventsheader'] = 'Upcoming events (7 days):';
$string['shortcut_messagesheader'] = 'Your messages:';
$string['shortcut_teachersheader'] = 'Teachers of {$a}:';
$string['shortcut_progressheader'] = 'Your progress in {$a}:';
$string['shortcut_nocourses'] = 'You are not enrolled in any courses.';
$string['shortcut_coursesheader'] = 'Your enrolled courses:';
$string['viewallcourses'] = 'View all courses';
$string['shortcut_noparticipants'] = 'No participants in this course.';
$string['shortcut_participantsheader'] = 'Participants of {$a}:';
$string['totalparticipants'] = 'Total participants: {$a}';
$string['viewallparticipants'] = 'View all participants';
$string['shortcut_nobadges'] = 'You have not earned any badges yet. Keep going!';
$string['shortcut_badgesheader'] = 'Your earned badges:';
$string['issuedon'] = 'Issued on {$a}';
$string['viewallbadges'] = 'View all badges';

// Menu help (v3.0.3).
$string['menu_help'] = 'How can I help you? Select an option or type your question:';

// Context and placeholders.
$string['contextaware'] = 'Context Aware';
$string['contextaware_help'] = 'This rule uses information from the current course or page';
$string['dynamicresponse'] = 'Dynamic Response';
$string['dynamicresponse_help'] = 'Response contains placeholders that will be replaced with real data';
$string['requiredcontext'] = 'Required Context';
$string['requiredcontext_help'] = 'Required context: site, course, or activity';
$string['placeholders'] = 'Available Placeholders';
$string['placeholders_help'] = 'Use these placeholders in dynamic responses to show information from the current context';
$string['requirescoursecontext'] = 'This information is only available within a course. Please navigate to a course first.';

// Placeholder labels.
$string['notavailable'] = 'N/A';
$string['theteacher'] = 'the teacher';
$string['noteachersassigned'] = 'No teachers assigned';
$string['noenddate'] = 'No end date';
$string['notgraded'] = 'Not graded';
$string['noduedate'] = 'No due date';
$string['nopendingassignments'] = 'No pending assignments';
$string['nopendingquizzes'] = 'No pending quizzes';
$string['andmore'] = '... and {$a} more';
$string['noupcomingevents'] = 'No upcoming events';
$string['noeventthisweek'] = 'No events this week';

// Grade and progress.
$string['overallgrade'] = 'Overall grade';
$string['notgradedyet'] = 'Not graded yet';
$string['recentgrades'] = 'Recent grades';
$string['viewallgrades'] = 'View all grades';
$string['currentgrade'] = 'Current grade';
$string['pendingtasks'] = 'Pending tasks';
$string['completion'] = 'Completion';
$string['teacher'] = 'Teacher';

// Messages.
$string['unreadmessages'] = 'You have {$a} unread message(s)';
$string['nounreadmessages'] = 'You have no unread messages';
$string['recentmessages'] = 'Recent messages';
$string['viewallmessages'] = 'View all messages';
$string['sendmessage'] = 'Send message';

// Calendar.
$string['viewcalendar'] = 'View full calendar';
$string['duedate'] = 'Due';
$string['overdue'] = 'Overdue';
$string['duein'] = 'Due in {$a}';

// v1.8.0 - Advanced personalization.
// Language settings.
$string['language_heading'] = 'Language Settings';
$string['autolang'] = 'Auto-detect language';
$string['autolang_desc'] = 'Automatically select rules based on the user\'s preferred language. When enabled, rules in the user\'s language will be preferred.';
$string['language'] = 'Language';
$string['language_help'] = 'Select the language for this rule. Rules will be filtered based on the user\'s language preference.';
$string['multilanguage'] = 'Multi-language';
$string['parentrule'] = 'Parent rule (for translations)';
$string['parentrule_help'] = 'If this rule is a translation of another rule, select the parent rule here. Leave empty for original rules.';
$string['translations'] = 'Translations';
$string['addtranslation'] = 'Add Translation';

// Restrictions.
$string['restrictions'] = 'Restrictions';
$string['roles'] = 'Roles';
$string['roles_help'] = 'Comma-separated role shortnames (e.g., student,teacher). Leave empty for all roles.';
$string['courses'] = 'Courses';
$string['courses_help'] = 'Comma-separated course IDs. Leave empty for all courses.';

// Advanced section.
$string['advanced'] = 'Advanced';
$string['contextaware'] = 'Context-aware';
$string['contextaware_help'] = 'If enabled, the response can include dynamic data from the current context.';
$string['dynamicresponse'] = 'Dynamic response';
$string['dynamicresponse_help'] = 'If enabled, the response contains placeholders that will be replaced with real data.';
$string['requiredcontext'] = 'Required context';
$string['requiredcontext_help'] = 'Specify where this rule should apply. "Any" means it applies everywhere.';
$string['anycontext'] = 'Any context';
$string['sitecontext'] = 'Site level only';
$string['coursecontext'] = 'Course level only';
$string['activitycontext'] = 'Activity level only';

// Themes.
$string['managethemes'] = 'Manage Themes';
$string['addtheme'] = 'Add Theme';
$string['edittheme'] = 'Edit Theme';
$string['deletetheme'] = 'Delete Theme';
$string['themename'] = 'Theme Name';
$string['themecreated'] = 'Theme created successfully';
$string['themeupdated'] = 'Theme updated successfully';
$string['themedeleted'] = 'Theme deleted successfully';
$string['themesetasdefault'] = 'Theme set as default';
$string['setasdefault'] = 'Set as default';
$string['cannotdeletedefault'] = 'Cannot delete the default theme. Set another theme as default first.';
$string['nothemes'] = 'No themes found';
$string['colors'] = 'Colors';
$string['primarycolor_help'] = 'Main accent color for the widget header and buttons.';
$string['secondarycolor'] = 'Secondary Color';
$string['secondarycolor_help'] = 'Secondary accent color for hover states and accents.';
$string['textcolor'] = 'Text Color';
$string['textcolor_help'] = 'Color for text content in the widget.';
$string['backgroundcolor'] = 'Background Color';
$string['backgroundcolor_help'] = 'Background color for the chat area.';
$string['usercolor'] = 'User Message Color';
$string['usercolor_help'] = 'Background color for user message bubbles.';
$string['botcolor'] = 'Bot Message Color';
$string['botcolor_help'] = 'Background color for bot message bubbles.';
$string['invalidcolor'] = 'Invalid color format. Use hex format (e.g., #FF5500).';

// v1.8.1 - Widget Icon Customization.
$string['widgeticonheading'] = 'Widget Icon';
$string['widgeticontype'] = 'Icon type';
$string['widgeticontype_help'] = 'Select the type of icon that will appear in the widget header.';
$string['icontype_default'] = 'Default icon (globe)';
$string['icontype_emoji'] = 'Emoji';
$string['icontype_bootstrap'] = 'Bootstrap Icons';
$string['icontype_custom'] = 'Custom image';
$string['widgeticonemoji'] = 'Emoji';
$string['widgeticonemoji_help'] = 'Enter a Unicode emoji (e.g., 🤖, 💬, 🎓)';
$string['widgeticonbs'] = 'Bootstrap Icons class';
$string['widgeticonbs_help'] = 'Enter the Bootstrap Icons class without the "bi-" prefix (e.g., robot, chat-dots, mortarboard). See https://icons.getbootstrap.com for all available icons.';
$string['widgeticonfile'] = 'Icon file';
$string['widgeticonfile_help'] = 'Upload a PNG, SVG, JPG or GIF image. Recommended size: 32x32px. Maximum: 100KB.';

// v1.8.1 - Mascot Customization.
$string['mascotheading'] = 'Chatbot Mascot';
$string['mascotenabled'] = 'Enable mascot';
$string['mascotenabled_help'] = 'Show an animated mascot in the widget that reacts based on bot state and helps users with suggestions and frequent questions.';
$string['mascottype'] = 'Mascot type';
$string['mascottype_help'] = 'Select the mascot design. Each mascot has its own SVG animations.';
$string['mascot_none'] = 'No mascot';
$string['mascot_clippy'] = 'Clippy (paperclip)';
$string['mascot_robot'] = 'Friendly robot';
$string['mascot_owl'] = 'Academic owl';
$string['mascot_cat'] = 'Studious cat';
$string['mascot_lightbulb'] = 'Idea lightbulb';
$string['mascot_custom'] = 'Custom mascot (SVG)';
$string['mascotfile'] = 'Mascot SVG file';
$string['mascotfile_help'] = 'Upload an SVG file with specific structure (viewBox 80x80, IDs: body, eyes, arms). The SVG must have CSS-animatable elements. Maximum: 50KB.';
$string['mascot_aria_label'] = 'Bot assistant mascot';

// v1.8.1 - Mascot messages.
$string['mascot_greeting'] = 'Hi! How can I help you?';
$string['mascot_needmore'] = 'Need anything else?';
$string['mascot_tryagain'] = 'Try rephrasing your question or click me for suggestions';
$string['mascot_nopopular'] = 'No popular questions yet';
$string['mascot_error'] = 'Could not load questions';
$string['mascot_popularheader'] = 'Popular questions:';
$string['mascot_similarheader'] = 'Did you mean:';
$string['mascot_suggest_tasks'] = 'Need help with your tasks?';
$string['mascot_suggest_grades'] = 'I can show your grades';
$string['mascot_suggest_calendar'] = 'Want to see the calendar?';
$string['mascot_suggest_course'] = 'Ask me about your course';
$string['mascot_suggest_help'] = 'Click me for popular questions!';

// v1.9.0 - Conversation History and Persistence.
$string['history_heading'] = 'Conversation History';
$string['enablehistory'] = 'Enable conversation history';
$string['enablehistory_desc'] = 'Save and restore conversation history for users. Users can resume previous conversations when reopening the chat.';
$string['historyretention'] = 'History retention period';
$string['historyretention_desc'] = 'How long to keep conversation logs in the database. Older records will be automatically deleted by a scheduled task.';
$string['retention_forever'] = 'Keep forever';
$string['retention_1week'] = '1 week';
$string['retention_1month'] = '1 month';
$string['retention_3months'] = '3 months';
$string['retention_6months'] = '6 months';
$string['retention_1year'] = '1 year';
$string['task_cleanup_history'] = 'Cleanup old conversation history';
$string['task_analyze_feedback'] = 'Analyze feedback and flag problematic rules';
$string['previousconversation'] = 'Previous conversation';
$string['historydeleted'] = 'Conversation history deleted';
$string['recorddeleted'] = 'Record deleted';
$string['recordnotfound'] = 'Record not found';
$string['chatconversations'] = 'Chat Conversations';

// v1.9.0 - Inactivity Timeout.
$string['timeout_heading'] = 'Inactivity Timeout';
$string['inactivitytimeout'] = 'Inactivity timeout';
$string['inactivitytimeout_desc'] = 'Automatically minimize the chat after this period of inactivity (in milliseconds). Set to 0 to disable. Default: 600000 (10 minutes).';
$string['inactivity_warning'] = 'Chat will close soon due to inactivity';
$string['chat_closed_inactivity'] = 'Chat has been closed due to inactivity. Click to reopen.';
$string['keepchatopen'] = 'Keep chat open';
$string['loadinghistory'] = 'Loading history...';

// v1.9.0 - Archetype-based greetings.
$string['mascot_greeting_student'] = 'Hi student! Do you have questions about your tasks or grades?';
$string['mascot_greeting_teacher'] = 'Hello teacher! How can I assist you today?';
$string['mascot_greeting_editingteacher'] = 'Hi! Need help setting up your course?';
$string['mascot_greeting_coursecreator'] = 'Hello course creator! Working on a new course?';
$string['mascot_greeting_manager'] = 'Welcome administrator! How can I help you manage the system?';
$string['mascot_greeting_guest'] = 'Welcome! As a guest, I can answer general questions.';
$string['mascot_greeting_user'] = 'Hi! How can I help you today?';

// v1.9.0 - Archetype-based suggestions.
// Student suggestions.
$string['mascot_suggest_deadlines'] = 'Check your upcoming deadlines';
// Teacher suggestions.
$string['mascot_suggest_grading'] = 'Need help with grading?';
$string['mascot_suggest_students'] = 'Questions about your students?';
$string['mascot_suggest_attendance'] = 'View attendance reports';
// Editing teacher suggestions.
$string['mascot_suggest_activities'] = 'Add activities to your course';
// Course creator suggestions.
$string['mascot_suggest_newcourse'] = 'Create a new course?';
$string['mascot_suggest_templates'] = 'Use course templates';
$string['mascot_suggest_categories'] = 'Organize course categories';
// Manager suggestions.
$string['mascot_suggest_reports'] = 'View system reports?';
$string['mascot_suggest_admin'] = 'Admin dashboard help?';
$string['mascot_suggest_users'] = 'Manage users';
$string['mascot_suggest_settings'] = 'Site configuration';
// Guest suggestions.
$string['mascot_suggest_browse'] = 'Browse available courses';
$string['mascot_suggest_login'] = 'Log in for more features';
// User suggestions.
$string['mascot_suggest_profile'] = 'Update your profile';
$string['mascot_suggest_courses'] = 'Explore available courses';

// v1.9.0 - Privacy strings for GDPR compliance.
$string['privacy:metadata:log'] = 'Chat conversation log storing user questions and bot responses.';
$string['privacy:metadata:log:userid'] = 'The ID of the user who asked the question.';
$string['privacy:metadata:log:question'] = 'The question asked by the user.';
$string['privacy:metadata:log:response'] = 'The response provided by the bot.';
$string['privacy:metadata:log:ruleid'] = 'The ID of the rule that matched the question.';
$string['privacy:metadata:log:confidence'] = 'The confidence score of the match (0-1).';
$string['privacy:metadata:log:matched'] = 'Whether a matching rule was found.';
$string['privacy:metadata:log:timecreated'] = 'The timestamp when the conversation occurred.';

// v1.9.2 - Archetypes for rule filtering.
$string['archetypes'] = 'Allowed role archetypes';
$string['archetypes_help'] = 'Select which role archetypes can see this rule. Leave empty for all users. These are Moodle role archetypes, not the role names which can be customized.';
$string['archetype_student'] = 'Student';
$string['archetype_teacher'] = 'Teacher (non-editing)';
$string['archetype_editingteacher'] = 'Teacher (editing)';
$string['archetype_coursecreator'] = 'Course creator';
$string['archetype_manager'] = 'Manager / Administrator';
$string['archetype_guest'] = 'Guest';
$string['archetype_user'] = 'Authenticated user (no specific role)';

// v2.0.0 - Commercial Features.
// Export.
$string['exportchat'] = 'Export conversation';
$string['exportchat_desc'] = 'Download the current conversation as a text file';

// Feedback.
$string['feedback_heading'] = 'User Feedback';
$string['enablefeedback'] = 'Enable feedback';
$string['enablefeedback_desc'] = 'Allow users to rate bot responses with thumbs up/down. This helps improve the knowledge base.';
$string['feedback_helpful'] = 'Helpful';
$string['feedback_nothelpful'] = 'Not helpful';
$string['feedback_thanks'] = 'Thanks for your feedback!';
$string['rulenotfound'] = 'Rule not found';

// Sound notifications.
$string['sound_heading'] = 'Sound Notifications';
$string['enablesound'] = 'Enable notification sound';
$string['enablesound_desc'] = 'Play a notification sound when the bot responds. Users can override this in their browser.';

// Typing indicator.
$string['typing'] = 'is typing...';

// Privacy metadata for feedback.
$string['privacy:metadata:feedback'] = 'Feedback submitted by users on bot responses.';
$string['privacy:metadata:feedback:userid'] = 'The ID of the user who submitted feedback.';
$string['privacy:metadata:feedback:ruleid'] = 'The ID of the rule being rated.';
$string['privacy:metadata:feedback:helpful'] = 'Whether the response was marked as helpful.';
$string['privacy:metadata:feedback:timecreated'] = 'When the feedback was submitted.';

// v2.0.0 - Error messages.
$string['error_noresponse'] = 'Error: No response received.';
$string['error_connection'] = 'Error: Could not connect to server.';

// v3.0.1 - Widget rewrite.
$string['retrying'] = 'Retrying...';

// v2.0.0 - Export strings.
$string['export_header'] = 'Conversation with {$a}';
$string['export_datetime'] = 'Exported: {$a}';
$string['export_you'] = 'You';
$string['export_footer'] = 'End of conversation';
$string['export_filename'] = 'chat-export';

// v2.2.2 - Shortcuts with descriptions.
$string['shortcuts_title'] = 'Quick actions';

// v2.2.7 - New admin action types.
$string['actiontype_teacher_grades'] = 'Manage course grades';
$string['actiontype_admin_users'] = 'Site user management';
$string['actiontype_admin_courses'] = 'Course administration';
$string['actiontype_admin_reports'] = 'Site reports and statistics';
$string['actiontype_admin_settings'] = 'Site configuration';
$string['actiontype_admin_plugins'] = 'Plugin management';
$string['actiontype_admin_security'] = 'Site security';
$string['actiontype_admin_backup'] = 'Backup management';

// v2.2.7 - Admin shortcut headers.
$string['shortcut_teachergradesheader'] = 'Grade management for {$a}:';
$string['shortcut_adminusersheader'] = 'Site user management:';
$string['shortcut_admincoursesheader'] = 'Course administration:';
$string['shortcut_adminreportsheader'] = 'Site reports:';
$string['shortcut_adminsettingsheader'] = 'Site configuration:';
$string['shortcut_adminpluginsheader'] = 'Plugin management:';
$string['shortcut_adminsecurityheader'] = 'Site security:';
$string['shortcut_adminbackupheader'] = 'Backup management:';

// v2.2.7 - Teacher grade management strings.
$string['viewgraderreport'] = 'View grader report';
$string['gradebooksetup'] = 'Gradebook setup';
$string['importgrades'] = 'Import grades';
$string['exportgrades'] = 'Export grades';

// v2.2.7 - User management strings.
$string['totalusers'] = 'Active users on site: {$a}';
$string['browseusers'] = 'Browse users';
$string['addnewuser'] = 'Add new user';
$string['uploadusers'] = 'Upload users';
$string['managecohorts'] = 'Manage cohorts';

// v2.2.7 - Course management strings.
$string['totalcourses'] = 'Total courses: {$a}';
$string['managecourses'] = 'Manage courses and categories';
$string['addnewcourse'] = 'Create new course';
$string['managecategories'] = 'Manage categories';
$string['restorecourse'] = 'Restore course';

// v2.2.7 - Reports strings.
$string['viewlogs'] = 'View system logs';
$string['viewlivelogs'] = 'View live logs';
$string['activityreport'] = 'Activity report';
$string['viewstatistics'] = 'View statistics';
$string['configchanges'] = 'Configuration changes';

// v2.2.7 - Settings strings.
$string['siteadministration'] = 'Site administration';
$string['frontpagesettings'] = 'Front page settings';
$string['appearancesettings'] = 'Appearance settings';
$string['languagesettings'] = 'Language settings';
$string['notificationsettings'] = 'Notification settings';

// v2.2.7 - Plugin strings.
$string['pluginsoverview'] = 'Plugins overview';
$string['installplugins'] = 'Install plugins';
$string['manageactivities'] = 'Manage activity modules';
$string['manageauthentication'] = 'Manage authentication';
$string['manageenrolments'] = 'Manage enrolment methods';

// v2.2.7 - Security strings.
$string['sitepolicies'] = 'Site policies';
$string['httpsecurity'] = 'HTTP security';
$string['ipblocker'] = 'IP blocker';
$string['securitynotifications'] = 'Security notifications';
$string['securityreport'] = 'Security report';

// v2.2.7 - Backup strings.
$string['backupsettings'] = 'Backup settings';
$string['automatedbackups'] = 'Automated backups';
$string['restoresite'] = 'Restore site/course';
$string['importcourse'] = 'Import course';

// v3.1.0 - Bot responses externalized.
// Greetings by time of day.
$string['greeting_morning'] = 'Good morning';
$string['greeting_afternoon'] = 'Good afternoon';
$string['greeting_evening'] = 'Good evening';

// Greeting responses (array of variants).
$string['greeting_response_1'] = '{$a->greeting}, {$a->firstname}! How can I help you today?';
$string['greeting_response_2'] = 'Hello {$a->firstname}! I\'m here to help you.';
$string['greeting_response_3'] = '{$a->greeting}! What do you need to know?';
$string['greeting_response_4'] = 'Hello! How can I assist you today?';

// Farewell responses.
$string['farewell_response_1'] = 'Goodbye! If you need anything else, I\'ll be here.';
$string['farewell_response_2'] = 'Bye! It was nice helping you.';
$string['farewell_response_3'] = 'See you! Don\'t hesitate to come back when you need help.';
$string['farewell_response_4'] = 'Goodbye! Have a great day.';

// Thanks responses.
$string['thanks_response_1'] = 'You\'re welcome! Glad I could help. Need anything else?';
$string['thanks_response_2'] = 'My pleasure! I\'m here for whatever you need.';
$string['thanks_response_3'] = 'No problem! Can I help with anything else?';
$string['thanks_response_4'] = 'Happy to help! If you have more questions, just ask.';

// Empathetic responses for frustrated users.
$string['empathetic_response_1'] = 'I understand your frustration, {$a}. Let me help you with this.';
$string['empathetic_response_2'] = 'I\'m sorry you\'re having difficulties. Let\'s solve this together.';
$string['empathetic_response_3'] = 'Don\'t worry, I\'m here to help. Can you explain what\'s happening?';

// Fallback responses when no match found.
$string['fallback_response_1'] = 'I\'m not sure I understand your question. Could you rephrase it?';
$string['fallback_response_2'] = 'Hmm, I couldn\'t find information about that. Can you be more specific?';
$string['fallback_response_3'] = 'Sorry, I don\'t have an answer for that. Shall we try different words?';
$string['fallback_suggestions'] = 'Perhaps you meant:';

// Follow-up responses.
$string['affirmation_response'] = 'Perfect! What else can I help you with?';
$string['negation_response'] = 'Understood. If you need anything else, just ask.';

// Quick start options.
$string['option_view_tasks'] = 'View my tasks';
$string['option_view_grades'] = 'Check grades';
$string['option_view_calendar'] = 'View calendar';
$string['option_task_problem'] = 'I have a problem with a task';
$string['option_access_problem'] = 'I can\'t access something';
$string['option_contact_teacher'] = 'I need to contact the teacher';

// Topic suggestions.
$string['suggestion_pending_tasks'] = 'What tasks do I have pending?';
$string['suggestion_next_deadline'] = 'When is my next task due?';
$string['suggestion_current_grade'] = 'What is my current grade?';
$string['suggestion_my_grades'] = 'How are my grades?';
$string['suggestion_week_events'] = 'What events do I have this week?';
$string['suggestion_next_exam'] = 'When is the next exam?';
$string['suggestion_who_teacher'] = 'Who is the teacher?';
$string['suggestion_course_info'] = 'Course information';
$string['suggestion_my_tasks'] = 'What tasks do I have?';
$string['suggestion_my_calendar'] = 'View my calendar';
$string['suggestion_my_grades_alt'] = 'Check grades';

// Follow-up suggestions by topic.
$string['followup_assignment_1'] = 'When is this task due?';
$string['followup_assignment_2'] = 'What other tasks do I have pending?';
$string['followup_assignment_3'] = 'How can I submit the task?';
$string['followup_grades_1'] = 'What is my overall average?';
$string['followup_grades_2'] = 'What grades am I missing?';
$string['followup_grades_3'] = 'How can I improve my grade?';
$string['followup_calendar_1'] = 'What events are this week?';
$string['followup_calendar_2'] = 'When is the next exam?';
$string['followup_calendar_3'] = 'What are my upcoming deadlines?';
$string['followup_course_1'] = 'Who is the teacher of this course?';
$string['followup_course_2'] = 'When does the course end?';
$string['followup_course_3'] = 'What topics are left to cover?';
$string['followup_general_1'] = 'What tasks do I have pending?';
$string['followup_general_2'] = 'What is my current grade?';
$string['followup_general_3'] = 'What events do I have coming up?';

// Context prompts.
$string['context_follow_up_prompts'] = 'anything else,need,would you like,want me to,can I help';

// v3.8.0 - Widget UI Improvements.
// Scroll button.
$string['scrolltobottom'] = 'Scroll to bottom';

// Dark mode.
$string['toggledarkmode'] = 'Toggle dark mode';

// Keyboard shortcuts.
$string['tosend'] = 'to send';

// Time separators.
$string['today'] = 'Today';
$string['yesterday'] = 'Yesterday';
