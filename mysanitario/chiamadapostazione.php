<?php

include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$turno = $_POST["turno"] ?? '';
$test = $_POST["test"] ?? '0';
$idpostazione = intval($postazione);
$idturno = 0;
$uturnochiamato = 0;

// La postazione passata è già l'ID
$sql = "SELECT * FROM postazioni WHERE ID_postazione = " . $idpostazione;
if ($test == '1') {
    echo $sql;
    echo "<br>";
}
$rs = mysqli_query($conn, $sql);
if (!$rs || mysqli_num_rows($rs) == 0) {
    exit;
}
if ($row = mysqli_fetch_assoc($rs)) {
    $uturnochiamato = $row['uturnochiamato'] ?? 0;
} else {
    exit;
}

if ($turno != '') {
    // Se il turno è specificato, cerco il suo ID
    $sql = "select ID_turno from turni where turno = '" . $turno . "'";
    $rs = mysqli_query($conn, $sql);
    if (mysqli_num_rows($rs) == 0) {
        $turno = '';
    }
    if ($row = mysqli_fetch_assoc($rs)) {
        $id_turno = $row['ID_turno'];
    } else {
        $turno = '';
    }
}

$unumero = 0;
$uchiamato = 0;

// CONTROLLO PRELIMINARE: verifica se esiste almeno un turno con numeri in coda da chiamare
// Un numero è in coda se CODA.numero > NUMERO.numero per lo stesso turno
$sql_check = "SELECT c1.id_turno, c1.numero as coda_num, IFNULL(c2.numero, 0) as num_chiamato 
              FROM contatori c1 
              LEFT JOIN contatori c2 ON c1.id_turno = c2.id_turno AND c2.tipo = 'NUMERO'
              INNER JOIN turni t ON c1.id_turno = t.ID_turno
              INNER JOIN operazioni_turni ot ON ot.id_turno = t.ID_turno
              INNER JOIN operazioni_postazioni op ON op.id_operazione = ot.id_operazione
              WHERE c1.tipo = 'CODA' 
              AND t.stato = 'A'
              AND op.id_postazione = " . $idpostazione . "
              AND c1.numero > IFNULL(c2.numero, 0)";
$rs_check = mysqli_query($conn, $sql_check);
$haNumeriInCoda = ($rs_check && mysqli_num_rows($rs_check) > 0);

// Se non ci sono numeri in coda per nessun turno, esci subito
if (!$haNumeriInCoda) {
    echo "-001";
    mysqli_close($conn);
    exit;
}

if ($turno == '') {
    if ($uturnochiamato > 0) {
        $sql = "select * from turni where ID_turno = " . $uturnochiamato;
        $rs = mysqli_query($conn, $sql);
        if (mysqli_num_rows($rs) > 0) {
            if ($row = mysqli_fetch_assoc($rs)) {
                $priorita = $row['priorita'];
                if ($priorita == 1) {
                    $consecutivi = 0;
                    $sql = "select * from contatori where tipo='CODA' and id_turno=" . $uturnochiamato;

                    $rs2 = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($rs2) > 0) {
                        if ($row2 = mysqli_fetch_assoc($rs2)) {
                            $consecutivi = $row2['consecutivi'];
                        }
                    }
                    if ($consecutivi < 3) {
                        $id_turno = $uturnochiamato;
                        $turno = $row['turno'];
                    } else {
                        $time = date('Hi');
                        $sql = "select c.consecutivi, c.id_turno, t.priorita from contatori c
                                inner join turni t on c.id_turno = t.ID_turno 
                                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                                AND t.stato = 'A' and c.id_turno <> " . $uturnochiamato . " 
                                and c.numero > 0
                                and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                                order by t.priorita, c.consecutivi";

                        $rs = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($rs) > 0) {
                            while ($row = mysqli_fetch_assoc($rs)) {
                                $consecutivi = $row['consecutivi'];
                                $id_turno = $row['id_turno'];
                                $priorita = $row['priorita'];
                                if ($priorita == 1 && $consecutivi < 3) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                } else if ($priorita == 0 && $consecutivi == 0) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                }
                            }
                        } else {
                            $sql = "select ID_contatore from contatori c
                                inner join turni t on c.id_turno = t.ID_turno 
                                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                                AND t.stato = 'A' and c.numero > 0
                                AND (
                                    (t.priorita = 1 AND c.consecutivi >= 3) OR
                                    (t.priorita = 0 AND c.consecutivi > 0)
                                )";
                            $rs = mysqli_query($conn, $sql);
                            while ($row = mysqli_fetch_assoc($rs)) {
                                $id_contatore = $row['ID_contatore'];
                                $sql = "UPDATE contatori set consecutivi = 0 where ID_contatore = " . $id_contatore;
                                mysqli_query($conn, $sql);
                            }
                            $sql = "select c.consecutivi, c.id_turno, t.priorita from contatori c
                                    inner join turni t on c.id_turno = t.ID_turno 
                                    inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                    inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                    where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                                    AND t.stato = 'A' and c.numero > 0
                                    and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                                    order by t.priorita, c.consecutivi";

                            $rs = mysqli_query($conn, $sql);
                            if (mysqli_num_rows($rs) > 0) {
                                while ($row = mysqli_fetch_assoc($rs)) {
                                    $consecutivi = $row['consecutivi'];
                                    $id_turno = $row['id_turno'];
                                    $priorita = $row['priorita'];
                                    if ($priorita == 1 && $consecutivi < 3) {
                                        $id_turno = $row['id_turno'];
                                        break;
                                    } else if ($priorita == 0 && $consecutivi == 0) {
                                        $id_turno = $row['id_turno'];
                                        break;
                                    }
                                }
                            }
                        }
                        if ($id_turno > 0) {
                            $sql = "select turno from turni where ID_turno = " . $id_turno;
                            $rs = mysqli_query($conn, $sql);
                            if (mysqli_num_rows($rs) > 0) {
                                if ($row = mysqli_fetch_assoc($rs)) {
                                    $turno = $row['turno'];
                                }
                            }
                        } else {
                            $id_turno = $uturnochiamato;
                        }
                    }
                } else {
                    $time = date('Hi');
                    $sql = "select c.consecutivi, c.id_turno, t.priorita from contatori c
                            inner join turni t on c.id_turno = t.ID_turno 
                            inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                            inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                            where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                            AND t.stato = 'A' and c.id_turno <> " . $uturnochiamato . "  and c.numero > 0
                            and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                            order by t.priorita, c.consecutivi";

                    $rs = mysqli_query($conn, $sql);
                    if (mysqli_num_rows($rs) > 0) {
                        while ($row = mysqli_fetch_assoc($rs)) {
                            $consecutivi = $row['consecutivi'];
                            $id_turno = $row['id_turno'];
                            $priorita = $row['priorita'];
                            if ($priorita == 1 && $consecutivi < 3) {
                                $id_turno = $row['id_turno'];
                                break;
                            } else if ($priorita == 0 && $consecutivi == 0) {
                                $id_turno = $row['id_turno'];
                                break;
                            }
                        }
                    } else {
                        $sql = "select ID_contatore from contatori c
                            inner join turni t on c.id_turno = t.ID_turno 
                            inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                            inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                            where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                            AND t.stato = 'A' and c.numero > 0
                            AND (
                                (t.priorita = 1 AND c.consecutivi >= 3) OR
                                (t.priorita = 0 AND c.consecutivi > 0)
                            )";
                        $rs = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($rs)) {
                            $id_contatore = $row['ID_contatore'];
                            $sql = "UPDATE contatori set consecutivi = 0 where ID_contatore = " . $id_contatore;
                            mysqli_query($conn, $sql);
                        }

                        $sql = "select c.consecutivi, c.id_turno, t.priorita from contatori c
                                inner join turni t on c.id_turno = t.ID_turno 
                                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                                AND t.stato = 'A' and c.numero > 0
                                and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                                order by t.priorita, c.consecutivi";

                        $rs = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($rs) > 0) {
                            while ($row = mysqli_fetch_assoc($rs)) {
                                $consecutivi = $row['consecutivi'];
                                $id_turno = $row['id_turno'];
                                $priorita = $row['priorita'];
                                if ($priorita == 1 && $consecutivi < 3) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                } else if ($priorita == 0 && $consecutivi == 0) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                }
                            }
                        }
                    }

                    if ($id_turno == 0) {

                        $sql = "select c.consecutivi, c.id_turno, t.priorita, c2.numero, c.numero from contatori c
                                inner join turni t on c.id_turno = t.ID_turno 
                                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                inner join contatori c2 on c2.id_turno = c.id_turno and c2.tipo='NUMERO'
                                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . "
                                AND t.stato = 'A' and c.numero > 0
                                #and c2.numero < c.numero
                                order by t.priorita, c.consecutivi";

                        $rs = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($rs) > 0) {
                            while ($row = mysqli_fetch_assoc($rs)) {
                                $consecutivi = $row['consecutivi'];
                                $id_turno = $row['id_turno'];
                                $priorita = $row['priorita'];
                                if ($priorita == 1 && $consecutivi < 3) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                } else if ($priorita == 0 && $consecutivi == 0) {
                                    $id_turno = $row['id_turno'];
                                    break;
                                }
                            }
                        }

                    }
                    if ($id_turno > 0) {
                        $sql = "select turno from turni where ID_turno = " . $id_turno;
                        $rs = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($rs) > 0) {
                            if ($row = mysqli_fetch_assoc($rs)) {
                                $turno = $row['turno'];
                            }
                        }
                    } else {
                        $id_turno = $uturnochiamato;
                    }

                }
            }
        }
    } else {
        $time = date('Hi');
        $sql = "select distinct c.consecutivi, c.id_turno, t.priorita from contatori c
                inner join turni t on c.id_turno = t.ID_turno 
                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                AND t.stato = 'A' and c.numero > 0
                and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                order by t.priorita, c.consecutivi";
        $rs = mysqli_query($conn, $sql);
        if (mysqli_num_rows($rs) > 0) {
            while ($row = mysqli_fetch_assoc($rs)) {
                $consecutivi = $row['consecutivi'];
                $id_turno = $row['id_turno'];
                $priorita = $row['priorita'];
                if ($priorita == 1 && $consecutivi < 3) {
                    $id_turno = $row['id_turno'];
                    break;
                } else if ($priorita == 1 && $consecutivi >= 3) {
                    $sql = "update contatori set consecutivi=0 where tipo='NUMERO' and id_turno=" . $row['id_turno'];
                    mysqli_query($conn, $sql);
                    $id_turno = $row['id_turno'];
                } else if ($priorita == 0 && $consecutivi == 0) {
                    if ($row['id_turno'] != $uturnochiamato) {
                        $id_turno = $row['id_turno'];
                        break;
                    } else {
                        $sql = "select distinct c.consecutivi, c.id_turno, t.priorita from contatori c
                                inner join turni t on c.id_turno = t.ID_turno 
                                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                                AND t.stato = 'A' and c.id_turno <> " . $uturnochiamato . " and c.numero > 0
                                order by t.priorita, c.consecutivi";

                        $rs = mysqli_query($conn, $sql);
                        if (mysqli_num_rows($rs) > 0) {
                            if ($row = mysqli_fetch_assoc($rs)) {
                                $id_turno = $row['id_turno'];
                                break;
                            }
                        }
                    }
                    $id_turno = $row['id_turno'];
                    break;
                }
            }
        } else {
            $sql = "select ID_contatore from contatori c
                inner join turni t on c.id_turno = t.ID_turno 
                inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                AND t.stato = 'A'
                AND (
                    (t.priorita = 1 AND c.consecutivi >= 3) OR
                    (t.priorita = 0 AND c.consecutivi > 0)
                )";
            $rs = mysqli_query($conn, $sql);
            while ($row = mysqli_fetch_assoc($rs)) {
                $id_contatore = $row['ID_contatore'];
                $sql = "UPDATE contatori set consecutivi = 0 where ID_contatore = " . $id_contatore;
                mysqli_query($conn, $sql);
            }

            $sql = "select c.consecutivi, c.id_turno, t.priorita from contatori c
                    inner join turni t on c.id_turno = t.ID_turno 
                    inner join operazioni_turni ot on ot.id_turno = t.ID_turno 
                    inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione 
                    where c.tipo='CODA' and op.id_postazione = " . $idpostazione . " 
                    AND t.stato = 'A' and c.numero > 0
                    and ((t.priorita = 1 and c.consecutivi < 3) or (t.priorita = 0 and c.consecutivi = 0))
                    order by t.priorita, c.consecutivi";
            $rs = mysqli_query($conn, $sql);
            if (mysqli_num_rows($rs) > 0) {
                while ($row = mysqli_fetch_assoc($rs)) {
                    $consecutivi = $row['consecutivi'];
                    $id_turno = $row['id_turno'];
                    $priorita = $row['priorita'];
                    if ($priorita == 1 && $consecutivi < 3) {
                        $id_turno = $row['id_turno'];
                        break;
                    } else if ($priorita == 0 && $consecutivi == 0) {
                        $id_turno = $row['id_turno'];
                        break;
                    }
                }
            }

        }
        if ($id_turno > 0) {
            $sql = "select turno from turni where ID_turno = " . $id_turno;
            $rs = mysqli_query($conn, $sql);
            if (mysqli_num_rows($rs) > 0) {
                if ($row = mysqli_fetch_assoc($rs)) {
                    $turno = $row['turno'];
                }
            }
        }
    }
}

$prossimo = -1;

if ($id_turno > 0) {
    $sql = "update postazioni set uturnochiamato=" . $id_turno . " where ID_postazione = " . $idpostazione;
    mysqli_query($conn, $sql);
    $sql = "update contatori set consecutivi = consecutivi+1 where tipo='CODA' and id_turno=" . $id_turno;
    mysqli_query($conn, $sql);
       
    //SELEZIONA L'ULTIMO NUMERO PRESENTE NEL TURNO
    $StrQ = "select * from contatori where tipo='CODA' and id_turno=" . $id_turno;
    $rs2 = mysqli_query($conn, $StrQ);
    if (mysqli_num_rows($rs2) > 0) {
        while ($row2 = mysqli_fetch_assoc($rs2)) {
            // Elaborazione dei dati dei contatori
            $unumero = $row2['numero'];
        }
    }

    //SELEZIONA L'ULTIMO NUMERO CHIAMATO DALLA POSTAZIONE IN QUESTO TURNO
    $StrQ = "select * from contatori where tipo='NUMERO' and id_turno=" . $id_turno;
    $rs2 = mysqli_query($conn, $StrQ);
    if (mysqli_num_rows($rs2) > 0) {
        while ($row2 = mysqli_fetch_assoc($rs2)) {
            // Elaborazione dei dati dei contatori
            $uchiamato = $row2['numero'];
        }
    }

    // $unumero = ultimo numero emesso dal totem (CODA)
    // $uchiamato = ultimo numero chiamato dalla postazione (NUMERO)
    // Chiamiamo solo se ci sono numeri in coda da chiamare:
    // - $unumero > $uchiamato: ci sono numeri non ancora chiamati
    // - oppure wrap-around (es. unumero=5, uchiamato=998 -> si è ripartiti da 1)
    if ($unumero > $uchiamato) {
        $prossimo = $uchiamato + 1;
        if ($prossimo > 999) {
            $prossimo = 1;
        }
    } else if ($unumero < $uchiamato && $unumero > 0 && $unumero < 100 && $uchiamato > 900) {
        // Caso wrap-around: il contatore è ripartito da 1
        $prossimo = $uchiamato + 1;
        if ($prossimo > 999) {
            $prossimo = 1;
        }
    }
    // Se $unumero == $uchiamato oppure $unumero == 0: coda vuota, $prossimo resta -1

    if ($prossimo > 0) {
        $strq = "UPDATE contatori SET numero = " . $prossimo . ", id_postazione = " . $idpostazione . ", 
        ora = '" . date('His') . "' 
        WHERE tipo = 'NUMERO' and numero = " . $uchiamato . " and id_turno=" . $id_turno;
        $rs2 = mysqli_query($conn, $strq);
        if (mysqli_affected_rows($conn) > 0) {
            $StrQ = "insert into coda (numero, sportello, turno, id_turno, id_postazione) 
            values (" . $prossimo . ", '" . $postazione . "', '" . $turno . "', " . $id_turno . ", " . $idpostazione . ")";
            try {
                mysqli_query($conn, $StrQ);
            } catch (Exception $e) {
                // Gestione dell'errore
                $variabile = $e->getMessage();
                error_log("Errore durante l'inserimento in coda: " . $e->getMessage());
            }
        }

    }
}

echo $turno."-".str_pad($prossimo, 3, '0', STR_PAD_LEFT);

mysqli_close($conn);