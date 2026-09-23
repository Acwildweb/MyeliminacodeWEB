<?php
// Debug log endpoint - da rimuovere in produzione
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/plain');

$logFile = __DIR__ . '/tts_debug.log';
$data = file_get_contents('php://input');
$line = date('H:i:s.') . substr(microtime(),2,3) . ' | ' . trim($data) . "\n";
file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
echo 'ok';
