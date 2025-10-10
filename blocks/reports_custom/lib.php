<?php

require_once("$CFG->libdir/excellib.class.php");
require_once("$CFG->libdir/odslib.class.php");
require_once("$CFG->libdir/csvlib.class.php");

/**
 * Export data to .xlsx or .ods format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file without extension.
 * @param string $format The format of the export: 'excel' or 'ods'.
 * @param string $sheetname The name of the worksheet.
 */
function export_to_spreadsheet($headers, $rows, $filename, $format, $sheetname = 'Sheet1')
{
    global $CFG;

    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $filename .= ".xlsx";
        $workbook = new MoodleExcelWorkbook("-");
    } else {
        $filename .= ".ods";
        $workbook = new MoodleODSWorkbook("-");
    }

    $workbook->send($filename);

    $worksheet = $workbook->add_worksheet($sheetname);

    $formatbc = $workbook->add_format(array('bold' => 1));

    $col = 0;
    foreach ($headers as $header) {
        $worksheet->write(0, $col++, $header, $formatbc);
    }

    $row = 1;
    foreach ($rows as $record) {
        $col = 0;
        foreach ($record as $value) {
            $worksheet->write($row, $col++, $value);
        }
        $row++;
    }

    $workbook->close();
}

/**
 * Export data to .csv format.
 *
 * @param array $headers The headers for the data.
 * @param array $rows The rows of data.
 * @param string $filename The base name of the file.
 */
function export_to_csv($headers, $rows, $filename)
{
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= ".csv";

    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');
    if ($output === false) {
        debugging("Failed to open output stream", DEBUG_DEVELOPER);
        return;
    }

    fputcsv($output, $headers);

    foreach ($rows as $record) {
        fputcsv($output, $record);
    }

    fclose($output);
}

/**
 * Get the full category path name.
 *
 * @param int $categoryId The ID of the category.
 * @param object $DB The database object.
 * @return string The full path name of the category.
 */
function get_category_path($categoryId, $DB)
{
    $categoryPath = $DB->get_record('course_categories', array('id' => $categoryId), 'path');
    $fullPathNames = [];
    if ($categoryPath) {
        $pathIds = explode('/', trim($categoryPath->path, '/'));
        foreach ($pathIds as $pathId) {
            $categoryName = $DB->get_record('course_categories', array('id' => $pathId), 'name');
            if ($categoryName) {
                $fullPathNames[] = $categoryName->name;
            }
        }
    }
    return implode(' / ', $fullPathNames);
}

/**
 * Get all categories.
 *
 * @param object $DB The database object.
 * @return array The list of categories.
 */
function get_all_categories($DB)
{
    return $DB->get_records('course_categories');
}

/**
 * Get courses by category.
 *
 * @param int $categoryId The ID of the category.
 * @param object $DB The database object.
 * @return array The list of courses.
 */
function get_courses_by_category($categoryId, $DB)
{
    if ($categoryId) {
        return $DB->get_records('course', array('category' => $categoryId), 'fullname ASC', 'id, fullname');
    } else {
        return $DB->get_records('course', null, 'fullname ASC', 'id, fullname');
    }
}

/**
 * Get distinct user types including 'No asignado'.
 *
 * @param object $DB The database object.
 * @return array The list of user types.
 */
function get_user_types($DB)
{
    $sql = "SELECT DISTINCT d1.data AS usertype 
            FROM {user_info_data} d1 
            JOIN {user_info_field} f1 ON d1.fieldid = f1.id 
            WHERE f1.shortname = 'user_type'";

    $usertypes = $DB->get_records_sql($sql);

    $usertypesArray = array_map(function ($record) {
        return $record->usertype;
    }, $usertypes);

    if (!in_array('No asignado', $usertypesArray)) {
        $usertypesArray[] = 'No asignado';
    }

    $result = [];
    foreach ($usertypesArray as $usertype) {
        $result[] = (object)['usertype' => $usertype];
    }

    return $result;
}

/**
 * Get allowed categories for a user based on their roles.
 *
 * @param int $userid The ID of the user.
 * @return array|null The list of allowed category IDs or null if no restrictions.
 */
function get_allowed_categories_for_user($userid)
{
    global $DB;

    $userRoles = $DB->get_records_sql("
        SELECT DISTINCT r.id, r.shortname
        FROM {role_assignments} ra
        JOIN {role} r ON ra.roleid = r.id
        WHERE ra.userid = ?
    ", array($userid));

    $restrictedRoles = [11, 12, 13];
    $userHasRestrictedRole = false;
    $allowedCategories = array();

    foreach ($userRoles as $role) {
        if ($role->id == 11) {
            $userHasRestrictedRole = true;
            $allowedCategories[] = 72;
        } elseif ($role->id == 12) {
            $userHasRestrictedRole = true;
            $allowedCategories[] = 74;
        } elseif ($role->id == 13) {
            $userHasRestrictedRole = true;
            $allowedCategories[] = 78;
        } elseif ($role->id == 14) {
            $userHasRestrictedRole = true;
            $allowedCategories[] = 73;
        }
        
    }

    // Si el usuario no tiene un rol restringido, retornar null (sin restricciones)
    if (!$userHasRestrictedRole) {
        return null;
    }

    return array_unique($allowedCategories);
}

/**
 * Get certificate records.
 *
 * @param array $params The parameters for the query.
 * @param object $DB The database object.
 * @return array The list of certificate records.
 */
function get_certificates_records($params, $DB)
{
    $sql = "SELECT
                CerGene.id AS uniqueid,
                usua.idnumber AS cedula,
                usua.firstname AS nombres,
                usua.lastname AS apellidos,
                usua.institution AS clinica,
                usua.department AS area,
                Curso.fullname AS nombrecurso,
                FROM_UNIXTIME(CerGene.timecreated) AS fecha,
                CatCurso.name AS categoriacurso,
                COALESCE(d1.data, 'No asignado') AS user_type
            FROM
                {customcert_issues} AS CerGene
            JOIN
                {customcert} AS CusCert ON CerGene.customcertid = CusCert.id
            JOIN
                {course} AS Curso ON Curso.id = CusCert.course
            JOIN
                {user} AS usua ON usua.id = CerGene.userid
            JOIN
                {course_categories} AS CatCurso ON Curso.category = CatCurso.id
            LEFT JOIN
                {user_info_field} f1 ON f1.shortname = 'user_type'
            LEFT JOIN
                {user_info_data} d1 ON d1.userid = usua.id AND d1.fieldid = f1.id
            WHERE
                usua.idnumber <> ''";

    if (!empty($params['category'])) {
        $sql .= " AND Curso.category = :category";
    }
    if (!empty($params['course'])) {
        $sql .= " AND Curso.id = :course";
    }
    if (!empty($params['firstname'])) {
        $sql .= " AND usua.firstname LIKE :firstname";
        $params['firstname'] = $params['firstname'] . "%";
    }
    if (!empty($params['lastname'])) {
        $sql .= " AND usua.lastname LIKE :lastname";
        $params['lastname'] = $params['lastname'] . "%";
    }
    if (!empty($params['usertype'])) {
        $sql .= " AND d1.data = :usertype";
    }
    if (!empty($params['idnumber'])) {
        $sql .= " AND usua.idnumber LIKE :idnumber";
        $params['idnumber'] = '%' . $params['idnumber'] . '%';
    }
    if (!empty($params['startdate'])) {
        $sql .= " AND CerGene.timecreated >= :startdate";
    }
    if (!empty($params['enddate'])) {
        $sql .= " AND CerGene.timecreated <= :enddate";
    }
    if (!empty($params['allowed_categories'])) {
        $sql .= " AND Curso.category IN (" . $params['allowed_categories'] . ")";
    }

    return $DB->get_records_sql($sql, $params);
}

/**
 * Get progress records.
 *
 * @param array $params The parameters for the query.
 * @param object $DB The database object.
 * @return array The list of progress records.
 */
function get_progress_records($params, $DB)
{
    $sql = "SELECT
                CONCAT(gg.id, '_', gg.userid, '_', gi.id) AS unique_id,
                u.id AS userid,
                u.idnumber AS cedula,
                u.username AS usuario,
                u.firstname AS nombre,
                u.lastname AS apellido,
                CONCAT(u.firstname, ' ', u.lastname) AS nombre_completo,
                u.institution AS clinica,
                u.department AS area,
                cc.name AS categoria,
                c.fullname AS curso,
                gi.itemname AS item,
                gg.finalgrade AS calificacion,
                FROM_UNIXTIME(gg.timemodified) AS fecha,
                COALESCE(d1.data, 'No asignado') AS user_type
            FROM
                {grade_grades} gg
            JOIN
                {grade_items} gi ON gg.itemid = gi.id
            JOIN
                {course} c ON gi.courseid = c.id
            JOIN
                {course_categories} cc ON c.category = cc.id
            JOIN
                {user} u ON gg.userid = u.id
            LEFT JOIN
                {user_info_field} f1 ON f1.shortname = 'user_type'
            LEFT JOIN
                {user_info_data} d1 ON d1.userid = u.id AND d1.fieldid = f1.id
            WHERE
                u.idnumber <> ''";

    if (!empty($params['category'])) {
        $sql .= " AND c.category = :category";
    }
    if (!empty($params['course'])) {
        $sql .= " AND c.id = :course";
    }
    if (!empty($params['firstname'])) {
        $sql .= " AND u.firstname LIKE :firstname";
        $params['firstname'] = $params['firstname'] . "%";
    }
    if (!empty($params['lastname'])) {
        $sql .= " AND u.lastname LIKE :lastname";
        $params['lastname'] = $params['lastname'] . "%";
    }
    if (!empty($params['usertype'])) {
        $sql .= " AND d1.data = :usertype";
    }
    if (!empty($params['idnumber'])) {
        $sql .= " AND u.idnumber LIKE :idnumber";
        $params['idnumber'] = '%' . $params['idnumber'] . '%';
    }
    if (!empty($params['startdate'])) {
        $sql .= " AND gg.timemodified >= :startdate";
    }
    if (!empty($params['enddate'])) {
        $sql .= " AND gg.timemodified <= :enddate";
    }
    if (!empty($params['allowed_categories'])) {
        $sql .= " AND c.category IN (" . $params['allowed_categories'] . ")";
    }

    return $DB->get_records_sql($sql, $params);
}
