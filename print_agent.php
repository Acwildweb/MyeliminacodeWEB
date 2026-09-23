<?php
/**
 * print_agent.php - Agente di stampa locale per il totem (Modalità B)
 * =========================================================================
 * Installare SULLA MACCHINA TOTEM Windows con stampante Axon A8R (o compatibile).
 *
 * INSTALLAZIONE (Axon A8R):
 *  1. Driver Windows A8R da axonmicrelec.com → Software e Driver → A8R
 *  2. XAMPP sul totem (Apache + PHP, extension=gd in php.ini)
 *  3. Copiare: print_agent.php, printer_profiles.php, print_raw.ps1,
 *     printer_config_local.json (e opz. printer_logo.png)
 *  4. In printer_config_local.json: printer_name = nome Windows della A8R,
 *     printer_model = axon_a8r
 *  5. Sul server: config_stampante.php → Modalità B + URL agente localhost
 *
 * SICUREZZA: solo localhost (127.0.0.1 / ::1). CORS per totem.php sul server.
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

$logoPath = $config['logo_path'] ?? '';
$logoFile = $logoPath !== '' ? __DIR__ . '/' . $logoPath : '';

$printerModel = normalizePrinterModel($config['printer_model'] ?? 'axon_a8r');
$out = buildEscPosTicket($turno, $numero, $data, $config, $logoFile !== '' ? $logoFile : null);

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
