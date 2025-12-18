<?php
/**
 * MySanitario Totem Web
 * Versione web del totem eliminacode
 * 
 * Funzionalità:
 * - Mostra pulsanti per i turni disponibili
 * - Genera nuovi numeri al click
 * - Mostra il numero generato
 * - Opzionale: stampa biglietto
 */

require_once __DIR__ . '/bootstrap.php';

use MySanitario\Sync\Database;
use MySanitario\Sync\Logger;

// Inizializzazione
$db = Database::getInstance();
$logger = Logger::getInstance();

// Gestione richieste AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => ''];
    
    switch ($action) {
        case 'get_new_number':
            $idTurno = (int)($_POST['id_turno'] ?? 0);
            if ($idTurno > 0) {
                $result = getNewNumber($db, $idTurno);
                $response = $result;
            } else {
                $response['message'] = 'ID turno non valido';
            }
            break;
            
        case 'get_turni':
            $turni = getTurniAttivi($db);
            $response = ['success' => true, 'turni' => $turni];
            break;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Ottiene i turni attivi per oggi
 */
function getTurniAttivi(Database $db): array {
    $today = date('Ymd');
    $dayOfWeek = date('N'); // 1=Lun, 7=Dom
    $currentTime = date('H:i');
    
    // Query per ottenere i turni con la configurazione del totem
    $sql = "SELECT t.*, 
                   ct.sfondobutton, ct.fontturno, ct.sizeturno, ct.boldturno,
                   ct.coloreturno, ct.descrizioneturno, ct.fontdescr, ct.sizedescr,
                   ct.bolddescr, ct.coloredescr, ct.visibile
            FROM turni t
            LEFT JOIN configturnitotem ct ON t.id_turno = ct.idturno
            WHERE t.attivo = 1
            ORDER BY t.ordine, t.id_turno";
    
    $result = $db->query($sql);
    $turni = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Verifica orari e giorni (se configurati)
            $turni[] = [
                'id_turno' => $row['id_turno'],
                'turno' => $row['turno'],
                'descrizione' => $row['descrizione'] ?? '',
                'lettera' => $row['lettera'] ?? substr($row['turno'], 0, 1),
                'sfondo_button' => $row['sfondobutton'] ?? '',
                'font_turno' => $row['fontturno'] ?? 'Arial',
                'size_turno' => $row['sizeturno'] ?? 24,
                'bold_turno' => $row['boldturno'] ?? 0,
                'colore_turno' => $row['coloreturno'] ?? '#FFFFFF',
                'descrizione_turno' => $row['descrizioneturno'] ?? $row['descrizione'],
                'font_descr' => $row['fontdescr'] ?? 'Arial',
                'size_descr' => $row['sizedescr'] ?? 14,
                'bold_descr' => $row['bolddescr'] ?? 0,
                'colore_descr' => $row['coloredescr'] ?? '#FFFFFF',
                'visibile' => $row['visibile'] ?? 1
            ];
        }
    }
    
    return $turni;
}

/**
 * Genera un nuovo numero per il turno specificato
 */
function getNewNumber(Database $db, int $idTurno): array {
    global $logger;
    
    $today = date('Ymd');
    $now = date('H:i:s');
    
    // Ottieni info turno
    $turnoResult = $db->query("SELECT * FROM turni WHERE id_turno = $idTurno");
    if (!$turnoResult || $turnoResult->num_rows === 0) {
        return ['success' => false, 'message' => 'Turno non trovato'];
    }
    $turno = $turnoResult->fetch_assoc();
    
    // Cerca il contatore CODA per questo turno e data
    $contResult = $db->query(
        "SELECT * FROM contatori WHERE tipo = 'CODA' AND id_turno = $idTurno AND data = '$today'"
    );
    
    if (!$contResult || $contResult->num_rows === 0) {
        // Crea il contatore se non esiste
        $db->execute(
            "INSERT INTO contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) 
             VALUES ('CODA', $idTurno, 0, 0, '$today', '', 0)"
        );
        $currentNumber = 0;
    } else {
        $contatore = $contResult->fetch_assoc();
        $currentNumber = (int)$contatore['numero'];
    }
    
    // Incrementa il numero
    $newNumber = $currentNumber + 1;
    
    // Aggiorna il contatore
    $db->execute(
        "UPDATE contatori SET numero = $newNumber, ora = '$now' 
         WHERE tipo = 'CODA' AND id_turno = $idTurno AND data = '$today'"
    );
    
    // Formatta il numero con la lettera del turno
    $lettera = $turno['lettera'] ?? substr($turno['turno'], 0, 1);
    $numeroFormattato = $lettera . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    
    $logger->info('Nuovo numero generato', [
        'turno' => $turno['turno'],
        'numero' => $numeroFormattato,
        'id_turno' => $idTurno
    ]);
    
    return [
        'success' => true,
        'numero' => $newNumber,
        'numero_formattato' => $numeroFormattato,
        'lettera' => $lettera,
        'turno' => $turno['turno'],
        'descrizione' => $turno['descrizione'] ?? '',
        'ora' => date('H:i'),
        'data' => date('d/m/Y')
    ];
}

// Carica configurazione totem
$configTotem = [];
$configResult = $db->query("SELECT * FROM configtotem LIMIT 1");
if ($configResult && $configResult->num_rows > 0) {
    $configTotem = $configResult->fetch_assoc();
}

// Carica turni per il rendering iniziale
$turni = getTurniAttivi($db);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <title>MySanitario - Totem Eliminacode</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }
        
        html, body {
            height: 100%;
            overflow: hidden;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: <?= !empty($configTotem['immaginesfondo']) ? 'url("media/images/sfondototem.jpg")' : 'linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%)' ?>;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .totem-container {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
        }
        
        .totem-header {
            text-align: center;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 20px;
        }
        
        .totem-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .totem-header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .turni-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            width: 100%;
            padding: 20px;
        }
        
        .turno-button {
            position: relative;
            background: linear-gradient(145deg, #3498db, #2980b9);
            border: none;
            border-radius: 20px;
            padding: 30px 20px;
            min-height: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .turno-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(145deg, rgba(255,255,255,0.1), transparent);
            pointer-events: none;
        }
        
        .turno-button:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.4);
        }
        
        .turno-button:active {
            transform: translateY(0) scale(0.98);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        
        .turno-button.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            filter: grayscale(50%);
        }
        
        .turno-lettera {
            font-size: 3rem;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 10px;
        }
        
        .turno-nome {
            font-size: 1.2rem;
            color: white;
            text-align: center;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .turno-descrizione {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.8);
            text-align: center;
            margin-top: 5px;
        }
        
        /* Modal per visualizzare il numero */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-overlay.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 30px;
            padding: 50px;
            text-align: center;
            max-width: 500px;
            width: 100%;
            animation: modalIn 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        @keyframes modalIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .modal-header {
            color: #333;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 1.5rem;
            color: #666;
        }
        
        .numero-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 5rem;
            font-weight: bold;
            padding: 30px 50px;
            border-radius: 20px;
            margin: 30px 0;
            text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }
        
        .ticket-info {
            color: #666;
            margin-bottom: 30px;
        }
        
        .ticket-info p {
            margin: 5px 0;
            font-size: 1.1rem;
        }
        
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 15px 40px;
            border: none;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        }
        
        .btn-close {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        /* Loading spinner */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        
        .loading.active {
            display: flex;
        }
        
        .spinner {
            width: 80px;
            height: 80px;
            border: 6px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Messaggio quando non ci sono turni */
        .no-turni {
            color: white;
            text-align: center;
            padding: 50px;
            font-size: 1.5rem;
        }
        
        /* Footer */
        .totem-footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            color: rgba(255,255,255,0.5);
            font-size: 0.9rem;
        }
        
        /* Colori turni predefiniti */
        .turno-button[data-color="A"] { background: linear-gradient(145deg, #e74c3c, #c0392b); }
        .turno-button[data-color="B"] { background: linear-gradient(145deg, #3498db, #2980b9); }
        .turno-button[data-color="C"] { background: linear-gradient(145deg, #2ecc71, #27ae60); }
        .turno-button[data-color="D"] { background: linear-gradient(145deg, #f39c12, #d68910); }
        .turno-button[data-color="E"] { background: linear-gradient(145deg, #9b59b6, #8e44ad); }
        .turno-button[data-color="F"] { background: linear-gradient(145deg, #1abc9c, #16a085); }
        .turno-button[data-color="G"] { background: linear-gradient(145deg, #e91e63, #c2185b); }
        .turno-button[data-color="H"] { background: linear-gradient(145deg, #00bcd4, #0097a7); }
        .turno-button[data-color="I"] { background: linear-gradient(145deg, #ff5722, #e64a19); }
        .turno-button[data-color="L"] { background: linear-gradient(145deg, #795548, #5d4037); }
        
        /* Responsive */
        @media (max-width: 600px) {
            .totem-header h1 {
                font-size: 1.8rem;
            }
            
            .turni-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                padding: 10px;
            }
            
            .turno-button {
                min-height: 120px;
                padding: 20px 15px;
            }
            
            .turno-lettera {
                font-size: 2.5rem;
            }
            
            .turno-nome {
                font-size: 1rem;
            }
            
            .numero-display {
                font-size: 3.5rem;
                padding: 20px 30px;
            }
            
            .modal-content {
                padding: 30px 20px;
            }
        }
        
        /* Portrait mode per tablet/totem verticale */
        @media (orientation: portrait) and (min-height: 800px) {
            .turni-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .turno-button {
                min-height: 180px;
            }
            
            .turno-lettera {
                font-size: 4rem;
            }
        }
    </style>
</head>
<body>
    <div class="totem-container">
        <div class="totem-header">
            <h1>🏥 Ritira il tuo numero</h1>
            <p>Seleziona il servizio desiderato</p>
        </div>
        
        <div class="turni-grid" id="turniGrid">
            <?php if (empty($turni)): ?>
                <div class="no-turni">
                    <p>⚠️ Nessun servizio disponibile al momento</p>
                    <p style="font-size: 1rem; margin-top: 10px;">Contattare l'assistenza</p>
                </div>
            <?php else: ?>
                <?php foreach ($turni as $turno): ?>
                    <?php if ($turno['visibile']): ?>
                    <button class="turno-button" 
                            data-id="<?= $turno['id_turno'] ?>"
                            data-color="<?= strtoupper($turno['lettera']) ?>"
                            onclick="richiediNumero(<?= $turno['id_turno'] ?>)">
                        <div class="turno-lettera"><?= htmlspecialchars(strtoupper($turno['lettera'])) ?></div>
                        <div class="turno-nome"><?= htmlspecialchars($turno['turno']) ?></div>
                        <?php if (!empty($turno['descrizione_turno'])): ?>
                        <div class="turno-descrizione"><?= htmlspecialchars($turno['descrizione_turno']) ?></div>
                        <?php endif; ?>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Loading -->
    <div class="loading" id="loading">
        <div class="spinner"></div>
    </div>
    
    <!-- Modal Numero -->
    <div class="modal-overlay" id="modalNumero">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTurno">Servizio</h2>
            </div>
            <div class="numero-display" id="numeroDisplay">A001</div>
            <div class="ticket-info">
                <p><strong>Data:</strong> <span id="ticketData">--/--/----</span></p>
                <p><strong>Ora:</strong> <span id="ticketOra">--:--</span></p>
            </div>
            <div class="modal-buttons">
                <button class="btn btn-primary" onclick="stampaTicket()">🖨️ Stampa</button>
                <button class="btn btn-close" onclick="chiudiModal()">✓ OK</button>
            </div>
        </div>
    </div>
    
    <!-- Area stampa nascosta -->
    <div id="printArea" style="display: none;">
        <div id="printContent" style="text-align: center; padding: 20px; font-family: Arial, sans-serif;">
            <h2 style="margin-bottom: 10px;">🏥 MySanitario</h2>
            <div style="font-size: 48px; font-weight: bold; margin: 20px 0;" id="printNumero">A001</div>
            <p style="font-size: 18px;" id="printTurno">Servizio</p>
            <p style="margin-top: 15px; font-size: 14px;">
                <span id="printData">--/--/----</span> - <span id="printOra">--:--</span>
            </p>
            <hr style="margin: 20px 0; border: 1px dashed #ccc;">
            <p style="font-size: 12px; color: #666;">Attendere la chiamata del proprio numero</p>
        </div>
    </div>
    
    <div class="totem-footer">
        MySanitario Totem Web &copy; <?= date('Y') ?>
    </div>
    
    <script>
        let ultimoNumero = null;
        
        async function richiediNumero(idTurno) {
            // Mostra loading
            document.getElementById('loading').classList.add('active');
            
            try {
                const formData = new FormData();
                formData.append('action', 'get_new_number');
                formData.append('id_turno', idTurno);
                
                const response = await fetch('totem.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    ultimoNumero = data;
                    mostraNumero(data);
                } else {
                    alert('Errore: ' + data.message);
                }
            } catch (error) {
                console.error('Errore:', error);
                alert('Errore di comunicazione con il server');
            } finally {
                document.getElementById('loading').classList.remove('active');
            }
        }
        
        function mostraNumero(data) {
            document.getElementById('modalTurno').textContent = data.turno;
            document.getElementById('numeroDisplay').textContent = data.numero_formattato;
            document.getElementById('ticketData').textContent = data.data;
            document.getElementById('ticketOra').textContent = data.ora;
            
            // Prepara area stampa
            document.getElementById('printNumero').textContent = data.numero_formattato;
            document.getElementById('printTurno').textContent = data.turno;
            document.getElementById('printData').textContent = data.data;
            document.getElementById('printOra').textContent = data.ora;
            
            // Mostra modal
            document.getElementById('modalNumero').classList.add('active');
            
            // Riproduci suono (opzionale)
            playSound();
        }
        
        function chiudiModal() {
            document.getElementById('modalNumero').classList.remove('active');
            ultimoNumero = null;
        }
        
        function stampaTicket() {
            const printContent = document.getElementById('printContent').innerHTML;
            const printWindow = window.open('', '_blank', 'width=300,height=400');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Ticket</title>
                    <style>
                        body { margin: 0; padding: 0; }
                        @media print {
                            body { margin: 0; }
                        }
                    </style>
                </head>
                <body>
                    ${printContent}
                    <script>
                        window.onload = function() {
                            window.print();
                            window.onafterprint = function() {
                                window.close();
                            };
                        };
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
        
        function playSound() {
            // Prova a riprodurre un suono di conferma
            try {
                const audio = new Audio('media/mp3/click.mp3');
                audio.volume = 0.5;
                audio.play().catch(() => {}); // Ignora errori se il file non esiste
            } catch (e) {}
        }
        
        // Chiudi modal cliccando fuori
        document.getElementById('modalNumero').addEventListener('click', function(e) {
            if (e.target === this) {
                chiudiModal();
            }
        });
        
        // Chiudi modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                chiudiModal();
            }
        });
        
        // Auto-chiudi modal dopo 10 secondi
        let autoCloseTimer;
        const modalObserver = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.target.classList.contains('active')) {
                    clearTimeout(autoCloseTimer);
                    autoCloseTimer = setTimeout(chiudiModal, 10000);
                }
            });
        });
        
        modalObserver.observe(document.getElementById('modalNumero'), {
            attributes: true,
            attributeFilter: ['class']
        });
    </script>
</body>
</html>
