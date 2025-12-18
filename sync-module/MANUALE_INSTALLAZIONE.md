# MySanitario Sync Module
## Manuale di Installazione e Utilizzo

**Versione:** 1.0.0  
**Data:** Dicembre 2025  
**Compatibilità:** Windows Server / Windows 10/11 con XAMPP

---

## 📋 Indice

1. [Descrizione](#descrizione)
2. [Requisiti di Sistema](#requisiti-di-sistema)
3. [Struttura File](#struttura-file)
4. [Installazione](#installazione)
5. [Configurazione](#configurazione)
6. [Utilizzo Interfaccia Web](#utilizzo-interfaccia-web)
7. [Utilizzo da Linea di Comando](#utilizzo-da-linea-di-comando)
8. [Attività Pianificate](#attività-pianificate)
9. [Risoluzione Problemi](#risoluzione-problemi)

---

## Descrizione

Il **MySanitario Sync Module** è un modulo PHP che sincronizza le configurazioni dal server online MySanitario al database locale. 

### Funzionalità principali:
- ✅ Download configurazioni (turni, postazioni, operazioni) dal server online
- ✅ Scrittura nel database MySQL locale o remoto
- ✅ Download automatico file multimediali (immagini sfondo, pulsanti)
- ✅ Interfaccia web per configurazione e test
- ✅ Script per esecuzione automatica programmata
- ✅ API REST per integrazione con altri sistemi

---

## Requisiti di Sistema

### Software richiesto:
- **XAMPP** (o Apache + PHP + MySQL separati)
  - PHP 7.4+ (consigliato 8.0+)
  - Estensioni PHP: `mysqli`, `curl`, `json`
- **MySQL/MariaDB** 5.7+ (può essere locale o remoto)

### Requisiti di rete:
- Accesso a Internet per raggiungere `https://myeliminacode.acwild.eu/`
- Accesso al database MySQL (locale o sulla rete)

---

## Struttura File

```
sync-module/
├── index.php                 # Interfaccia web principale
├── email.php                 # 📧 Configurazione Email e Notifiche
├── sync.php                  # Script CLI per cron/batch
├── send_notification.php     # Script CLI per invio notifiche
├── bootstrap.php             # Autoloader e inizializzazione
├── .htaccess                 # Configurazione Apache
│
├── config/
│   ├── config.php            # Configurazione base (non modificare)
│   ├── local.php             # ⚙️ CONFIGURAZIONE LOCALE (da personalizzare)
│   └── notifications.json    # Configurazione notifiche programmate
│
├── src/
│   ├── Database.php          # Gestione connessione MySQL
│   ├── HttpClient.php        # Client HTTP per API remote
│   ├── Logger.php            # Sistema di logging
│   ├── SyncService.php       # Logica di sincronizzazione
│   ├── EmailService.php      # 📧 Servizio invio email SMTP
│   └── NotificationScheduler.php  # 🔔 Gestione notifiche programmate
│
├── api/
│   └── sync.php              # Endpoint API REST
│
├── scripts/
│   ├── sync_completo.bat     # 🔄 Batch: Sync completa (per Task Scheduler)
│   ├── sync_media.bat        # 📷 Batch: Solo download media
│   ├── send_notification.bat # 📧 Batch: Invio notifiche (ogni 5 min)
│   └── installa_attivita_pianificate.ps1  # Script installazione automatica
│
├── logs/                     # File di log
│   ├── sync.log              # Log principale
│   ├── scheduled_sync.log    # Log sync pianificata
│   ├── scheduled_media.log   # Log media pianificata
│   └── scheduled_notifications.log  # Log notifiche
│
└── media/                    # File multimediali scaricati
    └── images/
```

---

## Installazione

### Passo 1: Copia dei file

1. Copia l'intera cartella `sync-module` in:
   ```
   C:\xampp\htdocs\sync-module\
   ```

2. Verifica i permessi di scrittura su:
   - `config/` (per salvare la configurazione)
   - `logs/` (per i file di log)
   - `media/` (per i file scaricati)

### Passo 2: Avvia Apache

1. Apri **XAMPP Control Panel**
2. Clicca **Start** su **Apache**
3. Verifica che sia attivo (luce verde)

### Passo 3: Verifica installazione

Apri nel browser:
```
http://localhost/sync-module/
```

Dovresti vedere l'interfaccia di configurazione.

---

## Configurazione

### Configurazione tramite Interfaccia Web (Consigliato)

1. Apri `http://localhost/sync-module/`
2. Nella sezione **Configurazione**, inserisci:

   | Campo | Descrizione | Esempio |
   |-------|-------------|---------|
   | **Host** | Indirizzo server MySQL | `192.168.178.99` o `localhost` |
   | **Nome Database** | Nome del database | `mysanitario` |
   | **Utente** | Utente MySQL | `root` |
   | **Password** | Password MySQL | (lascia vuoto se nessuna) |
   | **ID Cliente/Monitor** | ID univoco dal server online | `Monitor-4CQLb` |

3. Clicca **💾 Salva Configurazione**
4. Clicca **🔌 Test Database** per verificare la connessione
5. Clicca **🌐 Test API Online** per verificare il collegamento al server

### Configurazione Manuale (Alternativa)

Modifica il file `config/local.php`:

```php
<?php
// === CONFIGURAZIONE DATABASE ===
define('DB_HOST', '192.168.178.99');     // IP o hostname del server MySQL
define('DB_USER', 'root');                // Utente MySQL
define('DB_PASSWORD', '');                // Password MySQL
define('DB_NAME', 'mysanitario');         // Nome database
define('DB_CHARSET', 'utf8mb4');

// === ID CLIENTE ===
define('CLIENT_ID', 'Monitor-4CQLb');     // ID dal server online

// === OPZIONI ===
define('SYNC_MEDIA_FILES', true);         // Scarica file multimediali
define('ENABLE_LOGGING', true);           // Abilita logging
define('LOG_LEVEL', 'INFO');              // DEBUG, INFO, WARNING, ERROR
```

---

## Utilizzo Interfaccia Web

### Accesso
```
http://localhost/sync-module/
```
oppure
```
http://[IP-SERVER]/sync-module/
```

### Sezioni disponibili:

#### 📊 Stato Sistema
Mostra lo stato attuale della connessione database e l'ID cliente configurato.

#### ⚙️ Configurazione
Permette di modificare le impostazioni di connessione e salvarle.

#### 🔄 Sincronizzazione
- **Sincronizzazione Completa**: Scarica turni, postazioni, operazioni e **AZZERA i dati esistenti**
- **Solo Configurazioni**: Aggiorna solo le configurazioni senza toccare i dati principali
- **Sincronizza file multimediali**: Scarica immagini di sfondo e pulsanti

#### 📋 Log
Visualizza gli ultimi log di sincronizzazione per debug.

---

## Utilizzo da Linea di Comando

### Sintassi
```bash
php sync.php [opzioni]
```

### Opzioni disponibili

| Opzione | Descrizione |
|---------|-------------|
| (nessuna) | Sincronizza solo configurazioni monitor/totem |
| `--full` | Sincronizzazione completa (turni, postazioni, operazioni) - **AZZERA DATI** |
| `--media` | Scarica file multimediali |
| `--full --media` | Sync completa + download media |
| `--no-media` | Disabilita download media |

### Esempi

```bash
# Solo configurazioni (non modifica turni/postazioni)
C:\xampp\php\php.exe C:\xampp\htdocs\sync-module\sync.php

# Sincronizzazione completa + media
C:\xampp\php\php.exe C:\xampp\htdocs\sync-module\sync.php --full --media

# Solo download immagini (non tocca il database)
C:\xampp\php\php.exe C:\xampp\htdocs\sync-module\sync.php --media
```

---

## Attività Pianificate

### File Batch disponibili

| File | Descrizione | Frequenza consigliata |
|------|-------------|----------------------|
| `sync_completo.bat` | Sync completa + media (AZZERA DATI) | 1 volta al giorno (es. 06:00) |
| `sync_media.bat` | Solo download media | Ogni ora |

### Installazione Automatica (Consigliato)

1. Apri **PowerShell come Amministratore**
2. Esegui:
   ```powershell
   cd C:\xampp\htdocs\sync-module\scripts
   .\installa_attivita_pianificate.ps1
   ```

3. (Opzionale) Personalizza orari:
   ```powershell
   .\installa_attivita_pianificate.ps1 -SyncCompletaOrario "07:00" -MediaIntervalloOre 2 -NotificheIntervalloMinuti 10
   ```

### Installazione Manuale

1. Apri **Utilità di pianificazione** (`taskschd.msc`)
2. Crea nuova attività:
   - **Nome**: `MySanitario_SyncCompleto`
   - **Programma**: `C:\xampp\htdocs\sync-module\scripts\sync_completo.bat`
   - **Trigger**: Giornaliero alle 06:00
3. Ripeti per `sync_media.bat` con trigger "Ogni ora"
4. Ripeti per `send_notification.bat` con trigger "Ogni 5 minuti"

### Disinstallazione Attività

```powershell
Unregister-ScheduledTask -TaskName "MySanitario_SyncCompleto" -Confirm:$false
Unregister-ScheduledTask -TaskName "MySanitario_SyncMedia" -Confirm:$false
Unregister-ScheduledTask -TaskName "MySanitario_Notifiche" -Confirm:$false
```

---

## Configurazione Email e Notifiche

### Accesso alla pagina di configurazione
```
http://localhost/sync-module/email.php
```

### Configurazione SMTP

Per inviare email, è necessario configurare un server SMTP. Supportati:
- **Gmail** (richiede "Password per le app")
- **Outlook/Office 365**
- **Server SMTP personalizzato**

| Campo | Descrizione | Esempio Gmail |
|-------|-------------|---------------|
| **Server SMTP** | Indirizzo del server | `smtp.gmail.com` |
| **Porta** | Porta SMTP | `587` (TLS) o `465` (SSL) |
| **Crittografia** | Tipo crittografia | `TLS` |
| **Username** | Email account | `tuaemail@gmail.com` |
| **Password** | Password o App Password | `xxxx xxxx xxxx xxxx` |

#### Configurazione Gmail

1. Vai su [myaccount.google.com](https://myaccount.google.com)
2. Sicurezza → Verifica in 2 passaggi (abilita se non attiva)
3. Sicurezza → Password per le app → Genera nuova password
4. Usa questa password nel campo "Password SMTP"

### Configurazione Notifiche Programmate

Nella sezione "Notifiche Programmate" puoi configurare:

| Campo | Descrizione |
|-------|-------------|
| **Abilita notifiche** | Attiva/disattiva l'invio automatico |
| **Email Destinatario** | Indirizzo a cui inviare i report |
| **Orario Invio** | Ora del giorno per l'invio (es. 08:00) |
| **Giorni della Settimana** | Seleziona i giorni in cui inviare |
| **Contenuto Report** | Scegli quali tipi di log includere |

### Test Email

1. Configura i parametri SMTP
2. Clicca **"Test Connessione SMTP"** per verificare la connessione
3. Inserisci un'email e clicca **"Invia Test"** per inviare un'email di prova
4. Clicca **"Invia Report Adesso"** per testare l'invio del report log

### Script di Notifica

Lo script `send_notification.bat` viene eseguito ogni 5 minuti dal Task Scheduler.
Verifica automaticamente se è il momento programmato e invia l'email solo all'orario configurato.

---

## API REST

### Endpoint
```
POST http://localhost/sync-module/api/sync.php
```

### Parametri
| Parametro | Tipo | Descrizione |
|-----------|------|-------------|
| `full` | boolean | Se true, sincronizza turni/postazioni/operazioni |
| `media` | boolean | Se true, scarica file multimediali |
| `client_id` | string | (Opzionale) Override dell'ID cliente |

### Esempio con cURL
```bash
curl -X POST http://localhost/sync-module/api/sync.php \
  -d "full=1&media=1"
```

### Risposta
```json
{
  "success": true,
  "message": "Sincronizzazione completata con successo",
  "stats": {
    "turni": 4,
    "postazioni": 9,
    "operazioni": 4,
    "config_monitor": 1,
    "config_totem": 1,
    "files_downloaded": 7
  },
  "timestamp": "2025-12-15 22:30:54"
}
```

---

## Risoluzione Problemi

### Errore: "Host not allowed to connect to MariaDB/MySQL"

**Causa**: L'utente MySQL non è autorizzato per connessioni remote.

**Soluzione**: Sul server MySQL, esegui:
```sql
GRANT ALL PRIVILEGES ON mysanitario.* TO 'root'@'%' IDENTIFIED BY '';
FLUSH PRIVILEGES;
```

---

### Errore: "Could not resolve host: uploads"

**Causa**: I percorsi dei file multimediali sono relativi invece che assoluti.

**Soluzione**: Aggiorna `SyncService.php` alla versione più recente che include la correzione.

---

### Errore: "Internal Server Error 500"

**Causa**: Errore di configurazione Apache o PHP.

**Soluzione**:
1. Controlla `C:\xampp\apache\logs\error.log`
2. Verifica che `.htaccess` non contenga direttive non permesse
3. Assicurati che le cartelle `logs/` e `media/` siano scrivibili

---

### I file multimediali non si scaricano

**Verifiche**:
1. L'opzione "Sincronizza file multimediali" è selezionata?
2. La cartella `media/images/` esiste ed è scrivibile?
3. Controlla i log in `logs/sync.log` per errori specifici

---

### Le attività pianificate non partono

**Verifiche**:
1. Lo script è eseguito come utente con permessi sufficienti?
2. Il percorso di PHP in `sync_completo.bat` è corretto?
3. Controlla i log in `logs/scheduled_sync.log`

---

## Contatti e Supporto

Per assistenza tecnica, contattare l'amministratore del sistema.

---

*Documento generato automaticamente - MySanitario Sync Module v1.0.0*
