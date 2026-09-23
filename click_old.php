<?php
    include 'connect.php';
    
    $postazione = $_GET["postazione"] ?? 0;

    if ($postazione == 0) {
        exit;
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Maschera Chiamata / Coda</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      background: #f5f5f5;
    }
    /* ...existing code... */
    .container {
      width: 90vw;
      max-width: 800px;
      height: auto;
      min-height: 500px;
      margin: 20px auto;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 2px 16px rgba(0,0,0,0.1);
      overflow: hidden;
      display: flex;
      flex-direction: column;
      padding: 0;
    }

    /* Responsive: per schermi piccoli */
    @media (max-width: 600px) {
      .container {
        width: 98vw;
        min-width: unset;
        padding: 0 2vw;
      }
      .tab-content {
        padding: 16px 4px;
      }
      .buttons-row {
        flex-direction: column;
        gap: 12px;
        margin-bottom: 16px;
      }
      .double-box-row {
        flex-direction: column;
        gap: 12px;
      }
      .number-box, .double-box {
        width: 100%;
        min-width: unset;
        padding: 10px 4px;
        font-size: 16px;
      }
    }
    .tabs {
      display: flex;
      border-bottom: 2px solid #e0e0e0;
      background: #f9f9f9;
    }
    .tab {
      flex: 1;
      padding: 16px 0;
      text-align: center;
      cursor: pointer;
      font-size: 18px;
      color: #666;
      transition: background 0.2s;
    }
    .tab.active {
      background: #fff;
      color: #222;
      border-bottom: 2px solid #3498db;
      font-weight: bold;
    }
    .tab-content {
      flex: 1;
      padding: 32px 40px;
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    .buttons-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 32px;
    }
    .action-btn {
      width: 100px;
      height: 100px;
      background: #eaf6fb;
      border: 2px solid #3498db;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(52,152,219,0.07);
      transition: background 0.2s, box-shadow 0.2s;
    }
    .action-btn:hover {
      background: #d0e7fa;
      box-shadow: 0 4px 16px rgba(52,152,219,0.15);
    }
    .action-btn img {
      width: 42px;
      height: 42px;
      margin-bottom: 8px;
    }
    .action-btn span {
      font-size: 15px;
      color: #222;
      font-weight: bold;
    }
    .number-box {
      margin-top: 24px;
      background: #f4f9ff;
      border: 2px solid #b1d5f7;
      border-radius: 10px;
      padding: 18px 28px;
      text-align: center;
      width: 260px;
      margin-left: auto;
      margin-right: auto;
      box-shadow: 0 2px 10px rgba(52,152,219,0.08);
    }
    .number-header {
      font-size: 15px;
      color: #555;
      margin-bottom: 8px;
      font-weight: bold;
    }
    .number-value {
      font-size: 38px;
      color: #3498db;
      font-weight: bold;
      letter-spacing: 4px;
    }
    /* CODA placeholder */
    .placeholder {
      width: 100%;
      height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #888;
      font-size: 24px;
      font-style: italic;
    }
    .double-box-row {
      display: flex;
      gap: 24px;
      margin-top: 18px;
      width: 100%;
      justify-content: center;
    }
    .double-box {
      flex: 1;
      background: #f4f9ff;
      border: 2px solid #b1d5f7;
      border-radius: 10px;
      min-height: 120px;
      padding: 18px 28px;
      box-shadow: 0 2px 10px rgba(52,152,219,0.08);
      text-align: center;
      font-size: 18px;
    }
    .bottone-turno {
      background: #3498db;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 12px 24px;
      margin: 8px 6px;
      font-size: 18px;
      font-weight: bold;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(52,152,219,0.15);
      transition: background 0.2s, box-shadow 0.2s;
    }
    .bottone-turno:hover {
      background: #217dbb;
      box-shadow: 0 4px 16px rgba(52,152,219,0.25);
    }
    .lampeggia {
      animation: lampeggio 0.6s linear 2;
    }
    @keyframes lampeggio {
      0%   { background: #ffeaa7; }
      50%  { background: #fff; }
      100% { background: #ffeaa7; }
    }
    
    /* Stili per il modale */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      z-index: 1000;
    }
    
    .modal {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      min-width: 350px;
      max-width: 90vw;
    }
    
    .modal-header {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #333;
      text-align: center;
    }
    
    .modal-body {
      margin-bottom: 25px;
    }
    
    .modal-select {
      width: 100%;
      padding: 12px;
      border: 2px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
      margin-bottom: 15px;
    }
    
    .modal-buttons {
      display: flex;
      gap: 15px;
      justify-content: center;
    }
    
    .modal-btn {
      padding: 12px 25px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      font-weight: bold;
      cursor: pointer;
      min-width: 100px;
    }
    
    .modal-btn-confirm {
      background: #27ae60;
      color: white;
    }
    
    .modal-btn-confirm:hover {
      background: #229954;
    }
    
    .modal-btn-cancel {
      background: #e74c3c;
      color: white;
    }
    
    .modal-btn-cancel:hover {
      background: #c0392b;
    }
  </style>
  <script src="jquery-3.6.0.min.js"></script>
  <script>
    // Array globale accessibile da tutti gli script
    window.vNonTrasferiti = window.vNonTrasferiti || [];
  </script>
</head>
<body>
  <div class="container">
    <div class="tabs">
      <div class="tab active" onclick="showTab(0)">CHIAMATA</div>
      <div class="tab" onclick="showTab(1)">CODA</div>
    </div>
    <div class="tab-content active" id="tab-chiamata">
      <div class="buttons-row">
        <button class="action-btn" id="richiama">
          <img src="img/richiama.png" alt="Richiama" style="width: 56px; height: 56px;">
          <span>Richiama</span>
        </button>
        <button class="action-btn" id="chiama">
          <img src="img/chiama.png" alt="Chiama" style="width: 56px; height: 56px;">
          <span>Chiama</span>
        </button>
        <button class="action-btn" id="trasferisci">
          <img src="img/trasferisci.png" alt="Trasferisci" style="width: 56px; height: 56px;">
          <span>Trasferisci</span>
        </button>
        <!--button class="action-btn" id="cancella">
          <img src="img/cancella.png" alt="Cancella" style="width: 56px; height: 56px;">
          <span>Cancella</span>
        </button-->
      </div>
      <div class="number-box">
        <div class="number-header">Numero chiamato</div>
        <div class="number-value" id="numero-chiamato">000</div>
      </div>
      <div id="postazione-box" style="margin-top:18px; text-align:center; color:#d35400; font-size:22px;">Sei nella postazione <?php echo $postazione;?></div>
      <div id="messaggi-box" style="margin-top:18px; text-align:center; color:#d35400; font-size:17px;"></div>
      <div class="double-box-row">
        <div class="double-box" id="box-sinistra">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; font-size:17px; color:#3498db; padding-bottom:8px;">Ultimi numeri chiamati da te</th>
              </tr>
            </thead>
            <tbody id="ultimi-numeri-chiamati">
            </tbody>
          </table>
        </div>
        <div class="double-box" id="box-destra">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; font-size:17px; color:#3498db; padding-bottom:8px;">Ultimi numeri chiamati</th>
              </tr>
            </thead>
            <tbody id="ultimi-numeri-chiamati-generali">
            </tbody>
          </table>
        </div>
      </div>
      
    </div>
    <div class="tab-content" id="tab-coda">
      <div class="placeholder">
        <div id="bottoni-box" style="margin-top:18px; text-align:center; color:#d35400; font-size:17px;"></div>
      </div>
    </div>
  </div>
  
  <!-- Modale per trasferimento -->
  <div class="modal-overlay" id="modal-trasferisci">
    <div class="modal">
      <div class="modal-header">
        Trasferisci numero
      </div>
      <div class="modal-body">
        <div class="row">
          <label for="select-postazione" style="display: block; margin-bottom: 8px; font-weight: bold;">Seleziona postazione di destinazione:</label>
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
        </div>
        <div class="row">
          <label for="select-numero-da-chiamare" style="display: block; margin-bottom: 8px; font-weight: bold;">Seleziona numero da trasferire:</label>
          <select id="select-numero-da-chiamare" class="modal-select">
            <option value="">-- Seleziona numero --</option>
          </select>
        </div>
      </div>
      <div class="modal-buttons">
        <button class="modal-btn modal-btn-cancel" onclick="chiudiModale()">Annulla</button>
        <button class="modal-btn modal-btn-confirm" onclick="confermaTrasferimento()">Conferma</button>
      </div>
    </div>
  </div>
  
  <script>
    
    function showTab(index) {
      const tabs = document.querySelectorAll('.tab');
      const contents = document.querySelectorAll('.tab-content');
      tabs.forEach((tab, i) => {
        tab.classList.toggle('active', i === index);
        contents[i].classList.toggle('active', i === index);
      });
    }

    document.getElementById('richiama').addEventListener('click', function() {
      $.ajax({
        url: 'richiamadapostazione.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?> },
        success: function(response) {
          if (response != '-1' && response != '') {
            $('#numero-chiamato').html(response);
          }
        },
        error: function() {
          alert('Errore durante la chiamata');
        }
      });
    });
    document.getElementById('chiama').addEventListener('click', function() {
      $.ajax({
        url: 'chiamadapostazione.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?> },
        success: function(response) {
          if (response != '-1' && response != '') {
            $('#numero-chiamato').html(response);
            window.vNonTrasferiti = window.vNonTrasferiti || [];
            window.vNonTrasferiti.push(response);
          }
        },
        error: function() {
          alert('Errore durante la chiamata');
        }
      });
    });
    document.getElementById('trasferisci').addEventListener('click', function() {
      apriModale();
    });
    /*document.getElementById('cancella').addEventListener('click', function() {
      alert('Funzione Cancella di prossima attivazione');
    });*/

    setInterval(function() {
      $.ajax({
        url: 'ricevicoda.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?> },
        success: function(response) {
          let items;
          try {
              items = JSON.parse(response);
          } catch (e) {
              $('#messaggi-box').html('Formato dati non valido');
              return;
          }
          let html = '';
          let htmlBottoni = '';
          items.forEach(function(item) {
              html += 'Turno: ' + item.turno + ' - In coda: ' + item.numero + ' utenti<br>';
              htmlBottoni += '<button class="bottone-turno" onclick="gestisciTurno(\'' + item.turno + '\')">Chiama il turno ' + item.turno + ' (' + item.numero + ' utenti in coda)</button><br /> ';
          });
          if (html === '') {
            html = '<span style="color:#888; font-size:22px; font-style:italic;">Nessun utente in coda</span>';
            htmlBottoni = '';
          } else {
            $('#messaggi-box').addClass('lampeggia');
            setTimeout(function() {
              $('#messaggi-box').removeClass('lampeggia');
            }, 1200); // 2 lampeggi da 0.6s
          }
          $('#messaggi-box').html(html);
          $('#bottoni-box').html(htmlBottoni);
        },
        error: function() {
          console.error('Errore durante l\'aggiornamento del numero chiamato');
        }
      });
    }, 3000);

    setInterval(function() {
      $.ajax({
        url: 'ricevichiamate.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?> },
        success: function(response) {
          let items;
          try {
              items = JSON.parse(response);
          } catch (e) {
              $('#messaggi-box').html('Formato dati non valido');
              return;
          }
          let html = '';
          let html2 = '';
          items.postazione.forEach(function(itemp) {
            html += '<tr><td>' + itemp.turno + ' - ' + itemp.numero + '</td></tr>';
          });
          items.chiamati.forEach(function(itemc) {
            html2 += '<tr><td>' + itemc.turno + ' - ' + itemc.numero + '</td></tr>';
          });
          $('#ultimi-numeri-chiamati').html(html);
          $('#ultimi-numeri-chiamati-generali').html(html2);
        },
        error: function() {
          console.error('Errore durante l\'aggiornamento del numero chiamato');
        }
      });
    }, 3000);

    function gestisciTurno(turno) {
      $.ajax({
        url: 'chiamadapostazione.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?>, turno: turno },
        success: function(response) {
          if (response != '-1' && response != '') {
            $('#numero-chiamato').html(response);
            // Aggiunge il valore al vettore globale dei non trasferiti
            try {
              window.vNonTrasferiti = window.vNonTrasferiti || [];
              window.vNonTrasferiti.push(response);
              console.log('Aggiunto a vNonTrasferiti:', response);
              console.log('vNonTrasferiti ora contiene:', window.vNonTrasferiti);
            } catch (e) {
              console.error('Impossibile aggiungere a vNonTrasferiti:', e);
            }
          } else {
            alert('Errore durante la gestione del turno');
          }
        },
        error: function() {
          alert('Errore durante la gestione del turno');
        }
      });
    }

    // Funzioni per il modale di trasferimento
    function updateNumeriDaChiamare() {
      try {
        const selPost = document.getElementById('select-postazione');
        const selNum = document.getElementById('select-numero-da-chiamare');
        if (!selPost || !selNum) return;

        const opt = selPost.options[selPost.selectedIndex];
        const turno = (opt && opt.dataset && opt.dataset.turnoAmbulatorio)
          ? String(opt.dataset.turnoAmbulatorio).trim()
          : '';

        const source = Array.isArray(window.vNonTrasferiti) ? window.vNonTrasferiti : [];
        const filtrati = turno
          ? source.filter(v => {
              const s = String(v).trim();
              return s.charAt(0) === turno;
            })
          : [];

        // Pulisci e popola la select
        selNum.innerHTML = '';
        // Placeholder sempre presente
        const ph = document.createElement('option');
        ph.value = '';
        ph.selected = true;
        ph.textContent = filtrati.length > 0 ? 'Seleziona numero…' : 'Nessun numero compatibile';
        selNum.appendChild(ph);

        // Opzioni filtrate
        filtrati.forEach(v => {
          const opt = document.createElement('option');
          opt.value = String(v);
          opt.textContent = String(v);
          selNum.appendChild(opt);
        });
        // Mantieni sempre visibile
        selNum.style.display = 'block';
      } catch (e) {
        console.error('Errore aggiornando select numeri:', e);
      }
    }

    function apriModale() {
      document.getElementById('modal-trasferisci').style.display = 'block';
      // Aggiorna la select dei numeri in base alla postazione selezionata
      updateNumeriDaChiamare();
    }

    function chiudiModale() {
      document.getElementById('modal-trasferisci').style.display = 'none';
      document.getElementById('select-postazione').value = '';
      // Pulisci la select dei numeri (ma non nasconderla)
      const selNum = document.getElementById('select-numero-da-chiamare');
      if (selNum) {
        selNum.innerHTML = '';
        const ph = document.createElement('option');
        ph.value = '';
        ph.selected = true;
        ph.textContent = '-- Seleziona numero --';
        selNum.appendChild(ph);
      }
    }

    function confermaTrasferimento() {
      const postazioneDestinazione = document.getElementById('select-postazione').value;
      const selNum = document.getElementById('select-numero-da-chiamare');
      const numeroSelezionato = selNum ? selNum.value : '';
      const numeroCorrente = numeroSelezionato || document.getElementById('numero-chiamato').textContent;
      
      if (!postazioneDestinazione) {
        alert('Seleziona una postazione di destinazione');
        return;
      }
      
      if (!numeroCorrente || numeroCorrente === '000') {
        alert('Nessun numero da trasferire');
        return;
      }
      
      // Qui puoi aggiungere la chiamata AJAX per effettuare il trasferimento
      $.ajax({
        url: 'trasferisci_ambulatorio.php',
        method: 'POST',
        data: { 
          postazione_origine: <?php echo $postazione; ?>,
          postazione_destinazione: postazioneDestinazione,
          numero: numeroCorrente
        },
        success: function(response) {
          if (response === 'OK') {
            // Rimuovi il numero trasferito dal vettore dei non trasferiti
            try {
              const toRemove = String(numeroCorrente).trim();
              window.vNonTrasferiti = (window.vNonTrasferiti || []).filter(v => String(v).trim() !== toRemove);
              // Aggiorna eventualmente la select dei numeri
              updateNumeriDaChiamare();
            } catch (e) {
              console.error('Errore rimuovendo dal vettore vNonTrasferiti:', e);
            }
            alert('Numero trasferito con successo alla postazione ' + postazioneDestinazione);
            chiudiModale();
          } else {
            alert('Errore durante il trasferimento: ' + response);
          }
        },
        error: function() {
          alert('Errore durante il trasferimento');
        }
      });
    }

    // Chiudi modale cliccando sull'overlay
    document.getElementById('modal-trasferisci').addEventListener('click', function(e) {
      if (e.target === this) {
        chiudiModale();
      }
    });

    // Aggiorna la seconda select quando cambia la postazione
  (function attachChangeHandler() {
      const selPost = document.getElementById('select-postazione');
      if (selPost) {
    selPost.addEventListener('change', updateNumeriDaChiamare);
    selPost.addEventListener('input', updateNumeriDaChiamare);
      }
    })();
  </script>
</body>
</html>
<?php
  mysqli_close($conn);
?>