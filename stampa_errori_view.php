<?php
$remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remoteIP, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    die('<h2>Accesso consentito solo da localhost.</h2>');
}
$logFile = __DIR__ . '/stampa_errori.log';
$content = file_exists($logFile) ? htmlspecialchars(file_get_contents($logFile)) : '(nessun errore registrato)';
?>
<!DOCTYPE html><html lang="it"><head><meta charset="UTF-8"><title>Log Errori Stampa</title></head>
<body><h2>Log errori stampa</h2><pre><?php echo $content; ?></pre></body></html>
