# Deploy Windows (IIS)

## Task Scheduler (esempi su questa installazione)

### 1. Importazione configurazione generale mysanitario
- Programma: `C:\PHP\php.exe`
- Argomenti: `C:\inetpub\wwwroot\tocron\import_impostazioni.php`
- Pianificazione tipica: ogni giorno alle 20:00
- Effetto: sync orari/turni da pannello remoto + bump stamp auto-reload totem

### 2. sincronizza-immagini_myeliminacode
- Programma: `C:\PHP\php.exe`
- Argomenti: `C:\inetpub\wwwroot\tocron\download_images.php >> C:\inetpub\wwwroot\tocron\task_scheduler.log 2>&1`
- Pianificazione: periodica (es. ogni pochi minuti)

## Requisiti
- IIS + PHP (curl, zip, json, mysqli)
- File locale `update_config.json` (da `update_config.example.json`) con PAT GitHub Contents: Read
- `connect.php` locale (da `connect.example.php`)

## Aggiornamenti
Amministrazione → card Aggiornamenti → Verifica / Aggiorna (branch `windows-iis`).