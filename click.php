<?php
    include 'connect.php';
    $postazione = trim((string)($_GET['postazione'] ?? ''));
    if ($postazione === '') { exit; }
    $postazioneJs = json_encode($postazione, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    $postazioneHtml = htmlspecialchars($postazione, ENT_QUOTES, 'UTF-8');
?><!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Sportello <?php echo $postazioneHtml; ?></title>
  <script src="jquery-3.6.0.min.js"></script>
  <script>window.vNonTrasferiti = window.vNonTrasferiti || [];</script>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    :root{
      --accent:#42a5f5;
      --bg-from:#0d1b3e;--bg-to:#1a3a6e;
      --card:rgba(10,22,40,.82);
      --sep:rgba(255,255,255,.09);
    }
    html,body{width:100%;height:100%;font-family:'Segoe UI',system-ui,Arial,sans-serif;
      background:linear-gradient(160deg,var(--bg-from) 0%,var(--bg-to) 100%);color:#fff;overflow:hidden}
    .wrap{display:flex;flex-direction:column;width:100vw;height:100vh}
    /* HEADER */
    .pg-header{flex:0 0 auto;display:flex;align-items:center;gap:14px;padding:12px 20px;
      background:rgba(0,0,0,.35);border-bottom:1px solid var(--sep);min-height:58px}
    .pg-icon{font-size:24px;line-height:1;flex-shrink:0}
    .pg-title{font-size:17px;font-weight:800;letter-spacing:.04em}
    .pg-sub{font-size:11px;opacity:.4;margin-top:2px;letter-spacing:.08em;text-transform:uppercase}
    .pg-spacer{flex:1}
    .status-dot{width:8px;height:8px;border-radius:50%;background:#2ecc71;display:inline-block;
      margin-right:5px;box-shadow:0 0 6px rgba(46,204,113,.7)}
    /* TABS */
    .tabs{flex:0 0 auto;display:flex;background:rgba(0,0,0,.22);border-bottom:1px solid var(--sep)}
    .tab{flex:1;padding:13px 0;text-align:center;font-size:13px;font-weight:700;
      letter-spacing:.06em;text-transform:uppercase;color:rgba(255,255,255,.4);cursor:pointer;
      border:none;border-bottom:3px solid transparent;background:none;outline:none;
      transition:color .2s,border-color .2s}
    .tab.active{color:var(--accent);border-bottom-color:var(--accent)}
    .tab:hover:not(.active){color:rgba(255,255,255,.7)}
    /* CONTENT */
    .tab-content{display:none;flex:1 1 auto;flex-direction:column;overflow-y:auto;padding:16px}
    .tab-content.active{display:flex}
    .tab-content::-webkit-scrollbar{width:3px}
    .tab-content::-webkit-scrollbar-thumb{background:rgba(255,255,255,.15);border-radius:2px}
    /* ACTION BUTTONS */
    .actions-row{display:flex;gap:10px;justify-content:center;margin-bottom:16px}
    .action-card{flex:1;min-width:80px;max-width:130px;display:flex;flex-direction:column;
      align-items:center;justify-content:center;gap:8px;padding:16px 8px;
      border-radius:14px;border:1.5px solid var(--sep);background:var(--card);
      cursor:pointer;transition:background .18s,transform .12s,box-shadow .18s;
      outline:none;color:#fff}
    .action-card:hover{background:rgba(66,165,245,.2);border-color:var(--accent);
      transform:translateY(-2px);box-shadow:0 6px 20px rgba(66,165,245,.2)}
    .action-card:active{transform:scale(.96)}
    .ac-icon{width:44px;height:44px;color:rgba(255,255,255,.8);transition:color .18s,transform .18s}
    .action-card:hover .ac-icon{color:var(--accent);transform:scale(1.08)}
    .ac-label{font-size:12px;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    /* NUM DISPLAY */
    .num-card{background:var(--card);border:1.5px solid var(--sep);border-radius:14px;
      padding:14px 22px;text-align:center;margin-bottom:12px}
    .num-label{font-size:10px;text-transform:uppercase;letter-spacing:.1em;opacity:.4;margin-bottom:6px}
    .num-value{font-size:clamp(44px,9vw,76px);font-weight:900;color:var(--accent);line-height:1;
      font-variant-numeric:tabular-nums;letter-spacing:-.02em;text-shadow:0 0 20px rgba(66,165,245,.4)}
    .num-value.zero{color:rgba(255,255,255,.2);text-shadow:none}
    /* INFO / ALERT */
    .info-bar{text-align:center;font-size:12px;opacity:.4;margin-bottom:8px;letter-spacing:.04em}
    .queue-alert{text-align:center;font-size:13px;font-weight:600;color:#ffd54f;
      min-height:18px;margin-bottom:8px;padding:0 4px;line-height:1.4}
    /* TABLES */
    .tables-row{display:flex;gap:10px;flex:1 1 auto;min-height:100px}
    .table-card{flex:1;background:var(--card);border:1.5px solid var(--sep);
      border-radius:12px;padding:12px;overflow:hidden;display:flex;flex-direction:column;min-width:0}
    .table-title{font-size:10px;text-transform:uppercase;letter-spacing:.08em;
      color:var(--accent);opacity:.75;margin-bottom:8px;font-weight:700}
    .table-card table{width:100%;border-collapse:collapse}
    .table-card td{padding:4px 6px;font-size:12px;border-bottom:1px solid rgba(255,255,255,.04);
      color:rgba(255,255,255,.7)}
    /* CODA TAB */
    .coda-btns{display:flex;flex-direction:column;gap:10px;padding:4px 0;width:100%}
    .coda-btn{width:100%;padding:15px 20px;
      background:linear-gradient(135deg,rgba(66,165,245,.18) 0%,rgba(26,115,232,.18) 100%);
      border:1.5px solid rgba(66,165,245,.3);border-radius:12px;color:#fff;
      font-size:15px;font-weight:700;cursor:pointer;text-align:left;outline:none;
      transition:background .18s,transform .1s}
    .coda-btn:hover{background:linear-gradient(135deg,rgba(66,165,245,.32) 0%,rgba(26,115,232,.32) 100%);
      transform:translateX(4px)}
    .coda-empty{text-align:center;opacity:.25;font-size:16px;padding:40px 0;font-style:italic}
    /* MODAL */
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.72);display:none;
      z-index:1000;align-items:center;justify-content:center;backdrop-filter:blur(4px)}
    .modal-overlay.open{display:flex}
    .modal{background:linear-gradient(145deg,#0f2246 0%,#1a3a6e 100%);
      border:1.5px solid rgba(66,165,245,.25);border-radius:16px;padding:26px;
      min-width:300px;max-width:90vw;box-shadow:0 16px 48px rgba(0,0,0,.6)}
    .modal-header{font-size:16px;font-weight:800;margin-bottom:18px;color:var(--accent);text-align:center}
    .modal-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.08em;
      opacity:.5;margin-bottom:6px;font-weight:700}
    .modal-select{width:100%;padding:9px 11px;background:rgba(255,255,255,.07);
      border:1.5px solid rgba(255,255,255,.15);border-radius:8px;color:#fff;
      font-size:14px;font-family:inherit;margin-bottom:4px;outline:none}
    .modal-select option{background:#1a3a6e;color:#fff}
    .modal-footer{display:flex;gap:12px;justify-content:center;margin-top:18px}
    .modal-btn{padding:11px 26px;border:none;border-radius:8px;font-size:14px;font-weight:700;
      cursor:pointer;min-width:100px;transition:background .15s,transform .1s;outline:none}
    .modal-btn:active{transform:scale(.96)}
    .modal-btn.cancel{background:rgba(255,255,255,.1);color:#fff;border:1.5px solid rgba(255,255,255,.18)}
    .modal-btn.cancel:hover{background:rgba(255,255,255,.18)}
    .modal-btn.confirm{background:#1a73e8;color:#fff}
    .modal-btn.confirm:hover{background:#1558b0}
    /* RESPONSIVE */
    @media(max-width:480px){
      .actions-row{gap:6px}
      .action-card{min-width:68px;padding:12px 5px}
      .ac-icon{width:34px;height:34px}
      .tables-row{flex-direction:column}
    }
  </style>
</head>
<body>
<div class="wrap">

  <header class="pg-header">
    <div class="pg-icon">🖥️</div>
    <div>
      <div class="pg-title">Sportello <?php echo $postazioneHtml; ?></div>
      <div class="pg-sub">Postazione operatore</div>
    </div>
    <div class="pg-spacer"></div>
    <div style="display:flex;align-items:center">
      <span class="status-dot"></span>
      <span style="font-size:11px;opacity:.35;letter-spacing:.06em">ONLINE</span>
    </div>
  </header>

  <div class="tabs">
    <button class="tab active" onclick="showTab(0)">📞 Chiamata</button>
    <button class="tab" onclick="showTab(1)">👥 Coda</button>
  </div>

  <!-- TAB CHIAMATA -->
  <div class="tab-content active" id="tab-chiamata">
    <div class="actions-row">
      <button class="action-card" id="richiama">
        <svg class="ac-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 .49-5"/></svg>
        <div class="ac-label">Richiama</div>
      </button>
      <button class="action-card" id="chiama">
        <svg class="ac-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.5 12 19.79 19.79 0 0 1 1.15 3.18 2 2 0 0 1 3.12 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16z"/></svg>
        <div class="ac-label">Chiama</div>
      </button>
      <button class="action-card" id="trasferisci">
        <svg class="ac-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
        <div class="ac-label">Trasferisci</div>
      </button>
    </div>
    <div class="num-card">
      <div class="num-label">Numero in servizio</div>
      <div class="num-value zero" id="numero-chiamato">&#8212;</div>
    </div>
    <div class="info-bar" id="postazione-box">Sei nella postazione <?php echo $postazioneHtml; ?></div>
    <div class="queue-alert" id="messaggi-box"></div>
    <div class="tables-row">
      <div class="table-card">
        <div class="table-title">Ultimi chiamati da te</div>
        <table><tbody id="ultimi-numeri-chiamati"></tbody></table>
      </div>
      <div class="table-card">
        <div class="table-title">Ultimi chiamati globali</div>
        <table><tbody id="ultimi-numeri-chiamati-generali"></tbody></table>
      </div>
    </div>
  </div>

  <!-- TAB CODA -->
  <div class="tab-content" id="tab-coda">
    <div class="coda-btns" id="bottoni-box"></div>
    <div class="coda-empty" id="coda-empty-msg">Nessun utente in coda</div>
  </div>

</div>

<!-- MODAL TRASFERIMENTO -->
<div class="modal-overlay" id="modal-trasferisci">
  <div class="modal">
    <div class="modal-header">&#128256; Trasferisci numero</div>
    <div>
      <label class="modal-label">Postazione di destinazione</label>
      <select id="select-postazione" class="modal-select">
        <option value="">-- Seleziona postazione --</option>
<?php
      $sql = "SELECT * FROM postazioni where turno_ambulatorio <> ''";
      $result = mysqli_query($conn, $sql);
      while ($row = mysqli_fetch_assoc($result)) {
          echo '<option value="' . htmlspecialchars($row['postazione']) . '" data-turno-ambulatorio="' . htmlspecialchars($row['turno_ambulatorio']) . '">' . htmlspecialchars($row['descrizione']) . '</option>';
      }
?>
      </select>
      <label class="modal-label" style="margin-top:14px">Numero da trasferire</label>
      <select id="select-numero-da-chiamare" class="modal-select">
        <option value="">-- Seleziona numero --</option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="modal-btn cancel" onclick="chiudiModale()">Annulla</button>
      <button class="modal-btn confirm" onclick="confermaTrasferimento()">Conferma</button>
    </div>
  </div>
</div>

<script>
  var turniAmbulatori = [];
<?php
      $sql = "SELECT distinct turno_ambulatorio FROM postazioni where turno_ambulatorio <> ''";
      $result = mysqli_query($conn, $sql);
      while ($row = mysqli_fetch_assoc($result)) {
?>
  turniAmbulatori.push('<?php echo $row['turno_ambulatorio']?>');
<?php
      }
?>

  function showTab(index) {
    document.querySelectorAll('.tab').forEach(function(t,i){ t.classList.toggle('active', i===index); });
    document.querySelectorAll('.tab-content').forEach(function(c,i){ c.classList.toggle('active', i===index); });
  }

  document.getElementById('richiama').addEventListener('click', function() {
    $.ajax({
      url: 'richiamadapostazione.php', method: 'POST',
      data: { postazione: <?php echo $postazioneJs; ?> },
      success: function(response) {
        if (response != '-1' && response != '') {
          var el = document.getElementById('numero-chiamato');
          el.innerHTML = response; el.classList.remove('zero');
        }
      },
      error: function() { alert('Errore durante la chiamata'); }
    });
  });

  document.getElementById('chiama').addEventListener('click', function() {
    $.ajax({
      url: 'chiamadapostazione.php', method: 'POST',
      data: { postazione: <?php echo $postazioneJs; ?> },
      success: function(response) {
        if (response != '-1' && response != '') {
          var el = document.getElementById('numero-chiamato');
          el.innerHTML = response; el.classList.remove('zero');
          turniAmbulatori.forEach(function(lettera) {
            if (lettera === response.charAt(0)) {
              window.vNonTrasferiti = [];
              window.vNonTrasferiti.push(response);
              apriModale();
            }
          });
        }
      },
      error: function() { alert('Errore durante la chiamata'); }
    });
  });

  document.getElementById('trasferisci').addEventListener('click', function() { apriModale(); });

  setInterval(function() {
    $.ajax({
      url: 'ricevicoda.php', method: 'POST',
      data: { postazione: <?php echo $postazioneJs; ?> },
      success: function(response) {
        var items;
        try { items = JSON.parse(response); } catch(e) { return; }
        var htmlBottoni = '';
        var emptyEl = document.getElementById('coda-empty-msg');
        items.forEach(function(item) {
          htmlBottoni += '<button class="coda-btn" onclick="gestisciTurno(\'' + item.turno + '\')">Chiama turno ' + item.turno + ' &mdash; ' + item.numero + ' in coda</button>';
        });
        if (items.length === 0) {
          emptyEl.style.display = 'block';
          document.getElementById('messaggi-box').innerHTML = '';
        } else {
          emptyEl.style.display = 'none';
          var html = '';
          items.forEach(function(item){ html += 'Turno ' + item.turno + ': ' + item.numero + ' in coda &nbsp; '; });
          document.getElementById('messaggi-box').innerHTML = html;
        }
        document.getElementById('bottoni-box').innerHTML = htmlBottoni;
      },
      error: function() {}
    });
  }, 3000);

  setInterval(function() {
    $.ajax({
      url: 'ricevichiamate.php', method: 'POST',
      data: { postazione: <?php echo $postazioneJs; ?> },
      success: function(response) {
        var items;
        try { items = JSON.parse(response); } catch(e) { return; }
        var html = '', html2 = '';
        items.postazione.forEach(function(itemp){ html += '<tr><td>' + itemp.turno + ' &mdash; ' + itemp.numero + '</td></tr>'; });
        items.chiamati.forEach(function(itemc){ html2 += '<tr><td>' + itemc.turno + ' &mdash; ' + itemc.numero + ' &mdash; post. ' + itemc.postazione + '</td></tr>'; });
        document.getElementById('ultimi-numeri-chiamati').innerHTML = html;
        document.getElementById('ultimi-numeri-chiamati-generali').innerHTML = html2;
      },
      error: function() {}
    });
  }, 3000);

  function gestisciTurno(turno) {
    $.ajax({
      url: 'chiamadapostazione.php', method: 'POST',
      data: { postazione: <?php echo $postazioneJs; ?>, turno: turno },
      success: function(response) {
        if (response != '-1' && response != '') {
          var el = document.getElementById('numero-chiamato');
          el.innerHTML = response; el.classList.remove('zero');
          try { window.vNonTrasferiti = window.vNonTrasferiti || []; window.vNonTrasferiti.push(response); } catch(e) {}
        } else {
          alert('Errore durante la gestione del turno');
        }
      },
      error: function() { alert('Errore durante la gestione del turno'); }
    });
  }

  function updateNumeriDaChiamare() {
    var selPost = document.getElementById('select-postazione');
    var selNum = document.getElementById('select-numero-da-chiamare');
    if (!selPost || !selNum) return;
    var opt = selPost.options[selPost.selectedIndex];
    var turno = (opt && opt.dataset && opt.dataset.turnoAmbulatorio) ? String(opt.dataset.turnoAmbulatorio).trim() : '';
    var source = Array.isArray(window.vNonTrasferiti) ? window.vNonTrasferiti : [];
    var filtrati = turno ? source.filter(function(v){ return String(v).trim().charAt(0) === turno; }) : [];
    selNum.innerHTML = '<option value="">-- Seleziona numero --</option>';
    filtrati.forEach(function(v){ var o = document.createElement('option'); o.value = v; o.textContent = v; selNum.appendChild(o); });
  }

  function apriModale() {
    updateNumeriDaChiamare();
    document.getElementById('modal-trasferisci').classList.add('open');
  }
  function chiudiModale() {
    document.getElementById('modal-trasferisci').classList.remove('open');
  }

  function confermaTrasferimento() {
    var postazioneDestinazione = document.getElementById('select-postazione').value;
    var selNum = document.getElementById('select-numero-da-chiamare');
    var numeroSelezionato = selNum ? selNum.value : '';
    var el = document.getElementById('numero-chiamato');
    var numeroCorrente = numeroSelezionato || (el ? el.textContent.trim() : '');
    if (!postazioneDestinazione) { alert('Seleziona una postazione di destinazione'); return; }
    if (!numeroCorrente || numeroCorrente === '\u2014' || numeroCorrente === '000') { alert('Nessun numero da trasferire'); return; }
    $.ajax({
      url: 'trasferisci_ambulatorio.php', method: 'POST',
      data: { postazione_origine: <?php echo $postazioneJs; ?>, postazione_destinazione: postazioneDestinazione, numero: numeroCorrente },
      success: function(response) {
        if (response === 'OK') {
          try {
            var toRemove = String(numeroCorrente).trim();
            window.vNonTrasferiti = (window.vNonTrasferiti || []).filter(function(v){ return String(v).trim() !== toRemove; });
            updateNumeriDaChiamare();
          } catch(e) {}
          alert('Numero trasferito alla postazione ' + postazioneDestinazione);
          chiudiModale();
        } else {
          alert('Errore durante il trasferimento: ' + response);
        }
      },
      error: function() { alert('Errore durante il trasferimento'); }
    });
  }

  document.getElementById('modal-trasferisci').addEventListener('click', function(e) {
    if (e.target === this) chiudiModale();
  });
  (function() {
    var selPost = document.getElementById('select-postazione');
    if (selPost) { selPost.addEventListener('change', updateNumeriDaChiamare); selPost.addEventListener('input', updateNumeriDaChiamare); }
  })();
</script>
</body>
</html>
<?php mysqli_close($conn); ?>
