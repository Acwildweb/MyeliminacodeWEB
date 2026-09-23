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
        'np2511d2',
        'epson_t88',
        'escpos_generic',
        'custom_tg2460',
        'custom_tg2480',
    ];
}

/**
 * @return array{cmdCodePage:string,cmdCut:string,feedLines:int,imgMode:string,maxImgWidth:int,label:string}
 */
function getPrinterProfile(string $model): array
{
    $ESC = "\x1B";
    $GS  = "\x1D";

    switch ($model) {
        case 'epson_t88':
        case 'escpos_generic':
            return [
                'cmdCodePage' => $ESC . 't' . chr(16),
                'cmdCut'      => $GS . 'V' . chr(0x41) . chr(0),
                'feedLines'   => 4,
                'imgMode'     => 'gsv0',
                'maxImgWidth' => 384,
                'label'       => 'ESC/POS generico / Epson',
            ];
        case 'custom_tg2460':
            return [
                'cmdCodePage' => $ESC . 't' . chr(19),
                'cmdCut'      => $ESC . 'i',
                'feedLines'   => 5,
                'imgMode'     => 'esc_star',
                'maxImgWidth' => 320,
                'label'       => 'Custom TG2460HIII (60mm)',
            ];
        case 'custom_tg2480':
            return [
                'cmdCodePage' => $ESC . 't' . chr(19),
                'cmdCut'      => $ESC . 'i',
                'feedLines'   => 5,
                'imgMode'     => 'esc_star',
                'maxImgWidth' => 384,
                'label'       => 'Custom TG2480HIII (80mm)',
            ];
        default: // np2511d2
            return [
                'cmdCodePage' => $ESC . 't' . chr(5),
                'cmdCut'      => $ESC . 'i',
                'feedLines'   => 5,
                'imgMode'     => 'escb',
                'maxImgWidth' => 384,
                'label'       => 'NP-2511D-2',
            ];
    }
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
    return in_array($model, validPrinterModels(), true) ? $model : 'np2511d2';
}
