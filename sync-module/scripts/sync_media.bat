@echo off
REM ============================================================
REM MySanitario Sync - Download File Multimediali
REM ============================================================
REM Questo script scarica solo i file multimediali:
REM - Immagini di sfondo monitor/totem
REM - Immagini pulsanti turni
REM - NON modifica i dati nel database
REM 
REM Schedulare con Task Scheduler: ogni ora
REM ============================================================

REM Imposta il percorso di PHP (modifica se necessario)
SET PHP_PATH=C:\xampp\php\php.exe

REM Percorso del modulo sync
SET SYNC_PATH=C:\xampp\htdocs\sync-module

REM Log file
SET LOG_FILE=%SYNC_PATH%\logs\scheduled_media.log

REM Timestamp
FOR /F "tokens=1-4 delims=/ " %%a IN ('date /t') DO SET DATE=%%c-%%b-%%a
FOR /F "tokens=1-2 delims=: " %%a IN ('time /t') DO SET TIME=%%a:%%b

echo [%DATE% %TIME%] === Inizio Download Media === >> "%LOG_FILE%"

REM Esegui solo download media (senza --full)
"%PHP_PATH%" "%SYNC_PATH%\sync.php" --media >> "%LOG_FILE%" 2>&1

IF %ERRORLEVEL% EQU 0 (
    echo [%DATE% %TIME%] Download media completato con successo >> "%LOG_FILE%"
) ELSE (
    echo [%DATE% %TIME%] ERRORE: Download media fallito (codice: %ERRORLEVEL%) >> "%LOG_FILE%"
)

echo [%DATE% %TIME%] === Fine Download Media === >> "%LOG_FILE%"
echo. >> "%LOG_FILE%"

exit /b %ERRORLEVEL%
