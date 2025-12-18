# MySanitario Sync Module

Modulo PHP per la sincronizzazione delle configurazioni tra il server online MySanitario e il database locale.

## Funzionalità

- ✅ Download configurazioni da server online via API JSON
- ✅ Scrittura automatica nel database MySQL locale
- ✅ Download file multimediali (immagini sfondo, video)
- ✅ Interfaccia web per configurazione e monitoraggio
- ✅ API REST per integrazione con altri sistemi
- ✅ Esecuzione da linea di comando (CLI) per cron jobs
- ✅ Sistema di logging

## Requisiti

- PHP 7.4 o superiore
- Estensione MySQLi
- Estensione cURL
- Server web (Apache/Nginx) o PHP built-in server
- Database MySQL/MariaDB

## Installazione

### 1. Copia i file

Copia la cartella `sync-module` nella root del tuo server web o in una sottocartella.

### 2. Configura il modulo

Copia il file di configurazione esempio:

```bash
cp config/local.example.php config/local.php
```

Modifica `config/local.php` con i tuoi dati:

```php
// Database locale
define('DB_HOST', 'localhost');
define('DB_USER', 'tuo_utente');
define('DB_PASSWORD', 'tua_password');
define('DB_NAME', 'mysanitario');

// ID Cliente (dal pannello online)
define('CLIENT_ID', 'Monitor-XXXXX');
```

### 3. Crea le cartelle necessarie

```bash
mkdir -p logs storage/images storage/videos
chmod 755 logs storage storage/images storage/videos
```

### 4. Verifica permessi

Assicurati che il server web possa scrivere in:
- `config/` (per salvare configurazione da interfaccia web)
- `logs/` (per i file di log)
- `storage/` (per file multimediali scaricati)

## Utilizzo

### Interfaccia Web

Accedi a `http://tuo-server/sync-module/` per:
- Configurare i parametri di connessione
- Testare connessione database e API
- Eseguire sincronizzazione manuale
- Visualizzare i log

### Linea di Comando

```bash
# Sincronizzazione solo configurazioni
php sync.php

# Sincronizzazione completa (azzera turni/postazioni)
php sync.php --full

# Senza download file multimediali
php sync.php --no-media
```

### API REST

#### Stato sistema
```
GET /api/sync.php?action=status
```

#### Esegui sincronizzazione
```
POST /api/sync.php
Content-Type: application/x-www-form-urlencoded

action=sync&full_sync=0&sync_media=1
```

#### Test connessione database
```
GET /api/sync.php?action=test_db
```

#### Test connessione API online
```
GET /api/sync.php?action=test_api&client_id=Monitor-XXXXX
```

#### Visualizza log
```
GET /api/sync.php?action=logs&lines=50
```

## Cron Job

Per sincronizzazione automatica, aggiungi un cron job:

```bash
# Sincronizza ogni 15 minuti
*/15 * * * * /usr/bin/php /path/to/sync-module/sync.php >> /path/to/sync-module/logs/cron.log 2>&1

# Sincronizzazione completa ogni notte alle 3:00
0 3 * * * /usr/bin/php /path/to/sync-module/sync.php --full >> /path/to/sync-module/logs/cron.log 2>&1
```

## Struttura Files

```
sync-module/
├── api/
│   └── sync.php          # API REST
├── config/
│   ├── config.php        # Configurazione base
│   ├── local.php         # Configurazione locale (da creare)
│   └── local.example.php # Esempio configurazione
├── logs/                 # File di log (auto-creati)
├── src/
│   ├── Database.php      # Gestione connessione MySQL
│   ├── HttpClient.php    # Client HTTP per API
│   ├── Logger.php        # Sistema logging
│   └── SyncService.php   # Logica sincronizzazione
├── storage/
│   ├── images/           # Immagini scaricate
│   └── videos/           # Video scaricati
├── bootstrap.php         # Autoloader
├── index.php             # Interfaccia web
├── sync.php              # Script CLI
└── README.md             # Questo file
```

## Tabelle Sincronizzate

Il modulo sincronizza le seguenti tabelle:

| Tabella | Sync Completa | Sync Config |
|---------|---------------|-------------|
| turni | ✅ | ❌ |
| postazioni | ✅ | ❌ |
| operazioni | ✅ | ❌ |
| operazioni_giorni | ✅ | ❌ |
| operazioni_postazioni | ✅ | ❌ |
| operazioni_turni | ✅ | ❌ |
| contatori | ✅ (reset) | ❌ |
| configmonitor | ✅ | ✅ |
| configturnimonitor | ✅ | ✅ |
| configtotem | ✅ | ✅ |
| configturnitotem | ✅ | ✅ |

## Troubleshooting

### Errore connessione database
- Verifica i parametri in `config/local.php`
- Assicurati che MySQL sia in esecuzione
- Verifica che l'utente abbia i permessi sul database

### Errore API online
- Verifica che CLIENT_ID sia corretto
- Controlla la connessione internet
- Verifica che il server online sia raggiungibile

### File non scaricati
- Verifica permessi cartella `storage/`
- Controlla che cURL sia abilitato
- Verifica spazio disco disponibile

## Licenza

Proprietario - MySanitario
