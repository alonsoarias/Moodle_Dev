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
 * Library functions for report_customcajasan
 *
 * @package    block_report_customcajasan
 * @copyright  2025 Cajasan
 * @author     Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/excellib.class.php');
require_once($CFG->libdir . '/odslib.class.php');
require_once($CFG->libdir . '/csvlib.class.php');

// Constantes para la generación de informes
define('REPORT_CUSTOMCAJASAN_CHUNK_SIZE', 1000); // Tamaño de chunks para procesamiento por lotes

/**
 * Normalise course and category restrictions stored in block configuration.
 *
 * @param object|array|null $config Block configuration object or raw data.
 * @param int|null $userid User identifier used for capability checks.
 * @return array{courses: array<int>, categories: array<int>, expandedcategories: array<int>} Normalised restrictions.
 */
function block_report_customcajasan_compute_restrictions($config = null, ?int $userid = null): array {
    global $USER, $CFG;

    $userid = $userid ?? $USER->id ?? 0;

    require_once($CFG->dirroot . '/course/lib.php');

    $courses = [];
    $categories = [];
    $expandedcategories = [];

    $accessiblecourses = [];
    $accessiblecategories = [];

    if ($userid > 0) {
        $usercourses = get_user_capability_course('block/report_customcajasan:viewreport', $userid, true, 'id, category');
        foreach ($usercourses as $usercourse) {
            $courseid = isset($usercourse->id) ? (int)$usercourse->id : 0;
            if ($courseid <= 0) {
                continue;
            }
            $accessiblecourses[$courseid] = $courseid;

            $coursecategoryid = isset($usercourse->category) ? (int)$usercourse->category : 0;
            if ($coursecategoryid <= 0) {
                continue;
            }

            $accessiblecategories[$coursecategoryid] = $coursecategoryid;

            try {
                $coursecategory = core_course_category::get($coursecategoryid, IGNORE_MISSING);
            } catch (moodle_exception $e) {
                $coursecategory = null;
            }

            if (!$coursecategory || empty($coursecategory->path)) {
                continue;
            }

            foreach (explode('/', trim($coursecategory->path, '/')) as $pathid) {
                $pathid = (int)$pathid;
                if ($pathid > 0) {
                    $accessiblecategories[$pathid] = $pathid;
                }
            }
        }
    }

    if ($config instanceof __PHP_Incomplete_Class) {
        $config = (object)get_object_vars($config);
    } else if (is_array($config)) {
        $config = (object)$config;
    }

    if (empty($config) || !is_object($config)) {
        return [
            'courses' => [],
            'categories' => [],
            'expandedcategories' => [],
        ];
    }

    $courseids = [];
    if (!empty($config->coursefilters)) {
        $courseids = array_map('intval', (array)$config->coursefilters);
    }

    foreach ($courseids as $courseid) {
        if ($courseid <= 0) {
            continue;
        }

        if (!empty($accessiblecourses)) {
            if (isset($accessiblecourses[$courseid])) {
                $courses[$courseid] = $courseid;
            }
            continue;
        }

        $coursecontext = context_course::instance($courseid, IGNORE_MISSING);
        if ($coursecontext && has_capability('block/report_customcajasan:viewreport', $coursecontext, $userid, false)) {
            $courses[$courseid] = $courseid;
        }
    }

    $categoryids = [];
    if (!empty($config->categoryfilters)) {
        $categoryids = array_map('intval', (array)$config->categoryfilters);
    }

    foreach ($categoryids as $categoryid) {
        if ($categoryid <= 0) {
            continue;
        }
        $categorycontext = context_coursecat::instance($categoryid, IGNORE_MISSING);
        $categoryallowed = false;

        if ($categorycontext && has_capability('block/report_customcajasan:viewreport', $categorycontext, $userid, false)) {
            $categoryallowed = true;
        } else if (isset($accessiblecategories[$categoryid])) {
            $categoryallowed = true;
        }

        if (!$categorycontext || !$categoryallowed) {
            continue;
        }

        $categories[$categoryid] = $categoryid;
        $expandedcategories[$categoryid] = $categoryid;

        try {
            $category = core_course_category::get($categoryid, IGNORE_MISSING);
        } catch (moodle_exception $e) {
            $category = null;
        }

        if ($category) {
            foreach ($category->get_all_children_ids() as $childid) {
                $childid = (int)$childid;
                if ($childid <= 0) {
                    continue;
                }

                if (!empty($accessiblecategories) && !isset($accessiblecategories[$childid])) {
                    $childcontext = context_coursecat::instance($childid, IGNORE_MISSING);
                    if (!$childcontext || !has_capability('block/report_customcajasan:viewreport', $childcontext, $userid, false)) {
                        continue;
                    }
                }

                $expandedcategories[$childid] = $childid;
            }
        }
    }

    return [
        'courses' => array_values($courses),
        'categories' => array_values($categories),
        'expandedcategories' => array_values($expandedcategories),
    ];
}

/**
 * Retrieve restrictions defined by a block instance, validating permissions.
 *
 * @param int $blockinstanceid Block instance identifier.
 * @param int|null $userid User identifier used for capability checks.
 * @return array{courses: array<int>, categories: array<int>, expandedcategories: array<int>, parentcontext: ?context, blockcontext: ?context, config: ?stdClass}
 */
function block_report_customcajasan_get_block_restrictions(int $blockinstanceid, ?int $userid = null): array {
    global $DB, $USER;

    $userid = $userid ?? $USER->id ?? 0;

    $result = [
        'courses' => [],
        'categories' => [],
        'expandedcategories' => [],
        'parentcontext' => null,
        'blockcontext' => null,
        'config' => null,
    ];

    if (empty($blockinstanceid)) {
        return $result;
    }

    $blockinstance = $DB->get_record('block_instances', [
        'id' => $blockinstanceid,
        'blockname' => 'report_customcajasan',
    ]);

    if (!$blockinstance) {
        return $result;
    }

    $blockcontext = context_block::instance($blockinstanceid, IGNORE_MISSING);
    if (!$blockcontext || !has_capability('block/report_customcajasan:viewblock', $blockcontext, $userid, false)) {
        return $result;
    }

    $parentcontext = context::instance_by_id($blockinstance->parentcontextid, IGNORE_MISSING);
    if (!$parentcontext || $parentcontext->contextlevel !== CONTEXT_COURSE) {
        return $result;
    }

    $config = null;
    if (!empty($blockinstance->configdata)) {
        $decoded = base64_decode($blockinstance->configdata, true);
        if ($decoded !== false) {
            $config = @unserialize($decoded, ['allowed_classes' => ['stdClass']]);
            if ($config === false || !is_object($config)) {
                $config = null;
            } else if ($config instanceof __PHP_Incomplete_Class) {
                $config = (object)get_object_vars($config);
            }
        }
    }

    $restrictions = block_report_customcajasan_compute_restrictions($config, $userid);

    $result['courses'] = $restrictions['courses'];
    $result['categories'] = $restrictions['categories'];
    $result['expandedcategories'] = $restrictions['expandedcategories'];
    $result['parentcontext'] = $parentcontext;
    $result['blockcontext'] = $blockcontext;
    $result['config'] = $config;

    if ($parentcontext->contextlevel === CONTEXT_COURSE &&
        empty($result['courses']) && empty($result['categories'])) {
        $courseid = $parentcontext->instanceid ?? 0;
        if ($courseid > 0) {
            $result['courses'] = [$courseid];

            if (empty($result['expandedcategories'])) {
                $coursecategory = $DB->get_field('course', 'category', ['id' => $courseid]);
                if (!empty($coursecategory)) {
                    $coursecategory = (int)$coursecategory;
                    $categoryids = [$coursecategory => $coursecategory];

                    try {
                        $category = core_course_category::get($coursecategory, IGNORE_MISSING);
                    } catch (moodle_exception $e) {
                        $category = null;
                    }

                    if ($category && !empty($category->path)) {
                        foreach (explode('/', trim($category->path, '/')) as $pathid) {
                            $pathid = (int)$pathid;
                            if ($pathid > 0) {
                                $categoryids[$pathid] = $pathid;
                            }
                        }
                    }

                    $result['expandedcategories'] = array_values($categoryids);
                }
            }
        }
    }

    return $result;
}

/**
 * Get enrollment data based on filters with pagination support
 * Optimized for large datasets with pagination and chunking
 *
 * @param array $filters Filter parameters
 * @param int $limitfrom Starting point for records (null for all records)
 * @param int $limitnum Maximum number of records (null for all records)
 * @param bool $count_only If true, returns only the count of records
 * @return array|int Enrollment data or record count
 */
function report_customcajasan_get_data($filters, $limitfrom = null, $limitnum = null, $count_only = false) {
    global $DB;
    
    // Check for required tables
    $dbman = $DB->get_manager();
    $cert_table_exists = $dbman->table_exists('customcert_issues');
    $completion_table_exists = $dbman->table_exists('course_completions');
    
    // For count query, use the optimized count function
    if ($count_only) {
        return report_customcajasan_count_data($filters);
    }
    
    // Determinar si es necesario procesar por lotes (chunking)
    $process_in_chunks = ($limitnum === null || $limitnum > REPORT_CUSTOMCAJASAN_CHUNK_SIZE || $limitnum == PHP_INT_MAX);
    
    // Inicializar el resultado final
    $combined_results = array();
    
    // Si se solicitan demasiados registros, procesarlos por lotes (chunks)
    if ($process_in_chunks && $limitnum != null) {
        $total_count = report_customcajasan_count_data($filters);
        $chunks = ceil($total_count / REPORT_CUSTOMCAJASAN_CHUNK_SIZE);
        
        // Procesar cada chunk individualmente para evitar sobrecarga de memoria
        for ($i = 0; $i < $chunks; $i++) {
            $chunk_start = $limitfrom + ($i * REPORT_CUSTOMCAJASAN_CHUNK_SIZE);
            $chunk_size = min(REPORT_CUSTOMCAJASAN_CHUNK_SIZE, $limitnum - ($i * REPORT_CUSTOMCAJASAN_CHUNK_SIZE));
            
            if ($chunk_size <= 0) {
                break;
            }
            
            $chunk_results = report_customcajasan_get_data_chunk($filters, $chunk_start, $chunk_size, $cert_table_exists, $completion_table_exists);
            $combined_results = array_merge($combined_results, $chunk_results);
            
            // Si ya hemos obtenido suficientes registros, salir del ciclo
            if (count($combined_results) >= $limitnum) {
                $combined_results = array_slice($combined_results, 0, $limitnum);
                break;
            }
        }
        
        return $combined_results;
    } else {
        // Para consultas pequeñas, usar el método normal
        return report_customcajasan_get_data_chunk($filters, $limitfrom, $limitnum, $cert_table_exists, $completion_table_exists);
    }
}

/**
 * Get a chunk of enrollment data - helper function for report_customcajasan_get_data
 * 
 * @param array $filters Filter parameters
 * @param int $limitfrom Starting point for records
 * @param int $limitnum Maximum number of records
 * @param bool $cert_table_exists Whether the certificate table exists
 * @param bool $completion_table_exists Whether the completion table exists
 * @return array Enrollment data for this chunk
 */
function report_customcajasan_get_data_chunk($filters, $limitfrom, $limitnum, $cert_table_exists, $completion_table_exists) {
    global $DB;
    
    // Optimización: Seleccionar solo las columnas necesarias para mejorar rendimiento
    $sql = "SELECT 
                CONCAT(u.id, '_', c.id) AS unique_id,
                u.id AS userid,
                c.id AS courseid,
                u.idnumber AS identificacion,
                u.firstname AS nombres,
                u.lastname AS apellidos,
                u.email AS correo,
                c.fullname AS curso,
                cat.name AS categoria,
                u.department AS unidad,
                FROM_UNIXTIME(ue.timestart) AS fecha_matricula";
    
    // Add last access to course - optimizado para utilizar el JOIN directo
    $sql .= ", CASE WHEN la.timeaccess IS NOT NULL AND la.timeaccess > 0 
                THEN FROM_UNIXTIME(la.timeaccess) 
                ELSE NULL 
              END AS ultimo_acceso";
    
    // Certificate columns - optimizar JOINs para certificados
    $need_cert_joins = $cert_table_exists && 
                     (!isset($filters['estado']) || 
                      in_array($filters['estado'], ['', 'APROBADO']));
    
    if ($cert_table_exists) {
        if ($need_cert_joins) {
            $sql .= ",
                    CASE WHEN cert.id IS NOT NULL THEN 1 ELSE 0 END AS has_certificate,
                    CASE WHEN ci.timecreated IS NOT NULL THEN FROM_UNIXTIME(ci.timecreated) ELSE NULL END AS fecha_certificado";
        } else {
            $sql .= ",
                    0 AS has_certificate,
                    NULL AS fecha_certificado";
        }
    } else {
        $sql .= ",
                0 AS has_certificate,
                NULL AS fecha_certificado";
    }
    
    // Status determination with updated and optimized logic - corregido para mantener consistencia con último acceso
    $sql .= ",
            CASE 
                WHEN (cert.id IS NULL) THEN 'SOLO CONSULTA'
                WHEN (ci.id IS NOT NULL OR cc.timecompleted IS NOT NULL) THEN 'APROBADO'
                WHEN (la.timeaccess IS NOT NULL AND la.timeaccess > 0) THEN 'EN CURSO'
                ELSE 'NO INICIADO'
            END AS estado";
    
    // FROM clause with required tables - incluir solo cursos y categorías visibles
    $sql .= "
            FROM {user_enrolments} ue
            JOIN {enrol} e ON ue.enrolid = e.id
            JOIN {course} c ON e.courseid = c.id AND c.visible = 1
            JOIN {course_categories} cat ON c.category = cat.id AND cat.visible = 1
            JOIN {user} u ON ue.userid = u.id
            LEFT JOIN {user_lastaccess} la ON la.userid = u.id AND la.courseid = c.id";
    
    // Solo incluir course modules si son necesarios
    $need_cm_join = true; // Always needed for status determination
    if ($need_cm_join) {
        $sql .= " LEFT JOIN (
                    SELECT course, COUNT(*) as has_completion
                    FROM {course_modules}
                    WHERE completion > 0
                    GROUP BY course
                ) hc ON hc.course = c.id
                LEFT JOIN {course_modules} cm ON cm.course = c.id";
    }
    
    // Add conditional JOINs only if tables exist and needed
    if ($completion_table_exists) {
        $sql .= " LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id";
    } else {
        // Use a lightweight join that won't return data but keeps the query structure intact
        $sql .= " LEFT JOIN (SELECT NULL AS timecompleted, NULL AS timestarted, NULL AS userid, NULL AS course) cc 
                  ON cc.userid = u.id AND cc.course = c.id";
    }
    
    // Solo incluir certificados si son necesarios
    if ($cert_table_exists) {
        $sql .= " LEFT JOIN {customcert} cert ON cert.course = c.id";
        if ($need_cert_joins) {
            $sql .= " LEFT JOIN {customcert_issues} ci ON ci.userid = u.id AND ci.customcertid = cert.id";
        } else {
            $sql .= " LEFT JOIN (SELECT NULL AS id, NULL AS userid, NULL AS customcertid, NULL AS timecreated) ci 
                      ON ci.userid = u.id AND ci.customcertid = cert.id";
        }
    }
    
    // WHERE clause
    $sql .= " WHERE u.deleted = 0";
    
    // Parameter collection
    $params = array();

    $allowedcourses = !empty($filters['allowedcourses']) ? array_map('intval', (array)$filters['allowedcourses']) : [];
    $allowedcategories = !empty($filters['allowedcategories']) ? array_map('intval', (array)$filters['allowedcategories']) : [];

    if (!empty($allowedcourses) || !empty($allowedcategories)) {
        $restrictionsql = [];
        if (!empty($allowedcourses)) {
            list($coursesql, $courseparams) = $DB->get_in_or_equal($allowedcourses, SQL_PARAMS_NAMED, 'alrc');
            $restrictionsql[] = "c.id {$coursesql}";
            $params = array_merge($params, $courseparams);
        }
        if (!empty($allowedcategories)) {
            list($catsql, $catparams) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, 'alrg');
            $restrictionsql[] = "cat.id {$catsql}";
            $params = array_merge($params, $catparams);
        }

        if (!empty($restrictionsql)) {
            $sql .= ' AND ' . (count($restrictionsql) > 1 ? '(' . implode(' OR ', $restrictionsql) . ')' : $restrictionsql[0]);
        }
    }

    // Apply filters using clean, consistent pattern with optimized conditions
    if (!empty($filters['category'])) {
        $sql .= " AND c.category = :category";
        $params['category'] = $filters['category'];
    }
    
    if (!empty($filters['course'])) {
        $sql .= " AND c.id = :course";
        $params['course'] = $filters['course'];
    }
    
    if (!empty($filters['idnumber'])) {
        $sql .= " AND u.idnumber LIKE :idnumber";
        $params['idnumber'] = '%' . $filters['idnumber'] . '%';
    }
    
    if (!empty($filters['firstname'])) {
        $sql .= " AND u.firstname LIKE :firstname";
        $params['firstname'] = $filters['firstname'] . '%';
    }
    
    if (!empty($filters['lastname'])) {
        $sql .= " AND u.lastname LIKE :lastname";
        $params['lastname'] = $filters['lastname'] . '%';
    }
    
    // Optimized filters for estado
    if (!empty($filters['estado'])) {
        if ($filters['estado'] === 'APROBADO') {
            $sql .= " AND cert.id IS NOT NULL AND (ci.id IS NOT NULL OR cc.timecompleted IS NOT NULL)";
        } else if ($filters['estado'] === 'EN CURSO') {
            $sql .= " AND cert.id IS NOT NULL AND (la.timeaccess IS NOT NULL AND la.timeaccess > 0) 
                     AND (cc.timecompleted IS NULL OR cc.timecompleted = 0) 
                     AND (ci.id IS NULL)";
        } else if ($filters['estado'] === 'NO INICIADO') {
            $sql .= " AND cert.id IS NOT NULL AND (la.timeaccess IS NULL OR la.timeaccess = 0)
                     AND (cc.timestarted IS NULL OR cc.timestarted = 0)
                     AND (cc.timecompleted IS NULL OR cc.timecompleted = 0)
                     AND (ci.id IS NULL)";
        } else if ($filters['estado'] === 'SOLO CONSULTA') {
            if ($cert_table_exists) {
                $sql .= " AND cert.id IS NULL";
            }
        }
    }
    
    // Date range filters
    if (!empty($filters['startdate'])) {
        $sql .= " AND ue.timestart >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }
    
    if (!empty($filters['enddate'])) {
        $sql .= " AND ue.timestart <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }
    
    // Use simplified grouping when possible - this improves query performance
    $sql .= " GROUP BY u.id, c.id, cat.name";
    if ($cert_table_exists && $need_cert_joins) {
        $sql .= ", cert.id, ci.id, ci.timecreated";
    }
    if ($completion_table_exists) {
        $sql .= ", cc.timecompleted, cc.timestarted";
    }
    
    // Optimized order by for mejor rendimiento de índices
    $sql .= " ORDER BY u.lastname, u.firstname, c.fullname";
    
    // Get results with pagination if specified
    $result = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
    
    return $result;
}

/**
 * Optimized count of enrollment data based on filters
 * 
 * @param array $filters Filter parameters
 * @return int Count of matching records
 */
function report_customcajasan_count_data($filters) {
    global $DB;
    
    // Check for required tables
    $dbman = $DB->get_manager();
    $cert_table_exists = $dbman->table_exists('customcert_issues');
    $completion_table_exists = $dbman->table_exists('course_completions');
    
    // Optimización: Usar una consulta simplificada solo para contar
    $sql = "SELECT COUNT(DISTINCT CONCAT(u.id, '_', c.id)) 
            FROM {user_enrolments} ue
            JOIN {enrol} e ON ue.enrolid = e.id
            JOIN {course} c ON e.courseid = c.id AND c.visible = 1
            JOIN {course_categories} cat ON c.category = cat.id AND cat.visible = 1
            JOIN {user} u ON ue.userid = u.id
            LEFT JOIN {user_lastaccess} la ON la.userid = u.id AND la.courseid = c.id";
    
    // Solo incluir JOINs si son necesarios para los filtros
    $need_cert_joins = $cert_table_exists && 
                      (!empty($filters['estado']) && 
                       in_array($filters['estado'], ['APROBADO', 'EN CURSO', 'NO INICIADO', 'SOLO CONSULTA']));
                      
    $need_completion_joins = $completion_table_exists && 
                            (!empty($filters['estado']) && 
                             in_array($filters['estado'], ['APROBADO', 'EN CURSO', 'NO INICIADO']));
    
    // Solo agregar JOINs si son realmente necesarios
    if ($need_completion_joins) {
        $sql .= " LEFT JOIN {course_completions} cc ON cc.userid = u.id AND cc.course = c.id";
    }
    
    if ($need_cert_joins) {
        $sql .= " LEFT JOIN {customcert} cert ON cert.course = c.id";
        
        if (in_array($filters['estado'], ['APROBADO'])) {
            $sql .= " LEFT JOIN {customcert_issues} ci ON ci.userid = u.id AND ci.customcertid = cert.id";
        } else {
            $sql .= " LEFT JOIN (SELECT NULL AS id, NULL AS userid, NULL AS customcertid) ci 
                      ON ci.userid = u.id AND ci.customcertid = cert.id";
        }
    }
    
    // WHERE clause
    $sql .= " WHERE u.deleted = 0";
    
    // Parameter collection
    $params = array();

    $allowedcourses = !empty($filters['allowedcourses']) ? array_map('intval', (array)$filters['allowedcourses']) : [];
    $allowedcategories = !empty($filters['allowedcategories']) ? array_map('intval', (array)$filters['allowedcategories']) : [];

    if (!empty($allowedcourses) || !empty($allowedcategories)) {
        $restrictionsql = [];
        if (!empty($allowedcourses)) {
            list($coursesql, $courseparams) = $DB->get_in_or_equal($allowedcourses, SQL_PARAMS_NAMED, 'alrc');
            $restrictionsql[] = "c.id {$coursesql}";
            $params = array_merge($params, $courseparams);
        }
        if (!empty($allowedcategories)) {
            list($catsql, $catparams) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, 'alrg');
            $restrictionsql[] = "cat.id {$catsql}";
            $params = array_merge($params, $catparams);
        }

        if (!empty($restrictionsql)) {
            $sql .= ' AND ' . (count($restrictionsql) > 1 ? '(' . implode(' OR ', $restrictionsql) . ')' : $restrictionsql[0]);
        }
    }

    // Apply filters
    if (!empty($filters['category'])) {
        $sql .= " AND c.category = :category";
        $params['category'] = $filters['category'];
    }
    
    if (!empty($filters['course'])) {
        $sql .= " AND c.id = :course";
        $params['course'] = $filters['course'];
    }
    
    if (!empty($filters['idnumber'])) {
        $sql .= " AND u.idnumber LIKE :idnumber";
        $params['idnumber'] = '%' . $filters['idnumber'] . '%';
    }
    
    if (!empty($filters['firstname'])) {
        $sql .= " AND u.firstname LIKE :firstname";
        $params['firstname'] = $filters['firstname'] . '%';
    }
    
    if (!empty($filters['lastname'])) {
        $sql .= " AND u.lastname LIKE :lastname";
        $params['lastname'] = $filters['lastname'] . '%';
    }
    
    // Optimized status filters
    if (!empty($filters['estado'])) {
        if ($filters['estado'] === 'APROBADO') {
            $sql .= " AND cert.id IS NOT NULL AND (ci.id IS NOT NULL OR cc.timecompleted IS NOT NULL)";
        } else if ($filters['estado'] === 'EN CURSO') {
            $sql .= " AND cert.id IS NOT NULL AND (la.timeaccess IS NOT NULL AND la.timeaccess > 0)";
            if ($need_completion_joins) {
                $sql .= " AND (cc.timecompleted IS NULL OR cc.timecompleted = 0)";
            }
            if ($need_cert_joins) {
                $sql .= " AND (ci.id IS NULL)";
            }
        } else if ($filters['estado'] === 'NO INICIADO') {
            $sql .= " AND cert.id IS NOT NULL AND (la.timeaccess IS NULL OR la.timeaccess = 0)";
            if ($need_completion_joins) {
                $sql .= " AND (cc.timestarted IS NULL OR cc.timestarted = 0) 
                          AND (cc.timecompleted IS NULL OR cc.timecompleted = 0)";
            }
            if ($need_cert_joins) {
                $sql .= " AND (ci.id IS NULL)";
            }
        } else if ($filters['estado'] === 'SOLO CONSULTA') {
            if ($need_cert_joins) {
                $sql .= " AND cert.id IS NULL";
            }
        }
    }
    
    // Date range filters
    if (!empty($filters['startdate'])) {
        $sql .= " AND ue.timestart >= :startdate";
        $params['startdate'] = $filters['startdate'];
    }
    
    if (!empty($filters['enddate'])) {
        $sql .= " AND ue.timestart <= :enddate";
        $params['enddate'] = $filters['enddate'];
    }
    
    // Usar COUNT optimizado específicamente para conteo
    $count = $DB->count_records_sql($sql, $params);
    
    return $count;
}

/**
 * Get the list of available states for filtering - updated states
 *
 * @return array List of states
 */
function report_customcajasan_get_states() {
    return [
        '' => get_string('option_all', 'block_report_customcajasan'),
        'APROBADO' => get_string('state_aprobado', 'block_report_customcajasan'),
        'EN CURSO' => get_string('state_encurso', 'block_report_customcajasan'),
        'NO INICIADO' => get_string('state_noiniciado', 'block_report_customcajasan'),
        'SOLO CONSULTA' => get_string('state_soloconsulta', 'block_report_customcajasan')
    ];
}

/**
 * Export data to .xlsx or .ods format with optimization para grandes conjuntos de datos
 *
 * @param array $filters Filter parameters for generating the data to export
 * @param string $filename The base name of the file without extension.
 * @param string $format The format of the export: 'excel' or 'ods'.
 * @param string $sheetname The name of the worksheet.
 */
function report_customcajasan_export_spreadsheet($filters, $filename, $format, $sheetname = 'Sheet1') {
    global $CFG;

    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    if ($format === 'excel') {
        $workbook = new MoodleExcelWorkbook("-");
        $filename .= '.xlsx';
    } else {
        $workbook = new MoodleODSWorkbook("-");
        $filename .= '.ods';
    }

    $workbook->send($filename);
    $worksheet = $workbook->add_worksheet($sheetname);

    // Set column widths - updated order
    $worksheet->set_column(0, 0, 15);  // Identificación
    $worksheet->set_column(1, 1, 20);  // Nombres
    $worksheet->set_column(2, 2, 20);  // Apellidos
    $worksheet->set_column(3, 3, 30);  // Correo
    $worksheet->set_column(4, 4, 40);  // Curso
    $worksheet->set_column(5, 5, 30);  // Categoría
    $worksheet->set_column(6, 6, 20);  // Unidad
    $worksheet->set_column(7, 7, 20);  // Fecha de matricula
    $worksheet->set_column(8, 8, 20);  // Último acceso al curso
    $worksheet->set_column(9, 9, 20);  // Fecha de emisión certificado
    $worksheet->set_column(10, 10, 15);  // Estado

    // Header format
    $headerformat = $workbook->add_format();
    $headerformat->set_bold();
    $headerformat->set_align('center');
    $headerformat->set_align('vcenter');
    $headerformat->set_bg_color('#CCCCCC');

    // Prepare headers
    $headers = array(
        get_string('column_identificacion', 'block_report_customcajasan'),
        get_string('column_nombres', 'block_report_customcajasan'),
        get_string('column_apellidos', 'block_report_customcajasan'),
        get_string('column_correo', 'block_report_customcajasan'),
        get_string('column_curso', 'block_report_customcajasan'),
        get_string('column_categoria', 'block_report_customcajasan'),
        get_string('column_unidad', 'block_report_customcajasan'),
        get_string('column_fecha_matricula', 'block_report_customcajasan'),
        get_string('column_ultimo_acceso', 'block_report_customcajasan'),
        get_string('column_fecha_certificado', 'block_report_customcajasan'),
        get_string('column_estado', 'block_report_customcajasan')
    );

    // Write headers
    for ($i = 0; $i < count($headers); $i++) {
        $worksheet->write(0, $i, $headers[$i], $headerformat);
    }

    // OPTIMIZACIÓN: Procesar los datos por chunks para evitar agotamiento de memoria
    $row = 1;
    $chunksize = REPORT_CUSTOMCAJASAN_CHUNK_SIZE;
    $offset = 0;
    $total = report_customcajasan_count_data($filters);
    
    // Procesar los datos en chunks para evitar problemas de memoria
    while ($offset < $total) {
        $enrollments = report_customcajasan_get_data($filters, $offset, $chunksize);
        
        foreach ($enrollments as $enrollment) {
            // Ajustar último acceso según el estado:
            // - Para "NO INICIADO", mostrar "Nunca"
            // - Para otros estados, mostrar la fecha normal
            $ultimo_acceso = $enrollment->ultimo_acceso;
            if ($enrollment->estado === 'NO INICIADO' || empty($ultimo_acceso)) {
                $ultimo_acceso = get_string('never', 'block_report_customcajasan');
            }
            
            $worksheet->write($row, 0, $enrollment->identificacion);
            $worksheet->write($row, 1, $enrollment->nombres);
            $worksheet->write($row, 2, $enrollment->apellidos);
            $worksheet->write($row, 3, $enrollment->correo);
            $worksheet->write($row, 4, $enrollment->curso);
            $worksheet->write($row, 5, $enrollment->categoria);
            $worksheet->write($row, 6, $enrollment->unidad);
            $worksheet->write($row, 7, $enrollment->fecha_matricula);
            $worksheet->write($row, 8, $ultimo_acceso);
            $worksheet->write($row, 9, $enrollment->fecha_certificado);
            $worksheet->write($row, 10, $enrollment->estado);
            $row++;
        }
        
        // Liberar memoria explícitamente
        unset($enrollments);
        
        // Avanzar al siguiente chunk
        $offset += $chunksize;
    }

    $workbook->close();
    exit;
}

/**
 * Export data to CSV format con optimización para grandes conjuntos de datos
 *
 * @param array $filters Filter parameters for generating the data to export
 * @param string $filename The base name of the file without extension.
 */
function report_customcajasan_export_csv($filters, $filename) {
    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    $filename .= '.csv';
    
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Pragma: public");

    $output = fopen('php://output', 'w');
    
    // Write headers
    $headers = array(
        get_string('column_identificacion', 'block_report_customcajasan'),
        get_string('column_nombres', 'block_report_customcajasan'),
        get_string('column_apellidos', 'block_report_customcajasan'),
        get_string('column_correo', 'block_report_customcajasan'),
        get_string('column_curso', 'block_report_customcajasan'),
        get_string('column_categoria', 'block_report_customcajasan'),
        get_string('column_unidad', 'block_report_customcajasan'),
        get_string('column_fecha_matricula', 'block_report_customcajasan'),
        get_string('column_ultimo_acceso', 'block_report_customcajasan'),
        get_string('column_fecha_certificado', 'block_report_customcajasan'),
        get_string('column_estado', 'block_report_customcajasan')
    );
    fputcsv($output, $headers);
    
    // OPTIMIZACIÓN: Procesar los datos por chunks para evitar agotamiento de memoria
    $chunksize = REPORT_CUSTOMCAJASAN_CHUNK_SIZE;
    $offset = 0;
    $total = report_customcajasan_count_data($filters);
    
    // Procesar los datos en chunks para evitar problemas de memoria
    while ($offset < $total) {
        $enrollments = report_customcajasan_get_data($filters, $offset, $chunksize);
        
        foreach ($enrollments as $enrollment) {
            // Ajustar último acceso según el estado:
            // - Para "NO INICIADO", mostrar "Nunca"
            // - Para otros estados, mostrar la fecha normal
            $ultimo_acceso = $enrollment->ultimo_acceso;
            if ($enrollment->estado === 'NO INICIADO' || empty($ultimo_acceso)) {
                $ultimo_acceso = get_string('never', 'block_report_customcajasan');
            }
            
            fputcsv($output, array(
                $enrollment->identificacion,
                $enrollment->nombres,
                $enrollment->apellidos,
                $enrollment->correo,
                $enrollment->curso,
                $enrollment->categoria,
                $enrollment->unidad,
                $enrollment->fecha_matricula,
                $ultimo_acceso,
                $enrollment->fecha_certificado,
                $enrollment->estado
            ));
        }
        
        // Liberar memoria explícitamente
        unset($enrollments);
        
        // Enviar el buffer al navegador
        flush();
        
        // Avanzar al siguiente chunk
        $offset += $chunksize;
    }
    
    fclose($output);
    exit;
}

/**
 * Get all visible course categories (no hidden ones)
 *
 * @return array List of visible categories
 */
function report_customcajasan_get_categories(array $allowedcategories = [], array $allowedcourses = []) {
    global $DB;

    $allowedcategories = array_filter(array_map('intval', $allowedcategories));
    $allowedcourses = array_filter(array_map('intval', $allowedcourses));

    if (empty($allowedcategories) && empty($allowedcourses)) {
        return $DB->get_records('course_categories', ['visible' => 1], 'path ASC', 'id, name, depth, path');
    }

    $categoryids = $allowedcategories;

    if (!empty($allowedcourses)) {
        list($insql, $params) = $DB->get_in_or_equal($allowedcourses, SQL_PARAMS_NAMED, 'ccsel');
        $coursecategories = $DB->get_fieldset_sql("SELECT DISTINCT category FROM {course} WHERE id {$insql}", $params);
        $categoryids = array_merge($categoryids, array_map('intval', $coursecategories));
    }

    $categoryids = array_values(array_unique(array_filter($categoryids)));

    if (empty($categoryids)) {
        return [];
    }

    list($catsql, $params) = $DB->get_in_or_equal($categoryids, SQL_PARAMS_NAMED, 'ccf');
    $records = $DB->get_records_sql("SELECT id, name, depth, path FROM {course_categories} WHERE id {$catsql}", $params);

    $allcategoryids = [];
    foreach ($records as $record) {
        $allcategoryids[$record->id] = $record->id;
        if (!empty($record->path)) {
            foreach (explode('/', trim($record->path, '/')) as $pathid) {
                if ($pathid !== '') {
                    $allcategoryids[(int)$pathid] = (int)$pathid;
                }
            }
        }
    }

    if (empty($allcategoryids)) {
        return [];
    }

    list($allsql, $allparams) = $DB->get_in_or_equal(array_values($allcategoryids), SQL_PARAMS_NAMED, 'ccvis');

    return $DB->get_records_sql(
        "SELECT id, name, depth, path FROM {course_categories} WHERE visible = 1 AND id {$allsql} ORDER BY path ASC",
        $allparams
    );
}

/**
 * Get visible courses by category
 *
 * @param int $categoryid Category ID
 * @return array List of visible courses
 */
function report_customcajasan_get_courses($categoryid, array $allowedcourses = [], array $allowedcategories = []) {
    global $DB;

    $params = ['visible' => 1];
    $sql = "SELECT id, fullname FROM {course} WHERE visible = :visible";

    if (!empty($categoryid)) {
        $categoryexists = $DB->record_exists('course_categories', ['id' => $categoryid, 'visible' => 1]);
        if ($categoryexists) {
            $sql .= " AND category = :category";
            $params['category'] = $categoryid;
        }
    }

    $allowedcourses = array_filter(array_map('intval', $allowedcourses));
    $allowedcategories = array_filter(array_map('intval', $allowedcategories));

    if (!empty($allowedcourses) || !empty($allowedcategories)) {
        $restrictionsql = [];
        if (!empty($allowedcourses)) {
            list($coursesql, $courseparams) = $DB->get_in_or_equal($allowedcourses, SQL_PARAMS_NAMED, 'ccourse');
            $restrictionsql[] = "id {$coursesql}";
            $params = array_merge($params, $courseparams);
        }
        if (!empty($allowedcategories)) {
            list($catsql, $catparams) = $DB->get_in_or_equal($allowedcategories, SQL_PARAMS_NAMED, 'ccat');
            $restrictionsql[] = "category {$catsql}";
            $params = array_merge($params, $catparams);
        }

        if (!empty($restrictionsql)) {
            $sql .= ' AND (' . implode(' OR ', $restrictionsql) . ')';
        }
    }

    $sql .= " ORDER BY fullname ASC";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Custom function to generate pagination bar HTML
 * 
 * @param int $totalcount Total number of items
 * @param int $page Current page number
 * @param int $perpage Number of items per page
 * @param moodle_url $baseurl Base URL for pagination links
 * @return string HTML for pagination bar
 */
function custom_paging_bar($totalcount, $page, $perpage, $baseurl) {
    if ($totalcount <= $perpage) {
        return '';
    }
    
    $output = '';
    $totalpage = ceil($totalcount / $perpage);
    
    $output .= html_writer::start_tag('nav', ['aria-label' => 'Page navigation']);
    $output .= html_writer::start_tag('ul', ['class' => 'pagination']);
    
    // Previous page link
    if ($page > 0) {
        $output .= html_writer::start_tag('li', ['class' => 'page-item']);
        $output .= html_writer::link('#', '«', ['class' => 'page-link', 'aria-label' => 'Previous', 'data-page' => ($page - 1)]);
        $output .= html_writer::end_tag('li');
    } else {
        $output .= html_writer::start_tag('li', ['class' => 'page-item disabled']);
        $output .= html_writer::tag('span', '«', ['class' => 'page-link']);
        $output .= html_writer::end_tag('li');
    }
    
    // Page numbers
    $startpage = max(0, $page - 4);
    $endpage = min($totalpage - 1, $page + 4);
    
    if ($startpage > 0) {
        $output .= html_writer::start_tag('li', ['class' => 'page-item']);
        $output .= html_writer::link('#', '1', ['class' => 'page-link', 'data-page' => 0]);
        $output .= html_writer::end_tag('li');
        
        if ($startpage > 1) {
            $output .= html_writer::start_tag('li', ['class' => 'page-item disabled']);
            $output .= html_writer::tag('span', '...', ['class' => 'page-link']);
            $output .= html_writer::end_tag('li');
        }
    }
    
    for ($i = $startpage; $i <= $endpage; $i++) {
        if ($i == $page) {
            $output .= html_writer::start_tag('li', ['class' => 'page-item active']);
            $output .= html_writer::tag('span', $i + 1, ['class' => 'page-link']);
            $output .= html_writer::end_tag('li');
        } else {
            $output .= html_writer::start_tag('li', ['class' => 'page-item']);
            $output .= html_writer::link('#', $i + 1, ['class' => 'page-link', 'data-page' => $i]);
            $output .= html_writer::end_tag('li');
        }
    }
    
    if ($endpage < $totalpage - 1) {
        if ($endpage < $totalpage - 2) {
            $output .= html_writer::start_tag('li', ['class' => 'page-item disabled']);
            $output .= html_writer::tag('span', '...', ['class' => 'page-link']);
            $output .= html_writer::end_tag('li');
        }
        
        $output .= html_writer::start_tag('li', ['class' => 'page-item']);
        $output .= html_writer::link('#', $totalpage, ['class' => 'page-link', 'data-page' => $totalpage - 1]);
        $output .= html_writer::end_tag('li');
    }
    
    // Next page link
    if ($page < $totalpage - 1) {
        $output .= html_writer::start_tag('li', ['class' => 'page-item']);
        $output .= html_writer::link('#', '»', ['class' => 'page-link', 'aria-label' => 'Next', 'data-page' => ($page + 1)]);
        $output .= html_writer::end_tag('li');
    } else {
        $output .= html_writer::start_tag('li', ['class' => 'page-item disabled']);
        $output .= html_writer::tag('span', '»', ['class' => 'page-link']);
        $output .= html_writer::end_tag('li');
    }
    
    $output .= html_writer::end_tag('ul');
    $output .= html_writer::end_tag('nav');
    
    return $output;
}