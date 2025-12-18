<?php
/**
 * Configurazione principale del modulo di sincronizzazione
 * MySanitario Sync Module
 */

// Previeni accesso diretto
if (!defined('SYNC_MODULE')) {
    die('Accesso diretto non consentito');
}

// Configurazione base
define('SYNC_VERSION', '1.0.0');
define('SYNC_DEBUG', true);

// Percorsi
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', BASE_PATH . '/config');
define('LOGS_PATH', BASE_PATH . '/logs');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('IMAGES_PATH', STORAGE_PATH . '/images');
define('VIDEOS_PATH', STORAGE_PATH . '/videos');

// URL Server Online
define('REMOTE_API_BASE', 'https://myeliminacode.acwild.eu/api/');
define('REMOTE_FILES_BASE', 'https://myeliminacode.acwild.eu/appOffline/');

// Carica configurazione locale se esiste
$localConfigFile = CONFIG_PATH . '/local.php';
if (file_exists($localConfigFile)) {
    require_once $localConfigFile;
}

// Funzione per ottenere configurazione database
function getDbConfig(): array {
    // Prima controlla se esiste configurazione locale
    if (defined('DB_HOST')) {
        return [
            'host' => DB_HOST,
            'user' => DB_USER,
            'password' => DB_PASSWORD,
            'database' => DB_NAME,
            'charset' => defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4'
        ];
    }
    
    // Altrimenti usa valori default
    return [
        'host' => 'localhost',
        'user' => 'mysanitario',
        'password' => 'mysanitario2025',
        'database' => 'mysanitario',
        'charset' => 'utf8mb4'
    ];
}

// Funzione per ottenere ID cliente
function getClientId(): string {
    if (defined('CLIENT_ID')) {
        return CLIENT_ID;
    }
    return '';
}
