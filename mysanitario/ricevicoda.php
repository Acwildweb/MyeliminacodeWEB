<?php
include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$idpostazione = intval($postazione);

// La postazione passata è già l'ID
if ($idpostazione <= 0) {
    echo json_encode([]);
    exit;
}

// Verifica che la postazione esista
$sql = "SELECT * FROM postazioni WHERE ID_postazione = " . $idpostazione;
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    echo json_encode([]);
    exit;
}

$coda = [];
$sql = "select t.turno, c.numero 
        from turni t inner join operazioni_turni ot on t.ID_turno = ot.id_turno 
        inner join operazioni_postazioni op on ot.id_operazione = op.id_operazione 
        inner join contatori c on t.ID_turno = c.id_turno 
        where c.tipo = 'NUMERO' and op.id_postazione = " . $idpostazione;
        //echo $sql."<br />";
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) > 0) {
    while ($row = mysqli_fetch_assoc($rs)) {
        $turno = $row['turno'];
        $numero = $row['numero'];
        $sql = "select t.turno, c.numero 
                from turni t inner join contatori c on t.ID_turno = c.id_turno 
                where c.tipo = 'CODA'  and t.turno = '" . $turno . "'";
        //echo $sql."<br />";
        $rs2 = mysqli_query($conn, $sql);
        if (mysqli_num_rows($rs2) > 0) {
            if ($row2 = mysqli_fetch_assoc($rs2)) {
                $numero2 = $row2['numero'];
                if ($numero2 > $numero) {
                    $coda[] = [
                        'turno' => $turno,
                        'numero' => $numero2 - $numero
                    ];
                }
            }
        }
    }
}
echo json_encode($coda);

mysqli_close($conn);