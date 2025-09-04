<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Hook definitions for the Role Styles plugin.
 *
 * @package    local_rolestyles
 */

defined('MOODLE_INTERNAL') || die();

$hooks = [
    [
        'hookname' => 'core\\hook\\output\\before_http_headers',
        'callback' => 'local_rolestyles_hook_before_http_headers',
        'priority' => 100,
    ],
];
