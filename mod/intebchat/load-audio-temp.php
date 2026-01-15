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
 * Serve temporary audio files for intebchat with multi-format support.
 *
 * Supports: mp3, opus, webm, mp4, aac, wav
 *
 * @package    mod_intebchat
 * @copyright  2024 Eduardo Kraus
 * @copyright  2025 Enhanced by Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once("../../config.php");

require_login();

$filename = required_param('filename', PARAM_ALPHANUMEXT);
$format = optional_param('format', 'mp3', PARAM_ALPHA);

// Sanitize filename - only allow alphanumeric, underscore and hyphen
$filename = preg_replace('/[^a-zA-Z0-9_-]/', '', $filename);

// Allowed audio formats with their MIME types
$allowed_formats = [
    'mp3'  => 'audio/mpeg',
    'opus' => 'audio/opus',
    'webm' => 'audio/webm',
    'mp4'  => 'audio/mp4',
    'm4a'  => 'audio/mp4',
    'aac'  => 'audio/aac',
    'wav'  => 'audio/wav',
    'ogg'  => 'audio/ogg',
];

// Validate format
if (!array_key_exists($format, $allowed_formats)) {
    $format = 'mp3';
}

$tempdir = "{$CFG->dataroot}/temp/";
$filepath = "{$tempdir}{$filename}.{$format}";

// If exact format not found, try to find the file with any allowed extension
if (!file_exists($filepath)) {
    $found = false;
    foreach (array_keys($allowed_formats) as $ext) {
        $testpath = "{$tempdir}{$filename}.{$ext}";
        if (file_exists($testpath)) {
            $filepath = $testpath;
            $format = $ext;
            $found = true;
            break;
        }
    }

    if (!$found) {
        header("HTTP/1.0 404 Not Found");
        die("Audio file not found");
    }
}

// Clean old temp files (older than 1 hour) - improved cleanup
$cleanup_extensions = array_keys($allowed_formats);
if ($handle = opendir($tempdir)) {
    $now = time();
    while (false !== ($file = readdir($handle))) {
        if ($file === "." || $file === "..") {
            continue;
        }

        // Check if it's an audio file we manage
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        if (in_array($extension, $cleanup_extensions)) {
            $fullpath = $tempdir . $file;
            $filemtime = @filemtime($fullpath);
            if ($filemtime && ($now - $filemtime > 3600)) {
                @unlink($fullpath);
            }
        }
    }
    closedir($handle);
}

// Get MIME type
$mimetype = $allowed_formats[$format] ?? 'audio/mpeg';

// Clear any previous output
if (ob_get_level()) {
    ob_end_clean();
}

// Send appropriate headers for streaming
header("Content-Disposition: inline; filename=\"audio.{$format}\"");
header("Content-Type: {$mimetype}");
header("Content-Length: " . filesize($filepath));
header("Accept-Ranges: bytes");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Output the file
readfile($filepath);
exit;
