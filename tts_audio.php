<?php
// tts_audio.php — genera audio WAV
// Supporta: Windows IIS (SAPI via PowerShell) + Linux (edge-tts / espeak-ng)

// ── Modalità debug: chiamare con ?debug=1 (solo da localhost/LAN) ─────────
if (isset($_GET['debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "=== TTS DEBUG ===\n";
    echo "PHP_OS       : " . PHP_OS . "\n";
    echo "exec abilitato: " . (function_exists('exec') ? 'SI' : 'NO') . "\n\n";

    // Cerca python.exe
    $pythonCandidates = [
        'C:\\Program Files\\Python312\\python.exe',
        'C:\\Program Files\\Python311\\python.exe',
        'C:\\Python312\\python.exe',
    ];
    echo "--- Ricerca python.exe ---\n";
    foreach ($pythonCandidates as $p) {
        echo "$p : " . (file_exists($p) ? 'TROVATO' : 'non trovato') . "\n";
    }

    // Test edge_tts come modulo Python
    $pythonExe = null;
    foreach ($pythonCandidates as $p) { if (file_exists($p)) { $pythonExe = $p; break; } }
    if ($pythonExe) {
        $mp3 = sys_get_temp_dir() . '\\tts_dbg.mp3';
        $cmd = '"' . $pythonExe . '" -m edge_tts --voice it-IT-ElsaNeural --write-media "' . $mp3 . '" --text "test audio" 2>&1';
        $out = []; $ret = 0; exec($cmd, $out, $ret);
        echo "\n--- Test edge_tts (python -m edge_tts) ---\n";
        echo "Exit code: $ret\n";
        echo "Output   : " . implode("\n", $out) . "\n";
        echo "MP3 creato: " . (file_exists($mp3) && filesize($mp3) > 0 ? 'SI (' . filesize($mp3) . ' bytes)' : 'NO') . "\n";
        @unlink($mp3);
    }

    // Test PowerShell SAPI con file .ps1
    echo "\n--- Test PowerShell SAPI (file .ps1) ---\n";
    $wav = sys_get_temp_dir() . '\\tts_dbg.wav';
    $ps1 = sys_get_temp_dir() . '\\tts_dbg.ps1';
    $psScript = 'Add-Type -AssemblyName System.Speech' . "\r\n"
              . '$s = New-Object System.Speech.Synthesis.SpeechSynthesizer' . "\r\n"
              . '$s.SetOutputToWaveFile("' . addslashes($wav) . '")' . "\r\n"
              . '$s.Speak("test audio")' . "\r\n"
              . '$s.SetOutputToDefaultAudioDevice()' . "\r\n";
    file_put_contents($ps1, $psScript);
    $psOut = []; $psRet = 0;
    exec('powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $ps1 . '" 2>&1', $psOut, $psRet);
    echo "Exit code: $psRet\n";
    echo "Output   : " . implode("\n", $psOut) . "\n";
    echo "WAV creato: " . (file_exists($wav) && filesize($wav) > 0 ? 'SI (' . filesize($wav) . ' bytes)' : 'NO') . "\n";
    @unlink($ps1); @unlink($wav);
    exit;
}

$testo = isset($_GET['t']) ? trim($_GET['t']) : '';
if (!$testo || strlen($testo) > 300) { http_response_code(400); exit; }
$testo = preg_replace('/[<>"&;|`$\\\\]/u', ' ', $testo);
$testo = trim($testo);

$cfgFile = __DIR__ . '/totem_ui_config.json';
$cfg = file_exists($cfgFile) ? (json_decode(file_get_contents($cfgFile), true) ?: []) : [];

$allowedVoci = ['ElsaNeural','IsabellaNeural','DiegoNeural','GiuseppeMultilingualNeural','paola','riccardo','mb-it2','mb-it3','it'];
$voce = isset($_GET['voce']) && in_array($_GET['voce'], $allowedVoci)
    ? $_GET['voce']
    : (in_array($cfg['tts_voce'] ?? '', $allowedVoci) ? $cfg['tts_voce'] : 'ElsaNeural');

$velocita = isset($_GET['vel']) ? intval($_GET['vel']) : intval($cfg['tts_velocita'] ?? 135);
$velocita = max(80, min(220, $velocita));

if (!isset($_GET['voce']) && isset($cfg['tts_attivo']) && !$cfg['tts_attivo']) {
    http_response_code(204); exit;
}

$isWindows = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');
$edgeVoci  = ['ElsaNeural','IsabellaNeural','DiegoNeural','GiuseppeMultilingualNeural'];

// ── Cache md5 su disco (testo completo) ──────────────────────────────
$_cacheDir  = __DIR__ . DIRECTORY_SEPARATOR . 'tts_cache';
$_cacheKey  = md5($testo . $voce);
$_cachePath = $_cacheDir . DIRECTORY_SEPARATOR . $_cacheKey . '.mp3';
if (is_file($_cachePath) && filesize($_cachePath) > 0) {
    header('Content-Type: audio/mpeg');
    header('Content-Length: ' . filesize($_cachePath));
    header('Cache-Control: public, max-age=86400');
    readfile($_cachePath);
    exit;
}

// ── WINDOWS ──────────────────────────────────────────────────────────────────
if ($isWindows) {

    // ── Tentativo 0: composizione dai pezzi pre-generati (istantaneo) ─────
    // Pattern: "Turno X, numero N. Recarsi allo sportello S"
    if (preg_match('/^turno\s+([A-Z])[,\.]*\s+numero\s+(\d+)[,\.]*\s+recarsi\s+allo\s+sportello\s+(\d+)/iu', $testo, $pm)) {
        $pLettera   = strtoupper($pm[1]);
        $pNumero    = intval($pm[2]);
        $pSportello = intval($pm[3]);
        $pVoceDir   = $_cacheDir . DIRECTORY_SEPARATOR . $voce;

        // Estrae PCM + parametri da un file WAV parsando i chunk RIFF dinamicamente
        $wavExtract = function($path) {
            $raw = @file_get_contents($path);
            if (!$raw || strlen($raw) < 12) return null;
            if (substr($raw, 0, 4) !== 'RIFF' || substr($raw, 8, 4) !== 'WAVE') return null;
            $pos = 12; $ch = 1; $sr = 22050; $bps = 16; $pcm = '';
            while ($pos + 8 <= strlen($raw)) {
                $id   = substr($raw, $pos, 4);
                $size = unpack('V', substr($raw, $pos + 4, 4))[1];
                if ($id === 'fmt ') {
                    $ch  = unpack('v', substr($raw, $pos + 10, 2))[1];
                    $sr  = unpack('V', substr($raw, $pos + 12, 4))[1];
                    $bps = unpack('v', substr($raw, $pos + 22, 2))[1];
                } elseif ($id === 'data') {
                    $pcm = substr($raw, $pos + 8, $size);
                    break;
                }
                $pos += 8 + $size + ($size & 1);
            }
            return $pcm ? compact('pcm', 'ch', 'sr', 'bps') : null;
        };

        // Cerca prima .mp3 (generati da gen_tts_cache_windows.ps1), poi .wav (piper Linux)
        foreach (['mp3', 'wav'] as $ext) {
            $pT = $pVoceDir . DIRECTORY_SEPARATOR . "t_{$pLettera}.{$ext}";
            $pN = $pVoceDir . DIRECTORY_SEPARATOR . "n_{$pNumero}.{$ext}";
            $pS = $pVoceDir . DIRECTORY_SEPARATOR . "s_{$pSportello}.{$ext}";
            if (!is_file($pT)||!is_file($pN)||!is_file($pS)) continue;
            if (!filesize($pT)||!filesize($pN)||!filesize($pS)) continue;

            if ($ext === 'mp3') {
                // MP3: concatenazione diretta dei frame (i frame MP3 sono self-contained)
                header('Content-Type: audio/mpeg');
                header('Cache-Control: public, max-age=86400');
                readfile($pT); readfile($pN); readfile($pS);
                exit;
            }

            // WAV: parsing robusto dei chunk RIFF, poi ricostruzione header pulita
            $dT = $wavExtract($pT);
            $dN = $wavExtract($pN);
            $dS = $wavExtract($pS);
            if (!$dT || !$dN || !$dS) continue; // parsing fallito, prova altro ext

            $pcm  = $dT['pcm'] . $dN['pcm'] . $dS['pcm'];
            $dLen = strlen($pcm);
            // Ricostruisce header WAV standard 44 byte dai parametri del primo file
            $byteRate   = $dT['sr'] * $dT['ch'] * intdiv($dT['bps'], 8);
            $blockAlign = $dT['ch'] * intdiv($dT['bps'], 8);
            $hdr  = 'RIFF' . pack('V', 36 + $dLen) . 'WAVE';
            $hdr .= 'fmt ' . pack('V', 16) . pack('v', 1)
                  . pack('v', $dT['ch'])  . pack('V', $dT['sr'])
                  . pack('V', $byteRate)  . pack('v', $blockAlign)
                  . pack('v', $dT['bps']);
            $hdr .= 'data' . pack('V', $dLen);
            header('Content-Type: audio/wav');
            header('Content-Length: ' . (44 + $dLen));
            header('Cache-Control: public, max-age=86400');
            echo $hdr . $pcm;
            exit;
        }
    }

    // ── Tentativo 1: edge-tts via python.exe diretto (evita problemi PATH IIS)
    $mp3 = tempnam(sys_get_temp_dir(), 'tts_') . '.mp3';
    $edgeVoiceName = in_array($voce, $edgeVoci) ? ('it-IT-' . $voce) : 'it-IT-ElsaNeural';

    // Cerca python.exe nei percorsi standard
    $pythonCandidates = [
        'C:\\Program Files\\Python312\\python.exe',
        'C:\\Program Files\\Python311\\python.exe',
        'C:\\Program Files\\Python310\\python.exe',
        'C:\\Python312\\python.exe',
        'C:\\Python311\\python.exe',
    ];
    $pythonExe = null;
    foreach ($pythonCandidates as $p) {
        if (file_exists($p)) { $pythonExe = $p; break; }
    }

    if ($pythonExe !== null) {
        // Chiama edge_tts come modulo Python → nessun problema di PATH
        $cmd = '"' . $pythonExe . '" -m edge_tts'
             . ' --voice ' . escapeshellarg($edgeVoiceName)
             . ' --write-media ' . escapeshellarg($mp3)
             . ' --text ' . escapeshellarg($testo)
             . ' 2>NUL';
        exec($cmd);
        if (file_exists($mp3) && filesize($mp3) > 0) {
            // Salva in cache per le prossime richieste identiche
            if (!is_dir($_cacheDir)) { @mkdir($_cacheDir, 0755, true); }
            @copy($mp3, $_cachePath);
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . filesize($mp3));
            header('Cache-Control: public, max-age=86400');
            readfile($mp3);
            @unlink($mp3);
            exit;
        }
    }
    @unlink($mp3);

    // ── Tentativo 2: SAPI via script .ps1 temporaneo (fix escaping) ──────
    $wav  = tempnam(sys_get_temp_dir(), 'tts_') . '.wav';
    $ps1  = tempnam(sys_get_temp_dir(), 'tts_') . '.ps1';

    // Unica voce rilevata sul server: Microsoft Zira Desktop (EN)
    $sapiVoce = 'Microsoft Zira Desktop';
    $sapiRate = (int)round(($velocita - 135) / 14);
    $sapiRate = max(-5, min(5, $sapiRate));

    // Scrive lo script PS1 su file → nessun problema di escaping inline
    $psScript = 'Add-Type -AssemblyName System.Speech' . "\r\n"
              . '$s = New-Object System.Speech.Synthesis.SpeechSynthesizer' . "\r\n"
              . 'try { $s.SelectVoice("' . addslashes($sapiVoce) . '") } catch {}' . "\r\n"
              . '$s.Rate = ' . $sapiRate . "\r\n"
              . '$s.SetOutputToWaveFile("' . addslashes($wav) . '")' . "\r\n"
              . '$s.Speak("' . addslashes($testo) . '")' . "\r\n"
              . '$s.SetOutputToDefaultAudioDevice()' . "\r\n";
    file_put_contents($ps1, $psScript);

    exec('powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' . $ps1 . '"');
    @unlink($ps1);

    if (file_exists($wav) && filesize($wav) > 0) {
        header('Content-Type: audio/wav');
        header('Content-Length: ' . filesize($wav));
        header('Cache-Control: no-store');
        readfile($wav);
        @unlink($wav);
        exit;
    }
    @unlink($wav);

    http_response_code(503);
    exit;
}

// ── LINUX: edge-tts Neural ────────────────────────────────────────────────
if (in_array($voce, $edgeVoci)) {
    $tmp = tempnam(sys_get_temp_dir(), 'tts_');
    $mp3 = $tmp . '.mp3';
    $wav = $tmp . '.wav';
    exec('/usr/local/bin/edge-tts-wrapper --voice ' . escapeshellarg('it-IT-' . $voce)
         . ' --text ' . escapeshellarg($testo)
         . ' --write-media ' . escapeshellarg($mp3) . ' 2>/dev/null');
    if (file_exists($mp3) && filesize($mp3) > 0) {
        exec('/usr/bin/ffmpeg -y -i ' . escapeshellarg($mp3)
             . ' -ar 22050 -ac 1 ' . escapeshellarg($wav) . ' 2>/dev/null');
        if (file_exists($wav) && filesize($wav) > 0) {
            header('Content-Type: audio/wav');
            header('Content-Length: ' . filesize($wav));
            header('Cache-Control: no-store');
            readfile($wav);
            @unlink($mp3); @unlink($wav); @unlink($tmp);
            exit;
        }
    }
    @unlink($mp3); @unlink($wav); @unlink($tmp);
}

// ── LINUX: fallback espeak-ng ─────────────────────────────────────────────
header('Content-Type: audio/wav');
header('Cache-Control: no-store');
passthru('/usr/bin/espeak-ng -v it -s ' . intval($velocita)
       . ' --stdout ' . escapeshellarg($testo) . ' 2>/dev/null');
