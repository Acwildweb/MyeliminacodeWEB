<?php

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

$sql = "select * from coda_ambulatori where sportello = '".$postazione."' and turno = '".$turno."' and dachiamare = '1' order by ID DESC limit 1";

$rsnumeri = mysqli_query($conn, $sql);
if ($rownumeri = mysqli_fetch_assoc($rsnumeri)) {
	$prossimo = $rownumeri["numero"];
	$id = $rownumeri["ID"];

	$sql = "update coda_ambulatori set ipmonitor = '' where ID = ".$id;
	mysqli_query($conn, $sql);

	echo $turno."-".str_pad($prossimo, 3, '0', STR_PAD_LEFT);
} else {
	echo "";
}

mysqli_close($conn);