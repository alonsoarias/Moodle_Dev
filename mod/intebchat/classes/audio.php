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
    /**
     * Transcribe audio to text using OpenAI Whisper with enhanced token tracking.
     *
     * @param string $audio Base64 encoded MP3 data
     * @param string|null $lang Language hint
     * @return array
     */
    public static function transcribe(string $audio, ?string $lang = null): array
    {
        global $CFG;

        $mimetype = 'mp3';
        if (strpos($audio, 'data:audio/') === 0) {
            if (strpos($audio, 'audio/webm') !== false) {
                $mimetype = 'webm';
            } else if (strpos($audio, 'audio/mp4') !== false) {
                $mimetype = 'mp4';
            }
        }
        $audio = preg_replace('#^data:audio/\w+;base64,#i', '', $audio);
        $audiodata = base64_decode($audio);
        
        // Calculate audio file size for duration estimation
        $file_size = strlen($audiodata);
        
        $filename = uniqid();
        $filepath = "{$CFG->dataroot}/temp/{$filename}.{$mimetype}";

        // Ensure temp directory exists
        if (!file_exists("{$CFG->dataroot}/temp")) {
            mkdir("{$CFG->dataroot}/temp", 0777, true);
        }

        file_put_contents($filepath, $audiodata);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file' => curl_file_create($filepath, 'audio/' . $mimetype, 'audio.' . $mimetype),
            'model' => 'whisper-1',
            'response_format' => 'verbose_json', // Get detailed response with duration
            'language' => $lang,
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
            'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
        ]);

        $result = curl_exec($ch);
        curl_close($ch);  // Keep file for playback
        
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
     * Convert text to speech using OpenAI TTS with token tracking.
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use
     * @return array URL and token info
     */
    public static function speech_with_tracking(string $input, string $voice = 'alloy'): array
    {
        global $CFG;
        
        // Calculate character count for token estimation
        $char_count = strlen($input);
        
        $json = json_encode((object) [
            'model' => 'tts-1',
            'input' => $input,
            'voice' => $voice,
            'response_format' => 'mp3',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/speech');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
        ]);

        $audiodata = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200) {
            return [
                'url' => '',
                'error' => 'TTS generation failed',
                'tokens' => 0
            ];
        }

        // Ensure temp directory exists
        if (!file_exists("{$CFG->dataroot}/temp")) {
            mkdir("{$CFG->dataroot}/temp", 0777, true);
        }

        $filename = uniqid();
        $filepath = "{$CFG->dataroot}/temp/{$filename}.mp3";
        file_put_contents($filepath, $audiodata);
        
        // Calculate audio file size
        $file_size = strlen($audiodata);

        return [
            'url' => "{$CFG->wwwroot}/mod/intebchat/load-audio-temp.php?filename={$filename}",
            'tokens' => $char_count, // TTS is billed per character
            'file_size' => $file_size,
            'duration' => $file_size / (16 * 1024) // Rough duration estimate
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
}