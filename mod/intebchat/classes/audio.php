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
 * Audio helper functions focusing on lowest latency while keeping original logic/shape.
 *
 * - Transcribe: usa gpt-4o-mini-transcribe (rápido). Si se pasa $lang, usa whisper-1 + verbose_json (compatibilidad).
 * - TTS: intenta gpt-4o-mini-tts y hace fallback a tts-1.
 *
 * @package    mod_intebchat
 * @copyright  2024 Eduardo Kraus
 * @copyright  Optimized 2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;

defined('MOODLE_INTERNAL') || die();

class audio
{
    /**
     * Transcribe audio to text with fastest path by default.
     * Mantiene firma y forma de retorno original.
     *
     * @param string $audio Base64 encoded audio (data URI o base64 crudo)
     * @param string|null $lang Language hint (si se pasa, fuerza whisper-1 + verbose_json)
     * @return array ['text','language','duration','filename','file_size']
     */
    public static function transcribe(string $audio, ?string $lang = null): array
    {
        global $CFG;

        // Detect extension (default mp3) desde data URI si aplica
        $mimetype = 'mp3';
        if (strpos($audio, 'data:audio/') === 0) {
            if (strpos($audio, 'audio/webm') !== false) {
                $mimetype = 'webm';
            } else if (strpos($audio, 'audio/mp4') !== false) {
                $mimetype = 'mp4';
            } else if (strpos($audio, 'audio/wav') !== false || strpos($audio, 'audio/x-wav') !== false) {
                $mimetype = 'wav';
            } else if (strpos($audio, 'audio/m4a') !== false) {
                $mimetype = 'm4a';
            } else if (strpos($audio, 'audio/ogg') !== false || strpos($audio, 'audio/opus') !== false) {
                $mimetype = 'ogg';
            } else if (strpos($audio, 'audio/mpeg') !== false) {
                $mimetype = 'mp3';
            }
        }

        // Limpia cabecera data URI y decodifica
        $audio = preg_replace('#^data:audio/[a-z0-9.+-]+;base64,#i', '', $audio);
        $audiodata = base64_decode($audio);
        if ($audiodata === false) {
            return [
                'text' => '',
                'language' => '',
                'duration' => 0,
                'filename' => '',
                'file_size' => 0,
            ];
        }

        // Tamaño (para estimar duración si hace falta)
        $file_size = strlen($audiodata);

        // Archivo temporal (se mantiene para reproducción, como en la clase original)
        $filename = uniqid();
        $tempdir = rtrim($CFG->dataroot, '/').'/temp';
        if (!file_exists($tempdir)) {
            @mkdir($tempdir, 0777, true);
        }
        $filepath = "{$tempdir}/{$filename}.{$mimetype}";
        file_put_contents($filepath, $audiodata);

        // Selección de modelo enfocada en latencia:
        // - Si hay $lang => whisper-1 + verbose_json (para compatibilidad con language/duration/segments).
        // - En caso contrario => gpt-4o-mini-transcribe + json (más rápido).
        $usewhisper = !empty($lang);
        $model = $usewhisper ? 'whisper-1' : 'gpt-4o-mini-transcribe';
        $response_format = $usewhisper ? 'verbose_json' : 'json';

        // Construir multipart (deja que cURL maneje el boundary; no forzar Content-Type)
        $postfields = [
            'file' => curl_file_create($filepath, 'audio/' . $mimetype, 'audio.' . $mimetype),
            'model' => $model,
            'response_format' => $response_format,
        ];
        if ($usewhisper && !empty($lang)) {
            $postfields['language'] = $lang;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/transcriptions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);

        // Optimizaciones de red para reducir latencia
        if (defined('CURL_HTTP_VERSION_2TLS')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
        }
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        if (defined('CURLOPT_TCP_KEEPIDLE')) curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 30);
        if (defined('CURLOPT_TCP_KEEPINTVL')) curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 15);
        if (defined('CURL_IPRESOLVE_V4')) curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
            'Accept: application/json',
            'Connection: keep-alive',
            'Expect:', // evita 100-continue
        ]);

        $result = curl_exec($ch);
        // No cerramos ni borramos el archivo aquí (se mantiene para playback); cleanup lo hace cleanup_temp_files()
        curl_close($ch);

        // Intentamos parsear JSON; si no, devolvemos lo más seguro posible
        $json = json_decode($result);
        $text = '';
        $language = '';
        $duration = 0.0;

        if (is_object($json)) {
            $text = isset($json->text) ? (string)$json->text : '';
            // Whisper con verbose_json suele devolver language y duration
            if (isset($json->language)) {
                $language = (string)$json->language;
            }
            if (isset($json->duration)) {
                $duration = (float)$json->duration;
            }
        }

        // Si no vino duración en la respuesta, estima por bitrate promedio (~128 kbps = 16 KB/s)
        if (!$duration) {
            $duration = $file_size / (16 * 1024);
        }

        return [
            'text' => $text,
            'language' => $language,
            'duration' => $duration,
            'filename' => $filename,
            'file_size' => $file_size,
        ];
    }

    /**
     * Convert text to speech using the fastest model, with fallback.
     * Mantiene firma y forma de retorno original.
     *
     * @param string $input Text to convert
     * @param string $voice Voice to use
     * @return array ['url','tokens','file_size','duration',('error' opcional si falla)]
     */
    public static function speech_with_tracking(string $input, string $voice = 'alloy'): array
    {
        global $CFG;

        // Estimación simple de "tokens" como caracteres (misma lógica original)
        $char_count = strlen($input);

        // Intento rápido: gpt-4o-mini-tts
        $result = self::do_tts_request($input, $voice, 'gpt-4o-mini-tts');
        if ($result['ok'] === false) {
            // Fallback compatible: tts-1
            $result = self::do_tts_request($input, $voice, 'tts-1');
        }

        if ($result['ok'] === false) {
            return [
                'url' => '',
                'error' => $result['error'] ?: 'TTS generation failed',
                'tokens' => 0,
                'file_size' => 0,
                'duration' => 0.0,
            ];
        }

        // Guardar mp3 en temp (igual que antes)
        $tempdir = rtrim($CFG->dataroot, '/').'/temp';
        if (!file_exists($tempdir)) {
            @mkdir($tempdir, 0777, true);
        }
        $filename = uniqid();
        $filepath = "{$tempdir}/{$filename}.mp3";
        file_put_contents($filepath, $result['data']);

        $file_size = strlen($result['data']);
        // Estimar duración por tamaño (evitamos decodificar el mp3)
        $duration = $file_size / (16 * 1024);

        return [
            'url' => "{$CFG->wwwroot}/mod/intebchat/load-audio-temp.php?filename={$filename}",
            'tokens' => $char_count,
            'file_size' => $file_size,
            'duration' => $duration
        ];
    }

    /**
     * Wrapper de compatibilidad (misma firma original).
     */
    public static function speech(string $input, string $voice = 'alloy'): string
    {
        $result = self::speech_with_tracking($input, $voice);
        return $result['url'];
    }

    /**
     * Clean up old temporary audio files.
     *
     * @param int $max_age Maximum age in seconds (default 1 hour)
     * @return int Number of files cleaned
     */
    public static function cleanup_temp_files($max_age = 3600)
    {
        global $CFG;

        $tempdir = rtrim($CFG->dataroot, '/').'/temp/';
        $cleaned = 0;

        if (!file_exists($tempdir)) {
            return 0;
        }

        if ($handle = opendir($tempdir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                if (!preg_match('/\.(mp3|webm|wav|mp4|ogg|m4a)$/i', $file)) {
                    continue;
                }
                $filepath = $tempdir . $file;
                $filemtime = @filemtime($filepath);
                if ($filemtime && (time() - $filemtime > $max_age)) {
                    @unlink($filepath);
                    $cleaned++;
                }
            }
            closedir($handle);
        }

        return $cleaned;
    }

    // ======== Helpers internos ========

    /**
     * Hace una llamada TTS y devuelve audio binario o error.
     *
     * @param string $input
     * @param string $voice
     * @param string $model
     * @return array ['ok'=>bool,'data'=>string,'error'=>string]
     */
    private static function do_tts_request(string $input, string $voice, string $model): array
    {
        $payload = json_encode((object) [
            'model' => $model,
            'input' => $input,
            'voice' => $voice,
            'response_format' => 'mp3',
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/audio/speech');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

        // Optimizaciones red
        if (defined('CURL_HTTP_VERSION_2TLS')) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_2TLS);
        }
        curl_setopt($ch, CURLOPT_TCP_KEEPALIVE, 1);
        if (defined('CURLOPT_TCP_KEEPIDLE')) curl_setopt($ch, CURLOPT_TCP_KEEPIDLE, 30);
        if (defined('CURLOPT_TCP_KEEPINTVL')) curl_setopt($ch, CURLOPT_TCP_KEEPINTVL, 15);
        if (defined('CURL_IPRESOLVE_V4')) curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: audio/mpeg',
            'Authorization: Bearer ' . get_config('mod_intebchat', 'apikey'),
            'Connection: keep-alive',
            'Expect:',
        ]);

        $audiodata = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlerr = curl_error($ch);
        curl_close($ch);

        if ($http_code !== 200 || $audiodata === false) {
            return ['ok' => false, 'data' => '', 'error' => $curlerr ?: (string)$audiodata];
        }

        return ['ok' => true, 'data' => $audiodata, 'error' => ''];
    }
}
