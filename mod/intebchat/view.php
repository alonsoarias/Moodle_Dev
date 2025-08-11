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
 * Prints a particular instance of intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // intebchat instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('intebchat', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $intebchat  = $DB->get_record('intebchat', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $intebchat  = $DB->get_record('intebchat', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $intebchat->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('intebchat', $intebchat->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$event = \mod_intebchat\event\course_module_viewed::create(array(
    'objectid' => $PAGE->cm->instance,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot($PAGE->cm->modname, $intebchat);
$event->trigger();

// Print the page header.
$PAGE->set_url('/mod/intebchat/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($intebchat->name));
$PAGE->set_heading(format_string($course->fullname));

// Check if API key is configured.
$config = get_config('mod_intebchat');
$apiconfig = intebchat_get_api_config($intebchat);
$apikey_configured = !empty($apiconfig['apikey']);

// Check token limit for current user
$token_limit_info = intebchat_check_token_limit($USER->id);

// Prepare data for JavaScript
$persistconvo = $intebchat->persistconvo && $config->allowinstancesettings ? $intebchat->persistconvo : $config->persistconvo;
$api_type = $config->type ?: 'chat';

// Pass data to JavaScript
$jsdata = [
    'instanceId' => $intebchat->id,
    'api_type' => $api_type,
    'persistConvo' => $persistconvo,
    'tokenLimitEnabled' => !empty($config->enabletokenlimit),
    'tokenLimit' => $token_limit_info['limit'],
    'tokensUsed' => $token_limit_info['used'],
    'tokenLimitExceeded' => !$token_limit_info['allowed'],
    'resetTime' => $token_limit_info['reset_time'],
    'audioEnabled' => !empty($intebchat->enableaudio),
    'audioMode' => $intebchat->audiomode ?? 'text'
];

$PAGE->requires->js_call_amd('mod_intebchat/lib', 'init', [$jsdata]);
if (!empty($intebchat->enableaudio)) {
    $PAGE->requires->js_call_amd('mod_intebchat/audio', 'init', [$intebchat->audiomode]);
}

// Add CSS
$PAGE->requires->css('/mod/intebchat/styles/styles.css');

// Output starts here
echo $OUTPUT->header();

// Show activity name and description
echo $OUTPUT->heading($intebchat->name);

if ($intebchat->intro) {
    echo $OUTPUT->box(format_module_intro('intebchat', $intebchat, $cm->id), 'generalbox mod_introbox', 'intebchatintro');
}

// Get assistant and user names
$assistantname = $intebchat->assistantname ?: ($config->assistantname ?: get_string('defaultassistantname', 'mod_intebchat'));
$username = $USER->firstname ?: get_string('defaultusername', 'mod_intebchat');

// Get user's conversations for this activity
$conversations = [];
if ($persistconvo && isloggedin()) {
    $conversations = intebchat_get_user_conversations($intebchat->id, $USER->id);
}

// Format conversations for template
$formatted_conversations = [];
foreach ($conversations as $conv) {
    $formatted_conversations[] = [
        'id' => $conv->id,
        'title' => format_string($conv->title, true, ['context' => $PAGE->context]),
        'preview' => format_string($conv->preview, true, ['context' => $PAGE->context]),
        'lastmessage_formatted' => userdate($conv->lastmessage, '%d/%m')
    ];
}

// Calculate token percentage
$percentage = ($token_limit_info['limit'] > 0) ? 
    ($token_limit_info['used'] / $token_limit_info['limit'] * 100) : 0;

// Determine progress bar class
$progress_class = '';
if ($percentage >= 100) {
    $progress_class = ' danger';
} elseif ($percentage > 90) {
    $progress_class = ' danger';
} elseif ($percentage > 75) {
    $progress_class = ' warning';
}

// Audio mode settings
$showTextarea = ($intebchat->audiomode === 'text' || $intebchat->audiomode === 'both');
$showAudio = !empty($intebchat->enableaudio) && ($intebchat->audiomode === 'audio' || $intebchat->audiomode === 'both');

// Prepare template context
$templatecontext = [
    'instanceid' => $intebchat->id,
    'assistantname' => format_string($assistantname, true, ['context' => $PAGE->context]),
    'username' => format_string($username, true, ['context' => $PAGE->context]),
    'showlabels' => $intebchat->showlabels,
    'apikey_configured' => $apikey_configured,
    'apikeymissing' => get_string('apikeymissing', 'mod_intebchat'),
    'conversations' => $formatted_conversations,
    'hasconversations' => !empty($formatted_conversations),
    'noconversations' => get_string('noconversations', 'mod_intebchat'),
    'searchconversations' => get_string('searchconversations', 'mod_intebchat'),
    'newconversation' => get_string('newconversation', 'mod_intebchat'),
    'edittitle' => get_string('edittitle', 'mod_intebchat'),
    'clearconversation' => get_string('clearconversation', 'mod_intebchat'),
    'askaquestion' => get_string('askaquestion', 'mod_intebchat'),
    'token_limit_exceeded' => !$token_limit_info['allowed'],
    'token_limit_message' => !$token_limit_info['allowed'] ? get_string('tokenlimitexceeded', 'mod_intebchat', [
        'used' => $token_limit_info['used'],
        'limit' => $token_limit_info['limit'],
        'reset' => userdate($token_limit_info['reset_time'])
    ]) : '',
    'token_limit_enabled' => !empty($config->enabletokenlimit),
    'tokens_used' => $token_limit_info['used'],
    'tokens_limit' => $token_limit_info['limit'],
    'tokens_percentage' => number_format($percentage, 1),
    'tokens_percentage_capped' => min($percentage, 100),
    'progress_class' => $progress_class,
    'tokens_used_label' => get_string('tokensused', 'mod_intebchat', [
        'used' => $token_limit_info['used'],
        'limit' => $token_limit_info['limit']
    ]),
    'show_reset_time' => $token_limit_info['reset_time'] > time(),
    'tokens_reset_label' => get_string('tokensreset', 'mod_intebchat', 
        userdate($token_limit_info['reset_time'], '%H:%M')),
    'logging_enabled' => $config->logging,
    'loggingenabled' => get_string('loggingenabled', 'mod_intebchat'),
    'showTextarea' => $showTextarea,
    'showAudio' => $showAudio,
    'recordaudio' => get_string('recordaudio', 'mod_intebchat'),
    'stoprecording' => get_string('stoprecording', 'mod_intebchat'),
    'switchtheme' => get_string('switchtheme', 'mod_intebchat')
];

// Render the chat interface using Mustache template
echo $OUTPUT->render_from_template('mod_intebchat/chat', $templatecontext);

// Finish the page
echo $OUTPUT->footer();