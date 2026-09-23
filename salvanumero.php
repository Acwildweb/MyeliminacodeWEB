<?php
include 'connect.php';
$id_contatore = $_POST['id_contatore'] ?? 0;
$numero = $_POST['numero'] ?? 0;
if ($id_contatore == 0 || $numero == 0) {
    exit;
}
$sql = "update contatori set numero = " . $numero . " where ID_contatore = " . $id_contatore;
mysqli_query($conn, $sql);
if (mysqli_affected_rows($conn) > 0) {
    echo "Numero aggiornato correttamente";
} else {
    echo "Errore nell'aggiornamento del numero";
}
mysqli_close($conn);
