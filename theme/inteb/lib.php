<?php
/**
 * Lib functions for theme_inteb
 *
 * @package   theme_inteb
 * @copyright (c) 2025 IngeWeb <soporte@ingeweb.co>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Pedro Arias <soporte@ingeweb.co>
 */

defined('MOODLE_INTERNAL') || die();

// Load our license override autoloader
require_once(__DIR__ . '/classes/license_autoload.php');

require_once(__DIR__ . '/../remui/lib.php');

// Ejecutar activación de licencia inmediatamente cuando se carga este archivo
theme_inteb_license_autoload();

// Establecer el estado de la licencia como válido y habilitar estadísticas
global $CFG;
if (defined('EDD_LICENSE_STATUS')) {
    set_config(EDD_LICENSE_STATUS, 'valid', 'theme_remui');
    set_config(EDD_LICENSE_KEY, 'license-auto-activated-by-inteb', 'theme_remui');
    set_config(EDD_LICENSE_ACTION, true, 'theme_remui');
    
    // Configurar transient de larga duración
    if (defined('WDM_LICENSE_TRANS')) {
        $transient = serialize(array('valid', time() + (60 * 60 * 24 * 365)));
        set_config(WDM_LICENSE_TRANS, $transient, 'theme_remui');
    }
    
    // Asegurar que las estadísticas del dashboard estén habilitadas
    if (get_config('theme_remui', 'enabledashboardcoursestats') === false) {
        set_config('enabledashboardcoursestats', '1', 'theme_remui');
    }
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_inteb_get_extra_scss($theme) {
    // Activar licencia cuando se genera el CSS
    theme_inteb_license_autoload();
    
    $scss = '';
    // Cargando SCSS existente de variables y estilos personalizados
    if (file_exists(__DIR__ . '/scss/_variables.scss')) {
        $scss .= file_get_contents(__DIR__ . '/scss/_variables.scss');
    }
    if (file_exists(__DIR__ . '/scss/custom_variables.scss')) {
        $scss .= file_get_contents(__DIR__ . '/scss/custom_variables.scss');
    }
    if (file_exists(__DIR__ . '/scss/inteb.scss')) {
        $scss .= file_get_contents(__DIR__ . '/scss/inteb.scss');
    }

    // Añadiendo el contenido de custom.css
    if (file_exists(__DIR__ . '/style/custom.css')) {
        $customCss = file_get_contents(__DIR__ . '/style/custom.css');
        $scss .= $customCss;
    }

    return $scss;
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_inteb_get_pre_scss($theme)
{
    // Activar licencia cuando se genera el CSS
    theme_inteb_license_autoload();
    
    $scss = theme_remui_get_extra_scss($theme);

    return $scss;
}

/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_inteb_get_main_scss_content($theme) {
    global $CFG;

    // Activar licencia cuando se genera el CSS
    theme_inteb_license_autoload();

    // Primero, cargar el SCSS del tema padre (RemUI) directamente, ya que no podemos 
    // confiar en method_exists en este caso específico. Utilizamos la lógica original
    // de theme_remui_get_main_scss_content.
    $scss = '';
    $filename = !empty($theme->settings->preset) ? $theme->settings->preset : null;
    $fs = get_file_storage();
    $context = context_system::instance();

    if ($filename == 'default.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/remui/scss/preset/default.scss');
    } else if ($filename == 'plain.scss') {
        $scss .= file_get_contents($CFG->dirroot . '/theme/remui/scss/preset/plain.scss');
    } else if ($filename && ($presetfile = $fs->get_file($context->id, 'theme_remui', 'preset', 0, '/', $filename))) {
        $scss .= $presetfile->get_content();
    } else {
        // Fallback de seguridad a default.scss si no se encuentra el preset especificado.
        $scss .= file_get_contents($CFG->dirroot . '/theme/remui/scss/preset/default.scss');
    }

    // Luego, cargar las personalizaciones SCSS de inteb.
    $intebVariables = '';
    $customVariables = '';
    $intebScss = '';

    if (file_exists($CFG->dirroot . '/theme/inteb/scss/_variables.scss')) {
        $intebVariables = file_get_contents($CFG->dirroot . '/theme/inteb/scss/_variables.scss');
    }
    if (file_exists($CFG->dirroot . '/theme/inteb/scss/custom_variables.scss')) {
        $customVariables = file_get_contents($CFG->dirroot . '/theme/inteb/scss/custom_variables.scss');
    }
    if (file_exists($CFG->dirroot . '/theme/inteb/scss/inteb.scss')) {
        $intebScss = file_get_contents($CFG->dirroot . '/theme/inteb/scss/inteb.scss');
    }

    // Cargar cualquier CSS personalizado desde 'custom.css'.
    $customCss = '';
    if (file_exists($CFG->dirroot . '/theme/inteb/style/custom.css')) {
        $customCss = file_get_contents($CFG->dirroot . '/theme/inteb/style/custom.css');
    }

    // Combinar todos los estilos en el orden correcto.
    $combinedScssContent = $scss . "\n" . $intebVariables . "\n" . $customVariables . "\n" . $intebScss . "\n" . $customCss;

    return $combinedScssContent;
}

/**
 * Serves any files associated with the theme settings.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return mixed
 */
function theme_inteb_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array())
{
    // Activar licencia cuando se sirven archivos
    theme_inteb_license_autoload();
    
    $theme = theme_config::load('inteb');

    if ($context->contextlevel == CONTEXT_SYSTEM) {
        // Serve theme files with prefixed names
        if ($filearea === 'ib_personalareaheader') {
            return $theme->setting_file_serve('ib_personalareaheader', $args, $forcedownload, $options);
        }
        if ($filearea === 'ib_mycoursesheader') {
            return $theme->setting_file_serve('ib_mycoursesheader', $args, $forcedownload, $options);
        }

        // Check if the file area corresponds to the carousel images.
        if (strpos($filearea, 'ib_login_slideimage') === 0) {
            // Extract the slide number from the file area name.
            $slide_number = substr($filearea, strlen('ib_login_slideimage'));
            // Serve the slide image.
            return $theme->setting_file_serve("ib_login_slideimage{$slide_number}", $args, $forcedownload, $options);
        }
    }

    return theme_remui_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options);
}

/**
 * Esta función es llamada durante el inicio del tema. La usaremos para activar la licencia.
 */
function theme_inteb_page_init() {
    // Activar licencia al inicializar la página
    theme_inteb_license_autoload();
    
    // Asegurar que las estadísticas del dashboard estén habilitadas
    if (get_config('theme_remui', 'enabledashboardcoursestats') === false) {
        set_config('enabledashboardcoursestats', '1', 'theme_remui');
    }
}

/**
 * Función para asegurar que se activa la licencia antes de mostrar cualquier footer
 */
function theme_inteb_before_footer() {
    // Asegurar licencia en cada página
    theme_inteb_license_autoload();
    return '';
}

/**
 * Asegura que la licencia del tema se valida cuando se renderiza el CSS.
 * 
 * @param string $css El CSS final.
 * @return string El CSS procesado.
 */
function theme_inteb_process_css($css) {
    // Aplicar override de licencia antes de procesar el CSS
    theme_inteb_license_autoload();
    
    // Devolver el CSS tal cual, no se necesita procesamiento adicional aquí
    return $css;
}

/**
 * Función auxiliar para convertir nombres de configuración antiguos a nuevos con prefijo.
 * 
 * @return void
 */
function theme_inteb_migrate_settings() {
    $oldtonew = [
        'generalnoticemode' => 'ib_generalnoticemode',
        'generalnotice' => 'ib_generalnotice',
        'enable_chat' => 'ib_enable_chat',
        'tawkto_embed_url' => 'ib_tawkto_embed_url',
        'copypaste_prevention' => 'ib_copypaste_prevention',
        'copypaste_roles' => 'ib_copypaste_roles',
        'login_numberofslides' => 'ib_login_numberofslides',
        'login_carouselinterval' => 'ib_login_carouselinterval',
        'showpersonalareaheader' => 'ib_showpersonalareaheader',
        'personalareaheader' => 'ib_personalareaheader',
        'showmycoursesheader' => 'ib_showmycoursesheader',
        'mycoursesheader' => 'ib_mycoursesheader',
        'hidefrontpagesections' => 'ib_hidefrontpagesections',
        'hidefootersections' => 'ib_hidefootersections',
        'abouttitle' => 'ib_abouttitle',
        'abouttext' => 'ib_abouttext'
    ];
    
    // Slide specific settings
    for ($i = 1; $i <= 10; $i++) {
        $oldtonew["login_slidetitle$i"] = "ib_login_slidetitle$i";
        $oldtonew["login_slideurl$i"] = "ib_login_slideurl$i";
        // Las imágenes se migrarán cuando se guarden los nuevos settings
    }
    
    foreach ($oldtonew as $old => $new) {
        $value = get_config('theme_inteb', $old);
        if ($value !== false) {
            set_config($new, $value, 'theme_inteb');
            // Opcional: eliminar el valor antiguo
            // unset_config($old, 'theme_inteb');
        }
    }
}

/**
 * Función para obtener ajustes del tema con nombres con prefijo
 *
 * @param string $setting Nombre del ajuste
 * @param mixed $default Valor predeterminado si no se encuentra el ajuste
 * @return mixed El valor del ajuste o predeterminado
 */
function theme_inteb_get_setting($setting) {
    // Siempre intentar primero con el prefijo ib_
    $value = get_config('theme_inteb', 'ib_' . $setting);

    // Si no se encuentra, intentar con la versión sin prefijo (para compatibilidad con versiones anteriores)
    if ($value === false) {
        $value = get_config('theme_inteb', $setting);
    }

    return $value;
}

/**
 * SOBRESCRITURA DE FUNCIÓN DE FORMAT_REMUIFORMAT
 *
 * Obtiene contexto de profesores inscritos en un curso para format_remuiformat
 * MODIFICADO: Ahora obtiene TODOS los profesores (editing y non-editing)
 *
 * Esta función sobrescribe get_enrolled_teachers_context_formate() de format_remuiformat
 * para mostrar TODOS los profesores con indicadores de tipo.
 *
 * @param object $course Objeto del curso
 * @param bool $frontlineteacher Si true, NO limita cantidad de profesores mostrados (modificado)
 * @return array Contexto con información completa de profesores
 */
function get_enrolled_teachers_context_formate($course, $frontlineteacher = false) {
    global $OUTPUT, $CFG, $USER, $DB;

    $courseid = $course->id;

    // Obtener grupos del usuario para respetar modo de grupos
    $usergroups = groups_get_user_groups($courseid, $USER->id);
    $groupids = 0;

    if ($course->groupmode == 1) {
        $groupids = $usergroups[0];
    }

    $coursecontext = \context_course::instance($courseid);

    // ====================================================================
    // MODIFICACIÓN PRINCIPAL: Obtener TODOS los profesores
    // ====================================================================

    // PASO 1: Obtener profesores con permisos de edición (editingteacher)
    // Capability: mod/folder:managefiles (como hace remuiformat originalmente)
    $editingteachers = get_enrolled_users(
        $coursecontext,
        'mod/folder:managefiles',
        $groupids,
        '*',
        'firstname',
        $limitfrom = 0,
        $limitnum = 0,
        $onlyactive = true
    );

    // PASO 2: Obtener profesores SIN permisos de edición (non-editing teacher)
    // Capability: moodle/course:viewhiddenactivities (capability típica de teacher role)
    $noneditingteachers = get_enrolled_users(
        $coursecontext,
        'moodle/course:viewhiddenactivities',
        $groupids,
        '*',
        'firstname',
        $limitfrom = 0,
        $limitnum = 0,
        $onlyactive = true
    );

    // PASO 3: Combinar ambos arrays y eliminar duplicados
    $allteachers = array();

    // Agregar editing teachers
    foreach ($editingteachers as $teacher) {
        $allteachers[$teacher->id] = $teacher;
        // Marcar como editing teacher
        $allteachers[$teacher->id]->is_editing_teacher = true;
    }

    // Agregar non-editing teachers (evitando duplicados)
    foreach ($noneditingteachers as $teacher) {
        if (!isset($allteachers[$teacher->id])) {
            $allteachers[$teacher->id] = $teacher;
            // Marcar como non-editing teacher
            $allteachers[$teacher->id]->is_editing_teacher = false;
        }
    }

    // PASO 4: Ordenar por firstname
    uasort($allteachers, function($a, $b) {
        return strcasecmp($a->firstname, $b->firstname);
    });

    // ====================================================================
    // FIN DE MODIFICACIÓN PRINCIPAL
    // ====================================================================

    // Obtener rol de editingteacher para el enlace de participantes
    $roles = new \stdClass();
    $allroles = get_all_roles();
    foreach ($allroles as $singlerole) {
        if ($singlerole->shortname == 'editingteacher') {
            $roles = $singlerole;
            break;
        }
    }
    if (!isset($roles->id)) {
        $roles->id = "";
    }

    $context = array();

    if ($allteachers) {
        // MODIFICADO: Ya no limitamos a 4 profesores - mostramos TODOS
        $profilecount = 0;

        foreach ($allteachers as $key => $teacher) {
            // Crear entrada para cada profesor
            $instructor = array();
            $instructor['id'] = $teacher->id;
            $instructor['name'] = fullname($teacher, true);
            $instructor['avatars'] = $OUTPUT->user_picture($teacher);
            $instructor['teacherprofileurl'] = $CFG->wwwroot.'/user/profile.php?id='.$teacher->id;

            // NUEVO: Agregar indicador de tipo de profesor
            $instructor['is_editing_teacher'] = $teacher->is_editing_teacher;
            $instructor['teacher_type_class'] = $teacher->is_editing_teacher
                ? 'editing-teacher'
                : 'non-editing-teacher';

            // NUEVO: Agregar tooltip descriptivo
            if ($teacher->is_editing_teacher) {
                $instructor['teacher_type_title'] = get_string('editingteacher', 'theme_inteb');
            } else {
                $instructor['teacher_type_title'] = get_string('teacher', 'theme_inteb');
            }

            if ($profilecount != 0) {
                $instructor['hasanother'] = true;
            }

            $context['instructors'][] = $instructor;
            $profilecount++;
        }

        // Ya no hay teachercount porque mostramos todos
        $context['teachercount'] = 0;

        $context['participantspageurl'] = $CFG->wwwroot.'/user/index.php?id='.$courseid.'&roleid='.$roles->id;
        $context['hasteachers'] = true;

        // NUEVO: Agregar contadores separados para estadísticas
        $editingcount = 0;
        $noneditingcount = 0;
        foreach ($allteachers as $teacher) {
            if ($teacher->is_editing_teacher) {
                $editingcount++;
            } else {
                $noneditingcount++;
            }
        }
        $context['editing_teachers_count'] = $editingcount;
        $context['non_editing_teachers_count'] = $noneditingcount;
        $context['total_teachers_count'] = $profilecount;
    }

    return $context;
}