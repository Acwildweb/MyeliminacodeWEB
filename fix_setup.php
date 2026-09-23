<?php
/**
 * fix_setup.php
 * Applica le correzioni necessarie al sito bisceglie:
 *  1. Aggiunge le colonne mancanti nella tabella config_monitor2
 *  2. Corregge object-fit:cover → contain in index2.php
 *
 * Legge i parametri di connessione DB da connect.php.
 * Eseguibile da browser: fix_setup.php?token=bsc2026!fix
 */

// Sicurezza: richiede token segreto via GET
define('SECRET_TOKEN', 'bsc2026!fix');
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['token']) || $_GET['token'] !== SECRET_TOKEN) {
        http_response_code(403);
        die('Accesso negato. Usa: fix_setup.php?token=bsc2026!fix');
    }
}

$results = [];

// ─── 1. Carica parametri di connessione da connect.php ───────────────────────
$connectFile = __DIR__ . '/connect.php';
if (!file_exists($connectFile)) {
    die("ERRORE: connect.php non trovato in " . __DIR__);
}

$src = file_get_contents($connectFile);
preg_match('/\$host\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m); $host = $m[1] ?? 'localhost';
preg_match('/\$user\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m); $user = $m[1] ?? '';
preg_match('/\$password\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m); $password = $m[1] ?? '';
preg_match('/\$dbname\s*=\s*[\'"]([^\'"]+)[\'"]/', $src, $m); $dbname = $m[1] ?? '';

if (!$user || !$dbname) {
    die("ERRORE: impossibile leggere i parametri di connessione da connect.php");
}

// ─── 2. Connessione al database ───────────────────────────────────────────────
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $password, $dbname);
    $conn->set_charset('utf8mb4');
    $results[] = "✔ Connessione al database '$dbname' riuscita.";
} catch (mysqli_sql_exception $e) {
    die("ERRORE connessione DB: " . $e->getMessage());
}

// ─── 3. Aggiunge colonne mancanti in config_monitor2 ─────────────────────────
$colonne = [
    'multimedia_immagine_servizio' => "TINYINT(1) NOT NULL DEFAULT 1",
    'multimedia_durata'            => "INT NOT NULL DEFAULT 5",
];

foreach ($colonne as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM config_monitor2 LIKE '$col'");
    if ($check && $check->num_rows > 0) {
        $results[] = "⚠ Colonna '$col' già presente — saltata.";
    } else {
        $conn->query("ALTER TABLE config_monitor2 ADD COLUMN $col $def");
        $results[] = "✔ Colonna '$col' aggiunta ($def).";
    }
}

$conn->close();

// ─── 4. Corregge object-fit in index2.php ────────────────────────────────────
$index2 = __DIR__ . '/index2.php';
if (!file_exists($index2)) {
    $results[] = "⚠ index2.php non trovato — saltato.";
} else {
    $content = file_get_contents($index2);
    $original = $content;
    $content = str_replace('object-fit:cover', 'object-fit:contain', $content);

    if ($content === $original) {
        $results[] = "⚠ index2.php: nessun 'object-fit:cover' trovato — già corretto.";
    } else {
        $count = substr_count($original, 'object-fit:cover');
        if (file_put_contents($index2, $content) !== false) {
            $results[] = "✔ index2.php: $count occorrenza/e di 'object-fit:cover' corrette in 'contain'.";
        } else {
            $results[] = "✘ index2.php: impossibile scrivere il file (permessi insufficienti).";
        }
    }
}

// ─── Output ───────────────────────────────────────────────────────────────────
if (php_sapi_name() === 'cli') {
    echo implode("\n", $results) . "\n";
} else {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>body{font-family:monospace;background:#0d1b2e;color:#cde;padding:2rem}
    h2{color:#42a5f5;margin-bottom:1rem}
    ul{list-style:none;padding:0}li{padding:.4rem 0;border-bottom:1px solid rgba(255,255,255,.08)}
    .ok{color:#69f0ae}.warn{color:#ffd740}.err{color:#ff5252}
    </style></head><body>
    <h2>Fix Setup — Bisceglie</h2><ul>';
    foreach ($results as $r) {
        $cls = strpos($r,'✔') !== false ? 'ok' : (strpos($r,'⚠') !== false ? 'warn' : 'err');
        echo "<li class=\"$cls\">" . htmlspecialchars($r) . "</li>";
    }
    echo '</ul><p style="margin-top:1.5rem;opacity:.4;font-size:.85rem">Eseguito il '
        . date('d/m/Y H:i:s') . '</p></body></html>';
}
