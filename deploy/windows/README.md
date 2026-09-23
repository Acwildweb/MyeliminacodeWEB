# Deploy Windows (IIS)

## Task Scheduler

### 1. Importazione configurazione generale mysanitario
- Programma: `C:\PHP\php.exe`
- Argomenti: `C:\inetpub\wwwroot\tocron\import_impostazioni.php`
- Pianificazione tipica: ogni giorno alle 20:00

### 2. sincronizza-immagini_myeliminacode
- Programma: `C:\PHP\php.exe`
- Argomenti: `C:\inetpub\wwwroot\tocron\download_images.php >> C:\inetpub\wwwroot\tocron\task_scheduler.log 2>&1`

## Requisiti
- IIS + PHP (curl, zip, json, mysqli)
- `update_config.json` locale con PAT
- `connect.php` locale

## Aggiornamenti
Amministrazione → Aggiornamenti software → Verifica / Aggiorna (branch `windows-iis`).
