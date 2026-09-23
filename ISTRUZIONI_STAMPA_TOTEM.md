# Istruzioni per la Stampa Automatica del Totem su Android

## IMPORTANTE: Stampa Diretta Senza Conferma

Il sistema è configurato per stampare **automaticamente senza finestre di dialogo**.

## Configurazione della Stampante Termica

### Requisiti
- Dispositivo Android (tablet o smartphone)
- Stampante termica a rullo (58mm o 80mm)
- Connessione via Bluetooth, USB o WiFi
- **Browser in modalità Kiosk con auto-print abilitato**

### METODO 1: Fully Kiosk Browser (CONSIGLIATO per stampa automatica)

Questo è il metodo migliore per ottenere stampa automatica senza finestre di dialogo.

#### Installazione:
1. **Scaricare "Fully Kiosk Browser"** da Google Play Store
2. Aprire l'app e configurare:

#### Configurazione Base:
- **Start URL**: `http://[indirizzo-server]/mysanitario/totem.php`
  - Se locale: `http://localhost/mysanitario/totem.php`
  - Se remoto: `http://192.168.x.x/mysanitario/totem.php`

#### Configurazione Stampa Automatica:
Nelle impostazioni di Fully Kiosk Browser:

1. **Settings > Web Content Settings**:
   - ✅ Enable JavaScript
   - ✅ Enable Popups
   - ✅ Allow Window Open

2. **Settings > Printing**:
   - ✅ Enable Auto Print
   - ✅ Print Automatically (no dialog)
   - Selezionare la **Default Printer** (la stampante termica)
   - Print Page Scale: **100%**
   - Print Margins: **None**

3. **Settings > Kiosk Mode**:
   - ✅ Enable Kiosk Mode
   - ✅ Hide Status Bar
   - ✅ Hide Navigation Bar
   - ✅ Prevent App Exit

4. **Settings > Advanced Web Settings**:
   - ✅ Auto Reload on Error
   - Reload on Idle: (opzionale, es. 300 secondi)

5. **Proteggere con password**:
   - Settings > Password Protection
   - Impostare una password per le impostazioni

#### Riavvio:
- Riavviare il tablet
- L'app si aprirà automaticamente in modalità kiosk
- La stampa avverrà automaticamente senza conferme

### METODO 2: Kiosk Browser Lite (Alternativa gratuita)

1. **Scaricare "Kiosk Browser Lockdown"** da Google Play Store
2. Configurare:
   - URL: indirizzo del totem.php
   - Enable Auto Print
   - Default Printer: selezionare la stampante
3. Abilitare modalità kiosk

### METODO 3: Chrome con Estensioni (Limitato)

**Nota**: Chrome standard mostra sempre la finestra di stampa. Tuttavia:

1. Installare Chrome su Android
2. Andare in `chrome://flags`
3. Cercare "Print Preview" e disabilitarlo (se disponibile)
4. Configurare stampante predefinita nelle impostazioni Android

**Limitazione**: La finestra apparirà comunque, ma sarà più veloce.

### METODO 4: App di Stampa Dedicata (Per stampanti ESC/POS)

Per stampanti che supportano comandi ESC/POS raw:

1. **Installare app come**:
   - "Bluetooth Printer" di JASI SOFT
   - "RawBT" (per Bluetooth)
   - "PrintHand" (supporta molte stampanti)

2. **Configurare l'app**:
   - Connettere alla stampante
   - Impostare come handler predefinito per file `.prn`

3. **Il sistema genererà anche file raw** che possono essere aperti automaticamente dall'app

## Connessione Stampante

### Bluetooth:
1. Impostazioni Android > Bluetooth
2. Accoppiare la stampante
3. Nelle impostazioni di stampa Android, selezionarla come predefinita

### USB (con OTG):
1. Collegare stampante tramite cavo USB-OTG
2. Android dovrebbe riconoscerla automaticamente
3. Selezionare come stampante predefinita

### WiFi:
1. Connettere stampante alla rete WiFi
2. Installare eventuali driver/app forniti dal produttore
3. Configurare indirizzo IP nelle impostazioni

## Come Funziona la Stampa Automatica

Il sistema utilizza **due metodi paralleli**:

### Metodo A - Popup Auto-Print:
- Apre `stampa_biglietto.php` in una finestra popup nascosta
- La pagina si auto-stampa tramite `window.print()` al caricamento
- Si chiude automaticamente dopo 1 secondo

### Metodo B - File ESC/POS Raw (opzionale):
- Genera un file `.prn` con comandi ESC/POS raw
- Compatibile con app Android di stampa diretta
- Invia comandi direttamente alla stampante termica

## Configurazione Stampante Predefinita su Android

### Android 11+:
1. **Impostazioni > Dispositivi connessi > Preferenze di connessione > Stampa**
2. Toccare "Servizio di stampa predefinito"
3. Selezionare la stampante termica
4. Abilitare il servizio

### Se la stampante non appare:
1. Scaricare l'app del produttore:
   - Epson: "Epson Print Enabler"
   - Star: "Star Print"
   - Generiche: "PrinterShare"
2. Installare e configurare
3. Torare nelle impostazioni di stampa Android

## Test della Stampa

1. Aprire `totem.php` sul tablet
2. Cliccare su un turno
3. **NON dovrebbe apparire alcuna finestra di dialogo**
4. Il biglietto dovrebbe stampare automaticamente
5. Appare solo il modale verde di conferma

## Risoluzione Problemi

### La finestra di stampa appare ancora:
- ✅ Verificare di usare **Fully Kiosk Browser** o simile
- ✅ Verificare che "Auto Print" sia abilitato nelle impostazioni
- ✅ Verificare che la stampante sia configurata come predefinita
- ❌ Chrome normale NON supporta auto-print senza dialogo

### La stampante non stampa:
- Verificare che sia accesa e connessa
- Verificare carta inserita correttamente
- Provare a stampare una pagina di test da Android
- Controllare lo stato della stampante nell'app del produttore

### Il popup viene bloccato:
- Nelle impostazioni del browser, consentire popup per il sito
- Oppure usare il metodo iframe (già implementato come fallback)

### Stampa lenta:
- Ridurre la qualità di stampa nelle impostazioni
- Verificare la velocità della connessione (Bluetooth può essere lento)
- Usare connessione USB se possibile

## Modalità Kiosk Completa

Per un'installazione fissa come totem:

### Con Fully Kiosk Browser:

1. **Configurare autostart**:
   - Settings > Autostart
   - ✅ Launch on Device Boot

2. **Bloccare navigazione**:
   - Settings > Navigation
   - ❌ Disable Address Bar
   - ❌ Disable Browser Menu
   - ✅ Disable Back Button

3. **Screensaver**:
   - Settings > Screensaver
   - URL: stesso del totem
   - Timeout: 60 secondi

4. **Impedire chiusura**:
   - Settings > Security
   - ✅ Prevent Task Killer
   - ✅ Prevent Uninstall
   - Password Protection: ON

5. **Nascondere barre**:
   - Settings > Appearance
   - ✅ Hide Status Bar
   - ✅ Hide Navigation Bar
   - ✅ Immersive Mode

## Schema di Funzionamento

```
1. Utente clicca bottone turno
         ↓
2. Sistema verifica orario
         ↓
3. Assegna numero da DB
         ↓
4. Apre stampa_biglietto.php in popup nascosto
         ↓
5. Pagina si auto-stampa (onload)
         ↓
6. Stampante riceve comando e stampa
         ↓
7. Popup si chiude automaticamente
         ↓
8. Modale verde conferma l'operazione
```

## File di Stampa Generati

### stampa_biglietto.php
- Genera HTML ottimizzato per stampante termica
- Si auto-stampa al caricamento
- Si chiude dopo la stampa

### stampa_raw.php
- Genera file `.prn` con comandi ESC/POS
- Compatibile con app di stampa Android
- Download automatico (opzionale)

## Note di Sicurezza

- Proteggere le impostazioni del browser kiosk con password
- Limitare l'accesso fisico al tablet
- Configurare timeout di inattività per risparmiare energia
- Testare il sistema prima della messa in produzione

## Supporto Produttori Stampanti

### Epson TM Series:
- App: "Epson TM Utility"
- Supporta auto-print via ePOS

### Star Micronics:
- App: "Star Print"  
- SDK disponibile per integrazione

### Generiche 58mm/80mm:
- Fully Kiosk Browser funziona con la maggior parte
- In alternativa, usare "RawBT" per Bluetooth

## Conclusione

Con **Fully Kiosk Browser** configurato correttamente, il sistema stamperà automaticamente senza alcuna finestra di dialogo. L'utente vedrà solo:
1. Il modale di conferma verde (3 secondi)
2. Il biglietto che esce dalla stampante

✅ **Nessuna finestra di stampa**
✅ **Nessuna conferma richiesta**
✅ **Stampa completamente automatica**
