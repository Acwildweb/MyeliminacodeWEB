<?php
include 'connect.php';

header('Content-Type: application/json');

$idturno = isset($_POST['idturno']) ? intval($_POST['idturno']) : 0;

if ($idturno == 0) {
    echo json_encode(['success' => false, 'error' => 'ID turno non valido']);
    mysqli_close($conn);
    exit;
}

// Funzione per verificare se il turno è attivo
function verificaTurnoAttivo($conn, $idturno) {
    // Ottieni giorno della settimana (0=domenica, 1=lunedì, ..., 6=sabato)
    $giorno = date('w');
    // Ottieni ora corrente in formato HHMM
    $ora_corrente = date('Hi');
    
    // Nomi dei giorni della settimana
    $nomi_giorni = ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'];
    
    // Verifica se esiste un orario attivo per questo turno
    $sql = "SELECT og.ora_inizio, og.ora_fine, o.operazione, og.giorno
            FROM operazioni_turni ot
            INNER JOIN operazioni_giorni og ON ot.id_operazione = og.id_operazione
            INNER JOIN operazioni o ON og.id_operazione = o.ID_operazione
            WHERE ot.id_turno = " . intval($idturno) . "
            AND og.giorno = " . intval($giorno);
    
    $result = mysqli_query($conn, $sql);
    
    if (!$result || mysqli_num_rows($result) == 0) {
        // Cerca gli orari per tutti i giorni per mostrare quando è disponibile
        $sql_tutti = "SELECT og.ora_inizio, og.ora_fine, og.giorno
                     FROM operazioni_turni ot
                     INNER JOIN operazioni_giorni og ON ot.id_operazione = og.id_operazione
                     WHERE ot.id_turno = " . intval($idturno) . "
                     ORDER BY og.giorno";
        $result_tutti = mysqli_query($conn, $sql_tutti);
        $orari = [];
        if ($result_tutti) {
            while ($row = mysqli_fetch_assoc($result_tutti)) {
                $orari[] = [
                    'giorno' => $nomi_giorni[$row['giorno']],
                    'inizio' => substr($row['ora_inizio'], 0, 2) . ':' . substr($row['ora_inizio'], 2, 2),
                    'fine' => substr($row['ora_fine'], 0, 2) . ':' . substr($row['ora_fine'], 2, 2)
                ];
            }
        }
        return ['attivo' => false, 'messaggio' => 'Servizio non disponibile oggi', 'orari' => $orari];
    }
    
    $attivo = false;
    $messaggio = '';
    $orari = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $ora_inizio = str_replace(':', '', $row['ora_inizio']);
        $ora_fine = str_replace(':', '', $row['ora_fine']);
        
        $orari[] = [
            'giorno' => $nomi_giorni[$row['giorno']],
            'inizio' => substr($ora_inizio, 0, 2) . ':' . substr($ora_inizio, 2, 2),
            'fine' => substr($ora_fine, 0, 2) . ':' . substr($ora_fine, 2, 2)
        ];
        
        if ($ora_corrente >= $ora_inizio && $ora_corrente <= $ora_fine) {
            $attivo = true;
            break;
        } else {
            $messaggio = 'Servizio disponibile dalle ' . substr($ora_inizio, 0, 2) . ':' . substr($ora_inizio, 2, 2) . 
                        ' alle ' . substr($ora_fine, 0, 2) . ':' . substr($ora_fine, 2, 2);
        }
    }
    
    if (!$attivo && empty($messaggio)) {
        $messaggio = 'Servizio chiuso in questo orario';
    }
    
    return ['attivo' => $attivo, 'messaggio' => $messaggio, 'orari' => $orari];
}

// Verifica se il turno è attivo
$statoTurno = verificaTurnoAttivo($conn, $idturno);

if (!$statoTurno['attivo']) {
    echo json_encode([
        'success' => false, 
        'error' => $statoTurno['messaggio'],
        'orari' => isset($statoTurno['orari']) ? $statoTurno['orari'] : []
    ]);
    mysqli_close($conn);
    exit;
}

// Ottieni il nome del turno
$sql_turno = "SELECT turno FROM turni WHERE ID_turno = " . intval($idturno);
$result_turno = mysqli_query($conn, $sql_turno);
$nometurno = '';

if ($result_turno && $row_turno = mysqli_fetch_assoc($result_turno)) {
    $nometurno = $row_turno['turno'];
} else {
    echo json_encode(['success' => false, 'error' => 'Turno non trovato']);
    mysqli_close($conn);
    exit;
}

// Data corrente in formato YYYYMMDD
$data_oggi = date('Ymd');
$nextnumero = 0;
$success = false;

// Cerca il numero massimo per questo turno nella coda di oggi
$sql_max = "SELECT MAX(numero) AS maxcoda FROM contatori 
            WHERE id_turno = " . intval($idturno) . " 
            AND tipo = 'CODA' 
            AND data = '" . mysqli_real_escape_string($conn, $data_oggi) . "'";

$result_max = mysqli_query($conn, $sql_max);

if ($result_max && $row_max = mysqli_fetch_assoc($result_max)) {
    if (is_null($row_max['maxcoda'])) {
        // Primo numero della giornata per questo turno
        $nextnumero = 1;
        
        $sql_insert = "INSERT INTO contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) 
                      VALUES ('CODA', " . intval($idturno) . ", 0, " . intval($nextnumero) . ", 
                      '" . mysqli_real_escape_string($conn, $data_oggi) . "', '', 1)";
        
        if (mysqli_query($conn, $sql_insert)) {
            $success = true;
        } else {
            echo json_encode(['success' => false, 'error' => 'Errore nell\'inserimento del primo numero']);
            mysqli_close($conn);
            exit;
        }
    } else {
        // Incrementa il numero
        $nextnumero = intval($row_max['maxcoda']) + 1;
        
        // Controlla il limite di 999
        if ($nextnumero > 999) {
            $nextnumero = 1;
        }
        
        // Verifica che il numero non esista già oggi per questo turno
        $sql_check = "SELECT * FROM contatori 
                     WHERE numero = " . intval($nextnumero) . " 
                     AND data = '" . mysqli_real_escape_string($conn, $data_oggi) . "' 
                     AND tipo = 'CODA' 
                     AND id_turno = " . intval($idturno);
        
        $result_check = mysqli_query($conn, $sql_check);
        
        if ($result_check && mysqli_num_rows($result_check) == 0) {
            // Il numero non esiste, aggiorna il contatore
            $sql_update = "UPDATE contatori 
                          SET numero = " . intval($nextnumero) . ", 
                              data = '" . mysqli_real_escape_string($conn, $data_oggi) . "',
                              consecutivi = consecutivi + 1
                          WHERE tipo = 'CODA' 
                          AND id_turno = " . intval($idturno);
            
            if (mysqli_query($conn, $sql_update)) {
                if (mysqli_affected_rows($conn) > 0) {
                    $success = true;
                } else {
                    // Nessuna riga aggiornata, forse il record non esiste, prova a inserire
                    $sql_insert = "INSERT INTO contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) 
                                  VALUES ('CODA', " . intval($idturno) . ", 0, " . intval($nextnumero) . ", 
                                  '" . mysqli_real_escape_string($conn, $data_oggi) . "', '', 1)";
                    
                    if (mysqli_query($conn, $sql_insert)) {
                        $success = true;
                    }
                }
            } else {
                echo json_encode(['success' => false, 'error' => 'Errore nell\'aggiornamento del contatore']);
                mysqli_close($conn);
                exit;
            }
        } else {
            // Il numero esiste già, errore
            echo json_encode(['success' => false, 'error' => 'Numero già assegnato oggi']);
            mysqli_close($conn);
            exit;
        }
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Errore nella lettura del contatore']);
    mysqli_close($conn);
    exit;
}

mysqli_close($conn);

if ($success) {
    echo json_encode([
        'success' => true, 
        'turno' => $nometurno, 
        'numero' => $nextnumero,
        'idturno' => $idturno,
        'data' => $data_oggi
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Impossibile assegnare il numero']);
}
?>
