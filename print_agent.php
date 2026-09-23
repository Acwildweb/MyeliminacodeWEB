<?php
/**
 * print_agent.php - Agente di stampa locale per il totem (Modalita B)
 * =========================================================================
 * Questo file deve essere installato SULLA MACCHINA TOTEM (non sul server).
 * Il browser del totem chiama http://localhost/.../print_agent.php direttamente,
 * quindi la stampa avviene sulla stampante fisica collegata al totem stesso.
 *
 * INSTALLAZIONE SUL TOTEM:
 *  1. Installa XAMPP sul totem (Apache + PHP; MySQL non necessario).
 *  2. Copia nella stessa cartella:
 *       - print_agent.php        (questo file)
 *       - printer_profiles.php   (profili ESC/POS per modello)
 *       - print_raw.ps1          (script PowerShell di stampa RAW)
 *       - printer_config_local.json  (printer_name, printer_model, testi)
 *  3. Avvia Apache sul totem. Rimane in ascolto su localhost, non esposto in rete.
 *  4. In config_stampante.php sul server seleziona Modalita B e imposta l'URL
 *     corrispondente (es. http://localhost/MySanitarioConTotem/print_agent.php).
 *
 * SICUREZZA: accetta richieste solo da localhost (127.0.0.1 / ::1).
 *            Le richieste cross-origin dal browser locale sono consentite
 *            tramite CORS header (Access-Control-Allow-Origin).
 */

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
header('Access-Control-Allow-Origin: ' . ($origin !== '' ? $origin : '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Private-Network: true');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIP, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Accesso consentito solo da localhost.']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/printer_profiles.php';

$turno  = trim($_POST['turno']  ?? '');
$numero = trim($_POST['numero'] ?? '');
$data   = trim($_POST['data']   ?? date('Ymd'));

if ($turno === '' || $numero === '') {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti (turno, numero)']);
    exit;
}

$configFile = __DIR__ . '/printer_config_local.json';
if (!file_exists($configFile)) {
    $configFile = __DIR__ . '/printer_config.json';
}
if (!file_exists($configFile)) {
    echo json_encode(['success' => false, 'error' => 'Configurazione stampante locale non trovata (printer_config_local.json).']);
    exit;
}

$config      = json_decode(file_get_contents($configFile), true) ?: [];
$printerName = trim($config['printer_name'] ?? '');
if ($printerName === '') {
    echo json_encode(['success' => false, 'error' => 'printer_name non impostato in printer_config_local.json.']);
    exit;
}

$intestazione1 = $config['intestazione1'] ?? 'BIGLIETTO PRENOTAZIONE';
$intestazione2 = $config['intestazione2'] ?? '';
$piede         = $config['piede']         ?? '';
$logoPath      = $config['logo_path']     ?? '';
$logoFile      = $logoPath !== '' ? __DIR__ . '/' . $logoPath : '';

$ESC = "\x1B";
$dataFmt = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
$oraFmt  = date('H:i');

$printerModel = normalizePrinterModel($config['printer_model'] ?? 'np2511d2');
$profile      = getPrinterProfile($printerModel);
$cmdCodePage  = $profile['cmdCodePage'];
$cmdCut       = $profile['cmdCut'];
$feedLines    = $profile['feedLines'];
$imgMode      = $profile['imgMode'];
$maxImgWidth  = $profile['maxImgWidth'];

$out  = $ESC . '@';
$out .= $cmdCodePage;
$out .= $ESC . 'a' . chr(1);

if ($logoFile !== '' && file_exists($logoFile)) {
    $escImg = buildEscPosRaster($logoFile, $maxImgWidth, $imgMode);
    if ($escImg !== '') {
        $out .= $escImg;
        if ($imgMode !== 'esc_star') {
            $out .= "\n";
        }
    }
}

$out .= $ESC . '!' . chr(0x30);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', $intestazione1) . "\n";
$out .= $ESC . '!' . chr(0x00);

if ($intestazione2 !== '') {
    $out .= $ESC . '!' . chr(0x08);
    $out .= iconv('UTF-8', 'CP1252//TRANSLIT', $intestazione2) . "\n";
    $out .= $ESC . '!' . chr(0x00);
}

$out .= "================================\n\n";

$out .= $ESC . '!' . chr(0x20);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', 'SPORTELLO') . "\n";
$out .= $ESC . '!' . chr(0x00);

$out .= $ESC . '!' . chr(0x38);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', $turno) . "\n";
$out .= $ESC . '!' . chr(0x00);

$out .= "\n";
$out .= $ESC . '!' . chr(0x20);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', 'NUMERO') . "\n";
$out .= $ESC . '!' . chr(0x00);

$out .= $ESC . '!' . chr(0x38);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', $numero) . "\n";
$out .= $ESC . '!' . chr(0x00);

$out .= "\n================================\n\n";

$out .= $ESC . '!' . chr(0x00);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', 'Data: ' . $dataFmt . '  Ora: ' . $oraFmt) . "\n";

if ($piede !== '') {
    $out .= "\n";
    $out .= iconv('UTF-8', 'CP1252//TRANSLIT', $piede) . "\n";
}

$out .= $ESC . 'd' . chr($feedLines);
$out .= $cmdCut;

$tmpFile = tempnam(sys_get_temp_dir(), 'esc_agent_');
rename($tmpFile, $tmpFile . '.bin');
$tmpFile .= '.bin';
file_put_contents($tmpFile, $out);

$ps1 = __DIR__ . '/print_raw.ps1';
$cmd = 'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass'
     . ' -File ' . escapeshellarg($ps1)
     . ' -PrinterName ' . escapeshellarg($printerName)
     . ' -FilePath ' . escapeshellarg($tmpFile)
     . ' -RawPort 9100 2>&1';

$result  = shell_exec($cmd);
$success = $result !== null && stripos(trim($result), 'OK') !== false;

@unlink($tmpFile);

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Biglietto stampato.']);
} else {
    $err = trim((string)$result);
    $logFile = __DIR__ . '/stampa_errori_agent.log';
    @file_put_contents(
        $logFile,
        date('[Y-m-d H:i:s]') . " AGENT ERR model=$printerModel printer=$printerName result=$err\n",
        FILE_APPEND | LOCK_EX
    );
    echo json_encode(['success' => false, 'error' => 'Stampa non riuscita: ' . $err]);
}
