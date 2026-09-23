<?php
    $postazione = $_GET["postazione"] ?? 0;
    $turno = $_GET["turno"] ?? '';

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
  </style>
  <script src="jquery-3.6.0.min.js"></script>
</head>
<body>
  <div class="container">
    <div class="tabs">
      <div class="tab active" onclick="showTab(0)">CHIAMATA</div>
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
      </div>
      <div class="number-box">
        <div class="number-header">Numero chiamato</div>
        <div class="number-value" id="numero-chiamato">000</div>
      </div>
      <div id="postazione-box" style="margin-top:18px; text-align:center; color:#d35400; font-size:22px;">Sei nella postazione <?php echo $postazione;?></div>
      <div id="messaggi-box" style="margin-top:18px; text-align:center; color:#d35400; font-size:17px;"></div>
      <div class="double-box-row">
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
        <div class="double-box" id="box-sinistra">
          <table style="width:100%; border-collapse:collapse;">
            <thead>
              <tr>
                <th style="text-align:left; font-size:17px; color:#3498db; padding-bottom:8px;">Prossimi numeri in coda</th>
              </tr>
            </thead>
            <tbody id="prossimi-numeri-chiamati">
            </tbody>
          </table>
        </div>
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
        url: 'richiamadapostazione_ambulatorio.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?>, turno: '<?php echo $turno; ?>' },
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
        url: 'chiamadapostazione_ambulatorio.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?>, turno: '<?php echo $turno; ?>' },
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
    /*document.getElementById('cancella').addEventListener('click', function() {
      alert('Funzione Cancella di prossima attivazione');
    });*/

    /*setInterval(function() {
      $.ajax({
        url: 'ricevicoda_ambulatorio.php',
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
    }, 3000);*/

    setInterval(function() {
      $.ajax({
        url: 'ricevichiamate_ambulatorio.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?>, turno: '<?php echo $turno; ?>' },
        success: function(response) {
			html2 = '';
			if (response != "") {
				let vchiamate = response.split(",");
				for (let i = 0; i < vchiamate.length; i++) {
					html2 = html2 + vchiamate[i] + "&nbsp;&nbsp;&nbsp;";
				}
			}
			$('#ultimi-numeri-chiamati-generali').html(html2);
        },
        error: function() {
			console.error('Errore durante l\'aggiornamento del numero chiamato');
        }
      });
    }, 3000);

    setInterval(function() {
      $.ajax({
        url: 'ricevicoda_ambulatorio.php',
        method: 'POST',
        data: { postazione: <?php echo $postazione; ?>, turno: '<?php echo $turno; ?>' },
        success: function(response) {
			html2 = '';
			if (response != "") {
				let vchiamate = response.split(",");
				for (let i = 0; i < vchiamate.length; i++) {
					html2 = html2 + vchiamate[i] + "&nbsp;&nbsp;&nbsp;";
				}
			}
			$('#prossimi-numeri-chiamati').html(html2);
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
          } else {
            alert('Errore durante la gestione del turno');
          }
        },
        error: function() {
          alert('Errore durante la gestione del turno');
        }
      });
    }
  </script>
</body>
</html>