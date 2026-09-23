<?php
/**
 * Versione configurazione orari/turni per auto-reload del totem kiosk.
 * Usa solo tabelle di configurazione (mai contatori/coda): emettere un ticket NON cambia la versione.
 */
function orariVersionStampPath(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'orari_version.stamp';
}

function getOrariConfigVersion(mysqli $conn): string
{
    $parts = [];

    $sql = "SELECT ot.id_turno, og.giorno, og.ora_inizio, og.ora_fine
            FROM operazioni_turni ot
            INNER JOIN operazioni_giorni og ON ot.id_operazione = og.id_operazione
            ORDER BY ot.id_turno, og.giorno, og.ora_inizio, og.ora_fine, og.pk_opgio";
    $res = mysqli_query($conn, $sql);
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $parts[] = $row['id_turno'] . ':' . $row['giorno'] . ':' . $row['ora_inizio'] . ':' . $row['ora_fine'];
        }
    }

    $resT = mysqli_query($conn, "SELECT ID_turno, turno, desstato, stato FROM turni ORDER BY ID_turno");
    if ($resT) {
        while ($row = mysqli_fetch_assoc($resT)) {
            $parts[] = 't:' . $row['ID_turno'] . ':' . $row['turno'] . ':' . $row['desstato'] . ':' . $row['stato'];
        }
    }

    $resC = @mysqli_query($conn, "SELECT idturno FROM configturnitotem ORDER BY idturno");
    if ($resC) {
        while ($row = mysqli_fetch_assoc($resC)) {
            $parts[] = 'ct:' . $row['idturno'];
        }
    }

    $stampFile = orariVersionStampPath();
    if (is_file($stampFile)) {
        $parts[] = 'stamp:' . (string) @filemtime($stampFile) . ':' . substr((string) @file_get_contents($stampFile), 0, 64);
    }

    return md5(implode("\n", $parts));
}

function bumpOrariVersionStamp(string $reason = 'import'): bool
{
    $payload = date('c') . ' ' . $reason . ' ' . bin2hex(random_bytes(4));
    return @file_put_contents(orariVersionStampPath(), $payload, LOCK_EX) !== false;
}
