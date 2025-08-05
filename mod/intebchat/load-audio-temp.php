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
 * Serve temporary audio files for intebchat.
 *
 * @package    mod_intebchat
 * @copyright  2024 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

require_login();

$filename = required_param('filename', PARAM_TEXT);
$filename = preg_replace('/[\W]/', '', $filename);

$filepath = "{$CFG->dataroot}/temp/{$filename}.mp3";

if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    die("File not found");
}

// Clean old temp files (older than 1 hour)
$tempdir = "{$CFG->dataroot}/temp/";
if ($handle = opendir($tempdir)) {
    while (false !== ($file = readdir($handle))) {
        if ($file != "." && $file != ".." && strpos($file, '.mp3') !== false) {
            $filemtime = filemtime($tempdir . $file);
            if (time() - $filemtime > 3600) { // 1 hour
                unlink($tempdir . $file);
            }
        }
    }
    closedir($handle);
}

ob_clean();

header("Content-Disposition: inline; filename=\"{$filename}.mp3\"");
header("Content-type: audio/mp3");
header("Content-Length: " . filesize($filepath));

readfile($filepath);