<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Installation script for the Role Styles plugin.
 *
 * @package    local_rolestyles
 */

/**
 * Set default configuration values on install.
 *
 * @return bool
 */
function xmldb_local_rolestyles_install() {
    $defaults = [
        'enabled' => 0,
        'selected_roles' => '',
        'custom_css' => '',
    ];

    foreach ($defaults as $name => $value) {
        if (get_config('local_rolestyles', $name) === false) {
            set_config($name, $value, 'local_rolestyles');
        }
    }

    return true;
}
