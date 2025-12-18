<?php
/**
 * SyncService - Servizio principale di sincronizzazione
 * 
 * Gestisce:
 * - Download configurazioni da server online
 * - Scrittura nel database locale
 * - Download file multimediali
 */

namespace MySanitario\Sync;

class SyncService {
    private Database $db;
    private HttpClient $http;
    private Logger $logger;
    private string $clientId;
    private string $remoteApiUrl;
    private string $remoteFilesUrl;
    
    private array $syncStats = [
        'turni' => 0,
        'postazioni' => 0,
        'operazioni' => 0,
        'config_monitor' => 0,
        'config_totem' => 0,
        'files_downloaded' => 0,
        'errors' => []
    ];
    
    public function __construct(?string $clientId = null) {
        $this->db = Database::getInstance();
        $this->http = new HttpClient();
        $this->logger = Logger::getInstance();
        
        $this->clientId = $clientId ?? getClientId();
        $this->remoteApiUrl = defined('REMOTE_API_BASE') ? REMOTE_API_BASE : 'https://myeliminacode.acwild.eu/api/';
        $this->remoteFilesUrl = defined('REMOTE_FILES_BASE') ? REMOTE_FILES_BASE : 'https://myeliminacode.acwild.eu/appOffline/';
    }
    
    /**
     * Esegue la sincronizzazione completa
     */
    public function sync(bool $fullSync = false, bool $syncMedia = true): array {
        $this->logger->info('Inizio sincronizzazione', ['fullSync' => $fullSync, 'clientId' => $this->clientId]);
        
        $result = [
            'success' => false,
            'message' => '',
            'stats' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Verifica ID cliente
        if (empty($this->clientId)) {
            $result['message'] = 'ID Cliente non configurato';
            $this->logger->error($result['message']);
            return $result;
        }
        
        // Test connessione database
        $dbTest = $this->db->testConnection();
        if (!$dbTest['success']) {
            $result['message'] = 'Errore connessione database: ' . $dbTest['message'];
            $this->logger->error($result['message']);
            return $result;
        }
        
        // Scarica configurazione da server online
        $remoteData = $this->fetchRemoteConfiguration();
        if ($remoteData === null) {
            $result['message'] = 'Errore download configurazione da server online';
            $this->logger->error($result['message'], ['error' => $this->http->getLastError()]);
            return $result;
        }
        
        // Sincronizza le varie tabelle
        try {
            if ($fullSync) {
                $this->syncTurni($remoteData['san_turni'] ?? []);
                $this->syncPostazioni($remoteData['san_postazioni'] ?? []);
                $this->syncOperazioni($remoteData['san_operazioni'] ?? []);
                $this->syncOperazioniGiorni($remoteData['san_operazioni_giorni'] ?? []);
                $this->syncOperazioniPostazioni($remoteData['san_operazioni_postazioni'] ?? []);
                $this->syncOperazioniTurni($remoteData['san_operazioni_turni'] ?? []);
            }
            
            // Configurazioni monitor e totem (sempre sincronizzate)
            $this->syncConfigMonitor($remoteData['configmonitor'] ?? []);
            $this->syncConfigTurniMonitor($remoteData['configturnimonitor'] ?? []);
            $this->syncConfigTotem($remoteData['configtotem'] ?? []);
            $this->syncConfigTurniTotem($remoteData['configturnitotem'] ?? []);
            
            // Download file multimediali
            if ($syncMedia && defined('SYNC_MEDIA_FILES') && SYNC_MEDIA_FILES) {
                $this->syncMediaFiles($remoteData);
            }
            
            $result['success'] = true;
            $result['message'] = 'Sincronizzazione completata con successo';
            $result['stats'] = $this->syncStats;
            
            $this->logger->info('Sincronizzazione completata', $this->syncStats);
            
        } catch (\Exception $e) {
            $result['message'] = 'Errore durante sincronizzazione: ' . $e->getMessage();
            $this->logger->error($result['message'], ['exception' => $e->getMessage()]);
            $this->syncStats['errors'][] = $e->getMessage();
            $result['stats'] = $this->syncStats;
        }
        
        return $result;
    }
    
    /**
     * Sincronizza SOLO i file multimediali (senza modificare il database)
     */
    public function syncMediaOnly(): array {
        $this->logger->info('Inizio download solo media', ['clientId' => $this->clientId]);
        
        $result = [
            'success' => false,
            'message' => '',
            'stats' => [],
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Verifica ID cliente
        if (empty($this->clientId)) {
            $result['message'] = 'ID Cliente non configurato';
            $this->logger->error($result['message']);
            return $result;
        }
        
        // Scarica configurazione da server online per ottenere i path dei file
        $remoteData = $this->fetchRemoteConfiguration();
        if ($remoteData === null) {
            $result['message'] = 'Errore download configurazione da server online';
            $this->logger->error($result['message'], ['error' => $this->http->getLastError()]);
            return $result;
        }
        
        try {
            // Download solo file multimediali
            $this->syncMediaFiles($remoteData);
            
            $result['success'] = true;
            $result['message'] = 'Download media completato con successo';
            $result['stats'] = $this->syncStats;
            
            $this->logger->info('Download media completato', $this->syncStats);
            
        } catch (\Exception $e) {
            $result['message'] = 'Errore durante download media: ' . $e->getMessage();
            $this->logger->error($result['message'], ['exception' => $e->getMessage()]);
            $this->syncStats['errors'][] = $e->getMessage();
            $result['stats'] = $this->syncStats;
        }
        
        return $result;
    }
    
    /**
     * Scarica configurazione dal server remoto
     */
    private function fetchRemoteConfiguration(): ?array {
        $url = $this->remoteApiUrl . 'get_json_config.php';
        $data = $this->http->getJson($url, ['idmonitor' => $this->clientId]);
        
        if ($data === null) {
            $this->logger->error('Errore fetch configurazione remota', [
                'url' => $url,
                'error' => $this->http->getLastError(),
                'httpCode' => $this->http->getLastHttpCode()
            ]);
        }
        
        return $data;
    }
    
    /**
     * Sincronizza tabella turni
     */
    private function syncTurni(array $turni): void {
        if (empty($turni)) return;
        
        // Pulisci tabella
        $this->db->execute("DELETE FROM turni");
        $this->db->execute("DELETE FROM contatori");
        
        foreach ($turni as $turno) {
            $sql = sprintf(
                "INSERT INTO turni (id_turno, turno, stato, desstato, priorita) VALUES (%d, '%s', '%s', '%s', %d)",
                (int)$turno['id_turno'],
                $this->db->escape($turno['turno']),
                $this->db->escape($turno['stato']),
                $this->db->escape($turno['desstato'] ?? ''),
                (int)($turno['priorita'] ?? 0)
            );
            
            if ($this->db->execute($sql)) {
                // Crea contatori per questo turno
                $this->createCountersForTurn((int)$turno['id_turno']);
                $this->syncStats['turni']++;
            }
        }
        
        $this->logger->debug('Sincronizzati turni', ['count' => $this->syncStats['turni']]);
    }
    
    /**
     * Crea i contatori per un turno
     */
    private function createCountersForTurn(int $idTurno): void {
        $today = date('Ymd');
        
        // Contatore CODA (numeri emessi)
        $this->db->execute(sprintf(
            "INSERT INTO contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) 
             VALUES ('CODA', %d, 0, 0, '%s', '', 0)",
            $idTurno, $today
        ));
        
        // Contatore NUMERO (numeri chiamati)
        $this->db->execute(sprintf(
            "INSERT INTO contatori (tipo, id_turno, id_postazione, numero, data, ora, consecutivi) 
             VALUES ('NUMERO', %d, 0, 0, '%s', '', 0)",
            $idTurno, $today
        ));
    }
    
    /**
     * Sincronizza tabella postazioni
     */
    private function syncPostazioni(array $postazioni): void {
        if (empty($postazioni)) return;
        
        $this->db->execute("DELETE FROM postazioni");
        
        foreach ($postazioni as $post) {
            $sql = sprintf(
                "INSERT INTO postazioni (id_postazione, postazione, descrizione, turno_ambulatorio) 
                 VALUES (%d, '%s', '%s', '%s')",
                (int)$post['id_postazione'],
                $this->db->escape($post['postazione']),
                $this->db->escape($post['descrizione'] ?? ''),
                $this->db->escape($post['turno_ambulatorio'] ?? '')
            );
            
            if ($this->db->execute($sql)) {
                $this->syncStats['postazioni']++;
            }
        }
        
        $this->logger->debug('Sincronizzate postazioni', ['count' => $this->syncStats['postazioni']]);
    }
    
    /**
     * Sincronizza tabella operazioni
     */
    private function syncOperazioni(array $operazioni): void {
        if (empty($operazioni)) return;
        
        $this->db->execute("DELETE FROM operazioni");
        
        foreach ($operazioni as $op) {
            $sql = sprintf(
                "INSERT INTO operazioni (id_operazione, operazione) VALUES (%d, '%s')",
                (int)$op['id_operazione'],
                $this->db->escape($op['operazione'])
            );
            
            if ($this->db->execute($sql)) {
                $this->syncStats['operazioni']++;
            }
        }
    }
    
    /**
     * Sincronizza tabella operazioni_giorni
     */
    private function syncOperazioniGiorni(array $data): void {
        if (empty($data)) return;
        
        $this->db->execute("DELETE FROM operazioni_giorni");
        
        foreach ($data as $item) {
            $sql = sprintf(
                "INSERT INTO operazioni_giorni (pk_opgio, id_operazione, giorno, ora_inizio, ora_fine, note) 
                 VALUES (%d, %d, %d, '%s', '%s', '%s')",
                (int)$item['pk_opgio'],
                (int)$item['id_operazione'],
                (int)$item['giorno'],
                $this->db->escape(str_replace(':', '', $item['ora_inizio'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_fine'] ?? '')),
                $this->db->escape($item['note'] ?? '')
            );
            $this->db->execute($sql);
        }
    }
    
    /**
     * Sincronizza tabella operazioni_postazioni
     */
    private function syncOperazioniPostazioni(array $data): void {
        if (empty($data)) return;
        
        $this->db->execute("DELETE FROM operazioni_postazioni");
        
        foreach ($data as $item) {
            $sql = sprintf(
                "INSERT INTO operazioni_postazioni 
                 (id_postazione, id_operazione, ora_inizio1, ora_fine1, ora_inizio2, ora_fine2, 
                  ora_inizio3, ora_fine3, ora_inizio4, ora_fine4) 
                 VALUES (%d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                (int)$item['id_postazione'],
                (int)$item['id_operazione'],
                $this->db->escape(str_replace(':', '', $item['ora_inizio1'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_fine1'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_inizio2'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_fine2'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_inizio3'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_fine3'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_inizio4'] ?? '')),
                $this->db->escape(str_replace(':', '', $item['ora_fine4'] ?? ''))
            );
            $this->db->execute($sql);
        }
    }
    
    /**
     * Sincronizza tabella operazioni_turni
     */
    private function syncOperazioniTurni(array $data): void {
        if (empty($data)) return;
        
        $this->db->execute("DELETE FROM operazioni_turni");
        
        foreach ($data as $item) {
            $sql = sprintf(
                "INSERT INTO operazioni_turni (id_turno, id_operazione) VALUES (%d, %d)",
                (int)$item['id_turno'],
                (int)$item['id_operazione']
            );
            $this->db->execute($sql);
        }
    }
    
    /**
     * Sincronizza configurazione monitor
     */
    private function syncConfigMonitor(array $configs): void {
        if (empty($configs)) return;
        
        $this->db->execute("DELETE FROM configmonitor");
        
        foreach ($configs as $cfg) {
            $sql = sprintf(
                "INSERT INTO configmonitor 
                 (idconfigmonitor, immaginesfondo, ximmaginesfondo, yimmaginesfondo,
                  boximmagini, hboximmagini, wboximmagini, xboximmagini, yboximmagini,
                  boxvideo, hboxvideo, wboxvideo, xboxvideo, yboxvideo,
                  boxmeteo, hboxmeteo, wboxmeteo, xboxmeteo, yboxmeteo, urlboxmeteo,
                  boxnews, hboxnews, wboxnews, xboxnews, yboxnews, urlboxnews,
                  boxchiamati, hboxchiamati, wboxchiamati, xboxchiamati, yboxchiamati,
                  fontboxchiamati, boldboxchiamati,
                  numatuttoschermo, fontnumatuttoschermo, boldnumatuttoschermo,
                  fontturnonumatuttoschermo, boldturnonumatuttoschermo, imgsfondonumatuttoschermo)
                 VALUES (%d, '%s', %d, %d, '%s', %d, %d, %d, %d, '%s', %d, %d, %d, %d,
                         '%s', %d, %d, %d, %d, '%s', '%s', %d, %d, %d, %d, '%s',
                         '%s', %d, %d, %d, %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                (int)($cfg['idconfigmonitor'] ?? 0),
                $this->db->escape($cfg['immaginesfondo'] ?? ''),
                (int)($cfg['ximmaginesfondo'] ?? 0),
                (int)($cfg['yimmaginesfondo'] ?? 0),
                $this->db->escape($cfg['boximmagini'] ?? '0'),
                (int)($cfg['hboximmagini'] ?? 0),
                (int)($cfg['wboximmagini'] ?? 0),
                (int)($cfg['xboximmagini'] ?? 0),
                (int)($cfg['yboximmagini'] ?? 0),
                $this->db->escape($cfg['boxvideo'] ?? '0'),
                (int)($cfg['hboxvideo'] ?? 0),
                (int)($cfg['wboxvideo'] ?? 0),
                (int)($cfg['xboxvideo'] ?? 0),
                (int)($cfg['yboxvideo'] ?? 0),
                $this->db->escape($cfg['boxmeteo'] ?? '0'),
                (int)($cfg['hboxmeteo'] ?? 0),
                (int)($cfg['wboxmeteo'] ?? 0),
                (int)($cfg['xboxmeteo'] ?? 0),
                (int)($cfg['yboxmeteo'] ?? 0),
                $this->db->escape($cfg['urlboxmeteo'] ?? ''),
                $this->db->escape($cfg['boxnews'] ?? '0'),
                (int)($cfg['hboxnews'] ?? 0),
                (int)($cfg['wboxnews'] ?? 0),
                (int)($cfg['xboxnews'] ?? 0),
                (int)($cfg['yboxnews'] ?? 0),
                $this->db->escape($cfg['urlboxnews'] ?? ''),
                $this->db->escape($cfg['boxchiamati'] ?? '0'),
                (int)($cfg['hboxchiamati'] ?? 0),
                (int)($cfg['wboxchiamati'] ?? 0),
                (int)($cfg['xboxchiamati'] ?? 0),
                (int)($cfg['yboxchiamati'] ?? 0),
                $this->db->escape($cfg['fontboxchiamati'] ?? ''),
                $this->db->escape($cfg['boldboxchiamati'] ?? '0'),
                $this->db->escape($cfg['numatuttoschermo'] ?? '0'),
                $this->db->escape($cfg['fontnumatuttoschermo'] ?? ''),
                $this->db->escape($cfg['boldnumatuttoschermo'] ?? '0'),
                $this->db->escape($cfg['fontturnonumatuttoschermo'] ?? ''),
                $this->db->escape($cfg['boldturnonumatuttoschermo'] ?? '0'),
                $this->db->escape($cfg['imgsfondonumatuttoschermo'] ?? '')
            );
            
            if ($this->db->execute($sql)) {
                $this->syncStats['config_monitor']++;
            }
        }
    }
    
    /**
     * Sincronizza configurazione turni monitor
     */
    private function syncConfigTurniMonitor(array $configs): void {
        if (empty($configs)) return;
        
        $this->db->execute("DELETE FROM configturnimonitor");
        
        foreach ($configs as $cfg) {
            $sql = sprintf(
                "INSERT INTO configturnimonitor 
                 (idconfigturnimonitor, idturno, fontturno, sizeturno, boldturno, coloreturno,
                  xturno, yturno, hturno, wturno,
                  fontcontatore, sizecontatore, boldcontatore, colorecontatore,
                  xcontatore, ycontatore, hcontatore, wcontatore,
                  fontpostazione, sizepostazione, boldpostazione, colorepostazione,
                  xpostazione, ypostazione, hpostazione, wpostazione)
                 VALUES (%d, %d, '%s', %d, '%s', '%s', %d, %d, %d, %d,
                         '%s', %d, '%s', '%s', %d, %d, %d, %d,
                         '%s', %d, '%s', '%s', %d, %d, %d, %d)",
                (int)($cfg['idconfigturnimonitor'] ?? 0),
                (int)($cfg['idturno'] ?? 0),
                $this->db->escape($cfg['fontturno'] ?? ''),
                (int)($cfg['sizeturno'] ?? 0),
                $this->db->escape($cfg['boldturno'] ?? '0'),
                $this->db->escape($cfg['coloreturno'] ?? ''),
                (int)($cfg['xturno'] ?? 0),
                (int)($cfg['yturno'] ?? 0),
                (int)($cfg['hturno'] ?? 0),
                (int)($cfg['wturno'] ?? 0),
                $this->db->escape($cfg['fontcontatore'] ?? ''),
                (int)($cfg['sizecontatore'] ?? 0),
                $this->db->escape($cfg['boldcontatore'] ?? '0'),
                $this->db->escape($cfg['colorecontatore'] ?? ''),
                (int)($cfg['xcontatore'] ?? 0),
                (int)($cfg['ycontatore'] ?? 0),
                (int)($cfg['hcontatore'] ?? 0),
                (int)($cfg['wcontatore'] ?? 0),
                $this->db->escape($cfg['fontpostazione'] ?? ''),
                (int)($cfg['sizepostazione'] ?? 0),
                $this->db->escape($cfg['boldpostazione'] ?? '0'),
                $this->db->escape($cfg['colorepostazione'] ?? ''),
                (int)($cfg['xpostazione'] ?? 0),
                (int)($cfg['ypostazione'] ?? 0),
                (int)($cfg['hpostazione'] ?? 0),
                (int)($cfg['wpostazione'] ?? 0)
            );
            $this->db->execute($sql);
        }
    }
    
    /**
     * Sincronizza configurazione totem
     */
    private function syncConfigTotem(array $configs): void {
        if (empty($configs)) return;
        
        $this->db->execute("DELETE FROM configtotem");
        
        foreach ($configs as $cfg) {
            $sql = sprintf(
                "INSERT INTO configtotem 
                 (idconfigtotem, idcliente, immaginesfondo, ximmaginesfondo, yimmaginesfondo)
                 VALUES (%d, %d, '%s', %d, %d)",
                (int)($cfg['idconfigtotem'] ?? 0),
                (int)($cfg['idcliente'] ?? 0),
                $this->db->escape($cfg['immaginesfondo'] ?? ''),
                (int)($cfg['ximmaginesfondo'] ?? 0),
                (int)($cfg['yimmaginesfondo'] ?? 0)
            );
            
            if ($this->db->execute($sql)) {
                $this->syncStats['config_totem']++;
            }
        }
    }
    
    /**
     * Sincronizza configurazione turni totem
     */
    private function syncConfigTurniTotem(array $configs): void {
        if (empty($configs)) return;
        
        $this->db->execute("DELETE FROM configturnitotem");
        
        foreach ($configs as $cfg) {
            $sql = sprintf(
                "INSERT INTO configturnitotem 
                 (idconfigturnitotem, idconfigtotem, idturno, fontturno, sizeturno, boldturno,
                  coloreturno, xturno, yturno, hturno, wturno, sfondobutton)
                 VALUES (%d, %d, %d, '%s', %d, '%s', '%s', %d, %d, %d, %d, '%s')",
                (int)($cfg['idconfigturnitotem'] ?? 0),
                (int)($cfg['idconfigtotem'] ?? 0),
                (int)($cfg['idturno'] ?? 0),
                $this->db->escape($cfg['fontturno'] ?? ''),
                (int)($cfg['sizeturno'] ?? 0),
                $this->db->escape($cfg['boldturno'] ?? '0'),
                $this->db->escape($cfg['coloreturno'] ?? ''),
                (int)($cfg['xturno'] ?? 0),
                (int)($cfg['yturno'] ?? 0),
                (int)($cfg['hturno'] ?? 0),
                (int)($cfg['wturno'] ?? 0),
                $this->db->escape($cfg['sfondobutton'] ?? '')
            );
            $this->db->execute($sql);
        }
    }
    
    /**
     * Sincronizza file multimediali (immagini e video)
     */
    private function syncMediaFiles(array $remoteData): void {
        $imagesPath = defined('CUSTOM_IMAGES_PATH') && CUSTOM_IMAGES_PATH 
            ? CUSTOM_IMAGES_PATH 
            : (defined('IMAGES_PATH') ? IMAGES_PATH : dirname(__DIR__) . '/storage/images');
            
        $videosPath = defined('CUSTOM_VIDEOS_PATH') && CUSTOM_VIDEOS_PATH 
            ? CUSTOM_VIDEOS_PATH 
            : (defined('VIDEOS_PATH') ? VIDEOS_PATH : dirname(__DIR__) . '/storage/videos');
        
        // Crea directory se non esistono
        if (!is_dir($imagesPath)) mkdir($imagesPath, 0755, true);
        if (!is_dir($videosPath)) mkdir($videosPath, 0755, true);
        
        // Download sfondo monitor
        if (!empty($remoteData['configmonitor'])) {
            foreach ($remoteData['configmonitor'] as $cfg) {
                if (!empty($cfg['immaginesfondo'])) {
                    $this->downloadMediaFile($cfg['immaginesfondo'], $imagesPath, 'sfondomonitor');
                }
                if (!empty($cfg['imgsfondonumatuttoschermo'])) {
                    $this->downloadMediaFile($cfg['imgsfondonumatuttoschermo'], $imagesPath, 'sfondotuttoschermo');
                }
            }
        }
        
        // Download sfondo totem
        if (!empty($remoteData['configtotem'])) {
            foreach ($remoteData['configtotem'] as $cfg) {
                if (!empty($cfg['immaginesfondo'])) {
                    $this->downloadMediaFile($cfg['immaginesfondo'], $imagesPath, 'sfondototem');
                }
            }
        }
        
        // Download sfondi pulsanti turni totem
        if (!empty($remoteData['configturnitotem'])) {
            foreach ($remoteData['configturnitotem'] as $cfg) {
                if (!empty($cfg['sfondobutton'])) {
                    $turno = $cfg['idturno'] ?? 0;
                    $this->downloadMediaFile($cfg['sfondobutton'], $imagesPath, 'sfondoturno_' . $turno);
                }
            }
        }
    }
    
    /**
     * Scarica un file multimediale
     */
    private function downloadMediaFile(string $remoteUrl, string $localPath, string $baseName): bool {
        if (empty($remoteUrl)) return false;
        
        // Converti percorso relativo in URL assoluto
        // Se l'URL non inizia con http:// o https://, aggiungi il base URL del server
        if (!preg_match('#^https?://#i', $remoteUrl)) {
            // Rimuovi eventuali slash iniziali
            $remoteUrl = ltrim($remoteUrl, '/\\');
            // Costruisci URL completo usando il base URL del server online
            $baseUrl = defined('REMOTE_UPLOADS_BASE') 
                ? REMOTE_UPLOADS_BASE 
                : 'https://myeliminacode.acwild.eu/';
            $remoteUrl = rtrim($baseUrl, '/') . '/' . $remoteUrl;
        }
        
        // Estrai estensione dal URL remoto
        $ext = pathinfo(parse_url($remoteUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
        if (empty($ext)) $ext = 'png';
        
        $localFile = rtrim($localPath, '/\\') . '/' . $baseName . '.' . $ext;
        
        $this->logger->debug('Download file', ['url' => $remoteUrl, 'local' => $localFile]);
        
        if ($this->http->downloadFile($remoteUrl, $localFile)) {
            $this->syncStats['files_downloaded']++;
            return true;
        }
        
        $this->logger->warning('Errore download file', [
            'url' => $remoteUrl,
            'error' => $this->http->getLastError()
        ]);
        
        return false;
    }
    
    /**
     * Restituisce le statistiche dell'ultima sincronizzazione
     */
    public function getStats(): array {
        return $this->syncStats;
    }
}
