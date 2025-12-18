<?php
/**
 * Verifica se un turno ha un ambulatorio associato (tramite operazione)
 * Restituisce le info dell'ambulatorio se presente
 */
include 'connect.php';

header('Content-Type: application/json');

$idTurno = intval($_POST["id_turno"] ?? 0);
$turno = $_POST["turno"] ?? '';

if ($idTurno <= 0 && $turno === '') {
    echo json_encode(['success' => false, 'message' => 'Turno non specificato']);
    exit;
}

// Se abbiamo solo la lettera del turno, recupera l'ID
if ($idTurno <= 0 && $turno !== '') {
    $turno = mysqli_real_escape_string($conn, $turno);
    $sql = "SELECT ID_turno FROM turni WHERE turno = '$turno' AND stato = 'A'";
    $rs = mysqli_query($conn, $sql);
    if ($rs && mysqli_num_rows($rs) > 0) {
        $row = mysqli_fetch_assoc($rs);
        $idTurno = $row['ID_turno'];
    } else {
        echo json_encode(['success' => true, 'hasAmbulatorio' => false]);
        exit;
    }
}

// Cerca ambulatorio associato al turno tramite: turno -> operazioni_turni -> operazioni_ambulatori -> ambulatori
$sql = "SELECT DISTINCT a.ID_ambulatorio, a.nome, a.descrizione 
        FROM ambulatori a 
        INNER JOIN operazioni_ambulatori oa ON a.ID_ambulatorio = oa.id_ambulatorio 
        INNER JOIN operazioni_turni ot ON oa.id_operazione = ot.id_operazione 
        WHERE ot.id_turno = $idTurno AND a.stato = 'A' 
        LIMIT 1";

$rs = mysqli_query($conn, $sql);

if ($rs && mysqli_num_rows($rs) > 0) {
    $row = mysqli_fetch_assoc($rs);
    echo json_encode([
        'success' => true,
        'hasAmbulatorio' => true,
        'ambulatorio' => [
            'id' => $row['ID_ambulatorio'],
            'nome' => $row['nome'],
            'descrizione' => $row['descrizione']
        ]
    ]);
} else {
    echo json_encode(['success' => true, 'hasAmbulatorio' => false]);
}

mysqli_close($conn);
?>
