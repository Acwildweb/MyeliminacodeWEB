<?php
/**
 * API per sincronizzazione - Può essere chiamata da altri sistemi
 * 
 * Endpoint: /api/sync.php
 * Metodo: POST
 * Parametri:
 *   - action: 'sync' | 'status' | 'test'
 *   - full_sync: 0|1 (opzionale)
 *   - sync_media: 0|1 (opzionale)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once dirname(__DIR__) . '/bootstrap.php';

use MySanitario\Sync\Database;
use MySanitario\Sync\SyncService;
use MySanitario\Sync\Logger;

/**
 * Invia risposta JSON
 */
function jsonResponse(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Ottieni azione
$action = $_REQUEST['action'] ?? $_GET['action'] ?? 'status';

switch ($action) {
    case 'sync':
        // Esegui sincronizzazione
        $fullSync = filter_var($_REQUEST['full_sync'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $syncMedia = filter_var($_REQUEST['sync_media'] ?? true, FILTER_VALIDATE_BOOLEAN);
        
        $sync = new SyncService();
        $result = $sync->sync($fullSync, $syncMedia);
        
        jsonResponse([
            'success' => $result['success'],
            'message' => $result['message'],
            'stats' => $result['stats'],
            'timestamp' => $result['timestamp']
        ], $result['success'] ? 200 : 500);
        break;
        
    case 'status':
        // Stato del sistema
        $db = Database::getInstance();
        $dbTest = $db->testConnection();
        
        // Ultima sincronizzazione (dal log)
        $logger = Logger::getInstance();
        $lastLogs = $logger->getLastLines(10);
        $lastSync = null;
        foreach (array_reverse($lastLogs) as $log) {
            if (strpos($log, 'Sincronizzazione completata') !== false) {
                preg_match('/\[([\d-]+ [\d:]+)\]/', $log, $matches);
                $lastSync = $matches[1] ?? null;
                break;
            }
        }
        
        jsonResponse([
            'success' => true,
            'status' => [
                'database' => [
                    'connected' => $dbTest['success'],
                    'message' => $dbTest['message'],
                    'server' => $dbTest['server_info'] ?? null
                ],
                'client_id' => getClientId(),
                'last_sync' => $lastSync,
                'version' => defined('SYNC_VERSION') ? SYNC_VERSION : '1.0.0'
            ]
        ]);
        break;
        
    case 'test_db':
        // Test connessione database
        $db = Database::getInstance();
        $result = $db->testConnection();
        
        jsonResponse([
            'success' => $result['success'],
            'message' => $result['message'],
            'server_info' => $result['server_info'] ?? null
        ], $result['success'] ? 200 : 500);
        break;
        
    case 'test_api':
        // Test connessione API remota
        $clientId = $_REQUEST['client_id'] ?? getClientId();
        
        if (empty($clientId)) {
            jsonResponse([
                'success' => false,
                'message' => 'Client ID non specificato'
            ], 400);
        }
        
        $http = new \MySanitario\Sync\HttpClient();
        $url = (defined('REMOTE_API_BASE') ? REMOTE_API_BASE : 'https://myeliminacode.acwild.eu/api/') . 'get_json_config.php';
        $data = $http->getJson($url, ['idmonitor' => $clientId]);
        
        if ($data !== null) {
            jsonResponse([
                'success' => true,
                'message' => 'Connessione API riuscita',
                'data_summary' => [
                    'turni' => count($data['san_turni'] ?? []),
                    'postazioni' => count($data['san_postazioni'] ?? []),
                    'operazioni' => count($data['san_operazioni'] ?? []),
                    'config_monitor' => count($data['configmonitor'] ?? []),
                    'config_totem' => count($data['configtotem'] ?? [])
                ]
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'message' => 'Errore connessione API: ' . $http->getLastError(),
                'http_code' => $http->getLastHttpCode()
            ], 500);
        }
        break;
        
    case 'logs':
        // Ultimi log
        $lines = (int)($_REQUEST['lines'] ?? 50);
        $logger = Logger::getInstance();
        $logs = $logger->getLastLines($lines);
        
        jsonResponse([
            'success' => true,
            'logs' => $logs,
            'count' => count($logs)
        ]);
        break;
        
    default:
        jsonResponse([
            'success' => false,
            'message' => 'Azione non valida',
            'available_actions' => ['sync', 'status', 'test_db', 'test_api', 'logs']
        ], 400);
}
