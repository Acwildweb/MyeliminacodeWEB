<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);

include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$idpostazione = 0;

$sql = "select * from postazioni where postazione = '" . $postazione . "'";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    exit;
}
if ($row = mysqli_fetch_assoc($rs)) {
    $idpostazione = $row['ID_postazione'];
} else {
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
            'numero' => $numero,
            'postazione' => $row['postazione']
        ];
    }
}

$sql = "select c.*, p.postazione from coda c inner join postazioni p on c.id_postazione = p.ID_postazione order by c.ID desc limit 5";
        //echo $sql."<br />";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) > 0) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $turno = $row['turno'];
        $numero = $row['numero'];
        $coda['chiamati'][] = [
            'turno' => $turno,
            'numero' => $numero,
            'postazione' => $row['postazione']
        ];
    }
}
echo json_encode($coda);


mysqli_close($conn);