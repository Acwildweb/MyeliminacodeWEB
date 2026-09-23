<?php
require_once __DIR__ . '/admin_auth_lib.php';
admin_require_page('stampa_errori', 'Log Stampa', 'Accesso da rete LAN — inserire utente e password');

$logFile = __DIR__ . '/stampa_errori.log';
$content = file_exists($logFile) ? htmlspecialchars(file_get_contents($logFile)) : '(nessun errore registrato)';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Log Errori Stampa</title>
    <style>
        body { font-family: monospace; background:#1e1e1e; color:#d4d4d4; padding:20px; }
        h2 { color:#4ec9b0; margin-bottom:16px; }
        pre { white-space: pre-wrap; word-wrap: break-word; font-size:13px; line-height:1.6; }
        a { color:#9cdcfe; }
    </style>
</head>
<body>
    <h2>Log errori stampa termica</h2>
    <p><a href="config_stampante.php">Torna alla configurazione</a> · <a href="amministrazione.php">Pannello admin</a></p>
    <pre><?php echo $content; ?></pre>
</body>
</html>
