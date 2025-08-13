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
 * English language strings for intebchat
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General strings
$string['pluginname'] = 'INTEB Chat';
$string['modulename'] = 'INTEB Chat';
$string['modulenameplural'] = 'INTEB Chats';
$string['intebchat'] = 'intebchat';
$string['intebchatname'] = 'Chat name';
$string['intebchatname_help'] = 'This is the name that will appear on the course page.';
$string['intebchat:view'] = 'View INTEB Chat';
$string['intebchat:addinstance'] = 'Add a new INTEB Chat';
$string['intebchat:viewownconversations'] = 'View own conversations';
$string['intebchat:viewstudentconversations'] = 'View student conversations';
$string['intebchat:viewallconversations'] = 'View all conversations';
$string['intebchat:managetokenlimits'] = 'Manage token limits';
$string['pluginadministration'] = 'INTEB Chat administration';
$string['noopenaichats'] = 'No INTEB Chats in this course';

// Conversation management
$string['newconversation'] = 'New conversation';
$string['conversations'] = 'Conversations';
$string['clearconversation'] = 'Clear conversation';
$string['edittitle'] = 'Edit title';
$string['conversationtitle'] = 'Conversation title';
$string['searchconversations'] = 'Search conversations...';
$string['noconversations'] = 'No conversations yet';
$string['confirmclear'] = 'Are you sure you want to clear this conversation?';
$string['confirmclearmessage'] = 'Are you sure you want to clear this conversation? This action cannot be undone.';
$string['conversationcleared'] = 'Conversation cleared';
$string['loadingconversation'] = 'Loading conversation...';
$string['defaultconversation'] = 'Migrated conversation';
$string['errorloadingconversation'] = 'Error loading conversation';
$string['errorclearingconversation'] = 'Error clearing conversation';
$string['conversationtitleupdated'] = 'Title updated successfully';

// Settings strings
$string['generalsettings'] = 'General settings';
$string['generalsettingsdesc'] = 'These settings apply to all INTEB Chat instances on the site.';
$string['apikey'] = 'OpenAI API Key';
$string['apikeydesc'] = 'The API key from your OpenAI account';
$string['apikeymissing'] = 'Please configure your OpenAI API key in the plugin settings.';
$string['type'] = 'API Type';
$string['typedesc'] = 'Which OpenAI API to use';
$string['assistant'] = 'Assistant';
$string['assistantdesc'] = 'The assistant to use if using the Assistants API';
$string['persistconvo'] = 'Persist conversation';
$string['persistconvodesc'] = 'Persist the user conversation between sessions';
$string['noassistants'] = 'No assistants available. Please create one in your OpenAI account.';

// Instance settings
$string['chatsettings'] = 'Chat settings';
$string['showlabels'] = 'Show name labels';
$string['sourceoftruth'] = 'Source of truth';
$string['config_sourceoftruth'] = 'Information that the AI should use as a basis for its responses';
$string['config_sourceoftruth_help'] = 'Provide specific information that the AI should consider as factual and prioritize in its responses. This helps ensure accurate and consistent answers.';
$string['prompt'] = 'Custom prompt';
$string['config_prompt'] = 'Additional instructions to customize AI behavior';
$string['config_prompt_help'] = 'Provide specific instructions to guide how the AI should respond. This will be added to the system prompt.';
$string['config_instructions'] = 'Custom instructions for the assistant';
$string['config_instructions_help'] = 'Provide specific instructions for the assistant. These instructions will override the assistant\'s default instructions for this instance.';
$string['assistantname'] = 'Assistant name';
$string['config_assistantname'] = 'How the assistant name will be displayed in the chat';
$string['config_assistantname_help'] = 'Enter a custom name for the assistant. This name will be displayed in the chat interface instead of the default name.';
$string['advanced'] = 'Advanced settings';

// Help strings for instance settings
$string['config_assistant'] = 'Assistant';
$string['config_assistant_help'] = 'Select which assistant to use from your OpenAI account. You must have created assistants in your OpenAI account first.';
$string['config_persistconvo'] = 'Persist conversation';
$string['config_persistconvo_help'] = 'If enabled, the conversation history will be maintained between sessions. Users can continue previous conversations.';
$string['config_apikey'] = 'API Key (instance level)';
$string['config_apikey_help'] = 'Optionally provide an instance-specific API key. This will override the global API key for this instance only.';

// Model settings
$string['model'] = 'Model';
$string['config_model'] = 'Which OpenAI model to use';
$string['config_model_help'] = 'Select the AI model. Different models have different capabilities and costs. GPT-4 models are more capable but more expensive.';
$string['temperature'] = 'Temperature';
$string['config_temperature'] = 'Controls randomness (0-2)';
$string['config_temperature_help'] = 'Controls the randomness of responses. Lower values (0.0-0.5) make responses more focused and deterministic. Higher values (0.5-2.0) make responses more creative and varied.';
$string['maxlength'] = 'Max length';
$string['config_maxlength'] = 'Maximum number of tokens in response';
$string['config_maxlength_help'] = 'The maximum number of tokens to generate in the response. One token is roughly 4 characters. Range: 1-4000.';
$string['topp'] = 'Top P';
$string['config_topp'] = 'Nucleus sampling (0-1)';
$string['config_topp_help'] = 'An alternative to temperature sampling. The model considers tokens with top_p probability mass. 0.1 means only the top 10% probability tokens are considered.';
$string['frequency'] = 'Frequency penalty';
$string['config_frequency'] = 'Reduces token repetition (-2 to 2)';
$string['config_frequency_help'] = 'Positive values penalize new tokens based on their existing frequency in the text, decreasing likelihood of repeating the same line.';
$string['presence'] = 'Presence penalty';
$string['config_presence'] = 'Reduces topic repetition (-2 to 2)';
$string['config_presence_help'] = 'Positive values penalize new tokens based on whether they appear in the text so far, increasing likelihood of talking about new topics.';

// Token limit settings
$string['tokenlimitsettings'] = 'Token limit settings';
$string['tokenlimitsettingsdesc'] = 'Control token usage by users';
$string['enabletokenlimit'] = 'Enable token limit';
$string['enabletokenlimitdesc'] = 'Limit the number of tokens users can use';
$string['maxtokensperuser'] = 'Maximum tokens per user';
$string['maxtokensperuserdesc'] = 'Maximum number of tokens a user can use in the specified period';
$string['tokenlimitperiod'] = 'Token limit period';
$string['tokenlimitperioddesc'] = 'The time period for the token limit';
$string['tokensused'] = 'Tokens used: {$a->used} / {$a->limit}';
$string['tokenlimitexceeded'] = 'You have exceeded your token limit. Used: {$a->used}, Limit: {$a->limit}. Resets at: {$a->reset}';
$string['totaltokensused'] = 'Total tokens used: {$a}';
$string['tokensreset'] = 'Tokens will reset at: {$a}';
$string['audiotokens'] = 'Audio tokens used: {$a}';
$string['texttokens'] = 'Text tokens used: {$a}';
$string['totaltokensdetailed'] = 'Total tokens - Text: {$a->text}, Audio: {$a->audio}';
$string['tokenlimitwarning'] = 'Only {$a} tokens remaining!';

// Messages
$string['askaquestion'] = 'Ask a question...';
$string['erroroccurred'] = 'An error occurred! Please try again later.';
$string['new_chat'] = 'New chat';
$string['loggingenabled'] = 'Logging is enabled - your conversations will be saved';
$string['messagecount'] = 'Number of messages: {$a}';
$string['firstmessage'] = 'First message';
$string['lastmessage'] = 'Last message';
$string['nomessages'] = 'No messages';
$string['messages'] = 'Messages';
$string['created'] = 'Created';
$string['transcribing'] = 'Transcribing...';

// Audio mode strings
$string['enableaudio'] = 'Enable audio';
$string['enableaudio_help'] = 'Enable audio features for this chat instance';
$string['enableaudio_desc'] = 'Allow recording and audio responses';
$string['audiomode'] = 'Mode';
$string['audiomode_help'] = 'Select how users can interact with the chat: Text only, Audio only, or Both text and audio';
$string['audiomode_text'] = 'Text only';
$string['audiomode_audio'] = 'Audio only';
$string['audiomode_both'] = 'Text and audio';
$string['voice'] = 'Voice for audio responses';
$string['voice_desc'] = 'Select the default voice for text-to-speech responses. This can be overridden at the instance level.';
$string['voice_help'] = 'Select the voice for text-to-speech responses in this chat instance. Each voice has different characteristics:
• Alloy: Neutral and professional
• Ash: Calm and modern
• Ballad: Narrative and engaging
• Coral: Bright and crisp
• Echo: Warm and conversational
• Fable: Expressive and dynamic
• Onyx: Deep and authoritative
• Nova: Energetic and bright
• Sage: Calm and thoughtful
• Shimmer: Gentle and soothing
• Verse: Poetic and rhythmic

This setting overrides the global default voice for this specific chat instance.';
$string['recordaudio'] = 'Record audio';
$string['stoprecording'] = 'Stop recording';
$string['switchtoaudiomode'] = 'Switch to audio mode';
$string['switchtotextmode'] = 'Switch to text mode';
$string['inputmode'] = 'Input mode';
$string['responsemode'] = 'Response mode';
$string['audioresponse'] = 'Audio response';
$string['textresponse'] = 'Text response';

// Theme strings
$string['switchtheme'] = 'Switch theme';
$string['darkmode'] = 'Dark mode';
$string['lightmode'] = 'Light mode';

// Default strings
$string['defaultassistantname'] = 'Assistant';
$string['defaultusername'] = 'User';
$string['defaultprompt'] = 'You are a helpful assistant.';
$string['sourceoftruthpreamble'] = 'You have been provided with the following information as context:';
$string['sourceoftruthreinforcement'] = ' In your responses, always prioritize the information provided in the context.';

// Validation messages
$string['temperaturerange'] = 'Temperature must be between 0 and 2';
$string['topprange'] = 'Top P must be between 0 and 1';
$string['maxlengthrange'] = 'Max length must be between 1 and 4000';
$string['reasoningmodelwarning'] = 'You have selected model "{$a}". This is an advanced reasoning model designed to solve complex problems through step-by-step analysis. Please note it may have higher cost and response time.';

// Other settings
$string['restrictusage'] = 'Restrict to logged-in users';
$string['restrictusagedesc'] = 'Only logged-in users can use the chat';
$string['logging'] = 'Log conversations';
$string['loggingdesc'] = 'Log all conversations for later analysis';
$string['allowinstancesettings'] = 'Allow per-instance settings';
$string['allowinstancesettingsdesc'] = 'Allow teachers to override global settings on individual instances';
$string['defaultvalues'] = 'Default values';
$string['defaultvaluesdesc'] = 'Default values for new instances';

// API specific headers
$string['chatheading'] = 'Chat API Settings';
$string['chatheadingdesc'] = 'Settings for the Chat API';
$string['assistantheading'] = 'Assistant API Settings';
$string['assistantheadingdesc'] = 'Settings for the Assistant API';

// Privacy
$string['privacy:metadata:intebchat_log'] = 'INTEB Chat conversation logs';
$string['privacy:metadata:intebchat_log:userid'] = 'The ID of the user who sent the message';
$string['privacy:metadata:intebchat_log:instanceid'] = 'The ID of the module instance';
$string['privacy:metadata:intebchat_log:usermessage'] = 'The message sent by the user';
$string['privacy:metadata:intebchat_log:airesponse'] = 'The response generated by the AI';
$string['privacy:metadata:intebchat_log:timecreated'] = 'The time when the message was sent';
$string['privacy:chatmessagespath'] = 'Chat messages';

// OpenAI specific
$string['openaitimedout'] = 'The OpenAI request has timed out. Please try again.';

// Report strings
$string['intebchat_logs'] = 'INTEB Chat logs';
$string['viewreport'] = 'View report';
$string['viewallreports'] = 'View all reports';
$string['userid'] = 'User ID';
$string['username'] = 'Username';
$string['usermessage'] = 'User message';
$string['airesponse'] = 'AI response';
$string['tokens'] = 'Tokens';
$string['prompt'] = 'Prompt';
$string['completion'] = 'Completion';
$string['nopermission'] = 'You do not have permission to view this';

// Agregar estas líneas a lang/en/intebchat.php y lang/es/intebchat.php

// === ENGLISH (lang/en/intebchat.php) ===
// Audio confirmation strings
$string['audiorecorded'] = 'Audio Recorded';
$string['confirmaudiosend'] = 'Do you want to send this audio message?';
$string['playaudio'] = 'Play Audio';
$string['rerecord'] = 'Re-record';
$string['send'] = 'Send';
$string['cancelrecording'] = 'Cancel Recording';
$string['audioduration'] = 'Duration: {$a}';
$string['confirmdelete'] = 'Are you sure you want to delete this recording?';

