<?php
/**
 * config_stampante.php
 * Pagina di configurazione della stampante termica per la stampa kiosk.
 * – da localhost : accesso diretto (nessuna password)
 * – da rete LAN  : accesso con password (condivisa con amministrazione.php)
 */

session_start();

require_once __DIR__ . '/printer_profiles.php';

// ── Controllo accesso ────────────────────────────────────────
$remoteIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($remoteIP, ['127.0.0.1', '::1'], true);

if (!$isLocalhost && !preg_match(
    '/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|::1$|fc[0-9a-f]|fd[0-9a-f])/i',
    $remoteIP
)) {
    http_response_code(403);
    die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Accesso negato</title>'
       .'<style>body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;'
       .'align-items:center;justify-content:center;height:100vh;margin:0}'
       .'.b{text-align:center;padding:40px;background:rgba(255,255,255,.08);border-radius:12px}</style></head>'
       .'<body><div class="b"><h2>403 — Accesso non consentito</h2>'
       .'<p>Questa pagina è accessibile solo dalla rete locale.</p></div></body></html>');
}

// ── Autenticazione LAN (stessa password di config_totem_ui.php) ──────────
$authFile  = __DIR__ . '/admin_auth.json';
$authData  = file_exists($authFile)
    ? (json_decode(file_get_contents($authFile), true) ?: [])
    : [];
$pwdHash   = $authData['password_hash'] ?? null;
$isDefault = ($pwdHash === null);
if ($isDefault) $pwdHash = password_hash('admin', PASSWORD_DEFAULT);

$authKey   = 'totem_cfg_' . substr(md5(__DIR__), 0, 8);  // stesso token di amministrazione.php
$authError = '';

if (isset($_GET['logout'])) {
    unset($_SESSION[$authKey]);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

if (!$isLocalhost && isset($_POST['_lan_pwd'])) {
    if (password_verify(trim($_POST['_lan_pwd']), $pwdHash)) {
        $_SESSION[$authKey] = ['t' => time(), 'ip' => $remoteIP];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $authError = 'Password non corretta.';
    sleep(1);
}

$sessionOk = $isLocalhost || (
    isset($_SESSION[$authKey]) &&
    (time() - (int)($_SESSION[$authKey]['t'] ?? 0)) < 28800 &&
    ($_SESSION[$authKey]['ip'] ?? '') === $remoteIP
);

if (!$isLocalhost && !$sessionOk) {
    http_response_code($authError !== '' ? 401 : 200);
    ?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accesso — Configurazione Stampante</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Segoe UI,Arial,sans-serif;
     background:linear-gradient(160deg,#0d1b3e 0%,#1a3a6e 100%);
     min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:rgba(255,255,255,.07);backdrop-filter:blur(14px);
    border:1px solid rgba(100,160,255,.25);border-radius:16px;
    padding:44px 36px;width:100%;max-width:380px;color:#fff;text-align:center}
.lock{font-size:52px;margin-bottom:14px}
h1{font-size:20px;margin-bottom:6px}
.sub{font-size:13px;opacity:.6;margin-bottom:26px}
input[type=password]{width:100%;padding:12px 16px;
    border:1px solid rgba(255,255,255,.25);border-radius:8px;
    background:rgba(255,255,255,.1);color:#fff;font-size:15px;
    outline:none;margin-bottom:14px;letter-spacing:.06em}
input[type=password]:focus{border-color:#42a5f5}
.btn-login{width:100%;padding:12px;
    background:linear-gradient(135deg,#1565c0,#0d47a1);
    border:none;border-radius:8px;color:#fff;font-size:15px;
    font-weight:600;cursor:pointer}
.btn-login:hover{background:linear-gradient(135deg,#1976d2,#1565c0)}
.err{background:rgba(220,53,69,.2);border:1px solid rgba(220,53,69,.5);
    border-radius:6px;padding:10px;font-size:13px;margin-bottom:14px;color:#f99}
.warn{background:rgba(255,193,7,.15);border:1px solid rgba(255,193,7,.4);
    border-radius:6px;padding:10px 12px;font-size:12px;margin-bottom:16px;
    color:#ffd54f;text-align:left;line-height:1.6}
</style>
</head>
<body>
<div class="login-box">
    <div class="lock">🔒</div>
    <h1>Configurazione Stampante</h1>
    <p class="sub">Accesso da rete LAN — inserire la password di amministrazione</p>
    <?php if ($isDefault): ?>
    <div class="warn">⚠️ Stai usando la password predefinita <strong>admin</strong>.<br>
        Cambiala in <em>amministrazione.php → 🔐 Sicurezza</em>.</div>
    <?php endif; ?>
    <?php if ($authError !== ''): ?>
    <div class="err">❌ <?php echo htmlspecialchars($authError); ?></div>
    <?php endif; ?>
    <form method="POST">
        <input type="password" name="_lan_pwd" placeholder="Password" autofocus autocomplete="current-password">
        <button type="submit" class="btn-login">🔓 Accedi</button>
    </form>
</div>
</body>
</html>
    <?php
    exit;
}
// ── Fine blocco autenticazione ──────────────────────────────

$configFile = __DIR__ . '/printer_config.json';
$message    = '';
$msgType    = '';

// Leggi configurazione esistente
$config = [];
if (file_exists($configFile)) {
    $config = json_decode(file_get_contents($configFile), true) ?: [];
}

// ── Recupera stampanti locali del server ────────────────────
$printerList = [];
$psOut = shell_exec('powershell.exe -NonInteractive -ExecutionPolicy Bypass -Command "Get-Printer | Select-Object -ExpandProperty Name" 2>&1');
if ($psOut) {
    $lines = array_filter(array_map('trim', explode("\n", $psOut)));
    foreach ($lines as $line) {
        if ($line !== '') $printerList[] = $line;
    }
}

// ── Ricerca stampanti su computer remoto (AJAX) ───────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_remote_printers') {
    header('Content-Type: application/json; charset=utf-8');
    $host = preg_replace('/[^a-zA-Z0-9._\-]/', '', trim($_POST['host'] ?? ''));
    if ($host === '') { echo json_encode(['error' => 'Host non valido']); exit; }

    // ── Tentativo 1: Get-Printer -ComputerName (WMI/CIM) ─────
    $escapedHost = str_replace("'", "''", $host);
    $psCmd = 'powershell.exe -NonInteractive -ExecutionPolicy Bypass -Command '
           . '"Get-Printer -ComputerName \'' . $escapedHost . '\' '
           . '| Select-Object -ExpandProperty Name"';
    $descriptors = [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']];
    $proc = @proc_open($psCmd, $descriptors, $pipes);
    $stdout = $stderr = '';
    if (is_resource($proc)) {
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
        $exitCode = proc_close($proc);
    } else {
        $exitCode = 1;
    }
    $list = [];
    if ($exitCode === 0 && trim($stdout) !== '') {
        $list = array_values(array_filter(array_map('trim', explode("\n", $stdout))));
    }

    // ── Tentativo 2: net view (SMB) ───────────────────────────
    // $host è già sanificato con preg_replace, NON usare escapeshellarg
    // (su Windows lo avvolgerebbe in virgolette rompendo il percorso UNC)
    if (empty($list)) {
        $netCmd = 'net view \\\\' . $host . ' /all 2>&1';
        $netOut = (string)shell_exec($netCmd);
        // "net view" produce righe con colonne a larghezza fissa:
        // "Nome condivisione                 Tipo    ..."
        // I nomi delle condivisioni possono contenere spazi, quindi usiamo
        // .+? (lazy) seguito da 2+ spazi come separatore di colonna
        foreach (explode("\n", $netOut) as $line) {
            $line = trim($line);
            if (preg_match('/^(.+?)\s{2,}(?:Stampa|Print)\b/i', $line, $m)) {
                $shareName = trim($m[1]);
                $list[] = '\\\\' . $host . '\\' . $shareName;
            }
        }
    }

    if (!empty($list)) {
        echo json_encode(['printers' => $list]);
    } else {
        // Nessun metodo ha funzionato: invece di restituire l'errore grezzo,
        // spiega cosa fare
        $hint = '';
        if (stripos($stderr . $stdout, '0x80070721') !== false
         || stripos($stderr . $stdout, 'CimException') !== false) {
            $hint = 'Il computer remoto non accetta connessioni WMI. '
                  . 'Verifica che su ' . htmlspecialchars($host)
                  . ' sia abilitato "Condivisione file e stampanti" nel Firewall di Windows.';
        } elseif (stripos($stderr . $stdout, 'Accesso negato') !== false
               || stripos($stderr . $stdout, 'Access is denied') !== false) {
            $hint = 'Accesso negato. Abilita l\'accesso Guest alla condivisione '
                  . 'oppure inserisci il percorso UNC manualmente.';
        } else {
            $hint = 'Impossibile interrogare le stampanti su ' . htmlspecialchars($host) . '. '
                  . 'Inserisci il percorso UNC manualmente: \\\\' . htmlspecialchars($host) . '\\NomeStampanteCondivisa';
        }
        echo json_encode(['printers' => [], 'hint' => $hint]);
    }
    exit;
}

// ── Salva configurazione (POST) ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $newPrintMode   = in_array($_POST['print_mode'] ?? '', ['server','local_agent']) ? $_POST['print_mode'] : 'server';
    $newPrinterName = trim($_POST['printer_name'] ?? '');
    // In Modalità B il server non usa printer_name: preserva il valore già in config
    if ($newPrintMode === 'local_agent' && $newPrinterName === '') {
        $newPrinterName = $config['printer_name'] ?? '';
    }
    $newConfig = [
        'print_mode'      => $newPrintMode,
        'printer_name'    => $newPrinterName,
        'local_agent_url' => trim($_POST['local_agent_url'] ?? 'http://localhost/MySanitarioConTotem/print_agent.php'),
        'printer_model'   => normalizePrinterModel($_POST['printer_model'] ?? 'np2511d2'),
        'intestazione1'   => trim($_POST['intestazione1']   ?? 'BIGLIETTO PRENOTAZIONE'),
        'intestazione2'   => trim($_POST['intestazione2']   ?? ''),
        'piede'           => trim($_POST['piede']           ?? ''),
        'logo_path'       => $config['logo_path'] ?? '',
    ];

    // ── Rimozione logo ────────────────────────────────────────
    if (!empty($_POST['remove_logo'])) {
        $oldLogo = __DIR__ . '/printer_logo.png';
        if (file_exists($oldLogo)) @unlink($oldLogo);
        $newConfig['logo_path'] = '';
    }

    // ── Upload logo ───────────────────────────────────────────
    if (!empty($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['logo_file']['tmp_name']);
        finfo_close($finfo);
        if (in_array($mime, $allowedMime, true)) {
            $destLogo = __DIR__ . '/printer_logo.png';
            // Converti sempre in PNG tramite GD per uniformità
            switch ($mime) {
                case 'image/jpeg': $imgSrc = @imagecreatefromjpeg($_FILES['logo_file']['tmp_name']); break;
                case 'image/png':  $imgSrc = @imagecreatefrompng($_FILES['logo_file']['tmp_name']);  break;
                case 'image/gif':  $imgSrc = @imagecreatefromgif($_FILES['logo_file']['tmp_name']);  break;
                case 'image/bmp':  $imgSrc = @imagecreatefrombmp($_FILES['logo_file']['tmp_name']);  break;
                case 'image/webp': $imgSrc = @imagecreatefromwebp($_FILES['logo_file']['tmp_name']); break;
                default: $imgSrc = false;
            }
            if ($imgSrc) {
                // Sfondo bianco (utile per PNG con trasparenza)
                $w = imagesx($imgSrc); $h = imagesy($imgSrc);
                $out_img = imagecreatetruecolor($w, $h);
                imagefill($out_img, 0, 0, imagecolorallocate($out_img, 255, 255, 255));
                imagecopy($out_img, $imgSrc, 0, 0, 0, 0, $w, $h);
                imagedestroy($imgSrc);
                if (imagepng($out_img, $destLogo)) {
                    $newConfig['logo_path'] = 'printer_logo.png';
                } else {
                    $message2 = ' (Attenzione: impossibile salvare il logo, controlla i permessi della cartella)';
                }
                imagedestroy($out_img);
            } else {
                $message2 = ' (Attenzione: impossibile aprire l\'immagine con GD)';
            }
        } else {
            $message2 = ' (Attenzione: formato immagine non supportato – usa JPG, PNG o GIF)';
        }
    }

    if ($newConfig['print_mode'] === 'server' && $newConfig['printer_name'] === '') {
        $message = 'Il nome della stampante non può essere vuoto (Modalità Server).';
        $msgType = 'error';
    } else {
        if (file_put_contents($configFile, json_encode($newConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $config  = $newConfig;
            $message = 'Configurazione salvata correttamente.' . ($message2 ?? '');
            $msgType = empty($message2) ? 'success' : 'warning';
        } else {
            $message = 'Impossibile scrivere il file di configurazione. Verifica i permessi.';
            $msgType = 'error';
        }
    }
}

// ── Stampa di test (POST) ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_print'])) {
    // Ricarica la config dal file
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true) ?: [];
    }
    $effectiveMode = in_array($_POST['print_mode'] ?? ($config['print_mode'] ?? 'server'), ['server','local_agent'], true)
        ? ($_POST['print_mode'] ?? ($config['print_mode'] ?? 'server'))
        : 'server';

    if ($effectiveMode === 'local_agent') {
        $message = 'Modalita B: il test viene eseguito dal browser del totem verso l\'agente locale.';
        $msgType = 'warning';
    } else {
        $testUrl = 'http://localhost/MySanitarioConTotem/stampa_diretta.php';
        $postData = http_build_query([
            'turno'  => 'TEST',
            'numero' => '999',
            'data'   => date('Ymd'),
        ]);
        $opts = ['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'content' => $postData,
            'timeout' => 15,
        ]];
        $ctx = stream_context_create($opts);
        $resp = @file_get_contents($testUrl, false, $ctx);
        if ($resp !== false) {
            $decoded = json_decode($resp, true);
            if ($decoded && $decoded['success']) {
                $message = 'Stampa di test inviata con successo!';
                $msgType = 'success';
            } else {
                $message = 'Errore stampa di test: ' . ($decoded['error'] ?? $resp);
                $msgType = 'error';
            }
        } else {
            $message = 'Impossibile raggiungere stampa_diretta.php – controlla XAMPP/Apache.';
            $msgType = 'error';
        }
    }
}

$printMode       = $config['print_mode']      ?? 'server';
$currentPrinter  = $config['printer_name']    ?? '';
$localAgentUrl   = $config['local_agent_url'] ?? 'http://localhost/MySanitarioConTotem/print_agent.php';
$printerModel    = $config['printer_model']   ?? 'np2511d2';
$intestazione1   = $config['intestazione1']   ?? 'BIGLIETTO PRENOTAZIONE';
$intestazione2   = $config['intestazione2']   ?? '';
$piede           = $config['piede']           ?? '';
$logoPath        = $config['logo_path']       ?? '';
$logoFile        = $logoPath !== '' ? __DIR__ . '/' . $logoPath : '';
$logoExists      = $logoFile !== '' && file_exists($logoFile);

// ── Download pacchetto agente locale (GET ?download=agent_package) ──────────
if (isset($_GET['download']) && $_GET['download'] === 'agent_package') {
    // Ricarica config aggiornata
    $dlConfig = file_exists($configFile)
        ? (json_decode(file_get_contents($configFile), true) ?: [])
        : [];

    // Leggi nome struttura da totem_ui_config.json
    $uiCfgFile  = __DIR__ . '/totem_ui_config.json';
    $uiCfg      = file_exists($uiCfgFile) ? (json_decode(file_get_contents($uiCfgFile), true) ?: []) : [];
    $nomeStruttura = $uiCfg['nome_struttura'] ?? 'MySanitario';

    // Determina URL base del server corrente
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $baseUrl  = $scheme . '://' . $host;
    $dirPath  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    $agentUrl = 'http://localhost' . $dirPath . '/print_agent.php';

    // Costruisci printer_config_local.json dinamico
    $localJson = [
        // Se il server era in Modalità A (percorso UNC \\server\nome),
        // sul totem serve il nome locale → svuota il campo per forzare la personalizzazione
        'printer_name'  => (function() use ($dlConfig) {
            $n = $dlConfig['printer_name'] ?? '';
            return (substr($n, 0, 2) === '\\\\') ? '' : $n;
        })(),
        'printer_model' => $dlConfig['printer_model'] ?? 'epson_t88',
        'intestazione1' => $dlConfig['intestazione1'] ?? $nomeStruttura,
        'intestazione2' => $dlConfig['intestazione2'] ?? '',
        'piede'         => $dlConfig['piede']         ?? '',
        'logo_path'     => ($dlConfig['logo_path'] ?? '') !== '' ? 'printer_logo.png' : '',
    ];
    $localJsonStr = json_encode($localJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Costruisci printer_config.json per il server (local_agent_url aggiornato)
    $serverJson = $dlConfig;
    $serverJson['local_agent_url'] = $agentUrl;
    $serverJsonStr = json_encode($serverJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    // Leggi print_agent.php, printer_profiles.php e print_raw.ps1
    $agentPhpContent    = file_get_contents(__DIR__ . '/print_agent.php');
    $profilesPhpContent = file_get_contents(__DIR__ . '/printer_profiles.php');
    $printRawContent = file_exists(__DIR__ . '/print_raw.ps1')
        ? file_get_contents(__DIR__ . '/print_raw.ps1')
        : '';

    // Genera il README di installazione
    $dirName   = basename($dirPath) ?: 'MySanitarioConTotem';
    $readme = "# Pacchetto Agente Stampa Locale — {$nomeStruttura}\r\n";
    $readme .= "# Generato il " . date('d/m/Y H:i') . " da {$baseUrl}\r\n\r\n";
    $readme .= "## Installazione rapida (3 passi)\r\n\r\n";
    $readme .= "1. Installa XAMPP per Windows: https://www.apachefriends.org/\r\n";
    $readme .= "   Avvia il pannello XAMPP e clicca **Start** su Apache.\r\n\r\n";
    $readme .= "2. Copia l'intera cartella `{$dirName}/` in:\r\n";
    $readme .= "   C:\\xampp\\htdocs\\{$dirName}\\\r\n\r\n";
    $readme .= "3. Apri `printer_config_local.json` e imposta:\r\n";
    $readme .= "   - `printer_name` con il nome esatto della stampante su questo PC\r\n";
    $readme .= "   - `printer_model` uguale al modello scelto sul server (es. custom_tg2460 o custom_tg2480)\r\n";
    $readme .= "   Per Custom TG: in PrinterSet impostare emulazione CUSTOM/POS, cutter Enabled,\r\n";
    $readme .= "   Code Table PC858 (19). Non usare profili NP-2511D-2 o Generico ESC/POS.\r\n\r\n";
    $readme .= "## File inclusi\r\n\r\n";
    $readme .= "| File | Descrizione |\r\n";
    $readme .= "|------|-------------|\r\n";
    $readme .= "| print_agent.php | Agente PHP che riceve la richiesta dal browser e stampa |\r\n";
    $readme .= "| printer_profiles.php | Profili comandi ESC/POS per modello stampante |\r\n";
    $readme .= "| print_raw.ps1 | Script PowerShell per stampa RAW via Windows API |\r\n";
    $readme .= "| printer_config_local.json | Configurazione stampante locale (da personalizzare) |\r\n";
    if (($dlConfig['logo_path'] ?? '') !== '') {
        $readme .= "| printer_logo.png | Logo da stampare in cima al biglietto |\r\n";
    }
    $readme .= "\r\n## Verifica funzionamento\r\n\r\n";
    $readme .= "Con Apache avviato, apri nel browser del totem:\r\n";
    $readme .= "  http://localhost/{$dirName}/print_agent.php\r\n";
    $readme .= "Dovresti vedere: {\"success\":false,\"error\":\"Parametri mancanti (turno, numero)\"}\r\n\r\n";
    $readme .= "Poi testa la stampa completa aprendo il totem:\r\n";
    $readme .= "  {$baseUrl}/totem.php\r\n";
    $readme .= "e cliccando su un turno.\r\n\r\n";
    $readme .= "## URL agente configurato sul server\r\n\r\n";
    $readme .= "  {$agentUrl}\r\n";
    $readme .= "(già impostato in printer_config.json sul server)\r\n";

    // Crea ZIP in memoria
    $zipTmp = tempnam(sys_get_temp_dir(), 'agent_pkg_') . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zipTmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        http_response_code(500);
        echo json_encode(['error' => 'Impossibile creare il file ZIP.']);
        exit;
    }
    $zip->addEmptyDir($dirName);
    $zip->addFromString($dirName . '/print_agent.php',            $agentPhpContent);
    $zip->addFromString($dirName . '/printer_profiles.php',       $profilesPhpContent);
    $zip->addFromString($dirName . '/print_raw.ps1',              $printRawContent);
    $zip->addFromString($dirName . '/printer_config_local.json',  $localJsonStr);
    $zip->addFromString($dirName . '/INSTALLAZIONE.txt',          $readme);

    // Includi il logo se presente
    $logoSrc = ($dlConfig['logo_path'] ?? '') !== '' ? __DIR__ . '/' . $dlConfig['logo_path'] : '';
    if ($logoSrc !== '' && file_exists($logoSrc)) {
        $zip->addFile($logoSrc, $dirName . '/printer_logo.png');
    }
    $zip->close();

    // Aggiorna local_agent_url sul server con il valore calcolato
    if ($serverJson['local_agent_url'] !== ($dlConfig['local_agent_url'] ?? '')) {
        file_put_contents($configFile, json_encode($serverJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    // Invia il file al browser
    $zipSize = filesize($zipTmp);
    $safeNome = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $nomeStruttura);
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="agente-stampa-' . $safeNome . '.zip"');
    header('Content-Length: ' . $zipSize);
    header('Cache-Control: no-cache, no-store');
    readfile($zipTmp);
    @unlink($zipTmp);
    exit;
}


?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurazione Stampante Termica – Totem</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Segoe UI, Arial, sans-serif; background: #f0f2f5; color: #333; }
        .container { max-width: 720px; margin: 40px auto; background: #fff; border-radius: 10px;
                     box-shadow: 0 4px 20px rgba(0,0,0,.12); padding: 36px; }
        h1 { font-size: 22px; margin-bottom: 6px; color: #1a73e8; }
        .subtitle { font-size: 13px; color: #888; margin-bottom: 28px; }
        .msg { padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; font-weight: 600; }
        .msg.success { background: #e6f4ea; color: #2d6a4f; border: 1px solid #b7dfca; }
        .msg.error   { background: #fce8e6; color: #c0392b; border: 1px solid #f5c6c2; }
        .msg.warning { background: #fef9c3; color: #7a5c00; border: 1px solid #f5e27a; }
        label { display: block; font-size: 13px; font-weight: 600; margin-bottom: 5px; color: #555; }
        select, input[type=text] {
            width: 100%; padding: 9px 12px; border: 1px solid #ccc;
            border-radius: 6px; font-size: 14px; margin-bottom: 18px; }
        select:focus, input[type=text]:focus { outline: none; border-color: #1a73e8; }
        .btn { display: inline-block; padding: 10px 22px; border-radius: 6px;
               font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
        .btn-primary { background: #1a73e8; color: #fff; }
        .btn-primary:hover { background: #1558b0; }
        .btn-test { background: #34a853; color: #fff; margin-left: 10px; }
        .btn-test:hover { background: #2a8a42; }
        .section-title { font-size: 15px; font-weight: 700; color: #444; margin-bottom: 14px;
                         border-bottom: 2px solid #e8eaed; padding-bottom: 6px; }
        .printer-select { display: flex; gap: 8px; align-items: flex-start; }
        .printer-select select { flex: 1; margin-bottom: 0; }
        .printer-select input { flex: 1; margin-bottom: 0; }
        .note { font-size: 12px; color: #888; margin-top: -12px; margin-bottom: 18px; }
        .log-link { font-size: 12px; color: #1a73e8; text-decoration: none; }
        .log-link:hover { text-decoration: underline; }
        .logo-preview-wrap { display:flex; align-items:center; gap:16px; margin-bottom:18px;
            padding:12px; background:#f8f9fa; border-radius:8px; border:1px solid #e0e0e0; }
        .logo-preview-wrap img { max-height:80px; max-width:240px; border:1px solid #ccc;
            border-radius:4px; background:#fff; }
        .logo-preview-wrap .logo-info { font-size:12px; color:#666; }
        .file-input-wrap { position:relative; }
        .file-input-wrap input[type=file] { width:100%; padding:7px; border:1px dashed #aaa;
            border-radius:6px; font-size:13px; background:#fafafa; margin-bottom:6px; }
        .checkbox-row { display:flex; align-items:center; gap:8px; margin-bottom:18px; font-size:13px; color:#c0392b; }
        .checkbox-row input[type=checkbox] { width:16px; height:16px; cursor:pointer; }
        .mode-box { border:2px solid #e0e0e0; border-radius:8px; padding:16px 18px; margin-bottom:18px;
            cursor:pointer; transition:border-color .2s,background .2s; }
        .mode-box:hover { border-color:#1a73e8; background:#f0f6ff; }
        .mode-box.active { border-color:#1a73e8; background:#e8f0fe; }
        .mode-box input[type=radio] { margin-right:8px; accent-color:#1a73e8; }
        .mode-box strong { font-size:14px; color:#1a237e; }
        .mode-box p { font-size:12px; color:#666; margin:4px 0 0 24px; line-height:1.5; }
        .pane { display:none; } .pane.active { display:block; }
        .info-box { background:#fff3cd; border:1px solid #ffc107; border-radius:6px;
            padding:12px 14px; font-size:12px; color:#664d03; margin-bottom:18px; line-height:1.6; }
        .info-box code { background:#ffe082; padding:1px 4px; border-radius:3px; font-size:11px; }
        .remote-row { display:flex; gap:8px; margin-bottom:12px; }
        .remote-row input { flex:1; margin-bottom:0; }
        .remote-row .btn { white-space:nowrap; padding:9px 14px; font-size:13px; }
        #remoteList { width:100%; margin-bottom:18px; display:none; }
    </style>
</head>
<body>
<div class="container">
    <h1>⚙️ Configurazione Stampante Termica</h1>
    <p class="subtitle">Imposta la stampante termica per la stampa kiosk dei biglietti.</p>

    <?php if ($message): ?>
        <div class="msg <?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="cfgForm">

        <!-- ══════════════════════════════════════════════════════ -->
        <div class="section-title">🖨️ Modalità di stampa</div>

        <label class="mode-box <?php echo $printMode==='server' ? 'active':''; ?>" onclick="switchMode('server')">
            <input type="radio" name="print_mode" value="server"
                   <?php if($printMode==='server') echo 'checked'; ?>
                   onchange="switchMode('server')">
            <strong>Modalità A — Stampante sul server (condivisione di rete Windows)</strong>
            <p>La stampante termica è connessa a un'altra macchina (il totem) ma è <strong>condivisa sulla rete Windows</strong>.
               Il server la raggiunge tramite percorso UNC <code>\\IP-totem\NomeStampante</code>.<br>
               Richiede: condivisione attiva sulla macchina totem + stessa rete locale.</p>
        </label>

        <label class="mode-box <?php echo $printMode==='local_agent' ? 'active':''; ?>" onclick="switchMode('local_agent')">
            <input type="radio" name="print_mode" value="local_agent"
                   <?php if($printMode==='local_agent') echo 'checked'; ?>
                   onchange="switchMode('local_agent')">
            <strong>Modalità B — Agente locale sul totem (consigliata per kiosk)</strong>
            <p>Sul totem gira un mini-server PHP locale (<code>print_agent.php</code>).
               Il browser del totem manda la richiesta di stampa direttamente a
               <code>http://localhost/…/print_agent.php</code> — nessun percorso di rete,
               nessuna condivisione Windows necessaria.</p>
        </label>

        <!-- ── Pannello Modalità A ──────────────────────────────── -->
        <div id="pane-server" class="pane <?php echo $printMode==='server' ? 'active':''; ?>">

            <div class="info-box">
                <strong>Come configurare la condivisione Windows sul totem:</strong><br>
                1. Sul totem: <em>Impostazioni → Bluetooth e dispositivi → Stampanti e scanner</em>
                → seleziona la stampante termica → <em>Proprietà stampante → Condivisione → Condividi stampante</em>.<br>
                2. Assegna un nome senza spazi (es. <code>TermalTotem</code>).<br>
                3. Apri il Firewall: consenti <em>Condivisione file e stampanti</em> nella rete locale.<br>
                4. Il percorso da usare qui sotto sarà: <code>\\IP-del-totem\TermalTotem</code>
            </div>

            <label>Cerca stampanti su computer remoto (opzionale)</label>
            <div class="remote-row">
                <input type="text" id="remoteHost" placeholder="IP o nome host del totem (es. 192.168.1.50)" style="margin-bottom:0">
                <button type="button" class="btn btn-primary" onclick="cercaRemote()">🔍 Cerca</button>
            </div>
            <select id="remoteList" onchange="document.getElementById('printer_name').value=this.value">
                <option value="">-- stampanti trovate sul computer remoto --</option>
            </select>

            <label for="printer_name">Nome / Percorso UNC stampante</label>
            <?php if (!empty($printerList)): ?>
            <div class="printer-select" style="margin-bottom:6px">
                <select id="printer_select" onchange="document.getElementById('printer_name').value=this.value">
                    <option value="">-- Stampanti locali del server (scorciatoia) --</option>
                    <?php foreach ($printerList as $p): ?>
                        <option value="<?php echo htmlspecialchars($p); ?>"
                            <?php if ($p === $currentPrinter && $printMode==='server') echo 'selected'; ?>>
                            <?php echo htmlspecialchars($p); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <input type="text" id="printer_name" name="printer_name"
                   value="<?php echo $printMode==='server' ? htmlspecialchars($currentPrinter) : ''; ?>"
                   placeholder="Es: \\192.168.1.50\TermalTotem  oppure  EPSON TM-T88V">
            <p class="note">Inserisci il nome esatto o il percorso UNC <code>\\IP\NomeCondivisione</code>.</p>
        </div>

        <!-- ── Pannello Modalità B ──────────────────────────────── -->
        <div id="pane-local_agent" class="pane <?php echo $printMode==='local_agent' ? 'active':''; ?>">

            <div style="margin-bottom:14px">
                <a href="?download=agent_package"
                   class="btn btn-primary"
                   style="display:inline-flex;align-items:center;gap:8px;text-decoration:none;padding:11px 22px;font-size:14px">
                    ⬇️ Scarica pacchetto installazione totem
                </a>
                <span style="font-size:12px;color:#888;margin-left:10px">
                    ZIP preconfigurato con i parametri attuali
                </span>
            </div>
            <div class="info-box">
                <strong>Come installare l'agente locale sul totem (3 passi):</strong><br><br>
                <strong>1.</strong> Installa <strong>XAMPP per Windows</strong> sul totem
                (<a href="https://www.apachefriends.org/" target="_blank" rel="noopener" style="color:#664d03">apachefriends.org</a>)
                e avvia <strong>Apache</strong> dal pannello XAMPP.<br>
                <strong>Importante:</strong> in <code>C:\xampp\php\php.ini</code> abilita GD
                (riga <code>extension=gd</code>, eventualmente rimuovendo <code>;</code>),
                poi riavvia Apache.<br><br>
                <strong>2.</strong> Clicca il pulsante <em>⬇️ Scarica pacchetto</em> qui sopra e
                copia la cartella estratta dallo ZIP in <code>C:\xampp\htdocs\</code>.<br>
                Il pacchetto contiene già <code>print_agent.php</code>, <code>print_raw.ps1</code>,
                <code>printer_config_local.json</code> e il logo preconfigurati con i dati attuali.<br><br>
                <strong>3.</strong> Apri <code>printer_config_local.json</code> sul totem e
                imposta <code>printer_name</code> con il nome <strong>esatto</strong> della stampante
                termica installata su quel PC (Menu Start → Stampanti e scanner → clicca stampante → Proprietà → scheda Generale).<br><br>
                ✅ <strong>Verifica:</strong> con Apache avviato, apri nel browser del totem
                <code>http://localhost/<?php echo htmlspecialchars(basename(dirname($_SERVER['SCRIPT_NAME']))); ?>/print_agent.php</code>
                — deve rispondere con un JSON. Poi apri
                <code><?php echo htmlspecialchars(($scheme??'http').':// '.($_SERVER['HTTP_HOST']??'server')); ?>/totem.php</code>
                e clicca un turno: il biglietto viene stampato.<br><br>
                <strong>Nota:</strong> l'agente ascolta solo su <code>localhost</code>, non è esposto sulla rete.
            </div>

            <!-- In Modalità B il printer_name non viene inviato al server: la stampante
                 è configurata localmente sul totem in printer_config_local.json -->
            <input type="hidden" id="printer_name_local"
                   value="<?php echo $printMode==='local_agent' ? htmlspecialchars($currentPrinter) : ''; ?>">

            <label for="local_agent_url">URL agente locale (visto dal browser del totem)</label>
            <input type="text" id="local_agent_url" name="local_agent_url"
                   value="<?php echo htmlspecialchars($localAgentUrl); ?>"
                   placeholder="http://localhost/MySanitarioConTotem/print_agent.php">
            <p class="note">Di solito <code>http://localhost/…/print_agent.php</code>.
               Il browser del totem raggiunge questo indirizzo, non il server centrale.</p>
        </div>

        <div class="section-title">🖨️ Modello stampante</div>
        <label for="printer_model">Seleziona il modello per abbinare i comandi ESC/POS corretti</label>
        <select id="printer_model" name="printer_model">
            <option value="np2511d2"
                <?php if ($printerModel === 'np2511d2')       echo 'selected'; ?>>
                NP-2511D-2 / NP-3511D-2 (Nippon Printer Engineering) — attuale
            </option>
            <option value="epson_t88"
                <?php if ($printerModel === 'epson_t88')      echo 'selected'; ?>>
                Epson TM-T88 V/VI/VII &mdash; TM-T20 I/II/III &mdash; TM-T70 &mdash; TM-U220
            </option>
            <option value="escpos_generic"
                <?php if ($printerModel === 'escpos_generic') echo 'selected'; ?>>
                Generico ESC/POS (Star TSP, Citizen CT-S, Bixolon, ecc.)
            </option>
            <option value="custom_tg2460"
                <?php if ($printerModel === 'custom_tg2460') echo 'selected'; ?>>
                Custom TG2460HIII (carta 60 mm) — CUSTOM/POS
            </option>
            <option value="custom_tg2480"
                <?php if ($printerModel === 'custom_tg2480') echo 'selected'; ?>>
                Custom TG2480HIII (carta 80 mm) — CUSTOM/POS
            </option>
        </select>
        <p class="note">Modifica solo se sostituisci la stampante con un modello diverso dalla NP-2511D-2.
            Influisce su codifica caratteri, comando di taglio, avanzamento carta e comando immagine raster.<br>
            <strong>Custom TG:</strong> usare solo i profili Custom TG2460/TG2480 (non NP-2511D-2 né Generico ESC/POS).</p>

        <div class="section-title">Logo in testa al biglietto</div>

        <?php if ($logoExists): ?>
        <div class="logo-preview-wrap">
            <img src="<?php echo htmlspecialchars($logoPath); ?>?v=<?php echo filemtime($logoFile); ?>"
                 alt="Logo attuale">
            <div class="logo-info">
                <strong>Logo attuale:</strong> <?php echo htmlspecialchars($logoPath); ?><br>
                <?php
                    list($lw,$lh) = getimagesize($logoFile);
                    echo $lw . ' × ' . $lh . ' px — ' . round(filesize($logoFile)/1024,1) . ' KB';
                ?>
            </div>
        </div>
        <div class="checkbox-row">
            <input type="checkbox" name="remove_logo" id="remove_logo" value="1">
            <label for="remove_logo" style="margin-bottom:0;color:#c0392b">Rimuovi logo esistente</label>
        </div>
        <?php else: ?>
        <p class="note" style="margin-bottom:14px;">Nessun logo configurato. Carica un'immagine (JPG, PNG, GIF – consigliato max 384 px di larghezza, in bianco/nero o scala di grigi per risultati migliori sulla stampante termica).</p>
        <?php endif; ?>

        <label for="logo_file">Carica nuovo logo</label>
        <div class="file-input-wrap">
            <input type="file" id="logo_file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/bmp,image/webp">
        </div>
        <p class="note">Il logo sarà stampato centrato in cima al biglietto, prima del testo.<br>
            Larghezza massima stampa: 384 pixel (80mm a 203dpi). Immagini più larghe vengono ridimensionate automaticamente.</p>

        <div class="section-title">Testo biglietto</div>

        <label for="intestazione1">Intestazione principale</label>
        <input type="text" id="intestazione1" name="intestazione1"
               value="<?php echo htmlspecialchars($intestazione1); ?>"
               placeholder="BIGLIETTO PRENOTAZIONE">

        <label for="intestazione2">Intestazione secondaria (opzionale)</label>
        <input type="text" id="intestazione2" name="intestazione2"
               value="<?php echo htmlspecialchars($intestazione2); ?>"
               placeholder="Nome struttura, reparto, ecc.">

        <label for="piede">Piede del biglietto (opzionale)</label>
        <input type="text" id="piede" name="piede"
               value="<?php echo htmlspecialchars($piede); ?>"
               placeholder="Grazie per la visita!">

        <div style="margin-top:10px">
            <button type="submit" name="save" class="btn btn-primary">💾 Salva configurazione</button>
            <?php if ($currentPrinter !== '' || $printMode === 'local_agent'): ?>
                <button type="submit" name="test_print" id="btnTestPrint" class="btn btn-test">🖨️ Stampa biglietto di test</button>
            <?php endif; ?>
        </div>
    </form>

    <div style="margin-top:28px; font-size:13px; color:#888;">
        File configurazione: <code>printer_config.json</code><br>
        <?php if (file_exists(__DIR__ . '/stampa_errori.log')): ?>
            📋 <a class="log-link" href="stampa_errori_view.php">Visualizza log errori stampa</a>
        <?php endif; ?>
        <div style="margin-top:12px;padding:10px 14px;background:#e8f0fe;border-radius:6px;color:#1a237e;font-size:12px">
            📖 <strong>Modalità attiva:</strong>
            <?php if($printMode==='local_agent'): ?>
                <strong>B – Agente locale</strong> — totem.php indirizzerà la stampa a
                <code><?php echo htmlspecialchars($localAgentUrl); ?></code>
            <?php else: ?>
                <strong>A – Stampante di rete/server</strong> — la stampa avviene tramite PowerShell sul server.
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SICUREZZA -->
<div class="container" style="margin-top:20px">
    <div class="section-title">🔐 Sicurezza — Accesso LAN</div>
    <?php if ($isLocalhost): ?>
    <p style="font-size:13px;color:#888;margin-bottom:14px">
        Sei connesso da <strong>localhost</strong>: l'accesso diretto non richiede password.<br>
        La password per gli accessi LAN si imposta in
        <a href="amministrazione.php" style="color:#1a73e8">amministrazione.php → 🔐 Sicurezza</a>.
    </p>
    <?php else: ?>
    <p style="font-size:13px;color:#888;margin-bottom:14px">
        Sei connesso da rete LAN (<code><?php echo htmlspecialchars($remoteIP); ?></code>).
        Sessione valida 8 ore.
        <a href="?logout" style="color:#ea4335;margin-left:10px">🚪 Disconnetti</a>
    </p>
    <?php endif; ?>
    <?php if ($isDefault): ?>
    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;
                font-size:12px;color:#664d03;margin-bottom:0">
        ⚠️ Password predefinita <strong>"admin"</strong> ancora attiva.
        Cambiala in <a href="amministrazione.php#sicurezza" style="color:#664d03;font-weight:700">amministrazione.php → 🔐 Sicurezza</a>.
    </div>
    <?php endif; ?>
</div>
<script>
function switchMode(m) {
    document.querySelectorAll('.mode-box').forEach(function(b){ b.classList.remove('active'); });
    var radio = document.querySelector('input[name=print_mode][value="'+m+'"]');
    if (radio) { radio.checked=true; radio.closest('.mode-box').classList.add('active'); }
    document.querySelectorAll('.pane').forEach(function(p){ p.classList.remove('active'); });
    var p = document.getElementById('pane-'+m);
    if (p) p.classList.add('active');
}
function cercaRemote() {
    var host = document.getElementById('remoteHost').value.trim();
    if (!host) { alert('Inserisci IP o nome host del totem.'); return; }
    var btn = event.target; btn.disabled=true; btn.textContent='…';
    var fd = new FormData();
    fd.append('action','get_remote_printers');
    fd.append('host', host);
    fetch('config_stampante.php', {method:'POST', body:fd})
        .then(function(r){ return r.json(); })
        .then(function(d){
            var sel = document.getElementById('remoteList');
            sel.style.display='block';
            sel.innerHTML='<option value="">-- stampanti trovate su '+host+' --</option>';
            if (d.error) {
                sel.innerHTML+='<option value="" disabled>Errore: '+d.error+'</option>';
            } else if (d.printers && d.printers.length) {
                d.printers.forEach(function(p){
                    // Se il percorso inizia già con \\ lo uso direttamente
                    var unc = p.startsWith('\\\\') ? p : '\\\\'+host+'\\'+p;
                    sel.innerHTML+='<option value="'+unc+'">'+p+'</option>';
                });
                if (d.hint) {
                    sel.innerHTML+='<option value="" disabled>ℹ️ '+d.hint+'</option>';
                }
            } else {
                // Lista vuota: mostra hint se disponibile
                var msg = d.hint || 'Nessuna stampante trovata. Inserisci il percorso UNC manualmente.';
                sel.innerHTML+='<option value="" disabled>⚠️ '+msg+'</option>';
            }
        })
        .catch(function(){ alert('Errore di comunicazione con il server.'); })
        .finally(function(){ btn.disabled=false; btn.textContent='🔍 Cerca'; });
}

(function(){
    var form = document.getElementById('cfgForm');
    if (!form) return;

    form.addEventListener('submit', function(e){
        var submitter = e.submitter || document.activeElement;
        if (!submitter || submitter.name !== 'test_print') return;

        var modeEl = document.querySelector('input[name="print_mode"]:checked');
        var mode = modeEl ? modeEl.value : 'server';
        if (mode !== 'local_agent') return;

        e.preventDefault();
        var btn = submitter;
        var oldText = btn.textContent;
        btn.disabled = true;
        btn.textContent = 'Test in corso...';

        var agentEl = document.getElementById('local_agent_url');
        var agentUrl = (agentEl && agentEl.value) ? agentEl.value : 'http://localhost/print_agent.php';
        var now = new Date();
        var y = now.getFullYear();
        var m = String(now.getMonth()+1).padStart(2,'0');
        var d = String(now.getDate()).padStart(2,'0');

        var body = new URLSearchParams({
            turno: 'TEST',
            numero: '999',
            data: '' + y + m + d
        });

        fetch(agentUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        })
        .then(function(r){ return r.json(); })
        .then(function(resp){
            if (resp && resp.success) {
                alert('Stampa di test inviata con successo (agente locale).');
            } else {
                alert('Errore stampa di test (agente locale): ' + ((resp && resp.error) ? resp.error : 'risposta non valida'));
            }
        })
        .catch(function(err){
            alert('Errore chiamata agente locale: ' + err);
        })
        .finally(function(){
            btn.disabled = false;
            btn.textContent = oldText;
        });
    });
})();
</script>
</body>
</html>
