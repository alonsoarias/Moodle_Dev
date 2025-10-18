<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Login Personalizado Educam';

// General
$string['general_heading'] = 'Configuración General';
$string['enabled'] = 'Habilitar login personalizado';
$string['enabled_desc'] = 'Habilitar la página de login personalizada de Educam';

// Layout selection
$string['layout_heading'] = 'Selección de Diseño';
$string['layout_heading_desc'] = 'Elige el diseño que deseas usar para tu página de login';
$string['layout'] = 'Diseño de login';
$string['layout_desc'] = 'Selecciona entre los dos diseños disponibles';
$string['layout1'] = 'Layout 1: Dos columnas (Imagen izquierda + Formulario derecha)';
$string['layout2'] = 'Layout 2: Centrado con fondo completo';

// Images
$string['images_heading'] = 'Configuración de Imágenes';
$string['images_heading_desc'] = 'Sube las imágenes para tu página de login. Si no subes una imagen, se usará una imagen de placeholder.';
$string['background_image'] = 'Imagen de fondo';
$string['background_image_desc'] = 'Imagen de fondo para la página de login. <strong>CRÍTICA para Layout 2</strong>. Dimensiones recomendadas: 1920x1080px o superior.';
$string['team_photo'] = 'Fotografía del equipo';
$string['team_photo_desc'] = 'Fotografía de estudiantes o equipo que aparece a la izquierda. <strong>Solo para Layout 1</strong>. Dimensiones recomendadas: 800x600px o superior.';
$string['logo_educam'] = 'Logo Educam';
$string['logo_educam_desc'] = 'Logo de Educam. Dimensiones recomendadas: 210x66px (Layout 1) o 160x50px (Layout 2). Usar PNG con fondo transparente o SVG.';
$string['logo_americas'] = 'Logo Americas';
$string['logo_americas_desc'] = 'Logo de Americas Business Process. Dimensiones recomendadas: 180x40px. Usar PNG con fondo transparente o SVG.';

// Colors
$string['colors_heading'] = 'Configuración de Colores';
$string['colors_heading_desc'] = 'Personaliza los colores del login. Los colores se aplican usando CSS variables.';
$string['bg_color'] = 'Color de fondo (Layout 1)';
$string['bg_color_desc'] = 'Color de fondo principal para Layout 1';
$string['panel_top'] = 'Color superior del panel';
$string['panel_top_desc'] = 'Color de la parte superior del gradiente del panel';
$string['panel_mid'] = 'Color medio del panel';
$string['panel_mid_desc'] = 'Color de la parte media del gradiente del panel';
$string['panel_bottom'] = 'Color inferior del panel';
$string['panel_bottom_desc'] = 'Color de la parte inferior del gradiente del panel';
$string['field_bg'] = 'Color de fondo de los campos';
$string['field_bg_desc'] = 'Color de fondo para los campos de texto (username y password)';
$string['link_color'] = 'Color de los enlaces';
$string['link_color_desc'] = 'Color para enlaces y labels';
$string['btn_color'] = 'Color del botón';
$string['btn_color_desc'] = 'Color del botón "Acceder"';
$string['btn_hover'] = 'Color del botón (hover)';
$string['btn_hover_desc'] = 'Color del botón "Acceder" al pasar el mouse';

// Texts
$string['texts_heading'] = 'Configuración de Textos';
$string['welcome_text'] = 'Texto de bienvenida';
$string['welcome_text_desc'] = 'Texto grande de bienvenida';
$string['copyright_text'] = 'Texto de copyright';
$string['copyright_text_desc'] = 'Texto de copyright que aparece en la parte inferior';

// reCAPTCHA
$string['recaptcha_heading'] = 'Configuración de Google reCAPTCHA';
$string['recaptcha_heading_desc'] = 'Configura Google reCAPTCHA v2 para proteger tu página de login contra bots';
$string['recaptcha_sitekey'] = 'Site Key de reCAPTCHA';
$string['recaptcha_sitekey_desc'] = 'Clave del sitio (Site Key) de Google reCAPTCHA v2';
$string['recaptcha_secretkey'] = 'Secret Key de reCAPTCHA';
$string['recaptcha_secretkey_desc'] = 'Clave secreta (Secret Key) de Google reCAPTCHA v2';

// Form strings
$string['username'] = 'Usuario';
$string['password'] = 'Contraseña';
$string['forgotpassword'] = '¿Olvidó su contraseña?';
$string['login'] = 'Acceder';
$string['showpassword'] = 'Mostrar contraseña';
$string['hidepassword'] = 'Ocultar contraseña';
$string['skiptoform'] = 'Ir al formulario de acceso';
$string['logininstructions'] = 'Ingrese su usuario y contraseña para acceder a la plataforma.';

// Error messages
$string['loginerror'] = 'Usuario o contraseña incorrectos. Por favor, inténtelo de nuevo.';
$string['recaptchaerror'] = 'Por favor, complete la verificación de reCAPTCHA.';
