<?php
// download_images.php
// Scarica da un servizio remoto l'elenco dei file e salva i file in immaginicliente/
// Compatibile: Windows Task Scheduler e Linux cron (PHP CLI).
// Log: tocron/download_images.log (ruota automaticamente oltre 1 MB)

// ---------------- CONFIGURAZIONE ----------------
$listEndpointBase    = 'https://pannello.myeliminacode.it/appOffline/contactsvr/contactsvr/getfilesgruppo.php';
$listEndpointCliente = 'https://pannello.myeliminacode.it/appOffline/contactsvr/contactsvr/getfilescliente.php';
$remoteFilesBase     = 'https://pannello.myeliminacode.it/appOffline';
$timeoutSeconds      = 30;
$logFile             = __DIR__ . DIRECTORY_SEPARATOR . 'download_images.log';
$logMaxBytes         = 1048576; // 1 MB: ruota il log oltre questa dimensione

// Verifica SSL: abilitata se il sistema ha un CA bundle, disabilitata altrimenti (es. Windows senza bundle configurato)
function detectVerifySsl(): bool
{
    // Rispetta la configurazione esplicita di PHP (curl.cainfo o openssl.cafile)
    foreach (['curl.cainfo', 'openssl.cafile', 'openssl.capath'] as $ini) {
        $v = ini_get($ini);
        if (!empty($v) && file_exists($v)) {
            return true;
        }
    }
    // Posizioni standard dei CA bundle su Linux/macOS
    $bundles = [
        '/etc/ssl/certs/ca-certificates.crt',  // Debian/Ubuntu
        '/etc/pki/tls/certs/ca-bundle.crt',    // RHEL/CentOS
        '/etc/ssl/ca-bundle.pem',              // openSUSE
        '/usr/local/etc/openssl/cert.pem',     // macOS (Homebrew)
    ];
    foreach ($bundles as $b) {
        if (file_exists($b)) {
            return true;
        }
    }
    return false; // Windows senza bundle configurato o sistema non riconosciuto
}
$verifySsl = detectVerifySsl();
// ------------------------------------------------

/** Scrive una riga su stdout e sul file di log. */
function logLine(string $msg): void
{
    global $logFile, $logMaxBytes;
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    // rotazione log semplice
    if (file_exists($logFile) && filesize($logFile) > $logMaxBytes) {
        @rename($logFile, $logFile . '.bak');
    }
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    echo $line;
}

/**
 * Esegue una GET con cURL e ritorna il body o false in caso di errore.
 */
function curlGet(string $url, int $timeout = 30, bool $verifySsl = true)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'DownloaderScript/1.0',
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);
    $body    = curl_exec($ch);
    $errNo   = curl_errno($ch);
    $err     = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($errNo !== 0) {
        logLine("curlGet ERROR [$errNo] $err  url=$url");
        return false;
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        logLine("curlGet HTTP $httpCode  url=$url");
        return false;
    }
    return $body;
}

/**
 * Scarica un file remoto e lo salva in $localPath.
 * Usa RETURNTRANSFER per evitare il bug dei 0 byte con CURLOPT_FILE su Windows.
 * Restituisce true se ok.
 */
function downloadToFile(string $fileUrl, string $localPath, int $timeout = 60, bool $verifySsl = true): bool
{
    $ch = curl_init($fileUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_USERAGENT      => 'DownloaderScript/1.0',
        CURLOPT_ENCODING       => '',   // accetta gzip/deflate e decomprime automaticamente
        CURLOPT_SSL_VERIFYPEER => $verifySsl,
        CURLOPT_SSL_VERIFYHOST => $verifySsl ? 2 : 0,
    ]);

    $content  = curl_exec($ch);
    $errNo    = curl_errno($ch);
    $err      = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($content === false || $errNo !== 0 || $httpCode < 200 || $httpCode >= 300) {
        logLine("FAILED url=$fileUrl http=$httpCode err=[$errNo] $err");
        return false;
    }

    $bytes = strlen($content);
    if ($bytes === 0) {
        logLine("FAILED url=$fileUrl - il server ha risposto con body vuoto (0 byte)");
        return false;
    }

    $written = file_put_contents($localPath, $content);
    if ($written === false || $written !== $bytes) {
        @unlink($localPath);
        logLine("FAILED scrittura $localPath (scritti=" . ($written === false ? 'false' : $written) . " attesi=$bytes)");
        return false;
    }

    return true;
}

/**
 * Rimuove un file locale in modo robusto (anche su Windows con attributi restrittivi).
 */
function deleteLocalFile(string $fullPath): bool
{
    clearstatcache(true, $fullPath);

    if (!file_exists($fullPath)) {
        return true;
    }

    // Primo tentativo diretto.
    if (@unlink($fullPath)) {
        return true;
    }

    // Su Windows alcuni file arrivano readonly: proviamo a renderli scrivibili.
    @chmod($fullPath, 0666);
    clearstatcache(true, $fullPath);
    if (@unlink($fullPath)) {
        return true;
    }

    // Fallback: rename in file temporaneo e poi unlink.
    $tmp = $fullPath . '.deleting_' . uniqid('', true);
    if (@rename($fullPath, $tmp)) {
        clearstatcache(true, $tmp);
        if (@unlink($tmp)) {
            return true;
        }
    }

    return false;
}

/**
 * Legge idcliente da totem_ui_config.json (directory padre dello script).
 */
function getIdCliente(): string
{
    $configPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'totem_ui_config.json';
    $configPath = realpath($configPath) ?: $configPath;
    if (!file_exists($configPath)) {
        return '';
    }
    $contenuto = file_get_contents($configPath);
    if ($contenuto === false) {
        return '';
    }
    $dati = json_decode($contenuto, true);
    if ($dati === null && json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }
    return $dati['idcliente'] ?? '';
}

/**
 * Punto di ingresso principale.
 * Supporta CLI (Windows Task Scheduler e Linux cron) e richiesta HTTP.
 * Esce con codice 0 se non ci sono fallimenti, 1 altrimenti.
 */
function main(): void
{
    global $listEndpointBase, $listEndpointCliente, $remoteFilesBase, $timeoutSeconds, $verifySsl;

    logLine('=== Avvio download_images ===');

    // --- Lettura idcliente ---
    if (php_sapi_name() === 'cli') {
        $opts      = getopt('', ['idcliente:']);
        $idcliente = trim($opts['idcliente'] ?? '');
    } else {
        $idcliente = trim($_GET['idcliente'] ?? '');
    }

    if ($idcliente === '') {
        $idcliente = getIdCliente();
    }

    if ($idcliente === '') {
        logLine('ERRORE: idcliente non trovato (né da argomento né da totem_ui_config.json).');
        exit(1);
    }

    logLine("idcliente: $idcliente");

    // --- Percorso locale (sempre relativo alla posizione dello script) ---
    $localDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'immaginicliente';
    if (!is_dir($localDir)) {
        if (!mkdir($localDir, 0755, true)) {
            logLine("ERRORE: impossibile creare la cartella locale $localDir");
            exit(1);
        }
    }

    $remoteBasenames = [];
    $success         = 0;
    $failed          = 0;
    $deleted         = 0;
    $syncErrors      = 0;
    $listCallsTotal  = 0;
    $listCallsOk     = 0;

    $endpoints = [
        ['url' => $listEndpointBase,    'label' => 'gruppo',  'path' => 'gruppi'],
        ['url' => $listEndpointCliente, 'label' => 'cliente', 'path' => 'clienti'],
    ];
    $baseRemote = rtrim($remoteFilesBase, '/');

    foreach (['i', 'v'] as $tipo) {
        foreach ($endpoints as $ep) {
            $listCallsTotal++;
            $listUrl = $ep['url'] . '?idcliente=' . rawurlencode($idcliente) . '&tipo=' . rawurlencode($tipo);
            logLine("Endpoint [{$ep['label']}] tipo=$tipo : $listUrl");

            $resp = curlGet($listUrl, $timeoutSeconds, $verifySsl);
            if ($resp === false) {
                logLine("Errore: impossibile raggiungere l'endpoint. Controlla il log.");
                $syncErrors++;
                continue;
            }

            $resp = trim($resp);
            if ($resp === 'NOFILE' || $resp === '') {
                logLine('Nessun file da scaricare (risposta: NOFILE o vuota).');
                $listCallsOk++;
                continue;
            }

            // Formato risposta: "file1.jpg|file2.png§cartella"
            $parts     = explode('§', $resp, 2);
            $filesPart = $parts[0] ?? '';
            $cartella  = trim($parts[1] ?? '');

            if ($filesPart === '' || $cartella === '') {
                logLine("Formato risposta non valido (filesPart='$filesPart' cartella='$cartella').");
                $syncErrors++;
                continue;
            }

            $listCallsOk++;

            $filenames = array_filter(
                array_map('trim', explode('|', $filesPart)),
                fn($v) => $v !== ''
            );

            if (count($filenames) === 0) {
                logLine('Nessun file valido nell\'elenco.');
                continue;
            }

            $subdir = ($tipo === 'v') ? 'video/' : '';

            foreach ($filenames as $fname) {
                $fname     = trim($fname);
                $localFile = $localDir . DIRECTORY_SEPARATOR . basename($fname);
                $remoteUrl = $baseRemote . '/' . $ep['path'] . '/' . rawurlencode($cartella) . '/' . $subdir . rawurlencode($fname);

                logLine("Scaricando: $remoteUrl");
                $ok = downloadToFile($remoteUrl, $localFile, $timeoutSeconds * 2, $verifySsl);
                if ($ok) {
                    logLine("OK -> $localFile");
                    $success++;
                } else {
                    $failed++;
                }
                $remoteBasenames[] = basename($fname);
            }
        }
    }

    // --- Pulizia file locali non più presenti sul server ---
    // Eseguiamo cleanup solo se tutte le liste remota sono state lette correttamente.
    if ($listCallsOk === $listCallsTotal) {
        $localFiles = @scandir($localDir);
        if ($localFiles !== false) {
            foreach ($localFiles as $localName) {
                if ($localName === '.' || $localName === '..') {
                    continue;
                }
                $localPath = $localDir . DIRECTORY_SEPARATOR . $localName;
                if (!is_file($localPath)) {
                    continue;
                }
                if (!in_array($localName, $remoteBasenames, true)) {
                    logLine("Eliminando file obsoleto: $localName");
                    if (deleteLocalFile($localPath)) {
                        $deleted++;
                    } else {
                        $lastErr = error_get_last();
                        $errMsg = is_array($lastErr) && isset($lastErr['message']) ? $lastErr['message'] : 'errore non disponibile';
                        $writable = is_writable($localPath) ? 'yes' : 'no';
                        logLine("ERRORE eliminazione: $localName (writable=$writable, errore=$errMsg)");
                    }
                }
            }
        }
    } else {
        logLine("ATTENZIONE: cleanup saltato (liste ok {$listCallsOk}/{$listCallsTotal}, syncErrors={$syncErrors}).");
    }

    logLine("=== Fine: successi=$success  falliti=$failed  eliminati=$deleted  liste_ok={$listCallsOk}/{$listCallsTotal}  syncErrors=$syncErrors ===");
    exit(($failed > 0 || $syncErrors > 0) ? 1 : 0);
}

// Avvio
main();