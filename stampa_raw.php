<?php
// Ricevi i parametri
$turno = isset($_GET['turno']) ? $_GET['turno'] : '';
$numero = isset($_GET['numero']) ? $_GET['numero'] : '';
$data = isset($_GET['data']) ? $_GET['data'] : date('Ymd');

// Formatta data e ora
$dataFormattata = substr($data, 6, 2) . '/' . substr($data, 4, 2) . '/' . substr($data, 0, 4);
$oraFormattata = date('H:i');

// Inizializza comandi ESC/POS
$esc = "\x1B"; // ESC
$gs = "\x1D";  // GS

// Costruisci il contenuto della stampa
$output = "";

// Inizializza stampante
$output .= $esc . "@"; // Reset stampante

// Centra il testo
$output .= $esc . "a" . chr(1); // Allineamento centrale

// Intestazione
$output .= $esc . "!" . chr(0x10); // Font doppia altezza
$output .= "BIGLIETTO PRENOTAZIONE\n";
$output .= $esc . "!" . chr(0x00); // Reset font

// Linea separatrice
$output .= "================================\n";

// Turno
$output .= "\n";
$output .= $esc . "!" . chr(0x20); // Font doppia larghezza
$output .= "Turno: " . $turno . "\n";
$output .= $esc . "!" . chr(0x00); // Reset font
$output .= "\n";

// Numero (grande)
$output .= $esc . "!" . chr(0x38); // Font doppia larghezza e altezza + grassetto
$output .= $numero . "\n";
$output .= $esc . "!" . chr(0x00); // Reset font
$output .= "\n";

// Linea separatrice
$output .= "================================\n";

// Footer con data e ora
$output .= $esc . "!" . chr(0x01); // Font piccolo
$output .= "Data: " . $dataFormattata . "\n";
$output .= "Ora: " . $oraFormattata . "\n";
$output .= $esc . "!" . chr(0x00); // Reset font

// Linea di taglio
$output .= "\n";
$output .= "- - - - - - - - - - - -\n";
$output .= "\n\n\n";

// Taglia carta (se supportato)
$output .= $gs . "V" . chr(1); // Taglio parziale

// Invia l'output come file scaricabile per app di stampa
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="biglietto_' . $turno . '_' . $numero . '.prn"');
header('Content-Length: ' . strlen($output));

echo $output;
?>
