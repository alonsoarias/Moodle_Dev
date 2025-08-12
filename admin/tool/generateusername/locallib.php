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
 * Helper functions for username generation.
 *
 * @package   tool_generateusername
 * @copyright 2024 Alonso Arias
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Clean text by removing accents and special characters.
 *
 * @param string $text
 * @return string
 */
function tool_generateusername_clean_text($text) {
    $text = moodle_strtolower(core_text::remove_accents(trim($text)));
    $text = preg_replace('/[^a-z]/', '', $text);
    return $text;
}

/**
 * Generate username from firstname and lastname.
 *
 * Policy:
 * - first 4 letters of firstname
 * - first 3 letters of first surname
 * - first letter of second surname
 * Missing parts are padded with x.
 *
 * @param string $firstname
 * @param string $lastname
 * @return string
 */
function tool_generateusername_generate_username($firstname, $lastname) {
    $firstname = tool_generateusername_clean_text($firstname);
    $lastname = tool_generateusername_clean_text($lastname);
    $lastnameparts = preg_split('/\s+/', $lastname);
    $firstsurname = $lastnameparts[0] ?? '';
    $secondsurname = $lastnameparts[1] ?? '';

    $namepart = substr(str_replace(' ', '', $firstname), 0, 4);
    $namepart = str_pad($namepart, 4, 'x');
    $firstpart = str_pad(substr($firstsurname, 0, 3), 3, 'x');
    $secondpart = $secondsurname !== '' ? substr($secondsurname, 0, 1) : 'x';
    return $namepart . $firstpart . $secondpart;
}

/**
 * Make username unique against existing array and database.
 *
 * @param string $base
 * @param array $existing
 * @return string
 */
function tool_generateusername_make_unique($base, array $existing) {
    global $DB;
    $username = $base;
    $suffix = 1;
    while (in_array($username, $existing) || $DB->record_exists('user', ['username' => $username])) {
        $username = substr($base, 0, 7) . $suffix;
        $suffix++;
    }
    return $username;
}

