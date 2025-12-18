<?php
/**
 * Trasferisce l'ultimo numero chiamato dalla postazione ad un altro turno
 * Il trasferimento è abilitato SOLO per le postazioni che gestiscono il LABORATORIO ANALISI
 * e solo per numeri del turno Laboratorio Analisi
 */
include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$turnoDestinazione = $_POST["turno_destinazione"] ?? '';
$action = $_POST["action"] ?? 'transfer';
$idpostazione = intval($postazione);

if ($idpostazione <= 0) {
    echo json_encode(['success' => false, 'message' => 'Postazione non specificata']);
    exit;
}

// Verifica che la postazione esista (la postazione passata è già l'ID)
$sql = "SELECT ID_postazione FROM postazioni WHERE ID_postazione = " . $idpostazione;
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    echo json_encode(['success' => false, 'message' => 'Postazione non trovata']);
    exit;
}

// Verifica se questa postazione gestisce il LABORATORIO ANALISI
$sql = "SELECT COUNT(*) as cnt 
        FROM operazioni_postazioni op 
        INNER JOIN operazioni o ON op.id_operazione = o.ID_operazione 
        WHERE op.id_postazione = $idpostazione 
        AND o.operazione LIKE '%LABORATORIO%'";
$rs = mysqli_query($conn, $sql);
$row = mysqli_fetch_assoc($rs);
$isLaboratorio = ($row['cnt'] > 0);

if (!$isLaboratorio) {
    echo json_encode(['success' => false, 'message' => 'Trasferimento non abilitato per questa postazione']);
    mysqli_close($conn);
    exit;
}

// Trova il turno LABORATORIO ANALISI
$sql = "SELECT t.ID_turno, t.turno FROM turni t 
        INNER JOIN operazioni_turni ot ON t.ID_turno = ot.id_turno 
        INNER JOIN operazioni o ON ot.id_operazione = o.ID_operazione 
        WHERE o.operazione LIKE '%LABORATORIO%' AND t.stato = 'A' LIMIT 1";
$rs = mysqli_query($conn, $sql);
$turnoLaboratorio = null;
$idTurnoLaboratorio = 0;
if ($rs && mysqli_num_rows($rs) > 0) {
    $row = mysqli_fetch_assoc($rs);
    $idTurnoLaboratorio = $row['ID_turno'];
    $turnoLaboratorio = $row['turno'];
}

// Se action è 'get_turni', restituisce la lista dei turni a cui può trasferire
// (tutti i turni tranne il Laboratorio Analisi)
if ($action === 'get_turni') {
    $turni = [];
    
    // Trova i turni attivi diversi dal Laboratorio Analisi
    $sql = "SELECT DISTINCT t.ID_turno, t.turno, t.desstato 
            FROM turni t 
            WHERE t.stato = 'A'
            AND t.ID_turno != $idTurnoLaboratorio
            ORDER BY t.priorita, t.turno";
    
    $rs = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($rs)) {
        $turni[] = [
            'id' => $row['ID_turno'],
            'turno' => $row['turno'],
            'descrizione' => $row['desstato']
        ];
    }
    
    header('Content-Type: application/json');
    echo json_encode($turni);
    mysqli_close($conn);
    exit;
}

// Trasferimento numero
if ($turnoDestinazione == '') {
    echo json_encode(['success' => false, 'message' => 'Turno destinazione non specificato']);
    exit;
}

// Trova l'ultimo numero chiamato da questa postazione (DEVE essere del turno Laboratorio)
$sql = "SELECT c.numero, c.turno, c.id_turno 
        FROM coda c 
        WHERE c.id_postazione = $idpostazione AND c.sportello != '0'
        ORDER BY c.ID DESC 
        LIMIT 1";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    echo json_encode(['success' => false, 'message' => 'Nessun numero chiamato da trasferire']);
    exit;
}
$row = mysqli_fetch_assoc($rs);
$numeroTrasferire = $row['numero'];
$turnoOrigine = $row['turno'];
$idTurnoOrigine = $row['id_turno'];

// Verifica che il numero sia del turno Laboratorio Analisi
if ($idTurnoOrigine != $idTurnoLaboratorio) {
    echo json_encode(['success' => false, 'message' => 'Puoi trasferire solo numeri del Laboratorio Analisi']);
    exit;
}

// Trova il turno di destinazione
$sql = "SELECT ID_turno, turno FROM turni WHERE turno = '" . mysqli_real_escape_string($conn, $turnoDestinazione) . "' AND stato = 'A'";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    echo json_encode(['success' => false, 'message' => 'Turno destinazione non valido']);
    exit;
}
$row = mysqli_fetch_assoc($rs);
$idTurnoDestinazione = $row['ID_turno'];
$turnoDestinazioneNome = $row['turno'];

if ($idTurnoOrigine == $idTurnoDestinazione) {
    echo json_encode(['success' => false, 'message' => 'Il numero è già in questo turno']);
    exit;
}

// Non si può trasferire al Laboratorio (non ha senso)
if ($idTurnoDestinazione == $idTurnoLaboratorio) {
    echo json_encode(['success' => false, 'message' => 'Non puoi trasferire al Laboratorio Analisi']);
    exit;
}

// Inserisci il numero trasferito nella coda del turno destinazione
// Il numero mantiene lo stesso valore originale ma va nel nuovo turno
$sql = "INSERT INTO coda (numero, sportello, turno, id_turno, id_postazione, ipmonitor) 
        VALUES ('$numeroTrasferire', '0', '$turnoDestinazioneNome', $idTurnoDestinazione, 0, 'TRASF:$turnoOrigine>$turnoDestinazioneNome')";
mysqli_query($conn, $sql);

// Incrementa il contatore CODA del turno destinazione se necessario
$sql = "SELECT numero FROM contatori WHERE tipo='CODA' AND id_turno = $idTurnoDestinazione";
$rs = mysqli_query($conn, $sql);
if ($rs && mysqli_num_rows($rs) > 0) {
    $row = mysqli_fetch_assoc($rs);
    $numCoda = $row['numero'];
    // Aggiorna solo se il numero trasferito è maggiore del contatore attuale
    if ($numeroTrasferire > $numCoda) {
        $sql = "UPDATE contatori SET numero = $numeroTrasferire WHERE tipo='CODA' AND id_turno = $idTurnoDestinazione";
        mysqli_query($conn, $sql);
    }
}

echo json_encode([
    'success' => true, 
    'message' => "Numero $turnoOrigine-" . str_pad($numeroTrasferire, 3, '0', STR_PAD_LEFT) . " trasferito a $turnoDestinazioneNome",
    'nuovo_turno' => $turnoDestinazioneNome
]);

mysqli_close($conn);
