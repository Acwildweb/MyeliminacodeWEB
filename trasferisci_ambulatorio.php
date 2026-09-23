<?php

include 'connect.php';

$postazione = $_POST["postazione_destinazione"] ?? '';
$numero = $_POST["numero"] ?? '';

if ($numero != '') {
    $turno = explode('-', $numero)[0];
    $numero2 = explode('-', $numero)[1];
}

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

$sql = "select * from turni where turno = '" . $turno . "'";

$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    exit;
}
if ($row = mysqli_fetch_assoc($rs)) {
    $idturno = $row['ID_turno'];
} else {
    exit;
}

$sql = "insert into coda_ambulatori (turno, numero, sportello, id_turno, id_postazione, 
    ipmonitor, dachiamare, orariochiamata) values ('" . $turno . "', '" . $numero2 . "', '" . $postazione . "', 
    " . $idturno . ", " . $idpostazione . ", '', '0', NULL)";
mysqli_query($conn, $sql);
mysqli_close($conn);
echo "OK";