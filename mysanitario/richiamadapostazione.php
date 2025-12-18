<?php

include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$idpostazione = intval($postazione);

// La postazione passata è già l'ID
if ($idpostazione <= 0) {
    exit;
}

$sql = "SELECT * FROM postazioni WHERE ID_postazione = " . $idpostazione;
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    exit;
}


$sql = "select * from coda where id_postazione = " . $idpostazione . " order by ID desc limit 1";
       // echo $sql."<br />";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) > 0) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $turno = $row['turno'];
        $numero = $row['numero'];
        $idturno = $row['id_turno'];
        $idpostazione = $row['id_postazione'];
        $sportello = $row['sportello'];
        $StrQ = "insert into coda (numero, sportello, turno, id_turno, id_postazione) 
        values (" . $numero . ", '" . $sportello . "', '" . $turno . "', " . $idturno . ", " . $idpostazione . ")";
        try {
            mysqli_query($conn, $StrQ);// 6. Restituisci la scelta
            echo $turno."-".str_pad($numero, 3, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            // Gestione dell'errore
            $variabile = $e->getMessage();
            error_log("Errore durante l'inserimento in coda: " . $e->getMessage());
            echo -1;
        }

    }
}

mysqli_close($conn);