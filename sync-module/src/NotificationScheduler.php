<?php
/**
 * NotificationScheduler - Gestione notifiche programmate
 */

namespace MySanitario\Sync;

class NotificationScheduler {
    private EmailService $emailService;
    private Logger $logger;
    private string $configFile;
    
    public function __construct() {
        $this->emailService = new EmailService();
        $this->logger = Logger::getInstance();
        $this->configFile = __DIR__ . '/../config/notifications.json';
    }
    
    /**
     * Ottieni configurazione notifiche
     */
    public function getConfig(): array {
        $default = [
            'enabled' => false,
            'email_to' => '',
            'schedule' => [
                'days' => [1, 2, 3, 4, 5], // Lun-Ven (1=Lun, 7=Dom)
                'time' => '08:00'
            ],
            'last_sent' => null,
            'include_warnings' => true,
            'include_errors' => true,
            'include_success' => true
        ];
        
        if (file_exists($this->configFile)) {
            $content = file_get_contents($this->configFile);
            $config = json_decode($content, true);
            if ($config) {
                return array_merge($default, $config);
            }
        }
        
        return $default;
    }
    
    /**
     * Salva configurazione notifiche
     */
    public function saveConfig(array $config): bool {
        $configDir = dirname($this->configFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $result = file_put_contents($this->configFile, json_encode($config, JSON_PRETTY_PRINT));
        
        if ($result) {
            $this->logger->info('Configurazione notifiche salvata', $config);
        }
        
        return $result !== false;
    }
    
    /**
     * Verifica se è il momento di inviare la notifica
     */
    public function shouldSendNow(): bool {
        $config = $this->getConfig();
        
        if (!$config['enabled'] || empty($config['email_to'])) {
            return false;
        }
        
        // Controlla giorno della settimana (1=Lun, 7=Dom)
        $currentDay = (int)date('N');
        if (!in_array($currentDay, $config['schedule']['days'])) {
            return false;
        }
        
        // Controlla orario (con tolleranza di 5 minuti)
        $scheduledTime = strtotime($config['schedule']['time']);
        $currentTime = strtotime(date('H:i'));
        $tolerance = 5 * 60; // 5 minuti
        
        if (abs($currentTime - $scheduledTime) > $tolerance) {
            return false;
        }
        
        // Controlla se già inviata oggi
        if ($config['last_sent']) {
            $lastSentDate = date('Y-m-d', strtotime($config['last_sent']));
            if ($lastSentDate === date('Y-m-d')) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Invia notifica programmata
     */
    public function sendScheduledNotification(): array {
        $result = [
            'success' => false,
            'message' => ''
        ];
        
        $config = $this->getConfig();
        
        if (!$config['enabled']) {
            $result['message'] = 'Notifiche disabilitate';
            return $result;
        }
        
        if (empty($config['email_to'])) {
            $result['message'] = 'Email destinatario non configurata';
            return $result;
        }
        
        if (!$this->emailService->isConfigured()) {
            $result['message'] = 'SMTP non configurato';
            return $result;
        }
        
        // Leggi i log delle ultime 24 ore
        $logs = $this->getRecentLogs(24);
        
        // Filtra log in base alle preferenze
        $filteredLogs = $this->filterLogs($logs, $config);
        
        // Invia email
        if ($this->emailService->sendLogReport($config['email_to'], $filteredLogs)) {
            // Aggiorna last_sent
            $config['last_sent'] = date('Y-m-d H:i:s');
            $this->saveConfig($config);
            
            $result['success'] = true;
            $result['message'] = 'Notifica inviata a ' . $config['email_to'];
            $this->logger->info('Notifica programmata inviata', ['to' => $config['email_to']]);
        } else {
            $result['message'] = 'Errore invio: ' . $this->emailService->getLastError();
            $this->logger->error('Errore invio notifica programmata', ['error' => $result['message']]);
        }
        
        return $result;
    }
    
    /**
     * Invia notifica manuale (ora)
     */
    public function sendNow(): array {
        $config = $this->getConfig();
        
        if (empty($config['email_to'])) {
            return ['success' => false, 'message' => 'Email destinatario non configurata'];
        }
        
        if (!$this->emailService->isConfigured()) {
            return ['success' => false, 'message' => 'SMTP non configurato'];
        }
        
        $logs = $this->getRecentLogs(24);
        $filteredLogs = $this->filterLogs($logs, $config);
        
        if ($this->emailService->sendLogReport($config['email_to'], $filteredLogs, 'manuale')) {
            return ['success' => true, 'message' => 'Report inviato a ' . $config['email_to']];
        } else {
            return ['success' => false, 'message' => 'Errore: ' . $this->emailService->getLastError()];
        }
    }
    
    /**
     * Leggi log recenti
     */
    private function getRecentLogs(int $hours): array {
        $logsDir = __DIR__ . '/../logs/';
        $logs = [];
        $cutoff = time() - ($hours * 3600);
        
        // Leggi tutti i file di log recenti
        $files = glob($logsDir . 'sync_*.log');
        
        // Aggiungi anche i log schedulati
        if (file_exists($logsDir . 'scheduled_sync.log')) {
            $files[] = $logsDir . 'scheduled_sync.log';
        }
        if (file_exists($logsDir . 'scheduled_media.log')) {
            $files[] = $logsDir . 'scheduled_media.log';
        }
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                continue;
            }
            
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                // Estrai timestamp dal log
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $logTime = strtotime($matches[1]);
                    if ($logTime >= $cutoff) {
                        $logs[] = $line;
                    }
                } else {
                    // Log senza timestamp, includi comunque
                    $logs[] = $line;
                }
            }
        }
        
        // Ordina per timestamp
        usort($logs, function($a, $b) {
            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $a, $ma);
            preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $b, $mb);
            $ta = isset($ma[1]) ? strtotime($ma[1]) : 0;
            $tb = isset($mb[1]) ? strtotime($mb[1]) : 0;
            return $ta - $tb;
        });
        
        return $logs;
    }
    
    /**
     * Filtra log in base alle preferenze
     */
    private function filterLogs(array $logs, array $config): array {
        return array_filter($logs, function($log) use ($config) {
            if (strpos($log, '[ERROR]') !== false) {
                return $config['include_errors'] ?? true;
            }
            if (strpos($log, '[WARNING]') !== false) {
                return $config['include_warnings'] ?? true;
            }
            if (strpos($log, '[INFO]') !== false) {
                return $config['include_success'] ?? true;
            }
            return true; // Include DEBUG e altri
        });
    }
    
    /**
     * Ottieni lista giorni per UI
     */
    public static function getDaysList(): array {
        return [
            1 => 'Lunedì',
            2 => 'Martedì',
            3 => 'Mercoledì',
            4 => 'Giovedì',
            5 => 'Venerdì',
            6 => 'Sabato',
            7 => 'Domenica'
        ];
    }
}
