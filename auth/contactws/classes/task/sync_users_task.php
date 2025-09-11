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

namespace auth_contactws\task;

defined('MOODLE_INTERNAL') || die();

use core\task\scheduled_task;
use auth_contactws\auth;

/**
 * Task to synchronize users and suspend those not present in the SARH API.
 *
 * @package    auth_contactws
 * @copyright  2025 Pedro Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sync_users_task extends scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('task_sync_users', 'auth_contactws');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB, $CFG;
        
        mtrace('Starting SARH user synchronization task...');
        
        // Registrar el tiempo de inicio
        $startTime = microtime(true);
        
        // Check if auth_contactws is enabled
        if (!is_enabled_auth('contactws')) {
            mtrace('SARH authentication is disabled. Skipping task.');
            return;
        }
        
        $config = get_config('auth_contactws');
        
        // Get the auth plugin instance
        $authplugin = new auth();
        
        // Get active users from API PRIMERO (necesario para la migración)
        $token = $this->get_api_token($config);
        if (!$token) {
            mtrace('Failed to get API token. Skipping task.');
            return;
        }
        
        $apiusers = $this->get_users_from_api($token);
        if ($apiusers === false) {
            mtrace('Failed to get users from API. Skipping task.');
            return;
        }
        
        // NUEVA FUNCIONALIDAD: Migrar usuarios que están en SARH a auth_contactws
        $this->migrate_sarh_users_to_contactws($apiusers);
        
        // Process users con nueva lógica
        $this->process_users_with_new_logic($apiusers);
        
        // Calcular el tiempo de ejecución
        $executionTime = microtime(true) - $startTime;
        set_config('sync_execution_time', $executionTime, 'auth_contactws');
        
        mtrace('SARH user synchronization completed in ' . round($executionTime, 2) . ' seconds.');
    }
    
    /**
     * NUEVA FUNCIÓN: Migrar usuarios que están en SARH a auth_contactws
     * Solo migra usuarios que existen tanto en Moodle como en SARH
     * 
     * @param array $apiusers Lista de usuarios desde SARH API
     */
    private function migrate_sarh_users_to_contactws($apiusers) {
        global $DB;
        
        mtrace('Starting migration of SARH users to auth_contactws...');
        
        // Crear un mapa de documentos de SARH para búsqueda rápida
        $sarh_documents = [];
        foreach ($apiusers as $apiuser) {
            $docnumber = $apiuser['NumeroDocumento'];
            if (!empty($docnumber)) {
                $sarh_documents[$docnumber] = [
                    'Estado' => $apiuser['Estado'],
                    'NEstado' => $apiuser['NEstado'] ?? '',
                    'Usuario' => $apiuser['Usuario'] ?? ''
                ];
            }
        }
        
        if (empty($sarh_documents)) {
            mtrace('No valid document numbers found in SARH API response.');
            return;
        }
        
        mtrace('Found ' . count($sarh_documents) . ' users in SARH API.');
        
        // Obtener usuarios de Moodle que:
        // 1. NO están en auth_contactws
        // 2. Tienen un idnumber (documento)
        // 3. Están activos (no suspendidos ni eliminados)
        // 4. No son usuarios del sistema (guest, admin)
        $sql = "SELECT id, username, auth, idnumber, email, firstname, lastname
                FROM {user} 
                WHERE deleted = 0 
                AND suspended = 0 
                AND auth != 'contactws' 
                AND auth != 'nologin'
                AND idnumber IS NOT NULL
                AND idnumber != ''
                AND id > 2"; // Excluir guest (id=1) y admin (id=2)
        
        $moodle_users = $DB->get_records_sql($sql);
        
        if (empty($moodle_users)) {
            mtrace('No eligible users found in Moodle for migration.');
            return;
        }
        
        $migrated_count = 0;
        $skipped_count = 0;
        $failed_count = 0;
        
        foreach ($moodle_users as $user) {
            try {
                // Verificar si el usuario existe en SARH
                if (!isset($sarh_documents[$user->idnumber])) {
                    mtrace("User {$user->username} (ID: {$user->id}, Doc: {$user->idnumber}) not found in SARH, skipping migration.");
                    $skipped_count++;
                    continue;
                }
                
                // El usuario existe en SARH, proceder con la migración
                $sarh_info = $sarh_documents[$user->idnumber];
                mtrace("User {$user->username} found in SARH with status: {$sarh_info['NEstado']} ({$sarh_info['Estado']})");
                
                // Actualizar el método de autenticación a contactws
                $updateuser = new \stdClass();
                $updateuser->id = $user->id;
                $updateuser->auth = 'contactws';
                $updateuser->timemodified = time();
                
                if ($DB->update_record('user', $updateuser)) {
                    mtrace("✓ Migrated user {$user->username} (ID: {$user->id}) from '{$user->auth}' to 'contactws'");
                    $migrated_count++;
                } else {
                    mtrace("✗ Failed to migrate user {$user->username} (ID: {$user->id})");
                    $failed_count++;
                }
                
            } catch (\Exception $e) {
                mtrace("✗ Error migrating user {$user->username}: " . $e->getMessage());
                $failed_count++;
            }
        }
        
        mtrace("=====================================");
        mtrace("Migration Summary:");
        mtrace("  - Users migrated: {$migrated_count}");
        mtrace("  - Users skipped (not in SARH): {$skipped_count}");
        mtrace("  - Failed migrations: {$failed_count}");
        mtrace("=====================================");
        
        // Guardar estadísticas de migración
        set_config('last_migration_count', $migrated_count, 'auth_contactws');
        set_config('last_migration_skipped', $skipped_count, 'auth_contactws');
        set_config('last_migration_time', time(), 'auth_contactws');
    }
    
    /**
     * Get API token for authentication.
     *
     * @param object $config Plugin configuration
     * @return string|bool Token or false on failure
     */
    private function get_api_token($config) {
        mtrace('Getting API token...');
        
        $curl = curl_init();
        $url = rtrim($config->baseurl, '/') . '/login';
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'Username' => $config->apiusername,
                'Password' => $config->apipassword
            ]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            mtrace('CURL Error: ' . curl_error($curl));
        }
        
        curl_close($curl);
        
        if ($httpcode === 200) {
            $data = json_decode($response, true);
            $token = $data['Token'] ?? null;
            
            if ($token) {
                mtrace('API token obtained successfully.');
                return $token;
            }
        }
        
        mtrace('Failed to obtain API token. HTTP Code: ' . $httpcode);
        return false;
    }
    
    /**
     * Get users from the estados API endpoint.
     *
     * @param string $token The authentication token
     * @return array|bool Array of users or false on failure
     */
    private function get_users_from_api($token) {
        global $CFG;
        
        mtrace('Getting users from API...');
        
        $config = get_config('auth_contactws');
        $curl = curl_init();
        $url = rtrim($config->baseurl, '/') . '/estados';
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => '',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $token
            ],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20
        ]);
        
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        if (curl_errno($curl)) {
            mtrace('CURL Error: ' . curl_error($curl));
        }
        
        curl_close($curl);
        
        if ($httpcode === 200) {
            $data = json_decode($response, true);
            
            if (isset($data['RespuestaSolicitud']) && $data['RespuestaSolicitud'] === true) {
                $users = $data['Datos'] ?? [];
                mtrace('Received ' . count($users) . ' users from API.');
                
                // Store raw response for the notification task
                set_config('last_api_response', $response, 'auth_contactws');
                
                return $users;
            } else {
                mtrace('API response indicates failure.');
            }
        } else {
            mtrace('Failed to get data from API. HTTP Code: ' . $httpcode);
        }
        
        return false;
    }
    
    /**
     * FUNCIÓN MODIFICADA: Process users from the API response con nueva lógica.
     * NO reactiva usuarios suspendidos automáticamente
     * Solo procesa usuarios que tienen auth='contactws'
     *
     * @param array $apiusers List of users from API
     */
    private function process_users_with_new_logic($apiusers) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        
        mtrace('Processing ' . count($apiusers) . ' users with new logic...');
        
        // Define which estados are considered "active"
        $activestatuses = [1, 3, 5];
        
        // Create maps for active document numbers and missing users
        $activedocuments = [];
        $missingusers = [];
        
        // Statistics by status
        $statusStats = [
            1 => ['count' => 0, 'name' => 'Activo', 'missing' => 0],
            3 => ['count' => 0, 'name' => 'En Proceso', 'missing' => 0],
            5 => ['count' => 0, 'name' => 'Contratado', 'missing' => 0],
            'other' => ['count' => 0, 'missing' => 0]
        ];
        
        $totalApiUsers = count($apiusers);
        $totalMissingUsers = 0;
        $totalProcessedUsers = 0;
        $startTime = microtime(true);
        $maxProcessingTime = 240; // 4 minutos
        
        // Procesar usuarios de la API
        foreach ($apiusers as $apiuser) {
            // Si llevamos más del tiempo máximo, parar el procesamiento
            if ((microtime(true) - $startTime) > $maxProcessingTime) {
                mtrace('Reached maximum processing time. Stopping further processing.');
                break;
            }
            
            $totalProcessedUsers++;
            
            $docnumber = $apiuser['NumeroDocumento'];
            $status = $apiuser['Estado'];
            $statusName = $apiuser['NEstado'];
            
            // Update status statistics
            if (isset($statusStats[$status])) {
                $statusStats[$status]['count']++;
                $statusStats[$status]['name'] = $statusName;
            } else {
                $statusStats['other']['count']++;
            }
            
            // Check if user should be active based on estado
            $shouldbeactive = in_array($status, $activestatuses);
            
            if ($shouldbeactive) {
                $activedocuments[$docnumber] = $status;
            }
            
            // Check if user exists in Moodle with auth='contactws'
            if ($shouldbeactive) {
                $sql = "SELECT id FROM {user} 
                        WHERE idnumber = :idnumber 
                        AND deleted = 0 
                        AND auth = 'contactws'";
                
                if (!$DB->record_exists_sql($sql, ['idnumber' => $docnumber])) {
                    // Solo contar como faltante si el usuario no existe CON auth='contactws'
                    // Podría existir con otro método de auth pero eso no es relevante aquí
                    $missingusers[] = [
                        'docnumber' => $docnumber,
                        'status' => $status,
                        'statusname' => $statusName
                    ];
                    $totalMissingUsers++;
                    
                    // Update missing count by status
                    if (isset($statusStats[$status])) {
                        $statusStats[$status]['missing']++;
                    } else {
                        $statusStats['other']['missing']++;
                    }
                }
            }
        }
        
        // Calcular usuarios no procesados debido a límite de tiempo
        $totalUnprocessedUsers = $totalApiUsers - $totalProcessedUsers;
        
        mtrace("Processed $totalProcessedUsers out of $totalApiUsers users.");
        
        if ($totalUnprocessedUsers > 0) {
            mtrace("$totalUnprocessedUsers users not processed due to time constraints.");
        }
        
        mtrace(count($activedocuments) . ' users should be active based on API data.');
        mtrace($totalMissingUsers . ' active users from API are missing in Moodle with auth=contactws.');
        
        // Store missing users for notification
        set_config('missing_users', json_encode($missingusers), 'auth_contactws');
        
        // Store status statistics
        set_config('status_statistics', json_encode($statusStats), 'auth_contactws');
        set_config('total_api_users', $totalApiUsers, 'auth_contactws');
        set_config('total_missing_users', $totalMissingUsers, 'auth_contactws');
        set_config('total_unprocessed_users', $totalUnprocessedUsers, 'auth_contactws');
        set_config('total_processed_users', $totalProcessedUsers, 'auth_contactws');
        
        // IMPORTANTE: Solo procesar usuarios con auth='contactws'
        $sql = "SELECT id, idnumber, username, suspended, timecreated, lastaccess, lastlogin, auth
                FROM {user} 
                WHERE auth = 'contactws' AND deleted = 0";
        $moodleusers = $DB->get_records_sql($sql);
        
        mtrace('Found ' . count($moodleusers) . ' ContactWS users in Moodle.');
        
        // Solo procesar usuarios de Moodle si aún estamos dentro del tiempo permitido
        if ((microtime(true) - $startTime) <= $maxProcessingTime) {
            // Find duplicate document numbers
            $usersbyidnumber = [];
            $duplicateIdnumbers = [];
            
            // Mapear usuarios por idnumber
            foreach ($moodleusers as $user) {
                if (!empty($user->idnumber)) {
                    if (!isset($usersbyidnumber[$user->idnumber])) {
                        $usersbyidnumber[$user->idnumber] = [];
                    }
                    $usersbyidnumber[$user->idnumber][] = $user;
                    
                    // Identificar duplicados
                    if (count($usersbyidnumber[$user->idnumber]) == 2) {
                        $duplicateIdnumbers[] = $user->idnumber;
                    }
                }
            }
            
            mtrace("Found " . count($duplicateIdnumbers) . " duplicate idnumbers.");
            
            // Usuarios para suspender (NO reactivar automáticamente)
            $userstosuspend = [];
            $users_pending_activation = []; // Usuarios pendientes de activación
            
            // Procesar primero todos los duplicados
            foreach ($duplicateIdnumbers as $docnumber) {
                $users = $usersbyidnumber[$docnumber];
                $shouldbeactive = isset($activedocuments[$docnumber]);
                
                if ($shouldbeactive) {
                    // Encontrar el usuario con actividad más reciente
                    usort($users, function($a, $b) {
                        // Primero comparar por lastaccess (no nulo)
                        if ($a->lastaccess > 0 && $b->lastaccess > 0) {
                            return $b->lastaccess - $a->lastaccess; // Orden descendente
                        }
                        if ($a->lastaccess > 0 && $b->lastaccess == 0) return -1; // a primero
                        if ($a->lastaccess == 0 && $b->lastaccess > 0) return 1;  // b primero
                        
                        // Luego por lastlogin
                        if ($a->lastlogin > 0 && $b->lastlogin > 0) {
                            return $b->lastlogin - $a->lastlogin;
                        }
                        if ($a->lastlogin > 0 && $b->lastlogin == 0) return -1;
                        if ($a->lastlogin == 0 && $b->lastlogin > 0) return 1;
                        
                        // Finalmente por timecreated
                        return $b->timecreated - $a->timecreated;
                    });
                    
                    // El primer usuario es el más activo
                    $mostActiveUser = $users[0];
                    
                    mtrace("Most active user for document $docnumber is: $mostActiveUser->username (ID: $mostActiveUser->id)");
                    
                    // Procesar cada usuario duplicado
                    foreach ($users as $user) {
                        if ($user->id === $mostActiveUser->id) {
                            // NO reactivar automáticamente usuarios suspendidos
                            if ($user->suspended) {
                                // Marcar como pendiente de activación pero NO activar
                                $users_pending_activation[$user->id] = $user;
                                mtrace("User {$user->username} is suspended but active in API - marked for manual activation only");
                                
                                // Guardar estado para activación posterior durante login
                                set_user_preference('auth_contactws_pending_activation', 1, $user->id);
                            }
                        } else {
                            // Suspender a todos los demás duplicados
                            if (!$user->suspended) {
                                $userstosuspend[$user->id] = $user;
                            }
                        }
                    }
                } else {
                    // Si el documento no debe estar activo, suspender todos
                    foreach ($users as $user) {
                        if (!$user->suspended) {
                            $userstosuspend[$user->id] = $user;
                        }
                    }
                }
            }
            
            // Procesar usuarios no duplicados
            foreach ($moodleusers as $user) {
                // Saltar usuarios ya marcados
                if (isset($userstosuspend[$user->id]) || isset($users_pending_activation[$user->id])) {
                    continue;
                }
                
                // Saltar usuarios duplicados (ya procesados)
                if (!empty($user->idnumber) && in_array($user->idnumber, $duplicateIdnumbers)) {
                    continue;
                }
                
                // Verificar si debe estar activo según SARH
                $shouldbeactive = !empty($user->idnumber) && isset($activedocuments[$user->idnumber]);
                
                if (!$shouldbeactive && !$user->suspended) {
                    // Usuario no está en SARH o no está activo - debe ser suspendido
                    $userstosuspend[$user->id] = $user;
                    mtrace("User {$user->username} not active in SARH - marking for suspension");
                } else if ($shouldbeactive && $user->suspended) {
                    // Usuario está activo en SARH pero suspendido en Moodle
                    // NO reactivar automáticamente - solo marcar para activación manual
                    $users_pending_activation[$user->id] = $user;
                    mtrace("User {$user->username} is suspended but active in API - marked for manual activation only");
                    
                    // Guardar estado para activación posterior durante login
                    set_user_preference('auth_contactws_pending_activation', 1, $user->id);
                }
            }
            
            // Aplicar cambios en lotes para mejorar rendimiento
            // Suspender usuarios
            mtrace(count($userstosuspend) . ' users to suspend.');
            $this->update_users_batch($userstosuspend, 1);
            
            // NO reactivamos usuarios automáticamente
            mtrace(count($users_pending_activation) . ' users marked for manual activation (will activate on next login).');
            
            // Guardar estadística de usuarios pendientes de activación
            set_config('pending_activation_count', count($users_pending_activation), 'auth_contactws');
            
        } else {
            mtrace('Skipped Moodle user processing due to time constraints.');
        }
        
        // Update statistics for notification
        $activeusers = $DB->count_records('user', ['auth' => 'contactws', 'suspended' => 0, 'deleted' => 0]);
        $suspendedusers = $DB->count_records('user', ['auth' => 'contactws', 'suspended' => 1, 'deleted' => 0]);
        
        set_config('active_users_count', $activeusers, 'auth_contactws');
        set_config('suspended_users_count', $suspendedusers, 'auth_contactws');
        set_config('last_sync_time', time(), 'auth_contactws');
    }
    
    /**
     * Update users' suspended status in batches for better performance.
     *
     * @param array $users Users to update
     * @param int $suspended Suspended status (0 or 1)
     */
    private function update_users_batch($users, $suspended) {
        global $DB;
        require_once($GLOBALS['CFG']->dirroot . '/user/lib.php');
        
        if (empty($users)) {
            return;
        }
        
        // Usar transacción para mejor rendimiento
        $transaction = $DB->start_delegated_transaction();
        
        try {
            // Procesar en lotes pequeños
            $batchSize = 100;
            $processed = 0;
            $userBatch = [];
            
            foreach ($users as $user) {
                $userBatch[] = $user;
                $processed++;
                
                // Cuando el lote alcanza el tamaño o es el último usuario
                if (count($userBatch) >= $batchSize || $processed == count($users)) {
                    foreach ($userBatch as $batchUser) {
                        mtrace(($suspended ? "Suspending" : "Unsuspending") . " user: $batchUser->username (ID: $batchUser->id)");
                        $updateuser = new \stdClass();
                        $updateuser->id = $batchUser->id;
                        $updateuser->suspended = $suspended;
                        user_update_user($updateuser, false);
                    }
                    
                    // Limpiar el lote
                    $userBatch = [];
                }
            }
            
            // Confirmar transacción
            $DB->commit_delegated_transaction($transaction);
        } catch (\Exception $e) {
            // Rollback en caso de error
            $DB->rollback_delegated_transaction($transaction);
            mtrace('Error updating users: ' . $e->getMessage());
        }
    }
}