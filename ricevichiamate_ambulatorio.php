<?php
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$turno = $_POST["turno"] ?? '';
$idpostazione = 0;
$idturno = 0;

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

$sql = "select * from coda_ambulatori where orariochiamata >= '".date("Y-m-d")."' 
        and sportello = '".$postazione."' and turno = '".$turno."' order by ID desc limit 5";
$rsnumeri = mysqli_query($conn, $sql);
$testo = "";
$virgola = "";
while ($rownumeri = mysqli_fetch_assoc($rsnumeri)) {
	$testo .= $virgola . $rownumeri["turno"] . "-" . $rownumeri["numero"];
	$virgola = ",";
}
echo $testo;

mysqli_close($conn);