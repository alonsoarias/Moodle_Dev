<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../lib.php');

function xmldb_local_educamlogin_uninstall() {
    set_config('ed_enabled', 0, 'local_educamlogin');
    local_educamlogin_update_alternatelogin();
    return true;
}
