<?php
$testo = $_GET['t'] ?? 'Turno A, numero 1. Recarsi allo sportello 5.';
$testo = preg_replace('/[<>"&;|`$\\\\]/u', ' ', $testo);
$testo = trim($testo);

$tmp = tempnam(sys_get_temp_dir(), 'tts_');
$mp3 = $tmp . '.mp3';
$wav = $tmp . '.wav';
$ffmpeg = '/usr/bin/ffmpeg';

$cmd = '/usr/local/bin/edge-tts-wrapper --voice it-IT-ElsaNeural --text ' . escapeshellarg($testo) . ' --write-media ' . escapeshellarg($mp3) . ' 2>&1';
echo "CMD: $cmd\n";
exec($cmd, $out, $ret);
echo "Exit: $ret\nOutput: " . implode("\n", $out) . "\n";
echo "MP3: " . (file_exists($mp3) ? filesize($mp3)." bytes" : "NON ESISTE") . "\n";

if (file_exists($mp3)) {
    $ffcmd = escapeshellarg($ffmpeg) . ' -y -i ' . escapeshellarg($mp3) . ' -ar 22050 -ac 1 ' . escapeshellarg($wav) . ' 2>&1';
    echo "FFCMD: $ffcmd\n";
    exec($ffcmd, $out2, $ret2);
    echo "FFmpeg exit: $ret2\n";
    echo "FFmpeg output: " . implode("\n", array_slice($out2, -3)) . "\n";
    echo "WAV: " . (file_exists($wav) ? filesize($wav)." bytes" : "NON ESISTE") . "\n";
    @unlink($mp3); @unlink($wav);
}
@unlink($tmp);
