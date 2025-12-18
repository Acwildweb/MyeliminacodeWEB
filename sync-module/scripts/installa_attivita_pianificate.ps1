<# 
.SYNOPSIS
    Installa le attività pianificate per MySanitario Sync

.DESCRIPTION
    Questo script crea tre attività pianificate in Windows:
    1. Sincronizzazione Completa - alle 06:00 ogni giorno
    2. Download Media - ogni ora
    3. Notifiche Email - ogni 5 minuti (verifica e invia se programmato)

.NOTES
    Eseguire come Amministratore
#>

param(
    [string]$SyncPath = "C:\xampp\htdocs\sync-module",
    [string]$SyncCompletaOrario = "06:00",
    [int]$MediaIntervalloOre = 1,
    [int]$NotificheIntervalloMinuti = 5
)

# Verifica privilegi amministratore
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "ERRORE: Questo script richiede privilegi di Amministratore!" -ForegroundColor Red
    Write-Host "Esegui PowerShell come Amministratore e riprova." -ForegroundColor Yellow
    exit 1
}

Write-Host "=== MySanitario Sync - Installazione Attività Pianificate ===" -ForegroundColor Cyan
Write-Host ""

# Verifica esistenza script
$scriptCompleto = "$SyncPath\scripts\sync_completo.bat"
$scriptMedia = "$SyncPath\scripts\sync_media.bat"

if (-not (Test-Path $scriptCompleto)) {
    Write-Host "ERRORE: Script non trovato: $scriptCompleto" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $scriptMedia)) {
    Write-Host "ERRORE: Script non trovato: $scriptMedia" -ForegroundColor Red
    exit 1
}

Write-Host "Script trovati in: $SyncPath\scripts\" -ForegroundColor Green
Write-Host ""

# --- Attività 1: Sincronizzazione Completa ---
Write-Host "Creazione attività: MySanitario_SyncCompleto..." -ForegroundColor Yellow

$taskName1 = "MySanitario_SyncCompleto"
$action1 = New-ScheduledTaskAction -Execute $scriptCompleto -WorkingDirectory "$SyncPath\scripts"
$trigger1 = New-ScheduledTaskTrigger -Daily -At $SyncCompletaOrario
$settings1 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Rimuovi se esiste
Unregister-ScheduledTask -TaskName $taskName1 -Confirm:$false -ErrorAction SilentlyContinue

# Crea nuova attività
Register-ScheduledTask -TaskName $taskName1 -Action $action1 -Trigger $trigger1 -Settings $settings1 -Description "Sincronizzazione completa MySanitario (azzera e riscrive dati)" | Out-Null

Write-Host "  ✓ Attività '$taskName1' creata - Orario: $SyncCompletaOrario ogni giorno" -ForegroundColor Green
Write-Host ""

# --- Attività 2: Download Media ---
Write-Host "Creazione attività: MySanitario_SyncMedia..." -ForegroundColor Yellow

$taskName2 = "MySanitario_SyncMedia"
$action2 = New-ScheduledTaskAction -Execute $scriptMedia -WorkingDirectory "$SyncPath\scripts"
$trigger2 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Hours $MediaIntervalloOre) -RepetitionDuration (New-TimeSpan -Days 9999)
$settings2 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Rimuovi se esiste
Unregister-ScheduledTask -TaskName $taskName2 -Confirm:$false -ErrorAction SilentlyContinue

# Crea nuova attività
Register-ScheduledTask -TaskName $taskName2 -Action $action2 -Trigger $trigger2 -Settings $settings2 -Description "Download file multimediali MySanitario" | Out-Null

Write-Host "  ✓ Attività '$taskName2' creata - Intervallo: ogni $MediaIntervalloOre ora/e" -ForegroundColor Green
Write-Host ""

# --- Attività 3: Notifiche Email ---
Write-Host "Creazione attività: MySanitario_Notifiche..." -ForegroundColor Yellow

$scriptNotifiche = "$SyncPath\scripts\send_notification.bat"
if (Test-Path $scriptNotifiche) {
    $taskName3 = "MySanitario_Notifiche"
    $action3 = New-ScheduledTaskAction -Execute $scriptNotifiche -WorkingDirectory "$SyncPath\scripts"
    $trigger3 = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes $NotificheIntervalloMinuti) -RepetitionDuration (New-TimeSpan -Days 9999)
    $settings3 = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

    # Rimuovi se esiste
    Unregister-ScheduledTask -TaskName $taskName3 -Confirm:$false -ErrorAction SilentlyContinue

    # Crea nuova attività
    Register-ScheduledTask -TaskName $taskName3 -Action $action3 -Trigger $trigger3 -Settings $settings3 -Description "Verifica e invio notifiche email MySanitario" | Out-Null

    Write-Host "  ✓ Attività '$taskName3' creata - Intervallo: ogni $NotificheIntervalloMinuti minuti" -ForegroundColor Green
} else {
    Write-Host "  ⚠ Script notifiche non trovato: $scriptNotifiche" -ForegroundColor Yellow
}
Write-Host ""

# --- Riepilogo ---
Write-Host "=== Installazione Completata ===" -ForegroundColor Cyan
Write-Host ""
Write-Host "Attività pianificate create:" -ForegroundColor White
Write-Host "  1. $taskName1 - Ogni giorno alle $SyncCompletaOrario" -ForegroundColor Gray
Write-Host "  2. $taskName2 - Ogni $MediaIntervalloOre ora/e" -ForegroundColor Gray
Write-Host "  3. MySanitario_Notifiche - Ogni $NotificheIntervalloMinuti minuti" -ForegroundColor Gray
Write-Host ""
Write-Host "Per visualizzare: taskschd.msc" -ForegroundColor Yellow
Write-Host "Per disinstallare: Unregister-ScheduledTask -TaskName 'MySanitario_*'" -ForegroundColor Yellow
Write-Host ""

# Mostra stato
Write-Host "Stato attività:" -ForegroundColor White
Get-ScheduledTask -TaskName "MySanitario_*" | Format-Table TaskName, State, Description -AutoSize
