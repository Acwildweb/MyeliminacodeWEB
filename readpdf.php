<?php
$pdf = file_get_contents("C:/xampp/htdocs/MySanitarioConTotem/D-F10116_NP-2511D-2_3511D-2_COM_6th_E.pdf");
preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m);

foreach($m[1] as $idx => $stream) {
    $dec = @gzuncompress($stream);
    if ($dec === false) $dec = @gzinflate($stream);
    if ($dec === false) continue;
    
    // Ricostruisci testo dalla sequenza di operatori Tj/TJ
    // decodifica hex a 2 byte come ASCII diretto
    $page = '';
    // Cerca tutti i <hex> Tj
    if (preg_match_all('/<([0-9A-Fa-f]{2,})>\s*Tj/', $dec, $hits)) {
        foreach($hits[1] as $h) {
            for($i=0;$i<strlen($h);$i+=2){
                $b = hexdec(substr($h,$i,2));
                $page .= chr($b);
            }
        }
    }
    // Cerca (text) Tj
    if (preg_match_all('/\(([^\)\\\\]{1,200})\)\s*Tj/', $dec, $hits2)) {
        $page .= implode('', $hits2[1]);
    }
    
    // Se la pagina contiene "cut" o "GS" stampa le ultime/prime 500 char della pagina
    if ($page !== '' && preg_match('/cut|GS.?V|cutter|partial|full cut/i', $page)) {
        echo "\n=== STREAM $idx ===\n";
        // Trova la posizione e mostra contesto
        preg_match_all('/.{0,80}(?:cut|GS.?V|cutter|partial|full cut).{0,80}/i', $page, $ctx);
        foreach($ctx[0] as $c) echo trim($c)."\n";
    }
}
