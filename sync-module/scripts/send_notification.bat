@echo off
REM ============================================================
REM MySanitario Sync - Invio Notifiche Email
REM ============================================================
REM Questo script verifica se è il momento di inviare la notifica
REM programmata e, se sì, la invia.
REM 
REM Schedulare con Task Scheduler: ogni 5 minuti
REM ============================================================

REM Imposta il percorso di PHP (modifica se necessario)
SET PHP_PATH=C:\xampp\php\php.exe

REM Percorso del modulo sync
SET SYNC_PATH=C:\xampp\htdocs\sync-module

REM Log file
SET LOG_FILE=%SYNC_PATH%\logs\scheduled_notifications.log

REM Timestamp
FOR /F "tokens=1-4 delims=/ " %%a IN ('date /t') DO SET DATE=%%c-%%b-%%a
FOR /F "tokens=1-2 delims=: " %%a IN ('time /t') DO SET TIME=%%a:%%b

REM Esegui controllo e invio notifica
"%PHP_PATH%" "%SYNC_PATH%\send_notification.php" >> "%LOG_FILE%" 2>&1

exit /b %ERRORLEVEL%
