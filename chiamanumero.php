<?php
include 'connect.php';
$nmonitor = $_POST["ipmonitor"];
$sql = "select * from coda where ipmonitor not like '%".$nmonitor."%' or ipmonitor is null order by id";
$rs = mysqli_query($conn, $sql);
if ($row = mysqli_fetch_assoc($rs)) {
    $numero = $row['numero'];
    $turno = $row['turno'];
    $sportello = $row['sportello'];
    $idturno = $row['id_turno'];
    $id = $row['ID'];
    $sql = "UPDATE coda SET ipmonitor = concat('" . $nmonitor . ";', ifnull(ipmonitor, '')) WHERE ID = $id";
    mysqli_query($conn, $sql);
    $sql = "select * from postazioni where postazione = ".$sportello;
    $rs2 = mysqli_query($conn, $sql);
    $lsportello = $sportello;
    if ($row2 = mysqli_fetch_assoc($rs2)) { $lsportello = $row2['postazione']; }
    mysqli_close($conn);
    echo $turno . "|" . $numero . "|" . $lsportello . "|" . $lsportello . "|" . $idturno;
} else {
    mysqli_close($conn);
}
