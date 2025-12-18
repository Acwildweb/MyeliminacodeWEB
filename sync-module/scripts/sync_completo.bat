@echo off
REM ============================================================
REM MySanitario Sync - Sincronizzazione Completa
REM ============================================================
REM Questo script esegue una sincronizzazione completa:
REM - Scarica turni, postazioni, operazioni
REM - AZZERA E RISCRIVE i dati nel database locale
REM - Scarica file multimediali
REM 
REM Schedulare con Task Scheduler: una volta al giorno (es. alle 06:00)
REM ============================================================

REM Imposta il percorso di PHP (modifica se necessario)
SET PHP_PATH=C:\xampp\php\php.exe

REM Percorso del modulo sync
SET SYNC_PATH=C:\xampp\htdocs\sync-module

REM Log file
SET LOG_FILE=%SYNC_PATH%\logs\scheduled_sync.log

REM Timestamp
FOR /F "tokens=1-4 delims=/ " %%a IN ('date /t') DO SET DATE=%%c-%%b-%%a
FOR /F "tokens=1-2 delims=: " %%a IN ('time /t') DO SET TIME=%%a:%%b

echo [%DATE% %TIME%] === Inizio Sincronizzazione Completa === >> "%LOG_FILE%"

REM Esegui sincronizzazione completa
"%PHP_PATH%" "%SYNC_PATH%\sync.php" --full --media >> "%LOG_FILE%" 2>&1

IF %ERRORLEVEL% EQU 0 (
    echo [%DATE% %TIME%] Sincronizzazione completata con successo >> "%LOG_FILE%"
) ELSE (
    echo [%DATE% %TIME%] ERRORE: Sincronizzazione fallita (codice: %ERRORLEVEL%) >> "%LOG_FILE%"
)

echo [%DATE% %TIME%] === Fine Sincronizzazione Completa === >> "%LOG_FILE%"
echo. >> "%LOG_FILE%"

exit /b %ERRORLEVEL%
