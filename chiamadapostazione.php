<?php

include 'connect.php';

$postazione = $_POST["postazione"] ?? 0;
$turno = $_POST["turno"] ?? '';
$test = $_POST["test"] ?? '0';
$idpostazione = 0;
$idturno = 0;
$uturnochiamato = 0;

$sql = "select * from postazioni where postazione = '" . $postazione . "'";
if ($test == '1') {
    echo $sql;
    echo "<br>";
}
$rs = mysqli_query($conn, $sql);
if (mysqli_num_rows($rs) == 0) {
    exit;
}
if ($row = mysqli_fetch_assoc($rs)) {
    $idpostazione = $row['ID_postazione'];
    $uturnochiamato = $row['uturnochiamato'];
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

    if ($unumero > $uchiamato || ($unumero < $uchiamato && $unumero < 100 && $uchiamato > 900) || ($unumero == 0 && $uchiamato == 0)) {
        $prossimo = $uchiamato + 1;
        if ($prossimo > 999) {
            $prossimo = 1;
        }
    }

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
                $sql = "insert into contatori_storico (id_turno, id_postazione, numero, data, ora) 
                values (" . $id_turno . ", " . $idpostazione . ", " . $prossimo . ", '" . date('Y-m-d') . "', '" . date('H:i:s') . "')";
                mysqli_query($conn, $sql);
            } catch (Exception $e) {
                // Gestione dell'errore
                $variabile = $e->getMessage();
                error_log("Errore durante l'inserimento in coda: " . $e->getMessage());
            }
        }

    }

    // FALLBACK: turno selezionato esaurito, cerca il prossimo turno con numeri in attesa
    if ($prossimo <= 0) {
        $sql_fb = "select t2.ID_turno as id_turno, t2.turno, cn.numero as uchiamato
                   from (
                       select c.id_turno, max(c.numero) as max_coda
                       from contatori c
                       inner join turni t on c.id_turno = t.ID_turno
                       inner join operazioni_turni ot on ot.id_turno = t.ID_turno
                       inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione
                       where c.tipo='CODA' and op.id_postazione = " . $idpostazione . "
                       AND t.stato = 'A' and c.id_turno <> " . $id_turno . "
                       group by c.id_turno
                   ) max_c
                   inner join turni t2 on t2.ID_turno = max_c.id_turno
                   inner join contatori cn on cn.id_turno = max_c.id_turno and cn.tipo='NUMERO'
                   where max_c.max_coda > cn.numero
                   order by t2.priorita limit 1";
        $rs_fb = mysqli_query($conn, $sql_fb);
        if ($rs_fb && ($row_fb = mysqli_fetch_assoc($rs_fb))) {
            $id_turno  = $row_fb['id_turno'];
            $turno     = $row_fb['turno'];
            $uchiamato = $row_fb['uchiamato'];
            $prossimo  = $uchiamato + 1;
            if ($prossimo > 999) $prossimo = 1;
            $sql = "update postazioni set uturnochiamato=" . $id_turno . " where ID_postazione=" . $idpostazione;
            mysqli_query($conn, $sql);
            $strq = "UPDATE contatori SET numero=" . $prossimo . ", id_postazione=" . $idpostazione . ",
            ora='" . date('His') . "'
            WHERE tipo='NUMERO' and numero=" . $uchiamato . " and id_turno=" . $id_turno;
            $rs2 = mysqli_query($conn, $strq);
            if (mysqli_affected_rows($conn) > 0) {
                $StrQ = "insert into coda (numero, sportello, turno, id_turno, id_postazione)
                values (" . $prossimo . ", '" . $postazione . "', '" . $turno . "', " . $id_turno . ", " . $idpostazione . ")";
                mysqli_query($conn, $StrQ);
                $sql = "insert into contatori_storico (id_turno, id_postazione, numero, data, ora) 
                values (" . $id_turno . ", " . $idpostazione . ", " . $prossimo . ", '" . date('Y-m-d') . "', '" . date('H:i:s') . "')";
                mysqli_query($conn, $sql);
            }
        }
    }

    // FALLBACK: turno esaurito, cerca il prossimo turno con numeri in attesa
    if ($prossimo <= 0) {
        $sql_fb = "select t2.ID_turno as id_turno, t2.turno, cn.numero as uchiamato
                   from (select c.id_turno, max(c.numero) as max_coda
                         from contatori c
                         inner join turni t on c.id_turno = t.ID_turno
                         inner join operazioni_turni ot on ot.id_turno = t.ID_turno
                         inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione
                         where c.tipo='CODA' and op.id_postazione = " . $idpostazione . "
                         AND t.stato = 'A' and c.id_turno <> " . $id_turno . "
                         group by c.id_turno) max_c
                   inner join turni t2 on t2.ID_turno = max_c.id_turno
                   inner join contatori cn on cn.id_turno = max_c.id_turno and cn.tipo='NUMERO'
                   where max_c.max_coda > cn.numero
                   order by t2.priorita limit 1";
        $rs_fb = mysqli_query($conn, $sql_fb);
        if ($rs_fb && ($row_fb = mysqli_fetch_assoc($rs_fb))) {
            $id_turno  = $row_fb['id_turno'];
            $turno     = $row_fb['turno'];
            $uchiamato = $row_fb['uchiamato'];
            $prossimo  = $uchiamato + 1;
            if ($prossimo > 999) $prossimo = 1;
            mysqli_query($conn, "update postazioni set uturnochiamato=" . $id_turno . " where ID_postazione=" . $idpostazione);
            $rs2 = mysqli_query($conn, "UPDATE contatori SET numero=" . $prossimo . ", id_postazione=" . $idpostazione . ", ora='" . date('His') . "' WHERE tipo='NUMERO' and numero=" . $uchiamato . " and id_turno=" . $id_turno);
            if (mysqli_affected_rows($conn) > 0) {
                mysqli_query($conn, "insert into coda (numero, sportello, turno, id_turno, id_postazione) values (" . $prossimo . ", '" . $postazione . "', '" . $turno . "', " . $id_turno . ", " . $idpostazione . ")");
                $sql = "insert into contatori_storico (id_turno, id_postazione, numero, data, ora) 
                values (" . $id_turno . ", " . $idpostazione . ", " . $prossimo . ", '" . date('Y-m-d') . "', '" . date('H:i:s') . "')";
                mysqli_query($conn, $sql);
            }
        }
    }
}


    // FALLBACK: turno esaurito, cerca il prossimo turno con numeri in attesa
    if ($prossimo <= 0) {
        $sql_fb = "select t2.ID_turno as id_turno, t2.turno, cn.numero as uchiamato
                   from (select c.id_turno, max(c.numero) as max_coda
                         from contatori c
                         inner join turni t on c.id_turno = t.ID_turno
                         inner join operazioni_turni ot on ot.id_turno = t.ID_turno
                         inner join operazioni_postazioni op on op.id_operazione = ot.id_operazione
                         where c.tipo='CODA' and op.id_postazione = " . $idpostazione . "
                         AND t.stato = 'A' and c.id_turno <> " . $id_turno . "
                         group by c.id_turno) max_c
                   inner join turni t2 on t2.ID_turno = max_c.id_turno
                   inner join contatori cn on cn.id_turno = max_c.id_turno and cn.tipo='NUMERO'
                   where max_c.max_coda > cn.numero
                   order by t2.priorita limit 1";
        $rs_fb = mysqli_query($conn, $sql_fb);
        if ($rs_fb && ($row_fb = mysqli_fetch_assoc($rs_fb))) {
            $id_turno  = $row_fb['id_turno'];
            $turno     = $row_fb['turno'];
            $uchiamato = $row_fb['uchiamato'];
            $prossimo  = $uchiamato + 1;
            if ($prossimo > 999) $prossimo = 1;
            mysqli_query($conn, "update postazioni set uturnochiamato=" . $id_turno . " where ID_postazione=" . $idpostazione);
            $rs2 = mysqli_query($conn, "UPDATE contatori SET numero=" . $prossimo . ", id_postazione=" . $idpostazione . ", ora='" . date('His') . "' WHERE tipo='NUMERO' and numero=" . $uchiamato . " and id_turno=" . $id_turno);
            if (mysqli_affected_rows($conn) > 0) {
                mysqli_query($conn, "insert into coda (numero, sportello, turno, id_turno, id_postazione) values (" . $prossimo . ", '" . $postazione . "', '" . $turno . "', " . $id_turno . ", " . $idpostazione . ")");
                $sql = "insert into contatori_storico (id_turno, id_postazione, numero, data, ora) 
                values (" . $id_turno . ", " . $idpostazione . ", " . $prossimo . ", '" . date('Y-m-d') . "', '" . date('H:i:s') . "')";
                mysqli_query($conn, $sql);
            }
        }
    }

echo $turno."-".str_pad($prossimo, 3, '0', STR_PAD_LEFT);

mysqli_close($conn);