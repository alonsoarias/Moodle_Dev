<?php
// This file part of a custom tool devoled by www.promwebsoft.com

/**
 * Plugin version info
 *
 * @package    tool
 * @subpackage idnumbertousernames
 * @copyright  2016 Pablo A Pico @pabloapico
 * @license    Para uso exclusivo de www.promwebsoft.com y sus clientes
 */

defined('MOODLE_INTERNAL') || die;

//$ADMIN->add('accounts', new admin_externalpage('toolidnumbertousernames', get_string('uploadidnumbers', 'tool_idnumbertousernames'), "$CFG->wwwroot/$CFG->admin/tool/idnumbertousernames/index.php", 'moodle/site:idnumbertousernames:idnumbertousernames'));
$ADMIN->add('accounts', new admin_externalpage('toolidnumbertousernames', get_string('uploadidnumbers', 'tool_idnumbertousernames'), "$CFG->wwwroot/$CFG->admin/tool/idnumbertousernames/index.php", 'tool/idnumbertousernames:idnumbertousernames'));


/*
// Entry in Site administration -> Users -> Accounts -> Download users.
if ($hassiteconfig) {
    $ADMIN->add(
        'accounts',
        new admin_externalpage(
            'toolidnumbertousernames', get_string('uploadidnumbers', 'tool_idnumbertousernames'),
            "$CFG->wwwroot/$CFG->admin/tool/idnumbertousernames/index.php"
        )
    );
}
*/