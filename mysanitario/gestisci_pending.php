<?php
/**
 * Gestisce le chiamate pending - verifica se c'è una chiamata in attesa di decisione
 * e permette di risolvere (annullare o inoltrare)
 */
include 'connect.php';

header('Content-Type: application/json');

$postazione = intval($_POST["postazione"] ?? 0);
$action = $_POST["action"] ?? 'check';

if ($postazione <= 0) {
    echo json_encode(['success' => false, 'message' => 'Postazione non specificata']);
    exit;
}

// Verifica che la postazione esista
$sql = "SELECT * FROM postazioni WHERE ID_postazione = $postazione";
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    echo json_encode(['success' => false, 'message' => 'Postazione non trovata']);
    exit;
}

// ACTION: check - verifica se c'è una chiamata pending
if ($action === 'check') {
    $sql = "SELECT cp.*, a.nome as nome_ambulatorio 
            FROM chiamate_pending cp 
            LEFT JOIN ambulatori a ON cp.id_ambulatorio = a.ID_ambulatorio 
            WHERE cp.id_postazione = $postazione AND cp.stato = 'PENDING' 
            ORDER BY cp.created_at DESC LIMIT 1";
    $rs = mysqli_query($conn, $sql);
    
    if ($rs && mysqli_num_rows($rs) > 0) {
        $row = mysqli_fetch_assoc($rs);
        echo json_encode([
            'success' => true,
            'hasPending' => true,
            'pending' => [
                'id' => $row['ID_pending'],
                'turno' => $row['turno'],
                'numero' => $row['numero'],
                'ambulatorio' => $row['nome_ambulatorio'],
                'id_ambulatorio' => $row['id_ambulatorio'],
                'created_at' => $row['created_at']
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'hasPending' => false]);
    }
    exit;
}

// ACTION: resolve - risolve una chiamata pending
if ($action === 'resolve') {
    $idPending = intval($_POST['id_pending'] ?? 0);
    $resolution = $_POST['resolution'] ?? ''; // 'annullata' o 'inoltrata'
    
    if ($idPending <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID pending non specificato']);
        exit;
    }
    
    if (!in_array($resolution, ['annullata', 'inoltrata'])) {
        echo json_encode(['success' => false, 'message' => 'Risoluzione non valida']);
        exit;
    }
    
    // Recupera la chiamata pending
    $sql = "SELECT * FROM chiamate_pending WHERE ID_pending = $idPending AND id_postazione = $postazione AND stato = 'PENDING'";
    $rs = mysqli_query($conn, $sql);
    if (!$rs || mysqli_num_rows($rs) == 0) {
        echo json_encode(['success' => false, 'message' => 'Chiamata pending non trovata o già risolta']);
        exit;
    }
    $pending = mysqli_fetch_assoc($rs);
    
    if ($resolution === 'annullata') {
        // Aggiorna stato a ANNULLATA
        $sql = "UPDATE chiamate_pending SET stato = 'ANNULLATA', resolved_at = NOW() WHERE ID_pending = $idPending";
        mysqli_query($conn, $sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Chiamata annullata',
            'resolution' => 'annullata'
        ]);
    } else if ($resolution === 'inoltrata') {
        // Aggiorna stato a INOLTRATA
        $sql = "UPDATE chiamate_pending SET stato = 'INOLTRATA', resolved_at = NOW() WHERE ID_pending = $idPending";
        mysqli_query($conn, $sql);
        
        // Recupera info postazione per lo sportello
        $sql = "SELECT postazione FROM postazioni WHERE ID_postazione = $postazione";
        $rs = mysqli_query($conn, $sql);
        $postRow = mysqli_fetch_assoc($rs);
        $sportello = $postRow['postazione'] ?? $postazione;
        
        // Inserisci nella coda ambulatorio
        $idAmb = $pending['id_ambulatorio'];
        $idTurno = $pending['id_turno'];
        $turno = mysqli_real_escape_string($conn, $pending['turno']);
        $numero = $pending['numero'];
        
        // Verifica struttura tabella coda_ambulatori e inserisci
        $sql = "INSERT INTO coda_ambulatori (numero, sportello, turno, id_turno, id_postazione, dachiamare) 
                VALUES ('$turno-" . str_pad($numero, 3, '0', STR_PAD_LEFT) . "', '$sportello', '$turno', $idTurno, $postazione, 'S')";
        mysqli_query($conn, $sql);
        
        echo json_encode([
            'success' => true,
            'message' => 'Numero inoltrato all\'ambulatorio',
            'resolution' => 'inoltrata',
            'numero' => $turno . '-' . str_pad($numero, 3, '0', STR_PAD_LEFT)
        ]);
    }
    exit;
}

// ACTION: create - crea una nuova chiamata pending (chiamato dopo una chiamata con ambulatorio associato)
if ($action === 'create') {
    $idTurno = intval($_POST['id_turno'] ?? 0);
    $turno = mysqli_real_escape_string($conn, $_POST['turno'] ?? '');
    $numero = intval($_POST['numero'] ?? 0);
    $idAmbulatorio = intval($_POST['id_ambulatorio'] ?? 0);
    
    if ($turno === '' || $numero <= 0) {
        echo json_encode(['success' => false, 'message' => 'Dati incompleti']);
        exit;
    }
    
    // Se non abbiamo l'id_turno, lo recuperiamo dal nome del turno
    if ($idTurno <= 0 && $turno !== '') {
        $sql = "SELECT ID_turno FROM turni WHERE turno = '$turno' AND stato = 'A' LIMIT 1";
        $rs = mysqli_query($conn, $sql);
        if ($rs && mysqli_num_rows($rs) > 0) {
            $row = mysqli_fetch_assoc($rs);
            $idTurno = $row['ID_turno'];
        }
    }
    
    if ($idTurno <= 0) {
        echo json_encode(['success' => false, 'message' => 'Turno non trovato']);
        exit;
    }
    
    // Inserisci chiamata pending
    $sql = "INSERT INTO chiamate_pending (id_postazione, id_turno, turno, numero, id_ambulatorio, stato) 
            VALUES ($postazione, $idTurno, '$turno', $numero, " . ($idAmbulatorio > 0 ? $idAmbulatorio : 'NULL') . ", 'PENDING')";
    
    if (mysqli_query($conn, $sql)) {
        $idPending = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'id_pending' => $idPending,
            'message' => 'Chiamata pending creata'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore: ' . mysqli_error($conn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta']);
mysqli_close($conn);
?>
