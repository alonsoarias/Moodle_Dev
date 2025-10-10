<?php
require_once('../../../config.php');
require_once('../lib.php');
require_login();

$courseid = optional_param('course', 0, PARAM_INT);
$categoryid = optional_param('category', 0, PARAM_INT);
$usertype = optional_param('usertype', '', PARAM_TEXT);

// Obtener las categorías permitidas para el usuario actual
$allowedCategories = get_allowed_categories_for_user($USER->id);

$params = [];
$sql = "SELECT DISTINCT u.id, CONCAT(u.firstname, ' ', u.lastname) AS fullname
        FROM {user} u
        JOIN {user_enrolments} ue ON ue.userid = u.id
        JOIN {enrol} e ON e.id = ue.enrolid
        JOIN {course} c ON c.id = e.courseid";

$where = ["u.deleted = 0"];

if ($courseid) {
    $where[] = "e.courseid = :courseid";
    $params['courseid'] = $courseid;
} elseif ($categoryid) {
    $where[] = "c.category = :categoryid";
    $params['categoryid'] = $categoryid;
}

if ($usertype) {
    $sql .= " LEFT JOIN {user_info_data} d1 ON d1.userid = u.id
              LEFT JOIN {user_info_field} f1 ON d1.fieldid = f1.id AND f1.shortname = 'user_type'";
    $where[] = "(d1.data = :usertype OR (d1.data IS NULL AND :usertype = 'No asignado'))";
    $params['usertype'] = $usertype;
}

// Aplicar restricciones de categorías permitidas
if ($allowedCategories !== null) {
    $where[] = "c.category IN (" . implode(',', $allowedCategories) . ")";
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= " ORDER BY fullname ASC";

$users = $DB->get_records_sql($sql, $params);

echo json_encode(array_values($users));