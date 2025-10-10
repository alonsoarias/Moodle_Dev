<?php
require_once('../../../config.php');
require_once('../lib.php');
require_login();

$categoryid = optional_param('category', 0, PARAM_INT);
$allowedCategories = get_allowed_categories_for_user($USER->id);

// Construir la consulta SQL base
$sql = "SELECT id, fullname FROM {course} WHERE 1=1";
$params = array();

// Aplicar filtro de categoría si se proporciona
if ($categoryid) {
    $sql .= " AND category = :categoryid";
    $params['categoryid'] = $categoryid;
}

// Aplicar restricciones de categorías permitidas
if ($allowedCategories !== null) {
    $sql .= " AND category IN (" . implode(',', $allowedCategories) . ")";
}

// Ordenar los cursos por nombre
$sql .= " ORDER BY fullname ASC";

// Ejecutar la consulta
$courses = $DB->get_records_sql($sql, $params);

// Devolver los resultados como JSON
echo json_encode(array_values($courses));