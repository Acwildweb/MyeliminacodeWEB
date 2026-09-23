<?php
/**
 * config_monitor2.php
 * Configurazione del nuovo monitor code (index2.php)
 * – da localhost : accesso diretto
 * – da rete LAN  : accesso con password (stessa di amministrazione.php)
 */
session_start();

$remoteIP    = $_SERVER['REMOTE_ADDR'] ?? '';
$isLocalhost = in_array($remoteIP, ['127.0.0.1', '::1'], true);

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
       .'<body><div class="b"><h2>403 &mdash; Accesso non consentito</h2>'
       .'<p>Questa pagina &egrave; accessibile solo dalla rete locale.</p></div></body></html>');
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

if (!$isLocalhost && empty($_SESSION[$authKey])) {
    // Mostra form login
    ?><!DOCTYPE html>
<html lang="it"><head><meta charset="UTF-8"><title>Login – Monitor Config</title>
<style>
body{font-family:Segoe UI,sans-serif;background:#0d1b3e;color:#fff;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.box{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);border-radius:14px;padding:40px;width:320px;text-align:center}
h2{margin-bottom:24px;font-size:18px;letter-spacing:1px}
input[type=password]{width:100%;padding:10px 14px;border-radius:8px;border:1px solid rgba(255,255,255,.25);background:rgba(255,255,255,.1);color:#fff;font-size:15px;margin-bottom:14px}
button{width:100%;padding:10px;border-radius:8px;border:none;background:#00bcd4;color:#000;font-weight:700;font-size:15px;cursor:pointer}
.err{color:#ff6b6b;font-size:13px;margin-bottom:10px}
</style></head>
<body><div class="box">
<h2>&#128274; Configurazione Monitor</h2>
<?php if($authError): ?><div class="err"><?php echo htmlspecialchars($authError);?></div><?php endif;?>
<form method="post">
<input type="password" name="_lan_pwd" placeholder="Password" autofocus>
<button type="submit">Accedi</button>
</form>
</div></body></html>
<?php
    exit;
}

// ── Include DB ────────────────────────────────────────────────
include 'connect.php';

$msg = '';

// ── Gestione upload logo ──────────────────────────────────────
if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','image/svg+xml'];
    $mime = mime_content_type($_FILES['logo_file']['tmp_name']);
    if (in_array($mime, $allowed, true)) {
        $ext = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $imgDir = __DIR__ . '/immagini';
        if (!is_dir($imgDir)) { mkdir($imgDir, 0775, true); }
        $dest = __DIR__ . '/immagini/logo_monitor2.' . $ext;
        $tmpData = file_get_contents($_FILES['logo_file']['tmp_name']);
        if ($tmpData !== false && file_put_contents($dest, $tmpData) !== false) {
            @unlink($_FILES['logo_file']['tmp_name']);
            $logoVal = 'logo_monitor2.' . $ext;
            mysqli_query($conn, "UPDATE config_monitor2 SET logo_immagine='" . mysqli_real_escape_string($conn, $logoVal) . "' WHERE id=1");
            $msg = 'Logo aggiornato con successo.';
        }
    } else {
        $msg = 'Formato immagine non supportato.';
    }
}

// ── Gestione upload sfondo ────────────────────────────────────
if (isset($_FILES['sfondo_file']) && $_FILES['sfondo_file']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $mime = mime_content_type($_FILES['sfondo_file']['tmp_name']);
    if (in_array($mime, $allowed, true)) {
        $ext = strtolower(pathinfo($_FILES['sfondo_file']['name'], PATHINFO_EXTENSION));
        $imgDir = __DIR__ . '/immagini';
        if (!is_dir($imgDir)) { mkdir($imgDir, 0775, true); }
        $dest = __DIR__ . '/immagini/sfondo_monitor2.' . $ext;
        $tmpData = file_get_contents($_FILES['sfondo_file']['tmp_name']);
        if ($tmpData !== false && file_put_contents($dest, $tmpData) !== false) {
            @unlink($_FILES['sfondo_file']['tmp_name']);
            $sfondoVal = 'sfondo_monitor2.' . $ext;
            mysqli_query($conn, "UPDATE config_monitor2 SET sfondo_immagine='" . mysqli_real_escape_string($conn, $sfondoVal) . "' WHERE id=1");
            $msg = 'Immagine sfondo aggiornata.';
        }
    }
}

// ── Gestione upload immagine di servizio ─────────────────────
if (isset($_FILES['servizio_file']) && $_FILES['servizio_file']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
    $mime = mime_content_type($_FILES['servizio_file']['tmp_name']);
    if (in_array($mime, $allowed, true)) {
        $ext = strtolower(pathinfo($_FILES['servizio_file']['name'], PATHINFO_EXTENSION));
        $imgDir = __DIR__ . '/immagini';
        if (!is_dir($imgDir)) { mkdir($imgDir, 0775, true); }
        $dest = __DIR__ . '/immagini/servizio_multimedia.' . $ext;
        $tmpData = file_get_contents($_FILES['servizio_file']['tmp_name']);
        if ($tmpData !== false && file_put_contents($dest, $tmpData) !== false) {
            @unlink($_FILES['servizio_file']['tmp_name']);
            $servizioVal = 'servizio_multimedia.' . $ext;
            mysqli_query($conn, "UPDATE config_monitor2 SET multimedia_immagine_servizio='" . mysqli_real_escape_string($conn, $servizioVal) . "' WHERE id=1");
            $msg = 'Immagine di servizio aggiornata.';
        }
    } else {
        $msg = 'Formato immagine non supportato.';
    }
}

// ── Salvataggio configurazione ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_cfg'])) {
    $fields = [
        'sfondo_tipo'       => mysqli_real_escape_string($conn, $_POST['sfondo_tipo'] ?? 'gradiente'),
        'sfondo_colore1'    => mysqli_real_escape_string($conn, $_POST['sfondo_colore1'] ?? '#0d1b3e'),
        'sfondo_colore2'    => mysqli_real_escape_string($conn, $_POST['sfondo_colore2'] ?? '#1a3a6e'),
        'colore_primario'   => mysqli_real_escape_string($conn, $_POST['colore_primario'] ?? '#00bcd4'),
        'colore_testo'      => mysqli_real_escape_string($conn, $_POST['colore_testo'] ?? '#ffffff'),
        'colore_riga_pari'  => mysqli_real_escape_string($conn, $_POST['colore_riga_pari'] ?? '#0a2240'),
        'colore_riga_dispari'=> mysqli_real_escape_string($conn, $_POST['colore_riga_dispari'] ?? '#0d2d50'),
        'animazione'        => mysqli_real_escape_string($conn, $_POST['animazione'] ?? 'flip'),
        'font_principale'   => mysqli_real_escape_string($conn, $_POST['font_principale'] ?? 'Roboto Condensed'),
        'news_testo'        => mysqli_real_escape_string($conn, $_POST['news_testo'] ?? ''),
        'news_velocita'     => intval($_POST['news_velocita'] ?? 60),
        'news_attivo'       => isset($_POST['news_attivo']) ? 1 : 0,
        'mostra_multimedia'              => isset($_POST['mostra_multimedia']) ? 1 : 0,
        'titolo_struttura'               => mysqli_real_escape_string($conn, $_POST['titolo_struttura'] ?? 'OSPEDALE'),
        'multimedia_durata'              => max(1, intval($_POST['multimedia_durata'] ?? 5)),
    ];
    $sets = [];
    foreach ($fields as $col => $val) {
        $sets[] = "`$col`='" . $val . "'";
    }
    mysqli_query($conn, 'UPDATE config_monitor2 SET ' . implode(',', $sets) . ' WHERE id=1');

    // Salva impostazioni TTS su JSON (usato da tts_audio.php)
    $ttsAllowed = ['ElsaNeural','IsabellaNeural','DiegoNeural','GiuseppeMultilingualNeural','paola','riccardo','mb-it2','mb-it3','it'];
    $ttsVoce    = in_array($_POST['tts_voce'] ?? '', $ttsAllowed) ? $_POST['tts_voce'] : 'ElsaNeural';
    $ttsVel     = max(80, min(220, intval($_POST['tts_velocita'] ?? 135)));
    $ttsAttivo  = isset($_POST['tts_attivo']);
    $ttsFile    = __DIR__ . '/totem_ui_config.json';
    $ttsCfg     = file_exists($ttsFile) ? (json_decode(file_get_contents($ttsFile), true) ?: []) : [];
    $ttsCfg['tts_voce']     = $ttsVoce;
    $ttsCfg['tts_velocita'] = $ttsVel;
    $ttsCfg['tts_attivo']   = $ttsAttivo;
    file_put_contents($ttsFile, json_encode($ttsCfg, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $msg = 'Configurazione salvata con successo.';
}

// ── Carica config TTS da JSON ─────────────────────────────────
$ttsFile  = __DIR__ . '/totem_ui_config.json';
$ttsCfg   = file_exists($ttsFile) ? (json_decode(file_get_contents($ttsFile), true) ?: []) : [];
$ttsVoce    = $ttsCfg['tts_voce']     ?? 'ElsaNeural';
$ttsVel     = $ttsCfg['tts_velocita'] ?? 135;
$ttsAttivo  = $ttsCfg['tts_attivo']   ?? true;

// ── Carica config attuale ─────────────────────────────────────
$cfg = [];
$rs = mysqli_query($conn, 'SELECT * FROM config_monitor2 WHERE id=1 LIMIT 1');
if ($rs && $row = mysqli_fetch_assoc($rs)) $cfg = $row;
$cfg += [
    'sfondo_tipo'=>'gradiente','sfondo_colore1'=>'#0d1b3e','sfondo_colore2'=>'#1a3a6e',
    'sfondo_immagine'=>'','logo_immagine'=>'','news_testo'=>'',
    'news_velocita'=>60,'news_attivo'=>1,'colore_primario'=>'#00bcd4',
    'colore_testo'=>'#1565c0','colore_riga_pari'=>'#0d47a1',
    'colore_riga_dispari'=>'#0a1628','animazione'=>'flip',
    'font_principale'=>'Roboto Condensed','mostra_multimedia'=>1,
    'titolo_struttura'=>'OSPEDALE',
    'multimedia_durata'=>5,'multimedia_immagine_servizio'=>'',
];

mysqli_close($conn);

function h($v){ return htmlspecialchars($v, ENT_QUOTES|ENT_HTML5); }
function sel($a,$b){ return $a===$b?'selected':''; }
function chk($v){ return $v?'checked':''; }
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Configurazione Monitor Coda</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#0d1b3e;color:#e0e8f0;display:flex;min-height:100vh}
/* SIDEBAR */
.sidebar{width:220px;min-height:100vh;background:#091428;padding:20px 0;display:flex;flex-direction:column;flex-shrink:0;border-right:1px solid rgba(255,255,255,.08)}
.sidebar-logo{padding:16px 20px 20px;font-size:20px;font-weight:700;color:#00bcd4;letter-spacing:1px;display:flex;align-items:center;gap:8px}
.sidebar h2{font-size:11px;letter-spacing:2px;text-transform:uppercase;color:rgba(255,255,255,.35);padding:0 20px 8px;margin-bottom:4px}
.sidebar a{display:block;padding:10px 20px;color:rgba(255,255,255,.7);text-decoration:none;font-size:13px;border-left:3px solid transparent;transition:.2s}
.sidebar a:hover{background:rgba(255,255,255,.05);color:#fff}
.sidebar a.active{background:rgba(0,188,212,.12);border-left-color:#00bcd4;color:#00bcd4}
.sidebar-sep{height:1px;background:rgba(255,255,255,.07);margin:10px 16px}
/* CONTENT */
.content{flex:1;padding:30px;overflow-y:auto}
h1{font-size:22px;font-weight:700;color:#00bcd4;margin-bottom:6px;display:flex;align-items:center;gap:8px}
.subtitle{font-size:13px;opacity:.5;margin-bottom:24px}
.msg{padding:12px 18px;border-radius:8px;margin-bottom:20px;font-size:13px;background:rgba(0,188,212,.15);border:1px solid rgba(0,188,212,.4);color:#00bcd4}
.card{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:24px;margin-bottom:20px}
.card h3{font-size:13px;font-weight:700;letter-spacing:2px;text-transform:uppercase;color:#00bcd4;margin-bottom:18px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.08)}
.form-row{display:grid;grid-template-columns:160px 1fr;align-items:center;gap:12px;margin-bottom:14px}
.form-row label{font-size:13px;opacity:.7}
input[type=text],input[type=number],select,textarea{
  width:100%;padding:8px 12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.18);
  border-radius:7px;color:#fff;font-size:13px;font-family:inherit;outline:none;transition:.2s;
}
input:focus,select:focus,textarea:focus{border-color:#00bcd4;background:rgba(0,188,212,.08)}
select option{background:#1a3a6e;color:#fff}
textarea{height:80px;resize:vertical}
input[type=color]{width:50px;height:34px;padding:2px;border-radius:6px;cursor:pointer;border:1px solid rgba(255,255,255,.2);background:rgba(255,255,255,.05)}
.color-row{display:flex;align-items:center;gap:10px}
.color-row input[type=text]{flex:1}
input[type=checkbox]{width:18px;height:18px;accent-color:#00bcd4;cursor:pointer}
.toggle-row{display:flex;align-items:center;gap:10px;font-size:13px;opacity:.8}
.upload-area{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
.upload-area input[type=file]{background:rgba(255,255,255,.06);border:1px dashed rgba(255,255,255,.25);border-radius:7px;padding:6px 12px;color:#ccc;cursor:pointer;font-size:12px}
.preview-img{max-height:60px;border-radius:6px;border:1px solid rgba(255,255,255,.15)}
.btn-primary{padding:10px 28px;background:#00bcd4;color:#000;border:none;border-radius:8px;font-weight:700;font-size:14px;cursor:pointer;transition:.2s;letter-spacing:.5px}
.btn-primary:hover{background:#00e5ff}
.btn-save-row{display:flex;gap:12px;align-items:center;justify-content:flex-end;margin-top:8px}
.preview-link{font-size:12px;opacity:.6;text-decoration:none;color:#00bcd4}
.preview-link:hover{opacity:1}
</style>
</head>
<body>
<nav class="sidebar">
  <div class="sidebar-logo">&#9881;&#65039; Config</div>
  <h2>Totem</h2>
  <a href="amministrazione.php">&#127912; Grafica Totem</a>
  <a href="config_stampante.php">&#128424; Stampante</a>
  <a href="config_sportelli.php">&#128251; Sportelli / Click</a>
  <div class="sidebar-sep"></div>
  <h2>Monitor</h2>
  <a href="config_monitor2.php" class="active">&#128250; Monitor Coda</a>
  <div class="sidebar-sep"></div>
  <a href="totem.php" target="_blank">&#128065; Anteprima Totem</a>
  <a href="index2.php" target="_blank">&#128065; Anteprima Monitor</a>
  <a href="stampa_errori_view.php" target="_blank">&#128203; Log Stampa</a>
  <?php if(!$isLocalhost):?>
  <div class="sidebar-sep"></div>
  <a href="?logout=1">&#128275; Esci</a>
  <?php endif;?>
</nav>
<div class="content">
  <h1>&#128250; Monitor Coda</h1>
  <div class="subtitle">Configura l'aspetto del nuovo monitor di chiamata numeri (index2.php)</div>

  <?php if($msg):?><div class="msg"><?php echo h($msg);?></div><?php endif;?>

  <form method="post" enctype="multipart/form-data">

    <!-- SFONDO -->
    <div class="card">
      <h3>&#127752; Sfondo</h3>
      <div class="form-row">
        <label>Tipo sfondo</label>
        <select name="sfondo_tipo" id="sfondo_tipo" onchange="toggleSfondo(this.value)">
          <option value="gradiente" <?php echo sel($cfg['sfondo_tipo'],'gradiente');?>>Gradiente</option>
          <option value="colore"    <?php echo sel($cfg['sfondo_tipo'],'colore');?>>Colore pieno</option>
          <option value="immagine"  <?php echo sel($cfg['sfondo_tipo'],'immagine');?>>Immagine</option>
        </select>
      </div>
      <div id="sfondo-colori">
        <div class="form-row">
          <label>Colore 1</label>
          <div class="color-row">
            <input type="color" value="<?php echo h($cfg['sfondo_colore1']);?>" oninput="document.getElementById('sc1').value=this.value">
            <input type="text" id="sc1" name="sfondo_colore1" value="<?php echo h($cfg['sfondo_colore1']);?>" maxlength="20">
          </div>
        </div>
        <div class="form-row" id="row-sc2">
          <label>Colore 2 (gradiente)</label>
          <div class="color-row">
            <input type="color" value="<?php echo h($cfg['sfondo_colore2']);?>" oninput="document.getElementById('sc2').value=this.value">
            <input type="text" id="sc2" name="sfondo_colore2" value="<?php echo h($cfg['sfondo_colore2']);?>" maxlength="20">
          </div>
        </div>
      </div>
      <div id="sfondo-img" style="display:none">
        <div class="form-row">
          <label>Immagine sfondo</label>
          <div class="upload-area">
            <input type="file" name="sfondo_file" accept="image/jpeg,image/png,image/gif,image/webp">
            <?php if($cfg['sfondo_immagine']):?>
              <img class="preview-img" src="immagini/<?php echo h($cfg['sfondo_immagine']);?>" alt="sfondo">
            <?php endif;?>
          </div>
        </div>
      </div>
    </div>

    <!-- LOGO -->
    <div class="card">
      <h3>&#127959; Logo e Titolo</h3>
      <div class="form-row">
        <label>Titolo struttura</label>
        <input type="text" name="titolo_struttura" value="<?php echo h($cfg['titolo_struttura']);?>" maxlength="200">
      </div>
      <div class="form-row">
        <label>Logo</label>
        <div class="upload-area">
          <input type="file" name="logo_file" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml">
          <?php if($cfg['logo_immagine']):?>
            <img class="preview-img" src="immagini/<?php echo h($cfg['logo_immagine']);?>" alt="logo">
          <?php endif;?>
        </div>
      </div>
    </div>

    <!-- COLORI -->
    <div class="card">
      <h3>&#127912; Colori elementi monitor</h3>
      <div class="form-row">
        <label>&#128308; Accento &mdash; numeri turno, orologio, sportello</label>
        <div class="color-row">
          <input type="color" value="<?php echo h($cfg['colore_primario']);?>" oninput="document.getElementById('cp').value=this.value">
          <input type="text" id="cp" name="colore_primario" value="<?php echo h($cfg['colore_primario']);?>" maxlength="20">
        </div>
        <small style="color:rgba(255,255,255,.45);font-size:11px">Colore del numero chiamato, dell&rsquo;orologio, del nome sportello e di tutte le evidenziazioni.</small>
      </div>
      <div class="form-row">
        <label>&#9646; Card turno &mdash; colore chiaro (sfondo superiore)</label>
        <div class="color-row">
          <input type="color" value="<?php echo h($cfg['colore_testo']??'#1565c0');?>" oninput="document.getElementById('ct').value=this.value">
          <input type="text" id="ct" name="colore_testo" value="<?php echo h($cfg['colore_testo']??'#1565c0');?>" maxlength="20">
        </div>
        <small style="color:rgba(255,255,255,.45);font-size:11px">Sfondo della parte alta delle card turno (gradiente). Default: blu medio #1565c0.</small>
      </div>
      <div class="form-row">
        <label>&#9646; Card turno &mdash; colore scuro (sfondo inferiore)</label>
        <div class="color-row">
          <input type="color" value="<?php echo h($cfg['colore_riga_pari']??'#0d47a1');?>" oninput="document.getElementById('crp').value=this.value">
          <input type="text" id="crp" name="colore_riga_pari" value="<?php echo h($cfg['colore_riga_pari']??'#0d47a1');?>" maxlength="20">
        </div>
        <small style="color:rgba(255,255,255,.45);font-size:11px">Sfondo della parte bassa delle card turno (gradiente). Default: blu scuro #0d47a1.</small>
      </div>
      <div class="form-row">
        <label>&#9644; Header e Footer &mdash; barra superiore e barra avvisi</label>
        <div class="color-row">
          <input type="color" value="<?php echo h($cfg['colore_riga_dispari']??'#0a1628');?>" oninput="document.getElementById('crd').value=this.value">
          <input type="text" id="crd" name="colore_riga_dispari" value="<?php echo h($cfg['colore_riga_dispari']??'#0a1628');?>" maxlength="20">
        </div>
        <small style="color:rgba(255,255,255,.45);font-size:11px">Sfondo della barra in alto (logo+titolo+orologio) e del footer con il ticker avvisi. Default: blu notte #0a1628.</small>
      </div>
    </div>

    <!-- ANIMAZIONE E FONT -->
    <div class="card">
      <h3>&#9654; Animazione e Font</h3>
      <div class="form-row">
        <label>Animazione numero</label>
        <select name="animazione">
          <option value="flip"  <?php echo sel($cfg['animazione'],'flip');?>>Flip (ribaltamento)</option>
          <option value="slide" <?php echo sel($cfg['animazione'],'slide');?>>Slide (scorrimento)</option>
          <option value="flash" <?php echo sel($cfg['animazione'],'flash');?>>Flash (lampeggio)</option>
          <option value="zoom"  <?php echo sel($cfg['animazione'],'zoom');?>>Zoom (ingrandimento)</option>
        </select>
      </div>
      <div class="form-row">
        <label>Font principale</label>
        <select name="font_principale">
          <option value="Roboto Condensed" <?php echo sel($cfg['font_principale'],'Roboto Condensed');?>>Roboto Condensed</option>
          <option value="Rajdhani"         <?php echo sel($cfg['font_principale'],'Rajdhani');?>>Rajdhani</option>
          <option value="Orbitron"         <?php echo sel($cfg['font_principale'],'Orbitron');?>>Orbitron</option>
          <option value="Arial"            <?php echo sel($cfg['font_principale'],'Arial');?>>Arial</option>
          <option value="Segoe UI"         <?php echo sel($cfg['font_principale'],'Segoe UI');?>>Segoe UI</option>
        </select>
      </div>
    </div>

    <!-- NEWS TICKER -->
    <div class="card">
      <h3>&#128226; News ticker</h3>
      <div class="form-row">
        <label>Attivo</label>
        <div class="toggle-row">
          <input type="checkbox" name="news_attivo" id="news_attivo" <?php echo chk($cfg['news_attivo']);?>>
          <label for="news_attivo">Mostra striscia avvisi in fondo al monitor</label>
        </div>
      </div>
      <div class="form-row">
        <label>Testo avvisi</label>
        <textarea name="news_testo"><?php echo h($cfg['news_testo']);?></textarea>
      </div>
      <div class="form-row">
        <label>Velocit&agrave; scroll (s)</label>
        <input type="number" name="news_velocita" value="<?php echo intval($cfg['news_velocita']);?>" min="10" max="300" step="5">
      </div>
    </div>

    <!-- MULTIMEDIA -->
    <div class="card">
      <h3>&#127916; Multimedia</h3>
      <div class="form-row">
        <label>Zona multimediale</label>
        <div class="toggle-row">
          <input type="checkbox" name="mostra_multimedia" id="m_mm" <?php echo chk($cfg['mostra_multimedia']);?>>
          <label for="m_mm">Mostra area immagini/video nella barra laterale</label>
        </div>
      </div>
      <div class="form-row" style="margin-top:14px">
        <label>Durata contenuto (s)</label>
        <div style="display:flex;align-items:center;gap:10px">
          <input type="number" name="multimedia_durata" value="<?php echo intval($cfg['multimedia_durata']);?>" min="1" max="300" step="1" style="width:100px">
          <small style="opacity:.5;font-size:12px">Secondi di visualizzazione per ogni immagine; per i video, attesa sull&rsquo;immagine successiva al termine del video.</small>
        </div>
      </div>
      <div class="form-row">
        <label>Immagine di servizio</label>
        <div class="upload-area">
          <input type="file" name="servizio_file" accept="image/jpeg,image/png,image/gif,image/webp">
          <?php if($cfg['multimedia_immagine_servizio']):?>
            <img class="preview-img" src="immagini/<?php echo h($cfg['multimedia_immagine_servizio']);?>" alt="servizio">
            <small style="opacity:.5;font-size:12px"><?php echo h($cfg['multimedia_immagine_servizio']);?></small>
          <?php endif;?>
        </div>
      </div>
      <p style="font-size:12px;opacity:.5;margin-top:8px">L&rsquo;immagine di servizio viene mostrata quando non sono presenti immagini o video nella cartella <code>immaginicliente/</code>. Le immagini si caricano dalla cartella <code>immaginicliente/</code> e i video da <code>videocliente/</code> tramite le API esistenti.</p>
    </div>

    <div class="card">
      <h3>&#128266; Voce Annunci</h3>
      <div class="form-row">
        <label>Annunci vocali</label>
        <div class="toggle-row">
          <input type="checkbox" name="tts_attivo" id="tts_attivo" value="1" <?php echo $ttsAttivo ? 'checked' : ''; ?>>
          <label for="tts_attivo">Attiva annunci vocali al cambio numero</label>
        </div>
      </div>
      <div class="form-row">
        <label for="tts_voce">Voce</label>
        <select name="tts_voce" id="tts_voce" style="background:#0a1e3d;color:#fff;border:1px solid rgba(255,255,255,.15);border-radius:6px;padding:8px 12px;font-size:14px;width:100%;max-width:340px">
          <?php
          $voci=['ElsaNeural'=>'🔊 Elsa — femminile (Microsoft Neural) ★','IsabellaNeural'=>'🔊 Isabella — femminile (Microsoft Neural)','DiegoNeural'=>'🔊 Diego — maschile (Microsoft Neural)','GiuseppeMultilingualNeural'=>'🔊 Giuseppe — maschile (Microsoft Neural)','paola'=>'🎙 Paola — femminile (Piper locale)','riccardo'=>'🎙 Riccardo — maschile (Piper locale)','mb-it2'=>'🇮🇹 Femminile (mbrola)','mb-it3'=>'🇮🇹 Maschile (mbrola)','it'=>'🤖 Sintetica base (espeak)'];
          foreach($voci as $k=>$v): ?>
          <option value="<?php echo $k;?>" <?php echo $ttsVoce===$k?'selected':'';?>><?php echo htmlspecialchars($v);?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-row">
        <label>Velocità: <strong id="tts_vel_lbl"><?php echo intval($ttsVel);?></strong> par/min</label>
        <input type="range" name="tts_velocita" id="tts_velocita" min="80" max="220" step="5"
               value="<?php echo intval($ttsVel);?>"
               oninput="document.getElementById('tts_vel_lbl').textContent=this.value"
               style="width:100%;max-width:340px;margin-top:4px">
        <div style="display:flex;justify-content:space-between;max-width:340px;font-size:11px;opacity:.5;margin-top:2px"><span>Lento</span><span>Normale</span><span>Veloce</span></div>
      </div>
      <div class="form-row">
        <label>&nbsp;</label>
        <button type="button" onclick="testTTS()" style="background:#2e7d32;color:#fff;border:none;border-radius:6px;padding:8px 18px;cursor:pointer;font-size:14px">&#9654; Ascolta anteprima</button>
        <span id="tts_st" style="margin-left:10px;font-size:13px;opacity:.7"></span>
      </div>
    </div>

    <div class="btn-save-row">
      <a href="index2.php" target="_blank" class="preview-link">&#128065; Apri anteprima monitor &rarr;</a>
      <button type="submit" name="save_cfg" class="btn-primary">&#128190; Salva configurazione</button>
    </div>

  </form>
</div>

<script>
function testTTS(){
  var voce=document.getElementById('tts_voce').value;
  var vel=document.getElementById('tts_velocita').value;
  var st=document.getElementById('tts_st');
  st.textContent='Generazione...';
  var url='/tts_audio.php?t='+encodeURIComponent('Turno A, numero 1. Recarsi allo sportello 5.')
         +'&voce='+encodeURIComponent(voce)+'&vel='+encodeURIComponent(vel);
  var a=new Audio(url);
  a.onplay=function(){st.textContent='▶ Riproduzione...';};
  a.onended=function(){st.textContent='✓ Fine';};
  a.onerror=function(){st.textContent='✗ Errore';};
  a.play().catch(function(e){st.textContent='✗ '+e.message;});
}
</script>
<script>
function toggleSfondo(v){
  document.getElementById('sfondo-colori').style.display=(v==='immagine')?'none':'block';
  document.getElementById('sfondo-img').style.display=(v==='immagine')?'block':'none';
  document.getElementById('row-sc2').style.display=(v==='colore')?'none':'block';
}
toggleSfondo('<?php echo h($cfg['sfondo_tipo']);?>');
</script>
</body>
</html>
