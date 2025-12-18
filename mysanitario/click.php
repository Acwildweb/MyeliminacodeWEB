<?php
    require_once 'connect.php';
    
    $postazione = $_GET["postazione"] ?? 0;
    $nomePostazione = "Postazione $postazione";
    $serviziAssociati = [];
    $turniAssociati = [];
    
    // Recupera il nome della postazione e le associazioni dal database
    if ($postazione > 0 && $conn) {
        $stmt = $conn->prepare("SELECT postazione FROM postazioni WHERE ID_postazione = ?");
        $stmt->bind_param("i", $postazione);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $nomePostazione = $row['postazione'];
        }
        $stmt->close();
        
        // Servizi associati alla postazione
        $qServ = "SELECT DISTINCT o.operazione 
                  FROM operazioni o 
                  INNER JOIN operazioni_postazioni op ON o.id_operazione = op.id_operazione 
                  WHERE op.id_postazione = ? 
                  ORDER BY o.operazione";
        $stmtServ = $conn->prepare($qServ);
        $stmtServ->bind_param("i", $postazione);
        $stmtServ->execute();
        $resServ = $stmtServ->get_result();
        while ($serv = $resServ->fetch_assoc()) {
            $serviziAssociati[] = $serv['operazione'];
        }
        $stmtServ->close();
        
        // Turni associati alla postazione
        $qTurni = "SELECT DISTINCT t.turno, t.stato 
                   FROM turni t 
                   INNER JOIN operazioni_turni ot ON t.id_turno = ot.id_turno 
                   INNER JOIN operazioni_postazioni op ON ot.id_operazione = op.id_operazione 
                   WHERE op.id_postazione = ? AND t.stato = 'A'
                   ORDER BY t.turno";
        $stmtTurni = $conn->prepare($qTurni);
        $stmtTurni->bind_param("i", $postazione);
        $stmtTurni->execute();
        $resTurni = $stmtTurni->get_result();
        while ($turno = $resTurni->fetch_assoc()) {
            $turniAssociati[] = $turno['turno'];
        }
        $stmtTurni->close();
    }

    if ($postazione == 0) {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Errore</title>
        <style>body{font-family:Inter,Arial,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
        .error-box{background:white;padding:40px;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,0.1);text-align:center;max-width:400px;}
        h1{color:#ef4444;margin-bottom:16px;font-size:24px;}p{color:#64748b;}</style></head>
        <body><div class="error-box"><h1> Postazione non specificata</h1><p>Aggiungi il parametro ?postazione=X alla URL</p></div></body></html>';
        exit;
    }
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($nomePostazione); ?> - MySanitario</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg-primary: #f8fafc;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f1f5f9;
            --bg-dark: #1e293b;
            --text-primary: #1e293b;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
            --radius: 8px;
            --radius-lg: 12px;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            padding: 24px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
            color: white;
        }
        .header h1 {
            font-size: 28px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }
        .header h1 i { color: var(--primary-light); }
        .header .postazione-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 100px;
            font-size: 16px;
            font-weight: 600;
            margin-top: 12px;
        }
        .header .servizi-info {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .header .info-group {
            display: flex;
            align-items: center;
            gap: 8px;
            color: rgba(255,255,255,0.7);
            font-size: 13px;
        }
        .header .info-group i {
            font-size: 12px;
            opacity: 0.6;
        }
        .header .info-group .info-label {
            opacity: 0.6;
        }
        .header .info-tags {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .header .info-tag {
            background: rgba(255,255,255,0.15);
            padding: 3px 10px;
            border-radius: 100px;
            font-size: 12px;
            font-weight: 500;
            color: white;
        }
        .header .info-tag.turno {
            background: rgba(16,185,129,0.3);
            border: 1px solid rgba(16,185,129,0.5);
        }
        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            background: rgba(255,255,255,0.1);
            padding: 6px;
            border-radius: var(--radius-lg) var(--radius-lg) 0 0;
        }
        .tab {
            flex: 1;
            padding: 14px 24px;
            text-align: center;
            font-size: 15px;
            font-weight: 600;
            color: rgba(255,255,255,0.7);
            background: transparent;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab:hover { color: white; background: rgba(255,255,255,0.1); }
        .tab.active {
            background: var(--bg-secondary);
            color: var(--primary);
        }
        .tab i { margin-right: 8px; }
        /* Content */
        .content-box {
            background: var(--bg-secondary);
            border-radius: 0 0 var(--radius-lg) var(--radius-lg);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }
        .tab-content {
            display: none;
            padding: 32px;
        }
        .tab-content.active { display: block; }
        /* Action Buttons */
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 24px 16px;
            background: var(--bg-tertiary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius-lg);
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-btn:hover {
            border-color: var(--primary);
            background: rgba(37,99,235,0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.15);
        }
        .action-btn i {
            font-size: 32px;
            color: var(--primary);
        }
        .action-btn span {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }
        .action-btn.btn-richiama i { color: var(--warning); }
        .action-btn.btn-richiama:hover { border-color: var(--warning); background: rgba(245,158,11,0.05); }
        .action-btn.btn-trasferisci i { color: var(--success); }
        .action-btn.btn-trasferisci:hover { border-color: var(--success); background: rgba(16,185,129,0.05); }
        /* Number Display */
        .number-display {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: var(--radius-lg);
            padding: 32px;
            text-align: center;
            margin-bottom: 24px;
            color: white;
        }
        .number-label {
            font-size: 14px;
            font-weight: 500;
            opacity: 0.9;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .number-value {
            font-size: 56px;
            font-weight: 700;
            letter-spacing: 8px;
        }
        /* Info Box */
        .info-box {
            background: rgba(245,158,11,0.1);
            border: 1px solid rgba(245,158,11,0.3);
            border-radius: var(--radius);
            padding: 16px 20px;
            margin-bottom: 24px;
            color: #b45309;
            font-size: 14px;
            text-align: center;
        }
        .info-box.lampeggia {
            animation: lampeggio 0.5s ease-in-out 3;
        }
        @keyframes lampeggio {
            0%, 100% { background: rgba(245,158,11,0.1); }
            50% { background: rgba(245,158,11,0.3); }
        }
        /* Tables */
        .tables-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .table-card {
            background: var(--bg-tertiary);
            border-radius: var(--radius);
            overflow: hidden;
        }
        .table-card-header {
            background: var(--bg-dark);
            color: white;
            padding: 12px 16px;
            font-size: 13px;
            font-weight: 600;
        }
        .table-card-header i { margin-right: 8px; color: var(--primary-light); }
        .table-card-body {
            padding: 0;
            max-height: 200px;
            overflow-y: auto;
        }
        .table-card-body table { width: 100%; border-collapse: collapse; }
        .table-card-body td {
            padding: 10px 16px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        .table-card-body tr:last-child td { border-bottom: none; }
        .table-card-body tr:hover { background: rgba(37,99,235,0.05); }
        /* Coda Tab */
        .coda-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .btn-turno {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
            background: var(--bg-secondary);
            border: 2px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-turno:hover {
            border-color: var(--primary);
            background: rgba(37,99,235,0.05);
        }
        .btn-turno .turno-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-turno .turno-letter {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
        }
        .btn-turno .turno-text {
            font-weight: 600;
            color: var(--text-primary);
        }
        .btn-turno .turno-count {
            background: var(--warning);
            color: white;
            padding: 6px 14px;
            border-radius: 100px;
            font-size: 13px;
            font-weight: 600;
        }
        .empty-state {
            text-align: center;
            padding: 48px;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
        .empty-state h4 { font-size: 18px; margin-bottom: 8px; color: var(--text-secondary); }
        /* Modal Pending */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active { display: flex; }
        .modal-pending {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            animation: modalSlide 0.3s ease;
        }
        @keyframes modalSlide {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .modal-pending h2 {
            margin: 0 0 8px 0;
            color: var(--text-primary);
            font-size: 24px;
        }
        .modal-pending .numero-pending {
            font-size: 48px;
            font-weight: 700;
            color: var(--primary);
            margin: 16px 0;
            letter-spacing: 4px;
        }
        .modal-pending .ambulatorio-info {
            background: rgba(37,99,235,0.1);
            border: 1px solid rgba(37,99,235,0.3);
            border-radius: 8px;
            padding: 12px;
            margin: 16px 0;
            font-size: 14px;
            color: var(--primary);
        }
        .modal-pending .ambulatorio-info i { margin-right: 8px; }
        .modal-pending .modal-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 24px;
        }
        .modal-pending .modal-btn {
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
        }
        .modal-pending .modal-btn i { font-size: 24px; }
        .modal-pending .btn-annulla {
            background: var(--bg-tertiary);
            color: var(--danger);
            border: 2px solid var(--danger);
        }
        .modal-pending .btn-annulla:hover { background: rgba(239,68,68,0.1); }
        .modal-pending .btn-inoltra {
            background: var(--success);
            color: white;
        }
        .modal-pending .btn-inoltra:hover { background: #059669; }
        .modal-pending .warning-text {
            margin-top: 16px;
            font-size: 13px;
            color: var(--warning);
        }
        /* Responsive */
        @media (max-width: 600px) {
            body { padding: 12px; }
            .action-buttons { grid-template-columns: 1fr; }
            .tables-row { grid-template-columns: 1fr; }
            .number-value { font-size: 42px; }
            .tab-content { padding: 20px; }
        }
    </style>
    <script src="jquery-3.6.0.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fa-solid fa-computer"></i> MySanitario Postazione</h1>
            <div class="postazione-badge">
                <i class="fa-solid fa-desktop"></i>
                <?php echo htmlspecialchars($nomePostazione); ?>
            </div>
            <?php if (!empty($serviziAssociati) || !empty($turniAssociati)): ?>
            <div class="servizi-info">
                <?php if (!empty($serviziAssociati)): ?>
                <div class="info-group">
                    <i class="fa-solid fa-concierge-bell"></i>
                    <span class="info-label">Servizi:</span>
                    <div class="info-tags">
                        <?php foreach ($serviziAssociati as $serv): ?>
                        <span class="info-tag"><?= htmlspecialchars($serv) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($turniAssociati)): ?>
                <div class="info-group">
                    <i class="fa-solid fa-ticket"></i>
                    <span class="info-label">Turni:</span>
                    <div class="info-tags">
                        <?php foreach ($turniAssociati as $turno): ?>
                        <span class="info-tag turno"><?= htmlspecialchars($turno) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="tabs">
            <button class="tab active" onclick="showTab(0)">
                <i class="fa-solid fa-phone"></i> Chiamata
            </button>
            <button class="tab" onclick="showTab(1)">
                <i class="fa-solid fa-users"></i> Coda
            </button>
        </div>

        <div class="content-box">
            <!-- TAB CHIAMATA -->
            <div class="tab-content active" id="tab-chiamata">
                <div class="action-buttons">
                    <button class="action-btn btn-richiama" id="richiama">
                        <i class="fa-solid fa-rotate-right"></i>
                        <span>Richiama</span>
                    </button>
                    <button class="action-btn" id="chiama">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Chiama</span>
                    </button>
                    <button class="action-btn btn-trasferisci" id="trasferisci">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        <span>Trasferisci</span>
                    </button>
                </div>

                <div class="number-display">
                    <div class="number-label">Numero Chiamato</div>
                    <div class="number-value" id="numero-chiamato">---</div>
                </div>

                <div class="info-box" id="messaggi-box">
                    In attesa di aggiornamenti dalla coda...
                </div>

                <div class="tables-row">
                    <div class="table-card">
                        <div class="table-card-header">
                            <i class="fa-solid fa-user"></i> Chiamati da te
                        </div>
                        <div class="table-card-body">
                            <table>
                                <tbody id="ultimi-numeri-chiamati">
                                    <tr><td style="color:var(--text-muted);text-align:center;">Nessuna chiamata</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="table-card">
                        <div class="table-card-header">
                            <i class="fa-solid fa-users"></i> Ultimi chiamati
                        </div>
                        <div class="table-card-body">
                            <table>
                                <tbody id="ultimi-numeri-chiamati-generali">
                                    <tr><td style="color:var(--text-muted);text-align:center;">Nessuna chiamata</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB CODA -->
            <div class="tab-content" id="tab-coda">
                <div class="coda-buttons" id="bottoni-box">
                    <div class="empty-state">
                        <i class="fa-regular fa-clock"></i>
                        <h4>Caricamento coda...</h4>
                        <p>Attendere l aggiornamento</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pending -->
    <div class="modal-overlay" id="modalPending">
        <div class="modal-pending">
            <h2><i class="fa-solid fa-exclamation-triangle" style="color:var(--warning);"></i> Chiamata in sospeso</h2>
            <p>Devi prima gestire questa chiamata:</p>
            <div class="numero-pending" id="pendingNumero">---</div>
            <div class="ambulatorio-info" id="pendingAmbulatorio">
                <i class="fa-solid fa-hospital-user"></i>
                <span id="pendingAmbNome">Ambulatorio</span>
            </div>
            <div class="modal-actions">
                <button class="modal-btn btn-annulla" onclick="resolvePending('annullata')">
                    <i class="fa-solid fa-times-circle"></i>
                    <span>Annulla Chiamata</span>
                    <small style="font-weight:400;font-size:11px;">Utente non presentato</small>
                </button>
                <button class="modal-btn btn-inoltra" onclick="resolvePending('inoltrata')">
                    <i class="fa-solid fa-arrow-right"></i>
                    <span>Inoltra</span>
                    <small style="font-weight:400;font-size:11px;">Invia all'ambulatorio</small>
                </button>
            </div>
            <p class="warning-text"><i class="fa-solid fa-info-circle"></i> Devi fare una scelta prima di chiamare un altro numero</p>
            <input type="hidden" id="pendingId" value="">
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

        // Richiama
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
                    alert('Errore durante la richiamata');
                }
            });
        });

        // Chiama - verifica prima se c'è una chiamata pending
        document.getElementById('chiama').addEventListener('click', function() {
            // Prima verifica se c'è una chiamata pending da gestire
            $.ajax({
                url: 'gestisci_pending.php',
                method: 'POST',
                data: { postazione: <?php echo $postazione; ?>, action: 'check' },
                success: function(response) {
                    let result;
                    try {
                        result = JSON.parse(response);
                    } catch(e) {
                        // Se errore parsing, procedi con chiamata normale
                        eseguiChiamata();
                        return;
                    }
                    
                    if (result.hasPending) {
                        // C'è una chiamata pending - mostra modal
                        showPendingModal(result.pending);
                    } else {
                        // Nessun pending - procedi con chiamata
                        eseguiChiamata();
                    }
                },
                error: function() {
                    // In caso di errore, procedi comunque
                    eseguiChiamata();
                }
            });
        });

        // Funzione per eseguire la chiamata effettiva
        function eseguiChiamata(turnoSpecifico) {
            let data = { postazione: <?php echo $postazione; ?> };
            if (turnoSpecifico) {
                data.turno = turnoSpecifico;
            }
            
            $.ajax({
                url: 'chiamadapostazione.php',
                method: 'POST',
                data: data,
                success: function(response) {
                    if (response && response.indexOf('-001') === -1 && response.indexOf('--001') === -1 && response.trim() !== '') {
                        $('#numero-chiamato').html(response);
                        
                        // Estrai turno e numero dalla risposta (es. "C-005")
                        let parts = response.split('-');
                        if (parts.length >= 2) {
                            let turnoChiamato = parts[0];
                            let numeroChiamato = parseInt(parts[1]);
                            
                            // Verifica se questo turno ha un ambulatorio associato
                            verificaAmbulatorioDopoChiamata(turnoChiamato, numeroChiamato);
                        }
                    } else {
                        alert('Nessun numero in coda');
                    }
                },
                error: function() {
                    alert('Errore durante la chiamata');
                }
            });
        }

        // Verifica se il turno chiamato ha un ambulatorio e crea pending
        function verificaAmbulatorioDopoChiamata(turno, numero) {
            $.ajax({
                url: 'verifica_ambulatorio.php',
                method: 'POST',
                data: { turno: turno },
                success: function(response) {
                    let result;
                    try {
                        result = JSON.parse(response);
                    } catch(e) {
                        return;
                    }
                    
                    if (result.hasAmbulatorio) {
                        // Crea chiamata pending
                        $.ajax({
                            url: 'gestisci_pending.php',
                            method: 'POST',
                            data: {
                                postazione: <?php echo $postazione; ?>,
                                action: 'create',
                                turno: turno,
                                numero: numero,
                                id_turno: 0, // Verrà recuperato dal server
                                id_ambulatorio: result.ambulatorio.id
                            }
                        });
                    }
                }
            });
        }

        // Mostra modal pending
        function showPendingModal(pending) {
            $('#pendingNumero').text(pending.turno + '-' + String(pending.numero).padStart(3, '0'));
            $('#pendingAmbNome').text(pending.ambulatorio || 'Ambulatorio');
            $('#pendingId').val(pending.id);
            
            if (!pending.ambulatorio) {
                $('#pendingAmbulatorio').hide();
            } else {
                $('#pendingAmbulatorio').show();
            }
            
            $('#modalPending').addClass('active');
        }

        // Risolvi pending
        function resolvePending(resolution) {
            let idPending = $('#pendingId').val();
            
            $.ajax({
                url: 'gestisci_pending.php',
                method: 'POST',
                data: {
                    postazione: <?php echo $postazione; ?>,
                    action: 'resolve',
                    id_pending: idPending,
                    resolution: resolution
                },
                success: function(response) {
                    let result;
                    try {
                        result = JSON.parse(response);
                    } catch(e) {
                        alert('Errore nella risposta');
                        return;
                    }
                    
                    $('#modalPending').removeClass('active');
                    
                    if (result.success) {
                        if (resolution === 'inoltrata') {
                            alert('Numero inoltrato all\'ambulatorio');
                        } else {
                            alert('Chiamata annullata');
                        }
                    } else {
                        alert('Errore: ' + result.message);
                    }
                },
                error: function() {
                    alert('Errore durante l\'operazione');
                }
            });
        }

        // Trasferisci
        document.getElementById('trasferisci').addEventListener('click', function() {
            $.ajax({
                url: 'trasferiscinumero.php',
                method: 'POST',
                data: { postazione: <?php echo $postazione; ?>, action: 'get_turni' },
                success: function(response) {
                    let turni;
                    try {
                        turni = JSON.parse(response);
                    } catch(e) {
                        alert('Errore nel caricamento dei turni');
                        return;
                    }
                    if (turni.length === 0) {
                        alert('Nessun turno disponibile');
                        return;
                    }
                    let msg = 'Seleziona il turno di destinazione:\n\n';
                    turni.forEach(function(t, i) {
                        msg += (i+1) + ') ' + t.turno + ' - ' + t.descrizione + '\n';
                    });
                    msg += '\nInserisci la lettera del turno:';
                    let scelta = prompt(msg);
                    if (scelta && scelta.trim() !== '') {
                        $.ajax({
                            url: 'trasferiscinumero.php',
                            method: 'POST',
                            data: { postazione: <?php echo $postazione; ?>, turno_destinazione: scelta.trim().toUpperCase(), action: 'transfer' },
                            success: function(resp) {
                                let result;
                                try {
                                    result = JSON.parse(resp);
                                } catch(e) {
                                    alert('Errore nella risposta');
                                    return;
                                }
                                if (result.success) {
                                    alert(result.message);
                                } else {
                                    alert('Errore: ' + result.message);
                                }
                            },
                            error: function() {
                                alert('Errore durante il trasferimento');
                            }
                        });
                    }
                },
                error: function() {
                    alert('Errore nel caricamento dei turni');
                }
            });
        });

        // Polling coda
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
                        return;
                    }
                    let htmlInfo = '';
                    let htmlBottoni = '';
                    items.forEach(function(item) {
                        htmlInfo += '<strong>' + item.turno + '</strong>: ' + item.numero + ' in coda &nbsp;&nbsp;';
                        htmlBottoni += '<button class="btn-turno" onclick="gestisciTurno(\'' + item.turno + '\')">' +
                            '<div class="turno-info">' +
                            '<div class="turno-letter">' + item.turno + '</div>' +
                            '<div class="turno-text">Chiama turno ' + item.turno + '</div>' +
                            '</div>' +
                            '<div class="turno-count">' + item.numero + ' in coda</div>' +
                            '</button>';
                    });
                    if (htmlInfo === '') {
                        htmlInfo = '<span style="color:var(--text-muted);">Nessun utente in coda</span>';
                        htmlBottoni = '<div class="empty-state"><i class="fa-regular fa-face-smile"></i><h4>Nessun utente in coda</h4><p>La coda e vuota</p></div>';
                    } else {
                        $('#messaggi-box').addClass('lampeggia');
                        setTimeout(function() {
                            $('#messaggi-box').removeClass('lampeggia');
                        }, 1500);
                    }
                    $('#messaggi-box').html(htmlInfo);
                    $('#bottoni-box').html(htmlBottoni);
                },
                error: function() {
                    console.error('Errore aggiornamento coda');
                }
            });
        }, 3000);

        // Polling chiamate
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
                        return;
                    }
                    let html = '';
                    let html2 = '';
                    if (items.postazione && items.postazione.length > 0) {
                        items.postazione.forEach(function(itemp) {
                            html += '<tr><td><strong>' + itemp.turno + '</strong> - ' + itemp.numero + '</td></tr>';
                        });
                    } else {
                        html = '<tr><td style="color:var(--text-muted);text-align:center;">Nessuna chiamata</td></tr>';
                    }
                    if (items.chiamati && items.chiamati.length > 0) {
                        items.chiamati.forEach(function(itemc) {
                            html2 += '<tr><td><strong>' + itemc.turno + '</strong> - ' + itemc.numero + '</td></tr>';
                        });
                    } else {
                        html2 = '<tr><td style="color:var(--text-muted);text-align:center;">Nessuna chiamata</td></tr>';
                    }
                    $('#ultimi-numeri-chiamati').html(html);
                    $('#ultimi-numeri-chiamati-generali').html(html2);
                },
                error: function() {
                    console.error('Errore aggiornamento chiamate');
                }
            });
        }, 3000);

        function gestisciTurno(turno) {
            // Prima verifica se c'è una chiamata pending da gestire
            $.ajax({
                url: 'gestisci_pending.php',
                method: 'POST',
                data: { postazione: <?php echo $postazione; ?>, action: 'check' },
                success: function(response) {
                    let result;
                    try {
                        result = JSON.parse(response);
                    } catch(e) {
                        eseguiChiamata(turno);
                        return;
                    }
                    
                    if (result.hasPending) {
                        showPendingModal(result.pending);
                    } else {
                        eseguiChiamata(turno);
                    }
                },
                error: function() {
                    eseguiChiamata(turno);
                }
            });
        }
    </script>
</body>
</html>