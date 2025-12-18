<?php
/**
 * Script per eseguire la sincronizzazione da linea di comando o cron
 * 
 * Utilizzo:
 *   php sync.php                    # Sincronizzazione solo configurazioni
 *   php sync.php --full             # Sincronizzazione completa (include turni, postazioni, ecc.)
 *   php sync.php --full --media     # Sync completa + download file multimediali
 *   php sync.php --media            # Solo download file multimediali (senza modificare DB)
 *   php sync.php --no-media         # Senza download file multimediali
 */

require_once __DIR__ . '/bootstrap.php';

use MySanitario\Sync\SyncService;
use MySanitario\Sync\Logger;

// Parse argomenti
$fullSync = in_array('--full', $argv ?? []);
$mediaOnly = in_array('--media', $argv ?? []) && !$fullSync;
$noMedia = in_array('--no-media', $argv ?? []);
$syncMedia = in_array('--media', $argv ?? []) || (!$noMedia && $fullSync);

// Verifica configurazione
if (empty(getClientId())) {
    echo "ERRORE: CLIENT_ID non configurato!\n";
    echo "Modifica il file config/local.php e imposta CLIENT_ID\n";
    exit(1);
}

echo "=== MySanitario Sync Module ===\n";
echo "Data/Ora: " . date('Y-m-d H:i:s') . "\n";
echo "Client ID: " . getClientId() . "\n";

if ($mediaOnly) {
    echo "Modalità: SOLO DOWNLOAD MEDIA\n";
} else {
    echo "Modalità: " . ($fullSync ? 'COMPLETA (AZZERA DATI)' : 'SOLO CONFIGURAZIONI') . "\n";
}
echo "Media files: " . ($syncMedia ? 'SI' : 'NO') . "\n";
echo "--------------------------------\n";

// Esegui sincronizzazione
$sync = new SyncService();

if ($mediaOnly) {
    // Solo download media - non tocca il database
    $result = $sync->syncMediaOnly();
} else {
    $result = $sync->sync($fullSync, $syncMedia);
}

// Output risultato
if ($result['success']) {
    echo "✓ " . $result['message'] . "\n\n";
    echo "Statistiche:\n";
    foreach ($result['stats'] as $key => $value) {
        if ($key !== 'errors') {
            echo "  - $key: $value\n";
        }
    }
} else {
    echo "✗ ERRORE: " . $result['message'] . "\n";
    exit(1);
}

if (!empty($result['stats']['errors'])) {
    echo "\nErrori:\n";
    foreach ($result['stats']['errors'] as $error) {
        echo "  ! $error\n";
    }
}

echo "\n=== Fine sincronizzazione ===\n";
