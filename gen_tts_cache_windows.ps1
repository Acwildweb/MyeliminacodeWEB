# gen_tts_cache_windows.ps1
# Genera i file audio pre-renderizzati per il sistema di code MYSanitario
# Da eseguire UNA SOLA VOLTA sul server Windows (o quando si aggiungono voci/sportelli)
#
# Uso:
#   .\gen_tts_cache_windows.ps1
#   .\gen_tts_cache_windows.ps1 -Voci ElsaNeural,DiegoNeural
#   .\gen_tts_cache_windows.ps1 -MaxNumero 500 -MaxSportello 10
#
# Prerequisiti: Python 3.x con edge-tts installato
#   pip install edge-tts

param(
    [string[]]$Voci        = @("ElsaNeural","IsabellaNeural","DiegoNeural","GiuseppeMultilingualNeural"),
    [int]     $MaxNumero   = 999,
    [int]     $MaxSportello = 20,
    [string]  $CacheDir    = "C:\inetpub\wwwroot\tts_cache"
)

# ── Trova python.exe ───────────────────────────────────────────────────────
$pythonCandidates = @(
    "C:\Program Files\Python312\python.exe",
    "C:\Program Files\Python311\python.exe",
    "C:\Program Files\Python310\python.exe",
    "C:\Python312\python.exe",
    "C:\Python311\python.exe"
)
$python = $null
foreach ($p in $pythonCandidates) { if (Test-Path $p) { $python = $p; break } }
if (-not $python) {
    Write-Error "Python non trovato. Installa Python 3.x e assicurati che edge-tts sia installato (pip install edge-tts)."
    exit 1
}
Write-Host "Python trovato: $python" -ForegroundColor Green

# ── Mappa voce → nome edge-tts ────────────────────────────────────────────
$voiceMap = @{
    "ElsaNeural"                  = "it-IT-ElsaNeural"
    "IsabellaNeural"              = "it-IT-IsabellaNeural"
    "DiegoNeural"                 = "it-IT-DiegoNeural"
    "GiuseppeMultilingualNeural"  = "it-IT-GiuseppeMultilingualNeural"
}

# ── Funzione generazione singolo file ─────────────────────────────────────
function Gen-Audio {
    param([string]$Text, [string]$OutFile, [string]$EdgeVoice)
    # Salta se esiste già in qualsiasi formato (.mp3 o .wav)
    $base = [System.IO.Path]::ChangeExtension($OutFile, $null).TrimEnd('.')
    if (((Test-Path "$base.mp3") -and (Get-Item "$base.mp3").Length -gt 0) -or
        ((Test-Path "$base.wav") -and (Get-Item "$base.wav").Length -gt 0)) {
        return $true
    }
    & $python -m edge_tts --voice $EdgeVoice --write-media $OutFile --text $Text 2>$null
    if ((Test-Path $OutFile) -and (Get-Item $OutFile).Length -gt 0) { return $true }
    Write-Warning "  Fallito: $OutFile"
    Remove-Item $OutFile -ErrorAction SilentlyContinue
    return $false
}

$totalStart = Get-Date

foreach ($voce in $Voci) {
    $edgeVoice = $voiceMap[$voce]
    if (-not $edgeVoice) { Write-Warning "Voce '$voce' non nella mappa, skip."; continue }

    $outDir = Join-Path $CacheDir $voce
    New-Item -ItemType Directory -Force -Path $outDir | Out-Null

    Write-Host ""
    Write-Host "=== $voce ($edgeVoice) ===" -ForegroundColor Cyan
    $voceStart = Get-Date

    # ── Turni A-Z ────────────────────────────────────────────────────────
    Write-Host "  Generazione turni A-Z..." -NoNewline
    $ok = 0
    foreach ($code in 65..90) {
        $l = [char]$code
        if (Gen-Audio "Turno $l" (Join-Path $outDir "t_$l.mp3") $edgeVoice) { $ok++ }
    }
    Write-Host " $ok/26 OK"

    # ── Numeri 0-MaxNumero ───────────────────────────────────────────────
    Write-Host "  Generazione numeri 0-$MaxNumero..."
    $ok = 0; $step = [math]::Max(1, [math]::Floor($MaxNumero / 10))
    for ($n = 0; $n -le $MaxNumero; $n++) {
        if ($n % $step -eq 0) {
            $pct = [math]::Round($n / $MaxNumero * 100)
            Write-Host "    $n/$MaxNumero ($pct%)" -NoNewline
            Write-Host "`r" -NoNewline
        }
        if (Gen-Audio "numero $n" (Join-Path $outDir "n_$n.mp3") $edgeVoice) { $ok++ }
    }
    Write-Host "  Numeri: $ok/$($MaxNumero+1) OK          "

    # ── Sportelli 1-MaxSportello ─────────────────────────────────────────
    Write-Host "  Generazione sportelli 1-$MaxSportello..." -NoNewline
    $ok = 0
    for ($s = 1; $s -le $MaxSportello; $s++) {
        if (Gen-Audio "recarsi allo sportello $s" (Join-Path $outDir "s_$s.mp3") $edgeVoice) { $ok++ }
    }
    Write-Host " $ok/$MaxSportello OK"

    $elapsed = (Get-Date) - $voceStart
    Write-Host "  $voce completato in $([math]::Round($elapsed.TotalMinutes,1)) min" -ForegroundColor Green
}

$totalElapsed = (Get-Date) - $totalStart
Write-Host ""
Write-Host "Cache completata in $CacheDir" -ForegroundColor Green
Write-Host "Tempo totale: $([math]::Round($totalElapsed.TotalMinutes,1)) minuti" -ForegroundColor Green
Write-Host ""
Write-Host "Struttura generata:"
foreach ($voce in $Voci) {
    $dir = Join-Path $CacheDir $voce
    if (Test-Path $dir) {
        $count = (Get-ChildItem $dir -Filter "*.mp3").Count
        Write-Host "  $dir  ($count file MP3)"
    }
}
