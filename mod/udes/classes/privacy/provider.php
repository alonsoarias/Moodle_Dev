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
 * Privacy Subsystem implementation for mod_udes.
 *
 * @package     mod_udes
 * @copyright   2026 Universidad de Santander - UDES (udes.edu.co)
 * @author      Alonso Arias <soporte@orioncloud.com.co>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_udes\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem for mod_udes implementing metadata and plugin providers.
 *
 * @copyright  2026 Universidad de Santander - UDES
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,

    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,

    // This plugin is capable of determining which users have data within it.
    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'udes_recursos',
            [
                'userid' => 'privacy:metadata:udes_recursos:userid',
                'contenido_unidad' => 'privacy:metadata:udes_recursos:contenido_unidad',
                'contenido_tema' => 'privacy:metadata:udes_recursos:contenido_tema',
                'timecreated' => 'privacy:metadata:udes_recursos:timecreated',
                'timemodified' => 'privacy:metadata:udes_recursos:timemodified',
            ],
            'privacy:metadata:udes_recursos'
        );

        $items->add_database_table(
            'udes_comentarios',
            [
                'userid' => 'privacy:metadata:udes_comentarios:userid',
                'comentario' => 'privacy:metadata:udes_comentarios:comentario',
                'timecreated' => 'privacy:metadata:udes_comentarios:timecreated',
            ],
            'privacy:metadata:udes_comentarios'
        );

        $items->add_database_table(
            'udes_aprobaciones',
            [
                'userid' => 'privacy:metadata:udes_aprobaciones:userid',
                'comentario' => 'privacy:metadata:udes_aprobaciones:comentario',
                'aprobado' => 'privacy:metadata:udes_aprobaciones:aprobado',
                'timecreated' => 'privacy:metadata:udes_aprobaciones:timecreated',
            ],
            'privacy:metadata:udes_aprobaciones'
        );

        $items->add_database_table(
            'udes_workflow',
            [
                'userid' => 'privacy:metadata:udes_workflow:userid',
                'timecreated' => 'privacy:metadata:udes_workflow:timecreated',
                'timemodified' => 'privacy:metadata:udes_workflow:timemodified',
            ],
            'privacy:metadata:udes_workflow'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Resources created by user.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_recursos} ur ON ur.udesid = u.id
                 WHERE ur.userid = :userid";

        $params = [
            'modname' => 'udes',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Comments made by user.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_comentarios} uc ON uc.udesid = u.id
                 WHERE uc.userid = :userid";

        $contextlist->add_from_sql($sql, $params);

        // Approvals/rejections by user.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_aprobaciones} ua ON ua.udesid = u.id
                 WHERE ua.userid = :userid";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Resources.
        $sql = "SELECT ur.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'udes'
                  JOIN {udes} u ON u.id = cm.instance
                  JOIN {udes_recursos} ur ON ur.udesid = u.id
                 WHERE cm.id = :cmid";

        $params = ['cmid' => $context->instanceid];
        $userlist->add_from_sql('userid', $sql, $params);

        // Comments.
        $sql = "SELECT uc.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'udes'
                  JOIN {udes} u ON u.id = cm.instance
                  JOIN {udes_comentarios} uc ON uc.udesid = u.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Approvals.
        $sql = "SELECT ua.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'udes'
                  JOIN {udes} u ON u.id = cm.instance
                  JOIN {udes_aprobaciones} ua ON ua.udesid = u.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);

        // Workflow.
        $sql = "SELECT uw.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = 'udes'
                  JOIN {udes} u ON u.id = cm.instance
                  JOIN {udes_workflow} uw ON uw.udesid = u.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export resources.
        $sql = "SELECT ur.*
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_recursos} ur ON ur.udesid = u.id
                 WHERE c.id {$contextsql}
                       AND ur.userid = :userid
              ORDER BY ur.timecreated ASC";

        $params = ['modname' => 'udes', 'userid' => $user->id] + $contextparams;

        $recursos = $DB->get_recordset_sql($sql, $params);
        foreach ($recursos as $recurso) {
            $context = \context_module::instance($recurso->udesid);
            $contextdata = helper::get_context_data($context, $contextlist->get_user());

            $data = (object)[
                'unidad' => $recurso->unidad,
                'item' => $recurso->item,
                'nombre_unidad' => $recurso->nombre_unidad,
                'nombre_tema' => $recurso->nombre_tema,
                'tipo_recurso_unidad' => $recurso->tipo_recurso_unidad,
                'recurso_unidad' => $recurso->recurso_unidad,
                'tipo_recurso_tema' => $recurso->tipo_recurso_tema,
                'recurso_tema' => $recurso->recurso_tema,
                'estado' => $recurso->estado,
                'timecreated' => \core_privacy\local\request\transform::datetime($recurso->timecreated),
                'timemodified' => \core_privacy\local\request\transform::datetime($recurso->timemodified),
            ];

            writer::with_context($context)->export_data(['recursos', $recurso->id], $data);
        }
        $recursos->close();

        // Export comments.
        $sql = "SELECT uc.*
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_comentarios} uc ON uc.udesid = u.id
                 WHERE c.id {$contextsql}
                       AND uc.userid = :userid
              ORDER BY uc.timecreated ASC";

        $comentarios = $DB->get_recordset_sql($sql, $params);
        foreach ($comentarios as $comentario) {
            $context = \context_module::instance($comentario->udesid);

            $data = (object)[
                'phase' => $comentario->phase,
                'comentario' => $comentario->comentario,
                'timecreated' => \core_privacy\local\request\transform::datetime($comentario->timecreated),
            ];

            writer::with_context($context)->export_data(['comentarios', $comentario->id], $data);
        }
        $comentarios->close();

        // Export approvals.
        $sql = "SELECT ua.*
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {udes} u ON u.id = cm.instance
            INNER JOIN {udes_aprobaciones} ua ON ua.udesid = u.id
                 WHERE c.id {$contextsql}
                       AND ua.userid = :userid
              ORDER BY ua.timecreated ASC";

        $aprobaciones = $DB->get_recordset_sql($sql, $params);
        foreach ($aprobaciones as $aprobacion) {
            $context = \context_module::instance($aprobacion->udesid);

            $data = (object)[
                'phase' => $aprobacion->phase,
                'rol' => $aprobacion->rol,
                'aprobado' => $aprobacion->aprobado ? get_string('yes') : get_string('no'),
                'comentario' => $aprobacion->comentario,
                'timecreated' => \core_privacy\local\request\transform::datetime($aprobacion->timecreated),
            ];

            writer::with_context($context)->export_data(['aprobaciones', $aprobacion->id], $data);
        }
        $aprobaciones->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('udes', $context->instanceid);
        if (!$cm) {
            return;
        }

        $udesid = $cm->instance;

        // Delete all related data.
        $DB->delete_records('udes_recursos', ['udesid' => $udesid]);
        $DB->delete_records('udes_comentarios', ['udesid' => $udesid]);
        $DB->delete_records('udes_aprobaciones', ['udesid' => $udesid]);
        $DB->delete_records('udes_workflow', ['udesid' => $udesid]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }

            $cm = get_coursemodule_from_id('udes', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $udesid = $cm->instance;

            // Delete user's resources.
            $DB->delete_records('udes_recursos', ['udesid' => $udesid, 'userid' => $userid]);

            // Delete user's comments.
            $DB->delete_records('udes_comentarios', ['udesid' => $udesid, 'userid' => $userid]);

            // Delete user's approvals.
            $DB->delete_records('udes_aprobaciones', ['udesid' => $udesid, 'userid' => $userid]);

            // Delete user's workflow entries.
            $DB->delete_records('udes_workflow', ['udesid' => $udesid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('udes', $context->instanceid);
        if (!$cm) {
            return;
        }

        $udesid = $cm->instance;
        $userids = $userlist->get_userids();

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        // Delete resources.
        $DB->delete_records_select('udes_recursos', "udesid = :udesid AND userid $usersql",
            array_merge(['udesid' => $udesid], $userparams));

        // Delete comments.
        $DB->delete_records_select('udes_comentarios', "udesid = :udesid AND userid $usersql",
            array_merge(['udesid' => $udesid], $userparams));

        // Delete approvals.
        $DB->delete_records_select('udes_aprobaciones', "udesid = :udesid AND userid $usersql",
            array_merge(['udesid' => $udesid], $userparams));

        // Delete workflow.
        $DB->delete_records_select('udes_workflow', "udesid = :udesid AND userid $usersql",
            array_merge(['udesid' => $udesid], $userparams));
    }
}
