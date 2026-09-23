<?php
include 'connect.php';
$postazione = $_POST["postazione_destinazione"] ?? '';
$numero = $_POST["numero"] ?? '';
if ($numero != '') {
    $turno = explode('-', $numero)[0];
    $numero2 = explode('-', $numero)[1];
}
$sql = "select * from postazioni where postazione = '" . mysqli_real_escape_string($conn,$postazione) . "'";
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) exit;
$row = mysqli_fetch_assoc($rs);
$idpostazione = $row['ID_postazione'];
$sql = "select * from turni where turno = '" . mysqli_real_escape_string($conn,$turno) . "'";
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) exit;
$row = mysqli_fetch_assoc($rs);
$idturno = $row['ID_turno'];
$sql = "insert into coda_ambulatori (turno, numero, sportello, id_turno, id_postazione, ipmonitor, dachiamare, orariochiamata) values ('" . mysqli_real_escape_string($conn,$turno) . "', '" . mysqli_real_escape_string($conn,$numero2) . "', '" . mysqli_real_escape_string($conn,$postazione) . "', " . intval($idturno) . ", " . intval($idpostazione) . ", '', '0', NULL)";
mysqli_query($conn, $sql);
mysqli_close($conn);
echo "OK";
