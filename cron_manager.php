<?php
date_default_timezone_set('Europe/Rome');
/**
 * cron_manager.php — Gestione visuale Cron Job
 * Accesso: cron_manager.php?token=cron2026!mgr
 */
define('SECRET_TOKEN', 'cron2026!mgr');
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
        http_response_code(403); die('403 - Accesso negato. Usa ?token=cron2026!mgr');
    }
}

preg_match('#/home/(sslip-[^/]+)/#', __DIR__, $m);
$siteUser = $m[1] ?? 'sslip-unknown';
$siteName = str_replace('sslip-', '', $siteUser);
$cronFile = "/etc/cron.d/{$siteUser}";
$homeDir  = "/home/{$siteUser}";
$logDir   = "{$homeDir}/logs/cron";
$varsFile = __DIR__ . '/.cron_vars.json';
$token    = htmlspecialchars($_GET['token'] ?? '');

if (!is_dir($logDir)) @mkdir($logDir, 0755, true);

function parseCronFile($file) {
    $vars = []; $jobs = [];
    if (!file_exists($file)) return [$vars, $jobs];
    foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (preg_match('/^([A-Z_]+)\s*=\s*"?([^"]*)"?\s*$/', $line, $mm)) {
            $vars[$mm[1]] = $mm[2]; continue;
        }
        if (preg_match('/^([@\d\*\/,\-]+(?:\s+[@\d\*\/,\-]+){4})\s+(\S+)\s+(.+)$/', $line, $mm)) {
            $jobs[] = ['schedule' => $mm[1], 'user' => $mm[2], 'command' => trim($mm[3])];
        }
    }
    return [$vars, $jobs];
}

function scheduleToHuman($s) {
    $map = [
        '* * * * *'  => 'Ogni minuto',
        '0 * * * *'  => 'Ogni ora',
        '0 0 * * *'  => 'Ogni giorno a mezzanotte',
        '0 8 * * *'  => 'Ogni giorno alle 08:00',
        '0 20 * * *' => 'Ogni giorno alle 20:00',
        '0 0 * * 0'  => 'Ogni domenica',
        '0 0 1 * *'  => 'Il 1° di ogni mese',
    ];
    return $map[$s] ?? $s;
}

[$cronFileVars, $cronJobs] = parseCronFile($cronFile);
$extraVars = file_exists($varsFile) ? (json_decode(file_get_contents($varsFile), true) ?? []) : [];

if (!empty($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    if ($_POST['action'] === 'save_vars') {
        $vars = [];
        foreach (['SHELL','PATH','MAILTO','EDITOR','HOME'] as $k) {
            if (isset($_POST[$k])) $vars[$k] = trim($_POST[$k]);
        }
        file_put_contents($varsFile, json_encode($vars, JSON_PRETTY_PRINT));
        echo json_encode(['ok' => true, 'msg' => 'Variabili salvate.']); exit;
    }
    if ($_POST['action'] === 'run') {
        $idx = intval($_POST['idx'] ?? -1);
        if (!isset($cronJobs[$idx])) { echo json_encode(['ok'=>false,'msg'=>'Job non trovato.']); exit; }
        $job = $cronJobs[$idx];
        $cmd = $job['command'];
        $ts  = date('Y-m-d H:i:s');
        $output = []; $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);
        $outStr = implode("\n", $output);
        $logFile = "{$GLOBALS['logDir']}/" . preg_replace('/[^a-z0-9_]/', '_', basename($cmd)) . '.log';
        $entry = "[{$ts}] MANUALE — {$cmd}\n" . ($outStr ?: '(nessun output)') . "\nExit: {$exitCode}\n" . str_repeat('-',60) . "\n";
        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
        echo json_encode(['ok'=>true,'output'=>$outStr?:'(nessun output)','exit'=>$exitCode,'ts'=>$ts]); exit;
    }
    if ($_POST['action'] === 'logs') {
        $idx = intval($_POST['idx'] ?? -1);
        if (!isset($cronJobs[$idx])) { echo json_encode(['ok'=>false,'content'=>'']); exit; }
        $logFile = "{$GLOBALS['logDir']}/" . preg_replace('/[^a-z0-9_]/', '_', basename($cronJobs[$idx]['command'])) . '.log';
        $content = file_exists($logFile) ? file_get_contents($logFile) : '(nessun log disponibile)';
        $lines = explode("\n", $content);
        if (count($lines) > 300) $lines = array_slice($lines, -300);
        echo json_encode(['ok'=>true,'content'=>implode("\n",$lines)]); exit;
    }
    echo json_encode(['ok'=>false,'msg'=>'Azione sconosciuta.']); exit;
}

$knownVars = ['SHELL'=>'/bin/bash','PATH'=>'/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin','MAILTO'=>'','EDITOR'=>'nano','HOME'=>$homeDir];
$mergedVars = array_merge($knownVars, $cronFileVars, $extraVars);
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cron Manager — <?= htmlspecialchars($siteName) ?></title>
<script src="jquery-3.6.0.min.js"></script>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',system-ui,sans-serif;background:#0d1b2e;color:#cde;min-height:100vh}
.wrap{max-width:1100px;margin:0 auto;padding:2rem 1rem}
h1{font-size:1.6rem;font-weight:800;color:#42a5f5;margin-bottom:.3rem}
.sub{font-size:.82rem;opacity:.4;margin-bottom:1.5rem}
.card{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:1.4rem;margin-bottom:1.4rem}
.card-title{font-size:.72rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#42a5f5;margin-bottom:.9rem;opacity:.8}
.vars-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:.75rem}
.var-row{display:flex;flex-direction:column;gap:.25rem}
.var-row label{font-size:.72rem;opacity:.5;letter-spacing:.06em;text-transform:uppercase}
.var-row input{background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);border-radius:8px;padding:.45rem .75rem;color:#fff;font-size:.83rem;font-family:monospace;width:100%;transition:border-color .2s}
.var-row input:focus{outline:none;border-color:#42a5f5}
.badge-ro{display:inline-block;font-size:.58rem;padding:.1rem .35rem;background:rgba(255,170,0,.15);color:#ffd740;border-radius:4px;margin-left:.35rem;vertical-align:middle}
.btn{display:inline-flex;align-items:center;gap:.45rem;padding:.45rem 1rem;border-radius:8px;border:none;cursor:pointer;font-size:.82rem;font-weight:600;transition:all .2s}
.btn-primary{background:#1565c0;color:#fff}.btn-primary:hover{background:#1976d2}
.btn-success{background:#2e7d32;color:#fff}.btn-success:hover{background:#388e3c}
.btn-log{background:rgba(255,255,255,.06);color:#cde}.btn-log:hover{background:rgba(255,255,255,.12)}
.btn-sm{padding:.3rem .7rem;font-size:.76rem}
.save-row{margin-top:.9rem;display:flex;align-items:center;gap:1rem}
.save-msg{font-size:.78rem;color:#69f0ae;opacity:0;transition:opacity .4s}
.info-bar{display:flex;gap:1rem;flex-wrap:wrap;margin-bottom:1.2rem}
.info-pill{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:.4rem .85rem;font-size:.78rem}
.info-pill strong{color:#42a5f5}
.jobs-table{width:100%;border-collapse:collapse}
.jobs-table th{font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;color:#42a5f5;opacity:.7;padding:.55rem .7rem;text-align:left;border-bottom:1px solid rgba(255,255,255,.07)}
.jobs-table td{padding:.85rem .7rem;border-bottom:1px solid rgba(255,255,255,.05);vertical-align:top}
.jobs-table tr:last-child td{border-bottom:none}
.schedule-badge{display:inline-block;background:rgba(21,101,192,.3);border:1px solid rgba(66,165,245,.3);border-radius:6px;padding:.18rem .55rem;font-size:.73rem;font-family:monospace;color:#90caf9}
.cmd-text{font-family:monospace;font-size:.75rem;color:#b0bec5;word-break:break-all;margin-top:.25rem;opacity:.7}
.human{font-size:.75rem;color:#69f0ae;margin-top:.18rem}
.actions{display:flex;gap:.45rem;flex-wrap:wrap;margin-top:.45rem}
.run-out{background:rgba(0,0,0,.4);border:1px solid rgba(255,255,255,.07);border-radius:8px;padding:.7rem;font-family:monospace;font-size:.75rem;color:#69f0ae;white-space:pre-wrap;margin-top:.7rem;display:none;max-height:180px;overflow-y:auto}
.spinner{width:13px;height:13px;border:2px solid rgba(255,255,255,.2);border-top-color:#42a5f5;border-radius:50%;animation:spin .6s linear infinite;display:none}
@keyframes spin{to{transform:rotate(360deg)}}
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:100;display:none;align-items:center;justify-content:center}
.modal-bg.open{display:flex}
.modal{background:#0d1b2e;border:1px solid rgba(255,255,255,.12);border-radius:16px;width:90vw;max-width:820px;max-height:82vh;display:flex;flex-direction:column;overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:.9rem 1.1rem;border-bottom:1px solid rgba(255,255,255,.07)}
.modal-header h3{font-size:.95rem;color:#42a5f5}
.modal-close{background:none;border:none;color:#fff;font-size:1.2rem;cursor:pointer;opacity:.5}.modal-close:hover{opacity:1}
.modal-body{flex:1;overflow-y:auto;padding:.9rem 1.1rem}
.log-pre{font-family:monospace;font-size:.73rem;white-space:pre-wrap;word-break:break-all;color:#b0bec5;line-height:1.65}
</style>
</head>
<body>
<div class="wrap">
  <h1>&#9881; Cron Manager</h1>
  <div class="sub">Sito: <strong style="color:#fff"><?= htmlspecialchars($siteName) ?></strong> &nbsp;&middot;&nbsp; File: <code>/etc/cron.d/<?= htmlspecialchars($siteUser) ?></code> &nbsp;&middot;&nbsp; Log: <code><?= htmlspecialchars($logDir) ?></code></div>
  <div class="info-bar">
    <div class="info-pill">Job schedulati: <strong><?= count($cronJobs) ?></strong></div>
    <div class="info-pill">Utente: <strong><?= htmlspecialchars($siteUser) ?></strong></div>
    <div class="info-pill">Variabili: <strong><?= count($mergedVars) ?></strong></div>
    <div class="info-pill">Log dir: <strong><?= is_dir($logDir) ? 'OK' : 'Creata al primo run' ?></strong></div>
  </div>

  <div class="card">
    <div class="card-title">Cron Variables <span class="badge-ro">cron.d in sola lettura &mdash; CloudPanel non viene toccato</span></div>
    <p style="font-size:.76rem;opacity:.4;margin-bottom:.9rem">Variabili da <code>/etc/cron.d/<?= htmlspecialchars($siteUser) ?></code> sono mostrate in sola lettura. Le modifiche qui vengono salvate in <code>.cron_vars.json</code> locale.</p>
    <div class="vars-grid">
<?php foreach ($mergedVars as $k => $v):
  $fromFile  = isset($cronFileVars[$k]);
  $fromExtra = isset($extraVars[$k]);
?>
      <div class="var-row">
        <label><?= htmlspecialchars($k) ?><?php if ($fromFile && !$fromExtra): ?> <span class="badge-ro">da cron.d</span><?php endif; ?></label>
        <input type="text" id="var_<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>"
          <?= ($fromFile && !$fromExtra) ? 'placeholder="(da cron.d)"' : '' ?>>
      </div>
<?php endforeach; ?>
    </div>
    <div class="save-row">
      <button class="btn btn-primary" onclick="saveVars()">&#128190; Salva variabili</button>
      <span class="save-msg" id="saveMsg"></span>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Cron Jobs (<?= count($cronJobs) ?>)</div>
    <?php if (empty($cronJobs)): ?>
    <p style="opacity:.35;font-size:.83rem">Nessun cron job trovato in <code><?= htmlspecialchars($cronFile) ?></code></p>
    <?php else: ?>
    <table class="jobs-table">
      <thead><tr><th>#</th><th>Schedulazione</th><th>Comando</th><th>Azioni</th></tr></thead>
      <tbody>
<?php foreach ($cronJobs as $i => $job): ?>
        <tr>
          <td style="opacity:.35;font-size:.78rem"><?= $i+1 ?></td>
          <td>
            <span class="schedule-badge"><?= htmlspecialchars($job['schedule']) ?></span>
            <div class="human"><?= htmlspecialchars(scheduleToHuman($job['schedule'])) ?></div>
          </td>
          <td><div class="cmd-text"><?= htmlspecialchars($job['command']) ?></div></td>
          <td>
            <div class="actions">
              <button class="btn btn-success btn-sm" onclick="runJob(<?= $i ?>)">
                <span class="spinner" id="spin<?= $i ?>"></span>&#9654; Esegui ora
              </button>
              <button class="btn btn-log btn-sm" onclick="showLogs(<?= $i ?>)">&#128203; Log</button>
            </div>
            <div class="run-out" id="out<?= $i ?>"></div>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
</div>

<div class="modal-bg" id="modalBg">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modalTitle">Log esecuzioni</h3>
      <button class="modal-close" onclick="closeModal()">&#x2715;</button>
    </div>
    <div class="modal-body"><pre class="log-pre" id="logContent">Caricamento...</pre></div>
  </div>
</div>

<script>
var TOKEN = '<?= addslashes($token) ?>';
function saveVars(){
  var data = {action:'save_vars'};
  ['SHELL','PATH','MAILTO','EDITOR','HOME'].forEach(function(k){
    var el = document.getElementById('var_'+k);
    if(el) data[k] = el.value;
  });
  $.post('cron_manager.php?token='+TOKEN, data, function(r){
    var msg = document.getElementById('saveMsg');
    msg.textContent = r.ok ? '\u2714 Salvato!' : '\u2718 Errore: '+r.msg;
    msg.style.color = r.ok ? '#69f0ae' : '#ff5252';
    msg.style.opacity = 1;
    setTimeout(function(){ msg.style.opacity=0; }, 2800);
  });
}
function runJob(idx){
  var spin = document.getElementById('spin'+idx);
  var out  = document.getElementById('out'+idx);
  spin.style.display='inline-block'; out.style.display='none'; out.textContent='';
  $.post('cron_manager.php?token='+TOKEN, {action:'run',idx:idx}, function(r){
    spin.style.display='none'; out.style.display='block';
    if(r.ok){
      out.style.color = r.exit===0 ? '#69f0ae' : '#ff8a65';
      out.textContent = '['+r.ts+'] Exit: '+r.exit+'\n\n'+r.output;
    } else { out.style.color='#ff5252'; out.textContent='Errore: '+r.msg; }
  });
}
function showLogs(idx){
  document.getElementById('modalBg').classList.add('open');
  document.getElementById('logContent').textContent='Caricamento...';
  $.post('cron_manager.php?token='+TOKEN, {action:'logs',idx:idx}, function(r){
    document.getElementById('logContent').textContent = r.ok ? (r.content||'(log vuoto)') : 'Errore.';
  });
}
function closeModal(){ document.getElementById('modalBg').classList.remove('open'); }
document.getElementById('modalBg').addEventListener('click',function(e){ if(e.target===this) closeModal(); });
</script>
</body>
</html>
