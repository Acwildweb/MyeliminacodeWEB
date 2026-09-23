<?php
/**
 * aggiorna_db.php
 * Applica le modifiche alla tabella config_monitor2 necessarie per le nuove
 * funzionalità multimedia (immagine di servizio + durata contenuti).
 *
 * Uso: eseguire tramite aggiorna_db.bat oppure da CLI:
 *   php aggiorna_db.php
 *
 * Le colonne vengono aggiunte SOLO SE NON ESISTONO GIÀ (idempotente).
 */

// Carica i parametri di connessione da connect.php
include __DIR__ . '/connect.php';

// ── Definizione colonne da aggiungere ────────────────────────────────────────
$alterazioni = [
    [
        'colonna'  => 'multimedia_durata',
        'sql'      => "ALTER TABLE `config_monitor2` ADD COLUMN `multimedia_durata` SMALLINT NOT NULL DEFAULT 5",
        'desc'     => 'Durata visualizzazione contenuti multimediali (secondi)',
    ],
    [
        'colonna'  => 'multimedia_immagine_servizio',
        'sql'      => "ALTER TABLE `config_monitor2` ADD COLUMN `multimedia_immagine_servizio` VARCHAR(255) NOT NULL DEFAULT ''",
        'desc'     => 'Percorso immagine di servizio quando non ci sono contenuti',
    ],
];

// ── Funzione: verifica se la colonna esiste già ──────────────────────────────
function colonnaEsiste(mysqli $conn, string $tabella, string $colonna): bool
{
    $res = mysqli_query(
        $conn,
        "SELECT COUNT(*) AS cnt
         FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME   = '" . mysqli_real_escape_string($conn, $tabella) . "'
           AND COLUMN_NAME  = '" . mysqli_real_escape_string($conn, $colonna)  . "'"
    );
    if (!$res) return false;
    $row = mysqli_fetch_assoc($res);
    return (int)($row['cnt'] ?? 0) > 0;
}

// ── Esecuzione ────────────────────────────────────────────────────────────────
echo "\n=== Aggiornamento database: config_monitor2 ===\n\n";

$tuttoOk = true;

foreach ($alterazioni as $alt) {
    echo "• [{$alt['colonna']}] {$alt['desc']}\n";

    if (colonnaEsiste($conn, 'config_monitor2', $alt['colonna'])) {
        echo "  → Colonna già presente, nessuna modifica.\n\n";
        continue;
    }

    if (mysqli_query($conn, $alt['sql'])) {
        echo "  → Colonna aggiunta con successo.\n\n";
    } else {
        echo "  → ERRORE: " . mysqli_error($conn) . "\n\n";
        $tuttoOk = false;
    }
}

mysqli_close($conn);

if ($tuttoOk) {
    echo "=== Completato senza errori. ===\n";
} else {
    echo "=== Completato con ERRORI. Controlla i messaggi sopra. ===\n";
    exit(1);
}
