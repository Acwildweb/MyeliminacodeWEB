<?php
/**
 * amministrazione.php
 * Configurazione grafica del totem kiosk
 * – da localhost : accesso diretto (nessuna password)
 * – da rete LAN  : accesso con password
 */

session_start();

// ── Controllo accesso ─────────────────────────────────────────
$remoteIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($remoteIP, ['127.0.0.1', '::1'], true)
              || (bool)preg_match('/^10\.213\.134\./', $remoteIP);

function _cfgIsLanIP(string $ip): bool {
    return (bool)preg_match(
        '/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|::1$|fc[0-9a-f]|fd[0-9a-f])/i',
        $ip
    );
}

if (!$isLocalhost && !_cfgIsLanIP($remoteIP)) {
    http_response_code(403);
    die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Accesso negato</title>'
       .'<style>body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;'
       .'align-items:center;justify-content:center;height:100vh;margin:0}'
       .'.b{text-align:center;padding:40px;background:rgba(255,255,255,.08);border-radius:12px}</style></head>'
       .'<body><div class="b"><h2>403 — Accesso non consentito</h2>'
       .'<p>Questa pagina è accessibile solo dalla rete locale.</p></div></body></html>');
}

// ── Autenticazione LAN ────────────────────────────────────────
$authFile  = __DIR__ . '/admin_auth.json';
$authData  = file_exists($authFile)
    ? (json_decode(file_get_contents($authFile), true) ?: [])
    : [];
$pwdHash   = $authData['password_hash'] ?? null;
$isDefault = ($pwdHash === null);          // true = mai impostata, usa "admin"
if ($isDefault) $pwdHash = password_hash('admin', PASSWORD_DEFAULT);

$authKey   = 'totem_cfg_' . substr(md5(__DIR__), 0, 8);
$authError = '';

// Logout
if (isset($_GET['logout'])) {
    unset($_SESSION[$authKey]);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Login POST
if (!$isLocalhost && isset($_POST['_lan_pwd'])) {
    if (password_verify(trim($_POST['_lan_pwd']), $pwdHash)) {
        $_SESSION[$authKey] = ['t' => time(), 'ip' => $remoteIP];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
    $authError = 'Password non corretta.';
    sleep(1); // anti-brute-force
}

// Verifica sessione (valida 8 ore, vincolata all'IP)
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
<title>Accesso — Configurazione Totem</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:Segoe UI,Arial,sans-serif;
     background:linear-gradient(160deg,#0d1b3e 0%,#1a3a6e 100%);
     min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:rgba(255,255,255,.07);backdrop-filter:blur(14px);
    border:1px solid rgba(100,160,255,.25);border-radius:16px;
    padding:44px 36px;width:100%;max-width:380px;color:#fff;text-align:center}
.login-box .lock{font-size:52px;margin-bottom:14px}
.login-box h1{font-size:20px;margin-bottom:6px}
.login-box p{font-size:13px;opacity:.6;margin-bottom:26px}
input[type=password]{width:100%;padding:12px 16px;
    border:1px solid rgba(255,255,255,.25);border-radius:8px;
    background:rgba(255,255,255,.1);color:#fff;font-size:15px;
    outline:none;margin-bottom:14px;letter-spacing:.06em}
input[type=password]:focus{border-color:#42a5f5}
.btn-login{width:100%;padding:12px;
    background:linear-gradient(135deg,#1565c0,#0d47a1);
    border:none;border-radius:8px;color:#fff;font-size:15px;
    font-weight:600;cursor:pointer;transition:.2s}
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
    <h1>Configurazione Totem</h1>
    <p>Accesso da rete LAN — inserire la password di amministrazione</p>
    <?php if ($isDefault): ?>
    <div class="warn">⚠️ Stai usando la password predefinita <strong>admin</strong>.<br>
        Accedi e cambiala subito nella sezione <em>🔐 Sicurezza</em>.</div>
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
// ── Fine blocco autenticazione ────────────────────────────────

$configFile = __DIR__ . '/totem_ui_config.json';
$message = ''; $msgType = '';

// Default
$defaults = [
    'nome_struttura'        => 'MySanitario',
	'idcliente'             => 'temp',
    'sottotitolo'           => 'Benvenuto — Seleziona il servizio desiderato',
    'logo_path'             => '',
    'sfondo_tipo'           => 'gradiente',
    'sfondo_gradiente_da'   => '#0d1b3e',
    'sfondo_gradiente_a'    => '#1a3a6e',
    'sfondo_colore'         => '#0d1b3e',
    'header_gradiente_da'   => '#0a1628',
    'header_gradiente_a'    => '#112244',
    'tasto_gradiente_da'    => '#1565c0',
    'tasto_gradiente_a'     => '#0d47a1',
    'tasto_hover_da'        => '#1976d2',
    'tasto_hover_a'         => '#1565c0',
    'tasto_bordo_colore'    => 'rgba(100,160,255,0.35)',
    'tasto_testo_colore'    => '#ffffff',
    'accento_colore'        => '#42a5f5',
    'header_testo_colore'   => '#ffffff',
    'footer_testo'          => 'Grazie per la vostra visita',
    'mostra_footer'         => true,
    'colonne_max'           => 3,
    'icone_abilitate'       => true,
    'animazioni_abilitate'  => true,
    // Comportamento pulsanti fuori orario operativo (totem)
    'pulsanti_stato_attivo'     => true,
    'pulsanti_modo_chiuso'      => 'evidenzia',   // evidenzia | disabilita | nascondi
    'pulsanti_mostra_messaggio' => true,
    'pulsanti_colore_chiuso'    => '#5b6573',
    'pulsanti_testo_chiuso'     => 'Chiuso',
    // TTS voce annunci monitor
    'tts_voce'              => 'ElsaNeural',
    'tts_velocita'          => 135,
    'tts_attivo'            => true,
];

$cfg = $defaults;
if (file_exists($configFile)) {
    $saved = json_decode(file_get_contents($configFile), true);
    if ($saved) $cfg = array_merge($defaults, $saved);
}

// ── Lista turni per simulatore ticket ────────────────────────
$turniList = [];
include_once __DIR__ . '/connect.php';
if (isset($conn) && $conn) {
    $r = mysqli_query($conn, 'SELECT ID_turno, turno FROM turni ORDER BY ID_turno');
    if ($r) { while ($row = mysqli_fetch_assoc($r)) { $turniList[] = $row; } }
}

// ── Stato aggiornamenti software ─────────────────────────────
$updateStatus = ['version'=>'—','commit'=>'','branch'=>'—','built_at'=>'','os'=>PHP_OS_FAMILY,'config_present'=>false];
try {
    require_once __DIR__ . '/update_lib.php';
    $updateStatus = updateLocalStatus();
} catch (Throwable $e) {
    $updateStatus['error'] = $e->getMessage();
}

// ── Temi predefiniti ──────────────────────────────────────────
$temi = [
    'blu' => [
        'label' => 'Blu Professionale',
        'sfondo_gradiente_da' => '#0d1b3e', 'sfondo_gradiente_a' => '#1a3a6e',
        'header_gradiente_da' => '#0a1628', 'header_gradiente_a' => '#112244',
        'tasto_gradiente_da'  => '#1565c0', 'tasto_gradiente_a'  => '#0d47a1',
        'tasto_hover_da'      => '#1976d2', 'tasto_hover_a'      => '#1565c0',
        'accento_colore'      => '#42a5f5', 'tasto_bordo_colore' => 'rgba(100,160,255,0.35)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
    'verde' => [
        'label' => 'Verde Salute',
        'sfondo_gradiente_da' => '#0a2e1b', 'sfondo_gradiente_a' => '#134d2a',
        'header_gradiente_da' => '#071e12', 'header_gradiente_a' => '#0d3a1e',
        'tasto_gradiente_da'  => '#1b5e20', 'tasto_gradiente_a'  => '#2e7d32',
        'tasto_hover_da'      => '#388e3c', 'tasto_hover_a'      => '#2e7d32',
        'accento_colore'      => '#66bb6a', 'tasto_bordo_colore' => 'rgba(80,200,120,0.35)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
    'viola' => [
        'label' => 'Viola Elegante',
        'sfondo_gradiente_da' => '#1a0533', 'sfondo_gradiente_a' => '#2d1052',
        'header_gradiente_da' => '#110222', 'header_gradiente_a' => '#1e0840',
        'tasto_gradiente_da'  => '#4a148c', 'tasto_gradiente_a'  => '#6a1b9a',
        'tasto_hover_da'      => '#7b1fa2', 'tasto_hover_a'      => '#6a1b9a',
        'accento_colore'      => '#ce93d8', 'tasto_bordo_colore' => 'rgba(180,100,255,0.35)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
    'rosso' => [
        'label' => 'Rosso Urgente',
        'sfondo_gradiente_da' => '#2d0a0a', 'sfondo_gradiente_a' => '#4a1010',
        'header_gradiente_da' => '#1a0505', 'header_gradiente_a' => '#350d0d',
        'tasto_gradiente_da'  => '#b71c1c', 'tasto_gradiente_a'  => '#c62828',
        'tasto_hover_da'      => '#d32f2f', 'tasto_hover_a'      => '#c62828',
        'accento_colore'      => '#ef9a9a', 'tasto_bordo_colore' => 'rgba(255,120,120,0.35)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
    'scuro' => [
        'label' => 'Scuro Premium',
        'sfondo_gradiente_da' => '#0a0a0a', 'sfondo_gradiente_a' => '#1a1a2e',
        'header_gradiente_da' => '#050505', 'header_gradiente_a' => '#0f0f1e',
        'tasto_gradiente_da'  => '#1e1e2e', 'tasto_gradiente_a'  => '#16213e',
        'tasto_hover_da'      => '#2a2a4a', 'tasto_hover_a'      => '#1e1e3e',
        'accento_colore'      => '#818cf8', 'tasto_bordo_colore' => 'rgba(130,140,255,0.3)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
    'chiaro' => [
        'label' => 'Chiaro Moderno',
        'sfondo_gradiente_da' => '#e8f4fd', 'sfondo_gradiente_a' => '#f0f8ff',
        'header_gradiente_da' => '#1565c0', 'header_gradiente_a' => '#0d47a1',
        'tasto_gradiente_da'  => '#1976d2', 'tasto_gradiente_a'  => '#1565c0',
        'tasto_hover_da'      => '#2196f3', 'tasto_hover_a'      => '#1976d2',
        'accento_colore'      => '#42a5f5', 'tasto_bordo_colore' => 'rgba(25,118,210,0.3)',
        'tasto_testo_colore'  => '#ffffff', 'header_testo_colore'=> '#ffffff',
    ],
];

// ── Salva configurazione ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $newCfg = [
        'nome_struttura'        => trim($_POST['nome_struttura']        ?? $defaults['nome_struttura']),
		'idcliente'             => trim($_POST['idcliente']             ?? $defaults['idcliente']),
        'sottotitolo'           => trim($_POST['sottotitolo']           ?? $defaults['sottotitolo']),
        'logo_path'             => $cfg['logo_path'], // gestito separatamente
        'sfondo_tipo'           => in_array($_POST['sfondo_tipo']??'', ['gradiente','solido']) ? $_POST['sfondo_tipo'] : 'gradiente',
        'sfondo_gradiente_da'   => trim($_POST['sfondo_gradiente_da']   ?? $defaults['sfondo_gradiente_da']),
        'sfondo_gradiente_a'    => trim($_POST['sfondo_gradiente_a']    ?? $defaults['sfondo_gradiente_a']),
        'sfondo_colore'         => trim($_POST['sfondo_colore']         ?? $defaults['sfondo_colore']),
        'header_gradiente_da'   => trim($_POST['header_gradiente_da']   ?? $defaults['header_gradiente_da']),
        'header_gradiente_a'    => trim($_POST['header_gradiente_a']    ?? $defaults['header_gradiente_a']),
        'tasto_gradiente_da'    => trim($_POST['tasto_gradiente_da']    ?? $defaults['tasto_gradiente_da']),
        'tasto_gradiente_a'     => trim($_POST['tasto_gradiente_a']     ?? $defaults['tasto_gradiente_a']),
        'tasto_hover_da'        => trim($_POST['tasto_hover_da']        ?? $defaults['tasto_hover_da']),
        'tasto_hover_a'         => trim($_POST['tasto_hover_a']         ?? $defaults['tasto_hover_a']),
        'tasto_bordo_colore'    => trim($_POST['tasto_bordo_colore']    ?? $defaults['tasto_bordo_colore']),
        'tasto_testo_colore'    => trim($_POST['tasto_testo_colore']    ?? $defaults['tasto_testo_colore']),
        'accento_colore'        => trim($_POST['accento_colore']        ?? $defaults['accento_colore']),
        'header_testo_colore'   => trim($_POST['header_testo_colore']   ?? $defaults['header_testo_colore']),
        'footer_testo'          => trim($_POST['footer_testo']          ?? $defaults['footer_testo']),
        'mostra_footer'         => isset($_POST['mostra_footer']),
        'colonne_max'           => max(1, min(6, intval($_POST['colonne_max'] ?? 3))),
        'icone_abilitate'       => isset($_POST['icone_abilitate']),
        'animazioni_abilitate'  => isset($_POST['animazioni_abilitate']),
        // Comportamento pulsanti fuori orario
        'pulsanti_stato_attivo'     => isset($_POST['pulsanti_stato_attivo']),
        'pulsanti_modo_chiuso'      => in_array($_POST['pulsanti_modo_chiuso'] ?? '', ['evidenzia','disabilita','nascondi'], true) ? $_POST['pulsanti_modo_chiuso'] : 'evidenzia',
        'pulsanti_mostra_messaggio' => isset($_POST['pulsanti_mostra_messaggio']),
        'pulsanti_colore_chiuso'    => trim($_POST['pulsanti_colore_chiuso'] ?? $defaults['pulsanti_colore_chiuso']),
        'pulsanti_testo_chiuso'     => trim($_POST['pulsanti_testo_chiuso'] ?? $defaults['pulsanti_testo_chiuso']),
        // TTS: preservati dal JSON (gestiti da config_monitor2.php)
        'tts_attivo'            => $cfg['tts_attivo'],
        'tts_voce'              => $cfg['tts_voce'],
        'tts_velocita'          => $cfg['tts_velocita'],
    ];

    // Upload logo
    if (!empty($_POST['remove_logo'])) {
        $lf = __DIR__ . '/' . ($cfg['logo_path'] ?? '');
        if ($cfg['logo_path'] && file_exists($lf) && $lf !== __DIR__ . '/') @unlink($lf);
        $newCfg['logo_path'] = '';
    }
    if (!empty($_FILES['logo_file']['tmp_name']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($fi, $_FILES['logo_file']['tmp_name']);
        finfo_close($fi);
        $allowed = ['image/jpeg','image/png','image/gif','image/bmp','image/webp'];
        if (in_array($mime, $allowed, true)) {
            switch($mime){
                case 'image/jpeg': $img=@imagecreatefromjpeg($_FILES['logo_file']['tmp_name']); break;
                case 'image/png':  $img=@imagecreatefrompng($_FILES['logo_file']['tmp_name']);  break;
                case 'image/gif':  $img=@imagecreatefromgif($_FILES['logo_file']['tmp_name']);  break;
                case 'image/bmp':  $img=@imagecreatefrombmp($_FILES['logo_file']['tmp_name']);  break;
                case 'image/webp': $img=@imagecreatefromwebp($_FILES['logo_file']['tmp_name']); break;
                default: $img=false;
            }
            if ($img) {
                $w=imagesx($img); $h=imagesy($img);
                // Crea canvas con supporto alpha (trasparenza preservata)
                $out=imagecreatetruecolor($w,$h);
                imagealphablending($out, false);
                imagesavealpha($out, true);
                $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
                imagefilledrectangle($out, 0, 0, $w, $h, $transparent);
                imagealphablending($out, true);
                imagecopy($out,$img,0,0,0,0,$w,$h);
                imagedestroy($img);
                $dest = __DIR__ . '/totem_logo.png';
                if (imagepng($out,$dest)) { $newCfg['logo_path']='totem_logo.png'; }
                imagedestroy($out);
            }
        }
    }

    if (file_put_contents($configFile, json_encode($newCfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        $cfg = $newCfg;
        $message = 'Configurazione salvata. Il totem verrà aggiornato al prossimo accesso.';
        $msgType = 'success';
    } else {
        $message = 'Impossibile scrivere il file di configurazione.';
        $msgType = 'error';
    }
}

$logoFile  = $cfg['logo_path'] !== '' ? __DIR__ . '/' . $cfg['logo_path'] : '';
$logoExist = $logoFile !== '' && file_exists($logoFile);

// ── Cambia password LAN (POST) ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_pwd'])) {
    $newPwd  = trim($_POST['new_password']     ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    if ($newPwd === '') {
        $message = 'La nuova password non può essere vuota.'; $msgType = 'error';
    } elseif (strlen($newPwd) < 6) {
        $message = 'La password deve essere di almeno 6 caratteri.'; $msgType = 'error';
    } elseif ($newPwd !== $confirm) {
        $message = 'Le due password non coincidono.'; $msgType = 'error';
    } else {
        $authData['password_hash'] = password_hash($newPwd, PASSWORD_DEFAULT);
        if (file_put_contents($authFile, json_encode($authData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $isDefault = false;
            $message   = '✅ Password aggiornata con successo.';
            $msgType   = 'success';
        } else {
            $message = 'Impossibile salvare la password (controlla i permessi della cartella).';
            $msgType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configurazione Grafica Totem</title>
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #333; min-height: 100vh; }
.page { display: flex; gap: 0; min-height: 100vh; }

/* SIDEBAR */
.sidebar {
    width: 260px; flex: 0 0 260px;
    background: linear-gradient(160deg, #0d1b3e 0%, #1a3a6e 100%);
    color: #fff; padding: 28px 20px;
    display: flex; flex-direction: column; gap: 8px;
}
.sidebar h2 { font-size: 15px; font-weight: 800; letter-spacing: .05em;
    margin-bottom: 16px; color: #42a5f5; text-transform: uppercase; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border-radius: 8px; color: rgba(255,255,255,.75); text-decoration: none; font-size: 14px;
    transition: background .15s, color .15s; }
.sidebar a:hover { background: rgba(255,255,255,.1); color: #fff; }
.sidebar a.active { background: rgba(66,165,245,.25); color: #42a5f5; font-weight: 700; }
.sidebar-sep { height: 1px; background: rgba(255,255,255,.1); margin: 8px 0; }
.sidebar-logo { font-size: 28px; font-weight: 900; color: #42a5f5;
    margin-bottom: 24px; display: flex; align-items: center; gap: 10px; }

/* MAIN */
.main { flex: 1 1 auto; display: flex; flex-direction: column; }
.topbar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 16px 32px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 1px 6px rgba(0,0,0,.07); }
.topbar h1 { font-size: 20px; font-weight: 700; color: #1a1a2e; }
.topbar-btns { display: flex; gap: 10px; }

.content { padding: 28px 32px; flex: 1 1 auto; }

/* ALERT */
.alert { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-weight: 600; font-size: 14px; }
.alert-success { background: #e6f4ea; color: #2d6a4f; border: 1px solid #b7dfca; }
.alert-error   { background: #fce8e6; color: #c0392b; border: 1px solid #f5c6c2; }

/* GRID */
.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media(max-width:1100px){ .grid-2 { grid-template-columns: 1fr; } }

/* CARD */
.card { background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07); margin-bottom: 24px; }
.card-title { font-size: 14px; font-weight: 800; color: #1a3a6e; text-transform: uppercase;
    letter-spacing: .06em; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
    border-bottom: 2px solid #e8f0fe; padding-bottom: 10px; }
.card-title span { font-size: 18px; }

/* INPUTS */
label.lbl { display: block; font-size: 12px; font-weight: 700; color: #555;
    margin-bottom: 5px; margin-top: 14px; text-transform: uppercase; letter-spacing: .04em; }
label.lbl:first-child { margin-top: 0; }
input[type=text], input[type=number], select, textarea {
    width: 100%; padding: 9px 12px; border: 1.5px solid #dde3ec;
    border-radius: 8px; font-size: 14px; font-family: inherit;
    background: #fafbfc; color: #333; transition: border .15s; }
input[type=text]:focus, input[type=number]:focus, select:focus, textarea:focus {
    outline: none; border-color: #1a73e8; background: #fff; }
textarea { resize: vertical; min-height: 60px; }

/* COLOR PICKER */
.color-row { display: flex; align-items: center; gap: 8px; }
.color-row input[type=color] {
    width: 42px; height: 36px; padding: 2px; border: 1.5px solid #dde3ec;
    border-radius: 8px; cursor: pointer; background: #fff; flex: 0 0 42px; }
.color-row input[type=text] { flex: 1 1 auto; font-family: monospace; }

/* TOGGLE */
.toggle-row { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.toggle-row input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; accent-color: #1a73e8; }
.toggle-row label { font-size: 14px; color: #444; cursor: pointer; }

/* RADIO SFONDO */
.radio-group { display: flex; gap: 12px; margin-top: 6px; }
.radio-card { flex: 1; border: 2px solid #dde3ec; border-radius: 10px; padding: 12px;
    cursor: pointer; text-align: center; transition: border-color .15s, background .15s; }
.radio-card:has(input:checked) { border-color: #1a73e8; background: #e8f0fe; }
.radio-card input { display: none; }
.radio-card .rc-icon { font-size: 24px; margin-bottom: 4px; }
.radio-card .rc-label { font-size: 13px; font-weight: 700; color: #444; }

/* TEMI */
.temi-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; margin-top: 6px; }
.tema-card {
    border: 2px solid transparent; border-radius: 12px; padding: 10px 8px;
    cursor: pointer; text-align: center; transition: transform .15s, box-shadow .15s;
    overflow: hidden; position: relative;
}
.tema-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.15); }
.tema-card input { display: none; }
.tema-preview {
    height: 44px; border-radius: 8px; margin-bottom: 8px;
    display: flex; align-items: center; justify-content: center; gap: 4px;
}
.tema-btn-preview {
    width: 28px; height: 18px; border-radius: 4px;
}
.tema-label { font-size: 11px; font-weight: 700; color: #444; }
.tema-card:has(input:checked) { border-color: #1a73e8; box-shadow: 0 0 0 3px rgba(26,115,232,.2); }

/* LOGO */
.logo-preview-wrap { display: flex; align-items: center; gap: 16px; padding: 12px;
    background: #f8f9fa; border-radius: 10px; border: 1px solid #e0e0e0; margin-bottom: 12px; }
.logo-preview-wrap img { max-height: 70px; max-width: 200px; object-fit: contain;
    background: #fff; border: 1px solid #ccc; border-radius: 6px; padding: 4px; }
input[type=file] { width: 100%; padding: 7px; border: 2px dashed #bbb;
    border-radius: 8px; font-size: 13px; background: #fafafa; margin-bottom: 4px; }
.note { font-size: 11px; color: #999; margin-top: 4px; }

/* ANTEPRIMA COLORI */
.preview-mini {
    border-radius: 10px; overflow: hidden; margin-top: 12px;
    box-shadow: 0 2px 12px rgba(0,0,0,.15);
}
.prev-header { padding: 10px 14px; font-weight: 700; font-size: 13px; }
.prev-body { padding: 14px; display: flex; gap: 8px; flex-wrap: wrap; }
.prev-btn { border-radius: 8px; padding: 8px 14px; font-size: 12px; font-weight: 700;
    color: #fff; border: none; cursor: default; }
.prev-accent-bar { height: 3px; }

/* BUTTONS */
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 10px 22px;
    border-radius: 8px; font-size: 14px; font-weight: 700; cursor: pointer; border: none;
    text-decoration: none; transition: background .15s, transform .1s; }
.btn:active { transform: scale(.97); }
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover { background: #1558b0; }
.btn-ghost { background: transparent; color: #1a73e8; border: 2px solid #1a73e8; }
.btn-ghost:hover { background: #e8f0fe; }
.btn-danger { background: #ea4335; color: #fff; }
.btn-danger:hover { background: #c5221f; }
.btn-green { background: #34a853; color: #fff; }
.btn-green:hover { background: #2a8a42; }
.btn:disabled, .btn[disabled] { opacity: .45; cursor: not-allowed; transform: none !important; }
.update-row { display:flex; flex-wrap:wrap; gap:10px; align-items:center; margin-top:12px; }
.update-meta { font-size:13px; color:#555; line-height:1.6; }
.update-meta code { background:#f1f3f4; padding:1px 6px; border-radius:4px; font-size:12px; }
#update-result { display:none; margin-top:14px; padding:12px 16px; border-radius:8px; font-size:13px; border:1px solid transparent; white-space:pre-wrap; }

/* COLONNE SLIDER */
.col-preview { display: flex; gap: 6px; margin-top: 8px; }
.col-box { height: 22px; border-radius: 4px; background: #1a73e8; flex: 1; opacity: .7; }
</style>
</head>
<body>
<div class="page">

<!-- SIDEBAR -->
<nav class="sidebar">
    <div class="sidebar-logo">&#9881;&#65039; <span style="font-size:18px">Config</span></div>
    <h2>Totem</h2>
    <a href="amministrazione.php" class="active">&#127912; Grafica Totem</a>
    <a href="config_stampante.php">&#128424; Stampante</a>
    <a href="config_sportelli.php">&#128251; Sportelli / Click</a>
    <div class="sidebar-sep"></div>
    <h2>Monitor</h2>
    <a href="config_monitor2.php">&#128250; Monitor Coda</a>
    <div class="sidebar-sep"></div>
    <a href="totem.php" target="_blank">&#128065; Anteprima Totem</a>
    <a href="index2.php" target="_blank">&#128065; Anteprima Monitor</a>
    <a href="stampa_errori_view.php" target="_blank">&#128203; Log Stampa</a>
    <?php if (!$isLocalhost): ?>
    <div class="sidebar-sep"></div>
    <a href="?logout=1">&#128275; Esci</a>
    <?php endif; ?>
</nav>

<!-- MAIN -->
<div class="main">
<div class="topbar">
    <h1>🎨 Grafica &amp; Personalizzazione Totem</h1>
    <div class="topbar-btns">
        <a href="totem.php" target="_blank" class="btn btn-ghost">👁️ Anteprima</a>
        <button type="submit" name="save" form="mainForm" class="btn btn-primary">💾 Salva Configurazione</button>
    </div>
</div>

<div class="content">
<?php if ($message): ?>
<div class="alert alert-<?php echo $msgType; ?>"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<form id="mainForm" method="POST" enctype="multipart/form-data">

<div class="grid-2">

<!-- COLONNA SINISTRA -->
<div>

<!-- IDENTITÀ -->
<div class="card">
    <div class="card-title"><span>🏥</span> Identità Struttura</div>

    <label class="lbl" for="nome_struttura">Nome Struttura</label>
    <input type="text" id="nome_struttura" name="nome_struttura"
           value="<?php echo htmlspecialchars($cfg['nome_struttura']); ?>"
           placeholder="Es: Poliambulatorio Centrale">

    <label class="lbl" for="idcliente">ID Monitor</label>
    <input type="text" id="idcliente" name="idcliente"
           value="<?php echo htmlspecialchars($cfg['idcliente']); ?>"
           placeholder="Es: M-12345">

    <label class="lbl" for="sottotitolo">Sottotitolo / Messaggio benvenuto</label>
    <input type="text" id="sottotitolo" name="sottotitolo"
           value="<?php echo htmlspecialchars($cfg['sottotitolo']); ?>"
           placeholder="Benvenuto — Seleziona il servizio desiderato">

    <label class="lbl" for="footer_testo">Testo footer</label>
    <input type="text" id="footer_testo" name="footer_testo"
           value="<?php echo htmlspecialchars($cfg['footer_testo']); ?>"
           placeholder="Grazie per la vostra visita">

    <div class="toggle-row">
        <input type="checkbox" id="mostra_footer" name="mostra_footer" value="1"
               <?php echo $cfg['mostra_footer'] ? 'checked' : ''; ?>>
        <label for="mostra_footer">Mostra footer</label>
    </div>
</div>

<!-- LOGO -->
<div class="card">
    <div class="card-title"><span>🖼️</span> Logo Header</div>

    <?php if ($logoExist): ?>
    <div class="logo-preview-wrap">
        <img src="<?php echo htmlspecialchars($cfg['logo_path']); ?>?v=<?php echo filemtime($logoFile); ?>"
             alt="Logo attuale">
        <div style="font-size:12px;color:#666">
            <strong>Logo attuale</strong><br>
            <?php list($lw,$lh)=getimagesize($logoFile); echo $lw.'×'.$lh.' px – '.round(filesize($logoFile)/1024,1).' KB'; ?>
        </div>
    </div>
    <div class="toggle-row" style="margin-bottom:12px">
        <input type="checkbox" id="remove_logo" name="remove_logo" value="1">
        <label for="remove_logo" style="color:#ea4335">Rimuovi logo (verrà usata la lettera iniziale)</label>
    </div>
    <?php else: ?>
    <p class="note" style="margin-bottom:12px;font-size:13px;color:#888">
        Nessun logo caricato. Se non carichi un logo verrà mostrata la lettera iniziale del nome struttura.
    </p>
    <?php endif; ?>

    <label class="lbl" for="logo_file">Carica logo (JPG, PNG, GIF, WebP)</label>
    <input type="file" id="logo_file" name="logo_file"
           accept="image/jpeg,image/png,image/gif,image/bmp,image/webp">
    <p class="note">Consigliato: sfondo trasparente o bianco, formato orizzontale, altezza min 80 px.</p>
</div>

<!-- LAYOUT BOTTONI -->
<div class="card">
    <div class="card-title"><span>⊞</span> Layout Pulsanti</div>

    <label class="lbl" for="colonne_max">Numero massimo di colonne</label>
    <input type="number" id="colonne_max" name="colonne_max"
           value="<?php echo intval($cfg['colonne_max']); ?>"
           min="1" max="6" oninput="aggiornaColPreview(this.value)">
    <div class="col-preview" id="colPreview">
        <?php for($i=0;$i<intval($cfg['colonne_max']);$i++): ?><div class="col-box"></div><?php endfor; ?>
    </div>
    <p class="note">Le colonne si adattano automaticamente al numero di turni e alla dimensione dello schermo.</p>

    <div class="toggle-row">
        <input type="checkbox" id="icone_abilitate" name="icone_abilitate" value="1"
               <?php echo $cfg['icone_abilitate'] ? 'checked' : ''; ?>>
        <label for="icone_abilitate">Mostra icone emoji sui pulsanti</label>
    </div>
    <div class="toggle-row">
        <input type="checkbox" id="animazioni_abilitate" name="animazioni_abilitate" value="1"
               <?php echo $cfg['animazioni_abilitate'] ? 'checked' : ''; ?>>
        <label for="animazioni_abilitate">Animazioni ingresso (consigliato)</label>
    </div>
</div>

<!-- COMPORTAMENTO PULSANTI -->
<div class="card">
    <div class="card-title"><span>⏰</span> Comportamento Pulsanti (fuori orario)</div>
    <p style="font-size:13px;color:#888;margin-bottom:6px">
        Definisce come si comportano i pulsanti del totem quando l'orario operativo del turno
        è terminato o non è ancora iniziato.
    </p>

    <div class="toggle-row">
        <input type="checkbox" id="pulsanti_stato_attivo" name="pulsanti_stato_attivo" value="1"
               <?php echo $cfg['pulsanti_stato_attivo'] ? 'checked' : ''; ?>>
        <label for="pulsanti_stato_attivo">Cambia aspetto dei pulsanti fuori orario</label>
    </div>

    <label class="lbl" for="pulsanti_modo_chiuso">Aspetto quando il servizio è chiuso</label>
    <select id="pulsanti_modo_chiuso" name="pulsanti_modo_chiuso">
        <option value="evidenzia"  <?php echo $cfg['pulsanti_modo_chiuso']==='evidenzia'  ? 'selected' : ''; ?>>Evidenzia come chiuso (resta cliccabile)</option>
        <option value="disabilita" <?php echo $cfg['pulsanti_modo_chiuso']==='disabilita' ? 'selected' : ''; ?>>Disabilita (non cliccabile)</option>
        <option value="nascondi"   <?php echo $cfg['pulsanti_modo_chiuso']==='nascondi'   ? 'selected' : ''; ?>>Nascondi il pulsante</option>
    </select>

    <label class="lbl">Colore pulsante chiuso</label>
    <div class="color-row">
        <input type="color" id="pulsanti_colore_chiuso_picker"
               value="<?php echo htmlspecialchars($cfg['pulsanti_colore_chiuso']); ?>"
               oninput="document.getElementById('pulsanti_colore_chiuso').value=this.value">
        <input type="text" id="pulsanti_colore_chiuso" name="pulsanti_colore_chiuso"
               value="<?php echo htmlspecialchars($cfg['pulsanti_colore_chiuso']); ?>"
               oninput="document.getElementById('pulsanti_colore_chiuso_picker').value=this.value">
    </div>

    <label class="lbl" for="pulsanti_testo_chiuso">Etichetta stato chiuso</label>
    <input type="text" id="pulsanti_testo_chiuso" name="pulsanti_testo_chiuso"
           value="<?php echo htmlspecialchars($cfg['pulsanti_testo_chiuso']); ?>"
           placeholder="Es: Chiuso">

    <div class="toggle-row">
        <input type="checkbox" id="pulsanti_mostra_messaggio" name="pulsanti_mostra_messaggio" value="1"
               <?php echo $cfg['pulsanti_mostra_messaggio'] ? 'checked' : ''; ?>>
        <label for="pulsanti_mostra_messaggio">Mostra il messaggio quando il servizio non è disponibile</label>
    </div>
    <p class="note">Se disattivato, il pulsante cambia solo aspetto senza mostrare orari o avvisi di indisponibilità (né sul pulsante né al tocco).</p>
</div>

</div><!-- fine colonna sinistra -->

<!-- COLONNA DESTRA -->
<div>

<!-- TEMI RAPIDI -->
<div class="card">
    <div class="card-title"><span>🎨</span> Tema Rapido</div>
    <p style="font-size:13px;color:#888;margin-bottom:12px">Clicca per applicare un tema predefinito. Puoi personalizzarlo dopo.</p>
    <div class="temi-grid">
    <?php foreach($temi as $chiave => $tema): ?>
    <label class="tema-card">
        <input type="radio" name="tema_rapido" value="<?php echo $chiave; ?>"
               onclick="applicaTema('<?php echo $chiave; ?>')">
        <div class="tema-preview" style="background:linear-gradient(135deg,<?php echo $tema['sfondo_gradiente_da']; ?>,<?php echo $tema['sfondo_gradiente_a']; ?>)">
            <div class="tema-btn-preview" style="background:linear-gradient(145deg,<?php echo $tema['tasto_gradiente_da']; ?>,<?php echo $tema['tasto_gradiente_a']; ?>);border-radius:4px"></div>
            <div class="tema-btn-preview" style="background:linear-gradient(145deg,<?php echo $tema['tasto_gradiente_da']; ?>,<?php echo $tema['tasto_gradiente_a']; ?>);border-radius:4px"></div>
        </div>
        <div class="tema-label"><?php echo $tema['label']; ?></div>
    </label>
    <?php endforeach; ?>
    </div>
</div>

<!-- SFONDO -->
<div class="card">
    <div class="card-title"><span>🖼️</span> Sfondo Pagina</div>
    <label class="lbl">Tipo di sfondo</label>
    <div class="radio-group">
        <label class="radio-card">
            <input type="radio" name="sfondo_tipo" value="gradiente"
                   <?php echo $cfg['sfondo_tipo']==='gradiente'?'checked':''; ?>>
            <div class="rc-icon">🌅</div>
            <div class="rc-label">Gradiente</div>
        </label>
        <label class="radio-card">
            <input type="radio" name="sfondo_tipo" value="solido"
                   <?php echo $cfg['sfondo_tipo']==='solido'?'checked':''; ?>>
            <div class="rc-icon">🟦</div>
            <div class="rc-label">Colore solido</div>
        </label>
    </div>

    <label class="lbl">Colore sfondo (da)</label>
    <div class="color-row">
        <input type="color" id="sfondo_da_picker" value="<?php echo $cfg['sfondo_gradiente_da']; ?>"
               oninput="document.getElementById('sfondo_gradiente_da').value=this.value;aggiornaPreview()">
        <input type="text" id="sfondo_gradiente_da" name="sfondo_gradiente_da"
               value="<?php echo htmlspecialchars($cfg['sfondo_gradiente_da']); ?>"
               oninput="document.getElementById('sfondo_da_picker').value=this.value;aggiornaPreview()">
    </div>

    <label class="lbl">Colore sfondo (a) / Colore solido</label>
    <div class="color-row">
        <input type="color" id="sfondo_a_picker" value="<?php echo $cfg['sfondo_gradiente_a']; ?>"
               oninput="document.getElementById('sfondo_gradiente_a').value=this.value;document.getElementById('sfondo_colore').value=this.value;aggiornaPreview()">
        <input type="text" id="sfondo_gradiente_a" name="sfondo_gradiente_a"
               value="<?php echo htmlspecialchars($cfg['sfondo_gradiente_a']); ?>"
               oninput="document.getElementById('sfondo_a_picker').value=this.value;aggiornaPreview()">
        <input type="hidden" id="sfondo_colore" name="sfondo_colore"
               value="<?php echo htmlspecialchars($cfg['sfondo_colore']); ?>">
    </div>
</div>

<!-- HEADER -->
<div class="card">
    <div class="card-title"><span>📌</span> Barra Header</div>
    <label class="lbl">Gradiente header (da)</label>
    <div class="color-row">
        <input type="color" id="hda_picker" value="<?php echo $cfg['header_gradiente_da']; ?>"
               oninput="document.getElementById('header_gradiente_da').value=this.value;aggiornaPreview()">
        <input type="text" id="header_gradiente_da" name="header_gradiente_da"
               value="<?php echo htmlspecialchars($cfg['header_gradiente_da']); ?>"
               oninput="document.getElementById('hda_picker').value=this.value;aggiornaPreview()">
    </div>
    <label class="lbl">Gradiente header (a)</label>
    <div class="color-row">
        <input type="color" id="ha_picker" value="<?php echo $cfg['header_gradiente_a']; ?>"
               oninput="document.getElementById('header_gradiente_a').value=this.value;aggiornaPreview()">
        <input type="text" id="header_gradiente_a" name="header_gradiente_a"
               value="<?php echo htmlspecialchars($cfg['header_gradiente_a']); ?>"
               oninput="document.getElementById('ha_picker').value=this.value;aggiornaPreview()">
    </div>
    <label class="lbl">Colore testo header</label>
    <div class="color-row">
        <input type="color" id="ht_picker" value="<?php echo $cfg['header_testo_colore']; ?>"
               oninput="document.getElementById('header_testo_colore').value=this.value;aggiornaPreview()">
        <input type="text" id="header_testo_colore" name="header_testo_colore"
               value="<?php echo htmlspecialchars($cfg['header_testo_colore']); ?>"
               oninput="document.getElementById('ht_picker').value=this.value;aggiornaPreview()">
    </div>
    <label class="lbl">Colore accento (clock, sottotitolo, barra)</label>
    <div class="color-row">
        <input type="color" id="ac_picker" value="<?php echo $cfg['accento_colore']; ?>"
               oninput="document.getElementById('accento_colore').value=this.value;aggiornaPreview()">
        <input type="text" id="accento_colore" name="accento_colore"
               value="<?php echo htmlspecialchars($cfg['accento_colore']); ?>"
               oninput="document.getElementById('ac_picker').value=this.value;aggiornaPreview()">
    </div>
</div>

<!-- PULSANTI -->
<div class="card">
    <div class="card-title"><span>🔲</span> Pulsanti Servizio</div>
    <label class="lbl">Gradiente pulsante (da)</label>
    <div class="color-row">
        <input type="color" id="tda_picker" value="<?php echo $cfg['tasto_gradiente_da']; ?>"
               oninput="document.getElementById('tasto_gradiente_da').value=this.value;aggiornaPreview()">
        <input type="text" id="tasto_gradiente_da" name="tasto_gradiente_da"
               value="<?php echo htmlspecialchars($cfg['tasto_gradiente_da']); ?>"
               oninput="document.getElementById('tda_picker').value=this.value;aggiornaPreview()">
    </div>
    <label class="lbl">Gradiente pulsante (a)</label>
    <div class="color-row">
        <input type="color" id="ta_picker" value="<?php echo $cfg['tasto_gradiente_a']; ?>"
               oninput="document.getElementById('tasto_gradiente_a').value=this.value;aggiornaPreview()">
        <input type="text" id="tasto_gradiente_a" name="tasto_gradiente_a"
               value="<?php echo htmlspecialchars($cfg['tasto_gradiente_a']); ?>"
               oninput="document.getElementById('ta_picker').value=this.value;aggiornaPreview()">
    </div>
    <label class="lbl">Colore testo pulsante</label>
    <div class="color-row">
        <input type="color" id="tt_picker" value="<?php echo $cfg['tasto_testo_colore']; ?>"
               oninput="document.getElementById('tasto_testo_colore').value=this.value;aggiornaPreview()">
        <input type="text" id="tasto_testo_colore" name="tasto_testo_colore"
               value="<?php echo htmlspecialchars($cfg['tasto_testo_colore']); ?>"
               oninput="document.getElementById('tt_picker').value=this.value;aggiornaPreview()">
    </div>

    <!-- Anteprima live -->
    <div class="preview-mini" id="previewMini">
        <div class="prev-header" id="prevHeader"
             style="background:linear-gradient(135deg,<?php echo $cfg['header_gradiente_da']; ?>,<?php echo $cfg['header_gradiente_a']; ?>);color:<?php echo $cfg['header_testo_colore']; ?>">
            🏥 <?php echo htmlspecialchars($cfg['nome_struttura']); ?> &nbsp;&nbsp;
            <span style="color:<?php echo $cfg['accento_colore']; ?>">⏰ 09:45</span>
        </div>
        <div class="prev-accent-bar" id="prevAccent"
             style="background:linear-gradient(90deg,transparent,<?php echo $cfg['accento_colore']; ?>,transparent)"></div>
        <div class="prev-body" id="prevBody"
             style="background:linear-gradient(160deg,<?php echo $cfg['sfondo_gradiente_da']; ?>,<?php echo $cfg['sfondo_gradiente_a']; ?>)">
            <div class="prev-btn" id="prevBtn1"
                 style="background:linear-gradient(145deg,<?php echo $cfg['tasto_gradiente_da']; ?>,<?php echo $cfg['tasto_gradiente_a']; ?>);color:<?php echo $cfg['tasto_testo_colore']; ?>">
                🏷️ Turno
            </div>
            <div class="prev-btn" id="prevBtn2"
                 style="background:linear-gradient(145deg,<?php echo $cfg['tasto_gradiente_da']; ?>,<?php echo $cfg['tasto_gradiente_a']; ?>);color:<?php echo $cfg['tasto_testo_colore']; ?>">
                📋 Accettazione
            </div>
        </div>
    </div>
</div>

</div><!-- fine colonna destra -->
</div><!-- fine grid-2 -->

<!-- ── SICUREZZA ──────────────────────────────────────────────────────── -->
<div class="card" style="margin-top:24px">
    <div class="card-title"><span>🔐</span> Sicurezza — Accesso LAN</div>
    <?php if ($isLocalhost): ?>
    <p style="font-size:13px;color:#888;margin-bottom:14px">
        Sei connesso da <strong>localhost</strong>: l'accesso diretto non richiede password.
        Imposta qui la password per gli accessi dalla rete LAN.
    </p>
    <?php else: ?>
    <p style="font-size:13px;color:#888;margin-bottom:14px">
        Sei connesso da rete LAN (<code><?php echo htmlspecialchars($remoteIP); ?></code>).
        La sessione è valida per 8 ore.
        <a href="?logout" style="color:#ea4335;margin-left:10px">🚪 Disconnetti</a>
    </p>
    <?php endif; ?>
    <?php if ($isDefault): ?>
    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:6px;padding:10px 14px;
                font-size:12px;color:#664d03;margin-bottom:16px">
        ⚠️ Stai usando la <strong>password predefinita "admin"</strong>. Cambiala subito.
    </div>
    <?php endif; ?>
    <form method="POST" style="max-width:420px">
        <label class="lbl" for="new_password">Nuova password LAN (min. 6 caratteri)</label>
        <input type="password" id="new_password" name="new_password"
               class="inp" placeholder="Nuova password" autocomplete="new-password">
        <label class="lbl" for="confirm_password">Conferma password</label>
        <input type="password" id="confirm_password" name="confirm_password"
               class="inp" placeholder="Ripeti la password" autocomplete="new-password">
        <button type="submit" name="change_pwd" class="btn btn-primary" style="margin-top:4px">
            🔑 Aggiorna Password
        </button>
    </form>
</div>


<div style="text-align:right;margin-top:8px">
    <button type="submit" name="save" class="btn btn-primary" style="font-size:16px;padding:14px 36px">
        💾 Salva Configurazione
    </button>
</div>

</form>

<!-- ── AGGIORNAMENTI SOFTWARE ───────────────────────────────────────── -->
<div class="card" style="margin-top:8px;border-left:4px solid #1a73e8">
    <div class="card-title"><span>🔄</span> Aggiornamenti software</div>
    <p style="font-size:13px;color:#888;margin-bottom:10px">
        Confronta la versione installata con il branch GitHub della piattaforma
        (<code>windows-iis</code> / <code>linux</code>) e applica gli aggiornamenti con backup automatico.
        Configura <code>update_config.json</code> (vedi <code>UPDATE_SETUP.md</code>).
    </p>
    <div class="update-meta" id="update-meta">
        <div>Versione locale: <strong id="upd-version"><?php echo htmlspecialchars($updateStatus['version'] ?? '—'); ?></strong></div>
        <div>Branch: <code id="upd-branch"><?php echo htmlspecialchars($updateStatus['branch'] ?? '—'); ?></code>
            &nbsp;·&nbsp; OS: <code><?php echo htmlspecialchars($updateStatus['os'] ?? PHP_OS_FAMILY); ?></code></div>
        <div>Commit: <code id="upd-commit"><?php echo htmlspecialchars(($updateStatus['commit'] ?? '') !== '' ? substr($updateStatus['commit'],0,12) : '—'); ?></code></div>
        <div>Config GitHub: <?php echo !empty($updateStatus['config_present']) ? '<span style="color:#2e7d32">presente</span>' : '<span style="color:#c62828">mancante (copia update_config.example.json)</span>'; ?></div>
    </div>
    <div class="update-row">
        <button type="button" class="btn btn-primary" id="btn-update-check">🔍 Verifica</button>
        <button type="button" class="btn btn-green" id="btn-update-apply" disabled>⬇ Aggiorna</button>
    </div>
    <div id="update-result"></div>
</div>

<!-- ── SIMULATORE TICKET ─────────────────────────────────────────────── -->
<div class="card" style="margin-top:8px;border-left:4px solid #ff9800">
    <div class="card-title"><span>🧪</span> Simulatore Ticket &mdash; Test</div>
    <p style="font-size:13px;color:#888;margin-bottom:16px">
        Emette un ticket reale nella coda <strong>bypassando blocchi orari e senza stampare</strong>.
        Utile per testare il monitor, le chiamate agli sportelli e il TTS.
    </p>
    <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <?php foreach($turniList as $t): ?>
        <button type="button"
                style="background:linear-gradient(135deg,#e65100,#ff9800);color:#fff;border:none;border-radius:8px;padding:10px 22px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 2px 6px rgba(0,0,0,.15)"
                onclick="simTicket(<?php echo intval($t['ID_turno']); ?>, '<?php echo htmlspecialchars(addslashes($t['turno'])); ?>')">
            🎫 <?php echo htmlspecialchars($t['turno']); ?>
        </button>
        <?php endforeach; ?>
        <?php if(empty($turniList)): ?>
        <p style="color:#aaa;font-style:italic">Nessun turno trovato nel database.</p>
        <?php endif; ?>
    </div>
    <div id="sim-result" style="display:none;padding:12px 16px;border-radius:8px;font-size:14px;font-weight:600;border:1px solid transparent"></div>
</div>

</div><!-- /content -->
</div><!-- /main -->
</div><!-- /page -->

<!-- Dati temi per JS -->
<script>
var TEMI = <?php echo json_encode($temi, JSON_UNESCAPED_UNICODE); ?>;

function applicaTema(chiave) {
    var t = TEMI[chiave]; if(!t) return;
    function setField(id, val) {
        var el = document.getElementById(id);
        if (el) { el.value = val; }
        var picker = document.getElementById(id + '_picker') ||
                     document.getElementById(id.split('_').map((p,i)=>i===0?p[0]+p.slice(1):p[0].toUpperCase()+p.slice(1)).join('').replace('_','') + '_picker');
        // set color pickers by name mapping
    }
    // Sfondo
    document.getElementById('sfondo_gradiente_da').value = t.sfondo_gradiente_da;
    document.getElementById('sfondo_da_picker').value    = t.sfondo_gradiente_da;
    document.getElementById('sfondo_gradiente_a').value  = t.sfondo_gradiente_a;
    document.getElementById('sfondo_a_picker').value     = t.sfondo_gradiente_a;
    document.getElementById('sfondo_colore').value       = t.sfondo_gradiente_a;
    // Header
    document.getElementById('header_gradiente_da').value = t.header_gradiente_da;
    document.getElementById('hda_picker').value          = t.header_gradiente_da;
    document.getElementById('header_gradiente_a').value  = t.header_gradiente_a;
    document.getElementById('ha_picker').value           = t.header_gradiente_a;
    document.getElementById('header_testo_colore').value = t.header_testo_colore;
    document.getElementById('ht_picker').value           = t.header_testo_colore;
    document.getElementById('accento_colore').value      = t.accento_colore;
    document.getElementById('ac_picker').value           = t.accento_colore;
    // Tasti
    document.getElementById('tasto_gradiente_da').value  = t.tasto_gradiente_da;
    document.getElementById('tda_picker').value          = t.tasto_gradiente_da;
    document.getElementById('tasto_gradiente_a').value   = t.tasto_gradiente_a;
    document.getElementById('ta_picker').value           = t.tasto_gradiente_a;
    document.getElementById('tasto_testo_colore').value  = t.tasto_testo_colore;
    document.getElementById('tt_picker').value           = t.tasto_testo_colore;
    aggiornaPreview();
}

function aggiornaPreview() {
    var sda = document.getElementById('sfondo_gradiente_da').value;
    var sa  = document.getElementById('sfondo_gradiente_a').value;
    var hda = document.getElementById('header_gradiente_da').value;
    var ha  = document.getElementById('header_gradiente_a').value;
    var ht  = document.getElementById('header_testo_colore').value;
    var ac  = document.getElementById('accento_colore').value;
    var tda = document.getElementById('tasto_gradiente_da').value;
    var ta  = document.getElementById('tasto_gradiente_a').value;
    var tt  = document.getElementById('tasto_testo_colore').value;
    var nome = document.getElementById('nome_struttura').value || 'MySanitario';

    var ph = document.getElementById('prevHeader');
    ph.style.background = 'linear-gradient(135deg,' + hda + ',' + ha + ')';
    ph.style.color = ht;
    ph.innerHTML = '🏥 ' + nome + ' &nbsp;&nbsp;<span style="color:' + ac + '">⏰ 09:45</span>';

    document.getElementById('prevAccent').style.background =
        'linear-gradient(90deg,transparent,' + ac + ',transparent)';

    var pb = document.getElementById('prevBody');
    pb.style.background = 'linear-gradient(160deg,' + sda + ',' + sa + ')';

    ['prevBtn1','prevBtn2'].forEach(function(id){
        var el = document.getElementById(id);
        el.style.background = 'linear-gradient(145deg,' + tda + ',' + ta + ')';
        el.style.color = tt;
    });
}

function aggiornaColPreview(n) {
    n = Math.max(1, Math.min(6, parseInt(n)||1));
    var cp = document.getElementById('colPreview');
    cp.innerHTML = '';
    for(var i=0;i<n;i++) {
        var d = document.createElement('div');
        d.className = 'col-box';
        cp.appendChild(d);
    }
}

// Aggiorna anteprima al cambiamento del nome struttura
document.getElementById('nome_struttura').addEventListener('input', aggiornaPreview);

var _updRemoteSha = '';
function updShow(msg, kind) {
    var el = document.getElementById('update-result');
    el.style.display = 'block';
    if (kind === 'ok') {
        el.style.background = '#e8f5e9'; el.style.borderColor = '#66bb6a'; el.style.color = '#2e7d32';
    } else if (kind === 'warn') {
        el.style.background = '#fff8e1'; el.style.borderColor = '#ffc107'; el.style.color = '#7c5c00';
    } else if (kind === 'err') {
        el.style.background = '#fce8e6'; el.style.borderColor = '#ef9a9a'; el.style.color = '#c62828';
    } else {
        el.style.background = '#e8f0fe'; el.style.borderColor = '#90caf9'; el.style.color = '#0d47a1';
    }
    el.textContent = msg;
}
function updSetApplyEnabled(on) {
    document.getElementById('btn-update-apply').disabled = !on;
}
document.getElementById('btn-update-check').addEventListener('click', function() {
    updSetApplyEnabled(false);
    _updRemoteSha = '';
    updShow('Verifica in corso…', 'info');
    fetch('update_api.php?action=check&_=' + Date.now(), { credentials: 'same-origin' })
        .then(function(r){ return r.json().then(function(j){ return {status:r.status, j:j}; }); })
        .then(function(x) {
            var d = x.j;
            if (!d || d.ok === false) {
                updShow((d && d.error) ? d.error : ('Errore HTTP ' + x.status), 'err');
                return;
            }
            if (d.local) {
                document.getElementById('upd-version').textContent = d.local.version || '—';
                document.getElementById('upd-commit').textContent = d.local.commit ? d.local.commit.substring(0,12) : '—';
            }
            if (d.branch) document.getElementById('upd-branch').textContent = d.branch;
            if (d.update_available) {
                _updRemoteSha = (d.remote && d.remote.commit) ? d.remote.commit : '';
                updSetApplyEnabled(true);
                var rv = (d.remote && d.remote.version) ? d.remote.version : '';
                var rm = (d.remote && d.remote.message) ? d.remote.message : '';
                updShow(
                    'Aggiornamento disponibile.\n' +
                    'Remoto: ' + (_updRemoteSha ? _updRemoteSha.substring(0,12) : '—') +
                    (rv ? (' (v' + rv + ')') : '') +
                    (rm ? ('\n' + rm) : '') +
                    '\nPremi Aggiorna per scaricare (verrà creato un backup).',
                    'warn'
                );
            } else {
                updShow('Sistema aggiornato. Nessuna novità sul branch ' + (d.branch || '') + '.', 'ok');
            }
        })
        .catch(function(e) {
            updShow('Errore di rete: ' + e, 'err');
        });
});
document.getElementById('btn-update-apply').addEventListener('click', function() {
    if (!confirm('Applicare l\'aggiornamento?\n\nI file modificati verranno salvati in backups/ prima della sostituzione.\nI file di configurazione locali (connect.php, JSON, ecc.) non verranno sovrascritti.')) {
        return;
    }
    updSetApplyEnabled(false);
    document.getElementById('btn-update-check').disabled = true;
    updShow('Download e applicazione in corso… non chiudere la pagina.', 'info');
    var fd = new FormData();
    fd.append('action', 'apply');
    if (_updRemoteSha) fd.append('sha', _updRemoteSha);
    fetch('update_api.php', { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function(r){ return r.json().then(function(j){ return {status:r.status, j:j}; }); })
        .then(function(x) {
            document.getElementById('btn-update-check').disabled = false;
            var d = x.j;
            if (!d || !d.ok) {
                updShow((d && d.error) ? d.error : ('Errore HTTP ' + x.status), 'err');
                updSetApplyEnabled(true);
                return;
            }
            if (!d.applied) {
                updShow(d.message || 'Nessuna modifica applicata', 'ok');
                return;
            }
            if (d.version) {
                document.getElementById('upd-version').textContent = d.version.version || '—';
                document.getElementById('upd-commit').textContent = d.version.commit ? d.version.commit.substring(0,12) : '—';
                if (d.version.branch) document.getElementById('upd-branch').textContent = d.version.branch;
            }
            updShow(
                'Aggiornamento completato.\n' +
                'File aggiornati: ' + (d.files_updated || 0) +
                ' · Backup: ' + (d.backup_dir || '') +
                ' (' + (d.files_backed_up || 0) + ' file)',
                'ok'
            );
        })
        .catch(function(e) {
            document.getElementById('btn-update-check').disabled = false;
            updSetApplyEnabled(true);
            updShow('Errore di rete: ' + e, 'err');
        });
});

function simTicket(idturno, nometurno) {
    var res = document.getElementById('sim-result');
    res.style.display = 'block';
    res.style.background = '#fff8e1';
    res.style.borderColor = '#ffc107';
    res.style.color = '#7c5c00';
    res.textContent = '\u23F3 Emissione ticket per ' + nometurno + '...';
    var fd = new FormData();
    fd.append('idturno', idturno);
    fd.append('force', '1');
    fetch('prendinumero.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            if (data.success) {
                res.style.background = '#e8f5e9';
                res.style.borderColor = '#66bb6a';
                res.style.color = '#2e7d32';
                res.textContent = '\u2713 Turno ' + data.turno + ' \u2014 Numero ' + data.numero + ' inserito in coda';
            } else {
                res.style.background = '#fce8e6';
                res.style.borderColor = '#ef9a9a';
                res.style.color = '#c62828';
                res.textContent = '\u2717 ' + (data.error || 'Errore sconosciuto');
            }
        })
        .catch(function(e) {
            res.style.background = '#fce8e6';
            res.style.borderColor = '#ef9a9a';
            res.style.color = '#c62828';
            res.textContent = '\u2717 Errore di rete: ' + e.message;
        });
}
</script>
</body>
</html>
