<?php
$testo = 'Turno A, numero 1. Recarsi allo sportello 5.';
$tmp = tempnam(sys_get_temp_dir(), 'tts_');
$mp3 = $tmp . '.mp3';
$wav = $tmp . '.wav';

exec('/usr/local/bin/edge-tts-wrapper --voice it-IT-ElsaNeural --text ' . escapeshellarg($testo) . ' --write-media ' . escapeshellarg($mp3) . ' 2>/dev/null');
exec('/usr/bin/ffmpeg -y -i ' . escapeshellarg($mp3) . ' -ar 22050 -ac 1 ' . escapeshellarg($wav) . ' 2>/dev/null');

if (file_exists($wav) && filesize($wav) > 0) {
    header('Content-Type: audio/wav');
    header('Content-Length: ' . filesize($wav));
    header('Cache-Control: no-store');
    readfile($wav);
} else {
    echo "ERRORE: wav=" . (file_exists($wav) ? filesize($wav)."b" : "NOPE") . " mp3=" . (file_exists($mp3) ? filesize($mp3)."b" : "NOPE");
}
@unlink($tmp); @unlink($mp3); @unlink($wav);
