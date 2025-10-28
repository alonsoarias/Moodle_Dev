<?php
/**
 * Provides the core rendering functionality for the theme_inteb, aligning Moodle's HTML with Bootstrap expectations.
 *
 * This core renderer class extends theme_remui's core renderer, adding specific modifications to enhance and customize
 * the user interface for theme_inteb. Key functionalities include customized login forms, theme settings integration,
 * and dynamic handling of UI elements like carousels and notices based on theme configurations.
 *
 * @package    theme_inteb
 * @category   output
 * @author     ...
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_inteb\output;

use theme_config;
use moodle_url;
use context_course;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../../remui/classes/output/core_renderer.php');
require_once(__DIR__ . '/../util/theme_settings.php');

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 */
class core_renderer extends \theme_remui\output\core_renderer
{
    /**
     * Renders the login form.
     *
     * @param \core_auth\output\login $form The renderable.
     * @return string
     */
    public function render_login(\core_auth\output\login $form) {
        global $SITE;

        $context = $form->export_for_template($this);

        // Prepare error text if any.
        $context->errorformatted = $this->error_text($context->error);

        // Logo from 'remui' or from 'inteb'? 
        // Si deseas usar el logo del tema "inteb" en vez de "remui", cambia la línea:
        $context->logourl = $this->get_theme_logo_url('logo');

        // Nombre del sitio.
        $context->sitename = format_string($SITE->fullname, true, ['context' => \context_course::instance(SITEID)]);

        // Renderiza la plantilla 'core/loginbox' (o la que uses en tu tema).
        return $this->render_from_template('core/loginbox', $context);
    }

    /**
     * Devuelve la URL de una imagen de configuración del tema (ej. loginimage, slide1, etc.)
     *
     * @param string $img Nombre del ajuste
     * @return string
     */
    public function get_theme_img_url($img) {
        $theme = theme_config::load('inteb');
        return $theme->setting_file_url('ib_' . $img, 'ib_' . $img);
    }

    /**
     * Devuelve la URL del logo. Puede cambiarse para usar 'inteb' o 'remui' según tu preferencia.
     *
     * @param string $img
     * @return string|null
     */
    public function get_theme_logo_url($img) {
        $theme = theme_config::load('remui');
        return $theme->setting_file_url($img, $img);
    }

    /**
     * Devuelve el footer estándar y agrega scripts de chat y prevención de copy/paste según configuraciones.
     *
     * @return string
     */
    public function standard_footer_html() {
        global $USER;

        // Footer principal heredado de theme_remui
        $output = parent::standard_footer_html();

        // Carga la configuración del tema inteb
        $theme = theme_config::load('inteb');

        // 1) Widget de chat
        if (!empty($theme->settings->ib_enable_chat)) {
            $output .= $this->add_chat_widget();
        }

        // 2) Prevención de Copy/Paste
        if (!empty($theme->settings->ib_copypaste_prevention)) {
            $this->add_copy_paste_prevention();
        }

        return $output;
    }

    /**
     * Sobrescribe el método full_header para mostrar avisos generales u otros estilos en el header.
     *
     * @return string
     */
    public function full_header() {
        global $CFG, $USER, $PAGE;

        $theme = theme_config::load('inteb');
        $output = '';

        // Ocultar secciones front page si está configurado
        if (!empty($theme->settings->ib_hidefrontpagesections)) {
            $output .= '<style>.frontpage-sections { display: none; }</style>';
        }

        // Aviso general (notice)
        if (!empty(trim($theme->settings->ib_generalnotice))) {
            $mode = $theme->settings->ib_generalnoticemode;
            // 'info' => alert-info, 'danger' => alert-danger, 'off' => sin aviso
            if ($mode === 'info') {
                $output .= '<div class="alert alert-info mt-4"><strong><i class="fa fa-info-circle"></i></strong> ' . $theme->settings->ib_generalnotice . '</div>';
            } else if ($mode === 'danger') {
                $output .= '<div class="alert alert-danger mt-4"><strong><i class="fa fa-warning"></i></strong> ' . $theme->settings->ib_generalnotice . '</div>';
            }
        }

        // Recordatorio para admin, si el aviso está en modo 'off'
        if (is_siteadmin() && (!empty($theme->settings->ib_generalnoticemode) && $theme->settings->ib_generalnoticemode === 'off')) {
            $output .= '<div class="alert mt-4"><a href="' . $CFG->wwwroot . '/admin/settings.php?section=themesettinginteb#theme_inteb">' .
                       '<strong><i class="fa fa-edit"></i></strong> ' . get_string('generalnotice_create', 'theme_inteb') . '</a></div>';
        }

        // Validación de URL (por ejemplo, para sitios de prueba)
        if (!$this->check_allowed_urls()) {
            $popup_id = bin2hex(random_bytes(8));
            $output .= $this->show_unauthorized_access_overlay($popup_id);
        }

        // Continúa con el header normal.
        $output .= parent::full_header();
        return $output;
    }

    /**
     * Agrega el script de chat si está configurado en el tema (enable_chat y tawkto_embed_url).
     *
     * @return string HTML/JS del widget de chat
     */
    protected function add_chat_widget() {
        global $USER;

        $theme = theme_config::load('inteb');
        // Si el usuario no ha iniciado sesión o no tenemos URL del chat, no hacemos nada
        if (!isloggedin() || empty($theme->settings->ib_tawkto_embed_url)) {
            return '';
        }

        // Insertamos el script de Tawk.to (u otro) con datos del usuario
        $script = "
        <!--Start of Chat Script-->
        <script type=\"text/javascript\">
        var Tawk_API = Tawk_API || {}, Tawk_LoadStart = new Date();
        Tawk_API.visitor = {
            name  : '" . fullname($USER) . "',
            email : '" . $USER->email . "',
            username : '" . $USER->username . "',
            idnumber : '" . $USER->idnumber . "'
        };
        Tawk_API.onLoad = function(){
            Tawk_API.setAttributes({
                name  : '" . fullname($USER) . "',
                email : '" . $USER->email . "',
                username : '" . $USER->username . "',
                idnumber : '" . $USER->idnumber . "'
            }, function(error){});
        };
        (function(){
            var s1 = document.createElement(\"script\"), s0 = document.getElementsByTagName(\"script\")[0];
            s1.async = true;
            s1.src = '" . $theme->settings->ib_tawkto_embed_url . "';
            s1.charset = 'UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1, s0);
        })();
        </script>
        <!--End of Chat Script-->
        ";

        return $script;
    }

    /**
     * Agrega la lógica de prevención de Copy/Paste para roles específicos.
     */
    protected function add_copy_paste_prevention() {
        global $USER, $PAGE, $COURSE;

        $theme = theme_config::load('inteb');
        $restrictedroles = $theme->settings->ib_copypaste_roles;

        // Si no hay roles restringidos, no hacemos nada.
        if (empty($restrictedroles)) {
            return;
        }

        // Si es administrador/a, ignoramos la restricción
        if (is_siteadmin()) {
            return;
        }

        try {
            // Obtenemos el contexto para saber en qué curso o página estamos
            $context = null;
            if (!empty($COURSE->id) && $COURSE->id > 1) {
                // Contexto de un curso
                $context = \context_course::instance($COURSE->id);
            } else if (!empty($PAGE->context)) {
                // Si no es un curso, usamos el contexto de la página actual
                $context = $PAGE->context;
            }

            if (!$context) {
                return; // No hay contexto válido
            }

            // Convertimos a array si es string (por seguridad)
            if (!is_array($restrictedroles)) {
                $restrictedroles = explode(',', $restrictedroles);
            }

            // Obtenemos los roles del usuario en este contexto
            $userroles = get_user_roles($context, $USER->id);
            $hasrestrictedrole = false;
            foreach ($userroles as $role) {
                if (in_array($role->roleid, $restrictedroles)) {
                    $hasrestrictedrole = true;
                    break;
                }
            }

            // Si el usuario tiene algún rol restringido, aplicamos la prevención
            if (isloggedin() && $hasrestrictedrole) {
                // Llama a un módulo AMD con la lógica para bloquear copy/paste
                $PAGE->requires->js_call_amd('theme_inteb/prevent_copy_paste', 'init');
            }
        } catch (moodle_exception $e) {
            debugging('Error in copy/paste prevention: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return;
        }
    }

    /**
     * Muestra un overlay de acceso no autorizado.
     * 
     * @param string $popup_id Un ID único para el div
     * @return string
     */
    protected function show_unauthorized_access_overlay($popup_id) {
        $output = '';
        $output .= '<style>
            #' . $popup_id . ' {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: 100% !important;
                background: rgba(0, 0, 0, 0.75) !important;
                z-index: 10000 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                pointer-events: auto !important;
            }
            html, body {
                overflow: hidden !important; /* Prevent scrolling on the whole page */
            }
        </style>';

        $output .= '<div id="' . $popup_id . '">';
        $output .= '<div style="padding: 20px; background: white; border: 1px solid #ccc; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">';
        $output .= '<h2 style="color: red;">' . get_string('unauthorized_access', 'theme_inteb') . '</h2>';
        $output .= '<p>' . get_string('unauthorized_access_msg', 'theme_inteb') . '</p>';
        $output .= '</div>';
        $output .= '</div>';

        // JS para bloquear devtools e interacción
        $output .= '<script type="text/javascript">
            document.addEventListener("keydown", function(event) {
                // Bloquea F12 y ctrl+shift+i / ctrl+shift+j
                if (event.keyCode == 123 || (event.ctrlKey && event.shiftKey && (event.keyCode == 73 || event.keyCode == 74))) {
                    event.preventDefault();
                    alert("' . get_string('devtools_access_disabled', 'theme_inteb') . '");
                    return false;
                }
            });
            setInterval(function() {
                if ((window.outerHeight - window.innerHeight) > 200 || (window.outerWidth - window.innerWidth) > 200) {
                    alert("' . get_string('devtools_access_disabled', 'theme_inteb') . '");
                }
            }, 1000);
            // Previene interacción en el resto de la página
            document.body.style.pointerEvents = "none";
            document.addEventListener("contextmenu", event => event.preventDefault());
            document.body.addEventListener("click", function(e) {
                e.stopPropagation();
                return false;
            }, true);
        </script>';

        return $output;
    }

    /**
     * Comprueba si la URL actual está en la lista de URLs permitidas.
     * @return bool True si la URL está permitida, False en caso contrario.
     */
    protected function check_allowed_urls() {
        global $CFG;
        $allowed_urls = [
            'https://inteb.moodlesoporte.net',
            'http://inteb.moodlesoporte.net',
            'https://aulavirtualnew.inteb.edu.co',
            'http://aulavirtualnew.inteb.edu.co',
            'https://moodle45.localhost.com',
            'http://moodle45.localhost.com',
            'https://demomoodle.ingeweb.co',
            'http://demomoodle.ingeweb.co'
        ];

        return in_array($CFG->wwwroot, $allowed_urls);
    }
}