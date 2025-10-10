<?php
require_once('../../../config.php');
require_once('../lib.php');
require_login();

$context = context_system::instance();
require_capability('block/reports_custom:viewreports', $context);

// Captura de parámetros
$category = optional_param('category', '', PARAM_INT);
$course = optional_param('course', '', PARAM_INT);
$firstname = optional_param('firstname', '', PARAM_TEXT);
$lastname = optional_param('lastname', '', PARAM_TEXT);
$usertype = optional_param('usertype', '', PARAM_TEXT);
$idnumber = optional_param('idnumber', '', PARAM_TEXT);
$startdate = optional_param('startdate', '', PARAM_TEXT);
$enddate = optional_param('enddate', '', PARAM_TEXT);
$format = optional_param('format', 'excel', PARAM_TEXT);

// Obtener categorías permitidas para el usuario actual
$allowedCategories = get_allowed_categories_for_user($USER->id);
$allowedCategoriesString = $allowedCategories ? implode(',', $allowedCategories) : '';

// Preparando parámetros para la consulta
$params = [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype,
    'idnumber' => $idnumber,
    'startdate' => !empty($startdate) ? strtotime($startdate) : null,
    'enddate' => !empty($enddate) ? strtotime($enddate . ' 23:59:59') : null,
    'allowed_categories' => $allowedCategoriesString
];

// Recuperando registros aplicando filtros
$records = get_progress_records($params, $DB);
$data = new stdClass();
$data->tabhead = [
    get_string('header_cedula', 'block_reports_custom'),
    get_string('header_usuario', 'block_reports_custom'),
    get_string('header_nombre', 'block_reports_custom'),
    get_string('header_apellido', 'block_reports_custom'),
    get_string('header_nombre_completo', 'block_reports_custom'),
    get_string('header_clinica', 'block_reports_custom'),
    get_string('header_area', 'block_reports_custom'),
    get_string('header_categoria', 'block_reports_custom'),
    get_string('header_curso', 'block_reports_custom'),
    get_string('header_item', 'block_reports_custom'),
    get_string('header_calificacion', 'block_reports_custom'),
    get_string('header_fecha', 'block_reports_custom'),
    get_string('header_user_type', 'block_reports_custom')
];
$data->table = [];

foreach ($records as $record) {
    $data->table[] = [
        $record->cedula,
        $record->usuario,
        $record->nombre,
        $record->apellido,
        $record->nombre_completo,
        $record->clinica,
        $record->area,
        $record->categoria,
        $record->curso,
        $record->item,
        $record->calificacion,
        $record->fecha,
        $record->user_type
    ];
}

// Exportación de datos
if (optional_param('download', '', PARAM_TEXT)) {
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'csv') {
        export_to_csv($data->tabhead, $data->table, 'progress_report');
    } else {
        export_to_spreadsheet($data->tabhead, $data->table, 'progress_report', $format, get_string('progress_report', 'block_reports_custom'));
    }
    exit;
}
// Configuración de la página y carga de recursos necesarios
$PAGE->set_url(new moodle_url('/blocks/reports_custom/reports/progress.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('progress_report', 'block_reports_custom'));
$PAGE->set_heading(get_string('progress_report', 'block_reports_custom'));
$PAGE->requires->jquery();
$PAGE->requires->js(new moodle_url('/blocks/reports_custom/reports/progress.js'));

$perpage = 100;
$page = optional_param('page', 0, PARAM_INT);

echo $OUTPUT->header();

// Renderizado del formulario de filtros
echo '<form id="filtersForm" method="GET" class="form-inline mb-3">';
echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="category" class="mr-2">'.get_string('option_category', 'block_reports_custom').':</label>';
echo '<select id="category" name="category" class="form-control mb-2">';
echo '<option value="">'.get_string('option_todos', 'block_reports_custom').'</option>';
$categories = get_all_categories($DB);
foreach ($categories as $categoryObj) {
    if ($allowedCategories === null || in_array($categoryObj->id, $allowedCategories)) {
        $selected = $category == $categoryObj->id ? 'selected' : '';
        $categoryPath = get_category_path($categoryObj->id, $DB);
        echo '<option value="'.$categoryObj->id.'" '.$selected.'>'.$categoryPath.'</option>';
    }
}
echo '</select>';
echo '</div>';
echo '<div class="col-auto">';
echo '<label for="course" class="mr-2">'.get_string('option_course', 'block_reports_custom').':</label>';
echo '<select id="course" name="course" class="form-control mb-2">';
echo '<option value="">'.get_string('option_todos', 'block_reports_custom').'</option>';
$courses = get_courses_by_category($category, $DB);
foreach ($courses as $courseObj) {
    $selected = $course == $courseObj->id ? 'selected' : '';
    echo '<option value="'.$courseObj->id.'" '.$selected.'>'.$courseObj->fullname.'</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="idnumber" class="mr-2">'.get_string('idnumber', 'block_reports_custom').':</label>';
echo '<input type="text" id="idnumber" name="idnumber" value="'.$idnumber.'" class="form-control mb-2">';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="startdate" class="mr-2">'.get_string('start_date', 'block_reports_custom').':</label>';
echo '<input type="date" id="startdate" name="startdate" value="'.$startdate.'" class="form-control mb-2">';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="enddate" class="mr-2">'.get_string('end_date', 'block_reports_custom').':</label>';
echo '<input type="date" id="enddate" name="enddate" value="'.$enddate.'" class="form-control mb-2">';
echo '</div>';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="usertype" class="mr-2">'.get_string('option_usertype', 'block_reports_custom').':</label>';
echo '<select id="usertype" name="usertype" class="form-control mb-2">';
echo '<option value="">'.get_string('option_todos', 'block_reports_custom').'</option>';
$userTypes = get_user_types($DB);
foreach ($userTypes as $type) {
    $selected = $usertype == $type->usertype ? 'selected' : '';
    echo '<option value="'.$type->usertype.'" '.$selected.'>'.$type->usertype.'</option>';
}
echo '</select>';
echo '</div>';

echo '</div>';
echo '<div class="form-row align-items-center">';
echo '<div class="col-auto">';
echo '<label for="firstname" class="mr-2">'.get_string('option_nombre', 'block_reports_custom').':</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="firstname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">'.get_string('alphabet_all', 'block_reports_custom').'</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $firstname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 '.$active.'" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '<input type="hidden" name="firstname" value="'.$firstname.'">';
echo '</div>';

echo '<div class="col-auto">';
echo '<label for="lastname" class="mr-2">'.get_string('option_apellido', 'block_reports_custom').':</label>';
echo '<div class="alphabet-filter d-flex mb-2" data-filter="lastname">';
echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1" data-letter="">'.get_string('alphabet_all', 'block_reports_custom').'</a>';
foreach (range('A', 'Z') as $letter) {
    $active = $lastname == $letter ? 'active' : '';
    echo '<a href="#" class="btn btn-outline-secondary btn-sm mr-1 '.$active.'" data-letter="'.$letter.'">'.$letter.'</a>';
}
echo '</div>';
echo '<input type="hidden" name="lastname" value="'.$lastname.'">';
echo '</div>';
echo '</div>';

echo '</form>';

echo '<div id="reportData">';

$totalcount = count($records);
$records = array_slice($records, $page * $perpage, $perpage);

$table = new html_table();
$table->head = $data->tabhead;

foreach ($records as $record) {
    $table->data[] = [
        $record->cedula,
        $record->usuario,
        $record->nombre,
        $record->apellido,
        $record->nombre_completo,
        $record->clinica,
        $record->area,
        $record->categoria,
        $record->curso,
        $record->item,
        $record->calificacion,
        $record->fecha,
        $record->user_type
    ];
}

echo html_writer::table($table);

// Mostrar el total de registros
echo '<div class="mt-3">';
echo '<strong>' . get_string('total_records', 'block_reports_custom') . ': ' . $totalcount . '</strong>';
echo '</div>';

echo '<form id="downloadForm" method="GET">';
echo '<input type="hidden" name="category" value="'.$category.'">';
echo '<input type="hidden" name="course" value="'.$course.'">';
echo '<input type="hidden" name="firstname" value="'.$firstname.'">';
echo '<input type="hidden" name="lastname" value="'.$lastname.'">';
echo '<input type="hidden" name="usertype" value="'.$usertype.'">';
echo '<input type="hidden" name="idnumber" value="'.$idnumber.'">';
echo '<input type="hidden" name="startdate" value="'.$startdate.'">';
echo '<input type="hidden" name="enddate" value="'.$enddate.'">';
echo '<input type="hidden" name="allowed_categories" value="'.$allowedCategoriesString.'">';
echo '<div class="form-group">';
echo '<label for="format" class="mr-2">'.get_string('option_download_format', 'block_reports_custom').':</label>';
echo '<select id="format" name="format" class="form-control d-inline w-auto">';
echo '<option value="excel" ' . ($format === 'excel' ? 'selected' : '') . '>'.get_string('option_download_excel', 'block_reports_custom').'</option>';
echo '<option value="ods" ' . ($format === 'ods' ? 'selected' : '') . '>'.get_string('option_download_ods', 'block_reports_custom').'</option>';
echo '<option value="csv" ' . ($format === 'csv' ? 'selected' : '') . '>'.get_string('option_download_csv', 'block_reports_custom').'</option>';
echo '</select>';
echo '<button type="submit" name="download" value="1" class="btn btn-primary ml-2">'.get_string('btn_descargar', 'block_reports_custom').'</button>';
echo '</div>';
echo '</form>';

$baseurl = new moodle_url('/blocks/reports_custom/reports/progress.php', [
    'category' => $category,
    'course' => $course,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'usertype' => $usertype,
    'idnumber' => $idnumber,
    'startdate' => $startdate,
    'enddate' => $enddate,
    'allowed_categories' => $allowedCategoriesString
]);
echo $OUTPUT->paging_bar($totalcount, $page, $perpage, $baseurl);

echo '</div>';
echo $OUTPUT->footer();
?>