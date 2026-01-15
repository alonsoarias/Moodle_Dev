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
 * Rate limiter class for API request throttling.
 *
 * Implements a sliding window rate limiting algorithm using Moodle's cache API.
 * Prevents abuse by limiting requests per user and per IP address.
 *
 * @package    mod_intebchat
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_intebchat;

defined('MOODLE_INTERNAL') || die();

/**
 * Rate limiter implementation using sliding window algorithm.
 */
class ratelimiter {

    /** @var int Default requests per minute for authenticated users */
    const DEFAULT_USER_LIMIT = 60;

    /** @var int Default requests per minute for IP-based limiting */
    const DEFAULT_IP_LIMIT = 30;

    /** @var int Window size in seconds (1 minute) */
    const WINDOW_SIZE = 60;

    /** @var int Cleanup probability (1 in N requests) */
    const CLEANUP_PROBABILITY = 100;

    /** @var \cache Cache instance for rate limiting data */
    private $cache;

    /** @var int Maximum requests per window for users */
    private $userlimit;

    /** @var int Maximum requests per window for IPs */
    private $iplimit;

    /** @var bool Whether rate limiting is enabled */
    private $enabled;

    /**
     * Constructor.
     *
     * @param int|null $userlimit Override user limit (null for config/default)
     * @param int|null $iplimit Override IP limit (null for config/default)
     */
    public function __construct(?int $userlimit = null, ?int $iplimit = null) {
        $config = get_config('mod_intebchat');

        $this->enabled = !empty($config->enableratelimit);
        $this->userlimit = $userlimit ?? ($config->ratelimit_user ?? self::DEFAULT_USER_LIMIT);
        $this->iplimit = $iplimit ?? ($config->ratelimit_ip ?? self::DEFAULT_IP_LIMIT);

        // Use application cache for rate limiting
        $this->cache = \cache::make('mod_intebchat', 'ratelimit');
    }

    /**
     * Check if a request is allowed for the given user.
     *
     * @param int $userid The user ID
     * @param string|null $ip Optional IP address (auto-detected if null)
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int, 'reason' => string]
     */
    public function check(int $userid, ?string $ip = null): array {
        // If rate limiting is disabled, always allow
        if (!$this->enabled) {
            return [
                'allowed' => true,
                'remaining' => PHP_INT_MAX,
                'reset_in' => 0,
                'reason' => ''
            ];
        }

        $now = time();
        $ip = $ip ?? $this->get_client_ip();

        // Check user limit first
        $userResult = $this->check_limit('user_' . $userid, $this->userlimit, $now);
        if (!$userResult['allowed']) {
            $userResult['reason'] = 'user_limit_exceeded';
            return $userResult;
        }

        // Check IP limit (additional protection)
        $ipResult = $this->check_limit('ip_' . $this->hash_ip($ip), $this->iplimit, $now);
        if (!$ipResult['allowed']) {
            $ipResult['reason'] = 'ip_limit_exceeded';
            return $ipResult;
        }

        // Request allowed - record it
        $this->record_request('user_' . $userid, $now);
        $this->record_request('ip_' . $this->hash_ip($ip), $now);

        // Periodic cleanup
        if (rand(1, self::CLEANUP_PROBABILITY) === 1) {
            $this->cleanup_old_entries('user_' . $userid, $now);
            $this->cleanup_old_entries('ip_' . $this->hash_ip($ip), $now);
        }

        // Return the more restrictive remaining count
        $remaining = min($userResult['remaining'] - 1, $ipResult['remaining'] - 1);

        return [
            'allowed' => true,
            'remaining' => max(0, $remaining),
            'reset_in' => self::WINDOW_SIZE,
            'reason' => ''
        ];
    }

    /**
     * Check the rate limit for a specific key.
     *
     * @param string $key The cache key (user_X or ip_X)
     * @param int $limit The maximum allowed requests
     * @param int $now Current timestamp
     * @return array ['allowed' => bool, 'remaining' => int, 'reset_in' => int]
     */
    private function check_limit(string $key, int $limit, int $now): array {
        $data = $this->cache->get($key);

        if ($data === false) {
            // No existing data - first request
            return [
                'allowed' => true,
                'remaining' => $limit,
                'reset_in' => self::WINDOW_SIZE
            ];
        }

        $requests = json_decode($data, true);
        if (!is_array($requests)) {
            $requests = [];
        }

        // Count requests within the window
        $windowStart = $now - self::WINDOW_SIZE;
        $recentRequests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        $count = count($recentRequests);
        $remaining = max(0, $limit - $count);

        // Calculate when the oldest request in window will expire
        $resetIn = self::WINDOW_SIZE;
        if (!empty($recentRequests)) {
            $oldest = min($recentRequests);
            $resetIn = max(1, ($oldest + self::WINDOW_SIZE) - $now);
        }

        return [
            'allowed' => $count < $limit,
            'remaining' => $remaining,
            'reset_in' => $resetIn
        ];
    }

    /**
     * Record a request for the given key.
     *
     * @param string $key The cache key
     * @param int $now Current timestamp
     */
    private function record_request(string $key, int $now): void {
        $data = $this->cache->get($key);
        $requests = ($data !== false) ? json_decode($data, true) : [];

        if (!is_array($requests)) {
            $requests = [];
        }

        // Add current request
        $requests[] = $now;

        // Remove old entries (outside window + buffer)
        $cutoff = $now - (self::WINDOW_SIZE * 2);
        $requests = array_filter($requests, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        // Re-index array and save
        $this->cache->set($key, json_encode(array_values($requests)));
    }

    /**
     * Clean up old entries for a key.
     *
     * @param string $key The cache key
     * @param int $now Current timestamp
     */
    private function cleanup_old_entries(string $key, int $now): void {
        $data = $this->cache->get($key);
        if ($data === false) {
            return;
        }

        $requests = json_decode($data, true);
        if (!is_array($requests) || empty($requests)) {
            $this->cache->delete($key);
            return;
        }

        // Remove entries older than 2x window size
        $cutoff = $now - (self::WINDOW_SIZE * 2);
        $requests = array_filter($requests, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        if (empty($requests)) {
            $this->cache->delete($key);
        } else {
            $this->cache->set($key, json_encode(array_values($requests)));
        }
    }

    /**
     * Get the client IP address.
     *
     * @return string The client IP address
     */
    private function get_client_ip(): string {
        // Check for forwarded IP (behind proxy/load balancer)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy header
            'HTTP_X_REAL_IP',            // Nginx
            'REMOTE_ADDR'                // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // X-Forwarded-For can contain multiple IPs, take the first
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validate IP format
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Hash an IP address for privacy.
     *
     * @param string $ip The IP address
     * @return string Hashed IP (first 16 chars of SHA256)
     */
    private function hash_ip(string $ip): string {
        global $CFG;
        $salt = $CFG->secretsaltmain ?? $CFG->passwordsaltmain ?? 'intebchat';
        return substr(hash('sha256', $ip . $salt), 0, 16);
    }

    /**
     * Reset rate limit for a specific user.
     *
     * @param int $userid The user ID to reset
     * @return bool Success
     */
    public function reset_user(int $userid): bool {
        return $this->cache->delete('user_' . $userid);
    }

    /**
     * Get current usage stats for a user.
     *
     * @param int $userid The user ID
     * @return array ['requests' => int, 'limit' => int, 'remaining' => int]
     */
    public function get_user_stats(int $userid): array {
        $now = time();
        $key = 'user_' . $userid;
        $data = $this->cache->get($key);

        if ($data === false) {
            return [
                'requests' => 0,
                'limit' => $this->userlimit,
                'remaining' => $this->userlimit
            ];
        }

        $requests = json_decode($data, true);
        if (!is_array($requests)) {
            $requests = [];
        }

        // Count requests within the window
        $windowStart = $now - self::WINDOW_SIZE;
        $recentRequests = array_filter($requests, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        $count = count($recentRequests);

        return [
            'requests' => $count,
            'limit' => $this->userlimit,
            'remaining' => max(0, $this->userlimit - $count)
        ];
    }

    /**
     * Check if rate limiting is enabled.
     *
     * @return bool True if rate limiting is enabled
     */
    public function is_enabled(): bool {
        return $this->enabled;
    }
}
