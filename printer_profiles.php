<?php
/**
 * printer_profiles.php
 * Profili ESC/POS per modello stampante e generazione raster condivisa.
 */

/**
 * @return string[]
 */
function validPrinterModels(): array
{
    return [
        'axon_a8r',
        'np2511d2',
        'epson_t88',
        'escpos_generic',
        'custom_tg2460',
        'custom_tg2480',
    ];
}

/**
 * Etichette per la tendina modello in config_stampante.php.
 *
 * @return array<string, string>  value => label HTML
 */
function printerModelOptions(): array
{
    $out = [];
    foreach (validPrinterModels() as $id) {
        $out[$id] = getPrinterProfile($id)['label'];
    }
    return $out;
}

/**
 * @return array{cmdCodePage:string,cmdCut:string,feedLines:int,imgMode:string,maxImgWidth:int,textEncoding:string,label:string}
 */
function getPrinterProfile(string $model): array
{
    $ESC = "\x1B";
    $GS  = "\x1D";

    switch ($model) {
        case 'axon_a8r':
            // Axon Micrelec A8R / famiglia POS80K — emulazione ESC/POS (manuale programming POS80K)
            // ESC t 19 = PC858, GS v 0 = raster, GS V 65 = taglio parziale, 72mm @ 203dpi = 576 dot
            return [
                'cmdCodePage'  => $ESC . 't' . chr(19),
                'cmdCut'       => $GS . 'V' . chr(0x41) . chr(0),
                'feedLines'    => 4,
                'imgMode'      => 'gsv0',
                'maxImgWidth'  => 576,
                'textEncoding' => 'CP858',
                'label'        => 'Axon Micrelec A8R (80 mm ESC/POS) — consigliata',
            ];
        case 'epson_t88':
        case 'escpos_generic':
            return [
                'cmdCodePage'  => $ESC . 't' . chr(16),
                'cmdCut'       => $GS . 'V' . chr(0x41) . chr(0),
                'feedLines'    => 4,
                'imgMode'      => 'gsv0',
                'maxImgWidth'  => 384,
                'textEncoding' => 'CP1252',
                'label'        => 'ESC/POS generico / Epson',
            ];
        case 'custom_tg2460':
            return [
                'cmdCodePage'  => $ESC . 't' . chr(19),
                'cmdCut'       => $ESC . 'i',
                'feedLines'    => 5,
                'imgMode'      => 'esc_star',
                'maxImgWidth'  => 320,
                'textEncoding' => 'CP858',
                'label'        => 'Custom TG2460HIII (60mm)',
            ];
        case 'custom_tg2480':
            return [
                'cmdCodePage'  => $ESC . 't' . chr(19),
                'cmdCut'       => $ESC . 'i',
                'feedLines'    => 5,
                'imgMode'      => 'esc_star',
                'maxImgWidth'  => 384,
                'textEncoding' => 'CP858',
                'label'        => 'Custom TG2480HIII (80mm)',
            ];
        default: // np2511d2
            return [
                'cmdCodePage'  => $ESC . 't' . chr(5),
                'cmdCut'       => $ESC . 'i',
                'feedLines'    => 5,
                'imgMode'      => 'escb',
                'maxImgWidth'  => 384,
                'textEncoding' => 'CP1252',
                'label'        => 'NP-2511D-2',
            ];
    }
}

/**
 * Converte testo UTF-8 nella codifica del profilo stampante.
 */
function encodeEscPosText(string $text, string $model): string
{
    $enc = getPrinterProfile(normalizePrinterModel($model))['textEncoding'] ?? 'CP1252';
    $converted = @iconv('UTF-8', $enc . '//TRANSLIT', $text);
    return $converted !== false ? $converted : $text;
}

/**
 * Costruisce il payload ESC/POS del biglietto totem (condiviso da agente locale e stampa server).
 *
 * @param array{printer_model?:string,intestazione1?:string,intestazione2?:string,piede?:string} $config
 */
function buildEscPosTicket(string $turno, string $numero, string $dataYmd, array $config, ?string $logoFile = null): string
{
    $ESC = "\x1B";
    $model = normalizePrinterModel($config['printer_model'] ?? 'axon_a8r');
    $profile = getPrinterProfile($model);

    $intestazione1 = $config['intestazione1'] ?? 'BIGLIETTO PRENOTAZIONE';
    $intestazione2 = $config['intestazione2'] ?? '';
    $piede         = $config['piede']         ?? '';

    $dataFmt = strlen($dataYmd) === 8
        ? substr($dataYmd, 6, 2) . '/' . substr($dataYmd, 4, 2) . '/' . substr($dataYmd, 0, 4)
        : $dataYmd;
    $oraFmt = date('H:i');

    $enc = static function (string $s) use ($model): string {
        return encodeEscPosText($s, $model);
    };

    $out  = $ESC . '@';
    $out .= $profile['cmdCodePage'];
    $out .= $ESC . 'a' . chr(1);

    if ($logoFile !== null && $logoFile !== '' && file_exists($logoFile)) {
        $escImg = buildEscPosRaster($logoFile, $profile['maxImgWidth'], $profile['imgMode']);
        if ($escImg !== '') {
            $out .= $escImg;
            if ($profile['imgMode'] !== 'esc_star') {
                $out .= "\n";
            }
        }
    }

    $out .= $ESC . '!' . chr(0x30);
    $out .= $enc($intestazione1) . "\n";
    $out .= $ESC . '!' . chr(0x00);

    if ($intestazione2 !== '') {
        $out .= $ESC . '!' . chr(0x08);
        $out .= $enc($intestazione2) . "\n";
        $out .= $ESC . '!' . chr(0x00);
    }

    $out .= "================================\n\n";

    $out .= $ESC . '!' . chr(0x20);
    $out .= $enc('SPORTELLO') . "\n";
    $out .= $ESC . '!' . chr(0x00);

    $out .= $ESC . '!' . chr(0x38);
    $out .= $enc($turno) . "\n";
    $out .= $ESC . '!' . chr(0x00);

    $out .= "\n";
    $out .= $ESC . '!' . chr(0x20);
    $out .= $enc('NUMERO') . "\n";
    $out .= $ESC . '!' . chr(0x00);

    $out .= $ESC . '!' . chr(0x38);
    $out .= $enc($numero) . "\n";
    $out .= $ESC . '!' . chr(0x00);

    $out .= "\n================================\n\n";

    $out .= $ESC . '!' . chr(0x00);
    $out .= $enc('Data: ' . $dataFmt . '  Ora: ' . $oraFmt) . "\n";

    if ($piede !== '') {
        $out .= "\n";
        $out .= $enc($piede) . "\n";
    }

    $out .= $ESC . 'd' . chr($profile['feedLines']);
    $out .= $profile['cmdCut'];

    return $out;
}

/**
 * Carica immagine, ridimensiona e converte in raster 1-bit (MSB = sinistra).
 *
 * @return array{0:int,1:int,2:string}|null  [width, height, rowBytes]
 */
function loadEscPosRasterImage(string $filePath, int $maxWidth): ?array
{
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    switch ($ext) {
        case 'jpg': case 'jpeg': $imgSrc = @imagecreatefromjpeg($filePath); break;
        case 'png':              $imgSrc = @imagecreatefrompng($filePath);  break;
        case 'gif':              $imgSrc = @imagecreatefromgif($filePath);  break;
        case 'bmp':              $imgSrc = @imagecreatefrombmp($filePath);  break;
        case 'webp':             $imgSrc = @imagecreatefromwebp($filePath); break;
        default: return null;
    }
    if (!$imgSrc) {
        return null;
    }

    $origW = imagesx($imgSrc);
    $origH = imagesy($imgSrc);

    if ($origW > $maxWidth) {
        $newW = $maxWidth;
        $newH = (int)round($origH * $maxWidth / $origW);
        $resized = imagecreatetruecolor($newW, $newH);
        imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
        imagecopyresampled($resized, $imgSrc, 0, 0, 0, 0, $newW, $newH, $origW, $origH);
        imagedestroy($imgSrc);
        $imgSrc = $resized;
        $w = $newW;
        $h = $newH;
    } else {
        $w = $origW;
        $h = $origH;
    }

    $tc = imagecreatetruecolor($w, $h);
    imagefill($tc, 0, 0, imagecolorallocate($tc, 255, 255, 255));
    imagecopy($tc, $imgSrc, 0, 0, 0, 0, $w, $h);
    imagedestroy($imgSrc);

    $bytesPerRow = (int)ceil($w / 8);
    $rows = '';
    for ($y = 0; $y < $h; $y++) {
        for ($bx = 0; $bx < $bytesPerRow; $bx++) {
            $byte = 0;
            for ($bit = 0; $bit < 8; $bit++) {
                $px = $bx * 8 + $bit;
                if ($px < $w) {
                    $rgb = imagecolorat($tc, $px, $y);
                    $r   = ($rgb >> 16) & 0xFF;
                    $g   = ($rgb >> 8)  & 0xFF;
                    $b   =  $rgb        & 0xFF;
                    $lum = (int)(0.299 * $r + 0.587 * $g + 0.114 * $b);
                    if ($lum < 128) {
                        $byte |= (0x80 >> $bit);
                    }
                }
            }
            $rows .= chr($byte);
        }
    }
    imagedestroy($tc);

    return [$w, $h, $rows];
}

/**
 * Genera byte ESC/POS per stampa logo/immagine.
 *
 * @param string $mode  gsv0 | escb | esc_star
 */
function buildEscPosRaster(string $filePath, int $maxWidth = 384, string $mode = 'escb'): string
{
    $raster = loadEscPosRasterImage($filePath, $maxWidth);
    if ($raster === null) {
        return '';
    }

    [$w, $h, $data] = $raster;
    $bytesPerRow = (int)ceil($w / 8);
    $ESC = "\x1B";
    $GS  = "\x1D";

    if ($mode === 'gsv0') {
        $xL = $bytesPerRow & 0xFF;
        $xH = ($bytesPerRow >> 8) & 0xFF;
        $yL = $h & 0xFF;
        $yH = ($h >> 8) & 0xFF;
        return $GS . 'v' . '0' . chr(0x00) . chr($xL) . chr($xH) . chr($yL) . chr($yH) . $data;
    }

    if ($mode === 'esc_star') {
        $out = '';
        $rowLen = strlen($data) / $h;
        $nL = $bytesPerRow & 0xFF;
        $nH = ($bytesPerRow >> 8) & 0xFF;
        for ($y = 0; $y < $h; $y++) {
            $rowData = substr($data, (int)($y * $rowLen), (int)$rowLen);
            $out .= $ESC . '*' . chr(0) . chr($nL) . chr($nH) . $rowData . "\n";
        }
        return $out;
    }

    // escb — NP-2511D-2
    $n1 = $bytesPerRow;
    $n2 = $h & 0xFF;
    $n3 = ($h >> 8) & 0xFF;
    return $ESC . 'b' . chr($n1) . chr($n2) . chr($n3) . $data
         . $ESC . 'J' . chr(0);
}

/**
 * Normalizza printer_model da config; fallback np2511d2.
 */
function normalizePrinterModel(?string $model): string
{
    $model = trim((string)$model);
    return in_array($model, validPrinterModels(), true) ? $model : 'axon_a8r';
}

/**
 * URL predefinito dell'agente locale (Modalità B) in base alla cartella dell'app.
 */
function defaultLocalAgentUrl(): string
{
    $dir = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($dir === '' || $dir === '.') {
        $dir = 'MySanitarioConTotem';
    }
    return 'http://localhost/' . $dir . '/print_agent.php';
}
