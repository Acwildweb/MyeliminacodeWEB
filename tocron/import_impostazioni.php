<?php
// import_impostazioni.php
// Requisiti: PHP 7.4+ con estensione PDO_MYSQL abilitata

function getJsonFromUrl(string $url, int $timeout = 15)
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'ImportImpostazioniScript/1.0',
        // CURLOPT_SSL_VERIFYPEER => true, // abilita in produzione
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode < 200 || $httpCode >= 300) {
        error_log("getJsonFromUrl: HTTP $httpCode, curl_err: $err, url: $url");
        return null;
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("getJsonFromUrl: JSON decode error: " . json_last_error_msg());
        return null;
    }

    return $data;
}

function getIdCliente(): string
{
    if (!file_exists('../totem_ui_config.json')) {
        return '';
    }

    $contenuto = file_get_contents('../totem_ui_config.json');
    if ($contenuto === false) {
        return '';
    }

    $dati = json_decode($contenuto, true);
    if ($dati === null && json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }

    return $dati['idcliente'] ?? '';
}

function recordExists(PDO $pdo, string $table, string $pkField, $pkValue): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `$table` WHERE `$pkField` = ?");
    $stmt->execute([$pkValue]);
    return $stmt->fetchColumn() > 0;
}

/**
 * Carica impostazioni dal JSON e aggiorna il DB MariaDB
 *
 * Regole:
 * - Se $deleteAll = true: svuota e ricrea tutto
 * - Se $deleteAll = false: aggiunge solo i record mancanti, senza modificare o eliminare quelli esistenti
 *
 * @param PDO $pdo Connessione PDO a MariaDB
 * @param string $urlJson Base URL (es. 'https://example.com/configs?cliente=' oppure 'https://example.com/configs/')
 * @param mixed $idCliente ID cliente da concatenare a $urlJson
 * @param bool $deleteAll Se true pulisce le tabelle e reinserisce tutto
 * @return bool true se OK, false in caso di errore
 */
function loadImpostazioniFromUrl(PDO $pdo, string $urlJson, $idCliente, bool $deleteAll = false): bool
{
    $url = $urlJson . $idCliente;
    $data = getJsonFromUrl($url);

    if ($data === null) {
        return false;
    }

    $go = true;
	
	if (isset($data['errore'])) {
          $go = false;
          error_log("----------********** LICENZA SCADUTA *********--------------");
		/*if (isset($data['token'])) {
			if ($data['token'] != '') {
				$sql = 'update configturnimonitor set immaginesfondo = ?';
				$updateconfig = $pdo->prepare($sql);
				$updateconfig->execute([$data['token']]);
			}
		}*/
	}

    try {
        $pdo->beginTransaction();

        error_log("----------********** STO SVUOTANDO *********--------------");
        if (true) {
            $tablesToClear = [
                'turni',
                'coda',
                'coda_ambulatori',
                'contatori',
                'postazioni',
                'operazioni',
                'operazioni_giorni',
                'operazioni_postazioni',
                'operazioni_turni',
            ];

            foreach ($tablesToClear as $t) {
                $pdo->exec("DELETE FROM `$t`");
            }
        }
        error_log("----------********** DB SVUOTATO *********--------------");

        if (!$go) {
           $pdo->commit();
           exit;
        }

        error_log("----------********** INIZIO CARICAMENTO DATI *********--------------");

        // =========================
        // turni
        // =========================
        $insertTurno = $pdo->prepare("
            INSERT INTO `turni` (ID_turno, turno, stato, desstato, priorita, maxchiamate)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $insertContatore = $pdo->prepare("
            INSERT INTO `contatori` (`tipo`, `id_turno`, `id_postazione`, `numero`, `data`, `ora`, `consecutivi`)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($data['san_turni'] ?? [] as $item) {
            $id_turno = $item['id_turno'] ?? null;
            if ($id_turno === null) {
                continue;
            }

            if (!$deleteAll && recordExists($pdo, 'turni', 'ID_turno', $id_turno)) {
                continue;
            }

            $insertTurno->execute([
                $id_turno,
                $item['turno'] ?? '',
                $item['stato'] ?? '',
                $item['desstato'] ?? '',
                $item['priorita'] ?? 0,
                $item['maxchiamate'] ?? 0
            ]);

            if ($deleteAll || !recordExists($pdo, 'contatori', 'id_turno', $id_turno)) {
                $insertContatore->execute(['CODA', $id_turno, 0, 0, '', '', 0]);
                $insertContatore->execute(['NUMERO', $id_turno, 0, 0, '', '', 0]);
            }
        }

        // =========================
        // postazioni
        // =========================
        $insertPostazione = $pdo->prepare("
            INSERT INTO `postazioni` (ID_postazione, postazione, descrizione, turno_ambulatorio)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($data['san_postazioni'] ?? [] as $item) {
            $id_postazione = $item['id_postazione'] ?? null;
            if ($id_postazione === null) {
                continue;
            }

            if (!$deleteAll && recordExists($pdo, 'postazioni', 'ID_postazione', $id_postazione)) {
                continue;
            }

            $insertPostazione->execute([
                $id_postazione,
                $item['postazione'] ?? '',
                $item['descrizione'] ?? '',
                $item['turno_ambulatorio'] ?? ''
            ]);
        }

        // =========================
        // operazioni
        // =========================
        $insertOperazione = $pdo->prepare("
            INSERT INTO `operazioni` (ID_operazione, operazione)
            VALUES (?, ?)
        ");

        foreach ($data['san_operazioni'] ?? [] as $item) {
            $id_operazione = $item['id_operazione'] ?? null;
            if ($id_operazione === null) {
                continue;
            }

            if (!$deleteAll && recordExists($pdo, 'operazioni', 'ID_operazione', $id_operazione)) {
                continue;
            }

            $insertOperazione->execute([
                $id_operazione,
                $item['operazione'] ?? ''
            ]);
        }

        // =========================
        // operazioni_giorni
        // =========================
        $insertOperazioniGiorni = $pdo->prepare("
            INSERT INTO `operazioni_giorni` (pk_opgio, id_operazione, giorno, ora_inizio, ora_fine, `note`)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        foreach ($data['san_operazioni_giorni'] ?? [] as $item) {
            $pk_opgio = $item['pk_opgio'] ?? null;
            if ($pk_opgio === null) {
                continue;
            }

            if (!$deleteAll && recordExists($pdo, 'operazioni_giorni', 'pk_opgio', $pk_opgio)) {
                continue;
            }

            $ora_inizio = isset($item['ora_inizio']) ? str_replace(':', '', $item['ora_inizio']) : '';
            $ora_fine   = isset($item['ora_fine']) ? str_replace(':', '', $item['ora_fine']) : '';

            $insertOperazioniGiorni->execute([
                $pk_opgio,
                $item['id_operazione'] ?? null,
                $item['giorno'] ?? null,
                $ora_inizio,
                $ora_fine,
                $item['note'] ?? ''
            ]);
        }

        // =========================
        // operazioni_postazioni
        // =========================
        // Nota: qui non è evidente una PK univoca singola dal codice originale.
        // Usiamo come chiave logica la coppia (id_postazione, id_operazione).
        $existsOperazioniPostazioni = $pdo->prepare("
            SELECT COUNT(*) FROM `operazioni_postazioni`
            WHERE `id_postazione` = ? AND `id_operazione` = ?
        ");

        $insertOperazioniPostazioni = $pdo->prepare("
            INSERT INTO `operazioni_postazioni`
            (id_postazione, id_operazione, ora_inizio1, ora_fine1, ora_inizio2, ora_fine2, ora_inizio3, ora_fine3, ora_inizio4, ora_fine4)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($data['san_operazioni_postazioni'] ?? [] as $item) {
            $id_postazione = $item['id_postazione'] ?? null;
            $id_operazione = $item['id_operazione'] ?? null;

            if ($id_postazione === null || $id_operazione === null) {
                continue;
            }

            if (!$deleteAll) {
                $existsOperazioniPostazioni->execute([$id_postazione, $id_operazione]);
                if ($existsOperazioniPostazioni->fetchColumn() > 0) {
                    continue;
                }
            }

            $insertOperazioniPostazioni->execute([
                $id_postazione,
                $id_operazione,
                isset($item['ora_inizio1']) ? str_replace(':', '', $item['ora_inizio1']) : '',
                isset($item['ora_fine1'])   ? str_replace(':', '', $item['ora_fine1'])   : '',
                isset($item['ora_inizio2']) ? str_replace(':', '', $item['ora_inizio2']) : '',
                isset($item['ora_fine2'])   ? str_replace(':', '', $item['ora_fine2'])   : '',
                isset($item['ora_inizio3']) ? str_replace(':', '', $item['ora_inizio3']) : '',
                isset($item['ora_fine3'])   ? str_replace(':', '', $item['ora_fine3'])   : '',
                isset($item['ora_inizio4']) ? str_replace(':', '', $item['ora_inizio4']) : '',
                isset($item['ora_fine4'])   ? str_replace(':', '', $item['ora_fine4'])   : '',
            ]);
        }

        // =========================
        // operazioni_turni
        // =========================
        // Chiave logica: coppia (id_turno, id_operazione)
        $existsOperazioniTurni = $pdo->prepare("
            SELECT COUNT(*) FROM `operazioni_turni`
            WHERE `id_turno` = ? AND `id_operazione` = ?
        ");

        $insertOperazioniTurni = $pdo->prepare("
            INSERT INTO `operazioni_turni` (id_turno, id_operazione)
            VALUES (?, ?)
        ");

        foreach ($data['san_operazioni_turni'] ?? [] as $item) {
            $id_turno = $item['id_turno'] ?? null;
            $id_operazione = $item['id_operazione'] ?? null;

            if ($id_turno === null || $id_operazione === null) {
                continue;
            }

            if (!$deleteAll) {
                $existsOperazioniTurni->execute([$id_turno, $id_operazione]);
                if ($existsOperazioniTurni->fetchColumn() > 0) {
                    continue;
                }
            }

            $insertOperazioniTurni->execute([
                $id_turno,
                $id_operazione
            ]);
        }

        $pdo->commit();
        // Notifica i totem kiosk di ricaricare gli orari (file stamp, best-effort)
        try {
            require_once __DIR__ . '/orari_version_lib.php';
            bumpOrariVersionStamp('import_ok');
        } catch (Throwable $e) {
            error_log('bumpOrariVersionStamp: ' . $e->getMessage());
        }
        error_log("----------********** FINE CARICAMENTO DATI *********--------------");
        return true;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("loadImpostazioniFromUrl error: " . $ex->getMessage());
        return false;
    }
}

// -------------------- ESEMPIO USO --------------------

$dbHost = 'localhost';
$dbName = 'mysanitario';
$dbUser = 'root';
$dbPass = 'Ciccio7f6385e3eb';
$dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $urlJson = 'https://pannello.myeliminacode.it/api/get_json_config.php?idmonitor=';
    $idCliente = getIdCliente();

    // true = svuota e ricrea tutto
    // false = aggiunge solo i record mancanti, senza toccare quelli esistenti
    $deleteAll = true;

    $ok = loadImpostazioniFromUrl($pdo, $urlJson, $idCliente, $deleteAll);
    echo $ok ? "Import completato\n" : "Import fallito (vedi log)\n";
} catch (Exception $e) {
    echo "Connessione DB fallita: " . $e->getMessage() . "\n";
}