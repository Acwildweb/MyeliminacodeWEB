<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$idpostazione = intval($postazione);

// La postazione passata è già l'ID
if ($idpostazione <= 0) {
    echo json_encode(['postazione' => [], 'chiamati' => []]);
    exit;
}

// Verifica che la postazione esista
$sql = "SELECT * FROM postazioni WHERE ID_postazione = " . $idpostazione;
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    echo json_encode(['postazione' => [], 'chiamati' => []]);
    exit;
}

$coda['postazione'] = [];
$coda['chiamati'] = [];

$sql = "select * from coda where id_postazione = " . $idpostazione . " order by ID desc limit 5";
       // echo $sql."<br />";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) > 0) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $turno = $row['turno'];
        $numero = $row['numero'];
        $coda['postazione'][] = [
            'turno' => $turno,
            'numero' => $numero
        ];
    }
}

$sql = "select * from coda order by ID desc limit 5";
        //echo $sql."<br />";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) > 0) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $turno = $row['turno'];
        $numero = $row['numero'];
        $coda['chiamati'][] = [
            'turno' => $turno,
            'numero' => $numero
        ];
    }
}
echo json_encode($coda);


mysqli_close($conn);