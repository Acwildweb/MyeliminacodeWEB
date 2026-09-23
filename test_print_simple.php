<?php
// Test minimo: solo ASCII puro + comandi base ESC/POS
$ESC = "\x1B";
$GS  = "\x1D";

$out = '';
$out .= $ESC . '@';           // reset
$out .= $ESC . 't' . chr(5); // Code Page 1252 (Windows-1252: à è é ì ò ù) - manuale par.36
$out .= $ESC . 'a' . chr(1); // centra
$out .= "\n";
$out .= "***** TEST STAMPA *****\n";
$out .= "\n";
// Testo con caratteri italiani (iconv UTF-8 → CP850)
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', "Turno: Accettazione\n");
$out .= "Numero: 001\n";
$out .= "\n";
$out .= iconv('UTF-8', 'CP1252//TRANSLIT', "Benvenuto! Attendi il\ntuo turno. Grazie.\n");
$out .= "\n";
$out .= "Data: 18/04/2026\n";
$out .= $ESC . 'd' . chr(12); // feed 12 righe (porta carta al cutter)
$out .= $ESC . 'i';           // ESC i = full cut (manuale ufficiale NP-2511D-2, cmd 30)

$tmpFile = sys_get_temp_dir() . '\test_raw.bin';
file_put_contents($tmpFile, $out);

$ps1 = 'C:\\xampp\\htdocs\\MySanitarioConTotem\\print_raw.ps1';
$printerName = '\\\\192.168.178.12\\NPI Integration Driver (Copia 1)';
$cmd = 'powershell.exe -ExecutionPolicy Bypass -NonInteractive -WindowStyle Hidden'
     . ' -File "' . $ps1 . '"'
     . ' -PrinterName ' . escapeshellarg($printerName)
     . ' -FilePath ' . escapeshellarg($tmpFile)
     . ' 2>&1';
$out2 = shell_exec($cmd);
@unlink($tmpFile);
echo trim($out2);
