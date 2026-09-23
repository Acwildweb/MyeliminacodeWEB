<?php
// stampa_errori_view.php – Visualizzazione log errori stampa (solo localhost)
$remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIP, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('<h2>Accesso consentito solo da localhost.</h2>');
}
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
    <h2>📋 Log errori stampa termica</h2>
    <p><a href="config_stampante.php">← Torna alla configurazione</a></p>
    <pre><?php echo $content; ?></pre>
</body>
</html>
