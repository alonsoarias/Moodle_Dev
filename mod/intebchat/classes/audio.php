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
 * Audio helper functions with enhanced token tracking based on local_geniai plugin.
 *
 * @package    mod_intebchat
 * @copyright  2024 Eduardo Kraus
 * @copyright  Enhanced 2025 Alonso Arias
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;

defined('MOODLE_INTERNAL') || die();

class audio
{
    /** @var int Maximum audio file size in bytes (25MB - Whisper API limit) */
    const MAX_AUDIO_SIZE = 25 * 1024 * 1024;

    /** @var int Maximum base64 input size (roughly 33% larger than binary) */
    const MAX_BASE64_SIZE = 35 * 1024 * 1024;

    /** @var array Allowed audio MIME types */
    const ALLOWED_MIME_TYPES = ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg'];

    /**
     * Transcribe audio to text using OpenAI Whisper with enhanced token tracking.
     *
     * @param string $audio Base64 encoded audio data
     * @param string|null $lang Language hint (ISO-639-1 code)
     * @return array ['text' => string, 'language' => string, 'duration' => float, 'filename' => string, 'file_size' => int]
     * @throws \moodle_exception If audio validation fails
     */
    public static function transcribe(string $audio, ?string $lang = null): array
    {
        global $CFG;

        // Validate base64 input size before decoding
        if (strlen($audio) > self::MAX_BASE64_SIZE) {
            throw new \moodle_exception('audiofiletoolarge', 'mod_intebchat', '', [
                'size' => self::format_bytes(strlen($audio)),
                'max' => self::format_bytes(self::MAX_AUDIO_SIZE)
            ]);
        }

        // Detect and validate MIME type
        // Format can be: data:audio/webm;base64, or data:audio/webm;codecs=opus;base64,
        $mimetype = 'mp3';
        if (strpos($audio, 'data:audio/') === 0) {
            // Match audio type, handling optional parameters like codecs=opus
            if (preg_match('#^data:audio/([a-z0-9]+)(?:;[^;]+)*;base64,#i', $audio, $matches)) {
                $detectedType = strtolower($matches[1]);
                if (in_array($detectedType, self::ALLOWED_MIME_TYPES)) {
                    $mimetype = $detectedType;
                } else {
                    throw new \moodle_exception('invalidaudioformat', 'mod_intebchat', '', $detectedType);
                }
            }
        }

        // Remove data URI prefix and decode (handle optional parameters like codecs=opus)
        $audio = preg_replace('#^data:audio/[a-z0-9]+(?:;[^;]+)*;base64,#i', '', $audio);
        $audiodata = base64_decode($audio, true);

        // Validate base64 decoding
        if ($audiodata === false) {
            throw new \moodle_exception('invalidaudiodata', 'mod_intebchat');
        }

        // Validate decoded file size
        $file_size = strlen($audiodata);
        if ($file_size > self::MAX_AUDIO_SIZE) {
            throw new \moodle_exception('audiofiletoolarge', 'mod_intebchat', '', [
                'size' => self::format_bytes($file_size),
                'max' => self::format_bytes(self::MAX_AUDIO_SIZE)
            ]);
        }

        // Validate minimum size (at least 100 bytes for a valid audio file)
        if ($file_size < 100) {
            throw new \moodle_exception('audiofiletoosmall', 'mod_intebchat');
        }
        
        $filename = uniqid();
        $filepath = "{$CFG->dataroot}/temp/{$filename}.{$mimetype}";

        // Ensure temp directory exists
        if (!file_exists("{$CFG->dataroot}/temp")) {
            mkdir("{$CFG->dataroot}/temp", 0777, true);
        }

        file_put_contents($filepath, $audiodata);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/transcriptions',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'file' => curl_file_create($filepath, 'audio/' . $mimetype, 'audio.' . $mimetype),
                'model' => 'whisper-1',
                'response_format' => 'verbose_json',
                'language' => $lang,
            ],
            CURLOPT_HTTPHEADER => [
                'Content-Type: multipart/form-data',
                'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
            ],
            // Performance optimizations
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 60, // Whisper can take time for longer audio
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0, // HTTP/2 for better performance
            CURLOPT_TCP_NODELAY => true, // Disable Nagle's algorithm for lower latency
        ]);

        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Handle errors
        if ($http_code !== 200 || empty($result)) {
            // Clean up temp file on error
            @unlink($filepath);
            throw new \moodle_exception('transcriptionfailed', 'mod_intebchat', '', $curl_error ?: "HTTP $http_code");
        }

        $result = json_decode($result);
        
        // Calculate duration from response or estimate from file size
        $duration = 0;
        if (isset($result->duration)) {
            $duration = $result->duration;
        } else {
            // Estimate duration based on file size (rough approximation)
            // Average bitrate for audio: 128 kbps = 16 KB/s
            $duration = $file_size / (16 * 1024); // Rough estimate in seconds
        }

        return [
            'text' => $result->text ?? '',
            'language' => $result->language ?? '',
            'duration' => $duration,
            'filename' => $filename,
            'file_size' => $file_size,
        ];
    }

    /**
     * Convert text to speech using OpenAI TTS with optimized performance.
     *
     * Uses tts-1 model with opus format for faster response times.
     * Opus provides better compression and lower latency than MP3.
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use (alloy, ash, coral, echo, fable, onyx, nova, sage, shimmer, verse)
     * @param bool $hd Use HD model (tts-1-hd) for higher quality but slower response
     * @return array URL and token info
     */
    public static function speech_with_tracking(string $input, string $voice = 'alloy', bool $hd = false): array
    {
        global $CFG;

        // Limit text length to avoid timeout
        $max_chars = 4096;
        if (strlen($input) > $max_chars) {
            $input = mb_substr($input, 0, $max_chars);
        }

        // Calculate character count for token estimation
        $char_count = strlen($input);

        // Use opus format for faster streaming/loading
        // opus has better compression and lower latency than mp3
        $response_format = 'opus';
        $file_extension = 'opus';

        // For very short texts, mp3 might be faster to generate
        if ($char_count < 100) {
            $response_format = 'mp3';
            $file_extension = 'mp3';
        }

        $json = json_encode((object) [
            'model' => $hd ? 'tts-1-hd' : 'tts-1',
            'input' => $input,
            'voice' => $voice,
            'response_format' => $response_format,
            'speed' => 1.0, // Normal speed
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/speech',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
            ],
            // Optimized timeouts for faster response
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            // Enable HTTP/2 for better performance
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        ]);

        $audiodata = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200 || empty($audiodata)) {
            return [
                'url' => '',
                'error' => 'TTS generation failed: ' . ($curl_error ?: "HTTP $http_code"),
                'tokens' => 0
            ];
        }

        // Ensure temp directory exists
        $tempdir = "{$CFG->dataroot}/temp";
        if (!file_exists($tempdir)) {
            mkdir($tempdir, 0777, true);
        }

        $filename = uniqid('tts_');
        $filepath = "{$tempdir}/{$filename}.{$file_extension}";
        file_put_contents($filepath, $audiodata);

        // Calculate audio file size
        $file_size = strlen($audiodata);

        // Estimate duration based on format and file size
        // Opus typically has ~32kbps for speech = 4KB/s
        // MP3 typically has ~128kbps = 16KB/s
        $bytes_per_second = ($response_format === 'opus') ? 4096 : 16384;
        $duration = $file_size / $bytes_per_second;

        return [
            'url' => "{$CFG->wwwroot}/mod/intebchat/load-audio-temp.php?filename={$filename}&format={$file_extension}",
            'tokens' => $char_count,
            'file_size' => $file_size,
            'duration' => $duration,
            'format' => $response_format
        ];
    }

    /**
     * Convert text to speech using OpenAI TTS (backward compatibility).
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use
     * @return string URL to generated audio
     */
    public static function speech(string $input, string $voice = 'alloy'): string
    {
        $result = self::speech_with_tracking($input, $voice);
        return $result['url'];
    }
    
    /**
     * Clean up old temporary audio files
     * 
     * @param int $max_age Maximum age in seconds (default 1 hour)
     * @return int Number of files cleaned
     */
    public static function cleanup_temp_files($max_age = 3600)
    {
        global $CFG;
        
        $tempdir = "{$CFG->dataroot}/temp/";
        $cleaned = 0;
        
        if (!file_exists($tempdir)) {
            return 0;
        }
        
        if ($handle = opendir($tempdir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != "." && $file != ".." && (strpos($file, '.mp3') !== false || strpos($file, '.webm') !== false)) {
                    $filepath = $tempdir . $file;
                    $filemtime = filemtime($filepath);
                    if (time() - $filemtime > $max_age) {
                        unlink($filepath);
                        $cleaned++;
                    }
                }
            }
            closedir($handle);
        }
        
        return $cleaned;
    }

    /**
     * Format bytes into human-readable string.
     *
     * @param int $bytes Number of bytes
     * @param int $precision Decimal precision
     * @return string Formatted string (e.g., "1.5 MB")
     */
    private static function format_bytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Save TTS audio permanently using Moodle's File API
     *
     * @param string $audiobinary Raw binary audio data
     * @param string $format Audio format (opus, mp3, etc.)
     * @param int $contextid The context ID for the file area
     * @param int $conversationid The conversation ID (used as itemid)
     * @return array ['url' => string, 'filename' => string] or ['error' => string]
     */
    public static function save_tts_audio(string $audiobinary, string $format, int $contextid, int $conversationid): array
    {
        global $USER;

        if (empty($audiobinary) || strlen($audiobinary) < 100) {
            return ['error' => 'Invalid audio data'];
        }

        // Generate unique filename
        $filename = 'ttsaudio_' . $conversationid . '_' . time() . '_' . uniqid() . '.' . $format;

        // Prepare file record
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $contextid,
            'component' => 'mod_intebchat',
            'filearea' => 'ttsaudio',
            'itemid' => $conversationid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        // Create the file from string
        $file = $fs->create_file_from_string($fileinfo, $audiobinary);

        if (!$file) {
            return ['error' => 'Failed to save TTS audio file'];
        }

        // Generate the URL using pluginfile.php
        $url = \moodle_url::make_pluginfile_url(
            $contextid,
            'mod_intebchat',
            'ttsaudio',
            $conversationid,
            '/',
            $filename
        );

        return [
            'url' => $url->out(),
            'filename' => $filename,
        ];
    }

    /**
     * Convert text to speech and save permanently
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use
     * @param int $contextid The context ID for the file area
     * @param int $conversationid The conversation ID
     * @param bool $hd Use HD model
     * @return array URL and token info with permanent storage
     */
    public static function speech_with_permanent_storage(string $input, string $voice, int $contextid, int $conversationid, bool $hd = false): array
    {
        global $CFG;

        // Limit text length to avoid timeout
        $max_chars = 4096;
        if (strlen($input) > $max_chars) {
            $input = mb_substr($input, 0, $max_chars);
        }

        $char_count = strlen($input);

        // Use opus for better compression
        $response_format = 'opus';
        $file_extension = 'opus';

        if ($char_count < 100) {
            $response_format = 'mp3';
            $file_extension = 'mp3';
        }

        $json = json_encode((object) [
            'model' => $hd ? 'tts-1-hd' : 'tts-1',
            'input' => $input,
            'voice' => $voice,
            'response_format' => $response_format,
            'speed' => 1.0,
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/audio/speech',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0,
        ]);

        $audiodata = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200 || empty($audiodata)) {
            return [
                'url' => '',
                'error' => 'TTS generation failed: ' . ($curl_error ?: "HTTP $http_code"),
                'tokens' => 0
            ];
        }

        // Save permanently
        $save_result = self::save_tts_audio($audiodata, $file_extension, $contextid, $conversationid);

        if (!empty($save_result['error'])) {
            // Fallback to temporary storage if permanent save fails
            $tempdir = "{$CFG->dataroot}/temp";
            if (!file_exists($tempdir)) {
                mkdir($tempdir, 0777, true);
            }
            $filename = uniqid('tts_');
            $filepath = "{$tempdir}/{$filename}.{$file_extension}";
            file_put_contents($filepath, $audiodata);

            return [
                'url' => "{$CFG->wwwroot}/mod/intebchat/load-audio-temp.php?filename={$filename}&format={$file_extension}",
                'tokens' => $char_count,
                'file_size' => strlen($audiodata),
                'format' => $response_format,
                'permanent' => false
            ];
        }

        return [
            'url' => $save_result['url'],
            'tokens' => $char_count,
            'file_size' => strlen($audiodata),
            'format' => $response_format,
            'permanent' => true
        ];
    }

    /**
     * Save user audio permanently using Moodle's File API
     *
     * @param string $audiodata Base64 encoded audio data
     * @param int $contextid The context ID for the file area
     * @param int $conversationid The conversation ID (used as itemid)
     * @return array ['url' => string, 'filename' => string] or ['error' => string]
     */
    public static function save_user_audio(string $audiodata, int $contextid, int $conversationid): array
    {
        global $CFG, $USER;

        // Detect MIME type from data URI
        $mimetype = 'webm';
        $extension = 'webm';
        if (strpos($audiodata, 'data:audio/') === 0) {
            if (preg_match('#^data:audio/([a-z0-9]+)(?:;[^;]+)*;base64,#i', $audiodata, $matches)) {
                $mimetype = strtolower($matches[1]);
                $extension = $mimetype;
            }
        }

        // Remove data URI prefix and decode
        $audiodata = preg_replace('#^data:audio/[a-z0-9]+(?:;[^;]+)*;base64,#i', '', $audiodata);
        $audiobinary = base64_decode($audiodata, true);

        if ($audiobinary === false || strlen($audiobinary) < 100) {
            return ['error' => 'Invalid audio data'];
        }

        // Generate unique filename
        $filename = 'useraudio_' . $conversationid . '_' . time() . '_' . uniqid() . '.' . $extension;

        // Prepare file record
        $fs = get_file_storage();
        $fileinfo = [
            'contextid' => $contextid,
            'component' => 'mod_intebchat',
            'filearea' => 'useraudio',
            'itemid' => $conversationid,
            'filepath' => '/',
            'filename' => $filename,
            'userid' => $USER->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];

        // Check if file already exists and delete it
        $existingfile = $fs->get_file(
            $fileinfo['contextid'],
            $fileinfo['component'],
            $fileinfo['filearea'],
            $fileinfo['itemid'],
            $fileinfo['filepath'],
            $fileinfo['filename']
        );
        if ($existingfile) {
            $existingfile->delete();
        }

        // Create the file from string
        $file = $fs->create_file_from_string($fileinfo, $audiobinary);

        if (!$file) {
            return ['error' => 'Failed to save audio file'];
        }

        // Generate the URL using pluginfile.php
        $url = \moodle_url::make_pluginfile_url(
            $contextid,
            'mod_intebchat',
            'useraudio',
            $conversationid,
            '/',
            $filename
        );

        return [
            'url' => $url->out(),
            'filename' => $filename,
        ];
    }
}