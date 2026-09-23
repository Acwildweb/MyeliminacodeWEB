<?php
include 'connect.php';

$nmonitor = $_POST["ipmonitor"];
$postazione = $_POST["postazione"];

$sql = "select * from coda_ambulatori where (ipmonitor not like '%".$nmonitor."%' or ipmonitor is null) and dachiamare = '1' and sportello = ".$postazione." order by id limit 1";
$rs = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($rs)) {
    $numero = $row['numero'];
    $turno = $row['turno'];
    $sportello = $row['sportello'];
    $idturno = $row['id_turno'];
    $idpostazione = $row['id_postazione'];
    $id = $row['ID'];

    $sql = "UPDATE coda_ambulatori SET ipmonitor = concat('" . $nmonitor . ";', ifnull(ipmonitor, '')), orariochiamata = current_timestamp WHERE ID = $id";
    $rs = mysqli_query($conn, $sql);

    $sql = "select * from postazioni where postazione = ".$sportello;
    $rs2 = mysqli_query($conn, $sql);
    if ($row2 = mysqli_fetch_assoc($rs2)) {
        $lsportello = $row2['postazione'];
    }
    mysqli_close($conn);

    $testo  = $turno . "|" . $numero . "|" . $lsportello . "|" . $lsportello . "|" . $idturno . "|" . date("H:i");
    echo $testo;
} else {
    mysqli_close($conn);
}
?>