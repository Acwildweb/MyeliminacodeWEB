<?php
/**
 * stampa_diretta.php
 * Stampa biglietto ESC/POS (Modalità A — server Windows con stampante di rete).
 * Sul server Linux usare la Modalità B (print_agent.php sul totem).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/printer_profiles.php';

$turno  = isset($_POST['turno'])  ? trim($_POST['turno'])  : '';
$numero = isset($_POST['numero']) ? trim($_POST['numero']) : '';
$data   = isset($_POST['data'])   ? trim($_POST['data'])   : date('Ymd');

if ($turno === '' || $numero === '') {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti (turno, numero)']);
    exit;
}

$configFile = __DIR__ . '/printer_config.json';
if (!file_exists($configFile)) {
    echo json_encode([
        'success' => false,
        'error'   => 'Configurazione stampante non trovata. Aprire config_stampante.php per impostare la stampante.'
    ]);
    exit;
}

$config = json_decode(file_get_contents($configFile), true);
$printerName = isset($config['printer_name']) ? trim($config['printer_name']) : '';

if ($printerName === '') {
    echo json_encode([
        'success' => false,
        'error'   => 'Nome stampante non configurato. Aprire config_stampante.php.'
    ]);
    exit;
}

$logoPath = $config['logo_path'] ?? '';
$logoFile = $logoPath !== '' ? __DIR__ . '/' . $logoPath : '';

$out = buildEscPosTicket($turno, $numero, $data, $config, $logoFile !== '' && file_exists($logoFile) ? $logoFile : null);

$tmpFile = tempnam(sys_get_temp_dir(), 'prn') . '.bin';
if (file_put_contents($tmpFile, $out) === false) {
    echo json_encode(['success' => false, 'error' => 'Impossibile creare il file temporaneo di stampa.']);
    exit;
}

$ps1 = __DIR__ . '\\print_raw.ps1';
$cmd = 'powershell.exe'
     . ' -ExecutionPolicy Bypass'
     . ' -NonInteractive'
     . ' -WindowStyle Hidden'
     . ' -File "' . $ps1 . '"'
     . ' -PrinterName ' . escapeshellarg($printerName)
     . ' -FilePath ' . escapeshellarg($tmpFile)
     . ' 2>&1';

$psOutput = trim((string)shell_exec($cmd));
@unlink($tmpFile);

if ($psOutput === 'OK') {
    echo json_encode([
        'success' => true,
        'message' => 'Stampa inviata correttamente alla stampante "' . htmlspecialchars($printerName) . '"'
    ]);
} else {
    $logFile = __DIR__ . '/stampa_errori.log';
    $logMsg   = date('Y-m-d H:i:s') . ' | Turno:' . $turno . ' Num:' . $numero
              . ' | Stampante:"' . $printerName . '" | Output PS: ' . $psOutput . "\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);

    echo json_encode([
        'success' => false,
        'error'   => 'Errore durante la stampa. Dettaglio: ' . $psOutput
    ]);
}
