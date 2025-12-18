<?php
/**
 * Interfaccia Web per configurazione e sincronizzazione
 * MySanitario Sync Module - Pannello di Controllo
 */

require_once __DIR__ . '/bootstrap.php';

use MySanitario\Sync\Database;
use MySanitario\Sync\SyncService;
use MySanitario\Sync\Logger;

// Gestione azioni POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'test_db':
            $db = Database::getInstance();
            $result = $db->testConnection();
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            if ($result['success'] && $result['server_info']) {
                $message .= ' (MySQL ' . $result['server_info'] . ')';
            }
            break;
            
        case 'test_api':
            $clientId = $_POST['client_id'] ?? getClientId();
            if (empty($clientId)) {
                $message = 'ID Cliente non specificato';
                $messageType = 'error';
            } else {
                $http = new \MySanitario\Sync\HttpClient();
                $url = (defined('REMOTE_API_BASE') ? REMOTE_API_BASE : 'https://myeliminacode.acwild.eu/api/') . 'get_json_config.php';
                $data = $http->getJson($url, ['idmonitor' => $clientId]);
                
                if ($data !== null) {
                    $message = 'Connessione API riuscita! Trovati: ' . 
                        count($data['san_turni'] ?? []) . ' turni, ' .
                        count($data['san_postazioni'] ?? []) . ' postazioni';
                    $messageType = 'success';
                } else {
                    $message = 'Errore API: ' . $http->getLastError();
                    $messageType = 'error';
                }
            }
            break;
            
        case 'sync':
            $fullSync = isset($_POST['full_sync']);
            $syncMedia = isset($_POST['sync_media']);
            
            $sync = new SyncService();
            $result = $sync->sync($fullSync, $syncMedia);
            
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'error';
            
            if ($result['success']) {
                $stats = $result['stats'];
                $message .= " | Turni: {$stats['turni']}, Postazioni: {$stats['postazioni']}, " .
                           "Config Monitor: {$stats['config_monitor']}, File scaricati: {$stats['files_downloaded']}";
            }
            break;
            
        case 'save_config':
            $configContent = "<?php\n";
            $configContent .= "/**\n * Configurazione locale generata automaticamente\n * Data: " . date('Y-m-d H:i:s') . "\n */\n\n";
            $configContent .= "// === CONFIGURAZIONE DATABASE LOCALE ===\n";
            $configContent .= "define('DB_HOST', '" . addslashes($_POST['db_host'] ?? 'localhost') . "');\n";
            $configContent .= "define('DB_USER', '" . addslashes($_POST['db_user'] ?? '') . "');\n";
            $configContent .= "define('DB_PASSWORD', '" . addslashes($_POST['db_password'] ?? '') . "');\n";
            $configContent .= "define('DB_NAME', '" . addslashes($_POST['db_name'] ?? '') . "');\n";
            $configContent .= "define('DB_CHARSET', 'utf8mb4');\n\n";
            $configContent .= "// === ID CLIENTE/MONITOR ===\n";
            $configContent .= "define('CLIENT_ID', '" . addslashes($_POST['client_id'] ?? '') . "');\n\n";
            $configContent .= "// === OPZIONI ===\n";
            $configContent .= "define('SYNC_MEDIA_FILES', " . (isset($_POST['sync_media_files']) ? 'true' : 'false') . ");\n";
            $configContent .= "define('ENABLE_LOGGING', true);\n";
            $configContent .= "define('LOG_LEVEL', 'INFO');\n";
            
            $configFile = __DIR__ . '/config/local.php';
            if (file_put_contents($configFile, $configContent)) {
                $message = 'Configurazione salvata con successo!';
                $messageType = 'success';
                // Reset database instance per usare nuova config
                Database::resetInstance();
            } else {
                $message = 'Errore nel salvataggio della configurazione';
                $messageType = 'error';
            }
            break;
            
        case 'view_logs':
            // Gestito separatamente
            break;
    }
}

// Carica configurazione attuale
$currentConfig = [
    'db_host' => defined('DB_HOST') ? DB_HOST : 'localhost',
    'db_user' => defined('DB_USER') ? DB_USER : '',
    'db_password' => defined('DB_PASSWORD') ? DB_PASSWORD : '',
    'db_name' => defined('DB_NAME') ? DB_NAME : '',
    'client_id' => defined('CLIENT_ID') ? CLIENT_ID : '',
    'sync_media_files' => defined('SYNC_MEDIA_FILES') ? SYNC_MEDIA_FILES : true,
];

// Stato connessioni
$dbStatus = null;
$apiStatus = null;

// Verifica rapida stato DB
try {
    $db = Database::getInstance();
    $dbTest = $db->testConnection();
    $dbStatus = $dbTest['success'];
} catch (Exception $e) {
    $dbStatus = false;
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySanitario Sync - Pannello di Controllo</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .nav-links {
            margin-top: 15px;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 13px;
            margin: 0 5px;
            transition: background 0.2s;
        }
        
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-header .icon {
            font-size: 20px;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-indicator.online {
            background: #d4edda;
            color: #155724;
        }
        
        .status-indicator.offline {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-indicator .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        
        .status-indicator.online .dot {
            background: #28a745;
        }
        
        .status-indicator.offline .dot {
            background: #dc3545;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .sync-options {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .log-viewer .log-line {
            margin-bottom: 2px;
        }
        
        .log-viewer .log-line.error {
            color: #f48771;
        }
        
        .log-viewer .log-line.warning {
            color: #cca700;
        }
        
        .log-viewer .log-line.info {
            color: #3dc9b0;
        }
        
        .info-box {
            background: #e7f3ff;
            border: 1px solid #b6d4fe;
            color: #084298;
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 600px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔄 MySanitario Sync</h1>
            <p>Modulo di Sincronizzazione - Versione <?= defined('SYNC_VERSION') ? SYNC_VERSION : '1.0.0' ?></p>
            <div class="nav-links">
                <a href="email.php">📧 Configurazione Email</a>
            </div>
        </header>
        
        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>">
            <?= $messageType === 'success' ? '✓' : '✗' ?>
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>
        
        <!-- Stato Sistema -->
        <div class="card">
            <div class="card-header">
                <span class="icon">📊</span>
                Stato Sistema
            </div>
            <div class="card-body">
                <div style="display: flex; gap: 20px; flex-wrap: wrap;">
                    <div>
                        <strong>Database:</strong>
                        <span class="status-indicator <?= $dbStatus ? 'online' : 'offline' ?>">
                            <span class="dot"></span>
                            <?= $dbStatus ? 'Connesso' : 'Non connesso' ?>
                        </span>
                    </div>
                    <div>
                        <strong>Client ID:</strong>
                        <code><?= htmlspecialchars($currentConfig['client_id'] ?: 'Non configurato') ?></code>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configurazione -->
        <div class="card">
            <div class="card-header">
                <span class="icon">⚙️</span>
                Configurazione
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="action" value="save_config">
                    
                    <h4 style="margin-bottom: 15px; color: #555;">Database MySQL Locale</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Host</label>
                            <input type="text" name="db_host" value="<?= htmlspecialchars($currentConfig['db_host']) ?>" placeholder="localhost">
                        </div>
                        <div class="form-group">
                            <label>Nome Database</label>
                            <input type="text" name="db_name" value="<?= htmlspecialchars($currentConfig['db_name']) ?>" placeholder="mysanitario">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Utente</label>
                            <input type="text" name="db_user" value="<?= htmlspecialchars($currentConfig['db_user']) ?>" placeholder="root">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="db_password" value="<?= htmlspecialchars($currentConfig['db_password']) ?>">
                        </div>
                    </div>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                    
                    <h4 style="margin-bottom: 15px; color: #555;">Server Online</h4>
                    
                    <div class="form-group">
                        <label>ID Cliente/Monitor</label>
                        <input type="text" name="client_id" value="<?= htmlspecialchars($currentConfig['client_id']) ?>" placeholder="Es: Monitor-xzK0K">
                        <small style="color: #888; font-size: 12px;">L'ID univoco assegnato dal server online</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" name="sync_media_files" id="sync_media_files" <?= $currentConfig['sync_media_files'] ? 'checked' : '' ?>>
                            <label for="sync_media_files" style="margin: 0;">Sincronizza anche file multimediali (immagini/video)</label>
                        </div>
                    </div>
                    
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">💾 Salva Configurazione</button>
                    </div>
                </form>
                
                <hr style="margin: 20px 0; border: none; border-top: 1px solid #eee;">
                
                <div class="btn-group">
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="test_db">
                        <button type="submit" class="btn btn-secondary">🔌 Test Database</button>
                    </form>
                    
                    <form method="post" style="display: inline;">
                        <input type="hidden" name="action" value="test_api">
                        <input type="hidden" name="client_id" value="<?= htmlspecialchars($currentConfig['client_id']) ?>">
                        <button type="submit" class="btn btn-secondary">🌐 Test API Online</button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sincronizzazione -->
        <div class="card">
            <div class="card-header">
                <span class="icon">🔄</span>
                Sincronizzazione
            </div>
            <div class="card-body">
                <div class="info-box">
                    <strong>ℹ️ Info:</strong> La sincronizzazione scarica le configurazioni dal server online e le salva nel database locale.
                    La modalità "Completa" azzera anche turni, postazioni e contatori.
                </div>
                
                <form method="post">
                    <input type="hidden" name="action" value="sync">
                    
                    <div class="sync-options">
                        <div class="checkbox-group" style="margin-bottom: 10px;">
                            <input type="checkbox" name="full_sync" id="full_sync">
                            <label for="full_sync" style="margin: 0;"><strong>Sincronizzazione Completa</strong> (include turni, postazioni, operazioni - AZZERA I DATI)</label>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="sync_media" id="sync_media" checked>
                            <label for="sync_media" style="margin: 0;">Scarica file multimediali</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success">▶️ Avvia Sincronizzazione</button>
                </form>
            </div>
        </div>
        
        <!-- Log -->
        <div class="card">
            <div class="card-header">
                <span class="icon">📋</span>
                Log Recenti
            </div>
            <div class="card-body">
                <?php
                $logger = Logger::getInstance();
                $logLines = $logger->getLastLines(50);
                ?>
                
                <?php if (empty($logLines)): ?>
                    <p style="color: #888; text-align: center;">Nessun log disponibile</p>
                <?php else: ?>
                    <div class="log-viewer">
                        <?php foreach (array_reverse($logLines) as $line): ?>
                            <?php
                            $class = 'info';
                            if (strpos($line, '[ERROR]') !== false) $class = 'error';
                            elseif (strpos($line, '[WARNING]') !== false) $class = 'warning';
                            ?>
                            <div class="log-line <?= $class ?>"><?= htmlspecialchars($line) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <footer style="text-align: center; color: #888; font-size: 12px; margin-top: 30px;">
            MySanitario Sync Module v<?= defined('SYNC_VERSION') ? SYNC_VERSION : '1.0.0' ?> | 
            <?= date('Y') ?> | 
            <a href="https://myeliminacode.acwild.eu" target="_blank" style="color: #667eea;">myeliminacode.acwild.eu</a>
        </footer>
    </div>
</body>
</html>
