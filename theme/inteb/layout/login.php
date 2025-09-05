<?php
/**
 * Login page layout for theme_inteb
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

defined('MOODLE_INTERNAL') || die();

// Obtener atributos del body y la configuración del tema.
$bodyattributes = $OUTPUT->body_attributes();
$theme = theme_config::load('inteb');

// Para el manejo de archivos.
$fs = get_file_storage();
$context = context_system::instance();

// Construimos el contexto para la plantilla.
$templatecontext = [
    'sitename' => format_string(
        $SITE->fullname,
        true,
        ['context' => context_course::instance(SITEID), 'escape' => false]
    ),
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'carouselimages' => [],
    'carouselinterval' => 5000, // Valor por defecto si no hay nada configurado
    'my_credit' => get_string('credit', 'theme_inteb'),
    'hasgeneralnote' => false,
    'generalnote' => ''
];

// =========================================================================
// 1) Carrusel de diapositivas
// =========================================================================

// Intervalo (ms) para la rotación automática del carrusel.
// Aseguramos que sea un entero válido.
$carouselinterval = isset($theme->settings->ib_login_carouselinterval) && is_numeric($theme->settings->ib_login_carouselinterval)
    ? (int)$theme->settings->ib_login_carouselinterval
    : 5000; // Valor por defecto si no está configurado o no es numérico.

$templatecontext['carouselinterval'] = $carouselinterval;

// Número de diapositivas configuradas (ej. 1..5).
$numslides = isset($theme->settings->ib_login_numberofslides) && is_numeric($theme->settings->ib_login_numberofslides)
    ? (int)$theme->settings->ib_login_numberofslides
    : 1;

// Recorremos cada slide. El índice interno comenzará en 0.
for ($i = 0; $i < $numslides; $i++) {
    // Ajustes se llaman ib_login_slideimage1, ib_login_slideimage2..., así que sumamos 1 para la lectura real.
    $slideindex = $i + 1;

    // Obtenemos la URL de la imagen (si se subió archivo).
    $imageurl = $theme->setting_file_url("ib_login_slideimage{$slideindex}", "ib_login_slideimage{$slideindex}");
    if (!empty($imageurl)) {
        // Verificamos si el archivo existe en storage (opcional).
        $files = $fs->get_area_files(
            $context->id,
            'theme_inteb',
            "ib_login_slideimage{$slideindex}",
            0,
            'sortorder',
            false
        );
        if (!empty($files)) {
            // Título y enlace configurados para la diapositiva.
            $slidetitle = format_string(
                $theme->settings->{"ib_login_slidetitle{$slideindex}"} ?? '',
                true,
                ['escape' => false]
            );
            $slideurl   = $theme->settings->{"ib_login_slideurl{$slideindex}"} ?? '#';

            // Añadimos la diapositiva al array.
            // 'index' => $i indica el orden real para el 'data-slide-to'.
            $templatecontext['carouselimages'][] = [
                'url'         => $imageurl,
                'link'        => $slideurl,
                'title'       => $slidetitle,
                'first'       => (count($templatecontext['carouselimages']) === 0), // Marca 'active' si es la primera
                'description' => '', // Puedes agregar descripción si tienes ese campo
                'button_url'  => $slideurl,
                'index'       => $i
            ];
        }
    }
}

// Si no se encontró ninguna imagen válida, añadimos una por defecto.
if (empty($templatecontext['carouselimages'])) {
    $defaultImage = $OUTPUT->image_url('slide0', 'theme_inteb');
    $templatecontext['carouselimages'][] = [
        'url'         => (string)$defaultImage,
        'link'        => '',
        'title'       => get_string('default_slide_title', 'theme_inteb'),
        'first'       => true,
        'description' => '', // Puedes agregar descripción si tienes ese campo
        'button_url'  => '#',
        'index'       => 0
    ];
}

// Para que la plantilla sepa si hay carrusel.
$templatecontext['hascarousel'] = !empty($templatecontext['carouselimages']);

// =========================================================================
// 2) Texto "About" u otra info en el login
// =========================================================================
if (!empty($theme->settings->ib_abouttext)) {
    $templatecontext['abouttext'] = format_string(
        $theme->settings->ib_abouttext,
        true,
        ['escape' => false]
    );
}

// =========================================================================
// 3) Renderizar la plantilla con este contexto
// =========================================================================
echo $OUTPUT->render_from_template('theme_inteb/core/login-custom', $templatecontext);