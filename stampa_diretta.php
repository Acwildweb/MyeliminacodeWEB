<?php
/**
 * stampa_diretta.php
 * Stampa il biglietto ESC/POS direttamente sulla stampante termica
 * senza alcun intervento umano (modalità kiosk).
 *
 * Chiamata via AJAX POST da totem.php con i parametri:
 *   turno  - nome del turno
 *   numero - numero biglietto
 *   data   - data in formato Ymd
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/printer_profiles.php';

// ── 1. Parametri ──────────────────────────────────────────────
$turno  = isset($_POST['turno'])  ? trim($_POST['turno'])  : '';
$numero = isset($_POST['numero']) ? trim($_POST['numero']) : '';
$data   = isset($_POST['data'])   ? trim($_POST['data'])   : date('Ymd');

if ($turno === '' || $numero === '') {
    echo json_encode(['success' => false, 'error' => 'Parametri mancanti (turno, numero)']);
    exit;
}

// ── 2. Leggi configurazione stampante ─────────────────────────
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

// ── 3. Intestazione da config (opzionale) ─────────────────────
$intestazione1 = isset($config['intestazione1']) ? $config['intestazione1'] : 'BIGLIETTO PRENOTAZIONE';
$intestazione2 = isset($config['intestazione2']) ? $config['intestazione2'] : '';
$piede         = isset($config['piede'])         ? $config['piede']         : '';
$logoPath      = isset($config['logo_path'])     ? $config['logo_path']     : '';
$logoFile      = $logoPath !== '' ? __DIR__ . '/' . $logoPath : '';

// ── 4. Formattazione data/ora ──────────────────────────────────
$dataFormattata = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
$oraFormattata  = date('H:i');

// ── 5. Costruzione payload ESC/POS ────────────────────────────
$ESC = "\x1B";
$GS  = "\x1D";

$printerModel = normalizePrinterModel($config['printer_model'] ?? 'np2511d2');
$profile      = getPrinterProfile($printerModel);
$cmdCodePage  = $profile['cmdCodePage'];
$cmdCut       = $profile['cmdCut'];
$feedLines    = $profile['feedLines'];
$imgMode      = $profile['imgMode'];
$maxImgWidth  = $profile['maxImgWidth'];

$out = '';

// Reset stampante
$out .= $ESC . '@';
$out .= $cmdCodePage; // codifica caratteri specifica per il modello

// Centra tutto
$out .= $ESC . 'a' . chr(1);

// ── Logo immagine (se configurato) ─────────────────────────────────
if ($logoFile !== '' && file_exists($logoFile)) {
    $escImg = buildEscPosRaster($logoFile, $maxImgWidth, $imgMode);
    if ($escImg !== '') {
        $out .= $escImg;
        if ($imgMode !== 'esc_star') {
            $out .= "\n";
        }
    }
}

// Intestazione principale – doppia altezza + grassetto
$out .= $ESC . '!' . chr(0x30);
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', $intestazione1) . "\n";
$out .= $ESC . '!' . chr(0x00);

// Eventuale seconda riga intestazione
if ($intestazione2 !== '') {
    $out .= $ESC . '!' . chr(0x08); // grassetto
    $out .= iconv('UTF-8', 'CP1252//TRANSLIT', $intestazione2) . "\n";
    $out .= $ESC . '!' . chr(0x00);
}

// Separatore
$out .= "================================\n";
$out .= "\n";

// Turno – doppia larghezza
$out .= $ESC . '!' . chr(0x20);
$out .= 'Turno: ' . iconv('UTF-8', 'CP1252//TRANSLIT', $turno) . "\n";
$out .= $ESC . '!' . chr(0x00);
$out .= "\n";

// Numero – quadruplo (larghezza + altezza + grassetto)
$out .= $ESC . '!' . chr(0x38);
$out .= $numero . "\n";
$out .= $ESC . '!' . chr(0x00);
$out .= "\n";

// Separatore
$out .= "================================\n";

// Footer data/ora – font piccolo
$out .= $ESC . '!' . chr(0x01);
$out .= 'Data: ' . $dataFormattata . "\n";
$out .= 'Ora:  ' . $oraFormattata  . "\n";

// Piede personalizzato
if ($piede !== '') {
    $out .= "\n" . iconv('UTF-8', 'CP1252//TRANSLIT', $piede) . "\n";
}

$out .= $ESC . '!' . chr(0x00);

// Avanzamento carta e taglio – comandi specifici del modello (vedi switch sopra)
$out .= $ESC . 'd' . chr($feedLines);
$out .= $cmdCut;

// ── 6. Scrivi in file temporaneo ──────────────────────────────
$tmpFile = tempnam(sys_get_temp_dir(), 'prn') . '.bin';
if (file_put_contents($tmpFile, $out) === false) {
    echo json_encode(['success' => false, 'error' => 'Impossibile creare il file temporaneo di stampa.']);
    exit;
}

// ── 7. Invia alla stampante ───────────────────────────────────
// Percorso assoluto allo script PowerShell
$ps1 = __DIR__ . '\\print_raw.ps1';

// Costruisci il comando PowerShell
// -ExecutionPolicy Bypass: evita blocchi di policy sul server locale
// -NonInteractive -WindowStyle Hidden: nessuna finestra visibile
$cmd = 'powershell.exe'
     . ' -ExecutionPolicy Bypass'
     . ' -NonInteractive'
     . ' -WindowStyle Hidden'
     . ' -File "' . $ps1 . '"'
     . ' -PrinterName ' . escapeshellarg($printerName)
     . ' -FilePath ' . escapeshellarg($tmpFile)
     . ' 2>&1';

$psOutput = shell_exec($cmd);
$psOutput = trim((string)$psOutput);

// Rimuovi il file temporaneo
@unlink($tmpFile);

// ── 8. Risposta ───────────────────────────────────────────────
if ($psOutput === 'OK') {
    echo json_encode([
        'success' => true,
        'message' => 'Stampa inviata correttamente alla stampante "' . htmlspecialchars($printerName) . '"'
    ]);
} else {
    // Log dell'errore su file per debug
    $logFile = __DIR__ . '/stampa_errori.log';
    $logMsg   = date('Y-m-d H:i:s') . ' | Turno:' . $turno . ' Num:' . $numero
              . ' | Stampante:"' . $printerName . '" | Output PS: ' . $psOutput . "\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);

    echo json_encode([
        'success' => false,
        'error'   => 'Errore durante la stampa. Dettaglio: ' . $psOutput
    ]);
}
