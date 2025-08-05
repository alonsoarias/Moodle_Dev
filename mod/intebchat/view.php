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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

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

// Add professional CSS
$PAGE->requires->css('/mod/intebchat/styles.css');

// Output starts here
echo $OUTPUT->header();

// Show activity name and description
echo $OUTPUT->heading($intebchat->name);

if ($intebchat->intro) {
    echo $OUTPUT->box(format_module_intro('intebchat', $intebchat, $cm->id), 'generalbox mod_introbox', 'intebchatintro');
}

// Determine name labels visibility
$showlabelscss = '';
if (!$intebchat->showlabels) {
    $showlabelscss = '
        .openai_message:before {
            display: none;
        }
    ';
}

// Get assistant and user names
$assistantname = $intebchat->assistantname ?: ($config->assistantname ?: get_string('defaultassistantname', 'mod_intebchat'));
$username = $USER->firstname ?: get_string('defaultusername', 'mod_intebchat');

// Get user's conversations for this activity
$conversations = [];
if ($config->logging && isloggedin()) {
    $conversations = intebchat_get_user_conversations($intebchat->id, $USER->id);
}

$assistantname = format_string($assistantname, true, ['context' => $PAGE->context]);
$username = format_string($username, true, ['context' => $PAGE->context]);

// Chat interface HTML with conversations sidebar
?>
<div class="mod_intebchat" data-instance-id="<?php echo $intebchat->id; ?>">
    <script>
        var assistantName = "<?php echo addslashes($assistantname); ?>";
        var userName = "<?php echo addslashes($username); ?>";
    </script>

    <style>
        <?php echo $showlabelscss; ?>
        .openai_message.user:before {
            content: "<?php echo addslashes($username); ?>";
        }
        .openai_message.bot:before {
            content: "<?php echo addslashes($assistantname); ?>";
        }
    </style>

    <?php if (!$apikey_configured): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-triangle"></i> <?php echo get_string('apikeymissing', 'mod_intebchat'); ?>
        </div>
    <?php else: ?>
        <div class="intebchat-wrapper">
            <!-- Mobile Toggle Button -->
            <button class="intebchat-mobile-toggle" id="mobile-menu-toggle">
                <i class="fa fa-bars"></i>
            </button>

            <!-- Conversations Sidebar -->
            <div class="intebchat-sidebar" id="conversations-sidebar">
                <div class="intebchat-sidebar-header">
                    <div class="intebchat-sidebar-search">
                        <input type="text" 
                               id="conversation-search" 
                               placeholder="<?php echo get_string('searchconversations', 'mod_intebchat'); ?>" 
                               aria-label="<?php echo get_string('searchconversations', 'mod_intebchat'); ?>">
                        <i class="fa fa-search search-icon"></i>
                    </div>
                    <button class="intebchat-new-conversation" id="new-conversation-btn">
                        <i class="fa fa-plus"></i>
                        <?php echo get_string('newconversation', 'mod_intebchat'); ?>
                    </button>
                </div>
                
                <div class="intebchat-conversations-list" id="conversations-list">
                    <?php if (empty($conversations)): ?>
                        <div class="intebchat-no-conversations">
                            <p><?php echo get_string('noconversations', 'mod_intebchat'); ?></p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $conv): ?>
                            <div class="intebchat-conversation-item" 
                                 data-conversation-id="<?php echo $conv->id; ?>"
                                 data-title="<?php echo s($conv->title); ?>">
                                <div class="intebchat-conversation-title">
                                    <span class="title-text"><?php echo s($conv->title); ?></span>
                                    <span class="intebchat-conversation-date">
                                        <?php echo userdate($conv->lastmessage, '%d/%m'); ?>
                                    </span>
                                </div>
                                <div class="intebchat-conversation-preview">
                                    <?php echo s($conv->preview); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Main Chat Area -->
            <div class="intebchat-main">
                <!-- Chat Header -->
                <div class="intebchat-header">
                    <h3 class="intebchat-header-title" id="conversation-title">
                        <?php echo get_string('newconversation', 'mod_intebchat'); ?>
                    </h3>
                    <div class="intebchat-header-actions">
                        <button class="intebchat-header-btn" id="edit-title-btn" title="<?php echo get_string('edittitle', 'mod_intebchat'); ?>">
                            <i class="fa fa-edit"></i>
                        </button>
                        <button class="intebchat-header-btn" id="clear-conversation-btn" title="<?php echo get_string('clearconversation', 'mod_intebchat'); ?>">
                            <i class="fa fa-trash"></i>
                        </button>
                    </div>
                </div>

                <?php if (!$token_limit_info['allowed']): ?>
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-circle"></i> 
                        <?php echo get_string('tokenlimitexceeded', 'mod_intebchat', [
                            'used' => $token_limit_info['used'],
                            'limit' => $token_limit_info['limit'],
                            'reset' => userdate($token_limit_info['reset_time'])
                        ]); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($config->enabletokenlimit)): ?>
                    <div class="token-usage-info">
                        <div class="token-display">
                            <span class="token-label"><?php echo get_string('tokensused', 'mod_intebchat', [
                                'used' => $token_limit_info['used'],
                                'limit' => $token_limit_info['limit']
                            ]); ?></span>
                        </div>
                        <div class="progress">
                            <div class="progress-bar<?php 
                                $percentage = ($token_limit_info['used'] / $token_limit_info['limit'] * 100);
                                if ($percentage > 90) echo ' danger';
                                elseif ($percentage > 75) echo ' warning';
                            ?>" role="progressbar" 
                                 style="width: <?php echo ($token_limit_info['used'] / $token_limit_info['limit'] * 100); ?>%"
                                 aria-valuenow="<?php echo $token_limit_info['used']; ?>" 
                                 aria-valuemin="0" 
                                 aria-valuemax="<?php echo $token_limit_info['limit']; ?>">
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="intebchat_log" role="log" aria-live="polite">
                    <!-- Messages will be loaded here -->
                </div>
                
                <div id="control_bar">
                    <?php if ($config->logging): ?>
                        <div class="logging-info">
                            <i class="fa fa-info-circle"></i> <?php echo get_string('loggingenabled', 'mod_intebchat'); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="openai_input_bar" id="input_bar">
                        <?php 
                        $showTextarea = ($intebchat->audiomode === 'text' || $intebchat->audiomode === 'both');
                        $showAudio = !empty($intebchat->enableaudio) && ($intebchat->audiomode === 'audio' || $intebchat->audiomode === 'both');
                        ?>
                        
                        <?php if ($showTextarea): ?>
                            <textarea aria-label="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>"
                                      rows="1"
                                      id="openai_input"
                                      placeholder="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>"
                                      name="message"
                                      <?php echo !$token_limit_info['allowed'] ? 'disabled' : ''; ?>></textarea>
                        <?php endif; ?>
                        
                        <?php if ($showAudio): ?>
                            <button class="openai_input_mic_btn" id="intebchat-icon-mic" title="<?php echo get_string('recordaudio', 'mod_intebchat'); ?>">
                                <i class="fa fa-microphone"></i>
                            </button>
                            <button class="openai_input_stop_btn" id="intebchat-icon-stop" title="<?php echo get_string('stoprecording', 'mod_intebchat'); ?>" style="display:none">
                                <i class="fa fa-stop"></i>
                            </button>
                            <input type="hidden" id="intebchat-recorded-audio" name="audio" value="">
                        <?php endif; ?>
                        
                        <?php if ($showTextarea): ?>
                            <button class='openai_input_submit_btn'
                                    title="<?php echo get_string('askaquestion', 'mod_intebchat'); ?>"
                                    id="go"
                                    <?php echo !$token_limit_info['allowed'] ? 'disabled' : ''; ?>>
                                <i class="fa fa-paper-plane"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Finish the page
echo $OUTPUT->footer();