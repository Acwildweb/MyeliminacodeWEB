<?php
/**
 * config_sportelli.php
 * Anteprima pagine sportello (click.php) e generatore scorciatoie Windows
 */
session_start();

// ── Accesso ────────────────────────────────────────────────────
$remoteIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($remoteIP, ['127.0.0.1', '::1'], true);
function _sptIsLanIP(string $ip): bool {
    return (bool)preg_match('/^(10\.|172\.(1[6-9]|2[0-9]|3[01])\.|192\.168\.|::1$)/i', $ip);
}
if (!$isLocalhost && !_sptIsLanIP($remoteIP)) {
    http_response_code(403);
    die('<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Accesso negato</title>
    <style>body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
    .b{text-align:center;padding:40px;background:rgba(255,255,255,.08);border-radius:12px}</style></head>
    <body><div class="b"><h2>403 — Accesso non consentito</h2><p>Solo rete locale.</p></div></body></html>');
}
$authFile  = __DIR__ . '/admin_auth.json';
$authData  = file_exists($authFile) ? (json_decode(file_get_contents($authFile), true) ?: []) : [];
$pwdHash   = $authData['password_hash'] ?? null;
$isDefault = ($pwdHash === null);
if ($isDefault) $pwdHash = password_hash('admin', PASSWORD_DEFAULT);
$authKey   = 'totem_cfg_' . substr(md5(__DIR__), 0, 8);
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
    ?><!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accesso — Sportelli</title>
<style>*{box-sizing:border-box;margin:0;padding:0}body{font-family:Segoe UI,Arial,sans-serif;background:linear-gradient(160deg,#0d1b3e 0%,#1a3a6e 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:rgba(255,255,255,.07);backdrop-filter:blur(14px);border:1px solid rgba(100,160,255,.25);border-radius:16px;padding:44px 36px;width:100%;max-width:380px;color:#fff;text-align:center}
.login-box .lock{font-size:52px;margin-bottom:14px}.login-box h1{font-size:20px;margin-bottom:6px}.login-box p{font-size:13px;opacity:.6;margin-bottom:26px}
input[type=password]{width:100%;padding:12px 16px;border:1px solid rgba(255,255,255,.25);border-radius:8px;background:rgba(255,255,255,.1);color:#fff;font-size:15px;outline:none;margin-bottom:14px}
input[type=password]:focus{border-color:#42a5f5}.btn-login{width:100%;padding:12px;background:linear-gradient(135deg,#1565c0,#0d47a1);border:none;border-radius:8px;color:#fff;font-size:15px;font-weight:600;cursor:pointer}
.err{background:rgba(220,53,69,.2);border:1px solid rgba(220,53,69,.5);border-radius:6px;padding:10px;font-size:13px;margin-bottom:14px;color:#f99}</style></head>
<body><div class="login-box"><div class="lock">🔒</div><h1>Gestione Sportelli</h1>
<p>Accesso da rete LAN</p>
<?php if ($authError !== ''): ?><div class="err">❌ <?php echo htmlspecialchars($authError); ?></div><?php endif; ?>
<form method="POST"><input type="password" name="_lan_pwd" placeholder="Password" autofocus>
<button type="submit" class="btn-login">🔓 Accedi</button></form></div></body></html>
    <?php exit;
}

// ── DB ─────────────────────────────────────────────────────────
include 'connect.php';

// ── URL di base — DINAMICO (si adatta al dominio/IP corrente) ──
$proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host    = $_SERVER['HTTP_HOST'];
$baseUrl = $proto . '://' . $host;

// ── Carica postazioni ──────────────────────────────────────────
$postazioni = [];
$rs = mysqli_query($conn, "SELECT ID_postazione, postazione, descrizione FROM postazioni ORDER BY CAST(postazione AS UNSIGNED), postazione");
while ($row = mysqli_fetch_assoc($rs)) $postazioni[] = $row;
mysqli_close($conn);
?><!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Gestione Sportelli</title>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; color: #333; min-height: 100vh; }
.page { display: flex; gap: 0; min-height: 100vh; }
/* SIDEBAR */
.sidebar { width: 260px; flex: 0 0 260px; background: linear-gradient(160deg, #0d1b3e 0%, #1a3a6e 100%);
    color: #fff; padding: 28px 20px; display: flex; flex-direction: column; gap: 8px; }
.sidebar h2 { font-size: 15px; font-weight: 800; letter-spacing: .05em; margin-bottom: 16px;
    color: #42a5f5; text-transform: uppercase; }
.sidebar a { display: flex; align-items: center; gap: 10px; padding: 10px 14px;
    border-radius: 8px; color: rgba(255,255,255,.75); text-decoration: none; font-size: 14px;
    transition: background .15s, color .15s; }
.sidebar a:hover { background: rgba(255,255,255,.1); color: #fff; }
.sidebar a.active { background: rgba(66,165,245,.25); color: #42a5f5; font-weight: 700; }
.sidebar-sep { height: 1px; background: rgba(255,255,255,.1); margin: 8px 0; }
.sidebar-logo { font-size: 28px; font-weight: 900; color: #42a5f5; margin-bottom: 24px;
    display: flex; align-items: center; gap: 10px; }
/* MAIN */
.main { flex: 1 1 auto; display: flex; flex-direction: column; }
.topbar { background: #fff; border-bottom: 1px solid #e0e0e0; padding: 16px 32px;
    display: flex; align-items: center; justify-content: space-between;
    box-shadow: 0 1px 6px rgba(0,0,0,.07); }
.topbar h1 { font-size: 20px; font-weight: 700; color: #1a1a2e; }
.content { padding: 28px 32px; flex: 1 1 auto; }
.card { background: #fff; border-radius: 12px; padding: 24px;
    box-shadow: 0 2px 12px rgba(0,0,0,.07); margin-bottom: 24px; }
.card-title { font-size: 14px; font-weight: 800; color: #1a3a6e; text-transform: uppercase;
    letter-spacing: .06em; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;
    border-bottom: 2px solid #e8f0fe; padding-bottom: 10px; }
.btn { display: inline-flex; align-items: center; gap: 6px; padding: 9px 18px;
    border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; border: none;
    text-decoration: none; transition: background .15s, transform .1s; }
.btn:active { transform: scale(.97); }
.btn-primary { background: #1a73e8; color: #fff; }
.btn-primary:hover { background: #1558b0; }
.btn-ghost { background: transparent; color: #1a73e8; border: 2px solid #1a73e8; }
.btn-ghost:hover { background: #e8f0fe; }
.btn-green { background: #34a853; color: #fff; }
.btn-green:hover { background: #2a8a42; }
.btn-orange { background: #f57c00; color: #fff; }
.btn-orange:hover { background: #e65100; }
.btn-sm { padding: 6px 14px; font-size: 12px; }
/* URL info bar */
.url-bar { background: #e8f0fe; border: 1px solid #c5d8fb; border-radius: 10px;
    padding: 14px 20px; margin-bottom: 28px; display: flex; align-items: center; gap: 14px;
    flex-wrap: wrap; }
.url-bar .url-label { font-size: 12px; font-weight: 700; color: #1a3a6e; text-transform: uppercase;
    letter-spacing: .06em; white-space: nowrap; }
.url-bar .url-val { font-family: monospace; font-size: 14px; color: #1a73e8; font-weight: 600; }
.url-bar .url-note { font-size: 11px; color: #666; flex: 1 1 100%; margin-top: 4px; }
/* Grid sportelli */
.sportelli-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px; }
/* Card sportello */
.sp-card { background: #fff; border-radius: 14px; box-shadow: 0 3px 16px rgba(0,0,0,.09);
    overflow: hidden; border: 1px solid #e5eaf2; }
.sp-header { background: linear-gradient(135deg, #0d1b3e 0%, #1a3a6e 100%);
    color: #fff; padding: 14px 18px; display: flex; align-items: center; gap: 10px; }
.sp-header .sp-icon { font-size: 20px; }
.sp-header .sp-name { font-size: 15px; font-weight: 800; flex: 1; }
.sp-header .sp-desc { font-size: 11px; opacity: .6; }
.sp-preview { background: #0d1b3e; position: relative; overflow: hidden; height: 200px;
    display: flex; align-items: center; justify-content: center; }
.sp-preview-wrap { width: 320px; height: 200px; overflow: hidden; position: relative; flex-shrink: 0; }
.sp-preview-wrap iframe { width: 960px; height: 600px; border: none;
    transform: scale(0.333); transform-origin: 0 0; pointer-events: none; }
.sp-preview-label { position: absolute; bottom: 8px; right: 10px; font-size: 10px;
    color: rgba(255,255,255,.35); letter-spacing: .06em; }
.sp-body { padding: 16px 18px; }
.sp-url-row { display: flex; align-items: center; gap: 8px; margin-bottom: 14px;
    background: #f8f9fa; border-radius: 8px; padding: 9px 12px; border: 1px solid #e5eaf2; }
.sp-url-text { font-family: monospace; font-size: 12px; color: #1a73e8; flex: 1;
    word-break: break-all; }
/* Settings row */
.sp-settings { display: flex; gap: 10px; align-items: flex-end; margin-bottom: 14px;
    flex-wrap: wrap; }
.sp-field { display: flex; flex-direction: column; gap: 4px; }
.sp-field label { font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .06em; color: #888; }
.sp-field input[type=number] { width: 80px; padding: 7px 10px; border: 1.5px solid #dde3ec;
    border-radius: 7px; font-size: 13px; background: #fafbfc; }
.sp-field select { padding: 7px 10px; border: 1.5px solid #dde3ec;
    border-radius: 7px; font-size: 13px; background: #fafbfc; }
.sp-actions { display: flex; gap: 8px; flex-wrap: wrap; }
.chrome-cmd-row { display: flex; gap: 8px; align-items: flex-start; width: 100%; margin-bottom: 6px; }
.chrome-cmd-text { flex: 1; font-family: monospace; font-size: 11.5px; padding: 8px 10px; border: 1.5px solid #dde3ec; border-radius: 8px; background: #f4f8ff; color: #1a3a6e; resize: none; line-height: 1.5; }
.sp-no-posts { text-align: center; padding: 60px 20px; color: #999; font-size: 16px; }
/* Copia feedback */
.copied-msg { font-size: 11px; color: #34a853; font-weight: 700; display: none; margin-left: 4px; }
</style>
</head>
<body>
<div class="page">

<!-- SIDEBAR -->
<nav class="sidebar">
    <div class="sidebar-logo">🖥️ <span style="font-size:18px">Totem</span></div>
    <h2>Configurazione</h2>
    <a href="amministrazione.php">🎨 Grafica Totem</a>
    <a href="config_stampante.php">🖨️ Stampante Termica</a>
    <a href="config_monitor2.php">&#128250; Monitor Coda</a>
    <div class="sidebar-sep"></div>
    <a href="config_sportelli.php" class="active">🖥️ Sportelli / Click</a>
    <div class="sidebar-sep"></div>
    <a href="totem.php" target="_blank">👁️ Anteprima Totem</a>
    <a href="stampa_errori_view.php" target="_blank">📋 Log Stampa</a>
</nav>

<!-- MAIN -->
<div class="main">
<div class="topbar">
    <h1>🖥️ Sportelli — Anteprima &amp; Comandi Chrome</h1>
    <?php if (!$isLocalhost): ?>
    <a href="?logout" style="color:#ea4335;font-size:13px;text-decoration:none">🚪 Disconnetti</a>
    <?php endif; ?>
</div>
<div class="content">

<!-- URL BASE RILEVATO -->
<div class="url-bar">
    <span class="url-label">&#127760; Base URL rilevato</span>
    <span class="url-val" id="detected-base"><?php echo htmlspecialchars($baseUrl); ?></span>
    <span class="url-note">
        &#8505;&#65039; Questo indirizzo viene rilevato automaticamente dalla richiesta corrente.
        I link generati useranno sempre l'indirizzo con cui accedi a questa pagina
        (dominio, IP o localhost) — nessun valore è hardcoded.
    </span>
</div>

<?php if (empty($postazioni)): ?>
<div class="card">
    <div class="sp-no-posts">&#128683; Nessuna postazione configurata nel database.<br>
    <small style="font-size:13px;color:#bbb;margin-top:8px;display:block">Aggiungi postazioni dalla tabella <code>postazioni</code>.</small></div>
</div>
<?php else: ?>

<!-- GRIGLIA SPORTELLI -->
<div class="sportelli-grid">
<?php foreach ($postazioni as $sp):
    $num   = htmlspecialchars($sp['postazione']);
    $desc  = htmlspecialchars($sp['descrizione'] ?: '');
    $idSp  = 'sp_' . preg_replace('/\W/', '_', $sp['postazione']);
    $clickUrl = $baseUrl . '/click.php?postazione=' . rawurlencode($sp['postazione']);
?>
<div class="sp-card">
    <div class="sp-header">
        <span class="sp-icon">🖥️</span>
        <span class="sp-name">Sportello <?php echo $num; ?></span>
        <?php if ($desc): ?><span class="sp-desc"><?php echo $desc; ?></span><?php endif; ?>
    </div>
    <div class="sp-preview">
        <div class="sp-preview-wrap">
            <iframe src="<?php echo htmlspecialchars($clickUrl); ?>" loading="lazy"
                title="Anteprima Sportello <?php echo $num; ?>"></iframe>
        </div>
        <div class="sp-preview-label">ANTEPRIMA</div>
    </div>
    <div class="sp-body">
        <div class="sp-url-row">
            <span class="sp-url-text" id="url-<?php echo $idSp; ?>"><?php echo htmlspecialchars($clickUrl); ?></span>
            <button class="btn btn-ghost btn-sm" onclick="copiaUrl('<?php echo $idSp; ?>')">&#128203;</button>
            <span class="copied-msg" id="copied-<?php echo $idSp; ?>">Copiato!</span>
        </div>
        <div class="sp-settings">
            <div class="sp-field">
                <label>Larghezza (px)</label>
                <input type="number" id="w-<?php echo $idSp; ?>" value="900" min="400" max="3840" step="50" oninput="aggiornaCmdChrome('<?php echo $idSp; ?>')">
            </div>
            <div class="sp-field">
                <label>Altezza (px)</label>
                <input type="number" id="h-<?php echo $idSp; ?>" value="700" min="300" max="2160" step="50" oninput="aggiornaCmdChrome('<?php echo $idSp; ?>')">
            </div>
            <div class="sp-field">
                <label>Modalità</label>
                <select id="mode-<?php echo $idSp; ?>" onchange="aggiornaCmdChrome('<?php echo $idSp; ?>')">
                    <option value="app" selected>&#128752; Finestra (app)</option>
                    <option value="kiosk">&#9974; Kiosk fullscreen</option>
                </select>
            </div>
        </div>
        <div class="sp-actions">
            <div class="chrome-cmd-row">
                <textarea class="chrome-cmd-text" id="cmd-<?php echo $idSp; ?>" data-url="<?php echo htmlspecialchars($clickUrl); ?>" readonly rows="3"></textarea>
                <button class="btn btn-primary" onclick="copiaCmdChrome('<?php echo $idSp; ?>');return false;">&#128203; Copia</button>
            </div>
            <span class="copied-msg" id="copied-cmd-<?php echo $idSp; ?>">&#10003; Copiato!</span>
            <a href="<?php echo htmlspecialchars($clickUrl); ?>" target="_blank" class="btn btn-ghost">
                &#128065; Apri
            </a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- LEGENDA -->
<div class="card" style="margin-top:28px">
    <div class="card-title"><span>&#8505;&#65039;</span> Guida rapida</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;font-size:13px;color:#555;line-height:1.7">
        <div style="grid-column:1/-1">
            <strong style="color:#1a3a6e">📋 Comando Chrome — copia &amp; incolla</strong><br>
            Copia il comando mostrato nel riquadro, poi incollalo nel campo <strong>Destinazione</strong>
            di una scorciatoia Windows (<kbd>Tasto destro sul Desktop</kbd> → <em>Nuovo → Collegamento</em>).
            Scegli larghezza, altezza e modalità prima di copiare.<br>
            <em style="font-size:11px;color:#888">Richiede Google Chrome su <code>C:\Program Files\Google\Chrome\Application\chrome.exe</code>.</em>
        </div>
        <div>
            <strong style="color:#1a3a6e">🖥️ Modalità Finestra (app)</strong><br>
            Nessun controllo browser, finestra ridimensionabile, gestibile insieme ad altre finestre.
            Ideale per operatori con più applicazioni aperte.
        </div>
        <div>
            <strong style="color:#1a3a6e">⛶ Kiosk fullscreen</strong><br>
            Schermo intero senza controlli. Per uscire: <kbd>Alt</kbd>+<kbd>F4</kbd> o <kbd>Ctrl</kbd>+<kbd>W</kbd>.
            Ideale per postazioni dedicate con un solo monitor.
        </div>
    </div>
</div>

<?php endif; ?>
</div><!-- /content -->
</div><!-- /main -->
</div><!-- /page -->

<script>
function copiaUrl(id) {
    var el = document.getElementById('url-' + id);
    if (!el) return;
    navigator.clipboard.writeText(el.textContent.trim()).then(function() {
        var msg = document.getElementById('copied-' + id);
        if (msg) { msg.style.display = 'inline'; setTimeout(function(){ msg.style.display = 'none'; }, 1800); }
    });
}

function aggiornaCmdChrome(id) {
    var ta   = document.getElementById('cmd-' + id);
    if (!ta) return;
    var url  = ta.dataset.url || '';
    var wEl  = document.getElementById('w-' + id);
    var hEl  = document.getElementById('h-' + id);
    var mEl  = document.getElementById('mode-' + id);
    var w    = wEl ? (parseInt(wEl.value) || 900) : 900;
    var h    = hEl ? (parseInt(hEl.value) || 700) : 700;
    var mode = mEl ? mEl.value : 'app';
    var chrome = '"C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe"';
    var cmd;
    if (mode === 'kiosk') {
        cmd = chrome + ' --kiosk "' + url + '" --disable-infobars --noerrdialogs --no-default-browser-check';
    } else {
        cmd = chrome + ' --app="' + url + '" --window-size=' + w + ',' + h + ' --window-position=100,100 --disable-infobars --noerrdialogs --no-default-browser-check';
    }
    ta.value = cmd;
}

function copiaCmdChrome(id) {
    var ta = document.getElementById('cmd-' + id);
    if (!ta) return;
    navigator.clipboard.writeText(ta.value).then(function() {
        var msg = document.getElementById('copied-cmd-' + id);
        if (msg) { msg.style.display = 'inline'; setTimeout(function(){ msg.style.display = 'none'; }, 1800); }
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ids = <?php echo json_encode(array_map(function($s) {
        return 'sp_' . preg_replace('/\\W/', '_', $s['postazione']);
    }, $postazioni)); ?>;
    ids.forEach(function(id) { aggiornaCmdChrome(id); });
});
</script>
</body>
</html>
