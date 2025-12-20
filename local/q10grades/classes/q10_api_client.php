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
 * Q10 API Client for grade synchronization.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_q10grades;

defined('MOODLE_INTERNAL') || die();

use curl;
use moodle_exception;

/**
 * Q10 API Client class.
 *
 * This class handles all communication with the Q10 API.
 * The API endpoints and authentication methods are based on typical REST API patterns
 * and should be adjusted according to Q10's official API documentation.
 */
class q10_api_client {

    /** @var string API base URL */
    private $apiurl;

    /** @var string API key */
    private $apikey;

    /** @var string API secret */
    private $apisecret;

    /** @var string Institution ID */
    private $institutionid;

    /** @var bool Debug mode */
    private $debug;

    /** @var string Access token */
    private $accesstoken;

    /** @var int Token expiry timestamp */
    private $tokenexpiry;

    /**
     * Constructor.
     *
     * @param string|null $apiurl API URL (optional, will use config if not provided)
     * @param string|null $apikey API key (optional, will use config if not provided)
     * @param string|null $apisecret API secret (optional, will use config if not provided)
     * @param string|null $institutionid Institution ID (optional, will use config if not provided)
     */
    public function __construct($apiurl = null, $apikey = null, $apisecret = null, $institutionid = null) {
        $this->apiurl = $apiurl ?? get_config('local_q10grades', 'apiurl');
        $this->apikey = $apikey ?? get_config('local_q10grades', 'apikey');
        $this->apisecret = $apisecret ?? get_config('local_q10grades', 'apisecret');
        $this->institutionid = $institutionid ?? get_config('local_q10grades', 'institutionid');
        $this->debug = (bool) get_config('local_q10grades', 'debug');
    }

    /**
     * Check if the API is configured.
     *
     * @return bool True if configured, false otherwise.
     */
    public function is_configured(): bool {
        return !empty($this->apiurl) && !empty($this->apikey) && !empty($this->apisecret);
    }

    /**
     * Authenticate with Q10 API and get access token.
     *
     * @return bool True on success, false on failure.
     * @throws moodle_exception If authentication fails.
     */
    public function authenticate(): bool {
        // Check if we have a valid token.
        if ($this->accesstoken && $this->tokenexpiry && time() < $this->tokenexpiry) {
            return true;
        }

        $endpoint = $this->apiurl . '/auth/token';

        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->apikey,
            'client_secret' => $this->apisecret,
        ];

        if (!empty($this->institutionid)) {
            $params['institution_id'] = $this->institutionid;
        }

        $response = $this->make_request('POST', $endpoint, $params, false);

        if (isset($response['access_token'])) {
            $this->accesstoken = $response['access_token'];
            // Token expires in 1 hour by default, we refresh 5 minutes before.
            $expiresin = $response['expires_in'] ?? 3600;
            $this->tokenexpiry = time() + $expiresin - 300;
            return true;
        }

        throw new moodle_exception('authfailed', 'local_q10grades', '', $response['error'] ?? 'Unknown error');
    }

    /**
     * Get list of academic periods from Q10.
     *
     * @return array List of academic periods.
     */
    public function get_periods(): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/periods';
        return $this->make_request('GET', $endpoint);
    }

    /**
     * Get list of subjects/materias from Q10.
     *
     * @param string|null $periodid Filter by period ID.
     * @return array List of subjects.
     */
    public function get_subjects(?string $periodid = null): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/subjects';

        $params = [];
        if ($periodid) {
            $params['period_id'] = $periodid;
        }

        return $this->make_request('GET', $endpoint, $params);
    }

    /**
     * Get list of students enrolled in a subject.
     *
     * @param string $subjectid Subject ID.
     * @param string|null $groupid Group ID (optional).
     * @return array List of students.
     */
    public function get_students(string $subjectid, ?string $groupid = null): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/subjects/' . urlencode($subjectid) . '/students';

        $params = [];
        if ($groupid) {
            $params['group_id'] = $groupid;
        }

        return $this->make_request('GET', $endpoint, $params);
    }

    /**
     * Get evaluation components for a subject.
     *
     * @param string $subjectid Subject ID.
     * @return array List of evaluation components.
     */
    public function get_evaluation_components(string $subjectid): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/subjects/' . urlencode($subjectid) . '/components';
        return $this->make_request('GET', $endpoint);
    }

    /**
     * Upload grades for a specific subject and component.
     *
     * @param string $subjectid Q10 subject ID.
     * @param string $componentid Q10 evaluation component ID.
     * @param array $grades Array of grades with student_id and grade value.
     * @param string|null $periodid Period ID (optional).
     * @return array API response.
     */
    public function upload_grades(string $subjectid, string $componentid, array $grades, ?string $periodid = null): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/grades';

        $payload = [
            'subject_id' => $subjectid,
            'component_id' => $componentid,
            'grades' => $grades,
        ];

        if ($periodid) {
            $payload['period_id'] = $periodid;
        }

        return $this->make_request('POST', $endpoint, $payload);
    }

    /**
     * Upload final course grades.
     *
     * @param string $subjectid Q10 subject ID.
     * @param array $grades Array of grades with student_id and final_grade.
     * @param string|null $periodid Period ID (optional).
     * @return array API response.
     */
    public function upload_final_grades(string $subjectid, array $grades, ?string $periodid = null): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/grades/final';

        $payload = [
            'subject_id' => $subjectid,
            'grades' => $grades,
        ];

        if ($periodid) {
            $payload['period_id'] = $periodid;
        }

        return $this->make_request('POST', $endpoint, $payload);
    }

    /**
     * Bulk upload grades for multiple students and components.
     *
     * @param array $data Array containing subject_id and grades data.
     * @return array API response.
     */
    public function bulk_upload_grades(array $data): array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/academic/grades/bulk';
        return $this->make_request('POST', $endpoint, $data);
    }

    /**
     * Validate student mapping.
     *
     * @param string $studentidentifier Student identifier (code, ID, email).
     * @return array|null Student data if found, null otherwise.
     */
    public function find_student(string $studentidentifier): ?array {
        $this->authenticate();
        $endpoint = $this->apiurl . '/students/search';

        $params = ['q' => $studentidentifier];
        $response = $this->make_request('GET', $endpoint, $params);

        if (!empty($response['data']) && count($response['data']) === 1) {
            return $response['data'][0];
        }

        return null;
    }

    /**
     * Test API connection.
     *
     * @return array Connection test result.
     */
    public function test_connection(): array {
        try {
            $this->authenticate();

            // Try to get periods as a simple test.
            $periods = $this->get_periods();

            return [
                'success' => true,
                'message' => get_string('connectionsuccessful', 'local_q10grades'),
                'periods_count' => count($periods['data'] ?? []),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Make HTTP request to Q10 API.
     *
     * @param string $method HTTP method (GET, POST, PUT, DELETE).
     * @param string $endpoint API endpoint URL.
     * @param array $params Request parameters.
     * @param bool $authenticated Whether to include auth token.
     * @return array Response data.
     * @throws moodle_exception If request fails.
     */
    private function make_request(string $method, string $endpoint, array $params = [], bool $authenticated = true): array {
        $curl = new curl();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        if ($authenticated && $this->accesstoken) {
            $headers[] = 'Authorization: Bearer ' . $this->accesstoken;
        }

        $curl->setHeader($headers);

        // Configure timeout.
        $curl->setopt([
            'CURLOPT_TIMEOUT' => 30,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        $this->log_debug("Making $method request to: $endpoint");

        switch (strtoupper($method)) {
            case 'GET':
                if (!empty($params)) {
                    $endpoint .= '?' . http_build_query($params);
                }
                $response = $curl->get($endpoint);
                break;

            case 'POST':
                $response = $curl->post($endpoint, json_encode($params));
                break;

            case 'PUT':
                $response = $curl->put($endpoint, json_encode($params));
                break;

            case 'DELETE':
                $response = $curl->delete($endpoint);
                break;

            default:
                throw new moodle_exception('invalidhttpmethod', 'local_q10grades', '', $method);
        }

        $httpcode = $curl->get_info()['http_code'] ?? 0;
        $this->log_debug("Response code: $httpcode");
        $this->log_debug("Response body: $response");

        if ($httpcode >= 400) {
            $error = json_decode($response, true);
            $errormsg = $error['message'] ?? $error['error'] ?? "HTTP Error: $httpcode";
            throw new moodle_exception('apierror', 'local_q10grades', '', $errormsg);
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new moodle_exception('invalidjsonresponse', 'local_q10grades');
        }

        return $data;
    }

    /**
     * Log debug message.
     *
     * @param string $message Debug message.
     */
    private function log_debug(string $message): void {
        if ($this->debug) {
            debugging('[Q10 API] ' . $message, DEBUG_DEVELOPER);
        }
    }
}
