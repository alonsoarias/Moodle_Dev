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
 * Audio helper functions based on local_geniai plugin.
 *
 * @package    mod_intebchat
 * @copyright  2024 Eduardo Kraus
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;

defined('MOODLE_INTERNAL') || die();

class audio
{
    /**
     * Transcribe audio to text using OpenAI Whisper.
     *
     * @param string $audio Base64 encoded MP3 data
     * @param string|null $lang Language hint
     * @return array
     */
    public static function transcribe(string $audio, ?string $lang = null): array
    {
        global $CFG;

        $audio = str_replace('data:audio/mp3;base64,', '', $audio);
        $audiodata = base64_decode($audio);
        $filename = uniqid();
        $filepath = "{$CFG->dataroot}/temp/{$filename}.mp3";

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
            'file' => curl_file_create($filepath),
            'model' => 'whisper-1',
            'response_format' => 'verbose_json',
            'language' => $lang,
        ]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
            'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
        ]);

        $result = curl_exec($ch);
        curl_close($ch);  // El archivo se mantiene para poder reproducirlo luego
        $result = json_decode($result);

        return [
            'text' => $result->text ?? '',
            'language' => $result->language ?? '',
            'filename' => $filename,
        ];
    }

    /**
     * Convert text to speech using OpenAI TTS.
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use
     * @return string URL to generated audio
     */
    public static function speech(string $input, string $voice = 'alloy'): string
    {
        global $CFG;

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
        curl_close($ch);

        // Ensure temp directory exists
        if (!file_exists("{$CFG->dataroot}/temp")) {
            mkdir("{$CFG->dataroot}/temp", 0777, true);
        }

        $filename = uniqid();
        $filepath = "{$CFG->dataroot}/temp/{$filename}.mp3";
        file_put_contents($filepath, $audiodata);

        return "{$CFG->wwwroot}/mod/intebchat/load-audio-temp.php?filename={$filename}";
    }
}
