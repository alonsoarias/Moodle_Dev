<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Educam Custom Login';

// General
$string['general_heading'] = 'General Settings';
$string['enabled'] = 'Enable custom login';
$string['enabled_desc'] = 'Enable the Educam custom login page';

// Layout selection
$string['layout_heading'] = 'Layout Selection';
$string['layout_heading_desc'] = 'Choose the layout you want to use for your login page';
$string['layout'] = 'Login layout';
$string['layout_desc'] = 'Select between the two available layouts';
$string['layout1'] = 'Layout 1: Two columns (Image left + Form right)';
$string['layout2'] = 'Layout 2: Centered with full background';

// Images
$string['images_heading'] = 'Image Settings';
$string['images_heading_desc'] = 'Upload images for your login page. If you don\'t upload an image, a placeholder will be used.';
$string['background_image'] = 'Background image';
$string['background_image_desc'] = 'Background image for the login page. <strong>CRITICAL for Layout 2</strong>. Recommended dimensions: 1920x1080px or higher.';
$string['team_photo'] = 'Team photo';
$string['team_photo_desc'] = 'Photo of students or team that appears on the left. <strong>Only for Layout 1</strong>. Recommended dimensions: 800x600px or higher.';
$string['logo_educam'] = 'Educam logo';
$string['logo_educam_desc'] = 'Educam logo. Recommended dimensions: 210x66px (Layout 1) or 160x50px (Layout 2). Use PNG with transparent background or SVG.';
$string['logo_americas'] = 'Americas logo';
$string['logo_americas_desc'] = 'Americas Business Process logo. Recommended dimensions: 180x40px. Use PNG with transparent background or SVG.';

// Colors
$string['colors_heading'] = 'Color Settings';
$string['colors_heading_desc'] = 'Customize login colors. Colors are applied using CSS variables.';
$string['bg_color'] = 'Background color (Layout 1)';
$string['bg_color_desc'] = 'Main background color for Layout 1';
$string['panel_top'] = 'Panel top color';
$string['panel_top_desc'] = 'Top color of the panel gradient';
$string['panel_mid'] = 'Panel mid color';
$string['panel_mid_desc'] = 'Middle color of the panel gradient';
$string['panel_bottom'] = 'Panel bottom color';
$string['panel_bottom_desc'] = 'Bottom color of the panel gradient';
$string['field_bg'] = 'Field background color';
$string['field_bg_desc'] = 'Background color for text fields (username and password)';
$string['link_color'] = 'Link color';
$string['link_color_desc'] = 'Color for links and labels';
$string['btn_color'] = 'Button color';
$string['btn_color_desc'] = 'Color of the "Login" button';
$string['btn_hover'] = 'Button hover color';
$string['btn_hover_desc'] = 'Color of the "Login" button on hover';

// Texts
$string['texts_heading'] = 'Text Settings';
$string['welcome_text'] = 'Welcome text';
$string['welcome_text_desc'] = 'Large welcome text';
$string['copyright_text'] = 'Copyright text';
$string['copyright_text_desc'] = 'Footer text displayed at the bottom of the login page. Supports HTML formatting.';
$string['forgotpassword_url'] = 'Password recovery link';
$string['forgotpassword_url_desc'] = 'Full URL used for the "Forgot your password?" link. Leave empty to hide the link.';

// reCAPTCHA
$string['recaptcha_heading'] = 'Google reCAPTCHA Settings';
$string['recaptcha_heading_desc'] = 'Configure Google reCAPTCHA v3 to protect your login page against bots';
$string['recaptcha_sitekey'] = 'reCAPTCHA Site Key';
$string['recaptcha_sitekey_desc'] = 'Google reCAPTCHA v3 Site Key';
$string['recaptcha_secretkey'] = 'reCAPTCHA Secret Key';
$string['recaptcha_secretkey_desc'] = 'Google reCAPTCHA v3 Secret Key';
$string['recaptcha_action'] = 'Expected action';
$string['recaptcha_action_desc'] = 'Action name sent to Google when executing reCAPTCHA. It must match the action configured in the reCAPTCHA admin console.';
$string['recaptcha_threshold'] = 'Minimum score';
$string['recaptcha_threshold_desc'] = 'Minimum acceptable score (0-1). Requests returning a lower score will be rejected.';
$string['recaptcha_whitelist'] = 'reCAPTCHA IP whitelist';
$string['recaptcha_whitelist_desc'] = 'List IP addresses or CIDR ranges (one per line or comma separated) that bypass reCAPTCHA verification. Lines starting with # are ignored. Supports IPv4 and IPv6.';

// Form strings
$string['username'] = 'Username';
$string['password'] = 'Password';
$string['forgotpassword'] = 'Forgot your password?';
$string['login'] = 'Login';
$string['showpassword'] = 'Show password';
$string['hidepassword'] = 'Hide password';
$string['skiptoform'] = 'Skip to login form';
$string['logininstructions'] = 'Enter your username and password to access the platform.';

// Error messages
$string['loginerror'] = 'Invalid username or password. Please try again.';
$string['recaptchaerror'] = 'reCAPTCHA verification failed. Please try again.';
